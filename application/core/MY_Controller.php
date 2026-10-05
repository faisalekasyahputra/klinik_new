<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MY_Controller Class
 * 
 * Base Application Controller providing security headers on every response.
 * Adapted from kliknikpkp_styling for klinik_new (lighter version - no auth_lib/encryption_lib/audit_model).
 */
class MY_Controller extends CI_Controller {

    public function __construct() {
        parent::__construct();

        // NOW()/CURRENT_TIMESTAMP MySQL harus sama dengan date() PHP (Asia/Jakarta, lihat index.php).
        // Offset tetap, bukan nama zona: tabel zona waktu MySQL tidak selalu terisi di hosting.
        // ponytail: WIB tanpa DST jadi +07:00 aman; ganti ke nama zona bila suatu saat pindah ke zona ber-DST.
        // NAMES: collation koneksi eksplisit dari config/database.php (char_set, dbcollat); tanpa
        // ini literal string dibandingkan dengan uca1400_ai_ci di 11.8 dan general_ci di 10.4.
        $this->db->query("SET time_zone = '+07:00', NAMES ".$this->db->char_set.' COLLATE '.$this->db->dbcollat);

        // Load essential helpers
        $this->load->helper(['url', 'form', 'security']);
        $this->load->library('session');
        $this->enforce_positive_input_validation();

        // Set OWASP security headers on every response
        $this->set_security_headers();

        // Kontrol anti-otomatisasi GLOBAL (poin 10.4): dilewati SEMUA controller, paling awal
        // supaya permintaan yang ditolak tidak sempat menyentuh sesi/DB lebih jauh.
        $this->enforce_anti_automation();

        // Kebijakan metode HTTP dan kebersihan URI (poin 12.2 dan 12.4).
        $this->enforce_http_policy();

        // Validasi skema per-endpoint untuk API dan layanan web (poin 12.5).
        $this->enforce_api_schema();

        // Penyapu retensi harian, dijalankan SESUDAH respons terkirim (poin 7.3).
        $this->jadwalkan_retensi();

        $this->usir_kalau_nonaktif();
        $this->enforce_single_session_and_password_expiry();
        $this->enforce_onboarding();
    }

    /**
     * Sesi tanpa peran (akun baru yang belum menyelesaikan onboarding) hanya boleh berada di
     * controller Auth: onboarding, save_onboarding, logout. Tanpa ini akun baru bisa meninggalkan
     * onboarding dan membuka /akun dengan peran kosong (ditemukan 3 Okt 2026).
     */
    private function enforce_onboarding() {
        if ( ! $this->session->userdata('is_logged') || ! empty($this->session->userdata('role'))) { return; }
        if (strtolower((string) $this->router->fetch_class()) === 'auth') { return; }
        if ($this->input->is_ajax_request()) {
            $this->output->set_status_header(403); header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'code' => 'onboarding_belum_selesai', 'message' => 'Lengkapi pendaftaran dan pilih peran Anda terlebih dahulu.']); exit;
        }
        redirect('Auth/onboarding'); exit;
    }

    /**
     * Kontrol anti-otomatisasi global (form keamanan poin 10.4, docs/engineering/ANTI_OTOMATISASI.md).
     *   1. User-Agent alat serangan/pemindai yang dikenal: 403 + peringatan ke admin.
     *   2. Batas laju global/tulis/kelas rute/unggahan (libraries/Anti_automation.php): 429 +
     *      Retry-After + X-Security-Warning, dan peringatan ke admin pada pelampauan pertama.
     * Loopback dan IP di ANTI_OTOMATISASI_IP_DIIZINKAN dikecualikan. FAIL-OPEN bila penyimpanan
     * pembatas laju gagal (jalur ini dilalui SETIAP halaman).
     */
    private function enforce_anti_automation()
    {
        if ($this->input->is_cli_request()) { return; }
        try {
            $ip = (string) $this->input->ip_address();
            if (anti_automation_ip_allowed($ip)) { return; }

            if (anti_automation_is_scanner($this->input->user_agent())) {
                $this->load->library('Security_alert');
                $this->security_alert->raise('pemindai_ua', 'sedang',
                    'Alat pemindai/serangan dikenali dari User-Agent dan permintaannya ditolak (403)',
                    ['route' => strtolower((string) $this->router->fetch_class()) . '/' . strtolower((string) $this->router->fetch_method())],
                    'ua');
                $this->output->set_status_header(403)->set_content_type('text/plain', 'utf-8')->set_output('Akses ditolak.');
                $this->output->_display(); exit;
            }

            $this->load->library('Anti_automation');
            $verdict = $this->anti_automation->guard([
                'account_id'  => $this->session->userdata('is_logged') ? (int) $this->session->userdata('user_id') : 0,
                'controller'  => $this->router->fetch_class(),
                'method'      => $this->router->fetch_method(),
                'http_method' => $this->input->method(TRUE),
                // Hanya permintaan yang SUNGGUH membawa berkas (kolom berkas kosong = UPLOAD_ERR_NO_FILE tidak dihitung).
                'has_files'   => (bool) array_filter((array) $_FILES, static function ($f) {
                    return is_array($f) && (is_array($f['error'] ?? NULL) || (int) ($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);
                }),
            ]);
            if ( ! empty($verdict['blocked'])) {
                $this->rate_limit_reject($verdict['result'],
                    'Terlalu banyak permintaan dalam waktu singkat. Silakan tunggu sebentar lalu coba lagi.',
                    $this->input->is_ajax_request()
                        || anti_automation_route_is_json($this->router->fetch_class(), $this->router->fetch_method()));
                $this->output->_display(); exit;
            }
        } catch (Throwable $e) {
            // Pengamat tidak boleh menjadi titik gagal: catat, lanjutkan.
            log_message('error', 'enforce_anti_automation gagal (fail-open): ' . $e->getMessage());
        }
    }

    /**
     * Penyapu retensi (poin 7.3, libraries/Penyapu_retensi.php): sekali per interval (bawaan 24 jam), dipicu oleh
     * permintaan web pertama yang mendapati penanda basi, dijalankan SESUDAH respons terkirim ke klien
     * dan dijaga flock supaya hanya satu proses yang menyapu. Murah pada jalur biasa: satu stat() berkas.
     * Gagal diam-diam (dicatat): penyapu tidak boleh menggagalkan permintaan yang sedang dilayani.
     */
    private function jadwalkan_retensi()
    {
        static $terdaftar = FALSE;
        if ($terdaftar || $this->input->is_cli_request()) { return; }
        try {
            $this->config->load('data_lifecycle', TRUE);
            $interval = (int) ($this->config->item('data_lifecycle', 'data_lifecycle')['retensi']['interval_detik'] ?? 86400);
            $marker = APPPATH . 'cache' . DIRECTORY_SEPARATOR . 'retensi_terakhir';
            if ( ! is_dir(dirname($marker)) || (is_file($marker) && (int) @filemtime($marker) > time() - $interval)) { return; }
            $terdaftar = TRUE;
            $ci = $this;
            register_shutdown_function(function () use ($ci, $marker, $interval) {
                if (function_exists('fastcgi_finish_request')) { @fastcgi_finish_request(); }
                elseif (function_exists('litespeed_finish_request')) { @litespeed_finish_request(); }
                $fh = @fopen($marker, 'c');
                if ( ! $fh) { return; }
                try {
                    if ( ! flock($fh, LOCK_EX | LOCK_NB)) { return; }
                    clearstatcache(true, $marker);
                    if (filesize($marker) > 0 && (int) filemtime($marker) > time() - $interval) { return; }   // proses lain baru saja menyapu
                    ftruncate($fh, 0); fwrite($fh, date('c')); fflush($fh);   // tandai dulu: galat di tengah jalan tidak memicu ulang tiap permintaan
                    $ci->load->library('Penyapu_retensi');
                    $hasil = $ci->penyapu_retensi->jalankan(FALSE);
                    $ci->penyapu_retensi->catat($hasil, 'sistem');
                } catch (Throwable $e) {
                    log_message('error', 'Penyapu retensi gagal: ' . $e->getMessage());
                } finally {
                    @flock($fh, LOCK_UN); @fclose($fh);
                }
            });
        } catch (Throwable $e) {
            log_message('error', 'jadwalkan_retensi gagal: ' . $e->getMessage());
        }
    }

    /**
     * Catat akses STAF ke informasi pribadi (poin 7.3): berkas privat yang dibuka dan data terdekripsi yang
     * ditampilkan. Pemilik yang melihat datanya sendiri tidak dicatat. Ditekan per pelaku+objek (bawaan 10
     * menit) supaya menyegarkan halaman tidak membanjiri jejak audit. Gagal diam-diam (dicatat).
     */
    protected function catat_akses_data_pribadi($jenis, $objek_tipe, $objek_id, array $detail = [])
    {
        try {
            $this->config->load('data_lifecycle', TRUE);
            $cfg = $this->config->item('data_lifecycle', 'data_lifecycle')['audit'];
            $peran = (string) $this->session->userdata('role');
            if ( ! in_array($peran, $cfg['peran_staf'], TRUE)) { return FALSE; }
            $this->load->library('Rate_limiter');
            $kunci = hash('sha256', (int) $this->get_user_id() . '|' . $jenis . '|' . $objek_tipe . '|' . $objek_id);
            $r = $this->rate_limiter->hit_fast('audit_akses_dedupe', ['key' => $kunci]);
            if ( ! empty($r['success']) && (int) ($r['count'] ?? 1) > 1) { return FALSE; }
            return $this->catat_audit('akses_' . $jenis, 'Staf mengakses informasi pribadi: ' . $jenis . ' (' . $objek_tipe . ' #' . $objek_id . ')',
                (string) $objek_tipe, (string) $objek_id, $detail);
        } catch (Throwable $e) {
            log_message('error', 'catat_akses_data_pribadi gagal: ' . $e->getMessage());
            return FALSE;
        }
    }

    /**
     * Kebijakan metode HTTP dan kebersihan URI (form keamanan poin 12.2 dan 12.4,
     * docs/engineering/URI_DAN_METODE_HTTP.md): hanya GET/HEAD/POST, penerowongan metode ditolak,
     * endpoint yang mengubah keadaan hanya POST, OPTIONS dijawab dengan Allow milik rute itu, dan
     * data pribadi/rahasia tidak boleh berada di query string maupun jalur URI. Dijalankan SESUDAH
     * rute teresolusi (controller dan metodenya ada), jadi header Allow tidak pernah bocor untuk
     * alamat asal-asalan. FAIL-CLOSED: galat pada pemeriksa menolak permintaan.
     */
    private function enforce_http_policy()
    {
        if ($this->input->is_cli_request()) { return; }
        $kelas = $this->router->fetch_class(); $metode = $this->router->fetch_method();
        try {
            $this->load->library('Http_policy');
            $hasil = $this->http_policy->check([
                'route'    => strtolower($kelas . '/' . $metode),
                'method'   => $this->input->method(TRUE),
                'server'   => $_SERVER,
                'get'      => $_GET,
                'post'     => $_POST,
                'segments' => array_slice((array) $this->uri->rsegments, 2),
            ]);
        } catch (Throwable $e) {
            log_message('error', 'enforce_http_policy gagal: ' . $e->getMessage());
            $this->output->set_status_header(500)->set_content_type('text/plain', 'utf-8')->set_output('Layanan sementara belum dapat memproses permintaan.');
            $this->output->_display(); exit;
        }
        if ( ! empty($hasil['ok'])) { return; }

        $this->output->set_header('Allow: ' . implode(', ', $hasil['allow']))->set_header('Cache-Control: no-store');
        if ($hasil['code'] === 'options') {
            $this->output->set_status_header(204);
            $this->output->_display(); exit;
        }

        $rute = strtolower($kelas . '/' . $metode);
        log_message('error', 'SECURITY_WARNING http_policy_rejected route=' . $rute . ' code=' . $hasil['code'] . ' method=' . $this->input->method(TRUE));
        if ( ! empty($hasil['structural'])) {
            try {
                $this->load->library('Security_alert');
                $this->security_alert->raise('kebijakan_http', 'rendah',
                    "Permintaan ke '{$rute}' ditolak kebijakan HTTP ({$hasil['code']})",
                    ['route' => $rute, 'kode' => $hasil['code'], 'metode' => substr((string) $this->input->method(TRUE), 0, 12)],
                    'http:' . $hasil['code']);
            } catch (Throwable $e) { /* pengamat tidak boleh menggagalkan penolakan */ }
        }
        $this->output->set_status_header((int) $hasil['status']);
        if ($this->input->is_ajax_request() || anti_automation_route_is_json($kelas, $metode)) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'status' => 'error', 'code' => $hasil['code'], 'message' => $hasil['message'],
            ], JSON_UNESCAPED_UNICODE));
            $this->output->_display(); exit;
        }
        show_error($hasil['message'], (int) $hasil['status'], 'Permintaan Tidak Valid');
        exit;
    }

    /**
     * Validasi skema per-endpoint (form keamanan poin 12.5, docs/engineering/KEAMANAN_API.md):
     * metode, XHR, Content-Type, field wajib/tipe/rentang/enum, field tak dikenal, objek JSON
     * bersarang, segmen URI, dan kolom berkas diperiksa SEBELUM metode controller berjalan.
     * Endpoint yang belum terdaftar di config/api_schemas.php tidak terpengaruh (tetap lewat
     * Input_guard). Pelanggaran STRUKTURAL (field asing, larik di tempat skalar, metode/Content-Type
     * salah) dicatat sebagai peringatan keamanan; salah isi biasa (mis. NIK kurang digit) tidak.
     * FAIL-CLOSED: galat pada validator menolak permintaan endpoint terdaftar.
     */
    private function enforce_api_schema()
    {
        if ($this->input->is_cli_request()) { return; }
        $kelas = $this->router->fetch_class(); $metode = $this->router->fetch_method();
        $this->load->library('Api_schema');
        $skema = $this->api_schema->find($kelas, $metode);
        if ($skema === NULL) { return; }

        try {
            $ctype = strtolower(trim(explode(';', (string) $this->input->server('CONTENT_TYPE'))[0]));
            $json = NULL;
            if ($ctype === 'application/json') {
                $raw = (string) $this->input->raw_input_stream;
                $json = $raw === '' ? NULL : json_decode($raw, TRUE, 12);
            }
            $hasil = $this->api_schema->validate($skema, [
                'method'       => $this->input->method(TRUE),
                'get'          => $_GET,
                'post'         => $_POST,
                'files'        => array_keys((array) $_FILES),
                'segments'     => array_slice((array) $this->uri->rsegments, 2),
                'ajax'         => $this->input->is_ajax_request(),
                'content_type' => $ctype,
                'json'         => $json,
            ]);
        } catch (Throwable $e) {
            log_message('error', 'enforce_api_schema gagal: ' . $e->getMessage());
            $hasil = ['ok' => FALSE, 'status' => 500, 'code' => 'schema_error', 'errors' => [], 'structural' => FALSE];
        }
        if ( ! empty($hasil['ok'])) { return; }

        $rute = strtolower($kelas . '/' . $metode);
        log_message('error', 'SECURITY_WARNING api_schema_rejected route=' . $rute . ' code=' . $hasil['code']
            . ' fields=' . implode(',', array_slice(array_keys((array) $hasil['errors']), 0, 8)));
        if ( ! empty($hasil['structural'])) {
            try {
                $this->load->library('Security_alert');
                $this->security_alert->raise('skema_tidak_valid', 'rendah',
                    "Kiriman ke '{$rute}' ditolak skema ({$hasil['code']}) pada struktur permintaan",
                    ['route' => $rute, 'kode' => $hasil['code'], 'field' => array_slice(array_keys((array) $hasil['errors']), 0, 8)],
                    'schema:' . $rute);
            } catch (Throwable $e) { /* pengamat tidak boleh menggagalkan penolakan */ }
        }

        // Formulir peramban dengan tujuan pengalihan sendiri: pesan ramah, bukan halaman galat.
        if (empty($skema['json']) && ! $this->input->is_ajax_request() && ! empty($skema['invalid']['redirect'])) {
            $this->session->set_flashdata('error', (string) ($skema['invalid']['flash'] ?? 'Isian tidak valid.'));
            redirect($skema['invalid']['redirect']);
            exit;
        }

        $this->output->set_status_header((int) $hasil['status'])->set_header('Cache-Control: no-store');
        if ( ! empty($hasil['allow'])) { $this->output->set_header('Allow: ' . implode(', ', $hasil['allow'])); }
        if ( ! empty($skema['json']) || $this->input->is_ajax_request()) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'status'  => 'error',
                'code'    => $hasil['code'],
                'message' => 'Permintaan tidak sesuai skema endpoint.',
                'errors'  => $hasil['errors'],
            ], JSON_UNESCAPED_UNICODE));
            $this->output->_display(); exit;
        }
        show_error('Permintaan tidak sesuai skema endpoint.', (int) $hasil['status'], 'Permintaan Tidak Valid');
        exit;
    }

    /** Batalkan sesi lama dan paksa penggantian kata sandi yang berusia 90 hari. */
    private function enforce_single_session_and_password_expiry() {
        if ( ! $this->session->userdata('is_logged')) { return; }
        $id = (int) $this->session->userdata('user_id');
        $token = (string) $this->session->userdata('session_auth_token');
        $session_id = (string) $this->session->session_id;
        $row = $this->db->select('sesi_aktif_hash,sesi_aktif_id_hash,sandi_diganti_at,sandi_kedaluwarsa_at')
            ->get_where('usr_akun', ['id' => $id])->row();
        $token_valid = $row && ! empty($token) && ! empty($row->sesi_aktif_hash)
            && hash_equals((string) $row->sesi_aktif_hash, hash('sha256', $token));
        $session_id_valid = $row && ! empty($session_id) && ! empty($row->sesi_aktif_id_hash)
            && hash_equals((string) $row->sesi_aktif_id_hash, hash('sha256', $session_id));
        // CodeIgniter mengganti ID sesi tiap sess_time_to_update (300 detik) dan MEMBAWA isi
        // sesi, termasuk token. Token yang masih cocok membuktikan ini sesi yang sama, jadi
        // hash ID diperbarui alih-alih mengeluarkan pengguna (dulu semua peran terlempar
        // sekitar 5 menit sekali, 27 Sep 2026). Login di perangkat lain tetap ditendang
        // karena tokennya berbeda.
        if ($token_valid && ! $session_id_valid && ! empty($session_id)) {
            $this->db->where('id', $id)->update('usr_akun', ['sesi_aktif_id_hash' => hash('sha256', $session_id)]);
            $session_id_valid = TRUE;
        }
        if ( ! $token_valid || ! $session_id_valid) {
            $this->session->unset_userdata([
                'is_logged', 'user_id', 'role', 'name', 'username', 'email', 'avatar',
                'kabupaten_id', 'bidang_kode', 'session_auth_token', 'password_change_required',
            ]);
            $this->session->sess_regenerate(TRUE);
            // "Perangkat lain" hanya benar kalau token sesi ini digantikan.
            // ID sesi yang tidak cocok juga tidak membuktikan login ganda.
            $message = ($row && ! $token_valid && ! empty($token) && ! empty($row->sesi_aktif_hash))
                ? 'Sesi ini berakhir karena akun digunakan untuk masuk pada perangkat lain.'
                : 'Sesi Anda telah berakhir. Silakan masuk kembali.';
            if ($this->input->is_ajax_request()) {
                $this->output->set_status_header(401); header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'code' => 'sesi_digantikan', 'message' => $message]); exit;
            }
            $this->session->set_flashdata('error', $message);
            redirect('Auth/login'); exit;
        }

        $expired = ! empty($row->sandi_kedaluwarsa_at) && strtotime($row->sandi_kedaluwarsa_at) <= time();
        if ( ! $expired) { return; }
        $this->session->set_userdata('password_change_required', TRUE);
        $controller = strtolower((string) $this->router->fetch_class());
        if (in_array($controller, ['auth', 'pengaturan'], TRUE)) { return; }
        $this->load->model('Auth_model');
        $pesan = $this->Auth_model->pesan_ganti_sandi($row);
        if ($this->input->is_ajax_request()) {
            $this->output->set_status_header(403); header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'code' => 'password_kedaluwarsa', 'message' => $pesan]); exit;
        }
        // Pemberitahuan, bukan galat (5 Okt 2026): tidak ada yang salah, pengguna hanya perlu membuat sandi.
        $this->Auth_model->flash_ganti_sandi($row);
        redirect('akun/profil?password_expired=1'); exit;
    }
    /**
     * Putuskan sesi yang akunnya sudah dinonaktifkan - diperiksa TIAP PERMINTAAN.
     *
     * Tanpa ini, tombol "Nonaktifkan" di Akses Staf hanya menutup pintu MASUK.
     * Orang yang sudah terlanjur login tetap memegang akses penuh sampai
     * sesinya kedaluwarsa (`sess_expiration = 7200`, dan CI menyegarkannya
     * selama ia terus mengklik) - jadi selama tabnya dibiarkan terbuka,
     * pencabutan akses tidak pernah berlaku. Pesan suksesnya sendiri berbunyi
     * "tidak bisa masuk lagi", yang secara harfiah salah untuk kasus itu.
     *
     * Ditemukan lewat tinjauan adversarial 3 Agt 2026, bersama lubang kembarnya
     * di `Auth::google_callback()`.
     *
     * BIAYANYA satu lookup primary key per permintaan, dan HANYA untuk yang
     * sudah login - pengunjung anonim tidak menyentuh DB sama sekali di sini.
     * Itu harga yang wajar untuk saklar yang benar-benar memutus.
     *
     * SENGAJA TIDAK menyegarkan role/scope dari DB sekalipun barisnya sudah
     * dibaca di sini. Itu lubang terpisah yang sudah tercatat di AGENTS.md §18
     * ("Sesi sebagai replika role & scope, tanpa jalur invalidasi"), dan
     * menambalnya sambil lalu akan mengubah perilaku otorisasi seluruh aplikasi
     * di dalam commit yang judulnya soal Akses Staf.
     */
    private function usir_kalau_nonaktif() {
        if ( ! $this->session->userdata('is_logged')) { return; }
        $id = (int) $this->session->userdata('user_id');
        if ($id < 1) { return; }

        $row = $this->db->select('status')->get_where('usr_akun', ['id' => $id])->row();

        // Baris yang HILANG juga mengakhiri sesi: akun yang dihapus tidak boleh
        // terus berjalan hanya karena cookie-nya masih ada.
        $mati = ! $row || strtolower(trim((string) $row->status)) === 'nonaktif';
        if ( ! $mati) { return; }

        /**
         * Kunci autentikasinya DILEPAS, sesinya tidak dihancurkan.
         *
         * Percobaan pertama memakai `sess_destroy()` lalu menulis flashdata -
         * dan pesannya lenyap bersama sesi yang barusan dibunuh, jadi orangnya
         * terlempar ke halaman login tanpa satu pun keterangan kenapa. Diuji dan
         * ketahuan langsung: "diputus" benar, "pesan muncul" tidak.
         *
         * Melepas kuncinya sudah cukup: setiap gerbang membaca `is_logged` +
         * `user_id`, dan id sesinya diregenerasi supaya cookie lama tidak bisa
         * dipakai ulang.
         */
        $this->session->unset_userdata([
            'is_logged', 'user_id', 'role', 'name', 'username', 'email',
            'avatar', 'kabupaten_id', 'bidang_kode',
        ]);
        $this->session->sess_regenerate(TRUE);

        // Permintaan AJAX tidak boleh di-redirect: fetch akan mengikutinya dan
        // menerima HTML halaman login sebagai "berhasil".
        if ($this->input->is_ajax_request()) {
            // Ditulis LANGSUNG, bukan lewat $this->output->set_output().
            // Kita berada di konstruktor dan mengakhiri permintaan dengan exit,
            // sementara set_output() baru dikirim oleh CI saat _display() di
            // akhir siklus normal - yang tidak pernah tercapai. Percobaan
            // pertama memakai output class dan menghasilkan balasan BERBADAN
            // KOSONG: pemanggil fetch menerima 'sukses' tanpa isi.
            $this->output->set_status_header(401);
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'error', 'code' => 'akun_nonaktif',
                'message' => 'Akses akun ini sudah dicabut. Silakan hubungi Super Admin.',
            ]);
            exit;
        }
        $this->session->set_flashdata('error',
            'Akses akun ini sudah dicabut. Silakan hubungi Super Admin bila menurut Anda ini keliru.');
        $this->gerbang_login();
        exit;
    }

    /**
     * Inject essential security headers on every response
     */
    private function set_security_headers() {
        // Satu-satunya sumber daftar header keamanan: helpers/content_security_helper.php. Fungsi yang sama
        // dipakai MY_Exceptions untuk halaman galat/404 dari router, yang tidak pernah melewati controller.
        kirim_header_keamanan();
    }

    /**
     * Check if the current user is logged in via session
     * 
     * @return bool
     */
    protected function is_logged_in() {
        return $this->session->userdata('is_logged') === TRUE;
    }

    /**
     * Get the currently logged in user's ID from session
     *
     * @return int|null
     */
    protected function get_user_id() {
        return $this->session->userdata('user_id');
    }

    /**
     * Catat satu tindakan ke jejak audit pusat (`sys_jejak_audit`).
     *
     * Ditaruh di base controller karena pertanyaan "siapa mengubah ini" tidak
     * mengenal batas modul: role, akun staf, slot magang, dan keputusan layanan
     * semuanya perlu menjawabnya dengan bentuk yang sama.
     *
     * TIGA hal yang sengaja:
     *
     * 1. Pelaku diambil dari SESI, tidak pernah dari parameter. Jejak audit yang
     *    penulisnya bisa ditentukan pemanggil bukan jejak audit.
     * 2. Email & role pelaku disalin apa adanya. FK-nya ON DELETE SET NULL, jadi
     *    tanpa salinan ini jejak kehilangan "siapa" tepat pada kasus yang paling
     *    perlu ditelusuri - akun yang sudah dihapus.
     * 3. GAGAL DIAM-DIAM, tidak pernah melempar. Audit adalah pengamat; kalau
     *    penulisannya menggagalkan tindakan yang sedang diaudit, ia berubah dari
     *    pelindung jadi titik gagal baru. Kegagalannya tetap masuk log error.
     *
     * @param string $aksi      Kata kerja pendek & stabil, untuk menyaring.
     * @param string $ringkasan Kalimat yang dibaca manusia, disimpan SAAT
     *                          KEJADIAN - bukan disusun ulang saat ditampilkan.
     */
    protected function catat_audit($aksi, $ringkasan, $objek_tipe = NULL, $objek_id = NULL, array $detail = []) {
        try {
            if ( ! $this->db->table_exists('sys_jejak_audit')) { return FALSE; }
            $saved = $this->db->insert('sys_jejak_audit', [
                'pelaku_id'    => $this->get_user_id() ?: NULL,
                'pelaku_email' => $this->session->userdata('email') ?: NULL,
                'pelaku_peran'  => $this->session->userdata('role') ?: NULL,
                'aksi'        => substr((string) $aksi, 0, 40),
                'objek_tipe'  => $objek_tipe !== NULL ? substr((string) $objek_tipe, 0, 40) : NULL,
                'objek_id'    => $objek_id !== NULL ? substr((string) $objek_id, 0, 60) : NULL,
                'ringkasan'   => substr((string) $ringkasan, 0, 255),
                'detail_json' => $detail ? json_encode($detail, JSON_UNESCAPED_UNICODE) : NULL,
                'ip'          => $this->input->ip_address(),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
            return (bool) $saved;
        } catch (Throwable $e) {
            log_message('error', 'Gagal menulis jejak audit (' . $aksi . '): ' . $e->getMessage());
            return FALSE;
        }
    }

    /**
     * Get the currently logged in user's role from session
     *
     * @return string|null
     */
    protected function current_role() {
        return $this->session->userdata('role');
    }

    /**
     * Check if the logged in user has a given role (or one of several)
     *
     * @param string|array $role
     * @return bool
     */
    protected function has_role($role) {
        $roles = is_array($role) ? $role : [$role];
        return in_array($this->current_role(), $roles, TRUE);
    }

    /**
     * Render a page view - full layout on a normal request, or just the
     * inner content fragment when called via AJAX (used by the navbar's
     * tab-loader in footer.php, which fetches this fragment and swaps it
     * into #page-content-wrapper instead of doing a full page navigation).
     *
     * @param string $view View path, e.g. 'pages/home/awal'
     * @param array  $data Data passed to the view
     */
    protected function render($view, $data = []) {
        /* Butir 14 putaran 2 - tombol Dashboard di samping nama pengguna.
           Sebelumnya tombol itu HANYA muncul untuk superadmin, sehingga warga,
           pengembang, mahasiswa, kabkota, dan bidang tidak punya satu pun jalan
           ke dashboardnya dari situs publik. Alamatnya dihitung per peran di
           sini supaya tata letak tidak perlu tahu peta menu siapa pun. */
        if ( ! isset($data['dashboard_home']) && $this->session->userdata('is_logged')) {
            $data['dashboard_home'] = $this->dashboard_home();
        }

        /* Permintaan user 15 Agt 2026: sesudah login, kembali ke halaman
           TERAKHIR yang dilihat - bukan cuma ke halaman yang sempat
           menggerbangnya (intended_url lama hanya terisi kalau orangnya
           DITOLAK oleh login-gate).
           ingat_halaman_asal() SUDAH punya seluruh penjagaan open-redirect
           yang dibutuhkan (uri_string() dari server, GET saja,
           auth/* dikecualikan, disaring sanitize_redirect()) - dan dia
           SUDAH no-op untuk yang sudah login. Tinggal dipanggil di titik
           yang LEBIH SERING: setiap halaman publik yang benar-benar
           tampil, bukan cuma yang menggerbang. Ditulis LEBIH DULU dari
           gerbang manapun (halaman yang menggerbang tidak pernah sampai
           render() - dia redirect duluan), jadi "terakhir menang" berlaku
           otomatis tanpa logika tambahan.
           Cabang AJAX (fragment tab-loader di footer.php) IKUT dihitung -
           dari sisi server itu tetap konten sungguhan yang sedang dilihat
           orang, cuma dikirim lewat fetch, bukan navigasi penuh. */
        $this->ingat_halaman_asal();

        if ($this->input->is_ajax_request()) {
            $this->load->view($view, $data);
        } else {
            $data['content'] = $this->load->view($view, $data, true);
            $this->load->view('layouts/main', $data);
        }
    }

    /**
     * Simpan satu berkas unggahan ke DIREKTORI PRIVAT di luar webroot.
     *
     * Satu-satunya pintu unggah yang boleh dipakai fitur baru. Sebelum ini ada
     * tiga jalur berbeda yang semuanya menyimpan di dalam webroot
     * (Auth::_handle_uploads, Umum::simpan_aduan, KemitraanPortal::simpan) -
     * KTP, KTM, dan lampiran aduan bisa diakses lewat HTTP kalau nama filenya
     * bocor. Nama acak bukan kontrol akses. Lihat Pola A di
     * docs/engineering/AUDIT_SISTEM_ROLE_RINGKASAN.md.
     *
     * Validasi berlapis, meniru pola yang sudah terbukti di
     * Pengembang::simpan_dokumen(): whitelist ekstensi + cek MIME ASLI lewat
     * finfo (bukan percaya Content-Type kiriman browser) + batas ukuran +
     * nama file acak. File disimpan di private_uploads/{domain}/{pemilik}/.
     *
     * @param string $field     nama field di $_FILES
     * @param string $domain    subfolder, mis. 'aduan' | 'kemitraan' | 'onboarding'
     * @param mixed  $owner_id  ID pemilik (dipakai sebagai nama subfolder)
     * @param string $error     diisi pesan kegagalan (by-reference)
     * @param int    $max_bytes batas ukuran, default 5 MB
     * @return string|false nama file tersimpan, atau FALSE kalau gagal/tidak ada
     */
    protected function store_private_upload($field, $domain, $owner_id, &$error = NULL, $max_bytes = 5242880) {
        if (empty($_FILES[$field]['name'])) { return FALSE; }

        $file = $_FILES[$field];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = 'Berkas gagal diunggah.';
            return FALSE;
        }
        if ($file['size'] > $max_bytes) {
            $error = 'Ukuran berkas melebihi ' . round($max_bytes / 1048576, 1) . ' MB.';
            return FALSE;
        }

        $allowed = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if ( ! isset($allowed[$ext]) || $mime !== $allowed[$ext]) {
            $error = 'Jenis berkas tidak didukung. Gunakan PDF, JPG, atau PNG.';
            return FALSE;
        }

        // 11.4: isi berkas dipindai SEBELUM menyentuh penyimpanan.
        if ( ! $this->scan_uploaded_file($file['tmp_name'], $ext, $error, $domain)) { return FALSE; }

        $this->ensure_private_uploads_protected();
        $dir = $this->private_upload_dir($domain, $owner_id);
        if ( ! is_dir($dir) && ! mkdir($dir, 0700, TRUE)) {
            $error = 'Gagal menyiapkan penyimpanan berkas.';
            return FALSE;
        }

        $nama_simpan = bin2hex(random_bytes(16)) . '.' . $ext;
        // 11.1: kuota jumlah berkas dan total ukuran per pengguna, dipesan sebelum berkas dipindah.
        if ( ! $this->reserve_upload_quota($domain, $owner_id, $nama_simpan, (int) $file['size'], $error)) { return FALSE; }
        if ( ! move_uploaded_file($file['tmp_name'], $dir . $nama_simpan)) {
            $this->release_upload_quota($domain, $owner_id, $nama_simpan);
            $error = 'Berkas gagal disimpan.';
            return FALSE;
        }
        return $nama_simpan;
    }

    /**
     * Pindai isi satu berkas unggahan (form keamanan poin 11.4). Dipakai SEMUA titik unggah,
     * termasuk yang tidak menyimpan berkasnya (impor Excel) atau menyimpannya di webroot
     * (gambar katalog dan beranda). Berkas yang ditolak dicatat sebagai peringatan keamanan
     * (jejak audit + banner admin) beserta SHA-256-nya, tanpa isi berkas.
     * Gagal-tertutup: galat pada pemindai = berkas ditolak.
     */
    protected function scan_uploaded_file($tmp_name, $ext, &$error = NULL, $domain = 'unggahan') {
        try {
            $this->load->library('Upload_scanner');
            $hasil = $this->upload_scanner->scan($tmp_name, $ext);
        } catch (Throwable $e) {
            log_message('error', 'scan_uploaded_file: pemindai gagal dimuat: ' . $e->getMessage());
            $error = Upload_scanner::PESAN['pemindai_tak_tersedia'];
            return FALSE;
        }
        if ( ! empty($hasil['ok'])) { return TRUE; }

        $error = $hasil['message'];
        try {
            $this->load->library('Security_alert');
            $this->security_alert->raise(
                'berkas_berbahaya', in_array($hasil['code'], ['antivirus', 'kode_php', 'kode_dinamis', 'eicar'], TRUE) ? 'tinggi' : 'sedang',
                "Unggahan ditolak pemindai berkas ({$hasil['code']}) pada domain '{$domain}'",
                ['domain' => (string) $domain, 'kode' => $hasil['code'], 'ekstensi' => (string) $ext,
                 'sha256' => $hasil['sha256'] ?? NULL, 'ukuran' => $hasil['size'] ?? NULL, 'rincian' => substr((string) ($hasil['detail'] ?? ''), 0, 160)],
                'upl:' . $hasil['code']
            );
        } catch (Throwable $e) {
            log_message('error', 'scan_uploaded_file: peringatan gagal dikirim: ' . $e->getMessage());
        }
        return FALSE;
    }

    /** Pengguna unggahan saat ini: akun bila login, IP bila tamu (aduan publik). */
    private function upload_identity() {
        $this->load->library('Upload_quota');
        $login = (bool) $this->session->userdata('is_logged');
        return $this->upload_quota->identify(
            $login ? (int) $this->session->userdata('user_id') : 0,
            $this->session->userdata('role'),
            $this->input->ip_address()
        );
    }

    /**
     * Pesan jatah unggahan (form keamanan poin 11.1); FALSE + $error bila kuota pengguna terlampaui.
     * Gagal-tertutup bila buku kuota tidak dapat ditulis.
     */
    protected function reserve_upload_quota($domain, $owner_id, $nama_simpan, $size, &$error = NULL) {
        try {
            $id = $this->upload_identity();
            return $this->upload_quota->reserve($id['actor'], $id['limits'], $domain, $owner_id, $nama_simpan, $size, $error);
        } catch (Throwable $e) {
            log_message('error', 'reserve_upload_quota: ' . $e->getMessage());
            $error = 'Gagal memeriksa kuota unggahan. Coba lagi.';
            return FALSE;
        }
    }

    protected function release_upload_quota($domain, $owner_id, $nama_simpan) {
        try {
            $id = $this->upload_identity();
            $this->upload_quota->release($id['actor'], $domain, $owner_id, $nama_simpan);
        } catch (Throwable $e) {
            log_message('error', 'release_upload_quota: ' . $e->getMessage());
        }
    }

    /**
     * Pastikan akar private_uploads/ punya .htaccess penolak akses.
     *
     * KENAPA PERLU, padahal namanya sudah "private": nama direktori tidak
     * menjamin apa pun. Di layout XAMPP lokal, dirname(FCPATH) ternyata SAMA
     * DENGAN DocumentRoot Apache (C:/xampp/htdocs), sehingga private_uploads/
     * benar-benar tersaji lewat HTTP - diverifikasi langsung: dokumen SRP2
     * bisa diunduh tanpa login sama sekali. Asumsi "di luar webroot" yang
     * tertulis di AGENTS.md §9 tidak berlaku universal, tergantung di mana
     * aplikasi dipasang relatif terhadap DocumentRoot.
     *
     * Ditulis oleh KODE (bukan disiapkan manual) karena private_uploads/ ada
     * di luar repo git - file yang ditaruh manual tidak akan ikut ter-deploy.
     *
     * BATAS: .htaccess hanya dipatuhi Apache/LiteSpeed. Kalau suatu saat
     * pindah ke nginx, proteksi ini TIDAK berlaku dan wajib diganti aturan
     * server (atau pindahkan direktorinya benar-benar keluar dari DocumentRoot).
     */
    protected function ensure_private_uploads_protected() {
        $this->load->helper('private_upload');
        $akar = private_uploads_root();
        if ( ! is_dir($akar) && ! @mkdir($akar, 0700, TRUE)) { return; }

        $htaccess = $akar . '.htaccess';
        if (is_file($htaccess)) { return; }

        @file_put_contents($htaccess, implode("\n", [
            '# Dibuat otomatis oleh MY_Controller::ensure_private_uploads_protected().',
            '# Berkas di sini (KTP, KTM, lampiran aduan, dokumen SRP2) HANYA boleh',
            '# disajikan lewat endpoint ber-guard, tidak pernah diakses langsung.',
            '# JANGAN dihapus. Catatan: hanya berlaku di Apache/LiteSpeed.',
            '<IfModule mod_authz_core.c>',
            '    Require all denied',
            '</IfModule>',
            '<IfModule !mod_authz_core.c>',
            '    Order allow,deny',
            '    Deny from all',
            '</IfModule>',
        ]) . "\n");
    }

    /**
     * Path direktori privat satu pemilik. Akar & sanitasinya ditangani helper
     * private_upload supaya controller dan model memakai sumber yang sama.
     */
    protected function private_upload_dir($domain, $owner_id) {
        $this->load->helper('private_upload');
        return private_uploads_dir($domain, $owner_id);
    }

    /**
     * Sajikan berkas privat ke pemanggil. Controller pemanggil WAJIB sudah
     * memastikan yang meminta memang berhak (guard role + scope) SEBELUM
     * memanggil ini - method ini tidak tahu apa-apa soal otorisasi.
     *
     * basename() dipakai pada nama file supaya nilai dari DB yang (entah
     * bagaimana) memuat path tidak bisa membaca file di luar direktorinya.
     */
    protected function serve_private_file($domain, $owner_id, $nama_simpan, $mime = 'application/octet-stream') {
        $path = $this->private_upload_dir($domain, $owner_id) . basename((string) $nama_simpan);
        if (empty($nama_simpan) || ! is_file($path)) {
            // 404 ke klien tetap opaque (anti-IDOR), tapi penyebabnya WAJIB
            // tercatat - "mengapa 404" tidak boleh butuh bedah DB manual.
            log_message('error', sprintf(
                'serve_private_file 404: domain=%s owner=%s stored=%s (%s)',
                $domain, $owner_id, (string) $nama_simpan,
                empty($nama_simpan) ? 'nama_simpan kosong' : 'berkas tidak ada di disk'
            ));
            show_404(); return;
        }

        // Poin 7.3: setiap pembukaan berkas privat oleh STAF tercatat (jenis berkas, pemilik, pelaku, waktu).
        $this->catat_akses_data_pribadi('berkas_privat', (string) $domain, (string) $owner_id);

        // header() langsung, BUKAN $this->output->set_content_type():
        // readfile() menulis body duluan sehingga antrean header CI terlambat -
        // PHP terlanjur mengirim text/html default, dan nosniff (dipasang di
        // constructor) melarang browser menebak, jadi gambar tampil sebagai teks.
        // Poin 13.5: jenis konten ditentukan dari EKSTENSI yang kita tulis sendiri (nama acak + ekstensi daftar-izin
        // saat unggah), bukan dari $mime kiriman pemanggil/DB; yang tidak dikenal dipaksa berupa unduhan biner.
        $aman = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $inline = isset($aman[$ext]);
        header('Content-Type: ' . ($inline ? $aman[$ext] : 'application/octet-stream'));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="berkas' . ($inline ? '.' . $ext : '.bin') . '"');
        header('Cache-Control: private, no-store, max-age=0');
        header('Content-Length: ' . (int) filesize($path));
        if ($inline && $ext !== 'pdf') { header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; sandbox"); }
        readfile($path);
    }

    /**
     * Data tabel antrean perumahan (cari + filter status + urut + paginasi,
     * semuanya server-side). Dipakai DUA controller yang merender view
     * `admin/antrean/dashboard.php` yang sama:
     *   - Admin::index()          → $kabupaten_id NULL (superadmin, lintas wilayah)
     *   - Admin_Kabkota::index()  → $kabupaten_id dari sesi (ter-scope)
     *
     * Scope diterima sebagai ARGUMEN EKSPLISIT, bukan lewat state query builder
     * yang sudah diterapkan pemanggil - supaya tidak ada kemungkinan scope
     * terlewat karena urutan pemanggilan.
     *
     * Sebelumnya halaman ini mengirim s.d. 1000 baris sebagai JSON ke browser
     * lalu memfilter di klien; itu paradigma tabel yang dihapus di B8.
     *
     * @param int|null $kabupaten_id NULL = tanpa scope wilayah (superadmin)
     * @return array [queue, table, pager, filter_status, filter_tanpa_wilayah, can_filter_tanpa_wilayah]
     */
    protected function antrean_table_data($kabupaten_id = NULL) {
        // Kotak cari antrean dikirim POST (bisa berisi NIK); dijawab redirect, fungsi ini tidak kembali.
        if ($this->input->method() === 'post') { $this->cari_antrean_prg(); }

        // Nama pemohon terenkripsi sejak migrasi 067: tidak bisa diurutkan maupun dicari di SQL.
        $kolom_sort = [
            'sf_antrean_pengajuan.created_at', 'sf_program.nama_program', 'sf_antrean_pengajuan.status_antrean',
        ];
        $table = $this->table_state($kolom_sort, 'sf_antrean_pengajuan.created_at');
        $table['cari_post'] = TRUE;
        // NIK tidak dilayani lewat URL (?q=<16 digit>, tautan lama): dialihkan ke alamat tanpa q supaya tidak
        // tersalin ke tautan filter/urutan/halaman. Pencarian NIK hanya lewat token sesi dari cari_antrean_prg().
        if (preg_match('/^\d{16}$/', preg_replace('/\s+/', '', $table['q']))) {
            $sisa = $this->input->get();
            unset($sisa['q']);
            redirect(uri_string() . ($sisa ? '?' . http_build_query($sisa) : ''), 'location', 303);
        }
        $cari_nik = NULL;
        $token = (string) $this->input->get('cari', TRUE);
        $tersimpan = (array) $this->session->userdata('cari_antrean');
        if ($token !== '' && isset($tersimpan[$token]['sidik'])) {
            $cari_nik = $tersimpan[$token];
            $table['q'] = '';
            $table['cari_label'] = $cari_nik['label'];
        }

        $status = $this->input->get('status', TRUE);
        $status = in_array($status, ['pending', 'needs_revision', 'approved', 'rejected'], TRUE) ? $status : NULL;
        $tanpa_wilayah = $kabupaten_id === NULL && $this->input->get('tanpa_wilayah', TRUE) === '1';

        $this->db->from('sf_antrean_pengajuan')
            ->join('sf_program', 'sf_antrean_pengajuan.program_id = sf_program.id', 'left');
        if ($kabupaten_id !== NULL) { $this->db->where('sf_antrean_pengajuan.kabupaten_id', $kabupaten_id); }
        if ($status) { $this->db->where('sf_antrean_pengajuan.status_antrean', $status); }
        if ($tanpa_wilayah) { $this->db->where('sf_antrean_pengajuan.kabupaten_id IS NULL', NULL, FALSE); }
        if ($cari_nik !== NULL) {
            /* NIK dicari hanya utuh 16 digit, lewat sidiknya (migrasi 067): tiket lama menyimpannya
               di antrean, tiket wizard di sf_profil_warga. Klausa ini di dalam group yang di-AND
               dengan scope wilayah, jadi NIK wilayah lain tetap tidak muncul. Pencarian nama
               DICABUT: nama terenkripsi, dan menyaringnya di PHP merusak hitungan halaman. */
            $sidik = (string) $cari_nik['sidik'];
            $this->db->group_start()
                ->where('sf_antrean_pengajuan.nik_pengaju_lookup_hash', $sidik)
                ->or_where('sf_antrean_pengajuan.penilaian_id IN (SELECT a.id FROM sf_penilaian_perumahan a'
                    . ' JOIN sf_profil_warga p ON p.id = a.profil_warga_id'
                    . ' WHERE p.nik_lookup_hash = ' . $this->db->escape($sidik) . ')', NULL, FALSE)
                ->group_end();
        } elseif ($table['q'] !== '') {
            $this->db->group_start()
                ->like('sf_antrean_pengajuan.kode_tiket', $table['q'])
                ->or_like('sf_program.nama_program', $table['q'])
                ->group_end();
        }

        // FALSE = pertahankan state query builder untuk query ambil di bawah.
        $table += $this->paginate_state($this->db->count_all_results('', FALSE));

        $queue = $this->db->select('sf_antrean_pengajuan.*, sf_program.nama_program')
            ->order_by($table['sort'], $table['dir'])
            ->limit($table['per_page'], $table['offset'])
            ->get()->result();
        // Dibuka hanya untuk satu halaman yang sudah ter-scope; penyamaran B2 tetap di view.
        foreach ($queue as $row) { $this->buka_pii_antrean($row); }

        return [
            'queue' => $queue, 'table' => $table, 'pager' => $table,
            'filter_status' => $status, 'filter_tanpa_wilayah' => $tanpa_wilayah,
            'can_filter_tanpa_wilayah' => $kabupaten_id === NULL,
        ];
    }

    /**
     * POST kotak cari antrean lalu redirect (PRG). NIK 16 digit tidak pernah masuk URL, tautan halaman,
     * log akses, atau riwayat peramban: yang disimpan di sesi hanya sidiknya plus label bertopeng, dan URL
     * membawa token acak `cari`. Teks lain (tiket, program) bukan data pribadi dan tetap lewat ?q=.
     * ponytail: lima token terakhir per sesi; cukup untuk tab yang terbuka bersamaan.
     */
    private function cari_antrean_prg() {
        $q = preg_replace('/\s+/', '', (string) $this->input->post('q', TRUE));
        $params = [];
        foreach (['status', 'tanpa_wilayah', 'sort', 'dir'] as $k) {
            $v = $this->input->post($k, TRUE);
            if (is_string($v) && $v !== '') { $params[$k] = $v; }
        }
        if (preg_match('/^\d{16}$/', $q)) {
            $this->load->library('encryption_lib');
            $tersimpan = array_slice((array) $this->session->userdata('cari_antrean'), -4, NULL, TRUE);
            $token = bin2hex(random_bytes(12));
            $tersimpan[$token] = ['sidik' => $this->encryption_lib->deterministic_hash($q), 'label' => 'NIK berakhiran ' . substr($q, -4)];
            $this->session->set_userdata('cari_antrean', $tersimpan);
            $params['cari'] = $token;
        } elseif (trim((string) $this->input->post('q', TRUE)) !== '') {
            $params['q'] = trim((string) $this->input->post('q', TRUE));
        }
        redirect(uri_string() . ($params ? '?' . http_build_query($params) : ''), 'location', 303);
    }

    /**
     * Buka salinan terenkripsi tiket lama (migrasi 067) ke nama properti lamanya, supaya
     * view tidak perlu tahu kolomnya terenkripsi. Ciphertext dan sidik dibuang dari baris.
     * Gagal buka jadi NULL: view menampilkan "belum tersedia", bukan teks sandi.
     */
    protected function buka_pii_antrean($row) {
        $this->load->library('encryption_lib');
        foreach (['nik_pengaju', 'nama_lengkap', 'data_simperum_json', 'data_survey_json'] as $kolom) {
            $kolom_c = $kolom . '_ciphertext';
            $c = $row->$kolom_c ?? NULL;
            $p = ($c !== NULL && $c !== '' && $this->encryption_lib->is_encrypted($c)) ? $this->encryption_lib->decrypt($c) : NULL;
            $row->$kolom = $p === FALSE ? NULL : $p;
            unset($row->$kolom_c);
        }
        unset($row->nik_pengaju_lookup_hash);
        return $row;
    }

    protected function assessment_detail_data($antrean_id, $kabupaten_id = NULL) {
        $this->load->model('Housing_assessment_model');
        $this->load->library('encryption_lib');
        $this->load->library('Matriks_program_ruleset');
        $detail = $this->Housing_assessment_model->get_scoped_queue_detail($antrean_id, $kabupaten_id);
        if ( ! $detail) { return NULL; }
        // Poin 7.3: profil warga terdekripsi (identitas, alamat, koordinat) ditampilkan ke staf: dicatat.
        $this->catat_akses_data_pribadi('penilaian_warga', 'sf_antrean_pengajuan', (string) (int) $antrean_id);

        $assessment = $detail['assessment'];
        $source_row = $this->db->select('muatan_ciphertext')
            ->get_where('sf_rekaman_simperum', ['id' => (int) ($assessment['rekaman_simperum_id'] ?? 0)])
            ->row_array();
        $source = $source_row
            ? kunci_tersimpan_ke_baru(json_decode($this->encryption_lib->decrypt($source_row['muatan_ciphertext']), TRUE)) : [];
        $selected_id = (int) ($detail['queue']['rekomendasi_id'] ?? 0);
        $recommendations = $this->Housing_assessment_model->get_owned_recommendations(
            (int) ($assessment['id'] ?? 0),
            (int) ($detail['queue']['user_id'] ?? 0)
        );
        foreach ($recommendations as &$recommendation) {
            $recommendation['is_selected'] = (int) $recommendation['rekomendasi_id'] === $selected_id;
        }
        unset($recommendation);
        $provenance_value = $assessment['asal_isian_json']
            ?? $detail['profile_snapshot']['asal_isian_json'] ?? [];
        $provenance = is_array($provenance_value)
            ? $provenance_value : (json_decode((string) $provenance_value, TRUE) ?: []);
        $provenance = kunci_tersimpan_ke_baru($provenance);

        // Admin membaca snapshot yang sama dengan warga, bukan menghitung ulang.
        $preliminary_matrix = json_decode($assessment['preliminary_matrix'] ?? 'null', TRUE);

        return [
            'queue' => $detail['queue'], 'assessment' => $assessment,
            'profile' => $detail['profile_snapshot'] ?? [],
            'source_snapshot' => is_array($source) ? $source : [],
            'provenance' => $provenance,
            'recommendations' => $recommendations,
            'evidence' => $this->Housing_assessment_model->get_scoped_queue_files($antrean_id, $kabupaten_id),
            'preliminary_matrix' => $preliminary_matrix,
            // NIK pemohon terbukti miliknya (nama akun + tanggal lahir cocok dengan SIMPERUM, 3 Okt 2026).
            'nik_terverifikasi' => $this->db->where('user_id', (int) ($detail['queue']['user_id'] ?? 0))
                ->where('confirmed_at IS NOT NULL', NULL, FALSE)->count_all_results('sf_profil_warga') > 0,
        ];
    }

    protected function scoped_queue_file($antrean_id, $jenis_berkas, $kabupaten_id = NULL) {
        $this->load->model('Housing_assessment_model');
        foreach ($this->Housing_assessment_model->get_scoped_queue_files($antrean_id, $kabupaten_id) as $file) {
            if (hash_equals((string) $file['jenis_berkas'], (string) $jenis_berkas)) { return $file; }
        }
        return NULL;
    }

    /**
     * State pencarian + pengurutan untuk tabel admin (server-side).
     * Dipakai berpasangan dengan paginate_state(): panggil ini dulu, terapkan
     * filternya ke query builder, hitung total, baru paginate_state().
     *
     * KEAMANAN: kolom sort di-whitelist ketat lewat $sortable_columns.
     * Nilai dari ?sort= TIDAK PERNAH boleh masuk ORDER BY apa adanya -
     * CI query builder tidak meng-escape nama kolom seperti dia meng-escape
     * nilai, jadi itu jalur SQL injection. Kata kunci pencarian aman karena
     * masuk lewat like() yang di-escape sebagai nilai.
     *
     * @param array  $sortable_columns whitelist nama kolom yang boleh diurut
     * @param string $default_sort     kolom default (harus ada di whitelist)
     * @return array [q, sort, dir, sortable]
     */
    protected function table_state($sortable_columns = [], $default_sort = NULL) {
        $sort = $this->input->get('sort', TRUE);
        $dir  = strtolower((string) $this->input->get('dir', TRUE));

        return [
            'q'        => trim((string) $this->input->get('q', TRUE)),
            'sort'     => in_array($sort, $sortable_columns, TRUE) ? $sort : $default_sort,
            'dir'      => $dir === 'asc' ? 'ASC' : 'DESC',
            'sortable' => $sortable_columns,
        ];
    }

    /**
     * Hitung state paginasi server-side dari jumlah baris total.
     * Dipakai halaman admin yang dulu merender SELURUH tabel tanpa LIMIT
     * (Admin_Bidang/Admin_Kemitraan/Admin_Users) - aman saat data masih
     * sedikit, tapi berat begitu menumpuk. Lihat ANCHOR_DASHBOARD_TERPADU.md B7.
     *
     * Nomor halaman dibaca dari ?page= dan selalu di-clamp ke rentang valid,
     * jadi nilai ngawur/negatif tidak bisa dipakai untuk offset aneh.
     *
     * @return array [page, per_page, offset, total_rows, total_pages]
     */
    protected function paginate_state($total_rows, $per_page = 25) {
        $total_rows  = (int) $total_rows;
        $per_page    = max(1, (int) $per_page);
        $total_pages = max(1, (int) ceil($total_rows / $per_page));
        $page        = max(1, (int) $this->input->get('page'));
        if ($page > $total_pages) { $page = $total_pages; }

        return [
            'page' => $page, 'per_page' => $per_page, 'offset' => ($page - 1) * $per_page,
            'total_rows' => $total_rows, 'total_pages' => $total_pages,
        ];
    }

    /**
     * URL "dashboard saya" untuk sebuah role, diturunkan dari registry
     * (modul pertama yang boleh dilihat role itu, urut group lalu order).
     *
     * Dipakai tombol/tautan yang mengarahkan user "kembali ke dashboardnya"
     * dari halaman publik. Dulu tautan semacam itu hardcode ke `akun`, padahal
     * `akun` bukan dashboard semua role - superadmin misalnya sengaja TIDAK
     * punya menu "Status Pengajuan" (dia pengelola, bukan pemohon), jadi
     * dikirim ke sana berarti mendarat di halaman yang tidak ada di menunya.
     *
     * Sengaja tidak memakai dashboard_menu() supaya tidak ikut menjalankan
     * query badge yang tidak dibutuhkan di sini.
     *
     * @param string|null $role NULL = role sesi saat ini
     * @return string path CI, fallback 'akun'
     */
    protected function module_privilege_allowed($kunci_modul) {
        $role = $this->current_role();
        if ($role === 'admin' || ! in_array($role, ['admin_kabkota','admin_bidang'], TRUE)) { return TRUE; }
        if ( ! $this->db->table_exists('usr_hak_modul_admin')) { return TRUE; }
        $user_id = (int) $this->get_user_id();
        if ($this->db->where('user_id',$user_id)->count_all_results('usr_hak_modul_admin') === 0) { return TRUE; }
        $row = $this->db->get_where('usr_hak_modul_admin',['user_id'=>$user_id,'kunci_modul'=>$kunci_modul])->row();
        return $row && (int)$row->diizinkan === 1;
    }

    protected function enforce_current_module_privilege() {
        $role = $this->current_role();
        $this->config->load('dashboard_modules', FALSE, TRUE);
        $uri = trim($this->uri->uri_string(), '/');
        $best = NULL; $length = -1;
        foreach (($this->config->item('dashboard_modules') ?: []) as $key => $module) {
            if (empty($module['roles']) || !in_array($role,$module['roles'],TRUE)) { continue; }
            // Awalan terpanjang menang; 'aksi' = path tambahan milik modul di luar url-nya.
            foreach (array_merge([$module['url'] ?? ''], (array) ($module['aksi'] ?? [])) as $url) {
                $url = trim($url, '/');
                if ($url !== '' && (strcasecmp($uri,$url)===0 || stripos($uri,$url.'/')===0) && strlen($url)>$length) {
                    $best=$key; $length=strlen($url);
                }
            }
        }
        if ($best !== NULL && ! $this->module_privilege_allowed($best)) {
            show_error('Akses modul ini tidak diberikan oleh Super Admin.', 403, 'Akses Ditolak');
            exit;
        }
    }
    protected function dashboard_home($role = NULL) {
        $role = $role ?: $this->current_role();
        if (empty($role)) { return 'akun'; }

        $this->config->load('dashboard_modules', FALSE, TRUE);
        $group_order = $this->config->item('dashboard_module_groups') ?: [];

        $kandidat = [];
        foreach ($this->modul_untuk_peran($role) as $m) {
            $g = array_search($m['group'] ?? '', $group_order);
            $kandidat[] = ['g' => $g === FALSE ? 999 : $g, 'o' => $m['order'] ?? 999, 'url' => $m['url']];
        }
        if (empty($kandidat)) { return 'akun'; }

        usort($kandidat, fn($a, $b) => [$a['g'], $a['o']] <=> [$b['g'], $b['o']]);
        return $kandidat[0]['url'];
    }

    /** Peran yang urusannya di situs publik, bukan di dashboard. */
    private const PERAN_PUBLIK = ['warga', 'pengembang', 'mahasiswa'];

    /**
     * Ke mana orang mendarat sesudah login, saat TIDAK ada halaman asal.
     *
     * BUTIR 24 PUTARAN 2 - "alur dirapikan lagi, usernya masih bingung".
     * Keputusan user 10 Agt 2026: pilihan (a) - warga tidak dibawa ke dashboard.
     *
     * Alasannya terukur, bukan selera. Dashboard warga, pengembang, dan
     * mahasiswa hanya berisi DUA menu ("Status Pengajuan" dan "Profil Saya"),
     * sementara semua yang mereka cari - Cari Rumah, Cek Data Rumah, diagnosa,
     * pendataan - ada di situs publik, DI LUAR dashboard. Mendaratkan mereka di
     * sana berarti memindahkan orang ke tempat lain di tengah jalan, lalu
     * membiarkannya tanpa jalan pulang selain keluar akun.
     *
     * Yang punya halaman asal TIDAK lewat sini - `Auth` mengembalikannya ke
     * tujuan semula lebih dulu (butir A5). Ini hanya jaring untuk yang menekan
     * "Masuk" langsung dari beranda.
     *
     * Peran staf tetap ke dashboard: bagi mereka dashboard MEMANG tempat kerja,
     * dan admin punya 14 menu di sana.
     */
    protected function tujuan_setelah_login($role = NULL) {
        $role = $role ?: $this->current_role();
        return in_array($role, self::PERAN_PUBLIK, TRUE) ? '' : $this->dashboard_home($role);
    }

    /**
     * Modul registry yang berlaku untuk peran ini: hak modul, enabled, roles, scope sesi, scope_values.
     * Satu saringan untuk sidebar (dashboard_menu), beranda dashboard (dashboard_home), dan Pusat
     * Pemberitahuan, supaya ketiganya tidak pernah berbeda pendapat soal modul mana yang terlihat.
     *
     * @return array [kunci => entri registry], urutan sesuai berkas registry
     */
    protected function modul_untuk_peran($role = NULL) {
        $role = $role ?: $this->current_role();
        $this->config->load('dashboard_modules', FALSE, TRUE);
        $hasil = [];
        foreach (($this->config->item('dashboard_modules') ?: []) as $key => $m) {
            if ( ! $this->module_privilege_allowed($key)) { continue; }
            if (array_key_exists('enabled', $m) && $m['enabled'] === FALSE) { continue; }
            if (empty($m['roles']) || ! in_array($role, $m['roles'], TRUE)) { continue; }
            $scope_value = ! empty($m['scope']) ? $this->session->userdata($m['scope']) : NULL;
            if ( ! empty($m['scope']) && empty($scope_value)) { continue; }
            if ( ! empty($m['scope_values']) && ! in_array($scope_value, $m['scope_values'], TRUE)) { continue; }
            $hasil[$key] = $m;
        }
        return $hasil;
    }

    /**
     * Query builder berisi FROM + WHERE "belum diproses" satu entri registry ('table' + 'pending_where',
     * plus 'scope_column' = nilai scope sesi). SATU-SATUNYA definisi antrean sebuah badge: angka badge
     * (count_pending_modul) dan baris Pusat Pemberitahuan (pending_modul_baris) sama-sama mulai dari sini,
     * jadi keduanya tidak bisa melenceng.
     *
     * @return CI_DB_query_builder|NULL NULL kalau entri tidak berantrean atau scope sesinya kosong
     */
    private function query_pending_modul($modul) {
        if (empty($modul['table']) || empty($modul['pending_where'])) { return NULL; }
        $scope_value = NULL;
        if ( ! empty($modul['scope_column'])) {
            $scope = $modul['scope'] ?? NULL;
            $scope_value = $scope ? $this->session->userdata($scope) : NULL;
            if ($scope === NULL || $scope_value === NULL || $scope_value === '') { return NULL; }
        }
        $this->db->from($modul['table'])->where($modul['pending_where']);
        if ($scope_value !== NULL) { $this->db->where($modul['scope_column'], $scope_value); }
        return $this->db;
    }

    /**
     * Hitung baris "belum diproses" untuk satu entri registry. Satu mekanisme untuk badge sidebar,
     * ringkasan "Perlu tindakan" di topbar, dan Pusat Pemberitahuan.
     *
     * @param array $modul entri dari config dashboard_modules
     * @return int 0 kalau entri tidak mendeklarasikan tabel/pending_where
     */
    protected function count_pending_modul($modul) {
        $query = $this->query_pending_modul($modul);
        return $query ? (int) $query->count_all_results() : 0;
    }

    /**
     * Baris yang membentuk angka badge: paling banyak $batas terbaru, plus totalnya, dalam SATU query
     * (COUNT(*) OVER () dihitung sebelum LIMIT). Kolom yang diambil hanya id, created_at, dan kolom
     * non-pribadi yang dideklarasikan di 'tindakan' => 'penanda'/'keterangan' registry.
     *
     * @return array ['total' => int, 'baris' => array of assoc]
     */
    protected function pending_modul_baris($modul, $batas = 50) {
        $query = $this->query_pending_modul($modul);
        if ( ! $query) { return ['total' => 0, 'baris' => []]; }
        $kolom = array_unique(array_filter(['id', 'created_at',
            $modul['tindakan']['penanda'] ?? NULL, $modul['tindakan']['keterangan'] ?? NULL]));
        // Nama kolom dari registry (konfigurasi), bukan dari masukan; FALSE supaya OVER () tidak di-escape.
        $baris = $query->select(implode(', ', $kolom) . ', COUNT(*) OVER () AS total_pending', FALSE)
            ->order_by('created_at', 'DESC')->order_by('id', 'DESC')
            ->limit((int) $batas)->get()->result_array();
        return ['total' => $baris ? (int) $baris[0]['total_pending'] : 0, 'baris' => $baris];
    }

    /**
     * Bangun menu dashboard dari registry application/config/dashboard_modules.php,
     * difilter berdasarkan role & scope sesi saat ini, dikelompokkan sesuai
     * dashboard_module_groups. INI HANYA MENGATUR TAMPILAN MENU - bukan otorisasi;
     * penegakan akses tetap di constructor controller tujuan tiap modul (lihat
     * peringatan di kepala file registry & docs/architecture/ANCHOR_DASHBOARD_TERPADU.md).
     *
     * @return array [group_label => [item, item, ...]]
     */
    protected function dashboard_menu() {
        $this->config->load('dashboard_modules', FALSE, TRUE);
        $group_order = $this->config->item('dashboard_module_groups') ?: [];

        $items = [];
        foreach ($this->modul_untuk_peran() as $key => $m) {
            if (array_key_exists('sidebar', $m) && $m['sidebar'] === FALSE) { continue; } // disembunyikan dari sidebar, akses tetap

            $badge = NULL;
            if ( ! empty($m['badge'])) {
                $badge = $this->count_pending_modul($m) ?: NULL;
            }

            $items[] = [
                'key' => $key, 'label' => $m['label'], 'icon' => $m['icon'], 'url' => $m['url'],
                // Menu ber-badge membuka modulnya dengan filter yang sama dengan badge (overview_url),
                // supaya baris yang tampil = angka yang diklik. `url` tetap dipakai sorotan aktif.
                'href' => $badge && ! empty($m['overview_url']) ? $m['overview_url'] : $m['url'],
                'badge_judul' => $badge ? $badge . ' ' . ($m['tindakan']['satuan'] ?? 'menunggu tindakan') : NULL,
                'group' => $m['group'] ?? '', 'order' => $m['order'] ?? 999, 'badge' => $badge,
                'parent' => $m['parent'] ?? NULL,
                'tab_baru' => ! empty($m['tab_baru']),
            ];
        }

        usort($items, function ($a, $b) use ($group_order) {
            $ga = array_search($a['group'], $group_order); $ga = $ga === FALSE ? 999 : $ga;
            $gb = array_search($b['group'], $group_order); $gb = $gb === FALSE ? 999 : $gb;
            return [$ga, $a['order']] <=> [$gb, $b['order']];
        });

        // Active-state ditentukan di sini, bukan di view: hanya URL dengan
        // kecocokan TERPANJANG yang aktif. Kalau tiap item menilai dirinya
        // sendiri, 'akun' ikut menyala saat membuka 'akun/profil'.
        //
        // Perbandingannya TIDAK peka huruf besar-kecil: `uri_string()` memberi
        // segmen apa adanya seperti yang diketik, sedangkan registry menulis
        // nama controller berkapital (`Rekam_Perumahan`). Di Linux URL memang
        // peka huruf, tetapi itu urusan router - bukan alasan sidebar berhenti
        // menyorot saat orang tiba lewat tautan yang kapitalisasinya berbeda.
        $uri = strtolower($this->uri->uri_string());
        $best = -1; $best_i = NULL;
        foreach ($items as $i => $item) {
            $url = strtolower($item['url']);
            $match = ($uri === $url) || strpos($uri, $url . '/') === 0;
            // Seri panjang (induk dan anak berbagi URL, mis. Tinjau SRP2 dan SRP2 dalam
            // Pengajuan): anak yang menang, karena dialah nama layar yang sedang dibuka.
            if ($match && (strlen($url) > $best || (strlen($url) === $best && $item['parent'] !== NULL))) { $best = strlen($url); $best_i = $i; }
        }
        foreach ($items as $i => $item) { $items[$i]['active'] = ($i === $best_i); }

        // Susun jadi POHON, kedalaman bebas - `parent` boleh menunjuk item yang
        // sendirinya punya induk. Rekam Data memakai dua tingkat: Rekam Data →
        // Perumahan/Kawasan → Capaian/Rekap/Riwayat.
        //
        // Anak SELALU dirender (tidak dibuang saat cabangnya tertutup) supaya
        // pengguna bisa membukanya sendiri lewat tombol lipat. Yang diputuskan
        // di sini cuma keadaan AWALnya: cabang yang memuat halaman sekarang
        // terbuka, sisanya terlipat.
        $anak = [];
        foreach ($items as $item) {
            if ($item['parent'] !== NULL) { $anak[$item['parent']][] = $item; }
        }
        $per_key = [];
        foreach ($items as $item) { $per_key[$item['key']] = $item; }

        // Tandai seluruh leluhur item aktif sebagai "terbuka" - bukan hanya
        // induk langsungnya. Tanpa menaiki rantainya, membuka Rekap Perumahan
        // akan membuka "Perumahan" tetapi meninggalkan "Rekam Data" terlipat,
        // dan layar yang sedang dibuka jadi tidak terlihat sama sekali.
        $terbuka = [];
        $leluhur = []; // hanya rantai induk item aktif yang ikut menyala, bukan keturunannya
        foreach ($items as $item) {
            if ( ! $item['active']) { continue; }
            // Item aktif membuka DIRINYA SENDIRI juga, bukan hanya leluhurnya.
            // Tanpa ini, mendarat di "Rekam Data" menyorot induknya tetapi
            // membiarkan enam anaknya terlipat - orang sampai di halaman yang
            // gunanya justru mengantar, lalu tidak melihat satu pun tujuan.
            $terbuka[$item['key']] = TRUE;
            $naik = $item['parent'];
            while ($naik !== NULL && isset($per_key[$naik])) {
                $terbuka[$naik] = TRUE;
                $leluhur[$naik] = TRUE;
                $naik = $per_key[$naik]['parent'];
            }

            /**
             * Dan SELURUH keturunannya, bukan cuma anak langsung.
             *
             * Sebelum ini, mendarat di "Rekam Data" membuka Perumahan & Kawasan
             * tetapi meninggalkan Rekap dan Riwayat di bawahnya terlipat - dua
             * tingkat, jadi butuh dua klik caret lagi untuk melihat layar yang
             * memang dicari. Dinas melaporkannya sebagai "rekap/submit tidak
             * ada" (revisi 3 Agt 2026 butir 10); layarnya ada sejak 30 Jul,
             * yang tidak ada adalah jalan masuk yang terlihat.
             *
             * Cakupannya sempit dengan sendirinya: hanya cabang yang SEDANG
             * dibuka yang ikut terbentang. Di halaman lain tujuh entri Rekam
             * Data tetap menyusut jadi satu, yang memang alasan penyarangan itu
             * dibuat.
             */
            $turun = [$item['key']];
            while ($turun) {
                $kini = array_pop($turun);
                foreach ($anak[$kini] ?? [] as $cucu) {
                    if ( ! empty($terbuka[$cucu['key']])) { continue; }
                    $terbuka[$cucu['key']] = TRUE;
                    $turun[] = $cucu['key'];
                }
            }
        }

        $bangun = function ($key) use (&$bangun, $anak, $terbuka, $leluhur) {
            $out = [];
            foreach ($anak[$key] ?? [] as $item) {
                $item['children'] = $bangun($item['key']);
                $item['open']     = ! empty($terbuka[$item['key']]);
                // Induk ikut menyala bila MEMUAT layar yang dibuka - supaya orang tahu
                // sedang berada di cabang mana. Bukan sekadar terbuka: keturunan item
                // aktif ikut terbentang, dan dulu ikut menyala sehingga di SRP2 dalam
                // Pengajuan dua sub-menu tersorot bersamaan (audit UI 2 Okt 2026).
                $item['active']   = $item['active'] || ! empty($leluhur[$item['key']]);
                $out[] = $item;
            }
            return $out;
        };

        $grouped = [];
        foreach ($items as $item) {
            if ($item['parent'] !== NULL) { continue; }
            $item['children'] = $bangun($item['key']);
            $item['open']     = ! empty($terbuka[$item['key']]);
            $item['active']   = $item['active'] || ! empty($leluhur[$item['key']]);
            $grouped[$item['group']][] = $item;
        }
        return $grouped;
    }

    /**
     * Render dashboard shell (sidebar+topbar admin/index.php) yang dipakai SEMUA
     * role login - status_pengajuan/profil (warga/pengembang/mahasiswa) maupun
     * admin ter-scope. Menu diambil dari dashboard_menu(), bukan parameter -
     * satu sumber kebenaran untuk semua pemanggil (lihat render_admin()/
     * render_scoped_admin() di bawah, keduanya tinggal delegasi ke sini).
     *
     * @param string $view View path, mis. 'pages/pengaturan/index'
     * @param array  $data Data untuk view
     */
    protected function render_user_dashboard($view, $data = []) {
        $data['dashboard_home'] = $this->dashboard_home();

        // Cabang partial HANYA untuk loader dashboard (assets/js/admin-progressive.js),
        // dikenali lewat `X-Shell: admin` - BUKAN untuk sembarang permintaan AJAX.
        //
        // Kenapa: loader portal PUBLIK (application/views/layouts/footer.php)
        // menangkap semua tautan internal dan mem-fetch-nya dengan
        // `X-Requested-With: XMLHttpRequest`. Kalau cabang ini hanya memeriksa
        // "apakah AJAX", halaman admin dibalas TANPA shell admin lalu disuntikkan
        // ke panel publik: tanpa sidebar, tanpa tailwind-admin.css, dan tanpa ikon
        // Phosphor (portal memakai FontAwesome) - judul kartu putih di kartu putih,
        // ikon jadi kotak kosong. Terjadi nyata saat kartu REKAM DATA di beranda
        // publik diklik menuju /Rekam_Data.
        //
        // Dengan pemeriksaan ini, permintaan dari loader publik mendapat DOKUMEN
        // UTUH, dan `loadTab()` sudah punya penjaganya: pola `^\s*(<!doctype|<html)`
        // membuatnya menyerah ke navigasi penuh sehingga shell admin yang benar
        // termuat. Perhatikan bahwa daftar jalur di footer.php
        // (`login|admin|Admin|akun|...`) TIDAK bisa diandalkan sebagai penjaga -
        // ia menyebut nama jalur satu per satu, dan `Rekam_*` tidak memuat kata
        // "admin" sama sekali. Header ini menutup seluruh keluarga itu sekaligus,
        // termasuk controller admin baru yang namanya belum ada hari ini.
        if ($this->input->is_ajax_request()
            && $this->input->get_request_header('X-Shell', TRUE) === 'admin') {
            // Cabang partial untuk loader progresif dashboard - pola yang sama
            // dengan render() portal. Judul dikirim lewat header supaya
            // document.title ikut diperbarui tanpa membungkus HTML.
            if (! empty($data['title'])) {
                $this->output->set_header('X-Page-Title: ' . rawurlencode($data['title']));
            }
            $data['dashboard_menu'] = $this->dashboard_menu();
            $this->load->view($view, $data);

            // Menu ikut dikirim tiap pindah halaman, dibungkus <template> supaya
            // tidak ikut tampil di dalam konten. Loader menukarnya ke #sidebar-nav.
            //
            // WAJIB `append_output()`, BUKAN `echo`. `load->view()` menumpuk ke
            // buffer internal CI yang baru dikeluarkan di akhir, sedangkan `echo`
            // menulis langsung ke buffer PHP - hasilnya template mendarat di
            // posisi 0, SEBELUM kontennya. Loader memotong balasan di penanda
            // template, jadi konten yang tersisa nol byte dan seluruh halaman
            // admin tampil kosong saat dibuka lewat navigasi progresif.
            //
            // Alasannya bukan kerapian: sorotan aktif dan sub-menu diputuskan
            // dashboard_menu() (kecocokan URL terpanjang + cabang terbuka).
            // Sebelum ini loader menyalin sebagian aturan itu di JS - mencocokkan
            // path PERSIS dan menempel aria-current - sehingga /Rekam_Perumahan/input
            // tidak menyorot apa pun, sorotan lama dari render server tidak pernah
            // dilepas (dua item menyala bersamaan), dan sub-menu cabang lama tetap
            // terbuka di halaman yang tidak ada hubungannya. Mengirim menu jadi
            // yang termurah: satu aturan, satu tempat.
            $this->output->append_output('<template id="sidebar-nav-baru">'
                . $this->load->view('admin/layouts/sidebar_nav', $data, TRUE)
                . '</template>');
            return;
        }
        $data['dashboard_menu'] = $this->dashboard_menu();
        $data['content'] = $this->load->view($view, $data, TRUE);
        $this->load->view('admin/index', $data);
    }

    /**
     * Validate that a redirect path is internal (not an open redirect to external domain)
     *
     * @param string $path The path or URL to validate
     * @return string Safe redirect path (internal only)
     */
    /**
     * Gerbang ke registry pembatas laju bersama. Policy memisahkan scope,
     * sedangkan context memasok dimensi akun, NIK, atau objek bila dibutuhkan.
     */
    protected function rate_limit_consume($policy, array $context = [])
    {
        $this->load->library('Rate_limiter');
        return $this->rate_limiter->consume($policy, $context);
    }

    protected function rate_limit_inspect($policy, array $context = [])
    {
        $this->load->library('Rate_limiter');
        return $this->rate_limiter->inspect($policy, $context);
    }

    protected function rate_limit_hit($policy, array $context = [])
    {
        $this->load->library('Rate_limiter');
        return $this->rate_limiter->hit($policy, $context);
    }

    /**
     * Respons seragam: batas normal menghasilkan 429 + Retry-After, sedangkan
     * kegagalan konfigurasi/penyimpanan fail-closed sebagai 503.
     */
    /** Terapkan allowlist dan format positif untuk seluruh input web. */
    private function enforce_positive_input_validation()
    {
        $this->load->library('Input_guard');
        $status = $this->input_guard->validate_request();
        if ( ! empty($status['valid'])) { return; }

        $message = 'Permintaan ditolak karena format input tidak aman.';
        $this->output->set_status_header(400)->set_header('Cache-Control: no-store');
        if ($this->input->is_ajax_request()) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'status' => 'error', 'code' => 'invalid_input', 'message' => $message,
            ]));
            $this->output->_display(); exit;
        }
        show_error($message, 400, 'Input Tidak Valid');
        exit;
    }
    protected function rate_limit_reject(array $result, $message, $json = FALSE)
    {
        $configured = ! empty($result['success']);
        $is_attack_warning = $configured && ! empty($result['warning_type']);
        $safe_message = $configured
            ? ($is_attack_warning ? 'Peringatan keamanan: terdeteksi pola akses otomatis. ' : '') . $message
            : 'Layanan sementara belum dapat memproses permintaan.';

        $this->output->set_status_header($configured ? 429 : 503);
        if ($configured) {
            $this->output->set_header('Retry-After: ' . max(1, (int) ($result['retry_after'] ?? 1)));
        }
        if ($is_attack_warning) {
            $this->output->set_header('X-Security-Warning: automated-access-detected');
        }
        if ($json) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => 'error',
                    'code' => $is_attack_warning ? 'automated_attack_warning' : 'rate_limit_error',
                    'message' => $safe_message,
                    'warning_type' => $result['warning_type'] ?? NULL,
                    'retry_after' => $configured ? max(1, (int) ($result['retry_after'] ?? 1)) : NULL,
                ]));
            return;
        }
        $this->output
            ->set_content_type('text/plain', 'utf-8')
            ->set_output($safe_message);
    }

    /**
     * Saring tujuan redirect internal. Yang lolos HANYA path relatif aplikasi, mis.
     * "Umum/Sebaran" atau "Statistika?tahun=2025" (satu "/" di depan dibuang). Selain itu ''.
     *
     * Ditolak: kosong/bukan string; URL absolut atau berskema (":" di bagian path, termasuk
     * "javascript:"); "//host" dan "//" di mana pun di path; garis miring terbalik; spasi dan
     * karakter kontrol; segmen ".."; dan bentuk tersandinya (%2f%2f, %5c, %0d%0a, %252f...),
     * karena setiap lapis dekode diperiksa ulang. URL absolut ke situs sendiri pun ditolak:
     * tidak ada pemanggil yang membutuhkannya, dan satu aturan lebih mudah dibuktikan.
     * Pemanggil tetap menyaring ulang tepat sebelum redirect() (lapis kedua).
     * Uji: docs/engineering/uji_redirect_aman.php.
     */
    protected function sanitize_redirect($path) {
        if ( ! is_string($path) || $path === '' || strlen($path) > 2048) {
            return '';
        }
        // Bentuk mentah: hanya karakter path yang wajar, tanpa spasi; query bebas kecuali kontrol/spasi/backslash.
        if ( ! preg_match('#^/?[A-Za-z0-9_][A-Za-z0-9_\-.~%/+,=@]*(?:[?\#][^\x00-\x20\x7F\\\\]*)?$#', $path)) {
            return '';
        }
        $lapis = $path;
        for ($i = 0; $i < 5; $i++) {
            $jalur = preg_split('/[?#]/', $lapis, 2)[0];
            if (preg_match('/[\x00-\x1F\x7F\\\\]/', $lapis)                // kontrol (CR/LF dst) dan backslash di lapis mana pun
                || strpos($jalur, ':') !== FALSE                           // skema/port: "javascript:", "http:"
                || strpos($jalur, '//') !== FALSE                          // "//host" (protocol-relative) dan "/" ganda
                || preg_match('#(^|/)\.\.(/|$)#', $jalur)) {
                return '';
            }
            $dekode = rawurldecode($lapis);
            if ($dekode === $lapis) {
                return ltrim($path, '/') === $path ? $path : substr($path, 1);
            }
            $lapis = $dekode;
        }
        return ''; // masih berubah sesudah 5 lapis dekode: sengaja dikaburkan
    }

    /**
     * Lucuti metadata gambar (EXIF/komentar) di tempat, tanpa GD.
     *
     * DINAIKKAN ke induk 5 Agt 2026, isinya TIDAK diubah sedikit pun. Semula
     * `private` di `Warga.php`; begitu unggahan foto program lahir,
     * alternatifnya cuma menyalin - dan dua implementasi pelucut metadata
     * berarti yang satu bisa diperbaiki sementara yang lain tetap membocorkan
     * lokasi GPS pengunggah.
     */
    protected function strip_image_metadata($path, $mime)
    {
        $data = file_get_contents($path);
        if ($data === FALSE) { return FALSE; }
        if ($mime === 'image/png') {
            if (substr($data, 0, 8) !== "\x89PNG\r\n\x1a\n") { return FALSE; }
            $out = substr($data, 0, 8);
            $offset = 8;
            $ended = FALSE;
            while ($offset + 12 <= strlen($data)) {
                $length = unpack('N', substr($data, $offset, 4))[1];
                $chunk_length = 12 + $length;
                if ($offset + $chunk_length > strlen($data)) { return FALSE; }
                $type = substr($data, $offset + 4, 4);
                if ( ! in_array($type, ['eXIf', 'tEXt', 'zTXt', 'iTXt'], TRUE)) {
                    $out .= substr($data, $offset, $chunk_length);
                }
                $offset += $chunk_length;
                if ($type === 'IEND') { $ended = TRUE; break; }
            }
            return $ended && file_put_contents($path, $out, LOCK_EX) !== FALSE;
        }
        if ($mime !== 'image/jpeg' || substr($data, 0, 2) !== "\xFF\xD8") { return FALSE; }
        $out = "\xFF\xD8";
        $offset = 2;
        while ($offset < strlen($data)) {
            if (ord($data[$offset]) !== 0xFF) { return FALSE; }
            $start = $offset++;
            while ($offset < strlen($data) && ord($data[$offset]) === 0xFF) { $offset++; }
            if ($offset >= strlen($data)) { return FALSE; }
            $marker = ord($data[$offset++]);
            if ($marker === 0xDA) {
                $out .= substr($data, $start);
                return file_put_contents($path, $out, LOCK_EX) !== FALSE;
            }
            if ($marker === 0xD9) {
                $out .= substr($data, $start, $offset - $start);
                return file_put_contents($path, $out, LOCK_EX) !== FALSE;
            }
            if ($marker === 0x01 || ($marker >= 0xD0 && $marker <= 0xD7)) {
                $out .= substr($data, $start, $offset - $start);
                continue;
            }
            if ($offset + 2 > strlen($data)) { return FALSE; }
            $length = unpack('n', substr($data, $offset, 2))[1];
            if ($length < 2 || $offset + $length > strlen($data)) { return FALSE; }
            if ( ! in_array($marker, [0xE1, 0xED, 0xFE], TRUE)) {
                $out .= substr($data, $start, ($offset - $start) + $length);
            }
            $offset += $length;
        }
        return FALSE;
    }

    /** Folder foto/logo direktori SRP2 (aset publik, di-.gitignore supaya deploy tidak menyapunya). */
    const DIR_FOTO_SRP2 = 'assets/img/pengembang/unggahan/';

    /**
     * Simpan foto/logo perusahaan direktori SRP2 dari $_FILES[$field]. Dipakai admin
     * (Admin_Srp2::save) dan pengembang (Pengaturan::simpan_perusahaan), jadi satu aturan.
     *
     * Foto TAYANG PUBLIK, jadi isinya yang dijaga: jenis dari finfo (bukan nama/tipe kiriman),
     * getimagesize, pemindai unggahan, lalu DIGAMBAR ULANG lewat GD. Gambar ulang membuang
     * metadata (termasuk GPS) dan apa pun yang menumpang di luar piksel, dan sekalian
     * mengecilkan ke sisi terpanjang 512 px. Nama berkas acak; nama kiriman tidak menyentuh disk.
     *
     * @return string|NULL path relatif; NULL + $galat terisi bila ditolak. Tanpa berkas: NULL, $galat NULL.
     */
    protected function simpan_foto_srp2($field, &$galat = NULL)
    {
        $galat = NULL;
        $f = $_FILES[$field] ?? NULL;
        if ( ! $f || is_array($f['name'] ?? NULL) || ($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) { return NULL; }
        if ($f['error'] !== UPLOAD_ERR_OK || ! is_uploaded_file($f['tmp_name'])) { $galat = 'Foto gagal diunggah. Coba lagi.'; return NULL; }
        if ($f['size'] > 2 * 1024 * 1024) { $galat = 'Ukuran foto maksimal 2 MB.'; return NULL; }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        // Nama fungsi di sini hanya untuk cek function_exists; pemanggilannya literal (match) di bawah,
        // supaya tidak ada pemanggilan lewat nama dinamis (tests/dynamic_code_test.php).
        $jenis = [
            'image/jpeg' => ['jpg', 'imagecreatefromjpeg'],
            'image/png'  => ['png', 'imagecreatefrompng'],
            'image/webp' => ['webp', 'imagecreatefromwebp'],
        ][$mime] ?? NULL;
        if ($jenis === NULL || ! function_exists($jenis[1])) { $galat = 'Foto harus JPG, PNG, atau WEBP.'; return NULL; }
        $dimensi = @getimagesize($f['tmp_name']);
        if ($dimensi === FALSE || $dimensi[0] < 1 || $dimensi[1] < 1 || $dimensi[0] * $dimensi[1] > 25000000) {
            $galat = 'Berkas itu bukan gambar yang sah.'; return NULL;
        }
        if ( ! $this->scan_uploaded_file($f['tmp_name'], $jenis[0], $galat, 'srp2_foto')) { return NULL; }

        $img = match ($jenis[0]) {
            'jpg'  => @imagecreatefromjpeg($f['tmp_name']),
            'png'  => @imagecreatefrompng($f['tmp_name']),
            'webp' => @imagecreatefromwebp($f['tmp_name']),
        };
        if ( ! $img) { $galat = 'Berkas itu bukan gambar yang sah.'; return NULL; }
        $skala = min(1, 512 / max(imagesx($img), imagesy($img)));
        if ($skala < 1) {
            $kecil = imagescale($img, max(1, (int) round(imagesx($img) * $skala)), max(1, (int) round(imagesy($img) * $skala)));
            imagedestroy($img);
            $img = $kecil;
        }
        if ($jenis[0] !== 'jpg') { imagealphablending($img, FALSE); imagesavealpha($img, TRUE); }

        $dir = FCPATH . self::DIR_FOTO_SRP2;
        if ( ! is_dir($dir) && ! @mkdir($dir, 0755, TRUE)) { imagedestroy($img); $galat = 'Folder foto tidak bisa dibuat.'; return NULL; }
        $nama = bin2hex(random_bytes(16)) . '.' . $jenis[0];
        $ok = match ($jenis[0]) {
            'jpg'  => imagejpeg($img, $dir . $nama, 85),
            'png'  => imagepng($img, $dir . $nama, 6),
            'webp' => imagewebp($img, $dir . $nama, 85),
        };
        imagedestroy($img);
        if ( ! $ok) { @unlink($dir . $nama); $galat = 'Foto gagal disimpan.'; return NULL; }
        return self::DIR_FOTO_SRP2 . $nama;
    }

    /** Hapus berkas foto direktori SRP2; hanya path di dalam folder fotonya yang disentuh. */
    protected function hapus_foto_srp2($path)
    {
        $path = (string) $path;
        if (strpos($path, self::DIR_FOTO_SRP2) === 0 && basename($path) === substr($path, strlen(self::DIR_FOTO_SRP2))) {
            @unlink(FCPATH . $path);
        }
    }
    /**
     * Kirim satu lembar kerja ke peramban sebagai berkas Excel, lalu berhenti.
     *
     * SpreadsheetML (XML), BUKAN CSV dan BUKAN pustaka. Alasannya berurutan:
     *
     *   - Pustaka (PhpSpreadsheet) berarti dependensi baru + `composer install`
     *     di production, untuk sebuah tabel rekap. Terlalu mahal.
     *   - CSV terlihat lebih murah tapi punya jebakan yang justru menggigit di
     *     sini: `fputcsv` memakai KOMA, sementara di Excel berlokal Indonesia
     *     koma adalah pemisah DESIMAL dan pemisah daftarnya titik koma. Berkas
     *     koma terbuka jadi SATU KOLOM di komputer dinas. Memilih titik koma
     *     memindahkan masalahnya ke komputer yang berlokal lain - dua-duanya
     *     salah, tergantung mesin siapa yang membukanya.
     *   - SpreadsheetML menandai tiap sel `Number` atau `String` secara
     *     eksplisit, jadi angkanya mendarat sebagai angka apa pun lokalnya.
     *
     * `header()` MENTAH, bukan `$this->output->set_*`: badan berkas ditulis
     * langsung ke keluaran, sehingga antrean header CI terlambat terkirim -
     * alasan yang sama sudah dicatat di `serve_private_file()`.
     *
     * @param array $baris Tiap baris array nilai. Nilai NULL = sel KOSONG,
     *              dan itu disengaja: rekap ini menganut "nol tabel nol" -
     *              sumber tanpa laporan tidak boleh ditulis 0, karena nol
     *              karangan tidak bisa dibedakan dari nol yang dilaporkan.
     */
    protected function kirim_spreadsheet($nama_berkas, $nama_lembar, array $header, array $baris)
    {
        $x = static function ($v) {
            return htmlspecialchars((string) $v, ENT_QUOTES | ENT_XML1, 'UTF-8');
        };
        /* Butir cetak/rekap 17 Agt 2026: penjinak injeksi formula (CWE-1236,
         * "CSV injection"), ditambal DI SINI - satu-satunya tempat yang
         * merangkai sel string - supaya SEMUA pemanggil ikut terlindungi
         * tanpa disentuh satu per satu.
         *
         * Sebagian besar sel di sini memang teks tetap (label kolom, nama
         * kabupaten, status baku), tapi Rekam_Kawasan::export() ikut
         * menyertakan ISIAN BEBAS admin kab/kota (nama kegiatan, lokasi,
         * keterangan sumber) - dan berkas ini dibuka provinsi sebagai
         * lampiran resmi. Kalau isian itu diawali `=`, `+`, `-`, atau `@`,
         * sebagian pembaca spreadsheet menafsirkannya sebagai FORMULA, bukan
         * teks apa adanya - walau `ss:Type="String"` seharusnya mencegahnya
         * di pembaca yang taat aturan, tidak semua pembaca (LibreOffice,
         * Google Sheets lewat impor, versi Excel lama) menghormatinya sama
         * ketatnya. Satu baris yang lolos berarti kode yang dijalankan di
         * komputer petugas provinsi saat membuka "lampiran resmi" dari
         * kabupaten - eskalasi kepercayaan yang diam-diam, dan yang menaruh
         * datanya bukan superadmin.
         *
         * Penjinaknya: apostrof di depan. Cara ini yang dipakai Excel sendiri
         * saat pengguna mengetik `=` lalu ingin memaksanya jadi teks - aman
         * dibaca ulang oleh siapa pun, dan tidak mengubah teks yang memang
         * tidak diawali karakter itu.
         */
        $jinak = static function ($s) {
            return (preg_match('/^[=+\-@\t\r]/', $s) === 1) ? "'" . $s : $s;
        };
        $sel = static function ($v) use ($x, $jinak) {
            if ($v === NULL || $v === '') { return '<Cell/>'; }
            if (is_int($v) || is_float($v)) {
                return '<Cell><Data ss:Type="Number">' . $v . '</Data></Cell>';
            }
            return '<Cell><Data ss:Type="String">' . $x($jinak((string) $v)) . '</Data></Cell>';
        };

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
              . '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"'
              . ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n"
              . '<Worksheet ss:Name="' . $x(substr($nama_lembar, 0, 31)) . '"><Table>' . "\n";
        $xml .= '<Row>';
        foreach ($header as $h) { $xml .= '<Cell><Data ss:Type="String">' . $x($jinak((string) $h)) . '</Data></Cell>'; }
        $xml .= "</Row>\n";
        foreach ($baris as $r) {
            $xml .= '<Row>';
            foreach ($r as $v) { $xml .= $sel($v); }
            $xml .= "</Row>\n";
        }
        $xml .= "</Table></Worksheet>\n</Workbook>\n";

        // Nama berkas dijinakkan: ia masuk ke header HTTP, dan karakter aneh di
        // situ adalah jalan injeksi header.
        $aman = preg_replace('/[^A-Za-z0-9 _.-]/', '', (string) $nama_berkas);

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $aman . '.xls"');
        header('Content-Length: ' . strlen($xml));
        header('Cache-Control: private, no-store');
        echo $xml;
        exit;
    }

    /**
     * Gerbang "silakan masuk dulu" - SATU pintu untuk seluruh aplikasi.
     *
     * Dibuat 5 Agt 2026 (revisi dinas butir A5: "kalau user sudah login,
     * balikkan ke menu awal dia, jangan dilempar ke dashboard, soalnya
     * bingung"). Mesin pengingatnya sebenarnya SUDAH ADA sejak lama -
     * `Auth::_redirect_after_login()` membaca `intended_url` dari sesi. Yang
     * bolong: 21 tempat memanggil `$this->gerbang_login()` telanjang tanpa
     * pernah mengisinya, dan hanya dua controller yang mengisinya sendiri.
     * Karena itu sebagian halaman kembali dengan benar dan sebagian tidak.
     *
     * Ditaruh di induk, bukan ditambal di tiap controller: menambal 21 kali
     * berarti gerbang ke-22 lupa lagi.
     */
    /**
     * Render halaman masuk dengan satu pesan yang HANYA untuk render ini. Pesan lewat
     * set_flashdata() saja ikut tampil lagi di permintaan berikutnya (flashdata baru hidup
     * satu permintaan lagi), jadi pesannya muncul dua kali (temuan UAT universitas U8).
     */
    protected function render_login_berpesan($tipe, $pesan, array $data = []) {
        $this->session->set_flashdata($tipe, $pesan);
        $this->load->view('pages/auth/login', $data + ['recaptcha_site_key' => getenv('RECAPTCHA_SITE_KEY') ?: '']);
        $this->session->unset_userdata($tipe);
    }

    protected function gerbang_login($tujuan = NULL) {
        /* SATU GERBANG, DUA KEADAAN YANG SAMA SEKALI BERBEDA - dan sampai 10
           Agt 2026 keduanya diperlakukan sama, itu kekeliruannya.

           Belum login  : dilempar ke halaman masuk. Masuk akal.
           SUDAH login,
           salah peran  : juga dilempar ke halaman masuk - PADAHAL DIA SUDAH
                          MASUK. Yang dialami orangnya: menekan sesuatu, lalu
                          tiba-tiba diminta login lagi tanpa penjelasan, lalu
                          terlempar entah ke mana. Tidak ada satu pun kalimat
                          yang memberi tahu bahwa masalahnya PERAN, bukan sesi.

           Pesan dari controller pemanggil ("Anda bukan Admin Kabupaten/Kota")
           sebenarnya sudah bagus, tetapi mendarat di halaman masuk tempat orang
           tidak mencarinya. Sekarang pesan itu dibawa ke layar khusus yang
           menjelaskan keadaannya dan menawarkan jalan keluar. */
        if ($this->session->userdata('is_logged')) {
            $this->session->set_userdata('akses_ditolak_tujuan', (string) ($tujuan ?: uri_string()));
            redirect('Auth/akses_ditolak');
            return;
        }

        $this->ingat_halaman_asal($tujuan);
        redirect('Auth/login');
    }

    /**
     * Catat halaman yang sedang dituju supaya bisa dikembalikan sesudah login.
     *
     * 🔴 INI POLA OPEN REDIRECT, dan itu bukan basa-basi. "Simpan alamat lalu
     * arahkan ke sana sesudah login" persis mekanisme yang dipakai penyerang:
     * korban mengeklik tautan yang tampak sah, login sungguhan di situs kita,
     * lalu terlempar ke situs palsu dalam keadaan baru saja login - jauh lebih
     * meyakinkan daripada halaman phishing biasa.
     *
     * Empat lapis penjagaannya, dan lapis pertama yang paling menentukan:
     *
     *   1. Alamatnya diambil dari SERVER (`uri_string()`), TIDAK PERNAH dari
     *      query string atau isian mana pun. Alamat yang datang dari luar tidak
     *      dipercaya sama sekali - bukan disaring, tapi tidak dipakai.
     *   2. Hanya GET. Mengembalikan orang ke URL POST sesudah login cuma
     *      menghasilkan galat atau tindakan terkirim dua kali.
     *   3. Rute `auth/*` tidak pernah disimpan - akan melingkar ke layar masuk.
     *   4. Tetap dilewatkan `sanitize_redirect()` meski sumbernya server.
     *      Berlapis, karena satu perubahan kelak bisa mengubah asumsi ini.
     *
     * Dan satu hal yang tidak kelihatan tapi penting: kalau user SUDAH login,
     * asalnya TIDAK disimpan. Gerbang di bawah ini dipakai dua keperluan -
     * "belum login" dan "sudah login tapi salah peran/belum punya wilayah".
     * Untuk yang kedua, menyimpan asalnya membuat lingkaran: sesudah login
     * ulang ia dilempar ke sana lagi, lalu ditolak lagi.
     */
    protected function ingat_halaman_asal($tujuan = NULL) {
        if ($this->session->userdata('is_logged')) {
            return; // penolakan peran/scope, bukan gerbang "belum login"
        }

        if ($tujuan === NULL) {
            // Diturunkan dari server. Hanya GET: mengembalikan orang ke URL POST
            // sesudah login cuma menghasilkan galat atau tindakan terkirim dua kali.
            if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
                return;
            }
            $tujuan = (string) uri_string();
            if ($tujuan === '') {
                return; // sudah di beranda; tidak ada yang perlu diingat
            }
            $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
            if ($query !== '') {
                // Penyaring & tahun/triwulan hidup di query string. Tanpa ini user
                // kembali ke halaman yang benar tapi kehilangan tapisannya.
                $tujuan .= '?' . $query;
            }
        }
        /* $tujuan yang diberikan pemanggil MENANG atas URL saat ini, dan itu
           bukan kenyamanan belaka: `KemitraanPortal::akses_mahasiswa('akun')`
           sengaja mengirim orang ke halaman lain, bukan ke URL yang barusan
           ditolak. Nilainya selalu literal di kode - tidak pernah dari request -
           dan tetap dilewatkan penyaring di bawah. */

        $tujuan = (string) $tujuan;
        if (preg_match('#^auth(/|$)#i', $tujuan)) {
            return;
        }

        $aman = $this->sanitize_redirect($tujuan);
        if ($aman === '') {
            return;
        }
        $this->session->set_userdata('intended_url', $aman);
    }

    /**
     * Best-effort Web Push. Kegagalan kanal notifikasi tidak boleh membatalkan
     * data bisnis yang sudah sah tersimpan.
     */
    protected function notify_admin_push(array $audiences, $title, $body, $url, $tag)
    {
        try {
            $this->load->library('web_push_service');
            return $this->web_push_service->notify($audiences, $title, $body, $url, $tag);
        } catch (Throwable $e) {
            log_message('error', 'Pemicu Web Push gagal: ' . $e->getMessage());
            return ['sent' => 0, 'failed' => 0, 'skipped' => TRUE];
        }
    }
}

/**
 * Public_Controller Class
 * 
 * Base Controller for completely public-facing routes.
 */
class Public_Controller extends MY_Controller {

    public function __construct() {
        parent::__construct();
    }
}

class Admin_Controller extends MY_Controller {
    public function __construct() {
        parent::__construct();
        // Redirect jika belum login atau bukan admin
        if (!$this->session->userdata('is_logged') || $this->session->userdata('role') !== 'admin') {
            $this->session->set_flashdata('error', 'Akses ditolak. Anda bukan Administrator.');
            $this->gerbang_login();
        }
    }

    // Delegasi ke render_user_dashboard() - badge/menu superadmin sekarang
    // datang dari registry (dashboard_modules.php), bukan hardcode di sini.
    protected function render_admin($view, $data = []) {
        return $this->render_user_dashboard($view, $data);
    }
}

/**
 * Admin_Kabkota_Controller Class
 *
 * Base controller untuk admin yang di-scope ke 1 kabupaten/kota
 * (kelola antrean perumahan wilayahnya saja - lihat sf_antrean_pengajuan.kabupaten_id).
 * Scope-nya (kabupaten_id) ditaruh di session saat login, bukan dipercaya dari request.
 */
class Admin_Kabkota_Controller extends MY_Controller {

    protected $my_kabupaten_id;

    public function __construct() {
        parent::__construct();

        if ( ! $this->session->userdata('is_logged') || $this->session->userdata('role') !== 'admin_kabkota') {
            $this->session->set_flashdata('error', 'Akses ditolak. Anda bukan Admin Kabupaten/Kota.');
            $this->gerbang_login();
        }

        $this->my_kabupaten_id = $this->session->userdata('kabupaten_id');
        if (empty($this->my_kabupaten_id)) {
            $this->session->set_flashdata('error', 'Akun ini belum ditetapkan ke kabupaten/kota manapun. Hubungi superadmin.');
            $this->gerbang_login();
        }
        $this->enforce_current_module_privilege();
    }

    // Delegasi ke render_user_dashboard() - menu ter-scope sekarang datang
    // dari registry (dashboard_modules.php, filter role+scope), bukan hardcode.
    protected function render_scoped_admin($view, $data = []) {
        return $this->render_user_dashboard($view, $data);
    }
}

/**
 * Admin_Bidang_Controller Class
 *
 * Base controller untuk admin yang di-scope ke 1 bidang
 * (kelola aduan yang masuk ke bidangnya saja - lihat aduan.bidang_kode).
 * Scope-nya (bidang_kode) ditaruh di session saat login, bukan dipercaya dari request.
 */
class Admin_Bidang_Controller extends MY_Controller {

    protected $my_bidang_kode;

    public function __construct() {
        parent::__construct();

        if ( ! $this->session->userdata('is_logged') || $this->session->userdata('role') !== 'admin_bidang') {
            $this->session->set_flashdata('error', 'Akses ditolak. Anda bukan Admin Bidang.');
            $this->gerbang_login();
        }

        $this->my_bidang_kode = $this->session->userdata('bidang_kode');
        if (empty($this->my_bidang_kode)) {
            $this->session->set_flashdata('error', 'Akun ini belum ditetapkan ke bidang manapun. Hubungi superadmin.');
            $this->gerbang_login();
        }
        $this->enforce_current_module_privilege();
    }

    // Delegasi ke render_user_dashboard() - menu ter-scope sekarang datang
    // dari registry (dashboard_modules.php, filter role+scope), bukan hardcode.
    protected function render_scoped_admin($view, $data = []) {
        return $this->render_user_dashboard($view, $data);
    }
}
