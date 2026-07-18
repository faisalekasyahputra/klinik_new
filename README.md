# 🏠 Klinik PKP — Panduan Setup Cepat

> **Klinik Perumahan & Kawasan Permukiman** — Disperakim Provinsi Jawa Tengah  
> Portal informasi perumahan subsidi, data spasial, dan konsultasi terpadu.

---

## ⚡ Quick Start (5 Menit)

### 1. Extract & Pindahkan
```
Ekstrak ZIP → pindahkan ke folder htdocs (XAMPP/Laragon)
Contoh: C:\xampp\htdocs\klinik_new\
```

### 2. Buat File `.env`
Copy `.env.example` → `.env`, lalu isi:
```env
SITE_URL=http://localhost/klinik_new/

DB_HOST=localhost
DB_NAME=klinikpkp
DB_USER=root
DB_PASS=

KPKP_DATA_KEY=<minta ke lead developer>
KPKP_DATA_PEPPER=<minta ke lead developer>

GOOGLE_CLIENT_ID=<opsional, untuk Google Login>
GOOGLE_CLIENT_SECRET=<opsional>
GOOGLE_REDIRECT_URI=http://localhost/klinik_new/Auth/google_callback

GEMINI_API_KEY=<opsional, untuk fitur AI>
```

> ⚠️ **PENTING:** Kunci enkripsi `KPKP_DATA_KEY` dan `KPKP_DATA_PEPPER` wajib diminta ke lead developer. Tanpa ini, fitur yang melibatkan data NIK/Alamat tidak akan berfungsi.

### 3. Install Composer Dependencies
```bash
cd klinik_new
composer install
```

### 4. Buat Database
Buka **phpMyAdmin** (`http://localhost/phpmyadmin`):
1. Buat database baru: `klinikpkp` (collation: `utf8_general_ci`)
2. Import file: `docs/engineering/schema_klinikpkp.sql`

### 5. Jalankan
Buka browser: **http://localhost/klinik_new/**

---

## 📁 Struktur Proyek

```
klinik_new/
├── application/           ← Source code utama (MVC)
│   ├── config/            ← Konfigurasi (database, routes, dll)
│   ├── controllers/       ← Logic bisnis (22 controller)
│   ├── core/              ← MY_Controller (base controller)
│   ├── helpers/            ← Helper functions
│   ├── libraries/         ← Library kustom (Encryption, API)
│   ├── models/            ← Database models
│   └── views/             ← Tampilan (modular per fitur)
│       ├── layouts/       ← Template (nav, head, footer)
│       └── pages/         ← Halaman per modul
├── assets/                ← CSS, JS, gambar
│   ├── css/
│   ├── js/
│   └── img/
├── docs/                  ← 📖 Dokumentasi lengkap (BACA INI!)
│   ├── architecture/      ← Desain teknis, ERD, security threat model
│   ├── engineering/       ← Schema SQL, setup guide, alur auth
│   ├── product/           ← PRD, roadmap, audit keamanan
│   ├── design/            ← Design tokens & color scheme
│   ├── meetings/          ← Notulensi rapat
│   └── archive/           ← Dokumen historis
├── system/                ← CodeIgniter 3 core (JANGAN EDIT)
├── vendor/                ← Composer dependencies
├── .env                   ← Environment (TIDAK DI-SHARE)
├── .env.example           ← Template environment
├── composer.json          ← PHP dependencies
└── index.php              ← Entry point
```

---

## 📖 Dokumentasi Lengkap

Baca file-file di folder `docs/` untuk pemahaman mendalam:

| File | Isi |
|------|-----|
| [`AGENTS.md`](AGENTS.md) | Panduan untuk AI coding agent |
| [`README.md`](docs/README.md) | Index dokumentasi |
| [`TECHNICAL_DESIGN_DOCUMENT.md`](docs/architecture/TECHNICAL_DESIGN_DOCUMENT.md) | Arsitektur & struktur kode |
| [`DATABASE_DESIGN_DOCUMENT.md`](docs/architecture/DATABASE_DESIGN_DOCUMENT.md) | Kamus data & relasi tabel |
| [`SECURITY_DESIGN_DOCUMENT.md`](docs/architecture/SECURITY_DESIGN_DOCUMENT.md) | Keamanan (enkripsi, CSRF, OAuth) |
| [`PRODUCT_REQUIREMENTS_DOCUMENT.md`](docs/product/PRODUCT_REQUIREMENTS_DOCUMENT.md) | Spesifikasi fitur & PRD |
| [`IMPLEMENTATION_ROADMAP.md`](docs/product/IMPLEMENTATION_ROADMAP.md) | Roadmap pengembangan |
| [`AKUN_LOGIN.md`](docs/engineering/AKUN_LOGIN.md) | Cara kerja autentikasi |
| [`changelog_090626_12.39WIB_.md`](docs/archive/changelog_090626_12.39WIB_.md) | Riwayat perubahan |

---

## 🔧 Tech Stack

| Layer | Teknologi |
|-------|-----------|
| **Backend** | CodeIgniter 3 (PHP 8.x) |
| **Frontend** | Tailwind CSS + Alpine.js + Vanilla JS |
| **Database** | MySQL / MariaDB |
| **Auth** | Email/Password + Google OAuth 2.0 |
| **Enkripsi** | AES-256-GCM (data PII) |
| **API** | Sikaper, Sikunang, Siperum, API Ternak |

---

## 🗄️ Tabel Database (14 tabel + legacy)

| Tabel | Fungsi |
|-------|--------|
| `usr_users` | Data pengguna (auth, profil) |
| `usr_documents` | Dokumen user |
| `sf_programs` | Program perumahan |
| `sf_program_kategori` | Kategori program |
| `sf_housing_queue` | Antrean kelayakan (pending/approved/rejected) |
| `forum_diskusi` | Thread forum |
| `forum_komentar` | Komentar forum (nested) |
| `forum_likes` | Like pada thread |
| `sys_menu` | Menu navigasi |
| `sys_multi` | Data perumahan |
| `sys_settings` | Konfigurasi sistem |
| `chat_rooms` | Ruang chat konsultasi |
| `chat_messages` | Pesan chat |
| `data_sosmed_perumahan` | Sosmed pengembang |

> Ada juga tabel legacy tanpa prefix (`kondisi`, `bendung`, `irigasi`, `saluran_pembuang`) yang dipakai dinamis oleh `Buka_peta.php` tapi tidak ada di `schema_klinikpkp.sql`.

---

## ❓ Troubleshooting

| Masalah | Solusi |
|---------|--------|
| **Blank page / Error 500** | Cek `.env` sudah dibuat dan terisi benar |
| **Database error** | Pastikan database `klinikpkp` ada dan schema sudah diimport |
| **CSS tidak muncul** | Pastikan `base_url` di `.env` sesuai path folder kamu |
| **"Class not found"** | Jalankan `composer install` |
| **Google Login gagal** | Normal jika belum setup Google OAuth credentials |

---

*Klinik PKP — diperbarui 18 Juli 2026*
