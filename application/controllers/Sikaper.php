<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Sikaper extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        // Load the API wrapper
        $this->load->library('sikaper_api');
    }

    public function index()
    {
        // Parameter Default dari contoh Postman
        $id_lomba_default = 'MTAxNDI3Nw';
        $tahun_default = date('Y');
        $id_kawasan_default = 'MzU0NjcyNzYx';

        // Fetch Data from API
        $data['info_habitat'] = $this->sikaper_api->get_info_hari_habitat();
        $data['jadwal_habitat'] = $this->sikaper_api->get_jadwal_hari_habitat($id_lomba_default);
        $data['data_kawasan'] = $this->sikaper_api->get_data_kawasan($tahun_default);
        $data['detail_kawasan'] = $this->sikaper_api->get_detail_kawasan($id_kawasan_default);
        $data['data_rtrw'] = $this->sikaper_api->get_data_rtrw($id_kawasan_default);

        $data['title'] = 'Sikaper Jateng - API Data';

        // Render Views
        $data['content'] = $this->load->view('pages/sikaper/index', $data, true);
        $this->load->view('layouts/main', $data);
    }
}
