<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Posisi/lowongan magang — layar admin. Butir F1 revisi dinas.
 *
 * Dinas minta papan magang menampilkan daftar posisi, bukan nama bidang, dan
 * daftar posisinya sendiri belum pernah diberikan secara resmi — yang disebut
 * di rapat (programmer, arsitek, pengelola data, drafter, content creator)
 * masih berupa contoh dalam kalimat.
 *
 * Karena itu layar ini, bukan seed. Kalau kami menuliskan lima nama itu ke
 * migrasi, tebakan kami akan terlihat seperti keputusan dinas dan mahasiswa
 * melamar posisi yang mungkin tidak ada. Dinas mengisinya sendiri, dan
 * menambah posisi berikutnya tidak perlu menyentuh kode sama sekali.
 *
 * POSISI ADA DI DALAM BIDANG. `kuota` di sini KETERANGAN — yang mengikat
 * pendaftaran tetap `kkn_magang_bidang.kuota`, dan `periksa_slot()` tidak
 * disentuh. Lihat migrasi 038 untuk alasannya.
 */
class Admin_Magang_Posisi extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Aduan_model');   // sumber tunggal daftar bidang
    }

    public function index()
    {
        $data['title']  = 'Posisi Magang';
        $data['bidang'] = $this->Aduan_model->daftar_bidang();
        $data['rows']   = $this->db->select('p.*, b.nama AS nama_bidang')
            ->from('kkn_magang_posisi p')
            ->join('bidang b', 'b.kode = p.bidang_kode', 'left')
            ->order_by('b.nama', 'ASC')->order_by('p.urutan', 'ASC')->order_by('p.nama_posisi', 'ASC')
            ->get()->result();

        /* Dipakai modal pengingat: berapa lama daftar ini tidak disentuh.
           NULL = belum pernah ada isinya sama sekali. */
        $data['terakhir_diubah'] = $this->db->select_max('updated_at', 'terakhir')
            ->get('kkn_magang_posisi')->row('terakhir');
        $data['jumlah_aktif'] = (int) $this->db->where('aktif', 1)->count_all_results('kkn_magang_posisi');

        $this->render_admin('admin/magang_posisi/index', $data);
    }

    public function simpan()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); return; }

        $id     = (int) $this->input->post('id');
        $bidang = trim((string) $this->input->post('bidang_kode', TRUE));
        $nama   = trim((string) $this->input->post('nama_posisi', TRUE));
        $ket    = trim((string) $this->input->post('keterangan', TRUE));
        $kuota  = (int) $this->input->post('kuota');
        $urutan = (int) $this->input->post('urutan');

        if ($nama === '' || mb_strlen($nama) > 100) {
            $this->session->set_flashdata('error', 'Nama posisi wajib diisi, maksimal 100 karakter.');
            redirect('Admin_Magang_Posisi'); return;
        }
        if (mb_strlen($ket) > 255) {
            $this->session->set_flashdata('error', 'Keterangan maksimal 255 karakter.');
            redirect('Admin_Magang_Posisi'); return;
        }
        /* Bidang DIVALIDASI ke tabelnya, bukan ke daftar literal. Sejak migrasi
           030 tabel `bidang` adalah satu-satunya sumber; menyalin daftarnya ke
           sini akan menyimpang diam-diam saat tabelnya berubah. */
        $sah = array_column($this->Aduan_model->daftar_bidang(), 'kode');
        if ( ! in_array($bidang, $sah, TRUE)) {
            $this->session->set_flashdata('error', 'Bidang tidak dikenal.');
            redirect('Admin_Magang_Posisi'); return;
        }
        $kuota  = max(0, min(99, $kuota));
        $urutan = max(0, min(999, $urutan));

        $payload = [
            'bidang_kode' => $bidang,
            'nama_posisi' => $nama,
            'keterangan'  => $ket === '' ? NULL : $ket,
            'kuota'       => $kuota,
            'aktif'       => $this->input->post('aktif') ? 1 : 0,
            'urutan'      => $urutan,
            'updated_at'  => date('Y-m-d H:i:s'),
        ];

        if ($id > 0) {
            $this->db->where('id', $id)->update('kkn_magang_posisi', $payload);
            $this->catat_audit('magang_posisi_diubah', 'Posisi magang "' . $nama . '" diperbarui',
                'kkn_magang_posisi', $id, ['bidang' => $bidang, 'kuota' => $kuota]);
            $pesan = 'Posisi diperbarui.';
        } else {
            $payload['created_at'] = $payload['updated_at'];
            $this->db->insert('kkn_magang_posisi', $payload);
            $this->catat_audit('magang_posisi_ditambah', 'Posisi magang "' . $nama . '" ditambahkan',
                'kkn_magang_posisi', $this->db->insert_id(), ['bidang' => $bidang, 'kuota' => $kuota]);
            $pesan = 'Posisi ditambahkan.';
        }

        $this->session->set_flashdata('success', $pesan);
        redirect('Admin_Magang_Posisi');
    }

    public function hapus()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); return; }
        $id = (int) $this->input->post('id');
        $row = $this->db->get_where('kkn_magang_posisi', ['id' => $id])->row();
        if ( ! $row) {
            $this->session->set_flashdata('error', 'Posisi tidak ditemukan.');
            redirect('Admin_Magang_Posisi'); return;
        }
        $this->db->where('id', $id)->delete('kkn_magang_posisi');
        $this->catat_audit('magang_posisi_dihapus', 'Posisi magang "' . $row->nama_posisi . '" dihapus',
            'kkn_magang_posisi', $id, ['bidang' => $row->bidang_kode]);
        $this->session->set_flashdata('success', 'Posisi dihapus.');
        redirect('Admin_Magang_Posisi');
    }
}
