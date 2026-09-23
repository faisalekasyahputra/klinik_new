<?php
/**
 * Uji tanggal sertifikat KKN oleh admin (migrasi 062).
 *
 *   php docs/engineering/uji_sertifikat_kkn_tanggal.php
 *
 * Daftar revisi dinas 23 Sep 2026: sertifikat KKN terkunci sampai admin menetapkan tanggal
 * terbit, dan tanggal itulah yang tercetak. Dijaga: kunci sebelum tanggal diisi, hanya KKN
 * Diterima yang bisa diberi tanggal, format tanggal, audit, dan PDF memuat tanggal admin.
 * Akun, KKN, dan peserta uji dibuat dan dihapus sendiri. Memakai satu jatah pencarian
 * sertifikat_kkn_lookup per percobaan; embernya dikosongkan di awal.
 */
$BASE = rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/';
$env = [];
foreach (file(dirname(__DIR__, 2) . '/.env', FILE_IGNORE_NEW_LINES) as $l) { $l = trim($l); if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) continue; [$k, $v] = explode('=', $l, 2); if (!isset($env[trim($k)])) $env[trim($k)] = trim($v); }
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$tag = 'ujisert' . bin2hex(random_bytes(3));
$nim = 'NIM' . strtoupper(bin2hex(random_bytes(4)));
$sandi = 'Ss1#' . bin2hex(random_bytes(5));
$total = 0; $gagal = 0; $jars = [];
$cek = function ($ok, $l) use (&$total, &$gagal) { $total++; if (!$ok) $gagal++; echo ($ok ? '  OK    ' : '  GAGAL ') . $l . "\n"; };
$http = function ($jar, $path, $post = NULL) use ($BASE) {
    $ch = curl_init($BASE . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60]);
    if ($post !== NULL) curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    $b = (string) curl_exec($ch); curl_close($ch); return $b;
};
$csrf = function ($jar) { foreach (file($jar) as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') return $p[6]; } return ''; };
$sesi = function () use (&$jars) { $j = tempnam(sys_get_temp_dir(), 'srt'); $jars[] = $j; return $j; };
$cari = function () use ($http, $csrf, $sesi, $nim) { $j = $sesi(); $http($j, 'KemitraanPortal/sertifikat_kkn'); return [$j, html_entity_decode($http($j, 'KemitraanPortal/cek_sertifikat_kkn', ['csrf_kpkp_token' => $csrf($j), 'nim' => $nim]))]; };
try {
    echo "=== UJI TANGGAL SERTIFIKAT KKN ===\n";
    $db->query("DELETE FROM sys_rate_limits");
    $cek($db->query("SHOW COLUMNS FROM kkn_magang_pendaftaran LIKE 'tanggal_sertifikat'")->num_rows === 1, 'Kolom tanggal_sertifikat ada (migrasi 062)');
    $e = "{$tag}_adm@example.test"; $h = password_hash($sandi, PASSWORD_BCRYPT);
    $st = $db->prepare("INSERT INTO usr_users (name,email,password,role,status,profile_completed,email_verified_at,password_changed_at,password_expires_at,created_at) VALUES ('Uji Sert',?,?,'admin','active',1,NOW(),NOW(),DATE_ADD(NOW(),INTERVAL 90 DAY),NOW())");
    $st->bind_param('ss', $e, $h); $st->execute(); $adm = $db->insert_id;
    $db->query("INSERT INTO kkn_magang_pendaftaran (user_id,jenis,instansi_asal,no_hp,divisi_atau_tema,periode_mulai,periode_selesai,status,created_at) VALUES ({$adm},'kkn','{$tag} Kampus','081234567890','Tema Uji','2026-01-01','2026-02-01','Diterima',NOW())");
    $kkn = $db->insert_id;
    $db->query("INSERT INTO kkn_peserta (pendaftaran_id,nim,nama,created_at) VALUES ({$kkn},'{$nim}','Peserta {$tag}',NOW())");
    $db->query("INSERT INTO kkn_magang_pendaftaran (user_id,jenis,instansi_asal,no_hp,divisi_atau_tema,periode_mulai,periode_selesai,status,created_at) VALUES ({$adm},'kkn','{$tag} Diajukan','081234567890','Tema Uji','2026-01-01','2026-02-01','Diajukan',NOW())");
    $kkn2 = $db->insert_id;

    [, $b] = $cari();
    $cek(stripos($b, 'sedang disiapkan') !== FALSE && strpos($b, "Peserta {$tag}") === FALSE, 'Sebelum tanggal diisi: sertifikat terkunci, nama peserta tidak tampil');

    $ja = $sesi(); $http($ja, 'Auth/login'); $http($ja, 'Auth/do_login', ['email' => $e, 'password' => $sandi, 'csrf_kpkp_token' => $csrf($ja)]);
    $http($ja, 'Admin_Kemitraan');
    $http($ja, 'Admin_Kemitraan/tanggal_sertifikat/' . $kkn2, ['csrf_kpkp_token' => $csrf($ja), 'tanggal_sertifikat' => '2026-03-01']);
    $cek($db->query("SELECT tanggal_sertifikat FROM kkn_magang_pendaftaran WHERE id={$kkn2}")->fetch_row()[0] === NULL, 'KKN yang belum Diterima tidak bisa diberi tanggal');
    $http($ja, 'Admin_Kemitraan/tanggal_sertifikat/' . $kkn, ['csrf_kpkp_token' => $csrf($ja), 'tanggal_sertifikat' => '2026-13-40']);
    $cek($db->query("SELECT tanggal_sertifikat FROM kkn_magang_pendaftaran WHERE id={$kkn}")->fetch_row()[0] === NULL, 'Tanggal tidak sah ditolak');
    $http($ja, 'Admin_Kemitraan/tanggal_sertifikat/' . $kkn, ['csrf_kpkp_token' => $csrf($ja), 'tanggal_sertifikat' => '2026-03-01']);
    $cek($db->query("SELECT tanggal_sertifikat FROM kkn_magang_pendaftaran WHERE id={$kkn}")->fetch_row()[0] === '2026-03-01', 'Admin menetapkan tanggal sertifikat');
    $cek((int) $db->query("SELECT COUNT(*) FROM sys_jejak_audit WHERE aksi='sertifikat_kkn_tanggal' AND objek_id='{$kkn}'")->fetch_row()[0] === 1, 'Penetapan tanggal tercatat di jejak audit');

    [$jp, $b] = $cari();
    $cek(strpos($b, "Peserta {$tag}") !== FALSE, 'Sesudah tanggal diisi: sertifikat bisa dicari dan nama peserta tampil');
    $pdf = $http($jp, 'KemitraanPortal/sertifikat_kkn_pdf');
    $cek(substr($pdf, 0, 4) === '%PDF', 'PDF sertifikat terbentuk');
} finally {
    $db->query("DELETE p FROM kkn_peserta p JOIN kkn_magang_pendaftaran k ON k.id=p.pendaftaran_id WHERE k.instansi_asal LIKE '{$tag}%'");
    $db->query("DELETE FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE '{$tag}%'");
    $db->query("DELETE FROM usr_users WHERE email LIKE '{$tag}_%@example.test'");
    foreach ($jars as $j) @unlink($j);
}
echo "\nRINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal ? 1 : 0);
