<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Daftar dosen pembimbing lapangan (DPL) per KKN untuk piagam penghargaan (permintaan dinas 1 Nov 2026).
 * Diisi dari unggahan Excel universitas (kolom Nama, NIDN opsional), pola yang sama dengan kkn_peserta.
 */
class Migration_Kkn_dpl extends CI_Migration {

    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `kkn_dpl` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `pendaftaran_id` INT UNSIGNED NOT NULL,
            `nidn` VARCHAR(30) NULL,
            `nama` VARCHAR(150) NOT NULL,
            `urut` INT UNSIGNED NOT NULL,
            `created_at` DATETIME NULL,
            PRIMARY KEY (`id`),
            KEY `idx_kkn_dpl_pendaftaran` (`pendaftaran_id`),
            CONSTRAINT `fk_kkn_dpl_pendaftaran` FOREIGN KEY (`pendaftaran_id`) REFERENCES `kkn_magang_pendaftaran` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
          COMMENT='kkn_ = KKN dan magang mahasiswa. Dosen pembimbing lapangan satu KKN untuk piagam penghargaan.'");
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS `kkn_dpl`');
    }
}
