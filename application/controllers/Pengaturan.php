<?php
defined('BASEPATH') || exit('No direct script access allowed');

class Pengaturan extends MY_Controller {

    public function __construct() {
        parent::__construct();
        if (!$this->is_logged_in()) {
            redirect('Auth/login');
        }
        $this->load->model('User_model');
        $this->load->model('Auth_model');
    }

    /**
     * Item utama: satu list gabungan status SEMUA jenis pengajuan milik user
     * (antrean perumahan, aduan, SRP2, KKN/Magang) — bukan lagi kartu terpisah
     * per jenis, supaya warga/pengembang/mahasiswa punya satu tempat pantau status.
     */
    public function index() {
        $user_id = $this->get_user_id();
        $role = $this->session->userdata('role');

        $items = [];

        foreach ($this->db->select('id, ticket_code, status_antrean, created_at')
            ->where('user_id', (int) $user_id)->order_by('created_at', 'DESC')
            ->get('sf_housing_queue')->result() as $r) {
            $status_map = ['pending' => ['Menunggu Verifikasi', 'pending'], 'approved' => ['Disetujui', 'ok'], 'rejected' => ['Ditolak', 'reject']];
            $status = $status_map[$r->status_antrean] ?? ['Sedang Diverifikasi', 'pending'];
            $items[] = [
                'jenis' => 'Antrean Perumahan', 'icon' => 'ph-ticket',
                'judul' => !empty($r->ticket_code) ? $r->ticket_code : 'Tiket belum tersedia',
                'status_label' => $status[0], 'status_kelas' => $status[1],
                'created_at' => $r->created_at, 'aksi_url' => null,
            ];
        }

        // catatan_admin ikut diambil supaya pelapor tahu ALASAN status berubah,
        // bukan cuma statusnya — pola yang sudah dipakai SRP2 tapi dulu belum
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
            $sp2 = $this->db->where('user_id', $user_id)->order_by('id', 'DESC')->get('srp2_registrations')->row();
            if ($sp2) {
                // Label status + label tombol aksi. Tombolnya diberi nama sesuai
                // apa yang BENAR-BENAR terjadi saat diklik, bukan "Kelola" untuk
                // semua keadaan — "Kelola" pada pengajuan yang sudah final
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
                    'judul' => $sp2->nama_perusahaan,
                    'status_label' => $status[0], 'status_kelas' => $status[1],
                    // updated_at, bukan created_at — draft bisa dibuat jauh sebelum
                    // benar-benar diisi/dikirim, tanggal aktivitas terakhir lebih relevan.
                    'created_at' => $sp2->updated_at ?: $sp2->created_at,
                    // SEMUA status mengarah ke wizard — itu tempat dokumen pengajuan
                    // ini berada, dan wizard sendiri yang menentukan mode: Draft/Ditolak
                    // bisa diedit, Pending/Diterima tampil read-only.
                    //
                    // JANGAN arahkan ke akun/profil. Pernah dilakukan untuk status
                    // Diterima dengan alasan "dokumen sudah final, yang bisa dikelola
                    // tinggal data perusahaan" — dan itu keliru: tombol pada baris
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
                $status_map = ['Diajukan' => ['Diajukan', 'pending'], 'Diterima' => ['Diterima', 'ok'], 'Ditolak' => ['Ditolak', 'reject']];
                $status = $status_map[$p->status] ?? [$p->status, 'pending'];
                $items[] = [
                    'jenis' => strtoupper($p->jenis) . ' — ' . $p->instansi_asal, 'icon' => 'ph-graduation-cap',
                    'judul' => $p->divisi_atau_tema,
                    'status_label' => $status[0], 'status_kelas' => $status[1],
                    'created_at' => $p->created_at, 'aksi_url' => null,
                ];
            }
        }

        usort($items, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));

        $datacontent = [
            'title' => 'Status Pengajuan',
            'items' => $items,
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

        if ($this->session->userdata('role') === 'pengembang') {
            $datacontent['pengajuan_sp2'] = $this->db->where('user_id', $user_id)
                ->order_by('id', 'DESC')->get('srp2_registrations')->row();
        }

        $this->render_user_dashboard('pages/pengaturan/profil', $datacontent);
    }

    public function update_pengembang_profile() {
        $user_id = $this->get_user_id();

        if ($this->session->userdata('role') !== 'pengembang') {
            show_404();
            return;
        }

        $pengajuan = $this->db->get_where('srp2_registrations', ['user_id' => $user_id])->row();
        if (!$pengajuan) {
            $this->session->set_flashdata('error', 'Data pengajuan sertifikasi tidak ditemukan.');
            redirect('akun/profil');
            return;
        }

        // Simpan teks apa adanya (escape dilakukan sekali saat render lewat htmlspecialchars()
        // di profil.php, bukan di sini — supaya tidak double-encode).
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

        if (!in_array($data['asosiasi'], ['rei', 'himperra', 'apersi', 'pi', 'lainnya'], TRUE)) {
            $this->session->set_flashdata('error', 'Asosiasi tidak valid.');
            redirect('akun/profil');
            return;
        }

        // user_id selalu dari sesi, bukan dari input, supaya tidak bisa mengedit data pengembang lain (anti-IDOR)
        $this->db->where('user_id', $user_id);
        $this->db->update('srp2_registrations', $data);

        $this->session->set_flashdata('success', 'Data pengembang berhasil diperbarui!');
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

        // Ganti password opsional — dipindahkan ke sini saat User_Profile
        // (halaman profil khusus superadmin) dilebur ke halaman ini, supaya
        // fiturnya tidak hilang. Aturan kekuatan password disamakan dengan
        // Auth::do_register()/save_onboarding(), bukan versi longgar lama
        // yang menerima password apa pun.
        $password = $this->input->post('password');
        if (!empty($password)) {
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
