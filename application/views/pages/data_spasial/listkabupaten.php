<section class="w-full  pt-24 pb-16 px-4 sm:px-6 lg:px-8 relative min-h-screen font-outfit">
    <!-- Background Ornaments -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-[#d6fb00]/5 blur-[120px] rounded-full"></div>
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
                            <span class="text-[#d6fb00]">Kabupaten / Kota</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-6 mb-12">
            <div class="text-left space-y-4 max-w-2xl">
                <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tighter font-jakarta">
                    Daftar <span class="text-[#d6fb00]">Intervensi</span>
                </h2>
                <p class="text-zinc-400 text-sm sm:text-base leading-relaxed">
                    Rekapitulasi Program Perumahan Kabupaten/Kota dan monitoring spasial secara mendetail.
                </p>
            </div>
            <div class="flex-shrink-0">
                <a href="#" class="btn-primary text-xs px-5 py-3 rounded-xl flex items-center gap-2 shadow-[0_0_15px_rgba(214,251,0,0.3)] hover:shadow-[0_0_25px_rgba(214,251,0,0.5)]">
                    <i class="fa-solid fa-circle-plus text-sm"></i>
                    Tambah Data
                </a>
            </div>
        </div>

        <!-- Table Container -->
        <div class="w-full overflow-hidden rounded-[24px] bg-[#0f2a30] border border-[#d6fb00]/20 shadow-2xl shadow-black/40">
            <div class="w-full overflow-x-auto">
                <table class="w-full min-w-[700px] border-collapse text-left text-xs sm:text-sm">
                    <thead>
                        <tr class="bg-[#d6fb00]/10 border-b border-[#d6fb00]/20 text-[#ecffb6] text-[11px] font-bold uppercase tracking-widest">
                            <th scope="col" class="px-6 py-5 whitespace-nowrap">Wilayah</th>
                            <th scope="col" class="px-6 py-5 whitespace-nowrap">Program</th>
                            <th scope="col" class="px-6 py-5 whitespace-nowrap">Unit</th>
                            <th scope="col" class="px-6 py-5 whitespace-nowrap">Anggaran</th>
                            <th scope="col" class="px-6 py-5 whitespace-nowrap">Tahun</th>
                            <th scope="col" class="px-6 py-5 whitespace-nowrap text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#d6fb00]/10">
                        <tr class="hover:bg-[#d6fb00]/5 transition-colors group">
                            <td class="px-6 py-5 font-bold text-white whitespace-nowrap">Kota Semarang</td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> RTLH
                                </span>
                            </td>
                            <td class="px-6 py-5 text-zinc-400 font-medium whitespace-nowrap group-hover:text-zinc-300 transition-colors">25 Unit</td>
                            <td class="px-6 py-5 text-zinc-400 font-medium whitespace-nowrap group-hover:text-zinc-300 transition-colors">Rp 500.000.000</td>
                            <td class="px-6 py-5 text-zinc-400 font-medium whitespace-nowrap group-hover:text-zinc-300 transition-colors">2026</td>
                            <td class="px-6 py-5 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-3">
                                    <button class="w-8 h-8 rounded-lg bg-[#d6fb00]/10 text-[#d6fb00] hover:bg-[#d6fb00] hover:text-[#0a1a1f] flex items-center justify-center transition-all duration-300"><i class="fa-solid fa-pen-to-square text-xs"></i></button>
                                    <button class="w-8 h-8 rounded-lg bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-all duration-300"><i class="fa-solid fa-trash-can text-xs"></i></button>
                                </div>
                            </td>
                        </tr>
                        <tr class="hover:bg-[#d6fb00]/5 transition-colors group">
                            <td class="px-6 py-5 font-bold text-white whitespace-nowrap">Kab. Magelang</td>
                            <td class="px-6 py-5 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 px-3 py-1.5 rounded-lg text-[10px] font-bold uppercase tracking-wider">
                                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-500 animate-pulse"></span> PSU PERUMAHAN
                                </span>
                            </td>
                            <td class="px-6 py-5 text-zinc-400 font-medium whitespace-nowrap group-hover:text-zinc-300 transition-colors">1 Kompleks</td>
                            <td class="px-6 py-5 text-zinc-400 font-medium whitespace-nowrap group-hover:text-zinc-300 transition-colors">Rp 750.000.000</td>
                            <td class="px-6 py-5 text-zinc-400 font-medium whitespace-nowrap group-hover:text-zinc-300 transition-colors">2026</td>
                            <td class="px-6 py-5 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-3">
                                    <button class="w-8 h-8 rounded-lg bg-[#d6fb00]/10 text-[#d6fb00] hover:bg-[#d6fb00] hover:text-[#0a1a1f] flex items-center justify-center transition-all duration-300"><i class="fa-solid fa-pen-to-square text-xs"></i></button>
                                    <button class="w-8 h-8 rounded-lg bg-red-500/10 text-red-500 hover:bg-red-500 hover:text-white flex items-center justify-center transition-all duration-300"><i class="fa-solid fa-trash-can text-xs"></i></button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>
