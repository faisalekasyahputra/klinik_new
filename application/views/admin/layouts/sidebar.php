<aside x-cloak x-show="desktop || sidebarOpen"
       class="admin-sidebar bg-white dark:bg-brand-card border-r border-gray-200 dark:border-white/5 flex flex-col transition-all duration-300 relative z-20 shadow-xl shadow-gray-200/50 dark:shadow-none"
       :class="desktop ? (sidebarOpen ? 'w-64' : 'w-20') : ''"
       :style="!desktop ? (sidebarOpen ? 'display:flex !important;position:fixed !important;inset:0 auto 0 0 !important;z-index:60 !important;width:16rem !important;transform:none !important;' : 'display:none !important;') : ''">
    <div class="h-20 flex items-center px-5 border-b border-gray-200 dark:border-white/5" :class="sidebarOpen ? 'justify-start' : 'justify-center'">
        <a href="<?= base_url('Admin_Dashboard') ?>" class="flex items-center gap-3 group">
            <div class="w-10 h-10 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
                <img src="<?= base_url('assets/img/logo-jateng.png') ?>" alt="Logo Jateng" class="h-8 w-auto object-contain drop-shadow-sm">
            </div>
            <div class="flex flex-col transition-opacity duration-200 whitespace-nowrap overflow-hidden" 
                 x-show="sidebarOpen">
                <span class="text-lg font-black tracking-tight text-gray-900 dark:text-white leading-none mb-0.5">
                    Klinik<span class="text-blue-600 dark:text-brand-primary">PKP</span>
                </span>
                <span class="text-[10px] font-bold text-gray-500 dark:text-brand-muted uppercase tracking-wider">
                    <?= $this->session->userdata('role') ? ucwords(str_replace('_', ' ', $this->session->userdata('role'))) : 'Super Admin' ?>
                </span>
            </div>
        </a>
    </div>
    
    <div class="px-3 py-4 overflow-y-auto overflow-x-hidden flex-1 custom-scrollbar">
        <?php
        // Menu dibangun dari application/config/dashboard_modules.php (lihat
        // MY_Controller::dashboard_menu()) — SATU sumber kebenaran untuk semua
        // role, bukan lagi hardcode per varian dashboard. Registry cuma
        // mengatur tampilan; otorisasi tetap di constructor controller tujuan.
        // Active-state sudah dihitung di dashboard_menu() (match terpanjang).
        ?>
        <?php foreach (($dashboard_menu ?? []) as $group_label => $menu_items): ?>
        <div class="text-[10px] font-bold text-gray-400 dark:text-brand-muted/70 uppercase tracking-wider mb-2 ml-2 mt-6 first:mt-0 transition-all duration-200 whitespace-nowrap overflow-hidden" x-show="sidebarOpen"><?= htmlspecialchars($group_label) ?></div>
        <nav class="space-y-1 relative">
            <?php foreach ($menu_items as $item): ?>
            <a href="<?= base_url($item['url']) ?>" class="relative flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-all duration-200 <?= !empty($item['active']) ? 'bg-blue-50 text-blue-700 dark:bg-brand-primary/10 dark:text-brand-primary shadow-sm dark:shadow-none' : 'hover:bg-gray-100 dark:hover:bg-white/5 text-gray-600 dark:text-brand-muted hover:text-gray-900 dark:hover:text-brand-light' ?>" :class="!sidebarOpen ? 'justify-center' : ''" title="<?= htmlspecialchars($item['label']) ?>">
                <i class="ph <?= htmlspecialchars($item['icon']) ?> text-lg shrink-0" :class="sidebarOpen ? 'mr-3' : 'mr-0'"></i>
                <span x-show="sidebarOpen" class="whitespace-nowrap overflow-hidden flex-1"><?= htmlspecialchars($item['label']) ?></span>
                <?php if (!empty($item['badge'])): ?>
                <span x-show="sidebarOpen" class="flex h-5 w-5 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-500/20 dark:text-red-400 text-[10px] font-bold shrink-0"><?= (int) $item['badge'] ?></span>
                <span x-show="!sidebarOpen" class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-white text-[9px] font-bold border-[1.5px] border-white dark:border-brand-card shadow-sm z-10"><?= (int) $item['badge'] ?></span>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </nav>
        <?php endforeach; ?>
    </div>

    <!-- Link to Main Website (OG Preview Style) -->
    <div class="mt-auto px-4 mb-4" x-show="sidebarOpen" x-transition.opacity.duration.300ms>
        <a href="<?= base_url() ?>" target="_blank" class="group block overflow-hidden rounded-xl bg-white dark:bg-[#0a1a1f] border border-gray-200 dark:border-white/10 shadow-sm hover:shadow-md dark:shadow-none transition-all duration-300 relative">
            <!-- Image Area -->
            <div class="h-20 w-full overflow-hidden relative border-b border-gray-100 dark:border-white/5">
                <img src="<?= base_url('assets/img/og-cover.jpg') ?>" alt="Klinik PKP Preview" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                <div class="absolute top-2 right-2 bg-black/30 backdrop-blur-md rounded-md p-1 opacity-0 group-hover:opacity-100 transition-opacity duration-300 border border-white/20">
                    <i class="ph ph-arrow-square-out text-white text-xs"></i>
                </div>
                <!-- Mini Logo Overlay -->
                <div class="absolute bottom-2 left-3">
                    <img src="<?= base_url('assets/img/logo-jateng.png') ?>" class="h-5 w-auto drop-shadow-lg" alt="Logo">
                </div>
            </div>
            <!-- Text Area -->
            <div class="p-3">
                <h4 class="text-xs font-bold text-gray-900 dark:text-white line-clamp-1 group-hover:text-blue-600 dark:group-hover:text-brand-primary transition-colors">Portal Klinik PKP</h4>
                <p class="text-[10px] text-gray-500 dark:text-brand-muted line-clamp-2 mt-1 leading-snug">Layanan informasi perumahan & kawasan permukiman terpadu Jawa Tengah.</p>
                <div class="flex items-center gap-1 mt-2.5 text-[9px] font-semibold text-gray-400 dark:text-brand-muted/70">
                    <i class="ph ph-link text-[10px]"></i>
                    <span class="truncate"><?= str_replace(['http://', 'https://'], '', base_url()) ?></span>
                </div>
            </div>
        </a>
    </div>
</aside>
