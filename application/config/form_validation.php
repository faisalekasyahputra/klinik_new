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
    /* nim/tempat_lahir/tanggal_lahir/semester TIDAK LAGI "required" di sini
       sejak 21 Agt 2026 (permintaan user: "untuk kkn yang bisa mendaftar
       adalah universitas") - keempatnya data pribadi SATU mahasiswa, dan
       pendaftaran KKN sekarang murni data kampus + surat-surat, bukan
       mewakili satu mahasiswa tertentu. Aturan format (max_length/regex/
       rentang) tetap berlaku KALAU diisi - CI3 melewati rule non-required
       untuk field kosong, jadi ini otomatis tetap wajib untuk MAGANG lewat
       pemeriksaan manual di KemitraanPortal::simpan() (pola sama dengan
       surat pengantar yang sudah lebih dulu wajib-untuk-magang-saja di
       sana), bukan di sini - satu tempat yang tahu "wajib untuk jenis
       apa", sama seperti alasan §17 melarang pembatas laju kedua. */
    ['field' => 'nim',              'label' => 'NIM',              'rules' => 'trim|alpha_numeric_spaces|max_length[30]'],
    ['field' => 'tempat_lahir',     'label' => 'Tempat Lahir',     'rules' => 'trim|max_length[100]'],
    ['field' => 'tanggal_lahir',    'label' => 'Tanggal Lahir',    'rules' => 'regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
    ['field' => 'semester',         'label' => 'Semester',         'rules' => 'integer|greater_than[0]|less_than[15]'],
    // Label validasi generik dengan sengaja - kolom ini bermakna ganda
    // seperti `divisi_atau_tema` (Jurusan untuk magang, Penanggung Jawab
    // untuk KKN), lihat daftar.php.
    ['field' => 'jurusan',          'label' => 'Jurusan/Penanggung Jawab', 'rules' => 'required|trim|max_length[150]'],
    ['field' => 'instansi_asal',    'label' => 'Instansi Asal',    'rules' => 'required|trim|max_length[150]'],
    ['field' => 'no_hp',            'label' => 'Nomor HP',         'rules' => 'required|numeric|min_length[10]|max_length[15]'],
    /* divisi_atau_tema/periode_mulai/periode_selesai TIDAK LAGI "required"
       di sini sejak 21 Agt 2026, dengan alasan SAMA seperti NIM dkk. di
       atas - KKN sekarang tidak lagi menanyakan tema kegiatan maupun
       periode sama sekali. Untuk MAGANG, periode_mulai/periode_selesai
       ditegakkan manual di KemitraanPortal::simpan()/simpan_ubah() dan
       Admin_Kemitraan::simpan_ubah() (pola sama dengan NIM dkk.);
       divisi_atau_tema sendiri sudah otomatis wajib untuk magang lewat
       periksa_slot() - kode bidang kosong tidak pernah cocok dengan
       bidang_by_kode(), jadi pemeriksaan manual kedua di sini akan
       cuma menduplikasi pesan yang sudah jelas dari sana. */
    ['field' => 'divisi_atau_tema', 'label' => 'Divisi/Tema',      'rules' => 'trim|max_length[150]'],
    ['field' => 'periode_mulai',    'label' => 'Periode Mulai',    'rules' => 'regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
    ['field' => 'periode_selesai',  'label' => 'Periode Selesai',  'rules' => 'regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
];

/**
 * Tambah KKN lewat dashboard universitas (KemitraanPortal::kkn_tambah(),
 * permintaan user 21 Agt 2026). Formulirnya HANYA periode + keterangan +
 * dua berkas - data pribadi mahasiswa dan identitas universitas TIDAK ada
 * di sini sama sekali (diambil dari akun / roster Excel terpisah), jadi
 * grup ini sengaja tidak dipakai bersama `kemitraan_pendaftaran` yang
 * masih melayani Magang.
 */
$config['kkn_tambah'] = [
    ['field' => 'periode_mulai',   'label' => 'Periode Mulai',   'rules' => 'required|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
    ['field' => 'periode_selesai', 'label' => 'Periode Selesai', 'rules' => 'required|regex_match[/^\d{4}-\d{2}-\d{2}$/]'],
    ['field' => 'keterangan',      'label' => 'Keterangan',      'rules' => 'required|trim|max_length[150]'],
];
