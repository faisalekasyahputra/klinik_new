<style>
  .perum-card-hover {
    z-index: 10;
  }
  .perum-card-hover:hover {
    z-index: 50;
  }
  .hover-scale-img-box {
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    z-index: 10;
  }
  .hover-scale-img-box:hover {
    transform: scale(1.35) translateY(-5px);
    z-index: 50;
    box-shadow: 0 20px 50px rgba(0,0,0,0.9);
    border-color: #d6fb00 !important;
  }
  .hover-scale-overlay {
    opacity: 0;
    transition: opacity 0.4s ease;
    background-color: #0a1a1f;
  }
  .hover-scale-img-box:hover .hover-scale-overlay {
    opacity: 1;
  }
  .hover-scale-badge {
    transition: opacity 0.3s ease;
  }
  .hover-scale-img-box:hover .hover-scale-badge {
    opacity: 0;
  }
</style>

<?php if (!empty($results)): ?>
    <?php foreach ($results as $row): ?>
    <div class="group bg-[#11272d] border border-[#d6fb00]/30 hover:border-[#d6fb00] p-4 flex flex-col justify-between transition-all duration-300 h-full shadow-lg relative perum-card-hover" style="border-radius: 20px;">
        
        <!-- Static Placeholder to preserve layout -->
        <div class="relative w-full h-44 mb-4">
            <!-- Interactive Image Container -->
            <div class="absolute inset-0 w-full h-44 hover-scale-img-box bg-[#0a1a1f] border overflow-hidden" style="border-radius: 16px; border-color: rgba(26, 61, 69, 0.5);">
                <?php if (isset($row['tipeRumah'][0]['status'])): ?>
                <span class="hover-scale-badge absolute top-2.5 left-2.5 z-20 bg-[#d6fb00] text-[#0a1a1f] text-[9px] font-bold px-2 py-1 rounded-md shadow-sm tracking-widest uppercase">
                    <?= $row['tipeRumah'][0]['status'] ?>
                </span>
                <?php endif; ?>
                
                <?php 
                    $url_asli = $row['foto'][0] ?? ''; 
                    $path_bersih = '';
                    if ($url_asli) {
                        $posisi_public = strpos($url_asli, "public");
                        if ($posisi_public !== false) {
                            $path_potong = substr($url_asli, $posisi_public);
                            $path_bersih = stripslashes($path_potong);
                        }
                    }
                ?>
                <!-- Cropped Default View -->
                <img src="<?= base_url('Index/buka_foto?path=' . urlencode($path_bersih)) ?>" class="w-full h-full object-cover transition-transform duration-500" alt="<?= htmlspecialchars($row['namaPerumahan']) ?>" loading="lazy">
                <div class="absolute inset-0 bg-gradient-to-t from-[#0a1a1f]/70 via-transparent to-transparent opacity-60 pointer-events-none"></div>

                <!-- Uncropped Full View (Appears on Hover) -->
                <div class="hover-scale-overlay absolute inset-0 z-30 p-1 flex items-center justify-center pointer-events-none">
                    <img src="<?= base_url('Index/buka_foto?path=' . urlencode($path_bersih)) ?>" class="w-full h-full object-contain" alt="<?= htmlspecialchars($row['namaPerumahan']) ?>">
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="flex-grow flex flex-col">
            <h4 class="text-white font-bold text-lg mb-1.5 group-hover:text-[#d6fb00] transition-colors line-clamp-1">
                <?= $row['namaPerumahan'] ?>
            </h4>
            
            <p class="text-zinc-400 text-[10px] uppercase tracking-wide mb-1 flex items-center gap-1.5">
                <i class="fa-solid fa-location-dot text-[#d6fb00] w-3 text-center"></i>
                <span class="truncate"><?= $row['wilayah']['kabupaten'] ?>, <?= $row['wilayah']['provinsi'] ?></span>
            </p>
            
            <p class="text-zinc-500 text-[10px] uppercase tracking-wide mb-3 flex items-center gap-1.5">
                <i class="fa-solid fa-building text-[#00a3b5] w-3 text-center"></i>
                <span class="truncate"><?= $row['pengembang']['nama'] ?></span>
            </p>

            <?php if (isset($row['tipeRumah'][0]['luasBangunan'])): ?>
            <!-- Specifications -->
            <div class="flex items-center justify-between gap-2 mb-4 bg-[#0a1a1f]/50 border rounded-xl px-3 py-2" style="border-color: #1a3d45;">
                <div class="flex items-center gap-1.5 text-zinc-300 text-[10px] font-semibold">
                    <i class="fa-solid fa-ruler-combined text-[#d6fb00]"></i> 
                    <?= $row['tipeRumah'][0]['luasBangunan'] ?>/<?= $row['tipeRumah'][0]['luasTanah'] ?>
                </div>
                <div class="flex items-center gap-1.5 text-zinc-300 text-[10px] font-semibold">
                    <i class="fa-solid fa-bed text-[#d6fb00]"></i> 
                    <?= $row['tipeRumah'][0]['kamarTidur'] ?>
                </div>
                <div class="flex items-center gap-1.5 text-zinc-300 text-[10px] font-semibold">
                    <i class="fa-solid fa-bath text-[#d6fb00]"></i> 
                    <?= $row['tipeRumah'][0]['kamarMandi'] ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Price and Action -->
        <div class="flex justify-between items-center mt-auto pt-1">
            <div>
                <span class="text-zinc-500 text-[8px] uppercase tracking-widest block mb-0.5">Mulai dari</span>
                <?php if (isset($row['tipeRumah'][0]['harga'])): ?>
                <span class="text-[#d6fb00] text-base font-extrabold block tracking-tight">
                    Rp <?= number_format($row['tipeRumah'][0]['harga'], 0, ',', '.') ?>
                </span>
                <?php endif; ?>
            </div>
            <a href="<?= base_url('detail_perum/' . $row['idLokasi']) ?>" class="bg-[#1a3d45] hover:bg-[#d6fb00] text-[#00a3b5] hover:text-[#0a1a1f] w-9 h-9 rounded-lg flex items-center justify-center transition-all flex-shrink-0 group/btn z-10 relative">
                <i class="fa-solid fa-arrow-right text-xs group-hover/btn:-rotate-45 transition-transform duration-300"></i>
            </a>
        </div>
    </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="col-span-1 md:col-span-3">
        <div class="bg-[#11272d] border border-[#1a3d45] p-8 text-center" style="border-radius: 20px;">
            <i class="fa-solid fa-house-circle-exclamation text-4xl text-[#00a3b5] mb-4 opacity-50"></i>
            <h4 class="text-white text-lg font-bold mb-2">Data Tidak Ditemukan</h4>
            <p class="text-zinc-400 text-sm">Silakan masukkan kata kunci atau pilih wilayah lain untuk mencari data perumahan.</p>
        </div>
    </div>
<?php endif; ?>
