<!-- Tab Content: Kawasan -->
<div class="py-4 sm:py-6 px-1 sm:px-2">
    <div class="flex items-center gap-2 mb-3">
        <i class="fa-solid fa-city text-[#d6fb00]"></i>
        <h2 class="text-sm font-bold uppercase tracking-widest text-[#2d6b75]">Data Kawasan</h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">

        <!-- Card: Sebaran RTLH -->
        <a href="<?= base_url('sebaran') ?>" data-tab-link data-tab-key="sebaran" data-tab-group="kawasan"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--bg-card); border: 1px solid rgba(245,158,11, 0.15); box-shadow: 0 2px 12px rgba(0,50,60,0.08); min-height: 120px;">
            <i class="fa-solid fa-house-crack absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 80px; right: -1rem; bottom: -1rem; color: #f59e0b; opacity: 0.08;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-house-crack mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 24px; color: #f59e0b; filter: drop-shadow(0 0 12px rgba(245,158,11, 0.4));"></i>
                <h4 class="text-[#0f2a30] font-bold text-sm mb-1 group-hover:text-[#f59e0b] transition-colors">Sebaran RTLH</h4>
                <p class="text-[#5a8a92] text-xs leading-relaxed">Peta sebaran rumah tidak layak huni di Jawa Tengah</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: rgba(245,158,11,0.1); color: #f59e0b; border: 1px solid rgba(245,158,11,0.2);">
                    <span>Lihat Peta</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <!-- Card: Sebaran Rusun -->
        <a href="<?= base_url('sebaran_rusun') ?>" data-tab-link data-tab-key="sebaran_rusun" data-tab-group="kawasan"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--bg-card); border: 1px solid rgba(0,163,181, 0.15); box-shadow: 0 2px 12px rgba(0,50,60,0.08); min-height: 120px;">
            <i class="fa-solid fa-building absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 80px; right: -1rem; bottom: -1rem; color: #00a3b5; opacity: 0.08;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-building mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 24px; color: #00a3b5; filter: drop-shadow(0 0 12px rgba(0,163,181, 0.4));"></i>
                <h4 class="text-[#0f2a30] font-bold text-sm mb-1 group-hover:text-[#00a3b5] transition-colors">Sebaran Rusun</h4>
                <p class="text-[#5a8a92] text-xs leading-relaxed">Peta sebaran rumah susun di Jawa Tengah</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: rgba(0,163,181,0.1); color: #00a3b5; border: 1px solid rgba(0,163,181,0.2);">
                    <span>Lihat Peta</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <!-- Card: Profil Kawasan Kumuh -->
        <a href="<?= base_url('profil_kumuh') ?>" data-tab-link data-tab-key="profil_kumuh" data-tab-group="kawasan"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--bg-card); border: 1px solid rgba(214,251,0, 0.15); box-shadow: 0 2px 12px rgba(0,50,60,0.08); min-height: 120px;">
            <i class="fa-solid fa-city absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 80px; right: -1rem; bottom: -1rem; color: #d6fb00; opacity: 0.08;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-city mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 24px; color: #d6fb00; filter: drop-shadow(0 0 12px rgba(214,251,0, 0.4));"></i>
                <h4 class="text-[#0f2a30] font-bold text-sm mb-1 group-hover:text-[#d6fb00] transition-colors">Profil Kawasan Kumuh</h4>
                <p class="text-[#5a8a92] text-xs leading-relaxed">Data profil dan deliniasi kawasan kumuh</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: rgba(214,251,0,0.1); color: #d6fb00; border: 1px solid rgba(214,251,0,0.2);">
                    <span>Lihat Data</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <!-- Card: Sebaran Bantuan SDGS -->
        <a href="<?= base_url('sebaran_sdgs') ?>" data-tab-link data-tab-key="sebaran_sdgs" data-tab-group="kawasan"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--bg-card); border: 1px solid rgba(107,203,119, 0.15); box-shadow: 0 2px 12px rgba(0,50,60,0.08); min-height: 120px;">
            <i class="fa-solid fa-hand-holding-heart absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 80px; right: -1rem; bottom: -1rem; color: #6bcb77; opacity: 0.08;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-hand-holding-heart mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 24px; color: #6bcb77; filter: drop-shadow(0 0 12px rgba(107,203,119, 0.4));"></i>
                <h4 class="text-[#0f2a30] font-bold text-sm mb-1 group-hover:text-[#6bcb77] transition-colors">Sebaran Bantuan SDGS</h4>
                <p class="text-[#5a8a92] text-xs leading-relaxed">Peta sebaran bantuan program SDGS</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: rgba(107,203,119,0.1); color: #6bcb77; border: 1px solid rgba(107,203,119,0.2);">
                    <span>Lihat Peta</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

    </div>
</div>
