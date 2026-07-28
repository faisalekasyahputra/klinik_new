<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Nama tabel domain warga memakai Bahasa Indonesia.
 *
 * Tabel legacy usr_users, sf_programs, dan sf_housing_queue tetap dipertahankan
 * karena sudah menjadi kontrak production sebelum modul ini dibuat.
 */
class Migration_Indonesian_warga_table_names extends CI_Migration {

    private const TABLES = [
        'sf_citizen_profiles' => 'sf_profil_warga',
        'sf_simperum_snapshots' => 'sf_rekaman_simperum',
        'sf_housing_assessments' => 'sf_penilaian_perumahan',
        'sf_assessment_files' => 'sf_berkas_penilaian',
        'sf_assessment_recommendations' => 'sf_rekomendasi_penilaian',
    ];

    public function up()
    {
        foreach (self::TABLES as $lama => $baru) {
            if ($this->table_exists($lama) && ! $this->table_exists($baru)) {
                $this->db->query("RENAME TABLE `{$lama}` TO `{$baru}`");
            }
        }
    }

    public function down()
    {
        foreach (array_reverse(self::TABLES, TRUE) as $lama => $baru) {
            if ($this->table_exists($baru) && ! $this->table_exists($lama)) {
                $this->db->query("RENAME TABLE `{$baru}` TO `{$lama}`");
            }
        }
    }

    private function table_exists($table)
    {
        return (bool) $this->db->query(
            'SELECT 1 FROM information_schema.TABLES
             WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1',
            [$table]
        )->row();
    }
}
