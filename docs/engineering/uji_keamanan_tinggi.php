<?php
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Tiga perbaikan keamanan tingkat tinggi (3 Okt 2026).
 *
 *   php docs/engineering/uji_keamanan_tinggi.php                (kode di working tree + HTTP lokal)
 *   php docs/engineering/uji_keamanan_tinggi.php --rev=631e6b5  (lapis unit dari revisi lain, tanpa HTTP)
 *
 *   1. Kepemilikan NIK: data SIMPERUM baru terbuka dan NIK baru terikat sesudah nama lengkap akun
 *      dan tanggal lahir cocok dengan data sumber; percobaan gagal dibatasi per akun dan per NIK;
 *      NIK milik akun lain ditolak dengan arahan ke menu Aduan; tidak ada lagi lookup otomatis
 *      (onboarding, cek anonim lalu login) yang mengikat tanpa bukti.
 *   2. Login Google: email harus terverifikasi Google; akun berkata sandi yang belum pernah tertaut
 *      Google kehilangan sandi lamanya saat Google membuktikan pemilik email, lalu wajib membuat
 *      sandi baru; verifikasi email simulasi dihapus.
 *   3. Cookie sesi dan CSRF memakai awalan __Host- di production (HTTPS).
 *
 * Bukti bahwa suite ini menggigit: lapis unit dengan --rev=631e6b5 merah, dan lapis HTTP merah
 * bila dijalankan terhadap kode sebelum perbaikan (lihat catatan commit).
 *
 * SIMPERUM: hanya mode simulasi lokal dengan fixture API-01/02/03 (NIK berawalan 3399, kabupaten
 * fiktif, bukan NIK warga). Suite menolak jalan bila SIMPERUM_MODE bukan simulation atau DB bukan
 * lokal, jadi API dinas tidak pernah tersentuh. Akun uji (@uji-tinggi.test) dibuat dan dihapus
 * sendiri; akun seed_agen_peran.php dipakai untuk login/logout enam peran dan kasus tidak cocok.
 * Ember batas laju yang tersentuh dipinjam lalu dikembalikan utuh.
 */

define('APP_ROOT', dirname(__DIR__, 2));
define('BASE', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/');
define('BASEPATH', APP_ROOT . '/system/');
define('APPPATH', APP_ROOT . '/application/');
define('ENVIRONMENT', 'development');
if ( ! function_exists('log_message')) { function log_message() {} }

$GLOBALS['total'] = 0; $GLOBALS['gagal'] = 0;
function cek($kondisi, $label) {
    $GLOBALS['total']++;
    echo ($kondisi ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $kondisi) { $GLOBALS['gagal']++; }
    return (bool) $kondisi;
}
function ringkas_dan_keluar() {
    echo "RINGKASAN: {$GLOBALS['total']} pemeriksaan, {$GLOBALS['gagal']} gagal\n";
    exit($GLOBALS['gagal'] ? 1 : 0);
}

$env = [];
foreach (file(APP_ROOT . '/.env', FILE_IGNORE_NEW_LINES) as $l) {
    $l = trim($l);
    if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) { continue; }
    [$k, $v] = explode('=', $l, 2);
    $k = trim($k); $v = trim($v, " \t\"'");
    if ( ! array_key_exists($k, $env)) { $env[$k] = $v; putenv("$k=$v"); }
}
$rev = getopt('', ['rev::'])['rev'] ?? '';

/** Ambil satu method dari sumber (kini atau revisi git), cocokkan kurawal lewat token. */
function ambil_method($berkas, $tanda, $rev) {
    $sumber = $rev === ''
        ? (string) @file_get_contents(APP_ROOT . '/' . $berkas)
        : (string) shell_exec('git -C ' . escapeshellarg(APP_ROOT) . ' show ' . escapeshellarg($rev . ':' . $berkas) . ' 2>&1');
    $mulai = strpos($sumber, $tanda);
    if ($mulai === FALSE) { return NULL; }
    $badan = ''; $dalam = 0; $buka = FALSE;
    foreach (array_slice(token_get_all('<?php ' . substr($sumber, $mulai)), 1) as $t) {
        $teks = is_array($t) ? $t[1] : $t;
        $badan .= $teks;
        if ($teks === '{' || (is_array($t) && in_array($t[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], TRUE))) { $dalam++; $buka = TRUE; }
        if ($teks === '}') { $dalam--; if ($buka && $dalam === 0) { break; } }
    }
    return $badan;
}

/** Muat kelas uji dari potongan sumber repo lewat berkas sementara (pola uji_redirect_aman). */
function muat_kelas($kode) {
    $berkas = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'uji_tinggi_' . bin2hex(random_bytes(4)) . '.php';
    file_put_contents($berkas, "<?php
" . $kode . "
");
    require $berkas;
    unlink($berkas);
}

/* ===================================================================== 1. Unit: pencocokan nama */
echo "== 1. Unit Simperum_gateway::nama_sama()" . ($rev !== '' ? " dari revisi $rev" : '') . "\n";
$badan = ambil_method('application/libraries/Simperum_gateway.php', 'public static function nama_sama(', $rev);
if (cek($badan !== NULL, 'Pencocokan nama pemilik NIK ada di gateway')) {
    muat_kelas('class Uji_Nama { ' . $badan . ' }');
    $sama = [
        ['SUGENG SINTETIS', 'Sugeng Sintetis', 'huruf besar/kecil'],
        ['  sugeng   sintetis ', 'SUGENG SINTETIS', 'spasi berlebih'],
        ['MA&#039;RUF SINTETIS', "MA'RUF SINTETIS", 'entitas HTML dari html_escape()'],
        ["MA'RUF SINTETIS", 'MARUF SINTETIS', 'apostrof'],
        ['SITI-AMINAH SINTETIS', 'SITI AMINAH SINTETIS', 'tanda hubung'],
        ['H. SUGENG SINTETIS', 'SUGENG SINTETIS', 'sapaan H. di depan'],
        ['DRS. SUGENG SINTETIS', 'SUGENG SINTETIS', 'gelar Drs. di depan'],
        ['SUGENG SINTETIS, S.PD', 'SUGENG SINTETIS', 'gelar sesudah koma'],
    ];
    foreach ($sama as [$a, $b, $ket]) { cek(Uji_Nama::nama_sama($a, $b) === TRUE, "Cocok: $ket"); }
    $beda = [
        ['SUGENG', 'SUGENG SINTETIS', 'nama depan saja'],
        ['SUGENG SINTETIS WIBOWO', 'SUGENG SINTETIS', 'kata tambahan'],
        ['SUGENG SINTETIK', 'SUGENG SINTETIS', 'satu huruf beda (tanpa fuzzy)'],
        ['SINTETIS SUGENG', 'SUGENG SINTETIS', 'urutan kata ditukar'],
        ['', '', 'keduanya kosong'],
        ['H', 'H', 'hanya sapaan, tetap dianggap nama'],
        ['SUGENG SINTETIS', '', 'nama sumber kosong'],
    ];
    foreach ($beda as [$a, $b, $ket]) {
        $hasil = Uji_Nama::nama_sama($a, $b);
        cek($ket === 'hanya sapaan, tetap dianggap nama' ? $hasil === TRUE : $hasil === FALSE, "Ditolak/kasus batas: $ket");
    }
}

/* ===================================================================== 2. Unit: penautan Google */
echo "\n== 2. Unit User_model::check_google_user()" . ($rev !== '' ? " dari revisi $rev" : '') . "\n";
mysqli_report(MYSQLI_REPORT_OFF);
$host = strtolower($env['DB_HOST'] ?? '');
if ( ! in_array($host, ['localhost', '127.0.0.1', '::1'], TRUE)) { cek(FALSE, 'DB lokal (suite ini menulis akun uji)'); ringkas_dan_keluar(); }
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
$TAG = 'ujitinggi' . bin2hex(random_bytes(3));
$SANDI = 'Uji#' . bin2hex(random_bytes(5)) . 'A1';
$HASH = password_hash($SANDI, PASSWORD_BCRYPT);
$akun_uji = [];
function akun_baru($nama, array $kolom = []) {
    global $TAG, $HASH, $akun_uji;
    $email = $TAG . '_' . count($akun_uji) . '@uji-tinggi.test';
    $isi = $kolom + ['nama' => $nama, 'email' => $email, 'kata_sandi' => $HASH, 'peran' => 'warga',
        'status' => 'active', 'profil_lengkap' => 1, 'sandi_diganti_at' => date('Y-m-d H:i:s'),
        'sandi_kedaluwarsa_at' => date('Y-m-d H:i:s', strtotime('+90 days')), 'created_at' => date('Y-m-d H:i:s')];
    $id = jalan('INSERT INTO usr_akun (' . implode(',', array_keys($isi)) . ') VALUES (' . implode(',', array_fill(0, count($isi), '?')) . ')', array_values($isi));
    $akun_uji[] = (int) $id;
    return [(int) $id, $email];
}

/* CI_Model + query builder CodeIgniter sungguhan di atas DB lokal, tanpa menjalankan aplikasi. */
require_once BASEPATH . 'core/Common.php';
require_once BASEPATH . 'database/DB.php';
require_once BASEPATH . 'core/Model.php';
$CI_DB = DB(['dsn' => '', 'hostname' => $env['DB_HOST'], 'username' => $env['DB_USER'], 'password' => $env['DB_PASS'] ?? '',
    'database' => $env['DB_NAME'], 'dbdriver' => 'mysqli', 'char_set' => 'utf8mb4', 'dbcollat' => 'utf8mb4_unicode_ci',
    'db_debug' => FALSE, 'pconnect' => FALSE], TRUE);
$CI_DB->query("SET time_zone = '+07:00'");
$GLOBALS['UJI_CI'] = (object) ['db' => $CI_DB, 'load' => new class { public function database() {} public function library() {} public function model() {} }];
if ( ! function_exists('get_instance')) { function &get_instance() { return $GLOBALS['UJI_CI']; } }
$badan_model = ambil_method('application/models/User_model.php', 'public function check_google_user(', $rev);
$model = NULL;
if (cek($badan_model !== NULL, 'check_google_user() ditemukan di sumber')) {
    muat_kelas('class Uji_User_model extends CI_Model { ' . $badan_model . ' }');
    $model = new Uji_User_model();
}

try {
    if ($model) {
        $gid = function () { return 'uji' . bin2hex(random_bytes(8)); };
        $data = function ($email, $g) { return ['google_id' => $g, 'nama' => 'Uji Google', 'email' => $email, 'foto_profil' => 'https://example.test/f.png']; };

        // G1: email yang tidak diverifikasi Google tidak boleh menautkan.
        [$id, $email] = akun_baru('Uji Google 1');
        $hasil = $model->check_google_user($data($email, $gid()), FALSE);
        $baris = satu('SELECT google_id, kata_sandi FROM usr_akun WHERE id=?', [$id]);
        cek($hasil === NULL && $baris['google_id'] === NULL && $baris['kata_sandi'] !== NULL,
            'Google verified_email bukan true: ditolak, akun tidak tertaut');

        // G2: akun berkata sandi tanpa google_id (bisa didaftarkan siapa pun memakai email orang lain).
        [$id, $email] = akun_baru('Uji Google 2', ['sesi_aktif_hash' => str_repeat('a', 64), 'sesi_aktif_id_hash' => str_repeat('b', 64), 'sesi_aktif_at' => date('Y-m-d H:i:s')]);
        $g = $gid();
        $hasil = $model->check_google_user($data($email, $g), TRUE);
        $baris = satu('SELECT google_id, kata_sandi, sesi_aktif_hash, sesi_aktif_id_hash, email_verified_at, sandi_diganti_at, sandi_kedaluwarsa_at FROM usr_akun WHERE id=?', [$id]);
        cek(is_array($hasil) && $baris['google_id'] === $g, 'Penautan pertama: akun tertaut ke google_id');
        cek($baris['kata_sandi'] === NULL, 'Penautan pertama: sandi lama (milik pendaftar email) dihapus');
        cek($baris['sesi_aktif_hash'] === NULL && $baris['sesi_aktif_id_hash'] === NULL, 'Penautan pertama: sesi aktif lama dicabut');
        cek($baris['email_verified_at'] !== NULL, 'Penautan pertama: email ditandai terverifikasi (dibuktikan Google)');
        cek($baris['sandi_diganti_at'] === NULL && $baris['sandi_kedaluwarsa_at'] !== NULL && strtotime($baris['sandi_kedaluwarsa_at']) <= time() + 5,
            'Penautan pertama: sandi baru diwajibkan lewat gerbang kedaluwarsa');
        cek(is_array($hasil) && array_key_exists('kata_sandi', $hasil[0]) && $hasil[0]['kata_sandi'] === NULL, 'Baris yang dikembalikan sudah tanpa sandi (onboarding meminta sandi baru)');

        // G3: email yang sudah tertaut ke akun Google LAIN.
        $g_lama = $gid();
        [$id, $email] = akun_baru('Uji Google 3', ['google_id' => $g_lama]);
        $hasil = $model->check_google_user($data($email, $gid()), TRUE);
        cek($hasil === NULL && satu('SELECT google_id FROM usr_akun WHERE id=?', [$id])['google_id'] === $g_lama,
            'Email tertaut ke google_id lain: ditolak, google_id lama tidak ditimpa');

        // G4: akun yang sudah tertaut ke google_id ini: sandinya tidak disentuh.
        $g = $gid();
        [$id, $email] = akun_baru('Uji Google 4', ['google_id' => $g]);
        $hasil = $model->check_google_user($data($email, $g), TRUE);
        cek(is_array($hasil) && satu('SELECT kata_sandi FROM usr_akun WHERE id=?', [$id])['kata_sandi'] !== NULL,
            'Login Google berikutnya (google_id sama): sandi tetap');

        // G5: email baru membuat akun baru yang emailnya terverifikasi.
        $email = $TAG . '_baru@uji-tinggi.test';
        $hasil = $model->check_google_user($data($email, $gid()), TRUE);
        $baris = satu('SELECT id, kata_sandi, email_verified_at FROM usr_akun WHERE email=?', [$email]);
        if ($baris) { $akun_uji[] = (int) $baris['id']; }
        cek(is_array($hasil) && ($hasil[1] ?? '') === '0' && $baris && $baris['kata_sandi'] === NULL && $baris['email_verified_at'] !== NULL,
            'Email baru: akun baru tanpa sandi, email terverifikasi');
    }
} catch (Throwable $e) {
    cek(FALSE, 'Lapis unit Google melempar ' . get_class($e) . ': ' . $e->getMessage());
}

if ($rev !== '') {
    foreach ($akun_uji as $id) { jalan('DELETE FROM usr_akun WHERE id=?', [$id]); }
    ringkas_dan_keluar();
}

/* ===================================================================== 3. Konfigurasi cookie */
echo "\n== 3. Awalan __Host- pada cookie sesi dan CSRF\n";
$php = PHP_BINARY;
$baca_config = function ($lingkungan) use ($php) {
    // Tanpa tanda kutip ganda: escapeshellarg() di Windows menggantinya dengan spasi.
    $kode = "define('BASEPATH','x');define('FCPATH'," . var_export(APP_ROOT . DIRECTORY_SEPARATOR, TRUE) . ");define('ENVIRONMENT'," . var_export($lingkungan, TRUE) . ');$config=[];'
        . 'require ' . var_export(APPPATH . 'config/config.php', TRUE) . ';echo json_encode($config);';
    return json_decode((string) shell_exec(escapeshellarg($php) . ' -r ' . escapeshellarg($kode)), TRUE) ?: [];
};
$prod = $baca_config('production');
$dev = $baca_config('development');
cek(($prod['sess_cookie_name'] ?? '') === '__Host-ci_session', 'Production: cookie sesi bernama __Host-ci_session');
cek(($prod['csrf_cookie_name'] ?? '') === '__Host-csrf_kpkp_cookie', 'Production: cookie CSRF bernama __Host-csrf_kpkp_cookie');
cek(($prod['cookie_secure'] ?? NULL) === TRUE && ($prod['cookie_path'] ?? '') === '/' && ($prod['cookie_domain'] ?? 'x') === ''
    && ($prod['cookie_prefix'] ?? 'x') === '', 'Production: syarat __Host- terpenuhi (Secure, Path=/, tanpa Domain, tanpa cookie_prefix)');
cek(($dev['sess_cookie_name'] ?? '') === 'ci_session' && ($dev['csrf_cookie_name'] ?? '') === 'csrf_kpkp_cookie' && ($dev['cookie_secure'] ?? NULL) === FALSE,
    'Lokal (http): nama tanpa awalan dan tanpa Secure, login lokal tetap jalan');

/* Aplikasi sungguhan dalam mode production di server bawaan PHP, HTTPS ditandai lewat
   X-Forwarded-Proto (is_https() CodeIgniter). Hanya halaman masuk dan satu POST ke rute yang
   tidak ada: CSRF diperiksa sebelum routing, jadi 404 = token diterima, 403 = ditolak. */
$port = 18000 + random_int(0, 999);
// session.save_path eksplisit: PHP CLI tanpa nilai bawaan (mis. Herd) gagal diam-diam di mode production.
$proses = proc_open([$php, '-d', 'session.save_path=' . sys_get_temp_dir(), '-S', "127.0.0.1:$port", APP_ROOT . '/index.php'], [0 => ['pipe', 'r'], 1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w']],
    $pipa, APP_ROOT, array_merge(getenv(), ['CI_ENV' => 'production']));
$minta_prod = function ($path, $post = NULL, $cookie = '') use ($port) {
    $c = curl_init("http://127.0.0.1:$port/$path");
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_HEADER => TRUE, CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => array_filter(['X-Forwarded-Proto: https', $cookie !== '' ? "Cookie: $cookie" : NULL])]);
    if ($post !== NULL) { curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post)); }
    $r = (string) curl_exec($c);
    $kode = curl_getinfo($c, CURLINFO_HTTP_CODE);
    $kepala = substr($r, 0, curl_getinfo($c, CURLINFO_HEADER_SIZE));
    curl_close($c);
    return [$kode, $kepala];
};
$siap = FALSE;
for ($i = 0; $i < 50 && ! $siap; $i++) { usleep(100000); $siap = @fsockopen('127.0.0.1', $port) !== FALSE; }
if (cek($siap, 'Server PHP mode production siap')) {
    [$kode, $kepala] = $minta_prod('Auth/login');
    preg_match_all('/^Set-Cookie:\s*([^\r\n]+)/mi', $kepala, $m);
    $csrf_set = current(array_filter($m[1], fn($s) => stripos($s, '__Host-csrf_kpkp_cookie=') === 0)) ?: '';
    $sesi_set = current(array_filter($m[1], fn($s) => stripos($s, '__Host-ci_session=') === 0)) ?: '';
    $syarat = fn($s) => $s !== '' && stripos($s, 'secure') !== FALSE && preg_match('/;\s*path=\/(;|$)/i', $s) && stripos($s, 'domain=') === FALSE;
    cek($kode === 200 && $syarat($csrf_set), 'Set-Cookie __Host-csrf_kpkp_cookie: Secure, Path=/, tanpa Domain');
    cek($syarat($sesi_set), 'Set-Cookie __Host-ci_session: Secure, Path=/, tanpa Domain')
        || print('        (HTTP ' . $kode . ', cookie terkirim: ' . implode(', ', array_map(fn($s) => preg_replace('/=.*?;/', '=...;', $s), $m[1])) . ")\n");
    cek( ! preg_grep('/^(csrf_kpkp_cookie|ci_session)=/i', $m[1]), 'Tidak ada cookie bernama polos yang masih dikirim');
    $token = preg_match('/^__Host-csrf_kpkp_cookie=([0-9a-f]{32})/i', $csrf_set, $t) ? $t[1] : '';
    [$kode] = $minta_prod('Index/uji_tidak_ada', ['csrf_kpkp_token' => $token], "__Host-csrf_kpkp_cookie=$token");
    cek($token !== '' && $kode === 404, 'POST dengan cookie __Host- dan token yang cocok lolos CSRF (404 rute, bukan 403)');
    $palsu = bin2hex(random_bytes(16));
    [$kode] = $minta_prod('Index/uji_tidak_ada', ['csrf_kpkp_token' => $palsu], "csrf_kpkp_cookie=$palsu");
    cek($kode === 403, 'Cookie CSRF tanpa awalan (bentuk yang bisa dipasang situs tetangga) ditolak 403');
    [$kode] = $minta_prod('Index/uji_tidak_ada', ['csrf_kpkp_token' => $palsu], "__Host-csrf_kpkp_cookie=$token");
    cek($kode === 403, 'Token yang tidak cocok dengan cookie __Host- ditolak 403');
}
if (is_resource($proses)) { proc_terminate($proses); proc_close($proses); }

/* ===================================================================== HTTP lokal: klien */
function minta($jar, $path, $post = NULL, $ikuti = TRUE) {
    $c = curl_init(BASE . $path);
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_FOLLOWLOCATION => $ikuti, CURLOPT_COOKIEJAR => $jar,
        CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 60, CURLOPT_HEADER => FALSE]);
    if ($post !== NULL) {
        $post += ['csrf_kpkp_token' => token_csrf($jar)];
        curl_setopt($c, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $badan = (string) curl_exec($c);
    $kode = curl_getinfo($c, CURLINFO_HTTP_CODE);
    $url = (string) curl_getinfo($c, CURLINFO_EFFECTIVE_URL);
    curl_close($c);
    return ['kode' => $kode, 'badan' => html_entity_decode($badan, ENT_QUOTES, 'UTF-8'), 'url' => $url];
}
function token_csrf($jar) {
    foreach (@file($jar) ?: [] as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') { return $p[6]; } }
    return '';
}
$jar_dibuat = [];
function jar() { global $jar_dibuat; return $jar_dibuat[] = tempnam(sys_get_temp_dir(), 'ujt'); }
function masuk($email, $sandi) {
    $j = jar();
    minta($j, 'Auth/login');
    minta($j, 'Auth/do_login', ['email' => $email, 'password' => $sandi]);
    return $j;
}

/* Ember batas laju: dipinjam (disimpan lalu dikosongkan), dikembalikan utuh di finally. */
$ember_asli = [];
function kosongkan_ember($kunci) {
    global $ember_asli;
    if ( ! array_key_exists($kunci, $ember_asli)) { $ember_asli[$kunci] = satu('SELECT kunci, jendela_mulai_at, jumlah_gagal FROM sys_batas_laju WHERE kunci=?', [$kunci]); }
    jalan('DELETE FROM sys_batas_laju WHERE kunci=?', [$kunci]);
}
function ember_ip($policy) { foreach (['127.0.0.1', '::1', '0000000000000000/64'] as $ip) { kosongkan_ember(hash('sha256', "$policy:ip:$ip")); } }
function ember_akun($policy, $id) { kosongkan_ember(hash('sha256', "$policy:account:$id")); }
function ember_nik($policy, $nik) { global $enc; kosongkan_ember(hash('sha256', "$policy:nik:" . $enc->deterministic_hash($nik))); }
function gagal_tercatat($dimensi, $nilai) {
    global $enc;
    $nilai = $dimensi === 'nik' ? $enc->deterministic_hash($nilai) : $nilai;
    return (int) (satu('SELECT jumlah_gagal FROM sys_batas_laju WHERE kunci=?', [hash('sha256', "verifikasi_nik:$dimensi:$nilai")])['jumlah_gagal'] ?? 0);
}

require_once APPPATH . 'libraries/Encryption_lib.php';
$enc = new Encryption_lib();
$NIK1 = '3399991508850001'; $LAHIR1 = '1985-08-15'; // API-01, SUGENG SINTETIS
$NIK2 = '3399995506900002'; $LAHIR2 = '1990-06-15'; // API-02, SRI SINTETIS
$NIK3 = '3399990101700003'; $LAHIR3 = '1970-01-01'; // API-03, PAIMAN SINTETIS
$NIK_SIM2 = '0000000000000002'; // SIM-02 (Warga Simulasi Parsial): khusus cek anonim
$NIK_SIM4 = '0000000000000004'; // SIM-04: khusus onboarding kirim ulang (harus belum terikat siapa pun)
$AGEN_SANDI = 'AgenUji!2026'; // sandi mainan seed_agen_peran.php, hanya ada di DB lokal

try {
    /* ================================================================= 4. Google dan verifikasi email */
    echo "\n== 4. Login Google dan verifikasi email (HTTP, tanpa Google sungguhan)\n";
    ember_ip('login');
    [$id_v, $email_v] = akun_baru('Uji Verifikasi', ['email_verified_at' => NULL]);
    $j = masuk($email_v, $SANDI);
    $r = minta($j, 'Auth/do_verify_email', [], FALSE);
    cek($r['kode'] === 404 && satu('SELECT email_verified_at FROM usr_akun WHERE id=?', [$id_v])['email_verified_at'] === NULL,
        'POST Auth/do_verify_email tidak ada lagi (404) dan tidak menandai email terverifikasi');
    cek(minta($j, 'Auth/verify_pending', NULL, FALSE)['kode'] === 404, 'Halaman verifikasi email simulasi tidak ada lagi (404)');
    $r = minta(jar(), 'Auth/google_callback?state=palsu&code=palsu');
    cek($r['kode'] === 200 && strpos($r['badan'], 'window.opener.location.href') !== FALSE && strpos($r['badan'], 'login') !== FALSE,
        'google_callback dengan state palsu: popup ditutup ke halaman masuk, tanpa menukar kode');

    // Akun yang sandinya dicabut check_google_user(): disimulasikan pada sesi yang sudah ada.
    ember_ip('profile_password'); // hanya percobaan gagal yang dihitung, per IP: jalan lain bisa memenuhinya
    [$id_p, $email_p] = akun_baru('Uji Tanpa Sandi', ['peran' => 'pengembang']);
    $j = masuk($email_p, $SANDI);
    jalan('UPDATE usr_akun SET kata_sandi=NULL, sandi_diganti_at=NULL, sandi_kedaluwarsa_at=NOW() WHERE id=?', [$id_p]);
    $r = minta($j, 'Pengembang/syarat');
    cek(strpos($r['url'], 'akun/profil') !== FALSE && strpos($r['badan'], 'dibuktikan lewat Google') !== FALSE,
        'Akun tanpa sandi diarahkan ke Profil Saya dengan pesan wajib membuat sandi baru');
    $baru = 'Baru#' . bin2hex(random_bytes(4)) . 'Z9';
    $r = minta($j, 'akun/update', ['name' => 'Uji Tanpa Sandi', 'phone' => '081234567890', 'password' => $baru, 'password_confirm' => $baru]);
    $baris = satu('SELECT kata_sandi, sandi_kedaluwarsa_at FROM usr_akun WHERE id=?', [$id_p]);
    cek($baris['kata_sandi'] !== NULL && password_verify($baru, $baris['kata_sandi']) && strtotime($baris['sandi_kedaluwarsa_at']) > time(),
        'Akun tanpa sandi membuat sandi pertamanya tanpa "sandi saat ini"');
    $j2 = masuk($email_p, $baru);
    cek(strpos(minta($j2, 'akun/profil')['badan'], 'Auth/do_login') === FALSE, 'Sandi baru dipakai untuk masuk');
    $r = minta($j2, 'akun/update', ['name' => 'Uji Tanpa Sandi', 'phone' => '081234567890', 'password' => $baru . 'x', 'password_confirm' => $baru . 'x']);
    cek(strpos($r['badan'], 'Password saat ini salah') !== FALSE, 'Akun yang sudah bersandi tetap wajib menyebut sandi saat ini');

    /* ================================================================= 5. Kepemilikan NIK */
    echo "\n== 5. Kepemilikan NIK: nama akun + tanggal lahir (fixture simulasi)\n";
    if ( ! cek(($env['SIMPERUM_MODE'] ?? '') === 'simulation', 'SIMPERUM lokal mode simulasi (API dinas tidak dipanggil)')) { throw new RuntimeException('mode SIMPERUM'); }
    foreach ([$NIK1, $NIK2, $NIK3, $NIK_SIM2, $NIK_SIM4] as $n) {
        $h = $enc->deterministic_hash($n);
        $pemilik = (int) (satu('SELECT COUNT(*) n FROM usr_akun WHERE nik_lookup_hash=?', [$h])['n']) + (int) (satu('SELECT COUNT(*) n FROM sf_profil_warga WHERE nik_lookup_hash=?', [$h])['n']);
        if ( ! cek($pemilik === 0, 'Fixture ' . substr($n, 0, 4) . '..' . substr($n, -4) . ' belum terikat ke akun mana pun')) { throw new RuntimeException('fixture terikat'); }
        foreach (['warga_lookup', 'verifikasi_nik'] as $p) { ember_nik($p, $n); }
    }
    $agen = satu("SELECT id FROM usr_akun WHERE email='agen_warga@agen.test' AND peran='warga'");
    if ( ! cek($agen && ! satu('SELECT id FROM sf_profil_warga WHERE user_id=?', [$agen['id']]) && satu('SELECT nik_lookup_hash FROM usr_akun WHERE id=?', [$agen['id']])['nik_lookup_hash'] === NULL,
        'agen_warga (seed) ada, tanpa NIK dan tanpa profil pendataan')) { throw new RuntimeException('seed'); }
    $AGEN = (int) $agen['id'];
    [$W_COCOK, $e_cocok] = akun_baru('sugeng   Sintetis');
    [$W_KEDUA, $e_kedua] = akun_baru('SUGENG SINTETIS');
    [$W_SRI, $e_sri] = akun_baru('Sri Sintetis');
    foreach ([$AGEN, $W_COCOK, $W_KEDUA, $W_SRI] as $id) {
        foreach (['warga_lookup', 'warga_lookup_jam', 'warga_lookup_harian', 'verifikasi_nik', 'rtlh_cek', 'rtlh_cek_harian', 'account_export'] as $p) { ember_akun($p, $id); }
    }
    $lookup = function ($j, $nik, $lahir) {
        ember_ip('warga_lookup');
        $isi = ['step' => 'find_data', 'action' => 'lookup', 'nik' => $nik];
        if ($lahir !== NULL) { $isi['birth_date'] = $lahir; }
        return minta($j, 'warga/pendataan', $isi);
    };
    $profil = fn($id) => satu('SELECT mode_sumber, confirmed_at FROM sf_profil_warga WHERE user_id=?', [$id]);
    $cermin = fn($id) => (int) satu('SELECT COUNT(*) n FROM sf_data_simperum WHERE user_id=?', [$id])['n'];
    $step = fn($b) => preg_match('/name="step" value="([a-z_]+)"/', $b, $m) ? $m[1] : '';

    $j_agen = masuk('agen_warga@agen.test', $AGEN_SANDI);
    $r = minta($j_agen, 'warga/pendataan');
    cek(strpos($r['badan'], 'name="birth_date"') !== FALSE && strpos($r['badan'], 'nama lengkap di akun') !== FALSE,
        'Langkah Cek NIK akun meminta tanggal lahir dan menjelaskan pencocokan nama akun');

    $r = $lookup($j_agen, $NIK1, $LAHIR1);
    cek(stripos($r['badan'], 'tidak cocok') !== FALSE && $step($r['badan']) === 'find_data', 'Nama akun beda: ditolak, tetap di Cek NIK');
    cek(stripos($r['badan'], 'SUGENG') === FALSE && strpos($r['badan'], 'S****G') === FALSE && stripos($r['badan'], 'Alamat API') === FALSE,
        'Nama akun beda: halaman tidak memuat nama (utuh/tersamar) maupun alamat sumber');
    cek($profil($AGEN) === NULL && $cermin($AGEN) === 0, 'Nama akun beda: NIK tidak terikat, profil dan cermin SIMPERUM tidak tertulis');
    $j_cocok = masuk($e_cocok, $SANDI);
    $r = $lookup($j_cocok, $NIK1, '1985-08-16');
    cek(stripos($r['badan'], 'tidak cocok') !== FALSE && $profil($W_COCOK) === NULL, 'Nama cocok, tanggal lahir beda sehari: ditolak');
    $r = $lookup($j_cocok, $NIK1, NULL);
    cek(stripos($r['badan'], 'Tanggal lahir wajib') !== FALSE && gagal_tercatat('account', $W_COCOK) === 1,
        'Tanggal lahir kosong: diminta, tidak dihitung sebagai percobaan gagal');
    $r = $lookup($j_cocok, $NIK1, $LAHIR1);
    $p = $profil($W_COCOK);
    cek(strpos($r['badan'], 'NIK terverifikasi') !== FALSE && $step($r['badan']) === 'housing_family', 'Nama (dinormalkan) dan tanggal lahir cocok: terverifikasi, maju ke Data untuk Rekomendasi');
    cek($p && $p['mode_sumber'] === 'simulation' && $p['confirmed_at'] !== NULL, 'Profil terikat dan ditandai terverifikasi (confirmed_at)');
    cek($cermin($W_COCOK) === 1, 'Cermin SIMPERUM ditulis sesudah verifikasi');
    cek(strpos($r['badan'], 'value="' . $LAHIR1 . '"') !== FALSE, 'Data sesudah verifikasi tampil (tanggal lahir terisi)');

    $j_kedua = masuk($e_kedua, $SANDI);
    $r = $lookup($j_kedua, $NIK1, $LAHIR1);
    cek(strpos($r['badan'], 'sudah terhubung dengan akun lain') !== FALSE && strpos($r['badan'], 'menu Aduan') !== FALSE,
        'NIK sudah terikat: akun kedua ditolak walau nama dan tanggal lahir cocok, diarahkan ke Aduan');
    cek($profil($W_KEDUA) === NULL && $cermin($W_KEDUA) === 0 && strpos($r['badan'], $e_cocok) === FALSE && $step($r['badan']) === 'find_data',
        'NIK sudah terikat: tidak ada data, profil, atau identitas pemilik yang terbuka');
    cek(gagal_tercatat('account', $W_KEDUA) === 0, 'NIK sudah terikat: tidak dihitung sebagai tebakan gagal');

    $r = minta($j_agen, 'Cek_Rtlh/periksa', ['nik' => $NIK1]);
    cek(strpos($r['badan'], 'TERDAFTAR') !== FALSE && strpos($r['badan'], '>Nama</dt>') === FALSE && strpos($r['badan'], 'S****G') === FALSE,
        'Cek RTLH akun login: status terdaftar saja, tanpa nama/alamat tersamar');

    // Batas percobaan gagal per akun: agen_warga sudah gagal 1 kali (NIK1), 4 lagi di NIK2.
    for ($i = 0; $i < 4; $i++) { $lookup($j_agen, $NIK2, $LAHIR2); }
    cek(gagal_tercatat('account', $AGEN) === 5, 'Lima percobaan gagal tercatat untuk akun');
    $r = $lookup($j_agen, $NIK2, $LAHIR2);
    cek(stripos($r['badan'], 'Terlalu banyak percobaan verifikasi') !== FALSE && $profil($AGEN) === NULL, 'Percobaan keenam per akun ditahan dengan pesan jelas');
    // Batas per NIK: NIK2 sudah 4 gagal dari agen_warga; satu gagal dari akun lain mengunci NIK itu.
    $j_sri = masuk($e_sri, $SANDI);
    $lookup($j_sri, $NIK2, '1990-06-16');
    $r = $lookup($j_sri, $NIK2, $LAHIR2);
    cek(stripos($r['badan'], 'Terlalu banyak percobaan verifikasi') !== FALSE && $profil($W_SRI) === NULL && gagal_tercatat('account', $W_SRI) === 1,
        'Batas per NIK: akun lain ikut ditahan untuk NIK yang sedang ditebak, walau datanya benar');

    $ekspor = minta($j_agen, 'akun/export', ['current_password' => $AGEN_SANDI]);
    $json = json_decode($ekspor['badan'], TRUE);
    cek(is_array($json) && empty($json['data']['sf_profil_warga']) && empty($json['data']['sf_data_simperum']),
        'Ekspor data akun yang gagal verifikasi tidak memuat data SIMPERUM');

    // Onboarding tidak lagi membuka data, dan tidak bisa dikirim ulang untuk mengganti NIK.
    [$W_ONB, $e_onb] = akun_baru('Uji Onboarding', ['peran' => NULL, 'profil_lengkap' => 0]);
    foreach (['warga_lookup', 'warga_lookup_jam', 'warga_lookup_harian', 'verifikasi_nik'] as $p) { ember_akun($p, $W_ONB); }
    $j_onb = masuk($e_onb, $SANDI);
    minta($j_onb, 'Auth/onboarding');
    minta($j_onb, 'Auth/save_onboarding', ['role' => 'warga', 'username' => $TAG . 'onb', 'nama_lengkap' => 'Paiman Sintetis',
        'nik_identitas' => $NIK3, 'alamat_domisili' => 'Alamat uji', 'phone' => '081234567890']);
    $u = satu('SELECT profil_lengkap, nik_lookup_hash FROM usr_akun WHERE id=?', [$W_ONB]);
    cek((int) $u['profil_lengkap'] === 1 && $u['nik_lookup_hash'] === $enc->deterministic_hash($NIK3), 'Onboarding warga menyimpan NIK akun');
    cek($profil($W_ONB) === NULL && (int) satu('SELECT COUNT(*) n FROM sf_penilaian_perumahan WHERE user_id=?', [$W_ONB])['n'] === 0,
        'Onboarding tidak lagi mengikat profil atau membuat draft dari SIMPERUM tanpa verifikasi');
    $r = minta($j_onb, 'warga/pendataan');
    cek($step($r['badan']) === 'find_data' && strpos($r['badan'], 'value="' . $NIK3 . '"') !== FALSE && stripos($r['badan'], 'PAIMAN SINTETIS') === FALSE,
        'Wizard tidak lookup otomatis: Cek NIK terisi NIK akun, data sumber belum terbuka');
    minta($j_onb, 'Auth/save_onboarding', ['role' => 'warga', 'username' => $TAG . 'onb', 'nama_lengkap' => 'Paiman Sintetis',
        'nik_identitas' => $NIK_SIM4, 'alamat_domisili' => 'Alamat uji', 'phone' => '081234567890']);
    cek(satu('SELECT nik_lookup_hash FROM usr_akun WHERE id=?', [$W_ONB])['nik_lookup_hash'] === $enc->deterministic_hash($NIK3),
        'Onboarding yang dikirim ulang dari akun lengkap tidak mengganti NIK');
    $r = $lookup($j_onb, $NIK2, $LAHIR2);
    cek(strpos($r['badan'], 'berbeda dengan NIK yang terdaftar di akun Anda') !== FALSE && $profil($W_ONB) === NULL,
        'NIK lain dari NIK akun sendiri ditolak sebelum pencocokan');
    $r = $lookup($j_onb, $NIK3, $LAHIR3);
    $p = $profil($W_ONB);
    cek($p && $p['confirmed_at'] !== NULL && $step($r['badan']) === 'housing_family', 'NIK akun + nama akun + tanggal lahir: terverifikasi dan maju');

    // Cek NIK anonim lalu masuk: NIK hanya mengisi kolom, tidak terikat.
    [$W_ANON, $e_anon] = akun_baru('Uji Anonim');
    foreach (['warga_lookup', 'warga_lookup_jam', 'warga_lookup_harian'] as $p) { ember_akun($p, $W_ANON); }
    ember_ip('warga_lookup_anon');
    $j_anon = jar();
    minta($j_anon, 'warga/pendataan');
    minta($j_anon, 'warga/pendataan', ['step' => 'find_data', 'action' => 'lookup', 'nik' => $NIK_SIM2]);
    minta($j_anon, 'Auth/do_login', ['email' => $e_anon, 'password' => $SANDI]);
    $r = minta($j_anon, 'warga/pendataan');
    cek($profil($W_ANON) === NULL && $cermin($W_ANON) === 0, 'Cek anonim lalu masuk: NIK tidak diikat otomatis ke akun');
    cek($step($r['badan']) === 'find_data' && strpos($r['badan'], 'value="' . $NIK_SIM2 . '"') !== FALSE && stripos($r['badan'], 'Warga Simulasi Parsial') === FALSE,
        'Cek anonim lalu masuk: kolom NIK terisi, data sumber belum terbuka');

    /* ================================================================= 6. Smoke enam peran */
    echo "\n== 6. Login dan logout enam peran (akun seed_agen_peran.php)\n";
    ember_ip('login');
    foreach (['warga', 'pengembang', 'mahasiswa', 'admin_kabkota', 'admin_bidang', 'admin'] as $peran) {
        $j = masuk("agen_$peran@agen.test", $AGEN_SANDI);
        $masuk = strpos(minta($j, 'akun/profil')['badan'], 'Auth/do_login') === FALSE;
        minta($j, 'Auth/logout');
        $keluar = strpos(minta($j, 'akun/profil')['badan'], 'Auth/do_login') !== FALSE;
        cek($masuk && $keluar, "Peran $peran: masuk, lalu keluar");
    }
} catch (Throwable $e) {
    cek(FALSE, 'Suite berhenti: ' . $e->getMessage());
} finally {
    foreach ($akun_uji as $id) {
        foreach (['sf_data_simperum', 'sf_penilaian_perumahan', 'sf_profil_warga'] as $t) { jalan("DELETE FROM $t WHERE user_id=?", [$id]); }
        jalan('DELETE FROM usr_akun WHERE id=?', [$id]);
    }
    if (isset($AGEN)) {
        foreach (['sf_data_simperum', 'sf_penilaian_perumahan', 'sf_profil_warga'] as $t) { jalan("DELETE FROM $t WHERE user_id=?", [$AGEN]); }
    }
    foreach ($ember_asli as $kunci => $baris) {
        jalan('DELETE FROM sys_batas_laju WHERE kunci=?', [$kunci]);
        if ($baris) { jalan('INSERT INTO sys_batas_laju (kunci, jendela_mulai_at, jumlah_gagal) VALUES (?,?,?)', array_values($baris)); }
    }
    foreach ($jar_dibuat as $f) { @unlink($f); }
    $sisa = (int) satu("SELECT COUNT(*) n FROM usr_akun WHERE email LIKE ?", [$TAG . '%'])['n'];
    echo "Akun uji tersisa: $sisa\n";
}
ringkas_dan_keluar();
