<?php
defined('BASEPATH') || exit('No direct script access allowed');

class Auth extends MY_Controller {

    protected $google_client;

    /** Satu pesan untuk setiap login gagal: akun tidak ada, sandi salah, akun tanpa sandi. */
    const PESAN_GAGAL = 'Email/username atau password salah.';
    /** Hash bcrypt dari nilai acak yang dibuang; dipakai bila akun tidak ada supaya waktu respons setara.
        Satu per biaya bawaan password_hash() (10; 12 sejak PHP 8.4), mengikuti biaya hash akun baru. */
    const HASH_TIRUAN = [
        10 => '$2y$10$ZIKXoFd.94t.ekhlY3Bid.4NLPoFjQNRUzYnxj3I94qEw/JSt6i6O',
        12 => '$2y$12$oR/a/6hS68D47mLR8mwyAOOTXBSW7FVMjGa/VuqTM/ZXQZjMBvqgm',
    ];

    // reCAPTCHA keys (set your own in .env or config)
    private $recaptcha_site_key   = '';
    private $recaptcha_secret_key = '';

    public function __construct() {
        parent::__construct();
        $this->load->config('google');
        $this->load->model('user_model');
        $this->load->model('auth_model');
        $this->load->library('encryption_lib');

        // reCAPTCHA config (load from .env if available)
        $this->recaptcha_site_key   = getenv('RECAPTCHA_SITE_KEY') ?: '';
        $this->recaptcha_secret_key = getenv('RECAPTCHA_SECRET_KEY') ?: '';

        // Inisialisasi Google Client Library
        $this->google_client = new Google\Client();
        $this->google_client->setClientId($this->config->item('client_id', 'google'));
        $this->google_client->setClientSecret($this->config->item('client_secret', 'google'));
        $this->google_client->setRedirectUri($this->config->item('redirect_uri', 'google'));

        foreach ($this->config->item('scopes', 'google') as $scope) {
            $this->google_client->addScope($scope);
        }
    }

    // =========================================================
    // LOGIN - Email/Password
    // =========================================================

    /**
     * Display login page
     */
    public function login() {
        // Simpan tujuan lanjutan setelah login (mis. link "Sudah punya akun?"
        // dari alur pendaftaran SRP2) - dibaca lewat ?next=, divalidasi anti-open-redirect.
        // WAJIB dibaca duluan SEBELUM cek is_logged_in(), supaya kalau user ternyata
        // sudah login, redirect di bawah tetap tahu harus lanjut ke mana (bukan jatuh ke beranda).
        $next = $this->input->get('next', TRUE);
        if (!empty($next)) {
            $safe_next = $this->sanitize_redirect($next);
            if (!empty($safe_next)) {
                $this->session->set_userdata('intended_url', $safe_next);
            }
        }

        // If already logged in, redirect
        if ($this->is_logged_in()) {
            $this->_redirect_after_login();
            return;
        }

        $data = ['recaptcha_site_key' => $this->recaptcha_site_key];
        // Pengaturan::delete_account() mengalihkan ke sini sesudah sess_destroy(), jadi
        // flashdata tidak bisa ikut; penandanya lewat ?msg= dengan pesan tetap dari server
        // (temuan UAT universitas U8: sebelumnya tidak ada konfirmasi sama sekali).
        if ($this->input->get('msg', TRUE) === 'account_deleted') {
            $this->render_login_berpesan('success', 'Akun Anda sudah dihapus.', $data);
            return;
        }
        if ($this->input->get('msg', TRUE) === 'sandi_diganti') {
            $this->render_login_berpesan('success', 'Password Anda sudah diganti. Masuk dengan sandi baru; sandi itu wajib diganti saat masuk.', $data);
            return;
        }
        $this->load->view('pages/auth/login', $data);
    }

    /**
     * Process login form (POST)
     */
    public function do_login() {
        $login_id = trim($this->input->post('email', TRUE));
        $password = $this->input->post('password');
        $is_ajax  = $this->input->is_ajax_request();

        // Keputusan pemilik produk 22 Sep 2026: yang dihitung hanya percobaan GAGAL (dicatat di
        // _login_fail). Menghitung setiap percobaan membuat 30 login sah per 5 menit dari satu IP
        // kantor (NAT) saling mengunci. Tebakan per akun ditahan per pasangan IP + nama masuk
        // (login_akun, di bawah), bukan lagi kunci per akun.
        $rate = $this->rate_limit_inspect('login');
        if (empty($rate['success']) || empty($rate['allowed'])) {
            $this->rate_limit_reject(
                $rate,
                'Terlalu banyak percobaan masuk dalam waktu singkat. Silakan tunggu sebelum mencoba lagi.',
                $is_ajax
            );
            return;
        }

        // Kalau form login ini ditanam di halaman lain (mis. wizard SRP2 Pengembang/syarat),
        // form itu kirim hidden field 'redirect_to' supaya kalau gagal (jalur non-AJAX), user
        // tetap di halaman asalnya - bukan terlempar ke Auth/login umum. Divalidasi anti-open-redirect.
        $error_target = $this->sanitize_redirect($this->input->post('redirect_to', TRUE)) ?: 'Auth/login';

        // Tantangan bot (honeypot + token waktu; poin 10.4). reCAPTCHA dilewati bila kuncinya kosong,
        // dan di production kuncinya kosong, jadi tanpa ini login tidak punya tantangan bot sama sekali.
        if ( ! $this->_bot_gate('login', $is_ajax, $error_target)) {
            return;
        }

        // Basic validation
        if (empty($login_id) || empty($password)) {
            $this->_login_fail($is_ajax, 'Email/Username dan password wajib diisi.', $error_target);
            return;
        }

        // Verify reCAPTCHA (skip if no secret key configured)
        if (!empty($this->recaptcha_secret_key)) {
            $recaptcha_response = $this->input->post('g-recaptcha-response');
            if (!$this->_verify_recaptcha($recaptcha_response)) {
                $this->_login_fail($is_ajax, 'Verifikasi Captcha gagal. Silakan coba lagi.', $error_target);
                return;
            }
        }

        /* Anti enumerasi dan anti penguncian oleh orang lain (3 Okt 2026). Semua kegagalan memakai
           PESAN_GAGAL yang sama; akun tak dikenal tetap menjalankan bcrypt (hash tiruan) supaya
           waktunya setara; tidak ada lagi kunci per akun yang bisa dipicu siapa saja. Penebak
           ditahan per pasangan IP + nama masuk (login_akun) dan per IP (login). */
        $pasangan = ['key' => hash('sha256', anti_automation_ip_bucket($this->input->ip_address()) . '|' . strtolower($login_id))];
        $rate = $this->rate_limit_inspect('login_akun', $pasangan);
        if (empty($rate['success']) || empty($rate['allowed'])) {
            $this->rate_limit_reject($rate,
                'Terlalu banyak percobaan masuk dalam waktu singkat. Silakan tunggu sebelum mencoba lagi.', $is_ajax);
            return;
        }

        $user = $this->auth_model->find_by_login($login_id);
        $tiruan = self::HASH_TIRUAN[PHP_VERSION_ID >= 80400 ? 12 : 10];
        $hash = ($user && ! empty($user->kata_sandi)) ? (string) $user->kata_sandi : $tiruan;

        // Verify password (terhadap hash tiruan bila akun tidak ada atau tanpa sandi)
        $password_valid = password_verify($password, $hash) && $hash !== $tiruan;
        $this->load->library('sensitive_buffer');
        $this->sensitive_buffer->wipe($password);
        if (isset($_POST['password'])) {
            $this->sensitive_buffer->wipe($_POST['password']);
        }
        if (!$password_valid) {
            $this->rate_limit_hit('login_akun', $pasangan);
            if ($user && $this->auth_model->increment_login_attempts($user->id) === TRUE) {
                // Gagal beruntun: bisa salah ketik, bisa tebak-sandi/credential stuffing. Akun TIDAK
                // dikunci (orang lain tidak boleh bisa mengunci pemiliknya); peringatan ke admin
                // (poin 10.5), hanya id akun, tanpa email/NIK.
                try {
                    $this->load->library('Security_alert');
                    $this->security_alert->raise('login_beruntun', 'sedang',
                        'Akun (id ' . (int) $user->id . ') menerima ' . Auth_model::MAX_LOGIN_ATTEMPTS . ' percobaan login gagal beruntun',
                        ['akun_id' => (int) $user->id], 'lock:' . (int) $user->id);
                } catch (Throwable $e) { log_message('error', 'Auth: peringatan gagal login beruntun gagal: ' . $e->getMessage()); }
            }
            $this->_login_fail($is_ajax, self::PESAN_GAGAL, $error_target);
            return;
        }

        /**
         * GERBANG STATUS - ditambahkan 3 Agt 2026 bersama layar Akses Staf.
         *
         * Yang diblokir HANYA `nonaktif`, bukan "apa pun yang bukan active": `restricted`
         * peninggalan lama (termasuk akun superadmin) bekerja normal, dan menetapkan maknanya
         * lewat gerbang login adalah keputusan produk. Diperiksa SESUDAH sandi terbukti (3 Okt
         * 2026), jadi status akun hanya terbaca oleh yang memegang sandinya.
         */
        if (strtolower(trim((string) ($user->status ?? ''))) === 'nonaktif') {
            $this->_login_fail($is_ajax,
                'Akun ini dinonaktifkan. Hubungi Super Admin bila menurut Anda ini keliru.',
                $error_target);
            return;
        }

        // Success - reset attempts and create session
        $this->auth_model->reset_login_attempts($user->id);

        $session_data = [
            'user_id'      => $user->id,
            'name'         => $user->nama,
            'username'     => $user->nama_pengguna ?? '',
            'email'        => $user->email,
            'avatar'       => $user->foto_profil,
            'role'         => $user->peran, // Added role to session
            'kabupaten_id' => $user->kabupaten_id ?? null, // scope untuk role admin_kabkota
            'bidang_kode'  => $user->bidang_kode ?? null, // scope untuk role admin_bidang
            'is_logged'    => TRUE,
        ];
        $session_data['password_change_required'] = $this->auth_model->password_expired($user);
        $this->session->sess_regenerate(TRUE);
        $session_data['session_auth_token'] = $this->auth_model->issue_session_token($user->id, $this->session->session_id);
        $this->session->set_userdata($session_data);

        // Draft SRP2 dipastikan ada untuk SEMUA jalur login - bukan cuma cabang
        // AJAX. Dulu pemanggilan ini ada DI DALAM `if ($is_ajax)`, sehingga
        // pengembang yang masuk lewat halaman login utama (`Auth/login`) tidak
        // punya baris draft, dan `Pengaturan::index()` yang menjaga dengan
        // `if ($sp2)` membuat item SRP2-nya tidak muncul sama sekali di /akun.
        // Hasilnya: fitur yang sama terlihat ada atau tidak ada, tergantung
        // lewat pintu mana user masuk.
        $srp2 = ($user->peran === 'pengembang')
            ? $this->auth_model->srp2_state($user->id)
            : NULL;
        $pengajuan_id = $srp2['pengajuan_id'] ?? NULL;

        // Wizard (mis. SRP2 di Pengembang/syarat) cuma butuh konfirmasi + role, bukan redirect -
        // wizard yang urus lanjutannya sendiri di sisi klien, tanpa pindah halaman.
        if ($is_ajax) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'status'          => 'success',
                'role'            => $user->peran,
                'name'            => $user->nama,
                'pengajuan_id' => $pengajuan_id,
                // Keadaan pengajuan ikut dikirim supaya wizard tidak menampilkan
                // keadaan tamu (0/14 dokumen, catatan admin hilang) untuk
                // pengembang lama yang baru saja masuk lewat wizard.
                'srp2'            => $srp2,
                // Dipakai wizard SRP2 saat akun yang login ternyata bukan
                // pengembang: kartu salah-role butuh tujuan dashboard yang benar
                // untuk role INI, bukan tautan hardcode ke `akun`.
                'dashboard_url'   => $this->dashboard_home($user->peran),
                'is_pengelola'    => in_array($user->peran, ['admin', 'admin_kabkota', 'admin_bidang'], TRUE),
            ]));
            return;
        }

        // Kalau sebelumnya diarahkan ke sini di tengah alur lain (mis. pendaftaran SRP2
        // lewat link "Sudah punya akun?"), kasih tahu login berhasil dan alurnya lanjut.
        if (!empty($this->session->userdata('intended_url'))) {
            $this->session->set_flashdata('success', 'Anda berhasil masuk. Mari lanjutkan.');
        }

        // Redirect based on profile completion
        $this->_redirect_after_login();
    }

    /**
     * Balas gagal login - JSON kalau request AJAX (dipakai wizard SRP2), flashdata+redirect
     * kalau request halaman biasa (perilaku asli, tidak berubah).
     */
    /**
     * Gerbang tantangan bot untuk login/registrasi. TRUE = lanjut. FALSE = respons penolakan
     * sudah dikirim (pemanggil cukup return). Setiap penolakan menjadi peringatan keamanan
     * (ditekan duplikatnya) yang dilihat administrator di Jejak Audit.
     */
    private function _bot_gate($form, $is_ajax, $target) {
        $this->load->library('Bot_guard');
        $hasil = $this->bot_guard->check($form);
        if ( ! empty($hasil['ok'])) {
            return TRUE;
        }
        try {
            $this->load->library('Security_alert');
            $this->security_alert->raise(
                'bot_form', 'sedang',
                "Formulir {$form} ditolak: tanda otomatisasi ({$hasil['reason']})",
                ['form' => $form, 'alasan' => $hasil['reason']],
                'bot:' . $form . ':' . $hasil['reason']
            );
        } catch (Throwable $e) {
            log_message('error', 'Auth::_bot_gate: peringatan gagal: ' . $e->getMessage());
        }
        // Pesan SAMA untuk semua alasan: tidak membocorkan mana yang memicu penolakan.
        $pesan = 'Verifikasi keamanan gagal. Muat ulang halaman lalu coba lagi.';
        if ($form === 'register') {
            $this->_register_fail($is_ajax, $pesan, $target);
        } else {
            $this->_login_fail($is_ajax, $pesan, $target);
        }
        return FALSE;
    }

    private function _login_fail($is_ajax, $message, $error_target) {
        $this->rate_limit_hit('login');
        if ($is_ajax) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'status'  => 'error',
                'message' => $message,
            ]));
            return;
        }
        $this->session->set_flashdata('error', $message);
        // Lapis kedua: disaring ulang tepat sebelum redirect(), bukan hanya saat dibaca dari POST.
        redirect($this->sanitize_redirect($error_target) ?: 'Auth/login');
    }

    // =========================================================
    // REGISTRATION - Step 1 (Email + Password)
    // =========================================================

    /**
     * Display registration page
     */
    public function register() {
        if ($this->is_logged_in()) {
            $this->_redirect_after_login();
            return;
        }

        $data = ['recaptcha_site_key' => $this->recaptcha_site_key];
        $this->load->view('pages/auth/register', $data);
    }

    /**
     * Process registration form (POST)
     */
    public function do_register() {
        $email            = trim($this->input->post('email', TRUE));
        $password         = $this->input->post('password');
        $password_confirm = $this->input->post('password_confirm');
        $is_srp2          = $this->input->post('srp2_pengembang') === '1';
        $nama_perusahaan  = trim($this->input->post('nama_perusahaan', TRUE));
        $is_ajax          = $this->input->is_ajax_request();
        // Wizard SRP2 sekarang tinggal di Pengembang/syarat - bukan lagi halaman
        // Pengembang/daftar terpisah (diarsipkan, cuma jadi redirect ke sini).
        $redirect_target  = $is_srp2 ? 'Pengembang/syarat' : 'Auth/register';

        // Batas laju pendaftaran per IP. Tanpa ini satu skrip bisa menciptakan
        // akun pengembang aktif berulang-ulang, masing-masing berhak membuka
        // pengajuan ke meja admin - meja verifikasi yang tercemar membatalkan
        // nilai seluruh mesin verifikasi. reCAPTCHA TIDAK bisa diandalkan
        // sebagai gantinya: verifikasinya dilewati seluruhnya kalau kuncinya
        // kosong, dan di .env lokal memang kosong. Roadmap T0 butir 3.
        //
        // Dicatat pada SETIAP percobaan, bukan cuma yang gagal - di sini justru
        // pendaftaran yang BERHASIL berulang kali yang jadi penyalahgunaannya.
        $rate = $this->rate_limit_consume('register');
        if (empty($rate['success']) || empty($rate['allowed'])) {
            $this->rate_limit_reject(
                $rate,
                'Terlalu banyak percobaan pendaftaran. Silakan coba lagi sebentar.',
                $is_ajax
            );
            return;
        }

        // Tantangan bot (poin 10.4), lihat komentar di do_login().
        if ( ! $this->_bot_gate('register', $is_ajax, $redirect_target)) {
            return;
        }

        // Validation
        if (empty($email) || empty($password) || empty($password_confirm)) {
            $this->_register_fail($is_ajax, 'Semua field wajib diisi.', $redirect_target);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->_register_fail($is_ajax, 'Format email tidak valid.', $redirect_target);
            return;
        }

        // Centang S&K dulu hanya dijaga atribut required di peramban (UAT warga 26 Sep 2026).
        if ( ! in_array((string) $this->input->post('tos_agree'), ['1', 'on', 'true'], TRUE)) {
            $this->_register_fail($is_ajax, 'Centang persetujuan Ketentuan Layanan dan Kebijakan Privasi untuk mendaftar.', $redirect_target);
            return;
        }

        if ($password !== $password_confirm) {
            $this->_register_fail($is_ajax, 'Password dan konfirmasi tidak cocok.', $redirect_target);
            return;
        }

        // Password strength
        if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
            $this->_register_fail($is_ajax, 'Password harus minimal 8 karakter, mengandung huruf besar, angka, dan simbol.', $redirect_target);
            return;
        }

        // Verify reCAPTCHA
        if (!empty($this->recaptcha_secret_key)) {
            $recaptcha_response = $this->input->post('g-recaptcha-response');
            if (!$this->_verify_recaptcha($recaptcha_response)) {
                $this->_register_fail($is_ajax, 'Verifikasi Captcha gagal. Silakan coba lagi.', $redirect_target);
                return;
            }
        }

        // Check if email already exists
        $existing = $this->auth_model->find_by_email($email);
        if ($existing) {
            // Sengaja TIDAK menyatakan bahwa emailnya sudah terdaftar: pesan
            // seperti itu menjadikan formulir pendaftaran alat pengecek
            // keanggotaan - siapa pun bisa menguji daftar email untuk tahu
            // siapa saja punya akun di sini. Pemilik akun yang sah tetap
            // terbantu lewat tautan Masuk / Lupa Sandi.
            $this->_register_fail($is_ajax, 'Pendaftaran tidak dapat diproses dengan email tersebut. Kalau Anda sudah punya akun, silakan masuk.', $redirect_target);
            return;
        }

        // Create user
        if ($is_srp2 && $nama_perusahaan === '') {
            $this->_register_fail($is_ajax, 'Nama perusahaan wajib diisi untuk akun pengembang.', 'Pengembang/syarat');
            return;
        }
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $user_id = $this->auth_model->create_user($email, $password_hash);

        if (!$user_id) {
            $this->_register_fail($is_ajax, 'Terjadi kesalahan sistem. Silakan coba lagi.', $redirect_target);
            return;
        }
        // Bukti persetujuan S&K: waktu dan akunnya tercatat di jejak audit.
        $this->catat_audit('persetujuan_sk', 'Menyetujui Ketentuan Layanan dan Kebijakan Privasi saat mendaftar', 'usr_akun', (string) $user_id, ['email' => $email]);

        $pengajuan_id = null;
        $default_name = NULL;
        $default_username = NULL;
        if ($is_srp2) {
            // Daftar cepat SRP2 langsung menyetel profil_lengkap=1 dan tidak
            // pernah melalui onboarding, jadi name/username akan NULL selamanya
            // kalau tidak diisi di sini - roadmap T5 S12-a. Diturunkan dari data
            // yang MEMANG sudah diisi user (email, nama perusahaan), bukan
            // dikarang; pemohon tetap bebas menggantinya di /akun/profil.
            $default_username = $this->auth_model->generate_unique_username(strstr($email, '@', TRUE));
            $default_name = 'Perwakilan ' . strtoupper($nama_perusahaan);

            $this->db->where('id', $user_id)->update('usr_akun', [
                'peran' => 'pengembang',
                'nama_pengguna' => $default_username, 'nama' => $default_name,
                'profil_lengkap' => 1, 'status' => 'active', 'updated_at' => date('Y-m-d H:i:s'),
            ]);
            // Draft dibuat langsung di sini (bukan lewat detour verifikasi-email simulasi)
            // supaya wizard bisa lanjut ke langkah unggah dokumen tanpa pindah halaman.
            // Nama perusahaan disimpan di pengajuan, bukan usr_akun (migrasi 070).
            $pengajuan_id = $this->auth_model->ensure_srp2_draft($user_id);
            $this->auth_model->isi_pengajuan_kosong($pengajuan_id, ['nama_perusahaan' => strtoupper($nama_perusahaan)]);
            $this->session->set_userdata('intended_url', 'akun');
            $this->session->set_userdata('srp2_quick_registration', TRUE);
        }

        // Auto-login the new user
        $session_data = [
            'user_id'   => $user_id,
            'name'      => $default_name,
            'username'  => $default_username,
            'email'     => $email,
            'avatar'    => NULL,
            'role'      => $is_srp2 ? 'pengembang' : NULL,
            'is_logged' => TRUE,
        ];
        $this->session->sess_regenerate(TRUE);
        $session_data['session_auth_token'] = $this->auth_model->issue_session_token($user_id, $this->session->session_id);
        $this->session->set_userdata($session_data);

        if ($is_ajax) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'status'          => 'success',
                'role'            => $session_data['role'],
                // Wizard SRP2 menampilkan "Masuk sebagai <nama>" lewat field ini
                // (syarat.php:506) -- tanpanya kartu "Anda sudah terdaftar" tampil
                // dengan nama kosong tepat sesudah daftar cepat (roadmap T6 R2-sisa).
                'name'            => $default_name,
                'pengajuan_id' => $pengajuan_id,
            ]));
            return;
        }

        if ($is_srp2) {
            redirect('Pengembang/syarat');
            return;
        }

        // Halaman verifikasi email simulasi dihapus (ia menandai email terverifikasi tanpa bukti).
        redirect('Auth/onboarding');
    }

    /**
     * Balas gagal registrasi - JSON kalau request AJAX (dipakai wizard SRP2), flashdata+redirect
     * kalau request halaman biasa (perilaku asli, tidak berubah).
     */
    private function _register_fail($is_ajax, $message, $redirect_target) {
        if ($is_ajax) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'status'  => 'error',
                'message' => $message,
            ]));
            return;
        }
        $this->session->set_flashdata('error', $message);
        redirect($this->sanitize_redirect($redirect_target) ?: 'Auth/register');
    }

    // =========================================================
    // ONBOARDING - Progressive Profiling
    // =========================================================

    /**
     * Display onboarding page
     */
    public function onboarding() {
        if (!$this->is_logged_in()) {
            $this->gerbang_login();
            return;
        }

        // If profile already complete, go to dashboard (atau lanjutkan alur yang tertunda)
        if ($this->auth_model->is_profile_complete($this->get_user_id())) {
            $this->_redirect_after_login();
            return;
        }

        // Check if user needs to set a password (Google users have no password)
        $user = $this->auth_model->find_by_id($this->get_user_id());
        $needs_password = empty($user->kata_sandi);

        $old = $this->session->flashdata('ob_old') ?: [];
        /* Permintaan user 14 Agt 2026: kalau alur ini BERAWAL dari
           pengecekan NIK di /warga/pendataan (ditemukan ATAU tidak - lihat
           Warga::lookup_anonim(), keduanya mengisi `warga_pending_nik`),
           field NIK di formulir onboarding langsung terisi - tidak boleh
           mengetik ulang NIK yang baru saja dicek.
           `ob_old` (isian dari percobaan submit SEBELUMNYA yang gagal
           validasi) MENANG kalau keduanya ada - itu ketikan orangnya
           sendiri barusan, lebih baru daripada NIK dari pengecekan awal.
           TIDAK di-unset di sini dengan sengaja - baris ini cuma membaca
           untuk prefill, Auth::_redirect_after_login() yang jadi pemilik
           tunggal siklus hidupnya (baca lalu unset), supaya kunjungan
           ulang ke halaman onboarding ini (mis. submit gagal sekali lalu
           balik) tetap terprefill, bukan cuma sekali pakai. */
        if (empty($old['nik_identitas'])) {
            $pending_nik = $this->session->userdata('warga_pending_nik');
            if ( ! empty($pending_nik)) {
                $old['nik_identitas'] = $pending_nik;
            }
        }

        $data = [
            'user_email'     => $this->session->userdata('email'),
            'needs_password' => $needs_password,
            'old'            => $old,
        ];
        $this->load->view('pages/auth/onboarding', $data);
    }

    /**
     * Process onboarding form (POST)
     */
    public function save_onboarding() {
        if (!$this->is_logged_in()) {
            $this->gerbang_login();
            return;
        }

        $user_id = $this->get_user_id();
        // Onboarding hanya untuk profil yang belum lengkap. Mengirim ulang formulir ini dari akun
        // yang sudah aktif dulu bisa menimpa NIK yang di Profil Saya dikunci sekali isi.
        if ($this->auth_model->is_profile_complete($user_id)) {
            $this->_redirect_after_login();
            return;
        }
        $role    = html_escape($this->input->post('role'));

        /* Pendaftaran yang dimulai dari cek NIK tidak boleh berakhir pada
           peran lain. Modalnya memang bertuliskan "Buat Akun Warga" dan
           intended_url mengarah ke pendataan; memaksa peran di server
           mencegah pilihan UI atau POST yang dimanipulasi memutus alur itu. */
        if ( ! empty($this->session->userdata('warga_pending_nik'))) {
            $role = 'warga';
        }

        // Validate role
        // 'vendor' DICABUT. Ia bukan sekadar tidak terpakai - kolom yang diisi
        // cabangnya (nama_usaha, alamat_usaha, jenis_usaha) tidak ada di
        // usr_akun, jadi save_profile() pasti gagal di tingkat DB. Siapa pun
        // yang memilih kartu itu tidak pernah mendapat profil, cuma error. Nol
        // baris berperan vendor di production, dan config/roles.php memang
        // sudah tidak mencantumkannya sebagai role resmi.
        $valid_roles = ['warga', 'pengembang', 'mahasiswa'];
        if (!in_array($role, $valid_roles)) {
            $this->_onboarding_fail('Pilih peran yang valid.');
            return;
        }

        // Common fields
        $username  = html_escape($this->input->post('username'));
        $username  = preg_replace('/\s+/', '', strtolower($username)); // Ensure no spaces
        $nama      = html_escape($this->input->post('nama_lengkap'));
        $nik_raw   = preg_replace('/\D+/', '', (string) $this->input->post('nik_identitas', TRUE));
        $npwp_raw  = preg_replace('/\D+/', '', (string) $this->input->post('npwp', TRUE));
        $alamat_raw = html_escape($this->input->post('alamat_domisili'));
        $phone     = html_escape($this->input->post('phone'));

        // NPWP/NIK kosong sengaja TIDAK di sini: cek format per peran di bawah
        // menangkapnya dengan pesan yang menyebut medannya (temuan 27 Sep 2026).
        if (empty($username) || empty($nama) || empty($alamat_raw) || empty($phone)) {
            $this->_onboarding_fail('Semua field wajib harus diisi.');
            return;
        }

        // Handle password for Google users (no existing password)
        $user_record = $this->auth_model->find_by_id($user_id);
        if (empty($user_record->kata_sandi)) {
            $password         = $this->input->post('password');
            $password_confirm  = $this->input->post('password_confirm');

            if (empty($password) || empty($password_confirm)) {
                $this->_onboarding_fail('Password wajib diisi untuk mengamankan akun Anda.');
                return;
            }

            if ($password !== $password_confirm) {
                $this->_onboarding_fail('Password dan konfirmasi tidak cocok.');
                return;
            }

            if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) ||
                !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
                $this->_onboarding_fail('Password harus minimal 8 karakter, mengandung huruf besar, angka, dan simbol.');
                return;
            }

            // Will be added to profile_data below
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
        }

        // Check if username is unique
        $this->db->where('nama_pengguna', $username);
        $this->db->where('id !=', $user_id);
        if ($this->db->count_all_results('usr_akun') > 0) {
            $this->_onboarding_fail('Username sudah digunakan, silakan pilih yang lain.');
            return;
        }

        // Identitas dibedakan menurut peran. Pengembang memakai NPWP perusahaan;
        // NIK hanya diikat ke akun warga/mahasiswa.
        if ($role === 'pengembang') {
            if ( ! preg_match('/^[0-9]{15,16}$/', $npwp_raw)) {
                $this->_onboarding_fail('NPWP harus terdiri dari 15 atau 16 digit angka.');
                return;
            }
            $npwp_hash = $this->encryption_lib->deterministic_hash($npwp_raw);
            $dipakai_pengajuan = $this->db->where('npwp_lookup_hash', $npwp_hash)
                ->where('user_id !=', $user_id)->count_all_results('srp2_pengajuan');
            $dipakai_direktori = $this->db->where('npwp_lookup_hash', $npwp_hash)
                ->count_all_results('srp2_direktori_pengembang');
            if ($dipakai_pengajuan || $dipakai_direktori) {
                $this->_onboarding_fail('NPWP sudah digunakan oleh pengembang lain.');
                return;
            }
            $npwp_encrypted = $this->encryption_lib->encrypt($npwp_raw);
        } elseif ($role === 'warga' && ! preg_match('/^[0-9]{16}$/', $nik_raw)) {
            // Daftar revisi dinas 23 Sep 2026: mahasiswa tidak dimintai NIK (identitasnya NIM
            // di formulir magang). NIK kini hanya untuk warga.
            $this->_onboarding_fail('NIK harus terdiri dari 16 digit angka.');
            return;
        } elseif ($role === 'warga') {
            /* Satu NIK satu akun, aturan yang sama dengan Pengaturan dan
               Housing_assessment_model::save_profile. Dulu tidak dicek di sini:
               onboarding lolos, lalu prefill SIMPERUM gagal diam-diam karena
               nik_already_bound dan warga terus mendarat di layar Cek NIK. */
            $nik_hash = $this->encryption_lib->deterministic_hash($nik_raw);
            if ( ! empty($user_record->nik_lookup_hash) && ! hash_equals((string) $user_record->nik_lookup_hash, $nik_hash)) {
                $this->_onboarding_fail('NIK sudah terkunci pada akun ini dan tidak dapat diubah sendiri. Hubungi admin bila ada kekeliruan.');
                return;
            }
            /* NIK yang sudah terikat ke akun lain tidak lagi menahan onboarding (3 Okt 2026): akun
               disimpan TANPA NIK, NIK-nya mengisi Cek NIK pendataan, dan di sana pemilik yang lolos
               verifikasi nama + tanggal lahir mengambil alih ikatan yang belum terverifikasi
               (Simperum_gateway::verifikasi_pemilik). Pesannya sama untuk ikatan terverifikasi
               atau belum. */
            $nik_terikat_lain = $this->db->where('nik_lookup_hash', $nik_hash)->where('id !=', $user_id)->count_all_results('usr_akun') > 0
                || $this->db->where('nik_lookup_hash', $nik_hash)->where('user_id !=', $user_id)->count_all_results('sf_profil_warga') > 0;
        }

        $alamat_encrypted = $this->encryption_lib->encrypt($alamat_raw);
        $profile_data = [
            'nama_pengguna' => $username,
            'nama' => $nama,
            'peran' => $role,
            'alamat' => $alamat_encrypted,
            'no_hp' => $phone,
            'kategori' => $role,
        ];
        if ($role === 'warga' && empty($nik_terikat_lain)) {
            $profile_data['nik'] = $this->encryption_lib->encrypt($nik_raw);
            $profile_data['nik_lookup_hash'] = $nik_hash;
        }
        // Role-specific fields
        if ($role === 'pengembang') {
            // Divalidasi di SERVER, bukan cuma atribut required di form. Ini hulu
            // KEDUA yang melahirkan pengembang (selain daftar cepat di
            // do_register), dan satu gerbang tidak cukup kalau ada dua hulu:
            // nama kosong di sini akan menjadi pengajuan yang mustahil disetujui
            // di meja admin. Roadmap T1a butir 3.
            $nama_perusahaan_ob = trim((string) $this->input->post('nama_perusahaan'));
            if ($nama_perusahaan_ob === '') {
                $this->_onboarding_fail('Nama perusahaan wajib diisi untuk mendaftar sebagai pengembang.');
                return;
            }
            // Data perusahaan masuk ke pengajuan SRP2 di bawah, bukan usr_akun (migrasi 070).
            // Telepon kantor tidak lagi diminta: nomor akun (phone) sudah diisi di formulir yang sama.
            $perusahaan_ob = [
                'nama_perusahaan' => html_escape($nama_perusahaan_ob),
                'alamat_kantor'   => html_escape((string) $this->input->post('alamat_kantor')),
            ];
        }

        // Save profile
        if (isset($password_hash)) {
            $profile_data['kata_sandi'] = $password_hash;
            $profile_data = array_merge($profile_data, $this->auth_model->password_lifetime_fields());
            // Sandi baru menggantikan sandi yang dicabut saat penautan Google (check_google_user).
            $this->session->unset_userdata('password_change_required');
        }
        $this->auth_model->save_profile($user_id, $profile_data);

        // Jalur onboarding umum dulu TIDAK membuat draft SRP2 sama sekali,
        // sehingga user yang jadi pengembang lewat sini tidak melihat item SRP2
        // apa pun di /akun sampai kebetulan membuka wizard. Sekarang konsisten
        // dengan jalur daftar cepat. Lihat PRD_VERIFIKASI_ADMIN_SRP2.md Fase 2.
        if ($role === 'pengembang') {
            $pengajuan_id = $this->auth_model->ensure_srp2_draft($user_id);
            if ($pengajuan_id) {
                $this->db->where('id', $pengajuan_id)->update('srp2_pengajuan', [
                    'npwp_ciphertext' => $npwp_encrypted,
                    'npwp_lookup_hash' => $npwp_hash,
                ]);
                // Nama dan alamat kantor dari onboarding ke pengajuan (alamat dulu hilang dari alur
                // SRP2, simulasi pengembang 27 Sep 2026); isian pengajuan yang sudah ada tidak ditimpa.
                $this->auth_model->isi_pengajuan_kosong($pengajuan_id, $perusahaan_ob);
            }
        }

        // Handle file uploads
        $this->_handle_uploads($user_id, $role);

        // Update session name
        $this->session->set_userdata('name', $nama);
        $this->session->set_userdata('username', $username);
        $this->session->set_userdata('role', $role);

        /* Prefill SIMPERUM otomatis sesudah onboarding (26 Sep 2026) DICABUT: data sumber baru
           boleh dibuka sesudah nama akun dan tanggal lahir cocok dengan data NIK itu, dan
           tanggal lahir diminta di langkah Cek NIK pendataan (Simperum_gateway::lookup()).
           NIK akun tetap mengisi kolom Cek NIK otomatis. */

        $this->session->set_flashdata('success', 'Profil berhasil disimpan! Selamat datang di Klinik PKP.');
        if ( ! empty($nik_terikat_lain)) {
            $this->load->model('Housing_assessment_model');
            $this->session->set_userdata('warga_pending_nik', $nik_raw);
            $this->session->set_flashdata('warning', Housing_assessment_model::PESAN_NIK_TERIKAT_BUKTIKAN);
        }
        $this->_redirect_after_login();
    }

    /**
     * Gagalkan onboarding TANPA membuang isian user.
     *
     * Sebelumnya tiap cabang validasi memanggil set_flashdata('error') +
     * redirect sendiri-sendiri, jadi satu digit NIK yang keliru memulangkan
     * pengembang ke formulir kosong - 12 isian dan 2 unggahan hilang. Semua
     * cabang sekarang lewat sini supaya isian ikut pulang bersama pesannya.
     *
     * Password TIDAK ikut disimpan: flashdata menumpang session, dan kata sandi
     * polos tidak boleh singgah di sana meski cuma satu permintaan.
     * Berkas juga tidak bisa dikembalikan - HTML melarang mengisi input file
     * dari server, jadi formulir menyebutkannya terus terang ke user.
     */
    private function _onboarding_fail($pesan) {
        $old = $this->input->post();
        unset($old['password'], $old['password_confirm'],
              $old[$this->security->get_csrf_token_name()]);

        $this->session->set_flashdata('ob_old', $old);
        $this->session->set_flashdata('error', $pesan);
        redirect('Auth/onboarding');
    }

    // =========================================================
    // FORGOT PASSWORD (Placeholder)
    // =========================================================

    public function forgot_password() {
        $this->load->view('pages/auth/forgot_password');
    }

    // Verifikasi email simulasi (verify_pending + do_verify_email) DIHAPUS: ia menandai
    // email_verified_at tanpa bukti kepemilikan. Satu-satunya penanda yang tersisa adalah
    // bukti sungguhan: login Google (email terverifikasi Google), token verify_email(), atau
    // akun yang dibuat admin.

    public function lanjutkan() {
        if (!$this->is_logged_in()) { $this->gerbang_login(); return; }
        if ($this->session->userdata('srp2_quick_registration') === TRUE) {
            // Jalur ini memang cuma peduli draft yang BELUM dikirim - kalau
            // pengajuannya sudah Pending/Diterima, buat draft baru itu keliru.
            $draft_id = $this->auth_model->ensure_srp2_draft($this->get_user_id(), 'Draft');
            $this->session->unset_userdata('srp2_quick_registration');
            $this->session->unset_userdata('srp2_verify_pending');
            $this->session->unset_userdata('intended_url');
            // Ke WIZARD, bukan halaman unggah terpisah. Redirect lama adalah
            // satu-satunya yang masih menyeret pemohon KELUAR dari wizard di
            // tengah alur - sisa era sebelum wizard yang lupa diperbarui.
            redirect('Pengembang/syarat');
            return;
        }
        $this->_redirect_after_login();
    }

    // =========================================================
    // EMAIL VERIFICATION (Token-based - future implementation)
    // =========================================================

    public function verify_email($token = '') {
        if (empty($token)) {
            show_404();
            return;
        }

        $user = $this->auth_model->verify_email_token($token);
        if ($user) {
            $this->session->set_flashdata('success', 'Email berhasil diverifikasi! Silakan login.');
        } else {
            $this->session->set_flashdata('error', 'Tautan verifikasi tidak valid atau sudah kedaluwarsa.');
        }
        $this->gerbang_login();
    }

    // =========================================================
    // GOOGLE OAuth (preserved from original)
    // =========================================================

    /**
     * Endpoint: base_url('auth/google') -> Redirect to Google Login
     */
    public function google() {
        $from = $this->input->get('from');
        $safe_redirect = $this->sanitize_redirect($from);

        $state = bin2hex(random_bytes(16));
        $this->session->set_userdata('oauth_state', $state);
        if ($safe_redirect !== '') {
            $this->session->set_userdata('intended_url', $safe_redirect);
        }

        $this->google_client->setState($state);
        $login_url = $this->google_client->createAuthUrl();
        redirect($login_url);
    }

    /**
     * Endpoint: base_url('auth/google_callback') -> Handle Google response
     */
    public function google_callback() {
        // Validate state token (Anti-CSRF OAuth)
        $state_from_google  = $this->input->get('state');
        $state_from_session = $this->session->userdata('oauth_state');
        $this->session->unset_userdata('oauth_state');

        if (empty($state_from_google) || empty($state_from_session) ||
            !hash_equals($state_from_session, $state_from_google)) {
            redirect('Auth/login');
        }

        if ($this->input->get('code')) {
            try {
                $token = $this->google_client->fetchAccessTokenWithAuthCode($this->input->get('code'));

                if (!isset($token['error'])) {
                    $this->google_client->setAccessToken($token['access_token']);

                    $google_oauth = new Google\Service\Oauth2($this->google_client);
                    $google_data  = $google_oauth->userinfo->get();

                    $user_data = [
                        'google_id' => $google_data['id'],
                        'nama'      => $google_data['name'],
                        'email'     => $google_data['email'],
                        'foto_profil' => $google_data['picture'],
                    ];

                    $logged_in_user = $this->user_model->check_google_user($user_data, $google_data->verifiedEmail === TRUE);
                    if ( ! $logged_in_user) {
                        // Email belum diverifikasi Google, atau sudah tertaut ke akun Google lain.
                        $this->session->set_flashdata('error', 'Masuk dengan Google tidak dapat diproses untuk email ini. Silakan masuk dengan email dan kata sandi, atau hubungi admin.');
                        redirect('Auth/login');
                    }

                    if ($logged_in_user) {
                        /**
                         * GERBANG STATUS - kembaran dari yang ada di do_login().
                         *
                         * Tanpa ini, tombol "Nonaktifkan" di Akses Staf tidak
                         * menutup apa pun bagi siapa saja yang emailnya juga
                         * akun Google: `check_google_user()` mencocokkan lewat
                         * EMAIL (bukan google_id) dan mengembalikan barisnya apa
                         * adanya, berapa pun statusnya. Orang yang baru dicabut
                         * aksesnya tinggal mengeklik "Masuk dengan Google" dan
                         * kembali dengan role serta scope lengkap - sementara
                         * layar Akses Staf dan jejak audit sama-sama melaporkan
                         * pencabutan itu berhasil.
                         *
                         * Ditemukan lewat tinjauan adversarial 3 Agt 2026.
                         * Pelajaran umumnya: gerbang yang dipasang di satu titik
                         * masuk bukan gerbang - ia harus dipasang di SEMUA titik
                         * yang membuat sesi. Di berkas ini ada tiga (do_login,
                         * do_register, google_callback).
                         */
                        if (strtolower(trim((string) ($logged_in_user[0]['status'] ?? ''))) === 'nonaktif') {
                            $this->session->set_flashdata('error',
                                'Akun ini dinonaktifkan. Hubungi Super Admin bila menurut Anda ini keliru.');
                            $this->gerbang_login();
                            return;
                        }

                        // email_verified_at dan pencabutan sandi lama sudah ditangani check_google_user().
                        $session_data = [
                            'user_id'      => $logged_in_user[0]['id'],
                            'name'         => $logged_in_user[0]['nama'],
                            'username'     => $logged_in_user[0]['nama_pengguna'] ?? '',
                            'email'        => $logged_in_user[0]['email'],
                            'avatar'       => $logged_in_user[0]['foto_profil'],
                            'role'         => $logged_in_user[0]['peran'] ?? null, // Added role to session
                            'kabupaten_id' => $logged_in_user[0]['kabupaten_id'] ?? null,
                            'bidang_kode'  => $logged_in_user[0]['bidang_kode'] ?? null,
                            'is_logged'    => TRUE,
                        ];
                        $this->session->sess_regenerate(TRUE);
                        $session_data['session_auth_token'] = $this->auth_model->issue_session_token($logged_in_user[0]['id'], $this->session->session_id);
                        $this->session->set_userdata($session_data);

                        $user_record = $this->auth_model->find_by_id($logged_in_user[0]['id']);

                        // Jalur login ketiga (Google OAuth) juga membuat sesi
                        // pengembang, jadi drafnya harus dipastikan ada di sini
                        // juga - kalau tidak, item SRP2 hilang dari /akun cuma
                        // karena user memilih masuk lewat Google.
                        if ($user_record && $user_record->peran === 'pengembang') {
                            $this->auth_model->ensure_srp2_draft($user_record->id);
                        }

                        // Onboarding, wajib ganti sandi, dan halaman asal (intended_url) ditangani di satu tempat.
                        $this->_redirect_after_login();
                        return;
                    }
                }
            } catch (Exception $e) {
                log_message('error', 'google_callback gagal: ' . get_class($e));
            }
        }

        // Warga menekan Batal, kode ditolak Google, atau pustaka melempar galat.
        $this->session->set_flashdata('error', 'Masuk dengan Google tidak berhasil. Silakan coba lagi.');
        redirect('Auth/login');
    }

    // =========================================================
    // LOGOUT
    // =========================================================

    /**
     * Layar "akses ditolak" - dipanggil `MY_Controller::gerbang_login()` saat
     * orangnya SUDAH masuk tetapi perannya tidak berhak.
     *
     * Dibuat terpisah dari halaman masuk dengan sengaja. Melempar orang yang
     * sudah login ke halaman login adalah jawaban yang salah untuk pertanyaan
     * yang salah: sesinya baik-baik saja, yang tidak cocok perannya. Layar ini
     * mengatakan itu apa adanya, lalu memberi tiga jalan keluar supaya tidak
     * ada yang merasa mentok.
     */
    public function akses_ditolak() {
        if ( ! $this->session->userdata('is_logged')) {
            redirect('Auth/login');
            return;
        }

        $tujuan = (string) $this->session->userdata('akses_ditolak_tujuan');
        $this->session->unset_userdata('akses_ditolak_tujuan');

        $this->render('pages/auth/akses_ditolak', [
            'judul'         => 'Halaman ini bukan untuk peran Anda',
            'pesan'         => (string) $this->session->flashdata('error'),
            'tujuan'        => $this->sanitize_redirect($tujuan),
            'peran'         => (string) $this->current_role(),
            'tujuan_pulang' => $this->tujuan_setelah_login(),
        ]);
    }

    public function logout() {
        $curr = $this->input->get('curr', TRUE);
        $safe_redirect = $this->sanitize_redirect($curr);
        $this->auth_model->revoke_session_token((int) $this->session->userdata('user_id'),
            (string) $this->session->userdata('session_auth_token'));
        $this->session->sess_destroy();
        redirect(!empty($safe_redirect) ? $safe_redirect : 'login');
    }

    // =========================================================
    // LEGACY - reg_user (backward compat, redirects to onboarding)
    // =========================================================

    public function reg_user($id = null) {
        redirect('Auth/onboarding');
    }

    // =========================================================
    // LEGACY - update (backward compat)
    // =========================================================

    public function update() {
        if (!$this->is_logged_in()) {
            show_error('Anda harus login terlebih dahulu.', 403);
            return;
        }

        // Redirect to new onboarding
        redirect('Auth/onboarding');
    }

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    /**
     * Redirect user after login based on profile completion status
     */
    private function _redirect_after_login() {
        $user_id = $this->get_user_id();
        if ($this->session->userdata('password_change_required')) {
            $this->session->set_flashdata('error', $this->auth_model->pesan_ganti_sandi($this->auth_model->find_by_id($user_id)));
            redirect('akun/profil?password_expired=1');
            return;
        }

        /* NIK yang sempat dicek ANONIM (Warga::lookup_anonim()) TIDAK lagi diikat otomatis ke akun
           saat login/daftar: data SIMPERUM baru boleh terbuka sesudah nama akun dan tanggal lahir
           cocok (Simperum_gateway::lookup()), dan tanggal lahir diminta di langkah Cek NIK.
           `warga_pending_nik` tetap tinggal di sesi untuk mengisi kolom NIK onboarding dan Cek NIK;
           Warga::lookup() melepasnya sesudah verifikasi berhasil.

           Yang tetap otomatis: NIK yang TIDAK ADA di SIMPERUM. Sesudah onboarding selesai, draft
           manual dibuat dari NIK dan nama akun (tanpa data sumber apa pun; NIK tercatat belum
           terverifikasi) supaya warga tidak dipaksa mencari NIK yang sama sekali lagi. Pencarian
           di sini tanpa akun peminta (NULL), jadi tidak mengikat dan tidak membuka data. */
        $pending_nik = $this->session->userdata('warga_pending_nik');
        if ( ! empty($pending_nik) && $this->has_role('warga') && $this->auth_model->is_profile_complete($user_id)) {
            $this->load->library('Simperum_gateway');
            $hasil = $this->simperum_gateway->lookup($pending_nik, '', NULL, TRUE);
            if (($hasil['status'] ?? '') === 'not_found') {
                $this->load->model('Housing_assessment_model');
                $user = $this->auth_model->find_by_id($user_id);
                $manual = $this->Housing_assessment_model->bootstrap_manual_draft(
                    $user_id,
                    $pending_nik,
                    trim((string) ($user->nama ?? ''))
                );
                if ( ! empty($manual['success'])) {
                    $this->session->unset_userdata('warga_pending_nik');
                }
            }
        }

        if (!$this->auth_model->is_profile_complete($user_id)) {
            redirect('Auth/onboarding');
            return;
        }

        /* Alamat tujuan yang tersimpan. Sumbernya sesi - yang kita isi sendiri
           di sisi server lewat `MY_Controller::ingat_halaman_asal()` - jadi
           secara asal-usul sudah aman.
           Tetap disaring LAGI di sini, dan itu disengaja: yang menyimpan dan
           yang memakai ada di berkas berbeda, dan penyaringan yang cuma ada di
           sisi penyimpan akan hilang tanpa suara begitu ada jalur penyimpan
           kedua. Dibuang kalau tidak lolos, bukan dipakai apa adanya. */
        $intended = $this->session->userdata('intended_url');
        if ( ! empty($intended)) {
            $this->session->unset_userdata('intended_url');
            $aman = $this->sanitize_redirect($intended);
            if ($aman !== '') {
                redirect($aman);
                return;
            }
        }

        /* Butir 24 putaran 2, pilihan (a): warga/pengembang/mahasiswa mendarat
           di BERANDA, bukan dashboard. Alasan lengkapnya di
           `MY_Controller::tujuan_setelah_login()`. */
        redirect($this->tujuan_setelah_login());
    }

    /**
     * Verify reCAPTCHA response with Google
     */
    private function _verify_recaptcha($response) {
        if (empty($response)) return FALSE;

        /* Konteks stream dari transport_helper: sertifikat dan nama host
           diverifikasi, TLS 1.2/1.3 saja, batas waktu 10 detik (dulu tanpa
           konteks sama sekali - tanpa batas waktu, jadi Google yang lambat
           menahan worker PHP tanpa akhir). Gagal kirim tetap berarti FALSE. */
        $verify = @file_get_contents('https://www.google.com/recaptcha/api/siteverify?' . http_build_query([
            'secret'   => $this->recaptcha_secret_key,
            'response' => $response,
            'remoteip' => $this->input->ip_address(),
        ]), FALSE, transport_stream_context());
        if ($verify === FALSE) return FALSE;

        $result = json_decode($verify, TRUE);
        return isset($result['success']) && $result['success'] === TRUE;
    }

    /**
     * Handle file uploads for onboarding
     */
    /**
     * Simpan dokumen identitas onboarding (KTP, SIUP, KTM, surat magang).
     *
     * Sebelumnya disimpan di FCPATH.'uploads/documents/' - DI DALAM webroot,
     * jadi scan KTP bisa diakses lewat HTTP kalau nama filenya bocor. Nama acak
     * bukan kontrol akses, apalagi untuk dokumen kependudukan yang masuk
     * cakupan UU PDP. Sekarang lewat store_private_upload() ke
     * private_uploads/onboarding/{user_id}/ di luar webroot.
     *
     * CATATAN: saat ini TIDAK ADA UI yang menampilkan kembali dokumen ini
     * (Auth_model::get_user_documents() tidak pernah dipanggil di mana pun),
     * jadi belum dibuatkan endpoint baca. Kalau nanti dibutuhkan, buat endpoint
     * ber-guard yang memakai serve_private_file() - JANGAN kembalikan ke
     * direktori publik.
     */
    private function _handle_uploads($user_id, $role) {
        $upload_fields = [];
        if ($role === 'pengembang') {
            $upload_fields = ['file_ktp' => 'ktp', 'file_siup' => 'siup_nib'];
        } elseif ($role === 'mahasiswa') {
            $upload_fields = ['file_ktm' => 'ktm', 'file_surat_magang' => 'surat_magang'];
        }

        foreach ($upload_fields as $field_name => $jenis_dokumen) {
            $ukuran = isset($_FILES[$field_name]['size']) ? (int) $_FILES[$field_name]['size'] : 0;
            $nama_simpan = $this->store_private_upload($field_name, 'onboarding', $user_id);
            if ($nama_simpan) {
                $this->auth_model->save_document(
                    $user_id,
                    $jenis_dokumen,
                    $nama_simpan,
                    'private_uploads/onboarding/' . $user_id . '/' . $nama_simpan,
                    $ukuran
                );
            }
        }
    }
}
