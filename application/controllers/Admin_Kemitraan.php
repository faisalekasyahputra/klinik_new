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
    // PERSIS - bukan dari entri `roles` di dashboard_modules.php, yang cuma
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
        // Bawaannya BUKAN date('Y') buta. Cacat yang sama sudah diperbaiki di
        // papan publik (KemitraanPortal::magang) 2 Agt 2026, tapi tertinggal di
        // sisi admin: dengan 25 slot terkonfigurasi di 2027, admin membuka layar
        // ini dan melihat 2026 kosong melompong. Selektor tahunnya memang ada,
        // tapi orang yang baru saja melihat "tidak ada slot" tidak punya alasan
        // untuk mengeklik tahun lain - ia menyimpulkan pekerjaannya hilang.
        //
        // `tahun_sah()` tetap dipakai untuk memvalidasi tahun yang DIMINTA;
        // yang berubah hanya ke mana ia mendarat kalau tidak ada yang diminta.
        $tahun = $tahun === NULL
            ? $this->slot->tahun_papan()
            : $this->tahun_sah($tahun);
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
     * DETAIL satu bidang - dua belas bulan, masing-masing dengan rentang
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
     * - lengkap dengan kop dan tanda tangan yang tidak pernah dibubuhkan siapa
     * pun - adalah dokumen palsu, apa pun niatnya. Yang diunggah di sini adalah
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
        // private_uploads/ - dokumen berisi nama dan periode seseorang.
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

        // Filter dari query string DIVALIDASI ke daftar yang sah - bukan
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

        // Jumlah peserta DIHITUNG dari kkn_peserta, bukan disimpan - sama
        // seperti KemitraanPortal::kkn_dashboard() (lihat migrasi 044).
        // Subquery-nya aman untuk baris magang juga: pendaftaran_id yang
        // tidak pernah dipakai magang otomatis menghitung nol.
        $data['rows'] = $this->db->select('kkn_magang_pendaftaran.*, usr_users.name AS nama_mahasiswa,
                usr_users.email AS email_mahasiswa, (SELECT COUNT(*) FROM kkn_peserta
                WHERE kkn_peserta.pendaftaran_id = kkn_magang_pendaftaran.id) AS jumlah_peserta', FALSE)
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
     * Daftar akun universitas/mahasiswa (role='mahasiswa') - permintaan user
     * 22 Agt 2026: "bisa mengelola Akun KKN/Universitas". Ini daftar AKUN,
     * beda dari index() yang mendaftar PENGAJUAN - satu akun bisa punya
     * banyak baris kkn_magang_pendaftaran (dashboard KKN, migrasi 044).
     *
     * TIDAK menduplikasi sunting/nonaktifkan/reset sandi - itu tetap milik
     * Admin_Users (satu sumber kebenaran untuk SELURUH akun apa pun
     * rolenya, lihat komentar kepala berkas itu). Tab ini murni pandangan
     * yang relevan untuk domain Kemitraan (jumlah KKN per akun) + jalan
     * pintas MEMBUAT akun universitas baru tanpa harus memilih role secara
     * manual di formulir umum Admin_Users.
     *
     * Catatan jujur: role 'mahasiswa' dipakai BERSAMA oleh akun universitas
     * (KKN) dan mahasiswa perorangan (Magang) - keputusan sesi 21 Agt 2026.
     * Daftar ini karenanya menampilkan KEDUANYA; kolom "KKN Diajukan" akan
     * nol untuk akun yang cuma pernah Magang, bukan berarti akun itu error.
     */
    public function universitas()
    {
        $data['title'] = 'Akun Universitas';

        $table = $this->table_state(['created_at', 'name', 'email'], 'created_at');
        $data['base_url'] = 'Admin_Kemitraan/universitas';

        $this->db->from('usr_users')->where('role', 'mahasiswa');
        if ($table['q'] !== '') {
            $this->db->group_start()
                ->like('name', $table['q'])->or_like('email', $table['q'])
                ->or_like('username', $table['q'])->group_end();
        }
        $table += $this->paginate_state($this->db->count_all_results('', FALSE));

        // Jumlah KKN per akun DIHITUNG lewat subquery, sama seperti index()
        // dan KemitraanPortal::kkn_dashboard() - satu pola yang sama di
        // ketiga tempat, bukan tiga cara berbeda menghitung hal yang sama.
        $data['rows'] = $this->db->select("usr_users.*, (SELECT COUNT(*) FROM kkn_magang_pendaftaran
                WHERE kkn_magang_pendaftaran.user_id = usr_users.id
                  AND kkn_magang_pendaftaran.jenis = 'kkn') AS jumlah_kkn", FALSE)
            ->order_by($table['sort'], $table['dir'])
            ->limit($table['per_page'], $table['offset'])
            ->get()->result();
        $data['table'] = $data['pager'] = $table;
        $this->render_admin('admin/kemitraan/universitas', $data);
    }

    /**
     * Sajikan dokumen pendukung ke superadmin. Ber-guard lewat Admin_Controller,
     * dibaca dari private_uploads/ (luar webroot).
     *
     * @param string $berkas WHITELIST, bukan nama kolom dari URL - menerima nama
     *   kolom mentah berarti mempersilakan siapa pun membaca kolom apa pun.
     */
    public function lihat_dokumen($id = NULL, $berkas = 'surat')
    {
        if ( ! is_numeric($id)) { show_404(); }

        $kolom = [
            'surat'    => 'file_surat_pengantar',
            'proposal' => 'file_proposal',
            'balasan'  => 'file_surat_balasan',
            // Surat permohonan akun SIMPERUM - KKN dari dashboard universitas
            // (migrasi 044, permintaan user 21 Agt 2026).
            'simperum' => 'file_surat_simperum',
        ][$berkas] ?? NULL;
        if ($kolom === NULL) { show_404(); }

        $row = $this->db->select($kolom)
            ->get_where('kkn_magang_pendaftaran', ['id' => (int) $id])->row();
        if ( ! $row || empty($row->$kolom)) { show_404(); }

        $ext  = strtolower(pathinfo($row->$kolom, PATHINFO_EXTENSION));
        $mime = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'][$ext] ?? 'application/octet-stream';
        $this->serve_private_file('kemitraan', (int) $id, $row->$kolom, $mime);
    }

    /**
     * Lihat roster peserta satu KKN - permintaan user 22 Agt 2026 ("link
     * untuk melihat list pesertanya"). Sebelumnya index() cuma menampilkan
     * ANGKA jumlah peserta; admin tidak punya cara membaca NIM/nama
     * sebenarnya tanpa membuka DB langsung.
     *
     * BACA SAJA - roster hanya bisa diubah universitas sendiri lewat
     * dashboardnya (KemitraanPortal::kkn_upload_peserta()), sama seperti
     * dua surat KKN yang juga tidak bisa diganti dari sini.
     */
    public function peserta($id = NULL)
    {
        if ( ! is_numeric($id)) { show_404(); }

        $row = $this->db->select('kkn_magang_pendaftaran.*, usr_users.name AS nama_mahasiswa, usr_users.email AS email_mahasiswa')
            ->from('kkn_magang_pendaftaran')
            ->join('usr_users', 'usr_users.id = kkn_magang_pendaftaran.user_id', 'left')
            ->where('kkn_magang_pendaftaran.id', (int) $id)
            ->get()->row();
        if ( ! $row || $row->jenis !== 'kkn') { show_404(); }

        $data['title'] = 'Peserta KKN';
        $data['row'] = $row;
        $data['peserta'] = $this->db->where('pendaftaran_id', (int) $id)
            ->order_by('nama', 'ASC')->get('kkn_peserta')->result();
        $this->render_admin('admin/kemitraan/peserta', $data);
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

        // Data pribadi mahasiswa cuma wajib untuk magang - lihat alasan
        // lengkap di KemitraanPortal::simpan_ubah() (perubahan 21 Agt 2026).
        // Pola sama persis dipertahankan di sini supaya admin tidak bisa
        // "menyunting" baris KKN untuk kembali mewajibkan field yang
        // sudah sengaja dilepas mahasiswa sendiri di sisi publik.
        // Tema Kegiatan dan Periode juga tidak lagi wajib di sini untuk KKN
        // (perubahan 21 Agt 2026, lihat pages/kemitraan_portal/daftar.php) -
        // periode_mulai/periode_selesai ditambahkan ke daftar wajib-magang
        // yang sama karena periksa_slot() (dipakai KemitraanPortal) TIDAK
        // dipanggil di sini; tanpa pemeriksaan eksplisit ini, periode kosong
        // untuk magang akan lolos sampai ke bulan_terhalang() di bawah.
        if ($row->jenis === 'magang') {
            $wajib_magang = [
                'nim' => 'NIM', 'tempat_lahir' => 'Tempat Lahir',
                'tanggal_lahir' => 'Tanggal Lahir', 'semester' => 'Semester',
                'periode_mulai' => 'Periode Mulai', 'periode_selesai' => 'Periode Selesai',
            ];
            foreach ($wajib_magang as $field => $label) {
                if (trim((string) $this->input->post($field, TRUE)) === '') {
                    $this->session->set_flashdata('error', $label . ' wajib diisi untuk pendaftaran magang.');
                    redirect('Admin_Kemitraan/ubah/' . (int) $id);
                    return;
                }
            }
        }

        $mulai   = $this->input->post('periode_mulai', TRUE);
        $selesai = $this->input->post('periode_selesai', TRUE);
        if ($selesai < $mulai) {
            $this->session->set_flashdata('error', 'Periode selesai tidak boleh mendahului periode mulai.');
            redirect('Admin_Kemitraan/ubah/' . (int) $id);
            return;
        }

        // Batas panjang berlaku juga di sini. Admin boleh melampaui KUOTA, tapi
        // periode 79 tahun bukan kewenangan - ia membuat setiap render halaman
        // menelusuri puluhan ribu hari.
        if ($this->slot->periode_terlalu_panjang($mulai, $selesai)) {
            $this->session->set_flashdata('error', 'Periode terlalu panjang. Maksimal '
                . Kemitraan_slot_model::BATAS_HARI . ' hari.');
            redirect('Admin_Kemitraan/ubah/' . (int) $id);
            return;
        }

        $divisi_atau_tema = $this->input->post('divisi_atau_tema', TRUE);

        // Divisi tetap harus NYATA - kalau tidak, papan slot dan hitungan
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
            'nim'              => $this->input->post('nim', TRUE) ?: NULL,
            'tempat_lahir'     => $this->input->post('tempat_lahir', TRUE) ?: NULL,
            'tanggal_lahir'    => $this->input->post('tanggal_lahir', TRUE) ?: NULL,
            'semester'         => $this->input->post('semester', TRUE) !== '' && $this->input->post('semester', TRUE) !== NULL
                ? (int) $this->input->post('semester', TRUE) : NULL,
            'jurusan'          => $this->input->post('jurusan', TRUE),
            'instansi_asal'    => $this->input->post('instansi_asal', TRUE),
            'no_hp'            => $this->input->post('no_hp', TRUE),
            'divisi_atau_tema' => $divisi_atau_tema ?: NULL,
            'bidang_kode'      => $bidang_kode,
            'periode_mulai'    => $mulai ?: NULL,
            'periode_selesai'  => $selesai ?: NULL,
        ]);

        $this->session->set_flashdata('success', 'Data pendaftaran diperbarui.');
        redirect('Admin_Kemitraan');
    }

    /**
     * Hapus satu pendaftaran, berikut berkas pendukungnya.
     *
     * Ada demi kelengkapan CRUD, tapi ini SATU-SATUNYA aksi di modul ini yang
     * tidak bisa dibatalkan - "Ditolak" sudah cukup untuk hampir semua kasus,
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

        // private_uploads_dir() sudah berakhiran pemisah - sama seperti dipakai
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
        // melaporkan "Status pendaftaran diperbarui" - pesan sukses untuk
        // sesuatu yang tidak pernah terjadi.
        $row = $this->db->get_where('kkn_magang_pendaftaran', ['id' => (int) $id])->row();
        if ( ! $row) { show_404(); }

        // 'Ditinjau Bidang' adalah keputusan KHAS superadmin: meneruskan surat
        // ke meja kedua. 'Diterima' tetap ada supaya ia bisa mengambil alih
        // kalau bidangnya belum ada peninjaunya - tapi jalur normalnya adalah
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
            // `bidang_kode` kolom sungguhan sejak migrasi 031 - tidak ada lagi
            // pencocokan lewat nama, dan tidak ada lagi pemetaan divisi yang
            // bisa lupa diisi. KKN memang tidak melewati meja kedua.
            if ($row->jenis !== 'magang' || empty($row->bidang_kode)) {
                $this->session->set_flashdata('error', $row->jenis !== 'magang'
                    ? 'Pendaftaran KKN tidak melewati tinjauan bidang - putuskan langsung di sini.'
                    : 'Pendaftaran ini tidak menyebut bidang tujuan, jadi tidak ada yang bisa meninjaunya.');
                redirect('Admin_Kemitraan');
                return;
            }
        }

        // Memproses baris yang SUDAH diputuskan diizinkan - admin berhak
        // berubah pikiran, dan tombolnya memang dirender untuk status apa pun.
        // Yang perlu disadari: menarik 'Dibatalkan' kembali menjadi 'Diterima'
        // membuat baris itu memakan kuota lagi. Itu benar, tapi jangan sampai
        // terjadi tanpa disengaja - karena itu labelnya di layar berbunyi
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
