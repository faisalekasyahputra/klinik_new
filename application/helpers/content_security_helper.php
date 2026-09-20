<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Nilai header CSP dan Permissions-Policy dari config/content_security.php
 * (form keamanan poin 9.3 dan 9.4). Dibaca langsung dari berkas config supaya
 * dapat dipakai tes dan alat CLI tanpa memuat CodeIgniter.
 */

if ( ! function_exists('content_security_policy')) {
    function content_security_policy($key = NULL, $default = NULL)
    {
        static $policy = NULL;
        if ($policy === NULL) {
            $config = [];
            require dirname(__DIR__) . '/config/content_security.php';
            $policy = $config;
        }
        return $key === NULL ? $policy : ($policy[$key] ?? $default);
    }
}

if ( ! function_exists('csp_header_value')) {
    /**
     * CSP: skrip hanya dari 'self' dan host yang disetujui, tanpa <object>/<embed>,
     * <base> terkunci, dan HTTP diangkat ke HTTPS. Yang lain (gambar, gaya, frame)
     * sengaja tidak dibatasi: batasan itu belum diuji terhadap seluruh halaman.
     */
    function csp_header_value($https = TRUE)
    {
        $script = array_merge(content_security_policy('csp_script_sources_dasar', ["'self'"]),
                              content_security_policy('csp_script_hosts', []));
        // upgrade-insecure-requests hanya bermakna (dan hanya aman) pada halaman HTTPS,
        // sama seperti HSTS; pada http://localhost ia mengganggu pengembangan lokal.
        return 'script-src ' . implode(' ', $script)
            . "; object-src 'none'; base-uri 'self'" . ($https ? '; upgrade-insecure-requests' : '');
    }
}

if ( ! function_exists('permissions_policy_header_value')) {
    function permissions_policy_header_value()
    {
        $parts = [];
        foreach (content_security_policy('permissions_policy', []) as $feature => $allow) {
            $parts[] = $feature . '=' . $allow;
        }
        return implode(', ', $parts);
    }
}
