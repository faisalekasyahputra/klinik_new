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
        $registration_ids = array_column(
            $this->db->select('id')->get_where('srp2_registrations', ['user_id' => $user_id])->result_array(),
            'id'
        );
        if (!empty($registration_ids)) {
            $documents = $this->db->select('registration_id, stored_name')
                ->where_in('registration_id', $registration_ids)->get('srp2_documents')->result();
            foreach ($documents as $doc) {
                $path = dirname(FCPATH) . DIRECTORY_SEPARATOR . 'private_uploads' . DIRECTORY_SEPARATOR
                    . 'srp2' . DIRECTORY_SEPARATOR . $doc->registration_id . DIRECTORY_SEPARATOR . $doc->stored_name;
                if (is_file($path)) { unlink($path); }
            }
        }

        $kkn_files = $this->db->select('file_surat_pengantar')
            ->where('user_id', $user_id)->where('file_surat_pengantar IS NOT NULL', NULL, FALSE)
            ->get('kkn_magang_pendaftaran')->result();
        foreach ($kkn_files as $row) {
            $path = FCPATH . '.assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $row->file_surat_pengantar;
            if (is_file($path)) { unlink($path); }
        }
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
