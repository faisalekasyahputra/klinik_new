<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * BNBA per sumber dana (permintaan dinas 27 Okt 2026): daftar penerima diunggah
 * sebagai Excel DI form sumber dana, bukan satu PDF per laporan. Berkas Excel
 * disimpan privat (seperti BNBA lama); yang masuk DB hanya metadata dan tiga
 * angka hasil bacaan (jumlah penerima, total material, total padat karya).
 * NIK/nama/alamat penerima TIDAK disalin ke baris DB - satu salinan di berkas
 * privat, bukan dua.
 *
 * Menempel ke `rd_perumahan_baris.id` dengan CASCADE: menghapus sumber dana (atau
 * mencabut centang program) ikut menghapus baris ini. Berkasnya di disk dibuang
 * pemanggil, lihat Rekam_data_model::berkas_bnba_sumber().
 *
 * `rd_perumahan_bnba` (satu berkas per laporan) TIDAK disentuh: laporan lama
 * yang memilikinya tetap sah dan tetap memenuhi gerbang kirim.
 */
class Migration_Bnba_per_sumber_dana extends CI_Migration {

    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `rd_perumahan_bnba_sumber` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `baris_id` BIGINT UNSIGNED NOT NULL,
            `nama_asli` VARCHAR(255) NOT NULL,
            `path_privat` VARCHAR(255) NOT NULL,
            `mime_type` VARCHAR(100) NOT NULL,
            `ukuran` INT UNSIGNED NOT NULL DEFAULT 0,
            `jumlah_penerima` INT UNSIGNED NOT NULL DEFAULT 0,
            `total_material` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'rupiah penuh',
            `total_padat_karya` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'rupiah penuh',
            `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `uploaded_by` INT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_rd_bnba_sumber_baris` (`baris_id`),
            KEY `idx_rd_bnba_sumber_uploader` (`uploaded_by`),
            CONSTRAINT `fk_rd_bnba_sumber_baris` FOREIGN KEY (`baris_id`) REFERENCES `rd_perumahan_baris` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_rd_bnba_sumber_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `usr_akun` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
          COMMENT='rd_ = rekam data laporan kabupaten/kota. BNBA (daftar penerima, Excel) per sumber dana laporan perumahan, berkas privat.'");
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS `rd_perumahan_bnba_sumber`');
    }
}
