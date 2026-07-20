<!-- Tab Content: Bank Data -->
<div class="p-2 sm:p-4">
    <div class="flex items-center gap-2 mb-5">
        <i class="fa-solid fa-chart-pie text-[#d6fb00]"></i>
        <h2 class="text-sm font-bold uppercase tracking-widest text-[#8aacb0]">Bank Data</h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4">

        <!-- Card: Statistik & Grafik -->
        <a href="<?= base_url('Statistika') ?>" data-tab-link data-tab-key="statistika" data-tab-group="bankdata"
           class="group rounded-3xl p-5 sm:p-6 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--bg-card); border: 1px solid rgba(214,251,0, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-height: 168px;">
            <i class="fa-solid fa-chart-pie absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 120px; right: -1rem; bottom: -1rem; color: #d6fb00; opacity: 0.06;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-chart-pie mb-4 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 32px; color: #d6fb00; filter: drop-shadow(0 0 12px rgba(214,251,0, 0.4));"></i>
                <h4 class="text-white font-bold text-lg mb-1.5 group-hover:text-[#d6fb00] transition-colors">Statistik & Grafik</h4>
                <p class="text-zinc-500 text-xs leading-relaxed">Data statistik dan visualisasi perumahan Jawa Tengah</p>
            </div>
            <div class="relative z-10 mt-auto pt-4">
                <div class="tl-btn-base" style="background-color: rgba(214,251,0,0.1); color: #d6fb00; border: 1px solid rgba(214,251,0,0.2);">
                    <span>Lihat Statistik</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <!-- Card: Data Lainnya (DISABLED) -->
        <div title="Segera Hadir"
             class="rounded-3xl p-5 sm:p-6 flex flex-col transition-all duration-500 relative overflow-hidden opacity-50 cursor-not-allowed"
             style="background-color: var(--bg-card); border: 1px solid rgba(138,172,176, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-height: 168px;">
            <i class="fa-solid fa-database absolute pointer-events-none"
               style="font-size: 120px; right: -1rem; bottom: -1rem; color: #8aacb0; opacity: 0.06;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-database mb-4"
                   style="font-size: 32px; color: #8aacb0; filter: drop-shadow(0 0 12px rgba(138,172,176, 0.4));"></i>
                <h4 class="text-white font-bold text-lg mb-1.5">Data Lainnya</h4>
                <p class="text-zinc-500 text-xs leading-relaxed">Lebih banyak data akan tersedia</p>
            </div>
            <div class="relative z-10 mt-auto pt-4">
                <div class="tl-btn-base" style="background-color: rgba(138,172,176,0.1); color: #8aacb0; border: 1px solid rgba(138,172,176,0.2);">
                    <span>Segera Hadir</span>
                    <i class="fa-solid fa-lock"></i>
                </div>
            </div>
        </div>

    </div>
</div>
