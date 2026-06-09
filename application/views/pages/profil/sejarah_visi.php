<style>
  /* Custom overrides for missing JIT Tailwind classes */
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
    <!-- Breadcrumb Navigation -->
    <nav class="flex items-center gap-2.5 text-xs md:text-sm text-[#8aacb0] font-medium mb-8" aria-label="Breadcrumb">
        <a href="<?= base_url() ?>" class="hover:text-[#d6fb00] transition-colors duration-200 flex items-center gap-1.5">
            <i class="fa-solid fa-house text-[10px]"></i> Beranda
        </a>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-40"></i>
        <span class="hover:text-white transition-colors duration-200 cursor-default">
            Profil
        </span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-40"></i>
        <span class="text-white">Sejarah & Visi Misi</span>
    </nav>
    
    <div class="dark-bento-container shadow-2xl p-8 md:p-12">
        <h1 class="text-3xl md:text-4xl font-black neon-text-lime mb-6">Sejarah & Visi Misi</h1>
        <div class="border-b border-glass mb-8"></div>
        
        <div class="space-y-8 text-zinc-light leading-relaxed">
            <div>
                <h3 class="text-xl font-bold text-white mb-3">Sejarah Klinik PKP</h3>
                <p class="mb-4">
                    Klinik Perumahan dan Kawasan Permukiman (Klinik PKP) Disperakim Provinsi Jawa Tengah didirikan sebagai bentuk komitmen pemerintah provinsi dalam memberikan layanan informasi, konsultasi, dan fasilitasi terpadu di bidang perumahan dan kawasan permukiman.
                </p>
                <p>
                    Seiring dengan meningkatnya kebutuhan masyarakat terhadap hunian yang layak dan terjangkau, Klinik PKP bertransformasi menjadi platform digital yang mengintegrasikan berbagai layanan unggulan seperti Sikaper, Sikunang, Siperum, dan Sikumbang dalam satu pintu.
                </p>
            </div>
            
            <div>
                <h3 class="text-xl font-bold text-white mb-3">Visi</h3>
                <div class="bg-white/5 p-6 rounded-2xl border border-glass">
                    <p class="text-lg font-medium italic text-center">
                        "Terwujudnya Perumahan dan Kawasan Permukiman yang Layak, Aman, Terjangkau, dan Berkelanjutan bagi Seluruh Masyarakat Jawa Tengah."
                    </p>
                </div>
            </div>
            
            <div>
                <h3 class="text-xl font-bold text-white mb-3">Misi</h3>
                <ul class="list-disc list-inside space-y-2">
                    <li>Meningkatkan aksesibilitas masyarakat terhadap informasi perumahan dan kawasan permukiman.</li>
                    <li>Memfasilitasi kolaborasi antara pemerintah daerah, pengembang, dan masyarakat.</li>
                    <li>Mendorong inovasi pembiayaan dan penyediaan rumah layak huni (RTLH).</li>
                    <li>Menyelenggarakan tata kelola data spasial perumahan yang transparan dan akuntabel.</li>
                </ul>
            </div>
        </div>
    </div>
    </div>
</section>
