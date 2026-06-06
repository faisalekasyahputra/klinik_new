<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Chat_model extends CI_Model {

    // Dapatkan room berdasarkan token session browser, buat baru jika belum ada
    public function get_or_create_room($session_token) {
        $query = $this->db->get_where('chat_rooms', ['session_token' => $session_token], 1);
        
        if ($query->num_rows() > 0) {
            return $query->row();
        }

        $data = ['session_token' => $session_token, 'status' => 'bot'];
        $this->db->insert('chat_rooms', $data);
        $insert_id = $this->db->insert_id();

        return (object) ['id' => $insert_id, 'session_token' => $session_token, 'status' => 'bot'];
    }

    // Simpan pesan masuk/keluar
    public function save_message($room_id, $sender, $message) {
        $data = [
            'chat_room_id' => $room_id,
            'sender' => $sender,
            'message' => $message
        ];
        return $this->db->insert('chat_messages', $data);
    }

    // Perbarui status room (misal dari 'bot' ke 'admin')
    public function update_room_status($room_id, $status) {
        $this->db->where('id', $room_id);
        return $this->db->update('chat_rooms', ['status' => $status]);
    }
}