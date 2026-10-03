<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Aduan_model extends CI_Model {

    /**
     * Judul tetap permintaan peninjauan penghapusan data (Pengaturan::request_service_data_deletion).
     * Baris ini permintaan hak subjek data, bukan aduan publik: di Papan Aduan hanya terlihat oleh
     * pemohonnya dan staf (Umum::papan_aduan). Aduan biasa yang kebetulan berjudul sama ikut
     * tersembunyi; arah salahnya ke privasi, jadi diterima.
     */
    const JUDUL_PENGHAPUSAN_DATA = 'Permintaan Penghapusan Data Layanan';

    /**
     * Daftar bidang dari TABEL - satu-satunya sumber kebenaran.
     *
     * Sebelumnya daftar ini hidup sebagai literal di tiga tempat berbeda
     * (peta label di bawah, whitelist validasi di Umum::simpan_aduan, dan
     * dropdown di formulir aduan), dan ketiganya sudah menyimpang dari struktur
     * dinas: menyebut "pengembang" dan "umum" sebagai bidang. Dikonfirmasi ke
     * dinas 1 Agt 2026 - bidang ada lima, dan keduanya bukan termasuk.
     *
     * Dihafal per-permintaan karena dipanggil beberapa kali dalam satu render.
     */
    public function daftar_bidang() {
        static $cache = NULL;
        if ($cache === NULL) {
            $cache = $this->db->order_by('nama', 'ASC')->get('bidang')->result();
        }
        return $cache;
    }

    /**
     * Nama bidang untuk ditampilkan. Kode yang tidak dikenal dikembalikan APA
     * ADANYA, bukan dialihkan ke bidang lain - aduan lama yang menunjuk bidang
     * yang sudah tidak ada harus terbaca sebagai keanehan, bukan menyamar
     * sebagai aduan milik bidang yang masih hidup.
     */
    public function bidang_label($bidang) {
        foreach ($this->daftar_bidang() as $b) {
            if ($b->kode === $bidang) { return $b->nama; }
        }
        return (string) $bidang;
    }

    public function create($data) {
        $this->db->insert('aduan', $data);
        return $this->db->insert_id();
    }
}
