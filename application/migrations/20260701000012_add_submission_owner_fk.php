<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Opsi A dari docs/architecture/DESAIN_NORMALISASI_SKEMA_ROLE.md, kontrak #1:
 * user_id di tabel pengajuan selalu FK sungguhan, dengan ON DELETE dipilih
 * sadar per domain - bukan cuma KEY biasa seperti sebelumnya.
 *
 * - aduan.user_id -> SET NULL: baris aduan tetap relevan buat admin_bidang
 *   walau pelapornya sudah hapus akun (konsisten dengan sf_housing_queue.user_id
 *   yang sudah begini sejak migrasi housing_queue_ticket).
 * - kkn_magang_pendaftaran.user_id -> CASCADE: data murni milik pribadi
 *   mahasiswa, tidak berguna lagi tanpa pemiliknya (lihat AUDIT_ROLE_MAHASISWA.md
 *   Temuan #6 - sebelumnya baris ini jadi yatim permanen saat akun dihapus).
 * - srp2_registrations.user_id -> CASCADE: draft/pending/ditolak yang belum
 *   masuk direktori publik (srp2_certified_developers, tabel terpisah) tidak
 *   perlu bertahan tanpa pemiliknya.
 *
 * PENTING: sebelum migrasi ini dijalankan di lingkungan mana pun, pastikan
 * application/models/User_model.php::delete_user_account() SUDAH diperbarui
 * untuk unlink file fisik (private_uploads/srp2/{id}/, .assets/uploads/) SEBELUM
 * baris usr_users dihapus - begitu CASCADE menghapus baris DB, tidak ada lagi
 * cara menemukan nama file yang harus dihapus dari disk. Lihat commit yang
 * menyertakan migrasi ini untuk perubahan User_model yang dimaksud.
 *
 * Baris orphan (user_id menunjuk akun yang sudah lama terhapus dari masa
 * sebelum FK ini ada) dibersihkan dulu sebelum constraint ditambahkan, supaya
 * ALTER TABLE tidak gagal karena data lama melanggar constraint baru.
 *
 * CATATAN PENTING (ditemukan saat uji coba migrasi ini di lokal): usr_users.id
 * adalah int(11) SIGNED (peninggalan skema lama), sedangkan user_id di ketiga
 * tabel ini didefinisikan UNSIGNED sejak migrasi awalnya masing-masing. MySQL
 * menolak FOREIGN KEY antar kolom dengan signedness berbeda (errno 150), jadi
 * ketiga kolom ini di-MODIFY ke signed INT dulu sebelum constraint ditambahkan
 * - nilai existing tidak berubah (semua ID sudah pasti non-negatif), cuma
 * definisi tipenya yang disamakan dengan usr_users.id.
 */
class Migration_Add_submission_owner_fk extends CI_Migration {

    public function up()
    {
        // --- aduan: samakan tipe ke signed, anonim-kan orphan, lalu SET NULL on delete ---
        $this->db->query("ALTER TABLE `aduan` MODIFY COLUMN `user_id` INT NULL COMMENT 'FK ke usr_users.id, nullable untuk tamu';");
        $this->db->query("
            UPDATE `aduan` SET `user_id` = NULL
            WHERE `user_id` IS NOT NULL AND `user_id` NOT IN (SELECT `id` FROM `usr_users`);
        ");
        if ( ! $this->_key_exists('aduan', 'fk_aduan_user_id')) {
            $this->db->query("
                ALTER TABLE `aduan`
                  ADD CONSTRAINT `fk_aduan_user_id` FOREIGN KEY (`user_id`) REFERENCES `usr_users` (`id`) ON DELETE SET NULL;
            ");
        }

        // --- kkn_magang_pendaftaran: samakan tipe, hapus orphan, lalu CASCADE on delete ---
        $this->db->query("ALTER TABLE `kkn_magang_pendaftaran` MODIFY COLUMN `user_id` INT NOT NULL COMMENT 'FK ke usr_users.id, wajib login';");
        $this->db->query("
            DELETE FROM `kkn_magang_pendaftaran`
            WHERE `user_id` NOT IN (SELECT `id` FROM `usr_users`);
        ");
        if ( ! $this->_key_exists('kkn_magang_pendaftaran', 'fk_kkn_magang_user_id')) {
            $this->db->query("
                ALTER TABLE `kkn_magang_pendaftaran`
                  ADD CONSTRAINT `fk_kkn_magang_user_id` FOREIGN KEY (`user_id`) REFERENCES `usr_users` (`id`) ON DELETE CASCADE;
            ");
        }

        // --- srp2_registrations: samakan tipe, hapus orphan (kolom sudah nullable), lalu CASCADE ---
        $this->db->query("ALTER TABLE `srp2_registrations` MODIFY COLUMN `user_id` INT NULL COMMENT 'FK ke usr_users.id, nullable untuk kompatibilitas data lama';");
        $this->db->query("
            DELETE FROM `srp2_registrations`
            WHERE `user_id` IS NOT NULL AND `user_id` NOT IN (SELECT `id` FROM `usr_users`);
        ");
        if ( ! $this->_key_exists('srp2_registrations', 'fk_srp2_registrations_user_id')) {
            $this->db->query("
                ALTER TABLE `srp2_registrations`
                  ADD CONSTRAINT `fk_srp2_registrations_user_id` FOREIGN KEY (`user_id`) REFERENCES `usr_users` (`id`) ON DELETE CASCADE;
            ");
        }
    }

    public function down()
    {
        if ($this->_key_exists('srp2_registrations', 'fk_srp2_registrations_user_id')) {
            $this->db->query("ALTER TABLE `srp2_registrations` DROP FOREIGN KEY `fk_srp2_registrations_user_id`;");
        }
        if ($this->_key_exists('kkn_magang_pendaftaran', 'fk_kkn_magang_user_id')) {
            $this->db->query("ALTER TABLE `kkn_magang_pendaftaran` DROP FOREIGN KEY `fk_kkn_magang_user_id`;");
        }
        if ($this->_key_exists('aduan', 'fk_aduan_user_id')) {
            $this->db->query("ALTER TABLE `aduan` DROP FOREIGN KEY `fk_aduan_user_id`;");
        }
        // Data yang sudah dibersihkan/di-cascade oleh up() tidak dipulihkan - sama seperti
        // pola down() migrasi lain di repo ini (mis. 20260701000008_add_kabupaten.php).
    }

    private function _key_exists($table, $key_name)
    {
        $row = $this->db->query("SHOW KEYS FROM `{$table}` WHERE Key_name = ?", [$key_name])->row();
        return (bool) $row;
    }
}
