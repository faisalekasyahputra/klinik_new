<?php
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Uji dua perbaikan SRP2 dari simulasi pengembang 27 Sep 2026:
 *
 *   php docs/engineering/uji_srp2_onboarding_terima.php
 *
 * 1. Alamat kantor yang diisi saat onboarding pengembang ikut ke pengajuan SRP2
 *    (srp2_registrations.alamat_kantor), dulu hilang dari alur SRP2.
 * 2. Admin menerima pengajuan yang nama perusahaannya sudah dipakai baris lain di
 *    direktori bersertifikat: pesan jelas ke admin dan status tetap Pending, bukan
 *    galat SQL mentah (HTTP 500).
 * Akun, pengajuan, dan baris direktori uji dibuat dan dihapus sendiri.
 */
$BASE = rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/';
$env = [];
foreach (file(dirname(__DIR__, 2) . '/.env', FILE_IGNORE_NEW_LINES) as $l) { $l = trim($l); if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) continue; [$k, $v] = explode('=', $l, 2); $env[trim($k)] ??= trim($v); }
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$tag = 'ujisrpot' . bin2hex(random_bytes(3)); $TAG = strtoupper($tag);
$sandi = 'Sr1#' . bin2hex(random_bytes(5));
$total = 0; $gagal = 0; $jars = [];
$cek = function ($ok, $l) use (&$total, &$gagal) { $total++; if (!$ok) $gagal++; echo ($ok ? '  OK    ' : '  GAGAL ') . $l . "\n"; };
$http = function ($jar, $path, $post = NULL, $ajax = FALSE) use ($BASE) {
    $c = curl_init($BASE . $path);
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : []]);
    if ($post !== NULL) curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post));
    $b = (string) curl_exec($c); $k = (int) curl_getinfo($c, CURLINFO_HTTP_CODE); curl_close($c); unset($c); // PHP 8: jar ditulis saat handle dilepas
    return [$b, $k];
};
$csrf = function ($jar) { foreach (file($jar) as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') return $p[6]; } return ''; };
$jar = function () use (&$jars) { $j = tempnam(sys_get_temp_dir(), 'so'); $jars[] = $j; return $j; };
$satu = function ($sql) use ($db) { $r = $db->query($sql); return $r ? $r->fetch_row()[0] ?? NULL : NULL; };

// Ember batas laju register dan login per IP dipinjam lalu dikembalikan utuh (::1 tercatat per /64).
$ember = [];
foreach (['register', 'login'] as $pol) foreach (['127.0.0.1', '::1', '0000000000000000/64'] as $ip) {
    $k = hash('sha256', $pol . ':ip:' . $ip);
    $ember[$k] = $db->query("SELECT limit_key, window_started_at, failed_attempts FROM sys_rate_limits WHERE limit_key='$k'")->fetch_assoc();
    $db->query("DELETE FROM sys_rate_limits WHERE limit_key='$k'");
}
try {
    echo "=== UJI SRP2: ONBOARDING DAN TERIMA ===\n";

    // 1. Onboarding pengembang dengan alamat kantor.
    $jd = $jar(); $email = "{$tag}_dev@example.test";
    $http($jd, 'Auth/login');
    [$b] = $http($jd, 'Auth/do_register', ['email' => $email, 'password' => $sandi, 'password_confirm' => $sandi, 'tos_agree' => '1', 'csrf_kpkp_token' => $csrf($jd)], TRUE);
    $cek((json_decode($b, TRUE)['status'] ?? '') === 'success', 'Daftar akun pengembang uji');
    $http($jd, 'Auth/onboarding');
    $npwp = '9' . str_pad((string) random_int(0, 99999999999999), 14, '0', STR_PAD_LEFT);
    $http($jd, 'Auth/save_onboarding', ['csrf_kpkp_token' => $csrf($jd), 'role' => 'pengembang', 'username' => $tag, 'nama_lengkap' => 'Uji ' . $TAG,
        'npwp' => $npwp, 'alamat_domisili' => 'Jl. Domisili ' . $TAG, 'phone' => '081234567890', 'nama_perusahaan' => 'PT ' . $TAG,
        'alamat_kantor' => 'Jl. Kantor ' . $TAG, 'telp_kantor' => '0241234567']);
    $uid = (int) $satu("SELECT id FROM usr_users WHERE email='{$email}' AND role='pengembang'");
    $reg = $db->query("SELECT id, alamat_kantor, nama_perusahaan FROM srp2_registrations WHERE user_id={$uid}")->fetch_assoc();
    $cek($uid > 0 && $reg !== NULL, 'Onboarding pengembang membuat draft pengajuan SRP2');
    $cek(($reg['alamat_kantor'] ?? '') === 'Jl. Kantor ' . $TAG, 'Alamat kantor dari onboarding tersalin ke pengajuan SRP2');

    // 2. Admin menerima pengajuan yang namanya sudah dipakai baris lain di direktori.
    $rid = (int) ($reg['id'] ?? 0);
    $nama = $db->real_escape_string((string) ($reg['nama_perusahaan'] ?? ''));
    $db->query("UPDATE srp2_registrations SET status_verifikasi='Pending' WHERE id={$rid}");
    $db->query("INSERT INTO srp2_certified_developers (nama_perusahaan, status_aktif) VALUES ('{$nama}', 1)");
    $cid_lain = (int) $db->insert_id;
    $ea = "{$tag}_admin@example.test"; $h = password_hash($sandi, PASSWORD_BCRYPT);
    $db->query("INSERT INTO usr_users (name,email,password,role,status,profile_completed,email_verified_at,password_changed_at,password_expires_at,created_at) VALUES ('Admin Uji','{$ea}','{$h}','admin','active',1,NOW(),NOW(),DATE_ADD(NOW(),INTERVAL 90 DAY),NOW())");
    $ja = $jar(); $http($ja, 'Auth/login');
    $http($ja, 'Auth/do_login', ['email' => $ea, 'password' => $sandi, 'csrf_kpkp_token' => $csrf($ja)], TRUE);
    $http($ja, 'Admin_Srp2/detail/' . $rid);
    [$b, $kode] = $http($ja, 'Admin_Srp2/proses/' . $rid, ['csrf_kpkp_token' => $csrf($ja), 'status' => 'Diterima']);
    $cek($kode === 200 && stripos($b, 'Error Number') === FALSE && stripos($b, 'Duplicate entry') === FALSE, 'Bentrok nama tidak menampilkan galat SQL mentah');
    $cek(strpos($b, 'sudah dipakai pengembang lain di direktori bersertifikat') !== FALSE, 'Admin mendapat pesan bentrok nama yang jelas');
    $cek($satu("SELECT status_verifikasi FROM srp2_registrations WHERE id={$rid}") === 'Pending'
        && (int) $satu("SELECT COUNT(*) FROM srp2_certified_developers WHERE nama_perusahaan='{$nama}'") === 1, 'Status tetap Pending dan tidak ada baris direktori baru');
    $audit = "SELECT COUNT(*) FROM sys_jejak_audit WHERE aksi='srp2_keputusan' AND objek_tipe='srp2_registrations' AND objek_id='{$rid}'";
    $cek((int) $satu($audit) === 0, 'Keputusan yang gagal tidak meninggalkan jejak audit');
    // Bentrok dirapikan, keputusan diulang: kali ini tersimpan DAN tercatat di jejak audit.
    $db->query("DELETE FROM srp2_certified_developers WHERE id={$cid_lain}");
    $http($ja, 'Admin_Srp2/proses/' . $rid, ['csrf_kpkp_token' => $csrf($ja), 'status' => 'Diterima']);
    $cek($satu("SELECT status_verifikasi FROM srp2_registrations WHERE id={$rid}") === 'Diterima', 'PRASYARAT: keputusan ulang tersimpan (Diterima)');
    $cek((int) $satu($audit) === 1, 'Keputusan Diterima tercatat di jejak audit (srp2_keputusan)');
} finally {
    $ids = $db->query("SELECT id FROM usr_users WHERE email LIKE '{$tag}\\_%@example.test'")->fetch_all();
    foreach ($ids as [$id]) {
        foreach ($db->query("SELECT id FROM srp2_registrations WHERE user_id={$id}")->fetch_all() as [$r]) {
            $db->query("DELETE FROM srp2_documents WHERE registration_id={$r}");
            $db->query("DELETE FROM srp2_registrations WHERE id={$r}");
        }
        $db->query("DELETE FROM usr_documents WHERE user_id={$id}");
    }
    $db->query("DELETE FROM srp2_certified_developers WHERE nama_perusahaan='PT {$TAG}'");
    $db->query("DELETE FROM usr_users WHERE email LIKE '{$tag}\\_%@example.test'");
    foreach ($ember as $k => $row) {
        $db->query("DELETE FROM sys_rate_limits WHERE limit_key='$k'");
        if ($row) { $st = $db->prepare('INSERT INTO sys_rate_limits (limit_key, window_started_at, failed_attempts) VALUES (?,?,?)'); $st->bind_param('ssi', $row['limit_key'], $row['window_started_at'], $row['failed_attempts']); $st->execute(); }
    }
    foreach ($jars as $f) { @unlink($f); }
}
echo "\nRINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal ? 1 : 0);
