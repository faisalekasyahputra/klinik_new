<?php
/**
 * Check R2: cache semantics + 20 lookup serentak.
 *
 * Jalankan pada DB lokal/uji yang sudah migrasi 18:
 *   php docs/engineering/uji_simperum_gateway.php
 */

$root = dirname(__DIR__, 2);
$php = PHP_BINARY;
$total = 0;
$failed = 0;

function cek($condition, $label) {
    global $total, $failed;
    $total++;
    echo ($condition ? 'OK    ' : 'GAGAL ') . $label . "\n";
    if ( ! $condition) {
        $failed++;
    }
}

function run_cli($root, $php, array $arguments) {
    $command = array_merge([$php, 'index.php', 'migrate'], $arguments);
    $pipes = [];
    $process = proc_open($command, [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes, $root);
    if ( ! is_resource($process)) {
        return ['process' => NULL, 'pipes' => [], 'output' => '', 'error' => 'gagal memulai proses'];
    }
    return ['process' => $process, 'pipes' => $pipes];
}

function finish_cli(array $job) {
    $output = stream_get_contents($job['pipes'][1]);
    $error = stream_get_contents($job['pipes'][2]);
    fclose($job['pipes'][1]);
    fclose($job['pipes'][2]);
    return ['code' => proc_close($job['process']), 'output' => trim($output), 'error' => trim($error)];
}

$reset = finish_cli(run_cli($root, $php, ['simperum_probe', 'reset']));
cek($reset['code'] === 0, 'Cache SIM-01 dibersihkan sebelum uji');

$jobs = [];
for ($i = 0; $i < 20; $i++) {
    $jobs[] = run_cli($root, $php, ['simperum_probe', 'lookup']);
}

$good = 0;
foreach ($jobs as $job) {
    $result = finish_cli($job);
    $json = json_decode($result['output'], TRUE);
    if ($result['code'] === 0 && ($json['status'] ?? '') === 'found') {
        $good++;
    }
}
cek($good === 20, '20 lookup serentak seluruhnya berhasil');

$count = finish_cli(run_cli($root, $php, ['simperum_probe', 'count']));
cek($count['output'] === '1', '20 lookup serentak hanya membuat satu snapshot');

$semantics = finish_cli(run_cli($root, $php, ['uji_warga_r2']));
cek($semantics['code'] === 0 && strpos($semantics['output'], '14 pemeriksaan, 0 gagal') !== FALSE,
    'Found/negative/error cache dan masking 14/14 hijau');

$cleanup = finish_cli(run_cli($root, $php, ['simperum_probe', 'reset']));
cek($cleanup['code'] === 0, 'Cache SIM-01 dibersihkan setelah uji');

echo "RINGKASAN: {$total} pemeriksaan, {$failed} gagal\n";
exit($failed > 0 ? 1 : 0);

