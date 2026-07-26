<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Program_model extends CI_Model {

    const TICKET_LOOKUP_MAX_FAILURES = 5;

    public function get_program_by_code($kode_program) {
        $this->db->where('kode_program', $kode_program);
        $this->db->where('is_active', 1);
        return $this->db->get('sf_programs')->row_array();
    }

    /**
     * Tentukan kabupaten_id yang boleh disimpan ke sf_housing_queue.
     *
     * URUTAN KEPERCAYAAN (jangan dibalik):
     *   1. Domisili user yang login (usr_users.kabupaten_id) — data terverifikasi,
     *      tidak bisa dipalsukan pemohon lewat form.
     *   2. Pilihan user di form, TAPI wajib cocok dengan baris nyata di tabel
     *      kabupaten — menutup nilai sembarang/ngawur.
     *   3. NULL kalau dua-duanya tidak tersedia (tamu tanpa pilihan valid).
     *
     * Kenapa penting: kolom ini yang jadi dasar WHERE di dashboard Admin_Kabkota.
     * Sebelum ini nilainya diambil mentah dari $_POST, jadi pemohon bisa
     * mengarahkan pengajuannya ke admin wilayah manapun, atau mengirim nilai
     * kosong/ngawur supaya barisnya tidak muncul di dashboard siapa pun.
     * Lihat docs/engineering/AUDIT_ROLE_ADMIN_SCOPED.md temuan #1 (tingkat Tinggi).
     *
     * ASUMSI PRODUK: untuk pemohon yang sudah login dan profilnya punya
     * kabupaten, domisili profil MENANG atas pilihan dropdown. Kalau nanti
     * kebijakannya "warga boleh mengajukan ke kabupaten lain", ubah di sini —
     * satu tempat, bukan tersebar di controller.
     */
    public function resolve_kabupaten_id($user_id = NULL, $requested_id = NULL) {
        if ( ! empty($user_id)) {
            $profil = $this->db->select('kabupaten_id')
                ->get_where('usr_users', ['id' => (int) $user_id])->row();
            if ($profil && ! empty($profil->kabupaten_id)) {
                return (int) $profil->kabupaten_id;
            }
        }

        $requested_id = (int) $requested_id;
        if ($requested_id > 0 && $this->db->where('id', $requested_id)->count_all_results('kabupaten') > 0) {
            return $requested_id;
        }

        return NULL;
    }

    /**
     * Satu-satunya pintu masuk baris sf_housing_queue. Pemanggil WAJIB
     * menyertakan key 'kabupaten_id' (boleh bernilai NULL) yang sudah lewat
     * resolve_kabupaten_id() — kalau lupa, dicatat ke log dan dipaksa NULL,
     * supaya kelalaian terlihat di log alih-alih diam-diam menghasilkan baris
     * yang tidak pernah muncul di dashboard admin manapun (kasus lama
     * Program::ajukan_solusi(), lihat AUDIT_ROLE_WARGA.md temuan #4).
     */
    public function insert_housing_queue($data) {
        if (empty($data['ticket_code'])) {
            $data['ticket_code'] = $this->generate_ticket_code();
        }
        if ( ! array_key_exists('kabupaten_id', $data)) {
            log_message('error', 'insert_housing_queue dipanggil tanpa kabupaten_id — baris tidak akan terlihat admin kabupaten manapun. Pakai resolve_kabupaten_id() di pemanggil.');
            $data['kabupaten_id'] = NULL;
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
