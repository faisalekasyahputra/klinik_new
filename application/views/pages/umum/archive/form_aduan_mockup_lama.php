<!-- ARSIP: mockup form lama, BUKAN dihapus. Ini murni tampilan palsu —
     action="#", data nama/email hardcode, tidak ada atribut name pada
     input, toolbar editor kaya teks tidak fungsional, tanpa CSRF.
     Diarsipkan 23 Juli 2026 saat konsep aduan diubah jadi 1 form langsung
     dengan auto-routing kata kunci. -->
<section class="w-full max-w-4xl mx-auto bg-[#0b0c10]/75 border border-zinc-800/50 rounded-[2.5rem] p-6 sm:p-10 md:p-12 backdrop-blur-xl shadow-2xl z-10 relative mt-8 text-left">
    
    <div class="border-b border-zinc-800/80 pb-5 mb-8">
        <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight mb-2">
            Layanan dan Aduan
        </h2>
        <p class="text-zinc-400 text-xs sm:text-sm font-medium">
            Kirimkan detail laporan atau pertanyaan Anda melalui formulir di bawah ini.
        </p>
    </div>

    <form action="#" method="POST" class="space-y-6">
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div>
                <label class="block text-[#d6fb00] font-extrabold text-[11px] uppercase tracking-wider mb-2">Nama</label>
                <input type="text" value="Angki Yulistiawan" class="w-full bg-zinc-800/40 border border-zinc-700/60 text-white rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00]/40 transition-all duration-300">
            </div>
            <div>
                <label class="block text-[#d6fb00] font-extrabold text-[11px] uppercase tracking-wider mb-2">Alamat Email</label>
                <input type="email" value="sispambumen@gmail.com" class="w-full bg-zinc-800/40 border border-zinc-700/60 text-white rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00]/40 transition-all duration-300">
            </div>
        </div>

        <div>
            <label class="block text-[#d6fb00] font-extrabold text-[11px] uppercase tracking-wider mb-2">Judul</label>
            <input type="text" placeholder="Masukkan subjek atau judul aduan" class="w-full bg-zinc-800/40 border border-zinc-700/60 text-white placeholder-zinc-500 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00]/40 transition-all duration-300">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div>
                <label class="block text-[#d6fb00] font-extrabold text-[11px] uppercase tracking-wider mb-2">Bidang / Departemen</label>
                <select class="w-full bg-zinc-800/40 border border-zinc-700/60 text-white rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00]/40 transition-all duration-300 appearance-none">
                    <option value="perumahan" selected>Bidang Perumahan</option>
                    <option value="permukiman">Bidang Kawasan Permukiman</option>
                    <option value="keterpaduan">Bidang Keterpaduan PKP</option>
                    <option value="pertanahan">Bidang Pertanahan</option>
                </select>
            </div>
            <div>
                <label class="block text-[#d6fb00] font-extrabold text-[11px] uppercase tracking-wider mb-2">Layanan Lain</label>
                <select class="w-full bg-zinc-800/40 border border-zinc-700/60 text-white rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00]/40 transition-all duration-300 appearance-none">
                    <option value="none" selected>None</option>
                    <option value="opsi1">Opsi Tambahan 1</option>
                    <option value="opsi2">Opsi Tambahan 2</option>
                </select>
            </div>
            <div>
                <label class="block text-[#d6fb00] font-extrabold text-[11px] uppercase tracking-wider mb-2">Prioritas</label>
                <select class="w-full bg-zinc-800/40 border border-zinc-700/60 text-white rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00]/40 transition-all duration-300 appearance-none">
                    <option value="rendah">Rendah</option>
                    <option value="sedang" selected>Sedang</option>
                    <option value="tinggi">Tinggi</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-[#d6fb00] font-extrabold text-[11px] uppercase tracking-wider mb-2">Pesan</label>
            <div class="w-full border border-zinc-700/60 rounded-xl overflow-hidden bg-zinc-900/50">
                <div class="flex flex-wrap items-center gap-1 bg-zinc-800/60 px-3 py-2 border-b border-zinc-700/60 text-zinc-400 text-xs">
                    <button type="button" class="w-7 h-7 rounded hover:bg-zinc-700 hover:text-white transition-colors"><i class="fa-solid fa-bold"></i></button>
                    <button type="button" class="w-7 h-7 rounded hover:bg-zinc-700 hover:text-white transition-colors"><i class="fa-solid fa-italic"></i></button>
                    <button type="button" class="w-7 h-7 rounded hover:bg-zinc-700 hover:text-white transition-colors"><i class="fa-solid fa-heading"></i></button>
                    <span class="text-zinc-700 px-1">|</span>
                    <button type="button" class="w-7 h-7 rounded hover:bg-zinc-700 hover:text-white transition-colors"><i class="fa-solid fa-link"></i></button>
                    <span class="text-zinc-700 px-1">|</span>
                    <button type="button" class="w-7 h-7 rounded hover:bg-zinc-700 hover:text-white transition-colors"><i class="fa-solid fa-list-ul"></i></button>
                    <button type="button" class="w-7 h-7 rounded hover:bg-zinc-700 hover:text-white transition-colors"><i class="fa-solid fa-list-ol"></i></button>
                    <button type="button" class="w-7 h-7 rounded hover:bg-zinc-700 hover:text-white transition-colors"><i class="fa-solid fa-code"></i></button>
                    <button type="button" class="w-7 h-7 rounded hover:bg-zinc-700 hover:text-white transition-colors"><i class="fa-solid fa-quote-right"></i></button>
                    <span class="text-zinc-700 px-1">|</span>
                    <button type="button" class="px-2.5 h-7 rounded bg-[#005e6a] text-white font-bold text-[11px] hover:bg-[#004d57] transition-colors flex items-center gap-1">
                        <i class="fa-solid fa-eye"></i> Preview
                    </button>
                    <button type="button" class="w-7 h-7 rounded hover:bg-zinc-700 hover:text-white transition-colors ml-auto"><i class="fa-solid fa-question"></i></button>
                </div>
                <textarea rows="8" placeholder="Tuliskan detail laporan atau kronologi aduan Anda di sini..." class="w-full bg-transparent text-white placeholder-[#5a7a80] px-4 py-3 text-sm font-medium focus:outline-none resize-y"></textarea>
            </div>
        </div>

        <div>
            <label class="block text-[#d6fb00] font-extrabold text-[11px] uppercase tracking-wider mb-2">Lampiran</label>
            <div class="flex items-center gap-2 max-w-xl">
                <div class="relative flex-1">
                    <input type="file" id="file-upload" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                    <div class="w-full bg-zinc-800/40 border border-zinc-700/60 text-zinc-400 rounded-xl px-4 py-2.5 text-xs font-semibold flex items-center justify-between">
                        <span id="file-name">Choose file...</span>
                        <span class="bg-zinc-700 hover:bg-zinc-600 text-white px-3 py-1 rounded-lg text-[11px] transition-colors">Browse</span>
                    </div>
                </div>
                <button type="button" class="bg-zinc-800 hover:bg-zinc-700 border border-zinc-700 text-white font-bold text-xs px-4 py-3 rounded-xl transition-colors flex items-center gap-1.5 whitespace-nowrap">
                    <i class="fa-solid fa-plus"></i> Tambahkan Lainnya
                </button>
            </div>
            <p class="text-[10px] text-zinc-500 mt-2">
                Allowed File Extensions: .jpg, .gif, .jpeg, .png, .pdf, .zip, .html, .doc, .docx (Max file size: 32MB)
            </p>
        </div>

        <div class="pt-4 flex flex-col sm:flex-row gap-3">
            <button type="submit" class="w-full sm:w-auto bg-[#d6fb00] hover:bg-[#c2e600] text-[#0a1a1f] font-extrabold text-xs sm:text-sm uppercase tracking-wider px-8 py-4 rounded-xl transition-all duration-300 shadow-lg hover:shadow-[#d6fb00]/20 active:scale-[0.98]">
                Kirim Tiket Aduan
            </button>
            <button type="reset" class="w-full sm:w-auto bg-transparent hover:bg-[#d6fb00]/5 border border-zinc-800 hover:border-[#d6fb00]/20 text-zinc-400 hover:text-white font-bold text-xs sm:text-sm uppercase tracking-wider px-6 py-4 rounded-xl transition-colors">
                Batal
            </button>
        </div>

    </form>
    <div class="flex justify-center mt-12">
        <a href="<?= base_url('umum') ?>" class="group flex items-center gap-2.5 bg-[#d6fb00]/5 hover:bg-[#d6fb00]/8 border border-[#d6fb00]/20 hover:border-[#d6fb00]/20 px-6 py-3 rounded-xl text-xs font-semibold text-zinc-400 hover:text-white transition-all duration-300 shadow-xl backdrop-blur-md">
            <i class="fa-solid fa-arrow-left transition-transform group-hover:-translate-x-1"></i>
            <span>Kembali ke Halmaan Umum</span>
        </a>
    </div>
</section>
