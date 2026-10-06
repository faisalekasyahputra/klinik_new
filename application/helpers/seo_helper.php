<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * Tag SEO portal publik (6 Okt 2026): <title>, description, canonical, robots, Open Graph, Twitter Card,
 * dan JSON-LD. Sumbernya config/seo.php (per uri) yang bisa ditimpa $seo dari controller, dipakai
 * halaman detail yang judul dan gambarnya berasal dari data (perumahan, desain, kawasan).
 *
 * $seo: judul, deskripsi, gambar (path relatif atau URL penuh), gambar_alt, tipe (og:type),
 *       noindex (bool), jsonld (array schema.org tambahan).
 */

if ( ! function_exists('seo_uri')) {
    /** uri_string() huruf kecil tanpa garis miring tepi; '' = beranda. */
    function seo_uri() {
        return strtolower(trim((string) get_instance()->uri->uri_string(), '/'));
    }
}

if ( ! function_exists('seo_cfg')) {
    function seo_cfg() {
        $CI =& get_instance();
        $CI->config->load('seo', TRUE);
        return (array) $CI->config->item('seo', 'seo');
    }
}

if ( ! function_exists('seo_halaman')) {
    /** [kunci huruf asli, isi] entri config untuk uri ini (dicocokkan tanpa membedakan huruf), atau [NULL, []]. */
    function seo_halaman($uri = NULL) {
        // Alias rute (/login -> Auth/login) dicocokkan lewat rute tujuannya; canonical-nya ikut ke kunci config.
        $calon = $uri !== NULL ? [$uri] : [seo_uri(), strtolower(trim((string) get_instance()->uri->ruri_string(), '/'))];
        foreach ($calon as $c) {
            foreach ((array) (seo_cfg()['halaman'] ?? []) as $kunci => $isi) {
                if (strtolower((string) $kunci) === $c) { return [(string) $kunci, $isi]; }
            }
        }
        return [NULL, []];
    }
}

if ( ! function_exists('seo_noindex')) {
    /** Cocok persis atau diikuti '/'; entri berakhiran '/' atau '_' = awalan mentah. */
    function seo_noindex($uri, array $daftar = NULL) {
        foreach ($daftar ?? (seo_cfg()['noindex'] ?? []) as $p) {
            $p = strtolower((string) $p);
            $awalan = in_array(substr($p, -1), ['/', '_'], TRUE);
            if ($uri === rtrim($p, '/') || strpos($uri, $awalan ? $p : $p . '/') === 0) { return TRUE; }
        }
        return FALSE;
    }
}

if ( ! function_exists('seo_url_mutlak')) {
    /** Open Graph wajib URL penuh: path relatif dijadikan base_url(), URL http(s) dibiarkan. */
    function seo_url_mutlak($path) {
        $path = (string) $path;
        return preg_match('#^https?://#i', $path) ? $path : base_url(ltrim($path, '/'));
    }
}

if ( ! function_exists('seo_potong')) {
    /** Deskripsi dari teks bebas: satu baris, tanpa tag, paling panjang $maks karakter di batas kata. */
    function seo_potong($teks, $maks = 160) {
        $teks = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $teks)));
        if (mb_strlen($teks) <= $maks) { return $teks; }
        $potong = mb_substr($teks, 0, $maks - 1);
        $spasi = mb_strrpos($potong, ' ');
        return rtrim($spasi > $maks * 0.6 ? mb_substr($potong, 0, $spasi) : $potong, ' ,.;:') . '…';
    }
}

if ( ! function_exists('seo_judul_penuh')) {
    /** Judul untuk <title>; dipakai juga header X-Judul-Halaman pada respons partial (MY_Controller::render). */
    function seo_judul_penuh(array $seo = []) {
        $cfg = seo_cfg();
        $uri = seo_uri();
        $judul = trim((string) ($seo['judul'] ?? seo_halaman()[1]['judul'] ?? ''));
        return $judul === '' ? $cfg['nama_situs'] : ($uri === '' ? $cfg['nama_situs'] . ': ' . $judul : $judul . ' | ' . $cfg['nama_situs']);
    }
}

if ( ! function_exists('seo_meta')) {
    function seo_meta(array $seo = []) {
        $cfg = seo_cfg();
        $uri = seo_uri();
        [$kunci_hal, $hal] = seo_halaman();
        $situs = $cfg['nama_situs'];

        $judul = trim((string) ($seo['judul'] ?? $hal['judul'] ?? ''));
        $deskripsi = seo_potong($seo['deskripsi'] ?? $hal['deskripsi'] ?? $cfg['deskripsi']);
        $kustom = ! empty($seo['gambar']);
        $gambar = seo_url_mutlak($kustom ? $seo['gambar'] : $cfg['gambar']);
        $alt = (string) ($seo['gambar_alt'] ?? ($judul !== '' ? $judul : $situs));
        // Halaman berkueri (?page=, ?q=) menunjuk ke versi tanpa kueri supaya tidak dihitung halaman kembar.
        // Huruf rute dari config bila terdaftar (URL di Linux peka huruf: /cek_rtlh 404, /Cek_Rtlh benar).
        $kanonik = base_url($kunci_hal ?? trim((string) get_instance()->uri->uri_string(), '/'));
        $noindex = ! empty($seo['noindex']) || seo_noindex($uri);
        $e = function ($v) { return html_escape((string) $v); };

        $tag = [
            '<title>' . $e(seo_judul_penuh($seo)) . '</title>',
            '<meta name="description" content="' . $e($deskripsi) . '">',
            '<meta name="robots" content="' . ($noindex ? 'noindex, nofollow' : 'index, follow, max-image-preview:large') . '">',
        ];
        if ( ! $noindex) { $tag[] = '<link rel="canonical" href="' . $e($kanonik) . '">'; }
        foreach ([
            'og:type' => $seo['tipe'] ?? ($uri === '' ? 'website' : 'article'),
            'og:site_name' => $situs, 'og:locale' => 'id_ID',
            'og:title' => $judul !== '' ? $judul : $situs, 'og:description' => $deskripsi,
            'og:url' => $kanonik, 'og:image' => $gambar, 'og:image:alt' => $alt,
        ] as $k => $v) {
            $tag[] = '<meta property="' . $k . '" content="' . $e($v) . '">';
        }
        // Ukuran hanya diketahui untuk gambar bawaan; gambar dari data dibiarkan dibaca perayap.
        if ( ! $kustom) {
            $tag[] = '<meta property="og:image:width" content="' . (int) $cfg['gambar_lebar'] . '">';
            $tag[] = '<meta property="og:image:height" content="' . (int) $cfg['gambar_tinggi'] . '">';
        }
        foreach (['twitter:card' => 'summary_large_image', 'twitter:title' => $judul !== '' ? $judul : $situs,
            'twitter:description' => $deskripsi, 'twitter:image' => $gambar, 'twitter:image:alt' => $alt] as $k => $v) {
            $tag[] = '<meta name="' . $k . '" content="' . $e($v) . '">';
        }

        $ld = [];
        if ($uri === '') {
            $org = $cfg['organisasi'];
            $ld[] = ['@context' => 'https://schema.org', '@type' => 'GovernmentOrganization', 'name' => $org['nama'],
                'url' => base_url(), 'logo' => seo_url_mutlak($org['logo']),
                'address' => ['@type' => 'PostalAddress', 'addressRegion' => 'Jawa Tengah', 'addressCountry' => 'ID']];
            $ld[] = ['@context' => 'https://schema.org', '@type' => 'WebSite', 'name' => $situs, 'url' => base_url(),
                'inLanguage' => 'id-ID', 'publisher' => ['@type' => 'GovernmentOrganization', 'name' => $org['nama']]];
        }
        if ( ! empty($seo['jsonld'])) { $ld[] = ['@context' => 'https://schema.org'] + $seo['jsonld']; }
        foreach ($ld as $blok) {
            // JSON_HEX_TAG: isi dari data hulu tidak bisa menutup <script> lebih awal.
            $tag[] = '<script type="application/ld+json">' . json_encode($blok, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>';
        }
        return implode("\n    ", $tag) . "\n";
    }
}
