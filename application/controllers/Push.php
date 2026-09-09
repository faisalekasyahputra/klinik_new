<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Push extends MY_Controller {

    private $allowed = FALSE;

    public function __construct()
    {
        parent::__construct();
        $this->allowed = $this->session->userdata('is_logged')
            && in_array($this->session->userdata('role'), ['admin', 'admin_kabkota', 'admin_bidang'], TRUE);
        if ($this->allowed) {
            $this->load->library('web_push_service');
            $this->load->model('Push_subscription_model', 'push_subscriptions');
        }
    }

    public function config()
    {
        if ( ! $this->guard('GET')) { return; }
        $this->json(['success' => TRUE, 'enabled' => $this->web_push_service->configured(),
            'publicKey' => $this->web_push_service->public_key()]);
    }

    public function subscribe()
    {
        if ( ! $this->guard('POST')) { return; }
        $decoded = json_decode((string) $this->input->post('subscription', FALSE), TRUE);
        if ( ! is_array($decoded)) { $this->json(['success' => FALSE, 'message' => 'Data perangkat tidak valid.'], 422); return; }
        $result = $this->push_subscriptions->simpan((int) $this->session->userdata('user_id'), $decoded,
            $this->input->user_agent());
        $this->json($result, empty($result['success']) ? 422 : 200);
    }

    public function unsubscribe()
    {
        if ( ! $this->guard('POST')) { return; }
        $endpoint = trim((string) $this->input->post('endpoint', FALSE));
        if ($endpoint === '') { $this->json(['success' => FALSE, 'message' => 'Endpoint tidak ada.'], 422); return; }
        $this->push_subscriptions->nonaktifkan_milik((int) $this->session->userdata('user_id'), $endpoint);
        $this->json(['success' => TRUE, 'message' => 'Notifikasi perangkat dinonaktifkan.']);
    }

    private function guard($method)
    {
        if ($this->input->method(TRUE) !== $method) { show_404(); return FALSE; }
        if ( ! $this->allowed) {
            $this->json(['success' => FALSE, 'message' => 'Akses ditolak.'], 403);
            return FALSE;
        }
        return TRUE;
    }

    private function json(array $payload, $status = 200)
    {
        $this->output->set_status_header((int) $status)->set_content_type('application/json')
            ->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
