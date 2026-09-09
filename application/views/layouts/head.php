<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token-name" content="<?= $this->security->get_csrf_token_name(); ?>">
    <meta name="csrf-token-hash" content="<?= $this->security->get_csrf_hash(); ?>">
    <meta name="description" content="Portal layanan informasi perumahan dan kawasan permukiman terpadu untuk masyarakat Jawa Tengah - Disperakim Provinsi Jawa Tengah">
    <title>Klinik PKP - Disperakim Provinsi Jawa Tengah</title>

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Klinik PKP - Portal Perumahan Terpadu Jawa Tengah">
    <meta property="og:description" content="Portal informasi rumah subsidi, data spasial, dan konsultasi permukiman terpadu Jawa Tengah.">
    <meta property="og:image" content="<?= base_url('assets/img/og-cover.jpg') ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="<?= current_url() ?>">
    <meta property="og:site_name" content="Klinik PKP">
    <meta property="og:locale" content="id_ID">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Klinik PKP - Portal Perumahan Terpadu Jawa Tengah">
    <meta name="twitter:description" content="Portal informasi rumah subsidi, data spasial, dan konsultasi permukiman terpadu Jawa Tengah.">
    <meta name="twitter:image" content="<?= base_url('assets/img/og-cover.jpg') ?>">

    <link rel="icon" href="<?= base_url('assets/img/logo-jateng.png') ?>" type="image/png">
    <link rel="shortcut icon" href="<?= base_url('assets/img/logo-jateng.png') ?>" type="image/png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11.2.10/swiper-bundle.min.css" />
    <link rel="stylesheet" href="<?= base_url('assets/css/design-system.css?v=' . filemtime('assets/css/design-system.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/notifications.css?v=' . filemtime('assets/css/notifications.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/tailwind.min.css') ?>">
    <?php // Hasil panen kelas view portal (varian sm:* dst. yang TIDAK ada di
          // tailwind.min.css beku Jun 2026) - tanpa ini first paint telanjang
          // sampai CDN runtime selesai dan halaman "bergerak". Regenerasi:
          // php docs/engineering/panen_tailwind.php. CDN di bawah tetap ada
          // sebagai jaring pengaman untuk kelas yang belum terpanen. ?>
    <link rel="stylesheet" href="<?= base_url('assets/css/tailwind-generated.css?v=' . filemtime('assets/css/tailwind-generated.css')) ?>">
    <script defer src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Scripts -->
    <script defer src="<?= base_url('assets/js/notifications.js?v=' . filemtime('assets/js/notifications.js')) ?>"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.15.12/dist/cdn.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Custom Scrollbar & Global Fixes -->
    <style>
        html, body {
            overflow-x: hidden;
        }

        /* Portal punya DUA permukaan gulir dengan kebutuhan berlawanan, dan di
           situlah scrollbar lama salah: cangkang halaman gelap, sementara panel
           isinya selalu `.theme-light`. Satu nilai mutlak tidak mungkin benar di
           keduanya - yang cocok di cangkang jadi zaitun di atas putih. Default
           di :root untuk cangkang gelap, ditimpa di dalam panel terang. */
        :root {
            --portal-scroll-thumb: rgba(214, 251, 0, 0.28);
            --portal-scroll-thumb-hover: rgba(214, 251, 0, 0.55);
        }

        /* Critical panel CSS: dipakai sebelum stylesheet eksternal selesai dimuat. */
        .theme-light {
            --portal-bg: #f0f6f7;
            --portal-bg-card: #ffffff;
            --portal-border: rgba(0, 80, 95, 0.08);
            --portal-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --portal-skeleton: rgba(10, 26, 31, 0.08);
            --portal-scroll-thumb: rgba(15, 42, 48, 0.22);
            --portal-scroll-thumb-hover: rgba(15, 42, 48, 0.40);
        }
        .portal-panel {
            background: var(--portal-bg, #f0f6f7);
            border: 1px solid var(--portal-border, rgba(0, 80, 95, 0.08));
            border-top: none;
            border-radius: 0.625rem;
            padding: clamp(1rem, 2.5vw, 1.75rem);
            box-shadow: var(--portal-shadow, 0 1px 2px rgba(0, 0, 0, 0.05));
            position: relative;
            z-index: 1;
            min-height: 0;
            overflow: hidden;
        }
        .page-loading-skeleton {
            position: absolute;
            inset: 0;
            z-index: 20;
            padding: 1rem;
            background: var(--portal-bg, #f0f6f7);
            /* Mulai TERSEMBUNYI dan baru muncul setelah 250ms (CSS murni).
               Konten aslinya sudah dirender server di bawah overlay ini, jadi
               pada load cepat skeleton dulu justru MENCIPTAKAN kedipan yang
               seharusnya ia tutupi: skeleton -> fade -> konten, setiap refresh.
               Kalau DOMContentLoaded menang melawan 250ms, .is-hidden membatalkan
               animasinya dan skeleton tidak pernah terlihat sama sekali. */
            opacity: 0;
            visibility: hidden;
            transition: opacity .18s ease;
            animation: skeleton-appear 0s linear .25s forwards;
        }
        @keyframes skeleton-appear {
            to { opacity: 1; visibility: visible; }
        }
        .page-loading-skeleton.is-hidden {
            animation: none;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity .18s ease, visibility 0s linear .18s;
        }
        .page-loading-skeleton .skeleton {
            background: var(--portal-skeleton, rgba(10, 26, 31, 0.08));
        }

        @keyframes shimmer {
            100% {
                transform: translateX(100%);
            }
        }

        /* Scrollbar mengikuti tema, dan tracknya TIDAK diberi warna.
           Versi lama memakai nilai tema gelap secara mutlak: track
           rgba(10,26,31,.6) menjadi batang gelap melintang di bawah tabel
           bertema terang, dan thumb hijau neon 30% terbaca zaitun. Yang paling
           membuatnya tampak "terlalu lebar" justru tracknya, bukan ukurannya -
           track transparan menghapus kesan itu tanpa mengurangi area seret. */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: var(--portal-scroll-thumb, rgba(15, 42, 48, 0.22));
            border-radius: 99px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--portal-scroll-thumb-hover, rgba(15, 42, 48, 0.40));
        }
        /* Firefox tidak mengenal ::-webkit-*; tanpa dua baris ini ia memakai
           scrollbar bawaan OS yang lebih tebal lagi. */
        * {
            scrollbar-width: thin;
            scrollbar-color: var(--portal-scroll-thumb, rgba(15, 42, 48, 0.22)) transparent;
        }

        /* Page Transition (content reveal on load) */
        #page-content-wrapper {
            opacity: 1;
        }
        #page-content-wrapper.page-exiting {
            opacity: 0;
            transition: opacity 0.15s ease-in;
        }
    </style>

    <script>
        // Interceptor fade lama (preventDefault + tunda 150ms + navigasi penuh)
        // DIHAPUS: navigasi internal kini ditangani loader progresif global di
        // footer.php - link portal di-fetch dan di-swap di panel, sisanya jatuh
        // ke navigasi penuh biasa tanpa tunda buatan.

        // BFCache handler
        window.addEventListener('pageshow', (e) => {
            if (e.persisted) {
                const wrapper = document.getElementById('page-content-wrapper');
                if (wrapper) wrapper.classList.remove('page-exiting');
            }
        });
    </script>
