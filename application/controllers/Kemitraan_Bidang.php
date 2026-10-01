<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Tinjauan tahap dua KKN/Magang oleh admin bidang.
 *
 * Alur suratnya: mahasiswa mengirim surat pengantar -> sekretariat Disperakim
 * meneruskan atau menolak -> BIDANG tujuan memutuskan ->
 * surat balasan diunggah sekretariat. Layar ini meja yang kedua.
 *
 * Scope-nya datang dari `Admin_Bidang_Controller`: `$this->my_bidang_kode` diambil
 * dari sesi, bukan dari request. Yang menentukan pendaftaran mana yang terlihat
 * adalah `bidang_kode` pada pendaftarannya - kolom sungguhan sejak migrasi
 * 20260701000031, bukan pencocokan lewat nama.
 */
class Kemitraan_Bidang extends Admin_Bidang_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('kemitraan_slot_model', 'slot');
    }

    public function index()
    {
        $data['title'] = 'Magang Bidang Saya';

        $table = $this->table_state([
            'kkn_magang_pendaftaran.created_at', 'usr_users.name',
            'kkn_magang_pendaftaran.instansi_asal', 'kkn_magang_pendaftaran.status',
        ], 'kkn_magang_pendaftaran.created_at');
        $data['base_url'] = 'Kemitraan_Bidang';

        // Disaring lewat KOLOM `bidang_kode` pada pendaftarannya. Baris lama
        // yang tidak menyebut bidang (KKN, atau pendaftaran sebelum migrasi 031)
        // tidak muncul di sini - dan memang seharusnya begitu: ia bukan
        // tanggung jawab bidang mana pun.
        $this->db->from('kkn_magang_pendaftaran')
            ->join('usr_users', 'usr_users.id = kkn_magang_pendaftaran.user_id', 'left')
            ->where('kkn_magang_pendaftaran.jenis', 'magang')
            ->where('kkn_magang_pendaftaran.bidang_kode', $this->my_bidang_kode);

        if ($table['q'] !== '') {
            $this->db->group_start()
                ->like('usr_users.name', $table['q'])->or_like('usr_users.email', $table['q'])
                ->or_like('kkn_magang_pendaftaran.instansi_asal', $table['q'])
                ->or_like('kkn_magang_pendaftaran.divisi_atau_tema', $table['q'])
                ->group_end();
        }
        $table += $this->paginate_state($this->db->count_all_results('', FALSE));

        $data['rows'] = $this->db->select('kkn_magang_pendaftaran.*, usr_users.name AS nama_mahasiswa,
                usr_users.email AS email_mahasiswa')
            ->order_by($table['sort'], $table['dir'])
            ->limit($table['per_page'], $table['offset'])
            ->get()->result();
        $data['table'] = $data['pager'] = $table;

        $this->render_scoped_admin('admin/kemitraan_bidang/index', $data);
    }

    /**
     * Sajikan dokumen pendukung ke peninjau bidang.
     *
     * Guard-nya BUKAN sekadar "saya admin_bidang": barisnya harus benar-benar
     * milik bidang saya. Tanpa pemeriksaan itu, mengganti angka di URL berarti
     * membaca surat pengantar milik bidang lain - dan ini dokumen kependudukan.
     */
    public function lihat_dokumen($id = NULL, $berkas = 'surat')
    {
        if ( ! is_numeric($id)) { show_404(); }

        $kolom = ['surat' => 'file_surat_pengantar', 'proposal' => 'file_proposal'][$berkas] ?? NULL;
        if ($kolom === NULL) { show_404(); }

        $row = $this->baris_bidang_saya($id);
        if ( ! $row || empty($row->$kolom)) { show_404(); }

        $ext  = strtolower(pathinfo($row->$kolom, PATHINFO_EXTENSION));
        $mime = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'][$ext] ?? 'application/octet-stream';
        $this->serve_private_file('kemitraan', (int) $row->id, $row->$kolom, $mime);
    }

    public function proses($id = NULL)
    {
        if ($this->input->method(TRUE) !== 'POST' || ! is_numeric($id)) { show_404(); }

        $row = $this->baris_bidang_saya($id);
        if ( ! $row) { show_404(); }

        $status = $this->input->post('status', TRUE);
        if ( ! in_array($status, ['Diterima', 'Ditolak'], TRUE)) {
            $this->session->set_flashdata('error', 'Status tidak valid.');
            redirect('Kemitraan_Bidang');
            return;
        }

        // Hanya yang SUDAH diteruskan sekretariat. Kalau bidang boleh memutuskan
        // surat yang belum melewati meja pertama, tahap satu berhenti berarti
        // apa pun - dan diagram alurnya jadi hiasan.
        if ($row->status !== 'Ditinjau Bidang') {
            $this->session->set_flashdata('error', 'Pendaftaran ini tidak sedang menunggu tinjauan bidang (status: ' . html_escape($row->status) . ').');
            redirect('Kemitraan_Bidang');
            return;
        }

        // Jejaknya ditulis ke kolom TERSENDIRI, bukan menimpa reviewed_by -
        // pertanyaan "siapa yang meloloskan ini ke tahap dua" justru yang paling
        // sering ditanyakan ketika ada yang keliru.
        $this->db->where('id', (int) $row->id)->update('kkn_magang_pendaftaran', [
            'status'             => $status,
            'catatan_bidang'     => trim((string) $this->input->post('catatan_admin', TRUE)),
            'reviewed_by_bidang' => $this->get_user_id(),
            'reviewed_at_bidang' => date('Y-m-d H:i:s'),
        ]);
        // Aksi sama dengan Admin_Kemitraan::proses() supaya Admin_Audit melihat
        // seluruh rantai keputusan satu pendaftaran (UAT admin bidang AB5).
        $this->catat_audit('kemitraan_keputusan', 'Keputusan bidang ' . strtoupper($row->jenis) . ' ' . $row->instansi_asal . ': ' . $row->status . ' -> ' . $status,
            'kkn_magang_pendaftaran', (string) $row->id, [
                'status_lama' => $row->status, 'status_baru' => $status, 'bidang' => $this->my_bidang_kode,
                'catatan_baru' => trim((string) $this->input->post('catatan_admin', TRUE)),
            ]);

        $this->session->set_flashdata('success', 'Keputusan bidang tersimpan.');
        redirect('Kemitraan_Bidang');
    }

    /** Ambil satu pendaftaran, hanya kalau bidang tujuannya adalah bidang saya. */
    private function baris_bidang_saya($id)
    {
        // Dicocokkan lewat KOLOM `bidang_kode`, bukan join nama divisi. Versi
        // sebelumnya bersandar pada `divisi.nama = divisi_atau_tema` - satu ganti
        // nama dan seluruh pendaftaran putus dari bidangnya, termasuk dari guard
        // ini, yang berarti bidang kehilangan akses ke berkasnya sendiri.
        return $this->db->get_where('kkn_magang_pendaftaran', [
            'id'          => (int) $id,
            'bidang_kode' => $this->my_bidang_kode,
        ])->row();
    }

    /**
     * Akun Universitas untuk admin bidang - UAT 2026 sheet "universitas": "akun dibuatkan
     * admin bidang, akun diberikan kepada universitas oleh admin bidang". Sebelumnya hanya
     * superadmin (Admin_Kemitraan::universitas). View dipakai bersama; bedanya tautan
     * Manajemen Pengguna disembunyikan karena itu layar superadmin.
     *
     * Akun universitas tidak terikat bidang, jadi daftarnya tidak disaring bidang_kode.
     * Admin bidang hanya bisa MEMBUAT role 'universitas' - role dipatok di server, bukan
     * dibaca dari formulir, supaya endpoint ini tidak bisa dipakai membuat akun admin.
     */
    public function universitas()
    {
        $data['title'] = 'Akun Universitas';
        $table = $this->table_state(['created_at', 'name', 'email'], 'created_at');
        $data['base_url'] = 'Kemitraan_Bidang/universitas';
        $data['aksi_buat'] = 'Kemitraan_Bidang/buat_universitas';

        $this->db->from('usr_users')->where('role', 'universitas');
        if ($table['q'] !== '') {
            $this->db->group_start()
                ->like('name', $table['q'])->or_like('email', $table['q'])
                ->or_like('username', $table['q'])->group_end();
        }
        $table += $this->paginate_state($this->db->count_all_results('', FALSE));
        $data['rows'] = $this->db->select("usr_users.*, (SELECT COUNT(*) FROM kkn_magang_pendaftaran
                WHERE kkn_magang_pendaftaran.user_id = usr_users.id
                  AND kkn_magang_pendaftaran.jenis = 'kkn') AS jumlah_kkn", FALSE)
            ->order_by($table['sort'], $table['dir'])
            ->limit($table['per_page'], $table['offset'])
            ->get()->result();
        $data['table'] = $data['pager'] = $table;
        $this->render_scoped_admin('admin/kemitraan/universitas', $data);
    }

    public function buat_universitas()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }
        $kembali = 'Kemitraan_Bidang/universitas';

        $this->load->library('form_validation');
        $this->form_validation->set_rules('name', 'Nama', 'required|trim|max_length[150]');
        // Pesan email ganda disamakan dengan cabang 1062 di bawah (yang kini hanya terjangkau
        // lewat balapan dua kiriman). Nomor HP dan kekuatan sandi divalidasi di server, bukan
        // hanya maxlength HTML (temuan UAT universitas U1/U2, 28 Sep 2026).
        $this->form_validation->set_rules('email', 'Email', 'required|valid_email|max_length[100]|is_unique[usr_users.email]',
            ['is_unique' => 'Akun belum dibuat: email tersebut sudah terdaftar.']);
        $this->form_validation->set_rules('phone', 'Nomor HP', 'trim|max_length[20]|nomor_hp');
        $this->form_validation->set_rules('password', 'Password', 'required|sandi_kuat');
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', strip_tags(validation_errors()));
            redirect($kembali);
            return;
        }

        $this->load->model('auth_model');
        $payload = [
            'name'              => $this->input->post('name', TRUE),
            'email'             => $this->input->post('email', TRUE),
            'password'          => password_hash($this->input->post('password'), PASSWORD_BCRYPT),
            'role'              => 'universitas',
            'status'            => 'active',
            'profile_completed' => 1,
            'email_verified_at' => date('Y-m-d H:i:s'),
            'created_at'        => date('Y-m-d H:i:s'),
        // Sandi awal diketahui admin, jadi wajib diganti di login pertama (keputusan 29 Sep 2026).
        ] + $this->auth_model->password_awal_fields();
        $telp = trim((string) $this->input->post('phone', TRUE));
        if ($telp !== '') { $payload['phone'] = $telp; }

        if ( ! $this->db->insert('usr_users', $payload)) {
            $galat = $this->db->error();
            $this->session->set_flashdata('error', (int) ($galat['code'] ?? 0) === 1062
                ? 'Akun belum dibuat: email tersebut sudah terdaftar.'
                : 'Akun belum dibuat. Periksa isian lalu coba lagi.');
            redirect($kembali);
            return;
        }
        $id = (string) $this->db->insert_id();
        $this->catat_audit('universitas_dibuat',
            'Admin bidang ' . $this->my_bidang_kode . ' membuat akun universitas ' . $payload['email'],
            'usr_users', $id, ['role' => 'universitas', 'bidang_pembuat' => $this->my_bidang_kode]);

        $this->session->set_flashdata('success', 'Akun universitas berhasil dibuat. Serahkan email dan sandinya kepada universitas; sandi itu wajib diganti saat pertama masuk.');
        redirect($kembali);
    }

    // =====================================================================
    // KELOLA AKUN UNIVERSITAS - keputusan pemilik produk 29 Sep 2026: admin bidang menyunting,
    // mereset sandi, dan menonaktifkan akun universitas (sebelumnya hanya superadmin lewat
    // Admin_Users). Batasnya ada di akun_universitas(): hanya role 'universitas'.
    // =====================================================================

    /**
     * Akun sasaran dari POST id, HANYA kalau role-nya 'universitas'. Akun peran lain (termasuk
     * id yang disisipkan ke formulir) dijawab 404 persis seperti akun yang tidak ada, supaya
     * endpoint ini tidak bisa dipakai menyentuh atau menebak akun warga, pengembang, atau admin.
     */
    private function akun_universitas()
    {
        if ($this->input->method(TRUE) !== 'POST') { show_404(); }
        $user = $this->db->get_where('usr_users', ['id' => (int) $this->input->post('id'), 'role' => 'universitas'])->row();
        if ( ! $user) { show_404(); }
        return $user;
    }

    /** Sunting nama, email, dan nomor HP. Role tidak pernah dibaca dari formulir. */
    public function ubah_universitas()
    {
        $user = $this->akun_universitas();
        $kembali = 'Kemitraan_Bidang/universitas';

        $this->load->library('form_validation');
        $this->form_validation->set_rules('name', 'Nama', 'required|trim|max_length[150]');
        $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|max_length[100]');
        $this->form_validation->set_rules('phone', 'Nomor HP', 'trim|max_length[20]|nomor_hp');
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', strip_tags(validation_errors()));
            redirect($kembali);
            return;
        }

        $data = [
            'name'  => $this->input->post('name', TRUE),
            'email' => $this->input->post('email', TRUE),
            'phone' => trim((string) $this->input->post('phone', TRUE)) ?: NULL,
        ];
        // is_unique tidak bisa dipakai: email akun ini sendiri akan dianggap ganda.
        $ganda = $this->db->where('email', $data['email'])->where('id !=', (int) $user->id)->count_all_results('usr_users');
        $galat = $ganda > 0 ? 1062 : 0;
        if ( ! $galat && ! $this->db->where('id', (int) $user->id)->update('usr_users', $data)) {
            $galat = (int) ($this->db->error()['code'] ?? 0) ?: -1;
        }
        if ($galat) {
            $this->session->set_flashdata('error', $galat === 1062
                ? 'Perubahan belum disimpan: email tersebut sudah terdaftar.'
                : 'Perubahan belum disimpan. Periksa isian lalu coba lagi.');
            redirect($kembali);
            return;
        }

        $berubah = array_keys(array_filter($data, fn($v, $k) => (string) $v !== (string) $user->$k, ARRAY_FILTER_USE_BOTH));
        $this->catat_audit('universitas_diubah', 'Admin bidang ' . $this->my_bidang_kode . ' menyunting akun universitas ' . $user->email,
            'usr_users', (string) $user->id, ['kolom' => $berubah, 'email_lama' => $user->email, 'email_baru' => $data['email']]);
        $this->session->set_flashdata('success', 'Data akun ' . $data['email'] . ' diperbarui.');
        redirect($kembali);
    }

    /** Reset sandi: aturan kekuatan sama dengan sandi buatan admin, dan wajib diganti saat masuk. */
    public function sandi_universitas()
    {
        $user = $this->akun_universitas();
        $kembali = 'Kemitraan_Bidang/universitas';

        $this->load->library('form_validation');
        $this->form_validation->set_rules('password', 'Password', 'required|sandi_kuat');
        if ($this->form_validation->run() === FALSE) {
            $this->session->set_flashdata('error', strip_tags(validation_errors()));
            redirect($kembali);
            return;
        }

        // Pola Admin_Users::reset_sandi: kunci gagal dibuka dan sesi yang sedang berjalan dicabut.
        $this->load->model('auth_model');
        $this->db->where('id', (int) $user->id)->update('usr_users', [
            'password' => password_hash($this->input->post('password'), PASSWORD_BCRYPT),
            'login_attempts' => 0, 'locked_until' => NULL,
            'active_session_hash' => NULL, 'active_session_id_hash' => NULL, 'active_session_at' => NULL,
        ] + $this->auth_model->password_awal_fields());
        // Sandinya tidak ikut dicatat.
        $this->catat_audit('universitas_sandi_direset', 'Admin bidang ' . $this->my_bidang_kode . ' mereset sandi akun universitas ' . $user->email,
            'usr_users', (string) $user->id);
        $this->session->set_flashdata('success', 'Sandi ' . $user->email . ' diganti. Sampaikan lewat jalur pribadi; universitas wajib menggantinya saat masuk.');
        redirect($kembali);
    }

    /** Nonaktifkan atau aktifkan kembali. Menonaktifkan sekaligus mengakhiri sesi yang berjalan. */
    public function status_universitas()
    {
        $user = $this->akun_universitas();
        $ke = $this->input->post('status', TRUE) === 'nonaktif' ? 'nonaktif' : 'active';

        $data = ['status' => $ke];
        if ($ke === 'nonaktif') {
            $data += ['active_session_hash' => NULL, 'active_session_id_hash' => NULL, 'active_session_at' => NULL];
        }
        $this->db->where('id', (int) $user->id)->update('usr_users', $data);
        $this->catat_audit($ke === 'nonaktif' ? 'universitas_dinonaktifkan' : 'universitas_diaktifkan',
            'Admin bidang ' . $this->my_bidang_kode . ($ke === 'nonaktif' ? ' menonaktifkan' : ' mengaktifkan') . ' akun universitas ' . $user->email,
            'usr_users', (string) $user->id, ['dari' => $user->status, 'ke' => $ke]);
        $this->session->set_flashdata('success', $ke === 'nonaktif'
            ? 'Akun ' . $user->email . ' dinonaktifkan dan sesinya diakhiri.'
            : 'Akun ' . $user->email . ' diaktifkan kembali.');
        redirect('Kemitraan_Bidang/universitas');
    }
}
