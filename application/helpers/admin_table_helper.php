<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helper tabel admin - membangun URL yang MEMPERTAHANKAN parameter query lain
 * (filter, pencarian, urutan) saat salah satunya diubah.
 *
 * Ini menutup bug nyata: pagination lama membangun '?page=N' saja, sehingga
 * filter bidang di Admin_Aduan hilang begitu pindah halaman.
 */

if ( ! function_exists('admin_table_url')) {
    /**
     * @param string $base_url path CI halaman (mis. 'Admin_Aduan')
     * @param array  $ubah     parameter yang diganti/ditambah; nilai NULL menghapus
     */
    function admin_table_url($base_url, $ubah = []) {
        $params = $_GET;
        foreach ($ubah as $k => $v) {
            if ($v === NULL || $v === '') { unset($params[$k]); } else { $params[$k] = $v; }
        }
        // Ganti filter/urutan selalu balik ke halaman 1 - kalau tidak, user bisa
        // terdampar di halaman 7 dari hasil yang cuma punya 2 halaman.
        if ($ubah && ! array_key_exists('page', $ubah)) { unset($params['page']); }

        $qs = http_build_query($params);
        return base_url($base_url) . ($qs ? '?' . $qs : '');
    }
}

if ( ! function_exists('admin_sort_header')) {
    /**
     * Header kolom yang bisa diklik untuk mengurut. Kolom yang tidak ada di
     * whitelist $table['sortable'] dirender sebagai teks biasa (bukan link),
     * jadi tidak ada cara memaksa ORDER BY lewat URL.
     */
    function admin_sort_header($label, $kolom, $table, $base_url) {
        if (empty($table['sortable']) || ! in_array($kolom, $table['sortable'], TRUE)) {
            return html_escape($label);
        }

        $aktif = ($table['sort'] ?? NULL) === $kolom;
        $dir_berikut = ($aktif && strtoupper($table['dir']) === 'ASC') ? 'desc' : 'asc';
        $ikon = ! $aktif
            ? 'ph-caret-up-down opacity-30'
            : (strtoupper($table['dir']) === 'ASC' ? 'ph-sort-ascending text-brand-primary' : 'ph-sort-descending text-brand-primary');

        return '<a href="' . admin_table_url($base_url, ['sort' => $kolom, 'dir' => $dir_berikut])
            . '" class="inline-flex items-center gap-1 hover:text-gray-900 dark:hover:text-white transition-colors">'
            . html_escape($label) . '<i class="ph ' . $ikon . '"></i></a>';
    }
}
