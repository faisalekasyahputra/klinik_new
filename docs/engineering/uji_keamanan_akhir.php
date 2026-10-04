<?php
require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php'; // lokasi .env (luar akar dulu)
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Perbaikan keamanan akhir sebelum pentest (3 Okt 2026, branch fix/keamanan-akhir).
 *
 *   php docs/engineering/uji_keamanan_akhir.php
 *
 *   1. Panel "Kredensial Demo" tidak dirender di /login, modal masuk (beranda), dan syarat SRP2.
 *   2. `echo <email> | php index.php akun buat_superadmin`: hanya CLI (web 404), email ber-@ dan titik,
 *      Super Admin tanpa sandi; email yang sudah ada ditolak, tidak dinaikkan.
 *   3. Penautan Google pertama akun tanpa sandi mewajibkan sandi; gerbang memaksa membuatnya di Profil Saya.
 *   4. Migrasi 073 di DB sementara: menolak bila tidak ada Super Admin siap di luar akun demo, menonaktifkan
 *      akun demo tanpa menyentuh google_id/akun lain, down() memulihkan status saja; status di DB lokal.
 *   5. Klaim NIK tidak lagi memindahkan ikatan otomatis: ditinjau, ditolak/disetujui Super Admin, teraudit.
 *   6. Tebakan pihak ketiga tidak mengunci pemilik NIK; per akun tetap dibatasi; peringatan lintas akun.
 *   7. Cache dan batas laju: kata kunci SIKUMBANG, batas cache pencarian, cache_foto saat menulis,
 *      detail_perum, sebaran, OTP per IP, login atomik, NIK Profil Saya, pendaftaran tidak membedakan email.
 *
 * Bukti menggigit: merah terhadap kode 02c4cc7 (application/ dipulihkan sementara ke revisi itu).
 * Hanya DB lokal + SIMPERUM simulasi (fixture API-01/02/03, NIK berawalan 3399, bukan NIK warga).
 * Akun uji (@uji-akhir.test) dan ember batas laju dipinjam lalu dibersihkan/dikembalikan saat proses
 * berakhir (register_shutdown_function, juga bila suite mati di tengah). Tidak ada tembakan ke SIKUMBANG.
 */

define('APP_ROOT', dirname(__DIR__, 2));
define('BASE', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/');
define('BASEPATH', APP_ROOT . '/system/');
define('APPPATH', APP_ROOT . '/application/');
define('FCPATH', APP_ROOT . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'development');
if ( ! function_exists('log_message')) { function log_message() {} }

$GLOBALS['total'] = 0; $GLOBALS['gagal'] = 0;
function cek($kondisi, $label) {
    $GLOBALS['total']++;
    echo ($kondisi ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $kondisi) { $GLOBALS['gagal']++; }
    return (bool) $kondisi;
}

$env = [];
foreach (file(env_berkas_path(APP_ROOT), FILE_IGNORE_NEW_LINES) as $l) {
    $l = trim($l);
    if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) { continue; }
    [$k, $v] = explode('=', $l, 2);
    $k = trim($k); $v = trim($v, " \t\"'");
    if ( ! array_key_exists($k, $env)) { $env[$k] = $v; putenv("$k=$v"); }
}
mysqli_report(MYSQLI_REPORT_OFF);
if ( ! in_array(strtolower($env['DB_HOST'] ?? ''), ['localhost', '127.0.0.1', '::1'], TRUE) || ($env['SIMPERUM_MODE'] ?? '') !== 'simulation') {
    cek(FALSE, 'DB lokal dan SIMPERUM mode simulasi (suite ini menulis akun uji)');
    echo "RINGKASAN: {$GLOBALS['total']} pemeriksaan, {$GLOBALS['gagal']} gagal\n";
    exit(1);
}
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$db->set_charset('utf8mb4');
$db->query("SET time_zone = '+07:00'");
function satu($sql, array $p = []) {
    global $db;
    $st = $db->prepare($sql);
    if ($p) { $st->bind_param(str_repeat('s', count($p)), ...array_map(fn($x) => $x === NULL ? NULL : (string) $x, $p)); }
    $st->execute();
    $r = $st->get_result();
    return $r ? $r->fetch_assoc() : NULL;
}
function jalan($sql, array $p = []) {
    global $db;
    $st = $db->prepare($sql);
    if ($p) { $st->bind_param(str_repeat('s', count($p)), ...array_map(fn($x) => $x === NULL ? NULL : (string) $x, $p)); }
    $st->execute();
    return $st->insert_id ?: $st->affected_rows;
}

require_once APPPATH . 'libraries/Encryption_lib.php';
$enc = new Encryption_lib();
$h = fn($nik) => $enc->deterministic_hash($nik);

$TAG = 'ujiakhir' . bin2hex(random_bytes(3));
$SANDI = 'Uji#' . bin2hex(random_bytes(5)) . 'A1';
$HASH = password_hash($SANDI, PASSWORD_BCRYPT);
$akun_uji = [];
function akun_baru($nama, array $kolom = []) {
    global $TAG, $HASH, $akun_uji;
    $email = $TAG . '_' . count($akun_uji) . '@uji-akhir.test';
    $isi = $kolom + ['nama' => $nama, 'email' => $email, 'kata_sandi' => $HASH, 'peran' => 'warga',
        'status' => 'active', 'profil_lengkap' => 1, 'sandi_diganti_at' => date('Y-m-d H:i:s'),
        'sandi_kedaluwarsa_at' => date('Y-m-d H:i:s', strtotime('+90 days')), 'created_at' => date('Y-m-d H:i:s')];
    $id = jalan('INSERT INTO usr_akun (' . implode(',', array_keys($isi)) . ') VALUES (' . implode(',', array_fill(0, count($isi), '?')) . ')', array_values($isi));
    $akun_uji[] = (int) $id;
    return [(int) $id, $email];
}

/* ------------------------------------------------------------------ HTTP */
function minta($jar, $path, $post = NULL, $ajax = FALSE) {
    $c = curl_init(BASE . $path);
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_FOLLOWLOCATION => TRUE, CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60, CURLOPT_HTTPHEADER => $ajax ? ['X-Requested-With: XMLHttpRequest'] : []]);
    if ($post !== NULL) {
        $post += ['csrf_kpkp_token' => token_csrf($jar)];
        curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $badan = (string) curl_exec($c);
    $hasil = ['kode' => curl_getinfo($c, CURLINFO_HTTP_CODE), 'url' => (string) curl_getinfo($c, CURLINFO_EFFECTIVE_URL),
        'badan' => html_entity_decode($badan, ENT_QUOTES, 'UTF-8')];
    curl_close($c); unset($c); // PHP 8: jar ditulis saat handle dilepas
    return $hasil;
}
function token_csrf($jar) {
    foreach (@file($jar) ?: [] as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') { return $p[6]; } }
    return '';
}
$jar_dibuat = [];
function jar() { global $jar_dibuat; return $jar_dibuat[] = tempnam(sys_get_temp_dir(), 'uja'); }
function masuk($email, $sandi) {
    $j = jar();
    minta($j, 'Auth/login');
    minta($j, 'Auth/do_login', ['email' => $email, 'password' => $sandi]);
    return $j;
}
function pesan($badan) {
    return preg_match('/data-kpkp-flash-notifications>(.*?)<\/script>/s', $badan, $m) ? (string) $m[1] : '';
}
$step = fn($b) => preg_match('/name="step" value="([a-z_]+)"/', $b, $m) ? $m[1] : '';

/** Jalankan php index.php ... di akar aplikasi; $env_tambah menimpa env proses (DB_NAME untuk DB sementara). */
function cli(array $argumen, $stdin = '', array $env_tambah = []) {
    $env = array_merge(getenv(), ['CI_ENV' => 'development'], $env_tambah);
    $p = proc_open(array_merge([PHP_BINARY, '-d', 'variables_order=EGPCS', 'index.php'], $argumen),
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipa, APP_ROOT, $env);
    fwrite($pipa[0], $stdin); fclose($pipa[0]);
    $keluar = stream_get_contents($pipa[1]) . stream_get_contents($pipa[2]);
    fclose($pipa[1]); fclose($pipa[2]);
    return [proc_close($p), $keluar];
}

/* ------------------------------------------------------------------ ember batas laju dipinjam */
$ember_asli = [];
function pinjam_ember($kunci) {
    global $ember_asli;
    if ( ! array_key_exists($kunci, $ember_asli)) { $ember_asli[$kunci] = satu('SELECT kunci, jendela_mulai_at, jumlah_gagal FROM sys_batas_laju WHERE kunci=?', [$kunci]); }
    jalan('DELETE FROM sys_batas_laju WHERE kunci=?', [$kunci]);
}
const IP_UJI = ['127.0.0.1', '::1', '0000000000000000/64'];
function ember_ip($policy) { foreach (IP_UJI as $ip) { pinjam_ember(hash('sha256', "$policy:ip:$ip")); } }
function ember_nik($policy, $nik) { global $h; pinjam_ember(hash('sha256', "$policy:nik:" . $h($nik))); }
function ember_akun($policy, $id) { pinjam_ember(hash('sha256', "$policy:account:$id")); }
function hitungan($kunci) { return (int) (satu('SELECT jumlah_gagal FROM sys_batas_laju WHERE kunci=?', [$kunci])['jumlah_gagal'] ?? 0); }

$audit_awal = (int) satu('SELECT COALESCE(MAX(id), 0) n FROM sys_jejak_audit')['n'];
$db_sementara = '';
$otp_berkas = [];
$berkas_otp = fn($e) => APPPATH . 'cache/otp_uji/' . sha1(strtolower($e)) . '.txt';

register_shutdown_function(function () {
    global $db, $akun_uji, $audit_awal, $ember_asli, $jar_dibuat, $db_sementara, $otp_berkas, $TAG;
    if ($akun_uji) {
        $daftar = implode(',', array_map('intval', $akun_uji));
        $teks = "'" . implode("','", $akun_uji) . "'";
        $klaim = implode(',', array_map('intval', array_column($db->query("SELECT id FROM sys_jejak_audit WHERE id > $audit_awal
            AND aksi = 'klaim_nik_diajukan' AND pelaku_id IN ($daftar)")->fetch_all(MYSQLI_ASSOC), 'id'))) ?: '0';
        $db->query("DELETE FROM sys_jejak_audit WHERE id > $audit_awal AND (pelaku_id IN ($daftar) OR (objek_tipe = 'usr_akun' AND objek_id IN ($teks))
            OR (objek_tipe = 'klaim_nik' AND objek_id IN ($klaim)) OR (objek_tipe = 'nik_ditebak'))");
        foreach (['sf_data_simperum', 'sf_penilaian_perumahan', 'sf_profil_warga'] as $t) { $db->query("DELETE FROM $t WHERE user_id IN ($daftar)"); }
        $db->query("DELETE FROM usr_akun WHERE id IN ($daftar)");
    }
    foreach ($ember_asli as $kunci => $baris) {
        jalan('DELETE FROM sys_batas_laju WHERE kunci=?', [$kunci]);
        if ($baris) { jalan('INSERT INTO sys_batas_laju (kunci, jendela_mulai_at, jumlah_gagal) VALUES (?,?,?)', array_values($baris)); }
    }
    if ($db_sementara !== '') { $db->query("DROP DATABASE IF EXISTS `$db_sementara`"); }
    foreach ($jar_dibuat as $f) { @unlink($f); }
    foreach ($otp_berkas as $f) { @unlink($f); }
    echo 'Akun uji tersisa: ' . (int) satu('SELECT COUNT(*) n FROM usr_akun WHERE email LIKE ?', [$TAG . '%'])['n'] . "\n";
    echo "RINGKASAN: {$GLOBALS['total']} pemeriksaan, {$GLOBALS['gagal']} gagal\n";
});

$NIK1 = '3399991508850001'; $LAHIR1 = '1985-08-15'; // API-01, SUGENG SINTETIS
$NIK2 = '3399995506900002'; $LAHIR2 = '1990-06-15'; // API-02, SRI SINTETIS
$NIK3 = '3399990101700003';                         // API-03, dipakai sebagai NIK terikat di Profil Saya
const DEMO = ['admin@klinikpkp.jatengprov.go.id', 'warga@example.com', 'pengembang@example.com', 'universitas@example.com',
    'mahasiswa@example.com', 'adminkabkota@example.com', 'adminbidang@example.com', 'adminbidang.kawasan@example.com',
    'adminbidang.pertanahan@example.com', 'adminbidang.perencanaan@example.com', 'adminbidang.sekretariat@example.com', 'dev1@example.com'];

/* ===================================================================== 1 */
echo "== 1. Panel Kredensial Demo tidak dirender\n";
$j0 = jar();
foreach (['Auth/login' => 'halaman masuk', '' => 'beranda (modal masuk)', 'Pengembang/syarat' => 'syarat SRP2'] as $path => $ket) {
    $b = minta($j0, $path)['badan'];
    $demo = array_values(array_filter(DEMO, fn($e) => stripos($b, $e) !== FALSE));
    cek($b !== '' && stripos($b, 'Kredensial Demo') === FALSE && stripos($b, 'Password semua akun') === FALSE
        && strpos($b, 'data-demo-email') === FALSE && $demo === [], "Tanpa panel/email/sandi demo: $ket" . ($demo ? ' (ada: ' . implode(', ', $demo) . ')' : ''));
    if ($path === '') { cek(strpos($b, 'kpkp-login-modal') !== FALSE, 'Prasyarat: beranda memang memuat modal masuk'); }
}

/* ===================================================================== 2 */
echo "\n== 2. Perintah CLI akun buat_superadmin\n";
foreach (['akun/buat_superadmin', 'Akun/buat_superadmin'] as $p) { cek(minta(jar(), $p)['kode'] === 404, "Lewat web dijawab 404: $p"); }
$e_cli = "{$TAG}.pemilik@uji-akhir.test";
[$kode, $out] = cli(['akun', 'buat_superadmin'], $e_cli . "\n");
$baris = satu('SELECT id, peran, status, profil_lengkap, kata_sandi, google_id, email_verified_at, sandi_diganti_at, sandi_kedaluwarsa_at FROM usr_akun WHERE email=?', [$e_cli]);
cek($kode === 0 && $baris !== NULL, "CLI dengan email ber-@ dan titik membuat akun (exit $kode)");
if ($baris) { $akun_uji[] = (int) $baris['id']; }
cek($baris && $baris['peran'] === 'admin' && $baris['status'] === 'active' && (int) $baris['profil_lengkap'] === 1,
    'Akun CLI: peran admin, aktif, profil lengkap');
cek($baris && $baris['kata_sandi'] === NULL && $baris['google_id'] === NULL && $baris['email_verified_at'] === NULL,
    'Akun CLI: tanpa sandi, tanpa google_id, email belum terverifikasi');
cek($baris && (bool) satu("SELECT id FROM sys_jejak_audit WHERE id > ? AND aksi = 'superadmin_dibuat_cli' AND objek_id = ?", [$audit_awal, $baris['id']]),
    'Pembuatan tercatat di jejak audit');
[$kode2, $out2] = cli(['akun', 'buat_superadmin'], $e_cli . "\n");
cek($kode2 !== 0 && stripos($out2, 'DITOLAK') !== FALSE && (int) satu('SELECT COUNT(*) n FROM usr_akun WHERE email=?', [$e_cli])['n'] === 1,
    'Email yang sudah ada: ditolak dengan pesan jelas, tidak ada akun kedua');
[$W_ADA, $e_ada] = akun_baru('Uji Warga Sudah Ada');
[$kode3, $out3] = cli(['akun', 'buat_superadmin'], $e_ada . "\n");
cek($kode3 !== 0 && stripos($out3, 'DITOLAK') !== FALSE && satu('SELECT peran FROM usr_akun WHERE id=?', [$W_ADA])['peran'] === 'warga',
    'Akun lain yang sudah ada tidak dinaikkan diam-diam jadi Super Admin');
[$kode4] = cli(['akun', 'buat_superadmin'], "bukan-email\n");
cek($kode4 !== 0, 'Email tidak valid ditolak');
if ( ! $baris) { // kode lama tanpa perintah ini: buat padanannya supaya bagian berikut tetap berjalan
    [$id_cli] = akun_baru('Super Admin', ['email' => $e_cli, 'peran' => 'admin', 'kata_sandi' => NULL, 'sandi_diganti_at' => NULL,
        'sandi_kedaluwarsa_at' => date('Y-m-d H:i:s')]);
    $baris = ['id' => $id_cli];
}
$ID_CLI = (int) $baris['id'];

/* ===================================================================== 3 */
echo "\n== 3. Penautan Google pertama akun tanpa sandi mewajibkan sandi\n";
require_once BASEPATH . 'core/Common.php';
require_once BASEPATH . 'database/DB.php';
require_once BASEPATH . 'core/Model.php';
$CI_DB = DB(['dsn' => '', 'hostname' => $env['DB_HOST'], 'username' => $env['DB_USER'], 'password' => $env['DB_PASS'] ?? '',
    'database' => $env['DB_NAME'], 'dbdriver' => 'mysqli', 'char_set' => 'utf8mb4', 'dbcollat' => 'utf8mb4_unicode_ci',
    'db_debug' => FALSE, 'pconnect' => FALSE], TRUE);
$CI_DB->query("SET time_zone = '+07:00'");
$GLOBALS['UJI_CI'] = (object) ['db' => $CI_DB, 'load' => new class { public function database() {} public function library() {} public function model() {} }];
if ( ! function_exists('get_instance')) { function &get_instance() { return $GLOBALS['UJI_CI']; } }
$sumber = (string) file_get_contents(APPPATH . 'models/User_model.php');
$mulai = strpos($sumber, 'public function check_google_user(');
$badan = ''; $dalam = 0; $buka = FALSE;
foreach (array_slice(token_get_all('<?php ' . substr($sumber, (int) $mulai)), 1) as $t) {
    $teks = is_array($t) ? $t[1] : $t; $badan .= $teks;
    if ($teks === '{' || (is_array($t) && in_array($t[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], TRUE))) { $dalam++; $buka = TRUE; }
    if ($teks === '}') { $dalam--; if ($buka && $dalam === 0) { break; } }
}
$berkas = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'uji_akhir_' . bin2hex(random_bytes(4)) . '.php';
file_put_contents($berkas, "<?php\nclass Uji_Google_model extends CI_Model { " . $badan . " }\n");
require $berkas; unlink($berkas);
$model = new Uji_Google_model();
$g = 'ujiakhir' . bin2hex(random_bytes(6));
$hasil = $model->check_google_user(['google_id' => $g, 'nama' => 'Pemilik', 'email' => $e_cli, 'foto_profil' => 'https://example.test/f.png'], TRUE);
$b = satu('SELECT google_id, kata_sandi, email_verified_at, sandi_diganti_at, sandi_kedaluwarsa_at FROM usr_akun WHERE id=?', [$ID_CLI]);
cek(is_array($hasil) && $b['google_id'] === $g && $b['email_verified_at'] !== NULL && $b['kata_sandi'] === NULL,
    'Akun buatan CLI tertaut lewat email terverifikasi Google pada penautan pertama');
cek($b['sandi_diganti_at'] === NULL && $b['sandi_kedaluwarsa_at'] !== NULL && strtotime($b['sandi_kedaluwarsa_at']) <= time() + 5,
    'Sesudah tertaut: sandi wajib dibuat (kedaluwarsa sekarang)');
[$ID_TANPA, $e_tanpa] = akun_baru('Uji Tanpa Sandi Lain', ['peran' => 'admin', 'kata_sandi' => NULL, 'sandi_diganti_at' => NULL, 'sandi_kedaluwarsa_at' => NULL]);
$model->check_google_user(['google_id' => $g . 'b', 'nama' => 'X', 'email' => $e_tanpa, 'foto_profil' => ''], TRUE);
$b2 = satu('SELECT sandi_kedaluwarsa_at FROM usr_akun WHERE id=?', [$ID_TANPA]);
cek($b2['sandi_kedaluwarsa_at'] !== NULL && strtotime($b2['sandi_kedaluwarsa_at']) <= time() + 5,
    'Akun tanpa sandi apa pun (tanpa kedaluwarsa): penautan pertama tetap mewajibkan sandi');
// Sesi seperti sesudah callback Google: masuk sekali dengan sandi sementara (kedaluwarsa tetap), lalu sandi dicabut lagi.
jalan('UPDATE usr_akun SET kata_sandi=? WHERE id=?', [$HASH, $ID_CLI]);
ember_ip('login');
$j_cli = masuk($e_cli, $SANDI);
jalan('UPDATE usr_akun SET kata_sandi=NULL WHERE id=?', [$ID_CLI]);
$r = minta($j_cli, 'Admin_Users');
cek(strpos($r['url'], 'akun/profil') !== FALSE && strpos($r['url'], 'password_expired=1') !== FALSE, 'Gerbang memaksa ke Profil Saya untuk membuat sandi');
$baru = 'Baru#' . bin2hex(random_bytes(4)) . 'Z9';
$r = minta($j_cli, 'akun/update', ['name' => 'Super Admin Uji', 'phone' => '081234567890', 'password' => $baru, 'password_confirm' => $baru]);
$b3 = satu('SELECT kata_sandi, sandi_kedaluwarsa_at FROM usr_akun WHERE id=?', [$ID_CLI]);
cek($b3['kata_sandi'] !== NULL && password_verify($baru, $b3['kata_sandi']) && strtotime($b3['sandi_kedaluwarsa_at']) > time() + 86400,
    'Sandi pertama dibuat di Profil Saya tanpa sandi lama');
$r = minta($j_cli, 'Admin_Users');
cek($r['kode'] === 200 && strpos($r['url'], 'Admin_Users') !== FALSE, 'Sesudahnya panel admin terbuka');

/* ===================================================================== 4 */
echo "\n== 4. Migrasi 073 (DB sementara) dan keadaannya di DB lokal\n";
$db_sementara = 'uji073_' . bin2hex(random_bytes(3));
$ok = $db->query("CREATE DATABASE `$db_sementara` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
foreach (['usr_akun', 'sys_jejak_audit', 'migrations'] as $t) { $ok = $ok && $db->query("CREATE TABLE `$db_sementara`.`$t` LIKE `{$env['DB_NAME']}`.`$t`"); }
$ok = $ok && $db->query("INSERT INTO `$db_sementara`.migrations (version) VALUES (20260701000072)");
cek((bool) $ok, 'Prasyarat: DB sementara dengan usr_akun, sys_jejak_audit, migrations di 072');
$ds = "`$db_sementara`.usr_akun";
$tambah = function ($email, $peran, $status, $sandi, $google = NULL) use ($db, $ds) {
    $st = $db->prepare("INSERT INTO $ds (email, peran, status, kata_sandi, google_id, sesi_aktif_hash, sesi_aktif_at) VALUES (?,?,?,?,?,?,NOW())");
    $sesi = str_repeat('a', 64);
    $st->bind_param('ssssss', $email, $peran, $status, $sandi, $google, $sesi); $st->execute();
    return $db->insert_id;
};
$d_admin = $tambah('admin@klinikpkp.jatengprov.go.id', 'admin', 'restricted', $HASH, 'gdemo');
$d_warga = $tambah('warga@example.com', 'warga', 'active', $HASH);
$d_dev = $tambah('dev1@example.com', 'pengembang', 'restricted', $HASH);
$d_nyata = $tambah('warga.nyata@uji-akhir.test', 'warga', 'active', $HASH);
$tambah('admin.mati@uji-akhir.test', 'admin', 'nonaktif', $HASH);
$d_cli = $tambah('admin.cli@uji-akhir.test', 'admin', 'active', NULL); // buatan CLI, belum tertaut Google
$versi = fn() => (string) $db->query("SELECT MAX(version) v FROM `$db_sementara`.migrations")->fetch_row()[0];
$baca = fn($id) => $db->query("SELECT status, kata_sandi, google_id, sesi_aktif_hash FROM $ds WHERE id = " . (int) $id)->fetch_assoc();
[, $out] = cli(['migrate', 'ke', '20260701000073'], '', ['DB_NAME' => $db_sementara]);
cek($versi() === '20260701000072' && stripos($out, 'ditolak') !== FALSE && $baca($d_admin)['status'] === 'restricted' && $baca($d_admin)['kata_sandi'] !== NULL,
    'Tanpa Super Admin siap di luar akun demo (yang ada nonaktif, atau buatan CLI belum tertaut): 073 menolak, tidak ada yang berubah');
$db->query("UPDATE $ds SET google_id = 'gpemilik' WHERE id = " . (int) $d_cli);
[, $out] = cli(['migrate', 'ke', '20260701000073'], '', ['DB_NAME' => $db_sementara]);
cek($versi() === '20260701000073', 'Sesudah Super Admin tertaut Google: 073 terpasang');
$semua_demo = TRUE;
foreach ([$d_admin, $d_warga, $d_dev] as $id) { $x = $baca($id); $semua_demo = $semua_demo && $x['status'] === 'nonaktif' && $x['kata_sandi'] === NULL && $x['sesi_aktif_hash'] === NULL; }
cek($semua_demo, 'Akun demo (termasuk dev1@example.com): nonaktif, sandi NULL, sesi dicabut');
cek($baca($d_admin)['google_id'] === 'gdemo', 'google_id akun demo tidak disentuh');
cek($baca($d_nyata)['status'] === 'active' && $baca($d_nyata)['kata_sandi'] !== NULL && $baca($d_cli)['status'] === 'active', 'Akun di luar daftar demo tidak disentuh');
$snap = $db->query("SELECT detail_json FROM `$db_sementara`.sys_jejak_audit WHERE aksi = 'akun_demo_dinonaktifkan'")->fetch_row()[0] ?? '';
cek(strpos((string) $snap, 'status_sebelum') !== FALSE && stripos((string) $snap, '$2y$') === FALSE, 'Cuplikan status sebelumnya tersimpan di jejak audit, tanpa hash sandi');
[, $out] = cli(['migrate', 'ke', '20260701000072'], '', ['DB_NAME' => $db_sementara]);
cek($versi() === '20260701000072' && $baca($d_admin)['status'] === 'restricted' && $baca($d_warga)['status'] === 'active'
    && $baca($d_admin)['kata_sandi'] === NULL, 'down(): status dipulihkan dari cuplikan, sandi tetap kosong');
$db->query("DROP DATABASE IF EXISTS `$db_sementara`"); $db_sementara = '';
[, $status] = cli(['migrate', 'status']);
cek(preg_match('/akun demo nonaktif \(migrasi 073\): TERPASANG/', $status) === 1, 'DB lokal: Migrate::status melaporkan 073 TERPASANG');
$aktif = (int) satu("SELECT COUNT(*) n FROM usr_akun WHERE LOWER(email) IN ('" . implode("','", DEMO) . "') AND (status <> 'nonaktif' OR COALESCE(kata_sandi, '') <> '')")['n'];
cek($aktif === 0, "DB lokal: nol akun demo yang aktif atau bersandi (dapat $aktif)");

/* ===================================================================== 5 */
echo "\n== 5. Klaim NIK ditinjau Super Admin, tidak dipindahkan otomatis\n";
foreach ([$NIK1, $NIK2, $NIK3] as $n) {
    $terikat = (int) satu('SELECT COUNT(*) n FROM usr_akun WHERE nik_lookup_hash=?', [$h($n)])['n']
        + (int) satu('SELECT COUNT(*) n FROM sf_profil_warga WHERE nik_lookup_hash=?', [$h($n)])['n'];
    cek($terikat === 0, 'Prasyarat: fixture ' . substr($n, 0, 4) . '..' . substr($n, -4) . ' belum terikat');
    foreach (['warga_lookup', 'verifikasi_nik', 'verifikasi_nik_lintas'] as $p) { ember_nik($p, $n); }
}
$lookup = function ($j, $nik, $lahir) {
    ember_ip('warga_lookup');
    return minta($j, 'warga/pendataan', ['step' => 'find_data', 'action' => 'lookup', 'nik' => $nik, 'birth_date' => $lahir]);
};
$siapkan = function ($id) { foreach (['warga_lookup', 'warga_lookup_jam', 'warga_lookup_harian', 'verifikasi_nik', 'profil_nik'] as $p) { ember_akun($p, $id); } };
[$A, $e_a] = akun_baru('Uji Pemegang Lama Akhir', ['nik' => $enc->encrypt($NIK1), 'nik_lookup_hash' => $h($NIK1)]);
[$B, $e_b] = akun_baru('SUGENG SINTETIS');
[$ADM, $e_adm] = akun_baru('Admin Uji Akhir', ['peran' => 'admin']);
$siapkan($A); $siapkan($B);
$nik_a = fn() => satu('SELECT nik_lookup_hash FROM usr_akun WHERE id=?', [$A])['nik_lookup_hash'];
$j_b = masuk($e_b, $SANDI);
$r = $lookup($j_b, $NIK1, $LAHIR1);
cek(strpos(pesan($r['badan']), 'sedang ditinjau') !== FALSE && $step($r['badan']) === 'find_data', 'B lolos verifikasi: diberi tahu permintaannya sedang ditinjau');
cek($nik_a() === $h($NIK1) && satu('SELECT id FROM sf_profil_warga WHERE user_id=?', [$B]) === NULL, 'NIK tidak berpindah otomatis: A masih memegangnya, B tanpa profil');
$jumlah_klaim = fn() => (int) satu("SELECT COUNT(*) n FROM sys_jejak_audit WHERE id > ? AND aksi = 'klaim_nik_diajukan' AND pelaku_id = ?", [$audit_awal, $B])['n'];
cek($jumlah_klaim() === 1, 'Permintaan klaim tercatat di jejak audit');
$jejak = satu("SELECT ringkasan, detail_json FROM sys_jejak_audit WHERE id > ? AND aksi = 'klaim_nik_diajukan' AND pelaku_id = ?", [$audit_awal, $B]);
cek($jejak && strpos($jejak['ringkasan'] . $jejak['detail_json'], $NIK1) === FALSE && strpos($jejak['ringkasan'] . $jejak['detail_json'], substr($NIK1, -6)) === FALSE,
    'Permintaan tanpa NIK (hanya sidik)');
$lookup($j_b, $NIK1, $LAHIR1);
cek($jumlah_klaim() === 1, 'Permintaan yang masih menunggu tidak digandakan');
$id_klaim = (int) (satu("SELECT id FROM sys_jejak_audit WHERE id > ? AND aksi = 'klaim_nik_diajukan' AND pelaku_id = ? ORDER BY id DESC", [$audit_awal, $B])['id'] ?? 0);
minta($j_b, 'akun/profil');
minta($j_b, 'Admin_Users/putuskan_klaim_nik', ['id' => $id_klaim, 'keputusan' => 'setuju']);
cek($nik_a() === $h($NIK1) && ! satu("SELECT id FROM sys_jejak_audit WHERE objek_tipe = 'klaim_nik' AND objek_id = ?", [$id_klaim]),
    'Pemohon (bukan Super Admin) tidak bisa memutuskan permintaannya sendiri');
ember_ip('login');
$j_adm = masuk($e_adm, $SANDI);
$r = minta($j_adm, 'Admin_Users');
cek(strpos($r['badan'], 'data-klaim-nik') !== FALSE && strpos($r['badan'], $e_b) !== FALSE, 'Layar Pengguna Super Admin menampilkan permintaan klaim');
$r = minta($j_adm, 'Admin_Users/putuskan_klaim_nik', ['id' => $id_klaim, 'keputusan' => 'tolak', 'alasan' => 'uji tolak']);
cek($nik_a() === $h($NIK1) && (bool) satu("SELECT id FROM sys_jejak_audit WHERE aksi = 'klaim_nik_ditolak' AND objek_tipe = 'klaim_nik' AND objek_id = ?", [$id_klaim]),
    'Ditolak: ikatan lama tetap, keputusan teraudit');
$lookup($j_b, $NIK1, $LAHIR1);
$id_klaim2 = (int) (satu("SELECT id FROM sys_jejak_audit WHERE id > ? AND aksi = 'klaim_nik_diajukan' AND pelaku_id = ? ORDER BY id DESC", [$audit_awal, $B])['id'] ?? 0);
cek($id_klaim2 > $id_klaim, 'Sesudah ditolak, Cek NIK berikutnya membuat permintaan baru');
minta($j_adm, 'Admin_Users');
$r = minta($j_adm, 'Admin_Users/putuskan_klaim_nik', ['id' => $id_klaim2, 'keputusan' => 'setuju']);
$pindah = satu("SELECT pelaku_id, detail_json FROM sys_jejak_audit WHERE id > ? AND aksi = 'nik_dipindahkan' AND objek_id = ?", [$audit_awal, $A]);
cek($nik_a() === NULL && satu('SELECT nik_lookup_hash FROM usr_akun WHERE id=?', [$B])['nik_lookup_hash'] === $h($NIK1),
    'Disetujui: NIK dilepas dari A dan diikat ke akun B');
cek($pindah && (int) $pindah['pelaku_id'] === $ADM && (json_decode($pindah['detail_json'], TRUE)['akun_penerima'] ?? 0) === $B
    && (bool) satu("SELECT id FROM sys_jejak_audit WHERE aksi = 'klaim_nik_disetujui' AND objek_tipe = 'klaim_nik' AND objek_id = ?", [$id_klaim2]),
    'Disetujui: jejak nik_dipindahkan (pelaku Super Admin) dan keputusan teraudit');
$r = $lookup($j_b, $NIK1, $LAHIR1);
cek($step($r['badan']) === 'housing_family' && (satu('SELECT confirmed_at FROM sf_profil_warga WHERE user_id=?', [$B])['confirmed_at'] ?? NULL) !== NULL,
    'Sesudah disetujui, Cek NIK B terverifikasi');

/* ===================================================================== 6 */
echo "\n== 6. Tebakan pihak ketiga tidak mengunci pemilik NIK\n";
[$C, $e_c] = akun_baru('Uji Penebak Satu');
[$E, $e_e] = akun_baru('Uji Penebak Dua');
[$D, $e_d] = akun_baru('SRI SINTETIS');
$siapkan($C); $siapkan($E); $siapkan($D);
foreach (IP_UJI as $ip) { pinjam_ember(hash('sha256', 'alert_dedupe:key:' . hash('sha256', 'nik:' . substr($h($NIK2), 0, 16) . '|' . $ip))); }
$j_c = masuk($e_c, $SANDI);
for ($i = 0; $i < 5; $i++) { $lookup($j_c, $NIK2, '1990-06-1' . $i); }
$j_e = masuk($e_e, $SANDI);
$lookup($j_e, $NIK2, '1990-06-20');
cek(hitungan(hash('sha256', 'verifikasi_nik_lintas:nik:' . $h($NIK2))) === 6, 'Enam tebakan gagal dari dua akun terhitung untuk NIK itu');
cek((bool) satu("SELECT id FROM sys_jejak_audit WHERE id > ? AND aksi = 'peringatan_keamanan' AND objek_tipe = 'nik_ditebak'", [$audit_awal]),
    'Super Admin mendapat peringatan nik_ditebak (tanpa NIK)');
$j_d = masuk($e_d, $SANDI);
$r = $lookup($j_d, $NIK2, $LAHIR2);
cek(stripos($r['badan'], 'Terlalu banyak percobaan verifikasi') === FALSE && $step($r['badan']) === 'housing_family'
    && (satu('SELECT confirmed_at FROM sf_profil_warga WHERE user_id=?', [$D])['confirmed_at'] ?? NULL) !== NULL,
    'Pemilik dengan data benar tetap bisa verifikasi walau NIK-nya baru ditebak orang lain');
$r = $lookup($j_c, $NIK2, '1990-06-19');
cek(stripos($r['badan'], 'Terlalu banyak percobaan verifikasi') !== FALSE, 'Batas per akun tetap: tebakan keenam akun yang sama ditahan');

/* ===================================================================== 7 */
echo "\n== 7. Cache dan batas laju\n";
require_once APPPATH . 'helpers/sikumbang_helper.php';
require_once APPPATH . 'helpers/cache_hulu_helper.php';
require_once APPPATH . 'helpers/anti_automation_helper.php';
cek(sikumbang_param('keyword', 'PERUMAHAN  Griya') === sikumbang_param('keyword', 'perumahan griya')
    && sikumbang_param('keyword', 'pErUmAhAn') === 'perumahan', 'Kata kunci SIKUMBANG dinormalkan (varian huruf = satu berkas cache)');
cek(sikumbang_param('kodeWilayah', '32', '33') === '33' && sikumbang_param('kodeWilayah', '3374', '33') === '3374', 'Kode wilayah hanya Jawa Tengah');
foreach (['Index', 'Umum'] as $c) {
    cek(strpos((string) file_get_contents(APPPATH . "controllers/$c.php"), "'cache/sikumbang_sebaran_' . (\$kodeWilayah === '33' ? 'jateng' : \$kodeWilayah)") !== FALSE,
        "$c::sebaran: kode wilayah ikut nama cache dasar");
}
cek(in_array('cari', anti_automation_route_classes('Index', 'detail_perum'), TRUE), 'detail_perum masuk kelas laju cari');
$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'uja_' . bin2hex(random_bytes(3));
@mkdir($tmp . DIRECTORY_SEPARATOR . 'cache', 0700, TRUE);
$gagal_ambil = fn() => [FALSE, NULL];
cache_hulu_ambil($tmp . '/cache/a.json', 60, $gagal_ambil, NULL);
cache_hulu_ambil($tmp . '/cache/b.json', 60, $gagal_ambil, 'uji_detail_b');
clearstatcache();
cek(glob($tmp . '/cache/*_gagal.flag') === [$tmp . '/cache/uji_detail_b_gagal.flag'], 'Tanpa prefiks bendera tidak ada berkas bendera; dengan prefiks ada');
$src_index = (string) file_get_contents(APPPATH . 'controllers/Index.php');
cek(strpos($src_index, "\$bendera ? ('sikumbang_detail_' . \$idLokasi) : NULL") !== FALSE, 'detail_perum: bendera per id hanya untuk id asli');
// Batas total cache pencarian di penyapu: tiga berkas pencarian 600 KB, batas 1 MB, satu cache non-pencarian 2 MB.
foreach (['ajax_perumahan_' . str_repeat('1', 32) => 300, 'sikumbang_cari_' . str_repeat('2', 32) => 200, 'sikumbang_sebaran_' . str_repeat('3', 32) => 100, 'sikaper_lain' => 50] as $n => $umur) {
    file_put_contents("$tmp/cache/$n.json", str_repeat('x', $n === 'sikaper_lain' ? 2097152 : 614400));
    touch("$tmp/cache/$n.json", time() - $umur);
}
require_once APPPATH . 'libraries/Penyapu_retensi.php';
$config = []; require APPPATH . 'config/data_lifecycle.php';
$penyapu = new Penyapu_retensi(['db' => $CI_DB, 'policy' => $config['data_lifecycle']['retensi'], 'app' => $tmp . DIRECTORY_SEPARATOR]);
$sapu = new ReflectionMethod('Penyapu_retensi', 'sapu_cache'); $sapu->setAccessible(TRUE);
$sapu->invoke($penyapu, 30, FALSE, 1);
clearstatcache();
$sisa = array_map('basename', glob("$tmp/cache/*.json"));
sort($sisa);
cek($sisa === ['sikaper_lain.json', 'sikumbang_sebaran_' . str_repeat('3', 32) . '.json'], 'Penyapu: cache pencarian disapu dari yang tertua sampai di bawah batas; cache lain utuh');
cek(($config['data_lifecycle']['retensi']['cache_cari_maks_mb'] ?? 0) > 0, 'Kebijakan cache_cari_maks_mb ada');
// cache_foto: batas ditegakkan saat menulis.
cek(function_exists('cache_foto_muat'), 'Pemeriksa ukuran cache_foto saat menulis ada');
if (function_exists('cache_foto_muat')) {
    file_put_contents("$tmp/cache/f.jpg", str_repeat('x', 600));
    cek(cache_foto_muat("$tmp/cache", 300, 1000, "$tmp/t.txt") === TRUE && cache_foto_muat("$tmp/cache", 200, 1000, "$tmp/t.txt") === FALSE,
        'cache_foto_muat: menolak tulisan yang melewati batas');
}
$pos_muat = strpos($src_index, 'cache_foto_muat(');
cek($pos_muat !== FALSE && $pos_muat < strpos($src_index, '@file_put_contents($path_file_lokal'), 'Index::buka_foto memeriksa batas sebelum menyimpan foto');
foreach (array_merge(glob("$tmp/cache/*") ?: [], glob("$tmp/*.txt") ?: []) as $f) { @unlink($f); }
@rmdir("$tmp/cache"); @rmdir($tmp);

// Pendaftaran: email yang sudah terdaftar dijawab sama dengan email baru.
foreach (['register', 'otp_kirim_ip', 'otp_salah_ip'] as $p) { ember_ip($p); }
pinjam_ember(hash('sha256', 'otp_kirim_global:key:otp_global'));
$daftar = function ($email) use ($SANDI) {
    $j = jar(); minta($j, 'Auth/register');
    $r = minta($j, 'Auth/do_register', ['email' => $email, 'password' => $SANDI, 'password_confirm' => $SANDI, 'tos_agree' => '1'], TRUE);
    return [$j, json_decode($r['badan'], TRUE) ?: []];
};
[, $e_lama] = akun_baru('Uji Email Lama');
$e_baru = "{$TAG}_baru@uji-akhir.test";
foreach ([$e_lama, $e_baru] as $e) {
    $otp_berkas[] = $berkas_otp($e);
    foreach (['otp_kirim', 'otp_salah'] as $p) { pinjam_ember(hash('sha256', "$p:key:" . hash('sha256', strtolower($e)))); }
}
[$j_lama, $x_lama] = $daftar($e_lama);
[$j_baru, $x_baru] = $daftar($e_baru);
cek(($x_lama['status'] ?? '') === 'otp_required' && ($x_baru['status'] ?? '') === 'otp_required' && array_keys($x_lama) === array_keys($x_baru),
    'do_register: email terdaftar dan email baru dijawab sama (otp_required, kunci JSON sama)');
cek(trim((string) @file_get_contents($berkas_otp($e_lama))) === 'SUDAH_TERDAFTAR' && preg_match('/^\d{6}$/', trim((string) @file_get_contents($berkas_otp($e_baru)))) === 1,
    'Email terdaftar menerima pemberitahuan tanpa kode; email baru menerima kode');
$v_lama = json_decode(minta($j_lama, 'Auth/do_verifikasi_email', ['kode_otp' => '000000'], TRUE)['badan'], TRUE) ?: [];
$salah = str_pad((string) (((int) trim((string) @file_get_contents($berkas_otp($e_baru)))) + 1) % 1000000, 6, '0', STR_PAD_LEFT);
$v_baru = json_decode(minta($j_baru, 'Auth/do_verifikasi_email', ['kode_otp' => $salah], TRUE)['badan'], TRUE) ?: [];
cek(($v_lama['message'] ?? 'a') === ($v_baru['message'] ?? 'b'), 'Kode salah dijawab sama untuk keduanya');
// OTP: batas per IP (jatah IP ini disetel penuh).
foreach (IP_UJI as $ip) { jalan('INSERT INTO sys_batas_laju (kunci, jendela_mulai_at, jumlah_gagal) VALUES (?, NOW(), 20) ON DUPLICATE KEY UPDATE jendela_mulai_at = NOW(), jumlah_gagal = 20', [hash('sha256', "otp_kirim_ip:ip:$ip")]); }
$e_ip = "{$TAG}_ip@uji-akhir.test"; $otp_berkas[] = $berkas_otp($e_ip);
pinjam_ember(hash('sha256', 'otp_kirim:key:' . hash('sha256', strtolower($e_ip))));
[, $x_ip] = $daftar($e_ip);
cek(($x_ip['status'] ?? '') !== 'otp_required' && stripos((string) ($x_ip['message'] ?? ''), 'Terlalu banyak permintaan kode') !== FALSE && ! is_file($berkas_otp($e_ip)),
    'OTP: jatah kirim per IP penuh, kode tidak dikirim');
$rl = []; $config = []; require APPPATH . 'config/rate_limits.php';
$pol = $config['rate_limit_policies'];
cek(isset($pol['otp_kirim_ip'], $pol['otp_kirim_global']) && $pol['otp_kirim_global']['window'] >= 86400, 'OTP: kebijakan per IP dan plafon global harian ada');

// NIK di Profil Saya: pesan umum dan dibatasi.
[$Q, $e_q] = akun_baru('Uji Pemegang NIK Tiga', ['nik' => $enc->encrypt($NIK3), 'nik_lookup_hash' => $h($NIK3)]);
[$P, $e_p] = akun_baru('Uji Profil Tanpa NIK');
$siapkan($P); ember_ip('profil_nik');
$j_p = masuk($e_p, $SANDI);
$jawab = [];
for ($i = 0; $i < 6; $i++) {
    minta($j_p, 'akun/profil');
    $jawab[] = pesan(minta($j_p, 'akun/update', ['name' => 'Uji Profil Tanpa NIK', 'phone' => '081234567890', 'nik' => $NIK3])['badan']);
}
cek(stripos($jawab[0], 'terhubung dengan akun lain') === FALSE && stripos($jawab[0], 'belum dapat disimpan') !== FALSE,
    'Profil Saya: NIK milik akun lain dijawab umum, tanpa menyebut akun lain');
cek(stripos($jawab[5], 'Terlalu banyak percobaan mengisi NIK') !== FALSE && satu('SELECT nik_lookup_hash FROM usr_akun WHERE id=?', [$P])['nik_lookup_hash'] === NULL,
    'Profil Saya: kiriman NIK keenam dalam sehari ditahan');

// Login: jatah dipesan atomik (delapan permintaan paralel, batas login_akun 5).
ember_ip('login');
$tiada = "tiada_{$TAG}@uji-akhir.test";
foreach (IP_UJI as $ip) { pinjam_ember(hash('sha256', 'login_akun:key:' . hash('sha256', $ip . '|' . $tiada))); }
$jars = []; for ($i = 0; $i < 8; $i++) { $jars[$i] = jar(); minta($jars[$i], 'Auth/login'); }
$mh = curl_multi_init(); $hs = [];
foreach ($jars as $i => $jj) {
    $c = curl_init(BASE . 'Auth/do_login');
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_COOKIEFILE => $jj, CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest'],
        CURLOPT_POSTFIELDS => http_build_query(['email' => $tiada, 'password' => 'Salah#123x', 'csrf_kpkp_token' => token_csrf($jj)])]);
    curl_multi_add_handle($mh, $c); $hs[$i] = $c;
}
do { $s = curl_multi_exec($mh, $jalan); if ($jalan) { curl_multi_select($mh, 1); } } while ($jalan && $s === CURLM_OK);
$tebakan = 0; $ditahan = 0;
foreach ($hs as $c) {
    $kode = curl_getinfo($c, CURLINFO_HTTP_CODE);
    if ($kode === 429) { $ditahan++; } elseif ($kode === 200) { $tebakan++; }
    curl_multi_remove_handle($mh, $c); curl_close($c);
}
curl_multi_close($mh);
// Yang dibuktikan: batas tidak bisa dilampaui lewat permintaan serentak. Di bawah perebutan kunci DB
// pembatas laju sengaja menolak (429) saat ragu, jadi jumlah yang diperiksa bisa kurang dari 5.
cek($tebakan >= 1 && $tebakan <= 5 && $tebakan + $ditahan === 8, "Login paralel: paling banyak 5 tebakan diperiksa, sisanya ditahan (dapat $tebakan diperiksa, $ditahan ditahan)");
$n_login = fn() => array_sum(array_map(fn($ip) => hitungan(hash('sha256', "login:ip:$ip")), IP_UJI));
$sebelum = $n_login();
masuk($e_p, $SANDI);
cek($n_login() === $sebelum, 'Login yang berhasil tidak menambah hitungan gagal per IP');

exit($GLOBALS['gagal'] ? 1 : 0);
