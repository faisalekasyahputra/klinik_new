<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Forum_model extends CI_Model {

    /**
     * Ambil diskusi (filter soft-delete, search, kategori), OPSIONAL dibatasi
     * ke satu pemilik.
     *
     * `$user_id`: NULL = semua diskusi (dipakai admin, lihat Umum::forum()).
     * Diisi angka = HANYA milik user itu - ini yang menegakkan privasi
     * konsultasi (permintaan user 15 Agt 2026: "hanya bisa dilihat oleh
     * admin"). Sebelum ini method-nya selalu mengembalikan SEMUA diskusi ke
     * SIAPA PUN yang memanggil `Umum::forum()`, termasuk tamu anonim -
     * konsultasi satu warga bisa dibaca warga lain begitu saja.
     */
    public function get_all_diskusi($search = '', $kategori = '', $user_id = NULL) {
        $this->db->select('forum_diskusi.*, COUNT(forum_komentar.id) as total_balasan');
        $this->db->from('forum_diskusi');
        $this->db->join('forum_komentar', 'forum_diskusi.id = forum_komentar.diskusi_id AND forum_komentar.dihapus = 0', 'left');
        $this->db->where('forum_diskusi.dihapus', 0);

        if ($user_id !== NULL) {
            $this->db->where('forum_diskusi.user_id', (int) $user_id);
        }

        if (!empty($search)) {
            $this->db->group_start();
            $this->db->like('forum_diskusi.judul_topik', $search);
            $this->db->or_like('forum_diskusi.isi_diskusi', $search);
            $this->db->group_end();
        }

        if (!empty($kategori)) {
            $this->db->where('forum_diskusi.kategori', $kategori);
        }

        $this->db->group_by('forum_diskusi.id');
        $this->db->order_by('forum_diskusi.created_at', 'DESC');
        return $this->db->get()->result_array();
    }

    public function get_diskusi_by_id($id) {
        $this->db->where('dihapus', 0);
        return $this->db->get_where('forum_diskusi', ['id' => $id])->row_array();
    }

    /**
     * ID diskusi induk dari satu komentar - dipakai menegakkan kepemilikan
     * saat aksi (like/lapor) menyasar KOMENTAR, bukan topiknya langsung.
     * NULL kalau komentarnya tidak ada/sudah dihapus.
     */
    public function get_diskusi_id_dari_komentar($id_komentar) {
        $row = $this->db->select('diskusi_id')->where('dihapus', 0)
            ->get_where('forum_komentar', ['id' => (int) $id_komentar])->row();
        return $row ? (int) $row->diskusi_id : NULL;
    }

    public function get_komentar_by_diskusi($id) {
        $this->db->where('dihapus', 0);
        $this->db->order_by('created_at', 'ASC');
        $flat = $this->db->get_where('forum_komentar', ['diskusi_id' => $id])->result_array();
        
        // Build lookup map for parent names
        $map = [];
        foreach ($flat as &$k) {
            // Balasan petugas tampil atas nama institusi, bukan username staf - juga untuk baris lama
            // dan sesudah User_model::update_user menyinkronkan ulang nama_komentator.
            if (($k['peran'] ?? '') === 'Petugas Disperakim') { $k['nama_komentator'] = 'Petugas Disperakim'; }
            $map[$k['id']] = $k;
            $k['reply_to_name'] = null;
        }
        unset($k);
        
        // Attach parent name
        foreach ($flat as &$k) {
            if (!empty($k['balasan_untuk_id']) && isset($map[$k['balasan_untuk_id']])) {
                $k['reply_to_name'] = $map[$k['balasan_untuk_id']]['nama_komentator'];
                $k['reply_to_snippet'] = mb_substr($map[$k['balasan_untuk_id']]['isi_komentar'], 0, 80, 'UTF-8');
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
        $this->db->where('id', $id);
        return $this->db->update('forum_diskusi', ['dihapus' => 1]);
    }

    /** Soft-delete komentar */
    public function soft_delete_komentar($id) {
        $this->db->where('id', $id);
        return $this->db->update('forum_komentar', ['dihapus' => 1]);
    }

    /** Update status diskusi (open/resolved/closed) */
    public function update_status($id, $status) {
        $valid = ['open', 'resolved', 'closed'];
        if (!in_array($status, $valid)) return false;
        $this->db->where('id', $id);
        return $this->db->update('forum_diskusi', ['status' => $status]);
    }

    /** Increment report count */
    public function report_diskusi($id) {
        $this->db->where('id', $id);
        $this->db->set('jumlah_laporan', 'jumlah_laporan + 1', FALSE);
        return $this->db->update('forum_diskusi');
    }

    /**
     * B3 - laporan komentar dicatat per PELAPOR, bukan sekadar penghitung.
     *
     * Dulu method ini hanya menaikkan `jumlah_laporan`, sehingga lima klik dari
     * satu orang bernilai sama dengan lima orang berbeda. Kini setiap laporan
     * masuk ledger `forum_laporan_komentar` ber-UNIQUE (komentar_id, user_id),
     * lalu `jumlah_laporan` DIHITUNG ULANG dari jumlah pelapor unik - bukan
     * ditambah. Dengan begitu angka di kolom itu selalu berarti "berapa orang",
     * dan laporan berulang dari orang yang sama tidak bergerak sama sekali.
     *
     * `dihapus` SENGAJA tidak disentuh: U2 ledger-only. Auto-hide menunggu
     * keputusan #10 dan, bila dipilih, roadmap moderasi tersendiri yang juga
     * menyediakan antrean + restore. Lima akun tidak boleh menjadi sensor
     * permanen tanpa jalan pulang.
     *
     * @return array ['success' => bool, 'baru' => bool, 'jumlah' => int]
     */
    public function report_komentar($id, $user_id) {
        $id = (int) $id;
        $user_id = (int) $user_id;

        $this->db->trans_begin();

        // Kunci baris komentar induknya lebih dulu: dua request paralel dari
        // pelapor berbeda tidak boleh sama-sama membaca hitungan lama lalu
        // menuliskan hasil yang sama.
        $komentar = $this->db->query(
            'SELECT id FROM forum_komentar WHERE id = ? FOR UPDATE', [$id]
        )->row_array();
        if ( ! $komentar) {
            $this->db->trans_rollback();
            return ['success' => FALSE, 'baru' => FALSE, 'jumlah' => 0];
        }

        // UNIQUE yang menegakkan "satu laporan per orang"; INSERT kedua dari
        // orang yang sama ditolak DB, bukan dicegah dengan SELECT-lalu-INSERT
        // yang bisa kalah balapan.
        $baru = (bool) $this->db->query(
            'INSERT IGNORE INTO forum_laporan_komentar (komentar_id, user_id) VALUES (?, ?)',
            [$id, $user_id]
        );
        $baru = $baru && $this->db->affected_rows() === 1;

        $jumlah = (int) $this->db->where('komentar_id', $id)
            ->count_all_results('forum_laporan_komentar');

        $this->db->where('id', $id);
        $this->db->update('forum_komentar', ['jumlah_laporan' => $jumlah]);

        if ( ! $this->db->trans_status()) {
            $this->db->trans_rollback();
            return ['success' => FALSE, 'baru' => FALSE, 'jumlah' => 0];
        }
        $this->db->trans_commit();

        return ['success' => TRUE, 'baru' => $baru, 'jumlah' => $jumlah];
    }

    /** Auto-hide konten yang dilaporkan >= threshold kali */
    public function auto_hide_reported($threshold = 5) {
        $this->db->where('jumlah_laporan >=', $threshold);
        $this->db->where('dihapus', 0);
        $this->db->update('forum_diskusi', ['dihapus' => 1]);

        $this->db->where('jumlah_laporan >=', $threshold);
        $this->db->where('dihapus', 0);
        $this->db->update('forum_komentar', ['dihapus' => 1]);
    }

    // =========================================================
    // LIKE SYSTEM
    // =========================================================

    /**
     * Toggle like (like jika belum, unlike jika sudah).
     * @return array ['action' => 'liked'|'unliked', 'count' => int]
     */
    public function toggle_like($user_id, $jenis_target, $target_id) {
        $existing = $this->db->get_where('forum_suka', [
            'user_id'     => $user_id,
            'jenis_target' => $jenis_target,
            'target_id'   => $target_id
        ])->row();

        $table = ($jenis_target === 'diskusi') ? 'forum_diskusi' : 'forum_komentar';
        $id_col = 'id'; // kedua tabel ber-PK id sejak migrasi 072

        if ($existing) {
            // Unlike
            $this->db->delete('forum_suka', ['id' => $existing->id]);
            $this->db->where($id_col, $target_id);
            $this->db->set('jumlah_suka', 'GREATEST(jumlah_suka - 1, 0)', FALSE);
            $this->db->update($table);
            $action = 'unliked';
        } else {
            // Like
            $this->db->insert('forum_suka', [
                'user_id'     => $user_id,
                'jenis_target' => $jenis_target,
                'target_id'   => $target_id
            ]);
            $this->db->where($id_col, $target_id);
            $this->db->set('jumlah_suka', 'jumlah_suka + 1', FALSE);
            $this->db->update($table);
            $action = 'liked';
        }

        // Get updated count
        $row = $this->db->select('jumlah_suka')->get_where($table, [$id_col => $target_id])->row();
        return ['action' => $action, 'count' => $row ? (int)$row->jumlah_suka : 0];
    }

    /**
     * Cek apakah user sudah like target tertentu.
     */
    public function has_liked($user_id, $jenis_target, $target_id) {
        return $this->db->get_where('forum_suka', [
            'user_id'     => $user_id,
            'jenis_target' => $jenis_target,
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
        $komentar_ids = $this->db->select('id')
                                 ->get_where('forum_komentar', ['diskusi_id' => $diskusi_id, 'dihapus' => 0])
                                 ->result();
        
        if (!empty($komentar_ids)) {
            $ids = array_column($komentar_ids, 'id');
            $liked = $this->db->where('user_id', $user_id)
                              ->where('jenis_target', 'komentar')
                              ->where_in('target_id', $ids)
                              ->get('forum_suka')
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
        $this->db->where('id', $diskusi_id);
        $this->db->set('jumlah_dilihat', 'jumlah_dilihat + 1', FALSE);
        return $this->db->update('forum_diskusi');
    }
}
