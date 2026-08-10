<?php
/**
 * Penjaga pencarian rumah SIKUMBANG — butir A3 revisi dinas.
 *
 *   php docs/engineering/uji_cari_rumah_sikumbang.php
 *
 * KENAPA BERKAS INI ADA. Dinas melaporkan "kayaknya belum ke-load semua,
 * muncul cuma 3 atau 6 gambar saja". Jawaban kami yang pertama KELIRU: kami
 * menulis "angka 3 dan 6 tidak cocok dengan pengaturan mana pun, jadi
 * kemungkinan datanya memang sedikit". Ternyata cocok persis.
 *
 * Penyaring subsidi/non-subsidi berjalan di sisi kita, sementara API diminta
 * `limit=9` — jadi sembilan baris itulah yang disaring, dan yang tersisa 1–8
 * buah. Terukur 10 Agt 2026 di Kota Semarang (wilayah bawaan halaman): API
 * mengirim 9, yang lolos saringan subsidi hanya SATU. Halaman 3 malah nol,
 * dan `load_more()` membaca balasan kosong sebagai "Semua Data Telah Dimuat"
 * sehingga daftarnya mati padahal halaman 4 masih berisi.
 *
 * TIDAK MENYENTUH JARINGAN. `Index::bongkah_sikumbang()` membaca cache berkas
 * lebih dulu, jadi cache-nya kita isi sendiri dengan data uji. Itu sekaligus
 * membuat penjaga ini menguji KODE KITA, bukan ketersediaan API orang lain —
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
 * penjaga ini akan diam-diam menembak jaringan sungguhan — hijau yang salah.
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
// Skenario 1 — "Semarang": bongkahan gemuk, yang cocok sedikit.
// 100 baris hanya 1 subsidi, lalu 100 baris 20 subsidi, lalu habis.
// Total subsidi = 21. Sebelum perbaikan, halaman 1 memberi SATU kartu.
// ---------------------------------------------------------------------------
echo "== 1. Yang cocok sedikit di antara yang banyak ==\n";
seed('9901', 1, bongkah(BONGKAH, 1, 1000));
seed('9901', 2, bongkah(BONGKAH, 20, 2000));
seed('9901', 3, []);

$h1 = minta('9901', 'subsidi', 1);
cek(kartu($h1) === 9, 'Halaman 1 penuh 9 kartu, bukan sisa saringan (dapat: ' . kartu($h1) . ')');

$h2 = minta('9901', 'subsidi', 2, 'load_more');
cek(kartu($h2) === 9, 'Halaman 2 penuh 9 kartu (dapat: ' . kartu($h2) . ')');

$h3 = minta('9901', 'subsidi', 3, 'load_more');
cek(kartu($h3) === 3, 'Halaman 3 memberi sisa 3 kartu (21 total) (dapat: ' . kartu($h3) . ')');

$h4 = minta('9901', 'subsidi', 4, 'load_more');
cek(trim($h4) === '', 'Halaman 4 kosong — "habis" diucapkan hanya saat memang habis');

// ---------------------------------------------------------------------------
// Skenario 2 — bongkahan pertama NOL yang cocok.
// Inilah yang dulu mematikan tombol "Muat Lebih Banyak" secara permanen.
// ---------------------------------------------------------------------------
echo "\n== 2. Bongkahan pertama nol cocok tidak boleh menghentikan daftar ==\n";
seed('9902', 1, bongkah(BONGKAH, 0, 3000));
seed('9902', 2, bongkah(BONGKAH, 15, 4000));
seed('9902', 3, []);

$n1 = minta('9902', 'subsidi', 1);
cek(kartu($n1) === 9, 'Halaman 1 tetap berisi 9 walau bongkahan pertama nol cocok (dapat: ' . kartu($n1) . ')');

$n2 = minta('9902', 'subsidi', 2, 'load_more');
cek(kartu($n2) === 6, 'Halaman 2 memberi sisa 6 dari 15 (dapat: ' . kartu($n2) . ')');

// ---------------------------------------------------------------------------
// Skenario 3 — saringan benar-benar menyaring, bukan cuma melewatkan.
// ---------------------------------------------------------------------------
echo "\n== 3. Saringan memilah, dan 'semua' tidak memilah ==\n";
$k1 = minta('9901', 'komersil', 1);
cek(kartu($k1) === 9, 'Non-subsidi juga penuh 9 (dapat: ' . kartu($k1) . ')');

$s1 = minta('9901', 'semua', 1);
cek(kartu($s1) === 9, '"semua" memberi 9 (dapat: ' . kartu($s1) . ')');

/* Kartu subsidi dan non-subsidi harus benar-benar BERBEDA isinya. Tanpa cek
   ini, saringan yang meloloskan segalanya tetap lulus ketiga asersi di atas —
   dan itu persis bug toggle lama yang pernah kami perbaiki. */
preg_match_all('#detail_perum/(\d+)#', $h1, $m_sub);
preg_match_all('#detail_perum/(\d+)#', $k1, $m_kom);
cek($m_sub[1] && $m_kom[1] && ! array_intersect($m_sub[1], $m_kom[1]),
    'Daftar subsidi dan non-subsidi tidak beririsan sama sekali');

// ---------------------------------------------------------------------------
// Skenario 4 — ukuran halaman dijepit.
// ---------------------------------------------------------------------------
echo "\n== 4. Ukuran halaman dijepit ==\n";
$besar = minta('9901', 'semua', 1, 'cari_wil', 999);
cek(kartu($besar) <= 50, 'limit=999 dijepit ke maksimal 50 (dapat: ' . kartu($besar) . ')');

$nol = minta('9901', 'semua', 1, 'cari_wil', 0);
cek(kartu($nol) >= 1, 'limit=0 tidak menghasilkan halaman kosong (dapat: ' . kartu($nol) . ')');

// ---------------------------------------------------------------------------
// Skenario 5 — gagal jaringan tidak boleh menyamar jadi "data habis".
//
// Pemeriksaan STRUKTURAL, bukan simulasi, dan disebut apa adanya supaya tidak
// dibaca sebagai bukti perilaku: `bongkah_sikumbang()` hanya mengembalikan NULL
// saat curl gagal, dan memalsukan kegagalan curl dari dalam uji lebih rapuh
// daripada hal yang dijaganya.
//
// 🔻 VERSI PERTAMA PENJAGA INI LOLOS DARI MUTASI, dan itu dicatat di sini
// supaya penggantinya tidak mengulang. Dulu ia cuma mencari pola
// `if ($baris === NULL)`. Mutasi yang membuang `$gagal = TRUE` dari dalam
// cabang itu TETAP HIJAU — polanya masih ada, perilakunya sudah rusak.
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
// Skenario 6 — keterangan A2 ada di layar dan punya kedua rumusan.
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
