<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    // get_all_housing_queue() dihapus — dulu mengambil s.d. 1000 baris sekaligus
    // untuk dikirim ke browser sebagai JSON (difilter di klien). Digantikan
    // MY_Controller::antrean_table_data() yang cari/filter/urut/paginasi
    // semuanya server-side. Jangan hidupkan lagi pola ambil-semua-lalu-filter.

    /**
     * Jalur superadmin (tanpa scope wilayah) — sengaja TIDAK pakai WHERE
     * kabupaten_id, karena role admin memang berwenang lintas kabupaten.
     * Bandingkan Admin_Kabkota::update_status() yang WHERE-nya wajib ganda.
     * $reviewer diisi dari sesi oleh controller, bukan dari request.
     */
    public function update_queue_status($id, $status, $catatan, $reviewer = NULL) {
        $data = array(
            'status_antrean' => $status,
            'catatan_admin' => $catatan,
            'reviewed_by' => $reviewer,
            'reviewed_at' => date('Y-m-d H:i:s')
        );
        $this->db->where('id', $id);
        return $this->db->update('sf_housing_queue', $data);
    }

    // count_pending_queue()/count_pending_srp2() dihapus — digantikan mekanisme
    // tunggal MY_Controller::count_pending_modul() yang membaca 'table' +
    // 'pending_where' dari application/config/dashboard_modules.php, dipakai
    // badge sidebar sekaligus kartu overview. Jangan tambah method counter
    // per-domain lagi di sini; deklarasikan di registry.
}
