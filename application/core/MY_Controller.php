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
     * Simpan satu berkas unggahan ke DIREKTORI PRIVAT di luar webroot.
     *
     * Satu-satunya pintu unggah yang boleh dipakai fitur baru. Sebelum ini ada
     * tiga jalur berbeda yang semuanya menyimpan di dalam webroot
     * (Auth::_handle_uploads, Umum::simpan_aduan, KemitraanPortal::simpan) —
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
     * benar-benar tersaji lewat HTTP — diverifikasi langsung: dokumen SRP2
     * bisa diunduh tanpa login sama sekali. Asumsi "di luar webroot" yang
     * tertulis di AGENTS.md §9 tidak berlaku universal, tergantung di mana
     * aplikasi dipasang relatif terhadap DocumentRoot.
     *
     * Ditulis oleh KODE (bukan disiapkan manual) karena private_uploads/ ada
     * di luar repo git — file yang ditaruh manual tidak akan ikut ter-deploy.
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
     * memanggil ini — method ini tidak tahu apa-apa soal otorisasi.
     *
     * basename() dipakai pada nama file supaya nilai dari DB yang (entah
     * bagaimana) memuat path tidak bisa membaca file di luar direktorinya.
     */
    protected function serve_private_file($domain, $owner_id, $stored_name, $mime = 'application/octet-stream') {
        $path = $this->private_upload_dir($domain, $owner_id) . basename((string) $stored_name);
        if (empty($stored_name) || ! is_file($path)) { show_404(); return; }

        // header() langsung, BUKAN $this->output->set_content_type():
        // readfile() menulis body duluan sehingga antrean header CI terlambat —
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
     * yang sudah diterapkan pemanggil — supaya tidak ada kemungkinan scope
     * terlewat karena urutan pemanggilan.
     *
     * Sebelumnya halaman ini mengirim s.d. 1000 baris sebagai JSON ke browser
     * lalu memfilter di klien; itu paradigma tabel yang dihapus di B8.
     *
     * @param int|null $kabupaten_id NULL = tanpa scope wilayah (superadmin)
     * @return array [queue, table, pager, filter_status]
     */
    protected function antrean_table_data($kabupaten_id = NULL) {
        $kolom_sort = [
            'sf_housing_queue.created_at', 'sf_housing_queue.nama_lengkap',
            'sf_programs.nama_program', 'sf_housing_queue.status_antrean',
        ];
        $table = $this->table_state($kolom_sort, 'sf_housing_queue.created_at');

        $status = $this->input->get('status', TRUE);
        $status = in_array($status, ['pending', 'needs_revision', 'approved', 'rejected'], TRUE) ? $status : NULL;

        $this->db->from('sf_housing_queue')
            ->join('sf_programs', 'sf_housing_queue.program_id = sf_programs.id', 'left');
        if ($kabupaten_id !== NULL) { $this->db->where('sf_housing_queue.kabupaten_id', $kabupaten_id); }
        if ($status) { $this->db->where('sf_housing_queue.status_antrean', $status); }
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

        return ['queue' => $queue, 'table' => $table, 'pager' => $table, 'filter_status' => $status];
    }

    protected function assessment_detail_data($queue_id, $kabupaten_id = NULL) {
        $this->load->model('Housing_assessment_model');
        $this->load->library('encryption_lib');
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

        return [
            'queue' => $detail['queue'], 'assessment' => $assessment,
            'profile' => $detail['profile_snapshot'] ?? [],
            'source_snapshot' => is_array($source) ? $source : [],
            'provenance' => $provenance,
            'recommendations' => $recommendations,
            'evidence' => $this->Housing_assessment_model->get_scoped_queue_files($queue_id, $kabupaten_id),
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
     * Nilai dari ?sort= TIDAK PERNAH boleh masuk ORDER BY apa adanya —
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
     * (Admin_Bidang/Admin_Kemitraan/Admin_Users) — aman saat data masih
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
     * `akun` bukan dashboard semua role — superadmin misalnya sengaja TIDAK
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
            if ( ! empty($m['scope']) && empty($this->session->userdata($m['scope']))) { continue; }
            $g = array_search($m['group'] ?? '', $group_order);
            $kandidat[] = ['g' => $g === FALSE ? 999 : $g, 'o' => $m['order'] ?? 999, 'url' => $m['url']];
        }
        if (empty($kandidat)) { return 'akun'; }

        usort($kandidat, fn($a, $b) => [$a['g'], $a['o']] <=> [$b['g'], $b['o']]);
        return $kandidat[0]['url'];
    }

    /**
     * Hitung baris "belum diproses" untuk satu entri registry, berdasarkan
     * 'table' + 'pending_where' yang dideklarasikan di sana. Satu mekanisme
     * untuk badge sidebar DAN kartu ringkas overview superadmin — sebelumnya
     * tiap counter dulu butuh method model sendiri.
     *
     * @param array $modul entri dari config dashboard_modules
     * @return int 0 kalau entri tidak mendeklarasikan tabel/pending_where
     */
    protected function count_pending_modul($modul) {
        if (empty($modul['table']) || empty($modul['pending_where'])) {
            return 0;
        }
        return (int) $this->db->where($modul['pending_where'])->count_all_results($modul['table']);
    }

    /**
     * Bangun menu dashboard dari registry application/config/dashboard_modules.php,
     * difilter berdasarkan role & scope sesi saat ini, dikelompokkan sesuai
     * dashboard_module_groups. INI HANYA MENGATUR TAMPILAN MENU — bukan otorisasi;
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
            if ( ! empty($m['scope']) && empty($this->session->userdata($m['scope']))) { continue; }

            $badge = NULL;
            if ( ! empty($m['badge'])) {
                $badge = $this->count_pending_modul($m) ?: NULL;
            }

            $items[] = [
                'key' => $key, 'label' => $m['label'], 'icon' => $m['icon'], 'url' => $m['url'],
                'group' => $m['group'] ?? '', 'order' => $m['order'] ?? 999, 'badge' => $badge,
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
        $uri = $this->uri->uri_string();
        $best = -1; $best_i = NULL;
        foreach ($items as $i => $item) {
            $url = $item['url'];
            $match = ($uri === $url) || strpos($uri, $url . '/') === 0;
            if ($match && strlen($url) > $best) { $best = strlen($url); $best_i = $i; }
        }
        foreach ($items as $i => $item) { $items[$i]['active'] = ($i === $best_i); }

        $grouped = [];
        foreach ($items as $item) { $grouped[$item['group']][] = $item; }
        return $grouped;
    }

    /**
     * Render dashboard shell (sidebar+topbar admin/index.php) yang dipakai SEMUA
     * role login — status_pengajuan/profil (warga/pengembang/mahasiswa) maupun
     * admin ter-scope. Menu diambil dari dashboard_menu(), bukan parameter —
     * satu sumber kebenaran untuk semua pemanggil (lihat render_admin()/
     * render_scoped_admin() di bawah, keduanya tinggal delegasi ke sini).
     *
     * @param string $view View path, mis. 'pages/pengaturan/index'
     * @param array  $data Data untuk view
     */
    protected function render_user_dashboard($view, $data = []) {
        if ($this->input->is_ajax_request()) {
            // Cabang partial untuk loader progresif dashboard — pola yang sama
            // dengan render() portal. Judul dikirim lewat header supaya
            // document.title ikut diperbarui tanpa membungkus HTML.
            if (! empty($data['title'])) {
                $this->output->set_header('X-Page-Title: ' . rawurlencode($data['title']));
            }
            $data['dashboard_menu'] = $this->dashboard_menu();
            $this->load->view($view, $data);
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

    // Delegasi ke render_user_dashboard() — badge/menu superadmin sekarang
    // datang dari registry (dashboard_modules.php), bukan hardcode di sini.
    protected function render_admin($view, $data = []) {
        return $this->render_user_dashboard($view, $data);
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

    // Delegasi ke render_user_dashboard() — menu ter-scope sekarang datang
    // dari registry (dashboard_modules.php, filter role+scope), bukan hardcode.
    protected function render_scoped_admin($view, $data = []) {
        return $this->render_user_dashboard($view, $data);
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

    // Delegasi ke render_user_dashboard() — menu ter-scope sekarang datang
    // dari registry (dashboard_modules.php, filter role+scope), bukan hardcode.
    protected function render_scoped_admin($view, $data = []) {
        return $this->render_user_dashboard($view, $data);
    }
}
