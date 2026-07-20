<!-- Tab Content: Bank Data -->
<div class="py-4 sm:py-6 px-1 sm:px-2">
    <div class="flex items-center gap-2 mb-3">
        <i class="fa-solid fa-chart-pie  text-[color:var(--portal-text)]"></i>
        <h2 class="text-sm font-bold uppercase tracking-widest text-[#2d6b75]">Bank Data</h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">

        <!-- Card: Statistik & Grafik -->
        <a href="<?= base_url('Statistika') ?>" data-tab-link data-tab-key="statistika" data-tab-group="bankdata"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow); min-height: 120px;">
            <i class="fa-solid fa-chart-pie absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 80px; right: -1rem; bottom: -1rem; color: var(--portal-icon); opacity: 0.08;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-chart-pie mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 24px; color: var(--portal-icon); "></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1 group-hover:text-[color:var(--portal-text)] transition-colors">Statistik & Grafik</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">Data statistik dan visualisasi perumahan Jawa Tengah</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: var(--portal-btn-bg); color: var(--portal-icon); border: 1px solid var(--portal-btn-border);">
                    <span>Lihat Statistik</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <!-- Card: Data Lainnya (DISABLED) -->
        <div title="Segera Hadir"
             class="rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden opacity-50 cursor-not-allowed"
             style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow); min-height: 120px;">
            <i class="fa-solid fa-database absolute pointer-events-none"
               style="font-size: 80px; right: -1rem; bottom: -1rem; color: var(--portal-icon); opacity: 0.08;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-database mb-2.5"
                   style="font-size: 24px; color: var(--portal-icon); "></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1">Data Lainnya</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">Lebih banyak data akan tersedia</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: var(--portal-btn-bg); color: var(--portal-icon); border: 1px solid var(--portal-btn-border);">
                    <span>Segera Hadir</span>
                    <i class="fa-solid fa-lock"></i>
                </div>
            </div>
        </div>

    </div>
</div>
