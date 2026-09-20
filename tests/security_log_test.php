<?php
define('BASEPATH', __DIR__ . '/../system/');
require BASEPATH . 'core/Log.php';
require __DIR__ . '/../application/core/MY_Log.php';

class Test_Security_Log extends MY_Log {
    public function __construct() {}
    public function render($message) {
        return $this->_format_line('ERROR', '2026-09-20 00:00:00', $message);
    }
}

function check($condition, $message) {
    if (!$condition) { throw new RuntimeException($message); }
}

$logger = new Test_Security_Log();
putenv('KPKP_DATA_KEY=' . str_repeat('ab', 32));
$input = "SECURITY_WARNING login_failed nik=3321110912700002\nERROR - fake entry";
$line = $logger->render($input);
check(substr_count($line, "\n") === 1, 'Log injection created a second line');
check(strpos($line, '3321110912700002') === false, 'NIK leaked to disk');
check(strpos($line, 'fake entry') === false, 'Untrusted message leaked to disk');
check(preg_match('/enc:v1:([A-Za-z0-9+\/=]+)/', $line, $matches) === 1, 'Encrypted payload absent');

$raw = base64_decode($matches[1], true);
$key = hash_hkdf('sha256', hex2bin(str_repeat('ab', 32)), 32, 'klinik-pkp:log:v1');
$decoded = openssl_decrypt(substr($raw, 28), 'aes-256-gcm', $key, OPENSSL_RAW_DATA,
    substr($raw, 0, 12), substr($raw, 12, 16), 'klinik-pkp:log:v1');
$event = json_decode($decoded, true);
check($event['event'] === 'login_failed', 'Security event type lost');
check(isset($event['fingerprint']), 'Diagnostic fingerprint absent');
check(strpos($decoded, '3321110912700002') === false, 'PII persisted inside ciphertext');

putenv('KPKP_DATA_KEY=');
$fallback = $logger->render($input);
check(strpos($fallback, 'LOG_KEY_UNAVAILABLE') !== false, 'Missing key did not fail closed');
check(strpos($fallback, '3321110912700002') === false, 'Missing key leaked PII');
echo "security_log_test: OK\n";
