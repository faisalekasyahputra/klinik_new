<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Langganan Web Push per perangkat admin. Endpoint adalah rahasia kapabilitas. */
class Migration_Web_push_admin extends CI_Migration {
    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `sys_push_subscriptions` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT NOT NULL,
            `endpoint_hash` CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            `endpoint` TEXT NOT NULL,
            `public_key` VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            `auth_token` VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
            `content_encoding` VARCHAR(20) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT 'aes128gcm',
            `user_agent` VARCHAR(255) NULL,
            `aktif` TINYINT(1) NOT NULL DEFAULT 1,
            `gagal_berturut` TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `last_success_at` DATETIME NULL,
            `last_failure_at` DATETIME NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_push_endpoint_hash` (`endpoint_hash`),
            KEY `idx_push_user_active` (`user_id`, `aktif`),
            CONSTRAINT `fk_push_subscription_user` FOREIGN KEY (`user_id`) REFERENCES `usr_users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS `sys_push_subscriptions`");
    }
}
