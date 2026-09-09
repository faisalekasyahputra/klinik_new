<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Jejak Audit - layar baca `sys_jejak_audit` (migrasi 20260701000033).
 *
 * SENGAJA HANYA PUNYA index(). Tidak ada hapus, tidak ada ekspor-lalu-bersihkan,
 * tidak ada "tandai sudah dibaca". Jejak audit yang bisa dirapikan lewat UI
 * tidak melindungi siapa pun: orang yang paling ingin barisnya hilang adalah
 * orang yang punya tombolnya. Kalau suatu saat perlu retensi, itu pekerjaan
 * job terjadwal dengan kebijakan tertulis - bukan tombol di sebelah barisnya.
 */
class Admin_Audit extends Admin_Controller {

    public function index()
    {
        $data['title'] = 'Jejak Audit';

        // Migrasi 033 belum jalan = query gagal, dan dengan `db_debug` MATI di
        // production kegagalannya cuma mengembalikan FALSE - `->result()` di
        // bawah lalu fatal dan layarnya putih tanpa sebab. Sebabnya disebut apa
        // adanya. Pola yang sama dipakai MY_Controller::catat_audit().
        if ( ! $this->db->table_exists('sys_jejak_audit')) {
            $this->session->set_flashdata('error',
                'Tabel jejak audit belum ada. Jalankan migrasi 20260701000033 lebih dulu.');
            redirect($this->dashboard_home());
            return;
        }

        // Daftar aksi diambil dari isi tabel, BUKAN konstanta di sini. Nilai
        // `aksi` ditulis oleh MY_Controller::catat_audit() dari puluhan call
        // site yang tersebar; daftar hardcode akan diam-diam menyembunyikan
        // aksi baru dari penyaring - tepat pada aksi yang paling belum dikenal.
        // Dijalankan SEBELUM from() di bawah supaya tidak mengotori state
        // query builder yang dipakai penghitungan halaman.
        $data['aksi_tersedia'] = array_column(
            $this->db->distinct()->select('aksi')->order_by('aksi', 'ASC')
                ->get('sys_jejak_audit')->result(),
            'aksi'
        );

        // 'created_at' default + table_state() memberi dir DESC kecuali diminta
        // 'asc' → terbaru dulu tanpa perlakuan khusus.
        $table = $this->table_state(['created_at', 'aksi', 'actor_email'], 'created_at');
        $data['base_url'] = 'Admin_Audit';

        // Nilai ?aksi= dicocokkan ke daftar yang benar-benar ADA, jadi tidak ada
        // filter yang menghasilkan layar kosong tanpa penjelasan.
        $aksi = trim((string) $this->input->get('aksi', TRUE));
        $data['f_aksi'] = in_array($aksi, $data['aksi_tersedia'], TRUE) ? $aksi : NULL;

        // from() di depan lalu count_all_results('', FALSE) - bukan
        // count_all_results('sys_jejak_audit', FALSE) diikuti get(tabel),
        // keduanya menyetel FROM sehingga jadi "FROM x, x" (error 1066).
        $this->db->from('sys_jejak_audit');
        if ($data['f_aksi'] !== NULL) { $this->db->where('aksi', $data['f_aksi']); }
        if ($table['q'] !== '') {
            $this->db->group_start()
                ->like('ringkasan', $table['q'])
                ->or_like('actor_email', $table['q'])
                ->or_like('aksi', $table['q'])->group_end();
        }
        $table += $this->paginate_state($this->db->count_all_results('', FALSE));

        // `id` sebagai pengurut kedua, searah pengurut pertama. `created_at`
        // hanya berpresisi detik, dan satu tindakan admin bisa menulis beberapa
        // baris dalam detik yang sama - tanpa pemecah seri, urutan baris seri
        // ditentukan MySQL semaunya dan LIMIT/OFFSET bisa mengulang atau
        // melewati baris saat pindah halaman.
        $data['jejak'] = $this->db->order_by($table['sort'], $table['dir'])
            ->order_by('id', $table['dir'])
            ->limit($table['per_page'], $table['offset'])
            ->get()->result();

        $data['table'] = $data['pager'] = $table;
        $this->render_admin('admin/audit/index', $data);
    }
}
