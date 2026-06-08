<section class="w-full max-w-6xl mx-auto bg-[#0b0c10]/75 border border-zinc-800/50 rounded-[2.5rem] p-6 sm:p-10 md:py-14 text-center backdrop-blur-xl shadow-2xl z-10 relative">
    
    <h2 class="text-2xl sm:text-3xl font-extrabold text-[#d6fb00] tracking-wide uppercase mb-10 md:mb-12">
        Menu Pengembang
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 text-left">
        
        <a href="<?=base_url('Pengembang/sertifikasi')?>" class="group relative bg-[#0f2a30]/80 border border-zinc-800/80 hover:border-[#d6fb00]/20 rounded-2xl p-5 flex items-center justify-between transition-all duration-300 hover:bg-[#0f2a30] hover:shadow-xl min-h-[95px]">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-zinc-100/10 border border-zinc-100/20 text-zinc-100 flex items-center justify-center text-lg flex-shrink-0 transition-transform duration-300 group-hover:scale-105">
                    <i class="fa-solid fa-square-check"></i>
                </div>
                <div>
                    <h3 class="text-white font-bold text-xs sm:text-sm tracking-wide uppercase leading-snug transition-colors group-hover:text-zinc-300">
                        Sertifikasi Registrasi<br>Pengembang
                    </h3>
                </div>
            </div>
            <div class="text-zinc-600 group-hover:text-white transition-colors pl-2 flex-shrink-0">
                <i class="fa-solid fa-arrow-right text-xs transition-transform duration-300 group-hover:translate-x-1"></i>
            </div>
        </a>

        <a href="<?=base_url('Pengembang/publikasi')?>" class="group relative bg-[#0f2a30]/80 border border-zinc-800/80 hover:border-[#d6fb00]/20 rounded-2xl p-5 flex items-center justify-between transition-all duration-300 hover:bg-[#0f2a30] hover:shadow-xl min-h-[95px]">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center text-lg flex-shrink-0 transition-transform duration-300 group-hover:scale-105">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
                <div>
                    <h3 class="text-white font-bold text-xs sm:text-sm tracking-wide uppercase leading-snug transition-colors group-hover:text-purple-400">
                        Publikasi Sosial Media<br>Perumahan
                    </h3>
                </div>
            </div>
            <div class="text-zinc-600 group-hover:text-purple-400 transition-colors pl-2 flex-shrink-0">
                <i class="fa-solid fa-arrow-right text-xs transition-transform duration-300 group-hover:translate-x-1"></i>
            </div>
        </a>

        <a href="#" class="group relative bg-[#0f2a30]/80 border border-zinc-800/80 hover:border-[#d6fb00]/20 rounded-2xl p-5 flex items-center justify-between transition-all duration-300 hover:bg-[#0f2a30] hover:shadow-xl min-h-[95px]">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 flex items-center justify-center text-lg flex-shrink-0 transition-transform duration-300 group-hover:scale-105">
                    <i class="fa-solid fa-file-arrow-down"></i>
                </div>
                <div>
                    <h3 class="text-white font-bold text-xs sm:text-sm tracking-wide uppercase leading-snug transition-colors group-hover:text-cyan-400">
                        Download Sertifikat
                    </h3>
                </div>
            </div>
            <div class="text-zinc-600 group-hover:text-cyan-400 transition-colors pl-2 flex-shrink-0">
                <i class="fa-solid fa-arrow-right text-xs transition-transform duration-300 group-hover:translate-x-1"></i>
            </div>
            
        </a>
        

    </div>
    <div class="flex justify-center mt-12">
        <a href="<?= base_url() ?>" class="group flex items-center gap-2.5 bg-[#d6fb00]/5 hover:bg-[#d6fb00]/8 border border-[#d6fb00]/20 hover:border-[#d6fb00]/20 px-6 py-3 rounded-xl text-xs font-semibold text-zinc-400 hover:text-white transition-all duration-300 shadow-xl backdrop-blur-md">
            <i class="fa-solid fa-arrow-left transition-transform group-hover:-translate-x-1"></i>
            <span>Kembali ke Beranda Utama</span>
        </a>
    </div>
</section>