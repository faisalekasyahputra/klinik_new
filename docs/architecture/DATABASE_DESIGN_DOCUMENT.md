# 🗄️ DATABASE DESIGN DOCUMENT
## Klinik PKP — Super App Disperakim Provinsi Jawa Tengah
**Target Database:** `klinikpkp`  
**Terakhir Diperbarui:** 9 Juni 2026

---

## 1. STRUKTUR & KAMUS DATA TABEL (Data Dictionary)

Berdasarkan analisis file model dan controller, database `klinikpkp` terdiri dari beberapa tabel utama yang mendukung fitur autentikasi hibrida, forum komunitas, GIS spasial, chat interaktif, dan manajemen akun.

### 👤 1.1 Tabel `users` (Akun Publik — Hybrid Auth)
Tabel ini menyimpan akun pengguna yang mendaftar melalui Google OAuth SSO maupun registrasi tradisional (email/password).

| Nama Field | Tipe Data | Kunci | Keterangan |
|---|---|---|---|
| `id` | INT | PK, AI | ID unik baris database |
| `google_id` | VARCHAR(255) | — | ID unik dari Google (NULL jika registrasi tradisional) |
| `username` | VARCHAR(30) | Unique | Username publik (ditampilkan di forum, tanpa spasi) |
| `name` | VARCHAR(255) | — | Nama Lengkap |
| `email` | VARCHAR(255) | Unique | Email pengguna |
| `password` | VARCHAR(255) | — | Password (bcrypt hash). NULL sementara untuk user Google sebelum onboarding |
| `avatar` | VARCHAR(255) | — | URL Foto profil dari Google (kosong jika tradisional) |
| `phone` | VARCHAR(20) | — | No. WhatsApp (opsional) |
| `nik` | TEXT | — | NIK 16 Digit — **terenkripsi AES-256-GCM** |
| `nik_lookup_hash` | VARCHAR(64) | Index | SHA-256 hash dari NIK plaintext untuk pencarian cepat |
| `alamat` | TEXT | — | Alamat Domisili — **terenkripsi AES-256-GCM** |
| `kategori` | VARCHAR(50) | — | Kategori akun (Masyarakat Umum / Pengembang / Mahasiswa Magang) |
| `is_verified` | TINYINT(1) | — | Status verifikasi email (1 = terverifikasi) |
| `onboarding_done` | TINYINT(1) | — | Status onboarding selesai (1 = selesai) |
| `updated_at` | DATETIME | — | Timestamp pembaruan data terakhir |
| `created_at` | DATETIME | — | Timestamp pembuatan akun |

> **Catatan Keamanan:**  
> - Kolom `nik` dan `alamat` menyimpan data terenkripsi, bukan plaintext.
> - `nik_lookup_hash` digunakan untuk validasi keunikan NIK tanpa mendekripsi seluruh tabel.
> - `password` di-hash menggunakan `password_hash()` (bcrypt, cost 10+).

---

### 🔑 1.2 Tabel `user` (Akun Staf / Admin Legacy)
Tabel ini digunakan untuk autentikasi manual staf internal melalui panel administrator sistem pemetaan.

| Nama Field | Tipe Data | Kunci | Keterangan |
|---|---|---|---|
| `id` | INT | PK, AI | ID unik administrator |
| `email` | VARCHAR(255) | — | Email staf/admin |
| `pass` | VARCHAR(255) | — | Password (di-hash menggunakan SHA-1) |
| `nama` | VARCHAR(255) | — | Nama lengkap staf |

---

### 💬 1.3 Tabel `tb_diskusi` (Forum Topik Diskusi)
Mencatat topik diskusi yang diinisiasi oleh warga di forum komunitas.

| Nama Field | Tipe Data | Kunci | Keterangan |
|---|---|---|---|
| `id_diskusi` | INT | PK, AI | ID unik topik diskusi |
| `user_id` | INT | FK → users.id | ID pembuat topik (NULL jika akun dihapus) |
| `nama_user` | VARCHAR(255) | — | Nama pengirim (disinkronkan dari username) |
| `email_user` | VARCHAR(255) | — | Email pengirim topik |
| `judul_topik` | VARCHAR(255) | — | Judul permasalahan forum |
| `kategori` | VARCHAR(50) | — | Kategori masalah (RTLH, Sengketa Lahan, dll) |
| `isi_diskusi` | TEXT | — | Deskripsi lengkap masalah |
| `created_at` | DATETIME | — | Tanggal pembuatan topik |

> **Catatan:** Kolom `nama_user` disinkronkan otomatis saat user mengubah username di pengaturan. Jika akun dihapus, `user_id` di-set NULL dan `nama_user` diubah ke "Akun Dihapus".

---

### 💬 1.4 Tabel `tb_komentar` (Balasan Diskusi)
Menampung komentar, solusi, dan tanggapan pada topik diskusi.

| Nama Field | Tipe Data | Kunci | Keterangan |
|---|---|---|---|
| `id_komentar` | INT | PK, AI | ID unik komentar |
| `id_diskusi` | INT | FK → tb_diskusi | Relasi ke topik diskusi |
| `user_id` | INT | FK → users.id | ID pengirim komentar (NULL jika akun dihapus) |
| `parent_id` | INT | FK → tb_komentar | ID komentar induk untuk threading (NULL jika root) |
| `nama_komentator` | VARCHAR(255) | — | Nama pengirim (disinkronkan dari username) |
| `isi_komentar` | TEXT | — | Isi tanggapan |
| `role` | VARCHAR(50) | — | Peran (Warga / Petugas Disperakim) — ditentukan backend |
| `created_at` | DATETIME | — | Tanggal pengiriman komentar |

---

### 👍 1.5 Tabel `tb_forum_likes` (Voting Forum)
Menyimpan data like/upvote pada topik diskusi.

| Nama Field | Tipe Data | Kunci | Keterangan |
|---|---|---|---|
| `id` | INT | PK, AI | ID unik |
| `id_diskusi` | INT | FK → tb_diskusi | Relasi ke topik yang di-like |
| `user_id` | INT | FK → users.id | ID pengguna yang memberi like |
| `created_at` | DATETIME | — | Timestamp like |

> **Constraint:** Kombinasi `id_diskusi` + `user_id` bersifat unique (satu user = satu like per topik).

---

### 🤖 1.6 Tabel `chat_rooms` (Live Chat & Chatbot AI Sessions)
Mendukung interaksi chat warga dengan AI chatbot maupun eskalasi ke admin.

| Nama Field | Tipe Data | Kunci | Keterangan |
|---|---|---|---|
| `id` | INT | PK, AI | ID unik sesi chat |
| `session_token` | VARCHAR(255) | Unique | Token session acak dari browser |
| `status` | VARCHAR(50) | — | Status sesi (`bot` atau `admin`) |

> **Catatan:** Chat diidentifikasi berdasarkan `session_token` browser, bukan `user_id`.

---

### 🤖 1.7 Tabel `chat_messages` (Isi Chat Transkrip)
Menyimpan riwayat transkrip pesan dalam sesi percakapan.

| Nama Field | Tipe Data | Kunci | Keterangan |
|---|---|---|---|
| `id` | INT | PK, AI | ID pesan |
| `chat_room_id` | INT | FK → chat_rooms.id | Relasi ke sesi chat |
| `sender` | VARCHAR(50) | — | Pengirim (`user` / `bot` / `admin`) |
| `message` | TEXT | — | Isi teks pesan |

---

### 🗺️ 1.8 Tabel GIS & Spasial Sektoral
Tabel-tabel ini mendukung visualisasi peta sebaran dan data spasial infrastruktur:

1.  **`menu`** (id, link, default) — Konfigurasi navigasi sidebar.
2.  **`multi`** (id_user, id_menu) — Otoritas relasi menu dinamis per user.
3.  **`kondisi`** (Id_Saluran, tahun, HM, geojson) — Titik spasial kondisi fisik saluran air.
4.  **`irigasi`** (UPTD, KONDISI, PANJANG, geojson) — Cakupan irigasi pengairan.
5.  **`saluran_pembuang`** (UPTD, KONDISI, PANJANG, geojson) — Data saluran pembuangan.
6.  **`bendung`** (UPTD, KONDISI, geojson) — Persebaran bendungan daerah.

---

### 📋 1.9 Tabel `program_kategori` & `programs` (Master Program Perumahan)
Tabel untuk mengatur program perumahan dan kriteria kelayakannya.

**`program_kategori`**
| Nama Field | Tipe Data | Kunci | Keterangan |
|---|---|---|---|
| `id` | INT | PK, AI | ID unik kategori |
| `nama_kategori` | VARCHAR(100) | — | Nama kategori (KPR, RTLH, dll) |
| `created_at` | DATETIME | — | Tanggal pembuatan |

**`programs`**
| Nama Field | Tipe Data | Kunci | Keterangan |
|---|---|---|---|
| `id` | INT | PK, AI | ID unik program |
| `id_kategori` | INT | FK → program_kategori | Relasi ke kategori |
| `kode_program` | VARCHAR(50) | Unique | Kode identifier (flpp, oemah_lestari) |
| `nama_program` | VARCHAR(255) | — | Nama lengkap program |
| `deskripsi_singkat` | TEXT | — | Deskripsi |
| `batas_penghasilan_max` | DECIMAL(15,2) | — | Maksimal penghasilan untuk *Smart Filter* |
| `is_active` | TINYINT(1) | — | Status aktif program |

---

### 📋 1.10 Tabel `housing_queue` (Antrean & Onboarding Journey)
Tabel utama Fase 9 untuk menyimpan pendaftaran warga hasil *filtering* SIMPERUM.

| Nama Field | Tipe Data | Kunci | Keterangan |
|---|---|---|---|
| `id` | INT | PK, AI | ID unik antrean |
| `user_id` | INT | FK → users.id | (Opsional) ID user jika login |
| `program_id` | INT | FK → programs.id | Program yang dipilih/direkomendasikan |
| `nik_pengaju` | VARCHAR(255) | — | NIK yang digunakan untuk daftar |
| `nama_lengkap` | VARCHAR(255) | — | Nama lengkap pengaju |
| `data_simperum_json` | TEXT | — | Hasil tarikan JSON riil dari API SIMPERUM |
| `data_survey_json` | TEXT | — | Hasil *Kalkulator Kelayakan* (pekerjaan, gaji, dll) |
| `status_antrean` | ENUM | — | `pending`, `approved`, `rejected` |
| `catatan_admin` | TEXT | — | Alasan penolakan / persetujuan dari admin |
| `created_at` | DATETIME | — | Tanggal pengajuan |

---

## 🔒 2. SKEMA ENKRIPSI DATA PII (UU PDP Compliance)

Untuk memenuhi **UU No. 27 Tahun 2022 tentang Perlindungan Data Pribadi**, kolom sensitif dienkripsi:

```
[NIK Input] ──┬──→ [Hash SHA-256 + Pepper] ──→ Simpan di `nik_lookup_hash` (Index Cepat)
              │
              └───→ [AES-256-GCM + Key]    ──→ Simpan di `nik` (Terenkripsi)

[Alamat Input] ──→ [AES-256-GCM + Key]     ──→ Simpan di `alamat` (Terenkripsi)
```

**Konfigurasi kunci** disimpan di file `.env` (tidak masuk version control):
- `KPKP_DATA_KEY` — 32-byte kunci enkripsi AES
- `KPKP_DATA_PEPPER` — String acak untuk hashing NIK

---

## 3. DIAGRAM RELASI ANTAR TABEL (Simplified ERD)

```
┌──────────────┐       ┌──────────────────┐       ┌─────────────────┐
│    users     │       │    tb_diskusi     │       │   tb_komentar   │
│──────────────│       │──────────────────│       │─────────────────│
│ id (PK)      │◄──┐   │ id_diskusi (PK)  │◄──┐   │ id_komentar(PK) │
│ username     │   ├──►│ user_id (FK)     │   ├──►│ id_diskusi (FK) │
│ name         │   │   │ nama_user        │   │   │ user_id (FK)    │
│ email        │   │   │ kategori         │   │   │ parent_id (FK)──┤►(self)
│ password     │   │   │ judul_topik      │   │   │ nama_komentator │
│ nik (enc)    │   │   │ isi_diskusi      │   │   │ isi_komentar    │
│ alamat (enc) │   │   └──────────────────┘   │   │ role            │
│ kategori     │   │                           │   └─────────────────┘
│ avatar       │   │   ┌──────────────────┐   │
│ phone        │   │   │ tb_forum_likes   │   │
└──────────────┘   ├──►│ user_id (FK)     │   │
                   │   │ id_diskusi (FK)───┘   │
                   │   └──────────────────┘   │
                   │                           │
                   │   ┌──────────────────┐   │
                   │   │   chat_rooms     │   │
                   │   │ session_token    │   │
                   │   │ status           │   │
                   │   └───────┬──────────┘   │
                   │           │               │
                   │   ┌───────▼──────────┐   │
                   │   │  chat_messages   │   │
                   │   │ chat_room_id(FK) │   │
                   │   │ sender           │   │
                   │   │ message          │   │
                   │   └──────────────────┘   │
                   │                           │
                   │   ┌──────────────────┐    │
                   │   │ program_kategori │    │
                   │   │ id (PK)          │◄──┐│
                   │   └──────────────────┘   ││
                   │   ┌──────────────────┐   ││
                   │   │    programs      │   ││
                   │   │ id (PK)          │◄─┐├┘
                   │   │ id_kategori (FK) ├──┘│
                   │   └──────────────────┘   │
                   │   ┌──────────────────┐   │
                   │   │  housing_queue   │   │
                   ├──►│ user_id (FK)     │   │
                   │   │ program_id (FK)  ├───┘
                   │   │ status_antrean   │   
                   │   └──────────────────┘   
                   └───────────────────────────┘
```

---
*Dokumen ini diperbarui otomatis oleh Antigravity AI Coding Assistant — 9 Juni 2026.*
