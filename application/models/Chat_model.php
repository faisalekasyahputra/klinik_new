<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Chat_model extends CI_Model {

    // Dapatkan room berdasarkan token session browser, buat baru jika belum ada
    public function get_or_create_room($token_sesi) {
        $query = $this->db->get_where('chat_ruang', ['token_sesi' => $token_sesi], 1);
        
        if ($query->num_rows() > 0) {
            return $query->row();
        }

        $data = ['token_sesi' => $token_sesi, 'status' => 'bot'];
        $this->db->insert('chat_ruang', $data);
        $insert_id = $this->db->insert_id();

        return (object) ['id' => $insert_id, 'token_sesi' => $token_sesi, 'status' => 'bot'];
    }

    // Simpan pesan masuk/keluar
    public function save_message($room_id, $sender, $message) {
        $data = [
            'ruang_id' => $room_id,
            'pengirim' => $sender,
            'pesan' => $message
        ];
        return $this->db->insert('chat_pesan', $data);
    }

    // Perbarui status room (misal dari 'bot' ke 'admin')
    public function update_room_status($room_id, $status) {
        $this->db->where('id', $room_id);
        return $this->db->update('chat_ruang', ['status' => $status]);
    }
}