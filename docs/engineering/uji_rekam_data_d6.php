<?php
/**
 * Uji D6 - Rekam Data: peninjauan provinsi oleh Admin Bidang.
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
// Dulu `BULAN = 6` (kumulatif s.d. Juni). Sejak migrasi 024 periodenya triwulan.
define('TRIWULAN', 2);

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;

/** Penanda waktu mulai - dipakai membersihkan bucket rate limit run ini saja. */
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

/**
 * Lampirkan BNBA langsung ke DB - BNBA WAJIB sejak 5 Agt 2026 (butir C1),
 * dan `Rekam_data_model::kirim()` menolak laporan perumahan tanpa lampirannya.
 *
 * Ditulis langsung, bukan lewat `unggah_bnba`: yang diuji berkas ini bukan alur
 * unggahnya (itu punya penjaganya sendiri di uji_wizard_rekam_perumahan),
 * melainkan apa yang terjadi SESUDAH laporan terkirim. Menempuh ulang
 * unggahannya cuma menyalin cakupan dan menambah sebab gagal yang bukan milik
 * uji ini. Baris ini ikut terhapus bersama laporannya lewat FK.
 */
function lampirkan_bnba($laporan_id) {
    q('INSERT INTO rd_perumahan_bnba (laporan_id, nama_asli, private_path, mime_type, ukuran)
       VALUES (?,?,?,?,?) ON DUPLICATE KEY UPDATE nama_asli = VALUES(nama_asli)',
       [(int) $laporan_id, 'bnba-uji.pdf', 'uji/bnba-uji.pdf', 'application/pdf', 1024]);
}

/** Prepared statement mengembalikan tipe native - selalu di-cast (AGENTS.md §0e). */
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
    // bisa dicocokkan ke akun uji - tapi dimensi `ip` dipakai sebagai kunci
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

echo "Uji D6 - Peninjauan provinsi\n";

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
    // `$url` = layar Capaian, BACA-SAJA. Dulu membukanya cukup untuk melahirkan
    // draft; sejak wizard, index() memakai laporan_periode() yang tidak pernah
    // membuat apa pun (kalau layar baca ikut melahirkan draft, setiap admin yang
    // cuma menengok mengisi riwayat dengan triwulan kosong). Draft lahir di
    // mulai(), dan penyuntingan hidup di /input - dua alamat berbeda sekarang,
    // dan harness ini dulu menganggapnya satu.
    $url    = 'Rekam_Perumahan?tahun=' . TAHUN . '&triwulan=' . TRIWULAN;
    $wizard = 'Rekam_Perumahan/input';

    http('kab', 'Rekam_Perumahan/mulai', [
        'csrf_kpkp_token' => csrf('kab', $wizard),
        'tahun' => TAHUN, 'triwulan' => TRIWULAN]);
    $LAP = skalar_int('SELECT id FROM rd_laporan WHERE domain = ? AND kabupaten_id = ?
        AND tahun = ? AND triwulan = ?', ['perumahan', $KAB, TAHUN, TRIWULAN]);
    wajib($LAP > 0, 'Draft perumahan kabupaten tersedia');
    // input() TANPA ?laporan= selalu merender langkah pemilihan periode
    // ($laporan NULL), bukan wizard laporan ini. Harness harus menyebut
    // laporannya, sama seperti yang dilakukan tautan di layar.
    $wizard_lap = $wizard . '?laporan=' . $LAP;
    cek(skalar_int('SELECT COUNT(*) c FROM rd_laporan WHERE domain = ? AND kabupaten_id = ?
        AND tahun = ? AND triwulan = ?', ['perumahan', $KAB, TAHUN, TRIWULAN]) === 1
        && http('kab', $url)['code'] === 200
        && skalar_int('SELECT COUNT(*) c FROM rd_laporan WHERE domain = ? AND kabupaten_id = ?
            AND tahun = ? AND triwulan = ?', ['perumahan', $KAB, TAHUN, TRIWULAN]) === 1,
        'Membuka layar Capaian TIDAK melahirkan laporan kedua');

    // Draft belum boleh terlihat peninjau.
    $daftar = http('bid_p', 'Rekam_Tinjauan?tahun=' . TAHUN)['body'];
    cek(strpos($daftar, 'Belum ada laporan yang dikirim') !== FALSE,
        'Draft kabupaten tidak muncul di daftar peninjauan');

    // GERBANGNYA TERBALIK dibanding versi lama harness ini. Dulu yang dicentang
    // adalah SUMBER DANA (sepuluh baris `simpan_gerbang`), dan program jadi
    // anaknya. Sejak W1 induknya PROGRAM: `simpan_program` mencentang program,
    // baru `simpan_sumber` mengisi angka per sumber dana di dalamnya. Endpoint
    // `simpan_gerbang` dan `simpan_angka` sudah tidak ada sama sekali - harness
    // ini memanggil dua URL mati dan tetap "lolos" karena tidak pernah memeriksa
    // hasilnya, sampai baris DB di bawah yang merah.
    http('kab', 'Rekam_Perumahan/simpan_program', [
        'csrf_kpkp_token' => csrf('kab', $wizard_lap),
        'laporan_id' => $LAP, 'program' => ['pk_rtlh']]);
    wajib(skalar_int('SELECT COUNT(*) c FROM rd_perumahan_program WHERE laporan_id = ?',
        [$LAP]) === 1, 'Satu program tercentang');

    // Empat angka, bukan dua: rencana DAN realisasi.
    http('kab', 'Rekam_Perumahan/simpan_sumber', [
        'csrf_kpkp_token' => csrf('kab', $wizard_lap),
        'laporan_id' => $LAP, 'program' => 'pk_rtlh', 'sumber_dana' => 'apbd_kabkota',
        'rencana_unit' => 30, 'rencana_anggaran' => 6000000000,
        'realisasi_unit' => 30, 'realisasi_anggaran' => 6000000000]);
    wajib(skalar_int('SELECT realisasi_unit FROM rd_perumahan_baris WHERE laporan_id = ?',
        [$LAP]) === 30, 'Angka tersimpan sebelum dikirim');

    lampirkan_bnba($LAP);

    http('kab', 'Rekam_Perumahan/kirim', [
        'csrf_kpkp_token' => csrf('kab', $wizard_lap), 'laporan_id' => $LAP]);
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
    // Uji ini DIBALIK, dan justru uji inilah yang menemukan bugnya. Ia dulu
    // menuntut layar peninjau berbunyi "kumulatif sampai dengan" - benar selama
    // angkanya kumulatif per bulan, KEBALIKAN kenyataan sejak W1. Kalimat itu
    // masih terpampang sampai 30 Jul 2026 di layar tempat provinsi memutuskan
    // terima atau minta perbaikan: peninjau yang membaca 30 unit sebagai total
    // setahun padahal itu capaian satu triwulan menilai wilayahnya jauh di bawah
    // kenyataan, dan angkanya tetap terlihat wajar.
    cek(strpos($detail['body'], 'bukan kumulatif sejak Januari') !== FALSE,
        'Detail menegaskan angkanya CAPAIAN TRIWULAN ITU, bukan kumulatif');
    cek(strpos($detail['body'], 'kumulatif sampai dengan') === FALSE,
        'Klaim lama "kumulatif sampai dengan" benar-benar hilang (penjaga regresi)');

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
    // Penyuntingan pindah ke /input, bukan lagi di layar Capaian. Catatan
    // peninjau harus sampai ke tempat orangnya BEKERJA, bukan hanya ke layar
    // baca - jadi diperiksa di wizard, dan tetap diperiksa di riwayat.
    $layar_kab = http('kab', $wizard_lap)['body'];
    cek(strpos($layar_kab, $catatan) !== FALSE,
        'Catatan peninjau tampil di layar input (wizard) kabupaten');
    $riwayat_kab = http('kab', 'Rekam_Perumahan/riwayat?tahun=' . TAHUN)['body'];
    cek(strpos($riwayat_kab, $catatan) !== FALSE, 'Catatan juga tampil di riwayat kabupaten');
    cek(strpos($layar_kab, 'name="sumber_dana"') !== FALSE,
        'Kabupaten bisa menyunting lagi setelah dikembalikan');

    // ----------------------------------- kirim ulang memakai laporan yang SAMA
    http('kab', 'Rekam_Perumahan/simpan_program', [
        'csrf_kpkp_token' => csrf('kab', $wizard_lap),
        // 'program[]' dua kali adalah kunci array PHP yang SAMA - yang kedua
        // menimpa yang pertama, jadi pk_rtlh justru ter-uncheck dan angkanya
        // ikut terhapus lewat cascade. Kirim sebagai array betulan.
        'laporan_id' => $LAP, 'program' => ['pk_rtlh']]);
    http('kab', 'Rekam_Perumahan/simpan_sumber', [
        'csrf_kpkp_token' => csrf('kab', $wizard_lap),
        'laporan_id' => $LAP, 'program' => 'pk_rtlh', 'sumber_dana' => 'apbn_bsps',
        'rencana_unit' => 5, 'rencana_anggaran' => 1000000000,
        'realisasi_unit' => 5, 'realisasi_anggaran' => 1000000000]);
    lampirkan_bnba($LAP);
    http('kab', 'Rekam_Perumahan/kirim', [
        'csrf_kpkp_token' => csrf('kab', $wizard_lap), 'laporan_id' => $LAP]);

    cek(skalar_int('SELECT COUNT(*) c FROM rd_laporan WHERE domain = ? AND kabupaten_id = ?
        AND tahun = ? AND triwulan = ?', ['perumahan', $KAB, TAHUN, TRIWULAN]) === 1,
        'Kirim ulang TIDAK membuat periode baru');
    cek(skalar_str('SELECT status FROM rd_laporan WHERE id = ?', [$LAP]) === 'terkirim',
        'Laporan yang sama kembali berstatus terkirim');
    cek(skalar_str('SELECT catatan_admin FROM rd_laporan WHERE id = ?', [$LAP]) === '',
        'Kirim ulang membersihkan catatan perbaikan lama');
    cek(skalar_str('SELECT reviewed_at FROM rd_laporan WHERE id = ?', [$LAP]) === '',
        'Kirim ulang membersihkan jejak peninjauan lama');

    // ------------------------------------------------ terima + rate limit
    // Token diambil SEKARANG, selagi laporan masih `terkirim` dan belum
    // ditinjau - hanya pada keadaan itu form keputusan dirender, jadi hanya
    // saat itu halaman detail memuat token CSRF. Mengambilnya sesudah diterima
    // menghasilkan token KOSONG dan seluruh POST ditolak 403 sebelum menyentuh
    // limiter; uji rate limit lalu "gagal" tanpa ada yang rusak.
    $token = csrf('bid_p', 'Rekam_Tinjauan/detail/' . $LAP);
    cek($token !== '', 'Layar keputusan merender token CSRF selagi laporan menunggu ditinjau');

    http('bid_p', 'Rekam_Tinjauan/terima', ['csrf_kpkp_token' => $token, 'laporan_id' => $LAP]);
    cek(skalar_str('SELECT reviewed_at FROM rd_laporan WHERE id = ?', [$LAP]) !== '',
        'Terima menandai laporan sudah ditinjau');
    cek(skalar_str('SELECT status FROM rd_laporan WHERE id = ?', [$LAP]) === 'terkirim',
        'Status tetap terkirim - "diterima" bukan status tersendiri di skema');

    $daftar = http('bid_p', 'Rekam_Tinjauan?tahun=' . TAHUN)['body'];
    cek(strpos($daftar, 'Diterima') !== FALSE, 'Daftar menampilkan status Diterima');

    $waktu_terima = skalar_str('SELECT reviewed_at FROM rd_laporan WHERE id = ?', [$LAP]);
    http('bid_p', 'Rekam_Tinjauan/terima', ['csrf_kpkp_token' => $token, 'laporan_id' => $LAP]);
    cek(skalar_str('SELECT reviewed_at FROM rd_laporan WHERE id = ?', [$LAP]) === $waktu_terima,
        'Terima dua kali ditolak, stempel tidak berubah');

    // Kabupaten tetap terkunci sesudah diterima - diperiksa di WIZARD, bukan di
    // layar Capaian. Capaian baca-saja untuk semua status, jadi ia tidak pernah
    // perlu menyebut "terkunci" dan mencarinya di sana menguji halaman yang salah.
    // Yang penting: orang yang mencoba MENYUNTING diberi tahu kenapa tidak bisa.
    $layar_kab = http('kab', $wizard_lap)['body'];
    cek(strpos($layar_kab, 'terkunci') !== FALSE, 'Kabupaten tetap terkunci setelah diterima');

    // ------------------------------------------------------- rate limit
    // Policy `admin_queue_decision`: 30 per 60 detik, dimensi ip+account+object.
    // Token yang sama dipakai berulang - `csrf_regenerate` FALSE di repo ini,
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
    cek(($ragam[403] ?? 0) === 0, 'Nol penolakan CSRF - burst benar-benar mencapai limiter');

} finally {
    bersihkan();
}

cek(skalar_int('SELECT COUNT(*) c FROM rd_laporan WHERE tahun = ?', [TAHUN]) === 0,
    'Data uji dibersihkan');

echo "RINGKASAN: {$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
