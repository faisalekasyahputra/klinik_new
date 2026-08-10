<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Opsi (b) dari docs/product/PRD_VERIFIKASI_ADMIN_SRP2.md Fase 0: link
 * berbasis ID antara srp2_registrations dan srp2_certified_developers
 * (direktori publik), menggantikan pencocokan string nama yang rapuh
 * (lihat docs/engineering/AUDIT_ROLE_PENGEMBANG.md Temuan #4). Diisi
 * otomatis oleh Admin_Srp2::proses() saat status diubah jadi Diterima.
 *
 * FK ke srp2_certified_developers.id (INT UNSIGNED, PK tabel itu sendiri,
 * BUKAN usr_users.id) - jadi tidak kena aturan "harus signed" di
 * migrasi 20260701000011/12, keduanya sama-sama unsigned, aman.
 */
class Migration_Add_srp2_certified_developer_link extends CI_Migration {

    public function up()
    {
        $this->db->query("
            ALTER TABLE `srp2_registrations`
              ADD COLUMN IF NOT EXISTS `certified_developer_id` INT UNSIGNED NULL COMMENT 'FK srp2_certified_developers.id, diisi saat status Diterima' AFTER `reviewed_at`;
        ");
        if ( ! $this->_key_exists('srp2_registrations', 'fk_srp2_registrations_certified_developer')) {
            $this->db->query("
                ALTER TABLE `srp2_registrations`
                  ADD KEY `idx_srp2_registrations_certified_developer` (`certified_developer_id`),
                  ADD CONSTRAINT `fk_srp2_registrations_certified_developer` FOREIGN KEY (`certified_developer_id`) REFERENCES `srp2_certified_developers` (`id`) ON DELETE SET NULL;
            ");
        }
    }

    public function down()
    {
        if ($this->_key_exists('srp2_registrations', 'fk_srp2_registrations_certified_developer')) {
            $this->db->query("ALTER TABLE `srp2_registrations` DROP FOREIGN KEY `fk_srp2_registrations_certified_developer`;");
        }
        $this->db->query("ALTER TABLE `srp2_registrations` DROP COLUMN IF EXISTS `certified_developer_id`;");
    }

    private function _key_exists($table, $key_name)
    {
        $row = $this->db->query("SHOW KEYS FROM `{$table}` WHERE Key_name = ?", [$key_name])->row();
        return (bool) $row;
    }
}
