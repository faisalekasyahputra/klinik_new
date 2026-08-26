<?php
/**
 * Penjaga cadangan cache basi SIKUMBANG.
 *
 *   php docs/engineering/uji_sikumbang_cadangan.php
 *
 * KENAPA BERKAS INI ADA. Dinas melaporkan dua hal yang kelihatannya terpisah:
 * "hostinger selalu mati" dan "API Sikumbang tidak bisa load". Diselidiki
 * 26 Agt 2026, keduanya berakar pada satu kekurangan yang sama di kode kita,
 * BUKAN di SIKUMBANG - hulunya diukur sehat hari itu dari lokal (1,48 dtk)
 * maupun dari server production (1,75 dtk).
 *
 * Pola lama, disalin di tujuh tempat:
 *
 *     if (cache masih segar) pakai cache;
 *     else { curl; if (berhasil) tulis cache; }
 *     if ($response) tampilkan; else tampilkan KOSONG;
 *
 * Kalau cache lewat TTL DAN curl gagal, berkas cache yang masih bagus di disk
 * DIABAIKAN dan halaman render kosong. Itu gejala "tidak bisa load". Dan
 * karena kegagalan tidak pernah dicatat, setiap permintaan berikutnya
 * mengulang tembakan dan membayar timeout penuh lagi - itu yang menahan
 * worker PHP sampai puluhan detik dan membuat situs terlihat mati.
 *
 * Yang diuji di sini KODE KITA, bukan ketersediaan API orang lain. Kegagalan
 * jaringan ditiru dengan alamat yang dijamin tidak bisa dihubungi, bukan
 * dengan menunggu SIKUMBANG kebetulan mati - uji yang merah karena hulunya
 * sedang mati adalah uji yang tidak berguna.
 *
 * SENGAJA TIDAK MEMAKAI FRAMEWORK: repo ini tidak punya tests/, dan penjaga
 * lain di folder ini (uji_cari_rumah_sikumbang.php dst) memakai pola yang
 * sama - assert telanjang, keluar dengan kode 1 kalau ada yang merah.
 */

define('BASEPATH', TRUE);   // helper menolak diakses langsung tanpa ini
require dirname(__DIR__, 2) . '/application/helpers/sikumbang_helper.php';

$GLOBALS['total'] = 0;
$GLOBALS['gagal'] = 0;
$GLOBALS['bersih'] = [];

function cek($benar, $label) {
    $GLOBALS['total']++;
    echo ($benar ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $benar) { $GLOBALS['gagal']++; }
    return (bool) $benar;
}
function bersihkan() {
    foreach ($GLOBALS['bersih'] as $f) { @unlink($f); }
}
register_shutdown_function('bersihkan');

$dir = sys_get_temp_dir() . '/uji_sikumbang_' . getmypid();
@mkdir($dir, 0777, TRUE);
register_shutdown_function(function () use ($dir) { @rmdir($dir); });

$cache   = $dir . '/bongkah.json';
$bendera = $dir . '/sikumbang_gagal.flag';
$GLOBALS['bersih'][] = $cache;
$GLOBALS['bersih'][] = $bendera;

/* Alamat yang DIJAMIN gagal cepat tanpa menyentuh internet: port 1 di
   loopback tidak pernah mendengarkan. Dipakai supaya uji ini deterministik
   dan tidak ikut lambat kalau jaringan mesin penguji sedang buruk. */
const URL_MATI  = 'http://127.0.0.1:1/mati';
const URL_HIDUP = 'https://sikumbang.tapera.go.id/ajax/lokasi/search?limit=1';

/**
 * ⚠️ `clearstatcache()` di sini WAJIB, bukan kehati-hatian berlebihan.
 * Tanpa itu skenario 2 LULUS PALSU: PHP menyimpan hasil stat per-proses, jadi
 * `filemtime()` di dalam helper masih melaporkan umur dari pemanggilan
 * SEBELUMNYA (terukur: 10 detik padahal berkasnya baru saja di-`touch` ke
 * 7200 detik). Cache dikira masih segar, helper pulang lebih awal, jalur
 * kegagalan TIDAK PERNAH dijalankan - dan assert isi tetap hijau karena isi
 * yang dikembalikan kebetulan sama. Yang membongkarnya cuma assert bendera.
 * Di production ini tidak pernah jadi masalah: tiap permintaan HTTP proses
 * PHP-nya baru, jadi tidak ada stat yang terbawa. Ini murni jebakan harness.
 */
function tulis_cache($f, $isi, $umur_detik) {
    file_put_contents($f, $isi);
    touch($f, time() - $umur_detik);
    clearstatcache(TRUE, $f);
}

echo "=== UJI CADANGAN CACHE BASI SIKUMBANG ===\n\n";

// ---------------------------------------------------------------------------
echo "1. Cache masih segar: dipakai, jaringan TIDAK disentuh\n";
// ---------------------------------------------------------------------------
@unlink($bendera);
tulis_cache($cache, '{"data":["segar"]}', 10);
$mulai = microtime(TRUE);
$hasil = sikumbang_ambil(URL_MATI, $cache, 3600, 5);
$lama  = microtime(TRUE) - $mulai;
cek($hasil === '{"data":["segar"]}', 'isi cache segar dikembalikan apa adanya');
cek($lama < 1.0, sprintf('tidak menembak jaringan (%.3f dtk)', $lama));

// ---------------------------------------------------------------------------
echo "\n2. INTI PERBAIKAN - cache basi + jaringan gagal: cadangan dipakai\n";
// ---------------------------------------------------------------------------
@unlink($bendera);
tulis_cache($cache, '{"data":["basi tapi berguna"]}', 7200);   // lewat TTL 3600
$hasil = sikumbang_ambil(URL_MATI, $cache, 3600, 5);
cek($hasil === '{"data":["basi tapi berguna"]}',
    'isi basi dikembalikan, BUKAN NULL (inilah bug halaman kosong)');
cek(file_exists($bendera), 'kegagalan dicatat di bendera untuk menahan tembakan berikutnya');

// ---------------------------------------------------------------------------
echo "\n3. Bendera menyala: tembakan berikutnya dilewati, langsung cadangan\n";
// ---------------------------------------------------------------------------
$mulai = microtime(TRUE);
$hasil = sikumbang_ambil(URL_MATI, $cache, 3600, 5);
$lama  = microtime(TRUE) - $mulai;
cek($hasil === '{"data":["basi tapi berguna"]}', 'tetap menyajikan cadangan');
cek($lama < 0.5, sprintf('nol percobaan jaringan selama bendera menyala (%.3f dtk)', $lama));

// ---------------------------------------------------------------------------
echo "\n4. Tanpa cache sama sekali + jaringan gagal: NULL, bukan tebakan\n";
// ---------------------------------------------------------------------------
@unlink($bendera);
@unlink($cache);
$hasil = sikumbang_ambil(URL_MATI, $cache, 3600, 5);
cek($hasil === NULL, 'NULL supaya pemanggil bisa membedakan gagal dari kosong');
cek( ! file_exists($cache), 'tidak menulis cache dari kegagalan');

// ---------------------------------------------------------------------------
echo "\n5. Bendera menyala tapi cache tidak ada: NULL cepat, tanpa menembak\n";
// ---------------------------------------------------------------------------
touch($bendera);
$mulai = microtime(TRUE);
$hasil = sikumbang_ambil(URL_MATI, $cache, 3600, 5);
$lama  = microtime(TRUE) - $mulai;
cek($hasil === NULL, 'NULL');
cek($lama < 0.5, sprintf('tidak menembak (%.3f dtk)', $lama));

// ---------------------------------------------------------------------------
echo "\n6. Bendera kedaluwarsa: boleh mencoba jaringan lagi\n";
// ---------------------------------------------------------------------------
tulis_cache($cache, '{"data":["basi"]}', 7200);
touch($bendera, time() - (SIKUMBANG_JEDA_GAGAL + 5));
clearstatcache(TRUE, $bendera);
sikumbang_ambil(URL_MATI, $cache, 3600, 5);
clearstatcache(TRUE, $bendera);
cek(filemtime($bendera) > time() - 5,
    'bendera disegarkan sesudah percobaan baru yang juga gagal');

// ---------------------------------------------------------------------------
echo "\n7. Jaringan sehat: cache ditulis, bendera dipadamkan\n";
// ---------------------------------------------------------------------------
@unlink($cache);
touch($bendera);
$hasil = sikumbang_ambil(URL_HIDUP, $cache, 3600, 20);
if ($hasil === NULL) {
    /* Sebab yang PALING SERING di sini bukan SIKUMBANG mati, melainkan XAMPP
       lokal tanpa CA bundle: `curl.cainfo` kosong, jadi SSL_VERIFYPEER=TRUE
       selalu gagal dengan "SSL peer certificate ... was not OK" di mesin
       pengembang sementara production baik-baik saja (diverifikasi lewat PHP
       curl DI SERVER, 26 Agt 2026: HTTP 200, 23012 byte). Sebabnya dicetak
       apa adanya supaya tidak ada yang menyimpulkan hulunya mati padahal
       yang kurang cuma sertifikat di mesinnya sendiri.
       JANGAN mematikan SSL_VERIFYPEER untuk membuat baris ini hijau. */
    $ch = curl_init(URL_HIDUP);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_TIMEOUT => 20]);
    curl_exec($ch);
    $sebab = curl_error($ch) ?: 'tidak diketahui';
    curl_close($ch);
    echo "  LEWAT Skenario 7 dilewati - ia satu-satunya yang butuh jaringan sungguhan.\n";
    echo "        Sebab: {$sebab}\n";
    echo "        Kalau soal sertifikat, itu mesin ini, bukan SIKUMBANG dan bukan kode.\n";
} else {
    cek(file_exists($cache) && file_get_contents($cache) === $hasil, 'cache ditulis dari balasan sukses');
    cek( ! file_exists($bendera), 'bendera dipadamkan setelah sukses');
}

// ---------------------------------------------------------------------------
echo "\n=== {$GLOBALS['total']} pemeriksaan, {$GLOBALS['gagal']} merah ===\n";
exit($GLOBALS['gagal'] === 0 ? 0 : 1);
