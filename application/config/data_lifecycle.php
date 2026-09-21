<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Siklus hidup informasi yang dikecualikan (data pribadi dan rahasia): pertukaran, penghapusan, audit
 * (form keamanan poin 7.3). Sumber tunggal untuk libraries/Penyapu_retensi.php, libraries/Data_erasure.php,
 * MY_Controller (audit akses), User_model (hapus dan ekspor akun), tests/data_lifecycle_test.php dan
 * tests/data_lifecycle_db_test.php. Penjelasan dan prosedur: docs/engineering/SIKLUS_HIDUP_DATA.md.
 */
$config['data_lifecycle'] = [

    /* PENGHAPUSAN 1: retensi. Yang kedaluwarsa dihapus otomatis (sekali per interval, dipicu permintaan
       web sesudah respons terkirim, atau `php index.php retensi jalankan` dari CLI/cron). */
    'retensi' => [
        'interval_detik'                => 86400,
        'snapshot_simperum_lewat_hari'  => 7,     // snapshot SIMPERUM yang sudah lewat expires_at sekian hari
        'rate_limit_hari'               => 2,     // penghitung batas laju (jendela terpanjang 1 hari)
        'token_surel_lewat_hari'        => 1,     // token verifikasi surel yang sudah kedaluwarsa
        'langganan_push_nonaktif_hari'  => 90,    // langganan Web Push yang sudah dinonaktifkan
        'log_aplikasi_hari'             => 180,   // log aplikasi terenkripsi di application/logs
        'jejak_audit_hari'              => 1825,  // 5 tahun; jejak audit sengaja disimpan lama
    ],

    /* PENGHAPUSAN 2: hapus akun. Nasib SETIAP kolom yang menunjuk ke usr_users. cascade = barisnya ikut
       terhapus; set_null = baris arsip layanan/keputusan tetap ada tanpa tautan ke akun (alasan wajib);
       Data_erasure menyapu berkas dan menyamarkan sisa identitas lebih dulu. Diperiksa terhadap skema
       nyata oleh tests/data_lifecycle_db_test.php. */
    'fk_usr_users' => [
        'aduan.user_id'                          => ['set_null', 'arsip layanan; pesan dan lampiran ditinjau admin lewat permintaan penghapusan data layanan'],
        'aduan.reviewed_by'                      => ['set_null', 'atribusi petugas'],
        'forum_janji_temu.user_id'               => ['cascade', 'janji temu milik akun'],
        'forum_janji_temu.reviewed_by'           => ['set_null', 'atribusi petugas'],
        'forum_laporan_komentar.user_id'         => ['cascade', 'laporan milik akun'],
        'kkn_magang_pendaftaran.user_id'         => ['cascade', 'pendaftaran milik akun; SEMUA berkasnya disapu dari disk'],
        'kkn_magang_pendaftaran.reviewed_by_bidang' => ['set_null', 'atribusi petugas'],
        'kkn_magang_pendaftaran.reviewed_by'     => ['set_null', 'atribusi petugas'],
        'rd_laporan.reviewed_by'                 => ['set_null', 'atribusi petugas'],
        'rd_laporan.submitted_by'                => ['set_null', 'laporan rekam data adalah arsip dinas'],
        'rd_perumahan_bnba.uploaded_by'          => ['set_null', 'atribusi petugas'],
        'sf_berkas_penilaian.uploaded_by'        => ['set_null', 'berkas penilaian terkirim adalah arsip; draf disapu'],
        'sf_berkas_penilaian.verified_by'        => ['set_null', 'atribusi petugas'],
        'sf_housing_queue.reviewed_by'           => ['set_null', 'atribusi petugas'],
        'sf_housing_queue.user_id'               => ['set_null', 'antrean pengajuan adalah arsip layanan; ditinjau lewat permintaan penghapusan data layanan'],
        'sf_penilaian_perumahan.user_id'         => ['set_null', 'penilaian TERKIRIM adalah arsip; DRAF dihapus beserta berkasnya saat akun dihapus'],
        'sf_profil_warga.user_id'                => ['cascade', 'profil terenkripsi milik akun'],
        'sf_rekaman_simperum.requested_by'       => ['set_null', 'snapshot dihapus oleh retensi; tidak menunjuk orang lagi'],
        'sf_riwayat_keputusan_antrean.actor_id'  => ['set_null', 'riwayat keputusan adalah arsip'],
        'srp2_registrations.user_id'             => ['cascade', 'pengajuan SRP2 milik akun; berkasnya disapu dari disk'],
        'srp2_registrations.reviewed_by'         => ['set_null', 'atribusi petugas'],
        'sys_jejak_audit.actor_id'               => ['set_null', 'jejak audit disimpan; surel pelaku disamarkan (pseudonim) saat akun dihapus'],
        'sys_push_subscriptions.user_id'         => ['cascade', 'langganan perangkat milik akun'],
        'usr_admin_module_privileges.user_id'    => ['cascade', 'hak modul milik akun'],
        'usr_admin_module_privileges.updated_by' => ['set_null', 'atribusi petugas'],
        'usr_documents.user_id'                  => ['cascade', 'dokumen onboarding milik akun; berkasnya disapu dari disk'],
    ],

    /* Kolom pemilik TANPA kunci asing: Data_erasure/User_model menanganinya secara eksplisit. */
    'kolom_tanpa_fk' => [
        'forum_diskusi.user_id'    => 'dianonimkan (user_id NULL, nama dan surel disamarkan)',
        'forum_komentar.user_id'   => 'dianonimkan (user_id NULL, nama disamarkan)',
        'forum_likes.user_id'      => 'dihapus',
    ],

    /* PERTUKARAN 1: ekspor data akun oleh pemilik (Pengaturan/export_account_data, butuh kata sandi,
       dibatasi laju, diaudit). Tabel milik akun yang diekspor; sisanya dikecualikan dengan alasan. */
    'ekspor_akun' => [
        'tabel' => ['sf_profil_warga', 'sf_penilaian_perumahan', 'sf_housing_queue', 'aduan', 'srp2_registrations',
                    'kkn_magang_pendaftaran', 'forum_diskusi', 'forum_komentar', 'forum_janji_temu', 'usr_documents'],
        'dikecualikan' => [
            'forum_laporan_komentar'       => 'laporan moderasi kepada admin; bukan data isian pemilik',
            'sys_push_subscriptions'       => 'kredensial langganan perangkat (kunci enkripsi push); rahasia, bukan data profil',
            'usr_admin_module_privileges'  => 'hak akses staf yang diberikan superadmin; bukan data pemilik',
            'forum_likes'                  => 'tanda suka tanpa isi pribadi',
        ],
    ],

    /* PERTUKARAN 2: register aliran data ke/dari pihak luar. Setiap berkas yang membuka koneksi keluar
       (daftar izin pindai_kode_berbahaya.php) WAJIB tercatat di sini beserta data pribadi yang dikirim. */
    'pertukaran' => [
        'libraries/Simperum_gateway.php' => ['pihak' => 'SIMPERUM Disperakim Jateng', 'arah' => 'keluar+masuk', 'data' => 'NIK (kueri); profil RTLH (balasan)',
            'perlindungan' => 'HTTPS terverifikasi; balasan disimpan terenkripsi (AES-256-GCM) dengan kedaluwarsa; peminta tercatat di sf_rekaman_simperum.requested_by', 'pribadi' => TRUE],
        'controllers/Auth.php'           => ['pihak' => 'Google (reCAPTCHA dan OAuth)', 'arah' => 'keluar+masuk', 'data' => 'token tantangan dan alamat IP (reCAPTCHA); surel dan nama akun Google (OAuth)',
            'perlindungan' => 'HTTPS; state OAuth sekali pakai; reCAPTCHA tidak aktif bila kunci kosong', 'pribadi' => TRUE],
        'libraries/Web_push_service.php' => ['pihak' => 'layanan push peramban', 'arah' => 'keluar', 'data' => 'judul dan isi notifikasi generik tanpa data pribadi',
            'perlindungan' => 'VAPID dan enkripsi payload; kredensial langganan terenkripsi di DB', 'pribadi' => FALSE],
        'libraries/Sikaper_api.php'      => ['pihak' => 'Sikaper Jateng', 'arah' => 'keluar+masuk', 'data' => 'kueri data publik; tidak ada data pribadi',
            'perlindungan' => 'HTTPS terverifikasi', 'pribadi' => FALSE],
        'libraries/Ternak_api.php'       => ['pihak' => 'API Ternak/KRS Jawa 3', 'arah' => 'keluar+masuk', 'data' => 'artikel dan desain publik; tidak ada data pribadi',
            'perlindungan' => 'HTTPS terverifikasi', 'pribadi' => FALSE],
        'controllers/Index.php'          => ['pihak' => 'Sikumbang Tapera', 'arah' => 'keluar+masuk', 'data' => 'pencarian perumahan publik; tidak ada data pribadi',
            'perlindungan' => 'HTTPS terverifikasi; cache lokal hanya berisi data publik', 'pribadi' => FALSE],
        'helpers/sikumbang_helper.php'   => ['pihak' => 'Sikumbang Tapera', 'arah' => 'keluar+masuk', 'data' => 'data perumahan publik; tidak ada data pribadi',
            'perlindungan' => 'HTTPS terverifikasi', 'pribadi' => FALSE],
        'controllers/Chat.php'           => ['pihak' => 'Google Gemini', 'arah' => 'keluar', 'data' => 'teks pertanyaan warga (jalur dikarantina 404, tidak aktif)',
            'perlindungan' => 'HTTPS; kunci API di header (bukan URI); jalur dimatikan', 'pribadi' => TRUE],
        'libraries/Upload_scanner.php'   => ['pihak' => 'ClamAV lokal (clamd)', 'arah' => 'keluar', 'data' => 'isi berkas unggahan ke pemindai antivirus di mesin yang sama/jaringan internal',
            'perlindungan' => 'hanya bila CLAMD_ADDRESS diisi operator (soket unix/tcp); tidak aktif di production', 'pribadi' => TRUE],
    ],

    /* AUDIT: akses staf ke informasi pribadi dicatat di sys_jejak_audit, ditekan per pelaku+objek. */
    'audit' => [
        'akses_dedupe_detik' => 600,
        'peran_staf'         => ['admin', 'admin_kabkota', 'admin_bidang'],
    ],
];
