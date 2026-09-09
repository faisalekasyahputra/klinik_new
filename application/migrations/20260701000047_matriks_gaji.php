<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Field ke-7 di step "Isi Data Sesuai Matriks" - permintaan user 23 Agt
 * 2026: tambahkan "Gaji" sesuai kolom A xlsx "MATRIKS VARIABEL PENENTUAN
 * PROGRAM PERUMAHAN.xlsx" Sheet4 ("Pendapatan / Gaji", TIDAK bertanda '*'
 * di analisis awal makanya belum ikut migrasi 045 - diminta menyusul
 * terpisah).
 *
 * Field BARU (matrix_income_code), BUKAN menimpa income_band_code yang
 * sudah ada di step "Data Warga" - alasan SAMA seperti 5 field matriks
 * lain di migrasi 045: income_band_code sudah dipakai Warga_ruleset.php
 * (desil/eligibility) dengan kosakata pita (7 pita asli SIMPERUM + 3 pita
 * tambahan 4,2-6/6-8/>8 jt) yang BEDA persis dari 7 pita kolom A xlsx ini
 * (0-1,5 / 1,5-2,2 / 2,2-2,8 / 2,8-8,5 / 2,8-10 / >8,5 / >10 jt - dua
 * pasang pita di antaranya SENGAJA tumpang tindih di baris asli xlsx,
 * dibedakan oleh Status Perkawinan pada baris yang sama: batas 8,5jt
 * untuk Belum Menikah, 10jt untuk Menikah - bukan kesalahan duplikasi).
 */
class Migration_Matriks_gaji extends CI_Migration {

    const TABEL = 'sf_penilaian_perumahan';
    const KOLOM = 'matrix_income_code';

    public function up()
    {
        if ( ! $this->db->table_exists(self::TABEL)) {
            log_message('error', 'Migrasi 047: tabel ' . self::TABEL . ' tidak ada.');
            return;
        }
        if ($this->db->field_exists(self::KOLOM, self::TABEL)) { return; }
        $ok = $this->db->query(
            'ALTER TABLE `' . self::TABEL . '` ADD COLUMN `' . self::KOLOM . '`
             VARCHAR(30) NULL COMMENT \'Pendapatan/Gaji - xlsx Sheet4 kolom A\''
        );
        if ($ok === FALSE) {
            log_message('error', 'Migrasi 047: ADD COLUMN ' . self::KOLOM . ' GAGAL.');
        }
    }

    public function down()
    {
        if ($this->db->field_exists(self::KOLOM, self::TABEL)) {
            $this->db->query('ALTER TABLE `' . self::TABEL . '` DROP COLUMN `' . self::KOLOM . '`');
        }
    }
}
