<!-- Tab Content: Pertanahan -->
<div class="py-4 sm:py-6 px-1 sm:px-2">
    <div class="flex items-center gap-2 mb-3">
        <i class="fa-solid fa-mountain-sun text-[#d6fb00]"></i>
        <h2 class="text-sm font-bold uppercase tracking-widest text-[#8aacb0]">Layanan Pertanahan</h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">

        <!-- Card: Informasi Status Tanah -->
        <a href="<?= base_url('info_tanah') ?>" data-tab-link data-tab-key="info_tanah" data-tab-group="pertanahan"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--bg-card); border: 1px solid rgba(0,163,181, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-height: 120px;">
            <i class="fa-solid fa-file-circle-check absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 80px; right: -1rem; bottom: -1rem; color: #00a3b5; opacity: 0.06;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-file-circle-check mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 24px; color: #00a3b5; filter: drop-shadow(0 0 12px rgba(0,163,181, 0.4));"></i>
                <h4 class="text-white font-bold text-sm mb-1 group-hover:text-[#00a3b5] transition-colors">Informasi Status Tanah</h4>
                <p class="text-zinc-500 text-xs leading-relaxed">Cek status kepemilikan dan legalitas tanah</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: rgba(0,163,181,0.1); color: #00a3b5; border: 1px solid rgba(0,163,181,0.2);">
                    <span>Cek Status</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <!-- Card: Sertifikasi Lahan Perumahan -->
        <a href="<?= base_url('sertifikasi') ?>" data-tab-link data-tab-key="sertifikasi_tanah" data-tab-group="pertanahan"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--bg-card); border: 1px solid rgba(214,251,0, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-height: 120px;">
            <i class="fa-solid fa-stamp absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 80px; right: -1rem; bottom: -1rem; color: #d6fb00; opacity: 0.06;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-stamp mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 24px; color: #d6fb00; filter: drop-shadow(0 0 12px rgba(214,251,0, 0.4));"></i>
                <h4 class="text-white font-bold text-sm mb-1 group-hover:text-[#d6fb00] transition-colors">Sertifikasi Lahan Perumahan</h4>
                <p class="text-zinc-500 text-xs leading-relaxed">Informasi sertifikasi lahan untuk perumahan</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: rgba(214,251,0,0.1); color: #d6fb00; border: 1px solid rgba(214,251,0,0.2);">
                    <span>Info Sertifikasi</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <!-- Card: Penyelesaian Sengketa -->
        <a href="<?= base_url('sengketa') ?>" data-tab-link data-tab-key="sengketa" data-tab-group="pertanahan"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--bg-card); border: 1px solid rgba(245,158,11, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-height: 120px;">
            <i class="fa-solid fa-gavel absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 80px; right: -1rem; bottom: -1rem; color: #f59e0b; opacity: 0.06;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-gavel mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 24px; color: #f59e0b; filter: drop-shadow(0 0 12px rgba(245,158,11, 0.4));"></i>
                <h4 class="text-white font-bold text-sm mb-1 group-hover:text-[#f59e0b] transition-colors">Penyelesaian Sengketa</h4>
                <p class="text-zinc-500 text-xs leading-relaxed">Panduan penyelesaian sengketa lahan perumahan</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: rgba(245,158,11,0.1); color: #f59e0b; border: 1px solid rgba(245,158,11,0.2);">
                    <span>Konsultasi</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <!-- Card: Bank Tanah (Land Bank) -->
        <a href="<?= base_url('bank_tanah') ?>" data-tab-link data-tab-key="bank_tanah" data-tab-group="pertanahan"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--bg-card); border: 1px solid rgba(107,203,119, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-height: 120px;">
            <i class="fa-solid fa-mountain-sun absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 80px; right: -1rem; bottom: -1rem; color: #6bcb77; opacity: 0.06;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-mountain-sun mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 24px; color: #6bcb77; filter: drop-shadow(0 0 12px rgba(107,203,119, 0.4));"></i>
                <h4 class="text-white font-bold text-sm mb-1 group-hover:text-[#6bcb77] transition-colors">Bank Tanah (Land Bank)</h4>
                <p class="text-zinc-500 text-xs leading-relaxed">Informasi ketersediaan lahan untuk pembangunan</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: rgba(107,203,119,0.1); color: #6bcb77; border: 1px solid rgba(107,203,119,0.2);">
                    <span>Lihat Data</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

    </div>
</div>
