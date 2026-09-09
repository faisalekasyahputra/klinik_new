<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Srp2_account_flow extends CI_Migration {

    public function up()
    {
        $this->db->query("
            ALTER TABLE `usr_users`
              ADD COLUMN IF NOT EXISTS `nama_perusahaan` VARCHAR(150) NULL AFTER `alamat`,
              ADD COLUMN IF NOT EXISTS `alamat_kantor` TEXT NULL AFTER `nama_perusahaan`,
              ADD COLUMN IF NOT EXISTS `telp_kantor` VARCHAR(20) NULL AFTER `alamat_kantor`;
        ");
    }

    public function down()
    {
        $this->db->query("
            ALTER TABLE `usr_users`
              DROP COLUMN IF EXISTS `nama_perusahaan`,
              DROP COLUMN IF EXISTS `alamat_kantor`,
              DROP COLUMN IF EXISTS `telp_kantor`;
        ");
    }
}
