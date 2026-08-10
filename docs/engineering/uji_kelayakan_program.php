<?php
/**
 * Penjaga aturan kelayakan program - desil x status kepemilikan.
 *
 *   php docs/engineering/uji_kelayakan_program.php
 *
 * KENAPA BERKAS INI ADA. Salah di aturan kelayakan TIDAK menghasilkan galat
 * apa pun: halamannya tetap 200, tabelnya tetap terisi, tidak ada yang merah.
 * Yang terjadi cuma warga diarahkan ke bantuan yang keliru - dan itu baru
 * ketahuan berbulan-bulan kemudian di loket. Aturannya karena itu dikunci
 * sebagai MATRIKS PENUH, bukan beberapa contoh.
 *
 * Nol sentuhan DB, nol HTTP: `Smart_filter` adalah fungsi murni desil +
 * kepemilikan -> daftar program. Diuji langsung, jadi ia tidak bisa hijau
 * gara-gara sesuatu yang lain sedang mati.
 *
 * SUMBER ATURANNYA, dan ini yang membuat matriks di bawah bukan karangan:
 * docs/meetings/22_juni_2026/ANALISA_PROGRAM_PPT_UN_HABITAT.md - ekstraksi PPT
 * UN HABITAT 2026. §1 memetakan desil -> program; §2 D menyebut PB Backlog
 * untuk "menumpang di rumah orangtua/saudara ATAU sewa/kontrak", dan §2 C
 * menyebut RTLH untuk "rumahnya masuk kategori tidak layak".
 *
 * Penyaring kepemilikan ditambahkan 5 Agt 2026 (revisi dinas butir A7):
 * status rumah MENYARING DI ATAS desil, tidak menggantikannya.
 */

define('APP_ROOT', dirname(__DIR__, 2));

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;

function cek($kondisi, $label) {
    $GLOBALS['uji_total']++;
    echo ($kondisi ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $kondisi) { $GLOBALS['uji_gagal']++; }
    return (bool) $kondisi;
}
function wajib($kondisi, $label) {
    if ( ! cek($kondisi, $label)) { fwrite(STDERR, "Berhenti: prasyarat gagal.\n"); exit(1); }
}

// CI tidak di-bootstrap; `Smart_filter::__construct()` cuma memanggil
// get_instance(), jadi cukup distub. Kalau kelak ia benar-benar memakai CI,
// uji ini akan gagal keras di sini - bukan diam-diam berubah makna.
if ( ! defined('BASEPATH')) { define('BASEPATH', 1); }
if ( ! function_exists('get_instance')) {
    function get_instance() { static $ci; if ( ! $ci) { $ci = new stdClass(); } return $ci; }
}
require_once APP_ROOT . '/application/libraries/Smart_filter.php';

echo "=== UJI KELAYAKAN PROGRAM (desil x kepemilikan) ===\n\n";
$sf = new Smart_filter();

/** Kode program yang keluar, diurutkan supaya perbandingannya stabil. */
$kode = function ($desil, $kepemilikan) use ($sf) {
    $out = array_map(
        static fn($p) => (string) ($p['kode'] ?? $p['id'] ?? '?'),
        $sf->get_eligible_programs($desil, $kepemilikan)
    );
    sort($out);
    return $out;
};

const TAK_LAYAK = 'Punya Rumah Tidak Layak';
const LAYAK     = 'Punya Rumah Layak';
const LAHAN     = 'Punya Lahan Belum Bangun';
const NUMPANG   = 'Numpang/Keluarga';
const SEWA      = 'Sewa/Kontrak';

/* ---------------------------------------------------------------- 1
 * MATRIKS PENUH. Ditulis sebagai harapan, bukan disalin dari keluaran -
 * menyalin keluaran berarti menguji bahwa kode sama dengan dirinya sendiri.
 */
echo "== 1. Matriks penuh desil x kepemilikan ==\n";
$matriks = [
    // desil => [ kepemilikan => program yang diharapkan (urut abjad) ]
    1  => [TAK_LAYAK => ['rtlh'], LAYAK => ['umum'], LAHAN => ['pb'], NUMPANG => ['pb'], SEWA => ['pb']],
    2  => [TAK_LAYAK => ['rtlh'], LAYAK => ['umum'], LAHAN => ['pb'], NUMPANG => ['pb'], SEWA => ['pb']],
    3  => [TAK_LAYAK => ['rtlh'], LAYAK => ['umum'], LAHAN => ['pb'], NUMPANG => ['pb'], SEWA => ['pb']],
    4  => [TAK_LAYAK => ['omah_sekeng'], LAYAK => ['omah_sekeng'],
           LAHAN => ['omah_sekeng', 'pb'], NUMPANG => ['omah_sekeng', 'pb'], SEWA => ['omah_sekeng', 'pb']],
    // Desil 5-10 = jalur PEMBIAYAAN. Membeli rumah terbuka apa pun status
    // hunian sekarang, jadi kepemilikan TIDAK boleh menyaring apa pun di sini.
    5  => [TAK_LAYAK => ['flpp', 'oemah_lestari'], LAYAK => ['flpp', 'oemah_lestari'],
           LAHAN => ['flpp', 'oemah_lestari'], NUMPANG => ['flpp', 'oemah_lestari'], SEWA => ['flpp', 'oemah_lestari']],
    8  => [TAK_LAYAK => ['flpp', 'oemah_lestari'], LAYAK => ['flpp', 'oemah_lestari'],
           LAHAN => ['flpp', 'oemah_lestari'], NUMPANG => ['flpp', 'oemah_lestari'], SEWA => ['flpp', 'oemah_lestari']],
    9  => [TAK_LAYAK => ['oemah_lestari'], LAYAK => ['oemah_lestari'],
           LAHAN => ['oemah_lestari'], NUMPANG => ['oemah_lestari'], SEWA => ['oemah_lestari']],
    10 => [TAK_LAYAK => ['oemah_lestari'], LAYAK => ['oemah_lestari'],
           LAHAN => ['oemah_lestari'], NUMPANG => ['oemah_lestari'], SEWA => ['oemah_lestari']],
];
foreach ($matriks as $desil => $baris) {
    foreach ($baris as $kep => $harap) {
        $dapat = $kode($desil, $kep);
        cek($dapat === $harap,
            "desil {$desil} + {$kep} => " . implode(',', $harap)
            . ($dapat === $harap ? '' : '  (DAPAT: ' . implode(',', $dapat) . ')'));
    }
}

/* ---------------------------------------------------------------- 2
 * Inti butir A7, dinyatakan ulang sebagai kalimat dinas sendiri supaya
 * maksudnya tidak hilang di balik matriks di atas.
 */
echo "\n== 2. Kalimat dinas: milik sendiri -> kualitas, selain itu -> baru ==\n";
foreach ([1, 2, 3] as $d) {
    cek( ! in_array('pb', $kode($d, TAK_LAYAK), TRUE),
        "desil {$d}: punya rumah (tidak layak) TIDAK ditawari pembangunan baru");
    cek(in_array('rtlh', $kode($d, TAK_LAYAK), TRUE),
        "desil {$d}: punya rumah (tidak layak) ditawari peningkatan kualitas");
    foreach ([SEWA, NUMPANG] as $k) {
        cek( ! in_array('rtlh', $kode($d, $k), TRUE),
            "desil {$d}: {$k} TIDAK ditawari peningkatan kualitas");
        cek(in_array('pb', $kode($d, $k), TRUE),
            "desil {$d}: {$k} ditawari pembangunan baru");
    }
}

/* ---------------------------------------------------------------- 3
 * Yang TIDAK boleh ikut berubah. Menyaring itu gampang kebablasan, dan
 * kebablasan di sini berarti menutup bantuan yang seharusnya terbuka.
 */
echo "\n== 3. Yang sengaja TIDAK disaring ==\n";
cek($kode(1, '') === ['pb', 'rtlh'],
    'Kepemilikan kosong: nol penyaringan - lebih baik menawarkan terlalu banyak daripada diam-diam menutup');
cek(in_array('omah_sekeng', $kode(4, LAYAK), TRUE),
    'Omah Sekeng tetap muncul apa pun kepemilikannya - dokumen sumber tidak menyebut jenis intervensinya');
foreach ([5, 6, 7, 8] as $d) {
    cek($kode($d, TAK_LAYAK) === $kode($d, SEWA),
        "desil {$d}: jalur pembiayaan tidak terpengaruh kepemilikan sama sekali");
}
cek($kode(6, LAHAN) === ['flpp', 'oemah_lestari'], 'Desil 6 tetap utuh (regresi palet lama)');

/* ---------------------------------------------------------------- 4 */
echo "\n== 4. Tepi ==\n";
cek($kode(0, SEWA) === ['umum'], 'Desil di luar rentang jatuh ke Program Terpadu, bukan kosong');
cek($kode(11, SEWA) === ['umum'], 'Desil 11 juga');
$pesisir = $sf->get_eligible_programs(9, LAYAK, TRUE);
cek(in_array('rumah_apung', array_column($pesisir, 'kode'), TRUE),
    'Kawasan pesisir tetap menambah Rumah Apung (bypass desil)');

/* ---------------------------------------------------------------- 5
 * Penjaga sumber: kalau pemetaan ini diubah, komentarnya harus ikut
 * menunjuk dokumen asalnya. Aturan tanpa sumber akan diubah orang berikutnya
 * atas dasar "sepertinya lebih masuk akal".
 */
echo "\n== 5. Aturannya menunjuk sumbernya ==\n";
$src = (string) @file_get_contents(APP_ROOT . '/application/libraries/Smart_filter.php');
wajib($src !== '', 'Sumber Smart_filter.php terbaca');
cek(strpos($src, 'ANALISA_PROGRAM_PPT_UN_HABITAT') !== FALSE,
    'Pemetaan kepemilikan menyebut dokumen sumbernya');
cek(is_file(APP_ROOT . '/docs/meetings/22_juni_2026/ANALISA_PROGRAM_PPT_UN_HABITAT.md'),
    'Dokumen sumbernya benar-benar ada di repo');

echo "\nRINGKASAN: {$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
