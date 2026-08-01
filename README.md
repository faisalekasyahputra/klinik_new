# 🏠 Klinik PKP — Panduan Setup Cepat

> **Klinik Perumahan & Kawasan Permukiman** — Disperakim Provinsi Jawa Tengah  
> Portal informasi perumahan subsidi, data spasial, dan konsultasi terpadu.

---

## ⚡ Quick Start (5 Menit)

### 1. Clone Repo
Repo ini **privat** — kamu harus sudah diundang sebagai collaborator di GitHub dulu.
Clone langsung ke dalam folder `htdocs` (XAMPP/Laragon):

```bash
cd C:/xampp/htdocs && git clone -b feature/homepage-portal-v2 https://github.com/faisalekasyahputra/klinik_new.git
```

> ⚠️ **`-b feature/homepage-portal-v2` itu wajib, bukan opsional.** Tanpa itu kamu
> dapat `main`, dan `main` **beku sejak 19 Juli 2026** — kodenya ketinggalan jauh dan
> DB-nya belum pernah dimigrasi sama sekali. `main` disimpan untuk rilis akhir saja.
> Branch kerja yang hidup selalu tercatat di [`AGENTS.md`](AGENTS.md) §0a — kalau
> suatu saat pindah, percayai tabel di sana, bukan baris ini.
>
> 🔴 **Branch ini auto-deploy ke PRODUCTION.** Setiap `git push` ke
> `feature/homepage-portal-v2` langsung tayang di situs asli. Tidak ada lagi staging
> terpisah. Kerjakan dan uji di lokal; jangan push kecuali memang mau merilis.

Git for Windows akan membuka jendela login GitHub di browser saat pertama kali clone;
setelah itu kredensialnya tersimpan dan tidak ditanya lagi. Kalau jendela itu tidak
muncul dan kamu malah dimintai password di terminal, pakai GitHub CLI:

```bash
gh auth login && gh repo clone faisalekasyahputra/klinik_new -- -b feature/homepage-portal-v2
```

> ❌ **404 / "repository not found"** saat clone hampir selalu berarti akun GitHub-mu
> belum punya akses, bukan URL-nya salah. Repo privat memang tampil sebagai 404 ke
> orang luar. Minta undangan collaborator ke lead developer.

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

### 5. Jalankan Migrasi — WAJIB, jangan dilewati
```bash
php index.php migrate
```

> ⚠️ **Berkas `schema_klinikpkp.sql` itu snapshot lama, bukan skema terbaru.** Tanpa langkah ini kamu akan kehilangan tabel `aduan`, `kabupaten`, `bidang`, `kkn_magang_pendaftaran`, seluruh tabel `srp2_*`, kolom `reviewed_by`/`reviewed_at`, dan semua foreign key — aplikasi akan error di banyak halaman.
>
> Sumber kebenaran skema adalah `application/migrations/`, bukan berkas `.sql`. Perintah ini juga yang dipakai untuk menyamakan skema DB manapun yang sedang ditunjuk `.env`.

### 6. Jalankan
Buka browser: **http://localhost/klinik_new/**

---

## 📁 Struktur Proyek

```
klinik_new/
├── application/           ← Source code utama (MVC)
│   ├── config/            ← Konfigurasi (database, routes, dll)
│   ├── controllers/       ← Logic bisnis (29 controller)
│   ├── core/              ← MY_Controller (hierarki base controller + guard role)
│   ├── helpers/            ← Helper functions
│   ├── libraries/         ← Library kustom (Encryption, API)
│   ├── migrations/        ← ⭐ SUMBER KEBENARAN skema DB (01–14)
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
| [`AGENTS.md`](AGENTS.md) | ⭐ **Baca duluan** — status terkini, aturan mengikat, daftar jebakan |
| [`README.md`](docs/README.md) | Index dokumentasi |
| [`PEMBACAAN_CODEBASE_26JUL2026.md`](docs/engineering/PEMBACAAN_CODEBASE_26JUL2026.md) | Peta 8 subsistem + 141 temuan |
| [`AUDIT_SISTEM_ROLE_RINGKASAN.md`](docs/engineering/AUDIT_SISTEM_ROLE_RINGKASAN.md) | Ringkasan audit 5 peran |
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

## 🗄️ Tabel Database (23 tabel + legacy)

Prefix menandai domainnya — tabel baru wajib mengikuti pola ini.

| Tabel | Fungsi |
|-------|--------|
| `usr_users` | Data pengguna (auth, profil, scope wilayah/bidang) |
| `usr_documents` | Dokumen user |
| `sf_programs` | Program perumahan |
| `sf_program_kategori` | Kategori program |
| `sf_housing_queue` | Antrean kelayakan + tiket `PKP-XXXXXX` |
| `forum_diskusi` | Thread forum |
| `forum_komentar` | Komentar forum (nested lewat `reply_to`) |
| `forum_likes` | Like pada thread & komentar |
| `srp2_registrations` | Pendaftaran sertifikasi pengembang |
| `srp2_documents` | 14 dokumen persyaratan SRP2 |
| `srp2_certified_developers` | Direktori publik pengembang bersertifikat |
| `aduan` | Pengaduan masyarakat (per bidang) |
| `kkn_magang_pendaftaran` | Pendaftaran KKN & Magang |
| `kabupaten` | 35 kabupaten/kota Jateng (kode Kemendagri) |
| `bidang` | 5 bidang penanganan aduan |
| `sys_menu` | Menu navigasi |
| `sys_multi` | Data perumahan |
| `sys_settings` | Konfigurasi sistem |
| `sys_ticket_lookup_limits` | Rate limit lookup tiket publik |
| `chat_rooms`, `chat_messages` | ⚠️ **ADA tapi menganggur** — lihat catatan di bawah |
| `data_sosmed_perumahan` | Sosmed pengembang |
| `migrations` | Versi migrasi yang sudah dijalankan |

> ⚠️ **Jebakan:** `chat_rooms`/`chat_messages` ada di DB tapi tidak dipakai kode manapun. Fitur chat yang berjalan menulis ke `tb_chat` — tabel yang **tidak ada di skema maupun migrasi**, sehingga chat gagal di instalasi bersih. Lihat `AGENTS.md` §18.

> Ada juga tabel legacy tanpa prefix (`kondisi`, `bendung`, `irigasi`, `saluran_pembuang`) yang dipakai dinamis oleh `Buka_peta.php` — model itu sendiri sudah tidak dipanggil dari manapun.

---

## ❓ Troubleshooting

| Masalah | Solusi |
|---------|--------|
| **Blank page / Error 500** | Cek `.env` sudah dibuat dan terisi benar |
| **Database error** | Pastikan database `klinikpkp` ada, schema diimport, **dan `php index.php migrate` sudah dijalankan** |
| **`Table '...' doesn't exist`** | Hampir selalu karena migrasi belum dijalankan — ulangi langkah 5 |
| **CSS tidak muncul** | Pastikan `base_url` di `.env` sesuai path folder kamu |
| **"Class not found"** | Jalankan `composer install` |
| **Google Login gagal** | Normal jika belum setup Google OAuth credentials |
| **MySQL XAMPP tidak mau start** | Cek `mysql/data/multi-master.info` — kalau isinya potongan teks log, singkirkan berkas itu beserta `master-*.info` dan `mysql-relay-bin-*`. Jangan sentuh `.frm`/`.ibd`/`ibdata1` |

---

## ⚠️ Sebelum ikut mengembangkan

1. **Baca [`AGENTS.md`](AGENTS.md) lebih dulu** — di sana ada status terkini, aturan yang mengikat, dan daftar jebakan yang sudah pernah memakan korban.
2. **Jangan sentuh branch `main`** tanpa perintah eksplisit — push ke sana langsung merilis ke production tanpa konfirmasi.
3. **Jangan commit script atau aset satu-kali-pakai ke akar repo** — semuanya ikut ter-deploy dan bisa diakses publik. Taruh di `dev-scripts/` atau `local-assets/` yang sudah di-`.gitignore`.

---

*Klinik PKP — diperbarui 27 Juli 2026*
