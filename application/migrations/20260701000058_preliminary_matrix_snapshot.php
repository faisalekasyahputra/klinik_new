<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Preliminary_matrix_snapshot extends CI_Migration {
    public function up()
    {
        if (!$this->db->field_exists('preliminary_matrix_ciphertext', 'sf_penilaian_perumahan')) {
            $this->dbforge->add_column('sf_penilaian_perumahan', [
                'preliminary_matrix_ciphertext'=>['type'=>'MEDIUMTEXT', 'null'=>TRUE],
            ]);
        }
        if (!$this->db->field_exists('preliminary_matrix_ciphertext', 'sf_penilaian_perumahan')) {
            throw new RuntimeException('Kolom snapshot rekomendasi awal belum terbentuk.');
        }
    }

    public function down()
    {
        if ($this->db->field_exists('preliminary_matrix_ciphertext', 'sf_penilaian_perumahan')) {
            if ($this->db->where('preliminary_matrix_ciphertext IS NOT NULL', NULL, FALSE)->count_all_results('sf_penilaian_perumahan')) {
                throw new RuntimeException('Rollback ditolak: rekomendasi awal sudah tersimpan.');
            }
            $this->dbforge->drop_column('sf_penilaian_perumahan', 'preliminary_matrix_ciphertext');
        }
    }
}
