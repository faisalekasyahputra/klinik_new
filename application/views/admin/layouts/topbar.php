<header class="admin-topbar h-20 bg-white dark:bg-[#0a1a1f] border-b border-gray-200 dark:border-white/5 flex items-center justify-between px-8 z-40 sticky top-0">
    <div class="flex items-center gap-6">
        <button @click="sidebarOpen = !sidebarOpen" :aria-expanded="sidebarOpen.toString()" aria-label="Buka atau tutup menu navigasi" class="w-10 h-10 rounded-xl flex items-center justify-center text-gray-500 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/5 hover:text-gray-900 dark:hover:text-white transition-all">
            <i class="ph ph-list text-2xl"></i>
        </button>
        
    </div>
    
    <div class="flex items-center space-x-2">
        <!-- Theme Toggle -->
        <button @click="darkMode = !darkMode" class="w-10 h-10 rounded-xl flex items-center justify-center text-gray-500 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/5 hover:text-gray-900 dark:hover:text-brand-primary transition-all relative overflow-hidden group">
            <div class="absolute inset-0 bg-brand-primary/10 translate-y-full group-hover:translate-y-0 transition-transform duration-300 rounded-xl"></div>
            <i class="ph ph-sun text-xl relative z-10" x-show="!darkMode"></i>
            <i class="ph ph-moon text-xl relative z-10" x-show="darkMode" style="display: none;"></i>
        </button>
        
        <div class="w-px h-6 bg-gray-200 dark:bg-white/10 mx-2"></div>
        
        <!-- User Menu -->
        <div class="relative" x-data="{ userMenuOpen: false }">
            <div @click="userMenuOpen = !userMenuOpen" @click.away="userMenuOpen = false" class="flex items-center gap-3 pl-2 cursor-pointer group">
                <div class="text-right hidden md:block">
                    <div class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-brand-primary transition-colors"><?= html_escape($this->session->userdata('name') ?: 'Administrator') ?></div>
                    <div class="text-[10px] text-gray-500 dark:text-brand-muted uppercase tracking-wider font-semibold">
                        <?= $this->session->userdata('role') ? ucwords(str_replace('_', ' ', $this->session->userdata('role'))) : 'Super Admin' ?>
                    </div>
                </div>
                <div class="relative">
                    <img src="<?= $this->session->userdata('avatar') ?: avatar_inisial($this->session->userdata('name') ?: 'Admin') ?>" alt="Avatar" class="h-10 w-10 rounded-xl object-cover border-2 border-transparent group-hover:border-brand-primary transition-all shadow-sm">
                    <div class="absolute bottom-[-2px] right-[-2px] w-3.5 h-3.5 bg-green-500 border-2 border-white dark:border-[#0a1a1f] rounded-full"></div>
                </div>
                <i class="ph ph-caret-down text-gray-400 dark:text-brand-muted text-xs transition-transform duration-200" :class="userMenuOpen ? 'rotate-180' : ''"></i>
            </div>
            
            <!-- Dropdown -->
            <div x-show="userMenuOpen" x-transition.opacity.duration.200ms class="absolute right-0 top-full mt-3 w-48 bg-white dark:bg-brand-card border border-gray-100 dark:border-white/10 rounded-xl shadow-lg overflow-hidden z-50">
                <?php
                // Satu halaman profil untuk SEMUA role (akun/profil). Dulu link ini
                // hardcode ke User_Profile yang digate superadmin, jadi role lain
                // diusir ke login (B3); lalu User_Profile sendiri dilebur ke sini (B9).
                ?>
                <div class="p-2 space-y-1">
                    <a href="<?= base_url('akun/profil') ?>" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-white/5 rounded-lg transition-colors">
                        <i class="ph ph-user text-lg"></i> Profil Saya
                    </a>
                    <div class="h-px bg-gray-100 dark:bg-white/10 my-1 mx-2"></div>
                    <a href="<?= base_url('Auth/logout') ?>" class="flex items-center gap-2 px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition-colors">
                        <i class="ph ph-sign-out text-lg"></i> Keluar
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
