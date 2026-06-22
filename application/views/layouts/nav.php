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
                
                <!-- Profil Dropdown -->
                <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
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

                <!-- Layanan Dropdown -->
                <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <a href="<?= $is_home ? 'javascript:void(0)' : '#' ?>" <?= $is_home ? '@click.prevent="document.getElementById(\'layanan-kami\').scrollIntoView({behavior: \'smooth\'})"' : '' ?> class="<?= $is_layanan_dropdown ? 'text-[#d6fb00]' : 'text-[#8aacb0] hover:text-[#ecffb6]' ?> px-2.5 py-2 flex items-center gap-1 transition-colors duration-200">
                        Layanan <?= !$is_home ? '<i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="open ? \'rotate-180 text-[#d6fb00]\' : \'\'"></i>' : '' ?>
                    </a>
                    <?php if (!$is_home): ?>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         class="absolute left-0 mt-0 bg-[#0a1a1f] border border-white/10 rounded-2xl shadow-2xl shadow-black/30 p-2 backdrop-blur-xl" style="min-width: 220px; white-space: nowrap;">
                        <a href="<?= base_url('umum') ?>" class="block px-4 py-2.5 <?= $method == 'umum' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">Masyarakat Umum</a>
                        <a href="<?= base_url('pengembang') ?>" class="block px-4 py-2.5 <?= $method == 'pengembang' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">Pengembang</a>
                        <a href="<?= base_url('kemitraan') ?>" class="block px-4 py-2.5 <?= $method == 'kemitraan' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">KKN & Magang</a>
                        <a href="<?= base_url('listkabupaten') ?>" class="block px-4 py-2.5 <?= $method == 'listkabupaten' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">Kabupaten / Kota</a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Bank Data Dropdown -->
                <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <button class="<?= $is_layanan ? 'text-[#d6fb00]' : 'text-[#8aacb0] hover:text-[#ecffb6]' ?> px-2.5 py-2 flex items-center gap-1 transition-colors duration-200">
                        Bank Data <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="open ? 'rotate-180 text-[#d6fb00]' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         class="absolute left-0 mt-0 bg-[#0a1a1f] border border-white/10 rounded-2xl shadow-2xl shadow-black/30 p-2 backdrop-blur-xl" style="min-width: 220px; white-space: nowrap;">
                        <a href="<?= base_url('Sikaper') ?>" class="block px-4 py-2.5 <?= $class == 'sikaper' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">Sikaper</a>
                        <a href="<?= base_url('Sikunang') ?>" class="block px-4 py-2.5 <?= $class == 'sikunang' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">Sikunang</a>
                        <a href="<?= base_url('Siperum') ?>" class="block px-4 py-2.5 <?= $class == 'siperum' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">Siperum</a>
                        <a href="<?= base_url('Sikumbang') ?>" class="block px-4 py-2.5 <?= $class == 'sikumbang' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">Sikumbang</a>
                    </div>
                </div>

                <!-- Info KPR Dropdown -->
                <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <button class="<?= $is_info_kpr ? 'text-[#d6fb00]' : 'text-[#8aacb0] hover:text-[#ecffb6]' ?> px-2.5 py-2 flex items-center gap-1 transition-colors duration-200">
                        Info KPR <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="open ? 'rotate-180 text-[#d6fb00]' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         class="absolute left-0 mt-0 bg-[#0a1a1f] border border-white/10 rounded-2xl shadow-2xl shadow-black/30 p-2 backdrop-blur-xl" style="min-width: 220px; white-space: nowrap;">
                        <a href="<?= base_url('simulasi_kpr') ?>" class="block px-4 py-2.5 <?= $method == 'simulasi_kpr' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">Simulasi KPR</a>
                        <a href="https://my.pkp.go.id/cekbantuan" target="_blank" class="block px-4 py-2.5 text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6] rounded-xl transition-all duration-200">Cek Bantuan <i class="fa-solid fa-arrow-up-right-from-square text-[8px] ml-1 opacity-50"></i></a>
                    </div>
                </div>

                <!-- Bank Desain Dropdown -->
                <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <button class="<?= $is_bank_desain ? 'text-[#d6fb00]' : 'text-[#8aacb0] hover:text-[#ecffb6]' ?> px-2.5 py-2 flex items-center gap-1 transition-colors duration-200">
                        Bank Desain <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="open ? 'rotate-180 text-[#d6fb00]' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         class="absolute left-0 mt-0 bg-[#0a1a1f] border border-white/10 rounded-2xl shadow-2xl shadow-black/30 p-2 backdrop-blur-xl" style="min-width: 220px; white-space: nowrap;">
                        <a href="<?= $is_home ? 'javascript:void(0)' : base_url() . '#bank-desain' ?>" <?= $is_home ? '@click.prevent="document.getElementById(\'bank-desain\').scrollIntoView({behavior: \'smooth\'}); open = false"' : '' ?> class="block px-4 py-2.5 text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6] rounded-xl transition-all duration-200">Prototype Rumah</a>
                        <a href="<?= base_url('materia') ?>" class="block px-4 py-2.5 <?= $method == 'materia' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">Materia</a>
                        <a href="https://maspetruk.puskimjar.com/" target="_blank" class="block px-4 py-2.5 text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6] rounded-xl transition-all duration-200">Mas Petruk <i class="fa-solid fa-arrow-up-right-from-square text-[8px] ml-1 opacity-50"></i></a>
                    </div>
                </div>

                <!-- Data & Spasial Dropdown -->
                <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <button class="<?= $is_data_spasial ? 'text-[#d6fb00]' : 'text-[#8aacb0] hover:text-[#ecffb6]' ?> px-2.5 py-2 flex items-center gap-1 transition-colors duration-200">
                        Data & Spasial <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="open ? 'rotate-180 text-[#d6fb00]' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         class="absolute left-0 mt-0 bg-[#0a1a1f] border border-white/10 rounded-2xl shadow-2xl shadow-black/30 p-2 backdrop-blur-xl" style="min-width: 220px; white-space: nowrap;">
                        <a href="<?= base_url('sebaran') ?>" class="block px-4 py-2.5 <?= $method == 'sebaran' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">Sebaran RTLH</a>
                        <a href="<?= base_url('sebaran_rusun') ?>" class="block px-4 py-2.5 <?= $method == 'sebaran_rusun' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">Sebaran Rusun</a>
                        <a href="<?= base_url('profil_kumuh') ?>" class="block px-4 py-2.5 <?= $method == 'profil_kumuh' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">Profil Kawasan Kumuh</a>
                        <a href="<?= base_url('sebaran_sdgs') ?>" class="block px-4 py-2.5 <?= $method == 'sebaran_sdgs' ? 'bg-[#d6fb00]/10 text-[#ecffb6]' : 'text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6]' ?> rounded-xl transition-all duration-200">Sebaran Bantuan SDGS</a>
                    </div>
                </div>
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
