<!-- Tab Content: Perumahan -->
<div class="py-4 sm:py-6 px-1 sm:px-2">
    <div class="flex items-center gap-2 mb-3">
        <i class="fa-solid fa-house-chimney  text-[color:var(--portal-text)]"></i>
        <h2 class="text-sm font-bold uppercase tracking-widest text-[#2d6b75]">Layanan Perumahan</h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3">

        <!-- Card: Etalase Program -->
        <a href="<?= base_url() ?>#etalase-program" data-tab-link data-tab-key="etalase" data-tab-group="perumahan"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow); min-height: 120px;">
            <i class="fa-solid fa-house-chimney-window absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 80px; right: -1rem; bottom: -1rem; color: var(--portal-icon); opacity: 0.08;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-house-chimney-window mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 24px; color: var(--portal-icon); "></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1 group-hover:text-[color:var(--portal-text)] transition-colors">Etalase Program</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">Daftar program bantuan & subsidi perumahan</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: rgba(15,42,48,0.05); color: var(--portal-icon); border: 1px solid rgba(15,42,48,0.1);">
                    <span>Lihat Program</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <!-- Card: Info KPR & Subsidi -->
        <a href="<?= base_url('simulasi_kpr') ?>" data-tab-link data-tab-key="simulasi_kpr" data-tab-group="perumahan"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow); min-height: 120px;">
            <i class="fa-solid fa-calculator absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 80px; right: -1rem; bottom: -1rem; color: var(--portal-icon); opacity: 0.08;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-calculator mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 24px; color: var(--portal-icon); "></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1 group-hover:text-[color:var(--portal-text)] transition-colors">Info KPR & Subsidi</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">Simulasi kredit dan informasi subsidi perumahan</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: rgba(15,42,48,0.05); color: var(--portal-icon); border: 1px solid rgba(15,42,48,0.1);">
                    <span>Simulasi KPR</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <!-- Card: Bank Desain Rumah -->
        <a href="<?= base_url('panduan_desain') ?>" data-tab-link data-tab-key="panduan_desain" data-tab-group="perumahan"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow); min-height: 120px;">
            <i class="fa-solid fa-pen-ruler absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 80px; right: -1rem; bottom: -1rem; color: var(--portal-icon); opacity: 0.08;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-pen-ruler mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 24px; color: var(--portal-icon); "></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1 group-hover:text-[color:var(--portal-text)] transition-colors">Bank Desain Rumah</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">Prototipe desain rumah dari katalog Ternak Web</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: rgba(15,42,48,0.05); color: var(--portal-icon); border: 1px solid rgba(15,42,48,0.1);">
                    <span>Lihat Desain</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

    </div>
</div>
