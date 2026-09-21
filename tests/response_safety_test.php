<?php
/**
 * Penjaga "respons aplikasi dan konten yang aman" (form keamanan poin 13.5).
 * Offline: tanpa basis data dan tanpa jaringan. Jalankan:  php tests/response_safety_test.php
 *
 * Dijaga: (1) satu sumber header keamanan (kirim_header_keamanan) dipakai controller DAN halaman galat/404 buatan
 * CodeIgniter, sehingga tidak ada respons PHP tanpa header; (2) berkas statis (yang tidak lewat PHP) diberi jenis konten
 * yang benar, nosniff, dan header keamanan dari .htaccess, tanpa menggandakan header PHP; (3) semua respons JSON
 * berjenis application/json; (4) berkas privat disajikan dengan jenis dari ekstensi daftar-izin, nama netral, tanpa cache.
 * Bukti perilaku sebenarnya (header pada respons HTTP nyata) ada di docs/engineering/RESPONS_APLIKASI_AMAN.md.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$akar = realpath(__DIR__ . '/..');
$total = 0;
function check($kondisi, $pesan) { global $total; $total++; if ( ! $kondisi) { throw new RuntimeException($pesan); } }
function rel($p) { global $akar; return ltrim(str_replace('\\', '/', substr($p, strlen($akar))), '/'); }
function baca($r) { global $akar; $p = $akar . '/' . $r; return is_file($p) ? (string) file_get_contents($p) : ''; }

/* ============================================================ 1. Satu sumber header keamanan */
$helper = baca('application/helpers/content_security_helper.php');
preg_match('/function kirim_header_keamanan\(\)\s*\{(.*?)\n    \}\n/s', $helper, $mh);
$badan = $mh[1] ?? '';
check($badan !== '', 'kirim_header_keamanan() harus ada di content_security_helper.php');
$WAJIB = [
    'X-Frame-Options: DENY', 'X-Content-Type-Options: nosniff', 'Referrer-Policy: strict-origin-when-cross-origin',
    'Strict-Transport-Security: max-age=31536000', 'Permissions-Policy: ', 'Content-Security-Policy: ',
    'X-Permitted-Cross-Domain-Policies: none', 'Cross-Origin-Opener-Policy: same-origin-allow-popups', 'Cross-Origin-Resource-Policy: same-origin',
];
foreach ($WAJIB as $h) { check(strpos($badan, $h) !== FALSE, "kirim_header_keamanan() harus mengirim: $h"); }
check(strpos($badan, "header_remove('X-Powered-By')") !== FALSE, 'kirim_header_keamanan() harus membuang X-Powered-By');
// COOP `same-origin` murni memutus popup login Google (jendela pembuka tak dapat ditutup/diarahkan); tetap allow-popups.
check(strpos($badan, 'Cross-Origin-Opener-Policy: same-origin\'') === FALSE && strpos($badan, 'Cross-Origin-Opener-Policy: same-origin"') === FALSE, 'COOP harus same-origin-allow-popups, bukan same-origin (memutus popup masuk dengan Google)');
check(strpos($badan, 'if ($https) { header(\'Strict-Transport-Security') !== FALSE, 'HSTS hanya dikirim pada HTTPS');

$mc = baca('application/core/MY_Controller.php');
check(preg_match('/function set_security_headers\(\)\s*\{[^}]*kirim_header_keamanan\(\)/s', $mc) === 1, 'MY_Controller::set_security_headers() harus memakai kirim_header_keamanan()');
$menyimpang = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($akar . '/application', FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if ($f->getExtension() !== 'php') { continue; }
    $r = rel($f->getPathname());
    if ($r === 'application/helpers/content_security_helper.php' || strpos($r, 'application/cache/') === 0) { continue; }
    if (preg_match_all('/header\(\s*[\'"](X-Frame-Options|X-Content-Type-Options|Strict-Transport-Security|Referrer-Policy|Cross-Origin-[A-Za-z-]+)\s*:/i', (string) file_get_contents($f->getPathname()), $m)) {
        foreach ($m[0] as $h) { $menyimpang[] = "$r: $h"; }
    }
}
// Satu pengecualian yang ditinjau: unduhan Pengaturan menambah nosniff untuk berkas yang dibangunnya sendiri (nilai sama).
$menyimpang = array_values(array_filter($menyimpang, function ($s) { return strpos($s, 'application/controllers/Pengaturan.php: header(\'X-Content-Type-Options') !== 0; }));
check($menyimpang === [], "Header keamanan dipasang di luar kirim_header_keamanan() (sumber ganda bisa saling bertentangan):\n  " . implode("\n  ", $menyimpang));

$ex = baca('application/core/MY_Exceptions.php');
check(strpos($ex, 'extends CI_Exceptions') !== FALSE, 'MY_Exceptions harus memperluas CI_Exceptions');
foreach (['show_404', 'show_error', 'show_exception'] as $fn) {
    check(preg_match('/function ' . $fn . '\([^)]*\)\s*\{\s*\$this->pasang_header\(\);\s*return parent::' . $fn . '\(/s', $ex) === 1, "MY_Exceptions::$fn harus memasang header keamanan SEBELUM memanggil parent (404 dari router tidak melewati controller)");
}
check(strpos($ex, 'kirim_header_keamanan()') !== FALSE, 'MY_Exceptions harus memakai kirim_header_keamanan()');
check(preg_match('/subclass_prefix\'\]\s*=\s*\'MY_\'/', baca('application/config/config.php')) === 1, "subclass_prefix harus MY_ supaya MY_Exceptions dimuat");

/* ============================================================ 2. Berkas statis (tidak melewati PHP) */
$ht = baca('.htaccess');
foreach (['AddType text/javascript .js .mjs', 'AddType text/css .css', 'AddType application/manifest+json .webmanifest', 'AddType font/woff2 .woff2', 'AddCharset UTF-8 .js .mjs .css .webmanifest'] as $d) {
    check(strpos($ht, $d) !== FALSE, ".htaccess harus memuat: $d (jenis konten dan charset yang benar untuk aset statis)");
}
check(preg_match('/<FilesMatch "[^"]*\(css\|js[^"]*woff2[^"]*\)\$">\s*Header always set X-Content-Type-Options "nosniff"\s*Header always set X-Frame-Options "DENY"\s*Header always set Referrer-Policy "strict-origin-when-cross-origin"\s*Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"\s*<\/FilesMatch>/s', $ht) === 1,
    '.htaccess harus memasang nosniff, X-Frame-Options, Referrer-Policy, dan HSTS pada berkas statis lewat FilesMatch');
// `Header always set` di luar FilesMatch akan MENGGANDAKAN header respons PHP (mis. "DENY, DENY", nilai tak sah dan diabaikan peramban).
$tanpa_filesmatch = preg_replace('#<FilesMatch[^>]*>.*?</FilesMatch>#s', '', $ht);
check(preg_match('/Header\s+always\s+set\s+(X-Frame-Options|X-Content-Type-Options|Referrer-Policy|Strict-Transport-Security|Content-Security-Policy)/i', $tanpa_filesmatch) === 0,
    '.htaccess memasang header keamanan di luar FilesMatch statis: respons PHP akan menerima header ganda');
check(preg_match('/Header\s+always\s+unset\s+X-Powered-By/', $ht) === 1, '.htaccess harus membuang X-Powered-By');
check(strpos(baca('.user.ini'), 'expose_php=Off') !== FALSE, '.user.ini harus mematikan expose_php');
$manifest_pwa = baca('manifest.webmanifest');
check(json_decode($manifest_pwa, TRUE) !== NULL, 'manifest.webmanifest harus JSON sah');

/* ============================================================ 3. Respons JSON berjenis application/json */
$salah = []; $n_json = 0;
foreach (['application/controllers', 'application/core', 'application/libraries'] as $d) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($akar . '/' . $d, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->getExtension() !== 'php' || $f->getFilename() === 'Migrate.php') { continue; }   // Migrate: CLI/alat internal, bukan respons peramban
        $baris = preg_split('/\R/', (string) file_get_contents($f->getPathname()));
        foreach ($baris as $i => $b) {
            if (preg_match('#^\s*//#', $b) || ! preg_match('/\becho\s+json_encode\(|\bexit\(\s*json_encode\(|\bdie\(\s*json_encode\(/', $b)) { continue; }
            $n_json++;
            $konteks = implode("\n", array_slice($baris, max(0, $i - 3), 4));
            if ( ! preg_match('/Content-Type:\s*application\/json/i', $konteks)) { $salah[] = rel($f->getPathname()) . ':' . ($i + 1); }
        }
    }
}
check($n_json >= 10, "Pemeriksa respons JSON menjangkau terlalu sedikit ($n_json)");
check($salah === [], "echo json_encode tanpa header Content-Type: application/json (dikirim sebagai text/html; pemicu XSS bila isi berpantulan):\n  " . implode("\n  ", $salah));

/* ============================================================ 4. Berkas privat */
preg_match('/function serve_private_file\(.*?\n    \}\n/s', $mc, $ms);
$sp = $ms[0] ?? '';
check($sp !== '', 'MY_Controller::serve_private_file harus ada');
check(strpos($sp, "'pdf' => 'application/pdf'") !== FALSE && strpos($sp, 'PATHINFO_EXTENSION') !== FALSE, 'serve_private_file: jenis konten harus dari ekstensi daftar-izin');
check(strpos($sp, "'application/octet-stream'") !== FALSE && strpos($sp, "'attachment'") !== FALSE, 'serve_private_file: ekstensi tak dikenal harus diunduh sebagai biner (attachment)');
check(preg_match('/Content-Type:\s*\'\s*\.\s*\(\$inline/', $sp) === 1 && preg_match('/Content-Type:\s*\'\s*\.\s*\$mime/', $sp) === 0, 'serve_private_file: Content-Type tidak boleh berasal dari $mime kiriman pemanggil');
check(strpos($sp, 'no-store') !== FALSE && strpos($sp, 'private') !== FALSE, 'serve_private_file: Cache-Control harus private, no-store');
check(preg_match('/filename="berkas/', $sp) === 1, 'serve_private_file: nama unduhan harus netral (bukan nama tersimpan/asli)');
check(strpos($sp, 'Content-Length') !== FALSE, 'serve_private_file: Content-Length harus dikirim');
check(strpos($sp, "sandbox") !== FALSE && strpos($sp, "default-src 'none'") !== FALSE, 'serve_private_file: gambar harus dilindungi CSP sandbox');
check(strpos($sp, 'basename((string) $stored_name)') !== FALSE || strpos($mc, 'basename((string) $stored_name)') !== FALSE, 'serve_private_file: nama berkas harus di-basename (anti path traversal)');

echo "response_safety_test: OK ($total pemeriksaan; $n_json respons JSON, " . count($WAJIB) . " header wajib)\n";
