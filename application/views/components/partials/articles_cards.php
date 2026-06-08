<style>
/* Premium Article Card Styles (Safe from Tailwind JIT limits) */
.pkp-article-card {
    background-color: rgba(15, 42, 48, 0.4);
    border: 1px solid rgba(214, 251, 0, 0.1);
    border-radius: 1rem;
    overflow: hidden;
    position: relative;
    transition: all 0.4s ease;
}
.pkp-article-card:hover {
    border-color: rgba(214, 251, 0, 0.3);
    box-shadow: 0 8px 30px rgba(214, 251, 0, 0.08);
    transform: translateY(-4px);
}
.pkp-article-date {
    background-color: #d6fb00;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    transition: transform 0.5s ease;
}
.pkp-article-card:hover .pkp-article-date {
    transform: scale(1.1) rotate(4deg);
}
.pkp-article-img {
    transition: transform 0.7s ease;
}
.pkp-article-card:hover .pkp-article-img {
    transform: scale(1.08);
}
.pkp-article-title {
    transition: color 0.3s ease;
}
.pkp-article-card:hover .pkp-article-title {
    color: #d6fb00 !important;
}
.pkp-read-more i {
    transition: transform 0.3s ease;
}
.pkp-article-card:hover .pkp-read-more i {
    transform: translateX(4px);
}
.pkp-line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.pkp-image-gradient {
    background: linear-gradient(to top, #0a1a1f 0%, transparent 100%);
    opacity: 0.7;
}
.pkp-content-gradient {
    background: linear-gradient(to bottom, rgba(10, 26, 31, 0.6) 0%, transparent 100%);
}
</style>

<?php if(!empty($articles)): ?>
<?php
    $no = 1;
    foreach($articles as $item): 
    if($no > 6) break;
?>
<div class="pkp-article-card">
    
    <!-- Image Section -->
    <div class="relative w-full overflow-hidden" style="height: 200px;">
        <!-- Date Badge -->
        <div class="absolute top-4 right-4 z-20 pkp-article-date w-12 h-12 rounded-xl flex flex-col items-center justify-center text-center">
            <span class="font-black text-sm leading-none" style="color: #0a1a1f;"><?=date('d',strtotime($item['createdAt']))?></span>
            <span class="text-[9px] font-bold uppercase tracking-wider mt-0.5" style="color: rgba(10, 26, 31, 0.7);"><?=date('M',strtotime($item['createdAt']))?></span>
        </div>
        
        <!-- Image Gradient Overlay -->
        <div class="absolute inset-0 pkp-image-gradient z-10"></div>
        
        <img src="https://apiternak.krsjawa3.com/<?= $item['path_image'] ?>" class="w-full h-full object-cover pkp-article-img" alt="Artikel" loading="lazy">
    </div>

    <!-- Content Section -->
    <div class="p-6 relative z-20 pkp-content-gradient">
        <a href="<?= base_url('Index/detail_artikel/').$item['id'] ?>" class="block">
            <h4 class="text-white font-extrabold text-base leading-snug pkp-article-title mb-3" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                <?= htmlspecialchars($item['title']) ?>
            </h4>
        </a>
        <p class="text-xs leading-relaxed mb-5 pkp-line-clamp-3" style="color: #a1a1aa;"><?=strip_tags($item['body'])?></p>
        
        <div class="pt-4 flex items-center justify-between" style="border-top: 1px solid rgba(255,255,255,0.05);">
            <a href="<?= base_url('Index/detail_artikel/').$item['id'] ?>" class="inline-flex items-center gap-2 text-xs font-bold transition-colors pkp-read-more" style="color: #d6fb00;">
                Baca selengkapnya 
                <i class="fa-solid fa-arrow-right-long text-[10px]"></i>
            </a>
        </div>
    </div>
</div>
<?php $no++; endforeach; ?>
<?php else: ?>
    <p class="col-span-3 text-center text-zinc-600 py-8">Belum ada artikel yang diterbitkan.</p>
<?php endif; ?>
