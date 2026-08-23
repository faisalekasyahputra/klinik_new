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

    // =========================================================
    // DASHBOARD KKN UNIVERSITAS - permintaan user 21 Agt 2026, PENGGANTI
    // formulir sekali-daftar (daftar('kkn')/simpan() lama, lihat komentar
    // di keduanya). Satu akun universitas mengelola BANYAK KKN dari waktu
    // ke waktu di sini, masing-masing dengan roster pesertanya sendiri.
    // Magang TIDAK ikut - jalur satu-kali-daftar lamanya tetap utuh dan
    // tidak disentuh apa pun di bawah ini.
    // =========================================================

    public function kkn_dashboard()
    {
        if ( ! $this->akses_universitas('KemitraanPortal/kkn_dashboard')) { return; }

        // "Jumlah Peserta" DIHITUNG dari kkn_peserta, TIDAK disimpan sebagai
        // kolom tersendiri - lihat alasan lengkap di migrasi 044.
        $daftar_kkn = $this->db
            ->select('kkn_magang_pendaftaran.*, (SELECT COUNT(*) FROM kkn_peserta
                WHERE kkn_peserta.pendaftaran_id = kkn_magang_pendaftaran.id) AS jumlah_peserta', FALSE)
            ->where(['user_id' => $this->get_user_id(), 'jenis' => 'kkn'])
            ->order_by('created_at', 'DESC')
            ->get('kkn_magang_pendaftaran')->result();

        // Shell dashboard TERPADU (admin/index, sama dengan Status Pengajuan/
        // Profil Saya), BUKAN shell portal publik - keputusan user 21 Agt
        // 2026: "Desainnya samakan dengan yang ini" (menunjuk /akun).
        $this->render_user_dashboard('pages/kemitraan_portal/kkn_dashboard', [
            'title'      => 'Dashboard KKN',
            'nama_akun'  => (string) $this->session->userdata('name'),
            'email_akun' => (string) $this->session->userdata('email'),
            'daftar_kkn' => $daftar_kkn,
        ]);
    }

    public function kkn_tambah()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }
        if ( ! $this->akses_universitas('KemitraanPortal/kkn_dashboard')) { return; }

        /* Satu jalan pulang untuk SEMUA penolakan di method ini, sekaligus
           menandai dashboard supaya membuka ULANG modal Tambah KKN -
           tanpa penanda ini pengguna melihat toast galat tanpa tahu
           formulir mana yang dimaksud (modal sudah tertutup begitu form
           di-submit). Flag TERPISAH dari isi pesan galat dengan sengaja -
           menyimpulkannya dari kata dalam pesan (mis. mencari "wajib")
           rapuh dan akan meleset begitu ada pesan galat baru yang lupa
           memakai kata itu. */
        $tolak = function ($pesan) {
            $this->session->set_flashdata('error', $pesan);
            $this->session->set_flashdata('kkn_tambah_gagal', TRUE);
            redirect('KemitraanPortal/kkn_dashboard');
        };

        if ($this->form_validation->run('kkn_tambah') === FALSE) {
            $tolak(validation_errors('<li>', '</li>'));
            return;
        }

        $mulai   = $this->input->post('periode_mulai', TRUE);
        $selesai = $this->input->post('periode_selesai', TRUE);
        if ($selesai < $mulai) {
            $tolak('Periode selesai tidak boleh mendahului periode mulai.');
            return;
        }
        if ($this->slot->periode_terlalu_panjang($mulai, $selesai)) {
            $tolak('Periode terlalu panjang. Maksimal ' . Kemitraan_slot_model::BATAS_HARI . ' hari.');
            return;
        }

        /* Nomor HP diambil dari PROFIL AKUN (usr_users.phone), bukan
           diminta ulang di formulir ini - formulir Tambah KKN cuma
           periode+keterangan+dua surat (permintaan user 21 Agt 2026).
           Kalau belum diisi, pemohon diarahkan melengkapi Profil Saya
           dulu - inilah yang membuat tombol "Ubah Profil" di dashboard
           berguna sungguhan, bukan sekadar hiasan. */
        $telp = trim((string) $this->db->select('phone')
            ->get_where('usr_users', ['id' => $this->get_user_id()])->row('phone'));
        if ($telp === '') {
            $tolak('Lengkapi Nomor HP/WhatsApp di Profil Saya sebelum menambah KKN.');
            return;
        }

        /* Kedua surat WAJIB sejak dashboard ini - beda dari formulir lama
           yang melonggarkan surat pengantar jadi opsional untuk KKN, lalu
           akhirnya menghapusnya sama sekali dari formulir itu (sesi yang
           sama). Persyaratan dashboard ini justru mengembalikannya, KALI
           INI konsisten dengan teks persyaratan yang sudah tertulis di
           kkn.php sejak 3 Agt 2026 - dua surat dari kampus, bukan satu. */
        foreach ([
            'file_surat_pengantar' => 'Surat permohonan menjadi mitra',
            'file_surat_simperum'  => 'Surat permohonan akun SIMPERUM',
        ] as $field => $label) {
            if ( ! isset($_FILES[$field]) || (int) $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
                $tolak($label . ' wajib dilampirkan.');
                return;
            }
            // Dua surat ini WAJIB PDF (permintaan user) - lebih sempit
            // dari store_private_upload() yang juga menerima JPG/PNG untuk
            // domain lain (Aduan, SRP2). Diperiksa DI SINI supaya pesannya
            // spesifik "wajib PDF", bukan pesan generik helper bersama itu.
            $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
            if ($ext !== 'pdf') {
                $tolak($label . ' wajib berformat PDF.');
                return;
            }
        }

        $this->db->insert('kkn_magang_pendaftaran', [
            'user_id'          => $this->get_user_id(),
            'jenis'            => 'kkn',
            // Data pribadi SATU mahasiswa - tidak berlaku, dashboard ini
            // murni data kampus (permintaan user 21 Agt 2026).
            'nim'              => NULL,
            'tempat_lahir'     => NULL,
            'tanggal_lahir'    => NULL,
            'semester'         => NULL,
            'jurusan'          => NULL,
            // Nama universitas diambil dari akun - sudah disamakan dengan
            // nama akun sejak keputusan user sebelumnya di sesi yang sama
            // (KemitraanPortal::simpan()/simpan_ubah() lama), bukan
            // diminta ulang di formulir ini.
            'instansi_asal'    => (string) $this->session->userdata('name'),
            'no_hp'            => $telp,
            'divisi_atau_tema' => $this->input->post('keterangan', TRUE),
            'bidang_kode'      => NULL,
            'periode_mulai'    => $mulai,
            'periode_selesai'  => $selesai,
            'status'           => 'Diajukan',
            'file_surat_pengantar' => NULL,
            'file_surat_simperum'  => NULL,
            'file_proposal'        => NULL,
        ]);
        $id = $this->db->insert_id();

        $simpan = [];
        $galat_berkas = NULL;
        foreach (['file_surat_pengantar', 'file_surat_simperum'] as $field) {
            $galat = NULL;
            $nama_berkas = $this->store_private_upload($field, 'kemitraan', $id, $galat);
            if ($nama_berkas) {
                $simpan[$field] = $nama_berkas;
            } else {
                $galat_berkas = $galat ?: 'Berkas tidak dapat disimpan.';
                break;
            }
        }

        /* Kedua surat WAJIB MENDARAT, bukan cuma terkirim - kalau salah
           satu gagal (kebesaran/rusak saat MIME diperiksa ulang di
           store_private_upload()), baris ini dibuang SELURUHNYA.
           Membiarkannya berdiri dengan satu/nol surat berarti KKN
           "Diajukan" tanpa satu pun dokumen yang dijanjikan
           persyaratannya - meja admin meninjau sesuatu yang belum
           benar-benar lengkap. Pola sama dengan surat pengantar magang
           di simpan() lama. */
        if (count($simpan) < 2) {
            foreach ($simpan as $nama_berkas) {
                $jalur = $this->private_upload_dir('kemitraan', $id) . basename($nama_berkas);
                if (is_file($jalur)) { @unlink($jalur); }
            }
            @rmdir($this->private_upload_dir('kemitraan', $id));
            $this->db->where('id', $id)->delete('kkn_magang_pendaftaran');

            $tolak('Salah satu berkas gagal diunggah' . ($galat_berkas ? ' (' . $galat_berkas . ')' : '')
                . ', jadi KKN ini belum tersimpan. Perbaiki berkasnya lalu kirim ulang.');
            return;
        }

        $this->db->where('id', $id)->update('kkn_magang_pendaftaran', $simpan);

        $this->session->set_flashdata('success',
            'KKN baru berhasil diajukan. Tim kami akan meninjau kedua surat yang dilampirkan.');
        redirect('KemitraanPortal/kkn_dashboard');
    }

    /**
     * Unggah/ganti roster peserta KKN dari berkas Excel.
     *
     * MENGGANTI seluruh roster lama, bukan menambah - unggahan ini
     * dianggap daftar TERKINI (permintaan user 21 Agt 2026). Kalau
     * universitas cuma perlu menambah satu nama, mereka mengunggah ulang
     * seluruh berkas dengan nama itu ditambahkan - lebih sederhana dan
     * lebih jujur daripada mekanisme tambah-satu-per-satu yang bisa
     * menyimpang dari berkas sumber yang mereka simpan sendiri.
     */
    public function kkn_upload_peserta($id = NULL)
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }
        $row = $this->pendaftaran_milik($id);
        if ( ! $row) { return; }
        if ($row->jenis !== 'kkn') { show_404(); }

        if (empty($_FILES['file_peserta']['name'])) {
            $this->session->set_flashdata('error', 'Pilih berkas daftar peserta terlebih dahulu.');
            redirect('KemitraanPortal/pendaftaran/' . (int) $row->id);
            return;
        }
        $file = $_FILES['file_peserta'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->session->set_flashdata('error', 'Berkas gagal diunggah.');
            redirect('KemitraanPortal/pendaftaran/' . (int) $row->id);
            return;
        }
        // Batas sama dengan berkas lain di modul ini (store_private_upload()
        // default 5 MB) - satu batas ukuran untuk seluruh domain kemitraan.
        if ($file['size'] > 5242880) {
            $this->session->set_flashdata('error', 'Ukuran berkas melebihi 5 MB.');
            redirect('KemitraanPortal/pendaftaran/' . (int) $row->id);
            return;
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ( ! in_array($ext, ['xls', 'xlsx'], TRUE)) {
            $this->session->set_flashdata('error', 'Jenis berkas tidak didukung. Gunakan XLS atau XLSX.');
            redirect('KemitraanPortal/pendaftaran/' . (int) $row->id);
            return;
        }

        $this->load->library('kkn_peserta_import');
        $hasil = $this->kkn_peserta_import->baca($file['tmp_name']);
        if (empty($hasil['success'])) {
            $this->session->set_flashdata('error', $hasil['message']);
            redirect('KemitraanPortal/pendaftaran/' . (int) $row->id);
            return;
        }

        // Transaksional: hapus lalu isi ulang harus sukses BERSAMA, supaya
        // roster tidak pernah berhenti kosong-sesaat kalau insert_batch()
        // gagal di tengah jalan (mis. koneksi terputus).
        $this->db->trans_start();
        $this->db->delete('kkn_peserta', ['pendaftaran_id' => $row->id]);
        $baris = [];
        foreach ($hasil['peserta'] as $p) {
            $baris[] = [
                'pendaftaran_id' => $row->id,
                'nim'            => $p['nim'],
                'nama'           => $p['nama'],
                'created_at'     => date('Y-m-d H:i:s'),
            ];
        }
        $this->db->insert_batch('kkn_peserta', $baris);
        $this->db->trans_complete();

        $this->session->set_flashdata(
            $this->db->trans_status() === FALSE ? 'error' : 'success',
            $this->db->trans_status() === FALSE
                ? 'Gagal menyimpan daftar peserta. Coba lagi.'
                : count($baris) . ' peserta berhasil disimpan.'
        );
        redirect('KemitraanPortal/pendaftaran/' . (int) $row->id);
    }

    // =========================================================
    // CETAK SERTIFIKAT KKN - permintaan user 22 Agt 2026.
    //
    // TANPA login dengan sengaja (keputusan user): mahasiswa peserta KKN
    // belum tentu punya akun sendiri di sistem ini - akun yang ada adalah
    // akun UNIVERSITAS (role 'universitas'), bukan per-mahasiswa. Karena
    // itu dicari lewat NIM, bukan lewat sesi login.
    //
    // Ini pencarian data pribadi TANPA login yang bisa dienumerasi (NIM
    // sengaja dicoba satu-satu akan membocorkan siapa terdaftar KKN, kapan,
    // dan status kelulusannya) - kelas risiko yang SAMA dengan cek-tiket
    // yang pernah dicabut (lihat riwayat commit b9bdc93). Bedanya di sini
    // sengaja dipertahankan (bukan luput), dengan dua pengaman:
    //   1. rate_limit sertifikat_kkn_lookup (5/jam per IP, lihat
    //      rate_limits.php) - anti-enumerasi sungguhan, bukan sekadar
    //      penahan spam.
    //   2. Hasil GAGAL tidak pernah menyebut nama/universitas - cuma
    //      status generik. Nama & data lengkap CUMA muncul di hasil
    //      BERHASIL, yang berarti NIM itu memang sudah terverifikasi
    //      lulus KKN dan sertifikatnya memang untuk pemilik NIM tsb.
    //
    // TEMPLATE SERTIFIKAT RESMI BELUM ADA (menyusul dari user) - hasil
    // berhasil untuk sementara menampilkan data yang sudah terverifikasi
    // dalam tata letak siap-cetak sederhana, bukan desain resmi. Data yang
    // ditampilkan sudah final (nama, NIM, universitas, tema, periode);
    // yang menyusul cuma tampilannya.
    // =========================================================

    public function sertifikat_kkn()
    {
        $this->render('pages/kemitraan_portal/sertifikat_kkn', ['judul' => 'Cetak Sertifikat KKN']);
    }

    public function cek_sertifikat_kkn()
    {
        /* Redirect BALIK ke formulir pencarian, bukan show_404() - keluhan
           user 23 Agt 2026: refresh halaman hasil (GET, bukan POST yang
           sama seperti submit form) mendarat di 404 polos yang membingungkan.
           NIM tetap TIDAK PERNAH muncul di URL manapun (redirect ini tanpa
           query string), jadi larangan "hasil pencarian tidak boleh jadi
           tautan yang bisa disimpan/dibagikan" masih tegak - yang berubah
           cuma GET ke sini sekarang mendarat di formulir yang benar,
           bukan halaman kosong tanpa jalan keluar. */
        if ($this->input->method(TRUE) !== 'POST') {
            redirect('KemitraanPortal/sertifikat_kkn');
            return;
        }

        $rate = $this->rate_limit_consume('sertifikat_kkn_lookup');
        if (empty($rate['success']) || empty($rate['allowed'])) {
            $this->rate_limit_reject(
                $rate,
                'Terlalu banyak percobaan pencarian. Silakan coba lagi sebentar.',
                $this->input->is_ajax_request()
            );
            return;
        }

        $nim = trim((string) $this->input->post('nim', TRUE));
        if ($nim === '' || ! preg_match('/^[A-Za-z0-9]{1,30}$/', $nim)) {
            $this->session->set_flashdata('error', 'NIM tidak valid. Periksa kembali dan coba lagi.');
            redirect('KemitraanPortal/sertifikat_kkn');
            return;
        }

        /* Baris TERBARU saja (ORDER BY periode_selesai DESC LIMIT 1) - kasus
           satu NIM ikut KKN lebih dari sekali memang mungkin (KKN periode
           berulang tiap tahun), dan yang paling relevan untuk dicetak
           adalah keikutsertaan yang paling akhir. */
        $baris = $this->db
            ->select('kkn_peserta.id AS id_peserta, kkn_peserta.nama AS nama_peserta, kkn_peserta.nim,
                kkn_magang_pendaftaran.instansi_asal, kkn_magang_pendaftaran.divisi_atau_tema,
                kkn_magang_pendaftaran.periode_mulai, kkn_magang_pendaftaran.periode_selesai,
                kkn_magang_pendaftaran.status')
            ->from('kkn_peserta')
            ->join('kkn_magang_pendaftaran', 'kkn_magang_pendaftaran.id = kkn_peserta.pendaftaran_id')
            ->where(['kkn_peserta.nim' => $nim, 'kkn_magang_pendaftaran.jenis' => 'kkn'])
            ->order_by('kkn_magang_pendaftaran.periode_selesai', 'DESC')
            ->limit(1)
            ->get()->row();

        $tolak = function ($pesan) {
            $this->session->set_flashdata('error', $pesan);
            redirect('KemitraanPortal/sertifikat_kkn');
        };

        // Pesan GAGAL sengaja generik dan TIDAK menyebut nama/universitas -
        // lihat catatan anti-enumerasi di kepala berkas ini.
        if ( ! $baris) {
            $tolak('NIM tidak ditemukan dalam data peserta KKN.');
            return;
        }
        if ($baris->status !== 'Diterima') {
            $tolak('Sertifikat belum dapat diterbitkan untuk NIM ini.');
            return;
        }
        if (empty($baris->periode_selesai) || strtotime($baris->periode_selesai) >= strtotime('today')) {
            // Tanggal selesai BOLEH disebutkan di sini - berbeda dari dua
            // kegagalan di atas, cabang ini sudah memastikan NIM tsb memang
            // pemilik pendaftaran yang diterima, jadi memberi tahu kapan
            // sertifikatnya bisa dicetak adalah informasi yang MEMANG untuk
            // pemilik NIM itu, bukan bocoran ke penebak.
            $tolak('KKN Anda belum melewati periode pelaksanaan. Sertifikat dapat dicetak mulai '
                . tgl_id($baris->periode_selesai) . '.');
            return;
        }

        /* Disimpan ke SESI, bukan dikirim lewat query string - tombol Cetak
           (permintaan user 22 Agt 2026: "mengarah ke tab baru") membuka
           cetak_sertifikat_kkn() di tab terpisah, dan NIM di URL akan
           tersimpan di riwayat/log peramban serta bisa dibagikan tanpa
           sengaja. Sesi memang bisa dibaca ulang dari tab manapun milik
           peramban yang sama, cocok untuk "buka di tab baru". Ditimpa
           begitu saja tiap pencarian baru berhasil - tidak perlu dibersihkan
           manual, dan tidak pernah bocor ke NIM lain karena tiap pencarian
           menuliskan datanya sendiri. */
        $this->session->set_userdata('sertifikat_kkn_cetak', (array) $baris);

        $this->render('pages/kemitraan_portal/sertifikat_kkn_hasil', [
            'judul' => 'Sertifikat KKN',
            'data'  => $baris,
        ]);
    }

    /**
     * Tab cetak terpisah - permintaan user 22 Agt 2026 ("buat tombol cetak
     * mengarah ke tab baru"). Sengaja TIDAK menyunting apa pun sendiri: baca
     * saja dari sesi yang sudah ditulis cek_sertifikat_kkn() SETELAH lolos
     * seluruh pemeriksaan (NIM cocok, status Diterima, periode sudah lewat)
     * - method ini tidak menjalankan pemeriksaan itu lagi.
     *
     * Dirender lewat load->view() LANGSUNG, bukan render() - halaman cetak
     * ini sengaja polos (tanpa navbar/sidebar portal), karena tab baru ini
     * memang cuma untuk dicetak/disimpan sebagai PDF.
     */
    public function cetak_sertifikat_kkn()
    {
        $data = $this->session->userdata('sertifikat_kkn_cetak');
        if (empty($data)) {
            $this->session->set_flashdata('error', 'Tidak ada data sertifikat untuk dicetak. Cari NIM Anda terlebih dahulu.');
            redirect('KemitraanPortal/sertifikat_kkn');
            return;
        }

        $this->load->view('pages/kemitraan_portal/cetak_sertifikat_kkn', [
            'judul' => 'Cetak Sertifikat KKN',
            'data'  => (object) $data,
        ]);
    }

    /**
     * PDF sertifikat sungguhan - permintaan user 22 Agt 2026 ("isi dengan
     * embed pdf", lalu "ganti isi pdf nya dengan file jpg itu"). Satu
     * halaman, dibangun FPDF di atas TEMPLATE RESMI dinas
     * (assets/img/template_sertifikat_kkn.jpg, disimpan user 22 Agt 2026)
     * dari sesi yang SAMA dengan cetak_sertifikat_kkn() - guard identik,
     * TIDAK memeriksa NIM ulang di sini.
     *
     * KOORDINAT PLACEHOLDER DIUKUR MANUAL dari piksel templatenya (1753x1240,
     * rasio A4 lanskap persis - ~5.904 px/mm), BUKAN ditebak: setiap kotak
     * di bawah di-crop + diberi penggaris piksel lalu diperiksa satu per
     * satu sebelum angkanya ditulis di sini. Tetap PERKIRAAN TERBAIK dari
     * pengukuran visual, bukan dari berkas desain sumber (.ai/.psd) yang
     * tidak tersedia - kalau templatenya diganti/diedit ulang, koordinat
     * ini HARUS diukur ulang, bukan diwariskan begitu saja.
     *
     * Output 'I' (inline) - dikirim sebagai application/pdf langsung ke
     * body respons supaya <embed>/<iframe> di cetak_sertifikat_kkn.php bisa
     * menampilkannya, bukan memaksa unduhan.
     */
    public function sertifikat_kkn_pdf()
    {
        /* require_once LANGSUNG, bukan mengandalkan classmap composer -
           setasign/fpdf 1.9.0 MENYATAKAN classmap "fpdf.php" di
           composer.json-nya sendiri, tapi entah kenapa tidak pernah
           mendarat di vendor/composer/autoload_classmap.php walau sudah
           composer dump-autoload berkali-kali di lingkungan ini (diverifikasi
           langsung: entri "FPDF" nihil di classmap yang dihasilkan).
           require_once pada berkas tunggal ini AMAN dipakai berulang -
           gaya pakai FPDF yang paling umum di luar Composer justru begini,
           dan idempoten (PHP tidak mendefinisikan ulang kelas yang sudah ada). */
        require_once FCPATH . 'vendor/setasign/fpdf/fpdf.php';

        $data = $this->session->userdata('sertifikat_kkn_cetak');
        if (empty($data)) { show_404(); return; }
        $data = (object) $data;

        $template = FCPATH . 'assets/img/template_sertifikat_kkn.jpg';
        if ( ! is_file($template)) {
            // Template WAJIB ada - bukan sesuatu yang boleh diam-diam
            // jatuh ke rancangan lama, karena rancangan lama itu sudah
            // dilepas sepenuhnya (bukan cuma tidak dipakai).
            log_message('error', 'sertifikat_kkn_pdf: template hilang di ' . $template);
            show_404();
            return;
        }

        // FPDF (font bawaan Arial/Times) TIDAK mengenal UTF-8 - dikonversi
        // ke Windows-1252 sekali di sini, bukan menulis konversi berulang
        // di tiap Cell(). //TRANSLIT menjatuhkan karakter yang benar-benar
        // tidak punya padanan (mis. emoji) alih-alih gagal total.
        $t = function ($s) {
            $s = (string) ($s ?? '');
            $hasil = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $s);
            return $hasil !== FALSE ? $hasil : $s;
        };

        $pdf = new FPDF('L', 'mm', 'A4'); // 297 x 210mm - rasio PERSIS template
        $pdf->SetTitle('Sertifikat KKN Kemitraan - ' . $data->nama_peserta);
        $pdf->SetAutoPageBreak(FALSE);

        /* Font skrip untuk <<Nama Lengkap>> - permintaan user 22 Agt 2026,
           mendekati kaligrafi biru navy templatenya jauh lebih dekat
           daripada Times italic bawaan FPDF. Berkasnya
           (BrittanySignature.json + .z, dibangkitkan SEKALI dari .ttf lewat
           vendor/setasign/fpdf/makefont/makefont.php - lihat catatan di
           application/fonts/) DIBACA DARI application/fonts/, bukan
           vendor/ - font pihak ketiga yang diunggah user, bukan bagian
           paket FPDF itu sendiri. */
        $pdf->AddFont('BrittanySignature', '', 'BrittanySignature.json', APPPATH . 'fonts/');

        /* Font utk kalimat "Atas partisipasinya... dalam kegiatan :" -
           permintaan user 23 Agt 2026, dipilih user sendiri untuk mendekati
           Cormorant Garamond yang dipakai versi HTML+print (Versi B) yang
           dibandingkan sebelum ini. Dipakai instans STATIS "Regular" dari
           paket variable-font yang dilampirkan user
           (Cormorant_Garamond/static/CormorantGaramond-Regular.ttf, BUKAN
           file variable-font utamanya) - makefont.php FPDF tidak
           mendukung sumbu variabel; instans statis satu berat inilah yang
           sungguh dipakai untuk merender, bukan cuma nama filenya yang mirip. */
        $pdf->AddFont('CormorantGaramond', '', 'CormorantGaramond-Regular.json', APPPATH . 'fonts/');

        $pdf->AddPage();
        $pdf->Image($template, 0, 0, 297, 210, 'JPG');

        /* GANTI TEMPLATE 23 Agt 2026 - user memberi file sumber aslinya
           (PIAGAM PENGHARGAAN KKN KEMITRAAN 2025 COBA.pdf, lihat folder
           Downloads/SLAMRET1/). Dibongkar dengan smalot/pdfparser: file
           itu ternyata punya LAPISAN TERPISAH - satu gambar latar yang
           SUNGGUH KOSONG (tanpa "<<ID>>"/"<<Nama Lengkap>>"/kalimat
           Universitas ter-bake sama sekali, diekstrak jadi
           assets/img/template_sertifikat_kkn.jpg yang sekarang, 2000x1414px)
           plus lapisan teks vektor terpisah untuk keempat field itu di
           atasnya. File JPG LAMA (semua placeholder ter-bake jadi piksel,
           sumber segala kotak-penutup-warna-sampel di bawah sebelumnya)
           disimpan sebagai template_sertifikat_kkn_lama_baked.jpg untuk
           rujukan/rollback, tidak lagi dipakai kode ini.

           Konsekuensinya: TIDAK ADA LAGI kotak penutup warna-sampel sama
           sekali - background di sini sudah benar-benar kosong di keempat
           area, jadi teks tinggal ditulis langsung tanpa perlu menutupi
           apa pun (akar masalah "kelihatan ditempel" dari kotak-warna-
           sampel sebelumnya otomatis tidak relevan lagi). Koordinat di
           bawah BUKAN hasil ukur visual/pixel-ruler lagi, tapi dihitung
           dari matriks transformasi PDF asli (rangkaian q/cm/Tm di
           content stream file sumbernya) - lihat riwayat sesi untuk
           skrip penelusurannya. */

        // "Nomor : 600.2/69." + <<ID>> - baris ini SELURUHNYA tidak ada di
        // background baru (dulu ter-bake penuh termasuk "<<ID>>"), jadi
        // labelnya ikut ditulis di sini. <<ID>> memakai kkn_peserta.id
        // (permintaan user 23 Agt 2026) - BUKAN NIM lagi seperti sebelumnya,
        // supaya nomor surat terlihat seperti nomor urut asli (pendek,
        // berurutan sesuai baris di DB), bukan NIM 10-16 digit yang
        // membuatnya terlihat aneh sebagai "nomor surat". Text() FPDF
        // memakai (x,y) SEBAGAI TITIK DASAR/BASELINE teks - persis makna
        // Tm di PDF, jadi angka hasil penelusuran matriks bisa dipakai
        // langsung tanpa konversi tambahan.
        $pdf->SetFont('Times', '', 13);
        $pdf->SetTextColor(10, 10, 10);
        $pdf->Text(121.34, 62.95, $t('Nomor : 600.2/69.' . $data->id_peserta));

        // <<Nama Lengkap>> - font BrittanySignature (diunggah user 22 Agt
        // 2026), mendekati skrip/kursif biru navy templatenya. Dipusatkan
        // manual (GetStringWidth) terhadap TITIK TENGAH KERTAS SUNGGUHAN
        // (148.5mm, dari 297mm lebar A4 landscape) - bukan titik tengah
        // kotak 50-207mm yang dipakai sebelumnya, yang pusatnya di 128.5mm
        // dan membuat nama terlihat bergeser ~20mm ke kiri dibanding garis
        // titik-titik di bawahnya (keluhan user 23 Agt 2026, dan diverifikasi
        // dengan mengukur ulang garis titik-titik di background baru: pusat
        // sungguhannya 148.43mm, cocok dengan titik tengah halaman, bukan
        // 128.5mm). Baseline asli dari file sumber (x=93.85mm) TETAP tidak
        // dipakai untuk pemusatan - itu cuma valid untuk teks placeholder
        // "<<Nama Lengkap>>" itu sendiri, bukan patokan nama sungguhan yang
        // panjangnya bervariasi per mahasiswa. Ukuran huruf MENGECIL
        // OTOMATIS kalau nama kepanjangan - satu-satunya cara nama yang
        // sangat panjang tidak meluber ke luar kertas.
        $nama = $t($data->nama_peserta);
        $namaUkuran = 44;
        $pdf->SetFont('BrittanySignature', '', $namaUkuran);
        while ($pdf->GetStringWidth($nama) > 155 && $namaUkuran > 20) {
            $namaUkuran -= 0.5;
            $pdf->SetFont('BrittanySignature', '', $namaUkuran);
        }
        $pdf->SetTextColor(19, 61, 103);
        $namaX = 148.5 - $pdf->GetStringWidth($nama) / 2;
        $pdf->Text($namaX, 95.31, $nama);

        /* Kalimat "Atas partisipasinya ... dan <<Universitas>> dalam
           kegiatan :" - dulu diperlakukan sebagai satu kotak sempit 35mm
           untuk nama universitas SAJA karena sisa kalimatnya ter-bake di
           background lama. Sekarang SELURUH kalimat itu tidak ada di
           background baru, jadi ditulis penuh di sini sebagai satu
           paragraf rata-tengah (MultiCell) dengan nama universitas
           disisipkan di tengah. Rentang x=37.6-259.4mm diambil dari
           baseline dua baris kalimat ini di file sumber (37.6mm & 97.6mm
           dari margin kiri).

           WAJIB MUAT 2 BARIS, tidak boleh lebih - diukur langsung di
           background baru: jarak dari garis titik-titik (~104.7mm) ke
           judul kegiatan tetap "Verifikasi dan Validasi..." (~127mm)
           cuma cukup untuk 2 baris di ukuran wajar; baris ke-3 akan
           bertabrakan dengan judul itu (terbukti lewat preview GD saat
           nama universitas panjang dipaksa 16pt tetap - lihat riwayat
           sesi). Makanya ukuran font MENGECIL OTOMATIS (bukan wrap bebas
           seperti MultiCell biasa) sampai hasil lipatannya <=2 baris,
           persis pola yang sama dipakai untuk Nama Lengkap di atas. */
        $kalimat = 'Atas partisipasinya sebagai peserta Kuliah Kerja Nyata (KKN) Kemitraan Disperakim Provinsi Jawa Tengah dan '
            . $t($data->instansi_asal) . ' dalam kegiatan :';

        $hitung_baris = function ($teks, $lebar) use ($pdf) {
            $kata = explode(' ', $teks);
            $baris = 1;
            $baris_ini = '';
            foreach ($kata as $k) {
                $coba = $baris_ini === '' ? $k : $baris_ini . ' ' . $k;
                if ($pdf->GetStringWidth($coba) > $lebar - 2 && $baris_ini !== '') {
                    $baris++;
                    $baris_ini = $k;
                } else {
                    $baris_ini = $coba;
                }
            }
            return $baris;
        };

        $kalimatBoxW = 221.8;
        // Ukuran dasar 16pt: file sumber memakai 21.333 unit teks x skala
        // CTM 0.75 = 16pt EFEKTIF - dan sesi lain yang membongkar file yang
        // SAMA lewat PyMuPDF (page.get_text('dict')) menemukan angka
        // PERSIS sama, "Cormorant Garamond Regular, 16pt", jadi ini bukan
        // kebetulan dua taksiran beda ketemu sama, tapi dua metode
        // independen mengukur font placeholder aslinya. FPDF di sini
        // bekerja langsung dalam mm/pt tanpa CTM tersembunyi, jadi 16pt
        // dipakai apa adanya sebagai titik awal sebelum pengecekan
        // wajib-2-baris di bawah.
        $kalimatUkuran = 16;
        $pdf->SetFont('CormorantGaramond', '', $kalimatUkuran);
        while ($hitung_baris($kalimat, $kalimatBoxW) > 2 && $kalimatUkuran > 9) {
            $kalimatUkuran -= 0.5;
            $pdf->SetFont('CormorantGaramond', '', $kalimatUkuran);
        }
        $kalimatLineH = $kalimatUkuran * 0.42375; // rasio sama seperti 16pt->6.78mm

        $pdf->SetTextColor(10, 10, 10);
        $pdf->SetXY(37.6, 106.5);
        $pdf->MultiCell($kalimatBoxW, $kalimatLineH, $kalimat, 0, 'C');

        // Nama kegiatan ("Verifikasi dan Validasi Rumah Tidak Layak Huni")
        // - permintaan user 23 Agt 2026: BIARKAN APA ADANYA, tidak diganti
        // dinamis dari data->divisi_atau_tema. Kalimat ini sekarang bagian
        // TETAP dari gambar background (sudah ter-bake di file sumber),
        // jadi sengaja TIDAK ADA kode yang menulis/menutup area ini lagi.

        $pdf->Output('I', 'sertifikat-kkn-' . preg_replace('/[^A-Za-z0-9_-]/', '', $data->nim) . '.pdf');
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

        // Formulir sekali-daftar untuk KKN DIPENSIUNKAN - keputusan user
        // 21 Agt 2026 ("Ganti alur lama"). Satu akun universitas sekarang
        // mengelola KKN-nya (banyak periode, roster peserta, dua surat)
        // lewat kkn_dashboard(), bukan mendaftar sekali lewat formulir ini.
        // Magang TIDAK ikut - jalur lamanya utuh di bawah.
        if ($jenis === 'kkn') {
            redirect('KemitraanPortal/kkn_dashboard');
            return;
        }

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

        /* KKN dari dashboard baru (permintaan user 21 Agt 2026) tampil di
           template TERPISAH - roster peserta dan dua surat tidak punya
           padanan di pendaftaran.php lama (satu surat, tanpa roster).
           Magang TIDAK ikut - template lamanya utuh di bawah. */
        if ($row->jenis === 'kkn') {
            $peserta = $this->db->where('pendaftaran_id', $row->id)
                ->order_by('nama', 'ASC')->get('kkn_peserta')->result();

            // Shell dashboard TERPADU, sama dengan kkn_dashboard() - dijangkau
            // dari sana, jadi harus terlihat sebagai bagian dari layar yang
            // sama, bukan lompat balik ke tema portal publik.
            $this->render_user_dashboard('pages/kemitraan_portal/kkn_batch', [
                'title'     => 'Detail KKN',
                'row'       => $row,
                'peserta'   => $peserta,
                'bisa_batal' => $row->status === 'Diajukan',
            ]);
            return;
        }

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

        // KKN dari dashboard baru TIDAK bisa disunting lewat sini -
        // formulir ini (Jurusan/Universitas/No HP per-baris) dibangun untuk
        // model LAMA yang sudah dipensiunkan untuk KKN (permintaan user
        // 21 Agt 2026). Satu-satunya koreksi yang ditawarkan dashboard baru
        // adalah Batalkan - periode/keterangan yang keliru diajukan ulang
        // sebagai KKN baru, bukan disunting di tempat.
        if ($row->jenis === 'kkn') {
            $this->session->set_flashdata('error', 'KKN dari dashboard tidak bisa disunting. Batalkan pengajuan ini lalu ajukan ulang bila ada yang keliru.');
            redirect('KemitraanPortal/pendaftaran/' . (int) $row->id);
            return;
        }

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

        // Sama seperti ubah() - lihat komentar lengkap di sana.
        if ($row->jenis === 'kkn') { show_404(); }

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

        // Sama seperti simpan() - lihat komentar lengkap di sana.
        if ($row->jenis === 'magang') {
            $wajib_magang = [
                'nim' => 'NIM', 'tempat_lahir' => 'Tempat Lahir',
                'tanggal_lahir' => 'Tanggal Lahir', 'semester' => 'Semester',
                'periode_mulai' => 'Periode Mulai', 'periode_selesai' => 'Periode Selesai',
            ];
            foreach ($wajib_magang as $field => $label) {
                if (trim((string) $this->input->post($field, TRUE)) === '') {
                    $this->session->set_flashdata('error', $label . ' wajib diisi untuk pendaftaran magang.');
                    redirect('KemitraanPortal/ubah/' . (int) $row->id);
                    return;
                }
            }
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

        // Sama seperti simpan() - lihat komentar lengkap di sana. Mahasiswa
        // yang membetulkan nama kampusnya lewat sunting ini juga ikut
        // memperbarui nama akunnya, bukan cuma baris pendaftarannya.
        if ($row->jenis === 'kkn') {
            $nama_kampus = $this->input->post('instansi_asal', TRUE);
            $this->db->where('id', $this->get_user_id())->update('usr_users', ['name' => $nama_kampus]);
            $this->session->set_userdata('name', $nama_kampus);
        }

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

        // KKN dari dashboard baru kembali ke dashboard-nya sendiri, bukan
        // /akun - kartunya tidak pernah muncul di /akun sama sekali
        // (permintaan user 21 Agt 2026, lihat kkn_dashboard()).
        if ($row->jenis === 'kkn') {
            $this->session->set_flashdata('success', 'KKN dibatalkan.');
            redirect('KemitraanPortal/kkn_dashboard');
            return;
        }

        $this->session->set_flashdata('success', 'Pendaftaran dibatalkan. Slotnya kembali tersedia untuk mahasiswa lain.');
        redirect('akun');
    }

    /**
     * Ambil pendaftaran milik pemohon sendiri, atau hentikan permintaan.
     *
     * Dipakai baik untuk baris KKN (akun universitas) maupun Magang (akun
     * mahasiswa) - gerbangnya karena itu akses_kemitraan() (KEDUA role),
     * bukan akses_mahasiswa(). Anti-IDOR sesungguhnya dari pencocokan
     * user_id di WHERE bawah, bukan dari gerbang role ini.
     *
     * @return object|FALSE
     */
    private function pendaftaran_milik($id)
    {
        if ( ! is_numeric($id)) { show_404(); }
        if ( ! $this->akses_kemitraan('akun')) { return FALSE; }

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

        // Titik masuk lama untuk KKN - lihat alasan lengkap di daftar().
        // Kiriman jenis=kkn ke sini (mis. bookmark lama) dipulangkan ke
        // dashboard, BUKAN diproses - endpoint pemrosesan KKN yang baru
        // adalah kkn_tambah().
        if ($jenis === 'kkn') {
            redirect('KemitraanPortal/kkn_dashboard');
            return;
        }

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

        /* NIM/Tempat Lahir/Tanggal Lahir/Semester wajib HANYA untuk magang
           (permintaan user 21 Agt 2026: "untuk kkn yang bisa mendaftar
           adalah universitas" - KKN mendaftarkan kampus, bukan satu
           mahasiswa, jadi keempatnya tidak lagi punya arti untuk jenis
           itu). config/form_validation.php sudah melepas "required" dari
           keempatnya SUPAYA magang tetap bisa menegakkannya di sini -
           satu tempat yang tahu "wajib untuk jenis apa", pola sama dengan
           pemeriksaan surat pengantar tepat di bawah ini. `required` di
           formulir bukan penjagaan - ia hilang begitu POST dikirim tanpa
           lewat halaman itu. */
        // periode_mulai/periode_selesai ikut ke daftar ini (bukan cuma
        // NIM dkk.) sejak Tema Kegiatan dan Periode dihilangkan dari
        // formulir KKN (permintaan user 21 Agt 2026) - config
        // form_validation.php melepas "required"-nya untuk KEDUA jenis,
        // jadi kewajibannya-untuk-magang sekarang murni ditegakkan di
        // sini. divisi_atau_tema TIDAK ikut ditambahkan - periksa_slot()
        // di bawah sudah menolaknya lewat bidang_by_kode() begitu kosong,
        // dengan pesan yang lebih spesifik daripada pesan generik di sini.
        if ($jenis === 'magang') {
            $wajib_magang = [
                'nim' => 'NIM', 'tempat_lahir' => 'Tempat Lahir',
                'tanggal_lahir' => 'Tanggal Lahir', 'semester' => 'Semester',
                'periode_mulai' => 'Periode Mulai', 'periode_selesai' => 'Periode Selesai',
            ];
            foreach ($wajib_magang as $field => $label) {
                if (trim((string) $this->input->post($field, TRUE)) === '') {
                    $this->tolak_pendaftaran($jenis, $label . ' wajib diisi untuk pendaftaran magang.');
                    return;
                }
            }
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
            'nim'                  => $this->input->post('nim', TRUE) ?: NULL,
            'tempat_lahir'         => $this->input->post('tempat_lahir', TRUE) ?: NULL,
            'tanggal_lahir'        => $this->input->post('tanggal_lahir', TRUE) ?: NULL,
            // NULL kalau kosong (KKN), bukan 0 - "0 semester" bukan nilai
            // yang pernah benar-benar dilaporkan siapa pun, dan tercampur
            // dengan semester-1 sungguhan begitu ada yang menjumlahkannya.
            'semester'             => $this->input->post('semester', TRUE) !== '' && $this->input->post('semester', TRUE) !== NULL
                ? (int) $this->input->post('semester', TRUE) : NULL,
            'jurusan'              => $this->input->post('jurusan', TRUE),
            'instansi_asal'        => $this->input->post('instansi_asal', TRUE),
            'no_hp'                => $this->input->post('no_hp', TRUE),
            'divisi_atau_tema'     => $divisi_atau_tema ?: NULL,
            'bidang_kode'          => $bidang_kode,
            'periode_mulai'        => $periode_mulai ?: NULL,
            'periode_selesai'      => $periode_selesai ?: NULL,
            'file_surat_pengantar' => NULL,
            'file_proposal'        => NULL,
        ]);
        $id = $this->db->insert_id();

        /* KKN mendaftarkan kampus - nama akun disamakan dengan Nama
           Universitas yang baru diisi (keputusan user 21 Agt 2026), supaya
           "Terkirim atas nama akun" dan Profil Saya menunjukkan nama
           kampus, bukan nama demo generik akunnya. Sesi DIPERBARUI
           SEKALIGUS (bukan cuma DB) - halaman berikutnya (redirect ke
           /akun) langsung memakai nama baru tanpa perlu login ulang.
           Magang TIDAK ikut: akun magang tetap milik satu mahasiswa,
           bukan kampusnya. */
        if ($jenis === 'kkn') {
            $nama_kampus = $this->input->post('instansi_asal', TRUE);
            $this->db->where('id', $this->get_user_id())->update('usr_users', ['name' => $nama_kampus]);
            $this->session->set_userdata('name', $nama_kampus);
        }

        $pesan = 'Pendaftaran ' . strtoupper($jenis) . ' berhasil dikirim. Cek status pendaftaran di halaman akun Anda.';

        // Berkas disimpan di luar webroot, hanya bisa dibuka admin lewat
        // Admin_Kemitraan::lihat_dokumen() - dulu di .assets/uploads/ yang bisa
        // diakses HTTP langsung. Pendaftaran tetap tersimpan kalau berkasnya
        // gagal; pendaftar diberi tahu apa adanya, bukan dibiarkan mengira
        // lampirannya sudah masuk.
        //
        // Surat pengantar DAN proposal HANYA untuk magang (surat pengantar
        // sejak 21 Agt 2026 - KKN tidak lagi memintanya sama sekali,
        // sebelumnya opsional; proposal sejak 30 Jul 2026). Field-fieldnya
        // juga tidak dirender di formulir KKN, tapi pemeriksaan diulang di
        // sini: yang menentukan apa yang tersimpan adalah server, bukan
        // formulir yang dikirim peramban - kiriman KKN yang tetap
        // menyertakan berkas (mis. lewat POST manual) tidak akan tersimpan.
        $berkas = [];
        if ($jenis === 'magang') {
            $berkas['file_surat_pengantar'] = 'Surat pengantar';
            $berkas['file_proposal']        = 'Proposal';
        }

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

    /**
     * Gerbang akses generik untuk portal Kemitraan - dipakai TIGA gerbang
     * khusus di bawah, bukan dipanggil langsung dari luar berkas ini.
     *
     * Dipecah 22 Agt 2026 (permintaan user: "buat role UNIVERSITAS untuk
     * proses KKN, dan MAHASISWA hanya untuk proses magang"). Sebelum ini
     * KKN dan Magang berbagi SATU akses_mahasiswa() (role 'mahasiswa') -
     * keputusan yang jujur didokumentasikan sebagai sementara di riwayat
     * commit 3cf160e, bukan kelalaian, tapi tetap berarti akun universitas
     * dan mahasiswa perorangan tidak bisa dibedakan lewat role.
     *
     * @param string $target          Diserahkan ke gerbang_login() bila belum login.
     * @param array  $peran_diizinkan Role yang boleh lewat.
     * @param string $pesan_tolak     Pesan warning saat sudah login tapi role tidak cocok.
     */
    private function akses_peran_kemitraan($target, array $peran_diizinkan, $pesan_tolak)
    {
        if ( ! $this->is_logged_in()) {
            $this->session->set_flashdata('error', 'Silakan masuk terlebih dahulu.');
            // $target diserahkan ke gerbang, bukan ditulis sendiri ke sesi:
            // satu mekanisme, dan penyaringnya ikut berlaku di sini juga.
            $this->gerbang_login($target);
            return FALSE;
        }
        if ( ! in_array($this->session->userdata('role'), $peran_diizinkan, TRUE)) {
            // Ini bukan kegagalan sistem: akun ini memang memiliki jalur kerja
            // lain. Tampilkan peringatan agar pengguna tahu tindakan yang tepat.
            $this->session->set_flashdata('warning', $pesan_tolak);
            redirect('akun');
            return FALSE;
        }
        return TRUE;
    }

    /** Gerbang KKN - HANYA akun universitas. Dipakai kkn_dashboard()/kkn_tambah(). */
    private function akses_universitas($target)
    {
        return $this->akses_peran_kemitraan($target, ['universitas'],
            'Pendaftaran tidak tersedia. Pengajuan KKN hanya dapat dilakukan menggunakan akun universitas.');
    }

    /**
     * Gerbang Magang - HANYA akun mahasiswa. Perilakunya TIDAK berubah dari
     * sebelum 22 Agt 2026, cuma dipecah dari akses_universitas(): role
     * 'mahasiswa' sekarang murni Magang, tidak lagi dipakai bersama KKN.
     */
    private function akses_mahasiswa($target)
    {
        return $this->akses_peran_kemitraan($target, ['mahasiswa'],
            'Pendaftaran tidak tersedia. Pendaftaran Magang hanya dapat dilakukan menggunakan akun mahasiswa.');
    }

    /**
     * Gerbang MILIK SENDIRI - satu-satunya yang menerima KEDUA role
     * sekaligus, dipakai pendaftaran_milik() (lihat komentar lengkap di
     * sana): KKN dan Magang dilihat/diubah/dibatalkan lewat jalur yang
     * SAMA, jadi gerbangnya tidak boleh memilih salah satu.
     *
     * Anti-IDOR sesungguhnya TETAP dari pencocokan user_id di
     * pendaftaran_milik(), bukan dari gerbang ini - gerbang ini cuma
     * menyaring akun yang jelas bukan akun KKN/Magang sama sekali
     * (mis. warga/pengembang/admin), lebih awal dan dengan pesan yang
     * lebih jelas daripada baru gagal di query.
     */
    private function akses_kemitraan($target)
    {
        return $this->akses_peran_kemitraan($target, ['mahasiswa', 'universitas'],
            'Pendaftaran tidak tersedia. Fitur ini hanya untuk akun mahasiswa atau universitas.');
    }
}
