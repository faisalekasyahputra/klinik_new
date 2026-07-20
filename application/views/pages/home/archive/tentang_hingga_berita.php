<!-- ============================================================
     ARSIP: Section "Tentang Kami" s.d. "Berita & Artikel"
     ============================================================
     Disembunyikan dari homepage (application/views/pages/home/awal.php)
     atas permintaan user, TIDAK DIHAPUS — disimpan di sini sebagai
     referensi supaya bisa dipakai ulang di halaman lain nanti.

     Isi (urut sesuai homepage semula):
       - SECTION 2: ABOUT (Tentang Kami)
       - SECTION 3: LAYANAN (Bento Grid)
       - SECTION 4: TAHAPAN (Pipeline Timeline)
       - SECTION 5: BANK DESAIN (True Carousel) — versi mandirinya
         sudah ada di application/views/pages/bank_desain/panduan_desain.php
       - SECTION 6: CARI PERUMAHAN (Sikumbang) — versi mandirinya
         sudah ada di application/views/pages/perumahan/cari_rumah.php
       - SECTION 7: BERITA & ARTIKEL

     File ini BUKAN view yang di-load otomatis oleh controller manapun.
     Untuk dipakai lagi: copy section yang dibutuhkan ke halaman baru,
     lalu copy juga fungsi JS terkait dari <script> di bawah (designCarousel,
     lazy-load framework, cari_wil/load_more_data, animasi timeline SVG) —
     fungsi-fungsi itu awalnya ada di <script> global awal.php dan sudah
     ikut dipindah ke sini karena cuma dipakai oleh section-section ini.
     ============================================================ -->
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

<script>
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
