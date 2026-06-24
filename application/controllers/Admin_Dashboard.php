<?php
defined('BASEPATH') || exit('No direct script access allowed');

class Admin_Dashboard extends Admin_Controller {
    
    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $data['title'] = 'Overview Dashboard';
        
        // Dummy data for now. Can be replaced with actual model counts
        $data['stats'] = [
            'total_users' => $this->db->count_all('users'),
            'total_antrean' => 0, // Will map to housing_queue later
            'total_diskusi' => $this->db->table_exists('tb_diskusi') ? $this->db->count_all('tb_diskusi') : 0
        ];

        $this->render_admin('admin/dashboard', $data);
    }
}
