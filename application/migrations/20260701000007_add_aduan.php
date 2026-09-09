<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_aduan extends CI_Migration {

    public function up()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `aduan` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `user_id` INT UNSIGNED NULL COMMENT 'FK ke usr_users.id, nullable untuk tamu',
              `nama` VARCHAR(150) NOT NULL,
              `email` VARCHAR(100) NOT NULL,
              `judul` VARCHAR(150) NOT NULL,
              `pesan` TEXT NOT NULL,
              `bidang` VARCHAR(30) NOT NULL DEFAULT 'umum' COMMENT 'perumahan|kawasan|pertanahan|pengembang|umum, dipilih manual oleh pelapor',
              `lampiran` VARCHAR(255) NULL,
              `status` VARCHAR(20) NOT NULL DEFAULT 'Baru' COMMENT 'Baru|Diproses|Selesai',
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_user_id` (`user_id`),
              KEY `idx_bidang` (`bidang`),
              KEY `idx_status` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS `aduan`;");
    }
}
