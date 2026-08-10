<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Pantau Rekam Data - pandangan superadmin lintas kabupaten & lintas domain.
 *
 * LAHIR DARI KLAIM YANG TIDAK COCOK DENGAN KODE. Dinas menulis "belum ada
 * rekap/submit" (revisi 3 Agt 2026, butir 10). Kenyataannya wizard lima
 * langkah, `kirim()`, capaian, `/Rekam_Perumahan/rekap`, riwayat, dan
 * peninjauan bidang SEMUANYA sudah ada sejak rilis 30 Jul. Yang tidak ada
 * adalah **layar rekam data untuk superadmin** - dan superadmin yang dipakai
 * reviewer dinas. Dari kursi itu, fitur yang lengkap memang tidak terlihat
 * sama sekali.
 *
 * READ-ONLY, dan itu bukan sekadar "tidak ada tombol": nol endpoint tulis di
 * kelas ini. Keputusan terima/minta-perbaikan tetap kewenangan Admin Bidang
 * (`Rekam_Tinjauan`). Jalur tulis kedua ke keputusan yang sama adalah cara
 * paling rapi untuk menimpa keputusan orang tanpa jejak - bandingkan alasan
 * yang sama di `Admin_Aduan`.
 *
 * YANG MEMBEDAKANNYA DARI LAYAR YANG SUDAH ADA: papan ini bertolak dari
 * DAFTAR KABUPATEN, bukan dari daftar laporan. Semua layar rekam data lain
 * membaca `rd_laporan`, jadi tidak satu pun bisa menampilkan kabupaten yang
 * belum melapor - mereka tidak punya baris. Padahal "siapa yang belum" persis
 * pertanyaan yang dibawa dinas.
 */
class Admin_Rekam_Data extends Admin_Controller {

    private const DOMAIN = ['perumahan' => 'Perumahan', 'kawasan' => 'Kawasan Permukiman'];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Rekam_data_model', 'rd');
    }

    public function index()
    {
        $tahun = (int) ($this->input->get('tahun') ?: date('Y'));
        // Triwulan berjalan, bukan 1. Default yang salah membuat papan tampak
        // kosong seluruhnya di awal tahun dan orang menyimpulkan fiturnya rusak.
        $triwulan = (int) $this->input->get('triwulan');
        if ($triwulan < 1 || $triwulan > 4) {
            $triwulan = (int) ceil((int) date('n') / 3);
        }

        // Satu baris per kabupaten, kedua domain disandingkan. Dua panggilan,
        // bukan satu query cross-join: 70 baris, dan yang dibaca orang berikutnya
        // adalah dua SELECT sederhana, bukan satu yang pintar.
        $baris = [];
        $ringkas = [];
        foreach (array_keys(self::DOMAIN) as $domain) {
            $ringkas[$domain] = ['total' => 0, 'masuk' => 0, 'diterima' => 0, 'belum' => 0];
            foreach ($this->rd->pantau($domain, $tahun, $triwulan) as $r) {
                [$kunci, $label] = $this->rd->keadaan_laporan($r);
                $baris[$r['kabupaten_id']]['kabupaten'] = $r['kabupaten'];
                $baris[$r['kabupaten_id']][$domain] = $r + ['keadaan' => $kunci, 'keadaan_label' => $label];

                $ringkas[$domain]['total']++;
                if ($kunci === 'belum')                        { $ringkas[$domain]['belum']++; }
                if (in_array($kunci, ['menunggu', 'diterima', 'perbaikan'], TRUE)) { $ringkas[$domain]['masuk']++; }
                if ($kunci === 'diterima')                     { $ringkas[$domain]['diterima']++; }
            }
        }

        $this->render_admin('admin/rekam/pantau', [
            'title'     => 'Pantau Rekam Data',
            'base_url'  => 'Admin_Rekam_Data',
            'tahun'     => $tahun,
            'triwulan'  => $triwulan,
            'domain'    => self::DOMAIN,
            'baris'     => $baris,
            'ringkas'   => $ringkas,
            // Rentang tahun dari DATA, bukan dari daftar yang ditulis di view -
            // daftar tahun yang dipatok akan basi tanpa ada yang menyadarinya.
            'tahun_ada' => array_column(
                $this->db->select('DISTINCT tahun', FALSE)->order_by('tahun', 'DESC')
                    ->get('rd_laporan')->result_array(), 'tahun'),
        ]);
    }

    /**
     * Detail satu laporan, read-only. Memakai view yang SAMA dengan peninjauan
     * bidang - `boleh_putuskan` FALSE mematikan blok keputusannya.
     *
     * Tidak ada gerbang domain di sini (bandingkan `Rekam_Tinjauan` yang
     * mengunci bidang): superadmin memang berwenang lintas domain. Gerbangnya
     * ada di kelas basenya.
     */
    public function detail($laporan_id = 0)
    {
        $isi = $this->rd->isi_laporan((int) $laporan_id);
        if ( ! $isi) { show_404(); return; }

        $laporan = $isi['laporan'];
        $kabupaten = $this->db->where('id', (int) $laporan['kabupaten_id'])
            ->get('kabupaten')->row('nama') ?: '-';

        $this->render_admin('admin/rekam/tinjauan_detail', [
            'title'          => 'Laporan ' . ucfirst($laporan['domain']) . ' - ' . $kabupaten,
            'domain'         => $laporan['domain'],
            'kabupaten'      => $kabupaten,
            'isi'            => $isi,
            'laporan'        => $laporan,
            'label'          => $this->rd->label_domain($laporan['domain']),
            'boleh_putuskan' => FALSE,
            'url_kembali'    => 'Admin_Rekam_Data',
        ]);
    }
}
