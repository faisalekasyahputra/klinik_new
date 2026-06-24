<main class="min-h-screen pt-24 pb-16 relative font-outfit z-10">
    <!-- Load Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Force body to allow sticky positioning in case of hidden overflows */
        html, body { 
            overflow-x: clip !important; 
            overflow-y: visible !important;
            height: auto !important;
        }
        main, #page-content-wrapper { 
            overflow: visible !important; 
        }
    </style>

    <div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="text-center mb-10 animate-fade-in-up">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#d6fb00]/10 border border-[#d6fb00]/20 text-[#ecffb6] text-xs font-semibold uppercase tracking-widest mb-4">
                <i class="fa-solid fa-chart-line"></i>
                Statistika Integrasi
            </div>
            <h1 class="text-4xl md:text-5xl font-extrabold text-white tracking-tight mb-4">
                Bank Data <span class="text-[#d6fb00] drop-shadow-[0_0_15px_rgba(214,251,0,0.3)]">Statistika</span>
            </h1>
            <p class="text-lg text-zinc-400 max-w-2xl mx-auto">
                Rangkuman data komprehensif terintegrasi dari berbagai sistem informasi beserta infografis visual.
            </p>
        </div>

        <!-- Filter Kabupaten -->
        <div class="max-w-md mx-auto mb-16 animate-fade-in-up" style="animation-delay: 0.05s;">
            <form action="<?= base_url('Statistika') ?>" method="GET" class="relative group">
                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-[#d6fb00]">
                    <i class="fa-solid fa-location-dot"></i>
                </div>
                <select name="kabupaten" onchange="this.form.submit()" class="block w-full p-4 pl-12 text-sm text-white bg-[#0a1a1f]/80 backdrop-blur-xl border border-white/20 rounded-2xl focus:ring-[#d6fb00] focus:border-[#d6fb00] appearance-none cursor-pointer shadow-[0_0_20px_rgba(0,0,0,0.5)] transition-all group-hover:border-[#d6fb00]/50 outline-none">
                    <option value="all" <?= $kabupaten_terpilih == 'all' ? 'selected' : '' ?>>Semua Kabupaten/Kota (Provinsi)</option>
                    <?php foreach($daftar_kabupaten as $kab): ?>
                        <option value="<?= $kab ?>" <?= $kabupaten_terpilih == $kab ? 'selected' : '' ?>><?= $kab ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-white/50 group-hover:text-[#d6fb00] transition-colors">
                    <i class="fa-solid fa-chevron-down"></i>
                </div>
            </form>
            <?php if($kabupaten_terpilih !== 'all'): ?>
            <div class="text-center mt-3 text-sm text-[#8aacb0] bg-[#d6fb00]/5 border border-[#d6fb00]/10 py-2 rounded-xl backdrop-blur-sm">
                Menampilkan data estimasi untuk <span class="font-bold text-[#d6fb00]"><?= htmlspecialchars($kabupaten_terpilih) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="flex flex-col lg:flex-row gap-8 items-start relative">
            
            <!-- Sidebar Navigation -->
            <div class="w-full lg:w-64 flex-shrink-0 hidden lg:block sticky top-32 self-start h-max z-40 transition-all duration-300" style="position: -webkit-sticky; position: sticky;">
                <aside class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-white/10 rounded-2xl p-4">
                <h3 class="text-white font-bold text-sm uppercase tracking-wider mb-4 px-2 border-b border-white/10 pb-2">Kategori Data</h3>
                <nav class="space-y-1" id="stat-nav">
                    <a href="#perumahan" class="flex items-center gap-3 px-3 py-2 text-sm text-[#8aacb0] hover:text-[#d6fb00] hover:bg-white/5 rounded-xl transition-colors">
                        <i class="fa-solid fa-house w-4 text-center"></i> Perumahan
                    </a>
                    <a href="#kawasan" class="flex items-center gap-3 px-3 py-2 text-sm text-[#8aacb0] hover:text-[#d6fb00] hover:bg-white/5 rounded-xl transition-colors">
                        <i class="fa-solid fa-map-location-dot w-4 text-center"></i> Kawasan
                    </a>
                    <a href="#pertanahan" class="flex items-center gap-3 px-3 py-2 text-sm text-[#8aacb0] hover:text-[#d6fb00] hover:bg-white/5 rounded-xl transition-colors">
                        <i class="fa-solid fa-map w-4 text-center"></i> Pertanahan
                    </a>
                    <a href="#pengembang" class="flex items-center gap-3 px-3 py-2 text-sm text-[#8aacb0] hover:text-[#d6fb00] hover:bg-white/5 rounded-xl transition-colors">
                        <i class="fa-solid fa-hard-hat w-4 text-center"></i> Pengembang
                    </a>
                    <a href="#penerima-manfaat" class="flex items-center gap-3 px-3 py-2 text-sm text-[#8aacb0] hover:text-[#d6fb00] hover:bg-white/5 rounded-xl transition-colors">
                        <i class="fa-solid fa-users w-4 text-center"></i> Penerima Manfaat
                    </a>
                    <a href="#publikasi" class="flex items-center gap-3 px-3 py-2 text-sm text-[#8aacb0] hover:text-[#d6fb00] hover:bg-white/5 rounded-xl transition-colors">
                        <i class="fa-solid fa-bullhorn w-4 text-center"></i> Publikasi
                    </a>
                </nav>
                </aside>
            </div>

            <!-- Main Content Area -->
            <div class="flex-1 min-w-0 space-y-16">
            
            <!-- 1. Perumahan -->
            <section id="perumahan" class="animate-fade-in-up scroll-mt-28" style="animation-delay: 0.1s;">
                <div class="flex items-center gap-4 mb-6 border-b border-white/5 pb-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#d6fb00] to-[#b5d400] flex items-center justify-center text-[#0a1a1f] shadow-[0_0_20px_rgba(214,251,0,0.3)]">
                        <i class="fa-solid fa-house text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">Perumahan</h2>
                        <p class="text-sm text-zinc-400">Statistika unit rumah dan penanganan RTLH</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                    <div class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-white/10 p-6 rounded-2xl relative group overflow-hidden">
                        <div class="relative z-10">
                            <div class="text-[#8aacb0] text-sm font-semibold mb-2">Tuku Lemah Oleh Omah</div>
                            <div class="text-3xl font-black text-white mb-3"><?= number_format($stats['perumahan']['tloo']['value'], 0, ',', '.') ?></div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-white/40 bg-white/5 inline-block px-2 py-1 rounded">Sumber: <?= $stats['perumahan']['tloo']['sumber'] ?></div>
                        </div>
                    </div>
                    <div class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-[#d6fb00]/20 p-6 rounded-2xl relative group overflow-hidden shadow-[0_0_15px_rgba(214,251,0,0.05)]">
                        <div class="relative z-10">
                            <div class="text-[#d6fb00] text-sm font-semibold mb-2">Bantuan RTLH (APBD)</div>
                            <div class="text-3xl font-black text-[#d6fb00] drop-shadow-[0_0_10px_rgba(214,251,0,0.3)] mb-3"><?= number_format($stats['perumahan']['rtlh_apbd']['value'], 0, ',', '.') ?></div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-[#d6fb00]/50 bg-[#d6fb00]/10 inline-block px-2 py-1 rounded">Sumber: <?= $stats['perumahan']['rtlh_apbd']['sumber'] ?></div>
                        </div>
                    </div>
                    <div class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-white/10 p-6 rounded-2xl relative group overflow-hidden">
                        <div class="relative z-10">
                            <div class="text-[#8aacb0] text-sm font-semibold mb-2">BSPS (APBN)</div>
                            <div class="text-3xl font-black text-white mb-3"><?= number_format($stats['perumahan']['bsps']['value'], 0, ',', '.') ?></div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-white/40 bg-white/5 inline-block px-2 py-1 rounded">Sumber: <?= $stats['perumahan']['bsps']['sumber'] ?></div>
                        </div>
                    </div>
                    <div class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-white/10 p-6 rounded-2xl relative group overflow-hidden">
                        <div class="relative z-10">
                            <div class="text-[#8aacb0] text-sm font-semibold mb-2">Program Omah Lestari</div>
                            <div class="text-3xl font-black text-white mb-3"><?= number_format($stats['perumahan']['omah_lestari']['value'], 0, ',', '.') ?></div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-white/40 bg-white/5 inline-block px-2 py-1 rounded">Sumber: <?= $stats['perumahan']['omah_lestari']['sumber'] ?></div>
                        </div>
                    </div>
                </div>

                <!-- Graphs for Perumahan -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-white/10 p-6 rounded-2xl flex flex-col items-center">
                        <h3 class="text-white font-semibold mb-4 text-center">Komposisi Penanganan Berdasarkan Program</h3>
                        <div class="relative w-full h-[250px] flex justify-center"><canvas id="chartRTLH"></canvas></div>
                    </div>
                    <div class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-white/10 p-6 rounded-2xl flex flex-col items-center">
                        <h3 class="text-white font-semibold mb-4 text-center">Unit Rumah Subsidi vs Komersil</h3>
                        <div class="relative w-full h-[250px]"><canvas id="chartUnit"></canvas></div>
                    </div>
                </div>
            </section>

            <!-- 2. Kawasan -->
            <section id="kawasan" class="animate-fade-in-up scroll-mt-28" style="animation-delay: 0.2s;">
                <div class="flex items-center gap-4 mb-6 border-b border-white/5 pb-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#00a3b5] to-[#00545f] flex items-center justify-center text-white shadow-[0_0_20px_rgba(0,163,181,0.3)]">
                        <i class="fa-solid fa-map-location-dot text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">Kawasan</h2>
                        <p class="text-sm text-zinc-400">Statistika penanganan kawasan kumuh</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    <div class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-white/10 p-6 rounded-2xl lg:col-span-2">
                        <div class="flex justify-between items-start mb-4">
                            <div class="text-[#8aacb0] text-sm font-semibold"><i class="fa-solid fa-chart-bar mr-2"></i>Progres Penanganan (Hektar)</div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-[#00a3b5]/70 bg-[#00a3b5]/10 inline-block px-2 py-1 rounded">Sumber: <?= $stats['kawasan']['tertangani']['sumber'] ?></div>
                        </div>
                        <div class="flex justify-between items-end mb-2">
                            <div class="text-3xl font-black text-white"><?= number_format($stats['kawasan']['tertangani']['value'], 1, ',', '.') ?> <span class="text-lg text-zinc-500 font-normal">/ <?= number_format($stats['kawasan']['luas_kumuh']['value'], 1, ',', '.') ?> Ha</span></div>
                            <div class="text-2xl font-bold text-[#00a3b5]"><?= $stats['kawasan']['persentase']['value'] ?>%</div>
                        </div>
                        <div class="w-full bg-[#0d2228] border border-white/5 rounded-full h-4 mb-3 overflow-hidden shadow-inner">
                            <div class="bg-gradient-to-r from-[#00545f] to-[#00a3b5] h-full rounded-full relative overflow-hidden" style="width: <?= $stats['kawasan']['persentase']['value'] ?>%">
                                <div class="absolute top-0 left-[-100%] w-full h-full bg-gradient-to-r from-transparent via-white/20 to-transparent animate-[shimmer_2s_infinite]"></div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-white/10 p-6 rounded-2xl flex flex-col justify-center">
                        <div class="text-[#8aacb0] text-sm font-semibold mb-2">Sisa Kawasan Kumuh</div>
                        <div class="text-4xl font-black text-[#ff6b6b] drop-shadow-[0_0_10px_rgba(255,107,107,0.3)] mb-3"><?= number_format($stats['kawasan']['sisa_kumuh']['value'], 1, ',', '.') ?> <span class="text-xl font-medium text-zinc-500">Ha</span></div>
                        <div><span class="text-[10px] font-bold uppercase tracking-wider text-[#ff6b6b]/40 bg-[#ff6b6b]/10 inline-block px-2 py-1 rounded">Sumber: <?= $stats['kawasan']['sisa_kumuh']['sumber'] ?></span></div>
                    </div>
                </div>

                <!-- Graphs for Kawasan -->
                <div class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-white/10 p-6 rounded-2xl flex flex-col items-center">
                    <h3 class="text-white font-semibold mb-2 text-center">Persentase Penanganan Kawasan Kumuh</h3>
                    <div class="relative w-full h-[250px] flex justify-center"><canvas id="chartKawasan"></canvas></div>
                </div>
            </section>

            <!-- 3. Pertanahan -->
            <section id="pertanahan" class="animate-fade-in-up scroll-mt-28" style="animation-delay: 0.3s;">
                <div class="flex items-center gap-4 mb-6 border-b border-white/5 pb-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white shadow-[0_0_20px_rgba(245,158,11,0.3)]">
                        <i class="fa-solid fa-map text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">Pertanahan</h2>
                        <p class="text-sm text-zinc-400">Statistika aset lahan dan bank tanah</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="space-y-6">
                        <div class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-white/10 p-6 rounded-2xl relative overflow-hidden">
                            <div class="text-[#8aacb0] text-sm font-semibold mb-2">Total Aset Lahan (Ha)</div>
                            <div class="text-3xl font-black text-white mb-3"><?= number_format($stats['pertanahan']['aset_lahan']['value'], 1, ',', '.') ?></div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-white/40 bg-white/5 inline-block px-2 py-1 rounded">Sumber: <?= $stats['pertanahan']['aset_lahan']['sumber'] ?></div>
                        </div>
                        <div class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-amber-500/20 p-6 rounded-2xl relative overflow-hidden shadow-[0_0_15px_rgba(245,158,11,0.05)]">
                            <div class="text-amber-500 text-sm font-semibold mb-2">Lahan Siap Bangun (Ha)</div>
                            <div class="text-3xl font-black text-amber-500 drop-shadow-[0_0_10px_rgba(245,158,11,0.3)] mb-3"><?= number_format($stats['pertanahan']['lahan_siap_bangun']['value'], 1, ',', '.') ?></div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-amber-500/50 bg-amber-500/10 inline-block px-2 py-1 rounded">Sumber: <?= $stats['pertanahan']['lahan_siap_bangun']['sumber'] ?></div>
                        </div>
                        <div class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-white/10 p-6 rounded-2xl relative overflow-hidden">
                            <div class="text-[#8aacb0] text-sm font-semibold mb-2">Lahan Termanfaatkan (Ha)</div>
                            <div class="text-3xl font-black text-white mb-3"><?= number_format($stats['pertanahan']['lahan_termanfaatkan']['value'], 1, ',', '.') ?></div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-white/40 bg-white/5 inline-block px-2 py-1 rounded">Sumber: <?= $stats['pertanahan']['lahan_termanfaatkan']['sumber'] ?></div>
                        </div>
                    </div>
                    <!-- Graphs for Pertanahan -->
                    <div class="lg:col-span-2 bg-[#0a1a1f]/80 backdrop-blur-xl border border-white/10 p-6 rounded-2xl flex flex-col items-center justify-center">
                        <h3 class="text-white font-semibold mb-4 text-center">Proporsi Pemanfaatan Lahan</h3>
                        <div class="relative w-full h-[350px] flex justify-center"><canvas id="chartPertanahan"></canvas></div>
                    </div>
                </div>
            </section>

            <!-- 4. Statistika Pengembang -->
            <section id="pengembang" class="animate-fade-in-up scroll-mt-28" style="animation-delay: 0.4s;">
                <div class="flex items-center gap-4 mb-6 border-b border-white/5 pb-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shadow-[0_0_20px_rgba(59,130,246,0.3)]">
                        <i class="fa-solid fa-hard-hat text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">Statistika Pengembang</h2>
                        <p class="text-sm text-zinc-400">Data kapasitas pengembang dan proyek perumahan</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Graph -->
                    <div class="lg:col-span-2 bg-[#0a1a1f]/80 backdrop-blur-xl border border-white/10 p-6 rounded-2xl flex flex-col">
                        <h3 class="text-white font-semibold mb-4 text-center">Aktivitas Pengembang</h3>
                        <div class="relative w-full h-[250px]"><canvas id="chartPengembang"></canvas></div>
                    </div>
                    <!-- Numbers -->
                    <div class="space-y-6 flex flex-col justify-center">
                        <div class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-white/10 p-4 rounded-2xl relative overflow-hidden">
                            <div class="text-[#8aacb0] text-xs font-semibold mb-1">Total Pengembang Terdaftar</div>
                            <div class="text-2xl font-black text-white mb-2"><?= number_format($stats['pengembang']['total_terdaftar']['value'], 0, ',', '.') ?></div>
                            <div class="text-[9px] font-bold uppercase tracking-wider text-white/40 bg-white/5 inline-block px-2 py-1 rounded">Sumber: <?= $stats['pengembang']['total_terdaftar']['sumber'] ?></div>
                        </div>
                        <div class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-white/10 p-4 rounded-2xl relative overflow-hidden">
                            <div class="text-[#8aacb0] text-xs font-semibold mb-1">Pengembang Aktif</div>
                            <div class="text-2xl font-black text-white mb-2"><?= number_format($stats['pengembang']['aktif']['value'], 0, ',', '.') ?></div>
                            <div class="text-[9px] font-bold uppercase tracking-wider text-white/40 bg-white/5 inline-block px-2 py-1 rounded">Sumber: <?= $stats['pengembang']['aktif']['sumber'] ?></div>
                        </div>
                        <div class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-blue-500/20 p-4 rounded-2xl relative overflow-hidden shadow-[0_0_15px_rgba(59,130,246,0.05)]">
                            <div class="text-blue-400 text-xs font-semibold mb-1">Proyek Berjalan</div>
                            <div class="text-2xl font-black text-blue-400 drop-shadow-[0_0_10px_rgba(59,130,246,0.3)] mb-2"><?= number_format($stats['pengembang']['proyek_berjalan']['value'], 0, ',', '.') ?></div>
                            <div class="text-[9px] font-bold uppercase tracking-wider text-blue-400/50 bg-blue-500/10 inline-block px-2 py-1 rounded">Sumber: <?= $stats['pengembang']['proyek_berjalan']['sumber'] ?></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 5. Statistika Penerima Manfaat -->
            <section id="penerima-manfaat" class="animate-fade-in-up scroll-mt-28" style="animation-delay: 0.5s;">
                <div class="flex items-center gap-4 mb-6 border-b border-white/5 pb-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-[0_0_20px_rgba(16,185,129,0.3)]">
                        <i class="fa-solid fa-users text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">Statistika Penerima Manfaat</h2>
                        <p class="text-sm text-zinc-400">Data masyarakat yang menerima manfaat langsung</p>
                    </div>
                </div>
                
                <!-- Main KPI Card -->
                <div class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-emerald-500/30 p-8 rounded-2xl relative overflow-hidden shadow-[0_0_20px_rgba(16,185,129,0.1)] mb-6 text-center">
                    <div class="absolute -right-4 -bottom-4 opacity-5 text-emerald-500"><i class="fa-solid fa-hand-holding-heart text-[200px]"></i></div>
                    <div class="relative z-10">
                        <div class="text-emerald-400 text-lg font-semibold mb-2 uppercase tracking-widest">Total Penerima Manfaat</div>
                        <div class="text-6xl font-black text-emerald-400 drop-shadow-[0_0_15px_rgba(16,185,129,0.4)] mb-4"><?= number_format($stats['penerima_manfaat']['total_penerima']['value'], 0, ',', '.') ?> Jiwa</div>
                        <div class="text-xs font-bold uppercase tracking-wider text-emerald-400/60 bg-emerald-500/10 inline-block px-3 py-1.5 rounded-full">Dihitung otomatis berdasarkan bantuan RTLH & KPR Subsidi</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="flex flex-col gap-6 justify-center">
                        <div class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-white/10 p-6 rounded-2xl relative overflow-hidden flex items-center justify-between">
                            <div>
                                <div class="text-[#8aacb0] text-sm font-semibold mb-1">Penerima Bantuan RTLH</div>
                                <div class="text-[10px] font-bold uppercase tracking-wider text-white/40 bg-white/5 inline-block px-2 py-1 rounded mb-2">Sumber: <?= $stats['penerima_manfaat']['bantuan_rtlh']['sumber'] ?></div>
                            </div>
                            <div class="text-3xl font-black text-white"><?= number_format($stats['penerima_manfaat']['bantuan_rtlh']['value'], 0, ',', '.') ?></div>
                        </div>
                        <div class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-white/10 p-6 rounded-2xl relative overflow-hidden flex items-center justify-between">
                            <div>
                                <div class="text-[#8aacb0] text-sm font-semibold mb-1">Pembeli Rumah Subsidi</div>
                                <div class="text-[10px] font-bold uppercase tracking-wider text-white/40 bg-white/5 inline-block px-2 py-1 rounded mb-2">Sumber: <?= $stats['penerima_manfaat']['pembeli_subsidi']['sumber'] ?></div>
                            </div>
                            <div class="text-3xl font-black text-white"><?= number_format($stats['penerima_manfaat']['pembeli_subsidi']['value'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                    <!-- Graph -->
                    <div class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-white/10 p-6 rounded-2xl flex flex-col items-center">
                        <h3 class="text-white font-semibold mb-4 text-center">Komposisi Penerima Manfaat</h3>
                        <div class="relative w-full h-[250px] flex justify-center"><canvas id="chartPenerima"></canvas></div>
                    </div>
                </div>
            </section>

            <!-- 6. Statistika Publikasi & Keterbukaan Informasi -->
            <section id="publikasi" class="animate-fade-in-up scroll-mt-28" style="animation-delay: 0.6s;">
                <div class="flex items-center gap-4 mb-6 border-b border-white/5 pb-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center text-white shadow-[0_0_20px_rgba(168,85,247,0.3)]">
                        <i class="fa-solid fa-bullhorn text-2xl"></i>
                    </div>
                    <div>
                        <h2 class="text-2xl font-bold text-white">Statistika Publikasi</h2>
                        <p class="text-sm text-zinc-400">Data keterbukaan informasi publik (Terkoneksi API KRSjawa3)</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-purple-500/30 p-5 rounded-2xl relative overflow-hidden shadow-[0_0_15px_rgba(168,85,247,0.1)]">
                        <div class="text-purple-400 text-xs font-semibold mb-1">Berita & Artikel</div>
                        <div class="text-3xl font-black text-purple-400 mb-2"><?= number_format($publikasi['artikel'], 0, ',', '.') ?></div>
                    </div>
                    <div class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-white/10 p-5 rounded-2xl relative overflow-hidden">
                        <div class="text-[#8aacb0] text-xs font-semibold mb-1">Galeri Video</div>
                        <div class="text-3xl font-black text-white mb-2"><?= number_format($publikasi['video'], 0, ',', '.') ?></div>
                    </div>
                    <div class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-white/10 p-5 rounded-2xl relative overflow-hidden">
                        <div class="text-[#8aacb0] text-xs font-semibold mb-1">Produk Hukum (Regulasi)</div>
                        <div class="text-3xl font-black text-white mb-2"><?= number_format($publikasi['regulasi'], 0, ',', '.') ?></div>
                    </div>
                    <div class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-white/10 p-5 rounded-2xl relative overflow-hidden">
                        <div class="text-[#8aacb0] text-xs font-semibold mb-1">Desain Rumah/Prototipe</div>
                        <div class="text-3xl font-black text-white mb-2"><?= number_format($publikasi['desain_rumah'], 0, ',', '.') ?></div>
                    </div>
                </div>

                <div class="bg-[#0a1a1f]/80 backdrop-blur-xl border border-white/10 p-6 rounded-2xl flex flex-col items-center">
                    <h3 class="text-white font-semibold mb-4 text-center">Komposisi Konten Publikasi</h3>
                    <div class="relative w-full h-[250px] flex justify-center"><canvas id="chartPublikasi"></canvas></div>
                </div>
            </section>

        </div>
    </div>
    </div>
    
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        Chart.defaults.color = '#8aacb0';
        Chart.defaults.font.family = 'Outfit, sans-serif';
        Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(10, 26, 31, 0.9)';
        Chart.defaults.plugins.tooltip.titleColor = '#ecffb6';
        Chart.defaults.plugins.tooltip.borderColor = 'rgba(214, 251, 0, 0.3)';
        Chart.defaults.plugins.tooltip.borderWidth = 1;
        
        // 1. RTLH Chart (Doughnut)
        const ctxRTLH = document.getElementById('chartRTLH').getContext('2d');
        new Chart(ctxRTLH, {
            type: 'doughnut',
            data: {
                labels: ['TLOO', 'APBD Prov', 'BSPS', 'Omah Lestari'],
                datasets: [{
                    data: [
                        <?= $stats['perumahan']['tloo']['value'] ?>, 
                        <?= $stats['perumahan']['rtlh_apbd']['value'] ?>, 
                        <?= $stats['perumahan']['bsps']['value'] ?>,
                        <?= $stats['perumahan']['omah_lestari']['value'] ?>
                    ],
                    backgroundColor: ['#d6fb00', '#10b981', '#3b82f6', '#f59e0b'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 20, usePointStyle: true, pointStyle: 'circle' } }
                },
                cutout: '70%'
            }
        });

        // 2. Unit Rumah (Bar)
        const ctxUnit = document.getElementById('chartUnit').getContext('2d');
        new Chart(ctxUnit, {
            type: 'bar',
            data: {
                labels: ['Subsidi', 'Komersil'],
                datasets: [{
                    label: 'Jumlah Unit',
                    data: [<?= $stats['perumahan']['unit_subsidi']['value'] ?>, <?= $stats['perumahan']['unit_komersil']['value'] ?>],
                    backgroundColor: ['#d6fb00', '#00a3b5'],
                    borderRadius: 8,
                    barThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255, 255, 255, 0.05)' } },
                    x: { grid: { display: false } }
                }
            }
        });

        // 3. Kawasan (Doughnut/Gauge)
        const ctxKawasan = document.getElementById('chartKawasan').getContext('2d');
        new Chart(ctxKawasan, {
            type: 'doughnut',
            data: {
                labels: ['Tertangani (Ha)', 'Sisa Kumuh (Ha)'],
                datasets: [{
                    data: [<?= $stats['kawasan']['tertangani']['value'] ?>, <?= $stats['kawasan']['sisa_kumuh']['value'] ?>],
                    backgroundColor: ['#00a3b5', '#ff6b6b'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle' } } },
                circumference: 180,
                rotation: -90,
                cutout: '75%'
            }
        });

        // 4. Pertanahan (Polar Area)
        const ctxPertanahan = document.getElementById('chartPertanahan').getContext('2d');
        new Chart(ctxPertanahan, {
            type: 'polarArea',
            data: {
                labels: ['Total Aset (Ha)', 'Siap Bangun (Ha)', 'Termanfaatkan (Ha)'],
                datasets: [{
                    data: [
                        <?= $stats['pertanahan']['aset_lahan']['value'] ?>, 
                        <?= $stats['pertanahan']['lahan_siap_bangun']['value'] ?>, 
                        <?= $stats['pertanahan']['lahan_termanfaatkan']['value'] ?>
                    ],
                    backgroundColor: [
                        'rgba(255, 255, 255, 0.3)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(16, 185, 129, 0.8)'
                    ],
                    borderWidth: 1,
                    borderColor: '#0a1a1f'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right', labels: { usePointStyle: true, pointStyle: 'circle' } } },
                scales: { r: { grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { display: false } } }
            }
        });

        // 5. Pengembang (Bar Horizontal)
        const ctxPengembang = document.getElementById('chartPengembang').getContext('2d');
        new Chart(ctxPengembang, {
            type: 'bar',
            data: {
                labels: ['Terdaftar', 'Aktif', 'Proyek Berjalan'],
                datasets: [{
                    label: 'Jumlah Pengembang',
                    data: [
                        <?= $stats['pengembang']['total_terdaftar']['value'] ?>, 
                        <?= $stats['pengembang']['aktif']['value'] ?>, 
                        <?= $stats['pengembang']['proyek_berjalan']['value'] ?>
                    ],
                    backgroundColor: ['rgba(255,255,255,0.2)', 'rgba(59, 130, 246, 0.5)', '#3b82f6'],
                    borderRadius: 6,
                    barThickness: 30
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } },
                    y: { grid: { display: false } }
                }
            }
        });

        // 6. Penerima Manfaat (Pie)
        const ctxPenerima = document.getElementById('chartPenerima').getContext('2d');
        new Chart(ctxPenerima, {
            type: 'pie',
            data: {
                labels: ['Bantuan RTLH', 'Pembeli Subsidi'],
                datasets: [{
                    data: [
                        <?= $stats['penerima_manfaat']['bantuan_rtlh']['value'] ?>, 
                        <?= $stats['penerima_manfaat']['pembeli_subsidi']['value'] ?>
                    ],
                    backgroundColor: ['#10b981', '#34d399'],
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, pointStyle: 'circle' } } }
            }
        });
        // 7. Publikasi (Bar)
        const ctxPublikasi = document.getElementById('chartPublikasi').getContext('2d');
        new Chart(ctxPublikasi, {
            type: 'bar',
            data: {
                labels: ['Artikel', 'Video', 'Regulasi', 'Desain Rumah'],
                datasets: [{
                    label: 'Jumlah Publikasi',
                    data: [
                        <?= $publikasi['artikel'] ?>, 
                        <?= $publikasi['video'] ?>, 
                        <?= $publikasi['regulasi'] ?>, 
                        <?= $publikasi['desain_rumah'] ?>
                    ],
                    backgroundColor: ['#a855f7', '#3b82f6', '#10b981', '#f59e0b'],
                    borderRadius: 8,
                    barThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: 'rgba(255, 255, 255, 0.05)' }, ticks: { precision: 0 } },
                    x: { grid: { display: false } }
                }
            }
        });
    });
    </script>
</main>
