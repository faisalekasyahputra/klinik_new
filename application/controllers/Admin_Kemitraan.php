<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_Kemitraan extends Admin_Controller {

    public function index()
    {
        $data['title'] = 'Pendaftaran KKN/Magang';
        $data['rows'] = $this->db->select('kkn_magang_pendaftaran.*, usr_users.name AS nama_mahasiswa, usr_users.email AS email_mahasiswa')
            ->from('kkn_magang_pendaftaran')
            ->join('usr_users', 'usr_users.id = kkn_magang_pendaftaran.user_id', 'left')
            ->order_by('kkn_magang_pendaftaran.created_at', 'DESC')
            ->get()->result();
        $this->render_admin('admin/kemitraan/index', $data);
    }

    public function proses($id = NULL)
    {
        if ($this->input->method(TRUE) !== 'POST' || ! is_numeric($id)) { show_404(); }

        $status = $this->input->post('status', TRUE);
        if ( ! in_array($status, ['Diterima', 'Ditolak'], TRUE)) {
            $this->session->set_flashdata('error', 'Status tidak valid.');
            redirect('Admin_Kemitraan');
            return;
        }

        $this->db->where('id', (int) $id)->update('kkn_magang_pendaftaran', [
            'status'        => $status,
            'catatan_admin' => trim((string) $this->input->post('catatan_admin', TRUE)),
        ]);

        $this->session->set_flashdata('success', 'Status pendaftaran diperbarui.');
        redirect('Admin_Kemitraan');
    }
}
