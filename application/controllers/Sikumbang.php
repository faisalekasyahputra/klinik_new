<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sikumbang extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->helper(array('url', 'download'));
		date_default_timezone_set('Asia/Jakarta');
	}
	public function index()
	{
		$keyword = $this->input->get('keyword');
        $sort    = $this->input->get('sort') ?: 'terbaru';
        $limit   = $this->input->get('limit') ?: 12;

		$data['results'] = [];
        $data['keyword'] = $keyword;

        // Eksekusi API jika ada keyword atau akses halaman utama
        $api_url = "https://sikumbang.tapera.go.id/ajax/lokasi/search";
        $params = [
            'keyword'  => $keyword,
            'searchBy' => 'nama-perumahan',
            'sort'     => $sort,
            'limit'    => $limit
        ];

        $full_url = $api_url . '?' . http_build_query($params);

        // Inisialisasi cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $full_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Lewati verifikasi SSL jika di localhost
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0'); 

        $response = curl_exec($ch);
		$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
		
        $err = curl_error($ch);
        curl_close($ch);

        if (!$err) {
            $decoded_data = json_decode($response, true);
			
            // Sesuaikan indeks data berdasarkan response asli API Sikumbang
            $data['results'] = isset($decoded_data['data']) ? $decoded_data['data'] : [];
        }
		$this->load->view('sikumbang', $data);
	}

	public function tambah_intervensi()
	{
		
		$this->load->view('tambah_intervensi');
	}
}
