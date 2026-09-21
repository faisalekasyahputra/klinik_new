<?php
/**
 * Penjaga regresi penggunaan fitur kode dinamis (form keamanan poin 4.6).
 * Offline: tanpa basis data dan tanpa jaringan. Jalankan:  php tests/dynamic_code_test.php
 *
 * "Fitur kode dinamis" = apa pun yang membuat teks/nilai runtime dijalankan sebagai kode atau
 * memilih kode yang dijalankan: eval, assert, include dengan jalur dinamis, unserialize, pemanggilan
 * fungsi/metode/kelas lewat nama variabel, variabel-variabel, extract, ekspresi Alpine/JS yang
 * dirakit dari data. Pedomannya: TIDAK ADA di kode buatan sendiri, dan yang tersisa (ditinjau dan
 * terdaftar di bawah beserta alasannya) tidak pernah menerima masukan pengguna.
 * Pelengkap: tests/no_dynamic_code_test.php (eval/exec/short tag) dan pemindai kode berbahaya.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$akar = realpath(__DIR__ . '/..');
$app = $akar . '/application';
$total = 0;
function check($kondisi, $pesan) { global $total; $total++; if ( ! $kondisi) { throw new RuntimeException($pesan); } }
function rel($p) { global $akar; return ltrim(str_replace('\\', '/', substr($p, strlen($akar))), '/'); }

/* ============================================================ daftar yang DITINJAU (dengan alasan) */
/** Pemanggilan metode/properti dinamis: nama berasal dari konfigurasi/kode, bukan dari masukan pengguna. */
$DINAMIS_DITINJAU = [
    'application/controllers/Admin_Privileges.php' => '$user->{$module[\'scope\']}: nama kolom dari config/dashboard_modules.php (konfigurasi), bukan masukan',
    'application/views/pages/program/hasil_diagnosa.php' => '$source->{$key}: pembaca properti generik; kunci literal di view yang sama',
];
/** include/require: hanya jalur dari konstanta/dirname/literal (diperiksa struktur), tidak ada yang dari variabel. */
$AWALAN_INCLUDE_SAH = ['APPPATH', 'FCPATH', 'BASEPATH', '__DIR__', 'dirname'];

/* ============================================================ 1. Pindai PHP (token) */
$dilarang_fungsi = ['assert', 'create_function', 'exec', 'system', 'passthru', 'shell_exec', 'popen', 'proc_open', 'pcntl_exec',
    'unserialize', 'call_user_func', 'call_user_func_array', 'forward_static_call', 'forward_static_call_array', 'extract',
    'import_request_variables', 'parse_str', 'dl', 'runkit_function_add', 'runkit7_function_add', 'override_function', 'get_defined_vars'];
$temuan = []; $berkas_php = 0; $var_func = 0; $closure_lokal = 0;
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($app, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if (strtolower($f->getExtension()) !== 'php') { continue; }
    $p = $f->getPathname(); $r = rel($p);
    if (strpos($r, '/archive/') !== FALSE) { continue; }
    $berkas_php++;
    $src = file_get_contents($p);
    $t = token_get_all($src); $n = count($t);
    $sig = function ($i, $d) use ($t, $n) {
        $k = $i + $d;
        while ($k >= 0 && $k < $n && is_array($t[$k]) && in_array($t[$k][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], TRUE)) { $k += $d; }
        return ($k >= 0 && $k < $n) ? $t[$k] : NULL;
    };
    // Variabel yang sah dipanggil: ditetapkan sebagai closure/fn di berkas ini, dilewatkan sebagai closure lewat use(), atau parameter callable.
    $boleh_dipanggil = [];
    if (preg_match_all('/(\$\w+)\s*=\s*(?:static\s+)?(?:function\s*\(|fn\s*\()/', $src, $m)) { foreach ($m[1] as $v) { $boleh_dipanggil[$v] = TRUE; } }
    if (preg_match_all('/use\s*\(([^)]*)\)/', $src, $m)) { foreach ($m[1] as $daftar) { if (preg_match_all('/&?(\$\w+)/', $daftar, $mv)) { foreach ($mv[1] as $v) { $boleh_dipanggil[$v] = TRUE; } } } }
    if (preg_match_all('/callable\s+(\$\w+)/', $src, $m)) { foreach ($m[1] as $v) { $boleh_dipanggil[$v] = TRUE; } }
    // Ekspresi jalur include yang sah.
    for ($i = 0; $i < $n; $i++) {
        $x = $t[$i];
        $baris = is_array($x) ? $x[2] : 0;
        if ( ! $baris) { for ($k = $i; $k >= 0; $k--) { if (is_array($t[$k])) { $baris = $t[$k][2]; break; } } }
        $lok = "$r:$baris";
        if ($x === '`') { $temuan[] = "$lok backtick (eksekusi shell)"; continue; }
        if ($x === '$') {
            $nx = $t[$i + 1] ?? NULL;
            if ($nx === '$' || $nx === '{' || (is_array($nx) && $nx[0] === T_VARIABLE)) { $temuan[] = "$lok variabel-variabel"; }
            continue;
        }
        if ( ! is_array($x)) { continue; }
        if ($x[0] === T_EVAL) { $temuan[] = "$lok eval"; continue; }
        if (in_array($x[0], [T_INCLUDE, T_INCLUDE_ONCE, T_REQUIRE, T_REQUIRE_ONCE], TRUE)) {
            $ekspr = ''; for ($k = $i + 1; $k < $n && $t[$k] !== ';' && ! (is_array($t[$k]) && $t[$k][0] === T_CLOSE_TAG); $k++) { $ekspr .= is_array($t[$k]) ? $t[$k][1] : $t[$k]; }
            $ekspr = trim(preg_replace('/\s+/', ' ', $ekspr));
            // Berkas view dengan HTML campur: "include" di teks HTML/JS bukan token include (T_INLINE_HTML); di sini hanya kode PHP sungguhan.
            $tanpa_literal = preg_replace('/([\'"])(?:(?!\1)[^\\\\]|\\\\.)*\1/', '', $ekspr);
            $ok_awalan = FALSE; foreach ($AWALAN_INCLUDE_SAH as $a) { if (preg_match('/^\(?\s*' . $a . '\b/', $ekspr) || $tanpa_literal === '' ) { $ok_awalan = TRUE; } }
            $ada_var = preg_match('/\$/', $tanpa_literal) === 1;
            if ( ! $ok_awalan || $ada_var) { $temuan[] = "$lok include/require dengan jalur dinamis: $ekspr"; }
            continue;
        }
        if ($x[0] === T_NEW) {
            $nx = $sig($i, 1);
            if (is_array($nx) && $nx[0] === T_VARIABLE) { $temuan[] = "$lok new \$variabel (kelas dinamis)"; }
            if (is_array($nx) && $nx[0] === T_STRING && stripos($nx[1], 'Reflection') === 0) { $temuan[] = "$lok Reflection"; }
            continue;
        }
        if ($x[0] === T_STRING) {
            $nama = strtolower($x[1]);
            $nx = $sig($i, 1); $pv = $sig($i, -1);
            $panggilan = ($nx === '(') && ! (is_array($pv) && in_array($pv[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON, T_FUNCTION, T_NEW], TRUE));
            if ($panggilan && in_array($nama, $dilarang_fungsi, TRUE)) { $temuan[] = "$lok $nama()"; }
            if ($panggilan && $nama === 'preg_replace') {
                for ($k = $i + 1; $k < $n && $t[$k] !== '('; $k++) {}
                $a1 = $sig($k, 1);
                if (is_array($a1) && $a1[0] === T_CONSTANT_ENCAPSED_STRING && preg_match('/[\'"].(?:.*)[^a-zA-Z]([a-zA-Z]*e[a-zA-Z]*)[\'"]$/s', $a1[1]) && preg_match('#^[\'"](.).*\1[a-df-zA-Z]*e[a-zA-Z]*[\'"]$#s', $a1[1])) { $temuan[] = "$lok preg_replace dengan pengubah /e"; }
            }
            continue;
        }
        if ($x[0] === T_VARIABLE) {
            $nx = $sig($i, 1); $pv = $sig($i, -1);
            if ($nx === '(' && ! (is_array($pv) && in_array($pv[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR, T_DOUBLE_COLON], TRUE))) {
                $var_func++;
                if ($x[1] === '$this') { continue; }
                if (isset($boleh_dipanggil[$x[1]])) { $closure_lokal++; continue; }
                $temuan[] = "$lok pemanggilan lewat variabel {$x[1]}( tanpa closure lokal yang menetapkannya";
            }
            if (is_array($pv) && in_array($pv[0], [T_OBJECT_OPERATOR, T_NULLSAFE_OBJECT_OPERATOR], TRUE) && $nx === '(') { $temuan[] = "$lok metode dinamis ->{$x[1]}()"; }
            if (is_array($pv) && $pv[0] === T_DOUBLE_COLON && $nx === '(') { $temuan[] = "$lok metode statis dinamis ::{$x[1]}()"; }
            if ($nx === '::') { $temuan[] = "$lok kelas dinamis {$x[1]}::"; }
        }
    }
    // Properti dinamis ->{expr}, hanya yang terdaftar.
    if (preg_match_all('/->\s*\{/', $src, $m, PREG_OFFSET_CAPTURE)) {
        if ( ! isset($DINAMIS_DITINJAU[$r])) { foreach ($m[0] as $mm) { $temuan[] = $r . ':' . (substr_count(substr($src, 0, $mm[1]), "\n") + 1) . ' properti/metode dinamis ->{...}'; } }
    }
}
check($berkas_php > 300, "Pemindaian PHP menjangkau terlalu sedikit berkas ($berkas_php)");
check($var_func > 300 && $closure_lokal > 250, "Pemeriksa closure lokal menjangkau terlalu sedikit ($var_func pemanggilan variabel, $closure_lokal terbukti closure)");
check($temuan === [], "Fitur kode dinamis di PHP buatan sendiri:\n  " . implode("\n  ", $temuan));
foreach ($DINAMIS_DITINJAU as $berkas => $alasan) { check(is_file($akar . '/' . $berkas) && strlen($alasan) > 30, "Pengecualian $berkas harus menunjuk berkas nyata dan beralasan"); }

/* ============================================================ 2. Pemuatan view/pustaka dengan nama dinamis */
// MY_Controller::render/render_admin menerima nama view dari pemanggil; semua pemanggil harus memberi literal.
$non_literal = [];
foreach (glob($app . '/controllers/*.php') as $f) {
    $s = file_get_contents($f);
    if (preg_match_all('/\$this->(render|render_admin)\(\s*([^\'"\s][^,)]*)/', $s, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[0] as $i => $mm) { $non_literal[] = basename($f) . ':' . (substr_count(substr($s, 0, $mm[1]), "\n") + 1) . ' ' . trim($mm[0]); }
    }
    if (preg_match_all('/->load->(view|library|model|helper|driver)\(\s*(\$\w+)/', $s, $m, PREG_OFFSET_CAPTURE)) {
        foreach ($m[0] as $mm) { $non_literal[] = basename($f) . ':' . (substr_count(substr($s, 0, $mm[1]), "\n") + 1) . ' ' . trim($mm[0]); }
    }
}
check($non_literal === [], 'Pemuatan view/pustaka dengan nama dari variabel di controller (nama tidak boleh berasal dari masukan): ' . implode('; ', $non_literal));
$my = file_get_contents($app . '/core/MY_Controller.php');
check(preg_match('/function render\(\$view, \$data = \[\]\)/', $my) === 1 && strpos($my, '->load->view($view') !== FALSE, 'render() harus tetap satu-satunya penerus nama view dan dipanggil dengan literal');

/* ============================================================ 3. JavaScript buatan sendiri dan skrip inline */
$js_dilarang = '/\beval\s*\(|\bnew\s+Function\s*\(|(?<![\w.$])Function\s*\(|\b(?:setTimeout|setInterval)\s*\(\s*[\'"`]|\bdocument\.write(?:ln)?\s*\(|\bexecScript\b|\bx-html\s*=|\bv-html\s*=/';
$js_temuan = []; $js_berkas = 0;
$sumber_js = glob($akar . '/assets/js/*.js');
foreach ($it2 = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($app . '/views', FilesystemIterator::SKIP_DOTS)) as $f) {
    if ($f->getExtension() === 'php' && strpos(rel($f->getPathname()), '/archive/') === FALSE) { $sumber_js[] = $f->getPathname(); }
}
foreach ($sumber_js as $p) {
    $js_berkas++;
    $s = file_get_contents($p);
    if (substr($p, -3) === 'php') {
        // Hanya isi <script> inline dan atribut Alpine/handler yang relevan; sisa HTML tidak relevan untuk eval.
        $bagian = '';
        if (preg_match_all('#<script\b[^>]*>(.*?)</script>#is', $s, $ms)) { $bagian .= implode("\n", $ms[1]); }
        if (preg_match_all('/\b(?:x-[a-z:.-]+|@[a-z.:-]+|:[a-z-]+)\s*=\s*"([^"]*)"/i', $s, $ma)) { $bagian .= "\n" . implode("\n", $ma[0]); }
        $s = $bagian;
    }
    if (preg_match_all($js_dilarang, $s, $m)) { foreach ($m[0] as $hit) { $js_temuan[] = rel($p) . ' ' . trim($hit); } }
}
check($js_berkas > 150, "Pemindaian JS/view menjangkau terlalu sedikit berkas ($js_berkas)");
check($js_temuan === [], 'eval/new Function/timer string/document.write/x-html di JS buatan sendiri: ' . implode('; ', array_unique($js_temuan)));

/* ============================================================ 4. Echo PHP di konteks kode (skrip inline dan ekspresi Alpine) */
// Nilai apa pun yang dicetak ke dalam JS atau ekspresi Alpine adalah kode yang dirakit dari data. Wajib dibungkus:
// json_encode (dengan htmlspecialchars di atribut), cast angka, atau fungsi URL/CSRF; atau ternari dua literal.
$aman = '/json_encode|htmlspecialchars|html_escape|\(int\)|\(float\)|\(bool\)|intval|number_format|base_url|site_url|csrf|count\(|date\(|nonce|config->item|\bcsp_|asset_url/i';
$ternari_literal = "/^[^?]+\?\s*'[^']*'\s*:\s*'[^']*'\s*$/";
$echo_temuan = []; $echo_total = 0;
foreach (glob($app . '/views/{,*/,*/*/,*/*/*/}*.php', GLOB_BRACE) as $p) {
    if (strpos(rel($p), '/archive/') !== FALSE) { continue; }
    $s = file_get_contents($p);
    $konteks = [];
    if (preg_match_all('#<script\b[^>]*>(.*?)</script>#is', $s, $ms, PREG_OFFSET_CAPTURE)) { foreach ($ms[1] as $c) { $konteks[] = $c; } }
    if (preg_match_all('/\b(?:x-data|x-init|x-bind:[\w-]+|x-on:[\w.-]+|x-text|x-show|x-if|x-for|x-model|:[a-z-]+|@[a-z.-]+)\s*=\s*("([^"]*<\?[^"]*)"|\'([^\']*<\?[^\']*)\')/i', $s, $ma, PREG_OFFSET_CAPTURE)) { foreach ($ma[1] as $c) { $konteks[] = $c; } }
    foreach ($konteks as [$isi, $pos]) {
        if (preg_match_all('/<\?=\s*(.*?)\s*;?\s*\?>/s', $isi, $me)) {
            foreach ($me[1] as $ekspr) {
                $echo_total++;
                $ekspr = trim(preg_replace('/\s+/', ' ', $ekspr));
                if (preg_match($aman, $ekspr) || preg_match($ternari_literal, $ekspr) || preg_match('/^\$(judul_js|kpkp_notifications_json)\b/', $ekspr)) { continue; }
                $echo_temuan[] = rel($p) . ': <?= ' . substr($ekspr, 0, 80) . ' ?>';
            }
        }
    }
}
check($echo_total > 80, "Pemeriksa echo di konteks kode menjangkau terlalu sedikit ($echo_total)");
check($echo_temuan === [], "Echo PHP tanpa pembungkus aman di dalam skrip inline/ekspresi Alpine:\n  " . implode("\n  ", array_unique($echo_temuan)));
// Regresi khusus: nilai isian ulang tidak boleh masuk ke ekspresi Alpine lewat html_escape di dalam kutip tunggal.
$onb = file_get_contents($app . '/views/pages/auth/onboarding.php');
check(strpos($onb, "onboardingForm('<?=") === FALSE && strpos($onb, 'onboardingForm(<?= htmlspecialchars(json_encode(') !== FALSE, 'onboarding: nilai role harus lewat json_encode (html_escape saja tidak cukup: entitas didekode sebelum Alpine mengevaluasi)');

/* ============================================================ 5. Kode mati dan konfigurasi */
check( ! is_file($app . '/models/Buka_peta.php'), 'Model Buka_peta (CRUD tabel dinamis + extract, tanpa pemanggil) harus tetap dihapus');
check(strpos(file_get_contents($app . '/config/content_security.php'), "'unsafe-eval'") !== FALSE && preg_match('/unsafe-eval.{0,200}Alpine|Alpine.{0,400}unsafe-eval/s', file_get_contents($app . '/config/content_security.php')) === 1,
    "CSP 'unsafe-eval' harus tetap terdokumentasi sebagai konsekuensi Alpine (batas yang diakui), bukan tersisa tanpa alasan");
$cfg = file_get_contents($app . '/config/config.php');
check(preg_match('/\$config\[\x27rewrite_short_tags\x27\]\s*=\s*FALSE\s*;/', $cfg) === 1, 'rewrite_short_tags harus FALSE');
$ini = (string) @file_get_contents($akar . '/.user.ini');
check(strpos($ini, 'expose_php=Off') !== FALSE, '.user.ini harus mematikan expose_php');

echo "dynamic_code_test: OK ($total pemeriksaan; $berkas_php berkas PHP, $var_func pemanggilan-variabel (semua closure lokal), $js_berkas berkas JS/view, $echo_total echo di konteks kode)\n";
