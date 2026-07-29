<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Rekam Data — Input Capaian Kawasan (tahap D4).
 *
 * Kabupaten SELALU dari sesi, sama seperti Rekam_Perumahan. Bedanya bentuk data:
 * satu ringkasan per laporan + daftar intervensi berulang TANPA BATAS (form
 * dinas berhenti di 20 karena Google Form tidak bisa mengulang baris; batas itu
 * tidak dibawa ke sini).
 *
 * Total anggaran dan total padat karya TIDAK pernah diketik — keduanya
 * dijumlahkan dari daftar intervensi, supaya tidak lahir dua angka yang bisa
 * saling bertentangan seperti di form aslinya.
 *
 * Acuan: docs/product/ROADMAP_REKAM_DATA.md (D4)
 */
class Rekam_Kawasan extends Admin_Kabkota_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Rekam_data_model', 'rd');
    }

    public function index()
    {
        $tahun = (int) ($this->input->get('tahun') ?: date('Y'));
        $bulan = (int) ($this->input->get('bulan') ?: date('n'));

        $hasil = $this->rd->ambil_atau_buat_draft('kawasan', $this->my_kabupaten_id, $tahun, $bulan);
        if (empty($hasil['success'])) {
            $this->session->set_flashdata('error', $hasil['message']);
            $hasil = $this->rd->ambil_atau_buat_draft(
                'kawasan', $this->my_kabupaten_id, (int) date('Y'), (int) date('n'));
            if (empty($hasil['success'])) {
                show_error('Draft laporan tidak dapat dibuka.', 500);
                return;
            }
        }

        $isi = $this->rd->isi_laporan((int) $hasil['laporan']['id'], $this->my_kabupaten_id);

        $data = [
            'title'       => 'Input Capaian Kawasan',
            'scope_label' => $this->db->where('id', $this->my_kabupaten_id)
                ->get('kabupaten')->row('nama') ?: 'Wilayah Saya',
            'laporan'     => $isi['laporan'],
            'ringkasan'   => $isi['ringkasan'],
            'intervensi'  => $isi['intervensi'],
            'total'       => $isi['total'],
            'diwarisi'    => (int) ($hasil['diwarisi'] ?? 0),
            'terkunci'    => $isi['laporan']['status'] === 'terkirim',
            'indikator_label'  => $this->label_indikator(),
            'sumber_label'     => $this->label_sumber(),
            'ubah_id'     => (int) $this->input->get('ubah'),
        ];

        $this->render_scoped_admin('admin/rekam/kawasan_input', $data);
    }

    /** Rekap satu periode. Tidak ada `SUM()` antar bulan — lihat catatan di model. */
    public function rekap()
    {
        $tahun = (int) ($this->input->get('tahun') ?: date('Y'));
        $bulan = (int) ($this->input->get('bulan') ?: date('n'));

        $baris = $this->rd->rekap('kawasan', $tahun, $bulan, $this->my_kabupaten_id);
        $ringkasan = $baris[0] ?? NULL;

        $intervensi = [];
        if ($ringkasan) {
            $intervensi = $this->db->order_by('urutan', 'ASC')
                ->get_where('rd_kawasan_intervensi', ['laporan_id' => (int) $ringkasan['laporan_id']])
                ->result_array();
        }

        $this->render_scoped_admin('admin/rekam/kawasan_rekap', [
            'title'           => 'Rekap Pelaporan Kawasan',
            'scope_label'     => $this->db->where('id', $this->my_kabupaten_id)
                ->get('kabupaten')->row('nama') ?: 'Wilayah Saya',
            'tahun'           => $tahun,
            'bulan'           => $bulan,
            'ringkasan'       => $ringkasan,
            'intervensi'      => $intervensi,
            'indikator_label' => $this->label_indikator(),
            'sumber_label'    => $this->label_sumber(),
        ]);
    }

    public function riwayat()
    {
        $tahun = (int) ($this->input->get('tahun') ?: date('Y'));

        $this->render_scoped_admin('admin/rekam/riwayat', [
            'title'       => 'Riwayat Pelaporan Kawasan',
            'scope_label' => $this->db->where('id', $this->my_kabupaten_id)
                ->get('kabupaten')->row('nama') ?: 'Wilayah Saya',
            'domain'      => 'kawasan',
            'base_url'    => 'Rekam_Kawasan',
            'tahun'       => $tahun,
            'periode'     => $this->rd->riwayat('kawasan', $this->my_kabupaten_id,
                $tahun, $this->my_kabupaten_id),
        ]);
    }

    public function simpan_ringkasan()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        $laporan_id = (int) $this->input->post('laporan_id');
        $hasil = $this->rd->simpan_ringkasan($laporan_id, [
            'ada_penanganan'  => $this->input->post('ada_penanganan') === '1',
            'ada_progres'     => $this->input->post('ada_progres') === '1',
            'catatan_progres' => $this->input->post('catatan_progres', TRUE),
            'total_luas_ha'   => $this->input->post('total_luas_ha'),
        ], $this->my_kabupaten_id);

        $this->pulang($hasil, $laporan_id, 'Ringkasan tersimpan.');
    }

    public function simpan_intervensi()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        $laporan_id = (int) $this->input->post('laporan_id');
        $ubah       = (int) $this->input->post('intervensi_id');

        $hasil = $this->rd->simpan_intervensi($laporan_id, [
            'indikator'         => $this->input->post('indikator', TRUE),
            'nama_kegiatan'     => $this->input->post('nama_kegiatan', TRUE),
            'lokasi_teks'       => $this->input->post('lokasi_teks', TRUE),
            'sumber_anggaran'   => $this->input->post('sumber_anggaran', TRUE),
            'keterangan_sumber' => $this->input->post('keterangan_sumber', TRUE),
            'volume'            => $this->input->post('volume'),
            'nilai_anggaran'    => $this->input->post('nilai_anggaran'),
            'nilai_padat_karya' => $this->input->post('nilai_padat_karya'),
        ], $ubah ?: NULL, $this->my_kabupaten_id);

        $this->pulang($hasil, $laporan_id, $ubah ? 'Intervensi diperbarui.' : 'Intervensi ditambahkan.');
    }

    public function hapus_intervensi()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        $laporan_id = (int) $this->input->post('laporan_id');
        $hasil = $this->rd->hapus_intervensi($laporan_id,
            (int) $this->input->post('intervensi_id'), $this->my_kabupaten_id);

        $this->pulang($hasil, $laporan_id, 'Intervensi dihapus dan urutannya dirapatkan.');
    }

    public function kirim()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }
        $laporan_id = (int) $this->input->post('laporan_id');
        $hasil = $this->rd->kirim($laporan_id, $this->get_user_id(), $this->my_kabupaten_id);

        $this->pulang($hasil, $laporan_id, 'Laporan terkirim dan terkunci.');
    }

    // ---------------------------------------------------------------- internal

    /** Pesan sukses hanya dipasang kalau model benar-benar melaporkan sukses. */
    private function pulang($hasil, $laporan_id, $pesan_sukses)
    {
        if (empty($hasil['success'])) {
            $this->session->set_flashdata('error', $hasil['message'] ?? 'Perubahan tidak tersimpan.');
        } else {
            $this->session->set_flashdata('success', $pesan_sukses);
        }
        $laporan = $this->rd->laporan($laporan_id, $this->my_kabupaten_id);
        redirect('Rekam_Kawasan?tahun=' . (int) ($laporan['tahun'] ?? date('Y'))
            . '&bulan=' . (int) ($laporan['bulan'] ?? date('n')));
    }

    /**
     * Label + satuan indikator. Satuan diturunkan dari model (tidak disimpan di
     * DB) supaya tidak bisa bertentangan dengan indikatornya.
     * `Proteksi Kebakaran` memang tanpa satuan di form dinas — pertanyaan
     * terbuka nomor 4, jangan dikarang.
     */
    private function label_indikator()
    {
        $nama = [
            'bangunan_gedung'    => 'Bangunan Gedung',
            'jalan_lingkungan'   => 'Jalan Lingkungan',
            'air_minum'          => 'Air Minum',
            'drainase'           => 'Drainase',
            'air_limbah'         => 'Air Limbah',
            'persampahan'        => 'Persampahan',
            'proteksi_kebakaran' => 'Proteksi Kebakaran',
        ];
        $out = [];
        foreach ($nama as $kode => $label) {
            $satuan = Rekam_data_model::satuan($kode);
            $out[$kode] = ['label' => $label, 'satuan' => $satuan];
        }
        return $out;
    }

    private function label_sumber()
    {
        return [
            'apbn'          => 'APBN',
            'apbd_provinsi' => 'APBD Provinsi',
            'apbd_kabkota'  => 'APBD Kab/Kota',
            'dana_desa'     => 'Dana Desa',
            'csr'           => 'CSR',
            'baznas'        => 'Baznas',
            'dana_lainnya'  => 'Dana Lainnya',
        ];
    }
}
