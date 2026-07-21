<?php
defined('BASEPATH') || exit('No direct script access allowed');

class Pengaturan extends MY_Controller {

    public function __construct() {
        parent::__construct();
        if (!$this->is_logged_in()) {
            redirect('Auth/login');
        }
        $this->load->model('User_model');
        $this->load->model('Auth_model');
    }

    public function index() {
        // Get full user details
        $user_id = $this->get_user_id();
        $user = $this->Auth_model->find_by_id($user_id);

        $datacontent['user'] = $user;
        $datacontent['riwayat_pengajuan'] = $this->db
            ->select('ticket_code, status_antrean, created_at')
            ->where('user_id', (int) $user_id)
            ->order_by('created_at', 'DESC')
            ->get('sf_housing_queue')
            ->result();

        if ($this->session->userdata('role') === 'pengembang') {
            $datacontent['pengajuan_sp2'] = $this->db->get_where('srp2_registrations', ['user_id' => $user_id])
                ->order_by('id', 'DESC')->row();
        }

        $data['content'] = $this->load->view('pages/pengaturan/index', $datacontent, true);
        $this->load->view('layouts/main', $data);
    }

    public function update_pengembang_profile() {
        $user_id = $this->get_user_id();

        if ($this->session->userdata('role') !== 'pengembang') {
            show_404();
            return;
        }

        $pengajuan = $this->db->get_where('srp2_registrations', ['user_id' => $user_id])->row();
        if (!$pengajuan) {
            $this->session->set_flashdata('error', 'Data pengajuan sertifikasi tidak ditemukan.');
            redirect('akun');
            return;
        }

        // Simpan teks apa adanya (escape dilakukan sekali saat render lewat htmlspecialchars()
        // di profil.php/pengaturan/index.php, bukan di sini — supaya tidak double-encode).
        $data = [
            'nama_perusahaan' => strtoupper(trim($this->input->post('nama_perusahaan'))),
            'alamat_kantor'   => trim($this->input->post('alamat_kantor')),
            'asosiasi'        => $this->input->post('asosiasi'),
            'no_keanggotaan'  => trim($this->input->post('no_keanggotaan')),
            'instagram'       => trim($this->input->post('instagram')),
            'website'         => trim($this->input->post('website')),
            'sosmed_lainnya'  => trim($this->input->post('sosmed_lainnya')),
        ];

        if (empty($data['nama_perusahaan']) || empty($data['alamat_kantor'])) {
            $this->session->set_flashdata('error', 'Nama Perusahaan dan Alamat Kantor tidak boleh kosong.');
            redirect('akun');
            return;
        }

        if (!in_array($data['asosiasi'], ['rei', 'himperra', 'apersi', 'pi', 'lainnya'], TRUE)) {
            $this->session->set_flashdata('error', 'Asosiasi tidak valid.');
            redirect('akun');
            return;
        }

        // user_id selalu dari sesi, bukan dari input, supaya tidak bisa mengedit data pengembang lain (anti-IDOR)
        $this->db->where('user_id', $user_id);
        $this->db->update('srp2_registrations', $data);

        $this->session->set_flashdata('success', 'Data pengembang berhasil diperbarui!');
        redirect('akun');
    }

    public function update_profile() {
        $user_id = $this->get_user_id();

        $username = html_escape($this->input->post('username'));
        $username = preg_replace('/\s+/', '', strtolower($username));
        $name     = html_escape($this->input->post('name'));
        $phone    = html_escape($this->input->post('phone'));

        if (empty($name)) {
            $this->session->set_flashdata('error', 'Nama Lengkap tidak boleh kosong.');
            redirect('akun');
            return;
        }

        // Check unique username if provided
        if (!empty($username)) {
            $this->db->where('username', $username);
            $this->db->where('id !=', $user_id);
            if ($this->db->count_all_results('usr_users') > 0) {
                $this->session->set_flashdata('error', 'Username sudah digunakan, silakan pilih yang lain.');
                redirect('akun');
                return;
            }
        }

        $data = [
            'name'  => $name,
            'phone' => $phone
        ];

        if (!empty($username)) {
            $data['username'] = $username;
        }

        $this->User_model->update_user($user_id, $data);

        // Update session
        $this->session->set_userdata('name', $name);
        if (!empty($username)) {
            $this->session->set_userdata('username', $username);
        }

        $this->session->set_flashdata('success', 'Profil berhasil diperbarui!');
        redirect('akun');
    }

    public function delete_account() {
        $user_id = $this->get_user_id();
        
        // Ensure POST request
        if ($this->input->method() !== 'post') {
            show_404();
            return;
        }

        $success = $this->User_model->delete_user_account($user_id);

        if ($success) {
            // Destroy session
            $this->session->sess_destroy();
            redirect('Auth/login?msg=account_deleted');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus akun. Silakan coba lagi.');
            redirect('akun');
        }
    }
}
