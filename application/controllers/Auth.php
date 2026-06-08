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
        $email    = trim($this->input->post('email', TRUE));
        $password = $this->input->post('password');

        // Basic validation
        if (empty($email) || empty($password)) {
            $this->session->set_flashdata('error', 'Email dan password wajib diisi.');
            redirect('Auth/login');
            return;
        }

        // Verify reCAPTCHA (skip if no secret key configured)
        if (!empty($this->recaptcha_secret_key)) {
            $recaptcha_response = $this->input->post('g-recaptcha-response');
            if (!$this->_verify_recaptcha($recaptcha_response)) {
                $this->session->set_flashdata('error', 'Verifikasi Captcha gagal. Silakan coba lagi.');
                redirect('Auth/login');
                return;
            }
        }

        // Find user
        $user = $this->auth_model->find_by_email($email);

        if (!$user || empty($user->password)) {
            // User not found or no password (Google-only user)
            $this->session->set_flashdata('error', 'Email atau password salah.');
            redirect('Auth/login');
            return;
        }

        // Check lockout
        if ($this->auth_model->is_locked($user)) {
            $remaining = ceil($this->auth_model->lockout_remaining($user) / 60);
            $this->session->set_flashdata('error', "Akun terkunci sementara. Coba lagi dalam {$remaining} menit.");
            redirect('Auth/login');
            return;
        }

        // Verify password
        if (!password_verify($password, $user->password)) {
            $this->auth_model->increment_login_attempts($user->id);
            $attempts_left = Auth_model::MAX_LOGIN_ATTEMPTS - ($user->login_attempts + 1);
            if ($attempts_left > 0) {
                $this->session->set_flashdata('error', "Email atau password salah. Sisa {$attempts_left} percobaan.");
            } else {
                $this->session->set_flashdata('error', 'Akun terkunci selama 15 menit karena terlalu banyak percobaan gagal.');
            }
            redirect('Auth/login');
            return;
        }

        // Success — reset attempts and create session
        $this->auth_model->reset_login_attempts($user->id);

        $session_data = [
            'user_id'   => $user->id,
            'name'      => $user->name,
            'email'     => $user->email,
            'avatar'    => $user->avatar,
            'is_logged' => TRUE,
        ];
        $this->session->set_userdata($session_data);
        $this->session->sess_regenerate(TRUE);

        // Redirect based on profile completion
        $this->_redirect_after_login();
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

        // Validation
        if (empty($email) || empty($password) || empty($password_confirm)) {
            $this->session->set_flashdata('error', 'Semua field wajib diisi.');
            redirect('Auth/register');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->session->set_flashdata('error', 'Format email tidak valid.');
            redirect('Auth/register');
            return;
        }

        if ($password !== $password_confirm) {
            $this->session->set_flashdata('error', 'Password dan konfirmasi tidak cocok.');
            redirect('Auth/register');
            return;
        }

        // Password strength
        if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) ||
            !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
            $this->session->set_flashdata('error', 'Password harus minimal 8 karakter, mengandung huruf besar, angka, dan simbol.');
            redirect('Auth/register');
            return;
        }

        // Verify reCAPTCHA
        if (!empty($this->recaptcha_secret_key)) {
            $recaptcha_response = $this->input->post('g-recaptcha-response');
            if (!$this->_verify_recaptcha($recaptcha_response)) {
                $this->session->set_flashdata('error', 'Verifikasi Captcha gagal. Silakan coba lagi.');
                redirect('Auth/register');
                return;
            }
        }

        // Check if email already exists
        $existing = $this->auth_model->find_by_email($email);
        if ($existing) {
            $this->session->set_flashdata('error', 'Email sudah terdaftar. Silakan login atau gunakan email lain.');
            redirect('Auth/register');
            return;
        }

        // Create user
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $user_id = $this->auth_model->create_user($email, $password_hash);

        if (!$user_id) {
            $this->session->set_flashdata('error', 'Terjadi kesalahan sistem. Silakan coba lagi.');
            redirect('Auth/register');
            return;
        }

        // Auto-login the new user
        $session_data = [
            'user_id'   => $user_id,
            'name'      => NULL,
            'email'     => $email,
            'avatar'    => NULL,
            'is_logged' => TRUE,
        ];
        $this->session->set_userdata($session_data);
        $this->session->sess_regenerate(TRUE);

        // Redirect to onboarding (mandatory)
        $this->session->set_flashdata('success', 'Akun berhasil dibuat! Silakan lengkapi profil Anda.');
        redirect('Auth/onboarding');
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

        // If profile already complete, go to dashboard
        if ($this->auth_model->is_profile_complete($this->get_user_id())) {
            redirect('');
            return;
        }

        $data = [
            'user_email' => $this->session->userdata('email'),
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
        $valid_roles = ['warga', 'pages/pengembang/pengembang', 'vendor', 'mahasiswa'];
        if (!in_array($role, $valid_roles)) {
            $this->session->set_flashdata('error', 'Pilih peran yang valid.');
            redirect('Auth/onboarding');
            return;
        }

        // Common fields
        $nama      = html_escape($this->input->post('nama_lengkap'));
        $nik_raw   = html_escape($this->input->post('nik_identitas'));
        $alamat_raw = html_escape($this->input->post('alamat_domisili'));
        $phone     = html_escape($this->input->post('phone'));

        if (empty($nama) || empty($nik_raw) || empty($alamat_raw) || empty($phone)) {
            $this->session->set_flashdata('error', 'Semua field wajib harus diisi.');
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
            'name'            => $nama,
            'role'            => $role,
            'nik'             => $nik_encrypted,
            'nik_lookup_hash' => $nik_hash,
            'alamat'          => $alamat_encrypted,
            'phone'           => $phone,
            'kategori'        => $role, // Map to existing kategori field
        ];

        // Role-specific fields
        if ($role === 'pages/pengembang/pengembang') {
            $profile_data['nama_perusahaan'] = html_escape($this->input->post('nama_perusahaan'));
            $profile_data['alamat_kantor']   = html_escape($this->input->post('alamat_kantor'));
            $profile_data['telp_kantor']     = html_escape($this->input->post('telp_kantor'));
        } elseif ($role === 'vendor') {
            $profile_data['nama_usaha']  = html_escape($this->input->post('nama_usaha'));
            $profile_data['alamat_usaha'] = html_escape($this->input->post('alamat_usaha'));
            $profile_data['jenis_usaha'] = html_escape($this->input->post('jenis_usaha'));
        }

        // Save profile
        $this->auth_model->save_profile($user_id, $profile_data);

        // Handle file uploads
        $this->_handle_uploads($user_id, $role);

        // Update session name
        $this->session->set_userdata('name', $nama);

        $this->session->set_flashdata('success', 'Profil berhasil disimpan! Selamat datang di Klinik PKP.');
        redirect('');
    }

    // =========================================================
    // FORGOT PASSWORD (Placeholder)
    // =========================================================

    public function forgot_password() {
        $this->load->view('pages/auth/forgot_password');
    }

    // =========================================================
    // EMAIL VERIFICATION (Placeholder — future implementation)
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
                window.opener.location.href = '" . base_url('pages/auth/login') . "';
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
                        $this->db->update('users', [
                            'email_verified_at' => date('Y-m-d H:i:s'),
                        ]);

                        $session_data = [
                            'user_id'   => $logged_in_user[0]['id'],
                            'name'      => $logged_in_user[0]['name'],
                            'email'     => $logged_in_user[0]['email'],
                            'avatar'    => $logged_in_user[0]['avatar'],
                            'is_logged' => TRUE,
                        ];
                        $this->session->set_userdata($session_data);
                        $this->session->sess_regenerate(TRUE);

                        // Check if profile is complete — redirect to new onboarding if not
                        $user_record = $this->auth_model->find_by_id($logged_in_user[0]['id']);
                        if ($user_record && $user_record->profile_completed == 0) {
                            $redirect_to = 'pages/auth/onboarding';
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
        redirect(!empty($safe_redirect) ? $safe_redirect : 'pages/auth/login');
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
    private function _handle_uploads($user_id, $role) {
        // Create upload directory
        $upload_path = FCPATH . 'uploads/documents/' . $user_id . '/';
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, TRUE);
        }

        $config = [
            'upload_path'   => $upload_path,
            'allowed_types' => 'pdf|jpg|jpeg|png',
            'max_size'      => 5120, // 5MB
            'encrypt_name'  => TRUE,
        ];

        $this->load->library('upload');

        // Define upload fields per role
        $upload_fields = [];
        if ($role === 'pages/pengembang/pengembang') {
            $upload_fields = ['file_ktp' => 'ktp', 'file_siup' => 'siup_nib'];
        } elseif ($role === 'vendor') {
            $upload_fields = ['file_ktp_vendor' => 'ktp', 'file_siu_vendor' => 'surat_ijin_usaha'];
        } elseif ($role === 'mahasiswa') {
            $upload_fields = ['file_ktm' => 'ktm', 'file_surat_magang' => 'surat_magang'];
        }

        foreach ($upload_fields as $field_name => $doc_type) {
            if (!empty($_FILES[$field_name]['name'])) {
                $this->upload->initialize($config);
                if ($this->upload->do_upload($field_name)) {
                    $file_data = $this->upload->data();
                    $this->auth_model->save_document(
                        $user_id,
                        $doc_type,
                        $file_data['file_name'],
                        'uploads/documents/' . $user_id . '/' . $file_data['file_name'],
                        $file_data['file_size'] * 1024
                    );
                }
            }
        }
    }
}
