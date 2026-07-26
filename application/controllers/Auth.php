<?php
defined('BASEPATH') || exit('No direct script access allowed');

class Auth extends MY_Controller {

    protected $google_client;

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
    // LOGIN — Email/Password
    // =========================================================

    /**
     * Display login page
     */
    public function login() {
        // Simpan tujuan lanjutan setelah login (mis. link "Sudah punya akun?"
        // dari alur pendaftaran SRP2) — dibaca lewat ?next=, divalidasi anti-open-redirect.
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
        $this->load->view('pages/auth/login', $data);
    }

    /**
     * Process login form (POST)
     */
    public function do_login() {
        $login_id = trim($this->input->post('email', TRUE));
        $password = $this->input->post('password');
        $is_ajax  = $this->input->is_ajax_request();

        // Kalau form login ini ditanam di halaman lain (mis. wizard SRP2 Pengembang/syarat),
        // form itu kirim hidden field 'redirect_to' supaya kalau gagal (jalur non-AJAX), user
        // tetap di halaman asalnya — bukan terlempar ke Auth/login umum. Divalidasi anti-open-redirect.
        $error_target = $this->sanitize_redirect($this->input->post('redirect_to', TRUE)) ?: 'Auth/login';

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

        // Find user
        $user = $this->auth_model->find_by_login($login_id);

        if (!$user || empty($user->password)) {
            // User not found or no password (Google-only user)
            $this->_login_fail($is_ajax, 'Akun tidak ditemukan atau password salah.', $error_target);
            return;
        }

        // Check lockout
        if ($this->auth_model->is_locked($user)) {
            $remaining = ceil($this->auth_model->lockout_remaining($user) / 60);
            $this->_login_fail($is_ajax, "Akun terkunci sementara. Coba lagi dalam {$remaining} menit.", $error_target);
            return;
        }

        // Verify password
        if (!password_verify($password, $user->password)) {
            $this->auth_model->increment_login_attempts($user->id);
            $attempts_left = Auth_model::MAX_LOGIN_ATTEMPTS - ($user->login_attempts + 1);
            $message = $attempts_left > 0
                ? "Email atau password salah. Sisa {$attempts_left} percobaan."
                : 'Akun terkunci selama 15 menit karena terlalu banyak percobaan gagal.';
            $this->_login_fail($is_ajax, $message, $error_target);
            return;
        }

        // Success — reset attempts and create session
        $this->auth_model->reset_login_attempts($user->id);

        $session_data = [
            'user_id'      => $user->id,
            'name'         => $user->name,
            'username'     => $user->username ?? '',
            'email'        => $user->email,
            'avatar'       => $user->avatar,
            'role'         => $user->role, // Added role to session
            'kabupaten_id' => $user->kabupaten_id ?? null, // scope untuk role admin_kabkota
            'bidang_kode'  => $user->bidang_kode ?? null, // scope untuk role admin_bidang
            'is_logged'    => TRUE,
        ];
        $this->session->set_userdata($session_data);
        $this->session->sess_regenerate(TRUE);

        // Draft SRP2 dipastikan ada untuk SEMUA jalur login — bukan cuma cabang
        // AJAX. Dulu pemanggilan ini ada DI DALAM `if ($is_ajax)`, sehingga
        // pengembang yang masuk lewat halaman login utama (`Auth/login`) tidak
        // punya baris draft, dan `Pengaturan::index()` yang menjaga dengan
        // `if ($sp2)` membuat item SRP2-nya tidak muncul sama sekali di /akun.
        // Hasilnya: fitur yang sama terlihat ada atau tidak ada, tergantung
        // lewat pintu mana user masuk.
        $srp2 = ($user->role === 'pengembang')
            ? $this->auth_model->srp2_state($user->id)
            : NULL;
        $registration_id = $srp2['registration_id'] ?? NULL;

        // Wizard (mis. SRP2 di Pengembang/syarat) cuma butuh konfirmasi + role, bukan redirect —
        // wizard yang urus lanjutannya sendiri di sisi klien, tanpa pindah halaman.
        if ($is_ajax) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'status'          => 'success',
                'role'            => $user->role,
                'name'            => $user->name,
                'registration_id' => $registration_id,
                // Keadaan pengajuan ikut dikirim supaya wizard tidak menampilkan
                // keadaan tamu (0/14 dokumen, catatan admin hilang) untuk
                // pengembang lama yang baru saja masuk lewat wizard.
                'srp2'            => $srp2,
                // Dipakai wizard SRP2 saat akun yang login ternyata bukan
                // pengembang: kartu salah-role butuh tujuan dashboard yang benar
                // untuk role INI, bukan tautan hardcode ke `akun`.
                'dashboard_url'   => $this->dashboard_home($user->role),
                'is_pengelola'    => in_array($user->role, ['admin', 'admin_kabkota', 'admin_bidang'], TRUE),
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
     * Balas gagal login — JSON kalau request AJAX (dipakai wizard SRP2), flashdata+redirect
     * kalau request halaman biasa (perilaku asli, tidak berubah).
     */
    private function _login_fail($is_ajax, $message, $error_target) {
        if ($is_ajax) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'status'  => 'error',
                'message' => $message,
            ]));
            return;
        }
        $this->session->set_flashdata('error', $message);
        redirect($error_target);
    }

    // =========================================================
    // REGISTRATION — Step 1 (Email + Password)
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
        // Wizard SRP2 sekarang tinggal di Pengembang/syarat — bukan lagi halaman
        // Pengembang/daftar terpisah (diarsipkan, cuma jadi redirect ke sini).
        $redirect_target  = $is_srp2 ? 'Pengembang/syarat' : 'Auth/register';

        // Validation
        if (empty($email) || empty($password) || empty($password_confirm)) {
            $this->_register_fail($is_ajax, 'Semua field wajib diisi.', $redirect_target);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->_register_fail($is_ajax, 'Format email tidak valid.', $redirect_target);
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
            $this->_register_fail($is_ajax, 'Email sudah terdaftar. Silakan login atau gunakan email lain.', $redirect_target);
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

        $registration_id = null;
        if ($is_srp2) {
            $this->db->where('id', $user_id)->update('usr_users', [
                'role' => 'pengembang', 'nama_perusahaan' => strtoupper($nama_perusahaan),
                'profile_completed' => 1, 'status' => 'active', 'updated_at' => date('Y-m-d H:i:s'),
            ]);
            // Draft dibuat langsung di sini (bukan lewat detour verifikasi-email simulasi)
            // supaya wizard bisa lanjut ke langkah unggah dokumen tanpa pindah halaman.
            // Dipanggil SETELAH usr_users di-update supaya nama_perusahaan yang
            // baru tersimpan ikut terbawa ke draft.
            $registration_id = $this->auth_model->ensure_srp2_draft($user_id);
            $this->session->set_userdata('intended_url', 'akun');
            $this->session->set_userdata('srp2_quick_registration', TRUE);
        }

        // Auto-login the new user
        $session_data = [
            'user_id'   => $user_id,
            'name'      => NULL,
            'username'  => NULL,
            'email'     => $email,
            'avatar'    => NULL,
            'role'      => $is_srp2 ? 'pengembang' : NULL,
            'is_logged' => TRUE,
        ];
        $this->session->set_userdata($session_data);
        $this->session->sess_regenerate(TRUE);

        if ($is_ajax) {
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'status'          => 'success',
                'role'            => $session_data['role'],
                'registration_id' => $registration_id,
            ]));
            return;
        }

        if ($is_srp2) {
            redirect('Pengembang/syarat');
            return;
        }

        // Redirect to dummy email verification page
        redirect('Auth/verify_pending');
    }

    /**
     * Balas gagal registrasi — JSON kalau request AJAX (dipakai wizard SRP2), flashdata+redirect
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
        redirect($redirect_target);
    }

    // =========================================================
    // ONBOARDING — Progressive Profiling
    // =========================================================

    /**
     * Display onboarding page
     */
    public function onboarding() {
        if (!$this->is_logged_in()) {
            redirect('Auth/login');
            return;
        }

        // If profile already complete, go to dashboard (atau lanjutkan alur yang tertunda)
        if ($this->auth_model->is_profile_complete($this->get_user_id())) {
            $this->_redirect_after_login();
            return;
        }

        // Check if user needs to set a password (Google users have no password)
        $user = $this->auth_model->find_by_id($this->get_user_id());
        $needs_password = empty($user->password);

        $data = [
            'user_email'     => $this->session->userdata('email'),
            'needs_password' => $needs_password,
        ];
        $this->load->view('pages/auth/onboarding', $data);
    }

    /**
     * Process onboarding form (POST)
     */
    public function save_onboarding() {
        if (!$this->is_logged_in()) {
            redirect('Auth/login');
            return;
        }

        $user_id = $this->get_user_id();
        $role    = html_escape($this->input->post('role'));

        // Validate role
        $valid_roles = ['warga', 'pengembang', 'vendor', 'mahasiswa'];
        if (!in_array($role, $valid_roles)) {
            $this->session->set_flashdata('error', 'Pilih peran yang valid.');
            redirect('Auth/onboarding');
            return;
        }

        // Common fields
        $username  = html_escape($this->input->post('username'));
        $username  = preg_replace('/\s+/', '', strtolower($username)); // Ensure no spaces
        $nama      = html_escape($this->input->post('nama_lengkap'));
        $nik_raw   = html_escape($this->input->post('nik_identitas'));
        $alamat_raw = html_escape($this->input->post('alamat_domisili'));
        $phone     = html_escape($this->input->post('phone'));

        if (empty($username) || empty($nama) || empty($nik_raw) || empty($alamat_raw) || empty($phone)) {
            $this->session->set_flashdata('error', 'Semua field wajib harus diisi.');
            redirect('Auth/onboarding');
            return;
        }

        // Handle password for Google users (no existing password)
        $user_record = $this->auth_model->find_by_id($user_id);
        if (empty($user_record->password)) {
            $password         = $this->input->post('password');
            $password_confirm  = $this->input->post('password_confirm');

            if (empty($password) || empty($password_confirm)) {
                $this->session->set_flashdata('error', 'Password wajib diisi untuk mengamankan akun Anda.');
                redirect('Auth/onboarding');
                return;
            }

            if ($password !== $password_confirm) {
                $this->session->set_flashdata('error', 'Password dan konfirmasi tidak cocok.');
                redirect('Auth/onboarding');
                return;
            }

            if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) ||
                !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
                $this->session->set_flashdata('error', 'Password harus minimal 8 karakter, mengandung huruf besar, angka, dan simbol.');
                redirect('Auth/onboarding');
                return;
            }

            // Will be added to profile_data below
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
        }

        // Check if username is unique
        $this->db->where('username', $username);
        $this->db->where('id !=', $user_id);
        if ($this->db->count_all_results('usr_users') > 0) {
            $this->session->set_flashdata('error', 'Username sudah digunakan, silakan pilih yang lain.');
            redirect('Auth/onboarding');
            return;
        }

        // NIK validation (16 digits)
        if (!preg_match('/^[0-9]{16}$/', $nik_raw)) {
            $this->session->set_flashdata('error', 'NIK harus terdiri dari 16 digit angka.');
            redirect('Auth/onboarding');
            return;
        }

        // Encrypt PII data
        $nik_encrypted    = $this->encryption_lib->encrypt($nik_raw);
        $alamat_encrypted = $this->encryption_lib->encrypt($alamat_raw);
        $nik_hash         = $this->encryption_lib->deterministic_hash($nik_raw);

        $profile_data = [
            'username'        => $username,
            'name'            => $nama,
            'role'            => $role,
            'nik'             => $nik_encrypted,
            'nik_lookup_hash' => $nik_hash,
            'alamat'          => $alamat_encrypted,
            'phone'           => $phone,
            'kategori'        => $role, // Map to existing kategori field
        ];

        // Role-specific fields
        if ($role === 'pengembang') {
            $profile_data['nama_perusahaan'] = html_escape($this->input->post('nama_perusahaan'));
            $profile_data['alamat_kantor']   = html_escape($this->input->post('alamat_kantor'));
            $profile_data['telp_kantor']     = html_escape($this->input->post('telp_kantor'));
        } elseif ($role === 'vendor') {
            $profile_data['nama_usaha']  = html_escape($this->input->post('nama_usaha'));
            $profile_data['alamat_usaha'] = html_escape($this->input->post('alamat_usaha'));
            $profile_data['jenis_usaha'] = html_escape($this->input->post('jenis_usaha'));
        }

        // Save profile
        if (isset($password_hash)) {
            $profile_data['password'] = $password_hash;
        }
        $this->auth_model->save_profile($user_id, $profile_data);

        // Jalur onboarding umum dulu TIDAK membuat draft SRP2 sama sekali,
        // sehingga user yang jadi pengembang lewat sini tidak melihat item SRP2
        // apa pun di /akun sampai kebetulan membuka wizard. Sekarang konsisten
        // dengan jalur daftar cepat. Lihat PRD_VERIFIKASI_ADMIN_SRP2.md Fase 2.
        if ($role === 'pengembang') {
            $this->auth_model->ensure_srp2_draft($user_id);
        }

        // Handle file uploads
        $this->_handle_uploads($user_id, $role);

        // Update session name
        $this->session->set_userdata('name', $nama);
        $this->session->set_userdata('username', $username);
        $this->session->set_userdata('role', $role);

        $this->session->set_flashdata('success', 'Profil berhasil disimpan! Selamat datang di Klinik PKP.');
        $this->_redirect_after_login();
    }

    // =========================================================
    // FORGOT PASSWORD (Placeholder)
    // =========================================================

    public function forgot_password() {
        $this->load->view('pages/auth/forgot_password');
    }

    // =========================================================
    // DUMMY EMAIL VERIFICATION
    // =========================================================

    /**
     * Show pending verification page (dummy — auto-verifies after countdown)
     */
    public function verify_pending() {
        if (!$this->is_logged_in()) {
            redirect('Auth/login');
            return;
        }

        $data = [
            'user_email' => $this->session->userdata('email'),
        ];
        $this->load->view('pages/auth/verify_pending', $data);
    }

    /**
     * AJAX endpoint — simulate email verification
     */
    public function do_verify_email() {
        if (!$this->is_logged_in()) {
            echo json_encode(['status' => 'error']);
            return;
        }

        $user_id = $this->get_user_id();
        $this->db->where('id', $user_id);
        $this->db->update('usr_users', [
            'email_verified_at' => date('Y-m-d H:i:s'),
        ]);

        echo json_encode(['status' => 'ok']);
    }

    public function lanjutkan() {
        if (!$this->is_logged_in()) { redirect('Auth/login'); return; }
        if ($this->session->userdata('srp2_quick_registration') === TRUE) {
            // Jalur ini memang cuma peduli draft yang BELUM dikirim — kalau
            // pengajuannya sudah Pending/Diterima, buat draft baru itu keliru.
            $draft_id = $this->auth_model->ensure_srp2_draft($this->get_user_id(), 'Draft');
            $this->session->unset_userdata('srp2_quick_registration');
            $this->session->unset_userdata('srp2_verify_pending');
            $this->session->unset_userdata('intended_url');
            redirect('Pengembang/dokumen/' . $draft_id);
            return;
        }
        $this->_redirect_after_login();
    }

    // =========================================================
    // EMAIL VERIFICATION (Token-based — future implementation)
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
        redirect('Auth/login');
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
        $this->session->set_userdata('oauth_redirect', $safe_redirect);

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
            echo "<script>
                window.opener.location.href = '" . base_url('login') . "';
                window.close();
            </script>";
            exit;
        }

        $redirect_to = $this->session->userdata('oauth_redirect');
        $this->session->unset_userdata('oauth_redirect');

        if (empty($redirect_to)) {
            $redirect_to = '';
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
                        'name'      => $google_data['name'],
                        'email'     => $google_data['email'],
                        'avatar'    => $google_data['picture'],
                    ];

                    $logged_in_user = $this->user_model->check_google_user($user_data);

                    if ($logged_in_user) {
                        // Mark Google users as email-verified
                        $this->db->where('id', $logged_in_user[0]['id']);
                        $this->db->update('usr_users', [
                            'email_verified_at' => date('Y-m-d H:i:s'),
                        ]);

                        $session_data = [
                            'user_id'      => $logged_in_user[0]['id'],
                            'name'         => $logged_in_user[0]['name'],
                            'username'     => $logged_in_user[0]['username'] ?? '',
                            'email'        => $logged_in_user[0]['email'],
                            'avatar'       => $logged_in_user[0]['avatar'],
                            'role'         => $logged_in_user[0]['role'] ?? null, // Added role to session
                            'kabupaten_id' => $logged_in_user[0]['kabupaten_id'] ?? null,
                            'bidang_kode'  => $logged_in_user[0]['bidang_kode'] ?? null,
                            'is_logged'    => TRUE,
                        ];
                        $this->session->set_userdata($session_data);
                        $this->session->sess_regenerate(TRUE);

                        // Check if profile is complete — redirect to new onboarding if not
                        $user_record = $this->auth_model->find_by_id($logged_in_user[0]['id']);
                        if ($user_record && $user_record->profile_completed == 0) {
                            $redirect_to = 'onboarding';
                        }

                        // Jalur login ketiga (Google OAuth) juga membuat sesi
                        // pengembang, jadi drafnya harus dipastikan ada di sini
                        // juga — kalau tidak, item SRP2 hilang dari /akun cuma
                        // karena user memilih masuk lewat Google.
                        if ($user_record && $user_record->role === 'pengembang') {
                            $this->auth_model->ensure_srp2_draft($user_record->id);
                        }

                        echo "
                        <!DOCTYPE html>
                        <html>
                        <head><title>Mengarahkan...</title></head>
                        <body>
                            <script>
                                window.opener.location.href = '" . base_url($redirect_to) . "';
                                window.close();
                            </script>
                        </body>
                        </html>";
                        exit;
                    }
                }
            } catch (Exception $e) {
                echo "<script>
                    window.opener.location.href = '" . base_url('Auth/login?status=error') . "';
                    window.close();
                </script>";
                exit;
            }
        }

        echo "<script>window.close();</script>";
        exit;
    }

    // =========================================================
    // LOGOUT
    // =========================================================

    public function logout() {
        $curr = $this->input->get('curr', TRUE);
        $safe_redirect = $this->sanitize_redirect($curr);
        $this->session->sess_destroy();
        redirect(!empty($safe_redirect) ? $safe_redirect : 'login');
    }

    // =========================================================
    // LEGACY — reg_user (backward compat, redirects to onboarding)
    // =========================================================

    public function reg_user($id = null) {
        redirect('Auth/onboarding');
    }

    // =========================================================
    // LEGACY — update (backward compat)
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

        if (!$this->auth_model->is_profile_complete($user_id)) {
            redirect('Auth/onboarding');
            return;
        }

        // Check for intended URL
        $intended = $this->session->userdata('intended_url');
        if (!empty($intended)) {
            $this->session->unset_userdata('intended_url');
            redirect($intended);
            return;
        }

        redirect('');
    }

    /**
     * Verify reCAPTCHA response with Google
     */
    private function _verify_recaptcha($response) {
        if (empty($response)) return FALSE;

        $verify = file_get_contents('https://www.google.com/recaptcha/api/siteverify?' . http_build_query([
            'secret'   => $this->recaptcha_secret_key,
            'response' => $response,
            'remoteip' => $this->input->ip_address(),
        ]));

        $result = json_decode($verify, TRUE);
        return isset($result['success']) && $result['success'] === TRUE;
    }

    /**
     * Handle file uploads for onboarding
     */
    /**
     * Simpan dokumen identitas onboarding (KTP, SIUP, KTM, surat magang).
     *
     * Sebelumnya disimpan di FCPATH.'uploads/documents/' — DI DALAM webroot,
     * jadi scan KTP bisa diakses lewat HTTP kalau nama filenya bocor. Nama acak
     * bukan kontrol akses, apalagi untuk dokumen kependudukan yang masuk
     * cakupan UU PDP. Sekarang lewat store_private_upload() ke
     * private_uploads/onboarding/{user_id}/ di luar webroot.
     *
     * CATATAN: saat ini TIDAK ADA UI yang menampilkan kembali dokumen ini
     * (Auth_model::get_user_documents() tidak pernah dipanggil di mana pun),
     * jadi belum dibuatkan endpoint baca. Kalau nanti dibutuhkan, buat endpoint
     * ber-guard yang memakai serve_private_file() — JANGAN kembalikan ke
     * direktori publik.
     */
    private function _handle_uploads($user_id, $role) {
        $upload_fields = [];
        if ($role === 'pengembang') {
            $upload_fields = ['file_ktp' => 'ktp', 'file_siup' => 'siup_nib'];
        } elseif ($role === 'vendor') {
            $upload_fields = ['file_ktp_vendor' => 'ktp', 'file_siu_vendor' => 'surat_ijin_usaha'];
        } elseif ($role === 'mahasiswa') {
            $upload_fields = ['file_ktm' => 'ktm', 'file_surat_magang' => 'surat_magang'];
        }

        foreach ($upload_fields as $field_name => $doc_type) {
            $ukuran = isset($_FILES[$field_name]['size']) ? (int) $_FILES[$field_name]['size'] : 0;
            $nama_simpan = $this->store_private_upload($field_name, 'onboarding', $user_id);
            if ($nama_simpan) {
                $this->auth_model->save_document(
                    $user_id,
                    $doc_type,
                    $nama_simpan,
                    'private_uploads/onboarding/' . $user_id . '/' . $nama_simpan,
                    $ukuran
                );
            }
        }
    }
}
