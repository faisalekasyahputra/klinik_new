<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_Kemitraan extends Admin_Controller {

    public function index()
    {
        $data['title'] = 'Pendaftaran KKN/Magang';

        // Cari + urut + paginasi semuanya server-side (B7/B8).
        $table = $this->table_state([
            'kkn_magang_pendaftaran.created_at', 'usr_users.name',
            'kkn_magang_pendaftaran.instansi_asal', 'kkn_magang_pendaftaran.status',
        ], 'kkn_magang_pendaftaran.created_at');
        $data['base_url'] = 'Admin_Kemitraan';

        $this->db->from('kkn_magang_pendaftaran')
            ->join('usr_users', 'usr_users.id = kkn_magang_pendaftaran.user_id', 'left');
        if ($table['q'] !== '') {
            $this->db->group_start()
                ->like('usr_users.name', $table['q'])->or_like('usr_users.email', $table['q'])
                ->or_like('kkn_magang_pendaftaran.instansi_asal', $table['q'])
                ->or_like('kkn_magang_pendaftaran.divisi_atau_tema', $table['q'])
                ->group_end();
        }
        $table += $this->paginate_state($this->db->count_all_results('', FALSE));

        $data['rows'] = $this->db->select('kkn_magang_pendaftaran.*, usr_users.name AS nama_mahasiswa, usr_users.email AS email_mahasiswa')
            ->order_by($table['sort'], $table['dir'])
            ->limit($table['per_page'], $table['offset'])
            ->get()->result();
        $data['table'] = $data['pager'] = $table;
        $this->render_admin('admin/kemitraan/index', $data);
    }

    /**
     * Sajikan surat pengantar satu pendaftaran ke admin. Ber-guard lewat
     * Admin_Controller, baca dari private_uploads/ (luar webroot).
     * Menutup AUDIT_ROLE_MAHASISWA.md temuan #4: dulu admin memutuskan
     * terima/tolak tanpa bisa membuka dokumen pendukung sama sekali.
     */
    /**
     * @param string $berkas 'surat' (bawaan, kompatibel dengan tautan lama) atau
     *                       'proposal'. WHITELIST, bukan nama kolom dari URL —
     *                       menerima nama kolom mentah berarti mempersilakan
     *                       siapa pun membaca kolom apa pun lewat query string.
     */
    public function lihat_dokumen($id = NULL, $berkas = 'surat')
    {
        if ( ! is_numeric($id)) { show_404(); }

        $kolom = ['surat' => 'file_surat_pengantar', 'proposal' => 'file_proposal'][$berkas] ?? NULL;
        if ($kolom === NULL) { show_404(); }

        $row = $this->db->select($kolom)
            ->get_where('kkn_magang_pendaftaran', ['id' => (int) $id])->row();
        if ( ! $row || empty($row->$kolom)) { show_404(); }

        $ext = strtolower(pathinfo($row->$kolom, PATHINFO_EXTENSION));
        $mime = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'][$ext] ?? 'application/octet-stream';
        $this->serve_private_file('kemitraan', (int) $id, $row->$kolom, $mime);
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
            'reviewed_by'   => $this->get_user_id(),
            'reviewed_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->session->set_flashdata('success', 'Status pendaftaran diperbarui.');
        redirect('Admin_Kemitraan');
    }
}
