<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Encryption_lib — Library Enkripsi Data Pribadi (PII)
 * 
 * Mengimplementasikan enkripsi AES-256-GCM untuk kepatuhan UU PDP No. 27/2022.
 * Digunakan untuk mengenkripsi kolom sensitif (NIK, Alamat) di database.
 * 
 * Algoritma: AES-256-GCM (Galois/Counter Mode)
 * - Authenticated encryption (menjamin integritas + kerahasiaan)
 * - Random IV per operasi enkripsi (mencegah pattern analysis)
 * - Deterministic hash untuk pencarian tanpa dekripsi
 * 
 * Kunci enkripsi disimpan di file .env (KPKP_DATA_KEY & KPKP_DATA_PEPPER)
 * 
 * @package     KlinikPKP
 * @subpackage  Libraries
 */
class Encryption_lib {

    /**
     * Kunci enkripsi AES-256 (32 bytes dari hex)
     * @var string
     */
    private $key;

    /**
     * Pepper untuk deterministic hashing
     * @var string
     */
    private $pepper;

    /**
     * Versi format enkripsi (untuk forward compatibility)
     * @var string
     */
    private $version = 'v1';

    /**
     * Cipher method
     * @var string
     */
    private $cipher = 'aes-256-gcm';

    /**
     * Constructor — memuat kunci dari environment variables
     */
    public function __construct() {
        $this->key    = hex2bin(getenv('KPKP_DATA_KEY'));
        $this->pepper = getenv('KPKP_DATA_PEPPER');

        if (empty($this->key) || strlen($this->key) !== 32) {
            log_message('error', 'Encryption_lib: KPKP_DATA_KEY tidak ditemukan atau tidak valid di .env');
        }
        if (empty($this->pepper)) {
            log_message('error', 'Encryption_lib: KPKP_DATA_PEPPER tidak ditemukan di .env');
        }
    }

    /**
     * Enkripsi data plaintext menggunakan AES-256-GCM
     * 
     * Format output: base64( VERSION(2) | IV(12) | TAG(16) | CIPHERTEXT )
     * 
     * @param string $plaintext Data yang akan dienkripsi
     * @return string|false Ciphertext dalam format base64, atau false jika gagal
     */
    public function encrypt($plaintext) {
        if (empty($plaintext) || empty($this->key)) {
            return $plaintext; // Kembalikan apa adanya jika kosong atau kunci tidak ada
        }

        // Generate random 12-byte IV (96 bit — standar GCM)
        $iv = random_bytes(12);
        
        // Tag autentikasi (16 bytes output)
        $tag = '';
        
        // Enkripsi dengan AES-256-GCM
        $ciphertext = openssl_encrypt(
            $plaintext,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            'kpkp:' . $this->version, // AAD (Additional Authenticated Data)
            16                         // Tag length
        );

        if ($ciphertext === false) {
            log_message('error', 'Encryption_lib::encrypt() gagal: ' . openssl_error_string());
            return false;
        }

        // Pack: version(2) + IV(12) + tag(16) + ciphertext
        $packed = $this->version . $iv . $tag . $ciphertext;

        return base64_encode($packed);
    }

    /**
     * Dekripsi data dari format terenkripsi kembali ke plaintext
     * 
     * @param string $encoded Data terenkripsi dalam format base64
     * @return string|false Plaintext asli, atau false jika gagal/data corrupt
     */
    public function decrypt($encoded) {
        if (empty($encoded) || empty($this->key)) {
            return $encoded;
        }

        // Cek apakah data ini memang terenkripsi (base64 valid dan prefix 'v1')
        $decoded = base64_decode($encoded, true);
        if ($decoded === false || strlen($decoded) < 30) {
            // Data bukan format terenkripsi — kembalikan apa adanya (plaintext lama)
            return $encoded;
        }

        // Unpack: version(2) + IV(12) + tag(16) + ciphertext
        $version    = substr($decoded, 0, 2);
        $iv         = substr($decoded, 2, 12);
        $tag        = substr($decoded, 14, 16);
        $ciphertext = substr($decoded, 30);

        if ($version !== 'v1') {
            log_message('error', 'Encryption_lib::decrypt() versi tidak dikenal: ' . $version);
            return false;
        }

        // Dekripsi
        $plaintext = openssl_decrypt(
            $ciphertext,
            $this->cipher,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            'kpkp:' . $version // AAD harus sama persis dengan saat enkripsi
        );

        if ($plaintext === false) {
            log_message('error', 'Encryption_lib::decrypt() gagal — data mungkin corrupt atau kunci salah');
            // Kemungkinan data plaintext lama — kembalikan apa adanya
            return $encoded;
        }

        return $plaintext;
    }

    /**
     * Buat deterministic hash dari plaintext untuk pencarian database
     * 
     * Menggunakan HMAC-SHA256 dengan pepper sehingga:
     * - Hash yang sama selalu dihasilkan untuk plaintext yang sama (deterministic)
     * - Tidak bisa di-reverse tanpa mengetahui pepper
     * - Bisa digunakan untuk WHERE clause: WHERE nik_lookup_hash = ?
     * 
     * @param string $plaintext Data yang akan di-hash (misal: NIK)
     * @return string Hash hex 64 karakter
     */
    public function deterministic_hash($plaintext) {
        if (empty($plaintext)) {
            return '';
        }
        return hash_hmac('sha256', $plaintext, $this->pepper);
    }

    /**
     * Cek apakah sebuah string sudah dalam format terenkripsi
     * 
     * @param string $data Data yang akan dicek
     * @return bool True jika data sudah terenkripsi
     */
    public function is_encrypted($data) {
        if (empty($data)) {
            return false;
        }
        $decoded = base64_decode($data, true);
        if ($decoded === false || strlen($decoded) < 30) {
            return false;
        }
        return substr($decoded, 0, 2) === 'v1';
    }
}
