<?php
/**
 * Pembuat kartu Open Graph 1200x630 (SEO 6 Okt 2026). Dijalankan di LAPTOP (Windows), hasilnya JPG statis
 * di assets/img/og/ yang di-commit; production tidak butuh GD atau font. seo_meta() memakai kartu
 * assets/img/og/<slug>.jpg bila ada (slug = kunci config, '/' jadi '-', beranda = 'beranda').
 *
 *   php docs/engineering/buat_kartu_og.php
 *
 * Isi kartu: foto tema sebagai latar gelap, lalu logo, label, dan judul rata tengah di zona aman persegi
 * (judul dari config/seo.php); kartu program dari Katalog Program (DB lokal) dengan foto heronya. Font Segoe UI
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
/** Tulis teks rata tengah pada garis dasar $y. */
function tengah($im, $ukuran, $font, $teks, $y, $warna) {
    $b = imagettfbbox($ukuran, 0, $font, $teks);
    imagettftext($im, $ukuran, 0, (int) ((LEBAR - ($b[2] - $b[0])) / 2), $y, $warna, $font, $teks);
}

// Tata letak AMAN DIPOTONG PERSEGI (7 Okt 2026): WhatsApp, di ponsel maupun desktop, memotong og:image jadi
// kotak di tengah. Semua yang harus terbaca (logo, label, judul) berada di zona tengah 630x630
// (x 285..915); foto tema jadi latar penuh yang digelapkan. Deskripsi tidak ditulis di gambar karena
// platform menampilkannya sebagai teks di bawah kartu.
const ZONA = 540; // lebar teks maksimal, di dalam kotak tengah 630 dengan napas di kiri-kanan
foreach ($kartu as $slug => [$label, $judul, $deskripsi, $foto]) {
    $im = imagecreatetruecolor(LEBAR, TINGGI);
    imagealphablending($im, TRUE);
    imagefill($im, 0, 0, warna($im, '#0a1a1f'));

    $src = $foto ? muat($foto) : NULL;
    if ($src) {
        $sw = imagesx($src); $sh = imagesy($src);
        $skala = max(LEBAR / $sw, TINGGI / $sh);
        $cw = (int) (LEBAR / $skala); $ch = (int) (TINGGI / $skala);
        imagecopyresampled($im, $src, 0, 0, (int) (($sw - $cw) / 2), (int) (($sh - $ch) / 2), LEBAR, TINGGI, $cw, $ch);
        imagedestroy($src);
        // Lapisan gelap merata, ditambah pita tengah yang lebih pekat di belakang teks.
        imagefilledrectangle($im, 0, 0, LEBAR, TINGGI, warna($im, '#0a1a1f', 48));
        for ($x = 0; $x < LEBAR; $x++) {
            $jarak = abs($x - LEBAR / 2) / (LEBAR / 2);           // 0 di tengah, 1 di tepi
            $alpha = (int) min(127, 60 + 67 * $jarak * $jarak);  // tengah lebih gelap
            imagefilledrectangle($im, $x, 0, $x, TINGGI, warna($im, '#0a1a1f', $alpha));
        }
    }
    imagefilledrectangle($im, 0, TINGGI - 10, LEBAR, TINGGI, warna($im, '#d6fb00'));

    // Logo + nama layanan, rata tengah.
    $teks_merek = 'Klinik PKP';
    $bm = imagettfbbox(24, 0, $GLOBALS['FONT_TEBAL'], $teks_merek);
    $lebar_merek = 44 + 12 + ($bm[2] - $bm[0]);
    $x0 = (int) ((LEBAR - $lebar_merek) / 2);
    if ($logo) { imagecopyresampled($im, $logo, $x0, 58, 0, 0, 44, (int) (44 * imagesy($logo) / imagesx($logo)), imagesx($logo), imagesy($logo)); }
    imagettftext($im, 24, 0, $x0 + 56, 92, warna($im, '#ffffff'), $GLOBALS['FONT_TEBAL'], $teks_merek);
    tengah($im, 14, $GLOBALS['FONT_BIASA'], 'Disperakim Provinsi Jawa Tengah', 126, warna($im, '#b8c9cd'));

    // Judul: 46pt, turun ke 40pt bila lebih dari tiga baris; blok label + judul dipusatkan di bawah merek.
    $ukuran = 46; $baris_judul = baris($judul, $GLOBALS['FONT_TEBAL'], $ukuran, ZONA, 4);
    if (count($baris_judul) > 3) { $ukuran = 40; $baris_judul = baris($judul, $GLOBALS['FONT_TEBAL'], $ukuran, ZONA, 4); }
    $tinggi_baris = (int) ($ukuran * 1.32);
    // Blok judul dipusatkan pada tengah kartu (sedikit di bawahnya karena logo di atas), BUKAN pada ruang
    // di bawah logo: cara lama menurunkan judul ke sepertiga bawah (umpan balik user 7 Okt 2026).
    $pusat = 345;
    $y = (int) ($pusat - count($baris_judul) * $tinggi_baris / 2 + $ukuran * 0.75); // garis dasar baris pertama
    $y_label = $y - (int) ($ukuran * 0.75) - 26;
    imagefilledrectangle($im, (int) (LEBAR / 2 - 36), $y_label - 40, (int) (LEBAR / 2 + 36), $y_label - 36, warna($im, '#00a3b5'));
    tengah($im, 16, $GLOBALS['FONT_TEBAL'], mb_strtoupper($label), $y_label, warna($im, '#d6fb00'));
    foreach ($baris_judul as $b) { tengah($im, $ukuran, $GLOBALS['FONT_TEBAL'], $b, $y, warna($im, '#ffffff')); $y += $tinggi_baris; }

    imagejpeg($im, $KELUAR . $slug . '.jpg', 80);
    imagedestroy($im);
    $dibuat++;
    echo "  $slug.jpg
";
}
echo "Kartu dibuat: $dibuat di assets/img/og/\n";
