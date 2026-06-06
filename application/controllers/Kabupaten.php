<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Kabupaten extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->helper(array('url', 'download'));
		date_default_timezone_set('Asia/Jakarta');
	}
	public function index()
	{
		
	}

	public function tambah_intervensi()
	{
		
		$this->load->view('tambah_intervensi');
	}
}
