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
		$data['kabupaten_kota_jateng'] = [
			"3301" => "Kabupaten Cilacap",
			"3302" => "Kabupaten Banyumas",
			"3303" => "Kabupaten Purbalingga",
			"3304" => "Kabupaten Banjarnegara",
			"3305" => "Kabupaten Kebumen",
			"3306" => "Kabupaten Purworejo",
			"3307" => "Kabupaten Wonosobo",
			"3308" => "Kabupaten Magelang",
			"3309" => "Kabupaten Boyolali",
			"3310" => "Kabupaten Klaten",
			"3311" => "Kabupaten Sukoharjo",
			"3312" => "Kabupaten Wonogiri",
			"3313" => "Kabupaten Karanganyar",
			"3314" => "Kabupaten Sragen",
			"3315" => "Kabupaten Grobogan",
			"3316" => "Kabupaten Blora",
			"3317" => "Kabupaten Rembang",
			"3318" => "Kabupaten Pati",
			"3319" => "Kabupaten Kudus",
			"3320" => "Kabupaten Jepara",
			"3321" => "Kabupaten Demak",
			"3322" => "Kabupaten Semarang",
			"3323" => "Kabupaten Temanggung",
			"3324" => "Kabupaten Kendal",
			"3325" => "Kabupaten Batang",
			"3326" => "Kabupaten Pekalongan",
			"3327" => "Kabupaten Pemalang",
			"3328" => "Kabupaten Tegal",
			"3329" => "Kabupaten Brebes",
			"3371" => "Kota Magelang",
			"3372" => "Kota Surakarta",
			"3373" => "Kota Salatiga",
			"3374" => "Kota Semarang",
			"3375" => "Kota Pekalongan",
			"3376" => "Kota Tegal"
		];
		
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

        // Caching
        $cache_key = md5($full_url);
        $cache_file = APPPATH . 'cache/sikumbang_cari_' . $cache_key . '.json';
        $cache_time = 86400; // 24 jam
        $response = null;

        if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
            $response = file_get_contents($cache_file);
        } else {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $full_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0'); 

            $response = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if (!$err && $response) {
                @file_put_contents($cache_file, $response);
            }
        }

        if ($response) {
            $decoded_data = json_decode($response, true);
            // Sesuaikan indeks data berdasarkan response asli API Sikumbang
            $data['results'] = isset($decoded_data['data']) ? $decoded_data['data'] : [];
        }

        $data['title'] = 'Sikumbang - Data Perumahan';
        
		$data['content'] = $this->load->view('pages/data_spasial/sikumbang', $data, true);
        $this->load->view('layouts/main', $data);
	}
}
