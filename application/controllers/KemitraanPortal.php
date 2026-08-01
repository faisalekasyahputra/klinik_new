<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class KemitraanPortal extends Public_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
    }

    public function index()
    {
        $this->render('pages/kemitraan_portal/index', ['judul' => 'KKN dan Magang']);
    }

    public function kkn()
    {
        $this->render('pages/kemitraan_portal/kkn', ['judul' => 'KKN Tematik']);
    }

    public function magang()
    {
        $this->render('pages/kemitraan_portal/magang', [
            'judul' => 'Magang dan Kerja Praktik',
            'slot_magang' => [
                ['divisi' => 'Administrasi Pemerintahan', 'tersedia' => ['Juni']],
                ['divisi' => 'Infrastruktur dan Teknologi Digital', 'tersedia' => ['Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']],
                ['divisi' => 'Komunikasi Publik dan Media', 'tersedia' => ['April', 'Mei', 'Juni', 'Agustus', 'September', 'Oktober', 'November', 'Desember']],
                ['divisi' => 'Komunikasi Publik dan Media (PPID)', 'tersedia' => ['April', 'Mei', 'Juni', 'Agustus', 'September', 'Oktober', 'November', 'Desember']],
                ['divisi' => 'Pengelolaan Data Statistik', 'tersedia' => ['Oktober', 'November', 'Desember']],
                ['divisi' => 'Penyediaan dan Pengadaan Barang', 'tersedia' => ['Januari', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']],
                ['divisi' => 'Sekretariat', 'tersedia' => ['Maret', 'April', 'Mei', 'Juni', 'Oktober', 'November', 'Desember']],
            ],
        ]);
    }

    public function daftar($jenis = NULL)
    {
        if ( ! in_array($jenis, ['kkn', 'magang'], TRUE)) { show_404(); }
        if ( ! $this->akses_mahasiswa('KemitraanPortal/daftar/' . $jenis)) { return; }

        // Nama & email ditampilkan BACA-SAJA di formulir, diambil dari sesi.
        // Bukan sekadar hiasan: pendaftaran ini menempel ke akun lewat user_id,
        // dan sebelumnya pendaftar tidak pernah diberi tahu nama siapa yang
        // ikut terkirim. Tetap tidak diterima sebagai input — `simpan()` hanya
        // membaca user_id dari sesi (anti-IDOR), jadi mengubahnya di peramban
        // tidak mengubah apa pun.
        $this->render('pages/kemitraan_portal/daftar', [
            'judul'      => $jenis === 'kkn' ? 'Daftar KKN Tematik' : 'Daftar Magang dan Kerja Praktik',
            'jenis'      => $jenis,
            'nama_akun'  => (string) $this->session->userdata('name'),
            'email_akun' => (string) $this->session->userdata('email'),
        ]);
    }

    public function simpan()
    {
        $jenis = $this->input->post('jenis', TRUE);
        if ( ! in_array($jenis, ['kkn', 'magang'], TRUE) || $this->input->method(TRUE) !== 'POST') { show_404(); }
        if ( ! $this->akses_mahasiswa('KemitraanPortal/daftar/' . $jenis)) { return; }

        // Identitas mahasiswa (migrasi 20260701000025). Kolomnya NULL di skema
        // demi baris lama, jadi kewajiban isinya HARUS ditegakkan di sini —
        // kalau tidak, tidak ada satu pun tempat yang memeriksanya.
        $this->form_validation->set_rules('nim', 'NIM', 'required|trim|alpha_numeric_spaces|max_length[30]');
        $this->form_validation->set_rules('tempat_lahir', 'Tempat Lahir', 'required|trim|max_length[100]');
        $this->form_validation->set_rules('tanggal_lahir', 'Tanggal Lahir', 'required|regex_match[/^\d{4}-\d{2}-\d{2}$/]');
        $this->form_validation->set_rules('semester', 'Semester', 'required|integer|greater_than[0]|less_than[15]');
        $this->form_validation->set_rules('jurusan', 'Jurusan', 'required|trim|max_length[150]');

        $this->form_validation->set_rules('instansi_asal', 'Instansi Asal', 'required|trim|max_length[150]');
        $this->form_validation->set_rules('no_hp', 'Nomor HP', 'required|numeric|min_length[10]|max_length[15]');
        $this->form_validation->set_rules('divisi_atau_tema', 'Divisi/Tema', 'required|trim|max_length[150]');
        $this->form_validation->set_rules('periode_mulai', 'Periode Mulai', 'required|regex_match[/^\d{4}-\d{2}-\d{2}$/]');
        $this->form_validation->set_rules('periode_selesai', 'Periode Selesai', 'required|regex_match[/^\d{4}-\d{2}-\d{2}$/]');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', validation_errors('<li>', '</li>'));
            redirect('KemitraanPortal/daftar/' . $jenis);
            return;
        }

        // user_id selalu dari sesi (anti-IDOR), bukan dari input. Baris dibuat
        // dulu supaya surat pengantarnya punya folder pemilik sendiri
        // (private_uploads/kemitraan/{id}/).
        $this->db->insert('kkn_magang_pendaftaran', [
            'user_id'              => $this->get_user_id(),
            'jenis'                => $jenis,
            'nim'                  => $this->input->post('nim', TRUE),
            'tempat_lahir'         => $this->input->post('tempat_lahir', TRUE),
            'tanggal_lahir'        => $this->input->post('tanggal_lahir', TRUE),
            'semester'             => (int) $this->input->post('semester', TRUE),
            'jurusan'              => $this->input->post('jurusan', TRUE),
            'instansi_asal'        => $this->input->post('instansi_asal', TRUE),
            'no_hp'                => $this->input->post('no_hp', TRUE),
            'divisi_atau_tema'     => $this->input->post('divisi_atau_tema', TRUE),
            'periode_mulai'        => $this->input->post('periode_mulai', TRUE),
            'periode_selesai'      => $this->input->post('periode_selesai', TRUE),
            'file_surat_pengantar' => NULL,
            'file_proposal'        => NULL,
        ]);
        $id = $this->db->insert_id();

        $pesan = 'Pendaftaran ' . strtoupper($jenis) . ' berhasil dikirim. Cek status pendaftaran di halaman akun Anda.';

        // Berkas disimpan di luar webroot, hanya bisa dibuka admin lewat
        // Admin_Kemitraan::lihat_dokumen() — dulu di .assets/uploads/ yang bisa
        // diakses HTTP langsung. Pendaftaran tetap tersimpan kalau berkasnya
        // gagal; pendaftar diberi tahu apa adanya, bukan dibiarkan mengira
        // lampirannya sudah masuk.
        //
        // Proposal HANYA untuk magang (keputusan user 30 Jul). Field-nya juga
        // tidak dirender di formulir KKN, tapi pemeriksaan diulang di sini:
        // yang menentukan apa yang tersimpan adalah server, bukan formulir yang
        // dikirim peramban.
        $berkas = ['file_surat_pengantar' => 'Surat pengantar'];
        if ($jenis === 'magang') { $berkas['file_proposal'] = 'Proposal'; }

        $simpan = [];
        foreach ($berkas as $field => $label) {
            $galat = NULL;
            $nama_berkas = $this->store_private_upload($field, 'kemitraan', $id, $galat);
            if ($nama_berkas) {
                $simpan[$field] = $nama_berkas;
            } elseif ($galat) {
                $pesan .= ' Namun ' . strtolower($label) . ' gagal diunggah (' . $galat . ') — hubungi admin untuk menyusulkan.';
            }
        }
        if ($simpan) {
            $this->db->where('id', $id)->update('kkn_magang_pendaftaran', $simpan);
        }

        $this->session->set_flashdata('success', $pesan);
        redirect('akun');
    }

    private function akses_mahasiswa($target)
    {
        if ( ! $this->is_logged_in()) {
            $this->session->set_userdata('intended_url', $target);
            $this->session->set_flashdata('error', 'Silakan masuk atau daftar akun mahasiswa terlebih dahulu.');
            redirect('Auth/login');
            return FALSE;
        }
        if ($this->session->userdata('role') !== 'mahasiswa') {
            $this->session->set_flashdata('error', 'Pendaftaran KKN/Magang hanya tersedia untuk akun dengan peran mahasiswa.');
            redirect('akun');
            return FALSE;
        }
        return TRUE;
    }
}
