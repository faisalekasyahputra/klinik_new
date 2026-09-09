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
        
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        
        if ($data) {
            @file_put_contents($cache_file, $response);
            $this->_site_data_cache = $data;
        }
        
        return $data;
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