# Klinik PKP

**Klinik Perumahan dan Kawasan Permukiman** - Dinas Perumahan Rakyat dan Kawasan Permukiman (Disperakim) Provinsi Jawa Tengah.

Portal layanan dan informasi perumahan: pendataan warga dan rekomendasi program, direktori serta sertifikasi pengembang (SRP2), aduan, konsultasi, KKN dan magang, rekam data capaian kabupaten/kota, dan bank data (statistik, buku data, kawasan kumuh).

> Repo ini **publik** atas permintaan dinas. Jangan pernah meng-commit kredensial, IP, host atau nama database, path server, nama berkas backup, atau rincian celah yang belum ditambal. Lihat [`AGENTS.md`](AGENTS.md) untuk aturan lengkapnya.

---

## Teknologi

| Lapisan | Teknologi |
|---|---|
| Backend | CodeIgniter 3, PHP 8.1 atau lebih baru |
| Frontend | Tailwind CSS (CSS statis hasil panen), Alpine.js, JavaScript biasa |
| Database | MySQL / MariaDB, `utf8mb4` |
| Autentikasi | Email dan sandi dengan OTP email, Google OAuth 2.0, sesi tunggal per akun |
| Keamanan data | Enkripsi AES-256-GCM untuk data pribadi, CSRF, pembatas laju, header keamanan |
| Integrasi | SIMPERUM (data warga), SIKUMBANG (unit rumah), SIKAPER (kawasan kumuh) |
| Dependensi Composer | Google API Client, PhpSpreadsheet, FPDF, Web Push |

---

## Menjalankan di lokal

Contoh berikut memakai XAMPP di Windows. Laragon atau Apache + PHP lain juga bisa.

### 1. Clone

```bash
cd C:/xampp/htdocs && git clone https://github.com/faisalekasyahputra/klinik_new.git
```

Branch kerja utama adalah `main`. Lihat [Alur kontribusi](#alur-kontribusi) sebelum mengubah apa pun.

### 2. Dependensi

```bash
cd klinik_new && composer install
```

### 3. Berkas `.env`

Salin `.env.example` menjadi `.env`, lalu isi. Semua variabel yang dibaca kode tercantum di `.env.example` beserta keterangannya. Minimal untuk lokal:

```env
SITE_URL=http://localhost/klinik_new/
DB_HOST=localhost
DB_NAME=klinikpkp
DB_USER=root
DB_PASS=
KPKP_DATA_KEY=<minta ke lead developer>
KPKP_DATA_PEPPER=<minta ke lead developer>
SIMPERUM_MODE=simulation
```

`KPKP_DATA_KEY` dan `KPKP_DATA_PEPPER` wajib; tanpanya data terenkripsi (NIK, alamat) tidak bisa dibaca. Di lokal, SIMPERUM **harus** tetap `simulation` supaya data pribadi warga sungguhan tidak masuk ke mesin pengembang.

### 4. Database

1. Buat database `klinikpkp` dengan collation `utf8mb4_unicode_ci`.
2. Ikuti [`docs/engineering/SETUP_DATABASE.md`](docs/engineering/SETUP_DATABASE.md).
3. Jalankan migrasi. **Wajib**, karena sumber kebenaran skema adalah `application/migrations/`, bukan berkas `.sql`:

```bash
php index.php migrate
```

### 5. Buka

`http://localhost/klinik_new/`

Akun uji lokal per peran dapat dibuat dengan `docs/engineering/seed_agen_peran.php`; daftarnya ada di [`docs/engineering/AGEN_PERAN.md`](docs/engineering/AGEN_PERAN.md). Akun itu hanya untuk localhost.

---

## Pengujian

Uji berupa skrip PHP di `docs/engineering/uji_*.php` (fungsional, per peran, regresi tampilan) dan `tests/` (form keamanan). Satu perintah menjalankan semuanya terhadap situs lokal:

```bash
php docs/engineering/jalankan_semua.php
```

Hasil yang diterima sebelum rilis: **0 merah dan 0 bisu**. Uji berjalan terhadap `localhost` dan database lokal, jadi jangan memakai localhost untuk hal lain selama suite berjalan (akun bersesi tunggal saling menendang).

---

## Alur kontribusi

1. Buat branch dari `main` (`fix/...`, `feat/...`, `docs/...`).
2. Kerjakan dan uji di lokal.
3. Buka pull request ke `main`, lalu merge.
4. **Rilis** dilakukan pemilik proyek: suite penuh 0 merah, lalu `main` dicerminkan (fast-forward) ke branch deploy `feature/homepage-portal-v2`, yang tayang otomatis ke production. Jangan push langsung ke branch deploy.

Perubahan skema wajib berupa berkas migrasi baru di `application/migrations/` dan menaikkan `$config['migration_version']`. Urutan rilis bermigrasi ada di [`AGENTS.md`](AGENTS.md) §0a.

Pesan commit memakai bahasa Indonesia dengan prefix conventional commit (`fix:`, `feat:`, `docs:`, `test:`, `chore:`).

---

## Struktur

```
klinik_new/
├── application/
│   ├── config/        konfigurasi (routes, modul dasbor, pembatas laju, keamanan)
│   ├── controllers/   controller
│   ├── core/          MY_Controller: hierarki controller dasar dan penjaga peran
│   ├── helpers/       fungsi bantu
│   ├── libraries/     gateway API, enkripsi, pemindai unggahan, OTP
│   ├── migrations/    SUMBER KEBENARAN skema database
│   ├── models/
│   └── views/         admin/ (cangkang dasbor), pages/ (portal publik), components/
├── assets/            CSS, JS, gambar, font
├── docs/              dokumentasi dan skrip uji (lihat docs/README.md)
├── system/            inti CodeIgniter 3 (jangan diedit)
├── tests/             uji form keamanan
├── .env.example       templat variabel lingkungan
└── index.php
```

Folder lokal yang sengaja tidak masuk repo (sudah di `.gitignore`): `vendor/`, `uploads/`, `dev-scripts/`, `local-assets/`, `_arsip/`, keluaran sementara (`tmp/`, `output/`, `outputs/`), dan berkas unggahan pengguna di bawah `assets/`.

---

## Dokumentasi

| Dokumen | Isi |
|---|---|
| [`AGENTS.md`](AGENTS.md) | **Baca duluan.** Status terkini, catatan rilis, aturan mengikat, dan jebakan yang pernah terjadi |
| [`docs/README.md`](docs/README.md) | Indeks dokumentasi |
| [`docs/architecture/TECHNICAL_DESIGN_DOCUMENT.md`](docs/architecture/TECHNICAL_DESIGN_DOCUMENT.md) | Arsitektur dan struktur kode |
| [`docs/architecture/DATABASE_DESIGN_DOCUMENT.md`](docs/architecture/DATABASE_DESIGN_DOCUMENT.md) | Kamus data dan relasi tabel |
| [`docs/architecture/SECURITY_DESIGN_DOCUMENT.md`](docs/architecture/SECURITY_DESIGN_DOCUMENT.md) | Model ancaman, enkripsi, CSRF, OAuth |
| [`docs/engineering/PANDUAN_HOSTING.md`](docs/engineering/PANDUAN_HOSTING.md) | Memasang atau memindahkan situs ke server lain |
| [`docs/design/DESIGN_SYSTEM.md`](docs/design/DESIGN_SYSTEM.md) | Token desain dan komponen tampilan |

---

## Masalah umum

| Gejala | Penyebab dan solusi |
|---|---|
| Halaman kosong / galat 500 | `.env` belum dibuat atau isinya salah |
| `Table '...' doesn't exist` | Migrasi belum dijalankan: `php index.php migrate` |
| CSS tidak termuat | `SITE_URL` di `.env` tidak cocok dengan path folder |
| `Class not found` | `composer install` belum dijalankan |
| Login Google gagal di lokal | Normal bila kredensial OAuth lokal belum diisi |
| Uji pendaftaran merah berantai | Batas kiriman OTP per IP di database lokal habis karena suite diulang dalam satu jam; lihat catatan rilis 4-5 Okt 2026 di `AGENTS.md` |
| MySQL XAMPP tidak mau start | Lihat catatan MariaDB di `AGENTS.md`; jangan menghapus `.frm`, `.ibd`, atau `ibdata1` |

---

*Diperbarui 5 Oktober 2026.*
