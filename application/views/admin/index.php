<?php $this->load->view('admin/layouts/head'); ?>
<?php $this->load->view('components/notification_center'); $this->load->view('components/file_viewer_modal'); ?>

<div x-data="{ sidebarOpen: false, desktop: window.innerWidth >= 768 }"
     x-init="sidebarOpen = desktop"
     @resize.window="sidebarOpen = (desktop && window.innerWidth < 768) ? false : sidebarOpen; desktop = window.innerWidth >= 768"
     @keydown.escape.window="if (!desktop) sidebarOpen = false"
     class="admin-shell flex h-screen w-full bg-[#f8fafc] dark:bg-brand-dark">
    <!-- Sidebar -->
    <?php $this->load->view('admin/layouts/sidebar', [
        'dashboard_home' => $dashboard_home ?? 'akun',
        'dashboard_menu' => $dashboard_menu ?? [],
    ]); ?>
    <button x-cloak x-show="!desktop && sidebarOpen" @click="sidebarOpen = false"
            class="admin-sidebar-backdrop"
            :style="!desktop && sidebarOpen ? 'display:block !important;position:fixed !important;inset:0 !important;z-index:50 !important;background:rgba(10,26,31,.55);' : ''"
            aria-label="Tutup menu navigasi"></button>
    
    <!-- Main Content Wrapper -->
    <div class="min-w-0 flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <!-- Background Ambient & Batik Pattern -->
        <div class="absolute inset-0 pointer-events-none z-0 overflow-hidden hidden dark:block">
            <!-- Glows -->
            <div class="absolute top-[-10%] right-[-5%] w-[40%] h-[40%] rounded-full bg-brand-primary/5 blur-[120px]"></div>
            <div class="absolute bottom-[-10%] left-[-10%] w-[30%] h-[30%] rounded-full bg-blue-500/5 blur-[100px]"></div>
            
            <!-- Batik Kawung Pattern -->
            <div class="absolute inset-0" style="opacity: 0.08; -webkit-mask-image: linear-gradient(to bottom, transparent 0%, black 40%, black 100%); mask-image: linear-gradient(to bottom, transparent 0%, black 40%, black 100%);">
                <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                  <defs>
                    <pattern id="batik-admin" x="0" y="0" width="100" height="100" patternUnits="userSpaceOnUse">
                      <circle cx="0" cy="0" r="50" fill="none" stroke="#00545f" stroke-width="2"/>
                      <circle cx="100" cy="0" r="50" fill="none" stroke="#00545f" stroke-width="2"/>
                      <circle cx="0" cy="100" r="50" fill="none" stroke="#00545f" stroke-width="2"/>
                      <circle cx="100" cy="100" r="50" fill="none" stroke="#00545f" stroke-width="2"/>
                      <line x1="-15" y1="0" x2="15" y2="0" stroke="#00545f" stroke-width="2"/>
                      <line x1="0" y1="-15" x2="0" y2="15" stroke="#00545f" stroke-width="2"/>
                      <circle cx="0" cy="0" r="4.5" fill="#d6fb00"/>
                      <line x1="85" y1="0" x2="115" y2="0" stroke="#00545f" stroke-width="2"/>
                      <line x1="100" y1="-15" x2="100" y2="15" stroke="#00545f" stroke-width="2"/>
                      <circle cx="100" cy="0" r="4.5" fill="#d6fb00"/>
                      <line x1="-15" y1="100" x2="15" y2="100" stroke="#00545f" stroke-width="2"/>
                      <line x1="0" y1="85" x2="0" y2="115" stroke="#00545f" stroke-width="2"/>
                      <circle cx="0" cy="100" r="4.5" fill="#d6fb00"/>
                      <line x1="85" y1="100" x2="115" y2="100" stroke="#00545f" stroke-width="2"/>
                      <line x1="100" y1="85" x2="100" y2="115" stroke="#00545f" stroke-width="2"/>
                      <circle cx="100" cy="100" r="4.5" fill="#d6fb00"/>
                      <polygon points="50,40 60,50 50,60 40,50" fill="none" stroke="#00a3b5" stroke-width="2"/>
                      <circle cx="50" cy="50" r="2.5" fill="#ecffb6"/>
                      <circle cx="50" cy="22" r="2" fill="#00a3b5"/>
                      <circle cx="50" cy="78" r="2" fill="#00a3b5"/>
                      <circle cx="22" cy="50" r="2" fill="#00a3b5"/>
                      <circle cx="78" cy="50" r="2" fill="#00a3b5"/>
                    </pattern>
                  </defs>
                  <rect width="100%" height="100%" fill="url(#batik-admin)" />
                </svg>
            </div>
        </div>

        <!-- Topbar -->
        <?php $this->load->view('admin/layouts/topbar'); ?>
        
        <!-- Main Content Area -->
        <?php /* `relative` TANPA `z-10` — dan hilangnya satu kelas itu yang membuat
                 modal admin bisa tampil sama sekali.

                 `position:relative` + `z-index` bernilai = STACKING CONTEXT. Selama
                 `#main-content` ber-`z-10`, setiap `z-50` di DALAMNYA cuma berlaku
                 relatif terhadap sesamanya di konteks itu — jadi modal `z-50`
                 tetap dicat DI BAWAH topbar (`z-40`) dan sidebar (`z-20`), yang
                 saudara-saudaranya di luar. Dilaporkan user 4 Agt 2026 sebagai
                 "modalnya tidak muncul karena tertumpuk".

                 `relative` dipertahankan (dipakai penempatan di dalamnya); yang
                 dibuang hanya z-index-nya. main tetap tercat di bawah sidebar &
                 topbar lewat urutan dokumen, jadi nol yang berubah secara visual. */ ?>
        <main id="main-content" class="admin-main flex-1 min-h-0 overflow-x-hidden overflow-y-auto p-8 relative custom-scrollbar">
            <!-- Injected Content -->
            <div class="animate-[fadeIn_0.3s_ease-out]">
                <?= isset($content) ? $content : '' ?>
            </div>
        </main>
        
        <!-- Footer (Fixed at bottom) -->
        <?php $this->load->view('admin/layouts/footer'); ?>
    </div>
</div>
