<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** AES-256-GCM untuk data pribadi; v1 legacy dan keyring bernomor v2. */
class Encryption_lib {
    private $legacy_key;
    private $keyring = [];
    private $active_key_id = '';
    private $keyring_error = false;
    private $pepper;
    private $cipher = 'aes-256-gcm';

    public function __construct() {
        $legacy_hex = getenv('KPKP_DATA_KEY');
        $this->legacy_key = self::decode_key($legacy_hex);
        $this->pepper = getenv('KPKP_DATA_PEPPER');

        $json = getenv('KPKP_DATA_KEYS');
        $active = getenv('KPKP_ACTIVE_KEY_ID');
        if (($json !== false && $json !== '') || ($active !== false && $active !== '')) {
            $entries = is_string($json) ? json_decode($json, true) : null;
            if (!is_array($entries) || !$entries || !is_string($active) || !self::valid_id($active)) {
                $this->keyring_error = true;
            } else {
                foreach ($entries as $id => $hex) {
                    $key = self::valid_id($id) ? self::decode_key($hex) : false;
                    if ($key === false) {
                        $this->keyring_error = true;
                        break;
                    }
                    $this->keyring[$id] = $key;
                }
                if (!isset($this->keyring[$active])) {
                    $this->keyring_error = true;
                } else {
                    $this->active_key_id = $active;
                }
            }
            if ($this->keyring_error) {
                log_message('error', 'Encryption_lib: konfigurasi keyring tidak valid');
            }
        }
        if ($this->legacy_key === false && !$this->keyring) {
            log_message('error', 'Encryption_lib: kunci enkripsi tidak tersedia');
        }
        if (empty($this->pepper)) {
            log_message('error', 'Encryption_lib: KPKP_DATA_PEPPER tidak ditemukan');
        }
    }

    public function __destruct() {
        require_once APPPATH . 'libraries/Sensitive_buffer.php';
        $buffer = new Sensitive_buffer();
        $buffer->wipe($this->legacy_key);
        $buffer->wipe($this->keyring);
        $buffer->wipe($this->pepper);
    }

    private static function decode_key($hex) {
        return is_string($hex) && preg_match('/\A[0-9a-fA-F]{64}\z/', $hex) ? hex2bin($hex) : false;
    }

    private static function valid_id($id) {
        return is_string($id) && (bool) preg_match('/\A[A-Za-z0-9_-]{1,16}\z/', $id);
    }

    private function check_config() {
        if ($this->keyring_error) {
            throw new RuntimeException('Encryption_lib: konfigurasi keyring tidak valid.');
        }
    }

    public function encrypt($plaintext) {
        if (empty($plaintext)) { return $plaintext; }
        $this->check_config();
        $v2 = $this->active_key_id !== '';
        $key = $v2 ? $this->keyring[$this->active_key_id] : $this->legacy_key;
        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException('Encryption_lib: kunci enkripsi hilang atau tidak valid; penulisan dibatalkan.');
        }
        $iv = random_bytes(12);
        $tag = '';
        $aad = $v2 ? 'kpkp:v2:' . $this->active_key_id : 'kpkp:v1';
        $ciphertext = openssl_encrypt($plaintext, $this->cipher, $key, OPENSSL_RAW_DATA, $iv, $tag, $aad, 16);
        if ($ciphertext === false) {
            log_message('error', 'Encryption_lib: OpenSSL gagal mengenkripsi');
            throw new RuntimeException('Encryption_lib: enkripsi gagal.');
        }
        $header = $v2 ? 'v2' . chr(strlen($this->active_key_id)) . $this->active_key_id : 'v1';
        return base64_encode($header . $iv . $tag . $ciphertext);
    }

    public function decrypt($encoded) {
        if (empty($encoded)) { return $encoded; }
        $this->check_config();
        $decoded = base64_decode($encoded, true);
        if ($decoded === false || strlen($decoded) < 2) { return $encoded; }
        $version = substr($decoded, 0, 2);
        if ($version !== 'v1' && $version !== 'v2') { return $encoded; }

        if ($version === 'v1') {
            if (strlen($decoded) < 30) { return false; }
            $key = $this->legacy_key;
            $offset = 2;
            $aad = 'kpkp:v1';
            if ($key === false) {
                throw new RuntimeException('Encryption_lib: kunci legacy tidak tersedia.');
            }
        } else {
            if (strlen($decoded) < 32) { return false; }
            $length = ord($decoded[2]);
            $id = substr($decoded, 3, $length);
            if ($length < 1 || $length > 16 || strlen($decoded) < 31 + $length || !self::valid_id($id)) {
                return false;
            }
            if (!isset($this->keyring[$id])) {
                throw new RuntimeException('Encryption_lib: ID kunci ciphertext tidak tersedia.');
            }
            $key = $this->keyring[$id];
            $offset = 3 + $length;
            $aad = 'kpkp:v2:' . $id;
        }

        $plaintext = openssl_decrypt(substr($decoded, $offset + 28), $this->cipher, $key,
            OPENSSL_RAW_DATA, substr($decoded, $offset, 12), substr($decoded, $offset + 12, 16), $aad);
        if ($plaintext === false) {
            log_message('error', 'Encryption_lib: ciphertext gagal autentikasi atau kunci salah');
            return false;
        }
        return $plaintext;
    }

    public function deterministic_hash($plaintext) {
        if (empty($plaintext)) { return ''; }
        if (empty($this->pepper)) {
            throw new RuntimeException('Encryption_lib: KPKP_DATA_PEPPER hilang; hash pencarian dibatalkan.');
        }
        return hash_hmac('sha256', $plaintext, $this->pepper);
    }

    public function is_encrypted($data) {
        if (empty($data)) { return false; }
        $decoded = base64_decode($data, true);
        if ($decoded === false || strlen($decoded) < 30) { return false; }
        $version = substr($decoded, 0, 2);
        if ($version === 'v1') { return true; }
        if ($version !== 'v2') { return false; }
        $length = ord($decoded[2]);
        return $length >= 1 && $length <= 16 && strlen($decoded) >= 31 + $length
            && self::valid_id(substr($decoded, 3, $length));
    }

    /** Rotasi per nilai; hasil harus disimpan oleh pemanggil dalam transaksi. */
    public function reencrypt($encoded) {
        if ($this->active_key_id === '') {
            throw new RuntimeException('Encryption_lib: keyring aktif wajib untuk rotasi.');
        }
        if (!$this->is_encrypted($encoded)) {
            throw new InvalidArgumentException('Encryption_lib: hanya ciphertext yang dapat dirotasi.');
        }
        $plaintext = $this->decrypt($encoded);
        if ($plaintext === false) {
            throw new RuntimeException('Encryption_lib: ciphertext gagal didekripsi; rotasi dibatalkan.');
        }
        try {
            return $this->encrypt($plaintext);
        } finally {
            require_once APPPATH . 'libraries/Sensitive_buffer.php';
            $buffer = new Sensitive_buffer();
            $buffer->wipe($plaintext);
        }
    }
}
