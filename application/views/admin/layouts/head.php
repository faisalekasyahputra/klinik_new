<!DOCTYPE html>
<html lang="id" x-data="{ darkMode: localStorage.getItem('theme') !== 'light' }" x-init="$watch('darkMode', val => localStorage.setItem('theme', val ? 'dark' : 'light'))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <?= csp_meta_tag() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Prevent FOUC (Flash of Unstyled Content) for Dark Mode -->
    <script>
        if (localStorage.getItem('theme') !== 'light') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <?php
    // Sama seperti admin/layouts/topbar.php/sidebar.php - lihat komentar
    // lengkap di sana. Sejak role 'universitas' berdiri sendiri (22 Agt
    // 2026, gantikan penyamaran label "mahasiswa" -> "Universitas"),
    // ucwords() generik di bawah sudah cukup - tidak perlu kasus khusus lagi.
    $peranSesi = $this->session->userdata('role');
    $roleUser = $peranSesi ? ucwords(str_replace('_', ' ', $peranSesi)) : 'Super Admin';
    ?>
    <title><?= isset($title) ? $title . ' - ' : '' ?><?= $roleUser ?> | Klinik PKP</title>
    
    <!-- Favicon -->
    <link rel="icon" href="<?= base_url('assets/img/logo-jateng.png') ?>" type="image/png">
    <link rel="shortcut icon" href="<?= base_url('assets/img/logo-jateng.png') ?>" type="image/png">
    <link rel="manifest" href="<?= base_url('manifest.webmanifest') ?>">
    <meta name="theme-color" content="#00545f">
    <meta name="csrf-token-name" content="<?= html_escape($this->security->get_csrf_token_name()) ?>">
    <meta name="csrf-token-hash" content="<?= html_escape($this->security->get_csrf_hash()) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/notifications.css?v=' . filemtime('assets/css/notifications.css')) ?>">
    <!-- Google Fonts -->
    <link rel="stylesheet" href="<?= base_url('assets/css/fonts.css?v=' . filemtime('assets/css/fonts.css')) ?>">
    <!-- Tailwind CSS -->
    <?php // Hasil panen kelas view admin - first paint bergaya penuh tanpa
          // menunggu CDN. Regenerasi: php docs/engineering/panen_tailwind.php admin
          // CDN di bawah DIUBAH ke defer (dulu blocking: layar putih sampai
          // ~110KB JS termuat & seluruh CSS di-generate ulang di setiap load)
          // dan kini hanya jaring pengaman untuk kelas yang belum terpanen. ?>
    <link rel="stylesheet" href="<?= base_url('assets/css/tailwind-admin.css?v=' . filemtime('assets/css/tailwind-admin.css')) ?>">
    <script defer src="<?= base_url('assets/js/vendor/tailwind-3.4.17.js') ?>"></script>
    <?php // `type="module"` WAJIB, jangan dilepas. Skrip inline biasa dieksekusi
          // saat parsing - sebelum CDN yang `defer` di atas jalan - sehingga
          // `tailwind` masih undefined dan SELURUH config di bawah hilang tanpa
          // suara. CDN lalu berjalan dengan default `darkMode: 'media'`, jadi
          // setiap kelas `dark:*` yang belum terpanen mengikuti preferensi OS,
          // BUKAN tombol tema. Akibat nyatanya: di mode terang pada perangkat
          // ber-OS gelap, `text-gray-900 dark:text-white` tetap putih - teks
          // putih di kartu putih. Logo sidebar, "Portal Klinik PKP", dan tiap
          // judul kartu tidak terbaca. Warna `brand-*` tetap benar sepanjang
          // itu waktu karena datang dari CSS hasil panen, bukan dari CDN, dan
          // itulah yang menyamarkan bug ini sejak `defer` ditambahkan.
          //
          // Skrip `type="module"` ditunda seperti `defer` DAN dieksekusi
          // menurut urutan dokumen, jadi ia jalan sesudah CDN. Diverifikasi:
          // tailwind.config.darkMode terbaca 'class', dan judul kartu
          // rgb(17,24,39) di terang / rgb(255,255,255) di gelap.
          // `defer` pada skrip inline TIDAK berlaku - spesifikasi mengabaikannya. ?>
    <script type="module">
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            primary: '#d6fb00',
                            hover: '#b5d400',
                            light: '#ecffb6',
                            muted: '#8aacb0',
                            dark: '#0a1a1f',
                            card: '#0f2933',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Alpine.js -->
    <script defer src="<?= base_url('assets/js/notifications.js?v=' . filemtime('assets/js/notifications.js')) ?>"></script>
    <script defer src="<?= base_url('assets/js/admin-web-push.js?v=' . filemtime('assets/js/admin-web-push.js')) ?>"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.15.12/dist/cdn.min.js" integrity="sha384-pb6hrQvo4s23cEUFtj0CZkzGE3jyK3pj26RIupXXxhSrrcUA/Cn0lZgcCrGH0t6L" crossorigin="anonymous"></script>
    <!-- Loader progresif dashboard: klik sidebar/link internal = swap #main-content, bukan full reload -->
    <script defer src="<?= base_url('assets/js/admin-progressive.js?v=' . filemtime('assets/js/admin-progressive.js')) ?>"></script>
    <style>
        /* Penanda aktif sidebar. Sejak menu ikut dikirim server tiap pindah
           halaman, kelas Tailwind-nya sudah benar sendiri - aturan ini tinggal
           jaring pengaman untuk `aria-current` yang dirender server. */
        aside a[aria-current="page"] { background: #eff6ff; color: #1d4ed8; }
        .dark aside a[aria-current="page"] { background: rgba(214, 251, 0, .1); color: #d6fb00; }

        /* Daftar pilihan <select> DI DALAM shell admin.
           Kelas Tailwind `bg-transparent` cuma mengatur kotak yang terlihat;
           daftar yang terbuka digambar sistem operasi dan mewarisi warnanya
           sendiri. Di mode gelap hasilnya latar putih dengan teks abu terang -
           pilihan yang tidak sedang disorot praktis tidak terbaca.
           Warna DIPAKSA di sini, pada elemennya maupun pada <option>, karena
           tidak semua peramban mewariskan warna select ke daftarnya. */
        .dark select { background-color: #0f2933; color: #fff; }
        .dark select option { background-color: #0f2933; color: #fff; }
        select option { background-color: #fff; color: #111827; }
        .dark select option:checked,
        .dark select option:hover { background-color: rgba(214, 251, 0, .15); color: #d6fb00; }
    </style>
    <!-- Phosphor Icons - defer: ikon menyusul sepersekian detik, halaman tidak menunggu -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2/src/regular/style.css" integrity="sha384-6p9AefaqUhEVheRlj1mpAkbngHXy9mbYMrIdcIt4Jlc9lOLIablJq3bBsLOjGwZ7" crossorigin="anonymous" media="print" onload="this.media='all'"><noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.1.2/src/regular/style.css" integrity="sha384-6p9AefaqUhEVheRlj1mpAkbngHXy9mbYMrIdcIt4Jlc9lOLIablJq3bBsLOjGwZ7" crossorigin="anonymous"></noscript>
    <style>
        [x-cloak] { display: none !important; }
        /* Kolom nomor urut tabel daftar admin (daftar revisi dinas 23 Sep 2026: "Numbering table").
           Satu aturan untuk semua tabel ber-[data-tabel-admin]; nomor awal datang dari offset
           paginasi yang dicetak sebagai counter-reset di pembungkus [data-tabel-admin] tiap view (reset di
           elemen saudara seperti toolbar TIDAK diwarisi tabel), jadi halaman 2 mulai dari 26, bukan 1.
           Baris kosong ber-colspan tidak dinomori tetapi tetap diberi sel supaya kolomnya lurus. */
        [data-tabel-admin] table > thead > tr::before { content: "No"; display: table-cell; padding: 1rem 0.5rem 1rem 1rem; }
        [data-tabel-admin] table > tbody > tr::before { counter-increment: baris-admin; content: counter(baris-admin); display: table-cell; padding: 1rem 0.5rem 1rem 1rem; vertical-align: top; font-size: 0.75rem; font-weight: 700; color: #6b7280; }
        [data-tabel-admin] table > tbody > tr:has(> td[colspan])::before { counter-increment: none; content: ""; }
        /* Kolom Aksi menempel di kanan wadah gulir (audit UI 2 Okt 2026: di 375 dan 768
           tombol Tinjau/Proses/Simpan baru terlihat setelah menggulir tabel jauh ke samping).
           Pasang kelas `aksi-tetap` pada pembungkus `overflow-x-auto` yang kolom TERAKHIR-nya
           Aksi. Latar sel wajib pekat supaya isi kolom lain tidak tembus saat lewat di bawahnya.
           AWAS: sel sticky membuat stacking context, jadi modal `fixed` di dalam sel Aksi
           terkubur di bawah topbar. Modal di sel itu WAJIB `<template x-teleport="body">`. */
        .aksi-tetap > table > * > tr > :last-child:not([colspan]) { position: sticky; right: 0; background-color: #fff; box-shadow: inset 1px 0 0 rgba(0, 0, 0, .06); }
        .aksi-tetap > table > thead.bg-gray-50 > tr > :last-child { background-color: #f9fafb; }
        .aksi-tetap > table > tbody > tr[class*="hover:bg-gray-50"]:hover > :last-child { background-color: #f9fafb; }
        .dark .aksi-tetap > table > * > tr > :last-child:not([colspan]) { background-color: #0f2933; box-shadow: inset 1px 0 0 rgba(255, 255, 255, .06); }
        .dark .aksi-tetap > table > thead.bg-gray-50 > tr > :last-child { background-color: #0c2129; }
        .dark .aksi-tetap > table > tbody > tr[class*="hover:bg-gray-50"]:hover > :last-child { background-color: #1b3440; }
        /* Tombol admin: SATU gaya untuk ketiga peran (audit UI 2 Okt 2026). Dulu tabel memakai
           campuran tombol berbingkai (Antrean), tautan teks biru/kuning/abu (Pengguna, Asosiasi),
           dan tombol utama lime, biru, teal, atau hijau tergantung layarnya.
             .tombol-utama          aksi utama layar atau formulir (Tambah, Cari, Unggah, Simpan)
             .tombol-aksi           tombol kecil berbingkai di kolom Aksi: <i class="ph ..."></i><span>Label</span>
             .tombol-aksi-bahaya    tambahan untuk aksi yang menghapus atau menonaktifkan
           Label wajib di <span> sesudah ikon: di ponsel aturan .aksi-tetap di bawah menyisakan ikonnya. */
        /* Biru di terang, lime di gelap (pola yang sudah dipakai Rekam Data): lime di latar putih tidak terbaca. */
        .tombol-utama { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; border-radius: .75rem; padding: .625rem 1.25rem; font-size: .875rem; line-height: 1.25rem; font-weight: 700; background-color: #2563eb; color: #fff; transition: background-color .15s; }
        .tombol-utama:hover { background-color: #1d4ed8; }
        .dark .tombol-utama { background-color: #d6fb00; color: #0a1a1f; }
        .dark .tombol-utama:hover { background-color: #b5d400; }
        .tombol-utama:disabled { opacity: .5; cursor: not-allowed; }
        .tombol-aksi { display: inline-flex; align-items: center; justify-content: center; gap: .375rem; border-radius: .5rem; border: 1px solid #e5e7eb; background-color: #f9fafb; padding: .375rem .75rem; font-size: .75rem; line-height: 1rem; font-weight: 700; color: #374151; white-space: nowrap; transition: background-color .15s, color .15s; }
        .tombol-aksi:hover { background-color: #f3f4f6; color: #111827; }
        .dark .tombol-aksi { border-color: rgba(255, 255, 255, .1); background-color: rgba(255, 255, 255, .05); color: #cbd5e1; }
        .dark .tombol-aksi:hover { background-color: rgba(214, 251, 0, .1); color: #d6fb00; }
        .tombol-aksi-bahaya { border-color: #fecaca; background-color: #fef2f2; color: #dc2626; }
        .tombol-aksi-bahaya:hover { background-color: #fee2e2; color: #b91c1c; }
        .dark .tombol-aksi-bahaya { border-color: rgba(248, 113, 113, .3); background-color: rgba(239, 68, 68, .1); color: #f87171; }
        .dark .tombol-aksi-bahaya:hover { background-color: rgba(239, 68, 68, .2); color: #fca5a5; }
        /* Beberapa tombol (atau form berisi tombol) berjajar dalam satu sel: beri jarak tanpa pembungkus. */
        td > :is(.tombol-aksi, form) + :is(.tombol-aksi, form) { margin-left: .375rem; }
        /* Deret kartu ringkasan: di layar lebar semua kartu satu baris, berapa pun jumlahnya
           (style="--jumlah-kartu: N"), supaya tidak ada kartu sendirian di baris kedua
           (audit UI 2 Okt 2026: 5 kartu di grid 4 kolom). Di bawah 1280 tetap kelas Tailwind. */
        @media (min-width: 1280px) { .grid.deret-kartu { grid-template-columns: repeat(var(--jumlah-kartu, 4), minmax(0, 1fr)); } }
        /* Unggah berkas berlabel Indonesia (admin/components/input_berkas.php): input asli transparan
           menutupi kotak, jadi klik, keyboard, dan validasi `required` tetap milik peramban. */
        .input-berkas { position: relative; display: flex; align-items: center; gap: .75rem; width: 100%; min-height: 2.5rem; border: 1px solid #e5e7eb; border-radius: .5rem; padding: .25rem .75rem .25rem .25rem; cursor: pointer; }
        .input-berkas > input[type="file"] { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
        .input-berkas:focus-within { outline: 2px solid #2563eb; outline-offset: 2px; }
        .input-berkas-tombol { display: inline-flex; align-items: center; gap: .375rem; flex-shrink: 0; border-radius: .375rem; background-color: #f3f4f6; padding: .375rem .75rem; font-size: .75rem; font-weight: 700; color: #374151; }
        .input-berkas-nama { min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: .75rem; color: #6b7280; }
        .dark .input-berkas { border-color: rgba(255, 255, 255, .1); }
        .dark .input-berkas:focus-within { outline-color: #d6fb00; }
        .dark .input-berkas-tombol { background-color: rgba(255, 255, 255, .1); color: #e5e7eb; }
        .dark .input-berkas-nama { color: #94a3b8; }
        /* Target sentuh di ponsel (audit UI 2 Okt 2026: chip filter 25px, tombol Cari dan Proses
           28px, tutup modal 18x28, radio 13px). Kontrol utama minimal 40px. Tautan bergaya pil
           (rounded + py-*) ikut; yang masih inline dijadikan inline-flex supaya min-height berlaku.
           Tautan teks biasa dan kartu (tanpa py-*) tidak tersentuh. Tidak dibatasi ke <main>
           karena modal di sel Aksi dipindah ke <body> lewat x-teleport. */
        @media (max-width: 767px) {
            :is(button, select, input:not([type="checkbox"], [type="radio"], [type="hidden"], [type="file"])) { min-height: 40px; }
            button { min-width: 40px; }
            a[class*="rounded"][class*="py-"] { min-height: 40px; }
            a[class*="rounded"][class*="py-"]:not([class*="flex"], [class*="block"], [class*="grid"], .hidden) { display: inline-flex; align-items: center; }
            input[type="checkbox"], input[type="radio"] { width: 20px; height: 20px; }
        }
        /*
         * Main Content Entry Animation.
         *
         * `backwards`, BUKAN `both` - dan satu kata itu yang dulu mematahkan
         * SETIAP modal di seluruh layar admin.
         *
         * `both` = `backwards` + `forwards`. Bagian `forwards` membuat keyframe
         * terakhir MENEMPEL selamanya sesudah animasinya habis - termasuk
         * `transform: translateY(0) scale(1)` dan `filter: blur(0px)`. Keduanya
         * memang tidak mengubah tampilan (identitas), tapi keberadaannya saja
         * sudah cukup: elemen ber-transform/filter menjadi CONTAINING BLOCK
         * untuk `position: fixed` dan membuat STACKING CONTEXT baru.
         *
         * Akibatnya, diukur 4 Agt 2026: modal `fixed inset-0 z-50` tidak lagi
         * menutupi layar melainkan terkurung di dalam `<main>` (x=256 alih-alih
         * 0), dan karena `#main-content` ber-`z-10`, `z-50` modal itu terkubur
         * di bawah topbar (z-40) dan sidebar (z-20). Dilaporkan user sebagai
         * "modalnya tidak muncul karena tertumpuk".
         *
         * `backwards` tetap memberi efek masuk yang sama (keadaan `from`
         * diterapkan sebelum animasi mulai, jadi tidak ada kedipan), tapi
         * melepaskan transform & filter begitu selesai. Keadaan akhirnya memang
         * identik dengan keadaan alami elemen, jadi nol yang hilang.
         */
        #main-content {
            animation: fade-in-blur 0.4s cubic-bezier(0.4, 0, 0.2, 1) backwards;
        }
        /* Di bawah 1024 (batas `desktop` di admin/index.php) sidebar jadi panel
           geser di atas isi, dan yang menggulir adalah kolom kanan seutuhnya,
           bukan <main> saja. Akibatnya footer tidak lagi menempel di dasar layar
           ponsel memakan 48px tiap saat, melainkan ikut di akhir halaman. Topbar
           tetap di atas karena `sticky top-0` di dalam kolom yang menggulir. */
        @media (max-width: 1023px) {
            .admin-kolom { overflow-y: auto !important; }
            .admin-kolom > #main-content { flex: 1 0 auto !important; }
            .admin-sidebar {
                position: fixed !important;
                inset: 0 auto 0 0 !important;
                z-index: 60 !important;
                flex: 0 0 16rem !important;
                width: 16rem !important;
                transform: translateX(0) !important;
            }
        }
        @media (max-width: 767px) {
            .admin-main { padding: 1rem; }
            .admin-topbar { padding-left: 1rem; padding-right: 1rem; }
            /* Kolom Aksi yang menempel menutupi kolom nama di ponsel: tombol berikon cukup
               ikonnya saja. Teks label tetap ada untuk pembaca layar (pola sr-only). */
            .aksi-tetap td:last-child :is(a, button):has(> i) > span {
                position: absolute; width: 1px; height: 1px; overflow: hidden;
                clip: rect(0 0 0 0); white-space: nowrap;
            }
        }

        @keyframes fade-out-blur {
            from { opacity: 1; filter: blur(0px); transform: translateY(0) scale(1); }
            to { opacity: 0; filter: blur(10px); transform: translateY(10px) scale(0.98); }
        }
        @keyframes fade-in-blur {
            from { opacity: 0; filter: blur(10px); transform: translateY(10px) scale(0.98); }
            to { opacity: 1; filter: blur(0px); transform: translateY(0) scale(1); }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        .dark ::-webkit-scrollbar-thumb {
            background: #1e3a45;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        .dark ::-webkit-scrollbar-thumb:hover {
            background: #2a4c5a;
        }
        /* ApexCharts Dark Theme overrides */
        .apexcharts-tooltip {
            background: #0f2933 !important;
            border: 1px solid rgba(255,255,255,0.1) !important;
            color: #fff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5) !important;
        }
        .apexcharts-tooltip-title {
            background: rgba(0,0,0,0.2) !important;
            border-bottom: 1px solid rgba(255,255,255,0.1) !important;
        }
    </style>
</head>
<body class="bg-[#f8fafc] text-gray-800 dark:bg-brand-dark dark:text-gray-200 transition-colors duration-300 flex h-screen overflow-hidden font-sans antialiased selection:bg-brand-primary selection:text-brand-dark">
