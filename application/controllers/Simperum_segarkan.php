<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Penyegaran MINGGUAN cermin data SIMPERUM (sf_data_simperum, migrasi 064) dari CLI/cron.
 *
 *   php index.php simperum_segarkan index 100
 *
 * Hanya GET, hanya NIK warga yang terdaftar di web ini: baris cermin yang next_refresh_at-nya
 * jatuh tempo, lalu akun warga ber-NIK yang belum punya baris. Tidak pernah menarik per
 * KodeDagri. Profil dan draft warga (termasuk koreksinya) tidak disentuh; lihat
 * Simperum_gateway::segarkan(). Keluaran hanya angka, tanpa NIK atau nama.
 *
 * Hanya CLI: lewat web dijawab 404 (pola Retensi).
 */
class Simperum_segarkan extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        if ( ! $this->input->is_cli_request()) { show_404(); exit; }
    }

    public function index($batas = 100)
    {
        $this->load->library('Simperum_gateway');
        $antrean = $this->Housing_assessment_model->antrean_segarkan_simperum((int) $batas);
        $hitung = ['diproses' => 0, 'found' => 0, 'not_found' => 0, 'error' => 0, 'dilewati' => 0];
        foreach ($antrean as $i => $item) {
            if ($i > 0) {
                usleep(1000000); // jeda sekitar 1 detik antar NIK, supaya server dinas tidak dibanjiri
            }
            $status = $this->simperum_gateway->segarkan($item['nik'], $item['user_id']);
            if ($status === 'api_not_configured') {
                echo "Berhenti: mode api tetapi koneksi SIMPERUM belum dikonfigurasi (SIMPERUM_BASE_URL/kunci di .env).\n";
                exit(1);
            }
            $hitung['diproses']++;
            if (isset($hitung[$status])) {
                $hitung[$status]++;
            } elseif (in_array($status, ['unbound', 'invalid'], TRUE)) {
                $hitung['dilewati']++;
            } else {
                $hitung['error']++;
            }
        }
        echo "Penyegaran SIMPERUM\n";
        foreach ($hitung as $nama => $n) {
            echo sprintf("  %-10s %6d\n", $nama, $n);
        }
    }
}
