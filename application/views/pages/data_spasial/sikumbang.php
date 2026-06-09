<!-- application/views/pages/data_spasial/sikumbang.php -->
<main class="min-h-screen pt-24 pb-16  relative overflow-hidden font-outfit">
    <!-- Background Ornaments -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <!-- Batik Pattern Overlay -->
        
        
        <div class="absolute top-[-10%] right-[-5%] w-[600px] h-[600px] rounded-full bg-[#d6fb00]/5 blur-[120px]"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[500px] h-[500px] rounded-full bg-[#00a3b5]/5 blur-[100px]"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        <!-- Header -->
        <div class="text-center mb-16 animate-fade-in-up">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#d6fb00]/10 border border-[#d6fb00]/20 text-[#ecffb6] text-xs font-semibold uppercase tracking-widest mb-4">
                <i class="fa-solid fa-house-chimney"></i>
                Menu Umum
            </div>
            <h1 class="text-4xl md:text-5xl font-extrabold text-white tracking-tight mb-4">
                Data Perumahan <span class="text-[#d6fb00] drop-shadow-[0_0_15px_rgba(214,251,0,0.3)]">Sikumbang</span>
            </h1>
            <p class="text-lg text-zinc-400 max-w-2xl mx-auto">
                Integrasi Data Sistem Informasi Kumpulan Pengembang (Sikumbang) Tapera.
            </p>
        </div>

        <!-- Search Bar / Filter Area -->
        <div class="border p-6 sm:p-8 rounded-3xl mb-10 backdrop-blur-md animate-fade-in-up" style="background-color: rgba(15, 42, 48, 0.7); border-color: rgba(214, 251, 0, 0.15); animation-delay: 0.1s;">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-end">
                <div class="lg:col-span-4">
                    <label class="block text-[10px] font-bold text-[#8aacb0] uppercase tracking-widest mb-2"><i class="fa-solid fa-location-dot mr-1.5 text-[#d6fb00]"></i> Wilayah</label>
                    <select id="kodeWilayah" onchange="cari_wil()" class="w-full bg-[#0a1a1f] border border-white/10 rounded-xl px-4 py-3 text-sm outline-none text-white focus:border-[#d6fb00]/50 transition-all">
                        <option value="33">Seluruh Jawa Tengah</option>
                        <?php foreach ($kabupaten_kota_jateng as $kode => $nama): ?>
                        <option value="<?= $kode ?>"><?= $nama ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lg:col-span-8">
                    <label class="block text-[10px] font-bold text-[#8aacb0] uppercase tracking-widest mb-2"><i class="fa-solid fa-magnifying-glass mr-1.5 text-[#d6fb00]"></i> Nama Perumahan</label>
                    <div class="relative flex items-center">
                        <input id="keyword" onkeyup="cari_wil()" type="text" placeholder="Cari nama perumahan..." class="w-full bg-[#0a1a1f] border border-white/10 rounded-xl px-4 py-3 pr-12 text-sm outline-none text-white focus:border-[#d6fb00]/50 transition-all placeholder-[#5a7a80]">
                        <button onclick="cari_wil()" class="absolute right-1.5 flex items-center justify-center bg-[#d6fb00] hover:bg-[#b5d400] text-[#030b0e] w-10 h-10 rounded-lg transition-all">
                            <i class="fa-solid fa-magnifying-glass text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-center mt-6 pt-6" style="border-top: 1px solid rgba(214, 251, 0, 0.1);">
                <div class="lg:col-span-4">
                    <label class="block text-[10px] font-bold text-[#8aacb0] uppercase tracking-widest mb-2"><i class="fa-solid fa-sort mr-1.5 text-[#d6fb00]"></i> Urutkan</label>
                    <select id="sort" onchange="cari_wil()" class="w-full bg-[#0a1a1f] border border-white/10 rounded-xl px-4 py-2.5 text-sm outline-none text-white focus:border-[#d6fb00]/50 transition-all">
                        <option value="terbaru">Terbaru</option>
                        <option value="subsidi-termurah">Harga Terendah</option>
                        <option value="subsidi-tertinggi">Harga Tertinggi</option>
                    </select>
                </div>
                <div class="lg:col-span-8 flex justify-end">
                    <label class="relative inline-flex items-center cursor-pointer group">
                        <input type="checkbox" id="status_subsidi" onchange="cari_wil()" class="sr-only peer" checked>
                        <div class="w-11 h-6 bg-[#0a1a1f] peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all border border-white/10 peer-checked:bg-[#d6fb00] peer-checked:after:"></div>
                        <span class="ml-3 text-sm font-semibold text-zinc-300 group-hover:text-white transition-colors">Hanya Rumah Subsidi</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Property Grid Container (Loaded via AJAX) -->
        <div id="temp_rumah" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 animate-fade-in-up" style="animation-delay: 0.2s;">
            <?php $this->load->view('components/cards/rumah', ['results' => $results]); ?>
        </div>

        <!-- Load More Section -->
        <div class="flex justify-center mt-12 mb-8 animate-fade-in-up" style="animation-delay: 0.3s;">
            <button id="btn-load-more" data-page="2" onclick="load_more_data()" class="group flex items-center gap-3 bg-[#0a1a1f] hover:bg-[#d6fb00]/10 border border-[#d6fb00]/30 hover:border-[#d6fb00] text-zinc-300 hover:text-[#d6fb00] px-8 py-3.5 rounded-xl text-sm font-bold transition-all duration-300 shadow-lg">
                <span id="text-load">Muat Lebih Banyak</span>
                <i class="fa-solid fa-rotate-right transition-transform group-hover:rotate-180"></i>
            </button>
        </div>
        
    </div>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function cari_wil() {
    var keyword = document.getElementById('keyword').value;
    var kodeWilayah = document.getElementById('kodeWilayah').value;
    var sort = document.getElementById('sort').value;
    let isChecked = document.getElementById('status_subsidi').checked;
    let statusRumah = isChecked ? 'subsidi' : 'semua';
    
    // Tampilkan loading skeleton atau teks loading (opsional)
    jQuery('#temp_rumah').html('<div class="col-span-full text-center py-10"><i class="fa-solid fa-circle-notch fa-spin text-4xl text-[#d6fb00]"></i><p class="mt-4 text-zinc-400">Mencari perumahan...</p></div>');
    
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
    
    let originalText = jQuery('#text-load').text();
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
        },
        error: function() {
            jQuery('#text-load').text(originalText);
        }
    });
}
</script>
