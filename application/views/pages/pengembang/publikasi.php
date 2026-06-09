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
                            <a href="<?= base_url('Index/pengembang') ?>" class="hover:text-[#d6fb00] transition-colors">Menu Pengembang</a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fa-solid fa-chevron-right text-[8px] mx-2"></i>
                            <span class="text-[#d6fb00]">Publikasi Sosial Media</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-10">
            <div class="text-left space-y-3 max-w-2xl">
                <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tighter font-jakarta">
                    Data Publikasi <span class="text-[#d6fb00]">Sosmed Perumahan</span>
                </h2>
                <p class="text-zinc-400 text-sm sm:text-base leading-relaxed">
                    Menampilkan <span class="text-[#d6fb00] font-bold"><?php echo !empty($perumahan) ? count($perumahan) : 0; ?></span> data pengembang dalam bentuk direktori resmi.
                </p>
            </div>
            <div class="w-full md:w-auto flex-shrink-0 relative">
                <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-zinc-500">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input type="text" id="tableSearch" placeholder="Cari nama perumahan, pengembang..." class="w-full md:w-80 bg-[#0f2a30] border border-[#d6fb00]/20 rounded-xl pl-10 pr-4 py-3 text-xs font-medium text-white placeholder-zinc-500 focus:outline-none focus:ring-1 focus:ring-[#d6fb00] focus:border-[#d6fb00] transition-all shadow-inner">
            </div>
        </div>

        <!-- Table Container -->
        <div class="w-full overflow-hidden rounded-[24px] bg-[#0f2a30] border border-[#d6fb00]/20 shadow-2xl shadow-black/40">
            <div class="w-full overflow-x-auto">
                <table class="w-full min-w-[800px] border-collapse text-left text-xs sm:text-sm" id="dataTable">
                    <thead>
                        <tr class="bg-[#d6fb00]/10 border-b border-[#d6fb00]/20 text-[#ecffb6] text-[11px] font-bold uppercase tracking-widest">
                            <th scope="col" class="px-6 py-5 text-center w-12 whitespace-nowrap">No</th>
                            <th scope="col" class="px-6 py-5 whitespace-nowrap">Nama Perumahan / Pengembang</th>
                            <th scope="col" class="px-6 py-5 whitespace-nowrap">Kabupaten / Kota</th>
                            <th scope="col" class="px-6 py-5 text-center whitespace-nowrap">Asosiasi</th>
                            <th scope="col" class="px-6 py-5 text-center whitespace-nowrap">Sosial Media</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#d6fb00]/10">
                        <?php if(!empty($perumahan)): ?>
                            <?php $no = 1; foreach($perumahan as $row): ?>
                            <tr class="table-row hover:bg-[#d6fb00]/5 transition-colors group">
                                <td class="px-6 py-4.5 text-center font-bold text-zinc-500 group-hover:text-zinc-400 whitespace-nowrap text-xs transition-colors">
                                    <?php echo $no++; ?>
                                </td>
                                
                                <td class="px-6 py-4.5 whitespace-nowrap">
                                    <div class="font-bold text-white tracking-tight text-sm"><?php echo htmlspecialchars($row->nama_perumahan); ?></div>
                                    <div class="text-[10px] text-[#d6fb00] font-semibold uppercase tracking-widest mt-0.5"><?php echo htmlspecialchars($row->pengembang); ?></div>
                                </td>
                                
                                <td class="px-6 py-4.5 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1.5 font-bold text-zinc-400 group-hover:text-zinc-300 text-xs transition-colors">
                                        <i class="fa-solid fa-location-dot text-cyan-400 text-[10px]"></i>
                                        <?php echo htmlspecialchars($row->kabupaten_kota); ?>
                                    </span>
                                </td>
                                
                                <td class="px-6 py-4.5 text-center whitespace-nowrap">
                                    <span class="px-2.5 py-1 text-[10px] font-bold tracking-wider border border-[#d6fb00]/20 bg-[#d6fb00]/5 text-[#d6fb00] rounded-md uppercase">
                                        <?php echo !empty($row->asosiasi) ? htmlspecialchars($row->asosiasi) : '-'; ?>
                                    </span>
                                </td>
                                
                                <td class="px-6 py-4.5 text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-2">
                                        
                                        <?php if(!empty($row->instagram)): ?>
                                            <a href="<?php echo htmlspecialchars($row->instagram); ?>" target="_blank" title="Instagram" class="w-8 h-8 inline-flex items-center justify-center bg-pink-500/10 text-pink-400 hover:bg-pink-500 hover:text-white border border-pink-500/20 rounded-xl transition-all shadow-[0_0_10px_rgba(236,72,153,0.1)] hover:shadow-[0_0_15px_rgba(236,72,153,0.4)]">
                                                <i class="fa-brands fa-instagram text-sm"></i>
                                            </a>
                                        <?php else: ?>
                                            <span title="Tidak Tersedia" class="w-8 h-8 inline-flex items-center justify-center bg-zinc-800/50 text-zinc-600 border border-zinc-800 rounded-xl cursor-not-allowed">
                                                <i class="fa-brands fa-instagram text-sm"></i>
                                            </span>
                                        <?php endif; ?>

                                        <?php if(!empty($row->facebook)): ?>
                                            <a href="<?php echo htmlspecialchars($row->facebook); ?>" target="_blank" title="Facebook" class="w-8 h-8 inline-flex items-center justify-center bg-blue-500/10 text-blue-400 hover:bg-blue-500 hover:text-white border border-blue-500/20 rounded-xl transition-all shadow-[0_0_10px_rgba(59,130,246,0.1)] hover:shadow-[0_0_15px_rgba(59,130,246,0.4)]">
                                                <i class="fa-brands fa-facebook-f text-sm"></i>
                                            </a>
                                        <?php else: ?>
                                            <span title="Tidak Tersedia" class="w-8 h-8 inline-flex items-center justify-center bg-zinc-800/50 text-zinc-600 border border-zinc-800 rounded-xl cursor-not-allowed">
                                                <i class="fa-brands fa-facebook-f text-sm"></i>
                                            </span>
                                        <?php endif; ?>

                                        <?php if(!empty($row->website)): ?>
                                            <a href="<?php echo htmlspecialchars($row->website); ?>" target="_blank" title="Website Resmi" class="w-8 h-8 inline-flex items-center justify-center bg-cyan-500/10 text-cyan-400 hover:bg-cyan-500 hover:text-white border border-cyan-500/20 rounded-xl transition-all shadow-[0_0_10px_rgba(6,182,212,0.1)] hover:shadow-[0_0_15px_rgba(6,182,212,0.4)]">
                                                <i class="fa-solid fa-globe text-sm"></i>
                                            </a>
                                        <?php else: ?>
                                            <span title="Tidak Tersedia" class="w-8 h-8 inline-flex items-center justify-center bg-zinc-800/50 text-zinc-600 border border-zinc-800 rounded-xl cursor-not-allowed">
                                                <i class="fa-solid fa-globe text-sm"></i>
                                            </span>
                                        <?php endif; ?>

                                        <?php if(!empty($row->youtube)): ?>
                                            <a href="<?php echo htmlspecialchars($row->youtube); ?>" target="_blank" title="YouTube Kanal" class="w-8 h-8 inline-flex items-center justify-center bg-red-500/10 text-red-400 hover:bg-red-500 hover:text-white border border-red-500/20 rounded-xl transition-all shadow-[0_0_10px_rgba(239,68,68,0.1)] hover:shadow-[0_0_15px_rgba(239,68,68,0.4)]">
                                                <i class="fa-brands fa-youtube text-sm"></i>
                                            </a>
                                        <?php else: ?>
                                            <span title="Tidak Tersedia" class="w-8 h-8 inline-flex items-center justify-center bg-zinc-800/50 text-zinc-600 border border-zinc-800 rounded-xl cursor-not-allowed">
                                                <i class="fa-brands fa-youtube text-sm"></i>
                                            </span>
                                        <?php endif; ?>

                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center text-zinc-500">
                                        <i class="fa-solid fa-folder-open text-3xl mb-3 text-zinc-600"></i>
                                        <p class="font-medium text-xs">Belum ada data publikasi sosial media perumahan.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <?php if(!empty($perumahan)): ?>
            <!-- Pagination Controls -->
            <div class="flex items-center justify-between border-t border-[#d6fb00]/10 bg-[#0a1a1f]/50 p-4 sm:px-6 text-zinc-400 text-[11px] sm:text-xs font-bold">
                <button id="btnPrev" class="flex items-center gap-2 px-3 sm:px-4 py-2 sm:py-2.5 border border-[#d6fb00]/20 bg-[#0f2a30] hover:bg-[#d6fb00]/10 hover:text-[#d6fb00] text-zinc-400 rounded-xl transition-all cursor-pointer disabled:opacity-40 disabled:hover:bg-[#0f2a30] disabled:hover:text-zinc-400 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-arrow-left"></i> <span class="hidden sm:inline">Sebelumnya</span>
                </button>
                <span id="pageInfo" class="text-zinc-500 font-medium">Halaman 1</span>
                <button id="btnNext" class="flex items-center gap-2 px-3 sm:px-4 py-2 sm:py-2.5 border border-[#d6fb00]/20 bg-[#0f2a30] hover:bg-[#d6fb00]/10 hover:text-[#d6fb00] text-zinc-400 rounded-xl transition-all cursor-pointer disabled:opacity-40 disabled:hover:bg-[#0f2a30] disabled:hover:text-zinc-400 disabled:cursor-not-allowed">
                    <span class="hidden sm:inline">Selanjutnya</span> <i class="fa-solid fa-arrow-right"></i>
                </button>
            </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<script>
let currentPage = 1;
const rowsPerPage = 10; // Menampilkan 10 data per halaman tabel
const rows = document.querySelectorAll('.table-row');

function displayTable(page) {
    if(rows.length === 0) return;
    
    let start = (page - 1) * rowsPerPage;
    let end = start + rowsPerPage;

    rows.forEach((row, index) => {
        if (index >= start && index < end) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });

    const totalPages = Math.ceil(rows.length / rowsPerPage);
    document.getElementById('pageInfo').innerText = `Halaman ${page} dari ${totalPages}`;
    
    document.getElementById('btnPrev').disabled = (page === 1);
    document.getElementById('btnNext').disabled = (page === totalPages);
}

// Event Klik Navigasi Pagination
const btnPrev = document.getElementById('btnPrev');
const btnNext = document.getElementById('btnNext');

if(btnPrev) {
    btnPrev.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            displayTable(currentPage);
        }
    });
}

if(btnNext) {
    btnNext.addEventListener('click', () => {
        const totalPages = Math.ceil(rows.length / rowsPerPage);
        if (currentPage < totalPages) {
            currentPage++;
            displayTable(currentPage);
        }
    });
}

// Fitur Live Search Bar
const tableSearch = document.getElementById('tableSearch');
if(tableSearch) {
    tableSearch.addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        
        if(filter === "") {
            displayTable(currentPage);
            return;
        }
        
        let foundAny = false;
        rows.forEach(row => {
            let text = row.innerText.toLowerCase();
            if(text.includes(filter)) {
                row.style.display = "";
                foundAny = true;
            } else {
                row.style.display = "none";
            }
        });
        
        if(btnPrev) btnPrev.disabled = true;
        if(btnNext) btnNext.disabled = true;
        
        const pageInfo = document.getElementById('pageInfo');
        if(pageInfo) pageInfo.innerText = foundAny ? "Hasil Pencarian..." : "Data tidak ditemukan";
    });
}

// Eksekusi sistem saat halaman di-render pertama kali
displayTable(currentPage);
</script>
