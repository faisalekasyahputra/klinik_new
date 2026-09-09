<aside x-cloak x-show="desktop || sidebarOpen"
       class="admin-sidebar bg-white dark:bg-brand-card border-r border-gray-200 dark:border-white/5 flex flex-col transition-all duration-300 relative z-20 shadow-xl shadow-gray-200/50 dark:shadow-none"
       :class="desktop ? (sidebarOpen ? 'w-64' : 'w-20') : ''"
       :style="!desktop ? (sidebarOpen ? 'display:flex !important;position:fixed !important;inset:0 auto 0 0 !important;z-index:60 !important;width:16rem !important;transform:none !important;' : 'display:none !important;') : ''">
    <div class="h-20 flex items-center px-5 border-b border-gray-200 dark:border-white/5" :class="sidebarOpen ? 'justify-start' : 'justify-center'">
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
                    echo $peran ? ucwords(str_replace('_', ' ', $peran)) : 'Super Admin';
                    ?>
                </span>
            </div>
        </a>
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
    <div id="sidebar-nav" class="px-3 py-4 overflow-y-auto overflow-x-hidden flex-1 custom-scrollbar">
        <?php $this->load->view('admin/layouts/sidebar_nav', ['dashboard_menu' => $dashboard_menu ?? []]); ?>
    </div>

    <!-- Link to Main Website (OG Preview Style) -->
    <div class="mt-auto px-4 mb-4" x-show="sidebarOpen" x-transition.opacity.duration.300ms>
        <a href="<?= base_url() ?>" target="_blank" class="group block overflow-hidden rounded-xl bg-white dark:bg-[#0a1a1f] border border-gray-200 dark:border-white/10 shadow-sm hover:shadow-md dark:shadow-none transition-all duration-300 relative">
            <!-- Penanda Beranda, bukan gambar promosi. -->
            <div class="relative flex h-20 w-full items-center justify-center gap-2 border-b border-gray-100 bg-[color:var(--portal-bg-card)] text-[color:var(--portal-text)] dark:border-white/5 dark:bg-[#102c35]">
                <i class="ph ph-house-line text-2xl text-[color:var(--portal-brand)]" aria-hidden="true"></i>
                <span class="text-sm font-black">Beranda</span>
                <div class="absolute right-2 top-2 rounded-md border border-white/20 bg-black/20 p-1 opacity-0 transition-opacity duration-300 group-hover:opacity-100">
                    <i class="ph ph-arrow-square-out text-xs"></i>
                </div>
            </div>
            <!-- Text Area -->
            <div class="p-3">
                <h4 class="text-xs font-bold text-gray-900 dark:text-white line-clamp-1 group-hover:text-blue-600 dark:group-hover:text-brand-primary transition-colors">Beranda</h4>
                <p class="text-[10px] text-gray-500 dark:text-brand-muted line-clamp-2 mt-1 leading-snug">Kembali ke halaman utama Portal Klinik PKP.</p>
                <div class="flex items-center gap-1 mt-2.5 text-[9px] font-semibold text-gray-400 dark:text-brand-muted/70">
                    <i class="ph ph-link text-[10px]"></i>
                    <span class="truncate"><?= str_replace(['http://', 'https://'], '', base_url()) ?></span>
                </div>
            </div>
        </a>
    </div>
</aside>
