<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Kebijakan unggahan berkas (form keamanan poin 11.1 dan 11.4).
 * Sumber tunggal untuk libraries/Upload_quota.php, libraries/Upload_scanner.php,
 * MY_Controller::store_private_upload() dan tests/upload_security_test.php.
 * Penjelasan dan prosedur: docs/engineering/UNGGAHAN_BERKAS.md.
 */
$config['upload_policy'] = [

    /* 11.1: kuota TOTAL per pengguna, dihitung lintas seluruh domain unggahan privat
       (onboarding, kemitraan, penilaian warga, SRP2, aduan, BNBA). `files` = jumlah berkas
       yang masih ada di disk, `bytes` = ukuran totalnya. Batas ukuran PER BERKAS tetap
       ditentukan tiap titik unggah (bawaan 5 MB; dokumen SRP2 2 MB).
       Kunci = peran sesi; `anon` = pengunjung tanpa akun (aduan publik), diikat ke IP dan
       dihitung dalam jendela `window` detik, karena tanpa akun tidak ada "seumur hidup". */
    'quota' => [
        'warga'          => ['files' => 60,  'bytes' => 150 * 1048576],
        'mahasiswa'      => ['files' => 40,  'bytes' => 100 * 1048576],
        'pengembang'     => ['files' => 60,  'bytes' => 150 * 1048576],
        'admin'          => ['files' => 300, 'bytes' => 500 * 1048576],
        'admin_kabkota'  => ['files' => 300, 'bytes' => 500 * 1048576],
        'admin_bidang'   => ['files' => 300, 'bytes' => 500 * 1048576],
        'anon'           => ['files' => 10,  'bytes' => 30 * 1048576, 'window' => 86400],
        '_default'       => ['files' => 20,  'bytes' => 50 * 1048576],
    ],

    /* Nama direktori buku kuota di dalam akar private_uploads/ (ikut terlindung .htaccess
       akar itu). Pemesanan yang belum disusul berkasnya dianggap batal setelah ttl detik. */
    'ledger_dir'      => '_pemilik',
    'reservation_ttl' => 120,

    /* 11.4: pemindaian isi berkas dari sumber tak tepercaya. */
    'scan' => [
        'max_bytes'         => 16 * 1048576,   // di atas ini tidak dipindai dan ditolak
        'image_max_pixels'  => 60000000,       // bom dekompresi gambar
        'pdf_objstm_max'    => 4 * 1048576,    // batas hasil dekompresi per ObjStm PDF
        'pdf_objstm_count'  => 200,
        'zip_max_entries'   => 2000,
        'zip_max_uncompressed' => 100 * 1048576,
        'zip_max_ratio'     => 200,            // rasio ukuran asli : terkompresi untuk entri > 1 MB
        'zip_entry_scan_max' => 8 * 1048576,   // entri lebih besar tidak dibaca isinya
        'clamd_timeout'     => 20,
    ],
];
