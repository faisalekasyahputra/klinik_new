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
 * Avatar inisial sebagai data URI SVG — tanpa permintaan jaringan.
 *
 * Menggantikan `https://ui-avatars.com/api/?name=...`, yang mengirim NAMA ASLI
 * pengguna ke server pihak ketiga pada SETIAP pemuatan halaman, untuk setiap
 * peran yang login — bersama IP dan referer-nya. Di portal dinas, itu berarti
 * nama warga, pengembang, mahasiswa, dan admin mengalir ke luar terus-menerus
 * untuk sesuatu yang hasilnya hanya dua huruf di atas kotak berwarna.
 *
 * Sekalian menghapus satu titik gagal: kalau ui-avatars tidak bisa dijangkau,
 * avatar di seluruh portal berubah jadi ikon gambar rusak.
 *
 * @param string $nama  Nama yang diambil inisialnya
 * @param string $bg    Warna latar (default kuning-hijau merek)
 * @param string $fg    Warna huruf
 */
if ( ! function_exists('avatar_inisial')) {
    function avatar_inisial($nama, $bg = '#d6fb00', $fg = '#0a1a1f') {
        $nama = trim((string) $nama);
        if ($nama === '') { $nama = 'Pengguna'; }

        // Dua inisial dari dua kata pertama; satu huruf kalau namanya satu kata.
        $kata = preg_split('/\s+/', $nama, -1, PREG_SPLIT_NO_EMPTY);
        $inisial = mb_strtoupper(mb_substr($kata[0], 0, 1));
        if (count($kata) > 1) { $inisial .= mb_strtoupper(mb_substr($kata[count($kata) - 1], 0, 1)); }

        // `text-anchor` + `dominant-baseline` dipakai supaya tidak perlu
        // menghitung posisi teks sendiri untuk setiap panjang inisial.
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" width="80" height="80">'
             . '<rect width="80" height="80" fill="' . $bg . '"/>'
             . '<text x="50%" y="50%" text-anchor="middle" dominant-baseline="central" '
             . 'font-family="system-ui,-apple-system,Segoe UI,Roboto,sans-serif" '
             . 'font-size="34" font-weight="700" fill="' . $fg . '">'
             . htmlspecialchars($inisial, ENT_QUOTES, 'UTF-8') . '</text></svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
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


