<?php
/**
 * Uji KATALOG PROGRAM - P2 master & kendali.
 *
 *   php docs/engineering/uji_katalog_program.php
 *
 * Layar ini menghadapi kenyataan yang sudah ada, bukan yang seharusnya:
 * katalog program hidup di DUA sumber dan keduanya nyata.
 *
 *   `sf_programs`                    | `Smart_filter::master_programs()`
 *   ---------------------------------|---------------------------------------
 *   `is_active` MENGGERBANGI          | judul di kartu hasil diagnosa
 *   pengajuan (Housing_assessment     | aturan pencocokan desil -> program
 *   _model:500)                       |
 *   `nama_program` -> antrean & /akun |
 *
 * Lima janji yang dijaga:
 *
 *   1. `is_active` BUKAN HIASAN. Ia betul-betul menolak pengajuan baru.
 *      Diuji dari MODEL-nya, bukan dari layar - sakelar yang cuma mengubah
 *      badge adalah persis bug `usr_users.status` yang sudah pernah terjadi
 *      (ditulis, tidak pernah dibaca).
 *   2. `kode_program` TIDAK BISA DIUBAH lewat endpoint. Ia identitas: dicari
 *      `Housing_assessment_model:748` dan dipetakan sebagai literal di
 *      `Smart_filter`. Ditembakkan langsung, bukan diperiksa dari formulir.
 *   3. SELISIH NAMA DITAMPILKAN, bukan disembunyikan. Layar katalog yang
 *      berpura-pura tabelnya satu-satunya sumber akan membuat orang mengira
 *      mengganti nama di sini juga mengganti nama di hasil diagnosa.
 *   4. CHECKBOX YANG TIDAK DICENTANG = 0, bukan "biarkan". Membacanya sebagai
 *      "tidak ada perubahan" membuat program aktif tidak pernah bisa dimatikan.
 *   5. TIAP PERUBAHAN TERCATAT, dan penonaktifan disebut khusus.
 *
 * Seluruh perubahan pada `sf_programs` DIPULIHKAN, termasuk kalau prosesnya
 * mati di tengah.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('ENV_PATH', APP_ROOT . '/.env');
define('SANDI', 'UjiKatalog!2026');
define('CAP', 'UJIKAT' . date('YmdHis') . mt_rand(100, 999));

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;
$GLOBALS['users']  = [];
$GLOBALS['jar']    = [];
$GLOBALS['pulih']  = [];   // [id, nama, deskripsi, is_active]

function cek($kondisi, $label) {
    $GLOBALS['uji_total']++;
    echo ($kondisi ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $kondisi) { $GLOBALS['uji_gagal']++; }
    return (bool) $kondisi;
}
function wajib($kondisi, $label) {
    if ( ! cek($kondisi, $label)) { bersihkan(); fwrite(STDERR, "Berhenti: prasyarat gagal.\n"); exit(1); }
}

function env_config($path) {
    $out = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $baris) {
        $baris = trim($baris);
        if ($baris === '' || $baris[0] === '#' || strpos($baris, '=') === FALSE) { continue; }
        [$k, $v] = explode('=', $baris, 2);
        $k = trim($k);
        if ( ! isset($out[$k])) { $out[$k] = trim($v); }
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
function tulis($sql, $p = []) { return (int) (q($sql, $p)['__id'] ?? 0); }
function nilai($sql, $p = []) { $r = q($sql, $p); return $r && ! isset($r['__id']) ? reset($r) : NULL; }

function sesi($nama) {
    if ( ! isset($GLOBALS['jar'][$nama])) { $GLOBALS['jar'][$nama] = tempnam(sys_get_temp_dir(), 'ujkt_'); }
    return $GLOBALS['jar'][$nama];
}

function http($nama, $path, ?array $post = NULL, $ajax = FALSE) {
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    $opt = [
        CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_COOKIEJAR => sesi($nama),
        CURLOPT_COOKIEFILE => sesi($nama), CURLOPT_FOLLOWLOCATION => TRUE, CURLOPT_TIMEOUT => 30,
    ];
    if ($ajax) { $opt[CURLOPT_HTTPHEADER] = ['X-Requested-With: XMLHttpRequest']; }
    if ($post !== NULL) { $opt[CURLOPT_POST] = TRUE; $opt[CURLOPT_POSTFIELDS] = http_build_query($post); }
    curl_setopt_array($ch, $opt);
    $body = (string) curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return ['code' => (int) $info['http_code'], 'body' => $body, 'url' => (string) $info['url']];
}

function csrf($nama, $path) {
    $r = http($nama, $path);
    if (preg_match('/name="csrf_kpkp_token"\s+value="([^"]+)"/', $r['body'], $m)) { return $m[1]; }
    if (preg_match('/<meta\s+name="csrf-token-hash"\s+content="([^"]+)"/', $r['body'], $m)) { return $m[1]; }
    bersihkan();
    fwrite(STDERR, "FATAL: token CSRF tidak ditemukan di /{$path}.\n");
    exit(1);
}

function login($nama, $email) {
    $r = http($nama, 'Auth/do_login', [
        'csrf_kpkp_token' => csrf($nama, 'Auth/login'), 'email' => $email, 'password' => SANDI,
    ], TRUE);
    return (json_decode($r['body'], TRUE)['status'] ?? '') === 'success';
}

function buat_akun($peran, $suffix) {
    $email = 'uji_katalog_' . $suffix . '_' . time() . '_' . mt_rand(1000, 9999) . '@example.test';
    $id = tulis(
        'INSERT INTO usr_users (email,password,name,username,role,status,profile_completed,created_at)
         VALUES (?,?,?,?,?, "active",1,NOW())',
        [$email, password_hash(SANDI, PASSWORD_BCRYPT), 'Uji Katalog ' . $suffix,
         'uji_katalog_' . $suffix . '_' . mt_rand(10000, 99999), $peran]
    );
    $GLOBALS['users'][] = $id;
    return [$id, $email];
}

/** Simpan keadaan asli satu program SEBELUM disentuh. */
function jaga_program($id) {
    $r = q('SELECT id,kode_program,nama_program,deskripsi_singkat,is_active FROM sf_programs WHERE id=?', [$id]);
    $GLOBALS['pulih'][] = $r;
    return $r;
}

function bersihkan() {
    if (empty($GLOBALS['db'])) { return; }
    /**
     * Program dipulihkan DULU - ini satu-satunya yang menyentuh data nyata.
     * Kode ikut dipulihkan, bukan cuma nama: uji di bawah sengaja MENEMBAKKAN
     * kode baru, dan kalau penjaganya kelak dilonggarkan, kodenya benar-benar
     * berubah dan pemulih berbasis id inilah yang menyelamatkannya.
     */
    foreach ($GLOBALS['pulih'] as $p) {
        if (empty($p['id'])) { continue; }
        q('UPDATE sf_programs SET kode_program=?, nama_program=?, deskripsi_singkat=?, is_active=? WHERE id=?',
            [$p['kode_program'], $p['nama_program'], $p['deskripsi_singkat'], $p['is_active'], $p['id']]);
    }
    $GLOBALS['pulih'] = [];
    foreach ($GLOBALS['users'] as $id) {
        q('DELETE FROM sys_jejak_audit WHERE actor_id=?', [$id]);
        q('DELETE FROM usr_users WHERE id=?', [$id]);
    }
    foreach ($GLOBALS['jar'] as $j) { @unlink($j); }
    $GLOBALS['users'] = [];
}
register_shutdown_function('bersihkan');

// ==========================================================================

if ( ! is_file(ENV_PATH)) { die(".env tidak ditemukan.\n"); }
$env = env_config(ENV_PATH);
$GLOBALS['db'] = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
if ($GLOBALS['db']->connect_error) { die("Koneksi DB gagal.\n"); }

define('MULAI', date('Y-m-d H:i:s', time() - 1));

echo "=== UJI KATALOG PROGRAM ===\n";
echo 'Target: ' . BASE_URL . " | DB: {$env['DB_NAME']}\n\n";

echo "== 0. Prasyarat ==\n";
$jml = (int) nilai('SELECT COUNT(*) c FROM sf_programs');
wajib($jml > 0, "Katalog terisi ({$jml} program)");

[$idA, $emailA] = buat_akun('admin', 'super');
[$idU, $emailU] = buat_akun('user', 'warga');
wajib(login('a', $emailA), 'Login superadmin');
wajib(login('u', $emailU), 'Login warga');

// ------------------------------------------------ 1. LAYAR
echo "\n== 1. Layar menampilkan katalog penuh ==\n";
$hal = http('a', 'Admin_Katalog_Program');
wajib($hal['code'] === 200 && strpos($hal['url'], 'Auth/login') === FALSE, 'Layar terbuka untuk superadmin');
preg_match('/<tbody[^>]*>(.*?)<\/tbody>/s', $hal['body'], $tb);
cek(preg_match_all('/<tr[^>]*>/', $tb[1] ?? '', $x) === $jml, "Tabel memuat {$jml} program");
cek(stripos($hal['body'], 'tidak bisa diubah') !== FALSE, 'Layar menyatakan kodenya tidak bisa diubah');
cek(strpos($hal['body'], 'batas_penghasilan_max') !== FALSE
    && stripos($hal['body'], 'tidak dibaca kode mana pun') !== FALSE,
    'Kolom tanpa pembaca dinyatakan apa adanya, bukan ditampilkan seolah berfungsi');

// ------------------------------------------------ 2. SELISIH DUA SUMBER
echo "\n== 2. Selisih dua sumber ditampilkan ==\n";
/**
 * Dihitung dari sumbernya sendiri, bukan dipatok angka: begitu seseorang
 * membereskan selisihnya, uji yang mematok angka akan merah untuk perbaikan.
 */
$judul = [];
foreach (muat_master() as $p) { $judul[$p['kode']][] = $p['title']; }
$selisih = 0;
foreach ($GLOBALS['db']->query('SELECT kode_program, nama_program FROM sf_programs')->fetch_all(MYSQLI_ASSOC) as $r) {
    if (isset($judul[$r['kode_program']]) && ! in_array($r['nama_program'], $judul[$r['kode_program']], TRUE)) { $selisih++; }
}
if ($selisih > 0) {
    cek(strpos($hal['body'], $selisih . ' program</b> memakai nama berbeda') !== FALSE,
        "Selisih {$selisih} program disebut di layar");
    cek(stripos($hal['body'], 'dua sumber') !== FALSE,
        'Dan sebabnya dijelaskan - katalognya memang punya dua sumber');
} else {
    cek(TRUE, 'Nol selisih nama (tidak ada yang perlu ditampilkan)');
    cek(TRUE, '(dilewati)');
}
// Judul versi Smart_filter memang ikut tercetak, apa pun keadaannya.
$satu_judul = reset($judul)[0] ?? '';
cek($satu_judul !== '' && strpos($hal['body'], $satu_judul) !== FALSE,
    "Judul versi hasil diagnosa ikut ditampilkan ('{$satu_judul}')");

function muat_master() {
    if ( ! defined('BASEPATH')) { define('BASEPATH', 'uji'); }
    require_once APP_ROOT . '/application/libraries/Smart_filter.php';
    $o = (new ReflectionClass('Smart_filter'))->newInstanceWithoutConstructor();
    return $o->master_programs();
}

// ------------------------------------------------ 3. KODE TIDAK BISA DIUBAH
echo "\n== 3. Kode program TIDAK bisa diubah lewat endpoint ==\n";
$target = q('SELECT id FROM sf_programs WHERE is_active=1 ORDER BY id ASC LIMIT 1');
$pid = (int) $target['id'];
$asli = jaga_program($pid);

http('a', 'Admin_Katalog_Program/ubah', [
    'csrf_kpkp_token' => csrf('a', 'Admin_Katalog_Program'),
    'id' => $pid, 'nama_program' => 'Nama Baru ' . CAP,
    'deskripsi_singkat' => 'Deskripsi baru ' . CAP, 'is_active' => '1',
    // Diselundupkan:
    'kode_program' => 'kode_selundupan_' . CAP,
]);
cek(nilai('SELECT kode_program FROM sf_programs WHERE id=?', [$pid]) === $asli['kode_program'],
    "Kode ASLI ('{$asli['kode_program']}') tidak berubah - POST kode_program diabaikan");
cek(nilai('SELECT nama_program FROM sf_programs WHERE id=?', [$pid]) === 'Nama Baru ' . CAP,
    'Sementara namanya memang berubah - permintaannya benar-benar diproses');

// ------------------------------------------------ 4. is_active MENGGERBANGI
echo "\n== 4. is_active bukan hiasan ==\n";
/**
 * Diuji dari MODEL-nya, bukan dari layar. `Housing_assessment_model:500`
 * menolak pengajuan kalau `is_active !== 1`; sakelar yang cuma mengubah badge
 * adalah persis bug `usr_users.status` yang sudah pernah terjadi di repo ini -
 * ditulis rapi, tidak pernah dibaca.
 */
$kode_model = file_get_contents(APP_ROOT . '/application/models/Housing_assessment_model.php');
cek((bool) preg_match("/\(int\)\s*\\\$recommendation\['is_active'\]\s*!==\s*1/", $kode_model),
    'Model pengajuan benar-benar membaca is_active sebagai syarat');
cek((bool) preg_match("/->where\('is_active',\s*1\)/", file_get_contents(APP_ROOT . '/application/models/Program_model.php')),
    'Program_model juga menyaring is_active');

// Matikan lewat layar: checkbox TIDAK dikirim = 0.
http('a', 'Admin_Katalog_Program/ubah', [
    'csrf_kpkp_token' => csrf('a', 'Admin_Katalog_Program'),
    'id' => $pid, 'nama_program' => 'Nama Baru ' . CAP,
    'deskripsi_singkat' => 'Deskripsi baru ' . CAP,
    // 'is_active' sengaja TIDAK dikirim - persis yang dilakukan browser.
]);
cek((int) nilai('SELECT is_active FROM sf_programs WHERE id=?', [$pid]) === 0,
    'Checkbox yang tidak dicentang = NONAKTIF, bukan "biarkan nilai lama"');

http('a', 'Admin_Katalog_Program/ubah', [
    'csrf_kpkp_token' => csrf('a', 'Admin_Katalog_Program'),
    'id' => $pid, 'nama_program' => 'Nama Baru ' . CAP,
    'deskripsi_singkat' => 'Deskripsi baru ' . CAP, 'is_active' => '1',
]);
cek((int) nilai('SELECT is_active FROM sf_programs WHERE id=?', [$pid]) === 1, 'Dan bisa dinyalakan lagi');

// ------------------------------------------------ 5. JEJAK
echo "\n== 5. Perubahan tercatat, penonaktifan disebut khusus ==\n";
cek((int) nilai("SELECT COUNT(*) c FROM sys_jejak_audit WHERE aksi='program_diubah' AND actor_id=? AND created_at >= ?",
    [$idA, MULAI]) >= 3, 'Tiap perubahan tercatat sebagai program_diubah');
cek((int) nilai("SELECT COUNT(*) c FROM sys_jejak_audit WHERE aksi='program_diubah' AND actor_id=? AND ringkasan LIKE ?",
    [$idA, '%DINONAKTIFKAN%']) === 1,
    'Penonaktifan ditandai khusus di ringkasannya - bukan tenggelam sebagai "diubah"');

// ------------------------------------------------ 6. YANG DITOLAK
echo "\n== 6. Yang harus ditolak ==\n";
cek(http('a', 'Admin_Katalog_Program/ubah?id=' . $pid . '&nama_program=lewat+get')['code'] === 404,
    'GET ke endpoint tulis dibalas 404');

http('a', 'Admin_Katalog_Program/ubah', ['id' => $pid, 'nama_program' => 'Tanpa Token']);
cek(nilai('SELECT nama_program FROM sf_programs WHERE id=?', [$pid]) !== 'Tanpa Token',
    'POST tanpa token CSRF tidak mengubah apa pun');

$sebelum = nilai('SELECT nama_program FROM sf_programs WHERE id=?', [$pid]);
http('a', 'Admin_Katalog_Program/ubah', [
    'csrf_kpkp_token' => csrf('a', 'Admin_Katalog_Program'),
    'id' => $pid, 'nama_program' => '   ', 'is_active' => '1']);
cek(nilai('SELECT nama_program FROM sf_programs WHERE id=?', [$pid]) === $sebelum, 'Nama kosong ditolak');

// Peran lain: DIALIHKAN, jadi kodenya 200 dari halaman lain. Buktinya DB.
http('u', 'Admin_Katalog_Program/ubah', [
    'csrf_kpkp_token' => csrf('u', 'umum/aduan'),
    'id' => $pid, 'nama_program' => 'Diubah Warga', 'is_active' => '1']);
cek(nilai('SELECT nama_program FROM sf_programs WHERE id=?', [$pid]) !== 'Diubah Warga',
    'Warga TIDAK bisa mengubah katalog (dicek dari DB, bukan kode HTTP)');
cek(strpos(http('u', 'Admin_Katalog_Program')['body'], 'Nama di hasil diagnosa') === FALSE,
    'Dan tidak mendapat layarnya');

echo "\nRINGKASAN: {$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
