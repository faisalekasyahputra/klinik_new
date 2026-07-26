<?php
defined('BASEPATH') || exit('No direct script access allowed');

class User_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    public function check_google_user($data) {
        // Cek apakah user dengan email tersebut sudah ada
        $this->db->where('email', $data['email']);
        $query = $this->db->get('usr_users');

        if ($query->num_rows() > 0) {
            // Jika user ada, update google_id dan avatar (jika sebelumnya login manual)
            $this->db->where('email', $data['email']);
            $this->db->update('usr_users', array(
                'google_id' => $data['google_id'],
                'avatar'    => $data['avatar']
            ));
            return [$query->row_array(),'1'];
        } else {
            // Jika user belum terdaftar, buat akun baru otomatis
            $this->db->insert('usr_users', $data);
            $insert_id = $this->db->insert_id();
            
            $this->db->where('id', $insert_id);
            $new_user = $this->db->get('usr_users');
            return [$new_user->row_array(),'0'];
        }
    }

    public function update_user($user_id, $data) {
        $this->db->trans_start();
        
        $this->db->where('id', $user_id);
        $this->db->update('usr_users', $data);

        // Fetch updated user to get the correct display name (username fallback to name)
        $user = $this->db->get_where('usr_users', ['id' => $user_id])->row_array();
        $display_name = !empty($user['username']) ? $user['username'] : $user['name'];

        // Sync with forum tables
        $this->db->where('user_id', $user_id);
        $this->db->update('forum_diskusi', ['nama_user' => $display_name]);

        $this->db->where('user_id', $user_id);
        $this->db->update('forum_komentar', ['nama_komentator' => $display_name]);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    /**
     * Hapus file fisik yang jadi yatim akibat FK CASCADE saat baris DB dihapus
     * di bawah (srp2_registrations->srp2_documents, kkn_magang_pendaftaran).
     * WAJIB dipanggil SEBELUM baris DB dihapus — begitu CASCADE jalan, tidak
     * ada lagi cara menemukan nama file yang harus dihapus dari disk.
     * Lihat application/migrations/20260701000012_add_submission_owner_fk.php.
     */
    private function _cleanup_owned_files($user_id) {
        // Lokasi akar dari helper — ikut PRIVATE_UPLOADS_PATH di .env kalau diisi.
        // Jangan susun path sendiri di sini; pernah terjadi path di sini tertinggal
        // di lokasi publik lama setelah penyimpanan dipindah, sehingga berkasnya
        // tidak pernah benar-benar terhapus.
        $this->load->helper('private_upload');

        // --- Dokumen SRP2 (private_uploads/srp2/{registration_id}/) ---
        $registration_ids = array_column(
            $this->db->select('id')->get_where('srp2_registrations', ['user_id' => $user_id])->result_array(),
            'id'
        );
        if (!empty($registration_ids)) {
            $documents = $this->db->select('registration_id, stored_name')
                ->where_in('registration_id', $registration_ids)->get('srp2_documents')->result();
            foreach ($documents as $doc) {
                $this->_unlink_private($doc->registration_id ? private_uploads_dir('srp2', $doc->registration_id) : '', $doc->stored_name);
            }
        }

        // --- Surat pengantar KKN/Magang (private_uploads/kemitraan/{id}/) ---
        $kkn = $this->db->select('id, file_surat_pengantar')
            ->where('user_id', $user_id)->where('file_surat_pengantar IS NOT NULL', NULL, FALSE)
            ->get('kkn_magang_pendaftaran')->result();
        foreach ($kkn as $row) {
            $this->_unlink_private(private_uploads_dir('kemitraan', $row->id), $row->file_surat_pengantar);
        }

        // --- Dokumen onboarding: KTP/SIUP/KTM (private_uploads/onboarding/{user_id}/) ---
        // usr_documents ikut terhapus lewat FK CASCADE, tapi FK tidak bisa
        // menghapus file di disk — jadi harus dibersihkan di sini.
        $onboarding = $this->db->select('file_name')
            ->get_where('usr_documents', ['user_id' => $user_id])->result();
        foreach ($onboarding as $row) {
            $this->_unlink_private(private_uploads_dir('onboarding', $user_id), $row->file_name);
        }
    }

    /**
     * Hapus satu berkas di dalam direktori privat. basename() dipakai supaya
     * nilai dari DB yang memuat path tidak bisa menghapus file di luar direktori
     * yang dimaksud.
     */
    private function _unlink_private($dir, $file_name) {
        if ($dir === '' || empty($file_name)) { return; }
        $path = $dir . basename((string) $file_name);
        if (is_file($path)) { unlink($path); }
    }

    public function delete_user_account($user_id) {
        // Di luar transaksi DB dengan sengaja — unlink() tidak bisa di-rollback,
        // jadi lebih aman dijalankan sebelum trans_start() daripada di dalamnya.
        $this->_cleanup_owned_files($user_id);

        $this->db->trans_start();

        // Anonymize forum comments
        $this->db->where('user_id', $user_id);
        $this->db->update('forum_komentar', [
            'user_id' => NULL,
            'nama_komentator' => 'Akun Dihapus',
            'role' => 'Warga'
        ]);

        // Anonymize forum discussions
        $this->db->where('user_id', $user_id);
        $this->db->update('forum_diskusi', [
            'user_id' => NULL,
            'nama_user' => 'Akun Dihapus'
        ]);

        // Delete user's likes
        $this->db->where('user_id', $user_id);
        $this->db->delete('forum_likes');

        // Finally, delete the user account
        $this->db->where('id', $user_id);
        $this->db->delete('usr_users');

        $this->db->trans_complete();

        return $this->db->trans_status();
    }
}
