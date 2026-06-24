<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_Settings extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        // Superadmin access is already checked in Admin_Controller
    }

    public function index()
    {
        $data['title'] = 'Pengaturan Sistem';
        $this->render_admin('admin/settings/index', $data);
    }
}
