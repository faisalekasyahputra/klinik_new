<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Janji temu konsultasi — SATU sumber untuk aturan perpindahan keadaannya.
 *
 * Ada di model karena dua sisi memerlukannya: warga (`Umum`) dan petugas
 * (`Admin_Konsultasi`). Whitelist transisi yang ditulis di dua controller akan
 * berselisih — itu bukan kekhawatiran teoretis di repo ini, daftar bidang
 * pernah hidup di empat tempat dan keempatnya sudah menyimpang.
 *
 * ⚠️ Kalau kamu menambahkan pintu tulis ketiga ke tabel ini, LEWATKAN ke sini
 * juga. AGENTS.md §18 mencatat pola "aksesor pajangan": fungsi yang diberi
 * docblock "satu-satunya sumber" lalu dipakai dua dari dua puluh pemanggil,
 * sementara sisanya query sendiri — dan bug yang sama muncul lagi di tiap
 * permukaan baru sebagai bug baru.
 */
class Janji_temu_model extends CI_Model {

    /**
     * status asal => [status tujuan => siapa yang boleh].
     *
     * Keadaan akhir (`ditolak`, `dibatalkan`, `selesai`) sengaja TIDAK punya
     * entri: absennya kunci itulah yang mengunci mereka, bukan sebuah daftar
     * terpisah yang bisa lupa diperbarui.
     *
     * Tidak ada transisi ke dirinya sendiri, dan itu penting untuk cara
     * `transisi()` mendeteksi kegagalan — lihat catatan di sana.
     */
    const ALUR = [
        'diajukan' => [
            'ditawarkan' => 'admin',
            'ditolak'    => 'admin',
            'dibatalkan' => 'pemilik',
        ],
        'ditawarkan' => [
            'disetujui'  => 'pemilik',
            // Minta jadwal lain: kembali ke antrean petugas, bukan keadaan baru.
            'diajukan'   => 'pemilik',
            'ditolak'    => 'admin',
            'dibatalkan' => 'pemilik',
        ],
        'disetujui' => [
            'selesai'    => 'admin',
            'dibatalkan' => 'pemilik',
        ],
    ];

    /** Keadaan yang masih memakan perhatian petugas maupun warga. */
    const HIDUP = ['diajukan', 'ditawarkan', 'disetujui'];

    public function boleh($dari, $ke, $pelaku)
    {
        return (self::ALUR[$dari][$ke] ?? NULL) === $pelaku;
    }

    /** Semua nilai status yang pernah sah — untuk chip filter & validasi. */
    public function status_sah()
    {
        $out = array_keys(self::ALUR);
        foreach (self::ALUR as $tujuan) { $out = array_merge($out, array_keys($tujuan)); }
        return array_values(array_unique($out));
    }

    /**
     * Perpindahan keadaan atomik.
     *
     * Status ASAL ikut di WHERE, jadi dua permintaan yang datang bersamaan
     * tidak bisa dua-duanya berhasil: yang kedua tidak menemukan barisnya lagi.
     * Membaca dulu lalu menulis (`SELECT` status, cek di PHP, `UPDATE`) punya
     * jendela di antaranya, dan jendela itu persis yang dipakai tombol yang
     * ditekan dua kali.
     *
     * ⚠️ INI LAPIS KEDUA, DAN TIDAK ADA UJI YANG MERAH KALAU KAMU MENCABUTNYA.
     * Kedua pemanggilnya sudah menolak lebih dulu lewat `boleh()`, jadi
     * mutasi yang menghapus `->where('status', $dari)` lolos seluruh
     * `uji_janji_temu.php` — diverifikasi 4 Agt 2026, bukan dugaan. Yang
     * dijaga baris ini adalah permintaan yang benar-benar BERSAMAAN, dan
     * harness berurutan tidak bisa menghasilkannya. Hijau di sini bukan izin
     * untuk membuangnya.
     *
     * `affected_rows()` DI SINI boleh dipercaya, dan itu tidak berlaku umum:
     * MySQL juga membalas 0 ketika nilai barunya sama persis dengan yang
     * tersimpan (masalah yang sudah pernah salah dilaporkan sebagai "bukan
     * milik Anda" di Admin_Bidang). Di sini `status` PASTI berubah karena
     * `self::ALUR` tidak punya transisi ke dirinya sendiri.
     *
     * @param array $batas syarat tambahan, mis. ['user_id' => 12] (anti-IDOR)
     */
    public function transisi($id, $dari, $ke, array $set = [], array $batas = [])
    {
        $this->db->where('id', (int) $id)->where('status', $dari);
        foreach ($batas as $k => $v) { $this->db->where($k, $v); }

        $this->db->update('forum_janji_temu', $set + [
            'status'     => $ke,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->db->affected_rows() > 0;
    }

    public function ambil($id)
    {
        return $this->db->get_where('forum_janji_temu', ['id' => (int) $id])->row();
    }

    /** Janji temu yang masih berjalan untuk satu topik — nol atau satu. */
    public function hidup_untuk_topik($id_diskusi)
    {
        return $this->db->where('id_diskusi', (int) $id_diskusi)
            ->where_in('status', self::HIDUP)
            ->order_by('id', 'DESC')->limit(1)
            ->get('forum_janji_temu')->row();
    }

    /** Seluruh riwayat satu topik, terbaru dulu. Riwayat penolakan ikut terbaca. */
    public function riwayat_topik($id_diskusi)
    {
        return $this->db->where('id_diskusi', (int) $id_diskusi)
            ->order_by('id', 'DESC')->get('forum_janji_temu')->result();
    }

    public function buat(array $data)
    {
        $this->db->insert('forum_janji_temu', $data + [
            'status'     => 'diajukan',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->db->insert_id();
    }
}
