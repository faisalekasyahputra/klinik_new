<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Program_model extends CI_Model {

    public function get_program_by_code($kode_program) {
        $this->db->where('kode_program', $kode_program);
        $this->db->where('is_active', 1);
        return $this->db->get('sf_programs')->row_array();
    }

    public function insert_housing_queue($data) {
        if (empty($data['ticket_code'])) {
            $data['ticket_code'] = $this->generate_ticket_code();
        }
        return $this->db->insert('sf_housing_queue', $data);
    }

    public function generate_ticket_code() {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $code = 'PKP-';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $exists = $this->db->where('ticket_code', $code)->count_all_results('sf_housing_queue') > 0;
        } while ($exists);

        return $code;
    }

    public function get_housing_queue_by_ticket($ticket_code, $nik_suffix) {
        return $this->db
            ->select('status_antrean, created_at, updated_at')
            ->where('ticket_code', $ticket_code)
            ->where('RIGHT(nik_pengaju, 4) =', $nik_suffix, FALSE)
            ->get('sf_housing_queue')
            ->row_array();
    }
}
