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
        $data['title'] = 'Tinjau Antrean'; // = label sidebar
        $data['scope_label'] = 'Semua Wilayah';
        $data['action_url']  = 'Admin/update_status';
        $data['empty_text']  = 'Belum ada antrean yang masuk.';
        $data['base_url']    = 'Admin';
        // Sakelar B2 (config/kebijakan_data.php) hanya untuk admin kab/kota; superadmin melihat identitas asli.
        $data['identitas_utuh'] = TRUE;
        $this->catat_akses_data_pribadi('identitas_warga', 'sf_antrean_pengajuan', 'daftar');
        $data += $this->antrean_table_data(NULL);
        // Ringkasan peringatan keamanan otomatis (poin 10.5); hanya superadmin yang melihatnya.
        $this->load->library('Security_alert');
        $data['peringatan_keamanan'] = $this->security_alert->ringkasan();

        $this->render_admin('admin/antrean/dashboard', $data);
    }

    public function update_status()
    {
        if ($this->input->method(TRUE) !== 'POST') {
            show_404();
            return;
        }

        // S6 - jalur superadmin dulu MELEWATI pembatas laju yang sudah dipasang
        // di Admin_Kabkota::update_status(). Policy `admin_queue_decision` yang
        // sama dipakai di sini, bukan mekanisme kedua (§17 poin 15): dimensi
        // ip+account+object, jadi satu akun yang membanjiri satu antrean tetap
        // tertahan sekalipun ia superadmin.
        $antrean_id = (int) $this->input->post('antrean_id');
        $rate = $this->rate_limit_consume('admin_queue_decision', [
            'account_id' => (int) $this->get_user_id(),
            'object_id'  => $antrean_id,
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
            $this->input->post('antrean_id'),
            $this->input->post('status_awal', TRUE),
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

    public function detail($antrean_id)
    {
        $data = $this->assessment_detail_data($antrean_id, NULL);
        if ( ! $data) { show_404(); return; }
        $data += ['title' => 'Detail Penilaian Warga', 'back_url' => 'Admin', 'action_url' => 'Admin/update_status', 'evidence_url' => 'Admin/evidence'];
        $this->render_admin('admin/antrean/detail', $data);
    }

    public function evidence($antrean_id, $jenis_berkas)
    {
        $file = $this->scoped_queue_file($antrean_id, $jenis_berkas, NULL);
        if ( ! $file) { show_404(); return; }
        $this->serve_private_file('warga_assessment', $file['storage_assessment_id'], $file['path_privat'], $file['mime_type']);
    }

}
