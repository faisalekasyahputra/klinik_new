# 🔐 Panduan Autentikasi & Akun — Klinik PKP
## Target: `c:\xampp\htdocs\klinik_new`
**Terakhir Diperbarui:** 1 Juli 2026 (v3.0 Refactor)

> **ℹ️ INFORMASI:**  
> Sistem `klinik_new` mendukung **autentikasi hibrida** — pengguna dapat mendaftar/masuk menggunakan **email & password tradisional** atau **Google OAuth 2.0 (SSO)**. Kedua metode melewati alur onboarding yang sama.

---

## 🌐 1. Alur Pendaftaran & Masuk

### 1.1 Pendaftaran Tradisional (Email/Password)
1.  Pengguna mengklik **"Daftar"** di navbar → diarahkan ke `Auth/register`.
2.  Mengisi: **Nama Lengkap**, **Email**, dan **Password** (dengan indikator kekuatan password).
3.  Setelah submit, diarahkan ke halaman **Dummy Email Verification** (`verify_pending.php`).
4.  Klik tombol verifikasi → diarahkan ke **Onboarding** (`Auth/onboarding`).

### 1.2 Login Tradisional (Email atau Username)
1.  Pengguna mengklik **"Masuk"** → diarahkan ke `Auth/login`.
2.  Mengisi **Email atau Username** + **Password**.
3.  Jika onboarding belum selesai → diarahkan ke `Auth/onboarding`.
4.  Jika sudah selesai → redirect ke halaman terakhir.

### 1.3 Login Google (SSO Popup)
1.  Klik ikon/tombol **"Masuk dengan Google"** di halaman login.
2.  Popup Google Sign-In muncul.
3.  Setelah otorisasi, callback `Auth/google_callback` memproses data.
4.  Jika email baru → akun dibuat otomatis (tanpa password), diarahkan ke **Onboarding** dengan flag `needs_password = true`.
5.  Jika email sudah ada → login langsung, redirect ke halaman asal.

### 1.4 Alur Onboarding (`Auth/onboarding`)
Semua pengguna baru wajib melengkapi data berikut sebelum akses penuh:

| Field | Keterangan | Kewajiban |
|-------|-----------|-----------|
| **Username** | Ditampilkan di forum. Tanpa spasi, lowercase, max 30 karakter. | Wajib |
| **Password** | Khusus user Google yang belum set password (`needs_password`). Min 8 karakter. | Kondisional |
| **Kategori/Role** | Masyarakat Umum, Pengembang, atau Mahasiswa Magang | Wajib |
| **NIK** | 16 digit, dienkripsi AES-256-GCM, di-hash SHA-256 untuk lookup | Wajib |
| **Alamat** | Dienkripsi AES-256-GCM | Wajib |
| **No. WhatsApp** | Opsional | Opsional |

---

## 🛠️ 2. Manajemen Profil (Halaman Pengaturan)

*   **URL:** `http://localhost/klinik_new/akun`
*   **Fitur yang tersedia:**
    *   Edit **Username** (disinkronkan ke `forum_diskusi.nama_user` dan `forum_komentar.nama_komentator`)
    *   Edit **Nama Lengkap**
    *   Edit **No. WhatsApp**
    *   **Email** bersifat read-only
    *   **Tombol Logout**

---

## 🗑️ 3. Penghapusan Akun (2-Langkah)

1.  Klik tombol **"Hapus Akun Secara Permanen"** di zona berbahaya halaman pengaturan.
2.  Modal konfirmasi muncul — pengguna harus **mengetik username/nama akun** secara manual.
3.  Setelah pengetikan cocok, tombol konfirmasi aktif.
4.  Proses backend (`User_model::delete_user_account`):
    *   Komentar dianonimkan → `nama_komentator = 'Akun Dihapus'`, `user_id = NULL`
    *   Diskusi dianonimkan → `nama_user = 'Akun Dihapus'`, `user_id = NULL`
    *   Likes dihapus dari `forum_likes`
    *   Record user dihapus dari tabel `usr_users`
    *   Session di-destroy

---

## 👤 4. Struktur Session

Data session yang disimpan setelah login berhasil:

| Key | Tipe | Keterangan |
|-----|------|-----------|
| `user_id` | INT | ID baris user di database |
| `username` | VARCHAR | Username (diprioritaskan untuk display) |
| `name` | VARCHAR | Nama lengkap |
| `email` | VARCHAR | Email pengguna |
| `avatar` | VARCHAR | URL foto profil Google (kosong jika tradisional) |
| `is_logged` | BOOL | Status login aktif |

> **Catatan:** Jika `avatar` kosong, UI menampilkan placeholder dari `ui-avatars.com` (inisial nama dengan warna tema).

---

## 🚪 5. Alur Keluar (Logout)

*   Tombol logout tersedia di:
    *   Navbar desktop (halaman pengaturan) → `Auth/logout`
    *   Mobile menu → `Auth/logout`
*   Proses: Session di-destroy → redirect ke beranda.

---

## 📁 6. File-File Terkait

| File | Peran |
|------|-------|
| `controllers/Auth.php` | Login, register, Google OAuth, onboarding, verifikasi, logout, hapus akun |
| `models/User_model.php` | CRUD user, sinkronisasi forum, delete account |
| `views/pages/auth/login.php` | Halaman login (email/username + password + Google) |
| `views/pages/auth/register.php` | Halaman pendaftaran tradisional |
| `views/pages/auth/onboarding.php` | Halaman onboarding (username, password, role, NIK, alamat) |
| `views/pages/auth/verify_pending.php` | Halaman dummy verifikasi email |
| `views/pages/pengaturan/index.php` | Pengaturan profil + hapus akun |
| `views/layouts/nav.php` | Navbar dengan avatar fallback |
| `libraries/Encryption_lib.php` | Enkripsi NIK & Alamat |

---

## 👥 7. Akun Demo (Untuk Pengujian)

Bagi pengembang atau pihak eksternal yang ingin mencoba fitur sistem (seperti Admin Dashboard atau Pengajuan Program), dapat menggunakan kredensial demo berikut:

| Peran (Role) | Username | Email | Password | Keterangan |
|-------------|----------|-------|----------|------------|
| **Admin** | `admin` | `admin@klinikpkp.jatengprov.go.id` | `password` | Mengakses Admin Dashboard & Validasi Antrean. |
| **Warga** | `warga_demo` | `warga@example.com` | `password` | Menguji pengajuan program perumahan. |
| **Pengembang** | `developer1` | `dev1@example.com` | `password` | Menguji fitur mitra pengembang. |

*(Harap tidak mengubah password akun demo di database utama)*

---
*Dokumen ini diperbarui otomatis oleh Antigravity AI Coding Assistant — 1 Juli 2026 (v3.0 Refactor).*
