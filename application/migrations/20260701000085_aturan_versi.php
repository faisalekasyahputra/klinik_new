<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tahap 2 aturan program yang bisa diatur dinas (26 Okt 2026): versi aturan dengan draf, terbit, dan arsip.
 *
 * - aturan_versi: satu baris per versi. Mesin rekomendasi selalu memakai versi berstatus `terbit` terbaru;
 *   Super Admin menyunting `draf` (paling banyak satu), lalu menerbitkannya, dan versi terbit sebelumnya
 *   menjadi `arsip` (tetap tersimpan, rekomendasi lama menunjuk ke kodenya lewat versi_aturan).
 * - Satu kode versi untuk kedua mesin: baris matriks MATRIKS-2026-09-10 DISALIN ke SIM-2026-02 (kode yang sudah
 *   tersimpan di sf_rekomendasi_penilaian). Baris lamanya dibiarkan sebagai versi arsip supaya kode PR #113 yang
 *   masih tayang di antara migrasi dan push kode tetap menemukan matriksnya (urutan rilis §0a).
 * - sf_program.syarat_utama menjadi TEXT: syarat yang ditulis dinas per baris tidak muat 300 karakter.
 */
class Migration_Aturan_versi extends CI_Migration {

    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS aturan_versi (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            kode VARCHAR(30) NOT NULL,
            status VARCHAR(10) NOT NULL COMMENT 'draf, terbit, arsip',
            catatan VARCHAR(255) NULL,
            dibuat_oleh INT NULL,
            diterbitkan_oleh INT NULL,
            diterbitkan_pada DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_aturan_versi_kode (kode),
            KEY idx_aturan_versi_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Versi aturan rekomendasi: draf, terbit (dipakai mesin), arsip'");

        foreach ([['SIM-2026-02', 'terbit', 'Aturan awal dari kode (migrasi 084)'],
                  ['MATRIKS-2026-09-10', 'arsip', 'Matriks lama untuk kode sebelum migrasi 085; disalin ke SIM-2026-02']] as [$kode, $status, $catatan]) {
            if ( ! $this->db->where('kode', $kode)->count_all_results('aturan_versi')) {
                $this->db->insert('aturan_versi', ['kode' => $kode, 'status' => $status, 'catatan' => $catatan, 'diterbitkan_pada' => '2026-07-28 00:00:00']);
            }
        }
        if ( ! $this->db->where('versi', 'SIM-2026-02')->count_all_results('aturan_matriks_baris')) {
            $this->db->query("INSERT INTO aturan_matriks_baris (versi, urutan, rentang_penghasilan, desil, status_dtks, lahan, rumah, lingkungan, finansial, usia, keluarga, nama_skema)
                SELECT 'SIM-2026-02', urutan, rentang_penghasilan, desil, status_dtks, lahan, rumah, lingkungan, finansial, usia, keluarga, nama_skema
                FROM aturan_matriks_baris WHERE versi = 'MATRIKS-2026-09-10' ORDER BY urutan");
        }

        foreach (['aturan_program' => 'fk_aturan_program_versi', 'aturan_matriks_baris' => 'fk_aturan_matriks_versi'] as $tabel => $fk) {
            $ada = $this->db->query("SELECT COUNT(*) n FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?", [$tabel, $fk])->row('n');
            if ( ! (int) $ada) {
                $this->db->query("ALTER TABLE $tabel ADD CONSTRAINT $fk FOREIGN KEY (versi) REFERENCES aturan_versi (kode) ON DELETE CASCADE");
            }
        }
        $this->db->query("ALTER TABLE sf_program MODIFY syarat_utama TEXT NULL COMMENT 'Syarat utama program, boleh beberapa baris'");
    }

    public function down()
    {
        if ($this->db->where('status', 'terbit')->where('kode !=', 'SIM-2026-02')->count_all_results('aturan_versi')) {
            throw new RuntimeException('Ada versi aturan terbit selain SIM-2026-02; terbitkan ulang versi itu dulu sebelum turun dari 085.');
        }
        $this->db->query('ALTER TABLE aturan_program DROP FOREIGN KEY fk_aturan_program_versi');
        $this->db->query('ALTER TABLE aturan_matriks_baris DROP FOREIGN KEY fk_aturan_matriks_versi');
        $this->db->query("DELETE FROM aturan_program WHERE versi <> 'SIM-2026-02'");
        $this->db->query("DELETE FROM aturan_matriks_baris WHERE versi <> 'MATRIKS-2026-09-10'");
        $this->db->query('DROP TABLE IF EXISTS aturan_versi');
        if ((int) $this->db->query('SELECT COALESCE(MAX(CHAR_LENGTH(syarat_utama)), 0) n FROM sf_program')->row('n') <= 300) {
            $this->db->query("ALTER TABLE sf_program MODIFY syarat_utama VARCHAR(300) NULL COMMENT 'Satu kalimat syarat yang tampil di slide'");
        }
    }
}
