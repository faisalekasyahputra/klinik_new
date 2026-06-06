<!-- ============================================================
     MOBILE MENU (Alpine.js)
     ============================================================ -->
<div x-show="mobileMenu" x-cloak x-transition.opacity class="lg:hidden fixed inset-0 bg-[#0a1a1f]/85 backdrop-blur-sm z-40" @click="mobileMenu = false"></div>
<div x-show="mobileMenu" x-cloak x-transition:enter="transition transform ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition transform ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
     class="lg:hidden fixed top-0 right-0 h-full w-[300px] bg-[#0d2228] border-l border-[#d6fb00]/20 z-50 overflow-y-auto" x-data="{ openLayanan: false, openKpr: false, openDesain: false, openBahan: false, openSpasial: false }">
    <div class="p-6 space-y-1">
        <div class="flex items-center justify-between mb-8">
            <span class="text-xs font-bold text-zinc-500 uppercase tracking-widest">Menu</span>
            <button @click="mobileMenu = false" class="w-8 h-8 flex items-center justify-center rounded-lg bg-[#d6fb00]/8 border border-[#d6fb00]/20 text-zinc-400"><i class="fa-solid fa-xmark text-sm"></i></button>
        </div>
        <a href="#" class="block text-white py-3 font-semibold text-sm border-b border-[#d6fb00]/20">Beranda</a>
        <a href="#" class="block text-zinc-400 py-3 text-sm border-b border-[#d6fb00]/20">Profil</a>
        
        <div class="border-b border-[#d6fb00]/20">
            <button @click="openLayanan = !openLayanan" class="w-full flex justify-between items-center text-zinc-400 py-3 text-sm">
                <span>Layanan Klinik</span>
                <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="openLayanan && 'rotate-180 text-[#d6fb00]'"></i>
            </button>
            <div x-show="openLayanan" x-cloak x-transition class="pl-4 pb-3 space-y-2">
                <a href="#" class="block text-zinc-500 hover:text-white text-xs py-1">Sikaper</a>
                <a href="#" class="block text-zinc-500 hover:text-white text-xs py-1">Sikunang</a>
                <a href="#" class="block text-zinc-500 hover:text-white text-xs py-1">Siperum</a>
                <a href="#" class="block text-zinc-500 hover:text-white text-xs py-1">Sikumbang</a>
            </div>
        </div>

        <div class="border-b border-[#d6fb00]/20">
            <button @click="openKpr = !openKpr" class="w-full flex justify-between items-center text-zinc-400 py-3 text-sm">
                <span>Info KPR</span>
                <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="openKpr && 'rotate-180 text-[#d6fb00]'"></i>
            </button>
            <div x-show="openKpr" x-cloak x-transition class="pl-4 pb-3 space-y-2">
                <a href="#" class="block text-zinc-500 hover:text-white text-xs py-1">Simulasi KPR</a>
                <a href="https://my.pkp.go.id/cekbantuan" target="_blank" class="block text-zinc-500 hover:text-white text-xs py-1">Cek Bantuan <i class="fa-solid fa-arrow-up-right-from-square text-[8px] ml-1"></i></a>
            </div>
        </div>

        <div class="border-b border-[#d6fb00]/20">
            <button @click="openDesain = !openDesain" class="w-full flex justify-between items-center text-zinc-400 py-3 text-sm">
                <span>Bank Desain</span>
                <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="openDesain && 'rotate-180 text-[#d6fb00]'"></i>
            </button>
            <div x-show="openDesain" x-cloak x-transition class="pl-4 pb-3 space-y-2">
                <a href="#bank-desain" class="block text-zinc-500 hover:text-white text-xs py-1" @click="mobileMenu = false">Prototype Rumah</a>
                <a href="#" class="block text-zinc-500 hover:text-white text-xs py-1">Materia</a>
                <a href="https://maspetruk.puskimjar.com/" target="_blank" class="block text-zinc-500 hover:text-white text-xs py-1">Mas Petruk <i class="fa-solid fa-arrow-up-right-from-square text-[8px] ml-1"></i></a>
            </div>
        </div>

        <div class="border-b border-[#d6fb00]/20">
            <button @click="openSpasial = !openSpasial" class="w-full flex justify-between items-center text-zinc-400 py-3 text-sm">
                <span>Data & Spasial</span>
                <i class="fa-solid fa-chevron-down text-[10px] transition-transform duration-200" :class="openSpasial && 'rotate-180 text-[#d6fb00]'"></i>
            </button>
            <div x-show="openSpasial" x-cloak x-transition class="pl-4 pb-3 space-y-2">
                <a href="#" class="block text-zinc-500 hover:text-white text-xs py-1">Sebaran RTLH</a>
                <a href="#" class="block text-zinc-500 hover:text-white text-xs py-1">Sebaran Rusun</a>
                <a href="#" class="block text-zinc-500 hover:text-white text-xs py-1">Profil Kawasan Kumuh</a>
                <a href="#" class="block text-zinc-500 hover:text-white text-xs py-1">Sebaran Bantuan SDGS</a>
            </div>
        </div>

        <div class="pt-6">
            <?php if ($this->session->userdata('is_logged')): ?>
                <div class="flex items-center gap-3 p-3 bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl mb-3">
                    <img src="<?= $this->session->userdata('avatar') ?>" class="w-9 h-9 rounded-lg object-cover">
                    <div>
                        <p class="text-white text-xs font-semibold"><?= $this->session->userdata('name') ?></p>
                        <p class="text-zinc-500 text-[10px]"><?= $this->session->userdata('email') ?></p>
                    </div>
                </div>
                <a href="#" onClick="logout()" class="block w-full text-center text-rose-400 text-xs font-semibold py-2.5 border border-rose-500/20 rounded-xl hover:bg-rose-500/10">Keluar</a>
            <?php else: ?>
                <a href="#" onClick="loginGooglePopup()" class="flex items-center justify-center gap-2 w-full bg-[#d6fb00] text-[#0a1a1f] font-bold text-xs py-3 rounded-xl hover:bg-[#ecffb6] transition-all">
                    <svg class="w-4 h-4" viewBox="0 0 24 24"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                    Masuk dengan Google
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ============================================================
     SECTION 1: HERO — Full Viewport Slideshow
     ============================================================ -->
<section class="w-full relative overflow-hidden" style="height: calc(100vh - var(--nav-height))" x-data="heroSlider()">
    
    <!-- Slide 1 -->
    <div class="hero-slide" :class="current === 0 && 'active'">
        <div class="absolute inset-0 gradient-hero z-10"></div>
        <img src="https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1920&q=80" alt="Perumahan Modern">
        <div class="absolute inset-0 z-20 max-w-7xl mx-auto px-6 lg:px-8 flex flex-col justify-center">
            <div class="max-w-2xl animate-fade-in-up">
                <div class="inline-flex items-center gap-2 bg-[#d6fb00]/5 border border-[#d6fb00]/15 backdrop-blur-sm px-4 py-1.5 rounded-full text-[11px] text-[#8aacb0] font-medium mb-6">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Dinas Perumahan Rakyat & Kawasan Permukiman Prov. Jawa Tengah
                </div>
                <h2 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white tracking-tight leading-[1.1] mb-5" style="font-family:'Merriweather',serif">
                    Portal Perumahan<br><span class="text-[#d6fb00]">Terpadu</span> Jawa Tengah
                </h2>
                <p class="text-slate-400 text-sm sm:text-base leading-relaxed max-w-lg mb-8">
                    Akses informasi rumah subsidi, data spasial permukiman, dan layanan konsultasi dalam satu platform terintegrasi.
                </p>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="#cari-perumahan" class="bg-[#d6fb00] hover:bg-[#ecffb6] text-[#0a1a1f] font-bold text-xs px-6 py-3.5 rounded-xl transition-all shadow-lg shadow-[#d6fb00]/20 flex items-center gap-2">
                        <i class="fa-solid fa-magnifying-glass"></i> Cari Rumah Subsidi
                    </a>
                    <a href="#layanan-kami" class="bg-[#d6fb00]/8 hover:bg-[#d6fb00]/15 border border-[#d6fb00]/15 text-[#ecffb6] font-semibold text-xs px-6 py-3.5 rounded-xl transition-all flex items-center gap-2 backdrop-blur-sm">
                        <i class="fa-solid fa-layer-group"></i> Lihat Layanan
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Slide 2 -->
    <div class="hero-slide" :class="current === 1 && 'active'">
        <div class="absolute inset-0 gradient-hero z-10"></div>
        <img src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1920&q=80" alt="Data Spasial">
        <div class="absolute inset-0 z-20 max-w-7xl mx-auto px-6 lg:px-8 flex flex-col justify-center">
            <div class="max-w-2xl animate-fade-in-up">
                <div class="inline-flex items-center gap-2 bg-[#00a3b5]/8 border border-[#00a3b5]/20 backdrop-blur-sm px-4 py-1.5 rounded-full text-[11px] text-[#8aacb0] font-medium mb-6">
                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-400 animate-pulse"></span>
                    Sistem Pemetaan RTLH & GIS Sektoral
                </div>
                <h2 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white tracking-tight leading-[1.1] mb-5" style="font-family:'Merriweather',serif">
                    Akurasi Data<br><span class="text-[#00a3b5]">Spasial</span> Permukiman
                </h2>
                <p class="text-slate-400 text-sm sm:text-base leading-relaxed max-w-lg mb-8">
                    Perencanaan pembangunan tepat sasaran melalui integrasi data GIS terpadu di 35 Kabupaten/Kota se-Jawa Tengah.
                </p>
                <a href="#" class="inline-flex bg-[#00a3b5] hover:bg-[#26c6da] text-[#0a1a1f] font-bold text-xs px-6 py-3.5 rounded-xl transition-all shadow-lg shadow-[#00a3b5]/20 items-center gap-2">
                    <i class="fa-solid fa-map-location-dot"></i> Buka Peta Sebaran
                </a>
            </div>
        </div>
    </div>

    <!-- Slide 3 -->
    <div class="hero-slide" :class="current === 2 && 'active'">
        <div class="absolute inset-0 gradient-hero z-10"></div>
        <img src="https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=1920&q=80" alt="Rumah Subsidi">
        <div class="absolute inset-0 z-20 max-w-7xl mx-auto px-6 lg:px-8 flex flex-col justify-center">
            <div class="max-w-2xl animate-fade-in-up">
                <div class="inline-flex items-center gap-2 bg-[#6bcb77]/8 border border-[#6bcb77]/20 backdrop-blur-sm px-4 py-1.5 rounded-full text-[11px] text-[#8aacb0] font-medium mb-6">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Program Bantuan Rumah Layak Huni
                </div>
                <h2 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white tracking-tight leading-[1.1] mb-5" style="font-family:'Merriweather',serif">
                    Rumah <span class="text-emerald-400">Layak Huni</span><br>untuk Semua
                </h2>
                <p class="text-slate-400 text-sm sm:text-base leading-relaxed max-w-lg mb-8">
                    Cek kelayakan bantuan perumahan, simulasi KPR subsidi, dan ajukan permohonan secara online.
                </p>
                <a href="https://my.pkp.go.id/cekbantuan" target="_blank" class="inline-flex bg-[#6bcb77] hover:bg-[#6bcb77]/80 text-[#0a1a1f] font-bold text-xs px-6 py-3.5 rounded-xl transition-all shadow-lg shadow-[#6bcb77]/20 items-center gap-2">
                    <i class="fa-solid fa-circle-check"></i> Cek Bantuan Sekarang
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Capsule Bar -->
    <div class="absolute bottom-0 left-0 right-0 z-30">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 pb-6">
            <!-- Slide Indicators -->
            <div class="flex items-center gap-2 mb-5">
                <template x-for="i in 3" :key="i">
                    <button @click="goTo(i-1)" class="h-1 rounded-full transition-all duration-500" :class="current === i-1 ? 'w-8 bg-[#d6fb00]' : 'w-2 bg-[#d6fb00]/15 hover:bg-[#d6fb00]/30'"></button>
                </template>
                <span class="text-[10px] text-zinc-500 ml-2 font-mono" x-text="'0' + (current+1) + ' / 03'"></span>
            </div>
            <!-- Stat Capsules -->
            <div class="hidden sm:flex flex-wrap items-center gap-3">
                <div class="stat-capsule">
                    <div class="stat-icon bg-[#d6fb00]/10 text-[#d6fb00]"><i class="fa-solid fa-house"></i></div>
                    <span class="text-white font-bold">209K+</span>
                    <span class="text-zinc-500 text-xs">Unit</span>
                </div>
                <div class="stat-capsule">
                    <div class="stat-icon bg-[#6bcb77]/10 text-[#6bcb77]"><i class="fa-solid fa-certificate"></i></div>
                    <span class="text-white font-bold">58.6K+</span>
                    <span class="text-zinc-500 text-xs">Subsidi</span>
                </div>
                <div class="stat-capsule">
                    <div class="stat-icon bg-[#00a3b5]/10 text-[#00a3b5]"><i class="fa-solid fa-location-crosshairs"></i></div>
                    <span class="text-white font-bold">2.7K+</span>
                    <span class="text-zinc-500 text-xs">Lokasi</span>
                </div>
                <div class="stat-capsule">
                    <div class="stat-icon bg-purple-500/10 text-purple-400"><i class="fa-solid fa-map"></i></div>
                    <span class="text-white font-bold">35</span>
                    <span class="text-zinc-500 text-xs">Kab/Kota</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     SECTION 2: ABOUT
     ============================================================ -->
<section class="w-full relative gradient-mesh py-20 sm:py-28">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <!-- Left: Copy -->
            <div>
                <div class="inline-flex items-center gap-2 bg-[#d6fb00]/10 border border-[#d6fb00]/15 px-3 py-1 rounded-full text-[11px] text-[#d6fb00] font-semibold mb-6">
                    <i class="fa-solid fa-building-columns"></i> Tentang Kami
                </div>
                <h3 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight leading-tight mb-5">
                    Klinik Perumahan &<br>Kawasan <span class="text-[#d6fb00]">Permukiman</span>
                </h3>
                <p class="text-slate-400 text-sm leading-relaxed mb-6">
                    Klinik PKP Balai P3KP Jawa III — Disperakim Provinsi Jawa Tengah hadir sebagai pusat layanan informasi dan konsultasi terpadu untuk pembangunan rumah layak huni. Kami menghubungkan masyarakat, pengembang, dan pemerintah daerah dalam satu platform yang transparan dan akuntabel.
                </p>
                <p class="text-slate-500 text-sm leading-relaxed mb-8">
                    Melalui integrasi data spasial, sistem informasi perumahan, dan layanan aduan digital, kami berkomitmen memastikan setiap warga Jawa Tengah memiliki akses terhadap hunian yang aman, layak, dan terjangkau.
                </p>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2 text-xs text-zinc-400">
                        <div class="w-8 h-8 rounded-lg bg-[#d6fb00]/10 border border-[#d6fb00]/15 flex items-center justify-center text-[#d6fb00]"><i class="fa-solid fa-shield-halved text-sm"></i></div>
                        <span>Lembaga Resmi<br><strong class="text-white">Pemerintah</strong></span>
                    </div>
                    <div class="w-px h-8 bg-[#d6fb00]/10"></div>
                    <div class="flex items-center gap-2 text-xs text-zinc-400">
                        <div class="w-8 h-8 rounded-lg bg-[#00a3b5]/10 border border-[#00a3b5]/20 flex items-center justify-center text-[#00a3b5]"><i class="fa-solid fa-database text-sm"></i></div>
                        <span>Data Real-time<br><strong class="text-white">Terintegrasi</strong></span>
                    </div>
                    <div class="w-px h-8 bg-[#d6fb00]/10"></div>
                    <div class="flex items-center gap-2 text-xs text-zinc-400">
                        <div class="w-8 h-8 rounded-lg bg-[#6bcb77]/10 border border-[#6bcb77]/20 flex items-center justify-center text-[#6bcb77]"><i class="fa-solid fa-headset text-sm"></i></div>
                        <span>Layanan<br><strong class="text-white">24/7</strong></span>
                    </div>
                </div>
            </div>
            <!-- Right: Visual Cards -->
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-[#0f2a30]/80 border border-[#d6fb00]/20 rounded-2xl p-6 flex flex-col items-center text-center hover:border-[#d6fb00]/35 transition-colors">
                    <div class="w-12 h-12 rounded-xl bg-[#d6fb00]/10 border border-[#d6fb00]/15 flex items-center justify-center text-[#d6fb00] mb-4"><i class="fa-solid fa-house-laptop text-lg"></i></div>
                    <h4 class="text-white font-bold text-2xl mb-1">4</h4>
                    <p class="text-zinc-500 text-[11px]">Layanan Digital</p>
                </div>
                <div class="bg-[#0f2a30]/80 border border-[#d6fb00]/20 rounded-2xl p-6 flex flex-col items-center text-center hover:border-[#00a3b5]/35 transition-colors">
                    <div class="w-12 h-12 rounded-xl bg-[#00a3b5]/10 border border-[#00a3b5]/20 flex items-center justify-center text-[#00a3b5] mb-4"><i class="fa-solid fa-map-location-dot text-lg"></i></div>
                    <h4 class="text-white font-bold text-2xl mb-1">35</h4>
                    <p class="text-zinc-500 text-[11px]">Kabupaten/Kota</p>
                </div>
                <div class="bg-[#0f2a30]/80 border border-[#d6fb00]/20 rounded-2xl p-6 flex flex-col items-center text-center hover:border-[#6bcb77]/35 transition-colors">
                    <div class="w-12 h-12 rounded-xl bg-[#6bcb77]/10 border border-[#6bcb77]/20 flex items-center justify-center text-[#6bcb77] mb-4"><i class="fa-solid fa-users text-lg"></i></div>
                    <h4 class="text-white font-bold text-2xl mb-1">12K+</h4>
                    <p class="text-zinc-500 text-[11px]">Warga Terlayani</p>
                </div>
                <div class="bg-[#0f2a30]/80 border border-[#d6fb00]/20 rounded-2xl p-6 flex flex-col items-center text-center hover:border-purple-500/35 transition-colors">
                    <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 mb-4"><i class="fa-solid fa-clock text-lg"></i></div>
                    <h4 class="text-white font-bold text-2xl mb-1">24/7</h4>
                    <p class="text-zinc-500 text-[11px]">Akses Online</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     SECTION 3: LAYANAN — Bento Grid
     ============================================================ -->
<section id="layanan-kami" class="w-full py-20 sm:py-28 scroll-mt-20 pattern-grid relative">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-14">
            <div class="inline-flex items-center gap-2 bg-[#d6fb00]/8 border border-[#d6fb00]/20 px-3 py-1 rounded-full text-[11px] text-[#8aacb0] font-medium mb-4">
                <i class="fa-solid fa-layer-group text-[#d6fb00]"></i> Pilar Layanan
            </div>
            <h3 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight mb-3">Layanan <span class="text-[#d6fb00]">Kami</span></h3>
            <p class="text-zinc-500 text-sm max-w-md mx-auto">Pilih kategori layanan sesuai kebutuhan Anda</p>
        </div>

        <div class="bento-grid">
            <!-- Featured: Masyarakat Umum -->
            <a href="<?= base_url('Index/umum') ?>" class="bento-featured group bg-gradient-to-br from-[#d6fb00]/5 to-transparent border border-[#d6fb00]/20 hover:border-[#d6fb00]/35 rounded-2xl p-8 sm:p-10 flex flex-col justify-between transition-all duration-300 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-[#d6fb00]/5 rounded-full blur-[80px] -translate-y-1/2 translate-x-1/2"></div>
                <div>
                    <div class="w-16 h-16 bg-[#d6fb00]/10 rounded-2xl flex items-center justify-center border border-[#d6fb00]/15 mb-6 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-users text-[#d6fb00] text-2xl"></i>
                    </div>
                    <h4 class="text-white font-bold text-xl sm:text-2xl mb-3 group-hover:text-[#d6fb00] transition-colors">Masyarakat Umum</h4>
                    <p class="text-zinc-500 text-sm leading-relaxed max-w-sm">Informasi perumahan, program bantuan rumah layak huni, cek kelayakan, simulasi KPR, dan layanan pengaduan masyarakat.</p>
                </div>
                <div class="flex items-center gap-2 mt-6 text-[#d6fb00] text-xs font-semibold">
                    <span>Selengkapnya</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </a>

            <!-- Pengembang -->
            <a href="<?= base_url('Index/pengembang') ?>" class="group bg-[#0f2a30]/60 border border-[#d6fb00]/20 hover:border-purple-500/35 rounded-2xl p-6 sm:p-7 flex flex-col justify-between transition-all duration-300">
                <div>
                    <div class="w-12 h-12 bg-purple-500/10 rounded-xl flex items-center justify-center border border-purple-500/20 mb-5 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-building text-purple-400 text-lg"></i>
                    </div>
                    <h4 class="text-white font-bold text-base mb-2 group-hover:text-purple-400 transition-colors">Pengembang</h4>
                    <p class="text-zinc-500 text-xs leading-relaxed">Registrasi, publikasi perumahan, dan dokumen petunjuk.</p>
                </div>
                <i class="fa-solid fa-arrow-right text-zinc-700 text-xs mt-4 group-hover:text-purple-400 group-hover:translate-x-1 transition-all"></i>
            </a>

            <!-- KKN & Magang -->
            <a href="<?= base_url('Index/kemitraan') ?>" class="group bg-[#0f2a30]/60 border border-[#d6fb00]/20 hover:border-[#00a3b5]/35 rounded-2xl p-6 sm:p-7 flex flex-col justify-between transition-all duration-300">
                <div>
                    <div class="w-12 h-12 bg-[#00a3b5]/10 rounded-xl flex items-center justify-center border border-[#00a3b5]/20 mb-5 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-graduation-cap text-[#00a3b5] text-lg"></i>
                    </div>
                    <h4 class="text-white font-bold text-base mb-2 group-hover:text-[#00a3b5] transition-colors">KKN & Magang</h4>
                    <p class="text-zinc-500 text-xs leading-relaxed">Program tematik, pendaftaran magang, dan syarat masuk.</p>
                </div>
                <i class="fa-solid fa-arrow-right text-zinc-700 text-xs mt-4 group-hover:text-[#00a3b5] group-hover:translate-x-1 transition-all"></i>
            </a>

            <!-- Kabupaten/Kota -->
            <a href="<?= base_url('Index/listkabupaten') ?>" class="group bg-[#0f2a30]/60 border border-[#d6fb00]/20 hover:border-[#6bcb77]/20 rounded-2xl p-6 sm:p-7 flex flex-col justify-between transition-all duration-300">
                <div>
                    <div class="w-12 h-12 bg-[#6bcb77]/10 rounded-xl flex items-center justify-center border border-[#6bcb77]/20 mb-5 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-map-location-dot text-[#6bcb77] text-lg"></i>
                    </div>
                    <h4 class="text-white font-bold text-base mb-2 group-hover:text-[#6bcb77] transition-colors">Kabupaten / Kota</h4>
                    <p class="text-zinc-500 text-xs leading-relaxed">Data intervensi kumuh dan monitoring spasial.</p>
                </div>
                <i class="fa-solid fa-arrow-right text-zinc-700 text-xs mt-4 group-hover:text-[#6bcb77] group-hover:translate-x-1 transition-all"></i>
            </a>
        </div>
    </div>
</section>

<!-- ============================================================
     SECTION 4: TAHAPAN — Pipeline Timeline
     ============================================================ -->
<section class="w-full py-20 sm:py-28">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-16">
            <h3 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white mb-3">
                Tahapan <span class="text-[#d6fb00]">Membangun</span> Rumah
            </h3>
            <p class="text-zinc-500 text-sm">Ikuti panduan lengkap untuk mewujudkan rumah impian Anda</p>
        </div>

        <!-- Desktop Pipeline -->
        <div class="hidden md:block relative">
            <div class="pipeline-connector" style="top:40px; left:12.5%; right:12.5%;"></div>
            <div class="grid grid-cols-4 gap-6 relative z-10">
                <div class="flex flex-col items-center text-center group">
                    <div class="pipeline-dot mb-6 group-hover:scale-125 transition-transform"></div>
                    <div class="bg-[#0f2a30]/80 border border-[#d6fb00]/20 group-hover:border-[#d6fb00]/20 rounded-2xl p-6 w-full transition-all duration-300 group-hover:-translate-y-2">
                        <div class="w-14 h-14 bg-[#d6fb00]/10 rounded-xl flex items-center justify-center text-[#d6fb00] mb-4 mx-auto border border-[#d6fb00]/15"><i class="fa-solid fa-compass-drafting text-xl"></i></div>
                        <h4 class="text-white font-bold text-sm mb-2">Perencanaan</h4>
                        <p class="text-zinc-500 text-[11px] leading-relaxed">Desain rumah, kalkulasi anggaran, dan perizinan pembangunan</p>
                    </div>
                </div>
                <div class="flex flex-col items-center text-center group">
                    <div class="pipeline-dot mb-6 group-hover:scale-125 transition-transform"></div>
                    <div class="bg-[#0f2a30]/80 border border-[#d6fb00]/20 group-hover:border-[#d6fb00]/20 rounded-2xl p-6 w-full transition-all duration-300 group-hover:-translate-y-2">
                        <div class="w-14 h-14 bg-[#00a3b5]/10 rounded-xl flex items-center justify-center text-[#00a3b5] mb-4 mx-auto border border-[#00a3b5]/20"><i class="fa-solid fa-helmet-safety text-xl"></i></div>
                        <h4 class="text-white font-bold text-sm mb-2">Konstruksi</h4>
                        <p class="text-zinc-500 text-[11px] leading-relaxed">Pemilihan material, kontraktor, dan pelaksanaan pembangunan</p>
                    </div>
                </div>
                <div class="flex flex-col items-center text-center group">
                    <div class="pipeline-dot mb-6 group-hover:scale-125 transition-transform"></div>
                    <div class="bg-[#0f2a30]/80 border border-[#d6fb00]/20 group-hover:border-[#d6fb00]/20 rounded-2xl p-6 w-full transition-all duration-300 group-hover:-translate-y-2">
                        <div class="w-14 h-14 bg-purple-500/10 rounded-xl flex items-center justify-center text-purple-400 mb-4 mx-auto border border-purple-500/20"><i class="fa-solid fa-user-check text-xl"></i></div>
                        <h4 class="text-white font-bold text-sm mb-2">Pengawasan</h4>
                        <p class="text-zinc-500 text-[11px] leading-relaxed">Kontrol kualitas, checklist progress, dan standar bangunan</p>
                    </div>
                </div>
                <div class="flex flex-col items-center text-center group">
                    <div class="pipeline-dot mb-6 group-hover:scale-125 transition-transform"></div>
                    <div class="bg-[#0f2a30]/80 border border-[#d6fb00]/20 group-hover:border-[#d6fb00]/20 rounded-2xl p-6 w-full transition-all duration-300 group-hover:-translate-y-2">
                        <div class="w-14 h-14 bg-[#6bcb77]/10 rounded-xl flex items-center justify-center text-[#6bcb77] mb-4 mx-auto border border-[#6bcb77]/20"><i class="fa-solid fa-house-chimney-user text-xl"></i></div>
                        <h4 class="text-white font-bold text-sm mb-2">Pemanfaatan</h4>
                        <p class="text-zinc-500 text-[11px] leading-relaxed">Penggunaan, pemeliharaan, dan perawatan berkala rumah</p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Mobile Stack -->
        <div class="md:hidden space-y-4 relative pl-8">
            <div class="absolute left-[14px] top-0 bottom-0 w-0.5 bg-gradient-to-b from-[#d6fb00]/30 via-[#00a3b5]/20 to-[#6bcb77]/30"></div>
            <?php
            $steps = [
                ['Perencanaan', 'Desain, anggaran, perizinan', 'fa-compass-drafting', 'amber'],
                ['Konstruksi', 'Material, kontraktor, pelaksanaan', 'fa-helmet-safety', 'cyan'],
                ['Pengawasan', 'Kontrol kualitas & progress', 'fa-user-check', 'purple'],
                ['Pemanfaatan', 'Penggunaan & pemeliharaan', 'fa-house-chimney-user', 'emerald']
            ];
            foreach ($steps as $i => $s): ?>
            <div class="relative flex items-start gap-4">
                <div class="absolute left-[-22px] top-4 pipeline-dot" style="width:10px;height:10px;border-width:2px;"></div>
                <div class="bg-[#0f2a30]/80 border border-[#d6fb00]/20 rounded-xl p-5 flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="w-9 h-9 bg-<?=$s[3]?>-500/10 rounded-lg flex items-center justify-center text-<?=$s[3]?>-400 border border-<?=$s[3]?>-500/20"><i class="fa-solid <?=$s[2]?> text-sm"></i></div>
                        <h4 class="text-white font-bold text-sm"><?=$s[0]?></h4>
                    </div>
                    <p class="text-zinc-500 text-xs"><?=$s[1]?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================================================
     SECTION 5: BANK DESAIN — True Carousel
     ============================================================ -->
<section id="bank-desain" class="w-full py-20 sm:py-28 scroll-mt-20" x-data="designCarousel()" @designs-loaded.window="$nextTick(() => recalc())">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row items-start sm:items-end justify-between mb-10 gap-4">
            <div>
                <h3 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">Bank <span class="text-[#00a3b5]">Desain</span></h3>
                <p class="text-zinc-500 text-sm mt-2">Temukan desain rumah yang sesuai kebutuhan Anda</p>
            </div>
            <div class="flex items-center gap-2">
                <button @click="prev()" class="w-10 h-10 rounded-xl bg-[#d6fb00]/5 border border-[#d6fb00]/20 hover:border-[#00a3b5]/50 text-[#8aacb0] hover:text-white flex items-center justify-center transition-all"><i class="fa-solid fa-chevron-left text-xs"></i></button>
                <button @click="next()" class="w-10 h-10 rounded-xl bg-[#d6fb00]/5 border border-[#d6fb00]/20 hover:border-[#00a3b5]/50 text-[#8aacb0] hover:text-white flex items-center justify-center transition-all"><i class="fa-solid fa-chevron-right text-xs"></i></button>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl">
            <div id="designs-container" class="carousel-track" :style="'transform: translateX(-' + (offset * (100 / perView)) + '%)'">
                <!-- Skeleton Loaders (replaced by AJAX) -->
                <div class="px-3"><div class="skeleton h-80"></div></div>
                <div class="px-3"><div class="skeleton h-80"></div></div>
                <div class="px-3"><div class="skeleton h-80"></div></div>
            </div>
        </div>

        <!-- Dot Indicators -->
        <div class="flex items-center justify-center gap-1.5 mt-6">
            <template x-for="i in totalPages" :key="i">
                <button @click="goToPage(i-1)" class="w-1.5 h-1.5 rounded-full transition-all duration-300" :class="currentPage === i-1 ? 'w-5 bg-[#00a3b5]' : 'bg-[#d6fb00]/12 hover:bg-[#d6fb00]/25'"></button>
            </template>
        </div>
    </div>
</section>

<!-- ============================================================
     SECTION 6: CARI PERUMAHAN (Sikumbang)
     ============================================================ -->
<section id="cari-perumahan" class="w-full py-20 sm:py-28 scroll-mt-20 gradient-mesh">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-10">
            <h3 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight mb-2">Cari <span class="text-[#d6fb00]">Perumahan</span></h3>
            <p class="text-zinc-500 text-xs">Data real-time dari Sikumbang — Tapera</p>
        </div>

        <div class="bg-[#0f2a30]/60 border border-[#d6fb00]/20 p-6 sm:p-8 rounded-2xl mb-10 backdrop-blur-sm">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-end">
                <div class="lg:col-span-4">
                    <label class="block text-[10px] font-semibold text-[#8aacb0] uppercase tracking-widest mb-2"><i class="fa-solid fa-location-dot mr-1.5 text-[#d6fb00]"></i> Wilayah</label>
                    <select id="kodeWilayah" onchange="cari_wil()" class="w-full bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl px-4 py-3 text-xs text-[#ecffb6] outline-none focus:border-[#d6fb00]/40 focus:shadow-[0_0_0_3px_rgba(214,251,0,0.1)] transition-all">
                        <option value="33">Seluruh Jawa Tengah</option>
                        <?php foreach ($kabupaten_kota_jateng as $kode => $nama): ?>
                        <option value="<?= $kode ?>"><?= $nama ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lg:col-span-8">
                    <label class="block text-[10px] font-semibold text-[#8aacb0] uppercase tracking-widest mb-2"><i class="fa-solid fa-magnifying-glass mr-1.5 text-[#d6fb00]"></i> Nama Perumahan</label>
                    <div class="relative">
                        <input id="keyword" onkeyup="cari_wil()" type="text" placeholder="Cari nama perumahan..." class="w-full bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl px-4 py-3 text-xs text-[#ecffb6] outline-none focus:border-[#d6fb00]/40 focus:shadow-[0_0_0_3px_rgba(214,251,0,0.1)] transition-all placeholder-[#5a7a80]">
                        <button onclick="cari_wil()" class="absolute right-1.5 top-1.5 bg-[#d6fb00] hover:bg-[#ecffb6] text-[#0a1a1f] h-9 w-9 rounded-lg flex items-center justify-center transition-all"><i class="fa-solid fa-magnifying-glass text-xs"></i></button>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-center mt-5 pt-5 border-t border-[#d6fb00]/20">
                <div class="lg:col-span-4">
                    <label class="block text-[10px] font-semibold text-[#8aacb0] uppercase tracking-widest mb-2"><i class="fa-solid fa-sort mr-1.5 text-[#d6fb00]"></i> Urutkan</label>
                    <select id="sort" onchange="cari_wil()" class="w-full bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl px-4 py-2.5 text-xs text-[#ecffb6] outline-none focus:border-[#d6fb00]/40 focus:shadow-[0_0_0_3px_rgba(214,251,0,0.1)] transition-all">
                        <option value="terbaru">Terbaru</option>
                        <option value="subsidi-termurah">Harga Terendah</option>
                        <option value="subsidi-tertinggi">Harga Tertinggi</option>
                    </select>
                </div>
                <div class="lg:col-span-8 flex lg:justify-end items-center">
                    <label class="inline-flex items-center cursor-pointer group">
                        <input id="status_subsidi" value="subsidi" onchange="cari_wil()" type="checkbox" class="sr-only peer" checked>
                        <div class="relative w-11 h-6 bg-[#1a3d45] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#d6fb00]"></div>
                        <span class="ml-3 text-xs font-medium text-zinc-400">Hanya Rumah Subsidi</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="temp_rumah">
            <div class="skeleton h-72"></div>
            <div class="skeleton h-72"></div>
            <div class="skeleton h-72"></div>
        </div>

        <div class="w-full flex justify-center mt-12">
            <button id="btn-load-more" onclick="load_more_data()" data-page="1" class="group inline-flex items-center gap-2 px-12 sm:px-24 py-3.5 bg-[#d6fb00]/5 border border-[#d6fb00]/20 text-[#ecffb6] font-semibold text-xs rounded-full hover:bg-[#d6fb00]/10 hover:border-[#d6fb00]/25 tracking-wide transition-all">
                <i class="fa-solid fa-circle-arrow-down text-sm text-[#5a7a80] group-hover:text-[#d6fb00] group-hover:translate-y-[1px] transition-all"></i>
                <span id="text-load">Muat Lebih Banyak</span>
            </button>
        </div>
    </div>
</section>

<!-- ============================================================
     SECTION 7: BERITA & ARTIKEL (Moved to pre-footer)
     ============================================================ -->
<section class="w-full py-20 sm:py-28">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row items-start sm:items-end justify-between mb-10 gap-4">
            <div>
                <h3 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
                    Berita & <span class="text-[#00a3b5]">Artikel</span>
                </h3>
                <p class="text-zinc-500 text-sm mt-2">Informasi terkini seputar perumahan dan permukiman</p>
            </div>
            <a href="#" class="group border border-[#d6fb00]/20 hover:border-[#00a3b5]/30 bg-[#d6fb00]/5 px-4 py-2 rounded-full text-xs font-semibold text-[#8aacb0] hover:text-white flex items-center gap-2 transition-all">
                Semua Artikel
                <span class="w-5 h-5 rounded-full bg-[#00a3b5]/20 text-[#00a3b5] group-hover:bg-[#00a3b5] group-hover:text-[#0a1a1f] flex items-center justify-center text-[10px] transition-colors"><i class="fa-solid fa-arrow-right"></i></span>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="articles-container">
            <div class="skeleton h-72"></div>
            <div class="skeleton h-72"></div>
            <div class="skeleton h-72"></div>
        </div>
    </div>
</section>


<!-- ============================================================
     SCRIPTS
     ============================================================ -->
<script>
// ============================================================
// HERO SLIDESHOW
// ============================================================
function heroSlider() {
    return {
        current: 0,
        total: 3,
        init() {
            setInterval(() => { this.current = (this.current + 1) % this.total; }, 6000);
        },
        goTo(i) { this.current = i; }
    }
}

// ============================================================
// DESIGN CAROUSEL (True sliding)
// ============================================================
function designCarousel() {
    return {
        offset: 0,
        perView: 3,
        totalItems: 0,
        currentPage: 0,
        totalPages: 1,
        
        init() {
            this.updatePerView();
            window.addEventListener('resize', () => this.updatePerView());
        },
        updatePerView() {
            if (window.innerWidth < 640) this.perView = 1;
            else if (window.innerWidth < 1024) this.perView = 2;
            else this.perView = 3;
            this.recalc();
        },
        recalc() {
            this.totalItems = document.querySelectorAll('#designs-container > *').length || 0;
            this.totalPages = Math.ceil(this.totalItems / this.perView);
            if (this.offset >= this.totalItems) this.offset = 0;
            this.currentPage = Math.floor(this.offset / this.perView);
        },
        next() { 
            this.recalc();
            this.offset = (this.offset + this.perView >= this.totalItems) ? 0 : this.offset + this.perView;
            this.currentPage = Math.floor(this.offset / this.perView);
        },
        prev() { 
            this.recalc();
            this.offset = (this.offset - this.perView < 0) ? Math.max(0, this.totalItems - this.perView) : this.offset - this.perView;
            this.currentPage = Math.floor(this.offset / this.perView);
        },
        goToPage(p) {
            this.offset = p * this.perView;
            this.currentPage = p;
        }
    }
}

// ============================================================
// PROGRESSIVE LAZY LOADING via IntersectionObserver
// ============================================================
const lazyState = { articles: false, designs: false, perumahan: false };

function lazyLoad(targetId, url, stateKey) {
    if (lazyState[stateKey]) return;
    lazyState[stateKey] = true;
    $.ajax({
        url: url,
        success: function(html) {
            $('#' + targetId).html(html);
            if (targetId === 'designs-container') {
                window.dispatchEvent(new CustomEvent('designs-loaded'));
            }
        },
        error: function() {
            $('#' + targetId).html('<p class="col-span-3 text-center text-[#5a7a80] py-8">Gagal memuat data. <button onclick="lazyState.' + stateKey + '=false;lazyLoad(\'' + targetId + '\',\'' + url + '\',\'' + stateKey + '\')" class="text-[#d6fb00] underline">Coba lagi</button></p>');
            lazyState[stateKey] = false;
        }
    });
}

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (!entry.isIntersecting) return;
        const id = entry.target.id;
        if (id === 'articles-container' && !lazyState.articles)
            lazyLoad('articles-container', '<?= base_url("Index/ajax_articles") ?>', 'articles');
        if (id === 'designs-container' && !lazyState.designs)
            lazyLoad('designs-container', '<?= base_url("Index/ajax_house_designs") ?>', 'designs');
        if (id === 'temp_rumah' && !lazyState.perumahan)
            lazyLoad('temp_rumah', '<?= base_url("Index/ajax_perumahan") ?>', 'perumahan');
    });
}, { rootMargin: '300px' });

document.addEventListener('DOMContentLoaded', function() {
    ['articles-container', 'designs-container', 'temp_rumah'].forEach(id => {
        const el = document.getElementById(id);
        if (el) observer.observe(el);
    });
});

// ============================================================
// SEARCH & LOAD MORE
// ============================================================
function cari_wil() {
    var keyword = document.getElementById('keyword').value;
    var kodeWilayah = document.getElementById('kodeWilayah').value;
    var sort = document.getElementById('sort').value;
    let isChecked = document.getElementById('status_subsidi').checked;
    let statusRumah = isChecked ? 'subsidi' : 'semua';
    $.ajax({
        url: '<?= base_url('Index/cari_wil') ?>?kodeWilayah='+kodeWilayah+'&keyword='+keyword+'&sort='+sort+'&status_rumah='+statusRumah,
        success: function(response) { jQuery('#temp_rumah').html(response); }
    });
}

function load_more_data() {
    let btn = jQuery('#btn-load-more');
    let currentPage = parseInt(btn.attr('data-page'));
    let nextPage = currentPage + 1;
    var keyword = document.getElementById('keyword').value;
    var kodeWilayah = document.getElementById('kodeWilayah').value;
    var sort = document.getElementById('sort').value;
    let isChecked = document.getElementById('status_subsidi').checked;
    let statusRumah = isChecked ? 'subsidi' : 'semua';
    jQuery('#text-load').text('Sedang Memuat...');
    jQuery.ajax({
        url: '<?= base_url('Index/load_more') ?>?kodeWilayah='+kodeWilayah+'&keyword='+keyword+'&sort='+sort+'&status_rumah='+statusRumah+'&page='+nextPage,
        success: function(response) {
            if (jQuery.trim(response) !== '') {
                jQuery('#temp_rumah').append(response);
                btn.attr('data-page', nextPage);
                jQuery('#text-load').text('Muat Lebih Banyak');
            } else {
                jQuery('#text-load').text('Semua Data Telah Dimuat');
                btn.prop('disabled', true).fadeOut(2000);
            }
        }
    });
}
</script>