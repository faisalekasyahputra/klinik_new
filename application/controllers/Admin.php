<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        
        // Load Admin_model
        $this->load->model('Admin_model');
    }

    public function index()
    {
        $data['title'] = 'Antrean & Validasi';
        // View bersama dengan Admin_Kabkota (yang ter-scope). Fork lama
        // pages/admin/dashboard.php diarsipkan: ikonnya fa-solid padahal shell
        // admin tidak me-load Font Awesome, jadi blank di produksi (B1).
        $data['scope_label'] = 'Semua Wilayah';
        $data['action_url']  = 'Admin/update_status';
        $data['empty_text']  = 'Belum ada antrean yang masuk.';
        $data['base_url']    = 'Admin';

        // NULL = tanpa scope wilayah; superadmin memang berwenang lintas kabupaten.
        $data += $this->antrean_table_data(NULL);

        $this->render_admin('admin/antrean/dashboard', $data);
    }

    public function update_status()
    {
        // Ensure it is a POST request
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            
            // Get data from POST with XSS filtering
            $queue_id = $this->input->post('queue_id', TRUE);
            $status = $this->input->post('status', TRUE);
            $catatan_admin = $this->input->post('catatan_admin', TRUE);

            // Basic validation
            if (!empty($queue_id) && in_array($status, ['approved', 'rejected'])) {
                
                // Update status via model
                // reviewed_by dari sesi (bukan request) — akuntabilitas siapa
                // yang memutuskan, kolom dari migrasi 20260701000011.
                $update_success = $this->Admin_model->update_queue_status($queue_id, $status, $catatan_admin, $this->get_user_id());
                
                if ($update_success) {
                    // Sinkronisasi ke API SIMPERUM BELUM dibangun — dulu pesan di
                    // sini mengklaim "telah disinkronisasi dengan API SIMPERUM"
                    // berdasarkan $mock_api_status = true yang di-hardcode, jadi
                    // admin diberi tahu data sudah konsisten di sistem eksternal
                    // padahal tidak ada request apa pun yang dikirim. Pesan
                    // dijujurkan; jangan kembalikan klaim sinkronisasi sampai
                    // integrasinya benar-benar ada.
                    $this->session->set_flashdata('success', 'Status pengajuan berhasil diubah menjadi '.strtoupper($status).'. Sinkronisasi ke SIMPERUM belum tersedia, perubahan baru tersimpan di sistem ini.');
                } else {
                    $this->session->set_flashdata('error', 'Gagal memperbarui status antrean. Silakan coba lagi.');
                }
            } else {
                $this->session->set_flashdata('error', 'Invalid input data provided.');
            }
        } else {
            $this->session->set_flashdata('error', 'Invalid request method.');
        }

        // Redirect back to Admin dashboard
        redirect('Admin/index');
    }
}
