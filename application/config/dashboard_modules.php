<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Registry Modul Dashboard Terpadu
| -------------------------------------------------------------------------
| Satu sumber kebenaran untuk:
| 1. Menu sidebar per role (application/views/admin/layouts/sidebar.php)
| 2. Peta role -> tabel pengajuan -> reviewer (dokumentasi hidup, menggantikan
|    rencana file role_admin_map.php yang disebut di
|    docs/architecture/DESAIN_NORMALISASI_SKEMA_ROLE.md — jangan buat file itu,
|    sudah digantikan registry ini)
|
| PERINGATAN KEAMANAN: 'roles' di sini HANYA mengatur menu yang TAMPIL.
| Otorisasi sesungguhnya ada di constructor base controller tujuan
| (Admin_Controller / Admin_Kabkota_Controller / Admin_Bidang_Controller)
| + WHERE ganda scope di query. Menambah role di sini TANPA controller
| yang menegakkan role itu = menu tampil tapi diusir saat diklik — dan
| itu perilaku yang BENAR (fail-closed). JANGAN PERNAH melonggarkan guard
| controller untuk "menyamakan" dengan registry ini — perbaikannya selalu
| di registry.
|
| Lihat docs/architecture/ANCHOR_DASHBOARD_TERPADU.md untuk keputusan
| arsitektur lengkap dan checklist "cara menambah modul dashboard".
|
| Field per modul:
|   label   - teks menu
|   icon    - kelas ikon Phosphor (ph-*) — Font Awesome TIDAK di-load di shell admin
|   url     - path CI (base_url() ditambahkan otomatis di view), juga dipakai
|             deteksi active-state (exact match atau prefix "url/")
|   roles   - array role yang menunya TAMPIL (bukan otorisasi, lihat di atas)
|   scope   - null | 'kabupaten_id' | 'bidang_kode' — menu hanya dirender kalau
|             session punya kolom scope ini terisi
|   group   - label section sidebar
|   order   - urutan dalam group
|   table, review_by - opsional, peta pengajuan->reviewer (dokumentasi hidup)
|   pending_where    - opsional, [kolom => nilai] yang berarti "belum diproses"
|                      untuk tabel di atas. Vocabulary status memang beda per
|                      domain (pending/Baru/Diajukan/Pending) dan sengaja tidak
|                      diseragamkan di DB — di sinilah perbedaan itu
|                      dideklarasikan SEKALI, dipakai badge sidebar sekaligus
|                      kartu ringkas Admin_Dashboard.
|   status_column, owner_column - opsional, dipakai bareng 'table'. Nama kolom
|                      status & pemilik baris SUDAH beda-beda per domain
|                      (status_verifikasi/status/status_antrean; owner selalu
|                      user_id hari ini tapi jangan diasumsikan tetap begitu
|                      untuk role berikutnya) — sebelumnya nama kolom status
|                      cuma terkubur sebagai KEY di dalam pending_where, jadi
|                      tidak ada cara membacanya tanpa tahu domainnya duluan.
|                      Roadmap T6, cetakan untuk warga/mahasiswa/admin_kabkota/
|                      admin_bidang.
|   badge   - opsional TRUE: tampilkan counter merah di sidebar sejumlah baris
|             yang cocok pending_where. Butuh 'table' + 'pending_where'.
|   ringkas - opsional label pendek untuk kartu overview superadmin; kalau tidak
|             diisi, modul tidak muncul sebagai kartu di Admin_Dashboard
|   enabled - opsional, default true; set false untuk mematikan modul tanpa hapus entri
*/

$config['dashboard_module_groups'] = ['Utama', 'Layanan', 'Manajemen', 'Akun'];

$config['dashboard_modules'] = [

    // ===== Milik semua role login (Pengaturan.php) =====
    // admin sengaja TIDAK diikutkan: superadmin bukan pemohon, punya 'overview'
    // sendiri. admin_kabkota/admin_bidang tetap ikut — mereka juga user biasa
    // yang bisa punya pengajuan pribadi, dan sidebar lama mereka memang cuma
    // 1 item scoped, jadi menu Akun ini murni tambahan yang benar.
    'status_pengajuan' => [
        'label' => 'Status Pengajuan', 'icon' => 'ph-list-checks',
        'url'   => 'akun', 'group' => 'Akun', 'order' => 10,
        'roles' => ['warga', 'pengembang', 'mahasiswa', 'admin_kabkota', 'admin_bidang'],
        'scope' => null,
    ],
    // admin ikut di sini (beda dari status_pengajuan) sejak User_Profile
    // dilebur ke akun/profil — superadmin tetap butuh halaman profil sendiri.
    'profil' => [
        'label' => 'Profil Saya', 'icon' => 'ph-user-circle',
        'url'   => 'akun/profil', 'group' => 'Akun', 'order' => 20,
        'roles' => ['warga', 'pengembang', 'mahasiswa', 'admin', 'admin_kabkota', 'admin_bidang'],
        'scope' => null,
    ],

    // ===== Admin ter-scope =====
    'antrean_kabkota' => [
        'label' => 'Antrean Wilayah Saya', 'icon' => 'ph-ticket',
        'url'   => 'Admin_Kabkota', 'group' => 'Layanan', 'order' => 10,
        'roles' => ['admin_kabkota'],
        'scope' => 'kabupaten_id',
        'table' => 'sf_housing_queue', 'review_by' => 'admin_kabkota',
        'pending_where' => ['status_antrean' => 'pending'],
        'status_column' => 'status_antrean', 'owner_column' => 'user_id',
        'public_where' => NULL, 'editable_where' => NULL,
    ],
    'rekam_perumahan' => [
        'label' => 'Rekam Data — Perumahan', 'icon' => 'ph-table',
        'url'   => 'Rekam_Perumahan', 'group' => 'Layanan', 'order' => 20,
        'roles' => ['admin_kabkota'],
        'scope' => 'kabupaten_id',
        'table' => 'rd_laporan', 'review_by' => 'admin_bidang',
        'status_column' => 'status', 'owner_column' => 'kabupaten_id',
        'public_where' => NULL, 'editable_where' => NULL,
    ],
    'rekam_perumahan_rekap' => [
        'label' => 'Rekap Perumahan', 'icon' => 'ph-chart-bar',
        'url'   => 'Rekam_Perumahan/rekap', 'group' => 'Layanan', 'order' => 21,
        'roles' => ['admin_kabkota'], 'scope' => 'kabupaten_id',
    ],
    'rekam_perumahan_riwayat' => [
        'label' => 'Riwayat Perumahan', 'icon' => 'ph-clock-counter-clockwise',
        'url'   => 'Rekam_Perumahan/riwayat', 'group' => 'Layanan', 'order' => 22,
        'roles' => ['admin_kabkota'], 'scope' => 'kabupaten_id',
    ],
    'rekam_kawasan' => [
        'label' => 'Rekam Data — Kawasan', 'icon' => 'ph-map-trifold',
        'url'   => 'Rekam_Kawasan', 'group' => 'Layanan', 'order' => 30,
        'roles' => ['admin_kabkota'],
        'scope' => 'kabupaten_id',
        'table' => 'rd_laporan', 'review_by' => 'admin_bidang',
        'status_column' => 'status', 'owner_column' => 'kabupaten_id',
        'public_where' => NULL, 'editable_where' => NULL,
    ],
    'rekam_kawasan_rekap' => [
        'label' => 'Rekap Kawasan', 'icon' => 'ph-chart-bar',
        'url'   => 'Rekam_Kawasan/rekap', 'group' => 'Layanan', 'order' => 31,
        'roles' => ['admin_kabkota'], 'scope' => 'kabupaten_id',
    ],
    'rekam_kawasan_riwayat' => [
        'label' => 'Riwayat Kawasan', 'icon' => 'ph-clock-counter-clockwise',
        'url'   => 'Rekam_Kawasan/riwayat', 'group' => 'Layanan', 'order' => 32,
        'roles' => ['admin_kabkota'], 'scope' => 'kabupaten_id',
    ],
    // Hanya bidang perumahan & kawasan yang punya Rekam Data; bidang lain
    // diarahkan keluar oleh controller dengan pesan jelas, bukan layar kosong.
    'rekam_tinjauan' => [
        'label' => 'Peninjauan Rekam Data', 'icon' => 'ph-clipboard-text',
        'url'   => 'Rekam_Tinjauan', 'group' => 'Layanan', 'order' => 20,
        'roles' => ['admin_bidang'],
        'scope' => 'bidang_kode',
        'table' => 'rd_laporan', 'review_by' => 'admin_bidang',
        'status_column' => 'status', 'owner_column' => 'kabupaten_id',
        'public_where' => NULL, 'editable_where' => NULL,
    ],
    'aduan_bidang' => [
        'label' => 'Aduan Bidang Saya', 'icon' => 'ph-chat-centered-text',
        'url'   => 'Admin_Bidang', 'group' => 'Layanan', 'order' => 10,
        'roles' => ['admin_bidang'],
        'scope' => 'bidang_kode',
        'table' => 'aduan', 'review_by' => 'admin_bidang',
        'pending_where' => ['status' => 'Baru'],
        'status_column' => 'status', 'owner_column' => 'user_id',
    ],

    // ===== Superadmin =====
    'overview' => [
        'label' => 'Dashboard', 'icon' => 'ph-squares-four',
        'url'   => 'Admin_Dashboard', 'group' => 'Utama', 'order' => 10,
        'roles' => ['admin'], 'scope' => null,
    ],
    'validasi_antrean' => [
        'label' => 'Validasi Antrean', 'icon' => 'ph-list-checks',
        'url'   => 'Admin', 'group' => 'Utama', 'order' => 20,
        'roles' => ['admin'], 'scope' => null,
        'table' => 'sf_housing_queue', 'review_by' => 'admin',
        'pending_where' => ['status_antrean' => 'pending'],
        'status_column' => 'status_antrean', 'owner_column' => 'user_id',
        'public_where' => NULL, 'editable_where' => NULL,
        'badge' => TRUE, 'ringkas' => 'Antrean Perumahan',
    ],
    'srp2_verifikasi' => [
        'label' => 'Verifikasi SRP2', 'icon' => 'ph-seal-check',
        'url'   => 'Admin_Srp2/pending', 'group' => 'Layanan', 'order' => 10,
        'roles' => ['admin'], 'scope' => null,
        'table' => 'srp2_registrations', 'review_by' => 'admin',
        'pending_where' => ['status_verifikasi' => 'Pending'],
        'status_column' => 'status_verifikasi', 'owner_column' => 'user_id',
        'badge' => TRUE, 'ringkas' => 'Sertifikasi SRP2',
    ],
    'srp2_direktori' => [
        'label' => 'Pengembang SRP2', 'icon' => 'ph-buildings',
        'url'   => 'Admin_Srp2', 'group' => 'Layanan', 'order' => 20,
        'roles' => ['admin'], 'scope' => null,
    ],
    // Read-only lintas bidang untuk superadmin (audit/eskalasi). Sengaja tanpa
    // endpoint tulis — keputusan tetap kewenangan admin_bidang.
    'aduan_semua' => [
        'label' => 'Semua Aduan', 'icon' => 'ph-chat-centered-dots',
        'url'   => 'Admin_Aduan', 'group' => 'Layanan', 'order' => 25,
        'roles' => ['admin'], 'scope' => null,
        'table' => 'aduan', 'review_by' => 'admin_bidang',
        'pending_where' => ['status' => 'Baru'],
        'status_column' => 'status', 'owner_column' => 'user_id',
        'badge' => TRUE, 'ringkas' => 'Aduan Warga',
    ],
    'kemitraan' => [
        'label' => 'KKN/Magang', 'icon' => 'ph-graduation-cap',
        'url'   => 'Admin_Kemitraan', 'group' => 'Layanan', 'order' => 30,
        'roles' => ['admin'], 'scope' => null,
        'table' => 'kkn_magang_pendaftaran', 'review_by' => 'admin',
        'pending_where' => ['status' => 'Diajukan'],
        'status_column' => 'status', 'owner_column' => 'user_id',
        'badge' => TRUE, 'ringkas' => 'KKN/Magang',
    ],
    'users' => [
        'label' => 'Pengguna', 'icon' => 'ph-users',
        'url'   => 'Admin_Users', 'group' => 'Manajemen', 'order' => 10,
        'roles' => ['admin'], 'scope' => null,
    ],
    'content' => [
        'label' => 'Konten Website', 'icon' => 'ph-article',
        'url'   => 'Admin_Content', 'group' => 'Manajemen', 'order' => 20,
        'roles' => ['admin'], 'scope' => null,
    ],
    // A6 — entri 'settings' DICABUT 29 Jul 2026 bersama controller Admin_Settings
    // dan view-nya. Layar itu berisi <form> tanpa action/method/CSRF, tombol
    // Simpan di LUAR form, empat tab href="#", dan toggle "Mode Pemeliharaan"
    // yang tidak menyalakan apa pun — dinas bisa mengiranya menutup situs
    // padahal terbuka penuh. Nol fungsi hilang: penyimpanan setting yang NYATA
    // ada di Admin_Content::update() lewat Setting_model.
];
