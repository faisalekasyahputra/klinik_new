<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_all_housing_queue() {
        $this->db->select('housing_queue.*, programs.nama_program');
        $this->db->from('housing_queue');
        $this->db->join('programs', 'housing_queue.program_id = programs.id', 'left');
        $this->db->order_by('housing_queue.created_at', 'DESC');
        $query = $this->db->get();
        return $query->result();
    }

    public function update_queue_status($id, $status, $catatan) {
        $data = array(
            'status_antrean' => $status,
            'catatan_admin' => $catatan
        );
        $this->db->where('id', $id);
        return $this->db->update('housing_queue', $data);
    }
}
