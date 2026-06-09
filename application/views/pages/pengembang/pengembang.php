<section class="w-full  pt-24 pb-16 px-4 sm:px-6 lg:px-8 relative min-h-screen font-outfit">
    <!-- Background Ornaments -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <!-- Batik Pattern Overlay -->
        
        
        <div class="absolute top-[-10%] right-[-5%] w-[600px] h-[600px] rounded-full bg-[#d6fb00]/5 blur-[120px]"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[500px] h-[500px] rounded-full bg-[#00a3b5]/5 blur-[100px]"></div>
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
                            <span class="text-[#d6fb00]">Menu Pengembang</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <div class="text-left mb-12 space-y-4 max-w-2xl">
            <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tighter font-jakarta">
                Menu <span class="text-[#d6fb00]">Pengembang</span>
            </h2>
            <p class="text-zinc-400 text-sm sm:text-base leading-relaxed">
                Pilih layanan spesifik yang diperuntukkan bagi pengembang perumahan.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <!-- Card 1: Sertifikasi Registrasi Pengembang -->
            <a href="<?=base_url('Pengembang/sertifikasi')?>" class="flex flex-col h-full group">
                <div class="bg-[#0a1a1f] border border-white/10 group-hover:border-[#d6fb00]/60 flex-1 p-6 rounded-[24px] flex flex-col justify-between group-hover:-translate-y-1 transition-all duration-300 shadow-lg group-hover:shadow-[0_8px_30px_rgba(214,251,0,0.1)] backdrop-blur-md">
                    <div class="flex items-start gap-4">
                        <div class="text-[#d6fb00] shrink-0 pt-0.5 group-hover:scale-110 group-hover:rotate-[-5deg] transition-transform duration-300">
                            <i class="fa-solid fa-square-check text-[28px]"></i>
                        </div>
                        <div class="space-y-1.5 pt-1">
                            <h4 class="text-white font-bold text-base tracking-tight group-hover:text-[#d6fb00] transition-colors">Sertifikasi Registrasi Pengembang</h4>
                            <p class="text-zinc-400 text-xs leading-relaxed">Layanan registrasi dan sertifikasi bagi pengembang perumahan yang terdaftar.</p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-4 mt-auto">
                        <i class="fa-solid fa-arrow-right text-xs text-zinc-600 group-hover:text-[#d6fb00] group-hover:translate-x-1 transition-all duration-300"></i>
                    </div>
                </div>
            </a>

            <!-- Card 2: Publikasi Sosial Media Perumahan -->
            <a href="<?=base_url('Pengembang/publikasi')?>" class="flex flex-col h-full group">
                <div class="bg-[#0a1a1f] border border-white/10 group-hover:border-purple-500/60 flex-1 p-6 rounded-[24px] flex flex-col justify-between group-hover:-translate-y-1 transition-all duration-300 shadow-lg group-hover:shadow-[0_8px_30px_rgba(168,85,247,0.1)] backdrop-blur-md">
                    <div class="flex items-start gap-4">
                        <div class="text-purple-400 shrink-0 pt-0.5 group-hover:scale-110 group-hover:rotate-[-5deg] transition-transform duration-300">
                            <i class="fa-solid fa-bullhorn text-[28px]"></i>
                        </div>
                        <div class="space-y-1.5 pt-1">
                            <h4 class="text-white font-bold text-base tracking-tight group-hover:text-purple-400 transition-colors">Publikasi Sosial Media Perumahan</h4>
                            <p class="text-zinc-400 text-xs leading-relaxed">Fasilitas pengajuan publikasi perumahan melalui kanal media sosial resmi.</p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-4 mt-auto">
                        <i class="fa-solid fa-arrow-right text-xs text-zinc-600 group-hover:text-purple-400 group-hover:translate-x-1 transition-all duration-300"></i>
                    </div>
                </div>
            </a>

            <!-- Card 3: Download Sertifikat -->
            <a href="#" class="flex flex-col h-full group">
                <div class="bg-[#0a1a1f] border border-white/10 group-hover:border-cyan-500/60 flex-1 p-6 rounded-[24px] flex flex-col justify-between group-hover:-translate-y-1 transition-all duration-300 shadow-lg group-hover:shadow-[0_8px_30px_rgba(0,163,181,0.1)] backdrop-blur-md">
                    <div class="flex items-start gap-4">
                        <div class="text-cyan-400 shrink-0 pt-0.5 group-hover:scale-110 group-hover:rotate-[-5deg] transition-transform duration-300">
                            <i class="fa-solid fa-file-arrow-down text-[28px]"></i>
                        </div>
                        <div class="space-y-1.5 pt-1">
                            <h4 class="text-white font-bold text-base tracking-tight group-hover:text-cyan-400 transition-colors">Download Sertifikat</h4>
                            <p class="text-zinc-400 text-xs leading-relaxed">Unduh salinan digital sertifikat registrasi pengembang Anda di sini.</p>
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
