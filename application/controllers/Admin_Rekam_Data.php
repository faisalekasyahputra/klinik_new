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
 * DULU read-only (nol endpoint tulis, supaya tidak ada jalur kedua ke keputusan
 * Admin Bidang tanpa jejak). DIBALIK 27 Okt 2026 atas permintaan dinas: super
 * admin "bisa semua yang bisa dilakukan user lainnya". Yang kini ada, semuanya
 * POST + CSRF + tercatat di jejak audit: terima, minta_perbaikan (memakai
 * model & pembatas laju yang SAMA dengan `Rekam_Tinjauan`, jadi tidak ada
 * logika keputusan ganda), dan bertindak/selesai (mengisi laporan atas nama satu
 * kabupaten lewat sesi, lihat `Admin_Kabkota_Controller::$izinkan_bertindak`).
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
        // Kembali ke papan = berhenti mewakili kabupaten, supaya tidak ada
        // keadaan "bertindak" yang tertinggal diam-diam di sesi.
        $this->session->unset_userdata('bertindak_kabupaten_id');

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
            // Satu hitungan per keadaan, supaya rinciannya selalu berjumlah sama dengan total
            // (dulu draft tidak terhitung di mana pun: "0 diterima, 34 belum" dari 35).
            $ringkas[$domain] = ['total' => 0, 'masuk' => 0, 'diterima' => 0, 'menunggu' => 0, 'perbaikan' => 0, 'draft' => 0, 'belum' => 0];
            foreach ($this->rd->pantau($domain, $tahun, $triwulan) as $r) {
                [$kunci, $label] = $this->rd->keadaan_laporan($r);
                $baris[$r['kabupaten_id']]['kabupaten'] = $r['kabupaten'];
                $baris[$r['kabupaten_id']][$domain] = $r + ['keadaan' => $kunci, 'keadaan_label' => $label];

                $ringkas[$domain]['total']++;
                $ringkas[$domain][$kunci]++;
                if (in_array($kunci, ['menunggu', 'diterima', 'perbaikan'], TRUE)) { $ringkas[$domain]['masuk']++; }
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
    /**
     * Unduh papan Pantau Rekam Data sebagai Excel - satu triwulan.
     *
     * Permintaan user 17 Agt 2026 ("bisa diexport ke excel atau pdf di
     * breakdown ke per tw dan per tahun"). Layar ini sebelumnya TIDAK PUNYA
     * unduhan sama sekali - berbeda dari Rekam_Perumahan/Rekam_Kawasan yang
     * sudah dapat butir 23 putaran 2 lebih dulu.
     *
     * MENGULANG PERSIS query di index() - satu baris per kabupaten, kedua
     * domain disandingkan (lihat alasannya di sana). Query kedua untuk data
     * yang sama berarti dua tempat yang bisa menyimpang.
     */
    public function export()
    {
        $tahun    = (int) ($this->input->get('tahun') ?: date('Y'));
        $setahun  = $this->input->get('periode') === 'tahun';
        $triwulan = (int) $this->input->get('triwulan');
        if ($triwulan < 1 || $triwulan > 4) {
            $triwulan = (int) ceil((int) date('n') / 3);
        }

        if ($setahun) { $this->export_tahunan($tahun); return; }

        $baris = [];
        foreach (array_keys(self::DOMAIN) as $domain) {
            foreach ($this->rd->pantau($domain, $tahun, $triwulan) as $r) {
                [, $label] = $this->rd->keadaan_laporan($r);
                $baris[$r['kabupaten_id']]['kabupaten'] = $r['kabupaten'];
                $baris[$r['kabupaten_id']][$domain] = $label;
            }
        }

        if ( ! $baris) {
            // Praktis tidak pernah kosong (35 kabupaten selalu punya baris,
            // lihat komentar pantau() di model) - dijaga untuk kasus seed
            // wilayah belum jalan, sama seperti pesan di tabelnya.
            $this->session->set_flashdata('error',
                'Tabel kabupaten kosong - tidak ada yang bisa diunduh.');
            redirect('Admin_Rekam_Data?tahun=' . $tahun . '&triwulan=' . $triwulan);
            return;
        }

        $nama_tw = [1 => 'TW I', 2 => 'TW II', 3 => 'TW III', 4 => 'TW IV'];
        $periode = ($nama_tw[$triwulan] ?? $triwulan) . ' ' . $tahun;

        $header = ['Kabupaten/Kota'];
        foreach (self::DOMAIN as $label) { $header[] = $label; }

        $isi = [];
        foreach ($baris as $b) {
            $r = [$b['kabupaten']];
            foreach (array_keys(self::DOMAIN) as $domain) {
                $r[] = $b[$domain] ?? 'Belum ada laporan';
            }
            $isi[] = $r;
        }

        $this->catat_audit('rekap_diunduh',
            'Pantau rekam data ' . $periode . ' diunduh (superadmin)',
            'rd_laporan', NULL, ['tahun' => $tahun, 'triwulan' => $triwulan]);

        $this->kirim_spreadsheet(
            'Pantau Rekam Data ' . $periode, 'Pantau ' . $periode, $header, $isi);
    }

    /**
     * Gabungan setahun: keempat triwulan sebagai kolom berdampingan per
     * domain, satu baris per kabupaten - bukan "baris per triwulan" seperti
     * Rekam_Perumahan::export_tahunan(). Bedanya sengaja: di sana tiap sel
     * adalah MATRIKS sumber×program yang sudah lebar, jadi triwulan ditaruh
     * sebagai baris. Di sini tiap sel cuma satu status per kabupaten, jadi
     * melebarkan kolom (bukan baris) tetap terbaca dan malah lebih ringkas -
     * satu baris per kabupaten, bisa dipindai vertikal.
     */
    private function export_tahunan($tahun)
    {
        $nama_tw = [1 => 'TW I', 2 => 'TW II', 3 => 'TW III', 4 => 'TW IV'];

        $data = [];
        $ada  = FALSE;
        foreach ([1, 2, 3, 4] as $tw) {
            foreach (array_keys(self::DOMAIN) as $domain) {
                foreach ($this->rd->pantau($domain, $tahun, $tw) as $r) {
                    [$kunci, $label] = $this->rd->keadaan_laporan($r);
                    $data[$r['kabupaten_id']]['kabupaten'] = $r['kabupaten'];
                    $data[$r['kabupaten_id']][$domain][$tw] = $label;
                    if ($kunci !== 'belum') { $ada = TRUE; }
                }
            }
        }

        if ( ! $ada) {
            $this->session->set_flashdata('error',
                'Belum ada laporan terkirim sepanjang ' . $tahun . ' - tidak ada yang bisa diunduh.');
            redirect('Admin_Rekam_Data?tahun=' . $tahun);
            return;
        }

        $header = ['Kabupaten/Kota'];
        foreach (self::DOMAIN as $label) {
            foreach ($nama_tw as $tw_label) { $header[] = $label . ' - ' . $tw_label; }
        }

        $isi = [];
        foreach ($data as $b) {
            $r = [$b['kabupaten']];
            foreach (array_keys(self::DOMAIN) as $domain) {
                foreach ([1, 2, 3, 4] as $tw) {
                    $r[] = $b[$domain][$tw] ?? 'Belum ada laporan';
                }
            }
            $isi[] = $r;
        }

        $this->catat_audit('rekap_diunduh',
            'Pantau rekam data setahun ' . $tahun . ' diunduh (superadmin)',
            'rd_laporan', NULL, ['tahun' => $tahun, 'periode' => 'tahun']);

        $this->kirim_spreadsheet(
            'Pantau Rekam Data ' . $tahun, 'Pantau ' . $tahun, $header, $isi);
    }

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
            // Sejak 27 Okt 2026 superadmin ikut memutuskan (permintaan dinas:
            // "bisa semua yang bisa dilakukan user lainnya"). Keadaan laporan
            // tetap digerbang view dan model, jadi laporan yang sudah diterima
            // atau sedang diperbaiki tidak bisa diputus ulang dari sini.
            'boleh_putuskan' => TRUE,
            'url_aksi'       => 'Admin_Rekam_Data',
            'url_kembali'    => 'Admin_Rekam_Data',
        ]);
    }

    public function terima()
    {
        $laporan_id = (int) $this->input->post('laporan_id');
        if ( ! $this->gerbang_tulis($laporan_id)) { return; }

        $hasil = $this->rd->terima($laporan_id, $this->get_user_id());
        if ( ! empty($hasil['success'])) {
            $this->catat_audit('rekam_diterima', 'Laporan rekam data diterima (superadmin)',
                'rd_laporan', $laporan_id);
        }
        $this->pulang($hasil, 'Laporan diterima.', $laporan_id);
    }

    public function minta_perbaikan()
    {
        $laporan_id = (int) $this->input->post('laporan_id');
        if ( ! $this->gerbang_tulis($laporan_id)) { return; }

        $hasil = $this->rd->minta_perbaikan($laporan_id, $this->get_user_id(),
            $this->input->post('catatan_admin', TRUE));
        if ( ! empty($hasil['success'])) {
            $laporan = $this->db->select('domain, kabupaten_id, tahun, triwulan')
                ->get_where('rd_laporan', ['id' => $laporan_id])->row_array();
            $layar = $laporan['domain'] === 'perumahan' ? 'Rekam_Perumahan' : 'Rekam_Kawasan';
            $this->notify_admin_push([['role' => 'admin_kabkota', 'kabupaten_id' => (int) $laporan['kabupaten_id']]],
                'Rekam Data perlu diperbaiki',
                'Laporan rekam data dikembalikan provinsi untuk diperbaiki.',
                $layar . '?tahun=' . (int) $laporan['tahun'] . '&triwulan=' . (int) $laporan['triwulan'],
                'rekam-perbaikan-' . $laporan_id);
            $this->catat_audit('rekam_diminta_perbaikan', 'Laporan rekam data dikembalikan (superadmin)',
                'rd_laporan', $laporan_id);
        }
        $this->pulang($hasil, 'Laporan dikembalikan untuk diperbaiki.', $laporan_id);
    }

    /**
     * Mulai mengisi laporan atas nama satu kabupaten. Kabupatennya divalidasi
     * ke tabel `kabupaten` lalu disimpan di SESI; `Admin_Kabkota_Controller`
     * hanya mempercayai nilai sesi itu, tidak pernah parameter request.
     */
    public function bertindak()
    {
        $kab_id = (int) $this->input->post('kabupaten_id');
        $domain = $this->input->post('domain');
        $tahun  = (int) $this->input->post('tahun');
        $tw     = (int) $this->input->post('triwulan');
        if ($this->input->method(TRUE) !== 'POST' || ! isset(self::DOMAIN[$domain])
            || $tw < 1 || $tw > 4
            || ! $this->db->where('id', $kab_id)->count_all_results('kabupaten')) {
            show_404();
            return;
        }

        $this->session->set_userdata('bertindak_kabupaten_id', $kab_id);
        $this->catat_audit('rekam_bertindak', 'Superadmin mengisi rekam data atas nama kabupaten',
            'kabupaten', $kab_id, ['domain' => $domain, 'tahun' => $tahun, 'triwulan' => $tw]);
        redirect(($domain === 'perumahan' ? 'Rekam_Perumahan' : 'Rekam_Kawasan')
            . '?tahun=' . $tahun . '&triwulan=' . $tw);
    }

    public function selesai()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); return; }
        $this->session->unset_userdata('bertindak_kabupaten_id');
        redirect('Admin_Rekam_Data');
    }

    // ---------------------------------------------------------------- internal

    /** POST saja + pembatas laju yang SAMA dengan Rekam_Tinjauan (§17 poin 15). */
    private function gerbang_tulis($laporan_id)
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

    private function pulang($hasil, $pesan_sukses, $laporan_id)
    {
        if (empty($hasil['success'])) {
            $this->session->set_flashdata('error', $hasil['message'] ?? 'Keputusan tidak tersimpan.');
        } else {
            $this->session->set_flashdata('success', $pesan_sukses);
        }
        redirect('Admin_Rekam_Data/detail/' . $laporan_id);
    }
}
