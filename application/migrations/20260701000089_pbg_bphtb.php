<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rekam Data PBG dan BPHTB (permintaan dinas 30 Okt 2026): "langsung di model
 * kayak upload BNBA". Satu berkas Excel per kabupaten per jenis per BULAN;
 * berkasnya privat, yang masuk DB hanya metadata dan jumlah baris hasil bacaan.
 * NIK/nama/alamat TIDAK disalin ke DB - satu salinan di berkas privat, sama
 * dengan rd_perumahan_bnba_sumber (migrasi 088).
 *
 * Tanpa status kirim/tinjau: dinas meminta unggah saja. Mengunggah lagi untuk
 * bulan yang sama menggantikan berkas lama (UNIQUE jenis+kabupaten+tahun+bulan).
 */
class Migration_Pbg_bphtb extends CI_Migration {

    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `rd_pbg_bphtb` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `jenis` ENUM('pbg','bphtb') NOT NULL,
            `kabupaten_id` INT UNSIGNED NOT NULL,
            `tahun` SMALLINT UNSIGNED NOT NULL,
            `bulan` TINYINT UNSIGNED NOT NULL,
            `nama_asli` VARCHAR(255) NOT NULL,
            `path_privat` VARCHAR(255) NOT NULL,
            `mime_type` VARCHAR(100) NOT NULL,
            `ukuran` INT UNSIGNED NOT NULL DEFAULT 0,
            `jumlah_baris` INT UNSIGNED NOT NULL DEFAULT 0,
            `ringkasan` VARCHAR(255) NULL COMMENT 'mis. jumlah pengembang dan perorangan PBG',
            `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `uploaded_by` INT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_rd_pbg_bphtb_periode` (`jenis`, `kabupaten_id`, `tahun`, `bulan`),
            KEY `idx_rd_pbg_bphtb_kab` (`kabupaten_id`),
            CONSTRAINT `fk_rd_pbg_bphtb_kabupaten` FOREIGN KEY (`kabupaten_id`) REFERENCES `kabupaten` (`id`),
            KEY `idx_rd_pbg_bphtb_uploader` (`uploaded_by`),
            CONSTRAINT `fk_rd_pbg_bphtb_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `usr_akun` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
          COMMENT='rd_ = rekam data kabupaten/kota. Berkas Excel PBG/BPHTB per bulan, berkas privat.'");
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS `rd_pbg_bphtb`');
    }
}
