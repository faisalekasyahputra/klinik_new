<?php
/**
 * Uji onboarding mahasiswa tanpa NIK (daftar revisi dinas 23 Sep 2026).
 *
 *   php docs/engineering/uji_onboarding_mahasiswa.php
 *
 * Mahasiswa tidak dimintai NIK (identitasnya NIM di formulir magang); warga tetap wajib NIK.
 * Dua akun sekali pakai berstatus profile_completed=0 dibuat dan dihapus sendiri.
 */
$BASE = rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/';
$env = [];
foreach (file(dirname(__DIR__, 2) . '/.env', FILE_IGNORE_NEW_LINES) as $l) { $l = trim($l); if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) continue; [$k, $v] = explode('=', $l, 2); if (!isset($env[trim($k)])) $env[trim($k)] = trim($v); }
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$tag = 'ujiobm' . bin2hex(random_bytes(3));
$sandi = 'Oo1#' . bin2hex(random_bytes(5));
$total = 0; $gagal = 0; $jars = [];
$cek = function ($ok, $l) use (&$total, &$gagal) { $total++; if (!$ok) $gagal++; echo ($ok ? '  OK    ' : '  GAGAL ') . $l . "\n"; };
$http = function ($jar, $path, $post = NULL) use ($BASE) {
    $ch = curl_init($BASE . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 30]);
    if ($post !== NULL) curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    $b = (string) curl_exec($ch); curl_close($ch); return html_entity_decode($b);
};
$csrf = function ($jar) { foreach (file($jar) as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') return $p[6]; } return ''; };
$coba = function ($peran) use ($db, $tag, $sandi, $http, $csrf, &$jars) {
    $email = "{$tag}_{$peran}@example.test"; $h = password_hash($sandi, PASSWORD_BCRYPT);
    $st = $db->prepare("INSERT INTO usr_users (name,email,password,role,profile_completed,status,email_verified_at,password_changed_at,password_expires_at,created_at) VALUES ('Uji Onb',?,?,NULL,0,'active',NOW(),NOW(),DATE_ADD(NOW(),INTERVAL 90 DAY),NOW())");
    $st->bind_param('ss', $email, $h); $st->execute(); $id = $db->insert_id;
    $j = tempnam(sys_get_temp_dir(), 'obm'); $jars[] = $j;
    $http($j, 'Auth/login'); $http($j, 'Auth/do_login', ['email' => $email, 'password' => $sandi, 'csrf_kpkp_token' => $csrf($j)]);
    $http($j, 'Auth/onboarding');
    $b = $http($j, 'Auth/save_onboarding', ['csrf_kpkp_token' => $csrf($j), 'role' => $peran, 'username' => $tag . $peran,
        'nama_lengkap' => 'Uji ' . $peran, 'alamat_domisili' => 'Jl. Uji No. 1', 'phone' => '081234567890', 'nik_identitas' => '']);
    return [$db->query("SELECT role, profile_completed, nik, nik_lookup_hash FROM usr_users WHERE id={$id}")->fetch_assoc(), $b];
};
try {
    echo "=== UJI ONBOARDING MAHASISWA TANPA NIK ===\n";
    [$m, $bm] = $coba('mahasiswa');
    $cek($m['role'] === 'mahasiswa' && (int) $m['profile_completed'] === 1, 'Mahasiswa tanpa NIK menyelesaikan onboarding');
    $cek($m['nik'] === NULL && $m['nik_lookup_hash'] === NULL, 'Tidak ada NIK kosong/terenkripsi yang tersimpan untuk mahasiswa');
    [$w, $bw] = $coba('warga');
    $cek((int) $w['profile_completed'] === 0, 'Warga tanpa NIK TETAP ditolak (NIK wajib untuk warga)');
    $cek(stripos($bw, 'wajib') !== FALSE, 'Warga mendapat pesan field wajib');
} finally {
    $db->query("DELETE FROM usr_users WHERE email LIKE '{$tag}_%@example.test'");
    foreach ($jars as $j) @unlink($j);
}
echo "\nRINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal ? 1 : 0);
