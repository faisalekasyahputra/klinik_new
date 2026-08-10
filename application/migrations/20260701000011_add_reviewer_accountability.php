<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Opsi A dari docs/architecture/DESAIN_NORMALISASI_SKEMA_ROLE.md, kontrak #2:
 * kolom akuntabilitas reviewer seragam di semua tabel pengajuan yang punya
 * alur approve/reject. Sebelum migrasi ini, tidak ada satu pun tabel yang
 * mencatat SIAPA admin yang memutuskan status - cuma status & catatan_admin.
 *
 * Diisi oleh controller admin terkait (Admin_Srp2, Admin_Bidang, Admin_Kemitraan,
 * Admin_Kabkota) saat mengubah status - lihat masing-masing controller untuk
 * implementasi penulisan reviewed_by/reviewed_at (di luar cakupan migrasi ini).
 *
 * `reviewed_by` sengaja INT biasa (BUKAN UNSIGNED) - usr_users.id adalah
 * int(11) signed (peninggalan skema lama), sedangkan hampir semua kolom
 * "*_id" lain di aplikasi ini unsigned. MySQL menolak FOREIGN KEY antar
 * kolom dengan signedness berbeda (errno 150), jadi kolom yang menunjuk ke
 * usr_users.id harus ikut signed - persis seperti sf_housing_queue.user_id
 * yang sudah begini sejak awal.
 */
class Migration_Add_reviewer_accountability extends CI_Migration {

    private $tables = ['srp2_registrations', 'aduan', 'kkn_magang_pendaftaran', 'sf_housing_queue'];

    public function up()
    {
        foreach ($this->tables as $table) {
            $this->db->query("
                ALTER TABLE `{$table}`
                  ADD COLUMN IF NOT EXISTS `reviewed_by` INT NULL COMMENT 'FK usr_users.id (signed, lihat catatan class) - admin yang memutuskan status',
                  ADD COLUMN IF NOT EXISTS `reviewed_at` DATETIME NULL COMMENT 'Kapan status terakhir diputuskan admin';
            ");

            $fk_name = "fk_{$table}_reviewed_by";
            if ( ! $this->_key_exists($table, $fk_name)) {
                $this->db->query("
                    ALTER TABLE `{$table}`
                      ADD KEY `idx_{$table}_reviewed_by` (`reviewed_by`),
                      ADD CONSTRAINT `{$fk_name}` FOREIGN KEY (`reviewed_by`) REFERENCES `usr_users` (`id`) ON DELETE SET NULL;
                ");
            }
        }
    }

    public function down()
    {
        foreach ($this->tables as $table) {
            $fk_name = "fk_{$table}_reviewed_by";
            if ($this->_key_exists($table, $fk_name)) {
                $this->db->query("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk_name}`;");
            }
            $this->db->query("ALTER TABLE `{$table}` DROP COLUMN IF EXISTS `reviewed_by`;");
            $this->db->query("ALTER TABLE `{$table}` DROP COLUMN IF EXISTS `reviewed_at`;");
        }
    }

    private function _key_exists($table, $key_name)
    {
        $row = $this->db->query("SHOW KEYS FROM `{$table}` WHERE Key_name = ?", [$key_name])->row();
        return (bool) $row;
    }
}
