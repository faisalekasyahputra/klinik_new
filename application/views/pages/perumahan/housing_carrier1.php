<section class="w-full max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-8 items-stretch z-10 relative">
    
    <div class="bg-[#0b0c10]/75 border border-zinc-800/50 rounded-[2.5rem] p-6 sm:p-10 backdrop-blur-xl shadow-2xl flex flex-col justify-between">
        
        <div class="text-center mb-8">
            <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                Housing Carrier
            </h2>
        </div>

        <form action="#" method="POST" class="space-y-4">
            <div>
                <label class="block text-[#d6fb00] font-extrabold text-[11px] uppercase tracking-wider mb-2">Sumber Pendapatan (Tetap/Tidak Tetap)</label>
                <input type="text" placeholder="Contoh: Tetap" class="w-full bg-zinc-100 border border-zinc-200 text-zinc-900 rounded-xl px-4 py-3 text-sm font-semibold placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40">
            </div>

            <div>
                <label class="block text-[#d6fb00] font-extrabold text-[11px] uppercase tracking-wider mb-2">Penghasilan (Rp. XXX)</label>
                <input type="number" placeholder="Masukkan nominal angka" class="w-full bg-zinc-100 border border-zinc-200 text-zinc-900 rounded-xl px-4 py-3 text-sm font-semibold placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40">
            </div>

            <div>
                <label class="block text-[#d6fb00] font-extrabold text-[11px] uppercase tracking-wider mb-2">Punya Lahan?</label>
                <select class="w-full bg-zinc-100 border border-zinc-200 text-zinc-900 rounded-xl px-4 py-3 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 appearance-none">
                    <option value="" disabled selected>Pilih Jawaban</option>
                    <option value="ya">Ya, Sudah Punya</option>
                    <option value="tidak">Tidak Ada</option>
                </select>
            </div>

            <div>
                <label class="block text-[#d6fb00] font-extrabold text-[11px] uppercase tracking-wider mb-2">Jumlah Anggota Keluarga</label>
                <input type="text" placeholder="Contoh: 4" class="w-full bg-zinc-100 border border-zinc-200 text-zinc-900 rounded-xl px-4 py-3 text-sm font-semibold placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40">
            </div>

            <div>
                <label class="block text-[#d6fb00] font-extrabold text-[11px] uppercase tracking-wider mb-2">Usia?</label>
                <input type="number" placeholder="Masukkan usia Anda" class="w-full bg-zinc-100 border border-zinc-200 text-zinc-900 rounded-xl px-4 py-3 text-sm font-semibold placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40">
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full bg-[#d6fb00] hover:bg-[#c2e600] text-[#0a1a1f] font-extrabold text-xs sm:text-sm uppercase tracking-wider py-4 rounded-xl transition-all duration-300 shadow-lg hover:shadow-[#d6fb00]/20 active:scale-[0.98]">
                    Pilihkan Saya Program Yang Tepat
                </button>
            </div>
        </form>
    </div>

    <div class="bg-[#0b0c10]/75 border border-zinc-800/50 rounded-[2.5rem] p-6 sm:p-10 backdrop-blur-xl shadow-2xl flex flex-col justify-between">
        
        <div class="mb-4">
          <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
              Simulasi KPR
          </h2>
      </div>

        <div class="space-y-5 bg-[#d6fb00]/5 border border-zinc-800/80 rounded-2xl p-5 text-xs sm:text-sm">
            
            <div>
                <h3 class="text-cyan-400 font-bold uppercase tracking-wider text-[11px] border-b border-zinc-800 pb-1.5 mb-2.5">
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
                <h3 class="text-cyan-400 font-bold uppercase tracking-wider text-[11px] border-b border-zinc-800 pb-1.5 mb-2.5">
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
                    <div class="flex justify-between font-medium text-zinc-400 border-b border-zinc-800/60 pb-2">
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
            <a href="#" class="group w-full bg-gradient-to-br from-[#d6fb00] via-amber-400 to-amber-500 p-3 rounded-xl border border-[#d6fb00]/15 flex items-center justify-center min-h-[55px] shadow-md transition-all duration-300 transform hover:-translate-y-0.5">
                <img src="https://upload.wikimedia.org/wikipedia/commons/5/5c/Bank_Central_Asia.svg" alt="BCA" class="h-6 w-full object-contain">
            </a>
            <a href="#" class="group w-full bg-gradient-to-br from-[#d6fb00] via-amber-400 to-amber-500 p-3 rounded-xl border border-[#d6fb00]/15 flex items-center justify-center min-h-[55px] shadow-md transition-all duration-300 transform hover:-translate-y-0.5">
                <img src="https://upload.wikimedia.org/wikipedia/commons/6/68/BANK_BRI_logo.svg" alt="BRI" class="h-5 w-full object-contain">
            </a>
            <a href="#" class="group w-full bg-gradient-to-br from-[#d6fb00] via-amber-400 to-amber-500 p-3 rounded-xl border border-[#d6fb00]/15 flex items-center justify-center min-h-[55px] shadow-md transition-all duration-300 transform hover:-translate-y-0.5">
                <img src="https://upload.wikimedia.org/wikipedia/commons/a/ad/Bank_Mandiri_logo_2016.svg" alt="Mandiri" class="h-4 w-full object-contain">
            </a>
            <a href="#" class="group w-full bg-gradient-to-br from-[#d6fb00] via-amber-400 to-amber-500 p-3 rounded-xl border border-[#d6fb00]/15 flex items-center justify-center min-h-[55px] shadow-md transition-all duration-300 transform hover:-translate-y-0.5">
                <img src="<?=base_url('assets/img/btn.svg')?>" alt="BTN" class="h-5 w-full object-contain">
            </a>
        </div>

       
    </div>

</section>
<section class="w-full max-w-6xl mx-auto bg-[#0b0c10]/75 border border-zinc-800/50 rounded-[2.5rem] p-8 sm:p-12 md:py-16 text-center backdrop-blur-xl shadow-2xl z-10 relative mt-8">
    
    <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight mb-12 sm:mb-16 text-left md:text-center">
        Orang Memilih KPR Karena
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-y-12 gap-x-8 text-center">
        
        <div class="flex flex-col items-center group">
            <div class="w-16 h-16 rounded-full bg-[#005e6a] border border-[#005e6a]/30 text-white flex items-center justify-center text-2xl shadow-lg transition-transform duration-300 group-hover:scale-110 group-hover:shadow-[#005e6a]/20 mb-5">
                <i class="fa-solid fa-border-all"></i>
            </div>
            <p class="text-zinc-300 text-xs sm:text-sm leading-relaxed font-medium max-w-xs transition-colors group-hover:text-white">
                Memungkinkan untuk memiliki properti tanpa harus membayar secara tunai penuh saat pembelian
            </p>
        </div>

        <div class="flex flex-col items-center group">
            <div class="w-16 h-16 rounded-full bg-[#005e6a] border border-[#005e6a]/30 text-white flex items-center justify-center text-2xl shadow-lg transition-transform duration-300 group-hover:scale-110 group-hover:shadow-[#005e6a]/20 mb-5">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <p class="text-zinc-300 text-xs sm:text-sm leading-relaxed font-medium max-w-xs transition-colors group-hover:text-white">
                Cicilan terjangkau dengan pembayaran yang tersebar dalam jangka waktu yang panjang
            </p>
        </div>

        <div class="flex flex-col items-center group">
            <div class="w-16 h-16 rounded-full bg-[#005e6a] border border-[#005e6a]/30 text-white flex items-center justify-center text-2xl shadow-lg transition-transform duration-300 group-hover:scale-110 group-hover:shadow-[#005e6a]/20 mb-5">
                <i class="fa-solid fa-code-branch"></i>
            </div>
            <p class="text-zinc-300 text-xs sm:text-sm leading-relaxed font-medium max-w-xs transition-colors group-hover:text-white">
                Menawarkan pilihan tenor yang beragam sesuai dengan kemampuan keuangan peminjam
            </p>
        </div>

        <div class="flex flex-col items-center group">
            <div class="w-16 h-16 rounded-full bg-[#005e6a] border border-[#005e6a]/30 text-white flex items-center justify-center text-2xl shadow-lg transition-transform duration-300 group-hover:scale-110 group-hover:shadow-[#005e6a]/20 mb-5">
                <i class="fa-solid fa-circle-plus"></i>
            </div>
            <p class="text-zinc-300 text-xs sm:text-sm leading-relaxed font-medium max-w-xs transition-colors group-hover:text-white">
                Miliki rumah serta memanfaatkan potensi apresiasi nilai properti di masa mendatang
            </p>
        </div>

        <div class="flex flex-col items-center group">
            <div class="w-16 h-16 rounded-full bg-[#005e6a] border border-[#005e6a]/30 text-white flex items-center justify-center text-2xl shadow-lg transition-transform duration-300 group-hover:scale-110 group-hover:shadow-[#005e6a]/20 mb-5">
                <i class="fa-solid fa-house-chimney"></i>
            </div>
            <p class="text-zinc-300 text-xs sm:text-sm leading-relaxed font-medium max-w-xs transition-colors group-hover:text-white">
                Memungkinkan peminjam untuk memiliki rumah lebih cepat
            </p>
        </div>

        <div class="flex flex-col items-center group">
            <div class="w-16 h-16 rounded-full bg-[#005e6a] border border-[#005e6a]/30 text-white flex items-center justify-center text-2xl shadow-lg transition-transform duration-300 group-hover:scale-110 group-hover:shadow-[#005e6a]/20 mb-5">
                <i class="fa-solid fa-lock"></i>
            </div>
            <p class="text-zinc-300 text-xs sm:text-sm leading-relaxed font-medium max-w-xs transition-colors group-hover:text-white">
                Memberikan kepastian hukum atas kepemilikan rumah
            </p>
        </div>

    </div>
</section>
<section class="w-full max-w-6xl mx-auto bg-[#0b0c10]/75 border border-zinc-800/50 rounded-[2.5rem] p-6 sm:p-10 md:py-14 text-left backdrop-blur-xl shadow-2xl z-10 relative mt-8">
    
    <div class="mb-10 md:mb-12">
        <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight mb-2">
            Prosedur Pengajuan KPR
        </h2>
        <p class="text-zinc-400 text-xs sm:text-sm font-medium">
            Ikuti langkah-langkah berikut untuk mengajukan KPR dengan mudah.
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-10">
        
        <div class="flex flex-col gap-3 group">
            <div class="w-10 h-10 rounded-xl bg-[#005e6a] border border-[#005e6a]/20 text-white flex items-center justify-center font-black text-lg shadow-md transition-transform duration-300 group-hover:scale-110">
                1
            </div>
            <div>
                <h3 class="text-white font-bold text-sm sm:text-base mb-1.5 transition-colors group-hover:text-[#005e6a]">
                    Pilih Bank/Lembaga Keuangan
                </h3>
                <p class="text-zinc-400 text-xs sm:text-[13px] leading-relaxed font-medium">
                    Pilih bank atau lembaga keuangan yang menawarkan kondisi KPR yang sesuai dengan kebutuhan.
                </p>
            </div>
        </div>

        <div class="flex flex-col gap-3 group">
            <div class="w-10 h-10 rounded-xl bg-[#005e6a] border border-[#005e6a]/20 text-white flex items-center justify-center font-black text-lg shadow-md transition-transform duration-300 group-hover:scale-110">
                2
            </div>
            <div>
                <h3 class="text-white font-bold text-sm sm:text-base mb-1.5 transition-colors group-hover:text-[#005e6a]">
                    Persiapan Dokumen
                </h3>
                <p class="text-zinc-400 text-xs sm:text-[13px] leading-relaxed font-medium">
                    Persiapkan dokumen-dokumen yang diperlukan untuk mengajukan KPR.
                </p>
            </div>
        </div>

        <div class="flex flex-col gap-3 group">
            <div class="w-10 h-10 rounded-xl bg-[#005e6a] border border-[#005e6a]/20 text-white flex items-center justify-center font-black text-lg shadow-md transition-transform duration-300 group-hover:scale-110">
                3
            </div>
            <div>
                <h3 class="text-white font-bold text-sm sm:text-base mb-1.5 transition-colors group-hover:text-[#005e6a]">
                    Pengisian Formulir
                </h3>
                <p class="text-zinc-400 text-xs sm:text-[13px] leading-relaxed font-medium">
                    Mengisikan formulir yang disediakan oleh bank atau lembaga keuangan terkait.
                </p>
            </div>
        </div>

        <div class="flex flex-col gap-3 group">
            <div class="w-10 h-10 rounded-xl bg-[#005e6a] border border-[#005e6a]/20 text-white flex items-center justify-center font-black text-lg shadow-md transition-transform duration-300 group-hover:scale-110">
                4
            </div>
            <div>
                <h3 class="text-white font-bold text-sm sm:text-base mb-1.5 transition-colors group-hover:text-[#005e6a]">
                    Pemeriksaan Kelayakan
                </h3>
                <p class="text-zinc-400 text-xs sm:text-[13px] leading-relaxed font-medium">
                    Pihak berwenang akan memvalidasi dokumen-dokumen yang diajukan serta memeriksa kelayakan keuangan untuk mendapatkan KPR.
                </p>
            </div>
        </div>

        <div class="flex flex-col gap-3 group">
            <div class="w-10 h-10 rounded-xl bg-[#005e6a] border border-[#005e6a]/20 text-white flex items-center justify-center font-black text-lg shadow-md transition-transform duration-300 group-hover:scale-110">
                5
            </div>
            <div>
                <h3 class="text-white font-bold text-sm sm:text-base mb-1.5 transition-colors group-hover:text-[#005e6a]">
                    Penilaian Properti
                </h3>
                <p class="text-zinc-400 text-xs sm:text-[13px] leading-relaxed font-medium">
                    Bank atau lembaga keuangan akan melakukan penilaian terhadap properti yang akan dijadikan jaminan.
                </p>
            </div>
        </div>

        <div class="flex flex-col gap-3 group">
            <div class="w-10 h-10 rounded-xl bg-[#005e6a] border border-[#005e6a]/20 text-white flex items-center justify-center font-black text-lg shadow-md transition-transform duration-300 group-hover:scale-110">
                6
            </div>
            <div>
                <h3 class="text-white font-bold text-sm sm:text-base mb-1.5 transition-colors group-hover:text-[#005e6a]">
                    Penandatanganan Akta Kredit
                </h3>
                <p class="text-zinc-400 text-xs sm:text-[13px] leading-relaxed font-medium">
                    Jika pengajuan KPR disetujui, pemohon akan diminta untuk menandatangani akta kredit yang berisi detail tentang persyaratan pinjaman.
                </p>
            </div>
        </div>

        <div class="flex flex-col gap-3 group">
            <div class="w-10 h-10 rounded-xl bg-[#005e6a] border border-[#005e6a]/20 text-white flex items-center justify-center font-black text-lg shadow-md transition-transform duration-300 group-hover:scale-110">
                7
            </div>
            <div>
                <h3 class="text-white font-bold text-sm sm:text-base mb-1.5 transition-colors group-hover:text-[#005e6a]">
                    Pembayaran Biaya Administrasi
                </h3>
                <p class="text-zinc-400 text-xs sm:text-[13px] leading-relaxed font-medium">
                    Penuhi pembayaran administrasi yang diperlukan dalam pengajuan KPR.
                </p>
            </div>
        </div>

        <div class="flex flex-col gap-3 group">
            <div class="w-10 h-10 rounded-xl bg-[#005e6a] border border-[#005e6a]/20 text-white flex items-center justify-center font-black text-lg shadow-md transition-transform duration-300 group-hover:scale-110">
                8
            </div>
            <div>
                <h3 class="text-white font-bold text-sm sm:text-base mb-1.5 transition-colors group-hover:text-[#005e6a]">
                    Pencairan Kredit
                </h3>
                <p class="text-zinc-400 text-xs sm:text-[13px] leading-relaxed font-medium">
                    Setelah semua persyaratan terpenuhi dan akta kredit ditandatangani, bank atau lembaga keuangan akan mencairkan dana pinjaman sesuai dengan kesepakatan.
                </p>
            </div>
        </div>

    </div>
    <div class="flex justify-center mt-12">
        <a href="<?= base_url('Index/Umum') ?>" class="group flex items-center gap-2.5 bg-[#d6fb00]/5 hover:bg-[#d6fb00]/8 border border-[#d6fb00]/20 hover:border-[#d6fb00]/20 px-6 py-3 rounded-xl text-xs font-semibold text-zinc-400 hover:text-white transition-all duration-300 shadow-xl backdrop-blur-md">
            <i class="fa-solid fa-arrow-left transition-transform group-hover:-translate-x-1"></i>
            <span>Kembali ke Halaman Umum</span>
        </a>
    </div>
</section>