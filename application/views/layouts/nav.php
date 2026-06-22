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
                <div class="leading-none hidden sm:block border-l border-white/10 pl-3">
                    <h1 class="text-[10px] font-extrabold text-[#8aacb0] uppercase tracking-wider">Disperakim</h1>
                </div>
            </div>

            <!-- Desktop Navigation (Perfectly Centered) -->
            <div class="hidden lg:flex items-center gap-0.5 text-[12px] font-semibold absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
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

                <!-- Pengembang (SRP2) -->
                <a href="<?= base_url('pengembang') ?>" class="<?= $method == 'pengembang' ? 'text-[#d6fb00]' : 'text-[#8aacb0] hover:text-[#ecffb6]' ?> px-2.5 py-2 transition-colors relative flex items-center gap-1.5">
                    Pengembang
                    <span class="px-1.5 py-0.5 rounded text-[8px] font-bold bg-[#d6fb00]/10 text-[#d6fb00] border border-[#d6fb00]/30">SRP2</span>
                </a>

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
                        
                        <!-- Bank Data -->
                        <div class="relative group/sub">
                            <button class="w-full flex justify-between items-center px-4 py-2 <?= $is_layanan ? 'bg-white/5 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-white/5 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">
                                <div class="flex items-center gap-3">
                                    <div class="w-4 text-center opacity-70"><i class="fa-solid fa-chart-pie"></i></div>
                                    <span>Bank Data Perumahan</span>
                                </div>
                                <i class="fa-solid fa-chevron-right text-[8px] opacity-50 group-hover/sub:opacity-100 transition-opacity"></i>
                            </button>
                            <!-- Sub-dropdown Bank Data -->
                            <div class="absolute top-0 left-[100%] ml-2 hidden group-hover/sub:block bg-[#0a1a1f] border border-white/10 rounded-2xl shadow-2xl p-2 min-w-[150px]">
                                <a href="<?= base_url('Sikumbang') ?>" class="block px-4 py-2 text-[#8aacb0] hover:bg-white/5 hover:text-[#ecffb6] rounded-xl transition-all">Sikumbang</a>
                                <a href="<?= base_url('Sikaper') ?>" class="block px-4 py-2 text-[#8aacb0] hover:bg-white/5 hover:text-[#ecffb6] rounded-xl transition-all">Sikaper</a>
                                <a href="<?= base_url('Sikunang') ?>" class="block px-4 py-2 text-[#8aacb0] hover:bg-white/5 hover:text-[#ecffb6] rounded-xl transition-all">Sikunang</a>
                                <a href="<?= base_url('Siperum') ?>" class="block px-4 py-2 text-[#8aacb0] hover:bg-white/5 hover:text-[#ecffb6] rounded-xl transition-all">Siperum</a>
                            </div>
                        </div>
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

                <!-- Pertanahan (Disabled) -->
                <a href="javascript:void(0)" class="text-[#8aacb0] opacity-40 cursor-not-allowed px-2.5 py-2 flex items-center gap-1 transition-colors" title="Akan Hadir">
                    Pertanahan <i class="fa-solid fa-lock text-[9px]"></i>
                </a>
            </div>

            <!-- Right: Login/User + Mobile Toggle -->
            <div class="flex items-center gap-3">
                <?php if ($this->session->userdata('is_logged')): ?>
                    <!-- Logged-in User -->
                    <div class="hidden lg:flex items-center gap-3">
                        <a href="<?= base_url('akun') ?>" class="flex items-center gap-2.5 px-3 py-1.5 bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl hover:bg-[#d6fb00]/10 hover:border-[#d6fb00]/30 transition-all duration-200">
                            <?php 
                                $avatar_src = $this->session->userdata('avatar');
                                if (empty($avatar_src)) {
                                    $fallback_name = urlencode($this->session->userdata('username') ?: $this->session->userdata('name') ?: 'User');
                                    $avatar_src = "https://ui-avatars.com/api/?name={$fallback_name}&background=d6fb00&color=0a1a1f&bold=true";
                                }
                            ?>
                            <img src="<?= $avatar_src ?>" class="w-7 h-7 rounded-lg object-cover ring-1 ring-[#d6fb00]/20">
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
