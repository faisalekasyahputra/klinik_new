<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Grup Aturan Validasi
|--------------------------------------------------------------------------
|
| Mekanisme bawaan CI3: `$this->form_validation->run('nama_grup')` membaca
| grup dari berkas ini. Dipakai supaya satu aturan tidak ditulis dua kali di
| dua controller - aturan kembar adalah aturan yang cepat atau lambat
| berselisih, dan yang longgar selalu yang menang.
|
*/

/**
 * Pendaftaran KKN/Magang.
 *
 * Dipakai `KemitraanPortal::simpan()` (mahasiswa mendaftar) DAN
 * `Admin_Kemitraan::simpan_ubah()` (superadmin menyunting baris yang sudah
 * ada). Keduanya menulis ke kolom yang sama, jadi keduanya harus tunduk pada
 * batasan yang sama - admin boleh melampaui KUOTA, tapi tidak boleh menyimpan
 * semester 99 atau NIK berisi tanda baca.
 *
 * Kolom-kolom ini NULL di skema demi baris lama yang tidak pernah ditanyai
 * (lihat migrasi 20260701000025), jadi kewajiban isinya memang harus hidup di
 * sini - bukan di DB.
 */
$config['kemitraan_pendaftaran'] = [
    ['field' => 'nim',              'label' => 'NIM',              'rules' => 'required|trim|alpha_numeric_spaces|max_length[30]'],
    ['field' => 'tempat_lahir',     'label' => 'Tempat Lahir',     'rules' => 'required|trim|max_length[100]'],
    ['field' => 'tanggal_lahir',    'label' => 'Tanggal Lahir',    'rules' => 'required|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
    ['field' => 'semester',         'label' => 'Semester',         'rules' => 'required|integer|greater_than[0]|less_than[15]'],
    ['field' => 'jurusan',          'label' => 'Jurusan',          'rules' => 'required|trim|max_length[150]'],
    ['field' => 'instansi_asal',    'label' => 'Instansi Asal',    'rules' => 'required|trim|max_length[150]'],
    ['field' => 'no_hp',            'label' => 'Nomor HP',         'rules' => 'required|numeric|min_length[10]|max_length[15]'],
    ['field' => 'divisi_atau_tema', 'label' => 'Divisi/Tema',      'rules' => 'required|trim|max_length[150]'],
    ['field' => 'periode_mulai',    'label' => 'Periode Mulai',    'rules' => 'required|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
    ['field' => 'periode_selesai',  'label' => 'Periode Selesai',  'rules' => 'required|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
];
