<?php
/**
 * Uji pendaftaran KKN/Magang — identitas mahasiswa + berkas proposal
 * (migrasi 20260701000025).
 *
 *   php docs/engineering/uji_kemitraan_daftar.php
 *
 * Env opsional: UJI_BASE_URL, UJI_MHS_EMAIL, UJI_MHS_PASSWORD
 *
 * INTI skrip ini bukan "formulir jalan", tapi dua uji NEGATIF:
 *
 *   1. Proposal dikirim pada pendaftaran KKN -> HARUS diabaikan server.
 *      Formulir KKN memang tidak merender field-nya, tapi tidak merender
 *      sesuatu bukan penjagaan — siapa pun bisa menambahkannya sendiri. Yang
 *      menentukan apa yang tersimpan adalah `KemitraanPortal::simpan()`.
 *   2. `Admin_Kemitraan::lihat_dokumen()` menerima nama berkas dari URL. Kalau
 *      itu dipetakan ke nama kolom mentah, siapa pun bisa membaca kolom apa
 *      pun. Skrip menembakkan nama kolom asli dan menuntut 404.
 *
 * DEFINISI SELESAI: balikkan salah satu penjagaan itu dan skrip ini WAJIB
 * merah di titik yang diramalkan. Seluruh jejaknya dihapus di akhir.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('MHS_EMAIL', getenv('UJI_MHS_EMAIL') ?: 'mahasiswa@example.com');
define('MHS_PASSWORD', getenv('UJI_MHS_PASSWORD') ?: 'password');
define('ADM_EMAIL', getenv('UJI_ADM_EMAIL') ?: 'admin@klinikpkp.jatengprov.go.id');
define('ADM_PASSWORD', getenv('UJI_ADM_PASSWORD') ?: 'password');
define('SENTINEL', 'UJI-KEMITRAAN-' . date('His'));

$GLOBALS['t'] = 0; $GLOBALS['g'] = 0;

function cek($kondisi, $label) {
    $GLOBALS['t']++;
    echo ($kondisi ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $kondisi) { $GLOBALS['g']++; }
    return (bool) $kondisi;
}

function wajib($kondisi, $label) {
    if ( ! cek($kondisi, $label)) { bersihkan(); fwrite(STDERR, "Berhenti: prasyarat gagal.\n"); exit(1); }
}

/** Parser .env milik index.php: kemunculan PERTAMA menang, baris '#' dilewati. */
function env_config($path) {
    $out = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $baris) {
        $baris = trim($baris);
        if ($baris === '' || $baris[0] === '#' || strpos($baris, '=') === FALSE) { continue; }
        [$k, $v] = explode('=', $baris, 2);
        if ( ! array_key_exists(trim($k), $out)) { $out[trim($k)] = trim($v); }
    }
    return $out;
}

$env = env_config(APP_ROOT . '/.env');
$db = new mysqli($env['DB_HOST'] ?? 'localhost', $env['DB_USER'] ?? 'root',
    $env['DB_PASS'] ?? '', $env['DB_NAME'] ?? 'klinikpkp');
if ($db->connect_error) { fwrite(STDERR, "Koneksi DB gagal: {$db->connect_error}\n"); exit(1); }

/**
 * Akar berkas privat, DITURUNKAN sama seperti aplikasinya (private_upload_helper):
 * `PRIVATE_UPLOADS_PATH` di .env kalau diisi, kalau tidak `dirname(FCPATH)`.
 *
 * Sempat saya hardcode ke APP_ROOT . '/private_uploads' dan tiga pemeriksaan
 * merah — padahal berkasnya tersimpan dengan benar. Yang salah alat ukurnya.
 * Menebak jalur yang sudah punya satu sumber kebenaran adalah cara membuat
 * harness melapor gagal untuk hal yang tidak pernah rusak.
 */
function akar_privat() {
    global $env;
    $p = trim($env['PRIVATE_UPLOADS_PATH'] ?? '');
    if ($p === '') { return dirname(APP_ROOT) . '/private_uploads'; }
    // Pemisah disamakan ke '/' dulu supaya sisanya tidak perlu berurusan
    // dengan backslash Windows sama sekali.
    $p = strtr($p, [chr(92) => '/']);
    $mutlak = preg_match('#^([A-Za-z]:/|/)#', $p);
    return rtrim($mutlak ? $p : APP_ROOT . '/' . ltrim($p, '/'), '/');
}

function dir_kemitraan($id) { return akar_privat() . '/kemitraan/' . (int) $id; }

function baris($sql, $params = []) {
    global $db;
    $stmt = $db->prepare($sql);
    if ($params) { $stmt->bind_param(str_repeat('s', count($params)), ...$params); }
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : NULL;
    $stmt->close();
    return $row;
}

// ---------------------------------------------------------------- HTTP

// Dua sesi terpisah: mahasiswa yang mendaftar, dan admin yang membuka
// dokumennya. Satu jar untuk keduanya membuat uji whitelist di bawah tidak
// pernah sampai ke parameternya — ia tertahan di gerbang peran lebih dulu.
$jars = ['mhs' => tempnam(sys_get_temp_dir(), 'ujikm_m'), 'admin' => tempnam(sys_get_temp_dir(), 'ujikm_a')];
$jar_aktif = 'mhs';

function pakai_sesi($nama) { $GLOBALS['jar_aktif'] = $nama; }

function http($path, $post = NULL, $follow = TRUE) {
    global $jars, $jar_aktif;
    $jar = $jars[$jar_aktif];
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_COOKIEJAR      => $jar,
        CURLOPT_COOKIEFILE     => $jar,
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_TIMEOUT        => 30,
    ]);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest']);
    if ($post !== NULL) {
        curl_setopt($ch, CURLOPT_POST, TRUE);
        // Array (bukan string) supaya CURLFile ikut terkirim sebagai multipart.
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    $body = (string) curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body];
}

function token($path = 'KemitraanPortal/daftar/magang') {
    $r = http($path);
    return preg_match('/name="csrf_kpkp_token" value="([^\"]+)"/', $r['body'], $m) ? $m[1] : '';
}

/**
 * Login menembak `Auth/do_login` (bukan `Auth/login`, yang cuma merender form),
 * membawa token CSRF dari halaman itu, dan menandai dirinya AJAX supaya
 * balasannya JSON. Sama persis dengan harness wizard.
 */
function login($email, $password) {
    $t = token('Auth/login');
    if ($t === '') { return FALSE; }
    $r = http('Auth/do_login', ['csrf_kpkp_token' => $t, 'email' => $email, 'password' => $password]);
    $json = json_decode($r['body'], TRUE);
    return ($json['status'] ?? '') === 'success';
}

/** PDF minimal yang lolos finfo — store_private_upload mencocokkan ekstensi DENGAN mime. */
function berkas_pdf($nama) {
    $path = sys_get_temp_dir() . '/' . $nama . '.pdf';
    file_put_contents($path, "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n");
    return $path;
}

$berkas_sementara = [];

function bersihkan() {
    global $db, $jars, $berkas_sementara;
    foreach ($db->query("SELECT id FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE '" . SENTINEL . "%'") ?: [] as $r) {
        $dir = dir_kemitraan($r['id']);
        if (is_dir($dir)) {
            foreach (glob($dir . '/*') as $f) { @unlink($f); }
            @rmdir($dir);
        }
    }
    $db->query("DELETE FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE '" . SENTINEL . "%'");
    // Slot 2099 yang dibuka skrip ini — tahun itu tidak pernah dipakai data asli.
    $db->query("DELETE FROM kkn_magang_slot WHERE tahun = 2099");
    foreach ($berkas_sementara as $f) { @unlink($f); }
    foreach ($jars as $f) { @unlink($f); }
}

// ---------------------------------------------------------------- jalan

echo "Uji pendaftaran KKN/Magang — " . BASE_URL . "\n\n";

echo "== Prasyarat ==\n";
// Kode HTTP saja TIDAK cukup: login gagal juga membalas 200. Yang menentukan
// isi JSON-nya — pelajaran dari harness wizard yang sempat hijau padahal
// akunnya tidak pernah masuk.
wajib(login(MHS_EMAIL, MHS_PASSWORD), 'Login mahasiswa berhasil (diperiksa dari JSON, bukan kode HTTP)');

$form_kkn    = http('KemitraanPortal/daftar/kkn');
$form_magang = http('KemitraanPortal/daftar/magang');
wajib($form_kkn['code'] === 200 && $form_magang['code'] === 200, 'Kedua formulir terbuka untuk peran mahasiswa');

echo "\n== Formulir menampilkan medan yang benar ==\n";
foreach (['nim', 'tempat_lahir', 'tanggal_lahir', 'semester', 'jurusan'] as $f) {
    cek(strpos($form_magang['body'], 'name="' . $f . '"') !== FALSE, "Medan identitas `$f` ada di formulir");
}
cek(strpos($form_magang['body'], 'name="file_proposal"') !== FALSE, 'Magang menawarkan unggahan proposal');
cek(strpos($form_kkn['body'], 'name="file_proposal"') === FALSE, 'KKN TIDAK menawarkan unggahan proposal');
cek(strpos($form_magang['body'], 'name="file_surat_pengantar"') !== FALSE, 'Surat pengantar tetap ada');

// Sejak migrasi 20260701000026, pendaftaran MAGANG tunduk pada slot: divisi
// harus terdaftar & aktif, dan setiap bulan dalam periodenya harus terbuka.
// Skrip ini memakai periode 2099 yang tidak punya slot sama sekali, jadi
// slotnya dibuka di sini dan ditutup lagi di bersihkan(). Dibuka untuk KEDUA
// divisi supaya uji "tanpa NIM" tetap menguji NIM — bukan lulus karena kebetulan
// tertahan penjagaan slot lebih dulu. KKN tidak terpengaruh: di sana field yang
// sama berarti tema kegiatan, bukan unit kerja.
foreach (['Sekretariat', 'Infrastruktur dan Teknologi Digital'] as $nama_divisi) {
    $d = baris("SELECT id FROM kkn_magang_divisi WHERE nama = ?", [$nama_divisi]);
    if ( ! $d) { continue; }
    // tgl_mulai/tgl_selesai WAJIB sejak migrasi 20260701000028 — sebulan penuh
    // dinyatakan eksplisit, bukan lewat NULL. LAST_DAY() mengurus panjang bulan
    // supaya skrip ini tidak perlu tahu Februari 2099 berapa hari.
    foreach ([1, 2] as $bulan) {
        $awal = sprintf('2099-%02d-01', $bulan);
        $db->query("INSERT IGNORE INTO kkn_magang_slot (divisi_id, tahun, bulan, tgl_mulai, tgl_selesai) VALUES ("
            . (int) $d['id'] . ", 2099, " . (int) $bulan . ", '" . $awal . "', LAST_DAY('" . $awal . "'))");
    }
}

echo "\n== Validasi menolak identitas kosong ==\n";
$tanpa_nim = http('KemitraanPortal/simpan', [
    'csrf_kpkp_token' => token(), 'jenis' => 'magang',
    'nim' => '', 'tempat_lahir' => 'Semarang', 'tanggal_lahir' => '2003-01-01',
    'semester' => '6', 'jurusan' => 'Teknik Sipil',
    'instansi_asal' => SENTINEL . '-TOLAK', 'no_hp' => '081234567890',
    'divisi_atau_tema' => 'Sekretariat', 'periode_mulai' => '2099-01-01', 'periode_selesai' => '2099-02-01',
]);
cek($tanpa_nim['code'] === 200,
    'POST tanpa NIM tidak meledak');
cek(baris("SELECT id FROM kkn_magang_pendaftaran WHERE instansi_asal = ?", [SENTINEL . '-TOLAK']) === NULL,
    'POST tanpa NIM TIDAK membuat baris — validasi menahannya');

echo "\n== Magang: identitas + dua berkas tersimpan ==\n";
$berkas_sementara[] = $p1 = berkas_pdf('uji_surat');
$berkas_sementara[] = $p2 = berkas_pdf('uji_proposal');
http('KemitraanPortal/simpan', [
    'csrf_kpkp_token' => token(), 'jenis' => 'magang',
    'nim' => 'H1A020099', 'tempat_lahir' => 'Purwokerto', 'tanggal_lahir' => '2003-05-17',
    'semester' => '6', 'jurusan' => 'Perencanaan Wilayah',
    'instansi_asal' => SENTINEL . '-MAGANG', 'no_hp' => '081234567890',
    'divisi_atau_tema' => 'Infrastruktur dan Teknologi Digital',
    'periode_mulai' => '2099-01-01', 'periode_selesai' => '2099-02-01',
    'file_surat_pengantar' => new CURLFile($p1, 'application/pdf', 'surat.pdf'),
    'file_proposal'        => new CURLFile($p2, 'application/pdf', 'proposal.pdf'),
]);
$m = baris("SELECT * FROM kkn_magang_pendaftaran WHERE instansi_asal = ?", [SENTINEL . '-MAGANG']);
wajib($m !== NULL, 'Pendaftaran magang tersimpan');
cek($m['nim'] === 'H1A020099', 'NIM tersimpan');
cek($m['tempat_lahir'] === 'Purwokerto', 'Tempat lahir tersimpan');
cek($m['tanggal_lahir'] === '2003-05-17', 'Tanggal lahir tersimpan sebagai DATE');
cek((int) $m['semester'] === 6, 'Semester tersimpan');
cek($m['jurusan'] === 'Perencanaan Wilayah', 'Jurusan tersimpan');
cek( ! empty($m['file_surat_pengantar']), 'Surat pengantar tercatat di DB');
cek( ! empty($m['file_proposal']), 'Proposal tercatat di DB');
$dir = dir_kemitraan($m['id']);
cek( ! empty($m['file_surat_pengantar']) && is_file($dir . '/' . $m['file_surat_pengantar']),
    'Surat pengantar benar-benar ada di disk, di luar webroot');
cek( ! empty($m['file_proposal']) && is_file($dir . '/' . $m['file_proposal']),
    'Proposal benar-benar ada di disk, di luar webroot');

echo "\n== NEGATIF: proposal pada KKN harus DIBUANG server ==\n";
$berkas_sementara[] = $p3 = berkas_pdf('uji_selundup');
http('KemitraanPortal/simpan', [
    'csrf_kpkp_token' => token(), 'jenis' => 'kkn',
    'nim' => 'H1A020100', 'tempat_lahir' => 'Solo', 'tanggal_lahir' => '2002-11-02',
    'semester' => '7', 'jurusan' => 'Arsitektur',
    'instansi_asal' => SENTINEL . '-KKN', 'no_hp' => '081234567891',
    'divisi_atau_tema' => 'Penataan Kawasan Kumuh',
    'periode_mulai' => '2099-03-01', 'periode_selesai' => '2099-04-01',
    'file_surat_pengantar' => new CURLFile($p1, 'application/pdf', 'surat.pdf'),
    // Sengaja dikirim walau formulir KKN tidak pernah menampilkannya.
    'file_proposal'        => new CURLFile($p3, 'application/pdf', 'selundupan.pdf'),
]);
$k = baris("SELECT * FROM kkn_magang_pendaftaran WHERE instansi_asal = ?", [SENTINEL . '-KKN']);
wajib($k !== NULL, 'Pendaftaran KKN tersimpan');
cek( ! empty($k['file_surat_pengantar']), 'KKN tetap menerima surat pengantar');
cek(empty($k['file_proposal']),
    'Proposal yang diselundupkan ke KKN TIDAK tersimpan — gerbangnya di server, bukan di formulir');
$dir_kkn = dir_kemitraan($k['id']);
cek(count(glob($dir_kkn . '/*') ?: []) === 1,
    'Hanya SATU berkas mendarat di folder KKN — selundupan tidak ikut ditulis ke disk');

echo "\n== NEGATIF: nama berkas di URL tidak boleh jadi nama kolom ==\n";
pakai_sesi('admin');
wajib(login(ADM_EMAIL, ADM_PASSWORD), 'Login admin berhasil (sesi terpisah dari mahasiswa)');

// Kunci SAH harus 200 lebih dulu. Tanpa ini "semua ditolak" ikut lulus —
// termasuk kalau method-nya rusak total dan menolak segalanya.
foreach (['surat', 'proposal'] as $sah) {
    $r = http('Admin_Kemitraan/lihat_dokumen/' . (int) $m['id'] . '/' . $sah, NULL, FALSE);
    cek($r['code'] === 200, "Kunci sah `$sah` menyajikan berkas (kode {$r['code']})");
}

foreach (['file_surat_pengantar', 'catatan_admin', 'status', 'ngawur'] as $tembakan) {
    $r = http('Admin_Kemitraan/lihat_dokumen/' . (int) $m['id'] . '/' . $tembakan, NULL, FALSE);
    // 404 PERSIS, bukan "yang penting bukan 200". Versi pertama skrip ini
    // menerima 302/307 juga, dan karenanya HIJAU tanpa menguji apa pun: sesi
    // yang dipakai mahasiswa, jadi semuanya tertahan di gerbang peran lebih
    // dulu dan parameter berkasnya tidak pernah sampai dibaca.
    cek($r['code'] === 404, "Nama kolom mentah `$tembakan` ditolak 404 (kode {$r['code']})");
}

bersihkan();
echo "\nRINGKASAN: {$GLOBALS['t']} pemeriksaan, {$GLOBALS['g']} gagal\n";
exit($GLOBALS['g'] > 0 ? 1 : 0);
