# 📅 IMPLEMENTATION ROADMAP
## Peta Jalan Perbaikan & Pengembangan Non-Destruktif Klinik PKP
**Target Workspace:** `c:\xampp\htdocs\klinik_new`

---

## 0. RINGKASAN EKSEKUTIF (Executive Summary)

Peta jalan (roadmap) ini dirancang sebagai panduan langkah demi langkah bagi tim pengembang untuk melakukan perbaikan keamanan mendalam (*security hardening*) dan modernisasi sistem *Klinik PKP* (`klinik_new`). 

**Fokus Utama:**
1.  **Penyelesaian Cepat (Quick Wins):** Menambal kerentanan kritis (IDOR, Google OAuth CSRF, dan Forum Role Impersonation) dalam 1-2 minggu kerja.
2.  **Pemulihan Fungsional:** Mengaktifkan kembali fitur komentar/balasan forum yang lumpuh karena method controller `balas_aksi` yang hilang.
3.  **Modernisasi Arsitektur:** Memodernisasi arsitektur controller menggunakan base class `MY_Controller` dan integrasi `.env` berbasis standar industri.
4.  **Kepatuhan Regulasi:** Mengimplementasikan enkripsi kolom NIK & Alamat guna mematuhi **UU PDP No. 27/2022**.

---

## 1. STRATEGI PELAKSANAAN 5 FASE (Phased Action Plan)

```
┌────────────────────────────────────────────────────────┐
│ FASE 1: PERTAHANAN GLOBAL & CSRF (1-3 Hari)           │
│  • Aktifkan CSRF Protection di config.php               │
│  • Inject CSRF Token ke form-form view aktif            │
│  • Terapkan Security Headers Dasar                      │
└──────────────────────────┬─────────────────────────────┘
                           │
                           ▼
┌────────────────────────────────────────────────────────┐
│ FASE 2: HARDENING GOOGLE AUTH & OAUTH CSRF (2-4 Hari)  │
│  • Tambahkan Dynamic Cryptographic State Token         │
│  • Cegah Phishing Open Redirect di Callback            │
└──────────────────────────┬─────────────────────────────┘
                           │
                           ▼
┌────────────────────────────────────────────────────────┐
│ FASE 3: INTEGRASI MY_Controller & FIX IDOR (3-5 Hari)  │
│  • Terapkan arsitektur base controllers                │
│  • Hapus ID dari input POST update, kunci via session   │
└──────────────────────────┬─────────────────────────────┘
                           │
                           ▼
┌────────────────────────────────────────────────────────┐
│ FASE 4: PEMULIHAN FORUM & IMPERSONASI GUARD (2-3 Hari) │
│  • Tulis method balas_aksi() di controller Umum.php    │
│  • Hapus dropdown Role di HTML, validasi di backend    │
└──────────────────────────┬─────────────────────────────┘
                           │
                           ▼
┌────────────────────────────────────────────────────────┐
│ FASE 5: KEPATUHAN UU PDP & ENKRIPSI DATA (4-6 Hari)   │
│  • Setup phpdotenv untuk environment variables         │
│  • Gunakan Encryption_lib untuk enkripsi NIK & Alamat   │
└────────────────────────────────────────────────────────┘
```

---

## 2. JADWAL DETAIL & ACTION ITEMS HARIAN

### 📍 FASE 1 — Pertahanan Global & CSRF (Minggu 1: Hari 1-3)
*   **Target:** Menghentikan serangan Cross-Site Request Forgery (CSRF) dan meminimalkan celah serangan dari browser.
*   **Langkah Konkret:**
    *   [ ] Ubah `$config['csrf_protection']` menjadi `TRUE` di `application/config/config.php`.
    *   [ ] Ubah penamaan token menjadi `kpkp_csrf_token`.
    *   [ ] Buka `application/views/forum.php` dan `application/views/detail.php`, tambahkan tag token CSRF di dalam form POST.
    *   [ ] Buka `application/views/registrasi.php`, sisipkan token CSRF ke form pendaftaran.
    *   [ ] Sisipkan header keamanan dasar (X-Frame-Options, X-Content-Type-Options, X-XSS-Protection) pada `layout/head.php`.

---

### 📍 FASE 2 — Hardening Google Authentication (Minggu 1: Hari 4-5)
*   **Target:** Mengamankan alur login Single Sign-On (SSO) Google dari manipulasi state token dan pengalihan jahat (Open Redirect).
*   **Langkah Konkret:**
    *   [ ] Di `Auth::google()`, generate token acak `state` menggunakan `bin2hex(random_bytes(16))` dan simpan di session.
    *   [ ] Simpan url redirect asal (`from`) di dalam session `oauth_redirect`, bukan di state URL.
    *   [ ] Di `Auth::google_callback()`, ambil `state` dari input GET dan validasi kecocokannya dengan session.
    *   [ ] Buat fungsi sanitasi URL sederhana untuk memastikan redirect target (`oauth_redirect`) tidak berisi protokol luar (mencegah Open Redirect).

---

### 📍 FASE 3 — Base Controller & Anti-IDOR (Minggu 2: Hari 1-3)
*   **Target:** Menerapkan pertahanan akses terpusat berbasis Role-Based Access Control (RBAC) dan menghentikan celah kebocoran profil (IDOR).
*   **Langkah Konkret:**
    *   [ ] Buat file `application/core/MY_Controller.php` berisi pembagian base class `MY_Controller`, `Public_Controller`, dan `Auth_Controller`.
    *   [ ] Ubah deklarasi class `Auth` dan `Umum` agar menginduk ke base controller yang sesuai.
    *   [ ] Refactor `Auth::update()`: hapus baris `$id = html_escape($this->input->post('id'))`, ganti dengan mengambil ID user dari session: `$id = $this->session->userdata('user_id')`.
    *   [ ] Refactor `Auth::reg_user($id)`: lakukan pengecekan apakah `$id` yang diakses sama dengan ID session yang aktif. Jika tidak sama, lempar error 403 (Akses Ditolak).

---

### 📍 FASE 4 — Pemulihan Forum & Impersonasi Guard (Minggu 2: Hari 4-5)
*   **Target:** Mengaktifkan kembali fitur komentar forum yang terputus dan menghentikan penipuan identitas staf.
*   **Langkah Konkret:**
    *   [ ] Tambahkan method `public function balas_aksi()` di controller `Umum.php`.
    *   [ ] Tangkap data post: `id_diskusi`, `nama_komentator`, dan `isi_komentar`.
    *   [ ] **Otorisasi Role Backend:** Tambahkan pengecekan otomatis. Default `role = 'Warga'`. Jika pengguna terdeteksi login sebagai staf dinas di session, ubah `role = 'Petugas Disperakim'`.
    *   [ ] Panggil `Forum_model->insert_komentar($data)` untuk menyimpan data ke database.
    *   [ ] Redirect kembali ke `Umum/detail/<id_diskusi>`.
    *   [ ] Buka `application/views/detail.php` dan hapus dropdown pilihan `role` dari UI HTML agar tidak bisa dimanipulasi peretas.

---

### 📍 FASE 5 — Perlindungan Data Pribadi (Minggu 3)
*   **Target:** Melindungi data pribadi NIK dan Alamat sesuai amanat UU PDP No. 27/2022.
*   **Langkah Konkret:**
    *   [ ] Jalankan `composer require vlucas/phpdotenv` untuk mengintegrasikan environment variables.
    *   [ ] Buat file `.env` di root direktori untuk menyimpan database credentials dan client secret Google OAuth.
    *   [ ] Buat library `Encryption_lib.php` di folder `application/libraries/` yang membungkus enkripsi openssl `aes-256-gcm`.
    *   [ ] Saat pendaftaran di `Auth::update()`, enkripsi data NIK dan Alamat domisili sebelum disimpan.
    *   [ ] Buat kolom `nik_lookup_hash` di database dan simpan nilai hash SHA-256 dari NIK plaintext sebagai index pencarian cepat.

---

## 3. KRITERIA KEBERHASILAN (Success Criteria)

Peta jalan ini dianggap sukses jika memenuhi parameter pengujian berikut:
1.  **Anti-IDOR:** Mengakses profil user lain (via URL `Auth/reg_user/<id>`) langsung memicu error 403.
2.  **Anti-CSRF:** Melakukan posting form forum dari luar aplikasi (domain luar) memicu error 403 Bad Request.
3.  **Integritas Forum:** Fitur komentar forum berjalan mulus tanpa error 404 dan tidak ada user warga yang bisa menyamar sebagai "Staff Ahli".
4.  **Google Auth:** Proses masuk Google login berjalan lancar dengan session terlindungi token CSRF state acak.
5.  **Enkripsi PDP:** Data kolom `nik` dan `alamat` di database terenkripsi secara kriptografis dan tidak terbaca sebagai plaintext.
