<?php
/**
 * Uji W6 — wizard Rekam Data Perumahan (bentuk baru: triwulan, gerbang per
 * program, Rencana + Realisasi).
 *
 * Menempuh lintasan penuh lewat HTTP sungguhan: pilih periode -> pilih program
 * -> tambah sumber dana -> ubah -> hapus -> kirim -> tinjau -> minta perbaikan
 * -> kirim ulang. Ditambah uji negatif yang justru jadi intinya.
 *
 *   php docs/engineering/uji_wizard_rekam_perumahan.php
 *
 * Env opsional: UJI_BASE_URL, UJI_ADMIN_EMAIL, UJI_ADMIN_PASSWORD
 * Tahun sentinel 2099; seluruh jejaknya dihapus di akhir.
 *
 * DEFINISI SELESAI skrip ini BUKAN "hijau sekali". Balikkan satu perbaikan —
 * misalnya lepas sisi `rencana` dari aturan anggaran-tanpa-unit di model — dan
 * skrip ini WAJIB merah di titik yang diramalkan. Skrip yang tidak pernah gagal
 * tidak membuktikan apa pun.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('ENV_PATH', APP_ROOT . '/.env');
define('ADMIN_EMAIL', getenv('UJI_ADMIN_EMAIL') ?: 'adminkabkota@example.com');
define('ADMIN_PASSWORD', getenv('UJI_ADMIN_PASSWORD') ?: 'password');
define('TAHUN', 2099);

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;

/** Penanda waktu mulai — dipakai membersihkan bucket rate limit run ini saja. */
$mulai = date('Y-m-d H:i:s', time() - 1);

function cek($condition, $label) {
    $GLOBALS['uji_total']++;
    echo ($condition ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $condition) { $GLOBALS['uji_gagal']++; }
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
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === FALSE) { continue; }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        if ( ! array_key_exists($key, $out)) { $out[$key] = trim($value); }
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
    if ($params) { $stmt->bind_param(str_repeat('s', count($params)), ...$params); }
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : NULL;
    $stmt->close();
    return $row;
}

/**
 * Lampirkan BNBA langsung ke DB — BNBA WAJIB sejak 5 Agt 2026 (butir C1),
 * dan `Rekam_data_model::kirim()` menolak laporan perumahan tanpa lampirannya.
 *
 * Ditulis langsung, bukan lewat `unggah_bnba`: yang diuji berkas ini bukan alur
 * unggahnya (gerbangnya diuji dua arah di [L5] di bawah), melainkan
 * penyiapan laporan KEDUA dan seterusnya. Menempuh ulang
 * unggahannya cuma menyalin cakupan dan menambah sebab gagal yang bukan milik
 * uji ini. Baris ini ikut terhapus bersama laporannya lewat FK.
 */
function lampirkan_bnba($laporan_id) {
    q('INSERT INTO rd_perumahan_bnba (laporan_id, nama_asli, private_path, mime_type, ukuran)
       VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE nama_asli = VALUES(nama_asli)',
       [(int) $laporan_id, 'bnba-uji.pdf', 'uji/bnba-uji.pdf', 'application/pdf', 1024]);
}

/** Prepared statement mengembalikan tipe native — selalu di-cast (AGENTS.md §0e). */
function skalar_int($sql, $params = []) {
    $row = q($sql, $params);
    return $row ? (int) reset($row) : 0;
}

function skalar_str($sql, $params = []) {
    $row = q($sql, $params);
    $v = $row ? reset($row) : NULL;
    return $v === NULL ? '' : (string) $v;
}

// ---------------------------------------------------------------- HTTP

$jars = [];

function jar($nama) {
    global $jars;
    if ( ! isset($jars[$nama])) { $jars[$nama] = tempnam(sys_get_temp_dir(), 'ujiw6_'); }
    return $jars[$nama];
}

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
    $body = (string) curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body];
}

function csrf($nama, $path) {
    $res = http($nama, $path);
    return preg_match('/name="csrf_kpkp_token" value="([^"]+)"/', $res['body'], $m) ? $m[1] : '';
}

/**
 * Login WAJIB diperiksa dari isinya, bukan dari kode HTTP.
 *
 * `Auth::do_login()` membalas 303 lalu curl mengikutinya ke halaman 200 —
 * dan login yang GAGAL juga berakhir 200 di halaman login. Memeriksa kode
 * status saja membuat seluruh sisa skrip berjalan tanpa sesi sambil melapor
 * hijau. Itu nyata terjadi saat skrip ini pertama ditulis.
 */
function login($nama, $email, $password) {
    $t = csrf($nama, 'Auth/login');
    if ($t === '') { return FALSE; }

    $ch = curl_init(BASE_URL . '/Auth/do_login');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_COOKIEJAR      => jar($nama),
        CURLOPT_COOKIEFILE     => jar($nama),
        CURLOPT_POST           => TRUE,
        CURLOPT_HTTPHEADER     => ['X-Requested-With: XMLHttpRequest'],
        CURLOPT_POSTFIELDS     => http_build_query([
            'csrf_kpkp_token' => $t, 'email' => $email, 'password' => $password,
        ]),
        CURLOPT_TIMEOUT        => 30,
    ]);
    $body = (string) curl_exec($ch);
    curl_close($ch);

    $json = json_decode($body, TRUE);
    return ($json['status'] ?? '') === 'success';
}

/** POST ke wizard dengan token segar dari halaman yang MERENDER form-nya. */
function kirim_form($nama, $halaman, $aksi, array $data) {
    $data['csrf_kpkp_token'] = csrf($nama, $halaman);
    return http($nama, $aksi, $data);
}

function bersihkan() {
    global $db, $jars, $mulai;
    $db->query('DELETE FROM rd_laporan WHERE tahun = ' . (int) TAHUN);

    // Bucket rate limit yang tersentuh selama run ini WAJIB dibersihkan.
    // Dimensi `ip` dipakai sebagai kunci terpisah, dan burst permintaan di akhir
    // run memblokir run berikutnya dari IP yang sama selama satu window penuh.
    // Tanpa ini skrip tidak bisa dijalankan dua kali beruntun, dan kegagalannya
    // terlihat seperti bug kode.
    $db->query("DELETE FROM sys_rate_limits WHERE window_started_at >= '"
        . $db->real_escape_string($mulai) . "'");

    $db->query("DELETE FROM usr_users WHERE email LIKE 'uji_w6_%'");
    foreach ($jars as $f) { @unlink($f); }
}

// ---------------------------------------------------------------- prasyarat

echo "Uji W6 — Wizard Rekam Data Perumahan\n";

$admin = q('SELECT id, kabupaten_id FROM usr_users WHERE email = ? AND role = ?',
    [ADMIN_EMAIL, 'admin_kabkota']);
wajib($admin && ! empty($admin['kabupaten_id']), 'Akun admin_kabkota tersedia dan ter-scope');
$KAB = (int) $admin['kabupaten_id'];

// Bersihkan SISA RUN SEBELUMNYA dulu — `bersihkan()` menghapus seluruh akun
// `uji_w6_%`, jadi memanggilnya SESUDAH membuat peninjau akan menghapus akun
// yang baru saja dibuat. Itu persis yang terjadi saat skrip ini pertama
// dijalankan: login diam-diam gagal, dan tiga pemeriksaan peninjauan merah
// tanpa ada yang salah di kode aplikasi.
bersihkan();

// Peninjau bidang perumahan, dibuat sesaat lalu dihapus di akhir.
$stamp = time();
$email_bidang = "uji_w6_perumahan_{$stamp}@example.test";
$db->query(sprintf(
    "INSERT INTO usr_users (email, password, role, bidang_kode, name, username)
     VALUES ('%s', '%s', 'admin_bidang', 'perumahan', 'Uji W6', 'uji_w6_%d')",
    $db->real_escape_string($email_bidang),
    $db->real_escape_string(password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT)),
    $stamp));
wajib($db->affected_rows === 1, 'Peninjau bidang perumahan dibuat');

wajib(login('kab', ADMIN_EMAIL, ADMIN_PASSWORD), 'Login admin_kabkota');
wajib(login('bid', $email_bidang, ADMIN_PASSWORD), 'Login admin_bidang perumahan');

try {
    // ------------------------------------------------- L1 periode
    echo "\n[L1] Periode Pelaporan\n";

    $sebelum = skalar_int('SELECT COUNT(*) AS n FROM rd_laporan');
    http('kab', 'Rekam_Perumahan/input');
    http('kab', 'Rekam_Perumahan/input');
    cek(skalar_int('SELECT COUNT(*) AS n FROM rd_laporan') === $sebelum,
        'Membuka layar periode TIDAK melahirkan draft');

    $r = http('kab', 'Rekam_Perumahan/input');
    cek(strpos($r['body'], 'Periode Pelaporan') !== FALSE, 'Layar periode tampil');
    cek(stripos($r['body'], 'triwulan ini saja') !== FALSE,
        'Label anti-kumulatif ada di layar, bukan catatan kaki');

    kirim_form('kab', 'Rekam_Perumahan/input', 'Rekam_Perumahan/mulai',
        ['tahun' => TAHUN, 'triwulan' => 9]);
    cek(skalar_int('SELECT COUNT(*) AS n FROM rd_laporan WHERE tahun = ?', [TAHUN]) === 0,
        'Triwulan 9 ditolak — nol laporan lahir');

    kirim_form('kab', 'Rekam_Perumahan/input', 'Rekam_Perumahan/mulai',
        ['tahun' => TAHUN, 'triwulan' => 1]);
    $ID1 = skalar_int('SELECT id AS n FROM rd_laporan WHERE tahun = ? AND triwulan = 1 AND domain = ?',
        [TAHUN, 'perumahan']);
    wajib($ID1 > 0, 'Draft TW I lahir setelah Lanjut ditekan');

    // ------------------------------------------------- L2 program
    echo "\n[L2] Program yang dilaporkan\n";

    $halaman = 'Rekam_Perumahan/input?laporan=' . $ID1 . '&langkah=program';
    $r = http('kab', $halaman);
    cek(substr_count($r['body'], 'name="program[]"') === 6, 'Enam checkbox program tampil');

    kirim_form('kab', $halaman, 'Rekam_Perumahan/simpan_program',
        ['laporan_id' => $ID1, 'program' => ['pk_rtlh', 'pb_backlog']]);
    cek(skalar_int('SELECT COUNT(*) AS n FROM rd_perumahan_program WHERE laporan_id = ?', [$ID1]) === 2,
        'Dua program tercentang');

    // ------------------------------------------------- L3 isian
    echo "\n[L3] Isian per program\n";

    $isian = 'Rekam_Perumahan/input?laporan=' . $ID1 . '&langkah=isian&program=pk_rtlh';
    cek(strpos(http('kab', $isian)['body'], 'Belum ada data') !== FALSE,
        'Keadaan kosong jujur: "Belum ada data"');

    // Negatif — program yang belum dicentang
    kirim_form('kab', $isian, 'Rekam_Perumahan/simpan_sumber', [
        'laporan_id' => $ID1, 'program' => 'pb_relokasi', 'sumber_dana' => 'csr',
        'realisasi_unit' => 5, 'realisasi_anggaran' => 100,
    ]);
    cek(skalar_int('SELECT COUNT(*) AS n FROM rd_perumahan_baris WHERE laporan_id = ? AND program = ?',
        [$ID1, 'pb_relokasi']) === 0, 'Sumber dana untuk program yang belum dicentang DITOLAK');

    // Negatif — anggaran tanpa unit, kedua sisi
    kirim_form('kab', $isian, 'Rekam_Perumahan/simpan_sumber', [
        'laporan_id' => $ID1, 'program' => 'pk_rtlh', 'sumber_dana' => 'apbd_kabkota',
        'realisasi_unit' => 0, 'realisasi_anggaran' => 5000000,
    ]);
    cek(skalar_int('SELECT COUNT(*) AS n FROM rd_perumahan_baris WHERE laporan_id = ?', [$ID1]) === 0,
        'REALISASI: anggaran tanpa unit DITOLAK');

    kirim_form('kab', $isian, 'Rekam_Perumahan/simpan_sumber', [
        'laporan_id' => $ID1, 'program' => 'pk_rtlh', 'sumber_dana' => 'apbd_kabkota',
        'rencana_unit' => 0, 'rencana_anggaran' => 5000000,
    ]);
    cek(skalar_int('SELECT COUNT(*) AS n FROM rd_perumahan_baris WHERE laporan_id = ?', [$ID1]) === 0,
        'RENCANA: anggaran tanpa unit DITOLAK');

    // Positif — empat angka
    kirim_form('kab', $isian, 'Rekam_Perumahan/simpan_sumber', [
        'laporan_id' => $ID1, 'program' => 'pk_rtlh', 'sumber_dana' => 'apbd_kabkota',
        'rencana_unit' => 120, 'rencana_anggaran' => 3000000000,
        'realisasi_unit' => 110, 'realisasi_anggaran' => 2800000000,
    ]);
    cek(skalar_int('SELECT realisasi_unit AS n FROM rd_perumahan_baris
         WHERE laporan_id = ? AND program = ? AND sumber_dana = ?',
        [$ID1, 'pk_rtlh', 'apbd_kabkota']) === 110, 'Empat angka tersimpan');

    // CSR membawa nama perusahaan
    kirim_form('kab', $isian, 'Rekam_Perumahan/simpan_sumber', [
        'laporan_id' => $ID1, 'program' => 'pk_rtlh', 'sumber_dana' => 'csr',
        'rencana_unit' => 20, 'rencana_anggaran' => 300000000,
        'realisasi_unit' => 20, 'realisasi_anggaran' => 300000000,
        'keterangan' => 'PT PLN Indonesia',
    ]);
    cek(skalar_str('SELECT keterangan AS s FROM rd_perumahan_baris
         WHERE laporan_id = ? AND program = ? AND sumber_dana = ?',
        [$ID1, 'pk_rtlh', 'csr']) === 'PT PLN Indonesia', 'Nama perusahaan CSR tersimpan');

    // Ubah — bukan menggandakan
    kirim_form('kab', $isian, 'Rekam_Perumahan/simpan_sumber', [
        'laporan_id' => $ID1, 'program' => 'pk_rtlh', 'sumber_dana' => 'apbd_kabkota',
        'rencana_unit' => 120, 'rencana_anggaran' => 3000000000,
        'realisasi_unit' => 115, 'realisasi_anggaran' => 2900000000,
    ]);
    cek(skalar_int('SELECT COUNT(*) AS n FROM rd_perumahan_baris
         WHERE laporan_id = ? AND program = ? AND sumber_dana = ?',
        [$ID1, 'pk_rtlh', 'apbd_kabkota']) === 1, 'Simpan ulang mengubah, tidak menggandakan');
    cek(skalar_int('SELECT realisasi_unit AS n FROM rd_perumahan_baris
         WHERE laporan_id = ? AND program = ? AND sumber_dana = ?',
        [$ID1, 'pk_rtlh', 'apbd_kabkota']) === 115, 'Nilai terbarui ke 115');

    // Hapus
    kirim_form('kab', $isian, 'Rekam_Perumahan/hapus_sumber', [
        'laporan_id' => $ID1, 'program' => 'pk_rtlh', 'sumber_dana' => 'csr',
    ]);
    cek(skalar_int('SELECT COUNT(*) AS n FROM rd_perumahan_baris
         WHERE laporan_id = ? AND program = ? AND sumber_dana = ?',
        [$ID1, 'pk_rtlh', 'csr']) === 0, 'Hapus satu sumber dana berhasil');

    // ------------------------------------------------- gerbang Kirim
    echo "\n[L5] Gerbang Kirim\n";

    $review = 'Rekam_Perumahan/input?laporan=' . $ID1 . '&langkah=review';
    kirim_form('kab', $review, 'Rekam_Perumahan/kirim', ['laporan_id' => $ID1]);
    cek(skalar_str('SELECT status AS s FROM rd_laporan WHERE id = ?', [$ID1]) === 'draft',
        'Kirim DITOLAK selama pb_backlog dicentang tanpa sumber dana');

    // Cabut program kosong → angkanya ikut tersapu (tidak ada di sini, tapi
    // gerbangnya hilang), lalu kirim
    $halaman = 'Rekam_Perumahan/input?laporan=' . $ID1 . '&langkah=program';
    kirim_form('kab', $halaman, 'Rekam_Perumahan/simpan_program',
        ['laporan_id' => $ID1, 'program' => ['pk_rtlh']]);

    /* GERBANG BNBA (butir C1, 5 Agt 2026). Sampai 4 Agt langkah BNBA boleh
       dilewati dan laporan tetap terkirim. Diuji DUA ARAH di sini, karena satu
       arah saja tidak membuktikan gerbangnya bekerja: */
    $r = kirim_form('kab', $review, 'Rekam_Perumahan/kirim', ['laporan_id' => $ID1]);
    cek(skalar_str('SELECT status AS s FROM rd_laporan WHERE id = ?', [$ID1]) === 'draft',
        'Kirim DITOLAK selama BNBA belum dilampirkan');
    cek(stripos($r['body'], 'BNBA') !== FALSE,
        'Sebab penolakannya menyebut BNBA, bukan galat umum');

    lampirkan_bnba($ID1);
    kirim_form('kab', $review, 'Rekam_Perumahan/kirim', ['laporan_id' => $ID1]);
    cek(skalar_str('SELECT status AS s FROM rd_laporan WHERE id = ?', [$ID1]) === 'terkirim',
        'Kirim diterima sesudah BNBA dilampirkan');

    // Terkunci
    kirim_form('kab', $isian, 'Rekam_Perumahan/simpan_sumber', [
        'laporan_id' => $ID1, 'program' => 'pk_rtlh', 'sumber_dana' => 'apbn_bsps',
        'realisasi_unit' => 9, 'realisasi_anggaran' => 0,
    ]);
    cek(skalar_int('SELECT COUNT(*) AS n FROM rd_perumahan_baris
         WHERE laporan_id = ? AND sumber_dana = ?', [$ID1, 'apbn_bsps']) === 0,
        'Laporan terkirim TERKUNCI dari perubahan');

    // ------------------------------------------------- peninjauan
    echo "\n[Tinjau] Bidang perumahan\n";

    $r = http('bid', 'Rekam_Tinjauan?tahun=' . TAHUN);
    cek(strpos($r['body'], 'Rekam_Tinjauan/detail/' . $ID1) !== FALSE,
        'Laporan terkirim muncul di daftar peninjauan');

    $detail = 'Rekam_Tinjauan/detail/' . $ID1;
    kirim_form('bid', $detail, 'Rekam_Tinjauan/minta_perbaikan',
        ['laporan_id' => $ID1, 'catatan_admin' => 'Angka APBD perlu dicek ulang.']);
    cek(skalar_str('SELECT status AS s FROM rd_laporan WHERE id = ?', [$ID1]) === 'perlu_perbaikan',
        'Peninjau mengembalikan laporan');
    cek(strpos(http('kab', $review)['body'], 'Angka APBD perlu dicek ulang.') !== FALSE,
        'Catatan peninjau SAMPAI ke layar kabupaten');

    lampirkan_bnba($ID1);
    kirim_form('kab', $review, 'Rekam_Perumahan/kirim', ['laporan_id' => $ID1]);
    cek(skalar_str('SELECT status AS s FROM rd_laporan WHERE id = ?', [$ID1]) === 'terkirim',
        'Kirim ulang memakai laporan YANG SAMA');
    cek(skalar_int('SELECT COUNT(*) AS n FROM rd_laporan WHERE tahun = ? AND domain = ?',
        [TAHUN, 'perumahan']) === 1, 'Nol laporan duplikat setelah siklus perbaikan');

    // ------------------------------------------------- KUMULATIF
    echo "\n[Kumulatif] Per triwulan, bukan kumulatif tersimpan\n";

    kirim_form('kab', 'Rekam_Perumahan/input', 'Rekam_Perumahan/mulai',
        ['tahun' => TAHUN, 'triwulan' => 2]);
    $ID2 = skalar_int('SELECT id AS n FROM rd_laporan WHERE tahun = ? AND triwulan = 2 AND domain = ?',
        [TAHUN, 'perumahan']);
    wajib($ID2 > 0, 'Draft TW II lahir');

    cek(skalar_int('SELECT COUNT(*) AS n FROM rd_perumahan_baris WHERE laporan_id = ?', [$ID2]) === 0,
        'TW II lahir KOSONG — nol pewarisan dari TW I');

    $h2 = 'Rekam_Perumahan/input?laporan=' . $ID2 . '&langkah=program';
    kirim_form('kab', $h2, 'Rekam_Perumahan/simpan_program',
        ['laporan_id' => $ID2, 'program' => ['pk_rtlh']]);
    $i2 = 'Rekam_Perumahan/input?laporan=' . $ID2 . '&langkah=isian&program=pk_rtlh';
    kirim_form('kab', $i2, 'Rekam_Perumahan/simpan_sumber', [
        'laporan_id' => $ID2, 'program' => 'pk_rtlh', 'sumber_dana' => 'apbd_kabkota',
        'rencana_unit' => 10, 'rencana_anggaran' => 100000000,
        'realisasi_unit' => 5, 'realisasi_anggaran' => 50000000,
    ]);
    lampirkan_bnba($ID2);
    kirim_form('kab', 'Rekam_Perumahan/input?laporan=' . $ID2 . '&langkah=review',
        'Rekam_Perumahan/kirim', ['laporan_id' => $ID2]);
    wajib(skalar_str('SELECT status AS s FROM rd_laporan WHERE id = ?', [$ID2]) === 'terkirim',
        'TW II terkirim');

    $tw1 = skalar_int('SELECT realisasi_unit AS n FROM rd_perumahan_baris
        WHERE laporan_id = ? AND program = ? AND sumber_dana = ?', [$ID1, 'pk_rtlh', 'apbd_kabkota']);
    $tw2 = skalar_int('SELECT realisasi_unit AS n FROM rd_perumahan_baris
        WHERE laporan_id = ? AND program = ? AND sumber_dana = ?', [$ID2, 'pk_rtlh', 'apbd_kabkota']);
    cek($tw1 === 115 && $tw2 === 5, "Tersimpan PER TRIWULAN (TW I={$tw1}, TW II={$tw2})");

    $r = http('kab', 'Rekam_Perumahan?tahun=' . TAHUN . '&triwulan=2');
    /* Dulu dua asersi terpisah untuk "Tabel Unit Rencana" dan "Tabel Unit
       Realisasi". Sejak butir C2 (revisi dinas 5 Agt 2026) keduanya jadi SATU
       tabel dengan dua baris per sumber dana, jadi yang dijaga sekarang judul
       gabungannya — plus bukti bahwa penanda kedua sisi benar-benar dirender. */
    cek(stripos($r['body'], 'Tabel Unit Rencana &amp; Realisasi') !== FALSE
        || stripos($r['body'], 'Tabel Unit Rencana & Realisasi') !== FALSE,
        'Layar Capaian: satu tabel gabungan Rencana & Realisasi');
    cek(preg_match('/>\s*Rencana\s*</', $r['body']) === 1
        && preg_match_all('/>\s*Realisasi\s*</', $r['body']) >= 1,
        'Layar Capaian: penanda baris Rencana & Realisasi dirender');
    cek(stripos($r['body'], 'Kumulatif Realisasi') !== FALSE, 'Layar Capaian: tabel kumulatif');

    // 115 + 5 = 120, dan HARUS muncul apa adanya — bukan 235 (dobel) dan bukan 5.
    cek(preg_match('/120(?!\d)/', strip_tags($r['body'])) === 1
        || strpos(str_replace('.', '', strip_tags($r['body'])), '120') !== FALSE,
        'Kumulatif 115+5=120 tampil di layar');

    // ------------------------------------------------- scope
    echo "\n[Scope] Wilayah lain\n";

    $lain = q('SELECT id FROM kabupaten WHERE id <> ? ORDER BY id LIMIT 1', [$KAB]);
    if ($lain) {
        $db->query(sprintf(
            "INSERT INTO rd_laporan (domain, kabupaten_id, tahun, triwulan, status)
             VALUES ('perumahan', %d, %d, 4, 'draft')", (int) $lain['id'], (int) TAHUN));
        $ID_LAIN = (int) $db->insert_id;
        kirim_form('kab', 'Rekam_Perumahan/input', 'Rekam_Perumahan/simpan_program',
            ['laporan_id' => $ID_LAIN, 'program' => ['pk_rtlh']]);
        cek(skalar_int('SELECT COUNT(*) AS n FROM rd_perumahan_program WHERE laporan_id = ?',
            [$ID_LAIN]) === 0, 'Laporan kabupaten LAIN ditolak (scope sebagai gerbang)');
    }

} finally {
    bersihkan();
}

cek(skalar_int('SELECT COUNT(*) AS n FROM rd_laporan WHERE tahun = ?', [TAHUN]) === 0,
    'Data uji dibersihkan');

echo "\nRINGKASAN: {$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
