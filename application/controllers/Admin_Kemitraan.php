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
        return $this->slot->tahun_sah($tahun);   // batasnya dipakai bersama Kemitraan_Bidang
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
            'title'          => 'KKN & Magang', // satu nama untuk ketiga tab, = label sidebar
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
        // Formulir mengirim keadaan LENGKAP dua belas bulan; bulan yang kotak
        // bukanya tidak tercentang tidak terkirim, dan itu memang berarti tutup.
        $kuota = $this->input->post('kuota');
        $berhasil = $this->slot->simpan_pengaturan_bidang($bidang->kode, $tahun, $kuota, (array) $this->input->post('bulan'));
        if ($berhasil) {
            $this->catat_audit('magang_slot_diubah', 'Slot magang ' . $bidang->nama . ' tahun ' . $tahun . ' diperbarui',
                'kkn_magang_bidang', (string) $bidang->kode, [
                    'tahun' => $tahun, 'kuota' => is_numeric($kuota) ? (int) $kuota : NULL,
                    'bulan' => array_values(array_map('intval', (array) $this->input->post('bulan'))),
                ]);
        }

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
        $this->catat_audit('magang_bidang_status', 'Bidang ' . $bidang->nama . ((int) $bidang->aktif ? ' berhenti' : ' mulai') . ' menerima magang',
            'kkn_magang_bidang', (string) $bidang->kode, ['aktif_lama' => (int) $bidang->aktif, 'aktif_baru' => (int) ! (int) $bidang->aktif]);

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

        $this->catat_audit('kemitraan_balasan', 'Mengunggah surat balasan ' . strtoupper($row->jenis) . ' ' . $row->instansi_asal,
            'kkn_magang_pendaftaran', (string) $row->id, ['berkas' => $nama_berkas, 'menggantikan' => $row->file_surat_balasan ?: NULL]);

        // Pemohon KKN adalah akun universitas, Magang akun mahasiswa (temuan UAT U7).
        $this->session->set_flashdata('success', 'Surat balasan diunggah. '
            . ($row->jenis === 'kkn' ? 'Universitas' : 'Mahasiswa') . ' sudah bisa mengunduhnya.');
        redirect('Admin_Kemitraan/ubah/' . (int) $row->id);
    }

    // =========================================================
    // DAFTAR PENDAFTARAN
    // =========================================================

    public function index()
    {
        $data['title'] = 'KKN & Magang'; // = label sidebar

        // Cari + urut + paginasi semuanya server-side (B7/B8).
        $table = $this->table_state([
            'kkn_magang_pendaftaran.created_at', 'usr_akun.nama',
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
            ->join('usr_akun', 'usr_akun.id = kkn_magang_pendaftaran.user_id', 'left');
        if ($f_status) { $this->db->where('kkn_magang_pendaftaran.status', $f_status); }
        if ($f_jenis)  { $this->db->where('kkn_magang_pendaftaran.jenis', $f_jenis); }
        if ($table['q'] !== '') {
            $this->db->group_start()
                ->like('usr_akun.nama', $table['q'])->or_like('usr_akun.email', $table['q'])
                ->or_like('kkn_magang_pendaftaran.instansi_asal', $table['q'])
                ->or_like('kkn_magang_pendaftaran.divisi_atau_tema', $table['q'])
                ->group_end();
        }
        $table += $this->paginate_state($this->db->count_all_results('', FALSE));

        // Jumlah peserta DIHITUNG dari kkn_peserta, bukan disimpan - sama
        // seperti KemitraanPortal::kkn_dashboard() (lihat migrasi 044).
        // Subquery-nya aman untuk baris magang juga: pendaftaran_id yang
        // tidak pernah dipakai magang otomatis menghitung nol.
        $data['rows'] = $this->db->select('kkn_magang_pendaftaran.*, usr_akun.nama AS nama_mahasiswa,
                usr_akun.email AS email_mahasiswa, (SELECT COUNT(*) FROM kkn_peserta
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
     * Daftar akun universitas (role='universitas') - permintaan user
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
     * Role 'universitas' sendiri, TERPISAH dari 'mahasiswa' - permintaan
     * user 22 Agt 2026 ("buat role UNIVERSITAS untuk proses KKN, dan
     * MAHASISWA hanya untuk proses magang"). Sebelum ini keduanya berbagi
     * role 'mahasiswa' (keputusan sesi 21 Agt 2026, lihat riwayat commit
     * 3cf160e) - daftar ini dulu ikut menampilkan mahasiswa perorangan yang
     * cuma pernah Magang, dengan "KKN Diajukan" bernilai nol untuk mereka.
     * Sejak role dipisah, filter di bawah cukup role='universitas' - tidak
     * ada lagi akun Magang yang nyasar ke daftar ini.
     */
    public function universitas()
    {
        $data['title'] = 'KKN & Magang'; // tab Akun Universitas, judul halamannya tetap satu

        $table = $this->table_state(['created_at', 'nama', 'email'], 'created_at');
        $data['base_url'] = 'Admin_Kemitraan/universitas';

        $this->db->from('usr_akun')->where('peran', 'universitas');
        if ($table['q'] !== '') {
            $this->db->group_start()
                ->like('nama', $table['q'])->or_like('email', $table['q'])
                ->or_like('nama_pengguna', $table['q'])->group_end();
        }
        $table += $this->paginate_state($this->db->count_all_results('', FALSE));

        // Jumlah KKN per akun DIHITUNG lewat subquery, sama seperti index()
        // dan KemitraanPortal::kkn_dashboard() - satu pola yang sama di
        // ketiga tempat, bukan tiga cara berbeda menghitung hal yang sama.
        $data['rows'] = $this->db->select("usr_akun.*, (SELECT COUNT(*) FROM kkn_magang_pendaftaran
                WHERE kkn_magang_pendaftaran.user_id = usr_akun.id
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
            // Laporan akhir KKN, hanya terisi setelah periode berakhir
            // (migrasi 050, permintaan user 24 Agt 2026).
            'laporan'  => 'file_laporan_akhir',
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
     * Sejak 7 Okt 2026 admin juga mengunggah daftar peserta (unggah_peserta), menetapkan awalan nomor dan
     * tanggal sertifikat, dan melihat sertifikat tiap peserta (pratinjau_sertifikat) di sini.
     */
    public function peserta($id = NULL)
    {
        if ( ! is_numeric($id)) { show_404(); }

        $row = $this->db->select('kkn_magang_pendaftaran.*, usr_akun.nama AS nama_mahasiswa, usr_akun.email AS email_mahasiswa')
            ->from('kkn_magang_pendaftaran')
            ->join('usr_akun', 'usr_akun.id = kkn_magang_pendaftaran.user_id', 'left')
            ->where('kkn_magang_pendaftaran.id', (int) $id)
            ->get()->row();
        if ( ! $row || $row->jenis !== 'kkn') { show_404(); }

        $data['title'] = 'Peserta KKN';
        $data['row'] = $row;
        // Urut unggahan (id), bukan nama: nomor otomatis 600.2/69. + id jadi tampil berurutan.
        $data['peserta'] = $this->db->where('pendaftaran_id', (int) $id)
            ->order_by('id', 'ASC')->get('kkn_peserta')->result();
        $data['nomor'] = $this->nomor_sertifikat_kkn((int) $id);
        $this->render_admin('admin/kemitraan/peserta', $data);
    }

    // =========================================================
    // SUNTING PENDAFTARAN
    // =========================================================

    public function ubah($id = NULL)
    {
        if ( ! is_numeric($id)) { show_404(); }

        $row = $this->db->select('kkn_magang_pendaftaran.*, usr_akun.nama AS nama_mahasiswa, usr_akun.email AS email_mahasiswa')
            ->from('kkn_magang_pendaftaran')
            ->join('usr_akun', 'usr_akun.id = kkn_magang_pendaftaran.user_id', 'left')
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

        $this->db->where('id', (int) $id)->update('kkn_magang_pendaftaran', $baru = [
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

        // Hanya NAMA kolom yang berubah; nilainya data pribadi mahasiswa.
        $this->catat_audit('kemitraan_diubah', 'Data pendaftaran ' . strtoupper($row->jenis) . ' ' . $row->instansi_asal . ' diubah admin',
            'kkn_magang_pendaftaran', (string) $row->id,
            ['kolom' => array_keys(array_filter($baru, function ($v, $k) use ($row) { return (string) $v !== (string) $row->$k; }, ARRAY_FILTER_USE_BOTH))]);
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
        // Folder ini khusus satu pendaftaran, jadi SELURUH isinya ikut dihapus. Daftar kolom
        // berkas dulu meninggalkan surat SIMPERUM dan laporan akhir di disk (simulasi
        // mahasiswa 27 Sep 2026), dan akan tertinggal lagi setiap ada kolom berkas baru.
        if (is_dir($dir)) {
            foreach (glob($dir . '*') ?: [] as $path) {
                if (is_file($path)) { @unlink($path); }
            }
            @rmdir($dir);
        }

        $this->db->delete('kkn_magang_pendaftaran', ['id' => (int) $row->id]);
        $this->catat_audit('kemitraan_dihapus', 'Pendaftaran ' . strtoupper($row->jenis) . ' ' . $row->instansi_asal . ' dihapus beserta berkasnya',
            'kkn_magang_pendaftaran', (string) $row->id, ['jenis' => $row->jenis, 'status' => $row->status]);

        $this->session->set_flashdata('success', 'Pendaftaran dihapus beserta berkasnya.');
        redirect('Admin_Kemitraan');
    }

    /**
     * Tetapkan tanggal terbit sertifikat KKN (daftar revisi dinas 23 Sep 2026, migrasi 062).
     * Hanya KKN yang sudah Diterima. Kosong = tarik kembali (sertifikat terkunci lagi).
     */
    // =========================================================
    // KENDALI SERTIFIKAT KKN (keputusan user 7 Okt 2026, migrasi 077)
    //
    // Dinas memegang kendali penuh atas sertifikat: admin bisa mencatat KKN atas nama
    // universitas (KKN yang berjalan sebelum aplikasi selesai), mengunggah roster sendiri,
    // dan menetapkan tanggal sertifikat tanpa menunggu permintaan. Mahasiswa yang NIM-nya
    // sudah ada di roster bisa meminta sertifikat (KemitraanPortal::minta_sertifikat_kkn);
    // permintaannya tampil di halaman sertifikat() dan badge menu.
    // =========================================================

    /** Formulir catat KKN atas nama universitas. */
    public function catat()
    {
        $data['title'] = 'KKN & Magang';
        $data['universitas'] = $this->db->select('id, nama, email')->where(['peran' => 'universitas', 'status' => 'active'])
            ->order_by('nama', 'ASC')->get('usr_akun')->result();
        $data['isian'] = (array) $this->session->flashdata('catat_kkn_isian');
        $this->render_admin('admin/kemitraan/catat', $data);
    }

    public function simpan_catat()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }
        $isian = [
            'universitas' => (int) $this->input->post('universitas'),
            'periode_mulai' => trim((string) $this->input->post('periode_mulai', TRUE)),
            'periode_selesai' => trim((string) $this->input->post('periode_selesai', TRUE)),
            'keterangan' => trim((string) $this->input->post('keterangan', TRUE)),
            'catatan' => trim((string) $this->input->post('catatan', TRUE)),
        ];
        $tolak = function ($pesan) use ($isian) {
            $this->session->set_flashdata('warning', $pesan);
            $this->session->set_flashdata('pemberitahuan_judul', 'Periksa isian KKN');
            $this->session->set_flashdata('pemberitahuan_dialog', TRUE);
            $this->session->set_flashdata('catat_kkn_isian', $isian);
            redirect('Admin_Kemitraan/catat');
        };
        $univ = $this->db->get_where('usr_akun', ['id' => $isian['universitas'], 'peran' => 'universitas'])->row();
        if ( ! $univ) { $tolak('Pilih akun universitas.'); return; }
        $sah = function ($t) { $d = DateTime::createFromFormat('!Y-m-d', $t); return $d && $d->format('Y-m-d') === $t; };
        if ( ! $sah($isian['periode_mulai']) || ! $sah($isian['periode_selesai'])) { $tolak('Periode mulai dan selesai wajib diisi dengan tanggal yang sah.'); return; }
        if ($isian['periode_selesai'] < $isian['periode_mulai']) { $tolak('Periode selesai tidak boleh mendahului periode mulai.'); return; }
        if ($this->slot->periode_terlalu_panjang($isian['periode_mulai'], $isian['periode_selesai'])) {
            $tolak('Periode terlalu panjang. Maksimal ' . Kemitraan_slot_model::BATAS_HARI . ' hari.');
            return;
        }
        if ($isian['keterangan'] === '' || mb_strlen($isian['keterangan']) > 150) { $tolak('Keterangan wajib diisi, maksimal 150 karakter.'); return; }
        if (mb_strlen($isian['catatan']) > 500) { $tolak('Catatan admin maksimal 500 karakter.'); return; }
        $ganda = $this->db->where(['user_id' => $univ->id, 'jenis' => 'kkn', 'periode_mulai' => $isian['periode_mulai'],
                'periode_selesai' => $isian['periode_selesai'], 'divisi_atau_tema' => $isian['keterangan']])
            ->where_not_in('status', ['Ditolak', 'Dibatalkan'])->count_all_results('kkn_magang_pendaftaran');
        if ($ganda > 0) { $tolak('KKN dengan universitas, periode, dan keterangan yang sama sudah ada.'); return; }

        $admin = (int) $this->get_user_id();
        $this->db->insert('kkn_magang_pendaftaran', [
            'user_id' => (int) $univ->id, 'jenis' => 'kkn', 'instansi_asal' => (string) $univ->nama,
            'no_hp' => (string) ($univ->no_hp ?? ''), 'divisi_atau_tema' => $isian['keterangan'],
            'periode_mulai' => $isian['periode_mulai'], 'periode_selesai' => $isian['periode_selesai'],
            // Dicatat dinas = sudah diputuskan; tidak melewati antrean Diajukan.
            'status' => 'Diterima', 'catatan_admin' => $isian['catatan'] !== '' ? $isian['catatan'] : NULL,
            'dicatat_oleh' => $admin, 'reviewed_by' => $admin, 'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
        $id = (int) $this->db->insert_id();
        $this->catat_audit('kkn_dicatat_admin', 'Mencatat KKN ' . $isian['keterangan'] . ' atas nama ' . $univ->nama,
            'kkn_magang_pendaftaran', (string) $id, ['universitas' => (int) $univ->id, 'periode' => $isian['periode_mulai'] . '/' . $isian['periode_selesai']]);
        $this->session->set_flashdata('success', 'KKN dicatat dan langsung diterima. Unggah daftar peserta, lalu tetapkan tanggal sertifikat.');
        redirect('Admin_Kemitraan/peserta/' . $id);
    }

    /**
     * Admin mengunggah atau mengganti roster (format sama dengan universitas). Berbeda dari universitas,
     * admin TETAP boleh mengganti roster sesudah tanggal sertifikat ditetapkan: dinas pemegang kendali,
     * dan perubahannya tercatat di jejak audit.
     */
    public function unggah_peserta($id = NULL)
    {
        if ($this->input->method(TRUE) !== 'POST' || ! is_numeric($id)) { show_404(); }
        $row = $this->db->get_where('kkn_magang_pendaftaran', ['id' => (int) $id, 'jenis' => 'kkn'])->row();
        if ( ! $row) { show_404(); }
        $kembali = 'Admin_Kemitraan/peserta/' . (int) $row->id;
        $file = $_FILES['file_peserta'] ?? NULL;
        if ( ! $file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ! is_uploaded_file($file['tmp_name'])) {
            $this->session->set_flashdata('warning', 'Pilih berkas daftar peserta (XLS atau XLSX) terlebih dahulu.');
            redirect($kembali);
            return;
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if ($file['size'] > 5242880 || ! in_array($ext, ['xls', 'xlsx'], TRUE)) {
            $this->session->set_flashdata('warning', 'Daftar peserta harus berkas XLS atau XLSX, maksimal 5 MB.');
            redirect($kembali);
            return;
        }
        $galat = NULL;
        if ( ! $this->scan_uploaded_file($file['tmp_name'], $ext, $galat, 'kkn_peserta')) {
            $this->session->set_flashdata('error', $galat);
            redirect($kembali);
            return;
        }
        $this->load->library('kkn_peserta_import');
        $hasil = $this->kkn_peserta_import->baca($file['tmp_name']);
        if (empty($hasil['success'])) {
            $this->session->set_flashdata('warning', $hasil['message']);
            redirect($kembali);
            return;
        }
        $jumlah = $this->ganti_roster_kkn($row->id, $hasil['peserta']);
        if ($jumlah === FALSE) {
            $this->session->set_flashdata('error', 'Gagal menyimpan daftar peserta. Coba lagi.');
            redirect($kembali);
            return;
        }
        $this->catat_audit('kkn_roster_admin', 'Admin mengganti daftar peserta KKN ' . $row->instansi_asal . ' (' . $jumlah . ' peserta)',
            'kkn_magang_pendaftaran', (string) $row->id, ['jumlah' => $jumlah]);
        $this->session->set_flashdata('success', $jumlah . ' peserta tersimpan.'
            . (empty($row->tanggal_sertifikat) ? ' Tetapkan tanggal sertifikat agar peserta bisa mencetak.' : ''));
        redirect($kembali);
    }

    /**
     * Lihat sertifikat satu peserta dari halaman Peserta KKN (permintaan user 7 Okt 2026, menggantikan edit nomor
     * per peserta): PDF yang sama dengan cetakan peserta (library Cetak_sertifikat_kkn), juga sebelum tanggal terbit
     * ditetapkan (tanggal tercetak = hari ini) supaya nomor dan nama bisa dicek sebelum diterbitkan.
     */
    public function pratinjau_sertifikat($id = NULL)
    {
        if ( ! is_numeric($id)) { show_404(); }
        $data = $this->db->select('kkn_peserta.id AS id_peserta, kkn_peserta.nama AS nama_peserta, kkn_peserta.nim, kkn_peserta.pendaftaran_id,
                kkn_magang_pendaftaran.instansi_asal, kkn_magang_pendaftaran.tanggal_sertifikat')
            ->from('kkn_peserta')->join('kkn_magang_pendaftaran', 'kkn_magang_pendaftaran.id = kkn_peserta.pendaftaran_id')
            ->where(['kkn_peserta.id' => (int) $id, 'kkn_magang_pendaftaran.jenis' => 'kkn'])->get()->row();
        if ( ! $data) { show_404(); }
        $nomor = $this->nomor_sertifikat_kkn((int) $data->pendaftaran_id)[(int) $data->id_peserta]->nomor;
        $this->load->library('cetak_sertifikat_kkn');
        if ( ! $this->cetak_sertifikat_kkn->kirim($data, $nomor)) { show_404(); }
    }

    /**
     * Awalan nomor sertifikat satu KKN (permintaan dinas 7 Okt 2026, migrasi 079): "600.2/69 ditentukan admin
     * per periode, dua digit terakhir otomatis menempel di peserta". Kosong = kembali ke 600.2/69. + id peserta.
     * Untuk KKN yang belum Diterima; yang sudah Diterima menyimpannya bersama tanggal sertifikat (tanggal_sertifikat).
     */
    public function awalan_nomor($id = NULL)
    {
        if ($this->input->method(TRUE) !== 'POST' || ! is_numeric($id)) { show_404(); }
        $row = $this->db->get_where('kkn_magang_pendaftaran', ['id' => (int) $id, 'jenis' => 'kkn'])->row();
        if ( ! $row) { show_404(); }
        if ($galat = $this->simpan_awalan($row, $this->input->post('awalan_nomor', TRUE))) {
            $this->session->set_flashdata('warning', $galat);
        } else {
            $this->session->set_flashdata('success', $this->pesan_awalan($row));
        }
        redirect('Admin_Kemitraan/peserta/' . (int) $row->id);
    }

    /**
     * Simpan awalan nomor satu KKN bila berubah. Awalan yang membuat nomor otomatis peserta sama dengan nomor
     * peserta lain (misalnya awalan KKN lain) ditolak. @return string|NULL pesan penolakan, NULL bila tersimpan/tetap.
     */
    private function simpan_awalan($row, $masukan)
    {
        $awalan = rtrim(preg_replace('/\s+/', ' ', trim((string) $masukan)), '. ');
        if ($awalan === (string) $row->awalan_nomor_sertifikat) { return NULL; }
        if ($awalan !== '' && ! preg_match('#^[A-Za-z0-9 .,/()_-]{1,80}$#', $awalan)) {
            return 'Awalan nomor maksimal 80 karakter: huruf, angka, spasi, dan tanda . , / - _ ( ).';
        }
        // ponytail: memindai nomor seluruh peserta KKN per simpan; batasi per awalan bila pesertanya puluhan ribu.
        $semua = $this->nomor_sertifikat_kkn(NULL, [(int) $row->id => $awalan === '' ? NULL : $awalan]);
        $milik = array_filter($semua, fn($n) => $n->pendaftaran_id === (int) $row->id);
        if ($kembar = $this->nomor_kembar($semua, array_keys($milik))) {
            return 'Awalan ' . $awalan . ' membuat nomor ' . $kembar[0] . ' sama dengan sertifikat peserta lain (NIM ' . $kembar[1] . '). Pakai awalan lain.';
        }
        $this->db->where('id', (int) $row->id)->update('kkn_magang_pendaftaran', ['awalan_nomor_sertifikat' => $awalan === '' ? NULL : $awalan]);
        $this->catat_audit('sertifikat_kkn_awalan', 'Awalan nomor sertifikat KKN ' . $row->instansi_asal . ': '
            . ($row->awalan_nomor_sertifikat ?: '(bawaan)') . ' -> ' . ($awalan ?: '(bawaan)'),
            'kkn_magang_pendaftaran', (string) $row->id, ['lama' => $row->awalan_nomor_sertifikat, 'baru' => $awalan ?: NULL]);
        $row->awalan_nomor_sertifikat = $awalan === '' ? NULL : $awalan;
        return NULL;
    }

    private function pesan_awalan($row)
    {
        $a = (string) $row->awalan_nomor_sertifikat;
        return $a === '' ? 'Nomor sertifikat memakai bawaan 600.2/69. + nomor urut database.'
            : 'Nomor sertifikat: ' . $a . '.01, ' . $a . '.02, dan seterusnya.';
    }

    /** [nomor, NIM peserta lain] bila nomor salah satu peserta $ids sudah dipakai peserta lain (tanpa beda huruf besar/kecil). */
    private function nomor_kembar(array $semua, array $ids)
    {
        $peta = [];
        foreach ($semua as $id => $n) { $peta[strtolower($n->nomor)][] = $id; }
        foreach ($ids as $id) {
            foreach ($peta[strtolower($semua[$id]->nomor)] as $lain) {
                if ($lain !== $id) {
                    return [$semua[$id]->nomor, $this->db->select('nim')->get_where('kkn_peserta', ['id' => $lain])->row()->nim ?? '-'];
                }
            }
        }
        return NULL;
    }

    /** KKN yang sertifikatnya diminta mahasiswa, lalu KKN diterima yang belum bertanggal sertifikat. */
    public function sertifikat()
    {
        $data['title'] = 'Sertifikat KKN';
        $peserta = '(SELECT COUNT(*) FROM kkn_peserta WHERE kkn_peserta.pendaftaran_id = kkn_magang_pendaftaran.id)';
        $data['rows'] = $this->db->select("kkn_magang_pendaftaran.*, $peserta AS jumlah_peserta", FALSE)
            ->from('kkn_magang_pendaftaran')
            ->where('jenis', 'kkn')->where('tanggal_sertifikat IS NULL', NULL, FALSE)
            ->group_start()->where('sertifikat_diminta_at IS NOT NULL', NULL, FALSE)->or_where('status', 'Diterima')->group_end()
            ->where_not_in('status', ['Ditolak', 'Dibatalkan'])
            ->order_by('sertifikat_diminta_at IS NULL', 'ASC', FALSE)->order_by('sertifikat_diminta_at', 'ASC')
            ->order_by('periode_selesai', 'ASC')->get()->result();
        $this->render_admin('admin/kemitraan/sertifikat', $data);
    }

    /** Tutup permintaan sertifikat tanpa menetapkan tanggal (mis. roster belum lengkap, dibahas di luar sistem). */
    public function abaikan_permintaan($id = NULL)
    {
        if ($this->input->method(TRUE) !== 'POST' || ! is_numeric($id)) { show_404(); }
        $row = $this->db->get_where('kkn_magang_pendaftaran', ['id' => (int) $id, 'jenis' => 'kkn'])->row();
        if ( ! $row) { show_404(); }
        $this->db->where('id', (int) $row->id)->update('kkn_magang_pendaftaran', ['sertifikat_diminta_at' => NULL, 'sertifikat_diminta_jumlah' => 0]);
        $this->catat_audit('sertifikat_kkn_diabaikan', 'Mengabaikan permintaan sertifikat KKN ' . $row->instansi_asal,
            'kkn_magang_pendaftaran', (string) $row->id, ['jumlah' => (int) $row->sertifikat_diminta_jumlah]);
        $this->session->set_flashdata('success', 'Permintaan sertifikat ditutup.');
        redirect('Admin_Kemitraan/sertifikat');
    }

    public function tanggal_sertifikat($id = NULL)
    {
        if ($this->input->method(TRUE) !== 'POST' || ! is_numeric($id)) { show_404(); }
        $row = $this->db->get_where('kkn_magang_pendaftaran', ['id' => (int) $id, 'jenis' => 'kkn'])->row();
        if ( ! $row) { show_404(); }
        // Dipanggil dari daftar pendaftaran, halaman Sertifikat KKN, dan halaman Peserta; kembali ke asalnya.
        $kembali = ['sertifikat' => 'Admin_Kemitraan/sertifikat', 'peserta' => 'Admin_Kemitraan/peserta/' . (int) $row->id][(string) $this->input->post('kembali', TRUE)] ?? 'Admin_Kemitraan';
        if ($row->status !== 'Diterima') {
            $this->session->set_flashdata('error', 'Tanggal sertifikat hanya untuk KKN yang sudah diterima.');
            redirect($kembali);
            return;
        }
        $tgl = trim((string) $this->input->post('tanggal_sertifikat', TRUE));
        if ($tgl !== '') {
            $d = DateTime::createFromFormat('!Y-m-d', $tgl);
            if ( ! $d || $d->format('Y-m-d') !== $tgl) {
                $this->session->set_flashdata('error', 'Tanggal sertifikat harus berformat YYYY-MM-DD.');
                redirect($kembali);
                return;
            }
        }
        // Awalan nomor di atas tanggal pada formulir yang sama (permintaan dinas 7 Okt 2026); tanpa isian ini
        // (tab Sertifikat KKN) awalan tidak disentuh. Ditolak = tanggal juga tidak disimpan.
        $awalan_lama = (string) $row->awalan_nomor_sertifikat;
        if ($this->input->post('awalan_nomor') !== NULL && ($galat = $this->simpan_awalan($row, $this->input->post('awalan_nomor', TRUE)))) {
            $this->session->set_flashdata('warning', $galat);
            redirect($kembali);
            return;
        }
        $awalan_info = (string) $row->awalan_nomor_sertifikat !== $awalan_lama ? ' ' . $this->pesan_awalan($row) : '';
        // Menetapkan tanggal sekaligus menjawab permintaan sertifikat mahasiswa (migrasi 077).
        $this->db->where('id', (int) $row->id)->update('kkn_magang_pendaftaran', ['tanggal_sertifikat' => $tgl === '' ? NULL : $tgl]
            + ($tgl === '' ? [] : ['sertifikat_diminta_at' => NULL, 'sertifikat_diminta_jumlah' => 0]));
        $this->catat_audit('sertifikat_kkn_tanggal', ($tgl === '' ? 'Menarik tanggal sertifikat KKN ' : 'Menetapkan tanggal sertifikat KKN ' . $tgl . ' untuk ') . $row->instansi_asal,
            'kkn_magang_pendaftaran', (string) $row->id, ['tanggal_sertifikat' => $tgl === '' ? NULL : $tgl]);
        // Peserta baru bisa mencetak sesudah periode KKN selesai (KemitraanPortal::cek_sertifikat_kkn),
        // jadi flash tidak boleh menjanjikan "sudah bisa" sebelum itu (temuan UAT U5).
        // Juga tidak sebelum tanggal terbitnya sendiri bila ditetapkan untuk hari depan.
        $mulai_cetak = max(date('Y-m-d', strtotime($row->periode_selesai . ' +1 day')), $tgl);
        $this->session->set_flashdata('success', ($tgl === '' ? 'Tanggal sertifikat ditarik; sertifikat terkunci kembali.'
            : ($mulai_cetak > date('Y-m-d')
                ? 'Tanggal sertifikat ditetapkan. Peserta bisa mencetak mulai ' . tgl_id($mulai_cetak) . '.'
                : 'Tanggal sertifikat ditetapkan. Peserta sudah bisa mencetak sertifikat.')) . $awalan_info);
        redirect($kembali);
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

        // Keputusan admin dicatat beserta keadaan sebelumnya: catatan_admin ditimpa setiap kali
        // keputusan diubah, jadi tanpa ini alasan penolakan lama hilang (temuan UAT U5).
        $this->catat_audit('kemitraan_keputusan', 'Keputusan ' . strtoupper($row->jenis) . ' ' . $row->instansi_asal . ': ' . $row->status . ' -> ' . $status,
            'kkn_magang_pendaftaran', (string) $row->id, [
                'status_lama' => $row->status, 'status_baru' => $status,
                'catatan_lama' => $row->catatan_admin, 'catatan_baru' => trim((string) $this->input->post('catatan_admin', TRUE)),
            ]);

        if ($status === 'Ditinjau Bidang') {
            $this->notify_admin_push([
                ['role' => 'admin_bidang', 'bidang_kode' => $row->bidang_kode],
            ], 'Pendaftaran magang untuk bidang Anda',
                'Ada pendaftaran magang yang menunggu tinjauan bidang.',
                'Kemitraan_Bidang', 'magang-bidang-' . (int) $row->id);
        }
        $this->session->set_flashdata('success', 'Status pendaftaran diperbarui.');
        redirect('Admin_Kemitraan');
    }
}
