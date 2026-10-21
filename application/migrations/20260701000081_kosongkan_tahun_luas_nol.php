<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tahun bantuan dan luas rumah bernilai 0 dijadikan NULL (temuan 22 Okt 2026).
 *
 * Isian opsional yang dibiarkan kosong dulu terkirim sebagai string kosong ke kolom angka, dan
 * MySQL (mode tidak ketat) mencatatnya 0. Formulir menolak 0 (tahun minimal 1900, luas minimal
 * 0,01), jadi warga yang membuka lagi langkah itu (Kembali atau Mulai Perbaikan) tertahan.
 * Penyebabnya ditutup di Housing_assessment_model::update_owned_draft (string kosong menjadi
 * NULL); migrasi ini membersihkan baris lama. 0 tidak pernah nilai sah di dua kolom ini, jadi
 * baris submitted ikut dibersihkan: salinan perbaikan menyalin baris itu apa adanya.
 *
 * down() sengaja kosong: mengembalikan 0 berarti mengembalikan bug.
 */
class Migration_Kosongkan_tahun_luas_nol extends CI_Migration {

    public function up()
    {
        $this->db->query('UPDATE sf_penilaian_perumahan SET tahun_intervensi = NULL WHERE tahun_intervensi = 0');
        $this->db->query('UPDATE sf_penilaian_perumahan SET luas_rumah = NULL WHERE luas_rumah = 0');
    }

    public function down()
    {
    }
}
