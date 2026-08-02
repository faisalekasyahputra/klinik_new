<?php
/**
 * Uji meja tinjauan TAHAP DUA KKN/Magang — `Kemitraan_Bidang`, peran
 * `admin_bidang`.
 *
 *   php docs/engineering/uji_kemitraan_bidang.php
 *
 * Ditulis karena `admin_bidang` adalah peran termuda di sistem ini dan satu-
 * satunya yang layar utamanya belum pernah punya harness sendiri: runner
 * mencatatnya "3 suite", tapi ketiganya menyentuh peran itu sebagai latar, bukan
 * sebagai subjek. Meja ini yang memutuskan apakah seorang mahasiswa jadi magang
 * di sebuah bidang, dan berkas yang disajikannya adalah surat pengantar berisi
 * data kependudukan — dua alasan yang cukup.
 *
 * Yang benar-benar dijaga di sini BUKAN "layarnya terbuka", melainkan tiga hal
 * yang kalau bocor tidak akan terlihat dari layar mana pun:
 *
 *   1. SEKAT ANTAR BIDANG. Scope datang dari sesi (`$this->my_bidang_kode`),
 *      tidak pernah dari URL. Mengganti angka di URL harus 404, termasuk untuk
 *      mengunduh berkas — bukan 403, karena 403 sudah membocorkan bahwa barisnya
 *      ada.
 *   2. URUTAN TAHAP. Bidang hanya boleh memutuskan yang SUDAH diteruskan
 *      sekretariat. Kalau ia bisa memutuskan yang masih 'Diajukan', meja pertama
 *      berhenti berarti apa pun dan diagram alurnya jadi hiasan.
 *   3. JEJAK TERPISAH. Keputusan bidang menulis ke `reviewed_by_bidang`, dan
 *      TIDAK BOLEH menimpa `reviewed_by` milik tahap satu. "Siapa yang
 *      meloloskan ini ke tahap dua" justru pertanyaan yang paling sering
 *      ditanyakan ketika ada yang keliru.
 *
 * Data uji dibuat dan dihapus sendiri; tidak bersandar pada akun demo mana pun
 * (pelajaran dari r4/r5/r6: harness yang meminjam data bersama akan merah
 * karena sebab yang tidak ada hubungannya dengan apa yang diujinya).
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('ENV_PATH', APP_ROOT . '/.env');
define('SANDI', 'UjiBidang!2026');
define('CAP', 'UJI-BIDANG-' . date('YmdHis'));

// Dua bidang yang benar-benar ada. Sekat antar bidang tidak bisa diuji dengan
// satu bidang saja.
define('BIDANG_A', 'perumahan');
define('BIDANG_B', 'kawasan');
define('BIDANG_TANPA_REKAM', 'pertanahan');

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;
$GLOBALS['users'] = [];
$GLOBALS['daftar'] = [];
$GLOBALS['jar'] = [];

function cek($kondisi, $label) {
    $GLOBALS['uji_total']++;
    echo ($kondisi ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $kondisi) { $GLOBALS['uji_gagal']++; }
    return (bool) $kondisi;
}

function wajib($kondisi, $label) {
    if ( ! cek($kondisi, $label)) {
        bersihkan();
        fwrite(STDERR, "Berhenti: prasyarat gagal.\n");
        exit(1);
    }
}

/** Parser .env milik index.php: kemunculan PERTAMA menang, baris '#' dilewati. */
function env_config($path) {
    $out = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $baris) {
        $baris = trim($baris);
        if ($baris === '' || $baris[0] === '#' || strpos($baris, '=') === FALSE) { continue; }
        [$k, $v] = explode('=', $baris, 2);
        $k = trim($k);
        if ( ! isset($out[$k])) { $out[$k] = trim($v); }
    }
    return $out;
}

function q($sql, $params = []) {
    $stmt = $GLOBALS['db']->prepare($sql);
    if ( ! $stmt) { fwrite(STDERR, $GLOBALS['db']->error . "\n"); exit(1); }
    if ($params) { $stmt->bind_param(str_repeat('s', count($params)), ...$params); }
    $stmt->execute();
    $res = $stmt->get_result();
    $out = $res ? $res->fetch_assoc() : NULL;
    $id = $stmt->insert_id;
    $stmt->close();
    return $out ?: ['__id' => $id];
}

function tulis($sql, $params = []) { return (int) (q($sql, $params)['__id'] ?? 0); }
function nilai($sql, $params = []) { $r = q($sql, $params); return $r && ! isset($r['__id']) ? reset($r) : NULL; }

function sesi($nama) {
    if ( ! isset($GLOBALS['jar'][$nama])) {
        $GLOBALS['jar'][$nama] = tempnam(sys_get_temp_dir(), 'ujib_');
    }
    return $GLOBALS['jar'][$nama];
}

function http($nama, $path, ?array $post = NULL, $ajax = FALSE) {
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    $opt = [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_COOKIEJAR      => sesi($nama),
        CURLOPT_COOKIEFILE     => sesi($nama),
        CURLOPT_FOLLOWLOCATION => TRUE,
        CURLOPT_TIMEOUT        => 30,
    ];
    if ($ajax) { $opt[CURLOPT_HTTPHEADER] = ['X-Requested-With: XMLHttpRequest']; }
    if ($post !== NULL) { $opt[CURLOPT_POST] = TRUE; $opt[CURLOPT_POSTFIELDS] = http_build_query($post); }
    curl_setopt_array($ch, $opt);
    $body = (string) curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    return ['code' => (int) $info['http_code'], 'body' => $body, 'url' => (string) $info['url']];
}

function csrf($nama, $path) {
    $r = http($nama, $path);
    return preg_match('/name="csrf_kpkp_token" value="([^"]+)"/', $r['body'], $m) ? $m[1] : '';
}

/**
 * Kode HTTP saja TIDAK cukup: login gagal juga membalas 200 karena redirect
 * diikuti. Cabang JSON-nya hanya menyala untuk permintaan AJAX, jadi headernya
 * wajib — tanpa itu body-nya HTML, json_decode NULL, dan harness melaporkan
 * "login gagal" pada login yang sebenarnya berhasil.
 */
function login($nama, $email) {
    $r = http($nama, 'Auth/do_login', [
        'csrf_kpkp_token' => csrf($nama, 'Auth/login'),
        'email' => $email, 'password' => SANDI,
    ], TRUE);
    return (json_decode($r['body'], TRUE)['status'] ?? '') === 'success';
}

function buat_akun($peran, $suffix, $bidang = NULL) {
    $email = 'uji_bidang_' . $suffix . '_' . time() . '_' . mt_rand(1000, 9999) . '@example.test';
    $id = tulis(
        'INSERT INTO usr_users (email,password,name,username,role,status,profile_completed,bidang_kode,created_at)
         VALUES (?,?,?,?,?, "active",1,?,NOW())',
        [$email, password_hash(SANDI, PASSWORD_BCRYPT), 'Uji ' . $suffix,
         'uji_bidang_' . $suffix . '_' . mt_rand(10000, 99999), $peran, $bidang]
    );
    $GLOBALS['users'][] = $id;
    return [$id, $email];
}

/**
 * Pendaftaran ditulis LANGSUNG ke DB, bukan lewat formulir mahasiswa. Yang
 * diuji berkas ini adalah meja tahap dua; menempuh ulang pendaftaran lewat HTTP
 * cuma menyalin cakupan `uji_perjalanan_mahasiswa.php` dan membuat uji ini
 * merah karena sebab yang bukan miliknya.
 */
function buat_pendaftaran($user_id, $bidang, $status, $peninjau_tahap_satu = NULL) {
    $id = tulis(
        'INSERT INTO kkn_magang_pendaftaran
         (user_id,jenis,nim,tempat_lahir,tanggal_lahir,semester,jurusan,instansi_asal,no_hp,
          divisi_atau_tema,bidang_kode,periode_mulai,periode_selesai,status,file_surat_pengantar,
          reviewed_by,created_at)
         VALUES (?, "magang", ?, "Semarang","2003-05-17","6","Teknik Informatika", ?, "081234567890",
          "Tema Uji", ?, "2027-06-01","2027-07-31", ?, ?, ?, NOW())',
        [$user_id, 'NIM' . mt_rand(100000, 999999), CAP . '-' . $bidang, $bidang, $status,
         'surat_uji.pdf', $peninjau_tahap_satu]
    );
    $GLOBALS['daftar'][] = $id;

    // Berkas fisiknya benar-benar ada, supaya 404 pada unduhan lintas bidang
    // membuktikan GUARD-nya bekerja — bukan sekadar berkasnya kebetulan tidak
    // ada. Uji negatif yang lulus karena sebab lain adalah uji yang berbohong.
    $dir = rtrim($GLOBALS['akar_privat'], '/\\') . DIRECTORY_SEPARATOR . 'kemitraan' . DIRECTORY_SEPARATOR . $id;
    if ( ! is_dir($dir)) { @mkdir($dir, 0777, TRUE); }
    file_put_contents($dir . DIRECTORY_SEPARATOR . 'surat_uji.pdf', "%PDF-1.4\n% uji bidang\n%%EOF\n");
    return $id;
}

function status_dari_db($id) { return (string) nilai('SELECT status FROM kkn_magang_pendaftaran WHERE id=?', [$id]); }

function bersihkan() {
    if (empty($GLOBALS['db'])) { return; }
    foreach ($GLOBALS['daftar'] as $id) {
        $dir = rtrim($GLOBALS['akar_privat'] ?? '', '/\\') . DIRECTORY_SEPARATOR . 'kemitraan' . DIRECTORY_SEPARATOR . $id;
        if (is_dir($dir)) {
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $f) { @unlink($f); }
            @rmdir($dir);
        }
        q('DELETE FROM kkn_magang_pendaftaran WHERE id=?', [$id]);
    }
    foreach ($GLOBALS['users'] as $id) { q('DELETE FROM usr_users WHERE id=?', [$id]); }
    foreach ($GLOBALS['jar'] as $j) { @unlink($j); }
}
register_shutdown_function('bersihkan');

// ==========================================================================

if ( ! is_file(ENV_PATH)) { die(".env tidak ditemukan.\n"); }
$env = env_config(ENV_PATH);
$GLOBALS['db'] = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
if ($GLOBALS['db']->connect_error) { die("Koneksi DB gagal.\n"); }

$akar = $env['PRIVATE_UPLOADS_PATH'] ?? '';
if ($akar === '') { die("PRIVATE_UPLOADS_PATH wajib untuk uji ini.\n"); }
if ( ! preg_match('#^(?:[A-Za-z]:|[/\\\\])#', $akar)) { $akar = APP_ROOT . DIRECTORY_SEPARATOR . $akar; }
$GLOBALS['akar_privat'] = $akar;

echo "=== UJI KEMITRAAN BIDANG (tinjauan tahap dua) ===\n";
echo 'Target: ' . BASE_URL . " | DB: {$env['DB_NAME']}\n\n";

wajib(nilai('SELECT kode FROM bidang WHERE kode=?', [BIDANG_A]) === BIDANG_A, 'Bidang ' . BIDANG_A . ' ada di DB');
wajib(nilai('SELECT kode FROM bidang WHERE kode=?', [BIDANG_B]) === BIDANG_B, 'Bidang ' . BIDANG_B . ' ada di DB');
wajib(nilai('SELECT kode FROM bidang WHERE kode=?', [BIDANG_TANPA_REKAM]) === BIDANG_TANPA_REKAM,
    'Bidang ' . BIDANG_TANPA_REKAM . ' ada di DB');

[$adminA, $emailA] = buat_akun('admin_bidang', 'a', BIDANG_A);
[$adminB, $emailB] = buat_akun('admin_bidang', 'b', BIDANG_B);
[$adminTanpaRekam, $emailTanpaRekam] = buat_akun('admin_bidang', 'tanpa_rekam', BIDANG_TANPA_REKAM);
[$yatimId, $yatimEmail] = buat_akun('admin_bidang', 'yatim', NULL);
[$mhsId, $mhsEmail] = buat_akun('mahasiswa', 'mhs');
[$wargaId, $wargaEmail] = buat_akun('warga', 'warga');

// Peninjau tahap satu yang jejaknya TIDAK BOLEH tertimpa.
$dA  = buat_pendaftaran($mhsId, BIDANG_A, 'Ditinjau Bidang', $wargaId);
$dB  = buat_pendaftaran($mhsId, BIDANG_B, 'Ditinjau Bidang', $wargaId);
$din = buat_pendaftaran($mhsId, BIDANG_A, 'Diajukan', NULL);

// ------------------------------------------------------------------ gerbang
wajib(login('a', $emailA), 'Login admin bidang A');
$idx = http('a', 'Kemitraan_Bidang');
wajib($idx['code'] === 200 && strpos($idx['url'], 'Auth/login') === FALSE, 'Admin bidang mendapat layar tinjauan');

cek(strpos($idx['body'], CAP . '-' . BIDANG_A) !== FALSE, 'Pendaftaran bidang sendiri tampil di daftar');
cek(strpos($idx['body'], CAP . '-' . BIDANG_B) === FALSE, 'Pendaftaran bidang LAIN tidak tampil di daftar');

preg_match('/>Magang Bidang Saya<\/span>\s*<span[^>]*>(\d+)<\/span>/s', $idx['body'], $badge);
$pending_bidang_a = (int) nilai('SELECT COUNT(*) c FROM kkn_magang_pendaftaran
    WHERE status=? AND bidang_kode=?', ['Ditinjau Bidang', BIDANG_A]);
cek(isset($badge[1]) && (int) $badge[1] === $pending_bidang_a,
    'Badge Magang Bidang Saya hanya menghitung antrean bidang sesi');

wajib(login('tanpa_rekam', $emailTanpaRekam), 'Login admin bidang tanpa Rekam Data');
$tanpa_rekam = http('tanpa_rekam', 'Rekam_Tinjauan');
cek($tanpa_rekam['code'] === 200
    && strpos($tanpa_rekam['url'], 'Admin_Bidang') !== FALSE
    && strpos($tanpa_rekam['body'], 'Aduan Bidang Saya') !== FALSE
    && strpos($tanpa_rekam['body'], 'Peninjauan Rekam Data') === FALSE,
    'Bidang tanpa Rekam Data kembali ke dasbor yang diizinkan tanpa menu Rekam Data');

/**
 * Yang dibuktikan di sini adalah layar meja bidang tidak ikut dirender, bukan
 * "diarahkan ke Auth/login". Pengguna yang sudah login kini kembali ke
 * dashboard perannya; mahasiswa uji memang dapat melihat pendaftarannya SENDIRI
 * di /akun, jadi penanda data ujinya tidak boleh dijadikan bukti kebocoran.
 */
function tak_kebagian($nama, $label) {
    $r = http($nama, 'Kemitraan_Bidang');
    cek(strpos($r['url'], 'Kemitraan_Bidang') === FALSE
        && stripos($r['body'], 'Magang Bidang Saya') === FALSE, $label);
}

wajib(login('mhs', $mhsEmail), 'Login mahasiswa');
tak_kebagian('mhs', 'Mahasiswa tidak kebagian isi meja bidang');

wajib(login('warga', $wargaEmail), 'Login warga');
tak_kebagian('warga', 'Warga tidak kebagian isi meja bidang');

// Akun admin_bidang TANPA bidang: gerbangnya ada di base controller, dan tanpa
// uji ini ia cuma janji di komentar. Kalau `bidang_kode` kosong lolos, WHERE-nya
// menjadi `bidang_kode IS NULL` atau '' — dan yang tampil bisa jadi baris orang
// lain, bukan layar kosong.
wajib(login('yatim', $yatimEmail), 'Login admin bidang tanpa bidang');
tak_kebagian('yatim', 'Admin bidang tanpa bidang_kode tidak kebagian isi meja bidang');

// ------------------------------------------------------------- sekat berkas
$dok_sendiri = http('a', 'Kemitraan_Bidang/lihat_dokumen/' . $dA . '/surat');
cek($dok_sendiri['code'] === 200 && strpos($dok_sendiri['body'], '%PDF') === 0, 'Surat bidang sendiri tersaji');

$dok_lain = http('a', 'Kemitraan_Bidang/lihat_dokumen/' . $dB . '/surat');
cek($dok_lain['code'] === 404, 'Surat bidang LAIN ditolak 404 walau berkasnya ada');
cek(strpos($dok_lain['body'], '%PDF') === FALSE, 'Isi surat bidang lain tidak ikut terkirim');

cek(http('a', 'Kemitraan_Bidang/lihat_dokumen/' . $dA . '/ngawur')['code'] === 404, 'Jenis berkas di luar daftar ditolak');
cek(http('a', 'Kemitraan_Bidang/lihat_dokumen/abc/surat')['code'] === 404, 'Id bukan angka ditolak');

// ------------------------------------------------------------------ menulis
cek(http('a', 'Kemitraan_Bidang/proses/' . $dA)['code'] === 404, 'GET ke proses ditolak — hanya POST yang menulis');

// CSRF: tanpa token, tidak boleh ada perubahan sama sekali.
http('a', 'Kemitraan_Bidang/proses/' . $dA, ['status' => 'Diterima', 'catatan_admin' => 'tanpa token']);
cek(status_dari_db($dA) === 'Ditinjau Bidang', 'POST tanpa token CSRF tidak mengubah status');

// IDOR tulis: baris bidang lain.
$tok = csrf('a', 'Kemitraan_Bidang');
$idor = http('a', 'Kemitraan_Bidang/proses/' . $dB, [
    'csrf_kpkp_token' => $tok, 'status' => 'Diterima', 'catatan_admin' => 'lintas bidang',
]);
cek($idor['code'] === 404, 'Memutuskan baris bidang lain dibalas 404');
cek(status_dari_db($dB) === 'Ditinjau Bidang', 'Status baris bidang lain tidak berubah');
cek((string) nilai('SELECT catatan_bidang FROM kkn_magang_pendaftaran WHERE id=?', [$dB]) === '',
    'Catatan tidak bocor ke baris bidang lain');

// Status di luar daftar.
$tok = csrf('a', 'Kemitraan_Bidang');
http('a', 'Kemitraan_Bidang/proses/' . $dA, ['csrf_kpkp_token' => $tok, 'status' => 'Ngawur', 'catatan_admin' => 'x']);
cek(status_dari_db($dA) === 'Ditinjau Bidang', 'Status di luar daftar ditolak server');

// Urutan tahap: yang belum diteruskan sekretariat tidak boleh diputuskan.
$tok = csrf('a', 'Kemitraan_Bidang');
http('a', 'Kemitraan_Bidang/proses/' . $din, ['csrf_kpkp_token' => $tok, 'status' => 'Diterima', 'catatan_admin' => 'lompat tahap']);
cek(status_dari_db($din) === 'Diajukan', 'Pendaftaran yang belum diteruskan sekretariat tidak bisa diputuskan bidang');
cek((string) nilai('SELECT catatan_bidang FROM kkn_magang_pendaftaran WHERE id=?', [$din]) === '',
    'Penolakan lompat tahap tidak meninggalkan catatan separuh jadi');

// ------------------------------------------------------------- jalur positif
$tok = csrf('a', 'Kemitraan_Bidang');
$ok = http('a', 'Kemitraan_Bidang/proses/' . $dA, [
    'csrf_kpkp_token' => $tok, 'status' => 'Diterima', 'catatan_admin' => 'Disetujui bidang ' . CAP,
]);
cek($ok['code'] === 200, 'Keputusan sah diterima server');
cek(status_dari_db($dA) === 'Diterima', 'Status berubah menjadi Diterima');
cek((string) nilai('SELECT catatan_bidang FROM kkn_magang_pendaftaran WHERE id=?', [$dA]) === 'Disetujui bidang ' . CAP,
    'Catatan bidang tersimpan utuh');
cek((int) nilai('SELECT reviewed_by_bidang FROM kkn_magang_pendaftaran WHERE id=?', [$dA]) === $adminA,
    'Peninjau bidang tercatat dari SESI');
cek((string) nilai('SELECT reviewed_at_bidang FROM kkn_magang_pendaftaran WHERE id=?', [$dA]) !== '',
    'Waktu tinjauan bidang tercatat');

// Inti butir 3: jejak tahap satu utuh.
cek((int) nilai('SELECT reviewed_by FROM kkn_magang_pendaftaran WHERE id=?', [$dA]) === $wargaId,
    'Jejak peninjau TAHAP SATU tidak tertimpa keputusan bidang');

// Keputusan ganda: sudah Diterima berarti tidak lagi 'Ditinjau Bidang'.
$tok = csrf('a', 'Kemitraan_Bidang');
http('a', 'Kemitraan_Bidang/proses/' . $dA, ['csrf_kpkp_token' => $tok, 'status' => 'Ditolak', 'catatan_admin' => 'balik keputusan']);
cek(status_dari_db($dA) === 'Diterima', 'Keputusan yang sudah final tidak bisa dibalik lewat POST ulang');

// Bidang B memutuskan barisnya sendiri — membuktikan sekat tadi bukan karena
// baris B kebetulan tidak bisa diputuskan siapa pun.
wajib(login('b', $emailB), 'Login admin bidang B');
$tok = csrf('b', 'Kemitraan_Bidang');
http('b', 'Kemitraan_Bidang/proses/' . $dB, ['csrf_kpkp_token' => $tok, 'status' => 'Ditolak', 'catatan_admin' => 'Ditolak B']);
cek(status_dari_db($dB) === 'Ditolak', 'Bidang B bisa memutuskan barisnya sendiri');
cek((int) nilai('SELECT reviewed_by_bidang FROM kkn_magang_pendaftaran WHERE id=?', [$dB]) === $adminB,
    'Peninjau baris B adalah admin B, bukan admin A');

bersihkan();
$GLOBALS['daftar'] = $GLOBALS['users'] = [];
cek((int) nilai('SELECT COUNT(*) c FROM kkn_magang_pendaftaran WHERE instansi_asal LIKE ?', [CAP . '%']) === 0,
    'Data uji dibersihkan');

echo "\nRINGKASAN: {$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
