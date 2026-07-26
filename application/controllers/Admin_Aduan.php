<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Visibilitas superadmin atas SELURUH aduan lintas bidang — menutup
 * AUDIT_ROLE_ADMIN_SCOPED.md temuan #5: sebelumnya tabel `aduan` hanya
 * disentuh Umum (kirim), Admin_Bidang (kelola, ter-scope), dan Pengaturan
 * (baca milik sendiri). Kalau sebuah bidang belum punya admin_bidang
 * ter-assign, aduannya tidak terlihat SIAPA PUN di sisi admin.
 *
 * SENGAJA READ-ONLY: keputusan tetap kewenangan admin bidang masing-masing.
 * Superadmin di sini hanya untuk audit/eskalasi — tidak ada endpoint tulis,
 * jadi tidak ada jalur kedua yang bisa menimpa keputusan admin bidang tanpa
 * jejak (bandingkan masalah dua-jalur-tulis di sf_housing_queue, temuan #3).
 */
class Admin_Aduan extends Admin_Controller {

    public function index()
    {
        $data['title'] = 'Semua Aduan';

        $bidang_filter = $this->input->get('bidang', TRUE);
        $daftar_bidang = $this->db->order_by('nama', 'ASC')->get('bidang')->result();
        $kode_valid = array_column($daftar_bidang, 'kode');
        // Filter dari query string divalidasi ke tabel bidang — bukan langsung
        // dimasukkan ke WHERE.
        $bidang_filter = in_array($bidang_filter, $kode_valid, TRUE) ? $bidang_filter : NULL;

        // Cari + urut + paginasi semuanya server-side (B7/B8).
        $table = $this->table_state(
            ['aduan.created_at', 'aduan.nama', 'aduan.judul', 'aduan.bidang', 'aduan.status'],
            'aduan.created_at'
        );
        $data['base_url'] = 'Admin_Aduan';

        $this->db->from('aduan')->join('usr_users', 'usr_users.id = aduan.reviewed_by', 'left');
        if ($bidang_filter) { $this->db->where('aduan.bidang', $bidang_filter); }
        if ($table['q'] !== '') {
            $this->db->group_start()
                ->like('aduan.nama', $table['q'])->or_like('aduan.email', $table['q'])
                ->or_like('aduan.judul', $table['q'])->or_like('aduan.pesan', $table['q'])
                ->group_end();
        }
        $table += $this->paginate_state($this->db->count_all_results('', FALSE));

        $data['rows'] = $this->db->select('aduan.*, usr_users.name AS nama_petugas')
            ->order_by($table['sort'], $table['dir'])
            ->limit($table['per_page'], $table['offset'])
            ->get()->result();
        $data['table'] = $data['pager'] = $table;

        // Bidang tanpa admin ter-assign: aduannya tidak akan tertangani siapa pun.
        $data['bidang_tanpa_admin'] = [];
        foreach ($daftar_bidang as $b) {
            $ada_admin = $this->db->where(['role' => 'admin_bidang', 'bidang_kode' => $b->kode])
                ->count_all_results('usr_users');
            if ($ada_admin === 0) { $data['bidang_tanpa_admin'][] = $b->nama; }
        }

        $data['daftar_bidang'] = $daftar_bidang;
        $data['bidang_filter'] = $bidang_filter;
        $this->render_admin('admin/aduan/index', $data);
    }

    /**
     * Sajikan lampiran aduan ke superadmin (lintas bidang, sesuai kewenangan).
     * Ber-guard lewat Admin_Controller; berkas dibaca dari private_uploads/,
     * bukan URL publik. Bandingkan Admin_Bidang::lihat_lampiran() yang
     * WHERE-nya ganda karena memang ter-scope.
     */
    public function lihat_lampiran($id = NULL)
    {
        if ( ! is_numeric($id)) { show_404(); }

        $row = $this->db->select('lampiran')->get_where('aduan', ['id' => (int) $id])->row();
        if ( ! $row || empty($row->lampiran)) { show_404(); }

        $ext = strtolower(pathinfo($row->lampiran, PATHINFO_EXTENSION));
        $mime = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'][$ext] ?? 'application/octet-stream';
        $this->serve_private_file('aduan', (int) $id, $row->lampiran, $mime);
    }
}
