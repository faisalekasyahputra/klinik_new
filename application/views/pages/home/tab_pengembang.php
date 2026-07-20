<!-- Tab Content: Pengembang -->
<div class="py-4 sm:py-6 px-1 sm:px-2">
    <div class="flex items-center gap-2 mb-3">
        <i class="fa-solid fa-helmet-safety text-[#d6fb00]"></i>
        <h2 class="text-sm font-bold uppercase tracking-widest text-[#8aacb0]">Pengembang Perumahan</h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 sm:gap-3">

        <!-- Card: Daftar Pengembang Tersertifikasi -->
        <a href="<?= base_url('Pengembang/sertifikasi') ?>" data-tab-link data-tab-key="pengembang_list" data-tab-group="pengembang"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--bg-card); border: 1px solid rgba(214,251,0, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-height: 120px;">
            <i class="fa-solid fa-users absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 80px; right: -1rem; bottom: -1rem; color: #d6fb00; opacity: 0.06;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-users mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 24px; color: #d6fb00; filter: drop-shadow(0 0 12px rgba(214,251,0, 0.4));"></i>
                <h4 class="text-white font-bold text-sm mb-1 group-hover:text-[#d6fb00] transition-colors">Daftar Pengembang Tersertifikasi</h4>
                <p class="text-zinc-500 text-xs leading-relaxed">Daftar pengembang perumahan yang telah tersertifikasi SRP2</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: rgba(214,251,0,0.1); color: #d6fb00; border: 1px solid rgba(214,251,0,0.2);">
                    <span>Lihat Daftar</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <!-- Card: Syarat & Ketentuan SRP2 -->
        <a href="<?= base_url('Pengembang/syarat') ?>" data-tab-link data-tab-key="pengembang_syarat" data-tab-group="pengembang"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--bg-card); border: 1px solid rgba(0,163,181, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-height: 120px;">
            <i class="fa-solid fa-clipboard-list absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 80px; right: -1rem; bottom: -1rem; color: #00a3b5; opacity: 0.06;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-clipboard-list mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 24px; color: #00a3b5; filter: drop-shadow(0 0 12px rgba(0,163,181, 0.4));"></i>
                <h4 class="text-white font-bold text-sm mb-1 group-hover:text-[#00a3b5] transition-colors">Syarat & Ketentuan SRP2</h4>
                <p class="text-zinc-500 text-xs leading-relaxed">Informasi persyaratan dan dokumen pendaftaran SRP2</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: rgba(0,163,181,0.1); color: #00a3b5; border: 1px solid rgba(0,163,181,0.2);">
                    <span>Lihat Syarat</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <!-- Card: Formulir Pendaftaran SRP2 -->
        <a href="<?= base_url('Pengembang/formulir') ?>" data-tab-link data-tab-key="pengembang_formulir" data-tab-group="pengembang"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--bg-card); border: 1px solid rgba(107,203,119, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-height: 120px;">
            <i class="fa-solid fa-file-signature absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 80px; right: -1rem; bottom: -1rem; color: #6bcb77; opacity: 0.06;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-file-signature mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 24px; color: #6bcb77; filter: drop-shadow(0 0 12px rgba(107,203,119, 0.4));"></i>
                <h4 class="text-white font-bold text-sm mb-1 group-hover:text-[#6bcb77] transition-colors">Formulir Pendaftaran SRP2</h4>
                <p class="text-zinc-500 text-xs leading-relaxed">Formulir pendaftaran sertifikasi pengembang perumahan</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: rgba(107,203,119,0.1); color: #6bcb77; border: 1px solid rgba(107,203,119,0.2);">
                    <span>Daftar Sekarang</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

    </div>
</div>
