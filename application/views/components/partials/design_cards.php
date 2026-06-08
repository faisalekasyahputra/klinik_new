<?php if(!empty($house_designs)): ?>
<style>
    .hover-gradient-mask {
        background: linear-gradient(to top, rgba(10, 26, 31, 0.95) 0%, rgba(10, 26, 31, 0.3) 35%, transparent 100%);
        transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .group:hover .hover-gradient-mask {
        background: linear-gradient(to top, rgba(10, 26, 31, 1) 0%, rgba(10, 26, 31, 0.95) 45%, rgba(10, 26, 31, 0.5) 80%, transparent 100%);
    }
    .card-content-wrapper {
        transform: translateY(144px);
        transition: transform 0.7s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .group:hover .card-content-wrapper {
        transform: translateY(0);
    }
    .card-details {
        height: 144px;
        opacity: 0;
        transition: opacity 0.5s ease 0.1s;
    }
    .group:hover .card-details {
        opacity: 1;
    }
</style>
<?php foreach($house_designs as $index => $item): ?>
<div class="px-3 pb-8">
    <!-- Full-bleed Card Container -->
    <div class="group relative w-full bg-[#0a1a1f] border border-[#d6fb00]/20 hover:border-[#d6fb00]/80 transition-all duration-500 shadow-lg hover:shadow-[0_10px_30px_rgba(214,251,0,0.15)] overflow-hidden flex flex-col" style="border-radius: 24px;">
        
        <!-- Dynamic Height Image (No cropping, full width) -->
        <img src="https://apiternak.krsjawa3.com/<?= $item['path_image'] ?>" class="relative w-full h-auto block transition-transform duration-1000 group-hover:scale-110" alt="<?= htmlspecialchars($item['title']) ?>" loading="lazy">
        
        <!-- Gradient Overlay Mask -->
        <div class="absolute inset-0 hover-gradient-mask z-10 pointer-events-none"></div>

        <!-- Eye Icon Overlay -->
        <div class="absolute top-4 right-4 bg-[#d6fb00] text-[#0a1a1f] text-[10px] font-black px-2.5 py-1.5 rounded-lg z-20 shadow-lg flex items-center gap-1.5 opacity-0 group-hover:opacity-100 transition-opacity duration-500 delay-100 pointer-events-none">
            <i class="fa-solid fa-eye"></i> LIHAT
        </div>

        <!-- Content Container (Sliding Wrapper) -->
        <div class="absolute inset-x-0 bottom-0 z-20 flex flex-col justify-end pointer-events-none">
            <div class="card-content-wrapper flex flex-col px-6 pointer-events-auto w-full">
                
                <!-- Always visible Title -->
                <h4 class="text-white font-bold text-lg sm:text-xl tracking-wide group-hover:text-[#d6fb00] transition-colors line-clamp-2 drop-shadow-xl w-full" style="padding-bottom: 20px;">
                    <?php
                        // Highlight "Tipe" followed by numbers in Electric Lime
                        echo preg_replace('/(Tipe\s+\d+)/i', '<span style="color: #d6fb00;">$1</span>', htmlspecialchars($item['title']));
                    ?>
                </h4>

                <!-- Reveal on hover (Facilities & Buttons) -->
                <div class="card-details flex flex-col justify-start w-full" style="padding-bottom: 24px;">
                    
                    <p class="text-zinc-400 text-[10px] uppercase tracking-widest font-semibold mb-3 border-t border-white/10 pt-3 w-full">
                        FASILITAS
                    </p>

                    <!-- Facilities Icons -->
                    <div class="flex items-start justify-between gap-2 mb-4 w-full">
                        <div class="flex flex-col items-center text-center gap-1.5 flex-1">
                            <i class="fa-solid fa-bed text-base" style="color: #d6fb00; filter: drop-shadow(0 0 5px rgba(214,251,0,0.3));"></i>
                            <span class="text-zinc-300 text-[10px] leading-tight font-medium">2 Kamar</span>
                        </div>
                        <div class="flex flex-col items-center text-center gap-1.5 flex-1">
                            <i class="fa-solid fa-bath text-base" style="color: #d6fb00; filter: drop-shadow(0 0 5px rgba(214,251,0,0.3));"></i>
                            <span class="text-zinc-300 text-[10px] leading-tight font-medium">1 K. Mandi</span>
                        </div>
                        <div class="flex flex-col items-center text-center gap-1.5 flex-1">
                            <i class="fa-solid fa-tree text-base" style="color: #d6fb00; filter: drop-shadow(0 0 5px rgba(214,251,0,0.3));"></i>
                            <span class="text-zinc-300 text-[10px] leading-tight font-medium">Taman</span>
                        </div>
                    </div>

                    <!-- Buttons at bottom -->
                    <div class="flex items-center gap-3 w-full mt-auto">
                        <a href="https://apiternak.krsjawa3.com/<?= $item['path_file'] ?>" target="_blank" class="flex-grow bg-[#d6fb00] hover:bg-[#ecffb6] text-[#0a1a1f] font-bold text-sm py-2.5 px-4 rounded-xl transition-all flex items-center justify-center gap-2 shadow-[0_4px_15px_rgba(214,251,0,0.3)]">
                            <i class="fa-solid fa-download"></i> Unduh Desain
                        </a>
                        <?php if (!empty($item['video']['link_video'])): ?>
                        <a href="<?= $item['video']['link_video'] ?>" target="_blank" class="w-[42px] h-[42px] bg-[#ff0000] hover:bg-[#cc0000] text-white rounded-xl flex items-center justify-center transition-all flex-shrink-0 shadow-[0_4px_15px_rgba(255,0,0,0.4)]">
                            <i class="fa-brands fa-youtube text-xl"></i>
                        </a>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php else: ?>
    <div class="px-3 col-span-3 text-center text-zinc-600 py-10">Tidak ada data desain.</div>
<?php endif; ?>
