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
            redirect('dashboard');
        }
        $this->domain = self::BIDANG_KE_DOMAIN[$this->my_bidang_kode];
    }

    public function index()
    {
        $tahun = (int) ($this->input->get('tahun') ?: date('Y'));
        $bulan = $this->input->get('bulan') !== NULL && $this->input->get('bulan') !== ''
            ? (int) $this->input->get('bulan') : NULL;

        $this->render_scoped_admin('admin/rekam/tinjauan_daftar', [
            'title'   => 'Peninjauan ' . ucfirst($this->domain),
            'domain'  => $this->domain,
            'tahun'   => $tahun,
            'bulan'   => $bulan,
            'laporan' => $this->rd->daftar_tinjauan($this->domain, $tahun, $bulan),
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
            'label'      => $this->label_domain(),
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

    /** Label per domain supaya satu view detail melayani keduanya. */
    private function label_domain()
    {
        if ($this->domain === 'perumahan') {
            return [
                'sumber' => [
                    'apbd_kabkota' => 'APBD Kabupaten/Kota', 'apbn_bsps' => 'APBN BSPS (dari Kementerian PKP)',
                    'apbn_dak' => 'APBN DAK', 'apbn_kemensos' => 'APBN Kemensos',
                    'apbn_dana_desa' => 'APBN Dana Desa', 'apbn_kl_lain' => 'APBN dari Kementerian/Lembaga Lain',
                    'baznas_ri' => 'BAZNAS RI', 'baznas_kabkota' => 'BAZNAS Kab/Kota',
                    'csr' => 'CSR', 'dana_lainnya' => 'Dana Lainnya',
                ],
                'program' => [
                    'pk_rtlh' => 'PK RTLH', 'pb_rtlh' => 'PB RTLH', 'pb_backlog' => 'PB BACKLOG',
                    'pk_bencana' => 'PK BENCANA', 'pb_bencana' => 'PB BENCANA', 'pb_relokasi' => 'PB RELOKASI',
                ],
            ];
        }
        return [
            'indikator' => [
                'bangunan_gedung' => 'Bangunan Gedung', 'jalan_lingkungan' => 'Jalan Lingkungan',
                'air_minum' => 'Air Minum', 'drainase' => 'Drainase', 'air_limbah' => 'Air Limbah',
                'persampahan' => 'Persampahan', 'proteksi_kebakaran' => 'Proteksi Kebakaran',
            ],
            'sumber' => [
                'apbn' => 'APBN', 'apbd_provinsi' => 'APBD Provinsi', 'apbd_kabkota' => 'APBD Kab/Kota',
                'dana_desa' => 'Dana Desa', 'csr' => 'CSR', 'baznas' => 'Baznas',
                'dana_lainnya' => 'Dana Lainnya',
            ],
        ];
    }
}
