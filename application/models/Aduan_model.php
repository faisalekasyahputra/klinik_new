<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Aduan_model extends CI_Model {

    public function bidang_label($bidang) {
        $labels = [
            'perumahan'  => 'Bidang Perumahan',
            'kawasan'    => 'Bidang Kawasan Permukiman',
            'pertanahan' => 'Bidang Pertanahan',
            'pengembang' => 'Bidang Pengembang',
            'umum'       => 'Admin Umum',
        ];
        return $labels[$bidang] ?? $labels['umum'];
    }

    public function create($data) {
        $this->db->insert('aduan', $data);
        return $this->db->insert_id();
    }
}
