<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Serah Terima PSU - SATU sumber label status, dipakai admin (Admin_Psu,
 * layar CRUD) DAN publik (Psu::index()) supaya kalimat yang dibaca admin
 * dan warga tidak pernah menyimpang.
 *
 * DITARUH DI HELPER, BUKAN method statis di Admin_Psu - pelajaran langsung:
 * percobaan pertama memanggil `Admin_Psu::label_status()` dari view publik
 * gagal fatal, "Class Admin_Psu not found". CI3 TIDAK auto-load controller
 * lain di luar yang sedang menangani request; saat controller aktifnya
 * `Psu` (publik), berkas Admin_Psu.php tidak pernah ikut ter-require. Helper
 * dimuat eksplisit di kedua sisi (`$this->load->helper('psu')`), sama pola
 * dengan srp2_helper.php.
 *
 * Istilah TIGA TAHAP UMUM, bukan kutipan regulasi - lihat komentar lengkap
 * di migrasi 043. Ganti di SINI SAJA kalau dinas mengirim rumusan resmi.
 */
function psu_daftar_status() {
    return [
        'belum_diserahkan'  => 'Belum Diserahkan',
        'proses_verifikasi' => 'Proses Verifikasi',
        'sudah_diserahkan'  => 'Sudah Diserahkan',
    ];
}

function psu_label_status($kode) {
    return psu_daftar_status()[$kode] ?? $kode;
}
