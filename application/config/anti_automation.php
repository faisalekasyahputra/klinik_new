<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kebijakan kontrol anti-otomatisasi dan peringatan (form keamanan poin 10.4 dan 10.5).
 * Sumber tunggal untuk: helpers/anti_automation_helper.php, libraries/Anti_automation.php,
 * libraries/Security_alert.php, controllers/Jebakan.php, dan tests/anti_automation_test.php.
 * Penjelasan dan prosedur: docs/engineering/ANTI_OTOMATISASI.md.
 */

/* Tanda tangan alat serangan/pemindai kerentanan yang dikenal pada User-Agent.
   Sengaja SEMPIT: klien generik (curl, python-requests, dsb) TIDAK diblokir karena
   dipakai alat uji dan pemantauan yang sah. Yang diblokir adalah alat yang tujuannya
   memang menyerang. Cocok = 403 + peringatan ke admin. */
$config['scanner_user_agents'] = [
    'sqlmap', 'nikto', 'nmap', 'masscan', 'acunetix', 'nessus', 'openvas', 'wpscan',
    'dirbuster', 'gobuster', 'ffuf', 'wfuzz', 'hydra', 'zgrab', 'nuclei', 'metasploit',
    'havij', 'netsparker', 'appscan', 'w3af', 'arachni', 'skipfish', 'jaeles', 'commix',
    '\$\{jndi:',
];

/* Kelas rute yang mendapat batas TAMBAHAN di atas batas global. Pola dicocokkan
   (fnmatch, huruf kecil) terhadap "controller/metode". Kunci = akhiran nama kebijakan
   di config/rate_limits.php (kelas_<kunci>_ip dan kelas_<kunci>_akun). */
$config['route_classes'] = [
    'cari'  => ['index/cari_wil', 'index/cari_rumah'],
    'api'   => ['program/api_*', 'program/cek_tiket'],
    'unduh' => ['*/export*', '*/unduh*', '*/lihat_*', '*/cetak_*', 'index/buka_foto'],
];

/* Metode HTTP yang mengubah keadaan; dihitung oleh batas tulis. */
$config['write_methods'] = ['POST', 'PUT', 'PATCH', 'DELETE'];

/* Jalur jebakan (honeypot): alamat yang TIDAK pernah dipakai pengguna sah tetapi
   selalu dicoba pemindai otomatis. Diarahkan ke Jebakan::index lewat routes.php;
   tes memastikan setiap pola di sini punya rute. */
$config['probe_paths'] = [
    'wp-login.php', 'wp-admin', 'wp-admin/(:any)', 'wp-content/(:any)', 'wp-includes/(:any)',
    'xmlrpc.php', 'phpmyadmin', 'phpmyadmin/(:any)', 'pma', 'myadmin', 'adminer.php',
    'administrator', 'administrator/(:any)', 'admin.php', 'shell.php', 'cgi-bin/(:any)',
    'boaform/(:any)', 'HNAP1', 'actuator/(:any)', 'solr/(:any)', 'manager/html',
];

/* Jebakan formulir (honeypot + token waktu) untuk endpoint publik tanpa akun:
   login, registrasi, lupa kata sandi. Berdiri sendiri, tidak bergantung pada Google
   reCAPTCHA (kunci situsnya kosong di production per 21 Sep 2026, jadi halaman-halaman itu
   tidak punya tantangan bot sama sekali sebelum ini). */
$config['bot_guard'] = [
    'honeypot_field'   => 'situs_web',
    'token_field'      => 'bot_token',
    'token_max_age'    => 21600,          // 6 jam; lewat itu = muat ulang halaman
    'min_fill_seconds' => 1,              // hanya ditegakkan di production (lihat Bot_guard)
    'forms'            => ['login', 'register', 'forgot'],
];

/* Peringatan keamanan ke administrator. */
$config['security_alert'] = [
    'aksi'            => 'peringatan_keamanan',   // nilai kolom `aksi` di sys_jejak_audit
    'push_tingkat'    => ['tinggi'],               // tingkat yang juga dikirim sebagai Web Push
    'push_audience'   => [['role' => 'admin']],    // superadmin
    'push_url'        => 'Admin_Audit?aksi=peringatan_keamanan',
    'banner_jam'      => 24,                       // jendela ringkasan di dasbor superadmin
];
