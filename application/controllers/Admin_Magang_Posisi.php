<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Posisi/lowongan magang - layar admin. Butir F1 revisi dinas.
 *
 * Dinas minta papan magang menampilkan daftar posisi, bukan nama bidang, dan
 * daftar posisinya sendiri belum pernah diberikan secara resmi - yang disebut
 * di rapat (programmer, arsitek, pengelola data, drafter, content creator)
 * masih berupa contoh dalam kalimat.
 *
 * Karena itu layar ini, bukan seed. Kalau kami menuliskan lima nama itu ke
 * migrasi, tebakan kami akan terlihat seperti keputusan dinas dan mahasiswa
 * melamar posisi yang mungkin tidak ada. Dinas mengisinya sendiri, dan
 * menambah posisi berikutnya tidak perlu menyentuh kode sama sekali.
 *
 * POSISI ADA DI DALAM BIDANG. `kuota` di sini KETERANGAN - yang mengikat
 * pendaftaran tetap `kkn_magang_bidang.kuota`, dan `periksa_slot()` tidak
 * disentuh. Lihat migrasi 038 untuk alasannya.
 */
class Admin_Magang_Posisi extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Aduan_model');   // sumber tunggal daftar bidang
        $this->load->model('kemitraan_slot_model', 'slot');
    }

    public function index()
    {
        $this->load->helper('kualifikasi');
        $data['title']  = 'Magang';   // tab di menu Magang, judulnya ikut induk
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

        /* Keadaan bidang di papan publik, dengan aturan yang sama seperti KemitraanPortal::magang():
           bidang nonaktif di Atur Slot tidak tampil sama sekali, bidang tanpa bulan dibuka tampil
           "Belum dibuka". Tanpa tanda ini petugas mengira posisinya sudah bisa dilamar. */
        $data['tahun_papan'] = $this->slot->tahun_papan();
        $peta = $this->slot->peta_slot($data['tahun_papan'], TRUE);
        $di_papan = array_map(static function ($b) { return $b->kode; }, $this->slot->bidang());
        $data['keadaan_bidang'] = [];
        foreach ($data['rows'] as $r) {
            if ( ! in_array($r->bidang_kode, $di_papan, TRUE)) {
                $data['keadaan_bidang'][$r->bidang_kode] = 'Bidang tidak tampil di papan';
            } elseif (empty($peta[$r->bidang_kode])) {
                $data['keadaan_bidang'][$r->bidang_kode] = 'Bidang belum membuka bulan magang';
            }
        }

        $this->render_admin('admin/magang_posisi/index', $data);
    }

    public function simpan()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); return; }

        $id     = (int) $this->input->post('id');
        $bidang = trim((string) $this->input->post('bidang_kode', TRUE));
        $nama   = trim((string) $this->input->post('nama_posisi', TRUE));
        // Kualifikasi datang sebagai daftar kualifikasi[] (satu kotak per butir) dan disimpan
        // satu per baris. Kotak kosong dibuang; batasnya ditegakkan di sini, bukan hanya di form.
        $this->load->helper('kualifikasi');
        $butir  = array_values(array_filter(array_map(function ($v) {
            return trim(preg_replace('/[\r\n]+/', ' ', (string) $v));
        }, (array) $this->input->post('kualifikasi', TRUE)), 'strlen'));
        if (count($butir) > KUALIFIKASI_MAKS_BUTIR) {
            $this->session->set_flashdata('error', 'Kualifikasi maksimal ' . KUALIFIKASI_MAKS_BUTIR . ' butir.');
            $this->kembali($bidang); return;
        }
        foreach ($butir as $b) {
            if (mb_strlen($b) > KUALIFIKASI_MAKS_KARAKTER) {
                $this->session->set_flashdata('error', 'Tiap kualifikasi maksimal ' . KUALIFIKASI_MAKS_KARAKTER . ' karakter.');
                $this->kembali($bidang); return;
            }
        }
        $ket    = implode("\n", $butir);
        $kuota  = (int) $this->input->post('kuota');
        $urutan = (int) $this->input->post('urutan');

        if ($nama === '' || mb_strlen($nama) > 100) {
            $this->session->set_flashdata('error', 'Nama posisi wajib diisi, maksimal 100 karakter.');
            $this->kembali($bidang); return;
        }
        /* Bidang DIVALIDASI ke tabelnya, bukan ke daftar literal. Sejak migrasi
           030 tabel `bidang` adalah satu-satunya sumber; menyalin daftarnya ke
           sini akan menyimpang diam-diam saat tabelnya berubah. */
        $sah = array_column($this->Aduan_model->daftar_bidang(), 'kode');
        if ( ! in_array($bidang, $sah, TRUE)) {
            $this->session->set_flashdata('error', 'Bidang tidak dikenal.');
            $this->kembali(''); return;
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
            // Sama seperti hapus(): UPDATE ke id yang tidak ada menyentuh nol baris tanpa galat.
            if ( ! $this->db->where('id', $id)->count_all_results('kkn_magang_posisi')) {
                $this->session->set_flashdata('error', 'Posisi tidak ditemukan.');
                $this->kembali($bidang); return;
            }
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
        $this->kembali($bidang);
    }

    public function hapus()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); return; }
        $id = (int) $this->input->post('id');
        $row = $this->db->get_where('kkn_magang_posisi', ['id' => $id])->row();
        if ( ! $row) {
            $this->session->set_flashdata('error', 'Posisi tidak ditemukan.');
            $this->kembali(''); return;
        }
        $this->db->where('id', $id)->delete('kkn_magang_posisi');
        $this->catat_audit('magang_posisi_dihapus', 'Posisi magang "' . $row->nama_posisi . '" dihapus',
            'kkn_magang_posisi', $id, ['bidang' => $row->bidang_kode]);
        $this->session->set_flashdata('success', 'Posisi dihapus.');
        $this->kembali($row->bidang_kode);
    }

    /**
     * Kembali ke layar asal. Modal posisi juga hidup di detail slot tiap bidang
     * (Admin_Kemitraan::slot_bidang); admin yang menyimpan dari sana kembali ke bidang itu,
     * bukan dilempar ke daftar semua posisi. Tujuannya selalu rute sendiri, bukan URL kiriman.
     */
    private function kembali($bidang)
    {
        $tahun = $this->slot->tahun_sah($this->input->post('tahun'));
        if ($this->input->post('asal') === 'slot' && $bidang !== '' && $tahun !== NULL) {
            redirect('Admin_Kemitraan/slot_bidang/' . rawurlencode($bidang) . '/' . $tahun);
        }
        redirect('Admin_Magang_Posisi');
    }
}
