<?php
require_once __DIR__ . '/_akun_demo.php'; // akun demo nonaktif sejak migrasi 073: dipinjam selama suite berjalan
date_default_timezone_set('Asia/Jakarta');
/**
 * Uji tampilan layar daftar super admin (permintaan pemilik produk 2 Okt 2026:
 * tombol dan kartu ringkas dan seragam, tanpa gradien, label huruf kalimat).
 *
 * Login sebagai superadmin, render tiap layar, lalu periksa isi <main id="main-content">:
 *   1. status 200 dan konten utamanya ada;
 *   2. tidak ada tombol di luar set tombol bersama (logika pemindai SAMA dengan
 *      tombol_liar_admin() di uji_regresi_tampilan.php, dijalankan atas HTML hasil render);
 *   3. tidak ada kata HURUF BESAR SEMUA di label tombol (singkatan dua-tiga huruf
 *      seperti NIK, KKN, PSU, SRP2 dikecualikan);
 *   4. tidak ada gradien (bg-gradient, from-*, linear-gradient).
 * Ditambah pemeriksaan sumber: utang tombol berkas view milik layar ini nol.
 *
 * Jalankan: php docs/engineering/uji_ui_daftar_superadmin.php
 */
define('BASE_URL', getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new');
define('APP_ROOT', dirname(__DIR__, 2));

$total = 0; $gagal = 0;
function cek($kondisi, $label) {
    global $total, $gagal;
    $total++;
    if ($kondisi) { echo "  OK    {$label}\n"; } else { $gagal++; echo "  GAGAL {$label}\n"; }
}

$jar = tempnam(sys_get_temp_dir(), 'ujisa');
function http($path, $post = NULL, $ajax = FALSE) {
    global $jar;
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar,
        CURLOPT_FOLLOWLOCATION => TRUE, CURLOPT_TIMEOUT => 60]);
    if ($ajax) { curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest']); }
    if ($post !== NULL) { curl_setopt($ch, CURLOPT_POST, TRUE); curl_setopt($ch, CURLOPT_POSTFIELDS, $post); }
    $body = (string) curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body];
}

// Pemindai yang sama dengan tombol_liar_admin() di uji_regresi_tampilan.php; mengembalikan
// daftar tag (bukan cuma jumlah) supaya pesan GAGAL langsung menunjuk tombolnya.
function tombol_liar($isi) {
    $isi = preg_replace_callback('/<\?(?:php|=)?(.*?)\?>/s', fn($m) => '{' . str_replace(['"', "'", '<', '>'], ' ', $m[1]) . '}', $isi);
    preg_match_all('/<(button|a)\b((?:"[^"]*"|\'[^\']*\'|[^>\'"])*)>/s', $isi, $m, PREG_SET_ORDER);
    $liar = [];
    foreach ($m as $t) {
        if ($t[1] === 'a' && ! preg_match('/tombol-|rounded[^"]*\bpy-|\bpy-[^"]*rounded/', $t[2])) { continue; }
        if ($t[1] === 'a' && ! preg_match('/\b(?:bg-|border\b|tombol-)/', $t[2])) { continue; }
        if (preg_match('/\b(?:tombol-utama|tombol-kedua|tombol-aksi|chip-filter|tombol-tab|tombol-ikon)\b/', $t[2])) { continue; }
        $liar[] = substr(preg_replace('/\s+/', ' ', $t[0]), 0, 120);
    }
    return $liar;
}

// Label tombol (teks di dalam <button>/<a> bergaya set tombol) yang memuat kata 4+ huruf kapital semua.
function label_kapital($html) {
    preg_match_all('#<(button|a)\b[^>]*class="[^"]*\b(?:tombol-[a-z-]+|chip-filter)\b[^"]*"[^>]*>(.*?)</\1>#s', $html, $m);
    $salah = [];
    foreach ($m[2] as $isi) {
        $teks = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($isi))));
        if (preg_match('/\b[A-Z]{4,}\b/', $teks)) { $salah[] = $teks; }
    }
    return $salah;
}

function isi_utama($html) {
    $a = strpos($html, '<main id="main-content"');
    $b = $a === FALSE ? FALSE : strpos($html, '</main>', $a);
    return ($a === FALSE || $b === FALSE) ? '' : substr($html, $a, $b - $a);
}

echo "== Login superadmin ==\n";
$hal_login = http('Auth/login');
$token = preg_match('/name="csrf_kpkp_token" value="([^"]+)"/', $hal_login['body'], $m) ? $m[1] : '';
$r = http('Auth/do_login', ['csrf_kpkp_token' => $token, 'email' => 'admin@klinikpkp.jatengprov.go.id', 'password' => pinjam_akun_demo('admin@klinikpkp.jatengprov.go.id')], TRUE);
$masuk = (json_decode($r['body'], TRUE)['status'] ?? '') === 'success';
cek($masuk, 'Login superadmin berhasil');
if ( ! $masuk) { @unlink($jar); exit(1); }

// ID nyata untuk layar yang butuh parameter; diambil dari DB, bukan dikarang.
$db = @new mysqli('localhost', 'root', '', 'klinikpkp');
$satu = function ($sql) use ($db) { $q = $db->connect_errno ? FALSE : $db->query($sql); $b = $q ? $q->fetch_row() : NULL; return $b[0] ?? NULL; };
$id_daftar = $satu('SELECT id FROM kkn_magang_pendaftaran ORDER BY id LIMIT 1');
$id_staf   = $satu("SELECT id FROM usr_akun WHERE peran IN ('admin_kabkota','admin_bidang') ORDER BY id LIMIT 1");
$kode_bid  = $satu('SELECT kode FROM bidang ORDER BY kode LIMIT 1');

$layar = [
    'Antrean perumahan'   => 'Admin',
    'Pantau aduan'        => 'Admin_Aduan',
    'Direktori SRP2'      => 'Admin_Srp2',
    'SRP2 pengajuan'      => 'Admin_Srp2/pending',
    'Tambah pengembang'   => 'Admin_Srp2/tambah',
    'KKN & Magang'        => 'Admin_Kemitraan',
    'Slot & bidang'       => 'Admin_Kemitraan/slot',
    'Akun universitas'    => 'Admin_Kemitraan/universitas',
    'Manajemen pengguna'  => 'Admin_Users',
    'Jejak audit'         => 'Admin_Audit',
    'Asosiasi'            => 'Admin_Asosiasi',
    'Data PSU'            => 'Admin_Psu',
    'Katalog program'     => 'Admin_Katalog_Program',
    'Struktur'            => 'Admin_Struktur',
    'Bank data'           => 'Admin_Bank_Data',
    'Posisi magang'       => 'Admin_Magang_Posisi',
    'Konsultasi'          => 'Admin_Konsultasi',
    'Konten beranda'      => 'Admin_Content',
];
if ($kode_bid)  { $layar['Detail slot bidang'] = 'Admin_Kemitraan/slot_bidang/' . rawurlencode($kode_bid) . '/' . date('Y'); }
if ($id_daftar) { $layar['Ubah pendaftaran']   = 'Admin_Kemitraan/ubah/' . (int) $id_daftar; }
if ($id_staf)   { $layar['Hak modul']          = 'Admin_Privileges/index/' . (int) $id_staf; }

foreach ($layar as $nama => $url) {
    echo "\n== {$nama} ({$url}) ==\n";
    $r = http($url);
    $utama = isi_utama($r['body']);
    cek($r['code'] === 200 && $utama !== '', "{$nama}: status 200 dan konten utama dirender (status {$r['code']})");
    if ($utama === '') { continue; }
    $liar = tombol_liar($utama);
    cek($liar === [], "{$nama}: semua tombol memakai set tombol bersama" . ($liar ? ' (liar: ' . implode(' | ', array_slice($liar, 0, 3)) . ')' : ''));
    $kapital = label_kapital($utama);
    cek($kapital === [], "{$nama}: label tombol huruf kalimat, tanpa kata kapital semua" . ($kapital ? ' (' . implode(' | ', array_slice($kapital, 0, 3)) . ')' : ''));
    cek(preg_match('/bg-gradient|\b(?:from|via)-[a-z]+-\d|linear-gradient/', $utama) === 0, "{$nama}: tanpa gradien dekoratif");
}

echo "\n== Sumber view: utang tombol nol ==\n";
$berkas = array_merge(
    ['admin/antrean/dashboard.php', 'admin/aduan/index.php', 'admin/srp2/index.php', 'admin/srp2/pending.php', 'admin/srp2/ubah.php'] /* tambah.php dilebur ke ubah.php 2 Okt 2026 */,
    array_map(fn($p) => 'admin/kemitraan/' . basename($p), array_filter(glob(APP_ROOT . '/application/views/admin/kemitraan/*.php'), fn($p) => basename($p) !== 'peserta.php')),
    array_map(fn($p) => str_replace(APP_ROOT . '/application/views/', '', $p), glob(APP_ROOT . '/application/views/admin/{users,audit,asosiasi,psu,katalog,struktur,bank_data,magang_posisi,konsultasi,content}/*.php', GLOB_BRACE))
);
foreach ($berkas as $b) {
    $isi = (string) @file_get_contents(APP_ROOT . '/application/views/' . $b);
    $liar = tombol_liar($isi);
    cek($isi !== '' && $liar === [], "{$b}: utang tombol 0" . ($liar ? ' (' . count($liar) . ')' : ''));
    cek(preg_match('/bg-gradient|\b(?:from|via)-[a-z]+-\d|linear-gradient/', $isi) === 0, "{$b}: tanpa gradien");
}

@unlink($jar);
echo "\nRINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal > 0 ? 1 : 0);
