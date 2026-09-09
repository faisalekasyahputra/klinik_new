<?php
/**
 * Penjaga etalase program - admin mengurus 5 program unggulan dari layar,
 * korsel beranda membacanya dari `sf_programs` (migrasi 036).
 *
 *   php docs/engineering/uji_etalase_program.php
 *
 * KENAPA BERKAS INI ADA. Sampai 5 Agt 2026 info program hidup di TIGA tempat
 * yang saling melenceng: tabel `sf_programs`, `Smart_filter::master_programs()`,
 * dan daftar hardcode di dalam JS korsel. Layar Katalog Program bahkan lahir
 * sebagai PEMBANDING selisih itu. Migrasi 036 memindahkan yang DITAMPILKAN ke
 * tabel; penjaga ini memastikan sumber ketiga tidak diam-diam hidup lagi.
 *
 * Bagian unggah gambar diuji dengan berkas SUNGGUHAN, termasuk satu yang
 * menyamar sebagai PNG. Memeriksa ekstensi saja akan meloloskannya.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('ENV_PATH', APP_ROOT . '/.env');
define('SANDI', 'AgenUji!2026');
define('EMAIL_ADMIN', 'agen_admin@agen.test');

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;
$GLOBALS['bersih'] = [];

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
        [$k, $v] = explode('=', $b, 2); $k = trim($k);
        if ( ! isset($o[$k])) { $o[$k] = trim($v); }
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
function jar() {
    static $j; if ( ! $j) { $j = tempnam(sys_get_temp_dir(), 'etal_'); } return $j;
}
function http($path, $post = NULL, $multipart = FALSE) {
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_COOKIEJAR => jar(),
        CURLOPT_COOKIEFILE => jar(), CURLOPT_FOLLOWLOCATION => TRUE, CURLOPT_TIMEOUT => 30]);
    if ($post !== NULL) {
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $multipart ? $post : http_build_query($post));
    }
    $b = (string) curl_exec($ch);
    curl_close($ch);
    return $b;
}
function token($path) {
    return preg_match('/name="csrf_kpkp_token" value="([^"]+)"/', http($path), $m) ? $m[1] : '';
}
function bersihkan() {
    foreach ($GLOBALS['bersih'] as $f) { @unlink($f); }
    @unlink(jar());
}
register_shutdown_function('bersihkan');

echo "=== UJI ETALASE PROGRAM ===\n\n";
if ( ! is_file(ENV_PATH)) { die(".env tidak ditemukan.\n"); }
$env = env_config(ENV_PATH);
$GLOBALS['db'] = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
if ($GLOBALS['db']->connect_error) { die("Koneksi DB gagal.\n"); }

// ------------------------------------------------------------------ 1. Skema
echo "== 1. Kolom etalase (migrasi 036) ==\n";
$kolom = array_column(q("SELECT COLUMN_NAME n FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'sf_programs'"), 'n');
foreach (['badge', 'syarat_utama', 'gambar', 'urutan', 'tampil_korsel'] as $k) {
    cek(in_array($k, $kolom, TRUE), "kolom `{$k}` ada");
}
$etalase = q("SELECT kode_program, gambar, badge FROM sf_programs
              WHERE tampil_korsel = 1 AND is_active = 1 ORDER BY urutan");
cek(count($etalase) >= 1, 'Ada minimal satu program etalase aktif (dapat: ' . count($etalase) . ')');

// ------------------------------------------------------------------ 2. Satu sumber
echo "\n== 2. Korsel membaca DB, bukan daftar hardcode ==\n";
$komponen = (string) @file_get_contents(APP_ROOT . '/application/views/components/program_showcase_carousel.php');
wajib($komponen !== '', 'Sumber komponen korsel terbaca');
cek(strpos($komponen, 'Program_model') !== FALSE, 'Komponen mengambil dari Program_model');
/* Penjaga terpenting berkas ini: daftar hardcode TIDAK BOLEH hidup lagi.
   Tanpa ini, seseorang bisa menambahkan slide langsung di JS "sekali saja" dan
   sumber ketiga itu kembali - persis keadaan yang migrasi 036 hapus. */
cek(preg_match("/\{\s*id:\s*'[a-z_-]+'\s*,\s*title:/", $komponen) === 0,
    'Nol slide hardcode tersisa di JS - sumber ketiga tidak hidup lagi');

$beranda = http('/');
foreach ($etalase as $p) {
    cek(strpos($beranda, '"id":"' . $p['kode_program'] . '"') !== FALSE,
        "Beranda merender slide `{$p['kode_program']}` dari DB");
}

// Jalur GAMBAR benar-benar sampai ke halaman - bukan cuma judulnya.
$rtlh = q("SELECT id, gambar FROM sf_programs WHERE kode_program = 'rtlh'")[0] ?? NULL;
wajib($rtlh !== NULL, 'Program rtlh ada di katalog');
$semula = $rtlh['gambar'];
q("UPDATE sf_programs SET gambar = ? WHERE id = ?", ['assets/img/program/UJI_ETALASE.png', $rtlh['id']]);
cek(strpos(http('/'), 'UJI_ETALASE.png') !== FALSE,
    'Ganti gambar di DB langsung terlihat di korsel');
q("UPDATE sf_programs SET gambar = ? WHERE id = ?", [$semula, $rtlh['id']]);
cek(strpos(http('/'), 'UJI_ETALASE.png') === FALSE, 'Nilai semula dipulihkan');

// ------------------------------------------------------------------ 3. Unggah
echo "\n== 3. Unggah foto - berkas sungguhan ==\n";
$tmp = sys_get_temp_dir();
$png = $tmp . '/uji_etalase_asli.png';
$im = imagecreatetruecolor(60, 40);
imagefill($im, 0, 0, imagecolorallocate($im, 120, 200, 160));
imagepng($im, $png);
$GLOBALS['bersih'][] = $png;

// Menyamar: header GIF + PHP di dalamnya, bernama .png. Memeriksa ekstensi saja
// akan meloloskan ini, dan hasilnya berkas PHP mendarat di direktori publik.
$palsu = $tmp . '/uji_etalase_palsu.png';
file_put_contents($palsu, "GIF89a<?php echo 'tembus'; ?>");
$GLOBALS['bersih'][] = $palsu;

http('Auth/login');
$t = token('Auth/login');
http('Auth/do_login', ['csrf_kpkp_token' => $t, 'email' => EMAIL_ADMIN, 'password' => SANDI]);
wajib(stripos(http('Admin_Katalog_Program'), 'Katalog Program') !== FALSE,
    'Login superadmin agen berhasil dan layar katalog terbuka');

$kirim = function ($berkas) use ($rtlh, $semula) {
    return http('Admin_Katalog_Program/ubah', [
        'csrf_kpkp_token'   => token('Admin_Katalog_Program'),
        'id'                => $rtlh['id'],
        'nama_program'      => 'Peningkatan Kualitas RTLH',
        'deskripsi_singkat' => 'Bantuan perbaikan rumah bagi masyarakat dengan hunian tidak layak.',
        'badge'             => 'Miskin & Ekstrem',
        'syarat_utama'      => 'Terdaftar DTKS dan rumah memenuhi kriteria kerusakan.',
        'urutan'            => 3,
        'tampil_korsel'     => 1,
        'is_active'         => 1,
        'gambar'            => new CURLFile($berkas),
    ], TRUE);
};

$r = $kirim($palsu);
cek(stripos($r, 'Gambar harus JPG atau PNG') !== FALSE,
    'Berkas menyamar DITOLAK - jenis ditentukan dari isi, bukan ekstensi');
cek((string) (q("SELECT gambar g FROM sf_programs WHERE id = ?", [$rtlh['id']])[0]['g'] ?? '') === (string) $semula,
    'Penolakan tidak mengubah gambar yang tersimpan');

$r = $kirim($png);
cek(stripos($r, 'Program diperbarui') !== FALSE, 'PNG sah diterima');
$baru = (string) (q("SELECT gambar g FROM sf_programs WHERE id = ?", [$rtlh['id']])[0]['g'] ?? '');
cek(strpos($baru, 'assets/img/program/unggahan/') === 0,
    'Disimpan di direktori unggahan terpisah, bukan menimpa berkas bawaan');
cek(preg_match('#/rtlh-[a-f0-9]{16}\.png$#', $baru) === 1,
    'Nama berkas ACAK - nama kiriman tidak pernah menyentuh disk');
cek(is_file(APP_ROOT . '/' . $baru), 'Berkasnya benar-benar ada di disk');
if (is_file(APP_ROOT . '/' . $baru)) { $GLOBALS['bersih'][] = APP_ROOT . '/' . $baru; }

// Pulihkan supaya beranda kembali memakai foto bawaan.
q("UPDATE sf_programs SET gambar = ? WHERE id = ?", [$semula, $rtlh['id']]);
cek((string) (q("SELECT gambar g FROM sf_programs WHERE id = ?", [$rtlh['id']])[0]['g'] ?? '') === (string) $semula,
    'Keadaan dipulihkan - uji ini tidak meninggalkan jejak');

// ------------------------------------------------------------------ 4. Batas
echo "\n== 4. Yang TIDAK diserahkan ke formulir ==\n";
$ctrl = (string) @file_get_contents(APP_ROOT . '/application/controllers/Admin_Katalog_Program.php');
cek(preg_match("/post\('(warna|muda|tengah|pekat)/", $ctrl) === 0,
    'Warna tidak bisa diubah lewat formulir - paletnya disetel & kontrasnya diukur');
cek(preg_match("/post\('kode_program/", $ctrl) === 0,
    'Kode program tidak bisa diubah - ia kunci yang dipakai aturan kelayakan');
cek(strpos($komponen, '$palet') !== FALSE, 'Warna dipetakan di kode, dari kode program');

echo "\nRINGKASAN: {$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
