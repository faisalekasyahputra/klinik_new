# Respons aplikasi dan konten yang aman - pengukuran, perbaikan, dan penjaga

Menjawab satu butir form keamanan (Standar Teknis Keamanan Aplikasi Web, parameter 13 "Konfigurasi"):

- **13.5** Menggunakan respons aplikasi dan konten yang aman.

Maksudnya: setiap respons - halaman, galat, JSON, berkas statis, unduhan berkas privat - membawa header yang membuat peramban memperlakukannya dengan aman, jenis kontennya benar (peramban tidak menebak), dan tidak membocorkan teknologi atau data.

## 1. Pengukuran sebelum perbaikan (situs live, 21 Sep 2026)

| Jenis respons | Yang kurang |
|---|---|
| Halaman dari controller | Sudah lengkap (CSP, Permissions-Policy, X-Frame-Options, nosniff, Referrer-Policy, HSTS) |
| **404 dari router dan halaman galat CodeIgniter** (dibuat sebelum controller berjalan) | **Tanpa nosniff, X-Frame-Options, Referrer-Policy, HSTS**: MY_Controller tak sempat berjalan |
| **Berkas statis** (CSS, JS, gambar, font, manifest) | Tanpa nosniff, X-Frame-Options, Referrer-Policy; jenis konten JS/CSS bergantung bawaan server (mis. `application/x-javascript` tanpa charset) |
| **JSON dari controller** (`Auth`, `Umum`, 11 titik) | Dikirim sebagai `text/html`: peramban dapat menafsirkannya sebagai HTML |
| **Berkas privat** (`serve_private_file`) | Jenis konten dari argumen `$mime` pemanggil/DB; nama unduhan tersimpan; tanpa `Cache-Control` ketat |
| Isolasi lintas asal | Tanpa Cross-Origin-Opener-Policy dan Cross-Origin-Resource-Policy |

## 2. Yang diperbaiki

| Temuan | Perbaikan |
|---|---|
| Satu sumber header | `kirim_header_keamanan()` di `helpers/content_security_helper.php` memasang **seluruh** header keamanan; dipakai `MY_Controller` dan `MY_Exceptions` |
| 404/galat tanpa header | Baru: `application/core/MY_Exceptions.php` memasang header sebelum `show_404`, `show_error`, `show_exception` |
| Berkas statis | `.htaccess`: `AddType`/`AddCharset` benar (`text/javascript`, `text/css`, `application/manifest+json`, `font/woff2`, UTF-8), plus nosniff, X-Frame-Options, Referrer-Policy, HSTS **hanya untuk berkas statis** lewat `FilesMatch` |
| JSON | 11 titik memakai `Content-Type: application/json; charset=utf-8`; 3 titik di `MY_Controller` sudah benar |
| Berkas privat | Jenis konten dari **ekstensi** daftar-izin (pdf/jpg/png; lainnya dipaksa unduhan biner), nama unduhan netral, `Cache-Control: private, no-store, max-age=0`, `Content-Length`, CSP `default-src 'none'; sandbox` untuk gambar |
| Isolasi lintas asal | `Cross-Origin-Opener-Policy: same-origin-allow-popups` (bukan `same-origin`, yang memutus popup masuk Google) dan `Cross-Origin-Resource-Policy: same-origin` |

Kenapa header berkas statis dipasang dari `.htaccess` dan bukan global: `Header always set` di Apache **menggandakan** header yang sudah dipasang PHP (`X-Frame-Options: DENY, DENY` adalah nilai tak sah yang diabaikan peramban). Karena itu ia dibatasi ke berkas statis, dan respons PHP (termasuk galat) memasangnya sendiri.

## 3. Bukti (Apache lokal, 21 Sep 2026)

| Permintaan | Hasil |
|---|---|
| `/assets/css/fonts.css` | 200, `text/css; charset=utf-8`, nosniff, X-Frame-Options DENY, Referrer-Policy, HSTS |
| `/assets/fonts/*.woff2` | 200, `font/woff2`, header yang sama |
| `/assets/js/notifications.js` | 200, `text/javascript; charset=utf-8`, header yang sama |
| `/manifest.webmanifest` | 200, `application/manifest+json; charset=utf-8`, header yang sama |
| `/alamat-yang-tidak-ada` (404 router) | 404, X-Frame-Options, nosniff, Referrer-Policy, COOP, CORP (sebelumnya nol) |
| Beranda | 200, seluruh header, tanpa `X-Powered-By`, nol rujukan ke Google Fonts |
| `/license.txt`, `/composer.json` | 403 (daftar izin), header keamanan ikut terpasang |

## 4. Penjaga

`tests/response_safety_test.php` (offline) menggagalkan bila: `kirim_header_keamanan()` kehilangan salah satu dari sembilan header wajib, COOP menjadi `same-origin`, HSTS dikirim di HTTP; ada header keamanan dipasang di luar fungsi itu; `MY_Exceptions` tidak memasang header sebelum `parent::`; `.htaccess` kehilangan `AddType`/`AddCharset` atau memasang header keamanan di luar `FilesMatch` (header ganda); ada `echo json_encode` tanpa `Content-Type: application/json`; `serve_private_file` mengambil jenis konten dari `$mime`, kehilangan `no-store`, `Content-Length`, sandbox, atau `basename`.
**Uji mutasi:** `show_404` tanpa header, JSON tanpa content-type, `AddType` JS dihapus, header ganda di luar `FilesMatch`, CORP dihapus, dan berkas privat dibuat dapat di-cache masing-masing menggagalkan tes.

## 5. Batas yang diakui

- **Header CSP dari aplikasi ditimpa platform hosting di production** (lapisan tepi mengganti dengan `upgrade-insecure-requests`, diverifikasi 21 Sep 2026); yang benar-benar menegakkan CSP adalah `<meta http-equiv>` di setiap halaman lengkap. Header lain yang dipasang aplikasi lolos.
- **Respons galat buatan server Apache** (403 dari `.htaccess`, 405) berbadan halaman bawaan server dan, untuk yang menolak berkas `.php`, tidak membawa header keamanan aplikasi. Isinya tidak memuat data.
- **CSP masih `'unsafe-inline'` dan `'unsafe-eval'`** (Alpine.js; lihat [`KODE_DINAMIS.md`](KODE_DINAMIS.md)). `X-XSS-Protection` dikirim untuk peramban lama; peramban modern mengabaikannya.
- Tes memeriksa **sumber** (kode dan konfigurasi), bukan respons HTTP nyata; buktinya di bagian 3 diambil dengan `curl` ke server lokal dan tidak berjalan otomatis. Di production tiap perubahan header sebaiknya diukur ulang dengan `curl -I`.
- Tidak ada `Cross-Origin-Embedder-Policy` (akan memutus tile peta dan aset CDN). CORP `same-origin` berlaku pada respons PHP; aset statis publik memang dimaksudkan dapat dipakai lintas asal.
- Unduhan spreadsheet/ekspor (PhpSpreadsheet) diatur `data_lifecycle` (butir 7.3) dan tidak diubah di sini.
