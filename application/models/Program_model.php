<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Program_model extends CI_Model {

    public function get_program_by_code($kode_program) {
        $this->db->where('kode_program', $kode_program);
        $this->db->where('is_active', 1);
        return $this->db->get('programs')->row_array();
    }

    public function insert_housing_queue($data) {
        return $this->db->insert('housing_queue', $data);
    }
}
