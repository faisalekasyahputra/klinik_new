# 🗄️ DATABASE DESIGN DOCUMENT
## Klinik PKP — Super App Disperakim Provinsi Jawa Tengah
**Target Database:** `klinikpkp`  
**Terakhir Diperbarui:** 30 Juni 2026 (Refactor v3.0)

---

## 1. STRUKTUR & KAMUS DATA TABEL MODULAR (Data Dictionary)

Berdasarkan *refactor* terbaru ke v3.0, database `klinikpkp` kini terstruktur menggunakan sistem *Prefix* Modular untuk membedakan konteks fitur dengan jelas.

### 👤 1.1 Tabel `usr_users` (Akun Publik — Hybrid Auth)
Tabel ini menyimpan akun pengguna yang mendaftar melalui Google OAuth SSO maupun registrasi tradisional.

| Nama Field | Tipe Data | Kunci | Keterangan |
|---|---|---|---|
| `id` | INT | PK, AI | ID unik baris database |
| `google_id` | VARCHAR(255) | — | ID unik dari Google |
| `username` | VARCHAR(30) | Unique | Username publik |
| `name` | VARCHAR(255) | — | Nama Lengkap |
| `email` | VARCHAR(255) | Unique | Email pengguna |
| `password` | VARCHAR(255) | — | Password (bcrypt hash) |
| `avatar` | VARCHAR(255) | — | URL Foto profil dari Google |
| `phone` | VARCHAR(20) | — | No. WhatsApp |
| `nik` | TEXT | — | NIK 16 Digit — **terenkripsi AES-256-GCM** |
| `nik_lookup_hash` | VARCHAR(64) | Index | SHA-256 hash dari NIK plaintext |
| `alamat` | TEXT | — | Alamat Domisili — **terenkripsi AES-256-GCM** |
| `kategori` | VARCHAR(50) | — | Kategori akun (Masyarakat/Pengembang/dll) |
| `is_verified` | TINYINT(1) | — | Status verifikasi email |
| `onboarding_done` | TINYINT(1) | — | Status onboarding wizard selesai |
| `updated_at` | DATETIME | — | Timestamp pembaruan data |
| `created_at` | DATETIME | — | Timestamp pembuatan akun |

---

### 🔑 1.2 Tabel `user` (Akun Staf / Admin Legacy)
Tabel ini (tanpa prefix) digunakan sementara untuk kompatibilitas ke belakang (sistem pemetaan lama).

| Nama Field | Tipe Data | Kunci | Keterangan |
|---|---|---|---|
| `id` | INT | PK, AI | ID unik administrator |
| `email` | VARCHAR(255) | — | Email staf/admin |
| `pass` | VARCHAR(255) | — | Password (Legacy SHA-1) |
| `nama` | VARCHAR(255) | — | Nama lengkap staf |

---

### 💬 1.3 Tabel `forum_diskusi` (Topik Diskusi)
Mencatat topik diskusi yang diinisiasi oleh warga di forum komunitas.

| Nama Field | Tipe Data | Kunci | Keterangan |
|---|---|---|---|
| `id_diskusi` | INT | PK, AI | ID unik topik diskusi |
| `user_id` | INT | FK → usr_users.id | ID pembuat topik |
| `nama_user` | VARCHAR(255) | — | Nama pengirim (sync otomatis) |
| `email_user` | VARCHAR(255) | — | Email pengirim topik |
| `judul_topik` | VARCHAR(255) | — | Judul permasalahan forum |
| `kategori` | VARCHAR(50) | — | Kategori masalah |
| `isi_diskusi` | TEXT | — | Deskripsi lengkap masalah |
| `created_at` | DATETIME | — | Tanggal pembuatan topik |

---

### 💬 1.4 Tabel `forum_komentar` (Balasan Diskusi)
Menampung komentar, solusi, dan tanggapan pada topik diskusi dengan sistem *nested thread*.

| Nama Field | Tipe Data | Kunci | Keterangan |
|---|---|---|---|
| `id_komentar` | INT | PK, AI | ID unik komentar |
| `id_diskusi` | INT | FK → forum_diskusi | Relasi ke topik diskusi |
| `user_id` | INT | FK → usr_users.id | ID pengirim komentar |
| `parent_id` | INT | FK → forum_komentar | ID komentar induk |
| `nama_komentator` | VARCHAR(255) | — | Nama pengirim |
| `isi_komentar` | TEXT | — | Isi tanggapan |
| `role` | VARCHAR(50) | — | Peran pengirim |
| `created_at` | DATETIME | — | Tanggal pengiriman komentar |

---

### 📋 1.5 Modul Smart Filter (`sf_programs` & `sf_program_kategori`)
Tabel utama untuk menyaring bantuan (RTLH, FLPP, Bantuan Rusun).

**`sf_program_kategori`**
| Nama Field | Tipe Data | Kunci | Keterangan |
|---|---|---|---|
| `id` | INT | PK, AI | ID unik kategori |
| `nama_kategori` | VARCHAR(100) | — | Nama kategori |

**`sf_programs`**
| Nama Field | Tipe Data | Kunci | Keterangan |
|---|---|---|---|
| `id` | INT | PK, AI | ID unik program |
| `id_kategori` | INT | FK → sf_program_kategori | Relasi ke kategori |
| `kode_program` | VARCHAR(50) | Unique | Kode identifier |
| `nama_program` | VARCHAR(255) | — | Nama lengkap program |
| `batas_penghasilan_max` | DECIMAL(15,2) | — | Maksimal pendapatan (Parameter matriks) |
| `is_active` | TINYINT(1) | — | Status aktif program |

---

### 📋 1.6 Tabel `sf_housing_queue` (Antrean Kelayakan)
Menyimpan pendaftaran warga dari hasil *Kalkulator Kelayakan* Smart Filter.

| Nama Field | Tipe Data | Kunci | Keterangan |
|---|---|---|---|
| `id` | INT | PK, AI | ID unik antrean |
| `user_id` | INT | FK → usr_users.id | Relasi User |
| `program_id` | INT | FK → sf_programs.id | Program yang dipilih/direkomendasikan |
| `nik_pengaju` | VARCHAR(255) | — | NIK yang digunakan |
| `data_simperum_json` | TEXT | — | Data JSON SIMPERUM |
| `data_survey_json` | TEXT | — | Input form JSON |
| `status_antrean` | ENUM | — | `pending`, `approved`, `rejected` |
| `catatan_admin` | TEXT | — | Alasan validasi admin |
| `created_at` | DATETIME | — | Waktu antre |

---

### ⚙️ 1.7 Modul Sistem (`sys_menu`, `sys_multi`)
Mengelola *sidebar* navigasi berdasarkan hak akses dinamis.

| Nama Field | Tipe Data | Kunci | Keterangan |
|---|---|---|---|
| `id` | INT | PK, AI | ID menu (tabel `sys_menu`) |
| `link` | VARCHAR | — | Target tautan |
| `id_user` | INT | FK → usr_users | ID Otorisasi (tabel `sys_multi`) |

---

## 🔒 2. SKEMA ENKRIPSI DATA PII (UU PDP Compliance)

```
[NIK Input] ──┬──→ [Hash SHA-256 + Pepper] ──→ Simpan di `nik_lookup_hash`
              │
              └───→ [AES-256-GCM + Key]    ──→ Simpan di `nik`

[Alamat Input] ──→ [AES-256-GCM + Key]     ──→ Simpan di `alamat`
```

---

## 3. DIAGRAM RELASI ANTAR TABEL MODULAR (ERD v3.0)

```
┌─────────────────┐       ┌──────────────────────┐       ┌──────────────────────┐
│   usr_users     │       │    forum_diskusi     │       │   forum_komentar     │
│─────────────────│       │──────────────────────│       │──────────────────────│
│ id (PK)         │◄──┐   │ id_diskusi (PK)      │◄──┐   │ id_komentar(PK)      │
│ username        │   ├──►│ user_id (FK)         │   ├──►│ id_diskusi (FK)      │
│ nik (enc)       │   │   │ nama_user            │   │   │ user_id (FK)         │
│ alamat (enc)    │   │   │ judul_topik          │   │   │ parent_id (FK)───────┤►(self)
└─────────────────┘   │   └──────────────────────┘   │   └──────────────────────┘
                      │                               │
                      │   ┌──────────────────────┐   │
                      ├──►│    forum_likes       │   │
                      │   │ user_id (FK)         │   │
                      │   │ id_diskusi (FK)──────┘   │
                      │   └──────────────────────┘   │
                      │                               │
                      │   ┌──────────────────────┐    │
                      │   │ sf_program_kategori  │    │
                      │   │ id (PK)              │◄──┐│
                      │   └──────────────────────┘   ││
                      │   ┌──────────────────────┐   ││
                      │   │    sf_programs       │   ││
                      │   │ id (PK)              │◄─┐├┘
                      │   │ id_kategori (FK)     ├──┘│
                      │   └──────────────────────┘   │
                      │   ┌──────────────────────┐   │
                      │   │   sf_housing_queue   │   │
                      ├──►│ user_id (FK)         │   │
                      │   │ program_id (FK)      ├───┘
                      │   │ status_antrean       │   
                      │   └──────────────────────┘   
                      └───────────────────────────────┘
```
