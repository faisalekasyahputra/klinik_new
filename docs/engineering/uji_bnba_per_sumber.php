<?php
require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php'; // lokasi .env (luar akar dulu)
require_once __DIR__ . '/_akun_demo.php'; // akun demo nonaktif sejak migrasi 073: dipinjam selama suite berjalan
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Uji W6 - wizard Rekam Data Perumahan (bentuk baru: triwulan, gerbang per
 * program, Rencana + Realisasi).
 *
 * Menempuh lintasan penuh lewat HTTP sungguhan: pilih periode -> pilih program
 * -> tambah sumber dana -> ubah -> hapus -> kirim -> tinjau -> minta perbaikan
 * -> kirim ulang. Ditambah uji negatif yang justru jadi intinya.
 *
 *   php docs/engineering/uji_bnba_per_sumber.php
 *
 * Env opsional: UJI_BASE_URL, UJI_ADMIN_EMAIL, UJI_ADMIN_PASSWORD
 * Tahun sentinel 2099; seluruh jejaknya dihapus di akhir.
 *
 * DEFINISI SELESAI skrip ini BUKAN "hijau sekali". Balikkan satu perbaikan -
 * misalnya lepas sisi `rencana` dari aturan anggaran-tanpa-unit di model - dan
 * skrip ini WAJIB merah di titik yang diramalkan. Skrip yang tidak pernah gagal
 * tidak membuktikan apa pun.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('ENV_PATH', env_berkas_path(APP_ROOT));
define('ADMIN_EMAIL', getenv('UJI_ADMIN_EMAIL') ?: 'adminkabkota@example.com');
define('ADMIN_PASSWORD', getenv('UJI_ADMIN_PASSWORD') ?: pinjam_akun_demo(ADMIN_EMAIL));
define('TAHUN', 2099);

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;

/** Penanda waktu mulai - dipakai membersihkan bucket rate limit run ini saja. */
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
 * Lampirkan BNBA langsung ke DB - BNBA WAJIB sejak 5 Agt 2026 (butir C1),
 * dan `Rekam_data_model::kirim()` menolak laporan perumahan tanpa lampirannya.
 *
 * Ditulis langsung, bukan lewat `unggah_bnba`: yang diuji berkas ini bukan alur
 * unggahnya (gerbangnya diuji dua arah di [L5] di bawah), melainkan
 * penyiapan laporan KEDUA dan seterusnya. Menempuh ulang
 * unggahannya cuma menyalin cakupan dan menambah sebab gagal yang bukan milik
 * uji ini. Baris ini ikut terhapus bersama laporannya lewat FK.
 */
function lampirkan_bnba($laporan_id) {
    q('INSERT INTO rd_perumahan_bnba (laporan_id, nama_asli, path_privat, mime_type, ukuran)
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
    if ( ! isset($jars[$nama])) { $jars[$nama] = tempnam(sys_get_temp_dir(), 'ujibn_'); }
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
 * `Auth::do_login()` membalas 303 lalu curl mengikutinya ke halaman 200 -
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
    $db->query("DELETE FROM sys_batas_laju WHERE jendela_mulai_at >= '"
        . $db->real_escape_string($mulai) . "'");

    $db->query("DELETE FROM usr_akun WHERE email LIKE 'uji_bn_%'");
    foreach ($jars as $f) { @unlink($f); }
}

// ---------------------------------------------------------------- bantu

require_once APP_ROOT . '/vendor/autoload.php';
define('BASEPATH', 'uji');
define('FCPATH', APP_ROOT . '/');
if ( ! empty($env['PRIVATE_UPLOADS_PATH'])) { putenv('PRIVATE_UPLOADS_PATH=' . $env['PRIVATE_UPLOADS_PATH']); }
require_once APP_ROOT . '/application/helpers/private_upload_helper.php';

$tmp_berkas = [];

/** Tulis xlsx uji. Semua sel bertipe teks supaya NIK 16 digit tidak jadi notasi ilmiah. */
function buat_xlsx(array $baris, ?array $judul = NULL) {
    global $tmp_berkas;
    $judul = $judul ?? ['NIK', 'NO KK', 'NAMA', 'ALAMAT', 'KABUPATEN', 'KECAMATAN', 'DESA/KELURAHAN',
        "KDDESA\n(KOLOM TERISI OTOMATIS)", 'JUMLAH MATERIAL', 'JUMLAH PADAT KARYA'];
    $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sh = $ss->getActiveSheet();
    foreach (array_merge([$judul], $baris) as $r => $isi) {
        foreach ($isi as $c => $v) {
            $sh->setCellValueExplicit([$c + 1, $r + 1], (string) $v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        }
    }
    $path = tempnam(sys_get_temp_dir(), 'ujibn_') . '.xlsx';
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save($path);
    return $tmp_berkas[] = $path;
}

/** Baris sah: desa SUNGGUHAN milik kabupaten penguji ($GLOBALS['DESA']), KDDESA = kode Kemendagri-nya. */
function baris_ok($nik, $kab, $material = 17500000, $padat = 250000) {
    $d = $GLOBALS['DESA'];
    return [$nik, '', 'Penerima ' . substr($nik, -4), 'RT01 RW02', $kab, $d['kec'], $d['desa'], $d['kode'], $material, $padat];
}

/** POST multipart ke simpan_sumber (angka + berkas), token dari halaman yang merender form. */
function simpan_dengan_bnba($nama, $halaman, array $data, $berkas = NULL) {
    $data['csrf_kpkp_token'] = csrf($nama, $halaman);
    if ($berkas !== NULL) { $data['bnba'] = new CURLFile($berkas, 'application/octet-stream', 'bnba.xlsx'); }
    $ch = curl_init(BASE_URL . '/Rekam_Perumahan/simpan_sumber');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_COOKIEJAR => jar($nama), CURLOPT_COOKIEFILE => jar($nama),
        CURLOPT_FOLLOWLOCATION => TRUE, CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => $data, CURLOPT_TIMEOUT => 60]);
    $body = (string) curl_exec($ch);
    curl_close($ch);
    return $body;
}

/** Revisi dinas 29 Okt 2026: realisasi harus SAMA dengan isi BNBA (penerima = unit, material + padat = rupiah). */
function sumber_data($lap, $program, $sumber, $unit, $rupiah) {
    return ['laporan_id' => $lap, 'program' => $program, 'sumber_dana' => $sumber,
        'rencana_unit' => $unit, 'rencana_anggaran' => $rupiah,
        'realisasi_unit' => $unit, 'realisasi_anggaran' => $rupiah];
}
// Pasangan angka untuk berkas uji: $bagus = 2 penerima (17.500.000+250.000, 20.000.000+300.000),
// baris_ok tunggal = 1 penerima 17.750.000, templat 3c = 1 penerima 20.000.000.
const S2 = [2, 38050000]; const S1 = [1, 17750000]; const T1 = [1, 20000000];

function ada_di_disk($lap, $nama) { clearstatcache(); return is_file(private_uploads_dir('rekam_bnba', $lap) . $nama); }

function bnba_baris($lap) {
    return q('SELECT s.path_privat, s.jumlah_penerima, s.total_material, s.total_padat_karya, b.id AS baris
              FROM rd_perumahan_bnba_sumber s JOIN rd_perumahan_baris b ON b.id = s.baris_id WHERE b.laporan_id = ?', [$lap]);
}

// ---------------------------------------------------------------- prasyarat

echo "Uji BNBA per sumber dana\n";

$admin = q('SELECT id, kabupaten_id FROM usr_akun WHERE email = ? AND peran = ?', [ADMIN_EMAIL, 'admin_kabkota']);
wajib($admin && ! empty($admin['kabupaten_id']), 'Akun admin_kabkota tersedia dan ter-scope');
$KAB = (int) $admin['kabupaten_id'];
$NAMA_KAB = skalar_str('SELECT nama AS s FROM kabupaten WHERE id = ?', [$KAB]);
$DESA = q('SELECT d.id AS kode, d.nama AS desa, k.nama AS kec FROM desa d JOIN kecamatan k ON k.id = d.kecamatan_id
           WHERE k.kabupaten_id = ? ORDER BY d.id LIMIT 1', [$KAB]);
$DESA_LAIN = q('SELECT d.id AS kode FROM desa d JOIN kecamatan k ON k.id = d.kecamatan_id WHERE k.kabupaten_id <> ? LIMIT 1', [$KAB]);
$LAIN = skalar_str('SELECT nama AS s FROM kabupaten WHERE id <> ? ORDER BY id LIMIT 1', [$KAB]);
bersihkan();

$stamp = time();
$email_bidang = "uji_bn_perumahan_{$stamp}@example.test";
$db->query(sprintf(
    "INSERT INTO usr_akun (email, kata_sandi, peran, bidang_kode, nama, nama_pengguna)
     VALUES ('%s', '%s', 'admin_bidang', 'perumahan', 'Uji BN', 'uji_bn_%d')",
    $db->real_escape_string($email_bidang), $db->real_escape_string(password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT)), $stamp));
wajib(login('kab', ADMIN_EMAIL, ADMIN_PASSWORD), 'Login admin_kabkota');
wajib(login('bid', $email_bidang, ADMIN_PASSWORD), 'Login admin_bidang (untuk uji lintas wilayah)');

try {
    kirim_form('kab', 'Rekam_Perumahan/input', 'Rekam_Perumahan/mulai', ['tahun' => TAHUN, 'triwulan' => 1]);
    $ID = skalar_int('SELECT id AS n FROM rd_laporan WHERE tahun = ? AND triwulan = 1 AND domain = ?', [TAHUN, 'perumahan']);
    wajib($ID > 0, 'Draft TW I lahir');
    kirim_form('kab', 'Rekam_Perumahan/input?laporan=' . $ID . '&langkah=program', 'Rekam_Perumahan/simpan_program',
        ['laporan_id' => $ID, 'program' => ['pk_rtlh']]);
    $isian = 'Rekam_Perumahan/input?laporan=' . $ID . '&langkah=isian&program=pk_rtlh';

    echo "\n[1] Form dan templat\n";
    $r = http('kab', $isian);
    cek(strpos($r['body'], 'enctype="multipart/form-data"') !== FALSE && strpos($r['body'], 'name="bnba"') !== FALSE,
        'Form sumber dana membawa kolom unggah BNBA (multipart)');
    // Temuan dinas 28 Okt 2026: templat lama SpreadsheetML ber-ekstensi .xls ditolak saat diunggah balik,
    // NIK jadi 3,37E+15, dan KDDESA tidak terisi. Templat kini XLSX asli.
    $t = http('kab', 'Rekam_Perumahan/templat_bnba');
    $templat = tempnam(sys_get_temp_dir(), 'ujibn_') . '.xlsx';
    file_put_contents($templat, $t['body']);
    $tmp_berkas[] = $templat;
    cek($t['code'] === 200 && substr($t['body'], 0, 2) === 'PK', 'Templat BNBA adalah XLSX asli (bukan XML ber-ekstensi .xls)');
    $ss = \PhpOffice\PhpSpreadsheet\IOFactory::load($templat);
    $sh = $ss->getSheetByName('BNBA');
    $kw = $ss->getSheetByName('KODE WILAYAH');
    cek($sh && $kw && $sh->getCell('J1')->getValue() === 'JUMLAH PADAT KARYA', 'Templat punya lembar BNBA (kolom dinas) dan KODE WILAYAH');
    cek($sh->getStyle('A2')->getNumberFormat()->getFormatCode() === '@' && $sh->getStyle('B2')->getNumberFormat()->getFormatCode() === '@',
        'Kolom NIK dan NO KK berformat Teks');
    cek(strpos((string) $sh->getCell('H2')->getValue(), "VLOOKUP") !== FALSE, 'KDDESA berumus VLOOKUP ke KODE WILAYAH');
    $kode_kw = [];
    for ($i = 2; $i <= $kw->getHighestRow(); $i++) { $kode_kw[] = (string) $kw->getCell("E$i")->getValue(); }
    cek($kode_kw && count(array_filter($kode_kw, fn($k) => strpos($k, (string) $KAB) !== 0)) === 0 && in_array($DESA['kode'], $kode_kw, TRUE),
        'KODE WILAYAH hanya berisi desa kabupaten pengunduh (' . count($kode_kw) . ' desa)');

    // Tiru petugas: isi templat (NIK diketik sebagai teks, nama desa huruf kecil) lalu simpan seperti
    // Excel, yaitu dengan nilai rumus ikut tersimpan.
    $isi_templat = function (array $baris) use ($templat, &$tmp_berkas) {
        $ss = \PhpOffice\PhpSpreadsheet\IOFactory::load($templat);
        $sh = $ss->getSheetByName('BNBA');
        foreach ($baris as $i => $r) {
            foreach ([0 => 'A', 1 => 'B', 2 => 'C', 3 => 'D', 4 => 'E', 5 => 'F', 6 => 'G', 8 => 'I', 9 => 'J'] as $c => $k) {
                $sh->setCellValueExplicit($k . ($i + 2), (string) $r[$c], $c < 2 ? \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
                    : (is_int($r[$c]) ? \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC : \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING));
            }
        }
        $p = tempnam(sys_get_temp_dir(), 'ujibn_') . '.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save($p); // preCalculate bawaan TRUE = nilai rumus tersimpan
        return $tmp_berkas[] = $p;
    };

    echo "\n[2] Gerbang kirim\n";
    simpan_dengan_bnba('kab', $isian, sumber_data($ID, 'pk_rtlh', 'apbd_kabkota', ...S1));
    cek(skalar_int('SELECT COUNT(*) AS n FROM rd_perumahan_baris WHERE laporan_id = ?', [$ID]) === 1, 'Angka sumber dana tersimpan tanpa berkas');
    kirim_form('kab', 'Rekam_Perumahan/input?laporan=' . $ID . '&langkah=review', 'Rekam_Perumahan/kirim', ['laporan_id' => $ID]);
    cek(skalar_str('SELECT status AS s FROM rd_laporan WHERE id = ?', [$ID]) === 'draft',
        'Kirim DITOLAK: sumber dengan realisasi tanpa BNBA');

    echo "\n[3] Unggah sah\n";
    $bagus = buat_xlsx([baris_ok('3305050903730004', $NAMA_KAB),
        baris_ok('3305110607760004', strtoupper($NAMA_KAB), 20000000, 300000)]);
    simpan_dengan_bnba('kab', $isian, sumber_data($ID, 'pk_rtlh', 'apbd_kabkota', ...S2), $bagus);
    $bs = bnba_baris($ID);
    wajib($bs !== NULL, 'BNBA tercatat di rd_perumahan_bnba_sumber');
    cek((int) $bs['jumlah_penerima'] === 2, 'Jumlah penerima = 2');
    cek((int) $bs['total_material'] === 37500000, 'Total material = 17.500.000 + 20.000.000');
    cek((int) $bs['total_padat_karya'] === 550000, 'Total padat karya = 250.000 + 300.000');
    cek(ada_di_disk($ID, $bs['path_privat']), 'Berkas Excel tersimpan privat di disk');
    $lama = $bs['path_privat'];
    $r = http('kab', $isian);
    cek(strpos($r['body'], 'unduh_bnba_sumber/') !== FALSE && strpos($r['body'], '2 penerima') !== FALSE,
        'Layar isian menampilkan ringkasan BNBA dan tautan unduh');

    echo "\n[3b] Sel angka berformat rupiah dan NIK bertipe angka\n";
    // Excel asli dinas: uang sering bertipe ANGKA berformat "#,##0.00". Dulu dibaca sebagai teks
    // terformat lalu pemisahnya dibuang, sehingga 17.500.000 jadi 1.750.000.000.
    $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sh = $ss->getActiveSheet();
    $sh->fromArray([['NIK', 'NO KK', 'NAMA', 'ALAMAT', 'KABUPATEN', 'KECAMATAN', 'DESA/KELURAHAN', 'KDDESA', 'JUMLAH MATERIAL', 'JUMLAH PADAT KARYA']]);
    $sh->fromArray([[3305050903730004, '', 'Uji Angka', 'RT01', $NAMA_KAB, $DESA['kec'], $DESA['desa'], $DESA['kode'], 17500000, 250000]], NULL, 'A2');
    $sh->getStyle('I2:J2')->getNumberFormat()->setFormatCode('#,##0.00');
    $angka = tempnam(sys_get_temp_dir(), 'ujibn_') . '.xlsx';
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save($angka);
    $tmp_berkas[] = $angka;
    simpan_dengan_bnba('kab', $isian, sumber_data($ID, 'pk_rtlh', 'apbd_kabkota', ...S1), $angka);
    $bs = bnba_baris($ID);
    cek((int) $bs['jumlah_penerima'] === 1 && (int) $bs['total_material'] === 17500000 && (int) $bs['total_padat_karya'] === 250000,
        'Uang bertipe angka berformat #,##0.00 terbaca 17.500.000 (bukan 100 kali lipat), NIK angka diterima');
    // Kembalikan ke berkas dua-penerima supaya bagian berikut tetap berpatokan sama.
    simpan_dengan_bnba('kab', $isian, sumber_data($ID, 'pk_rtlh', 'apbd_kabkota', ...S2), $bagus);
    $lama = bnba_baris($ID)['path_privat'];

    require_once APP_ROOT . '/application/libraries/Rekam_bnba_import.php';
    cek(Rekam_bnba_import::kab_cocok('Kebumen', 'Kabupaten Kebumen') && Rekam_bnba_import::kab_cocok('KAB. KEBUMEN', 'Kabupaten Kebumen')
        && ! Rekam_bnba_import::kab_cocok('Kota Semarang', 'Kabupaten Semarang') && ! Rekam_bnba_import::kab_cocok('Kabupaten Tegal', 'Kota Tegal'),
        'Kabupaten dan kota bernama sama tidak tertukar bila awalannya ditulis');
    cek(Rekam_bnba_import::rupiah('17.500.000') === 17500000 && Rekam_bnba_import::rupiah('17500000,50') === NULL
        && Rekam_bnba_import::rupiah(17500000.5) === NULL && Rekam_bnba_import::rupiah('-5') === NULL,
        'Rupiah: pemisah ribuan diterima, desimal dan negatif ditolak');

    echo "\n[3c] Alur templat: unduh, isi, unggah balik\n";
    $d = $DESA;
    $body = simpan_dengan_bnba('kab', $isian, sumber_data($ID, 'pk_rtlh', 'apbd_kabkota', ...T1), $isi_templat([
        ['3305150101700001', '3305150101090001', 'Lewat Templat', 'RT01', strtoupper(preg_replace('/^Kabupaten\s+/i', '', $NAMA_KAB)), strtolower($d['kec']), strtolower($d['desa']), '', 17500000, 2500000],
    ]));
    $kini = bnba_baris($ID);
    cek((int) $kini['jumlah_penerima'] === 1 && strpos($body, 'BNBA ditolak') === FALSE,
        'Templat yang diisi lalu diunggah balik diterima (KDDESA terisi rumus, NIK tetap 16 angka)');
    $body = simpan_dengan_bnba('kab', $isian, sumber_data($ID, 'pk_rtlh', 'apbd_kabkota', ...T1), $isi_templat([
        ['3305150101700001', '', 'Desa Salah Ketik', 'RT01', $NAMA_KAB, $d['kec'], $d['desa'] . 'X', '', 17500000, 2500000],
    ]));
    cek(strpos($body, 'KDDESA kosong') !== FALSE, 'Desa salah ketik: KDDESA kosong dari rumus, ditolak dengan peringatan');
    simpan_dengan_bnba('kab', $isian, sumber_data($ID, 'pk_rtlh', 'apbd_kabkota', ...S2), $bagus);
    $lama = bnba_baris($ID)['path_privat'];

    echo "\n[4] Penolakan: satu baris salah menolak seluruh berkas, yang lama tetap\n";
    $kasus = [
        'NIK bukan 16 angka'      => [baris_ok('330505090373000', $NAMA_KAB)],
        'NIK ganda dalam berkas'  => [baris_ok('3305050903730004', $NAMA_KAB), baris_ok('3305050903730004', $NAMA_KAB)],
        'kabupaten bukan wilayah' => [baris_ok('3305050903730004', $LAIN)],
        'material bukan angka'    => [baris_ok('3305050903730004', $NAMA_KAB, 'banyak')],
        'berkas tanpa penerima'   => [],
        'KDDESA kosong'           => [array_replace(baris_ok('3305050903730004', $NAMA_KAB), [7 => ''])],
        'KDDESA desa kab. lain'   => [array_replace(baris_ok('3305050903730004', $NAMA_KAB), [7 => $DESA_LAIN['kode']])],
    ];
    foreach ($kasus as $nama => $isi) {
        $body = simpan_dengan_bnba('kab', $isian, sumber_data($ID, 'pk_rtlh', 'apbd_kabkota', ...S1), buat_xlsx($isi));
        $kini = bnba_baris($ID);
        cek($kini && $kini['path_privat'] === $lama && (int) $kini['jumlah_penerima'] === 2 && strpos($body, 'BNBA ditolak') !== FALSE,
            "Ditolak dengan pesan, BNBA lama utuh: {$nama}");
    }
    $body = simpan_dengan_bnba('kab', $isian, sumber_data($ID, 'pk_rtlh', 'apbd_kabkota', ...S1),
        buat_xlsx([baris_ok('3305050903730004', $NAMA_KAB)], ['NIK', 'NAMA']));
    cek(strpos($body, 'Kolom tidak ditemukan') !== FALSE, 'Judul kolom tak lengkap ditolak dengan daftar kolom yang hilang');
    $tak_excel = tempnam(sys_get_temp_dir(), 'ujibn_') . '.xlsx';
    file_put_contents($tak_excel, 'bukan excel');
    $tmp_berkas[] = $tak_excel;
    $body = simpan_dengan_bnba('kab', $isian, sumber_data($ID, 'pk_rtlh', 'apbd_kabkota', ...S1), $tak_excel);
    cek(strpos($body, 'BNBA ditolak') !== FALSE, 'Berkas palsu berekstensi xlsx ditolak');
    cek(skalar_int('SELECT COUNT(*) AS n FROM rd_perumahan_bnba_sumber s JOIN rd_perumahan_baris b ON b.id = s.baris_id WHERE b.laporan_id = ?', [$ID]) === 1,
        'Tetap satu BNBA untuk sumber dana ini');

    echo "\n[4b] Pesan sampai ke layar lewat muat parsial (jalur yang dipakai wizard di peramban)\n";
    // Wizard mengirim form lewat admin-progressive.js (X-Shell: admin). Balasan parsialnya dulu tidak
    // membawa pesan flash, jadi "BNBA ditolak" terbaca lalu terbuang tanpa pernah tampil.
    $data = sumber_data($ID, 'pk_rtlh', 'apbd_kabkota', ...S1);
    $data['csrf_kpkp_token'] = csrf('kab', $isian);
    $data['bnba'] = new CURLFile(buat_xlsx([baris_ok('330505090373000', $NAMA_KAB)]), 'application/octet-stream', 'bnba.xlsx');
    $ch = curl_init(BASE_URL . '/Rekam_Perumahan/simpan_sumber');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_COOKIEJAR => jar('kab'), CURLOPT_COOKIEFILE => jar('kab'),
        CURLOPT_FOLLOWLOCATION => TRUE, CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => $data, CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest', 'X-Shell: admin']]);
    $parsial = (string) curl_exec($ch);
    curl_close($ch);
    cek(stripos($parsial, '<html') === FALSE && preg_match('/data-kpkp-flash-notifications>[^<]*BNBA ditolak/', $parsial) === 1,
        'Balasan parsial membawa pesan "BNBA ditolak" untuk ditampilkan loader');

    echo "\n[5] Ganti berkas membuang yang lama dari disk\n";
    simpan_dengan_bnba('kab', $isian, sumber_data($ID, 'pk_rtlh', 'apbd_kabkota', ...S1), buat_xlsx([baris_ok('3305050903730004', $NAMA_KAB)]));
    $kini = bnba_baris($ID);
    cek((int) $kini['jumlah_penerima'] === 1 && $kini['path_privat'] !== $lama, 'Unggah ulang mengganti isi');
    cek( ! ada_di_disk($ID, $lama), 'Berkas lama terhapus dari disk');
    $BARIS = (int) $kini['baris'];

    echo "\n[5b] Revisi dinas 29 Okt: isi BNBA harus sama dengan realisasi\n";
    $body = simpan_dengan_bnba('kab', $isian, sumber_data($ID, 'pk_rtlh', 'apbd_kabkota', ...S1), $bagus);
    cek(strpos($body, 'jumlah penerima 2, padahal realisasi 1 unit') !== FALSE && (int) bnba_baris($ID)['jumlah_penerima'] === 1,
        'Jumlah penerima beda dengan realisasi unit: ditolak, BNBA lama utuh');
    $body = simpan_dengan_bnba('kab', $isian, sumber_data($ID, 'pk_rtlh', 'apbd_kabkota', 1, 20000000), buat_xlsx([baris_ok('3305050903730004', $NAMA_KAB)]));
    cek(strpos($body, 'padahal realisasi anggaran Rp 20.000.000') !== FALSE, 'Rupiah BNBA beda dengan realisasi anggaran: ditolak');
    simpan_dengan_bnba('kab', $isian, sumber_data($ID, 'pk_rtlh', 'apbd_kabkota', ...S2)); // angka diubah TANPA unggah ulang
    cek(strpos(http('kab', $isian)['body'], 'BNBA tidak cocok dengan realisasi') !== FALSE, 'Angka diubah sesudah unggah: layar menandai BNBA tidak cocok');
    $r = kirim_form('kab', 'Rekam_Perumahan/input?laporan=' . $ID . '&langkah=review', 'Rekam_Perumahan/kirim', ['laporan_id' => $ID]);
    cek(skalar_str('SELECT status AS s FROM rd_laporan WHERE id = ?', [$ID]) === 'draft', 'Kirim DITOLAK selama BNBA tidak cocok dengan realisasi');
    simpan_dengan_bnba('kab', $isian, sumber_data($ID, 'pk_rtlh', 'apbd_kabkota', ...S1)); // kembali cocok

    echo "\n[6] Unduh ter-scope\n";
    $u = http('kab', 'Rekam_Perumahan/unduh_bnba_sumber/' . $BARIS);
    cek($u['code'] === 200 && substr($u['body'], 0, 2) === 'PK', 'Admin kabupaten pemilik bisa mengunduh (xlsx utuh)');
    $u = http('bid', 'Rekam_Perumahan/unduh_bnba_sumber/' . $BARIS);
    cek(substr($u['body'], 0, 2) !== 'PK', 'Admin bidang TIDAK bisa mengunduh lewat jalur kabupaten');

    echo "\n[7] Kirim sesudah BNBA lengkap, dan berkas yatim\n";
    simpan_dengan_bnba('kab', $isian, sumber_data($ID, 'pk_rtlh', 'csr', ...S1), buat_xlsx([baris_ok('3305110607760004', $NAMA_KAB)]));
    $csr = q('SELECT s.path_privat FROM rd_perumahan_bnba_sumber s JOIN rd_perumahan_baris b ON b.id = s.baris_id
              WHERE b.laporan_id = ? AND b.sumber_dana = ?', [$ID, 'csr']);
    wajib($csr !== NULL && ada_di_disk($ID, $csr['path_privat']), 'BNBA sumber kedua tersimpan');
    kirim_form('kab', $isian, 'Rekam_Perumahan/hapus_sumber', ['laporan_id' => $ID, 'program' => 'pk_rtlh', 'sumber_dana' => 'csr']);
    cek( ! ada_di_disk($ID, $csr['path_privat']), 'Hapus sumber dana membuang berkas BNBA-nya dari disk');
    cek(skalar_int('SELECT COUNT(*) AS n FROM rd_perumahan_bnba_sumber s LEFT JOIN rd_perumahan_baris b ON b.id = s.baris_id WHERE b.id IS NULL') === 0,
        'Tidak ada baris BNBA yatim di DB');

    $r = http('kab', 'Rekam_Perumahan/input?laporan=' . $ID . '&langkah=bnba');
    cek(strpos($r['body'], 'BNBA per sumber dana') !== FALSE && strpos($r['body'], '1 penerima') !== FALSE,
        'Langkah BNBA menampilkan status per sumber dana');

    kirim_form('kab', 'Rekam_Perumahan/input?laporan=' . $ID . '&langkah=review', 'Rekam_Perumahan/kirim', ['laporan_id' => $ID]);
    cek(skalar_str('SELECT status AS s FROM rd_laporan WHERE id = ?', [$ID]) === 'terkirim', 'Kirim BERHASIL setelah semua sumber ber-BNBA');

    $t = http('bid', 'Rekam_Tinjauan/detail/' . $ID);
    cek(strpos($t['body'], 'Lampiran BNBA') !== FALSE && preg_match('/tabular-nums[^"]*">1<\/td>/', $t['body']) === 1,
        'Peninjau melihat ringkasan BNBA per sumber dana');

    echo "\n[8] Mencabut centang program membuang berkasnya\n";
    kirim_form('kab', 'Rekam_Perumahan/input', 'Rekam_Perumahan/mulai', ['tahun' => TAHUN, 'triwulan' => 2]);
    $ID2 = skalar_int('SELECT id AS n FROM rd_laporan WHERE tahun = ? AND triwulan = 2 AND domain = ?', [TAHUN, 'perumahan']);
    $hal2 = 'Rekam_Perumahan/input?laporan=' . $ID2 . '&langkah=isian&program=pb_backlog';
    kirim_form('kab', 'Rekam_Perumahan/input?laporan=' . $ID2 . '&langkah=program', 'Rekam_Perumahan/simpan_program',
        ['laporan_id' => $ID2, 'program' => ['pb_backlog']]);
    simpan_dengan_bnba('kab', $hal2, sumber_data($ID2, 'pb_backlog', 'apbd_kabkota', ...S1), buat_xlsx([baris_ok('3305110607760004', $NAMA_KAB)]));
    $p = bnba_baris($ID2);
    wajib($p !== NULL, 'BNBA di laporan kedua tersimpan');
    kirim_form('kab', 'Rekam_Perumahan/input?laporan=' . $ID2 . '&langkah=program', 'Rekam_Perumahan/simpan_program',
        ['laporan_id' => $ID2, 'program' => ['pk_rtlh']]);
    cek( ! ada_di_disk($ID2, $p['path_privat']), 'Berkas ikut hilang dari disk saat programnya dicabut');
} finally {
    // Berkas di disk tidak ikut terhapus oleh FK: sapu direktori laporan uji ini saja.
    foreach (array_filter([$ID ?? 0, $ID2 ?? 0]) as $lap) {
        foreach (glob(private_uploads_dir('rekam_bnba', $lap) . '*') ?: [] as $f) { @unlink($f); }
        @rmdir(private_uploads_dir('rekam_bnba', $lap));
    }
    bersihkan();
    foreach ($tmp_berkas as $f) { @unlink($f); }
}

cek(skalar_int('SELECT COUNT(*) AS n FROM rd_laporan WHERE tahun = ?', [TAHUN]) === 0, 'Data uji dibersihkan');
echo "\nRINGKASAN: {$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
