<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pengembang extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->helper(['url', 'download']);
        $this->load->model('Buka_peta');
        $this->load->library(['form_validation', 'session']);
        date_default_timezone_set('Asia/Jakarta');
    }

    public function sertifikasi() {
        $data['judul'] = '';
        $table = $this->db->table_exists('srp2_certified_developers') ? 'srp2_certified_developers' : 'srp2_registrations';
        $status = $table === 'srp2_certified_developers' ? ['status_aktif' => 1] : ['status_verifikasi' => 'Diterima'];
        $data['daftar_pengembang'] = $this->db->select('id, nama_perusahaan')->where($status)->order_by('nama_perusahaan', 'ASC')->get($table)->result();
        $this->render('pages/pengembang/sertifikasi', $data);
    }

    public function syarat() { $this->render('pages/pengembang/syarat', ['judul' => '', 'recaptcha_site_key' => getenv('RECAPTCHA_SITE_KEY') ?: '']); }

    public function daftar() { $this->render('pages/pengembang/daftar', ['judul' => '', 'recaptcha_site_key' => getenv('RECAPTCHA_SITE_KEY') ?: '']); }

    public function formulir() {
        if (!$this->akses_pengembang('Pengembang/formulir')) return;
        $this->render('pages/pengembang/formulir_sertifikasi', ['judul' => '']);
    }

    public function simpan() {
        if (!$this->akses_pengembang('Pengembang/formulir') || $this->input->method(TRUE) !== 'POST') show_404();
        $this->form_validation->set_rules('nama_peserta', 'Nama Peserta', 'required|trim');
        $this->form_validation->set_rules('nik_ktp', 'NIK KTP', 'required|numeric|exact_length[16]|is_unique[srp2_registrations.nik_ktp]');
        $this->form_validation->set_rules('jabatan', 'Jabatan', 'required|in_list[direktur_utama,manajer_proyek,penanggung_jawab,staf_legal]');
        $this->form_validation->set_rules('no_whatsapp', 'Nomor WhatsApp', 'required|numeric|min_length[10]|max_length[15]');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|max_length[100]');
        $this->form_validation->set_rules('nama_perusahaan', 'Nama Perusahaan', 'required|trim');
        $this->form_validation->set_rules('nib', 'NIB Perusahaan', 'required|numeric|exact_length[13]|is_unique[srp2_registrations.nib]');
        $this->form_validation->set_rules('asosiasi', 'Asosiasi', 'required|in_list[rei,himperra,apersi,pi,lainnya]');
        $this->form_validation->set_rules('no_keanggotaan', 'Nomor Keanggotaan', 'required|trim|max_length[50]');
        $this->form_validation->set_rules('alamat_kantor', 'Alamat Kantor', 'required|trim');
        $this->form_validation->set_rules('pernyataan', 'Pernyataan Keaslian', 'required');
        if (!$this->form_validation->run()) { $this->session->set_flashdata('error', validation_errors('<li>', '</li>')); redirect('Pengembang/formulir'); return; }
        $payload = [
            'user_id' => $this->get_user_id(), 'nama_peserta' => strtoupper($this->input->post('nama_peserta', TRUE)),
            'nik_ktp' => $this->input->post('nik_ktp', TRUE), 'jabatan' => $this->input->post('jabatan', TRUE),
            'no_whatsapp' => $this->input->post('no_whatsapp', TRUE), 'email' => strtolower($this->input->post('email', TRUE)),
            'nama_perusahaan' => strtoupper($this->input->post('nama_perusahaan', TRUE)), 'nib' => $this->input->post('nib', TRUE),
            'asosiasi' => $this->input->post('asosiasi', TRUE), 'no_keanggotaan' => $this->input->post('no_keanggotaan', TRUE),
            'alamat_kantor' => $this->input->post('alamat_kantor', TRUE), 'status_verifikasi' => 'Draft'
        ];
        if (!$this->db->insert('srp2_registrations', $payload)) { $this->session->set_flashdata('error', 'Gagal menyimpan data pengajuan. Silakan coba lagi.'); redirect('Pengembang/formulir'); return; }
        redirect('Pengembang/dokumen/' . $this->db->insert_id());
    }

    public function result($id = NULL) {
        if (!$this->akses_pengembang('Pengembang/result/' . (int) $id) || !is_numeric($id)) show_404();
        $data['pendaftar'] = $this->db->get_where('srp2_registrations', ['id' => (int) $id, 'user_id' => $this->get_user_id()])->row();
        if (!$data['pendaftar']) show_404();
        $this->render('pages/pengembang/v_sertifikasi', $data);
    }

    public function profil($id = NULL) {
        if (!is_numeric($id)) show_404();
        if ($this->db->table_exists('srp2_certified_developers')) {
            $data['pengembang'] = $this->db->get_where('srp2_certified_developers', ['id' => (int) $id, 'status_aktif' => 1])->row();
            if ($data['pengembang'] && $this->db->table_exists('srp2_registrations')) {
                $registration = $this->db->get_where('srp2_registrations', ['nama_perusahaan' => $data['pengembang']->nama_perusahaan, 'status_verifikasi' => 'Diterima'])->row();
                if ($registration) foreach (['asosiasi', 'no_keanggotaan', 'alamat_kantor', 'instagram', 'website', 'sosmed_lainnya'] as $field) if (empty($data['pengembang']->$field)) $data['pengembang']->$field = $registration->$field ?? NULL;
            }
        } else $data['pengembang'] = $this->db->get_where('srp2_registrations', ['id' => (int) $id, 'status_verifikasi' => 'Diterima'])->row();
        if (!$data['pengembang']) show_404();
        $this->render('pages/pengembang/profil', $data);
    }

    public function mulai_unggah($document_key = NULL) {
        $document_key = array_key_exists($document_key, $this->dokumen_persyaratan()) ? $document_key : NULL;
        $target = 'Pengembang/mulai_unggah' . ($document_key ? '/' . $document_key : '');
        if (!$this->akses_pengembang($target)) return;
        $registration = $this->db->order_by('id', 'DESC')->get_where('srp2_registrations', ['user_id' => $this->get_user_id()])->row();
        if (!$registration) { $this->session->set_flashdata('error', 'Lengkapi profil pengajuan SRP2 terlebih dahulu.'); redirect('Pengembang/formulir'); return; }
        redirect('Pengembang/dokumen/' . $registration->id . ($document_key ? '#dokumen_' . $document_key : ''));
    }

    public function dokumen($id = NULL) {
        if (!is_numeric($id) || !$this->akses_pengembang('Pengembang/dokumen/' . (int) $id)) show_404();
        $data['pendaftar'] = $this->db->get_where('srp2_registrations', ['id' => (int) $id, 'user_id' => $this->get_user_id()])->row();
        if (!$data['pendaftar']) show_404();
        $data['dokumen'] = $this->dokumen_persyaratan();
        $data['uploaded_count'] = $this->db->where('registration_id', (int) $id)->count_all_results('srp2_documents');
        $this->render('pages/pengembang/dokumen', $data);
    }

    public function simpan_dokumen($id = NULL) {
        if (!is_numeric($id) || !$this->akses_pengembang('Pengembang/dokumen/' . (int) $id) || $this->input->method(TRUE) !== 'POST') show_404();
        $registration = $this->db->get_where('srp2_registrations', ['id' => (int) $id, 'user_id' => $this->get_user_id()])->row();
        if (!$registration || !$this->db->table_exists('srp2_documents')) show_404();
        $path = dirname(FCPATH) . DIRECTORY_SEPARATOR . 'private_uploads' . DIRECTORY_SEPARATOR . 'srp2' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR;
        if (!is_dir($path)) mkdir($path, 0700, TRUE);
        $allowed = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png']; $stored = [];
        foreach ($this->dokumen_persyaratan() as $key => $label) {
            if (empty($_FILES[$key]['name'])) continue;
            $file = $_FILES[$key]; $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)); $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 1048576 || !isset($allowed[$ext]) || $mime !== $allowed[$ext]) { $this->session->set_flashdata('error', 'Berkas ' . $label . ' tidak valid. Gunakan PDF/JPG/PNG maksimal 1 MB.'); redirect('Pengembang/dokumen/' . $id); return; }
            $name = bin2hex(random_bytes(16)) . '.' . $ext;
            if (!move_uploaded_file($file['tmp_name'], $path . $name)) { $this->session->set_flashdata('error', 'Berkas gagal disimpan.'); redirect('Pengembang/dokumen/' . $id); return; }
            $stored[] = ['registration_id' => (int) $id, 'document_key' => $key, 'original_name' => substr(basename($file['name']), 0, 255), 'stored_name' => $name, 'mime_type' => $mime, 'file_size' => (int) $file['size']];
        }
        foreach ($stored as $row) $this->db->replace('srp2_documents', $row);
        $this->session->set_flashdata('success', $stored ? 'Dokumen berhasil diunggah.' : 'Pilih setidaknya satu dokumen untuk diunggah.'); redirect('Pengembang/dokumen/' . $id);
    }

    public function kirim_pengajuan($id = NULL) {
        if (!is_numeric($id) || !$this->akses_pengembang('Pengembang/dokumen/' . (int) $id) || $this->input->method(TRUE) !== 'POST') show_404();
        $registration = $this->db->get_where('srp2_registrations', ['id' => (int) $id, 'user_id' => $this->get_user_id()])->row();
        if (!$registration) show_404();
        $required = count($this->dokumen_persyaratan()); $uploaded = $this->db->where('registration_id', (int) $id)->count_all_results('srp2_documents');
        if ($uploaded < $required) { $this->session->set_flashdata('error', 'Lengkapi seluruh ' . $required . ' dokumen sebelum mengirim pengajuan.'); redirect('Pengembang/dokumen/' . $id); return; }
        $this->db->where('id', (int) $id)->where('user_id', $this->get_user_id())->update('srp2_registrations', ['status_verifikasi' => 'Pending']);
        $this->session->set_flashdata('success', 'Pengajuan SRP2 berhasil dikirim dan sedang menunggu verifikasi.'); redirect('akun');
    }

    private function akses_pengembang($target) {
        if (!$this->is_logged_in()) { $this->session->set_userdata('intended_url', $target); $this->session->set_flashdata('error', 'Silakan masuk atau daftar akun pengembang terlebih dahulu.'); redirect('Auth/login'); return FALSE; }
        if ($this->session->userdata('role') !== 'pengembang') { $this->session->set_flashdata('error', 'Layanan SRP2 hanya tersedia untuk akun dengan peran pengembang.'); redirect('akun'); return FALSE; }
        return TRUE;
    }

    private function dokumen_persyaratan() {
        return ['form_1' => 'Form 1 – Surat Permohonan SRP2', 'form_2a' => 'Form 2.A – Data Administrasi dan Identitas Pengembang', 'form_2b' => 'Form 2.B – Data Administrasi dan Data Pengurus', 'form_3' => 'Form 3 – Pernyataan Bukan ASN', 'form_4' => 'Form 4 – Laporan Keuangan dan Data Kepemilikan', 'form_5' => 'Form 5 – Ketersediaan SDM Penanggung Jawab Teknis', 'form_6' => 'Form 6 – Pengalaman Pekerjaan', 'form_6b' => 'Form 6B – Rekomendasi Perusahaan Baru', 'form_7' => 'Form 7 – Kesanggupan Penyampaian Laporan', 'form_8' => 'Form 8 – Kebenaran Data', 'form_9' => 'Form 9 – Pakta Integritas', 'form_10' => 'Form 10 – BA Verifikasi dan Validasi', 'form_11' => 'Form 11 – BA Klasifikasi dan Kualifikasi', 'form_13' => 'Form 13 – Laporan Pembangunan Perumahan'];
    }
}
