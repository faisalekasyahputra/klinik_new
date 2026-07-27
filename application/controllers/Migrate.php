<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Runner migrasi skema DB. Hanya bisa diakses dari CLI atau localhost —
 * dipakai untuk menyamakan skema lokal & staging lewat application/migrations/,
 * menggantikan kebiasaan lama jalankan file .sql di docs/engineering/ manual satu-satu.
 */
class Migrate extends CI_Controller {

    public function __construct()
    {
        parent::__construct();

        if ( ! $this->input->is_cli_request() && ! in_array($this->input->ip_address(), array('127.0.0.1', '::1')))
        {
            show_404();
        }

        $this->load->library('migration');
    }

    public function index()
    {
        $result = $this->migration->latest();

        if ($result === FALSE)
        {
            echo 'Migrasi gagal: '.$this->migration->error_string()."\n";
            return;
        }

        echo "Migrasi sukses, versi skema sekarang: {$result}\n";
    }

    /**
     * Diagnostik BACA SAJA — jalankan SEBELUM index() di lingkungan mana pun
     * yang keadaan migrasinya belum pasti (production khususnya). CI
     * migration->version() menandai migrasi sebagai berhasil TANPA memeriksa
     * nilai balik query di dalamnya (lihat system/libraries/Migration.php
     * baris ~302-309), jadi kalau tabel migrations ternyata tidak ada sama
     * sekali, latest() akan mencoba ulang migrasi 1..N dari nol dan bisa
     * menandai sukses walau CREATE TABLE-nya gagal senyap karena db_debug
     * mati di production. Method ini tidak mengubah apa pun — dipertahankan
     * sebagai alat baku, bukan sekali pakai, karena T6 dan role berikutnya
     * akan menghadapi masalah yang sama.
     */
    public function status()
    {
        $tables = $this->db->list_tables();
        echo 'Total tabel: '.count($tables)."\n";
        echo 'migrations: '.(in_array('migrations', $tables) ? 'ADA' : 'TIDAK ADA')."\n";

        if (in_array('migrations', $tables)) {
            $row = $this->db->order_by('version', 'DESC')->limit(1)->get('migrations')->row();
            echo 'Versi migrasi tercatat: '.($row->version ?? 'NONE')."\n";
        }

        foreach (['sys_rate_limits', 'srp2_registrations', 'srp2_certified_developers'] as $t) {
            echo $t.': '.(in_array($t, $tables) ? 'ADA' : 'TIDAK ADA')."\n";
        }

        if (in_array('srp2_registrations', $tables)) {
            echo 'srp2_registrations.certified_developer_id: '.
                ($this->db->field_exists('certified_developer_id', 'srp2_registrations') ? 'ADA' : 'TIDAK ADA')."\n";
        }
    }
}
