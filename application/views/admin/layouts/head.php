<!DOCTYPE html>
<html lang="id" x-data="{ darkMode: localStorage.getItem('theme') !== 'light' }" x-init="$watch('darkMode', val => localStorage.setItem('theme', val ? 'dark' : 'light'))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Prevent FOUC (Flash of Unstyled Content) for Dark Mode -->
    <script>
        if (localStorage.getItem('theme') !== 'light') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <?php $roleUser = $this->session->userdata('role') ? ucwords(str_replace('_', ' ', $this->session->userdata('role'))) : 'Super Admin'; ?>
    <title><?= isset($title) ? $title . ' - ' : '' ?><?= $roleUser ?> | Klinik PKP</title>
    
    <!-- Favicon -->
    <link rel="icon" href="<?= base_url('assets/img/logo-jateng.png') ?>" type="image/png">
    <link rel="shortcut icon" href="<?= base_url('assets/img/logo-jateng.png') ?>" type="image/png">
    <link rel="stylesheet" href="<?= base_url('assets/css/notifications.css?v=' . filemtime('assets/css/notifications.css')) ?>">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <?php // Hasil panen kelas view admin — first paint bergaya penuh tanpa
          // menunggu CDN. Regenerasi: php docs/engineering/panen_tailwind.php admin
          // CDN di bawah DIUBAH ke defer (dulu blocking: layar putih sampai
          // ~110KB JS termuat & seluruh CSS di-generate ulang di setiap load)
          // dan kini hanya jaring pengaman untuk kelas yang belum terpanen. ?>
    <link rel="stylesheet" href="<?= base_url('assets/css/tailwind-admin.css?v=' . filemtime('assets/css/tailwind-admin.css')) ?>">
    <script defer src="https://cdn.tailwindcss.com"></script>
    <?php // `type="module"` WAJIB, jangan dilepas. Skrip inline biasa dieksekusi
          // saat parsing — sebelum CDN yang `defer` di atas jalan — sehingga
          // `tailwind` masih undefined dan SELURUH config di bawah hilang tanpa
          // suara. CDN lalu berjalan dengan default `darkMode: 'media'`, jadi
          // setiap kelas `dark:*` yang belum terpanen mengikuti preferensi OS,
          // BUKAN tombol tema. Akibat nyatanya: di mode terang pada perangkat
          // ber-OS gelap, `text-gray-900 dark:text-white` tetap putih — teks
          // putih di kartu putih. Logo sidebar, "Portal Klinik PKP", dan tiap
          // judul kartu tidak terbaca. Warna `brand-*` tetap benar sepanjang
          // itu waktu karena datang dari CSS hasil panen, bukan dari CDN, dan
          // itulah yang menyamarkan bug ini sejak `defer` ditambahkan.
          //
          // Skrip `type="module"` ditunda seperti `defer` DAN dieksekusi
          // menurut urutan dokumen, jadi ia jalan sesudah CDN. Diverifikasi:
          // tailwind.config.darkMode terbaca 'class', dan judul kartu
          // rgb(17,24,39) di terang / rgb(255,255,255) di gelap.
          // `defer` pada skrip inline TIDAK berlaku — spesifikasi mengabaikannya. ?>
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
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.15.12/dist/cdn.min.js"></script>
    <!-- Loader progresif dashboard: klik sidebar/link internal = swap #main-content, bukan full reload -->
    <script defer src="<?= base_url('assets/js/admin-progressive.js?v=' . filemtime('assets/js/admin-progressive.js')) ?>"></script>
    <style>
        /* Penanda aktif sidebar. Sejak menu ikut dikirim server tiap pindah
           halaman, kelas Tailwind-nya sudah benar sendiri — aturan ini tinggal
           jaring pengaman untuk `aria-current` yang dirender server. */
        aside a[aria-current="page"] { background: #eff6ff; color: #1d4ed8; }
        .dark aside a[aria-current="page"] { background: rgba(214, 251, 0, .1); color: #d6fb00; }

        /* Daftar pilihan <select> DI DALAM shell admin.
           Kelas Tailwind `bg-transparent` cuma mengatur kotak yang terlihat;
           daftar yang terbuka digambar sistem operasi dan mewarisi warnanya
           sendiri. Di mode gelap hasilnya latar putih dengan teks abu terang —
           pilihan yang tidak sedang disorot praktis tidak terbaca.
           Warna DIPAKSA di sini, pada elemennya maupun pada <option>, karena
           tidak semua peramban mewariskan warna select ke daftarnya. */
        .dark select { background-color: #0f2933; color: #fff; }
        .dark select option { background-color: #0f2933; color: #fff; }
        select option { background-color: #fff; color: #111827; }
        .dark select option:checked,
        .dark select option:hover { background-color: rgba(214, 251, 0, .15); color: #d6fb00; }
    </style>
    <!-- Phosphor Icons — defer: ikon menyusul sepersekian detik, halaman tidak menunggu -->
    <script defer src="https://unpkg.com/@phosphor-icons/web@2.1.2"></script>
    <?php // Chart.js DIPINDAH ke satu-satunya view pemakainya (admin/dashboard.php)
          // — dulu ~200KB blocking dimuat di SEMUA halaman admin. ?>
    <style>
        [x-cloak] { display: none !important; }
        /* Main Content Entry Animation */
        #main-content {
            animation: fade-in-blur 0.4s cubic-bezier(0.4, 0, 0.2, 1) both;
        }
        .admin-sidebar-backdrop { display: none; }
        @media (max-width: 767px) {
            .admin-sidebar {
                position: fixed !important;
                inset: 0 auto 0 0 !important;
                z-index: 60 !important;
                flex: 0 0 16rem !important;
                width: 16rem !important;
                transform: translateX(0) !important;
            }
            .admin-sidebar-backdrop {
                display: block !important;
                position: fixed !important;
                inset: 0 !important;
                z-index: 50 !important;
                background: rgba(10, 26, 31, .55);
            }
            .admin-main { padding: 1rem; }
            .admin-topbar { padding-left: 1rem; padding-right: 1rem; }
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
