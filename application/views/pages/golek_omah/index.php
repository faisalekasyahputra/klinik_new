<div class="py-4 sm:py-6 px-1 sm:px-2">
    <!-- Header -->
    <div class="mb-6 max-w-2xl">
        <h2 class="text-2xl sm:text-3xl font-black text-[color:var(--portal-text)] tracking-tighter font-jakarta mb-2">
            Nggolek <span class="text-[color:var(--portal-brand)]">Omah</span>
        </h2>
        <p class="text-[color:var(--portal-text-muted)] text-xs sm:text-sm leading-relaxed">
            Pilih langkah yang sesuai kebutuhanmu — cari rumah yang sudah tersedia, cari panduan desain untuk bangun sendiri, atau temukan solusi pembiayaan yang cocok.
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3">

        <!-- Card 1: Cari Rumah -->
        <a href="<?= base_url('cari_rumah') ?>" data-tab-link data-tab-key="cari_rumah" data-tab-group="perumahan"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow); min-height: 140px;">
            <i class="fa-solid fa-magnifying-glass-location absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 90px; right: -1rem; bottom: -1rem; color: var(--portal-icon); opacity: 0.08;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-magnifying-glass-location mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 28px; color: var(--portal-icon);"></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1 group-hover:text-[color:var(--portal-text)] transition-colors">Cari Rumah ?</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">(Rumah Subsidi dan Non Subsidi)</p>
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
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1 group-hover:text-[color:var(--portal-text)] transition-colors">Desain Rumah</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">(Rumah Swadaya)</p>
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
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1 group-hover:text-[color:var(--portal-text)] transition-colors">Diagnosa Pembiayaan</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">(Program Perumahan)</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: var(--portal-btn-bg); color: var(--portal-icon); border: 1px solid var(--portal-btn-border);">
                    <span>Cek Kelayakan</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <!-- Card 4: Wizard Kelayakan Warga -->
        <a href="<?= base_url('warga/pendataan') ?>"
           data-tab-link data-tab-key="warga_pendataan"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-brand); box-shadow: var(--portal-shadow); min-height: 140px;">
            <span class="absolute right-3 top-3 z-20 inline-flex items-center gap-1 rounded-full px-2 py-1 text-[9px] font-black uppercase tracking-wider"
                  style="background:var(--portal-brand);color:#06333b"><span class="h-1.5 w-1.5 rounded-full bg-current"></span> Wizard Baru</span>
            <i class="fa-solid fa-clipboard-check absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 90px; right: -1rem; bottom: -1rem; color: var(--portal-icon); opacity: 0.08;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-clipboard-check mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 28px; color: var(--portal-icon);"></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1 transition-colors">Cek Kelayakan Program</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">(Pendataan Warga Lengkap)</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: var(--portal-brand); color: #06333b; border: 1px solid var(--portal-brand);">
                    <span>Mulai Wizard</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

    </div>
</div>
