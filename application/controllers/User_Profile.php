<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Diarsipkan - halaman profil khusus superadmin ini dilebur ke
 * Pengaturan::profil() (route `akun/profil`) yang sudah dipakai semua role
 * lain. Dua halaman "Profil Saya" dengan logika update nyaris identik,
 * dipisah hanya karena gate role (lihat ANCHOR_DASHBOARD_TERPADU.md B9).
 * Fitur ganti password yang dulu hanya ada di sini ikut dipindahkan, dengan
 * aturan kekuatan password yang sama seperti Auth (dulu di sini tanpa
 * validasi apa pun). Viewnya: application/views/admin/profile/archive/.
 *
 * Redirect dipertahankan supaya bookmark/tautan lama tidak jadi dead-end,
 * pola yang sama dengan Pengembang::daftar()/formulir().
 */
class User_Profile extends Admin_Controller {

    public function index() { redirect('akun/profil'); }

    public function update() { redirect('akun/profil'); }
}
