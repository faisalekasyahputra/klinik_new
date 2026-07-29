<?php
/**
 * Uji D6 — Rekam Data: peninjauan provinsi oleh Admin Bidang.
 *
 * Menempuh perjalanan penuh: kabupaten mengisi dan mengirim, provinsi meminta
 * perbaikan, kabupaten memperbaiki dan mengirim ulang, provinsi menerima.
 * Yang dijaga: bidang perumahan dan bidang kawasan saling tertutup, catatan
 * perbaikan benar-benar sampai ke layar kabupaten, kirim ulang memakai laporan
 * YANG SAMA, dan keputusan kena rate limit yang sudah ada.
 *
 *   php docs/engineering/uji_rekam_data_d6.php
 *
 * Env opsional: UJI_BASE_URL, UJI_ADMIN_EMAIL, UJI_ADMIN_PASSWORD
 * Tahun sentinel 2099; seluruh jejaknya dihapus di akhir.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('ENV_PATH', APP_ROOT . '/.env');
define('ADMIN_EMAIL', getenv('UJI_ADMIN_EMAIL') ?: 'adminkabkota@example.com');
define('ADMIN_PASSWORD', getenv('UJI_ADMIN_PASSWORD') ?: 'password');
define('TAHUN', 2099);
define('BULAN', 6);

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;

/** Penanda waktu mulai — dipakai membersihkan bucket rate limit run ini saja. */
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
    if ( ! isset($jars[$nama])) {
        $jars[$nama] = tempnam(sys_get_temp_dir(), 'ujid6_');
    }
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

function login($nama, $email, $password) {
    return http($nama, 'Auth/do_login', [
        'csrf_kpkp_token' => csrf($nama, 'Auth/login'),
        'email' => $email, 'password' => $password,
    ])['code'] === 200;
}

function bersihkan() {
    global $db, $jars, $mulai;
    $db->query('DELETE FROM rd_laporan WHERE tahun = ' . (int) TAHUN);

    // Bucket rate limit yang tersentuh selama run ini WAJIB dibersihkan.
    // Kuncinya SHA-256 dan nilai dimensinya tidak pernah disimpan, jadi tidak
    // bisa dicocokkan ke akun uji — tapi dimensi `ip` dipakai sebagai kunci
    // TERPISAH, dan burst 34 permintaan di akhir run memblokir run berikutnya
    // dari IP yang sama selama satu window penuh. Tanpa ini harness tidak bisa
    // dijalankan dua kali beruntun, dan kegagalannya terlihat seperti bug kode.
    $db->query("DELETE FROM sys_rate_limits WHERE window_started_at >= '"
        . $db->real_escape_string($mulai) . "'");

    $db->query("DELETE FROM usr_users WHERE email LIKE 'uji_rd_d6_%'");
    foreach ($jars as $f) {
        @unlink($f);
    }
}

// ---------------------------------------------------------------- prasyarat

echo "Uji D6 — Peninjauan provinsi\n";

$admin = q('SELECT id, kabupaten_id FROM usr_users WHERE email = ? AND role = ?',
    [ADMIN_EMAIL, 'admin_kabkota']);
wajib($admin && ! empty($admin['kabupaten_id']), 'Akun admin_kabkota tersedia dan ter-scope');
$KAB = (int) $admin['kabupaten_id'];

$stamp = time();
$bidang = [];
foreach (['perumahan', 'kawasan'] as $kode) {
    $email = "uji_rd_d6_{$kode}_{$stamp}@example.test";
    $db->query(sprintf(
        "INSERT INTO usr_users (email, password, role, bidang_kode, name, username)
         VALUES ('%s', '%s', 'admin_bidang', '%s', 'Uji D6 %s', 'uji_rd_d6_%s_%d')",
        $db->real_escape_string($email),
        $db->real_escape_string(password_hash('UjiRdD6!', PASSWORD_BCRYPT)),
        $db->real_escape_string($kode), $db->real_escape_string($kode),
        $db->real_escape_string($kode), $stamp));
    $bidang[$kode] = $email;
}

try {
    wajib(login('kab', ADMIN_EMAIL, ADMIN_PASSWORD), 'Login admin_kabkota berhasil');
    wajib(login('bid_p', $bidang['perumahan'], 'UjiRdD6!'), 'Login Admin Bidang Perumahan berhasil');
    wajib(login('bid_k', $bidang['kawasan'], 'UjiRdD6!'), 'Login Admin Bidang Kawasan berhasil');

    // ------------------------------------------ kabupaten mengisi & mengirim
    $url = 'Rekam_Perumahan?tahun=' . TAHUN . '&bulan=' . BULAN;
    http('kab', $url);
    $LAP = skalar_int('SELECT id FROM rd_laporan WHERE domain = ? AND kabupaten_id = ?
        AND tahun = ? AND bulan = ?', ['perumahan', $KAB, TAHUN, BULAN]);
    wajib($LAP > 0, 'Draft perumahan kabupaten tersedia');

    // Draft belum boleh terlihat peninjau.
    $daftar = http('bid_p', 'Rekam_Tinjauan?tahun=' . TAHUN)['body'];
    cek(strpos($daftar, 'Belum ada laporan yang dikirim') !== FALSE,
        'Draft kabupaten tidak muncul di daftar peninjauan');

    foreach (['apbd_kabkota', 'apbn_bsps', 'apbn_dak', 'apbn_kemensos', 'apbn_dana_desa',
        'apbn_kl_lain', 'baznas_ri', 'baznas_kabkota', 'csr', 'dana_lainnya'] as $i => $kode) {
        http('kab', 'Rekam_Perumahan/simpan_gerbang', [
            'csrf_kpkp_token' => csrf('kab', $url), 'laporan_id' => $LAP,
            'sumber_dana' => $kode, 'ada' => $i === 0 ? '1' : '0']);
    }
    http('kab', 'Rekam_Perumahan/simpan_angka', [
        'csrf_kpkp_token' => csrf('kab', $url), 'laporan_id' => $LAP,
        'sumber_dana' => 'apbd_kabkota',
        'program[pk_rtlh][unit]' => 30, 'program[pk_rtlh][anggaran]' => 6000000000]);
    http('kab', 'Rekam_Perumahan/kirim', [
        'csrf_kpkp_token' => csrf('kab', $url), 'laporan_id' => $LAP]);
    wajib(skalar_str('SELECT status FROM rd_laporan WHERE id = ?', [$LAP]) === 'terkirim',
        'Laporan kabupaten terkirim');

    // -------------------------------------------------- daftar peninjauan
    $daftar = http('bid_p', 'Rekam_Tinjauan?tahun=' . TAHUN)['body'];
    cek(strpos($daftar, 'Menunggu ditinjau') !== FALSE, 'Laporan terkirim muncul di daftar bidang perumahan');
    cek(strpos($daftar, 'Rekam_Tinjauan/detail/' . $LAP) !== FALSE, 'Tautan periksa mengarah ke laporan itu');

    // ------------------------------------------------ bidang saling tertutup
    $lintas = http('bid_k', 'Rekam_Tinjauan/detail/' . $LAP);
    cek($lintas['code'] === 404, 'Bidang Kawasan tidak bisa membuka laporan perumahan');
    cek(strpos($lintas['body'], '6.000.000.000') === FALSE, 'Nol angka perumahan bocor ke bidang kawasan');

    $daftar_k = http('bid_k', 'Rekam_Tinjauan?tahun=' . TAHUN)['body'];
    cek(strpos($daftar_k, 'Belum ada laporan yang dikirim') !== FALSE,
        'Daftar bidang kawasan tidak memuat laporan perumahan');

    $detail = http('bid_p', 'Rekam_Tinjauan/detail/' . $LAP);
    cek($detail['code'] === 200, 'Bidang Perumahan bisa membuka laporannya');
    cek(strpos($detail['body'], '6.000.000.000') !== FALSE, 'Detail menampilkan angka yang dilaporkan');
    cek(strpos($detail['body'], 'kumulatif sampai dengan') !== FALSE,
        'Detail menegaskan angkanya kumulatif, bukan capaian bulan itu saja');

    // -------------------------------------- minta perbaikan tanpa catatan
    http('bid_p', 'Rekam_Tinjauan/minta_perbaikan', [
        'csrf_kpkp_token' => csrf('bid_p', 'Rekam_Tinjauan/detail/' . $LAP),
        'laporan_id' => $LAP, 'catatan_admin' => '']);
    cek(skalar_str('SELECT status FROM rd_laporan WHERE id = ?', [$LAP]) === 'terkirim',
        'Minta perbaikan tanpa catatan ditolak server');

    // ------------------------------------------- minta perbaikan sah
    $catatan = 'Angka BSPS belum diisi, mohon dilengkapi.';
    http('bid_p', 'Rekam_Tinjauan/minta_perbaikan', [
        'csrf_kpkp_token' => csrf('bid_p', 'Rekam_Tinjauan/detail/' . $LAP),
        'laporan_id' => $LAP, 'catatan_admin' => $catatan]);
    cek(skalar_str('SELECT status FROM rd_laporan WHERE id = ?', [$LAP]) === 'perlu_perbaikan',
        'Minta perbaikan dengan catatan berhasil');
    cek(skalar_str('SELECT catatan_admin FROM rd_laporan WHERE id = ?', [$LAP]) === $catatan,
        'Catatan tersimpan apa adanya');
    cek(skalar_int('SELECT reviewed_by FROM rd_laporan WHERE id = ?', [$LAP]) > 0,
        'Peninjau tercatat');

    // ------------------------------- catatan benar-benar sampai ke kabupaten
    $layar_kab = http('kab', $url)['body'];
    cek(strpos($layar_kab, $catatan) !== FALSE,
        'Catatan peninjau tampil di layar input kabupaten');
    $riwayat_kab = http('kab', 'Rekam_Perumahan/riwayat?tahun=' . TAHUN)['body'];
    cek(strpos($riwayat_kab, $catatan) !== FALSE, 'Catatan juga tampil di riwayat kabupaten');
    cek(strpos($layar_kab, 'name="sumber_dana"') !== FALSE,
        'Kabupaten bisa menyunting lagi setelah dikembalikan');

    // ----------------------------------- kirim ulang memakai laporan yang SAMA
    http('kab', 'Rekam_Perumahan/simpan_gerbang', [
        'csrf_kpkp_token' => csrf('kab', $url), 'laporan_id' => $LAP,
        'sumber_dana' => 'apbn_bsps', 'ada' => '1']);
    http('kab', 'Rekam_Perumahan/simpan_angka', [
        'csrf_kpkp_token' => csrf('kab', $url), 'laporan_id' => $LAP,
        'sumber_dana' => 'apbn_bsps',
        'program[pk_rtlh][unit]' => 5, 'program[pk_rtlh][anggaran]' => 1000000000]);
    http('kab', 'Rekam_Perumahan/kirim', [
        'csrf_kpkp_token' => csrf('kab', $url), 'laporan_id' => $LAP]);

    cek(skalar_int('SELECT COUNT(*) c FROM rd_laporan WHERE domain = ? AND kabupaten_id = ?
        AND tahun = ? AND bulan = ?', ['perumahan', $KAB, TAHUN, BULAN]) === 1,
        'Kirim ulang TIDAK membuat periode baru');
    cek(skalar_str('SELECT status FROM rd_laporan WHERE id = ?', [$LAP]) === 'terkirim',
        'Laporan yang sama kembali berstatus terkirim');
    cek(skalar_str('SELECT catatan_admin FROM rd_laporan WHERE id = ?', [$LAP]) === '',
        'Kirim ulang membersihkan catatan perbaikan lama');
    cek(skalar_str('SELECT reviewed_at FROM rd_laporan WHERE id = ?', [$LAP]) === '',
        'Kirim ulang membersihkan jejak peninjauan lama');

    // ------------------------------------------------ terima + rate limit
    // Token diambil SEKARANG, selagi laporan masih `terkirim` dan belum
    // ditinjau — hanya pada keadaan itu form keputusan dirender, jadi hanya
    // saat itu halaman detail memuat token CSRF. Mengambilnya sesudah diterima
    // menghasilkan token KOSONG dan seluruh POST ditolak 403 sebelum menyentuh
    // limiter; uji rate limit lalu "gagal" tanpa ada yang rusak.
    $token = csrf('bid_p', 'Rekam_Tinjauan/detail/' . $LAP);
    cek($token !== '', 'Layar keputusan merender token CSRF selagi laporan menunggu ditinjau');

    http('bid_p', 'Rekam_Tinjauan/terima', ['csrf_kpkp_token' => $token, 'laporan_id' => $LAP]);
    cek(skalar_str('SELECT reviewed_at FROM rd_laporan WHERE id = ?', [$LAP]) !== '',
        'Terima menandai laporan sudah ditinjau');
    cek(skalar_str('SELECT status FROM rd_laporan WHERE id = ?', [$LAP]) === 'terkirim',
        'Status tetap terkirim — "diterima" bukan status tersendiri di skema');

    $daftar = http('bid_p', 'Rekam_Tinjauan?tahun=' . TAHUN)['body'];
    cek(strpos($daftar, 'Diterima') !== FALSE, 'Daftar menampilkan status Diterima');

    $waktu_terima = skalar_str('SELECT reviewed_at FROM rd_laporan WHERE id = ?', [$LAP]);
    http('bid_p', 'Rekam_Tinjauan/terima', ['csrf_kpkp_token' => $token, 'laporan_id' => $LAP]);
    cek(skalar_str('SELECT reviewed_at FROM rd_laporan WHERE id = ?', [$LAP]) === $waktu_terima,
        'Terima dua kali ditolak, stempel tidak berubah');

    // kabupaten tetap terkunci sesudah diterima
    $layar_kab = http('kab', $url)['body'];
    cek(strpos($layar_kab, 'terkunci') !== FALSE, 'Kabupaten tetap terkunci setelah diterima');

    // ------------------------------------------------------- rate limit
    // Policy `admin_queue_decision`: 30 per 60 detik, dimensi ip+account+object.
    // Token yang sama dipakai berulang — `csrf_regenerate` FALSE di repo ini,
    // dan mengambil ulang tiap iterasi menambah 34 GET yang bisa membuat burst
    // melampaui window 60 detik sehingga counter-nya keburu direset.
    $kode_429 = 0;
    $kode_semua = [];
    for ($i = 1; $i <= 34; $i++) {
        $r = http('bid_p', 'Rekam_Tinjauan/terima', [
            'csrf_kpkp_token' => $token, 'laporan_id' => $LAP], FALSE);
        $kode_semua[] = $r['code'];
        if ($r['code'] === 429) {
            $kode_429++;
        }
    }
    $ragam = array_count_values($kode_semua);
    cek(($ragam[429] ?? 0) > 0,
        'Keputusan berulang kena rate limit 429 (policy admin_queue_decision)');
    // Penjaga terhadap kesalahan yang baru saja terjadi di harness ini: kalau
    // tokennya kosong, seluruh burst ditolak 403 sebelum menyentuh limiter dan
    // uji di atas "gagal" tanpa ada yang rusak.
    cek(($ragam[403] ?? 0) === 0, 'Nol penolakan CSRF — burst benar-benar mencapai limiter');

} finally {
    bersihkan();
}

cek(skalar_int('SELECT COUNT(*) c FROM rd_laporan WHERE tahun = ?', [TAHUN]) === 0,
    'Data uji dibersihkan');

echo "RINGKASAN: {$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
