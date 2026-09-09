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
        /* B4 LUNAS 9 Sep 2026. Verifikasi TLS dinyalakan penuh, dan itu tidak
           mengorbankan apa pun: sertifikat host API-nya sah (Google Trust
           Services, CN=phicos.co.id) dan diuji 200 dengan verifikasi AKTIF
           dari lokal MAUPUN dari server production. Utang ini dulu dibiarkan
           karena library-nya yatim; sekarang ia dipakai, jadi tidak boleh
           lagi. JANGAN kembalikan ke false "sementara" - kalau kelak ada
           galat sertifikat, betulkan CA bundle-nya, jangan matikan
           pemeriksaannya. */
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
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
        return $this->_request('hari_habitat/info', 'GET');
    }

    /**
     * Jadwal Hari Habitat (POST api/v2/hari_habitat/jadwal)
     * @param string $id_lomba
     */
    public function get_jadwal_hari_habitat($id_lomba)
    {
        return $this->_request('hari_habitat/jadwal', 'POST', ['id_lomba' => $id_lomba]);
    }

    /**
     * Data Kawasan Per Tahun (POST api/v2/data_kawasan/kawasan)
     * @param string $tahun
     */
    public function get_data_kawasan($tahun = '2024')
    {
        return $this->_request('data_kawasan/kawasan', 'POST', ['tahun' => $tahun]);
    }

    /**
     * Detail Per ID Kawasan (POST api/v2/data_kawasan/detail_kawasan)
     * @param string $id_kawasan
     */
    public function get_detail_kawasan($id_kawasan)
    {
        return $this->_request('data_kawasan/detail_kawasan', 'POST', ['id_kawasan' => $id_kawasan]);
    }

    /**
     * Data RTRW per ID Kawasan (POST api/v2/data_kawasan/data_rtrw)
     * @param string $id_kawasan
     */
    public function get_data_rtrw($id_kawasan)
    {
        return $this->_request('data_kawasan/data_rtrw', 'POST', ['id_kawasan' => $id_kawasan]);
    }

    /**
     * Daftar peserta Lomba Hari Habitat (POST hari_habitat/detail_peserta).
     *
     * TIDAK ADA di tabel SIKAPER_API.md maupun di koleksi Postman yang
     * dipetakan 1 Sep - ketahuan dari tangkapan layar dinas 9 Sep 2026.
     * Diverifikasi hidup: 200, ~13 KB, berisi kode_kab, nama_kab,
     * judul_proposal, pic, no_hp per peserta.
     *
     * ⚠️ Responsnya memuat NOMOR HP dan NAMA PIC. Kalau kelak ditampilkan ke
     * halaman publik, saring dulu - itu data kontak orang, bukan statistik.
     */
    public function get_detail_peserta_hari_habitat($id_lomba)
    {
        return $this->_request('hari_habitat/detail_peserta', 'POST', ['id_lomba' => $id_lomba]);
    }
}
