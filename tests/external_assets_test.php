<?php
/**
 * Penjaga integritas aset yang diakses dari luar (form keamanan poin 13.4).
 * Offline: tanpa basis data dan tanpa jaringan. Jalankan:  php tests/external_assets_test.php
 *
 * Pembuktian bahwa hash SRI di view BENAR terhadap isi CDN yang disajikan sekarang butuh internet dan ada di
 * docs/engineering/verifikasi_aset_eksternal.php (tanpa argumen). Tes ini menjaga sisanya secara offline: setiap
 * aset eksternal ber-SRI + crossorigin, sama dengan manifest; tidak ada aset eksternal tanpa SRI selain yang terdaftar
 * beserta alasannya; font dihosting sendiri dan berkasnya identik dengan hash di manifest.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$akar = realpath(__DIR__ . '/..');
$total = 0;
function check($kondisi, $pesan) { global $total; $total++; if ( ! $kondisi) { throw new RuntimeException($pesan); } }
function rel($p) { global $akar; return ltrim(str_replace('\\', '/', substr($p, strlen($akar))), '/'); }

require $akar . '/docs/engineering/verifikasi_aset_eksternal.php';   // hanya mendefinisikan baca_aset_eksternal_dari_view()
$manifest = json_decode((string) file_get_contents($akar . '/docs/engineering/aset_eksternal_manifest.json'), TRUE);
check(is_array($manifest) && isset($manifest['eksternal'], $manifest['lokal_pihak_ketiga'], $manifest['tanpa_sri_diakui']), 'Manifest aset eksternal harus terbaca dan lengkap');

/* ============================================================ 1. Aset eksternal di view */
$aset = baca_aset_eksternal_dari_view($akar);
check(count($aset) >= 10, 'Pemeriksa aset eksternal menjangkau terlalu sedikit (' . count($aset) . ')');
$config = []; if ( ! defined('BASEPATH')) { define('BASEPATH', $akar . '/system/'); }
require $akar . '/application/config/content_security.php';
$sri_pengecualian = $config['sri_pengecualian'] ?? [];
$hosts_csp = $config['csp_script_hosts'] ?? [];

$tanpa_sri = []; $ber_sri = [];
foreach ($aset as $url => $a) {
    check(strpos($url, 'https://') === 0, "Aset eksternal harus lewat HTTPS: $url (" . implode(', ', $a['dipakai_di']) . ')');
    check(strpos($a['integrity'], 'KONFLIK:') !== 0, "Hash SRI berbeda antar berkas untuk aset yang sama: $url (" . $a['integrity'] . ')');
    if ($a['integrity'] === '') { $tanpa_sri[$url] = $a['dipakai_di']; } else { $ber_sri[$url] = $a; }
}
foreach ($ber_sri as $url => $a) {
    check(preg_match('/^sha(256|384|512)-[A-Za-z0-9+\/]{43,}={0,2}$/', $a['integrity']) === 1, "Hash SRI $url harus sha256/sha384/sha512 berformat base64 sah (sha1/md5 tidak diterima)");
    check(isset($manifest['eksternal'][$url]) && $manifest['eksternal'][$url]['integrity'] === $a['integrity'],
        "Hash SRI $url di view berbeda dari (atau tidak ada di) manifest; jalankan docs/engineering/verifikasi_aset_eksternal.php untuk membuktikan hash yang benar, lalu --tulis");
}
foreach (array_keys($manifest['eksternal']) as $url) { check(isset($ber_sri[$url]), "Manifest memuat $url yang tidak lagi dipakai di view (manifest usang)"); }

// crossorigin harus menyertai integrity pada aset lintas asal, kalau tidak peramban menolak aset dari CDN (halaman rusak diam-diam).
$tanpa_cors = [];
foreach ((function () use ($akar) { $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($akar . '/application/views', FilesystemIterator::SKIP_DOTS)); foreach ($it as $f) { if ($f->getExtension() === 'php') { yield $f->getPathname(); } } })() as $p) {
    if (preg_match_all('#<(?:script|link)\b[^>]*\bintegrity\s*=[^>]*>#i', (string) file_get_contents($p), $ms)) {
        foreach ($ms[0] as $tag) { if ( ! preg_match('#\bcrossorigin\b#i', $tag)) { $tanpa_cors[] = rel($p) . ': ' . substr($tag, 0, 90); } }
    }
}
check($tanpa_cors === [], "Aset ber-integrity tanpa atribut crossorigin (ditolak peramban):\n  " . implode("\n  ", $tanpa_cors));

// Aset eksternal tanpa SRI: hanya yang terdaftar (sri_pengecualian) DAN diakui di manifest, dan hostnya di daftar CSP.
foreach ($tanpa_sri as $url => $dipakai) {
    check(isset($sri_pengecualian[$url]), "Aset eksternal tanpa SRI dan tanpa alasan tertulis di sri_pengecualian: $url (" . implode(', ', $dipakai) . ')');
    check(isset($manifest['tanpa_sri_diakui'][$url]), "Aset tanpa SRI $url belum diakui di manifest");
    $host = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST);
    check(in_array($host, $hosts_csp, TRUE), "Host $host tidak ada di csp_script_hosts");
}
foreach (array_keys($sri_pengecualian) as $url) {
    check(strpos($url, 'fonts.googleapis.com') === FALSE && strpos($url, 'fonts.gstatic.com') === FALSE, "Pengecualian SRI untuk Google Fonts ($url) tidak boleh kembali: font dihosting sendiri");
    check(isset($tanpa_sri[$url]), "Pengecualian SRI $url tidak lagi dipakai di view (hapus dari config)");
}
check(count($sri_pengecualian) === 1 && isset($sri_pengecualian['https://www.google.com/recaptcha/api.js']), 'Satu-satunya pengecualian SRI yang sah adalah reCAPTCHA');

// reCAPTCHA hanya dimuat bila situs-key terisi (tanpa key: nol permintaan ke Google dari halaman masuk/daftar).
foreach (['application/views/pages/auth/login.php', 'application/views/pages/auth/register.php', 'application/views/pages/pengembang/masuk.php', 'application/views/pages/pengembang/syarat.php', 'application/views/components/login_modal.php'] as $v) {
    $s = (string) file_get_contents($akar . '/' . $v);
    check(preg_match_all('#recaptcha/api\.js#', $s) >= 1, "$v harus memuat reCAPTCHA (atau daftar pemakai di test usang)");
    foreach (preg_split('/\R/', $s) as $i => $baris) {
        if (strpos($baris, 'recaptcha/api.js') === FALSE) { continue; }
        $konteks = implode("\n", array_slice(preg_split('/\R/', $s), max(0, $i - 12), 13));
        check(preg_match('/if\s*\(\s*!\s*empty\(\$recaptcha_site_key\)|if\s*\(\s*\$modal_recaptcha_site_key\s*!==\s*\'\'/', $konteks) === 1, "$v baris " . ($i + 1) . ': skrip reCAPTCHA harus dimuat bersyarat (hanya bila site key terisi)');
    }
}

/* ============================================================ 2. Font dihosting sendiri */
$css_semua = [];
foreach (array_merge(glob($akar . '/assets/css/*.css') ?: []) as $p) { $css_semua[rel($p)] = preg_replace('#/\*.*?\*/#s', '', (string) file_get_contents($p)); }
foreach ($css_semua as $r => $isi) {
    check(preg_match('#fonts\.(googleapis|gstatic)\.com#i', $isi) === 0, "$r masih menunjuk ke Google Fonts (font harus lokal)");
    check(preg_match('#@import\s+(?:url\()?[\'"]?https?://#i', $isi) === 0, "$r memuat CSS dari luar lewat @import");
    check(preg_match('#url\(\s*[\'"]?https?://#i', $isi) === 0, "$r memuat sumber daya dari luar lewat url()");
}
$view_luar = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($akar . '/application/views', FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if ($f->getExtension() !== 'php') { continue; }
    $s = (string) file_get_contents($f->getPathname());
    if (preg_match('#fonts\.(googleapis|gstatic)\.com#i', $s)) { $view_luar[] = rel($f->getPathname()); }
}
check($view_luar === [], "View masih memuat/preconnect ke Google Fonts:\n  " . implode("\n  ", $view_luar));
foreach (['application/views/layouts/head.php', 'application/views/admin/layouts/head.php', 'application/views/pages/kemitraan_portal/cetak_sertifikat_kkn.php'] as $v) {
    check(strpos((string) file_get_contents($akar . '/' . $v), 'assets/css/fonts.css') !== FALSE, "$v harus memuat assets/css/fonts.css (font lokal)");
}
check(strpos((string) file_get_contents($akar . '/assets/css/auth-pages.css'), "@import url('fonts.css')") !== FALSE, 'auth-pages.css harus memuat fonts.css (halaman masuk/daftar berdiri sendiri, tanpa layout utama)');

$fonts_css = $css_semua['assets/css/fonts.css'] ?? '';
check($fonts_css !== '', 'assets/css/fonts.css harus ada');
preg_match_all('#url\(\s*[\'"]?([^)\'"]+)#', $fonts_css, $mu);
check(count($mu[1]) >= 6, 'fonts.css harus mereferensikan berkas font lokal');
$dirujuk = [];
foreach ($mu[1] as $u) {
    check(strpos($u, '../fonts/') === 0, "fonts.css: $u harus berupa berkas lokal di assets/fonts");
    $r = 'assets/fonts/' . substr($u, strlen('../fonts/'));
    check(is_file($akar . '/' . $r), "fonts.css merujuk berkas yang tidak ada: $r");
    $dirujuk[$r] = TRUE;
}

/* ============================================================ 3. Berkas lokal pihak ketiga identik dengan manifest */
$lokal = $manifest['lokal_pihak_ketiga'];
foreach ($lokal as $r => $a) {
    check(is_file($akar . '/' . $r), "Berkas lokal pihak ketiga tidak ada: $r");
    check(strpos($a['sumber'], 'https://') === 0 && preg_match('/^[0-9a-f]{64}$/', $a['sha256']) === 1, "Manifest $r harus memuat sumber HTTPS dan sha256");
    check(hash_equals($a['sha256'], hash_file('sha256', $akar . '/' . $r)), "Isi $r berbeda dari hash di manifest (berkas diubah/rusak; unduh ulang dari sumbernya dan perbarui manifest lewat tinjauan)");
    check(strpos((string) file_get_contents($akar . '/' . $r), 'wOF2') === 0, "$r bukan berkas woff2 yang sah (tanda tangan berkas salah)");
}
$semua_font = array_map('rel', glob($akar . '/assets/fonts/*') ?: []);
foreach ($semua_font as $r) { check(isset($lokal[$r]), "Berkas font $r tidak tercatat (sumber + hash) di manifest"); check(isset($dirujuk[$r]), "Berkas font $r tidak dipakai fonts.css (mati)"); }
foreach (array_keys($lokal) as $r) { check(in_array($r, $semua_font, TRUE), "Manifest memuat $r yang tidak ada di assets/fonts"); }

/* ============================================================ 4. Pengunci: verifikasi online tetap tersedia */
$ver = (string) file_get_contents($akar . '/docs/engineering/verifikasi_aset_eksternal.php');
check(strpos($ver, 'hash_equals') !== FALSE && strpos($ver, '--tulis') !== FALSE, 'Skrip verifikasi online (hitung ulang hash dari CDN) harus tetap tersedia');

echo "external_assets_test: OK ($total pemeriksaan; " . count($ber_sri) . ' aset eksternal ber-SRI, ' . count($tanpa_sri) . ' tanpa SRI (diakui), ' . count($lokal) . " berkas font lokal)\n";
