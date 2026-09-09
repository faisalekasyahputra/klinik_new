<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Meja janji temu konsultasi - sisi petugas dari mekanisme yang selama ini
 * hanya dijanjikan UI (`Umum/forum` berjudul "Konsultasi Terjadwal" dengan tiga
 * kartu alur dan nol mekanisme di belakangnya).
 *
 * SUPERADMIN, bukan admin bidang. Forum tidak punya scope apa pun - tidak ada
 * kolom bidang maupun kabupaten di `forum_diskusi` - jadi tidak ada dasar untuk
 * membagi mejanya. Memaksakan pembagian berarti mengarang atribut yang tidak
 * ada di datanya.
 *
 * SENGAJA memakai `Admin_Controller`, BUKAN `_check_admin()` gaya lama yang
 * masih beredar di beberapa controller: gerbang lama itu menerima campuran
 * peran ('admin', 'staff', 'Petugas Disperakim') yang tidak satu pun terdaftar
 * sebagai peran resmi di `usr_users.role` selain 'admin'.
 *
 * Aturan perpindahan keadaan TIDAK ditulis di sini - dibaca dari
 * `Janji_temu_model::ALUR`, satu tabel yang sama dengan yang dipakai sisi warga.
 */
class Admin_Konsultasi extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Janji_temu_model');
    }

    public function index()
    {
        $data['title'] = 'Janji Temu Konsultasi';

        $status_sah = $this->Janji_temu_model->status_sah();
        $status_filter = $this->input->get('status', TRUE);
        $status_filter = in_array($status_filter, $status_sah, TRUE) ? $status_filter : NULL;

        $table = $this->table_state(
            ['jt.created_at', 'jt.status', 'jt.jadwal_mulai', 'd.judul_topik'],
            'jt.created_at'
        );
        $data['base_url'] = 'Admin_Konsultasi';

        $this->db->from('forum_janji_temu jt')
            ->join('forum_diskusi d', 'd.id_diskusi = jt.id_diskusi', 'left')
            ->join('usr_users u', 'u.id = jt.user_id', 'left');
        if ($status_filter) { $this->db->where('jt.status', $status_filter); }
        if ($table['q'] !== '') {
            $this->db->group_start()
                ->like('d.judul_topik', $table['q'])->or_like('u.name', $table['q'])
                ->or_like('jt.alasan', $table['q'])
                ->group_end();
        }
        $table += $this->paginate_state($this->db->count_all_results('', FALSE));

        $data['rows'] = $this->db
            ->select('jt.*, d.judul_topik, d.kategori, u.name AS nama_pemohon, u.email AS email_pemohon')
            ->order_by($table['sort'], $table['dir'])
            ->limit($table['per_page'], $table['offset'])
            ->get()->result();
        $data['table'] = $data['pager'] = $table;

        $data['status_sah']    = $status_sah;
        $data['status_filter'] = $status_filter;
        $data['jml_baru'] = (int) $this->db->where('status', 'diajukan')
            ->count_all_results('forum_janji_temu');

        $this->render_admin('admin/konsultasi/index', $data);
    }

    /**
     * Tawarkan waktu & tempat. `diajukan` -> `ditawarkan`.
     *
     * Jadwalnya wajib DI MASA DEPAN. Bukan kerapian: tawaran bertanggal kemarin
     * akan disetujui warga, lalu muncul di daftar sebagai agenda yang sudah
     * lewat sebelum disepakati - dan tidak ada satu pun layar yang bisa
     * membedakannya dari pertemuan yang benar-benar terlewat.
     */
    public function tawarkan($id = NULL)
    {
        $row = $this->sasaran($id, 'ditawarkan');
        if ( ! $row) { return; }

        $jadwal = trim((string) $this->input->post('jadwal_mulai', TRUE));
        $ts = $jadwal !== '' ? strtotime($jadwal) : FALSE;
        if ($ts === FALSE || $ts <= time()) {
            $this->session->set_flashdata('error', 'Isi waktu konsultasi yang valid dan masih di masa depan.');
            redirect('Admin_Konsultasi');
            return;
        }

        $lokasi = trim((string) $this->input->post('lokasi', TRUE));
        if ($lokasi === '') {
            $this->session->set_flashdata('error', 'Lokasi wajib diisi - warga perlu tahu harus datang ke mana.');
            redirect('Admin_Konsultasi');
            return;
        }

        $this->jalankan($row, 'ditawarkan', [
            'jadwal_mulai'  => date('Y-m-d H:i:s', $ts),
            'lokasi'        => $lokasi,
            'catatan_admin' => trim((string) $this->input->post('catatan_admin', TRUE)) ?: NULL,
        ], 'Tawaran jadwal terkirim ke pemohon.');
    }

    /** Tolak. Catatan WAJIB - penolakan tanpa sebab tidak bisa ditindaklanjuti siapa pun. */
    public function tolak($id = NULL)
    {
        $row = $this->sasaran($id, 'ditolak');
        if ( ! $row) { return; }

        $catatan = trim((string) $this->input->post('catatan_admin', TRUE));
        if ($catatan === '') {
            $this->session->set_flashdata('error', 'Alasan penolakan wajib diisi.');
            redirect('Admin_Konsultasi');
            return;
        }

        $this->jalankan($row, 'ditolak', ['catatan_admin' => $catatan], 'Pengajuan ditolak.');
    }

    /** Tandai pertemuannya sudah berlangsung. `disetujui` -> `selesai`. */
    public function selesai($id = NULL)
    {
        $row = $this->sasaran($id, 'selesai');
        if ( ! $row) { return; }

        $this->jalankan($row, 'selesai', [
            'catatan_admin' => trim((string) $this->input->post('catatan_admin', TRUE)) ?: $row->catatan_admin,
        ], 'Janji temu ditandai selesai.');
    }

    // ------------------------------------------------------------------

    /**
     * Ambil barisnya + pastikan transisinya SAH untuk peran admin, atau
     * alihkan dengan sebab yang jelas. Mengembalikan NULL kalau tidak boleh.
     *
     * Keabsahan dibaca dari `Janji_temu_model::ALUR`, jadi menambah keadaan
     * baru kelak tidak perlu menyentuh tiga method di atas.
     */
    private function sasaran($id, $ke)
    {
        if ($this->input->method(TRUE) !== 'POST' || ! is_numeric($id)) { show_404(); }

        $row = $this->Janji_temu_model->ambil((int) $id);
        if ( ! $row) {
            $this->session->set_flashdata('error', 'Pengajuan tidak ditemukan.');
            redirect('Admin_Konsultasi');
            return NULL;
        }

        if ( ! $this->Janji_temu_model->boleh($row->status, $ke, 'admin')) {
            $this->catat_audit('janji_temu_transisi_ditolak',
                'Transisi ' . $row->status . ' -> ' . $ke . ' ditolak untuk janji temu #' . $row->id,
                'forum_janji_temu', $row->id, ['dari' => $row->status, 'ke' => $ke]);
            $this->session->set_flashdata('error',
                'Pengajuan ini berstatus "' . $row->status . '" - tindakan itu tidak berlaku.');
            redirect('Admin_Konsultasi');
            return NULL;
        }

        return $row;
    }

    private function jalankan($row, $ke, array $set, $pesan)
    {
        $ok = $this->Janji_temu_model->transisi($row->id, $row->status, $ke,
            $set + ['reviewed_by' => $this->get_user_id()]);

        if ( ! $ok) {
            // Barisnya ada dan transisinya sah saat dibaca - kalau UPDATE tetap
            // nol baris, keadaannya berubah di antara dua query itu (warga
            // membatalkan lebih dulu). Dilaporkan apa adanya, bukan "berhasil".
            $this->session->set_flashdata('error', 'Keadaan pengajuan berubah barusan. Muat ulang halaman.');
            redirect('Admin_Konsultasi');
            return;
        }

        $this->catat_audit('janji_temu_' . $ke,
            'Janji temu #' . $row->id . ' menjadi ' . $ke,
            'forum_janji_temu', $row->id,
            ['dari' => $row->status, 'ke' => $ke] + array_intersect_key($set, ['jadwal_mulai' => 1, 'lokasi' => 1]));

        $this->session->set_flashdata('success', $pesan);
        redirect('Admin_Konsultasi');
    }
}
