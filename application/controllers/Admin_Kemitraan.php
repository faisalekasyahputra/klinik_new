<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_Kemitraan extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('kemitraan_slot_model', 'slot');
    }

    // =========================================================
    // SLOT MAGANG
    //
    // Otorisasinya datang dari Admin_Controller, yang menuntut role === 'admin'
    // PERSIS — bukan dari entri `roles` di dashboard_modules.php, yang cuma
    // menentukan menunya dirender atau tidak. Superadmin memegang daftar divisi
    // sekaligus buka-tutup slot; tujuh divisi ini unit kerja internal yang tidak
    // beririsan dengan lima `bidang` di DB, jadi admin_bidang tidak bisa dipakai
    // apa adanya. Keputusan user 1 Agt 2026.
    // =========================================================

    /** Batas tahun yang boleh dibuka dari URL, supaya tidak lahir halaman tak berujung. */
    private function tahun_sah($tahun)
    {
        $tahun = (int) ($tahun ?: date('Y'));
        return ($tahun < 2020 || $tahun > (int) date('Y') + 5) ? NULL : $tahun;
    }

    /**
     * DAFTAR divisi — satu baris per divisi, bukan matriks 7x12.
     *
     * Bentuk lama menaruh 84 kotak centang di satu layar dan menuntut admin
     * memahami seluruh tahun sekaligus. Pengaturannya sekarang pindah ke layar
     * detail per divisi; layar ini hanya menjawab "divisi apa saja, statusnya
     * bagaimana, bulan mana yang terbuka".
     */
    public function slot($tahun = NULL)
    {
        $tahun = $this->tahun_sah($tahun);
        if ($tahun === NULL) { show_404(); }

        // FALSE: layar ini justru perlu melihat divisi nonaktif, kalau tidak
        // tidak ada cara menyalakannya kembali.
        $divisi = $this->slot->divisi(FALSE);
        $peta   = $this->slot->peta_slot($tahun);
        $terisi = $this->slot->peta_terisi();

        $ringkas = [];
        foreach ($divisi as $d) {
            $bulan = $peta[(int) $d->id] ?? [];
            ksort($bulan);

            $puncak = 0;
            foreach (array_keys($bulan) as $nomor) {
                $isi = (int) ($terisi[$d->nama][$tahun . '-' . $nomor] ?? 0);
                if ($isi > $puncak) { $puncak = $isi; }
            }

            $ringkas[(int) $d->id] = [
                'label'  => array_map(function ($s) { return $this->slot->label_rentang($s, TRUE); }, $bulan),
                'puncak' => $puncak,
            ];
        }

        $this->render_admin('admin/kemitraan/slot', [
            'title'          => 'Slot Magang',
            'tahun'          => $tahun,
            'tahun_tersedia' => $this->slot->tahun_tersedia(),
            'divisi'         => $divisi,
            'ringkas'        => $ringkas,
        ]);
    }

    /**
     * DETAIL satu divisi — dua belas bulan, masing-masing dengan rentang
     * tanggalnya dan daftar mahasiswa yang mengisinya.
     *
     * Daftar mahasiswa itu bukan hiasan: sebelum ada layar ini, angka "2 dari 2"
     * muncul tanpa bisa ditelusuri ke siapa pun, dan hitungan yang tidak bisa
     * ditelusuri adalah hitungan yang akan dihitung ulang manual di sebelahnya.
     */
    public function slot_divisi($id = NULL, $tahun = NULL)
    {
        if ( ! is_numeric($id)) { show_404(); }
        $tahun = $this->tahun_sah($tahun);
        if ($tahun === NULL) { show_404(); }

        $divisi = $this->slot->divisi_by_id($id);
        if ( ! $divisi) { show_404(); }

        $this->render_admin('admin/kemitraan/slot_divisi', [
            'title'      => 'Slot ' . $divisi->nama,
            'divisi'     => $divisi,
            'tahun'      => $tahun,
            'slot'       => $this->slot->slot_divisi($divisi->id, $tahun),
            'pendaftar'  => $this->slot->pendaftar_divisi($divisi->nama, $tahun),
            'terisi'     => $this->slot->peta_terisi()[$divisi->nama] ?? [],
            'nama_bulan' => Kemitraan_slot_model::nama_bulan(),
        ]);
    }

    public function simpan_slot_divisi($id = NULL)
    {
        if ($this->input->method(TRUE) !== 'POST' || ! is_numeric($id)) { show_404(); }

        $divisi = $this->slot->divisi_by_id($id);
        if ( ! $divisi) { show_404(); }

        $tahun = $this->tahun_sah($this->input->post('tahun'));
        if ($tahun === NULL) {
            $this->session->set_flashdata('error', 'Tahun tidak valid.');
            redirect('Admin_Kemitraan/slot');
            return;
        }

        // Kuota ikut satu tombol dengan bulannya. Dua tombol simpan pada satu
        // layar berarti admin bisa mengubah angka lalu kehilangan rentangnya,
        // dan tidak ada cara menebak mana yang ia maksud.
        $kuota = $this->input->post('kuota');
        if (is_numeric($kuota)) { $this->slot->set_kuota($divisi->id, $kuota); }

        // Formulir mengirim keadaan LENGKAP dua belas bulan; bulan yang kotak
        // bukanya tidak tercentang tidak terkirim, dan itu memang berarti tutup.
        $berhasil = $this->slot->tulis_ulang_divisi($divisi->id, $tahun, (array) $this->input->post('bulan'));

        $this->session->set_flashdata(
            $berhasil ? 'success' : 'error',
            $berhasil ? 'Slot ' . $divisi->nama . ' tahun ' . $tahun . ' diperbarui.' : 'Slot gagal disimpan.'
        );
        redirect('Admin_Kemitraan/slot_divisi/' . (int) $divisi->id . '/' . $tahun);
    }

    public function tambah_divisi()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }

        $nama  = (string) $this->input->post('nama', TRUE);
        $tahun = (int) $this->input->post('tahun') ?: (int) date('Y');

        if ($this->slot->tambah_divisi($nama)) {
            $this->session->set_flashdata('success', 'Divisi ditambahkan. Slotnya masih tertutup semua.');
        } else {
            $this->session->set_flashdata('error', 'Nama divisi kosong atau sudah ada.');
        }
        redirect('Admin_Kemitraan/slot/' . $tahun);
    }

    public function ganti_nama_divisi($id = NULL)
    {
        if ($this->input->method(TRUE) !== 'POST' || ! is_numeric($id)) { show_404(); }

        $tahun = $this->tahun_sah($this->input->post('tahun')) ?: (int) date('Y');
        $nama  = (string) $this->input->post('nama', TRUE);

        if ($this->slot->ganti_nama_divisi($id, $nama)) {
            $this->session->set_flashdata('success', 'Nama divisi diperbarui, termasuk pada pendaftaran yang menunjuk ke sana.');
        } else {
            $this->session->set_flashdata('error', 'Nama kosong, tidak berubah, atau sudah dipakai divisi lain.');
        }
        redirect('Admin_Kemitraan/slot_divisi/' . (int) $id . '/' . $tahun);
    }

    public function hapus_divisi($id = NULL)
    {
        if ($this->input->method(TRUE) !== 'POST' || ! is_numeric($id)) { show_404(); }

        $tahun = $this->tahun_sah($this->input->post('tahun')) ?: (int) date('Y');
        $hasil = $this->slot->hapus_divisi($id);

        if ($hasil === TRUE) {
            $this->session->set_flashdata('success', 'Divisi dihapus.');
            redirect('Admin_Kemitraan/slot/' . $tahun);
            return;
        }

        $this->session->set_flashdata('error', $hasil);
        redirect('Admin_Kemitraan/slot_divisi/' . (int) $id . '/' . $tahun);
    }

    public function ubah_status_divisi($id = NULL)
    {
        if ($this->input->method(TRUE) !== 'POST' || ! is_numeric($id)) { show_404(); }

        $divisi = $this->slot->divisi_by_id($id);
        if ( ! $divisi) { show_404(); }

        $tahun = (int) $this->input->post('tahun') ?: (int) date('Y');
        $this->slot->ubah_status_divisi($divisi->id, ! (int) $divisi->aktif);

        $this->session->set_flashdata('success', 'Divisi ' . html_escape($divisi->nama) . ' kini '
            . ((int) $divisi->aktif ? 'nonaktif' : 'aktif') . '.');
        redirect('Admin_Kemitraan/slot/' . $tahun);
    }

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

    // =========================================================
    // SUNTING PENDAFTARAN
    // =========================================================

    public function ubah($id = NULL)
    {
        if ( ! is_numeric($id)) { show_404(); }

        $row = $this->db->select('kkn_magang_pendaftaran.*, usr_users.name AS nama_mahasiswa, usr_users.email AS email_mahasiswa')
            ->from('kkn_magang_pendaftaran')
            ->join('usr_users', 'usr_users.id = kkn_magang_pendaftaran.user_id', 'left')
            ->where('kkn_magang_pendaftaran.id', (int) $id)
            ->get()->row();
        if ( ! $row) { show_404(); }

        $this->render_admin('admin/kemitraan/ubah', [
            'title'  => 'Ubah Pendaftaran',
            'row'    => $row,
            // Divisi hanya relevan untuk magang; di KKN kolom yang sama berisi
            // tema kegiatan yang memang teks bebas.
            'divisi' => $row->jenis === 'magang' ? $this->slot->divisi(FALSE) : [],
        ]);
    }

    public function simpan_ubah($id = NULL)
    {
        if ($this->input->method(TRUE) !== 'POST' || ! is_numeric($id)) { show_404(); }

        $row = $this->db->get_where('kkn_magang_pendaftaran', ['id' => (int) $id])->row();
        if ( ! $row) { show_404(); }

        $this->load->library('form_validation');
        if ($this->form_validation->run('kemitraan_pendaftaran') === FALSE) {
            $this->session->set_flashdata('error', validation_errors('<li>', '</li>'));
            redirect('Admin_Kemitraan/ubah/' . (int) $id);
            return;
        }

        $mulai   = $this->input->post('periode_mulai', TRUE);
        $selesai = $this->input->post('periode_selesai', TRUE);
        if ($selesai < $mulai) {
            $this->session->set_flashdata('error', 'Periode selesai tidak boleh mendahului periode mulai.');
            redirect('Admin_Kemitraan/ubah/' . (int) $id);
            return;
        }

        // Batas panjang berlaku juga di sini. Admin boleh melampaui KUOTA, tapi
        // periode 79 tahun bukan kewenangan — ia membuat setiap render halaman
        // menelusuri puluhan ribu hari.
        if ($this->slot->periode_terlalu_panjang($mulai, $selesai)) {
            $this->session->set_flashdata('error', 'Periode terlalu panjang. Maksimal '
                . Kemitraan_slot_model::BATAS_HARI . ' hari.');
            redirect('Admin_Kemitraan/ubah/' . (int) $id);
            return;
        }

        $divisi_atau_tema = $this->input->post('divisi_atau_tema', TRUE);

        // Divisi tetap harus NYATA — kalau tidak, papan slot dan hitungan
        // terisinya menunjuk ke nama yang tidak pernah ada, dan angkanya
        // berhenti berarti apa pun. Yang TIDAK ditegakkan di sini adalah
        // kuotanya: admin berwenang menempatkan orang ke bulan yang penuh, dan
        // papan tetap jujur menampilkan 3 dari 2 apa adanya. Keputusan user
        // 1 Agt 2026.
        if ($row->jenis === 'magang') {
            $divisi = $this->slot->divisi_by_nama($divisi_atau_tema);
            if ( ! $divisi) {
                $this->session->set_flashdata('error', 'Divisi tidak dikenal. Pilih dari daftar yang tersedia.');
                redirect('Admin_Kemitraan/ubah/' . (int) $id);
                return;
            }
            $divisi_atau_tema = $divisi->nama;
        }

        $this->db->where('id', (int) $id)->update('kkn_magang_pendaftaran', [
            'nim'              => $this->input->post('nim', TRUE),
            'tempat_lahir'     => $this->input->post('tempat_lahir', TRUE),
            'tanggal_lahir'    => $this->input->post('tanggal_lahir', TRUE),
            'semester'         => (int) $this->input->post('semester', TRUE),
            'jurusan'          => $this->input->post('jurusan', TRUE),
            'instansi_asal'    => $this->input->post('instansi_asal', TRUE),
            'no_hp'            => $this->input->post('no_hp', TRUE),
            'divisi_atau_tema' => $divisi_atau_tema,
            'periode_mulai'    => $mulai,
            'periode_selesai'  => $selesai,
        ]);

        $this->session->set_flashdata('success', 'Data pendaftaran diperbarui.');
        redirect('Admin_Kemitraan');
    }

    /**
     * Hapus satu pendaftaran, berikut berkas pendukungnya.
     *
     * Ada demi kelengkapan CRUD, tapi ini SATU-SATUNYA aksi di modul ini yang
     * tidak bisa dibatalkan — "Ditolak" sudah cukup untuk hampir semua kasus,
     * dan ia meninggalkan jejak yang bisa dibaca. Hapus disediakan untuk yang
     * memang tidak boleh tersisa: kiriman ganda, atau data yang salah orang.
     *
     * Berkasnya ikut dihapus. Membiarkan KTP dan surat pengantar tergeletak di
     * private_uploads/ setelah barisnya lenyap berarti menyimpan dokumen
     * kependudukan tanpa satu pun catatan tentang milik siapa.
     */
    public function hapus($id = NULL)
    {
        if ($this->input->method(TRUE) !== 'POST' || ! is_numeric($id)) { show_404(); }

        $row = $this->db->get_where('kkn_magang_pendaftaran', ['id' => (int) $id])->row();
        if ( ! $row) { show_404(); }

        // private_uploads_dir() sudah berakhiran pemisah — sama seperti dipakai
        // serve_private_file(), jadi jangan tambahkan garis miring lagi.
        $dir = $this->private_upload_dir('kemitraan', (int) $row->id);
        foreach ([$row->file_surat_pengantar, $row->file_proposal] as $berkas) {
            if (empty($berkas)) { continue; }
            $path = $dir . basename((string) $berkas);
            if (is_file($path)) { @unlink($path); }
        }
        if (is_dir($dir) && ! glob($dir . '*')) { @rmdir($dir); }

        $this->db->delete('kkn_magang_pendaftaran', ['id' => (int) $row->id]);

        $this->session->set_flashdata('success', 'Pendaftaran dihapus beserta berkasnya.');
        redirect('Admin_Kemitraan');
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
