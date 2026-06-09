<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sikunang extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		date_default_timezone_set('Asia/Jakarta');
	}

	public function index()
	{
        $data['title'] = 'Sikunang - Layanan Informasi';
		$data['content'] = $this->load->view('pages/layanan/sikunang', $data, true);
        $this->load->view('layouts/main', $data);
	}
}
