<?php
/**
 * Uji impor Excel PSU (Admin_Psu::import_excel, fitur 235bd89, 1 Sep 2026).
 *
 *   php docs/engineering/uji_psu_import.php
 *
 * Butir "Import form Excel PSU" di daftar revisi dinas. Menempuh jalur admin sungguhan:
 * unduh template, isi lewat PhpSpreadsheet, unggah, lalu periksa DB, duplikat, penolakan
 * utuh untuk berkas yang memuat satu baris cacat, halaman publik, dan jejak audit.
 * Akun dan baris uji (awalan UJIPSU) dibuat dan dihapus sendiri.
 */
require dirname(__DIR__, 2) . '/vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
$BASE = rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/';
$env = [];
foreach (file(dirname(__DIR__, 2) . '/.env', FILE_IGNORE_NEW_LINES) as $l) { $l = trim($l); if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) continue; [$k, $v] = explode('=', $l, 2); if (!isset($env[trim($k)])) $env[trim($k)] = trim($v); }
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$tag = 'UJIPSU' . bin2hex(random_bytes(3));
$sandi = 'Pp1#' . bin2hex(random_bytes(5));
$email = 'uji_psu_' . strtolower($tag) . '@example.test';
$total = 0; $gagal = 0; $tmp = [];
$cek = function ($ok, $l) use (&$total, &$gagal) { $total++; if (!$ok) $gagal++; echo ($ok ? '  OK    ' : '  GAGAL ') . $l . "\n"; };
$jar = tempnam(sys_get_temp_dir(), 'psu');
$http = function ($path, $post = NULL, $raw = FALSE) use ($BASE, $jar) {
    $ch = curl_init($BASE . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60]);
    if ($post !== NULL) curl_setopt($ch, CURLOPT_POSTFIELDS, $raw ? $post : http_build_query($post));
    $b = (string) curl_exec($ch); $info = curl_getinfo($ch); curl_close($ch); return [$b, $info];
};
$csrf = function () use ($jar) { foreach (file($jar) as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') return $p[6]; } return ''; };
$jumlah = function () use ($db, $tag) { return (int) $db->query("SELECT COUNT(*) FROM psu_serah_terima WHERE nama_perumahan LIKE '{$tag}%'")->fetch_row()[0]; };
$xlsx = function (array $rows) use (&$tmp) {
    $book = IOFactory::load(dirname(__DIR__, 2) . '/application/templates/template_import_psu.xlsx');
    $sheet = $book->getSheetByName('Data PSU');
    $hr = NULL; for ($r = 1; $r <= 10 && !$hr; $r++) for ($c = 1; $c <= 20; $c++) if (strtolower(trim((string) $sheet->getCellByColumnAndRow($c, $r)->getValue())) === 'nama_perumahan') { $hr = $r; break; }
    $cols = []; for ($c = 1; $c <= 20; $c++) { $n = strtolower(trim((string) $sheet->getCellByColumnAndRow($c, $hr)->getValue())); if ($n !== '') $cols[$n] = $c; }
    for ($r = $hr + 1; $r <= $sheet->getHighestDataRow(); $r++) foreach ($cols as $c) $sheet->setCellValueByColumnAndRow($c, $r, NULL);
    foreach (array_values($rows) as $i => $row) foreach ($row as $k => $v) $sheet->setCellValueByColumnAndRow($cols[$k], $hr + 1 + $i, $v);
    $f = tempnam(sys_get_temp_dir(), 'psux') . '.xlsx'; IOFactory::createWriter($book, 'Xlsx')->save($f); $tmp[] = $f; return $f;
};
$impor = function ($file) use ($http, $csrf) {
    $http('Admin_Psu');
    [$b] = $http('Admin_Psu/import_excel', ['csrf_kpkp_token' => $csrf(), 'file_excel' => new CURLFile($file, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'psu.xlsx')], TRUE);
    return html_entity_decode(strip_tags($b));
};
try {
    $h = password_hash($sandi, PASSWORD_BCRYPT);
    $st = $db->prepare("INSERT INTO usr_users (name,email,password,role,status,profile_completed,email_verified_at,password_changed_at,password_expires_at,created_at) VALUES ('Uji PSU',?,?,'admin','active',1,NOW(),NOW(),DATE_ADD(NOW(),INTERVAL 90 DAY),NOW())");
    $st->bind_param('ss', $email, $h); $st->execute(); $uid = $db->insert_id;
    $http('Auth/login'); $http('Auth/do_login', ['email' => $email, 'password' => $sandi, 'csrf_kpkp_token' => $csrf()]);
    [$b] = $http('Admin_Psu'); $cek(strpos($b, 'Admin_Psu/import_excel') !== FALSE, 'Admin membuka layar PSU dengan formulir impor');
    [$b, $i] = $http('Admin_Psu/template_excel');
    $cek($i['http_code'] === 200 && substr($b, 0, 2) === 'PK', 'Template XLSX bisa diunduh');

    $kab = $db->query("SELECT nama FROM kabupaten ORDER BY id LIMIT 1")->fetch_row()[0];
    $baik = [
        ['nama_perumahan' => "{$tag} Griya Satu", 'nama_pengembang' => 'PT Uji Satu', 'kabupaten_kota' => $kab, 'kode_asosiasi' => 'REI', 'status_serah_terima' => 'sudah diserahkan', 'tanggal_serah_terima' => '2026-08-01', 'tampil_di_publik' => 'Ya'],
        ['nama_perumahan' => "{$tag} Griya Dua", 'nama_pengembang' => 'PT Uji Dua', 'kabupaten_kota' => $kab, 'status_serah_terima' => 'proses_verifikasi', 'tampil_di_publik' => 'Tidak'],
    ];
    $pesan = $impor($xlsx($baik));
    $cek($jumlah() === 2, 'Dua baris sah tersimpan (pesan: ' . (preg_match('/Import selesai[^.]*\./', $pesan, $m) ? $m[0] : substr(trim(preg_replace('/\s+/', ' ', $pesan)), 0, 80)) . ')');
    $r = $db->query("SELECT * FROM psu_serah_terima WHERE nama_perumahan='{$tag} Griya Satu'")->fetch_assoc();
    $cek($r && $r['status_serah_terima'] === 'sudah_diserahkan' && $r['tanggal_serah_terima'] === '2026-08-01' && $r['asosiasi'] === 'rei' && (int) $r['status_aktif'] === 1 && (int) $r['kabupaten_id'] > 0,
        'Isi baris terpetakan benar (status, tanggal, asosiasi, wilayah, tampil publik)');

    $impor($xlsx($baik));
    $cek($jumlah() === 2, 'Impor ulang berkas yang sama: duplikat dilewati, tidak bertambah');

    $cacat = [['nama_perumahan' => "{$tag} Griya Tiga", 'nama_pengembang' => 'PT Uji Tiga', 'status_serah_terima' => 'ngawur', 'tampil_di_publik' => 'Ya'],
              ['nama_perumahan' => "{$tag} Griya Empat", 'nama_pengembang' => 'PT Uji Empat', 'status_serah_terima' => 'belum diserahkan', 'tampil_di_publik' => 'Ya']];
    $pesan = $impor($xlsx($cacat));
    $cek($jumlah() === 2 && stripos($pesan, 'status_serah_terima tidak valid') !== FALSE, 'Satu baris cacat: SELURUH berkas ditolak dengan pesan per baris, nol baris baru');

    $pub = html_entity_decode($http('Psu')[0]);
    $cek(strpos($pub, "{$tag} Griya Satu") !== FALSE && strpos($pub, "{$tag} Griya Dua") === FALSE, 'Halaman publik PSU menampilkan baris Tampil=Ya saja');

    $cek((int) $db->query("SELECT COUNT(*) FROM sys_jejak_audit WHERE aksi='psu_diimpor_excel' AND actor_id={$uid}")->fetch_row()[0] === 1, 'Impor dicatat sekali di jejak audit (impor duplikat & cacat tidak tercatat)');
} finally {
    $db->query("DELETE FROM psu_serah_terima WHERE nama_perumahan LIKE '{$tag}%'");
    $db->query("DELETE FROM usr_users WHERE email='" . $db->real_escape_string($email) . "'");
    foreach ($tmp as $f) @unlink($f); @unlink($jar);
    echo "pembersihan selesai\n";
}
echo "\n$total cek, $gagal gagal\n"; exit($gagal ? 1 : 0);
