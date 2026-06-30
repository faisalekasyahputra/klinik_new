<?php
defined('BASEPATH') || exit('No direct script access allowed');

class Admin_Dashboard extends Admin_Controller {
    
    public function __construct() {
        parent::__construct();
    }

    public function index() {
        $data['title'] = 'Overview Dashboard';
        
        // Use updated table names
        $data['stats'] = [
            'total_users' => $this->db->count_all('usr_users'),
            'total_antrean' => $this->db->where('status_antrean', 'pending')->count_all_results('sf_housing_queue'),
            'total_diskusi' => $this->db->table_exists('forum_diskusi') ? $this->db->count_all('forum_diskusi') : 0
        ];

        $this->render_admin('admin/dashboard', $data);
    }
}
