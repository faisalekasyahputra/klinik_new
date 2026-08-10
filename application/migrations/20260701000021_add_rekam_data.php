<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rekam Data - pelaporan capaian Kabupaten/Kota, pengganti dua Google Form dinas.
 * Spesifikasi + keputusan user 29 Jul 2026: docs/product/PRD_REKAM_DATA.md
 *
 * Lima tabel: satu amplop bersama (rd_laporan) + detail per domain. Siklus
 * draft/kirim/perbaikan ditulis sekali di amplop, dipakai kedua domain.
 *
 * FK: kabupaten.id = int(10) unsigned, usr_users.id = int(11) SIGNED.
 * Menyamakan keduanya jadi UNSIGNED = errno 150 (AGENTS.md §0e).
 */
class Migration_Add_rekam_data extends CI_Migration {

    /** Ikut daftar di form aktual, bukan spreadsheet - keputusan user 29 Jul 2026. */
    private const SUMBER_PERUMAHAN = "'apbd_kabkota','apbn_bsps','apbn_dak','apbn_kemensos'"
        . ",'apbn_dana_desa','apbn_kl_lain','baznas_ri','baznas_kabkota','csr','dana_lainnya'";

    private const PROGRAM = "'pk_rtlh','pb_rtlh','pb_backlog','pk_bencana','pb_bencana','pb_relokasi'";

    private const INDIKATOR_KAWASAN = "'bangunan_gedung','jalan_lingkungan','air_minum'"
        . ",'drainase','air_limbah','persampahan','proteksi_kebakaran'";

    private const SUMBER_KAWASAN = "'apbn','apbd_provinsi','apbd_kabkota','dana_desa'"
        . ",'csr','baznas','dana_lainnya'";

    public function up()
    {
        $sumber_perumahan  = self::SUMBER_PERUMAHAN;
        $program           = self::PROGRAM;
        $indikator_kawasan = self::INDIKATOR_KAWASAN;
        $sumber_kawasan    = self::SUMBER_KAWASAN;

        // Amplop bersama. `bulan` = "data kumulatif sampai dengan bulan", 1-12,
        // dipakai kedua domain karena keduanya memang bersiklus bulanan-kumulatif.
        $this->db->query("CREATE TABLE IF NOT EXISTS `rd_laporan` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `domain` ENUM('perumahan','kawasan') NOT NULL,
            `kabupaten_id` INT UNSIGNED NOT NULL,
            `tahun` SMALLINT UNSIGNED NOT NULL,
            `bulan` TINYINT UNSIGNED NOT NULL COMMENT '1-12, kumulatif sampai dengan bulan ini',
            `status` ENUM('draft','terkirim','perlu_perbaikan') NOT NULL DEFAULT 'draft',
            `submitted_at` DATETIME NULL,
            `submitted_by` INT NULL,
            `reviewed_at` DATETIME NULL,
            `reviewed_by` INT NULL,
            `catatan_admin` TEXT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_rd_laporan_periode` (`domain`,`kabupaten_id`,`tahun`,`bulan`),
            KEY `idx_rd_laporan_status` (`domain`,`status`),
            KEY `idx_rd_laporan_submitted_by` (`submitted_by`),
            KEY `idx_rd_laporan_reviewed_by` (`reviewed_by`),
            CONSTRAINT `fk_rd_laporan_kabupaten` FOREIGN KEY (`kabupaten_id`) REFERENCES `kabupaten` (`id`),
            CONSTRAINT `fk_rd_laporan_submitter` FOREIGN KEY (`submitted_by`) REFERENCES `usr_users` (`id`) ON DELETE SET NULL,
            CONSTRAINT `fk_rd_laporan_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `usr_users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Jawaban gerbang "Ada / Tidak Ada" per sumber dana. Tabel sendiri, bukan kolom
        // SET di rd_laporan: SET adalah atribut multi-nilai (melanggar 1NF) dan lagipula
        // khusus perumahan, tidak pantas menempel di amplop bersama.
        // Gunanya membedakan "sumber ini nihil" dari "belum diisi" - tanpa ini, nol dan
        // kosong tidak terbedakan di rekap.
        $this->db->query("CREATE TABLE IF NOT EXISTS `rd_perumahan_bagian` (
            `laporan_id` BIGINT UNSIGNED NOT NULL,
            `sumber_dana` ENUM({$sumber_perumahan}) NOT NULL,
            `ada` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`laporan_id`,`sumber_dana`),
            CONSTRAINT `fk_rd_perumahan_bagian_laporan` FOREIGN KEY (`laporan_id`) REFERENCES `rd_laporan` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Satu baris per (sumber dana x program). `keterangan` menampung tiga field
        // form yang berbeda nama tapi sama peran: Kementerian Sumber / Perusahaan CSR /
        // Sumber Anggaran Dana Lainnya. Satu per baris, sesuai keputusan user.
        //
        // FK menunjuk ke rd_perumahan_bagian, BUKAN langsung ke rd_laporan: angka hanya
        // boleh ada kalau sumber dananya sudah dinyatakan. Menghapus centang sumber dana
        // otomatis menyapu angkanya, dan cascade ke rd_laporan tetap jalan lewat induknya.
        $this->db->query("CREATE TABLE IF NOT EXISTS `rd_perumahan_baris` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `laporan_id` BIGINT UNSIGNED NOT NULL,
            `sumber_dana` ENUM({$sumber_perumahan}) NOT NULL,
            `program` ENUM({$program}) NOT NULL,
            `unit` INT UNSIGNED NOT NULL DEFAULT 0,
            `anggaran` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'rupiah penuh',
            `keterangan` VARCHAR(150) NOT NULL DEFAULT '' COMMENT 'Kementerian Sumber / Perusahaan CSR / Sumber Dana Lainnya',
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_rd_perumahan_baris` (`laporan_id`,`sumber_dana`,`program`),
            CONSTRAINT `fk_rd_perumahan_baris_bagian` FOREIGN KEY (`laporan_id`,`sumber_dana`)
                REFERENCES `rd_perumahan_bagian` (`laporan_id`,`sumber_dana`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // BNBA (By Name By Address) - satu berkas per laporan, sesuai form.
        // Berkas masuk private_uploads seperti dokumen SRP2, bukan webroot.
        $this->db->query("CREATE TABLE IF NOT EXISTS `rd_perumahan_bnba` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `laporan_id` BIGINT UNSIGNED NOT NULL,
            `nama_asli` VARCHAR(255) NOT NULL,
            `private_path` VARCHAR(255) NOT NULL,
            `mime_type` VARCHAR(100) NOT NULL,
            `ukuran` INT UNSIGNED NOT NULL DEFAULT 0,
            `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `uploaded_by` INT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_rd_bnba_laporan` (`laporan_id`),
            KEY `idx_rd_bnba_uploader` (`uploaded_by`),
            CONSTRAINT `fk_rd_bnba_laporan` FOREIGN KEY (`laporan_id`) REFERENCES `rd_laporan` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_rd_bnba_uploader` FOREIGN KEY (`uploaded_by`) REFERENCES `usr_users` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Ringkasan kawasan, 1:1 dengan laporan (halaman 1-2 form).
        // Total anggaran SENGAJA tidak ada di sini: diturunkan dari SUM() intervensi,
        // supaya tidak lahir dua angka yang bisa saling bertentangan.
        $this->db->query("CREATE TABLE IF NOT EXISTS `rd_kawasan_ringkasan` (
            `laporan_id` BIGINT UNSIGNED NOT NULL,
            `ada_penanganan` TINYINT(1) NOT NULL DEFAULT 0,
            `ada_progres` TINYINT(1) NOT NULL DEFAULT 0,
            `catatan_progres` TEXT NULL COMMENT 'diisi bila tidak ada progres',
            `total_luas_ha` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'tidak bisa diturunkan dari volume, satuan indikator berbeda-beda',
            PRIMARY KEY (`laporan_id`),
            CONSTRAINT `fk_rd_kawasan_ringkasan_laporan` FOREIGN KEY (`laporan_id`) REFERENCES `rd_laporan` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // Daftar intervensi. Google Form membatasi 20 karena tidak bisa mengulang baris;
        // batas itu tidak dibawa ke sini. `satuan` sengaja tidak disimpan - diturunkan
        // dari `indikator`, supaya tidak bisa bertentangan dengannya.
        // `keterangan_sumber` tambahan kita: form kawasan tidak punya nama perusahaan CSR.
        $this->db->query("CREATE TABLE IF NOT EXISTS `rd_kawasan_intervensi` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `laporan_id` BIGINT UNSIGNED NOT NULL,
            `urutan` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `indikator` ENUM({$indikator_kawasan}) NOT NULL,
            `nama_kegiatan` TEXT NOT NULL,
            `lokasi_teks` TEXT NOT NULL COMMENT 'RT, RW, Desa/Kelurahan, Kecamatan',
            `sumber_anggaran` ENUM({$sumber_kawasan}) NOT NULL,
            `keterangan_sumber` VARCHAR(150) NOT NULL DEFAULT '',
            `volume` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
            `nilai_anggaran` BIGINT UNSIGNED NOT NULL DEFAULT 0,
            `nilai_padat_karya` BIGINT UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uq_rd_kawasan_intervensi_urutan` (`laporan_id`,`urutan`),
            CONSTRAINT `fk_rd_kawasan_intervensi_laporan` FOREIGN KEY (`laporan_id`) REFERENCES `rd_laporan` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    public function down()
    {
        // Urutan terbalik: anak dulu, induk terakhir.
        $this->db->query("DROP TABLE IF EXISTS `rd_kawasan_intervensi`");
        $this->db->query("DROP TABLE IF EXISTS `rd_kawasan_ringkasan`");
        $this->db->query("DROP TABLE IF EXISTS `rd_perumahan_bnba`");
        $this->db->query("DROP TABLE IF EXISTS `rd_perumahan_baris`");
        $this->db->query("DROP TABLE IF EXISTS `rd_perumahan_bagian`");
        $this->db->query("DROP TABLE IF EXISTS `rd_laporan`");
    }
}
