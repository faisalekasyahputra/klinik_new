<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Permintaan dinas 26 Okt 2026: pendataan warga yang hasilnya "Hunian Anda sudah layak huni" tetap tersimpan
 * (masuk antrean, tercatat di unduhan Excel) walau tidak ada program yang dipilih. Antrean semacam itu
 * program_id-nya NULL; semua join antrean ke sf_program sudah LEFT JOIN. Kode lama tidak pernah menulis NULL,
 * jadi migrasi ini aman dijalankan sebelum kode baru tayang.
 */
class Migration_Antrean_tanpa_program extends CI_Migration {

    public function up()
    {
        $this->db->query("ALTER TABLE sf_antrean_pengajuan MODIFY program_id INT(11) NULL
            COMMENT 'Program yang dipilih warga; NULL = hunian sudah layak huni, data dicatat tanpa program'");
    }

    public function down()
    {
        if ($this->db->where('program_id IS NULL', NULL, FALSE)->count_all_results('sf_antrean_pengajuan')) {
            throw new RuntimeException('Ada antrean hunian layak tanpa program; tidak bisa kembali ke program_id NOT NULL.');
        }
        $this->db->query('ALTER TABLE sf_antrean_pengajuan MODIFY program_id INT(11) NOT NULL');
    }
}
