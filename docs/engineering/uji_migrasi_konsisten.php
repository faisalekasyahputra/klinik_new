<?php
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Check konsistensi migrasi - butir S8 roadmap pelunasan utang teknis.
 *
 *   php docs/engineering/uji_migrasi_konsisten.php
 *
 * SENGAJA tidak mem-bootstrap CodeIgniter. Pemeriksaan 1-5 statis atas berkas
 * dan konfigurasi, jadi bisa dijalankan di worktree mana pun - termasuk yang
 * belum punya `.env`. Hanya pemeriksaan 6 membaca information_schema (SELECT
 * saja) dan LEWAT kalau DB tidak tersedia.
 *
 * Yang dijaga:
 *  1. `migration_version` = nomor tertinggi seluruh berkas migrasi. Kalau ia
 *     tertinggal, `$this->migration->current()` akan MENURUNKAN skema ke versi
 *     lama - dan `down()` migrasi ini menghapus tabel.
 *  2. Nol pemanggil `migration->current()` di kode. `latest()` yang dipakai
 *     `Migrate::index()` mengabaikan `migration_version`, jadi selama nol
 *     pemanggil, nilai yang tertinggal "hanya" bom waktu, bukan kerusakan
 *     aktif. Check ini menjaga keduanya sekaligus.
 *  3. Nol berkas migrasi liar (ada di disk tapi tidak dilacak git). Berkas
 *     liar di server membuat `latest()` menargetkan nomor yang tidak ada di
 *     commit rilis (AGENTS.md §0e).
 *  4. Setiap tabel yang DIBUAT sebuah migrasi disebut di `Migrate::status()`.
 *     Itu satu-satunya cara membaca keadaan skema production, dan ia daftar
 *     yang ditulis tangan - jadi ia membusuk diam-diam. Terbukti: `status()`
 *     berhenti di migrasi 031 sementara skemanya sudah 034, dan tak ada yang
 *     tahu sampai keluarannya dibaca baris demi baris (4 Agt 2026).
 *  5. Charset/collation eksplisit: config koneksi utf8mb4_unicode_ci dan migrasi
 *     sesudah 068 tidak menulis collation lain (sumber drift 11.8 vs 10.4).
 *  6. Live: information_schema DB aplikasi hanya berisi utf8mb4_unicode_ci
 *     (kolom ascii_bin migrasi 052/053 dikecualikan dengan sengaja).
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
    . ($pemanggil ? ' - ditemukan: ' . implode(', ', $pemanggil) : ''));

// ------------------------------------------------- 3. nol berkas liar

// `shell_exec()` mengembalikan NULL saat perintahnya tidak menghasilkan output
// SAMA SEKALI - dan `git status --porcelain` pada direktori bersih memang diam.
// Menyamakan NULL dengan "git tidak tersedia" membuat check ini melewati
// pemeriksaannya justru pada keadaan yang paling sering: bersih. Karena itu
// ketersediaan git diuji dengan perintah yang SELALU mencetak sesuatu.
$gitHidup = trim((string) @shell_exec(
    'git -C ' . escapeshellarg($root) . ' rev-parse --is-inside-work-tree 2>&1')) === 'true';

if ( ! $gitHidup) {
    echo "  LEWAT git tidak tersedia - pemeriksaan berkas liar dilewati\n";
} else {
    $git = (string) @shell_exec(
        'git -C ' . escapeshellarg($root) . ' status --porcelain -- application/migrations 2>&1');
    $liar = array_values(array_filter(explode("\n", trim($git))));
    cek($liar === [], 'Nol berkas migrasi untracked/termodifikasi'
        . ($liar ? ' - ' . implode(' ; ', $liar) : ''));
}

// -------------------------------- 4. status() menyebut tiap tabel baru

/**
 * `Migrate::status()` adalah satu-satunya cara membaca keadaan skema server,
 * dan isinya daftar yang ditulis TANGAN. Daftar tulis-tangan yang tidak dijaga
 * akan tertinggal - dan kalau tertinggal, keluarannya tetap terlihat lengkap:
 * semua yang disebutkan berkata ADA, dan yang TIDAK disebutkan tidak
 * meninggalkan jejak apa pun bahwa ia dilewati. Terbukti 4 Agt 2026: `status()`
 * berhenti di 031 sementara skemanya sudah 034, tanpa satu pun tanda.
 *
 * Yang dijaga: NOMOR migrasi terbaru harus disebut di `Migrate.php`. Bukan nama
 * tabelnya.
 *
 * Versi pertama penjaga ini memang mencocokkan nama tabel, dan salah dua kali
 * sekaligus. (a) Positif palsu: migrasi 019 me-RENAME lima tabel `sf_*` lewat
 * peta `const`, jadi nama lamanya memang TIDAK BOLEH ada di `status()` - tapi
 * "pernah dibuat" tetap terbaca dari `CREATE TABLE`-nya. (b) Negatif palsu:
 * grep-nya menyapu SELURUH `Migrate.php`, termasuk method `uji_*`, sehingga
 * tabel yang kebetulan disebut satu uji terhitung "sudah tercakup" padahal
 * `status()` tidak pernah menyentuhnya.
 *
 * Nomor migrasi tidak punya dua masalah itu, dan menangkap satu kelas lagi yang
 * nama tabel tidak bisa: migrasi yang mengubah BENTUK kolom yang sudah ada
 * (034 membuat `aduan.bidang` NULL-able) - ia tidak melahirkan nama tabel baru
 * untuk dicari, tapi justru itu yang paling perlu diverifikasi, karena
 * `field_exists()` akan menjawab ADA baik migrasinya jalan maupun tidak.
 */
$migrateIsi = file_get_contents($root . '/application/controllers/Migrate.php');
$tigaDigit = substr((string) $tertinggi, -3);
$disebut = (bool) preg_match('/migrasi[^\n]{0,24}\b0*' . preg_quote($tigaDigit, '/') . '\b/i', $migrateIsi);
cek($disebut, "Migrate::status() menyebut migrasi terbaru ({$tigaDigit}) - "
    . 'tambahkan pemeriksaan skemanya, jangan cuma menaikkan migration_version');

// ------------------------- 5. charset/collation eksplisit (migrasi 068)

/**
 * Asal 14 tabel uca1400 di production: CREATE TABLE tanpa COLLATE di 11.8 mengambil
 * `character_set_collations` server (utf8mb4 -> utf8mb4_uca1400_ai_ci), collation yang tidak
 * dikenal 10.4 lokal. Yang dijaga: (a) config koneksi = utf8mb4/utf8mb4_unicode_ci, karena
 * dbforge->create_table() menulis COLLATE dari `dbcollat`; (b) migrasi SESUDAH 068 yang
 * menulis CREATE TABLE mentah wajib menyebut utf8mb4_unicode_ci, dan tiap COLLATE/CHARSET yang
 * ditulis hanya boleh target atau ascii(_bin). ADD/MODIFY kolom tanpa klausa mewarisi default
 * tabel, yang sesudah 068 sudah target.
 */
$dbConfig = file_get_contents($root . '/application/config/database.php');
cek(preg_match("/'char_set'\s*=>\s*'utf8mb4'/", $dbConfig) && preg_match("/'dbcollat'\s*=>\s*'utf8mb4_unicode_ci'/", $dbConfig),
    "config/database.php: char_set 'utf8mb4' dan dbcollat 'utf8mb4_unicode_ci'");
$menyimpang = [];
foreach ($berkas as $f) {
    if ( ! preg_match('/^(\d+)_/', basename($f), $mm) || strcmp($mm[1], '20260701000068') <= 0) { continue; }
    $isi = file_get_contents($f);
    if (preg_match('/CREATE\s+TABLE/i', $isi) && stripos($isi, 'utf8mb4_unicode_ci') === FALSE) {
        $menyimpang[] = basename($f) . ' (CREATE TABLE tanpa utf8mb4_unicode_ci)';
    }
    preg_match_all('/\b(?:COLLATE|CHARSET|CHARACTER\s+SET)\s*=?\s*[\'"]?(\w+)/i', $isi, $kk);
    foreach (array_unique($kk[1]) as $nilai) {
        if ( ! in_array(strtolower($nilai), ['utf8mb4', 'utf8mb4_unicode_ci', 'ascii', 'ascii_bin'], TRUE)) {
            $menyimpang[] = basename($f) . " ({$nilai})";
        }
    }
}
cek($menyimpang === [], 'Migrasi sesudah 068 hanya menulis charset/collation target'
    . ($menyimpang ? ' - ' . implode(' ; ', $menyimpang) : ''));

// --------------------- 6. live: information_schema DB aplikasi (baca saja)

// Satu-satunya bagian yang menyentuh DB, dan hanya SELECT information_schema. Tanpa .env atau
// tanpa server, LEWAT - supaya bagian statis di atas tetap bisa dijalankan di worktree mana pun.
$env = [];
foreach (@file($root . '/.env', FILE_IGNORE_NEW_LINES) ?: [] as $b) {
    $b = trim($b);
    if ($b === '' || $b[0] === '#' || strpos($b, '=') === FALSE) { continue; }
    [$k, $v] = explode('=', $b, 2);
    if ( ! isset($env[trim($k)])) { $env[trim($k)] = trim($v); }
}
mysqli_report(MYSQLI_REPORT_OFF);
$db = empty($env['DB_NAME']) ? NULL : @new mysqli($env['DB_HOST'] ?? 'localhost', $env['DB_USER'] ?? 'root', $env['DB_PASS'] ?? '', $env['DB_NAME']);
if ( ! $db || $db->connect_error) {
    echo "  LEWAT DB tidak tersedia - pemeriksaan collation live dilewati\n";
} else {
    $r = $db->query("SELECT
        (SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'
            AND TABLE_COLLATION <> 'utf8mb4_unicode_ci') t,
        (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND COLLATION_NAME IS NOT NULL
            AND CHARACTER_SET_NAME <> 'ascii' AND COLLATION_NAME <> 'utf8mb4_unicode_ci') k")->fetch_assoc();
    cek((int) $r['t'] === 0, "Live: semua tabel utf8mb4_unicode_ci ({$r['t']} menyimpang)");
    cek((int) $r['k'] === 0, "Live: semua kolom string non-ascii utf8mb4_unicode_ci ({$r['k']} menyimpang)");
    $db->close();
}

echo "RINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal > 0 ? 1 : 0);
