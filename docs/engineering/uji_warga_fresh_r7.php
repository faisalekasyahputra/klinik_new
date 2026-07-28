<?php
/**
 * R7: bangun DB fresh, migrasikan baseline sampai versi terbaru, jalankan
 * perjalanan R1-R6 melalui CLI + Apache/curl, lalu pulihkan .env dan hapus DB.
 *
 * Jalankan:
 *   C:\xampp\php\php.exe docs\engineering\uji_warga_fresh_r7.php
 */

$root = dirname(__DIR__, 2);
$envPath = $root . DIRECTORY_SEPARATOR . '.env';
$schemaPath = __DIR__ . DIRECTORY_SEPARATOR . 'schema_klinikpkp.sql';
$lock = fopen(sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'klinikpkp_r7_fresh.lock', 'c');
if ( ! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
    fwrite(STDERR, "Uji fresh R7 lain sedang berjalan.\n");
    exit(1);
}

function env_values($path)
{
    $values = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === FALSE) continue;
        [$key, $value] = explode('=', $line, 2);
        if ( ! array_key_exists(trim($key), $values)) $values[trim($key)] = trim($value);
    }
    return $values;
}

function run_check($root, array $command, array $env)
{
    echo "\n> " . implode(' ', array_map(function ($part) {
        return strpos($part, ' ') === FALSE ? $part : '"' . $part . '"';
    }, $command)) . "\n";
    $pipes = [];
    $process = proc_open($command, [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes, $root, $env);
    if ( ! is_resource($process)) return 1;
    while ( ! feof($pipes[1]) || ! feof($pipes[2])) {
        $read = [];
        if ( ! feof($pipes[1])) $read[] = $pipes[1];
        if ( ! feof($pipes[2])) $read[] = $pipes[2];
        if ( ! $read) break;
        $write = $except = NULL;
        if (stream_select($read, $write, $except, 1) === FALSE) break;
        foreach ($read as $stream) {
            $chunk = fread($stream, 8192);
            if ($stream === $pipes[2]) fwrite(STDERR, $chunk); else echo $chunk;
        }
    }
    fclose($pipes[1]); fclose($pipes[2]);
    return proc_close($process);
}

if ( ! is_file($envPath) || ! is_file($schemaPath)) {
    fwrite(STDERR, ".env atau baseline schema tidak ditemukan.\n");
    exit(1);
}

$originalEnv = file_get_contents($envPath);
$config = env_values($envPath);
foreach (['DB_HOST', 'DB_USER', 'DB_NAME'] as $required) {
    if (empty($config[$required])) {
        fwrite(STDERR, "$required tidak tersedia.\n");
        exit(1);
    }
}

$database = 'klinikpkp_uji_warga_r7_' . date('Ymd_His') . '_' . bin2hex(random_bytes(2));
if ( ! preg_match('/^klinikpkp_uji_warga_r7_[a-zA-Z0-9_]+$/', $database)) exit(1);
$admin = new mysqli($config['DB_HOST'], $config['DB_USER'], $config['DB_PASS'] ?? '');
if ($admin->connect_error) {
    fwrite(STDERR, "Koneksi MariaDB gagal: {$admin->connect_error}\n");
    exit(1);
}

$envSwitched = FALSE;
$databaseCreated = FALSE;
register_shutdown_function(function () use (
    &$envSwitched, &$databaseCreated, $envPath, $originalEnv, $admin, $database, $lock
) {
    if ($envSwitched) file_put_contents($envPath, $originalEnv, LOCK_EX);
    if ($databaseCreated && preg_match('/^klinikpkp_uji_warga_r7_[a-zA-Z0-9_]+$/', $database)) {
        $admin->query("DROP DATABASE `$database`");
    }
    if (is_resource($lock)) {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
});

echo "=== UJI FRESH WARGA R7 ===\n";
echo "DB sementara: $database\n";
echo "Baseline: docs/engineering/schema_klinikpkp.sql\n";

if ( ! $admin->query("CREATE DATABASE `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    fwrite(STDERR, "CREATE DATABASE gagal: {$admin->error}\n");
    exit(1);
}
$databaseCreated = TRUE;
$admin->select_db($database);
$sql = file_get_contents($schemaPath);
$sql = preg_replace('/^\xEF\xBB\xBF/', '', $sql);
if ( ! $admin->multi_query($sql)) {
    fwrite(STDERR, "Import baseline gagal: {$admin->error}\n");
    exit(1);
}
do {
    if ($result = $admin->store_result()) $result->free();
    if ( ! $admin->more_results()) break;
} while ($admin->next_result());
if ($admin->errno) {
    fwrite(STDERR, "Import baseline gagal: {$admin->error}\n");
    exit(1);
}

$freshEnv = preg_replace(
    '/^(?!\s*#)\s*DB_NAME\s*=.*$/m',
    'DB_NAME=' . $database,
    $originalEnv,
    1,
    $replacementCount
);
if ($replacementCount !== 1 || file_put_contents($envPath, $freshEnv, LOCK_EX) === FALSE) {
    fwrite(STDERR, "DB_NAME aktif di .env tidak dapat dialihkan.\n");
    exit(1);
}
$envSwitched = TRUE;

$childEnv = array_merge(getenv(), [
    'DB_HOST' => $config['DB_HOST'],
    'DB_USER' => $config['DB_USER'],
    'DB_PASS' => $config['DB_PASS'] ?? '',
    'DB_NAME' => $database,
    'UJI_BASE_URL' => getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new',
]);
$php = PHP_BINARY;
$checks = [
    [$php, 'index.php', 'migrate'],
    [$php, 'index.php', 'migrate', 'status'],
    [$php, 'index.php', 'migrate', 'uji_warga_r1'],
    [$php, 'docs/engineering/uji_simperum_gateway.php'],
    [$php, 'docs/engineering/uji_pendataan_warga_r3.php'],
    [$php, 'docs/engineering/uji_pendataan_warga_r4.php'],
    [$php, 'docs/engineering/uji_pendataan_warga_r5.php'],
    [$php, 'docs/engineering/uji_pendataan_warga_r6.php'],
    [$php, 'docs/engineering/uji_perjalanan_warga.php'],
    [$php, 'docs/engineering/uji_keamanan_warga_r7.php'],
];

$failed = 0;
foreach ($checks as $check) {
    $code = run_check($root, $check, $childEnv);
    if ($code !== 0) {
        $failed++;
        $diagnostic = $admin->query("SELECT c.TABLE_NAME,c.COLUMN_NAME,c.COLUMN_TYPE,t.ENGINE
            FROM information_schema.COLUMNS c
            JOIN information_schema.TABLES t
              ON t.TABLE_SCHEMA=c.TABLE_SCHEMA AND t.TABLE_NAME=c.TABLE_NAME
            WHERE c.TABLE_SCHEMA=DATABASE()
              AND ((c.TABLE_NAME='sf_housing_queue' AND c.COLUMN_NAME='recommendation_id')
                OR (c.TABLE_NAME='sf_rekomendasi_penilaian' AND c.COLUMN_NAME='id'))");
        if ($diagnostic) {
            foreach ($diagnostic->fetch_all(MYSQLI_ASSOC) as $row) {
                echo "DIAG: {$row['TABLE_NAME']}.{$row['COLUMN_NAME']} {$row['COLUMN_TYPE']} {$row['ENGINE']}\n";
            }
        }
        $tables = $admin->query("SHOW TABLES LIKE 'sf_%'");
        if ($tables) echo 'DIAG tables: ' . implode(', ', array_column($tables->fetch_all(MYSQLI_NUM), 0)) . "\n";
        echo "GAGAL (exit $code); rangkaian dihentikan.\n";
        break;
    }
}

$envSwitched = file_put_contents($envPath, $originalEnv, LOCK_EX) === FALSE;
$dropped = $admin->query("DROP DATABASE `$database`");
$databaseCreated = ! $dropped;
echo "\nCleanup .env: " . ($envSwitched ? 'GAGAL' : 'OK, byte asli dipulihkan') . "\n";
echo "Cleanup DB: " . ($dropped ? 'OK, database sementara dihapus' : 'GAGAL') . "\n";
echo "RINGKASAN R7: " . ($failed || $envSwitched || ! $dropped ? 'GAGAL' : 'HIJAU') . "\n";
exit($failed || $envSwitched || ! $dropped ? 1 : 0);
