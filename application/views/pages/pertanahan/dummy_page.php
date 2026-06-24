<div class="pt-28 pb-20 min-h-[80vh] flex flex-col items-center justify-center text-center px-4">
    <!-- Icon -->
    <div class="mb-6 text-[#d6fb00] text-6xl md:text-7xl opacity-80">
        <i class="fa-solid fa-person-digging"></i>
    </div>
    
    <!-- Badge -->
    <div class="inline-block px-4 py-1.5 mb-6 rounded-full bg-[#d6fb00]/10 border border-[#d6fb00]/30 text-[#d6fb00] text-xs font-bold tracking-wider uppercase">
        Dalam Tahap Pengembangan (Coming Soon)
    </div>
    
    <!-- Title -->
    <h1 class="text-3xl md:text-4xl font-black text-white mb-4 tracking-tight">
        <?= isset($title) ? $title : 'Fitur Sedang Dibangun' ?>
    </h1>
    
    <!-- Description -->
    <p class="text-[#8aacb0] max-w-lg mx-auto mb-10 text-sm md:text-base leading-relaxed">
        <?= isset($desc) ? $desc : 'Fitur terkait pilar pertanahan ini sedang dalam tahap perancangan konsep dan akan segera hadir pada fase pembaruan Klinik PKP berikutnya.' ?>
    </p>
    
    <!-- Action Button -->
    <a href="<?= base_url() ?>" class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-[#0a1a1f] hover:bg-[#d6fb00]/10 text-[#d6fb00] font-semibold rounded-xl border border-[#d6fb00]/30 transition-all duration-300 group">
        <i class="fa-solid fa-arrow-left text-sm group-hover:-translate-x-1 transition-transform"></i>
        Kembali ke Beranda
    </a>
</div>
