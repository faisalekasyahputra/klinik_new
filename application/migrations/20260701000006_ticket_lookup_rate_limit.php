<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Ticket_lookup_rate_limit extends CI_Migration {

    public function up()
    {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `sys_ticket_lookup_limits` (
              `ip_hash` CHAR(64) NOT NULL,
              `window_started_at` DATETIME NOT NULL,
              `failed_attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
              PRIMARY KEY (`ip_hash`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    public function down()
    {
        $this->db->query("DROP TABLE IF EXISTS `sys_ticket_lookup_limits`;");
    }
}
