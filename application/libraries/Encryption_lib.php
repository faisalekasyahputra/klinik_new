<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Encryption_lib - Library Enkripsi Data Pribadi (PII)
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
     * Constructor - memuat kunci dari environment variables
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
    /**
     * B9 - FAIL-CLOSED. Kunci hilang MELEMPAR, bukan mengembalikan plaintext.
     *
     * Kontrak lama menggabungkan dua keadaan yang sama sekali berbeda ke dalam
     * satu `return $plaintext`: "tidak ada yang perlu dienkripsi" dan "kunci
     * enkripsinya hilang". Pemanggil tidak punya cara membedakannya, sehingga
     * NIK dan alamat warga tersimpan APA ADANYA ke kolom bernama
     * `*_ciphertext` - dan tidak ada satu pun yang tahu. Bukan skenario
     * hipotetis: log lokal memuat kejadian nyata "KPKP_DATA_KEY tidak
     * ditemukan", dan constructor hanya menulis log lalu melanjutkan.
     *
     * Plaintext kosong tetap boleh lewat - memang tidak ada isinya.
     *
     * @throws RuntimeException bila kunci hilang/tidak valid, atau OpenSSL gagal.
     */
    public function encrypt($plaintext) {
        if (empty($plaintext)) {
            return $plaintext;
        }
        if (empty($this->key) || strlen($this->key) !== 32) {
            throw new RuntimeException(
                'Encryption_lib: KPKP_DATA_KEY hilang atau tidak valid - penulisan dibatalkan '
                . 'agar data pribadi tidak tersimpan sebagai plaintext.');
        }

        // Generate random 12-byte IV (96 bit - standar GCM)
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
            // Dulu `return false`. Nilai itu tersimpan ke kolom sebagai string
            // kosong dan kegagalannya tidak pernah terlihat - kolom ciphertext
            // berisi '' terbaca seperti "memang tidak diisi".
            $galat = openssl_error_string();
            log_message('error', 'Encryption_lib::encrypt() gagal: ' . $galat);
            throw new RuntimeException('Encryption_lib: enkripsi gagal (' . $galat . ').');
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
        if (empty($encoded)) {
            return $encoded;
        }
        // B9 - kunci hilang MELEMPAR. Kontrak lama mengembalikan `$encoded`
        // apa adanya, sehingga ciphertext base64 tersaji ke layar seolah itu
        // NIK atau alamat aslinya, dan pemanggil tidak punya cara tahu.
        if (empty($this->key) || strlen($this->key) !== 32) {
            throw new RuntimeException(
                'Encryption_lib: KPKP_DATA_KEY hilang atau tidak valid - pembacaan dibatalkan.');
        }

        // Kompatibilitas plaintext LEGACY sengaja dipertahankan di sini, dan
        // hanya di sini: baris lama yang ditulis sebelum enkripsi menyala
        // memang berisi teks biasa. Bedanya dengan kontrak lama, cabang ini
        // kini hanya tercapai bila kuncinya ADA - jadi "tidak bisa didekripsi
        // karena kunci hilang" tidak lagi menyamar sebagai "ini plaintext lama".
        $decoded = base64_decode($encoded, true);
        if ($decoded === false || strlen($decoded) < 30) {
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
            log_message('error', 'Encryption_lib::decrypt() gagal - data mungkin corrupt atau kunci salah');
            // Kemungkinan data plaintext lama - kembalikan apa adanya
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
        // B9 - pepper kosong MELEMPAR. `hash_hmac()` dengan secret kosong tetap
        // menghasilkan 64 hex yang terlihat meyakinkan dan deterministik, tapi
        // siapa pun bisa menghitungnya tanpa tahu apa pun: lookup hash NIK jadi
        // bisa dibalik dengan brute force 16 digit. Itu fail-open yang paling
        // sulit terlihat, karena keluarannya tidak berbeda dari yang benar.
        if (empty($this->pepper)) {
            throw new RuntimeException(
                'Encryption_lib: KPKP_DATA_PEPPER hilang - hash pencarian dibatalkan '
                . 'agar tidak lahir hash yang bisa dihitung siapa saja.');
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
