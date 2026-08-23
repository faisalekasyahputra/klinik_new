<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Perbaikan migrasi 045: dua kolom terlalu sempit untuk kode yang
 * sungguh dipakai, ketahuan lewat tes langsung (bukan dibaca dari kode
 * saja) - VARCHAR(30) diam-diam MEMOTONG
 * 'work_stable_or_unstable_no_subsidy' (35 karakter) jadi
 * 'work_stable_or_unstable_no_sub' begitu tersimpan (MySQL non-strict
 * mode tidak menolak, cuma memotong tanpa galat) - dikonfirmasi lewat
 * SELECT langsung ke sf_penilaian_perumahan sesudah submit uji coba.
 * 'family_unrestricted_single_or_married' (38 karakter) untuk
 * matrix_marital_family_code punya risiko sama, walau belum sempat
 * teruji manual saat ditemukan.
 *
 * Dilebarkan ke 50 (bukan pas-pasan ke panjang saat ini) - kode pendek
 * di pendataan.php/Warga.php bisa berubah lagi tanpa perlu migrasi
 * lebar kolom berulang setiap kali.
 */
class Migration_Matriks_variabel_lebar_kolom extends CI_Migration {

    const TABEL = 'sf_penilaian_perumahan';
    const KOLOM = ['matrix_occupation_finance_code', 'matrix_marital_family_code'];

    public function up()
    {
        if ( ! $this->db->table_exists(self::TABEL)) {
            log_message('error', 'Migrasi 046: tabel ' . self::TABEL . ' tidak ada.');
            return;
        }
        foreach (self::KOLOM as $kolom) {
            if ( ! $this->db->field_exists($kolom, self::TABEL)) {
                log_message('error', 'Migrasi 046: kolom ' . $kolom . ' tidak ada, lewati.');
                continue;
            }
            $ok = $this->db->query(
                'ALTER TABLE `' . self::TABEL . '` MODIFY COLUMN `' . $kolom . '` VARCHAR(50) NULL'
            );
            if ($ok === FALSE) {
                log_message('error', 'Migrasi 046: MODIFY ' . $kolom . ' GAGAL.');
            }
        }
    }

    public function down()
    {
        // Sengaja TIDAK mengecilkan balik ke VARCHAR(30) - bisa memotong
        // data yang sudah tersimpan dengan panjang penuh. down() cuma
        // no-op yang aman; lebar kolom bukan sesuatu yang perlu "dibatalkan".
    }
}
