<style>
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
</style>
<section class="w-full pt-24 pb-16 px-4 sm:px-6 lg:px-8 relative min-h-screen font-outfit">
    <!-- Background Ornaments -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
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
                            <span class="text-[#d6fb00]">Nggolek Omah</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <div class="text-left mb-12 space-y-4 max-w-2xl">
            <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tighter font-jakarta">
                Nggolek <span class="text-[#d6fb00]">Omah</span>
            </h2>
            <p class="text-zinc-400 text-sm sm:text-base leading-relaxed">
                Pilih langkah yang sesuai kebutuhanmu — cari rumah yang sudah tersedia, cari panduan desain untuk bangun sendiri, atau temukan solusi pembiayaan yang cocok.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-6">

            <!-- Card 1: Cari Rumah -->
            <a href="<?= base_url('cari_rumah') ?>" class="group rounded-3xl p-6 sm:p-8 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--bg-card); border: 1px solid rgba(214, 251, 0, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-height: 220px;">
                <i class="fa-solid fa-magnifying-glass-location absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 120px; right: -1rem; bottom: -1rem; color: #d6fb00; opacity: 0.06;"></i>
                <div class="relative z-10">
                    <i class="fa-solid fa-magnifying-glass-location mb-4 transition-transform duration-500 group-hover:scale-110" style="font-size: 32px; color: #d6fb00; filter: drop-shadow(0 0 12px rgba(214, 251, 0, 0.4));"></i>
                    <h4 class="text-white font-bold text-lg mb-1.5 group-hover:text-[#d6fb00] transition-colors">Cari Rumah?</h4>
                    <p class="text-zinc-500 text-xs leading-relaxed">Telusuri rumah subsidi dan non-subsidi yang sudah tersedia di Jawa Tengah.</p>
                </div>
                <div class="relative z-10 mt-auto pt-4">
                    <div class="tl-btn-base" style="background-color: rgba(214,251,0,0.1); color: #d6fb00; border: 1px solid rgba(214,251,0,0.2);">
                        <span>Cari Sekarang</span>
                        <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>

            <!-- Card 2: Panduan Desain Rumah -->
            <a href="<?= base_url('panduan_desain') ?>" class="group rounded-3xl p-6 sm:p-8 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--bg-card); border: 1px solid rgba(214, 251, 0, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-height: 220px;">
                <i class="fa-solid fa-pen-ruler absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 120px; right: -1rem; bottom: -1rem; color: #d6fb00; opacity: 0.06;"></i>
                <div class="relative z-10">
                    <i class="fa-solid fa-pen-ruler mb-4 transition-transform duration-500 group-hover:scale-110" style="font-size: 32px; color: #d6fb00; filter: drop-shadow(0 0 12px rgba(214, 251, 0, 0.4));"></i>
                    <h4 class="text-white font-bold text-lg mb-1.5 group-hover:text-[#d6fb00] transition-colors">Panduan Desain Rumah</h4>
                    <p class="text-zinc-500 text-xs leading-relaxed">Referensi desain untuk rumah swadaya, dari Bank Desain kami.</p>
                </div>
                <div class="relative z-10 mt-auto pt-4">
                    <div class="tl-btn-base" style="background-color: rgba(214,251,0,0.1); color: #d6fb00; border: 1px solid rgba(214,251,0,0.2);">
                        <span>Lihat Desain</span>
                        <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>

            <!-- Card 3: Temukan Solusi Pembiayaan -->
            <a href="<?= base_url('solusi_pembiayaan') ?>" class="group rounded-3xl p-6 sm:p-8 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--bg-card); border: 1px solid rgba(214, 251, 0, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-height: 220px;">
                <i class="fa-solid fa-hand-holding-dollar absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 120px; right: -1rem; bottom: -1rem; color: #d6fb00; opacity: 0.06;"></i>
                <div class="relative z-10">
                    <i class="fa-solid fa-hand-holding-dollar mb-4 transition-transform duration-500 group-hover:scale-110" style="font-size: 32px; color: #d6fb00; filter: drop-shadow(0 0 12px rgba(214, 251, 0, 0.4));"></i>
                    <h4 class="text-white font-bold text-lg mb-1.5 group-hover:text-[#d6fb00] transition-colors">Temukan Solusi Pembiayaan</h4>
                    <p class="text-zinc-500 text-xs leading-relaxed">Cek program bantuan perumahan yang sesuai kondisimu.</p>
                </div>
                <div class="relative z-10 mt-auto pt-4">
                    <div class="tl-btn-base" style="background-color: rgba(214,251,0,0.1); color: #d6fb00; border: 1px solid rgba(214,251,0,0.2);">
                        <span>Cek Kelayakan</span>
                        <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>

        </div>
    </div>
</section>
