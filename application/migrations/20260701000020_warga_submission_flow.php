<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Warga_submission_flow extends CI_Migration {

    public function up()
    {
        $this->db->query("ALTER TABLE `sf_housing_queue`
            MODIFY `status_antrean` ENUM('pending','needs_revision','approved','rejected') NOT NULL DEFAULT 'pending',
            MODIFY `nik_pengaju` VARCHAR(255) NULL,
            MODIFY `nama_lengkap` VARCHAR(255) NULL,
            ADD COLUMN IF NOT EXISTS `recommendation_id` BIGINT UNSIGNED NULL AFTER `assessment_id`,
            ADD COLUMN IF NOT EXISTS `submission_key` CHAR(64) NULL AFTER `recommendation_id`");
        if ( ! $this->key_exists('uq_sf_queue_submission_key')) {
            $this->db->query("ALTER TABLE `sf_housing_queue`
                ADD UNIQUE KEY `uq_sf_queue_submission_key` (`submission_key`),
                ADD KEY `idx_sf_queue_recommendation` (`recommendation_id`)");
        }
        if ( ! $this->constraint_exists('fk_sf_queue_recommendation')) {
            $this->db->query("ALTER TABLE `sf_housing_queue`
                ADD CONSTRAINT `fk_sf_queue_recommendation`
                FOREIGN KEY (`recommendation_id`) REFERENCES `sf_rekomendasi_penilaian` (`id`) ON DELETE SET NULL");
        }
        $this->db->query("CREATE TABLE IF NOT EXISTS `sf_riwayat_keputusan_antrean` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `queue_id` INT NOT NULL,
            `from_status` VARCHAR(30) NULL,
            `to_status` VARCHAR(30) NOT NULL,
            `note` TEXT NULL,
            `actor_id` INT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`), KEY `idx_sf_riwayat_queue` (`queue_id`),
            CONSTRAINT `fk_sf_riwayat_queue` FOREIGN KEY (`queue_id`) REFERENCES `sf_housing_queue` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_sf_riwayat_actor` FOREIGN KEY (`actor_id`) REFERENCES `usr_users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS `sf_riwayat_keputusan_antrean`");
        $this->db->query("UPDATE `sf_housing_queue` SET `status_antrean`='pending' WHERE `status_antrean`='needs_revision'");
        if ($this->constraint_exists('fk_sf_queue_recommendation')) {
            $this->db->query("ALTER TABLE `sf_housing_queue` DROP FOREIGN KEY `fk_sf_queue_recommendation`");
        }
        if ($this->key_exists('uq_sf_queue_submission_key')) {
            $this->db->query("ALTER TABLE `sf_housing_queue` DROP KEY `uq_sf_queue_submission_key`, DROP KEY `idx_sf_queue_recommendation`");
        }
        $this->db->query("ALTER TABLE `sf_housing_queue`
            DROP COLUMN IF EXISTS `submission_key`, DROP COLUMN IF EXISTS `recommendation_id`,
            MODIFY `status_antrean` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending'");
    }

    private function key_exists($name)
    {
        return (bool) $this->db->query("SHOW KEYS FROM `sf_housing_queue` WHERE Key_name = ?", [$name])->row();
    }

    private function constraint_exists($name)
    {
        return (bool) $this->db->query("SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME='sf_housing_queue' AND CONSTRAINT_NAME=? LIMIT 1", [$name])->row();
    }
}
