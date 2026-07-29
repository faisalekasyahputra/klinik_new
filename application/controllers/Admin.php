<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Housing_assessment_model');
    }

    public function index()
    {
        $data['title'] = 'Antrean & Validasi';
        $data['scope_label'] = 'Semua Wilayah';
        $data['action_url']  = 'Admin/update_status';
        $data['empty_text']  = 'Belum ada antrean yang masuk.';
        $data['base_url']    = 'Admin';
        $data += $this->antrean_table_data(NULL);

        $this->render_admin('admin/antrean/dashboard', $data);
    }

    public function update_status()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }

        // S6 — jalur superadmin dulu MELEWATI pembatas laju yang sudah dipasang
        // di Admin_Kabkota::update_status(). Policy `admin_queue_decision` yang
        // sama dipakai di sini, bukan mekanisme kedua (§17 poin 15): dimensi
        // ip+account+object, jadi satu akun yang membanjiri satu antrean tetap
        // tertahan sekalipun ia superadmin.
        $queue_id = (int) $this->input->post('queue_id');
        $rate = $this->rate_limit_consume('admin_queue_decision', [
            'account_id' => (int) $this->get_user_id(),
            'object_id'  => $queue_id,
        ]);
        if (empty($rate['success']) || empty($rate['allowed'])) {
            $this->rate_limit_reject(
                $rate,
                'Terlalu banyak keputusan dalam waktu singkat. Silakan coba lagi sebentar.',
                $this->input->is_ajax_request()
            );
            return;
        }

        $result = $this->Housing_assessment_model->transition_queue(
            $this->input->post('queue_id'),
            $this->input->post('from_status', TRUE),
            $this->input->post('status', TRUE),
            $this->get_user_id(),
            NULL,
            $this->input->post('catatan_admin', TRUE)
        );

        $this->session->set_flashdata(
            $result['success'] ? 'success' : 'error',
            $result['success']
                ? 'Keputusan berhasil disimpan. Sinkronisasi ke SIMPERUM belum tersedia.'
                : $result['message']
        );
        redirect('Admin');
    }

    public function detail($queue_id)
    {
        $data = $this->assessment_detail_data($queue_id, NULL);
        if ( ! $data) { show_404(); return; }
        $data += ['title' => 'Detail Penilaian Warga', 'back_url' => 'Admin', 'action_url' => 'Admin/update_status', 'evidence_url' => 'Admin/evidence'];
        $this->render_admin('admin/antrean/detail', $data);
    }

    public function evidence($queue_id, $file_kind)
    {
        $file = $this->scoped_queue_file($queue_id, $file_kind, NULL);
        if ( ! $file) { show_404(); return; }
        $this->serve_private_file('warga_assessment', $file['storage_assessment_id'], $file['private_path'], $file['mime_type']);
    }

}
