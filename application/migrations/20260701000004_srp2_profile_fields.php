<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Srp2_profile_fields extends CI_Migration {

    public function up()
    {
        $this->db->query("
            ALTER TABLE `srp2_certified_developers`
              ADD COLUMN IF NOT EXISTS `alamat_kantor` TEXT NULL AFTER `nama_perusahaan`,
              ADD COLUMN IF NOT EXISTS `website` VARCHAR(255) NULL AFTER `alamat_kantor`,
              ADD COLUMN IF NOT EXISTS `instagram` VARCHAR(255) NULL AFTER `website`,
              ADD COLUMN IF NOT EXISTS `sosmed_lainnya` VARCHAR(255) NULL AFTER `instagram`;
        ");
    }

    public function down()
    {
        $this->db->query("
            ALTER TABLE `srp2_certified_developers`
              DROP COLUMN IF EXISTS `alamat_kantor`,
              DROP COLUMN IF EXISTS `website`,
              DROP COLUMN IF EXISTS `instagram`,
              DROP COLUMN IF EXISTS `sosmed_lainnya`;
        ");
    }
}
