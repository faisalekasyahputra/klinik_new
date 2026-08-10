<?php
/**
 * Penjaga gerbang login - "kembali ke halaman asal" DAN pengamannya.
 *
 *   php docs/engineering/uji_gerbang_login.php
 *
 * Revisi dinas 5 Agt 2026 butir A5: "kalau usernya udah login, dibuat mbalik ke
 * menu awal dia aja, jadi jangan di lempar ke dashboard, soalnya bingung."
 *
 * Mesin pengingatnya sudah lama ada (`Auth::_redirect_after_login()` membaca
 * `intended_url`). Yang bolong: 21 gerbang memanggil `redirect('Auth/login')`
 * telanjang tanpa pernah mengisinya. Sekarang semuanya lewat satu pintu,
 * `MY_Controller::gerbang_login()`.
 *
 * SETENGAH BERKAS INI TENTANG KEAMANAN, BUKAN KENYAMANAN. "Simpan alamat lalu
 * arahkan ke sana sesudah login" adalah pola OPEN REDIRECT - korban mengeklik
 * tautan yang tampak sah, login sungguhan di situs kita, lalu terlempar ke
 * situs palsu dalam keadaan baru saja login. Uji T4-T6 yang menjaganya, dan
 * ketiganya harus dibuktikan bisa merah sebelum dianggap ada.
 *
 * CATATAN TEKNIS yang gampang menjebak penulis uji berikutnya: login lewat
 * AJAX membalas JSON dan TIDAK menyentuh `intended_url` sama sekali. Hanya
 * jalur non-AJAX yang memanggil `_redirect_after_login()`. Jadi uji di sini
 * sengaja TIDAK memakai header X-Requested-With - kalau dipakai, seluruh
 * berkas ini akan hijau tanpa pernah menguji apa pun.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('ENV_PATH', APP_ROOT . '/.env');
define('SANDI', 'UjiGerbang!2026');

$GLOBALS['uji_total'] = 0;
$GLOBALS['uji_gagal'] = 0;
$GLOBALS['users'] = [];
$GLOBALS['jar']   = [];

function cek($kondisi, $label) {
    $GLOBALS['uji_total']++;
    echo ($kondisi ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $kondisi) { $GLOBALS['uji_gagal']++; }
    return (bool) $kondisi;
}
function wajib($kondisi, $label) {
    if ( ! cek($kondisi, $label)) { bersihkan(); fwrite(STDERR, "Berhenti: prasyarat gagal.\n"); exit(1); }
}

function env_config($path) {
    $out = [];
    foreach (file($path, FILE_IGNORE_NEW_LINES) as $b) {
        $b = trim($b);
        if ($b === '' || $b[0] === '#' || strpos($b, '=') === FALSE) { continue; }
        [$k, $v] = explode('=', $b, 2);
        $k = trim($k);
        if ( ! isset($out[$k])) { $out[$k] = trim($v); }
    }
    return $out;
}
function q($sql, $p = []) {
    $st = $GLOBALS['db']->prepare($sql);
    if ( ! $st) { fwrite(STDERR, $GLOBALS['db']->error . "\n"); exit(1); }
    if ($p) { $st->bind_param(str_repeat('s', count($p)), ...$p); }
    $st->execute();
    $r = $st->get_result();
    $o = $r ? $r->fetch_assoc() : NULL;
    $id = $st->insert_id;
    $st->close();
    return $o ?: ['__id' => $id];
}
function tulis($sql, $p = []) { return (int) (q($sql, $p)['__id'] ?? 0); }

function sesi($n) {
    if ( ! isset($GLOBALS['jar'][$n])) { $GLOBALS['jar'][$n] = tempnam(sys_get_temp_dir(), 'ujg_'); }
    return $GLOBALS['jar'][$n];
}

/** Balikkan badan DAN alamat akhir - yang diuji berkas ini justru alamat akhirnya. */
function http($n, $path, ?array $post = NULL) {
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_COOKIEJAR      => sesi($n),
        CURLOPT_COOKIEFILE     => sesi($n),
        CURLOPT_FOLLOWLOCATION => TRUE,
        CURLOPT_TIMEOUT        => 30,
    ]);
    if ($post !== NULL) {
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $body = (string) curl_exec($ch);
    $akhir = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    return ['body' => $body, 'url' => $akhir];
}

/** Login jalur NON-AJAX - lihat catatan teknis di kepala berkas. */
function login($n, $email) {
    $b = http($n, 'Auth/login');
    $t = preg_match('/name="csrf_kpkp_token" value="([^"]+)"/', $b['body'], $m) ? $m[1] : '';
    return http($n, 'Auth/do_login', [
        'csrf_kpkp_token' => $t, 'email' => $email, 'password' => SANDI,
    ]);
}

function buat_akun($peran) {
    $email = 'uji_gerbang_' . time() . '_' . mt_rand(1000, 9999) . '@example.test';
    $id = tulis('INSERT INTO usr_users (email,password,name,username,role,status,profile_completed,created_at)
                 VALUES (?,?,?,?,?, "active",1,NOW())',
        [$email, password_hash(SANDI, PASSWORD_BCRYPT), 'Uji Gerbang',
         'uji_gerbang_' . mt_rand(10000, 99999), $peran]);
    $GLOBALS['users'][] = $id;
    return [$id, $email];
}

function bersihkan() {
    if ( ! empty($GLOBALS['db'])) {
        foreach ($GLOBALS['users'] as $id) { q('DELETE FROM usr_users WHERE id=?', [$id]); }
    }
    foreach ($GLOBALS['jar'] as $j) { @unlink($j); }
}
register_shutdown_function('bersihkan');

// ==========================================================================

echo "=== UJI GERBANG LOGIN ===\n";
if ( ! is_file(ENV_PATH)) { die(".env tidak ditemukan.\n"); }
$env = env_config(ENV_PATH);
$GLOBALS['db'] = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
if ($GLOBALS['db']->connect_error) { die("Koneksi DB gagal.\n"); }
echo "Target: " . BASE_URL . "\n\n";

/** Halaman ber-gerbang yang cuma menuntut login (bukan peran tertentu). */
const HALAMAN = 'Umum/papan_aduan';

[, $email] = buat_akun('mahasiswa');

// -------------------------------------------------------------- T1
$anon = http('t1', HALAMAN);
cek(stripos($anon['url'], 'auth/login') !== FALSE,
    'T1: anonim membuka halaman ber-gerbang -> mendarat di layar masuk');

// -------------------------------------------------------------- T2  INTI BUTIR A5
$masuk = login('t1', $email);
cek(stripos($masuk['url'], strtolower(HALAMAN)) !== FALSE,
    'T2: sesudah login KEMBALI ke halaman asal, bukan dashboard (dapat: ' . $masuk['url'] . ')');

// -------------------------------------------------------------- T3
$anon3 = http('t3', HALAMAN . '?hal=2&urut=lama');
cek(stripos($anon3['url'], 'auth/login') !== FALSE, 'T3a: halaman ber-query juga digerbang');
$masuk3 = login('t3', $email);
cek(strpos($masuk3['url'], 'hal=2') !== FALSE && strpos($masuk3['url'], 'urut=lama') !== FALSE,
    'T3b: penyaring di query string ikut kembali (dapat: ' . $masuk3['url'] . ')');

// -------------------------------------------------------------- T4-T6  KEAMANAN
/* CATATAN HASIL MUTASI 5 Agt 2026 - dibaca sebelum "memperbaiki" uji ini.
 *
 * Penyaringnya BERLAPIS DUA: saat disimpan (`Auth::login()` untuk `?next=`,
 * `MY_Controller::ingat_halaman_asal()` untuk yang diturunkan server) dan saat
 * dipakai (`Auth::_redirect_after_login()`).
 *
 * Mencabut SATU lapis saja TIDAK memerahkan T4-T6, dan itu bukan cacat ujinya
 * melainkan memang maksud berlapis: lapis yang tersisa masih menahan. Yang
 * dijaga ketiga uji ini adalah SIFAT SISTEMNYA - alamat luar tidak pernah jadi
 * tujuan - bukan keberadaan satu baris kode tertentu.
 *
 * Dicabut KEDUANYA, ketiganya merah, dan pendaratannya benar-benar
 * `https://jahat.example/curi`. Jadi ini bukan uji yang hijau selamanya:
 * ia merah tepat saat kerentanannya betul-betul terbuka.
 */
$jahat = [
    'T4' => 'https://jahat.example/curi',
    'T5' => '//jahat.example/curi',
    'T6' => 'http://jahat.example',
];
foreach ($jahat as $kode => $url) {
    $n = strtolower($kode);
    http($n, 'Auth/login?next=' . urlencode($url));
    $r = login($n, $email);
    cek(stripos($r['url'], 'jahat.example') === FALSE,
        "{$kode}: alamat luar `{$url}` DITOLAK, tidak pernah jadi tujuan (dapat: " . $r['url'] . ')');
}

// -------------------------------------------------------------- T7
/* POST ke halaman ber-gerbang tidak boleh diingat: mengembalikan orang ke URL
   POST sesudah login cuma menghasilkan galat atau tindakan terkirim dua kali.

   POST-nya HARUS membawa token CSRF yang sah. Tanpa itu permintaannya ditolak
   lapisan CSRF SEBELUM menyentuh gerbang, dan ujinya hijau tanpa pernah
   menguji apa pun - persis yang terjadi pada versi pertama berkas ini.
   Prasyaratnya diperiksa eksplisit di bawah supaya kegagalan seperti itu
   ketahuan sebagai MERAH, bukan menyamar jadi lulus. */
$awal7 = http('t7', 'Auth/login');
$tok7  = preg_match('/name="csrf_kpkp_token" value="([^"]+)"/', $awal7['body'], $m7) ? $m7[1] : '';
wajib($tok7 !== '', 'T7 prasyarat: token CSRF terbaca');
$post7 = http('t7', 'Cek_Rtlh/periksa', ['csrf_kpkp_token' => $tok7, 'nik' => '3374010101010001']);
wajib(stripos($post7['url'], 'auth/login') !== FALSE,
    'T7 prasyarat: POST anonim benar-benar SAMPAI ke gerbang (dapat: ' . $post7['url'] . ')');
$masuk7 = login('t7', $email);
cek(stripos($masuk7['url'], 'cek_rtlh') === FALSE,
    'T7: URL POST tidak diingat sebagai tujuan (dapat: ' . $masuk7['url'] . ')');

/* TITIK AMATAN untuk T8 & T9: membuka `Auth/login` SAAT SUDAH LOGIN memanggil
   `_redirect_after_login()`, yang memakai lalu menghapus `intended_url`. Jadi
   apa pun yang tersimpan langsung kelihatan sebagai tujuan pendaratan.
   Dipakai supaya keduanya tidak bergantung pada logout - logout menghancurkan
   sesi, sehingga uji yang lewat sana akan hijau tanpa membuktikan apa pun. */

// -------------------------------------------------------------- T8
/* Sesudah dipakai, ingatannya HARUS terhapus. Kalau tidak, kunjungan berikutnya
   melompat ke tempat lama tanpa sebab yang bisa dijelaskan user. Sesi 't1'
   sudah memakai tujuannya di T2 - sekarang ingatannya harus kosong. */
$lagi = http('t1', 'Auth/login');
cek(stripos($lagi['url'], strtolower(HALAMAN)) === FALSE,
    'T8: tujuan tidak terulang - ingatannya sekali pakai (dapat: ' . $lagi['url'] . ')');

// -------------------------------------------------------------- T9
/* Sudah login tapi salah peran: asalnya TIDAK boleh diingat. Kalau diingat,
   sesudah login ulang ia dilempar ke sana lagi lalu ditolak lagi - berputar. */
$masuk9 = login('t9', $email);                       // mahasiswa, tanpa terpental dulu
wajib(stripos($masuk9['url'], 'auth/login') === FALSE, 'T9 prasyarat: login mahasiswa berhasil');
$tolak9 = http('t9', 'Admin_Aduan');                 // layar superadmin -> ditolak
wajib(stripos($tolak9['url'], 'admin_aduan') === FALSE, 'T9 prasyarat: Admin_Aduan memang menolak mahasiswa');
$amat9 = http('t9', 'Auth/login');
cek(stripos($amat9['url'], 'admin_aduan') === FALSE,
    'T9: penolakan PERAN tidak diingat sebagai tujuan - tidak berputar (dapat: ' . $amat9['url'] . ')');

// -------------------------------------------------------------- Sumber
/* Penjaga bentuk: alamat tujuan HARUS diturunkan dari server, tidak pernah
   dari query string. Ini yang membedakan "mengingat halaman" dari open redirect. */
$src = (string) @file_get_contents(APP_ROOT . '/application/core/MY_Controller.php');
wajib($src !== '', 'Sumber MY_Controller.php terbaca');
preg_match('/function ingat_halaman_asal.*?\n    \}/s', $src, $mf);
$fn = $mf[0] ?? '';
cek($fn !== '', 'Sumber: ingat_halaman_asal() ketemu');
cek(strpos($fn, 'uri_string()') !== FALSE,
    'Sumber: tujuan diturunkan dari uri_string() (server), bukan dari request');
cek( ! preg_match('/input->get|\$_GET/', $fn),
    'Sumber: nol pembacaan query param sebagai TUJUAN - itu pintu open redirect');
cek(strpos($fn, 'sanitize_redirect') !== FALSE,
    'Sumber: tetap dilewatkan sanitize_redirect() meski sumbernya server');
cek(preg_match("/is_logged'\)\)\s*\{\s*\n\s*return/", $fn) === 1,
    'Sumber: yang SUDAH login tidak diingat asalnya - pencegah putaran');

echo "\nRINGKASAN: {$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
