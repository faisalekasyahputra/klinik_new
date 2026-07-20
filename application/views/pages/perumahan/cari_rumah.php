<div class="py-4 sm:py-6 px-1 sm:px-2 relative font-outfit">
    <!-- Header -->
    <div class="text-center mb-10" data-aos="fade-down">
        <h3 class="text-3xl sm:text-4xl font-extrabold text-[color:var(--portal-text)] tracking-tight mb-2">Cari <span class="text-[color:var(--portal-brand)]">Perumahan</span></h3>
        <p class="text-[color:var(--portal-text-muted)] text-xs">Data real-time dari Sikumbang — Tapera</p>
    </div>

        <style>
            .tl-search-input {
                background-color: rgba(214, 251, 0, 0.03);
                border: 1px solid rgba(214, 251, 0, 0.15);
                color: #ecffb6;
            }
            .tl-search-input:focus {
                border-color: rgba(214, 251, 0, 0.4);
                box-shadow: 0 0 0 3px rgba(214,251,0,0.1);
            }
            .tl-search-input option {
                background-color: #0a1a1f;
                color: #ecffb6;
            }
            .tl-btn-search {
                background-color: #d6fb00;
                color: #0a1a1f;
                width: 36px;
                height: 36px;
                border-radius: 8px;
            }
            .tl-btn-search:hover { background-color: #ecffb6; }

            /* Custom CSS Toggle */
            .tl-toggle-bg {
                position: relative;
                width: 44px;
                height: 24px;
                background-color: rgba(255, 255, 255, 0.1);
                border-radius: 9999px;
                transition: all 0.3s;
                cursor: pointer;
            }
            .tl-toggle-circle {
                position: absolute;
                top: 3px;
                left: 3px;
                width: 18px;
                height: 18px;
                background-color: #8aacb0;
                border-radius: 50%;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            input:checked + .tl-toggle-bg {
                background-color: #d6fb00;
            }
            input:checked + .tl-toggle-bg .tl-toggle-circle {
                transform: translateX(20px);
                background-color: #0a1a1f;
            }
        </style>

        <div class="border p-6 sm:p-8 rounded-3xl mb-10 backdrop-blur-md" style="background-color: rgba(15, 42, 48, 0.7); border-color: rgba(214, 251, 0, 0.15);" data-aos="fade-up" data-aos-delay="100">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-end">
                <div class="lg:col-span-4">
                    <label class="block text-[10px] font-bold text-[#8aacb0] uppercase tracking-widest mb-2"><i class="fa-solid fa-location-dot mr-1.5 text-[#d6fb00]"></i> Wilayah</label>
                    <select id="kodeWilayah" onchange="cari_wil()" class="w-full rounded-xl px-4 py-3 text-sm outline-none transition-all tl-search-input">
                        <option value="33">Seluruh Jawa Tengah</option>
                        <?php foreach ($kabupaten_kota_jateng as $kode => $nama): ?>
                        <option value="<?= $kode ?>"><?= $nama ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lg:col-span-8">
                    <label class="block text-[10px] font-bold text-[#8aacb0] uppercase tracking-widest mb-2"><i class="fa-solid fa-magnifying-glass mr-1.5 text-[#d6fb00]"></i> Nama Perumahan</label>
                    <div class="relative flex items-center">
                        <input id="keyword" onkeyup="cari_wil()" type="text" placeholder="Cari nama perumahan..." class="w-full rounded-xl px-4 py-3 pr-12 text-sm outline-none transition-all tl-search-input placeholder-[#5a7a80]">
                        <button onclick="cari_wil()" class="absolute right-1.5 flex items-center justify-center transition-all tl-btn-search">
                            <i class="fa-solid fa-magnifying-glass text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-center mt-6 pt-6" style="border-top: 1px solid rgba(214, 251, 0, 0.1);">
                <div class="lg:col-span-4">
                    <label class="block text-[10px] font-bold text-[#8aacb0] uppercase tracking-widest mb-2"><i class="fa-solid fa-sort mr-1.5 text-[#d6fb00]"></i> Urutkan</label>
                    <select id="sort" onchange="cari_wil()" class="w-full rounded-xl px-4 py-2.5 text-sm outline-none transition-all tl-search-input">
                        <option value="terbaru">Terbaru</option>
                        <option value="subsidi-termurah">Harga Terendah</option>
                        <option value="subsidi-tertinggi">Harga Tertinggi</option>
                    </select>
                </div>
                <div class="lg:col-span-8 flex lg:justify-end items-center mt-2 lg:mt-0">
                    <label class="inline-flex items-center cursor-pointer group">
                        <input id="status_subsidi" value="subsidi" onchange="cari_wil()" type="checkbox" class="sr-only" checked>
                        <div class="tl-toggle-bg">
                            <div class="tl-toggle-circle"></div>
                        </div>
                        <span class="ml-3 text-sm font-semibold text-zinc-400 group-hover:text-white transition-colors">Hanya Rumah Subsidi</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6" id="temp_rumah" data-aos="fade-up" data-aos-delay="200">
            <div class="skeleton h-72"></div>
            <div class="skeleton h-72"></div>
            <div class="skeleton h-72"></div>
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
    let isChecked = document.getElementById('status_subsidi').checked;
    let statusRumah = isChecked ? 'subsidi' : 'semua';
    $.ajax({
        url: '<?= base_url('cari_wil') ?>?kodeWilayah='+kodeWilayah+'&keyword='+keyword+'&sort='+sort+'&status_rumah='+statusRumah,
        success: function(response) { jQuery('#temp_rumah').html(response); }
    });
}

function load_more_data() {
    let btn = jQuery('#btn-load-more');
    let currentPage = parseInt(btn.attr('data-page'));
    let nextPage = currentPage + 1;
    var keyword = document.getElementById('keyword').value;
    var kodeWilayah = document.getElementById('kodeWilayah').value;
    var sort = document.getElementById('sort').value;
    let isChecked = document.getElementById('status_subsidi').checked;
    let statusRumah = isChecked ? 'subsidi' : 'semua';
    jQuery('#text-load').text('Sedang Memuat...');
    jQuery.ajax({
        url: '<?= base_url('load_more') ?>?kodeWilayah='+kodeWilayah+'&keyword='+keyword+'&sort='+sort+'&status_rumah='+statusRumah+'&page='+nextPage,
        success: function(response) {
            if (jQuery.trim(response) !== '') {
                jQuery('#temp_rumah').append(response);
                btn.attr('data-page', nextPage);
                jQuery('#text-load').text('Muat Lebih Banyak');
            } else {
                jQuery('#text-load').text('Semua Data Telah Dimuat');
                btn.prop('disabled', true).fadeOut(2000);
            }
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    cari_wil();
});
</script>
