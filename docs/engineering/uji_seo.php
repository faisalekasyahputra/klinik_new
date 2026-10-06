<?php
/**
 * SEO portal publik (permintaan user 6 Okt 2026): helpers/seo_helper.php, config/seo.php, controllers/Seo.php.
 *
 *   php docs/engineering/uji_seo.php
 *
 * Dijaga:
 *   1. Setiap halaman terdaftar punya <title> dan description SENDIRI (tidak kembar), canonical berhuruf
 *      rute asli, og:image URL penuh; header situs bukan <h1> lagi; beranda memuat JSON-LD yang sah.
 *   2. Halaman pribadi dan pratinjau (dummy) diberi noindex tanpa canonical; alias /login menunjuk Auth/login.
 *   3. Halaman detail perumahan dan kawasan mengisi judul, deskripsi, dan gambar dari datanya; setiap program
 *      pemerintah punya halaman sendiri dengan kartu OG JPG dan JSON-LD GovernmentService.
 *   4. robots.txt dan sitemap.xml: sah, beralamat penuh, tanpa halaman noindex.
 *   5. Respons partial loader portal membawa X-Judul-Halaman.
 *
 * Hanya membaca (GET); tidak menulis DB. Halaman detail perumahan memakai id yang sudah ada di cache SIKUMBANG.
 */
define('APP_ROOT', dirname(__DIR__, 2));
define('BASE', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/');

$GLOBALS['total'] = 0; $GLOBALS['gagal'] = 0;
function cek($kondisi, $label) {
    $GLOBALS['total']++;
    echo ($kondisi ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $kondisi) { $GLOBALS['gagal']++; }
    return (bool) $kondisi;
}
function ambil($path, array $header = []) {
    $c = curl_init(BASE . $path);
    $kepala = [];
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_TIMEOUT => 90, CURLOPT_HTTPHEADER => $header,
        CURLOPT_HEADERFUNCTION => function ($c, $h) use (&$kepala) {
            if (strpos($h, ':') !== FALSE) { [$k, $v] = explode(':', $h, 2); $kepala[strtolower(trim($k))] = trim($v); }
            return strlen($h);
        }]);
    $badan = (string) curl_exec($c);
    $r = ['kode' => curl_getinfo($c, CURLINFO_HTTP_CODE), 'kepala' => $kepala, 'badan' => $badan];
    curl_close($c);
    return $r;
}
function meta($badan) {
    $m = ['title' => preg_match('#<title>(.*?)</title>#s', $badan, $x) ? html_entity_decode($x[1], ENT_QUOTES, 'UTF-8') : NULL];
    foreach (['description' => 'name="description"', 'robots' => 'name="robots"', 'og:image' => 'property="og:image"', 'og:title' => 'property="og:title"'] as $k => $attr) {
        $m[$k] = preg_match('#<meta ' . preg_quote($attr, '#') . ' content="([^"]*)"#', $badan, $x) ? html_entity_decode($x[1], ENT_QUOTES, 'UTF-8') : NULL;
    }
    $m['canonical'] = preg_match('#<link rel="canonical" href="([^"]*)"#', $badan, $x) ? $x[1] : NULL;
    $m['ld'] = preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $badan, $x) ? array_map(fn($j) => json_decode($j, TRUE), $x[1]) : [];
    return $m;
}

$cfg = (function () { define('BASEPATH', 1); $config = []; include APP_ROOT . '/application/config/seo.php'; return $config['seo']; })();

echo "== 1. Halaman terdaftar\n";
$judul = $deskripsi = []; $salah = [];
foreach ($cfg['halaman'] as $kunci => $isi) {
    $r = ambil($kunci);
    $m = meta($r['badan']);
    if ($r['kode'] !== 200 || ! $m['title'] || ! $m['description'] || $m['canonical'] !== BASE . $kunci
        || strpos((string) $m['og:image'], 'http') !== 0 || strpos((string) $m['robots'], 'noindex') !== FALSE) {
        $salah[] = "/$kunci (kode {$r['kode']}, canonical {$m['canonical']})";
    }
    $judul[] = $m['title']; $deskripsi[] = $m['description'];
}
cek( ! $salah, 'Setiap halaman terdaftar 200 dengan title, description, canonical berhuruf rute, og:image penuh, dapat diindeks' . ($salah ? ': ' . implode('; ', $salah) : ''));
cek(count(array_unique($judul)) === count($judul), 'Tidak ada <title> kembar (' . count($judul) . ' halaman)');
cek(count(array_unique($deskripsi)) === count($deskripsi), 'Tidak ada description kembar');
cek( ! array_filter($judul, fn($t) => mb_strlen((string) $t) > 70), 'Panjang <title> paling banyak 70 karakter');
$beranda = ambil('');
$mb = meta($beranda['badan']);
cek(strpos($beranda['badan'], 'Kawasan Permukiman</h1>') === FALSE, 'Header situs bukan <h1> (h1 milik judul konten)');
$tipe = array_column(array_filter($mb['ld'], 'is_array'), '@type');
cek(in_array('GovernmentOrganization', $tipe, TRUE) && in_array('WebSite', $tipe, TRUE), 'Beranda memuat JSON-LD GovernmentOrganization dan WebSite yang sah');

echo "\n== 2. noindex dan alias\n";
foreach (['info_tanah', 'materia', 'Auth/forgot_password'] as $u) {
    $m = meta(ambil($u)['badan']);
    cek(strpos((string) $m['robots'], 'noindex') !== FALSE && $m['canonical'] === NULL, "/$u diberi noindex tanpa canonical");
}
cek(meta(ambil('login')['badan'])['canonical'] === BASE . 'Auth/login', 'Alias /login menunjuk canonical Auth/login');

echo "\n== 3. Halaman detail dari data\n";
$id = NULL;
foreach (glob(APP_ROOT . '/application/cache/sikumbang_detail_*.json') as $f) {
    $d = json_decode((string) file_get_contents($f), TRUE);
    if ( ! empty($d['detail']['namaPerumahan']) && ! empty($d['detail']['foto'][0])) { $id = $d['detail']['idLokasi'] ?? NULL; $nama = $d['detail']['namaPerumahan']; break; }
}
if ($id) {
    $m = meta(ambil('detail_perum/' . $id)['badan']);
    $place = array_values(array_filter($m['ld'], fn($b) => ($b['@type'] ?? '') === 'Place'))[0] ?? [];
    cek(stripos((string) $m['title'], ucwords(strtolower($nama))) === 0 && stripos((string) $m['description'], 'tipe') !== FALSE,
        'Detail perumahan: judul dari nama perumahan, deskripsi menyebut tipe rumah');
    cek(strpos((string) $m['og:image'], 'http') === 0 && $m['og:image'] !== BASE . $cfg['gambar'], 'Detail perumahan: og:image foto perumahan, bukan gambar bawaan');
    cek(($place['name'] ?? '') !== '' && isset($place['address']['addressRegion']), 'Detail perumahan: JSON-LD Place beralamat');
} else {
    echo "  (lewati detail perumahan: belum ada cache SIKUMBANG berfoto)\n";
}
$peta = ambil('sitemap.xml');
$xml = @simplexml_load_string($peta['badan']);
$loc = $xml ? array_map(fn($u) => (string) $u->loc, iterator_to_array($xml->url, FALSE)) : [];
$kawasan = array_values(array_filter($loc, fn($u) => strpos($u, 'kawasan_kumuh/detail/') !== FALSE));
if ($kawasan) {
    $m = meta(ambil(substr($kawasan[0], strlen(BASE)))['badan']);
    cek(strpos((string) $m['title'], 'Kawasan Kumuh ') === 0 && $m['title'] !== 'Kawasan Kumuh Jawa Tengah | ' . $cfg['nama_situs'],
        'Detail kawasan kumuh: judul memuat nama kawasan');
}

echo "\n== 3b. Program Pemerintah\n";
$daftar = ambil('program-pemerintah');
preg_match_all('#program-pemerintah/([a-z0-9-]+)"#', $daftar['badan'], $x);
$slug = array_values(array_unique($x[1]));
cek($daftar['kode'] === 200 && count($slug) >= 1 && strpos($daftar['badan'], 'data-program-daftar') !== FALSE, 'Halaman daftar program menaut ke ' . count($slug) . ' program');
$salah = [];
foreach ($slug as $sl) {
    $m = meta(ambil('program-pemerintah/' . $sl)['badan']);
    $jasa = array_filter($m['ld'], fn($b) => ($b['@type'] ?? '') === 'GovernmentService');
    if (strpos((string) $m['title'], 'Syarat ') !== 0 || strpos((string) $m['og:image'], BASE . 'assets/img/og/program-pemerintah-' . $sl . '.jpg?v=') !== 0 || ! $jasa
        || mb_strlen((string) $m['title']) > 70) { $salah[] = $sl; }
}
cek($slug && ! $salah, 'Setiap program: judul "Syarat ...", kartu OG JPG sendiri, JSON-LD GovernmentService' . ($salah ? ': ' . implode(', ', $salah) : ''));
cek(ambil('program-pemerintah/tidak-ada')['kode'] === 404 && ambil('program-pemerintah/Oemah_Lestari')['kode'] === 404, 'Slug program tak dikenal atau berbentuk lain dijawab 404');

echo "\n== 4. robots.txt dan sitemap.xml\n";
$robots = ambil('robots.txt');
cek($robots['kode'] === 200 && strpos($robots['kepala']['content-type'] ?? '', 'text/plain') === 0, 'robots.txt 200 text/plain');
cek(preg_match('#^Sitemap: ' . preg_quote(BASE, '#') . 'sitemap\.xml$#m', $robots['badan'])
    && strpos($robots['badan'], 'Disallow: ' . parse_url(BASE, PHP_URL_PATH) . 'Admin') !== FALSE
    && strpos($robots['badan'], 'Auth/forgot_password') !== FALSE && strpos($robots['badan'], 'auth/forgot_password') !== FALSE,
    'robots.txt menyebut sitemap beralamat penuh, menutup Admin, dan menulis jalur dengan dua bentuk huruf');
cek($peta['kode'] === 200 && $xml !== FALSE && strpos($peta['kepala']['content-type'] ?? '', 'xml') !== FALSE, 'sitemap.xml 200 dan XML sah');
cek($loc && ! array_filter($loc, fn($u) => strpos($u, BASE) !== 0), 'Semua URL sitemap beralamat penuh di situs ini (' . count($loc) . ' URL)');
cek(in_array(BASE . 'golek_omah', $loc, TRUE) && in_array(BASE . 'Cek_Rtlh', $loc, TRUE), 'Sitemap memuat halaman terdaftar dengan huruf rute asli');
cek( ! array_diff(array_map(fn($sl) => BASE . 'program-pemerintah/' . $sl, $slug), $loc), 'Sitemap memuat setiap halaman program');
cek( ! array_intersect($loc, [BASE . 'info_tanah', BASE . 'materia', BASE . 'akun', BASE . 'Auth/login', BASE . 'sebaran']),
    'Sitemap tanpa halaman noindex dan tanpa yang ditandai peta => FALSE');

echo "\n== 4b. Tautan yang dibagikan\n";
$salah = [];
foreach (['fbclid=IwAR0uji', 'utm_source=whatsapp&utm_medium=social&utm_campaign=uji', 'gclid=uji', 'igshid=uji', 'v=3'] as $q) {
    $r = ambil('program-pemerintah?' . $q);
    if ($r['kode'] !== 200 || meta($r['badan'])['canonical'] !== BASE . 'program-pemerintah') { $salah[] = $q; }
}
cek( ! $salah, 'Parameter pelacak platform (fbclid, utm_*, gclid, igshid, v) diterima, canonical tanpa query' . ($salah ? ': ' . implode(', ', $salah) : ''));
cek(ambil('program-pemerintah?parameter_asing=1')['kode'] === 400, 'Parameter asing tetap ditolak penyaring input (400)');

echo "\n== 5. Loader portal\n";
$r = ambil('golek_omah', ['X-Requested-With: XMLHttpRequest']);
cek(rawurldecode($r['kepala']['x-judul-halaman'] ?? '') === meta(ambil('golek_omah')['badan'])['title'], 'Respons partial membawa X-Judul-Halaman = <title> halaman penuh');

echo "RINGKASAN: {$GLOBALS['total']} pemeriksaan, {$GLOBALS['gagal']} gagal\n";
exit($GLOBALS['gagal'] ? 1 : 0);
