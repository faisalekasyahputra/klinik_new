<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pengembang extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->helper(array('url', 'download'));
        $this->load->model('Buka_peta');
		date_default_timezone_set('Asia/Jakarta');
		$this->load->library(['form_validation', 'session']);
        
      
	}
	public function index()
	{
		
	}

	public function sertifikasi()
	{
		
		$datacontent['judul'] ='';
		$data['content'] = $this->load->view('pages/pengembang/sertifikasi', $datacontent, true);
		$this->load->view('layouts/main',$data);
	}
	public function formulir()
	{
		
		$datacontent['judul'] ='';
		$data['content'] = $this->load->view('pages/pengembang/formulir_sertifikasi', $datacontent, true);
		$this->load->view('layouts/main',$data);
	}
	public function syarat()
	{
		
		$this->load->view('pages/pengembang/syarat');
	}
	public function publikasi()
	{
		
		$datacontent['perumahan']= $this->Buka_peta->frd('sosmed_perumahan',null,null,null,null);
        $data['content'] = $this->load->view('pages/pengembang/publikasi', $datacontent, true);
		$this->load->view('layouts/main',$data);
	}
	public function tambah_publikasi()
	{
		
		$this->load->view('pages/pengembang/tambah_publikasi');
	}
	public function simpan() {
		
		$this->form_validation->set_rules('nama_peserta', 'Nama Peserta', 'required|trim');
      
		$this->form_validation->set_rules('nik_ktp', 'NIK KTP', 'required|numeric|exact_length[16]|is_unique[srp2_registrations.nik_ktp]', [
            'is_unique' => 'NIK KTP ini sudah terdaftar dalam sistem.'
        ]);
		
        $this->form_validation->set_rules('jabatan', 'Jabatan', 'required|in_list[direktur_utama,manajer_proyek,penanggung_jawab,staf_legal]');
        $this->form_validation->set_rules('no_whatsapp', 'Nomor WhatsApp', 'required|numeric|min_length[10]|max_length[15]');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|max_length[100]');
        $this->form_validation->set_rules('nama_perusahaan', 'Nama Perusahaan', 'required|trim');
        $this->form_validation->set_rules('nib', 'NIB Perusahaan', 'required|numeric|exact_length[13]|is_unique[srp2_registrations.nib]', [
            'is_unique' => 'NIB Perusahaan ini sudah terdaftar dalam sistem.'
        ]);
        $this->form_validation->set_rules('asosiasi', 'Asosiasi', 'required|in_list[rei,himperra,apersi,pi,lainnya]');
        $this->form_validation->set_rules('no_keanggotaan', 'Nomor Keanggotaan', 'required|trim|max_length[50]');
        $this->form_validation->set_rules('alamat_kantor', 'Alamat Kantor', 'required|trim');
        $this->form_validation->set_rules('pernyataan', 'Pernyataan Keaslian', 'required', [
            'required' => 'Anda harus menyetujui pernyataan keaslian data.'
        ]);
 		 if ($this->form_validation->run() == FALSE) {
            $this->session->set_flashdata('error', validation_errors('<li>', '</li>'));
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }
       // Jika validasi input teks gagal
        
	
        // 2. KONFIGURASI PROSES UPLOAD FILE (Maksimal 5MB per File)
        $upload_path = '.assets/uploads/';
        if (!is_dir($upload_path)) {
            mkdir($upload_path, 0755, TRUE);
        }

        $config['upload_path']   = $upload_path;
        $config['max_size']      = 5120; // 5 MB dalam hitungan Kilobytes
        $config['encrypt_name']  = TRUE; // Enkripsi nama file agar acak dan aman (menghindari duplikasi)

        $this->load->library('upload', $config);

        // --- UPLOAD FILE 1: SCAN KTP ---
        $config['allowed_types'] = 'pdf|jpg|jpeg|png';
        $this->upload->initialize($config);
        if (!$this->upload->do_upload('file_ktp')) {
            $this->session->set_flashdata('error', 'Gagal Unggah KTP: ' . $this->upload->display_errors('', ''));
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }
        $data_ktp = $this->upload->data();
        $file_ktp = $data_ktp['file_name'];

        // --- UPLOAD FILE 2: KTA ASOSIASI ---
        if (!$this->upload->do_upload('file_kta')) {
            @unlink($upload_path . $file_ktp); // Hapus file KTP yang kadung terupload jika step ini gagal
            $this->session->set_flashdata('error', 'Gagal Unggah KTA: ' . $this->upload->display_errors('', ''));
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }
        $data_kta = $this->upload->data();
        $file_kta = $data_kta['file_name'];

        // --- UPLOAD FILE 3: NIB (KHUSUS PDF) ---
        $config['allowed_types'] = 'pdf';
        $this->upload->initialize($config);
        if (!$this->upload->do_upload('file_nib')) {
            @unlink($upload_path . $file_ktp);
            @unlink($upload_path . $file_kta);
            $this->session->set_flashdata('error', 'Gagal Unggah NIB: ' . $this->upload->display_errors('', ''));
            redirect($_SERVER['HTTP_REFERER']);
            return;
        }
        $data_nib = $this->upload->data();
        $file_nib = $data_nib['file_name'];

        // --- UPLOAD FILE 4: SURAT TUGAS (KONDISIONAL) ---
        $file_surat_tugas = null;
        $jabatan = $this->input->post('jabatan', TRUE);

        // Jika jabatan selain Direktur Utama, Surat Tugas hukumnya WAJIB
        if (in_array($jabatan, ['manajer_proyek', 'penanggung_jawab', 'staf_legal'])) {
            if (!empty($_FILES['file_surat_tugas']['name'])) {
                if (!$this->upload->do_upload('file_surat_tugas')) {
                    // Bersihkan file-file sebelumnya jika gagal di tahap akhir
                    @unlink($upload_path . $file_ktp);
                    @unlink($upload_path . $file_kta);
                    @unlink($upload_path . $file_nib);
                    
                    $this->session->set_flashdata('error', 'Gagal Unggah Surat Tugas: ' . $this->upload->display_errors('', ''));
                    redirect($_SERVER['HTTP_REFERER']);
                    return;
                }
                $data_st = $this->upload->data();
                $file_surat_tugas = $data_st['file_name'];
            } else {
                @unlink($upload_path . $file_ktp);
                @unlink($upload_path . $file_kta);
                @unlink($upload_path . $file_nib);
                
                $this->session->set_flashdata('error', 'Surat Tugas wajib dilampirkan untuk jabatan perwakilan perusahaan!');
                redirect($_SERVER['HTTP_REFERER']);
                return;
            }
        }

        // 3. STRUKTUR DATA SIAP INPUT (Kapitalisasi Otomatis Sesuai Petunjuk)
        $payload = [
            'nama_peserta'      => strtoupper($this->input->post('nama_peserta', TRUE)), // Otomatis Huruf Kapital
            'nik_ktp'           => $this->input->post('nik_ktp', TRUE),
            'jabatan'           => $jabatan,
            'no_whatsapp'       => $this->input->post('no_whatsapp', TRUE),
            'email'             => strtolower($this->input->post('email', TRUE)),
            'nama_perusahaan'   => strtoupper($this->input->post('nama_perusahaan', TRUE)), // Otomatis Huruf Kapital
            'nib'               => $this->input->post('nib', TRUE),
            'asosiasi'          => $this->input->post('asosiasi', TRUE),
            'no_keanggotaan'    => $this->input->post('no_keanggotaan', TRUE),
            'alamat_kantor'     => $this->input->post('alamat_kantor', TRUE),
            'file_ktp'          => $file_ktp,
            'file_kta'          => $file_kta,
            'file_nib'          => $file_nib,
            'file_surat_tugas'  => $file_surat_tugas,
            'status_verifikasi' => 'Pending'
        ];

        // 4. EKSEKUSI PENYIMPANAN KE DATABASE (Query Builder)
        $insert_action = $this->db->insert('srp2_registrations', $payload);
        $last_inserted_id = $this->db->insert_id();

        if ($insert_action) {
            // Set alert sukses dan arahkan langsung ke halaman result/bukti pendaftaran
            $this->session->set_flashdata('success_alert', TRUE);
            redirect('Pengembang/result/' . $last_inserted_id);
        } else {
            // Hapus berkas fisik jika query database gagal mengeksekusi data
            @unlink($upload_path . $file_ktp);
            @unlink($upload_path . $file_kta);
            @unlink($upload_path . $file_nib);
            if ($file_surat_tugas) @unlink($upload_path . $file_surat_tugas);

            $this->session->set_flashdata('error', 'Gagal menyimpan data pendaftaran ke database. Silakan coba lagi.');
            redirect($_SERVER['HTTP_REFERER']);
        }
	}
	public function result($id = NULL) {
        if (empty($id) || !is_numeric($id)) {
            show_404();
        }

        // Ambil data pendaftar dari database berdasarkan ID
        $datacontent['pendaftar'] = $this->db->get_where('srp2_registrations', ['id' => $id])->row();

        // Proteksi jika ID tidak ditemukan di tabel
        if (!$datacontent['pendaftar']) {
            show_404();
        }

        $data['content'] = $this->load->view('pages/pengembang/v_sertifikasi', $datacontent, true);
		$this->load->view('layouts/main',$data);
    }
}
