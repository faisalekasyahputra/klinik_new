<style>
  .dark-bento-container {
    background-color: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid rgba(214, 251, 0, 0.08);
    border-radius: 24px;
  }
  .neon-text-lime { color: #d6fb00; }
  .text-zinc-light { color: rgba(236, 255, 182, 0.9); }
  .border-glass { border-color: rgba(255, 255, 255, 0.08); }
  
  /* Fallback Classes for uncompiled Tailwind */
  .bg-lime-gradient {
    background: linear-gradient(to bottom right, #d6fb00, #ecffb6);
    color: #0a1a1f;
    box-shadow: 0 10px 15px -3px rgba(214, 251, 0, 0.2);
  }
  .line-connector-v {
    border-left: 2px dashed rgba(214, 251, 0, 0.5);
    width: 0;
  }
  .line-connector-h {
    border-top: 2px dashed rgba(214, 251, 0, 0.5);
    height: 0;
  }
  .card-dark {
    background-color: rgba(255, 255, 255, 0.05);
    border-color: rgba(214, 251, 0, 0.3);
  }
  .card-dark:hover {
    border-color: rgba(214, 251, 0, 0.6);
  }
</style>

<section class="w-full pt-24 pb-16 px-4 sm:px-6 lg:px-8 relative min-h-screen font-outfit">
    <!-- Background Ornaments -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute top-[-10%] right-[-5%] w-[600px] h-[600px] rounded-full bg-[#d6fb00]/5 blur-[120px]"></div>
    </div>

    <div class="max-w-7xl mx-auto relative z-10">
    <nav class="flex items-center gap-2.5 text-xs md:text-sm text-[#8aacb0] font-medium mb-8" aria-label="Breadcrumb">
        <a href="<?= base_url() ?>" class="hover:text-[#d6fb00] transition-colors duration-200 flex items-center gap-1.5">
            <i class="fa-solid fa-house text-[10px]"></i> Beranda
        </a>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-40"></i>
        <span class="hover:text-white transition-colors duration-200 cursor-default">Profil</span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-40"></i>
        <span class="text-white">Struktur Organisasi</span>
    </nav>
    
    <div class="dark-bento-container shadow-2xl p-8 md:p-12 text-center">
        <h1 class="text-3xl md:text-4xl font-black neon-text-lime mb-10">Struktur Organisasi</h1>
        
        <div class="inline-block w-full max-w-5xl mx-auto overflow-x-auto pb-8 px-4">
            
            <div class="flex flex-col items-center relative">
                
                <!-- Kepala Dinas -->
                <div class="bg-lime-gradient px-8 py-6 rounded-2xl font-bold z-10 w-64 text-center">
                    <div class="w-20 h-20 rounded-full border-4 border-[#0a1a1f]/20 mx-auto mb-4 overflow-hidden shadow-lg bg-[#0a1a1f]">
                        <img src="https://ui-avatars.com/api/?name=Arief+Djatmiko&background=0a1a1f&color=d6fb00&size=128&bold=true" alt="Ir. Arief Djatmiko" class="w-full h-full object-cover">
                    </div>
                    <p class="text-[10px] uppercase tracking-widest opacity-80 mb-1">Kepala Dinas</p>
                    <h3 class="text-base font-black">Ir. Arief Djatmiko</h3>
                </div>

                <!-- Garis Vertikal -->
                <div class="h-8 line-connector-v z-0"></div>

                <!-- Sekretaris -->
                <div class="card-dark border text-white px-8 py-5 rounded-2xl font-bold z-10 w-64 text-center backdrop-blur-sm">
                    <div class="w-16 h-16 rounded-full border-2 border-[#d6fb00]/30 mx-auto mb-3 overflow-hidden bg-[#d6fb00]">
                        <img src="https://ui-avatars.com/api/?name=Sekretaris+Dinas&background=d6fb00&color=0a1a1f&size=128&bold=true" alt="Sekretaris Dinas" class="w-full h-full object-cover">
                    </div>
                    <p class="text-[10px] text-[#8aacb0] uppercase tracking-widest mb-1">Sekretaris Dinas</p>
                    <h3 class="text-sm font-bold">Nama Pejabat</h3>
                </div>

                <!-- Garis Vertikal Lanjutan -->
                <div class="h-8 line-connector-v z-0 hidden md:block"></div>

                <!-- Bidang-Bidang -->
                <div class="w-full relative z-10">
                    
                    <!-- Kumpulan Garis Penghubung Presisi (Desktop) -->
                    <div class="hidden md:block absolute top-0 left-0 w-full h-8 z-0">
                        <!-- Garis Horizontal Penghubung (Menyambungkan Box 1 dan 3) -->
                        <div class="absolute top-0 line-connector-h" style="left: calc(16.666% - 8px); right: calc(16.666% - 8px);"></div>
                        
                        <!-- Garis Vertikal ke Box 1 -->
                        <div class="absolute top-0 bottom-0 line-connector-v" style="left: calc(16.666% - 8px);"></div>
                        
                        <!-- Garis Vertikal ke Box 2 (Tengah) -->
                        <div class="absolute top-0 bottom-0 left-1/2 line-connector-v" style="transform: translateX(-1px);"></div>
                        
                        <!-- Garis Vertikal ke Box 3 -->
                        <div class="absolute top-0 bottom-0 line-connector-v" style="right: calc(16.666% - 8px);"></div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 relative pt-0 md:pt-8 z-10">
                        
                        <!-- Box 1 -->
                        <div class="card-dark border text-white p-6 rounded-2xl transition-colors backdrop-blur-sm w-full h-full text-center mt-6 md:mt-0">
                            <div class="w-16 h-16 rounded-full border-2 border-[#d6fb00]/30 mx-auto mb-4 overflow-hidden bg-[#d6fb00]">
                                <img src="https://ui-avatars.com/api/?name=Kepala+Bidang&background=d6fb00&color=0a1a1f&size=128&bold=true" alt="Kepala Bidang" class="w-full h-full object-cover">
                            </div>
                            <p class="text-[10px] text-[#8aacb0] uppercase tracking-widest mb-2">Kepala Bidang</p>
                            <h3 class="text-sm font-bold mb-3">Bidang Perumahan</h3>
                            <p class="text-xs text-[#8aacb0]">Nama Pejabat</p>
                        </div>
                        
                        <!-- Box 2 -->
                        <div class="card-dark border text-white p-6 rounded-2xl transition-colors backdrop-blur-sm w-full h-full text-center mt-6 md:mt-0">
                            <div class="w-16 h-16 rounded-full border-2 border-[#d6fb00]/30 mx-auto mb-4 overflow-hidden bg-[#d6fb00]">
                                <img src="https://ui-avatars.com/api/?name=Kepala+Bidang&background=d6fb00&color=0a1a1f&size=128&bold=true" alt="Kepala Bidang" class="w-full h-full object-cover">
                            </div>
                            <p class="text-[10px] text-[#8aacb0] uppercase tracking-widest mb-2">Kepala Bidang</p>
                            <h3 class="text-sm font-bold mb-3">Bidang Kawasan Permukiman</h3>
                            <p class="text-xs text-[#8aacb0]">Nama Pejabat</p>
                        </div>

                        <!-- Box 3 -->
                        <div class="card-dark border text-white p-6 rounded-2xl transition-colors backdrop-blur-sm w-full h-full text-center mt-6 md:mt-0">
                            <div class="w-16 h-16 rounded-full border-2 border-[#d6fb00]/30 mx-auto mb-4 overflow-hidden bg-[#d6fb00]">
                                <img src="https://ui-avatars.com/api/?name=Kepala+UPT&background=d6fb00&color=0a1a1f&size=128&bold=true" alt="Kepala UPT" class="w-full h-full object-cover">
                            </div>
                            <p class="text-[10px] text-[#8aacb0] uppercase tracking-widest mb-2">Kepala UPT</p>
                            <h3 class="text-sm font-bold mb-3">Balai P3KP</h3>
                            <p class="text-xs text-[#8aacb0]">Nama Pejabat</p>
                        </div>

                    </div>
                </div>

            </div>

        </div>
    </div>
    </div>
</section>
