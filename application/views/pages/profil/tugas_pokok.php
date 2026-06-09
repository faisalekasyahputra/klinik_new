<style>
  .dark-bento-container {
    background-color: rgba(15, 42, 48, 0.4);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid rgba(214, 251, 0, 0.08);
    border-radius: 24px;
  }
  .neon-text-lime { color: #d6fb00; }
  .text-zinc-light { color: rgba(236, 255, 182, 0.9); }
  .border-glass { border-color: rgba(255, 255, 255, 0.08); }
</style>

<section class="w-full pt-24 pb-16 px-4 sm:px-6 lg:px-8 relative min-h-screen font-outfit">
    <!-- Background Ornaments -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute top-[-10%] right-[-5%] w-[600px] h-[600px] rounded-full bg-[#d6fb00]/5 blur-[120px]"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[500px] h-[500px] rounded-full bg-[#00a3b5]/5 blur-[100px]"></div>
    </div>

    <div class="max-w-7xl mx-auto relative z-10">
    <nav class="flex items-center gap-2.5 text-xs md:text-sm text-[#8aacb0] font-medium mb-8" aria-label="Breadcrumb">
        <a href="<?= base_url() ?>" class="hover:text-[#d6fb00] transition-colors duration-200 flex items-center gap-1.5">
            <i class="fa-solid fa-house text-[10px]"></i> Beranda
        </a>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-40"></i>
        <span class="hover:text-white transition-colors duration-200 cursor-default">Profil</span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-40"></i>
        <span class="text-white">Tugas Pokok & Fungsi</span>
    </nav>
    
    <div class="dark-bento-container shadow-2xl p-8 md:p-12">
        <h1 class="text-3xl md:text-4xl font-black neon-text-lime mb-6">Tugas Pokok & Fungsi</h1>
        <div class="border-b border-glass mb-8"></div>
        
        <p class="text-zinc-light leading-relaxed mb-8">
            Dinas Perumahan Rakyat dan Kawasan Permukiman Provinsi Jawa Tengah memiliki tugas pokok melaksanakan urusan pemerintahan bidang perumahan dan kawasan permukiman yang menjadi kewenangan daerah provinsi.
        </p>

        <div class="space-y-4">
            <div class="bg-white/5 p-5 rounded-2xl border border-glass hover:bg-white/10 hover:border-[#d6fb00]/30 transition-all duration-300 group cursor-default">
                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-xl bg-[#d6fb00]/10 text-[#d6fb00] flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-1"></i>
                    </div>
                    <div>
                        <h4 class="text-white font-bold mb-2">Perumusan Kebijakan</h4>
                        <p class="text-sm text-[#8aacb0] leading-relaxed">Merumuskan kebijakan teknis di bidang perumahan, kawasan permukiman, serta prasarana, sarana, dan utilitas umum.</p>
                    </div>
                </div>
            </div>

            <div class="bg-white/5 p-5 rounded-2xl border border-glass hover:bg-white/10 hover:border-[#d6fb00]/30 transition-all duration-300 group cursor-default">
                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-xl bg-[#d6fb00]/10 text-[#d6fb00] flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-2"></i>
                    </div>
                    <div>
                        <h4 class="text-white font-bold mb-2">Pelaksanaan Kebijakan</h4>
                        <p class="text-sm text-[#8aacb0] leading-relaxed">Menyelenggarakan pelaksanaan kebijakan di bidang penyediaan perumahan, penataan kawasan permukiman kumuh, dan penyediaan tanah.</p>
                    </div>
                </div>
            </div>

            <div class="bg-white/5 p-5 rounded-2xl border border-glass hover:bg-white/10 hover:border-[#d6fb00]/30 transition-all duration-300 group cursor-default">
                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-xl bg-[#d6fb00]/10 text-[#d6fb00] flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-3"></i>
                    </div>
                    <div>
                        <h4 class="text-white font-bold mb-2">Evaluasi & Pelaporan</h4>
                        <p class="text-sm text-[#8aacb0] leading-relaxed">Melaksanakan pemantauan, evaluasi, dan pelaporan pelaksanaan urusan perumahan dan kawasan permukiman di wilayah Jawa Tengah.</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white/5 p-5 rounded-2xl border border-glass hover:bg-white/10 hover:border-[#d6fb00]/30 transition-all duration-300 group cursor-default">
                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-xl bg-[#d6fb00]/10 text-[#d6fb00] flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                        <i class="fa-solid fa-4"></i>
                    </div>
                    <div>
                        <h4 class="text-white font-bold mb-2">Fasilitasi & Mediasi</h4>
                        <p class="text-sm text-[#8aacb0] leading-relaxed">Memberikan dukungan fasilitasi pelayanan Klinik PKP sebagai pusat informasi dan penanganan aduan masyarakat terkait perumahan rakyat.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
</section>
