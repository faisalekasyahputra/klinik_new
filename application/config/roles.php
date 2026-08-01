<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Daftar Role Resmi
|--------------------------------------------------------------------------
|
| Sumber kebenaran tunggal untuk role yang berlaku di aplikasi ini.
| 'admin_kabkota' dan 'admin_bidang' butuh scope tambahan (kabupaten_id /
| bidang_kode di usr_users) dan HANYA bisa dibuat lewat Admin_Users oleh
| superadmin — tidak ada di daftar pendaftaran publik (lihat
| Auth::onboarding(), $valid_roles).
|
| 'vendor' sengaja tidak masuk daftar ini — role lama yang belum
| dikembangkan lebih lanjut. Sejak 1 Agt 2026 ia juga dicabut dari
| Auth::save_onboarding(): cabangnya menulis ke kolom nama_usaha/
| alamat_usaha/jenis_usaha yang tidak ada di usr_users, jadi memilihnya
| menghasilkan error DB, bukan profil. Nol baris berperan vendor.
|
*/
$config['available_roles'] = [
    'admin'         => 'Administrator',
    'warga'         => 'Warga',
    'pengembang'    => 'Pengembang',
    'admin_kabkota' => 'Admin Kabupaten/Kota',
    'mahasiswa'     => 'Mahasiswa',
    'admin_bidang'  => 'Admin Bidang',
];

// Role yang butuh kabupaten_id terisi di usr_users
$config['roles_scoped_kabupaten'] = ['admin_kabkota'];

// Role yang butuh bidang_kode terisi di usr_users
$config['roles_scoped_bidang'] = ['admin_bidang'];
