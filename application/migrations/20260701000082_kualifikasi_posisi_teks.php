<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kolom kualifikasi posisi magang diperbesar dari VARCHAR(255) ke TEXT (22 Okt 2026).
 *
 * Admin kini mengisi kualifikasi sebagai daftar (satu butir per baris, maksimal 15 butir
 * x 150 karakter, ditegakkan Admin_Magang_Posisi::simpan). Batas 255 sudah tercapai di
 * production: isian Programeer terpotong di tengah kata. Data tidak diubah; isian lama
 * berpemisah koma tetap terbaca lewat daftar_kualifikasi().
 */
class Migration_Kualifikasi_posisi_teks extends CI_Migration {

    public function up()
    {
        $this->db->query('ALTER TABLE kkn_magang_posisi MODIFY keterangan TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL');
    }

    public function down()
    {
        // Kembali ke 255 hanya aman bila tidak ada isian yang lebih panjang.
        $panjang = (int) $this->db->query('SELECT COALESCE(MAX(CHAR_LENGTH(keterangan)), 0) n FROM kkn_magang_posisi')->row()->n;
        if ($panjang <= 255) {
            $this->db->query('ALTER TABLE kkn_magang_posisi MODIFY keterangan VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL');
        }
    }
}
