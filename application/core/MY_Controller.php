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

        // Load essential helpers
        $this->load->helper(['url', 'form', 'security']);
        $this->load->library('session');

        // Set OWASP security headers on every response
        $this->set_security_headers();

        $this->usir_kalau_nonaktif();
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

        $row = $this->db->select('status')->get_where('usr_users', ['id' => $id])->row();

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
        // Anti-clickjacking
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');

        // Referrer Policy
        header("Referrer-Policy: strict-origin-when-cross-origin");

        // HSTS - enforce HTTPS for 1 year (only effective over HTTPS)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        // Permissions Policy - restrict browser APIs
        header("Permissions-Policy: camera=(), microphone=(), geolocation=(self), payment=(), usb=()");

        // Block cross-domain content policies (Flash/PDF)
        header('X-Permitted-Cross-Domain-Policies: none');
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
            if ( ! $this->db->table_exists('sys_jejak_audit')) { return; }
            $this->db->insert('sys_jejak_audit', [
                'actor_id'    => $this->get_user_id() ?: NULL,
                'actor_email' => $this->session->userdata('email') ?: NULL,
                'actor_role'  => $this->session->userdata('role') ?: NULL,
                'aksi'        => substr((string) $aksi, 0, 40),
                'objek_tipe'  => $objek_tipe !== NULL ? substr((string) $objek_tipe, 0, 40) : NULL,
                'objek_id'    => $objek_id !== NULL ? substr((string) $objek_id, 0, 60) : NULL,
                'ringkasan'   => substr((string) $ringkasan, 0, 255),
                'detail_json' => $detail ? json_encode($detail, JSON_UNESCAPED_UNICODE) : NULL,
                'ip'          => $this->input->ip_address(),
                'created_at'  => date('Y-m-d H:i:s'),
            ]);
        } catch (Exception $e) {
            log_message('error', 'Gagal menulis jejak audit (' . $aksi . '): ' . $e->getMessage());
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

        $this->ensure_private_uploads_protected();
        $dir = $this->private_upload_dir($domain, $owner_id);
        if ( ! is_dir($dir) && ! mkdir($dir, 0700, TRUE)) {
            $error = 'Gagal menyiapkan penyimpanan berkas.';
            return FALSE;
        }

        $nama_simpan = bin2hex(random_bytes(16)) . '.' . $ext;
        if ( ! move_uploaded_file($file['tmp_name'], $dir . $nama_simpan)) {
            $error = 'Berkas gagal disimpan.';
            return FALSE;
        }
        return $nama_simpan;
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
    protected function serve_private_file($domain, $owner_id, $stored_name, $mime = 'application/octet-stream') {
        $path = $this->private_upload_dir($domain, $owner_id) . basename((string) $stored_name);
        if (empty($stored_name) || ! is_file($path)) {
            // 404 ke klien tetap opaque (anti-IDOR), tapi penyebabnya WAJIB
            // tercatat - "mengapa 404" tidak boleh butuh bedah DB manual.
            log_message('error', sprintf(
                'serve_private_file 404: domain=%s owner=%s stored=%s (%s)',
                $domain, $owner_id, (string) $stored_name,
                empty($stored_name) ? 'stored_name kosong' : 'berkas tidak ada di disk'
            ));
            show_404(); return;
        }

        // header() langsung, BUKAN $this->output->set_content_type():
        // readfile() menulis body duluan sehingga antrean header CI terlambat -
        // PHP terlanjur mengirim text/html default, dan nosniff (dipasang di
        // constructor) melarang browser menebak, jadi gambar tampil sebagai teks.
        header('Content-Type: ' . $mime);
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
        $kolom_sort = [
            'sf_housing_queue.created_at', 'sf_housing_queue.nama_lengkap',
            'sf_programs.nama_program', 'sf_housing_queue.status_antrean',
        ];
        $table = $this->table_state($kolom_sort, 'sf_housing_queue.created_at');

        $status = $this->input->get('status', TRUE);
        $status = in_array($status, ['pending', 'needs_revision', 'approved', 'rejected'], TRUE) ? $status : NULL;
        $tanpa_wilayah = $kabupaten_id === NULL && $this->input->get('tanpa_wilayah', TRUE) === '1';

        $this->db->from('sf_housing_queue')
            ->join('sf_programs', 'sf_housing_queue.program_id = sf_programs.id', 'left');
        if ($kabupaten_id !== NULL) { $this->db->where('sf_housing_queue.kabupaten_id', $kabupaten_id); }
        if ($status) { $this->db->where('sf_housing_queue.status_antrean', $status); }
        if ($tanpa_wilayah) { $this->db->where('sf_housing_queue.kabupaten_id IS NULL', NULL, FALSE); }
        if ($table['q'] !== '') {
            $this->db->group_start()
                ->like('sf_housing_queue.nama_lengkap', $table['q'])
                ->or_like('sf_housing_queue.nik_pengaju', $table['q'])
                ->or_like('sf_housing_queue.ticket_code', $table['q'])
                ->or_like('sf_programs.nama_program', $table['q'])
                ->group_end();
        }

        // FALSE = pertahankan state query builder untuk query ambil di bawah.
        $table += $this->paginate_state($this->db->count_all_results('', FALSE));

        $queue = $this->db->select('sf_housing_queue.*, sf_programs.nama_program')
            ->order_by($table['sort'], $table['dir'])
            ->limit($table['per_page'], $table['offset'])
            ->get()->result();

        return [
            'queue' => $queue, 'table' => $table, 'pager' => $table,
            'filter_status' => $status, 'filter_tanpa_wilayah' => $tanpa_wilayah,
            'can_filter_tanpa_wilayah' => $kabupaten_id === NULL,
        ];
    }

    protected function assessment_detail_data($queue_id, $kabupaten_id = NULL) {
        $this->load->model('Housing_assessment_model');
        $this->load->library('encryption_lib');
        $this->load->library('Matriks_program_ruleset');
        $detail = $this->Housing_assessment_model->get_scoped_queue_detail($queue_id, $kabupaten_id);
        if ( ! $detail) { return NULL; }

        $assessment = $detail['assessment'];
        $source_row = $this->db->select('payload_ciphertext')
            ->get_where('sf_rekaman_simperum', ['id' => (int) ($assessment['simperum_snapshot_id'] ?? 0)])
            ->row_array();
        $source = $source_row
            ? json_decode($this->encryption_lib->decrypt($source_row['payload_ciphertext']), TRUE) : [];
        $selected_id = (int) ($detail['queue']['recommendation_id'] ?? 0);
        $recommendations = $this->Housing_assessment_model->get_owned_recommendations(
            (int) ($assessment['id'] ?? 0),
            (int) ($detail['queue']['user_id'] ?? 0)
        );
        foreach ($recommendations as &$recommendation) {
            $recommendation['is_selected'] = (int) $recommendation['recommendation_id'] === $selected_id;
        }
        unset($recommendation);
        $provenance_value = $assessment['field_provenance_json']
            ?? $detail['profile_snapshot']['field_provenance_json'] ?? [];
        $provenance = is_array($provenance_value)
            ? $provenance_value : (json_decode((string) $provenance_value, TRUE) ?: []);

        /* Rekomendasi matriks xlsx (kolom J Sheet4) untuk ADMIN - permintaan
           user 23 Agt 2026 ("ya kerjakan", menindaklanjuti pertanyaan
           "apakah admin bisa mengelolanya?"). SAMA PERSIS logika yang
           dipakai Warga::pendataan() (fungsi murni dari 7 field
           matrix_*_code + umur, dihitung ulang di sini juga - bukan
           disimpan ke DB, lihat catatan konsistensi di Warga.php), TAPI
           BEDA satu hal yang disengaja: admin melihat hasil pencocokan
           ASLI apa adanya, TIDAK ditimpa jadi 'Oemah Lestari'/'FLPP'
           seperti di sisi warga. Penimpaan itu di sisi warga adalah
           pengaman supaya warga tidak melihat nama program yang belum
           terverifikasi SIMPERUM sebagai kepastian - admin adalah
           peninjau tepercaya yang justru PERLU melihat hasil aslinya
           untuk mengambil keputusan, dilengkapi status "data ada/tidak
           ada di SIMPERUM" supaya tetap tahu tingkat kepercayaannya. */
        $matriks_income_code = $assessment['matrix_income_code'] ?? NULL;
        $matriks_birth_date = $detail['profile_snapshot']['birth_date'] ?? NULL;
        $matriks_age_years = NULL;
        if (trim((string) $matriks_birth_date) !== '') {
            try {
                $matriks_age_years = (new DateTime($matriks_birth_date))->diff(new DateTime('today'))->y;
            } catch (Exception $e) {
                $matriks_age_years = NULL;
            }
        }
        $matriks_recommendation = $this->matriks_program_ruleset->match([
            'income_code' => $matriks_income_code,
            'welfare_decile' => $this->matriks_program_ruleset->decile_for_income($matriks_income_code),
            'dtks_code' => $assessment['matrix_dtks_status'] ?? NULL,
            'land_code' => $assessment['matrix_land_ownership_code'] ?? NULL,
            'housing_code' => $assessment['matrix_current_housing_code'] ?? NULL,
            'environment_code' => $assessment['matrix_environment_condition_code'] ?? NULL,
            'occupation_code' => $assessment['matrix_occupation_finance_code'] ?? NULL,
            'age_years' => $matriks_age_years,
            'family_code' => $assessment['matrix_marital_family_code'] ?? NULL,
        ]);

        return [
            'queue' => $detail['queue'], 'assessment' => $assessment,
            'profile' => $detail['profile_snapshot'] ?? [],
            'source_snapshot' => is_array($source) ? $source : [],
            'provenance' => $provenance,
            'recommendations' => $recommendations,
            'evidence' => $this->Housing_assessment_model->get_scoped_queue_files($queue_id, $kabupaten_id),
            'matriks_decile_label' => $this->matriks_program_ruleset->decile_label_for_income($matriks_income_code),
            'matriks_data_simperum' => ($assessment['source_mode'] ?? NULL) !== 'manual',
            'matriks_recommendation' => $matriks_recommendation,
        ];
    }

    protected function scoped_queue_file($queue_id, $file_kind, $kabupaten_id = NULL) {
        $this->load->model('Housing_assessment_model');
        foreach ($this->Housing_assessment_model->get_scoped_queue_files($queue_id, $kabupaten_id) as $file) {
            if (hash_equals((string) $file['file_kind'], (string) $file_kind)) { return $file; }
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
    protected function dashboard_home($role = NULL) {
        $role = $role ?: $this->current_role();
        if (empty($role)) { return 'akun'; }

        $this->config->load('dashboard_modules', FALSE, TRUE);
        $modules     = $this->config->item('dashboard_modules') ?: [];
        $group_order = $this->config->item('dashboard_module_groups') ?: [];

        $kandidat = [];
        foreach ($modules as $m) {
            if (array_key_exists('enabled', $m) && $m['enabled'] === FALSE) { continue; }
            if (empty($m['roles']) || ! in_array($role, $m['roles'], TRUE)) { continue; }
            $scope_value = ! empty($m['scope']) ? $this->session->userdata($m['scope']) : NULL;
            if ( ! empty($m['scope']) && empty($scope_value)) { continue; }
            if ( ! empty($m['scope_values']) && ! in_array($scope_value, $m['scope_values'], TRUE)) { continue; }
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
     * Hitung baris "belum diproses" untuk satu entri registry, berdasarkan
     * 'table' + 'pending_where' yang dideklarasikan di sana. Satu mekanisme
     * untuk badge sidebar DAN kartu ringkas overview superadmin - sebelumnya
     * tiap counter dulu butuh method model sendiri.
     *
     * @param array $modul entri dari config dashboard_modules
     * @return int 0 kalau entri tidak mendeklarasikan tabel/pending_where
     */
    protected function count_pending_modul($modul) {
        if (empty($modul['table']) || empty($modul['pending_where'])) {
            return 0;
        }
        $query = $this->db->where($modul['pending_where']);
        if ( ! empty($modul['scope_column'])) {
            $scope = $modul['scope'] ?? NULL;
            $scope_value = $scope ? $this->session->userdata($scope) : NULL;
            if ($scope === NULL || $scope_value === NULL || $scope_value === '') {
                return 0;
            }
            $query->where($modul['scope_column'], $scope_value);
        }
        return (int) $query->count_all_results($modul['table']);
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
        $modules     = $this->config->item('dashboard_modules') ?: [];
        $group_order = $this->config->item('dashboard_module_groups') ?: [];

        $role  = $this->current_role();
        $items = [];
        foreach ($modules as $key => $m) {
            if (array_key_exists('enabled', $m) && $m['enabled'] === FALSE) { continue; }
            if (empty($m['roles']) || ! in_array($role, $m['roles'], TRUE)) { continue; }
            $scope_value = ! empty($m['scope']) ? $this->session->userdata($m['scope']) : NULL;
            if ( ! empty($m['scope']) && empty($scope_value)) { continue; }
            if ( ! empty($m['scope_values']) && ! in_array($scope_value, $m['scope_values'], TRUE)) { continue; }

            $badge = NULL;
            if ( ! empty($m['badge'])) {
                $badge = $this->count_pending_modul($m) ?: NULL;
            }

            $items[] = [
                'key' => $key, 'label' => $m['label'], 'icon' => $m['icon'], 'url' => $m['url'],
                'group' => $m['group'] ?? '', 'order' => $m['order'] ?? 999, 'badge' => $badge,
                'parent' => $m['parent'] ?? NULL,
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
            if ($match && strlen($url) > $best) { $best = strlen($url); $best_i = $i; }
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

        $bangun = function ($key) use (&$bangun, $anak, $terbuka) {
            $out = [];
            foreach ($anak[$key] ?? [] as $item) {
                $item['children'] = $bangun($item['key']);
                $item['open']     = ! empty($terbuka[$item['key']]);
                // Induk ikut menyala saat cabangnya terbuka - supaya orang tahu
                // sedang berada di cabang mana, bukan cuma di layar mana.
                $item['active']   = $item['active'] || $item['open'];
                $out[] = $item;
            }
            return $out;
        };

        $grouped = [];
        foreach ($items as $item) {
            if ($item['parent'] !== NULL) { continue; }
            $item['children'] = $bangun($item['key']);
            $item['open']     = ! empty($terbuka[$item['key']]);
            $item['active']   = $item['active'] || $item['open'];
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
    protected function rate_limit_reject(array $result, $message, $json = FALSE)
    {
        $configured = ! empty($result['success']);
        $safe_message = $configured
            ? $message
            : 'Layanan sementara belum dapat memproses permintaan.';

        $this->output->set_status_header($configured ? 429 : 503);
        if ($configured) {
            $this->output->set_header('Retry-After: ' . max(1, (int) ($result['retry_after'] ?? 1)));
        }
        if ($json) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => 'error', 'message' => $safe_message]));
            return;
        }
        $this->output
            ->set_content_type('text/plain', 'utf-8')
            ->set_output($safe_message);
    }

    protected function sanitize_redirect($path) {
        if (empty($path)) {
            return '';
        }

        // If it's already a relative path (e.g., "Umum/Sebaran"), it's safe
        if (strpos($path, '://') === false && strpos($path, '//') !== 0) {
            // Strip any leading slashes to normalize
            return ltrim($path, '/');
        }

        // If it starts with our base_url, extract the relative path
        $base = base_url();
        if (strpos($path, $base) === 0) {
            return substr($path, strlen($base));
        }

        // Anything else (external URL) → reject, return empty
        return '';
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
 * (kelola antrean perumahan wilayahnya saja - lihat sf_housing_queue.kabupaten_id).
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
 * (kelola aduan yang masuk ke bidangnya saja - lihat aduan.bidang).
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
    }

    // Delegasi ke render_user_dashboard() - menu ter-scope sekarang datang
    // dari registry (dashboard_modules.php, filter role+scope), bukan hardcode.
    protected function render_scoped_admin($view, $data = []) {
        return $this->render_user_dashboard($view, $data);
    }
}
