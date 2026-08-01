<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_Kemitraan extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('kemitraan_slot_model', 'slot');
    }

    // =========================================================
    // SLOT MAGANG PER BIDANG
    //
    // Otorisasinya datang dari Admin_Controller, yang menuntut role === 'admin'
    // PERSIS — bukan dari entri `roles` di dashboard_modules.php, yang cuma
    // menentukan menunya dirender atau tidak.
    //
    // Daftar bidangnya TIDAK dikelola di sini: itu struktur organisasi dinas
    // (lima bidang, dikonfirmasi ke dinas 1 Agt 2026), bukan sesuatu yang
    // ditambah atau dihapus lewat modul magang. Yang bisa diatur cuma kuota,
    // aktif/nonaktif, dan bulan mana yang dibuka.
    // =========================================================

    /** Batas tahun yang boleh dibuka dari URL, supaya tidak lahir halaman tak berujung. */
    private function tahun_sah($tahun)
    {
        $tahun = (int) ($tahun ?: date('Y'));
        return ($tahun < 2020 || $tahun > (int) date('Y') + 5) ? NULL : $tahun;
    }

    public function slot($tahun = NULL)
    {
        $tahun = $this->tahun_sah($tahun);
        if ($tahun === NULL) { show_404(); }

        // FALSE: layar ini justru perlu melihat bidang nonaktif, kalau tidak
        // tidak ada cara menyalakannya kembali.
        $bidang = $this->slot->bidang(FALSE);
        $peta   = $this->slot->peta_slot($tahun);
        $terisi = $this->slot->peta_terisi();

        $ringkas = [];
        foreach ($bidang as $b) {
            $bulan = $peta[$b->kode] ?? [];
            ksort($bulan);

            $puncak = 0;
            foreach (array_keys($bulan) as $nomor) {
                $isi = (int) ($terisi[$b->kode][$tahun . '-' . $nomor] ?? 0);
                if ($isi > $puncak) { $puncak = $isi; }
            }

            $ringkas[$b->kode] = [
                'label'  => array_map(function ($s) { return $this->slot->label_rentang($s, TRUE); }, $bulan),
                'puncak' => $puncak,
            ];
        }

        $this->render_admin('admin/kemitraan/slot', [
            'title'          => 'Slot Magang',
            'tahun'          => $tahun,
            'tahun_tersedia' => $this->slot->tahun_tersedia(),
            'bidang'         => $bidang,
            'ringkas'        => $ringkas,
        ]);
    }

    /**
     * DETAIL satu bidang — dua belas bulan, masing-masing dengan rentang
     * tanggalnya dan daftar mahasiswa yang mengisinya.
     *
     * Daftar mahasiswa itu bukan hiasan: tanpa layar ini, angka "2 dari 2"
     * muncul tanpa bisa ditelusuri ke siapa pun, dan hitungan yang tidak bisa
     * ditelusuri akan dihitung ulang manual di sebelahnya.
     */
    public function slot_bidang($kode = NULL, $tahun = NULL)
    {
        $tahun = $this->tahun_sah($tahun);
        if ($tahun === NULL) { show_404(); }

        $bidang = $this->slot->bidang_by_kode($kode);
        if ( ! $bidang) { show_404(); }

        $this->render_admin('admin/kemitraan/slot_bidang', [
            'title'      => 'Slot ' . $bidang->nama,
            'bidang'     => $bidang,
            'tahun'      => $tahun,
            'slot'       => $this->slot->slot_bidang($bidang->kode, $tahun),
            'pendaftar'  => $this->slot->pendaftar_bidang($bidang->kode, $tahun),
            'terisi'     => $this->slot->peta_terisi()[$bidang->kode] ?? [],
            'nama_bulan' => Kemitraan_slot_model::nama_bulan(),
        ]);
    }

    public function simpan_slot_bidang($kode = NULL)
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }

        $bidang = $this->slot->bidang_by_kode($kode);
        if ( ! $bidang) { show_404(); }

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
        if (is_numeric($kuota)) { $this->slot->set_kuota($bidang->kode, $kuota); }

        // Formulir mengirim keadaan LENGKAP dua belas bulan; bulan yang kotak
        // bukanya tidak tercentang tidak terkirim, dan itu memang berarti tutup.
        $berhasil = $this->slot->tulis_ulang_bidang($bidang->kode, $tahun, (array) $this->input->post('bulan'));

        $this->session->set_flashdata(
            $berhasil ? 'success' : 'error',
            $berhasil ? 'Slot ' . $bidang->nama . ' tahun ' . $tahun . ' diperbarui.' : 'Slot gagal disimpan.'
        );
        redirect('Admin_Kemitraan/slot_bidang/' . rawurlencode($bidang->kode) . '/' . $tahun);
    }

    public function ubah_status_bidang($kode = NULL)
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }

        $bidang = $this->slot->bidang_by_kode($kode);
        if ( ! $bidang) { show_404(); }

        $tahun = $this->tahun_sah($this->input->post('tahun')) ?: (int) date('Y');
        $this->slot->set_aktif($bidang->kode, ! (int) $bidang->aktif);

        $this->session->set_flashdata('success', html_escape($bidang->nama) . ' kini '
            . ((int) $bidang->aktif ? 'tidak menerima' : 'menerima') . ' pendaftaran magang.');
        redirect('Admin_Kemitraan/slot/' . $tahun);
    }

    /**
     * Unggah surat balasan bertanda tangan.
     *
     * Sistem TIDAK membuat suratnya. Dokumen resmi yang dikarang perangkat lunak
     * — lengkap dengan kop dan tanda tangan yang tidak pernah dibubuhkan siapa
     * pun — adalah dokumen palsu, apa pun niatnya. Yang diunggah di sini adalah
     * PDF yang sudah ditandatangani pejabat, dan mahasiswa mengunduh berkas itu
     * apa adanya.
     */
    public function unggah_balasan($id = NULL)
    {
        if ($this->input->method(TRUE) !== 'POST' || ! is_numeric($id)) { show_404(); }

        $row = $this->db->get_where('kkn_magang_pendaftaran', ['id' => (int) $id])->row();
        if ( ! $row) { show_404(); }

        if ($row->status !== 'Diterima') {
            $this->session->set_flashdata('error', 'Surat balasan hanya untuk pendaftaran yang sudah diterima.');
            redirect('Admin_Kemitraan/ubah/' . (int) $row->id);
            return;
        }

        $galat = NULL;
        $nama_berkas = $this->store_private_upload('file_surat_balasan', 'kemitraan', (int) $row->id, $galat);
        if ( ! $nama_berkas) {
            $this->session->set_flashdata('error', $galat ?: 'Tidak ada berkas yang diunggah.');
            redirect('Admin_Kemitraan/ubah/' . (int) $row->id);
            return;
        }

        // Berkas lama dibuang supaya tidak menumpuk tanpa pemilik di
        // private_uploads/ — dokumen berisi nama dan periode seseorang.
        if ( ! empty($row->file_surat_balasan)) {
            $lama = $this->private_upload_dir('kemitraan', (int) $row->id) . basename($row->file_surat_balasan);
            if (is_file($lama)) { @unlink($lama); }
        }

        $this->db->where('id', (int) $row->id)
            ->update('kkn_magang_pendaftaran', ['file_surat_balasan' => $nama_berkas]);

        $this->session->set_flashdata('success', 'Surat balasan diunggah. Mahasiswa sudah bisa mengunduhnya.');
        redirect('Admin_Kemitraan/ubah/' . (int) $row->id);
    }

    // =========================================================
    // DAFTAR PENDAFTARAN
    // =========================================================

    public function index()
    {
        $data['title'] = 'Pendaftaran KKN/Magang';

        // Cari + urut + paginasi semuanya server-side (B7/B8).
        $table = $this->table_state([
            'kkn_magang_pendaftaran.created_at', 'usr_users.name',
            'kkn_magang_pendaftaran.instansi_asal', 'kkn_magang_pendaftaran.status',
        ], 'kkn_magang_pendaftaran.created_at');
        $data['base_url'] = 'Admin_Kemitraan';

        // Filter dari query string DIVALIDASI ke daftar yang sah — bukan
        // langsung dimasukkan ke WHERE. Tanpa filter ini, satu-satunya cara
        // memisahkan "yang perlu ditinjau" dari yang sudah selesai adalah
        // membaca seluruh halaman satu per satu.
        $status_sah = ['Diajukan', 'Ditinjau Bidang', 'Diterima', 'Ditolak', 'Dibatalkan'];
        $jenis_sah  = ['kkn', 'magang'];
        $f_status = $this->input->get('status', TRUE);
        $f_jenis  = $this->input->get('jenis', TRUE);
        $f_status = in_array($f_status, $status_sah, TRUE) ? $f_status : NULL;
        $f_jenis  = in_array($f_jenis, $jenis_sah, TRUE) ? $f_jenis : NULL;

        $this->db->from('kkn_magang_pendaftaran')
            ->join('usr_users', 'usr_users.id = kkn_magang_pendaftaran.user_id', 'left');
        if ($f_status) { $this->db->where('kkn_magang_pendaftaran.status', $f_status); }
        if ($f_jenis)  { $this->db->where('kkn_magang_pendaftaran.jenis', $f_jenis); }
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
        $data['status_sah'] = $status_sah;
        $data['jenis_sah']  = $jenis_sah;
        $data['f_status']   = $f_status;
        $data['f_jenis']    = $f_jenis;
        $this->render_admin('admin/kemitraan/index', $data);
    }

    /**
     * Sajikan dokumen pendukung ke superadmin. Ber-guard lewat Admin_Controller,
     * dibaca dari private_uploads/ (luar webroot).
     *
     * @param string $berkas WHITELIST, bukan nama kolom dari URL — menerima nama
     *   kolom mentah berarti mempersilakan siapa pun membaca kolom apa pun.
     */
    public function lihat_dokumen($id = NULL, $berkas = 'surat')
    {
        if ( ! is_numeric($id)) { show_404(); }

        $kolom = [
            'surat'    => 'file_surat_pengantar',
            'proposal' => 'file_proposal',
            'balasan'  => 'file_surat_balasan',
        ][$berkas] ?? NULL;
        if ($kolom === NULL) { show_404(); }

        $row = $this->db->select($kolom)
            ->get_where('kkn_magang_pendaftaran', ['id' => (int) $id])->row();
        if ( ! $row || empty($row->$kolom)) { show_404(); }

        $ext  = strtolower(pathinfo($row->$kolom, PATHINFO_EXTENSION));
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
            'bidang' => $row->jenis === 'magang' ? $this->slot->bidang(FALSE) : [],
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
        $bidang_kode = $row->bidang_kode;
        if ($row->jenis === 'magang') {
            $bidang = $this->slot->bidang_by_kode($divisi_atau_tema);
            if ( ! $bidang) {
                $this->session->set_flashdata('error', 'Bidang tidak dikenal. Pilih dari daftar yang tersedia.');
                redirect('Admin_Kemitraan/ubah/' . (int) $id);
                return;
            }
            $bidang_kode      = $bidang->kode;
            $divisi_atau_tema = $bidang->nama;
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
            'bidang_kode'      => $bidang_kode,
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

        // Keberadaan barisnya diperiksa lebih dulu. Sebelumnya method ini
        // langsung UPDATE: id yang tidak ada menyentuh nol baris lalu tetap
        // melaporkan "Status pendaftaran diperbarui" — pesan sukses untuk
        // sesuatu yang tidak pernah terjadi.
        $row = $this->db->get_where('kkn_magang_pendaftaran', ['id' => (int) $id])->row();
        if ( ! $row) { show_404(); }

        // 'Ditinjau Bidang' adalah keputusan KHAS superadmin: meneruskan surat
        // ke meja kedua. 'Diterima' tetap ada supaya ia bisa mengambil alih
        // kalau bidangnya belum ada peninjaunya — tapi jalur normalnya adalah
        // meneruskan, dan tombolnya di layar memang menawarkan itu lebih dulu.
        $status = $this->input->post('status', TRUE);
        if ( ! in_array($status, ['Ditinjau Bidang', 'Diterima', 'Ditolak'], TRUE)) {
            $this->session->set_flashdata('error', 'Status tidak valid.');
            redirect('Admin_Kemitraan');
            return;
        }

        if ($status === 'Ditinjau Bidang') {
            // Diteruskan ke bidang mana? Kalau divisinya belum ditetapkan, surat
            // ini akan mendarat di meja yang tidak ada. Lebih baik ditahan di
            // sini dengan alasan yang jelas daripada hilang diam-diam.
            // `bidang_kode` kolom sungguhan sejak migrasi 031 — tidak ada lagi
            // pencocokan lewat nama, dan tidak ada lagi pemetaan divisi yang
            // bisa lupa diisi. KKN memang tidak melewati meja kedua.
            if ($row->jenis !== 'magang' || empty($row->bidang_kode)) {
                $this->session->set_flashdata('error', $row->jenis !== 'magang'
                    ? 'Pendaftaran KKN tidak melewati tinjauan bidang — putuskan langsung di sini.'
                    : 'Pendaftaran ini tidak menyebut bidang tujuan, jadi tidak ada yang bisa meninjaunya.');
                redirect('Admin_Kemitraan');
                return;
            }
        }

        // Memproses baris yang SUDAH diputuskan diizinkan — admin berhak
        // berubah pikiran, dan tombolnya memang dirender untuk status apa pun.
        // Yang perlu disadari: menarik 'Dibatalkan' kembali menjadi 'Diterima'
        // membuat baris itu memakan kuota lagi. Itu benar, tapi jangan sampai
        // terjadi tanpa disengaja — karena itu labelnya di layar berbunyi
        // "Ubah Keputusan", bukan "Proses".
        $this->db->where('id', (int) $row->id)->update('kkn_magang_pendaftaran', [
            'status'        => $status,
            'catatan_admin' => trim((string) $this->input->post('catatan_admin', TRUE)),
            'reviewed_by'   => $this->get_user_id(),
            'reviewed_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->session->set_flashdata('success', 'Status pendaftaran diperbarui.');
        redirect('Admin_Kemitraan');
    }
}
