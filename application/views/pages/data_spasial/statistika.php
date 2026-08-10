<div class="py-4 sm:py-6 px-1 sm:px-2 relative font-outfit z-10">
    <!-- Load Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1"></script>

    <div class="w-full">
        
        <!-- Header -->
        <div class="text-center mb-4 animate-fade-in-up">
            
            <div class="mx-auto mb-2 flex h-9 w-9 items-center justify-center rounded-xl bg-[color:var(--portal-btn-bg)] text-[color:var(--portal-brand)] shadow-sm rotate-3">
                <i class="fa-solid fa-chart-pie"></i>
            </div>
            <h1 class="text-xl sm:text-2xl font-black font-jakarta tracking-tighter text-[color:var(--portal-text)] mb-1">
                Bank Data <span class="text-[color:var(--portal-brand)]">Statistika</span>
            </h1>
            <p class="text-sm text-[color:var(--portal-text-muted)] max-w-2xl mx-auto">
                Rangkuman data perumahan dan kawasan permukiman beserta infografis visual.
            </p>

<?php
/*
 * A2 - halaman DIPERTAHANKAN atas keputusan user, tetapi arah klaimnya
 * dibalik. Seluruh angka di bawah masih SIMULASI: nilainya konstanta dikali
 * `crc32($kabupaten)` di Statistika.php, bukan hasil query ke sistem mana pun.
 * Label lamanya karena itu keliru dua kali - menyatakan provenance yang tidak
 * ada, DAN meminjam kredibilitas sistem dinas yang sungguh-sungguh ada.
 * Sekarang tiap kartu menyebut sistem yang DIRENCANAKAN menjadi sumbernya,
 * bukan mengklaim sudah berasal dari sana.
 *
 * Ditulis sebagai komentar PHP, bukan komentar HTML: komentar HTML ikut
 * terkirim ke pengunjung, sehingga mengutip label lamanya di situ membuat
 * klaim yang baru dicabut muncul lagi di respons. Kesalahan yang sama sudah
 * terjadi sekali di listkabupaten.php (A3).
 */
?>
            <p class="mt-4 mx-auto max-w-2xl rounded-xl border px-4 py-3 text-xs leading-relaxed"
               style="border-color: var(--portal-border); background-color: var(--portal-bg-card); color: var(--portal-text-muted);">
                <b style="color: var(--portal-text);">Seluruh angka di halaman ini masih simulasi.</b>
                Belum ada satu pun yang ditarik dari sistem sumbernya. Tiap kartu mencantumkan
                sistem yang <b>direncanakan</b> menjadi sumbernya bila integrasinya sudah aktif -
                bukan tempat angka itu berasal sekarang. Jangan dikutip sebagai data resmi.
            </p>
        </div>

        <div class="flex flex-col lg:flex-row gap-2.5 items-start relative">
            
            <!-- Sidebar Navigation -->
            <div class="w-full lg:w-48 flex-shrink-0 hidden lg:block sticky top-3 self-start h-max z-40 transition-all duration-300" style="position: -webkit-sticky; position: sticky;">
                <aside class="bg-[color:var(--portal-btn-bg)] border border-[color:var(--portal-border)] rounded-2xl p-2.5">
                <h3 class="text-[color:var(--portal-text)] font-bold text-sm uppercase tracking-wider mb-3 px-2 border-b border-[color:var(--portal-border)] pb-2">Kategori Data</h3>
                <nav class="space-y-1" id="stat-nav">
                    <a href="#perumahan" class="flex items-center gap-2.5 px-2.5 py-1.5 text-xs text-[color:var(--portal-text-muted)] hover:text-[color:var(--portal-brand)] hover:bg-[color:var(--portal-bg-card)] rounded-xl transition-colors">
                        <i class="fa-solid fa-house w-4 text-center"></i> Perumahan
                    </a>
                    <a href="#kawasan" class="flex items-center gap-2.5 px-2.5 py-1.5 text-xs text-[color:var(--portal-text-muted)] hover:text-[color:var(--portal-brand)] hover:bg-[color:var(--portal-bg-card)] rounded-xl transition-colors">
                        <i class="fa-solid fa-map-location-dot w-4 text-center"></i> Kawasan
                    </a>
                    <a href="#pertanahan" class="flex items-center gap-2.5 px-2.5 py-1.5 text-xs text-[color:var(--portal-text-muted)] hover:text-[color:var(--portal-brand)] hover:bg-[color:var(--portal-bg-card)] rounded-xl transition-colors">
                        <i class="fa-solid fa-map w-4 text-center"></i> Pertanahan
                    </a>
                    <a href="#pengembang" class="flex items-center gap-2.5 px-2.5 py-1.5 text-xs text-[color:var(--portal-text-muted)] hover:text-[color:var(--portal-brand)] hover:bg-[color:var(--portal-bg-card)] rounded-xl transition-colors">
                        <i class="fa-solid fa-hard-hat w-4 text-center"></i> Pengembang
                    </a>
                    <a href="#penerima-manfaat" class="flex items-center gap-2.5 px-2.5 py-1.5 text-xs text-[color:var(--portal-text-muted)] hover:text-[color:var(--portal-brand)] hover:bg-[color:var(--portal-bg-card)] rounded-xl transition-colors">
                        <i class="fa-solid fa-users w-4 text-center"></i> Penerima Manfaat
                    </a>
                    <a href="#publikasi" class="flex items-center gap-2.5 px-2.5 py-1.5 text-xs text-[color:var(--portal-text-muted)] hover:text-[color:var(--portal-brand)] hover:bg-[color:var(--portal-bg-card)] rounded-xl transition-colors">
                        <i class="fa-solid fa-bullhorn w-4 text-center"></i> Publikasi
                    </a>
                </nav>
                </aside>
            </div>

            <nav aria-label="Kategori statistika" class="lg:hidden w-full flex gap-2 overflow-x-auto pb-1 scrollbar-hide">
                <a href="#perumahan" class="shrink-0 rounded-full border border-[color:var(--portal-border)] bg-[color:var(--portal-btn-bg)] px-3 py-1.5 text-xs font-semibold text-[color:var(--portal-text)]"><i class="fa-solid fa-house mr-1.5 text-[color:var(--portal-brand)]"></i>Perumahan</a>
                <a href="#kawasan" class="shrink-0 rounded-full border border-[color:var(--portal-border)] bg-[color:var(--portal-btn-bg)] px-3 py-1.5 text-xs font-semibold text-[color:var(--portal-text)]"><i class="fa-solid fa-map-location-dot mr-1.5 text-[#00a3b5]"></i>Kawasan</a>
                <a href="#pertanahan" class="shrink-0 rounded-full border border-[color:var(--portal-border)] bg-[color:var(--portal-btn-bg)] px-3 py-1.5 text-xs font-semibold text-[color:var(--portal-text)]"><i class="fa-solid fa-map mr-1.5 text-[#ffd93d]"></i>Pertanahan</a>
                <a href="#pengembang" class="shrink-0 rounded-full border border-[color:var(--portal-border)] bg-[color:var(--portal-btn-bg)] px-3 py-1.5 text-xs font-semibold text-[color:var(--portal-text)]"><i class="fa-solid fa-hard-hat mr-1.5 text-blue-500"></i>Pengembang</a>
                <a href="#penerima-manfaat" class="shrink-0 rounded-full border border-[color:var(--portal-border)] bg-[color:var(--portal-btn-bg)] px-3 py-1.5 text-xs font-semibold text-[color:var(--portal-text)]"><i class="fa-solid fa-users mr-1.5 text-[#10b981]"></i>Penerima Manfaat</a>
                <a href="#publikasi" class="shrink-0 rounded-full border border-[color:var(--portal-border)] bg-[color:var(--portal-btn-bg)] px-3 py-1.5 text-xs font-semibold text-[color:var(--portal-text)]"><i class="fa-solid fa-bullhorn mr-1.5 text-[#00a3b5]"></i>Publikasi</a>
            </nav>

            <!-- Main Content Area -->
            <div class="flex-1 min-w-0 space-y-4">
            
            <!-- 1. Perumahan -->
            <section id="perumahan" class="animate-fade-in-up scroll-mt-4" style="animation-delay: 0.1s;">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-3 border-b border-[color:var(--portal-border)] pb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-[color:var(--portal-btn-bg)] flex items-center justify-center text-[color:var(--portal-icon)] ">
                            <i class="fa-solid fa-house text-lg"></i>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-[color:var(--portal-text)]">Perumahan</h2>
                            <p class="text-xs text-[color:var(--portal-text-muted)]">Statistika unit rumah dan penanganan RTLH</p>
                        </div>
                    </div>
                    
                    <!-- Filter Kabupaten -->
                    <div class="w-full md:w-[280px]">
                        <form action="<?= base_url('Statistika') ?>" method="GET" class="relative group">
                            <label for="filter-kabupaten" class="sr-only">Pilih kabupaten atau kota</label>
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-[color:var(--portal-brand)]">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <select id="filter-kabupaten" name="kabupaten" onchange="this.form.submit()" class="block w-full p-2.5 pl-10 text-sm text-[color:var(--portal-text)] bg-[color:var(--portal-btn-bg)] border border-[color:var(--portal-border)] rounded-xl focus:ring-[color:var(--portal-brand)] focus:border-[color:var(--portal-brand)] appearance-none cursor-pointer transition-all group-hover:border-[color:var(--portal-brand)] outline-none">
                                <option value="all" <?= $kabupaten_terpilih == 'all' ? 'selected' : '' ?>>Semua Kabupaten/Kota (Provinsi)</option>
                                <?php foreach($daftar_kabupaten as $kab): ?>
                                    <option value="<?= $kab ?>" <?= $kabupaten_terpilih == $kab ? 'selected' : '' ?>><?= $kab ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-[color:var(--portal-text-muted)] group-hover:text-[color:var(--portal-brand)] transition-colors">
                                <i class="fa-solid fa-chevron-down text-xs"></i>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if($kabupaten_terpilih !== 'all'): ?>
                <div class="text-center mb-3 text-xs text-[color:var(--portal-text-muted)] bg-[color:var(--portal-bg)] border border-[color:var(--portal-border)] py-2 rounded-xl ">
                    Menampilkan data estimasi untuk <span class="font-bold text-[color:var(--portal-brand)]"><?= htmlspecialchars($kabupaten_terpilih) ?></span>
                </div>
                <?php endif; ?>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 mb-3">
                    <div class="bg-[color:var(--portal-bg-card)] border border-[color:var(--portal-border)] p-2.5 sm:p-3.5 rounded-2xl relative group overflow-hidden">
                        <div class="relative z-10">
                            <div class="text-[color:var(--portal-text-muted)] text-sm font-semibold mb-2">Tuku Lemah Oleh Omah</div>
                            <div class="text-lg font-bold text-[color:var(--portal-text)] mb-2"><?= number_format($stats['perumahan']['tloo']['value'], 0, ',', '.') ?></div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-[color:var(--portal-text-muted)] bg-[color:var(--portal-bg)] inline-block px-2 py-1 rounded">Simulasi &middot; rencana sumber: <?= $stats['perumahan']['tloo']['sumber'] ?></div>
                        </div>
                    </div>
                    <div class="bg-[color:var(--portal-bg-card)] border border-[color:var(--portal-border)] p-2.5 sm:p-3.5 rounded-2xl relative group overflow-hidden ">
                        <div class="relative z-10">
                            <div class="text-[color:var(--portal-brand)] text-sm font-semibold mb-2">Bantuan RTLH (APBD)</div>
                            <div class="text-lg sm:text-xl font-black font-jakarta text-[color:var(--portal-brand)] mb-3"><?= number_format($stats['perumahan']['rtlh_apbd']['value'], 0, ',', '.') ?></div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-[color:var(--portal-text-muted)] bg-[color:var(--portal-bg)] inline-block px-2 py-1 rounded">Simulasi &middot; rencana sumber: <?= $stats['perumahan']['rtlh_apbd']['sumber'] ?></div>
                        </div>
                    </div>
                    <div class="bg-[color:var(--portal-bg-card)] border border-[color:var(--portal-border)] p-2.5 sm:p-3.5 rounded-2xl relative group overflow-hidden">
                        <div class="relative z-10">
                            <div class="text-[color:var(--portal-text-muted)] text-sm font-semibold mb-2">BSPS (APBN)</div>
                            <div class="text-lg font-bold text-[color:var(--portal-text)] mb-2"><?= number_format($stats['perumahan']['bsps']['value'], 0, ',', '.') ?></div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-[color:var(--portal-text-muted)] bg-[color:var(--portal-bg)] inline-block px-2 py-1 rounded">Simulasi &middot; rencana sumber: <?= $stats['perumahan']['bsps']['sumber'] ?></div>
                        </div>
                    </div>
                    <div class="bg-[color:var(--portal-bg-card)] border border-[color:var(--portal-border)] p-2.5 sm:p-3.5 rounded-2xl relative group overflow-hidden">
                        <div class="relative z-10">
                            <div class="text-[color:var(--portal-text-muted)] text-sm font-semibold mb-2">Program Omah Lestari</div>
                            <div class="text-lg font-bold text-[color:var(--portal-text)] mb-2"><?= number_format($stats['perumahan']['omah_lestari']['value'], 0, ',', '.') ?></div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-[color:var(--portal-text-muted)] bg-[color:var(--portal-bg)] inline-block px-2 py-1 rounded">Simulasi &middot; rencana sumber: <?= $stats['perumahan']['omah_lestari']['sumber'] ?></div>
                        </div>
                    </div>
                </div>

                <!-- Graphs for Perumahan -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-2.5">
                    <div class="bg-[color:var(--portal-bg-card)] border border-[color:var(--portal-border)] p-2.5 sm:p-3.5 rounded-2xl flex flex-col items-center">
                        <h3 class="text-[color:var(--portal-text)] font-semibold mb-3 text-center">Komposisi Penanganan Berdasarkan Program</h3>
                        <div class="relative w-full h-[180px] flex justify-center"><canvas id="chartRTLH"></canvas></div>
                    </div>
                    <div class="bg-[color:var(--portal-bg-card)] border border-[color:var(--portal-border)] p-2.5 sm:p-3.5 rounded-2xl flex flex-col items-center">
                        <h3 class="text-[color:var(--portal-text)] font-semibold mb-3 text-center">Unit Rumah Subsidi vs Komersil</h3>
                        <div class="relative w-full h-[180px]"><canvas id="chartUnit"></canvas></div>
                    </div>
                </div>
            </section>

            <!-- 2. Kawasan -->
            <section id="kawasan" class="animate-fade-in-up scroll-mt-4" style="animation-delay: 0.2s;">
                <div class="flex items-center gap-2.5 mb-3 border-b border-[color:var(--portal-border)] pb-4">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#00a3b5] to-[#00545f] flex items-center justify-center text-white shadow-sm rotate-2">
                        <i class="fa-solid fa-map-location-dot text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-[color:var(--portal-text)]">Kawasan</h2>
                        <p class="text-xs text-[color:var(--portal-text-muted)]">Statistika penanganan kawasan kumuh</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-2.5 mb-3">
                    <div class="bg-[color:var(--portal-bg-card)] border border-[color:var(--portal-border)] p-2.5 sm:p-3.5 rounded-2xl lg:col-span-2">
                        <div class="flex justify-between items-start mb-3">
                            <div class="text-[color:var(--portal-text-muted)] text-sm font-semibold"><i class="fa-solid fa-chart-bar mr-2"></i>Progres Penanganan (Hektar)</div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-[color:var(--portal-text-muted)] bg-[color:var(--portal-bg)] inline-block px-2 py-1 rounded">Simulasi &middot; rencana sumber: <?= $stats['kawasan']['tertangani']['sumber'] ?></div>
                        </div>
                        <div class="flex justify-between items-end mb-2">
                            <div class="text-lg sm:text-xl font-black font-jakarta text-[color:var(--portal-text)]"><?= number_format($stats['kawasan']['tertangani']['value'], 1, ',', '.') ?> <span class="text-lg text-zinc-500 font-normal">/ <?= number_format($stats['kawasan']['luas_kumuh']['value'], 1, ',', '.') ?> Ha</span></div>
                            <div class="text-xl font-bold text-[#00a3b5]"><?= $stats['kawasan']['persentase']['value'] ?>%</div>
                        </div>
                        <div class="w-full bg-[color:var(--portal-btn-bg)] border border-[color:var(--portal-border)] rounded-full h-4 mb-3 overflow-hidden shadow-inner">
                            <div class="bg-gradient-to-r from-[#00545f] to-[#00a3b5] h-full rounded-full relative overflow-hidden" style="width: <?= $stats['kawasan']['persentase']['value'] ?>%">
                                <div class="absolute top-0 left-[-100%] w-full h-full bg-gradient-to-r from-transparent via-white/20 to-transparent animate-[shimmer_2s_infinite]"></div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-[color:var(--portal-bg-card)] border border-[color:var(--portal-border)] p-2.5 sm:p-3.5 rounded-2xl flex flex-col justify-center">
                        <div class="text-[color:var(--portal-text-muted)] text-sm font-semibold mb-2">Sisa Kawasan Kumuh</div>
                        <div class="text-2xl font-black text-[#ff6b6b]  mb-3"><?= number_format($stats['kawasan']['sisa_kumuh']['value'], 1, ',', '.') ?> <span class="text-xl font-medium text-zinc-500">Ha</span></div>
                        <div><span class="text-[10px] font-bold uppercase tracking-wider text-[color:var(--portal-text-muted)] bg-[color:var(--portal-bg)] inline-block px-2 py-1 rounded">Simulasi &middot; rencana sumber: <?= $stats['kawasan']['sisa_kumuh']['sumber'] ?></span></div>
                    </div>
                </div>

                <!-- Graphs for Kawasan -->
                <div class="bg-[color:var(--portal-bg-card)] border border-[color:var(--portal-border)] p-2.5 sm:p-3.5 rounded-2xl flex flex-col items-center">
                    <h3 class="text-[color:var(--portal-text)] font-semibold mb-2 text-center">Persentase Penanganan Kawasan Kumuh</h3>
                    <div class="relative w-full h-[180px] flex justify-center"><canvas id="chartKawasan"></canvas></div>
                </div>
            </section>

            <!-- 3. Pertanahan -->
            <section id="pertanahan" class="animate-fade-in-up scroll-mt-4" style="animation-delay: 0.3s;">
                <div class="flex items-center gap-2.5 mb-3 border-b border-[color:var(--portal-border)] pb-4">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white shadow-sm -rotate-2">
                        <i class="fa-solid fa-map text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-[color:var(--portal-text)]">Pertanahan</h2>
                        <p class="text-xs text-[color:var(--portal-text-muted)]">Statistika aset lahan dan bank tanah</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-2.5">
                    <div class="space-y-4">
                        <div class="bg-[color:var(--portal-bg-card)] border border-[color:var(--portal-border)] p-2.5 sm:p-3.5 rounded-2xl relative overflow-hidden">
                            <div class="text-[color:var(--portal-text-muted)] text-sm font-semibold mb-2">Total Aset Lahan (Ha)</div>
                            <div class="text-lg font-bold text-[color:var(--portal-text)] mb-2"><?= number_format($stats['pertanahan']['aset_lahan']['value'], 1, ',', '.') ?></div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-[color:var(--portal-text-muted)] bg-[color:var(--portal-bg)] inline-block px-2 py-1 rounded">Simulasi &middot; rencana sumber: <?= $stats['pertanahan']['aset_lahan']['sumber'] ?></div>
                        </div>
                        <div class="bg-[color:var(--portal-bg-card)] border border-amber-500/20 p-2.5 sm:p-3.5 rounded-2xl relative overflow-hidden ">
                            <div class="text-amber-500 text-sm font-semibold mb-2">Lahan Siap Bangun (Ha)</div>
                            <div class="text-lg sm:text-xl font-black font-jakarta text-amber-500  mb-3"><?= number_format($stats['pertanahan']['lahan_siap_bangun']['value'], 1, ',', '.') ?></div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-amber-500/50 bg-amber-500/10 inline-block px-2 py-1 rounded">Simulasi &middot; rencana sumber: <?= $stats['pertanahan']['lahan_siap_bangun']['sumber'] ?></div>
                        </div>
                        <div class="bg-[color:var(--portal-bg-card)] border border-[color:var(--portal-border)] p-2.5 sm:p-3.5 rounded-2xl relative overflow-hidden">
                            <div class="text-[color:var(--portal-text-muted)] text-sm font-semibold mb-2">Lahan Termanfaatkan (Ha)</div>
                            <div class="text-lg font-bold text-[color:var(--portal-text)] mb-2"><?= number_format($stats['pertanahan']['lahan_termanfaatkan']['value'], 1, ',', '.') ?></div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-[color:var(--portal-text-muted)] bg-[color:var(--portal-bg)] inline-block px-2 py-1 rounded">Simulasi &middot; rencana sumber: <?= $stats['pertanahan']['lahan_termanfaatkan']['sumber'] ?></div>
                        </div>
                    </div>
                    <!-- Graphs for Pertanahan -->
                    <div class="lg:col-span-2 bg-[color:var(--portal-bg-card)] border border-[color:var(--portal-border)] p-2.5 sm:p-3.5 rounded-2xl flex flex-col items-center justify-center">
                        <h3 class="text-[color:var(--portal-text)] font-semibold mb-3 text-center">Proporsi Pemanfaatan Lahan</h3>
                        <div class="relative w-full h-[250px] flex justify-center"><canvas id="chartPertanahan"></canvas></div>
                    </div>
                </div>
            </section>

            <!-- 4. Statistika Pengembang -->
            <section id="pengembang" class="animate-fade-in-up scroll-mt-4" style="animation-delay: 0.4s;">
                <div class="flex items-center gap-2.5 mb-3 border-b border-[color:var(--portal-border)] pb-4">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white shadow-sm rotate-2">
                        <i class="fa-solid fa-hard-hat text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-[color:var(--portal-text)]">Statistika Pengembang</h2>
                        <p class="text-xs text-[color:var(--portal-text-muted)]">Data kapasitas pengembang dan proyek perumahan</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-2.5">
                    <!-- Graph -->
                    <div class="lg:col-span-2 bg-[color:var(--portal-bg-card)] border border-[color:var(--portal-border)] p-2.5 sm:p-3.5 rounded-2xl flex flex-col">
                        <h3 class="text-[color:var(--portal-text)] font-semibold mb-3 text-center">Aktivitas Pengembang</h3>
                        <div class="relative w-full h-[180px]"><canvas id="chartPengembang"></canvas></div>
                    </div>
                    <!-- Numbers -->
                    <div class="space-y-4 flex flex-col justify-center">
                        <div class="bg-[color:var(--portal-bg-card)] border border-[color:var(--portal-border)] p-2.5 rounded-2xl relative overflow-hidden">
                            <div class="text-[color:var(--portal-text-muted)] text-xs font-semibold mb-1">Total Pengembang Terdaftar</div>
                            <div class="text-2xl font-black text-[color:var(--portal-text)] mb-2"><?= number_format($stats['pengembang']['total_terdaftar']['value'], 0, ',', '.') ?></div>
                            <div class="text-[9px] font-bold uppercase tracking-wider text-[color:var(--portal-text-muted)] bg-[color:var(--portal-bg)] inline-block px-2 py-1 rounded">Simulasi &middot; rencana sumber: <?= $stats['pengembang']['total_terdaftar']['sumber'] ?></div>
                        </div>
                        <div class="bg-[color:var(--portal-bg-card)] border border-[color:var(--portal-border)] p-2.5 rounded-2xl relative overflow-hidden">
                            <div class="text-[color:var(--portal-text-muted)] text-xs font-semibold mb-1">Pengembang Aktif</div>
                            <div class="text-2xl font-black text-[color:var(--portal-text)] mb-2"><?= number_format($stats['pengembang']['aktif']['value'], 0, ',', '.') ?></div>
                            <div class="text-[9px] font-bold uppercase tracking-wider text-[color:var(--portal-text-muted)] bg-[color:var(--portal-bg)] inline-block px-2 py-1 rounded">Simulasi &middot; rencana sumber: <?= $stats['pengembang']['aktif']['sumber'] ?></div>
                        </div>
                        <div class="bg-[color:var(--portal-bg-card)] border border-blue-500/20 p-2.5 rounded-2xl relative overflow-hidden ">
                            <div class="text-blue-400 text-xs font-semibold mb-1">Proyek Berjalan</div>
                            <div class="text-2xl font-black text-blue-400  mb-2"><?= number_format($stats['pengembang']['proyek_berjalan']['value'], 0, ',', '.') ?></div>
                            <div class="text-[9px] font-bold uppercase tracking-wider text-blue-400/50 bg-blue-500/10 inline-block px-2 py-1 rounded">Simulasi &middot; rencana sumber: <?= $stats['pengembang']['proyek_berjalan']['sumber'] ?></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- 5. Statistika Penerima Manfaat -->
            <section id="penerima-manfaat" class="animate-fade-in-up scroll-mt-4" style="animation-delay: 0.5s;">
                <div class="flex items-center gap-2.5 mb-3 border-b border-[color:var(--portal-border)] pb-4">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-emerald-500 to-teal-600 flex items-center justify-center text-white shadow-sm -rotate-2">
                        <i class="fa-solid fa-users text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-[color:var(--portal-text)]">Statistika Penerima Manfaat</h2>
                        <p class="text-xs text-[color:var(--portal-text-muted)]">Data masyarakat yang menerima manfaat langsung</p>
                    </div>
                </div>
                
                <!-- Main KPI Card -->
                <div class="bg-[color:var(--portal-bg-card)] border border-emerald-500/30 p-4 rounded-2xl relative overflow-hidden  mb-3 text-center">
                    <div class="absolute -right-4 -bottom-4 opacity-5 text-emerald-500"><i class="fa-solid fa-hand-holding-heart text-[200px]"></i></div>
                    <div class="relative z-10">
                        <div class="text-emerald-400 text-lg font-semibold mb-2 uppercase tracking-widest">Total Penerima Manfaat</div>
                        <div class="text-3xl font-black text-emerald-400  mb-3"><?= number_format($stats['penerima_manfaat']['total_penerima']['value'], 0, ',', '.') ?> Jiwa</div>
                        <div class="text-xs font-bold uppercase tracking-wider text-emerald-400/60 bg-emerald-500/10 inline-block px-3 py-1.5 rounded-full">Dihitung otomatis berdasarkan bantuan RTLH & KPR Subsidi</div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-2.5">
                    <div class="flex flex-col gap-2.5 justify-center">
                        <div class="bg-[color:var(--portal-bg-card)] border border-[color:var(--portal-border)] p-2.5 sm:p-3.5 rounded-2xl relative overflow-hidden flex items-center justify-between">
                            <div>
                                <div class="text-[color:var(--portal-text-muted)] text-sm font-semibold mb-1">Penerima Bantuan RTLH</div>
                                <div class="text-[10px] font-bold uppercase tracking-wider text-[color:var(--portal-text-muted)] bg-[color:var(--portal-bg)] inline-block px-2 py-1 rounded mb-2">Simulasi &middot; rencana sumber: <?= $stats['penerima_manfaat']['bantuan_rtlh']['sumber'] ?></div>
                            </div>
                            <div class="text-lg sm:text-xl font-black font-jakarta text-[color:var(--portal-text)]"><?= number_format($stats['penerima_manfaat']['bantuan_rtlh']['value'], 0, ',', '.') ?></div>
                        </div>
                        <div class="bg-[color:var(--portal-bg-card)] border border-[color:var(--portal-border)] p-2.5 sm:p-3.5 rounded-2xl relative overflow-hidden flex items-center justify-between">
                            <div>
                                <div class="text-[color:var(--portal-text-muted)] text-sm font-semibold mb-1">Pembeli Rumah Subsidi</div>
                                <div class="text-[10px] font-bold uppercase tracking-wider text-[color:var(--portal-text-muted)] bg-[color:var(--portal-bg)] inline-block px-2 py-1 rounded mb-2">Simulasi &middot; rencana sumber: <?= $stats['penerima_manfaat']['pembeli_subsidi']['sumber'] ?></div>
                            </div>
                            <div class="text-lg sm:text-xl font-black font-jakarta text-[color:var(--portal-text)]"><?= number_format($stats['penerima_manfaat']['pembeli_subsidi']['value'], 0, ',', '.') ?></div>
                        </div>
                    </div>
                    <!-- Graph -->
                    <div class="bg-[color:var(--portal-bg-card)] border border-[color:var(--portal-border)] p-2.5 sm:p-3.5 rounded-2xl flex flex-col items-center">
                        <h3 class="text-[color:var(--portal-text)] font-semibold mb-3 text-center">Komposisi Penerima Manfaat</h3>
                        <div class="relative w-full h-[180px] flex justify-center"><canvas id="chartPenerima"></canvas></div>
                    </div>
                </div>
            </section>

            <!-- 6. Statistika Publikasi & Keterbukaan Informasi -->
            <section id="publikasi" class="animate-fade-in-up scroll-mt-4" style="animation-delay: 0.6s;">
                <div class="flex items-center gap-2.5 mb-3 border-b border-[color:var(--portal-border)] pb-4">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-cyan-500 to-blue-600 flex items-center justify-center text-white shadow-sm rotate-2">
                        <i class="fa-solid fa-bullhorn text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-[color:var(--portal-text)]">Statistika Publikasi</h2>
                        <p class="text-xs text-[color:var(--portal-text-muted)]">Data keterbukaan informasi publik (Terkoneksi API KRSjawa3)</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 mb-3">
                    <div class="bg-[color:var(--portal-bg-card)] border border-cyan-500/30 p-3.5 rounded-2xl relative overflow-hidden ">
                        <div class="text-cyan-400 text-xs font-semibold mb-1">Berita & Artikel</div>
                        <div class="text-lg sm:text-xl font-black font-jakarta text-cyan-400 mb-2"><?= number_format($publikasi['artikel'], 0, ',', '.') ?></div>
                    </div>
                    <div class="bg-[color:var(--portal-bg-card)] border border-[color:var(--portal-border)] p-3.5 rounded-2xl relative overflow-hidden">
                        <div class="text-[color:var(--portal-text-muted)] text-xs font-semibold mb-1">Galeri Video</div>
                        <div class="text-lg sm:text-xl font-black font-jakarta text-[color:var(--portal-text)] mb-2"><?= number_format($publikasi['video'], 0, ',', '.') ?></div>
                    </div>
                    <div class="bg-[color:var(--portal-bg-card)] border border-[color:var(--portal-border)] p-3.5 rounded-2xl relative overflow-hidden">
                        <div class="text-[color:var(--portal-text-muted)] text-xs font-semibold mb-1">Produk Hukum (Regulasi)</div>
                        <div class="text-lg sm:text-xl font-black font-jakarta text-[color:var(--portal-text)] mb-2"><?= number_format($publikasi['regulasi'], 0, ',', '.') ?></div>
                    </div>
                    <div class="bg-[color:var(--portal-bg-card)] border border-[color:var(--portal-border)] p-3.5 rounded-2xl relative overflow-hidden">
                        <div class="text-[color:var(--portal-text-muted)] text-xs font-semibold mb-1">Desain Rumah/Prototipe</div>
                        <div class="text-lg sm:text-xl font-black font-jakarta text-[color:var(--portal-text)] mb-2"><?= number_format($publikasi['desain_rumah'], 0, ',', '.') ?></div>
                    </div>
                </div>

                <div class="bg-[color:var(--portal-bg-card)] border border-[color:var(--portal-border)] p-2.5 sm:p-3.5 rounded-2xl flex flex-col items-center">
                    <h3 class="text-[color:var(--portal-text)] font-semibold mb-3 text-center">Komposisi Konten Publikasi</h3>
                    <div class="relative w-full h-[180px] flex justify-center"><canvas id="chartPublikasi"></canvas></div>
                </div>
            </section>

        </div>
    </div>
    </div>
    
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const portalStyle = getComputedStyle(document.body);
        const chartText = portalStyle.getPropertyValue('--portal-text-muted').trim() || '#5a7a80';
        const chartGrid = 'rgba(0, 84, 95, 0.12)';
        const chartBorder = portalStyle.getPropertyValue('--portal-bg-card').trim() || '#ffffff';

        Chart.defaults.color = chartText;
        Chart.defaults.font.family = 'Outfit, sans-serif';
        Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 84, 95, 0.96)';
        Chart.defaults.plugins.tooltip.titleColor = '#ffffff';
        Chart.defaults.plugins.tooltip.bodyColor = '#ffffff';
        Chart.defaults.plugins.tooltip.borderColor = '#00a3b5';
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
                    y: { beginAtZero: true, grid: { color: chartGrid } },
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
                        'rgba(0, 163, 181, 0.35)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(16, 185, 129, 0.8)'
                    ],
                    borderWidth: 1,
                    borderColor: chartBorder
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'right', labels: { usePointStyle: true, pointStyle: 'circle' } } },
                scales: { r: { grid: { color: chartGrid }, ticks: { display: false } } }
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
                    backgroundColor: ['rgba(0, 163, 181, 0.35)', 'rgba(59, 130, 246, 0.55)', '#3b82f6'],
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
                    x: { beginAtZero: true, grid: { color: chartGrid } },
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
                    backgroundColor: ['#00a3b5', '#3b82f6', '#10b981', '#f59e0b'],
                    borderRadius: 8,
                    barThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: chartGrid }, ticks: { precision: 0 } },
                    x: { grid: { display: false } }
                }
            }
        });
    });
    </script>
</div>
