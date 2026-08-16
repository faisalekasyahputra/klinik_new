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
            ->select('sf_housing_queue.id, ticket_code, status_antrean, catatan_admin, source_mode, sf_housing_queue.created_at, sf_programs.nama_program')
            ->from('sf_housing_queue')
            ->join('sf_programs', 'sf_programs.id=sf_housing_queue.program_id', 'left')
            ->where('sf_housing_queue.user_id', (int) $user_id)
            ->order_by('sf_housing_queue.created_at', 'DESC')->get()->result() as $r) {
            $status = housing_queue_statuses()[$r->status_antrean] ?? ['label' => 'Sedang Diverifikasi', 'badge' => 'pending'];
            $items[] = [
                'jenis' => 'Antrean Perumahan - ' . ($r->nama_program ?: 'Program'), 'icon' => 'ph-ticket',
                'judul' => !empty($r->ticket_code) ? $r->ticket_code : 'Tiket belum tersedia',
                'status_label' => $status['label'], 'status_kelas' => $status['badge'],
                'created_at' => $r->created_at,
                'aksi_url' => null,
                'aksi_post_url' => $r->status_antrean === 'needs_revision'
                    ? 'warga/pendataan' : null,
                'aksi_post_fields' => $r->status_antrean === 'needs_revision'
                    ? ['action' => 'start_revision', 'queue_id' => $r->id] : [],
                'aksi_label' => $r->status_antrean === 'needs_revision'
                    ? 'Mulai Perbaikan' : null,
                'catatan_admin' => $r->catatan_admin,
                'is_simulation' => $r->source_mode === 'simulation',
                // Perjalanan pengajuan, bukan cuma status terakhir - supaya
                // pemohon tahu sudah sampai mana dan apa yang sudah terjadi.
                'riwayat' => $this->Housing_assessment_model->get_owned_timeline($r->id, (int) $user_id),
            ];
        }

        // catatan_admin ikut diambil supaya pelapor tahu ALASAN status berubah,
        // bukan cuma statusnya - pola yang sudah dipakai SRP2 tapi dulu belum
        // ada di aduan (AUDIT_ROLE_ADMIN_SCOPED.md #7).
        foreach ($this->db->select('id, judul, bidang, status, catatan_admin, created_at')
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
            $sp2  = $srp2 ? $this->db->get_where('srp2_registrations', ['id' => $srp2['registration_id']])->row() : NULL;
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
                    'judul' => $sp2->nama_perusahaan ?: '(Nama perusahaan belum diisi)',
                    'status_label' => $status[0], 'status_kelas' => $status[1],
                    // updated_at, bukan created_at - draft bisa dibuat jauh sebelum
                    // benar-benar diisi/dikirim, tanggal aktivitas terakhir lebih relevan.
                    'created_at' => $sp2->updated_at ?: $sp2->created_at,
                    // SEMUA status mengarah ke wizard - itu tempat dokumen pengajuan
                    // ini berada, dan wizard sendiri yang menentukan mode: Draft/Ditolak
                    // bisa diedit, Pending/Diterima tampil read-only.
                    //
                    // JANGAN arahkan ke akun/profil. Pernah dilakukan untuk status
                    // Diterima dengan alasan "dokumen sudah final, yang bisa dikelola
                    // tinggal data perusahaan" - dan itu keliru: tombol pada baris
                    // PENGAJUAN harus membawa ke pengajuan itu, bukan ke halaman lain.
                    // Edit data perusahaan punya jalannya sendiri lewat menu Profil.
                    'aksi_url' => 'Pengembang/syarat',
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
                    'aksi_label' => $p->status === 'Diajukan' ? 'Lihat / Ubah' : 'Lihat',
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
     * Edit profil pribadi + (khusus pengembang) data perusahaan SRP2 + hapus akun.
     */
    public function profil() {
        $user_id = $this->get_user_id();
        $user = $this->Auth_model->find_by_id($user_id);

        $datacontent = ['user' => $user, 'title' => 'Profil Saya'];

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
                ? $this->db->get_where('srp2_registrations', ['id' => $srp2['registration_id']])->row()
                : NULL;
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
            ->order_by('id', 'DESC')->get('srp2_registrations')->row();
        if (!$pengajuan) {
            $this->session->set_flashdata('error', 'Data pengajuan sertifikasi tidak ditemukan.');
            redirect('akun/profil');
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
        $data = [
            'nama_perusahaan' => strtoupper(trim($this->input->post('nama_perusahaan'))),
            'alamat_kantor'   => trim($this->input->post('alamat_kantor')),
            'asosiasi'        => $this->input->post('asosiasi'),
            'no_keanggotaan'  => trim($this->input->post('no_keanggotaan')),
            'instagram'       => trim($this->input->post('instagram')),
            'website'         => trim($this->input->post('website')),
            'sosmed_lainnya'  => trim($this->input->post('sosmed_lainnya')),
        ];

        if (empty($data['nama_perusahaan']) || empty($data['alamat_kantor'])) {
            $this->session->set_flashdata('error', 'Nama Perusahaan dan Alamat Kantor tidak boleh kosong.');
            redirect('akun/profil');
            return;
        }

        // Daftarnya dari srp2_daftar_asosiasi() sejak 14 Agt 2026, bukan
        // salinan literal di sini - formulir admin (Admin_Srp2::save()) kini
        // memvalidasi ke daftar yang SAMA.
        $this->load->helper('srp2');
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
        $this->db->update('srp2_registrations', $data);

        // Perubahan ikut menular ke direktori publik kalau pengajuan ini memang
        // sudah terbit di sana. Dulu baris direktori hanya diisi SEKALI saat
        // approve, sehingga ganti alamat/website/Instagram tidak pernah sampai
        // ke publik - padahal formnya berlabel "Kontak publik - ditampilkan di
        // halaman profil pengembang". Satu fungsi upsert yang sama dengan yang
        // dipakai Admin_Srp2::proses(), bukan salinan kedua.
        $tersinkron = FALSE;
        if ( ! empty($pengajuan->certified_developer_id)) {
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

    public function update_profile() {
        $user_id = $this->get_user_id();

        $username = html_escape($this->input->post('username'));
        $username = preg_replace('/\s+/', '', strtolower($username));
        $name     = html_escape($this->input->post('name'));
        $phone    = html_escape($this->input->post('phone'));

        if (empty($name)) {
            $this->session->set_flashdata('error', 'Nama Lengkap tidak boleh kosong.');
            redirect('akun/profil');
            return;
        }

        // Check unique username if provided
        if (!empty($username)) {
            $this->db->where('username', $username);
            $this->db->where('id !=', $user_id);
            if ($this->db->count_all_results('usr_users') > 0) {
                $this->session->set_flashdata('error', 'Username sudah digunakan, silakan pilih yang lain.');
                redirect('akun/profil');
                return;
            }
        }

        $data = [
            'name'  => $name,
            'phone' => $phone
        ];

        if (!empty($username)) {
            $data['username'] = $username;
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
                ->get_where('usr_users', ['id' => $user_id])->row('nik_lookup_hash');

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
                $sidik = $this->encryption_lib->deterministic_hash($nik_kirim);
                $dipakai = $this->db->where('nik_lookup_hash', $sidik)
                    ->where('id !=', $user_id)->count_all_results('usr_users');
                if ($dipakai > 0) {
                    $this->session->set_flashdata('error',
                        'NIK ini sudah terdaftar pada akun lain. Satu NIK hanya untuk satu akun.');
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
            $current_password = (string) $this->input->post('current_password');
            $user = $this->Auth_model->find_by_id($user_id);
            if (!password_verify($current_password, (string) $user->password)) {
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
            $data['password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        $this->User_model->update_user($user_id, $data);

        // Update session
        $this->session->set_userdata('name', $name);
        if (!empty($username)) {
            $this->session->set_userdata('username', $username);
        }

        $this->session->set_flashdata('success', 'Profil berhasil diperbarui!');
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
        $current_password = (string) $this->input->post('current_password');
        $user = $this->Auth_model->find_by_id($user_id);
        if (!password_verify($current_password, (string) $user->password)) {
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
