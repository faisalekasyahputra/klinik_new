<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rekam Data — Input Capaian Perumahan (tahap D2).
 *
 * Kabupaten SELALU dari sesi (`$this->my_kabupaten_id`), tidak pernah dari
 * request: tidak ada dropdown wilayah di layar mana pun, dan tiap panggilan
 * model membawa scope itu sebagai gerbang, bukan sebagai penyaring tampilan.
 *
 * Tahap ini hanya isi + draft. Kirim, BNBA, dan pewarisan tampil di layar
 * adalah D3; peninjauan provinsi D6.
 *
 * Acuan: docs/product/ROADMAP_REKAM_DATA.md (D2)
 */
class Rekam_Perumahan extends Admin_Kabkota_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Rekam_data_model', 'rd');
    }

    /**
     * Layar pertama Capaian Perumahan: TABEL DULU, tombol Input di bawahnya.
     * Urutan ini mengikuti sketsa Menu Utama — orang melihat capaian yang sudah
     * tercatat sebelum mengubahnya, bukan langsung disuguhi sepuluh bagian isian.
     *
     * Baca-saja, dan itu penting: memakai `laporan_periode()` yang TIDAK membuat
     * apa pun, bukan `ambil_atau_buat_draft()`. Kalau layar baca ikut membuat
     * draft, setiap admin yang cuma menengok melahirkan periode baru di
     * `rd_laporan` dan riwayat pelaporan penuh bulan kosong yang tidak pernah
     * diniatkan. Draft lahir di `input()` saja.
     *
     * Berbeda dari `rekap()`: di sini angka SENDIRI apa adanya termasuk draft,
     * di sana hanya laporan berstatus `terkirim` dan bisa lintas periode.
     */
    public function index()
    {
        $tahun = (int) ($this->input->get('tahun') ?: date('Y'));
        $bulan = (int) ($this->input->get('bulan') ?: date('n'));

        $laporan = $this->rd->laporan_periode('perumahan', $this->my_kabupaten_id, $tahun, $bulan);
        $matriks = [];
        if ($laporan) {
            $isi = $this->rd->isi_laporan((int) $laporan['id'], $this->my_kabupaten_id);
            foreach ($isi['baris'] as $row) {
                $matriks[$row['sumber_dana']][$row['program']] = $row;
            }
        }

        $this->render_scoped_admin('admin/rekam/perumahan_rekap', [
            'title'         => 'Capaian Perumahan',
            'scope_label'   => $this->nama_wilayah(),
            'tahun'         => $tahun,
            'bulan'         => $bulan,
            'matriks'       => $matriks,
            'ada_data'      => ! empty($matriks),
            'sumber_label'  => $this->label_sumber(),
            'program_label' => $this->label_program(),
            // Mode `capaian` membuat view menampilkan status laporan dan tombol
            // Input Capaian, serta mengarahkan pemilih periode ke layar ini.
            'mode'          => 'capaian',
            'laporan'       => $laporan,
        ]);
    }

    /**
     * Form isian. Di sinilah draft periode LAHIR — satu-satunya layar yang
     * membuatnya, dipicu tombol Input Capaian di `index()`.
     *
     * Periode dipilih lewat query string biasa (GET), bukan segmen URL, supaya
     * tautan "ganti bulan" tetap satu halaman yang sama.
     */
    public function input()
    {
        $tahun = (int) ($this->input->get('tahun') ?: date('Y'));
        $bulan = (int) ($this->input->get('bulan') ?: date('n'));

        $hasil = $this->rd->ambil_atau_buat_draft('perumahan', $this->my_kabupaten_id, $tahun, $bulan);
        if (empty($hasil['success'])) {
            $this->session->set_flashdata('error', $hasil['message']);
            // Jatuh ke periode berjalan supaya layar tidak kosong tanpa sebab.
            $hasil = $this->rd->ambil_atau_buat_draft(
                'perumahan', $this->my_kabupaten_id, (int) date('Y'), (int) date('n'));
            if (empty($hasil['success'])) {
                show_error('Draft laporan tidak dapat dibuka.', 500);
                return;
            }
        }

        $laporan_id = (int) $hasil['laporan']['id'];
        $isi        = $this->rd->isi_laporan($laporan_id, $this->my_kabupaten_id);

        $data = [
            'title'        => 'Input Capaian Perumahan',
            'scope_label'  => $this->nama_wilayah(),
            'laporan'      => $isi['laporan'],
            'bagian'       => array_column($isi['bagian'], 'ada', 'sumber_dana'),
            'baris'        => $this->baris_per_sumber($isi['baris']),
            'belum_dijawab' => $isi['belum_dijawab'],
            'bnba'         => $isi['bnba'],
            'diwarisi'     => (int) ($hasil['diwarisi'] ?? 0),
            'sumber_label' => $this->label_sumber(),
            'program_label' => $this->label_program(),
            'sumber_berketerangan' => ['apbn_kl_lain' => 'Kementerian sumber',
                'csr' => 'Perusahaan penyalur', 'dana_lainnya' => 'Sumber penyalur'],
            'terkunci'     => $isi['laporan']['status'] === 'terkirim',
        ];

        $this->render_scoped_admin('admin/rekam/perumahan_input', $data);
    }

    /** Jawaban gerbang "Ada / Tidak Ada" untuk satu sumber dana. */
    public function simpan_gerbang()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        $laporan_id = (int) $this->input->post('laporan_id');
        $hasil = $this->rd->simpan_bagian(
            $laporan_id,
            $this->input->post('sumber_dana', TRUE),
            $this->input->post('ada') === '1',
            $this->my_kabupaten_id
        );
        $this->pulang($hasil, $laporan_id, $hasil['success'] ?? FALSE
            ? 'Jawaban sumber dana tersimpan.' : NULL);
    }

    /** Enam program sekaligus untuk satu sumber dana. */
    public function simpan_angka()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        $laporan_id = (int) $this->input->post('laporan_id');
        $sumber     = $this->input->post('sumber_dana', TRUE);

        // Bentuk payload: program[<kode>][unit|anggaran|keterangan].
        // Nilai mentah sengaja TIDAK dibersihkan di sini — model yang menolak
        // negatif, bukan-angka, dan anggaran tanpa unit, supaya aturannya satu
        // tempat dan jalur lain ikut terlindungi.
        $baris = [];
        foreach ((array) $this->input->post('program') as $program => $nilai) {
            $baris[$program] = [
                'unit'       => $nilai['unit'] ?? 0,
                'anggaran'   => $nilai['anggaran'] ?? 0,
                'keterangan' => $nilai['keterangan'] ?? '',
            ];
        }

        $hasil = $this->rd->simpan_baris($laporan_id, $sumber, $baris, $this->my_kabupaten_id);
        $this->pulang($hasil, $laporan_id, $hasil['success'] ?? FALSE
            ? 'Angka ' . ($this->label_sumber()[$sumber] ?? $sumber) . ' tersimpan.' : NULL);
    }

    /**
     * Rekap satu periode. **Tidak menjumlahkan antar bulan** — angkanya sudah
     * kumulatif, `SUM()` lintas bulan akan melipatgandakan capaian. Itu jebakan
     * terbesar modul ini, jadi periodenya selalu disebut eksplisit di layar.
     */
    public function rekap()
    {
        $tahun = (int) ($this->input->get('tahun') ?: date('Y'));
        $bulan = (int) ($this->input->get('bulan') ?: date('n'));

        $baris = $this->rd->rekap('perumahan', $tahun, $bulan, $this->my_kabupaten_id);
        $matriks = [];
        foreach ($baris as $row) {
            $matriks[$row['sumber_dana']][$row['program']] = $row;
        }

        $this->render_scoped_admin('admin/rekam/perumahan_rekap', [
            'title'         => 'Rekap Pelaporan Perumahan',
            'scope_label'   => $this->nama_wilayah(),
            'tahun'         => $tahun,
            'bulan'         => $bulan,
            'matriks'       => $matriks,
            'ada_data'      => ! empty($baris),
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
            'periode'     => $this->rd->riwayat('perumahan', $this->my_kabupaten_id,
                $tahun, $this->my_kabupaten_id),
        ]);
    }

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
            $this->kembali_ke_periode($laporan);
            return;
        }

        $galat = NULL;
        $tersimpan = $this->store_private_upload('bnba', 'rekam_bnba', $laporan_id, $galat);
        if ($tersimpan === FALSE) {
            $this->session->set_flashdata('error', $galat ?: 'Berkas gagal diunggah.');
            $this->kembali_ke_periode($laporan);
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
        $this->kembali_ke_periode($laporan);
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
        $this->pulang($hasil, $laporan_id, $hasil['success'] ?? FALSE
            ? 'Laporan terkirim dan terkunci.' : NULL);
    }

    // ---------------------------------------------------------------- internal

    /**
     * Kembali ke layar ISIAN, bukan ke `index()` yang kini tabel baca-saja.
     * Dipakai alur unggah BNBA, yang formulirnya memang ada di layar isian —
     * melempar orang ke tabel sesudah gagal mengunggah menyembunyikan pesan
     * galatnya dari tempat ia bisa mencoba lagi.
     */
    private function kembali_ke_periode(array $laporan)
    {
        redirect('Rekam_Perumahan/input?tahun=' . (int) $laporan['tahun']
            . '&bulan=' . (int) $laporan['bulan']);
    }

    /**
     * Satu pintu keluar untuk kedua aksi tulis: pesan sukses HANYA dipasang
     * kalau modelnya benar-benar melaporkan sukses (§19 — tidak boleh ada
     * penanda sukses sederajat dengan query tulis).
     */
    private function pulang($hasil, $laporan_id, $pesan_sukses)
    {
        if (empty($hasil['success'])) {
            $this->session->set_flashdata('error', $hasil['message'] ?? 'Perubahan tidak tersimpan.');
        } else {
            $this->session->set_flashdata('success', $pesan_sukses);
        }
        $laporan = $this->rd->laporan($laporan_id, $this->my_kabupaten_id);
        redirect('Rekam_Perumahan/input?tahun=' . (int) ($laporan['tahun'] ?? date('Y'))
            . '&bulan=' . (int) ($laporan['bulan'] ?? date('n')));
    }

    private function nama_wilayah()
    {
        return $this->db->where('id', $this->my_kabupaten_id)
            ->get('kabupaten')->row('nama') ?: 'Wilayah Saya';
    }

    /** ['apbd_kabkota' => ['pk_rtlh' => [...]], ...] */
    private function baris_per_sumber(array $baris)
    {
        $out = [];
        foreach ($baris as $row) {
            $out[$row['sumber_dana']][$row['program']] = $row;
        }
        return $out;
    }

    /** Label verbatim dari form dinas — jangan diperhalus, dinas mengenalinya. */
    private function label_sumber()
    {
        // Urutannya SENGAJA mengikuti Rekam_data_model::SUMBER_PERUMAHAN supaya
        // urutan di layar input dan di rekap tidak pernah berbeda.
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
            'pb_backlog'  => 'PB BACKLOG',
            'pk_bencana'  => 'PK BENCANA',
            'pb_bencana'  => 'PB BENCANA',
            'pb_relokasi' => 'PB RELOKASI',
        ];
    }
}
