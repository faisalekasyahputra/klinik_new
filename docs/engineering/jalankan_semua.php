<?php
/**
 * Runner seluruh harness: satu perintah, semua peran, semua skenario.
 *
 *   php docs/engineering/jalankan_semua.php
 *   php docs/engineering/jalankan_semua.php --hanya=kemitraan
 *   php docs/engineering/jalankan_semua.php --peran=admin_bidang
 *   php docs/engineering/jalankan_semua.php --fresh      (ikutkan runner DB bersih)
 *
 * Ini BUKAN framework. Ia cuma menjalankan 24 skrip yang sudah ada apa adanya,
 * satu proses per skrip, dan membaca exit code-nya. Tidak ada satu pun harness
 * lama yang perlu diubah untuk ikut — kontraknya sudah seragam sejak awal:
 * exit 0 hijau, non-nol merah.
 *
 * Yang dilaporkan runner ini TAPI tidak dilaporkan skrip mana pun sendirian:
 *
 *   - Suite yang exit 0 dengan NOL pemeriksaan. Itu bukan hijau, itu bisu —
 *     jebakan §0e "harness yang berbohong". Skrip yang mati sebelum cek()
 *     pertama, atau yang seluruh isinya terlewat karena prasyarat, akan
 *     terlihat sukses kalau yang dibaca cuma exit code.
 *   - Peta peran: peran mana yang tersentuh suite mana. Kolom yang tipis
 *     adalah jawaban jujur atas "sudah semua peran belum?".
 */

const AKAR   = __DIR__;
const PERAN  = ['warga', 'pengembang', 'mahasiswa', 'admin_kabkota', 'admin_bidang', 'admin'];

// Runner DB-bersih menukar .env dan DB — mahal, dan menjalankan ulang suite
// lain di dalamnya. Ikut hanya kalau diminta.
const FRESH  = ['uji_rekam_data_fresh.php', 'uji_warga_fresh_r7.php'];

// Menolak berjalan di DB dev secara sengaja (butuh DB khusus). Exit non-nol-nya
// adalah penolakan, bukan kegagalan — jangan dihitung merah.
const KHUSUS = ['uji_utang_teknis.php' => 'butuh DB berakhiran _utang'];

// Empat harness ini hidup sebagai method Migrate, bukan berkas — tidak akan
// pernah tertangkap glob, dan itulah sebabnya mereka sering terlupa dijalankan.
const MIGRATE = ['uji_warga_r1', 'uji_warga_r2', 'uji_rekam_data_d1', 'uji_wizard_w2'];

// Tanpa ini keempat method Migrate di atas menjawab 404, bukan menjalankan uji:
// masing-masing dibuka dengan `if (ENVIRONMENT === 'production') show_404()`,
// dan CLI tidak pernah melihat `SetEnv CI_ENV` milik Apache (lihat index.php:67).
// 404 yang exit 0 adalah persis "hijau palsu" yang runner ini dibuat untuk cegah.
putenv('CI_ENV=development');

$opsi   = getopt('', ['hanya::', 'peran::', 'fresh', 'diam']);
$hanya  = $opsi['hanya']  ?? '';
$peran  = $opsi['peran']  ?? '';
$fresh  = isset($opsi['fresh']);
$diam   = isset($opsi['diam']);

$suites = [];
foreach (glob(AKAR . '/uji_*.php') as $path) {
    $nama = basename($path);
    if ( ! $fresh && in_array($nama, FRESH, TRUE)) { continue; }
    $suites[$nama] = ['cmd' => 'php ' . escapeshellarg($path), 'isi' => (string) file_get_contents($path)];
}
foreach (MIGRATE as $m) {
    // Dijalankan dari akar aplikasi supaya index.php menemukan system/.
    $suites[$m . ' (migrate)'] = [
        'cmd' => 'php ' . escapeshellarg(dirname(AKAR, 2) . '/index.php') . ' migrate ' . $m,
        'isi' => '',
    ];
}
ksort($suites);

if ($hanya !== '') {
    $suites = array_filter($suites, fn($n) => stripos($n, $hanya) !== FALSE, ARRAY_FILTER_USE_KEY);
}
if ($peran !== '') {
    $suites = array_filter($suites, fn($s) => stripos($s['isi'], $peran) !== FALSE);
}
if ( ! $suites) { fwrite(STDERR, "Tidak ada suite yang cocok.\n"); exit(1); }

echo "Menjalankan " . count($suites) . " suite terhadap " . (getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new') . "\n\n";

$hasil = [];
foreach ($suites as $nama => $s) {
    $mulai = microtime(TRUE);
    $keluaran = [];
    $kode = 0;
    exec($s['cmd'] . ' 2>&1', $keluaran, $kode);
    $teks = implode("\n", $keluaran);

    // Dua format cek() beredar di repo ini: '  OK    x' / '  GAGAL x' dipakai
    // mayoritas, '[OK] x' / '[FAIL] x' dipakai uji_notifikasi.php. Pola yang
    // cuma mengenal satu di antaranya melaporkan suite yang lain sebagai bisu.
    $ok    = preg_match_all('/^(\s*OK\s{2,}|\[OK\]\s)/m', $teks);
    $gagal = preg_match_all('/^(\s*GAGAL\s|\[FAIL\]\s)/m', $teks);
    $lewat = isset(KHUSUS[$nama]);

    $hasil[$nama] = [
        'kode' => $kode, 'ok' => $ok, 'gagal' => $gagal, 'teks' => $teks,
        'detik' => round(microtime(TRUE) - $mulai, 1),
        'lewat' => $lewat,
        'bisu'  => ! $lewat && $kode === 0 && ($ok + $gagal) === 0,
    ];

    $tanda = $lewat ? 'lewat' : ($kode !== 0 ? 'MERAH' : (($ok + $gagal) === 0 ? 'BISU ' : 'hijau'));
    printf("  %-5s %-42s %3d cek  %5.1fs%s\n", $tanda, $nama, $ok + $gagal, $hasil[$nama]['detik'],
        $lewat ? '  (' . KHUSUS[$nama] . ')' : '');

    // Kegagalan dicetak utuh saat itu juga — yang dicari orang setelah runner
    // merah adalah barisnya, bukan nama berkasnya.
    if ($kode !== 0 && ! $lewat && ! $diam) {
        foreach (preg_grep('/^\s*GAGAL\s|Berhenti:|Fatal error|Uncaught/', $keluaran) as $b) {
            echo "           | " . trim($b) . "\n";
        }
    }
}

echo "\n=== Peta peran ===\n";
foreach (PERAN as $p) {
    $sentuh = array_keys(array_filter($suites, fn($s) => stripos($s['isi'], $p) !== FALSE));
    printf("  %-14s %2d suite  %s\n", $p, count($sentuh),
        $sentuh ? implode(', ', array_map(fn($n) => str_replace(['uji_', '.php'], '', $n), $sentuh)) : '— TIDAK TERSENTUH —');
}

$total  = array_sum(array_column($hasil, 'ok')) + array_sum(array_column($hasil, 'gagal'));
$merah  = array_keys(array_filter($hasil, fn($h) => $h['kode'] !== 0 && ! $h['lewat']));
$bisu   = array_keys(array_filter($hasil, fn($h) => $h['bisu']));
$lewat  = array_keys(array_filter($hasil, fn($h) => $h['lewat']));

echo "\n=== Ringkasan ===\n";
echo "  " . count($hasil) . " suite, {$total} pemeriksaan, " . count($merah) . " merah, "
   . count($bisu) . " bisu, " . count($lewat) . " dilewati\n";
foreach ($merah as $n) { echo "  MERAH  {$n}\n"; }
foreach ($bisu as $n)  { echo "  BISU   {$n} — exit 0 tanpa satu pun cek(); perlakukan sebagai merah\n"; }

exit(($merah || $bisu) ? 1 : 0);
