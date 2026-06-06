# TECHNICAL DESIGN DOCUMENT (TDD)
## Klinik PKP — Arsitektur Backend & Pengembangan Kode (CodeIgniter 3)

---

## 1. STRUKTUR ARSITEKTUR KODE (Code Architecture)

Aplikasi **Klinik PKP** (`klinik_new`) dibangun menggunakan arsitektur **MVC** terpusat di CodeIgniter 3. Spesifikasi teknis ini merinci struktur controller, library, dan model yang berjalan di backend.

```
                  ┌──────────────────────────────────────────────┐
                  │                 BROWSER CLIENT               │
                  └──────────────────────┬───────────────────────┘
                                         │ HTTP Request
                                         ▼
                  ┌──────────────────────────────────────────────┐
                  │           Nginx / Apache Server              │
                  │   (Memotong direct access ke /application)   │
                  └──────────────────────┬───────────────────────┘
                                         │ index.php
                                         ▼
                  ┌──────────────────────────────────────────────┐
                  │          CodeIgniter 3 Framework             │
                  │                                              │
                  │   ┌──────────────────────────────────────┐   │
                  │   │   Core Hooks Layer                   │   │
                  │   │    - CSRF Protection                 │   │
                  │   │    - Security Headers                │   │
                  │   └──────────────────┬───────────────────┘   │
                  │                      │                       │
                  │                      ▼                       │
                  │   ┌──────────────────────────────────────┐   │
                  │   │   Base Controller: MY_Controller     │   │
                  │   │    - Public_Controller (Bebas)       │   │
                  │   │    - Auth_Controller (Wajib Login)   │   │
                  │   └──────────────────┬───────────────────┘   │
                  │                      │                       │
                  │                      ▼                       │
                  │   ┌──────────────────────────────────────┐   │
                  │   │    Controllers, Libraries, Models    │   │
                  │   │    - Auth.php  - Ternak_api.php      │   │
                  │   │    - Index.php - Forum_model.php     │   │
                  │   └──────────────────────────────────────┘   │
                  └──────────────────────────────────────────────┘
```

---

## 2. SPESIFIKASI BASE CONTROLLER (`MY_Controller`)

Untuk merapikan penulisan hak akses dan menghindari kebocoran data, kita menerapkan hierarki base controller terpusat di `application/core/MY_Controller.php`:

```php
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class MY_Controller extends CI_Controller {
    public function __construct() {
        parent::__construct();
        // Load library global dinamis
        $this->load->library(['session', 'encryption']);
        $this->load->helper(['url', 'form']);
    }

    // Custom render template layout induk
    protected function render($view, $data = []) {
        $data['content'] = $this->load->view($view, $data, TRUE);
        $this->load->view('index', $data);
    }
}

// Controller yang bisa diakses publik tanpa login
class Public_Controller extends MY_Controller {
    public function __construct() {
        parent::__construct();
    }
}

// Controller yang wajib melalui verifikasi login Google
class Auth_Controller extends MY_Controller {
    public function __construct() {
        parent::__construct();
        if (!$this->session->userdata('is_logged')) {
            redirect('Index'); // Kembalikan ke beranda jika belum login
        }
    }
}
```

---

## 3. INTEGRASI & PROXY API EKSTERNAL

### 🌐 3.1 Integrasi API SiKumbang Tapera
Data perumahan diambil langsung dari server Tapera menggunakan cURL di sisi backend untuk menghindari pemblokiran CORS browser.

*   **Pencarian Perumahan:** `Index::cari_wil()` memanggil URL `https://sikumbang.tapera.go.id/ajax/lokasi/search` dengan parameter wilayah (Jateng = `33`), kata kunci, pengurutan, dan paginasi.
*   **Paginasi AJAX (Load More):** Diatur oleh method `Index::load_more()` yang merender view parsial `temp_rumah.php` untuk di-*append* menggunakan jQuery ke container utama di beranda.

### 🌐 3.2 Library Ternak Web API (`Ternak_api.php`)
Menghubungkan aplikasi ke server `apiternak.krsjawa3.com` melalui library kustom `application/libraries/Ternak_api.php`:

*   **Caching Request:** Menggunakan caching *per-request* di memori (`$_site_data_cache`) untuk memastikan query berulang tidak memicu pemanggilan HTTP baru yang memperlambat server.
*   **Ekstraksi Data:** Menggunakan metode gabungan local dan inherited data untuk menyusun katalog berita, regulasi, dan prototipe bank desain.

---

## 🖼️ 4. SPESIFIKASI PROXY CACHE FOTO (`buka_foto`)

Untuk menanggulangi masalah pemblokiran gambar (Mixed Content) dan proteksi *hotlinking* dari server SiKumbang, diimplementasikan proxy cache dinamis pada `Index::buka_foto()`:

1.  **MD5 Unique Hashing:** Nama file asli di-hash menggunakan MD5 untuk menghasilkan nama file cache lokal yang unik (contoh: `d2b7ac9e...jpg`).
2.  **Pengecekan Cache Lokal:**
    *   Jika file sudah ada di folder `assets/cache_foto/`, langsung sajikan ke browser menggunakan header cache 30 hari.
    *   Jika belum ada, unduh gambar menggunakan cURL, simpan ke folder cache lokal, dan tampilkan ke browser.
3.  **Graceful Fallback:** Jika server Tapera offline atau file gambar corupt, backend menyajikan placeholder otomatis via `https://placehold.co/600x400?text=No+Image` agar tampilan web tidak pecah.

---

## 📂 5. STRUKTUR DIREKTORI (Target Clean)

Berikut adalah pemetaan folder final yang bersih untuk proyek `klinik_new`:

```
klinik_new/
├── .htaccess                           # Blokir direktori dan filter SQLi/XSS dasar
├── index.php                           # Front controller
├── composer.json                       # Dependensi package (Dotenv, dll)
├── assets/                             # Aset statis frontend
│   ├── cache_foto/                     # Folder penyimpanan cache gambar Tapera
│   ├── css/
│   ├── js/
│   └── img/
├── docs/                               # Dokumen Analisis & Rencana Resmi (NEW)
│   ├── AKUN_LOGIN.md
│   ├── DATABASE_DESIGN_DOCUMENT.md
│   ├── IMPLEMENTATION_ROADMAP.md
│   ├── PRODUCT REQUIREMENTS DOCUMENT.md
│   ├── SECURITY_DESIGN_DOCUMENT.md
│   └── TECHNICAL_DESIGN_DOCUMENT.md
├── application/                        # Kode aplikasi backend
│   ├── config/
│   │   ├── config.php                  # Pengaturan CSRF & Sesi
│   │   ├── database.php                # Koneksi DB
│   │   ├── google.php                  # Kredensial OAuth (Secure Mode)
│   │   └── ternak_api.php              # Kredensial API Ternak
│   ├── core/
│   │   └── MY_Controller.php           # Base Controller Hierarchy (NEW)
│   ├── controllers/
│   │   ├── Auth.php                    # Otentikasi Google
│   │   ├── Index.php                   # Portal Utama & AJAX Load More
│   │   └── Umum.php                    # Forum & Layanan Spasial
│   ├── libraries/
│   │   └── Ternak_api.php              # Integrasi API Ternak Web
│   ├── models/
│   │   ├── Forum_model.php             # Database forum
│   │   ├── User_model.php              # Database user Google
│   │   └── Buka_peta.php               # Database GIS & Saluran
│   └── views/                          # Halaman antarmuka
│       ├── awal.php                    # Beranda utama
│       ├── detail.php                  # Detail utas forum
│       ├── registrasi.php              # Form profil registrasi
│       └── layout/
│           ├── head.php                # Security headers & assets
│           ├── nav.php                 # Dynamic navigation
│           └── footer.php              # Footer layout
```
