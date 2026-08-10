<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Perbaikan migrasi 031 yang GAGAL SEPARUH di production.
 *
 * 031 melaporkan "Migrasi sukses, versi skema sekarang: 20260701000031" padahal
 * `kkn_magang_bidang` tidak pernah terbentuk. CI menandai migrasi berhasil tanpa
 * memeriksa nilai balik query (system/libraries/Migration.php ~302-309), jadi
 * satu CREATE TABLE yang ditolak tetap dicatat lunas. Ketahuan hanya karena
 * `Migrate::status()` sudah diajari memeriksa tabelnya satu per satu.
 *
 * PENYEBAB - collation, bukan tipe. `bidang.kode` di production
 * `utf8mb4_general_ci`, sementara tabel baru yang cuma menyebut
 * `DEFAULT CHARSET=utf8mb4` mewarisi bawaan server: `utf8mb4_uca1400_ai_ci`
 * (MariaDB 11.4+). Foreign key antar kolom yang collation-nya beda ditolak
 * errno 150. Di lokal (XAMPP) bawaannya kebetulan `general_ci`, jadi lolos -
 * dan itulah kenapa lokal hijau sementara production diam-diam patah.
 *
 * AGENTS.md §0e sudah memperingatkan errno 150 untuk ketidakcocokan tipe FK.
 * Peringatan itu kurang satu kata: collation juga menentukan. Migrasi ini
 * MEMBACA collation `bidang.kode` dari information_schema dan memakainya apa
 * adanya - bukan menuliskan `utf8mb4_general_ci` sebagai tebakan baru yang
 * kebetulan benar hari ini di dua server yang kebetulan sama.
 *
 * Keadaan production sesudah 031 gagal separuh:
 *   - kkn_magang_bidang        : TIDAK ADA
 *   - kkn_magang_slot          : ada, MASIH ber-divisi_id, tanpa FK (sudah
 *                                dilepas 031), isinya sudah dikosongkan
 *   - kkn_magang_divisi        : sudah lenyap
 *   - kkn_magang_pendaftaran   : keempat kolom barunya ADA - tabel itu lebih
 *                                tua dan ber-collation general_ci, jadi FK-nya
 *                                kebetulan cocok dan lolos
 *
 * Di lokal seluruh isi migrasi ini NO-OP: semuanya sudah pada tempatnya.
 */
class Migration_Perbaiki_kolasi_magang_bidang extends CI_Migration {

    /** Collation kolom rujukan, dibaca dari DB - bukan diasumsikan. */
    private function kolasi_bidang_kode()
    {
        $row = $this->db->query(
            "SELECT COLLATION_NAME FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bidang' AND COLUMN_NAME = 'kode'"
        )->row();

        // Kalau tidak terbaca sekalipun, JANGAN menebak: tanpa collation yang
        // cocok, FK-nya akan ditolak lagi dan migrasi ini mengulang kesalahan
        // yang sedang diperbaikinya.
        return $row && $row->COLLATION_NAME ? $row->COLLATION_NAME : NULL;
    }

    public function up()
    {
        $kolasi = $this->kolasi_bidang_kode();
        if ($kolasi === NULL) { return; }
        $col = 'VARCHAR(30) COLLATE ' . $kolasi;

        if ( ! $this->db->table_exists('kkn_magang_bidang')) {
            $this->db->query("CREATE TABLE `kkn_magang_bidang` (
                `bidang_kode` {$col} NOT NULL,
                `kuota` TINYINT UNSIGNED NOT NULL DEFAULT 2
                    COMMENT 'Maksimal mahasiswa hadir BERSAMAAN di bidang ini',
                `aktif` TINYINT(1) NOT NULL DEFAULT 1
                    COMMENT 'Nonaktif = tidak ditawarkan pada pendaftaran baru',
                PRIMARY KEY (`bidang_kode`),
                CONSTRAINT `fk_magang_bidang` FOREIGN KEY (`bidang_kode`)
                    REFERENCES `bidang` (`kode`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE={$kolasi}");
        }

        // Seed menyusul pembuatan tabel, dan dijalankan terpisah supaya bidang
        // yang ditambahkan setelah tabel ini lahir tetap ikut terdaftar.
        foreach ($this->db->get('bidang')->result() as $b) {
            $this->db->query("INSERT IGNORE INTO `kkn_magang_bidang` (`bidang_kode`) VALUES (?)", [$b->kode]);
        }

        if ( ! $this->db->field_exists('bidang_kode', 'kkn_magang_slot')) {
            // Barisnya sudah dikosongkan 031 sebelum gagal; dikosongkan lagi di
            // sini supaya migrasi ini tetap benar pada DB yang belum menjalankan
            // 031 sama sekali. `divisi_id` NOT NULL tidak punya padanan di dunia
            // per-bidang, jadi tidak ada yang bisa dipindahkan.
            $this->db->query("DELETE FROM `kkn_magang_slot`");

            // Index unik lama menyebut divisi_id, jadi ia harus lepas sebelum
            // kolomnya dibuang. Dipisah per-pernyataan supaya kegagalan salah
            // satunya tidak menyeret sisanya.
            if ($this->indeks_ada('kkn_magang_slot', 'uq_slot_divisi_periode')) {
                $this->db->query("ALTER TABLE `kkn_magang_slot` DROP INDEX `uq_slot_divisi_periode`");
            }
            if ($this->db->field_exists('divisi_id', 'kkn_magang_slot')) {
                $this->db->query("ALTER TABLE `kkn_magang_slot` DROP COLUMN `divisi_id`");
            }

            $this->db->query("ALTER TABLE `kkn_magang_slot`
                ADD COLUMN `bidang_kode` {$col} NOT NULL FIRST,
                ADD UNIQUE KEY `uq_slot_bidang_periode` (`bidang_kode`,`tahun`,`bulan`),
                ADD CONSTRAINT `fk_slot_bidang` FOREIGN KEY (`bidang_kode`)
                    REFERENCES `kkn_magang_bidang` (`bidang_kode`) ON DELETE CASCADE");
        }

        // Dijaga terpisah: pada DB yang 031-nya lolos utuh, kolomnya sudah ada
        // tapi bisa saja lahir dengan collation yang berbeda.
        if ( ! $this->db->field_exists('bidang_kode', 'kkn_magang_pendaftaran')) {
            $this->db->query("ALTER TABLE `kkn_magang_pendaftaran`
                ADD COLUMN `bidang_kode` {$col} NULL
                    COMMENT 'Bidang tujuan magang; NULL untuk KKN dan baris lama'
                    AFTER `divisi_atau_tema`,
                ADD KEY `idx_kkn_bidang` (`bidang_kode`),
                ADD CONSTRAINT `fk_kkn_bidang` FOREIGN KEY (`bidang_kode`)
                    REFERENCES `bidang` (`kode`) ON DELETE SET NULL");
        }

        $this->db->query("DROP TABLE IF EXISTS `kkn_magang_divisi`");
    }

    private function indeks_ada($tabel, $nama)
    {
        return (bool) $this->db->query(
            "SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1",
            [$tabel, $nama]
        )->row();
    }

    public function down()
    {
        // Tidak ada yang dikembalikan: migrasi ini memperbaiki keadaan yang
        // memang rusak. Turun ke 031 berarti kembali ke separuh jadi.
    }
}
