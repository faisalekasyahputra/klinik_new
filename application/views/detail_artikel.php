<section class="w-full max-w-7xl mx-auto px-4 py-8 bg-white">
    
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <div class="lg:col-span-8 space-y-6">
            
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                
                <div class="w-full aspect-[16/9] md:aspect-[21/10] bg-slate-100 overflow-hidden relative">
                    <img src="https://apiternak.krsjawa3.com/<?= $item['path_image'] ?>" 
                         class="w-full h-full object-cover" 
                         alt="Banner Artikel">
                </div>
                
                <div class="p-6 md:p-8 space-y-6">
                    
                    <h1 class="text-xl md:text-2xl lg:text-3xl font-black text-slate-900 leading-snug tracking-tight">
                        <?= htmlspecialchars($item['title']) ?>
                    </h1>
                    
                    <div class="flex items-center gap-2 text-xs md:text-sm text-slate-500 border-b border-slate-200 pb-4">
                        <i class="fa-regular fa-calendar-days text-blue-600"></i>
                        <span class="text-slate-600 font-medium"> <?= format_tanggal_api($item['createdAt']) ?></span>
                        <span class="mx-1 text-slate-400">•</span>
                        <span class="text-blue-600 font-bold uppercase tracking-widest text-[11px]"> 
                            <?= isset($item['category']['name']) ? $item['category']['name'] : 'Tanpa Kategori' ?>
                        </span>
                    </div>
                    
                    <div class="text-slate-800 text-sm md:text-base leading-relaxed space-y-5 font-normal tracking-wide">
                        <?php 
                            echo str_ireplace(['<b>', '</b>', '<strong>', '</strong>'], '', $item['body']);
                        ?>
                    </div>
                    
                </div>
            </div>
        </div>
        
        <div class="lg:col-span-4 lg:sticky lg:top-6">
            
            <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-6 space-y-6">
                
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-widest flex items-center gap-2 border-b border-slate-200 pb-4">
                    <i class="fa-solid fa-book-open text-blue-600"></i> Artikel Lainnya
                </h3>
                
                <div class="space-y-5">
                    <?php if(!empty($articles)): ?>
                    <?php
                        $no = 1;
                        foreach($articles as $item): 
                        if($no > 4) break;
                        ?>
                    <div class="flex items-start gap-4 group">
                        <div class="w-20 h-16 md:w-24 md:h-20 bg-slate-100 border border-slate-100 rounded-xl overflow-hidden flex-shrink-0">
                            <img src="https://apiternak.krsjawa3.com/<?= $item['path_image'] ?>" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" alt="Thumbnail">
                        </div>
                        <div class="space-y-1 flex-1 min-w-0">
                            <span class="inline-block bg-blue-50 text-blue-600 text-[9px] font-extrabold px-2 py-0.5 rounded uppercase tracking-wide border border-blue-100">
                                 <?= isset($item['category']['name']) ? $item['category']['name'] : 'Tanpa Kategori' ?>
                            </span>
                            <h4 class="text-slate-900 font-bold text-xs md:text-sm leading-snug group-hover:text-blue-600 transition-colors line-clamp-2">
                                <a href="<?= base_url('Index/detail_artikel/').$item['id'] ?>" class="no-underline"> <?= htmlspecialchars($item['title']) ?></a>
                            </h4>
                            <div class="flex items-center gap-1 text-[10px] text-slate-400 pt-0.5">
                                <i class="fa-regular fa-clock text-[9px]"></i>
                                <span> <?= format_tanggal_api($item['createdAt']) ?></span>
                            </div>
                        </div>
                    </div>
                      <?php $no++; endforeach; ?>
                <?php else: ?>
                    <p>Belum ada artikel yang diterbitkan.</p>
                <?php endif; ?>
            
                </div>
                
            </div>
                
            </div>
            
        </div>
        
    </div>
</section>