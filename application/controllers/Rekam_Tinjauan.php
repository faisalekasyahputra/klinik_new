<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rekam Data — Peninjauan provinsi oleh Admin Bidang (tahap D6).
 *
 * Scope-nya BIDANG, bukan wilayah: Admin Bidang Perumahan melihat seluruh
 * kabupaten/kota tetapi hanya domain perumahan, dan sebaliknya. Bidang lain
 * (pertanahan, pengembang, umum) tidak punya modul ini sama sekali — itu
 * dinyatakan apa adanya, bukan diam-diam menampilkan layar kosong.
 *
 * Acuan: docs/product/ROADMAP_REKAM_DATA.md (D6)
 */
class Rekam_Tinjauan extends Admin_Bidang_Controller {

    /** Bidang yang punya modul Rekam Data, dipetakan ke domain laporannya. */
    private const BIDANG_KE_DOMAIN = [
        'perumahan' => 'perumahan',
        'kawasan'   => 'kawasan',
    ];

    private $domain;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Rekam_data_model', 'rd');

        if ( ! isset(self::BIDANG_KE_DOMAIN[$this->my_bidang_kode])) {
            $this->session->set_flashdata('error',
                'Rekam Data hanya untuk Bidang Perumahan dan Bidang Kawasan Permukiman.');
            redirect($this->dashboard_home());
        }
        $this->domain = self::BIDANG_KE_DOMAIN[$this->my_bidang_kode];
    }

    public function index()
    {
        $tahun = (int) ($this->input->get('tahun') ?: date('Y'));
        $triwulan = $this->input->get('triwulan') !== NULL && $this->input->get('triwulan') !== ''
            ? (int) $this->input->get('triwulan') : NULL;

        $this->render_scoped_admin('admin/rekam/tinjauan_daftar', [
            'title'   => 'Peninjauan ' . ucfirst($this->domain),
            'domain'  => $this->domain,
            'tahun'   => $tahun,
            'triwulan'   => $triwulan,
            'laporan' => $this->rd->daftar_tinjauan($this->domain, $tahun, $triwulan),
        ]);
    }

    public function detail($laporan_id = 0)
    {
        $isi = $this->rd->laporan_bidang((int) $laporan_id, $this->domain);
        if ( ! $isi) {
            // Domain lain = tidak ada, bukan "ditolak". Tidak membocorkan
            // bahwa laporannya memang ada di bidang sebelah.
            show_404();
            return;
        }

        $kabupaten = $this->db->where('id', (int) $isi['laporan']['kabupaten_id'])
            ->get('kabupaten')->row('nama') ?: '—';

        $this->render_scoped_admin('admin/rekam/tinjauan_detail', [
            'title'      => 'Detail Laporan ' . ucfirst($this->domain),
            'domain'     => $this->domain,
            'kabupaten'  => $kabupaten,
            'isi'        => $isi,
            'laporan'    => $isi['laporan'],
            'label'      => $this->rd->label_domain($this->domain),
            // Layar INI yang memutuskan. View yang sama juga dipakai Pantau
            // Rekam Data (superadmin) yang read-only, dan di sana nilainya FALSE.
            'boleh_putuskan' => TRUE,
        ]);
    }

    public function terima()
    {
        $laporan_id = (int) $this->input->post('laporan_id');
        if ( ! $this->gerbang_keputusan($laporan_id)) {
            return;
        }
        $hasil = $this->rd->terima($laporan_id, $this->get_user_id(), $this->domain);
        $this->pulang($hasil, 'Laporan diterima.');
    }

    public function minta_perbaikan()
    {
        $laporan_id = (int) $this->input->post('laporan_id');
        if ( ! $this->gerbang_keputusan($laporan_id)) {
            return;
        }
        $hasil = $this->rd->minta_perbaikan($laporan_id, $this->get_user_id(),
            $this->input->post('catatan_admin', TRUE), $this->domain);
        $this->pulang($hasil, 'Laporan dikembalikan untuk diperbaiki.');
    }

    // ---------------------------------------------------------------- internal

    /**
     * Metode POST + rate limit keputusan. Memakai policy `admin_queue_decision`
     * yang SUDAH ADA (dimensi ip/account/object) — §17 poin 15 melarang membuat
     * mekanisme pembatas kedua.
     */
    private function gerbang_keputusan($laporan_id)
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return FALSE;
        }
        $rate = $this->rate_limit_consume('admin_queue_decision', [
            'account_id' => (int) $this->get_user_id(),
            'object_id'  => $laporan_id,
        ]);
        if (empty($rate['success']) || empty($rate['allowed'])) {
            $this->rate_limit_reject($rate,
                'Terlalu banyak keputusan dalam waktu singkat. Coba lagi sebentar.',
                $this->input->is_ajax_request());
            return FALSE;
        }
        return TRUE;
    }

    private function pulang($hasil, $pesan_sukses)
    {
        if (empty($hasil['success'])) {
            $this->session->set_flashdata('error', $hasil['message'] ?? 'Keputusan tidak tersimpan.');
        } else {
            $this->session->set_flashdata('success', $pesan_sukses);
        }
        redirect('Rekam_Tinjauan');
    }

    // `label_domain()` DIPINDAH ke Rekam_data_model 4 Agt 2026, saat layar
    // Pantau superadmin butuh label yang sama persis. Dua salinan daftar nama
    // program akan menyimpang, dan yang menyimpang di situ bukan tampilan
    // melainkan arti angkanya.
}
