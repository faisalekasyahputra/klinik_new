# Forum Diskusi — Security Hardening & Feature Upgrade

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Memperkuat keamanan forum diskusi Klinik PKP dan menambahkan fitur moderasi, pencarian, filter kata kotor, serta reCAPTCHA agar forum aman dari bot spam, XSS, dan penyalahgunaan — layak digunakan sebagai kanal resmi komunikasi publik instansi pemerintah.

**Architecture:** Server-side validation & security di CodeIgniter 3 (controller + model + helper), client-side UX improvements di view (Alpine.js). Semua proteksi bersifat backend-first — frontend hanya pelengkap UX. Menggunakan pola yang sudah ada di Auth controller (reCAPTCHA, session role, form validation).

**Tech Stack:** PHP 7.4+ / CodeIgniter 3, MySQL, Google reCAPTCHA v2, Alpine.js (sudah terinstall), Tailwind CSS JIT (sudah terinstall)

---

## File Structure

| File | Action | Tanggung Jawab |
|---|---|---|
| `application/config/config.php` | MODIFY | Aktifkan global XSS filtering |
| `application/config/profanity.php` | CREATE | Kamus kata-kata terlarang (judi, spam, kasar) |
| `application/helpers/forum_helper.php` | CREATE | Fungsi: `contains_profanity()`, `sanitize_forum_input()`, `check_forum_rate_limit()` |
| `application/models/Forum_model.php` | MODIFY | Tambah method: `soft_delete`, `update_status`, `search`, `count_by_user_ip`, `get_reported` |
| `application/controllers/Umum.php` | MODIFY | Tambah: validasi input, reCAPTCHA, rate-limit, profanity check, moderasi admin |
| `application/views/pages/umum/forum.php` | MODIFY | Tambah: reCAPTCHA widget, search bar, filter kategori, badge status, tombol report |
| `application/views/pages/perumahan/detail.php` | MODIFY | Tambah: reCAPTCHA di form balas, tombol report komentar, status resolved |

---

## Task 1: Aktifkan Global XSS Filtering

**Files:**
- Modify: `application/config/config.php:444`

- [ ] **Step 1: Ubah global_xss_filtering menjadi TRUE**

```php
// Baris 444 di application/config/config.php
// SEBELUM:
$config['global_xss_filtering'] = FALSE;

// SESUDAH:
$config['global_xss_filtering'] = TRUE;
```

> **Catatan:** Ini memastikan SEMUA input dari `$this->input->post()` dan `$this->input->get()` otomatis dibersihkan dari kode XSS tanpa harus menambahkan parameter `TRUE` satu per satu. Parameter `TRUE` yang sudah ada di controller tetap aman (tidak bentrok).

- [ ] **Step 2: Commit**

```bash
git add application/config/config.php
git commit -m "security: enable global XSS filtering"
```

---

## Task 2: Buat Kamus Kata Terlarang (Profanity Config)

**Files:**
- Create: `application/config/profanity.php`

- [ ] **Step 1: Buat file kamus kata terlarang**

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Profanity Filter Configuration
 * 
 * Daftar kata-kata yang dilarang di Forum Diskusi Klinik PKP.
 * Case-insensitive. Sistem akan mencocokkan kata di dalam teks
 * menggunakan regex word-boundary (\b).
 */

// Kata kasar / makian umum
$config['profanity_words'] = [
    // Iklan judi & pinjaman online
    'slot', 'gacor', 'togel', 'casino', 'jackpot', 'maxwin',
    'pinjol', 'pinjaman online', 'dana cair', 'loan shark',
    'betting', 'taruhan', 'judi online',
    
    // Spam & phishing keywords
    'klik disini', 'click here', 'free money', 'hubungi wa',
    'promo terbatas', 'limited offer', 'gratis modal',
    
    // Ujaran kebencian & SARA
    'kafir', 'cina bangsat', 'pribumi',
    
    // Kata kasar bahasa Indonesia (sensor parsial)
    'anjing', 'bangsat', 'bajingan', 'brengsek', 'tolol',
    'goblok', 'idiot', 'kampret', 'monyet', 'babi',
    'kontol', 'memek', 'ngentot', 'pepek', 'jancok',
];

// Pola URL mencurigakan (regex patterns)
$config['profanity_url_patterns'] = [
    '/bit\.ly/i',
    '/tinyurl\.com/i',
    '/wa\.me\/\d+/i',
    '/t\.me\//i',
];
```

- [ ] **Step 2: Commit**

```bash
git add application/config/profanity.php
git commit -m "feat: add profanity word list config for forum"
```

---

## Task 3: Buat Forum Helper (Sanitasi, Profanity Check, Rate Limit)

**Files:**
- Create: `application/helpers/forum_helper.php`

- [ ] **Step 1: Buat helper dengan 3 fungsi utama**

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Forum Helper
 * Fungsi keamanan dan sanitasi untuk Forum Diskusi.
 */

if (!function_exists('contains_profanity')) {
    /**
     * Cek apakah teks mengandung kata terlarang.
     * 
     * @param string $text Teks yang akan diperiksa
     * @return array ['found' => bool, 'words' => string[]]
     */
    function contains_profanity($text) {
        $CI =& get_instance();
        $CI->config->load('profanity', TRUE);
        
        $words = $CI->config->item('profanity_words', 'profanity');
        $url_patterns = $CI->config->item('profanity_url_patterns', 'profanity');
        
        $found_words = [];
        $lower_text = mb_strtolower($text, 'UTF-8');
        
        // Cek kata terlarang (word boundary)
        foreach ($words as $word) {
            $pattern = '/\b' . preg_quote(mb_strtolower($word, 'UTF-8'), '/') . '\b/iu';
            if (preg_match($pattern, $lower_text)) {
                $found_words[] = $word;
            }
        }
        
        // Cek pola URL mencurigakan
        foreach ($url_patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $found_words[] = '[URL mencurigakan]';
                break;
            }
        }
        
        return [
            'found' => count($found_words) > 0,
            'words' => $found_words
        ];
    }
}

if (!function_exists('sanitize_forum_input')) {
    /**
     * Bersihkan input forum dari tag HTML berbahaya.
     * Hanya mengizinkan teks polos.
     * 
     * @param string $text
     * @return string
     */
    function sanitize_forum_input($text) {
        // Hapus semua tag HTML
        $text = strip_tags($text);
        // Hapus karakter kontrol kecuali newline & tab
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text);
        // Trim whitespace berlebihan
        $text = trim($text);
        // Batasi newline beruntun (maks 3)
        $text = preg_replace('/\n{4,}/', "\n\n\n", $text);
        return $text;
    }
}

if (!function_exists('check_forum_rate_limit')) {
    /**
     * Cek apakah IP sudah melebihi batas posting.
     * 
     * @param string $table 'tb_diskusi' atau 'tb_komentar'
     * @param string $ip_column nama kolom IP di tabel
     * @param int $max_per_hour Maksimal posting per jam
     * @return bool TRUE jika masih boleh posting, FALSE jika sudah melebihi limit
     */
    function check_forum_rate_limit($table, $ip_column, $max_per_hour = 5) {
        $CI =& get_instance();
        $ip = $CI->input->ip_address();
        
        $one_hour_ago = date('Y-m-d H:i:s', strtotime('-1 hour'));
        
        $count = $CI->db->where($ip_column, $ip)
                        ->where('created_at >=', $one_hour_ago)
                        ->count_all_results($table);
        
        return $count < $max_per_hour;
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add application/helpers/forum_helper.php
git commit -m "feat: add forum_helper with profanity check, sanitize, rate limit"
```

---

## Task 4: Upgrade Database — Tambah Kolom Keamanan & Fitur

**Files:**
- Modify: Database MySQL (via phpMyAdmin atau SQL query)

- [ ] **Step 1: Jalankan SQL migration untuk menambah kolom baru**

```sql
-- Tambah kolom keamanan di tb_diskusi
ALTER TABLE `tb_diskusi`
    ADD COLUMN `ip_address` VARCHAR(45) DEFAULT NULL AFTER `isi_diskusi`,
    ADD COLUMN `status` ENUM('open','resolved','closed') DEFAULT 'open' AFTER `ip_address`,
    ADD COLUMN `is_deleted` TINYINT(1) DEFAULT 0 AFTER `status`,
    ADD COLUMN `report_count` INT DEFAULT 0 AFTER `is_deleted`;

-- Tambah kolom keamanan di tb_komentar
ALTER TABLE `tb_komentar`
    ADD COLUMN `ip_address` VARCHAR(45) DEFAULT NULL AFTER `isi_komentar`,
    ADD COLUMN `is_deleted` TINYINT(1) DEFAULT 0 AFTER `ip_address`,
    ADD COLUMN `report_count` INT DEFAULT 0 AFTER `is_deleted`;

-- Tambah index untuk performa query filter
ALTER TABLE `tb_diskusi` ADD INDEX `idx_status` (`status`);
ALTER TABLE `tb_diskusi` ADD INDEX `idx_kategori` (`kategori`);
ALTER TABLE `tb_diskusi` ADD INDEX `idx_is_deleted` (`is_deleted`);
ALTER TABLE `tb_komentar` ADD INDEX `idx_is_deleted` (`is_deleted`);
```

- [ ] **Step 2: Commit (simpan SQL sebagai migration reference)**

```bash
git add docs/migrations/
git commit -m "db: add security columns to forum tables"
```

---

## Task 5: Upgrade Forum_model — Soft Delete, Search, Status, Report

**Files:**
- Modify: `application/models/Forum_model.php`

- [ ] **Step 1: Rewrite Forum_model dengan method baru**

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Forum_model extends CI_Model {

    /**
     * Ambil semua diskusi (dengan filter soft-delete).
     * Support search dan filter kategori.
     */
    public function get_all_diskusi($search = '', $kategori = '') {
        $this->db->select('tb_diskusi.*, COUNT(tb_komentar.id_komentar) as total_balasan');
        $this->db->from('tb_diskusi');
        $this->db->join('tb_komentar', 'tb_diskusi.id_diskusi = tb_komentar.id_diskusi AND tb_komentar.is_deleted = 0', 'left');
        $this->db->where('tb_diskusi.is_deleted', 0);
        
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('tb_diskusi.judul_topik', $search);
            $this->db->or_like('tb_diskusi.isi_diskusi', $search);
            $this->db->group_end();
        }
        
        if (!empty($kategori)) {
            $this->db->where('tb_diskusi.kategori', $kategori);
        }
        
        $this->db->group_by('tb_diskusi.id_diskusi');
        $this->db->order_by('tb_diskusi.created_at', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_diskusi_by_id($id) {
        $this->db->where('is_deleted', 0);
        return $this->db->get_where('tb_diskusi', ['id_diskusi' => $id])->row_array();
    }

    public function get_komentar_by_diskusi($id) {
        $this->db->where('is_deleted', 0);
        $this->db->order_by('created_at', 'ASC');
        return $this->db->get_where('tb_komentar', ['id_diskusi' => $id])->result_array();
    }

    public function insert_diskusi($data) {
        return $this->db->insert('tb_diskusi', $data);
    }

    public function insert_komentar($data) {
        return $this->db->insert('tb_komentar', $data);
    }

    /**
     * Soft-delete diskusi (sembunyikan tanpa hapus dari DB)
     */
    public function soft_delete_diskusi($id) {
        $this->db->where('id_diskusi', $id);
        return $this->db->update('tb_diskusi', ['is_deleted' => 1]);
    }

    /**
     * Soft-delete komentar
     */
    public function soft_delete_komentar($id) {
        $this->db->where('id_komentar', $id);
        return $this->db->update('tb_komentar', ['is_deleted' => 1]);
    }

    /**
     * Update status diskusi (open / resolved / closed)
     */
    public function update_status($id, $status) {
        $valid = ['open', 'resolved', 'closed'];
        if (!in_array($status, $valid)) return false;
        
        $this->db->where('id_diskusi', $id);
        return $this->db->update('tb_diskusi', ['status' => $status]);
    }

    /**
     * Increment report count
     */
    public function report_diskusi($id) {
        $this->db->where('id_diskusi', $id);
        $this->db->set('report_count', 'report_count + 1', FALSE);
        return $this->db->update('tb_diskusi');
    }

    public function report_komentar($id) {
        $this->db->where('id_komentar', $id);
        $this->db->set('report_count', 'report_count + 1', FALSE);
        return $this->db->update('tb_komentar');
    }

    /**
     * Auto-hide: sembunyikan konten yang dilaporkan >= N kali
     */
    public function auto_hide_reported($threshold = 5) {
        // Auto-hide diskusi
        $this->db->where('report_count >=', $threshold);
        $this->db->where('is_deleted', 0);
        $this->db->update('tb_diskusi', ['is_deleted' => 1]);
        
        // Auto-hide komentar
        $this->db->where('report_count >=', $threshold);
        $this->db->where('is_deleted', 0);
        $this->db->update('tb_komentar', ['is_deleted' => 1]);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add application/models/Forum_model.php
git commit -m "feat: upgrade Forum_model with soft-delete, search, status, report"
```

---

## Task 6: Upgrade Controller — Validasi, reCAPTCHA, Rate Limit, Profanity

**Files:**
- Modify: `application/controllers/Umum.php` (method `forum`, `tambah_aksi`, `balas_aksi`)

- [ ] **Step 1: Tambahkan helper dan reCAPTCHA di constructor**

Di baris 9 (dalam `__construct()`), tambahkan setelah `$this->load->model('Forum_model');`:

```php
$this->load->helper('forum');

// reCAPTCHA (menggunakan env yang sama dengan Auth)
$this->recaptcha_site_key   = getenv('RECAPTCHA_SITE_KEY') ?: '';
$this->recaptcha_secret_key = getenv('RECAPTCHA_SECRET_KEY') ?: '';
```

Dan tambahkan property di class:

```php
class Umum extends MY_Controller {
    
    private $recaptcha_site_key   = '';
    private $recaptcha_secret_key = '';

    public function __construct()
    {
        // ... existing code ...
    }
```

- [ ] **Step 2: Upgrade method `forum()` — tambahkan search, filter, dan recaptcha key**

```php
public function forum()
{
    $search   = $this->input->get('q');
    $kategori = $this->input->get('kategori');
    
    $datacontent['judul']    = 'Forum Diskusi';
    $datacontent['diskusi']  = $this->Forum_model->get_all_diskusi($search, $kategori);
    $datacontent['search']   = $search;
    $datacontent['kategori_aktif'] = $kategori;
    $datacontent['recaptcha_site_key'] = $this->recaptcha_site_key;
    
    $data['content'] = $this->load->view('pages/umum/forum', $datacontent, true);
    $this->load->view('layouts/main', $data);
}
```

- [ ] **Step 3: Upgrade method `tambah_aksi()` — full security chain**

```php
public function tambah_aksi() {
    // 1. RATE LIMIT: Maks 5 topik per jam per IP
    if (!check_forum_rate_limit('tb_diskusi', 'ip_address', 5)) {
        $this->session->set_flashdata('error', 'Anda terlalu sering membuat topik. Silakan coba lagi nanti.');
        redirect('Umum/forum');
        return;
    }

    // 2. reCAPTCHA VERIFICATION
    if (!empty($this->recaptcha_secret_key)) {
        $recaptcha_response = $this->input->post('g-recaptcha-response');
        if (!$this->_verify_recaptcha($recaptcha_response)) {
            $this->session->set_flashdata('error', 'Verifikasi Captcha gagal. Silakan coba lagi.');
            redirect('Umum/forum');
            return;
        }
    }

    // 3. INPUT VALIDATION
    $nama   = sanitize_forum_input($this->input->post('nama_user'));
    $email  = trim($this->input->post('email_user'));
    $judul  = sanitize_forum_input($this->input->post('judul_topik'));
    $kat    = $this->input->post('kategori');
    $isi    = sanitize_forum_input($this->input->post('isi_diskusi'));

    // Validasi wajib
    if (empty($nama) || empty($email) || empty($judul) || empty($isi)) {
        $this->session->set_flashdata('error', 'Semua field wajib diisi.');
        redirect('Umum/forum');
        return;
    }

    // Validasi email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->session->set_flashdata('error', 'Format email tidak valid.');
        redirect('Umum/forum');
        return;
    }

    // Validasi panjang
    if (mb_strlen($judul) < 10 || mb_strlen($judul) > 200) {
        $this->session->set_flashdata('error', 'Judul topik harus antara 10–200 karakter.');
        redirect('Umum/forum');
        return;
    }

    if (mb_strlen($isi) < 20) {
        $this->session->set_flashdata('error', 'Deskripsi masalah minimal 20 karakter.');
        redirect('Umum/forum');
        return;
    }

    // Validasi kategori (whitelist)
    $valid_kategori = ['RTLH', 'Prasarana Umum', 'Sengketa Lahan', 'Rumah Subsidi', 'Lainnya'];
    if (!in_array($kat, $valid_kategori)) {
        $this->session->set_flashdata('error', 'Kategori tidak valid.');
        redirect('Umum/forum');
        return;
    }

    // 4. PROFANITY CHECK
    $check_judul = contains_profanity($judul);
    $check_isi   = contains_profanity($isi);
    if ($check_judul['found'] || $check_isi['found']) {
        $this->session->set_flashdata('error', 'Postingan Anda mengandung kata-kata yang tidak diperbolehkan. Harap gunakan bahasa yang sopan.');
        redirect('Umum/forum');
        return;
    }

    // 5. INSERT DATA
    $data = [
        'nama_user'   => $nama,
        'email_user'  => $email,
        'judul_topik' => $judul,
        'kategori'    => $kat,
        'isi_diskusi' => $isi,
        'ip_address'  => $this->input->ip_address(),
        'status'      => 'open',
        'created_at'  => date('Y-m-d H:i:s')
    ];
    $this->Forum_model->insert_diskusi($data);

    $this->session->set_flashdata('success', 'Topik diskusi berhasil dibuat!');
    redirect('Umum/forum');
}
```

- [ ] **Step 4: Upgrade method `balas_aksi()` — tambahkan rate limit + profanity**

```php
public function balas_aksi() {
    $id_diskusi      = $this->input->post('id_diskusi');
    $nama_komentator = sanitize_forum_input($this->input->post('nama_komentator'));
    $isi_komentar    = sanitize_forum_input($this->input->post('isi_komentar'));

    // Validasi input wajib
    if (empty($id_diskusi) || empty($nama_komentator) || empty($isi_komentar)) {
        $this->session->set_flashdata('error', 'Semua field wajib diisi.');
        redirect('Umum/forum');
        return;
    }

    // Validasi panjang
    if (mb_strlen($isi_komentar) < 5) {
        $this->session->set_flashdata('error', 'Tanggapan minimal 5 karakter.');
        redirect('Umum/detail/' . $id_diskusi);
        return;
    }

    // Rate Limit: Maks 10 komentar per jam per IP
    if (!check_forum_rate_limit('tb_komentar', 'ip_address', 10)) {
        $this->session->set_flashdata('error', 'Anda terlalu sering membalas. Silakan coba lagi nanti.');
        redirect('Umum/detail/' . $id_diskusi);
        return;
    }

    // reCAPTCHA
    if (!empty($this->recaptcha_secret_key)) {
        $recaptcha_response = $this->input->post('g-recaptcha-response');
        if (!$this->_verify_recaptcha($recaptcha_response)) {
            $this->session->set_flashdata('error', 'Verifikasi Captcha gagal.');
            redirect('Umum/detail/' . $id_diskusi);
            return;
        }
    }

    // Profanity Check
    $check = contains_profanity($isi_komentar);
    if ($check['found']) {
        $this->session->set_flashdata('error', 'Komentar Anda mengandung kata-kata yang tidak diperbolehkan.');
        redirect('Umum/detail/' . $id_diskusi);
        return;
    }

    // PROTEKSI PERAN: role ditentukan di backend
    $role = 'Warga';
    $session_role = $this->session->userdata('role');
    if ($this->session->userdata('is_logged') === TRUE && 
        in_array($session_role, ['admin', 'staff', 'Petugas Disperakim'])) {
        $role = 'Petugas Disperakim';
    }

    $data = [
        'id_diskusi'      => $id_diskusi,
        'nama_komentator' => $nama_komentator,
        'isi_komentar'    => $isi_komentar,
        'role'            => $role,
        'ip_address'      => $this->input->ip_address(),
        'created_at'      => date('Y-m-d H:i:s')
    ];

    $this->Forum_model->insert_komentar($data);

    // Auto-hide jika ada konten yang banyak dilaporkan
    $this->Forum_model->auto_hide_reported(5);

    redirect('Umum/detail/' . $id_diskusi);
}
```

- [ ] **Step 5: Tambahkan method `_verify_recaptcha()` dan endpoint moderasi**

```php
/**
 * Verify reCAPTCHA (sama persis dengan Auth controller)
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
 * Laporkan diskusi (POST via AJAX)
 */
public function report_diskusi() {
    $id = $this->input->post('id');
    if (empty($id)) { echo json_encode(['status' => 'error']); return; }
    
    // Rate limit laporan: 3 per jam per IP
    if (!check_forum_rate_limit('tb_diskusi', 'ip_address', 3)) {
        echo json_encode(['status' => 'error', 'message' => 'Terlalu banyak laporan.']);
        return;
    }
    
    $this->Forum_model->report_diskusi($id);
    $this->Forum_model->auto_hide_reported(5);
    echo json_encode(['status' => 'ok']);
}

/**
 * Laporkan komentar (POST via AJAX)
 */
public function report_komentar() {
    $id = $this->input->post('id');
    if (empty($id)) { echo json_encode(['status' => 'error']); return; }
    
    $this->Forum_model->report_komentar($id);
    $this->Forum_model->auto_hide_reported(5);
    echo json_encode(['status' => 'ok']);
}

/**
 * Admin: Update status diskusi ke resolved/closed
 */
public function update_status_diskusi() {
    if ($this->session->userdata('is_logged') !== TRUE) {
        redirect('Auth/login');
        return;
    }
    
    $session_role = $this->session->userdata('role');
    if (!in_array($session_role, ['admin', 'staff', 'Petugas Disperakim'])) {
        show_error('Anda tidak memiliki izin.', 403);
        return;
    }
    
    $id     = $this->input->post('id_diskusi');
    $status = $this->input->post('status');
    
    $this->Forum_model->update_status($id, $status);
    redirect('Umum/detail/' . $id);
}

/**
 * Admin: Hapus (soft-delete) diskusi
 */
public function delete_diskusi() {
    if ($this->session->userdata('is_logged') !== TRUE) {
        redirect('Auth/login');
        return;
    }
    
    $session_role = $this->session->userdata('role');
    if (!in_array($session_role, ['admin', 'staff', 'Petugas Disperakim'])) {
        show_error('Anda tidak memiliki izin.', 403);
        return;
    }
    
    $id = $this->input->post('id_diskusi');
    $this->Forum_model->soft_delete_diskusi($id);
    
    $this->session->set_flashdata('success', 'Diskusi berhasil dihapus.');
    redirect('Umum/forum');
}

/**
 * Admin: Hapus (soft-delete) komentar
 */
public function delete_komentar() {
    if ($this->session->userdata('is_logged') !== TRUE) {
        redirect('Auth/login');
        return;
    }
    
    $session_role = $this->session->userdata('role');
    if (!in_array($session_role, ['admin', 'staff', 'Petugas Disperakim'])) {
        show_error('Anda tidak memiliki izin.', 403);
        return;
    }
    
    $id_komentar = $this->input->post('id_komentar');
    $id_diskusi  = $this->input->post('id_diskusi');
    $this->Forum_model->soft_delete_komentar($id_komentar);
    
    redirect('Umum/detail/' . $id_diskusi);
}
```

- [ ] **Step 6: Commit**

```bash
git add application/controllers/Umum.php
git commit -m "security: add validation, reCAPTCHA, rate-limit, profanity to forum"
```

---

## Task 7: Upgrade View Forum — Search, reCAPTCHA, Flash Messages, Status Badge

**Files:**
- Modify: `application/views/pages/umum/forum.php`

- [ ] **Step 1: Tambahkan reCAPTCHA script, search bar, flash messages, dan status badge**

Tambahkan di baris 1 (sebelum `<section>`):

```html
<!-- reCAPTCHA Script -->
<?php if (!empty($recaptcha_site_key)): ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>
```

Tambahkan setelah breadcrumb dan header (sebelum `<div class="space-y-4">`):

```html
<!-- Flash Messages -->
<?php if ($this->session->flashdata('error')): ?>
<div class="bg-red-500/10 border border-red-500/30 text-red-400 px-5 py-3 rounded-xl text-xs mb-6 flex items-center gap-2">
    <i class="fa-solid fa-circle-exclamation"></i>
    <?= $this->session->flashdata('error') ?>
</div>
<?php endif; ?>
<?php if ($this->session->flashdata('success')): ?>
<div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 px-5 py-3 rounded-xl text-xs mb-6 flex items-center gap-2">
    <i class="fa-solid fa-circle-check"></i>
    <?= $this->session->flashdata('success') ?>
</div>
<?php endif; ?>

<!-- Search & Filter Bar -->
<form method="GET" action="<?= base_url('Umum/forum') ?>" class="flex flex-col sm:flex-row gap-3 mb-8">
    <div class="flex-grow">
        <input type="text" name="q" value="<?= htmlspecialchars($search ?? '') ?>" 
               placeholder="Cari topik diskusi..." 
               class="w-full bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl px-4 py-3 text-white text-xs outline-none focus:border-[#d6fb00]/40 placeholder-[#5a7a80]">
    </div>
    <select name="kategori" class="bg-[#0a1a1f] border border-[#d6fb00]/20 rounded-xl px-4 py-3 text-zinc-300 text-xs outline-none w-full sm:w-48">
        <option value="">Semua Kategori</option>
        <option value="RTLH" <?= ($kategori_aktif ?? '') === 'RTLH' ? 'selected' : '' ?>>RTLH</option>
        <option value="Prasarana Umum" <?= ($kategori_aktif ?? '') === 'Prasarana Umum' ? 'selected' : '' ?>>Prasarana Umum</option>
        <option value="Sengketa Lahan" <?= ($kategori_aktif ?? '') === 'Sengketa Lahan' ? 'selected' : '' ?>>Sengketa Lahan</option>
        <option value="Rumah Subsidi" <?= ($kategori_aktif ?? '') === 'Rumah Subsidi' ? 'selected' : '' ?>>Rumah Subsidi</option>
        <option value="Lainnya" <?= ($kategori_aktif ?? '') === 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
    </select>
    <button type="submit" class="bg-[#d6fb00] text-black font-bold text-xs px-5 py-3 rounded-xl flex items-center gap-2 shrink-0">
        <i class="fa-solid fa-magnifying-glass"></i> Cari
    </button>
</form>
```

Pada setiap kartu diskusi, tambahkan status badge setelah badge kategori:

```php
<?php if (isset($row['status']) && $row['status'] === 'resolved'): ?>
<span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold tracking-wide uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
    <i class="fa-solid fa-circle-check mr-1"></i>Selesai
</span>
<?php elseif (isset($row['status']) && $row['status'] === 'closed'): ?>
<span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold tracking-wide uppercase bg-red-500/10 text-red-400 border border-red-500/20">
    <i class="fa-solid fa-lock mr-1"></i>Ditutup
</span>
<?php endif; ?>
```

Pada modal form "Buat Diskusi Baru", tambahkan reCAPTCHA sebelum tombol submit:

```html
<!-- reCAPTCHA widget -->
<?php if (!empty($recaptcha_site_key)): ?>
<div class="g-recaptcha" data-sitekey="<?= $recaptcha_site_key ?>" data-theme="dark"></div>
<?php endif; ?>
```

- [ ] **Step 2: Commit**

```bash
git add application/views/pages/umum/forum.php
git commit -m "feat: add search, reCAPTCHA, status badges, flash messages to forum view"
```

---

## Task 8: Upgrade View Detail — reCAPTCHA, Report, Status Controls

**Files:**
- Modify: `application/views/pages/perumahan/detail.php`

- [ ] **Step 1: Tambahkan reCAPTCHA di form balasan**

Sebelum `<section>` tag, tambahkan:

```html
<?php if (!empty($recaptcha_site_key)): ?>
<script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>
```

Di dalam form balas (`<form action="<?= base_url('Umum/balas_aksi') ?>">`), tambahkan sebelum tombol submit:

```html
<?php if (!empty($recaptcha_site_key)): ?>
<div class="g-recaptcha" data-sitekey="<?= $recaptcha_site_key ?>" data-theme="dark"></div>
<?php endif; ?>
```

- [ ] **Step 2: Tambahkan status badge dan kontrol admin di header topik**

Setelah tanggal di header topik (baris 26), tambahkan:

```php
<!-- Status Badge -->
<?php if (isset($topik['status']) && $topik['status'] === 'resolved'): ?>
<span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
    <i class="fa-solid fa-circle-check mr-1"></i>Selesai
</span>
<?php elseif (isset($topik['status']) && $topik['status'] === 'closed'): ?>
<span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold uppercase bg-red-500/10 text-red-400 border border-red-500/20">
    <i class="fa-solid fa-lock mr-1"></i>Ditutup
</span>
<?php endif; ?>
```

- [ ] **Step 3: Tambahkan kontrol moderasi admin (visible hanya untuk admin)**

Setelah blok header topik (setelah `</div>` penutup dari blok topik utama), tambahkan:

```php
<!-- Admin Controls -->
<?php 
$is_admin = ($this->session->userdata('is_logged') === TRUE && 
             in_array($this->session->userdata('role'), ['admin', 'staff', 'Petugas Disperakim']));
?>
<?php if ($is_admin): ?>
<div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-[#d6fb00]/10">
    <form method="POST" action="<?= base_url('Umum/update_status_diskusi') ?>" class="inline">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
        <input type="hidden" name="id_diskusi" value="<?= $topik['id_diskusi'] ?>">
        <input type="hidden" name="status" value="resolved">
        <button type="submit" class="text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-3 py-1.5 rounded-lg hover:bg-emerald-500/20 transition-all">
            <i class="fa-solid fa-circle-check mr-1"></i> Tandai Selesai
        </button>
    </form>
    <form method="POST" action="<?= base_url('Umum/update_status_diskusi') ?>" class="inline">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
        <input type="hidden" name="id_diskusi" value="<?= $topik['id_diskusi'] ?>">
        <input type="hidden" name="status" value="closed">
        <button type="submit" class="text-[10px] font-bold bg-red-500/10 text-red-400 border border-red-500/20 px-3 py-1.5 rounded-lg hover:bg-red-500/20 transition-all">
            <i class="fa-solid fa-lock mr-1"></i> Tutup Diskusi
        </button>
    </form>
    <form method="POST" action="<?= base_url('Umum/delete_diskusi') ?>" class="inline" onsubmit="return confirm('Yakin ingin menghapus diskusi ini?')">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
        <input type="hidden" name="id_diskusi" value="<?= $topik['id_diskusi'] ?>">
        <button type="submit" class="text-[10px] font-bold bg-zinc-500/10 text-zinc-400 border border-zinc-500/20 px-3 py-1.5 rounded-lg hover:bg-red-500/20 hover:text-red-400 transition-all">
            <i class="fa-solid fa-trash mr-1"></i> Hapus
        </button>
    </form>
</div>
<?php endif; ?>
```

- [ ] **Step 4: Tambahkan tombol "Laporkan" di setiap komentar**

Di dalam loop komentar, tambahkan setelah `<p>` isi komentar:

```php
<!-- Tombol Laporkan (untuk non-admin) -->
<?php if (!$is_admin): ?>
<div class="pl-9 mt-2">
    <button onclick="reportKomentar(<?= $kom['id_komentar'] ?>)" 
            class="text-[10px] text-zinc-600 hover:text-red-400 transition-colors flex items-center gap-1">
        <i class="fa-regular fa-flag"></i> Laporkan
    </button>
</div>
<?php else: ?>
<!-- Admin: Hapus komentar -->
<div class="pl-9 mt-2">
    <form method="POST" action="<?= base_url('Umum/delete_komentar') ?>" class="inline" onsubmit="return confirm('Hapus komentar ini?')">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
        <input type="hidden" name="id_komentar" value="<?= $kom['id_komentar'] ?>">
        <input type="hidden" name="id_diskusi" value="<?= $topik['id_diskusi'] ?>">
        <button type="submit" class="text-[10px] text-zinc-600 hover:text-red-400 transition-colors flex items-center gap-1">
            <i class="fa-solid fa-trash"></i> Hapus Komentar
        </button>
    </form>
</div>
<?php endif; ?>
```

Dan tambahkan script JS di bawah `</section>`:

```html
<script>
function reportKomentar(id) {
    if (!confirm('Laporkan komentar ini karena mengandung konten tidak pantas?')) return;
    fetch('<?= base_url("Umum/report_komentar") ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: '<?= $this->security->get_csrf_token_name() ?>=<?= $this->security->get_csrf_hash() ?>&id=' + id
    })
    .then(r => r.json())
    .then(d => {
        alert(d.status === 'ok' ? 'Laporan terkirim. Terima kasih atas partisipasi Anda.' : (d.message || 'Gagal mengirim laporan.'));
    })
    .catch(() => alert('Terjadi kesalahan jaringan.'));
}
</script>
```

- [ ] **Step 5: Pastikan `detail()` di controller mengirim `recaptcha_site_key`**

Di method `detail()` di `Umum.php`, tambahkan:

```php
public function detail($id) {
    $datacontent['topik'] = $this->Forum_model->get_diskusi_by_id($id);
    if(empty($datacontent['topik'])) { show_404(); }
    
    $datacontent['komentar'] = $this->Forum_model->get_komentar_by_diskusi($id);
    $datacontent['recaptcha_site_key'] = $this->recaptcha_site_key;
    
    $data['content'] = $this->load->view('pages/perumahan/detail', $datacontent, true);
    $this->load->view('layouts/main', $data);
}
```

- [ ] **Step 6: Commit**

```bash
git add application/views/pages/perumahan/detail.php application/controllers/Umum.php
git commit -m "feat: add reCAPTCHA, report system, admin controls to forum detail"
```

---

## Verification Plan

### Manual Verification

1. **Anti-Bot:** Buka forum, submit form tanpa centang reCAPTCHA → harus ditolak dengan pesan error
2. **Rate Limit:** Submit 6 topik berturut-turut → topik ke-6 harus ditolak
3. **Profanity Filter:** Buat topik dengan judul "slot gacor maxwin" → harus ditolak
4. **XSS:** Buat topik dengan judul `<script>alert('xss')</script>` → harus ditampilkan sebagai teks biasa
5. **Validasi:** Submit form dengan judul kosong atau < 10 karakter → harus ditolak
6. **Search:** Ketik keyword di search bar → hanya topik yang cocok yang muncul
7. **Filter Kategori:** Pilih kategori "RTLH" → hanya topik RTLH yang ditampilkan
8. **Status Badge:** Login sebagai admin, klik "Tandai Selesai" → badge hijau muncul
9. **Report:** Klik "Laporkan" pada komentar → alert konfirmasi muncul
10. **Soft Delete:** Login admin, klik "Hapus" pada diskusi → diskusi hilang dari list tapi masih ada di DB
