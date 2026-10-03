<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_Users extends Admin_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->config('roles');
        $this->load->model('auth_model');
        // Superadmin access is already checked in Admin_Controller
    }

    public function index()
    {
        $data['title'] = 'Akses Staf'; // = label sidebar

        // Cari + urut + paginasi semuanya server-side (B7/B8).
        $table = $this->table_state(['created_at', 'nama', 'email', 'peran'], 'created_at');
        $data['base_url'] = 'Admin_Users';

        // from() di depan lalu count_all_results('', FALSE) - JANGAN
        // count_all_results('usr_akun', FALSE) diikuti get('usr_akun'),
        // keduanya menyetel FROM sehingga jadi "FROM usr_akun, usr_akun".
        $this->db->from('usr_akun');
        if ($table['q'] !== '') {
            $this->db->group_start()
                ->like('nama', $table['q'])->or_like('email', $table['q'])
                ->or_like('nama_pengguna', $table['q'])->group_end();
        }
        $table += $this->paginate_state($this->db->count_all_results('', FALSE));

        $data['users'] = $this->db->order_by($table['sort'], $table['dir'])
            ->limit($table['per_page'], $table['offset'])
            ->get()->result();
        $data['warga_nik_bound'] = [];
        $user_ids = array_map(static function ($user) { return (int) $user->id; }, $data['users']);
        if ($user_ids && $this->db->table_exists('sf_profil_warga')) {
            $profiles = $this->db->select('user_id, confirmed_at')->where_in('user_id', $user_ids)
                ->get('sf_profil_warga')->result_array();
            // Nilai = NIK terverifikasi (nama akun + tanggal lahir cocok dengan SIMPERUM, confirmed_at).
            foreach ($profiles as $profile) $data['warga_nik_bound'][(int) $profile['user_id']] = ! empty($profile['confirmed_at']) ? 'terverifikasi' : 'belum';
        }
        $data['table'] = $data['pager'] = $table;
        $data['available_roles'] = $this->config->item('available_roles');
        $data['kabupaten_list'] = $this->db->order_by('nama', 'ASC')->get('kabupaten')->result();
        $data['bidang_list'] = $this->db->order_by('nama', 'ASC')->get('bidang')->result();
        $this->render_admin('admin/users/index', $data);
    }

    /**
     * Ubah role user + scope (kabupaten/bidang) kalau perlu.
     * Superadmin-only (sudah digerbangi Admin_Controller).
     */
    public function update_role()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }

        $id = (int) $this->input->post('id');
        $role = trim((string) $this->input->post('role', TRUE));
        $available_roles = array_keys($this->config->item('available_roles'));

        if ( ! $id || ! in_array($role, $available_roles, TRUE)) {
            $this->session->set_flashdata('error', 'Role tidak valid.');
            redirect('Admin_Users');
            return;
        }

        $payload = ['peran' => $role, 'kabupaten_id' => null, 'bidang_kode' => null];

        if (in_array($role, $this->config->item('roles_scoped_kabupaten'), TRUE)) {
            $kabupaten_id = (int) $this->input->post('kabupaten_id');
            if ( ! $kabupaten_id || ! $this->db->where('id', $kabupaten_id)->get('kabupaten')->row()) {
                $this->session->set_flashdata('error', 'Pilih kabupaten/kota untuk role Admin Kabupaten/Kota.');
                redirect('Admin_Users');
                return;
            }
            $payload['kabupaten_id'] = $kabupaten_id;
        }

        if (in_array($role, $this->config->item('roles_scoped_bidang'), TRUE)) {
            $bidang_kode = trim((string) $this->input->post('bidang_kode', TRUE));
            if ( ! $bidang_kode || ! $this->db->where('kode', $bidang_kode)->get('bidang')->row()) {
                $this->session->set_flashdata('error', 'Pilih bidang untuk role Admin Bidang.');
                redirect('Admin_Users');
                return;
            }
            $payload['bidang_kode'] = $bidang_kode;
        }

        // Keadaan SEBELUM diambil dulu - sesudah UPDATE ia sudah tidak ada, dan
        // "diubah dari apa" justru separuh isi dari sebuah jejak audit.
        $sebelum = $this->db->get_where('usr_akun', ['id' => $id])->row();
        if ( ! $sebelum) {
            $this->session->set_flashdata('error', 'Akun tidak ditemukan.');
            redirect('Admin_Users');
            return;
        }

        /**
         * PENGUNCIAN TOTAL YANG PALING MUDAH TERJADI ada di sini, bukan di
         * tombol Nonaktifkan.
         *
         * Superadmin membuka Akses Staf, mengubah role DIRINYA SENDIRI menjadi
         * "Warga", dan sejak detik itu tidak ada satu pun akun yang bisa membuka
         * panel - termasuk untuk membatalkannya. Pemulihannya harus lewat DB.
         * Satu klik, dan di DB ini cuma ada SATU superadmin.
         *
         * Berbeda dari menonaktifkan: menurunkan role diri sendiri sama sekali
         * tidak tertahan penjaga akun-sendiri, karena "mengubah role" tidak
         * terlihat seperti mencabut akses sampai akibatnya terjadi.
         *
         * Ditemukan 3 Agt 2026 saat menelusuri kenapa penjaga superadmin-terakhir
         * di ubah_status tidak pernah menyala.
         */
        if ($sebelum->peran === 'admin' && $role !== 'admin' && $this->sisa_superadmin($sebelum, $role) === 0) {
            $this->catat_audit('role_diubah_ditolak',
                'DITOLAK: menurunkan role Super Admin terakhir (' . $sebelum->email . ') menjadi ' . $role,
                'usr_akun', (string) $id);
            $this->session->set_flashdata('error',
                $sebelum->id == $this->get_user_id()
                    ? 'Anda satu-satunya Super Admin. Menurunkan role Anda sendiri akan mengunci semua orang dari panel ini - angkat Super Admin lain dulu.'
                    : 'Ini satu-satunya Super Admin yang masih bisa masuk. Angkat Super Admin lain dulu sebelum menurunkan rolenya.');
            redirect('Admin_Users');
            return;
        }

        // Role, kabupaten, dan bidang dibaca dari sesi; tanpa ini sesi yang sedang
        // berjalan tetap memegang hak lama (bahkan bisa menaikkan dirinya lagi lewat
        // update_role). Mengosongkan hash sesi mengakhirinya di request berikutnya,
        // pola yang sama dengan reset_sandi().
        $berubah = $sebelum->peran !== $role
            || (string) $sebelum->kabupaten_id !== (string) $payload['kabupaten_id']
            || (string) $sebelum->bidang_kode !== (string) $payload['bidang_kode'];
        $sesi_putus = $berubah ? ['sesi_aktif_hash' => NULL, 'sesi_aktif_id_hash' => NULL, 'sesi_aktif_at' => NULL] : [];

        if ( ! $this->db->where('id', $id)->update('usr_akun', $payload + $sesi_putus)) {
            $this->session->set_flashdata('error', 'Role pengguna belum tersimpan. Coba lagi.');
            redirect('Admin_Users');
            return;
        }

        if ($sebelum->peran !== $role && $this->db->table_exists('usr_hak_modul_admin')) {
            $this->db->where('user_id', $id)->delete('usr_hak_modul_admin');
        }
        $this->catat_audit('role_diubah',
            'Mengubah role ' . ($sebelum->email ?? '#' . $id) . ' dari '
            . ($sebelum->peran ?: '(kosong)') . ' menjadi ' . $role,
            'usr_akun', (string) $id,
            ['dari' => ['role' => $sebelum->peran ?? NULL, 'kabupaten_id' => $sebelum->kabupaten_id ?? NULL,
                        'bidang_kode' => $sebelum->bidang_kode ?? NULL],
             'ke'   => $payload]);

        if ($berubah && $id === (int) $this->get_user_id()) {
            $this->session->sess_destroy();
            redirect('Auth/login');
            return;
        }
        $this->session->set_flashdata('success', $berubah
            ? 'Role pengguna diperbarui. Sesi akun itu diakhiri; perubahan berlaku saat ia masuk lagi.'
            : 'Role pengguna diperbarui.');
        redirect('Admin_Users');
    }

    /**
     * Buat akun staff (admin_kabkota/admin_bidang/dst) langsung oleh superadmin -
     * tidak lewat pendaftaran publik Auth::onboarding().
     */
    public function create_staff()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }

        $this->load->library('form_validation');
        $this->form_validation->set_rules('name', 'Nama', 'required|trim|max_length[150]');
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|max_length[100]|is_unique[usr_akun.email]',
            ['is_unique' => 'Akun staff belum dibuat: email tersebut sudah terdaftar.']);
        // Aturan sandi sama dengan daftar dan ganti sandi; nomor HP divalidasi di server
        // (temuan UAT universitas U1/U2, 28 Sep 2026).
        $this->form_validation->set_rules('password', 'Password', 'required|sandi_kuat');
        $this->form_validation->set_rules('phone', 'Nomor HP', 'trim|max_length[20]|nomor_hp');
        $this->form_validation->set_rules('role', 'Role', 'required|in_list[' . implode(',', array_keys($this->config->item('available_roles'))) . ']');

        // Formulir Tambah Universitas di tab KKN (Admin_Kemitraan/universitas) memakai endpoint
        // ini juga; admin dikembalikan ke tab itu, bukan dipindah ke Manajemen Pengguna.
        // Hanya tujuan di daftar ini yang diterima (bukan URL bebas dari formulir).
        $kembali = $this->input->post('kembali', TRUE) === 'Admin_Kemitraan/universitas'
            ? 'Admin_Kemitraan/universitas' : 'Admin_Users';

        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', strip_tags(validation_errors()));
            redirect($kembali);
            return;
        }

        $role = $this->input->post('role', TRUE);
        $payload = [
            'nama'               => $this->input->post('name', TRUE),
            'email'              => $this->input->post('email', TRUE),
            'kata_sandi'         => password_hash($this->input->post('password'), PASSWORD_BCRYPT),
            'peran'              => $role,
            'status'             => 'active',
            'profil_lengkap'  => 1,
            'email_verified_at'  => date('Y-m-d H:i:s'),
            'created_at'         => date('Y-m-d H:i:s'),
        // Sandi awal diketahui admin, jadi wajib diganti di login pertama (keputusan 29 Sep 2026).
        ] + $this->auth_model->password_awal_fields();

        /* Telepon OPSIONAL - bukan field standar akun staf, jadi kolomnya
           dilewati sama sekali kalau kosong (bukan disimpan '' atau NULL
           eksplisit tanpa alasan). Ditambahkan untuk formulir "Tambah
           Universitas" (Admin_Kemitraan::universitas(), permintaan user
           22 Agt 2026) - KemitraanPortal::kkn_tambah() MEWAJIBKAN
           usr_akun.no_hp terisi sebelum akun bisa mengajukan KKN, jadi
           mengisinya di sini sekaligus berarti akun universitas yang baru
           dibuat admin langsung bisa dipakai tanpa mampir dulu ke Profil
           Saya. Field ini tidak berbahaya untuk role lain - cuma
           menyimpan apa yang dikirim, sama seperti Pengaturan::update_profile(). */
        $telp = trim((string) $this->input->post('phone', TRUE));
        if ($telp !== '') { $payload['no_hp'] = $telp; }

        if (in_array($role, $this->config->item('roles_scoped_kabupaten'), TRUE)) {
            $kabupaten_id = (int) $this->input->post('kabupaten_id');
            if ( ! $kabupaten_id || ! $this->db->where('id', $kabupaten_id)->get('kabupaten')->row()) {
                $this->session->set_flashdata('error', 'Pilih kabupaten/kota untuk role Admin Kabupaten/Kota.');
                redirect($kembali);
                return;
            }
            $payload['kabupaten_id'] = $kabupaten_id;
        }

        if (in_array($role, $this->config->item('roles_scoped_bidang'), TRUE)) {
            $bidang_kode = trim((string) $this->input->post('bidang_kode', TRUE));
            if ( ! $bidang_kode || ! $this->db->where('kode', $bidang_kode)->get('bidang')->row()) {
                $this->session->set_flashdata('error', 'Pilih bidang untuk role Admin Bidang.');
                redirect($kembali);
                return;
            }
            $payload['bidang_kode'] = $bidang_kode;
        }

        // Titik paling berbahaya dari enam titik A5: `usr_akun.email` ber-UNIQUE,
        // jadi email duplikat membuat INSERT ditolak. Selama ini superadmin tetap
        // diberi tahu akunnya jadi - dan setelah U0 mematikan db_debug, penolakan
        // itu sepenuhnya senyap. Sebabnya disebut apa adanya supaya bisa ditindak.
        if ( ! $this->db->insert('usr_akun', $payload)) {
            $galat = $this->db->error();
            $duplikat = isset($galat['code']) && (int) $galat['code'] === 1062;
            $this->session->set_flashdata('error', $duplikat
                ? 'Akun staff belum dibuat: email tersebut sudah terdaftar.'
                : 'Akun staff belum dibuat. Periksa isian lalu coba lagi.');
            redirect($kembali);
            return;
        }
        $this->catat_audit('staf_dibuat',
            'Membuat akun staf ' . $payload['email'] . ' dengan role ' . $role,
            'usr_akun', (string) $this->db->insert_id(),
            ['role' => $role, 'kabupaten_id' => $payload['kabupaten_id'] ?? NULL,
             'bidang_kode' => $payload['bidang_kode'] ?? NULL]);

        $this->session->set_flashdata('success', 'Akun staff baru berhasil dibuat.');
        redirect($kembali);
    }

    // =====================================================================
    // AKSES STAF - nonaktifkan/aktifkan, reset sandi, buka kunci.
    //
    // Sebelum ini superadmin hanya bisa MEMBUAT akun dan mengubah role. Tidak
    // ada cara mencabut akses tanpa menyentuh DB langsung, dan tidak ada cara
    // membuka akun yang terkunci 15 menit selain menunggu.
    // =====================================================================

    /**
     * Penjaga bersama untuk setiap tindakan yang menyentuh akun lain.
     *
     * Mengembalikan baris user, atau NULL setelah memasang flash + redirect.
     * Dipusatkan supaya penambahan tindakan berikutnya tidak perlu menyalin
     * ulang pemeriksaan yang sama - dan tidak bisa lupa menyalinnya.
     */
    private function sasaran_sah($izinkan_diri_sendiri = FALSE)
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }

        $id = (int) $this->input->post('id');
        $user = $id ? $this->db->get_where('usr_akun', ['id' => $id])->row() : NULL;
        if ( ! $user) {
            $this->session->set_flashdata('error', 'Akun tidak ditemukan.');
            redirect('Admin_Users');
            return NULL;
        }

        // Akun sendiri: dilarang untuk tindakan yang mencabut akses. Superadmin
        // yang menonaktifkan dirinya sendiri kehilangan satu-satunya jalan untuk
        // membatalkannya - pemulihannya harus lewat DB.
        if ( ! $izinkan_diri_sendiri && (int) $user->id === (int) $this->get_user_id()) {
            $this->catat_audit('tindakan_diri_sendiri_ditolak',
                'DITOLAK: mencoba melakukan tindakan pencabutan akses pada akun sendiri',
                'usr_akun', (string) $user->id);
            $this->session->set_flashdata('error',
                'Anda tidak bisa melakukan itu pada akun Anda sendiri.');
            redirect('Admin_Users');
            return NULL;
        }
        return $user;
    }

    /**
     * Apakah $user adalah satu-satunya superadmin yang masih bisa masuk?
     *
     * Dihitung dari kondisi yang SAMA dengan gerbang login (`status` selain
     * 'nonaktif'), bukan dari `status = 'active'`. Enam akun di DB ini berstatus
     * `restricted` dan tetap bisa masuk; menghitung dengan `= active` akan
     * menyimpulkan nol superadmin dan memblokir tindakan yang sah.
     */
    /**
     * Berapa superadmin yang MASIH BISA MASUK kalau $user diubah jadi $role_baru?
     *
     * Dihitung dengan syarat yang SAMA dengan gerbang login (`status` selain
     * 'nonaktif'), bukan `status = 'active'`. Enam akun di DB ini berstatus
     * `restricted` dan tetap bisa masuk; menghitung dengan `= active` akan
     * menyimpulkan nol superadmin dan memblokir tindakan yang sah.
     *
     * @param string|NULL $role_baru  NULL = perannya tidak berubah, hanya
     *                                statusnya yang jadi 'nonaktif'.
     */
    private function sisa_superadmin($user, $role_baru = NULL)
    {
        $lain = $this->db->where('peran', 'admin')
            ->where('id !=', (int) $user->id)
            ->where("LOWER(TRIM(COALESCE(status,''))) !=", 'nonaktif')
            ->count_all_results('usr_akun');

        // Target ikut dihitung kalau SESUDAH perubahan ia masih admin yang bisa
        // masuk. $role_baru NULL berarti kita sedang menonaktifkannya.
        $target_tetap_admin = $role_baru === NULL ? FALSE : ($role_baru === 'admin');
        return $lain + ($target_tetap_admin ? 1 : 0);
    }

    private function superadmin_terakhir($user)
    {
        // Catatan jujur: lewat UI, cabang ini nyaris tidak bisa tercapai -
        // pelakunya sendiri selalu terhitung sebagai "admin lain" yang masih
        // bisa masuk, jadi target tidak pernah menjadi yang terakhir; dan kalau
        // targetnya diri sendiri, penjaga akun-sendiri menyala lebih dulu.
        // Dipertahankan sebagai jaring kalau kelak ada jalur tulis lain.
        // Lubang yang BENAR-BENAR bisa mengunci semua orang ada di update_role
        // (turunkan role admin terakhir) dan dijaga tersendiri di sana.
        return $user->peran === 'admin' && $this->sisa_superadmin($user, NULL) === 0;
    }

    public function ubah_status()
    {
        $user = $this->sasaran_sah();
        if ( ! $user) { return; }

        $ke = $this->input->post('status', TRUE) === 'nonaktif' ? 'nonaktif' : 'active';

        // Menonaktifkan superadmin terakhir = mengunci semua orang dari panel.
        // Tidak ada jalan pulih lewat aplikasi; pemulihannya harus lewat DB.
        if ($ke === 'nonaktif' && $this->superadmin_terakhir($user)) {
            // Percobaan yang DITOLAK ikut dicatat. Jejak audit yang hanya
            // merekam keberhasilan tidak bisa menjawab "siapa yang mencoba
            // mematikan panel ini" - dan justru percobaan itu yang perlu
            // terlihat, terlepas berhasil atau tidak.
            $this->catat_audit('akun_dinonaktifkan_ditolak',
                'DITOLAK: mencoba menonaktifkan Super Admin terakhir (' . $user->email . ')',
                'usr_akun', (string) $user->id);
            $this->session->set_flashdata('error',
                'Ini satu-satunya Super Admin yang masih bisa masuk. Angkat Super Admin lain dulu sebelum menonaktifkannya.');
            redirect('Admin_Users');
            return;
        }

        $this->db->where('id', (int) $user->id)->update('usr_akun', ['status' => $ke]);
        $this->catat_audit($ke === 'nonaktif' ? 'akun_dinonaktifkan' : 'akun_diaktifkan',
            ($ke === 'nonaktif' ? 'Menonaktifkan' : 'Mengaktifkan') . ' akun ' . $user->email,
            'usr_akun', (string) $user->id, ['dari' => $user->status, 'ke' => $ke]);

        $this->session->set_flashdata('success', $ke === 'nonaktif'
            ? 'Akun ' . $user->email . ' dinonaktifkan dan tidak bisa masuk lagi.'
            : 'Akun ' . $user->email . ' diaktifkan kembali.');
        redirect('Admin_Users');
    }

    /**
     * Buka akun yang terkunci karena percobaan login gagal.
     *
     * Boleh dilakukan pada akun sendiri - ini tindakan MEMULIHKAN akses, bukan
     * mencabutnya, jadi larangan "jangan sentuh diri sendiri" tidak berlaku.
     */
    public function buka_kunci()
    {
        $user = $this->sasaran_sah(TRUE);
        if ( ! $user) { return; }

        $this->db->where('id', (int) $user->id)
            ->update('usr_akun', ['gagal_masuk' => 0, 'terkunci_sampai' => NULL]);
        $this->catat_audit('kunci_dibuka', 'Membuka kunci akun ' . $user->email,
            'usr_akun', (string) $user->id,
            ['login_attempts_sebelumnya' => $user->gagal_masuk, 'terkunci_sampai' => $user->terkunci_sampai]);

        $this->session->set_flashdata('success', 'Kunci akun ' . $user->email . ' dibuka.');
        redirect('Admin_Users');
    }

    /**
     * Lepaskan NIK dari akun warga yang belum pernah mengirim penilaian.
     * Pengajuan terkirim tetap menjadi arsip resmi dan memblokir reset agar
     * satu akun tidak dipakai bergantian oleh beberapa orang.
     */
    public function reset_nik()
    {
        $user = $this->sasaran_sah(TRUE);
        if ( ! $user) { return; }

        $alasan = trim((string) $this->input->post('alasan', TRUE));
        if ($user->peran !== 'warga') {
            $this->session->set_flashdata('error', 'Reset NIK hanya tersedia untuk akun Warga.');
            redirect('Admin_Users'); return;
        }
        if (mb_strlen($alasan) < 10 || mb_strlen($alasan) > 500) {
            $this->session->set_flashdata('error', 'Alasan reset NIK wajib diisi 10 sampai 500 karakter.');
            redirect('Admin_Users'); return;
        }

        $profile = $this->db->select('id')->get_where('sf_profil_warga', ['user_id'=>(int)$user->id])->row();
        if ( ! $profile) {
            $this->session->set_flashdata('error', 'Akun ini belum terhubung dengan NIK.');
            redirect('Admin_Users'); return;
        }

        $submitted = $this->db->where('user_id', (int) $user->id)
            ->where('status !=', 'draft')->count_all_results('sf_penilaian_perumahan');
        if ($submitted > 0) {
            $this->catat_audit('reset_nik_ditolak',
                'DITOLAK: reset NIK akun ' . $user->email . ' karena memiliki penilaian terkirim',
                'usr_akun', (string) $user->id, ['alasan'=>$alasan]);
            $this->session->set_flashdata('error',
                'NIK tidak dapat direset karena akun memiliki pengajuan yang sudah dikirim. Data harus tetap menjadi arsip.');
            redirect('Admin_Users'); return;
        }

        $drafts = $this->db->select('id')->get_where('sf_penilaian_perumahan',
            ['user_id'=>(int)$user->id, 'status'=>'draft'])->result_array();
        $draft_ids = array_map('intval', array_column($drafts, 'id'));
        $files = [];
        if ($draft_ids) {
            $files = $this->db->select('penilaian_id,path_privat')->where_in('penilaian_id', $draft_ids)
                ->get('sf_berkas_penilaian')->result_array();
        }

        $this->db->trans_start();
        if ($draft_ids) $this->db->where_in('id', $draft_ids)->delete('sf_penilaian_perumahan');
        $this->db->where('id', (int) $profile->id)->delete('sf_profil_warga');
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            $this->session->set_flashdata('error', 'Reset NIK gagal disimpan. Coba lagi.');
            redirect('Admin_Users'); return;
        }

        foreach ($files as $file) {
            @unlink($this->private_upload_dir('warga_assessment', (int)$file['penilaian_id'])
                . basename((string)$file['path_privat']));
        }
        foreach ($draft_ids as $draft_id) @rmdir($this->private_upload_dir('warga_assessment', $draft_id));

        $this->catat_audit('nik_warga_direset',
            'Mereset hubungan NIK akun warga ' . $user->email,
            'usr_akun', (string) $user->id,
            ['alasan'=>$alasan, 'draft_dihapus'=>count($draft_ids)]);
        $this->session->set_flashdata('success',
            'NIK akun ' . $user->email . ' berhasil direset. Warga dapat memasukkan NIK kembali.');
        redirect('Admin_Users');
    }
    public function reset_sandi()
    {
        $user = $this->sasaran_sah(TRUE);
        if ( ! $user) { return; }

        $sandi = (string) $this->input->post('password');
        // Aturan sama dengan sandi_kuat (MY_Form_validation) dan reset oleh admin bidang (29 Sep 2026).
        $this->load->library('form_validation');
        if ( ! $this->form_validation->sandi_kuat($sandi)) {
            $this->session->set_flashdata('error', 'Password baru harus minimal 8 karakter, mengandung huruf besar, angka, dan simbol.');
            redirect('Admin_Users');
            return;
        }

        // Penghitung gagal ikut direset: sandi baru yang langsung disambut
        // "akun terkunci" adalah cara paling cepat membuat orang mengira
        // resetnya tidak berhasil.
        $this->db->where('id', (int) $user->id)->update('usr_akun', [
            'kata_sandi' => password_hash($sandi, PASSWORD_BCRYPT),
            'gagal_masuk' => 0, 'terkunci_sampai' => NULL,
            'sesi_aktif_hash' => NULL, 'sesi_aktif_id_hash' => NULL, 'sesi_aktif_at' => NULL,
        // Sandi hasil reset diketahui admin, jadi wajib diganti di login berikutnya (29 Sep 2026).
        ] + $this->auth_model->password_awal_fields());

        // Sandinya TIDAK ikut dicatat, bahkan tidak sebagian. Jejak audit dibaca
        // orang yang tidak selalu berhak tahu isinya.
        $this->catat_audit('sandi_direset', 'Mereset password akun ' . $user->email,
            'usr_akun', (string) $user->id);

        // Hash sesi di atas ikut mengakhiri sesi pelaku bila sasarannya diri sendiri; pemeriksa
        // sesi tunggal lalu menimpa flash sukses dengan "Sesi Anda telah berakhir". Sesinya
        // diakhiri di sini dan konfirmasinya dibawa lewat ?msg= (pola Pengaturan::delete_account).
        if ((int) $user->id === (int) $this->get_user_id()) {
            $this->session->sess_destroy();
            redirect('Auth/login?msg=sandi_diganti');
            return;
        }

        $this->session->set_flashdata('success',
            'Password ' . $user->email . ' diganti. Sampaikan ke yang bersangkutan lewat jalur pribadi; sandi itu wajib diganti saat masuk.');
        redirect('Admin_Users');
    }
}
