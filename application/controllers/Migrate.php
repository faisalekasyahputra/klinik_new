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
}
