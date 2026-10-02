<?php
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Uji sesi tunggal dan pergantian ID sesi rutin (27 Sep 2026).
 *
 *   php docs/engineering/uji_sesi_tunggal.php
 *
 * CodeIgniter mengganti ID sesi tiap sess_time_to_update (300 detik) dan membawa isi sesi.
 * Penjaga sesi tunggal dulu hanya mencocokkan hash ID sesi yang ditulis saat login, sehingga
 * semua peran terlempar "Sesi Anda telah berakhir" sekitar 5 menit sekali. Menunggu 5 menit
 * sungguhan terlalu lambat, jadi keadaan sesudah pergantian ID ditiru: hash ID di DB dibuat
 * tidak cocok sementara token sesi tetap sah. Login di perangkat lain harus tetap ditendang.
 */
$BASE = rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/';
$env = [];
foreach (file(dirname(__DIR__, 2) . '/.env', FILE_IGNORE_NEW_LINES) as $l) { $l = trim($l); if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) continue; [$k, $v] = explode('=', $l, 2); $env[trim($k)] ??= trim($v); }
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$tag = 'ujisesi' . bin2hex(random_bytes(3)); $sandi = 'Se1#' . bin2hex(random_bytes(5));
$email = "{$tag}@example.test";
$total = 0; $gagal = 0; $jars = [];
$cek = function ($ok, $l) use (&$total, &$gagal) { $total++; if (!$ok) $gagal++; echo ($ok ? '  OK    ' : '  GAGAL ') . $l . "\n"; };
$http = function ($jar, $path, $post = NULL) use ($BASE) {
    $c = curl_init($BASE . $path);
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $post !== NULL ? ['X-Requested-With: XMLHttpRequest'] : []]);
    if ($post !== NULL) curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post));
    $b = (string) curl_exec($c); $u = (string) curl_getinfo($c, CURLINFO_EFFECTIVE_URL); curl_close($c); unset($c); // PHP 8: jar ditulis saat handle dilepas
    return [$b, $u];
};
$login = function () use ($http, $email, $sandi, &$jars) {
    $j = tempnam(sys_get_temp_dir(), 'ss'); $jars[] = $j;
    $http($j, 'Auth/login');
    $csrf = ''; foreach (file($j) as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') $csrf = $p[6]; }
    [$b] = $http($j, 'Auth/do_login', ['email' => $email, 'password' => $sandi, 'csrf_kpkp_token' => $csrf]);
    return [$j, (json_decode($b, TRUE)['status'] ?? '') === 'success'];
};
$hash_id = function () use ($db, $email) { return $db->query("SELECT sesi_aktif_id_hash FROM usr_akun WHERE email='{$email}'")->fetch_row()[0] ?? NULL; };
$masuk = function ($jar) use ($http) { [, $u] = $http($jar, 'akun'); return strpos($u, 'Auth/login') === FALSE && strpos($u, '/login') === FALSE; };

// Ember batas laju login per IP dipinjam lalu dikembalikan utuh (::1 tercatat per /64).
$ember = [];
foreach (['127.0.0.1', '::1', '0000000000000000/64'] as $ip) {
    $k = hash('sha256', 'login:ip:' . $ip);
    $ember[$k] = $db->query("SELECT kunci, jendela_mulai_at, jumlah_gagal FROM sys_batas_laju WHERE kunci='$k'")->fetch_assoc();
    $db->query("DELETE FROM sys_batas_laju WHERE kunci='$k'");
}
try {
    echo "=== UJI SESI TUNGGAL ===\n";
    $h = password_hash($sandi, PASSWORD_BCRYPT);
    $db->query("INSERT INTO usr_akun (nama,email,kata_sandi,peran,status,profil_lengkap,email_verified_at,sandi_diganti_at,sandi_kedaluwarsa_at,created_at) VALUES ('Uji Sesi','{$email}','{$h}','pengembang','active',1,NOW(),NOW(),DATE_ADD(NOW(),INTERVAL 90 DAY),NOW())");

    [$a, $ok] = $login();
    $cek($ok && $masuk($a), 'Login dan buka /akun');

    // Tiru pergantian ID sesi rutin: hash ID di DB tidak lagi cocok, token tetap sah.
    $db->query("UPDATE usr_akun SET sesi_aktif_id_hash='" . hash('sha256', 'id-sesi-lama-' . $tag) . "' WHERE email='{$email}'");
    $cek($masuk($a), 'Sesudah ID sesi berganti, pengguna yang sama TIDAK dikeluarkan');
    $cek($hash_id() !== hash('sha256', 'id-sesi-lama-' . $tag), 'Hash ID sesi di DB diperbarui ke ID yang sedang dipakai');
    $cek($masuk($a), 'Permintaan berikutnya tetap masuk');

    [$b, $ok] = $login();
    $cek($ok && $masuk($b), 'Login kedua (perangkat lain) berhasil');
    $cek( ! $masuk($a), 'Sesi pertama ditendang karena tokennya digantikan');
    $cek($masuk($b), 'Sesi kedua tetap berjalan');
} finally {
    $db->query("DELETE FROM usr_akun WHERE email='{$email}'");
    foreach ($ember as $k => $row) {
        $db->query("DELETE FROM sys_batas_laju WHERE kunci='$k'");
        if ($row) { $st = $db->prepare('INSERT INTO sys_batas_laju (kunci, jendela_mulai_at, jumlah_gagal) VALUES (?,?,?)'); $st->bind_param('ssi', $row['kunci'], $row['jendela_mulai_at'], $row['jumlah_gagal']); $st->execute(); }
    }
    foreach ($jars as $f) { @unlink($f); }
}
echo "\nRINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal ? 1 : 0);
