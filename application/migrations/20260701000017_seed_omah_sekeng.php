<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Seed_omah_sekeng extends CI_Migration {

    public function up()
    {
        $this->db->query("
            INSERT IGNORE INTO `sf_programs`
                (`id_kategori`, `kode_program`, `nama_program`, `deskripsi_singkat`, `batas_penghasilan_max`, `is_active`)
            VALUES
                (3, 'omah_sekeng', 'Omah Sekeng', 'Bantuan kolaboratif CSR dan Pemprov untuk masyarakat Desil 4.', 4000000, 1)
        ");
    }

    public function down()
    {
        // Sengaja tidak menghapus program: sf_housing_queue memakai ON DELETE
        // CASCADE, sehingga rollback seed tidak boleh ikut menghapus pengajuan.
    }
}
