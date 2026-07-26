<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Pengembang extends MY_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->helper(['url', 'download']);
        $this->load->model('Buka_peta');
        $this->load->model('auth_model');
        $this->load->library('session');
        date_default_timezone_set('Asia/Jakarta');
    }

    public function sertifikasi() {
        $data['judul'] = '';
        $table = $this->db->table_exists('srp2_certified_developers') ? 'srp2_certified_developers' : 'srp2_registrations';
        $status = $table === 'srp2_certified_developers' ? ['status_aktif' => 1] : ['status_verifikasi' => 'Diterima'];
        $data['daftar_pengembang'] = $this->db->select('id, nama_perusahaan')->where($status)->order_by('nama_perusahaan', 'ASC')->get($table)->result();
        $this->render('pages/pengembang/sertifikasi', $data);
    }

    /**
     * Wizard SRP2 satu halaman: syarat -> masuk/daftar -> unggah dokumen -> kirim.
     * Semua langkah tetap di URL ini (state di Alpine), tidak pindah halaman.
     */
    public function syarat() {
        $is_logged = $this->is_logged_in();
        $role = $this->session->userdata('role');
        $is_pengembang = $is_logged && $role === 'pengembang';

        $registration_id = null;
        $uploaded_keys = [];
        $status_verifikasi = null;
        $catatan_admin = null;
        if ($is_pengembang) {
            // Draft dibuat/ditemukan lewat satu pintu di Auth_model — akun yang
            // rolenya di-set manual admin pun tetap dapat drafnya di sini.
            $registration_id = $this->auth_model->ensure_srp2_draft($this->get_user_id());
            $registration = $this->db->get_where('srp2_registrations', ['id' => $registration_id])->row();
            if ($registration) {
                $status_verifikasi = $registration->status_verifikasi;
                $catatan_admin = $registration->catatan_admin;
                $uploaded_keys = $this->db->select('document_key')->where('registration_id', $registration_id)
                    ->get('srp2_documents')->result_array();
                $uploaded_keys = array_column($uploaded_keys, 'document_key');
            }
        }

        $this->render('pages/pengembang/syarat', [
            'judul'            => '',
            'recaptcha_site_key' => getenv('RECAPTCHA_SITE_KEY') ?: '',
            'is_logged'        => $is_logged,
            'is_pengembang'    => $is_pengembang,
            'wrong_role'       => $is_logged && !$is_pengembang,
            'nama_user'        => $this->session->userdata('name') ?: $this->session->userdata('email'),
            'registration_id'  => $registration_id,
            'dokumen'          => $this->dokumen_persyaratan(),
            'uploaded_keys'    => $uploaded_keys,
            'status_verifikasi' => $status_verifikasi,
            'catatan_admin'    => $catatan_admin,
        ]);
    }

    /**
     * Diarsipkan — perannya sekarang diambil alih wizard di Pengembang/syarat
     * (satu halaman: syarat -> masuk/daftar -> unggah dokumen -> kirim).
     * Redirect dipertahankan supaya bookmark/tautan lama tidak jadi dead-end.
     */
    public function daftar() { redirect('Pengembang/syarat'); }

    /**
     * Diarsipkan — form manual 12 field digantikan wizard daftar cepat di
     * Pengembang/syarat. Redirect dipertahankan supaya bookmark/tautan lama
     * tidak jadi dead-end. Lihat archive/formulir_sertifikasi_12field.php.
     */
    public function formulir() { redirect('Pengembang/syarat'); }

    public function simpan() { redirect('Pengembang/syarat'); }

    public function result($id = NULL) {
        if (!$this->akses_pengembang('Pengembang/result/' . (int) $id) || !is_numeric($id)) show_404();
        $data['pendaftar'] = $this->db->get_where('srp2_registrations', ['id' => (int) $id, 'user_id' => $this->get_user_id()])->row();
        if (!$data['pendaftar']) show_404();
        $this->render('pages/pengembang/v_sertifikasi', $data);
    }

    public function profil($id = NULL) {
        if (!is_numeric($id)) show_404();
        if ($this->db->table_exists('srp2_certified_developers')) {
            $data['pengembang'] = $this->db->get_where('srp2_certified_developers', ['id' => (int) $id, 'status_aktif' => 1])->row();
            if ($data['pengembang'] && $this->db->table_exists('srp2_registrations')) {
                $registration = $this->db->get_where('srp2_registrations', ['nama_perusahaan' => $data['pengembang']->nama_perusahaan, 'status_verifikasi' => 'Diterima'])->row();
                if ($registration) foreach (['asosiasi', 'no_keanggotaan', 'alamat_kantor', 'instagram', 'website', 'sosmed_lainnya'] as $field) if (empty($data['pengembang']->$field)) $data['pengembang']->$field = $registration->$field ?? NULL;
            }
        } else $data['pengembang'] = $this->db->get_where('srp2_registrations', ['id' => (int) $id, 'status_verifikasi' => 'Diterima'])->row();
        if (!$data['pengembang']) show_404();
        $this->render('pages/pengembang/profil', $data);
    }

    public function mulai_unggah($document_key = NULL) {
        $document_key = array_key_exists($document_key, $this->dokumen_persyaratan()) ? $document_key : NULL;
        $target = 'Pengembang/mulai_unggah' . ($document_key ? '/' . $document_key : '');
        if (!$this->akses_pengembang($target)) return;
        $registration = $this->db->order_by('id', 'DESC')->get_where('srp2_registrations', ['user_id' => $this->get_user_id()])->row();
        if (!$registration) { $this->session->set_flashdata('error', 'Lengkapi pendaftaran SRP2 terlebih dahulu.'); redirect('Pengembang/syarat'); return; }
        redirect('Pengembang/dokumen/' . $registration->id . ($document_key ? '#dokumen_' . $document_key : ''));
    }

    public function dokumen($id = NULL) {
        if (!is_numeric($id) || !$this->akses_pengembang('Pengembang/dokumen/' . (int) $id)) show_404();
        $data['pendaftar'] = $this->db->get_where('srp2_registrations', ['id' => (int) $id, 'user_id' => $this->get_user_id()])->row();
        if (!$data['pendaftar']) show_404();
        $data['dokumen'] = $this->dokumen_persyaratan();
        $data['uploaded_count'] = $this->db->where('registration_id', (int) $id)->count_all_results('srp2_documents');
        $this->render('pages/pengembang/dokumen', $data);
    }

    public function simpan_dokumen($id = NULL) {
        $is_ajax = $this->input->is_ajax_request();
        if (!is_numeric($id) || !$this->akses_pengembang('Pengembang/dokumen/' . (int) $id) || $this->input->method(TRUE) !== 'POST') { if (!$is_ajax) show_404(); return; }
        // Anti-IDOR: WHERE user_id selalu dari sesi — pengembang lain tidak bisa menulis ke registrasi ini.
        $registration = $this->db->get_where('srp2_registrations', ['id' => (int) $id, 'user_id' => $this->get_user_id()])->row();
        if (!$registration || !$this->db->table_exists('srp2_documents')) {
            if ($is_ajax) { $this->output->set_status_header(404)->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => 'Pengajuan tidak ditemukan.'])); return; }
            show_404();
        }
        // Dokumen dikunci setelah dikirim ke admin — mencegah file diganti diam-diam
        // saat sedang/sudah ditinjau (Diterima pun tidak boleh diubah lagi).
        if (in_array($registration->status_verifikasi, ['Pending', 'Diterima'], TRUE)) {
            $message = 'Dokumen tidak bisa diubah saat status ' . $registration->status_verifikasi . '.';
            if ($is_ajax) { $this->output->set_status_header(409)->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => $message])); return; }
            $this->session->set_flashdata('error', $message); redirect('Pengembang/dokumen/' . $id); return;
        }
        // Pastikan akar private_uploads/ tertutup dari akses HTTP langsung.
        // "Di luar webroot" ternyata tidak selalu benar — tergantung posisi
        // aplikasi terhadap DocumentRoot; lihat catatan di method itu.
        $this->ensure_private_uploads_protected();
        $path = dirname(FCPATH) . DIRECTORY_SEPARATOR . 'private_uploads' . DIRECTORY_SEPARATOR . 'srp2' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR;
        if (!is_dir($path)) mkdir($path, 0700, TRUE);
        $allowed = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png']; $stored = [];
        foreach ($this->dokumen_persyaratan() as $key => $label) {
            if (empty($_FILES[$key]['name'])) continue;
            $file = $_FILES[$key]; $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)); $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 2097152 || !isset($allowed[$ext]) || $mime !== $allowed[$ext]) {
                $message = 'Berkas ' . $label . ' tidak valid. Gunakan PDF/JPG/PNG maksimal 2 MB.';
                if ($is_ajax) { $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'document_key' => $key, 'message' => $message])); return; }
                $this->session->set_flashdata('error', $message); redirect('Pengembang/dokumen/' . $id); return;
            }
            $name = bin2hex(random_bytes(16)) . '.' . $ext;
            if (!move_uploaded_file($file['tmp_name'], $path . $name)) {
                if ($is_ajax) { $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'document_key' => $key, 'message' => 'Berkas gagal disimpan.'])); return; }
                $this->session->set_flashdata('error', 'Berkas gagal disimpan.'); redirect('Pengembang/dokumen/' . $id); return;
            }
            $stored[] = ['registration_id' => (int) $id, 'document_key' => $key, 'original_name' => substr(basename($file['name']), 0, 255), 'stored_name' => $name, 'mime_type' => $mime, 'file_size' => (int) $file['size']];
        }
        foreach ($stored as $row) $this->db->replace('srp2_documents', $row);

        if ($is_ajax) {
            if (empty($stored)) { $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => 'Tidak ada berkas yang dipilih.'])); return; }
            $uploaded_count = $this->db->where('registration_id', (int) $id)->count_all_results('srp2_documents');
            $this->output->set_content_type('application/json')->set_output(json_encode([
                'status' => 'success', 'document_key' => $stored[0]['document_key'], 'uploaded_count' => $uploaded_count,
            ]));
            return;
        }
        $this->session->set_flashdata('success', $stored ? 'Dokumen berhasil diunggah.' : 'Pilih setidaknya satu dokumen untuk diunggah.'); redirect('Pengembang/dokumen/' . $id);
    }

    public function kirim_pengajuan($id = NULL) {
        $is_ajax = $this->input->is_ajax_request();
        if (!is_numeric($id) || !$this->akses_pengembang('Pengembang/dokumen/' . (int) $id) || $this->input->method(TRUE) !== 'POST') { if (!$is_ajax) show_404(); return; }
        $registration = $this->db->get_where('srp2_registrations', ['id' => (int) $id, 'user_id' => $this->get_user_id()])->row();
        if (!$registration) {
            if ($is_ajax) { $this->output->set_status_header(404)->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => 'Pengajuan tidak ditemukan.'])); return; }
            show_404();
        }
        if (in_array($registration->status_verifikasi, ['Pending', 'Diterima'], TRUE)) {
            $message = 'Pengajuan sudah dikirim dan berstatus ' . $registration->status_verifikasi . '.';
            if ($is_ajax) { $this->output->set_status_header(409)->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => $message])); return; }
            $this->session->set_flashdata('error', $message); redirect('akun'); return;
        }
        $required = count($this->dokumen_persyaratan()); $uploaded = $this->db->where('registration_id', (int) $id)->count_all_results('srp2_documents');
        if ($uploaded < $required) {
            $message = 'Lengkapi seluruh ' . $required . ' dokumen sebelum mengirim pengajuan.';
            if ($is_ajax) { $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 'error', 'message' => $message, 'uploaded_count' => $uploaded, 'required' => $required])); return; }
            $this->session->set_flashdata('error', $message); redirect('Pengembang/dokumen/' . $id); return;
        }
        // Anti-IDOR: WHERE user_id selalu dari sesi.
        $this->db->where('id', (int) $id)->where('user_id', $this->get_user_id())->update('srp2_registrations', ['status_verifikasi' => 'Pending']);

        if ($is_ajax) { $this->output->set_content_type('application/json')->set_output(json_encode(['status' => 'success', 'message' => 'Pengajuan SRP2 berhasil dikirim dan sedang menunggu verifikasi.'])); return; }
        $this->session->set_flashdata('success', 'Pengajuan SRP2 berhasil dikirim dan sedang menunggu verifikasi.'); redirect('akun');
    }

    private function akses_pengembang($target) {
        // Request AJAX (dipanggil wizard SRP2 lewat fetch) TIDAK BOLEH di-redirect() —
        // fetch akan diam-diam mengikuti redirect dan menerima HTML halaman lain sebagai
        // "berhasil". Balas status HTTP + JSON supaya wizard tahu harus tampilkan apa.
        if ($this->input->is_ajax_request()) {
            if (!$this->is_logged_in()) {
                $this->output->set_status_header(401)->set_content_type('application/json')
                    ->set_output(json_encode(['status' => 'error', 'code' => 'not_logged_in', 'message' => 'Silakan masuk atau daftar terlebih dahulu.']));
                return FALSE;
            }
            if ($this->session->userdata('role') !== 'pengembang') {
                $this->output->set_status_header(403)->set_content_type('application/json')
                    ->set_output(json_encode(['status' => 'error', 'code' => 'wrong_role', 'message' => 'Layanan SRP2 hanya tersedia untuk akun dengan peran pengembang.']));
                return FALSE;
            }
            return TRUE;
        }

        if (!$this->is_logged_in()) { $this->session->set_userdata('intended_url', $target); redirect('Pengembang/masuk'); return FALSE; }
        if ($this->session->userdata('role') !== 'pengembang') { $this->session->set_flashdata('error', 'Layanan SRP2 hanya tersedia untuk akun dengan peran pengembang.'); redirect('akun'); return FALSE; }
        return TRUE;
    }

    /**
     * Gerbang masuk/daftar khusus alur SRP2 — supaya user pengembang tetap di dalam
     * alur ini (bukan dilempar ke Auth/login umum) saat belum login. Auth/login dan
     * Auth/register utama TIDAK diubah, tetap berfungsi seperti biasa untuk role lain.
     */
    public function masuk() {
        if ($this->is_logged_in()) {
            $intended = $this->session->userdata('intended_url') ?: 'Pengembang/syarat';
            $this->session->unset_userdata('intended_url');
            redirect($intended);
            return;
        }
        $this->render('pages/pengembang/masuk', ['judul' => '', 'recaptcha_site_key' => getenv('RECAPTCHA_SITE_KEY') ?: '']);
    }

    private function dokumen_persyaratan() {
        // Daftar sesungguhnya ada di application/helpers/srp2_helper.php — satu
        // sumber kebenaran, juga dipakai Admin_Srp2::detail() untuk verifikasi.
        $this->load->helper('srp2');
        return srp2_dokumen_persyaratan();
    }
}
