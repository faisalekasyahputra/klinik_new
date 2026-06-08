<section class="w-full max-w-6xl mx-auto bg-[#0b0c10]/75 border border-zinc-800/50 rounded-[2.5rem] p-6 sm:p-10 md:p-12 backdrop-blur-xl shadow-2xl z-10 relative">
    
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-3 relative">
        <div class="hidden sm:block w-36"></div> 
        
        <div class="text-center">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-[#d6fb00] tracking-wide uppercase">
                Daftar Intervensi
            </h2>
        </div>
        
        <a href="#" class="bg-[#d6fb00] hover:bg-[#c2e600] text-[#0a1a1f] px-4 py-2 rounded-xl text-xs font-bold flex items-center gap-2 transition-all duration-300 shadow-md hover:shadow-[#d6fb00]/20 active:scale-95">
            <i class="fa-solid fa-circle-plus text-sm"></i>
            Tambah Data
        </a>
    </div>

    <p class="text-zinc-300 text-xs sm:text-sm font-medium tracking-wide text-center mb-10">
        Rekapitulasi Program Perumahan Kabupaten/Kota
    </p>

    <div class="w-full overflow-x-auto rounded-2xl shadow-xl border border-zinc-800/50">
        <table class="w-full min-w-[700px] border-collapse bg-white text-zinc-900 text-left text-xs sm:text-sm font-semibold">
            
            <thead>
                <tr class="bg-[#24211a] border-b border-zinc-800 text-[#d6fb00] text-[11px] font-bold uppercase tracking-wider">
                    <th scope="col" class="px-6 py-4">Wilayah</th>
                    <th scope="col" class="px-6 py-4">Program</th>
                    <th scope="col" class="px-6 py-4">Unit</th>
                    <th scope="col" class="px-6 py-4">Anggaran</th>
                    <th scope="col" class="px-6 py-4">Tahun</th>
                    <th scope="col" class="px-6 py-4 text-center">Aksi</th>
                </tr>
            </thead>
            
            <tbody class="divide-y divide-zinc-100">
                
                <tr class="hover:bg-zinc-50/80 transition-colors">
                    <td class="px-6 py-5 font-bold text-zinc-800">Kota Semarang</td>
                    <td class="px-6 py-5">
                        <span class="inline-block bg-[#d6fb00]/10 text-[#c2e600] border border-[#d6fb00]/15 px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide">
                            RTLH
                        </span>
                    </td>
                    <td class="px-6 py-5 text-zinc-600 font-medium">25 Unit</td>
                    <td class="px-6 py-5 text-zinc-600 font-medium">Rp 500.000.000</td>
                    <td class="px-6 py-5 text-zinc-600 font-medium">2026</td>
                    <td class="px-6 py-5 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button class="text-zinc-400 hover:text-[#d6fb00] transition-colors"><i class="fa-solid fa-pen-to-square"></i></button>
                            <button class="text-zinc-400 hover:text-red-500 transition-colors"><i class="fa-solid fa-trash-can"></i></button>
                        </div>
                    </td>
                </tr>

                <tr class="hover:bg-zinc-50/80 transition-colors">
                    <td class="px-6 py-5 font-bold text-zinc-800">Kab. Magelang</td>
                    <td class="px-6 py-5">
                        <span class="inline-block bg-[#d6fb00]/10 text-[#c2e600] border border-[#d6fb00]/15 px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wide">
                            PSU PERUMAHAN
                        </span>
                    </td>
                    <td class="px-6 py-5 text-zinc-600 font-medium">1 Kompleks</td>
                    <td class="px-6 py-5 text-zinc-600 font-medium">Rp 750.000.000</td>
                    <td class="px-6 py-5 text-zinc-600 font-medium">2026</td>
                    <td class="px-6 py-5 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <button class="text-zinc-400 hover:text-[#d6fb00] transition-colors"><i class="fa-solid fa-pen-to-square"></i></button>
                            <button class="text-zinc-400 hover:text-red-500 transition-colors"><i class="fa-solid fa-trash-can"></i></button>
                        </div>
                    </td>
                </tr>

            </tbody>
        </table>
    </div>
    <div class="flex justify-center mt-12">
        <a href="<?= base_url() ?>" class="group flex items-center gap-2.5 bg-[#d6fb00]/5 hover:bg-[#d6fb00]/8 border border-[#d6fb00]/20 hover:border-[#d6fb00]/20 px-6 py-3 rounded-xl text-xs font-semibold text-zinc-400 hover:text-white transition-all duration-300 shadow-xl backdrop-blur-md">
            <i class="fa-solid fa-arrow-left transition-transform group-hover:-translate-x-1"></i>
            <span>Kembali ke Beranda Utama</span>
        </a>
    </div>
</section>