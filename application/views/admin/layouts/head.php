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
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
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
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- Phosphor Icons -->
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Main Content Entry Animation */
        #main-content {
            animation: fade-in-blur 0.4s cubic-bezier(0.4, 0, 0.2, 1) both;
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
