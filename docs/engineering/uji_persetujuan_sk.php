<?php
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Uji persetujuan S&K saat daftar (keputusan pemilik produk 26 Sep 2026).
 *
 *   php docs/engineering/uji_persetujuan_sk.php
 *
 * Dulu centang S&K hanya dijaga atribut required di peramban; do_register tanpa tos_agree
 * tetap membuat akun. Sekarang server menolaknya, dan pendaftaran yang sah meninggalkan
 * baris persetujuan_sk di jejak audit. Akun uji dibuat dan dihapus sendiri.
 */
$BASE = rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/';
$env = [];
foreach (file(dirname(__DIR__, 2) . '/.env', FILE_IGNORE_NEW_LINES) as $l) { $l = trim($l); if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) continue; [$k, $v] = explode('=', $l, 2); $env[trim($k)] ??= trim($v); }
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$tag = 'ujisk' . bin2hex(random_bytes(3)); $sandi = 'Sk1#' . bin2hex(random_bytes(5));
$total = 0; $gagal = 0; $jars = [];
$cek = function ($ok, $l) use (&$total, &$gagal) { $total++; if (!$ok) $gagal++; echo ($ok ? '  OK    ' : '  GAGAL ') . $l . "\n"; };
$daftar = function ($email, $tos) use ($BASE, &$jars) {
    $j = tempnam(sys_get_temp_dir(), 'sk'); $jars[] = $j;
    $c = curl_init($BASE . 'Auth/login'); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_COOKIEJAR => $j, CURLOPT_COOKIEFILE => $j]); curl_exec($c); curl_close($c); unset($c); // PHP 8: jar baru ditulis saat handle dilepas
    $csrf = ''; foreach (file($j) as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') $csrf = $p[6]; }
    $post = ['email' => $email, 'password' => $GLOBALS['sandi'], 'password_confirm' => $GLOBALS['sandi'], 'csrf_kpkp_token' => $csrf] + ($tos ? ['tos_agree' => 'on'] : []);
    $c = curl_init($BASE . 'Auth/do_register'); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_COOKIEJAR => $j, CURLOPT_COOKIEFILE => $j, CURLOPT_POSTFIELDS => http_build_query($post), CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest']]);
    $b = (string) curl_exec($c); curl_close($c); if (getenv("UJI_DEBUG")) echo $b, "
";
    if ((json_decode($b, TRUE)['status'] ?? '') === 'otp_required') { // akun baru lahir sesudah kode OTP benar
        require_once __DIR__ . '/_otp_uji.php';
        $c = curl_init($BASE . 'Auth/do_verifikasi_email'); curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_COOKIEJAR => $j, CURLOPT_COOKIEFILE => $j, CURLOPT_POSTFIELDS => http_build_query(['kode_otp' => kode_otp_uji($email), 'csrf_kpkp_token' => $csrf]), CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest']]);
        $b = (string) curl_exec($c); curl_close($c);
    }
    return json_decode($b, TRUE) ?: ['raw' => substr($b, 0, 200)];
};
$ada = function ($email) use ($db) { $st = $db->prepare('SELECT id FROM usr_akun WHERE email=?'); $st->bind_param('s', $email); $st->execute(); return $st->get_result()->fetch_row()[0] ?? NULL; };
// Ember batas laju pendaftaran per IP dipinjam lalu dikembalikan utuh (::1 tercatat per /64),
// supaya suite lain yang juga mendaftar tidak membuat suite ini merah 429.
$ember = [];
foreach (['127.0.0.1', '::1', '0000000000000000/64'] as $ip) {
    $k = hash('sha256', 'register:ip:' . $ip);
    $ember[$k] = $db->query("SELECT kunci, jendela_mulai_at, jumlah_gagal FROM sys_batas_laju WHERE kunci='$k'")->fetch_assoc();
    $db->query("DELETE FROM sys_batas_laju WHERE kunci='$k'");
}
try {
    echo "=== UJI PERSETUJUAN S&K ===\n";
    $r = $daftar("{$tag}_tanpa@example.test", FALSE);
    $cek(($r['status'] ?? '') !== 'success' && stripos((string) ($r['message'] ?? ''), 'Ketentuan Layanan') !== FALSE && $ada("{$tag}_tanpa@example.test") === NULL,
        'Daftar tanpa centang S&K ditolak server, akun tidak terbentuk');
    $r = $daftar("{$tag}_setuju@example.test", TRUE);
    $id = $ada("{$tag}_setuju@example.test");
    $cek(($r['status'] ?? '') === 'success' && $id !== NULL, 'Daftar dengan centang S&K berhasil');
    $jml = $id ? (int) $db->query("SELECT COUNT(*) FROM sys_jejak_audit WHERE aksi='persetujuan_sk' AND objek_id='" . (int) $id . "'")->fetch_row()[0] : 0;
    $cek($jml === 1, 'Persetujuan S&K tercatat sekali di jejak audit untuk akun itu');
} finally {
    $db->query("DELETE FROM sys_jejak_audit WHERE aksi='persetujuan_sk' AND objek_id IN (SELECT id FROM usr_akun WHERE email LIKE '{$tag}\_%@example.test')");
    $db->query("DELETE FROM usr_akun WHERE email LIKE '{$tag}\_%@example.test'");
    foreach ($jars as $f) { @unlink($f); }
    foreach ($ember as $k => $row) {
        $db->query("DELETE FROM sys_batas_laju WHERE kunci='$k'");
        if ($row) { $st = $db->prepare('INSERT INTO sys_batas_laju (kunci, jendela_mulai_at, jumlah_gagal) VALUES (?,?,?)'); $st->bind_param('ssi', $row['kunci'], $row['jendela_mulai_at'], $row['jumlah_gagal']); $st->execute(); }
    }
}
echo "\nRINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal ? 1 : 0);
