<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ternak_api {

    protected $CI;
    protected $api_url;
    protected $site_slug;
    
    // Caching internal per-request agar tidak request berulang kali
    protected $_site_data_cache = null;

    public function __construct() {
        $this->CI =& get_instance();
        $this->CI->load->config('ternak_api');
        $this->api_url = $this->CI->config->item('ternak_api_url');
        $this->site_slug = $this->CI->config->item('ternak_site_slug');
    }

    /**
     * Mengambil SEMUA data site publik (Di-cache di file 10 menit + memori per-request)
     */
    public function get_site_data() {
        if ($this->_site_data_cache !== null) {
            return $this->_site_data_cache;
        }

        $cache_file = APPPATH . 'cache/ternak_site_data_' . $this->site_slug . '.json';
        $cache_time = 600; // 10 menit

        if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
            $data = json_decode(file_get_contents($cache_file), true);
            if ($data) {
                // Cache dari sebelum penyaringan (tanpa penanda) disaring sekali lalu ditulis ulang.
                if (empty($data["_disaring"])) { $data = $this->simpan_bersih($cache_file, $data, filemtime($cache_file)); }
                $this->_site_data_cache = $data;
                return $data;
            }
        }

        $url = $this->api_url . '/public/sites/' . $this->site_slug;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Baris VERIFYHOST ada DUA KALI di sini: `2` lalu langsung ditimpa
        // `false`. Yang berlaku yang terakhir, jadi verifikasi nama host MATI -
        // rantai sertifikat diperiksa, tapi tidak ada yang memastikan sertifikat
        // itu memang milik host yang kita tuju. Sertifikat sah dari domain mana
        // pun akan diterima. Baris `2` di atasnya membuatnya terbaca aman
        // sekilas; itu yang membuatnya bertahan lama. Yang menimpa dibuang.
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt_array($ch, transport_curl_options()); // TLS 1.2+, HTTPS saja (poin 8.2)

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        
        if ($data) {
            $data = $this->simpan_bersih($cache_file, $data);
            $this->_site_data_cache = $data;
        }

        return $data;
    }

    /** Saring lalu tulis cache bertanda `_disaring`; $mtime menjaga umur cache lama yang ditulis ulang. */
    private function simpan_bersih($cache_file, array $data, $mtime = NULL) {
        $data = self::bersihkan_data($data);
        $data['_disaring'] = 1;
        @file_put_contents($cache_file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if ($mtime) { @touch($cache_file, (int) $mtime); }
        return $data;
    }

    /**
     * Konten CMS ini ditulis di luar kendali aplikasi (termasuk blok `inherited` dari situs induk),
     * jadi body artikel diperlakukan sebagai HTML tak tepercaya. Medan lain dicetak ter-escape di view.
     */
    public static function bersihkan_data(array $data) {
        foreach (['local', 'inherited'] as $blok) {
            foreach ((array) ($data[$blok]['articles'] ?? []) as $i => $artikel) {
                if (is_array($artikel) && isset($artikel['body'])) {
                    $data[$blok]['articles'][$i]['body'] = self::bersihkan_html($artikel['body']);
                }
            }
        }
        return $data;
    }

    /**
     * HTML artikel lewat daftar izin HTMLPurifier (ikut terpasang bersama phpspreadsheet): tag teks,
     * daftar, tabel, dan tautan http/https/mailto. Atribut on*, style, script, iframe, dan skema
     * javascript: dibuang. Tanpa HTMLPurifier body jadi teks biasa (gagal ke arah aman).
     */
    public static function bersihkan_html($html) {
        $html = (string) $html;
        if ( ! class_exists('HTMLPurifier')) {
            return nl2br(htmlspecialchars(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES, 'UTF-8'));
        }
        static $purifier = NULL;
        if ($purifier === NULL) {
            $c = HTMLPurifier_Config::createDefault();
            $c->set('Cache.DefinitionImpl', NULL); // tanpa menulis cache definisi ke folder vendor
            $c->set('HTML.Allowed', 'p,br,strong,b,em,i,u,s,sub,sup,span,div,h2,h3,h4,h5,h6,blockquote,'
                . 'ul,ol,li,table,thead,tbody,tr,th,td,a[href|title]');
            $c->set('URI.AllowedSchemes', ['http' => TRUE, 'https' => TRUE, 'mailto' => TRUE]);
            $c->set('HTML.TargetBlank', TRUE);
            $purifier = new HTMLPurifier($c);
        }
        return $purifier->purify($html);
    }

    /**
     * Fungsi pembantu untuk ekstrak data dari kombinasi 'local' dan 'inherited'
     */
    private function _extract($resource_key) {
        $data = $this->get_site_data();
        $result = [];
        
        if (isset($data['local'][$resource_key]) && is_array($data['local'][$resource_key])) {
            $result = array_merge($result, $data['local'][$resource_key]);
        }
        if (isset($data['inherited'][$resource_key]) && is_array($data['inherited'][$resource_key])) {
            $result = array_merge($result, $data['inherited'][$resource_key]);
        }
        
        return $result;
    }

    // --- GETTERS ---

    public function get_public_articles() { return $this->_extract('articles'); }
    public function get_public_archives() { return $this->_extract('archives'); }
    public function get_public_videos() { return $this->_extract('videos'); }
    public function get_public_house_designs() { return $this->_extract('houseDesigns'); }
    /**
     * Desain PROTOTIPE, bukan liliput.
     *
     * Butir 4 putaran 2. Metode ini dulu bernama `get_public_liliput_designs()`
     * padahal yang dikembalikannya `prototypeDesigns`, dan diperiksa langsung ke
     * API 11 Agt 2026: sembilan barisnya bertipe 22/72 sampai 36/72, bukan rumah
     * liliput. Nama yang menjanjikan hal yang datanya tidak dukung lebih
     * berbahaya daripada nama yang jelek: pemakai berikutnya akan menayangkannya
     * dengan label "liliput UGM" di halaman publik dan tidak ada yang merah.
     *
     * Rumah liliput UGM memang BELUM ADA sumbernya di API ini. Itu dicatat di
     * layar sebagai keterangan, bukan diisi dengan data yang kebetulan ada.
     */
    public function get_public_prototype_designs() { return $this->_extract('prototypeDesigns'); }
    public function get_public_regulations() { return $this->_extract('regulations'); }
    
    public function get_public_site_info() {
        $data = $this->get_site_data();
        return isset($data['site']) ? $data['site'] : [];
    }

    public function get_public_banners() {
        $data = $this->get_site_data();
        return isset($data['site']['banners']) ? $data['site']['banners'] : [];
    }
    
    public function get_public_infographics() {
        $data = $this->get_site_data();
        return isset($data['site']['infographics']) ? $data['site']['infographics'] : [];
    }
}