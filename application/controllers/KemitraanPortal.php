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

        $this->render('pages/kemitraan_portal/daftar', [
            'judul' => $jenis === 'kkn' ? 'Daftar KKN Tematik' : 'Daftar Magang dan Kerja Praktik',
            'jenis' => $jenis,
        ]);
    }

    public function simpan()
    {
        $jenis = $this->input->post('jenis', TRUE);
        if ( ! in_array($jenis, ['kkn', 'magang'], TRUE) || $this->input->method(TRUE) !== 'POST') { show_404(); }
        if ( ! $this->akses_mahasiswa('KemitraanPortal/daftar/' . $jenis)) { return; }

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

        $file_surat_pengantar = NULL;
        if ( ! empty($_FILES['file_surat_pengantar']['name'])) {
            $upload_path = '.assets/uploads/';
            if ( ! is_dir($upload_path)) { mkdir($upload_path, 0755, TRUE); }

            $this->load->library('upload', [
                'upload_path'   => $upload_path,
                'allowed_types' => 'pdf|jpg|jpeg|png',
                'max_size'      => 5120,
                'encrypt_name'  => TRUE,
            ]);

            if ( ! $this->upload->do_upload('file_surat_pengantar')) {
                $this->session->set_flashdata('error', 'Gagal mengunggah surat pengantar: ' . $this->upload->display_errors('', ''));
                redirect('KemitraanPortal/daftar/' . $jenis);
                return;
            }
            $file_surat_pengantar = $this->upload->data('file_name');
        }

        // user_id selalu dari sesi (anti-IDOR), bukan dari input.
        $this->db->insert('kkn_magang_pendaftaran', [
            'user_id'              => $this->get_user_id(),
            'jenis'                => $jenis,
            'instansi_asal'        => $this->input->post('instansi_asal', TRUE),
            'no_hp'                => $this->input->post('no_hp', TRUE),
            'divisi_atau_tema'     => $this->input->post('divisi_atau_tema', TRUE),
            'periode_mulai'        => $this->input->post('periode_mulai', TRUE),
            'periode_selesai'      => $this->input->post('periode_selesai', TRUE),
            'file_surat_pengantar' => $file_surat_pengantar,
        ]);

        $this->session->set_flashdata('success', 'Pendaftaran ' . strtoupper($jenis) . ' berhasil dikirim. Cek status pendaftaran di halaman akun Anda.');
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
