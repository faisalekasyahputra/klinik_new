<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_Bidang extends Admin_Bidang_Controller {

    public function index()
    {
        $data['title'] = 'Aduan Bidang Saya';
        $data['bidang_nama'] = $this->db->where('kode', $this->my_bidang_kode)->get('bidang')->row('nama');

        // Cari + urut + paginasi semuanya server-side (B7/B8).
        $table = $this->table_state(['created_at', 'nama', 'judul', 'status'], 'created_at');
        $data['base_url'] = 'Admin_Bidang';

        // Scope bidang tetap wajib ikut di query hitung MAUPUN query ambil —
        // pencarian tidak boleh jadi celah keluar dari scope.
        // from() di depan, lalu count_all_results('', FALSE) — kalau tabelnya
        // disebut di kedua tempat, FROM tertulis dua kali dan query gagal.
        $this->db->from('aduan')->where('bidang', $this->my_bidang_kode);
        if ($table['q'] !== '') {
            $this->db->group_start()
                ->like('nama', $table['q'])->or_like('email', $table['q'])
                ->or_like('judul', $table['q'])->or_like('pesan', $table['q'])
                ->group_end();
        }
        // FALSE = pertahankan state query builder supaya filter yang sama
        // dipakai ulang oleh query ambil di bawah.
        $table += $this->paginate_state($this->db->count_all_results('', FALSE));

        $data['rows'] = $this->db->order_by($table['sort'], $table['dir'])
            ->limit($table['per_page'], $table['offset'])
            ->get()->result();
        $data['table'] = $data['pager'] = $table;

        $this->render_scoped_admin('admin_bidang/dashboard', $data);
    }

    /**
     * Sajikan lampiran satu aduan ke admin bidang. Anti-IDOR: WHERE ganda
     * (id + bidang dari sesi) — admin bidang lain tidak bisa membuka lampiran
     * di luar bidangnya walau tahu ID-nya. Berkas dibaca dari private_uploads/
     * (luar webroot), bukan URL publik.
     */
    public function lihat_lampiran($id = NULL)
    {
        if ( ! is_numeric($id)) { show_404(); }

        $row = $this->db->select('lampiran')
            ->where('id', (int) $id)->where('bidang', $this->my_bidang_kode)
            ->get('aduan')->row();
        if ( ! $row || empty($row->lampiran)) { show_404(); }

        $ext = strtolower(pathinfo($row->lampiran, PATHINFO_EXTENSION));
        $mime = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'][$ext] ?? 'application/octet-stream';
        $this->serve_private_file('aduan', (int) $id, $row->lampiran, $mime);
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
        // bidang lain tidak bisa mengubah aduan di luar bidangnya. Cek dulu
        // keberadaannya di scope: affected_rows() saja tidak cukup karena
        // MySQL membalas 0 juga saat nilai barunya sama persis dengan yang
        // tersimpan (resubmit tanpa perubahan) — dulu itu salah dilaporkan
        // sebagai "bukan bidang Anda". Lihat AUDIT_ROLE_ADMIN_SCOPED.md #6.
        $milik_bidang = $this->db->where('id', (int) $id)
            ->where('bidang', $this->my_bidang_kode)
            ->count_all_results('aduan');

        if ($milik_bidang === 0) {
            $this->session->set_flashdata('error', 'Data tidak ditemukan di bidang Anda.');
            redirect('Admin_Bidang');
            return;
        }

        $this->db->where('id', (int) $id)
            ->where('bidang', $this->my_bidang_kode)
            ->update('aduan', [
                'status'        => $status,
                'catatan_admin' => trim((string) $this->input->post('catatan_admin', TRUE)),
                'reviewed_by'   => $this->get_user_id(),
                'reviewed_at'   => date('Y-m-d H:i:s'),
            ]);

        $this->session->set_flashdata('success', 'Status aduan diperbarui.');
        redirect('Admin_Bidang');
    }
}
