# Panduan Hosting Klinik PKP (di luar Hostinger)

Panduan memasang Klinik PKP di server mana pun selain Hostinger: VPS, server dinas,
atau hosting cPanel lain. Hal khusus Hostinger (deploy otomatis lewat Git hPanel,
cron di hPanel, path `/opt/alt/php83`) ada di `AGENTS.md` §0a dan
`RUNBOOK_RILIS_*.md`, bukan di sini.

**Status dokumen.** Disusun 3 Okt 2026 dari kode dan konfigurasi repo. Jalur Apache
sama dengan yang berjalan di production (aturannya ada di `.htaccess`). Contoh
konfigurasi Nginx di §6 adalah terjemahan `.htaccess` yang **belum diuji di server
Nginx sungguhan**: jalankan daftar periksa §11 sebelum situs dibuka untuk publik.

---

## 1. Kebutuhan server

| Komponen | Syarat |
|---|---|
| Web server | **Apache 2.4 (disarankan)** dengan `mod_rewrite`, `mod_headers`, `mod_expires`, `mod_mime`, `mod_deflate`, dan `AllowOverride All`. Nginx bisa, tetapi lihat §6 |
| PHP | **8.1 atau lebih baru**; production memakai 8.3. Lebih baik lewat PHP-FPM |
| Database | MySQL 5.7+ atau MariaDB 10.3+ (production: MariaDB 11.8), charset `utf8mb4` |
| Composer | 2.x. Folder `vendor/` tidak ikut git, jadi wajib dijalankan di server |
| HTTPS | **Wajib.** Di production cookie sesi bernama `__Host-ci_session` dan ber-`Secure`, jadi login tidak akan jalan lewat `http://` |
| Akses shell | Untuk `php index.php migrate` dan tugas terjadwal (§9) |

### Ekstensi PHP

| Ekstensi | Dipakai untuk |
|---|---|
| `openssl` | Enkripsi data pribadi AES-256-GCM (NIK, koordinat, nama berkas), Web Push |
| `curl` | SIMPERUM, Sikaper, Sikumbang, Google, reCAPTCHA |
| `mbstring`, `json`, `ctype`, `iconv` | Pengolahan teks dan JSON |
| `fileinfo` | Memeriksa jenis berkas unggahan dari isinya, bukan dari nama |
| `zip`, `zlib`, `xml`, `dom`, `simplexml`, `xmlreader`, `xmlwriter`, `libxml` | Impor/ekspor Excel (PhpSpreadsheet) |
| `gd` | PhpSpreadsheet dan FPDF (sertifikat KKN) |
| `sodium` | Opsional: menghapus kunci dari memori sesudah dipakai |

Cara memastikan, sesudah `composer install` di server:

```bash
composer check-platform-reqs --no-dev
```

### Setelan `php.ini`

```ini
upload_max_filesize = 16M   ; pemindai unggahan menolak berkas > 16 MB
post_max_size       = 20M
memory_limit        = 256M  ; impor Excel
max_execution_time  = 60
expose_php          = Off
```

Batas per berkas di aplikasi 5 MB (foto bukti, dokumen). Bawaan PHP (2 MB) membuat
unggahan 2 sampai 5 MB gagal dengan pesan yang tidak menjelaskan sebabnya (mis.
"Pilih berkas JPG/PNG terlebih dahulu"), karena PHP membuang berkasnya sebelum
aplikasi sempat melihatnya.

---

## 2. Tata letak folder

```
/home/klinik/
├── .env                  ← izin 600, DI LUAR DocumentRoot (satu tingkat di atas app/)
├── app/                  ← DocumentRoot = isi repo (berisi index.php)
│   ├── index.php
│   ├── application/
│   ├── assets/
│   └── ...
├── private_uploads/      ← PRIVATE_UPLOADS_PATH, izin 700, DI LUAR DocumentRoot
└── backup_klinik/        ← dump DB, izin 700, DI LUAR DocumentRoot
```

- **DocumentRoot menunjuk folder repo itu sendiri** (tata letak CodeIgniter 3).
  `application/`, `system/`, `docs/`, `vendor/` memang ada di dalamnya; yang
  menahan semuanya dari publik adalah aturan `.htaccess` (§5) atau padanannya di
  Nginx (§6).
- **`.env` di luar DocumentRoot.** `index.php` mencari `.env` satu tingkat di atas
  folder aplikasi lebih dulu (`application/helpers/env_berkas_helper.php`); bila
  ada, `.env` di dalam folder aplikasi **tidak dibaca sama sekali**. Dengan begitu
  kunci enkripsi tidak bisa terunduh walau aturan web server hilang (pernah terjadi
  26 Jul 2026: `GET /.env` membalas 200). Syaratnya, folder induk itu sendiri
  **tidak tersaji** lewat web. Kalau aplikasi dipasang di subfolder DocumentRoot
  (mis. `/var/www/html/klinik/`), induknya justru tersaji: biarkan `.env` di akar
  aplikasi dan andalkan aturan §5/§6, seperti di XAMPP lokal.
- **`private_uploads` WAJIB di luar DocumentRoot.** Isinya foto rumah, KTP, dokumen
  SRP2, lampiran aduan. Berkas di sana hanya dibuka lewat controller yang memeriksa
  pemiliknya. Foto disimpan tanpa metadata (EXIF/GPS dibuang) dengan nama acak, tetapi
  **isinya tidak dienkripsi**, jadi izin folder adalah pelindung terakhirnya.
- **Jangan menaruh dump DB di DocumentRoot.** `.htaccess` menolak `*.sql`, tetapi
  lebih aman berkasnya memang tidak ada di sana (pernah bocor 26 Agt 2026).

### Folder yang harus bisa ditulis oleh pengguna PHP

| Folder | Isi |
|---|---|
| `application/cache/` | Cache respons layanan luar, kode OTP uji, penanda retensi |
| `application/logs/` | Log aplikasi (pesan sensitif dienkripsi) |
| `assets/img/hero/` | Gambar beranda (admin) |
| `assets/img/program/unggahan/`, `assets/img/pengembang/unggahan/` | Gambar katalog |
| `assets/dokumen/unggahan/` | PDF Bank Data |
| `assets/cache_foto/` | Cache foto perumahan dari Sikumbang |
| `PRIVATE_UPLOADS_PATH` | Semua berkas privat, plus buku kuota di `_pemilik/` |
| `session.save_path` PHP | Berkas sesi (`sess_driver = files`) |

Contoh izin (sesuaikan nama pengguna PHP-FPM/Apache, misalnya `www-data`). Folder
unggahan dan `assets/cache_foto/` di-gitignore, jadi belum ada sesudah `git clone`:

```bash
cd /home/klinik/app
mkdir -p assets/img/program/unggahan assets/img/pengembang/unggahan assets/dokumen/unggahan assets/cache_foto
chmod 600 /home/klinik/.env
chown -R klinik:www-data application/cache application/logs assets/img/hero \
  assets/img/program/unggahan assets/img/pengembang/unggahan assets/dokumen/unggahan assets/cache_foto
chmod -R ug+rwX application/cache application/logs assets/img/hero \
  assets/img/program/unggahan assets/img/pengembang/unggahan assets/dokumen/unggahan assets/cache_foto
mkdir -p /home/klinik/private_uploads && chmod 700 /home/klinik/private_uploads
```

Kalau PHP berjalan sebagai pengguna lain dari pemilik berkas, `private_uploads` harus
dimiliki pengguna PHP itu (izin `700` berarti hanya pemiliknya yang bisa masuk).

---

## 3. Variabel lingkungan (`.env`)

Salin `.env.example` menjadi `/home/klinik/.env` (satu tingkat di atas folder
aplikasi, lihat §2); setiap kunci di sana sudah diberi keterangan. Pastikan hanya
ada **satu** `.env`: bila ada juga salinan di folder aplikasi, salinan itu diam-diam
diabaikan dan mudah disangka berlaku.

- **Format:** `KEY=nilai`, satu per baris, **tanpa tanda kutip**. Pemuat di
  `index.php` tidak membuang kutip, jadi `DB_PASS="rahasia"` membuat sandinya
  `"rahasia"` lengkap dengan kutipnya.
- **`CI_ENV` tidak dibaca dari `.env`.** `index.php` menentukan environment
  sebelum `.env` dimuat. Di server biarkan tidak disetel: kosong berarti
  `production` (fail-closed). Jangan pernah menyetel `development` di server
  publik: tampilan galat terbuka dan cookie kehilangan `Secure`.

Kunci yang paling menentukan:

| Kunci | Catatan |
|---|---|
| `SITE_URL` | `https://domain/` dengan garis miring di akhir. Salah isi = CSS tidak muncul dan tautan rusak |
| `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` | Pengguna DB khusus aplikasi, bukan `root` |
| `DB_SSL`, `DB_SSL_HOSTNAME` | Isi bila server DB di mesin lain. Lihat `KEAMANAN_KOMUNIKASI.md` |
| `KPKP_DATA_KEY`, `KPKP_DATA_PEPPER` | Instalasi baru: buat baru (perintahnya ada di `.env.example`). **Pindah server: salin persis dari server lama** (§7) |
| `PRIVATE_UPLOADS_PATH` | Jalur absolut di luar DocumentRoot |
| `GOOGLE_REDIRECT_URI` | `SITE_URL` + `Auth/google_callback`, dan harus terdaftar di Google Cloud Console |
| `SMTP_*` | Production tanpa SMTP = pendaftaran akun ditolak (kode OTP tidak bisa dikirim). Uji kirim sebelum dibuka |
| `SIMPERUM_MODE` | `simulation` sampai UAT dinas selesai; `api` sesudahnya |

---

## 4. Pemasangan baru (database kosong)

```bash
# 1. Kode (repo publik; rahasia hanya di .env, yang tidak pernah ikut git)
cd /home/klinik
git clone https://github.com/faisalekasyahputra/klinik_new.git app
cd app && git checkout main

# 2. Pustaka PHP
composer install --no-dev --optimize-autoloader
composer check-platform-reqs --no-dev

# 3. Konfigurasi (.env di luar folder aplikasi, lihat §2)
cp .env.example ../.env && chmod 600 ../.env
nano ../.env       # isi sesuai §3

# 4. Database: buat DB utf8mb4 dan penggunanya, lalu skema awal + migrasi
mysql -u root -p -e "CREATE DATABASE klinikpkp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER 'klinikpkp'@'localhost' IDENTIFIED BY '<sandi>';
  GRANT ALL PRIVILEGES ON klinikpkp.* TO 'klinikpkp'@'localhost';"
mysql -u klinikpkp -p klinikpkp < docs/engineering/schema_klinikpkp.sql
php index.php migrate
php index.php migrate status
```

`schema_klinikpkp.sql` hanya snapshot lama. **Migrasi wajib dijalankan**; sumber
kebenaran skema adalah `application/migrations/`.

**Superadmin pertama.** Skema dan migrasi tidak membuat akun admin. Daftarkan akun
lewat situs (butuh SMTP atau Google Login yang sudah jalan), selesaikan onboarding,
lalu naikkan perannya:

```sql
UPDATE usr_akun SET peran = 'admin' WHERE email = 'admin@dinas.go.id';
```

Akun staf berikutnya (admin kabupaten/kota, admin bidang) dibuat superadmin ini lewat
menu **Akses Staf** (`Admin_Users`).

Lanjutkan ke konfigurasi web server (§5 atau §6), sertifikat HTTPS (mis. Let's
Encrypt lewat `certbot`; `.well-known/` sudah dikecualikan dari semua penolakan),
tugas terjadwal (§9), lalu daftar periksa (§11).

---

## 5. Apache (disarankan)

Semua aturan keamanan dan front controller sudah ada di `.htaccess` repo. Yang
diperlukan di sisi server hanya mengizinkannya berlaku:

```apache
<VirtualHost *:80>
    ServerName klinikpkp.contoh.go.id
    Redirect permanent / https://klinikpkp.contoh.go.id/
</VirtualHost>

<VirtualHost *:443>
    ServerName klinikpkp.contoh.go.id
    DocumentRoot /home/klinik/app

    <Directory /home/klinik/app>
        AllowOverride All
        Options -Indexes +SymLinksIfOwnerMatch
        Require all granted
    </Directory>

    SSLEngine on
    SSLCertificateFile    /etc/letsencrypt/live/klinikpkp.contoh.go.id/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/klinikpkp.contoh.go.id/privkey.pem
</VirtualHost>

# Di konfigurasi server utama (tidak bisa lewat .htaccess):
TraceEnable off
ServerTokens Prod
ServerSignature Off
```

```bash
a2enmod rewrite headers expires mime deflate ssl
```

Yang dilakukan `.htaccess` (jangan dilonggarkan; alasan tiap aturan ada di komentarnya):

1. Dotfile (`.env`, `.git`, `.htaccess`) ditolak 403, kecuali `.well-known/`.
2. Metode selain `GET/HEAD/POST/OPTIONS` dijawab 405.
3. `docs/`, `dev-scripts/`, `tests/`, `vendor/`, `*.md`, `composer.json/lock` ditolak.
4. Log (`error_log`, `*.log`) dan dump (`*.sql`, `*.dump`, `*.bak`, `.gz`) ditolak.
5. Daftar isi direktori di bawah `assets/` ditolak.
6. **Daftar izin:** berkas atau direktori nyata yang disajikan hanya `index.php`,
   `push-sw.js`, `manifest.webmanifest`, `assets/`, dan `.well-known/`. Selebihnya 403.
7. Alamat lain diteruskan ke `index.php` (URL tanpa `index.php`).
8. Header keamanan (nosniff, `X-Frame-Options`, `Referrer-Policy`, HSTS) dan cache
   untuk berkas statis. Respons PHP memasang header keamanannya sendiri.

---

## 6. Nginx

**Nginx tidak membaca `.htaccess`.** Tanpa terjemahan di bawah, `/.env`, `/docs/`,
`/vendor/`, dan log tersaji ke publik. Konfigurasi ini belum diuji di server
sungguhan; anggap sebagai titik awal dan buktikan dengan §11.

Pendekatannya lebih ketat dari Apache: selain `assets/`, `push-sw.js`,
`manifest.webmanifest`, dan `.well-known/`, **tidak ada berkas yang disajikan
langsung**. Semua alamat lain diteruskan ke `index.php`, sehingga `application/`,
`system/`, `docs/`, `vendor/`, `composer.json`, dan sejenisnya tidak pernah keluar
(dijawab 404 oleh aplikasi).

`/etc/nginx/snippets/klinik-header-statis.conf`:

```nginx
add_header X-Content-Type-Options "nosniff" always;
add_header X-Frame-Options "DENY" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
```

Header ini sengaja **hanya** untuk berkas statis. Respons PHP sudah memasangnya
sendiri; memasangnya juga di tingkat `server` membuat header ganda
(`X-Frame-Options: DENY, DENY`) yang bisa diabaikan peramban.

`/etc/nginx/sites-available/klinikpkp`:

```nginx
server {
    listen 80;
    server_name klinikpkp.contoh.go.id;
    location ^~ /.well-known/acme-challenge/ { root /home/klinik/app; }
    location / { return 301 https://$host$request_uri; }
}

server {
    listen 443 ssl http2;
    server_name klinikpkp.contoh.go.id;
    root /home/klinik/app;

    ssl_certificate     /etc/letsencrypt/live/klinikpkp.contoh.go.id/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/klinikpkp.contoh.go.id/privkey.pem;

    server_tokens off;
    client_max_body_size 20m;        # bawaan Nginx 1 MB: unggahan foto 5 MB gagal 413
    charset utf-8;
    charset_types text/css application/javascript application/manifest+json;
    gzip on;
    gzip_types text/css application/javascript application/json image/svg+xml font/woff2;

    # .htaccess butir 2: hanya GET/HEAD/POST/OPTIONS
    if ($request_method !~ ^(GET|HEAD|POST|OPTIONS)$) { return 405; }

    # Perpanjangan sertifikat. ^~ supaya aturan dotfile di bawah tidak berlaku di sini.
    location ^~ /.well-known/ { try_files $uri =404; }

    # .htaccess butir 1 dan 4. Lokasi regex dicek sebelum prefix biasa (/assets/).
    location ~ /\.                                   { return 403; }
    location ~* (^|/)(error_log|php_errorlog|[^/]+\.log)$ { return 403; }
    location ~* \.(sql|dump|bak)(\.gz)?$             { return 403; }
    location ~ \.php$                                { return 403; }   # PHP selain front controller

    # .htaccess butir 5, 6, 8: aset publik, tanpa daftar isi direktori
    location /assets/ {
        try_files $uri =404;
        expires 30d;
        include snippets/klinik-header-statis.conf;
    }
    location = /push-sw.js {
        try_files $uri =404;
        include snippets/klinik-header-statis.conf;
    }
    location = /manifest.webmanifest {
        default_type application/manifest+json;
        try_files $uri =404;
        include snippets/klinik-header-statis.conf;
    }

    # Front controller. Lokasi persis menang atas regex \.php$ di atas.
    location = /index.php {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root/index.php;
        fastcgi_param HTTPS on;          # header HSTS dari PHP hanya dikirim bila ini 'on'
        fastcgi_param CI_ENV production;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }

    # .htaccess butir 6 dan 7: semua alamat lain ke index.php, tidak ada yang disajikan
    # langsung. CodeIgniter membaca rute dari REQUEST_URI asli, query string ikut.
    location / {
        rewrite ^ /index.php last;
    }
}
```

---

## 7. Memindahkan situs yang sudah berjalan

Urutan yang aman: hentikan penulisan di server lama, salin, uji di server baru,
baru alihkan DNS.

1. **Turunkan TTL DNS** ke 300 detik sehari sebelumnya, supaya pengalihan cepat.
2. **Bekukan penulisan** di server lama (mode perawatan atau di luar jam layanan),
   supaya tidak ada data yang tertinggal di server lama sesudah disalin.
3. **Salin `.env` dari server lama** ke `/home/klinik/.env` (di luar folder
   aplikasi), lalu ubah hanya `SITE_URL`, `DB_*`, `PRIVATE_UPLOADS_PATH`, dan
   `GOOGLE_REDIRECT_URI`. Di Hostinger berkasnya bisa berada di luar
   `public_html` atau masih di akarnya; ambil yang benar-benar dipakai (yang di
   luar menang bila keduanya ada).
   - `KPKP_DATA_KEY`, `KPKP_DATA_PEPPER` (dan `KPKP_DATA_KEYS` /
     `KPKP_ACTIVE_KEY_ID` bila dipakai) **harus sama persis**. Kunci berbeda = NIK,
     koordinat, dan nama berkas terenkripsi tidak bisa dibaca lagi, tanpa jalan
     pulih. Simpan salinan kunci di tempat terpisah dari backup DB.
   - Kunci `WEB_PUSH_VAPID_*` juga dipertahankan; kunci baru membuat semua
     langganan notifikasi admin harus didaftar ulang.
4. **Database:**

   ```bash
   # di server lama
   set -o pipefail
   mysqldump --single-transaction -h <host> -u <user> -p <db> | gzip > ~/backup_klinik/pindah_$(date +%Y%m%d_%H%M).sql.gz
   gzip -t ~/backup_klinik/pindah_*.sql.gz && echo OK
   # di server baru
   gunzip < pindah_<waktu>.sql.gz | mysql -u klinikpkp -p klinikpkp
   php index.php migrate status
   ```

   Bila server baru memakai MariaDB lebih lama dari production (11.8), dua
   penyesuaian yang pernah dibutuhkan (lihat `AGENTS.md`): buang baris
   `/*M!999999 sandbox mode */` di awal dump, dan ganti collation
   `utf8mb4_uca1400_ai_ci` menjadi `utf8mb4_unicode_ci`.
5. **Berkas unggahan**, dengan struktur foldernya utuh:
   - seluruh `private_uploads/` ke `PRIVATE_UPLOADS_PATH` baru
   - `assets/img/hero/`, `assets/img/program/unggahan/`,
     `assets/img/pengembang/unggahan/`, `assets/dokumen/unggahan/`

   ```bash
   rsync -a lama:/path/private_uploads/ /home/klinik/private_uploads/
   ```
6. **Layanan luar yang terikat domain:** daftarkan URL callback baru di Google
   Cloud Console (OAuth), tambahkan domain baru di reCAPTCHA, uji kirim email OTP.
7. Jalankan daftar periksa §11 lewat alamat server baru (mis. dengan entri
   `/etc/hosts` sementara), baru alihkan DNS.
8. Server lama: matikan aplikasinya (403 untuk semua), tetapi **simpan datanya**
   sampai server baru terbukti stabil. Pastikan `.env` lama tidak lagi menunjuk DB
   production (pelajaran 2 Okt 2026: situs lama yang hidup lagi masih memegang
   akses ke DB production).

---

## 8. Rilis berikutnya

Di luar Hostinger tidak ada deploy otomatis; rilis dijalankan sendiri:

```bash
cd /home/klinik/app
set -o pipefail
mysqldump --single-transaction -u klinikpkp -p klinikpkp | gzip > ~/backup_klinik/pre_$(git rev-parse --short HEAD)_$(date +%Y%m%d_%H%M).sql.gz
git fetch && git status --porcelain     # harus kosong; berkas lokal di server = tanda bahaya
git checkout main && git pull --ff-only
composer install --no-dev --optimize-autoloader
php index.php migrate
php index.php migrate status
```

- **Migrasi gagal di tengah: berhenti, jangan ulangi `migrate`.** Pulihkan dari
  backup. `php index.php migrate <versi>` bukan rollback (lihat
  `RUNBOOK_RILIS_033_035.md` bagian Rollback).
- `git pull` menghapus berkas yang dihapus dari repo (berbeda dengan deploy
  Hostinger yang meninggalkannya).
- CSS Tailwind statis (`assets/css/tailwind-*.css`) sudah ikut repo; tidak ada
  langkah build di server.

---

## 9. Tugas terjadwal

| Tugas | Perintah | Jadwal |
|---|---|---|
| Penyegaran data SIMPERUM | `php index.php simperum_segarkan index 100` | Mingguan |
| Penyapu retensi data | `php index.php retensi jalankan` | Opsional, harian |

```cron
# crontab -e (pengguna yang sama dengan pemilik aplikasi)
30 2 * * 0  cd /home/klinik/app && php index.php simperum_segarkan index 100 >> /home/klinik/cron_simperum.log 2>&1
15 3 * * *  cd /home/klinik/app && php index.php retensi jalankan >> /home/klinik/cron_retensi.log 2>&1
```

Penyapu retensi juga berjalan sendiri sekali per 24 jam, dipicu kunjungan web
pertama sesudah penandanya basi (dibuat begitu karena Hostinger tidak punya cron
di SSH). Cron di atas hanya menjamin ia tetap jalan saat situs sepi. Keduanya
hanya bisa dijalankan dari CLI. Taruh log cron di luar DocumentRoot.

---

## 10. Reverse proxy, CDN, dan load balancer

- **Isi `$config['proxy_ips']`** di `application/config/config.php` dengan alamat
  proxy (Cloudflare, load balancer dinas, dst.). Tanpa itu semua pengunjung
  terlihat ber-IP sama: pembatas laju dan anti-otomatisasi menahan semua orang
  sekaligus, dan jejak audit mencatat IP yang salah.
- **Content-Security-Policy.** Aplikasi mengirim header CSP sendiri plus tag meta
  cadangan, karena CDN Hostinger (hcdn) menimpa header itu. Bila CDN baru juga
  mengubah header, periksa CSP yang benar-benar sampai ke peramban.
- **Aplikasi harus tahu permintaannya HTTPS.** Header keamanan PHP
  (`kirim_header_keamanan()`) hanya membaca `$_SERVER['HTTPS'] === 'on'`, bukan
  `X-Forwarded-Proto`. Bila TLS diakhiri di proxy dan server di belakangnya
  menerima HTTP biasa, HSTS dan `upgrade-insecure-requests` tidak dikirim. Setel
  di server belakang proxy: Nginx `fastcgi_param HTTPS on;`, atau Apache
  `SetEnvIf X-Forwarded-Proto "^https$" HTTPS=on` (hanya bila proxy itu satu-satunya
  jalan masuk, karena header ini bisa dipalsukan klien yang langsung terhubung).
- **ClamAV opsional.** Isi `CLAMD_ADDRESS` bila server punya `clamd`; tanpa itu
  hanya pemindai bawaan aplikasi yang berjalan (lihat `UNGGAHAN_BERKAS.md`).

---

## 11. Daftar periksa sesudah tayang

Ganti `D` dengan domainnya. Semua baris "ditolak" harus 403 atau 404, **bukan 200**.

```bash
D=https://klinikpkp.contoh.go.id
for p in .env .git/config docs/ docs/engineering/SETUP_DATABASE.md AGENTS.md vendor/autoload.php composer.json \
         application/config/database.php system/ README.md error_log backup.sql assets/img/ \
         private_uploads/; do
  printf '%-36s %s\n' "$p" "$(curl -s -o /dev/null -w '%{http_code}' "$D/$p")"
done
curl -s -o /dev/null -w 'TRACE %{http_code}\n' -X TRACE "$D/"      # 405
curl -s -o /dev/null -w 'beranda %{http_code}\n' "$D/"              # 200
curl -s -o /dev/null -w 'akun %{http_code}\n' "$D/akun"             # 307 ke login
curl -sI "$D/" | grep -iE 'strict-transport|x-frame-options|content-security|x-content-type'
curl -sI "$D/assets/css/design-system.css" | grep -iE 'strict-transport|x-frame-options|nosniff|cache-control'
```

Lalu uji dengan tangan di peramban:

- [ ] Login dengan akun biasa dan dengan Google; sesi bertahan saat pindah halaman.
- [ ] Daftar akun baru: kode OTP sampai ke email.
- [ ] Warga: unggah satu foto bukti, buka lagi lewat "Lihat berkas".
- [ ] Berkas itu ada di `PRIVATE_UPLOADS_PATH`, bukan di bawah DocumentRoot.
- [ ] Hanya ada satu `.env`, di luar DocumentRoot (`ls -la /home/klinik/.env
      /home/klinik/app/.env`: yang kedua tidak ada).
- [ ] Admin: buka satu pengajuan berlampiran; berkas tampil.
- [ ] `php index.php migrate status` di server menunjukkan versi terbaru, dan bila
      `DB_SSL` diisi, koneksi terenkripsi.
- [ ] Data lama (bila pindahan): NIK di detail antrean admin terbaca, bukan galat
      dekripsi. Ini bukti kunci enkripsi sudah benar.
