<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_Users extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        // Superadmin access is already checked in Admin_Controller
    }

    public function index()
    {
        $data['title'] = 'Manajemen Pengguna';
        // In the future, load users from database here
        $this->render_admin('admin/users/index', $data);
    }
}
