<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_Kabkota extends Admin_Kabkota_Controller {

    public function index()
    {
        $data['title'] = 'Antrean Perumahan Wilayah Saya';
        $data['kabupaten_nama'] = $this->db->where('id', $this->my_kabupaten_id)->get('kabupaten')->row('nama');
        $data['queue'] = $this->db->select('sf_housing_queue.*, sf_programs.nama_program')
            ->from('sf_housing_queue')
            ->join('sf_programs', 'sf_housing_queue.program_id = sf_programs.id', 'left')
            ->where('sf_housing_queue.kabupaten_id', $this->my_kabupaten_id)
            ->order_by('sf_housing_queue.created_at', 'DESC')
            ->limit(1000)
            ->get()->result();

        $this->render_scoped_admin('admin_kabkota/dashboard', $data);
    }

    public function update_status()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }

        $queue_id = (int) $this->input->post('queue_id');
        $status = $this->input->post('status', TRUE);
        $catatan_admin = $this->input->post('catatan_admin', TRUE);

        if ( ! $queue_id || ! in_array($status, ['approved', 'rejected'], TRUE)) {
            $this->session->set_flashdata('error', 'Data tidak valid.');
            redirect('Admin_Kabkota');
            return;
        }

        // Anti-IDOR: wajib WHERE kabupaten_id juga, bukan cuma id — supaya admin
        // kabupaten lain tidak bisa mengubah antrean di luar wilayahnya.
        $this->db->where('id', $queue_id)
            ->where('kabupaten_id', $this->my_kabupaten_id)
            ->update('sf_housing_queue', [
                'status_antrean' => $status,
                'catatan_admin'  => $catatan_admin,
            ]);

        if ($this->db->affected_rows() > 0) {
            $this->session->set_flashdata('success', 'Status pengajuan berhasil diubah menjadi ' . strtoupper($status) . '.');
        } else {
            $this->session->set_flashdata('error', 'Data tidak ditemukan di wilayah Anda.');
        }

        redirect('Admin_Kabkota');
    }
}
