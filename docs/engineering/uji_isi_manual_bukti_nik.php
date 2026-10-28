<?php
require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php'; // lokasi .env (luar akar dulu)
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Jalur "isi manual" pendataan warga tidak boleh mengikat NIK tanpa bukti (audit run-2, 29 Okt 2026).
 *
 *   php docs/engineering/uji_isi_manual_bukti_nik.php
 *
 * Temuan `warga-isi-manual:bind-simperum-found-nik-without-proof`: POST manual_entry=1 mengikat NIK
 * apa pun yang belum terikat, termasuk NIK yang ADA di SIMPERUM, ke profil pendataan akun peminta
 * tanpa pencocokan nama + tanggal lahir; pemilik aslinya lalu terdorong ke tinjauan klaim Super
 * Admin. Jalur yang sama menjawab "terhubung dengan akun lain" tanpa batas laju. Yang diuji:
 *   1. NIK berstatus found di SIMPERUM ditolak, tidak ada profil yang lahir.
 *   2. NIK berstatus not_found tetap bisa diisi manual (perilaku sah tidak patah).
 *   3. NIK yang sudah terikat akun lain dijawab dengan pesan umum, bukan PESAN_NIK_TERIKAT.
 *   4. NIK yang berbeda dari NIK akun (onboarding) ditolak.
 *   5. Jalur ini memakan jatah warga_lookup_jam akun.
 *
 * Snapshot SIMPERUM ditulis langsung (muatan kosong) untuk NIK sintetis berawalan kabupaten lokal,
 * karena fixture simulasi berawalan 3399 (kabupaten yang tidak ada) sehingga ditolak cakupan wilayah
 * sebelum sampai ke celahnya. Akun (@isi-manual.test), snapshot, dan ember batas laju dibersihkan.
 */

define('APP_ROOT', dirname(__DIR__, 2));
define('BASE', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/');
define('BASEPATH', APP_ROOT . '/system/');
define('APPPATH', APP_ROOT . '/application/');
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
$MODE = $env['SIMPERUM_MODE'] ?? '';
if ( ! in_array(strtolower($env['DB_HOST'] ?? ''), ['localhost', '127.0.0.1', '::1'], TRUE) || $MODE !== 'simulation') {
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

define('FCPATH', APP_ROOT . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'development');
require_once APPPATH . 'libraries/Encryption_lib.php';
$enc = new Encryption_lib();
$h = fn($nik) => $enc->deterministic_hash($nik);

$TAG = 'isimanual' . bin2hex(random_bytes(3));
$SANDI = 'Uji#' . bin2hex(random_bytes(5)) . 'A1';
$HASH = password_hash($SANDI, PASSWORD_BCRYPT);
$akun_uji = [];
function akun_baru($nama, array $kolom = []) {
    global $TAG, $HASH, $akun_uji;
    $email = $TAG . '_' . count($akun_uji) . '@isi-manual.test';
    $isi = $kolom + ['nama' => $nama, 'email' => $email, 'kata_sandi' => $HASH, 'peran' => 'warga',
        'status' => 'active', 'profil_lengkap' => 1, 'sandi_diganti_at' => date('Y-m-d H:i:s'),
        'sandi_kedaluwarsa_at' => date('Y-m-d H:i:s', strtotime('+90 days')), 'created_at' => date('Y-m-d H:i:s')];
    $id = jalan('INSERT INTO usr_akun (' . implode(',', array_keys($isi)) . ') VALUES (' . implode(',', array_fill(0, count($isi), '?')) . ')', array_values($isi));
    $akun_uji[] = (int) $id;
    return [(int) $id, $email];
}
function minta($jar, $path, $post = NULL) {
    $c = curl_init(BASE . $path);
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_FOLLOWLOCATION => TRUE, CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60]);
    if ($post !== NULL) {
        $post += ['csrf_kpkp_token' => token_csrf($jar)];
        curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $badan = (string) curl_exec($c);
    $kode = curl_getinfo($c, CURLINFO_HTTP_CODE);
    curl_close($c);
    return ['kode' => $kode, 'badan' => html_entity_decode($badan, ENT_QUOTES, 'UTF-8')];
}
function token_csrf($jar) {
    foreach (@file($jar) ?: [] as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') { return $p[6]; } }
    return '';
}
$jar_dibuat = [];
function masuk($email, $sandi) {
    global $jar_dibuat;
    $j = $jar_dibuat[] = tempnam(sys_get_temp_dir(), 'ujm');
    minta($j, 'Auth/login');
    minta($j, 'Auth/do_login', ['email' => $email, 'password' => $sandi]);
    return $j;
}
/** Seluruh teks notifikasi flashdata di halaman, digabung. */
function pesan($badan) {
    $isi = preg_match('/data-kpkp-flash-notifications>(.*?)<\/script>/s', $badan, $m) ? (json_decode($m[1], TRUE) ?: []) : [];
    return json_encode($isi, JSON_UNESCAPED_UNICODE);
}
$ember_asli = [];
function kosongkan_ember($kunci) {
    global $ember_asli;
    if ( ! array_key_exists($kunci, $ember_asli)) { $ember_asli[$kunci] = satu('SELECT kunci, jendela_mulai_at, jumlah_gagal FROM sys_batas_laju WHERE kunci=?', [$kunci]); }
    jalan('DELETE FROM sys_batas_laju WHERE kunci=?', [$kunci]);
}
$snapshot_uji = [];
function snapshot($nik, $status) {
    global $h, $MODE, $snapshot_uji;
    $snapshot_uji[] = (int) jalan("INSERT INTO sf_rekaman_simperum (nik_lookup_hash, mode_sumber, status_respons, versi_api, http_status, fetched_at, expires_at)
        VALUES (?,?,?,'simulation-v1',200,NOW(),?)", [$h($nik), $MODE, $status, date('Y-m-d H:i:s', strtotime('+1 day'))]);
}
$manual = function ($j, $nik) {
    return minta($j, 'warga/pendataan', ['step' => 'find_data', 'manual_entry' => '1', 'nik' => $nik, 'full_name' => 'Uji Isi Manual']);
};
$profil = fn($id) => satu('SELECT id, nik_lookup_hash, mode_sumber, confirmed_at FROM sf_profil_warga WHERE user_id=?', [$id]);

$KAB = (int) satu('SELECT id FROM kabupaten WHERE id BETWEEN 1000 AND 9999 ORDER BY id LIMIT 1')['id'];
$acak = fn() => sprintf('%04d', $KAB) . '98' . sprintf('%010d', random_int(0, 9999999999));
$NIK_ADA = $acak(); $NIK_TIDAK = $acak(); $NIK_AKUN = $acak(); $NIK_LAIN = $acak();

$PESAN_TERIKAT = 'sudah terhubung dengan akun lain';
$PESAN_UMUM = 'belum dapat disimpan dari halaman ini';

try {
    echo "== Persiapan\n";
    cek($KAB > 0, 'Ada kabupaten lokal untuk awalan NIK sintetis');
    foreach ([$NIK_ADA, $NIK_TIDAK, $NIK_AKUN, $NIK_LAIN] as $n) {
        $terikat = (int) satu('SELECT (SELECT COUNT(*) FROM usr_akun WHERE nik_lookup_hash=?) + (SELECT COUNT(*) FROM sf_profil_warga WHERE nik_lookup_hash=?) n', [$h($n), $h($n)])['n'];
        if ( ! cek($terikat === 0, 'NIK sintetis ' . substr($n, -4) . ' belum terikat')) { throw new RuntimeException('NIK sintetis terikat'); }
    }
    snapshot($NIK_ADA, 'found');
    snapshot($NIK_TIDAK, 'not_found');
    snapshot($NIK_AKUN, 'not_found');
    snapshot($NIK_LAIN, 'not_found');
    foreach (['127.0.0.1', '::1', '0000000000000000/64'] as $ip) { kosongkan_ember(hash('sha256', "login:ip:$ip")); }

    [$A, $e_a] = akun_baru('Uji Isi Manual A');
    [$B, $e_b] = akun_baru('Uji Isi Manual B');
    [$C, $e_c] = akun_baru('Uji Isi Manual C', ['nik' => $enc->encrypt($NIK_AKUN), 'nik_lookup_hash' => $h($NIK_AKUN)]);
    $j_a = masuk($e_a, $SANDI); $j_b = masuk($e_b, $SANDI); $j_c = masuk($e_c, $SANDI);
    $awal = minta($j_a, 'warga/pendataan');
    if ( ! cek($awal['kode'] === 200 && strpos($awal['badan'], 'name="step"') !== FALSE, 'Akun A masuk dan halaman pendataan terbuka (penjaga positif)')) {
        throw new RuntimeException('login gagal');
    }

    echo "== 1. NIK yang ADA di SIMPERUM tidak bisa diikat lewat isi manual\n";
    $r = $manual($j_a, $NIK_ADA);
    cek($r['kode'] === 200, 'Respons 200');
    cek($profil($A) === NULL, 'Tidak ada profil pendataan yang lahir untuk akun A');
    cek(strpos(pesan($r['badan']), 'tidak ditemukan di SIMPERUM') !== FALSE, 'Pesan mengarahkan ke Cek NIK');

    echo "== 2. NIK not_found tetap bisa diisi manual\n";
    $r = $manual($j_a, $NIK_TIDAK);
    $p = $profil($A);
    cek($p !== NULL && $p['nik_lookup_hash'] === $h($NIK_TIDAK) && $p['mode_sumber'] === 'manual', 'Profil manual lahir untuk NIK not_found');
    cek($p !== NULL && $p['confirmed_at'] === NULL, 'Profil manual tetap tercatat belum terverifikasi');

    echo "== 3. NIK milik akun lain dijawab dengan pesan umum\n";
    $r = $manual($j_b, $NIK_TIDAK);
    $teks = pesan($r['badan']);
    cek($profil($B) === NULL, 'Akun B tidak mendapat profil');
    cek(strpos($teks, $PESAN_TERIKAT) === FALSE, 'Pesan tidak membocorkan "terhubung dengan akun lain"');
    cek(strpos($teks, $PESAN_UMUM) !== FALSE, 'Pesan umum PESAN_NIK_BELUM_DISIMPAN yang tampil');

    echo "== 4. NIK berbeda dari NIK akun ditolak, NIK akun sendiri diterima\n";
    $r = $manual($j_c, $NIK_LAIN);
    cek($profil($C) === NULL, 'NIK lain tidak terikat ke akun C');
    cek(strpos(pesan($r['badan']), 'berbeda dengan NIK yang terdaftar') !== FALSE, 'Pesan menyebut NIK berbeda');
    $manual($j_c, $NIK_AKUN);
    $p = $profil($C);
    cek($p !== NULL && $p['nik_lookup_hash'] === $h($NIK_AKUN), 'NIK akun sendiri (not_found) diterima');

    echo "== 5. Jalur isi manual memakan jatah pencarian akun\n";
    $jatah = satu('SELECT jumlah_gagal FROM sys_batas_laju WHERE kunci=?', [hash('sha256', "warga_lookup_jam:account:$C")]);
    cek($jatah !== NULL && (int) $jatah['jumlah_gagal'] >= 2, 'Ember warga_lookup_jam akun C bertambah');
} catch (Throwable $e) {
    cek(FALSE, 'Pengecualian: ' . $e->getMessage());
} finally {
    if ($akun_uji) {
        $daftar = implode(',', array_map('intval', $akun_uji));
        foreach (['sf_data_simperum', 'sf_penilaian_perumahan', 'sf_profil_warga'] as $t) { $db->query("DELETE FROM $t WHERE user_id IN ($daftar)"); }
        foreach ($akun_uji as $id) {
            foreach (['warga_lookup_jam', 'warga_lookup_harian'] as $p) { jalan('DELETE FROM sys_batas_laju WHERE kunci=?', [hash('sha256', "$p:account:$id")]); }
        }
        $db->query("DELETE FROM usr_akun WHERE id IN ($daftar)");
    }
    if ($snapshot_uji) { $db->query('DELETE FROM sf_rekaman_simperum WHERE id IN (' . implode(',', $snapshot_uji) . ')'); }
    foreach ($ember_asli as $kunci => $baris) {
        jalan('DELETE FROM sys_batas_laju WHERE kunci=?', [$kunci]);
        if ($baris) { jalan('INSERT INTO sys_batas_laju (kunci, jendela_mulai_at, jumlah_gagal) VALUES (?,?,?)', array_values($baris)); }
    }
    foreach ($jar_dibuat as $f) { @unlink($f); }
    echo "Akun uji tersisa: " . (int) satu('SELECT COUNT(*) n FROM usr_akun WHERE email LIKE ?', [$TAG . '%'])['n'] . "\n";
}
echo "RINGKASAN: {$GLOBALS['total']} pemeriksaan, {$GLOBALS['gagal']} gagal\n";
exit($GLOBALS['gagal'] ? 1 : 0);
