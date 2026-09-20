<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Keep diagnostic events without persisting untrusted or personal text. */
class MY_Log extends CI_Log {
    private const LOG_AAD = 'klinik-pkp:log:v1';

    public function __construct() {
        parent::__construct();
        if (is_dir($this->_log_path)) {
            @chmod($this->_log_path, 0700);
        }
    }

    protected function _format_line($level, $date, $message) {
        $key = $this->log_key();
        if ($key === false) {
            return parent::_format_line($level, $date, 'LOG_KEY_UNAVAILABLE');
        }
        $event = 'application_error';
        if (preg_match('/\ASECURITY_WARNING\s+([A-Za-z0-9_]{1,64})/', (string) $message, $match)) {
            $event = $match[1];
        }
        $summary = json_encode([
            'event' => $event,
            'fingerprint' => hash_hmac('sha256', (string) $message, $key),
        ], JSON_UNESCAPED_SLASHES);
        $nonce = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($summary, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, self::LOG_AAD, 16);
        if ($ciphertext === false) {
            return parent::_format_line($level, $date, 'LOG_ENCRYPTION_FAILED');
        }
        return parent::_format_line($level, $date, 'enc:v1:' . base64_encode($nonce . $tag . $ciphertext));
    }

    private function log_key() {
        $hex = getenv('KPKP_DATA_KEY');
        if (!is_string($hex) || !preg_match('/\A[0-9a-fA-F]{64}\z/D', $hex)) {
            return false;
        }
        return hash_hkdf('sha256', hex2bin($hex), 32, self::LOG_AAD);
    }
}
