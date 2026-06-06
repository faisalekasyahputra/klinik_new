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

        $url = $this->api_url . '/public/sites/' . $this->site_slug;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        $this->_site_data_cache = $data;
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
    public function get_public_liliput_designs() { return $this->_extract('prototypeDesigns'); }
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