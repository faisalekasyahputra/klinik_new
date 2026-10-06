<?php
/**
 * Pembuat kartu Open Graph 1200x630 (SEO 6 Okt 2026). Dijalankan di LAPTOP (Windows), hasilnya JPG statis
 * di assets/img/og/ yang di-commit; production tidak butuh GD atau font. seo_meta() memakai kartu
 * assets/img/og/<slug>.jpg bila ada (slug = kunci config, '/' jadi '-', beranda = 'beranda').
 *
 *   php docs/engineering/buat_kartu_og.php
 *
 * Isi kartu: foto tema di kanan, logo dan nama layanan di kiri atas, label kecil, judul, dan deskripsi
 * dari config/seo.php; kartu program dari Katalog Program (DB lokal) dengan foto heronya. Font Segoe UI
 * dari C:\Windows\Fonts hanya dipakai untuk merender; berkas font tidak ikut repo. Jalankan ulang
 * sesudah judul di config/seo.php atau program di Katalog Program berubah, lalu commit hasilnya.
 */
require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php';
define('APP_ROOT', dirname(__DIR__, 2));
define('BASEPATH', APP_ROOT . '/system/');
define('APPPATH', APP_ROOT . '/application/');
const LEBAR = 1200, TINGGI = 630;
$FONT_TEBAL = 'C:/Windows/Fonts/segoeuib.ttf';
$FONT_BIASA = 'C:/Windows/Fonts/segoeui.ttf';
foreach ([$FONT_TEBAL, $FONT_BIASA] as $f) { if ( ! is_file($f)) { fwrite(STDERR, "Font tidak ada: $f\n"); exit(1); } }
$KELUAR = APP_ROOT . '/assets/img/og/';
if ( ! is_dir($KELUAR)) { mkdir($KELUAR, 0755, TRUE); }

$config = [];
include APPPATH . 'config/seo.php';
$cfg = $config['seo'];

/** Label dan foto tema per halaman; yang tidak disebut memakai bawaan. */
$tema = [
    ''                       => ['Portal Layanan', 'assets/img/hero/hero-perumahan-subsidi-opt.jpeg'],
    'golek_omah'             => ['Cari Rumah', 'assets/img/hero/hero-perumahan-subsidi-opt.jpeg'],
    'cari_rumah'             => ['Cari Rumah', 'assets/img/hero/hero-perumahan-subsidi-opt.jpeg'],
    'simulasi_kpr'           => ['Pembiayaan', 'assets/img/program/01_subsidif_lpp.jpeg'],
    'panduan_desain'         => ['Desain Rumah', 'assets/img/program/03_rtlh.jpeg'],
    'program-pemerintah'     => ['Program Pemerintah', 'assets/img/program/02_oemahletari.jpeg'],
    'kawasan_kumuh'          => ['Kawasan Permukiman', 'assets/img/program/05_areakumuh.jpeg'],
    'sebaran'                => ['Peta Sebaran', 'assets/img/map-semarang.jpg'],
    'psu'                    => ['Pengembang', 'assets/img/hero/hero-kawasan-permukiman-opt.jpeg'],
    'statistika'             => ['Bank Data', 'assets/img/map-semarang.jpg'],
    'dokumen'                => ['Bank Data', 'assets/img/map-semarang.jpg'],
    'tab/bankdata'           => ['Bank Data', 'assets/img/map-semarang.jpg'],
    'cek_rtlh'               => ['Rumah Tidak Layak Huni', 'assets/img/program/03_rtlh.jpeg'],
    'tab/pengembang'         => ['Pengembang', 'assets/img/hero/hero-sertifikasi-lahan-opt.jpeg'],
    'umum/pengembang'        => ['Pengembang', 'assets/img/hero/hero-sertifikasi-lahan-opt.jpeg'],
    'pengembang/syarat'      => ['Sertifikasi SRP2', 'assets/img/hero/hero-sertifikasi-lahan-opt.jpeg'],
    'pengembang/sertifikasi' => ['Sertifikasi SRP2', 'assets/img/hero/hero-sertifikasi-lahan-opt.jpeg'],
    'kemitraanportal/magang' => ['Kemitraan', 'assets/img/hero/hero-permukiman-prambanan-opt.jpeg'],
    'kemitraanportal/kkn'    => ['Kemitraan', 'assets/img/hero/hero-permukiman-prambanan-opt.jpeg'],
    'kemitraan'              => ['Kemitraan', 'assets/img/hero/hero-permukiman-prambanan-opt.jpeg'],
    'profil'                 => ['Profil Dinas', 'assets/img/hero/hero-permukiman-prambanan-opt.jpeg'],
    'tugas_pokok'            => ['Profil Dinas', 'assets/img/hero/hero-permukiman-prambanan-opt.jpeg'],
];
$bawaan = ['Klinik PKP', 'assets/img/hero/hero-kawasan-permukiman-opt.jpeg'];

$kartu = [];
foreach ($cfg['halaman'] as $kunci => $isi) {
    $k = strtolower((string) $kunci);
    if (in_array($k, ['auth/login', 'auth/register', 'kebijakan-privasi', 'syarat-ketentuan'], TRUE)) { continue; } // cukup og-cover
    [$label, $foto] = $tema[$k] ?? $bawaan;
    $kartu[$k === '' ? 'beranda' : str_replace('/', '-', $k)] = [$label, $isi['judul'], $isi['deskripsi'], $foto];
}

// Program aktif dari DB lokal; foto lewat Program_model::gambar_tampil() tanpa menyalakan CI.
$env = [];
foreach (file(env_berkas_path(APP_ROOT), FILE_IGNORE_NEW_LINES) as $l) {
    $l = trim($l);
    if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) { continue; }
    [$a, $b] = explode('=', $l, 2);
    if ( ! array_key_exists(trim($a), $env)) { $env[trim($a)] = trim($b, " \t\"'"); }
}
if ( ! in_array(strtolower($env['DB_HOST'] ?? ''), ['localhost', '127.0.0.1', '::1'], TRUE)) { fwrite(STDERR, "Hanya untuk DB lokal.\n"); exit(1); }
if ( ! class_exists('CI_Model')) { class CI_Model {} } // ponytail: cukup untuk memuat konstanta dan gambar_tampil()
require_once APPPATH . 'models/Program_model.php';
$pm = (new ReflectionClass('Program_model'))->newInstanceWithoutConstructor();
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$db->set_charset('utf8mb4');
$q = $db->query("SELECT p.kode_program, p.nama_program, p.deskripsi_singkat, p.lencana, p.gambar, k.nama_kategori
    FROM sf_program p LEFT JOIN sf_program_kategori k ON k.id = p.kategori_id WHERE p.aktif = 1");
foreach ($q->fetch_all(MYSQLI_ASSOC) as $p) {
    $kartu['program-pemerintah-' . str_replace('_', '-', $p['kode_program'])] = [
        $p['nama_kategori'] ?: 'Program Pemerintah', 'Syarat ' . $p['nama_program'],
        $p['deskripsi_singkat'] . ($p['lencana'] ? ' Sasaran: ' . $p['lencana'] . '.' : ''), $pm->gambar_tampil($p)];
}

function warna($im, $hex, $alpha = 0) {
    return imagecolorallocatealpha($im, hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2)), $alpha);
}
function muat($path) {
    $isi = @file_get_contents(APP_ROOT . '/' . $path);
    return $isi === FALSE ? NULL : @imagecreatefromstring($isi);
}
/** Pecah teks jadi baris yang muat $lebar piksel; baris ke-$maks diberi elipsis bila terpotong. */
function baris($teks, $font, $ukuran, $lebar, $maks) {
    $kata = preg_split('/\s+/u', trim($teks)); $hasil = []; $kini = '';
    foreach ($kata as $w) {
        $coba = $kini === '' ? $w : "$kini $w";
        $b = imagettfbbox($ukuran, 0, $font, $coba);
        if ($b[2] - $b[0] > $lebar && $kini !== '') { $hasil[] = $kini; $kini = $w; } else { $kini = $coba; }
    }
    if ($kini !== '') { $hasil[] = $kini; }
    if (count($hasil) > $maks) { $hasil = array_slice($hasil, 0, $maks); $hasil[$maks - 1] = rtrim($hasil[$maks - 1], ' ,.;:') . '…'; }
    return $hasil;
}

$logo = muat('assets/img/logo-jateng.png');
$dibuat = 0;
foreach ($kartu as $slug => [$label, $judul, $deskripsi, $foto]) {
    $im = imagecreatetruecolor(LEBAR, TINGGI);
    imagealphablending($im, TRUE);
    imagefill($im, 0, 0, warna($im, '#0a1a1f'));

    // Foto di kanan (crop menutup 520x630), dilebur ke latar dengan gradasi.
    $src = $foto ? muat($foto) : NULL;
    if ($src) {
        $fw = 520; $sw = imagesx($src); $sh = imagesy($src);
        $skala = max($fw / $sw, TINGGI / $sh);
        $cw = (int) ($fw / $skala); $ch = (int) (TINGGI / $skala);
        imagecopyresampled($im, $src, LEBAR - $fw, 0, (int) (($sw - $cw) / 2), (int) (($sh - $ch) / 2), $fw, TINGGI, $cw, $ch);
        for ($x = 0; $x < 260; $x++) {
            imagefilledrectangle($im, LEBAR - $fw + $x, 0, LEBAR - $fw + $x, TINGGI, warna($im, '#0a1a1f', (int) (127 * $x / 260)));
        }
        imagedestroy($src);
    }
    // Pita aksen bawah dan garis teal.
    imagefilledrectangle($im, 0, TINGGI - 10, LEBAR, TINGGI, warna($im, '#d6fb00'));
    imagefilledrectangle($im, 64, 168, 140, 173, warna($im, '#00a3b5'));

    // Logo dan nama layanan.
    if ($logo) { imagecopyresampled($im, $logo, 64, 52, 0, 0, 52, (int) (52 * imagesy($logo) / imagesx($logo)), imagesx($logo), imagesy($logo)); }
    imagettftext($im, 26, 0, 132, 82, warna($im, '#ffffff'), $GLOBALS['FONT_TEBAL'], 'Klinik PKP');
    imagettftext($im, 15, 0, 132, 110, warna($im, '#9fb3b8'), $GLOBALS['FONT_BIASA'], 'Disperakim Provinsi Jawa Tengah');

    // Label, judul, deskripsi.
    imagettftext($im, 17, 0, 64, 222, warna($im, '#d6fb00'), $GLOBALS['FONT_TEBAL'], mb_strtoupper($label));
    $y = 290;
    foreach (baris($judul, $GLOBALS['FONT_TEBAL'], 44, 610, 3) as $b) { imagettftext($im, 44, 0, 64, $y, warna($im, '#ffffff'), $GLOBALS['FONT_TEBAL'], $b); $y += 60; }
    $y += 14;
    foreach (baris($deskripsi, $GLOBALS['FONT_BIASA'], 21, 600, 3) as $b) { imagettftext($im, 21, 0, 64, $y, warna($im, '#c4d3d6'), $GLOBALS['FONT_BIASA'], $b); $y += 32; }

    imagejpeg($im, $KELUAR . $slug . '.jpg', 80);
    imagedestroy($im);
    $dibuat++;
    echo "  $slug.jpg\n";
}
echo "Kartu dibuat: $dibuat di assets/img/og/\n";
