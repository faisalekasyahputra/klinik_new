<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tahap 1 aturan program yang bisa diatur dinas (26 Okt 2026): data kedua mesin rekomendasi pindah dari
 * konstanta kode ke database, TANPA mengubah hasil. Logika pencocokan tetap di kode.
 *
 * - aturan_program: desil sasaran, urutan, kandidat saat desil kosong, dan jenis cek per program
 *   (dulu Warga_ruleset::route_candidates() dan $target_deciles di evaluate()).
 * - aturan_matriks_baris: 20 baris matriks Sheet3 dinas (dulu Matriks_program_ruleset::ROWS).
 *
 * Kolom `versi` = versi aturan yang sudah dipakai (SIM-2026-02 dan MATRIKS-2026-09-10), supaya tahap 2
 * (draf dan terbit versi baru) cukup menambah baris versi lain. Isi di bawah adalah salinan persis
 * konstanta kode per 26 Okt 2026; docs/engineering/uji_aturan_database.php membandingkannya.
 */
class Migration_Aturan_program extends CI_Migration {

    const VERSI_PROGRAM = 'SIM-2026-02';
    const VERSI_MATRIKS = 'MATRIKS-2026-09-10';

    public function up()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS aturan_program (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            versi VARCHAR(30) NOT NULL,
            program_id INT(11) NOT NULL,
            urutan TINYINT UNSIGNED NOT NULL,
            desil_sasaran VARCHAR(30) NOT NULL COMMENT 'Desil 1 sampai 10 dipisah koma',
            kandidat_tanpa_desil TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = ditawarkan saat desil belum ada',
            jenis_cek VARCHAR(30) NOT NULL COMMENT 'rtlh_kerusakan, pb_lahan, swadaya, pembiayaan, belum_tersedia',
            kode_alasan VARCHAR(60) NULL COMMENT 'Kode alasan hasil: potensial (pembiayaan) atau perlu data (belum_tersedia)',
            UNIQUE KEY uq_aturan_program (versi, program_id),
            CONSTRAINT fk_aturan_program FOREIGN KEY (program_id) REFERENCES sf_program (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Aturan rekomendasi per program per versi: desil sasaran dan jenis cek'");

        $this->db->query("CREATE TABLE IF NOT EXISTS aturan_matriks_baris (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            versi VARCHAR(30) NOT NULL,
            urutan TINYINT UNSIGNED NOT NULL COMMENT 'Urutan baris = prioritas, sama dengan urutan Sheet3',
            rentang_penghasilan VARCHAR(30) NOT NULL,
            desil VARCHAR(30) NULL COMMENT 'Desil dipisah koma; NULL = bebas',
            status_dtks VARCHAR(30) NULL,
            lahan VARCHAR(40) NULL,
            rumah VARCHAR(40) NULL,
            lingkungan VARCHAR(40) NULL,
            finansial VARCHAR(60) NULL,
            usia VARCHAR(30) NULL,
            keluarga VARCHAR(40) NULL,
            nama_skema VARCHAR(100) NOT NULL COMMENT 'Kolom J Sheet3, mis. PK RTLH (Prioritas 1)',
            UNIQUE KEY uq_aturan_matriks (versi, urutan)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Baris matriks program dinas per versi (dulu Sheet3 A3:J22 di kode)'");

        if ((int) $this->db->where('versi', self::VERSI_PROGRAM)->count_all_results('aturan_program') === 0) {
            $program = [
                // kode, urutan, desil, kandidat_tanpa_desil, jenis_cek, kode_alasan
                ['rtlh', 1, '1,2,3', 0, 'rtlh_kerusakan', NULL],
                ['pb', 2, '1,2,3,4', 0, 'pb_lahan', NULL],
                ['omah_sekeng', 3, '4', 0, 'swadaya', NULL],
                ['flpp', 4, '5,6,7,8', 1, 'pembiayaan', 'SIM_FLPP_INCOME'],
                ['oemah_lestari', 5, '5,6,7,8,9,10', 1, 'pembiayaan', 'SIM_OEMAH_INCOME'],
                ['rumah_apung', 6, '1,2,3,4,5,6,7,8,9,10', 0, 'belum_tersedia', 'SIM_RUMAH_APUNG_NEEDS_DATA'],
            ];
            foreach ($program as [$kode, $urutan, $desil, $tanpa, $cek, $alasan]) {
                $p = $this->db->select('id')->get_where('sf_program', ['kode_program' => $kode])->row_array();
                if ( ! $p) { throw new RuntimeException("Program $kode tidak ada di sf_program; migrasi 084 dibatalkan."); }
                $this->db->insert('aturan_program', [
                    'versi' => self::VERSI_PROGRAM, 'program_id' => (int) $p['id'], 'urutan' => $urutan,
                    'desil_sasaran' => $desil, 'kandidat_tanpa_desil' => $tanpa, 'jenis_cek' => $cek, 'kode_alasan' => $alasan,
                ]);
            }
        }

        if ((int) $this->db->where('versi', self::VERSI_MATRIKS)->count_all_results('aturan_matriks_baris') === 0) {
            $baris = [
                ['income_gt_8_5', '9,10', NULL, NULL, NULL, NULL, NULL, NULL, 'family_single', 'Oemah Lestari Non-Subsidi'],
                ['income_gt_10', '9,10', NULL, NULL, NULL, NULL, NULL, NULL, 'family_married', 'Oemah Lestari Non-Subsidi'],
                ['income_2_8_8_5', '5,6,7,8', NULL, NULL, 'house_none_or_rent', NULL, 'work_stable_or_unstable_no_subsidy', 'produktif_21', 'family_single', 'KPR-FLPP / Oemah Lestari Subsidi'],
                ['income_2_8_10', '5,6,7,8', NULL, NULL, 'house_none_or_rent', NULL, 'work_stable_or_unstable_no_subsidy', 'produktif_21', 'family_married', 'KPR-FLPP / Oemah Lestari Subsidi'],
                ['income_2_2_2_8', '4', 'dtks_ya', 'land_none', 'house_none_or_rent', 'env_safe', 'work_can_save_irregular', 'produktif_21', NULL, 'KPR-FLPP'],
                ['income_0_1_5', '1', 'dtks_ya', 'land_legal', 'house_rent_or_staying', 'env_safe', NULL, 'produktif_18', 'family_multi_household', 'PB Backlog (Prioritas 1)'],
                ['income_1_5_2_2', '2,3', 'dtks_ya', 'land_legal', 'house_rent_or_staying', 'env_safe', NULL, 'produktif_18', 'family_multi_household', 'PB Backlog (Prioritas 2)'],
                ['income_2_2_2_8', '4', 'dtks_ya', 'land_legal', 'house_rent_or_staying', 'env_safe', NULL, 'produktif_18', 'family_multi_household', 'PB Backlog (Prioritas 3)'],
                ['income_0_1_5', '1', 'dtks_ya', 'land_legal', 'house_restricted_area', 'env_relocation_zone', NULL, NULL, 'family_head_of_household', 'PB Relokasi (Prioritas 1)'],
                ['income_1_5_2_2', '2,3', 'dtks_ya', 'land_legal', 'house_restricted_area', 'env_relocation_zone', NULL, NULL, 'family_head_of_household', 'PB Relokasi (Prioritas 2)'],
                ['income_2_2_2_8', '4', 'dtks_ya', 'land_legal', 'house_restricted_area', 'env_relocation_zone', NULL, NULL, 'family_head_of_household', 'PB Relokasi (Prioritas 3)'],
                ['income_0_1_5', '1', 'dtks_ya', NULL, 'house_disaster_affected', 'env_disaster_severe', NULL, NULL, NULL, 'PB Bencana (Prioritas 1)'],
                ['income_1_5_2_2', '2,3', 'dtks_ya', NULL, 'house_disaster_affected', 'env_disaster_severe', NULL, NULL, NULL, 'PB Bencana (Prioritas 2)'],
                ['income_2_2_2_8', '4', 'dtks_ya', NULL, 'house_disaster_affected', 'env_disaster_severe', NULL, NULL, NULL, 'PB Bencana (Prioritas 3)'],
                ['income_0_1_5', '1', 'dtks_ya', NULL, 'house_disaster_affected', 'env_disaster_moderate', NULL, NULL, NULL, 'PK Bencana (Prioritas 1)'],
                ['income_1_5_2_2', '2,3', 'dtks_ya', NULL, 'house_disaster_affected', 'env_disaster_moderate', NULL, NULL, NULL, 'PK Bencana (Prioritas 2)'],
                ['income_2_2_2_8', '4', 'dtks_ya', NULL, 'house_disaster_affected', 'env_disaster_moderate', NULL, NULL, NULL, 'PK Bencana (Prioritas 3)'],
                ['income_0_1_5', '1', 'dtks_ya', NULL, 'house_owned', 'env_slum_uninhabitable', NULL, 'produktif_18_or_tua', NULL, 'PK RTLH (Prioritas 1)'],
                ['income_1_5_2_2', '2,3', 'dtks_ya', NULL, 'house_owned', 'env_slum_uninhabitable', NULL, 'produktif_18_or_tua', NULL, 'PK RTLH (Prioritas 2)'],
                ['income_2_2_2_8', '4', 'dtks_ya', NULL, 'house_owned', 'env_slum_uninhabitable', NULL, 'produktif_18_or_tua', NULL, 'PK RTLH (Prioritas 3)'],
            ];
            foreach ($baris as $i => $b) {
                $this->db->insert('aturan_matriks_baris', array_combine(
                    ['rentang_penghasilan', 'desil', 'status_dtks', 'lahan', 'rumah', 'lingkungan', 'finansial', 'usia', 'keluarga', 'nama_skema'], $b)
                    + ['versi' => self::VERSI_MATRIKS, 'urutan' => $i + 1]);
            }
        }
    }

    public function down()
    {
        $this->db->query('DROP TABLE IF EXISTS aturan_matriks_baris');
        $this->db->query('DROP TABLE IF EXISTS aturan_program');
    }
}
