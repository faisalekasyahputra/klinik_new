<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Kemitraan extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->helper(array('url', 'download'));
		date_default_timezone_set('Asia/Jakarta');
	}
	public function index()
	{
		
	}

	public function kkn()
	{
		
		$this->load->view('pages/kemitraan/kkn');
	}
	public function magang()
	{
		
		$this->load->view('pages/kemitraan/magang');
	}
	public function ketentuan()
	{
		
		$this->load->view('pages/kemitraan/ketentuan');
	}
	
	
}
