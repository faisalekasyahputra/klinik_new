<div class="py-4 sm:py-6 px-1 sm:px-2">
    <!-- Header -->
    <div class="mb-6 max-w-2xl">
        <h2 class="text-2xl sm:text-3xl font-black text-[color:var(--portal-text)] tracking-tighter font-jakarta mb-2">
            Nggolek <span class="text-[color:var(--brand)]">Omah</span>
        </h2>
        <p class="text-[color:var(--portal-text-muted)] text-xs sm:text-sm leading-relaxed">
            Pilih langkah yang sesuai kebutuhanmu — cari rumah yang sudah tersedia, cari panduan desain untuk bangun sendiri, atau temukan solusi pembiayaan yang cocok.
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3">

        <!-- Card 1: Cari Rumah -->
        <a href="<?= base_url('cari_rumah') ?>" data-tab-link data-tab-key="cari_rumah" data-tab-group="perumahan"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow); min-height: 140px;">
            <i class="fa-solid fa-magnifying-glass-location absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 90px; right: -1rem; bottom: -1rem; color: var(--portal-icon); opacity: 0.08;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-magnifying-glass-location mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 28px; color: var(--portal-icon);"></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1 group-hover:text-[color:var(--portal-text)] transition-colors">Cari Rumah?</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">Telusuri rumah subsidi dan non-subsidi yang sudah tersedia di Jawa Tengah.</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: var(--portal-btn-bg); color: var(--portal-icon); border: 1px solid var(--portal-btn-border);">
                    <span>Cari Sekarang</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <!-- Card 2: Panduan Desain Rumah -->
        <a href="<?= base_url('panduan_desain') ?>" data-tab-link data-tab-key="panduan_desain" data-tab-group="perumahan"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow); min-height: 140px;">
            <i class="fa-solid fa-pen-ruler absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 90px; right: -1rem; bottom: -1rem; color: var(--portal-icon); opacity: 0.08;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-pen-ruler mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 28px; color: var(--portal-icon);"></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1 group-hover:text-[color:var(--portal-text)] transition-colors">Panduan Desain Rumah</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">Referensi desain untuk rumah swadaya, dari Bank Desain kami.</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: var(--portal-btn-bg); color: var(--portal-icon); border: 1px solid var(--portal-btn-border);">
                    <span>Lihat Desain</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <!-- Card 3: Temukan Solusi Pembiayaan -->
        <a href="<?= base_url('solusi_pembiayaan') ?>" data-tab-link data-tab-key="solusi_pembiayaan" data-tab-group="perumahan"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow); min-height: 140px;">
            <i class="fa-solid fa-hand-holding-dollar absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 90px; right: -1rem; bottom: -1rem; color: var(--portal-icon); opacity: 0.08;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-hand-holding-dollar mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 28px; color: var(--portal-icon);"></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1 group-hover:text-[color:var(--portal-text)] transition-colors">Temukan Solusi Pembiayaan</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">Cek program bantuan perumahan yang sesuai kondisimu.</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: var(--portal-btn-bg); color: var(--portal-icon); border: 1px solid var(--portal-btn-border);">
                    <span>Cek Kelayakan</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

    </div>
</div>
