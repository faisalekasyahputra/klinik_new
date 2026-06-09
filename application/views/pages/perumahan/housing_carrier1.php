<section class="w-full  pt-24 pb-16 px-4 sm:px-6 lg:px-8 relative min-h-screen font-outfit">
    <!-- Background Ornaments -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <!-- Batik Pattern Overlay -->
        
        
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-[#d6fb00]/5 blur-[120px] rounded-full pointer-events-none"></div>
    </div>

    <div class="max-w-7xl mx-auto relative z-10">
        
        <!-- Breadcrumb -->
        <div class="mb-10">
            <nav class="flex text-[10px] sm:text-xs text-zinc-500 font-bold uppercase tracking-widest" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2">
                    <li class="inline-flex items-center">
                        <a href="<?= base_url() ?>" class="hover:text-[#d6fb00] transition-colors"><i class="fa-solid fa-house mr-2"></i>Beranda</a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fa-solid fa-chevron-right text-[8px] mx-2"></i>
                            <a href="<?= base_url('umum') ?>" class="hover:text-[#d6fb00] transition-colors">Layanan Umum</a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fa-solid fa-chevron-right text-[8px] mx-2"></i>
                            <span class="text-[#d6fb00]">Housing Career</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

<div class="w-full max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-8 items-stretch z-10 relative">
    
    <div class="bg-[#0f2a30] border border-[#d6fb00]/20 rounded-[2.5rem] p-6 sm:p-10 backdrop-blur-xl shadow-2xl flex flex-col justify-between">
        
        <div class="text-center mb-8">
            <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                Housing Carrier
            </h2>
        </div>

        <form action="#" method="POST" class="space-y-4">
            <div>
                <label class="block text-[#d6fb00] font-extrabold text-[11px] uppercase tracking-wider mb-2">Sumber Pendapatan</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="relative cursor-pointer">
                        <input type="radio" name="sumber_pendapatan" value="tetap" class="peer sr-only">
                        <div class="w-full bg-[#0a1a1f] border border-[#d6fb00]/20 text-zinc-400 rounded-xl px-4 py-3 text-sm font-semibold text-center transition-all duration-300 peer-checked:!bg-[#d6fb00] peer-checked:border-[#d6fb00] peer-checked:!text-[#0a1a1f] peer-checked:font-extrabold peer-checked:uppercase peer-checked:tracking-wider peer-checked:shadow-sm hover:border-[#d6fb00]/50">
                            Tetap
                        </div>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="sumber_pendapatan" value="tidak_tetap" class="peer sr-only">
                        <div class="w-full bg-[#0a1a1f] border border-[#d6fb00]/20 text-zinc-400 rounded-xl px-4 py-3 text-sm font-semibold text-center transition-all duration-300 peer-checked:!bg-[#d6fb00] peer-checked:border-[#d6fb00] peer-checked:!text-[#0a1a1f] peer-checked:font-extrabold peer-checked:uppercase peer-checked:tracking-wider peer-checked:shadow-sm hover:border-[#d6fb00]/50">
                            Tidak Tetap
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-[#d6fb00] font-extrabold text-[11px] uppercase tracking-wider mb-2">Penghasilan (Rp. XXX)</label>
                <input type="number" placeholder="Masukkan nominal angka" class="w-full bg-[#0a1a1f] border border-[#d6fb00]/20 text-white rounded-xl px-4 py-3 text-sm font-semibold placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40">
            </div>

            <div>
                <label class="block text-[#d6fb00] font-extrabold text-[11px] uppercase tracking-wider mb-2">Punya Lahan?</label>
                <div class="grid grid-cols-2 gap-3">
                    <label class="relative cursor-pointer">
                        <input type="radio" name="punya_lahan" value="ya" class="peer sr-only">
                        <div class="w-full bg-[#0a1a1f] border border-[#d6fb00]/20 text-zinc-400 rounded-xl px-4 py-3 text-sm font-semibold text-center transition-all duration-300 peer-checked:!bg-[#d6fb00] peer-checked:border-[#d6fb00] peer-checked:!text-[#0a1a1f] peer-checked:font-extrabold peer-checked:uppercase peer-checked:tracking-wider peer-checked:shadow-sm hover:border-[#d6fb00]/50">
                            Ya, Sudah Punya
                        </div>
                    </label>
                    <label class="relative cursor-pointer">
                        <input type="radio" name="punya_lahan" value="tidak" class="peer sr-only">
                        <div class="w-full bg-[#0a1a1f] border border-[#d6fb00]/20 text-zinc-400 rounded-xl px-4 py-3 text-sm font-semibold text-center transition-all duration-300 peer-checked:!bg-[#d6fb00] peer-checked:border-[#d6fb00] peer-checked:!text-[#0a1a1f] peer-checked:font-extrabold peer-checked:uppercase peer-checked:tracking-wider peer-checked:shadow-sm hover:border-[#d6fb00]/50">
                            Tidak Ada
                        </div>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-[#d6fb00] font-extrabold text-[11px] uppercase tracking-wider mb-2">Jumlah Anggota Keluarga</label>
                <div class="flex flex-wrap gap-2 items-center">
                    <!-- Preset Options 1 to 5 -->
                    <div class="flex gap-2">
                        <?php for($i=1; $i<=5; $i++): ?>
                        <label class="relative cursor-pointer">
                            <input type="radio" name="jml_keluarga_radio" value="<?= $i ?>" class="peer sr-only" onchange="document.getElementById('jml_keluarga_custom').value = ''">
                            <div class="w-10 h-10 sm:w-[46px] sm:h-[46px] flex items-center justify-center bg-[#0a1a1f] border border-[#d6fb00]/20 text-zinc-400 rounded-xl text-sm font-bold transition-all duration-300 peer-checked:!bg-[#d6fb00] peer-checked:border-[#d6fb00] peer-checked:!text-[#0a1a1f] peer-checked:font-extrabold peer-checked:shadow-sm hover:border-[#d6fb00]/50 shadow-sm">
                                <?= $i ?>
                            </div>
                        </label>
                        <?php endfor; ?>
                    </div>
                    
                    <!-- Custom Input for > 5 -->
                    <div class="flex-1 min-w-[100px]">
                        <input type="number" id="jml_keluarga_custom" name="jml_keluarga_custom" placeholder="Lainnya..." min="6" 
                               onfocus="document.querySelectorAll('input[name=\'jml_keluarga_radio\']').forEach(r => r.checked = false);"
                               class="w-full h-10 sm:h-[46px] bg-[#0a1a1f] border border-[#d6fb00]/20 text-white rounded-xl px-4 text-sm font-semibold placeholder-zinc-500 focus:outline-none focus:border-[#d6fb00] focus:bg-[#d6fb00]/5 transition-all duration-300 shadow-sm">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-[#d6fb00] font-extrabold text-[11px] uppercase tracking-wider mb-2">Usia & Tahun Lahir</label>
                <div class="grid grid-cols-2 gap-3">
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-zinc-500 text-xs font-bold"><i class="fa-regular fa-calendar"></i></span>
                        <input type="number" id="tahun_lahir" placeholder="Tahun Lahir" min="1950" max="<?= date('Y') ?>"
                               oninput="
                                   const year = parseInt(this.value); 
                                   const currentYear = new Date().getFullYear();
                                   if(year > 1900 && year <= currentYear) {
                                       document.getElementById('usia_kalkulator').value = currentYear - year;
                                       document.getElementById('usia_kalkulator').classList.add('text-[#d6fb00]', 'border-[#d6fb00]/50');
                                   } else {
                                       document.getElementById('usia_kalkulator').value = '';
                                       document.getElementById('usia_kalkulator').classList.remove('text-[#d6fb00]', 'border-[#d6fb00]/50');
                                   }"
                               class="w-full h-10 sm:h-[46px] bg-[#0a1a1f] border border-[#d6fb00]/20 text-white rounded-xl pl-10 pr-4 text-sm font-semibold placeholder-zinc-500 focus:outline-none focus:border-[#d6fb00] focus:bg-[#d6fb00]/5 transition-all duration-300">
                    </div>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-zinc-500 text-xs font-bold">Usia:</span>
                        <input type="number" id="usia_kalkulator" placeholder="0" readonly
                               class="w-full h-10 sm:h-[46px] bg-[#0a1a1f]/50 border border-[#d6fb00]/10 text-white rounded-xl pl-12 pr-10 text-sm font-bold focus:outline-none cursor-not-allowed transition-all duration-300">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-zinc-500 text-xs font-bold">Thn</span>
                    </div>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full bg-[#d6fb00] hover:bg-[#c2e600] text-[#0a1a1f] font-extrabold text-xs sm:text-sm uppercase tracking-wider py-4 rounded-xl transition-all duration-300 shadow-lg hover:shadow-[#d6fb00]/20 active:scale-[0.98]">
                    Pilihkan Saya Program Yang Tepat
                </button>
            </div>
        </form>
    </div>

    <div class="bg-[#0f2a30] border border-[#d6fb00]/20 rounded-[2.5rem] p-6 sm:p-10 backdrop-blur-xl shadow-2xl flex flex-col justify-between">
        
        <div class="mb-4">
          <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
              Simulasi KPR
          </h2>
      </div>

        <div class="space-y-5 bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-2xl p-5 text-xs sm:text-sm">
            
            <div>
                <h3 class="text-cyan-400 font-bold uppercase tracking-wider text-[11px] border-b border-[#d6fb00]/20 pb-1.5 mb-2.5">
                    Detail
                </h3>
                <div class="space-y-2">
                    <div class="flex justify-between font-medium text-zinc-300">
                        <span>Angsuran</span>
                        <span class="text-white font-bold">Rp. 5.390.607</span>
                    </div>
                    <div class="flex justify-between font-medium text-zinc-400">
                        <span>Pokok Hutang</span>
                        <span>Rp. 300.000.000</span>
                    </div>
                    <div class="flex justify-between font-medium text-zinc-400">
                        <span>Minimum Pendapatan</span>
                        <span>Rp. 10.781.214</span>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-cyan-400 font-bold uppercase tracking-wider text-[11px] border-b border-[#d6fb00]/20 pb-1.5 mb-2.5">
                    Biaya - Biaya Kredit
                </h3>
                <div class="space-y-2">
                    <div class="flex justify-between font-medium text-zinc-400">
                        <span>Uang Muka</span>
                        <span>Rp. 200.000.000</span>
                    </div>
                    <div class="flex justify-between font-medium text-zinc-400">
                        <span>Provisi</span>
                        <span>Rp. 3.000.000</span>
                    </div>
                    <div class="flex justify-between font-medium text-zinc-400 border-b border-[#d6fb00]/20 pb-2">
                        <span>Biaya Administrasi</span>
                        <span>Rp. 500.000</span>
                    </div>
                    <div class="flex justify-between font-bold text-[#d6fb00] pt-1 text-sm sm:text-base">
                        <span>Estimasi Total Biaya *</span>
                        <span>Rp. 203.500.000</span>
                    </div>
                </div>
            </div>

            <p class="text-[10px] text-zinc-500 italic leading-relaxed pt-1">
                * Belum termasuk biaya notaris, asuransi dan pajak (± 7% dari limit kredit)
            </p>
        </div>
      <div class="mt-4 -mb-1 w-full text-left">
          <span class="text-[#d6fb00] font-extrabold text-[11px] uppercase tracking-wider">
              Partner Bank
          </span>
      </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 items-center justify-items-center py-4 w-full">
            <a href="#" class="group w-full bg-[#0a1a1f] p-3 rounded-xl border border-[#d6fb00]/15 hover:border-[#d6fb00]/50 flex items-center justify-center min-h-[55px] shadow-md transition-all duration-300 transform hover:-translate-y-0.5">
                <img src="https://upload.wikimedia.org/wikipedia/commons/5/5c/Bank_Central_Asia.svg" alt="BCA" class="h-6 w-full object-contain brightness-0 invert opacity-70 group-hover:opacity-100 group-hover:brightness-100 group-hover:invert-0 transition-all duration-300">
            </a>
            <a href="#" class="group w-full bg-[#0a1a1f] p-3 rounded-xl border border-[#d6fb00]/15 hover:border-[#d6fb00]/50 flex items-center justify-center min-h-[55px] shadow-md transition-all duration-300 transform hover:-translate-y-0.5">
                <img src="https://upload.wikimedia.org/wikipedia/commons/6/68/BANK_BRI_logo.svg" alt="BRI" class="h-5 w-full object-contain brightness-0 invert opacity-70 group-hover:opacity-100 group-hover:brightness-100 group-hover:invert-0 transition-all duration-300">
            </a>
            <a href="#" class="group w-full bg-[#0a1a1f] p-3 rounded-xl border border-[#d6fb00]/15 hover:border-[#d6fb00]/50 flex items-center justify-center min-h-[55px] shadow-md transition-all duration-300 transform hover:-translate-y-0.5">
                <img src="https://upload.wikimedia.org/wikipedia/commons/a/ad/Bank_Mandiri_logo_2016.svg" alt="Mandiri" class="h-4 w-full object-contain brightness-0 invert opacity-70 group-hover:opacity-100 group-hover:brightness-100 group-hover:invert-0 transition-all duration-300">
            </a>
            <a href="#" class="group w-full bg-[#0a1a1f] p-3 rounded-xl border border-[#d6fb00]/15 hover:border-[#d6fb00]/50 flex items-center justify-center min-h-[55px] shadow-md transition-all duration-300 transform hover:-translate-y-0.5">
                <img src="<?=base_url('assets/img/btn.svg')?>" alt="BTN" class="h-5 w-full object-contain brightness-0 invert opacity-70 group-hover:opacity-100 group-hover:brightness-100 group-hover:invert-0 transition-all duration-300">
            </a>
        </div>

       
    </div>
</div>

<div class="w-full max-w-6xl mx-auto bg-[#0f2a30] border border-[#d6fb00]/20 rounded-[2.5rem] p-8 sm:p-12 md:py-16 text-center backdrop-blur-xl shadow-2xl z-10 relative mt-8">
    
    <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight mb-12 sm:mb-16 text-left md:text-center">
        Orang Memilih KPR Karena
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
        
        <!-- Item 1 -->
        <div class="flex flex-col items-center group bg-[#0a1a1f] p-8 rounded-3xl border border-[#d6fb00]/10 hover:border-[#d6fb00]/40 transition-all duration-300 shadow-lg hover:shadow-[#d6fb00]/10 relative overflow-hidden h-full hover:-translate-y-1 cursor-default">
            <div class="absolute inset-0 bg-gradient-to-br from-[#d6fb00]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
            <!-- Watermark Icon -->
            <div class="absolute -bottom-6 -right-4 text-[140px] text-white/[0.02] z-0 pointer-events-none group-hover:text-[#d6fb00]/10 transition-all duration-500 transform group-hover:-rotate-12">
                <i class="fa-solid fa-wallet"></i>
            </div>
            <div class="relative z-10 flex flex-col items-center h-full">
                <div class="w-16 h-16 rounded-2xl bg-[#d6fb00]/10 border border-[#d6fb00]/20 text-[#d6fb00] flex items-center justify-center text-2xl shadow-md transition-all duration-300 group-hover:scale-110 group-hover:bg-[#d6fb00] group-hover:text-[#0f2a30] group-hover:rounded-full mb-5">
                    <i class="fa-solid fa-wallet"></i>
                </div>
                <h3 class="text-white font-bold text-sm sm:text-base mb-3 transition-colors group-hover:text-[#d6fb00]">Tanpa Tunai Penuh</h3>
                <p class="text-zinc-400 text-[13px] leading-relaxed font-medium mt-auto transition-colors group-hover:text-zinc-300">
                    Wujudkan <span class="text-white font-semibold">properti impian</span> Anda seketika tanpa harus menguras tabungan untuk bayar tunai keras!
                </p>
            </div>
        </div>

        <!-- Item 2 -->
        <div class="flex flex-col items-center group bg-[#0a1a1f] p-8 rounded-3xl border border-[#d6fb00]/10 hover:border-[#d6fb00]/40 transition-all duration-300 shadow-lg hover:shadow-[#d6fb00]/10 relative overflow-hidden h-full hover:-translate-y-1 cursor-default">
            <div class="absolute inset-0 bg-gradient-to-br from-[#d6fb00]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
            <!-- Watermark Icon -->
            <div class="absolute -bottom-6 -right-4 text-[140px] text-white/[0.02] z-0 pointer-events-none group-hover:text-[#d6fb00]/10 transition-all duration-500 transform group-hover:rotate-12">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <div class="relative z-10 flex flex-col items-center h-full">
                <div class="w-16 h-16 rounded-2xl bg-[#d6fb00]/10 border border-[#d6fb00]/20 text-[#d6fb00] flex items-center justify-center text-2xl shadow-md transition-all duration-300 group-hover:scale-110 group-hover:bg-[#d6fb00] group-hover:text-[#0f2a30] group-hover:rounded-full mb-5">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
                <h3 class="text-white font-bold text-sm sm:text-base mb-3 transition-colors group-hover:text-[#d6fb00]">Cicilan Terjangkau</h3>
                <p class="text-zinc-400 text-[13px] leading-relaxed font-medium mt-auto transition-colors group-hover:text-zinc-300">
                    Beban finansial terasa <span class="text-[#d6fb00] font-semibold">sangat ringan</span> berkat skema pembayaran yang tersebar dalam jangka waktu panjang.
                </p>
            </div>
        </div>

        <!-- Item 3 -->
        <div class="flex flex-col items-center group bg-[#0a1a1f] p-8 rounded-3xl border border-[#d6fb00]/10 hover:border-[#d6fb00]/40 transition-all duration-300 shadow-lg hover:shadow-[#d6fb00]/10 relative overflow-hidden h-full hover:-translate-y-1 cursor-default">
            <div class="absolute inset-0 bg-gradient-to-br from-[#d6fb00]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
            <!-- Watermark Icon -->
            <div class="absolute -bottom-6 -right-4 text-[140px] text-white/[0.02] z-0 pointer-events-none group-hover:text-[#d6fb00]/10 transition-all duration-500 transform group-hover:-rotate-12">
                <i class="fa-solid fa-calendar-days"></i>
            </div>
            <div class="relative z-10 flex flex-col items-center h-full">
                <div class="w-16 h-16 rounded-2xl bg-[#d6fb00]/10 border border-[#d6fb00]/20 text-[#d6fb00] flex items-center justify-center text-2xl shadow-md transition-all duration-300 group-hover:scale-110 group-hover:bg-[#d6fb00] group-hover:text-[#0f2a30] group-hover:rounded-full mb-5">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <h3 class="text-white font-bold text-sm sm:text-base mb-3 transition-colors group-hover:text-[#d6fb00]">Tenor Fleksibel</h3>
                <p class="text-zinc-400 text-[13px] leading-relaxed font-medium mt-auto transition-colors group-hover:text-zinc-300">
                    Nikmati <span class="text-white font-semibold">kebebasan memilih</span> durasi cicilan yang paling nyaman dan sesuai dengan kondisi keuangan Anda.
                </p>
            </div>
        </div>

        <!-- Item 4 -->
        <div class="flex flex-col items-center group bg-[#0a1a1f] p-8 rounded-3xl border border-[#d6fb00]/10 hover:border-[#d6fb00]/40 transition-all duration-300 shadow-lg hover:shadow-[#d6fb00]/10 relative overflow-hidden h-full hover:-translate-y-1 cursor-default">
            <div class="absolute inset-0 bg-gradient-to-br from-[#d6fb00]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
            <!-- Watermark Icon -->
            <div class="absolute -bottom-6 -right-4 text-[140px] text-white/[0.02] z-0 pointer-events-none group-hover:text-[#d6fb00]/10 transition-all duration-500 transform group-hover:rotate-12">
                <i class="fa-solid fa-arrow-trend-up"></i>
            </div>
            <div class="relative z-10 flex flex-col items-center h-full">
                <div class="w-16 h-16 rounded-2xl bg-[#d6fb00]/10 border border-[#d6fb00]/20 text-[#d6fb00] flex items-center justify-center text-2xl shadow-md transition-all duration-300 group-hover:scale-110 group-hover:bg-[#d6fb00] group-hover:text-[#0f2a30] group-hover:rounded-full mb-5">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                </div>
                <h3 class="text-white font-bold text-sm sm:text-base mb-3 transition-colors group-hover:text-[#d6fb00]">Apresiasi Nilai</h3>
                <p class="text-zinc-400 text-[13px] leading-relaxed font-medium mt-auto transition-colors group-hover:text-zinc-300">
                    <span class="text-[#d6fb00] font-semibold">Investasi cerdas!</span> Miliki rumah sekaligus nikmati keuntungan dari lonjakan harga properti di masa depan.
                </p>
            </div>
        </div>

        <!-- Item 5 -->
        <div class="flex flex-col items-center group bg-[#0a1a1f] p-8 rounded-3xl border border-[#d6fb00]/10 hover:border-[#d6fb00]/40 transition-all duration-300 shadow-lg hover:shadow-[#d6fb00]/10 relative overflow-hidden h-full hover:-translate-y-1 cursor-default">
            <div class="absolute inset-0 bg-gradient-to-br from-[#d6fb00]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
            <!-- Watermark Icon -->
            <div class="absolute -bottom-6 -right-4 text-[140px] text-white/[0.02] z-0 pointer-events-none group-hover:text-[#d6fb00]/10 transition-all duration-500 transform group-hover:-rotate-12">
                <i class="fa-solid fa-house-chimney"></i>
            </div>
            <div class="relative z-10 flex flex-col items-center h-full">
                <div class="w-16 h-16 rounded-2xl bg-[#d6fb00]/10 border border-[#d6fb00]/20 text-[#d6fb00] flex items-center justify-center text-2xl shadow-md transition-all duration-300 group-hover:scale-110 group-hover:bg-[#d6fb00] group-hover:text-[#0f2a30] group-hover:rounded-full mb-5">
                    <i class="fa-solid fa-house-chimney"></i>
                </div>
                <h3 class="text-white font-bold text-sm sm:text-base mb-3 transition-colors group-hover:text-[#d6fb00]">Huni Lebih Cepat</h3>
                <p class="text-zinc-400 text-[13px] leading-relaxed font-medium mt-auto transition-colors group-hover:text-zinc-300">
                    Jangan tunda kebahagiaan. Anda dan keluarga bisa <span class="text-white font-semibold">langsung menempati rumah</span> tanpa menunggu bertahun-tahun.
                </p>
            </div>
        </div>

        <!-- Item 6 -->
        <div class="flex flex-col items-center group bg-[#0a1a1f] p-8 rounded-3xl border border-[#d6fb00]/10 hover:border-[#d6fb00]/40 transition-all duration-300 shadow-lg hover:shadow-[#d6fb00]/10 relative overflow-hidden h-full hover:-translate-y-1 cursor-default">
            <div class="absolute inset-0 bg-gradient-to-br from-[#d6fb00]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
            <!-- Watermark Icon -->
            <div class="absolute -bottom-6 -right-4 text-[140px] text-white/[0.02] z-0 pointer-events-none group-hover:text-[#d6fb00]/10 transition-all duration-500 transform group-hover:rotate-12">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div class="relative z-10 flex flex-col items-center h-full">
                <div class="w-16 h-16 rounded-2xl bg-[#d6fb00]/10 border border-[#d6fb00]/20 text-[#d6fb00] flex items-center justify-center text-2xl shadow-md transition-all duration-300 group-hover:scale-110 group-hover:bg-[#d6fb00] group-hover:text-[#0f2a30] group-hover:rounded-full mb-5">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3 class="text-white font-bold text-sm sm:text-base mb-3 transition-colors group-hover:text-[#d6fb00]">Legalitas Aman</h3>
                <p class="text-zinc-400 text-[13px] leading-relaxed font-medium mt-auto transition-colors group-hover:text-zinc-300">
                    Tidur nyenyak tanpa khawatir. Bank menjamin seluruh sertifikat dan <span class="text-[#d6fb00] font-semibold">legalitas kepemilikan Anda 100% aman!</span>
                </p>
            </div>
        </div>

    </div>
</div>
<div class="w-full max-w-6xl mx-auto bg-[#0f2a30] border border-[#d6fb00]/20 rounded-[2.5rem] p-6 sm:p-10 md:py-14 text-left backdrop-blur-xl shadow-2xl z-10 relative mt-8">
    
    <div class="mb-10 md:mb-12">
        <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight mb-2">
            Prosedur Pengajuan KPR
        </h2>
        <p class="text-zinc-400 text-xs sm:text-sm font-medium">
            Ikuti langkah-langkah berikut untuk mengajukan KPR dengan mudah.
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
        
        <!-- Step 1 -->
        <div class="flex flex-col group bg-[#0a1a1f] p-6 rounded-2xl border border-[#d6fb00]/10 hover:border-[#d6fb00]/40 transition-all duration-300 shadow-lg hover:shadow-[#d6fb00]/10 relative overflow-hidden h-full hover:-translate-y-1 cursor-default">
            <div class="absolute inset-0 bg-gradient-to-br from-[#d6fb00]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
            
            <!-- Watermark Number -->
            <div class="absolute -bottom-8 -right-4 text-[120px] font-black text-white/[0.02] z-0 pointer-events-none group-hover:text-[#d6fb00]/10 transition-colors duration-500">1</div>
            
            <div class="relative z-10 flex flex-col h-full">
                <div class="flex items-center gap-4 mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-[#d6fb00]/10 border border-[#d6fb00]/20 text-[#d6fb00] flex items-center justify-center font-black text-lg shadow-md transition-all duration-300 group-hover:scale-110 group-hover:bg-[#d6fb00] group-hover:text-[#0f2a30]">1</div>
                    <h3 class="text-white font-bold text-sm leading-tight transition-colors group-hover:text-[#d6fb00]">Pilih Bank Impian</h3>
                </div>
                <p class="text-zinc-400 text-[13px] leading-relaxed font-medium mt-auto">
                    Temukan bank atau lembaga keuangan yang menawarkan <span class="text-white font-semibold">kondisi KPR paling menguntungkan</span> untuk Anda!
                </p>
            </div>
        </div>

        <!-- Step 2 -->
        <div class="flex flex-col group bg-[#0a1a1f] p-6 rounded-2xl border border-[#d6fb00]/10 hover:border-[#d6fb00]/40 transition-all duration-300 shadow-lg hover:shadow-[#d6fb00]/10 relative overflow-hidden h-full hover:-translate-y-1 cursor-default">
            <div class="absolute inset-0 bg-gradient-to-br from-[#d6fb00]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
            <div class="absolute -bottom-8 -right-4 text-[120px] font-black text-white/[0.02] z-0 pointer-events-none group-hover:text-[#d6fb00]/10 transition-colors duration-500">2</div>
            <div class="relative z-10 flex flex-col h-full">
                <div class="flex items-center gap-4 mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-[#d6fb00]/10 border border-[#d6fb00]/20 text-[#d6fb00] flex items-center justify-center font-black text-lg shadow-md transition-all duration-300 group-hover:scale-110 group-hover:bg-[#d6fb00] group-hover:text-[#0f2a30]">2</div>
                    <h3 class="text-white font-bold text-sm leading-tight transition-colors group-hover:text-[#d6fb00]">Siapkan Berkas</h3>
                </div>
                <p class="text-zinc-400 text-[13px] leading-relaxed font-medium mt-auto">
                    Lengkapi <span class="text-white font-semibold">seluruh dokumen persyaratan</span> agar proses pengajuan Anda berjalan secepat kilat.
                </p>
            </div>
        </div>

        <!-- Step 3 -->
        <div class="flex flex-col group bg-[#0a1a1f] p-6 rounded-2xl border border-[#d6fb00]/10 hover:border-[#d6fb00]/40 transition-all duration-300 shadow-lg hover:shadow-[#d6fb00]/10 relative overflow-hidden h-full hover:-translate-y-1 cursor-default">
            <div class="absolute inset-0 bg-gradient-to-br from-[#d6fb00]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
            <div class="absolute -bottom-8 -right-4 text-[120px] font-black text-white/[0.02] z-0 pointer-events-none group-hover:text-[#d6fb00]/10 transition-colors duration-500">3</div>
            <div class="relative z-10 flex flex-col h-full">
                <div class="flex items-center gap-4 mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-[#d6fb00]/10 border border-[#d6fb00]/20 text-[#d6fb00] flex items-center justify-center font-black text-lg shadow-md transition-all duration-300 group-hover:scale-110 group-hover:bg-[#d6fb00] group-hover:text-[#0f2a30]">3</div>
                    <h3 class="text-white font-bold text-sm leading-tight transition-colors group-hover:text-[#d6fb00]">Isi Formulir</h3>
                </div>
                <p class="text-zinc-400 text-[13px] leading-relaxed font-medium mt-auto">
                    Isi formulir pengajuan KPR dengan <span class="text-white font-semibold">data diri yang akurat</span> untuk mempercepat persetujuan.
                </p>
            </div>
        </div>

        <!-- Step 4 -->
        <div class="flex flex-col group bg-[#0a1a1f] p-6 rounded-2xl border border-[#d6fb00]/10 hover:border-[#d6fb00]/40 transition-all duration-300 shadow-lg hover:shadow-[#d6fb00]/10 relative overflow-hidden h-full hover:-translate-y-1 cursor-default">
            <div class="absolute inset-0 bg-gradient-to-br from-[#d6fb00]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
            <div class="absolute -bottom-8 -right-4 text-[120px] font-black text-white/[0.02] z-0 pointer-events-none group-hover:text-[#d6fb00]/10 transition-colors duration-500">4</div>
            <div class="relative z-10 flex flex-col h-full">
                <div class="flex items-center gap-4 mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-[#d6fb00]/10 border border-[#d6fb00]/20 text-[#d6fb00] flex items-center justify-center font-black text-lg shadow-md transition-all duration-300 group-hover:scale-110 group-hover:bg-[#d6fb00] group-hover:text-[#0f2a30]">4</div>
                    <h3 class="text-white font-bold text-sm leading-tight transition-colors group-hover:text-[#d6fb00]">Validasi Kelayakan</h3>
                </div>
                <p class="text-zinc-400 text-[13px] leading-relaxed font-medium mt-auto">
                    Bank akan memproses dan <span class="text-white font-semibold">memvalidasi riwayat keuangan</span> Anda. Tetap tenang dan tunggu kabar baik!
                </p>
            </div>
        </div>

        <!-- Step 5 -->
        <div class="flex flex-col group bg-[#0a1a1f] p-6 rounded-2xl border border-[#d6fb00]/10 hover:border-[#d6fb00]/40 transition-all duration-300 shadow-lg hover:shadow-[#d6fb00]/10 relative overflow-hidden h-full hover:-translate-y-1 cursor-default">
            <div class="absolute inset-0 bg-gradient-to-br from-[#d6fb00]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
            <div class="absolute -bottom-8 -right-4 text-[120px] font-black text-white/[0.02] z-0 pointer-events-none group-hover:text-[#d6fb00]/10 transition-colors duration-500">5</div>
            <div class="relative z-10 flex flex-col h-full">
                <div class="flex items-center gap-4 mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-[#d6fb00]/10 border border-[#d6fb00]/20 text-[#d6fb00] flex items-center justify-center font-black text-lg shadow-md transition-all duration-300 group-hover:scale-110 group-hover:bg-[#d6fb00] group-hover:text-[#0f2a30]">5</div>
                    <h3 class="text-white font-bold text-sm leading-tight transition-colors group-hover:text-[#d6fb00]">Penilaian Properti</h3>
                </div>
                <p class="text-zinc-400 text-[13px] leading-relaxed font-medium mt-auto">
                    Tim penilai (appraisal) independen akan turun tangan untuk <span class="text-white font-semibold">mensurvei properti</span> idaman Anda.
                </p>
            </div>
        </div>

        <!-- Step 6 -->
        <div class="flex flex-col group bg-[#0a1a1f] p-6 rounded-2xl border border-[#d6fb00]/10 hover:border-[#d6fb00]/40 transition-all duration-300 shadow-lg hover:shadow-[#d6fb00]/10 relative overflow-hidden h-full hover:-translate-y-1 cursor-default">
            <div class="absolute inset-0 bg-gradient-to-br from-[#d6fb00]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
            <div class="absolute -bottom-8 -right-4 text-[120px] font-black text-white/[0.02] z-0 pointer-events-none group-hover:text-[#d6fb00]/10 transition-colors duration-500">6</div>
            <div class="relative z-10 flex flex-col h-full">
                <div class="flex items-center gap-4 mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-[#d6fb00]/10 border border-[#d6fb00]/20 text-[#d6fb00] flex items-center justify-center font-black text-lg shadow-md transition-all duration-300 group-hover:scale-110 group-hover:bg-[#d6fb00] group-hover:text-[#0f2a30]">6</div>
                    <h3 class="text-white font-bold text-sm leading-tight transition-colors group-hover:text-[#d6fb00]">Tanda Tangan Akta</h3>
                </div>
                <p class="text-zinc-400 text-[13px] leading-relaxed font-medium mt-auto">
                    Selamat! Anda akan diundang untuk <span class="text-[#d6fb00] font-semibold">menandatangani akta kredit</span> sebagai langkah akhir kesepakatan.
                </p>
            </div>
        </div>

        <!-- Step 7 -->
        <div class="flex flex-col group bg-[#0a1a1f] p-6 rounded-2xl border border-[#d6fb00]/10 hover:border-[#d6fb00]/40 transition-all duration-300 shadow-lg hover:shadow-[#d6fb00]/10 relative overflow-hidden h-full hover:-translate-y-1 cursor-default">
            <div class="absolute inset-0 bg-gradient-to-br from-[#d6fb00]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
            <div class="absolute -bottom-8 -right-4 text-[120px] font-black text-white/[0.02] z-0 pointer-events-none group-hover:text-[#d6fb00]/10 transition-colors duration-500">7</div>
            <div class="relative z-10 flex flex-col h-full">
                <div class="flex items-center gap-4 mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-[#d6fb00]/10 border border-[#d6fb00]/20 text-[#d6fb00] flex items-center justify-center font-black text-lg shadow-md transition-all duration-300 group-hover:scale-110 group-hover:bg-[#d6fb00] group-hover:text-[#0f2a30]">7</div>
                    <h3 class="text-white font-bold text-sm leading-tight transition-colors group-hover:text-[#d6fb00]">Biaya Administrasi</h3>
                </div>
                <p class="text-zinc-400 text-[13px] leading-relaxed font-medium mt-auto">
                    Tuntaskan pembayaran <span class="text-white font-semibold">administrasi, notaris, & pajak</span> agar dana bisa segera diturunkan.
                </p>
            </div>
        </div>

        <!-- Step 8 -->
        <div class="flex flex-col group bg-[#0a1a1f] p-6 rounded-2xl border border-[#d6fb00]/10 hover:border-[#d6fb00]/40 transition-all duration-300 shadow-lg hover:shadow-[#d6fb00]/10 relative overflow-hidden h-full hover:-translate-y-1 cursor-default">
            <div class="absolute inset-0 bg-gradient-to-br from-[#d6fb00]/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>
            <div class="absolute -bottom-8 -right-4 text-[120px] font-black text-white/[0.02] z-0 pointer-events-none group-hover:text-[#d6fb00]/10 transition-colors duration-500">8</div>
            <div class="relative z-10 flex flex-col h-full">
                <div class="flex items-center gap-4 mb-4">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-[#d6fb00]/10 border border-[#d6fb00]/20 text-[#d6fb00] flex items-center justify-center font-black text-lg shadow-md transition-all duration-300 group-hover:scale-110 group-hover:bg-[#d6fb00] group-hover:text-[#0f2a30]">8</div>
                    <h3 class="text-white font-bold text-sm leading-tight transition-colors group-hover:text-[#d6fb00]">Serah Terima Kunci!</h3>
                </div>
                <p class="text-zinc-400 text-[13px] leading-relaxed font-medium mt-auto">
                    Bank mencairkan dana ke pihak developer, dan <span class="text-[#d6fb00] font-bold">rumah impian resmi menjadi milik Anda!</span>
                </p>
            </div>
        </div>

    </div>

    <!-- Enhanced Back Button -->
    <div class="flex justify-center mt-14 pt-8 border-t border-[#d6fb00]/10">
        <a href="<?= base_url('umum') ?>" class="group flex items-center gap-3 bg-[#0a1a1f] hover:bg-[#d6fb00]/10 border border-[#d6fb00]/30 hover:border-[#d6fb00] text-zinc-300 hover:text-[#d6fb00] px-8 py-3.5 rounded-xl text-sm font-bold transition-all duration-300 shadow-lg">
            <i class="fa-solid fa-arrow-left transition-transform group-hover:-translate-x-1"></i>
            <span>Kembali ke Layanan Utama</span>
        </a>
    </div>
</div>

    </div>
</section>

