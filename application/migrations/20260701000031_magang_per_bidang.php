<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Slot magang pindah dari "divisi" ke BIDANG.
 *
 * Migrasi 026 membuat `kkn_magang_divisi` berisi tujuh divisi - Administrasi
 * Pemerintahan, Infrastruktur dan Teknologi Digital, PPID, dan seterusnya.
 * Ketujuhnya berasal dari array dummy di mockup lama, dan dinas mengonfirmasi
 * 1 Agt 2026 bahwa satuan terkecil organisasinya adalah BIDANG: "habis kadinas
 * bawahnya langsung bidang-bidang, ga ada divisi". Jadi yang dipindahkan ke DB
 * waktu itu bukan struktur organisasi, melainkan tebakan perancang mockup.
 *
 * Efek sampingnya justru menyederhanakan: dengan slot melekat pada bidang,
 * pertanyaan "bidang mana yang meninjau pendaftaran ini" hilang sama sekali -
 * slotnya sudah menyebut bidangnya. Kolom pemetaan divisi->bidang yang
 * ditambahkan migrasi 029 ikut lenyap bersama tabelnya.
 *
 * SLOT LAMA DIBUANG, tidak dipindahkan. Tidak ada pemetaan yang jujur dari
 * tujuh divisi fiktif ke lima bidang nyata; menebaknya berarti menyimpan angka
 * yang tidak pernah diputuskan siapa pun. Slot harus ditetapkan ulang.
 *
 * PENDAFTARAN LAMA TIDAK DIBUANG. `bidang_kode` dibiarkan NULL untuk baris yang
 * sudah ada - riwayat pendaftaran orang tidak boleh hilang karena strukturnya
 * dikoreksi, dan `divisi_atau_tema` tetap menyimpan teks aslinya sebagai jejak.
 */
class Migration_Magang_per_bidang extends CI_Migration {

    public function up()
    {
        // Setelan magang per bidang. Tabel TERSENDIRI, bukan kolom tambahan di
        // `bidang`: tabel itu struktur organisasi yang dipakai bersama modul
        // aduan, dan menumpanginya dengan kuota magang membuat satu tabel
        // memikul dua urusan yang berubah karena alasan berbeda.
        $this->db->query("CREATE TABLE IF NOT EXISTS `kkn_magang_bidang` (
            `bidang_kode` VARCHAR(30) NOT NULL,
            `kuota` TINYINT UNSIGNED NOT NULL DEFAULT 2
                COMMENT 'Maksimal mahasiswa hadir BERSAMAAN di bidang ini',
            `aktif` TINYINT(1) NOT NULL DEFAULT 1
                COMMENT 'Nonaktif = tidak ditawarkan pada pendaftaran baru',
            PRIMARY KEY (`bidang_kode`),
            CONSTRAINT `fk_magang_bidang` FOREIGN KEY (`bidang_kode`)
                REFERENCES `bidang` (`kode`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        foreach ($this->db->get('bidang')->result() as $b) {
            $this->db->query("INSERT IGNORE INTO `kkn_magang_bidang` (`bidang_kode`) VALUES (?)", [$b->kode]);
        }

        if ( ! $this->db->field_exists('bidang_kode', 'kkn_magang_slot')) {
            // Slot lama melekat pada divisi fiktif - dikosongkan lebih dulu,
            // supaya tidak ada baris yatim saat divisi_id dicabut.
            $this->db->query("DELETE FROM `kkn_magang_slot`");
            $this->db->query("ALTER TABLE `kkn_magang_slot` DROP FOREIGN KEY `fk_slot_divisi`");
            $this->db->query("ALTER TABLE `kkn_magang_slot`
                DROP INDEX `uq_slot_divisi_periode`,
                DROP COLUMN `divisi_id`,
                ADD COLUMN `bidang_kode` VARCHAR(30) NOT NULL FIRST,
                ADD UNIQUE KEY `uq_slot_bidang_periode` (`bidang_kode`,`tahun`,`bulan`),
                ADD CONSTRAINT `fk_slot_bidang` FOREIGN KEY (`bidang_kode`)
                    REFERENCES `kkn_magang_bidang` (`bidang_kode`) ON DELETE CASCADE");
        }

        if ( ! $this->db->field_exists('bidang_kode', 'kkn_magang_pendaftaran')) {
            // Kolom SUNGGUHAN, bukan pencocokan lewat nama. Sebelumnya routing
            // tinjauan bersandar pada join `divisi.nama = divisi_atau_tema` -
            // satu ganti nama dan seluruh pendaftaran putus dari bidangnya.
            $this->db->query("ALTER TABLE `kkn_magang_pendaftaran`
                ADD COLUMN `bidang_kode` VARCHAR(30) NULL
                    COMMENT 'Bidang tujuan magang; NULL untuk KKN dan baris lama'
                    AFTER `divisi_atau_tema`,
                ADD KEY `idx_kkn_bidang` (`bidang_kode`),
                ADD CONSTRAINT `fk_kkn_bidang` FOREIGN KEY (`bidang_kode`)
                    REFERENCES `bidang` (`kode`) ON DELETE SET NULL");
        }

        $this->db->query("DROP TABLE IF EXISTS `kkn_magang_divisi`");
    }

    public function down()
    {
        // Tidak dikembalikan: `kkn_magang_divisi` berisi struktur yang memang
        // tidak pernah ada. Turun ke versi sebelumnya berarti kehilangan slot,
        // dan itu memang konsekuensi yang jujur - bukan sesuatu yang bisa
        // ditambal dengan menebak ulang tujuh nama divisi.
        if ($this->db->field_exists('bidang_kode', 'kkn_magang_pendaftaran')) {
            $this->db->query("ALTER TABLE `kkn_magang_pendaftaran` DROP FOREIGN KEY `fk_kkn_bidang`");
            $this->db->query("ALTER TABLE `kkn_magang_pendaftaran` DROP COLUMN `bidang_kode`");
        }
        $this->db->query("DROP TABLE IF EXISTS `kkn_magang_slot`");
        $this->db->query("DROP TABLE IF EXISTS `kkn_magang_bidang`");
    }
}
