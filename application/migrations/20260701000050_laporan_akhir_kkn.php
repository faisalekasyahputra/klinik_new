<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Laporan Akhir KKN - permintaan user 24 Agt 2026: "buat drag and drop
 * baru untuk mengupload laporan", diklarifikasi sebagai laporan akhir KKN,
 * hanya boleh diunggah setelah periode KKN berakhir.
 *
 * Satu kolom baru di kkn_magang_pendaftaran, pola PERSIS sama dengan
 * file_surat_simperum (migrasi 044): VARCHAR(255) NULL, nama berkas acak
 * tersimpan lewat store_private_upload() (lihat MY_Controller.php), bukan
 * berkas itu sendiri. Gerbang waktu (periode_selesai sudah lewat) DITEGAKKAN
 * DI CONTROLLER (KemitraanPortal::kkn_upload_laporan()), bukan di skema -
 * kolom ini sendiri tidak tahu apa-apa soal tanggal.
 */
class Migration_Laporan_akhir_kkn extends CI_Migration {

    const TABEL = 'kkn_magang_pendaftaran';
    const KOLOM = 'file_laporan_akhir';

    public function up()
    {
        if ( ! $this->db->table_exists(self::TABEL)) {
            log_message('error', 'Migrasi 050: tabel ' . self::TABEL . ' tidak ada.');
            return;
        }
        if ($this->db->field_exists(self::KOLOM, self::TABEL)) { return; }
        $ok = $this->db->query(
            'ALTER TABLE `' . self::TABEL . '` ADD COLUMN `' . self::KOLOM . '`
             VARCHAR(255) NULL COMMENT \'Laporan akhir KKN, diunggah universitas setelah periode selesai. NULL = belum diunggah.\'
             AFTER `file_surat_simperum`'
        );
        if ($ok === FALSE) {
            log_message('error', 'Migrasi 050: ADD COLUMN ' . self::KOLOM . ' GAGAL.');
        }
    }

    public function down()
    {
        if ($this->db->table_exists(self::TABEL) && $this->db->field_exists(self::KOLOM, self::TABEL)) {
            $this->db->query('ALTER TABLE `' . self::TABEL . '` DROP COLUMN `' . self::KOLOM . '`');
        }
    }
}
