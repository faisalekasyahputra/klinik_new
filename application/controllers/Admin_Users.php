<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_Users extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->config('roles');
        // Superadmin access is already checked in Admin_Controller
    }

    public function index()
    {
        $data['title'] = 'Manajemen Pengguna';
        $data['users'] = $this->db->order_by('created_at', 'DESC')->get('usr_users')->result();
        $data['available_roles'] = $this->config->item('available_roles');
        $data['kabupaten_list'] = $this->db->order_by('nama', 'ASC')->get('kabupaten')->result();
        $data['bidang_list'] = $this->db->order_by('nama', 'ASC')->get('bidang')->result();
        $this->render_admin('admin/users/index', $data);
    }

    /**
     * Ubah role user + scope (kabupaten/bidang) kalau perlu.
     * Superadmin-only (sudah digerbangi Admin_Controller).
     */
    public function update_role()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }

        $id = (int) $this->input->post('id');
        $role = trim((string) $this->input->post('role', TRUE));
        $available_roles = array_keys($this->config->item('available_roles'));

        if ( ! $id || ! in_array($role, $available_roles, TRUE)) {
            $this->session->set_flashdata('error', 'Role tidak valid.');
            redirect('Admin_Users');
            return;
        }

        $payload = ['role' => $role, 'kabupaten_id' => null, 'bidang_kode' => null];

        if (in_array($role, $this->config->item('roles_scoped_kabupaten'), TRUE)) {
            $kabupaten_id = (int) $this->input->post('kabupaten_id');
            if ( ! $kabupaten_id || ! $this->db->where('id', $kabupaten_id)->get('kabupaten')->row()) {
                $this->session->set_flashdata('error', 'Pilih kabupaten/kota untuk role Admin Kabupaten/Kota.');
                redirect('Admin_Users');
                return;
            }
            $payload['kabupaten_id'] = $kabupaten_id;
        }

        if (in_array($role, $this->config->item('roles_scoped_bidang'), TRUE)) {
            $bidang_kode = trim((string) $this->input->post('bidang_kode', TRUE));
            if ( ! $bidang_kode || ! $this->db->where('kode', $bidang_kode)->get('bidang')->row()) {
                $this->session->set_flashdata('error', 'Pilih bidang untuk role Admin Bidang.');
                redirect('Admin_Users');
                return;
            }
            $payload['bidang_kode'] = $bidang_kode;
        }

        $this->db->where('id', $id)->update('usr_users', $payload);
        $this->session->set_flashdata('success', 'Role pengguna diperbarui.');
        redirect('Admin_Users');
    }

    /**
     * Buat akun staff (admin_kabkota/admin_bidang/dst) langsung oleh superadmin —
     * tidak lewat pendaftaran publik Auth::onboarding().
     */
    public function create_staff()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }

        $this->load->library('form_validation');
        $this->form_validation->set_rules('name', 'Nama', 'required|trim|max_length[150]');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|max_length[100]|is_unique[usr_users.email]');
        $this->form_validation->set_rules('password', 'Password', 'required|min_length[8]');
        $this->form_validation->set_rules('role', 'Role', 'required|in_list[' . implode(',', array_keys($this->config->item('available_roles'))) . ']');

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', strip_tags(validation_errors()));
            redirect('Admin_Users');
            return;
        }

        $role = $this->input->post('role', TRUE);
        $payload = [
            'name'               => $this->input->post('name', TRUE),
            'email'              => $this->input->post('email', TRUE),
            'password'           => password_hash($this->input->post('password'), PASSWORD_BCRYPT),
            'role'               => $role,
            'status'             => 'active',
            'profile_completed'  => 1,
            'email_verified_at'  => date('Y-m-d H:i:s'),
            'created_at'         => date('Y-m-d H:i:s'),
        ];

        if (in_array($role, $this->config->item('roles_scoped_kabupaten'), TRUE)) {
            $kabupaten_id = (int) $this->input->post('kabupaten_id');
            if ( ! $kabupaten_id || ! $this->db->where('id', $kabupaten_id)->get('kabupaten')->row()) {
                $this->session->set_flashdata('error', 'Pilih kabupaten/kota untuk role Admin Kabupaten/Kota.');
                redirect('Admin_Users');
                return;
            }
            $payload['kabupaten_id'] = $kabupaten_id;
        }

        if (in_array($role, $this->config->item('roles_scoped_bidang'), TRUE)) {
            $bidang_kode = trim((string) $this->input->post('bidang_kode', TRUE));
            if ( ! $bidang_kode || ! $this->db->where('kode', $bidang_kode)->get('bidang')->row()) {
                $this->session->set_flashdata('error', 'Pilih bidang untuk role Admin Bidang.');
                redirect('Admin_Users');
                return;
            }
            $payload['bidang_kode'] = $bidang_kode;
        }

        $this->db->insert('usr_users', $payload);
        $this->session->set_flashdata('success', 'Akun staff baru berhasil dibuat.');
        redirect('Admin_Users');
    }
}
