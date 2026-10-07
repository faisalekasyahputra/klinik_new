<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Nomor sertifikat KKN per peserta yang bisa diubah admin dinas (permintaan dinas 7 Okt 2026).
 *
 * - nomor_sertifikat: NULL = nomor otomatis seperti sebelumnya (600.2/69. + kkn_peserta.id),
 *   terisi = nomor yang ditetapkan admin lewat halaman Peserta KKN. Unik supaya dua sertifikat
 *   tidak bernomor kembar (NULL boleh banyak).
 */
class Migration_Nomor_sertifikat_kkn extends CI_Migration {

    public function up()
    {
        if ( ! $this->db->field_exists('nomor_sertifikat', 'kkn_peserta')) {
            $this->dbforge->add_column('kkn_peserta', [
                'nomor_sertifikat' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => TRUE, 'after' => 'nama'],
            ]);
            $this->db->query('ALTER TABLE kkn_peserta ADD UNIQUE KEY uq_kkn_peserta_nomor_sertifikat (nomor_sertifikat)');
        }
    }

    public function down()
    {
        if ($this->db->field_exists('nomor_sertifikat', 'kkn_peserta')) {
            $this->db->query('ALTER TABLE kkn_peserta DROP KEY uq_kkn_peserta_nomor_sertifikat');
            $this->dbforge->drop_column('kkn_peserta', 'nomor_sertifikat');
        }
    }
}
