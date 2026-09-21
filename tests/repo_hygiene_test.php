<?php
/**
 * Penjaga "hapus fitur, dokumentasi, sampel, dan konfigurasi yang tidak diperlukan" (form keamanan poin 13.3).
 * Offline: tanpa basis data dan tanpa jaringan. Jalankan:  php tests/repo_hygiene_test.php
 *
 * Yang dijaga: (1) berkas mati yang sudah dibuang tidak kembali, tiap view punya pemanggil; (2) DocumentRoot hanya
 * menyajikan yang publik (daftar IZIN di .htaccess) dan tidak ada salinan konfigurasi/cadangan/dump di akar; (3) repo
 * publik tidak memuat IP, akun hosting, atau kredensial bawaan; (4) fitur sampel (mode simulasi, migrasi data demo,
 * panel "Kredensial Demo") tidak aktif di production. Pelengkap: docs/engineering/bersihkan_data_sampel.php (data live).
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$akar = realpath(__DIR__ . '/..');
$total = 0;
function check($kondisi, $pesan) { global $total; $total++; if ( ! $kondisi) { throw new RuntimeException($pesan); } }
function rel($p) { global $akar; return ltrim(str_replace('\\', '/', substr($p, strlen($akar))), '/'); }
function baca($r) { global $akar; $p = $akar . '/' . $r; return is_file($p) ? (string) file_get_contents($p) : ''; }
function pindai($dir, $ekstensi) {
    $hasil = [];
    if ( ! is_dir($dir)) { return $hasil; }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) { if (in_array(strtolower($f->getExtension()), $ekstensi, TRUE)) { $hasil[] = $f->getPathname(); } }
    return $hasil;
}

/* ============================================================ 1. Berkas mati tidak kembali */
$DIBUANG = [
    'assets/css/style.css', 'assets/js/script.js',
    'application/views/welcome_message.php', 'application/views/registrasi.php',
    'application/views/pages/kemitraan/ketentuan.php', 'application/views/pages/kemitraan/kkn.php', 'application/views/pages/kemitraan/magang.php',
    'application/views/pages/pengembang/publikasi.php', 'application/views/pages/pengembang/tambah_publikasi.php',
    'application/views/pages/perumahan/detail.php', 'application/views/pages/user/pengaturan.php',
    'application/models/Buka_peta.php',
];
foreach ($DIBUANG as $r) { check( ! is_file($akar . '/' . $r), "Berkas mati $r sudah dibuang dan tidak boleh kembali"); }
check( ! is_dir($akar . '/docs/archive'), 'docs/archive (dokumen usang, sebagian memuat detail infrastruktur) tidak boleh kembali');
$arsip = [];
foreach (['application/views', 'assets'] as $d) {
    if ( ! is_dir($akar . '/' . $d)) { continue; }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($akar . '/' . $d, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($it as $f) { if ($f->isDir() && strtolower($f->getFilename()) === 'archive') { $arsip[] = rel($f->getPathname()); } }
}
check($arsip === [], "Direktori archive/ (view/aset lama yang tidak dipakai, ikut ter-deploy) tidak boleh ada:\n  " . implode("\n  ", $arsip));

// Tiap view harus disebut (jalur relatif tanpa .php) oleh kode lain; view tanpa pemanggil = kode mati yang ikut dideploy.
$korpus = '';
foreach (['application/controllers', 'application/core', 'application/libraries', 'application/models', 'application/helpers', 'application/config', 'application/views'] as $d) {
    foreach (pindai($akar . '/' . $d, ['php']) as $p) { $korpus .= file_get_contents($p) . "\n"; }
}
$CI_BAWAAN = ['errors/cli/', 'errors/html/'];   // dimuat oleh inti CodeIgniter (show_error/show_404), bukan lewat nama di kode aplikasi
$yatim = []; $n_view = 0;
foreach (pindai($akar . '/application/views', ['php']) as $p) {
    $v = rel($p); $n = substr($v, strlen('application/views/'), -4); $n_view++;
    $ci = FALSE; foreach ($CI_BAWAAN as $a) { if (strpos($n . '/', $a) === 0 || strpos($n, $a) === 0) { $ci = TRUE; } }
    if ($ci) { continue; }
    if (strpos($korpus, $n) === FALSE) { $yatim[] = $v; }
}
check($n_view > 100, "Pemeriksa view menjangkau terlalu sedikit ($n_view)");
check($yatim === [], "View tanpa pemanggil (kode mati):\n  " . implode("\n  ", $yatim));

/* ============================================================ 2. DocumentRoot: hanya yang publik */
$ht = baca('.htaccess');
check(preg_match('#RewriteRule \^\(\?!\$\|index\\\\\.php\(/\|\$\)\|push-sw\\\\\.js\$\|manifest\\\\\.webmanifest\$\|assets/\|\\\\\.well-known/\)\.\+ - \[F,L\]#', $ht) === 1,
    '.htaccess harus menolak (403) setiap berkas/direktori nyata di luar daftar izin (index.php, push-sw.js, manifest.webmanifest, assets/, .well-known/)');
check(preg_match('#RewriteCond %\{REQUEST_FILENAME\} -f \[OR\]\s*\n\s*RewriteCond %\{REQUEST_FILENAME\} -d\s*\n\s*RewriteRule \^\(\?!#', $ht) === 1,
    'Aturan daftar-izin harus berlaku hanya untuk berkas/direktori yang benar-benar ada (alamat virtual aplikasi tetap diteruskan ke index.php)');
check(strpos($ht, 'RewriteRule ^(docs|dev-scripts|tests|vendor)/ - [F,L]') !== FALSE, '.htaccess harus tetap menolak docs, dev-scripts, tests, vendor');
check(strpos($ht, '(sql|dump|bak)') !== FALSE && strpos($ht, '(^|/)\.(?!well-known/)[^/]*') !== FALSE, '.htaccess harus tetap menolak dump/cadangan dan dotfile');
$izin_urutan = strpos($ht, '-d' . "\n"); $front = strpos($ht, 'index.php/$1');
check($izin_urutan !== FALSE && $front !== FALSE && $izin_urutan < $front, 'Aturan daftar-izin harus sebelum aturan front-controller');

// Akar proyek bersih dari cadangan/dump/salinan konfigurasi yang tidak dikenal (lahir dari kebiasaan "backup dulu" di direktori kerja).
$akar_kotor = [];
foreach (scandir($akar) as $n) {
    if ($n === '.' || $n === '..' || is_dir($akar . '/' . $n)) { continue; }
    if (preg_match('/^\.env\.(?!example$).+/', $n) || preg_match('/\.(sql|dump|bak|orig|old|swp|log)(\.gz)?$/i', $n) || preg_match('/\.bak[-_.]/i', $n) || preg_match('/^(backup|dump)[-_]/i', $n)) { $akar_kotor[] = $n; }
}
check($akar_kotor === [], "Akar proyek memuat cadangan/dump/salinan konfigurasi (bisa tersaji publik jika lolos dari .htaccess):\n  " . implode("\n  ", $akar_kotor));
$gi = baca('.gitignore');
check(preg_match('/^\.env\b/m', $gi) === 1, '.env harus di .gitignore');

/* ============================================================ 3. Repo publik: tanpa IP, akun hosting, kredensial bawaan */
$IP_DIIZINKAN = ['127.0.0.1', '0.0.0.0', '255.255.255.255', '255.255.255.0', '255.255.255.255'];
$IP_BERKAS_DIIZINKAN = ['application/config/config.php' => ['10.0.1.200', '192.168.5.0']];   // contoh di komentar bawaan CodeIgniter
$berkas_teks = [];
foreach (['docs', 'application', 'tests', 'dev-scripts'] as $d) { foreach (pindai($akar . '/' . $d, ['md', 'php', 'json', 'sh', 'yml', 'txt']) as $p) { $berkas_teks[] = $p; } }
foreach (glob($akar . '/*.md') as $p) { $berkas_teks[] = $p; }
$berkas_teks[] = $akar . '/.htaccess'; $berkas_teks[] = $akar . '/.user.ini';
$ip_temuan = []; $akun_temuan = [];
foreach ($berkas_teks as $p) {
    $r = rel($p);
    if (strpos($r, 'application/fixtures/') === 0 || strpos($r, 'application/cache/') === 0 || strpos($r, 'application/logs/') === 0 || strpos($r, 'node_modules/') !== FALSE || $r === 'tests/repo_hygiene_test.php') { continue; }   // cache, log runtime, dan salinan pihak ketiga tidak dilacak git
    $s = (string) file_get_contents($p);
    if (preg_match_all('/(?<![\d.])(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})(?![\d.])/', $s, $m, PREG_SET_ORDER)) {
        foreach ($m as $x) {
            if ($x[1] > 255 || $x[2] > 255 || $x[3] > 255 || $x[4] > 255) { continue; }               // bukan IP
            if (preg_match('/^0\d/', $x[1]) || preg_match('/^0\d/', $x[2]) || preg_match('/^0\d/', $x[3]) || preg_match('/^0\d/', $x[4]) || ($x[2] === '000' && $x[3] === '000')) { continue; }   // angka berformat ribuan (3.000.000.000), bukan IP
            if (in_array($x[0], $IP_DIIZINKAN, TRUE) || in_array($x[0], $IP_BERKAS_DIIZINKAN[$r] ?? [], TRUE)) { continue; }
            if ((int) $x[1] === 203 && (int) $x[2] === 0 && (int) $x[3] === 113) { continue; }        // TEST-NET-3 (RFC 5737), khusus dokumentasi/tes
            if ((int) $x[1] === 198 && (int) $x[2] === 51 && (int) $x[3] === 100) { continue; }       // TEST-NET-2
            if ((int) $x[1] === 192 && (int) $x[2] === 0 && (int) $x[3] === 2) { continue; }          // TEST-NET-1
            $ip_temuan[] = "$r: " . $x[0];
        }
    }
    if (preg_match('#/home/u\d{6,}|\bu\d{9}(?:_\w+)?\b#', $s, $ma) && $r !== 'tests/repo_hygiene_test.php') { $akun_temuan[] = "$r: " . $ma[0]; }
}
check($ip_temuan === [], "Repo publik memuat alamat IP (infrastruktur/pihak nyata):\n  " . implode("\n  ", array_unique($ip_temuan)));
check($akun_temuan === [], "Repo publik memuat nama akun/jalur hosting:\n  " . implode("\n  ", array_unique($akun_temuan)));

// Kredensial bawaan tidak boleh tertulis di dokumen maupun di layar login.
$akun_doc = baca('docs/engineering/AKUN_LOGIN.md');
check($akun_doc !== '' && preg_match('/\|\s*`?password`?\s*\||@example\.com/i', $akun_doc) === 0, 'AKUN_LOGIN.md tidak boleh memuat kata sandi bawaan');
$LAYAR_LOGIN = ['application/views/pages/auth/login.php', 'application/views/components/login_modal.php', 'application/views/pages/pengembang/syarat.php'];
foreach ($LAYAR_LOGIN as $v) {
    $s = baca($v);
    check($s !== '', "$v harus ada");
    check(stripos($s, 'Kredensial Demo') === FALSE && strpos($s, "='password'") === FALSE && strpos($s, 'auth-demo') === FALSE && strpos($s, 'login-modal__demo') === FALSE,
        "$v tidak boleh memajang panel/isi-otomatis kredensial demo (kata sandi bawaan di layar publik)");
}
check(strpos(baca('assets/css/auth-pages.css'), 'auth-demo') === FALSE, 'CSS panel Kredensial Demo yatim harus tetap dihapus');

/* ============================================================ 4. Fitur sampel tidak aktif di production */
$sim = baca('application/config/simperum.php');
check(preg_match("/ENVIRONMENT\s*===\s*'production'\s*\)\s*\{\s*\\\$config\['simperum_mode'\]\s*=\s*'api'\s*;/", $sim) === 1, 'simperum.php harus memaksa mode `api` di production (data fiktif tidak boleh tersaji sebagai hasil resmi)');
$mig = baca('application/migrations/20260701000055_demo_sertifikat_kkn.php');
check(preg_match("/ENVIRONMENT\s*===\s*'production'/", $mig) === 1, 'Migrasi 055 (data demo sertifikat KKN) harus tidak berbuat apa-apa di production');
check(preg_match('/function up\(\)\s*\{\s*(?:\/\/[^\n]*\n\s*|\/\*.*?\*\/\s*)*if\s*\(\s*defined\(\'ENVIRONMENT\'\)\s*&&\s*ENVIRONMENT\s*===\s*\'production\'\s*\)\s*\{[^}]*\breturn\s*;/s', $mig) === 1, 'Guard production harus jadi pernyataan PERTAMA di up() migrasi 055');
$pembersih = baca('docs/engineering/bersihkan_data_sampel.php');
check(strpos($pembersih, 'KATA_SANDI_BAWAAN') !== FALSE && strpos($pembersih, "'--ya'") !== FALSE && strpos($pembersih, 'DEMO-SERTIFIKAT-KKN-%') !== FALSE, 'Alat pembersih data sampel harus tersedia (dry-run bawaan, --ya untuk menjalankan)');
check(preg_match('/getenv\(\'DB_PASS\'\)/', $pembersih) === 1 && strpos($pembersih, 'PHP_SAPI') !== FALSE, 'Pembersih data sampel hanya CLI dan membaca kredensial dari env');
$rel_env = baca('.env.example');
check(preg_match('/^SIMPERUM_MODE=/m', $rel_env) === 0 || preg_match('/^SIMPERUM_MODE=(simulation|api)\s*$/m', $rel_env) === 1, '.env.example: SIMPERUM_MODE harus bernilai sah');

echo "repo_hygiene_test: OK ($total pemeriksaan; " . count($berkas_teks) . " berkas teks dipindai, $n_view view, " . count($DIBUANG) . " berkas mati dijaga)\n";
