<?php $this->load->view('admin/layouts/head'); ?>

<div x-data="{ sidebarOpen: true }" class="flex h-screen w-full bg-[#f8fafc] dark:bg-brand-dark">
    <!-- Sidebar -->
    <?php $this->load->view('admin/layouts/sidebar'); ?>
    
    <!-- Main Content Wrapper -->
    <div class="flex-1 flex flex-col h-screen overflow-hidden relative">
        
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
        <main id="main-content" class="flex-1 min-h-0 overflow-x-hidden overflow-y-auto p-8 relative z-10 custom-scrollbar">
            
            <?php if($this->session->flashdata('success')): ?>
                <div x-data="{ show: true }" x-show="show" class="mb-6 p-4 rounded-2xl bg-green-50 text-green-700 border border-green-200 dark:bg-green-500/10 dark:text-green-400 dark:border-green-500/20 flex items-start justify-between gap-3 shadow-sm relative">
                    <div class="flex items-start gap-3">
                        <i class="ph ph-check-circle text-xl mt-0.5"></i>
                        <div>
                            <h4 class="font-bold text-sm">Berhasil!</h4>
                            <p class="text-sm mt-0.5 opacity-90"><?= $this->session->flashdata('success') ?></p>
                        </div>
                    </div>
                    <button @click="show = false" class="text-green-700/50 hover:text-green-700 dark:text-green-400/50 dark:hover:text-green-400 transition-colors">
                        <i class="ph ph-x"></i>
                    </button>
                </div>
            <?php endif; ?>
            
            <?php if($this->session->flashdata('error')): ?>
                <div x-data="{ show: true }" x-show="show" class="mb-6 p-4 rounded-2xl bg-red-50 text-red-700 border border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20 flex items-start justify-between gap-3 shadow-sm relative">
                    <div class="flex items-start gap-3">
                        <i class="ph ph-warning-circle text-xl mt-0.5"></i>
                        <div>
                            <h4 class="font-bold text-sm">Terjadi Kesalahan!</h4>
                            <p class="text-sm mt-0.5 opacity-90"><?= $this->session->flashdata('error') ?></p>
                        </div>
                    </div>
                    <button @click="show = false" class="text-red-700/50 hover:text-red-700 dark:text-red-400/50 dark:hover:text-red-400 transition-colors">
                        <i class="ph ph-x"></i>
                    </button>
                </div>
            <?php endif; ?>
            
            <!-- Injected Content -->
            <div class="animate-[fadeIn_0.3s_ease-out]">
                <?= isset($content) ? $content : '' ?>
            </div>
        </main>
        
        <!-- Footer (Fixed at bottom) -->
        <?php $this->load->view('admin/layouts/footer'); ?>
    </div>
</div>
