<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Ringkasan angka PBG/BPHTB per bulan (permintaan 30 Okt 2026: isian teks di samping
 * unggah Excel). Tabel terpisah dari rd_pbg_bphtb karena angka boleh diisi tanpa berkas.
 * PBG: jumlah unit dibebaskan retribusi; BPHTB: jumlah penerima dibebaskan BPHTB.
 */
class Migration_Pbg_bphtb_angka extends CI_Migration {

    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `rd_pbg_bphtb_angka` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `jenis` ENUM('pbg','bphtb') NOT NULL,
            `kabupaten_id` INT UNSIGNED NOT NULL,
            `tahun` SMALLINT UNSIGNED NOT NULL,
            `bulan` TINYINT UNSIGNED NOT NULL,
            `jumlah` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'PBG: unit; BPHTB: penerima',
            `nilai_rp` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'nilai retribusi PBG / BPHTB yang dibebaskan',
            `catatan` VARCHAR(500) NULL,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_by` INT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_rd_pbg_bphtb_angka_periode` (`jenis`, `kabupaten_id`, `tahun`, `bulan`),
            KEY `idx_rd_pbg_bphtb_angka_kab` (`kabupaten_id`),
            CONSTRAINT `fk_rd_pbg_bphtb_angka_kabupaten` FOREIGN KEY (`kabupaten_id`) REFERENCES `kabupaten` (`id`),
            KEY `idx_rd_pbg_bphtb_angka_oleh` (`updated_by`),
            CONSTRAINT `fk_rd_pbg_bphtb_angka_oleh` FOREIGN KEY (`updated_by`) REFERENCES `usr_akun` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
          COMMENT='rd_ = rekam data kabupaten/kota. Ringkasan angka PBG/BPHTB per bulan.'");
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS `rd_pbg_bphtb_angka`');
    }
}
