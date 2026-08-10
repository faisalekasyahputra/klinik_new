<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Setting_model extends CI_Model {

    protected $table = 'sys_settings';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get all settings as an associative array (key => value)
     */
    public function get_all()
    {
        $query = $this->db->get($this->table);

        // `get()` mengembalikan FALSE saat koneksi/query gagal - dan di
        // production `db_debug` mati, jadi tidak ada exception yang menahannya.
        // Tanpa penjagaan ini `FALSE->result_array()` jadi fatal, dan karena
        // pemanggilnya ada di `views/layouts/main.php` (layout portal yang
        // dipakai hampir semua halaman), satu gangguan DB sesaat memutihkan
        // SELURUH situs alih-alih membuatnya sekadar kehilangan pengaturan.
        // Terjadi sungguhan 30 Jul 2026: batas 500 koneksi/jam terlampaui, dan
        // log production penuh "Call to a member function result_array() on
        // false" di baris ini.
        //
        // Mengembalikan array kosong aman: setiap pembaca sudah memakai
        // `?? default` (mis. `$ftSettings['footer_copyright'] ?? '...'`).
        if ( ! $query) {
            return [];
        }

        $settings = [];
        foreach ($query->result_array() as $row) {
            $settings[$row['key_name']] = $row['key_value'];
        }
        return $settings;
    }

    /**
     * Get a specific setting by key
     */
    public function get_by_key($key)
    {
        $this->db->where('key_name', $key);
        $query = $this->db->get($this->table);
        $row = $query->row_array();
        
        if ($row) {
            return $row['key_value'];
        }
        return null;
    }

    /**
     * Update multiple settings from associative array
     */
    /**
     * @return bool TRUE hanya bila seluruh tulisan benar-benar berhasil.
     *
     * Dulu method ini SELALU `return true`, apa pun yang terjadi pada query di
     * dalamnya - sehingga pemanggilnya tidak punya cara membedakan berhasil dari
     * gagal, dan layar admin selalu mengatakan "berhasil diperbarui". Transaksi
     * dipakai supaya kegagalan di tengah tidak meninggalkan setelan separuh
     * tersimpan; polanya menyalin User_model::update_profile().
     */
    public function update_batch_settings($data)
    {
        $this->db->trans_start();

        foreach ($data as $key => $value) {
            $this->db->where('key_name', $key);
            $this->db->update($this->table, ['key_value' => $value]);
            
            // If key doesn't exist, insert it
            if ($this->db->affected_rows() == 0) {
                // Check if it really exists to avoid inserting if update just didn't change the value
                $this->db->where('key_name', $key);
                $query = $this->db->get($this->table);
                if ($query->num_rows() == 0) {
                    $this->db->insert($this->table, [
                        'key_name' => $key,
                        'key_value' => $value,
                        'type' => 'text'
                    ]);
                }
            }
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }
}
