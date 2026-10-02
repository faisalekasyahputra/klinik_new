<?php
/**
 * Verifikasi integritas aset yang dimuat dari luar (form keamanan poin 13.4).
 *
 *   php docs/engineering/verifikasi_aset_eksternal.php                 periksa (butuh internet): unduh SETIAP aset
 *                                                                       eksternal di view, hitung SRI, bandingkan dengan
 *                                                                       yang dideklarasikan view DAN manifest; unduh ulang
 *                                                                       font lokal dan bandingkan dengan salinan di repo
 *   php docs/engineering/verifikasi_aset_eksternal.php --tulis          tulis ulang aset_eksternal_manifest.json dari view
 *                                                                       (bagian `eksternal` dari atribut integrity di view;
 *                                                                       bagian `lokal_pihak_ketiga` dipertahankan)
 *
 * Kenapa perlu: integrity="sha384-..." di view hanya melindungi bila hash-nya BENAR dan cocok dengan berkas yang
 * memang dimaksud. Hash salah membuat halaman rusak diam-diam (skrip diblokir peramban); hash yang dihitung dari
 * berkas yang sudah dibobol membuat perlindungannya semu. Skrip ini membuktikan keduanya, dan manifest membuat
 * perubahan hash menjadi keputusan yang terlihat di diff. tests/external_assets_test.php (offline) menggagalkan
 * bila view dan manifest berbeda.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$akar = realpath(__DIR__ . '/../..');
$manifest_path = __DIR__ . '/aset_eksternal_manifest.json';

/** @return array<string,array{integrity:string,dipakai_di:string[]}> */
function baca_aset_eksternal_dari_view($akar)
{
    $hasil = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($akar . '/application/views', FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if ($f->getExtension() !== 'php' || strpos(str_replace('\\', '/', $f->getPathname()), '/archive/') !== FALSE) { continue; }
        $rel = ltrim(str_replace('\\', '/', substr($f->getPathname(), strlen($akar))), '/');
        if ( ! preg_match_all('#<(script|link)\b([^>]*)>#i', file_get_contents($f->getPathname()), $tags, PREG_SET_ORDER)) { continue; }
        foreach ($tags as $t) {
            $attr = $t[2];
            if ( ! preg_match('#\b(?:src|href)\s*=\s*"(https?://[^"]+)"#i', $attr, $u)) { continue; }
            $url = $u[1];
            $integrity = preg_match('#\bintegrity\s*=\s*"(sha(?:256|384|512)-[^"]+)"#i', $attr, $i) ? $i[1] : '';
            if ( ! isset($hasil[$url])) { $hasil[$url] = ['integrity' => $integrity, 'dipakai_di' => []]; }
            if ($integrity !== '' && $hasil[$url]['integrity'] !== $integrity) { $hasil[$url]['integrity'] = 'KONFLIK:' . $hasil[$url]['integrity'] . '|' . $integrity; }
            if ( ! in_array($rel, $hasil[$url]['dipakai_di'], TRUE)) { $hasil[$url]['dipakai_di'][] = $rel; }
        }
    }
    ksort($hasil);
    return $hasil;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') !== realpath(__FILE__)) { return; }   // dipakai sebagai pustaka oleh tes

function unduh($url)
{
    $ctx = stream_context_create(['http' => ['timeout' => 30, 'header' => "User-Agent: klinik-verifikasi-aset\r\n", 'follow_location' => 1], 'ssl' => ['verify_peer' => TRUE, 'verify_peer_name' => TRUE]]);
    $d = @file_get_contents($url, FALSE, $ctx);
    return $d === FALSE ? NULL : $d;
}

$aset = baca_aset_eksternal_dari_view($akar);
$manifest = is_file($manifest_path) ? (json_decode((string) file_get_contents($manifest_path), TRUE) ?: []) : [];

if (in_array('--tulis', $argv, TRUE)) {
    $eks = [];
    foreach ($aset as $url => $a) { if ($a['integrity'] !== '') { $eks[$url] = $a; } }
    $manifest['eksternal'] = $eks;
    $manifest['tanpa_sri_diakui'] = array_map(function ($a) { return $a['dipakai_di']; }, array_filter($aset, function ($a) { return $a['integrity'] === ''; }));
    file_put_contents($manifest_path, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n");
    echo 'Manifest ditulis: ' . count($eks) . " aset ber-SRI, " . count($manifest['tanpa_sri_diakui']) . " tanpa SRI (diakui)\n";
    exit(0);
}

$gagal = 0; $ok = 0;
echo "== Aset eksternal ber-SRI ==\n";
foreach (($manifest['eksternal'] ?? []) as $url => $a) {
    $deklarasi = $aset[$url]['integrity'] ?? '';
    if ($deklarasi !== $a['integrity']) { echo "  [GAGAL] $url: hash di view berbeda dari manifest\n"; $gagal++; continue; }
    $data = unduh($url);
    if ($data === NULL) { echo "  [GAGAL] $url: tidak dapat diunduh\n"; $gagal++; continue; }
    [$algo, $hash] = explode('-', $a['integrity'], 2);
    $hitung = base64_encode(hash($algo, $data, TRUE));
    if ( ! hash_equals($hash, $hitung)) { echo "  [GAGAL] $url: isi yang disajikan sekarang TIDAK cocok dengan hash (dideklarasikan $algo-" . substr($hash, 0, 12) . "..., sebenarnya $algo-" . substr($hitung, 0, 12) . "...)\n"; $gagal++; continue; }
    $ok++;
}
echo "  cocok: $ok, gagal: $gagal\n";

echo "\n== Font dan aset pihak ketiga yang dihosting sendiri ==\n";
$lok = 0;
foreach (($manifest['lokal_pihak_ketiga'] ?? []) as $rel => $a) {
    $p = $akar . '/' . $rel;
    if ( ! is_file($p)) { echo "  [GAGAL] $rel: berkas tidak ada\n"; $gagal++; continue; }
    if ( ! hash_equals($a['sha256'], hash_file('sha256', $p))) { echo "  [GAGAL] $rel: berkas lokal berubah dari manifest\n"; $gagal++; continue; }
    $asli = unduh($a['sumber']);
    if ($asli === NULL) { echo "  [PERINGATAN] $rel: sumber tidak dapat diunduh untuk dibandingkan\n"; continue; }
    if ( ! hash_equals($a['sha256'], hash('sha256', $asli))) { echo "  [PERINGATAN] $rel: sumber sekarang menyajikan berkas yang berbeda (pembaruan versi atau perubahan); salinan lokal tetap yang terverifikasi\n"; continue; }
    $lok++;
}
echo "  identik dengan sumber: $lok dari " . count($manifest['lokal_pihak_ketiga'] ?? []) . "\n";

echo "\n== Tanpa SRI (diakui) ==\n";
foreach (($manifest['tanpa_sri_diakui'] ?? []) as $url => $dipakai) { echo "  $url  <- " . implode(', ', array_slice($dipakai, 0, 3)) . (count($dipakai) > 3 ? ' ...' : '') . "\n"; }
echo "\nRINGKASAN: " . ($gagal === 0 ? "semua aset terverifikasi" : "$gagal kegagalan") . "\n";
exit($gagal === 0 ? 0 : 1);
