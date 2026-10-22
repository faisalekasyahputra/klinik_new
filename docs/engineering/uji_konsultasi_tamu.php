<?php
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
// Jalankan: C:/xampp/php/php.exe docs/engineering/uji_konsultasi_tamu.php
// UAT no login No. 15 dan 16. Sesi tamu terpisah; tidak mengubah akun/data aplikasi.
$base = 'http://localhost/klinik_new/';
$gagal = 0;
foreach (['umum/forum' => 'Konsultasi', 'KemitraanPortal' => 'KKN dan Magang'] as $rute => $layanan) {
foreach ([FALSE, TRUE] as $ajax) {
    $cookie = tempnam(sys_get_temp_dir(), 'uat_konsultasi_');
    try {
        $ch = curl_init($base . $rute);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_FOLLOWLOCATION => TRUE,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_COOKIEFILE => $cookie,
            CURLOPT_COOKIEJAR => $cookie,
            CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : [],
        ]);
        $body = curl_exec($ch);
        $info = curl_getinfo($ch);
        $error = curl_error($ch);
        curl_close($ch);
        $pesan = 'Silakan masuk terlebih dahulu untuk membuka ' . $layanan . '.';
        if ($rute === 'KemitraanPortal') {
            // Sejak 22 Okt 2026 (permintaan user) tamu melihat kartu menu tanpa modal: halaman 200
            // di alamatnya sendiri, tanpa data selain dua kartu. Modal login baru muncul saat kartu
            // diklik, karena halaman di balik kartu tetap digerbang server.
            $ok = $body !== FALSE && $info['http_code'] === 200
                && strtolower($info['url']) === strtolower($base . $rute)
                && strpos($body, 'KemitraanPortal/daftar/magang') !== FALSE
                && strpos($body, 'KemitraanPortal/kkn"') !== FALSE
                && strpos($body, 'data-perlu-masuk') === FALSE;
            $hasil = 'kartu menu tanpa modal';
        } else {
            $ok = $body !== FALSE && $info['http_code'] === 200
                && strtolower($info['url']) === strtolower($base . 'Auth/login')
                && $info['redirect_count'] > 0
                && strpos($body, 'type="password"') !== FALSE
                && strpos($body, $pesan) !== FALSE;
            $hasil = 'form login';
        }
        echo ($ok ? '[OK] ' : '[FAIL] ') . $layanan . ' tamu '
            . ($ajax ? 'AJAX' : 'navigasi langsung') . ' -> ' . $hasil
            . ($error ? ' (' . $error . ')' : '') . PHP_EOL;
        if (!$ok) { $gagal++; }
    } finally {
        unlink($cookie);
    }
}
}
exit($gagal ? 1 : 0);
