<?php
require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php'; // lokasi .env (luar akar dulu)
date_default_timezone_set('Asia/Jakarta');
/**
 * Uji buka kunci laporan Rekam Data (permintaan dinas 30 Okt 2026, keputusan "keduanya bisa buka kunci"):
 * kabupaten menarik kembali laporan terkirim yang BELUM diterima (kembali ke draft), dan provinsi
 * (super admin, Admin Bidang sesuai bidangnya) membuka laporan yang SUDAH diterima dengan alasan wajib.
 *
 *   php docs/engineering/uji_buka_kunci_rekam.php
 *
 * Tahun sentinel 2097 dan akun uji_bk_* sendiri; jejaknya dihapus di akhir.
 */
define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('TAHUN', 2097);

$total = 0; $gagal = 0;
function cek($ok, $label) { global $total, $gagal; $total++; echo ($ok ? '  OK    ' : '  GAGAL ') . $label . "\n"; if ( ! $ok) { $gagal++; } return (bool) $ok; }
function wajib($ok, $label) { if ( ! cek($ok, $label)) { fwrite(STDERR, "Berhenti: prasyarat gagal.\n"); bersihkan(); exit(1); } }

$env = [];
foreach (file(env_berkas_path(APP_ROOT), FILE_IGNORE_NEW_LINES) as $l) {
    if (strpos($l, '=') === FALSE || ltrim($l)[0] === '#') { continue; }
    [$k, $v] = explode('=', $l, 2);
    if ( ! isset($env[trim($k)])) { $env[trim($k)] = trim($v, " \t\"'"); }
}
$db = new mysqli($env['DB_HOST'] ?? 'localhost', $env['DB_USER'] ?? 'root', $env['DB_PASS'] ?? '', $env['DB_NAME'] ?? 'klinikpkp');
function q($sql, $p = []) {
    global $db;
    $st = $db->prepare($sql);
    if ($p) { $st->bind_param(str_repeat('s', count($p)), ...$p); }
    $st->execute();
    $r = $st->get_result();
    return $r ? $r->fetch_assoc() : NULL;
}

$jars = []; $mulai = date('Y-m-d H:i:s', time() - 1);
function jar($n) { global $jars; return $jars[$n] ??= tempnam(sys_get_temp_dir(), 'ujibk_'); }
function http($n, $path, $post = NULL) {
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_COOKIEJAR => jar($n), CURLOPT_COOKIEFILE => jar($n),
        CURLOPT_FOLLOWLOCATION => TRUE, CURLOPT_TIMEOUT => 60]);
    if ($post !== NULL) { curl_setopt($ch, CURLOPT_POST, TRUE); curl_setopt($ch, CURLOPT_POSTFIELDS, $post); }
    $body = (string) curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body];
}
function csrf($n, $path) { return preg_match('/name="csrf_kpkp_token" value="([^"]+)"/', http($n, $path)['body'], $m) ? $m[1] : ''; }
function login($n, $email, $sandi) {
    $t = csrf($n, 'Auth/login');
    $ch = curl_init(BASE_URL . '/Auth/do_login');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_COOKIEJAR => jar($n), CURLOPT_COOKIEFILE => jar($n),
        CURLOPT_POST => TRUE, CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest'],
        CURLOPT_POSTFIELDS => http_build_query(['csrf_kpkp_token' => $t, 'email' => $email, 'password' => $sandi])]);
    $j = json_decode((string) curl_exec($ch), TRUE);
    curl_close($ch);
    return ($j['status'] ?? '') === 'success';
}
/** POST lewat token dari halaman yang memuat form, balikan halaman sesudah redirect. */
function kirim($n, $halaman, $aksi, $laporan, $catatan = NULL) {
    $isi = ['csrf_kpkp_token' => csrf($n, $halaman), 'laporan_id' => $laporan];
    if ($catatan !== NULL) { $isi['catatan_admin'] = $catatan; }
    return http($n, $aksi, $isi)['body'];
}
function lap($id) { return q('SELECT status, reviewed_at, catatan_admin, submitted_at FROM rd_laporan WHERE id = ?', [$id]); }
function buat($domain, $kab, $tw, $diterima) {
    global $db;
    $db->query(sprintf("INSERT INTO rd_laporan (domain, kabupaten_id, tahun, triwulan, status, submitted_at, reviewed_at) VALUES ('%s', %d, %d, %d, 'terkirim', NOW(), %s)",
        $domain, $kab, TAHUN, $tw, $diterima ? 'NOW()' : 'NULL'));
    return (int) $db->insert_id;
}
function audit($pola, $aksi) {
    return (bool) q("SELECT 1 AS ada FROM sys_jejak_audit j JOIN usr_akun a ON a.id = j.pelaku_id WHERE a.email LIKE ? AND j.aksi = ? LIMIT 1", [$pola, $aksi]);
}
function bersihkan() {
    global $db, $jars, $mulai;
    $db->query('DELETE FROM rd_laporan WHERE tahun = ' . TAHUN);
    $db->query("DELETE j FROM sys_jejak_audit j JOIN usr_akun a ON a.id = j.pelaku_id WHERE a.email LIKE 'uji_bk_%'");
    $db->query("DELETE FROM sys_batas_laju WHERE jendela_mulai_at >= '" . $db->real_escape_string($mulai) . "'");
    $db->query("DELETE FROM usr_akun WHERE email LIKE 'uji_bk_%'");
    foreach ($jars as $f) { @unlink($f); }
}

echo "Uji buka kunci laporan Rekam Data\n";
[$KAB, $KAB2] = array_map(fn($r) => (int) $r[0], $db->query('SELECT id FROM kabupaten ORDER BY id LIMIT 2')->fetch_all());
wajib($KAB > 0 && $KAB2 > 0, 'Ada dua kabupaten untuk akun uji');
bersihkan();

$stamp = time(); $sandi = 'Uji#' . bin2hex(random_bytes(6)) . 'A1';
foreach (['kab' => "'admin_kabkota', NULL, $KAB", 'lain' => "'admin_kabkota', NULL, $KAB2", 'bper' => "'admin_bidang', 'perumahan', NULL",
          'bkaw' => "'admin_bidang', 'kawasan', NULL", 'super' => "'admin', NULL, NULL"] as $k => $peran) {
    $db->query(sprintf("INSERT INTO usr_akun (email, kata_sandi, peran, bidang_kode, kabupaten_id, nama, nama_pengguna, status, profil_lengkap) VALUES ('uji_bk_%s_%d@example.test', '%s', %s, 'Uji BK', 'uji_bk_%s_%d', 'active', 1)",
        $k, $stamp, $db->real_escape_string(password_hash($sandi, PASSWORD_DEFAULT)), $peran, $k, $stamp));
    wajib(login($k, "uji_bk_{$k}_{$stamp}@example.test", $sandi), "Login $k");
}

try {
    echo "\n[1] Kabupaten menarik kembali laporan yang belum diterima\n";
    $p1 = buat('perumahan', $KAB, 1, FALSE);
    $h = http('kab', 'Rekam_Perumahan/input?laporan=' . $p1 . '&langkah=review')['body'];
    cek(strpos($h, 'Rekam_Perumahan/tarik_kembali') !== FALSE && strpos($h, 'Buka lagi untuk diubah') !== FALSE, 'Wizard menampilkan tombol Buka lagi untuk diubah');
    cek(http('kab', 'Rekam_Perumahan/tarik_kembali')['code'] === 404, 'tarik_kembali lewat GET ditolak');
    kirim('lain', 'Rekam_Perumahan/input?tahun=' . TAHUN . '&triwulan=1', 'Rekam_Perumahan/tarik_kembali', $p1);
    cek(lap($p1)['status'] === 'terkirim', 'Kabupaten lain tidak bisa menarik laporan wilayah lain');
    $b = kirim('kab', 'Rekam_Perumahan/input?laporan=' . $p1 . '&langkah=review', 'Rekam_Perumahan/tarik_kembali', $p1);
    $l = lap($p1);
    cek($l['status'] === 'draft' && $l['submitted_at'] === NULL && strpos($b, 'Laporan dibuka lagi') !== FALSE, 'Laporan kembali ke draft dengan pesan');
    cek(audit('uji_bk_kab_%', 'rekam_ditarik'), 'Penarikan tercatat di jejak audit');

    $k1 = buat('kawasan', $KAB, 1, FALSE);
    $h = http('kab', 'Rekam_Kawasan?tahun=' . TAHUN . '&triwulan=1')['body'];
    cek(strpos($h, 'Rekam_Kawasan/tarik_kembali') !== FALSE, 'Layar Kawasan menampilkan tombol buka lagi');
    kirim('kab', 'Rekam_Kawasan?tahun=' . TAHUN . '&triwulan=1', 'Rekam_Kawasan/tarik_kembali', $k1);
    cek(lap($k1)['status'] === 'draft', 'Laporan kawasan kembali ke draft');

    $p2 = buat('perumahan', $KAB, 2, TRUE);
    $h = http('kab', 'Rekam_Perumahan/input?laporan=' . $p2 . '&langkah=review')['body'];
    cek(strpos($h, 'Rekam_Perumahan/tarik_kembali') === FALSE && strpos($h, 'sudah diterima provinsi') !== FALSE, 'Laporan diterima: tanpa tombol, ada keterangan');
    kirim('kab', 'Rekam_Perumahan/input?laporan=' . $p2 . '&langkah=review', 'Rekam_Perumahan/tarik_kembali', $p2);
    cek(lap($p2)['status'] === 'terkirim' && lap($p2)['reviewed_at'] !== NULL, 'Kabupaten tidak bisa menarik laporan yang sudah diterima');

    echo "\n[2] Provinsi membuka kunci laporan yang sudah diterima\n";
    cek(strpos(http('bper', 'Rekam_Tinjauan/detail/' . $p2)['body'], 'Rekam_Tinjauan/buka_kunci') !== FALSE, 'Detail Admin Bidang menampilkan form Buka kunci');
    kirim('bper', 'Rekam_Tinjauan/detail/' . $p2, 'Rekam_Tinjauan/buka_kunci', $p2, '');
    cek(lap($p2)['status'] === 'terkirim', 'Tanpa alasan ditolak');
    kirim('bkaw', 'Rekam_Tinjauan/detail/' . $p2, 'Rekam_Tinjauan/buka_kunci', $p2, 'Bukan bidang saya');
    cek(lap($p2)['status'] === 'terkirim', 'Admin Bidang Kawasan tidak bisa membuka laporan perumahan');
    $b = kirim('bper', 'Rekam_Tinjauan/detail/' . $p2, 'Rekam_Tinjauan/buka_kunci', $p2, 'Angka realisasi PK RTLH perlu dikoreksi');
    $l = lap($p2);
    cek($l['status'] === 'perlu_perbaikan' && $l['catatan_admin'] === 'Angka realisasi PK RTLH perlu dikoreksi' && strpos($b, 'Kunci laporan dibuka') !== FALSE,
        'Admin Bidang Perumahan membuka kunci: perlu perbaikan dengan catatan');

    $k2 = buat('kawasan', $KAB, 2, TRUE);
    cek(strpos(http('super', 'Admin_Rekam_Data/detail/' . $k2)['body'], 'Admin_Rekam_Data/buka_kunci') !== FALSE, 'Detail super admin menampilkan form Buka kunci');
    kirim('super', 'Admin_Rekam_Data/detail/' . $k2, 'Admin_Rekam_Data/buka_kunci', $k2, 'Luas penanganan salah ketik');
    cek(lap($k2)['status'] === 'perlu_perbaikan', 'Super admin membuka kunci laporan kawasan');
    cek(audit('uji_bk_super_%', 'rekam_dibuka_kunci'), 'Buka kunci tercatat di jejak audit');

    $p3 = buat('perumahan', $KAB, 3, FALSE);
    kirim('super', 'Admin_Rekam_Data/detail/' . $p3, 'Admin_Rekam_Data/buka_kunci', $p3, 'Coba');
    cek(lap($p3)['status'] === 'terkirim' && lap($p3)['reviewed_at'] === NULL, 'Buka kunci menolak laporan yang belum diterima (pakai Minta perbaikan)');
    cek(http('super', 'Admin_Rekam_Data/buka_kunci')['code'] === 404, 'buka_kunci lewat GET ditolak');
} finally {
    bersihkan();
}

cek(q('SELECT COUNT(*) AS n FROM rd_laporan WHERE tahun = ?', [TAHUN])['n'] == 0, 'Data uji dibersihkan');
echo "\nRINGKASAN: $total pemeriksaan, $gagal gagal\n";
exit($gagal > 0 ? 1 : 0);
