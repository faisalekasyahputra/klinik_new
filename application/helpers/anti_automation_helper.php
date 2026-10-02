<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Fungsi murni untuk kontrol anti-otomatisasi (form keamanan poin 10.4). Dibaca dari
 * config/anti_automation.php langsung supaya dapat diuji tanpa memuat CodeIgniter.
 */

if ( ! function_exists('anti_automation_config')) {
    function anti_automation_config($key = NULL, $default = NULL)
    {
        static $cfg = NULL;
        if ($cfg === NULL) {
            $config = [];
            require dirname(__DIR__) . '/config/anti_automation.php';
            $cfg = $config;
        }
        return $key === NULL ? $cfg : ($cfg[$key] ?? $default);
    }
}

if ( ! function_exists('anti_automation_is_scanner')) {
    /** TRUE bila User-Agent memuat tanda tangan alat serangan/pemindai yang dikenal. */
    function anti_automation_is_scanner($user_agent)
    {
        $user_agent = (string) $user_agent;
        if ($user_agent === '') { return FALSE; }
        $pola = anti_automation_config('scanner_user_agents', []);
        if ( ! $pola) { return FALSE; }
        return preg_match('#(?:' . implode('|', $pola) . ')#i', $user_agent) === 1;
    }
}

if ( ! function_exists('anti_automation_api_schemas')) {
    /** Registri skema endpoint API (config/api_schemas.php): sumber daftar endpoint API untuk kelas laju. */
    function anti_automation_api_schemas()
    {
        static $skema = NULL;
        if ($skema === NULL) {
            $config = [];
            require dirname(__DIR__) . '/config/api_schemas.php';
            $skema = $config['api_schemas'];
        }
        return $skema;
    }
}

if ( ! function_exists('anti_automation_route_classes')) {
    /**
     * Kelas rute untuk "controller/metode" (huruf kecil), mis. ['cari'] atau ['unduh'].
     * Satu rute boleh masuk lebih dari satu kelas. Selain pola di config/anti_automation.php,
     * SETIAP endpoint yang terdaftar di config/api_schemas.php otomatis masuk kelas yang
     * dideklarasikannya (`class`), jadi endpoint API baru tidak bisa lolos dari batas laju
     * dengan lupa menambahkan pola di dua tempat.
     */
    function anti_automation_route_classes($controller, $method)
    {
        $rute = strtolower((string) $controller) . '/' . strtolower((string) $method);
        $hasil = [];
        foreach (anti_automation_config('route_classes', []) as $kelas => $daftar) {
            foreach ($daftar as $pola) {
                if (fnmatch($pola, $rute)) { $hasil[] = $kelas; break; }
            }
        }
        $skema = anti_automation_api_schemas()[$rute] ?? NULL;
        if ($skema !== NULL && ! empty($skema['class']) && ! in_array($skema['class'], $hasil, TRUE)) {
            $hasil[] = $skema['class'];
        }
        return $hasil;
    }
}

if ( ! function_exists('anti_automation_route_is_json')) {
    /** TRUE bila endpoint terdaftar sebagai API yang responsnya (termasuk penolakan) harus berbentuk JSON. */
    function anti_automation_route_is_json($controller, $method)
    {
        $skema = anti_automation_api_schemas()[strtolower((string) $controller) . '/' . strtolower((string) $method)] ?? NULL;
        return $skema !== NULL && ! empty($skema['json']);
    }
}

if ( ! function_exists('anti_automation_is_write')) {
    function anti_automation_is_write($http_method)
    {
        return in_array(strtoupper((string) $http_method), anti_automation_config('write_methods', []), TRUE);
    }
}

if ( ! function_exists('anti_automation_ip_allowed')) {
    /**
     * IP yang dikecualikan dari kontrol global: loopback (permintaan dari server itu sendiri:
     * pemantauan, harness uji) dan daftar eksplisit di env ANTI_OTOMATISASI_IP_DIIZINKAN
     * (dipisah koma; IP persis, tanpa rentang). Untuk kantor/kampus yang berbagi satu IP dan
     * terus terkena batas; keputusan operasional yang dicatat, bukan bawaan.
     */
    function anti_automation_ip_allowed($ip, $daftar_env = NULL)
    {
        $ip = trim((string) $ip);
        if ($ip === '') { return FALSE; }
        if (in_array($ip, ['127.0.0.1', '::1'], TRUE)) { return TRUE; }
        $daftar_env = $daftar_env === NULL ? (string) getenv('ANTI_OTOMATISASI_IP_DIIZINKAN') : (string) $daftar_env;
        foreach (explode(',', $daftar_env) as $izin) {
            if (trim($izin) !== '' && strcasecmp(trim($izin), $ip) === 0) { return TRUE; }
        }
        return FALSE;
    }
}

if ( ! function_exists('anti_automation_ip_bucket')) {
    /**
     * Kunci penghitung untuk satu alamat klien. IPv4 apa adanya; IPv6 disatukan per blok /64
     * (penyerang IPv6 dapat berganti alamat sesuka hati di dalam /64 miliknya, sehingga penghitung
     * per alamat tidak menahan apa pun); IPv4-mapped IPv6 (::ffff:a.b.c.d) dianggap IPv4-nya.
     * Alamat yang tidak dapat diurai dikembalikan apa adanya.
     */
    function anti_automation_ip_bucket($ip)
    {
        $ip = trim((string) $ip);
        if ($ip === '' || strpos($ip, ':') === FALSE) { return $ip; }
        $bin = @inet_pton($ip);
        if ($bin === FALSE || strlen($bin) !== 16) { return $ip; }
        if (substr($bin, 0, 12) === hex2bin('00000000000000000000ffff')) { return (string) inet_ntop(substr($bin, 12)); }
        return bin2hex(substr($bin, 0, 8)) . '/64';
    }
}

if ( ! function_exists('bot_guard_fields')) {
    /** Untuk view: kolom honeypot tersembunyi + token waktu, disisipkan di dalam <form>. */
    function bot_guard_fields($form)
    {
        $CI =& get_instance();
        $CI->load->library('Bot_guard');
        return $CI->bot_guard->fields($form);
    }
}
