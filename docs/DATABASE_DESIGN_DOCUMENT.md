# 🗄️ DATABASE DESIGN DOCUMENT
## Klinik PKP — Super App Disperakim Provinsi Jawa Tengah
**Target Database:** `klinikpkp`

---

## 1. STRUKTUR & KAMUS DATA TABEL (Data Dictionary)

Berdasarkan analisis file model dan controller, database `klinikpkp` terdiri dari beberapa tabel utama yang mendukung fitur forum komunitas, GIS spasial, monitoring saluran, chat interaktif, dan autentikasi.

### 👤 1.1 Tabel `users` (Akun Google OAuth 2.0)
Tabel ini digunakan untuk mencatat pendaftaran mandiri masyarakat umum, pengembang, dan mahasiswa magang melalui sistem Google Single Sign-On (SSO).

| Nama Field | Tipe Data | Kunci | Keterangan |
|---|---|---|---|
| `id` | INT | PK, AI | ID unik baris database |
| `google_id` | VARCHAR(255) | - | ID unik pengguna dari Google |
| `name` | VARCHAR(255) | - | Nama Lengkap (dari Google / input form) |
| `email` | VARCHAR(255) | Unique | Email Gmail pengguna |
| `avatar` | VARCHAR(255) | - | URL Foto profil dari Google |
| `nik` | VARCHAR(16) | - | NIK 16 Digit (Plaintext L4 - Kebutuhan Enkripsi) |
| `kategori` | VARCHAR(50) | - | Kategori akun (Masyarakat Umum / Pengembang / Mahasiswa Magang) |
| `alamat` | TEXT | - | Alamat Domisili Lengkap (Plaintext L4 - Kebutuhan Enkripsi) |
| `updated_at` | DATETIME | - | Waktu update data profil terakhir |

---

### 🔑 1.2 Tabel `user` (Akun Staf / Admin Legacy)
Tabel ini digunakan untuk autentikasi manual staf internal melalui panel administrator sistem pemetaan.

| Nama Field | Tipe Data | Kunci | Keterangan |
|---|---|---|---|
| `id` | INT | PK, AI | ID unik administrator |
| `email` | VARCHAR(255) | - | Email staf/admin |
| `pass` | VARCHAR(255) | - | Password (di-hash menggunakan SHA-1) |
| `nama` | VARCHAR(255) | - | Nama lengkap staf |

---

### 💬 1.3 Tabel `tb_diskusi` (Forum Topik Diskusi)
Mencatat topik diskusi sarana prasarana yang diinisiasi oleh warga di forum komunitas.

| Nama Field | Tipe Data | Kunci | Keterangan |
|---|---|---|---|
| `id_diskusi` | INT | PK, AI | ID unik topik diskusi |
| `nama_user` | VARCHAR(255) | - | Nama pengirim topik |
| `email_user` | VARCHAR(255) | - | Email pengirim topik |
| `judul_topik` | VARCHAR(255) | - | Judul permasalahan forum |
| `kategori` | VARCHAR(50) | - | Kategori masalah (RTLH, Sengketa Lahan, dll) |
| `isi_diskusi` | TEXT | - | Deskripsi lengkap masalah |
| `created_at` | DATETIME | - | Tanggal pembuatan topik |

---

### 💬 1.4 Tabel `tb_komentar` (Balasan Diskusi)
Menampung komentar, solusi, dan regulasi sektoral terkait topik diskusi.

| Nama Field | Tipe Data | Kunci | Keterangan |
|---|---|---|---|
| `id_komentar` | INT | PK, AI | ID unik komentar |
| `id_diskusi` | INT | FK | Relasi ke `tb_diskusi.id_diskusi` |
| `nama_komentator` | VARCHAR(255) | - | Nama pengirim komentar |
| `isi_komentar` | TEXT | - | Isi tanggapan |
| `role` | VARCHAR(50) | - | Peran komentator (Warga / Petugas Disperakim) |
| `created_at` | DATETIME | - | Tanggal pengiriman komentar |

---

### 🤖 1.5 Tabel `chat_rooms` (Live Chat & Chatbot AI Sessions)
Mendukung interaksi chat interaktif warga dengan AI chatbot maupun eskalasi live chat dengan admin.

| Nama Field | Tipe Data | Kunci | Keterangan |
|---|---|---|---|
| `id` | INT | PK, AI | ID unik sesi chat |
| `session_token`| VARCHAR(255) | Unique | Token session acak unik dari browser |
| `status` | VARCHAR(50) | - | Status sesi (`bot` atau `admin`) |

---

### 🤖 1.6 Tabel `chat_messages` (Isi Chat Transkrip)
Menyimpan riwayat transkrip pesan masuk dan keluar dalam sesi percakapan.

| Nama Field | Tipe Data | Kunci | Keterangan |
|---|---|---|---|
| `id` | INT | PK, AI | ID pesan |
| `chat_room_id` | INT | FK | Relasi ke `chat_rooms.id` |
| `sender` | VARCHAR(50) | - | Pengirim (`user` / `bot` / `admin`) |
| `message` | TEXT | - | Isi teks pesan |

---

### 🗺️ 1.7 Tabel GIS & Spasial Sektoral (Peta & Saluran)
Tabel-tabel ini mendukung visualisasi peta sebaran perumahan serta data spasial infrastruktur pengairan/saluran di Jawa Tengah:
1.  **`menu`** (id, link, default) — Konfigurasi navigasi sidebar.
2.  **`multi`** (id_user, id_menu) — Otoritas relasi menu dinamis per user.
3.  **`kondisi`** (Id_Saluran, tahun, HM, geojson) — Titik spasial kondisi fisik jaringan saluran air.
4.  **`irigasi`** (UPTD, KONDISI, PANJANG, geojson) — Wilayah cakupan irigasi pengairan.
5.  **`saluran_pembuang`** (UPTD, KONDISI, PANJANG, geojson) — Data saluran pembuangan akhir.
6.  **`bendung`** (UPTD, KONDISI, geojson) — Data persebaran bendungan daerah.

---

## 🔒 2. REKOMENDASI DESAIN ENKRIPSI DATA PII (L4 - UU PDP Compliance)

Untuk memenuhi kepatuhan **UU No. 27 Tahun 2022 tentang Perlindungan Data Pribadi**, kolom sensitif pada tabel `users` (NIK dan Alamat) direkomendasikan untuk diubah format penyimpanannya di database menjadi terenkripsi menggunakan algoritma **AES-256-GCM**.

### 📐 Skema Migrasi Tabel Terenkripsi:
1.  **Kolom `nik`** diubah menjadi `nik_encrypted` (VARCHAR/BLOB terenkripsi secara acak).
2.  **Kolom `alamat`** diubah menjadi `alamat_encrypted` (TEXT terenkripsi secara acak).
3.  **Kolom `nik_lookup_hash`** ditambahkan (VARCHAR(64) berisi SHA-256 dari NIK plaintext + Salt) untuk memudahkan pencarian instan *unique constraint* di database tanpa perlu melakukan dekripsi massal (Deterministic Hash Lookup).

```
[NIK Input] ──┬──→ [Hash SHA-256 + Salt] ──→ Simpan di `nik_lookup_hash` (Index Cepat)
              │
              └───→ [AES-256-GCM + Key]  ──→ Simpan di `nik_encrypted` (Aman / Sandi)
```
Dengan skema ini, integritas database terjaga, performa query `WHERE nik_lookup_hash = ?` tetap berjalan instan, namun data pribadi warga di database tetap 100% aman dari kebocoran fisik.
