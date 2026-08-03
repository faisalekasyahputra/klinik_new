<?php
/**
 * Check konsistensi migrasi — butir S8 roadmap pelunasan utang teknis.
 *
 *   php docs/engineering/uji_migrasi_konsisten.php
 *
 * SENGAJA tidak mem-bootstrap CodeIgniter dan tidak menyentuh database:
 * seluruh pemeriksaannya statis atas berkas dan konfigurasi, jadi ia bisa
 * dijalankan di worktree mana pun — termasuk yang belum punya `.env`.
 *
 * Yang dijaga:
 *  1. `migration_version` = nomor tertinggi seluruh berkas migrasi. Kalau ia
 *     tertinggal, `$this->migration->current()` akan MENURUNKAN skema ke versi
 *     lama — dan `down()` migrasi ini menghapus tabel.
 *  2. Nol pemanggil `migration->current()` di kode. `latest()` yang dipakai
 *     `Migrate::index()` mengabaikan `migration_version`, jadi selama nol
 *     pemanggil, nilai yang tertinggal "hanya" bom waktu, bukan kerusakan
 *     aktif. Check ini menjaga keduanya sekaligus.
 *  3. Nol berkas migrasi liar (ada di disk tapi tidak dilacak git). Berkas
 *     liar di server membuat `latest()` menargetkan nomor yang tidak ada di
 *     commit rilis (AGENTS.md §0e).
 *  4. Setiap tabel yang DIBUAT sebuah migrasi disebut di `Migrate::status()`.
 *     Itu satu-satunya cara membaca keadaan skema production, dan ia daftar
 *     yang ditulis tangan — jadi ia membusuk diam-diam. Terbukti: `status()`
 *     berhenti di migrasi 031 sementara skemanya sudah 034, dan tak ada yang
 *     tahu sampai keluarannya dibaca baris demi baris (4 Agt 2026).
 */

$root = dirname(__DIR__, 2);
$total = 0;
$gagal = 0;

function cek($kondisi, $label) {
    global $total, $gagal;
    $total++;
    echo ($kondisi ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $kondisi) {
        $gagal++;
    }
    return (bool) $kondisi;
}

echo "Check konsistensi migrasi (S8)\n";

// ------------------------------------------------- 1. versi vs berkas

$configPath = $root . '/application/config/migration.php';
$config = file_get_contents($configPath);
preg_match("/\\\$config\['migration_version'\]\s*=\s*'?(\d+)'?/", $config, $m);
$versiConfig = isset($m[1]) ? $m[1] : NULL;
cek($versiConfig !== NULL, 'migration_version terbaca dari config');

$berkas = glob($root . '/application/migrations/*.php');
$nomor = [];
foreach ($berkas as $f) {
    if (preg_match('/^(\d+)_/', basename($f), $mm)) {
        $nomor[] = $mm[1];
    }
}
sort($nomor, SORT_STRING);
$tertinggi = $nomor ? end($nomor) : NULL;

cek($tertinggi !== NULL, 'Ada berkas migrasi bernomor di application/migrations/');
cek((string) $versiConfig === (string) $tertinggi,
    "migration_version ({$versiConfig}) sama dengan migrasi tertinggi ({$tertinggi})");

// Penomoran timestamp harus 14 digit; nomor pendek akan diurutkan salah oleh
// perbandingan string dan membuat "tertinggi" meleset tanpa suara.
$panjangSalah = array_filter($nomor, static function ($n) {
    return strlen($n) !== 14;
});
cek($panjangSalah === [], 'Seluruh nomor migrasi 14 digit (tipe timestamp)');

// ------------------------------------------- 2. nol pemanggil current()

$pemanggil = [];
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root . '/application', RecursiveDirectoryIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if ($file->getExtension() !== 'php') {
        continue;
    }
    $isi = file_get_contents($file->getPathname());
    // Komentar di config/migration.php menyebut current() sebagai dokumentasi,
    // bukan pemanggilan. Yang dicari pemanggilan sungguhan.
    foreach (explode("\n", $isi) as $no => $baris) {
        $bersih = trim($baris);
        if ($bersih === '' || $bersih[0] === '|' || $bersih[0] === '*'
            || strpos($bersih, '//') === 0 || strpos($bersih, '#') === 0) {
            continue;
        }
        if (preg_match('/migration\s*->\s*current\s*\(/', $baris)) {
            $pemanggil[] = str_replace($root . DIRECTORY_SEPARATOR, '', $file->getPathname())
                . ':' . ($no + 1);
        }
    }
}
cek($pemanggil === [], 'Nol pemanggil migration->current()'
    . ($pemanggil ? ' — ditemukan: ' . implode(', ', $pemanggil) : ''));

// ------------------------------------------------- 3. nol berkas liar

// `shell_exec()` mengembalikan NULL saat perintahnya tidak menghasilkan output
// SAMA SEKALI — dan `git status --porcelain` pada direktori bersih memang diam.
// Menyamakan NULL dengan "git tidak tersedia" membuat check ini melewati
// pemeriksaannya justru pada keadaan yang paling sering: bersih. Karena itu
// ketersediaan git diuji dengan perintah yang SELALU mencetak sesuatu.
$gitHidup = trim((string) @shell_exec(
    'git -C ' . escapeshellarg($root) . ' rev-parse --is-inside-work-tree 2>&1')) === 'true';

if ( ! $gitHidup) {
    echo "  LEWAT git tidak tersedia — pemeriksaan berkas liar dilewati\n";
} else {
    $git = (string) @shell_exec(
        'git -C ' . escapeshellarg($root) . ' status --porcelain -- application/migrations 2>&1');
    $liar = array_values(array_filter(explode("\n", trim($git))));
    cek($liar === [], 'Nol berkas migrasi untracked/termodifikasi'
        . ($liar ? ' — ' . implode(' ; ', $liar) : ''));
}

// -------------------------------- 4. status() menyebut tiap tabel baru

/**
 * `Migrate::status()` adalah satu-satunya cara membaca keadaan skema server,
 * dan isinya daftar yang ditulis TANGAN. Daftar tulis-tangan yang tidak dijaga
 * akan tertinggal — dan kalau tertinggal, keluarannya tetap terlihat lengkap:
 * semua yang disebutkan berkata ADA, dan yang TIDAK disebutkan tidak
 * meninggalkan jejak apa pun bahwa ia dilewati. Terbukti 4 Agt 2026: `status()`
 * berhenti di 031 sementara skemanya sudah 034, tanpa satu pun tanda.
 *
 * Yang dijaga: NOMOR migrasi terbaru harus disebut di `Migrate.php`. Bukan nama
 * tabelnya.
 *
 * Versi pertama penjaga ini memang mencocokkan nama tabel, dan salah dua kali
 * sekaligus. (a) Positif palsu: migrasi 019 me-RENAME lima tabel `sf_*` lewat
 * peta `const`, jadi nama lamanya memang TIDAK BOLEH ada di `status()` — tapi
 * "pernah dibuat" tetap terbaca dari `CREATE TABLE`-nya. (b) Negatif palsu:
 * grep-nya menyapu SELURUH `Migrate.php`, termasuk method `uji_*`, sehingga
 * tabel yang kebetulan disebut satu uji terhitung "sudah tercakup" padahal
 * `status()` tidak pernah menyentuhnya.
 *
 * Nomor migrasi tidak punya dua masalah itu, dan menangkap satu kelas lagi yang
 * nama tabel tidak bisa: migrasi yang mengubah BENTUK kolom yang sudah ada
 * (034 membuat `aduan.bidang` NULL-able) — ia tidak melahirkan nama tabel baru
 * untuk dicari, tapi justru itu yang paling perlu diverifikasi, karena
 * `field_exists()` akan menjawab ADA baik migrasinya jalan maupun tidak.
 */
$migrateIsi = file_get_contents($root . '/application/controllers/Migrate.php');
$tigaDigit = substr((string) $tertinggi, -3);
$disebut = (bool) preg_match('/migrasi[^\n]{0,24}\b0*' . preg_quote($tigaDigit, '/') . '\b/i', $migrateIsi);
cek($disebut, "Migrate::status() menyebut migrasi terbaru ({$tigaDigit}) — "
    . 'tambahkan pemeriksaan skemanya, jangan cuma menaikkan migration_version');

echo "RINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal > 0 ? 1 : 0);
