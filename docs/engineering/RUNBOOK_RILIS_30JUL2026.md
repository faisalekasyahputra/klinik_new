# Runbook Rilis - 30 Juli 2026

Rilis Rekam Data (D0-D6) + pelunasan utang teknis (P0, U0-U4, S0-S8, A1-A6) ke
production `floralwhite-lion-710022`.

**Status: BELUM DIJALANKAN.** Dokumen ini ditulis sebelum eksekusi. Setiap
gerbang di bawah harus dilewati dengan bukti yang dilihat mata, bukan asumsi.

---

## 0. Yang sudah diverifikasi di lokal (30 Jul 2026)

| Fakta | Cara diverifikasi |
|---|---|
| `chore/utang-teknis-p0` (`d9a2530`) adalah **superset ketat** dari `feature/homepage-portal-v2` (`9903743`) | `git merge-base --is-ancestor 9903743 chore/utang-teknis-p0` → ya |
| Merge ke branch deploy = **fast-forward, nol konflik** | `git merge-tree` → nol penanda konflik |
| 17 commit di atas production: 6 Rekam Data + 11 utang | `git log ac42941..d9a2530` |
| Kedua worktree bersih | `git status --short` → kosong di keduanya |
| `.htaccess` **tidak berubah** antara `ac42941` dan `d9a2530` | `git diff --stat ac42941..d9a2530 -- .htaccess` → kosong |
| Tidak ada yang menjalankan migrasi otomatis | `enable_hooks = FALSE`; `Migrate::index()` memakai `latest()`, dipanggil manual |
| Tidak ada tautan menggantung ke halaman yang dicabut | `git grep` di `application/views` + `assets` → hanya komentar PHP |

**Yang BELUM diverifikasi:** keadaan production sungguhan. Percobaan SSH dari
sesi ini diblokir, jadi Fase 1 di bawah adalah **penemuan**, bukan konfirmasi.
Jangan lewati.

---

## ⚠️ Satu keputusan yang tidak boleh salah

**Rilis `d9a2530` (branch utang), BUKAN `9903743` (branch Rekam Data saja).**

Pada `9903743`, `application/config/migration.php` masih menunjuk
`migration_version = 20260701000010` padahal berkas migrasi sudah sampai
`…021`. Selisih itu tidak berbahaya selama hanya `latest()` yang dipakai -
tetapi siapa pun yang kelak memanggil `Migration::current()` akan memicu
**downgrade** `…020` → `…010`, yang menjalankan `down()` migrasi 11-20 dan
membuang tabel serta kolom production. Perbaikannya (S8) ada di branch utang,
di commit `0bcaed3`, dan menaikkan nilai itu ke `…022`.

Karena branch utang adalah superset, merilisnya sekaligus menutup lubang ini.
Merilis Rekam Data sendirian justru **menanam** lubangnya.

---

## Fase 1 - Baca keadaan production (baca-saja, wajib)

```bash
ssh hostinger
```

Lalu di server:

```bash
ls -1 ~/domains
```

Temukan direktori situs aktif, lalu (ganti `<SITUS>`):

```bash
cd ~/domains/<SITUS>/public_html && git log -1 --oneline && git status --porcelain && php index.php migrate status
```

**Yang diharapkan:**

- `git log -1` → `ac42941`
- `git status --porcelain` → kosong, atau hanya `.env` / berkas tak-terlacak
- `migrate status` → versi skema **`20260701000020`**

**GERBANG.** Kalau versi skema bukan `…020`, **BERHENTI**. AGENTS.md sendiri
masih kontradiksi soal ini (baris 17 bilang `…20`, baris 26 bilang `…16`) -
angka yang keluar dari server inilah kebenarannya, dan rencana migrasi harus
disusun ulang mengikutinya. Kalau `git status` menunjukkan berkas terlacak yang
termodifikasi, catat apa saja sebelum menyentuh apa pun; deploy akan menimpanya.

Sekaligus catat jumlah tabel - dipakai membandingkan hasil backup nanti:

```bash
cd ~/domains/<SITUS>/public_html && php -r '$e=parse_ini_file(".env"); $m=new mysqli("localhost",$e["DB_USER"],$e["DB_PASS"],$e["DB_NAME"]); $r=$m->query("SELECT COUNT(*) c FROM information_schema.tables WHERE table_schema=DATABASE()"); echo "tabel: ".$r->fetch_assoc()["c"]."\n";'
```

Diharapkan **29 tabel** (naik dari 23 pada rilis 29 Jul).

---

## Fase 2 - Backup (wajib, sebelum apa pun berubah)

```bash
cd ~ && mysqldump -u u504551489_klinikstg -p u504551489_klinikstg | gzip > ~/backup_klinik_pre_rilis_30jul.sql.gz
```

*(nama user/DB ikuti `.env` server - jangan disalin buta dari sini)*

Verifikasi backup **terbaca**, bukan sekadar ada:

```bash
gzip -t ~/backup_klinik_pre_rilis_30jul.sql.gz && echo "gzip OK" && zcat ~/backup_klinik_pre_rilis_30jul.sql.gz | grep -c "^CREATE TABLE" && ls -lh ~/backup_klinik_pre_rilis_30jul.sql.gz
```

Backup berkas konfigurasi juga:

```bash
cd ~/domains/<SITUS>/public_html && cp .env .env.bak-30jul && cp .htaccess .htaccess.bak-30jul && ls -la .env.bak-30jul .htaccess.bak-30jul
```

**GERBANG.** Jumlah `CREATE TABLE` harus sama dengan jumlah tabel di Fase 1
(29). Kalau `gzip -t` gagal atau hitungannya meleset, **BERHENTI** - backup
yang tidak terbukti terbaca sama saja dengan tidak punya backup.

---

## Fase 3 - Migrasi skema dulu, kode belakangan

Urutan ini terbukti pada rilis 29 Jul: skema baru + kode lama aman karena kedua
migrasi hanya **menambah** (`…021` membuat 6 tabel `rd_*`, `…022` membuat
`forum_laporan_komentar`). Kebalikannya - kode baru bertemu skema lama -
langsung 500.

**3a. Salin HANYA dua berkas migrasi** (dari mesin lokal):

```bash
scp -P 65002 application/migrations/20260701000021_add_rekam_data.php application/migrations/20260701000022_add_forum_laporan_komentar.php hostinger:~/domains/<SITUS>/public_html/application/migrations/
```

**3b. Jalankan migrasi** (di server):

```bash
cd ~/domains/<SITUS>/public_html && php index.php migrate && php index.php migrate status
```

**Jebakan yang sudah pernah memakan korban:** `Migrasi sukses, versi skema
sekarang: 1` **bukan** berarti turun ke versi 1. CI3 mengembalikan `TRUE`
(tercetak `1`) kalau skema sudah di target. Jangan panik - verifikasi langsung:

```bash
cd ~/domains/<SITUS>/public_html && php -r '$e=parse_ini_file(".env"); $m=new mysqli("localhost",$e["DB_USER"],$e["DB_PASS"],$e["DB_NAME"]); $r=$m->query("SELECT version FROM migrations"); $v=$r->fetch_assoc(); echo "versi: ".$v["version"]."\n"; $t=$m->query("SHOW TABLES LIKE \"rd_%\""); echo "tabel rd_*: ".$t->num_rows."\n"; $f=$m->query("SHOW TABLES LIKE \"forum_laporan_komentar\""); echo "forum_laporan_komentar: ".($f->num_rows?"ADA":"TIDAK ADA")."\n";'
```

**GERBANG.** Harus terbaca `versi: 20260701000022`, `tabel rd_*: 6`,
`forum_laporan_komentar: ADA`. Kalau tidak - **BERHENTI dan pulihkan DB**
(lihat Rollback A). Jangan lanjut ke push.

**3c. Hapus berkas yang barusan disalin.** Ini bukan kerapian, ini pencegah
kegagalan deploy: keduanya kini **tak-terlacak** di server, sementara commit
yang masuk akan menambahkan berkas di jalur yang sama. `git pull`/`reset --hard`
akan menolak dengan *"untracked working tree files would be overwritten"*.

```bash
cd ~/domains/<SITUS>/public_html && rm application/migrations/20260701000021_add_rekam_data.php application/migrations/20260701000022_add_forum_laporan_komentar.php && git status --porcelain
```

Barisnya sudah tercatat di tabel `migrations`, jadi menghapus berkasnya tidak
membatalkan apa pun. Push nanti mengembalikan keduanya dengan isi identik.

**3d. Uji kode-lama + skema-baru masih sehat.** Dari mesin mana pun:

```bash
for u in "" "auth/login" "golek_omah" "umum" "listkabupaten"; do printf "%-16s " "/$u"; curl -s -o /dev/null -w "%{http_code}\n" "https://floralwhite-lion-710022.hostingersite.com/$u"; done
```

**GERBANG.** Kelimanya harus `200`. Kalau ada yang bukan - **BERHENTI**,
pulihkan DB (Rollback A). Belum ada kode baru yang di-push, jadi pemulihannya
masih murah.

---

## Fase 4 - Rilis kode

**4a. Fast-forward branch deploy** (di lokal, worktree `C:\xampp\htdocs\klinik_new`):

```bash
git checkout feature/homepage-portal-v2 && git merge --ff-only chore/utang-teknis-p0 && git log -1 --oneline
```

`--ff-only` sengaja: kalau ada yang berubah sejak dokumen ini ditulis, perintah
ini **gagal** alih-alih membuat commit merge diam-diam.

**4b. Bandingkan `.htaccess` server dengan versi yang akan mendarat.** `.htaccess`
dilacak git DAN memuat pengaman yang dipasang manual (penolak dotfile/`docs/`,
`SetEnv CI_ENV production`). Kalau isinya berbeda, deploy akan mencabut pengaman
itu dan `.env` kembali terbuka ke publik.

Di server:
```bash
cd ~/domains/<SITUS>/public_html && git hash-object .htaccess
```
Di lokal:
```bash
git hash-object .htaccess
```

**GERBANG.** Kedua hash harus **sama**. Kalau berbeda, **BERHENTI** -
selamatkan versi server dulu (`.htaccess.bak-30jul` sudah dibuat di Fase 2),
dan putuskan mana yang benar sebelum push.

**4c. Push - ini yang merilis:**

```bash
git push origin feature/homepage-portal-v2
```

**4d. Verifikasi deploy mendarat** (di server, setelah menunggu auto-deploy):

```bash
cd ~/domains/<SITUS>/public_html && git log -1 --oneline && git status --porcelain && php index.php migrate status
```

Diharapkan `d9a2530`, status bersih, skema `20260701000022`.

---

## Fase 5 - Smoke test

Ganti `BASE` dengan `https://floralwhite-lion-710022.hostingersite.com`.

**5a. Harus tetap `200` (regresi inti):**

| Jalur | Kenapa diuji |
|---|---|
| `/` | beranda; S1 mengubah transisi halaman |
| `/auth/login` | pintu masuk semua role |
| `/golek_omah` | kartu wizard warga |
| `/umum` | forum - C1, B3, S2 semua menyentuh ini |
| `/listkabupaten` | A3 mengubah isinya |
| `/Statistika` | A2 membalik arah klaimnya, halamannya **tetap ada** |
| `/warga/pendataan` | wizard warga (tanpa sesi → `302` ke login, itu benar) |
| `/akun` | tanpa sesi → `302`, itu benar |

```bash
for u in "" "auth/login" "golek_omah" "umum" "listkabupaten" "Statistika" "warga/pendataan" "akun"; do printf "%-20s " "/$u"; curl -s -o /dev/null -w "%{http_code}\n" "$BASE/$u"; done
```

**5b. Harus `404` (permukaan yang sengaja dicabut):**

```bash
for u in "sikaper" "Sikaper/index" "struktur" "sebaran_rusun" "profil_kumuh" "sebaran_sdgs" "Admin_Settings" "umum/info_rumah" "Chat/api_bot/halo" "Chat/ambil_pesan" "Chat/register_session"; do printf "%-24s " "/$u"; curl -s -o /dev/null -w "%{http_code}\n" "$BASE/$u"; done
```

Kesebelasnya `404`. Yang tiga terakhir (Chat) paling penting: dulu `api_bot`
routable anonim lewat GET dan setiap hit menembak Gemini memakai kunci API dinas.

**5c. Cookie mengikuti environment (U0 + `cookie_secure`):**

```bash
curl -s -I "$BASE/" | grep -i "set-cookie"
```

Harus ada atribut `Secure`. Kalau tidak ada, `ENVIRONMENT` tidak terbaca
`production` di server - periksa `SetEnv CI_ENV production` di `.htaccess`.
**Jangan taruh `CI_ENV` di `.env`**, tidak berpengaruh sama sekali: `index.php`
mendefinisikan `ENVIRONMENT` ratusan baris sebelum `.env` diurai.

**5d. Tidak ada galat yang bocor:**

```bash
curl -s "$BASE/" | grep -i -c "notice\|warning\|fatal error\|<b>Deprecated"
```

Harus `0`.

**5e. Lintasan browser sungguhan** (tidak bisa digantikan curl):

1. Login `adminkabkota@example.com` / `password` → buka Rekam Data → isi satu
   bagian perumahan → Kirim → status jadi `terkirim`
2. Login `adminbidang@example.com` → Peninjauan → laporan tadi muncul → Minta
   Perbaikan → kembali ke akun kabkota, status `perlu_perbaikan`
3. Buka forum sebagai anonim → tombol lapor komentar tidak lagi bisa dipakai
   tanpa login
4. Buka satu diskusi forum, muat ulang → `view_count` naik **1**, bukan 2

---

## Rollback

**A - migrasi gagal (belum ada push).** Pulihkan DB dari backup Fase 2:

```bash
cd ~ && zcat backup_klinik_pre_rilis_30jul.sql.gz | mysql -u u504551489_klinikstg -p u504551489_klinikstg && cd ~/domains/<SITUS>/public_html && php index.php migrate status
```

Lalu hapus dua berkas migrasi yang disalin kalau masih ada. Kode belum berubah,
jadi setelah ini production kembali persis seperti sebelum runbook dimulai.

**B - kode sudah di-push dan bermasalah.** Kembalikan branch deploy ke commit
production lama:

```bash
git push --force-with-lease origin ac42941:feature/homepage-portal-v2
```

Aman karena seluruh 17 commit tetap hidup di dua branch cadangan yang sudah ada
di origin: `chore/utang-teknis-p0` (`d9a2530`) dan `backup/rekam-data-d6`
(`9903743`). Tidak ada yang hilang.

**Skema tidak perlu ikut dimundurkan** - kedua migrasi hanya menambah tabel,
dan kode lama tidak menyentuhnya. Turunkan skema hanya kalau migrasinya sendiri
yang merusak, lewat Rollback A.

---

## Yang akan berubah di mata pengunjung

Bukan cuma perbaikan senyap - ada halaman yang benar-benar hilang. Ini
keputusan sadar user ("oke cabut"), dicatat di sini supaya tidak ada yang
kaget saat dinas membukanya:

| Hilang | Alasan |
|---|---|
| `/sebaran_rusun`, `/profil_kumuh`, `/sebaran_sdgs` | angka literal tanpa sumber; rusunawa bernama nyata dengan atribut karangan (A1) |
| `/struktur` | memajang nama pejabat tanpa sumber data (A4) |
| `/sikaper` | integrasi mati; kredensial menunggu kanal rotasi (B6, keputusan #5) |
| `/umum/info_rumah` | halaman yatim (S9) |
| `/Admin_Settings` | panel pengaturan yang tidak menulis ke mana pun (A6) |
| Widget chat | dikarantina 404 sampai keputusan #7 (B2) |

`/Statistika` **tetap ada** - yang berubah arah klaimnya: sekarang menyatakan
terang-terangan bahwa angkanya berasal dari SIMPERUM, bukan mengaku sebagai
sumber (A2).

---

## Yang TIDAK dirilis (masih terkunci keputusan)

| Butir | Menunggu |
|---|---|
| #5 B5 | kanal rotasi kredensial Sikaper - library & config sengaja ditinggal utuh |
| #7 C2/C3/B7 | chat dicabut atau dibangun; `Chat.php:157` (TLS) menunggu jawabannya |
| #8 B8 | kontrak cek tiket/NIK - butuh 3 rilis terpisah, tidak muat dalam satu hari |
| #9 | kebijakan retensi (S4 cleanup + S7 audit) |
| #11 | syarat bukti resmi (S3 gerbang bersyarat) |

---

## Setelah rilis mendarat

Perbarui `AGENTS.md` §0:

- baris 26 masih menulis skema production `20260701000016` - **salah**, dan
  sudah salah sebelum rilis ini. Ganti dengan angka yang keluar dari
  `migrate status` di Fase 4d.
- catat commit production baru (`d9a2530`) dan tanggalnya
- pindahkan Rekam Data D0-D6 dari "selesai lokal" ke "tayang di production"
- catat halaman yang dicabut, supaya agent berikutnya tidak mencarinya
