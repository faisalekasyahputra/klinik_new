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
    <link rel="stylesheet" href="<?= base_url('assets/css/design-system.css?v=' . filemtime('assets/css/design-system.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/tailwind.min.css') ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.1/css/all.min.css">
    
    <!-- Scripts -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- Custom Scrollbar -->
    <style>
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

        /* Page Transition Blur Fade (Content Only) */
        #page-content-wrapper {
            opacity: 0;
            filter: blur(10px);
            transition: opacity 0.3s ease-out, filter 0.3s ease-out;
        }
        #page-content-wrapper.page-loaded {
            opacity: 1;
            filter: blur(0);
        }
        #page-content-wrapper.page-exiting {
            opacity: 0;
            filter: blur(10px);
            transition: opacity 0.2s ease-in, filter 0.2s ease-in;
        }
    </style>

    <!-- Page Transition Script -->
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            requestAnimationFrame(() => {
                const wrapper = document.getElementById('page-content-wrapper');
                if (wrapper) wrapper.classList.add('page-loaded');
            });
        });

        document.addEventListener('click', (e) => {
            const link = e.target.closest('a');
            
            if (link && link.href && link.target !== '_blank' && !link.href.startsWith('#') && !link.href.startsWith('javascript:') && link.hostname === window.location.hostname) {
                // Cegah transisi jika link hanya anchor link (misal href="http://domain.com/page#section")
                const currentUrl = window.location.href.split('#')[0];
                const linkUrl = link.href.split('#')[0];
                if (currentUrl === linkUrl) return;

                e.preventDefault();
                const wrapper = document.getElementById('page-content-wrapper');
                if (wrapper) {
                    wrapper.classList.remove('page-loaded');
                    wrapper.classList.add('page-exiting');
                }
                setTimeout(() => {
                    window.location.href = link.href;
                }, 200); // Sesuai durasi CSS transition (.page-exiting)
            }
        });

        // BFCache (Back/Forward Cache) handler agar halaman tidak stuck blur saat tombol Back ditekan
        window.addEventListener('pageshow', (e) => {
            if (e.persisted) {
                const wrapper = document.getElementById('page-content-wrapper');
                if (wrapper) {
                    wrapper.classList.remove('page-exiting');
                    wrapper.classList.add('page-loaded');
                }
            }
        });
    </script>
