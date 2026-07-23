<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token-name" content="<?= $this->security->get_csrf_token_name(); ?>">
    <meta name="csrf-token-hash" content="<?= $this->security->get_csrf_hash(); ?>">
    <meta name="description" content="Portal layanan informasi perumahan dan kawasan permukiman terpadu untuk masyarakat Jawa Tengah — Disperakim Provinsi Jawa Tengah">
    <title>Klinik PKP — Disperakim Provinsi Jawa Tengah</title>

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Klinik PKP — Portal Perumahan Terpadu Jawa Tengah">
    <meta property="og:description" content="Portal informasi rumah subsidi, data spasial, dan konsultasi permukiman terpadu Jawa Tengah.">
    <meta property="og:image" content="<?= base_url('assets/img/og-cover.jpg') ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:url" content="<?= current_url() ?>">
    <meta property="og:site_name" content="Klinik PKP">
    <meta property="og:locale" content="id_ID">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Klinik PKP — Portal Perumahan Terpadu Jawa Tengah">
    <meta name="twitter:description" content="Portal informasi rumah subsidi, data spasial, dan konsultasi permukiman terpadu Jawa Tengah.">
    <meta name="twitter:image" content="<?= base_url('assets/img/og-cover.jpg') ?>">

    <link rel="icon" href="<?= base_url('assets/img/logo-jateng.png') ?>" type="image/png">
    <link rel="shortcut icon" href="<?= base_url('assets/img/logo-jateng.png') ?>" type="image/png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Merriweather:wght@700;900&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link rel="stylesheet" href="<?= base_url('assets/css/design-system.css?v=' . filemtime('assets/css/design-system.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/tailwind.min.css') ?>">
    <script defer src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <!-- Scripts -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Custom Scrollbar & Global Fixes -->
    <style>
        html, body {
            overflow-x: hidden;
        }

        /* Critical panel CSS: dipakai sebelum stylesheet eksternal selesai dimuat. */
        .theme-light {
            --portal-bg: #f0f6f7;
            --portal-bg-card: #ffffff;
            --portal-border: rgba(0, 80, 95, 0.08);
            --portal-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --portal-skeleton: rgba(10, 26, 31, 0.08);
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
            opacity: 1;
            visibility: visible;
            transition: opacity .18s ease, visibility 0s linear 0s;
        }
        .page-loading-skeleton.is-hidden {
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

        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(10, 26, 31, 0.6);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(214, 251, 0, 0.3);
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(214, 251, 0, 0.6);
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
        // Page transition: hanya untuk navigasi full-page (bukan AJAX tab)
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            // Skip jika bukan link, tab baru, anchor, atau AJAX tab link
            if (!link || !link.href || link.target === '_blank' || link.href.startsWith('#') || link.href.startsWith('javascript:') || link.hostname !== window.location.hostname || link.hasAttribute('data-tab-link') || link.hasAttribute('data-no-page-transition')) return;

            const currentUrl = window.location.href.split('#')[0];
            const linkUrl = link.href.split('#')[0];
            if (currentUrl === linkUrl) return;

            e.preventDefault();
            const wrapper = document.getElementById('page-content-wrapper');
            if (wrapper) wrapper.classList.add('page-exiting');
            setTimeout(() => { window.location.href = link.href; }, 150);
        });

        // BFCache handler
        window.addEventListener('pageshow', (e) => {
            if (e.persisted) {
                const wrapper = document.getElementById('page-content-wrapper');
                if (wrapper) wrapper.classList.remove('page-exiting');
            }
        });
    </script>
