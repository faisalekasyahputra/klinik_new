<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Berita extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		date_default_timezone_set('Asia/Jakarta');
		$this->load->library('ternak_api');
	}

	public function index()
	{
        $data['title'] = 'Berita & Artikel - Klinik PKP';
        
        // Mengambil semua artikel dari API
        $data['articles'] = $this->ternak_api->get_public_articles();
        
		$data['content'] = $this->load->view('pages/berita/index', $data, true);
        $this->load->view('layouts/main', $data);
	}
}
