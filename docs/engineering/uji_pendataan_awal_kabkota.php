<?php
/**
 * Uji layar Pendataan Awal Warga untuk admin kab/kota (Admin_Kabkota::pendataan_awal).
 *
 *   php docs/engineering/uji_pendataan_awal_kabkota.php
 *
 * Daftar revisi dinas 23 Sep 2026: "langkah ketiga harus tersimpan, 4 opsional dan bisa dipantau
 * admin". Dijaga: draft ber-rekomendasi-awal di wilayah sendiri tampil beserta programnya; wilayah
 * lain, draft tanpa rekomendasi awal, dan pengajuan terkirim TIDAK tampil; identitas mengikuti
 * sakelar B2; peran lain tidak bisa membuka layar ini. Kolom *_ciphertext diisi teks polos:
 * Encryption_lib::decrypt() mengembalikan teks yang bukan format terenkripsi apa adanya, jadi
 * uji tidak butuh kunci. Akun dan baris uji dibuat dan dihapus sendiri.
 */
$BASE = rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/';
$root = dirname(__DIR__, 2);
$env = [];
foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES) as $l) { $l = trim($l); if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) continue; [$k, $v] = explode('=', $l, 2); if (!isset($env[trim($k)])) $env[trim($k)] = trim($v); }
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$tag = 'ujipaw' . bin2hex(random_bytes(3));
$sandi = 'Pa1#' . bin2hex(random_bytes(5));
$total = 0; $gagal = 0; $jars = []; $users = []; $asesmen = [];
$cek = function ($ok, $l) use (&$total, &$gagal) { $total++; if (!$ok) $gagal++; echo ($ok ? '  OK    ' : '  GAGAL ') . $l . "\n"; };
$akun = function ($role, $kab = NULL) use ($db, $tag, $sandi, &$users) {
    $e = "{$tag}_{$role}_" . mt_rand(1000, 9999) . '@example.test'; $h = password_hash($sandi, PASSWORD_BCRYPT);
    $st = $db->prepare("INSERT INTO usr_users (name,email,password,role,kabupaten_id,status,profile_completed,email_verified_at,password_changed_at,password_expires_at,created_at) VALUES ('Uji PAw',?,?,?,?,'active',1,NOW(),NOW(),DATE_ADD(NOW(),INTERVAL 90 DAY),NOW())");
    $st->bind_param('sssi', $e, $h, $role, $kab); $st->execute(); $users[] = $db->insert_id; return [$db->insert_id, $e];
};
$draft = function ($uid, $kab, $status, $step, $matriks) use ($db, &$asesmen) {
    $st = $db->prepare("INSERT INTO sf_penilaian_perumahan (user_id,kabupaten_id,assessment_track,status,current_step,version_no,lock_version,source_mode,preliminary_matrix_ciphertext,created_at,updated_at) VALUES (?,?,'candidate_land',?,?,1,1,'simulation',?,NOW(),NOW())");
    $st->bind_param('iisss', $uid, $kab, $status, $step, $matriks); $st->execute(); $asesmen[] = $db->insert_id; return $db->insert_id;
};
$http = function ($jar, $path, $post = NULL) use ($BASE) {
    $ch = curl_init($BASE . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 30]);
    if ($post !== NULL) curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    $b = (string) curl_exec($ch); $u = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL); curl_close($ch); return [html_entity_decode($b), $u];
};
$csrf = function ($jar) { foreach (file($jar) as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') return $p[6]; } return ''; };
$login = function ($email) use ($http, $csrf, $sandi, &$jars) { $j = tempnam(sys_get_temp_dir(), 'paw'); $jars[] = $j; $http($j, 'Auth/login'); $http($j, 'Auth/do_login', ['email' => $email, 'password' => $sandi, 'csrf_kpkp_token' => $csrf($j)]); return $j; };
try {
    echo "=== UJI PENDATAAN AWAL KAB/KOTA ===\n";
    [$kabA, $kabB] = array_map(fn($r) => (int) $r[0], $db->query("SELECT id FROM kabupaten ORDER BY id LIMIT 2")->fetch_all());
    [, $eAdm] = $akun('admin_kabkota', $kabA);
    [$uW1] = $akun('warga'); [$uW2] = $akun('warga'); [$uW3] = $akun('warga'); [$uW4] = $akun('warga');
    $prog = "Program {$tag}";
    $mat = json_encode(['items' => [['program_name' => $prog]]]);
    $idTampil = $draft($uW1, $kabA, 'draft', 'preliminary_recommendation', $mat);
    $idLain   = $draft($uW2, $kabB, 'draft', 'preliminary_recommendation', json_encode(['items' => [['program_name' => "Lain {$tag}"]]]));
    $idKosong = $draft($uW3, $kabA, 'draft', 'housing_family', NULL);
    $idKirim  = $draft($uW4, $kabA, 'submitted', 'review', json_encode(['items' => [['program_name' => "Kirim {$tag}"]]]));

    $j = $login($eAdm);
    [$b, $u] = $http($j, 'Admin_Kabkota/pendataan_awal');
    $cek(strpos($u, 'pendataan_awal') !== FALSE && strpos($b, 'Pendataan Awal') !== FALSE, 'Admin kab/kota membuka layar Pendataan Awal Warga');
    $cek(strpos($b, $prog) !== FALSE, 'Draft ber-rekomendasi-awal di wilayahnya tampil beserta programnya');
    $cek(strpos($b, 'Berhenti di hasil rekomendasi awal') !== FALSE, 'Posisi terakhir warga dijelaskan');
    $cek(strpos($b, "Lain {$tag}") === FALSE, 'Draft wilayah lain TIDAK tampil');
    $cek(strpos($b, "Kirim {$tag}") === FALSE, 'Pengajuan yang sudah dikirim TIDAK tampil di sini');
    $cek(strpos($b, 'Warga Contoh ' . str_pad((string) $idKosong, 3, '0', STR_PAD_LEFT)) === FALSE, 'Draft tanpa rekomendasi awal TIDAK tampil');
    $b2 = trim((string) (preg_match("/\['identitas_warga_kabkota'\]\s*=\s*'([^']+)'/", file_get_contents($root . '/application/config/kebijakan_data.php'), $mb) ? $mb[1] : ''));
    $tersamar = strpos($b, 'Warga Contoh ' . str_pad((string) $idTampil, 3, '0', STR_PAD_LEFT)) !== FALSE;
    $cek($b2 === 'menunggu_keputusan' ? $tersamar : ! $tersamar, "Identitas mengikuti sakelar B2 ($b2)");

    [, $eW] = $akun('warga');
    [$bw, $uw] = $http($login($eW), 'Admin_Kabkota/pendataan_awal');
    $cek(strpos($bw, $prog) === FALSE, 'Warga TIDAK bisa membuka layar ini');
} finally {
    foreach ($asesmen as $id) $db->query("DELETE FROM sf_penilaian_perumahan WHERE id=" . (int) $id);
    foreach ($users as $id) $db->query("DELETE FROM usr_users WHERE id=" . (int) $id);
    $db->query("DELETE FROM usr_users WHERE email LIKE '{$tag}_%@example.test'");
    foreach ($jars as $jj) @unlink($jj);
}
echo "\nRINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal ? 1 : 0);
