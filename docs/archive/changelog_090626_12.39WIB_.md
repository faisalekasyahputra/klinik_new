# 📋 CHANGELOG - Klinik PKP
## Dari `klinik_new_2` (Baseline) → `klinik_new` (Current)
**Tanggal:** 9 Juni 2026 - 12.39 WIB  
**Baseline (sebelum):** `c:\xampp\htdocs\klinik_new_2`  
**Current (sesudah):** `c:\xampp\htdocs\klinik_new`

---

## 📊 Ringkasan Perubahan

| Kategori | Sebelum | Sesudah | Delta |
|----------|---------|---------|-------|
| Controllers | 9 file (44.2 KB) | 14 file (82.6 KB) | +5 file, +38.4 KB |
| Models | 4 file (15.3 KB) | 5 file (31.1 KB) | +1 file, +15.8 KB |
| Libraries | 1 file (3.0 KB) | 3 file (12.8 KB) | +2 file, +9.8 KB |
| Helpers | 1 file (1.2 KB) | 2+ file | +1 file |
| Config | 16 file | 18 file | +2 file |
| Views | 34 file (flat) | 17 subdirectories (modular) | Restrukturisasi total |
| Core | Kosong | 1 file (`MY_Controller.php`) | +1 file |
| Docs | Tidak ada | 7 dokumen teknis | +7 file |

---

## 🔴 BREAKING CHANGES

### Restrukturisasi Views (Flat → Modular)
Seluruh file view yang sebelumnya berada di `application/views/` secara flat telah dipindahkan ke struktur subdirektori modular di `application/views/pages/`:

```diff
- views/awal.php
+ views/pages/home/awal.php

- views/forum.php
+ views/pages/umum/forum.php

- views/detail.php (forum detail)
+ views/pages/umum/detail.php

- views/registrasi.php
+ views/pages/auth/onboarding.php (rewrite total)

- views/pengaturan.php
+ views/pages/pengaturan/index.php (rewrite total)

- views/umum.php
+ views/pages/umum/umum.php

- views/detail_perumahan.php
+ views/pages/perumahan/detail.php

- views/detail_artikel.php
+ views/pages/artikel/detail_artikel.php

- views/sebaran.php
+ views/pages/data_spasial/sebaran.php

- views/kemitraan.php
+ views/pages/kemitraan/kemitraan.php

- views/layout/
+ views/layouts/
```

### Halaman Auth Baru (Tidak Ada di Baseline)
```diff
+ views/pages/auth/login.php          (BARU)
+ views/pages/auth/register.php       (BARU)
+ views/pages/auth/onboarding.php     (BARU - menggantikan registrasi.php)
+ views/pages/auth/verify_pending.php (BARU)
```

---

## ✨ FITUR BARU

### 🔐 Autentikasi Hibrida (Auth.php: 6.6KB → 25.7KB)
| Fitur | Sebelum | Sesudah |
|-------|---------|---------|
| Login | Hanya Google OAuth popup | Google OAuth + Email/Password tradisional |
| Register | Tidak ada (otomatis via Google) | Form pendaftaran manual (nama, email, password) |
| Login field | - | Support email ATAU username |
| Password | Tidak ada di database | Bcrypt hash, validasi kekuatan di UI |
| Onboarding | `reg_user/$id` (form profil sederhana) | Halaman onboarding lengkap (username, password, role, NIK, alamat) |
| Email Verification | Tidak ada | Dummy verification page (`verify_pending.php`) |
| Hapus Akun | Tidak ada | Konfirmasi 2-langkah + anonimisasi forum |
| Session | `user_id`, `name`, `email`, `avatar` | + `username` (diprioritaskan untuk display) |

### 👤 Manajemen Profil (Controller Baru: `Pengaturan.php`)
- **Sebelum:** Tidak ada halaman pengaturan terpisah.
- **Sesudah:** Halaman `/akun` untuk edit username, nama, WhatsApp, dengan sinkronisasi otomatis ke tabel forum.

### 🗑️ Penghapusan Akun
- **Sebelum:** Tidak ada fitur hapus akun.
- **Sesudah:** Modal konfirmasi 2-langkah - user harus mengetik nama akun. Data forum dianonimkan menjadi "Akun Dihapus".

### 🖼️ Avatar Fallback
- **Sebelum:** Hanya menampilkan foto Google (`avatar` dari session). Jika kosong, broken image.
- **Sesudah:** Fallback otomatis ke `ui-avatars.com` dengan inisial nama dan warna tema.

### 🔄 Sinkronisasi Forum
- **Sebelum:** Nama di forum tidak terhubung ke profil user.
- **Sesudah:** Perubahan username otomatis di-propagasi ke `tb_diskusi.nama_user` dan `tb_komentar.nama_komentator`.

---

## 🛡️ PERBAIKAN KEAMANAN

### CSRF Protection
```diff
- $config['csrf_protection'] = FALSE;
+ $config['csrf_protection'] = TRUE;
```
Token CSRF diinjeksikan ke seluruh form POST.

### Google OAuth Hardening
```diff
- // State = URL asal (plain text, tidak divalidasi)
- $this->google_client->setState($from);

+ // State = token kriptografis acak
+ $state = bin2hex(random_bytes(16));
+ $this->session->set_userdata('oauth_state', $state);
+ $this->google_client->setState($state);
+ // Validasi di callback
```

### Anti-IDOR
```diff
- // ID diambil dari POST (bisa dimanipulasi)
- $id = html_escape($this->input->post('id'));

+ // ID diambil dari session (aman)
+ $id = $this->session->userdata('user_id');
```

### Base Controller Hierarchy
```diff
+ application/core/MY_Controller.php
+   ├── MY_Controller (base)
+   ├── Public_Controller (akses bebas)
+   └── Auth_Controller (wajib login)
```

### Impersonasi Guard
```diff
- <!-- Dropdown role bisa dipilih siapapun -->
- <select name="role">
-   <option value="Warga">Masyarakat</option>
-   <option value="Petugas Disperakim">Petugas (Internal)</option>
- </select>

+ // Role ditentukan di backend berdasarkan session
+ $role = ($this->session->userdata('is_admin')) ? 'Petugas Disperakim' : 'Warga';
```

### Enkripsi Data Pribadi (UU PDP)
```diff
- // NIK dan Alamat disimpan plaintext
- 'nik' => $nik,
- 'alamat' => $alamat

+ // NIK dan Alamat dienkripsi AES-256-GCM
+ 'nik' => $this->encryption_lib->encrypt($nik),
+ 'alamat' => $this->encryption_lib->encrypt($alamat),
+ 'nik_lookup_hash' => $this->encryption_lib->deterministic_hash($nik)
```

### Filter Kata Kasar
```diff
+ application/config/profanity.php  (BARU - daftar kata kasar untuk filter forum)
```

---

## 📁 FILE BARU

### Controllers (+5)
| File | Ukuran | Fungsi |
|------|--------|--------|
| `Pengaturan.php` | 2.8 KB | Manajemen profil & hapus akun |
| `Berita.php` | 0.6 KB | Halaman daftar berita |
| `Sikaper.php` | 1.2 KB | Integrasi data Sikaper |
| `Sikunang.php` | 0.4 KB | Integrasi data Sikunang |
| `Siperum.php` | 0.4 KB | Integrasi data Siperum |

### Models (+1)
| File | Ukuran | Fungsi |
|------|--------|--------|
| `Auth_model.php` | 7.5 KB | Login tradisional, register, verifikasi, Google auth |

### Libraries (+2)
| File | Ukuran | Fungsi |
|------|--------|--------|
| `Encryption_lib.php` | 5.9 KB | AES-256-GCM enkripsi/dekripsi, SHA-256 hashing |
| `Sikaper_api.php` | 3.8 KB | Integrasi API Sikaper |

### Config (+2)
| File | Fungsi |
|------|--------|
| `profanity.php` | Daftar kata kasar untuk filter konten forum |
| `sikaper.php` | Konfigurasi endpoint API Sikaper |

### Core (+1)
| File | Fungsi |
|------|--------|
| `MY_Controller.php` | Base controller hierarchy (Public / Auth) |

### Helpers (+1)
| File | Fungsi |
|------|--------|
| `forum_helper.php` | Helper fungsi forum (formatting, dll) |

### Docs (+7)
| File | Fungsi |
|------|--------|
| `AKUN_LOGIN.md` | Panduan autentikasi & akun |
| `ANALISIS_DAN_RENCANA_PERBAIKAN.md` | Audit keamanan & rencana perbaikan |
| `DATABASE_DESIGN_DOCUMENT.md` | Kamus data & ERD |
| `IMPLEMENTATION_ROADMAP.md` | Peta jalan pengembangan 9 fase |
| `PRODUCT REQUIREMENTS DOCUMENT.md` | PRD lengkap |
| `SECURITY_DESIGN_DOCUMENT.md` | Desain keamanan & STRIDE |
| `TECHNICAL_DESIGN_DOCUMENT.md` | Arsitektur teknis & struktur direktori |

---

## 📐 FILE YANG DIMODIFIKASI SIGNIFIKAN

### Controllers
| File | Sebelum | Sesudah | Perubahan |
|------|---------|---------|-----------|
| `Auth.php` | 6.6 KB (158 baris) | 25.7 KB (~600+ baris) | +login tradisional, +register, +onboarding, +verify, +hapus akun, +OAuth hardening |
| `Umum.php` | 5.8 KB | 14.0 KB | +`balas_aksi()`, +nested comments, +likes, +profanity filter, +role guard |
| `Index.php` | 13.1 KB | 17.0 KB | +routing baru, +modular view loading |
| `Sikumbang.php` | 1.8 KB | 3.8 KB | +fitur tambahan |

### Models
| File | Sebelum | Sesudah | Perubahan |
|------|---------|---------|-----------|
| `User_model.php` | 1.2 KB (34 baris) | 2.9 KB (86 baris) | +`update_user()` dengan sinkronisasi forum, +`delete_user_account()` dengan anonimisasi |
| `Forum_model.php` | 1.1 KB (31 baris) | 7.7 KB (~200+ baris) | +nested komentar, +likes system, +user_id FK, +parent_id threading |

### Views (Rewrite Total)
| View | Perubahan Utama |
|------|----------------|
| `awal.php` | Redesign total - hero slideshow, bento grid layanan, pipeline timeline, bank desain carousel |
| `nav.php` | Navbar responsif dengan avatar fallback, dropdown menu, mobile menu Alpine.js |
| `forum.php` | UI modern dengan profanity filter, like system, nested reply visual |
| `pengaturan/index.php` | Halaman baru - edit profil + modal hapus akun 2-langkah |

---

## 🗄️ PERUBAHAN DATABASE (Tabel `users`)

```diff
  id              INT (PK, AI)
  google_id       VARCHAR(255)
+ username        VARCHAR(30) UNIQUE        ← BARU
  name            VARCHAR(255)
  email           VARCHAR(255) UNIQUE
+ password        VARCHAR(255)              ← BARU (bcrypt hash)
  avatar          VARCHAR(255)
+ phone           VARCHAR(20)               ← BARU
- nik             VARCHAR(16)               ← PLAINTEXT (DIHAPUS)
+ nik             TEXT                      ← TERENKRIPSI AES-256-GCM
+ nik_lookup_hash VARCHAR(64) INDEX         ← BARU (SHA-256)
- alamat          TEXT                      ← PLAINTEXT (DIHAPUS)
+ alamat          TEXT                      ← TERENKRIPSI AES-256-GCM
  kategori        VARCHAR(50)
+ is_verified     TINYINT(1)               ← BARU
+ onboarding_done TINYINT(1)               ← BARU
  updated_at      DATETIME
+ created_at      DATETIME                  ← BARU
```

### Tabel `tb_diskusi` & `tb_komentar`
```diff
+ tb_diskusi.user_id       INT FK → users.id    ← BARU
+ tb_komentar.user_id      INT FK → users.id    ← BARU
+ tb_komentar.parent_id    INT FK → self         ← BARU (nested threading)
```

### Tabel Baru
```diff
+ tb_forum_likes (id, id_diskusi, user_id, created_at)  ← BARU
```

---

## 🎨 PERUBAHAN UI/UX

| Area | Sebelum | Sesudah |
|------|---------|---------|
| **Homepage** | Layout statis sederhana | Hero slideshow 3-slide, bento grid layanan, pipeline timeline animasi, bank desain carousel interaktif |
| **Navbar** | Basic navbar dengan ikon Google login | Navbar responsif dengan dropdown multi-level, avatar user, mobile hamburger menu (Alpine.js) |
| **Login** | Hanya popup Google | Form login modern (email/username + password) + tombol Google SSO |
| **Register** | Tidak ada | Form register dengan password strength indicator |
| **Profil** | Form `registrasi.php` sederhana | Onboarding step-by-step + halaman pengaturan terpisah |
| **Forum** | Basic thread list + komentar flat | Thread dengan like count, nested replies visual, profanity filter, role badge |
| **Footer** | Footer sederhana | Footer modern multi-kolom |

---

## 📝 CATATAN TEKNIS

1. **Backward Compatibility:** Struktur view berubah total. URL routing di `routes.php` telah disesuaikan.
2. **Environment:** File `.env` diperlukan untuk kunci enkripsi (`KPKP_DATA_KEY`, `KPKP_DATA_PEPPER`).
3. **Composer:** Dependency `vlucas/phpdotenv` ditambahkan untuk environment variable management.
4. **Tailwind CSS:** `tailwind.config.js` ditambahkan untuk custom styling.
5. **Alpine.js:** Digunakan secara luas untuk interaktivitas UI (mobile menu, form validation, modals).

---

## 🚀 DEPLOYMENT KE PRODUCTION (Hostinger)

**Tanggal:** 9 Juni 2026 - 13:40 WIB  
**URL Live:** https://palegreen-mink-703421.hostingersite.com/

### Infrastruktur
| Item | Detail |
|------|--------|
| **Hosting** | Hostinger Shared Hosting |
| **Domain** | `palegreen-mink-703421.hostingersite.com` (temp domain) |
| **Server** | `us-phx-web563` (Phoenix, US) |
| **Username** | `u504551489` |
| **Database** | `u504551489_klinikpkp` (MySQL) |
| **DB User** | `u504551489_klinikadm` |
| **PHP** | 8.x |
| **Deploy Method** | Git Auto-Deploy (GitHub Webhook) |

### Perubahan Config untuk Production

#### `config.php` - base_url dinamis
```diff
- $config['base_url'] = 'http://localhost/klinik_new/';
+ $config['base_url'] = getenv('SITE_URL') ?: 'http://localhost/klinik_new/';
```

#### `database.php` - credentials dari .env
```diff
- 'hostname' => 'localhost',
- 'username' => 'root',
- 'password' => '',
- 'database' => 'klinikpkp',
+ 'hostname' => getenv('DB_HOST') ?: 'localhost',
+ 'username' => getenv('DB_USER') ?: 'root',
+ 'password' => getenv('DB_PASS') ?: '',
+ 'database' => getenv('DB_NAME') ?: 'klinikpkp',
```

#### `schema_klinikpkp.sql` - encoding fix
```diff
- Encoding: UTF-16 LE (BOM) - menyebabkan error MySQL import
+ Encoding: UTF-8 tanpa BOM - kompatibel dengan MySQL CLI
```

### Auto-Deploy Flow
```
git push origin main
        ↓
GitHub Webhook POST → webhooks.hostinger.com/deploy/0f52d4c...
        ↓
Hostinger auto-pull → Site updated!
```

### File Production Baru
| File | Fungsi |
|------|--------|
| `.env.production` | Template environment production (referensi) |
| `deploy_build.ps1` | Script PowerShell build ZIP deployment (backup method) |

### Langkah Deploy yang Dilakukan
1. ✅ Koneksi API Hostinger via Bearer token
2. ✅ User buat temp domain `palegreen-mink-703421.hostingersite.com` di hPanel
3. ✅ Database `u504551489_klinikpkp` dibuat via Hostinger API
4. ✅ Git auto-deploy disetup (GitHub → Hostinger webhook)
5. ✅ File `.env` dibuat manual di server via SSH
6. ✅ `composer install --no-dev --optimize-autoloader` (29 dev packages removed)
7. ✅ Schema SQL diimport (15 tabel) via `mysql` CLI
8. ✅ Site live - HTTP 200, homepage tampil lengkap

### ⚠️ Post-Deploy TODO
- [ ] Update Google OAuth redirect URI di Google Cloud Console
- [ ] Daftarkan domain di Google reCAPTCHA admin
- [ ] Set environment `CI_ENV=production` di server

---

*Changelog ini dibuat & diperbarui oleh Antigravity AI Coding Assistant - 9 Juni 2026, terakhir update 13:43 WIB*

