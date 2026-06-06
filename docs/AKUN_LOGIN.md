# 🔐 Panduan Autentikasi & Akun — Klinik PKP
## Target: `c:\xampp\htdocs\klinik_new`

> **⚠️ INFORMASI PENTING:**  
> Berbeda dengan sistem pembanding yang menggunakan login email/password lokal, versi `klinik_new` saat ini menggunakan **Google OAuth 2.0** sebagai metode masuk utama (Single Sign-On). Hal ini menyederhanakan akses bagi masyarakat umum dan staf.

---

## 🌐 1. Alur Masuk Utama (Google Login Popup Flow)

Otentikasi dilakukan menggunakan jendela popup Google Sign-In yang terintegrasi dengan library Google API di backend.

*   **URL Portal Utama:** `http://localhost/klinik_new/`
*   **Tombol Login:** Terletak pada bagian kanan navbar (ikon Google).
*   **Fungsi JavaScript Popup (`loginGooglePopup()`):**
    ```javascript
    function loginGooglePopup() {
        const width = 500;
        const height = 600;
        const left = (screen.width / 2) - (width / 2);
        const top = (screen.height / 2) - (height / 2);
        const currentPath = window.location.pathname.split('/').slice(2).join('/'); 
        const url = "<?php echo base_url('Auth/google?from='); ?>" + encodeURIComponent(currentPath);
        
        const popup = window.open(
            url, 
            "GoogleLoginPopup", 
            `width=${width},height=${height},top=${top},left=${left},resizable=yes,scrollbars=yes`
        );
        if (window.focus) popup.focus();
    }
    ```

---

## 🛠️ 2. Tata Cara Pengujian Akun (Testing & QA)

Untuk mensimulasikan login di localhost:

1.  **Konfigurasi Redirect URI:**
    Pastikan sub-domain localhost yang kamu gunakan sudah didaftarkan di **Google Cloud Console Credentials** di bawah *Authorized Redirect URIs*:
    *   `http://localhost/klinik_new/Auth/google_callback`
2.  **Autentikasi Pertama Kali:**
    *   Klik tombol **Login dengan Google**.
    *   Gunakan akun Gmail biasa/uji coba.
    *   Sistem akan secara otomatis mendeteksi jika email belum terdaftar di tabel `users`.
3.  **Proses Registrasi Otomatis (Self-Register):**
    *   Jika email baru pertama kali digunakan, callback di `Auth::google_callback` akan mengalihkan popup ke halaman pengisian profil:
        *   `Auth/reg_user/<user_id>`
    *   Pengguna melengkapi data: **Nama Lengkap**, **Kategori Pendaftaran** (Masyarakat Umum, Pengembang, Mahasiswa Magang), **NIK (16 Digit)**, dan **Alamat Domisili**.
    *   Setelah disimpan, data akan masuk ke database dan status login langsung aktif.
4.  **Verifikasi Session:**
    Data session yang disimpan setelah berhasil masuk:
    *   `user_id` — ID baris pengguna di DB.
    *   `name` — Nama lengkap dari Google profile / input profil.
    *   `email` — Alamat Gmail.
    *   `avatar` — URL foto profil Google.
    *   `is_logged` — `TRUE`.

---

## 👥 3. Struktur Tabel Database Akun (`users`)

Saat registrasi pertama, sistem menyimpan data ke tabel `users` dengan struktur sebagai berikut:

| Nama Kolom | Tipe Data | Keterangan |
|---|---|---|
| `id` | INT (Auto Increment) | Primary Key |
| `google_id` | VARCHAR(255) | ID Unik Pengguna dari Google |
| `name` | VARCHAR(255) | Nama Lengkap Pengguna |
| `email` | VARCHAR(255) | Email Gmail Pengguna (Unique) |
| `avatar` | VARCHAR(255) | URL Foto Profil dari Akun Google |
| `nik` | VARCHAR(16) | NIK 16 Digit (Plaintext di database saat ini) |
| `kategori` | VARCHAR(50) | Kategori akun (Masyarakat Umum, Pengembang, dll.) |
| `alamat` | TEXT | Alamat domisili lengkap |
| `updated_at` | DATETIME | Timestamp pembaruan data profil terakhir |

---

## 🚪 4. Alur Keluar (Logout Flow)

Proses logout memotong sesi saat ini dan mengembalikan pengguna ke halaman terakhir yang sedang mereka buka secara mulus.

*   **Fungsi JavaScript Logout (`logout()`):**
    ```javascript
    function logout() {
        const currentPath = window.location.pathname.split('/').slice(2).join('/'); 
        window.location.href = "<?= base_url('Auth/logout') ?>?curr=" + encodeURIComponent(currentPath);
    }
    ```
*   **Method Backend (`Auth::logout()`):**
    ```php
    public function logout() {
        $curr = $_GET['curr'];
        $this->session->sess_destroy();
        redirect($curr);
    }
    ```
