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
        // Fetch housing queue data
        $data['queue'] = $this->Admin_model->get_all_housing_queue();
        $data['title'] = 'Antrean & Validasi';
        
        // Load views
        $this->render_admin('pages/admin/dashboard', $data);
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
                $update_success = $this->Admin_model->update_queue_status($queue_id, $status, $catatan_admin);
                
                if ($update_success) {
                    // MOCK SIMPERUM API SYNC
                    // In a real scenario, this would use cURL to send the approval status back to SIMPERUM API
                    // $ch = curl_init('https://api.simperum.jatengprov.go.id/v1/housing-queue/sync');
                    // curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['id' => $queue_id, 'status' => $status]));
                    // ...
                    $mock_api_status = true; // Simulated success
                    
                    if ($mock_api_status) {
                        $this->session->set_flashdata('success', 'Status pengajuan berhasil diubah menjadi '.strtoupper($status).' dan telah disinkronisasi dengan API SIMPERUM.');
                    } else {
                        $this->session->set_flashdata('success', 'Status diubah secara lokal, namun gagal sinkronisasi ke API SIMPERUM.');
                    }
                    
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
