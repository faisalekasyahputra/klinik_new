<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Housing_queue_ticket extends CI_Migration {

    public function up()
    {
        $this->db->query("
            ALTER TABLE `sf_housing_queue`
              ADD COLUMN IF NOT EXISTS `ticket_code` VARCHAR(10) NULL AFTER `id`;
        ");
        if ( ! $this->_key_exists('sf_housing_queue', 'uq_sf_housing_queue_ticket_code'))
        {
            $this->db->query("
                ALTER TABLE `sf_housing_queue`
                  ADD UNIQUE KEY `uq_sf_housing_queue_ticket_code` (`ticket_code`);
            ");
        }
    }

    public function down()
    {
        $this->db->query("
            ALTER TABLE `sf_housing_queue`
              DROP COLUMN IF EXISTS `ticket_code`;
        ");
    }

    private function _key_exists($table, $key_name)
    {
        $row = $this->db->query("SHOW KEYS FROM `{$table}` WHERE Key_name = ?", array($key_name))->row();
        return (bool) $row;
    }
}
