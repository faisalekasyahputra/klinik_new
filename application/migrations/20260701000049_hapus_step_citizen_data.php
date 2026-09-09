<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Step wizard 'citizen_data' (Data Warga) DIHAPUS PERMANEN 24 Agt 2026 -
 * permintaan user: "2 dan 5 gabung jadi 1 form di 5, lalu hapus 2" (mengacu
 * ke stepper 5-bucket: bucket 2 = "Data Warga", bucket 5 = "Lengkapi Data
 * SIMPERUM"). Field-fieldnya sekarang bagian dari form 'housing_family_detail'
 * - lihat komentar STEPS di Warga.php dan sub-bagian "Data Warga" di
 * pendataan.php.
 *
 * 'citizen_data' sudah dicabut dari Warga::STEPS, jadi draft LAMA yang masih
 * berhenti tepat di step itu akan macet: guard di Warga::save() menolak
 * step apa pun yang tidak ada di STEPS
 * (`! in_array($step, self::STEPS, TRUE)`), dan view (pendataan.php)
 * jatuh balik ke tampilan 'find_data' begitu step_slug tidak dikenali -
 * membuat warga terjebak mengulang pencarian NIK tanpa pernah maju.
 *
 * Migrasi data (bukan skema) ini memindahkan draft yang tersangkut itu ke
 * 'housing_family' - step pertama yang sah sesudah find_data pada urutan
 * BARU. Data yang sudah terisi (di sf_penilaian_perumahan maupun
 * sf_profil_warga) tidak tersentuh sama sekali; warga hanya perlu melalui
 * form gabungan yang baru sekali lagi dari awal urutan itu.
 */
class Migration_Hapus_step_citizen_data extends CI_Migration {

    const TABEL = 'sf_penilaian_perumahan';

    public function up()
    {
        if ( ! $this->db->table_exists(self::TABEL)) {
            log_message('error', 'Migrasi 049: tabel ' . self::TABEL . ' tidak ada.');
            return;
        }
        $ok = $this->db->query(
            "UPDATE `" . self::TABEL . "` SET `current_step` = 'housing_family'
             WHERE `current_step` = 'citizen_data'"
        );
        if ($ok === FALSE) {
            log_message('error', 'Migrasi 049: UPDATE current_step GAGAL.');
        }
    }

    public function down()
    {
        /* SENGAJA tidak dibalik: sesudah migrasi ini berjalan, draft BARU
           yang baru saja dibuat juga sah berhenti di 'housing_family' -
           tidak ada cara membedakan baris itu dari bekas 'citizen_data'
           tanpa mencatat ID-nya lebih dulu. Membalik UPDATE secara naif
           (housing_family -> citizen_data) akan salah mengembalikan draft
           baru itu ke step yang sudah tidak dikenal STEPS pasca-rollback
           kode, bukan cuma bekas 'citizen_data'. */
    }
}
