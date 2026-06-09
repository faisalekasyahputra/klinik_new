<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Siperum extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		date_default_timezone_set('Asia/Jakarta');
	}

	public function index()
	{
        $data['title'] = 'Siperum - Sistem Informasi Perumahan';
		$data['content'] = $this->load->view('pages/layanan/siperum', $data, true);
        $this->load->view('layouts/main', $data);
	}
}
