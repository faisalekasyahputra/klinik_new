<!-- Tab Content: Perumahan -->
<div class="p-2 sm:p-4">
    <div class="flex items-center gap-2 mb-5">
        <i class="fa-solid fa-house-chimney text-[#d6fb00]"></i>
        <h2 class="text-sm font-bold uppercase tracking-widest text-[#8aacb0]">Layanan Perumahan</h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 sm:gap-4">

        <!-- Card: Etalase Program -->
        <a href="<?= base_url() ?>#etalase-program" data-tab-link data-tab-key="etalase" data-tab-group="perumahan"
           class="group rounded-3xl p-5 sm:p-6 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--bg-card); border: 1px solid rgba(214,251,0, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-height: 168px;">
            <i class="fa-solid fa-house-chimney-window absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 120px; right: -1rem; bottom: -1rem; color: #d6fb00; opacity: 0.06;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-house-chimney-window mb-4 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 32px; color: #d6fb00; filter: drop-shadow(0 0 12px rgba(214,251,0, 0.4));"></i>
                <h4 class="text-white font-bold text-lg mb-1.5 group-hover:text-[#d6fb00] transition-colors">Etalase Program</h4>
                <p class="text-zinc-500 text-xs leading-relaxed">Daftar program bantuan & subsidi perumahan</p>
            </div>
            <div class="relative z-10 mt-auto pt-4">
                <div class="tl-btn-base" style="background-color: rgba(214,251,0,0.1); color: #d6fb00; border: 1px solid rgba(214,251,0,0.2);">
                    <span>Lihat Program</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <!-- Card: Info KPR & Subsidi -->
        <a href="<?= base_url('simulasi_kpr') ?>" data-tab-link data-tab-key="simulasi_kpr" data-tab-group="perumahan"
           class="group rounded-3xl p-5 sm:p-6 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--bg-card); border: 1px solid rgba(0,163,181, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-height: 168px;">
            <i class="fa-solid fa-calculator absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 120px; right: -1rem; bottom: -1rem; color: #00a3b5; opacity: 0.06;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-calculator mb-4 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 32px; color: #00a3b5; filter: drop-shadow(0 0 12px rgba(0,163,181, 0.4));"></i>
                <h4 class="text-white font-bold text-lg mb-1.5 group-hover:text-[#00a3b5] transition-colors">Info KPR & Subsidi</h4>
                <p class="text-zinc-500 text-xs leading-relaxed">Simulasi kredit dan informasi subsidi perumahan</p>
            </div>
            <div class="relative z-10 mt-auto pt-4">
                <div class="tl-btn-base" style="background-color: rgba(0,163,181,0.1); color: #00a3b5; border: 1px solid rgba(0,163,181,0.2);">
                    <span>Simulasi KPR</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <!-- Card: Bank Desain Rumah -->
        <a href="<?= base_url('panduan_desain') ?>" data-tab-link data-tab-key="panduan_desain" data-tab-group="perumahan"
           class="group rounded-3xl p-5 sm:p-6 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--bg-card); border: 1px solid rgba(107,203,119, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-height: 168px;">
            <i class="fa-solid fa-pen-ruler absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 120px; right: -1rem; bottom: -1rem; color: #6bcb77; opacity: 0.06;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-pen-ruler mb-4 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 32px; color: #6bcb77; filter: drop-shadow(0 0 12px rgba(107,203,119, 0.4));"></i>
                <h4 class="text-white font-bold text-lg mb-1.5 group-hover:text-[#6bcb77] transition-colors">Bank Desain Rumah</h4>
                <p class="text-zinc-500 text-xs leading-relaxed">Prototipe desain rumah dari katalog Ternak Web</p>
            </div>
            <div class="relative z-10 mt-auto pt-4">
                <div class="tl-btn-base" style="background-color: rgba(107,203,119,0.1); color: #6bcb77; border: 1px solid rgba(107,203,119,0.2);">
                    <span>Lihat Desain</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

    </div>
</div>
