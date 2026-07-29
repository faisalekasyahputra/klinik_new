# 🚀 Panduan Setup Database — Klinik PKP (v3.0)

Dokumen ini berisi panduan setup dan daftar skema database yang telah direstrukturisasi dengan sistem *Prefix* Modular untuk mempermudah manajemen data.

## Prasyarat
- MySQL 5.7+ atau MariaDB 10.3+
- XAMPP / Laragon (Lingkungan Lokal)

## ⚠️ WAJIB: setel `CI_ENV=development` di lingkungan lokal

Sejak butir B1/U0, `index.php` **fail-closed**: bila `CI_ENV` tidak ada atau
kosong, environment dianggap **`production`**. Tanpa langkah ini instalasi lokal
ikut menjadi production — tampilan galat hilang, dan `cookie_secure` menyala
sehingga browser menolak cookie sesi di `http://localhost` dan **login putus
lintas-request**.

**Untuk web (Apache).** Tambahkan ke konfigurasi Apache yang **tidak ikut Git**
(mis. `conf/extra/httpd-vhosts.conf`), lalu restart Apache:

```apache
<Directory "C:/xampp/htdocs/klinik_new">
    SetEnv CI_ENV development
</Directory>
```

Jangan menaruhnya di `.htaccess` repo — berkas yang sama dibaca lokal maupun
production, sehingga salah satu lingkungan pasti salah kaprah.

**Untuk perintah CLI.** `SetEnv` Apache **hanya** berlaku untuk request HTTP;
proses CLI tidak pernah melihatnya. Setiap perintah CLI lokal karena itu harus
membawa environment-nya sendiri:

```bash
CI_ENV=development php index.php migrate
CI_ENV=development php index.php migrate uji_rekam_data_d1
```

Di PowerShell:

```powershell
$env:CI_ENV="development"; php index.php migrate
```

Membuktikan setelannya benar-benar sampai: buat berkas sesaat berisi
`<?php echo $_SERVER['CI_ENV'] ?? '(tidak ada)';`, buka lewat HTTP, lalu hapus.
Percobaan pertama 27 Jul 2026 gagal justru karena `CI_ENV` ditaruh di `.env`,
yang dibaca 250 baris terlambat (AGENTS.md §0e).

## Langkah Import

### 1. Buat Database
```sql
CREATE DATABASE klinikpkp CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

### 2. Import Schema
Skema terbaru kini berada di folder `engineering`:
```bash
# Via command line
mysql -u root -p klinikpkp < docs/engineering/schema_klinikpkp.sql
```
*Atau import secara manual via phpMyAdmin ke database `klinikpkp`.*

### 3. Konfigurasi Environment (`.env`)
Salin atau buat file `.env` di root proyek (`C:\xampp\htdocs\klinik_new\.env`):

```env
# Koneksi Database Lokal
DB_HOST=localhost
DB_NAME=klinikpkp
DB_USER=root
DB_PASS=

# Google OAuth
GOOGLE_CLIENT_ID=your_client_id_here
GOOGLE_CLIENT_SECRET=your_client_secret_here

# Enkripsi NIK & PDP (WAJIB 32-byte)
KPKP_DATA_KEY=your_random_32_byte_key_here
KPKP_DATA_PEPPER=your_random_pepper_string_here
```

## Daftar Tabel Modular (Refactored)

Sistem database Klinik PKP kini menggunakan standar *prefix* berdasarkan fungsi modul:

### 1. Modul Smart Filter & Program (`sf_`)
| Tabel | Fungsi |
|-------|--------|
| `sf_programs` | Master data program perumahan (RTLH, PB, dll) |
| `sf_program_kategori` | Kategori program |
| `sf_housing_queue` | Antrean pengajuan (*Onboarding Journey*) masyarakat |

### 2. Modul Autentikasi & Pengguna (`usr_`)
| Tabel | Fungsi |
|-------|--------|
| `usr_users` | Akun publik (SSO Google & Tradisional) |
| `usr_documents` | Dokumen persyaratan (KTP, KK, dll) pengguna |
| `user` | *(Legacy)* Akun ASN/Staf internal |

### 3. Modul Forum & Komunitas (`forum_`)
| Tabel | Fungsi |
|-------|--------|
| `forum_diskusi` | Topik diskusi / *thread* komunitas |
| `forum_komentar` | Balasan diskusi (*nested*) |
| `forum_likes` | *Upvote* / *Likes* komunitas |

### 4. Modul Sistem Utama (`sys_`)
| Tabel | Fungsi |
|-------|--------|
| `sys_menu` | Master konfigurasi navigasi dan *role* akses |
| `sys_multi` | Pemetaan *role* menu pengguna |
| `sys_settings` | Pengaturan *global* website |

### 5. Modul Data Pendukung (`data_` & lainnya)
| Tabel | Fungsi |
|-------|--------|
| `data_sosmed_perumahan` | Link media sosial SP2 |
| `kondisi`, `irigasi`, `saluran_pembuang` | Data spasial dan pemetaan kawasan GIS |

## Catatan Keamanan
- **UU PDP Compliant:** Kolom `nik` dan `alamat` di `usr_users` disimpan secara terenkripsi (`AES-256-GCM`). 
- **Pencarian Cepat:** Pencarian NIK dilakukan melalui kolom `nik_lookup_hash` yang berisi *hash* SHA-256 (tanpa perlu deskripsi manual).
- **Password:** Di-*hash* menggunakan `password_hash()` standar PHP (BCRYPT).
