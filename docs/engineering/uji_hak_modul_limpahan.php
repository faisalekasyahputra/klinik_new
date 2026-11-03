<?php
require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php'; // lokasi .env (luar akar dulu)
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Uji LIMPAHAN HAK MODUL - Super Admin memberi modul super admin ke admin bidang/kabkota
 * lewat Admin_Privileges (permintaan dinas 2 Nov 2026).
 *
 *   php docs/engineering/uji_hak_modul_limpahan.php
 *
 * Yang dijaga:
 *   1. FAIL-CLOSED. Akun tanpa baris hak modul tidak mendapat modul super admin apa pun,
 *      dan layar Hak Modul tidak mencentangnya sendiri.
 *   2. LIMPAHAN BERLAKU. Modul yang dicentang terbuka lewat URL dan tampil di menu.
 *   3. YANG TIDAK DICENTANG TETAP TERTUTUP, walau satu controller dengan modul yang dicentang.
 *   4. TUJUH MODUL KHUSUS SUPER ADMIN tidak bisa dilimpahkan, juga lewat POST buatan.
 *
 * Akun dan barisnya dibuat dan dibersihkan sendiri; email berakhiran @example.test.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('ENV_PATH', env_berkas_path(APP_ROOT));
define('SANDI', 'UjiLimpah!2026');

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;
$GLOBALS['users'] = [];
$GLOBALS['emails'] = [];
$GLOBALS['jar'] = [];

function cek($kondisi, $label) {
    $GLOBALS['uji_total']++;
    echo ($kondisi ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $kondisi) { $GLOBALS['uji_gagal']++; }
    return (bool) $kondisi;
}

function wajib($kondisi, $label) {
    if ( ! cek($kondisi, $label)) { bersihkan(); fwrite(STDERR, "Berhenti: prasyarat gagal.\n"); exit(1); }
}

/** Parser .env milik index.php: kemunculan PERTAMA menang, baris '#' dilewati. */
function env_config($path) {
    $out = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $baris) {
        $baris = trim($baris);
        if ($baris === '' || $baris[0] === '#' || strpos($baris, '=') === FALSE) { continue; }
        [$k, $v] = explode('=', $baris, 2);
        if ( ! isset($out[trim($k)])) { $out[trim($k)] = trim($v); }
    }
    return $out;
}

function q($sql, $params = []) {
    $stmt = $GLOBALS['db']->prepare($sql);
    if ( ! $stmt) { fwrite(STDERR, $GLOBALS['db']->error . "\n"); exit(1); }
    if ($params) { $stmt->bind_param(str_repeat('s', count($params)), ...$params); }
    $stmt->execute();
    $res = $stmt->get_result();
    $out = $res ? $res->fetch_assoc() : NULL;
    $id = $stmt->insert_id;
    $stmt->close();
    return $out ?: ['__id' => $id];
}
function tulis($sql, $params = []) { return (int) (q($sql, $params)['__id'] ?? 0); }
function nilai($sql, $params = []) { $r = q($sql, $params); return $r && ! isset($r['__id']) ? reset($r) : NULL; }

function http($nama, $path, ?array $post = NULL) {
    if ( ! isset($GLOBALS['jar'][$nama])) { $GLOBALS['jar'][$nama] = tempnam(sys_get_temp_dir(), 'ujlm_'); }
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    $opt = [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_COOKIEJAR => $GLOBALS['jar'][$nama],
        CURLOPT_COOKIEFILE => $GLOBALS['jar'][$nama], CURLOPT_FOLLOWLOCATION => TRUE, CURLOPT_TIMEOUT => 30];
    if ($post !== NULL) {
        $opt[CURLOPT_POST] = TRUE; $opt[CURLOPT_POSTFIELDS] = http_build_query($post);
        $opt[CURLOPT_HTTPHEADER] = ['X-Requested-With: XMLHttpRequest'];
    }
    curl_setopt_array($ch, $opt);
    $body = (string) curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return ['code' => (int) $info['http_code'], 'body' => $body, 'url' => (string) $info['url']];
}

/** Token CSRF; fatal kalau hilang, supaya POST yang ditolak CSRF tidak lulus hampa. */
function csrf($nama, $path) {
    $r = http($nama, $path);
    if (preg_match('/name="csrf_kpkp_token"\s+value="([^"]+)"/', $r['body'], $m)) { return $m[1]; }
    if (preg_match('/<meta\s+name="csrf-token-hash"\s+content="([^"]+)"/', $r['body'], $m)) { return $m[1]; }
    bersihkan(); fwrite(STDERR, "FATAL: token CSRF tidak ditemukan di /{$path}.\n"); exit(1);
}

function login($nama, $email) {
    $r = http($nama, 'Auth/do_login', ['csrf_kpkp_token' => csrf($nama, 'Auth/login'), 'email' => $email, 'password' => SANDI]);
    return (json_decode($r['body'], TRUE)['status'] ?? '') === 'success';
}

function buat_akun($peran, $suffix, $bidang = NULL) {
    $email = 'uji_limpah_' . $suffix . '_' . time() . '@example.test';
    $id = tulis('INSERT INTO usr_akun (email,kata_sandi,nama,nama_pengguna,peran,bidang_kode,status,profil_lengkap,created_at)
                 VALUES (?,?,?,?,?,?,"active",1,NOW())',
        [$email, password_hash(SANDI, PASSWORD_BCRYPT), 'Uji Limpah ' . $suffix,
         'uji_limpah_' . $suffix . '_' . mt_rand(10000, 99999), $peran, $bidang]);
    $GLOBALS['users'][] = $id;
    $GLOBALS['emails'][] = $email;
    return [$id, $email];
}

/** Halaman benar-benar terbuka: tidak dialihkan ke layar akses ditolak/login dan bukan 403. */
function terbuka($r, $path) {
    return $r['code'] === 200 && stripos($r['url'], $path) !== FALSE
        && stripos($r['url'], 'akses_ditolak') === FALSE && stripos($r['url'], 'Auth/login') === FALSE;
}

function izin($user_id, $kunci) {
    return nilai('SELECT diizinkan FROM usr_hak_modul_admin WHERE user_id=? AND kunci_modul=?', [$user_id, $kunci]);
}

function bersihkan() {
    if (empty($GLOBALS['db'])) { return; }
    foreach ($GLOBALS['users'] as $id) {
        q("DELETE FROM sys_jejak_audit WHERE objek_tipe='usr_akun' AND objek_id=?", [(string) $id]);
        q('DELETE FROM usr_hak_modul_admin WHERE user_id=?', [$id]);
    }
    foreach ($GLOBALS['emails'] as $email) { q('DELETE FROM sys_jejak_audit WHERE pelaku_email=?', [$email]); }
    foreach ($GLOBALS['users'] as $id) { q('DELETE FROM usr_akun WHERE id=?', [$id]); }
    foreach ($GLOBALS['jar'] as $j) { @unlink($j); }
    $GLOBALS['users'] = $GLOBALS['emails'] = $GLOBALS['jar'] = [];
}
register_shutdown_function('bersihkan');

// ==========================================================================

if ( ! is_file(ENV_PATH)) { die(".env tidak ditemukan.\n"); }
$env = env_config(ENV_PATH);
$GLOBALS['db'] = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
if ($GLOBALS['db']->connect_error) { die("Koneksi DB gagal.\n"); }

echo "=== UJI LIMPAHAN HAK MODUL ===\n";
echo 'Target: ' . BASE_URL . " | DB: {$env['DB_NAME']}\n\n";

[$idA, $emailA] = buat_akun('admin', 'super');
[$idB, $emailB] = buat_akun('admin_bidang', 'bidang', 'perencanaan_teknis');

wajib(login('a', $emailA), 'Login super admin');
wajib(login('b', $emailB), 'Login admin bidang');

// ===================================================== 1. FAIL-CLOSED
cek( ! terbuka(http('b', 'Admin_Aduan'), 'Admin_Aduan'), 'Tanpa limpahan, admin bidang ditolak di Pantau Aduan');
cek( ! terbuka(http('b', 'Admin_Kemitraan/kkn'), 'Admin_Kemitraan'), 'Tanpa limpahan, admin bidang ditolak di KKN');
$layar = http('a', 'Admin_Privileges/index/' . $idB);
wajib(terbuka($layar, 'Admin_Privileges'), 'Layar Hak Modul terbuka untuk super admin');
cek(strpos($layar['body'], 'Tambahan dari Super Admin') !== FALSE, 'Layar Hak Modul memuat kelompok Tambahan dari Super Admin');
cek( ! preg_match('/value="aduan_semua"\s+checked/', $layar['body']), 'Modul limpahan tidak tercentang sendiri');
cek(preg_match('/value="aduan_bidang"\s+checked/', $layar['body']) === 1, 'Modul milik peran sendiri tetap tercentang bawaan');
foreach (['users', 'audit', 'struktur_cakupan', 'aturan_rekomendasi', 'katalog_program', 'permintaan_nik', 'seo_halaman'] as $k) {
    cek(strpos($layar['body'], 'value="' . $k . '"') === FALSE, "Modul khusus super admin '{$k}' tidak ditawarkan");
}

// ===================================================== 2. SIMPAN LIMPAHAN (+ POST buatan untuk modul terkunci)
http('a', 'Admin_Privileges/save', [
    'csrf_kpkp_token' => csrf('a', 'Admin_Privileges/index/' . $idB), 'id' => $idB,
    'modules' => ['aduan_bidang', 'kemitraan_bidang', 'aduan_semua', 'kemitraan_kkn', 'users', 'audit', 'permintaan_nik'],
]);
cek((int) izin($idB, 'kemitraan') === 1, 'Mencentang KKN ikut melimpahkan induknya, Kemitraan (tempat tombol tindakannya)');
cek((int) izin($idB, 'aduan_semua') === 1 && (int) izin($idB, 'kemitraan_kkn') === 1, 'Limpahan Pantau Aduan dan KKN tersimpan');
cek(izin($idB, 'users') === NULL && izin($idB, 'audit') === NULL && izin($idB, 'permintaan_nik') === NULL,
    'POST buatan untuk Akses Staf, Jejak Audit, dan Permintaan NIK tidak tersimpan');

// ===================================================== 3. LIMPAHAN BERLAKU (sesi baru membaca hak terbaru)
wajib(login('b2', $emailB), 'Login ulang admin bidang');
$aduan = http('b2', 'Admin_Aduan');
cek(terbuka($aduan, 'Admin_Aduan'), 'Pantau Aduan terbuka sesudah dilimpahkan');
cek(strpos($aduan['body'], 'Pantau Aduan') !== FALSE && strpos($aduan['body'], 'Aduan Bidang Saya') !== FALSE,
    'Menu memuat modul limpahan di samping modul milik sendiri');
cek(terbuka(http('b2', 'Admin_Kemitraan/kkn'), 'Admin_Kemitraan/kkn'), 'KKN terbuka sesudah dilimpahkan');

// ===================================================== 4. YANG TIDAK DICENTANG TETAP TERTUTUP
cek( ! terbuka(http('b2', 'Admin_Kemitraan/sertifikat'), 'Admin_Kemitraan/sertifikat'), 'Piagam KKN (tidak dicentang) tetap ditolak walau satu controller');
cek( ! terbuka(http('b2', 'Admin_Srp2'), 'Admin_Srp2'), 'SRP2 (tidak dicentang) tetap ditolak');
cek( ! terbuka(http('b2', 'Admin_Users'), 'Admin_Users'), 'Akses Staf tetap ditolak');
cek( ! terbuka(http('b2', 'Admin_Users/permintaan_nik'), 'Admin_Users'), 'Permintaan NIK tetap ditolak');
cek( ! terbuka(http('b2', 'Admin_Audit'), 'Admin_Audit'), 'Jejak Audit tetap ditolak');
cek( ! terbuka(http('b2', 'Admin_Privileges/index/' . $idB), 'Admin_Privileges'), 'Hak Modul dirinya sendiri tetap ditolak');
cek( ! terbuka(http('b2', 'Admin_Aturan'), 'Admin_Aturan'), 'Aturan Rekomendasi tetap ditolak');

// ===================================================== 5. DICABUT = TERTUTUP LAGI
http('a', 'Admin_Privileges/save', ['csrf_kpkp_token' => csrf('a', 'Admin_Privileges/index/' . $idB), 'id' => $idB,
    'modules' => ['aduan_bidang', 'kemitraan_bidang']]);
wajib(login('b3', $emailB), 'Login ulang admin bidang sesudah dicabut');
cek( ! terbuka(http('b3', 'Admin_Aduan'), 'Admin_Aduan'), 'Pantau Aduan tertutup lagi sesudah limpahan dicabut');

echo "\nHasil: " . ($GLOBALS['uji_total'] - $GLOBALS['uji_gagal']) . '/' . $GLOBALS['uji_total'] . " lulus\n";
exit($GLOBALS['uji_gagal'] ? 1 : 0);
