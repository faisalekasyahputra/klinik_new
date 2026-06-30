<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function get_all_housing_queue() {
        $this->db->select('sf_housing_queue.*, sf_programs.nama_program');
        $this->db->from('sf_housing_queue');
        $this->db->join('sf_programs', 'sf_housing_queue.program_id = sf_programs.id', 'left');
        $this->db->order_by('sf_housing_queue.created_at', 'DESC');
        $this->db->limit(1000); // Batasi 1000 baris terbaru untuk performa
        $query = $this->db->get();
        return $query->result();
    }

    public function update_queue_status($id, $status, $catatan) {
        $data = array(
            'status_antrean' => $status,
            'catatan_admin' => $catatan
        );
        $this->db->where('id', $id);
        return $this->db->update('sf_housing_queue', $data);
    }

    public function count_pending_queue() {
        $this->db->where('status_antrean', 'pending');
        return $this->db->count_all_results('sf_housing_queue');
    }
}
