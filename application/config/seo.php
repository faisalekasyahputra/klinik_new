<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * SEO portal publik (6 Okt 2026). Dibaca seo_meta() di helpers/seo_helper.php, robots.txt dan
 * sitemap.xml di controllers/Seo.php.
 *
 * 'halaman': kunci = uri dengan huruf besar-kecil PERSIS seperti rute di Linux (dicocokkan tanpa membedakan
 *   huruf, dipakai apa adanya untuk canonical dan sitemap; '' = beranda). Judul ditulis
 *   TANPA nama situs (ditambahkan otomatis), sekitar 50-60 karakter; deskripsi 120-160 karakter.
 *   'peta' => FALSE = tidak masuk sitemap (tetap boleh diindeks). Halaman detail (perumahan, desain,
 *   kawasan) mengisi $seo sendiri dari datanya dan tidak perlu terdaftar di sini.
 * 'noindex': uri (huruf rute asli; meta dicocokkan tanpa membedakan huruf) yang diberi <meta name="robots" content="noindex"> dan
 *   Disallow di robots.txt: cocok persis atau diikuti '/'; entri berakhiran '/' atau '_' = awalan mentah.
 *   Isinya halaman pribadi, alur akun, dan halaman pratinjau (dummy).
 */
$config['seo'] = [
    'nama_situs' => 'Klinik PKP Jawa Tengah',
    'deskripsi'  => 'Portal layanan perumahan dan kawasan permukiman Disperakim Provinsi Jawa Tengah: cari rumah subsidi, cek data rumah, simulasi KPR, data kawasan, dan konsultasi.',
    'gambar'     => 'assets/img/og-cover.jpg',
    'gambar_lebar'  => 1200,
    'gambar_tinggi' => 630,
    'organisasi' => [
        'nama'  => 'Dinas Perumahan Rakyat dan Kawasan Permukiman Provinsi Jawa Tengah',
        'logo'  => 'assets/img/logo-jateng.png',
    ],

    'halaman' => [
        '' => ['judul' => 'Portal Perumahan dan Permukiman',
            'deskripsi' => 'Klinik PKP Disperakim Jawa Tengah: cari rumah subsidi, cek data rumah tidak layak huni, simulasi KPR, data kawasan kumuh, dan konsultasi perumahan dalam satu portal.'],
        'golek_omah' => ['judul' => 'Nggolek Omah: Cari Rumah Subsidi Jawa Tengah',
            'deskripsi' => 'Cari perumahan subsidi dan komersial di seluruh kabupaten/kota Jawa Tengah dari data SIKUMBANG, lengkap dengan tipe rumah, harga, dan lokasi.'],
        'cari_rumah' => ['judul' => 'Cari Rumah per Kabupaten/Kota',
            'deskripsi' => 'Telusuri daftar perumahan di Jawa Tengah per kabupaten/kota, saring rumah subsidi atau komersial, lalu lihat detail tipe, harga, dan pengembangnya.'],
        'program-pemerintah' => ['judul' => 'Program Perumahan Pemerintah',
            'deskripsi' => 'Daftar program perumahan Pemprov Jawa Tengah: KPR subsidi FLPP, Oemah Lestari, bantuan RTLH, pembangunan baru, dan rumah apung, lengkap dengan syaratnya.'],
        'simulasi_kpr' => ['judul' => 'Simulasi KPR Rumah Subsidi dan Komersial',
            'deskripsi' => 'Hitung perkiraan angsuran KPR rumah subsidi FLPP maupun komersial: masukkan harga rumah, uang muka, tenor, dan bunga untuk melihat cicilan per bulan.'],
        'panduan_desain' => ['judul' => 'Panduan Desain Rumah Layak Huni',
            'deskripsi' => 'Kumpulan contoh desain rumah sederhana layak huni dari Disperakim Jawa Tengah, lengkap dengan denah, ukuran, dan gambar tampak.'],
        'kawasan_kumuh' => ['judul' => 'Data Kawasan Kumuh Jawa Tengah',
            'deskripsi' => 'Data kawasan permukiman kumuh di kabupaten/kota Jawa Tengah: luas, tingkat kekumuhan, dan RT/RW terdampak, bersumber dari Sikaper.'],
        'sebaran' => ['judul' => 'Peta Sebaran Perumahan Jawa Tengah', 'peta' => FALSE,
            'deskripsi' => 'Peta sebaran lokasi perumahan di Jawa Tengah per wilayah, untuk melihat persebaran hunian subsidi dan komersial.'],
        'psu' => ['judul' => 'Serah Terima PSU Perumahan',
            'deskripsi' => 'Daftar perumahan dan status serah terima prasarana, sarana, dan utilitas (PSU) dari pengembang kepada pemerintah kabupaten/kota di Jawa Tengah.'],
        'Statistika' => ['judul' => 'Statistika Perumahan dan Kawasan Permukiman',
            'deskripsi' => 'Ringkasan angka perumahan dan kawasan permukiman Jawa Tengah: unit rumah, pengembang tersertifikasi, dan data pendukung dari Bank Data Disperakim.'],
        'Dokumen' => ['judul' => 'Buku Data dan Dokumen Perumahan',
            'deskripsi' => 'Unduh dan baca Buku Data serta dokumen resmi Disperakim Provinsi Jawa Tengah tentang perumahan dan kawasan permukiman.'],
        'Cek_Rtlh' => ['judul' => 'Cek Data Rumah Tidak Layak Huni (RTLH)',
            'deskripsi' => 'Periksa apakah rumah Anda sudah tercatat dalam data rumah tidak layak huni (RTLH) Jawa Tengah sebelum mengajukan bantuan perbaikan rumah.'],
        'tab/pengembang' => ['judul' => 'Layanan untuk Pengembang Perumahan',
            'deskripsi' => 'Layanan Disperakim Jawa Tengah bagi pengembang perumahan: sertifikasi SRP2, direktori pengembang, dan serah terima PSU.'],
        'tab/bankdata' => ['judul' => 'Bank Data Perumahan dan Permukiman',
            'deskripsi' => 'Bank Data Disperakim Jawa Tengah: statistika perumahan, buku data, dan dokumen kawasan permukiman yang bisa dibaca dan diunduh.'],
        'Umum/pengembang' => ['judul' => 'Direktori Pengembang Perumahan Jawa Tengah',
            'deskripsi' => 'Direktori pengembang perumahan yang beroperasi di Jawa Tengah beserta status sertifikasi SRP2 dan asosiasinya.'],
        'Pengembang/syarat' => ['judul' => 'Syarat Sertifikasi Pengembang (SRP2)',
            'deskripsi' => 'Syarat dan dokumen yang perlu disiapkan pengembang perumahan untuk mengajukan sertifikasi SRP2 di Disperakim Provinsi Jawa Tengah.'],
        'Pengembang/sertifikasi' => ['judul' => 'Pengembang Tersertifikasi SRP2',
            'deskripsi' => 'Daftar pengembang perumahan di Jawa Tengah yang sudah tersertifikasi SRP2, lengkap dengan masa berlaku sertifikatnya.'],
        'KemitraanPortal/magang' => ['judul' => 'Program Magang di Disperakim Jawa Tengah',
            'deskripsi' => 'Informasi dan pendaftaran magang mahasiswa di bidang-bidang Disperakim Provinsi Jawa Tengah, termasuk posisi yang sedang dibuka.'],
        'KemitraanPortal/kkn' => ['judul' => 'Program KKN Tematik Perumahan',
            'deskripsi' => 'Kemitraan KKN tematik perumahan dan kawasan permukiman bersama perguruan tinggi, dari pendaftaran sampai sertifikat.'],
        'KemitraanPortal/sertifikat_kkn' => ['judul' => 'Cetak Sertifikat KKN',
            'deskripsi' => 'Cari dan cetak sertifikat KKN dengan NIM. Sertifikat diterbitkan Disperakim Provinsi Jawa Tengah sesudah periode KKN selesai.'],
        'kemitraan' => ['judul' => 'Kemitraan KKN dan Magang',
            'deskripsi' => 'Kerja sama Disperakim Jawa Tengah dengan perguruan tinggi lewat program KKN tematik dan magang mahasiswa.'],
        'umum' => ['judul' => 'Layanan Umum Klinik PKP',
            'deskripsi' => 'Layanan umum Klinik PKP: konsultasi perumahan, pengaduan, dan janji temu dengan petugas Disperakim Provinsi Jawa Tengah.'],
        'pengembang' => ['judul' => 'Informasi untuk Pengembang Perumahan', 'peta' => FALSE,
            'deskripsi' => 'Informasi layanan Disperakim Jawa Tengah untuk pengembang perumahan, dari sertifikasi SRP2 sampai serah terima PSU.'],
        'profil' => ['judul' => 'Sejarah, Visi, dan Misi Disperakim',
            'deskripsi' => 'Profil Dinas Perumahan Rakyat dan Kawasan Permukiman Provinsi Jawa Tengah: sejarah, visi, dan misi.'],
        'tugas_pokok' => ['judul' => 'Tugas Pokok dan Fungsi Disperakim Jawa Tengah',
            'deskripsi' => 'Tugas pokok dan fungsi Dinas Perumahan Rakyat dan Kawasan Permukiman Provinsi Jawa Tengah beserta bidang-bidangnya.'],
        'kebijakan-privasi' => ['judul' => 'Kebijakan Privasi',
            'deskripsi' => 'Bagaimana Klinik PKP mengumpulkan, memakai, melindungi, dan menghapus data pribadi pengguna layanan.'],
        'syarat-ketentuan' => ['judul' => 'Syarat dan Ketentuan',
            'deskripsi' => 'Syarat dan ketentuan penggunaan portal dan layanan Klinik PKP Disperakim Provinsi Jawa Tengah.'],
        'Auth/login' => ['judul' => 'Masuk', 'peta' => FALSE,
            'deskripsi' => 'Masuk ke akun Klinik PKP untuk mengakses layanan perumahan dan kawasan permukiman Jawa Tengah.'],
        'Auth/register' => ['judul' => 'Daftar Akun', 'peta' => FALSE,
            'deskripsi' => 'Buat akun Klinik PKP untuk mengajukan pendataan rumah, konsultasi, dan layanan Disperakim Jawa Tengah.'],
    ],

    'noindex' => [
        'akun', 'pengaturan', 'pemberitahuan', 'onboarding', 'warga/', 'atur-sandi', 'verify/',
        'Auth/forgot_password', 'forgot-password', 'Auth/google', 'Auth/akses_ditolak',
        'solusi_pembiayaan', 'cek_status_pengajuan', 'Umum/forum', 'Umum/detail/', 'Umum/aduan',
        'KemitraanPortal/daftar', 'KemitraanPortal/kkn_', 'KemitraanPortal/sertifikat',
        'Pengembang/formulir', 'Pengembang/daftar', 'Pengembang/dashboard',
        // Halaman pratinjau (dummy) pertanahan dan materia: jangan tampil di hasil pencarian sebagai layanan nyata.
        'info_tanah', 'sertifikasi', 'sengketa', 'bank_tanah', 'materia',
    ],
];
