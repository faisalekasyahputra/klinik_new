<!-- application/views/pages/berita/index.php -->
<style>
/* Premium Article Card Styles */
.pkp-article-card {
    background-color: rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
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
    background-color: rgba(255, 255, 255, 0.08);
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

/* Filter Input Styles */
.pkp-search-input {
    background-color: rgba(214, 251, 0, 0.03);
    border: 1px solid rgba(214, 251, 0, 0.15);
    color: #ecffb6;
    transition: all 0.3s ease;
}
.pkp-search-input:focus {
    border-color: rgba(214, 251, 0, 0.4);
    box-shadow: 0 0 0 3px rgba(214,251,0,0.1);
    outline: none;
}
.pkp-search-input option {
    background-color: #0a1a1f;
    color: #ecffb6;
}
</style>

<section class="w-full pt-16 pb-16 px-4 lg:px-8 relative min-h-screen font-outfit" x-data="articleFilter()">
    <!-- Background Ornaments -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute top-[-10%] right-[-5%] w-[600px] h-[600px] rounded-full bg-[#d6fb00]/5 blur-[120px]"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[500px] h-[500px] rounded-full bg-[#00a3b5]/5 blur-[100px]"></div>
    </div>

    <div class="max-w-[1600px] mx-auto relative z-10">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2.5 text-xs md:text-sm text-[#8aacb0] font-medium mb-8 animate-fade-in-up delay-100" aria-label="Breadcrumb">
            <a href="<?= base_url() ?>" class="hover:text-[#d6fb00] transition-colors duration-200 flex items-center gap-1.5">
                <i class="fa-solid fa-house text-[10px]"></i> Beranda
            </a>
            <i class="fa-solid fa-chevron-right text-[8px] opacity-40"></i>
            <span class="text-white">Berita & Artikel</span>
        </nav>

        <!-- Header -->
        <div class="mb-8 animate-fade-in-up delay-200 text-center sm:text-left flex flex-col sm:flex-row sm:items-end justify-between gap-4">
            <div>
                <h1 class="text-3xl md:text-5xl font-black text-white tracking-tight mb-2">
                    Berita & <span class="text-[#d6fb00] drop-shadow-md">Artikel</span>
                </h1>
                <p class="text-[#8aacb0] text-sm md:text-base">Informasi dan kabar terbaru seputar perumahan dan kawasan permukiman</p>
            </div>
        </div>

        <!-- Layout Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 xl:gap-8 items-start">
            
            <!-- Left: Kategori Sidebar (Hidden on mobile) -->
            <div class="hidden lg:block lg:col-span-2 lg:sticky" style="top: 104px;">
                <div class="rounded-3xl p-5 space-y-5 shadow-2xl" style="background-color: rgba(15, 42, 48, 0.7); backdrop-filter: blur(32px); border: 1px solid rgba(214, 251, 0, 0.1);">
                    <h3 class="text-xs font-bold text-white uppercase tracking-widest flex items-center gap-2 border-b border-white/10 pb-3">
                        <i class="fa-solid fa-layer-group text-[#00a3b5]"></i> Kategori
                    </h3>
                    <div class="space-y-1">
                        <!-- All Categories button -->
                        <a href="#" @click.prevent="selectedCategory = ''" 
                           class="flex items-center justify-between group p-2.5 rounded-xl transition-all duration-300 border"
                           :class="selectedCategory === '' ? 'border-[#00a3b5] bg-[#00a3b5]/10' : 'border-transparent hover:bg-white/5'">
                            <span class="text-[13px] transition-colors duration-300 font-bold" 
                                  :class="selectedCategory === '' ? 'text-white' : 'text-[#8aacb0] group-hover:text-white'">Semua Kategori</span>
                            <span class="text-[#00a3b5] text-[9px] font-extrabold px-2 py-0.5 rounded-full" 
                                  :class="selectedCategory === '' ? 'bg-[#00a3b5]/20' : 'bg-[#00a3b5]/10'" x-text="articles.length"></span>
                        </a>
                        
                        <!-- Dynamic Categories -->
                        <template x-for="(count, kat) in categories" :key="kat">
                            <a href="#" @click.prevent="selectedCategory = kat" 
                               class="flex items-center justify-between group p-2.5 rounded-xl transition-all duration-300 border"
                               :class="selectedCategory === kat ? 'border-[#d6fb00] bg-[#d6fb00]/10' : 'border-transparent hover:bg-white/5'">
                                <span class="text-[13px] transition-colors duration-300 truncate mr-2 font-bold" 
                                      :class="selectedCategory === kat ? 'text-white' : 'text-[#8aacb0] group-hover:text-white'" 
                                      x-text="kat" :title="kat"></span>
                                <span class="text-[9px] font-extrabold px-2 py-0.5 rounded-full flex-shrink-0" 
                                      :class="selectedCategory === kat ? 'text-[#d6fb00] bg-[#d6fb00]/20' : 'text-[#00a3b5] bg-[#00a3b5]/10'" 
                                      x-text="count"></span>
                            </a>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Center: Main Articles -->
            <div class="col-span-1 lg:col-span-10">
                
                <!-- Filter & Search Section -->
                <div class="border p-6 rounded-3xl mb-10 backdrop-blur-md animate-fade-in-up delay-200" style="background-color: rgba(15, 42, 48, 0.7); border-color: rgba(214, 251, 0, 0.15);">
                    <div class="flex flex-col md:flex-row gap-5 items-end">
                        <!-- Search Input -->
                        <div class="flex-grow transition-all duration-500 w-full">
                            <label class="block text-[10px] font-bold text-[#8aacb0] uppercase tracking-widest mb-2"><i class="fa-solid fa-magnifying-glass mr-1.5 text-[#d6fb00]"></i> Pencarian</label>
                            <input type="text" x-model="searchQuery" placeholder="Cari judul artikel..." class="w-full rounded-xl px-4 py-3 text-sm pkp-search-input placeholder-[#5a7a80]">
                        </div>
                        
                        <!-- Sort Select -->
                        <div class="w-full md:w-56 flex-shrink-0 transition-all duration-500">
                            <label class="block text-[10px] font-bold text-[#8aacb0] uppercase tracking-widest mb-2"><i class="fa-solid fa-sort mr-1.5 text-[#d6fb00]"></i> Urutkan</label>
                            <select x-model="sortOrder" class="w-full rounded-xl px-4 py-3 text-sm pkp-search-input cursor-pointer">
                                <option value="terbaru">Terbaru</option>
                                <option value="terlama">Terlama</option>
                                <option value="custom">Custom Tanggal</option>
                            </select>
                        </div>
                        
                        <!-- Custom Date Range (Only visible if 'custom' is selected) -->
                        <div class="w-full md:w-[450px] flex-shrink-0 grid grid-cols-2 gap-4 transition-all duration-500" x-show="sortOrder === 'custom'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-4">
                            <div>
                                <label class="block text-[10px] font-bold text-[#8aacb0] uppercase tracking-widest mb-2"><i class="fa-regular fa-calendar mr-1.5 text-[#d6fb00]"></i> Dari Tanggal</label>
                                <input type="date" x-model="dateStart" class="w-full rounded-xl px-4 py-3 text-sm pkp-search-input" style="color-scheme: dark;">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-[#8aacb0] uppercase tracking-widest mb-2"><i class="fa-regular fa-calendar-check mr-1.5 text-[#d6fb00]"></i> Sampai</label>
                                <input type="date" x-model="dateEnd" class="w-full rounded-xl px-4 py-3 text-sm pkp-search-input" style="color-scheme: dark;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Category Badge (Mobile only) -->
                <div class="lg:hidden mb-6 flex flex-wrap gap-2 animate-fade-in-up" x-show="selectedCategory !== ''" x-cloak>
                    <span class="text-xs text-[#8aacb0] py-1.5">Kategori:</span>
                    <button @click="selectedCategory = ''" class="flex items-center gap-2 bg-[#d6fb00]/10 border border-[#d6fb00]/30 text-[#d6fb00] px-3 py-1.5 rounded-full text-xs font-bold transition-all hover:bg-[#d6fb00]/20">
                        <span x-text="selectedCategory"></span>
                        <i class="fa-solid fa-times"></i>
                    </button>
                </div>

                <!-- Info / Loading / Not Found State -->
                <div x-show="filteredArticles.length === 0" class="w-full text-center py-20 border border-dashed border-[#8aacb0]/30 rounded-3xl bg-white/5 animate-fade-in-up" x-cloak>
                    <i class="fa-regular fa-folder-open text-4xl text-[#8aacb0] mb-4"></i>
                    <h3 class="text-xl font-bold text-white mb-2">Artikel Tidak Ditemukan</h3>
                    <p class="text-[#8aacb0]">Coba gunakan kata kunci, rentang tanggal, atau kategori yang berbeda.</p>
                </div>

                <!-- Grid Artikel -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            <template x-for="item in filteredArticles" :key="item.id">
                <div class="pkp-article-card animate-fade-in-up">
                    <!-- Image Section -->
                    <div class="relative w-full overflow-hidden" style="height: 200px;">
                        <!-- Date Badge -->
                        <div class="absolute top-4 right-4 z-20 pkp-article-date w-12 h-12 rounded-xl flex flex-col items-center justify-center text-center">
                            <span class="font-black text-sm leading-none" style="color: #0a1a1f;" x-text="formatDay(item.createdAt)"></span>
                            <span class="text-[9px] font-bold uppercase tracking-wider mt-0.5" style="color: rgba(10, 26, 31, 0.7);" x-text="formatMonth(item.createdAt)"></span>
                        </div>
                        
                        <!-- Image Gradient Overlay -->
                        <div class="absolute inset-0 pkp-image-gradient z-10"></div>
                        
                        <img :src="'https://apiternak.krsjawa3.com/' + item.path_image" class="w-full h-full object-cover pkp-article-img" alt="Artikel" loading="lazy">
                    </div>

                    <!-- Content Section -->
                    <div class="p-6 relative z-20 pkp-content-gradient">
                        <a :href="'<?= base_url('Index/detail_artikel/') ?>' + item.id" class="block">
                            <h4 class="text-white font-extrabold text-base leading-snug pkp-article-title mb-3" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;" x-text="item.title"></h4>
                        </a>
                        <p class="text-xs leading-relaxed mb-5 pkp-line-clamp-3" style="color: #a1a1aa;" x-text="stripTags(item.body)"></p>
                        
                        <div class="pt-4 flex items-center justify-between" style="border-top: 1px solid rgba(255,255,255,0.05);">
                            <a :href="'<?= base_url('Index/detail_artikel/') ?>' + item.id" class="inline-flex items-center gap-2 text-xs font-bold transition-colors pkp-read-more" style="color: #d6fb00;">
                                Baca selengkapnya 
                                <i class="fa-solid fa-arrow-right-long text-[10px]"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </template>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Mengimpor data dari PHP ke Alpine JS -->
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('articleFilter', () => ({
        // Source data
        articles: <?= json_encode($articles ?? []) ?>,
        initialCategory: <?= json_encode($this->input->get('kat') ?: '') ?>,
        
        // Filter state
        searchQuery: '',
        sortOrder: 'terbaru',
        dateStart: '',
        dateEnd: '',
        selectedCategory: '',

        init() {
            if (this.initialCategory) {
                this.selectedCategory = this.initialCategory;
            }
        },

        get categories() {
            let cats = {};
            this.articles.forEach(a => {
                let kat = a.category && a.category.name ? a.category.name : 'Tanpa Kategori';
                if (!cats[kat]) cats[kat] = 0;
                cats[kat]++;
            });
            return cats;
        },

        // Derived / Computed property
        get filteredArticles() {
            let result = [...this.articles];

            // Filter by search query
            if (this.searchQuery.trim() !== '') {
                const q = this.searchQuery.toLowerCase();
                result = result.filter(a => a.title.toLowerCase().includes(q));
            }

            // Filter by selected category
            if (this.selectedCategory !== '') {
                result = result.filter(a => {
                    let kat = a.category && a.category.name ? a.category.name : 'Tanpa Kategori';
                    return kat === this.selectedCategory;
                });
            }

            // Filter by Custom Date
            if (this.sortOrder === 'custom') {
                if (this.dateStart) {
                    const start = new Date(this.dateStart).getTime();
                    result = result.filter(a => new Date(a.createdAt).getTime() >= start);
                }
                if (this.dateEnd) {
                    // Set end to end of the day
                    const end = new Date(this.dateEnd);
                    end.setHours(23, 59, 59, 999);
                    result = result.filter(a => new Date(a.createdAt).getTime() <= end.getTime());
                }
            }

            // Sorting
            result.sort((a, b) => {
                const dateA = new Date(a.createdAt).getTime();
                const dateB = new Date(b.createdAt).getTime();
                
                if (this.sortOrder === 'terlama') {
                    return dateA - dateB;
                } else {
                    // terbaru is default, and also applies to 'custom' unless explicitly changed
                    return dateB - dateA;
                }
            });

            return result;
        },

        // Helper Formatters
        formatDay(dateStr) {
            const d = new Date(dateStr);
            let day = d.getDate().toString();
            return day.length < 2 ? '0' + day : day;
        },
        
        formatMonth(dateStr) {
            const months = ['JAN', 'FEB', 'MAR', 'APR', 'MEI', 'JUN', 'JUL', 'AGU', 'SEP', 'OKT', 'NOV', 'DES'];
            const d = new Date(dateStr);
            return months[d.getMonth()];
        },
        
        stripTags(html) {
            let tmp = document.createElement("DIV");
            tmp.innerHTML = html;
            return tmp.textContent || tmp.innerText || "";
        }
    }))
})
</script>
