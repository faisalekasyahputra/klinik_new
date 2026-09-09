<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Opsi A dari docs/architecture/DESAIN_NORMALISASI_SKEMA_ROLE.md, kontrak #3:
 * tabel dokumen anak selalu FK ke induknya. srp2_documents.registration_id
 * sejak awal (migrasi 20260701000003) cuma INT biasa tanpa constraint -
 * lihat AUDIT_ROLE_PENGEMBANG.md Temuan #5.
 *
 * ON DELETE CASCADE: dokumen tidak berguna tanpa baris pengajuan induknya.
 * Dijalankan SETELAH 20260701000012 (yang menghapus baris srp2_registrations
 * orphan) supaya urutan cleanup orphan konsisten: registrations dulu, baru
 * documents yang mengacu ke registration yang sudah tidak ada.
 *
 * File fisik di private_uploads/srp2/{registration_id}/ TIDAK ikut terhapus
 * otomatis oleh CASCADE ini (FK cuma bekerja di level baris DB) - pembersihan
 * file tetap tanggung jawab kode (lihat catatan di migrasi 20260701000012
 * soal User_model::delete_user_account()).
 */
class Migration_Add_srp2_documents_fk extends CI_Migration {

    public function up()
    {
        $this->db->query("
            DELETE FROM `srp2_documents`
            WHERE `registration_id` NOT IN (SELECT `id` FROM `srp2_registrations`);
        ");

        if ( ! $this->_key_exists('srp2_documents', 'fk_srp2_documents_registration')) {
            $this->db->query("
                ALTER TABLE `srp2_documents`
                  ADD CONSTRAINT `fk_srp2_documents_registration` FOREIGN KEY (`registration_id`) REFERENCES `srp2_registrations` (`id`) ON DELETE CASCADE;
            ");
        }
    }

    public function down()
    {
        if ($this->_key_exists('srp2_documents', 'fk_srp2_documents_registration')) {
            $this->db->query("ALTER TABLE `srp2_documents` DROP FOREIGN KEY `fk_srp2_documents_registration`;");
        }
    }

    private function _key_exists($table, $key_name)
    {
        $row = $this->db->query("SHOW KEYS FROM `{$table}` WHERE Key_name = ?", [$key_name])->row();
        return (bool) $row;
    }
}
