<?php
defined('BASEPATH') || exit('No direct script access allowed');

class Pengaturan extends MY_Controller {

    public function __construct() {
        parent::__construct();
        if (!$this->is_logged_in()) {
            $this->gerbang_login();
        }
        $this->load->model('User_model');
        $this->load->model('Auth_model');
        $this->load->model('Housing_assessment_model');
        $this->load->helper('housing_queue');
    }

    /**
     * Item utama: satu list gabungan status SEMUA jenis pengajuan milik user
     * (antrean perumahan, aduan, SRP2, KKN/Magang) - bukan lagi kartu terpisah
     * per jenis, supaya warga/pengembang/mahasiswa punya satu tempat pantau status.
     */
    public function index() {
        $user_id = $this->get_user_id();
        $role = $this->session->userdata('role');

        $items = [];

        foreach ($this->db
            ->select('sf_antrean_pengajuan.id, kode_tiket, status_antrean, catatan_admin, mode_sumber, sf_antrean_pengajuan.created_at, sf_program.nama_program')
            ->from('sf_antrean_pengajuan')
            ->join('sf_program', 'sf_program.id=sf_antrean_pengajuan.program_id', 'left')
            ->where('sf_antrean_pengajuan.user_id', (int) $user_id)
            ->order_by('sf_antrean_pengajuan.created_at', 'DESC')->get()->result() as $r) {
            $status = housing_queue_statuses()[$r->status_antrean] ?? ['label' => 'Sedang Diverifikasi', 'badge' => 'pending'];
            $items[] = [
                'jenis' => 'Antrean Perumahan - ' . ($r->nama_program ?: 'Program'), 'icon' => 'ph-ticket',
                'judul' => !empty($r->kode_tiket) ? $r->kode_tiket : 'Tiket belum tersedia',
                'status_label' => $status['label'], 'status_kelas' => $status['badge'],
                'created_at' => $r->created_at,
                'aksi_url' => null,
                'aksi_post_url' => $r->status_antrean === 'needs_revision'
                    ? 'warga/pendataan' : null,
                'aksi_post_fields' => $r->status_antrean === 'needs_revision'
                    ? ['action' => 'start_revision', 'antrean_id' => $r->id] : [],
                'aksi_label' => $r->status_antrean === 'needs_revision'
                    ? 'Mulai Perbaikan' : null,
                'catatan_admin' => $r->catatan_admin,
                'is_simulation' => $r->mode_sumber === 'simulation',
                // Perjalanan pengajuan, bukan cuma status terakhir - supaya
                // pemohon tahu sudah sampai mana dan apa yang sudah terjadi.
                'riwayat' => $this->Housing_assessment_model->get_owned_timeline($r->id, (int) $user_id),
            ];
        }

        // catatan_admin ikut diambil supaya pelapor tahu ALASAN status berubah,
        // bukan cuma statusnya - pola yang sudah dipakai SRP2 tapi dulu belum
        // ada di aduan (AUDIT_ROLE_ADMIN_SCOPED.md #7).
        foreach ($this->db->select('id, judul, bidang_kode, status, catatan_admin, created_at')
            ->where('user_id', (int) $user_id)->order_by('created_at', 'DESC')
            ->get('aduan')->result() as $r) {
            $status_map = ['Baru' => 'pending', 'Diproses' => 'process', 'Selesai' => 'ok'];
            $items[] = [
                'jenis' => 'Aduan', 'icon' => 'ph-chat-centered-text',
                'judul' => $r->judul,
                'status_label' => $r->status, 'status_kelas' => $status_map[$r->status] ?? 'pending',
                'created_at' => $r->created_at, 'aksi_url' => null,
                'catatan_admin' => $r->catatan_admin,
            ];
        }

        if ($role === 'pengembang') {
            // Keadaan pengajuan lewat SATU sumber bersama (§17 poin 13). Dulu
            // halaman ini query sendiri - salinan logika kedua, dan itu persis
            // yang dulu melahirkan bug 0/14 dokumen di wizard.
            $srp2 = $this->Auth_model->srp2_state($user_id);
            $sp2  = $srp2 ? $this->db->get_where('srp2_pengajuan', ['id' => $srp2['pengajuan_id']])->row() : NULL;
            if ($sp2) {
                // Label status + label tombol aksi. Tombolnya diberi nama sesuai
                // apa yang BENAR-BENAR terjadi saat diklik, bukan "Kelola" untuk
                // semua keadaan - "Kelola" pada pengajuan yang sudah final
                // menjanjikan sesuatu yang tidak bisa dilakukan.
                $peta = [
                    'Draft'    => ['Lengkapi Dokumen', 'process', 'Lengkapi'],
                    'Pending'  => ['Dalam Peninjauan', 'pending', 'Lihat Dokumen'],
                    'Diterima' => ['Diterima', 'ok', 'Lihat Dokumen'],
                    'Ditolak'  => ['Ditolak', 'reject', 'Perbaiki & Kirim Ulang'],
                ];
                $status = $peta[$sp2->status_verifikasi] ?? [$sp2->status_verifikasi, 'pending', 'Lihat'];

                // Draft + ada catatan = pengajuan DIBUKA KEMBALI admin lewat
                // "Minta Perbaikan", bukan draft yang belum pernah dikirim.
                // Bedakan labelnya supaya pemohon paham ini permintaan, bukan
                // sekadar pekerjaan yang belum dia selesaikan.
                if ($sp2->status_verifikasi === 'Draft' && ! empty($sp2->catatan_admin)) {
                    $status = ['Perlu Diperbaiki', 'process', 'Perbaiki Dokumen'];
                }
                $items[] = [
                    'jenis' => 'Sertifikasi Pengembang (SRP2)', 'icon' => 'ph-certificate',
                    // Bisa NULL: admin yang mempromosikan akun langsung ke role
                    // pengembang (Admin_Users::update_role()) tidak pernah
                    // menanyakan nama perusahaan, dan ensure_srp2_draft() ikut
                    // membuat draft dengan kolom itu kosong. Ditandai apa adanya,
                    // bukan disembunyikan (roadmap T6 R2-sisa).
                    // Akun tertaut direktori: nama dari baris direktori, satu sumber (migrasi 070).
                    'judul' => ($this->direktori_milik_saya()->nama_perusahaan ?? '') ?: ($sp2->nama_perusahaan ?: '(Nama perusahaan belum diisi)'),
                    'status_label' => $status[0], 'status_kelas' => $status[1],
                    // updated_at, bukan created_at - draft bisa dibuat jauh sebelum
                    // benar-benar diisi/dikirim, tanggal aktivitas terakhir lebih relevan.
                    'created_at' => $sp2->updated_at ?: $sp2->created_at,
                    // Tetap di dashboard; panel memakai pengajuan yang sama dengan wizard.
                    // Draft/Ditolak dapat diperbaiki, Pending/Diterima hanya dapat dilihat.
                    'aksi_url' => 'akun/dokumen',
                    'aksi_label' => $status[2],
                    // Alasan penolakan / permintaan perbaikan ikut ditampilkan di
                    // daftar, supaya pemohon tahu tanpa harus membuka wizard dulu.
                    'catatan_admin' => $sp2->catatan_admin,
                ];
            }
        }

        if ($role === 'mahasiswa') {
            foreach ($this->db->where('user_id', $user_id)->order_by('id', 'DESC')->get('kkn_magang_pendaftaran')->result() as $p) {
                $status_map = [
                    'Diajukan' => ['Menunggu Sekretariat', 'pending'],
                    'Ditinjau Bidang' => ['Ditinjau Bidang', 'process'],
                    'Diterima' => ['Diterima', 'ok'],
                    'Ditolak' => ['Ditolak', 'reject'], 'Dibatalkan' => ['Dibatalkan', 'reject'],
                ];
                $status = $status_map[$p->status] ?? [$p->status, 'pending'];
                $items[] = [
                    'jenis' => strtoupper($p->jenis) . ' - ' . $p->instansi_asal, 'icon' => 'ph-graduation-cap',
                    'judul' => $p->divisi_atau_tema,
                    'status_label' => $status[0], 'status_kelas' => $status[1],
                    'created_at' => $p->created_at,
                    // Dulu null, jadi barisnya buntu: mahasiswa melihat statusnya
                    // berubah tanpa pernah bisa membuka apa yang ia kirim, apalagi
                    // memperbaikinya. View-nya sudah siap merender tombol ini.
                    'aksi_url'   => 'KemitraanPortal/pendaftaran/' . (int) $p->id,
                    // KKN dari dashboard baru (21 Agt 2026) tidak punya
                    // formulir sunting sama sekali - lihat
                    // KemitraanPortal::ubah(). "Lihat / Ubah" untuk baris
                    // itu menjanjikan tombol yang tidak ada di halaman
                    // tujuannya.
                    'aksi_label' => ($p->status === 'Diajukan' && $p->jenis !== 'kkn') ? 'Lihat / Ubah' : 'Lihat',
                    // Cabang mahasiswa satu-satunya yang dulu TIDAK mengirim ini,
                    // padahal antrean, aduan, dan SRP2 semuanya mengirimnya - dan
                    // komentar di berkas ini sendiri (§ aduan) sudah menyebut
                    // alasannya: pelapor harus tahu ALASAN statusnya berubah,
                    // bukan cuma statusnya. Akibatnya admin menolak pendaftaran
                    // KKN, mengetik alasannya, dan mahasiswanya cuma melihat
                    // "Ditolak" - catatannya tersimpan di DB lalu tidak
                    // ditampilkan ke siapa pun. View-nya sudah siap merender.
                    'catatan_admin' => $p->catatan_admin,
                ];
            }
        }

        usort($items, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));

        $empty_action = [
            'warga'      => ['url' => 'warga/pendataan', 'label' => 'Mulai Pendataan Warga'],
            'pengembang' => ['url' => 'Pengembang/syarat', 'label' => 'Mulai Sertifikasi Pengembang'],
            'mahasiswa'  => ['url' => 'KemitraanPortal', 'label' => 'Lihat KKN & Magang'],
        ][$role] ?? NULL;

        $datacontent = [
            'title' => 'Status Pengajuan',
            'items' => $items,
            'empty_action' => $empty_action,
        ];
        $this->render_user_dashboard('pages/pengaturan/index', $datacontent);
    }

    /**
     * Kelola berkas dari dashboard memakai pengajuan yang sama dengan wizard.
     */
    public function dokumen() {
        if ($this->session->userdata('role') !== 'pengembang') { show_404(); return; }
        $state = $this->Auth_model->srp2_state($this->get_user_id());
        if (!$state) { show_404(); return; }
        $this->load->helper('srp2');
        $files = [];
        foreach ($this->db->select('kunci_dokumen, nama_asli')->where('pengajuan_id', $state['pengajuan_id'])
            ->get('srp2_dokumen')->result_array() as $file) {
            $files[$file['kunci_dokumen']] = $file['nama_asli'];
        }
        $this->render_user_dashboard('pages/pengaturan/dokumen', [
            'title' => 'Dokumen SRP2', 'srp2' => $state, 'files' => $files,
            'dokumen' => srp2_dokumen_persyaratan(), 'keterangan' => srp2_keterangan_persyaratan(),
        ]);
    }

    /** Edit profil pribadi dan data perusahaan SRP2. */
    public function profil() {
        $user_id = $this->get_user_id();
        $user = $this->Auth_model->find_by_id($user_id);

        $datacontent = ['user' => $user, 'title' => 'Profil Saya'];
        // Alasan wajib ganti sandi tetap tampil di formulir, bukan hanya di flash sekali tampil.
        $datacontent['pesan_ganti_sandi'] = $user && $this->Auth_model->password_expired($user)
            ? $this->Auth_model->pesan_ganti_sandi($user) : NULL;

        /* Butir 21: NIK ditampilkan TERSAMAR, hanya empat digit terakhir.
           Menampilkannya utuh di layar yang bisa dibuka di ruang publik tidak
           menambah kegunaan apa pun: pemiliknya sudah tahu NIK-nya, dan yang
           dia perlukan cuma memastikan yang tersimpan benar. Nilai aslinya
           tidak pernah dikirim ke halaman. */
        $datacontent['nik_tersamar'] = NULL;
        $datacontent['nik_terkunci'] = ! empty($user->nik_lookup_hash);
        if ($datacontent['nik_terkunci'] && ! empty($user->nik)) {
            $this->load->library('encryption_lib');
            $buka = $this->encryption_lib->decrypt($user->nik);
            $datacontent['nik_tersamar'] = (is_string($buka) && strlen($buka) >= 4)
                ? str_repeat('*', 12) . substr($buka, -4)
                : 'tersimpan';
        }

        if ($this->session->userdata('role') === 'pengembang') {
            // Lewat satu sumber bersama, sama dengan index() dan wizard (§17 poin 13).
            $srp2 = $this->Auth_model->srp2_state($user_id);
            $datacontent['pengajuan_sp2'] = $srp2
                ? $this->db->get_where('srp2_pengajuan', ['id' => $srp2['pengajuan_id']])->row()
                : NULL;
            $datacontent['direktori'] = $this->direktori_milik_saya();
        }

        $this->render_user_dashboard('pages/pengaturan/profil', $datacontent);
    }

    public function update_pengembang_profile() {
        $user_id = $this->get_user_id();

        if ($this->session->userdata('role') !== 'pengembang') {
            show_404();
            return;
        }

        // order_by SAMA dengan yang dipakai profil() saat merender form. Tanpa ini
        // baris yang DICEK dan baris yang DITULIS bisa berbeda begitu satu user
        // punya lebih dari satu baris pengajuan.
        $pengajuan = $this->db->where('user_id', $user_id)
            ->order_by('id', 'DESC')->get('srp2_pengajuan')->row();
        if (!$pengajuan) {
            $this->session->set_flashdata('error', 'Data pengajuan sertifikasi tidak ditemukan.');
            redirect('akun/profil');
            return;
        }

        // Akun yang memegang baris direktori mengubah datanya lewat Profil Perusahaan,
        // satu-satunya formulir untuk baris itu (2 Okt 2026). Formulir ini menulis ke
        // pengajuan lalu menimpa direktori, jadi dua formulir berarti dua versi data.
        if ($this->direktori_milik_saya()) {
            $this->session->set_flashdata('error', 'Data perusahaan Anda dikelola lewat halaman Profil Perusahaan.');
            redirect('akun/perusahaan');
            return;
        }

        // Dokumen dikunci saat Pending/Diterima, tapi DATA-nya dulu tidak - nama
        // dan alamat masih bisa bergeser di bawah tangan admin yang sedang
        // menilai, dan nilai terbaru itulah yang tersalin ke direktori publik
        // saat disetujui. Tanpa gerbang ini, gerbang di kirim_pengajuan() cuma
        // dekoratif: kirim dengan nama sah, lalu ganti jadi duplikat selagi
        // Pending. Roadmap T1a butir 4.
        // PENDING: terkunci penuh. Data tidak boleh bergeser di bawah tangan
        // admin yang sedang menilai - nilai yang dia lihat harus sama dengan
        // nilai yang tersalin ke direktori saat disetujui.
        if ($pengajuan->status_verifikasi === 'Pending') {
            $this->session->set_flashdata('error', 'Pengajuan sedang ditinjau admin - data perusahaan tidak bisa diubah sampai ada keputusan.');
            redirect('akun/profil');
            return;
        }

        // DITERIMA: identitas terkunci, KONTAK boleh berubah.
        //
        // Mengunci semuanya setelah disetujui terdengar aman, tapi artinya
        // pengembang yang pindah kantor atau ganti website tidak akan pernah
        // bisa memperbarui listing publiknya - padahal formnya sendiri berjanji
        // "Kontak publik - ditampilkan di halaman profil pengembang". Yang
        // benar-benar tidak boleh berubah adalah NAMA: itu identitas yang
        // diverifikasi admin sekaligus kunci UNIQUE baris direktori.
        $terkunci_identitas = ($pengajuan->status_verifikasi === 'Diterima');

        // Simpan teks apa adanya (escape dilakukan sekali saat render lewat htmlspecialchars()
        // di profil.php, bukan di sini - supaya tidak double-encode).
        // Alamat, nomor keanggotaan, dan tautan memakai aturan yang SAMA dengan Direktori
        // SRP2 (admin) dan Profil Perusahaan: srp2_bersihkan_profil().
        $this->load->helper('srp2');
        [$kontak, $galat] = srp2_bersihkan_profil([
            'alamat_kantor'  => $this->input->post('alamat_kantor'),
            'no_keanggotaan' => $this->input->post('no_keanggotaan'),
            'instagram'      => $this->input->post('instagram'),
            'website'        => $this->input->post('website'),
            'sosmed_lainnya' => $this->input->post('sosmed_lainnya'),
        ]);
        if ($galat !== NULL) {
            $this->session->set_flashdata('error', $galat);
            redirect('akun/profil');
            return;
        }
        $data = [
            'nama_perusahaan' => strtoupper(trim((string) $this->input->post('nama_perusahaan'))),
            'asosiasi'        => $this->input->post('asosiasi'),
        ] + $kontak;

        if (empty($data['nama_perusahaan']) || empty($data['alamat_kantor'])) {
            $this->session->set_flashdata('error', 'Nama Perusahaan dan Alamat Kantor tidak boleh kosong.');
            redirect('akun/profil');
            return;
        }

        // Daftarnya dari srp2_daftar_asosiasi() sejak 14 Agt 2026, bukan
        // salinan literal di sini - formulir admin (Admin_Srp2::save()) kini
        // memvalidasi ke daftar yang SAMA.
        if ( ! array_key_exists((string) $data['asosiasi'], srp2_daftar_asosiasi())) {
            $this->session->set_flashdata('error', 'Asosiasi tidak valid.');
            redirect('akun/profil');
            return;
        }

        // Identitas dikunci di sini - SESUDAH $data dibentuk, bukan sebelumnya.
        // Nama adalah yang diverifikasi admin sekaligus kunci UNIQUE baris
        // direktori; kontak (alamat/website/sosmed) justru memang dimaksudkan
        // untuk bisa diperbarui pemiliknya.
        if ($terkunci_identitas) {
            if ($data['nama_perusahaan'] !== strtoupper(trim((string) $pengajuan->nama_perusahaan))) {
                $this->session->set_flashdata('error', 'Nama perusahaan tidak bisa diubah setelah pengajuan disetujui. Untuk mengubahnya, minta admin membuka kembali pengajuan Anda.');
                redirect('akun/profil');
                return;
            }
            unset($data['nama_perusahaan']);
        }

        // user_id selalu dari sesi, bukan dari input, supaya tidak bisa mengedit
        // data pengembang lain (anti-IDOR). `id` ikut disertakan supaya UPDATE
        // mengenai TEPAT baris yang tadi dibaca - sebelumnya hanya WHERE user_id,
        // yang menimpa SEMUA baris milik user itu sekaligus.
        $this->db->trans_start();
        $this->db->where('id', $pengajuan->id)->where('user_id', $user_id);
        $this->db->update('srp2_pengajuan', $data);

        // Perubahan ikut menular ke direktori publik kalau pengajuan ini memang
        // sudah terbit di sana. Dulu baris direktori hanya diisi SEKALI saat
        // approve, sehingga ganti alamat/website/Instagram tidak pernah sampai
        // ke publik - padahal formnya berlabel "Kontak publik - ditampilkan di
        // halaman profil pengembang". Satu fungsi upsert yang sama dengan yang
        // dipakai Admin_Srp2::proses(), bukan salinan kedua.
        $tersinkron = FALSE;
        if ( ! empty($pengajuan->pengembang_id)) {
            $segar = (object) array_merge((array) $pengajuan, $data);
            $this->Auth_model->upsert_direktori_publik($segar);
            $tersinkron = TRUE;
        }
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Gagal menyimpan perubahan - tidak ada yang tersimpan. Kemungkinan nama perusahaan sudah dipakai pengembang lain di direktori.');
            redirect('akun/profil');
            return;
        }

        $this->session->set_flashdata('success', $tersinkron
            ? 'Data pengembang berhasil diperbarui - perubahan juga tampil di profil publik Anda.'
            : 'Data pengembang berhasil diperbarui!');
        redirect('akun/profil');
    }

    /**
     * Baris Direktori SRP2 milik akun yang login. Pemiliknya ditentukan dari SESI
     * (srp2_direktori_pengembang.user_id), tidak pernah dari input: anti-IDOR.
     */
    private function direktori_milik_saya() {
        if ($this->session->userdata('role') !== 'pengembang') { return NULL; }
        return $this->db->get_where('srp2_direktori_pengembang', ['user_id' => (int) $this->get_user_id()])->row();
    }

    /**
     * Gerbang ganti sandi melewatkan SELURUH controller Pengaturan (supaya halaman ganti
     * sandi bisa dibuka), jadi Profil Perusahaan menjaga dirinya sendiri: sandi awal dari
     * admin harus diganti sebelum data publik perusahaan bisa diubah.
     */
    private function wajib_ganti_sandi_dulu() {
        if ( ! $this->session->userdata('password_change_required')) { return FALSE; }
        $this->session->set_flashdata('error', $this->Auth_model->pesan_ganti_sandi($this->Auth_model->find_by_id($this->get_user_id())));
        redirect('akun/profil?password_expired=1');
        return TRUE;
    }

    /**
     * Profil Perusahaan (2 Okt 2026): pengembang yang akunnya tertaut mengubah baris
     * direktorinya sendiri. Nama, NPWP, wilayah, status, masa berlaku, dan penayangan
     * tetap milik dinas (hanya dibaca). Asosiasi boleh diubah karena formulir pengajuan
     * juga membolehkannya; wilayah tidak, karena pengajuan tidak pernah menanyakannya.
     */
    public function perusahaan() {
        if ($this->session->userdata('role') !== 'pengembang') { show_404(); return; }
        if ($this->wajib_ganti_sandi_dulu()) { return; }
        $this->load->helper('srp2');
        $row = $this->direktori_milik_saya();
        $this->render_user_dashboard('pages/pengaturan/perusahaan', [
            'title'   => 'Profil Perusahaan',
            'row'     => $row,
            'wilayah' => $row && $row->kabupaten_id
                ? $this->db->select('nama')->get_where('kabupaten', ['id' => (int) $row->kabupaten_id])->row('nama') : NULL,
        ]);
    }

    public function simpan_perusahaan() {
        if ($this->input->method(TRUE) !== 'POST' || $this->session->userdata('role') !== 'pengembang') { show_404(); return; }
        if ($this->wajib_ganti_sandi_dulu()) { return; }
        $row = $this->direktori_milik_saya();
        $gagal = function ($pesan) { $this->session->set_flashdata('error', $pesan); redirect('akun/perusahaan'); };
        if ( ! $row) { $gagal('Akun Anda belum tertaut ke entri direktori pengembang.'); return; }

        $this->load->helper('srp2');
        $masukan = [];
        foreach (['alamat_kantor', 'no_keanggotaan', 'nib', 'no_whatsapp', 'email_kontak', 'website', 'instagram', 'sosmed_lainnya'] as $k) {
            $masukan[$k] = $this->input->post($k, TRUE);
        }
        [$data, $galat] = srp2_bersihkan_profil($masukan);
        if ($galat !== NULL) { $gagal($galat); return; }
        if (empty($data['alamat_kantor'])) { $gagal('Alamat kantor wajib diisi.'); return; }
        $asosiasi = trim((string) $this->input->post('asosiasi', TRUE));
        if ($asosiasi !== '' && ! array_key_exists($asosiasi, srp2_daftar_asosiasi())) { $gagal('Asosiasi tidak valid.'); return; }
        $data['asosiasi'] = $asosiasi !== '' ? $asosiasi : NULL;

        $foto = $this->simpan_foto_srp2('foto_profil', $galat);
        if ($galat !== NULL) { $gagal($galat); return; }
        if ($foto !== NULL) { $data['foto_profil'] = $foto; }

        $this->db->trans_start();
        $this->db->where('id', (int) $row->id)->where('user_id', (int) $this->get_user_id())
            ->update('srp2_direktori_pengembang', $data);
        $this->Auth_model->sinkron_pengajuan_dari_direktori((int) $row->id);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            if ($foto !== NULL) { $this->hapus_foto_srp2($foto); }
            $gagal('Perubahan gagal disimpan. Tidak ada yang tersimpan; coba lagi.');
            return;
        }
        if ($foto !== NULL) { $this->hapus_foto_srp2($row->foto_profil); }

        $this->catat_audit('srp2_profil_diubah_pengembang', 'Pengembang memperbarui profil perusahaan "' . $row->nama_perusahaan . '"',
            'srp2_direktori_pengembang', (string) (int) $row->id, ['kolom' => array_keys($data)]);
        $this->session->set_flashdata('success', $row->status_aktif
            ? 'Profil perusahaan disimpan dan langsung tampil di direktori publik.'
            : 'Profil perusahaan disimpan. Entri Anda sedang tidak ditayangkan di direktori publik oleh dinas.');
        redirect('akun/perusahaan');
    }

    public function update_profile() {
        $user_id = $this->get_user_id();

        $username = html_escape($this->input->post('username'));
        $username = preg_replace('/\s+/', '', strtolower($username));
        $name     = html_escape($this->input->post('name'));

        if (empty($name)) {
            $this->session->set_flashdata('error', 'Nama Lengkap tidak boleh kosong.');
            redirect('akun/profil');
            return;
        }
        // Aturan HP sama dengan akun buatan admin; sebelumnya isian apa pun disimpan dan yang
        // lebih dari 20 karakter dipotong diam-diam oleh kolomnya (29 Sep 2026).
        $this->load->library('form_validation');
        $this->form_validation->set_rules('phone', 'Nomor HP', 'trim|max_length[20]|nomor_hp');
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', strip_tags(validation_errors()));
            redirect('akun/profil');
            return;
        }
        $phone = (string) $this->input->post('phone');

        // Username baru: format tanpa "@" dan unik terhadap username DAN email akun lain. Username lama
        // yang tidak diubah tetap boleh disimpan walau dibuat sebelum aturan format ini.
        if (!empty($username)) {
            $lama = (string) ($this->Auth_model->find_by_id($user_id)->nama_pengguna ?? '');
            $galat = $username === $lama ? NULL : $this->Auth_model->username_ditolak($username, $user_id);
            if ($galat !== NULL) {
                $this->session->set_flashdata('error', $galat);
                redirect('akun/profil');
                return;
            }
        }

        $data = [
            'nama'  => $name,
            'no_hp' => $phone
        ];

        if (!empty($username)) {
            $data['nama_pengguna'] = $username;
        }

        /* BUTIR 21 PUTARAN 2: isian NIK di Profil Saya.
           Kolomnya sudah lama ada; yang tidak pernah ada adalah isiannya.

           NIK DIISI SEKALI, LALU TIDAK BISA DIUBAH SENDIRI. Ini keputusan
           keamanan, bukan kekakuan: NIK adalah kunci identitas warga (butir 8),
           dan NIK yang bisa diganti sendiri kapan saja berarti satu orang bisa
           berpindah-pindah memakai NIK orang lain, termasuk sesudah pengajuan
           bantuannya dinilai. Yang salah ketik dibetulkan lewat admin.

           Diperlakukan seperti NIK di modul pendataan: terenkripsi, dicari
           lewat sidik deterministik, tidak pernah tampil utuh. Keunikannya
           ditegakkan indeks `uq_usr_nik_lookup` (migrasi 041); pemeriksaan di
           bawah hanya supaya pesannya ramah, bukan supaya aman. */
        $nik_kirim = preg_replace('/\D+/', '', (string) $this->input->post('nik'));
        if ($nik_kirim !== '') {
            $this->load->library('encryption_lib');
            $punya = (string) $this->db->select('nik_lookup_hash')
                ->get_where('usr_akun', ['id' => $user_id])->row('nik_lookup_hash');

            if ($punya !== '') {
                /* Sudah punya NIK. Kiriman yang SAMA dibiarkan lewat tanpa
                   pesan galat: formulir mengirim ulang nilai yang sudah ada
                   setiap kali disimpan, dan menolaknya akan membuat setiap
                   penyuntingan nama atau nomor HP ikut gagal. */
                if ( ! hash_equals($punya, $this->encryption_lib->deterministic_hash($nik_kirim))) {
                    $this->session->set_flashdata('error',
                        'NIK sudah terkunci pada akun ini dan tidak dapat diubah sendiri. Hubungi admin bila ada kekeliruan.');
                    redirect('akun/profil');
                    return;
                }
            } elseif (strlen($nik_kirim) !== 16) {
                $this->session->set_flashdata('error', 'NIK harus tepat 16 digit angka.');
                redirect('akun/profil');
                return;
            } else {
                /* Isian ini dulu orakel "apakah NIK ini punya akun" tanpa batas (temuan ekspor-pii-07):
                   setiap kiriman NIK pertama dihitung (per akun dan per IP), dan NIK yang terikat ke
                   akun lain dijawab dengan pesan umum yang tidak menyebut akun lain. */
                $laju = $this->rate_limit_consume('profil_nik', ['account_id' => (int) $user_id]);
                if (empty($laju['success']) || empty($laju['allowed'])) {
                    $this->session->set_flashdata('error', 'Terlalu banyak percobaan mengisi NIK. Silakan coba lagi besok.');
                    redirect('akun/profil');
                    return;
                }
                $sidik = $this->encryption_lib->deterministic_hash($nik_kirim);
                // Penjaga yang sama dengan onboarding dan pendataan: usr_akun DAN sf_profil_warga.
                $this->load->model('Housing_assessment_model');
                $ikatan = $this->Housing_assessment_model->cek_ikatan_nik($user_id, $sidik);
                if ($ikatan !== NULL) {
                    $this->session->set_flashdata('error', $ikatan['code'] === 'nik_already_bound'
                        ? Housing_assessment_model::PESAN_NIK_BELUM_DISIMPAN : $ikatan['message']);
                    redirect('akun/profil');
                    return;
                }
                $data['nik'] = $this->encryption_lib->encrypt($nik_kirim);
                $data['nik_lookup_hash'] = $sidik;
            }
        }

        // Ganti password opsional - dipindahkan ke sini saat User_Profile
        // (halaman profil khusus superadmin) dilebur ke halaman ini, supaya
        // fiturnya tidak hilang. Aturan kekuatan password disamakan dengan
        // Auth::do_register()/save_onboarding(), bukan versi longgar lama
        // yang menerima password apa pun.
        $password = $this->input->post('password');
        if (!empty($password)) {
            // Bukti kepemilikan sebelum ganti password - roadmap T5 S13. Tanpa
            // ini, sesi yang sempat dipakai orang lain (mis. komputer publik)
            // bisa mengunci pemilik asli keluar tanpa perlu tahu sandi lamanya.
            // Dibatasi seperti login: hanya percobaan GAGAL yang dihitung, per akun dan per IP,
            // supaya formulir ini tidak jadi orakel penebak sandi bagi sesi yang dibajak (29 Sep 2026).
            $konteks_laju = ['account_id' => (int) $user_id];
            $rate = $this->rate_limit_inspect('profile_password', $konteks_laju);
            if (empty($rate['success']) || empty($rate['allowed'])) {
                $this->rate_limit_reject($rate, 'Terlalu banyak percobaan password salah. Coba lagi nanti.');
                return;
            }
            $current_password = (string) $this->input->post('current_password');
            $user = $this->Auth_model->find_by_id($user_id);
            // Akun tanpa sandi sama sekali (sandi lama dicabut saat email dibuktikan lewat Google,
            // lihat User_model::check_google_user) membuat sandi pertamanya di sini; sesinya hanya
            // bisa berasal dari login Google, setara dengan isian sandi di onboarding.
            $valid_password = $user && (empty($user->kata_sandi)
                || password_verify($current_password, (string) $user->kata_sandi));
            $this->load->library('sensitive_buffer');
            $this->sensitive_buffer->wipe($current_password);
            if (isset($_POST['current_password'])) {
                $this->sensitive_buffer->wipe($_POST['current_password']);
            }
            if (!$valid_password) {
                $this->rate_limit_hit('profile_password', $konteks_laju);
                $this->session->set_flashdata('error', 'Password saat ini salah.');
                redirect('akun/profil');
                return;
            }
            if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) ||
                !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
                $this->session->set_flashdata('error', 'Password baru harus minimal 8 karakter, mengandung huruf besar, angka, dan simbol.');
                redirect('akun/profil');
                return;
            }
            if ($password !== $this->input->post('password_confirm')) {
                $this->session->set_flashdata('error', 'Password baru dan konfirmasinya tidak cocok.');
                redirect('akun/profil');
                return;
            }
            // Tanpa ini kedaluwarsa 90 hari bisa dilewati dengan mengetik ulang sandi lama.
            if (password_verify($password, (string) $user->kata_sandi)) {
                $this->session->set_flashdata('error', 'Password baru tidak boleh sama dengan password saat ini.');
                redirect('akun/profil');
                return;
            }
            $data['kata_sandi'] = password_hash($password, PASSWORD_BCRYPT);
            $this->sensitive_buffer->wipe($password);
            if (isset($_POST['password'])) { $this->sensitive_buffer->wipe($_POST['password']); }
            if (isset($_POST['password_confirm'])) { $this->sensitive_buffer->wipe($_POST['password_confirm']); }
            $data = array_merge($data, $this->Auth_model->password_lifetime_fields());
        }

        $this->User_model->update_user($user_id, $data);

        if (isset($data['kata_sandi'])) {
            $this->session->unset_userdata('password_change_required');
            $this->session->sess_regenerate(TRUE);
            $this->session->set_userdata('session_auth_token',
                $this->Auth_model->issue_session_token($user_id, $this->session->session_id));
        }

        // Update session
        $this->session->set_userdata('name', $name);
        if (!empty($username)) {
            $this->session->set_userdata('username', $username);
        }

        $this->session->set_flashdata('success', 'Profil berhasil diperbarui!');
        redirect('akun/profil');
    }

    /** Unduh salinan data milik akun setelah verifikasi sandi saat ini. */
    public function export_account_data() {
        if ($this->input->method() !== 'post') { show_404(); return; }
        $user_id = (int) $this->get_user_id();
        $rate = $this->rate_limit_consume('account_export', ['account_id' => $user_id]);
        if (empty($rate['success']) || empty($rate['allowed'])) {
            $this->rate_limit_reject($rate, 'Terlalu banyak permintaan ekspor. Coba lagi nanti.');
            return;
        }
        $this->load->library('sensitive_buffer');
        $password = (string) $this->input->post('current_password');
        $user = $this->Auth_model->find_by_id($user_id);
        $valid = $user && !empty($user->kata_sandi)
            && password_verify($password, (string) $user->kata_sandi);
        $this->sensitive_buffer->wipe($password);
        if (isset($_POST['current_password'])) {
            $this->sensitive_buffer->wipe($_POST['current_password']);
        }
        if (!$valid) {
            $this->catat_audit('ekspor_data_ditolak', 'Verifikasi sandi untuk ekspor data akun gagal', 'usr_akun', (string) $user_id);
            $this->session->set_flashdata('error', 'Password salah atau akun belum memiliki password. Data tidak diekspor.');
            redirect('akun/profil');
            return;
        }

        $data = NULL;
        try {
            $data = $this->User_model->export_account_data($user_id);
            $json = json_encode([
                'dieksport_pada' => date(DATE_ATOM),
                'cakupan' => 'Data akun dan catatan layanan yang terkait langsung dengan akun. Isi berkas unggahan tidak disertakan.',
                'data' => $data,
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            log_message('error', 'Ekspor data akun gagal untuk user_id=' . $user_id . ': ' . $e->getMessage());
            $this->session->set_flashdata('error', 'Ekspor data gagal. Silakan coba lagi atau hubungi admin.');
            redirect('akun/profil');
            return;
        } finally {
            $this->sensitive_buffer->wipe($data);
        }

        if (!$this->catat_audit('data_akun_diekspor', 'Pemilik akun mengunduh salinan data', 'usr_akun', (string) $user_id)) {
            $this->sensitive_buffer->wipe($json);
            $this->session->set_flashdata('error', 'Ekspor belum dapat dicatat. Silakan coba lagi.');
            redirect('akun/profil');
            return;
        }
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="data-akun-' . date('Ymd-His') . '.json"');
        header('Cache-Control: private, no-store, max-age=0');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        echo $json;
        $this->sensitive_buffer->wipe($json);
        exit;
    }
    /** Permintaan peninjauan penghapusan arsip layanan masuk antrean admin. */
    public function request_service_data_deletion() {
        if ($this->input->method() !== 'post') { show_404(); return; }
        $user_id = (int) $this->get_user_id();
        $rate = $this->rate_limit_consume('privacy_deletion_request', ['account_id' => $user_id]);
        if (empty($rate['success']) || empty($rate['allowed'])) {
            $this->rate_limit_reject($rate, 'Permintaan terlalu sering. Silakan coba lagi nanti.');
            return;
        }
        $this->load->model('Aduan_model');
        $title = Aduan_model::JUDUL_PENGHAPUSAN_DATA;
        $pending = $this->db->where('user_id', $user_id)->where('judul', $title)
            ->where('status !=', 'Selesai')->count_all_results('aduan');
        if ($pending) {
            $this->session->set_flashdata('error', 'Permintaan sebelumnya masih diproses. Lihat statusnya di menu Akun.');
            redirect('akun/profil');
            return;
        }
        $user = $this->Auth_model->find_by_id($user_id);
        if (!$user) { show_error('Akun tidak ditemukan.', 404); return; }
        $this->db->trans_begin();
        $id = $this->Aduan_model->create([
            'user_id' => $user_id,
            'nama' => (string) ($user->nama ?: $user->nama_pengguna ?: 'Pengguna'),
            'email' => (string) $user->email,
            'judul' => $title,
            'pesan' => 'Mohon tinjau penghapusan data layanan yang masih tersimpan setelah akun dihapus. Beri tahu data yang dapat dihapus, yang wajib diarsipkan, dan dasar retensinya.',
            'bidang_kode' => NULL,
            'lampiran' => NULL,
        ]);
        $audited = $id && $this->catat_audit('penghapusan_data_diminta',
            'Pemilik akun meminta peninjauan penghapusan data layanan', 'aduan', (string) $id);
        if (!$audited || !$this->db->trans_status()) {
            $this->db->trans_rollback();
            $this->session->set_flashdata('error', 'Permintaan belum dapat dicatat. Coba lagi.');
        } else {
            $this->db->trans_commit();
            $this->notify_admin_push([['role' => 'admin']], 'Permintaan data pribadi',
                'Ada permintaan penghapusan data layanan untuk ditinjau.', 'Admin_Aduan?status=Baru', 'privasi-' . (int) $id);
            $this->session->set_flashdata('success', 'Permintaan tersimpan. Statusnya dapat dipantau pada menu Akun.');
        }
        redirect('akun/profil');
    }
    public function delete_account() {
        $user_id = $this->get_user_id();

        // Ensure POST request
        if ($this->input->method() !== 'post') {
            show_404();
            return;
        }

        // Konfirmasi ketik-nama di modal cuma JS - siapa pun yang sempat
        // memakai sesi ini bisa POST langsung tanpa pernah mengetiknya.
        // Password saat ini adalah bukti kepemilikan yang sebenarnya
        // (roadmap T5 S13); tanpa ini FK CASCADE menghapus seluruh
        // pengajuan SRP2 pemilik asli.
        // Dibatasi seperti ekspor data (account_export), supaya endpoint ini tidak jadi
        // orakel penebak sandi bagi sesi yang dibajak.
        $rate = $this->rate_limit_consume('account_delete', ['account_id' => (int) $user_id]);
        if (empty($rate['success']) || empty($rate['allowed'])) {
            $this->rate_limit_reject($rate, 'Terlalu banyak percobaan hapus akun. Coba lagi nanti.');
            return;
        }
        $current_password = (string) $this->input->post('current_password');
        $user = $this->Auth_model->find_by_id($user_id);
        $valid_password = $user && !empty($user->kata_sandi)
            && password_verify($current_password, (string) $user->kata_sandi);
        $this->load->library('sensitive_buffer');
        $this->sensitive_buffer->wipe($current_password);
        if (isset($_POST['current_password'])) {
            $this->sensitive_buffer->wipe($_POST['current_password']);
        }
        if (!$valid_password) {
            $this->catat_audit('hapus_akun_ditolak', 'Verifikasi sandi untuk hapus akun gagal', 'usr_akun', (string) $user_id);
            $this->session->set_flashdata('error', 'Password salah. Akun tidak dihapus.');
            redirect('akun/profil');
            return;
        }

        $success = $this->User_model->delete_user_account($user_id);

        if ($success) {
            // Destroy session
            $this->session->sess_destroy();
            redirect('Auth/login?msg=account_deleted');
        } else {
            $this->session->set_flashdata('error', 'Gagal menghapus akun. Silakan coba lagi.');
            redirect('akun/profil');
        }
    }
}
