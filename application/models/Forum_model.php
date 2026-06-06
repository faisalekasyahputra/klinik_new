<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Forum_model extends CI_Model {

    public function get_all_diskusi() {
        $this->db->select('tb_diskusi.*, COUNT(tb_komentar.id_komentar) as total_balasan');
        $this->db->from('tb_diskusi');
        $this->db->join('tb_komentar', 'tb_diskusi.id_diskusi = tb_komentar.id_diskusi', 'left');
        $this->db->group_by('tb_diskusi.id_diskusi');
        $this->db->order_by('tb_diskusi.created_at', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_diskusi_by_id($id) {
        return $this->db->get_where('tb_diskusi', ['id_diskusi' => $id])->row_array();
    }

    public function get_komentar_by_diskusi($id) {
        $this->db->order_by('created_at', 'ASC');
        return $this->db->get_where('tb_komentar', ['id_diskusi' => $id])->result_array();
    }

    public function insert_diskusi($data) {
        return $this->db->insert('tb_diskusi', $data);
    }

    public function insert_komentar($data) {
        return $this->db->insert('tb_komentar', $data);
    }
}