<?php
defined('BASEPATH') || exit('No direct script access allowed');

class User_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function check_google_user($data) {
        // Cek apakah user dengan email tersebut sudah ada
        $this->db->where('email', $data['email']);
        $query = $this->db->get('users');

        if ($query->num_rows() > 0) {
            // Jika user ada, update google_id dan avatar (jika sebelumnya login manual)
            $this->db->where('email', $data['email']);
            $this->db->update('users', array(
                'google_id' => $data['google_id'],
                'avatar'    => $data['avatar']
            ));
            return [$query->row_array(),'1'];
        } else {
            // Jika user belum terdaftar, buat akun baru otomatis
            $this->db->insert('users', $data);
            $insert_id = $this->db->insert_id();
            
            $this->db->where('id', $insert_id);
            $new_user = $this->db->get('users');
            return [$new_user->row_array(),'0'];
        }
    }
}