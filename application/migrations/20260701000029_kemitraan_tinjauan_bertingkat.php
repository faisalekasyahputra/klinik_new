<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Peninjauan bertingkat KKN/Magang + surat balasan.
 *
 * Alur lama satu tahap: Diajukan -> Diterima/Ditolak, satu `reviewed_by`.
 * Kenyataannya surat pengantar mahasiswa melewati DUA meja - sekretariat
 * Disperakim lebih dulu, lalu bidang yang membawahi divisi tujuannya - dan
 * berakhir dengan surat balasan resmi yang diunduh mahasiswa.
 *
 * `bidang_kode` di tabel divisi adalah inti perubahan ini. Divisi memang anak
 * dari bidang di struktur dinas; hubungan itu selama ini tidak pernah tertulis
 * di DB, jadi tidak ada cara menentukan bidang mana yang harus meninjau sebuah
 * pendaftaran. NULL berarti belum ditetapkan - dan pendaftaran ke divisi
 * seperti itu akan berhenti di tahap dua, jadi layar admin WAJIB menampilkan
 * divisi yang belum punya bidang. Sengaja tidak diberi nilai bawaan: menebak
 * pemetaan struktur organisasi lebih buruk daripada mengaku belum tahu.
 *
 * Jejak peninjau tahap dua ditaruh di kolom TERSENDIRI, bukan menimpa
 * reviewed_by/reviewed_at yang sudah ada. Menimpanya berarti kehilangan siapa
 * yang meneruskan begitu bidang memutuskan - dan pertanyaan "siapa yang
 * meloloskan ini ke tahap dua" justru yang paling sering ditanyakan ketika ada
 * yang keliru.
 *
 * `bidang_kode` VARCHAR(30) mengikuti `bidang.kode` persis, termasuk
 * collationnya - lihat AGENTS.md §0e soal errno 150 kalau tipe rujukan beda.
 */
class Migration_Kemitraan_tinjauan_bertingkat extends CI_Migration {

    public function up()
    {
        if ( ! $this->db->field_exists('bidang_kode', 'kkn_magang_divisi')) {
            $this->db->query("ALTER TABLE `kkn_magang_divisi`
                ADD COLUMN `bidang_kode` VARCHAR(30) NULL
                    COMMENT 'Bidang yang membawahi divisi ini; NULL = belum ditetapkan' AFTER `nama`,
                ADD KEY `idx_divisi_bidang` (`bidang_kode`),
                ADD CONSTRAINT `fk_divisi_bidang` FOREIGN KEY (`bidang_kode`)
                    REFERENCES `bidang` (`kode`) ON DELETE SET NULL");
        }

        if ( ! $this->db->field_exists('reviewed_by_bidang', 'kkn_magang_pendaftaran')) {
            $this->db->query("ALTER TABLE `kkn_magang_pendaftaran`
                ADD COLUMN `reviewed_by_bidang` INT NULL
                    COMMENT 'FK usr_users.id - admin bidang yang memutuskan tahap dua' AFTER `reviewed_at`,
                ADD COLUMN `reviewed_at_bidang` DATETIME NULL AFTER `reviewed_by_bidang`,
                ADD COLUMN `catatan_bidang` TEXT NULL
                    COMMENT 'Catatan peninjau tahap dua; terpisah dari catatan_admin' AFTER `reviewed_at_bidang`,
                ADD KEY `idx_kkn_reviewed_by_bidang` (`reviewed_by_bidang`),
                ADD CONSTRAINT `fk_kkn_reviewed_by_bidang` FOREIGN KEY (`reviewed_by_bidang`)
                    REFERENCES `usr_users` (`id`) ON DELETE SET NULL");
        }

        if ( ! $this->db->field_exists('file_surat_balasan', 'kkn_magang_pendaftaran')) {
            // Disimpan di private_uploads/kemitraan/{id}/ bersama surat
            // pengantar dan proposal - bukan di webroot. Surat balasan memuat
            // nama, instansi, dan periode seseorang; ia bukan berkas publik
            // hanya karena isinya kabar baik.
            $this->db->query("ALTER TABLE `kkn_magang_pendaftaran`
                ADD COLUMN `file_surat_balasan` VARCHAR(255) NULL
                    COMMENT 'PDF bertanda tangan yang diunggah superadmin; diunduh mahasiswa lewat endpoint ber-guard'
                    AFTER `file_proposal`");
        }
    }

    public function down()
    {
        if ($this->db->field_exists('bidang_kode', 'kkn_magang_divisi')) {
            $this->db->query("ALTER TABLE `kkn_magang_divisi`
                DROP FOREIGN KEY `fk_divisi_bidang`");
            $this->db->query("ALTER TABLE `kkn_magang_divisi` DROP COLUMN `bidang_kode`");
        }
        if ($this->db->field_exists('reviewed_by_bidang', 'kkn_magang_pendaftaran')) {
            $this->db->query("ALTER TABLE `kkn_magang_pendaftaran`
                DROP FOREIGN KEY `fk_kkn_reviewed_by_bidang`");
            $this->db->query("ALTER TABLE `kkn_magang_pendaftaran`
                DROP COLUMN `reviewed_by_bidang`, DROP COLUMN `reviewed_at_bidang`, DROP COLUMN `catatan_bidang`");
        }
        if ($this->db->field_exists('file_surat_balasan', 'kkn_magang_pendaftaran')) {
            $this->db->query("ALTER TABLE `kkn_magang_pendaftaran` DROP COLUMN `file_surat_balasan`");
        }
    }
}
