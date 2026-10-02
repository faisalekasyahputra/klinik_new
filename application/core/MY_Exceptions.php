<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halaman galat dan 404 buatan CodeIgniter (form keamanan poin 13.5).
 *
 * Route yang tidak ditemukan dan galat fatal menghasilkan halaman tanpa melewati controller mana pun, sehingga
 * MY_Controller tidak sempat memasang header keamanan: 404 dari router keluar tanpa nosniff, X-Frame-Options,
 * Referrer-Policy, dan HSTS (diukur di situs live sebelum perubahan ini). Di sini header itu dipasang lebih dulu
 * lewat fungsi bersama yang sama dengan MY_Controller.
 */
class MY_Exceptions extends CI_Exceptions {

    public function show_404($page = '', $log_error = TRUE)
    {
        $this->pasang_header();
        return parent::show_404($page, $log_error);
    }

    public function show_error($heading, $message, $template = 'error_general', $status_code = 500)
    {
        $this->pasang_header();
        return parent::show_error($heading, $message, $template, $status_code);
    }

    public function show_exception($exception)
    {
        $this->pasang_header();
        return parent::show_exception($exception);
    }

    private function pasang_header()
    {
        try {
            if ( ! function_exists('kirim_header_keamanan')) { require_once APPPATH . 'helpers/content_security_helper.php'; }
            kirim_header_keamanan();
        } catch (Throwable $e) { /* halaman galat tidak boleh gagal karena header */ }
    }
}