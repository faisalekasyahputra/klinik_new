<div class="py-4 sm:py-6 px-1 sm:px-2 relative font-outfit" x-data="designCarousel()" @designs-loaded.window="$nextTick(() => recalc())">
    <!-- Section Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-end justify-between mb-10 gap-4">
        <div>
            <h2 class="text-3xl sm:text-4xl font-black text-[color:var(--portal-text)] tracking-tighter font-jakarta"><span style="color: var(--brand);">Bank</span> Desain</h2>
            <p class="text-[color:var(--portal-text-muted)] text-sm mt-2">Temukan desain rumah yang sesuai kebutuhan Anda</p>
        </div>
            <div class="flex items-center gap-2">
                <button @click="prev()" class="w-10 h-10 rounded-xl bg-[#d6fb00]/5 border border-[#d6fb00]/20 hover:border-[#00a3b5]/50 text-[#8aacb0] hover:text-white flex items-center justify-center transition-all"><i class="fa-solid fa-chevron-left text-xs"></i></button>
                <button @click="next()" class="w-10 h-10 rounded-xl bg-[#d6fb00]/5 border border-[#d6fb00]/20 hover:border-[#00a3b5]/50 text-[#8aacb0] hover:text-white flex items-center justify-center transition-all"><i class="fa-solid fa-chevron-right text-xs"></i></button>
            </div>
        </div>

        <div class="overflow-hidden rounded-2xl p-1 -mx-1">
            <div id="designs-container" class="carousel-track" :style="'transform: translateX(-' + (offset * (100 / perView)) + '%)'">
                <!-- Skeleton Loaders (replaced by AJAX) -->
                <div class="px-3"><div class="skeleton h-80"></div></div>
                <div class="px-3"><div class="skeleton h-80"></div></div>
                <div class="px-3"><div class="skeleton h-80"></div></div>
            </div>
        </div>

        <!-- Dot Indicators -->
        <div class="flex items-center justify-center gap-1.5 mt-6">
            <template x-for="i in totalPages" :key="i">
                <button @click="goToPage(i-1)" class="w-1.5 h-1.5 rounded-full transition-all duration-300" :class="currentPage === i-1 ? 'w-5 bg-[#00a3b5]' : 'bg-[#d6fb00]/12 hover:bg-[#d6fb00]/25'"></button>
            </template>
        </div>
</div>

<script>
// ponytail: disalin persis dari home/awal.php (designCarousel), standalone, tidak depend elemen lain
function designCarousel() {
    return {
        offset: 0,
        perView: 3,
        totalItems: 0,
        currentPage: 0,
        totalPages: 1,

        init() {
            this.updatePerView();
            window.addEventListener('resize', () => this.updatePerView());
        },
        updatePerView() {
            if (window.innerWidth < 640) this.perView = 1;
            else if (window.innerWidth < 1024) this.perView = 2;
            else this.perView = 3;
            this.recalc();
        },
        recalc() {
            this.totalItems = document.querySelectorAll('#designs-container > *').length || 0;
            this.totalPages = Math.ceil(this.totalItems / this.perView);
            if (this.offset >= this.totalItems) this.offset = 0;
            this.currentPage = Math.floor(this.offset / this.perView);
        },
        next() {
            this.recalc();
            this.offset = (this.offset + this.perView >= this.totalItems) ? 0 : this.offset + this.perView;
            this.currentPage = Math.floor(this.offset / this.perView);
        },
        prev() {
            this.recalc();
            this.offset = (this.offset - this.perView < 0) ? Math.max(0, this.totalItems - this.perView) : this.offset - this.perView;
            this.currentPage = Math.floor(this.offset / this.perView);
        },
        goToPage(p) {
            this.offset = p * this.perView;
            this.currentPage = p;
        }
    }
}

// Halaman mandiri: load penuh langsung saat DOM siap (bukan lazy-load-on-scroll seperti homepage)
document.addEventListener('DOMContentLoaded', function() {
    $.ajax({
        url: '<?= base_url("ajax_house_designs") ?>',
        success: function(html) {
            $('#designs-container').html(html);
            window.dispatchEvent(new CustomEvent('designs-loaded'));
        },
        error: function() {
            $('#designs-container').html('<p class="col-span-3 text-center text-[#5a7a80] py-8">Gagal memuat data desain rumah.</p>');
        }
    });
});
</script>
