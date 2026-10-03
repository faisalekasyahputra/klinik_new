<aside x-cloak x-show="desktop || sidebarOpen"
       class="admin-sidebar bg-white dark:bg-brand-card border-r border-gray-200 dark:border-white/5 flex flex-col transition-all duration-300 relative z-20 shadow-xl shadow-gray-200/50 dark:shadow-none"
       :class="desktop ? (sidebarOpen ? 'w-64' : 'w-20') : ''"
       :style="!desktop ? (sidebarOpen ? 'display:flex !important;position:fixed !important;inset:0 auto 0 0 !important;z-index:60 !important;width:16rem !important;transform:none !important;' : 'display:none !important;') : ''">
    <div class="h-16 flex items-center px-5 border-b border-gray-200 dark:border-white/5" :class="sidebarOpen ? 'justify-start' : 'justify-center'">
        <a href="<?= base_url($dashboard_home ?? 'akun') ?>" class="flex items-center gap-3 group">
            <div class="w-10 h-10 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                <img src="<?= base_url('assets/img/logo-jateng.png') ?>" alt="Logo Jateng" class="h-8 w-auto object-contain drop-shadow-sm">
            </div>
            <div class="flex flex-col transition-opacity duration-200 whitespace-nowrap overflow-hidden" 
                 x-show="sidebarOpen">
                <span class="text-lg font-black tracking-tight text-gray-900 dark:text-white leading-none mb-0.5">
                    Klinik<span class="text-blue-600 dark:text-brand-primary">PKP</span>
                </span>
                <span class="text-[10px] font-bold text-gray-500 dark:text-brand-muted uppercase tracking-wider">
                    <?php
                    // Sama seperti admin/layouts/topbar.php - lihat komentar
                    // lengkap di sana. Sejak role 'universitas' berdiri
                    // sendiri (22 Agt 2026), ucwords() generik di bawah
                    // sudah cukup - tidak perlu kasus khusus lagi.
                    $peran = $this->session->userdata('role');
                    echo $peran ? ucwords(str_replace('_', ' ', $peran)) : 'Belum Memilih Peran';
                    ?>
                </span>
            </div>
        </a>
        <?php // Panel geser (< 1024) butuh jalan keluar selain klik latar dan Esc. ?>
        <button type="button" x-show="!desktop" @click="sidebarOpen = false" aria-label="Tutup menu navigasi"
                class="ml-auto w-10 h-10 shrink-0 rounded-xl flex items-center justify-center text-gray-500 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/5 hover:text-gray-900 dark:hover:text-white transition-all">
            <i class="ph ph-x text-xl"></i>
        </button>
    </div>
    
    <?php
    /* BUTIR 14 PUTARAN 2 - jalan pulang ke beranda.
       Sebelum ini, satu-satunya cara keluar dari dashboard adalah KELUAR AKUN.
       Logo di atas menuju dashboard, bukan beranda, jadi orang yang ingin
       kembali ke situs publik benar-benar mentok. Ditaruh paling atas karena
       di situlah orang mencarinya, dan tetap terbaca saat sidebar menyempit
       (ikonnya sendiri sudah bermakna, teksnya menyusul saat melebar). */
    ?>
    <a href="<?= base_url() ?>"
       class="mx-3 mt-3 flex items-center gap-3 rounded-xl border border-gray-200 dark:border-white/10 px-3 py-2 text-sm font-bold text-gray-700 dark:text-brand-muted hover:bg-gray-50 dark:hover:bg-white/5 transition-colors"
       :class="sidebarOpen ? '' : 'justify-center'">
        <i class="ph ph-arrow-u-up-left text-lg shrink-0"></i>
        <span x-show="sidebarOpen" class="whitespace-nowrap">Kembali ke beranda</span>
    </a>

    <?php // `id` dipakai loader progresif untuk MENGGANTI seluruh isi menu tiap
          // pindah halaman. Sebelumnya loader cuma menempel aria-current lewat
          // JS, sementara sorotan dan sub-menu dirender PHP - dua implementasi
          // untuk satu aturan, dan hasilnya dua item menyala bersamaan sambil
          // sub-menu cabang lama tetap terbuka. Sekarang aturannya tetap satu:
          // dashboard_menu() memutuskan, server mengirim, JS hanya menukar. ?>
    <?php // Pindah halaman lewat loader progresif tidak memuat ulang shell, jadi
          // panel geser harus ditutup sendiri begitu sebuah tautan diklik. ?>
    <div id="sidebar-nav" @click="if (!desktop && $event.target.closest('a')) sidebarOpen = false" class="px-3 py-4 overflow-y-auto overflow-x-hidden flex-1 custom-scrollbar">
        <?php $this->load->view('admin/layouts/sidebar_nav', ['dashboard_menu' => $dashboard_menu ?? []]); ?>
    </div>

    <?php /* Kartu "Beranda" di dasar sidebar dicabut di semua ukuran (audit UI 2 Okt 2026): di
             1440x900 ia menutupi menu Manajemen (Akses Staf, Jejak Audit), dan tautan
             "Kembali ke beranda" di atas sudah menuju tempat yang sama. */ ?>
</aside>
