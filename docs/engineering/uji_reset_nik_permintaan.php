<?php
require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php'; // lokasi .env (luar akar dulu)
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Permintaan reset NIK dari warga ke Super Admin (permintaan user 6 Okt 2026).
 *
 *   php docs/engineering/uji_reset_nik_permintaan.php
 *
 * Dijaga:
 *   1. Warga yang NIK-nya terkunci di Profil Saya bisa mengajukan reset dengan alasan (10-500 karakter),
 *      satu permintaan menunggu per akun, endpoint hanya POST.
 *   2. Super Admin melihat permintaan di Akses Staf; Tolak mengirim catatan yang tampil ke warga; Setujui
 *      membuka NIK (usr_akun.nik dan nik_lookup_hash dikosongkan) sehingga isian NIK terbuka lagi.
 *   3. Akun yang punya pengajuan terkirim tidak bisa direset lewat permintaan (arsip tetap utuh).
 *   4. Bug lama: Reset NIK langsung dulu hanya menghapus profil pendataan; akun yang NIK-nya hanya di
 *      usr_akun ditolak "belum terhubung dengan NIK" dan NIK di akun tetap terkunci.
 *   5. Super Admin tahu ada permintaan (angka menu Akses Staf, Pusat Pemberitahuan, pita dasbor), dan
 *      warga menerima email hasil keputusan (mode uji: application/cache/surel_uji/, alamat *.test).
 *
 * Akun uji (@reset-nik.test) dan baris jejak audit buatan suite dibuat lalu dihapus sendiri. NIK uji
 * berawalan 3399 (kabupaten fiktif), bukan NIK warga.
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

define('FCPATH', APP_ROOT . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'development');
require_once APPPATH . 'libraries/Encryption_lib.php';
$enc = new Encryption_lib();

$TAG = 'resetnik' . bin2hex(random_bytes(3));
$SANDI = 'Uji#' . bin2hex(random_bytes(5)) . 'A1';
$HASH = password_hash($SANDI, PASSWORD_BCRYPT);
$AGEN_SANDI = 'AgenUji!2026'; // sandi mainan seed_agen_peran.php, hanya ada di DB lokal
$akun_uji = [];
/** Akun warga dengan NIK terkunci HANYA di usr_akun (tanpa profil pendataan), keadaan yang dulu tak bisa direset. */
function warga_ber_nik($urut) {
    global $TAG, $HASH, $akun_uji, $enc;
    $nik = '3399' . str_pad((string) random_int(0, 999999999999), 12, '0', STR_PAD_LEFT);
    $isi = ['nama' => 'Warga Reset ' . $urut, 'email' => $TAG . '_' . $urut . '@reset-nik.test', 'kata_sandi' => $HASH,
        'peran' => 'warga', 'status' => 'active', 'profil_lengkap' => 1, 'sandi_diganti_at' => date('Y-m-d H:i:s'),
        'sandi_kedaluwarsa_at' => date('Y-m-d H:i:s', strtotime('+90 days')), 'created_at' => date('Y-m-d H:i:s'),
        'nik' => $enc->encrypt($nik), 'nik_lookup_hash' => $enc->deterministic_hash($nik)];
    $id = jalan('INSERT INTO usr_akun (' . implode(',', array_keys($isi)) . ') VALUES (' . implode(',', array_fill(0, count($isi), '?')) . ')', array_values($isi));
    $akun_uji[] = (int) $id;
    return [(int) $id, $isi['email']];
}

function minta($jar, $path, $post = NULL, $metode = NULL) {
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
function jar() { global $jar_dibuat; return $jar_dibuat[] = tempnam(sys_get_temp_dir(), 'urn'); }
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
$ember_asli = [];
function ember_ip($policy) {
    global $ember_asli;
    foreach (['127.0.0.1', '::1', '0000000000000000/64'] as $ip) {
        $kunci = hash('sha256', "$policy:ip:$ip");
        if ( ! array_key_exists($kunci, $ember_asli)) { $ember_asli[$kunci] = satu('SELECT kunci, jendela_mulai_at, jumlah_gagal FROM sys_batas_laju WHERE kunci=?', [$kunci]); }
        jalan('DELETE FROM sys_batas_laju WHERE kunci=?', [$kunci]);
    }
}
$hash_akun = fn($id) => satu('SELECT nik_lookup_hash h FROM usr_akun WHERE id=?', [$id])['h'] ?? NULL;
$jumlah_minta = fn($id) => (int) satu("SELECT COUNT(*) n FROM sys_jejak_audit WHERE aksi='reset_nik_diajukan' AND pelaku_id=?", [$id])['n'];

$audit_awal = (int) satu('SELECT COALESCE(MAX(id), 0) n FROM sys_jejak_audit')['n'];
/** Email pemberitahuan yang "terkirim" ke alamat uji (Surel_pemberitahuan menulisnya ke berkas di luar production). */
$surel = function ($email) { $f = APP_ROOT . '/application/cache/surel_uji/' . sha1(strtolower($email)) . '.json'; return json_decode((string) @file_get_contents($f), TRUE) ?: []; };
$berkas_surel = [];
try {
    echo "== 1. Warga mengajukan reset NIK\n";
    [$A, $eA] = warga_ber_nik('a');
    ember_ip('login');
    $jA = masuk($eA, $SANDI);
    $r = minta($jA, 'akun/profil');
    cek(strpos($r['badan'], 'data-ajukan-reset-nik') !== FALSE && strpos($r['badan'], 'name="nik"') === FALSE,
        'Profil Saya: NIK terkunci menampilkan tombol Ajukan reset NIK');
    cek(minta($jA, 'akun/ajukan-reset-nik')['kode'] === 405, 'Endpoint ajukan reset NIK hanya POST (GET dijawab 405)');
    $r = minta($jA, 'akun/ajukan-reset-nik', ['alasan' => 'pendek']);
    cek(ada_pesan($r['badan'], 'error', '10 sampai 500') && $jumlah_minta($A) === 0, 'Alasan kurang dari 10 karakter ditolak, tidak ada permintaan tercatat');
    $r = minta($jA, 'akun/ajukan-reset-nik', ['alasan' => 'Salah ketik dua angka terakhir NIK saat mendaftar']);
    cek(ada_pesan($r['badan'], 'success', 'terkirim') && $jumlah_minta($A) === 1, 'Alasan sah: permintaan tercatat di jejak audit');
    cek(strpos($r['badan'], 'data-reset-nik-menunggu') !== FALSE && strpos($r['badan'], 'data-ajukan-reset-nik') === FALSE,
        'Profil Saya: status "sedang ditinjau" menggantikan tombol');
    minta($jA, 'akun/ajukan-reset-nik', ['alasan' => 'Mengirim ulang permintaan yang sama sekali lagi']);
    cek($jumlah_minta($A) === 1, 'Permintaan kedua saat masih menunggu tidak digandakan');
    $id_minta = (int) satu("SELECT id FROM sys_jejak_audit WHERE aksi='reset_nik_diajukan' AND pelaku_id=? ORDER BY id DESC", [$A])['id'];

    echo "\n== 2. Super Admin menolak lalu menyetujui\n";
    ember_ip('login');
    $jAdm = masuk('agen_admin@agen.test', $AGEN_SANDI);
    $r = minta($jAdm, 'Admin_Users/permintaan_nik');
    cek(strpos($r['badan'], 'data-reset-nik-minta') !== FALSE && strpos($r['badan'], $eA) !== FALSE && strpos($r['badan'], 'Warga Reset a') !== FALSE
        && strpos($r['badan'], 'Salah ketik dua angka terakhir') !== FALSE, 'Permintaan NIK Warga menampilkan permintaan, nama dan email pemohon, serta alasan');
    cek(strpos($r['badan'], 'data-badge-modul="permintaan_nik"') !== FALSE, 'Menu Permintaan NIK Warga memasang angka permintaan yang menunggu');
    $r = minta($jAdm, 'Admin_Users');
    cek(strpos($r['badan'], 'data-reset-nik-minta') === FALSE && strpos($r['badan'], 'data-badge-modul="users"') === FALSE,
        'Akses Staf tidak lagi memuat permintaan NIK maupun angkanya');
    $r = minta($jAdm, 'pemberitahuan');
    cek(strpos($r['badan'], 'data-modul-pemberitahuan="permintaan_nik"') !== FALSE && strpos($r['badan'], 'Reset NIK') !== FALSE,
        'Pusat Pemberitahuan memuat permintaan reset NIK');
    $r = minta($jAdm, 'Admin_Dashboard');
    cek(strpos($r['badan'], 'Permintaan NIK') !== FALSE, 'Pita "Perlu tindakan" di dasbor menyebut Permintaan NIK');
    $r = minta($jAdm, 'Admin_Users/putuskan_reset_nik', ['id' => $id_minta, 'keputusan' => 'tolak', 'alasan' => 'Lampirkan foto KTP lewat menu Aduan dulu']);
    cek(ada_pesan($r['badan'], 'success', 'ditolak') && $hash_akun($A) !== NULL, 'Tolak: keputusan tersimpan, NIK tetap terkunci');
    $berkas_surel[] = $eA;
    $daftar_tolak = $surel($eA);
    $m = end($daftar_tolak) ?: [];
    cek(strpos($m['subjek'] ?? '', 'belum dapat disetujui') !== FALSE && ($m['catatan'] ?? '') === 'Lampirkan foto KTP lewat menu Aduan dulu'
        && strpos($m['tautan'] ?? '', 'Auth/login?next=' . rawurlencode('akun/profil')) !== FALSE && ! preg_match('/\d{16}/', json_encode($m)),
        'Email penolakan ke warga: subjek, catatan petugas, tautan Profil Saya, tanpa NIK');
    $r = minta($jA, 'akun/profil');
    cek(strpos($r['badan'], 'data-reset-nik-ditolak') !== FALSE && strpos($r['badan'], 'Lampirkan foto KTP') !== FALSE
        && strpos($r['badan'], 'data-ajukan-reset-nik') !== FALSE, 'Warga melihat catatan penolakan dan bisa mengajukan lagi');
    minta($jA, 'akun/ajukan-reset-nik', ['alasan' => 'Foto KTP sudah saya kirim lewat menu Aduan']);
    $id_minta2 = (int) satu("SELECT id FROM sys_jejak_audit WHERE aksi='reset_nik_diajukan' AND pelaku_id=? ORDER BY id DESC", [$A])['id'];
    cek($id_minta2 > $id_minta, 'Permintaan baru sesudah ditolak tercatat');
    $r = minta($jAdm, 'Admin_Users/putuskan_reset_nik', ['id' => $id_minta2, 'keputusan' => 'setuju']);
    $akunA = satu('SELECT nik, nik_lookup_hash FROM usr_akun WHERE id=?', [$A]);
    cek(ada_pesan($r['badan'], 'success', 'disetujui') && $akunA['nik'] === NULL && $akunA['nik_lookup_hash'] === NULL,
        'Setujui: NIK di akun (usr_akun.nik dan sidiknya) dikosongkan');
    $daftar_surel = $surel($eA);
    cek(count($daftar_surel) === 2 && strpos(end($daftar_surel)['subjek'] ?? '', 'disetujui') !== FALSE
        && strpos(end($daftar_surel)['subjek'] ?? '', 'belum') === FALSE, 'Email persetujuan terkirim ke warga');
    $r = minta($jAdm, 'Admin_Users/permintaan_nik');
    cek(strpos($r['badan'], 'data-reset-nik-minta') === FALSE && strpos($r['badan'], 'data-permintaan-nik-kosong') !== FALSE,
        'Sesudah diputuskan, permintaan hilang dan halaman menampilkan keadaan kosong');
    cek((int) satu("SELECT COUNT(*) n FROM sys_jejak_audit WHERE aksi='nik_warga_direset' AND objek_id=? AND id > ?", [$A, $audit_awal])['n'] === 1,
        'Jejak audit nik_warga_direset tercatat');
    $tautan = (string) (end($daftar_surel)['tautan'] ?? '');
    $r = minta($jA, substr($tautan, strpos($tautan, 'Auth/login')));
    cek(strpos($tautan, 'next=' . rawurlencode('akun/profil?isi=nik')) !== FALSE && strpos($r['badan'], 'data-sorot-nik') !== FALSE
        && strpos($r['badan'], 'data-reset-nik-menunggu') === FALSE,
        'Tautan email dengan sesi warga terbuka langsung mengantar ke Profil Saya, isian NIK terbuka dan disorot');
    $r = minta($jAdm, 'Admin_Users/putuskan_reset_nik', ['id' => $id_minta2, 'keputusan' => 'setuju']);
    cek(ada_pesan($r['badan'], 'error', 'sudah diputuskan'), 'Permintaan yang sudah diputuskan tidak bisa diputuskan ulang');

    echo "\n== 3. Akun dengan pengajuan terkirim tidak bisa direset\n";
    [$B, $eB] = warga_ber_nik('b');
    jalan("INSERT INTO sf_penilaian_perumahan (user_id, status, submitted_at, created_at, updated_at) VALUES (?, 'submitted', NOW(), NOW(), NOW())", [$B]);
    ember_ip('login');
    $jB = masuk($eB, $SANDI);
    minta($jB, 'akun/ajukan-reset-nik', ['alasan' => 'NIK saya keliru satu digit di tengah']);
    $id_b = (int) satu("SELECT id FROM sys_jejak_audit WHERE aksi='reset_nik_diajukan' AND pelaku_id=?", [$B])['id'];
    $r = minta($jAdm, 'Admin_Users/permintaan_nik');
    cek(strpos($r['badan'], 'pengajuan terkirim, tidak dapat direset') !== FALSE, 'Permintaan NIK Warga menandai pemohon yang punya pengajuan terkirim');
    $r = minta($jAdm, 'Admin_Users/putuskan_reset_nik', ['id' => $id_b, 'keputusan' => 'setuju']);
    cek(ada_pesan($r['badan'], 'error', 'Belum disetujui') && $hash_akun($B) !== NULL
        && satu("SELECT id FROM sys_jejak_audit WHERE objek_tipe='reset_nik' AND objek_id=?", [$id_b]) === NULL,
        'Setujui ditolak server: NIK tetap, permintaan tetap menunggu');

    echo "\n== 4. Reset NIK langsung untuk NIK yang hanya di akun (bug lama)\n";
    [$C, $eC] = warga_ber_nik('c');
    $r = minta($jAdm, 'Admin_Users/reset_nik', ['id' => $C, 'alasan' => 'Warga melapor salah NIK lewat telepon dinas']);
    cek(ada_pesan($r['badan'], 'success', 'berhasil direset') && $hash_akun($C) === NULL,
        'Reset langsung berhasil dan NIK di akun ikut dikosongkan');
} catch (Throwable $e) {
    cek(FALSE, 'Suite berhenti: ' . $e->getMessage());
} finally {
    if ($akun_uji) {
        $daftar = implode(',', array_map('intval', $akun_uji));
        $minta_uji = implode(',', array_map('intval', array_column($db->query("SELECT id FROM sys_jejak_audit WHERE id > $audit_awal AND aksi='reset_nik_diajukan' AND pelaku_id IN ($daftar)")->fetch_all(MYSQLI_ASSOC), 'id'))) ?: '0';
        $db->query("DELETE FROM sys_jejak_audit WHERE id > $audit_awal AND ((objek_tipe='reset_nik' AND objek_id IN ($minta_uji)) OR id IN ($minta_uji)
            OR (objek_tipe='usr_akun' AND objek_id IN ('" . implode("','", $akun_uji) . "') AND aksi IN ('nik_warga_direset','reset_nik_ditolak')))");
        foreach (['sf_penilaian_perumahan', 'sf_profil_warga'] as $t) { $db->query("DELETE FROM $t WHERE user_id IN ($daftar)"); }
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
