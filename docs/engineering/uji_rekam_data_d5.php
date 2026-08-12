<?php
/**
 * Uji D5 - Rekam Data: Rekap & Riwayat.
 *
 * Uji terpenting di berkas ini adalah nomor "rekap tidak menjumlahkan antar
 * bulan". Angka modul ini kumulatif; `SUM()` lintas bulan melipatgandakan
 * capaian provinsi dan hasilnya tetap terlihat wajar - jenis kesalahan yang
 * tidak akan ketahuan tanpa uji yang sengaja mencarinya.
 *
 *   php docs/engineering/uji_rekam_data_d5.php
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

/** Selalu di-cast: prepared statement mengembalikan tipe native (AGENTS.md §0e). */
function skalar_int($sql, $params = []) {
    $row = q($sql, $params);
    return $row ? (int) reset($row) : 0;
}

function skalar_str($sql, $params = []) {
    $row = q($sql, $params);
    return $row ? (string) reset($row) : '';
}

// ---------------------------------------------------------------- HTTP

$jars = [];

function jar($nama) {
    global $jars;
    if ( ! isset($jars[$nama])) {
        $jars[$nama] = tempnam(sys_get_temp_dir(), 'ujid5_');
    }
    return $jars[$nama];
}

function http($nama, $path, ?array $post = NULL) {
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_COOKIEJAR      => jar($nama),
        CURLOPT_COOKIEFILE     => jar($nama),
        CURLOPT_FOLLOWLOCATION => TRUE,
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
    global $db, $jars;
    $db->query('DELETE FROM rd_laporan WHERE tahun = ' . (int) TAHUN);
    $db->query("DELETE FROM usr_users WHERE email LIKE 'uji_rd_d5_%'");
    foreach ($jars as $f) {
        @unlink($f);
    }
}

// ---------------------------------------------------------------- prasyarat

echo "Uji D5 - Rekap & Riwayat\n";

$admin = q('SELECT id, kabupaten_id FROM usr_users WHERE email = ? AND role = ?',
    [ADMIN_EMAIL, 'admin_kabkota']);
wajib($admin && ! empty($admin['kabupaten_id']), 'Akun admin_kabkota tersedia dan ter-scope');
$KAB = (int) $admin['kabupaten_id'];

$kab_lain = skalar_int('SELECT id FROM kabupaten WHERE id <> ? ORDER BY id LIMIT 1', [$KAB]);
wajib($kab_lain > 0, 'Ada kabupaten kedua untuk uji scope');

$stamp = time();
$email_lain = "uji_rd_d5_{$stamp}@example.test";
$db->query(sprintf(
    "INSERT INTO usr_users (email, password, role, kabupaten_id, name, username)
     VALUES ('%s', '%s', 'admin_kabkota', %d, 'Uji D5 Lain', 'uji_rd_d5_%d')",
    $db->real_escape_string($email_lain),
    $db->real_escape_string(password_hash('UjiRdD5!', PASSWORD_BCRYPT)),
    $kab_lain, $stamp));

try {
    wajib(login('kab', ADMIN_EMAIL, ADMIN_PASSWORD), 'Login admin_kabkota berhasil');
    wajib(login('lain', $email_lain, 'UjiRdD5!'), 'Login admin wilayah lain berhasil');

    /* Laporan TERKIRIM milik kabupaten LAIN, dengan angka penanda 7777000000.
       Sengaja ada supaya asersi "cakupan wilayah ikut ke berkas export" benar-
       benar terbukti: kalau `rekap()` kehilangan argumen kabupaten, angka ini
       yang akan bocor. Diisi di TW III agar ikut ke export triwulanan (TW II
       yang diuji) TIDAK, tetapi ke export SETAHUN yang menyapu keempat triwulan
       IYA - itulah yang membuat versi tahunan bisa dijaga, bukan cuma triwulanan.
       Dibersihkan oleh bersihkan() lewat DELETE ... WHERE tahun = TAHUN. */
    /* KOMBINASI SUMBER+PROGRAM SENGAJA BEDA dari yang dipakai $KAB
       (apbd_kabkota + pk_rtlh). Ini bukan detail: `matriks()` mengunci sel per
       [sumber_dana][program] dan baris terakhir MENIMPA yang pertama. Kalau
       wilayah lain memakai kombinasi yang sama, angkanya tertimpa data $KAB dan
       tak pernah muncul - persis kenapa mutasi cakupan sempat LOLOS. Dengan
       kombinasi berbeda (apbn_bsps + pb_backlog), selnya tak bisa ditimpa, jadi
       tanpa scope ia PASTI bocor, dan mutasinya merah. */
    $isi_lain = function ($triwulan, $angka) use ($kab_lain) {
        $w = 'Rekam_Perumahan/input';
        http('lain', 'Rekam_Perumahan/mulai', [
            'csrf_kpkp_token' => csrf('lain', $w),
            'tahun' => TAHUN, 'triwulan' => $triwulan]);
        $lap = skalar_int('SELECT id FROM rd_laporan WHERE domain = ? AND kabupaten_id = ?
            AND tahun = ? AND triwulan = ?', ['perumahan', $kab_lain, TAHUN, $triwulan]);
        $wl = $w . '?laporan=' . $lap;
        http('lain', 'Rekam_Perumahan/simpan_program', [
            'csrf_kpkp_token' => csrf('lain', $wl), 'laporan_id' => $lap,
            'program' => ['pb_backlog']]);
        http('lain', 'Rekam_Perumahan/simpan_sumber', [
            'csrf_kpkp_token' => csrf('lain', $wl), 'laporan_id' => $lap,
            'program' => 'pb_backlog', 'sumber_dana' => 'apbn_bsps',
            'rencana_unit' => 1, 'rencana_anggaran' => $angka,
            'realisasi_unit' => 1, 'realisasi_anggaran' => $angka]);
        lampirkan_bnba($lap);
        http('lain', 'Rekam_Perumahan/kirim', [
            'csrf_kpkp_token' => csrf('lain', $wl), 'laporan_id' => $lap]);
        return $lap;
    };
    $lap_lain = $isi_lain(3, 7777000000);
    wajib($lap_lain > 0
        && skalar_str('SELECT status FROM rd_laporan WHERE id = ?', [$lap_lain]) === 'terkirim',
        'PRASYARAT: laporan kabupaten lain benar-benar terkirim dengan angka penanda');

    // ------------------------------------ rekap kosong = jujur, bukan nol
    $kosong = http('kab', 'Rekam_Perumahan/rekap?tahun=' . TAHUN . '&triwulan=1');
    cek($kosong['code'] === 200, 'Layar rekap terbuka walau tanpa data');
    cek(strpos($kosong['body'], 'Belum ada laporan terkirim') !== FALSE,
        'Keadaan kosong dinyatakan apa adanya');
    cek(strpos($kosong['body'], 'Bukan berarti capaiannya nol') !== FALSE,
        'Layar membedakan "belum dikirim" dari "nol"');
    cek(strpos($kosong['body'], '<table') === FALSE,
        'Nol tabel angka saat belum ada data - tidak merender baris nol karangan');

    // ---------------------------------------------- siapkan dua periode
    // Alur pengisian ditulis ulang ke wizard: draft lahir di mulai() (membuka
    // layar tidak lagi menulis), gerbangnya per PROGRAM bukan per sumber dana,
    // dan angkanya empat kolom. Endpoint simpan_gerbang/simpan_angka sudah tidak
    // ada sama sekali.
    $isi_periode = function ($triwulan, $unit, $anggaran) use ($KAB) {
        $w = 'Rekam_Perumahan/input';
        http('kab', 'Rekam_Perumahan/mulai', [
            'csrf_kpkp_token' => csrf('kab', $w),
            'tahun' => TAHUN, 'triwulan' => $triwulan]);
        $lap = skalar_int('SELECT id FROM rd_laporan WHERE domain = ? AND kabupaten_id = ?
            AND tahun = ? AND triwulan = ?', ['perumahan', $KAB, TAHUN, $triwulan]);
        $wl = $w . '?laporan=' . $lap;
        http('kab', 'Rekam_Perumahan/simpan_program', [
            'csrf_kpkp_token' => csrf('kab', $wl), 'laporan_id' => $lap,
            'program' => ['pk_rtlh']]);
        http('kab', 'Rekam_Perumahan/simpan_sumber', [
            'csrf_kpkp_token' => csrf('kab', $wl), 'laporan_id' => $lap,
            'program' => 'pk_rtlh', 'sumber_dana' => 'apbd_kabkota',
            'rencana_unit' => $unit, 'rencana_anggaran' => $anggaran,
            'realisasi_unit' => $unit, 'realisasi_anggaran' => $anggaran]);
        lampirkan_bnba($lap);
        http('kab', 'Rekam_Perumahan/kirim', [
            'csrf_kpkp_token' => csrf('kab', $wl), 'laporan_id' => $lap]);
        return $lap;
    };

    $lap6 = $isi_periode(2, 25, 5000000000);
    cek(skalar_str('SELECT status FROM rd_laporan WHERE id = ?', [$lap6]) === 'terkirim',
        'TW II terkirim');

    // TW III diisi 40 sebagai capaian TRIWULAN ITU SENDIRI - bukan "kumulatif
    // yang dinaikkan dari 25". Tidak ada pewarisan sejak W1, jadi 40 memang
    // angka baru, dan kumulatif s.d. TW III = 25 + 40 dihitung oleh sistem.
    $lap7 = $isi_periode(3, 40, 8000000000);
    cek(skalar_str('SELECT status FROM rd_laporan WHERE id = ?', [$lap7]) === 'terkirim',
        'TW III terkirim');

    // ------------------------------ INTI D5: nol SUM() antar bulan
    $rekap6 = http('kab', 'Rekam_Perumahan/rekap?tahun=' . TAHUN . '&triwulan=2')['body'];
    $rekap7 = http('kab', 'Rekam_Perumahan/rekap?tahun=' . TAHUN . '&triwulan=3')['body'];

    // Pola diikat ke bentuk sel yang benar-benar dirender (`<angka><br>`).
    // Mencari "65" telanjang di seluruh HTML tidak bermakna: angka itu bisa
    // muncul di token CSRF, kelas CSS, atau atribut mana pun.
    cek(strpos($rekap6, '25<br>') !== FALSE, 'Rekap TW II menampilkan 25');
    cek(strpos($rekap7, '40<br>') !== FALSE, 'Rekap TW III menampilkan 40 (capaian TW III saja)');
    // Layar rekap kini DUA tabel: per-triwulan lalu kumulatif. 65 dan 13 miliar
    // MEMANG harus muncul - di tabel kumulatif. Mencarinya di seluruh HTML tidak
    // bisa membedakan "bocor ke tabel per-triwulan" dari "benar di tabel
    // kumulatif", jadi HTML-nya dipotong di judul tabel kumulatif dan yang
    // diperiksa hanya bagian atasnya. Tanpa pemotongan ini uji ini akan MERAH
    // pada perilaku yang benar - dan itu jenis uji yang akhirnya dimatikan orang.
    $batas = strpos($rekap7, 'Kumulatif Realisasi s.d.');
    wajib($batas !== FALSE, 'Tabel kumulatif ada sebagai penanda batas');
    $per_tw = substr($rekap7, 0, $batas);
    cek(strpos($per_tw, '65<br>') === FALSE,
        'Tabel per-triwulan TIDAK menjumlahkan TW II+TW III (65 tidak muncul di sana)');
    cek(strpos($rekap7, '65<br>') !== FALSE,
        'Kumulatif 25+40=65 memang dihitung - di tabelnya sendiri, sekali');

    // Bukti yang tidak bergantung pada HTML sama sekali: satu baris per
    // (sumber, program) per laporan, dan nilainya nilai bulan itu sendiri.
    cek(skalar_int('SELECT COUNT(*) c FROM rd_perumahan_baris WHERE laporan_id = ?
        AND sumber_dana = ? AND program = ?', [$lap7, 'apbd_kabkota', 'pk_rtlh']) === 1,
        'Satu baris per sumber+program per periode, tidak terakumulasi');
    cek(skalar_int('SELECT realisasi_unit FROM rd_perumahan_baris WHERE laporan_id = ?
        AND sumber_dana = ? AND program = ?', [$lap7, 'apbd_kabkota', 'pk_rtlh']) === 40,
        'Nilai TW III di DB tetap 40, bukan 25+40');
    cek(strpos($per_tw, number_format(13000000000, 0, ',', '.')) === FALSE,
        'Anggaran TW III tidak dijumlahkan dengan TW II di tabel per-triwulan');
    cek(strpos($per_tw, number_format(8000000000, 0, ',', '.')) !== FALSE,
        'Anggaran TW III tampil apa adanya');

    // -------------------------------------------- label periode eksplisit
    // DIBALIK. Label lama "kumulatif s.d. Juli" benar selama angkanya kumulatif;
    // sejak W1 layar justru harus menyangkalnya, dan menyediakan kumulatifnya
    // secara terpisah supaya keduanya tidak tertukar.
    cek(strpos($rekap7, 'per triwulan') !== FALSE,
        'Layar menyatakan angkanya PER TRIWULAN, bukan kumulatif');
    cek(strpos($rekap7, 'Kumulatif Realisasi s.d. TW III') !== FALSE,
        'Kumulatif disediakan terpisah dan menyebut periodenya eksplisit');
    cek(strpos($rekap6, 'Kumulatif Realisasi s.d. TW II') !== FALSE,
        'Label periode ikut berubah sesuai pilihan');

    // ------------------------------------------ dua domain tidak digabung
    // Kalimat "tidak digabungkan" HILANG dari layar Perumahan saat ditulis ulang;
    // yang masih memuatnya cuma rekap Kawasan (diperiksa di bawah). Bukan
    // kebohongan, cuma keterangan yang tinggal sebelah - jadi uji ini tidak
    // menuntutnya di Perumahan, dan tidak pula berpura-pura ia masih ada.
    cek(strpos($rekap7, 'per triwulan') !== FALSE,
        'Layar Perumahan tetap menerangkan cara membaca angkanya');

    // ------------------------------------------------ draft tidak masuk
    $lap8 = NULL;
    http('kab', 'Rekam_Perumahan/mulai', [
        'csrf_kpkp_token' => csrf('kab', 'Rekam_Perumahan/input'),
        'tahun' => TAHUN, 'triwulan' => 4]);
    $lap8 = skalar_int('SELECT id FROM rd_laporan WHERE domain = ? AND kabupaten_id = ?
        AND tahun = ? AND triwulan = ?', ['perumahan', $KAB, TAHUN, 4]);
    cek($lap8 > 0 && skalar_str('SELECT status FROM rd_laporan WHERE id = ?', [$lap8]) === 'draft',
        'TW IV masih draft (dan lahir kosong, tanpa mewarisi TW III)');
    $rekap8 = http('kab', 'Rekam_Perumahan/rekap?tahun=' . TAHUN . '&triwulan=4')['body'];
    cek(strpos($rekap8, 'Belum ada laporan terkirim') !== FALSE,
        'Draft tidak masuk rekap - hanya laporan terkirim yang dihitung');

    // ------------------------------------------------------- scope
    /* Scope dibuktikan DUA ARAH, dan itu lebih kuat daripada versi lama.
       Dulu asersi ini cuma memeriksa `lain` melihat "kosong" - bukti lemah
       yang mencampur "lain tak punya data" dengan "lain tak melihat data $KAB".
       Sejak `lain` kini punya TW III sendiri (7777000000), yang dijaga jadi
       tegas: `lain` melihat MILIKNYA, dan TIDAK melihat milik $KAB. */
    $rekap_lain = http('lain', 'Rekam_Perumahan/rekap?tahun=' . TAHUN . '&triwulan=3')['body'];
    cek(strpos($rekap_lain, number_format(7777000000, 0, ',', '.')) !== FALSE,
        'Admin wilayah lain melihat angka WILAYAHNYA SENDIRI');
    cek(strpos($rekap_lain, number_format(8000000000, 0, ',', '.')) === FALSE,
        'Angka wilayah ini TIDAK bocor ke rekap wilayah lain');

    // ------------------------------------------------------ riwayat
    $riwayat = http('kab', 'Rekam_Perumahan/riwayat?tahun=' . TAHUN)['body'];
    // Riwayat menyebut TRIWULAN, bukan nama bulan.
    cek(strpos($riwayat, 'TW II ') !== FALSE && strpos($riwayat, 'TW III') !== FALSE
        && strpos($riwayat, 'TW IV') !== FALSE, 'Riwayat memuat ketiga periode');
    cek(substr_count($riwayat, 'Terkirim') >= 2, 'Dua periode berstatus Terkirim');
    cek(strpos($riwayat, 'Draft') !== FALSE, 'Periode draft ikut tampil dengan statusnya');
    cek(strpos($riwayat, '<form method="post"') === FALSE,
        'Riwayat baca-saja: nol form POST');

    /* Riwayat `lain` kini memuat TW III miliknya sendiri, jadi "kosong" bukan
       lagi buktinya. Yang dijaga: ia melihat periode SENDIRI, dan tidak melihat
       TW IV yang cuma dipunyai $KAB. */
    $riwayat_lain = http('lain', 'Rekam_Perumahan/riwayat?tahun=' . TAHUN)['body'];
    cek(strpos($riwayat_lain, 'TW III') !== FALSE,
        'Riwayat wilayah lain memuat periode SENDIRI (TW III)');
    cek(strpos($riwayat_lain, 'TW IV') === FALSE,
        'Riwayat wilayah lain TIDAK memuat periode milik wilayah ini (TW IV)');

    // --------------------------------------------------- rekap kawasan
    $urlk = 'Rekam_Kawasan?tahun=' . TAHUN . '&triwulan=2';
    http('kab', $urlk);
    $lapk = skalar_int('SELECT id FROM rd_laporan WHERE domain = ? AND kabupaten_id = ?
        AND tahun = ? AND triwulan = ?', ['kawasan', $KAB, TAHUN, 2]);
    http('kab', 'Rekam_Kawasan/simpan_ringkasan', [
        'csrf_kpkp_token' => csrf('kab', $urlk), 'laporan_id' => $lapk,
        'ada_penanganan' => '1', 'ada_progres' => '1', 'total_luas_ha' => '12.75']);
    http('kab', 'Rekam_Kawasan/simpan_intervensi', [
        'csrf_kpkp_token' => csrf('kab', $urlk), 'laporan_id' => $lapk,
        'indikator' => 'drainase', 'nama_kegiatan' => 'Normalisasi drainase',
        'lokasi_teks' => 'RT 1 RW 1, Desa Uji', 'sumber_anggaran' => 'apbd_kabkota',
        'volume' => '320', 'nilai_anggaran' => '480000000', 'nilai_padat_karya' => '60000000']);
    http('kab', 'Rekam_Kawasan/kirim', [
        'csrf_kpkp_token' => csrf('kab', $urlk), 'laporan_id' => $lapk]);

    $rekapk = http('kab', 'Rekam_Kawasan/rekap?tahun=' . TAHUN . '&triwulan=2')['body'];
    cek(strpos($rekapk, number_format(480000000, 0, ',', '.')) !== FALSE,
        'Rekap kawasan menampilkan total anggaran hasil hitung');
    // Judulnya dulu "Angka kumulatif s.d. TW II" - dibantah keterangannya sendiri
    // dua baris di bawah ("triwulan sebelumnya tidak dijumlahkan") dan dibantah
    // kodenya. Sudah diluruskan jadi "Capaian TW II"; yang dijaga di sini tetap
    // sama: periodenya disebut EKSPLISIT, tidak dibiarkan ditebak pembaca.
    cek(strpos($rekapk, 'Capaian TW II') !== FALSE,
        'Rekap kawasan menyebut periodenya eksplisit');
    cek(strpos($rekapk, 'kumulatif') === FALSE,
        'Rekap kawasan tidak lagi mengaku kumulatif (penjaga regresi)');
    cek(strpos($rekapk, 'tidak digabungkan') !== FALSE,
        'Rekap kawasan menyatakan tidak digabung dengan perumahan');
    cek(strpos($rekapk, number_format(8000000000, 0, ',', '.')) === FALSE,
        'Nol angka perumahan muncul di rekap kawasan');

    // ================================================================ EXPORT
    /* Butir C4/D4, 5 Agt 2026. Ditempel di sini, bukan di harness baru: rekap
       yang jadi sumber export sudah dibangun & diuji di berkas ini, dan uji
       export yang menyiapkan datanya sendiri akan menguji jalur yang berbeda
       dari yang dipakai layar. */
    echo "\n== Export rekap ==\n";

    $xls = http('kab', 'Rekam_Perumahan/export?tahun=' . TAHUN . '&triwulan=2')['body'];
    cek(strpos($xls, '<Workbook') !== FALSE, 'Export perumahan mengembalikan lembar kerja');
    cek(@simplexml_load_string($xls) !== FALSE, 'Berkasnya XML yang sah - Excel bisa membukanya');

    /* Argumen cakupan `rekap()` ada di posisi KEEMPAT; menaruhnya salah tidak
       menghasilkan galat, `WHERE kabupaten_id` cuma tidak terpasang. Wilayah
       lain punya laporan di TW III (7777000000) dengan sumber+program BEDA dari
       $KAB, jadi `matriks()` tidak menimpanya - tanpa scope, angkanya bocor.
       Export triwulanan ini menguji TW II, tempat wilayah lain TIDAK punya
       data, jadi ia lolos scope maupun tidak; yang benar-benar menguji cakupan
       adalah export SETAHUN di bawah, yang menyapu TW III. */
    $angka_lain = number_format(7777000000, 0, ',', '.');
    cek(strpos($xls, '7777000000') === FALSE && strpos($xls, $angka_lain) === FALSE,
        'Export triwulan II tidak memuat angka wilayah lain (mereka tak berlaporan di TW II)');

    /* BUTIR 23 PUTARAN 2 - unduhan SETAHUN, empat triwulan sebagai baris.

       Yang dijaga bukan "berkasnya jadi", melainkan tiga hal yang bisa rusak
       tanpa satu pun galat:
         1. Keempat triwulan benar-benar ada, bukan cuma yang sedang dibuka.
         2. Ada baris Total, karena itu setengah dari yang diminta dinas.
         3. Cakupan wilayah tetap mengikat - argumen posisi `rekap()` mudah
            tergeser, dan kalau tergeser berkasnya memuat 35 kabupaten tanpa
            ada yang menyadarinya. */
    $xlsTahun = http('kab', 'Rekam_Perumahan/export?periode=tahun&tahun=' . TAHUN)['body'];
    cek(strpos($xlsTahun, '<Workbook') !== FALSE, 'Export setahun mengembalikan lembar kerja');
    cek(@simplexml_load_string($xlsTahun) !== FALSE, 'Berkas setahun XML yang sah');
    foreach (['TW I', 'TW II', 'TW III', 'TW IV'] as $tw) {
        cek(strpos($xlsTahun, '>' . $tw . '<') !== FALSE, "Berkas setahun memuat baris {$tw}");
    }
    cek(strpos($xlsTahun, 'Total ' . TAHUN) !== FALSE, 'Ada baris Total setahun');
    /* Cakupan wilayah pada export SETAHUN, dan kini BENAR-BENAR TERBUKTI.
       Sebelumnya asersi ini hijau tanpa arti karena tidak ada data kabupaten
       lain di keempat triwulan tahun uji - dengan atau tanpa scope, angkanya
       memang tak ada. Sekarang `$isi_lain(3, 7777000000)` di atas menaruh
       laporan terkirim milik kabupaten lain di TW III, yang PASTI disapu export
       tahunan. Kalau argumen kabupaten `rekap()` tergeser, angka ini bocor dan
       asersi merah - dibuktikan mutasinya di bawah. */
    cek(strpos($xlsTahun, '7777000000') === FALSE,
        'Export setahun TIDAK memuat angka kabupaten lain (TW III wilayah lain ada, tapi tersaring)');

    /* BNBA berisi nama + NIK penerima. Keputusan user: ia TIDAK ikut export.
       Jalur yang salah (`isi_laporan()`) akan menyeret metadatanya diam-diam. */
    cek(preg_match('/bnba|nama_asli|private_path/i', $xls) === 0,
        'Nol jejak BNBA di berkas - daftar penerima tidak ikut keluar');

    /* Header harus memakai `label_sumber()` (12 sumber), BUKAN
       `Rekam_data_model::label_domain()` yang cuma memuat 10 - `apbd_provinsi`
       dan `baznas_provinsi` hilang di sana. */
    foreach (['APBD Provinsi', 'BAZNAS Provinsi', 'Dana Lainnya'] as $s) {
        cek(strpos($xls, $s) !== FALSE, "Export memuat sumber \"{$s}\"");
    }
    cek(substr_count($xls, 'Rencana (unit)') === 6, 'Enam program × kolom rencana unit');

    /* Sel kosong harus KOSONG, bukan 0 - "nol tabel nol". Nol karangan tidak
       bisa dibedakan dari nol yang benar-benar dilaporkan, dan di spreadsheet
       ia ikut terjumlah. */
    /* Versi pertama penjaga ini cuma memastikan ADA sel kosong - dan itu hijau
       walau tiga dari empat kolom diisi 0, karena kolom keempat tetap kosong.
       Yang dinyatakan sekarang langsung: NOL angka nol di seluruh berkas.
       Fixture uji ini tidak pernah melaporkan 0 (25 dan 40 unit), jadi setiap
       angka nol yang muncul pasti karangan. Kalau kelak ada fixture yang
       benar-benar melaporkan 0, penjaga ini harus diubah - bukan dilonggarkan. */
    cek(strpos($xls, '<Cell/>') !== FALSE,
        'Ada sel kosong di berkas');
    cek(preg_match('/<Data ss:Type="Number">0<\/Data>/', $xls) === 0,
        'NOL angka nol dikarang - sumber tanpa laporan dibiarkan kosong, tidak diisi 0');

    // Periode tanpa laporan → diarahkan balik, bukan berkas nol baris.
    $kosong = http('kab', 'Rekam_Perumahan/export?tahun=' . TAHUN . '&triwulan=4')['body'];
    cek(strpos($kosong, '<Workbook') === FALSE,
        'Periode tanpa laporan tidak menghasilkan berkas - berkas nol baris terbaca sebagai "capaiannya nol"');

} finally {
    bersihkan();
}

cek(skalar_int('SELECT COUNT(*) c FROM rd_laporan WHERE tahun = ?', [TAHUN]) === 0,
    'Data uji dibersihkan');

echo "RINGKASAN: {$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
