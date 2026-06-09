<section class="w-full pt-24 pb-16 px-4 sm:px-6 lg:px-8 relative min-h-screen font-outfit">
    <!-- Background Ornaments -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-[#d6fb00]/5 blur-[120px] rounded-full"></div>
    </div>

    <div class="max-w-7xl mx-auto relative z-10">
        
        <!-- Breadcrumb -->
        <div class="mb-10">
            <nav class="flex text-[10px] sm:text-xs text-zinc-500 font-bold uppercase tracking-widest" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2">
                    <li class="inline-flex items-center">
                        <a href="<?= base_url() ?>" class="hover:text-[#d6fb00] transition-colors"><i class="fa-solid fa-house mr-2"></i>Beranda</a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fa-solid fa-chevron-right text-[8px] mx-2"></i>
                            <span class="text-[#d6fb00]">Layanan Umum</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <div class="text-left mb-12 space-y-4 max-w-2xl">
            <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tighter font-jakarta">
                Layanan Informasi <span class="text-[#d6fb00]">Perumahan</span>
            </h2>
            <p class="text-zinc-400 text-sm sm:text-base leading-relaxed">
                Pilih kategori layanan sesuai dengan pilar kebutuhan informasi, validasi data, dan fasilitas hunian Anda.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <!-- Card 1: Cek DTSN -->
            <a href="#" class="flex flex-col h-full group">
                <div class="bg-[#0f2a30] border border-[#d6fb00]/20 group-hover:border-[#d6fb00]/60 flex-1 p-6 rounded-[24px] flex flex-col justify-between group-hover:-translate-y-1 transition-all duration-300 shadow-lg group-hover:shadow-[0_8px_30px_rgba(214,251,0,0.1)]">
                    <div class="flex items-start gap-4">
                        <div class="text-[#d6fb00] shrink-0 pt-0.5 group-hover:scale-110 group-hover:rotate-[-5deg] transition-transform duration-300">
                            <i class="fa-solid fa-id-card-clip text-[28px]"></i>
                        </div>
                        <div class="space-y-1.5 pt-1">
                            <h4 class="text-white font-bold text-base tracking-tight group-hover:text-[#d6fb00] transition-colors">Cek DTSN</h4>
                            <p class="text-zinc-400 text-xs leading-relaxed">Fasilitas pengecekan status Data Terpadu Sasaran Nilai untuk memastikan validasi usulan bantuan perumahan.</p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-4 mt-auto">
                        <i class="fa-solid fa-arrow-right text-xs text-zinc-600 group-hover:text-[#d6fb00] group-hover:translate-x-1 transition-all duration-300"></i>
                    </div>
                </div>
            </a>

            <!-- Card 2: Housing Career -->
            <a href="<?=base_url('umum/housing')?>" class="flex flex-col h-full group">
                <div class="bg-[#0f2a30] border border-[#d6fb00]/20 group-hover:border-purple-500/60 flex-1 p-6 rounded-[24px] flex flex-col justify-between group-hover:-translate-y-1 transition-all duration-300 shadow-lg group-hover:shadow-[0_8px_30px_rgba(168,85,247,0.1)]">
                    <div class="flex items-start gap-4">
                        <div class="text-purple-400 shrink-0 pt-0.5 group-hover:scale-110 group-hover:rotate-[-5deg] transition-transform duration-300">
                            <i class="fa-solid fa-chart-line text-[28px]"></i>
                        </div>
                        <div class="space-y-1.5 pt-1">
                            <h4 class="text-white font-bold text-base tracking-tight group-hover:text-purple-400 transition-colors">Housing Career</h4>
                            <p class="text-zinc-400 text-xs leading-relaxed">Panduan dan perencanaan siklus kepemilikan rumah bertahap bagi masyarakat berpenghasilan rendah.</p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-4 mt-auto">
                        <i class="fa-solid fa-arrow-right text-xs text-zinc-600 group-hover:text-purple-400 group-hover:translate-x-1 transition-all duration-300"></i>
                    </div>
                </div>
            </a>

            <!-- Card 3: Rumah Subsidi & Non Subsidi -->
            <a href="<?=base_url('umum/sebaran')?>" class="flex flex-col h-full group">
                <div class="bg-[#0f2a30] border border-[#d6fb00]/20 group-hover:border-emerald-500/60 flex-1 p-6 rounded-[24px] flex flex-col justify-between group-hover:-translate-y-1 transition-all duration-300 shadow-lg group-hover:shadow-[0_8px_30px_rgba(16,185,129,0.1)]">
                    <div class="flex items-start gap-4">
                        <div class="text-emerald-400 shrink-0 pt-0.5 group-hover:scale-110 group-hover:rotate-[-5deg] transition-transform duration-300">
                            <i class="fa-solid fa-layer-group text-[28px]"></i>
                        </div>
                        <div class="space-y-1.5 pt-1">
                            <h4 class="text-white font-bold text-base tracking-tight group-hover:text-emerald-400 transition-colors">Rumah Subsidi &amp; Non Subsidi</h4>
                            <p class="text-zinc-400 text-xs leading-relaxed">Informasi berbasis peta lengkap hunian komersial resmi serta rumah subsidi program pemerintah di Jawa Tengah.</p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-4 mt-auto">
                        <i class="fa-solid fa-arrow-right text-xs text-zinc-600 group-hover:text-emerald-400 group-hover:translate-x-1 transition-all duration-300"></i>
                    </div>
                </div>
            </a>

            <!-- Card 4: Informasi Pengembang -->
            <a href="<?=base_url('umum/pengembang')?>" class="flex flex-col h-full group">
                <div class="bg-[#0f2a30] border border-[#d6fb00]/20 group-hover:border-blue-500/60 flex-1 p-6 rounded-[24px] flex flex-col justify-between group-hover:-translate-y-1 transition-all duration-300 shadow-lg group-hover:shadow-[0_8px_30px_rgba(59,130,246,0.1)]">
                    <div class="flex items-start gap-4">
                        <div class="text-blue-400 shrink-0 pt-0.5 group-hover:scale-110 group-hover:rotate-[-5deg] transition-transform duration-300">
                            <i class="fa-solid fa-city text-[28px]"></i>
                        </div>
                        <div class="space-y-1.5 pt-1">
                            <h4 class="text-white font-bold text-base tracking-tight group-hover:text-blue-400 transition-colors">Informasi Pengembang</h4>
                            <p class="text-zinc-400 text-xs leading-relaxed">Daftar asosiasi dan perusahaan pengembang properti (*developer*) resmi yang terdaftar di sistem Tapera.</p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-4 mt-auto">
                        <i class="fa-solid fa-arrow-right text-xs text-zinc-600 group-hover:text-blue-400 group-hover:translate-x-1 transition-all duration-300"></i>
                    </div>
                </div>
            </a>

            <!-- Card 5: Layanan & Aduan -->
            <a href="<?=base_url('umum/aduan')?>" class="flex flex-col h-full group">
                <div class="bg-[#0f2a30] border border-[#d6fb00]/20 group-hover:border-red-500/60 flex-1 p-6 rounded-[24px] flex flex-col justify-between group-hover:-translate-y-1 transition-all duration-300 shadow-lg group-hover:shadow-[0_8px_30px_rgba(239,68,68,0.1)]">
                    <div class="flex items-start gap-4">
                        <div class="text-red-400 shrink-0 pt-0.5 group-hover:scale-110 group-hover:rotate-[-5deg] transition-transform duration-300">
                            <i class="fa-solid fa-circle-exclamation text-[28px]"></i>
                        </div>
                        <div class="space-y-1.5 pt-1">
                            <h4 class="text-white font-bold text-base tracking-tight group-hover:text-red-400 transition-colors">Layanan &amp; Aduan</h4>
                            <p class="text-zinc-400 text-xs leading-relaxed">Pusat pelaporan dan konsultasi masalah sengketa lahan, rumah rusak, atau kendala fasilitas prasarana umum.</p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-4 mt-auto">
                        <i class="fa-solid fa-arrow-right text-xs text-zinc-600 group-hover:text-red-400 group-hover:translate-x-1 transition-all duration-300"></i>
                    </div>
                </div>
            </a>

            <!-- Card 6: Forum Diskusi -->
            <a href="<?=base_url('umum/forum')?>" class="flex flex-col h-full group">
                <div class="bg-[#0f2a30] border border-[#d6fb00]/20 group-hover:border-cyan-500/60 flex-1 p-6 rounded-[24px] flex flex-col justify-between group-hover:-translate-y-1 transition-all duration-300 shadow-lg group-hover:shadow-[0_8px_30px_rgba(6,182,212,0.1)]">
                    <div class="flex items-start gap-4">
                        <div class="text-cyan-400 shrink-0 pt-0.5 group-hover:scale-110 group-hover:rotate-[-5deg] transition-transform duration-300">
                            <i class="fa-solid fa-comments text-[28px]"></i>
                        </div>
                        <div class="space-y-1.5 pt-1">
                            <h4 class="text-white font-bold text-base tracking-tight group-hover:text-cyan-400 transition-colors">Forum Diskusi</h4>
                            <p class="text-zinc-400 text-xs leading-relaxed">Ruang interaksi komunitas warga untuk saling berbagi info seputar pemeliharaan lingkungan perumahan.</p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-4 mt-auto">
                        <i class="fa-solid fa-arrow-right text-xs text-zinc-600 group-hover:text-cyan-400 group-hover:translate-x-1 transition-all duration-300"></i>
                    </div>
                </div>
            </a>

        </div>

    </div>
</section>

