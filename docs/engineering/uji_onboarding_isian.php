<?php
/**
 * Uji pengembalian isian onboarding saat validasi gagal.
 *
 *   php docs/engineering/uji_onboarding_isian.php
 *
 * Env opsional: UJI_BASE_URL
 *
 * Dulu tiap cabang validasi di `Auth::save_onboarding()` memanggil
 * set_flashdata('error') + redirect sendiri-sendiri, dan isian user hangus.
 * Sekarang semuanya lewat `Auth::_onboarding_fail()`. Dua hal yang diuji:
 *
 *   1. POSITIF - isian teks pulang bersama pesan errornya, termasuk peran yang
 *      tadi dipilih (kalau peran tidak ikut, blok isian yang benar tidak
 *      terbuka lagi dan user mengira isiannya tetap hilang).
 *   2. NEGATIF, dan ini intinya - kata sandi TIDAK BOLEH ikut pulang.
 *      _onboarding_fail() menumpang flashdata, yang tersimpan di session; kata
 *      sandi polos tidak boleh singgah di sana meski hanya satu permintaan,
 *      dan tentu saja tidak boleh terpantul ke HTML. Hapus dua baris `unset`
 *      di helper itu dan pemeriksaan sandi di bawah WAJIB merah.
 *
 * Skrip membuat akun sekali pakai sendiri dan menghapusnya di akhir.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('CAP', 'UJIOB' . date('His'));
define('EMAIL', 'uji.onboarding.' . date('His') . '@example.test');
define('SANDI', 'UjiOnboard#' . date('His'));
// Sandi yang DIKIRIM lewat formulir. Sengaja berbeda dari sandi akun supaya
// kalau ia muncul di HTML, tidak ada keraguan dari mana asalnya.
define('SANDI_UMPAN', 'JanganPantulkanAku#' . date('His'));
// Nilai yang dikirim DAN diperiksa harus dihitung sekali saja. Versi pertama
// menulis `'081200' . date('His')` di dua tempat - sekali saat menyusun POST,
// sekali saat menyusun daftar harapan - jadi skrip merah setiap kali detiknya
// kebetulan berganti di antara keduanya. Merahnya menuduh aplikasi untuk
// kesalahan alat ukurnya sendiri.
define('HP', '081200' . date('His'));
define('TELP_KANTOR', '024550' . date('is'));

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

$jar = tempnam(sys_get_temp_dir(), 'ujiob');

function bersihkan() {
    global $db, $jar;
    $db->query("DELETE FROM usr_users WHERE email = '" . $db->real_escape_string(EMAIL) . "'");
    if ($jar && file_exists($jar)) { @unlink($jar); }
}

function http($path, $post = NULL, $follow = TRUE) {
    global $jar;
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_COOKIEJAR      => $jar,
        CURLOPT_COOKIEFILE     => $jar,
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_TIMEOUT        => 30,
    ]);
    // Menandai diri AJAX supaya `Auth::do_login` membalas JSON, sama seperti
    // harness kemitraan. Tanpa ini balasannya redirect dan jejaknya berakhir
    // 403 di tempat yang tidak ada hubungannya dengan yang sedang diuji.
    // `save_onboarding` sendiri tidak punya cabang AJAX, jadi tetap memantul
    // ke formulir seperti di peramban.
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest']);
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

echo "\n== Uji pengembalian isian onboarding ==\n\n";

// ---------------------------------------------------------------- Persiapan

// Akun sekali pakai dengan profile_completed=0: satu-satunya keadaan yang
// membuat /Auth/onboarding merender formulir, bukan memantulkan ke dasbor.
$stmt = $db->prepare("INSERT INTO usr_users (name, email, password, role, profile_completed, status, email_verified_at, created_at)
                      VALUES (?, ?, ?, NULL, 0, 'active', NOW(), NOW())");
$hash = password_hash(SANDI, PASSWORD_BCRYPT);
$nama_awal = 'Uji Onboarding';
$email = EMAIL;
$stmt->bind_param('sss', $nama_awal, $email, $hash);
wajib($stmt->execute(), 'Akun uji (profile_completed=0) dibuat');
$stmt->close();

// JANGAN menamai token ini `$t` di scope global: pencacah pemeriksaan hidup di
// `$GLOBALS['t']`, yang merupakan variabel yang SAMA. Setiap cek() lalu
// menjalankan `$t++` pada string hash - "…8547" menjadi "…8548" - dan setiap
// POST berikutnya ditolak 403 oleh CSRF karena tokennya sudah bergeser satu
// karakter. Gejalanya menyesatkan: yang tampak rusak justru loginnya.
$csrf = token('Auth/login');
wajib($csrf !== '', 'Token CSRF halaman login terbaca');
$r = http('Auth/do_login', ['csrf_kpkp_token' => $csrf, 'email' => EMAIL, 'password' => SANDI]);
wajib($r['code'] === 200, "Login akun uji (kode {$r['code']})");

$r = http('Auth/onboarding');
wajib(strpos($r['body'], 'onboardingForm(') !== FALSE, 'Formulir onboarding tampil untuk akun ini');
$csrf = token('Auth/onboarding');
wajib($csrf !== '', 'Token CSRF formulir onboarding terbaca');

// ---------------------------------------------------------------- Kirim gagal

// NIK 15 digit - gagal di cabang paling belakang, SESUDAH semua isian lain
// diterima. Kalau isian pulang di sini, ia pulang di cabang mana pun.
$r = http('Auth/save_onboarding', [
    'csrf_kpkp_token'  => $csrf,
    'role'             => 'pengembang',
    'username'         => strtolower(CAP),
    'nama_lengkap'     => 'Nama ' . CAP,
    'nik_identitas'    => '123456789012345',
    'alamat_domisili'  => 'Alamat ' . CAP,
    'phone'            => HP,
    'nama_perusahaan'  => 'PT ' . CAP,
    'alamat_kantor'    => 'Kantor ' . CAP,
    'telp_kantor'      => TELP_KANTOR,
    'password'         => SANDI_UMPAN,
    'password_confirm' => SANDI_UMPAN,
]);
wajib($r['code'] === 200, "Kiriman ber-NIK cacat dipantulkan ke formulir (kode {$r['code']})");
$html = $r['body'];

cek(strpos($html, 'NIK harus terdiri dari 16 digit angka') !== FALSE, 'Pesan error NIK tersampaikan');

// Profilnya TIDAK boleh tersimpan - kalau tersimpan, halaman ini seharusnya
// tidak lagi bisa dirender, dan uji di atas berbohong.
$row = $db->query("SELECT profile_completed, role FROM usr_users WHERE email = '" . $db->real_escape_string(EMAIL) . "'")->fetch_assoc();
cek((int) $row['profile_completed'] === 0 && $row['role'] === NULL, 'Profil tidak tersimpan saat validasi gagal');

// ---------------------------------------------------------------- Isian pulang

foreach ([
    'username'        => strtolower(CAP),
    'nama_lengkap'    => 'Nama ' . CAP,
    'nik_identitas'   => '123456789012345',
    'phone'           => HP,
    'nama_perusahaan' => 'PT ' . CAP,
    'alamat_kantor'   => 'Kantor ' . CAP,
] as $medan => $nilai) {
    cek(strpos($html, $nilai) !== FALSE, "Isian `$medan` dikembalikan ke formulir");
}

// Alamat domisili ada di <textarea>, jadi bukan atribut value.
cek(strpos($html, 'Alamat ' . CAP . '</textarea>') !== FALSE, 'Isian `alamat_domisili` dikembalikan ke textarea');

// Peran DAN langkah ikut pulang lewat inisialisasi Alpine. Tanpa peran, blok
// isian yang benar tertutup lagi; tanpa langkah 2, user dipulangkan ke
// pemilihan peran untuk keputusan yang sudah ia buat.
cek(strpos($html, "onboardingForm('pengembang', 2)") !== FALSE, 'Peran dan langkah 2 dipulihkan');

// ---------------------------------------------------------------- Vendor

// Kartunya dicabut dari formulir, tapi tidak merender sesuatu bukan penjagaan -
// siapa pun bisa menembakkan role sendiri. Yang menentukan adalah $valid_roles
// di save_onboarding(). Cabang vendor menulis ke kolom yang tidak ada di
// usr_users, jadi menerimanya berarti error DB, bukan profil.
cek(strpos($html, 'value="vendor"') === FALSE, 'Kartu peran vendor tidak lagi ditawarkan');

$r = http('Auth/save_onboarding', [
    'csrf_kpkp_token' => token('Auth/onboarding'),
    'role'            => 'vendor',
    'username'        => strtolower(CAP) . 'v',
    'nama_lengkap'    => 'Nama ' . CAP,
    'nik_identitas'   => '1234567890123456',
    'alamat_domisili' => 'Alamat ' . CAP,
    'phone'           => HP,
]);
cek(strpos($r['body'], 'Pilih peran yang valid') !== FALSE, 'Server menolak role vendor yang ditembakkan langsung');

$row = $db->query("SELECT profile_completed, role FROM usr_users WHERE email = '" . $db->real_escape_string(EMAIL) . "'")->fetch_assoc();
cek((int) $row['profile_completed'] === 0 && $row['role'] === NULL, 'Tembakan vendor tidak menyisakan profil');

// ---------------------------------------------------------------- Sandi & jalan keluar

cek(strpos($html, SANDI_UMPAN) === FALSE, 'Kata sandi TIDAK dipantulkan ke HTML');

// Pemeriksaan yang sesungguhnya: flashdata menumpang session, jadi HTML yang
// bersih belum membuktikan apa-apa kalau sandinya mendarat di penyimpanan
// session. Instalasi ini memakai driver `files` (config.php: sess_driver), jadi
// yang ditengok berkas session - bukan tabel ci_sessions yang memang tidak ada.
$dir_sesi = ini_get('session.save_path') ?: sys_get_temp_dir();
$tercemar = [];
foreach (glob(rtrim($dir_sesi, '/\\') . '/ci_session*') ?: [] as $berkas) {
    if (strpos((string) @file_get_contents($berkas), SANDI_UMPAN) !== FALSE) {
        $tercemar[] = basename($berkas);
    }
}
cek($tercemar === [], 'Kata sandi TIDAK tersimpan di berkas session'
    . ($tercemar ? ' - ditemukan di: ' . implode(', ', $tercemar) : ''));

// Halaman ini satu-satunya halaman auth yang dulu tidak punya jalan keluar.
cek(strpos($html, 'Kembali ke Beranda') !== FALSE, 'Tautan kembali ke beranda tersedia');
cek(strpos($html, 'Auth/logout') !== FALSE, 'Tautan keluar dari akun tersedia');

// ---------------------------------------------------------------- Wizard

// `required` pada langkah yang sedang tersembunyi membuat submit gagal
// diam-diam ("invalid form control is not focusable"): error di console, tanpa
// pesan apa pun ke user. Karena itu tiap atribut wajib DIIKAT ke langkahnya.
foreach ([
    ':required="langkah === 2"'                       => 'isian langkah 2 terikat langkahnya',
    ":required=\"role === 'pengembang' && langkah === 3\"" => 'isian pengembang terikat langkah 3',
] as $pola => $label) {
    cek(strpos($html, $pola) !== FALSE, "Atribut wajib: $label");
}
cek(strpos($html, 'required autocomplete="new-password"') === FALSE, 'Tidak ada `required` telanjang tersisa di isian password');
cek(substr_count($html, 'x-ref="langkah') === 3, 'Ketiga langkah punya x-ref untuk validasi per langkah');

// Langkah-langkah itu div bersarang. Satu penutup yang salah tempat membuat
// langkah 3 bersarang DI DALAM langkah 2 - ia lalu ikut tersembunyi selamanya
// dan pengembang tidak pernah bisa mengunggah berkas. Peramban memperbaiki
// sarang rusak diam-diam, jadi hitung saja.
cek(substr_count($html, '<div') === substr_count($html, '</div>'), 'Jumlah pembuka dan penutup <div> seimbang');

bersihkan();
echo "\nRINGKASAN: {$GLOBALS['t']} pemeriksaan, {$GLOBALS['g']} gagal\n";
exit($GLOBALS['g'] > 0 ? 1 : 0);
