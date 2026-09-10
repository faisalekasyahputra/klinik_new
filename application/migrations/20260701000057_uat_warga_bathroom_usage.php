<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Uat_warga_bathroom_usage extends CI_Migration {
    public function up()
    {
        // Boolean lama hanya menyatakan ada/tidak; tidak membuktikan kepemilikan.
        if (! $this->db->field_exists('bathroom_usage_code', 'sf_penilaian_perumahan')) {
            $this->dbforge->add_column('sf_penilaian_perumahan', [
                'bathroom_usage_code' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => TRUE],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->field_exists('bathroom_usage_code', 'sf_penilaian_perumahan')) {
            if ($this->db->where('bathroom_usage_code IS NOT NULL', NULL, FALSE)->count_all_results('sf_penilaian_perumahan')) {
                throw new RuntimeException('Rollback ditolak: data penggunaan kamar mandi sudah terisi.');
            }
            $this->dbforge->drop_column('sf_penilaian_perumahan', 'bathroom_usage_code');
        }
    }
}
