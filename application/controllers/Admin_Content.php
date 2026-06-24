<?php
defined('BASEPATH') || exit('No direct script access allowed');

class Admin_Content extends Admin_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Setting_model');
    }

    public function index() {
        $data['title'] = 'Manajemen Konten Website';
        
        // Load all settings
        $data['settings'] = $this->Setting_model->get_all();

        $this->render_admin('admin/content/index', $data);
    }

    public function update() {
        $post_data = $this->input->post();
        
        // Handle file upload for hero background
        if (!empty($_FILES['hero_background']['name'])) {
            $config['upload_path']   = './assets/img/hero/';
            $config['allowed_types'] = 'gif|jpg|png|jpeg|webp';
            $config['max_size']      = 5120; // 5MB
            $config['encrypt_name']  = TRUE;

            $this->load->library('upload', $config);

            if ($this->upload->do_upload('hero_background')) {
                $upload_data = $this->upload->data();
                $post_data['hero_background'] = 'assets/img/hero/' . $upload_data['file_name'];
            } else {
                $this->session->set_flashdata('error', $this->upload->display_errors('',''));
                redirect('Admin_Content');
                return;
            }
        }

        // Update settings in database
        if (!empty($post_data)) {
            $this->Setting_model->update_batch_settings($post_data);
            $this->session->set_flashdata('success', 'Konten website berhasil diperbarui.');
        }

        redirect('Admin_Content');
    }
}
