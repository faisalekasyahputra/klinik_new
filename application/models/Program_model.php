<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Program_model extends CI_Model {

    const TICKET_LOOKUP_MAX_FAILURES = 5;

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

    public function get_ticket_lookup_retry_after($ip_hash) {
        $row = $this->db
            ->select('failed_attempts, GREATEST(1, 60 - TIMESTAMPDIFF(SECOND, window_started_at, NOW())) AS retry_after', FALSE)
            ->where('ip_hash', $ip_hash)
            ->where('window_started_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)', NULL, FALSE)
            ->get('sys_ticket_lookup_limits')
            ->row_array();

        return $row && (int) $row['failed_attempts'] >= self::TICKET_LOOKUP_MAX_FAILURES
            ? (int) $row['retry_after']
            : 0;
    }

    public function record_ticket_lookup_failure($ip_hash) {
        return $this->db->query(
            'INSERT INTO sys_ticket_lookup_limits (ip_hash, window_started_at, failed_attempts)
             VALUES (?, NOW(), 1)
             ON DUPLICATE KEY UPDATE
                failed_attempts = IF(window_started_at <= DATE_SUB(NOW(), INTERVAL 1 MINUTE), 1, failed_attempts + 1),
                window_started_at = IF(window_started_at <= DATE_SUB(NOW(), INTERVAL 1 MINUTE), NOW(), window_started_at)',
            [$ip_hash]
        );
    }
}
