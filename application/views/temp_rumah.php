<?php if (!empty($results)): ?>
            <?php foreach ($results as $row): ?>
            <div class="group bg-[#0f2a30]/40 border border-[#d6fb00]/20 rounded-2xl overflow-hidden transition-all hover:border-[#d6fb00]/20">
                <div class="relative h-44 w-full bg-zinc-900">
                     <?php if (isset($row['tipeRumah'][0]['status'])) {?>
                    <span class="absolute top-3 left-3 z-10 bg-[#d6fb00] text-[#0a1a1f] text-[9px] font-extrabold px-2 py-0.5 rounded shadow-sm tracking-wider"><?= $row['tipeRumah'][0]['status'] ?></span>
                    <?php } 
                            $url_asli = $row['foto'][0]; 
                        
                        // 2. Cari posisi kata "public" dan potong stringnya
                        $posisi_public = strpos($url_asli, "public");
                        $path_potong = substr($url_asli, $posisi_public);
                        
                        // 3. Bersihkan tanda backslash (\) agar menjadi public/upload/...
                        $path_bersih = stripslashes($path_potong);

                    ?>
                    <img src="<?= base_url('Index/buka_foto?path=' . urlencode($path_bersih)) ?>" class="w-full h-full object-cover" alt="<?= htmlspecialchars($row['namaPerumahan']) ?>">
                </div>
                <div class="p-5">
                    <h4 class="text-white font-bold text-base mb-1 truncate"><?= $row['namaPerumahan'] ?></h4>
                    <p class="text-zinc-500 text-xs mb-1 flex items-center gap-1"><i class="fa-solid fa-location-dot text-zinc-700 text-[10px]"></i><?=$row['wilayah']['kabupaten']?>, <?=$row['wilayah']['provinsi']?></p>
                    <p class="text-zinc-500 text-[11px] mb-4 flex items-center gap-1.5"><span class="w-2 h-2 bg-zinc-700 rounded-sm"></span><?=$row['pengembang']['nama']?></p>
                    <div class="flex justify-between items-end pt-2 border-t border-[#d6fb00]/20">
                        <div>
                            <?php if (isset($row['tipeRumah'][0]['harga'])) {?>
                            
                            <span class="text-[#d6fb00] text-base font-extrabold block">Rp <?=number_format($row['tipeRumah'][0]['harga'], 0, ',', '.')?></span>
                            <?php } ?>
                            <?php if (isset($row['tipeRumah'][0]['luasBangunan'])) {?>
                            <span class="text-zinc-500 text-[10px]"><?= $row['tipeRumah'][0]['luasBangunan'] ?>/<?= $row['tipeRumah'][0]['luasTanah'] ?> m² • <?= $row['tipeRumah'][0]['kamarTidur'] ?> KT • <?= $row['tipeRumah'][0]['kamarMandi'] ?> KM</span>
                            <?php } ?>
                        </div>
                        <a href="<?php echo base_url('Index/detail_perum/' . $row['idLokasi']); ?>" class="text-zinc-400 hover:text-white text-xs font-medium flex items-center gap-1 transition-colors">Detail <i class="fa-solid fa-arrow-right text-[10px]"></i></a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    Silakan masukkan kata kunci untuk mencari data perumahan.
                </div>
            </div>
        <?php endif; ?>