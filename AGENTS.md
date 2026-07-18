# AGENTS.md — Panduan untuk AI Coding Agent

> Dokumen ini adalah peta navigasi teknis untuk agent (Claude Code, Cursor, dll) yang bekerja di repo ini.
> Ditulis berdasarkan pembacaan langsung kode per 18 Juli 2026 — bukan asumsi dari dokumen lama.
> Untuk spesifikasi produk/bisnis lengkap, lihat [`docs/README.md`](docs/README.md) (index dokumentasi resmi).
> ⚠️ [`README.md`](README.md) di root sudah usang (v1.0, 9 Juni 2026) — jangan jadikan acuan struktur kode, jadikan acuan ini.

---

## 1. Identitas Proyek

- **Nama:** Klinik PKP (Klinik Perumahan dan Kawasan Permukiman)
- **Instansi:** Dinas Perumahan Rakyat & Kawasan Permukiman Prov. Jawa Tengah
- **Stack:** CodeIgniter 3.1.13 (PHP 8.x), MySQL/MariaDB, Tailwind CSS, Alpine.js, Leaflet.js
- **Bahasa dokumentasi & komentar kode:** Bahasa Indonesia
- **Production:** `https://palegreen-mink-703421.hostingersite.com/` — deploy via Git auto-deploy (GitHub → Hostinger webhook)

## 2. Setup Lokal

```bash
composer install
# copy .env.example -> .env, isi DB_*, KPKP_DATA_KEY/PEPPER, GOOGLE_*, RECAPTCHA_*, GEMINI_API_KEY
# import docs/engineering/schema_klinikpkp.sql ke database bernama sesuai DB_NAME
```

- Entry point: [`index.php`](index.php) (root, bukan `application/`)
- `.env` dibaca via `getenv()` di `application/config/*.php` (lihat `config.php` untuk `base_url`)
- **Jangan edit** folder `system/` (core CodeIgniter) atau `vendor/`

## 3. Struktur Direktori

```
application/
├── config/        # database.php, routes.php, autoload.php (autoload: email, session, database; helper: url, file, ternak)
├── controllers/    # 22 file, lihat tabel §4
├── core/           # MY_Controller.php — base class hierarchy, lihat §5
├── helpers/        # forum_helper.php, ternak_helper.php
├── hooks/          # kosong
├── libraries/      # Encryption_lib, Smart_filter, Sikaper_api, Ternak_api — lihat §6
├── models/         # 8 file, lihat §7
└── views/pages/    # 20 subfolder modular per fitur (admin, auth, umum, program, dll)
docs/               # Dokumentasi resmi lengkap — BACA docs/README.md dulu sebelum menggali di sini
assets/             # css/js/img
uploads/            # file upload user
```

**`dev-scripts/` dan `local-assets/` (gitignored, tidak ter-track git):** script debug/migrasi one-off (`finish_refactor.ps1`, dst) dan aset besar non-web (rekaman rapat, gambar referensi) hidup di sini secara lokal, bukan di repo — sebelumnya sempat ke-commit di root dan ikut ter-deploy ke production (salah satunya sempat jadi celah keamanan aktif), sudah dibersihkan. **Jangan pernah commit script/aset satu-kali-pakai ke root repo** — taruh di salah satu folder ini, keduanya sudah di `.gitignore`.

## 4. Controllers (`application/controllers/`)

| Controller | Fungsi Utama |
|---|---|
| `Auth.php` | Login, register, Google OAuth, onboarding multi-step, hapus akun |
| `Index.php` | Portal utama, AJAX load more, proxy foto Tapera, banyak clean-URL routes (lihat `routes.php`) |
| `Umum.php` | Forum diskusi (thread, nested reply, likes), layanan masyarakat |
| `Pengembang.php` | Profil pengembang, SP2 |
| `Program.php` | Smart Filter — wizard diagnosa kelayakan perumahan (NIK → SIMPERUM mock) |
| `Chat.php` | Chat konsultasi |
| `Statistika.php` | Data statistik & chart |
| `Sikumbang.php`, `Sikaper.php`, `Sikunang.php`, `Siperum.php` | Integrasi API eksternal pemerintah |
| `Pengaturan.php` | Profil user & hapus akun |
| `Admin.php`, `Admin_Content.php`, `Admin_Dashboard.php`, `Admin_Settings.php`, `Admin_Users.php` | Panel admin (extends `Admin_Controller`) |
| `Bank_desain.php`, `Berita.php`, `Kemitraan.php`, `Kabupaten.php`, `User_Profile.php` | Fitur pendukung, ukuran kecil |

Controller besar (`Auth.php` ~26KB, `Index.php` ~20KB, `Umum.php` ~17KB) — kandidat untuk dipecah kalau melakukan refactor besar.

## 5. Base Controller Hierarchy (`application/core/MY_Controller.php`)

```
CI_Controller
└── MY_Controller          # security headers (CSP-lite: X-Frame-Options, HSTS, Permissions-Policy dll) di SETIAP request
    ├── Public_Controller   # untuk route publik, tidak ada guard tambahan
    └── Admin_Controller    # redirect ke Auth/login jika !is_logged || role !== 'admin'; punya render_admin()
```

Helper session di `MY_Controller`: `is_logged_in()`, `get_user_id()`, `sanitize_redirect()` (cegah open-redirect — cek ini sebelum pakai `redirect($_GET['next'])` gaya apa pun).

> Catatan: TIDAK ada kelas `Auth_Controller`. Kalau menemukan referensi itu di dokumen lama/checkpoint, itu keliru — controller auth cukup pakai `Public_Controller` biasa + cek session manual.

## 6. Libraries (`application/libraries/`)

| Library | Fungsi |
|---|---|
| `Encryption_lib.php` | AES-256-GCM encrypt/decrypt + SHA-256 deterministic hash (untuk lookup NIK terenkripsi). Kunci dari `.env`: `KPKP_DATA_KEY`, `KPKP_DATA_PEPPER`. **JANGAN GANTI kunci ini setelah data terenkripsi** — data akan hilang permanen. |
| `Smart_filter.php` | Kalkulasi Desil UN HABITAT Matrix untuk wizard diagnosa kelayakan di `Program.php` |
| `Sikaper_api.php` | Integrasi API Sikaper |
| `Ternak_api.php` | Integrasi API Ternak Web (katalog bank desain) |

## 7. Models (`application/models/`)

`Auth_model`, `User_model`, `Forum_model`, `Program_model`, `Chat_model`, `Admin_model`, `Setting_model`, `Buka_peta` (GIS/spasial — pakai nama tabel dinamis via parameter, lihat §8 soal tabel legacy).

## 8. Database

Schema migrasi resmi: [`docs/engineering/schema_klinikpkp.sql`](docs/engineering/schema_klinikpkp.sql) (tanpa data dummy).

**Konvensi prefix modular** (tabel baru ikuti pola ini):
- `usr_` — akun & data user (`usr_users`, `usr_documents`)
- `sf_` — Smart Filter (`sf_programs`, `sf_program_kategori`, `sf_housing_queue`)
- `forum_` — forum diskusi (`forum_diskusi`, `forum_komentar`, `forum_likes`)
- `sys_` — sistem (`sys_menu`, `sys_multi`, `sys_settings`)
- `chat_` — konsultasi (`chat_rooms`, `chat_messages`)
- `data_sosmed_perumahan` — sosmed pengembang

**Tabel legacy tanpa prefix** (dipakai lewat parameter dinamis di `Buka_peta.php`, tidak ada di `schema_klinikpkp.sql` — asumsikan sudah ada di DB dari baseline sebelum migrasi ini): `kondisi`, `bendung`, `irigasi`, `saluran_pembuang`.

Jangan hapus tabel existing saat menambah fitur baru — tambah tabel baru mengikuti konvensi prefix di atas.

## 9. Keamanan — JANGAN DIRUSAK

Fondasi ini sudah solid (OWASP + UU PDP compliant), perlakukan sebagai kontrak, bukan implementation detail yang bebas diubah:

1. CSRF protection aktif global (CodeIgniter native)
2. Google OAuth dengan state token kriptografis + anti-redirect (`sanitize_redirect()`)
3. Anti-IDOR — ID user selalu dari `session`/`get_user_id()`, **bukan** dari POST/GET body
4. `Admin_Controller` guard berbasis session role, backend-enforced (bukan hanya sembunyikan UI)
5. AES-256-GCM untuk NIK & Alamat (`Encryption_lib`), SHA-256 deterministic hash untuk lookup
6. Security headers global di `MY_Controller::set_security_headers()`
7. Kredensial hanya lewat `.env`, tidak pernah hardcode

## 10. Routes Penting (`application/config/routes.php`)

Default controller: `Index`. Banyak clean-URL alias ke `Index/*` (`umum`, `profil`, `pengembang`, `simulasi_kpr`, dll), plus alias auth (`login`, `register`, `onboarding`) dan AJAX endpoints (`ajax_articles`, `load_more`, `cari_wil`). Cek file ini sebelum menambah route baru — banyak alias sudah dipakai di frontend, jangan duplikasi path.

## 11. Status & Roadmap

9 dari 10 fase besar selesai (keamanan, OAuth, MY_Controller, forum, enkripsi, UI/UX, navbar, hero/etalase, Smart Filter). Fase 10 (Admin Dashboard untuk validasi manual antrean `sf_housing_queue`) belum dimulai. Detail lengkap: [`docs/product/IMPLEMENTATION_ROADMAP.md`](docs/product/IMPLEMENTATION_ROADMAP.md) dan [`docs/product/ANALISIS_DAN_RENCANA_PERBAIKAN.md`](docs/product/ANALISIS_DAN_RENCANA_PERBAIKAN.md).

Untuk konteks bisnis (arahan rapat, program perumahan, matrix Smart Filter), baca `docs/meetings/` dan `docs/product/PRODUCT_REQUIREMENTS_DOCUMENT.md` — jangan diduplikasi di sini, cek langsung sumbernya karena detail bisnis berubah lebih cepat dari kode.

## 12. Cara Verifikasi Sebelum Percaya Dokumen Lama

Repo ini punya banyak dokumen historis (`docs/archive/`, termasuk `docs/archive/AI_ROADMAP.md`). Sebelum mengandalkan klaim struktur kode dari dokumen manapun (termasuk file ini kalau sudah lama tidak di-update), verifikasi cepat:
- Controller/model list: `ls application/controllers application/models`
- Base controller hierarchy: baca langsung `application/core/MY_Controller.php`
- Tabel DB: `grep "CREATE TABLE" docs/engineering/schema_klinikpkp.sql`

## 13. Frontend & Design System

Warna/tipografi/spacing didokumentasikan di [`docs/design/DESIGN_SYSTEM.md`](docs/design/DESIGN_SYSTEM.md) — **baca itu sebelum menyentuh CSS/warna apa pun**. Ringkasan penting: ada 3 sumber warna yang tidak sinkron (`docs/design/tokens.css` tidak ke-load sama sekali di aplikasi; `assets/css/design-system.css` yang beneran dipakai situs publik; inline `tailwind.config` di `application/views/admin/layouts/head.php` buat panel admin), plus ~1.300 hex literal tertulis manual di 70+ file view karena situs publik tidak punya `tailwind.config`. `docs/design/DESIGN_SYSTEM.md` punya tabel palet kanonis (sudah diverifikasi ke pemakaian nyata di kode) dan daftar warna drift yang masih butuh keputusan — jangan asumsikan `tokens.css` sudah merepresentasikan apa yang tampil di browser.
