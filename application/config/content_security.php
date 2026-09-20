<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kebijakan konten dan izin peramban (form keamanan poin 9.3 dan 9.4).
 * Sumber tunggal untuk: helpers/content_security_helper.php (header respons),
 * tests/malicious_code_test.php (penjaga regresi), dan docs/engineering/uji_tls_situs.php
 * (uji situs live). Penjelasan dan prosedur: docs/engineering/KODE_BERBAHAYA.md.
 */

/* Host yang BOLEH memasok skrip. Aplikasi tidak boleh memuat kode dari sumber
   lain (ASVS 10.3.2). Menambah host = keputusan keamanan yang harus lewat
   review; tes memaksa daftar ini sama dengan semua host skrip di view.
     - jsdelivr/unpkg/code.jquery.com: aset bersemat versi, SEMUANYA ber-SRI.
     - google.com/gstatic.com: hanya reCAPTCHA (Google melarang SRI, lihat sri_pengecualian). */
$config['csp_script_hosts'] = [
    'https://cdn.jsdelivr.net',
    'https://unpkg.com',
    'https://code.jquery.com',
    'https://www.google.com',
    'https://www.gstatic.com',
];

/* 'unsafe-inline': banyak skrip inline di view. 'unsafe-eval': Alpine.js versi
   standar mengevaluasi ekspresi dengan `new Function`. Keduanya sengaja diakui
   sebagai kelemahan yang tersisa; yang DITEGAKKAN CSP ini adalah daftar host di
   atas, objek/plugin ditiadakan, dan <base> tidak bisa dibajak. */
$config['csp_script_sources_dasar'] = ["'self'", "'unsafe-inline'", "'unsafe-eval'"];

/* Aset eksternal yang TIDAK bisa ber-SRI, dan alasannya. Tes memastikan
   setiap aset eksternal lain di view memiliki integrity=. */
$config['sri_pengecualian'] = [
    'https://www.google.com/recaptcha/api.js' => 'Google melarang SRI dan mengubah skripnya tanpa pemberitahuan; penyedia tepercaya yang memang diperlukan untuk reCAPTCHA.',
    'https://fonts.googleapis.com/'           => 'CSS Google Fonts dibangkitkan per User-Agent sehingga hash tidak stabil; CSS tidak dapat menjalankan skrip.',
    'https://fonts.gstatic.com/'              => 'Hanya preconnect ke berkas font; tidak memuat kode.',
];

/* Izin fitur peramban. Semua yang berkaitan dengan sensor dan privasi DITOLAK,
   kecuali yang benar-benar dipakai: geolokasi (self), hanya lewat tombol
   "Gunakan lokasi saya" di wizard pendataan (dipanggil karena klik pengguna,
   bukan saat halaman dibuka). Notifikasi push admin meminta izin lewat klik
   tombol di assets/js/admin-web-push.js; Notification bukan fitur Permissions-Policy. */
$config['permissions_policy'] = [
    'accelerometer'      => '()',
    'camera'             => '()',
    'display-capture'    => '()',
    'geolocation'        => '(self)',
    'gyroscope'          => '()',
    'hid'                => '()',
    'magnetometer'       => '()',
    'microphone'         => '()',
    'midi'               => '()',
    'payment'            => '()',
    'serial'             => '()',
    'usb'                => '()',
    'xr-spatial-tracking' => '()',
];

/* API sensor/privasi di peramban dan SATU-SATUNYA berkas yang boleh memakainya.
   tests/malicious_code_test.php menggagalkan bila API ini muncul di berkas lain. */
$config['sensor_api_allowlist'] = [
    'navigator.geolocation'          => ['views/pages/warga/pendataan.php'],
    'Notification.requestPermission' => ['assets/js/admin-web-push.js'],
    'new Notification'               => ['assets/js/admin-web-push.js'],
    'pushManager'                    => ['assets/js/admin-web-push.js'],
    'serviceWorker'                  => ['assets/js/admin-web-push.js'],
    // Service worker sisi-terima dari fitur push yang sama (menampilkan notifikasi yang dikirim server).
    'showNotification'               => ['push-sw.js'],
];
