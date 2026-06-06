<?php if(!empty($house_designs)): ?>
    <?php foreach($house_designs as $key => $item): ?>
<div class="px-3">
    <div class="group bg-[#0f2a30]/80 border border-[#d6fb00]/20 hover:border-[#00a3b5]/20 rounded-2xl p-5 flex flex-col justify-between transition-all duration-300 h-full">
        <div>
            <div class="relative w-full h-52 rounded-xl overflow-hidden mb-5">
                <div class="absolute top-3 right-3 bg-[#0a1a1f]/70 backdrop-blur-sm border border-[#d6fb00]/15 px-2.5 py-1 rounded-lg z-20">
                    <span class="text-white font-bold text-xs"><?=$key + 1?></span>
                </div>
                <img src="https://apiternak.krsjawa3.com/<?= $item['path_image'] ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" alt="<?=$item['title']?>" loading="lazy">
            </div>
            <h4 class="text-white font-bold text-sm mb-2 group-hover:text-[#00a3b5] transition-colors tracking-wide">
                <?=$item['title']?>
            </h4>
            <p class="text-zinc-500 text-[11px] leading-relaxed mb-5 line-clamp-2">
                <?=$item['description']?>
            </p>
        </div>
        <div class="flex items-center gap-2 mt-auto">
            <a href="https://apiternak.krsjawa3.com/<?= $item['path_file'] ?>" target="_blank" class="flex-grow bg-[#00a3b5]/10 hover:bg-[#00a3b5] border border-[#00a3b5]/20 text-[#00a3b5] hover:text-[#0a1a1f] font-bold text-xs py-2.5 px-4 rounded-xl transition-all flex items-center justify-center gap-2">
                <i class="fa-solid fa-download"></i> Download
            </a>
            <?php if (!empty($item['video']['link_video'])): ?>
            <a href="<?= $item['video']['link_video'] ?>" target="_blank" class="w-10 h-10 bg-rose-500/10 hover:bg-rose-500 border border-rose-500/20 text-rose-400 hover:text-white rounded-xl flex items-center justify-center transition-all">
                <i class="fa-brands fa-youtube"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endforeach; ?>
<?php else: ?>
    <div class="px-3 col-span-3 text-center text-zinc-600 py-10">Tidak ada data desain.</div>
<?php endif; ?>
