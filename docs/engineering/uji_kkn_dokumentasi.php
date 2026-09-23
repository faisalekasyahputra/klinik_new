<?php
/**
 * Uji link dokumentasi KKN (migrasi 061, KemitraanPortal::kkn_simpan_dokumentasi).
 *
 *   php docs/engineering/uji_kkn_dokumentasi.php
 *
 * Daftar revisi dinas 23 Sep 2026: universitas menyimpan link folder dokumentasi KKN di cloud,
 * admin melihatnya. Dijaga: hanya pemilik KKN, hanya http/https, kosong = hapus, tampil di admin.
 * Akun dan KKN uji dibuat dan dihapus sendiri.
 */
$BASE = rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/';
$env = [];
foreach (file(dirname(__DIR__, 2) . '/.env', FILE_IGNORE_NEW_LINES) as $l) { $l = trim($l); if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) continue; [$k, $v] = explode('=', $l, 2); if (!isset($env[trim($k)])) $env[trim($k)] = trim($v); }
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$tag = 'ujidok' . bin2hex(random_bytes(3));
$sandi = 'Dd1#' . bin2hex(random_bytes(5));
$total = 0; $gagal = 0; $jars = [];
$cek = function ($ok, $l) use (&$total, &$gagal) { $total++; if (!$ok) $gagal++; echo ($ok ? '  OK    ' : '  GAGAL ') . $l . "\n"; };
$akun = function ($role) use ($db, $tag, $sandi) {
    $e = "{$tag}_{$role}_" . mt_rand(100, 999) . '@example.test'; $h = password_hash($sandi, PASSWORD_BCRYPT);
    $st = $db->prepare("INSERT INTO usr_users (name,email,password,role,phone,status,profile_completed,email_verified_at,password_changed_at,password_expires_at,created_at) VALUES ('Uji Dok',?,?,?,'081234567890','active',1,NOW(),NOW(),DATE_ADD(NOW(),INTERVAL 90 DAY),NOW())");
    $st->bind_param('sss', $e, $h, $role); $st->execute(); return [$db->insert_id, $e];
};
$http = function ($jar, $path, $post = NULL) use ($BASE) {
    $ch = curl_init($BASE . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 30]);
    if ($post !== NULL) curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    $b = (string) curl_exec($ch); curl_close($ch); return html_entity_decode($b);
};
$csrf = function ($jar) { foreach (file($jar) as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') return $p[6]; } return ''; };
$login = function ($email) use ($http, $csrf, $sandi, &$jars) { $j = tempnam(sys_get_temp_dir(), 'dok'); $jars[] = $j; $http($j, 'Auth/login'); $http($j, 'Auth/do_login', ['email' => $email, 'password' => $sandi, 'csrf_kpkp_token' => $csrf($j)]); return $j; };
$link = function ($id) use ($db) { $r = $db->query("SELECT link_dokumentasi FROM kkn_magang_pendaftaran WHERE id=" . (int) $id)->fetch_row(); return $r ? $r[0] : 'BARIS HILANG'; };
$simpan = function ($jar, $id, $url) use ($http, $csrf) { return $http($jar, 'KemitraanPortal/kkn_simpan_dokumentasi/' . $id, ['csrf_kpkp_token' => $csrf($jar), 'link_dokumentasi' => $url]); };
try {
    echo "=== UJI LINK DOKUMENTASI KKN ===\n";
    $cek($db->query("SHOW COLUMNS FROM kkn_magang_pendaftaran LIKE 'link_dokumentasi'")->num_rows === 1, 'Kolom link_dokumentasi ada (migrasi 061)');
    [$uidA, $eA] = $akun('universitas'); [$uidB, $eB] = $akun('universitas'); [, $eAdm] = $akun('admin');
    $db->query("INSERT INTO kkn_magang_pendaftaran (user_id,jenis,instansi_asal,no_hp,divisi_atau_tema,periode_mulai,periode_selesai,status,created_at) VALUES ({$uidA},'kkn','{$tag} Kampus','081234567890','Tema Uji','2098-01-01','2098-02-01','Diajukan',NOW())");
    $kkn = $db->insert_id;
    $jA = $login($eA);
    $cek(strpos($http($jA, 'KemitraanPortal/pendaftaran/' . $kkn), 'kkn_simpan_dokumentasi/' . $kkn) !== FALSE, 'Pemilik KKN melihat formulir link dokumentasi');
    $url = 'https://drive.google.com/drive/folders/' . $tag;
    $simpan($jA, $kkn, $url);
    $cek($link($kkn) === $url, 'Link https tersimpan');
    $cek(strpos($http($jA, 'KemitraanPortal/pendaftaran/' . $kkn), $url) !== FALSE, 'Link tampil kembali di dashboard universitas');
    $simpan($jA, $kkn, 'javascript:alert(1)');
    $cek($link($kkn) === $url, 'Skema javascript: ditolak, link lama tetap');
    $simpan($jA, $kkn, 'ftp://contoh.test/x');
    $cek($link($kkn) === $url, 'Skema selain http/https ditolak');
    $jB = $login($eB);
    $simpan($jB, $kkn, 'https://penyusup.test/');
    $cek($link($kkn) === $url, 'Universitas lain TIDAK bisa mengubah link KKN milik orang (anti-IDOR)');
    $jAdm = $login($eAdm);
    $cek(strpos($http($jAdm, 'Admin_Kemitraan?q=' . urlencode($tag)), $url) !== FALSE, 'Admin melihat link dokumentasi di layar Kemitraan');
    $simpan($jA, $kkn, '');
    $cek($link($kkn) === NULL, 'Mengosongkan lalu simpan menghapus link');
} finally {
    $db->query("DELETE FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE '{$tag}%'");
    $db->query("DELETE FROM usr_users WHERE email LIKE '{$tag}_%@example.test'");
    foreach ($jars as $j) @unlink($j);
}
echo "\nRINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal ? 1 : 0);
