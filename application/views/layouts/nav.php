<?php 
  $class = strtolower($this->router->fetch_class());
  $method = strtolower($this->router->fetch_method());
  
  $is_home = ($class == 'index' && $method == 'index');
  $is_profil = in_array($method, ['profil', 'tugas_pokok', 'struktur']);
  $is_layanan_dropdown = in_array($method, ['umum', 'pengembang', 'kemitraan', 'listkabupaten']);
  $is_layanan = in_array($class, ['sikaper', 'sikunang', 'siperum', 'sikumbang']);
  $is_info_kpr = in_array($method, ['simulasi_kpr']);
  $is_bank_desain = in_array($method, ['materia']);
  $is_data_spasial = in_array($method, ['sebaran', 'sebaran_rusun', 'profil_kumuh', 'sebaran_sdgs']);
  $is_pertanahan = in_array($method, ['info_tanah', 'sertifikasi', 'sengketa', 'bank_tanah']);
?>
<nav class="w-full fixed top-0 left-0 right-0 z-50 transition-all duration-300 <?= $is_home ? 'py-2' : '' ?>"
     <?php if ($is_home): ?>
     :class="scrolled ? 'py-0' : 'py-2'"
     <?php endif; ?>>
     
    <!-- Background Layer with Extended Mask Fade Out (Pure Blur + Batik) -->
    <div class="absolute inset-x-0 top-0 -bottom-12 pointer-events-none transition-all duration-300 <?= $is_home ? '' : 'backdrop-blur-xl' ?>"
         style="-webkit-mask-image: linear-gradient(to bottom, black 60%, transparent 100%); mask-image: linear-gradient(to bottom, black 60%, transparent 100%);"
         <?php if ($is_home): ?>
         :class="scrolled ? 'backdrop-blur-xl' : ''"
         <?php endif; ?>>
         
        <!-- No Batik Overlay Here -->
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="flex items-center justify-between h-20">
            
            <!-- Logo -->
            <div class="flex items-center gap-3 group cursor-pointer">
                <a href="<?= base_url() ?>" class="flex items-center gap-2.5 group-hover:scale-[1.02] transition-transform duration-300">
                    <img src="<?= base_url('assets/img/logo-jateng.png') ?>" alt="Logo Jawa Tengah" class="h-8 w-auto object-contain drop-shadow-md">
                    <div class="flex flex-col">
                        <span class="text-lg font-black tracking-tight text-white leading-none mb-0.5">
                            Klinik<span class="text-[#d6fb00]">PKP</span>
                        </span>
                        <span class="text-[9px] font-bold text-[#8aacb0] tracking-wider uppercase">Provinsi Jawa Tengah</span>
                    </div>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden lg:flex flex-1 justify-center items-center gap-0.5 text-[12px] font-semibold px-4">
                <a href="<?= base_url() ?>" class="<?= $is_home ? 'text-[#d6fb00]' : 'text-[#8aacb0] hover:text-[#ecffb6]' ?> px-2.5 py-2 transition-colors">Beranda</a>
                
                <!-- Profil Dropdown (Hidden per UI/UX rules, moved to footer later) -->
                <div class="relative hidden" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <button class="<?= $is_profil ? 'text-[#d6fb00]' : 'text-[#8aacb0] hover:text-[#ecffb6]' ?> px-2.5 py-2 flex items-center gap-1 transition-colors duration-200">
                        Profil <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="open ? 'rotate-180 text-[#d6fb00]' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         class="absolute left-0 mt-0 w-52 bg-[#0a1a1f] border border-white/10 rounded-2xl shadow-2xl shadow-black/30 p-2 backdrop-blur-xl">
                        <a href="<?= base_url('profil') ?>" class="block px-4 py-2.5 <?= $method == 'profil' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">Sejarah & Visi</a>
                        <a href="<?= base_url('tugas_pokok') ?>" class="block px-4 py-2.5 <?= $method == 'tugas_pokok' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">Tugas Pokok</a>
                        <a href="<?= base_url('struktur') ?>" class="block px-4 py-2.5 <?= $method == 'struktur' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">Struktur Organisasi</a>
                    </div>
                </div>

                <!-- Perumahan Dropdown -->
                <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <button class="<?= ($is_layanan || $is_info_kpr || $is_bank_desain || $method == 'program') ? 'text-[#d6fb00]' : 'text-[#8aacb0] hover:text-[#ecffb6]' ?> px-2.5 py-2 flex items-center gap-1 transition-colors duration-200">
                        Perumahan <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="open ? 'rotate-180 text-[#d6fb00]' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         class="absolute left-0 mt-0 bg-[#0a1a1f] border border-white/10 rounded-2xl shadow-2xl shadow-black/30 p-2 backdrop-blur-xl" style="min-width: 250px; white-space: nowrap;">
                        
                        <!-- Program Bantuan (Etalase) - Highlighted CTA -->
                        <a href="<?= $is_home ? 'javascript:void(0)' : base_url() . '#etalase-program' ?>" <?= $is_home ? '@click.prevent="document.getElementById(\'etalase-program\').scrollIntoView({behavior: \'smooth\'}); open = false"' : '' ?> class="flex items-start gap-3 px-4 py-3 mb-2 bg-[#d6fb00]/10 hover:bg-[#d6fb00]/20 text-[#ecffb6] border border-[#d6fb00]/20 rounded-xl transition-all duration-200 group">
                            <div class="mt-0.5 text-[#d6fb00] group-hover:scale-110 transition-transform">
                                <i class="fa-solid fa-house-chimney-window"></i>
                            </div>
                            <div class="flex flex-col">
                                <span class="font-bold">Etalase Program</span>
                                <span class="text-[9px] text-[#8aacb0] font-normal leading-tight mt-0.5">Daftar Bantuan & Subsidi</span>
                            </div>
                        </a>

                        <div class="px-3 pb-1 mb-1 border-b border-white/5 text-[9px] font-bold text-[#8aacb0] uppercase tracking-wider">Layanan Lainnya</div>

                        <!-- Info KPR -->
                        <a href="<?= base_url('simulasi_kpr') ?>" class="flex items-center gap-3 px-4 py-2 <?= $method == 'simulasi_kpr' ? 'bg-white/5 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-white/5 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">
                            <div class="w-4 text-center opacity-70"><i class="fa-solid fa-calculator"></i></div>
                            <span>Info KPR & Subsidi</span>
                        </a>

                        <!-- Bank Desain -->
                        <a href="<?= $is_home ? 'javascript:void(0)' : base_url() . '#bank-desain' ?>" <?= $is_home ? '@click.prevent="document.getElementById(\'bank-desain\').scrollIntoView({behavior: \'smooth\'}); open = false"' : '' ?> class="flex items-center gap-3 px-4 py-2 <?= $is_bank_desain ? 'bg-white/5 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-white/5 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">
                            <div class="w-4 text-center opacity-70"><i class="fa-solid fa-pen-ruler"></i></div>
                            <span>Bank Desain (Prototipe)</span>
                        </a>
                        
                        <!-- Menu Bank Data dipindahkan ke luar -->
                    </div>
                </div>

                <!-- Kawasan Dropdown -->
                <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <button class="<?= $is_data_spasial ? 'text-[#d6fb00]' : 'text-[#8aacb0] hover:text-[#ecffb6]' ?> px-2.5 py-2 flex items-center gap-1 transition-colors duration-200">
                        Kawasan <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="open ? 'rotate-180 text-[#d6fb00]' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         class="absolute left-0 mt-0 bg-[#0a1a1f] border border-white/10 rounded-2xl shadow-2xl shadow-black/30 p-2 backdrop-blur-xl" style="min-width: 220px; white-space: nowrap;">
                        <a href="<?= base_url('sebaran') ?>" class="block px-4 py-2.5 <?= $method == 'sebaran' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">Sebaran RTLH</a>
                        <a href="<?= base_url('sebaran_rusun') ?>" class="block px-4 py-2.5 <?= $method == 'sebaran_rusun' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">Sebaran Rusun</a>
                        <a href="<?= base_url('profil_kumuh') ?>" class="block px-4 py-2.5 <?= $method == 'profil_kumuh' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">Profil Kawasan Kumuh</a>
                        <a href="<?= base_url('sebaran_sdgs') ?>" class="block px-4 py-2.5 <?= $method == 'sebaran_sdgs' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">Sebaran Bantuan SDGS</a>
                    </div>
                </div>

                <!-- Pertanahan Dropdown -->
                <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <button class="<?= $is_pertanahan ? 'text-[#d6fb00]' : 'text-[#8aacb0] hover:text-[#ecffb6]' ?> px-2.5 py-2 flex items-center gap-1 transition-colors duration-200">
                        Pertanahan <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="open ? 'rotate-180 text-[#d6fb00]' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         class="absolute left-0 mt-0 bg-[#0a1a1f] border border-white/10 rounded-2xl shadow-2xl shadow-black/30 p-2 backdrop-blur-xl" style="min-width: 230px; white-space: nowrap;">
                        <a href="<?= base_url('info_tanah') ?>" class="block px-4 py-2.5 <?= $method == 'info_tanah' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">Informasi Status Tanah</a>
                        <a href="<?= base_url('sertifikasi') ?>" class="block px-4 py-2.5 <?= $method == 'sertifikasi' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">Sertifikasi Lahan Perumahan</a>
                        <a href="<?= base_url('sengketa') ?>" class="block px-4 py-2.5 <?= $method == 'sengketa' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">Penyelesaian Sengketa</a>
                        <a href="<?= base_url('bank_tanah') ?>" class="block px-4 py-2.5 <?= $method == 'bank_tanah' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">Bank Tanah (Land Bank)</a>
                    </div>
                </div>
                <!-- Pengembang (SRP2) -->
                <a href="<?= base_url('pengembang') ?>" class="<?= $method == 'pengembang' ? 'text-[#d6fb00]' : 'text-[#8aacb0] hover:text-[#ecffb6]' ?> px-2.5 py-2 transition-colors relative flex items-center gap-1.5">
                    Pengembang
                    <span class="px-1.5 py-0.5 rounded text-[8px] font-bold bg-[#d6fb00]/10 text-[#d6fb00] border border-[#d6fb00]/30">SRP2</span>
                </a>
                
                <!-- Bank Data (Statistika) -->
                <a href="<?= base_url('Statistika') ?>" class="<?= $class == 'statistika' ? 'text-[#d6fb00]' : 'text-[#8aacb0] hover:text-[#ecffb6]' ?> px-2.5 py-2 transition-colors relative flex items-center gap-1.5">
                    Bank Data
                </a>
            </div>

            <!-- Right: Login/User + Mobile Toggle -->
            <div class="flex items-center gap-3">
                <?php if ($this->session->userdata('is_logged')): ?>
                    <!-- Logged-in User (Grouped) -->
                    <div class="hidden lg:flex items-center p-1 bg-[#d6fb00]/5 border border-[#d6fb00]/15 rounded-xl backdrop-blur-sm">
                        <?php if ($this->session->userdata('role') === 'admin'): ?>
                            <a href="<?= base_url('Admin_Dashboard') ?>" class="flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-[#d6fb00]/15 text-[#d6fb00] transition-colors">
                                <i class="fa-solid fa-gauge-high text-[11px]"></i>
                                <span class="text-xs font-semibold">Dashboard</span>
                            </a>
                            
                            <!-- Separator -->
                            <div class="w-px h-5 bg-[#d6fb00]/20 mx-1"></div>
                        <?php endif; ?>
                        
                        <a href="<?= base_url('akun') ?>" class="flex items-center gap-2 px-3 py-1.5 rounded-lg hover:bg-[#d6fb00]/15 transition-colors group">
                            <?php 
                                $avatar_src = $this->session->userdata('avatar');
                                if (empty($avatar_src)) {
                                    $fallback_name = urlencode($this->session->userdata('username') ?: $this->session->userdata('name') ?: 'User');
                                    $avatar_src = "https://ui-avatars.com/api/?name={$fallback_name}&background=d6fb00&color=0a1a1f&bold=true";
                                }
                            ?>
                            <img src="<?= $avatar_src ?>" class="w-6 h-6 rounded-md object-cover border border-[#d6fb00]/20 group-hover:border-[#d6fb00]/40 transition-colors">
                            <span class="text-xs font-semibold text-[#ecffb6]"><?= $this->session->userdata('username') ?: $this->session->userdata('name') ?></span>
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Login Button: Primary Lime (dari color scheme) -->
                    <a href="<?= base_url('Auth/login') ?>" class="hidden lg:flex flex-shrink-0 items-center gap-2 btn-primary text-xs px-5 py-2.5 rounded-xl">
                        <i class="fa-solid fa-right-to-bracket text-sm"></i>
                        Masuk
                    </a>
                <?php endif; ?>

                <!-- Mobile Hamburger: Outline style -->
                <button @click="mobileMenu = !mobileMenu" class="lg:hidden w-10 h-10 rounded-xl bg-transparent border-[1.5px] border-[#d6fb00]/30 hover:bg-[#d6fb00]/8 hover:border-[#d6fb00] flex items-center justify-center text-[#8aacb0] hover:text-[#d6fb00] transition-all duration-200">
                    <i class="fa-solid fa-bars text-sm"></i>
                </button>
            </div>
        </div>
    </div>
</nav>

<!-- ============================================================
     MOBILE MENU (Alpine.js) — sibling of <nav>, not nested inside
     #page-content-wrapper, so its z-50 isn't trapped under a lower
     stacking context on non-home pages.
     ============================================================ -->
<div x-show="mobileMenu" x-cloak x-transition.opacity class="lg:hidden fixed inset-0 bg-[#0a1a1f]/85 backdrop-blur-sm z-40" @click="mobileMenu = false"></div>
<div x-show="mobileMenu" x-cloak x-transition:enter="transition transform ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition transform ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
     class="lg:hidden fixed top-0 right-0 h-full w-[300px] bg-[#0d2228] border-l border-[#d6fb00]/20 z-50 overflow-y-auto" x-data="{ openPerumahan: false, openKawasan: false }">
    <div class="p-6 space-y-1">
        <div class="flex items-center justify-between mb-8">
            <span class="text-xs font-bold text-zinc-500 uppercase tracking-widest">Menu</span>
            <button @click="mobileMenu = false" class="w-8 h-8 flex items-center justify-center rounded-lg bg-[#d6fb00]/8 border border-[#d6fb00]/20 text-zinc-400 hover:text-[#d6fb00] hover:bg-[#d6fb00]/10 transition-colors"><i class="fa-solid fa-xmark text-sm"></i></button>
        </div>

        <a href="<?= base_url() ?>" class="block text-white py-3 font-semibold text-sm border-b border-[#d6fb00]/20" @click="mobileMenu = false">Beranda</a>

        <a href="#" class="hidden text-zinc-400 py-3 text-sm border-b border-[#d6fb00]/20">Profil</a>

        <a href="<?= base_url('pengembang') ?>" class="flex items-center gap-2 text-zinc-400 hover:text-white py-3 text-sm border-b border-[#d6fb00]/20" @click="mobileMenu = false">
            Pengembang <span class="px-1.5 py-0.5 rounded text-[8px] font-bold bg-[#d6fb00]/10 text-[#d6fb00] border border-[#d6fb00]/30">SRP2</span>
        </a>

        <!-- Perumahan -->
        <div class="border-b border-[#d6fb00]/20">
            <button @click="openPerumahan = !openPerumahan" class="w-full flex justify-between items-center text-zinc-400 py-3 text-sm hover:text-[#d6fb00] transition-colors">
                <span>Perumahan</span>
                <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="openPerumahan && 'rotate-180 text-[#d6fb00]'"></i>
            </button>
            <div x-show="openPerumahan" x-cloak x-transition class="pl-4 pb-3 space-y-2 mt-1">
                <!-- Etalase Program (Highlighted CTA) -->
                <a href="<?= $is_home ? 'javascript:void(0)' : base_url() . '#etalase-program' ?>" <?= $is_home ? '@click.prevent="document.getElementById(\'etalase-program\').scrollIntoView({behavior: \'smooth\'}); mobileMenu = false"' : '@click="mobileMenu = false"' ?> class="flex items-center gap-2 text-[#d6fb00] text-xs py-2 font-bold bg-[#d6fb00]/10 hover:bg-[#d6fb00]/20 px-3 rounded-lg border border-[#d6fb00]/20 transition-colors">
                    <i class="fa-solid fa-house-chimney-window w-4 text-center"></i> Etalase Program
                </a>
                <a href="<?= base_url('simulasi_kpr') ?>" class="flex items-center gap-2 text-zinc-400 hover:text-white text-xs py-1 mt-2" @click="mobileMenu = false">
                    <i class="fa-solid fa-calculator w-4 text-center opacity-70"></i> Info KPR & Subsidi
                </a>
                <a href="<?= $is_home ? 'javascript:void(0)' : base_url() . '#bank-desain' ?>" <?= $is_home ? '@click.prevent="document.getElementById(\'bank-desain\').scrollIntoView({behavior: \'smooth\'}); mobileMenu = false"' : '@click="mobileMenu = false"' ?> class="flex items-center gap-2 text-zinc-400 hover:text-white text-xs py-1">
                    <i class="fa-solid fa-pen-ruler w-4 text-center opacity-70"></i> Bank Desain
                </a>
                <!-- Menu Bank Data dipindah ke luar -->
            </div>
        </div>

        <!-- Kawasan -->
        <div class="border-b border-[#d6fb00]/20">
            <button @click="openKawasan = !openKawasan" class="w-full flex justify-between items-center text-zinc-400 py-3 text-sm hover:text-[#d6fb00] transition-colors">
                <span>Kawasan</span>
                <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="openKawasan && 'rotate-180 text-[#d6fb00]'"></i>
            </button>
            <div x-show="openKawasan" x-cloak x-transition class="pl-4 pb-3 space-y-2 mt-1">
                <a href="<?= base_url('sebaran') ?>" class="block text-zinc-400 hover:text-white text-xs py-1" @click="mobileMenu = false">Sebaran RTLH</a>
                <a href="<?= base_url('sebaran_rusun') ?>" class="block text-zinc-400 hover:text-white text-xs py-1" @click="mobileMenu = false">Sebaran Rusun</a>
                <a href="<?= base_url('profil_kumuh') ?>" class="block text-zinc-400 hover:text-white text-xs py-1" @click="mobileMenu = false">Profil Kawasan Kumuh</a>
                <a href="<?= base_url('sebaran_sdgs') ?>" class="block text-zinc-400 hover:text-white text-xs py-1" @click="mobileMenu = false">Sebaran Bantuan SDGS</a>
            </div>
        </div>

        <!-- Bank Data (Statistika) -->
        <a href="<?= base_url('Statistika') ?>" class="flex items-center justify-between text-zinc-400 hover:text-white py-3 text-sm border-b border-[#d6fb00]/20 transition-colors" @click="mobileMenu = false">
            <div class="flex items-center gap-2">
                <span>Bank Data</span>
            </div>
            <i class="fa-solid fa-chart-pie text-[#d6fb00] text-[10px]"></i>
        </a>

        <!-- Pertanahan (Disabled) -->
        <a href="#" class="flex items-center gap-2 text-zinc-600 cursor-not-allowed py-3 text-sm border-b border-[#d6fb00]/20" title="Akan Hadir">
            Pertanahan <i class="fa-solid fa-lock text-[9px]"></i>
        </a>

        <div class="pt-6">
            <?php if ($this->session->userdata('is_logged')): ?>
                <?php
                    $avatar_src = $this->session->userdata('avatar');
                    if (empty($avatar_src)) {
                        $fallback_name = urlencode($this->session->userdata('username') ?: $this->session->userdata('name') ?: 'User');
                        $avatar_src = "https://ui-avatars.com/api/?name={$fallback_name}&background=d6fb00&color=0a1a1f&bold=true";
                    }
                ?>
                <div class="flex items-center gap-3 p-3 bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl mb-3">
                    <img src="<?= $avatar_src ?>" class="w-9 h-9 rounded-lg object-cover ring-1 ring-[#d6fb00]/20">
                    <div>
                        <p class="text-[#ecffb6] text-xs font-semibold"><?= $this->session->userdata('username') ?: $this->session->userdata('name') ?></p>
                        <p class="text-[#5a7a80] text-[10px]"><?= $this->session->userdata('email') ?></p>
                    </div>
                </div>
                <a href="#" onClick="logout()" class="block w-full text-center text-[#ff6b6b] text-xs font-semibold py-2.5 border border-[#ff6b6b]/20 rounded-xl hover:bg-[#ff6b6b]/10 transition-all duration-200">Keluar</a>
            <?php else: ?>
                <a href="<?= base_url('Auth/login') ?>" class="flex items-center justify-center gap-2 w-full btn-primary text-xs py-3 rounded-xl">
                    <i class="fa-solid fa-right-to-bracket text-sm"></i>
                    Masuk
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
