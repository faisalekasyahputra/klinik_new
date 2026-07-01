<section class="w-full pt-24 pb-16 px-4 sm:px-6 lg:px-8 relative min-h-screen font-outfit">

    <!-- Background Ornaments -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="gradient-mesh absolute inset-0"></div>
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[300px] blur-[120px] rounded-full pointer-events-none" style="background: rgba(214,251,0,0.05);"></div>
    </div>

    <div class="max-w-7xl mx-auto relative z-10">

        <!-- Breadcrumb -->
        <div class="mb-10 animate-fade-in-up">
            <nav class="flex text-[10px] sm:text-xs font-bold uppercase tracking-widest" style="color: var(--text-muted);" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2">
                    <li class="inline-flex items-center">
                        <a href="<?= base_url() ?>" class="transition-colors hover:text-white" style="color: var(--text-muted);">
                            <i class="fa-solid fa-house mr-2"></i>Beranda
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fa-solid fa-chevron-right text-[8px] mx-2"></i>
                            <span style="color: var(--brand);">Layanan Umum</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <!-- Header -->
        <div class="text-left mb-12 space-y-4 max-w-2xl animate-fade-in-up delay-100">
            <h2 class="text-3xl sm:text-4xl font-black tracking-tighter font-jakarta" style="color: var(--text-primary);">
                Layanan Informasi <span style="color: var(--brand);">Perumahan</span>
            </h2>
            <p class="text-sm sm:text-base leading-relaxed" style="color: var(--text-secondary);">
                Pilih kategori layanan sesuai dengan pilar kebutuhan informasi, validasi data, dan fasilitas hunian Anda.
            </p>
        </div>

        <!-- Card Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <!-- Card 1: Klinik Diagnosa (ex Housing Career) — CTA Utama -->
            <a href="<?= base_url('Program/diagnosa/umum') ?>" class="flex flex-col h-full group animate-fade-in-up delay-100">
                <div class="flex-1 p-6 rounded-[24px] flex flex-col justify-between transition-all duration-300 group-hover:-translate-y-1 shadow-lg"
                     style="background: var(--bg-card); border: 1px solid rgba(214,251,0,0.15);"
                     onmouseenter="this.style.borderColor='rgba(214,251,0,0.55)'; this.style.boxShadow='0 8px 30px rgba(214,251,0,0.12)';"
                     onmouseleave="this.style.borderColor='rgba(214,251,0,0.15)'; this.style.boxShadow='0 10px 15px -3px rgba(0,0,0,0.1)';">
                    <div class="flex items-start gap-4">
                        <div class="shrink-0 pt-0.5 transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-[5deg]" style="color: var(--brand);">
                            <i class="fa-solid fa-chart-line text-[28px]"></i>
                        </div>
                        <div class="space-y-1.5 pt-1 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h4 class="font-bold text-base tracking-tight transition-colors" style="color: var(--text-primary);"
                                    onmouseenter="this.closest('a').querySelector('h4').style.color='var(--brand)'"
                                    >Klinik Diagnosa</h4>
                                <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider" style="background: rgba(214,251,0,0.15); color: var(--brand); border: 1px solid rgba(214,251,0,0.3);">BARU</span>
                            </div>
                            <p class="text-xs leading-relaxed" style="color: var(--text-muted);">Cek kelayakan program bantuan perumahan dengan Wizard Smart Filter 4 langkah. Temukan program yang tepat untuk Anda.</p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-4 mt-auto">
                        <i class="fa-solid fa-arrow-right text-xs transition-all duration-300 group-hover:translate-x-1" style="color: var(--text-faint);"></i>
                    </div>
                </div>
            </a>

            <!-- Card 2: Cek DTSN -->
            <a href="<?= base_url('Program/diagnosa/umum') ?>" class="flex flex-col h-full group animate-fade-in-up delay-200">
                <div class="flex-1 p-6 rounded-[24px] flex flex-col justify-between transition-all duration-300 group-hover:-translate-y-1 shadow-lg"
                     style="background: var(--bg-card); border: 1px solid rgba(214,251,0,0.15);"
                     onmouseenter="this.style.borderColor='rgba(214,251,0,0.55)'; this.style.boxShadow='0 8px 30px rgba(214,251,0,0.12)';"
                     onmouseleave="this.style.borderColor='rgba(214,251,0,0.15)'; this.style.boxShadow='0 10px 15px -3px rgba(0,0,0,0.1)';">
                    <div class="flex items-start gap-4">
                        <div class="shrink-0 pt-0.5 transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-[5deg]" style="color: var(--brand);">
                            <i class="fa-solid fa-id-card-clip text-[28px]"></i>
                        </div>
                        <div class="space-y-1.5 pt-1">
                            <h4 class="font-bold text-base tracking-tight transition-colors" style="color: var(--text-primary);">Cek DTSN</h4>
                            <p class="text-xs leading-relaxed" style="color: var(--text-muted);">Fasilitas pengecekan status Data Terpadu Sasaran Nilai untuk memastikan validasi usulan bantuan perumahan.</p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-4 mt-auto">
                        <i class="fa-solid fa-arrow-right text-xs transition-all duration-300 group-hover:translate-x-1" style="color: var(--text-faint);"></i>
                    </div>
                </div>
            </a>

            <!-- Card 3: Rumah Subsidi & Non Subsidi -->
            <a href="<?= base_url('umum/sebaran') ?>" class="flex flex-col h-full group animate-fade-in-up delay-300">
                <div class="flex-1 p-6 rounded-[24px] flex flex-col justify-between transition-all duration-300 group-hover:-translate-y-1 shadow-lg"
                     style="background: var(--bg-card); border: 1px solid rgba(214,251,0,0.15);"
                     onmouseenter="this.style.borderColor='rgba(107,203,119,0.50)'; this.style.boxShadow='0 8px 30px rgba(107,203,119,0.10)';"
                     onmouseleave="this.style.borderColor='rgba(214,251,0,0.15)'; this.style.boxShadow='0 10px 15px -3px rgba(0,0,0,0.1)';">
                    <div class="flex items-start gap-4">
                        <div class="shrink-0 pt-0.5 transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-[5deg]" style="color: var(--emerald);">
                            <i class="fa-solid fa-layer-group text-[28px]"></i>
                        </div>
                        <div class="space-y-1.5 pt-1">
                            <h4 class="font-bold text-base tracking-tight transition-colors" style="color: var(--text-primary);">Rumah Subsidi &amp; Non Subsidi</h4>
                            <p class="text-xs leading-relaxed" style="color: var(--text-muted);">Informasi berbasis peta lengkap hunian komersial resmi serta rumah subsidi program pemerintah di Jawa Tengah.</p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-4 mt-auto">
                        <i class="fa-solid fa-arrow-right text-xs transition-all duration-300 group-hover:translate-x-1" style="color: var(--text-faint);"></i>
                    </div>
                </div>
            </a>

            <!-- Card 4: Informasi Pengembang -->
            <a href="<?= base_url('umum/pengembang') ?>" class="flex flex-col h-full group animate-fade-in-up delay-300">
                <div class="flex-1 p-6 rounded-[24px] flex flex-col justify-between transition-all duration-300 group-hover:-translate-y-1 shadow-lg"
                     style="background: var(--bg-card); border: 1px solid rgba(214,251,0,0.15);"
                     onmouseenter="this.style.borderColor='rgba(77,150,255,0.50)'; this.style.boxShadow='0 8px 30px rgba(77,150,255,0.10)';"
                     onmouseleave="this.style.borderColor='rgba(214,251,0,0.15)'; this.style.boxShadow='0 10px 15px -3px rgba(0,0,0,0.1)';">
                    <div class="flex items-start gap-4">
                        <div class="shrink-0 pt-0.5 transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-[5deg]" style="color: var(--info);">
                            <i class="fa-solid fa-city text-[28px]"></i>
                        </div>
                        <div class="space-y-1.5 pt-1">
                            <h4 class="font-bold text-base tracking-tight transition-colors" style="color: var(--text-primary);">Informasi Pengembang</h4>
                            <p class="text-xs leading-relaxed" style="color: var(--text-muted);">Daftar asosiasi dan perusahaan pengembang properti resmi yang terdaftar di sistem Tapera.</p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-4 mt-auto">
                        <i class="fa-solid fa-arrow-right text-xs transition-all duration-300 group-hover:translate-x-1" style="color: var(--text-faint);"></i>
                    </div>
                </div>
            </a>

            <!-- Card 5: Layanan & Aduan -->
            <a href="<?= base_url('umum/aduan') ?>" class="flex flex-col h-full group animate-fade-in-up delay-400">
                <div class="flex-1 p-6 rounded-[24px] flex flex-col justify-between transition-all duration-300 group-hover:-translate-y-1 shadow-lg"
                     style="background: var(--bg-card); border: 1px solid rgba(214,251,0,0.15);"
                     onmouseenter="this.style.borderColor='rgba(255,107,107,0.50)'; this.style.boxShadow='0 8px 30px rgba(255,107,107,0.10)';"
                     onmouseleave="this.style.borderColor='rgba(214,251,0,0.15)'; this.style.boxShadow='0 10px 15px -3px rgba(0,0,0,0.1)';">
                    <div class="flex items-start gap-4">
                        <div class="shrink-0 pt-0.5 transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-[5deg]" style="color: var(--error);">
                            <i class="fa-solid fa-circle-exclamation text-[28px]"></i>
                        </div>
                        <div class="space-y-1.5 pt-1">
                            <h4 class="font-bold text-base tracking-tight transition-colors" style="color: var(--text-primary);">Layanan &amp; Aduan</h4>
                            <p class="text-xs leading-relaxed" style="color: var(--text-muted);">Pusat pelaporan dan konsultasi masalah sengketa lahan, rumah rusak, atau kendala fasilitas prasarana umum.</p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-4 mt-auto">
                        <i class="fa-solid fa-arrow-right text-xs transition-all duration-300 group-hover:translate-x-1" style="color: var(--text-faint);"></i>
                    </div>
                </div>
            </a>

            <!-- Card 6: Forum Diskusi -->
            <a href="<?= base_url('umum/forum') ?>" class="flex flex-col h-full group animate-fade-in-up delay-500">
                <div class="flex-1 p-6 rounded-[24px] flex flex-col justify-between transition-all duration-300 group-hover:-translate-y-1 shadow-lg"
                     style="background: var(--bg-card); border: 1px solid rgba(214,251,0,0.15);"
                     onmouseenter="this.style.borderColor='rgba(0,163,181,0.50)'; this.style.boxShadow='0 8px 30px rgba(0,163,181,0.10)';"
                     onmouseleave="this.style.borderColor='rgba(214,251,0,0.15)'; this.style.boxShadow='0 10px 15px -3px rgba(0,0,0,0.1)';">
                    <div class="flex items-start gap-4">
                        <div class="shrink-0 pt-0.5 transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-[5deg]" style="color: var(--teal-bright);">
                            <i class="fa-solid fa-comments text-[28px]"></i>
                        </div>
                        <div class="space-y-1.5 pt-1">
                            <h4 class="font-bold text-base tracking-tight transition-colors" style="color: var(--text-primary);">Forum Diskusi</h4>
                            <p class="text-xs leading-relaxed" style="color: var(--text-muted);">Ruang interaksi komunitas warga untuk saling berbagi info seputar pemeliharaan lingkungan perumahan.</p>
                        </div>
                    </div>
                    <div class="flex justify-end pt-4 mt-auto">
                        <i class="fa-solid fa-arrow-right text-xs transition-all duration-300 group-hover:translate-x-1" style="color: var(--text-faint);"></i>
                    </div>
                </div>
            </a>

        </div><!-- end grid -->

    </div>
</section>

<script>
// Progressive hover color update for h4 title via JS (since inline onmouseenter can't read parent's hover)
document.querySelectorAll('.group').forEach(card => {
    const h4 = card.querySelector('h4');
    const iconEl = card.querySelector('[class*="fa-"]');
    if (!h4) return;

    card.addEventListener('mouseenter', () => {
        // Get the icon color from the icon container
        const iconContainer = card.querySelector('div[style*="color: var("]');
        if (iconContainer && h4) {
            const color = iconContainer.style.color;
            h4.style.color = color || 'var(--brand)';
        }
    });
    card.addEventListener('mouseleave', () => {
        if (h4) h4.style.color = 'var(--text-primary)';
    });
});
</script>
