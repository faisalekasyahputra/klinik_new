<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kualifikasi posisi magang (kkn_magang_posisi.keterangan) sebagai daftar.
 *
 * Satu aturan untuk tiga tempat: tampilan publik (_posisi.php), penyimpanan admin
 * (Admin_Magang_Posisi::simpan) dan form admin saat membuka isian lama.
 *
 * Tersimpan satu butir per baris (form daftar admin sejak 22 Okt 2026). Isian lama yang
 * diketik dinas di kotak satu baris memakai koma ("Bisa menggambar, Bisa berimajinasi"),
 * jadi bila tidak ada baris baru maupun titik koma, koma dipakai sebagai pemisah.
 */
const KUALIFIKASI_MAKS_BUTIR = 15;
const KUALIFIKASI_MAKS_KARAKTER = 150;

if ( ! function_exists('daftar_kualifikasi')) {
    function daftar_kualifikasi($teks)
    {
        $teks = (string) $teks;
        if (trim($teks) === '') { return []; }
        $pemisah = preg_match('/[\r\n;]/', $teks) ? '/\r\n|\r|\n|;/' : '/,/';
        return array_values(array_filter(array_map('trim', preg_split($pemisah, $teks)), 'strlen'));
    }
}
