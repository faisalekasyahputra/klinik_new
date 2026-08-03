<?php
/**
 * Uji PANTAU REKAM DATA — pandangan superadmin lintas kabupaten.
 *
 *   php docs/engineering/uji_pantau_rekam_data.php
 *
 * Layar ini lahir dari klaim dinas "belum ada rekap/submit" (revisi 3 Agt 2026,
 * butir 10) yang TIDAK cocok dengan kode: seluruh alurnya sudah ada sejak 30
 * Jul, tapi semuanya milik `admin_kabkota` dan `admin_bidang`. Superadmin —
 * kursi yang dipakai reviewer — tidak punya satu pun layar rekam data.
 *
 * Lima janji yang dijaga di sini, semuanya rusak SENYAP:
 *
 *   1. YANG BELUM MELAPOR IKUT TERLIHAT. Ini satu-satunya alasan layar ini
 *      dibangun terpisah, bukan menumpang layar yang sudah ada: semua layar
 *      lain bertolak dari `rd_laporan`, jadi kabupaten yang tidak mengirim apa
 *      pun TIDAK PUNYA BARIS untuk ditampilkan. Papan yang cuma memuat 3 dari
 *      35 kabupaten terlihat benar dan menjawab pertanyaan yang salah.
 *   2. "DITERIMA" DITURUNKAN, BUKAN DIBACA. `rd_laporan.status` hanya mengenal
 *      draft|terkirim|perlu_perbaikan; diterima = `terkirim` + `reviewed_at`
 *      terisi. Aturan turunan yang disalin ke tiap layar akan menyimpang, dan
 *      yang menyimpang berarti laporan diterima terbaca masih menunggu.
 *   3. READ-ONLY BERARTI NOL ENDPOINT TULIS, bukan nol tombol. Diperiksa
 *      statis atas kelasnya, karena method tulis yang ditambahkan enam bulan
 *      lagi akan lolos semua uji fungsional: ia BEKERJA, itu masalahnya.
 *   4. MENAMBAH PEMBACA TIDAK MENCABUT KEWENANGAN PENULIS. View detail kini
 *      dipakai dua layar dan default `boleh_putuskan`-nya FALSE — kalau
 *      `Rekam_Tinjauan` lupa mengirimnya, tombol keputusan Admin Bidang HILANG
 *      tanpa satu pun galat. Diuji dari sisi Admin Bidang, bukan cuma superadmin.
 *   5. PENOLAKAN PERAN DIBUKTIKAN LEWAT ISI HALAMAN, bukan kode HTTP —
 *      permintaan dari peran salah DIALIHKAN, jadi curl yang mengikuti redirect
 *      menerima 200 dari halaman lain.
 *
 * Data & akun dibuat sendiri, dibersihkan sendiri. Email uji berakhiran
 * @example.test supaya sensus kebocoran di jalankan_semua.php melihatnya.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('ENV_PATH', APP_ROOT . '/.env');
define('SANDI', 'UjiPantau!2026');
define('CAP', 'UJIPANTAU' . date('YmdHis') . mt_rand(100, 999));
define('TAHUN', (int) date('Y') + 3);      // tahun sendiri, supaya tak bercampur data lain
define('TW', 2);

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;
$GLOBALS['users']   = [];
$GLOBALS['laporan'] = [];
$GLOBALS['jar']     = [];

function cek($kondisi, $label) {
    $GLOBALS['uji_total']++;
    echo ($kondisi ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $kondisi) { $GLOBALS['uji_gagal']++; }
    return (bool) $kondisi;
}

function wajib($kondisi, $label) {
    if ( ! cek($kondisi, $label)) {
        bersihkan();
        fwrite(STDERR, "Berhenti: prasyarat gagal.\n");
        exit(1);
    }
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
    if ( ! isset($GLOBALS['jar'][$nama])) { $GLOBALS['jar'][$nama] = tempnam(sys_get_temp_dir(), 'ujpr_'); }
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

function buat_akun($peran, $suffix, $kab = NULL, $bidang = NULL) {
    $email = 'uji_pantau_' . $suffix . '_' . time() . '_' . mt_rand(1000, 9999) . '@example.test';
    $id = tulis(
        'INSERT INTO usr_users (email,password,name,username,role,kabupaten_id,bidang_kode,status,profile_completed,created_at)
         VALUES (?,?,?,?,?,?,?, "active",1,NOW())',
        [$email, password_hash(SANDI, PASSWORD_BCRYPT), 'Uji Pantau ' . $suffix,
         'uji_pantau_' . $suffix . '_' . mt_rand(10000, 99999), $peran, $kab, $bidang]
    );
    $GLOBALS['users'][] = $id;
    return [$id, $email];
}

function buat_laporan($domain, $kab, $status, $ditinjau = FALSE) {
    $id = tulis(
        'INSERT INTO rd_laporan (domain,kabupaten_id,tahun,triwulan,status,current_step,submitted_at,reviewed_at,created_at,updated_at)
         VALUES (?,?,?,?,?,"selesai", ?, ?, NOW(), NOW())',
        [$domain, $kab, TAHUN, TW, $status,
         $status === 'draft' ? NULL : date('Y-m-d H:i:s'),
         $ditinjau ? date('Y-m-d H:i:s') : NULL]
    );
    $GLOBALS['laporan'][] = $id;
    return $id;
}

function bersihkan() {
    if (empty($GLOBALS['db'])) { return; }
    foreach ($GLOBALS['laporan'] as $id) { q('DELETE FROM rd_laporan WHERE id=?', [$id]); }
    foreach ($GLOBALS['users'] as $id) {
        q('DELETE FROM sys_jejak_audit WHERE actor_id=?', [$id]);
        q('DELETE FROM usr_users WHERE id=?', [$id]);
    }
    foreach ($GLOBALS['jar'] as $j) { @unlink($j); }
    $GLOBALS['laporan'] = $GLOBALS['users'] = [];
}
register_shutdown_function('bersihkan');

// ==========================================================================

if ( ! is_file(ENV_PATH)) { die(".env tidak ditemukan.\n"); }
$env = env_config(ENV_PATH);
$GLOBALS['db'] = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
if ($GLOBALS['db']->connect_error) { die("Koneksi DB gagal.\n"); }

echo "=== UJI PANTAU REKAM DATA (pandangan superadmin) ===\n";
echo 'Target: ' . BASE_URL . " | DB: {$env['DB_NAME']} | tahun uji: " . TAHUN . "\n\n";

echo "== 0. Prasyarat ==\n";
$jml_kab = (int) nilai('SELECT COUNT(*) c FROM kabupaten');
wajib($jml_kab > 3, "Tabel kabupaten terisi ({$jml_kab} wilayah)");
wajib((int) nilai('SELECT COUNT(*) c FROM rd_laporan WHERE tahun=?', [TAHUN]) === 0,
    'Tahun uji ' . TAHUN . ' masih bersih dari laporan lain');

$kab = array_column(
    $GLOBALS['db']->query('SELECT id, nama FROM kabupaten ORDER BY nama ASC LIMIT 4')
        ->fetch_all(MYSQLI_ASSOC), 'nama', 'id');
$id_kab = array_keys($kab);
wajib(count($id_kab) === 4, 'Empat kabupaten pertama terbaca');

// Empat keadaan berbeda + sisanya (31 wilayah) sengaja TANPA laporan.
buat_laporan('perumahan', $id_kab[0], 'draft');
buat_laporan('perumahan', $id_kab[1], 'terkirim');                 // menunggu
buat_laporan('perumahan', $id_kab[2], 'terkirim', TRUE);           // diterima
buat_laporan('perumahan', $id_kab[3], 'perlu_perbaikan');
$lap_kawasan = buat_laporan('kawasan', $id_kab[0], 'terkirim');

[$idA, $emailA] = buat_akun('admin', 'super');
[$idK, $emailK] = buat_akun('admin_kabkota', 'kabkota', $id_kab[0]);
[$idB, $emailB] = buat_akun('admin_bidang', 'bidang', NULL, 'perumahan');

wajib(login('a', $emailA), 'Login superadmin');
wajib(login('k', $emailK), 'Login admin kabkota');
wajib(login('b', $emailB), 'Login admin bidang perumahan');

// ------------------------------------------------ 1. PAPAN
echo "\n== 1. Papan cakupan: 35 kabupaten, bukan 5 laporan ==\n";
$papan = http('a', 'Admin_Rekam_Data?tahun=' . TAHUN . '&triwulan=' . TW);
wajib($papan['code'] === 200 && strpos($papan['url'], 'Auth/login') === FALSE,
    'Layar Pantau Rekam Data terbuka untuk superadmin');

preg_match_all('/<tbody[^>]*>(.*?)<\/tbody>/s', $papan['body'], $tb);
$baris = preg_match_all('/<tr[^>]*>/', $tb[1][0] ?? '', $x);
cek($baris === $jml_kab, "Barisnya {$baris} — satu per kabupaten (harus {$jml_kab}), bukan sejumlah laporan");

foreach ($id_kab as $i => $kid) {
    cek(strpos($papan['body'], $kab[$kid]) !== FALSE, "Kabupaten '{$kab[$kid]}' tampil");
}

// Inilah yang tidak bisa ditampilkan layar mana pun yang bertolak dari rd_laporan.
$tanpa = (int) nilai('SELECT COUNT(*) c FROM kabupaten WHERE id NOT IN (?,?,?,?)',
    [$id_kab[0], $id_kab[1], $id_kab[2], $id_kab[3]]);
cek(substr_count($papan['body'], 'Belum ada laporan') >= $tanpa,
    "Kabupaten tanpa laporan ikut terlihat (minimal {$tanpa} sel 'Belum ada laporan')");

// ------------------------------------------------ 2. KEADAAN
echo "\n== 2. Empat keadaan dibedakan, dan 'Diterima' DITURUNKAN ==\n";
foreach (['Draft, belum dikirim', 'Menunggu ditinjau', 'Diterima', 'Perlu perbaikan'] as $label) {
    cek(strpos($papan['body'], $label) !== FALSE, "Keadaan '{$label}' tampil di papan");
}
/**
 * Bukti bahwa 'Diterima' bukan dibaca dari kolom status: tidak ada satu pun
 * baris yang statusnya 'diterima' di DB. Kalau uji ini hijau sementara query
 * di bawah memulangkan > 0, berarti seseorang menambahkan nilai enum baru dan
 * penurunannya perlu ditinjau ulang.
 */
cek((int) nilai("SELECT COUNT(*) c FROM rd_laporan WHERE status='diterima'") === 0,
    "Tidak ada status 'diterima' di DB — labelnya memang turunan reviewed_at");

echo "\n== 3. Ringkasan cakupan ==\n";
// perumahan: 4 laporan, 3 di antaranya sudah masuk (terkirim/diterima/perbaikan)
cek(preg_match('/3<span[^>]*>\/' . $jml_kab . '/', $papan['body']) === 1,
    "Ringkasan Perumahan berbunyi 3/{$jml_kab} sudah mengirim");
cek(preg_match('/1<span[^>]*>\/' . $jml_kab . '/', $papan['body']) === 1,
    "Ringkasan Kawasan berbunyi 1/{$jml_kab} sudah mengirim");

// ------------------------------------------------ 4. READ-ONLY
echo "\n== 4. Read-only: nol endpoint tulis, bukan nol tombol ==\n";
$kelas = file_get_contents(APP_ROOT . '/application/controllers/Admin_Rekam_Data.php');
preg_match_all('/^\s*public\s+function\s+(\w+)/m', $kelas, $m);
$publik = array_values(array_diff($m[1], ['__construct']));
sort($publik);
cek($publik === ['detail', 'index'],
    'Hanya index() dan detail() yang publik (ditemukan: ' . implode(', ', $publik) . ')');
foreach (['insert', 'update', 'delete', 'replace', 'truncate'] as $tulis) {
    cek( ! preg_match('/->' . $tulis . '\s*\(/', $kelas), "Tidak memanggil db->{$tulis}()");
}

$detail = http('a', 'Admin_Rekam_Data/detail/' . $lap_kawasan);
cek($detail['code'] === 200, 'Detail laporan terbuka untuk superadmin');
cek(strpos($detail['body'], 'Rekam_Tinjauan/terima') === FALSE,
    'Formulir "Terima laporan" TIDAK dirender di layar superadmin');
cek(strpos($detail['body'], 'Rekam_Tinjauan/minta_perbaikan') === FALSE,
    'Formulir "Minta perbaikan" TIDAK dirender');
cek(stripos($detail['body'], 'hanya baca') !== FALSE,
    'Sebabnya disebut ke pembacanya, bukan blok yang hilang tanpa keterangan');

// ------------------------------------------------ 5. TIDAK MENCABUT KEWENANGAN
echo "\n== 5. Admin Bidang TETAP bisa memutuskan (regresi view bersama) ==\n";
$lap_bidang = (int) nilai('SELECT id FROM rd_laporan WHERE domain="perumahan" AND status="terkirim" AND reviewed_at IS NULL AND tahun=? LIMIT 1', [TAHUN]);
wajib($lap_bidang > 0, 'Ada laporan perumahan yang menunggu keputusan');
$hal_bidang = http('b', 'Rekam_Tinjauan/detail/' . $lap_bidang);
wajib($hal_bidang['code'] === 200 && strpos($hal_bidang['url'], 'Auth/login') === FALSE,
    'Admin bidang bisa membuka detail laporan');
cek(strpos($hal_bidang['body'], 'Rekam_Tinjauan/terima') !== FALSE,
    'Tombol "Terima laporan" MASIH ada untuk admin bidang');
cek(strpos($hal_bidang['body'], 'Rekam_Tinjauan/minta_perbaikan') !== FALSE,
    'Tombol "Minta perbaikan" MASIH ada');

// ------------------------------------------------ 6. PERAN LAIN
echo "\n== 6. Peran lain tidak dapat papan ini (dicek dari ISI, bukan kode HTTP) ==\n";
foreach ([['k', 'admin kabkota'], ['b', 'admin bidang']] as [$s, $sebutan]) {
    $r = http($s, 'Admin_Rekam_Data');
    cek(strpos($r['body'], 'Pantau Rekam Data') === FALSE
        || strpos($r['body'], 'Belum ada laporan') === FALSE,
        "Papan superadmin tidak tersaji ke {$sebutan}");
}

// ------------------------------------------------ 7. JALAN MASUK KABKOTA
echo "\n== 7. Jalan masuk kabkota: Rekap & Riwayat terlihat tanpa klik caret ==\n";
$hal_rd = http('k', 'Rekam_Data');
wajib($hal_rd['code'] === 200 && strpos($hal_rd['url'], 'Auth/login') === FALSE,
    'Halaman Rekam Data terbuka untuk kabkota');
/**
 * Anak menu memang SELALU ada di DOM (disembunyikan `x-show`), jadi memeriksa
 * keberadaan tautannya saja akan hijau bahkan sebelum perbaikan ini. Yang
 * diperiksa: keadaan AWAL cabangnya — `buka: true`, bukan `buka: false`.
 */
cek(strpos($hal_rd['body'], 'Rekam_Perumahan/rekap') !== FALSE, 'Tautan Rekap Perumahan dirender');
cek(strpos($hal_rd['body'], 'Rekam_Perumahan/riwayat') !== FALSE, 'Tautan Riwayat Perumahan dirender');
cek(substr_count($hal_rd['body'], 'x-data="{ buka: false }"') === 0,
    'Tidak ada cabang Rekam Data yang lahir terlipat saat berada di dalamnya');
cek(substr_count($hal_rd['body'], 'x-data="{ buka: true }"') >= 3,
    'Rekam Data + Perumahan + Kawasan ketiganya terbentang (>=3 cabang terbuka)');

// ------------------------------------------------ 8. NILAI BAWAAN
echo "\n== 8. Nilai bawaan filter ==\n";
$polos = http('a', 'Admin_Rekam_Data');
$tw_kini = (int) ceil((int) date('n') / 3);
$nama_tw = [1 => 'TW I', 2 => 'TW II', 3 => 'TW III', 4 => 'TW IV'][$tw_kini];
cek(preg_match('/tahun=' . date('Y') . '&amp;triwulan=' . $tw_kini . '/', $polos['body']) === 1
    || strpos($polos['body'], 'tahun=' . date('Y')) !== FALSE,
    'Tanpa parameter, papan memakai tahun berjalan (' . date('Y') . ')');
cek(preg_match('/bg-brand-primary\/20[^>]*>' . preg_quote($nama_tw, '/') . '</', $polos['body']) === 1,
    "Triwulan bawaan = triwulan BERJALAN ({$nama_tw}), bukan TW I");

echo "\nRINGKASAN: {$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
