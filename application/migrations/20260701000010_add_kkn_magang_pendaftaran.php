<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pendaftaran KKN/Magang nyata untuk role mahasiswa — sebelumnya
 * halaman KemitraanPortal cuma statis tanpa form/tabel sama sekali.
 */
class Migration_Add_kkn_magang_pendaftaran extends CI_Migration {

    public function up()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `kkn_magang_pendaftaran` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `user_id` INT UNSIGNED NOT NULL COMMENT 'FK ke usr_users.id, wajib login',
              `jenis` ENUM('kkn','magang') NOT NULL,
              `instansi_asal` VARCHAR(150) NOT NULL COMMENT 'Nama kampus/universitas',
              `no_hp` VARCHAR(15) NOT NULL,
              `divisi_atau_tema` VARCHAR(150) NULL COMMENT 'Divisi (magang) atau tema (KKN)',
              `periode_mulai` DATE NULL,
              `periode_selesai` DATE NULL,
              `file_surat_pengantar` VARCHAR(255) NULL,
              `status` VARCHAR(20) NOT NULL DEFAULT 'Diajukan' COMMENT 'Diajukan|Diterima|Ditolak',
              `catatan_admin` TEXT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_kkn_magang_user_id` (`user_id`),
              KEY `idx_kkn_magang_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS `kkn_magang_pendaftaran`;");
    }
}
