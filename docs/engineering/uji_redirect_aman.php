<?php
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Penjaga redirect internal: MY_Controller::sanitize_redirect() hanya meloloskan path relatif aplikasi.
 *
 *   php docs/engineering/uji_redirect_aman.php                 (fungsi di working tree + HTTP lokal)
 *   php docs/engineering/uji_redirect_aman.php --rev=2ae7a7b   (fungsi dari revisi git lain, tanpa HTTP)
 *
 * Rencana perbaikan pemindaian keamanan bagian 3 (persiapan uji penetrasi Kominfo). Tiga lapis:
 *   1. Unit: badan sanitize_redirect() diambil dari sumbernya dan dipanggil langsung, tanpa CodeIgniter.
 *      Dengan --rev=2ae7a7b (fungsi lama) berkas ini HARUS merah: itu bukti ujinya menggigit.
 *   2. Sink: setiap redirect() yang tujuannya bisa berasal dari permintaan menyaring ulang tepat
 *      sebelum redirect() (lapis kedua), bukan hanya saat nilai dibaca.
 *   3. HTTP: Auth/logout?curr= adalah sink GET tanpa akun; Location harus tetap di situs ini.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;
function cek($kondisi, $label) {
    $GLOBALS['uji_total']++;
    echo ($kondisi ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $kondisi) { $GLOBALS['uji_gagal']++; }
    return (bool) $kondisi;
}

$rev = getopt('', ['rev::'])['rev'] ?? '';

/* ------------------------------------------------------------ ambil fungsi dari sumber */
$sumber = $rev === ''
    ? (string) file_get_contents(APP_ROOT . '/application/core/MY_Controller.php')
    : (string) shell_exec('git -C ' . escapeshellarg(APP_ROOT) . ' show ' . escapeshellarg($rev . ':application/core/MY_Controller.php'));
$mulai = strpos($sumber, 'protected function sanitize_redirect(');
if ($mulai === FALSE) { echo "  GAGAL sanitize_redirect() tidak ditemukan di sumber\n"; exit(1); }
// Cocokkan kurung kurawal lewat token, supaya kurawal di dalam string/regex tidak mengacaukan.
$tok = token_get_all('<?php ' . substr($sumber, $mulai));
$badan = ''; $kedalaman = 0; $sudah_buka = FALSE;
foreach (array_slice($tok, 1) as $t) {
    $teks = is_array($t) ? $t[1] : $t;
    $badan .= $teks;
    if ($teks === '{' || (is_array($t) && in_array($t[0], [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], TRUE))) { $kedalaman++; $sudah_buka = TRUE; }
    if ($teks === '}') { $kedalaman--; if ($sudah_buka && $kedalaman === 0) { break; } }
}
$berkas_uji = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'uji_redirect_' . bin2hex(random_bytes(4)) . '.php';
file_put_contents($berkas_uji, "<?php\nif ( ! function_exists('base_url')) { function base_url() { return '" . BASE_URL . "/'; } }\n"
    . "class Uji_Saring_Redirect { " . preg_replace('/^protected /', 'public ', $badan) . " }\n");
require $berkas_uji;
unlink($berkas_uji);
$s = new Uji_Saring_Redirect();
// Galat (mis. TypeError pada array dari ?next[]=) dicatat sebagai hasil, bukan mematikan suite.
$saring = function ($x) use ($s) { try { return $s->sanitize_redirect($x); } catch (Throwable $e) { return 'GALAT: ' . get_class($e); } };

echo "== 1. Unit sanitize_redirect()" . ($rev !== '' ? " dari revisi $rev" : '') . "\n";
$DITOLAK = [
    ''                               => 'kosong',
    'javascript:alert(1)'            => 'skema javascript:',
    'JaVaScRiPt:alert(1)'            => 'skema javascript: huruf campur',
    'data:text/html,<script>'        => 'skema data:',
    'http://evil.example'            => 'URL absolut http://',
    'https://evil.example/x'         => 'URL absolut https://',
    BASE_URL . '/Umum'               => 'URL absolut ke situs sendiri (tidak ada pemanggil yang butuh)',
    '//evil.example'                 => 'protocol-relative //host',
    '///evil.example'                => 'garis miring rangkap tiga',
    '/\\evil.example'                => 'garis miring lalu backslash (/\\host)',
    '\\\\evil.example'               => 'backslash ganda (\\\\host)',
    '\\evil.example'                 => 'backslash tunggal',
    'Umum\\..\\x'                    => 'backslash di tengah path',
    '%2f%2fevil.example'             => 'tersandi %2f%2f',
    '%2F%2Fevil.example'             => 'tersandi %2F%2F huruf besar',
    '/%2f%2fevil.example'            => 'tersandi sesudah satu garis miring',
    'Umum/%2f%2fevil.example'        => 'tersandi // di tengah path',
    '%5cevil.example'                => 'tersandi %5c',
    '/%5c%5cevil.example'            => 'tersandi /%5c%5c',
    'Umum%0d%0aSet-Cookie:x=1'       => 'CRLF tersandi %0d%0a (injeksi header)',
    "Umum\r\nSet-Cookie: x=1"        => 'CRLF mentah',
    "Umum\0x"                        => 'NUL mentah',
    'Umum%00x'                       => 'NUL tersandi',
    ' //evil.example'                => 'spasi di depan //host',
    "\t//evil.example"               => 'tab di depan //host',
    'javascript%3aalert(1)'          => 'titik dua tersandi %3a',
    '%252f%252fevil.example'         => 'tersandi dua kali %252f%252f',
    'Umum/../../etc'                 => 'segmen ..',
    'Umum/%2e%2e/x'                  => 'segmen .. tersandi',
    'http:/\\evil.example'           => 'skema dengan /\\',
];
foreach ($DITOLAK as $masuk => $alasan) {
    cek($saring($masuk) === '', "ditolak: $alasan");
}
cek($saring(NULL) === '' && $saring(['Umum']) === '', 'ditolak: bukan string (NULL, array dari ?next[]=)');

$SAH = [
    'Umum/Sebaran'                    => 'Umum/Sebaran',
    '/Umum/Sebaran'                   => 'Umum/Sebaran',
    'akun'                            => 'akun',
    'warga/pendataan'                 => 'warga/pendataan',
    'Pengembang/syarat'               => 'Pengembang/syarat',
    'Admin_Srp2/ubah/12'              => 'Admin_Srp2/ubah/12',
    'Statistika?tahun=2025&tw=1'      => 'Statistika?tahun=2025&tw=1',
    'cari_rumah?q=rumah%20murah'      => 'cari_rumah?q=rumah%20murah',
    'Umum?ref=https://contoh.id'      => 'Umum?ref=https://contoh.id',
    'Program/detail/abc-def_1.2~x'    => 'Program/detail/abc-def_1.2~x',
];
foreach ($SAH as $masuk => $harap) {
    cek($saring($masuk) === $harap, "sah tetap utuh: $masuk");
}

if ($rev !== '') {
    echo "\n{$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal (revisi $rev, lapis sink dan HTTP dilewati)\n";
    exit($GLOBALS['uji_gagal'] ? 1 : 0);
}

/* ------------------------------------------------------------ 2. sink menyaring ulang */
echo "\n== 2. Lapis kedua di setiap sink\n";
$auth = (string) file_get_contents(APP_ROOT . '/application/controllers/Auth.php');
$peng = (string) file_get_contents(APP_ROOT . '/application/controllers/Pengembang.php');
cek(strpos($auth, "redirect(\$this->sanitize_redirect(\$error_target) ?: 'Auth/login');") !== FALSE, 'Auth::_login_fail menyaring redirect_to ulang sebelum redirect()');
cek(strpos($auth, "redirect(\$this->sanitize_redirect(\$redirect_target) ?: 'Auth/register');") !== FALSE, 'Auth::_register_fail menyaring ulang sebelum redirect()');
cek(preg_match('/\$safe_redirect = \$this->sanitize_redirect\(\$from\);.*?set_userdata\(\'intended_url\', \$safe_redirect\);/s', $auth) === 1 && strpos($auth, 'oauth_redirect') === FALSE, 'Auth::google menyaring ?from= lalu menitipkannya ke intended_url (disaring ulang di _redirect_after_login)');
cek(preg_match('/\$aman = \$this->sanitize_redirect\(\$intended\);\s*if \(\$aman !== \'\'\) \{\s*redirect\(\$aman\);/', $auth) === 1, 'Auth::_redirect_after_login menyaring intended_url ulang');
cek(preg_match('/\$safe_redirect = \$this->sanitize_redirect\(\$curr\);.*?redirect\(!empty\(\$safe_redirect\) \? \$safe_redirect : \'login\'\);/s', $auth) === 1, 'Auth::logout menyaring ?curr= sebelum redirect()');
cek(strpos($peng, "\$intended = \$this->sanitize_redirect((string) \$this->session->userdata('intended_url')) ?: 'Pengembang/syarat';") !== FALSE, 'Pengembang::masuk menyaring intended_url ulang sebelum redirect()');
// Tidak ada redirect() lain yang membaca langsung dari permintaan atau sesi.
$langsung = [];
foreach (array_merge(glob(APP_ROOT . '/application/controllers/*.php'), [APP_ROOT . '/application/core/MY_Controller.php']) as $f) {
    if (preg_match_all('/(?<![\w])redirect\([^;\n]*(->input->|\$_GET|\$_POST|\$_REQUEST|\$_SERVER|->userdata\()[^;\n]*\);/',(string) file_get_contents($f), $m)) {
        foreach ($m[0] as $x) { if (strpos($x, 'sanitize_redirect') === FALSE) { $langsung[] = basename($f) . ': ' . $x; } }
    }
}
cek($langsung === [], 'tidak ada redirect() yang memakai masukan/sesi tanpa sanitize_redirect()' . ($langsung ? ': ' . implode(' | ', $langsung) : ''));

/* ------------------------------------------------------------ 3. HTTP lewat sink sungguhan */
echo "\n== 3. HTTP " . BASE_URL . "/Auth/logout?curr=\n";
function lokasi($curr) {
    $ch = curl_init(BASE_URL . '/Auth/logout?curr=' . $curr);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_HEADER => TRUE, CURLOPT_NOBODY => FALSE, CURLOPT_FOLLOWLOCATION => FALSE, CURLOPT_TIMEOUT => 20]);
    $r = (string) curl_exec($ch); $kode = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return [$kode, preg_match('/^Location:\s*(\S+)/mi', $r, $m) ? $m[1] : ''];
}
[$kode] = lokasi('akun');
if ( ! cek($kode >= 300 && $kode < 400, "server lokal menjawab redirect (HTTP $kode)")) { echo "\n{$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n"; exit(1); }
foreach (['%2F%2Fevil.example', '%2F%5Cevil.example', '%5C%5Cevil.example', 'javascript%3Aalert(1)', 'https%3A%2F%2Fevil.example', '%252f%252fevil.example', 'Umum%250d%250aSet-Cookie%3Ax%3D1'] as $c) {
    [$kode, $loc] = lokasi($c);
    cek($loc === BASE_URL . '/login', "curr=$c dialihkan ke /login (Location: $loc)");
}
[$kode, $loc] = lokasi('Umum%2FSebaran');
cek($loc === BASE_URL . '/Umum/Sebaran', "curr=Umum/Sebaran tetap utuh (Location: $loc)");
[$kode, $loc] = lokasi('Statistika%3Ftahun%3D2025');
cek($loc === BASE_URL . '/Statistika?tahun=2025', "curr=Statistika?tahun=2025 tetap utuh (Location: $loc)");

echo "\n{$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] ? 1 : 0);
