<?php
/**
 * Uji Cek Data Rumah, kontrak UAT No. 18 (10 Sep 2026).
 * Tamu mendapat status terdaftar/intervensi tanpa identitas, maksimal 5/jam/IP.
 * Pengguna login tetap dibatasi 10/jam dan 25/hari, dengan profil tersamar.
 * Menjaga CSRF, metode POST, pencatatan audit, dan profil pendataan tetap utuh.
 * php docs/engineering/uji_cek_rtlh.php
 * Hanya mode simulation: tidak memanggil API SIMPERUM sungguhan.
 */
define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('ENV_PATH', APP_ROOT . '/.env');
define('SANDI', 'UjiRtlh!2026');

define('NIK_ADA',    '0000000000000001');
define('TGL_ADA',    '1980-01-01');
define('NIK_KOSONG', '0000000000000098');

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
function tulis($sql, $p = []) { return (int) (q($sql, $p)['__id'] ?? 0); }
function nilai($sql, $p = []) { $r = q($sql, $p); return $r && ! isset($r['__id']) ? reset($r) : NULL; }

function sesi($nama) {
    if ( ! isset($GLOBALS['jar'][$nama])) { $GLOBALS['jar'][$nama] = tempnam(sys_get_temp_dir(), 'ujrt_'); }
    return $GLOBALS['jar'][$nama];
}

function http($nama, $path, ?array $post = NULL, $ajax = FALSE) {
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    $opt = [
        CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_COOKIEJAR => sesi($nama),
        CURLOPT_COOKIEFILE => sesi($nama), CURLOPT_FOLLOWLOCATION => TRUE, CURLOPT_TIMEOUT => 30,
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
    if (preg_match('/name="csrf_kpkp_token"\s+value="([^"]+)"/', $r['body'], $m)) { return $m[1]; }
    if (preg_match('/<meta\s+name="csrf-token-hash"\s+content="([^"]+)"/', $r['body'], $m)) { return $m[1]; }
    bersihkan();
    fwrite(STDERR, "FATAL: token CSRF tidak ditemukan di /{$path}.\n");
    exit(1);
}

function login($nama, $email) {
    $r = http($nama, 'Auth/do_login', [
        'csrf_kpkp_token' => csrf($nama, 'Auth/login'), 'email' => $email, 'password' => SANDI,
    ], TRUE);
    return (json_decode($r['body'], TRUE)['status'] ?? '') === 'success';
}

function buat_akun($suffix) {
    $email = 'uji_rtlh_' . $suffix . '_' . time() . '_' . mt_rand(1000, 9999) . '@example.test';
    $id = tulis(
        'INSERT INTO usr_users (email,password,name,username,role,status,profile_completed,created_at)
         VALUES (?,?,?,?,"user","active",1,NOW())',
        [$email, password_hash(SANDI, PASSWORD_BCRYPT), 'Uji RTLH ' . $suffix,
         'uji_rtlh_' . $suffix . '_' . mt_rand(10000, 99999)]
    );
    $GLOBALS['users'][] = $id;
    return [$id, $email];
}

/** Satu pencarian lewat formulir sungguhan. */
function periksa($sesi, $nik, $tgl) {
    return http($sesi, 'Cek_Rtlh/periksa', [
        'csrf_kpkp_token' => csrf($sesi, 'Cek_Rtlh'), 'nik' => $nik, 'tgl_lahir' => $tgl,
    ]);
}

function bersihkan() {
    if (empty($GLOBALS['db'])) { return; }
    foreach ($GLOBALS['users'] as $id) {
        q('DELETE FROM sf_profil_warga WHERE user_id=?', [$id]);
        q('DELETE FROM sys_jejak_audit WHERE actor_id=?', [$id]);
        q('DELETE FROM usr_users WHERE id=?', [$id]);
    }
    foreach (($GLOBALS['rate_anon_sebelum'] ?? []) as $key => $row) {
        q('DELETE FROM sys_rate_limits WHERE limit_key=?', [$key]);
        if (isset($row['limit_key'])) {
            q('INSERT INTO sys_rate_limits (limit_key,window_started_at,failed_attempts) VALUES (?,?,?)',
                [$key, $row['window_started_at'], $row['failed_attempts']]);
        }
    }
    $GLOBALS['rate_anon_sebelum'] = [];
    foreach ($GLOBALS['jar'] as $j) { @unlink($j); }
    $GLOBALS['users'] = [];
}
register_shutdown_function('bersihkan');

// ==========================================================================

if ( ! is_file(ENV_PATH)) { die(".env tidak ditemukan.\n"); }
$env = env_config(ENV_PATH);
$GLOBALS['db'] = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
if ($GLOBALS['db']->connect_error) { die("Koneksi DB gagal.\n"); }

define('MULAI', date('Y-m-d H:i:s', time() - 1));

echo "=== UJI CEK RTLH (SIMPERUM, mode simulasi) ===\n";
echo 'Target: ' . BASE_URL . " | DB: {$env['DB_NAME']}\n\n";

echo "== 0. Prasyarat ==\n";
$mode = trim((string) ($env['SIMPERUM_MODE'] ?? 'simulation'));
wajib($mode === 'simulation',
    "SIMPERUM_MODE=simulation (terbaca: {$mode}) - uji ini tidak boleh menyentuh API sungguhan");

// ------------------------------------------------ 1. HASIL UNTUK TAMU (UAT No. 18)
echo "\n== 1. Tamu melihat hasil terbatas tanpa login ==\n";
foreach (['127.0.0.1', '::1'] as $ip) {
    $key = hash('sha256', 'rtlh_cek_anon:ip:' . $ip);
    $GLOBALS['rate_anon_sebelum'][$key] = q('SELECT * FROM sys_rate_limits WHERE limit_key=?', [$key]);
    q('DELETE FROM sys_rate_limits WHERE limit_key=?', [$key]);
}
$tamu = http('tamu', 'Cek_Rtlh');
wajib($tamu['code'] === 200 && strpos($tamu['body'], 'Cek_Rtlh/periksa') !== FALSE,
    'Halaman tamu 200 dan formulir pencarian tersedia');
$r = periksa('tamu', NIK_ADA, '');
wajib($r['code'] === 200, 'POST tamu mendapat halaman hasil 200');
cek(strpos($r['body'], '****0001') !== FALSE && strpos($r['body'], 'TERDAFTAR') !== FALSE,
    'Tamu mendapatkan hasil terdaftar dengan NIK tersamar');
cek(strpos($r['body'], '>Intervensi</dt>') !== FALSE, 'Status intervensi ditampilkan kepada tamu');
cek(strpos($r['body'], '>Nama</dt>') === FALSE && strpos($r['body'], '>Alamat</dt>') === FALSE,
    'Hasil tamu tidak menyertakan nama atau alamat');
cek(strpos($r['body'], 'Masuk untuk melihat hasilnya') === FALSE, 'Hasil tamu tidak diganti ajakan login');
$tidak = periksa('tamu', NIK_KOSONG, '');
cek($tidak['code'] === 200 && strpos($tidak['body'], '****0098') !== FALSE && strpos($tidak['body'], 'tidak terdaftar') !== FALSE,
    'Tamu mendapatkan hasil tidak ditemukan');
$galat = periksa('tamu', '0000000000000099', '');
cek($galat['code'] === 200 && strpos($galat['body'], 'Pencarian belum berhasil') !== FALSE,
    'Galat sumber tidak disamarkan sebagai tidak terdaftar');
$invalid = periksa('tamu', '123', '');
cek($invalid['code'] === 200 && strpos($invalid['body'], 'NIK harus 16 digit') !== FALSE,
    'NIK tidak valid ditolak sebelum menghabiskan kuota pencarian');
periksa('tamu', NIK_ADA, '');
cek(periksa('tamu', NIK_ADA, '')['code'] === 200, 'Pencarian tamu ke-5 masih diizinkan');
cek(periksa('tamu', NIK_ADA, '')['code'] === 429, 'Pencarian tamu ke-6 ditolak 429');

[$id1, $email1] = buat_akun('utama');
wajib(login('u', $email1), 'Login pengguna uji');
$hal = http('u', 'Cek_Rtlh');
wajib($hal['code'] === 200 && strpos($hal['url'], 'Auth/login') === FALSE, 'Pengguna login bisa membuka Cek RTLH');
cek(strpos($hal['body'], 'Cek_Rtlh/periksa') !== FALSE, 'Formulirnya dirender');

// ------------------------------------------------ 2. HASIL
echo "\n== 2. Terdaftar / tidak terdaftar ==\n";
$ada = periksa('u', NIK_ADA, TGL_ADA);
cek(stripos($ada['body'], 'TERDAFTAR') !== FALSE, 'NIK yang ada dibaca TERDAFTAR');
cek(strpos($ada['body'], '****' . substr(NIK_ADA, -4)) !== FALSE,
    'NIK ditampilkan tersamar, hanya empat digit terakhir');
cek(stripos($ada['body'], 'MODE SIMULASI') !== FALSE,
    'Spanduk simulasi tampil - data contoh tidak boleh disangka nyata');

$kosong = periksa('u', NIK_KOSONG, TGL_ADA);
cek(stripos($kosong['body'], 'tidak terdaftar') !== FALSE, 'NIK tanpa data RTLH dibaca tidak terdaftar');
cek(stripos($kosong['body'], 'pendataan') !== FALSE,
    'Yang tidak terdaftar tetap diberi jalan lanjut, bukan jalan buntu');

// ------------------------------------- 3. TANGGAL LAHIR DICABUT, PENGGANTINYA
/* BUTIR 5 PUTARAN 2. Blok ini dulu menjaga pengaman tanggal lahir. Dinas
   memutuskan melepasnya dan user mengonfirmasi 11 Agt 2026, membalik keputusan
   5 Agt. Penjaganya TIDAK dihapus, melainkan DIBALIK: yang dijaga sekarang
   adalah bahwa penggantinya benar-benar terpasang.

   Kalau tidak dibalik, satu-satunya bekas keputusan ini adalah pesan commit,
   dan pengaman penggantinya bisa lenyap tanpa ada yang merah. */
echo "
== 3. Tanggal lahir dilepas, penggantinya terpasang ==
";

$tanpaTgl = periksa('u', NIK_ADA, '');
cek(stripos($tanpaTgl['body'], 'tidak cocok') === FALSE,
    'Pencarian tanpa tanggal lahir TIDAK lagi ditolak');

$halaman = http('u', 'Cek_Rtlh')['body'];
cek(stripos($halaman, 'name="tgl_lahir"') === FALSE,
    'Isian tanggal lahir sudah tidak ada di layar');

$kebijakan = (string) @file_get_contents(APP_ROOT . '/application/config/rate_limits.php');
cek(strpos($kebijakan, "'rtlh_cek'") !== FALSE, 'Batas per jam masih terdaftar');
cek(strpos($kebijakan, "'rtlh_cek_harian'") !== FALSE, 'Batas per HARI terdaftar sebagai penggantinya');

$ctrl = (string) @file_get_contents(APP_ROOT . '/application/controllers/Cek_Rtlh.php');
cek(strpos($ctrl, 'rtlh_cek_harian') !== FALSE,
    'Controller benar-benar memakai batas harian, bukan cuma mendeklarasikannya');
cek(strpos($ctrl, 'catat_audit') !== FALSE,
    'Tiap pencarian tetap dicatat, jejaknya bagian dari penggantinya');

/* Pengaman tanggal lahir HANYA dilepas di layar ini. Wizard pendataan warga
   memakai gateway yang sama, dan di sana ia tetap menjaga. */
$gw = (string) @file_get_contents(APP_ROOT . '/application/libraries/Simperum_gateway.php');
cek(strpos($gw, 'birth_date_matches') !== FALSE,
    'Pengaman tanggal lahir MASIH ADA di gateway, tidak dicabut untuk semua');
cek(strpos($gw, '$tanpa_tgl_lahir = FALSE') !== FALSE,
    'Pelepasannya harus diminta eksplisit, bawaannya tetap menjaga');

// ------------------------------------------------ 4. NOL EFEK SAMPING
echo "\n== 4. Cek cepat TIDAK menyentuh profil pendataan ==\n";
/**
 * Pemeriksaan terpenting di berkas ini, dan yang paling senyap kalau rusak.
 * `lookup()` dengan `$requested_by` terisi memanggil `save_profile()` -
 * mengecek NIK orang lain akan MENIMPA data pengajuan sendiri dengan data
 * orang itu, tanpa satu pun galat, dan baru ketahuan saat pengajuannya ditolak.
 */
cek((int) nilai('SELECT COUNT(*) c FROM sf_profil_warga WHERE user_id=?', [$id1]) === 0,
    'Empat pencarian tadi TIDAK membuat/menyentuh baris sf_profil_warga milik pengguna');

// ------------------------------------------------ 5. DATA MINIM
echo "\n== 5. Yang ditampilkan sedikit ==\n";
foreach (['Desil', 'desil', 'Penghasilan', 'penghasilan', 'income_band'] as $bocor) {
    cek(strpos($ada['body'], $bocor) === FALSE, "Kolom '{$bocor}' tidak ikut ditampilkan");
}

/**
 * NIK LENGKAP boleh muncul TEPAT SEKALI, dan hanya sebagai `value` input.
 *
 * Ditemukan lewat browser, bukan lewat harness ini - versi pertama uji ini
 * tidak memeriksanya sama sekali. Itu ISIAN ORANG ITU SENDIRI, dikembalikan
 * supaya ia tidak mengetik ulang 16 digit setelah salah ketik tanggal, dan
 * halamannya ber-gerbang login; jadi bukan kebocoran. Tapi kartu HASIL-nya
 * sengaja menyamarkan jadi `****0001`, dan tanpa patokan ini tidak ada yang
 * menahan seseorang kelak mencetak NIK penuh di sana juga - dua tempat, satu
 * disamarkan satu tidak, dan yang kedua tidak akan terlihat salah.
 */
$muncul = substr_count($ada['body'], NIK_ADA);
cek($muncul === 1, 'NIK lengkap muncul tepat sekali di halaman (ditemukan: ' . $muncul . ')');
cek((bool) preg_match('/name="nik"[^>]*value="' . NIK_ADA . '"/', $ada['body']),
    'Dan satu-satunya kemunculan itu adalah value input NIK, bukan kartu hasil');

// ------------------------------------------------ 6. METODE & CSRF
echo "\n== 6. GET dan POST tanpa token ==\n";
cek(http('u', 'Cek_Rtlh/periksa?nik=' . NIK_ADA . '&tgl_lahir=' . TGL_ADA)['code'] === 404,
    'GET ke endpoint pencarian dibalas 404');
$tanpa = http('u', 'Cek_Rtlh/periksa', ['nik' => NIK_ADA, 'tgl_lahir' => TGL_ADA]);
cek(stripos($tanpa['body'], 'MODE SIMULASI') === FALSE,
    'POST tanpa token CSRF tidak menghasilkan hasil');

// ------------------------------------------------ 7. JEJAK AUDIT
echo "\n== 7. Tercatat di jejak audit ==\n";
cek((int) nilai("SELECT COUNT(*) c FROM sys_jejak_audit WHERE aksi='rtlh_dicek' AND actor_id=?", [$id1]) >= 3,
    'Tiap pencarian tercatat (minimal 3 baris untuk pengguna ini)');
cek((int) nilai("SELECT COUNT(*) c FROM sys_jejak_audit WHERE aksi='rtlh_dicek' AND actor_id=? AND ringkasan LIKE ?",
    [$id1, '%' . NIK_ADA . '%']) === 0,
    'NIK LENGKAP tidak ikut tertulis di jejak audit - hanya empat digit terakhir');

// ------------------------------------------------ 8. BATAS LAJU
echo "\n== 8. Batas laju 10/jam per akun ==\n";
// Akun BARU: jatah pengguna pertama sudah terpakai sebagian di atas, dan uji
// yang menghitung sisa jatah orang lain akan berubah tiap kali bagian atas diedit.
[$id2, $email2] = buat_akun('laju');
wajib(login('l', $email2), 'Login akun kedua (jatah penuh)');
$kode_terakhir = 0;
for ($i = 1; $i <= 11; $i++) {
    $kode_terakhir = periksa('l', NIK_ADA, TGL_ADA)['code'];
    if ($i === 10) { cek($kode_terakhir === 200, 'Pencarian ke-10 masih dalam jatah'); }
}
cek($kode_terakhir === 429, 'Pencarian ke-11 ditolak 429, bukan 200 yang menyamar');

// ------------------------------------------------ 9. TAUTAN DARI HUB
echo "\n== 9. Pintu masuk dari hub Nggolek Omah ==\n";
$hub = http('u', 'golek_omah');

/**
 * Dihitung dari ATRIBUT href, bukan dengan menyapu seluruh halaman.
 *
 * Versi pertama memakai `substr_count($body, 'warga/pendataan') === 1` dan
 * merah untuk hub yang sebenarnya sudah benar: frasa itu juga muncul di
 * komentar HTML yang baru saya tulis sendiri ("SEBELUMNYA kartu ini menunjuk
 * warga/pendataan") dan di satu komentar JavaScript di footer. Tiga kemunculan,
 * satu tautan. Ini ketiga kalinya jebakan yang sama terinjak di sesi ini -
 * uji yang mengukur "dua kata ini ada di suatu tempat" akan selalu berbeda dari
 * uji yang mengukur "tautan ini ada berapa".
 */
// href diambil UTUH lalu awalan base_url dipotong - bukan dipungut lewat satu
// regex "pintar". Percobaan pertama memakai `[^"]*\/(...)` yang serakah sampai
// garis miring TERAKHIR, jadi `/warga/pendataan` terbaca `pendataan` dan
// hitungannya nol untuk halaman yang benar.
preg_match_all('/href="([^"]+)"/', $hub['body'], $hm);
$tujuan = array_count_values(array_map(
    static function ($h) { return ltrim(str_replace(BASE_URL, '', $h), '/'); },
    $hm[1]
));
cek(($tujuan['Cek_Rtlh'] ?? 0) >= 1, 'Hub memuat kartu menuju Cek RTLH');
// Kartu 3 & 4 dulu menunjuk tujuan yang sama: empat kartu, tiga tujuan.
cek(($tujuan['warga/pendataan'] ?? 0) === 1,
    'Tautan warga/pendataan tinggal SATU - kartu duplikatnya sudah diganti (ditemukan: '
    . ($tujuan['warga/pendataan'] ?? 0) . ')');

echo "\nRINGKASAN: {$GLOBALS['uji_total']} pemeriksaan, {$GLOBALS['uji_gagal']} gagal\n";
exit($GLOBALS['uji_gagal'] > 0 ? 1 : 0);
