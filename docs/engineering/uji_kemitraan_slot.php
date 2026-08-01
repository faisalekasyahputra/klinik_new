<?php
/**
 * Uji slot magang — data, pengelolaan, dan penegakannya (migrasi 20260701000026).
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
 *   1. Papan slot benar-benar dibaca dari DB — divisi yang dinonaktifkan
 *      superadmin hilang dari halaman publik.
 *   2. Penegakan SETIAP BULAN, bukan cuma bulan mulai. Ini intinya: divisi
 *      dengan Juni dan Agustus terbuka tapi Juli tertutup HARUS menolak
 *      pendaftaran Juni-Agustus. Kalau hanya bulan mulai yang diperiksa,
 *      pemeriksaan itu lulus dan papan slotnya berbohong.
 *   3. Select di formulir bukan penjagaan — divisi yang tidak ada dan divisi
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

define('DIVISI', 'UJI SLOT ' . date('His'));
define('SENTINEL', 'UJI-SLOT-' . date('His'));
// Tahun uji sengaja BUKAN tahun berjalan: penyimpanan slot menulis ulang seluruh
// tahun, jadi memakai tahun berjalan berarti menghapus slot sungguhan kalau
// formulirnya tidak mengirim ulang semuanya. Tahun depan masih kosong.
define('TAHUN_UJI', (int) date('Y') + 1);

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

// Dua sesi terpisah: superadmin yang mengelola, mahasiswa yang mendaftar. Satu
// jar untuk keduanya membuat uji guard di bawah tidak menguji apa pun.
$jars = ['adm' => tempnam(sys_get_temp_dir(), 'ujisl_a'), 'mhs' => tempnam(sys_get_temp_dir(), 'ujisl_m')];
$jar_aktif = 'adm';

function pakai_sesi($nama) { $GLOBALS['jar_aktif'] = $nama; }

/**
 * @param bool $ajax Menandai diri XMLHttpRequest. Default MATI, dan itu
 *   penting: `MY_Controller::render()` mengirim view telanjang TANPA layout
 *   untuk permintaan AJAX, sehingga `components/notification_center` — satu-
 *   satunya tempat flashdata dirender — tidak ikut. Versi pertama skrip ini
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
    $db->query("DELETE FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE '" . SENTINEL . "%'");
    // Slot divisi uji ikut terhapus lewat ON DELETE CASCADE.
    $db->query("DELETE FROM kkn_magang_divisi WHERE nama = '" . $db->real_escape_string(DIVISI) . "'");
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
 * @param array $rentang [bulan => ['Y-m-d mulai', 'Y-m-d selesai']] — tanpa ini
 *                       bulan dibuka penuh
 */
function atur_slot($divisi_id, $tahun, array $bulan, $kuota, array $rentang = []) {
    pakai_sesi('adm');
    $data = [
        'csrf_kpkp_token' => token('Admin_Kemitraan/slot_divisi/' . (int) $divisi_id . '/' . (int) $tahun),
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
    return http('Admin_Kemitraan/simpan_slot_divisi/' . (int) $divisi_id, http_build_query($data));
}

/** Kirim pendaftaran magang sebagai mahasiswa; kembalikan body balasannya. */
function daftar_magang($divisi, $mulai, $selesai) {
    pakai_sesi('mhs');
    return http('KemitraanPortal/simpan', [
        'csrf_kpkp_token'  => token('KemitraanPortal/daftar/magang'),
        'jenis'            => 'magang',
        'nim'              => '2101' . date('His'),
        'tempat_lahir'     => 'Semarang',
        'tanggal_lahir'    => '2003-05-17',
        'semester'         => '6',
        'jurusan'          => 'Teknik Sipil',
        'instansi_asal'    => SENTINEL . ' Universitas Uji',
        'no_hp'            => '081234567890',
        'divisi_atau_tema' => $divisi,
        'periode_mulai'    => $mulai,
        'periode_selesai'  => $selesai,
    ])['body'];
}

function jumlah_pendaftaran() {
    global $db;
    return (int) $db->query("SELECT COUNT(*) n FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE '" . SENTINEL . "%'")
        ->fetch_assoc()['n'];
}

echo "\n== Uji slot magang — " . BASE_URL . " ==\n\n";
echo "== Prasyarat ==\n";

// Kode HTTP saja TIDAK cukup: login gagal juga membalas 200. Yang menentukan
// isi JSON-nya.
pakai_sesi('adm');
wajib(login(ADM_EMAIL, ADM_PASSWORD), 'Login superadmin');
pakai_sesi('mhs');
wajib(login(MHS_EMAIL, MHS_PASSWORD), 'Login mahasiswa');

// Tahun uji harus kosong sebelum dipakai — penyimpanan slot menulis ulang satu
// tahun penuh, dan skrip ini tidak boleh menghapus slot orang lain.
$sudah_ada = (int) $db->query("SELECT COUNT(*) n FROM kkn_magang_slot WHERE tahun = " . TAHUN_UJI)->fetch_assoc()['n'];
wajib($sudah_ada === 0, 'Tahun uji ' . TAHUN_UJI . ' masih kosong (aman ditulis ulang)');

echo "\n== Pengelolaan oleh superadmin ==\n";

pakai_sesi('adm');
$r = http('Admin_Kemitraan/tambah_divisi', [
    'csrf_kpkp_token' => token('Admin_Kemitraan/slot'),
    'nama'            => DIVISI,
    'tahun'           => TAHUN_UJI,
]);
$divisi = baris("SELECT * FROM kkn_magang_divisi WHERE nama = ?", [DIVISI]);
wajib($divisi !== NULL, 'Superadmin menambah divisi');
cek((int) $divisi['aktif'] === 1, 'Divisi baru langsung aktif');
cek((int) $db->query("SELECT COUNT(*) n FROM kkn_magang_slot WHERE divisi_id = " . (int) $divisi['id'])->fetch_assoc()['n'] === 0,
    'Divisi baru belum membuka slot apa pun');

// Juni dan Agustus dibuka, JULI SENGAJA TIDAK — lubang di tengah inilah yang
// membedakan "periksa bulan mulai" dari "periksa semua bulan".
// http_build_query, bukan array mentah: CURLOPT_POSTFIELDS berbentuk array
// dikirim sebagai multipart dan TIDAK bisa membawa `slot[id][]` yang bersarang.
// Sebagai string ia terkirim urlencoded, dan PHP menyusunnya kembali jadi array.
atur_slot((int) $divisi['id'], TAHUN_UJI, [6, 8], 2);
$terbuka = [];
foreach ($db->query("SELECT bulan FROM kkn_magang_slot WHERE divisi_id = " . (int) $divisi['id'] . " AND tahun = " . TAHUN_UJI) as $b) {
    $terbuka[] = (int) $b['bulan'];
}
sort($terbuka);
cek($terbuka === [6, 8], 'Slot tersimpan persis seperti yang dicentang (Juni & Agustus, Juli tertutup)');

echo "\n== Papan slot publik dibaca dari DB ==\n";

$papan = http('KemitraanPortal/magang')['body'];
cek(strpos($papan, DIVISI) !== FALSE, 'Divisi baru muncul di papan slot publik');

$r = http('Admin_Kemitraan/ubah_status_divisi/' . (int) $divisi['id'], [
    'csrf_kpkp_token' => token('Admin_Kemitraan/slot/' . TAHUN_UJI),
    'tahun'           => TAHUN_UJI,
]);
$papan = http('KemitraanPortal/magang')['body'];
cek(strpos($papan, DIVISI) === FALSE, 'Divisi nonaktif hilang dari papan publik');

// Dinyalakan lagi untuk uji pendaftaran di bawah.
http('Admin_Kemitraan/ubah_status_divisi/' . (int) $divisi['id'], [
    'csrf_kpkp_token' => token('Admin_Kemitraan/slot/' . TAHUN_UJI),
    'tahun'           => TAHUN_UJI,
]);
$divisi = baris("SELECT * FROM kkn_magang_divisi WHERE nama = ?", [DIVISI]);
wajib((int) $divisi['aktif'] === 1, 'Divisi dinyalakan kembali');

echo "\n== Penegakan saat mendaftar ==\n";

// INTI SKRIP INI. Juni terbuka, jadi pemeriksaan "bulan mulai saja" akan
// meloloskannya. Juli tertutup, jadi pemeriksaan yang benar menolaknya.
$body = daftar_magang(DIVISI, TAHUN_UJI . '-06-01', TAHUN_UJI . '-08-31');
cek(strpos($body, 'tidak membuka slot pada') !== FALSE, 'Periode Juni-Agustus ditolak karena Juli tertutup');
cek(jumlah_pendaftaran() === 0, 'Pendaftaran yang ditolak tidak menyisakan baris');

$body = daftar_magang(DIVISI, TAHUN_UJI . '-07-01', TAHUN_UJI . '-07-31');
cek(strpos($body, 'tidak membuka slot pada') !== FALSE, 'Periode Juli penuh ditolak');

$body = daftar_magang('Divisi Yang Tidak Pernah Ada', TAHUN_UJI . '-06-01', TAHUN_UJI . '-06-30');
cek(strpos($body, 'tidak tersedia') !== FALSE, 'Divisi karangan ditolak walau select tidak merendernya');

$body = daftar_magang(DIVISI, TAHUN_UJI . '-08-31', TAHUN_UJI . '-06-01');
cek(strpos($body, 'Periode selesai tidak boleh mendahului') !== FALSE, 'Periode terbalik ditolak');
cek(jumlah_pendaftaran() === 0, 'Tiga penolakan, nol baris tersimpan');

// Divisi nonaktif: dimatikan sebentar, ditembakkan, lalu dinyalakan lagi.
pakai_sesi('adm');
http('Admin_Kemitraan/ubah_status_divisi/' . (int) $divisi['id'], [
    'csrf_kpkp_token' => token('Admin_Kemitraan/slot/' . TAHUN_UJI), 'tahun' => TAHUN_UJI,
]);
$body = daftar_magang(DIVISI, TAHUN_UJI . '-06-01', TAHUN_UJI . '-06-30');
cek(strpos($body, 'tidak tersedia') !== FALSE, 'Divisi nonaktif ditolak walau slotnya masih terbuka');
pakai_sesi('adm');
http('Admin_Kemitraan/ubah_status_divisi/' . (int) $divisi['id'], [
    'csrf_kpkp_token' => token('Admin_Kemitraan/slot/' . TAHUN_UJI), 'tahun' => TAHUN_UJI,
]);

// Kunci SAH harus lolos lebih dulu. Tanpa ini "semua ditolak" ikut lulus —
// termasuk kalau penjagaannya rusak total dan menolak segalanya.
$body = daftar_magang(DIVISI, TAHUN_UJI . '-06-05', TAHUN_UJI . '-06-30');
cek(jumlah_pendaftaran() === 1, 'Periode yang seluruhnya terbuka DITERIMA');

$tersimpan = baris("SELECT divisi_atau_tema FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE ?", [SENTINEL . '%']);
cek(($tersimpan['divisi_atau_tema'] ?? '') === DIVISI, 'Nama divisi tersimpan kanonik dari tabel');

echo "\n== Kuota menutup slot sendiri ==\n";

// Kuota diturunkan ke 1 lewat formulir admin — satu tombol dengan matriksnya,
// jadi centang bulan harus ikut dikirim atau slotnya terhapus.
pakai_sesi('adm');
atur_slot((int) $divisi['id'], TAHUN_UJI, [6, 8], 1);
$divisi = baris("SELECT * FROM kkn_magang_divisi WHERE nama = ?", [DIVISI]);
wajib((int) $divisi['kuota'] === 1, 'Kuota tersimpan lewat formulir admin');

// Satu pendaftaran Juni sudah ada dari uji sebelumnya, jadi Juni kini penuh.
$body = daftar_magang(DIVISI, TAHUN_UJI . '-06-10', TAHUN_UJI . '-06-20');
cek(strpos($body, 'penuh, 1 dari 1') !== FALSE, 'Bulan yang kuotanya habis menolak pendaftaran baru');
cek(jumlah_pendaftaran() === 1, 'Pendaftaran kedua di bulan penuh tidak tersimpan');

// Agustus masih kosong dan terbuka — membuktikan yang menolak tadi memang
// kuotanya, bukan penjagaan yang rusak dan menolak segalanya.
$body = daftar_magang(DIVISI, TAHUN_UJI . '-08-01', TAHUN_UJI . '-08-20');
cek(jumlah_pendaftaran() === 2, 'Bulan lain yang masih kosong tetap menerima');

// Angka terisi harus bisa ditelusuri ke ORANGNYA, bukan muncul begitu saja —
// hitungan yang tidak bisa ditelusuri akan dihitung ulang manual di sebelahnya.
pakai_sesi('adm');
$layar = http('Admin_Kemitraan/slot_divisi/' . (int) $divisi['id'] . '/' . TAHUN_UJI)['body'];
cek(strpos($layar, 'Paling ramai 1 dari 1') !== FALSE, 'Layar detail menampilkan hitungan terisi');
cek(strpos($layar, 'Mahasiswa') !== FALSE, 'Layar detail menyebut nama pengisinya, bukan cuma angka');

// Yang DITOLAK melepaskan tempatnya kembali — itu beda utama antara menghitung
// dari tabel pendaftaran dan menyimpan angka "terisi" yang harus disinkronkan.
$juni = baris("SELECT id FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE ? AND periode_mulai LIKE ? ORDER BY id LIMIT 1",
    [SENTINEL . '%', TAHUN_UJI . '-06%']);
wajib($juni !== NULL, 'Pendaftaran Juni ditemukan untuk ditolak');
http('Admin_Kemitraan/proses/' . (int) $juni['id'], [
    'csrf_kpkp_token' => token('Admin_Kemitraan'),
    'status'          => 'Ditolak',
    'catatan_admin'   => 'Uji pelepasan kuota',
]);
$body = daftar_magang(DIVISI, TAHUN_UJI . '-06-10', TAHUN_UJI . '-06-20');
cek(jumlah_pendaftaran() === 3, 'Pendaftaran yang DITOLAK melepaskan kuotanya kembali');

echo "\n== Kuota diukur dari kehadiran BERSAMAAN, bukan bulan tersentuh ==\n";

// Kasus yang melahirkan seluruh bagian ini. Kuota 1, Juli dan Agustus dibuka.
// A pulang 15 Juli, B datang 16 Juli — mereka tidak pernah bertemu satu hari
// pun. Dengan hitungan "berapa pendaftaran menyentuh bulan Juli", B DITOLAK.
// Dengan hitungan kehadiran bersamaan, B diterima.
pakai_sesi('adm');
atur_slot((int) $divisi['id'], TAHUN_UJI, [7, 8, 9], 1);
$db->query("DELETE FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE '" . SENTINEL . "%'");

daftar_magang(DIVISI, TAHUN_UJI . '-07-01', TAHUN_UJI . '-07-15');
wajib(jumlah_pendaftaran() === 1, 'A (1-15 Juli) diterima');

$body = daftar_magang(DIVISI, TAHUN_UJI . '-07-16', TAHUN_UJI . '-08-31');
cek(jumlah_pendaftaran() === 2, 'B (16 Juli-31 Agu) diterima — tidak pernah bertemu A');

// Pembuktian terbalik: yang BENAR-BENAR bertumpang tindih tetap ditolak. Tanpa
// ini, "semuanya diterima" ikut lulus — termasuk kalau kuotanya tidak dicek
// sama sekali.
$body = daftar_magang(DIVISI, TAHUN_UJI . '-07-10', TAHUN_UJI . '-07-20');
cek(strpos($body, 'penuh, 1 dari 1') !== FALSE, 'C yang bertumpang tindih dengan A dan B DITOLAK');
cek(jumlah_pendaftaran() === 2, 'Tumpang tindih tidak menyisakan baris');

// Puncak bulan tidak boleh dipakai sebagai penjagaan: September hanya terisi
// pada tanggal-tanggal awal lewat B? Tidak — B berakhir 31 Agustus. Pemohon
// September penuh harus lolos meski Agustus di sebelahnya sedang ramai.
$body = daftar_magang(DIVISI, TAHUN_UJI . '-09-01', TAHUN_UJI . '-09-30');
cek(jumlah_pendaftaran() === 3, 'Bulan yang bersih diterima walau bulan sebelahnya penuh');

$body = daftar_magang(DIVISI, TAHUN_UJI . '-07-01', TAHUN_UJI . '-12-31');
cek(strpos($body, 'Periode terlalu panjang') === FALSE, 'Periode setengah tahun masih dianggap wajar');
$body = daftar_magang(DIVISI, TAHUN_UJI . '-07-01', (TAHUN_UJI + 3) . '-07-01');
cek(strpos($body, 'Periode terlalu panjang') !== FALSE, 'Periode tiga tahun ditolak sebelum sempat dihitung harian');

// Dikembalikan ke keadaan yang diharapkan bagian berikutnya: satu baris saja.
$db->query("DELETE FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE '" . SENTINEL . "%'");
daftar_magang(DIVISI, TAHUN_UJI . '-08-01', TAHUN_UJI . '-08-20');
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
    'divisi_atau_tema' => DIVISI,
    'periode_mulai'    => TAHUN_UJI . '-08-01',
    'periode_selesai'  => TAHUN_UJI . '-08-31',
]);
$sesudah = baris("SELECT * FROM kkn_magang_pendaftaran WHERE id = ?", [(int) $sunting['id']]);
cek($sesudah['nim'] === 'H1A020777', 'Admin mengubah NIM pendaftaran');
cek($sesudah['jurusan'] === 'Arsitektur', 'Admin mengubah jurusan');
// Agustus sudah terisi satu dan kuotanya 1 — admin TETAP boleh menempatkannya
// di sana. Keputusan user: admin berwenang, papan menampilkan kelebihannya.
cek($sesudah['periode_mulai'] === TAHUN_UJI . '-08-01', 'Admin boleh melampaui kuota saat menyunting');

$r = http('Admin_Kemitraan/simpan_ubah/' . (int) $sunting['id'], [
    'csrf_kpkp_token'  => token('Admin_Kemitraan/ubah/' . (int) $sunting['id']),
    'nim'              => 'H1A020777', 'tempat_lahir' => 'Kudus', 'tanggal_lahir' => '2002-02-02',
    'semester'         => '8', 'jurusan' => 'Arsitektur',
    'instansi_asal'    => SENTINEL . ' Universitas Diubah', 'no_hp' => '081200000000',
    'divisi_atau_tema' => 'Divisi Karangan Admin',
    'periode_mulai'    => TAHUN_UJI . '-08-01', 'periode_selesai' => TAHUN_UJI . '-08-31',
]);
$sesudah = baris("SELECT divisi_atau_tema FROM kkn_magang_pendaftaran WHERE id = ?", [(int) $sunting['id']]);
cek($sesudah['divisi_atau_tema'] === DIVISI, 'Admin tetap tidak bisa menyimpan divisi yang tidak ada');

// Semester 99 ditembakkan: aturan yang sama dengan formulir mahasiswa harus
// berlaku di layar admin — itu gunanya satu grup aturan di config, bukan dua.
http('Admin_Kemitraan/simpan_ubah/' . (int) $sunting['id'], [
    'csrf_kpkp_token'  => token('Admin_Kemitraan/ubah/' . (int) $sunting['id']),
    'nim'              => 'H1A020777', 'tempat_lahir' => 'Kudus', 'tanggal_lahir' => '2002-02-02',
    'semester'         => '99', 'jurusan' => 'Arsitektur',
    'instansi_asal'    => SENTINEL . ' Universitas Diubah', 'no_hp' => '081200000000',
    'divisi_atau_tema' => DIVISI,
    'periode_mulai'    => TAHUN_UJI . '-08-01', 'periode_selesai' => TAHUN_UJI . '-08-31',
]);
$sesudah = baris("SELECT semester FROM kkn_magang_pendaftaran WHERE id = ?", [(int) $sunting['id']]);
cek((int) $sesudah['semester'] === 8, 'Aturan validasi yang sama berlaku di layar admin');

echo "\n== Rentang tanggal di dalam bulan ==\n";

// Juni dibuka HANYA tanggal 1-15. Bentuk lama tidak punya cara menyatakan ini:
// Juni terbuka penuh atau tertutup penuh, dan sisanya disaring manual di meja
// peninjauan — persis pekerjaan yang seharusnya dihapus oleh slot.
$db->query("DELETE FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE '" . SENTINEL . "%'");
atur_slot((int) $divisi['id'], TAHUN_UJI, [6], 2, [6 => [TAHUN_UJI . '-06-01', TAHUN_UJI . '-06-15']]);

$slot_juni = baris("SELECT tgl_mulai, tgl_selesai FROM kkn_magang_slot WHERE divisi_id = ? AND tahun = ? AND bulan = 6",
    [(int) $divisi['id'], TAHUN_UJI]);
wajib($slot_juni !== NULL, 'Slot Juni tersimpan');
cek($slot_juni['tgl_mulai'] === TAHUN_UJI . '-06-01' && $slot_juni['tgl_selesai'] === TAHUN_UJI . '-06-15',
    'Rentang tanggal tersimpan apa adanya');

$body = daftar_magang(DIVISI, TAHUN_UJI . '-06-05', TAHUN_UJI . '-06-12');
cek(jumlah_pendaftaran() === 1, 'Periode di DALAM rentang diterima');

$body = daftar_magang(DIVISI, TAHUN_UJI . '-06-10', TAHUN_UJI . '-06-25');
cek(strpos($body, 'hanya dibuka tanggal 1–15') !== FALSE, 'Periode yang melewati rentang ditolak dengan tanggalnya');
cek(jumlah_pendaftaran() === 1, 'Yang melewati rentang tidak tersimpan');

// Dijepit ke batas bulan, bukan ditolak: maksud admin sudah terbaca, dan bulan
// sebelah punya barisnya sendiri.
atur_slot((int) $divisi['id'], TAHUN_UJI, [6], 2, [6 => [TAHUN_UJI . '-05-20', TAHUN_UJI . '-07-10']]);
$slot_juni = baris("SELECT tgl_mulai, tgl_selesai FROM kkn_magang_slot WHERE divisi_id = ? AND tahun = ? AND bulan = 6",
    [(int) $divisi['id'], TAHUN_UJI]);
cek($slot_juni['tgl_mulai'] === TAHUN_UJI . '-06-01' && $slot_juni['tgl_selesai'] === TAHUN_UJI . '-06-30',
    'Tanggal di luar bulannya dijepit ke batas bulan');

echo "\n== CRUD divisi ==\n";

pakai_sesi('adm');
$nama_baru = DIVISI . ' EDIT';
http('Admin_Kemitraan/ganti_nama_divisi/' . (int) $divisi['id'], [
    'csrf_kpkp_token' => token('Admin_Kemitraan/slot_divisi/' . (int) $divisi['id'] . '/' . TAHUN_UJI),
    'nama'            => $nama_baru,
    'tahun'           => TAHUN_UJI,
]);
$sesudah = baris("SELECT nama FROM kkn_magang_divisi WHERE id = ?", [(int) $divisi['id']]);
cek(($sesudah['nama'] ?? '') === $nama_baru, 'Nama divisi bisa diganti');

// INTI bagian ini: pendaftaran menyimpan NAMA, bukan id. Kalau ia tidak ikut
// berubah, baris itu putus dari divisinya — hitungan kehadiran berhenti
// mengenalinya dan papan slot berhenti menampilkan beban yang sebenarnya.
$ikut = baris("SELECT divisi_atau_tema FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE ?", [SENTINEL . '%']);
cek(($ikut['divisi_atau_tema'] ?? '') === $nama_baru, 'Pendaftaran ikut berpindah nama, tidak jadi yatim');

http('Admin_Kemitraan/hapus_divisi/' . (int) $divisi['id'], [
    'csrf_kpkp_token' => token('Admin_Kemitraan/slot_divisi/' . (int) $divisi['id'] . '/' . TAHUN_UJI),
    'tahun'           => TAHUN_UJI,
]);
cek(baris("SELECT id FROM kkn_magang_divisi WHERE id = ?", [(int) $divisi['id']]) !== NULL,
    'Divisi yang sudah dipakai pendaftaran TIDAK bisa dihapus');

echo "\n== Hapus pendaftaran ==\n";

$hapus_id = baris("SELECT id FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE ? LIMIT 1", [SENTINEL . '%']);
http('Admin_Kemitraan/hapus/' . (int) $hapus_id['id'], [
    'csrf_kpkp_token' => token('Admin_Kemitraan/ubah/' . (int) $hapus_id['id']),
]);
cek(jumlah_pendaftaran() === 0, 'Superadmin bisa menghapus pendaftaran');

// Divisi kini bersih dari pendaftaran, jadi penghapusannya boleh — pembuktian
// bahwa penolakan tadi memang karena dipakai, bukan karena hapus selalu gagal.
http('Admin_Kemitraan/hapus_divisi/' . (int) $divisi['id'], [
    'csrf_kpkp_token' => token('Admin_Kemitraan/slot_divisi/' . (int) $divisi['id'] . '/' . TAHUN_UJI),
    'tahun'           => TAHUN_UJI,
]);
cek(baris("SELECT id FROM kkn_magang_divisi WHERE id = ?", [(int) $divisi['id']]) === NULL,
    'Divisi yang belum dipakai bisa dihapus');
cek((int) $db->query("SELECT COUNT(*) n FROM kkn_magang_slot WHERE divisi_id = " . (int) $divisi['id'])->fetch_assoc()['n'] === 0,
    'Slotnya ikut terhapus lewat CASCADE');

// Dibuat ulang supaya bagian guard di bawah punya divisi untuk diuji.
http('Admin_Kemitraan/tambah_divisi', [
    'csrf_kpkp_token' => token('Admin_Kemitraan/slot'),
    'nama'            => DIVISI,
    'tahun'           => TAHUN_UJI,
]);
$divisi = baris("SELECT * FROM kkn_magang_divisi WHERE nama = ?", [DIVISI]);
wajib($divisi !== NULL, 'Divisi dibuat ulang untuk uji guard');
atur_slot((int) $divisi['id'], TAHUN_UJI, [7, 8], 1);

echo "\n== Guard layar pengelolaan ==\n";

// Kunci SAH harus lolos lebih dulu. Tanpa ini "mahasiswa tidak melihat layarnya"
// ikut hijau walau halamannya 500 untuk semua orang — menguji ketiadaan tanpa
// pernah membuktikan ada.
pakai_sesi('adm');
$r = http('Admin_Kemitraan/slot/' . TAHUN_UJI);
cek(strpos($r['body'], 'Bulan Terbuka') !== FALSE, 'Superadmin melihat daftar slot');
cek(strpos($r['body'], DIVISI) !== FALSE, 'Divisi uji tampil di daftar admin');

// Satu wadah: pendaftaran dan slot adalah dua tab pada halaman yang sama,
// bukan dua entri sidebar. Kalau kepalanya hilang dari salah satunya, admin
// kehilangan jalan menyeberang.
foreach (['Admin_Kemitraan', 'Admin_Kemitraan/slot'] as $jalur) {
    $b = http($jalur)['body'];
    cek(strpos($b, 'Slot &amp; Divisi') !== FALSE && strpos($b, 'KKN &amp; Magang') !== FALSE,
        "Tab pengelolaan tampil di /$jalur");
}

pakai_sesi('mhs');
$r = http('Admin_Kemitraan/slot');
cek(strpos($r['body'], 'Bulan Terbuka') === FALSE, 'Mahasiswa tidak melihat layar pengelolaan slot');

$sebelum = (int) $db->query("SELECT COUNT(*) n FROM kkn_magang_slot WHERE tahun = " . TAHUN_UJI)->fetch_assoc()['n'];
http('Admin_Kemitraan/simpan_slot_divisi/' . (int) $divisi['id'], http_build_query([
    'csrf_kpkp_token' => token('KemitraanPortal/daftar/magang'),
    'tahun'           => TAHUN_UJI,
    'kuota'           => 99,
    'bulan'           => array_fill_keys(range(1, 12), ['buka' => 1]),
]));
$sesudah = (int) $db->query("SELECT COUNT(*) n FROM kkn_magang_slot WHERE tahun = " . TAHUN_UJI)->fetch_assoc()['n'];
// Dibandingkan dengan keadaan SEBELUMNYA, bukan angka tetap: bagian-bagian di
// atas mengubah berapa bulan yang terbuka, dan uji yang mematok angka akan
// merah setiap kali salah satunya disesuaikan — merah yang tidak menuduh apa pun.
cek($sebelum === $sesudah && $sesudah > 0, 'Mahasiswa tidak bisa menulis slot lewat POST langsung');

bersihkan();
echo "\nRINGKASAN: {$GLOBALS['t']} pemeriksaan, {$GLOBALS['g']} gagal\n";
exit($GLOBALS['g'] > 0 ? 1 : 0);
