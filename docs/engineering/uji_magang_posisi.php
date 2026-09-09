<?php
/**
 * Uji POSISI MAGANG - butir F1 revisi dinas.
 *
 *   php docs/engineering/uji_magang_posisi.php
 *
 * Dinas minta papan magang menampilkan daftar posisi, bukan nama bidang.
 * Daftar posisinya sendiri BELUM PERNAH diberikan secara resmi - lima yang
 * disebut di rapat masih berupa contoh dalam kalimat. Karena itu jawabannya
 * layar admin, bukan seed migrasi.
 *
 * TIGA JANJI YANG DIJAGA, semuanya bisa rusak tanpa satu pun galat:
 *
 *   1. POSISI TIDAK MENGGANTIKAN BIDANG. `kuota` di tabel posisi hanya
 *      keterangan; yang mengikat pendaftaran tetap `kkn_magang_bidang.kuota`.
 *      Kalau kelak ada yang menyambungkannya ke `periksa_slot()`, kuota bidang
 *      diam-diam kehilangan wewenangnya dan pendaftaran jebol.
 *   2. NOL DAFTAR KARANGAN. Selama dinas belum mengisi, papan publik TIDAK
 *      boleh menampilkan posisi contoh apa pun - mahasiswa akan melamar posisi
 *      yang tidak ada.
 *   3. MODAL PENGINGAT BERUBAH MENURUT KEADAAN. Pengingat yang berbunyi sama
 *      dalam keadaan apa pun cepat jadi hiasan yang diklik tanpa dibaca.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('ENV_PATH', APP_ROOT . '/.env');
define('SANDI', 'UjiPosisi!2026');

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;
$GLOBALS['users']     = [];
$GLOBALS['posisi']    = [];

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
function bersihkan() {
    if (empty($GLOBALS['db'])) { return; }
    $GLOBALS['db']->query("DELETE FROM kkn_magang_posisi WHERE nama_posisi LIKE 'UJIPOS %'");
    foreach ($GLOBALS['users'] as $id) { $GLOBALS['db']->query('DELETE FROM usr_users WHERE id=' . (int) $id); }
    @unlink(jar());
}
function jar() { static $j; if ( ! $j) { $j = tempnam(sys_get_temp_dir(), 'posisi_'); } return $j; }
register_shutdown_function('bersihkan');

function http($path, $post = NULL) {
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_COOKIEJAR => jar(),
        CURLOPT_COOKIEFILE => jar(), CURLOPT_FOLLOWLOCATION => TRUE, CURLOPT_TIMEOUT => 30]);
    if ($post !== NULL) {
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $b = (string) curl_exec($ch);
    curl_close($ch);
    return $b;
}
function token($path) {
    return preg_match('/name="csrf_kpkp_token" value="([^"]+)"/', http($path), $m) ? $m[1] : '';
}

echo "=== UJI POSISI MAGANG (butir F1) ===\n\n";
if ( ! is_file(ENV_PATH)) { die(".env tidak ditemukan.\n"); }
$env = env_config(ENV_PATH);
$GLOBALS['db'] = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
if ($GLOBALS['db']->connect_error) { die("Koneksi DB gagal.\n"); }

// ------------------------------------------------------------- 1. Skema 038
echo "== 1. Tabel posisi (migrasi 038) ==\n";
$kolom = array_column(q("SELECT COLUMN_NAME n FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'kkn_magang_posisi'"), 'n');
wajib($kolom, 'Tabel kkn_magang_posisi ADA');
foreach (['bidang_kode', 'nama_posisi', 'keterangan', 'kuota', 'aktif', 'urutan'] as $k) {
    cek(in_array($k, $kolom, TRUE), "kolom `{$k}` ada");
}
$fk = q("SELECT REFERENCED_TABLE_NAME t FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'kkn_magang_posisi'
           AND REFERENCED_TABLE_NAME IS NOT NULL");
cek(in_array('bidang', array_column($fk, 't'), TRUE), 'FK ke tabel bidang terpasang');

/* NOL SEED adalah janji, bukan kebetulan. Kalau kelak ada yang menambahkan
   seed "supaya tidak kosong", mahasiswa melamar posisi karangan kita. */
$seed = (string) @file_get_contents(APP_ROOT . '/application/migrations/20260701000038_magang_posisi.php');
cek(stripos($seed, 'insert') === FALSE, 'Migrasi 038 TIDAK menyeed satu pun posisi');

// ------------------------------------ 2. Kuota posisi bukan pengunci slot
echo "\n== 2. Posisi tidak merebut wewenang kuota bidang ==\n";
$slot_model = (string) @file_get_contents(APP_ROOT . '/application/models/Kemitraan_slot_model.php');
cek(strpos($slot_model, 'kkn_magang_posisi') === FALSE,
    'Mesin slot TIDAK menyentuh tabel posisi - kuota bidang tetap yang mengikat');

// ------------------------------------------------ 3. Papan publik tanpa isi
echo "\n== 3. Papan publik saat daftar posisi kosong ==\n";
$GLOBALS['db']->query("DELETE FROM kkn_magang_posisi WHERE nama_posisi LIKE 'UJIPOS %'");
$papan_kosong = http('KemitraanPortal/magang');
cek(strpos($papan_kosong, 'Kebutuhan Magang') !== FALSE, 'PRASYARAT: papan magang benar-benar terbuka');
foreach (['programmer', 'drafter', 'content creator'] as $karangan) {
    cek(stripos($papan_kosong, $karangan) === FALSE, "Papan TIDAK memuat posisi karangan '{$karangan}'");
}

// ----------------------------------------------- 4. Admin mengisi, papan ikut
echo "\n== 4. Yang diisi admin muncul di papan ==\n";
$bidang = q('SELECT kode FROM bidang ORDER BY kode LIMIT 1');
wajib($bidang, 'Ada minimal satu bidang');
$kode_bidang = $bidang[0]['kode'];

$emailAdm = 'uji_posisi_' . time() . '_' . mt_rand(1000, 9999) . '@example.test';
$uid = NULL;
$st = $GLOBALS['db']->prepare('INSERT INTO usr_users (email,password,name,username,role,status,profile_completed,created_at)
    VALUES (?,?,?,?,"admin","active",1,NOW())');
$nm = 'Uji Posisi'; $un = 'uji_posisi_' . mt_rand(10000, 99999);
$pw = password_hash(SANDI, PASSWORD_BCRYPT);
$st->bind_param('ssss', $emailAdm, $pw, $nm, $un);
$st->execute(); $uid = $GLOBALS['db']->insert_id; $st->close();
$GLOBALS['users'][] = $uid;

$t = token('Auth/login');
http('Auth/do_login', ['csrf_kpkp_token' => $t, 'email' => $emailAdm, 'password' => SANDI]);
$layar = http('Admin_Magang_Posisi');
wajib(strpos($layar, 'Posisi Magang') !== FALSE, 'Superadmin benar-benar sampai ke layar Posisi Magang');

/* Modal pengingat versi KOSONG harus muncul sekarang. */
cek(strpos($layar, 'Belum ada satu pun posisi magang') !== FALSE,
    'Modal pengingat berbunyi "belum ada posisi" saat daftar kosong');

$t2 = token('Admin_Magang_Posisi');
http('Admin_Magang_Posisi/simpan', [
    'csrf_kpkp_token' => $t2, 'id' => 0, 'bidang_kode' => $kode_bidang,
    'nama_posisi' => 'UJIPOS Drafter', 'keterangan' => 'Menguasai AutoCAD',
    'kuota' => 3, 'urutan' => 1, 'aktif' => 1,
]);
$tersimpan = q("SELECT * FROM kkn_magang_posisi WHERE nama_posisi='UJIPOS Drafter'");
cek(count($tersimpan) === 1, 'Posisi tersimpan');
cek($tersimpan && (int) $tersimpan[0]['kuota'] === 3, 'Kuota tersimpan apa adanya');

$papan_isi = http('KemitraanPortal/magang');
cek(strpos($papan_isi, 'UJIPOS Drafter') !== FALSE, 'Posisi muncul di papan magang publik');
cek(strpos($papan_isi, 'Menguasai AutoCAD') !== FALSE, 'Keterangan ikut terbawa sebagai judul bantuan');

// ------------------------------------------------- 5. Dimatikan, bukan dihapus
echo "\n== 5. Dimatikan menghilang dari papan, catatannya tetap ada ==\n";
$id = (int) $tersimpan[0]['id'];
$t3 = token('Admin_Magang_Posisi');
http('Admin_Magang_Posisi/simpan', [
    'csrf_kpkp_token' => $t3, 'id' => $id, 'bidang_kode' => $kode_bidang,
    'nama_posisi' => 'UJIPOS Drafter', 'keterangan' => 'Menguasai AutoCAD',
    'kuota' => 3, 'urutan' => 1,   // 'aktif' sengaja TIDAK dikirim = dimatikan
]);
$mati = q("SELECT aktif FROM kkn_magang_posisi WHERE id=?", [$id]);
cek($mati && (int) $mati[0]['aktif'] === 0, 'Posisi jadi tidak aktif');
cek(count(q("SELECT id FROM kkn_magang_posisi WHERE id=?", [$id])) === 1, 'Barisnya TETAP ADA, tidak terhapus');
cek(strpos(http('KemitraanPortal/magang'), 'UJIPOS Drafter') === FALSE, 'Posisi tidak aktif hilang dari papan publik');

// ----------------------------------------------------- 6. Bidang divalidasi
echo "\n== 6. Bidang divalidasi ke tabelnya ==\n";
$sebelum = (int) q("SELECT COUNT(*) c FROM kkn_magang_posisi")[0]['c'];
$t4 = token('Admin_Magang_Posisi');
http('Admin_Magang_Posisi/simpan', [
    'csrf_kpkp_token' => $t4, 'id' => 0, 'bidang_kode' => 'bidang_palsu_xyz',
    'nama_posisi' => 'UJIPOS Palsu', 'kuota' => 1, 'urutan' => 0, 'aktif' => 1,
]);
cek((int) q("SELECT COUNT(*) c FROM kkn_magang_posisi")[0]['c'] === $sebelum,
    'Bidang karangan ditolak - tidak ada baris baru');

echo "\n=== Ringkasan ===\n";
printf("  %d pemeriksaan, %d merah\n", $GLOBALS['uji_total'], $GLOBALS['uji_gagal']);
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
