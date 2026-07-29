<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sikaper_api {

    protected $CI;
    private $base_url;
    private $username;
    private $password;

    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->config('sikaper');

        $this->base_url = rtrim($this->CI->config->item('sikaper_api_base_url'), '/') . '/';
        $this->username = $this->CI->config->item('sikaper_api_username');
        $this->password = $this->CI->config->item('sikaper_api_password');
    }

    /**
     * Helper untuk HTTP Request
     */
    private function _request($endpoint, $method = 'GET', $data = [])
    {
        $url = $this->base_url . ltrim($endpoint, '/');

        $ch = curl_init();
        
        $headers = [
            'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password)
        ];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // B4 — SENGAJA belum diperbaiki, dan itu bukan kelalaian. Library ini
        // NOL PEMANGGIL sejak controller publik Sikaper dicabut (B6), jadi
        // baris ini tidak pernah dieksekusi. Nasibnya mengikuti keputusan #5:
        // bila integrasinya dipertahankan, rotasi kredensial dulu lalu TLS
        // dinyalakan sebelum diaktifkan kembali; bila dicabut, berkas ini
        // hilang seluruhnya. Memolesnya sekarang berarti merapikan kode yatim
        // yang mungkin dibuang besok.
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);

        if ($error) {
            return [
                'status' => false,
                'message' => 'cURL Error: ' . $error,
                'data' => null
            ];
        }

        $decoded = json_decode($response, true);
        
        if ($http_code >= 200 && $http_code < 300) {
            return [
                'status' => true,
                'http_code' => $http_code,
                'data' => $decoded
            ];
        } else {
            return [
                'status' => false,
                'http_code' => $http_code,
                'message' => 'API Error (HTTP ' . $http_code . ')',
                'data' => $decoded
            ];
        }
    }

    // ------------------------------------------------------------------------
    // API ENDPOINTS
    // ------------------------------------------------------------------------

    /**
     * Info Hari Habitat (GET api/v2/hari_habitat/info)
     */
    public function get_info_hari_habitat()
    {
        return $this->_request('v2/hari_habitat/info', 'GET');
    }

    /**
     * Jadwal Hari Habitat (POST api/v2/hari_habitat/jadwal)
     * @param string $id_lomba
     */
    public function get_jadwal_hari_habitat($id_lomba = 'MTAxNDI3Nw')
    {
        return $this->_request('v2/hari_habitat/jadwal', 'POST', ['id_lomba' => $id_lomba]);
    }

    /**
     * Data Kawasan Per Tahun (POST api/v2/data_kawasan/kawasan)
     * @param string $tahun
     */
    public function get_data_kawasan($tahun = '2024')
    {
        return $this->_request('v2/data_kawasan/kawasan', 'POST', ['tahun' => $tahun]);
    }

    /**
     * Detail Per ID Kawasan (POST api/v2/data_kawasan/detail_kawasan)
     * @param string $id_kawasan
     */
    public function get_detail_kawasan($id_kawasan = 'MzU0NjcyNzYx')
    {
        return $this->_request('v2/data_kawasan/detail_kawasan', 'POST', ['id_kawasan' => $id_kawasan]);
    }

    /**
     * Data RTRW per ID Kawasan (POST api/v2/data_kawasan/data_rtrw)
     * @param string $id_kawasan
     */
    public function get_data_rtrw($id_kawasan = 'MzU0NjcyNzYx')
    {
        return $this->_request('v2/data_kawasan/data_rtrw', 'POST', ['id_kawasan' => $id_kawasan]);
    }
}
