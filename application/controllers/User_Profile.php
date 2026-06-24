<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class User_Profile extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Auth_model');
        $this->load->model('User_model');
    }

    public function index()
    {
        $user_id = $this->get_user_id();
        $user = $this->Auth_model->find_by_id($user_id);
        
        $data['title'] = 'Profil Saya';
        $data['user'] = $user;
        $this->render_admin('admin/profile/index', $data);
    }
    
    public function update() {
        $user_id = $this->get_user_id();

        $name     = html_escape($this->input->post('name'));
        $phone    = html_escape($this->input->post('phone'));

        if (empty($name)) {
            $this->session->set_flashdata('error', 'Nama Lengkap tidak boleh kosong.');
            redirect('User_Profile');
            return;
        }

        $data = [
            'name'  => $name,
            'phone' => $phone
        ];

        // Only update password if provided
        $password = $this->input->post('password');
        if (!empty($password)) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $this->User_model->update_user($user_id, $data);

        // Update session
        $this->session->set_userdata('name', $name);

        $this->session->set_flashdata('success', 'Profil berhasil diperbarui!');
        redirect('User_Profile');
    }
}
