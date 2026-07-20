<!-- ============================================================
     SECTION 1: HERO — Full Viewport Slideshow
     ============================================================ -->
<section class="w-full relative overflow-hidden" style="height: 110vh;" x-data="heroBgSlider()">
    <!-- Background Slider — image only, no per-slide text (cards/title below are static) -->
    <div class="absolute inset-0 z-0" style="mask-image: linear-gradient(to bottom, black 0, black calc(100% - 10vh), transparent 100%); -webkit-mask-image: linear-gradient(to bottom, black 0, black calc(100% - 10vh), transparent 100%);">
        <div class="hero-slide" :class="current === 0 && 'active'">
            <div class="absolute inset-0 gradient-hero z-10"></div>
            <picture>
                <source srcset="<?= base_url('assets/img/hero/hero-perumahan-subsidi.webp') ?>" type="image/webp">
                <img src="<?= base_url('assets/img/hero/hero-perumahan-subsidi-opt.jpeg') ?>" alt="Pembangunan perumahan subsidi di Jawa Tengah" loading="eager">
            </picture>
        </div>
        <div class="hero-slide" :class="current === 1 && 'active'">
            <div class="absolute inset-0 gradient-hero z-10"></div>
            <picture>
                <source srcset="<?= base_url('assets/img/hero/hero-kawasan-permukiman.webp') ?>" type="image/webp">
                <img src="<?= base_url('assets/img/hero/hero-kawasan-permukiman-opt.jpeg') ?>" alt="Panorama udara kawasan permukiman modern Jawa Tengah" loading="lazy">
            </picture>
        </div>
        <div class="hero-slide" :class="current === 2 && 'active'">
            <div class="absolute inset-0 gradient-hero z-10"></div>
            <picture>
                <source srcset="<?= base_url('assets/img/hero/hero-sertifikasi-lahan.webp') ?>" type="image/webp">
                <img src="<?= base_url('assets/img/hero/hero-sertifikasi-lahan-opt.jpeg') ?>" alt="Hamparan lahan dan permukiman di Jawa Tengah" loading="lazy">
            </picture>
        </div>
    </div>
    <div class="absolute inset-0 gradient-radial pointer-events-none z-[1]"></div>

    <div class="relative z-10 flex flex-col justify-center" style="min-height: 100vh; padding-top: 6rem; padding-bottom: 6rem;">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 w-full">
        <div class="text-center mb-14" data-aos="fade-down">
            <div class="inline-flex items-center gap-2.5 text-[11px] text-[#8aacb0] font-medium mb-6">
                <img src="<?= base_url('assets/img/logo-jateng.png') ?>" alt="Logo Jawa Tengah" class="w-5 h-5 object-contain shrink-0">
                Dinas Perumahan Rakyat & Kawasan Permukiman Prov. Jawa Tengah
            </div>
            <h2 class="text-5xl sm:text-6xl lg:text-7xl font-extrabold text-white mb-5 leading-[1.1] tracking-tight">
                <?= !empty($settings['hero_title']) ? $settings['hero_title'] : 'Ngopeni Omah<br><span class="text-[#d6fb00]">Nglakoni Sesarengan</span>' ?>
            </h2>
            <p class="text-slate-400 text-sm sm:text-base lg:text-lg leading-relaxed max-w-2xl mx-auto">
                <?= !empty($settings['hero_subtitle']) ? nl2br(htmlspecialchars($settings['hero_subtitle'])) : 'Akses informasi rumah subsidi, data spasial permukiman, dan layanan konsultasi dalam satu platform terintegrasi.' ?>
            </p>
        </div>

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
            <!-- Card 1: Nggolek Omah -->
            <a href="<?= base_url('golek_omah') ?>" class="group rounded-3xl p-6 sm:p-8 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--bg-card); border: 1px solid rgba(214, 251, 0, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-height: 220px;" data-aos="fade-up" data-aos-delay="100">
                <i class="fa-solid fa-house-chimney-window absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 120px; right: -1rem; bottom: -1rem; color: #d6fb00; opacity: 0.06;"></i>
                <div class="relative z-10">
                    <i class="fa-solid fa-house-chimney-window mb-4 transition-transform duration-500 group-hover:scale-110" style="font-size: 32px; color: #d6fb00; filter: drop-shadow(0 0 12px rgba(214, 251, 0, 0.4));"></i>
                    <h4 class="text-white font-bold text-lg mb-1.5 group-hover:text-[#d6fb00] transition-colors">Nggolek Omah</h4>
                    <p class="text-zinc-500 text-xs leading-relaxed">Cari rumah sesuai kelayakan Anda</p>
                </div>
                <div class="relative z-10 mt-auto pt-4">
                    <div class="tl-btn-base" style="background-color: rgba(214,251,0,0.1); color: #d6fb00; border: 1px solid rgba(214,251,0,0.2);">
                        <span>Cek Sekarang</span>
                        <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>

            <!-- Card 2: Sertifikasi Pengembang (SRPP) -->
            <a href="<?= base_url('Pengembang/sertifikasi') ?>" class="group rounded-3xl p-6 sm:p-8 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--bg-card); border: 1px solid rgba(214, 251, 0, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-height: 220px;" data-aos="fade-up" data-aos-delay="200">
                <i class="fa-solid fa-certificate absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 120px; right: -1rem; bottom: -1rem; color: #d6fb00; opacity: 0.06;"></i>
                <div class="relative z-10">
                    <i class="fa-solid fa-certificate mb-4 transition-transform duration-500 group-hover:scale-110" style="font-size: 32px; color: #d6fb00; filter: drop-shadow(0 0 12px rgba(214, 251, 0, 0.4));"></i>
                    <h4 class="text-white font-bold text-lg mb-1.5 group-hover:text-[#d6fb00] transition-colors">Sertifikasi Pengembang</h4>
                    <p class="text-zinc-500 text-xs leading-relaxed">SRPP untuk pengembang perumahan</p>
                </div>
                <div class="relative z-10 mt-auto pt-4">
                    <div class="tl-btn-base" style="background-color: rgba(214,251,0,0.1); color: #d6fb00; border: 1px solid rgba(214,251,0,0.2);">
                        <span>Daftar</span>
                        <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>

            <!-- Card 3: PSU dan Kawasan Kumuh — placeholder, halaman belum dibuat -->
            <div class="rounded-3xl p-6 sm:p-8 flex flex-col relative overflow-hidden opacity-50 cursor-not-allowed" style="background-color: var(--bg-card); border: 1px solid rgba(255,255,255,0.08); min-height: 220px;" data-aos="fade-up" data-aos-delay="300" title="Segera Hadir">
                <i class="fa-solid fa-city absolute pointer-events-none" style="font-size: 120px; right: -1rem; bottom: -1rem; color: #8aacb0; opacity: 0.05;"></i>
                <div class="relative z-10">
                    <i class="fa-solid fa-city mb-4" style="font-size: 32px; color: #8aacb0;"></i>
                    <h4 class="text-zinc-300 font-bold text-lg mb-1.5">PSU dan Kawasan Kumuh</h4>
                    <p class="text-zinc-500 text-xs leading-relaxed">Data prasarana & kawasan kumuh</p>
                </div>
                <div class="relative z-10 mt-auto pt-4">
                    <div class="tl-btn-base" style="background-color: rgba(255,255,255,0.05); color: #5a7a80; border: 1px solid rgba(255,255,255,0.08);">
                        <span>Segera Hadir</span>
                        <i class="fa-solid fa-lock"></i>
                    </div>
                </div>
            </div>

            <!-- Card 4: Monitoring Capaian Kinerja — placeholder, fitur belum dibangun -->
            <div class="rounded-3xl p-6 sm:p-8 flex flex-col relative overflow-hidden opacity-50 cursor-not-allowed" style="background-color: var(--bg-card); border: 1px solid rgba(255,255,255,0.08); min-height: 220px;" data-aos="fade-up" data-aos-delay="400" title="Segera Hadir">
                <i class="fa-solid fa-chart-line absolute pointer-events-none" style="font-size: 120px; right: -1rem; bottom: -1rem; color: #8aacb0; opacity: 0.05;"></i>
                <div class="relative z-10">
                    <i class="fa-solid fa-chart-line mb-4" style="font-size: 32px; color: #8aacb0;"></i>
                    <h4 class="text-zinc-300 font-bold text-lg mb-1.5">Monitoring Capaian Kinerja</h4>
                    <p class="text-zinc-500 text-xs leading-relaxed">Pemantauan capaian program</p>
                </div>
                <div class="relative z-10 mt-auto pt-4">
                    <div class="tl-btn-base" style="background-color: rgba(255,255,255,0.05); color: #5a7a80; border: 1px solid rgba(255,255,255,0.08);">
                        <span>Segera Hadir</span>
                        <i class="fa-solid fa-lock"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</section>

<!-- ============================================================
     SECTION 1.5: QUICK LINKS
     ============================================================ -->
<section class="w-full relative py-4 sm:py-8 overflow-hidden">
    <!-- Batik Kawung background texture, subtle, faded at top/bottom edges -->
    <div class="absolute inset-0 pointer-events-none z-0" style="opacity: 0.07; mask-image: linear-gradient(to bottom, transparent 0%, black 25%, black 75%, transparent 100%); -webkit-mask-image: linear-gradient(to bottom, transparent 0%, black 25%, black 75%, transparent 100%);">
        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="batik-quicklinks" x="0" y="0" width="100" height="100" patternUnits="userSpaceOnUse">
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
            <rect width="100%" height="100%" fill="url(#batik-quicklinks)" />
        </svg>
    </div>

    <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
        <div class="text-center mb-10" data-aos="fade-down">
            <div class="inline-flex items-center gap-2 bg-[#d6fb00]/8 border border-[#d6fb00]/20 px-3 py-1 rounded-full text-[11px] text-[#8aacb0] font-medium mb-4">
                <i class="fa-solid fa-people-group text-[#d6fb00]"></i> Akses Cepat
            </div>
            <h3 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Terhubung & <span class="text-[#d6fb00]">Berpartisipasi</span></h3>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 sm:gap-6">
            <!-- Forum Diskusi -->
            <a href="<?= base_url('umum/forum') ?>" class="group rounded-3xl p-6 sm:p-8 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--bg-card); border: 1px solid rgba(214, 251, 0, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2);" data-aos="fade-up" data-aos-delay="100">
                <i class="fa-solid fa-comments absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 130px; right: -1rem; bottom: -1.5rem; color: #d6fb00; opacity: 0.06;"></i>
                <div class="relative z-10">
                    <i class="fa-solid fa-comments mb-4 transition-transform duration-500 group-hover:scale-110" style="font-size: 34px; color: #d6fb00; filter: drop-shadow(0 0 12px rgba(214, 251, 0, 0.4));"></i>
                    <h4 class="text-white font-bold text-xl mb-2 group-hover:text-[#d6fb00] transition-colors">Forum Diskusi</h4>
                    <p class="text-zinc-500 text-sm leading-relaxed">Diskusi seputar perumahan bersama komunitas dan pemerintah.</p>
                </div>
                <div class="relative z-10 mt-auto pt-6">
                    <div class="tl-btn-base" style="background-color: rgba(214,251,0,0.1); color: #d6fb00; border: 1px solid rgba(214,251,0,0.2);">
                        <span>Buka Forum</span>
                        <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>

            <!-- Aduan -->
            <a href="<?= base_url('umum/aduan') ?>" class="group rounded-3xl p-6 sm:p-8 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--bg-card); border: 1px solid rgba(214, 251, 0, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2);" data-aos="fade-up" data-aos-delay="200">
                <i class="fa-solid fa-circle-question absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 130px; right: -1rem; bottom: -1.5rem; color: #d6fb00; opacity: 0.06;"></i>
                <div class="relative z-10">
                    <i class="fa-solid fa-circle-question mb-4 transition-transform duration-500 group-hover:scale-110" style="font-size: 34px; color: #d6fb00; filter: drop-shadow(0 0 12px rgba(214, 251, 0, 0.4));"></i>
                    <h4 class="text-white font-bold text-xl mb-2 group-hover:text-[#d6fb00] transition-colors">Aduan</h4>
                    <p class="text-zinc-500 text-sm leading-relaxed">Sampaikan pertanyaan atau laporan seputar layanan perumahan.</p>
                </div>
                <div class="relative z-10 mt-auto pt-6">
                    <div class="tl-btn-base" style="background-color: rgba(214,251,0,0.1); color: #d6fb00; border: 1px solid rgba(214,251,0,0.2);">
                        <span>Kirim Aduan</span>
                        <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>

            <!-- KKN dan Magang -->
            <a href="<?= base_url('kemitraan') ?>" class="group rounded-3xl p-6 sm:p-8 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--bg-card); border: 1px solid rgba(214, 251, 0, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2);" data-aos="fade-up" data-aos-delay="300">
                <i class="fa-solid fa-user-graduate absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 130px; right: -1rem; bottom: -1.5rem; color: #d6fb00; opacity: 0.06;"></i>
                <div class="relative z-10">
                    <i class="fa-solid fa-user-graduate mb-4 transition-transform duration-500 group-hover:scale-110" style="font-size: 34px; color: #d6fb00; filter: drop-shadow(0 0 12px rgba(214, 251, 0, 0.4));"></i>
                    <h4 class="text-white font-bold text-xl mb-2 group-hover:text-[#d6fb00] transition-colors">KKN dan Magang</h4>
                    <p class="text-zinc-500 text-sm leading-relaxed">Program tematik untuk universitas dan mahasiswa.</p>
                </div>
                <div class="relative z-10 mt-auto pt-6">
                    <div class="tl-btn-base" style="background-color: rgba(214,251,0,0.1); color: #d6fb00; border: 1px solid rgba(214,251,0,0.2);">
                        <span>Info Selengkapnya</span>
                        <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>
</section>

<!-- ============================================================
     SECTION 2.5: ETALASE PROGRAM
     ============================================================ -->
<section id="etalase-program" class="w-full py-20 sm:py-28 relative overflow-hidden" x-data="programEtalase()">
    <!-- Dynamic Blurred Background — morphs (crossfade) to follow the active slide, subtle & seamless into adjacent sections -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none z-0" style="mask-image: linear-gradient(to bottom, transparent, black 15%, black 85%, transparent); -webkit-mask-image: linear-gradient(to bottom, transparent, black 15%, black 85%, transparent);">
        <div class="absolute inset-0 transition-opacity duration-[1200ms] ease-in-out"
             :style="'background-image: url(' + bgImages[0] + '); background-size: cover; background-position: center; filter: blur(8px); transform: scale(1.1); opacity: ' + (bgActiveLayer === 0 ? 0.18 : 0) + ';'"></div>
        <div class="absolute inset-0 transition-opacity duration-[1200ms] ease-in-out"
             :style="'background-image: url(' + bgImages[1] + '); background-size: cover; background-position: center; filter: blur(8px); transform: scale(1.1); opacity: ' + (bgActiveLayer === 1 ? 0.18 : 0) + ';'"></div>
    </div>

    <!-- Ambient glow -->
    <div class="absolute top-1/2 left-1/4 -translate-y-1/2 w-96 h-96 bg-[#d6fb00]/5 rounded-full blur-[100px] pointer-events-none"></div>

    <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
        <div class="text-center max-w-2xl mx-auto mb-10" data-aos="fade-down">
            <div class="inline-flex items-center gap-2 bg-[#d6fb00]/10 border border-[#d6fb00]/15 px-3 py-1 rounded-full text-[11px] text-[#d6fb00] font-semibold mb-6">
                <i class="fa-solid fa-house-chimney-window"></i> Etalase Program
            </div>
            <h3 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight leading-[1.1] mb-5">
                Pilih Program Sesuai <span class="text-[#d6fb00]">Kebutuhan</span>
            </h3>
            <p class="text-slate-400 text-sm leading-relaxed">
                Temukan berbagai skema bantuan perumahan dan kawasan permukiman dari Pemerintah Provinsi Jawa Tengah. Geser untuk menjelajah detail dan prasyarat tiap program.
            </p>
        </div>

        <!-- Program Carousel (Banner Slideshow, infinite loop, draggable, parallax) -->
        <div class="relative">
            <!-- Prev/Next — sit on the card edges, 50% overlap -->
            <button @click="stopAutoplay(); prev()" class="absolute top-1/2 -translate-y-1/2 -left-5 md:-left-6 z-20 w-10 h-10 md:w-12 md:h-12 rounded-xl bg-[#0f2a30] border border-[#d6fb00]/30 hover:border-[#d6fb00]/60 text-[#8aacb0] hover:text-white flex items-center justify-center transition-all shadow-lg shadow-black/30"><i class="fa-solid fa-chevron-left text-xs"></i></button>
            <button @click="stopAutoplay(); next()" class="absolute top-1/2 -translate-y-1/2 -right-5 md:-right-6 z-20 w-10 h-10 md:w-12 md:h-12 rounded-xl bg-[#0f2a30] border border-[#d6fb00]/30 hover:border-[#d6fb00]/60 text-[#8aacb0] hover:text-white flex items-center justify-center transition-all shadow-lg shadow-black/30"><i class="fa-solid fa-chevron-right text-xs"></i></button>

            <div class="overflow-hidden rounded-3xl">
                <div id="etalase-container"
                     class="flex select-none cursor-grab active:cursor-grabbing"
                     :class="(isDragging || noTransition) ? '' : 'transition-transform duration-500 ease-out'"
                     :style="'transform: translateX(calc(-' + (current * 100) + '% + ' + dragDeltaX + 'px));'"
                     @mousedown="onDragStart($event)"
                     @mousemove="onDragMove($event)"
                     @mouseup="onDragEnd()"
                     @mouseleave="onDragEnd()"
                     @touchstart="onDragStart($event)"
                     @touchmove="onDragMove($event)"
                     @touchend="onDragEnd()"
                     @transitionend="onTrackTransitionEnd($event)">
                    <template x-for="(id, idx) in slides" :key="idx + '-' + id">
                    <div class="w-full shrink-0 px-2">
                        <div class="relative rounded-3xl overflow-hidden flex flex-col md:flex-row border border-white/10 h-full"
                             :style="'background-color: var(--bg-card); transition: transform 0.5s ease-out, opacity 0.5s ease-out; transform: scale(' + (idx === current ? 1 : 0.85) + ') translateY(' + (idx === current ? 0 : 16) + 'px); opacity: ' + (idx === current ? 1 : 0.45) + '; z-index: ' + (idx === current ? 10 : 1) + ';'">
                            <!-- Batik halftone texture, fading right to left, subtle -->
                            <svg class="absolute inset-0 w-full h-full pointer-events-none" style="opacity: 0.07; mask-image: linear-gradient(to left, black, transparent); -webkit-mask-image: linear-gradient(to left, black, transparent);" xmlns="http://www.w3.org/2000/svg">
                                <defs>
                                    <pattern :id="'batik-etalase-' + idx" x="0" y="0" width="100" height="100" patternUnits="userSpaceOnUse">
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
                                <rect width="100%" height="100%" :fill="'url(#batik-etalase-' + idx + ')'" />
                            </svg>
                            <!-- Image -->
                            <div class="relative w-full md:w-5/12 h-48 md:h-auto shrink-0 overflow-hidden">
                                <img :src="programs[id].image" :alt="programs[id].title" class="absolute inset-0 w-full h-full object-cover" :style="'transform: translateX(' + (dragDeltaX * 0.15) + 'px) scale(1.1);'" draggable="false">
                                <div class="absolute inset-0 bg-gradient-to-t md:bg-gradient-to-l from-[#0f2a30] via-transparent to-transparent"></div>
                            </div>
                            <!-- Content -->
                            <div class="relative p-6 sm:p-8 flex-1 flex flex-col justify-center">
                                <div class="inline-flex items-center gap-1.5 text-[10px] font-bold tracking-widest uppercase mb-3" :style="'color: ' + programs[id].dotColor">
                                    <span class="w-1.5 h-1.5 rounded-full" :style="'background-color: ' + programs[id].dotColor"></span>
                                    <span x-text="programs[id].badge"></span>
                                </div>
                                <h3 class="text-2xl sm:text-3xl font-bold text-white leading-tight mb-4" x-html="programs[id].title"></h3>

                                <div class="mb-4">
                                    <h4 class="text-xs font-bold text-[#8aacb0] uppercase tracking-widest mb-1.5"><i class="fa-solid fa-book-open mr-1"></i> Definisi Operasional</h4>
                                    <p class="text-zinc-300 text-sm leading-relaxed" x-text="programs[id].definition"></p>
                                </div>

                                <div class="mb-4">
                                    <h4 class="text-xs font-bold text-[#8aacb0] uppercase tracking-widest mb-1.5"><i class="fa-solid fa-list-check mr-1"></i> Syarat & Ketentuan</h4>
                                    <p class="text-zinc-300 text-sm leading-relaxed" x-text="programs[id].terms"></p>

                                    <template x-if="programs[id].options">
                                        <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-2">
                                            <template x-for="(opt, idx) in programs[id].options" :key="idx">
                                                <div class="bg-[#0a1a1f]/50 rounded-xl p-3 border border-white/10">
                                                    <div class="flex items-center gap-2 mb-1.5">
                                                        <i class="fa-solid text-[#d6fb00] text-xs" :class="opt.icon || 'fa-house-chimney'"></i>
                                                        <h5 class="text-white font-bold text-xs leading-tight" x-text="opt.name"></h5>
                                                    </div>
                                                    <p class="text-zinc-400 text-[11px] leading-relaxed" x-text="opt.desc"></p>
                                                </div>
                                            </template>
                                        </div>
                                    </template>
                                </div>

                                <div class="mb-6 flex items-center gap-2 text-xs text-zinc-400">
                                    <i class="fa-solid fa-wallet text-zinc-500"></i>
                                    <span x-text="programs[id].budget"></span>
                                </div>

                                <a :href="'<?= base_url('Program/diagnosa/') ?>' + id" class="inline-flex px-5 py-2.5 rounded-xl text-sm font-semibold bg-white text-[#0a1a1f] hover:bg-zinc-200 transition-colors items-center gap-2 w-max">
                                    Cek Kelayakan NIK <i class="fa-solid fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Dot Indicators -->
        <div class="flex items-center justify-center gap-1.5 mt-6" data-aos="zoom-in" data-aos-delay="300">
            <template x-for="i in total" :key="i">
                <button @click="stopAutoplay(); goTo(i-1)" class="w-1.5 h-1.5 rounded-full transition-all duration-300" :class="activeDot === i-1 ? 'w-5 bg-[#d6fb00]' : 'bg-[#d6fb00]/12 hover:bg-[#d6fb00]/25'"></button>
            </template>
        </div>

        <!-- Instruction Indicator -->
        <div class="mt-4 w-full flex items-center justify-center gap-3 text-zinc-400 text-xs font-medium">
            <i class="fa-solid fa-hand-pointer text-[#d6fb00]"></i>
            Geser untuk menjelajah program lainnya
        </div>
    </div>

    <!-- Alpine.js Logic -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('programEtalase', () => ({
                programOrder: ['flpp', 'oemah_lestari', 'rtlh', 'pb', 'rumah_apung'],
                current: 1,
                total: 5,
                isDragging: false,
                noTransition: false,
                dragStartX: 0,
                dragDeltaX: 0,
                autoplayTimer: null,
                get slides() {
                    return [this.programOrder[this.total - 1], ...this.programOrder, this.programOrder[0]];
                },
                get activeDot() {
                    if (this.current === 0) return this.total - 1;
                    if (this.current === this.slides.length - 1) return 0;
                    return this.current - 1;
                },
                get currentProgramId() {
                    if (this.current === 0) return this.programOrder[this.total - 1];
                    if (this.current === this.slides.length - 1) return this.programOrder[0];
                    return this.programOrder[this.current - 1];
                },
                loopTimer: null,
                bgImages: ['', ''],
                bgActiveLayer: 0,
                init() {
                    this.autoplayTimer = setInterval(() => this.next(), 7000);
                    this.bgImages[0] = this.programs[this.currentProgramId].image;
                    this.$watch('currentProgramId', (id) => {
                        const nextLayer = this.bgActiveLayer === 0 ? 1 : 0;
                        this.bgImages[nextLayer] = this.programs[id].image;
                        this.bgActiveLayer = nextLayer;
                    });
                },
                next() { this.current++; this.scheduleLoopCheck(); },
                prev() { this.current--; this.scheduleLoopCheck(); },
                checkLoopBoundary() {
                    clearTimeout(this.loopTimer);
                    if (this.current >= this.slides.length - 1) {
                        this.noTransition = true;
                        this.current = 1;
                        this.$nextTick(() => { this.noTransition = false; });
                    } else if (this.current <= 0) {
                        this.noTransition = true;
                        this.current = this.total;
                        this.$nextTick(() => { this.noTransition = false; });
                    }
                },
                scheduleLoopCheck() {
                    clearTimeout(this.loopTimer);
                    this.loopTimer = setTimeout(() => this.checkLoopBoundary(), 600);
                },
                onTrackTransitionEnd(e) {
                    if (e.target !== e.currentTarget) return;
                    this.checkLoopBoundary();
                },
                goTo(i) { this.current = i + 1; },
                stopAutoplay() { clearInterval(this.autoplayTimer); },
                onDragStart(e) {
                    this.isDragging = true;
                    this.dragStartX = (e.touches ? e.touches[0].clientX : e.clientX);
                    this.dragDeltaX = 0;
                    this.stopAutoplay();
                },
                onDragMove(e) {
                    if (!this.isDragging) return;
                    const x = (e.touches ? e.touches[0].clientX : e.clientX);
                    this.dragDeltaX = x - this.dragStartX;
                },
                onDragEnd() {
                    if (!this.isDragging) return;
                    this.isDragging = false;
                    const track = document.getElementById('etalase-container');
                    const width = track ? track.offsetWidth : 300;
                    const threshold = width * 0.12;
                    if (this.dragDeltaX < -threshold) this.next();
                    else if (this.dragDeltaX > threshold) this.prev();
                    this.dragDeltaX = 0;
                },
                programs: {
                    'flpp': {
                        title: 'KPR-FLPP<br>Rumah Subsidi',
                        badge: 'MBR Fixed Income',
                        dotColor: '#00a3b5',
                        colorFrom: '#0f4c5c',
                        colorTo: '#062b35',
                        definition: 'Skema pembiayaan perumahan subsidi bagi Masyarakat Berpenghasilan Rendah (MBR).',
                        terms: 'Bunga flat tetap sebesar 5% sepanjang tenor, Uang Muka (DP) sangat ringan mulai 1%, dengan pilihan tenor (jangka waktu angsuran) panjang hingga 20 tahun. Batas penghasilan maksimal Rp 8 Juta / bulan.',
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
                }
            }));
        });
    </script>
</section>

<!-- ============================================================
     SCRIPTS
     ============================================================ -->
<script>
// ============================================================
// HERO BACKGROUND SLIDER (image-only crossfade)
// ============================================================
function heroBgSlider() {
    return {
        current: 0,
        total: 3,
        init() {
            setInterval(() => { this.current = (this.current + 1) % this.total; }, 6000);
        }
    }
}
</script>
