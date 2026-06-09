<!-- application/views/pages/kpr/simulasi.php -->
<main class="min-h-screen pt-24 pb-16  relative overflow-hidden font-outfit">
    <!-- Background Ornaments -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <!-- Batik Pattern Overlay -->
        
        
        <div class="absolute top-[-10%] right-[-5%] w-[600px] h-[600px] rounded-full bg-[#d6fb00]/5 blur-[120px]"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[500px] h-[500px] rounded-full bg-[#00a3b5]/5 blur-[100px]"></div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        <!-- Header -->
        <div class="mb-10 text-center animate-fade-in-up">
            <h1 class="text-3xl md:text-5xl font-black text-white tracking-tight mb-4">
                Simulasi <span class="text-[#d6fb00]">KPR</span>
            </h1>
            <p class="text-[#8aacb0] text-sm md:text-base max-w-2xl mx-auto leading-relaxed">
                Hitung estimasi cicilan Kredit Pemilikan Rumah (KPR) Anda di sini. Kalkulator ini membantu merencanakan keuangan Anda sebelum memutuskan untuk membeli rumah idaman.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start animate-fade-in-up" style="animation-delay: 0.1s;">
            
            <!-- Left: Calculator Inputs -->
            <div class="lg:col-span-7 bg-[#0a1a1f] border border-white/10 rounded-3xl p-6 sm:p-8 backdrop-blur-xl shadow-2xl relative transition-all duration-300">
                
                <h2 class="text-xl font-bold text-white mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-calculator text-[#d6fb00]"></i> Form Simulasi
                </h2>

                <form id="kpr-form" class="space-y-6" onsubmit="event.preventDefault(); hitungKPR();">
                    
                    <!-- Harga Properti -->
                    <div>
                        <label class="block text-[#8aacb0] font-bold text-xs uppercase tracking-widest mb-2">Harga Properti (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-zinc-400 font-bold">Rp</span>
                            <input type="text" id="harga_properti" placeholder="Cth: 300.000.000" onkeyup="formatRupiahInput(this); hitungKPR();" required
                                   class="w-full h-12 bg-white/5 border border-white/10 text-white rounded-xl pl-12 pr-4 font-bold placeholder-zinc-600 focus:outline-none focus:border-[#d6fb00]/50 focus:bg-white/10 transition-all">
                        </div>
                    </div>

                    <!-- Uang Muka (DP) -->
                    <div>
                        <label class="block text-[#8aacb0] font-bold text-xs uppercase tracking-widest mb-2">Uang Muka / DP (Rp)</label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="sm:col-span-2 relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-zinc-400 font-bold">Rp</span>
                                <input type="text" id="uang_muka" placeholder="Cth: 30.000.000" onkeyup="formatRupiahInput(this); hitungKPR(); updateDPPercent();" required
                                       class="w-full h-12 bg-white/5 border border-white/10 text-white rounded-xl pl-12 pr-4 font-bold placeholder-zinc-600 focus:outline-none focus:border-[#d6fb00]/50 focus:bg-white/10 transition-all">
                            </div>
                            <div class="relative">
                                <input type="number" id="uang_muka_persen" placeholder="10" onkeyup="updateDPNominal(); hitungKPR();" onchange="updateDPNominal(); hitungKPR();" min="0" max="100"
                                       class="w-full h-12 bg-white/5 border border-white/10 text-white rounded-xl pl-4 pr-10 font-bold placeholder-zinc-600 focus:outline-none focus:border-[#d6fb00]/50 focus:bg-white/10 transition-all text-center">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-zinc-400 font-bold">%</span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <!-- Suku Bunga -->
                        <div>
                            <label class="block text-[#8aacb0] font-bold text-xs uppercase tracking-widest mb-2">Suku Bunga</label>
                            <div class="relative">
                                <input type="number" id="suku_bunga" placeholder="Cth: 5.0" step="0.1" onkeyup="hitungKPR();" onchange="hitungKPR();" required
                                       class="w-full h-12 bg-white/5 border border-white/10 text-white rounded-xl pl-4 pr-16 font-bold placeholder-zinc-600 focus:outline-none focus:border-[#d6fb00]/50 focus:bg-white/10 transition-all">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-zinc-400 font-bold">% / Thn</span>
                            </div>
                        </div>

                        <!-- Tenor -->
                        <div>
                            <label class="block text-[#8aacb0] font-bold text-xs uppercase tracking-widest mb-2">Tenor / Jangka Waktu</label>
                            <div class="relative">
                                <input type="number" id="tenor" placeholder="Cth: 15" onkeyup="hitungKPR();" onchange="hitungKPR();" required
                                       class="w-full h-12 bg-white/5 border border-white/10 text-white rounded-xl pl-4 pr-16 font-bold placeholder-zinc-600 focus:outline-none focus:border-[#d6fb00]/50 focus:bg-white/10 transition-all">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-zinc-400 font-bold">Tahun</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Presets -->
                    <div class="pt-2">
                        <label class="block text-zinc-500 font-medium text-[10px] uppercase tracking-widest mb-2">Pilih Tipe Rumah Secara Cepat:</label>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" onclick="setPreset(185000000, 10, 5, 15)" class="px-4 py-1.5 rounded-lg border border-[#00a3b5]/30 text-[#00a3b5] text-xs font-bold hover:bg-[#00a3b5]/10 transition-colors">
                                Subsidi (Rp 185 Juta)
                            </button>
                            <button type="button" onclick="setPreset(350000000, 15, 7.5, 20)" class="px-4 py-1.5 rounded-lg border border-[#d6fb00]/30 text-[#d6fb00] text-xs font-bold hover:bg-[#d6fb00]/10 transition-colors">
                                Komersial Menengah
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Right: Results Panel -->
            <div class="lg:col-span-5 bg-[#0a1a1f] border border-white/10 rounded-3xl p-6 sm:p-8 backdrop-blur-xl shadow-2xl flex flex-col justify-between h-full relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-[#d6fb00]/5 rounded-full blur-[80px] pointer-events-none"></div>
                <div class="relative z-10">
                    <div class="mb-6">
                        <h2 class="text-2xl font-black text-white tracking-tight mb-1">Hasil Simulasi</h2>
                        <p class="text-[#8aacb0] text-xs">Estimasi rincian pembiayaan KPR Anda</p>
                    </div>

                    <div class="space-y-4">
                        <!-- Angsuran Utama -->
                        <div class="bg-white/5 border border-white/10 rounded-2xl p-5 relative overflow-hidden">
                            <div class="absolute right-0 top-0 w-24 h-24 bg-[#d6fb00]/10 rounded-bl-full pointer-events-none"></div>
                            <span class="block text-zinc-400 font-semibold text-xs uppercase tracking-wider mb-1">Angsuran Per Bulan</span>
                            <span id="hasil_angsuran" class="block text-3xl font-black text-[#d6fb00] tracking-tight">Rp 0</span>
                        </div>

                        <!-- Breakdown Details -->
                        <div class="bg-black/20 border border-white/5 rounded-2xl p-5 space-y-3">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-zinc-400 font-medium">Pokok Pinjaman</span>
                                <span id="hasil_pokok" class="text-white font-bold">Rp 0</span>
                            </div>
                            <div class="w-full h-px bg-white/5"></div>
                            
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-zinc-400 font-medium">Total Pembayaran (Termasuk Bunga)</span>
                                <span id="hasil_total_bayar" class="text-white font-bold">Rp 0</span>
                            </div>
                            <div class="w-full h-px bg-white/5"></div>

                            <div class="flex justify-between items-center text-sm">
                                <span class="text-zinc-400 font-medium">Saran Gaji Minimal per Bulan</span>
                                <span id="hasil_gaji" class="text-[#00a3b5] font-bold">Rp 0</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-white/10 relative z-10">
                    <p class="text-[10px] text-zinc-500 italic leading-relaxed text-center">
                        * Simulasi ini merupakan estimasi awal. Kalkulasi riil dapat berbeda tergantung kebijakan masing-masing Bank penyedia KPR (seperti asuransi, provisi, biaya notaris, dan pajak).
                    </p>
                </div>
            </div>

        </div>

        <!-- Bank Partners Section -->
        <div class="mt-16 text-center animate-fade-in-up" style="animation-delay: 0.2s;">
            <p class="text-[#8aacb0] text-xs font-bold uppercase tracking-widest mb-6">Mitra Perbankan KPR Kami</p>
            <div class="flex flex-wrap justify-center items-center gap-6 opacity-70">
                <img src="https://upload.wikimedia.org/wikipedia/commons/5/5c/Bank_Central_Asia.svg" alt="BCA" class="h-6 object-contain brightness-0 invert hover:brightness-100 hover:invert-0 transition-all duration-300">
                <img src="https://upload.wikimedia.org/wikipedia/commons/6/68/BANK_BRI_logo.svg" alt="BRI" class="h-5 object-contain brightness-0 invert hover:brightness-100 hover:invert-0 transition-all duration-300">
                <img src="https://upload.wikimedia.org/wikipedia/commons/a/ad/Bank_Mandiri_logo_2016.svg" alt="Mandiri" class="h-4 object-contain brightness-0 invert hover:brightness-100 hover:invert-0 transition-all duration-300">
                <img src="<?= base_url('assets/img/btn.svg') ?>" alt="BTN" class="h-5 object-contain brightness-0 invert hover:brightness-100 hover:invert-0 transition-all duration-300">
            </div>
        </div>

    </div>
</main>

<script>
// Format value ke Rupiah (Ribuan) saat diketik
function formatRupiahInput(input) {
    let value = input.value.replace(/[^,\d]/g, '').toString();
    let split = value.split(',');
    let sisa = split[0].length % 3;
    let rupiah = split[0].substr(0, sisa);
    let ribuan = split[0].substr(sisa).match(/\d{3}/gi);

    if (ribuan) {
        let separator = sisa ? '.' : '';
        rupiah += separator + ribuan.join('.');
    }

    rupiah = split[1] != undefined ? rupiah + ',' + split[1] : rupiah;
    input.value = rupiah;
}

// Convert format Rupiah ke Angka Murni (Integer)
function getIntFromRupiah(rupiahStr) {
    if (!rupiahStr) return 0;
    return parseInt(rupiahStr.replace(/[^0-9]/g, ''), 10) || 0;
}

// Format Angka Murni ke Format Rupiah Display
function formatRupiahDisplay(angka) {
    return 'Rp ' + Math.round(angka).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

// Update Nominal DP jika Persentase Berubah
function updateDPNominal() {
    let harga = getIntFromRupiah(document.getElementById('harga_properti').value);
    let persen = parseFloat(document.getElementById('uang_muka_persen').value) || 0;
    
    if (harga > 0) {
        let nominal = (harga * persen) / 100;
        let inputNominal = document.getElementById('uang_muka');
        inputNominal.value = Math.round(nominal).toString();
        formatRupiahInput(inputNominal);
    }
}

// Update Persentase DP jika Nominal Berubah
function updateDPPercent() {
    let harga = getIntFromRupiah(document.getElementById('harga_properti').value);
    let nominal = getIntFromRupiah(document.getElementById('uang_muka').value);
    
    if (harga > 0) {
        let persen = (nominal / harga) * 100;
        document.getElementById('uang_muka_persen').value = persen.toFixed(1).replace(/\.0$/, '');
    }
}

// Logic Kalkulasi KPR (Metode Efektif / Anuitas standar bank)
function hitungKPR() {
    let harga = getIntFromRupiah(document.getElementById('harga_properti').value);
    let dp = getIntFromRupiah(document.getElementById('uang_muka').value);
    let bungaTahunan = parseFloat(document.getElementById('suku_bunga').value) || 0;
    let tenorTahun = parseInt(document.getElementById('tenor').value) || 0;

    let pokokPinjaman = harga - dp;
    
    // Fallback jika belum lengkap
    if (harga <= 0 || tenorTahun <= 0 || pokokPinjaman <= 0) {
        document.getElementById('hasil_angsuran').innerText = "Rp 0";
        document.getElementById('hasil_pokok').innerText = "Rp 0";
        document.getElementById('hasil_total_bayar').innerText = "Rp 0";
        document.getElementById('hasil_gaji').innerText = "Rp 0";
        return;
    }

    let tenorBulan = tenorTahun * 12;
    let bungaBulanan = (bungaTahunan / 100) / 12;
    let angsuranPerBulan = 0;

    if (bungaTahunan > 0) {
        // Rumus Anuitas: P * (r(1+r)^n) / ((1+r)^n - 1)
        angsuranPerBulan = pokokPinjaman * (bungaBulanan * Math.pow(1 + bungaBulanan, tenorBulan)) / (Math.pow(1 + bungaBulanan, tenorBulan) - 1);
    } else {
        // Jika bunga 0%
        angsuranPerBulan = pokokPinjaman / tenorBulan;
    }

    let totalPembayaran = angsuranPerBulan * tenorBulan;
    let saranGaji = angsuranPerBulan / 0.3; // Asumsi cicilan maksimal 30% dari gaji

    // Update UI
    document.getElementById('hasil_angsuran').innerText = formatRupiahDisplay(angsuranPerBulan);
    document.getElementById('hasil_pokok').innerText = formatRupiahDisplay(pokokPinjaman);
    document.getElementById('hasil_total_bayar').innerText = formatRupiahDisplay(totalPembayaran);
    document.getElementById('hasil_gaji').innerText = formatRupiahDisplay(saranGaji);
}

// Set Preset Values
function setPreset(harga, persenDp, bunga, tenor) {
    let inputHarga = document.getElementById('harga_properti');
    inputHarga.value = harga.toString();
    formatRupiahInput(inputHarga);

    document.getElementById('uang_muka_persen').value = persenDp;
    updateDPNominal();

    document.getElementById('suku_bunga').value = bunga;
    document.getElementById('tenor').value = tenor;

    hitungKPR();
}

// Inisialisasi format saat pertama load jika ada value
document.addEventListener('DOMContentLoaded', () => {
    // Set default preset saat halaman dimuat (opsional)
    setPreset(185000000, 5, 5, 15); // Preset KPR Subsidi
});
</script>
