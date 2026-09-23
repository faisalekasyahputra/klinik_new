<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tanggal sertifikat KKN diisi manual oleh admin (daftar revisi dinas, 23 Sep 2026). Sertifikat
 * tidak bisa dicetak sebelum tanggal ini diisi, dan tanggal inilah yang tercetak, bukan hari
 * pencetakan. Hanya menambah kolom nullable; tidak ada data yang diubah.
 */
class Migration_Kkn_tanggal_sertifikat extends CI_Migration {
    public function up() {
        if ( ! $this->db->field_exists('tanggal_sertifikat', 'kkn_magang_pendaftaran')) {
            $this->dbforge->add_column('kkn_magang_pendaftaran', [
                'tanggal_sertifikat' => ['type' => 'DATE', 'null' => TRUE, 'after' => 'link_dokumentasi'],
            ]);
        }
        if ( ! $this->db->field_exists('tanggal_sertifikat', 'kkn_magang_pendaftaran')) {
            throw new RuntimeException('Kolom tanggal_sertifikat belum terbentuk.');
        }
    }

    public function down() {
        if ($this->db->field_exists('tanggal_sertifikat', 'kkn_magang_pendaftaran')) {
            if ($this->db->where('tanggal_sertifikat IS NOT NULL', NULL, FALSE)->count_all_results('kkn_magang_pendaftaran')) {
                throw new RuntimeException('Rollback ditolak: tanggal sertifikat sudah terisi.');
            }
            $this->dbforge->drop_column('kkn_magang_pendaftaran', 'tanggal_sertifikat');
        }
    }
}
