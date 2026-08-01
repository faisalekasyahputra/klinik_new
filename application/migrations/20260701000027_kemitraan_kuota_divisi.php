<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kuota mahasiswa per divisi, supaya slot menutup dirinya sendiri.
 *
 * Migrasi 026 membuat slot bisa dikelola, tapi buka-tutupnya seluruhnya manual:
 * admin harus menghitung sendiri berapa mahasiswa sudah masuk dan mencentang
 * ulang. Itu memindahkan pekerjaan, bukan menyelesaikannya.
 *
 * SATU kolom, di tabel divisi — bukan kolom kuota di tiap baris slot. "Setiap
 * divisi punya slot maksimal" berarti satu angka yang berlaku untuk tiap bulan
 * yang dibuka, dan kuota per sel akan menuntut admin mengisi 12 angka per
 * divisi untuk kebutuhan yang belum pernah disebut.
 *
 * Tidak ada kolom "terisi". Jumlah itu DITURUNKAN dari `kkn_magang_pendaftaran`
 * setiap kali dibaca. Menyimpannya berarti punya dua sumber kebenaran yang
 * harus disinkronkan pada setiap pendaftaran, penolakan, dan penyuntingan
 * admin — dan yang satu pasti akan menyimpang dari yang lain.
 *
 * Default 2, bukan 0: nilai lama tidak boleh berarti "semua divisi tiba-tiba
 * penuh" pada saat migrasi berjalan.
 */
class Migration_Kemitraan_kuota_divisi extends CI_Migration {

    const TABEL = 'kkn_magang_divisi';

    public function up()
    {
        // Idempoten — migrasi ini bisa saja pernah berjalan sebagian.
        if ($this->db->field_exists('kuota', self::TABEL)) { return; }

        $this->db->query("ALTER TABLE `" . self::TABEL . "`
            ADD COLUMN `kuota` TINYINT UNSIGNED NOT NULL DEFAULT 2
            COMMENT 'Maksimal mahasiswa per bulan pada divisi ini' AFTER `aktif`");
    }

    public function down()
    {
        if ( ! $this->db->field_exists('kuota', self::TABEL)) { return; }
        $this->db->query("ALTER TABLE `" . self::TABEL . "` DROP COLUMN `kuota`");
    }
}
