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
|    docs/architecture/DESAIN_NORMALISASI_SKEMA_ROLE.md - jangan buat file itu,
|    sudah digantikan registry ini)
|
| PERINGATAN KEAMANAN: 'roles' di sini HANYA mengatur menu yang TAMPIL.
| Otorisasi sesungguhnya ada di constructor base controller tujuan
| (Admin_Controller / Admin_Kabkota_Controller / Admin_Bidang_Controller)
| + WHERE ganda scope di query. Menambah role di sini TANPA controller
| yang menegakkan role itu = menu tampil tapi diusir saat diklik - dan
| itu perilaku yang BENAR (fail-closed). JANGAN PERNAH melonggarkan guard
| controller untuk "menyamakan" dengan registry ini - perbaikannya selalu
| di registry.
|
| Lihat docs/architecture/ANCHOR_DASHBOARD_TERPADU.md untuk keputusan
| arsitektur lengkap dan checklist "cara menambah modul dashboard".
|
| Field per modul:
|   label   - teks menu
|   icon    - kelas ikon Phosphor (ph-*) - Font Awesome TIDAK di-load di shell admin
|   url     - path CI (base_url() ditambahkan otomatis di view), juga dipakai
|             deteksi active-state (exact match atau prefix "url/")
|   roles   - array role yang menunya TAMPIL (bukan otorisasi, lihat di atas)
|   scope   - null | 'kabupaten_id' | 'bidang_kode' - menu hanya dirender kalau
|             session punya kolom scope ini terisi
|   scope_values - opsional, daftar nilai scope yang diizinkan untuk modul ini;
|                  menu disembunyikan bila scope sesi tidak termasuk daftar
|   group   - label section sidebar
|   order   - urutan dalam group
|   table, review_by - opsional, peta pengajuan->reviewer (dokumentasi hidup)
|   pending_where    - opsional, [kolom => nilai] yang berarti "belum diproses"
|                      untuk tabel di atas. Vocabulary status memang beda per
|                      domain (pending/Baru/Diajukan/Pending) dan sengaja tidak
|                      diseragamkan di DB - di sinilah perbedaan itu
|                      dideklarasikan SEKALI, dipakai badge sidebar sekaligus
|                      kartu ringkas Admin_Dashboard.
|   status_column, owner_column - opsional, dipakai bareng 'table'. Nama kolom
|                      status & pemilik baris SUDAH beda-beda per domain
|                      (status_verifikasi/status/status_antrean; owner selalu
|                      user_id hari ini tapi jangan diasumsikan tetap begitu
|                      untuk role berikutnya) - sebelumnya nama kolom status
|                      cuma terkubur sebagai KEY di dalam pending_where, jadi
|                      tidak ada cara membacanya tanpa tahu domainnya duluan.
|                      Roadmap T6, cetakan untuk warga/mahasiswa/admin_kabkota/
|                      admin_bidang.
|   badge   - opsional TRUE: tampilkan counter merah di sidebar sejumlah baris
|             yang cocok pending_where. Butuh 'table' + 'pending_where'.
|   scope_column - opsional, kolom tabel yang menerima nilai 'scope' saat
|                  menghitung badge. Dideklarasikan eksplisit; jangan menebak
|                  nama kolom dari nama scope sesi.
|   ringkas - opsional label pendek untuk kartu overview superadmin; kalau tidak
|             diisi, modul tidak muncul sebagai kartu di Admin_Dashboard
|   overview_url - opsional URL khusus kartu overview; `url` tetap dipakai
|                  sidebar, sehingga kartu bisa langsung membuka antrean kerja
|   enabled - opsional, default true; set false untuk mematikan modul tanpa hapus entri
*/

// 'Publikasi' dicabut 14 Agt 2026 - satu-satunya isinya ('Direktori SRP2')
// pindah jadi anak 'Tinjau SRP2' (srp2_verifikasi_aktif), jadi grupnya
// sendiri tidak lagi punya modul apa pun untuk role mana pun.
$config['dashboard_module_groups'] = ['Utama', 'Layanan', 'Tindak Lanjut', 'Pemantauan', 'Manajemen', 'Akun'];

$config['dashboard_modules'] = [

    // ===== Milik semua role login (Pengaturan.php) =====
    // Halaman ini mendaftar pengajuan PRIBADI: SRP2, antrean perumahan, aduan,
    // KKN/magang - hal-hal yang dikirim seseorang untuk dirinya sendiri.
    //
    // `admin` (superadmin) sejak awal tidak diikutkan: ia bukan pemohon.
    // `admin_kabkota` dan `admin_bidang` DICABUT 30 Jul 2026 dengan alasan yang
    // sama, setelah user bertanya "admin kota pengajuan apa?" sambil melihat
    // layar yang berbunyi "Belum ada pengajuan yang tercatat."
    //
    // Alasan lama - "mereka juga user biasa yang bisa punya pengajuan pribadi" -
    // benar di atas kertas tetapi tidak pernah terjadi: akun dinas tidak mengirim
    // pengajuan RTLH untuk dirinya sendiri. Hasilnya menu yang SELALU kosong.
    // Menu yang selalu kosong bukan netral: ia membuat orang bertanya-tanya apa
    // yang belum mereka isi.
    //
    // Ini hanya menyembunyikan MENU. /akun tetap dapat dibuka langsung dan tetap
    // berfungsi - registry mengatur tampilan, bukan otorisasi (lihat peringatan
    // di kepala berkas). Kalau kelak ada akun dinas yang memang mengirim
    // pengajuan pribadi, kembalikan role-nya ke sini, jangan longgarkan controller.
    'status_pengajuan' => [
        'label' => 'Status Pengajuan', 'icon' => 'ph-list-checks',
        'url'   => 'akun', 'group' => 'Akun', 'order' => 10,
        'roles' => ['warga', 'pengembang', 'mahasiswa'],
        'scope' => null,
    ],
    // admin ikut di sini (beda dari status_pengajuan) sejak User_Profile
    // dilebur ke akun/profil - superadmin tetap butuh halaman profil sendiri.
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
    // Rekam Data disusun BERSARANG, bukan tujuh entri datar. Tujuh baris untuk
    // dua modul × tiga layar membuat sidebar didominasi satu fitur, dan tidak
    // ada yang memberi tahu bahwa Rekap Perumahan itu bagian dari Perumahan.
    //
    // `parent` menautkan anak ke induknya. Anak hanya dirender saat cabang itu
    // sedang dibuka (lihat MY_Controller::dashboard_menu()), jadi di halaman
    // lain tujuh entri ini menyusut jadi satu.
    //
    // Induknya `Rekam_Data` - layar sambutan. Sebelum ada entri ini, membuka
    // /Rekam_Data dari kartu beranda publik membuat sidebar TIDAK menyorot apa
    // pun: orang sampai di sana tanpa tahu sedang di cabang mana.
    'rekam_data' => [
        'label' => 'Rekam Data', 'icon' => 'ph-database',
        'url'   => 'Rekam_Data', 'group' => 'Layanan', 'order' => 20,
        'roles' => ['admin_kabkota'], 'scope' => 'kabupaten_id',
    ],
    'rekam_perumahan' => [
        'label' => 'Perumahan', 'icon' => 'ph-house-line',
        'url'   => 'Rekam_Perumahan', 'group' => 'Layanan', 'order' => 21,
        'parent' => 'rekam_data',
        'roles' => ['admin_kabkota'],
        'scope' => 'kabupaten_id',
        'table' => 'rd_laporan', 'review_by' => 'admin_bidang',
        'status_column' => 'status', 'owner_column' => 'kabupaten_id',
        'public_where' => NULL, 'editable_where' => NULL,
    ],
    'rekam_perumahan_rekap' => [
        'label' => 'Rekap', 'icon' => 'ph-chart-bar',
        'url'   => 'Rekam_Perumahan/rekap', 'group' => 'Layanan', 'order' => 22,
        'parent' => 'rekam_perumahan',
        'roles' => ['admin_kabkota'], 'scope' => 'kabupaten_id',
    ],
    'rekam_perumahan_riwayat' => [
        'label' => 'Riwayat', 'icon' => 'ph-clock-counter-clockwise',
        'url'   => 'Rekam_Perumahan/riwayat', 'group' => 'Layanan', 'order' => 23,
        'parent' => 'rekam_perumahan',
        'roles' => ['admin_kabkota'], 'scope' => 'kabupaten_id',
    ],
    'rekam_kawasan' => [
        'label' => 'Kawasan Permukiman', 'icon' => 'ph-map-trifold',
        'url'   => 'Rekam_Kawasan', 'group' => 'Layanan', 'order' => 24,
        'parent' => 'rekam_data',
        'roles' => ['admin_kabkota'],
        'scope' => 'kabupaten_id',
        'table' => 'rd_laporan', 'review_by' => 'admin_bidang',
        'status_column' => 'status', 'owner_column' => 'kabupaten_id',
        'public_where' => NULL, 'editable_where' => NULL,
    ],
    'rekam_kawasan_rekap' => [
        'label' => 'Rekap', 'icon' => 'ph-chart-bar',
        'url'   => 'Rekam_Kawasan/rekap', 'group' => 'Layanan', 'order' => 25,
        'parent' => 'rekam_kawasan',
        'roles' => ['admin_kabkota'], 'scope' => 'kabupaten_id',
    ],
    'rekam_kawasan_riwayat' => [
        'label' => 'Riwayat', 'icon' => 'ph-clock-counter-clockwise',
        'url'   => 'Rekam_Kawasan/riwayat', 'group' => 'Layanan', 'order' => 26,
        'parent' => 'rekam_kawasan',
        'roles' => ['admin_kabkota'], 'scope' => 'kabupaten_id',
    ],
    // Hanya bidang perumahan & kawasan yang punya Rekam Data; bidang lain
    // diarahkan keluar oleh controller dengan pesan jelas, bukan layar kosong.
    'rekam_tinjauan' => [
        'label' => 'Peninjauan Rekam Data', 'icon' => 'ph-clipboard-text',
        'url'   => 'Rekam_Tinjauan', 'group' => 'Layanan', 'order' => 20,
        'roles' => ['admin_bidang'],
        'scope' => 'bidang_kode',
        'scope_values' => ['perumahan', 'kawasan'],
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
        'label' => 'Ringkasan Kerja', 'icon' => 'ph-squares-four',
        'url'   => 'Admin_Dashboard', 'group' => 'Utama', 'order' => 10,
        'roles' => ['admin'], 'scope' => null,
    ],
    'validasi_antrean' => [
        'label' => 'Tinjau Antrean', 'icon' => 'ph-list-checks',
        'url'   => 'Admin', 'group' => 'Tindak Lanjut', 'order' => 10,
        'overview_url' => 'Admin?status=pending',
        'roles' => ['admin'], 'scope' => null,
        'table' => 'sf_housing_queue', 'review_by' => 'admin',
        'pending_where' => ['status_antrean' => 'pending'],
        'status_column' => 'status_antrean', 'owner_column' => 'user_id',
        'public_where' => NULL, 'editable_where' => NULL,
        'badge' => TRUE, 'ringkas' => 'Antrean Perumahan',
    ],
    'srp2_verifikasi' => [
        'label' => 'Tinjau SRP2', 'icon' => 'ph-seal-check',
        'url'   => 'Admin_Srp2/pending', 'group' => 'Tindak Lanjut', 'order' => 20,
        'overview_url' => 'Admin_Srp2/pending?status=Pending',
        'roles' => ['admin'], 'scope' => null,
        'table' => 'srp2_registrations', 'review_by' => 'admin',
        'pending_where' => ['status_verifikasi' => 'Pending'],
        'status_column' => 'status_verifikasi', 'owner_column' => 'user_id',
        'badge' => TRUE, 'ringkas' => 'Sertifikasi SRP2',
    ],
    /* Dua anak di bawah - permintaan user 14 Agt 2026: "Tinjau SRP2" dua
       tabel berbeda (lihat percakapan yang menemukan ini - srp2_registrations
       vs srp2_certified_developers, cuma 1 dari 67 baris certified yang
       tertaut ke pengajuan), jadi sidebar-nya dibuat menyuarakan itu lewat
       submenu, bukan cuma satu tautan yang membingungkan mana yang dilihat.
       `parent` menautkan ke srp2_verifikasi - mekanisme yang sama dipakai
       Rekam Data → Perumahan/Kawasan di atas, lihat catatannya untuk cara
       kerja buka/tutup & penyorotan aktifnya.

       srp2_verifikasi_aktif ke Admin_Srp2 (index() - form edit inline
       lengkap), BUKAN ke Admin_Srp2/aktif. Percobaan pertama memakai
       Admin_Srp2/aktif (daftar ringkas baru, read-only, tombol "Kelola →"
       melompat ke index() buat benar-benar mengedit) - user tegas menolak
       lompatan dua-klik itu: "Direktori SRP2" di sidebar harus LANGSUNG ke
       halaman yang bisa mengedit, satu klik. Admin_Srp2::aktif() dan
       views/admin/srp2/aktif.php akhirnya tidak dipakai jalur mana pun -
       DIHAPUS, bukan dibiarkan menggantung tanpa pintu masuk. */
    'srp2_verifikasi_pengajuan' => [
        'label' => 'SRP2 dalam Pengajuan', 'icon' => 'ph-hourglass-medium',
        'url'   => 'Admin_Srp2/pending', 'group' => 'Tindak Lanjut', 'order' => 21,
        'parent' => 'srp2_verifikasi',
        'roles' => ['admin'], 'scope' => null,
    ],
    'srp2_verifikasi_aktif' => [
        'label' => 'Direktori SRP2', 'icon' => 'ph-buildings',
        'url'   => 'Admin_Srp2', 'group' => 'Tindak Lanjut', 'order' => 22,
        'parent' => 'srp2_verifikasi',
        'roles' => ['admin'], 'scope' => null,
    ],
    // Read-only lintas bidang untuk superadmin (audit/eskalasi). Sengaja tanpa
    // endpoint tulis - keputusan tetap kewenangan admin_bidang.
    'aduan_semua' => [
        'label' => 'Pantau Aduan', 'icon' => 'ph-chat-centered-dots',
        'url'   => 'Admin_Aduan', 'group' => 'Pemantauan', 'order' => 10,
        'overview_url' => 'Admin_Aduan?status=Baru',
        'roles' => ['admin'], 'scope' => null,
        'table' => 'aduan', 'review_by' => 'admin_bidang',
        'pending_where' => ['status' => 'Baru'],
        'status_column' => 'status', 'owner_column' => 'user_id',
        'badge' => TRUE, 'ringkas' => 'Aduan Warga',
    ],
    // Pandangan superadmin atas Rekam Data - read-only lintas kabupaten DAN
    // lintas domain. Tanpa entri ini, superadmin (kursi yang dipakai reviewer
    // dinas) tidak punya satu pun layar rekam data, dan fitur yang sudah
    // lengkap sejak 30 Jul terbaca sebagai "belum ada".
    //
    // TANPA `badge`/`pending_where`: papan ini bukan antrean kerja superadmin.
    // Yang menunggu keputusan adalah antrean Admin Bidang (`rekam_tinjauan`),
    // dan badge di dua tempat untuk satu tumpukan pekerjaan membuat dua orang
    // mengira itu tugasnya.
    'rekam_pantau' => [
        'label' => 'Pantau Rekam Data', 'icon' => 'ph-chart-line-up',
        'url'   => 'Admin_Rekam_Data', 'group' => 'Pemantauan', 'order' => 20,
        'roles' => ['admin'], 'scope' => null,
        'table' => 'rd_laporan', 'review_by' => 'admin_bidang',
        'status_column' => 'status', 'owner_column' => 'kabupaten_id',
        'public_where' => NULL, 'editable_where' => NULL,
    ],
    // Master bidang & wilayah + cakupan petugasnya. Grup Manajemen, bersebelahan
    // dengan Akses Staf: keduanya menjawab "siapa menangani apa", dan gerbang
    // "belum ada petugas" di layar ini menautkan langsung ke sana.
    //
    // TANPA badge: nol petugas di sebuah wilayah bukan antrean yang menunggu
    // diproses hari ini, ia keadaan struktural. Badge merah permanen berhenti
    // dibaca dalam seminggu.
    'struktur_cakupan' => [
        'label' => 'Struktur & Cakupan', 'icon' => 'ph-tree-structure',
        // order 30: Akses Staf 10, Jejak Audit 20 sudah terpakai. Dua entri
        // ber-order sama diurutkan `usort` secara tidak stabil - posisinya bisa
        // berpindah antar permintaan tanpa ada yang mengubah apa pun.
        'url'   => 'Admin_Struktur', 'group' => 'Manajemen', 'order' => 30,
        'roles' => ['admin'], 'scope' => null,
    ],
    // Katalog program bantuan. Grup Manajemen bersama Akses Staf, Jejak Audit,
    // dan Struktur & Cakupan - keempatnya data acuan, bukan pekerjaan harian.
    //
    // TANPA badge: jumlah program bukan antrean.
    'katalog_program' => [
        'label' => 'Katalog Program', 'icon' => 'ph-list-checks',
        'url'   => 'Admin_Katalog_Program', 'group' => 'Manajemen', 'order' => 40,
        'roles' => ['admin'], 'scope' => null,
    ],
    'kemitraan' => [
        'label' => 'Kelola KKN/Magang', 'icon' => 'ph-graduation-cap',
        'url'   => 'Admin_Kemitraan', 'group' => 'Tindak Lanjut', 'order' => 30,
        'overview_url' => 'Admin_Kemitraan?status=Diajukan',
        'roles' => ['admin'], 'scope' => null,
        'table' => 'kkn_magang_pendaftaran', 'review_by' => 'admin',
        'pending_where' => ['status' => 'Diajukan'],
        'status_column' => 'status', 'owner_column' => 'user_id',
        'badge' => TRUE, 'ringkas' => 'KKN/Magang',
    ],
    // Janji temu konsultasi (migrasi 035). Superadmin saja: `forum_diskusi`
    // tidak punya kolom bidang maupun kabupaten, jadi tidak ada dasar apa pun
    // untuk membagi mejanya per scope.
    'konsultasi_janji' => [
        'label' => 'Janji Temu Konsultasi', 'icon' => 'ph-calendar-check',
        'url'   => 'Admin_Konsultasi', 'group' => 'Tindak Lanjut', 'order' => 40,
        'overview_url' => 'Admin_Konsultasi?status=diajukan',
        'roles' => ['admin'], 'scope' => null,
        'table' => 'forum_janji_temu', 'review_by' => 'admin',
        'pending_where' => ['status' => 'diajukan'],
        'status_column' => 'status', 'owner_column' => 'user_id',
        'badge' => TRUE, 'ringkas' => 'Janji Temu',
    ],
    // Meja KEDUA alur surat magang. Terpisah dari 'kemitraan' di atas karena
    // pemiliknya berbeda: yang itu sekretariat (superadmin), yang ini bidang.
    // 'pending_where' memakai status 'Ditinjau Bidang' - vocabulary status
    // memang beda per domain, dan di sinilah perbedaan itu dideklarasikan.
    // Butir F1: daftar posisi/lowongan magang, diisi dinas sendiri. Superadmin
    // saja - posisi berlaku lintas bidang, jadi memberi tiap admin bidang hak
    // menyunting daftar bersama membuat bidang saling menimpa.
    'magang_posisi' => [
        'label' => 'Posisi Magang', 'icon' => 'ph-briefcase',
        'url'   => 'Admin_Magang_Posisi', 'group' => 'Master', 'order' => 40,
        'roles' => ['admin'], 'scope' => null,
    ],
    'kemitraan_bidang' => [
        'label' => 'Magang Bidang Saya', 'icon' => 'ph-graduation-cap',
        'url'   => 'Kemitraan_Bidang', 'group' => 'Layanan', 'order' => 11,
        'roles' => ['admin_bidang'], 'scope' => 'bidang_kode',
        'table' => 'kkn_magang_pendaftaran', 'review_by' => 'admin_bidang',
        'pending_where' => ['status' => 'Ditinjau Bidang'],
        'scope_column' => 'bidang_kode',
        'status_column' => 'status', 'owner_column' => 'user_id',
        'badge' => TRUE,
    ],
    // CATATAN: slot magang TIDAK punya entri sendiri di sini. Ia satu domain
    // dengan pendaftaran di atas - yang satu menetapkan tempatnya, yang lain
    // memproses orang yang mengisinya - dan hidup sebagai tab di dalam
    // Admin_Kemitraan. Sidebar yang bertambah satu baris setiap kali ada layar
    // baru akan berhenti bisa dibaca. Deteksi active-state di registry ini
    // memakai prefix "url/", jadi Admin_Kemitraan/slot tetap menyalakan menu
    // KKN/Magang tanpa entri tambahan.
    'users' => [
        'label' => 'Akses Staf', 'icon' => 'ph-users',
        'url'   => 'Admin_Users', 'group' => 'Manajemen', 'order' => 10,
        'roles' => ['admin'], 'scope' => null,
    ],
    // Read-only murni: tanpa 'table'/'pending_where' karena jejak audit tidak
    // punya keadaan "belum diproses" - badge di sini akan mengajari orang bahwa
    // barisnya perlu dibereskan sampai nol, dan justru itu yang tidak boleh.
    'audit' => [
        'label' => 'Jejak Audit', 'icon' => 'ph-scroll',
        'url'   => 'Admin_Audit', 'group' => 'Manajemen', 'order' => 20,
        'roles' => ['admin'], 'scope' => null,
    ],
    // A6 - entri 'settings' DICABUT 29 Jul 2026 bersama controller Admin_Settings
    // dan view-nya. Layar itu berisi <form> tanpa action/method/CSRF, tombol
    // Simpan di LUAR form, empat tab href="#", dan toggle "Mode Pemeliharaan"
    // yang tidak menyalakan apa pun - dinas bisa mengiranya menutup situs
    // padahal terbuka penuh. Nol fungsi hilang: penyimpanan setting yang NYATA
    // ada di Admin_Content::update() lewat Setting_model.
];
