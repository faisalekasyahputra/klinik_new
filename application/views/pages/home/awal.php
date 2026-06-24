<!-- ============================================================
     MOBILE MENU (Alpine.js)
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
                <a href="#etalase-program" class="flex items-center gap-2 text-[#d6fb00] text-xs py-2 font-bold bg-[#d6fb00]/10 hover:bg-[#d6fb00]/20 px-3 rounded-lg border border-[#d6fb00]/20 transition-colors" @click="mobileMenu = false">
                    <i class="fa-solid fa-house-chimney-window w-4 text-center"></i> Etalase Program
                </a>
                <a href="<?= base_url('simulasi_kpr') ?>" class="flex items-center gap-2 text-zinc-400 hover:text-white text-xs py-1 mt-2" @click="mobileMenu = false">
                    <i class="fa-solid fa-calculator w-4 text-center opacity-70"></i> Info KPR & Subsidi
                </a>
                <a href="#bank-desain" class="flex items-center gap-2 text-zinc-400 hover:text-white text-xs py-1" @click="mobileMenu = false">
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

<!-- ============================================================
     SECTION 1: HERO — Full Viewport Slideshow
     ============================================================ -->
<section class="w-full relative overflow-hidden" style="height: 100vh" x-data="heroSlider()">
    
    <!-- Slide 1 -->
    <div class="hero-slide" :class="current === 0 && 'active'">
        <div class="absolute inset-0 gradient-hero z-10"></div>
        <?php if(!empty($settings['hero_background'])): ?>
        <picture>
            <img src="<?= base_url($settings['hero_background']) ?>" alt="Hero Background" class="object-cover w-full h-full" loading="eager">
        </picture>
        <?php else: ?>
        <picture>
            <source srcset="<?= base_url('assets/img/hero/hero-perumahan-subsidi.webp') ?>" type="image/webp">
            <img src="<?= base_url('assets/img/hero/hero-perumahan-subsidi-opt.jpeg') ?>" alt="Pembangunan perumahan subsidi di Jawa Tengah — pekerja dan keluarga penerima manfaat" loading="eager">
        </picture>
        <?php endif; ?>
        <div class="absolute inset-0 z-20 max-w-[1440px] mx-auto px-6 lg:px-10 flex flex-col lg:flex-row items-center justify-between pb-20 lg:pb-0 pt-16 lg:pt-0 gap-8">
            <!-- Left Side Copy -->
            <div class="max-w-2xl w-full lg:w-[45%]" data-aos="fade-right" data-aos-duration="1000">
                <div class="inline-flex items-center gap-2 bg-[#d6fb00]/5 border border-[#d6fb00]/15 backdrop-blur-sm px-4 py-1.5 rounded-full text-[11px] text-[#8aacb0] font-medium mb-6 mt-8 lg:mt-0">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    Dinas Perumahan Rakyat & Kawasan Permukiman Prov. Jawa Tengah
                </div>
                <h2 class="text-3xl sm:text-4xl lg:text-[2.8rem] font-extrabold text-white mb-5 leading-[1.1] md:leading-[1.1] tracking-tight">
                    <?= !empty($settings['hero_title']) ? $settings['hero_title'] : 'Ngopeni Omah<br><span class="text-[#d6fb00]">Nglakoni Sesarengan</span>' ?>
                </h2>
                <p class="text-slate-400 text-sm sm:text-base lg:text-lg leading-relaxed max-w-xl mb-8">
                    <?= !empty($settings['hero_subtitle']) ? nl2br(htmlspecialchars($settings['hero_subtitle'])) : 'Akses informasi rumah subsidi, data spasial permukiman, dan layanan konsultasi dalam satu platform terintegrasi.' ?>
                </p>
                <div class="flex flex-wrap items-center gap-3">
                    <a href="#etalase-program" class="btn-primary text-xs px-6 py-3.5 rounded-xl flex items-center gap-2">
                        <i class="fa-solid fa-ticket"></i> Program
                    </a>
                    <a href="javascript:void(0)" onclick="document.getElementById('layanan-kami').scrollIntoView({behavior: 'smooth'})" class="btn-secondary text-xs px-6 py-3.5 rounded-xl flex items-center gap-2">
                        <i class="fa-solid fa-layer-group"></i> Lihat Layanan
                    </a>
                </div>
            </div>

            <!-- Right Side Grid (Ticket Style) -->
            <div class="hidden lg:grid grid-cols-2 gap-2 w-full lg:w-[55%] xl:pl-4 lg:scale-90 lg:origin-right">
                <style>
                .ticket-mask {
                    -webkit-mask-image: 
                        radial-gradient(circle at 5rem 0px, transparent 7.5px, black 8.5px),
                        radial-gradient(circle at 5rem 100%, transparent 7.5px, black 8.5px);
                    -webkit-mask-position: top left, bottom left;
                    -webkit-mask-size: 100% 51%;
                    -webkit-mask-repeat: no-repeat;
                    mask-image: 
                        radial-gradient(circle at 5rem 0px, transparent 7.5px, black 8.5px),
                        radial-gradient(circle at 5rem 100%, transparent 7.5px, black 8.5px);
                    mask-position: top left, bottom left;
                    mask-size: 100% 51%;
                    mask-repeat: no-repeat;
                }

                .ticket-base {
                    background: linear-gradient(135deg, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0.05) 100%);
                    backdrop-filter: blur(16px);
                    -webkit-backdrop-filter: blur(16px);
                    border-radius: 1rem;
                    -webkit-backface-visibility: hidden;
                    backface-visibility: hidden;
                }
                .ticket-wrapper:hover .ticket-base {
                    background: linear-gradient(135deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0.08) 100%);
                }

                .ticket-border {
                    border: 1px solid rgba(255,255,255,0.3);
                    border-radius: 1rem;
                }
                .ticket-wrapper:hover .ticket-border {
                    border-color: rgba(255,255,255,0.5);
                }

                /* Cutout Borders */
                .ticket-border::before,
                .ticket-border::after {
                    content: '';
                    position: absolute;
                    left: calc(5rem - 9px);
                    width: 18px;
                    height: 18px;
                    border-radius: 50%;
                    border: 1px solid rgba(255,255,255,0.3);
                    pointer-events: none;
                    transition: border-color 0.3s ease;
                    box-sizing: border-box;
                }
                .ticket-border::before { top: -9px; }
                .ticket-border::after { bottom: -9px; }
                </style>

                <!-- Ticket 1: KPR-FLPP -->
                <div class="ticket-wrapper relative group transition-shadow duration-300" data-aos="slide-up" data-aos-delay="100">
                    <div class="absolute inset-0 bg-transparent rounded-2xl shadow-[0_10px_30px_0_rgba(0,0,0,0.4)] group-hover:shadow-[0_15px_35px_0_rgba(0,0,0,0.5)] transition-shadow duration-300 pointer-events-none z-0"></div>
                    <a href="#etalase-program" class="ticket-base ticket-mask relative flex cursor-pointer min-h-[110px] z-10">
                        <div class="absolute inset-0 ticket-border pointer-events-none transition-colors duration-300 z-10"></div>
                        <div class="relative z-20 w-20 shrink-0 flex items-center justify-center border-r border-dashed border-white/40">
                            <i class="fa-solid fa-building-columns text-3xl text-[#00e1fa] group-hover:scale-110 group-hover:-rotate-3 transition-transform drop-shadow-[0_0_10px_rgba(0,225,250,0.5)]"></i>
                        </div>
                        <div class="relative z-20 p-4 flex-1 flex flex-col justify-center">
                            <div class="text-[9px] font-bold tracking-widest text-[#00e1fa] uppercase mb-1">MBR Fixed Income</div>
                            <h4 class="text-white font-bold text-base mb-1.5 leading-tight">KPR-FLPP</h4>
                            <p class="text-white/80 text-[11px] leading-relaxed line-clamp-2">Bunga flat 5% & cicilan ringan hingga 20 tahun.</p>
                        </div>
                    </a>
                </div>

                <!-- Ticket 2: Oemah Lestari -->
                <div class="ticket-wrapper relative group transition-shadow duration-300" data-aos="slide-up" data-aos-delay="200">
                    <div class="absolute inset-0 bg-transparent rounded-2xl shadow-[0_10px_30px_0_rgba(0,0,0,0.4)] group-hover:shadow-[0_15px_35px_0_rgba(0,0,0,0.5)] transition-shadow duration-300 pointer-events-none z-0"></div>
                    <a href="#etalase-program" class="ticket-base ticket-mask relative flex cursor-pointer min-h-[110px] z-10">
                        <div class="absolute inset-0 ticket-border pointer-events-none transition-colors duration-300 z-10"></div>
                        <div class="relative z-20 w-20 shrink-0 flex items-center justify-center border-r border-dashed border-white/40">
                            <i class="fa-solid fa-leaf text-3xl text-[#85ea92] group-hover:scale-110 group-hover:-rotate-3 transition-transform drop-shadow-[0_0_10px_rgba(133,234,146,0.5)]"></i>
                        </div>
                        <div class="relative z-20 p-4 flex-1 flex flex-col justify-center">
                            <div class="text-[9px] font-bold tracking-widest text-[#85ea92] uppercase mb-1">MBR & Umum</div>
                            <h4 class="text-white font-bold text-base mb-1.5 leading-tight">Oemah Lestari</h4>
                            <p class="text-white/80 text-[11px] leading-relaxed line-clamp-2">Fasilitas pembiayaan rumah murah bagi MBR dengan skema kredit bunga ringan (8% flat).</p>
                        </div>
                    </a>
                </div>

                <!-- Ticket 3: Peningkatan RTLH -->
                <div class="ticket-wrapper relative group transition-shadow duration-300" data-aos="slide-up" data-aos-delay="300">
                    <div class="absolute inset-0 bg-transparent rounded-2xl shadow-[0_10px_30px_0_rgba(0,0,0,0.4)] group-hover:shadow-[0_15px_35px_0_rgba(0,0,0,0.5)] transition-shadow duration-300 pointer-events-none z-0"></div>
                    <a href="#etalase-program" class="ticket-base ticket-mask relative flex cursor-pointer min-h-[110px] z-10">
                        <div class="absolute inset-0 ticket-border pointer-events-none transition-colors duration-300 z-10"></div>
                        <div class="relative z-20 w-20 shrink-0 flex items-center justify-center border-r border-dashed border-white/40">
                            <i class="fa-solid fa-hammer text-3xl text-[#d4a1ff] group-hover:scale-110 group-hover:-rotate-3 transition-transform drop-shadow-[0_0_10px_rgba(212,161,255,0.5)]"></i>
                        </div>
                        <div class="relative z-20 p-4 flex-1 flex flex-col justify-center">
                            <div class="text-[9px] font-bold tracking-widest text-[#d4a1ff] uppercase mb-1">Miskin & Ekstrem</div>
                            <h4 class="text-white font-bold text-base mb-1.5 leading-tight">Peningkatan RTLH</h4>
                            <p class="text-white/80 text-[11px] leading-relaxed line-clamp-2">Bantuan perbaikan rumah via Bankeupemdes.</p>
                        </div>
                    </a>
                </div>

                <!-- Ticket 4: Stimulan PB -->
                <div class="ticket-wrapper relative group transition-shadow duration-300" data-aos="slide-up" data-aos-delay="400">
                    <div class="absolute inset-0 bg-transparent rounded-2xl shadow-[0_10px_30px_0_rgba(0,0,0,0.4)] group-hover:shadow-[0_15px_35px_0_rgba(0,0,0,0.5)] transition-shadow duration-300 pointer-events-none z-0"></div>
                    <a href="#etalase-program" class="ticket-base ticket-mask relative flex cursor-pointer min-h-[110px] z-10">
                        <div class="absolute inset-0 ticket-border pointer-events-none transition-colors duration-300 z-10"></div>
                        <div class="relative z-20 w-20 shrink-0 flex items-center justify-center border-r border-dashed border-white/40">
                            <i class="fa-solid fa-trowel-bricks text-3xl text-[#e5ff4d] group-hover:scale-110 group-hover:-rotate-3 transition-transform drop-shadow-[0_0_10px_rgba(229,255,77,0.5)]"></i>
                        </div>
                        <div class="relative z-20 p-4 flex-1 flex flex-col justify-center">
                            <div class="text-[9px] font-bold tracking-widest text-[#e5ff4d] uppercase mb-1">Bencana & Relokasi</div>
                            <h4 class="text-white font-bold text-base mb-1.5 leading-tight">Stimulan PB</h4>
                            <p class="text-white/80 text-[11px] leading-relaxed line-clamp-2">Bantuan material pembangunan untuk PB Bencana.</p>
                        </div>
                    </a>
                </div>

                <!-- Ticket 5: Program Rumah Apung -->
                <div class="ticket-wrapper relative group transition-shadow duration-300 col-span-2 w-full max-w-[calc(50%-0.5rem)] mx-auto" data-aos="slide-up" data-aos-delay="500">
                    <div class="absolute inset-0 bg-transparent rounded-2xl shadow-[0_10px_30px_0_rgba(0,0,0,0.4)] group-hover:shadow-[0_15px_35px_0_rgba(0,0,0,0.5)] transition-shadow duration-300 pointer-events-none z-0"></div>
                    <a href="#etalase-program" class="ticket-base ticket-mask relative flex cursor-pointer min-h-[110px] z-10">
                        <div class="absolute inset-0 ticket-border pointer-events-none transition-colors duration-300 z-10"></div>
                        <div class="relative z-20 w-20 shrink-0 flex items-center justify-center border-r border-dashed border-white/40">
                            <i class="fa-solid fa-water text-3xl text-[#7db5ff] group-hover:scale-110 group-hover:-rotate-3 transition-transform drop-shadow-[0_0_10px_rgba(125,181,255,0.5)]"></i>
                        </div>
                        <div class="relative z-20 p-4 flex-1 flex flex-col justify-center">
                            <div class="text-[9px] font-bold tracking-widest text-[#7db5ff] uppercase mb-1">Kawasan Pesisir</div>
                            <h4 class="text-white font-bold text-base mb-1.5 leading-tight">Program Rumah Apung</h4>
                            <p class="text-white/80 text-[11px] leading-relaxed line-clamp-2">Inovasi desain rumah tahan banjir rob.</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Slide 2 -->
    <div class="hero-slide" :class="current === 1 && 'active'">
        <div class="absolute inset-0 gradient-hero z-10"></div>
        <picture>
            <source srcset="<?= base_url('assets/img/hero/hero-kawasan-permukiman.webp') ?>" type="image/webp">
            <img src="<?= base_url('assets/img/hero/hero-kawasan-permukiman-opt.jpeg') ?>" alt="Panorama udara kawasan permukiman modern Jawa Tengah dengan latar pegunungan" loading="lazy">
        </picture>
        <div class="absolute inset-0 z-20 max-w-7xl mx-auto px-6 lg:px-8 flex flex-col justify-center">
            <div class="max-w-2xl w-full" data-aos="fade-right" data-aos-duration="1000">
                <div class="inline-flex items-center gap-2 bg-[#00a3b5]/8 border border-[#00a3b5]/20 backdrop-blur-sm px-4 py-1.5 rounded-full text-[11px] text-[#8aacb0] font-medium mb-6">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#d6fb00] animate-pulse"></span>
                    Sistem Pemetaan RTLH & GIS Sektoral
                </div>
                <h2 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white mb-5">
                    Akurasi Data<br><span class="text-[#00a3b5]">Spasial</span> Permukiman
                </h2>
                <p class="text-slate-400 text-sm sm:text-base leading-relaxed max-w-lg mb-8">
                    Perencanaan pembangunan tepat sasaran melalui integrasi data GIS terpadu di 35 Kabupaten/Kota se-Jawa Tengah.
                </p>
                <a href="#" class="inline-flex btn-cyan text-xs px-6 py-3.5 rounded-xl items-center gap-2">
                    <i class="fa-solid fa-map-location-dot"></i> Buka Peta Sebaran
                </a>
            </div>
        </div>
    </div>

    <!-- Slide 3 -->
    <div class="hero-slide" :class="current === 2 && 'active'">
        <div class="absolute inset-0 gradient-hero z-10"></div>
        <picture>
            <source srcset="<?= base_url('assets/img/hero/hero-sertifikasi-lahan.webp') ?>" type="image/webp">
            <img src="<?= base_url('assets/img/hero/hero-sertifikasi-lahan-opt.jpeg') ?>" alt="Hamparan lahan dan permukiman di Jawa Tengah" loading="lazy">
        </picture>
        <div class="absolute inset-0 z-20 max-w-7xl mx-auto px-6 lg:px-8 flex flex-col justify-center">
            <div class="max-w-2xl w-full" data-aos="fade-right" data-aos-duration="1000">
                <div class="inline-flex items-center gap-2 bg-[#f59e0b]/8 border border-[#f59e0b]/20 backdrop-blur-sm px-4 py-1.5 rounded-full text-[11px] text-[#8aacb0] font-medium mb-6">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400 animate-pulse"></span>
                    Sertifikasi & Legalitas Lahan (Akan Hadir)
                </div>
                <h2 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white mb-5">
                    Kepastian <span class="text-amber-400">Legalitas</span><br>Tanah Anda
                </h2>
                <p class="text-slate-400 text-sm sm:text-base leading-relaxed max-w-lg mb-8">
                    Fasilitasi penyelesaian sengketa, informasi sertifikasi lahan, dan pemetaan aset tanah untuk hunian yang aman di Jawa Tengah.
                </p>
                <a href="#" class="inline-flex bg-amber-500/10 hover:bg-amber-500/20 border border-amber-500/20 text-amber-400 transition-colors text-xs px-6 py-3.5 rounded-xl items-center gap-2 cursor-not-allowed">
                    <i class="fa-solid fa-lock"></i> Info Pertanahan
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
                    <button @click="goTo(i-1)" class="h-1 rounded-full transition-all duration-500" :class="current === i-1 ? 'w-8 bg-brand' : 'w-2 bg-brand-muted'"></button>
                </template>
                <span class="text-[10px] text-zinc-500 ml-2 font-mono" x-text="'0' + (current+1) + ' / 03'"></span>
            </div>
            <!-- Stat Capsules -->
            <div class="hidden sm:flex flex-wrap items-center gap-3">
                <div class="stat-capsule" data-aos="zoom-in" data-aos-delay="600">
                    <div class="stat-icon bg-[#d6fb00]/10 text-[#d6fb00]"><i class="fa-solid fa-house"></i></div>
                    <span class="text-white font-bold">209K+</span>
                    <span class="text-zinc-500 text-xs">Unit</span>
                </div>
                <div class="stat-capsule" data-aos="zoom-in" data-aos-delay="700">
                    <div class="stat-icon bg-[#6bcb77]/10 text-[#6bcb77]"><i class="fa-solid fa-certificate"></i></div>
                    <span class="text-white font-bold">58.6K+</span>
                    <span class="text-zinc-500 text-xs">Subsidi</span>
                </div>
                <div class="stat-capsule" data-aos="zoom-in" data-aos-delay="800">
                    <div class="stat-icon bg-[#00a3b5]/10 text-[#00a3b5]"><i class="fa-solid fa-location-crosshairs"></i></div>
                    <span class="text-white font-bold">2.7K+</span>
                    <span class="text-zinc-500 text-xs">Lokasi</span>
                </div>
                <div class="stat-capsule" data-aos="zoom-in" data-aos-delay="900">
                    <div class="stat-icon bg-[#f59e0b]/10 text-[#f59e0b]"><i class="fa-solid fa-map"></i></div>
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
<section class="w-full relative py-20 sm:py-28">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <!-- Left: Copy -->
            <div data-aos="fade-right">
                <div class="inline-flex items-center gap-2 bg-[#d6fb00]/10 border border-[#d6fb00]/15 px-3 py-1 rounded-full text-[11px] text-[#d6fb00] font-semibold mb-6">
                    <i class="fa-solid fa-building-columns"></i> Tentang Kami
                </div>
                <h3 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight leading-tight mb-5">
                    <?= !empty($settings['about_title']) ? $settings['about_title'] : 'Klinik Perumahan &<br>Kawasan <span class="text-[#d6fb00]">Permukiman</span>' ?>
                </h3>
                <p class="text-slate-400 text-sm leading-relaxed mb-6">
                    <?= !empty($settings['about_desc_1']) ? nl2br(htmlspecialchars($settings['about_desc_1'])) : 'Klinik PKP Disperakim Provinsi Jawa Tengah hadir sebagai pusat layanan informasi dan konsultasi terpadu untuk pembangunan rumah layak huni. Kami menghubungkan masyarakat, pengembang, dan pemerintah daerah dalam satu platform yang transparan dan akuntabel.' ?>
                </p>
                <p class="text-slate-500 text-sm leading-relaxed mb-8">
                    <?= !empty($settings['about_desc_2']) ? nl2br(htmlspecialchars($settings['about_desc_2'])) : 'Melalui integrasi data spasial, sistem informasi perumahan, dan layanan aduan digital, kami berkomitmen memastikan setiap warga Jawa Tengah memiliki akses terhadap hunian yang aman, layak, dan terjangkau.' ?>
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

            
            <div class="space-y-4">
                <!-- Card 1: Informasi -->
                <div class="group relative bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-2xl p-5 hover:bg-[#d6fb00]/10 transition-all duration-300 flex items-start gap-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="w-12 h-12 shrink-0 rounded-xl bg-[#d6fb00]/10 text-[#d6fb00] flex items-center justify-center text-xl shadow-[0_0_15px_rgba(214,251,0,0.2)]">
                        <i class="fa-solid fa-bullhorn"></i>
                    </div>
                    <div>
                        <h4 class="text-white font-bold text-lg mb-1 group-hover:text-[#d6fb00] transition-colors">Informasi Terpadu</h4>
                        <p class="text-zinc-400 text-xs leading-relaxed">Rekomendasi dan panduan program perumahan yang transparan sesuai dengan kebutuhan spesifik Anda.</p>
                    </div>
                </div>

                <!-- Card 2: Bantuan Teknis -->
                <div class="group relative bg-[#00a3b5]/5 border border-[#00a3b5]/20 rounded-2xl p-5 hover:bg-[#00a3b5]/10 transition-all duration-300 flex items-start gap-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="w-12 h-12 shrink-0 rounded-xl bg-[#00a3b5]/10 text-[#00a3b5] flex items-center justify-center text-xl shadow-[0_0_15px_rgba(0,163,181,0.2)]">
                        <i class="fa-solid fa-pen-ruler"></i>
                    </div>
                    <div>
                        <h4 class="text-white font-bold text-lg mb-1 group-hover:text-[#00a3b5] transition-colors">Bantuan Teknis</h4>
                        <p class="text-zinc-400 text-xs leading-relaxed">Fasilitasi desain prototipe bangunan, penyusunan RAB, serta dukungan pengukuran spasial lahan.</p>
                    </div>
                </div>

                <!-- Card 3: Pendampingan -->
                <div class="group relative bg-[#6bcb77]/5 border border-[#6bcb77]/20 rounded-2xl p-5 hover:bg-[#6bcb77]/10 transition-all duration-300 flex items-start gap-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="w-12 h-12 shrink-0 rounded-xl bg-[#6bcb77]/10 text-[#6bcb77] flex items-center justify-center text-xl shadow-[0_0_15px_rgba(107,203,119,0.2)]">
                        <i class="fa-solid fa-handshake"></i>
                    </div>
                    <div>
                        <h4 class="text-white font-bold text-lg mb-1 group-hover:text-[#6bcb77] transition-colors">Pendampingan</h4>
                        <p class="text-zinc-400 text-xs leading-relaxed">Konsultasi interaktif secara *online* dan *offline* bagi penerima bantuan maupun pengembang perumahan.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         SECTION 2-3 DIVIDER: SVG Construction Gradient Mesh
         ============================================================ -->
    <div class="absolute bottom-0 left-0 right-0 w-full pointer-events-none overflow-hidden z-0" style="height: 600px; transform: translateY(300px);">
        <!-- Ambient Radial Gradient Glows -->
        <svg class="absolute inset-0 w-full h-full" viewBox="0 0 1920 600" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
            <defs>
                <!-- Radial Gradients for Mesh Glow -->
                <radialGradient id="meshGlow1" cx="20%" cy="50%" r="50%">
                    <stop offset="0%" stop-color="#d6fb00" stop-opacity="0.25" />
                    <stop offset="50%" stop-color="#d6fb00" stop-opacity="0.08" />
                    <stop offset="100%" stop-color="#0a1a1f" stop-opacity="0" />
                </radialGradient>
                <radialGradient id="meshGlow2" cx="80%" cy="40%" r="50%">
                    <stop offset="0%" stop-color="#00a3b5" stop-opacity="0.30" />
                    <stop offset="50%" stop-color="#00a3b5" stop-opacity="0.10" />
                    <stop offset="100%" stop-color="#0a1a1f" stop-opacity="0" />
                </radialGradient>
            </defs>
            <rect width="100%" height="100%" fill="url(#meshGlow1)" />
            <rect width="100%" height="100%" fill="url(#meshGlow2)" />
        </svg>

        <!-- CSS Gradient Mask to smoothly fade the top and bottom edges into the body background -->
        <div class="absolute inset-0 bg-gradient-to-b from-[#0a1a1f] via-transparent via-50% to-[#0a1a1f] pointer-events-none z-10"></div>
    </div>
</section>

<!-- ============================================================
     SECTION 2.5: ETALASE PROGRAM
     ============================================================ -->
<section id="etalase-program" class="w-full py-20 sm:py-28 relative overflow-hidden" x-data="programEtalase()" @open-modal.window="openModal($event.detail)">
    <!-- Ambient glow -->
    <div class="absolute top-1/2 left-1/4 -translate-y-1/2 w-96 h-96 bg-[#d6fb00]/5 rounded-full blur-[100px] pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
            
            <!-- Left Side Grid (Ticket Style) -->
            <div class="lg:col-span-7 order-2 lg:order-1 relative">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 w-full">
                    
                    <!-- Ticket 1: KPR-FLPP -->
                    <div class="ticket-wrapper relative group transition-shadow duration-300" data-aos="slide-up" data-aos-delay="100">
                        <div class="absolute inset-0 bg-transparent rounded-2xl shadow-[0_10px_30px_0_rgba(0,0,0,0.4)] group-hover:shadow-[0_15px_35px_0_rgba(0,0,0,0.5)] transition-shadow duration-300 pointer-events-none z-0"></div>
                        <div onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'flpp' }))" class="ticket-base ticket-mask relative flex cursor-pointer min-h-[110px] z-10">
                            <div class="absolute inset-0 ticket-border pointer-events-none transition-colors duration-300 z-10"></div>
                            <div class="relative z-20 w-20 shrink-0 flex items-center justify-center border-r border-dashed border-white/40">
                                <i class="fa-solid fa-building-columns text-3xl text-[#00a3b5] group-hover:scale-110 group-hover:-rotate-3 transition-transform drop-shadow-[0_0_10px_rgba(0,163,181,0.5)]"></i>
                            </div>
                            <div class="relative z-20 p-4 flex-1 flex flex-col justify-center">
                                <div class="text-[9px] font-bold tracking-widest text-[#00a3b5] uppercase mb-1">MBR Fixed Income</div>
                                <h4 class="text-white font-bold text-base mb-1.5 leading-tight">KPR-FLPP</h4>
                                <p class="text-white/80 text-xs leading-relaxed line-clamp-2">Bunga flat 5% & cicilan ringan hingga 20 tahun.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Ticket 2: Oemah Lestari -->
                    <div class="ticket-wrapper relative group transition-shadow duration-300" data-aos="slide-up" data-aos-delay="200">
                        <div class="absolute inset-0 bg-transparent rounded-2xl shadow-[0_10px_30px_0_rgba(0,0,0,0.4)] group-hover:shadow-[0_15px_35px_0_rgba(0,0,0,0.5)] transition-shadow duration-300 pointer-events-none z-0"></div>
                        <div onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'oemah_lestari' }))" class="ticket-base ticket-mask relative flex cursor-pointer min-h-[110px] z-10">
                            <div class="absolute inset-0 ticket-border pointer-events-none transition-colors duration-300 z-10"></div>
                            <div class="relative z-20 w-20 shrink-0 flex items-center justify-center border-r border-dashed border-white/40">
                                <i class="fa-solid fa-leaf text-3xl text-[#6bcb77] group-hover:scale-110 group-hover:-rotate-3 transition-transform drop-shadow-[0_0_10px_rgba(107,203,119,0.5)]"></i>
                            </div>
                            <div class="relative z-20 p-4 flex-1 flex flex-col justify-center">
                                <div class="text-[9px] font-bold tracking-widest text-[#6bcb77] uppercase mb-1">MBR & Umum</div>
                                <h4 class="text-white font-bold text-base mb-1.5 leading-tight">Oemah Lestari</h4>
                                <p class="text-white/80 text-xs leading-relaxed line-clamp-2">Fasilitas pembiayaan rumah murah skema kredit bunga ringan.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Ticket 3: Peningkatan RTLH -->
                    <div class="ticket-wrapper relative group transition-shadow duration-300" data-aos="slide-up" data-aos-delay="300">
                        <div class="absolute inset-0 bg-transparent rounded-2xl shadow-[0_10px_30px_0_rgba(0,0,0,0.4)] group-hover:shadow-[0_15px_35px_0_rgba(0,0,0,0.5)] transition-shadow duration-300 pointer-events-none z-0"></div>
                        <div onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'rtlh' }))" class="ticket-base ticket-mask relative flex cursor-pointer min-h-[110px] z-10">
                            <div class="absolute inset-0 ticket-border pointer-events-none transition-colors duration-300 z-10"></div>
                            <div class="relative z-20 w-20 shrink-0 flex items-center justify-center border-r border-dashed border-white/40">
                                <i class="fa-solid fa-hammer text-3xl text-[#f59e0b] group-hover:scale-110 group-hover:-rotate-3 transition-transform drop-shadow-[0_0_10px_rgba(245,158,11,0.5)]"></i>
                            </div>
                            <div class="relative z-20 p-4 flex-1 flex flex-col justify-center">
                                <div class="text-[9px] font-bold tracking-widest text-[#f59e0b] uppercase mb-1">Miskin & Ekstrem</div>
                                <h4 class="text-white font-bold text-base mb-1.5 leading-tight">Peningkatan RTLH</h4>
                                <p class="text-white/80 text-xs leading-relaxed line-clamp-2">Bantuan perbaikan rumah via Bankeupemdes Rp 20 Juta.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Ticket 4: Stimulan PB -->
                    <div class="ticket-wrapper relative group transition-shadow duration-300" data-aos="slide-up" data-aos-delay="400">
                        <div class="absolute inset-0 bg-transparent rounded-2xl shadow-[0_10px_30px_0_rgba(0,0,0,0.4)] group-hover:shadow-[0_15px_35px_0_rgba(0,0,0,0.5)] transition-shadow duration-300 pointer-events-none z-0"></div>
                        <div onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'pb' }))" class="ticket-base ticket-mask relative flex cursor-pointer min-h-[110px] z-10">
                            <div class="absolute inset-0 ticket-border pointer-events-none transition-colors duration-300 z-10"></div>
                            <div class="relative z-20 w-20 shrink-0 flex items-center justify-center border-r border-dashed border-white/40">
                                <i class="fa-solid fa-trowel-bricks text-3xl text-[#d6fb00] group-hover:scale-110 group-hover:-rotate-3 transition-transform drop-shadow-[0_0_10px_rgba(214,251,0,0.5)]"></i>
                            </div>
                            <div class="relative z-20 p-4 flex-1 flex flex-col justify-center">
                                <div class="text-[9px] font-bold tracking-widest text-[#d6fb00] uppercase mb-1">Bencana & Relokasi</div>
                                <h4 class="text-white font-bold text-base mb-1.5 leading-tight">Stimulan PB</h4>
                                <p class="text-white/80 text-xs leading-relaxed line-clamp-2">Bantuan material Rp 40 Juta untuk relokasi dan korban bencana.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Ticket 5: Program Rumah Apung -->
                    <div class="ticket-wrapper relative group transition-shadow duration-300 sm:col-span-2 md:col-span-1 xl:col-span-2" data-aos="slide-up" data-aos-delay="500">
                        <div class="absolute inset-0 bg-transparent rounded-2xl shadow-[0_10px_30px_0_rgba(0,0,0,0.4)] group-hover:shadow-[0_15px_35px_0_rgba(0,0,0,0.5)] transition-shadow duration-300 pointer-events-none z-0"></div>
                        <div onclick="window.dispatchEvent(new CustomEvent('open-modal', { detail: 'rumah_apung' }))" class="ticket-base ticket-mask relative flex cursor-pointer min-h-[110px] z-10">
                            <div class="absolute inset-0 ticket-border pointer-events-none transition-colors duration-300 z-10"></div>
                            <div class="relative z-20 w-20 shrink-0 flex items-center justify-center border-r border-dashed border-white/40">
                                <i class="fa-solid fa-water text-3xl text-[#00a3b5] group-hover:scale-110 group-hover:-rotate-3 transition-transform drop-shadow-[0_0_10px_rgba(0,163,181,0.5)]"></i>
                            </div>
                            <div class="relative z-20 p-4 flex-1 flex flex-col justify-center">
                                <div class="text-[9px] font-bold tracking-widest text-[#00a3b5] uppercase mb-1">Kawasan Pesisir</div>
                                <h4 class="text-white font-bold text-base mb-1.5 leading-tight">Program Rumah Apung</h4>
                                <p class="text-white/80 text-xs leading-relaxed line-clamp-2">Inovasi desain rumah tahan banjir rob untuk pesisir (Timbulsloko).</p>
                            </div>
                        </div>
                    </div>

                </div>
                
                <!-- Instruction Indicator -->
                <div class="mt-6 w-full flex items-center justify-center gap-4 text-zinc-300 text-sm font-medium" data-aos="fade-up" data-aos-delay="600">
                    <div class="relative w-12 h-12 shrink-0 bg-white/5 rounded-full flex items-center justify-center overflow-hidden border border-white/10">
                        <i class="fa-solid fa-ticket text-white animate-pulse relative z-10"></i>
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-[#d6fb00]/20 to-transparent -translate-x-full animate-[shimmer_2s_infinite]"></div>
                    </div>
                    Pilih tiket untuk membaca detail dan prasyarat program selengkapnya
                </div>
            </div>

            <!-- Right Copy -->
            <div class="lg:col-span-5 flex flex-col justify-center order-1 lg:order-2 xl:pl-12" data-aos="fade-left">
                <div class="inline-flex items-center gap-2 bg-[#d6fb00]/10 border border-[#d6fb00]/15 px-3 py-1 rounded-full text-[11px] text-[#d6fb00] font-semibold mb-6 w-max">
                    <i class="fa-solid fa-house-chimney-window"></i> Etalase Program
                </div>
                <h3 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight leading-[1.1] mb-5">
                    Pilih Program<br>Sesuai <span class="text-[#d6fb00]">Kebutuhan</span>
                </h3>
                <p class="text-slate-400 text-sm leading-relaxed mb-8">
                    Temukan berbagai skema bantuan perumahan dan kawasan permukiman dari Pemerintah Provinsi Jawa Tengah. Klik pada tiket program untuk melihat detail dan prasyaratnya.
                </p>

                <!-- Cek NIK Form -->
                <div class="w-full" data-aos="fade-up" data-aos-delay="400">
                    <label class="block text-xs font-bold text-[#8aacb0] uppercase tracking-widest mb-3">Cek Kelayakan Penerima Program</label>
                    <form action="<?= base_url('Program/diagnosa/umum') ?>" method="GET" class="relative flex items-center w-full">
                        <div class="absolute left-4 text-[#8aacb0]">
                            <i class="fa-solid fa-id-card"></i>
                        </div>
                        <input type="text" name="nik" placeholder="Masukkan NIK KTP (16 Digit)..." class="w-full bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl py-3.5 pl-11 pr-[110px] text-sm text-white placeholder-zinc-500 focus:outline-none focus:border-[#d6fb00]/50 focus:bg-[#d6fb00]/10 transition-colors" required minlength="16" maxlength="16" pattern="[0-9]{16}" title="Masukkan 16 digit NIK yang valid" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        <button type="submit" class="absolute right-1.5 top-1.5 bottom-1.5 px-4 bg-[#d6fb00] hover:bg-white text-[#0a1a1f] font-bold text-xs rounded-lg transition-colors flex items-center gap-2 shadow-[0_0_15px_rgba(214,251,0,0.2)] hover:shadow-[0_0_20px_rgba(255,255,255,0.4)]">
                            Cek NIK <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </form>

                    <!-- Skenario NIK -->
                    <div class="mt-4 bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl p-3 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-2 opacity-10"><i class="fa-solid fa-vial text-3xl"></i></div>
                        <p class="text-[11px] text-[#8aacb0] mb-2"><i class="fa-solid fa-flask"></i> <strong>Skenario Simulasi:</strong> Klik angka untuk menyalin</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-white/80">
                            <div class="bg-black/20 rounded-lg p-2 border border-white/5 hover:border-[#d6fb00]/30 transition-colors">
                                <button type="button" onclick="document.querySelector('input[name=\'nik\']').value='3329000000000001'" class="font-mono text-[#d6fb00] hover:text-white font-bold mb-0.5 transition-colors">3329000000000001</button>
                                <p class="text-[10px] text-zinc-400 leading-tight">Data Lengkap (Kalkulator terisi)</p>
                            </div>
                            <div class="bg-black/20 rounded-lg p-2 border border-white/5 hover:border-[#d6fb00]/30 transition-colors">
                                <button type="button" onclick="document.querySelector('input[name=\'nik\']').value='3329000000000002'" class="font-mono text-[#d6fb00] hover:text-white font-bold mb-0.5 transition-colors">3329000000000002</button>
                                <p class="text-[10px] text-zinc-400 leading-tight">Hanya Data Diri (Isi manual)</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Alpine.js -->
    <template x-teleport="body">
        <div x-show="isModalOpen" class="relative z-[100]" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div x-show="isModalOpen"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-[#0a1a1f]/90 backdrop-blur-sm transition-opacity"
                 @click="closeModal()"></div>

            <!-- Modal Panel Container -->
            <div class="fixed inset-0 z-10 w-screen overflow-y-auto" data-lenis-prevent>
                <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                    <div x-show="isModalOpen"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         class="relative transform overflow-hidden rounded-3xl bg-[#0f2a30] text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-6xl xl:max-w-7xl border border-white/10"
                         @click.stop>
                        
                        <!-- Close Button -->
                        <button @click="closeModal()" class="absolute top-4 right-4 w-8 h-8 flex items-center justify-center rounded-full bg-black/40 text-white hover:bg-white hover:text-black transition-colors z-30">
                            <i class="fa-solid fa-xmark"></i>
                        </button>

                        <style>
                            .modal-image-mask {
                                -webkit-mask-image: linear-gradient(to top, transparent 0%, black 50%);
                                mask-image: linear-gradient(to top, transparent 0%, black 50%);
                            }
                            @media (min-width: 768px) {
                                .modal-image-mask {
                                    -webkit-mask-image: linear-gradient(to left, transparent 0%, black 40%);
                                    mask-image: linear-gradient(to left, transparent 0%, black 40%);
                                }
                            }
                        </style>
                        <template x-if="activeId && programs[activeId]">
                            <div class="flex flex-col md:flex-row w-full h-full">
                                <!-- Modal Left Image Banner -->
                                <div class="relative aspect-square w-full md:w-5/12 shrink-0 overflow-hidden">
                                    
                                    <img :src="programs[activeId].image" alt="Program Image" class="absolute inset-0 w-full h-full object-cover modal-image-mask">
                                    
                                    <!-- Title on mobile only (overlay on image) -->
                                    <div class="absolute bottom-6 left-6 z-20 md:hidden">
                                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/10 rounded-lg border border-white/10 backdrop-blur-md text-[10px] font-bold tracking-widest uppercase mb-3 text-white">
                                            <span class="w-2 h-2 rounded-full" :style="'background-color: ' + programs[activeId].dotColor"></span>
                                            <span x-text="programs[activeId].badge"></span>
                                        </div>
                                        <h3 class="text-3xl font-bold text-white leading-tight" x-html="programs[activeId].title"></h3>
                                    </div>
                                </div>

                                <!-- Modal Right Content -->
                                <div class="p-6 sm:p-8 flex-1 flex flex-col justify-center">
                                    <!-- Header text for desktop -->
                                    <div class="hidden md:block mb-6">
                                        <div class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/5 rounded-lg border border-white/10 text-[10px] font-bold tracking-widest uppercase mb-3 text-white">
                                            <span class="w-2 h-2 rounded-full" :style="'background-color: ' + programs[activeId].dotColor"></span>
                                            <span x-text="programs[activeId].badge"></span>
                                        </div>
                                        <h3 class="text-3xl font-bold text-white leading-tight" x-html="programs[activeId].title"></h3>
                                    </div>
                                    <!-- Definition -->
                                    <div class="mb-6">
                                        <h4 class="text-xs font-bold text-[#8aacb0] uppercase tracking-widest mb-2"><i class="fa-solid fa-book-open mr-1"></i> Definisi Operasional</h4>
                                        <p class="text-zinc-300 text-sm leading-relaxed" x-text="programs[activeId].definition"></p>
                                    </div>

                                    <!-- Terms -->
                                    <div class="mb-6">
                                        <h4 class="text-xs font-bold text-[#8aacb0] uppercase tracking-widest mb-2"><i class="fa-solid fa-list-check mr-1"></i> Syarat & Ketentuan</h4>
                                        <p class="text-zinc-300 text-sm leading-relaxed" x-text="programs[activeId].terms"></p>
                                        
                                        <template x-if="programs[activeId].options">
                                            <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-3">
                                                <template x-for="(opt, idx) in programs[activeId].options" :key="idx">
                                                    <div class="bg-[#0a1a1f]/50 rounded-xl p-4 border border-white/10 hover:border-[#d6fb00]/30 hover:bg-[#d6fb00]/5 transition-all group flex flex-col h-full">
                                                        <div class="flex items-center gap-3 mb-3">
                                                            <div class="w-8 h-8 shrink-0 rounded-lg bg-[#d6fb00]/10 text-[#d6fb00] group-hover:bg-[#d6fb00] group-hover:text-[#0a1a1f] flex items-center justify-center text-sm transition-colors">
                                                                <i class="fa-solid" :class="opt.icon || 'fa-house-chimney'"></i>
                                                            </div>
                                                            <h5 class="text-white font-bold text-sm leading-tight" x-text="opt.name"></h5>
                                                        </div>
                                                        <p class="text-zinc-400 text-xs leading-relaxed flex-1" x-text="opt.desc"></p>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Budget/Source -->
                                    <div class="mb-8">
                                        <h4 class="text-xs font-bold text-[#8aacb0] uppercase tracking-widest mb-2"><i class="fa-solid fa-coins mr-1"></i> Sumber Anggaran / Pembiayaan</h4>
                                        <div class="inline-flex bg-[#163a42] border border-[#1d4d58] rounded-xl px-4 py-3 text-sm text-emerald-400 font-semibold items-center gap-3">
                                            <i class="fa-solid fa-wallet text-emerald-500 text-lg"></i>
                                            <span x-text="programs[activeId].budget"></span>
                                        </div>
                                    </div>

                                    <!-- Action Button -->
                                    <div class="flex justify-end gap-3 pt-5 border-t border-white/10">
                                        <button @click="closeModal()" class="px-5 py-2.5 rounded-xl text-sm font-semibold text-zinc-400 hover:text-white transition-colors">Tutup</button>
                                        <a :href="'<?= base_url('Program/diagnosa/') ?>' + activeId" class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-white text-[#0a1a1f] hover:bg-zinc-200 transition-colors inline-flex items-center gap-2">
                                            Cek Kelayakan NIK <i class="fa-solid fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <!-- Alpine.js Logic -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('programEtalase', () => ({
                isModalOpen: false,
                activeId: '',
                activeData: {
                    title: '',
                    badge: '',
                    dotColor: '',
                    colorFrom: '',
                    colorTo: '',
                    definition: '',
                    terms: '',
                    budget: '',
                    options: false,
                    image: ''
                },
                programs: {
                    'flpp': {
                        title: 'KPR-FLPP<br>Rumah Subsidi',
                        badge: 'MBR Fixed Income',
                        dotColor: '#00a3b5',
                        colorFrom: '#0f4c5c',
                        colorTo: '#062b35',
                        definition: 'Skema pembiayaan perumahan subsidi bagi Masyarakat Berpenghasilan Rendah (MBR).',
                        terms: 'Bunga flat tetap sebesar 5% sepanjang tenor, Uang Muka (DP) sangat ringan mulai 1%, dengan pilihan tenor (jangka waktu angsuran) panjang hingga 20 tahun.',
                        budget: 'APBN (Kuota Nasional)',
                        image: '<?= base_url('assets/img/program/01_subsidif_lpp.avif') ?>'
                    },
                    'oemah_lestari': {
                        title: 'Oemah<br>Lestari',
                        badge: 'MBR & Umum',
                        dotColor: '#6bcb77',
                        colorFrom: '#1b4332',
                        colorTo: '#0d2818',
                        definition: 'Program fasilitas pembiayaan rumah murah bagi MBR (Masyarakat Berpenghasilan Rendah) dengan skema kredit bunga ringan (8% flat) dengan tenor panjang hingga 15 tahun serta memenuhi kaidah "Bangunan Hijau" sebagai amanat SDG serta berkolaborasi dengan "BPR-BRK".',
                        terms: 'Skema kredit kolaborasi pembiayaan BPR-BRK dengan bunga ringan (8% flat), tenor penyicilan maksimal hingga 15 tahun.',
                        budget: 'Kolaborasi BPR-BRK',
                        image: '<?= base_url('assets/img/program/02_oemahletari.avif') ?>'
                    },
                    'rtlh': {
                        title: 'Peningkatan<br>Kualitas RTLH',
                        badge: 'Miskin & Ekstrem',
                        dotColor: '#f59e0b',
                        colorFrom: '#4a154b',
                        colorTo: '#290a2a',
                        definition: 'Program perbaikan atau renovasi rumah bagi masyarakat miskin yang rumahnya masuk kategori tidak layak.',
                        terms: 'Penerima harus terdaftar dalam DTKS. Dinyatakan rusak jika memenuhi 2 dari 3 unsur (Atap, Lantai, Dinding). Dieksekusi secara swadaya padat karya oleh warga lokal.',
                        budget: 'Bankeupemdes (Rp 20 Juta / Penerima)',
                        image: '<?= base_url('assets/img/program/03_rtlh.avif') ?>'
                    },
                    'pb': {
                        title: 'Stimulan<br>Pembangunan Baru',
                        badge: 'Bencana & Relokasi',
                        dotColor: '#d6fb00',
                        colorFrom: '#4d5c00',
                        colorTo: '#262e00',
                        definition: 'Bantuan stimulan material bangunan senilai Rp 40 Juta untuk pembangunan rumah baru.',
                        terms: 'Terbagi dalam 3 skema penanganan strategis:',
                        options: [
                            { name: 'PB Backlog', icon: 'fa-users-line', desc: 'Khusus bagi warga MBR yang masih menumpang (>1 KK dalam satu rumah) atau menyewa, namun sudah memiliki lahan/tanah hak milik pribadi yang sah dan siap bangun.' },
                            { name: 'PB Relokasi', icon: 'fa-truck-fast', desc: 'Bantuan struktur pracetak RUSPIN (Rumah Unggul Sistem Panel Instan). Dikhususkan bagi warga yang tinggal di kawasan kumuh berat atau rawan bencana alam yang direlokasi ke lahan baru yang aman.' },
                            { name: 'PB Bencana', icon: 'fa-house-crack', desc: 'Bantuan material pembangunan rumah baru bagi warga yang rumah asalnya mengalami kerusakan kategori berat atau roboh total diakibatkan oleh kejadian bencana alam tak terduga.' }
                        ],
                        budget: 'Bansos Pemprov Jawa Tengah',
                        image: '<?= base_url('assets/img/program/04_Bantuan.avif') ?>'
                    },
                    'rumah_apung': {
                        title: 'Program<br>Rumah Apung',
                        badge: 'Kawasan Pesisir',
                        dotColor: '#60a5fa',
                        colorFrom: '#003049',
                        colorTo: '#00121c',
                        definition: 'Program inovasi desain pembangunan rumah adaptif untuk bertahan dari genangan air.',
                        terms: 'Merupakan solusi NO ONE LEFT BEHIND bagi masyarakat pesisir terdampak rob permanen dan penurunan muka tanah ekstrem (180-300cm). Target utama: Demak.',
                        budget: 'Bantuan Khusus (Pilot Project)',
                        image: '<?= base_url('assets/img/program/05_areakumuh.avif') ?>'
                    }
                },
                openModal(id) {
                    if (this.programs[id]) {
                        this.activeData = this.programs[id];
                        this.activeId = id;
                        this.isModalOpen = true;
                        document.body.style.overflow = 'hidden';
                    }
                },
                closeModal() {
                    this.isModalOpen = false;
                    setTimeout(() => {
                        document.body.style.overflow = '';
                    }, 300);
                }
            }));
        });
    </script>
</section>

<!-- ============================================================
     SECTION 3: LAYANAN — Bento Grid
     ============================================================ -->
<section id="layanan-kami" class="w-full py-20 sm:py-28 scroll-mt-20 relative overflow-hidden">
    <!-- Grid Pattern Overlay with Smooth Mask Fade -->
    <div class="absolute inset-0 pattern-grid pointer-events-none z-0" style="mask-image: linear-gradient(to bottom, transparent, white 15%, white 85%, transparent); -webkit-mask-image: linear-gradient(to bottom, transparent, white 15%, white 85%, transparent);"></div>

    <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
        <div class="text-center mb-14" data-aos="fade-down">
            <div class="inline-flex items-center gap-2 bg-[#d6fb00]/8 border border-[#d6fb00]/20 px-3 py-1 rounded-full text-[11px] text-[#8aacb0] font-medium mb-4">
                <i class="fa-solid fa-layer-group text-[#d6fb00]"></i> Pilar Layanan
            </div>
            <h3 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight mb-3">Layanan <span class="text-[#d6fb00]">Kami</span></h3>
            <p class="text-zinc-500 text-sm max-w-md mx-auto">Pilih kategori layanan sesuai kebutuhan Anda</p>
        </div>

        <style>
            .tl-bento-card:hover { border-color: var(--brand-300); background-color: rgba(15, 42, 48, 0.95) !important; box-shadow: 0 10px 30px -15px rgba(214,251,0,0.15); }
            .bento-featured:hover { border-color: var(--brand-300); box-shadow: 0 10px 30px -15px rgba(214,251,0,0.15); }
        </style>

        <style>
            /* Custom layout to bypass missing Tailwind JIT classes */
            .tl-bento-wrapper {
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
            }
            .tl-bento-half {
                display: flex;
                flex-direction: column;
                width: 100%;
                gap: 1.5rem;
            }
            .tl-bento-inner-row {
                display: flex;
                flex-direction: column;
                gap: 1.5rem;
                flex: 1;
            }
            .tl-bento-card-grow {
                flex: 1;
                display: flex;
                flex-direction: column;
            }
            @media (min-width: 1024px) {
                .tl-bento-wrapper {
                    flex-direction: row;
                }
                .tl-bento-half {
                    width: 50%;
                }
                .tl-bento-inner-row {
                    flex-direction: row;
                }
            }
            
            /* Custom Button Styles to bypass JIT compiler */
            .tl-btn-base {
                display: inline-flex;
                align-items: center;
                gap: 0.375rem;
                padding: 0.375rem 0.875rem;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 600;
                transition: all 0.3s ease;
            }
            .tl-btn-emerald {
                background-color: rgba(16, 185, 129, 0.1);
                color: #34d399;
                border: 1px solid rgba(16, 185, 129, 0.2);
            }
            .group:hover .tl-btn-emerald {
                background-color: #10b981;
                color: #ffffff;
            }
            .tl-btn-cyan {
                background-color: rgba(0, 163, 181, 0.1);
                color: #00a3b5;
                border: 1px solid rgba(0, 163, 181, 0.2);
            }
            .group:hover .tl-btn-cyan {
                background-color: #00a3b5;
                color: #ffffff;
            }
            .tl-btn-green {
                background-color: rgba(107, 203, 119, 0.1);
                color: #6bcb77;
                border: 1px solid rgba(107, 203, 119, 0.2);
            }
            .group:hover .tl-btn-green {
                background-color: #6bcb77;
                color: #ffffff;
            }
        </style>

        <div class="tl-bento-wrapper">
            <!-- Kiri: Masyarakat Umum (Bento Terbesar) -->
            <div class="tl-bento-half">
                <a href="<?= base_url('umum') ?>" class="w-full group rounded-3xl p-8 sm:p-12 flex flex-col transition-all duration-500 relative overflow-hidden tl-bento-card-grow" style="background-color: var(--bg-card); min-height: 450px; border: 1px solid rgba(214, 251, 0, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2);" data-aos="fade-right" data-aos-delay="100">
                    <!-- Large Watermark Icon -->
                    <i class="fa-solid fa-people-group absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 240px; right: -2rem; bottom: -2rem; color: #d6fb00; opacity: 0.05;"></i>

                    <div class="relative z-10">
                        <i class="fa-solid fa-users mb-8 transition-transform duration-500 group-hover:scale-110 group-hover:-translate-y-2" style="font-size: 56px; color: #d6fb00; filter: drop-shadow(0 0 15px rgba(214, 251, 0, 0.4));"></i>
                        <h4 class="text-white font-black text-3xl sm:text-4xl lg:text-5xl mb-4 group-hover:text-[#d6fb00] transition-colors tracking-tight">Masyarakat Umum</h4>
                        <p class="text-zinc-400 text-sm sm:text-base leading-relaxed max-w-lg mb-8">Pusat informasi perumahan komprehensif, program bantuan rumah layak huni, cek kelayakan subsidi, simulasi KPR interaktif, dan layanan pengaduan.</p>
                    </div>
                    
                    <div class="relative z-10" style="margin-top: auto; padding-top: 2rem;">
                        <div class="inline-flex items-center gap-3 text-[#0a1a1f] text-sm font-bold bg-[#d6fb00] px-6 py-3 rounded-full hover:bg-white transition-colors w-max">
                            <span>Jelajahi Layanan</span>
                            <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Kanan: 3 Bento Lebih Kecil -->
            <div class="tl-bento-half">
                <!-- Baris Atas Kanan -->
                <div class="tl-bento-inner-row">
                    <!-- Pengembang -->
                    <a href="<?= base_url('pengembang') ?>" class="group rounded-3xl p-8 transition-all duration-500 relative overflow-hidden tl-bento-card-grow" style="background-color: var(--bg-card); border: 1px solid rgba(16, 185, 129, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2);" data-aos="fade-up" data-aos-delay="200">
                        <!-- Watermark Icon -->
                        <i class="fa-solid fa-city absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 160px; right: -1.5rem; bottom: -1.5rem; color: #34d399; opacity: 0.05;"></i>
                        
                        <div class="relative z-10">
                            <i class="fa-solid fa-building mb-5 transition-transform duration-500 group-hover:scale-110" style="font-size: 40px; color: #34d399; filter: drop-shadow(0 0 12px rgba(52, 211, 153, 0.4));"></i>
                            <h4 class="text-white font-bold text-xl mb-2 group-hover:text-emerald-400 transition-colors">Pengembang</h4>
                            <p class="text-zinc-500 text-sm leading-relaxed">Registrasi mandiri, publikasi proyek perumahan, dan dokumen petunjuk teknis.</p>
                        </div>
                        <div class="relative z-10" style="margin-top: auto; padding-top: 2rem;">
                            <div class="tl-btn-base tl-btn-emerald">
                                <span>Selengkapnya</span>
                                <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                            </div>
                        </div>
                    </a>

                    <!-- KKN & Magang -->
                    <a href="<?= base_url('kemitraan') ?>" class="group rounded-3xl p-8 transition-all duration-500 relative overflow-hidden tl-bento-card-grow" style="background-color: var(--bg-card); border: 1px solid rgba(0, 163, 181, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2);" data-aos="fade-up" data-aos-delay="300">
                        <!-- Watermark Icon -->
                        <i class="fa-solid fa-user-graduate absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 160px; right: -1.5rem; bottom: -1.5rem; color: #00a3b5; opacity: 0.05;"></i>

                        <div class="relative z-10">
                            <i class="fa-solid fa-graduation-cap mb-5 transition-transform duration-500 group-hover:scale-110" style="font-size: 40px; color: #00a3b5; filter: drop-shadow(0 0 12px rgba(0, 163, 181, 0.4));"></i>
                            <h4 class="text-white font-bold text-xl mb-2 group-hover:text-[#00a3b5] transition-colors">KKN & Magang</h4>
                            <p class="text-zinc-500 text-sm leading-relaxed">Kolaborasi program tematik, pendaftaran magang mahasiswa, dan administrasi.</p>
                        </div>
                        <div class="relative z-10" style="margin-top: auto; padding-top: 2rem;">
                            <div class="tl-btn-base tl-btn-cyan">
                                <span>Selengkapnya</span>
                                <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Baris Bawah Kanan: Kabupaten / Kota -->
                <a href="<?= base_url('listkabupaten') ?>" class="group rounded-3xl p-8 flex flex-col transition-all duration-500 relative overflow-hidden tl-bento-card-grow" style="background-color: var(--bg-card); border: 1px solid rgba(107, 203, 119, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2);" data-aos="fade-up" data-aos-delay="400">
                    <!-- Watermark Icon -->
                    <i class="fa-solid fa-map absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 200px; right: -1.5rem; bottom: -2.5rem; color: #6bcb77; opacity: 0.05;"></i>

                    <div class="relative z-10">
                        <i class="fa-solid fa-map-location-dot mb-5 transition-transform duration-500 group-hover:scale-110" style="font-size: 48px; color: #6bcb77; filter: drop-shadow(0 0 12px rgba(107, 203, 119, 0.4));"></i>
                        <h4 class="text-white font-bold text-2xl mb-2 group-hover:text-[#6bcb77] transition-colors">Kabupaten / Kota</h4>
                        <p class="text-zinc-500 text-sm leading-relaxed max-w-md">Data intervensi permukiman kumuh terpadu dan monitoring spasial secara mendetail.</p>
                    </div>
                    <div class="relative z-10" style="margin-top: auto; padding-top: 2rem;">
                        <div class="tl-btn-base tl-btn-green">
                            <span>Selengkapnya</span>
                            <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     SECTION 4: TAHAPAN — Pipeline Timeline
     ============================================================ -->
<section id="tahapan" class="w-full relative overflow-hidden py-20 sm:py-28">
    
    <!-- Background Grid -->
    <div class="absolute inset-0 pointer-events-none z-0" style="background-image: linear-gradient(to right, rgba(214,251,0,0.05) 1px, transparent 1px), linear-gradient(to bottom, rgba(214,251,0,0.05) 1px, transparent 1px); background-size: 32px 32px; mask-image: linear-gradient(to bottom, transparent, white 15%, white 85%, transparent); -webkit-mask-image: linear-gradient(to bottom, transparent, white 15%, white 85%, transparent);"></div>

    <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
        
        <!-- Header -->
        <div class="text-center mb-16 sm:mb-24" data-aos="fade-down">
            <div class="inline-flex items-center gap-2 bg-[#d6fb00]/8 border border-[#d6fb00]/20 px-3 py-1 rounded-full text-[11px] text-[#8aacb0] font-medium mb-4 shadow-sm">
                <i class="fa-solid fa-bars-progress text-[#d6fb00]"></i> Alur Kerja Pembangunan
            </div>
            
            <h3 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight mb-3">
                Tahapan <span class="text-[#d6fb00]">Membangun</span> Rumah
            </h3>
            
            <p class="text-zinc-500 text-sm max-w-md mx-auto">
                Ikuti panduan lengkap dan terstruktur untuk mewujudkan rumah tinggal impian Anda
            </p>
        </div>

        <!-- Struktur Pipeline / Timeline -->
        <style>
            /* Custom styles for Timeline to bypass Tailwind JIT compilation */
            .tl-line-v { left: 35px; border-left: 2px dashed; }
            .tl-card-wrap { width: 100%; }
            .tl-bg-icon-wrap { opacity: 0.03; }
            .group:hover .tl-bg-icon-wrap { opacity: 0.08; }
            .tl-bg-icon { font-size: 140px; line-height: 1; }
            
            @media (min-width: 768px) {
                .tl-row { min-height: 220px; }
                .tl-line-v { left: 50%; margin-left: -1px; }
                .tl-card-wrap { width: 45%; }
                .tl-line-h-right { right: 50%; width: 10%; border-top: 2px dashed; }
                .tl-line-h-left { left: 50%; width: 10%; border-top: 2px dashed; }
                .tl-justify-start { justify-content: flex-start; }
                .tl-justify-end { justify-content: flex-end; }
                .tl-pl-14 { padding-left: 3.5rem; }
                .tl-pr-14 { padding-right: 3.5rem; }
            }
            
            .group:hover .tl-dot { box-shadow: 0 0 15px rgba(214,251,0,0.4); border-color: #d6fb00; }
            .tl-card-inner:hover { box-shadow: 0 10px 30px -15px rgba(214,251,0,0.15); }
            
            .tl-line-border { border-color: rgba(214, 251, 0, 0.3); }
            .group:hover .tl-line-border { border-color: rgba(214, 251, 0, 0.8); }
            
            .tl-mesh-bg {
                background-color: #0f2a30;
                background-image: 
                    radial-gradient(circle at 100% 0%, rgba(0, 163, 181, 0.12) 0%, transparent 60%),
                    radial-gradient(circle at 0% 100%, rgba(214, 251, 0, 0.05) 0%, transparent 50%);
            }
            .tl-row { min-height: 180px; }
            .tl-bg-icon-wrap { opacity: 0.1; }
            .group:hover .tl-bg-icon-wrap { opacity: 0.2; }
            .tl-line-border { border-color: rgba(214,251,0,0.3); }
            .tl-line-top { top: 0; bottom: 50%; }
            .tl-line-bottom { top: 50%; bottom: 0; }
            .tl-card-visible {
                opacity: 1 !important;
                --tw-translate-x: 0px !important;
            }
        </style>
        <div class="relative" id="timeline-container">
            <!-- SVG Path for Timeline -->
            <svg class="absolute inset-0 w-full h-full pointer-events-none z-0" id="timeline-svg">
                <path id="timeline-path" fill="none" stroke="#d6fb00" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="filter: drop-shadow(0 0 8px rgba(214,251,0,0.5));"></path>
            </svg>
            <div class="flex flex-col relative z-10">
                <?php
                $steps = [
                    [
                        'id' => '01',
                        'title' => 'Perencanaan',
                        'description' => 'Desain arsitektur, perhitungan Rencana Anggaran Biaya (RAB) terperinci, serta pengurusan perizinan pembangunan resmi.',
                        'icon' => 'fa-ruler-combined'
                    ],
                    [
                        'id' => '02',
                        'title' => 'Konstruksi',
                        'description' => 'Pemilihan material bersertifikasi SNI, penunjukan kontraktor tepercaya, dan pelaksanaan pembangunan fisik lapangan.',
                        'icon' => 'fa-helmet-safety'
                    ],
                    [
                        'id' => '03',
                        'title' => 'Pengawasan',
                        'description' => 'Pemeriksaan mutu struktur beton dan baja, evaluasi berkala kemajuan, serta pemenuhan standar keselamatan gedung.',
                        'icon' => 'fa-clipboard-check'
                    ],
                    [
                        'id' => '04',
                        'title' => 'Pemanfaatan',
                        'description' => 'Hunian fungsional harian, pemeliharaan berkala sarana prasarana, serta pemantauan keandalan fungsi hunian jangka panjang.',
                        'icon' => 'fa-house-chimney'
                    ]
                ];

                $totalSteps = count($steps);
                foreach ($steps as $index => $step): 
                    $isEven = ($index % 2 === 0);
                    $icon = $step['icon'];
                ?>
                <div class="relative flex flex-col md:flex-row items-center w-full <?= $isEven ? 'tl-justify-start' : 'tl-justify-end' ?> py-5 md:py-0 tl-row group">
                    
                    <!-- Titik Pipeline -->
                    <div class="absolute left-[24px] md:left-1/2 top-1/2 -translate-y-1/2 md:-translate-x-1/2 w-6 h-6 rounded-full border-2 bg-[#0a1a1f] z-10 transition-all duration-500 tl-line-border tl-dot <?= $index === 0 ? '' : 'scale-0 opacity-0' ?>" id="tl-dot-<?= $index ?>"></div>

                    <!-- Pembungkus Kartu -->
                    <div class="pl-16 md:pl-0 <?= $isEven ? 'tl-pr-14' : 'tl-pl-14' ?> tl-card-wrap">
                        <!-- Kartu -->
                        <div class="relative tl-mesh-bg border border-[#d6fb00]/20 rounded-2xl p-6 md:p-8 transition-all duration-700 overflow-hidden hover:-translate-y-2 hover:border-[#d6fb00]/35 h-full tl-card-inner opacity-0 translate-x-12 <?= $isEven ? 'md:-translate-x-12' : '' ?>" id="tl-card-<?= $index ?>">
                            
                            <!-- Overlay Icon Background (Sisi Kanan) -->
                            <div class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-1/4 transition-all duration-700 group-hover:scale-110 pointer-events-none text-[#d6fb00] tl-bg-icon-wrap">
                                <i class="fa-solid <?= $icon ?> tl-bg-icon"></i>
                            </div>

                            <div class="relative z-10 flex flex-col md:flex-row gap-6 h-full">
                                <!-- Icon Utama (Tanpa Wadah) -->
                                <div class="flex-shrink-0 transition-transform duration-500 group-hover:scale-110 drop-shadow-[0_0_8px_rgba(214,251,0,0.3)] text-[#d6fb00] pt-1">
                                    <i class="fa-solid <?= $icon ?> text-3xl"></i>
                                </div>
                                
                                <!-- Konten -->
                                <div class="flex-1 pb-6 md:pb-0">
                                    <h3 class="text-xl sm:text-2xl font-bold text-white mb-2 group-hover:text-[#d6fb00] transition-colors">
                                        <?= $step['title'] ?>
                                    </h3>
                                    <p class="text-zinc-400 leading-relaxed text-xs sm:text-sm group-hover:text-zinc-300 transition-colors">
                                        <?= $step['description'] ?>
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Nomor -->
                            <div class="absolute bottom-5 right-6 font-black text-2xl text-[#1a3d45] transition-all duration-500 group-hover:text-[#d6fb00] group-hover:scale-110 group-hover:-translate-y-1 z-10">
                                <?= $step['id'] ?>
                            </div>
                        </div>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</section>

<!-- ============================================================
     SECTION 5: BANK DESAIN — True Carousel
     ============================================================ -->
<section id="bank-desain" class="w-full py-20 sm:py-28 scroll-mt-20" x-data="designCarousel()" @designs-loaded.window="$nextTick(() => recalc())">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row items-start sm:items-end justify-between mb-10 gap-4" data-aos="fade-down">
            <div>
                <h3 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white"><span style="color: #d6fb00;">Bank</span> Desain</h3>
                <p class="text-zinc-500 text-sm mt-2">Temukan desain rumah yang sesuai kebutuhan Anda</p>
            </div>
            <div class="flex items-center gap-2">
                <button @click="prev()" class="w-10 h-10 rounded-xl bg-[#d6fb00]/5 border border-[#d6fb00]/20 hover:border-[#00a3b5]/50 text-[#8aacb0] hover:text-white flex items-center justify-center transition-all"><i class="fa-solid fa-chevron-left text-xs"></i></button>
                <button @click="next()" class="w-10 h-10 rounded-xl bg-[#d6fb00]/5 border border-[#d6fb00]/20 hover:border-[#00a3b5]/50 text-[#8aacb0] hover:text-white flex items-center justify-center transition-all"><i class="fa-solid fa-chevron-right text-xs"></i></button>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl p-1 -mx-1" data-aos="fade-up" data-aos-delay="200">
            <div id="designs-container" class="carousel-track" :style="'transform: translateX(-' + (offset * (100 / perView)) + '%)'">
                <!-- Skeleton Loaders (replaced by AJAX) -->
                <div class="px-3"><div class="skeleton h-80"></div></div>
                <div class="px-3"><div class="skeleton h-80"></div></div>
                <div class="px-3"><div class="skeleton h-80"></div></div>
            </div>
        </div>

        <!-- Dot Indicators -->
        <div class="flex items-center justify-center gap-1.5 mt-6" data-aos="zoom-in" data-aos-delay="300">
            <template x-for="i in totalPages" :key="i">
                <button @click="goToPage(i-1)" class="w-1.5 h-1.5 rounded-full transition-all duration-300" :class="currentPage === i-1 ? 'w-5 bg-[#00a3b5]' : 'bg-[#d6fb00]/12 hover:bg-[#d6fb00]/25'"></button>
            </template>
        </div>
    </div>
</section>

<!-- ============================================================
     SECTION 6: CARI PERUMAHAN (Sikumbang)
     ============================================================ -->
<section id="cari-perumahan" class="w-full py-20 sm:py-28 scroll-mt-20">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-10" data-aos="fade-down">
            <h3 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight mb-2">Cari <span class="text-[#d6fb00]">Perumahan</span></h3>
            <p class="text-zinc-500 text-xs">Data real-time dari Sikumbang — Tapera</p>
        </div>

        <style>
            .tl-search-input {
                background-color: rgba(214, 251, 0, 0.03);
                border: 1px solid rgba(214, 251, 0, 0.15);
                color: #ecffb6;
            }
            .tl-search-input:focus {
                border-color: rgba(214, 251, 0, 0.4);
                box-shadow: 0 0 0 3px rgba(214,251,0,0.1);
            }
            .tl-search-input option {
                background-color: #0a1a1f;
                color: #ecffb6;
            }
            .tl-btn-search {
                background-color: #d6fb00;
                color: #0a1a1f;
                width: 36px;
                height: 36px;
                border-radius: 8px;
            }
            .tl-btn-search:hover { background-color: #ecffb6; }
            
            /* Custom CSS Toggle */
            .tl-toggle-bg {
                position: relative;
                width: 44px;
                height: 24px;
                background-color: rgba(255, 255, 255, 0.1);
                border-radius: 9999px;
                transition: all 0.3s;
                cursor: pointer;
            }
            .tl-toggle-circle {
                position: absolute;
                top: 3px;
                left: 3px;
                width: 18px;
                height: 18px;
                background-color: #8aacb0;
                border-radius: 50%;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            input:checked + .tl-toggle-bg {
                background-color: #d6fb00;
            }
            input:checked + .tl-toggle-bg .tl-toggle-circle {
                transform: translateX(20px);
                background-color: #0a1a1f;
            }
        </style>
        
        <div class="border p-6 sm:p-8 rounded-3xl mb-10 backdrop-blur-md" style="background-color: rgba(15, 42, 48, 0.7); border-color: rgba(214, 251, 0, 0.15);" data-aos="fade-up" data-aos-delay="100">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-end">
                <div class="lg:col-span-4">
                    <label class="block text-[10px] font-bold text-[#8aacb0] uppercase tracking-widest mb-2"><i class="fa-solid fa-location-dot mr-1.5 text-[#d6fb00]"></i> Wilayah</label>
                    <select id="kodeWilayah" onchange="cari_wil()" class="w-full rounded-xl px-4 py-3 text-sm outline-none transition-all tl-search-input">
                        <option value="33">Seluruh Jawa Tengah</option>
                        <?php foreach ($kabupaten_kota_jateng as $kode => $nama): ?>
                        <option value="<?= $kode ?>"><?= $nama ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lg:col-span-8">
                    <label class="block text-[10px] font-bold text-[#8aacb0] uppercase tracking-widest mb-2"><i class="fa-solid fa-magnifying-glass mr-1.5 text-[#d6fb00]"></i> Nama Perumahan</label>
                    <div class="relative flex items-center">
                        <input id="keyword" onkeyup="cari_wil()" type="text" placeholder="Cari nama perumahan..." class="w-full rounded-xl px-4 py-3 pr-12 text-sm outline-none transition-all tl-search-input placeholder-[#5a7a80]">
                        <button onclick="cari_wil()" class="absolute right-1.5 flex items-center justify-center transition-all tl-btn-search">
                            <i class="fa-solid fa-magnifying-glass text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-center mt-6 pt-6" style="border-top: 1px solid rgba(214, 251, 0, 0.1);">
                <div class="lg:col-span-4">
                    <label class="block text-[10px] font-bold text-[#8aacb0] uppercase tracking-widest mb-2"><i class="fa-solid fa-sort mr-1.5 text-[#d6fb00]"></i> Urutkan</label>
                    <select id="sort" onchange="cari_wil()" class="w-full rounded-xl px-4 py-2.5 text-sm outline-none transition-all tl-search-input">
                        <option value="terbaru">Terbaru</option>
                        <option value="subsidi-termurah">Harga Terendah</option>
                        <option value="subsidi-tertinggi">Harga Tertinggi</option>
                    </select>
                </div>
                <div class="lg:col-span-8 flex lg:justify-end items-center mt-2 lg:mt-0">
                    <label class="inline-flex items-center cursor-pointer group">
                        <input id="status_subsidi" value="subsidi" onchange="cari_wil()" type="checkbox" class="sr-only" checked>
                        <div class="tl-toggle-bg">
                            <div class="tl-toggle-circle"></div>
                        </div>
                        <span class="ml-3 text-sm font-semibold text-zinc-400 group-hover:text-white transition-colors">Hanya Rumah Subsidi</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="temp_rumah" data-aos="fade-up" data-aos-delay="200">
            <div class="skeleton h-72"></div>
            <div class="skeleton h-72"></div>
            <div class="skeleton h-72"></div>
        </div>

        <div class="w-full flex justify-center mt-12" data-aos="zoom-in" data-aos-delay="300">
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
        <div class="flex flex-col sm:flex-row items-start sm:items-end justify-between mb-10 gap-4" data-aos="fade-down">
            <div>
                <h3 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-white">
                    Berita & <span class="text-[#d6fb00] drop-shadow-md">Artikel</span>
                </h3>
                <p class="text-[#8aacb0] text-sm mt-2">Informasi terkini seputar perumahan dan permukiman</p>
            </div>
            <a href="<?= base_url('berita') ?>" class="group border border-[#d6fb00]/20 hover:border-[#d6fb00]/50 bg-[#d6fb00]/5 hover:bg-[#d6fb00]/10 px-5 py-2.5 rounded-full text-xs font-bold text-[#ecffb6] flex items-center gap-2.5 transition-all shadow-lg shadow-black/20">
                Semua Artikel
                <span class="w-6 h-6 rounded-full bg-[#d6fb00]/20 text-[#d6fb00] group-hover:bg-[#d6fb00] group-hover:text-[#0a1a1f] flex items-center justify-center text-[10px] transition-colors"><i class="fa-solid fa-arrow-right"></i></span>
            </a>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="articles-container" data-aos="fade-up" data-aos-delay="200">
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
            // Recalculate AOS positions after new content changes page height
            setTimeout(() => { 
                if (typeof AOS !== 'undefined') AOS.refresh(); 
            }, 100);
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
            lazyLoad('articles-container', '<?= base_url("ajax_articles") ?>', 'articles');
        if (id === 'designs-container' && !lazyState.designs)
            lazyLoad('designs-container', '<?= base_url("ajax_house_designs") ?>', 'designs');
        if (id === 'temp_rumah' && !lazyState.perumahan)
            lazyLoad('temp_rumah', '<?= base_url("ajax_perumahan") ?>', 'perumahan');
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
        url: '<?= base_url('cari_wil') ?>?kodeWilayah='+kodeWilayah+'&keyword='+keyword+'&sort='+sort+'&status_rumah='+statusRumah,
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
        url: '<?= base_url('load_more') ?>?kodeWilayah='+kodeWilayah+'&keyword='+keyword+'&sort='+sort+'&status_rumah='+statusRumah+'&page='+nextPage,
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

// ============================================================
// TIMELINE SVG MULTI-PATH ANIMATION (DASHED)
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    const svgContainer = document.getElementById('timeline-svg');
    if (!svgContainer) return;
    
    const dots = document.querySelectorAll('.tl-dot');
    const cardWraps = document.querySelectorAll('.tl-card-wrap');
    const container = document.getElementById('timeline-container');
    
    let pathData = [];
    
    function buildTimelinePaths() {
        svgContainer.innerHTML = '';
        pathData = [];
        
        if (!container || dots.length === 0) return;
        
        const cRect = container.getBoundingClientRect();
        let currentScrollTrigger = 0;
        
        dots.forEach((dot, i) => {
            const dRect = dot.getBoundingClientRect();
            const wrapRect = cardWraps[i].getBoundingClientRect();
            
            const x = dRect.left - cRect.left + (dRect.width / 2);
            const y = dRect.top - cRect.top + (dRect.height / 2);
            const r = (dRect.width / 2) + 2; // radius + slight padding
            
            const dotCenterY = dRect.top + window.scrollY + (dRect.height / 2);
            if (i === 0) {
                currentScrollTrigger = dotCenterY;
            }
            
            const padR = parseFloat(window.getComputedStyle(cardWraps[i]).paddingRight) || 0;
            const padL = parseFloat(window.getComputedStyle(cardWraps[i]).paddingLeft) || 0;
            
            // 1. Horizontal Path
            const isDesktop = window.innerWidth >= 768;
            let startX, cardX;
            if (isDesktop) {
                const isLeft = (i % 2 === 0);
                cardX = isLeft ? (wrapRect.right - padR - cRect.left) : (wrapRect.left + padL - cRect.left);
                startX = isLeft ? x - r : x + r;
            } else {
                cardX = wrapRect.left + padL - cRect.left;
                startX = x + r;
            }
            
            const hPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            hPath.setAttribute('d', ''); // Start empty to prevent stroke-linecap dot
            hPath.setAttribute('fill', 'none');
            hPath.setAttribute('stroke', '#d6fb00');
            hPath.setAttribute('stroke-width', '2');
            hPath.setAttribute('stroke-linecap', 'round');
            hPath.setAttribute('stroke-dasharray', '8 8'); // Garis putus-putus
            hPath.style.filter = 'drop-shadow(0 0 8px rgba(214,251,0,0.5))';
            
            svgContainer.appendChild(hPath);
            
            pathData.push({
                el: hPath,
                type: 'horizontal',
                index: i,
                startX: startX,
                startY: y,
                endX: cardX,
                endY: y,
                scrollStart: currentScrollTrigger,
                scrollEnd: currentScrollTrigger + 100
            });
            
            currentScrollTrigger += 100;
            
            // 2. Vertical Path to NEXT dot
            if (i < dots.length - 1) {
                const nextRect = dots[i+1].getBoundingClientRect();
                const nextY = nextRect.top - cRect.top + (nextRect.height / 2);
                const nextDotCenterY = nextRect.top + window.scrollY + (nextRect.height / 2);
                
                const startY = y + r;
                const endY = nextY - r;
                
                const vPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                vPath.setAttribute('d', ''); // Start empty to prevent stroke-linecap dot
                vPath.setAttribute('fill', 'none');
                vPath.setAttribute('stroke', '#d6fb00');
                vPath.setAttribute('stroke-width', '2');
                vPath.setAttribute('stroke-linecap', 'round');
                vPath.setAttribute('stroke-dasharray', '8 8'); // Garis putus-putus
                vPath.style.filter = 'drop-shadow(0 0 8px rgba(214,251,0,0.5))';
                
                svgContainer.appendChild(vPath);
                
                let endScroll = nextDotCenterY;
                if (endScroll <= currentScrollTrigger) {
                    endScroll = currentScrollTrigger + 50;
                }
                
                pathData.push({
                    el: vPath,
                    type: 'vertical',
                    index: i,
                    startX: x,
                    startY: startY,
                    endX: x,
                    endY: endY,
                    scrollStart: currentScrollTrigger,
                    scrollEnd: endScroll
                });
                
                currentScrollTrigger = endScroll;
            }
        });
        
        handleTimelineScroll();
    }
    
    function handleTimelineScroll() {
        const viewportHeight = window.innerHeight;
        const scrollY = window.scrollY;
        
        pathData.forEach(p => {
            // Start drawing when the top of the line reaches 75% of viewport
            const drawStartTrigger = scrollY + viewportHeight * 0.75;
            
            let percentage = (drawStartTrigger - p.scrollStart) / (p.scrollEnd - p.scrollStart);
            percentage = Math.max(0, Math.min(1, percentage));
            
            if (percentage === 0) {
                p.el.setAttribute('d', '');
            } else {
                const currentX = p.startX + (p.endX - p.startX) * percentage;
                const currentY = p.startY + (p.endY - p.startY) * percentage;
                p.el.setAttribute('d', `M ${p.startX} ${p.startY} L ${currentX} ${currentY}`);
            }
            
            // Trigger card visibility when horizontal line reaches 100%
            if (p.type === 'horizontal') {
                const card = document.getElementById(`tl-card-${p.index}`);
                if (card) {
                    if (percentage === 1) {
                        card.classList.add('tl-card-visible');
                    } else {
                        card.classList.remove('tl-card-visible');
                    }
                }
            }
            
            // Trigger dot visibility when vertical line reaches 100%
            if (p.type === 'vertical') {
                const nextDot = document.getElementById(`tl-dot-${p.index + 1}`);
                if (nextDot) {
                    if (percentage === 1) {
                        nextDot.classList.remove('scale-0', 'opacity-0');
                    } else {
                        nextDot.classList.add('scale-0', 'opacity-0');
                    }
                }
            }
        });
    }
    
    setTimeout(buildTimelinePaths, 500);
    window.addEventListener('resize', buildTimelinePaths);
    window.addEventListener('scroll', handleTimelineScroll);
});
</script>
