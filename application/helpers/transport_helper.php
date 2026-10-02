<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Keamanan koneksi KELUAR (form keamanan poin 8.2 dan 8.3).
 *
 * Satu tempat untuk syarat transport semua panggilan dari aplikasi ke luar:
 * layanan HTTP (cURL dan stream https) dan koneksi ke server database.
 * Kebijakannya ada di config/transport_security.php. Aturan pakainya:
 *
 *   curl_setopt_array($ch, [ ...opsi khas panggilan ini... ]);
 *   curl_setopt_array($ch, transport_curl_options());   // TERAKHIR
 *
 * Opsi kebijakan dipasang PALING AKHIR supaya opsi lain tidak bisa
 * melemahkannya (dulu satu panggilan mematikan verifikasi sertifikat dan satu
 * lagi menimpa VERIFYHOST=2 dengan false; keduanya lolos karena tiap
 * panggilan mengatur TLS sendiri-sendiri). tests/transport_security_test.php
 * menggagalkan build bila ada CURLOPT_SSL_VERIFY* yang dimatikan lagi.
 */

if ( ! function_exists('transport_policy')) {
    /**
     * Baca kebijakan. Dibaca langsung dari berkas config supaya bisa dipakai
     * juga oleh tes dan alat CLI yang tidak memuat CodeIgniter.
     */
    function transport_policy($key = NULL, $default = NULL)
    {
        static $policy = NULL;
        if ($policy === NULL) {
            $config = [];
            require dirname(__DIR__) . '/config/transport_security.php';
            $policy = $config;
        }
        return $key === NULL ? $policy : ($policy[$key] ?? $default);
    }
}

if ( ! function_exists('transport_curl_options')) {
    /**
     * Opsi cURL baku: sertifikat dan nama host diverifikasi, TLS 1.2 ke atas,
     * hanya HTTPS (juga pada pengalihan).
     */
    function transport_curl_options()
    {
        $options = [
            CURLOPT_SSL_VERIFYPEER => TRUE,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];
        if (defined('CURL_SSLVERSION_TLSv1_2')) {
            $options[CURLOPT_SSLVERSION] = CURL_SSLVERSION_TLSv1_2;
        }
        if (defined('CURLOPT_PROTOCOLS') && defined('CURLPROTO_HTTPS')) {
            $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
        }
        if (defined('CURLOPT_REDIR_PROTOCOLS') && defined('CURLPROTO_HTTPS')) {
            $options[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTPS;
        }
        return $options;
    }
}

if ( ! function_exists('transport_guzzle_options')) {
    /**
     * Padanan transport_curl_options() untuk klien Guzzle (mis. minishlink/web-push):
     * sertifikat diverifikasi dan TLS minimum 1.2 dipaksa lewat opsi 'curl'.
     */
    function transport_guzzle_options()
    {
        $options = ['verify' => TRUE];
        if (defined('CURL_SSLVERSION_TLSv1_2') && defined('CURLOPT_SSLVERSION')) {
            $options['curl'] = [CURLOPT_SSLVERSION => CURL_SSLVERSION_TLSv1_2];
        }
        return $options;
    }
}

if ( ! function_exists('transport_stream_context')) {
    /**
     * Konteks stream untuk file_get_contents('https://...'): sertifikat dan
     * nama host diverifikasi, TLS 1.2 atau 1.3 saja, tanpa kompresi TLS,
     * batas waktu, dan tanpa mengikuti pengalihan.
     */
    function transport_stream_context(array $http = [])
    {
        $ssl = [
            'verify_peer'         => TRUE,
            'verify_peer_name'    => TRUE,
            'allow_self_signed'   => FALSE,
            'disable_compression' => TRUE,
        ];
        $method = 0;
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
        }
        if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
            $method |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
        }
        if ($method !== 0) {
            $ssl['crypto_method'] = $method;
        }
        return stream_context_create([
            'ssl'  => $ssl,
            'http' => $http + ['timeout' => 10, 'follow_location' => 0],
        ]);
    }
}

if ( ! function_exists('transport_is_https_url')) {
    /** TRUE hanya untuk URL berskema https dengan host. */
    function transport_is_https_url($url)
    {
        $parts = parse_url((string) $url);
        return is_array($parts)
            && strtolower($parts['scheme'] ?? '') === 'https'
            && ! empty($parts['host']);
    }
}

if ( ! function_exists('transport_db_ssl_mode')) {
    /**
     * Mode enkripsi koneksi database dari env DB_SSL:
     *   kosong/off/0/false/no -> 'off'    (perilaku lama; dev lokal tanpa TLS)
     *   verify                -> 'verify' (terenkripsi + sertifikat server diverifikasi;
     *                                      DB_HOST atau DB_SSL_HOSTNAME harus NAMA
     *                                      yang tercantum di sertifikat, bukan IP)
     *   nilai lain            -> 'on'     (terenkripsi, tanpa verifikasi identitas)
     * Nilai yang tidak dikenal SENGAJA jatuh ke 'on', bukan 'off': salah ketik
     * tidak boleh diam-diam mematikan enkripsi.
     */
    function transport_db_ssl_mode($value = NULL)
    {
        $raw = strtolower(trim((string) ($value === NULL ? getenv('DB_SSL') : $value)));
        if ($raw === '' || in_array($raw, ['0', 'off', 'false', 'no'], TRUE)) {
            return 'off';
        }
        return $raw === 'verify' ? 'verify' : 'on';
    }
}

if ( ! function_exists('transport_db_encrypt')) {
    /**
     * Nilai untuk $db['default']['encrypt'] CodeIgniter. FALSE = tanpa TLS.
     * Driver mysqli CI3 gagal tertutup bila TLS diminta tetapi koneksi yang
     * jadi tidak terenkripsi, jadi tidak ada penurunan diam-diam.
     */
    function transport_db_encrypt($mode = NULL)
    {
        $mode = $mode === NULL ? transport_db_ssl_mode() : $mode;
        if ($mode === 'off') {
            return FALSE;
        }
        $encrypt = [
            'ssl_cipher' => transport_policy('transport_db_ciphers'),
            'ssl_verify' => ($mode === 'verify'),
        ];
        $ca = trim((string) getenv('DB_SSL_CA'));
        if ($ca !== '') {
            $encrypt['ssl_ca'] = $ca;
        }
        return $encrypt;
    }
}

if ( ! function_exists('transport_db_hostname')) {
    /**
     * Host untuk koneksi database. Pada mode 'verify' boleh diganti nama host
     * lewat DB_SSL_HOSTNAME, karena sertifikat server dikeluarkan untuk NAMA,
     * sedangkan DB_HOST sering berisi alamat IP (verifikasi nama akan gagal).
     */
    function transport_db_hostname($default = 'localhost', $mode = NULL)
    {
        $mode = $mode === NULL ? transport_db_ssl_mode() : $mode;
        $name = trim((string) getenv('DB_SSL_HOSTNAME'));
        if ($mode === 'verify' && $name !== '') {
            return $name;
        }
        $host = trim((string) getenv('DB_HOST'));
        return $host !== '' ? $host : $default;
    }
}
