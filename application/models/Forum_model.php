<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Forum_model extends CI_Model {

    /**
     * Ambil semua diskusi (filter soft-delete, search, kategori).
     */
    public function get_all_diskusi($search = '', $kategori = '') {
        $this->db->select('forum_diskusi.*, COUNT(forum_komentar.id_komentar) as total_balasan');
        $this->db->from('forum_diskusi');
        $this->db->join('forum_komentar', 'forum_diskusi.id_diskusi = forum_komentar.id_diskusi AND forum_komentar.is_deleted = 0', 'left');
        $this->db->where('forum_diskusi.is_deleted', 0);
        
        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('forum_diskusi.judul_topik', $search);
            $this->db->or_like('forum_diskusi.isi_diskusi', $search);
            $this->db->group_end();
        }
        
        if (!empty($kategori)) {
            $this->db->where('forum_diskusi.kategori', $kategori);
        }
        
        $this->db->group_by('forum_diskusi.id_diskusi');
        $this->db->order_by('forum_diskusi.created_at', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_diskusi_by_id($id) {
        $this->db->where('is_deleted', 0);
        return $this->db->get_where('forum_diskusi', ['id_diskusi' => $id])->row_array();
    }

    public function get_komentar_by_diskusi($id) {
        $this->db->where('is_deleted', 0);
        $this->db->order_by('created_at', 'ASC');
        $flat = $this->db->get_where('forum_komentar', ['id_diskusi' => $id])->result_array();
        
        // Build lookup map for parent names
        $map = [];
        foreach ($flat as &$k) {
            $map[$k['id_komentar']] = $k;
            $k['reply_to_name'] = null;
        }
        unset($k);
        
        // Attach parent name
        foreach ($flat as &$k) {
            if (!empty($k['reply_to']) && isset($map[$k['reply_to']])) {
                $k['reply_to_name'] = $map[$k['reply_to']]['nama_komentator'];
                $k['reply_to_snippet'] = mb_substr($map[$k['reply_to']]['isi_komentar'], 0, 80, 'UTF-8');
            }
        }
        unset($k);
        
        return $flat;
    }

    public function insert_diskusi($data) {
        return $this->db->insert('forum_diskusi', $data);
    }

    public function insert_komentar($data) {
        return $this->db->insert('forum_komentar', $data);
    }

    /** Soft-delete diskusi */
    public function soft_delete_diskusi($id) {
        $this->db->where('id_diskusi', $id);
        return $this->db->update('forum_diskusi', ['is_deleted' => 1]);
    }

    /** Soft-delete komentar */
    public function soft_delete_komentar($id) {
        $this->db->where('id_komentar', $id);
        return $this->db->update('forum_komentar', ['is_deleted' => 1]);
    }

    /** Update status diskusi (open/resolved/closed) */
    public function update_status($id, $status) {
        $valid = ['open', 'resolved', 'closed'];
        if (!in_array($status, $valid)) return false;
        $this->db->where('id_diskusi', $id);
        return $this->db->update('forum_diskusi', ['status' => $status]);
    }

    /** Increment report count */
    public function report_diskusi($id) {
        $this->db->where('id_diskusi', $id);
        $this->db->set('report_count', 'report_count + 1', FALSE);
        return $this->db->update('forum_diskusi');
    }

    public function report_komentar($id) {
        $this->db->where('id_komentar', $id);
        $this->db->set('report_count', 'report_count + 1', FALSE);
        return $this->db->update('forum_komentar');
    }

    /** Auto-hide konten yang dilaporkan >= threshold kali */
    public function auto_hide_reported($threshold = 5) {
        $this->db->where('report_count >=', $threshold);
        $this->db->where('is_deleted', 0);
        $this->db->update('forum_diskusi', ['is_deleted' => 1]);

        $this->db->where('report_count >=', $threshold);
        $this->db->where('is_deleted', 0);
        $this->db->update('forum_komentar', ['is_deleted' => 1]);
    }

    // =========================================================
    // LIKE SYSTEM
    // =========================================================

    /**
     * Toggle like (like jika belum, unlike jika sudah).
     * @return array ['action' => 'liked'|'unliked', 'count' => int]
     */
    public function toggle_like($user_id, $target_type, $target_id) {
        $existing = $this->db->get_where('forum_likes', [
            'user_id'     => $user_id,
            'target_type' => $target_type,
            'target_id'   => $target_id
        ])->row();

        $table = ($target_type === 'diskusi') ? 'forum_diskusi' : 'forum_komentar';
        $id_col = ($target_type === 'diskusi') ? 'id_diskusi' : 'id_komentar';

        if ($existing) {
            // Unlike
            $this->db->delete('forum_likes', ['id' => $existing->id]);
            $this->db->where($id_col, $target_id);
            $this->db->set('like_count', 'GREATEST(like_count - 1, 0)', FALSE);
            $this->db->update($table);
            $action = 'unliked';
        } else {
            // Like
            $this->db->insert('forum_likes', [
                'user_id'     => $user_id,
                'target_type' => $target_type,
                'target_id'   => $target_id
            ]);
            $this->db->where($id_col, $target_id);
            $this->db->set('like_count', 'like_count + 1', FALSE);
            $this->db->update($table);
            $action = 'liked';
        }

        // Get updated count
        $row = $this->db->select('like_count')->get_where($table, [$id_col => $target_id])->row();
        return ['action' => $action, 'count' => $row ? (int)$row->like_count : 0];
    }

    /**
     * Cek apakah user sudah like target tertentu.
     */
    public function has_liked($user_id, $target_type, $target_id) {
        return $this->db->get_where('forum_likes', [
            'user_id'     => $user_id,
            'target_type' => $target_type,
            'target_id'   => $target_id
        ])->num_rows() > 0;
    }

    /**
     * Ambil semua like status user untuk satu diskusi (topik + semua komentarnya).
     * Return: ['diskusi_ID' => true, 'komentar_ID' => true, ...]
     */
    public function get_user_likes($user_id, $diskusi_id) {
        $likes = [];

        // Cek like pada diskusi
        if ($this->has_liked($user_id, 'diskusi', $diskusi_id)) {
            $likes['diskusi_' . $diskusi_id] = true;
        }

        // Cek like pada semua komentar di diskusi ini
        $komentar_ids = $this->db->select('id_komentar')
                                 ->get_where('forum_komentar', ['id_diskusi' => $diskusi_id, 'is_deleted' => 0])
                                 ->result();
        
        if (!empty($komentar_ids)) {
            $ids = array_column($komentar_ids, 'id_komentar');
            $liked = $this->db->where('user_id', $user_id)
                              ->where('target_type', 'komentar')
                              ->where_in('target_id', $ids)
                              ->get('forum_likes')
                              ->result();
            foreach ($liked as $l) {
                $likes['komentar_' . $l->target_id] = true;
            }
        }

        return $likes;
    }

    // =========================================================
    // VIEW COUNT
    // =========================================================

    /** Increment view count (1 per page load) */
    public function increment_view($diskusi_id) {
        $this->db->where('id_diskusi', $diskusi_id);
        $this->db->set('view_count', 'view_count + 1', FALSE);
        return $this->db->update('forum_diskusi');
    }
}
