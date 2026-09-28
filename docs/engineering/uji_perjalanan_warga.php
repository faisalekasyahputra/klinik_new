<?php
/**
 * Uji perjalanan Warga ↔ Admin Kabupaten/Kota, dan penutupan jalur diagnosa lama.
 *
 * KEPUTUSAN PEMILIK PRODUK 27 Sep 2026: jalur diagnosa lama
 * (`solusi_pembiayaan`, `Program/diagnosa`, `Program/api_*`,
 * `Program/submit_antrean`) dialihkan ke wizard `warga/pendataan`. Jalur itu
 * menerbitkan tiket tanpa login atau atas nama akun non-warga, dan harness ini
 * dulu MEMAKAINYA untuk melahirkan tiket. Sekarang harness ini justru menjaga
 * bahwa jalur itu tertutup, dan tiket uji untuk admin lahir lewat INSERT
 * langsung (lihat alasannya di bagian POSITIF).
 *
 * Jalankan:
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
        /* Header ikut dikembalikan supaya pemanggil bisa membedakan redirect
           SUKSES dari redirect PENOLAKAN. Keduanya 302, dan tanpa Location
           asersi "redirect sukses" hijau untuk pengajuan yang DITOLAK. */
        return ['status' => $info['http_code'], 'body' => $body,
                'header' => substr($raw, 0, $info['header_size'])];
    }
}

function json_body($response) {
    return json_decode(ltrim($response['body'], "\xEF\xBB\xBF"), TRUE);
}

function lokasi($response) {
    preg_match('/^Location:\s*(.+)$/mi', (string) ($response['header'] ?? ''), $m);
    return trim($m[1] ?? '');
}

function dialihkan_ke_wizard($response) {
    return in_array((int) $response['status'], [302, 303, 307], TRUE)
        && strpos(lokasi($response), 'warga/pendataan') !== FALSE;
}

if ( ! is_file(ENV_PATH)) {
    fwrite(STDERR, ".env tidak ditemukan.\n");
    exit(1);
}

$env = env_config(ENV_PATH);
$db = new Db($env);

/* Batas laju: DIPINJAM lalu DIKEMBALIKAN utuh, bukan dikosongkan. Bentuk kunci
   sha256("<policy>:ip:<ip>") sesuai Rate_limiter::resolve(); ::1 dikelompokkan
   per /64 jadi '0000000000000000/64'. Versi lama harness ini menghapus kunci
   "<policy>:<ip>" yang tidak pernah mengenai baris apa pun. */
$GLOBALS['rate_asli'] = [];
foreach (['login', 'simperum_lookup', 'housing_submit', 'admin_queue_decision', 'kelas_api_ip', 'tulis_anon'] as $policy) {
    foreach (['127.0.0.1', '::1', '0000000000000000/64'] as $ip) {
        $key = hash('sha256', $policy . ':ip:' . $ip);
        $GLOBALS['rate_asli'][$key] = $db->row('SELECT limit_key, window_started_at, failed_attempts FROM sys_rate_limits WHERE limit_key = ?', [$key]);
        $db->run('DELETE FROM sys_rate_limits WHERE limit_key = ?', [$key]);
    }
}

$stamp = time();
$passwordHash = password_hash(ADMIN_PASSWORD, PASSWORD_BCRYPT);
$awalQueue = (int) $db->scalar('SELECT COALESCE(MAX(id), 0) FROM sf_housing_queue');
$GLOBALS['akun_uji'] = [];

/* Bersih-bersih dipasang sebagai shutdown handler supaya `wajib()` yang
   berhenti di tengah tidak meninggalkan akun maupun tiket. Urutan: rekaman
   SIMPERUM dan draft SEBELUM akun (FK SET NULL, bukan CASCADE, jadi menghapus
   akun saja meninggalkan baris yatim), tiket yang lahir selama jalan ini
   (termasuk tiket tamu kalau jalur lama ternyata masih terbuka), baru akun. */
register_shutdown_function(function () use ($db, $awalQueue) {
    foreach ($GLOBALS['akun_uji'] as $id) {
        $db->run('DELETE FROM sf_rekaman_simperum WHERE requested_by = ?', [$id]);
        $db->run('DELETE FROM sf_penilaian_perumahan WHERE user_id = ?', [$id]);
        $db->run('DELETE FROM sf_housing_queue WHERE user_id = ?', [$id]);
    }
    $db->run('DELETE FROM sf_housing_queue WHERE id > ? AND user_id IS NULL', [$awalQueue]);
    foreach ($GLOBALS['akun_uji'] as $id) {
        $db->run('DELETE FROM usr_users WHERE id = ?', [$id]);
    }
    foreach ($GLOBALS['rate_asli'] as $key => $row) {
        $db->run('DELETE FROM sys_rate_limits WHERE limit_key = ?', [$key]);
        if ($row) {
            $db->run('INSERT INTO sys_rate_limits (limit_key, window_started_at, failed_attempts) VALUES (?, ?, ?)',
                [$row['limit_key'], $row['window_started_at'], $row['failed_attempts']]);
        }
    }
});

function akun_uji($db, $email, $name, $role, $kabupaten_id, $hash) {
    $id = $db->run(
        "INSERT INTO usr_users (email, password, name, username, role, status, profile_completed, kabupaten_id, created_at)
         VALUES (?, ?, ?, ?, ?, 'active', 1, ?, NOW())",
        [$email, $hash, $name, strtok($email, '@'), $role, $kabupaten_id]
    );
    $GLOBALS['akun_uji'][] = $id;
    return $id;
}

$emailSemarang = "admin_warga_{$stamp}_semarang@example.test";
$emailBanyumas = "admin_warga_{$stamp}_banyumas@example.test";
$emailWarga = "warga_pw_{$stamp}@example.test";
$emailMhs = "mhs_pw_{$stamp}@example.test";

echo "=== UJI PERJALANAN WARGA ===\n";
echo "Target: " . BASE_URL . " | DB: {$env['DB_NAME']}\n\n";

$programId = $db->scalar("SELECT id FROM sf_programs WHERE kode_program = 'omah_sekeng'");
wajib($programId !== NULL, 'Seed Omah Sekeng tersedia');

$adminSemarang = akun_uji($db, $emailSemarang, 'Admin Semarang Uji', 'admin_kabkota', 3374, $passwordHash);
$adminBanyumas = akun_uji($db, $emailBanyumas, 'Admin Banyumas Uji', 'admin_kabkota', 3302, $passwordHash);
$wargaId = akun_uji($db, $emailWarga, 'Warga Uji Perjalanan', 'warga', 3374, $passwordHash);
$mhsId = akun_uji($db, $emailMhs, 'Mahasiswa Uji Perjalanan', 'mahasiswa', NULL, $passwordHash);

echo "-- TERTUTUP: jalur diagnosa lama dialihkan ke warga/pendataan (keputusan 27 Sep 2026)\n";
$tamu = new Session();
foreach (['solusi_pembiayaan', 'Program/diagnosa/umum', 'solusi_pembiayaan/hasil'] as $path) {
    $r = $tamu->get($path);
    cek(dialihkan_ke_wizard($r), "GET {$path} dialihkan ke warga/pendataan (HTTP {$r['status']}, tujuan: "
        . (lokasi($r) !== '' ? lokasi($r) : 'tidak ada Location') . ')');
}
$halamanUmum = $tamu->get('umum');
wajib($halamanUmum['status'] === 200, 'Halaman layanan umum terbuka');
cek(strpos($halamanUmum['body'], 'Program/diagnosa') === FALSE && strpos($halamanUmum['body'], 'warga/pendataan') !== FALSE,
    'Kartu Klinik Diagnosa di halaman umum menunjuk warga/pendataan, bukan Program/diagnosa');

/* Tamu DAN akun non-warga. Akun non-warga penting: di jalur lama
   api_cek_simperum mengikat NIK ke akun apa pun yang sedang masuk, dan
   submit_antrean menerbitkan tiket atas namanya. CSRF-nya sah (diambil dari
   halaman login), jadi yang menolak adalah controller, bukan penjaga CSRF. */
$mhs = new Session();
$mhs->get('Auth/login');
$mhsLogin = json_body($mhs->post('Auth/do_login', ['email' => $emailMhs, 'password' => ADMIN_PASSWORD]));
wajib(($mhsLogin['status'] ?? '') === 'success', 'Akun mahasiswa (non-warga) login');
$tamu->get('Auth/login');
wajib($tamu->csrf !== NULL, 'Tamu memegang token CSRF sah');

foreach (['tamu' => [$tamu, NULL], 'mahasiswa' => [$mhs, $mhsId]] as $siapa => [$sesi, $uid]) {
    $qSebelum = (int) $db->scalar('SELECT COUNT(*) FROM sf_housing_queue');
    $pSebelum = (int) $db->scalar('SELECT COUNT(*) FROM sf_profil_warga');
    $sSebelum = (int) $db->scalar('SELECT COALESCE(MAX(id), 0) FROM sf_rekaman_simperum');

    $sim = $sesi->post('Program/api_cek_simperum', ['nik' => '0000000000000001', 'tgl_lahir' => '1980-01-01']);
    $simJson = json_body($sim);
    cek($sim['status'] === 410 && ($simJson['code'] ?? '') === 'jalur_dipindah'
        && strpos((string) ($simJson['redirect'] ?? ''), 'warga/pendataan') !== FALSE,
        "{$siapa}: POST api_cek_simperum dijawab 410 jalur_dipindah (HTTP {$sim['status']})");
    cek( ! isset($simJson['data']) && stripos($sim['body'], 'Warga Simulasi') === FALSE,
        "{$siapa}: api_cek_simperum tidak mengembalikan data NIK apa pun");

    $kal = $sesi->post('Program/api_kalkulasi_program', [
        'penghasilan' => '2500000', 'pekerjaan' => 'Karyawan Swasta', 'status_kepemilikan' => 'Sewa/Kontrak',
        'alasan_pengajuan' => 'Membutuhkan rumah layak', 'kabupaten_id' => '3374',
        'kode_program_target' => 'umum', 'simpan_hasil' => '0',
    ]);
    cek($kal['status'] === 410 && (json_body($kal)['code'] ?? '') === 'jalur_dipindah',
        "{$siapa}: POST api_kalkulasi_program dijawab 410 jalur_dipindah (HTTP {$kal['status']})");

    $sub = $sesi->post('Program/submit_antrean', ['program_kode' => 'omah_sekeng'], FALSE);
    cek(dialihkan_ke_wizard($sub), "{$siapa}: POST submit_antrean (formulir) dialihkan ke warga/pendataan (tujuan: "
        . (lokasi($sub) !== '' ? lokasi($sub) : 'tidak ada Location') . ')');
    $subAjax = $sesi->post('Program/submit_antrean', ['program_kode' => 'omah_sekeng']);
    cek($subAjax['status'] === 410 && (json_body($subAjax)['code'] ?? '') === 'jalur_dipindah',
        "{$siapa}: POST submit_antrean (AJAX) dijawab 410 jalur_dipindah (HTTP {$subAjax['status']})");

    cek((int) $db->scalar('SELECT COUNT(*) FROM sf_housing_queue') === $qSebelum,
        "{$siapa}: nol baris sf_housing_queue lahir dari jalur lama");
    cek((int) $db->scalar('SELECT COUNT(*) FROM sf_profil_warga') === $pSebelum,
        "{$siapa}: nol baris sf_profil_warga lahir dari jalur lama");
    cek((int) $db->scalar('SELECT COALESCE(MAX(id), 0) FROM sf_rekaman_simperum') === $sSebelum,
        "{$siapa}: NIK tidak diproses (nol rekaman SIMPERUM baru)");
    if ($uid !== NULL) {
        cek((int) $db->scalar('SELECT COUNT(*) FROM sf_profil_warga WHERE user_id = ?', [$uid]) === 0,
            "{$siapa}: NIK tidak terikat ke akun non-warga");
    }
}

echo "\n-- POSITIF: tiket wilayah Semarang -> admin wilayah -> approve -> cek tiket\n";
/* Tiket lahir lewat INSERT langsung, SENGAJA. Satu-satunya jalur sah yang
   tersisa adalah wizard warga/pendataan, dan jalur itu (lookup NIK fixture,
   tujuh langkah, sampai tiket) sudah diuji utuh beserta keputusan admin di
   uji_wizard_dan_cek_rumah.php. Menjalankannya dua kali cuma menggandakan
   pemakaian kolam NIK fixture. Yang diuji DI SINI adalah sisi admin kab/kota
   (cakupan wilayah, reviewer, transisi), dan baris berbentuk tiket lama
   (source_mode 'legacy', tanpa assessment_id) memang masih ada di DB dan
   tetap harus bisa diputuskan admin. Pemiliknya akun warga uji, bukan tamu. */
$alfabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
do {
    $tiket = 'PKP-';
    for ($i = 0; $i < 6; $i++) { $tiket .= $alfabet[random_int(0, strlen($alfabet) - 1)]; }
} while ($db->scalar('SELECT id FROM sf_housing_queue WHERE ticket_code = ?', [$tiket]));
$queueId = $db->run(
    "INSERT INTO sf_housing_queue (ticket_code, user_id, kabupaten_id, program_id, nik_pengaju, nama_lengkap,
        data_survey_json, status_antrean, created_at, updated_at)
     VALUES (?, ?, 3374, ?, '0000000000000001', 'Warga Uji Perjalanan', ?, 'pending', NOW(), NOW())",
    [$tiket, $wargaId, $programId, json_encode(['penghasilan' => 2500000, 'pekerjaan' => 'Karyawan Swasta',
        'status_kepemilikan' => 'Sewa/Kontrak', 'alasan_pengajuan' => 'Membutuhkan rumah layak'])]
);
$queue = $db->row('SELECT * FROM sf_housing_queue WHERE id = ?', [$queueId]);
wajib($queue && $queue['status_antrean'] === 'pending', 'Baris tiket uji lahir sebagai pending');

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

/* BUTIR 20 PUTARAN 2: layar cek status DICABUT dari situs publik.
   Uji ini dulu membuka `cek_status_pengajuan` sebagai halaman publik, dan itu
   justru yang diminta hilang: nomor tiket berpola tetap plus empat digit NIK
   membuat pengajuan orang lain bisa ditengok tanpa pernah masuk.

   Yang dijaga sekarang perilaku penggantinya: tamu DIARAHKAN, bukan dilayani,
   dan tidak di-404-kan supaya tautan lama tidak jadi jalan buntu. */
$lookup = new Session();
$halamanTamu = $lookup->get('cek_status_pengajuan');
cek(strpos((string) ($halamanTamu['body'] ?? ''), 'Nomor tiket') === FALSE,
    'Formulir cek status tidak lagi disajikan ke tamu');

/* Endpoint tiket lama sengaja tidak lagi membaca data apa pun. Tautan lama
   tetap mendapat jawaban yang jelas (410), tetapi tidak boleh mengetahui
   status, nama, atau keberadaan pengajuan meski kode tiketnya benar. */
$lookup->get('Auth/login');
$lookupResponse = $lookup->post('Program/cek_tiket', [
    'ticket_code' => $queue['ticket_code'],
    'nik_suffix' => '0001',
]);
$lookupResult = json_body($lookupResponse);
// 410 dari controller (dicabut 17 Agt 2026), atau 400 dari penjaga input (21 Sep 2026: nik_suffix
// bukan field yang dikenal). Keduanya penolakan sebelum data dibaca; yang dijaga: tidak ada kebocoran.
cek(in_array((int) ($lookupResponse['status'] ?? 0), [400, 410], TRUE)
    && ($lookupResult['status'] ?? '') === 'error'
    && empty($lookupResult['status_pengajuan']),
    'Endpoint tiket lama menolak akses publik tanpa membocorkan pengajuan');

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
cek(strpos($wrongAdmin->get('Admin_Kabkota')['body'], $queue['ticket_code']) === FALSE,
    'Tiket tidak terlihat di dashboard admin wilayah lain');

$admin->post('Admin_Kabkota/update_status', [
    'queue_id' => $queue['id'], 'status' => 'approved', 'catatan_admin' => '',
], FALSE);
cek($db->scalar('SELECT status_antrean FROM sf_housing_queue WHERE id = ?', [$queue['id']]) === 'approved',
    'Transisi approved → approved ditolak');

echo "\n=== RINGKASAN ===\n";
echo "{$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal.\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
