# TECHNICAL DESIGN DOCUMENT (TDD)
## Klinik PKP — Arsitektur Backend & Pengembangan Kode (CodeIgniter 3)
**Terakhir Diperbarui:** 9 Juni 2026

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
├── .env                                # Environment variables (DB, OAuth, Encryption keys)
├── index.php                           # Front controller
├── composer.json                       # Dependensi package (Dotenv, dll)
├── tailwind.config.js                  # Konfigurasi Tailwind CSS
├── assets/                             # Aset statis frontend
│   ├── cache_foto/                     # Cache gambar Tapera (proxy)
│   ├── css/
│   ├── js/
│   └── img/
├── docs/                               # Dokumen teknis & roadmap
│   ├── AKUN_LOGIN.md
│   ├── ANALISIS_DAN_RENCANA_PERBAIKAN.md
│   ├── DATABASE_DESIGN_DOCUMENT.md
│   ├── IMPLEMENTATION_ROADMAP.md
│   ├── PRODUCT REQUIREMENTS DOCUMENT.md
│   ├── SECURITY_DESIGN_DOCUMENT.md
│   └── TECHNICAL_DESIGN_DOCUMENT.md
├── application/                        # Kode aplikasi backend
│   ├── config/
│   │   ├── config.php                  # CSRF, Sesi, Encryption
│   │   ├── database.php                # Koneksi DB
│   │   ├── google.php                  # Kredensial OAuth
│   │   ├── profanity.php               # Daftar kata kasar (filter forum)
│   │   └── ternak_api.php              # Kredensial API Ternak
│   ├── core/
│   │   └── MY_Controller.php           # Base Controller Hierarchy
│   ├── controllers/
│   │   ├── Auth.php                    # Login, Register, OAuth, Onboarding, Hapus Akun
│   │   ├── Index.php                   # Portal Utama & AJAX Load More
│   │   └── Umum.php                    # Forum, Layanan, Spasial
│   ├── helpers/
│   │   └── forum_helper.php            # Helper fungsi forum
│   ├── libraries/
│   │   ├── Ternak_api.php              # Integrasi API Ternak Web
│   │   └── Encryption_lib.php          # AES-256-GCM untuk NIK & Alamat
│   ├── models/
│   │   ├── Forum_model.php             # Database forum (diskusi, komentar, likes)
│   │   ├── User_model.php              # CRUD user, sinkronisasi, hapus akun
│   │   ├── Chat_model.php              # Chat rooms & messages
│   │   └── Buka_peta.php               # Database GIS & Saluran
│   └── views/
│       ├── index.php                   # Master layout
│       ├── layouts/
│       │   ├── head.php                # Meta, CSS, Security headers
│       │   ├── nav.php                 # Navbar (desktop + mobile, avatar fallback)
│       │   └── footer.php              # Footer layout
│       └── pages/
│           ├── home/
│           │   └── awal.php            # Homepage (hero, layanan, bank desain, dll)
│           ├── auth/
│           │   ├── login.php           # Login (email/username + Google)
│           │   ├── register.php        # Pendaftaran tradisional
│           │   ├── onboarding.php      # Onboarding (username, password, role, NIK)
│           │   └── verify_pending.php  # Dummy verifikasi email
│           ├── pengaturan/
│           │   └── index.php           # Pengaturan profil + hapus akun
│           ├── umum/
│           │   ├── umum.php            # Halaman layanan masyarakat umum
│           │   └── forum.php           # Forum diskusi komunitas
│           ├── perumahan/
│           │   └── detail.php          # Detail perumahan
│           ├── artikel/
│           │   └── detail_artikel.php  # Detail berita/artikel
│           └── data_spasial/
│               └── sebaran.php         # Peta sebaran GIS
```

---
*Dokumen ini diperbarui otomatis oleh Antigravity AI Coding Assistant — 9 Juni 2026.*
