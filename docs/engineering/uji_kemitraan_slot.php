<?php
/**
 * Uji slot magang - data, pengelolaan, dan penegakannya (migrasi 20260701000026).
 *
 *   php docs/engineering/uji_kemitraan_slot.php
 *
 * Env opsional: UJI_BASE_URL, UJI_MHS_EMAIL, UJI_MHS_PASSWORD,
 *               UJI_ADM_EMAIL, UJI_ADM_PASSWORD
 *
 * Slot dulu hanya array literal di `KemitraanPortal::magang()` dan tidak
 * mengikat apa pun: `divisi_atau_tema` adalah teks bebas, jadi pendaftar bisa
 * mengetik divisi yang di layar berwarna merah. Yang diuji di sini:
 *
 *   1. Papan slot benar-benar dibaca dari DB - divisi yang dinonaktifkan
 *      superadmin hilang dari halaman publik.
 *   2. Penegakan SETIAP BULAN, bukan cuma bulan mulai. Ini intinya: divisi
 *      dengan Juni dan Agustus terbuka tapi Juli tertutup HARUS menolak
 *      pendaftaran Juni-Agustus. Kalau hanya bulan mulai yang diperiksa,
 *      pemeriksaan itu lulus dan papan slotnya berbohong.
 *   3. Select di formulir bukan penjagaan - divisi yang tidak ada dan divisi
 *      nonaktif ditembakkan langsung ke endpoint dan harus ditolak.
 *   4. Layar pengelolaan tertutup untuk non-superadmin.
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
define('BID_EMAIL', getenv('UJI_BID_EMAIL') ?: 'adminbidang@example.com');
define('BID_PASSWORD', getenv('UJI_BID_PASSWORD') ?: 'password');

// Bidang TIDAK bisa dibuat skrip ini - daftarnya struktur organisasi dinas.
// Dipakai bidang milik akun admin_bidang yang ada supaya alur tahap dua bisa
// diuji utuh; keadaannya (slot tahun uji, kuota, aktif) dipulihkan di akhir.
define('BIDANG_UJI', getenv('UJI_BIDANG') ?: 'perumahan');
define('SENTINEL', 'UJI-SLOT-' . date('His'));
// Tahun uji sengaja JAUH di depan. `simpan_slot_bidang` menulis ulang SELURUH
// tahun untuk satu bidang, dan `bersihkan()` menghapus seluruh slot tahun ini -
// jadi tahun yang dipakai konfigurasi sungguhan akan musnah kalau dipilih.
//
// Sempat `date('Y') + 1`, dan itu meleset 2 Agt 2026 begitu slot 2027 benar-
// benar ditetapkan: menjalankan harness akan menghapusnya tanpa peringatan.
// Tahun uji harus di luar jangkauan perencanaan, bukan sekadar "belum dipakai
// hari ini". Dipatok +5 karena itu batas atas yang masih diterima
// `Admin_Kemitraan::tahun_sah()` - lebih jauh dari itu endpointnya menolak, dan
// harness akan merah karena batas yang benar, bukan karena bug.
define('TAHUN_UJI', (int) date('Y') + 5);

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

// TIGA sesi: sekretariat (superadmin), bidang (meja kedua), mahasiswa. Satu jar
// untuk beberapa peran membuat uji guard di bawah tidak menguji apa pun.
$jars = ['adm' => tempnam(sys_get_temp_dir(), 'ujisl_a'), 'mhs' => tempnam(sys_get_temp_dir(), 'ujisl_m'),
         'bid' => tempnam(sys_get_temp_dir(), 'ujisl_b')];
$jar_aktif = 'adm';

function pakai_sesi($nama) { $GLOBALS['jar_aktif'] = $nama; }

/**
 * @param bool $ajax Menandai diri XMLHttpRequest. Default MATI, dan itu
 *   penting: `MY_Controller::render()` mengirim view telanjang TANPA layout
 *   untuk permintaan AJAX, sehingga `components/notification_center` - satu-
 *   satunya tempat flashdata dirender - tidak ikut. Versi pertama skrip ini
 *   memasang header itu pada semua permintaan dan akibatnya membaca "tidak ada
 *   pesan penolakan" untuk penolakan yang sebenarnya terjadi. Hanya `login()`
 *   yang memakainya, karena `Auth::do_login` memang membalas JSON di sana.
 */
function http($path, $post = NULL, $ajax = FALSE) {
    global $jars, $jar_aktif;
    $jar = $jars[$jar_aktif];
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_COOKIEJAR      => $jar,
        CURLOPT_COOKIEFILE     => $jar,
        CURLOPT_FOLLOWLOCATION => TRUE,
        CURLOPT_TIMEOUT        => 30,
    ]);
    if ($ajax) { curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest']); }
    if ($post !== NULL) {
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    }
    $body = (string) curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body];
}

function token($path) {
    $r = http($path);
    return preg_match('/name="csrf_kpkp_token" value="([^"]+)"/', $r['body'], $m) ? $m[1] : '';
}

function login($email, $password) {
    $csrf = token('Auth/login');
    if ($csrf === '') { return FALSE; }
    $r = http('Auth/do_login', ['csrf_kpkp_token' => $csrf, 'email' => $email, 'password' => $password], TRUE);
    $json = json_decode($r['body'], TRUE);
    return ($json['status'] ?? '') === 'success';
}

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

function bersihkan() {
    global $db, $jars;
    if ( ! empty($GLOBALS['surat_uji'])) { @unlink($GLOBALS['surat_uji']); }
    $db->query("DELETE FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE '" . SENTINEL . "%'");
    if ( ! empty($GLOBALS['mhs_uji_id'])) {
        $db->query("DELETE FROM kkn_magang_pendaftaran WHERE user_id = " . (int) $GLOBALS['mhs_uji_id']);
        $db->query("DELETE FROM usr_users WHERE id = " . (int) $GLOBALS['mhs_uji_id']);
    }
    // Bidang tidak dihapus - ia struktur organisasi. Yang dipulihkan keadaan
    // magangnya: slot tahun uji dibuang, kuota dan status kembali ke bawaan.
    $db->query("DELETE FROM kkn_magang_slot WHERE tahun = " . TAHUN_UJI);
    $db->query("UPDATE kkn_magang_bidang SET kuota = 2, aktif = 1 WHERE bidang_kode = '"
        . $db->real_escape_string(BIDANG_UJI) . "'");
    foreach ($jars as $f) { @unlink($f); }
}

/**
 * Tetapkan slot satu divisi untuk satu tahun lewat layar admin.
 *
 * http_build_query, bukan array mentah: CURLOPT_POSTFIELDS berbentuk array
 * dikirim sebagai multipart dan TIDAK bisa membawa `bulan[6][buka]` yang
 * bersarang. Sebagai string ia terkirim urlencoded, dan PHP menyusunnya kembali.
 *
 * @param array $bulan   nomor bulan yang dibuka
 * @param array $rentang [bulan => ['Y-m-d mulai', 'Y-m-d selesai']] - tanpa ini
 *                       bulan dibuka penuh
 */
function atur_slot($kode, $tahun, array $bulan, $kuota, array $rentang = []) {
    pakai_sesi('adm');
    $data = [
        'csrf_kpkp_token' => token('Admin_Kemitraan/slot_bidang/' . rawurlencode($kode) . '/' . (int) $tahun),
        'tahun'           => (int) $tahun,
        'kuota'           => (int) $kuota,
        'bulan'           => [],
    ];
    foreach ($bulan as $b) {
        $data['bulan'][(int) $b] = ['buka' => 1];
        if (isset($rentang[$b])) {
            $data['bulan'][(int) $b]['mulai']   = $rentang[$b][0];
            $data['bulan'][(int) $b]['selesai'] = $rentang[$b][1];
        }
    }
    return http('Admin_Kemitraan/simpan_slot_bidang/' . rawurlencode($kode), http_build_query($data));
}

/**
 * PDF kecil sekali pakai untuk lampiran surat pengantar.
 *
 * Dibuat sekali lalu dipakai ulang; dihapus di bersihkan(). Isinya cukup
 * dikenali sebagai PDF oleh store_private_upload().
 */
function berkas_surat() {
    if (empty($GLOBALS['surat_uji'])) {
        $GLOBALS['surat_uji'] = tempnam(sys_get_temp_dir(), 'sursl_') . '.pdf';
        file_put_contents($GLOBALS['surat_uji'], "%PDF-1.4\n% uji slot\n%%EOF\n");
    }
    return $GLOBALS['surat_uji'];
}

/**
 * Kirim pendaftaran magang sebagai mahasiswa; kembalikan body balasannya.
 *
 * Surat pengantar WAJIB untuk magang sejak 2 Agt 2026 dan ditolak sebelum
 * barisnya lahir, jadi lampirannya bukan hiasan: tanpa itu SELURUH uji slot di
 * berkas ini gagal karena sebab yang tidak ada hubungannya dengan kuota.
 */
function daftar_magang($kode, $mulai, $selesai) {
    pakai_sesi('mhs');
    return http('KemitraanPortal/simpan', [
        'file_surat_pengantar' => new CURLFile(berkas_surat(), 'application/pdf', 'surat.pdf'),
        'csrf_kpkp_token'  => token('KemitraanPortal/daftar/magang'),
        'jenis'            => 'magang',
        'nim'              => '2101' . date('His'),
        'tempat_lahir'     => 'Semarang',
        'tanggal_lahir'    => '2003-05-17',
        'semester'         => '6',
        'jurusan'          => 'Teknik Sipil',
        'instansi_asal'    => SENTINEL . ' Universitas Uji',
        'no_hp'            => '081234567890',
        'divisi_atau_tema' => $kode,
        'periode_mulai'    => $mulai,
        'periode_selesai'  => $selesai,
    ])['body'];
}

/**
 * Tandai pendaftaran uji yang menggantung sebagai Diterima.
 *
 * Aturan "satu pendaftaran menggantung per mahasiswa per jenis" diuji
 * tersendiri di bagian CRUD. Di bagian-bagian slot, yang sedang diuji adalah
 * aturan SLOT - dan skenarionya menggambarkan beberapa mahasiswa BERBEDA yang
 * mengisi bulan yang sama, sementara harness ini hanya punya satu akun.
 *
 * Menerima yang sudah masuk menyingkirkan aturan yang tidak sedang diuji tanpa
 * mengubah hitungan yang sedang diuji: Diajukan dan Diterima sama-sama memakan
 * tempat di peta_harian().
 */
function terima_semua() {
    global $db;
    $db->query("UPDATE kkn_magang_pendaftaran SET status = 'Diterima'
        WHERE instansi_asal LIKE '" . SENTINEL . "%' AND status = 'Diajukan'");
}

function jumlah_pendaftaran() {
    global $db;
    return (int) $db->query("SELECT COUNT(*) n FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE '" . SENTINEL . "%'")
        ->fetch_assoc()['n'];
}

echo "\n== Uji slot magang - " . BASE_URL . " ==\n\n";
echo "== Prasyarat ==\n";

// Kode HTTP saja TIDAK cukup: login gagal juga membalas 200. Yang menentukan
// isi JSON-nya.
pakai_sesi('adm');
wajib(login(ADM_EMAIL, ADM_PASSWORD), 'Login superadmin');
/**
 * Mahasiswa SENDIRI, bukan akun demo bersama.
 *
 * Sebelumnya skrip ini masuk sebagai `mahasiswa@example.com`. Begitu akun itu
 * punya satu pendaftaran magang menggantung - apa pun sebabnya, termasuk
 * pendaftaran sungguhan yang dibuat orang - aturan "satu pendaftaran
 * menggantung per mahasiswa per jenis" menolak SEMUA pendaftaran skrip ini, dan
 * 44 pemeriksaan slot merah karena sebab yang tidak ada hubungannya dengan
 * kuota. Kejadian 2 Agt 2026. Pelajarannya sama dengan r4/r5/r6: harness yang
 * meminjam data bersama akan merah karena ulah orang lain.
 */
$MHS_UJI = 'uji_slot_mhs_' . time() . '_' . mt_rand(1000, 9999) . '@example.test';
$db->query("INSERT INTO usr_users (email,password,name,username,role,status,profile_completed,created_at)
    VALUES ('" . $db->real_escape_string($MHS_UJI) . "', '" . password_hash(MHS_PASSWORD, PASSWORD_BCRYPT)
    . "', 'Mahasiswa Uji Slot', 'uji_slot_" . mt_rand(100000, 999999) . "', 'mahasiswa', 'active', 1, NOW())");
$GLOBALS['mhs_uji_id'] = (int) $db->insert_id;

pakai_sesi('mhs');
wajib(login($MHS_UJI, MHS_PASSWORD), 'Login mahasiswa uji (akun sendiri, bukan akun demo bersama)');
pakai_sesi('bid');
cek(login(BID_EMAIL, BID_PASSWORD), 'Login admin bidang');

// Tahun uji harus kosong sebelum dipakai - penyimpanan slot menulis ulang satu
// tahun penuh, dan skrip ini tidak boleh menghapus slot orang lain.
$sudah_ada = (int) $db->query("SELECT COUNT(*) n FROM kkn_magang_slot WHERE tahun = " . TAHUN_UJI)->fetch_assoc()['n'];
wajib($sudah_ada === 0, 'Tahun uji ' . TAHUN_UJI . ' masih kosong (aman ditulis ulang)');

echo "\n== Pengelolaan oleh superadmin ==\n";

pakai_sesi('adm');
$bidang = baris("SELECT * FROM kkn_magang_bidang WHERE bidang_kode = ?", [BIDANG_UJI]);
wajib($bidang !== NULL, 'Bidang uji punya setelan magang');

// Juni dan Agustus dibuka, JULI SENGAJA TIDAK - lubang di tengah inilah yang
// membedakan "periksa bulan mulai" dari "periksa semua bulan".
atur_slot(BIDANG_UJI, TAHUN_UJI, [6, 8], 2);
$terbuka = [];
foreach ($db->query("SELECT bulan FROM kkn_magang_slot WHERE bidang_kode = '" . $db->real_escape_string(BIDANG_UJI)
    . "' AND tahun = " . TAHUN_UJI) as $b) {
    $terbuka[] = (int) $b['bulan'];
}
sort($terbuka);
cek($terbuka === [6, 8], 'Slot tersimpan persis seperti yang dicentang (Juni & Agustus, Juli tertutup)');

echo "\n== Papan slot publik dibaca dari DB ==\n";

$nama_bidang = baris("SELECT nama FROM bidang WHERE kode = ?", [BIDANG_UJI])['nama'];
$papan = http('KemitraanPortal/magang')['body'];
cek(strpos($papan, $nama_bidang) !== FALSE, 'Bidang muncul di papan slot publik');

// Bidang yang berhenti menerima hilang dari papan - daftarnya struktur
// organisasi, jadi yang bisa dimatikan cuma penerimaan magangnya.
http('Admin_Kemitraan/ubah_status_bidang/' . rawurlencode(BIDANG_UJI), [
    'csrf_kpkp_token' => token('Admin_Kemitraan/slot/' . TAHUN_UJI),
    'tahun'           => TAHUN_UJI,
]);
$papan = http('KemitraanPortal/magang')['body'];
cek(strpos($papan, $nama_bidang) === FALSE, 'Bidang yang berhenti menerima hilang dari papan publik');

http('Admin_Kemitraan/ubah_status_bidang/' . rawurlencode(BIDANG_UJI), [
    'csrf_kpkp_token' => token('Admin_Kemitraan/slot/' . TAHUN_UJI),
    'tahun'           => TAHUN_UJI,
]);
$bidang = baris("SELECT * FROM kkn_magang_bidang WHERE bidang_kode = ?", [BIDANG_UJI]);
wajib((int) $bidang['aktif'] === 1, 'Bidang menerima lagi');

echo "\n== Penegakan saat mendaftar ==\n";

// INTI SKRIP INI. Juni terbuka, jadi pemeriksaan "bulan mulai saja" akan
// meloloskannya. Juli tertutup, jadi pemeriksaan yang benar menolaknya.
$body = daftar_magang(BIDANG_UJI, TAHUN_UJI . '-06-01', TAHUN_UJI . '-08-31');
cek(strpos($body, 'tidak membuka slot pada') !== FALSE, 'Periode Juni-Agustus ditolak karena Juli tertutup');
cek(jumlah_pendaftaran() === 0, 'Pendaftaran yang ditolak tidak menyisakan baris');

$body = daftar_magang(BIDANG_UJI, TAHUN_UJI . '-07-01', TAHUN_UJI . '-07-31');
cek(strpos($body, 'tidak membuka slot pada') !== FALSE, 'Periode Juli penuh ditolak');

$body = daftar_magang('bidang_karangan', TAHUN_UJI . '-06-01', TAHUN_UJI . '-06-30');
cek(strpos($body, 'tidak tersedia') !== FALSE, 'Kode bidang karangan ditolak walau select tidak merendernya');

$body = daftar_magang(BIDANG_UJI, TAHUN_UJI . '-08-31', TAHUN_UJI . '-06-01');
cek(strpos($body, 'Periode selesai tidak boleh mendahului') !== FALSE, 'Periode terbalik ditolak');
cek(jumlah_pendaftaran() === 0, 'Tiga penolakan, nol baris tersimpan');

// Bidang yang berhenti menerima: dimatikan sebentar, ditembakkan, dinyalakan lagi.
pakai_sesi('adm');
http('Admin_Kemitraan/ubah_status_bidang/' . rawurlencode(BIDANG_UJI), [
    'csrf_kpkp_token' => token('Admin_Kemitraan/slot/' . TAHUN_UJI), 'tahun' => TAHUN_UJI,
]);
$body = daftar_magang(BIDANG_UJI, TAHUN_UJI . '-06-01', TAHUN_UJI . '-06-30');
cek(strpos($body, 'tidak tersedia') !== FALSE, 'Bidang nonaktif ditolak walau slotnya masih terbuka');
pakai_sesi('adm');
http('Admin_Kemitraan/ubah_status_bidang/' . rawurlencode(BIDANG_UJI), [
    'csrf_kpkp_token' => token('Admin_Kemitraan/slot/' . TAHUN_UJI), 'tahun' => TAHUN_UJI,
]);

// Kunci SAH harus lolos lebih dulu. Tanpa ini "semua ditolak" ikut lulus -
// termasuk kalau penjagaannya rusak total dan menolak segalanya.
$body = daftar_magang(BIDANG_UJI, TAHUN_UJI . '-06-05', TAHUN_UJI . '-06-30');
cek(jumlah_pendaftaran() === 1, 'Periode yang seluruhnya terbuka DITERIMA');

$tersimpan = baris("SELECT divisi_atau_tema, bidang_kode FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE ?", [SENTINEL . '%']);
cek(($tersimpan['bidang_kode'] ?? '') === BIDANG_UJI, 'Kode bidang tersimpan di kolomnya sendiri');
cek(($tersimpan['divisi_atau_tema'] ?? '') === $nama_bidang, 'Nama bidang tersimpan kanonik dari tabel');

echo "\n== Kuota menutup slot sendiri ==\n";

// Kuota diturunkan ke 1 lewat formulir admin - satu tombol dengan matriksnya,
// jadi centang bulan harus ikut dikirim atau slotnya terhapus.
pakai_sesi('adm');
atur_slot(BIDANG_UJI, TAHUN_UJI, [6, 8], 1);
$bidang = baris("SELECT * FROM kkn_magang_bidang WHERE bidang_kode = ?", [BIDANG_UJI]);
wajib((int) $bidang['kuota'] === 1, 'Kuota tersimpan lewat formulir admin');

terima_semua();
// Satu pendaftaran Juni sudah ada dari uji sebelumnya, jadi Juni kini penuh.
$body = daftar_magang(BIDANG_UJI, TAHUN_UJI . '-06-10', TAHUN_UJI . '-06-20');
cek(strpos($body, 'penuh, 1 dari 1') !== FALSE, 'Bulan yang kuotanya habis menolak pendaftaran baru');
cek(jumlah_pendaftaran() === 1, 'Pendaftaran kedua di bulan penuh tidak tersimpan');

// Agustus masih kosong dan terbuka - membuktikan yang menolak tadi memang
// kuotanya, bukan penjagaan yang rusak dan menolak segalanya.
$body = daftar_magang(BIDANG_UJI, TAHUN_UJI . '-08-01', TAHUN_UJI . '-08-20');
cek(jumlah_pendaftaran() === 2, 'Bulan lain yang masih kosong tetap menerima');

// Angka terisi harus bisa ditelusuri ke ORANGNYA, bukan muncul begitu saja -
// hitungan yang tidak bisa ditelusuri akan dihitung ulang manual di sebelahnya.
pakai_sesi('adm');
$layar = http('Admin_Kemitraan/slot_bidang/' . rawurlencode(BIDANG_UJI) . '/' . TAHUN_UJI)['body'];
cek(strpos($layar, 'Paling ramai 1 dari 1') !== FALSE, 'Layar detail menampilkan hitungan terisi');
cek(strpos($layar, 'Mahasiswa') !== FALSE, 'Layar detail menyebut nama pengisinya, bukan cuma angka');

// Yang DITOLAK melepaskan tempatnya kembali - itu beda utama antara menghitung
// dari tabel pendaftaran dan menyimpan angka "terisi" yang harus disinkronkan.
$juni = baris("SELECT id FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE ? AND periode_mulai LIKE ? ORDER BY id LIMIT 1",
    [SENTINEL . '%', TAHUN_UJI . '-06%']);
wajib($juni !== NULL, 'Pendaftaran Juni ditemukan untuk ditolak');
http('Admin_Kemitraan/proses/' . (int) $juni['id'], [
    'csrf_kpkp_token' => token('Admin_Kemitraan'),
    'status'          => 'Ditolak',
    'catatan_admin'   => 'Uji pelepasan kuota',
]);
terima_semua();
$body = daftar_magang(BIDANG_UJI, TAHUN_UJI . '-06-10', TAHUN_UJI . '-06-20');
cek(jumlah_pendaftaran() === 3, 'Pendaftaran yang DITOLAK melepaskan kuotanya kembali');

echo "\n== Kuota diukur dari kehadiran BERSAMAAN, bukan bulan tersentuh ==\n";

// Kasus yang melahirkan seluruh bagian ini. Kuota 1, Juli dan Agustus dibuka.
// A pulang 15 Juli, B datang 16 Juli - mereka tidak pernah bertemu satu hari
// pun. Dengan hitungan "berapa pendaftaran menyentuh bulan Juli", B DITOLAK.
// Dengan hitungan kehadiran bersamaan, B diterima.
pakai_sesi('adm');
atur_slot(BIDANG_UJI, TAHUN_UJI, [7, 8, 9], 1);
$db->query("DELETE FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE '" . SENTINEL . "%'");

daftar_magang(BIDANG_UJI, TAHUN_UJI . '-07-01', TAHUN_UJI . '-07-15');
wajib(jumlah_pendaftaran() === 1, 'A (1-15 Juli) diterima');

terima_semua();
$body = daftar_magang(BIDANG_UJI, TAHUN_UJI . '-07-16', TAHUN_UJI . '-08-31');
cek(jumlah_pendaftaran() === 2, 'B (16 Juli-31 Agu) diterima - tidak pernah bertemu A');

// Pembuktian terbalik: yang BENAR-BENAR bertumpang tindih tetap ditolak. Tanpa
// ini, "semuanya diterima" ikut lulus - termasuk kalau kuotanya tidak dicek
// sama sekali.
terima_semua();
$body = daftar_magang(BIDANG_UJI, TAHUN_UJI . '-07-10', TAHUN_UJI . '-07-20');
cek(strpos($body, 'penuh, 1 dari 1') !== FALSE, 'C yang bertumpang tindih dengan A dan B DITOLAK');
cek(jumlah_pendaftaran() === 2, 'Tumpang tindih tidak menyisakan baris');

// Puncak bulan tidak boleh dipakai sebagai penjagaan: September hanya terisi
// pada tanggal-tanggal awal lewat B? Tidak - B berakhir 31 Agustus. Pemohon
// September penuh harus lolos meski Agustus di sebelahnya sedang ramai.
$body = daftar_magang(BIDANG_UJI, TAHUN_UJI . '-09-01', TAHUN_UJI . '-09-30');
cek(jumlah_pendaftaran() === 3, 'Bulan yang bersih diterima walau bulan sebelahnya penuh');

terima_semua();
$body = daftar_magang(BIDANG_UJI, TAHUN_UJI . '-07-01', TAHUN_UJI . '-12-31');
cek(strpos($body, 'Periode terlalu panjang') === FALSE, 'Periode setengah tahun masih dianggap wajar');
$body = daftar_magang(BIDANG_UJI, TAHUN_UJI . '-07-01', (TAHUN_UJI + 3) . '-07-01');
cek(strpos($body, 'Periode terlalu panjang') !== FALSE, 'Periode tiga tahun ditolak sebelum sempat dihitung harian');

// Dikembalikan ke keadaan yang diharapkan bagian berikutnya: satu baris saja.
$db->query("DELETE FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE '" . SENTINEL . "%'");
daftar_magang(BIDANG_UJI, TAHUN_UJI . '-08-01', TAHUN_UJI . '-08-20');
wajib(jumlah_pendaftaran() === 1, 'Satu pendaftaran disiapkan untuk uji penyuntingan');

echo "\n== Superadmin menyunting pendaftaran ==\n";

$sunting = baris("SELECT id FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE ? ORDER BY id DESC LIMIT 1", [SENTINEL . '%']);
pakai_sesi('adm');
$r = http('Admin_Kemitraan/simpan_ubah/' . (int) $sunting['id'], [
    'csrf_kpkp_token'  => token('Admin_Kemitraan/ubah/' . (int) $sunting['id']),
    'nim'              => 'H1A020777',
    'tempat_lahir'     => 'Kudus',
    'tanggal_lahir'    => '2002-02-02',
    'semester'         => '8',
    'jurusan'          => 'Arsitektur',
    'instansi_asal'    => SENTINEL . ' Universitas Diubah',
    'no_hp'            => '081200000000',
    'divisi_atau_tema' => BIDANG_UJI,
    'periode_mulai'    => TAHUN_UJI . '-08-01',
    'periode_selesai'  => TAHUN_UJI . '-08-31',
]);
$sesudah = baris("SELECT * FROM kkn_magang_pendaftaran WHERE id = ?", [(int) $sunting['id']]);
cek($sesudah['nim'] === 'H1A020777', 'Admin mengubah NIM pendaftaran');
cek($sesudah['jurusan'] === 'Arsitektur', 'Admin mengubah jurusan');
// Agustus sudah terisi satu dan kuotanya 1 - admin TETAP boleh menempatkannya
// di sana. Keputusan user: admin berwenang, papan menampilkan kelebihannya.
cek($sesudah['periode_mulai'] === TAHUN_UJI . '-08-01', 'Admin boleh melampaui kuota saat menyunting');

$r = http('Admin_Kemitraan/simpan_ubah/' . (int) $sunting['id'], [
    'csrf_kpkp_token'  => token('Admin_Kemitraan/ubah/' . (int) $sunting['id']),
    'nim'              => 'H1A020777', 'tempat_lahir' => 'Kudus', 'tanggal_lahir' => '2002-02-02',
    'semester'         => '8', 'jurusan' => 'Arsitektur',
    'instansi_asal'    => SENTINEL . ' Universitas Diubah', 'no_hp' => '081200000000',
    'divisi_atau_tema' => 'bidang_karangan_admin',
    'periode_mulai'    => TAHUN_UJI . '-08-01', 'periode_selesai' => TAHUN_UJI . '-08-31',
]);
$sesudah = baris("SELECT divisi_atau_tema FROM kkn_magang_pendaftaran WHERE id = ?", [(int) $sunting['id']]);
cek($sesudah['divisi_atau_tema'] === $nama_bidang, 'Admin tetap tidak bisa menyimpan bidang yang tidak ada');

// Semester 99 ditembakkan: aturan yang sama dengan formulir mahasiswa harus
// berlaku di layar admin - itu gunanya satu grup aturan di config, bukan dua.
http('Admin_Kemitraan/simpan_ubah/' . (int) $sunting['id'], [
    'csrf_kpkp_token'  => token('Admin_Kemitraan/ubah/' . (int) $sunting['id']),
    'nim'              => 'H1A020777', 'tempat_lahir' => 'Kudus', 'tanggal_lahir' => '2002-02-02',
    'semester'         => '99', 'jurusan' => 'Arsitektur',
    'instansi_asal'    => SENTINEL . ' Universitas Diubah', 'no_hp' => '081200000000',
    'divisi_atau_tema' => BIDANG_UJI,
    'periode_mulai'    => TAHUN_UJI . '-08-01', 'periode_selesai' => TAHUN_UJI . '-08-31',
]);
$sesudah = baris("SELECT semester FROM kkn_magang_pendaftaran WHERE id = ?", [(int) $sunting['id']]);
cek((int) $sesudah['semester'] === 8, 'Aturan validasi yang sama berlaku di layar admin');

echo "\n== Rentang tanggal di dalam bulan ==\n";

// Juni dibuka HANYA tanggal 1-15. Bentuk lama tidak punya cara menyatakan ini:
// Juni terbuka penuh atau tertutup penuh, dan sisanya disaring manual di meja
// peninjauan - persis pekerjaan yang seharusnya dihapus oleh slot.
$db->query("DELETE FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE '" . SENTINEL . "%'");
atur_slot(BIDANG_UJI, TAHUN_UJI, [6], 2, [6 => [TAHUN_UJI . '-06-01', TAHUN_UJI . '-06-15']]);

$slot_juni = baris("SELECT tgl_mulai, tgl_selesai FROM kkn_magang_slot WHERE bidang_kode = ? AND tahun = ? AND bulan = 6",
    [BIDANG_UJI, TAHUN_UJI]);
wajib($slot_juni !== NULL, 'Slot Juni tersimpan');
cek($slot_juni['tgl_mulai'] === TAHUN_UJI . '-06-01' && $slot_juni['tgl_selesai'] === TAHUN_UJI . '-06-15',
    'Rentang tanggal tersimpan apa adanya');

$body = daftar_magang(BIDANG_UJI, TAHUN_UJI . '-06-05', TAHUN_UJI . '-06-12');
cek(jumlah_pendaftaran() === 1, 'Periode di DALAM rentang diterima');

terima_semua();
$body = daftar_magang(BIDANG_UJI, TAHUN_UJI . '-06-10', TAHUN_UJI . '-06-25');
cek(strpos($body, 'hanya dibuka tanggal 1-15') !== FALSE, 'Periode yang melewati rentang ditolak dengan tanggalnya');
cek(jumlah_pendaftaran() === 1, 'Yang melewati rentang tidak tersimpan');

// Dijepit ke batas bulan, bukan ditolak: maksud admin sudah terbaca, dan bulan
// sebelah punya barisnya sendiri.
atur_slot(BIDANG_UJI, TAHUN_UJI, [6], 2, [6 => [TAHUN_UJI . '-05-20', TAHUN_UJI . '-07-10']]);
$slot_juni = baris("SELECT tgl_mulai, tgl_selesai FROM kkn_magang_slot WHERE bidang_kode = ? AND tahun = ? AND bulan = 6",
    [BIDANG_UJI, TAHUN_UJI]);
cek($slot_juni['tgl_mulai'] === TAHUN_UJI . '-06-01' && $slot_juni['tgl_selesai'] === TAHUN_UJI . '-06-30',
    'Tanggal di luar bulannya dijepit ke batas bulan');

echo "\n== Hapus pendaftaran ==\n";

// Daftar bidang TIDAK bisa ditambah, diganti nama, atau dihapus lewat modul ini
// - ia struktur organisasi dinas, dipakai bersama modul aduan. Yang tersisa
// untuk diuji: menghapus pendaftaran.
$hapus_id = baris("SELECT id FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE ? LIMIT 1", [SENTINEL . '%']);
wajib($hapus_id !== NULL, 'Ada pendaftaran untuk dihapus');
pakai_sesi('adm');
http('Admin_Kemitraan/hapus/' . (int) $hapus_id['id'], [
    'csrf_kpkp_token' => token('Admin_Kemitraan/ubah/' . (int) $hapus_id['id']),
]);
cek(baris("SELECT id FROM kkn_magang_pendaftaran WHERE id = ?", [(int) $hapus_id['id']]) === NULL,
    'Superadmin bisa menghapus pendaftaran');

echo "\n== Alur surat dua tahap ==\n";

// Divisi uji ditetapkan ke bidang yang SAMA dengan akun admin bidang yang ada,
// supaya tahap dua punya meja yang benar-benar bisa dibuka.
$bidang_adm = baris("SELECT bidang_kode FROM usr_users WHERE role = 'admin_bidang' AND bidang_kode IS NOT NULL LIMIT 1");
if ( ! $bidang_adm) {
    echo "  LEWAT  Tidak ada akun admin_bidang - alur tahap dua tidak bisa diuji\n";
} else {
    $db->query("DELETE FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE '" . SENTINEL . "%'");
    atur_slot(BIDANG_UJI, TAHUN_UJI, [7, 8], 3);

    // Tidak ada lagi pemetaan yang harus diisi: slotnya sudah menyebut bidangnya.
    cek($bidang_adm['bidang_kode'] === BIDANG_UJI,
        'Bidang uji sama dengan bidang akun peninjau - alur tahap dua bisa diuji utuh');

    daftar_magang(BIDANG_UJI, TAHUN_UJI . '-07-01', TAHUN_UJI . '-07-20');
    $surat = baris("SELECT id, status FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE ? ORDER BY id DESC LIMIT 1", [SENTINEL . '%']);
    wajib($surat !== NULL, 'Surat masuk (tahap 1)');
    cek(($surat['status'] ?? '') === 'Diajukan', 'Status awal menunggu sekretariat');

    // Bidang TIDAK boleh memutuskan surat yang belum diteruskan. Kalau boleh,
    // tahap satu berhenti berarti apa pun dan diagram alurnya jadi hiasan.
    pakai_sesi('bid');
    http('Kemitraan_Bidang/proses/' . (int) $surat['id'], [
        'csrf_kpkp_token' => token('Kemitraan_Bidang'), 'status' => 'Diterima', 'catatan_admin' => 'curi start',
    ]);
    $cek = baris("SELECT status FROM kkn_magang_pendaftaran WHERE id = ?", [(int) $surat['id']]);
    cek(($cek['status'] ?? '') === 'Diajukan', 'Bidang tidak bisa memutuskan sebelum sekretariat meneruskan');

    pakai_sesi('adm');
    http('Admin_Kemitraan/proses/' . (int) $surat['id'], [
        'csrf_kpkp_token' => token('Admin_Kemitraan'), 'status' => 'Ditinjau Bidang', 'catatan_admin' => 'Diteruskan',
    ]);
    $cek = baris("SELECT status FROM kkn_magang_pendaftaran WHERE id = ?", [(int) $surat['id']]);
    cek(($cek['status'] ?? '') === 'Ditinjau Bidang', 'Sekretariat meneruskan ke bidang (tahap 2)');

    pakai_sesi('bid');
    $layar = http('Kemitraan_Bidang')['body'];
    cek(strpos($layar, SENTINEL) !== FALSE, 'Surat muncul di meja bidang yang benar');

    http('Kemitraan_Bidang/proses/' . (int) $surat['id'], [
        'csrf_kpkp_token' => token('Kemitraan_Bidang'), 'status' => 'Diterima', 'catatan_admin' => 'Disetujui bidang',
    ]);
    $cek = baris("SELECT status, catatan_admin, catatan_bidang, reviewed_by, reviewed_by_bidang FROM kkn_magang_pendaftaran WHERE id = ?", [(int) $surat['id']]);
    cek(($cek['status'] ?? '') === 'Diterima', 'Bidang memutuskan diterima (tahap 3)');

    // Jejak DUA meja harus utuh. Kalau tahap dua menimpa reviewed_by, pertanyaan
    // "siapa yang meloloskan ini ke bidang" jadi tidak terjawab selamanya.
    cek( ! empty($cek['reviewed_by']) && ! empty($cek['reviewed_by_bidang'])
        && $cek['reviewed_by'] !== $cek['reviewed_by_bidang'], 'Jejak kedua peninjau tersimpan terpisah');
    cek($cek['catatan_admin'] === 'Diteruskan' && $cek['catatan_bidang'] === 'Disetujui bidang',
        'Catatan kedua meja tidak saling menimpa');

    // Mahasiswa melihat perjalanannya, bukan cuma satu kata status.
    pakai_sesi('mhs');
    $detail = http('KemitraanPortal/pendaftaran/' . (int) $surat['id'])['body'];
    foreach (['Surat Masuk', 'Ditinjau Admin Disperakim', 'Ditinjau Admin Bidang', 'Surat Balasan'] as $tahap) {
        cek(strpos($detail, $tahap) !== FALSE, "Garis waktu menampilkan tahap \"$tahap\"");
    }
    cek(strpos($detail, 'Disetujui bidang') !== FALSE, 'Catatan bidang sampai ke mahasiswa');
    cek(strpos($detail, 'Unduh Surat Balasan') === FALSE, 'Tombol unduh belum muncul selama suratnya belum ada');

    // Berkasnya belum ada, jadi endpoint unduhnya harus 404 - bukan halaman
    // kosong yang menyaru berhasil.
    $r = http('KemitraanPortal/unduh_balasan/' . (int) $surat['id']);
    cek($r['code'] === 404, "Unduh surat yang belum terbit dijawab 404 (kode {$r['code']})");
}

echo "\n== Guard layar pengelolaan ==\n";

// Kunci SAH harus lolos lebih dulu. Tanpa ini "mahasiswa tidak melihat layarnya"
// ikut hijau walau halamannya 500 untuk semua orang - menguji ketiadaan tanpa
// pernah membuktikan ada.
pakai_sesi('adm');
$r = http('Admin_Kemitraan/slot/' . TAHUN_UJI);
cek(strpos($r['body'], 'Bulan Terbuka') !== FALSE, 'Superadmin melihat daftar slot');
cek(strpos($r['body'], $nama_bidang) !== FALSE, 'Bidang uji tampil di daftar admin');

// Satu wadah: pendaftaran dan slot adalah dua tab pada halaman yang sama,
// bukan dua entri sidebar. Kalau kepalanya hilang dari salah satunya, admin
// kehilangan jalan menyeberang.
foreach (['Admin_Kemitraan', 'Admin_Kemitraan/slot'] as $jalur) {
    $b = http($jalur)['body'];
    cek(strpos($b, 'Slot &amp; Bidang') !== FALSE && strpos($b, 'KKN &amp; Magang') !== FALSE,
        "Tab pengelolaan tampil di /$jalur");
}

pakai_sesi('mhs');
$r = http('Admin_Kemitraan/slot');
cek(strpos($r['body'], 'Bulan Terbuka') === FALSE, 'Mahasiswa tidak melihat layar pengelolaan slot');

$sebelum = (int) $db->query("SELECT COUNT(*) n FROM kkn_magang_slot WHERE tahun = " . TAHUN_UJI)->fetch_assoc()['n'];
http('Admin_Kemitraan/simpan_slot_bidang/' . rawurlencode(BIDANG_UJI), http_build_query([
    'csrf_kpkp_token' => token('KemitraanPortal/daftar/magang'),
    'tahun'           => TAHUN_UJI,
    'kuota'           => 99,
    'bulan'           => array_fill_keys(range(1, 12), ['buka' => 1]),
]));
$sesudah = (int) $db->query("SELECT COUNT(*) n FROM kkn_magang_slot WHERE tahun = " . TAHUN_UJI)->fetch_assoc()['n'];
// Dibandingkan dengan keadaan SEBELUMNYA, bukan angka tetap: bagian-bagian di
// atas mengubah berapa bulan yang terbuka, dan uji yang mematok angka akan
// merah setiap kali salah satunya disesuaikan - merah yang tidak menuduh apa pun.
cek($sebelum === $sesudah && $sesudah > 0, 'Mahasiswa tidak bisa menulis slot lewat POST langsung');

bersihkan();
echo "\nRINGKASAN: {$GLOBALS['t']} pemeriksaan, {$GLOBALS['g']} gagal\n";
exit($GLOBALS['g'] > 0 ? 1 : 0);
