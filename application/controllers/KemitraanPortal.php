<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class KemitraanPortal extends Public_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->model('kemitraan_slot_model', 'slot');
    }

    public function index()
    {
        $this->render('pages/kemitraan_portal/index', ['judul' => 'KKN dan Magang']);
    }

    public function kkn()
    {
        $this->render('pages/kemitraan_portal/kkn', ['judul' => 'KKN Kemitraan']);
    }

    /**
     * Papan slot magang. Dulu isinya array literal di method ini - tidak ada
     * yang bisa mengubahnya tanpa deploy, dan formulir pendaftaran tidak pernah
     * tunduk padanya. Sekarang dari `kkn_magang_slot`, dikelola superadmin
     * lewat Admin_Kemitraan::slot().
     */
    public function magang($tahun = NULL)
    {
        // Tahun boleh diminta dari URL, tapi bawaannya BUKAN date('Y') buta:
        // slot yang ditetapkan untuk tahun depan akan tak terlihat sama sekali
        // kalau papan selalu memaksa tahun berjalan.
        $tahun = in_array((int) $tahun, $this->slot->tahun_tersedia(), TRUE)
            ? (int) $tahun
            : $this->slot->tahun_papan();
        $peta   = $this->slot->peta_slot($tahun);
        $terisi = $this->slot->peta_terisi();

        /**
         * SATU KEADAAN PER BIDANG, bukan matriks 12 bulan.
         *
         * Revisi dinas 3 Agt 2026: "jadwalnya hilangkan saja, langsung ke list
         * kebutuhan magangnya saja, nanti ada keterangan kebutuhan berapa orang,
         * terpenuhi belum terpenuhi."
         *
         * Yang berubah HANYA yang dibaca orang. Mesin kuotanya tidak disentuh:
         * `periksa_slot()` tetap menolak per-bulan dan per-hari saat mendaftar,
         * dan formulir tetap meminta periode. Papan ini menjawab satu pertanyaan
         * - "bidang ini masih menerima atau tidak" - yang dulu harus disimpulkan
         * sendiri dari 12 kotak berwarna.
         *
         * "Terpenuhi" ditentukan dari BULAN YANG PALING LONGGAR, bukan dari
         * rata-rata atau dari puncak. Alasannya: pendaftar cuma perlu SATU bulan
         * yang masih muat. Memakai puncak akan menulis "terpenuhi" pada bidang
         * yang sebenarnya masih punya tiga bulan kosong - menolak orang yang
         * seharusnya diterima, dan papan yang berbohong ke arah itu jauh lebih
         * mahal daripada yang berbohong sebaliknya.
         */
        /* Butir F1: posisi/lowongan yang dicari tiap bidang, diisi dinas lewat
           layar Posisi Magang. Diambil SEKALI lalu dikelompokkan - query di
           dalam perulangan bidang berarti lima query untuk lima bidang.

           Hanya yang `aktif`. Posisi yang sudah terisi dimatikan, bukan
           dihapus, supaya catatannya tetap ada untuk periode berikutnya. */
        $posisi_per_bidang = [];
        if ($this->db->table_exists('kkn_magang_posisi')) {
            foreach ($this->db->where('aktif', 1)->order_by('urutan', 'ASC')
                         ->order_by('nama_posisi', 'ASC')->get('kkn_magang_posisi')->result() as $p) {
                $posisi_per_bidang[$p->bidang_kode][] = $p;
            }
        }

        $slot_magang = [];
        foreach ($this->slot->bidang() as $bidang) {
            $kuota   = (int) $bidang->kuota;
            $bulan_dibuka = [];
            $sisa_terbaik = NULL;   // NULL = belum ada satu bulan pun yang dibuka

            foreach (Kemitraan_slot_model::nama_bulan() as $nomor => $label) {
                if (empty($peta[$bidang->kode][$nomor])) { continue; }

                $bulan_dibuka[] = $label;
                $isi  = (int) ($terisi[$bidang->kode][$tahun . '-' . $nomor] ?? 0);
                $sisa = max(0, $kuota - $isi);
                if ($sisa_terbaik === NULL || $sisa > $sisa_terbaik) { $sisa_terbaik = $sisa; }
            }

            if ($sisa_terbaik === NULL) {
                $keadaan = 'tutup';   // tidak satu bulan pun dibuka tahun ini
            } elseif ($sisa_terbaik > 0) {
                $keadaan = 'menerima';
            } else {
                $keadaan = 'terpenuhi';
            }

            $slot_magang[] = [
                'bidang'       => $bidang->nama,
                'kuota'        => $kuota,
                'keadaan'      => $keadaan,
                'sisa'         => (int) $sisa_terbaik,
                'bulan_dibuka' => $bulan_dibuka,
                'posisi'       => $posisi_per_bidang[$bidang->kode] ?? [],
            ];
        }

        $this->render('pages/kemitraan_portal/magang', [
            'judul'          => 'Magang dan Kerja Praktik',
            'tahun'          => $tahun,
            'tahun_tersedia' => $this->slot->tahun_tersedia(),
            'slot_magang'    => $slot_magang,
        ]);
    }

    public function daftar($jenis = NULL)
    {
        if ( ! in_array($jenis, ['kkn', 'magang'], TRUE)) { show_404(); }
        if ( ! $this->akses_mahasiswa('KemitraanPortal/daftar/' . $jenis)) { return; }

        // Nama & email ditampilkan BACA-SAJA di formulir, diambil dari sesi.
        // Bukan sekadar hiasan: pendaftaran ini menempel ke akun lewat user_id,
        // dan sebelumnya pendaftar tidak pernah diberi tahu nama siapa yang
        // ikut terkirim. Tetap tidak diterima sebagai input - `simpan()` hanya
        // membaca user_id dari sesi (anti-IDOR), jadi mengubahnya di peramban
        // tidak mengubah apa pun.
        // Divisi hanya untuk magang. Di formulir KKN field yang sama berlabel
        // "Tema Kegiatan" - itu memang teks bebas, bukan unit kerja, jadi tidak
        // ada slot yang bisa mengaturnya.
        $this->render('pages/kemitraan_portal/daftar', [
            'judul'      => $jenis === 'kkn' ? 'Daftar KKN Kemitraan' : 'Daftar Magang dan Kerja Praktik',
            'jenis'      => $jenis,
            'nama_akun'  => (string) $this->session->userdata('name'),
            'email_akun' => (string) $this->session->userdata('email'),
            'divisi'     => $jenis === 'magang' ? $this->slot->bidang() : [],
        ]);
    }

    // =========================================================
    // PENDAFTARAN MILIK SENDIRI - lihat, sunting, batalkan
    //
    // Semuanya lewat pendaftaran_milik(), yang mencocokkan user_id dari SESI.
    // Id dari URL tidak pernah cukup: tanpa pencocokan itu, mengganti angka di
    // alamat berarti membaca dan menyunting pendaftaran orang lain.
    // =========================================================

    public function pendaftaran($id = NULL)
    {
        $row = $this->pendaftaran_milik($id);
        if ( ! $row) { return; }

        // Nama bidang diambil DI SINI, bukan di view. Pendaftar perlu tahu
        // bidang mana yang memegang berkasnya - tanpa itu garis waktunya cuma
        // bilang "bidang penanggung jawab" tanpa menyebut siapa. Query-nya
        // sempat saya taruh di view; dipindah karena view yang menyentuh DB
        // adalah pola yang akan ditiru berkas berikutnya.
        $nama_bidang = NULL;
        if ($row->jenis === 'magang' && ! empty($row->bidang_kode)) {
            $b = $this->db->select('nama')->get_where('bidang', ['kode' => $row->bidang_kode])->row();
            $nama_bidang = $b->nama ?? $row->bidang_kode;
        }

        $this->render('pages/kemitraan_portal/pendaftaran', [
            'judul'       => 'Pendaftaran ' . strtoupper($row->jenis),
            'row'         => $row,
            'nama_bidang' => $nama_bidang,
            'bisa_ubah'   => $row->status === 'Diajukan',
        ]);
    }

    /**
     * Unduh surat balasan sendiri.
     *
     * Berkas berada di luar webroot; satu-satunya jalan ke sana adalah endpoint
     * ini, dan ia hanya menyajikan baris milik pemanggilnya. Surat balasan
     * memuat nama, instansi, dan periode seseorang - ia bukan berkas publik
     * hanya karena isinya kabar baik.
     */
    public function unduh_balasan($id = NULL)
    {
        $row = $this->pendaftaran_milik($id);
        if ( ! $row) { return; }
        if (empty($row->file_surat_balasan)) { show_404(); }

        $ext  = strtolower(pathinfo($row->file_surat_balasan, PATHINFO_EXTENSION));
        $mime = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'][$ext] ?? 'application/octet-stream';
        $this->serve_private_file('kemitraan', (int) $row->id, $row->file_surat_balasan, $mime);
    }

    public function ubah($id = NULL)
    {
        $row = $this->pendaftaran_milik($id);
        if ( ! $row) { return; }

        // Yang sudah ditinjau TIDAK boleh berubah diam-diam di belakang
        // peninjaunya - itu membuat keputusan admin menjadi keputusan atas data
        // yang sudah tidak ada lagi. Keputusan user 1 Agt 2026.
        if ($row->status !== 'Diajukan') {
            $this->session->set_flashdata('error', 'Pendaftaran yang sudah ' . strtolower($row->status) . ' tidak bisa diubah lagi. Hubungi admin bila ada yang keliru.');
            redirect('KemitraanPortal/pendaftaran/' . (int) $row->id);
            return;
        }

        $this->render('pages/kemitraan_portal/ubah', [
            'judul'      => 'Ubah Pendaftaran',
            'row'        => $row,
            'nama_akun'  => (string) $this->session->userdata('name'),
            'email_akun' => (string) $this->session->userdata('email'),
            'divisi'     => $row->jenis === 'magang' ? $this->slot->bidang() : [],
        ]);
    }

    public function simpan_ubah($id = NULL)
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }
        $row = $this->pendaftaran_milik($id);
        if ( ! $row) { return; }

        if ($row->status !== 'Diajukan') {
            $this->session->set_flashdata('error', 'Pendaftaran yang sudah ' . strtolower($row->status) . ' tidak bisa diubah lagi.');
            redirect('KemitraanPortal/pendaftaran/' . (int) $row->id);
            return;
        }

        if ($this->form_validation->run('kemitraan_pendaftaran') === FALSE) {
            $this->session->set_flashdata('error', validation_errors('<li>', '</li>'));
            redirect('KemitraanPortal/ubah/' . (int) $row->id);
            return;
        }

        $mulai   = $this->input->post('periode_mulai', TRUE);
        $selesai = $this->input->post('periode_selesai', TRUE);
        $divisi_atau_tema = $this->input->post('divisi_atau_tema', TRUE);

        // $row->id diteruskan sebagai $abaikan_id: baris ini tidak boleh
        // menghalangi dirinya sendiri. Tanpa itu, menyunting apa pun pada
        // pendaftaran di bulan yang kuotanya pas akan selalu ditolak - oleh
        // dirinya sendiri.
        $bidang_kode = NULL;
        $galat = $this->periksa_slot($row->jenis, $divisi_atau_tema, $bidang_kode, $mulai, $selesai, (int) $row->id);
        if ($galat !== NULL) {
            $this->session->set_flashdata('error', $galat);
            redirect('KemitraanPortal/ubah/' . (int) $row->id);
            return;
        }

        $this->db->where('id', (int) $row->id)->update('kkn_magang_pendaftaran', [
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

        $this->session->set_flashdata('success', 'Pendaftaran diperbarui.');
        redirect('KemitraanPortal/pendaftaran/' . (int) $row->id);
    }

    /**
     * Batalkan pendaftaran sendiri.
     *
     * Statusnya diubah jadi 'Dibatalkan', BUKAN barisnya dihapus. Riwayatnya
     * tetap terbaca oleh mahasiswa maupun admin, sementara kuotanya lepas
     * seketika - peta_harian() hanya menghitung 'Diajukan' dan 'Diterima'.
     *
     * Ini menutup masalah yang nyata: status Diajukan sudah memakan kuota, jadi
     * satu orang yang salah pilih divisi mengunci slot sampai ada admin yang
     * menolaknya. Pada divisi berkuota 1, itu memblokir semua orang lain.
     */
    public function batal($id = NULL)
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }
        $row = $this->pendaftaran_milik($id);
        if ( ! $row) { return; }

        if ($row->status !== 'Diajukan') {
            $this->session->set_flashdata('error', 'Hanya pendaftaran yang masih diajukan yang bisa dibatalkan.');
            redirect('KemitraanPortal/pendaftaran/' . (int) $row->id);
            return;
        }

        $this->db->where('id', (int) $row->id)->update('kkn_magang_pendaftaran', ['status' => 'Dibatalkan']);
        $this->session->set_flashdata('success', 'Pendaftaran dibatalkan. Slotnya kembali tersedia untuk mahasiswa lain.');
        redirect('akun');
    }

    /**
     * Ambil pendaftaran milik pemohon sendiri, atau hentikan permintaan.
     *
     * @return object|FALSE
     */
    private function pendaftaran_milik($id)
    {
        if ( ! is_numeric($id)) { show_404(); }
        if ( ! $this->akses_mahasiswa('akun')) { return FALSE; }

        $row = $this->db->get_where('kkn_magang_pendaftaran', [
            'id'      => (int) $id,
            'user_id' => $this->get_user_id(),
        ])->row();

        // 404, bukan 403: membedakan "tidak ada" dari "ada tapi bukan milikmu"
        // memberi tahu penebak bahwa nomor itu sah.
        if ( ! $row) { show_404(); }
        return $row;
    }

    public function simpan()
    {
        $jenis = $this->input->post('jenis', TRUE);
        if ( ! in_array($jenis, ['kkn', 'magang'], TRUE) || $this->input->method(TRUE) !== 'POST') { show_404(); }
        if ( ! $this->akses_mahasiswa('KemitraanPortal/daftar/' . $jenis)) { return; }

        // Aturannya hidup di config/form_validation.php, grup
        // `kemitraan_pendaftaran` - mekanisme bawaan CI3. Superadmin menyunting
        // baris yang sama lewat Admin_Kemitraan::simpan_ubah(), dan dua salinan
        // aturan yang sama akan berselisih cepat atau lambat.
        if ($this->form_validation->run('kemitraan_pendaftaran') === FALSE) {
            $this->session->set_flashdata('error', validation_errors('<li>', '</li>'));
            redirect('KemitraanPortal/daftar/' . $jenis);
            return;
        }

        $divisi_atau_tema = $this->input->post('divisi_atau_tema', TRUE);
        $periode_mulai    = $this->input->post('periode_mulai', TRUE);
        $periode_selesai  = $this->input->post('periode_selesai', TRUE);

        // Satu mahasiswa, satu pendaftaran menggantung per jenis. Tanpa ini
        // formulir bisa dikirim berulang kali, dan karena tiap baris berstatus
        // Diajukan memakan kuota, satu orang bisa menghabiskan seluruh kuota
        // sebuah divisi sendirian.
        $menggantung = $this->db->where([
            'user_id' => $this->get_user_id(), 'jenis' => $jenis, 'status' => 'Diajukan',
        ])->count_all_results('kkn_magang_pendaftaran');
        if ($menggantung > 0) {
            $this->tolak_pendaftaran($jenis, 'Anda masih punya pendaftaran ' . strtoupper($jenis)
                . ' yang sedang ditinjau. Batalkan atau tunggu hasilnya dulu - lihat di halaman Akun.');
            return;
        }

        $bidang_kode = NULL;
        $galat = $this->periksa_slot($jenis, $divisi_atau_tema, $bidang_kode, $periode_mulai, $periode_selesai);
        if ($galat !== NULL) {
            $this->tolak_pendaftaran($jenis, $galat);
            return;
        }

        // SURAT PENGANTAR WAJIB UNTUK MAGANG - keputusan user 2 Agt 2026,
        // membuka butir #11 yang selama ini BLOCKED. KKN sengaja TIDAK ikut:
        // yang diminta hanya magang, dan melebarkannya sendiri berarti menolak
        // pendaftaran yang selama ini sah tanpa ada yang memutuskannya.
        //
        // Diperiksa SEBELUM barisnya lahir. Seluruh alur empat tahap berdiri di
        // atas surat ini; pendaftaran magang tanpa surat akan sampai ke meja
        // bidang membawa tahap 1 yang tidak pernah benar-benar terjadi.
        //
        // `required` di formulir tidak dihitung sebagai penjagaan - ia hilang
        // begitu POST dikirim tanpa lewat halaman itu.
        if ($jenis === 'magang' && ( ! isset($_FILES['file_surat_pengantar'])
            || (int) $_FILES['file_surat_pengantar']['error'] === UPLOAD_ERR_NO_FILE)) {
            $this->tolak_pendaftaran($jenis,
                'Surat pengantar wajib dilampirkan untuk pendaftaran magang. '
                . 'Format JPG, PNG, atau PDF, maksimal 5 MB.');
            return;
        }

        // user_id selalu dari sesi (anti-IDOR), bukan dari input. Baris dibuat
        // dulu supaya surat pengantarnya punya folder pemilik sendiri
        // (private_uploads/kemitraan/{id}/).
        $this->db->insert('kkn_magang_pendaftaran', [
            'user_id'              => $this->get_user_id(),
            'jenis'                => $jenis,
            'nim'                  => $this->input->post('nim', TRUE),
            'tempat_lahir'         => $this->input->post('tempat_lahir', TRUE),
            'tanggal_lahir'        => $this->input->post('tanggal_lahir', TRUE),
            'semester'             => (int) $this->input->post('semester', TRUE),
            'jurusan'              => $this->input->post('jurusan', TRUE),
            'instansi_asal'        => $this->input->post('instansi_asal', TRUE),
            'no_hp'                => $this->input->post('no_hp', TRUE),
            'divisi_atau_tema'     => $divisi_atau_tema,
            'bidang_kode'          => $bidang_kode,
            'periode_mulai'        => $periode_mulai,
            'periode_selesai'      => $periode_selesai,
            'file_surat_pengantar' => NULL,
            'file_proposal'        => NULL,
        ]);
        $id = $this->db->insert_id();

        $pesan = 'Pendaftaran ' . strtoupper($jenis) . ' berhasil dikirim. Cek status pendaftaran di halaman akun Anda.';

        // Berkas disimpan di luar webroot, hanya bisa dibuka admin lewat
        // Admin_Kemitraan::lihat_dokumen() - dulu di .assets/uploads/ yang bisa
        // diakses HTTP langsung. Pendaftaran tetap tersimpan kalau berkasnya
        // gagal; pendaftar diberi tahu apa adanya, bukan dibiarkan mengira
        // lampirannya sudah masuk.
        //
        // Proposal HANYA untuk magang (keputusan user 30 Jul). Field-nya juga
        // tidak dirender di formulir KKN, tapi pemeriksaan diulang di sini:
        // yang menentukan apa yang tersimpan adalah server, bukan formulir yang
        // dikirim peramban.
        $berkas = ['file_surat_pengantar' => 'Surat pengantar'];
        if ($jenis === 'magang') { $berkas['file_proposal'] = 'Proposal'; }

        $simpan = [];
        $galat_berkas = [];
        foreach ($berkas as $field => $label) {
            $galat = NULL;
            $nama_berkas = $this->store_private_upload($field, 'kemitraan', $id, $galat);
            if ($nama_berkas) {
                $simpan[$field] = $nama_berkas;
            } elseif ($galat) {
                // Disimpan PER FIELD. Satu `$galat` bersama akan berisi galat
                // berkas TERAKHIR saat pesannya dibaca - untuk magang itu
                // proposal, padahal yang ditanyakan surat pengantar.
                $galat_berkas[$field] = $galat;
                $pesan .= ' Namun ' . strtolower($label) . ' gagal diunggah (' . $galat . ') - hubungi admin untuk menyusulkan.';
            }
        }

        // Penjaga KEDUA. Yang pertama memastikan ada berkas TERKIRIM; ini
        // memastikan berkas itu benar-benar MENDARAT. Kalau ditolak karena
        // kebesaran atau formatnya salah, pendaftaran magangnya batal
        // seluruhnya - dibiarkan, ia melanjutkan perjalanan tanpa dokumen yang
        // barusan dinyatakan wajib, dengan pesan yang cuma menyuruh "hubungi
        // admin untuk menyusulkan".
        //
        // Divalidasi lewat store_private_upload() yang sama, bukan pemeriksaan
        // ukuran/MIME kedua di sini: dua definisi "berkas yang sah" akan
        // berbeda pendapat cepat atau lambat.
        if ($jenis === 'magang' && empty($simpan['file_surat_pengantar'])) {
            // Proposal bisa saja sudah mendarat lebih dulu - ikut dibuang supaya
            // tidak ada berkas yatim di folder pendaftaran yang barusan dihapus.
            foreach ($simpan as $nama_berkas) {
                $jalur = $this->private_upload_dir('kemitraan', $id) . basename($nama_berkas);
                if (is_file($jalur)) { @unlink($jalur); }
            }
            @rmdir($this->private_upload_dir('kemitraan', $id));
            $this->db->where('id', $id)->delete('kkn_magang_pendaftaran');

            $sebab = $galat_berkas['file_surat_pengantar'] ?? NULL;
            $this->tolak_pendaftaran($jenis,
                'Surat pengantar gagal diunggah' . ($sebab ? ' (' . $sebab . ')' : '')
                . ', jadi pendaftaran magang belum tersimpan. Perbaiki berkasnya lalu kirim ulang.');
            return;
        }

        if ($simpan) {
            $this->db->where('id', $id)->update('kkn_magang_pendaftaran', $simpan);
        }

        $this->session->set_flashdata('success', $pesan);
        redirect('akun');
    }

    /**
     * Periksa periode dan slot. Kembalikan NULL kalau boleh, atau pesan alasan.
     *
     * Dipakai `simpan()` (pendaftaran baru) DAN `simpan_ubah()` (mahasiswa
     * menyunting miliknya). Dua salinan pemeriksaan yang sama akan berselisih,
     * dan yang longgar selalu yang menang - di sini yang longgar berarti
     * mahasiswa bisa memindahkan dirinya ke bulan yang tertutup lewat layar
     * sunting, melewati penjagaan yang sudah dipasang di pendaftaran.
     *
     * @param string $divisi_atau_tema  DIUBAH jadi nama kanonik dari tabel.
     * @param int|null $abaikan_id      Baris yang tidak ikut dihitung - dipakai
     *   saat menyunting, supaya pendaftaran tidak menghalangi dirinya sendiri.
     */
    private function periksa_slot($jenis, &$divisi_atau_tema, &$bidang_kode, $mulai, $selesai, $abaikan_id = NULL)
    {
        // Urutan periode tidak pernah diperiksa sebelumnya, jadi pendaftaran
        // yang selesai mendahului mulainya bisa tersimpan begitu saja. Selain
        // salah pada dirinya sendiri, ia membuat penelusuran hari di bawah
        // tidak punya arah.
        if ($selesai < $mulai) {
            return 'Periode selesai tidak boleh mendahului periode mulai.';
        }

        if ($this->slot->periode_terlalu_panjang($mulai, $selesai)) {
            return 'Periode terlalu panjang. Maksimal ' . Kemitraan_slot_model::BATAS_HARI . ' hari.';
        }

        // Slot HANYA mengikat magang. Untuk KKN, field yang sama berarti tema
        // kegiatan - teks bebas yang tidak punya bidang untuk dicocokkan.
        if ($jenis !== 'magang') { $bidang_kode = NULL; return NULL; }

        // Formulir mengirim KODE bidang, bukan namanya. Nama bisa berubah;
        // kode adalah kunci yang tersimpan di pendaftaran dan dipakai routing
        // tinjauan tahap dua.
        $bidang = $this->slot->bidang_by_kode($divisi_atau_tema);
        if ( ! $bidang || (int) $bidang->aktif !== 1) {
            // Formulir merender select, tapi tidak merender pilihan bukan
            // penjagaan - siapa pun bisa mengirim nilai lain dari peramban.
            return 'Bidang yang dipilih tidak tersedia. Silakan pilih dari daftar yang ditawarkan.';
        }

        $halangan = $this->slot->bulan_terhalang($bidang, $mulai, $selesai, $abaikan_id);
        if ($halangan) {
            return html_escape($bidang->nama)
                . ' tidak membuka slot pada: ' . html_escape(implode(', ', $halangan))
                . '. Sesuaikan periode atau pilih bidang lain.';
        }

        // Yang tersimpan: KODE di kolomnya sendiri, dan NAMA di
        // `divisi_atau_tema` supaya layar lama tetap terbaca apa adanya.
        $bidang_kode      = $bidang->kode;
        $divisi_atau_tema = $bidang->nama;
        return NULL;
    }

    /**
     * Pulangkan pendaftar ke formulirnya dengan alasan yang bisa dibaca.
     *
     * Isian belum dikembalikan di sini - formulir ini belum punya mekanisme
     * seperti Auth::_onboarding_fail(). ponytail: satu tempat memulangkan,
     * jadi kalau nanti isian ikut dipulihkan, cukup satu method yang diubah.
     */
    private function tolak_pendaftaran($jenis, $pesan)
    {
        $this->session->set_flashdata('error', $pesan);
        redirect('KemitraanPortal/daftar/' . $jenis);
    }

    private function akses_mahasiswa($target)
    {
        if ( ! $this->is_logged_in()) {
            $this->session->set_flashdata('error', 'Silakan masuk atau daftar akun mahasiswa terlebih dahulu.');
            // $target diserahkan ke gerbang, bukan ditulis sendiri ke sesi:
            // satu mekanisme, dan penyaringnya ikut berlaku di sini juga.
            $this->gerbang_login($target);
            return FALSE;
        }
        if ($this->session->userdata('role') !== 'mahasiswa') {
            $this->session->set_flashdata('error', 'Pendaftaran KKN/Magang hanya tersedia untuk akun dengan peran mahasiswa.');
            redirect('akun');
            return FALSE;
        }
        return TRUE;
    }
}
