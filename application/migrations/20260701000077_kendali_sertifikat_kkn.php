<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kendali sertifikat KKN di tangan admin dinas (keputusan user 7 Okt 2026).
 *
 * - dicatat_oleh: admin yang mencatat KKN atas nama universitas lewat Admin_Kemitraan::catat
 *   (KKN yang berjalan sebelum aplikasi selesai). NULL = diajukan universitas sendiri.
 * - sertifikat_diminta_at / sertifikat_diminta_jumlah: permintaan sertifikat dari mahasiswa lewat
 *   halaman cek sertifikat (NIM ditemukan, periode lewat, tanggal sertifikat belum ditetapkan).
 *   Dikosongkan saat admin menetapkan tanggal sertifikat atau mengabaikan permintaannya.
 */
class Migration_Kendali_sertifikat_kkn extends CI_Migration {

    public function up()
    {
        $t = 'kkn_magang_pendaftaran';
        if ( ! $this->db->field_exists('dicatat_oleh', $t)) {
            $this->dbforge->add_column($t, [
                'dicatat_oleh' => ['type' => 'INT', 'constraint' => 11, 'null' => TRUE, 'after' => 'alasan_susulan'],
                'sertifikat_diminta_at' => ['type' => 'DATETIME', 'null' => TRUE, 'after' => 'tanggal_sertifikat'],
                'sertifikat_diminta_jumlah' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => TRUE, 'null' => FALSE, 'default' => 0, 'after' => 'sertifikat_diminta_at'],
            ]);
            $this->db->query('ALTER TABLE kkn_magang_pendaftaran ADD CONSTRAINT fk_kkn_dicatat_oleh
                FOREIGN KEY (dicatat_oleh) REFERENCES usr_akun (id) ON DELETE SET NULL');
            $this->db->query('ALTER TABLE kkn_magang_pendaftaran ADD KEY idx_kkn_sertifikat_diminta (sertifikat_diminta_at)');
        }
    }

    public function down()
    {
        if ($this->db->field_exists('dicatat_oleh', 'kkn_magang_pendaftaran')) {
            $this->db->query('ALTER TABLE kkn_magang_pendaftaran DROP FOREIGN KEY fk_kkn_dicatat_oleh');
            $this->db->query('ALTER TABLE kkn_magang_pendaftaran DROP KEY idx_kkn_sertifikat_diminta');
            $this->db->query('ALTER TABLE kkn_magang_pendaftaran DROP KEY fk_kkn_dicatat_oleh');
            foreach (['sertifikat_diminta_jumlah', 'sertifikat_diminta_at', 'dicatat_oleh'] as $k) {
                $this->dbforge->drop_column('kkn_magang_pendaftaran', $k);
            }
        }
    }
}
