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
                            <span class="text-[#d6fb00]">KKN & Magang</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <div class="text-left mb-12 space-y-4 max-w-2xl">
            <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tighter font-jakarta">
                KKN & <span class="text-[#d6fb00]">Magang</span>
            </h2>
            <p class="text-zinc-400 text-sm sm:text-base leading-relaxed">
                Informasi kemitraan, program tematik perguruan tinggi, dan penerimaan magang.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <!-- Card 1: KKN Tematik -->
            <a href="#" class="flex flex-col h-full group">
                <div class="bg-[#0f2a30] border border-[#d6fb00]/20 group-hover:border-cyan-500/60 flex-1 p-6 rounded-[24px] flex flex-col justify-between group-hover:-translate-y-1 transition-all duration-300 shadow-lg group-hover:shadow-[0_8px_30px_rgba(0,163,181,0.1)]">
                    <div class="flex items-start gap-4">
                        <div class="text-cyan-400 shrink-0 pt-0.5 group-hover:scale-110 group-hover:rotate-[-5deg] transition-transform duration-300">
                            <i class="fa-solid fa-graduation-cap text-[28px]"></i>
                        </div>
                        <div class="space-y-1.5 pt-1">
                            <h4 class="text-white font-bold text-base tracking-tight group-hover:text-cyan-400 transition-colors">KKN Tematik</h4>
                            <p class="text-zinc-400 text-xs leading-relaxed">Kolaborasi program Kuliah Kerja Nyata tematik dari berbagai perguruan tinggi.</p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-4 mt-auto">
                        <i class="fa-solid fa-arrow-right text-xs text-zinc-600 group-hover:text-cyan-400 group-hover:translate-x-1 transition-all duration-300"></i>
                    </div>
                </div>
            </a>

            <!-- Card 2: Magang -->
            <a href="#" class="flex flex-col h-full group">
                <div class="bg-[#0f2a30] border border-[#d6fb00]/20 group-hover:border-purple-500/60 flex-1 p-6 rounded-[24px] flex flex-col justify-between group-hover:-translate-y-1 transition-all duration-300 shadow-lg group-hover:shadow-[0_8px_30px_rgba(168,85,247,0.1)]">
                    <div class="flex items-start gap-4">
                        <div class="text-purple-400 shrink-0 pt-0.5 group-hover:scale-110 group-hover:rotate-[-5deg] transition-transform duration-300">
                            <i class="fa-solid fa-briefcase text-[28px]"></i>
                        </div>
                        <div class="space-y-1.5 pt-1">
                            <h4 class="text-white font-bold text-base tracking-tight group-hover:text-purple-400 transition-colors">Magang</h4>
                            <p class="text-zinc-400 text-xs leading-relaxed">Pendaftaran dan informasi kesempatan magang kerja bagi mahasiswa.</p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-4 mt-auto">
                        <i class="fa-solid fa-arrow-right text-xs text-zinc-600 group-hover:text-purple-400 group-hover:translate-x-1 transition-all duration-300"></i>
                    </div>
                </div>
            </a>

            <!-- Card 3: Informasi dan Ketentuan Penerimaan -->
            <a href="#" target="_blank" class="flex flex-col h-full group">
                <div class="bg-[#0f2a30] border border-[#d6fb00]/20 group-hover:border-[#d6fb00]/60 flex-1 p-6 rounded-[24px] flex flex-col justify-between group-hover:-translate-y-1 transition-all duration-300 shadow-lg group-hover:shadow-[0_8px_30px_rgba(214,251,0,0.1)]">
                    <div class="flex items-start gap-4">
                        <div class="text-[#d6fb00] shrink-0 pt-0.5 group-hover:scale-110 group-hover:rotate-[-5deg] transition-transform duration-300">
                            <i class="fa-solid fa-circle-info text-[28px]"></i>
                        </div>
                        <div class="space-y-1.5 pt-1">
                            <h4 class="text-white font-bold text-base tracking-tight group-hover:text-[#d6fb00] transition-colors">Informasi &amp; Ketentuan Penerimaan</h4>
                            <p class="text-zinc-400 text-xs leading-relaxed">Syarat, ketentuan, dan panduan lengkap proses pendaftaran kemitraan.</p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-4 mt-auto">
                        <i class="fa-solid fa-arrow-right text-xs text-zinc-600 group-hover:text-[#d6fb00] group-hover:translate-x-1 transition-all duration-300"></i>
                    </div>
                </div>
            </a>

        </div>
    </div>
</section>
