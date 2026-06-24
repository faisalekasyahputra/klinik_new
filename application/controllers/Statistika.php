<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Statistika extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('url');
        $this->load->library('ternak_api');
	}

	public function index()
	{
		$data['title'] = 'Statistika Bank Data Perumahan';
        
        $kabupaten = $this->input->get('kabupaten');
        $multiplier = 1.0;
        
        if (!empty($kabupaten) && $kabupaten !== 'all') {
            $hash = crc32($kabupaten);
            $multiplier = (($hash % 80) + 20) / 1000; // Multiplier between 0.02 and 0.1 (2% to 10% of province data)
        }
        
        // Dummy data for statistics grouped by domain
        $data['stats'] = [
            'perumahan' => [
                'tloo' => ['value' => round(1520 * $multiplier), 'sumber' => 'Simperum'],
                'rtlh_apbd' => ['value' => round(12500 * $multiplier), 'sumber' => 'Simperum'],
                'bsps' => ['value' => round(18000 * $multiplier), 'sumber' => 'Simperum'],
                'omah_lestari' => ['value' => round(2500 * $multiplier), 'sumber' => 'Simperum'],
                'unit_subsidi' => ['value' => round(45000 * $multiplier), 'sumber' => 'Sikumbang'],
                'unit_komersil' => ['value' => round(12000 * $multiplier), 'sumber' => 'Sikumbang'],
            ],
            'kawasan' => [
                'luas_kumuh' => ['value' => round(1205.5 * $multiplier, 1), 'sumber' => 'Sikunang'],
                'tertangani' => ['value' => round(850.2 * $multiplier, 1), 'sumber' => 'Sikunang'],
                'sisa_kumuh' => ['value' => round(355.3 * $multiplier, 1), 'sumber' => 'Sikunang'],
                'persentase' => ['value' => round((850.2 / 1205.5) * 100, 1), 'sumber' => 'Sikunang'], // Persentase tetap sama
            ],
            'pertanahan' => [
                'aset_lahan' => ['value' => round(150.5 * $multiplier, 1), 'sumber' => 'Bank Tanah'],
                'lahan_siap_bangun' => ['value' => round(85.2 * $multiplier, 1), 'sumber' => 'Bank Tanah'],
                'lahan_termanfaatkan' => ['value' => round(45.0 * $multiplier, 1), 'sumber' => 'Bank Tanah'],
            ],
            'pengembang' => [
                'total_terdaftar' => ['value' => round(450 * $multiplier), 'sumber' => 'Sikaper'],
                'aktif' => ['value' => round(310 * $multiplier), 'sumber' => 'Sikaper'],
                'proyek_berjalan' => ['value' => round(125 * $multiplier), 'sumber' => 'Sikaper'],
                'asosiasi' => ['value' => round(8 * $multiplier), 'sumber' => 'Sikaper'],
            ],
            'penerima_manfaat' => [
                'bantuan_rtlh' => ['value' => round(15000 * $multiplier), 'sumber' => 'Simperum'],
                'pembeli_subsidi' => ['value' => round(38000 * $multiplier), 'sumber' => 'Sikumbang'],
                'pembeli_komersil' => ['value' => round(8500 * $multiplier), 'sumber' => 'Sikumbang'],
                'total_penerima' => ['value' => round(61500 * $multiplier), 'sumber' => 'Kompilasi'],
            ]
        ];
        
        $data['kabupaten_terpilih'] = $kabupaten ? $kabupaten : 'all';
        $data['daftar_kabupaten'] = [
            'Kota Semarang', 'Kota Surakarta', 'Kota Salatiga', 'Kota Magelang', 'Kota Pekalongan', 'Kota Tegal',
            'Kabupaten Semarang', 'Kabupaten Kendal', 'Kabupaten Demak', 'Kabupaten Grobogan', 'Kabupaten Pati', 'Kabupaten Kudus', 'Kabupaten Jepara',
            'Kabupaten Rembang', 'Kabupaten Blora', 'Kabupaten Boyolali', 'Kabupaten Klaten', 'Kabupaten Sukoharjo', 'Kabupaten Wonogiri', 'Kabupaten Karanganyar',
            'Kabupaten Sragen', 'Kabupaten Banyumas', 'Kabupaten Cilacap', 'Kabupaten Purbalingga', 'Kabupaten Banjarnegara', 'Kabupaten Kebumen',
            'Kabupaten Purworejo', 'Kabupaten Wonosobo', 'Kabupaten Magelang', 'Kabupaten Temanggung', 
            'Kabupaten Batang', 'Kabupaten Pekalongan', 'Kabupaten Pemalang', 'Kabupaten Tegal', 'Kabupaten Brebes'
        ];
        
        // Fetch Real Data from KRSjawa 3 API for Publikasi
        $data['publikasi'] = [
            'artikel' => count($this->ternak_api->get_public_articles() ?: []),
            'video' => count($this->ternak_api->get_public_videos() ?: []),
            'regulasi' => count($this->ternak_api->get_public_regulations() ?: []),
            'desain_rumah' => count($this->ternak_api->get_public_house_designs() ?: [])
        ];

		$data['content'] = $this->load->view('pages/data_spasial/statistika', $data, true);
        $this->load->view('layouts/main', $data);
	}
}
