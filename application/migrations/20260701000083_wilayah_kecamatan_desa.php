<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kecamatan dan desa/kelurahan Jawa Tengah untuk isian berjenjang Pendataan Warga (permintaan dinas 26 Okt 2026).
 *
 * Data dari data/wilayah_jateng.csv: kode dan nama sesuai Kepmendagri No 300.2.2-2138 Tahun 2025, disaring dari
 * db/wilayah.sql github.com/cahyadsn/wilayah (MIT) untuk provinsi 33 saja: 576 kecamatan, 8.563 desa/kelurahan,
 * nol yatim, 35 kabupatennya sama dengan tabel kabupaten. Kode tanpa titik, sama dengan KodeDagri SIMPERUM
 * (contoh 3302182011 = Banyumas, Karanglewas, Singasari), jadi isian bisa terisi otomatis dari SIMPERUM.
 * Draft pendataan menyimpan kecamatan_id dan desa_id; NULL untuk draft lama.
 */
class Migration_Wilayah_kecamatan_desa extends CI_Migration {

    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS kecamatan (
            id CHAR(6) NOT NULL, kabupaten_id INT UNSIGNED NOT NULL, nama VARCHAR(100) NOT NULL,
            PRIMARY KEY (id), KEY idx_kecamatan_kabupaten (kabupaten_id),
            CONSTRAINT fk_kecamatan_kabupaten FOREIGN KEY (kabupaten_id) REFERENCES kabupaten (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Kecamatan Jawa Tengah (Kepmendagri 300.2.2-2138/2025), kode 6 digit'");
        $this->db->query("CREATE TABLE IF NOT EXISTS desa (
            id CHAR(10) NOT NULL, kecamatan_id CHAR(6) NOT NULL, nama VARCHAR(100) NOT NULL,
            PRIMARY KEY (id), KEY idx_desa_kecamatan (kecamatan_id),
            CONSTRAINT fk_desa_kecamatan FOREIGN KEY (kecamatan_id) REFERENCES kecamatan (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Desa/kelurahan Jawa Tengah (Kepmendagri 300.2.2-2138/2025), kode 10 digit = KodeDagri SIMPERUM'");

        $kecamatan = $desa = [];
        $berkas = fopen(APPPATH . 'migrations/data/wilayah_jateng.csv', 'r');
        while (($baris = fgetcsv($berkas, 0, ',', '"', '')) !== FALSE) {
            [$kode, $nama] = $baris;
            if (strlen($kode) === 6) { $kecamatan[] = ['id' => $kode, 'kabupaten_id' => (int) substr($kode, 0, 4), 'nama' => $nama]; }
            elseif (strlen($kode) === 10) { $desa[] = ['id' => $kode, 'kecamatan_id' => substr($kode, 0, 6), 'nama' => $nama]; }
        }
        fclose($berkas);
        // Ulang aman: tabel diisi ulang dari CSV yang sama.
        $this->db->query('DELETE FROM desa');
        $this->db->query('DELETE FROM kecamatan');
        foreach (array_chunk($kecamatan, 500) as $potong) { $this->db->insert_batch('kecamatan', $potong); }
        foreach (array_chunk($desa, 1000) as $potong) { $this->db->insert_batch('desa', $potong); }

        if ( ! $this->db->field_exists('kecamatan_id', 'sf_penilaian_perumahan')) {
            $this->db->query('ALTER TABLE sf_penilaian_perumahan
                ADD COLUMN kecamatan_id CHAR(6) NULL AFTER kabupaten_id,
                ADD COLUMN desa_id CHAR(10) NULL AFTER kecamatan_id,
                ADD KEY idx_penilaian_kecamatan (kecamatan_id), ADD KEY idx_penilaian_desa (desa_id),
                ADD CONSTRAINT fk_penilaian_kecamatan FOREIGN KEY (kecamatan_id) REFERENCES kecamatan (id) ON DELETE SET NULL,
                ADD CONSTRAINT fk_penilaian_desa FOREIGN KEY (desa_id) REFERENCES desa (id) ON DELETE SET NULL');
        }
    }

    public function down()
    {
        if ($this->db->field_exists('kecamatan_id', 'sf_penilaian_perumahan')) {
            $this->db->query('ALTER TABLE sf_penilaian_perumahan DROP FOREIGN KEY fk_penilaian_desa, DROP FOREIGN KEY fk_penilaian_kecamatan,
                DROP COLUMN desa_id, DROP COLUMN kecamatan_id');
        }
        $this->db->query('DROP TABLE IF EXISTS desa');
        $this->db->query('DROP TABLE IF EXISTS kecamatan');
    }
}
