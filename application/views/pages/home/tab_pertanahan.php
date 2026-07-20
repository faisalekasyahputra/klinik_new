<!-- Tab Content: Pertanahan -->
<div class="py-4 sm:py-6 px-1 sm:px-2">
    <div class="flex items-center gap-2 mb-3">
        <i class="fa-solid fa-mountain-sun  text-[color:var(--portal-text)]"></i>
        <h2 class="text-sm font-bold uppercase tracking-widest text-[#2d6b75]">Layanan Pertanahan</h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">

        <!-- Card: Informasi Status Tanah -->
        <a href="<?= base_url('info_tanah') ?>" data-tab-link data-tab-key="info_tanah" data-tab-group="pertanahan"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow); min-height: 120px;">
            <i class="fa-solid fa-file-circle-check absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 80px; right: -1rem; bottom: -1rem; color: var(--portal-icon); opacity: 0.08;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-file-circle-check mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 24px; color: var(--portal-icon); "></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1 group-hover:text-[color:var(--portal-text)] transition-colors">Informasi Status Tanah</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">Cek status kepemilikan dan legalitas tanah</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: rgba(15,42,48,0.05); color: var(--portal-icon); border: 1px solid rgba(15,42,48,0.1);">
                    <span>Cek Status</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <!-- Card: Sertifikasi Lahan Perumahan -->
        <a href="<?= base_url('sertifikasi') ?>" data-tab-link data-tab-key="sertifikasi_tanah" data-tab-group="pertanahan"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow); min-height: 120px;">
            <i class="fa-solid fa-stamp absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 80px; right: -1rem; bottom: -1rem; color: var(--portal-icon); opacity: 0.08;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-stamp mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 24px; color: var(--portal-icon); "></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1 group-hover:text-[color:var(--portal-text)] transition-colors">Sertifikasi Lahan Perumahan</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">Informasi sertifikasi lahan untuk perumahan</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: rgba(15,42,48,0.05); color: var(--portal-icon); border: 1px solid rgba(15,42,48,0.1);">
                    <span>Info Sertifikasi</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <!-- Card: Penyelesaian Sengketa -->
        <a href="<?= base_url('sengketa') ?>" data-tab-link data-tab-key="sengketa" data-tab-group="pertanahan"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow); min-height: 120px;">
            <i class="fa-solid fa-gavel absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 80px; right: -1rem; bottom: -1rem; color: var(--portal-icon); opacity: 0.08;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-gavel mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 24px; color: var(--portal-icon); "></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1 group-hover:text-[color:var(--portal-text)] transition-colors">Penyelesaian Sengketa</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">Panduan penyelesaian sengketa lahan perumahan</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: rgba(15,42,48,0.05); color: var(--portal-icon); border: 1px solid rgba(15,42,48,0.1);">
                    <span>Konsultasi</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <!-- Card: Bank Tanah (Land Bank) -->
        <a href="<?= base_url('bank_tanah') ?>" data-tab-link data-tab-key="bank_tanah" data-tab-group="pertanahan"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow); min-height: 120px;">
            <i class="fa-solid fa-mountain-sun absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 80px; right: -1rem; bottom: -1rem; color: var(--portal-icon); opacity: 0.08;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-mountain-sun mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 24px; color: var(--portal-icon); "></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1 group-hover:text-[color:var(--portal-text)] transition-colors">Bank Tanah (Land Bank)</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">Informasi ketersediaan lahan untuk pembangunan</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: rgba(15,42,48,0.05); color: var(--portal-icon); border: 1px solid rgba(15,42,48,0.1);">
                    <span>Lihat Data</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

    </div>
</div>
