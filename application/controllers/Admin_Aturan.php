<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Aturan rekomendasi (tahap 2 aturan yang bisa diatur dinas, 26 Okt 2026). Super Admin menyunting satu DRAF
 * salinan versi terbit: program yang ditawarkan (desil sasaran, jenis cek, urutan) dan baris matriks rekomendasi
 * awal; uji coba membandingkan draf dengan versi terbit pada contoh warga sintetis; TERBITKAN membuat mesin
 * memakai draf itu dan versi lama menjadi arsip. Pengajuan yang sudah masuk antrean tidak berubah.
 *
 * Batas: kolom kriteria dan jenis cek tetap (Warga_ruleset::JENIS_CEK, Matriks_program_ruleset::PILIHAN);
 * kategori atau cek baru tetap pekerjaan pengembang. Patokan sementara sampai dinas meninjau: matriks belum
 * menggantikan desil, semua Super Admin boleh menerbitkan, setiap terbit tercatat di jejak audit.
 */
class Admin_Aturan extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Aturan_model');
        $this->load->library('Warga_ruleset');
        $this->load->library('Matriks_program_ruleset');
    }

    public function index()
    {
        $aktif = $this->Aturan_model->kode_aktif();
        $draf = $this->Aturan_model->draf();
        $kode = $draf['kode'] ?? $aktif;
        $aturan = array_column($this->Aturan_model->program($kode), NULL, 'program_id');
        $katalog = $this->db->select('p.id, p.kode_program, p.nama_program, p.aktif')->from('sf_program p')
            ->order_by('p.urutan')->order_by('p.nama_program')->get()->result_array();
        // Urutan tampil = urutan aturan; program yang belum ditawarkan di bawah.
        usort($katalog, fn($a, $b) => [isset($aturan[$a['id']]) ? 0 : 1, $aturan[$a['id']]['urutan'] ?? 99]
            <=> [isset($aturan[$b['id']]) ? 0 : 1, $aturan[$b['id']]['urutan'] ?? 99]);
        $this->render_admin('admin/aturan/index', [
            'title' => 'Aturan Rekomendasi',
            'aktif' => $aktif ? $this->Aturan_model->versi($aktif) : NULL,
            'draf' => $draf,
            'katalog' => $katalog,
            'aturan' => $aturan,
            'matriks' => $this->Aturan_model->matriks($kode),
            'uji' => $draf ? $this->Aturan_model->uji_coba() : NULL,
            'riwayat' => $this->Aturan_model->riwayat(),
        ]);
    }

    public function buat_draf()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }
        $d = $this->Aturan_model->buat_draf($this->get_user_id());
        if ($d) {
            $this->catat_audit('aturan_draf_dibuat', 'Draf aturan rekomendasi ' . $d['kode'], 'aturan_versi', $d['id']);
        }
        $this->kembali($d ? 'success' : 'error', $d ? 'Draf dibuat dari versi terbit. Perubahan belum berlaku sebelum diterbitkan.' : 'Draf gagal dibuat.');
    }

    public function simpan_program()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }
        $galat = $this->Aturan_model->simpan_program_draf((array) $this->input->post('program'));
        if ( ! $galat) {
            $this->catat_audit('aturan_draf_diubah', 'Program yang ditawarkan di draf diubah', 'aturan_versi', $this->Aturan_model->draf()['id'] ?? NULL);
        }
        $this->kembali($galat ? 'error' : 'success', $galat ? implode(' ', $galat) : 'Program di draf tersimpan. Lihat uji coba sebelum menerbitkan.');
    }

    public function simpan_matriks()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }
        $galat = $this->Aturan_model->simpan_matriks_draf((array) $this->input->post('baris'));
        if ( ! $galat) {
            $this->catat_audit('aturan_draf_diubah', 'Baris matriks di draf diubah', 'aturan_versi', $this->Aturan_model->draf()['id'] ?? NULL);
        }
        $this->kembali($galat ? 'error' : 'success', $galat ? implode(' ', $galat) : 'Matriks di draf tersimpan. Lihat uji coba sebelum menerbitkan.');
    }

    public function terbitkan()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }
        if ( ! $this->input->post('konfirmasi')) {
            $this->kembali('error', 'Centang konfirmasi sebelum menerbitkan.');
            return;
        }
        $uji = $this->Aturan_model->uji_coba();
        $hasil = $this->Aturan_model->terbitkan($this->get_user_id());
        if ($hasil) {
            [$baru, $lama] = $hasil;
            $this->catat_audit('aturan_diterbitkan', 'Aturan rekomendasi ' . $baru . ' diterbitkan menggantikan ' . $lama,
                'aturan_versi', NULL, ['baru' => $baru, 'lama' => $lama, 'contoh_berubah' => $uji['berubah'] ?? NULL]);
        }
        $this->kembali($hasil ? 'success' : 'error', $hasil ? 'Aturan ' . $hasil[0] . ' diterbitkan dan langsung dipakai untuk penilaian warga berikutnya.'
            : 'Draf tidak bisa diterbitkan: pastikan ada program yang ditawarkan dan minimal satu baris matriks.');
    }

    public function buang_draf()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }
        $d = $this->Aturan_model->buang_draf();
        if ($d) {
            $this->catat_audit('aturan_draf_dibuang', 'Draf aturan rekomendasi ' . $d['kode'] . ' dibuang', 'aturan_versi', $d['id']);
        }
        $this->kembali('success', $d ? 'Draf dibuang; aturan terbit tidak berubah.' : 'Tidak ada draf.');
    }

    private function kembali($jenis, $pesan)
    {
        $this->session->set_flashdata($jenis, $pesan);
        redirect('Admin_Aturan');
    }
}
