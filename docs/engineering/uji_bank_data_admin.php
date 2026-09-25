<?php
/**
 * Uji Bank Data admin (Admin_Bank_Data, migrasi 063).
 *
 *   php docs/engineering/uji_bank_data_admin.php
 *
 * Daftar revisi dinas 23 Sep 2026: admin mengunggah PDF Buku Data dan Statistika yang tampil
 * sebagai kartu di tab Bank Data. Dijaga: unggah PDF sah, PDF ber-JavaScript dan berkas bukan
 * PDF ditolak, kartu publik + pembaca, sembunyikan, hapus berkas, audit, dan akses non-admin.
 * Akun, baris, dan berkas uji dibuat dan dihapus sendiri.
 */
$BASE = rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/';
$root = dirname(__DIR__, 2);
$env = [];
foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES) as $l) { $l = trim($l); if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) continue; [$k, $v] = explode('=', $l, 2); if (!isset($env[trim($k)])) $env[trim($k)] = trim($v); }
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$tag = 'UJIBD' . strtoupper(bin2hex(random_bytes(3)));
$sandi = 'Bd1#' . bin2hex(random_bytes(5));
$total = 0; $gagal = 0; $jars = []; $tmp = [];
$cek = function ($ok, $l) use (&$total, &$gagal) { $total++; if (!$ok) $gagal++; echo ($ok ? '  OK    ' : '  GAGAL ') . $l . "\n"; };
$akun = function ($role) use ($db, $tag, $sandi) {
    $e = strtolower($tag) . "_{$role}@example.test"; $h = password_hash($sandi, PASSWORD_BCRYPT);
    $st = $db->prepare("INSERT INTO usr_users (name,email,password,role,status,profile_completed,email_verified_at,password_changed_at,password_expires_at,created_at) VALUES ('Uji BD',?,?,?,'active',1,NOW(),NOW(),DATE_ADD(NOW(),INTERVAL 90 DAY),NOW())");
    $st->bind_param('sss', $e, $h, $role); $st->execute(); return $e;
};
$http = function ($jar, $path, $post = NULL, $multi = FALSE) use ($BASE) {
    $ch = curl_init($BASE . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60]);
    if ($post !== NULL) curl_setopt($ch, CURLOPT_POSTFIELDS, $multi ? $post : http_build_query($post));
    $b = (string) curl_exec($ch); $c = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch); return [html_entity_decode($b), $c];
};
$csrf = function ($jar) { foreach (file($jar) as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') return $p[6]; } return ''; };
$login = function ($email) use ($http, $csrf, $sandi, &$jars) { $j = tempnam(sys_get_temp_dir(), 'bd'); $jars[] = $j; $http($j, 'Auth/login'); $http($j, 'Auth/do_login', ['email' => $email, 'password' => $sandi, 'csrf_kpkp_token' => $csrf($j)]); return $j; };
$pdf = function ($isi_tambahan = '') use (&$tmp) {
    $obj = ["1 0 obj << /Type /Catalog /Pages 2 0 R {$isi_tambahan} >> endobj", '2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj', '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 200 200] >> endobj'];
    $d = "%PDF-1.4\n"; $off = [];
    foreach ($obj as $o) { $off[] = strlen($d); $d .= $o . "\n"; }
    $x = strlen($d); $d .= "xref\n0 " . (count($obj) + 1) . "\n0000000000 65535 f \n";
    foreach ($off as $o) { $d .= sprintf("%010d 00000 n \n", $o); }
    $d .= 'trailer << /Size ' . (count($obj) + 1) . " /Root 1 0 R >>\nstartxref\n{$x}\n" . '%%' . "EOF\n";
    $f = tempnam(sys_get_temp_dir(), 'bdpdf') . '.pdf'; file_put_contents($f, $d); $tmp[] = $f; return $f;
};
$unggah = function ($jar, $file, $judul, $jenis = 'buku_data', $mime = 'application/pdf') use ($http, $csrf) {
    $http($jar, 'Admin_Bank_Data');
    return $http($jar, 'Admin_Bank_Data/simpan', ['csrf_kpkp_token' => $csrf($jar), 'jenis' => $jenis, 'judul' => $judul, 'deskripsi' => 'Deskripsi ' . $judul, 'urutan' => '1', 'berkas_pdf' => new CURLFile($file, $mime, 'dok.pdf')], TRUE);
};
$baris = function ($judul) use ($db) { $st = $db->prepare('SELECT * FROM sf_bank_data_dokumen WHERE judul=?'); $st->bind_param('s', $judul); $st->execute(); return $st->get_result()->fetch_assoc(); };
try {
    echo "=== UJI BANK DATA ADMIN ===\n";
    $cek($db->query("SHOW TABLES LIKE 'sf_bank_data_dokumen'")->num_rows === 1, 'Tabel sf_bank_data_dokumen ada (migrasi 063)');
    $ja = $login($akun('admin'));
    $unggah($ja, $pdf(), "{$tag} Buku");
    $r = $baris("{$tag} Buku");
    $cek($r && (int) $r['aktif'] === 1 && is_file($root . '/' . $r['berkas']) && preg_match('#^assets/dokumen/unggahan/buku_data-[a-f0-9]{16}\.pdf$#', $r['berkas']), 'PDF sah tersimpan dengan nama acak di folder unggahan');
    $cek((int) $db->query("SELECT COUNT(*) FROM sys_jejak_audit WHERE aksi='bank_data_diunggah' AND objek_id='" . (int) ($r['id'] ?? 0) . "'")->fetch_row()[0] === 1, 'Unggahan tercatat di jejak audit');

    $unggah($ja, $pdf('/OpenAction << /S /JavaScript /JS (app.alert(1)) >>'), "{$tag} Jahat");
    $cek($baris("{$tag} Jahat") === NULL, 'PDF ber-JavaScript/aksi otomatis ditolak');
    $palsu = tempnam(sys_get_temp_dir(), 'bdx') . '.pdf'; file_put_contents($palsu, "<?php echo 'x'; ?>"); $tmp[] = $palsu;
    $unggah($ja, $palsu, "{$tag} Palsu");
    $cek($baris("{$tag} Palsu") === NULL, 'Berkas bukan PDF yang dinamai .pdf ditolak');

    $jp = tempnam(sys_get_temp_dir(), 'bdp'); $jars[] = $jp;
    [$tab] = $http($jp, 'tab/bankdata');
    $cek(strpos($tab, "{$tag} Buku") !== FALSE && strpos($tab, 'Dokumen/lihat/' . $r['id']) !== FALSE, 'Kartu dokumen tampil di tab Bank Data publik');
    [$lihat, $kode] = $http($jp, 'Dokumen/lihat/' . $r['id']);
    $cek($kode === 200 && strpos($lihat, $r['berkas']) !== FALSE, 'Pembaca dokumen publik membuka PDF unggahan');

    $http($ja, 'Admin_Bank_Data/ubah_status/' . $r['id'], ['csrf_kpkp_token' => $csrf($ja)]);
    [$tab2] = $http($jp, 'tab/bankdata');
    [, $kode2] = $http($jp, 'Dokumen/lihat/' . $r['id']);
    $cek(strpos($tab2, "{$tag} Buku") === FALSE && $kode2 === 404, 'Dokumen yang disembunyikan hilang dari publik (kartu dan pembaca 404)');

    $jw = $login($akun('warga'));
    $jml = (int) $db->query("SELECT COUNT(*) FROM sf_bank_data_dokumen")->fetch_row()[0];
    $unggah($jw, $pdf(), "{$tag} Warga");
    $cek((int) $db->query("SELECT COUNT(*) FROM sf_bank_data_dokumen")->fetch_row()[0] === $jml, 'Warga TIDAK bisa mengunggah dokumen Bank Data');

    $berkas = $root . '/' . $r['berkas'];
    $http($ja, 'Admin_Bank_Data/hapus/' . $r['id'], ['csrf_kpkp_token' => $csrf($ja)]);
    clearstatcache(); // is_file() sebelumnya tersimpan di stat cache PHP
    $cek($baris("{$tag} Buku") === NULL && ! is_file($berkas), 'Hapus membuang baris dan berkasnya');
} finally {
    foreach ($db->query("SELECT berkas FROM sf_bank_data_dokumen WHERE judul LIKE '{$tag}%'")->fetch_all() as $b) { @unlink($root . '/' . $b[0]); }
    $db->query("DELETE FROM sf_bank_data_dokumen WHERE judul LIKE '{$tag}%'");
    $db->query("DELETE FROM usr_users WHERE email LIKE '" . strtolower($tag) . "_%@example.test'");
    foreach (array_merge($jars, $tmp) as $f) { @unlink($f); }
}
echo "\nRINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal ? 1 : 0);
