<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_Kabkota extends Admin_Kabkota_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Housing_assessment_model');
    }

    public function index()
    {
        $data['title'] = 'Antrean Perumahan Wilayah Saya';
        $data['scope_label'] = $this->db->where('id', $this->my_kabupaten_id)
            ->get('kabupaten')->row('nama') ?: 'Wilayah Saya';
        $data['action_url'] = 'Admin_Kabkota/update_status';
        $data['empty_text'] = 'Belum ada antrean di wilayah Anda.';
        $data['base_url'] = 'Admin_Kabkota';
        $data += $this->antrean_table_data($this->my_kabupaten_id);

        $this->render_scoped_admin('admin/antrean/dashboard', $data);
    }

    public function update_status()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }

        $queue_id = (int) $this->input->post('queue_id');
        $rate = $this->rate_limit_consume('admin_queue_decision', [
            'account_id' => (int) $this->get_user_id(),
            'object_id' => $queue_id,
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
            $queue_id,
            $this->input->post('from_status', TRUE),
            $this->input->post('status', TRUE),
            $this->get_user_id(),
            $this->my_kabupaten_id,
            $this->input->post('catatan_admin', TRUE)
        );

        $this->session->set_flashdata(
            $result['success'] ? 'success' : 'error',
            $result['success'] ? 'Keputusan pengajuan berhasil disimpan.' : $result['message']
        );
        redirect('Admin_Kabkota');
    }

    public function detail($queue_id)
    {
        $data = $this->assessment_detail_data($queue_id, $this->my_kabupaten_id);
        if ( ! $data) { show_404(); return; }
        $data += ['title' => 'Detail Penilaian Warga', 'back_url' => 'Admin_Kabkota', 'action_url' => 'Admin_Kabkota/update_status', 'evidence_url' => 'Admin_Kabkota/evidence'];
        $this->render_scoped_admin('admin/antrean/detail', $data);
    }

    public function evidence($queue_id, $file_kind)
    {
        $file = $this->scoped_queue_file($queue_id, $file_kind, $this->my_kabupaten_id);
        if ( ! $file) { show_404(); return; }
        $this->serve_private_file('warga_assessment', $file['storage_assessment_id'], $file['private_path'], $file['mime_type']);
    }

}
