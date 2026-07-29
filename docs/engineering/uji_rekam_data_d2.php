<?php
/**
 * Uji D2 — Rekam Data, Input Capaian Perumahan lewat HTTP sungguhan.
 *
 * Menempuh layar seperti petugas kabupaten: login, buka periode, jawab gerbang
 * sumber dana, isi enam program, dan dibuktikan di DB — bukan dari flash di layar.
 *
 * Jalankan lewat Apache XAMPP (bukan `php -S`, lihat AGENTS.md §0e):
 *   php docs/engineering/uji_rekam_data_d2.php
 *
 * Env opsional: UJI_BASE_URL, UJI_ADMIN_EMAIL, UJI_ADMIN_PASSWORD
 *
 * Memakai tahun sentinel 2099 dan menghapus seluruh jejaknya di akhir.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('ENV_PATH', dirname(__DIR__, 2) . '/.env');
define('ADMIN_EMAIL', getenv('UJI_ADMIN_EMAIL') ?: 'adminkabkota@example.com');
define('ADMIN_PASSWORD', getenv('UJI_ADMIN_PASSWORD') ?: 'password');
define('TAHUN', 2099);
define('BULAN', 6);

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;

function cek($condition, $label) {
    $GLOBALS['uji_total']++;
    echo ($condition ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $condition) {
        $GLOBALS['uji_gagal']++;
    }
    return (bool) $condition;
}

function wajib($condition, $label) {
    if ( ! cek($condition, $label)) {
        fwrite(STDERR, "Berhenti: prasyarat gagal.\n");
        bersihkan();
        exit(1);
    }
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
        // Baris pertama menang: .env memuat tiga blok DB_*, mengambil yang
        // terakhir berarti mendapat kredensial production (AGENTS.md §0e).
        if ( ! array_key_exists($key, $out)) {
            $out[$key] = trim($value);
        }
    }
    return $out;
}

$env = env_config(ENV_PATH);
$db = new mysqli($env['DB_HOST'] ?? 'localhost', $env['DB_USER'] ?? 'root',
    $env['DB_PASS'] ?? '', $env['DB_NAME'] ?? 'klinikpkp');
if ($db->connect_error) {
    fwrite(STDERR, "Koneksi DB gagal: {$db->connect_error}\n");
    exit(1);
}

function q($sql, $params = []) {
    global $db;
    $stmt = $db->prepare($sql);
    if ($params) {
        $stmt->bind_param(str_repeat('s', count($params)), ...$params);
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : NULL;
    $stmt->close();
    return $row;
}

function skalar($sql, $params = []) {
    $row = q($sql, $params);
    return $row ? reset($row) : NULL;
}

// ---------------------------------------------------------------- HTTP

$jars = [];

function jar($nama) {
    global $jars;
    if ( ! isset($jars[$nama])) {
        $jars[$nama] = tempnam(sys_get_temp_dir(), 'ujid2_');
    }
    return $jars[$nama];
}

// $post nullable ditulis eksplisit: bentuk implisit memicu Deprecated di PHP 8.4
// yang mencemari stdout dan bisa membuat harness lain merah palsu (AGENTS.md §0e).
function http($nama, $path, ?array $post = NULL, $follow = TRUE) {
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_COOKIEJAR      => jar($nama),
        CURLOPT_COOKIEFILE     => jar($nama),
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_TIMEOUT        => 30,
    ]);
    if ($post !== NULL) {
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => (string) $body];
}

/** Token CSRF selalu diambil dari halaman yang sedang dibuka, bukan ditebak. */
function csrf($nama, $path) {
    $res = http($nama, $path);
    if (preg_match('/name="csrf_kpkp_token" value="([^"]+)"/', $res['body'], $m)) {
        return $m[1];
    }
    return '';
}

function login($nama, $email, $password) {
    $token = csrf($nama, 'Auth/login');
    $res = http($nama, 'Auth/do_login', [
        'csrf_kpkp_token' => $token, 'email' => $email, 'password' => $password,
    ]);
    return $res['code'] === 200;
}

function bersihkan() {
    global $db, $jars;
    $db->query('DELETE FROM rd_laporan WHERE tahun = ' . (int) TAHUN);
    $db->query("DELETE FROM usr_users WHERE email LIKE 'uji_rd_d2_%'");
    foreach ($jars as $file) {
        @unlink($file);
    }
}

// ---------------------------------------------------------------- prasyarat

echo "Uji D2 — Input Capaian Perumahan\n";

$admin = q('SELECT id, kabupaten_id FROM usr_users WHERE email = ? AND role = ?',
    [ADMIN_EMAIL, 'admin_kabkota']);
wajib($admin && ! empty($admin['kabupaten_id']),
    'Akun admin_kabkota tersedia dan ter-scope wilayah');
$KAB = (int) $admin['kabupaten_id'];

$kab_lain = skalar('SELECT id FROM kabupaten WHERE id <> ? ORDER BY id LIMIT 1', [$KAB]);
wajib((int) $kab_lain > 0, 'Ada kabupaten kedua untuk uji scope');

// Admin wilayah lain dibuat sekali pakai, supaya uji scope tidak bergantung
// pada akun demo yang bisa saja tidak ada di lingkungan lain.
$stamp = time();
$email_lain = "uji_rd_d2_{$stamp}@example.test";
$db->query(sprintf(
    "INSERT INTO usr_users (email, password, role, kabupaten_id, name, username)
     VALUES ('%s', '%s', 'admin_kabkota', %d, 'Uji D2 Lain', 'uji_rd_d2_%d')",
    $db->real_escape_string($email_lain),
    $db->real_escape_string(password_hash('UjiRdD2!', PASSWORD_BCRYPT)),
    (int) $kab_lain, $stamp));

try {
    wajib(login('kab', ADMIN_EMAIL, ADMIN_PASSWORD), 'Login admin_kabkota berhasil');
    wajib(login('lain', $email_lain, 'UjiRdD2!'), 'Login admin wilayah lain berhasil');

    // ------------------------------------------------------------ layar
    $url = 'Rekam_Perumahan?tahun=' . TAHUN . '&bulan=' . BULAN;
    $res = http('kab', $url);
    cek($res['code'] === 200, 'Layar input terbuka (200)');
    cek(strpos($res['body'], 'Input Capaian Perumahan') !== FALSE, 'Judul layar benar');

    $laporan = q('SELECT id, status FROM rd_laporan WHERE domain = ? AND kabupaten_id = ?
        AND tahun = ? AND bulan = ?', ['perumahan', $KAB, TAHUN, BULAN]);
    wajib($laporan && $laporan['status'] === 'draft', 'Draft periode dibuat otomatis saat layar dibuka');
    $LAP = (int) $laporan['id'];

    http('kab', $url);
    cek((int) skalar('SELECT COUNT(*) c FROM rd_laporan WHERE domain = ? AND kabupaten_id = ?
        AND tahun = ? AND bulan = ?', ['perumahan', $KAB, TAHUN, BULAN]) === 1,
        'Membuka layar dua kali tidak membuat laporan kedua');

    // Kabupaten dari sesi: tidak boleh ada pemilih wilayah di layar mana pun.
    cek(stripos($res['body'], 'name="kabupaten') === FALSE, 'Nol input wilayah di layar');
    cek(preg_match_all('/name="sumber_dana" value="/', $res['body']) === 10,
        'Sepuluh sumber dana dirender');

    // ------------------------------------------------------------ CSRF
    $tanpa_token = http('kab', 'Rekam_Perumahan/simpan_gerbang', [
        'laporan_id' => $LAP, 'sumber_dana' => 'apbd_kabkota', 'ada' => '1',
    ]);
    cek($tanpa_token['code'] === 403, 'POST tanpa token CSRF ditolak 403');
    cek((int) skalar('SELECT COUNT(*) c FROM rd_perumahan_bagian WHERE laporan_id = ?', [$LAP]) === 0,
        'POST tanpa CSRF tidak menulis apa pun');

    // ------------------------------------------------------------ gerbang
    $t = csrf('kab', $url);
    http('kab', 'Rekam_Perumahan/simpan_gerbang', [
        'csrf_kpkp_token' => $t, 'laporan_id' => $LAP,
        'sumber_dana' => 'apbd_kabkota', 'ada' => '1',
    ]);
    cek((int) skalar('SELECT ada FROM rd_perumahan_bagian WHERE laporan_id = ? AND sumber_dana = ?',
        [$LAP, 'apbd_kabkota']) === 1, 'Gerbang "Ada" tersimpan');

    // ------------------------------------------------------------ angka
    $isi = function ($unit, $anggaran) {
        $out = [];
        foreach (['pk_rtlh', 'pb_rtlh', 'pb_backlog', 'pk_bencana', 'pb_bencana', 'pb_relokasi'] as $p) {
            $out["program[$p][unit]"] = $unit;
            $out["program[$p][anggaran]"] = $anggaran;
        }
        return $out;
    };

    $t = csrf('kab', $url);
    http('kab', 'Rekam_Perumahan/simpan_angka', [
        'csrf_kpkp_token' => $t, 'laporan_id' => $LAP, 'sumber_dana' => 'apbd_kabkota',
    ] + $isi(12, 3000000000));
    cek((int) skalar('SELECT COUNT(*) c FROM rd_perumahan_baris WHERE laporan_id = ?', [$LAP]) === 6,
        'Enam program tersimpan');
    cek((string) skalar('SELECT anggaran FROM rd_perumahan_baris WHERE laporan_id = ?
        AND sumber_dana = ? AND program = ?', [$LAP, 'apbd_kabkota', 'pk_rtlh']) === '3000000000',
        'Anggaran tersimpan rupiah penuh, tanpa pembulatan');

    $t = csrf('kab', $url);
    http('kab', 'Rekam_Perumahan/simpan_angka', [
        'csrf_kpkp_token' => $t, 'laporan_id' => $LAP, 'sumber_dana' => 'apbd_kabkota',
    ] + $isi(12, 3000000000));
    cek((int) skalar('SELECT COUNT(*) c FROM rd_perumahan_baris WHERE laporan_id = ?', [$LAP]) === 6,
        'Simpan dua kali tidak menggandakan baris');

    // ------------------------------------------------------ tolakan server
    $tolak = [
        'unit negatif'        => $isi(-5, 1000),
        'unit bukan angka'    => $isi('dua', 1000),
        'anggaran tanpa unit' => $isi(0, 500000000),
    ];
    foreach ($tolak as $label => $payload) {
        $t = csrf('kab', $url);
        http('kab', 'Rekam_Perumahan/simpan_angka', [
            'csrf_kpkp_token' => $t, 'laporan_id' => $LAP, 'sumber_dana' => 'apbd_kabkota',
        ] + $payload);
        cek((string) skalar('SELECT unit FROM rd_perumahan_baris WHERE laporan_id = ?
            AND sumber_dana = ? AND program = ?', [$LAP, 'apbd_kabkota', 'pk_rtlh']) === '12',
            "Ditolak server, angka lama utuh: {$label}");
    }

    // ------------------------------------------------------------- scope
    $t = csrf('lain', 'Rekam_Perumahan');
    http('lain', 'Rekam_Perumahan/simpan_gerbang', [
        'csrf_kpkp_token' => $t, 'laporan_id' => $LAP,
        'sumber_dana' => 'csr', 'ada' => '1',
    ]);
    cek(skalar('SELECT ada FROM rd_perumahan_bagian WHERE laporan_id = ? AND sumber_dana = ?',
        [$LAP, 'csr']) === NULL, 'Admin wilayah lain tidak bisa menulis laporan ini');

    $t = csrf('lain', 'Rekam_Perumahan');
    http('lain', 'Rekam_Perumahan/simpan_angka', [
        'csrf_kpkp_token' => $t, 'laporan_id' => $LAP, 'sumber_dana' => 'apbd_kabkota',
    ] + $isi(999, 999));
    cek((string) skalar('SELECT unit FROM rd_perumahan_baris WHERE laporan_id = ?
        AND sumber_dana = ? AND program = ?', [$LAP, 'apbd_kabkota', 'pk_rtlh']) === '12',
        'Angka tidak berubah oleh admin wilayah lain');

    // -------------------------------------------------- batal centang
    $t = csrf('kab', $url);
    http('kab', 'Rekam_Perumahan/simpan_gerbang', [
        'csrf_kpkp_token' => $t, 'laporan_id' => $LAP,
        'sumber_dana' => 'apbd_kabkota', 'ada' => '0',
    ]);
    cek((int) skalar('SELECT COUNT(*) c FROM rd_perumahan_baris WHERE laporan_id = ?', [$LAP]) === 0,
        'Batal centang sumber dana menyapu angkanya lewat UI');
    cek((int) skalar('SELECT ada FROM rd_perumahan_bagian WHERE laporan_id = ? AND sumber_dana = ?',
        [$LAP, 'apbd_kabkota']) === 0, 'Jawaban "Tidak Ada" tetap tercatat, bukan hilang');

    // ---------------------------------------------------------- terkunci
    $db->query("UPDATE rd_laporan SET status = 'terkirim' WHERE id = {$LAP}");
    // Sengaja memakai sumber yang TIDAK disentuh uji lain. Versi sebelumnya
    // memakai `csr` — sumber yang sama dengan uji scope — sehingga saat guard
    // scope dilepas untuk uji balik, baris `csr` lahir dari sana dan uji ini
    // ikut merah tanpa ada kunci yang benar-benar bocor. Kegagalan beruntun
    // membuat diagnosis kabur; satu mutasi harus memerahkan titik yang tepat.
    $t = csrf('kab', $url);
    http('kab', 'Rekam_Perumahan/simpan_gerbang', [
        'csrf_kpkp_token' => $t, 'laporan_id' => $LAP,
        'sumber_dana' => 'baznas_ri', 'ada' => '1',
    ]);
    cek(skalar('SELECT ada FROM rd_perumahan_bagian WHERE laporan_id = ? AND sumber_dana = ?',
        [$LAP, 'baznas_ri']) === NULL, 'Laporan terkirim tidak bisa ditulis kabupaten');
    $res = http('kab', $url);
    cek(strpos($res['body'], 'terkunci') !== FALSE, 'Layar menjelaskan laporan terkunci');

} finally {
    bersihkan();
}

cek((int) skalar('SELECT COUNT(*) c FROM rd_laporan WHERE tahun = ?', [TAHUN]) === 0,
    'Data uji dibersihkan');

echo "RINGKASAN: {$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
