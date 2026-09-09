<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Field ke-8 di step "Isi Data Sesuai Matriks" - kolom C xlsx Sheet4
 * ("Status DTKS"), DIBUTUHKAN untuk mesin pencocokan 20 baris matriks
 * (permintaan user 23 Agt 2026: "Hasil Rekomendasi ... sesuaikan dengan
 * xlsx kolom J"). Tanpa status DTKS, 16 dari 20 baris (semua yang
 * mensyaratkan "YA") tidak bisa dicocokkan dengan benar - baris itu HANYA
 * berlaku untuk warga terdaftar DTKS, dan tanpa tahu status ini, mesin
 * pencocokan harus menebak (salah arah ke rekomendasi yang berhak
 * ditolak, atau ke penolakan yang sebenarnya berhak diterima).
 *
 * Field BARU (matrix_dtks_status), pola sama seperti 6 field matriks
 * lain: tidak menimpa apa pun yang sudah ada (tidak ada field DTKS di
 * skema ini sebelumnya - dicek langsung, nihil).
 */
class Migration_Matriks_dtks extends CI_Migration {

    const TABEL = 'sf_penilaian_perumahan';
    const KOLOM = 'matrix_dtks_status';

    public function up()
    {
        if ( ! $this->db->table_exists(self::TABEL)) {
            log_message('error', 'Migrasi 048: tabel ' . self::TABEL . ' tidak ada.');
            return;
        }
        if ($this->db->field_exists(self::KOLOM, self::TABEL)) { return; }
        $ok = $this->db->query(
            'ALTER TABLE `' . self::TABEL . '` ADD COLUMN `' . self::KOLOM . '`
             VARCHAR(20) NULL COMMENT \'Status DTKS - xlsx Sheet4 kolom C\''
        );
        if ($ok === FALSE) {
            log_message('error', 'Migrasi 048: ADD COLUMN ' . self::KOLOM . ' GAGAL.');
        }
    }

    public function down()
    {
        if ($this->db->field_exists(self::KOLOM, self::TABEL)) {
            $this->db->query('ALTER TABLE `' . self::TABEL . '` DROP COLUMN `' . self::KOLOM . '`');
        }
    }
}
