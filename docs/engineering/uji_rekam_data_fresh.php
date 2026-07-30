<?php
/**
 * Runner DB bersih — Rekam Data D1–D6.
 *
 * Membuktikan modul ini jalan di instalasi FRESH, bukan cuma di DB dev yang
 * sudah lama ditambal tangan (pelajaran `omah_sekeng`, AGENTS.md §0e):
 * baseline → migrasi 1→21 → seluruh check D1–D6.
 *
 *   php docs/engineering/uji_rekam_data_fresh.php
 *
 * KENAPA IA MENGALIHKAN `PRIVATE_UPLOADS_PATH`, BUKAN CUMA `DB_NAME`:
 * D3 menulis berkas BNBA ke disk dengan nama folder = id laporan. Id di DB
 * sementara mulai dari kecil dan TABRAKAN dengan id dev, jadi pembersih yang
 * menyapu berdasarkan isi disk bisa memakan berkas dev. Persis ini pernah
 * terjadi: 14 berkas SRP2 dev lenyap (AGENTS.md §0e). Runner yang hanya
 * mengganti `DB_NAME` dinyatakan TIDAK AMAN.
 *
 * BATAS YANG HARUS DISADARI: repo ini punya satu vhost, jadi selama runner
 * berjalan situs dev lokal ikut menunjuk DB sementara. Jangan menjalankannya
 * sambil memakai aplikasi di browser.
 */

$root = dirname(__DIR__, 2);
$envPath = $root . '/.env';
$schemaPath = $root . '/docs/engineering/schema_klinikpkp.sql';
$markerPath = sys_get_temp_dir() . '/uji_rekam_data_fresh.marker.json';

function env_values($path) {
    $out = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === FALSE) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        // Baris pertama menang: .env memuat tiga blok DB_*, yang terakhir
        // adalah kredensial PRODUCTION (AGENTS.md §0e).
        if ( ! array_key_exists($key, $out)) {
            $out[$key] = trim($value);
        }
    }
    return $out;
}

function hapus_rekursif($dir) {
    if ( ! is_dir($dir)) {
        return;
    }
    foreach (array_diff(scandir($dir), ['.', '..']) as $item) {
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        is_dir($path) ? hapus_rekursif($path) : @unlink($path);
    }
    @rmdir($dir);
}

function run_check($root, array $command, array $env) {
    echo "\n> " . implode(' ', array_map(static function ($part) {
        return strpos($part, ' ') === FALSE ? $part : '"' . $part . '"';
    }, $command)) . "\n";
    $proc = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root, $env);
    if ( ! is_resource($proc)) {
        echo "GAGAL menjalankan perintah.\n";
        return 1;
    }
    foreach ([1, 2] as $fd) {
        $out = stream_get_contents($pipes[$fd]);
        fclose($pipes[$fd]);
        if (trim($out) !== '') {
            echo $out;
        }
    }
    return proc_close($proc);
}

// ---------------------------------------------------------------- prasyarat

if ( ! is_file($envPath) || ! is_file($schemaPath)) {
    fwrite(STDERR, ".env atau baseline schema tidak ditemukan.\n");
    exit(1);
}

$config = env_values($envPath);
foreach (['DB_HOST', 'DB_USER', 'DB_NAME'] as $wajib) {
    if ( ! isset($config[$wajib])) {
        fwrite(STDERR, "{$wajib} tidak ada di .env.\n");
        exit(1);
    }
}

// Pagar keras: runner ini tidak boleh menyentuh apa pun selain localhost.
if ( ! in_array(strtolower($config['DB_HOST']), ['localhost', '127.0.0.1', '::1'], TRUE)) {
    fwrite(STDERR, "DB_HOST bukan localhost ({$config['DB_HOST']}) — dibatalkan.\n");
    exit(1);
}

$originalEnv = file_get_contents($envPath);
$hashAsli = hash('sha256', $originalEnv);

// -------------------------------------------------- pulihkan sisa hard-kill

$admin = new mysqli($config['DB_HOST'], $config['DB_USER'], $config['DB_PASS'] ?? '');
if ($admin->connect_error) {
    fwrite(STDERR, "Koneksi DB gagal: {$admin->connect_error}\n");
    exit(1);
}

if (is_file($markerPath)) {
    $sisa = json_decode(file_get_contents($markerPath), TRUE) ?: [];
    echo "Menemukan sisa run sebelumnya (kemungkinan hard-kill), membersihkan:\n";
    if ( ! empty($sisa['database']) && strpos($sisa['database'], 'klinikpkp_uji_rd_') === 0) {
        echo "  - DROP DATABASE {$sisa['database']}: "
            . ($admin->query("DROP DATABASE IF EXISTS `{$sisa['database']}`") ? "OK" : "GAGAL") . "\n";
    }
    if ( ! empty($sisa['uploads']) && strpos(basename($sisa['uploads']), 'uji_rd_uploads_') === 0) {
        hapus_rekursif($sisa['uploads']);
        echo "  - folder unggahan sementara: " . (is_dir($sisa['uploads']) ? 'GAGAL' : 'OK') . "\n";
    }
    if ( ! empty($sisa['env_backup']) && is_file($sisa['env_backup'])) {
        $cadangan = file_get_contents($sisa['env_backup']);
        if (hash('sha256', $cadangan) !== hash('sha256', file_get_contents($envPath))) {
            file_put_contents($envPath, $cadangan, LOCK_EX);
            echo "  - .env dipulihkan dari cadangan run sebelumnya\n";
        }
        @unlink($sisa['env_backup']);
    }
    @unlink($markerPath);
    // Baca ulang: .env mungkin baru saja dipulihkan.
    $originalEnv = file_get_contents($envPath);
    $hashAsli = hash('sha256', $originalEnv);
    $config = env_values($envPath);
}

// ---------------------------------------------------------------- persiapan

$stamp = date('Ymd_His') . '_' . getmypid();
$database = 'klinikpkp_uji_rd_' . $stamp;
$uploads = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'uji_rd_uploads_' . $stamp;
$envBackup = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'uji_rd_env_' . $stamp . '.bak';

echo "=== RUNNER DB BERSIH — REKAM DATA D1-D6 ===\n";
echo "DB sementara      : {$database}\n";
echo "Unggahan sementara: {$uploads}\n";
echo "Baseline          : docs/engineering/schema_klinikpkp.sql\n";

file_put_contents($envBackup, $originalEnv, LOCK_EX);
file_put_contents($markerPath, json_encode([
    'database'   => $database,
    'uploads'    => $uploads,
    'env_backup' => $envBackup,
    'mulai'      => date('c'),
], JSON_PRETTY_PRINT));

$dbDibuat = FALSE;
$envDialihkan = FALSE;

// Jaring pengaman untuk kegagalan biasa (exit/exception). Hard-kill TIDAK
// menjalankan ini — itulah gunanya marker di atas.
register_shutdown_function(static function () use (
    &$dbDibuat, &$envDialihkan, $admin, $database, $envPath, $originalEnv, $uploads
) {
    if ($envDialihkan) {
        file_put_contents($envPath, $originalEnv, LOCK_EX);
        fwrite(STDERR, "\n[shutdown] .env dipulihkan.\n");
    }
    if ($dbDibuat) {
        $admin->query("DROP DATABASE IF EXISTS `{$database}`");
        fwrite(STDERR, "[shutdown] DB sementara dihapus.\n");
    }
    if (is_dir($uploads)) {
        hapus_rekursif($uploads);
        fwrite(STDERR, "[shutdown] folder unggahan sementara dihapus.\n");
    }
});

if ( ! $admin->query("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    fwrite(STDERR, "CREATE DATABASE gagal: {$admin->error}\n");
    exit(1);
}
$dbDibuat = TRUE;
$admin->select_db($database);

$sql = preg_replace('/^\xEF\xBB\xBF/', '', file_get_contents($schemaPath));
if ( ! $admin->multi_query($sql)) {
    fwrite(STDERR, "Import baseline gagal: {$admin->error}\n");
    exit(1);
}
do {
    if ($res = $admin->store_result()) {
        $res->free();
    }
} while ($admin->more_results() && $admin->next_result());
if ($admin->errno) {
    fwrite(STDERR, "Import baseline gagal: {$admin->error}\n");
    exit(1);
}

if ( ! is_dir($uploads) && ! mkdir($uploads, 0700, TRUE)) {
    fwrite(STDERR, "Folder unggahan sementara gagal dibuat.\n");
    exit(1);
}

// --------------------------------------------------- alihkan .env (dua kunci)

$freshEnv = preg_replace('/^(?!\s*#)\s*DB_NAME\s*=.*$/m',
    'DB_NAME=' . $database, $originalEnv, 1, $n1);
$freshEnv = preg_replace('/^(?!\s*#)\s*PRIVATE_UPLOADS_PATH\s*=.*$/m',
    'PRIVATE_UPLOADS_PATH=' . $uploads, $freshEnv, 1, $n2);

if ($n1 !== 1 || $n2 !== 1) {
    fwrite(STDERR, "Gagal mengalihkan .env (DB_NAME={$n1}, PRIVATE_UPLOADS_PATH={$n2}).\n"
        . "Keduanya WAJIB tergantikan; mengalihkan salah satu saja tidak aman.\n");
    exit(1);
}
if (file_put_contents($envPath, $freshEnv, LOCK_EX) === FALSE) {
    fwrite(STDERR, ".env tidak dapat ditulis.\n");
    exit(1);
}
$envDialihkan = TRUE;

$php = PHP_BINARY;
$childEnv = array_merge(getenv(), [
    'DB_HOST' => $config['DB_HOST'],
    'DB_USER' => $config['DB_USER'],
    'DB_PASS' => $config['DB_PASS'] ?? '',
    'DB_NAME' => $database,
    'UJI_BASE_URL' => getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new',
]);

// ------------------------------------------------------------- migrasi dulu

$gagal = 0;
foreach ([[$php, 'index.php', 'migrate'], [$php, 'index.php', 'migrate', 'status']] as $cmd) {
    if (run_check($root, $cmd, $childEnv) !== 0) {
        $gagal++;
        break;
    }
}

$versi = $gagal ? NULL : ($admin->query('SELECT version FROM migrations')->fetch_assoc()['version'] ?? NULL);

// Versi harapan DITURUNKAN dari berkas migrasi paling akhir, tidak diketik.
// Sebelumnya di sini tertulis '20260701000021' — benar saat ditulis, dan sejak
// migrasi 022..024 mendarat, runner ini MERAH pada sistem yang justru benar.
// Angka yang harus cocok dengan sesuatu tidak boleh ditulis tangan: yang
// menambah migrasi berikutnya tidak punya alasan mencari baris ini.
$berkas_migrasi = glob(dirname(__DIR__, 2) . '/application/migrations/*.php');
sort($berkas_migrasi);
$harapan = $berkas_migrasi
    ? substr(basename((string) end($berkas_migrasi)), 0, 14)
    : NULL;

echo "\nVersi skema setelah migrasi: " . var_export($versi, TRUE)
    . " (harapan dari berkas: " . var_export($harapan, TRUE) . ")\n";
if ($harapan === NULL) {
    echo "GAGAL: tidak ada berkas migrasi yang terbaca.\n";
    $gagal++;
} elseif ($versi !== $harapan) {
    echo "GAGAL: versi skema bukan {$harapan}.\n";
    $gagal++;
}

$jumlah_kab = (int) ($admin->query('SELECT COUNT(*) c FROM kabupaten')->fetch_assoc()['c'] ?? 0);
echo "Kabupaten hasil migrasi: {$jumlah_kab}\n";
if ($jumlah_kab < 2) {
    echo "GAGAL: butuh minimal 2 kabupaten untuk uji scope.\n";
    $gagal++;
}

// --------------------------------------------------------- semai akun uji
// Baseline tidak memuat satu pun pengguna, jadi akun demo TIDAK ADA di DB
// fresh. Harness sudah menyediakan UJI_ADMIN_EMAIL/PASSWORD justru untuk ini.

if ( ! $gagal) {
    $kab = (int) $admin->query('SELECT id FROM kabupaten ORDER BY id LIMIT 1')->fetch_assoc()['id'];
    $email = 'uji_rd_fresh@example.test';
    $sandi = 'UjiRdFresh123!';
    $ok = $admin->query(sprintf(
        "INSERT INTO usr_users (email, password, role, kabupaten_id, name, username)
         VALUES ('%s', '%s', 'admin_kabkota', %d, 'Admin Uji Fresh', 'uji_rd_fresh')",
        $admin->real_escape_string($email),
        $admin->real_escape_string(password_hash($sandi, PASSWORD_BCRYPT)),
        $kab));
    if ( ! $ok) {
        echo "GAGAL menyemai akun uji: {$admin->error}\n";
        $gagal++;
    } else {
        echo "Akun uji disemai: {$email} (kabupaten {$kab})\n";
        $childEnv['UJI_ADMIN_EMAIL'] = $email;
        $childEnv['UJI_ADMIN_PASSWORD'] = $sandi;
    }
}

// ------------------------------------------------------------------ check

if ( ! $gagal) {
    // Dua suite wizard IKUT dijalankan di sini. Tanpa mereka runner DB-bersih
    // ini melewatkan justru pintu tulis yang paling banyak berubah (W1/W2), dan
    // "semua hijau di DB bersih" jadi klaim yang lebih besar dari buktinya.
    foreach ([
        [$php, 'index.php', 'migrate', 'uji_rekam_data_d1'],
        [$php, 'index.php', 'migrate', 'uji_wizard_w2'],
        [$php, 'docs/engineering/uji_wizard_rekam_perumahan.php'],
        [$php, 'docs/engineering/uji_rekam_data_d2.php'],
        [$php, 'docs/engineering/uji_rekam_data_d3.php'],
        [$php, 'docs/engineering/uji_rekam_data_d4.php'],
        [$php, 'docs/engineering/uji_rekam_data_d5.php'],
        [$php, 'docs/engineering/uji_rekam_data_d6.php'],
    ] as $cmd) {
        if (run_check($root, $cmd, $childEnv) !== 0) {
            $gagal++;
            echo "GAGAL (rangkaian dihentikan).\n";
            break;
        }
    }
}

// ---------------------------------------------------------------- cleanup

$envPulih = file_put_contents($envPath, $originalEnv, LOCK_EX) !== FALSE
    && hash('sha256', file_get_contents($envPath)) === $hashAsli;
$envDialihkan = ! $envPulih;

$dbTerhapus = $admin->query("DROP DATABASE `{$database}`");
$dbDibuat = ! $dbTerhapus;

hapus_rekursif($uploads);
$uploadTerhapus = ! is_dir($uploads);

@unlink($envBackup);
@unlink($markerPath);

echo "\n=== CLEANUP ===\n";
echo '.env            : ' . ($envPulih ? 'OK, byte identik dengan aslinya' : 'GAGAL') . "\n";
echo 'DB sementara    : ' . ($dbTerhapus ? 'OK, dihapus' : 'GAGAL') . "\n";
echo 'Folder unggahan : ' . ($uploadTerhapus ? 'OK, dihapus' : 'GAGAL') . "\n";

$beres = ! $gagal && $envPulih && $dbTerhapus && $uploadTerhapus;
echo "\nRINGKASAN FRESH: " . ($beres ? 'HIJAU' : 'GAGAL') . "\n";
exit($beres ? 0 : 1);
