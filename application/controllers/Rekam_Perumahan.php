<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rekam Data — Capaian Perumahan (wizard W3).
 *
 * Kabupaten SELALU dari sesi (`$this->my_kabupaten_id`), tidak pernah dari
 * request: tidak ada dropdown wilayah di layar mana pun, dan tiap panggilan
 * model membawa scope itu sebagai gerbang, bukan sebagai penyaring tampilan.
 *
 * Wizard mengikuti idiom `/warga/pendataan`: satu endpoint merender seluruh
 * langkah, POST menyimpan lalu memindahkan langkah, dan langkahnya disimpan DI
 * BARIS (`rd_laporan.current_step`) — bukan di sesi atau URL — supaya pengisian
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
            'title'         => 'Capaian Perumahan',
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
     * Kumulatifnya dihitung `Rekam_data_model::kumulatif()`, bukan di sini —
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

    public function riwayat()
    {
        $tahun = (int) ($this->input->get('tahun') ?: date('Y'));

        $this->render_scoped_admin('admin/rekam/riwayat', [
            'title'       => 'Riwayat Pelaporan Perumahan',
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
     * laporan, bukan dari URL — URL hanya boleh MEMILIH langkah yang sudah
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
            // Tanpa ini ia memilih TW berjalan, masuk, lalu baru ditolak — dan
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
                // closure `use (...)` di awal berkas — dan `use` dievaluasi saat
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
            ]);
            return;
        }

        $isi = $this->rd->isi_laporan($laporan_id, $this->my_kabupaten_id);
        if ( ! $isi || $isi['laporan']['domain'] !== 'perumahan') {
            show_404();
            return;
        }

        $langkah = (string) ($this->input->get('langkah') ?: $isi['laporan']['current_step']);
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
        // langsung ke isiannya — yang memang boleh dibaca.
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

        // Nilai mentah SENGAJA tidak dibersihkan di sini — model yang menolak
        // negatif, bukan-angka, dan anggaran tanpa unit, supaya aturannya satu
        // tempat dan jalur lain ikut terlindungi.
        $hasil = $this->rd->simpan_sumber($laporan_id, $program,
            (string) $this->input->post('sumber_dana', TRUE), [
                'rencana_unit'       => $this->input->post('rencana_unit'),
                'rencana_anggaran'   => $this->input->post('rencana_anggaran'),
                'realisasi_unit'     => $this->input->post('realisasi_unit'),
                'realisasi_anggaran' => $this->input->post('realisasi_anggaran'),
                'keterangan'         => $this->input->post('keterangan', TRUE),
            ], $this->my_kabupaten_id);

        $this->pulang_isian($hasil, $laporan_id, $program, 'Sumber dana tersimpan.');
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

        $this->pulang_isian($hasil, $laporan_id, $program, 'Sumber dana dihapus.');
    }

    /** Pindah langkah tanpa menyimpan apa pun — tombol Lanjut/Kembali. */
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
     * 404 — pengguna dikembalikan ke layar dengan pesan yang bisa dibaca.
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
        // penyajian — mempercayai klien di sini berarti membiarkan klien
        // menentukan header (dan curl saja sudah mengirim octet-stream).
        $path_tersimpan = $this->private_upload_dir('rekam_bnba', $laporan_id) . $tersimpan;
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path_tersimpan) ?: 'application/octet-stream';

        $hasil = $this->rd->simpan_bnba($laporan_id, [
            'nama_asli'    => $_FILES['bnba']['name'],
            'private_path' => $tersimpan,
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
            $berkas['private_path'], $berkas['mime_type'] ?: 'application/octet-stream');
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
        $this->session->set_flashdata('success', 'Laporan terkirim dan terkunci.');
        redirect('Rekam_Perumahan?tahun=' . (int) $laporan['tahun'] . '&triwulan=' . (int) $laporan['triwulan']);
    }

    // ---------------------------------------------------------------- internal

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
        // Urutannya SENGAJA mengikuti Rekam_data_model::SUMBER_PERUMAHAN supaya
        // urutan di layar isian dan di rekap tidak pernah berbeda.
        return [
            'apbd_provinsi'   => 'APBD Provinsi',
            'apbd_kabkota'    => 'APBD Kabupaten/Kota',
            'apbn_bsps'       => 'APBN BSPS (dari Kementerian PKP)',
            'apbn_dak'        => 'APBN DAK',
            'apbn_kemensos'   => 'APBN Kemensos',
            'apbn_dana_desa'  => 'APBN Dana Desa',
            'apbn_kl_lain'    => 'APBN dari Kementerian/Lembaga Lain',
            'baznas_ri'       => 'BAZNAS RI',
            'baznas_provinsi' => 'BAZNAS Provinsi',
            'baznas_kabkota'  => 'BAZNAS Kab/Kota',
            'csr'             => 'CSR',
            'dana_lainnya'    => 'Dana Lainnya',
        ];
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
