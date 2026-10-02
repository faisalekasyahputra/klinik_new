<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kebijakan keamanan komunikasi - satu-satunya tempat jenis algoritma dan
 * versi protokol transport ditetapkan. Dipakai oleh:
 *   - helpers/transport_helper.php (semua panggilan keluar aplikasi),
 *   - config/database.php (enkripsi koneksi ke server database),
 *   - docs/engineering/uji_tls_situs.php (alat uji berkala ke situs live),
 *   - tests/transport_security_test.php (penjaga regresi pada kode).
 * Prosedur uji dan riwayat hasilnya: docs/engineering/KEAMANAN_KOMUNIKASI.md.
 *
 * Yang DIATUR OLEH APLIKASI: versi TLS minimum dan verifikasi sertifikat pada
 * koneksi KELUAR (aplikasi ke layanan luar dan ke server database), daftar
 * cipher untuk koneksi database, dan header HSTS pada respons masuk.
 * Yang DIATUR OLEH PENYEDIA HOSTING (bukan aplikasi): versi TLS dan cipher
 * yang DITAWARKAN ke peramban pada koneksi MASUK, serta pengalihan HTTP ke
 * HTTPS. Bagian itu tidak bisa dikonfigurasi dari kode; ia DIBUKTIKAN dengan
 * alat uji di atas. Kalau hasil uji berubah, itu temuan untuk penyedia hosting.
 */

// Koneksi keluar: TLS 1.2 atau lebih baru (libcurl: CURL_SSLVERSION_TLSv1_2
// berarti "1.2 ke atas", bukan "tepat 1.2").
$config['transport_min_tls'] = '1.2';

// Protokol yang HARUS diterima dan yang HARUS ditolak server, dipakai alat uji.
$config['transport_allowed_tls']   = ['TLSv1.2', 'TLSv1.3'];
$config['transport_forbidden_tls'] = ['SSLv3', 'TLSv1.0', 'TLSv1.1'];

// Daftar cipher untuk koneksi ke server database (berlaku untuk TLS 1.2;
// TLS 1.3 memakai suite AEAD bawaan OpenSSL). Hanya pertukaran kunci ECDHE
// (forward secrecy) dengan enkripsi AEAD. Tanpa NULL/anonim/MD5/RC4/3DES/DES/
// EXPORT/PSK/SRP.
$config['transport_db_ciphers'] = 'ECDHE+AESGCM:ECDHE+CHACHA20:!aNULL:!eNULL:!MD5:!RC4:!3DES:!DES:!EXPORT:!PSK:!SRP';

// Cipher TLS 1.2 yang TIDAK BOLEH diterima server (nama OpenSSL). Dipakai
// alat uji: satu saja yang diterima berarti server masih menawarkan cipher lemah.
$config['transport_weak_ciphers'] = [
    'RC4-SHA', 'RC4-MD5', 'DES-CBC3-SHA', 'DES-CBC-SHA',
    'AES128-SHA', 'AES256-SHA', 'AES128-SHA256', 'AES256-SHA256',
    'ECDHE-RSA-AES128-SHA', 'ECDHE-RSA-AES256-SHA',
    'ECDHE-ECDSA-AES128-SHA', 'ECDHE-ECDSA-AES256-SHA',
    'NULL-SHA', 'NULL-MD5', 'EXP-RC4-MD5',
];

// HSTS: masa berlaku minimum yang dianggap memadai (180 hari). Nilai yang
// benar-benar dikirim ada di MY_Controller (365 hari).
$config['transport_hsts_min_age'] = 15552000;

// Sisa masa berlaku sertifikat situs yang dianggap wajar sebelum diberi
// peringatan oleh alat uji (hari).
$config['transport_cert_warn_days'] = 21;

// Algoritma kriptografi tingkat aplikasi, sebagai kebijakan tertulis. Tes
// tests/transport_security_test.php memeriksa bahwa kode masih memakainya.
$config['crypto_algorithms'] = [
    'kata_sandi'        => 'bcrypt (password_hash, PASSWORD_BCRYPT)',
    'data_pribadi'      => 'AES-256-GCM (openssl, IV/nonce acak, tag autentikasi)',
    'turunan_kunci'     => 'HKDF-SHA256 (kunci enkripsi log diturunkan terpisah dari kunci data)',
    'pencarian_nik'     => 'HMAC-SHA256 (hash deterministik dengan pepper)',
    'token_sesi'        => 'SHA-256 atas token acak random_bytes()',
    'angka_acak'        => 'random_bytes() / CSPRNG sistem operasi',
];
