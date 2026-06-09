<style>
  /* Custom overrides for missing JIT Tailwind classes */
  .dark-bento-container {
    background-color: rgba(15, 42, 48, 0.4);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid rgba(214, 251, 0, 0.08);
    border-radius: 24px;
  }
  .dark-bento-sidebar {
    background-color: rgba(15, 42, 48, 0.7);
    backdrop-filter: blur(32px);
    -webkit-backdrop-filter: blur(32px);
    border: 1px solid rgba(214, 251, 0, 0.1);
    border-radius: 24px;
  }
  .neon-text-lime { color: #d6fb00; }
  .neon-text-cyan { color: #00a3b5; }
  .text-zinc-light { color: rgba(236, 255, 182, 0.9); }
  .text-zinc-dim { color: #8aacb0; }
  .border-glass { border-color: rgba(255, 255, 255, 0.08); }
  
  .badge-neon-cyan {
    background-color: rgba(0, 163, 181, 0.1);
    color: #00a3b5;
    border: 1px solid rgba(0, 163, 181, 0.2);
  }
  
  .hover-glow-card:hover {
    background-color: rgba(214, 251, 0, 0.05);
    border-color: rgba(214, 251, 0, 0.2);
  }
  .hover-glow-text:hover { color: #d6fb00; }
  .group:hover .hover-glow-img { transform: scale(1.05); }

  /* Professional Article Typography */
  .article-body {
    color: #e2e8f0; /* slate-200 */
    font-size: 1.1rem;
    line-height: 1.8;
    letter-spacing: 0.01em;
    text-align: justify !important;
    text-justify: inter-word;
    word-wrap: break-word;
  }
  @media (max-width: 768px) {
    .article-body {
      font-size: 1rem;
      line-height: 1.7;
    }
  }
  .article-body p, .article-body div {
    margin-bottom: 1.75em;
    text-align: justify !important;
  }
  .article-body p:last-child {
    margin-bottom: 0;
  }
  /* Drop Cap for first paragraph */
  .article-body p:first-of-type::first-letter {
    float: left;
    font-size: 4rem;
    line-height: 0.8;
    font-weight: 900;
    margin-right: 0.12em;
    margin-top: 0.05em;
    color: #d6fb00;
    text-shadow: 0 0 15px rgba(214, 251, 0, 0.2);
  }
  .article-body b, .article-body strong {
    color: #ffffff;
    font-weight: 800;
  }
  .article-body i, .article-body em {
    color: #cbd5e1;
  }
  .article-body a {
    color: #d6fb00;
    text-decoration: underline;
    text-decoration-color: rgba(214, 251, 0, 0.4);
    text-underline-offset: 4px;
    transition: all 0.3s ease;
  }
  .article-body a:hover {
    color: #ffffff;
    text-decoration-color: #ffffff;
  }
  .article-body blockquote {
    border-left: 4px solid #d6fb00;
    padding-left: 1.5rem;
    margin-left: 0;
    margin-right: 0;
    font-style: italic;
    color: #cbd5e1;
    background: rgba(214, 251, 0, 0.03);
    padding-top: 1rem;
    padding-bottom: 1rem;
    border-radius: 0 12px 12px 0;
  }
  .article-body ul, .article-body ol {
    margin-bottom: 1.75em;
    padding-left: 1.5rem;
  }
  .article-body li {
    margin-bottom: 0.5em;
  }
  .article-body ul li {
    list-style-type: disc;
    marker-color: #00a3b5;
  }
</style>

<section class="w-full max-w-[1600px] mx-auto px-4 lg:px-8 py-8">
    
    <!-- Breadcrumb Navigation -->
    <nav class="flex items-center gap-2.5 text-xs md:text-sm text-zinc-dim font-medium mb-8" aria-label="Breadcrumb">
        <a href="<?= base_url() ?>" class="hover:text-[#d6fb00] transition-colors duration-200 flex items-center gap-1.5">
            <i class="fa-solid fa-house text-[10px]"></i> Beranda
        </a>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-40"></i>
        <a href="<?= base_url('Berita') ?>" class="hover:text-white transition-colors duration-200">
            Berita & Artikel
        </a>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-40"></i>
        <span class="text-white truncate max-w-[200px] sm:max-w-[400px]" title="<?= htmlspecialchars($item['title']) ?>">
            <?= htmlspecialchars($item['title']) ?>
        </span>
    </nav>
    
    <?php 
        // Ekstrak kategori unik dari seluruh artikel
        $kategori_list = [];
        if (!empty($articles)) {
            foreach ($articles as $a) {
                $kat = isset($a['category']['name']) ? $a['category']['name'] : 'Tanpa Kategori';
                if (!isset($kategori_list[$kat])) {
                    $kategori_list[$kat] = 0;
                }
                $kategori_list[$kat]++;
            }
        }
    ?>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 xl:gap-8 items-start">
        
        <!-- Left: Kategori Sidebar (Hidden on mobile) -->
        <div class="hidden lg:block lg:col-span-2 lg:sticky" style="top: 104px;">
            <div class="dark-bento-sidebar shadow-2xl p-5 space-y-5">
                <h3 class="text-xs font-bold text-white uppercase tracking-widest flex items-center gap-2 border-b border-glass pb-3">
                    <i class="fa-solid fa-layer-group neon-text-cyan"></i> Kategori
                </h3>
                <div class="space-y-1">
                    <?php foreach($kategori_list as $kat => $count): ?>
                    <a href="<?= base_url('Berita?kat=' . urlencode($kat)) ?>" class="flex items-center justify-between group hover-glow-card p-2.5 rounded-xl transition-all duration-300 border border-transparent hover:border-[#d6fb00]/30">
                        <span class="text-[13px] text-zinc-dim group-hover:text-white transition-colors duration-300 truncate mr-2" title="<?= $kat ?>"><?= $kat ?></span>
                        <span class="badge-neon-cyan text-[9px] font-extrabold px-2 py-0.5 rounded-full flex-shrink-0"><?= $count ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Center: Main Article Content -->
        <div class="col-span-1 lg:col-span-7 space-y-6">
            
            <div class="dark-bento-container shadow-2xl overflow-hidden">
                
                <div class="w-full aspect-[16/9] md:aspect-[21/10] bg-[#0a1a1f] overflow-hidden relative group">
                    <img src="https://apiternak.krsjawa3.com/<?= $item['path_image'] ?>" 
                         class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" 
                         alt="Banner Artikel">
                    <!-- Gradient overlay on image -->
                    <div class="absolute inset-0 bg-gradient-to-t from-[#0f2a30]/90 to-transparent pointer-events-none"></div>
                </div>
                
                <div class="p-6 md:p-10 space-y-6">
                    
                    <h1 class="text-xl md:text-2xl lg:text-3xl font-black neon-text-lime leading-snug tracking-tight drop-shadow-lg">
                        <?= htmlspecialchars($item['title']) ?>
                    </h1>
                    
                    <div class="flex items-center gap-3 text-xs md:text-sm text-zinc-dim border-b border-glass pb-4 flex-wrap">
                        <div class="flex items-center gap-1.5">
                            <i class="fa-regular fa-calendar-days neon-text-cyan"></i>
                            <span class="font-medium"> <?= format_tanggal_api($item['createdAt']) ?></span>
                        </div>
                        <span class="opacity-50 hidden sm:inline">•</span>
                        <div class="flex items-center gap-1.5 text-[#d6fb00] font-bold bg-[#d6fb00]/10 px-2.5 py-1 rounded-md border border-[#d6fb00]/20">
                            <i class="fa-solid fa-shield-halved"></i>
                            <span class="text-[11px] uppercase tracking-wider">Sumber Resmi</span>
                        </div>
                        <span class="opacity-50 hidden sm:inline">•</span>
                        <span class="badge-neon-cyan text-[10px] font-extrabold px-3 py-1 rounded-full uppercase tracking-widest"> 
                            <?= isset($item['category']['name']) ? $item['category']['name'] : 'Tanpa Kategori' ?>
                        </span>
                    </div>
                    
                    <div class="article-body font-outfit">
                        <?= $item['body']; ?>
                    </div>
                    
                    <!-- Validitas Sumber Banner -->
                    <div class="mt-10 p-5 rounded-2xl border border-[#00a3b5]/20 bg-[#00a3b5]/5 flex flex-col sm:flex-row items-start sm:items-center gap-4 transition-all duration-300 hover:bg-[#00a3b5]/10 hover:border-[#00a3b5]/40 group">
                        <div class="w-12 h-12 rounded-full bg-[#00a3b5]/20 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform duration-300">
                            <i class="fa-solid fa-building-circle-check text-[#00a3b5] text-xl"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="text-sm font-bold text-white mb-1.5 flex items-center gap-2">
                                <i class="fa-solid fa-circle-check text-[#d6fb00] text-xs"></i> Publikasi Resmi Terverifikasi
                            </h4>
                            <p class="text-xs text-[#8aacb0] leading-relaxed">
                                Artikel ini merupakan rilis berita resmi dari <strong class="text-white">Dinas Perumahan Rakyat dan Kawasan Permukiman (Disperakim) Provinsi Jawa Tengah</strong>. Kami senantiasa menyajikan informasi yang akurat dan dapat dipercaya untuk masyarakat.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Right: Sticky Sidebar (Hidden on mobile) -->
        <div class="hidden lg:block lg:col-span-3 lg:sticky" style="top: 104px;">
            
            <div class="dark-bento-sidebar shadow-2xl p-6 space-y-6">
                
                <h3 class="text-sm font-bold text-white uppercase tracking-widest flex items-center gap-2 border-b border-glass pb-4">
                    <i class="fa-solid fa-book-open neon-text-cyan"></i> Artikel Lainnya
                </h3>
                
                <div class="space-y-4">
                    <?php if(!empty($articles)): ?>
                    <?php
                        $no = 1;
                        foreach($articles as $item): 
                        if($no > 4) break;
                        ?>
                    <div class="flex items-start gap-4 group cursor-pointer hover-glow-card p-2 -mx-2 rounded-xl transition-all duration-300 border border-transparent">
                        <div class="w-20 h-16 md:w-24 md:h-20 bg-[#0a1a1f] rounded-xl overflow-hidden flex-shrink-0 relative">
                            <img src="https://apiternak.krsjawa3.com/<?= $item['path_image'] ?>" class="w-full h-full object-cover transition-transform duration-500 hover-glow-img" alt="Thumbnail">
                            <div class="absolute inset-0 ring-1 ring-inset ring-white/10 rounded-xl pointer-events-none"></div>
                        </div>
                        <div class="space-y-1.5 flex-1 min-w-0 py-0.5">
                            <span class="inline-block badge-neon-cyan text-[8px] font-extrabold px-2 py-0.5 rounded uppercase tracking-wider">
                                 <?= isset($item['category']['name']) ? $item['category']['name'] : 'Tanpa Kategori' ?>
                            </span>
                            <h4 class="text-white font-bold text-xs md:text-sm leading-snug hover-glow-text transition-colors duration-300 line-clamp-2">
                                <a href="<?= base_url('Index/detail_artikel/').$item['id'] ?>" class="no-underline"> <?= htmlspecialchars($item['title']) ?></a>
                            </h4>
                            <div class="flex items-center gap-1.5 text-[10px] text-zinc-dim pt-0.5">
                                <i class="fa-regular fa-clock text-[9px] opacity-70"></i>
                                <span> <?= format_tanggal_api($item['createdAt']) ?></span>
                            </div>
                        </div>
                    </div>
                      <?php $no++; endforeach; ?>
                <?php else: ?>
                    <p class="text-zinc-dim text-sm italic">Belum ada artikel yang diterbitkan.</p>
                <?php endif; ?>
            
                </div>
                
            </div>
                
        </div>
            
    </div>
        
</section>
