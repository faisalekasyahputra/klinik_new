<?php
/**
 * Penjaga regresi kontrol anti-otomatisasi dan peringatan (form keamanan poin 10.4 dan 10.5).
 * Offline: tanpa jaringan dan tanpa basis data. Jalankan:  php tests/anti_automation_test.php
 * Bukti perilaku atas MySQL nyata (semantik dan konkurensi): tests/anti_automation_db_test.php.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

define('BASEPATH', __DIR__ . '/../system/');
$app = realpath(__DIR__ . '/../application');
require $app . '/helpers/anti_automation_helper.php';

$total = 0;
function check($kondisi, $pesan) { global $total; $total++; if ( ! $kondisi) { throw new RuntimeException($pesan); } }
function sumber($rel) {
    global $app;
    $t = @file_get_contents($app . '/' . $rel);
    if ($t === FALSE) { throw new RuntimeException("Tidak bisa membaca application/$rel"); }
    return $t;
}

// --- 1. Deteksi alat pemindai lewat User-Agent ---------------------------------------------
foreach (['sqlmap/1.7.11#stable (https://sqlmap.org)', 'Mozilla/5.00 (Nikto/2.5.0)', 'Nmap Scripting Engine',
          'WPScan v3.8', 'gobuster/3.6', 'Mozilla/5.0 ${jndi:ldap://x/a}', 'SQLMAP'] as $ua) {
    check(anti_automation_is_scanner($ua), "Alat serangan harus dikenali: $ua");
}
foreach (['Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120 Safari/537.36',
          'curl/8.4.0', 'python-requests/2.31', 'Googlebot/2.1', 'UptimeRobot/2.0', ''] as $ua) {
    check( ! anti_automation_is_scanner($ua), "Klien sah/generik TIDAK boleh diblokir: '$ua'");
}
check( ! anti_automation_is_scanner(NULL), 'User-Agent kosong bukan pemindai');

// --- 2. Kelas rute dan metode tulis ---------------------------------------------------------
check(anti_automation_route_classes('Index', 'cari_wil') === ['cari'], 'Index/cari_wil harus kelas cari');
check(anti_automation_route_classes('Program', 'api_cek_simperum') === ['api'], 'Program/api_* harus kelas api');
check(in_array('unduh', anti_automation_route_classes('Admin_Rekap', 'export_xlsx'), TRUE), '*/export* harus kelas unduh');
check(anti_automation_route_classes('Auth', 'login') === [], 'Rute biasa tidak masuk kelas tambahan');
check(anti_automation_is_write('post') && anti_automation_is_write('DELETE') && ! anti_automation_is_write('GET'), 'Metode tulis: POST/DELETE ya, GET tidak');

// --- 3. Pengecualian IP ---------------------------------------------------------------------
check(anti_automation_ip_allowed('127.0.0.1') && anti_automation_ip_allowed('::1'), 'Loopback dikecualikan');
check( ! anti_automation_ip_allowed('203.0.113.7', ''), 'IP luar tidak dikecualikan secara bawaan');
check(anti_automation_ip_allowed('203.0.113.7', '198.51.100.1, 203.0.113.7'), 'IP di daftar env dikecualikan');
check( ! anti_automation_ip_allowed('203.0.113.70', '203.0.113.7'), 'Pencocokan IP harus persis, bukan awalan');
check( ! anti_automation_ip_allowed('', '203.0.113.7'), 'IP kosong tidak dikecualikan');

// --- 3b. Kunci penghitung IPv6 per /64
check(anti_automation_ip_bucket('203.0.113.7') === '203.0.113.7', 'IPv4 tidak diubah');
check(anti_automation_ip_bucket('2404:c0:b301:4e69:ed62:72cc:a4f0:8394') === anti_automation_ip_bucket('2404:00c0:b301:4e69:0:0:0:1'), 'Dua alamat IPv6 di /64 yang sama harus berbagi satu penghitung');
check(anti_automation_ip_bucket('2404:c0:b301:4e69::1') !== anti_automation_ip_bucket('2404:c0:b301:4e6a::1'), 'IPv6 di /64 berbeda punya penghitung berbeda');
check(anti_automation_ip_bucket('::ffff:203.0.113.7') === '203.0.113.7', 'IPv4-mapped IPv6 dihitung sebagai IPv4-nya');
check(anti_automation_ip_bucket('::1') === anti_automation_ip_bucket('0:0:0:0:0:0:0:2'), 'Loopback IPv6 tetap satu bucket');
check(anti_automation_ip_bucket('bukan-ip:') === 'bukan-ip:' && anti_automation_ip_bucket('') === '', 'Nilai yang tak dapat diurai dikembalikan apa adanya');
check(strpos(sumber('libraries/Rate_limiter.php'), 'anti_automation_ip_bucket(') !== FALSE, 'Dimensi ip pada Rate_limiter harus memakai kunci /64 untuk IPv6');

// --- 4. Kebijakan konsisten dengan registry pembatas laju --------------------------------------
$config = []; require $app . '/config/rate_limits.php'; $pol = $config['rate_limit_policies'];
$wajib = ['global_anon', 'global_akun', 'tulis_anon', 'tulis_akun', 'unggah_ip', 'unggah_akun', 'alert_dedupe', 'alert_eskalasi'];
foreach (array_keys(anti_automation_config('route_classes')) as $kelas) { $wajib[] = "kelas_{$kelas}_ip"; $wajib[] = "kelas_{$kelas}_akun"; }
foreach ($wajib as $nama) {
    check(isset($pol[$nama]), "Kebijakan $nama harus terdaftar di config/rate_limits.php");
    check($pol[$nama]['limit'] >= 1 && $pol[$nama]['limit'] <= 255, "$nama: limit harus 1..255 (kolom TINYINT UNSIGNED)");
    check($pol[$nama]['window'] >= 1, "$nama: window harus positif");
}
foreach (['global_anon', 'global_akun', 'tulis_anon', 'tulis_akun', 'kelas_cari_ip', 'kelas_api_ip', 'kelas_unduh_ip'] as $nama) {
    check($pol[$nama]['window'] <= 60, "$nama: jendela harus per menit supaya batas <= 255 berarti sesuatu ($nama)");
}
check($pol['global_anon']['dimensions'] === ['ip'] && $pol['global_akun']['dimensions'] === ['account'], 'Global anonim per IP, global login per akun');
foreach (['alert_dedupe', 'alert_eskalasi'] as $nama) { check( ! empty($pol[$nama]['senyap']), "$nama harus senyap (peringatan tidak boleh memicu peringatan)"); }
check($pol['tulis_anon']['limit'] < $pol['global_anon']['limit'], 'Batas tulis harus lebih ketat daripada batas global');
check($pol['kelas_api_ip']['limit'] < $pol['global_anon']['limit'], 'Batas kelas API harus lebih ketat daripada batas global');

// --- 5. Bot_guard (honeypot + token waktu) ---------------------------------------------------
require $app . '/libraries/Bot_guard.php';
$bg = new Bot_guard();
$t0 = 1_800_000_000;
$tok = $bg->token('login', $t0);
check($bg->check('login', ['bot_token' => $tok, 'situs_web' => ''], $t0 + 5, TRUE)['ok'] === TRUE, 'Token sah, honeypot kosong, waktu wajar: lolos');
check($bg->check('login', ['bot_token' => $tok, 'situs_web' => 'http://spam'], $t0 + 5, TRUE)['reason'] === 'honeypot', 'Honeypot terisi ditolak');
check($bg->check('login', ['bot_token' => $tok, 'situs_web' => 'http://spam'], $t0 + 5, FALSE)['reason'] === 'honeypot', 'Honeypot ditegakkan juga di luar production');
check($bg->check('login', ['situs_web' => ' '], $t0, FALSE)['ok'] === TRUE, 'Honeypot hanya spasi = kosong');
check($bg->check('login', [], $t0, TRUE)['reason'] === 'token_hilang', 'Production: tanpa token ditolak');
check($bg->check('login', [], $t0, FALSE)['ok'] === TRUE, 'Non-production: tanpa token dibiarkan (harness uji HTTP)');
check($bg->check('login', ['bot_token' => $tok], $t0, TRUE)['reason'] === 'terlalu_cepat', 'Production: kirim seketika (0 detik) ditolak');
check($bg->check('login', ['bot_token' => $tok], $t0 + 21601, TRUE)['reason'] === 'kedaluwarsa', 'Production: token lewat 6 jam ditolak');
check($bg->check('register', ['bot_token' => $tok], $t0 + 5, TRUE)['reason'] === 'form_salah', 'Token untuk formulir lain ditolak');
$rusak = substr($tok, 0, -3) . (substr($tok, -3) === 'aaa' ? 'bbb' : 'aaa');
check($bg->check('login', ['bot_token' => $rusak], $t0 + 5, TRUE)['reason'] === 'token_rusak', 'Tanda tangan diubah = token rusak');
$palsu = rtrim(strtr(base64_encode(json_encode(['f' => 'login', 't' => $t0 - 99999])), '+/', '-_'), '=') . '.' . explode('.', $tok)[1];
check($bg->check('login', ['bot_token' => $palsu], $t0 + 5, TRUE)['reason'] === 'token_rusak', 'Isi token dipalsukan (cap waktu) harus ketahuan');
check($bg->check('login', ['bot_token' => 'sampah'], $t0, TRUE)['reason'] === 'token_rusak', 'Token tanpa struktur = rusak');
$html = $bg->fields('login');
check(strpos($html, 'name="situs_web"') !== FALSE && strpos($html, 'name="bot_token"') !== FALSE, 'fields() harus memuat honeypot dan token');
check(strpos($html, 'tabindex="-1"') !== FALSE && strpos($html, 'aria-hidden="true"') !== FALSE, 'Honeypot harus tersembunyi dari pembaca layar dan urutan tab');

// --- 6. Jalur jebakan: setiap alamat punya rute ----------------------------------------------
$route = []; $config = [];
(function () use (&$route, $app) {
    $config = []; require $app . '/config/anti_automation.php';
    foreach ($config['probe_paths'] as $jalur) { $route[$jalur] = 'Jebakan/index'; }
})();
$probe = anti_automation_config('probe_paths');
check(count($probe) >= 15, 'Daftar jalur jebakan terlalu pendek');
foreach ($probe as $j) { check(($route[$j] ?? '') === 'Jebakan/index', "Jalur jebakan $j harus dipetakan ke Jebakan/index"); }
$routes_src = sumber('config/routes.php');
check(strpos($routes_src, "anti_automation.php") !== FALSE && strpos($routes_src, "'Jebakan/index'") !== FALSE, 'routes.php harus memuat pemetaan jalur jebakan dari config');
foreach ($probe as $j) {
    check( ! in_array(strtolower($j), ['admin', 'login', 'auth', 'index', 'program'], TRUE), "Jalur jebakan $j bentrok dengan rute aplikasi");
}
$jebakan = sumber('controllers/Jebakan.php');
check(strpos($jebakan, "extends MY_Controller") !== FALSE, 'Jebakan harus MY_Controller');
check(strpos($jebakan, 'set_status_header(404)') !== FALSE, 'Jebakan harus menjawab 404 biasa (pemindai tidak diberi tahu)');
check(strpos($jebakan, 'preg_replace') !== FALSE, 'Path dari penyerang harus disanitasi sebelum dicatat');

// --- 7. Pemasangan: kontrol benar-benar terhubung ---------------------------------------------
$my = sumber('core/MY_Controller.php');
check(preg_match('/function __construct\(\)\s*\{(.*?)\R    \}/s', $my, $ktor) === 1 && strpos($ktor[1], '$this->enforce_anti_automation();') !== FALSE, 'MY_Controller::__construct harus memanggil enforce_anti_automation() (kontrol GLOBAL)');
check(strpos($my, 'anti_automation->guard(') !== FALSE && strpos($my, 'rate_limit_reject(') !== FALSE, 'Penolakan harus lewat guard() dan rate_limit_reject()');
check(strpos($my, 'anti_automation_ip_allowed') !== FALSE && strpos($my, 'anti_automation_is_scanner') !== FALSE, 'MY_Controller harus memakai helper pengecualian dan deteksi pemindai');
check(preg_match('/catch \(Throwable \$e\)\s*\{[^}]*fail-open/s', $my) === 1, 'enforce_anti_automation harus fail-open bila penyimpanan bermasalah');
check(strpos(sumber('config/autoload.php'), "'anti_automation'") !== FALSE, 'Helper anti_automation harus di-autoload');

$auth = sumber('controllers/Auth.php');
foreach (["_bot_gate('login'", "_bot_gate('register'"] as $panggil) { check(strpos($auth, $panggil) !== FALSE, "Auth harus memanggil $panggil"); }
check(strpos($auth, "'akun_terkunci'") !== FALSE, 'Penguncian akun harus menghasilkan peringatan keamanan');
$formulir = ['components/login_modal.php' => 'login', 'pages/auth/login.php' => 'login', 'pages/auth/register.php' => 'register',
             'pages/pengembang/archive/daftar_standalone.php' => 'register', 'pages/pengembang/masuk.php' => 'login'];
foreach ($formulir as $view => $form) { check(strpos(sumber("views/$view"), "bot_guard_fields('$form')") !== FALSE, "views/$view harus menyisipkan bot_guard_fields('$form')"); }
$iv = sumber('config/input_validation.php');
check(strpos($iv, 'bot_token') !== FALSE && strpos($iv, 'situs_web') !== FALSE, 'Kolom bot_token/situs_web harus diizinkan oleh Input_guard');

check(strpos(sumber('controllers/Admin.php'), 'security_alert->ringkasan()') !== FALSE, 'Admin::index harus menyediakan ringkasan peringatan');
check(strpos(sumber('views/admin/antrean/dashboard.php'), 'peringatan_keamanan') !== FALSE, 'Dasbor superadmin harus menampilkan banner peringatan');
$rl = sumber('libraries/Rate_limiter.php');
check(strpos($rl, 'raise_alert(') !== FALSE && strpos($rl, 'first_excess') !== FALSE, 'Pelampauan batas pertama harus diteruskan sebagai peringatan admin');
$sa = sumber('libraries/Security_alert.php');
check(strpos($sa, 'sys_jejak_audit') !== FALSE && strpos($sa, 'web_push_service') !== FALSE, 'Peringatan harus masuk jejak audit dan Web Push');
check(strpos($sa, 'alert_dedupe') !== FALSE && strpos($sa, 'alert_eskalasi') !== FALSE, 'Penekan duplikat dan eskalasi harus dipakai');
check(strpos($sa, 'catch (Throwable') !== FALSE, 'Security_alert harus gagal diam-diam');

// Kontrol global tak boleh terlewat: setiap controller harus berada di rantai pewarisan MY_Controller.
// Satu-satunya pengecualian, Migrate, menolak semua permintaan non-CLI/non-loopback dengan 404 sendiri.
$induk = [];
foreach (glob($app . '/core/*.php') as $berkas) {
    if (preg_match_all('/^(?:abstract\s+)?class\s+(\w+)\s+extends\s+(\w+)/m', file_get_contents($berkas), $mm, PREG_SET_ORDER)) {
        foreach ($mm as $x) { $induk[$x[1]] = $x[2]; }
    }
}
$tanpa = [];
$jumlah = 0;
foreach (glob($app . '/controllers/*.php') as $berkas) {
    if (basename($berkas) === 'Migrate.php') { continue; }
    if ( ! preg_match('/^class\s+\w+\s+extends\s+(\w+)/m', file_get_contents($berkas), $mm)) { continue; }
    $jumlah++;
    $k = $mm[1];
    for ($i = 0; $i < 6 && $k !== 'MY_Controller' && isset($induk[$k]); $i++) { $k = $induk[$k]; }
    if ($k !== 'MY_Controller') { $tanpa[] = basename($berkas); }
}
check($jumlah >= 40, "Jumlah controller yang diperiksa terlalu sedikit ($jumlah)");
check($tanpa === [], 'Controller di luar rantai MY_Controller lolos dari kontrol global: ' . implode(', ', $tanpa));
check(strpos(sumber('controllers/Migrate.php'), "is_cli_request()") !== FALSE, 'Pengecualian Migrate harus tetap membatasi CLI/localhost');

echo "anti_automation_test: OK ($total pemeriksaan, $jumlah controller berada di bawah kontrol global)\n";
