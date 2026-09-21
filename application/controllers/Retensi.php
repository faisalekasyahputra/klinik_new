<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pelari penyapu retensi dari CLI/cron (form keamanan poin 7.3).
 *
 *   php index.php retensi jalankan          menghapus yang kedaluwarsa dan mencatatnya di jejak audit
 *   php index.php retensi jalankan kering   hanya menghitung, tidak mengubah apa pun
 *
 * Hanya CLI: lewat web dijawab 404. Di web, penyapu yang sama berjalan otomatis sekali per hari
 * (MY_Controller::jadwalkan_retensi); pelari ini untuk pemeriksaan manual dan untuk penjadwal
 * eksternal bila hosting menyediakannya.
 */
class Retensi extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        if ( ! $this->input->is_cli_request()) { show_404(); exit; }
    }

    public function jalankan($mode = '')
    {
        $kering = ($mode === 'kering');
        $this->load->library('Penyapu_retensi');
        $hasil = $this->penyapu_retensi->jalankan($kering);
        if ( ! $kering) { $this->penyapu_retensi->catat($hasil, 'cli'); }

        echo 'Penyapu retensi' . ($kering ? ' (KERING: hanya menghitung)' : '') . "\n";
        foreach ($hasil['tugas'] as $nama => $h) {
            echo sprintf("  %-26s %6d%s\n", $nama, $h['jumlah'], $h['galat'] !== NULL ? '  [galat: ' . $h['galat'] . ']' : '');
        }
        echo '  ' . str_repeat('-', 34) . "\n  " . sprintf("%-26s %6d\n", 'total', $hasil['total']);
    }
}
