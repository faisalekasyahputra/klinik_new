<div class="py-4 sm:py-6 px-1 sm:px-2 relative font-outfit">
    <!-- Header -->
    <div class="text-center mb-10" data-aos="fade-down">
        <h3 class="text-3xl sm:text-4xl font-extrabold text-[color:var(--portal-text)] tracking-tight mb-2">Cari <span class="text-[color:var(--portal-brand)]">Perumahan</span></h3>
        <p class="text-[color:var(--portal-text-muted)] text-xs">Data real-time dari Sikumbang — Tapera</p>
    </div>

        <div class="border p-6 sm:p-8 rounded-3xl mb-10 shadow-sm bg-[color:var(--portal-bg-card)] border-[color:var(--portal-border)]" data-aos="fade-up" data-aos-delay="100">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-end">
                <div class="lg:col-span-3">
                    <label class="block text-[10px] font-bold text-[color:var(--portal-text-muted)] uppercase tracking-widest mb-2"><i class="fa-solid fa-location-dot mr-1.5 text-[color:var(--portal-brand)]"></i> Wilayah</label>
                    <select id="kodeWilayah" onchange="cari_wil()" class="w-full rounded-xl px-4 py-3 text-sm outline-none transition-all bg-[color:var(--portal-btn-bg)] border border-[color:var(--portal-border)] text-[color:var(--portal-text)] focus:border-[color:var(--portal-brand)]">
                        <option value="33">Seluruh Jawa Tengah</option>
                        <?php foreach ($kabupaten_kota_jateng as $kode => $nama): ?>
                        <option value="<?= $kode ?>"><?= $nama ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lg:col-span-3">
                    <label class="block text-[10px] font-bold text-[color:var(--portal-text-muted)] uppercase tracking-widest mb-2"><i class="fa-solid fa-list mr-1.5 text-[color:var(--portal-brand)]"></i> Kategori</label>
                    <select id="searchBy" onchange="cari_wil()" class="w-full rounded-xl px-4 py-3 text-sm outline-none transition-all bg-[color:var(--portal-btn-bg)] border border-[color:var(--portal-border)] text-[color:var(--portal-text)] focus:border-[color:var(--portal-brand)]">
                        <option value="nama-perumahan">Nama Perumahan</option>
                        <option value="nama-pengembang">Nama Pengembang</option>
                        <option value="asosiasi">Asosiasi</option>
                    </select>
                </div>
                <div class="lg:col-span-6">
                    <label class="block text-[10px] font-bold text-[color:var(--portal-text-muted)] uppercase tracking-widest mb-2"><i class="fa-solid fa-magnifying-glass mr-1.5 text-[color:var(--portal-brand)]"></i> Kata Kunci</label>
                    <div class="relative flex items-center">
                        <input id="keyword" onkeyup="cari_wil()" type="text" placeholder="Masukkan kata kunci..." class="w-full rounded-xl px-4 py-3 pr-12 text-sm outline-none transition-all bg-[color:var(--portal-btn-bg)] border border-[color:var(--portal-border)] text-[color:var(--portal-text)] focus:border-[color:var(--portal-brand)] placeholder-[color:var(--portal-text-muted)]">
                        <button onclick="cari_wil()" class="absolute right-1.5 flex items-center justify-center transition-all w-9 h-9 rounded-lg bg-[color:var(--portal-brand)] text-[color:var(--portal-bg)] hover:opacity-80">
                            <i class="fa-solid fa-magnifying-glass text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-center mt-6 pt-6" style="border-top: 1px solid var(--portal-border);">
                <div class="lg:col-span-3">
                    <label class="block text-[10px] font-bold text-[color:var(--portal-text-muted)] uppercase tracking-widest mb-2"><i class="fa-solid fa-sort mr-1.5 text-[color:var(--portal-brand)]"></i> Urutkan</label>
                    <select id="sort" onchange="cari_wil()" class="w-full rounded-xl px-4 py-2.5 text-sm outline-none transition-all bg-[color:var(--portal-btn-bg)] border border-[color:var(--portal-border)] text-[color:var(--portal-text)] focus:border-[color:var(--portal-brand)]">
                        <option value="terbaru">Terbaru</option>
                        <option value="subsidi-termurah">Harga Terendah</option>
                        <option value="subsidi-tertinggi">Harga Tertinggi</option>
                    </select>
                </div>
                <div class="lg:col-span-9 flex lg:justify-end items-center mt-2 lg:mt-0">
                    <label class="inline-flex items-center cursor-pointer group">
                        <input id="status_subsidi" value="subsidi" onchange="cari_wil()" type="checkbox" class="sr-only" checked>
                        <div class="relative w-11 h-6 rounded-full transition-all duration-300 bg-[color:var(--portal-brand)] border border-[color:var(--portal-border)]" id="toggle-bg">
                            <div class="absolute top-[2px] left-[2px] w-[18px] h-[18px] rounded-full transition-all duration-300 bg-[color:var(--portal-bg)]" id="toggle-circle" style="transform: translateX(20px);"></div>
                        </div>
                        <span class="ml-3 text-sm font-semibold text-[color:var(--portal-text-muted)] group-hover:text-[color:var(--portal-text)] transition-colors">Hanya Rumah Subsidi</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4 sm:gap-5" id="temp_rumah" data-aos="fade-up" data-aos-delay="200">
            <div class="skeleton h-72"></div>
            <div class="skeleton h-72"></div>
            <div class="skeleton h-72"></div>
            <div class="skeleton h-72"></div>
            <div class="skeleton h-72 hidden xl:block"></div>
        </div>

        <div class="w-full flex justify-center mt-12" data-aos="zoom-in" data-aos-delay="300">
            <button id="btn-load-more" onclick="load_more_data()" data-page="1" class="group inline-flex items-center gap-2 px-12 sm:px-24 py-3.5 bg-[#d6fb00]/5 border border-[#d6fb00]/20 text-[#ecffb6] font-semibold text-xs rounded-full hover:bg-[#d6fb00]/10 hover:border-[#d6fb00]/25 tracking-wide transition-all">
                <i class="fa-solid fa-circle-arrow-down text-sm text-[#5a7a80] group-hover:text-[#d6fb00] group-hover:translate-y-[1px] transition-all"></i>
                <span id="text-load">Muat Lebih Banyak</span>
            </button>
        </div>

        <!-- CTA: Direktori Sosmed Pengembang (perlu login) -->
        <div class="mt-16 border p-6 sm:p-8 rounded-3xl backdrop-blur-md flex flex-col sm:flex-row items-center justify-between gap-6" style="background-color: rgba(15, 42, 48, 0.7); border-color: rgba(168, 85, 247, 0.2);" data-aos="fade-up">
            <div class="flex items-start gap-4">
                <div class="text-purple-400 shrink-0 pt-0.5">
                    <i class="fa-solid fa-bullhorn text-[28px]"></i>
                </div>
                <div class="space-y-1.5">
                    <h4 class="text-white font-bold text-base sm:text-lg tracking-tight">Lihat Direktori Sosial Media Pengembang</h4>
                    <p class="text-zinc-400 text-xs sm:text-sm leading-relaxed">Jelajahi kanal media sosial resmi pengembang perumahan untuk info unit dan promo terbaru. Login diperlukan untuk mengakses direktori lengkap.</p>
                </div>
            </div>
            <a href="<?= base_url('Pengembang/publikasi') ?>" class="shrink-0 inline-flex items-center gap-2 px-6 py-3 bg-purple-500/10 border border-purple-400/40 text-purple-300 font-semibold text-xs rounded-full hover:bg-purple-500/20 hover:border-purple-400/60 tracking-wide transition-all whitespace-nowrap">
                Buka Direktori
                <i class="fa-solid fa-arrow-right text-xs"></i>
            </a>
        </div>

</div>

<script>
function cari_wil() {
    var keyword = document.getElementById('keyword').value;
    var kodeWilayah = document.getElementById('kodeWilayah').value;
    var sort = document.getElementById('sort').value;
    var searchBy = document.getElementById('searchBy').value;
    let isChecked = document.getElementById('status_subsidi').checked;
    let statusRumah = isChecked ? 'subsidi' : 'komersil';
    
    let toggleBg = document.getElementById('toggle-bg');
    let toggleCircle = document.getElementById('toggle-circle');
    
    if (isChecked) {
        toggleBg.style.backgroundColor = 'var(--portal-brand)';
        toggleCircle.style.transform = 'translateX(20px)';
        toggleCircle.style.backgroundColor = 'var(--portal-bg)';
    } else {
        toggleBg.style.backgroundColor = 'var(--portal-btn-bg)';
        toggleCircle.style.transform = 'translateX(0)';
        toggleCircle.style.backgroundColor = 'var(--portal-text-muted)';
    }

    jQuery('#temp_rumah').html(`
        <div class="skeleton h-72"></div>
        <div class="skeleton h-72"></div>
        <div class="skeleton h-72"></div>
        <div class="skeleton h-72"></div>
        <div class="skeleton h-72 hidden xl:block"></div>
    `);

    // Setiap pencarian/filter selalu memulai pagination dari halaman pertama.
    jQuery('#btn-load-more').attr('data-page', '1').prop('disabled', false).show();
    jQuery('#text-load').text('Muat Lebih Banyak');

    $.ajax({
        url: '<?= base_url('cari_wil') ?>?kodeWilayah='+encodeURIComponent(kodeWilayah)+'&keyword='+encodeURIComponent(keyword)+'&searchBy='+encodeURIComponent(searchBy)+'&sort='+encodeURIComponent(sort)+'&status_rumah='+statusRumah+'&page=1&limit=9',
        success: function(response) { jQuery('#temp_rumah').html(response); },
        error: function() { jQuery('#temp_rumah').html('<p class="col-span-full py-10 text-center text-sm text-[color:var(--portal-text-muted)]">Data rumah gagal dimuat. Silakan coba lagi.</p>'); }
    });
}

function load_more_data() {
    let btn = jQuery('#btn-load-more');
    let currentPage = parseInt(btn.attr('data-page'), 10) || 1;
    let nextPage = currentPage + 1;
    var kodeWilayah = document.getElementById('kodeWilayah').value;
    var keyword = document.getElementById('keyword').value;
    var sort = document.getElementById('sort').value;
    var searchBy = document.getElementById('searchBy').value;
    let isChecked = document.getElementById('status_subsidi').checked;
    let statusRumah = isChecked ? 'subsidi' : 'komersil';
    
    jQuery('#text-load').text('Sedang Memuat...');
    jQuery.ajax({
        url: '<?= base_url('load_more') ?>?kodeWilayah='+encodeURIComponent(kodeWilayah)+'&keyword='+encodeURIComponent(keyword)+'&searchBy='+encodeURIComponent(searchBy)+'&sort='+encodeURIComponent(sort)+'&status_rumah='+statusRumah+'&page='+nextPage+'&limit=9',
        success: function(response) {
            if (jQuery.trim(response) !== '') {
                jQuery('#temp_rumah').append(response);
                btn.attr('data-page', nextPage);
                jQuery('#text-load').text('Muat Lebih Banyak');
            } else {
                jQuery('#text-load').text('Semua Data Telah Dimuat');
                btn.prop('disabled', true).fadeOut(2000);
            }
        },
        error: function() {
            btn.prop('disabled', false).show();
            jQuery('#text-load').text('Coba Lagi');
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    cari_wil();
});
</script>
