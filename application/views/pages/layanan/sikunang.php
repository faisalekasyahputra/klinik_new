<!-- application/views/pages/layanan/sikunang.php -->
<style>
  .dark-bento-container {
    background-color: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid rgba(214, 251, 0, 0.08);
    border-radius: 24px;
  }
  .neon-text-lime { color: #d6fb00; }
</style>

<section class="w-full pt-16 pb-16 px-4 sm:px-6 lg:px-8 relative min-h-screen font-outfit">
    <!-- Background Ornaments -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute top-[-10%] right-[-5%] w-[600px] h-[600px] rounded-full bg-[#d6fb00]/5 blur-[120px]"></div>
    </div>

    <div class="max-w-7xl mx-auto relative z-10">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2.5 text-xs md:text-sm text-[#8aacb0] font-medium mb-8 animate-fade-in-up delay-100" aria-label="Breadcrumb">
            <a href="<?= base_url() ?>" class="hover:text-[#d6fb00] transition-colors duration-200 flex items-center gap-1.5">
                <i class="fa-solid fa-house text-[10px]"></i> Beranda
            </a>
            <i class="fa-solid fa-chevron-right text-[8px] opacity-40"></i>
            <span class="hover:text-white transition-colors duration-200 cursor-default">Layanan</span>
            <i class="fa-solid fa-chevron-right text-[8px] opacity-40"></i>
            <span class="text-white">Sikunang</span>
        </nav>

        <!-- Dummy Content -->
        <div class="dark-bento-container shadow-2xl p-8 md:p-12 text-center animate-fade-in-up delay-200">
            <div class="w-24 h-24 rounded-full bg-[#d6fb00]/10 text-[#d6fb00] flex items-center justify-center mx-auto mb-6">
                <i class="fa-solid fa-lightbulb text-4xl"></i>
            </div>
            <h1 class="text-3xl md:text-4xl font-black neon-text-lime mb-4">Sikunang</h1>
            <p class="text-[#8aacb0] max-w-2xl mx-auto mb-8 leading-relaxed">
                Halaman ini masih dalam tahap pengembangan. Sikunang akan segera hadir untuk memberikan layanan informasi yang lebih baik dan terintegrasi untuk masyarakat Jawa Tengah.
            </p>
            <a href="<?= base_url() ?>" class="btn-primary inline-flex items-center gap-2 px-8 py-3 rounded-full">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Beranda
            </a>
        </div>
    </div>
</section>
