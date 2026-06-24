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
        $query = $this->db->get('users');

        if ($query->num_rows() > 0) {
            // Jika user ada, update google_id dan avatar (jika sebelumnya login manual)
            $this->db->where('email', $data['email']);
            $this->db->update('users', array(
                'google_id' => $data['google_id'],
                'avatar'    => $data['avatar']
            ));
            return [$query->row_array(),'1'];
        } else {
            // Jika user belum terdaftar, buat akun baru otomatis
            $this->db->insert('users', $data);
            $insert_id = $this->db->insert_id();
            
            $this->db->where('id', $insert_id);
            $new_user = $this->db->get('users');
            return [$new_user->row_array(),'0'];
        }
    }

    public function update_user($user_id, $data) {
        $this->db->trans_start();
        
        $this->db->where('id', $user_id);
        $this->db->update('users', $data);

        // Fetch updated user to get the correct display name (username fallback to name)
        $user = $this->db->get_where('users', ['id' => $user_id])->row_array();
        $display_name = !empty($user['username']) ? $user['username'] : $user['name'];

        // Sync with forum tables
        $this->db->where('user_id', $user_id);
        $this->db->update('diskusi', ['nama_user' => $display_name]);

        $this->db->where('user_id', $user_id);
        $this->db->update('komentar', ['nama_komentator' => $display_name]);

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function delete_user_account($user_id) {
        $this->db->trans_start();

        // Anonymize forum comments
        $this->db->where('user_id', $user_id);
        $this->db->update('komentar', [
            'user_id' => NULL,
            'nama_komentator' => 'Akun Dihapus',
            'role' => 'Warga'
        ]);

        // Anonymize forum discussions
        $this->db->where('user_id', $user_id);
        $this->db->update('diskusi', [
            'user_id' => NULL,
            'nama_user' => 'Akun Dihapus'
        ]);

        // Delete user's likes
        $this->db->where('user_id', $user_id);
        $this->db->delete('forum_likes');

        // Finally, delete the user account
        $this->db->where('id', $user_id);
        $this->db->delete('users');

        $this->db->trans_complete();

        return $this->db->trans_status();
    }
}