<?php 
  $is_home = (strtolower($this->router->fetch_class()) == 'index' && strtolower($this->router->fetch_method()) == 'index');
?>
<nav class="w-full fixed top-0 left-0 right-0 z-50 transition-all duration-300 <?= $is_home ? 'bg-transparent border-b border-transparent py-2' : 'bg-[#0d2228]/95 backdrop-blur-xl border-b border-[#d6fb00]/20' ?>"
     <?php if ($is_home): ?>
     :class="scrolled ? 'bg-[#0d2228]/95 backdrop-blur-xl border-b border-[#d6fb00]/20 py-0 shadow-lg shadow-black/20' : 'bg-transparent py-2 border-b border-transparent'"
     <?php endif; ?>>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
            
            <!-- Logo -->
            <div class="flex items-center gap-3 group cursor-pointer">
                <a href="<?= base_url() ?>" class="logo-shine px-3.5 py-2 gradient-brand rounded-xl flex items-center gap-2 shadow-lg shadow-[#d6fb00]/15 group-hover:scale-[1.02] transition-transform duration-300">
                    <i class="fa-solid fa-house-laptop text-[#0a1a1f] text-sm"></i>
                    <span class="text-sm font-black tracking-tight text-[#0a1a1f]">
                        Klinik<span class="text-[#0a1a1f]/60">PKP</span>
                    </span>
                </a>
                <div class="leading-none hidden sm:block">
                    <h1 class="text-[10px] font-extrabold text-[#8aacb0] uppercase tracking-wider">Disperakim</h1>
                    <p class="text-[8px] font-bold text-[#5a7a80] uppercase tracking-widest mt-0.5">Prov. Jawa Tengah</p>
                </div>
            </div>

            <!-- Desktop Navigation -->
            <div class="hidden lg:flex items-center gap-0.5 text-[12px] font-semibold">
                <a href="<?= base_url() ?>" class="text-[#d6fb00] px-2.5 py-2 transition-colors">Beranda</a>
                
                <!-- Profil Dropdown -->
                <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <button class="text-[#8aacb0] hover:text-[#ecffb6] px-2.5 py-2 flex items-center gap-1 transition-colors duration-200">
                        Profil <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="open ? 'rotate-180 text-[#d6fb00]' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         class="absolute left-0 mt-0 w-52 bg-[#0f2a30] border border-[#d6fb00]/20 rounded-2xl shadow-2xl shadow-black/30 p-2 backdrop-blur-xl">
                        <a href="<?= base_url('Index/profil') ?>" class="block px-4 py-2.5 text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6] rounded-xl transition-all duration-200">Sejarah & Visi</a>
                        <a href="<?= base_url('Index/tugas_pokok') ?>" class="block px-4 py-2.5 text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6] rounded-xl transition-all duration-200">Tugas Pokok</a>
                        <a href="<?= base_url('Index/struktur') ?>" class="block px-4 py-2.5 text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6] rounded-xl transition-all duration-200">Struktur Organisasi</a>
                    </div>
                </div>

                <!-- Layanan Klinik Dropdown -->
                <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <button class="text-[#8aacb0] hover:text-[#ecffb6] px-2.5 py-2 flex items-center gap-1 transition-colors duration-200">
                        Layanan Klinik <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="open ? 'rotate-180 text-[#d6fb00]' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         class="absolute left-0 mt-0 bg-[#0f2a30] border border-[#d6fb00]/20 rounded-2xl shadow-2xl shadow-black/30 p-2 backdrop-blur-xl" style="min-width: 220px; white-space: nowrap;">
                        <a href="<?= base_url('Sikaper') ?>" class="block px-4 py-2.5 text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6] rounded-xl transition-all duration-200">Sikaper</a>
                        <a href="<?= base_url('Sikunang') ?>" class="block px-4 py-2.5 text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6] rounded-xl transition-all duration-200">Sikunang</a>
                        <a href="<?= base_url('Siperum') ?>" class="block px-4 py-2.5 text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6] rounded-xl transition-all duration-200">Siperum</a>
                        <a href="<?= base_url('Sikumbang') ?>" class="block px-4 py-2.5 text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6] rounded-xl transition-all duration-200">Sikumbang</a>
                    </div>
                </div>

                <!-- Info KPR Dropdown -->
                <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <button class="text-[#8aacb0] hover:text-[#ecffb6] px-2.5 py-2 flex items-center gap-1 transition-colors duration-200">
                        Info KPR <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="open ? 'rotate-180 text-[#d6fb00]' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         class="absolute left-0 mt-0 bg-[#0f2a30] border border-[#d6fb00]/20 rounded-2xl shadow-2xl shadow-black/30 p-2 backdrop-blur-xl" style="min-width: 220px; white-space: nowrap;">
                        <a href="<?= base_url('Index/simulasi_kpr') ?>" class="block px-4 py-2.5 text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6] rounded-xl transition-all duration-200">Simulasi KPR</a>
                        <a href="https://my.pkp.go.id/cekbantuan" target="_blank" class="block px-4 py-2.5 text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6] rounded-xl transition-all duration-200">Cek Bantuan <i class="fa-solid fa-arrow-up-right-from-square text-[8px] ml-1 opacity-50"></i></a>
                    </div>
                </div>

                <!-- Bank Desain Dropdown -->
                <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <button class="text-[#8aacb0] hover:text-[#ecffb6] px-2.5 py-2 flex items-center gap-1 transition-colors duration-200">
                        Bank Desain <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="open ? 'rotate-180 text-[#d6fb00]' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         class="absolute left-0 mt-0 bg-[#0f2a30] border border-[#d6fb00]/20 rounded-2xl shadow-2xl shadow-black/30 p-2 backdrop-blur-xl" style="min-width: 220px; white-space: nowrap;">
                        <a href="#bank-desain" class="block px-4 py-2.5 text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6] rounded-xl transition-all duration-200">Prototype Rumah</a>
                        <a href="<?= base_url('Index/materia') ?>" class="block px-4 py-2.5 text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6] rounded-xl transition-all duration-200">Materia</a>
                        <a href="https://maspetruk.puskimjar.com/" target="_blank" class="block px-4 py-2.5 text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6] rounded-xl transition-all duration-200">Mas Petruk <i class="fa-solid fa-arrow-up-right-from-square text-[8px] ml-1 opacity-50"></i></a>
                    </div>
                </div>

                <!-- Data & Spasial Dropdown -->
                <div class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                    <button class="text-[#8aacb0] hover:text-[#ecffb6] px-2.5 py-2 flex items-center gap-1 transition-colors duration-200">
                        Data & Spasial <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="open ? 'rotate-180 text-[#d6fb00]' : ''"></i>
                    </button>
                    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                         class="absolute left-0 mt-0 bg-[#0f2a30] border border-[#d6fb00]/20 rounded-2xl shadow-2xl shadow-black/30 p-2 backdrop-blur-xl" style="min-width: 220px; white-space: nowrap;">
                        <a href="<?= base_url('Index/sebaran') ?>" class="block px-4 py-2.5 text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6] rounded-xl transition-all duration-200">Sebaran RTLH</a>
                        <a href="<?= base_url('Index/sebaran_rusun') ?>" class="block px-4 py-2.5 text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6] rounded-xl transition-all duration-200">Sebaran Rusun</a>
                        <a href="<?= base_url('Index/profil_kumuh') ?>" class="block px-4 py-2.5 text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6] rounded-xl transition-all duration-200">Profil Kawasan Kumuh</a>
                        <a href="<?= base_url('Index/sebaran_sdgs') ?>" class="block px-4 py-2.5 text-[#8aacb0] hover:bg-[#d6fb00]/8 hover:text-[#ecffb6] rounded-xl transition-all duration-200">Sebaran Bantuan SDGS</a>
                    </div>
                </div>
            </div>

            <!-- Right: Login/User + Mobile Toggle -->
            <div class="flex items-center gap-3">
                <?php if ($this->session->userdata('is_logged')): ?>
                    <!-- Logged-in User -->
                    <div class="hidden lg:flex items-center gap-3">
                        <a href="<?= base_url('Index/pengaturan') ?>" class="flex items-center gap-2.5 px-3 py-1.5 bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl hover:bg-[#d6fb00]/10 hover:border-[#d6fb00]/30 transition-all duration-200">
                            <img src="<?= $this->session->userdata('avatar') ?>" class="w-7 h-7 rounded-lg object-cover ring-1 ring-[#d6fb00]/20">
                            <span class="text-xs font-semibold text-[#ecffb6]"><?= $this->session->userdata('name') ?></span>
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
