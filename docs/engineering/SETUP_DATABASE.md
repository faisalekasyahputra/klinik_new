# 🚀 Panduan Setup Database — Klinik PKP

## Prasyarat
- MySQL 5.7+ atau MariaDB 10.3+
- XAMPP / Laragon / server MySQL lainnya

## Langkah Import

### 1. Buat Database
```sql
CREATE DATABASE klinikpkp CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

### 2. Import Schema
```bash
# Via command line
mysql -u root -p klinikpkp < docs/schema_klinikpkp.sql

# Atau via phpMyAdmin:
# 1. Buka http://localhost/phpmyadmin
# 2. Pilih database "klinikpkp"
# 3. Tab "Import" → pilih file schema_klinikpkp.sql → Go
```

### 3. Konfigurasi Environment
Buat file `.env` di root proyek (`c:\xampp\htdocs\klinik_new\.env`):

```env
# Database
DB_HOST=localhost
DB_NAME=klinikpkp
DB_USER=root
DB_PASS=

# Google OAuth
GOOGLE_CLIENT_ID=your_client_id_here
GOOGLE_CLIENT_SECRET=your_client_secret_here

# Enkripsi (WAJIB — generate random 32-byte key)
KPKP_DATA_KEY=your_random_32_byte_key_here
KPKP_DATA_PEPPER=your_random_pepper_string_here
```

> ⚠️ **PENTING:** Jangan commit file `.env` ke git. Sudah ada di `.gitignore`.

### 4. Verifikasi
Buka `http://localhost/klinik_new/` — homepage harus tampil tanpa error database.

## Daftar Tabel

| Tabel | Fungsi |
|-------|--------|
| `users` | Akun publik (hybrid: Google + tradisional) |
| `user` | Akun admin/staf legacy |
| `user_documents` | Dokumen upload pengguna (KTP, SIUP, dll) |
| `tb_diskusi` | Topik forum diskusi |
| `tb_komentar` | Komentar/balasan forum (nested via `reply_to`) |
| `tb_forum_likes` | Like/upvote pada diskusi & komentar |
| `chat_rooms` | Sesi live chat / chatbot |
| `chat_messages` | Riwayat pesan chat |
| `kondisi` | Data spasial kondisi saluran |
| `irigasi` | Data irigasi GIS |
| `saluran_pembuang` | Data saluran pembuang GIS |
| `bendung` | Data bendungan GIS |
| `sosmed_perumahan` | Sosial media pengembang perumahan |
| `menu` | Konfigurasi navigasi sidebar |
| `multi` | Relasi menu per-user |

## Catatan
- Kolom `nik` dan `alamat` di tabel `users` menyimpan data **terenkripsi AES-256-GCM**, bukan plaintext.
- `nik_lookup_hash` berisi SHA-256 hash untuk pencarian cepat tanpa dekripsi.
- Password di-hash menggunakan `password_hash()` (bcrypt).
