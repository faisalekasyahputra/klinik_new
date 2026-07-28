<?php
/**
 * Uji perjalanan Warga ↔ Admin Kabupaten/Kota.
 *
 * Jalankan pada DB uji bersih:
 *   php docs/engineering/uji_perjalanan_warga.php
 *
 * Env opsional:
 *   UJI_BASE_URL, UJI_ADMIN_PASSWORD
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('ENV_PATH', dirname(__DIR__, 2) . '/.env');
define('ADMIN_PASSWORD', getenv('UJI_ADMIN_PASSWORD') ?: 'UjiAdmin123!');

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;

function cek($condition, $label) {
    $GLOBALS['uji_total']++;
    if ($condition) {
        echo "  OK    {$label}\n";
        return TRUE;
    }
    echo "  GAGAL {$label}\n";
    $GLOBALS['uji_gagal']++;
    return FALSE;
}

function wajib($condition, $label) {
    if (cek($condition, $label)) {
        return;
    }
    fwrite(STDERR, "Berhenti: prasyarat gagal.\n");
    exit(1);
}

function env_config($path) {
    $out = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === FALSE) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        if ( ! array_key_exists($key, $out)) {
            $out[$key] = trim($value);
        }
    }
    foreach (['DB_HOST', 'DB_USER', 'DB_PASS', 'DB_NAME'] as $key) {
        if (getenv($key) !== FALSE) {
            $out[$key] = getenv($key);
        }
    }
    return $out;
}

class Db {
    private $mysqli;

    public function __construct($env) {
        $this->mysqli = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
        if ($this->mysqli->connect_error) {
            fwrite(STDERR, "Koneksi DB gagal: {$this->mysqli->connect_error}\n");
            exit(1);
        }
    }

    public function row($sql, $params = []) {
        $stmt = $this->prepare($sql, $params);
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: NULL;
    }

    public function scalar($sql, $params = []) {
        $row = $this->row($sql, $params);
        return $row ? reset($row) : NULL;
    }

    public function run($sql, $params = []) {
        $stmt = $this->prepare($sql, $params);
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    }

    private function prepare($sql, $params) {
        $stmt = $this->mysqli->prepare($sql);
        if ( ! $stmt) {
            fwrite(STDERR, "Query gagal disiapkan: {$this->mysqli->error}\n");
            exit(1);
        }
        if ($params) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }
        if ( ! $stmt->execute()) {
            fwrite(STDERR, "Query gagal: {$stmt->error}\n");
            exit(1);
        }
        return $stmt;
    }
}

class Session {
    private $cookie;
    public $csrf;

    public function __construct() {
        $this->cookie = tempnam(sys_get_temp_dir(), 'uji_warga_');
    }

    public function __destruct() {
        @unlink($this->cookie);
    }

    public function get($path) {
        return $this->call($path, [CURLOPT_HTTPGET => TRUE]);
    }

    public function post($path, $fields, $ajax = TRUE) {
        if ($this->csrf) {
            $fields['csrf_kpkp_token'] = $this->csrf;
        }
        $options = [CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => http_build_query($fields)];
        if ($ajax) {
            $options[CURLOPT_HTTPHEADER] = ['X-Requested-With: XMLHttpRequest'];
        }
        return $this->call($path, $options);
    }

    private function call($path, $options) {
        $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
        curl_setopt_array($ch, $options + [
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_COOKIEJAR => $this->cookie,
            CURLOPT_COOKIEFILE => $this->cookie,
            CURLOPT_FOLLOWLOCATION => FALSE,
            CURLOPT_HEADER => TRUE,
            CURLOPT_TIMEOUT => 20,
        ]);
        $raw = curl_exec($ch);
        if ($raw === FALSE) {
            fwrite(STDERR, "curl gagal: " . curl_error($ch) . "\n");
            exit(1);
        }
        $info = curl_getinfo($ch);
        curl_close($ch);
        $body = substr($raw, $info['header_size']);
        if (preg_match('/name="csrf_kpkp_token"\s+value="([a-f0-9]+)"/', $body, $match)) {
            $this->csrf = $match[1];
        }
        return ['status' => $info['http_code'], 'body' => $body];
    }
}

function json_body($response) {
    return json_decode(ltrim($response['body'], "\xEF\xBB\xBF"), TRUE);
}

function prepare_submission($session, $kabupaten_id) {
    $session->get('solusi_pembiayaan');
    $identity = $session->post('Program/api_cek_simperum', [
        'nik' => '0000000000000001',
        'tgl_lahir' => '1980-01-01',
    ]);
    $survey = $session->post('Program/api_kalkulasi_program', [
        'penghasilan' => '2000000',
        'pekerjaan' => 'Karyawan Swasta',
        'status_kepemilikan' => 'Sewa/Kontrak',
        'alasan_pengajuan' => 'Membutuhkan rumah layak',
        'kabupaten_id' => $kabupaten_id,
        'kode_program_target' => 'umum',
        'simpan_hasil' => '0',
    ]);
    return [$identity, $survey];
}

if ( ! is_file(ENV_PATH)) {
    fwrite(STDERR, ".env tidak ditemukan.\n");
    exit(1);
}

$env = env_config(ENV_PATH);
$db = new Db($env);
$rateKeys = [];
foreach (['127.0.0.1', '::1'] as $ip) {
    foreach (['simperum_lookup', 'housing_submit'] as $scope) {
        $rateKeys[] = hash('sha256', $scope . ':' . $ip);
    }
}
$db->run('DELETE FROM sys_rate_limits WHERE limit_key IN (?, ?, ?, ?)', $rateKeys);
$stamp = time();
$emailSemarang = "admin_warga_{$stamp}_semarang@example.test";
$emailBanyumas = "admin_warga_{$stamp}_banyumas@example.test";
$passwordHash = password_hash(ADMIN_PASSWORD, PASSWORD_BCRYPT);

echo "=== UJI PERJALANAN WARGA ===\n";
echo "Target: " . BASE_URL . " | DB: {$env['DB_NAME']}\n\n";

wajib((int) $db->scalar("SELECT COUNT(*) FROM sf_programs WHERE kode_program = 'omah_sekeng'") === 1,
    'Seed Omah Sekeng tersedia');

$adminSemarang = $db->run(
    "INSERT INTO usr_users (email, password, name, username, role, status, profile_completed, kabupaten_id, created_at)
     VALUES (?, ?, 'Admin Semarang Uji', ?, 'admin_kabkota', 'active', 1, 3374, NOW())",
    [$emailSemarang, $passwordHash, "admin_warga_{$stamp}_smg"]
);
$adminBanyumas = $db->run(
    "INSERT INTO usr_users (email, password, name, username, role, status, profile_completed, kabupaten_id, created_at)
     VALUES (?, ?, 'Admin Banyumas Uji', ?, 'admin_kabkota', 'active', 1, 3302, NOW())",
    [$emailBanyumas, $passwordHash, "admin_warga_{$stamp}_bms"]
);

echo "-- POSITIF: diagnosa → kirim → admin wilayah → approve → cek tiket\n";
$guest = new Session();
[$identity, $survey] = prepare_submission($guest, 3374);
wajib(($identity['status'] === 200) && ((json_body($identity)['status'] ?? '') === 'success'), 'Identitas diverifikasi server');
wajib(($survey['status'] === 200) && ((json_body($survey)['status'] ?? '') === 'success'), 'Survei dan wilayah diterima server');
$beforeId = (int) $db->scalar('SELECT COALESCE(MAX(id), 0) FROM sf_housing_queue');
$submit = $guest->post('Program/submit_antrean', ['program_kode' => 'omah_sekeng'], FALSE);
cek(in_array($submit['status'], [302, 303], TRUE), 'Pengajuan menghasilkan redirect sukses');

$queue = $db->row(
    "SELECT q.*, p.kode_program FROM sf_housing_queue q
     JOIN sf_programs p ON p.id = q.program_id
     WHERE q.id > ? ORDER BY q.id DESC LIMIT 1",
    [$beforeId]
);
wajib($queue && $queue['status_antrean'] === 'pending', 'Baris lahir sebagai pending');
cek((int) $queue['kabupaten_id'] === 3374, 'Scope tersimpan sebagai Kota Semarang');
cek($queue['kode_program'] === 'omah_sekeng', 'Program ditentukan ulang dari kode DB, bukan ID klien');
cek($queue['nama_lengkap'] === 'Warga Simulasi RTLH', 'Nama sintetis asli tersimpan; masking hanya untuk tampilan publik');

$admin = new Session();
$admin->get('Auth/login');
$login = json_body($admin->post('Auth/do_login', ['email' => $emailSemarang, 'password' => ADMIN_PASSWORD]));
wajib(($login['status'] ?? '') === 'success' && ($login['role'] ?? '') === 'admin_kabkota', 'Admin Kota Semarang login');
$dashboard = $admin->get('Admin_Kabkota');
cek(strpos($dashboard['body'], $queue['ticket_code']) !== FALSE, 'Tiket terlihat di dashboard admin wilayah yang benar');
$admin->post('Admin_Kabkota/update_status', ['queue_id' => $queue['id'], 'status' => 'approved', 'catatan_admin' => ''], FALSE);
$approved = $db->row('SELECT status_antrean, reviewed_by FROM sf_housing_queue WHERE id = ?', [$queue['id']]);
wajib($approved['status_antrean'] === 'approved', 'Admin wilayah berhasil menyetujui');
cek((int) $approved['reviewed_by'] === (int) $adminSemarang, 'Reviewer tercatat dari sesi admin');

$lookup = new Session();
$lookup->get('cek_status_pengajuan');
$lookupResult = json_body($lookup->post('Program/cek_tiket', [
    'ticket_code' => $queue['ticket_code'],
    'nik_suffix' => '0001',
]));
cek(($lookupResult['status_pengajuan'] ?? '') === 'Disetujui', 'Lookup publik menampilkan status terbaru tanpa PII');

echo "\n-- NEGATIF: program manipulasi tidak melahirkan baris\n";
$before = (int) $db->scalar('SELECT COUNT(*) FROM sf_housing_queue');
$tampered = new Session();
prepare_submission($tampered, 3374);
$tampered->post('Program/submit_antrean', ['program_kode' => 'flpp'], FALSE);
cek((int) $db->scalar('SELECT COUNT(*) FROM sf_housing_queue') === $before, 'Program di luar hasil diagnosa ditolak');

echo "\n-- NEGATIF: wilayah tidak sah ditolak sebelum antrean\n";
$invalidScope = new Session();
[, $invalidSurvey] = prepare_submission($invalidScope, 9999);
cek($invalidSurvey['status'] === 422, 'Kabupaten yang tidak ada ditolak oleh endpoint kalkulasi');
cek((int) $db->scalar('SELECT COUNT(*) FROM sf_housing_queue') === $before, 'Wilayah salah tidak membuat baris yatim scope');

echo "\n-- NEGATIF: admin wilayah lain dan transisi sama ditolak\n";
$wrongAdmin = new Session();
$wrongAdmin->get('Auth/login');
$wrongLogin = json_body($wrongAdmin->post('Auth/do_login', ['email' => $emailBanyumas, 'password' => ADMIN_PASSWORD]));
wajib(($wrongLogin['status'] ?? '') === 'success', 'Admin Kabupaten Banyumas login');
$wrongAdmin->post('Admin_Kabkota/update_status', [
    'queue_id' => $queue['id'], 'status' => 'rejected', 'catatan_admin' => 'Salah wilayah',
], FALSE);
cek($db->scalar('SELECT status_antrean FROM sf_housing_queue WHERE id = ?', [$queue['id']]) === 'approved',
    'Admin wilayah lain tidak dapat mengubah baris');

$admin->post('Admin_Kabkota/update_status', [
    'queue_id' => $queue['id'], 'status' => 'approved', 'catatan_admin' => '',
], FALSE);
cek($db->scalar('SELECT status_antrean FROM sf_housing_queue WHERE id = ?', [$queue['id']]) === 'approved',
    'Transisi approved → approved ditolak');

$db->run('DELETE FROM sf_housing_queue WHERE id = ?', [$queue['id']]);
$db->run('DELETE FROM usr_users WHERE id IN (?, ?)', [$adminSemarang, $adminBanyumas]);
$db->run('DELETE FROM sys_rate_limits WHERE limit_key IN (?, ?, ?, ?)', $rateKeys);

echo "\n=== RINGKASAN ===\n";
echo "{$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal.\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
