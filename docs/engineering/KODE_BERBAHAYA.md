# Pengendalian Kode Berbahaya - kebijakan, alat, prosedur, dan hasil

Menjawab empat butir form keamanan (Standar Teknis Keamanan Aplikasi Web, parameter 9):

- **9.1** Menggunakan analisis kode dalam kontrol kode berbahaya.
- **9.2** Memastikan kode sumber aplikasi dan pustaka tidak mengandung kode berbahaya dan fungsionalitas lain yang tidak diinginkan.
- **9.3** Mengatur izin terkait fitur atau sensor terkait privasi.
- **9.4** Mengatur pelindungan integritas.

(9.5, mekanisme fitur pembaruan, tidak berubah: pembaruan datang lewat git dan HTTPS.)
Dokumen ini tidak memuat alamat IP, nama host, atau jalur server (repo ini publik, lihat AGENTS.md §0).

## 1. Temuan awal (20-21 Sep 2026) yang membuat butir ini belum terpenuhi

Catatan lama di form menulis "dependency lock dan Git tersedia". Pemeriksaan langsung menunjukkan sebaliknya:

| Temuan | Dampak |
|---|---|
| `composer.lock` di-gitignore, sedangkan tiap deploy menjalankan `composer install` di clone baru | Versi pustaka DIPILIH ULANG di setiap push, tanpa review. 10 paket di production berbeda dari lock lokal, satu beda versi mayor |
| Deploy memakai `composer install` tanpa `--no-dev` | 29 paket dev (PHPUnit dkk., tidak dipakai kode mana pun) ikut terpasang di production |
| `vendor/` dapat dijangkau dari web | Berkas PHP pustaka dapat dipanggil langsung; daftar versi pustaka terbaca |
| Hampir semua halaman memuat skrip/CSS dari CDN pihak ketiga tanpa SRI | Bila satu CDN dibobol, skrip jahat berjalan di semua halaman, termasuk admin |
| CDN Tailwind tidak mengirim header CORS | SRI mustahil untuk aset yang dimuat di SETIAP halaman |
| Permissions-Policy hanya menolak 3 fitur; tidak ada CSP `script-src` | Skrip dari host mana pun dapat dimuat |
| Header CSP dari aplikasi DITIMPA platform hosting di production | Kebijakan lulus uji lokal tetapi tidak pernah sampai ke peramban (ditemukan sesudah rilis pertama, diperbaiki dengan tag meta) |

## 2. Yang dibangun

| Bagian | Isi | Butir |
|---|---|---|
| `docs/engineering/pindai_kode_berbahaya.php` | Analisis statis PHP (tokenisasi) dan JavaScript (berkas dan skrip inline di view): eksekusi dinamis, koneksi keluar (deteksi "telepon pulang"), operasi berkas berisiko dan unggahan, penyamaran (base64/heksadesimal/variabel-variabel), fungsi waktu (bom waktu, ketekunan di luar permintaan), tanda webshell. Daftar izin per berkas, masing-masing dengan alasan tertulis | 9.1 |
| `tests/malicious_code_test.php` | Penjaga regresi offline (223 pemeriksaan). Menguji pemindainya SENDIRI dengan contoh berbahaya sintetis, lalu memastikan kode nyata bersih di luar daftar izin, dan daftar izin tidak basi | 9.1, 9.2 |
| `docs/engineering/integritas_manifest.json` + mode `integritas`/`upstream` | Hash SHA-256 (akhir baris LF) 209 berkas pihak ketiga yang di-vendor: `system/` (CodeIgniter 3.1.13, 206 berkas), pdf.js 3.11.174 (2), Tailwind 3.4.17 (1). Mode `upstream` mengunduh rilis RESMI dan membandingkan | 9.2, 9.4 |
| `composer.json`/`composer.lock` | Lock kini dilacak git (dibangkitkan di PHP 8.3, platform deploy): 31 paket produksi, 0 dev. `require-dev` dan `scripts` (PHPUnit/vfsStream, tidak dipakai) dihapus. Mode `pustaka` memeriksa lock, terpasang, `composer validate`, dan `composer audit` | 9.2, 9.4 |
| `.htaccess` | `vendor/` (bersama `docs`, `dev-scripts`, `tests`) diblokir dari web | 9.4 |
| SRI pada view | sha384 + `crossorigin` untuk Alpine, jQuery, Swiper, AOS, Chart.js, Font Awesome, Phosphor (satu stylesheet, bukan loader dinamis), Leaflet. Tailwind dihosting sendiri (`assets/js/vendor/tailwind-3.4.17.js`, byte-identik dengan CDN) | 9.4 |
| `application/config/content_security.php` + `helpers/content_security_helper.php` | Sumber tunggal: CSP (`script-src` hanya `'self'` dan 5 host, `object-src 'none'`, `base-uri 'self'`), Permissions-Policy (12 fitur sensor/privasi ditolak, geolokasi hanya origin sendiri), daftar API sensor yang boleh dan berkas pemakainya, pengecualian SRI beserta alasan | 9.3, 9.4 |
| `csp_meta_tag()` di 14 halaman lengkap | CSP via tag meta karena platform hosting menimpa header CSP aplikasi. Header tetap dikirim untuk lingkungan yang tidak menimpanya. Tes memaksa meta ada di setiap halaman lengkap, sebelum `<script>` mana pun | 9.4 |
| `docs/engineering/uji_tls_situs.php` bagian 6 | Uji situs live: Permissions-Policy, CSP (dari header DAN meta), SRI halaman publik, dan jalur internal yang tidak boleh terjangkau (`vendor/`, `tests/`, `docs/`, `composer.*`, `.env`) | 9.3, 9.4 |

## 3. Cara menjalankan

```
php tests/malicious_code_test.php                                  # offline, wajib hijau sebelum push
php docs/engineering/pindai_kode_berbahaya.php kode                # analisis statis (bawaan)
php docs/engineering/pindai_kode_berbahaya.php integritas          # hash vs manifest (offline)
php docs/engineering/pindai_kode_berbahaya.php upstream            # bandingkan dengan rilis RESMI (internet)
php docs/engineering/pindai_kode_berbahaya.php pustaka [--ketat]   # lock, vendor/, validate, audit
php docs/engineering/pindai_kode_berbahaya.php deploy              # DI SERVER: pohon berkas vs git
php docs/engineering/pindai_kode_berbahaya.php semua --ketat       # kode + integritas + pustaka
php docs/engineering/uji_tls_situs.php https://<situs-production>  # dari luar
```

Di server, jalankan dengan PHP 8.3 (`php` bawaan hosting terlalu lama untuk dependensi Composer).
`--ketat` hanya bermakna di server: di mesin dev dengan PHP lebih lama, `vendor/` memang berbeda dari lock
(lock disusun untuk PHP 8.3 produksi), sehingga hanya menjadi peringatan.

## 4. Prosedur berkala

| Kapan | Apa |
|---|---|
| Tiap perubahan kode | `php tests/malicious_code_test.php` hijau. Temuan baru harus DITINJAU (bukan sekadar ditambahkan ke daftar izin): tulis alasannya |
| Tiap rilis, dan tiap kuartal | Di server: `pindai_kode_berbahaya.php semua --ketat` dan `deploy`; dari luar: `uji_tls_situs.php`. Catat di §6 |
| Menambah atau mengganti pustaka Composer | Jalankan `composer update` di lingkungan PHP 8.3 (bukan mesin dev PHP lama), periksa `composer audit`, commit `composer.lock`, lalu `pustaka --ketat` di server sesudah deploy |
| Menambah skrip/CSS dari CDN | Patok versi, hitung SRI sha384, pastikan CDN mengirim CORS (bila tidak, hosting sendiri), tambahkan host ke `csp_script_hosts` (review), catat pengecualian bila tidak bisa ber-SRI |
| Mengubah berkas vendor-an (`system/`, pdf.js, Tailwind) | Jalankan mode `upstream`; bila identik, `tulis-manifest`. `system/` TIDAK boleh diedit (manifest menggagalkan tes) |
| Menambah halaman HTML lengkap | Sisipkan `<?= csp_meta_tag() ?>` tepat sesudah `<meta charset>`; tes menggagalkan bila lupa |
| Sesudah mengubah izin fitur/sensor | Perbarui `permissions_policy` dan `sensor_api_allowlist`; API sensor baru harus punya berkas pemakai yang disetujui dan dipanggil dari aksi pengguna |

**Bila ada `GAGAL`:** jangan melonggarkan tes atau daftar izin supaya hijau. Tinjau kodenya; bila sah, tambahkan ke daftar izin
dengan alasan tertulis.

## 5. Hal yang perlu diketahui agen berikutnya

- **Deploy mempertahankan isi `vendor/` lama** ("Preserving paths matched by .gitignore"). Paket yang dibuang dari `composer.json`
  tidak ikut terhapus dari disk; mode `pustaka --ketat` melaporkannya. Pembersihan 21 Sep 2026: 31 entri sisa (29 paket dev, 2 proksi `bin/`) dipindahkan
  ke folder cadangan di luar DocumentRoot server (bukan dihapus). Cadangan itu boleh dihapus kapan saja; jangan menyalinnya keluar server.
- **Header `Content-Security-Policy` dari aplikasi ditimpa platform hosting** di production. `Permissions-Policy` dan header lain tidak. Jangan
  memindahkan CSP kembali ke header saja.
- **Tailwind Play CDN** menulis peringatan "should not be used in production" ke konsol; itu bawaan skripnya. Pengganti sejati (build CSS statis)
  belum dilakukan; `assets/css/tailwind*.css` hasil panen sudah ada sebagai jalur sebagian.
- Berkas log CodeIgniter (`application/logs/log-*.php`) memang berekstensi `.php`; sah bila hanya berisi SATU tag PHP penjaga.

## 6. Riwayat hasil

### 20-21 Sep 2026 - awal

| Pemeriksaan | Hasil |
|---|---|
| Uji situs live SEBELUM rilis | 5 gagal: Permissions-Policy tidak lengkap, tanpa CSP `script-src`, aset eksternal tanpa SRI, `vendor/` dapat dijangkau |
| Uji situs live SESUDAH rilis | 21 lulus, 0 gagal, 0 peringatan, 1 dilewati (SSLv3, lihat KEAMANAN_KOMUNIKASI.md) |
| Analisis statis kode sendiri | 13 sinyal di 12 berkas, seluruhnya ditinjau dan dicatat (8 berkas koneksi keluar, 3 unggahan, 1 fungsi waktu); 0 tersisa |
| Unggahan yang ditinjau | Gambar katalog (ekstensi dari MIME hasil sniffing, `getimagesize`, nama acak, metadata dibersihkan, hanya jpg/png) dan dokumen pengembang (ekstensi dan MIME harus cocok daftar putih, nama acak 128 bit) |
| Kode pihak ketiga vs rilis resmi | 209 dari 209 berkas identik: CodeIgniter 3.1.13 (206), pdf.js 3.11.174 (2), Tailwind 3.4.17 (1) |
| Pustaka Composer production vs instalasi bersih dari paket terkunci | 0 perbedaan versi (31 paket); 38.931 dari 38.931 berkas identik (SHA-256). Berkas ekstra hanya paket dev, proksi `bin/`, dan berkas metadata non-kode (`.github/*`, `.gitignore`, `.gitattributes`, `.repo-metadata.json`) |
| Simulasi perintah deploy (`composer install --prefer-dist`) dengan lock baru di server | Keluar 0; 31 paket = lock; hasil identik dengan instalasi awal |
| `composer audit` | Tidak ada advisori keamanan (lokal dan di server) |
| Sisa `vendor/` di server | 31 entri paket dev dipindahkan (14 MB); `pustaka --ketat` sesudahnya: bersih |
| `deploy` di server | Bersih: tidak ada berkas berubah atau asing terhadap commit; 23 log CodeIgniter sah |
| Peramban (lokal dan live) | Semua aset ber-SRI termuat; skrip dari host asing dan CDN tak terdaftar diblokir CSP; hash salah diblokir SRI; `<object>`/`<embed>` dan `<base>` asing diblokir; kamera/mikrofon/sensor ditolak; pdf.js beserta Web Worker, navigasi progresif admin, dan reCAPTCHA (kunci uji resmi Google) berjalan di bawah CSP |
| `tests/malicious_code_test.php` | 223 pemeriksaan hijau; dibuktikan bisa merah dengan sembilan sisipan pada pohon nyata (eval baru, koneksi keluar baru, CDN tanpa SRI, host asing, Tailwind CDN kembali, `getUserMedia`, `system/` diedit, `require-dev` kembali, `composer.lock` diabaikan lagi) |
| Halaman live sesudah semua perubahan | Beranda, login, Nggolek Omah, pendataan, pengembang, kemitraan, sebaran, statistika, Bank Data: HTTP 200 tanpa teks galat |

## 7. Batas yang diakui

- **Pemindai berbasis pola dan tokenisasi**, bukan analisis aliran data (taint) seperti SAST komersial; ia menjaga kelas masalah tertentu agar tidak
  masuk diam-diam dan tidak menggantikan review manusia. Deteksi bom waktu bersifat heuristik (perbandingan waktu dengan literal, fungsi ketekunan).
- **"Identik dengan rilis resmi" bukan bukti rilis itu bebas kode berbahaya.** Kepercayaan tetap bertumpu pada penerbit (CodeIgniter, Packagist, npm,
  GitHub). `composer audit` hanya mencakup kerentanan yang sudah diketahui.
- **Aset yang tidak bisa ber-SRI:** skrip reCAPTCHA (Google melarang SRI) dan CSS Google Fonts (dibangkitkan per User-Agent). Keduanya penyedia tepercaya dan
  tercatat sebagai pengecualian. SRI juga tidak mencakup berkas font yang dirujuk CSS.
- **CSP masih `'unsafe-inline'` dan `'unsafe-eval'`** (banyak skrip inline; Alpine.js versi standar memakai `new Function`). Yang ditegakkan adalah DARI MANA
  skrip boleh dimuat, `<object>` ditiadakan, dan `<base>` terkunci; bukan pencegahan injeksi skrip inline. Gambar, gaya, dan frame belum dibatasi.
- **Permissions-Policy hanya bisa lewat header** (tidak lewat meta) dan header itu sampai ke peramban di production (diverifikasi). Notifikasi peramban
  bukan fitur Permissions-Policy; izinnya diminta lewat klik tombol.
- **Commit Git tidak ditandatangani secara digital**, dan pipeline deploy (clone dan `composer install`) milik penyedia hosting.
- Manifest hanya menjaga berkas yang di-vendor; ia tidak mencakup `vendor/` Composer (dijaga lewat `composer.lock`, `pustaka --ketat`, dan audit).
