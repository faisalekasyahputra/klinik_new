<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * MY_Controller Class
 * 
 * Base Application Controller providing security headers on every response.
 * Adapted from kliknikpkp_styling for klinik_new (lighter version — no auth_lib/encryption_lib/audit_model).
 */
class MY_Controller extends CI_Controller {

    public function __construct() {
        parent::__construct();

        // Load essential helpers
        $this->load->helper(['url', 'form', 'security']);
        $this->load->library('session');

        // Set OWASP security headers on every response
        $this->set_security_headers();
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

        // HSTS — enforce HTTPS for 1 year (only effective over HTTPS)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        // Permissions Policy — restrict browser APIs
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
     * Render a page view — full layout on a normal request, or just the
     * inner content fragment when called via AJAX (used by the navbar's
     * tab-loader in footer.php, which fetches this fragment and swaps it
     * into #page-content-wrapper instead of doing a full page navigation).
     *
     * @param string $view View path, e.g. 'pages/home/awal'
     * @param array  $data Data passed to the view
     */
    protected function render($view, $data = []) {
        if ($this->input->is_ajax_request()) {
            $this->load->view($view, $data);
        } else {
            $data['content'] = $this->load->view($view, $data, true);
            $this->load->view('layouts/main', $data);
        }
    }

    /**
     * Render a personal dashboard page (role warga/pengembang/mahasiswa/dst) memakai
     * shell admin yang sama (sidebar+topbar admin/index.php) supaya semua dashboard
     * login punya tema konsisten — bukan lagi halaman portal terpisah.
     *
     * @param string $view        View path, mis. 'pages/pengaturan/index'
     * @param array  $data        Data untuk view
     * @param array  $scoped_menu Item sidebar, tiap item: ['label','icon','url','segment']
     */
    protected function render_user_dashboard($view, $data = [], $scoped_menu = []) {
        $data['scoped_menu'] = $scoped_menu;
        $data['content'] = $this->load->view($view, $data, TRUE);
        $this->load->view('admin/index', $data);
    }

    /**
     * Validate that a redirect path is internal (not an open redirect to external domain)
     *
     * @param string $path The path or URL to validate
     * @return string Safe redirect path (internal only)
     */
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
            redirect('Auth/login');
        }
    }

    // Custom render untuk layout khusus admin
    protected function render_admin($view, $data = []) {
        // Inject global pending count for sidebar notification
        $this->load->model('Admin_model');
        $data['pending_count'] = $this->Admin_model->count_pending_queue();

        $data['content'] = $this->load->view($view, $data, TRUE);
        $this->load->view('admin/index', $data);
    }
}

/**
 * Admin_Kabkota_Controller Class
 *
 * Base controller untuk admin yang di-scope ke 1 kabupaten/kota
 * (kelola antrean perumahan wilayahnya saja — lihat sf_housing_queue.kabupaten_id).
 * Scope-nya (kabupaten_id) ditaruh di session saat login, bukan dipercaya dari request.
 */
class Admin_Kabkota_Controller extends MY_Controller {

    protected $my_kabupaten_id;

    public function __construct() {
        parent::__construct();

        if ( ! $this->session->userdata('is_logged') || $this->session->userdata('role') !== 'admin_kabkota') {
            $this->session->set_flashdata('error', 'Akses ditolak. Anda bukan Admin Kabupaten/Kota.');
            redirect('Auth/login');
        }

        $this->my_kabupaten_id = $this->session->userdata('kabupaten_id');
        if (empty($this->my_kabupaten_id)) {
            $this->session->set_flashdata('error', 'Akun ini belum ditetapkan ke kabupaten/kota manapun. Hubungi superadmin.');
            redirect('Auth/login');
        }
    }

    protected function render_scoped_admin($view, $data = []) {
        $data['scoped_menu'] = [
            ['label' => 'Dashboard', 'icon' => 'ph-squares-four', 'url' => 'Admin_Kabkota', 'segment' => 'Admin_Kabkota'],
        ];
        $data['content'] = $this->load->view($view, $data, TRUE);
        $this->load->view('admin/index', $data);
    }
}

/**
 * Admin_Bidang_Controller Class
 *
 * Base controller untuk admin yang di-scope ke 1 bidang
 * (kelola aduan yang masuk ke bidangnya saja — lihat aduan.bidang).
 * Scope-nya (bidang_kode) ditaruh di session saat login, bukan dipercaya dari request.
 */
class Admin_Bidang_Controller extends MY_Controller {

    protected $my_bidang_kode;

    public function __construct() {
        parent::__construct();

        if ( ! $this->session->userdata('is_logged') || $this->session->userdata('role') !== 'admin_bidang') {
            $this->session->set_flashdata('error', 'Akses ditolak. Anda bukan Admin Bidang.');
            redirect('Auth/login');
        }

        $this->my_bidang_kode = $this->session->userdata('bidang_kode');
        if (empty($this->my_bidang_kode)) {
            $this->session->set_flashdata('error', 'Akun ini belum ditetapkan ke bidang manapun. Hubungi superadmin.');
            redirect('Auth/login');
        }
    }

    protected function render_scoped_admin($view, $data = []) {
        $data['scoped_menu'] = [
            ['label' => 'Dashboard', 'icon' => 'ph-squares-four', 'url' => 'Admin_Bidang', 'segment' => 'Admin_Bidang'],
        ];
        $data['content'] = $this->load->view($view, $data, TRUE);
        $this->load->view('admin/index', $data);
    }
}
