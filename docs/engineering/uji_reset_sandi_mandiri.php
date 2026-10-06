<?php
require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php'; // lokasi .env (luar akar dulu)
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Reset kata sandi mandiri lewat email (permintaan user 6 Okt 2026, migrasi 074).
 *
 *   php docs/engineering/uji_reset_sandi_mandiri.php
 *
 * Dijaga:
 *   1. Lupa Password berisi formulir email; jawabannya SAMA untuk email terdaftar dan tidak (anti
 *      enumerasi), dan hanya akun terdaftar yang dikirimi email (mode uji: application/cache/surel_uji/).
 *   2. Yang tersimpan hanya sidik SHA-256 token, berlaku 30 menit; tautan membuka formulir sandi baru
 *      tanpa Referer; tautan rusak atau kedaluwarsa ditolak dengan arahan meminta tautan baru.
 *   3. Sandi lemah atau konfirmasi beda ditolak tanpa menghanguskan token; sandi sah disimpan, token
 *      sekali pakai, kunci gagal masuk dibuka, sesi lama diakhiri, email pemberitahuan dikirim.
 *   4. Akun nonaktif tidak dikirimi tautan; pembatas laju per email menolak permintaan keempat.
 *
 * Akun uji (@reset-sandi.test) dibuat lalu dihapus sendiri; ember laju yang dipakai dipinjam lalu dipulihkan.
 */

define('APP_ROOT', dirname(__DIR__, 2));
define('BASE', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/');

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
    if ( ! array_key_exists($k, $env)) { $env[$k] = $v; }
}
mysqli_report(MYSQLI_REPORT_OFF);
if ( ! in_array(strtolower($env['DB_HOST'] ?? ''), ['localhost', '127.0.0.1', '::1'], TRUE)) {
    cek(FALSE, 'DB lokal (suite ini menulis akun uji)');
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

$TAG = 'resetsandi' . bin2hex(random_bytes(3));
$SANDI_LAMA = 'Lama#' . bin2hex(random_bytes(4)) . 'A1';
$SANDI_BARU = 'Baru#' . bin2hex(random_bytes(4)) . 'B2';
$akun_uji = [];

function akun_uji($urut, $status = 'active') {
    global $TAG, $SANDI_LAMA, $akun_uji;
    $isi = ['nama' => 'Warga Sandi ' . $urut, 'email' => $TAG . '_' . $urut . '@reset-sandi.test',
        'kata_sandi' => password_hash($SANDI_LAMA, PASSWORD_BCRYPT), 'peran' => 'warga', 'status' => $status, 'profil_lengkap' => 1,
        'sandi_diganti_at' => date('Y-m-d H:i:s'), 'sandi_kedaluwarsa_at' => date('Y-m-d H:i:s', strtotime('+90 days')),
        'created_at' => date('Y-m-d H:i:s')];
    $id = jalan('INSERT INTO usr_akun (' . implode(',', array_keys($isi)) . ') VALUES (' . implode(',', array_fill(0, count($isi), '?')) . ')', array_values($isi));
    $akun_uji[] = (int) $id;
    return [(int) $id, $isi['email']];
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
    $r = ['kode' => curl_getinfo($c, CURLINFO_HTTP_CODE), 'url' => curl_getinfo($c, CURLINFO_EFFECTIVE_URL),
        'badan' => html_entity_decode($badan, ENT_QUOTES, 'UTF-8')];
    curl_close($c);
    return $r;
}
function token_csrf($jar) {
    foreach (@file($jar) ?: [] as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') { return $p[6]; } }
    return '';
}
$jar_dibuat = [];
function jar() { global $jar_dibuat; return $jar_dibuat[] = tempnam(sys_get_temp_dir(), 'urs'); }
function masuk($email, $sandi) {
    $j = jar();
    minta($j, 'Auth/login');
    minta($j, 'Auth/do_login', ['email' => $email, 'password' => $sandi]);
    return $j;
}
function pesan($badan) {
    return preg_match('/data-kpkp-flash-notifications>(.*?)<\/script>/s', $badan, $m) ? (json_decode($m[1], TRUE) ?: []) : [];
}
function ada_pesan($badan, $jenis, $kata) {
    foreach (pesan($badan) as $p) { if (($p['type'] ?? '') === $jenis && strpos((string) ($p['message'] ?? ''), $kata) !== FALSE) { return TRUE; } }
    return FALSE;
}
/** Ember laju dipinjam: dikosongkan untuk suite, isi aslinya dipulihkan di akhir. */
$ember_asli = [];
function pinjam_ember($kunci) {
    global $ember_asli;
    if ( ! array_key_exists($kunci, $ember_asli)) { $ember_asli[$kunci] = satu('SELECT kunci, jendela_mulai_at, jumlah_gagal FROM sys_batas_laju WHERE kunci=?', [$kunci]); }
    jalan('DELETE FROM sys_batas_laju WHERE kunci=?', [$kunci]);
}
function ember_ip($policy) {
    foreach (['127.0.0.1', '::1', '0000000000000000/64'] as $ip) { pinjam_ember(hash('sha256', "$policy:ip:$ip")); }
}
function ember_email($email) { pinjam_ember(hash('sha256', 'sandi_lupa:key:' . hash('sha256', strtolower($email)))); }
$surel = function ($email) { return json_decode((string) @file_get_contents(APP_ROOT . '/application/cache/surel_uji/' . sha1(strtolower($email)) . '.json'), TRUE) ?: []; };
$terakhir = function ($email) use ($surel) { $d = $surel($email); return $d ? $d[count($d) - 1] : []; };
$token_dari = function (array $m) { return preg_match('#atur-sandi/([a-f0-9]{64})$#', (string) ($m['tautan'] ?? ''), $x) ? $x[1] : ''; };
$audit_awal = (int) satu('SELECT COALESCE(MAX(id), 0) n FROM sys_jejak_audit')['n'];
$berkas_surel = [];

try {
    ember_ip('sandi_lupa_ip');
    pinjam_ember(hash('sha256', 'otp_kirim_global:key:otp_global'));
    [$A, $eA] = akun_uji('a');
    [$N, $eN] = akun_uji('n', 'nonaktif');
    $eX = $TAG . '_tidakada@reset-sandi.test';
    foreach ([$eA, $eN, $eX] as $e) { ember_email($e); $berkas_surel[] = $e; }

    echo "== 1. Formulir dan jawaban yang sama untuk semua email\n";
    $j = jar();
    $r = minta($j, 'Auth/forgot_password');
    cek(strpos($r['badan'], 'data-lupa-sandi') !== FALSE && strpos($r['badan'], 'belum tersedia') === FALSE, 'Lupa Password berisi formulir email, bukan pesan "belum tersedia"');
    $r = minta($j, 'Auth/kirim_tautan_sandi');
    cek($r['kode'] >= 400 || strpos($r['url'], 'kirim_tautan_sandi') === FALSE, 'Kirim tautan hanya menerima POST');
    $rX = minta($j, 'Auth/kirim_tautan_sandi', ['email' => $eX]);
    minta($j, 'Auth/forgot_password');
    $rA = minta($j, 'Auth/kirim_tautan_sandi', ['email' => strtoupper($eA)]);
    cek(pesan($rX['badan']) === pesan($rA['badan']) && ada_pesan($rA['badan'], 'success', 'Bila email itu terdaftar'),
        'Pesan untuk email tidak terdaftar dan terdaftar identik');
    cek($surel($eX) === [], 'Email tidak terdaftar tidak dikirimi apa pun');
    $mA = $terakhir($eA);
    $tokenA = $token_dari($mA);
    $akun = satu('SELECT token_sandi_hash h, token_sandi_kedaluwarsa k FROM usr_akun WHERE id=?', [$A]);
    cek($tokenA !== '' && strpos($mA['subjek'] ?? '', 'kata sandi') !== FALSE, 'Email terdaftar (huruf besar pun) menerima tautan atur-sandi');
    cek(hash_equals((string) $akun['h'], hash('sha256', $tokenA)) && strpos(json_encode(satu('SELECT * FROM usr_akun WHERE id=?', [$A])), $tokenA) === FALSE,
        'Yang tersimpan hanya sidik SHA-256 token, bukan tokennya');
    $sisa = strtotime($akun['k']) - time();
    cek($sisa > 1700 && $sisa <= 1800, 'Token berlaku 30 menit');
    cek((int) satu("SELECT COUNT(*) n FROM sys_jejak_audit WHERE id > ? AND aksi='sandi_lupa_diminta' AND objek_id=?", [$audit_awal, $A])['n'] === 1,
        'Jejak audit sandi_lupa_diminta tercatat');
    minta($j, 'Auth/forgot_password');
    minta($j, 'Auth/kirim_tautan_sandi', ['email' => $eN]);
    cek($surel($eN) === [], 'Akun nonaktif tidak dikirimi tautan');

    echo "\n== 2. Tautan\n";
    $r = minta(jar(), 'atur-sandi/' . $tokenA);
    cek(strpos($r['badan'], 'data-atur-sandi') !== FALSE && strpos($r['badan'], 'name="token" value="' . $tokenA . '"') !== FALSE
        && strpos($r['badan'], 'name="referrer" content="no-referrer"') !== FALSE, 'Tautan sah membuka formulir sandi baru, tanpa Referer');
    $r = minta(jar(), 'atur-sandi/' . substr($tokenA, 0, 63) . ($tokenA[63] === '0' ? '1' : '0'));
    cek(strpos($r['badan'], 'data-lupa-sandi') !== FALSE && ada_pesan($r['badan'], 'error', 'tidak berlaku'), 'Tautan rusak diarahkan meminta tautan baru');

    echo "\n== 3. Simpan sandi baru\n";
    ember_ip('login');
    $jLama = masuk($eA, $SANDI_LAMA);
    cek(strpos(minta($jLama, 'akun/profil')['url'], 'akun/profil') !== FALSE, 'Prasyarat: sesi lama A aktif');
    jalan('UPDATE usr_akun SET gagal_masuk=5, terkunci_sampai=? WHERE id=?', [date('Y-m-d H:i:s', strtotime('+15 minutes')), $A]);
    $jT = jar();
    minta($jT, 'atur-sandi/' . $tokenA);
    $r = minta($jT, 'Auth/simpan_sandi', ['token' => $tokenA, 'password' => 'lemah', 'password_confirm' => 'lemah']);
    cek(ada_pesan($r['badan'], 'error', 'minimal 8 karakter') && satu('SELECT token_sandi_hash h FROM usr_akun WHERE id=?', [$A])['h'] !== NULL,
        'Sandi lemah ditolak, token tetap berlaku');
    $r = minta($jT, 'Auth/simpan_sandi', ['token' => $tokenA, 'password' => $SANDI_BARU, 'password_confirm' => $SANDI_BARU . 'x']);
    cek(ada_pesan($r['badan'], 'error', 'tidak sama') && strpos($r['badan'], 'data-atur-sandi') !== FALSE, 'Konfirmasi beda ditolak, kembali ke formulir');
    $r = minta($jT, 'Auth/simpan_sandi', ['token' => $tokenA, 'password' => $SANDI_BARU, 'password_confirm' => $SANDI_BARU]);
    $akun = satu('SELECT * FROM usr_akun WHERE id=?', [$A]);
    cek(strpos($r['badan'], 'Kata sandi baru sudah tersimpan') !== FALSE, 'Sandi sah disimpan, diarahkan ke halaman masuk dengan konfirmasi');
    cek(password_verify($SANDI_BARU, $akun['kata_sandi']) && $akun['token_sandi_hash'] === NULL && $akun['token_sandi_kedaluwarsa'] === NULL,
        'Sandi baru tersimpan dan token dihapus');
    cek((int) $akun['gagal_masuk'] === 0 && $akun['terkunci_sampai'] === NULL && $akun['sesi_aktif_hash'] === NULL, 'Kunci gagal masuk dibuka, sesi aktif dikosongkan');
    cek(strtotime($akun['sandi_kedaluwarsa_at']) > strtotime('+80 days') && $akun['email_verified_at'] !== NULL,
        'Sandi baru berlaku normal (tidak langsung wajib ganti), email tercatat terverifikasi');
    cek(strpos(minta($jLama, 'akun/profil')['url'], 'akun/profil') === FALSE, 'Sesi lama A diakhiri');
    $daftar = $surel($eA);
    cek(count($daftar) === 2 && strpos($terakhir($eA)['subjek'] ?? '', 'telah diganti') !== FALSE, 'Email pemberitahuan "kata sandi telah diganti" terkirim');
    cek((int) satu("SELECT COUNT(*) n FROM sys_jejak_audit WHERE id > ? AND aksi='sandi_direset_mandiri' AND objek_id=?", [$audit_awal, $A])['n'] === 1
        && strpos(json_encode(satu("SELECT * FROM sys_jejak_audit WHERE id > ? AND objek_id=? ORDER BY id DESC", [$audit_awal, $A])), $SANDI_BARU) === FALSE,
        'Jejak audit sandi_direset_mandiri tercatat tanpa sandi');
    $r = minta(jar(), 'atur-sandi/' . $tokenA);
    cek(ada_pesan($r['badan'], 'error', 'tidak berlaku'), 'Token sekali pakai: tautan yang sama tidak berlaku lagi');
    ember_ip('login');
    $jBaru = masuk($eA, $SANDI_BARU);
    cek(strpos(minta($jBaru, 'akun/profil')['url'], 'akun/profil') !== FALSE, 'Masuk dengan sandi baru berhasil');

    echo "\n== 4. Kedaluwarsa dan pembatas laju\n";
    ember_email($eA);
    minta($j, 'Auth/forgot_password');
    minta($j, 'Auth/kirim_tautan_sandi', ['email' => $eA]);
    $token2 = $token_dari($terakhir($eA));
    jalan('UPDATE usr_akun SET token_sandi_kedaluwarsa=? WHERE id=?', [date('Y-m-d H:i:s', time() - 1), $A]);
    $r = minta(jar(), 'atur-sandi/' . $token2);
    cek($token2 !== '' && ada_pesan($r['badan'], 'error', 'tidak berlaku'), 'Tautan yang lewat 30 menit ditolak');
    minta($j, 'Auth/kirim_tautan_sandi', ['email' => $eA]);
    minta($j, 'Auth/kirim_tautan_sandi', ['email' => $eA]);
    $r = minta($j, 'Auth/kirim_tautan_sandi', ['email' => $eA]);
    cek($r['kode'] === 429 && count($surel($eA)) === 5, 'Permintaan keempat dalam sejam untuk email yang sama ditolak (429) tanpa email');
} catch (Throwable $e) {
    cek(FALSE, 'Suite berhenti: ' . $e->getMessage());
} finally {
    if ($akun_uji) {
        $daftar = implode(',', array_map('intval', $akun_uji));
        $db->query("DELETE FROM sys_jejak_audit WHERE id > $audit_awal AND objek_tipe='usr_akun' AND objek_id IN ('" . implode("','", $akun_uji) . "')");
        $db->query("DELETE FROM usr_akun WHERE id IN ($daftar)");
    }
    foreach ($ember_asli as $kunci => $baris) {
        jalan('DELETE FROM sys_batas_laju WHERE kunci=?', [$kunci]);
        if ($baris) { jalan('INSERT INTO sys_batas_laju (kunci, jendela_mulai_at, jumlah_gagal) VALUES (?,?,?)', array_values($baris)); }
    }
    foreach ($jar_dibuat as $f) { @unlink($f); }
    foreach ($berkas_surel as $e) { @unlink(APP_ROOT . '/application/cache/surel_uji/' . sha1(strtolower($e)) . '.json'); }
    echo "Akun uji tersisa: " . (int) satu('SELECT COUNT(*) n FROM usr_akun WHERE email LIKE ?', [$TAG . '%'])['n'] . "\n";
}
echo "RINGKASAN: {$GLOBALS['total']} pemeriksaan, {$GLOBALS['gagal']} gagal\n";
exit($GLOBALS['gagal'] ? 1 : 0);
