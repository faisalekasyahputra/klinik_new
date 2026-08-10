<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * W1 wizard Rekam Data - tiga perubahan bentuk sekaligus.
 *
 * Acuan: docs/product/ROADMAP_WIZARD_REKAM_PERUMAHAN.md §2, dari rancangan
 * `new_flow/rekamdata/` (9 frame). Bentuk lama beserta alasannya terekam di
 * docs/engineering/REKAM_DATA_PERUMAHAN_SEBELUM_WIZARD.md.
 *
 * 1. PERIODE - `bulan` (1-12, kumulatif) menjadi `triwulan` (1-4, per triwulan).
 *    Kedua domain ikut; keputusan user "kawasan juga samakan, itu 1 jalur".
 *
 * 2. GERBANG DIBALIK - dulu per sumber dana (`rd_perumahan_bagian`), kini per
 *    program (`rd_perumahan_program`). Frame 004 memilih PROGRAM yang akan
 *    dilaporkan; sumber dana ditambahkan di dalam tiap program.
 *
 * 3. RENCANA + REALISASI - `unit`/`anggaran` menjadi empat kolom.
 *
 * Nama tabel ikut berganti karena isinya berganti. `bagian` dulu berarti
 * "bagian sumber dana"; memakai nama itu untuk gerbang program adalah jenis
 * kebohongan yang paling lama bertahan.
 *
 * BNBA (`rd_perumahan_bnba`) TIDAK disentuh - dipertahankan utuh sebagai
 * langkah opsional di wizard, sesuai keputusan user.
 */
class Migration_Rekam_perumahan_wizard extends CI_Migration {

    private const PROGRAM = "'pk_rtlh','pb_rtlh','pb_backlog','pk_bencana','pb_bencana','pb_relokasi'";

    private const SUMBER = "'apbd_provinsi','apbd_kabkota','apbn_bsps','apbn_dak'"
        . ",'apbn_kemensos','apbn_dana_desa','apbn_kl_lain','baznas_ri'"
        . ",'baznas_provinsi','baznas_kabkota','csr','dana_lainnya'";

    public function up()
    {
        $this->periode_jadi_triwulan();
        $this->gerbang_jadi_program();
        $this->baris_dapat_rencana();
    }

    /**
     * `bulan` -> `triwulan`, nilainya CEIL(bulan/3).
     *
     * BERHENTI bila konversi akan menabrakkan dua laporan ke periode yang sama
     * (mis. bulan 7 dan 8 sama-sama jadi TW III untuk kabupaten dan domain yang
     * sama). Membiarkannya lewat berarti kunci unik gagal dipasang, atau lebih
     * buruk: dipasang setelah salah satu baris diam-diam hilang.
     */
    private function periode_jadi_triwulan()
    {
        $tabrakan = (int) $this->db->query(
            'SELECT COUNT(*) AS n FROM (
                SELECT domain, kabupaten_id, tahun, CEIL(bulan/3) AS tw, COUNT(*) AS c
                FROM rd_laporan GROUP BY domain, kabupaten_id, tahun, CEIL(bulan/3)
                HAVING c > 1
             ) x')->row('n');

        if ($tabrakan > 0) {
            show_error("Migrasi 20260701000024 berhenti: {$tabrakan} kelompok laporan akan "
                . 'menabrak periode yang sama setelah bulan diubah jadi triwulan '
                . '(mis. bulan 7 dan 8 sama-sama TW III). Selesaikan dulu duplikatnya - '
                . 'menggabungkan angka dua bulan menjadi satu triwulan adalah keputusan '
                . 'isi data, bukan keputusan migrasi.');
            return;
        }

        $this->db->query('ALTER TABLE `rd_laporan` DROP INDEX `uq_rd_laporan_periode`');
        $this->db->query("ALTER TABLE `rd_laporan`
            CHANGE `bulan` `triwulan` TINYINT UNSIGNED NOT NULL COMMENT '1-4, capaian TRIWULAN INI (bukan kumulatif)'");
        $this->db->query('UPDATE `rd_laporan` SET `triwulan` = CEIL(`triwulan` / 3)');
        $this->db->query('ALTER TABLE `rd_laporan`
            ADD UNIQUE KEY `uq_rd_laporan_periode` (`domain`,`kabupaten_id`,`tahun`,`triwulan`)');

        // Langkah wizard disimpan DI BARIS, bukan di sesi atau URL - supaya
        // pengisian bisa dilanjutkan setelah keluar-masuk. Idiom yang sama
        // dipakai wizard Warga (`current_step` di tabel assessment).
        $this->db->query("ALTER TABLE `rd_laporan`
            ADD COLUMN `current_step` VARCHAR(24) NOT NULL DEFAULT 'periode' AFTER `status`");
    }

    /**
     * Gerbang pindah dari sumber dana ke program.
     *
     * Gerbang lama TIDAK bisa dipetakan langsung: "APBD ada" tidak memberi tahu
     * program mana yang dilaporkan. Yang bisa dipercaya hanya jejak angkanya -
     * program yang PUNYA baris pasti dilaporkan. Itu yang dipakai.
     */
    private function gerbang_jadi_program()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `rd_perumahan_program` (
            `laporan_id` BIGINT UNSIGNED NOT NULL,
            `program` ENUM(" . self::PROGRAM . ") NOT NULL,
            `dilaporkan` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`laporan_id`,`program`),
            CONSTRAINT `fk_rd_perumahan_program_laporan` FOREIGN KEY (`laporan_id`)
                REFERENCES `rd_laporan` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query(
            'INSERT IGNORE INTO `rd_perumahan_program` (`laporan_id`, `program`, `dilaporkan`)
             SELECT DISTINCT `laporan_id`, `program`, 1 FROM `rd_perumahan_baris`');
    }

    /**
     * Empat angka menggantikan dua, dan induk FK berpindah ke program.
     *
     * Angka lama seluruhnya masuk ke REALISASI, bukan dibagi atau ditebak:
     * Google Form yang melahirkannya berjudul "Realisasi", dan tidak ada satu
     * pun field rencana di 150 pertanyaannya. `rencana_*` mulai dari nol karena
     * memang belum pernah ada yang mengisinya.
     */
    private function baris_dapat_rencana()
    {
        $this->db->query('ALTER TABLE `rd_perumahan_baris` DROP FOREIGN KEY `fk_rd_perumahan_baris_bagian`');
        $this->db->query('ALTER TABLE `rd_perumahan_baris` DROP INDEX `uq_rd_perumahan_baris`');

        $this->db->query("ALTER TABLE `rd_perumahan_baris`
            ADD COLUMN `rencana_unit`       INT UNSIGNED    NOT NULL DEFAULT 0 AFTER `program`,
            ADD COLUMN `rencana_anggaran`   BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'rupiah penuh' AFTER `rencana_unit`,
            ADD COLUMN `realisasi_unit`     INT UNSIGNED    NOT NULL DEFAULT 0 AFTER `rencana_anggaran`,
            ADD COLUMN `realisasi_anggaran` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'rupiah penuh' AFTER `realisasi_unit`");

        $this->db->query('UPDATE `rd_perumahan_baris`
            SET `realisasi_unit` = `unit`, `realisasi_anggaran` = `anggaran`');

        $this->db->query('ALTER TABLE `rd_perumahan_baris` DROP COLUMN `unit`, DROP COLUMN `anggaran`');

        $this->db->query("ALTER TABLE `rd_perumahan_baris`
            MODIFY COLUMN `sumber_dana` ENUM(" . self::SUMBER . ") NOT NULL,
            ADD UNIQUE KEY `uq_rd_perumahan_baris` (`laporan_id`,`program`,`sumber_dana`),
            ADD CONSTRAINT `fk_rd_perumahan_baris_program` FOREIGN KEY (`laporan_id`,`program`)
                REFERENCES `rd_perumahan_program` (`laporan_id`,`program`) ON DELETE CASCADE");

        $this->db->query('DROP TABLE IF EXISTS `rd_perumahan_bagian`');
    }

    /**
     * Turun HANYA aman selama belum ada isi. Sesudah ada, turun berarti membuang
     * kolom rencana dan memaksa triwulan kembali jadi bulan - dan tidak ada
     * pemetaan jujur dari "TW III" ke satu bulan tertentu. Berhenti dengan galat
     * adalah perilaku yang DIINGINKAN.
     */
    public function down()
    {
        $isi = (int) $this->db->query('SELECT COUNT(*) AS n FROM `rd_perumahan_baris`')->row('n');
        if ($isi > 0) {
            show_error("Migrasi 20260701000024 tidak bisa diturunkan: ada {$isi} baris angka. "
                . 'Menurunkannya membuang kolom rencana_* dan memaksa triwulan kembali jadi '
                . 'bulan, padahal tidak ada pemetaan jujur dari TW III ke satu bulan tertentu. '
                . 'Pulihkan dari backup, jangan turunkan.');
            return;
        }

        $this->db->query("CREATE TABLE IF NOT EXISTS `rd_perumahan_bagian` (
            `laporan_id` BIGINT UNSIGNED NOT NULL,
            `sumber_dana` ENUM(" . self::SUMBER . ") NOT NULL,
            `ada` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`laporan_id`,`sumber_dana`),
            CONSTRAINT `fk_rd_perumahan_bagian_laporan` FOREIGN KEY (`laporan_id`)
                REFERENCES `rd_laporan` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->db->query('ALTER TABLE `rd_perumahan_baris` DROP FOREIGN KEY `fk_rd_perumahan_baris_program`');
        $this->db->query('ALTER TABLE `rd_perumahan_baris` DROP INDEX `uq_rd_perumahan_baris`');
        $this->db->query("ALTER TABLE `rd_perumahan_baris`
            ADD COLUMN `unit` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `program`,
            ADD COLUMN `anggaran` BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER `unit`,
            DROP COLUMN `rencana_unit`, DROP COLUMN `rencana_anggaran`,
            DROP COLUMN `realisasi_unit`, DROP COLUMN `realisasi_anggaran`,
            ADD UNIQUE KEY `uq_rd_perumahan_baris` (`laporan_id`,`sumber_dana`,`program`),
            ADD CONSTRAINT `fk_rd_perumahan_baris_bagian` FOREIGN KEY (`laporan_id`,`sumber_dana`)
                REFERENCES `rd_perumahan_bagian` (`laporan_id`,`sumber_dana`) ON DELETE CASCADE");

        $this->db->query('DROP TABLE IF EXISTS `rd_perumahan_program`');

        $this->db->query('ALTER TABLE `rd_laporan` DROP COLUMN `current_step`');
        $this->db->query('ALTER TABLE `rd_laporan` DROP INDEX `uq_rd_laporan_periode`');
        $this->db->query("ALTER TABLE `rd_laporan`
            CHANGE `triwulan` `bulan` TINYINT UNSIGNED NOT NULL COMMENT '1-12, kumulatif sampai dengan bulan ini'");
        $this->db->query('ALTER TABLE `rd_laporan`
            ADD UNIQUE KEY `uq_rd_laporan_periode` (`domain`,`kabupaten_id`,`tahun`,`bulan`)');
    }
}
