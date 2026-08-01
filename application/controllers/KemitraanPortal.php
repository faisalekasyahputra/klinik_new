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
        $this->render('pages/kemitraan_portal/kkn', ['judul' => 'KKN Tematik']);
    }

    /**
     * Papan slot magang. Dulu isinya array literal di method ini — tidak ada
     * yang bisa mengubahnya tanpa deploy, dan formulir pendaftaran tidak pernah
     * tunduk padanya. Sekarang dari `kkn_magang_slot`, dikelola superadmin
     * lewat Admin_Kemitraan::slot().
     */
    public function magang()
    {
        $tahun  = (int) date('Y');
        $peta   = $this->slot->peta_slot($tahun);
        $terisi = $this->slot->peta_terisi();

        // Tiap sel membawa keadaannya sendiri, bukan sekadar hijau/merah:
        // "tutup" tidak sama dengan "dibuka tapi sudah penuh", dan pembaca
        // berhak tahu bedanya sebelum menyusun periodenya.
        $slot_magang = [];
        foreach ($this->slot->divisi() as $divisi) {
            $sel = [];
            foreach (Kemitraan_slot_model::nama_bulan() as $nomor => $label) {
                $isi  = (int) ($terisi[$divisi->nama][$tahun . '-' . $nomor] ?? 0);
                $slot = $peta[(int) $divisi->id][$nomor] ?? NULL;

                if ( ! $slot) {
                    $sel[$label] = ['keadaan' => 'tutup', 'teks' => 'Tidak Tersedia', 'rentang' => ''];
                    continue;
                }

                // Rentang ditampilkan HANYA kalau bukan sebulan penuh. Menulis
                // "1–30" di setiap sel yang utuh cuma menambah yang harus dibaca
                // tanpa membedakan apa pun.
                $awal_bulan  = date('Y-m-01', strtotime($slot->tgl_mulai));
                $akhir_bulan = date('Y-m-t', strtotime($slot->tgl_mulai));
                $rentang = ($slot->tgl_mulai === $awal_bulan && $slot->tgl_selesai === $akhir_bulan)
                    ? ''
                    : (int) date('j', strtotime($slot->tgl_mulai)) . '–' . (int) date('j', strtotime($slot->tgl_selesai));

                $sel[$label] = $isi >= (int) $divisi->kuota
                    ? ['keadaan' => 'penuh', 'teks' => 'Penuh ' . $isi . '/' . (int) $divisi->kuota, 'rentang' => $rentang]
                    : ['keadaan' => 'buka',  'teks' => 'Sisa ' . ((int) $divisi->kuota - $isi),      'rentang' => $rentang];
            }
            $slot_magang[] = ['divisi' => $divisi->nama, 'kuota' => (int) $divisi->kuota, 'sel' => $sel];
        }

        $this->render('pages/kemitraan_portal/magang', [
            'judul'       => 'Magang dan Kerja Praktik',
            'tahun'       => $tahun,
            'slot_magang' => $slot_magang,
        ]);
    }

    public function daftar($jenis = NULL)
    {
        if ( ! in_array($jenis, ['kkn', 'magang'], TRUE)) { show_404(); }
        if ( ! $this->akses_mahasiswa('KemitraanPortal/daftar/' . $jenis)) { return; }

        // Nama & email ditampilkan BACA-SAJA di formulir, diambil dari sesi.
        // Bukan sekadar hiasan: pendaftaran ini menempel ke akun lewat user_id,
        // dan sebelumnya pendaftar tidak pernah diberi tahu nama siapa yang
        // ikut terkirim. Tetap tidak diterima sebagai input — `simpan()` hanya
        // membaca user_id dari sesi (anti-IDOR), jadi mengubahnya di peramban
        // tidak mengubah apa pun.
        // Divisi hanya untuk magang. Di formulir KKN field yang sama berlabel
        // "Tema Kegiatan" — itu memang teks bebas, bukan unit kerja, jadi tidak
        // ada slot yang bisa mengaturnya.
        $this->render('pages/kemitraan_portal/daftar', [
            'judul'      => $jenis === 'kkn' ? 'Daftar KKN Tematik' : 'Daftar Magang dan Kerja Praktik',
            'jenis'      => $jenis,
            'nama_akun'  => (string) $this->session->userdata('name'),
            'email_akun' => (string) $this->session->userdata('email'),
            'divisi'     => $jenis === 'magang' ? $this->slot->divisi() : [],
        ]);
    }

    public function simpan()
    {
        $jenis = $this->input->post('jenis', TRUE);
        if ( ! in_array($jenis, ['kkn', 'magang'], TRUE) || $this->input->method(TRUE) !== 'POST') { show_404(); }
        if ( ! $this->akses_mahasiswa('KemitraanPortal/daftar/' . $jenis)) { return; }

        // Aturannya hidup di config/form_validation.php, grup
        // `kemitraan_pendaftaran` — mekanisme bawaan CI3. Superadmin menyunting
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

        // Urutan periode tidak pernah diperiksa sebelumnya, jadi pendaftaran
        // yang selesai mendahului mulainya bisa tersimpan begitu saja. Selain
        // salah pada dirinya sendiri, ia membuat penelusuran bulan di bawah
        // tidak punya arah.
        if ($periode_selesai < $periode_mulai) {
            $this->tolak_pendaftaran($jenis, 'Periode selesai tidak boleh mendahului periode mulai.');
            return;
        }

        if ($this->slot->periode_terlalu_panjang($periode_mulai, $periode_selesai)) {
            $this->tolak_pendaftaran($jenis, 'Periode terlalu panjang. Maksimal '
                . Kemitraan_slot_model::BATAS_HARI . ' hari.');
            return;
        }

        // Slot HANYA mengikat magang. Untuk KKN, field yang sama berarti tema
        // kegiatan — teks bebas yang tidak punya divisi untuk dicocokkan.
        if ($jenis === 'magang') {
            // Dicocokkan lewat NAMA, karena itulah yang tersimpan di
            // `divisi_atau_tema` — kolom teks yang sudah dipakai pendaftaran
            // lama dan dibaca apa adanya di meja admin. Menyimpan divisi_id di
            // situ berarti membongkar kolom yang tidak perlu dibongkar.
            $divisi = $this->slot->divisi_by_nama($divisi_atau_tema);
            if ( ! $divisi || (int) $divisi->aktif !== 1) {
                // Formulir merender select, tapi tidak merender pilihan bukan
                // penjagaan — siapa pun bisa mengirim nilai lain dari peramban.
                $this->tolak_pendaftaran($jenis, 'Divisi yang dipilih tidak tersedia. Silakan pilih dari daftar yang ditawarkan.');
                return;
            }

            $halangan = $this->slot->bulan_terhalang($divisi, $periode_mulai, $periode_selesai);
            if ($halangan) {
                $this->tolak_pendaftaran($jenis, 'Divisi ' . html_escape($divisi->nama)
                    . ' tidak membuka slot pada: ' . html_escape(implode(', ', $halangan))
                    . '. Sesuaikan periode atau pilih divisi lain.');
                return;
            }

            // Simpan nama KANONIK dari tabel, bukan teks kiriman. Keduanya
            // cocok saat ini, tapi yang tersimpan harus selalu bisa ditelusuri
            // balik ke satu baris divisi — bukan varian spasi yang mirip.
            $divisi_atau_tema = $divisi->nama;
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
            'periode_mulai'        => $periode_mulai,
            'periode_selesai'      => $periode_selesai,
            'file_surat_pengantar' => NULL,
            'file_proposal'        => NULL,
        ]);
        $id = $this->db->insert_id();

        $pesan = 'Pendaftaran ' . strtoupper($jenis) . ' berhasil dikirim. Cek status pendaftaran di halaman akun Anda.';

        // Berkas disimpan di luar webroot, hanya bisa dibuka admin lewat
        // Admin_Kemitraan::lihat_dokumen() — dulu di .assets/uploads/ yang bisa
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
        foreach ($berkas as $field => $label) {
            $galat = NULL;
            $nama_berkas = $this->store_private_upload($field, 'kemitraan', $id, $galat);
            if ($nama_berkas) {
                $simpan[$field] = $nama_berkas;
            } elseif ($galat) {
                $pesan .= ' Namun ' . strtolower($label) . ' gagal diunggah (' . $galat . ') — hubungi admin untuk menyusulkan.';
            }
        }
        if ($simpan) {
            $this->db->where('id', $id)->update('kkn_magang_pendaftaran', $simpan);
        }

        $this->session->set_flashdata('success', $pesan);
        redirect('akun');
    }

    /**
     * Pulangkan pendaftar ke formulirnya dengan alasan yang bisa dibaca.
     *
     * Isian belum dikembalikan di sini — formulir ini belum punya mekanisme
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
            $this->session->set_userdata('intended_url', $target);
            $this->session->set_flashdata('error', 'Silakan masuk atau daftar akun mahasiswa terlebih dahulu.');
            redirect('Auth/login');
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
