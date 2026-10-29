<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rekam Data - Capaian Perumahan (wizard W3).
 *
 * Kabupaten SELALU dari sesi (`$this->my_kabupaten_id`), tidak pernah dari
 * request: tidak ada dropdown wilayah di layar mana pun, dan tiap panggilan
 * model membawa scope itu sebagai gerbang, bukan sebagai penyaring tampilan.
 *
 * Wizard mengikuti idiom `/warga/pendataan`: satu endpoint merender seluruh
 * langkah, POST menyimpan lalu memindahkan langkah, dan langkahnya disimpan DI
 * BARIS (`rd_laporan.langkah_sekarang`) - bukan di sesi atau URL - supaya pengisian
 * bisa dilanjutkan setelah keluar-masuk.
 *
 * Acuan: docs/product/ROADMAP_WIZARD_REKAM_PERUMAHAN.md §3,
 *        rancangan new_flow/rekamdata/ (frame 003, 004, "Tambah Sumber Dana",
 *        "Input Setelah ada Data").
 */
class Rekam_Perumahan extends Admin_Kabkota_Controller {

    /** Urutan langkah wizard. `bnba` WAJIB sejak 5 Agt 2026 (butir C1); gerbangnya
     *  di `Rekam_data_model::kirim()`, bukan di sini. */
    private const LANGKAH = ['periode', 'program', 'isian', 'bnba', 'review'];

    private const LABEL_LANGKAH = [
        'periode' => 'Periode',
        'program' => 'Program',
        'isian'   => 'Isian Capaian',
        'bnba'    => 'BNBA',
        'review'  => 'Review & Kirim',
    ];

    protected $izinkan_bertindak = TRUE;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Rekam_data_model', 'rd');
    }

    // ------------------------------------------------------------------ baca

    /**
     * Layar Capaian: TABEL DULU, tombol Input Capaian di bawahnya (frame 001).
     *
     * Baca-saja, dan itu penting: memakai `laporan_periode()` yang TIDAK membuat
     * apa pun. Kalau layar baca ikut membuat draft, setiap admin yang cuma
     * menengok melahirkan periode baru di `rd_laporan`, dan riwayat pelaporan
     * penuh triwulan kosong yang tidak pernah diniatkan. Draft lahir di
     * `mulai()` saja.
     */
    public function index()
    {
        $tahun    = (int) ($this->input->get('tahun') ?: date('Y'));
        $triwulan = $this->triwulan_dari_get();

        $laporan = $this->rd->laporan_periode('perumahan', $this->my_kabupaten_id, $tahun, $triwulan);
        $matriks = $laporan
            ? $this->matriks($this->rd->isi_laporan((int) $laporan['id'], $this->my_kabupaten_id)['baris'])
            : [];

        $this->render_scoped_admin('admin/rekam/perumahan_capaian', [
            'title'         => 'Perumahan', // = label sidebar
            'scope_label'   => $this->nama_wilayah(),
            'tahun'         => $tahun,
            'triwulan'      => $triwulan,
            'laporan'       => $laporan,
            'matriks'       => $matriks,
            'kumulatif'     => $this->matriks($this->rd->kumulatif($tahun, $triwulan, $this->my_kabupaten_id)),
            'sumber_label'  => $this->label_sumber(),
            'program_label' => $this->label_program(),
        ]);
    }

    /**
     * Rekap resmi: hanya laporan `terkirim`, satu triwulan, dan angka kumulatif
     * s.d. triwulan itu berdampingan.
     *
     * Kumulatifnya dihitung `Rekam_data_model::kumulatif()`, bukan di sini -
     * penjumlahan antar triwulan hanya boleh terjadi di satu tempat.
     */
    public function rekap()
    {
        $tahun    = (int) ($this->input->get('tahun') ?: date('Y'));
        $triwulan = $this->triwulan_dari_get();
        $baris    = $this->rd->rekap('perumahan', $tahun, $triwulan, $this->my_kabupaten_id);

        $this->render_scoped_admin('admin/rekam/perumahan_capaian', [
            'title'         => 'Rekap Pelaporan Perumahan',
            'scope_label'   => $this->nama_wilayah(),
            'tahun'         => $tahun,
            'triwulan'      => $triwulan,
            'laporan'       => NULL,
            'mode_rekap'    => TRUE,
            'matriks'       => $this->matriks($baris),
            'kumulatif'     => $this->matriks($this->rd->kumulatif($tahun, $triwulan, $this->my_kabupaten_id)),
            'sumber_label'  => $this->label_sumber(),
            'program_label' => $this->label_program(),
        ]);
    }

    /**
     * Unduh rekap perumahan sebagai berkas Excel (butir C4, 5 Agt 2026).
     *
     * MENGULANG PERSIS panggilan `rekap()` - sengaja, bukan query baru. Query
     * kedua untuk data yang sama berarti dua tempat yang bisa menyimpang, dan
     * yang menyimpang di sini adalah ANGKA CAPAIAN yang dipakai melapor ke
     * provinsi.
     *
     * 🔴 Perhatikan posisi argumen cakupan: `rekap()` menaruhnya di posisi
     * KEEMPAT, `kumulatif()` di posisi KETIGA. Keduanya opsional dan
     * memasangnya di tempat yang salah TIDAK menghasilkan galat apa pun -
     * `WHERE l.kabupaten_id` cuma tidak pernah terpasang, dan berkasnya berisi
     * seluruh 35 kabupaten. Batas kewenangan yang jebolnya tidak terlihat mata.
     *
     * BNBA TIDAK IKUT (keputusan user): ia daftar penerima per nama + NIK, dan
     * begitu jadi berkas ia berpindah tangan tanpa jejak. Karena itu jalur ini
     * lewat `rekap()`, BUKAN `isi_laporan()` yang ikut memulangkan metadata BNBA.
     */
    /**
     * BUTIR 23 PUTARAN 2: rekap bisa dirinci per triwulan ATAU per tahun.
     *
     * Dinas menyebut "di-breakdown ke per TW dan per tahun" tanpa memastikan
     * artinya. Dua bacaan mungkin: penjumlahan setahun, atau keempat triwulan
     * berdampingan untuk dibandingkan. Bentuk di bawah menjawab KEDUANYA dalam
     * satu berkas, jadi tidak perlu menunggu jawaban untuk mulai berguna.
     *
     * Caranya: baris per TRIWULAN, bukan kolom yang melebar. Melebarkan kolom
     * berarti empat kali lipat lebar untuk tiap program dan berkasnya tidak
     * terbaca lagi; menambah baris membuat jumlah kolomnya tetap sama persis
     * dengan versi triwulanan, dan baris Total menutup tiap sumber dana.
     */
    public function export()
    {
        $tahun    = (int) ($this->input->get('tahun') ?: date('Y'));
        $setahun  = $this->input->get('periode') === 'tahun';
        $triwulan = $this->triwulan_dari_get();

        if ($setahun) { $this->export_tahunan($tahun); return; }

        $baris = $this->rd->rekap('perumahan', $tahun, $triwulan, $this->my_kabupaten_id);
        if ( ! $baris) {
            // Diperiksa SEBELUM satu header pun dikirim. Berkas nol baris
            // terbaca sebagai "capaiannya memang nol", bukan "belum ada laporan".
            $this->session->set_flashdata('error',
                'Belum ada laporan terkirim untuk periode ini - tidak ada yang bisa diunduh.');
            redirect('Rekam_Perumahan/rekap?tahun=' . $tahun . '&triwulan=' . $triwulan);
            return;
        }

        $matriks  = $this->matriks($baris);
        $program  = $this->label_program();
        $sumber   = $this->label_sumber();
        $nama_tw  = [1 => 'TW I', 2 => 'TW II', 3 => 'TW III', 4 => 'TW IV'];
        $periode  = ($nama_tw[$triwulan] ?? $triwulan) . ' ' . $tahun;

        $header = ['Sumber Dana'];
        foreach ($program as $plabel) {
            $header[] = $plabel . ' - Rencana (unit)';
            $header[] = $plabel . ' - Rencana (Rp)';
            $header[] = $plabel . ' - Realisasi (unit)';
            $header[] = $plabel . ' - Realisasi (Rp)';
        }

        $isi = [];
        foreach ($sumber as $skode => $slabel) {
            $r = [$slabel];
            foreach ($program as $pkode => $plabel) {
                $sel = $matriks[$skode][$pkode] ?? NULL;
                /* Sel KOSONG, bukan 0 - aturan "nol tabel nol" yang berlaku di
                   layar berlaku juga di berkas. Nol karangan tidak bisa
                   dibedakan dari nol yang benar-benar dilaporkan, dan di
                   spreadsheet ia ikut terjumlah. */
                $r[] = $sel ? (int) $sel['rencana_unit']       : NULL;
                $r[] = $sel ? (int) $sel['rencana_anggaran']   : NULL;
                $r[] = $sel ? (int) $sel['realisasi_unit']     : NULL;
                $r[] = $sel ? (int) $sel['realisasi_anggaran'] : NULL;
            }
            $isi[] = $r;
        }

        $this->catat_audit('rekap_diunduh',
            'Rekap perumahan ' . $periode . ' diunduh (' . $this->nama_wilayah() . ')',
            'rd_laporan', NULL, ['tahun' => $tahun, 'triwulan' => $triwulan]);

        $this->kirim_spreadsheet(
            'Rekap Perumahan ' . $periode . ' - ' . $this->nama_wilayah(),
            'Rekap ' . $periode, $header, $isi);
    }

    /**
     * Rekap setahun: empat triwulan sebagai baris, ditutup baris Total.
     *
     * "Sel kosong, bukan 0" tetap berlaku dan justru lebih penting di sini.
     * Triwulan yang belum dilaporkan HARUS kosong, bukan nol: nol terbaca
     * sebagai "sudah dilapor, capaiannya nihil", dan begitu ia masuk kolom
     * yang dijumlah, laporan yang belum masuk berubah jadi capaian nol yang
     * terlihat sah. Baris Total pun dibiarkan kosong kalau sumber dana itu
     * tidak punya satu pun triwulan terlapor sepanjang tahun.
     */
    private function export_tahunan($tahun)
    {
        $program = $this->label_program();
        $sumber  = $this->label_sumber();
        $nama_tw = [1 => 'TW I', 2 => 'TW II', 3 => 'TW III', 4 => 'TW IV'];

        $matriks = [];
        $ada     = FALSE;
        foreach ([1, 2, 3, 4] as $tw) {
            $baris = $this->rd->rekap('perumahan', $tahun, $tw, $this->my_kabupaten_id);
            $matriks[$tw] = $baris ? $this->matriks($baris) : [];
            if ($baris) { $ada = TRUE; }
        }

        if ( ! $ada) {
            $this->session->set_flashdata('error',
                'Belum ada laporan terkirim sepanjang ' . $tahun . ' - tidak ada yang bisa diunduh.');
            redirect('Rekam_Perumahan/rekap?tahun=' . $tahun);
            return;
        }

        $header = ['Triwulan', 'Sumber Dana'];
        foreach ($program as $plabel) {
            $header[] = $plabel . ' - Rencana (unit)';
            $header[] = $plabel . ' - Rencana (Rp)';
            $header[] = $plabel . ' - Realisasi (unit)';
            $header[] = $plabel . ' - Realisasi (Rp)';
        }

        $isi = [];
        foreach ($sumber as $skode => $slabel) {
            $total = [];
            foreach ([1, 2, 3, 4] as $tw) {
                $r = [$nama_tw[$tw], $slabel];
                foreach ($program as $pkode => $plabel) {
                    $sel = $matriks[$tw][$skode][$pkode] ?? NULL;
                    foreach (['rencana_unit', 'rencana_anggaran', 'realisasi_unit', 'realisasi_anggaran'] as $medan) {
                        $nilai = $sel ? (int) $sel[$medan] : NULL;
                        $r[] = $nilai;
                        if ($nilai !== NULL) {
                            $kunci = $pkode . '|' . $medan;
                            $total[$kunci] = ($total[$kunci] ?? 0) + $nilai;
                        }
                    }
                }
                $isi[] = $r;
            }

            $baris_total = ['Total ' . $tahun, $slabel];
            foreach ($program as $pkode => $plabel) {
                foreach (['rencana_unit', 'rencana_anggaran', 'realisasi_unit', 'realisasi_anggaran'] as $medan) {
                    $baris_total[] = $total[$pkode . '|' . $medan] ?? NULL;
                }
            }
            $isi[] = $baris_total;
        }

        $this->catat_audit('rekap_diunduh',
            'Rekap perumahan setahun ' . $tahun . ' diunduh (' . $this->nama_wilayah() . ')',
            'rd_laporan', NULL, ['tahun' => $tahun, 'periode' => 'tahun']);

        $this->kirim_spreadsheet(
            'Rekap Perumahan ' . $tahun . ' - ' . $this->nama_wilayah(),
            'Rekap ' . $tahun, $header, $isi);
    }

    public function riwayat()
    {
        $tahun = (int) ($this->input->get('tahun') ?: date('Y'));

        $this->render_scoped_admin('admin/rekam/riwayat', [
            'title'       => 'Riwayat', // = label sidebar
            'scope_label' => $this->nama_wilayah(),
            'domain'      => 'perumahan',
            'base_url'    => 'Rekam_Perumahan',
            'tahun'       => $tahun,
            'periode'     => $this->rd->riwayat('perumahan', $this->my_kabupaten_id, $tahun, $this->my_kabupaten_id),
        ]);
    }

    // ---------------------------------------------------------------- wizard

    /**
     * Satu pintu render untuk seluruh langkah. Langkah diambil dari baris
     * laporan, bukan dari URL - URL hanya boleh MEMILIH langkah yang sudah
     * boleh dibuka, tidak melompatinya.
     */
    public function input()
    {
        $laporan_id = (int) $this->input->get('laporan');

        // Belum memilih periode → langkah 1. Draft belum lahir di sini.
        if ($laporan_id < 1) {
            $tahun = (int) ($this->input->get('tahun') ?: date('Y'));

            // Status tiap triwulan dibawa ke layar pemilihan supaya orang tahu
            // SEBELUM memilih bahwa periode itu sudah dikirim dan terkunci.
            // Tanpa ini ia memilih TW berjalan, masuk, lalu baru ditolak - dan
            // penolakan yang datang setelah tiga klik terbaca seperti kerusakan.
            $status = [];
            foreach ($this->rd->riwayat('perumahan', $this->my_kabupaten_id, $tahun,
                $this->my_kabupaten_id) as $row) {
                $status[(int) $row['triwulan']] = $row['status'];
            }

            $this->render_scoped_admin('admin/rekam/perumahan_wizard', [
                'title'       => 'Input Capaian Perumahan',
                'scope_label' => $this->nama_wilayah(),
                'langkah'     => 'periode',
                'label_langkah' => self::LABEL_LANGKAH,
                'urutan'      => self::LANGKAH,
                'tahun'       => $tahun,
                'triwulan'    => $this->triwulan_dari_get(),
                'status_triwulan' => $status,
                'laporan'     => NULL,
                'terkunci'    => FALSE,
                // Langkah 1 tidak memakai keduanya, tetapi view MEMBUATNYA jadi
                // closure `use (...)` di awal berkas - dan `use` dievaluasi saat
                // closure dibuat, bukan saat dipanggil. Tanpa ini PHP melempar
                // dua Warning "Undefined variable" di setiap pembukaan wizard,
                // padahal tidak ada yang salah dengan alurnya.
                'sumber_label' => $this->label_sumber(),
                'program_label' => $this->label_program(),
                'sumber_berketerangan' => ['apbn_kl_lain' => 'Kementerian sumber',
                    'csr' => 'Nama perusahaan', 'dana_lainnya' => 'Sumber penyalur'],
                'program_dipilih' => [],
                'program_aktif' => '',
                'baris' => [],
                'program_kosong' => [],
                'bnba' => NULL,
                'bnba_sumber' => [],
                'sumber_tanpa_bnba' => [],
            ]);
            return;
        }

        $isi = $this->rd->isi_laporan($laporan_id, $this->my_kabupaten_id);
        if ( ! $isi || $isi['laporan']['domain'] !== 'perumahan') {
            show_404();
            return;
        }

        $langkah = (string) ($this->input->get('langkah') ?: $isi['laporan']['langkah_sekarang']);
        if ( ! in_array($langkah, self::LANGKAH, TRUE) || $langkah === 'periode') {
            $langkah = 'program';
        }

        // Program yang sedang diisi pada langkah `isian`. Default: yang pertama
        // dicentang, supaya membuka langkah itu tidak pernah menampilkan layar
        // kosong tanpa sebab.
        $dipilih = array_keys(array_filter($isi['program']));
        $program = (string) $this->input->get('program');
        if ($langkah === 'isian' && ! in_array($program, $dipilih, TRUE)) {
            $program = $dipilih[0] ?? '';
        }

        $this->render_scoped_admin('admin/rekam/perumahan_wizard', [
            'title'         => 'Input Capaian Perumahan',
            'scope_label'   => $this->nama_wilayah(),
            'langkah'       => $langkah,
            'label_langkah' => self::LABEL_LANGKAH,
            'urutan'        => self::LANGKAH,
            'laporan'       => $isi['laporan'],
            'tahun'         => (int) $isi['laporan']['tahun'],
            'triwulan'      => (int) $isi['laporan']['triwulan'],
            'program_dipilih' => $dipilih,
            'program_aktif' => $program,
            'baris'         => $this->baris_per_program($isi['baris']),
            'program_kosong' => $isi['program_kosong'],
            'bnba'          => $isi['bnba'],
            'bnba_sumber'   => $isi['bnba_sumber'],
            'sumber_tanpa_bnba' => $isi['sumber_tanpa_bnba'],
            'terkunci'      => $isi['laporan']['status'] === 'terkirim',
            'sumber_label'  => $this->label_sumber(),
            'program_label' => $this->label_program(),
            'sumber_berketerangan' => ['apbn_kl_lain' => 'Kementerian sumber',
                'csr' => 'Nama perusahaan', 'dana_lainnya' => 'Sumber penyalur'],
        ]);
    }

    /** L1 → buat/temukan draft periode, lalu lanjut ke pemilihan program. */
    public function mulai()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        $tahun    = (int) $this->input->post('tahun');
        $triwulan = (int) $this->input->post('triwulan');

        $hasil = $this->rd->ambil_atau_buat_draft('perumahan', $this->my_kabupaten_id, $tahun, $triwulan);
        if (empty($hasil['success'])) {
            $this->session->set_flashdata('error', $hasil['message']);
            redirect('Rekam_Perumahan/input');
            return;
        }
        $id = (int) $hasil['laporan']['id'];

        // Periode yang sudah dikirim tidak bisa diubah, jadi mengantar orang ke
        // layar pemilihan program hanya menyodorkan pintu yang terkunci. Antar
        // langsung ke isiannya - yang memang boleh dibaca.
        if ($hasil['laporan']['status'] === 'terkirim') {
            redirect('Rekam_Perumahan/input?laporan=' . $id . '&langkah=isian');
            return;
        }

        $this->rd->simpan_langkah($id, 'perumahan', 'program', $this->my_kabupaten_id);
        redirect('Rekam_Perumahan/input?laporan=' . $id . '&langkah=program');
    }

    /** L2 → simpan enam centang program sekaligus. */
    public function simpan_program()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        $laporan_id = (int) $this->input->post('laporan_id');
        $dipilih    = (array) ($this->input->post('program') ?: []);

        $hasil = $this->rd->simpan_gerbang_program($laporan_id, $dipilih, $this->my_kabupaten_id);
        $this->buang_berkas_bnba($laporan_id, $hasil['berkas_lama'] ?? []);
        if (empty($hasil['success'])) {
            $this->session->set_flashdata('error', $hasil['message']);
            redirect('Rekam_Perumahan/input?laporan=' . $laporan_id . '&langkah=program');
            return;
        }
        if ( ! $dipilih) {
            $this->session->set_flashdata('error', 'Pilih minimal satu program yang akan dilaporkan.');
            redirect('Rekam_Perumahan/input?laporan=' . $laporan_id . '&langkah=program');
            return;
        }

        $pesan = 'Pilihan program tersimpan.';
        if ( ! empty($hasil['dicabut'])) {
            $pesan .= ' ' . (int) $hasil['dicabut'] . ' program dicabut beserta angkanya.';
        }
        $this->session->set_flashdata('success', $pesan);
        $this->rd->simpan_langkah($laporan_id, 'perumahan', 'isian', $this->my_kabupaten_id);
        redirect('Rekam_Perumahan/input?laporan=' . $laporan_id . '&langkah=isian');
    }

    /** L3 → tambah atau ubah satu sumber dana di dalam satu program. */
    public function simpan_sumber()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        $laporan_id = (int) $this->input->post('laporan_id');
        $program    = (string) $this->input->post('program', TRUE);

        // Nilai mentah SENGAJA tidak dibersihkan di sini - model yang menolak
        // negatif, bukan-angka, dan anggaran tanpa unit, supaya aturannya satu
        // tempat dan jalur lain ikut terlindungi.
        $sumber = (string) $this->input->post('sumber_dana', TRUE);
        $hasil = $this->rd->simpan_sumber($laporan_id, $program, $sumber, [
                'rencana_unit'       => $this->input->post('rencana_unit'),
                'rencana_anggaran'   => $this->input->post('rencana_anggaran'),
                'realisasi_unit'     => $this->input->post('realisasi_unit'),
                'realisasi_anggaran' => $this->input->post('realisasi_anggaran'),
                'keterangan'         => $this->input->post('keterangan', TRUE),
            ], $this->my_kabupaten_id);

        // BNBA (Excel) ikut di form yang sama. Angka disimpan lebih dulu; berkas yang ditolak
        // tidak membatalkan angkanya, dan pesannya bilang begitu apa adanya.
        $pesan = 'Sumber dana tersimpan.';
        if ( ! empty($hasil['success']) && ! empty($_FILES['bnba']['name'])) {
            $galat = $this->simpan_berkas_bnba_sumber($laporan_id, $program, $sumber);
            if ($galat === NULL) {
                $pesan = 'Sumber dana dan BNBA tersimpan.';
            } else {
                $hasil = ['success' => FALSE, 'message' => 'Angka sumber dana tersimpan, tetapi BNBA ditolak: ' . $galat];
            }
        }
        $this->pulang_isian($hasil, $laporan_id, $program, $pesan);
    }

    /** Unduh BNBA satu sumber dana milik wilayah sendiri. Scope diperiksa SEBELUM menyentuh disk. */
    public function unduh_bnba_sumber($baris_id = 0)
    {
        $berkas = $this->rd->bnba_sumber((int) $baris_id, $this->my_kabupaten_id);
        if ( ! $berkas) {
            show_404();
            return;
        }
        $this->serve_private_file('rekam_bnba', (int) $berkas['laporan_id'], $berkas['path_privat'], $berkas['mime_type']);
    }

    /** Templat kosong BNBA: hanya baris judul, supaya kolomnya persis format dinas. */
    /**
     * Templat BNBA sebagai XLSX ASLI (bukan SpreadsheetML ber-ekstensi .xls seperti pengirim rekap:
     * berkas itu ditolak saat diunggah balik, temuan dinas 28 Okt 2026). Isinya:
     * - lembar BNBA: NIK dan NO KK berformat Teks (tidak jadi 3,37E+15), KDDESA berumus VLOOKUP
     *   gabungan KABUPATEN_KECAMATAN_DESA ke lembar KODE WILAYAH (pola rumus dari dinas);
     * - lembar KODE WILAYAH: desa kabupaten pengunduh saja, dari tabel `desa` (kode Kemendagri).
     * Rumusnya TIDAK dihitung di sini; Excel menghitungnya saat berkas dibuka.
     */
    public function templat_bnba()
    {
        $this->catat_audit('rekap_diunduh', 'Templat BNBA diunduh (' . $this->nama_wilayah() . ')', 'rd_laporan');

        $baris_isi = 1000;
        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $bnba = $ss->getActiveSheet()->setTitle('BNBA');
        $bnba->fromArray(['NIK', 'NO KK', 'NAMA', 'ALAMAT', 'KABUPATEN', 'KECAMATAN', 'DESA/KELURAHAN',
            'KDDESA (KOLOM TERISI OTOMATIS)', 'JUMLAH MATERIAL', 'JUMLAH PADAT KARYA']);
        $bnba->getStyle('A1:J1')->getFont()->setBold(TRUE);
        $bnba->getStyle('A2:B' . ($baris_isi + 1))->getNumberFormat()->setFormatCode('@');
        $bnba->getStyle('I2:J' . ($baris_isi + 1))->getNumberFormat()->setFormatCode('#,##0');
        for ($i = 2; $i <= $baris_isi + 1; $i++) {
            $bnba->setCellValue("H$i", "=IF(G$i=\"\",\"\",IFERROR(VLOOKUP(UPPER(TRIM(E$i))&\"_\"&UPPER(TRIM(F$i))&\"_\"&UPPER(TRIM(G$i)),'KODE WILAYAH'!\$D:\$E,2,FALSE),\"\"))");
        }
        foreach (['A' => 20, 'B' => 20, 'C' => 28, 'D' => 34, 'E' => 16, 'F' => 18, 'G' => 20, 'H' => 18, 'I' => 18, 'J' => 20] as $k => $w) {
            $bnba->getColumnDimension($k)->setWidth($w);
        }
        $bnba->freezePane('A2');

        $kw = $ss->createSheet()->setTitle('KODE WILAYAH');
        $kw->fromArray(['kabupaten', 'kecamatan', 'deskel', 'concatenate', 'kode_dagri']);
        $r = 2;
        foreach ($this->desa_wilayah() as $d) {
            $kw->fromArray([$d['kab'], $d['kec'], $d['desa'], $d['kab'] . '_' . $d['kec'] . '_' . $d['desa']], NULL, "A$r");
            $kw->setCellValueExplicit("E$r", $d['kode'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $r++;
        }
        foreach (['A' => 18, 'B' => 22, 'C' => 24, 'D' => 50, 'E' => 14] as $k => $w) { $kw->getColumnDimension($k)->setWidth($w); }
        $ss->setActiveSheetIndex(0);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $writer->setPreCalculateFormulas(FALSE);
        $aman = preg_replace('/[^A-Za-z0-9 _.-]/', '', 'Templat BNBA - ' . $this->nama_wilayah());
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $aman . '.xlsx"');
        header('Cache-Control: private, no-store');
        $writer->save('php://output');
        exit;
    }

    public function hapus_sumber()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        $laporan_id = (int) $this->input->post('laporan_id');
        $program    = (string) $this->input->post('program', TRUE);

        $hasil = $this->rd->hapus_sumber($laporan_id, $program,
            (string) $this->input->post('sumber_dana', TRUE), $this->my_kabupaten_id);
        $this->buang_berkas_bnba($laporan_id, $hasil['berkas_lama'] ?? []);

        $this->pulang_isian($hasil, $laporan_id, $program, 'Sumber dana dihapus.');
    }

    /** Pindah langkah tanpa menyimpan apa pun - tombol Lanjut/Kembali. */
    public function langkah()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        $laporan_id = (int) $this->input->post('laporan_id');
        $tujuan     = (string) $this->input->post('langkah', TRUE);
        if ( ! in_array($tujuan, self::LANGKAH, TRUE)) {
            show_404();
            return;
        }
        $this->rd->simpan_langkah($laporan_id, 'perumahan', $tujuan, $this->my_kabupaten_id);
        redirect('Rekam_Perumahan/input?laporan=' . $laporan_id . '&langkah=' . $tujuan);
    }

    // ------------------------------------------------------------------ BNBA

    /**
     * Unggah BNBA. Menekan tombol tanpa memilih berkas BUKAN error dan BUKAN
     * 404 - pengguna dikembalikan ke layar dengan pesan yang bisa dibaca.
     * Bug ini pernah nyata di modul Warga (AGENTS.md §0b).
     */
    public function unggah_bnba()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        $laporan_id = (int) $this->input->post('laporan_id');
        $laporan = $this->rd->laporan($laporan_id, $this->my_kabupaten_id);
        if ( ! $laporan) {
            show_404();
            return;
        }

        if (empty($_FILES['bnba']['name'])) {
            $this->session->set_flashdata('error', 'Pilih berkas PDF/JPG/PNG terlebih dahulu.');
            $this->kembali_ke_bnba($laporan_id);
            return;
        }

        $galat = NULL;
        $tersimpan = $this->store_private_upload('bnba', 'rekam_bnba', $laporan_id, $galat);
        if ($tersimpan === FALSE) {
            $this->session->set_flashdata('error', $galat ?: 'Berkas gagal diunggah.');
            $this->kembali_ke_bnba($laporan_id);
            return;
        }

        // MIME dibaca ulang dari berkas yang SUDAH mendarat, bukan dari
        // $_FILES['type'] yang datang dari klien dan bisa berisi apa saja.
        // Nilai ini nanti dipakai apa adanya sebagai header Content-Type saat
        // penyajian - mempercayai klien di sini berarti membiarkan klien
        // menentukan header (dan curl saja sudah mengirim octet-stream).
        $path_tersimpan = $this->private_upload_dir('rekam_bnba', $laporan_id) . $tersimpan;
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path_tersimpan) ?: 'application/octet-stream';

        $hasil = $this->rd->simpan_bnba($laporan_id, [
            'nama_asli'    => $_FILES['bnba']['name'],
            'path_privat' => $tersimpan,
            'mime_type'    => $mime,
            'ukuran'       => filesize($path_tersimpan),
            'uploaded_by'  => $this->get_user_id(),
        ], $this->my_kabupaten_id);

        if (empty($hasil['success'])) {
            // Ledger menolak → berkas yang baru mendarat tidak boleh jadi yatim.
            @unlink($this->private_upload_dir('rekam_bnba', $laporan_id) . $tersimpan);
            $this->session->set_flashdata('error', $hasil['message']);
        } else {
            if ( ! empty($hasil['path_lama'])) {
                @unlink($this->private_upload_dir('rekam_bnba', $laporan_id) . $hasil['path_lama']);
            }
            $this->session->set_flashdata('success', 'Berkas BNBA tersimpan.');
        }
        $this->kembali_ke_bnba($laporan_id);
    }

    /** Unduh BNBA milik wilayah sendiri. Scope diperiksa SEBELUM menyentuh disk. */
    public function unduh_bnba($laporan_id = 0)
    {
        $berkas = $this->rd->bnba((int) $laporan_id, $this->my_kabupaten_id);
        if ( ! $berkas) {
            show_404();
            return;
        }
        $this->serve_private_file('rekam_bnba', (int) $laporan_id,
            $berkas['path_privat'], $berkas['mime_type'] ?: 'application/octet-stream');
    }

    public function kirim()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        $laporan_id = (int) $this->input->post('laporan_id');
        $hasil = $this->rd->kirim($laporan_id, $this->get_user_id(), $this->my_kabupaten_id);

        if (empty($hasil['success'])) {
            $this->session->set_flashdata('error', $hasil['message']);
            redirect('Rekam_Perumahan/input?laporan=' . $laporan_id . '&langkah=review');
            return;
        }
        $laporan = $this->rd->laporan($laporan_id, $this->my_kabupaten_id);
        $this->notify_admin_push([
            ['role' => 'admin'],
            ['role' => 'admin_bidang', 'bidang_kode' => 'perumahan'],
        ], 'Rekam Data Perumahan baru',
            'Ada laporan kabupaten/kota yang menunggu peninjauan.',
            'Rekam_Tinjauan?domain=perumahan', 'rekam-perumahan-' . $laporan_id);
        $this->session->set_flashdata('success', 'Laporan terkirim dan terkunci.');
        redirect('Rekam_Perumahan?tahun=' . (int) $laporan['tahun'] . '&triwulan=' . (int) $laporan['triwulan']);
    }

    // ---------------------------------------------------------------- internal

    /**
     * Terima Excel BNBA satu sumber dana: simpan privat (jenis, pindai virus, kuota), baca dan
     * validasi isinya, lalu catat. Berkas yang tidak lolos di tahap mana pun dibuang dari disk.
     * @return string|null pesan galat, NULL bila berhasil
     */
    private function simpan_berkas_bnba_sumber($laporan_id, $program, $sumber)
    {
        $galat = NULL;
        $xls_mime = ['application/vnd.ms-excel', 'application/CDFV2', 'application/x-ole-storage'];
        $tersimpan = $this->store_private_upload('bnba', 'rekam_bnba', $laporan_id, $galat, 5242880, [
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
            'xls'  => $xls_mime,
        ]);
        if ($tersimpan === FALSE) {
            return $galat ?: 'Berkas gagal diunggah.';
        }
        $path = $this->private_upload_dir('rekam_bnba', $laporan_id) . $tersimpan;

        $this->load->library('rekam_bnba_import');
        $kode_desa = array_fill_keys(array_column($this->desa_wilayah(), 'kode'), TRUE);
        $baca = $this->rekam_bnba_import->baca($path, $this->nama_wilayah(), $kode_desa);
        if (empty($baca['success'])) {
            @unlink($path);
            return $baca['message'];
        }
        // Revisi dinas 29 Okt 2026: isi BNBA harus sama dengan realisasi sumber dananya. Angka di form
        // sudah tersimpan lebih dulu (simpan_sumber), jadi yang dibandingkan adalah angka terbaru.
        $baris = $this->db->get_where('rd_perumahan_baris', ['laporan_id' => (int) $laporan_id,
            'program' => $program, 'sumber_dana' => $sumber])->row_array();
        $beda = $baris ? Rekam_data_model::bnba_tak_cocok($baris, $baca['jumlah_penerima'],
            $baca['total_material'], $baca['total_padat_karya']) : NULL;
        if ($beda !== NULL) {
            @unlink($path);
            return $beda . ' Samakan angka realisasi di form ini dengan isi BNBA, atau perbaiki berkasnya.';
        }

        $hasil = $this->rd->simpan_bnba_sumber($laporan_id, $program, $sumber, [
            'nama_asli'         => $_FILES['bnba']['name'],
            'path_privat'       => $tersimpan,
            'mime_type'         => (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream',
            'ukuran'            => filesize($path),
            'uploaded_by'       => $this->get_user_id(),
            'jumlah_penerima'   => $baca['jumlah_penerima'],
            'total_material'    => $baca['total_material'],
            'total_padat_karya' => $baca['total_padat_karya'],
        ], $this->my_kabupaten_id);
        if (empty($hasil['success'])) {
            @unlink($path);
            return $hasil['message'];
        }
        $this->buang_berkas_bnba($laporan_id, [$hasil['path_lama'] ?? NULL]);
        return NULL;
    }

    /** Buang berkas BNBA-sumber yang barisnya sudah hilang/diganti. Nama kosong dilewati. */
    private function buang_berkas_bnba($laporan_id, array $nama)
    {
        foreach (array_filter($nama) as $n) {
            @unlink($this->private_upload_dir('rekam_bnba', $laporan_id) . basename((string) $n));
        }
    }

    private function kembali_ke_bnba($laporan_id)
    {
        redirect('Rekam_Perumahan/input?laporan=' . (int) $laporan_id . '&langkah=bnba');
    }

    private function pulang_isian($hasil, $laporan_id, $program, $pesan_sukses)
    {
        if (empty($hasil['success'])) {
            $this->session->set_flashdata('error', $hasil['message'] ?? 'Perubahan tidak tersimpan.');
        } else {
            $this->session->set_flashdata('success', $pesan_sukses);
        }
        redirect('Rekam_Perumahan/input?laporan=' . (int) $laporan_id
            . '&langkah=isian&program=' . rawurlencode((string) $program));
    }

    /** 1-4, jatuh ke triwulan berjalan bila tidak diberikan atau di luar rentang. */
    private function triwulan_dari_get()
    {
        $tw = (int) $this->input->get('triwulan');
        return ($tw >= 1 && $tw <= 4) ? $tw : (int) ceil((int) date('n') / 3);
    }

    /**
     * Desa kabupaten pelapor dalam bentuk lembar KODE WILAYAH dinas: kabupaten HURUF BESAR tanpa
     * awalan "Kabupaten" (kota tetap "KOTA X"), kecamatan dan desa huruf besar, kode Kemendagri.
     * @return array [['kab','kec','desa','kode'], ...]
     */
    private function desa_wilayah()
    {
        $kab = strtoupper(preg_replace('/^Kabupaten\s+/i', '', $this->nama_wilayah()));
        $out = [];
        foreach ($this->db->select('d.id AS kode, d.nama AS desa, k.nama AS kec')
            ->from('desa d')->join('kecamatan k', 'k.id = d.kecamatan_id')
            ->where('k.kabupaten_id', (int) $this->my_kabupaten_id)
            ->order_by('k.nama')->order_by('d.nama')->get()->result_array() as $d) {
            $out[] = ['kab' => $kab, 'kec' => strtoupper($d['kec']), 'desa' => strtoupper($d['desa']), 'kode' => (string) $d['kode']];
        }
        return $out;
    }

    private function nama_wilayah()
    {
        return $this->db->where('id', $this->my_kabupaten_id)
            ->get('kabupaten')->row('nama') ?: 'Wilayah Saya';
    }

    /** [program][sumber_dana] => baris. Dipakai layar isian. */
    private function baris_per_program(array $baris)
    {
        $out = [];
        foreach ($baris as $row) {
            $out[$row['program']][$row['sumber_dana']] = $row;
        }
        return $out;
    }

    /** [sumber_dana][program] => baris. Bentuk matriks tabel Capaian. */
    private function matriks(array $baris)
    {
        $out = [];
        foreach ($baris as $row) {
            $out[$row['sumber_dana']][$row['program']] = $row;
        }
        return $out;
    }

    private function label_sumber()
    {
        return Rekam_data_model::LABEL_SUMBER_PERUMAHAN;
    }

    private function label_program()
    {
        return [
            'pk_rtlh'     => 'PK RTLH',
            'pb_rtlh'     => 'PB RTLH',
            'pb_backlog'  => 'PB Backlog',
            'pk_bencana'  => 'PK Bencana',
            'pb_bencana'  => 'PB Bencana',
            'pb_relokasi' => 'PB Relokasi',
        ];
    }
}
