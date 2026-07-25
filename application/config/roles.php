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
| dikembangkan lebih lanjut, dibiarkan apa adanya.
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
