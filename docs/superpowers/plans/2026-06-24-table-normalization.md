# Table Normalization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menormalisasi penamaan tabel forum dengan menghapus awalan `tb_` dan menghapus tabel terkait irigasi secara menyeluruh dari database dan *codebase*.

**Architecture:** Kita akan melakukan perubahan langsung pada level database menggunakan query RENAME dan DROP. Setelah itu, akan dilakukan penggantian (find/replace) semua string nama tabel lama pada Model dan Controller yang berhubungan. Fitur yang mengakses tabel-tabel tersebut akan diuji kembali secara manual dengan syntax linting PHP.

**Tech Stack:** MySQL, PHP (CodeIgniter 3).

---

### Task 1: RENAME dan DROP Tables pada Database MySQL

**Files:**
- Modify: `docs/database/schema_klinikpkp.sql`

- [ ] **Step 1: Jalankan Query RENAME & DROP via Command Line**
```bash
mysql -u root -e "RENAME TABLE klinikpkp.tb_diskusi TO klinikpkp.diskusi, klinikpkp.tb_forum_likes TO klinikpkp.forum_likes, klinikpkp.tb_komentar TO klinikpkp.komentar;"
mysql -u root -e "DROP TABLE IF EXISTS klinikpkp.irigasi, klinikpkp.bendung, klinikpkp.kondisi, klinikpkp.saluran_pembuang, klinikpkp.user;"
```

- [ ] **Step 2: Update file Schema SQL**
Ganti semua text dalam file `docs/database/schema_klinikpkp.sql`:
- Replace `tb_diskusi` -> `diskusi`
- Replace `tb_forum_likes` -> `forum_likes`
- Replace `tb_komentar` -> `komentar`
- Hapus struktur DROP dan CREATE untuk tabel `irigasi`, `bendung`, `kondisi`, `saluran_pembuang`, `user`.

- [ ] **Step 3: Commit**
```bash
git add docs/database/schema_klinikpkp.sql
git commit -m "db: normalize forum tables and drop irrigation and old user tables"
```

### Task 2: Update Model dan Controller Forum

**Files:**
- Modify: `application/models/Forum_model.php`
- Modify: `application/models/User_model.php`
- Modify: `application/helpers/forum_helper.php`
- Modify: `application/controllers/Admin_Dashboard.php`
- Modify: `application/controllers/Umum.php`

- [ ] **Step 1: Replace di `Forum_model.php`**
Buka `application/models/Forum_model.php`. Replace string:
- `tb_diskusi` -> `diskusi`
- `tb_komentar` -> `komentar`
- `tb_forum_likes` -> `forum_likes`

- [ ] **Step 2: Replace di `User_model.php`**
Buka `application/models/User_model.php`. Replace string:
- `tb_diskusi` -> `diskusi`
- `tb_komentar` -> `komentar`
- `tb_forum_likes` -> `forum_likes`

- [ ] **Step 3: Replace di Controller & Helper**
- `application/helpers/forum_helper.php` -> ubah `$table = 'tb_' . $type;` atau string query jika ada.
- `application/controllers/Admin_Dashboard.php:17` -> ubah pengecekan `tb_diskusi` ke `diskusi`.

- [ ] **Step 4: Linting PHP**
Jalankan linting:
```bash
php -l application/models/Forum_model.php
php -l application/models/User_model.php
php -l application/controllers/Admin_Dashboard.php
```

- [ ] **Step 5: Commit**
```bash
git add application/models/Forum_model.php application/models/User_model.php application/helpers/forum_helper.php application/controllers/Admin_Dashboard.php
git commit -m "refactor: rename forum tables in codebase"
```

### Task 3: Clean up Buka_peta Model

**Files:**
- Modify: `application/models/Buka_peta.php`

- [ ] **Step 1: Hapus Function yang spesifik Irigasi**
Hapus function-function ini dari `Buka_peta.php` karena melakukan query langsung (hardcode) ke tabel yang sudah di-drop:
- `frd_kondisi()`
- `statistik_panjang()` (query ke tabel `irigasi`)
- `statistik_panjangp()` (query ke tabel `saluran_pembuang`)
- `statistik_bendung()` (query ke tabel `bendung`)
*Catatan: Fungsi general query seperti `statistik()` biarkan tetap utuh, karena bisa dipassing string tabel lain yang masih valid.*

- [ ] **Step 2: Linting PHP**
Jalankan linting:
```bash
php -l application/models/Buka_peta.php
```

- [ ] **Step 3: Commit**
```bash
git add application/models/Buka_peta.php
git commit -m "refactor: remove deprecated irrigation queries from Buka_peta"
```
