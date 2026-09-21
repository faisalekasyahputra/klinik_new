<?php
/**
 * Penjaga regresi kebijakan metode HTTP dan kebersihan URI (form keamanan poin 12.2 dan 12.4).
 * Offline: tanpa basis data dan tanpa jaringan. Jalankan:  php tests/http_uri_test.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

define('BASEPATH', __DIR__ . '/../system/');
$app = realpath(__DIR__ . '/../application');
require $app . '/libraries/Http_policy.php';
require $app . '/libraries/Api_schema.php';
$config = []; require $app . '/config/http_policy.php'; $POL = $config['http_policy'];
$config = []; require $app . '/config/api_schemas.php'; $SKEMA = $config['api_schemas'];

$total = 0;
function check($kondisi, $pesan) { global $total; $total++; if ( ! $kondisi) { throw new RuntimeException($pesan); } }
function sumber($rel) {
    global $app;
    $t = @file_get_contents($app . '/' . $rel);
    if ($t === FALSE) { throw new RuntimeException("Tidak bisa membaca application/$rel"); }
    return $t;
}
$H = new Http_policy();
function c($rute, $metode = 'GET', array $extra = []) { global $H; return $H->check($extra + ['route' => $rute, 'method' => $metode, 'server' => [], 'get' => [], 'post' => [], 'segments' => []]); }

// ------------------------------------------------------------------ 1. Metode HTTP (12.4)
foreach (['GET', 'HEAD', 'POST'] as $m) { check(c('index/index', $m)['ok'] === TRUE, "$m harus dilayani pada rute biasa"); }
foreach (['PUT', 'DELETE', 'PATCH', 'TRACE', 'CONNECT', 'PROPFIND', 'put', 'Delete', ''] as $m) {
    if ($m === '') { continue; }
    $h = c('index/index', $m);
    check($h['ok'] === FALSE && $h['status'] === 405 && $h['structural'] === TRUE && $h['allow'] === ['GET', 'HEAD', 'POST'], "Metode $m harus 405 dengan Allow milik rute dan ditandai struktural");
}
foreach (['HTTP_X_HTTP_METHOD_OVERRIDE', 'HTTP_X_HTTP_METHOD', 'HTTP_X_METHOD_OVERRIDE'] as $hdr) {
    $h = c('index/index', 'POST', ['server' => [$hdr => 'DELETE']]);
    check($h['status'] === 400 && $h['code'] === 'method_override_forbidden' && $h['structural'], "Header $hdr harus ditolak (penerowongan metode)");
}
check(c('index/index', 'POST', ['get' => ['_method' => 'DELETE']])['code'] === 'method_override_forbidden', 'Parameter _method di query harus ditolak');
check(c('index/index', 'POST', ['post' => ['_method' => 'PUT']])['code'] === 'method_override_forbidden', 'Parameter _method di badan harus ditolak');
check(c('index/index', 'POST', ['server' => ['HTTP_X_HTTP_METHOD_OVERRIDE' => '']])['ok'] === TRUE, 'Header penimpa kosong tidak mengganggu');

// OPTIONS: hanya metode milik rute itu.
$o = c('index/index', 'OPTIONS');
check($o['status'] === 204 && $o['code'] === 'options' && $o['allow'] === ['GET', 'HEAD', 'POST', 'OPTIONS'], 'OPTIONS pada rute biasa: 204 + Allow GET, HEAD, POST, OPTIONS');
$o = c('auth/do_login', 'OPTIONS');
check($o['allow'] === ['POST', 'OPTIONS'], 'OPTIONS pada endpoint POST-only hanya menyebut POST');
$o = c('index/cari_wil', 'OPTIONS');
check($o['allow'] === ['GET', 'HEAD', 'OPTIONS'], 'OPTIONS pada endpoint skema GET menyebut GET dan HEAD saja (HEAD ditambahkan otomatis)');
check(c('push/config', 'PUT')['allow'] === ['GET', 'HEAD'], 'Allow pada 405 juga milik rute itu');

// Endpoint yang mengubah keadaan: hanya POST.
foreach ($POL['post_only'] as $rute => $alasan) {
    $g = c($rute, 'GET'); $hd = c($rute, 'HEAD'); $p = c($rute, 'POST');
    check($g['status'] === 405 && $g['allow'] === ['POST'] && $hd['status'] === 405 && $p['ok'] === TRUE, "$rute harus POST-only (GET dan HEAD = 405, POST lolos)");
    check($g['structural'] === FALSE, "$rute: GET salah alamat bukan pelanggaran struktural (tidak memicu peringatan)");
}
check(count($POL['post_only']) >= 20, 'Daftar post_only terlalu pendek');

// ------------------------------------------------------------------ 2. Data sensitif di URI (12.2)
foreach ($POL['sensitive_query'] as $nama) {
    $h = c('index/index', 'GET', ['get' => [$nama => 'x']]);
    check($h['status'] === 400 && $h['code'] === 'sensitive_in_uri' && $h['structural'], "Parameter query '$nama' harus ditolak");
}
check(c('index/index', 'GET', ['get' => ['NIK' => '1']])['code'] === 'sensitive_in_uri' && c('index/index', 'GET', ['get' => ['Password' => '1']])['code'] === 'sensitive_in_uri', 'Nama sensitif dicocokkan tanpa membedakan huruf besar/kecil');
check(c('index/cari_wil', 'GET', ['get' => ['keyword' => 'griya', 'page' => '2', 'limit' => '12', 'kodeWilayah' => '3374']])['ok'] === TRUE, 'Parameter pencarian biasa tidak terganggu');
foreach ([['3374010101900001'], ['a', '3374010101900001'], ['saya@contoh.id'], ['saya%40contoh.id'], ['x', 'KK3374010101900001']] as $i => $segmen) {
    check(c('index/detail', 'GET', ['segments' => $segmen])['code'] === 'sensitive_in_uri', "Segmen jalur berisi NIK/surel #$i harus ditolak");
}
foreach ([['12'], ['TKT-2026-000123'], ['337401010190000'], ['33740101019000012']] as $i => $segmen) {
    check(c('index/detail', 'GET', ['segments' => $segmen])['ok'] === TRUE, "Segmen jalur biasa #$i tidak boleh ditolak");
}

// Api_schema: endpoint POST tidak menerima query string.
$S = new Api_schema();
$sk = $S->find('program', 'api_kalkulasi_program');
$base = ['method' => 'POST', 'post' => ['penghasilan' => '1000000', 'pekerjaan' => 'Wiraswasta', 'status_kepemilikan' => 'Sewa/Kontrak', 'alasan_pengajuan' => 'x'], 'get' => [], 'files' => [], 'segments' => [], 'ajax' => TRUE, 'content_type' => '', 'json' => NULL];
check($S->validate($sk, $base)['ok'] === TRUE, 'POST tanpa query lolos');
$h = $S->validate($sk, ['get' => ['nik' => '3374010101900001']] + $base);
check($h['ok'] === FALSE && isset($h['errors']['_query']) && $h['structural'] === TRUE, 'POST dengan query string ditolak sebagai pelanggaran struktural');
$h = $S->validate($S->find('index', 'cari_wil'), ['method' => 'GET', 'get' => ['keyword' => 'a'], 'post' => [], 'files' => [], 'segments' => [], 'ajax' => FALSE, 'content_type' => '', 'json' => NULL]);
check($h['ok'] === TRUE, 'Endpoint GET tetap boleh memakai query string');

// ------------------------------------------------------------------ 3. Registri konsisten dengan kode
function daftar_controller() {
    global $app; $hasil = [];
    foreach (glob($app . '/controllers/*.php') as $f) { $hasil[strtolower(basename($f, '.php'))] = $f; }
    return $hasil;
}
$ctl = daftar_controller();
foreach (['post_only' => $POL['post_only'], 'get_write_exempt' => $POL['get_write_exempt']] as $nama => $daftar) {
    foreach ($daftar as $rute => $alasan) {
        [$k, $m] = explode('/', $rute);
        check(isset($ctl[$k]), "$nama: controller $k tidak ada");
        check(preg_match('/function\s+' . preg_quote($m, '/') . '\s*\(/i', file_get_contents($ctl[$k])) === 1, "$nama: metode $rute tidak ada");
        check(strlen(trim($alasan)) >= ($nama === "post_only" ? 5 : 20), "$nama: $rute wajib beralasan");
    }
}
check(array_intersect_key($POL['post_only'], $POL['get_write_exempt']) === [], 'Satu rute tidak boleh ada di post_only DAN get_write_exempt');
foreach ($POL['post_only'] as $rute => $_) {
    foreach ($SKEMA[$rute]['methods'] ?? [] as $metode => $__) { check($metode === 'POST', "Skema $rute mengizinkan $metode padahal rute ini post_only"); }
}

// Tidak ada tautan/navigasi GET ke endpoint post_only (akan rusak): href, location, window.open.
$rusak = [];
$aset = [];
foreach (['views', '../assets/js'] as $dir) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($app . '/' . $dir, FilesystemIterator::SKIP_DOTS)) as $f) {
        if (in_array($f->getExtension(), ['php', 'js'], TRUE) && strpos($f->getPathname(), 'vendor') === FALSE && strpos($f->getPathname(), '.min.') === FALSE) { $aset[] = $f->getPathname(); }
    }
}
check(count($aset) > 100, 'Pemindaian view/JS menjangkau terlalu sedikit berkas (' . count($aset) . ')');
foreach ($aset as $f) {
    $s = file_get_contents($f);
    foreach (array_keys($POL['post_only']) as $rute) {
        $pola = preg_quote($rute, '#');
        if (preg_match('#(href\s*=\s*["\'][^"\']*|location(\.href)?\s*=\s*[^;]*|window\.open\([^)]*)' . $pola . '#i', $s)) { $rusak[] = basename($f) . " -> $rute"; }
    }
}
check($rusak === [], 'Tautan GET ke endpoint POST-only: ' . implode('; ', $rusak));

// Setiap metode publik controller yang menulis tanpa penjaga metode harus terdaftar (post_only) atau dikecualikan dengan alasan.
$tulis = '/->(insert|update|delete|replace|insert_batch|update_batch|truncate|empty_table)\(|->(simpan|save|hapus|delete|update|ubah|set_status|transition|create|kirim|tandai|toggle|nonaktifkan|aktifkan|batalkan|setujui|tolak)[a-z_]*\(|unlink\(|file_put_contents\(|move_uploaded_file\(|session->sess_destroy|->trans_begin/i';
$jaga = '/method\(\s*(TRUE)?\s*\)\s*(!==|===|!=|==)\s*[\'"](post|POST)[\'"]|REQUEST_METHOD|input->method\(/';
$belum = []; $dicek = 0;
foreach ($ctl as $k => $f) {
    $src = file_get_contents($f);
    preg_match_all('/\R\s*(public|protected|private)?\s*function\s+(\w+)\s*\(/', $src, $mm, PREG_OFFSET_CAPTURE);
    $badan = []; $vis = [];
    foreach ($mm[2] as $i => [$nama, $pos]) { $badan[$nama] = substr($src, $pos, ($mm[2][$i + 1][1] ?? strlen($src)) - $pos); $vis[$nama] = $mm[1][$i][0]; }
    $penjaga = array_keys(array_filter($badan, function ($b) use ($jaga) { return preg_match($jaga, $b); }));
    foreach ($badan as $n => $b) {
        if ($vis[$n] === 'private' || $vis[$n] === 'protected' || $n[0] === '_' || $n === '__construct' || ! preg_match($tulis, $b)) { continue; }
        $dicek++;
        if (preg_match($jaga, $b)) { continue; }
        foreach ($penjaga as $g) { if ($g !== $n && preg_match('/\$this->' . preg_quote($g, '/') . '\(/', $b)) { continue 2; } }
        $rute = $k . '/' . strtolower($n);
        if ( ! isset($POL['post_only'][$rute]) && ! isset($POL['get_write_exempt'][$rute])) { $belum[] = $rute; }
    }
}
check($dicek >= 30, "Pemindai metode penulis menjangkau terlalu sedikit ($dicek)");
check($belum === [], 'Metode publik yang menulis tanpa penjaga metode, tidak di post_only maupun get_write_exempt: ' . implode(', ', $belum));

// ------------------------------------------------------------------ 4. URI tidak membawa data sensitif (audit statis)
$nama_peka = implode('|', array_map('preg_quote', $POL['sensitive_query']));
$temuan = [];
$kode = $aset;
foreach (glob($app . '/{controllers,libraries,helpers,models,core}/*.php', GLOB_BRACE) as $f) { $kode[] = $f; }
foreach ($kode as $f) {
    $s = file_get_contents($f);
    $rel = ltrim(str_replace('\\', '/', substr($f, strlen($app))), '/');
    if (isset($POL['outbound_sensitive_uri_exempt'][$rel])) { continue; }
    if (preg_match_all('#(?:https?://[^\s"\']*|base_url\(|redirect\(|href\s*=\s*["\']|fetch\(|url\s*:)[^;\n]{0,160}?[?&](' . $nama_peka . ')=#i', $s, $mm)) {
        foreach ($mm[1] as $n) { $temuan[] = "$rel ($n=)"; }
    }
}
check($temuan === [], 'URI berparameter sensitif di kode: ' . implode('; ', array_unique($temuan)));
foreach ($POL['outbound_sensitive_uri_exempt'] as $rel => $alasan) {
    check(is_file($app . '/' . $rel) && strlen($alasan) > 30, "Pengecualian URI keluar $rel harus menunjuk berkas nyata dan beralasan");
}
$chat = sumber('controllers/Chat.php');
check(strpos($chat, 'generateContent?key=') === FALSE && strpos($chat, 'x-goog-api-key') !== FALSE, 'Kunci API Gemini tidak boleh berada di URI (kirim lewat header)');

// Formulir GET tidak boleh memuat kolom sensitif.
$form_get = 0; $bocor = [];
foreach ($aset as $f) {
    if (substr($f, -4) !== '.php') { continue; }
    $s = file_get_contents($f);
    if (preg_match_all('#<form\b[^>]*method\s*=\s*["\']get["\'][^>]*>(.*?)</form>#is', $s, $mm)) {
        foreach ($mm[1] as $isi) {
            $form_get++;
            if (preg_match_all('#name\s*=\s*["\']([A-Za-z0-9_]+)["\']#', $isi, $nm)) {
                foreach ($nm[1] as $n) { if (in_array(strtolower($n), $POL['sensitive_query'], TRUE)) { $bocor[] = basename($f) . ":$n"; } }
            }
        }
    }
}
check($form_get >= 5, "Pemindai formulir GET menemukan terlalu sedikit ($form_get)");
check($bocor === [], 'Formulir GET dengan kolom sensitif (nilainya akan masuk URI): ' . implode(', ', $bocor));

// Header yang tidak membocorkan URI penuh ke pihak lain.
$my = sumber('core/MY_Controller.php') . sumber('helpers/content_security_helper.php');
check(preg_match('/Referrer-Policy:\s*(strict-origin-when-cross-origin|same-origin|no-referrer|strict-origin)\b/', $my) === 1, 'Referrer-Policy harus membatasi Referer (bukan unsafe-url atau no-referrer-when-downgrade)');
check(strpos($my, 'unsafe-url') === FALSE, 'Referrer-Policy unsafe-url tidak boleh dipakai');
check(strpos($my, "header_remove('X-Powered-By')") !== FALSE, 'X-Powered-By (teknologi dan versi PHP) harus dibuang dari respons PHP');
check(strpos((string) file_get_contents(__DIR__ . '/../.htaccess'), 'Header always unset X-Powered-By') !== FALSE, '.htaccess harus membuang X-Powered-By untuk berkas statis');

// ------------------------------------------------------------------ 5. Pemasangan
$ht = (string) file_get_contents(__DIR__ . '/../.htaccess');
check(preg_match('/RewriteCond %\{REQUEST_METHOD\} !\^\(GET\|HEAD\|POST\|OPTIONS\)\$ \[NC\]\s*\R\s*RewriteRule \^ - \[R=405,L\]/', $ht) === 1, '.htaccess harus menolak metode selain GET/HEAD/POST/OPTIONS (termasuk TRACE) di lapisan server');
check(strpos($ht, 'REQUEST_METHOD') < strpos($ht, 'index.php/$1'), 'Penolakan metode di .htaccess harus mendahului aturan front-controller');
check(preg_match('/function __construct\(\)\s*\{(.*?)\R    \}/s', $my, $ktor) === 1
    && strpos($ktor[1], '$this->enforce_http_policy();') !== FALSE
    && strpos($ktor[1], '$this->enforce_anti_automation();') < strpos($ktor[1], '$this->enforce_http_policy();')
    && strpos($ktor[1], '$this->enforce_http_policy();') < strpos($ktor[1], '$this->enforce_api_schema();'),
    'Konstruktor MY_Controller harus memanggil enforce_http_policy() sesudah anti-otomatisasi dan sebelum validasi skema');
check(preg_match('/function enforce_http_policy\(\).*?catch \(Throwable.*?set_status_header\(500\)/s', $my) === 1, 'Galat pemeriksa kebijakan HTTP harus menolak permintaan (fail-closed)');
check(strpos($my, "'kebijakan_http'") !== FALSE && strpos($my, "set_header('Allow: '") !== FALSE && strpos($my, "'options'") !== FALSE, 'Pelanggaran struktural jadi peringatan; 405 dan OPTIONS membawa Allow');
check(strpos(sumber('libraries/Api_schema.php'), '_query') !== FALSE, 'Api_schema harus menolak query string pada endpoint POST');

echo "http_uri_test: OK ($total pemeriksaan, " . count($POL['post_only']) . " endpoint POST-only, $dicek metode penulis diperiksa, $form_get formulir GET diperiksa, " . count($aset) . " berkas view/JS dipindai)\n";
