<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Bank_desain extends MY_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('ternak_api');
        $this->load->helper(['ternak', 'url', 'form']);
    }

    public function index() {
        $data['title']           = 'Bank Desain';
        $data['house_designs']   = $this->ternak_api->get_public_house_designs();
        $data['liliput_designs'] = $this->ternak_api->get_public_liliput_designs();
        $data['site_info']       = $this->ternak_api->get_public_site_info();

        $this->load->view('layouts/header', $data);
        $this->load->view('admin/bank_desain/index', $data);
        $this->load->view('layouts/footer');
    }
}