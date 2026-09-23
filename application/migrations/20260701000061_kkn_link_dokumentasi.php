<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Link dokumentasi KKN di penyimpanan cloud (daftar revisi dinas, 23 Sep 2026): universitas
 * mengisi satu URL (Google Drive, OneDrive, dsb.) per KKN dari dashboardnya, admin melihatnya.
 * Hanya menambah kolom nullable; tidak ada data yang diubah.
 */
class Migration_Kkn_link_dokumentasi extends CI_Migration {
    public function up() {
        if ( ! $this->db->field_exists('link_dokumentasi', 'kkn_magang_pendaftaran')) {
            $this->dbforge->add_column('kkn_magang_pendaftaran', [
                'link_dokumentasi' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => TRUE, 'after' => 'file_laporan_akhir'],
            ]);
        }
        if ( ! $this->db->field_exists('link_dokumentasi', 'kkn_magang_pendaftaran')) {
            throw new RuntimeException('Kolom link_dokumentasi belum terbentuk.');
        }
    }

    public function down() {
        if ($this->db->field_exists('link_dokumentasi', 'kkn_magang_pendaftaran')) {
            if ($this->db->where('link_dokumentasi IS NOT NULL', NULL, FALSE)->count_all_results('kkn_magang_pendaftaran')) {
                throw new RuntimeException('Rollback ditolak: link dokumentasi sudah terisi.');
            }
            $this->dbforge->drop_column('kkn_magang_pendaftaran', 'link_dokumentasi');
        }
    }
}
