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
// Dulu BULAN=6. Sejak migrasi 024 periodenya triwulan.
define('TRIWULAN', 2);

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;

/** Penanda waktu mulai — dipakai menyapu draft yang lahir selama run ini. */
$mulai = date('Y-m-d H:i:s', time() - 1);

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
    global $db, $jars, $mulai, $kab_lain;
    $db->query('DELETE FROM rd_laporan WHERE tahun = ' . (int) TAHUN);
    // `pulang()` mengarahkan kembali ke layar index setelah tulisan ditolak,
    // dan index SENGAJA membuat draft periode berjalan. Untuk admin wilayah
    // lain periodenya jatuh ke tahun berjalan, di luar tahun sentinel — jadi
    // draft itu harus disapu terpisah, dibatasi kabupatennya dan waktu run ini.
    if ($kab_lain) {
        $db->query(sprintf("DELETE FROM rd_laporan WHERE kabupaten_id = %d AND created_at >= '%s'",
            (int) $kab_lain, $db->real_escape_string($mulai)));
    }
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
    $res = http('kab', 'Rekam_Perumahan/input');
    cek($res['code'] === 200, 'Layar input terbuka (200)');
    cek(strpos($res['body'], 'Input Capaian Perumahan') !== FALSE, 'Judul layar benar');

    // DIBALIK. Dulu: "Draft periode dibuat otomatis saat layar dibuka". Sejak
    // wizard, input() memakai laporan_periode() yang TIDAK pernah menulis --
    // kalau layar baca ikut melahirkan draft, setiap admin yang cuma menengok
    // mengisi riwayat dengan triwulan kosong yang tidak pernah diniatkan.
    // Draft lahir hanya di mulai().
    cek((int) skalar('SELECT COUNT(*) c FROM rd_laporan WHERE domain = ? AND kabupaten_id = ?
        AND tahun = ?', ['perumahan', $KAB, TAHUN]) === 0,
        'Membuka layar TIDAK melahirkan draft');

    // Kabupaten dari sesi: tidak boleh ada pemilih wilayah di layar mana pun.
    cek(stripos($res['body'], 'name="kabupaten') === FALSE, 'Nol input wilayah di layar');

    http('kab', 'Rekam_Perumahan/mulai', [
        'csrf_kpkp_token' => csrf('kab', 'Rekam_Perumahan/input'),
        'tahun' => TAHUN, 'triwulan' => TRIWULAN]);
    $laporan = q('SELECT id, status FROM rd_laporan WHERE domain = ? AND kabupaten_id = ?
        AND tahun = ? AND triwulan = ?', ['perumahan', $KAB, TAHUN, TRIWULAN]);
    wajib($laporan && $laporan['status'] === 'draft', 'Draft lahir setelah periode dipilih');
    $LAP = (int) $laporan['id'];
    $url = 'Rekam_Perumahan/input?laporan=' . $LAP;

    http('kab', 'Rekam_Perumahan/mulai', [
        'csrf_kpkp_token' => csrf('kab', $url),
        'tahun' => TAHUN, 'triwulan' => TRIWULAN]);
    cek((int) skalar('SELECT COUNT(*) c FROM rd_laporan WHERE domain = ? AND kabupaten_id = ?
        AND tahun = ? AND triwulan = ?', ['perumahan', $KAB, TAHUN, TRIWULAN]) === 1,
        'Memilih periode yang sama dua kali tidak membuat laporan kedua');

    // ------------------------------------------------------------ CSRF
    // Gerbangnya kini per PROGRAM, jadi endpoint tanpa-token yang diuji ikut
    // berganti -- tapi yang dijaga sama: 403 DAN tidak menulis apa pun.
    $tanpa_token = http('kab', 'Rekam_Perumahan/simpan_program', [
        'laporan_id' => $LAP, 'program' => ['pk_rtlh'],
    ]);
    cek($tanpa_token['code'] === 403, 'POST tanpa token CSRF ditolak 403');
    cek((int) skalar('SELECT COUNT(*) c FROM rd_perumahan_program WHERE laporan_id = ?', [$LAP]) === 0,
        'POST tanpa CSRF tidak menulis apa pun');

    // ------------------------------------------------------------ gerbang
    $PROGRAM = ['pk_rtlh', 'pb_rtlh', 'pb_backlog', 'pk_bencana', 'pb_bencana', 'pb_relokasi'];
    http('kab', 'Rekam_Perumahan/simpan_program', [
        'csrf_kpkp_token' => csrf('kab', $url), 'laporan_id' => $LAP,
        'program' => $PROGRAM,
    ]);
    cek((int) skalar('SELECT COUNT(*) c FROM rd_perumahan_program WHERE laporan_id = ?', [$LAP]) === 6,
        'Enam program tercentang');

    // DUA BELAS sumber dana, bukan sepuluh -- migrasi 023 menambah apbd_provinsi
    // dan baznas_provinsi. Dihitung dari daftarnya, bukan angka ketikan, supaya
    // uji ini tidak jadi kalimat berikutnya yang rot saat daftarnya berubah.
    $isian_html = http('kab', $url . '&langkah=isian&program=pk_rtlh')['body'];
    $SUMBER = ['apbd_provinsi', 'apbd_kabkota', 'apbn_bsps', 'apbn_dak', 'apbn_kemensos',
        'apbn_dana_desa', 'apbn_kl_lain', 'baznas_ri', 'baznas_provinsi',
        'baznas_kabkota', 'csr', 'dana_lainnya'];
    $ada_opsi = 0;
    foreach ($SUMBER as $kode) {
        if (strpos($isian_html, '<option value="' . $kode . '"') !== FALSE) { $ada_opsi++; }
    }
    cek($ada_opsi === count($SUMBER),
        count($SUMBER) . ' sumber dana dirender sebagai pilihan');

    // ------------------------------------------------------------ angka
    $simpan = function ($program, $unit, $anggaran) use ($LAP, $url) {
        return http('kab', 'Rekam_Perumahan/simpan_sumber', [
            'csrf_kpkp_token' => csrf('kab', $url), 'laporan_id' => $LAP,
            'program' => $program, 'sumber_dana' => 'apbd_kabkota',
            'rencana_unit' => $unit, 'rencana_anggaran' => $anggaran,
            'realisasi_unit' => $unit, 'realisasi_anggaran' => $anggaran]);
    };

    foreach ($PROGRAM as $prog) { $simpan($prog, 12, 3000000000); }
    cek((int) skalar('SELECT COUNT(*) c FROM rd_perumahan_baris WHERE laporan_id = ?', [$LAP]) === 6,
        'Enam baris angka tersimpan, satu per program');
    cek((string) skalar('SELECT realisasi_anggaran FROM rd_perumahan_baris WHERE laporan_id = ?
        AND sumber_dana = ? AND program = ?', [$LAP, 'apbd_kabkota', 'pk_rtlh']) === '3000000000',
        'Anggaran tersimpan rupiah penuh, tanpa pembulatan');

    foreach ($PROGRAM as $prog) { $simpan($prog, 12, 3000000000); }
    cek((int) skalar('SELECT COUNT(*) c FROM rd_perumahan_baris WHERE laporan_id = ?', [$LAP]) === 6,
        'Simpan dua kali mengubah, tidak menggandakan baris');

    // ------------------------------------------------------ tolakan server
    foreach ([
        'unit negatif'        => [-5, 1000],
        'unit bukan angka'    => ['dua', 1000],
        'anggaran tanpa unit' => [0, 500000000],
    ] as $label => $pasangan) {
        $simpan('pk_rtlh', $pasangan[0], $pasangan[1]);
        cek((int) skalar('SELECT realisasi_unit FROM rd_perumahan_baris WHERE laporan_id = ?
            AND sumber_dana = ? AND program = ?', [$LAP, 'apbd_kabkota', 'pk_rtlh']) === 12,
            "Ditolak server, angka lama utuh: {$label}");
    }

    // ------------------------------------------------------------- scope
    $lain_url = 'Rekam_Perumahan/input';
    http('lain', 'Rekam_Perumahan/simpan_program', [
        'csrf_kpkp_token' => csrf('lain', $lain_url), 'laporan_id' => $LAP,
        'program' => ['pk_rtlh', 'pb_rtlh'],
    ]);
    cek((int) skalar('SELECT COUNT(*) c FROM rd_perumahan_program WHERE laporan_id = ?', [$LAP]) === 6,
        'Admin wilayah lain tidak bisa mengubah gerbang laporan ini');

    http('lain', 'Rekam_Perumahan/simpan_sumber', [
        'csrf_kpkp_token' => csrf('lain', $lain_url), 'laporan_id' => $LAP,
        'program' => 'pk_rtlh', 'sumber_dana' => 'apbd_kabkota',
        'rencana_unit' => 999, 'rencana_anggaran' => 999,
        'realisasi_unit' => 999, 'realisasi_anggaran' => 999,
    ]);
    cek((int) skalar('SELECT realisasi_unit FROM rd_perumahan_baris WHERE laporan_id = ?
        AND sumber_dana = ? AND program = ?', [$LAP, 'apbd_kabkota', 'pk_rtlh']) === 12,
        'Angka tidak berubah oleh admin wilayah lain');

    // ------------------------------------------------ batal centang program
    // Dulu ini mencabut SUMBER DANA dan memeriksa kolom `ada` tetap tercatat 0
    // ("Tidak Ada" adalah jawaban, bukan kekosongan). Bentuknya berubah: gerbang
    // kini KEBERADAAN BARIS di rd_perumahan_program, jadi tidak ada lagi `ada`
    // yang bisa bernilai 0 -- mencabut berarti barisnya hilang. Yang dijaga tetap
    // sama dan justru lebih penting: angkanya tidak boleh tertinggal jadi yatim.
    http('kab', 'Rekam_Perumahan/simpan_program', [
        'csrf_kpkp_token' => csrf('kab', $url), 'laporan_id' => $LAP,
        'program' => ['pb_rtlh'],
    ]);
    cek((int) skalar('SELECT COUNT(*) c FROM rd_perumahan_program WHERE laporan_id = ?', [$LAP]) === 1,
        'Program yang dicabut hilang dari gerbang');
    cek((int) skalar('SELECT COUNT(*) c FROM rd_perumahan_baris WHERE laporan_id = ?
        AND program = ?', [$LAP, 'pk_rtlh']) === 0,
        'Mencabut program menyapu angkanya, nol baris yatim');
    cek((int) skalar('SELECT COUNT(*) c FROM rd_perumahan_baris WHERE laporan_id = ?
        AND program = ?', [$LAP, 'pb_rtlh']) === 1,
        'Program yang MASIH dicentang angkanya utuh (bukan disapu semua)');

    // ---------------------------------------------------------- terkunci
    $db->query("UPDATE rd_laporan SET status = 'terkirim' WHERE id = {$LAP}");
    http('kab', 'Rekam_Perumahan/simpan_program', [
        'csrf_kpkp_token' => csrf('kab', $url), 'laporan_id' => $LAP,
        'program' => $PROGRAM,
    ]);
    cek((int) skalar('SELECT COUNT(*) c FROM rd_perumahan_program WHERE laporan_id = ?', [$LAP]) === 1,
        'Laporan terkirim tidak bisa ditulis kabupaten');
    $res = http('kab', $url);
    cek(strpos($res['body'], 'terkunci') !== FALSE, 'Layar menjelaskan laporan terkunci');

} finally {
    bersihkan();
}

cek((int) skalar('SELECT COUNT(*) c FROM rd_laporan WHERE tahun = ?', [TAHUN]) === 0,
    'Data uji dibersihkan');

echo "RINGKASAN: {$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
