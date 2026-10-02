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

if ( ! function_exists('kirim_header_keamanan')) {
    /**
     * Header keamanan untuk SETIAP respons PHP (poin 9.3, 9.4, 12.2, 13.5). Dipanggil MY_Controller dan
     * MY_Exceptions (halaman 404/galat yang dibuat CodeIgniter sebelum controller berjalan). Aman dipanggil
     * berulang: header() mengganti nilai bernama sama, tidak menggandakannya.
     */
    function kirim_header_keamanan()
    {
        if (headers_sent()) { return; }
        $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';

        // Anti-clickjacking dan anti-sniffing.
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');

        // Poin 12.2: jangan mengumumkan teknologi dan versi (X-Powered-By: PHP/x.y.z).
        header_remove('X-Powered-By');

        // HSTS: hanya bermakna di HTTPS.
        if ($https) { header('Strict-Transport-Security: max-age=31536000; includeSubDomains'); }

        // Permissions Policy: semua fitur sensor/privasi ditolak kecuali yang dipakai (geolokasi); config/content_security.php.
        header('Permissions-Policy: ' . permissions_policy_header_value());

        // CSP: skrip hanya dari 'self' dan host yang disetujui, tanpa <object>, <base> terkunci. PERINGATAN: di
        // production header ini DITIMPA platform hosting (hcdn mengganti Content-Security-Policy aplikasi dengan
        // `upgrade-insecure-requests` miliknya, diverifikasi 21 Sep 2026); yang benar-benar menegakkan di sana adalah
        // <meta http-equiv> lewat csp_meta_tag() di setiap halaman lengkap.
        header('Content-Security-Policy: ' . csp_header_value($https));

        // Blokir kebijakan lintas domain lama (Flash/PDF).
        header('X-Permitted-Cross-Domain-Policies: none');

        // Poin 13.5: isolasi lintas asal. COOP `same-origin-allow-popups` (bukan same-origin) supaya popup login
        // Google tetap dapat menutup dirinya dan mengarahkan jendela pembuka; CORP `same-origin` melarang situs
        // lain menyematkan respons dinamis kita (gambar/JSON) tanpa izin.
        header('Cross-Origin-Opener-Policy: same-origin-allow-popups');
        header('Cross-Origin-Resource-Policy: same-origin');
    }
}

if ( ! function_exists('csp_meta_tag')) {
    /**
     * CSP sebagai <meta http-equiv>, WAJIB ada di setiap halaman HTML lengkap.
     * Ditemukan 21 Sep 2026: platform hosting (hcdn) MENIMPA header
     * Content-Security-Policy dari aplikasi dengan miliknya sendiri
     * (`upgrade-insecure-requests`), sehingga CSP lewat header tidak pernah sampai
     * ke peramban di production. Tag meta tidak tersentuh platform, dan peramban
     * menegakkan meta bersama header (kebijakan berlaku sebagai irisan).
     * Letakkan SEBELUM skrip mana pun, tepat sesudah <meta charset>.
     * Catatan: directive frame-ancestors/report-uri/sandbox tidak berlaku lewat meta
     * (tidak dipakai di sini; anti-clickjacking tetap X-Frame-Options).
     */
    function csp_meta_tag()
    {
        $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        return '<meta http-equiv="Content-Security-Policy" content="'
            . htmlspecialchars(csp_header_value($https), ENT_QUOTES, 'UTF-8') . '">';
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
