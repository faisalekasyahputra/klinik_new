<?php
/**
 * Penjaga pencarian rumah SIKUMBANG - butir A3 revisi dinas.
 *
 *   php docs/engineering/uji_cari_rumah_sikumbang.php
 *
 * 🔻 USANG SEBAGIAN SEJAK 13 Agt 2026 - JANGAN PERCAYA HIJAU/MERAHNYA UNTUK
 * cari_wil() TANPA MEMBACA INI DULU. Kontrak endpoint itu berubah total:
 * Index::cari_wil() tidak lagi menerima `page`/`limit` dan mengiris SATU
 * halaman - sekarang ia SELALU mengembalikan SEMUA hasil (sampai
 * SIK_MAKS_BONGKAH) sekaligus, dipotong per Index::HALAMAN_UKURAN (20) dan
 * dibungkus <div class="halaman-data" data-halaman="N">, dengan Sebelumnya/
 * Berikutnya di cari_rumah.php sekarang murni tukar tampil/sembunyi DI
 * BROWSER - nol request susulan. Skenario 1-3 & 4 di bawah (yang memanggil
 * `minta(..., 'cari_wil', ...)` dengan `page`/`limit` custom) MENGASUMSIKAN
 * kontrak lama dan akan salah baca hasilnya: `kartu()` menghitung SEMUA
 * `detail_perum/` di HTML termasuk yang tersembunyi CSS (`display:none`
 * bukan absen dari markup), jadi angkanya sekarang = TOTAL semua halaman,
 * bukan satu halaman. Skenario yang memanggil `load_more` (bukan cari_wil)
 * TETAP valid - Index::load_more() dan lokasi_tersaring() SENGAJA tidak
 * diubah (masih dipakai data_spasial/sikumbang.php apa adanya). Belum
 * ditulis ulang karena sesi ini sedang menambal produksi langsung; siapa
 * pun yang menyentuh cari_wil() berikutnya, tulis ulang skenario 1-4 dulu
 * sebelum percaya suite ini hijau.
 *
 * KENAPA BERKAS INI ADA. Dinas melaporkan "kayaknya belum ke-load semua,
 * muncul cuma 3 atau 6 gambar saja". Jawaban kami yang pertama KELIRU: kami
 * menulis "angka 3 dan 6 tidak cocok dengan pengaturan mana pun, jadi
 * kemungkinan datanya memang sedikit". Ternyata cocok persis.
 *
 * Penyaring subsidi/non-subsidi berjalan di sisi kita, sementara API diminta
 * `limit=9` - jadi sembilan baris itulah yang disaring, dan yang tersisa 1-8
 * buah. Terukur 10 Agt 2026 di Kota Semarang (wilayah bawaan halaman): API
 * mengirim 9, yang lolos saringan subsidi hanya SATU. Halaman 3 malah nol,
 * dan `load_more()` membaca balasan kosong sebagai "Semua Data Telah Dimuat"
 * sehingga daftarnya mati padahal halaman 4 masih berisi.
 *
 * TIDAK MENYENTUH JARINGAN. `Index::bongkah_sikumbang()` membaca cache berkas
 * lebih dulu, jadi cache-nya kita isi sendiri dengan data uji. Itu sekaligus
 * membuat penjaga ini menguji KODE KITA, bukan ketersediaan API orang lain -
 * uji yang merah karena SIKUMBANG sedang mati adalah uji yang tidak berguna.
 *
 * Kode wilayah uji sengaja 99xx (tidak ada di Jateng) supaya tidak mungkin
 * tertukar dengan cache wilayah sungguhan milik pengguna.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('CACHE_DIR', APP_ROOT . '/application/cache/');
define('BONGKAH', 100);   // harus sama dengan Index::SIK_BONGKAH

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;
$GLOBALS['bersih']    = [];

function cek($k, $l) {
    $GLOBALS['uji_total']++;
    echo ($k ? '  OK    ' : '  GAGAL ') . $l . "\n";
    if ( ! $k) { $GLOBALS['uji_gagal']++; }
    return (bool) $k;
}
function wajib($k, $l) {
    if ( ! cek($k, $l)) { bersihkan(); fwrite(STDERR, "Berhenti: prasyarat gagal.\n"); exit(1); }
}
function bersihkan() {
    foreach ($GLOBALS['bersih'] as $f) { @unlink($f); }
}
register_shutdown_function('bersihkan');

/** Satu baris lokasi tiruan, berisi persis medan yang dibaca kartu rumah. */
function baris($id, $subsidi) {
    return [
        'idLokasi'      => $id,
        'namaPerumahan' => 'Perumahan Uji ' . $id,
        'foto'          => ['upload/uji.jpg'],
        'wilayah'       => ['kabupaten' => 'Kab Uji', 'provinsi' => 'Jawa Tengah'],
        'pengembang'    => ['nama' => 'PT Uji Sejahtera'],
        'tipeRumah'     => [[
            'status'        => $subsidi ? 'Subsidi' : 'Komersil',
            'luasBangunan'  => 36, 'luasTanah' => 72,
            'kamarTidur'    => 2,  'kamarMandi' => 1,
            'harga'         => 168000000,
        ]],
    ];
}

/**
 * Kunci cache HARUS dihitung dengan urutan parameter yang sama persis seperti
 * `Index::bongkah_sikumbang()`. Kalau urutannya berbeda, md5-nya berbeda, dan
 * penjaga ini akan diam-diam menembak jaringan sungguhan - hijau yang salah.
 */
function seed($wilayah, $halaman_api, array $baris) {
    $url = 'https://sikumbang.tapera.go.id/ajax/lokasi/search?' . http_build_query([
        'kodeWilayah' => $wilayah,
        'keyword'     => '',
        'searchBy'    => 'nama-perumahan',
        'sort'        => 'terbaru',
        'limit'       => BONGKAH,
        'page'        => $halaman_api,
    ]);
    $f = CACHE_DIR . 'ajax_perumahan_' . md5($url) . '.json';
    file_put_contents($f, json_encode(['count' => ['totalLokasi' => 999], 'data' => $baris]));
    $GLOBALS['bersih'][] = $f;
    return $f;
}

/** Bongkahan berisi $n baris, $s di antaranya subsidi. */
function bongkah($n, $s, $mulai) {
    $out = [];
    for ($i = 0; $i < $n; $i++) { $out[] = baris($mulai + $i, $i < $s); }
    return $out;
}

function http($path) {
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_TIMEOUT => 40]);
    $b = (string) curl_exec($ch);
    curl_close($ch);
    return $b;
}
/** Jumlah kartu = jumlah tautan detail, tepat satu per hasil. */
function kartu($html) {
    return substr_count($html, 'detail_perum/');
}
/** Jumlah wrapper halaman yang dirender server. */
function halaman($html) {
    return substr_count($html, 'class="halaman-data"');
}
function minta($wilayah, $status, $halaman, $aksi = 'cari_wil', $limit = 9) {
    return http($aksi . '?' . http_build_query([
        'kodeWilayah'  => $wilayah, 'keyword' => '', 'searchBy' => 'nama-perumahan',
        'sort'         => 'terbaru', 'status_rumah' => $status,
        'page'         => $halaman, 'limit' => $limit,
    ]));
}

echo "=== UJI PENCARIAN RUMAH SIKUMBANG (butir A3) ===\n\n";
wajib(is_dir(CACHE_DIR) && is_writable(CACHE_DIR), 'Folder cache bisa ditulis');

// ---------------------------------------------------------------------------
// Skenario 1 - "Semarang": bongkahan gemuk, yang cocok sedikit.
// 100 baris hanya 1 subsidi, lalu 100 baris 20 subsidi, lalu habis.
// Total subsidi = 21. Sebelum perbaikan, halaman 1 memberi SATU kartu.
// ---------------------------------------------------------------------------
echo "== 1. Yang cocok sedikit di antara yang banyak ==
";
/* KONTRAK BARU sejak 13 Agt 2026, dan skenario 1-4 ditulis ULANG karenanya.
   `cari_wil()` tidak lagi menerima page/limit dan tidak lagi mengiris satu
   halaman. Ia mengembalikan SELURUH hasil sekaligus, dipotong per
   Index::HALAMAN_UKURAN ke dalam <div class="halaman-data">, dan tombol
   Sebelumnya/Berikutnya cuma menukar tampil/sembunyi di browser.

   Versi lama memakai `kartu()` sebagai "jumlah di satu halaman". Itu SALAH
   BACA sekarang: halaman 2+ disembunyikan lewat CSS, jadi markup-nya tetap ada
   dan `kartu()` menghitung TOTAL. Angkanya yang membongkar: asersi "harus 9"
   mendapat 21, yaitu 1+20 dari kedua bongkahan. */
seed('9901', 1, bongkah(BONGKAH, 1, 1000));
seed('9901', 2, bongkah(BONGKAH, 20, 2000));
seed('9901', 3, []);

$h1 = minta('9901', 'subsidi', 1);
cek(kartu($h1) === 21, 'Seluruh 21 subsidi dikirim sekaligus (dapat: ' . kartu($h1) . ')');
cek(halaman($h1) === 2, '21 kartu dipotong jadi 2 halaman @20 (dapat: ' . halaman($h1) . ')');

// ------------------------------------------------------------------ Skenario 2
echo "
== 2. Bongkahan pertama nol cocok tidak menghentikan pengumpulan ==
";
seed('9902', 1, bongkah(BONGKAH, 0, 3000));
seed('9902', 2, bongkah(BONGKAH, 15, 4000));
seed('9902', 3, []);

$n1 = minta('9902', 'subsidi', 1);
cek(kartu($n1) === 15, 'Ke-15 subsidi dari bongkahan KEDUA tetap terkumpul (dapat: ' . kartu($n1) . ')');
cek(halaman($n1) === 1, '15 kartu muat dalam 1 halaman (dapat: ' . halaman($n1) . ')');

// ------------------------------------------------------------------ Skenario 3
echo "
== 3. Saringan memilah, dan 'semua' tidak memilah ==
";
$k1 = minta('9901', 'komersil', 1);
cek(kartu($k1) === 179, 'Non-subsidi: 179 dari 200 (dapat: ' . kartu($k1) . ')');

$s1 = minta('9901', 'semua', 1);
cek(kartu($s1) === 200, '"semua" mengembalikan 200, nol disaring (dapat: ' . kartu($s1) . ')');
cek(kartu($h1) + kartu($k1) === kartu($s1),
    'subsidi + non-subsidi = semua, jadi tidak ada baris yang hilang atau dobel');

preg_match_all('#detail_perum/(\d+)#', $h1, $m_sub);
preg_match_all('#detail_perum/(\d+)#', $k1, $m_kom);
cek($m_sub[1] && $m_kom[1] && ! array_intersect($m_sub[1], $m_kom[1]),
    'Daftar subsidi dan non-subsidi tidak beririsan sama sekali');

// ------------------------------------------------------------------ Skenario 4
/* HTML dari SIKUMBANG harus DI-ESCAPE. Ini bug SUNGGUHAN yang sempat tayang
   13 Agt 2026: kartu dari halaman tersembunyi "bocor" ke halaman yang tampil.
   Sebabnya teks dari API dicetak mentah, sehingga tag di dalamnya menutup
   pembungkus <div class="halaman-data"> lebih awal dan sisa kartunya lepas
   dari penyembunyian CSS.

   Halaman disembunyikan dengan CSS, bukan dibuang dari markup - jadi satu tag
   liar cukup membongkar seluruh paginasi. Nol escape = nol paginasi. */
echo "
== 4. Teks dari SIKUMBANG di-escape ==
";
$jahat = bongkah(3, 3, 5000);
$jahat[0]['namaPerumahan'] = 'Perum </div><b>BOCOR</b><div>';
seed('9903', 1, $jahat);
seed('9903', 2, []);

$x = minta('9903', 'subsidi', 1);
cek(strpos($x, '<b>BOCOR</b>') === FALSE, 'Tag dari nama perumahan TIDAK hidup sebagai HTML');
cek(strpos($x, 'BOCOR') !== FALSE, 'Teksnya tetap tampil, bukan lenyap diam-diam');
cek(halaman($x) === 1, 'Pembungkus halaman tetap utuh, tidak tertutup lebih awal');

// Skenario 5 - gagal jaringan tidak boleh menyamar jadi "data habis".
//
// Pemeriksaan STRUKTURAL, bukan simulasi, dan disebut apa adanya supaya tidak
// dibaca sebagai bukti perilaku: `bongkah_sikumbang()` hanya mengembalikan NULL
// saat curl gagal, dan memalsukan kegagalan curl dari dalam uji lebih rapuh
// daripada hal yang dijaganya.
//
// 🔻 VERSI PERTAMA PENJAGA INI LOLOS DARI MUTASI, dan itu dicatat di sini
// supaya penggantinya tidak mengulang. Dulu ia cuma mencari pola
// `if ($baris === NULL)`. Mutasi yang membuang `$gagal = TRUE` dari dalam
// cabang itu TETAP HIJAU - polanya masih ada, perilakunya sudah rusak.
// Sekarang yang diperiksa adalah IKATANNYA: cabang NULL wajib menyalakan
// penanda gagal, dan kedua metode wajib membacanya.
// ---------------------------------------------------------------------------
echo "\n== 5. Penanda gagal jaringan (pemeriksaan struktural) ==\n";
$ctrl = (string) @file_get_contents(APP_ROOT . '/application/controllers/Index.php');
$view = (string) @file_get_contents(APP_ROOT . '/application/views/pages/perumahan/cari_rumah.php');
cek(strpos($ctrl, '<!-- gagal-jaringan -->') !== FALSE,
    'Controller mengirim penanda gagal jaringan');
cek(strpos($view, '<!-- gagal-jaringan -->') !== FALSE,
    'JS mengenali penanda itu dan tidak mematikan tombol');
cek(preg_match('/\$baris\s*===\s*NULL\s*\)\s*\{\s*\$gagal\s*=\s*TRUE\s*;/', $ctrl) === 1,
    'Cabang NULL MENYALAKAN penanda gagal, bukan sekadar berhenti');
cek(substr_count($ctrl, 'if ($gagal && ! $list_final)') === 2,
    'cari_wil() dan load_more() dua-duanya membaca penanda itu');

// ---------------------------------------------------------------------------
// Skenario 6 - keterangan A2 ada di layar dan punya kedua rumusan.
// ---------------------------------------------------------------------------
echo "\n== 6. Keterangan subsidi & non subsidi (butir A2) ==\n";
cek(strpos($view, 'id="ket-status"') !== FALSE, 'Ada tempat keterangan di layar');
cek(preg_match('/keteranganStatus\s*=\s*\{[^}]*subsidi[^}]*komersil/s', $view) === 1,
    'Kedua rumusan tersedia dan berdampingan di satu tempat');
cek(strpos($view, 'aria-live') !== FALSE,
    'Perubahan keterangan terbaca pembaca layar');

echo "\n=== Ringkasan ===\n";
printf("  %d pemeriksaan, %d merah\n", $GLOBALS['uji_total'], $GLOBALS['uji_gagal']);
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
