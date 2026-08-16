<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Serah Terima PSU (Prasarana, Sarana, Utilitas) perumahan - CRUD admin.
 *
 * Permintaan user 14 Agt 2026: mengaktifkan kartu "PSU" di beranda (dulu
 * "Segera Hadir", pages/home/awal.php) dengan halaman publik yang
 * menampilkan nama perumahan, status serah terima, nama pengembang,
 * asosiasi, kabupaten/kota.
 *
 * Pola CRUD mengikuti Admin_Srp2 (tabel server-side B8: cari/urut/paginasi,
 * lihat ANCHOR_DASHBOARD_TERPADU.md) - bukan Admin_Magang_Posisi yang lebih
 * sederhana, karena daftar ini diharapkan tumbuh sama seperti direktori SRP2
 * (67 baris dan terus bertambah), bukan tinggal beberapa baris statis.
 *
 * `nama_pengembang` teks bebas, `pengembang_id` pranala OPSIONAL ke direktori
 * SRP2 - alasan lengkap ada di migrasi 043. `asosiasi` divalidasi ke
 * srp2_asosiasi (migrasi 042), SATU daftar untuk seluruh aplikasi.
 */
class Admin_Psu extends Admin_Controller {

    const TABEL = 'psu_serah_terima';

    /* Daftar status VALID untuk validasi server (array biasa, bukan
       psu_daftar_status() langsung, supaya in_array(...,TRUE) tidak
       bergantung urutan/bentuk array asosiatif). Label yang dibaca orang
       ada di helper psu_label_status() - SATU sumber dgn halaman publik,
       lihat komentar panjangnya di application/helpers/psu_helper.php. */
    const STATUS_SERAH_TERIMA = ['belum_diserahkan', 'proses_verifikasi', 'sudah_diserahkan'];

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('admin_table');
        $this->load->helper('srp2');
        $this->load->helper('psu');
    }

    public function index()
    {
        $data['title'] = 'Serah Terima PSU';

        $table = $this->table_state(['nama_perumahan', 'nama_pengembang', 'status_serah_terima', 'created_at'], 'created_at');
        $data['base_url'] = 'Admin_Psu';

        $this->db->from(self::TABEL);
        if ($table['q'] !== '') {
            $this->db->group_start()
                ->like('nama_perumahan', $table['q'])->or_like('nama_pengembang', $table['q'])
                ->group_end();
        }
        $table += $this->paginate_state($this->db->count_all_results('', FALSE));

        $data['rows'] = $this->db->order_by($table['sort'], $table['dir'])
            ->limit($table['per_page'], $table['offset'])
            ->get()->result();
        $data['table'] = $data['pager'] = $table;

        $data['kabupaten']  = $this->db->select('id, nama')->order_by('nama', 'ASC')->get('kabupaten')->result();
        $data['asosiasi']   = srp2_daftar_asosiasi(TRUE);
        $data['pengembang'] = $this->db->select('id, nama_perusahaan')->where('status_aktif', 1)
            ->order_by('nama_perusahaan', 'ASC')->get('srp2_certified_developers')->result();

        $this->render_admin('admin/psu/index', $data);
    }

    public function simpan()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); return; }

        $id              = (int) $this->input->post('id');
        $nama_perumahan  = trim((string) $this->input->post('nama_perumahan', TRUE));
        $nama_pengembang = trim((string) $this->input->post('nama_pengembang', TRUE));
        $keterangan      = trim((string) $this->input->post('keterangan', TRUE));

        if ($nama_perumahan === '' || mb_strlen($nama_perumahan) > 180) {
            $this->session->set_flashdata('error', 'Nama perumahan wajib diisi, maksimal 180 karakter.');
            redirect('Admin_Psu'); return;
        }
        if ($nama_pengembang === '' || mb_strlen($nama_pengembang) > 180) {
            $this->session->set_flashdata('error', 'Nama pengembang wajib diisi, maksimal 180 karakter.');
            redirect('Admin_Psu'); return;
        }
        if (mb_strlen($keterangan) > 255) {
            $this->session->set_flashdata('error', 'Keterangan maksimal 255 karakter.');
            redirect('Admin_Psu'); return;
        }

        $status = (string) $this->input->post('status_serah_terima', TRUE);
        if ( ! in_array($status, self::STATUS_SERAH_TERIMA, TRUE)) {
            $this->session->set_flashdata('error', 'Status serah terima tidak dikenal.');
            redirect('Admin_Psu'); return;
        }

        /* Kabupaten DIVALIDASI KE TABEL, bukan sekadar dicek angka - pola
           sama dengan Admin_Srp2::save(). Id yang tidak ada akan lolos
           is_numeric lalu menghasilkan baris berwilayah hantu. */
        $kabupaten_id = (int) $this->input->post('kabupaten_id');
        if ($kabupaten_id !== 0 && ! $this->db->where('id', $kabupaten_id)->count_all_results('kabupaten')) {
            $this->session->set_flashdata('error', 'Kabupaten/kota tidak dikenal.');
            redirect('Admin_Psu'); return;
        }

        // Asosiasi divalidasi ke srp2_daftar_asosiasi(TRUE) - TRUE supaya
        // baris lama yang asosiasinya sudah dinonaktifkan admin tetap bisa
        // disimpan ulang tanpa dipaksa ganti (pola sama dgn Admin_Srp2::save()
        // untuk kolom yang sama).
        $asosiasi = trim((string) $this->input->post('asosiasi', TRUE));
        if ($asosiasi !== '' && ! array_key_exists($asosiasi, srp2_daftar_asosiasi(TRUE))) {
            $this->session->set_flashdata('error', 'Asosiasi tidak dikenal.');
            redirect('Admin_Psu'); return;
        }

        /* Pranala pengembang OPSIONAL - divalidasi ke tabel kalau diisi,
           TIDAK WAJIB diisi (lihat komentar migrasi 043: proyek dengan
           pengembang belum/tidak bersertifikat SRP2 tetap harus bisa
           dicatat). Beda pola dari kabupaten_id di atas: 0/kosong di sini
           BUKAN kesalahan, itu memang keadaan sahnya. */
        $pengembang_id = (int) $this->input->post('pengembang_id');
        if ($pengembang_id !== 0 && ! $this->db->where('id', $pengembang_id)
            ->count_all_results('srp2_certified_developers')) {
            $this->session->set_flashdata('error', 'Pengembang di direktori SRP2 tidak dikenal.');
            redirect('Admin_Psu'); return;
        }

        $tanggal = trim((string) $this->input->post('tanggal_serah_terima', TRUE));
        if ($tanggal !== '' && ! DateTime::createFromFormat('!Y-m-d', $tanggal)) {
            $this->session->set_flashdata('error', 'Tanggal serah terima tidak valid.');
            redirect('Admin_Psu'); return;
        }

        $payload = [
            'nama_perumahan'       => $nama_perumahan,
            'nama_pengembang'      => $nama_pengembang,
            'pengembang_id'        => $pengembang_id ?: NULL,
            'asosiasi'             => $asosiasi === '' ? NULL : $asosiasi,
            'kabupaten_id'         => $kabupaten_id ?: NULL,
            'status_serah_terima'  => $status,
            'tanggal_serah_terima' => $tanggal === '' ? NULL : $tanggal,
            'keterangan'           => $keterangan === '' ? NULL : $keterangan,
            'status_aktif'         => $this->input->post('status_aktif') ? 1 : 0,
            'updated_at'           => date('Y-m-d H:i:s'),
        ];

        if ($id > 0) {
            $this->db->where('id', $id)->update(self::TABEL, $payload);
            $this->catat_audit('psu_diubah', 'Data PSU "' . $nama_perumahan . '" diperbarui',
                self::TABEL, $id, ['status' => $status]);
            $pesan = 'Data PSU diperbarui.';
        } else {
            $payload['created_at'] = $payload['updated_at'];
            $this->db->insert(self::TABEL, $payload);
            $this->catat_audit('psu_ditambah', 'Data PSU "' . $nama_perumahan . '" ditambahkan',
                self::TABEL, $this->db->insert_id(), ['status' => $status]);
            $pesan = 'Data PSU ditambahkan.';
        }

        $this->session->set_flashdata('success', $pesan);
        redirect('Admin_Psu');
    }

    public function hapus()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); return; }
        $id  = (int) $this->input->post('id');
        $row = $this->db->get_where(self::TABEL, ['id' => $id])->row();
        if ( ! $row) {
            $this->session->set_flashdata('error', 'Data PSU tidak ditemukan.');
            redirect('Admin_Psu'); return;
        }
        $this->db->where('id', $id)->delete(self::TABEL);
        $this->catat_audit('psu_dihapus', 'Data PSU "' . $row->nama_perumahan . '" dihapus',
            self::TABEL, $id, ['pengembang' => $row->nama_pengembang]);
        $this->session->set_flashdata('success', 'Data PSU "' . $row->nama_perumahan . '" dihapus.');
        redirect('Admin_Psu');
    }
}
