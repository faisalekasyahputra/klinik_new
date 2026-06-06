<?php if(!empty($articles)): ?>
<?php
    $no = 1;
    foreach($articles as $item): 
    if($no > 6) break;
?>
<div class="group bg-[#0f2a30]/60 border border-[#d6fb00]/20 hover:border-[#d6fb00]/15 rounded-2xl overflow-hidden transition-all duration-300">
    <div class="relative w-full h-48 overflow-hidden">
        <div class="absolute top-3 right-3 z-20 bg-[#0a1a1f]/70 backdrop-blur-sm border border-[#d6fb00]/15 w-11 h-11 rounded-xl flex flex-col items-center justify-center text-center">
            <span class="text-white font-bold text-xs leading-none"><?=date('d',strtotime($item['createdAt']))?></span>
            <span class="text-[#d6fb00] text-[8px] font-bold uppercase mt-0.5"><?=date('M',strtotime($item['createdAt']))?></span>
        </div>
        <img src="https://apiternak.krsjawa3.com/<?= $item['path_image'] ?>" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" alt="Artikel" loading="lazy">
    </div>
    <div class="p-5">
        <a href="<?= base_url('Index/detail_artikel/').$item['id'] ?>" class="block group/title">
            <h4 class="text-white font-bold text-sm leading-snug group-hover/title:text-[#00a3b5] transition-colors line-clamp-2 mb-2">
                <?= htmlspecialchars($item['title']) ?>
            </h4>
        </a>
        <p class="text-zinc-500 text-[11px] line-clamp-2 leading-relaxed mb-4"><?=strip_tags($item['body'])?></p>
        <a href="<?= base_url('Index/detail_artikel/').$item['id'] ?>" class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-[#00a3b5] hover:text-[#26c6da] transition-colors">
            Baca selengkapnya <i class="fa-solid fa-arrow-right text-[9px]"></i>
        </a>
    </div>
</div>
<?php $no++; endforeach; ?>
<?php else: ?>
    <p class="col-span-3 text-center text-zinc-600 py-8">Belum ada artikel yang diterbitkan.</p>
<?php endif; ?>
