<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_Bidang extends Admin_Bidang_Controller {

    public function index()
    {
        $data['title'] = 'Aduan Bidang Saya';
        $data['bidang_nama'] = $this->db->where('kode', $this->my_bidang_kode)->get('bidang')->row('nama');
        $data['rows'] = $this->db->where('bidang', $this->my_bidang_kode)
            ->order_by('created_at', 'DESC')
            ->get('aduan')->result();

        $this->render_scoped_admin('admin_bidang/dashboard', $data);
    }

    public function update_status($id = NULL)
    {
        if ($this->input->method(TRUE) !== 'POST' || ! is_numeric($id)) { show_404(); }

        $status = $this->input->post('status', TRUE);
        if ( ! in_array($status, ['Baru', 'Diproses', 'Selesai'], TRUE)) {
            $this->session->set_flashdata('error', 'Status tidak valid.');
            redirect('Admin_Bidang');
            return;
        }

        // Anti-IDOR: wajib WHERE bidang juga, bukan cuma id — supaya admin
        // bidang lain tidak bisa mengubah aduan di luar bidangnya.
        $this->db->where('id', (int) $id)
            ->where('bidang', $this->my_bidang_kode)
            ->update('aduan', [
                'status'        => $status,
                'catatan_admin' => trim((string) $this->input->post('catatan_admin', TRUE)),
            ]);

        if ($this->db->affected_rows() > 0) {
            $this->session->set_flashdata('success', 'Status aduan diperbarui.');
        } else {
            $this->session->set_flashdata('error', 'Data tidak ditemukan di bidang Anda.');
        }

        redirect('Admin_Bidang');
    }
}
