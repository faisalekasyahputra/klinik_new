<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 5 kolom baru di sf_penilaian_perumahan untuk step "housing_family" versi
 * baru - permintaan user 23 Agt 2026: ganti field step itu dengan yang ada
 * di "MATRIKS VARIABEL PENENTUAN PROGRAM PERUMAHAN.xlsx" Sheet4, kolom yang
 * ditandai '*' (Kepemilikan Lahan, Kepemilikan Rumah Saat Ini, Kondisi
 * Lingkungan/Fisik Bangunan, Pekerjaan/Kondisi Finansial, Status
 * Perkawinan/Keluarga - "Kategori Usia*" TIDAK dapat kolom sendiri, dihitung
 * otomatis dari tanggal lahir yang sudah ada, atas persetujuan user).
 *
 * FIELD BARU, BUKAN MENIMPA housing_status_code/land_title_code/
 * area_condition_code YANG SUDAH ADA - keputusan sadar user (dikonfirmasi
 * lewat pertanyaan eksplisit): ketiga field lama itu sudah dipakai
 * Warga_ruleset.php (mesin rekomendasi yang JALAN sekarang) dan auto-terisi
 * dari Simperum_gateway::normalize_api_record() dengan kosakata jawaban
 * yang BEDA dari teks literal di xlsx (mis. field lama pakai kode pendek
 * 'owned'/'rent', xlsx pakai frasa panjang "Punya Rumah Sendiri"). Menimpa
 * field lama dengan kosakata baru akan membuat KEDUA sistem itu salah baca
 * data tanpa perubahan tambahan di tempat lain. Field lama TETAP ADA di
 * skema dan formulir - cuma dipindah ke step baru "housing_family_detail"
 * (bagian dari "Lengkapi data SIMPERUM"), bukan dihapus.
 *
 * Kosakata jawaban tiap kolom = teks PERSIS yang muncul di sel-sel Sheet4
 * (lihat komentar di pendataan.php step 'housing_family' untuk daftar
 * lengkapnya), diberi KODE PENDEK (bukan menyimpan frasa panjang mentah di
 * DB) - pola yang SAMA dipakai semua kolom *_code lain di tabel ini.
 */
class Migration_Matriks_variabel_program extends CI_Migration {

    const TABEL = 'sf_penilaian_perumahan';

    const KOLOM = [
        'matrix_land_ownership_code' => "VARCHAR(30) NULL COMMENT 'Kepemilikan Lahan* - xlsx Sheet4 kolom D'",
        'matrix_current_housing_code' => "VARCHAR(40) NULL COMMENT 'Kepemilikan Rumah Saat Ini* - xlsx Sheet4 kolom E'",
        'matrix_environment_condition_code' => "VARCHAR(30) NULL COMMENT 'Kondisi Lingkungan/Fisik Bangunan* - xlsx Sheet4 kolom F'",
        'matrix_occupation_finance_code' => "VARCHAR(30) NULL COMMENT 'Pekerjaan/Kondisi Finansial* - xlsx Sheet4 kolom G'",
        'matrix_marital_family_code' => "VARCHAR(30) NULL COMMENT 'Status Perkawinan/Keluarga* - xlsx Sheet4 kolom I'",
    ];

    public function up()
    {
        if ( ! $this->db->table_exists(self::TABEL)) {
            log_message('error', 'Migrasi 045: tabel ' . self::TABEL . ' tidak ada.');
            return;
        }
        foreach (self::KOLOM as $kolom => $definisi) {
            if ($this->db->field_exists($kolom, self::TABEL)) { continue; }
            $ok = $this->db->query(
                'ALTER TABLE `' . self::TABEL . '` ADD COLUMN `' . $kolom . '` ' . $definisi
            );
            if ($ok === FALSE) {
                log_message('error', 'Migrasi 045: ADD COLUMN ' . $kolom . ' GAGAL.');
            }
        }
    }

    public function down()
    {
        foreach (array_keys(self::KOLOM) as $kolom) {
            if ($this->db->field_exists($kolom, self::TABEL)) {
                $this->db->query('ALTER TABLE `' . self::TABEL . '` DROP COLUMN `' . $kolom . '`');
            }
        }
    }
}
