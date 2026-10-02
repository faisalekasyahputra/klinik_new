<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Jalur jebakan (honeypot) untuk pemindai otomatis (form keamanan poin 10.4 dan 10.5).
 *
 * Alamat seperti wp-login.php, phpmyadmin, atau xmlrpc.php tidak pernah dipakai aplikasi ini
 * dan tidak pernah diketik pengguna sah, tetapi selalu dicoba pemindai. Daftarnya di
 * config/anti_automation.php (`probe_paths`) dan dipetakan ke sini di config/routes.php.
 * Setiap kunjungan menjadi peringatan keamanan (ditekan duplikatnya per IP) dan dijawab
 * 404 biasa: pemindai tidak diberi tahu bahwa ia terdeteksi. Meniru teknik 404-probing tanpa
 * mengubah penanganan galat inti: yang dihitung hanya jalur yang jelas-jelas tidak sah,
 * bukan setiap 404 (yang juga dihasilkan pengguna sah dari tautan basi).
 */
class Jebakan extends MY_Controller {

    public function index()
    {
        // Hanya karakter aman dan panjang terbatas: path ini dikendalikan si penyerang.
        $path = substr(preg_replace('/[^A-Za-z0-9._\/\-]/', '?', (string) $this->uri->uri_string()), 0, 80);
        $this->load->library('Security_alert');
        $this->security_alert->raise(
            'jalur_jebakan', 'sedang',
            'Jalur yang hanya dicari pemindai otomatis diakses: /' . $path,
            ['path' => $path],
            'probe'
        );
        $this->output->set_status_header(404)->set_content_type('text/plain', 'utf-8')->set_output('Halaman tidak ditemukan.');
    }
}
