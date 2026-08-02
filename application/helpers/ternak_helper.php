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

/**
 * Tanggal berbahasa Indonesia.
 *
 * `date('j F Y')` mengeluarkan "2 August 2026" — nama bulan Inggris di tengah
 * layar berbahasa Indonesia. Ketahuan 2 Agt 2026 waktu halaman pendaftaran
 * mahasiswa dibuka di browser; tidak akan pernah tertangkap harness HTTP,
 * karena responsnya 200 dan isinya "benar" bagi mesin.
 *
 * `strftime()` sengaja tidak dipakai: deprecated sejak PHP 8.1 dan bergantung
 * pada locale sistem yang di Windows tidak menyediakan id_ID.
 *
 * @param string $tanggal  Apa pun yang dimengerti strtotime()
 * @param bool   $pendek   TRUE untuk "Agt", FALSE untuk "Agustus"
 */
if ( ! function_exists('tgl_id')) {
    function tgl_id($tanggal, $pendek = FALSE) {
        if (empty($tanggal)) { return '-'; }
        $ts = strtotime($tanggal);
        if ($ts === FALSE) { return '-'; }

        $panjang = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $singkat = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
                    'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];
        $bulan = ($pendek ? $singkat : $panjang)[(int) date('n', $ts) - 1];

        return date('j', $ts) . ' ' . $bulan . ' ' . date('Y', $ts);
    }
}


