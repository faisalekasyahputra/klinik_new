<?php
/**
 * Uji basis data untuk pembatas laju jalur panas (form keamanan poin 10.4).
 *
 * Menjalankan Rate_limiter::hit_fast() ASLI terhadap MySQL nyata dan membuktikan tiga hal:
 *  1. semantik: hitungan naik 1,2,3..., yang melewati batas ditolak, jendela baru mereset, tutup di 255;
 *  2. KONKURENSI: beberapa proses PHP sungguhan menembak kunci yang sama serentak; hitungan yang
 *     diterima seluruhnya harus persis permutasi 1..N (tidak ada pembaruan hilang, tidak ada dobel);
 *  3. kepekaan uji: pencacah "baca lalu tulis" yang naif dijalankan dengan cara yang sama dan HARUS
 *     menghasilkan hitungan ganda, jadi uji ini memang bisa merah bila atomisitasnya rusak.
 *
 * Butuh MySQL yang menyimpan tabel sys_rate_limits (skema aplikasi). Env: DB_HOST (bawaan 127.0.0.1),
 * DB_USER (root), DB_PASS, DB_NAME (klinikpkp). Tanpa DB: gagal, kecuali UJI_DB_BOLEH_LEWATI=1.
 * Jalankan:  php tests/anti_automation_db_test.php
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

define('BASEPATH', __DIR__ . '/../system/');

function uji_koneksi() {
    mysqli_report(MYSQLI_REPORT_OFF);
    $m = @new mysqli(getenv('DB_HOST') ?: '127.0.0.1', getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '', getenv('DB_NAME') ?: 'klinikpkp');
    if ($m->connect_errno) { return NULL; }
    $m->set_charset('utf8mb4');
    return $m;
}

class UjiHasil {
    private $r;
    public function __construct($r) { $this->r = $r; }
    public function row_array() { return $this->r; }
}
class UjiDb {
    public $m;
    public function __construct($m) { $this->m = $m; }
    public function query($sql, $binds = []) {
        $st = $this->m->prepare($sql);
        if ( ! $st) { return FALSE; }
        if ($binds) { $st->bind_param(str_repeat('s', count($binds)), ...array_map('strval', $binds)); }
        if ( ! $st->execute()) { return FALSE; }
        $res = $st->get_result();
        if ($res instanceof mysqli_result) { return new UjiHasil($res->fetch_assoc() ?: NULL); }
        return TRUE;
    }
    public function insert_id() { return $this->m->insert_id; }
}
class UjiConfig {
    public $policies;
    public function load($n, $s = FALSE) {}
    public function item($k, $s = NULL) { return $k === 'rate_limit_policies' ? $this->policies : 'kunci-uji'; }
}
class UjiLoad { public function database() {} public function library() { throw new RuntimeException('tak dipakai'); } }
class UjiInput { public function ip_address() { return '203.0.113.9'; } }
class UjiRouter { public function fetch_class() { return 'Uji'; } public function fetch_method() { return 'uji'; } }

const UJI_KEBIJAKAN = [
    'uji_konkuren' => ['limit' => 200, 'window' => 60, 'dimensions' => ['key'], 'senyap' => TRUE],
    'uji_batas'    => ['limit' => 3,   'window' => 60, 'dimensions' => ['key'], 'senyap' => TRUE],
    'uji_jendela'  => ['limit' => 5,   'window' => 1,  'dimensions' => ['key'], 'senyap' => TRUE],
    'uji_tutup'    => ['limit' => 255, 'window' => 60, 'dimensions' => ['key'], 'senyap' => TRUE],
];

function uji_limiter($m) {
    global $ci;
    $cfg = new UjiConfig(); $cfg->policies = UJI_KEBIJAKAN;
    $ci = (object) ['db' => new UjiDb($m), 'config' => $cfg, 'load' => new UjiLoad(), 'input' => new UjiInput(), 'router' => new UjiRouter()];
    if ( ! function_exists('get_instance')) { eval('function &get_instance() { global $ci; return $ci; }'); }
    if ( ! function_exists('log_message')) { eval('function log_message($l, $msg) {}'); }
    require_once __DIR__ . '/../application/libraries/Rate_limiter.php';
    return new Rate_limiter();
}
function uji_kunci($policy, $nilai) { return hash('sha256', $policy . ':key:' . $nilai); }

/** Pencacah naif (baca lalu tulis) untuk membuktikan bahwa uji ini peka terhadap pembaruan yang hilang. */
function uji_naif($m, $policy, $nilai) {
    $kunci = uji_kunci($policy, $nilai);
    $st = $m->prepare('SELECT failed_attempts FROM sys_rate_limits WHERE limit_key = ?');
    $st->bind_param('s', $kunci); $st->execute();
    $baris = $st->get_result()->fetch_assoc();
    $baru = ($baris ? (int) $baris['failed_attempts'] : 0) + 1;
    usleep(random_int(0, 300));
    $st2 = $m->prepare('INSERT INTO sys_rate_limits (limit_key, window_started_at, failed_attempts) VALUES (?, NOW(), ?)
                        ON DUPLICATE KEY UPDATE failed_attempts = VALUES(failed_attempts)');
    $st2->bind_param('si', $kunci, $baru); $st2->execute();
    return $baru;
}

// ------------------------------------------------------------------ mode pekerja (proses anak)
if (($argv[1] ?? '') === 'pekerja') {
    [, , $mode, $policy, $nilai, $jumlah, $mulai] = $argv;
    $m = uji_koneksi();
    if ( ! $m) { fwrite(STDERR, "koneksi gagal\n"); exit(2); }
    $lim = $mode === 'atomik' ? uji_limiter($m) : NULL;
    while (microtime(TRUE) < (float) $mulai) { /* gerbang: semua pekerja mulai bersamaan */ }
    $hasil = [];
    for ($i = 0; $i < (int) $jumlah; $i++) {
        if ($mode === 'atomik') {
            $r = $lim->hit_fast($policy, ['key' => $nilai]);
            if (empty($r['success'])) { fwrite(STDERR, "hit_fast gagal\n"); exit(3); }
            $hasil[] = (int) $r['count'];
        } else {
            $hasil[] = uji_naif($m, $policy, $nilai);
        }
    }
    echo json_encode($hasil);
    exit(0);
}

// ------------------------------------------------------------------ pemeriksa
$total = 0;
function check($kondisi, $pesan) { global $total; $total++; if ( ! $kondisi) { throw new RuntimeException($pesan); } }

$m = uji_koneksi();
if ( ! $m) {
    if (getenv('UJI_DB_BOLEH_LEWATI') === '1') { echo "anti_automation_db_test: DILEWATI (tidak ada koneksi MySQL)\n"; exit(0); }
    fwrite(STDERR, "anti_automation_db_test: GAGAL, MySQL tidak terhubung (set UJI_DB_BOLEH_LEWATI=1 untuk melewati)\n");
    exit(1);
}
$cek = $m->query("SHOW TABLES LIKE 'sys_rate_limits'");
if ( ! $cek || $cek->num_rows === 0) { fwrite(STDERR, "tabel sys_rate_limits tidak ada\n"); exit(1); }

$lim = uji_limiter($m);
$run = bin2hex(random_bytes(6));
$dipakai = [];
register_shutdown_function(function () use ($m, &$dipakai) {
    foreach ($dipakai as $kunci) { $st = $m->prepare('DELETE FROM sys_rate_limits WHERE limit_key = ?'); $st->bind_param('s', $kunci); $st->execute(); }
});
$pakai = function ($policy, $nilai) use (&$dipakai) { return $dipakai[] = uji_kunci($policy, $nilai); };

// --- 1. Semantik ------------------------------------------------------------------------
$k = "batas-$run"; $pakai('uji_batas', $k);
$urut = [];
for ($i = 1; $i <= 5; $i++) { $urut[] = $lim->hit_fast('uji_batas', ['key' => $k]); }
check(array_column($urut, 'count') === [1, 2, 3, 4, 5], 'Hitungan harus naik 1..5, dapat: ' . implode(',', array_column($urut, 'count')));
check(array_column($urut, 'allowed') === [TRUE, TRUE, TRUE, FALSE, FALSE], 'Permintaan ke-1..3 diizinkan, ke-4 dan seterusnya ditolak');
check($urut[3]['warning_type'] === 'concurrent_burst', 'Pelampauan pada jendela <= 60 detik harus bertipe concurrent_burst');
check($urut[3]['retry_after'] >= 1 && $urut[3]['retry_after'] <= 60, 'retry_after harus antara 1 dan window');
check($urut[0]['retry_after'] === 0 && $urut[0]['warning_type'] === NULL, 'Permintaan yang diizinkan tidak boleh membawa retry_after/peringatan');

$k2 = "kunci-lain-$run"; $pakai('uji_batas', $k2);
check($lim->hit_fast('uji_batas', ['key' => $k2])['count'] === 1, 'Kunci lain harus punya hitungan sendiri');

$k = "jendela-$run"; $pakai('uji_jendela', $k);
for ($i = 0; $i < 4; $i++) { $lim->hit_fast('uji_jendela', ['key' => $k]); }
sleep(2);
check($lim->hit_fast('uji_jendela', ['key' => $k])['count'] === 1, 'Setelah jendela berakhir hitungan harus kembali ke 1');

$k = "tutup-$run"; $kunci_tutup = $pakai('uji_tutup', $k);
$m->query("INSERT INTO sys_rate_limits (limit_key, window_started_at, failed_attempts) VALUES ('$kunci_tutup', NOW(), 254)");
$a = $lim->hit_fast('uji_tutup', ['key' => $k]); $b = $lim->hit_fast('uji_tutup', ['key' => $k]); $c = $lim->hit_fast('uji_tutup', ['key' => $k]);
check([$a['count'], $b['count'], $c['count']] === [255, 255, 255], 'Hitungan harus berhenti di 255 (batas TINYINT), bukan meluap');
check($c['allowed'] === TRUE, 'Pada limit 255 hitungan 255 masih diizinkan (blokir hanya bila > limit)');

// --- 2. Konkurensi nyata ------------------------------------------------------------------
function uji_serentak($mode, $policy, $nilai, $proses, $per_proses) {
    $mulai = microtime(TRUE) + 1.5;
    $anak = [];
    for ($i = 0; $i < $proses; $i++) {
        $cmd = [PHP_BINARY, __FILE__, 'pekerja', $mode, $policy, $nilai, (string) $per_proses, sprintf('%.6F', $mulai)];
        $p = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipa);
        if ( ! is_resource($p)) { throw new RuntimeException('proc_open gagal'); }
        $anak[] = [$p, $pipa];
    }
    $semua = [];
    foreach ($anak as [$p, $pipa]) {
        $out = stream_get_contents($pipa[1]); $err = stream_get_contents($pipa[2]);
        fclose($pipa[1]); fclose($pipa[2]);
        $kode = proc_close($p);
        if ($kode !== 0) { throw new RuntimeException("Pekerja gagal (kode $kode): $err"); }
        $semua = array_merge($semua, json_decode($out, TRUE) ?: []);
    }
    return $semua;
}

$proses = 8; $per = 15; $n = $proses * $per;
$k = "serentak-$run"; $kk = $pakai('uji_konkuren', $k);
$hitungan = uji_serentak('atomik', 'uji_konkuren', $k, $proses, $per);
sort($hitungan);
check(count($hitungan) === $n, 'Jumlah hasil pekerja harus ' . $n . ', dapat ' . count($hitungan));
check($hitungan === range(1, $n), "Hitungan serentak harus persis permutasi 1..$n (tanpa pembaruan hilang/ganda); dapat: " . implode(',', $hitungan));
$st = $m->prepare('SELECT failed_attempts FROM sys_rate_limits WHERE limit_key = ?');
$st->bind_param('s', $kk); $st->execute();
check((int) $st->get_result()->fetch_assoc()['failed_attempts'] === $n, 'Nilai akhir di tabel harus sama dengan jumlah permintaan');

// --- 3. Kepekaan: pencacah naif HARUS menunjukkan hitungan ganda ---------------------------
$naif_ganda = FALSE;
for ($percobaan = 1; $percobaan <= 5 && ! $naif_ganda; $percobaan++) {
    $k = "naif-$run-$percobaan"; $pakai('uji_konkuren', $k);
    $h = uji_serentak('naif', 'uji_konkuren', $k, $proses, $per);
    if (count(array_unique($h)) < count($h)) { $naif_ganda = TRUE; }
}
check($naif_ganda, 'Pencacah naif seharusnya menghasilkan hitungan ganda; jika tidak, uji konkurensi ini tidak cukup peka');

echo "anti_automation_db_test: OK ($total pemeriksaan; $proses proses x $per permintaan serentak = permutasi 1..$n; pencacah naif terbukti ganda)\n";
