<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_Srp2 extends Admin_Controller {
    public function index() {
        $data['title'] = 'Daftar Pengembang SRP2';
        $data['rows'] = $this->db->order_by('nama_perusahaan', 'ASC')->get('srp2_certified_developers')->result();
        $this->render_admin('admin/srp2/index', $data);
    }

    public function save() {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }
        $id = (int) $this->input->post('id');
        $name = strtoupper(trim((string) $this->input->post('nama_perusahaan', TRUE)));
        if ($name === '' || strlen($name) > 180) { $this->session->set_flashdata('error', 'Nama perusahaan wajib diisi.'); redirect('Admin_Srp2'); return; }
        $urls = [];
        foreach (['website', 'instagram', 'sosmed_lainnya'] as $field) {
            $value = trim((string) $this->input->post($field, TRUE));
            if ($value !== '' && (!filter_var($value, FILTER_VALIDATE_URL) || !in_array(strtolower(parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], TRUE))) { $this->session->set_flashdata('error', 'Link ' . $field . ' harus menggunakan URL http/https yang valid.'); redirect('Admin_Srp2'); return; }
            $urls[$field] = $value ?: null;
        }
        $payload = array_merge(['nama_perusahaan' => $name, 'status_aktif' => $this->input->post('status_aktif') ? 1 : 0, 'alamat_kantor' => trim((string) $this->input->post('alamat_kantor', TRUE))], $urls);
        if ($id) { $this->db->where('id', $id)->update('srp2_certified_developers', $payload); } else { $this->db->insert('srp2_certified_developers', $payload); }
        $this->session->set_flashdata('success', 'Daftar pengembang diperbarui.'); redirect('Admin_Srp2');
    }

    public function delete($id = NULL) {
        if ($this->input->method(TRUE) !== 'POST' || !is_numeric($id)) { show_404(); }
        $this->db->where('id', (int) $id)->delete('srp2_certified_developers');
        $this->session->set_flashdata('success', 'Pengembang dihapus dari daftar.'); redirect('Admin_Srp2');
    }
}
