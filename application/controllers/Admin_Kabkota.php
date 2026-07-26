<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_Kabkota extends Admin_Kabkota_Controller {

    public function index()
    {
        $data['title'] = 'Antrean Perumahan Wilayah Saya';
        // View bersama admin/antrean/dashboard.php — dipakai juga Admin::index()
        // (superadmin, tanpa scope). Kontrak POST update status sama persis.
        $data['scope_label'] = $this->db->where('id', $this->my_kabupaten_id)->get('kabupaten')->row('nama') ?: 'Wilayah Saya';
        $data['action_url']  = 'Admin_Kabkota/update_status';
        $data['empty_text']  = 'Belum ada antrean di wilayah Anda.';
        $data['base_url']    = 'Admin_Kabkota';

        // Scope wilayah diteruskan EKSPLISIT sebagai argumen (bukan lewat state
        // query builder yang tersimpan) — pencarian tidak boleh jadi celah
        // melihat antrean kabupaten lain.
        $data += $this->antrean_table_data($this->my_kabupaten_id);

        $this->render_scoped_admin('admin/antrean/dashboard', $data);
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
        // kabupaten lain tidak bisa mengubah antrean di luar wilayahnya. Cek dulu
        // keberadaannya di scope: affected_rows() saja tidak cukup karena MySQL
        // membalas 0 juga saat nilai barunya sama persis dengan yang tersimpan
        // (resubmit tanpa perubahan) — dulu itu salah dilaporkan sebagai "bukan
        // wilayah Anda". Lihat AUDIT_ROLE_ADMIN_SCOPED.md #6.
        $milik_wilayah = $this->db->where('id', $queue_id)
            ->where('kabupaten_id', $this->my_kabupaten_id)
            ->count_all_results('sf_housing_queue');

        if ($milik_wilayah === 0) {
            $this->session->set_flashdata('error', 'Data tidak ditemukan di wilayah Anda.');
            redirect('Admin_Kabkota');
            return;
        }

        $this->db->where('id', $queue_id)
            ->where('kabupaten_id', $this->my_kabupaten_id)
            ->update('sf_housing_queue', [
                'status_antrean' => $status,
                'catatan_admin'  => $catatan_admin,
                'reviewed_by'    => $this->get_user_id(),
                'reviewed_at'    => date('Y-m-d H:i:s'),
            ]);

        $this->session->set_flashdata('success', 'Status pengajuan berhasil diubah menjadi ' . strtoupper($status) . '.');
        redirect('Admin_Kabkota');
    }
}
