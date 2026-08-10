<?php
/**
 * Uji BATAS WILAYAH ANTREAN KAB/KOTA — butir B2 revisi dinas.
 *
 *   php docs/engineering/uji_antrean_kabkota_scope.php
 *
 * KENAPA BERKAS INI ADA. Dinas melaporkan "dashboard kabupaten/kota masih
 * memunculkan data warga umum". Layar itu memang ADA dan memang menampilkan
 * data warga — namanya "Antrean Wilayah Saya", dan admin kab/kota memerlukannya
 * untuk menilai pengajuan. Jadi pertanyaannya bukan "ada atau tidak", melainkan
 * APAKAH BATAS WILAYAHNYA MENGIKAT.
 *
 * Bacaan kode bilang mengikat. Bacaan kode SELALU bilang begitu — itu sebabnya
 * berkas ini ada. Yang diuji: admin kab/kota A tidak boleh melihat, membuka,
 * mengunduh, atau mengubah satu pun pengajuan warga di kabupaten B.
 *
 * EMPAT PERMUKAAN, karena menutup daftar saja tidak menutup apa pun:
 *   1. daftar   — baris B tidak muncul di layar A
 *   2. detail   — /detail/<id B> ditolak walau id-nya ditebak benar
 *   3. berkas   — /evidence/<id B> ditolak (bukti berisi foto rumah & KTP)
 *   4. tulis    — POST keputusan atas antrean B tidak mengubah apa pun
 *
 * Nomor 4 yang paling gampang terlupa: layar bisa rapi sementara endpoint
 * tulisnya menerima id mana pun. Karena itu statusnya dibaca ULANG dari DB
 * sesudah POST — bukan disimpulkan dari pesan di layar.
 *
 * Diperiksa juga apa yang TERLIHAT: NIK wajib tersamar di daftar. Kalau kelak
 * ada yang menampilkannya penuh "supaya gampang dicari", penjaga ini merah.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('ENV_PATH', APP_ROOT . '/.env');
define('SANDI', 'UjiScope!2026');

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;
$GLOBALS['users']     = [];
$GLOBALS['antrean']   = [];

function cek($k, $l) {
    $GLOBALS['uji_total']++;
    echo ($k ? '  OK    ' : '  GAGAL ') . $l . "\n";
    if ( ! $k) { $GLOBALS['uji_gagal']++; }
    return (bool) $k;
}
function wajib($k, $l) {
    if ( ! cek($k, $l)) { bersihkan(); fwrite(STDERR, "Berhenti: prasyarat gagal.\n"); exit(1); }
}
function env_config($p) {
    $o = [];
    foreach (file($p, FILE_IGNORE_NEW_LINES) as $b) {
        $b = trim($b);
        if ($b === '' || $b[0] === '#' || strpos($b, '=') === FALSE) { continue; }
        [$k, $v] = explode('=', $b, 2);
        if ( ! isset($o[trim($k)])) { $o[trim($k)] = trim($v); }
    }
    return $o;
}
function q($sql, $p = []) {
    $st = $GLOBALS['db']->prepare($sql);
    if ( ! $st) { fwrite(STDERR, $GLOBALS['db']->error . "\n"); exit(1); }
    if ($p) { $st->bind_param(str_repeat('s', count($p)), ...$p); }
    $st->execute();
    $r = $st->get_result();
    $o = $r ? $r->fetch_all(MYSQLI_ASSOC) : [];
    $st->close();
    return $o;
}
function tulis($sql, $p = []) {
    $st = $GLOBALS['db']->prepare($sql);
    if ( ! $st) { fwrite(STDERR, $GLOBALS['db']->error . "\n"); exit(1); }
    if ($p) { $st->bind_param(str_repeat('s', count($p)), ...$p); }
    $st->execute();
    $id = $GLOBALS['db']->insert_id;
    $st->close();
    return $id;
}
function bersihkan() {
    if (empty($GLOBALS['db'])) { return; }
    foreach ($GLOBALS['antrean'] as $id) { $GLOBALS['db']->query('DELETE FROM sf_housing_queue WHERE id=' . (int) $id); }
    foreach ($GLOBALS['users'] as $id)   { $GLOBALS['db']->query('DELETE FROM usr_users WHERE id=' . (int) $id); }
    foreach (glob(sys_get_temp_dir() . '/scope_*') as $f) { @unlink($f); }
}
register_shutdown_function('bersihkan');

/** Satu sesi HTTP mandiri per admin — cookie jar terpisah, itu intinya. */
class Sesi {
    private $jar;
    public function __construct($nama) { $this->jar = tempnam(sys_get_temp_dir(), 'scope_' . $nama); }
    public function call($path, $post = NULL) {
        $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_COOKIEJAR => $this->jar,
            CURLOPT_COOKIEFILE => $this->jar, CURLOPT_FOLLOWLOCATION => TRUE, CURLOPT_TIMEOUT => 30]);
        if ($post !== NULL) {
            curl_setopt($ch, CURLOPT_POST, TRUE);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
        }
        $b = (string) curl_exec($ch);
        $s = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$s, $b];
    }
    public function token($path) {
        [, $b] = $this->call($path);
        return preg_match('/name="csrf_kpkp_token" value="([^"]+)"/', $b, $m) ? $m[1] : '';
    }
    public function login($email) {
        $t = $this->token('Auth/login');
        $this->call('Auth/do_login', ['csrf_kpkp_token' => $t, 'email' => $email, 'password' => SANDI]);
        [, $b] = $this->call('Admin_Kabkota');
        return strpos($b, 'Antrean') !== FALSE;
    }
}

function buat_akun($peran, $suffix, $kab = NULL) {
    $email = 'uji_scope_' . $suffix . '_' . time() . '_' . mt_rand(1000, 9999) . '@example.test';
    $id = tulis(
        'INSERT INTO usr_users (email,password,name,username,role,kabupaten_id,status,profile_completed,created_at)
         VALUES (?,?,?,?,?,?, "active",1,NOW())',
        [$email, password_hash(SANDI, PASSWORD_BCRYPT), 'Uji Scope ' . $suffix,
         'uji_scope_' . $suffix . '_' . mt_rand(10000, 99999), $peran, $kab]
    );
    $GLOBALS['users'][] = $id;
    return [$id, $email];
}

function buat_antrean($kab, $user_id, $program_id, $nama, $nik, $tiket) {
    $id = tulis(
        'INSERT INTO sf_housing_queue (user_id,program_id,kabupaten_id,nama_lengkap,nik_pengaju,ticket_code,status_antrean,created_at)
         VALUES (?,?,?,?,?,?, "pending", NOW())',
        [$user_id, $program_id, $kab, $nama, $nik, $tiket]
    );
    $GLOBALS['antrean'][] = $id;
    return $id;
}

echo "=== UJI BATAS WILAYAH ANTREAN KAB/KOTA (butir B2) ===\n\n";
if ( ! is_file(ENV_PATH)) { die(".env tidak ditemukan.\n"); }
$env = env_config(ENV_PATH);
$GLOBALS['db'] = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
if ($GLOBALS['db']->connect_error) { die("Koneksi DB gagal.\n"); }

// -------------------------------------------------------------- 0. Prasyarat
echo "== 0. Prasyarat ==\n";
$kab = array_column(q('SELECT id FROM kabupaten ORDER BY id LIMIT 2'), 'id');
wajib(count($kab) === 2, 'Ada minimal dua kabupaten untuk diadu');
$prog = q('SELECT id FROM sf_programs ORDER BY id LIMIT 1');
wajib($prog, 'Ada minimal satu program');
$program_id = (int) $prog[0]['id'];

[$uidA, $emailA] = buat_akun('admin_kabkota', 'a', $kab[0]);
[$uidB, $emailB] = buat_akun('admin_kabkota', 'b', $kab[1]);
[$uidW, ]        = buat_akun('warga', 'w');

/* `ticket_code` adalah varchar(10) dan MySQL di sini TIDAK strict: tiket yang
   lebih panjang dipotong DIAM-DIAM. Versi pertama uji ini memakai tiket 13
   karakter, dan akibatnya asersi "A tidak melihat antrean B" LULUS karena
   string penuh B memang tidak pernah ada di halaman — bukan karena batas
   wilayahnya bekerja. Panjangnya sekarang tepat 10, dan hasil simpannya
   diperiksa sebagai prasyarat di bawah. */
$tiketA = 'UJSA' . mt_rand(100000, 999999);
$tiketB = 'UJSB' . mt_rand(100000, 999999);
$antreanA = buat_antrean($kab[0], $uidW, $program_id, 'Warga Wilayah A', '3300000000000001', $tiketA);
$antreanB = buat_antrean($kab[1], $uidW, $program_id, 'Warga Wilayah B', '3300000000000002', $tiketB);

$tersimpanA = q('SELECT ticket_code FROM sf_housing_queue WHERE id=?', [$antreanA])[0]['ticket_code'];
$tersimpanB = q('SELECT ticket_code FROM sf_housing_queue WHERE id=?', [$antreanB])[0]['ticket_code'];
wajib($tersimpanA === $tiketA && $tersimpanB === $tiketB,
    'PRASYARAT: kode tiket tersimpan UTUH, tidak terpotong diam-diam oleh kolom');

$sesiA = new Sesi('a');
/* Prasyarat KERAS. Kalau sesi ini tidak benar-benar sampai ke layar antrean,
   seluruh asersi "tidak melihat wilayah lain" di bawah akan lulus karena tidak
   terjadi apa-apa — hijau yang tidak membuktikan apa pun. */
wajib($sesiA->login($emailA), 'Admin kab/kota A benar-benar masuk ke layar Antrean');

// ------------------------------------------------------- 1. Daftar ter-scope
echo "\n== 1. Daftar hanya memuat wilayah sendiri ==\n";
/* Pembedanya KODE TIKET, bukan nama. Sejak butir B2 nama warga diganti data
   contoh selama menunggu keputusan dinas, jadi nama tidak lagi bisa dipakai
   membedakan baris — dan kode tiket memang pembeda yang lebih tepat: ia bukan
   data pribadi, dan tetap utuh apa pun keputusan kebijakannya nanti. */
[$s, $daftarA] = $sesiA->call('Admin_Kabkota');
cek($s === 200, 'Layar antrean A terbuka');
cek(strpos($daftarA, $tiketA) !== FALSE, 'A melihat antrean wilayahnya sendiri');
cek(strpos($daftarA, $tiketB) === FALSE, 'A TIDAK melihat antrean wilayah B');

/* Pencarian adalah kanal kebocoran tersendiri: daftar boleh ter-scope sementara
   kotak carinya menembus wilayah. Dicari dengan NIK milik B, yang paling
   spesifik dan paling mustahil muncul kebetulan. */
[, $cariA] = $sesiA->call('Admin_Kabkota?q=3300000000000002');
cek(strpos($cariA, $tiketB) === FALSE, 'Mencari NIK wilayah B tidak memunculkannya');

// ---------------------------------------------------------- 2. NIK tersamar
echo "\n== 2. Yang terlihat di daftar ==\n";
cek(strpos($daftarA, '3300000000000001') === FALSE, 'NIK TIDAK tampil utuh di daftar');

// ------------------------------------- 2b. Identitas menunggu keputusan (B2)
echo "\n== 2b. Identitas diganti data contoh selama menunggu keputusan ==\n";
$kebijakan = (string) @file_get_contents(APP_ROOT . '/application/config/kebijakan_data.php');
$menunggu = strpos($kebijakan, "\$config['identitas_warga_kabkota'] = 'menunggu_keputusan';") !== FALSE;
cek(TRUE, 'Sakelar kebijakan terbaca: ' . ($menunggu ? 'menunggu_keputusan' : 'tampil'));

if ($menunggu) {
    /* Yang dijaga: nama SUNGGUHAN tidak sampai ke layar. Ini asersi yang paling
       gampang jadi hampa — kalau daftarnya kosong, "nama tidak muncul" lulus
       tanpa membuktikan apa pun. Karena itu keberadaan baris contohnya diperiksa
       lebih dulu sebagai prasyarat. */
    cek(strpos($daftarA, 'Warga Contoh') !== FALSE, 'PRASYARAT: baris contoh benar-benar dirender');
    cek(strpos($daftarA, 'Warga Wilayah A') === FALSE, 'Nama warga SUNGGUHAN tidak sampai ke layar');
    cek(strpos($daftarA, 'id="modal-identitas-b2"') !== FALSE, 'Modal keputusan dinas dirender');
    cek(strpos($daftarA, 'disampaikan kepada pengembang') !== FALSE, 'Modal meminta keputusannya disampaikan ke pengembang');
    cek(strpos($daftarA, 'Batas wilayahnya mengikat') !== FALSE, 'Modal memisahkan yang sudah aman dari yang belum diputuskan');
} else {
    cek(strpos($daftarA, 'Warga Wilayah A') !== FALSE, 'Dinas memutuskan tampil: nama sungguhan muncul');
    cek(strpos($daftarA, 'id="modal-identitas-b2"') === FALSE, 'Modal keputusan hilang sendiri sesudah diputuskan');
}

// ---------------------------------------------------------- 3. Detail & berkas
echo "\n== 3. Detail dan berkas wilayah lain ditolak ==\n";
/* 🔻 ASERSI INI SEMPAT HAMPA, dicatat supaya penggantinya tidak mengulang.
   Versi pertama berbunyi `$sDetB === 404 || strpos($body,'Warga Wilayah B')===FALSE`.
   Baris uji ini tidak punya `assessment_id`, dan layar detail memang tidak
   pernah menampilkan `nama_lengkap` dari baris antrean — jadi ruas kedua SELALU
   benar, entah batas wilayahnya bekerja atau tidak. Mutasi yang mencabut scope
   dari `get_scoped_queue_detail()` LOLOS. Sekarang yang diperiksa KODE HTTP-nya
   saja, ketat: ada scope -> baris tak ditemukan -> 404; tanpa scope -> 200. */
[$sDetB, ] = $sesiA->call('Admin_Kabkota/detail/' . $antreanB);
cek($sDetB === 404, 'Detail antrean wilayah B ditolak walau id-nya benar (dapat: ' . $sDetB . ')');

[$sDetA, ] = $sesiA->call('Admin_Kabkota/detail/' . $antreanA);
cek($sDetA === 200, 'PEMBANDING: detail wilayah sendiri tetap terbuka — 404 di atas bukan karena semuanya tertutup');

/* Berkas bukti TIDAK diuji lewat unduhan sungguhan, dan itu disebut apa adanya:
   tanpa assessment + berkas asli, `evidence` membalas 404 baik scope-nya bekerja
   maupun tidak — uji yang tidak bisa membedakan apa pun. Yang dijaga di sini
   adalah jalurnya: `get_scoped_queue_files()` WAJIB menurunkan izinnya dari
   `get_scoped_queue_detail()` yang sudah dijaga ketat di atas. Kalau kelak ia
   query sendiri ke `sf_berkas_penilaian`, batas wilayahnya lepas tanpa suara. */
$model = (string) @file_get_contents(APP_ROOT . '/application/models/Housing_assessment_model.php');
cek(preg_match('/function get_scoped_queue_files.*?get_scoped_queue_detail\(\$queue_id, \$kabupaten_id\)/s', $model) === 1,
    'Berkas bukti menurunkan izinnya dari detail yang ter-scope, bukan query sendiri');

// ------------------------------------------------------------- 4. Sisi tulis
echo "\n== 4. Keputusan atas wilayah lain tidak mengubah apa pun ==\n";
$sebelum = q('SELECT status_antrean FROM sf_housing_queue WHERE id=?', [$antreanB])[0]['status_antrean'];
$tok = $sesiA->token('Admin_Kabkota');
$sesiA->call('Admin_Kabkota/update_status', [
    'csrf_kpkp_token' => $tok, 'queue_id' => $antreanB,
    'from_status' => 'pending', 'status' => 'approved',
    'catatan_admin' => 'percobaan lintas wilayah',
]);
$sesudah = q('SELECT status_antrean FROM sf_housing_queue WHERE id=?', [$antreanB])[0]['status_antrean'];
cek($sebelum === $sesudah && $sesudah === 'pending',
    'Status antrean B TIDAK berubah — dibaca ulang dari DB, bukan dari pesan layar');

/* Pembanding wajib: kalau A juga tidak bisa memutuskan antreannya SENDIRI,
   asersi di atas hijau karena fiturnya mati, bukan karena batasnya bekerja. */
$tok2 = $sesiA->token('Admin_Kabkota');
$sesiA->call('Admin_Kabkota/update_status', [
    'csrf_kpkp_token' => $tok2, 'queue_id' => $antreanA,
    'from_status' => 'pending', 'status' => 'approved',
    'catatan_admin' => 'keputusan wilayah sendiri',
]);
$statusA = q('SELECT status_antrean FROM sf_housing_queue WHERE id=?', [$antreanA])[0]['status_antrean'];
cek($statusA === 'approved', 'PEMBANDING: A tetap bisa memutuskan antrean wilayahnya sendiri');

// -------------------------------------------- 5. Akun tanpa wilayah ditolak
echo "\n== 5. Admin kab/kota tanpa wilayah tidak melihat apa pun ==\n";
[, $emailX] = buat_akun('admin_kabkota', 'x', NULL);
$sesiX = new Sesi('x');
$tokX = $sesiX->token('Auth/login');
$sesiX->call('Auth/do_login', ['csrf_kpkp_token' => $tokX, 'email' => $emailX, 'password' => SANDI]);
[, $bodyX] = $sesiX->call('Admin_Kabkota');
cek(strpos($bodyX, $tiketA) === FALSE && strpos($bodyX, $tiketB) === FALSE,
    'Akun tanpa wilayah tidak melihat antrean mana pun (scope NULL = lihat semua adalah bahayanya)');

echo "\n=== Ringkasan ===\n";
printf("  %d pemeriksaan, %d merah\n", $GLOBALS['uji_total'], $GLOBALS['uji_gagal']);
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
