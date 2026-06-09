<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Menyusun URL absolut untuk aset file atau gambar
 */
if ( ! function_exists('api_image_url')) {
    function api_image_url($path) {
        if (empty($path)) {
            // Bisa return placeholder gambar default jika kosong
            return 'assets/img/default-placeholder.svg';
        }
        
        // Cek jika path sudah berupa URL HTTP utuh
        if (strpos($path, 'http') === 0) {
            return $path;
        }

        $CI =& get_instance();
        $CI->load->config('ternak_api');
        
        // Karena base api url diakhiri dengan /api, kita ambil rootnya
        $api_base = str_replace('/api', '', $CI->config->item('ternak_api_url'));
        
        return rtrim($api_base, '/') . '/' . ltrim($path, '/');
    }
}

/**
 * Format tanggal ISO ke format yang bisa dibaca di Indonesia
 */
if ( ! function_exists('format_tanggal_api')) {
    function format_tanggal_api($iso_date_string) {
        if (empty($iso_date_string)) return '-';
        return date('d M Y, H:i', strtotime($iso_date_string));
    }
}

