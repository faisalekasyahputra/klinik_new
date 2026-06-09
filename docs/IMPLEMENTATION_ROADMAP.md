# 📅 IMPLEMENTATION ROADMAP
## Peta Jalan Perbaikan & Pengembangan Klinik PKP
**Target Workspace:** `c:\xampp\htdocs\klinik_new`  
**Terakhir Diperbarui:** 9 Juni 2026

---

## 0. RINGKASAN EKSEKUTIF (Executive Summary)

Peta jalan (roadmap) ini dirancang sebagai panduan langkah demi langkah bagi tim pengembang untuk melakukan perbaikan keamanan mendalam (*security hardening*), modernisasi UI/UX, dan pengembangan fitur lanjutan pada sistem *Klinik PKP* (`klinik_new`).

**Status Terkini:**
- ✅ **Fase 1–5 (Security & Fondasi)** — Sebagian besar selesai
- ✅ **Fase 6 (UI/UX & Autentikasi Modern)** — Selesai
- 🚀 **Fase 7 (Forum & Interaksi Lanjutan)** — Siap dikerjakan
- 🛠️ **Fase 8 (Dashboard & Manajemen)** — Belum mulai
- 🔒 **Fase 9 (Finalisasi & Rilis)** — Belum mulai

---

## 1. STRATEGI PELAKSANAAN (Phased Action Plan)

```
┌──────────────────────────────────────────────────────────┐
│ FASE 1: PERTAHANAN GLOBAL & CSRF               ✅ DONE  │
│  • CSRF Protection aktif di config.php                   │
│  • Token CSRF di semua form                              │
│  • Security Headers dasar                                │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ FASE 2: HARDENING GOOGLE AUTH & OAUTH CSRF      ✅ DONE  │
│  • Dynamic Cryptographic State Token                     │
│  • Open Redirect prevention di callback                  │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ FASE 3: MY_Controller & FIX IDOR               ✅ DONE  │
│  • Arsitektur base controllers                           │
│  • ID diambil dari session, bukan POST                   │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ FASE 4: PEMULIHAN FORUM & IMPERSONASI GUARD     ✅ DONE  │
│  • Method balas_aksi() di Umum.php                       │
│  • Role ditentukan backend, bukan dropdown               │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ FASE 5: KEPATUHAN UU PDP & ENKRIPSI DATA        ✅ DONE  │
│  • Encryption_lib untuk NIK & Alamat                     │
│  • nik_lookup_hash untuk pencarian cepat                 │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ FASE 6: UI/UX MODERN & AUTH FLOW                ✅ DONE  │
│  • Redesign Homepage (Hero, Bento, Pipeline)             │
│  • Autentikasi Hibrida (Tradisional + Google)            │
│  • Onboarding, Profil, Hapus Akun                        │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ FASE 7: FORUM & INTERAKSI LANJUTAN              🚀 NEXT  │
│  • Thread forum (rich-text / markdown)                   │
│  • Komentar nested multi-level                           │
│  • Voting / Upvote & Reputasi                            │
│  • Notifikasi real-time                                  │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ FASE 8: DASHBOARD & MANAJEMEN                   🛠️ TODO  │
│  • Dashboard Admin (moderasi, statistik)                 │
│  • Dashboard Pengembang                                  │
│  • Integrasi Bank Data spasial                           │
│  • Live Chat Admin handover                              │
└────────────────────────┬─────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────┐
│ FASE 9: FINALISASI & RILIS                      🔒 TODO  │
│  • Email Verification SMTP asli                          │
│  • Lupa Password flow                                    │
│  • Rate Limiting                                         │
│  • Optimasi, SEO & Deployment                            │
└──────────────────────────────────────────────────────────┘
```

---

## 2. DETAIL PER-FASE

### ✅ FASE 1 — Pertahanan Global & CSRF (SELESAI)
*   **Target:** Menghentikan serangan CSRF dan meminimalkan celah dari browser.
*   **Hasil:**
    *   [x] `$config['csrf_protection']` = `TRUE` di `config.php`.
    *   [x] Token CSRF terinjeksi di seluruh form POST (forum, registrasi, login, pengaturan).
    *   [x] Security headers dasar (X-Frame-Options, X-Content-Type-Options, X-XSS-Protection).

---

### ✅ FASE 2 — Hardening Google Authentication (SELESAI)
*   **Target:** Mengamankan alur SSO Google dari manipulasi state token dan Open Redirect.
*   **Hasil:**
    *   [x] State token kriptografis `bin2hex(random_bytes(16))` di `Auth::google()`.
    *   [x] Validasi state di `Auth::google_callback()`.
    *   [x] Sanitasi URL redirect untuk mencegah Open Redirect.

---

### ✅ FASE 3 — Base Controller & Anti-IDOR (SELESAI)
*   **Target:** RBAC terpusat dan pencegahan kebocoran profil (IDOR).
*   **Hasil:**
    *   [x] `MY_Controller.php` dengan base class `Public_Controller` dan `Auth_Controller`.
    *   [x] User ID diambil dari session, bukan dari input POST.
    *   [x] Akses profil user lain memicu error 403.

---

### ✅ FASE 4 — Pemulihan Forum & Impersonasi Guard (SELESAI)
*   **Target:** Forum komentar fungsional, tanpa celah impersonasi role.
*   **Hasil:**
    *   [x] Method `balas_aksi()` di controller `Umum.php`.
    *   [x] Role ditentukan backend (default `Warga`, override dari session jika staf).
    *   [x] Dropdown role dihapus dari UI HTML.
    *   [x] Filter kata kasar (`profanity.php`) aktif.

---

### ✅ FASE 5 — Perlindungan Data Pribadi / UU PDP (SELESAI)
*   **Target:** Enkripsi NIK dan Alamat sesuai UU PDP No. 27/2022.
*   **Hasil:**
    *   [x] Library `Encryption_lib.php` dengan `aes-256-gcm`.
    *   [x] NIK & Alamat terenkripsi saat pendaftaran.
    *   [x] Kolom `nik_lookup_hash` (SHA-256) untuk pencarian cepat.

---

### ✅ FASE 6 — UI/UX Modern & Autentikasi Flow (SELESAI — Juni 2026)
*   **Target:** Modernisasi tampilan dan pengalaman autentikasi pengguna secara menyeluruh.
*   **Hasil:**
    *   [x] **Redesign Homepage:** Hero slideshow, bento grid layanan, pipeline timeline, bank desain carousel, footer modern.
    *   [x] **Autentikasi Hibrida:** Login/Register tradisional (Email/Password) + Google OAuth SSO.
    *   [x] **Alur Onboarding Cerdas:** Deteksi otomatis user Google (wajib set password) vs tradisional. Semua user wajib melengkapi Username, Nama, NIK, dll.
    *   [x] **Dummy Email Verification:** Halaman `verify_pending.php` untuk simulasi sebelum SMTP asli.
    *   [x] **Manajemen Profil:** Halaman `/akun` — edit username, nama, WhatsApp. Sinkronisasi otomatis ke `tb_diskusi` & `tb_komentar`.
    *   [x] **Hapus Akun 2-Langkah:** Modal konfirmasi ketik nama akun. Forum dianonimkan jadi "Akun Dihapus".
    *   [x] **Fallback Avatar:** Integrasi `ui-avatars.com` saat user belum punya foto profil.
    *   [x] **Session Username:** Username diprioritaskan di session agar tidak menampilkan nama lengkap Google.
    *   [x] **Navbar Responsif:** Desktop & mobile menu responsif dengan avatar + username.

#### File Kunci Fase 6
| File | Peran |
|------|-------|
| `controllers/Auth.php` | Login, register, Google OAuth, onboarding, verifikasi, logout |
| `models/User_model.php` | CRUD user, sinkronisasi forum, hapus akun |
| `views/pages/auth/onboarding.php` | Halaman onboarding (username, password, role) |
| `views/pages/auth/verify_pending.php` | Halaman dummy verifikasi email |
| `views/pages/pengaturan/index.php` | Pengaturan user + modal hapus akun |
| `views/layouts/nav.php` | Navbar utama dengan avatar fallback |
| `views/pages/home/awal.php` | Homepage (hero, layanan, bank desain, dll.) |

---

### 🚀 FASE 7 — Forum & Interaksi Lanjutan (BELUM MULAI)
*   **Target:** Menyempurnakan fitur inti "Klinik" — forum diskusi dan konsultasi publik.
*   **Action Items:**
    *   [ ] **Thread Forum:** Pembuatan thread baru dengan dukungan rich-text atau markdown.
    *   [ ] **Komentar Nested Multi-Level:** Visual threading yang jelas untuk balasan bersarang.
    *   [ ] **Voting & Reputasi:** Like/Upvote untuk diskusi bermanfaat, leaderboard kontributor.
    *   [ ] **Notifikasi Real-time:** Notifikasi saat thread dikomentari atau mendapat balasan.
    *   [ ] **Moderasi Konten:** Pelaporan konten, moderasi admin, dan filter kata kasar lanjutan.
    *   [ ] **Pencarian Forum:** Search thread berdasarkan judul, konten, atau nama user.

---

### 🛠️ FASE 8 — Dashboard & Sistem Manajemen (BELUM MULAI)
*   **Target:** Panel kontrol internal dan ruang kerja untuk entitas khusus.
*   **Action Items:**
    *   [ ] **Dashboard Admin:** Moderasi forum, statistik pendaftaran, manajemen pengguna, monitoring aktivitas.
    *   [ ] **Dashboard Pengembang:** Registrasi proyek perumahan, manajemen profil perusahaan, upload dokumen.
    *   [ ] **Integrasi Bank Data Spasial:** Sikaper, Sikunang, Siperum, Sikumbang ditampilkan secara interaktif di peta.
    *   [ ] **Live Chat Admin:** Handover dari chatbot ke operator manusia di dashboard.

---

### 🔒 FASE 9 — Finalisasi & Rilis (BELUM MULAI)
*   **Target:** Persiapan akhir untuk deployment production.
*   **Action Items:**
    *   [ ] **Email Verification SMTP Asli:** Pengiriman email dengan token/link aktivasi menggunakan PHPMailer/SMTP.
    *   [ ] **Lupa Password:** Alur reset password via email.
    *   [ ] **Rate Limiting:** Proteksi brute-force pada login dan form publik.
    *   [ ] **Optimasi & SEO:** Minifikasi aset, lazy-loading gambar, metadata OG/Twitter, sitemap.
    *   [ ] **Deployment Production:** Migrasi dari XAMPP localhost ke hosting/VPS.
    *   [ ] **Backup & Monitoring:** Strategi backup database otomatis dan health monitoring.

---

## 3. CATATAN TEKNIS PENTING

| Topik | Detail |
|-------|--------|
| **Google Users** | Tidak memiliki password di database awalnya. Flag `needs_password` pada onboarding memaksa set password. |
| **Enkripsi NIK** | Di-hash deterministik (SHA-256) ke `nik_lookup_hash` untuk pencarian. Data asli dienkripsi AES-256-GCM. |
| **Sinkronisasi Forum** | `tb_diskusi.nama_user` dan `tb_komentar.nama_komentator` harus disinkronkan setiap kali username berubah. |
| **Chat System** | Menggunakan `session_token` (bukan `user_id`) untuk identifikasi room di `chat_rooms` dan `chat_messages`. |
| **Session Priority** | Username > Name untuk display di navbar/forum. Dicek saat login dan onboarding. |

---

## 4. KRITERIA KEBERHASILAN (Success Criteria)

### Fase 1–5 (Security — Sudah Tercapai)
1.  ✅ **Anti-IDOR:** Akses profil user lain memicu error 403.
2.  ✅ **Anti-CSRF:** Posting form dari domain luar memicu error 403.
3.  ✅ **Integritas Forum:** Komentar berjalan tanpa error 404, tidak ada impersonasi role.
4.  ✅ **Google Auth:** Login Google terlindungi state token kriptografis.
5.  ✅ **Enkripsi PDP:** Data `nik` dan `alamat` terenkripsi di database.

### Fase 6 (UI/UX — Sudah Tercapai)
6.  ✅ **Homepage Modern:** Hero slideshow, bento grid, pipeline timeline responsif.
7.  ✅ **Auth Flow Lengkap:** Register → Verifikasi → Onboarding → Dashboard berjalan mulus.
8.  ✅ **Profil Konsisten:** Username tersinkronisasi di seluruh tabel terkait.

### Fase 7–9 (Belum Dicapai)
9.  ⬜ **Forum Interaktif:** Thread, nested comments, voting, dan notifikasi berfungsi.
10. ⬜ **Dashboard Operasional:** Admin dapat memoderasi dan memonitor dari panel kontrol.
11. ⬜ **Production-Ready:** Aplikasi siap di-deploy dengan email asli, rate limiting, dan monitoring.
