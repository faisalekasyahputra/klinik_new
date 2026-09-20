<?php
/**
 * Penjaga regresi keamanan komunikasi (form keamanan poin 8.2 dan 8.3).
 * Offline: tidak membuka koneksi jaringan. Jalankan:  php tests/transport_security_test.php
 * Pemeriksaan terhadap situs yang berjalan ada di docs/engineering/uji_tls_situs.php.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

define('BASEPATH', __DIR__ . '/../system/');
$app = realpath(__DIR__ . '/../application');
if ($app === false) { throw new RuntimeException('Application directory missing'); }
require $app . '/helpers/transport_helper.php';

$total = 0;
function check($condition, $message) {
    global $total;
    $total++;
    if (!$condition) { throw new RuntimeException($message); }
}
function source($relative) {
    global $app;
    $text = @file_get_contents($app . '/' . $relative);
    if ($text === false) { throw new RuntimeException("Tidak bisa membaca application/$relative"); }
    return $text;
}

// --- 1. Kebijakan tertulis ---------------------------------------------------
$policy = transport_policy();
check(($policy['transport_min_tls'] ?? '') === '1.2', 'TLS minimum harus 1.2');
check($policy['transport_allowed_tls'] === ['TLSv1.2', 'TLSv1.3'], 'Protokol yang diizinkan harus TLS 1.2 dan 1.3 saja');
foreach (['SSLv3', 'TLSv1.0', 'TLSv1.1'] as $old) {
    check(in_array($old, $policy['transport_forbidden_tls'], true), "$old harus tercantum terlarang");
}
foreach (['!aNULL', '!eNULL', '!MD5', '!RC4', '!3DES', '!DES', '!EXPORT'] as $ban) {
    check(strpos($policy['transport_db_ciphers'], $ban) !== false, "Cipher database harus melarang $ban");
}
check(strpos($policy['transport_db_ciphers'], 'ECDHE') === 0, 'Cipher database harus dimulai dari pertukaran kunci ECDHE');
check($policy['transport_hsts_min_age'] >= 15552000, 'Batas HSTS minimum tidak boleh di bawah 180 hari');

// --- 2. Opsi cURL baku -------------------------------------------------------
if (extension_loaded('curl')) {
    $o = transport_curl_options();
    check($o[CURLOPT_SSL_VERIFYPEER] === true, 'Verifikasi sertifikat harus menyala');
    check($o[CURLOPT_SSL_VERIFYHOST] === 2, 'Verifikasi nama host harus 2');
    check($o[CURLOPT_SSLVERSION] === CURL_SSLVERSION_TLSv1_2, 'TLS minimum cURL harus 1.2');
    check($o[CURLOPT_PROTOCOLS] === CURLPROTO_HTTPS, 'cURL hanya boleh HTTPS');
    check($o[CURLOPT_REDIR_PROTOCOLS] === CURLPROTO_HTTPS, 'Pengalihan cURL hanya boleh ke HTTPS');
} else {
    fwrite(STDERR, "peringatan: ekstensi curl tidak ada, bagian cURL dilewati\n");
}

$g = transport_guzzle_options();
check($g['verify'] === true, 'Guzzle harus memverifikasi sertifikat');
if (extension_loaded('curl')) {
    check(($g['curl'][CURLOPT_SSLVERSION] ?? null) === CURL_SSLVERSION_TLSv1_2, 'Guzzle harus memaksa TLS minimum 1.2');
}
check(strpos(source('libraries/Web_push_service.php'), 'transport_guzzle_options()') !== false, 'Web Push harus memakai transport_guzzle_options()');

// --- 3. Konteks stream -------------------------------------------------------
$ctx = stream_context_get_options(transport_stream_context());
check($ctx['ssl']['verify_peer'] === true && $ctx['ssl']['verify_peer_name'] === true, 'Stream https harus memverifikasi sertifikat dan nama');
check($ctx['ssl']['allow_self_signed'] === false, 'Stream https tidak boleh menerima sertifikat swa-tanda-tangan');
check(($ctx['ssl']['crypto_method'] & STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT) === STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT, 'Stream https harus mengizinkan TLS 1.2');
check(!defined('STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT') || ($ctx['ssl']['crypto_method'] & STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT) !== STREAM_CRYPTO_METHOD_TLSv1_0_CLIENT, 'Stream https tidak boleh mengizinkan TLS 1.0');
check(!defined('STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT') || ($ctx['ssl']['crypto_method'] & STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT) !== STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT, 'Stream https tidak boleh mengizinkan TLS 1.1');
check($ctx['http']['follow_location'] === 0, 'Stream https tidak boleh mengikuti pengalihan');
check($ctx['http']['timeout'] > 0, 'Stream https harus punya batas waktu');

// --- 4. Validasi URL ---------------------------------------------------------
foreach (['https://a.example/x', 'HTTPS://a.example'] as $ok) { check(transport_is_https_url($ok), "$ok harus diterima"); }
foreach (['http://a.example', 'ftp://a.example', '//a.example', 'https://', 'javascript:alert(1)', ''] as $bad) {
    check(!transport_is_https_url($bad), "'$bad' harus ditolak");
}

// --- 5. Mode enkripsi database ----------------------------------------------
foreach (['', '0', 'off', 'OFF ', 'false', 'no'] as $v) { check(transport_db_ssl_mode($v) === 'off', "DB_SSL='$v' harus off"); }
foreach (['on', '1', 'true', 'yes', 'salah-ketik'] as $v) { check(transport_db_ssl_mode($v) === 'on', "DB_SSL='$v' harus on (tidak boleh diam-diam mati)"); }
foreach (['verify', 'VERIFY', ' verify '] as $v) { check(transport_db_ssl_mode($v) === 'verify', "DB_SSL='$v' harus verify"); }
check(transport_db_encrypt('off') === false, 'Mode off tidak boleh mengaktifkan TLS');
$on = transport_db_encrypt('on');
check(is_array($on) && $on['ssl_verify'] === false && $on['ssl_cipher'] === $policy['transport_db_ciphers'], 'Mode on: TLS tanpa verifikasi dengan cipher kebijakan');
$vr = transport_db_encrypt('verify');
check(is_array($vr) && $vr['ssl_verify'] === true && $vr['ssl_cipher'] === $policy['transport_db_ciphers'], 'Mode verify: TLS dengan verifikasi dan cipher kebijakan');
putenv('DB_SSL_CA'); putenv('DB_HOST=alamat-db.contoh'); putenv('DB_SSL_HOSTNAME=nama-db.contoh');
check(transport_db_hostname('localhost', 'verify') === 'nama-db.contoh', 'Mode verify harus memakai DB_SSL_HOSTNAME');
check(transport_db_hostname('localhost', 'on') === 'alamat-db.contoh', 'Mode on tetap memakai DB_HOST');
check(transport_db_hostname('localhost', 'off') === 'alamat-db.contoh', 'Mode off tetap memakai DB_HOST');
putenv('DB_HOST'); putenv('DB_SSL_HOSTNAME');
check(transport_db_hostname('localhost', 'off') === 'localhost', 'Tanpa env, host bawaan dipakai');
putenv('DB_SSL_CA=/tmp/ca.pem');
check(transport_db_encrypt('verify')['ssl_ca'] === '/tmp/ca.pem', 'DB_SSL_CA harus diteruskan');
putenv('DB_SSL_CA');

// --- 6. Kode aplikasi tidak boleh melemahkan TLS ----------------------------
$violations = [];
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($app, FilesystemIterator::SKIP_DOTS));
$scanned = 0;
foreach ($files as $file) {
    if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') { continue; }
    $path = str_replace('\\', '/', $file->getPathname());
    if (strpos($path, '/application/logs/') !== false || strpos($path, '/application/cache/') !== false) { continue; }
    $rel = substr($path, strlen(str_replace('\\', '/', $app)) + 1);
    $code = file_get_contents($path);
    $scanned++;
    // Buang komentar supaya penjelasan "dulu false" tidak dihitung pelanggaran.
    $bare = '';
    foreach (token_get_all($code) as $t) {
        if (is_array($t)) { if (in_array($t[0], [T_COMMENT, T_DOC_COMMENT], true)) { $bare .= "\n"; continue; } $bare .= $t[1]; }
        else { $bare .= $t; }
    }
    if (preg_match('/CURLOPT_SSL_VERIFY(PEER|HOST)\s*(=>|,)\s*(false|0|1)\b/i', $bare)) { $violations[] = "$rel: verifikasi TLS cURL dimatikan/dilemahkan"; }
    if (preg_match('/[\'"](verify_peer|verify_peer_name)[\'"]\s*=>\s*(false|0)\b/i', $bare)) { $violations[] = "$rel: verify_peer(_name) dimatikan"; }
    if (preg_match('/[\'"]verify[\'"]\s*=>\s*(false|0)\b/i', $bare)) { $violations[] = "$rel: opsi verify=false (Guzzle)"; }
    if (preg_match('/[\'"]allow_self_signed[\'"]\s*=>\s*(true|1)\b/i', $bare)) { $violations[] = "$rel: allow_self_signed=true"; }
    // Hanya URL yang punya host; potongan seperti 'http://' pada str_replace() bukan panggilan keluar.
    if (preg_match('/[\'"]http:\/\/(?=[a-z0-9])(?!localhost|127\.0\.0\.1|www\.w3\.org)/i', $bare)) { $violations[] = "$rel: URL http:// (bukan https) pada kode"; }
    if ($rel !== 'helpers/transport_helper.php') {
        if (preg_match('/\bcurl_init\s*\(/', $bare) && strpos($bare, 'transport_curl_options(') === false) {
            $violations[] = "$rel: curl_init tanpa transport_curl_options()";
        }
        if (preg_match('/file_get_contents\s*\(\s*[\'"]https?:/', $bare) && strpos($bare, 'transport_stream_context(') === false) {
            $violations[] = "$rel: file_get_contents(https) tanpa transport_stream_context()";
        }
    }
}
check($scanned > 100, "Pemindaian terlalu sedikit berkas ($scanned) - jalur aplikasi salah?");
check($violations === [], "Pelanggaran keamanan komunikasi:\n  " . implode("\n  ", $violations));

// --- 7. Konfigurasi yang harus tetap ada -------------------------------------
$db = source('config/database.php');
check(strpos($db, "'encrypt' => transport_db_encrypt()") !== false, "database.php harus memakai transport_db_encrypt(), bukan 'encrypt' => FALSE tetap");
check(strpos($db, "transport_db_hostname(") !== false, 'database.php harus memakai transport_db_hostname()');
check(strpos(source('config/autoload.php'), "'transport'") !== false, "Helper 'transport' harus di-autoload");
check(preg_match('/\$config\[\'cookie_secure\'\]\s*=\s*\(ENVIRONMENT\s*===\s*\'production\'\)/', source('config/config.php')) === 1, 'cookie_secure harus menyala di production');
check(preg_match('/max-age=(\d+)/', source('core/MY_Controller.php'), $m) === 1 && (int) $m[1] >= $policy['transport_hsts_min_age'], 'HSTS max-age di MY_Controller di bawah batas kebijakan');
$ht = @file_get_contents($app . '/../.htaccess');
check($ht !== false && preg_match('/RewriteRule\s+\^\((?:[a-z-]+\|)*tests(?:\|[a-z-]+)*\)\//', $ht) === 1, '.htaccess harus memblokir direktori tests/ (skrip di dalamnya dapat dieksekusi lewat web)');

// --- 8. Algoritma yang dinyatakan kebijakan benar-benar dipakai kode ---------
check(strpos(source('libraries/Encryption_lib.php'), "'aes-256-gcm'") !== false, 'Encryption_lib harus memakai aes-256-gcm');
check(strpos(source('libraries/Encryption_lib.php'), "hash_hmac('sha256'") !== false, 'Encryption_lib harus memakai HMAC-SHA256 untuk hash pencarian');
check(strpos(source('controllers/Auth.php'), 'PASSWORD_BCRYPT') !== false, 'Auth harus meng-hash kata sandi dengan bcrypt');
$am = source('models/Auth_model.php');
check(strpos($am, "hash('sha256'") !== false && strpos($am, 'random_bytes(') !== false, 'Token sesi harus dari random_bytes() dan disimpan sebagai SHA-256');
check(strpos(source('core/MY_Log.php'), 'hash_hkdf(') !== false, 'MY_Log harus menurunkan kunci log dengan HKDF');
foreach (['kata_sandi', 'data_pribadi', 'turunan_kunci', 'pencarian_nik', 'token_sesi', 'angka_acak'] as $domain) {
    check(!empty($policy['crypto_algorithms'][$domain]), "Kebijakan algoritma untuk '$domain' hilang");
}
// Larangan: fungsi hash lemah tidak boleh dipakai untuk kata sandi/token.
check(!preg_match('/(md5|sha1)\s*\(\s*\$?(password|sandi|token)/i', source('controllers/Auth.php') . $am), 'md5/sha1 tidak boleh dipakai untuk kata sandi atau token');

echo "transport_security_test: OK ($total pemeriksaan, $scanned berkas dipindai)\n";
