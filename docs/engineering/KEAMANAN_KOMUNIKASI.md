# Keamanan Komunikasi - kebijakan, alat uji, dan prosedur berkala

Menjawab dua butir form keamanan (Standar Teknis Keamanan Aplikasi Web, parameter 8):

- **8.2** Mengatur koneksi masuk dan keluar yang aman dan terenkripsi dari sisi pengguna.
- **8.3** Mengatur jenis algoritma yang digunakan dan alat pengujiannya.

Dokumen ini tidak memuat alamat IP, nama host, atau jalur server (repo ini publik, lihat AGENTS.md §0).
Situs ditulis `https://<situs-production>`; nilai sebenarnya dipasang saat menjalankan alat.

## 1. Peta koneksi dan siapa yang mengaturnya

| Koneksi | Yang mengatur | Mekanisme | Dibuktikan oleh |
|---|---|---|---|
| Peramban ke situs (masuk) | Penyedia hosting | Versi TLS dan cipher yang ditawarkan; pengalihan HTTP ke HTTPS | `uji_tls_situs.php` bagian 1-4 |
| Peramban ke situs (masuk) | Aplikasi | HSTS (`MY_Controller`), cookie `Secure; HttpOnly; SameSite` | `uji_tls_situs.php` bagian 5; `tests/transport_security_test.php` |
| Peramban ke situs (masuk) | Penyedia hosting | CSP `upgrade-insecure-requests` (dikirim platform; terlihat bahkan pada berkas statis, bukan dari kode aplikasi. Koreksi 21 Sep 2026: versi awal dokumen ini keliru menulisnya sebagai milik aplikasi) | `uji_tls_situs.php` bagian 5 |
| Aplikasi ke layanan luar (keluar) | Aplikasi | `helpers/transport_helper.php`: sertifikat dan nama host diverifikasi, TLS 1.2 ke atas, hanya HTTPS | `tests/transport_security_test.php` (pemindaian kode) |
| Aplikasi ke server database (keluar) | Aplikasi | `config/database.php` + env `DB_SSL`; cipher dari kebijakan | `php index.php migrate status` (dibaca dari sesi koneksi) |

Pembagian ini penting: versi TLS dan cipher pada koneksi MASUK ada di sisi penyedia hosting dan tidak bisa
dikonfigurasi dari kode. Yang bisa dilakukan aplikasi adalah MEMBUKTIKANNYA berkala dan melapor bila berubah.

## 2. Kebijakan algoritma (sumber tunggal: `application/config/transport_security.php`)

| Bidang | Ketetapan |
|---|---|
| Protokol diizinkan | TLS 1.2 dan TLS 1.3 |
| Protokol terlarang | SSLv3, TLS 1.0, TLS 1.1 |
| Cipher koneksi database (TLS 1.2) | `ECDHE+AESGCM:ECDHE+CHACHA20` tanpa NULL, anonim, MD5, RC4, 3DES, DES, EXPORT, PSK, SRP. TLS 1.3 memakai suite AEAD bawaan OpenSSL |
| Cipher yang harus ditolak server | RC4, 3DES, DES, EXPORT, NULL, seluruh CBC-SHA/SHA256 (daftar lengkap di berkas kebijakan) |
| HSTS | `max-age` minimum 180 hari (nilai terkirim: 365 hari, `includeSubDomains`) |
| Kata sandi | bcrypt (`password_hash`) |
| Data pribadi | AES-256-GCM, IV/nonce acak, tag autentikasi |
| Turunan kunci | HKDF-SHA256 (kunci log dipisah dari kunci data) |
| Hash pencarian NIK | HMAC-SHA256 dengan pepper |
| Token sesi | `random_bytes(32)`, disimpan sebagai SHA-256 |

Klaim tabel algoritma diperiksa oleh `tests/transport_security_test.php` terhadap kode, jadi kebijakan yang
tertulis tidak bisa menyimpang dari kode tanpa tes merah.

## 3. Alat uji

**a. Penjaga regresi kode** - offline, cepat, dijalankan tiap ada perubahan yang menyentuh koneksi keluar:

```
php tests/transport_security_test.php
```

Menggagalkan bila verifikasi TLS dimatikan lagi (`CURLOPT_SSL_VERIFY*`, `verify_peer`, `verify => false`),
ada `curl_init` atau `file_get_contents('https://...')` di luar kebijakan, ada URL `http://` pada kode, konfigurasi
database kembali ke tanpa enkripsi, atau algoritma yang dinyatakan kebijakan tidak lagi dipakai kode.
Dibuktikan bisa merah dengan pelanggaran sisipan (20 Sep 2026).

**b. Uji situs yang berjalan** - dari luar, ke situs production:

```
php docs/engineering/uji_tls_situs.php https://<situs-production>
```

Status per pemeriksaan: `LULUS`, `GAGAL`, `PERINGATAN`, `LEWAT`. Kode keluar 1 bila ada `GAGAL`.
`LEWAT` berarti mesin penguji tidak sanggup melakukan uji itu, dan TIDAK dihitung lulus. Sebuah uji lulus hanya
bila server memberi penolakan yang jelas (mis. alert `protocol version`).

**c. Status enkripsi database** - di server: `php index.php migrate status` (butuh PHP CLI yang sesuai dengan
dependensi, di hosting ini PHP 8.3). Baris `Koneksi DB terenkripsi` dibaca dari sesi koneksi yang sedang dipakai.

## 4. Prosedur berkala

| Kapan | Apa |
|---|---|
| Tiap perubahan kode yang menyentuh koneksi keluar, database, atau header respons | `php tests/transport_security_test.php` harus hijau sebelum push |
| Tiap kuartal (Jan, Apr, Jul, Okt) | `uji_tls_situs.php` ke situs production, dari lokal DAN dari dalam server (sudut pandang berbeda); catat hasilnya di §6 |
| Sertifikat mendekati 21 hari kedaluwarsa (alat memberi `PERINGATAN`) | Pastikan perpanjangan otomatis penyedia hosting berjalan; uji ulang sesudahnya |
| Sesudah penyedia hosting mengubah server, CDN, atau sertifikat | Uji ulang segera |
| Sesudah mengubah `DB_SSL`, `DB_HOST`, atau server database | `migrate status` di server harus menampilkan `TLSv1.2` atau lebih tinggi |

**Bila ada `GAGAL`:** jangan melonggarkan tes atau kebijakan supaya hijau. Untuk bagian yang diatur penyedia
hosting (versi TLS, cipher, pengalihan HTTP) buka tiket ke penyedia dengan keluaran alat sebagai bukti. Untuk
bagian aplikasi, perbaiki kodenya.

## 5. Mengaktifkan enkripsi koneksi database

Bawaan (env kosong) = tanpa TLS, supaya lingkungan lokal tanpa TLS tetap jalan. Di server yang mendukung TLS:

1. Tentukan nama host server database yang tercantum di sertifikatnya (bukan alamat IP; sertifikat dikeluarkan
   untuk nama). Reverse DNS alamat `DB_HOST` biasanya memberi nama itu.
2. Uji dulu tanpa mengubah aplikasi: buka koneksi `mysqli` dengan `MYSQLI_CLIENT_SSL` dan
   `MYSQLI_OPT_SSL_VERIFY_SERVER_CERT` terhadap nama itu; pastikan `Ssl_version` tidak kosong, dan bahwa
   koneksi yang sama terhadap alamat IP DITOLAK (bukti bahwa verifikasi nyata).
3. Tambahkan ke `.env` server: `DB_SSL=verify` dan `DB_SSL_HOSTNAME=<nama host>`.
4. `php index.php migrate status` harus menampilkan `Mode DB_SSL: verify` dan `Koneksi DB terenkripsi: TLSv1.3`.
5. **Batal:** hapus dua baris itu dari `.env`. Aplikasi langsung kembali ke tanpa TLS, tanpa deploy ulang.

Mode: `off` (bawaan) | `on` (terenkripsi, identitas server tidak diverifikasi) | `verify` (terenkripsi dan
sertifikat diverifikasi; wajib nama host, bukan IP). Nilai lain, termasuk salah ketik, jatuh ke `on`, bukan `off`.
Bila TLS diminta tetapi server database tidak mendukungnya, koneksi GAGAL; aplikasi tidak turun diam-diam ke
tanpa enkripsi (dibuktikan lokal terhadap MariaDB tanpa TLS, 20 Sep 2026).

## 6. Riwayat hasil uji

### 20 Sep 2026 - awal, sesudah rilis kode keamanan komunikasi

| Pemeriksaan | Hasil |
|---|---|
| Uji situs dari lokal (PHP 8.1, OpenSSL 1.1.1) | 15 lulus, 0 gagal, 0 peringatan, 1 dilewati (SSLv3) |
| Uji situs dari dalam server (PHP 8.3, OpenSSL 3.5) | 15 lulus, 0 gagal, 0 peringatan, 1 dilewati (SSLv3) |
| TLS 1.0 dan 1.1 | Ditolak server dengan alert `protocol version` |
| TLS 1.2 dan 1.3 | Diterima. Cipher TLS 1.2: `ECDHE-ECDSA-AES128-GCM-SHA256`; TLS 1.3: `TLS_AES_256_GCM_SHA384` |
| Cipher lemah | 10 dari 15 ditolak eksplisit (semua varian CBC-SHA/SHA256 dan NULL). RC4, 3DES, DES, EXPORT tidak dapat dicoba oleh klien mana pun yang tersedia, jadi TIDAK dihitung lulus |
| Sertifikat | Rantai sah, nama cocok, EC 256 bit, tanda tangan ecdsa-with-SHA384, sisa 77 hari |
| HTTP ke HTTPS | 301 ke HTTPS |
| Respons HTTPS | HSTS 365 hari `includeSubDomains`; 2 cookie semuanya `Secure` dan `HttpOnly`; CSP `upgrade-insecure-requests` |
| Jalur keluar dengan kebijakan TLS (dari server) | SIMPERUM, Sikaper, Ternak, Sikumbang, reCAPTCHA (cURL dan stream): TLS berhasil, sertifikat terverifikasi |
| Koneksi database sebelum | TIDAK terenkripsi (`Ssl_version` kosong). Server database mendukung TLS 1.3 |
| Koneksi database sesudah (`DB_SSL=verify`) | TLSv1.3 / `TLS_AES_256_GCM_SHA384`, sertifikat diverifikasi terhadap nama host; koneksi ke alamat IP yang sama DITOLAK (verifikasi nyata). Server database yang dituju lewat nama dan lewat IP dipastikan sama |
| `tests/transport_security_test.php` | 79 pemeriksaan hijau, lokal dan di server |
| Halaman live sesudah semua perubahan | Beranda, login, Nggolek Omah, pendataan, pengembang, dokumen: HTTP 200, tanpa teks galat |

## 7. Batas yang diakui

- **RC4, 3DES, DES, EXPORT** pada koneksi masuk tidak dapat diuji langsung (klien penguji modern tidak
  memilikinya). Bukti tidak langsung: server menolak seluruh CBC-SHA dan NULL, menolak TLS di bawah 1.2, dan
  hanya menegosiasikan cipher AEAD. Bila ada klien dengan OpenSSL lama, jalankan alat dari sana.
- **SSLv3** tidak dapat dipaksa dari PHP/OpenSSL yang tersedia (PHP diam-diam bernegosiasi otomatis); alat
  melaporkannya `LEWAT`. TLS 1.0 dan 1.1 yang ditolak menunjukkan protokol lama tidak ditawarkan.
- **Versi TLS dan cipher koneksi masuk** adalah konfigurasi penyedia hosting. Aplikasi tidak dapat mengubahnya,
  hanya membuktikannya; hasil ini bisa berubah tanpa pemberitahuan sehingga uji berkala §4 wajib dijalankan.
- **Klien Google API** (login Google) dibuat oleh pustaka `google/apiclient` dan memakai bawaan Guzzle/libcurl:
  sertifikat diverifikasi, tetapi TLS minimum tidak dipaksa 1.2 oleh aplikasi; versi minimumnya mengikuti
  bawaan libcurl/OpenSSL di server dan BELUM diuji. Mengganti klien HTTP pustaka itu berisiko merusak alur
  OAuth dan belum dilakukan.
- Verifikasi identitas server database bergantung pada nama host di `DB_SSL_HOSTNAME` tetap tercantum di
  sertifikat penyedia. Bila penyedia mengganti nama atau sertifikat, koneksi database gagal (bukan turun ke
  tanpa enkripsi); pemulihannya adalah §5 langkah 5 sambil menunggu penyedia.
