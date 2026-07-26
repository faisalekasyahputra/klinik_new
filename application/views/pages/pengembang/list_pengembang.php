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
                            <a href="<?= base_url('umum') ?>" class="hover:text-[#d6fb00] transition-colors">Layanan Umum</a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fa-solid fa-chevron-right text-[8px] mx-2"></i>
                            <span class="text-[#d6fb00]">Informasi Pengembang</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

<div class="py-10 px-4 md:px-10">
    <div class="max-w-6xl mx-auto">
        <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
            <h2 class="text-2xl md:text-3xl font-black text-white tracking-tight">
                Data Pengembang <span class="text-[#d6fb00]">Perumahan</span>
            </h2>
            <p class="text-zinc-400 mt-2">
                Data Resmi Pengembang di Jawa Tengah.
            </p>
        </div>

        <div class="mb-6 relative w-full md:w-1/2 lg:w-1/3">
            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                <i class="fa-solid fa-magnifying-glass text-[#d6fb00]/50"></i>
            </div>
            <input type="text" id="searchInput" onkeyup="searchTable()" 
                   placeholder="Cari nama perumahan atau pengembang..." 
                   class="w-full pl-11 pr-4 py-3 bg-[#0f2a30] border border-[#d6fb00]/20 rounded-xl text-sm text-white focus:ring-2 focus:ring-[#d6fb00]/20 focus:border-[#d6fb00]/50 outline-none transition-all shadow-inner placeholder-zinc-500">
        </div>

        <div class="bg-[#0f2a30]/80 backdrop-blur-xl rounded-[2rem] shadow-2xl shadow-[#d6fb00]/5 border border-[#d6fb00]/20 p-1">
            <div class="bg-[#0a1a1f] rounded-[1.8rem] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-[#d6fb00]/10" id="devTable">
                        <thead class="bg-gradient-to-r from-[#0f2a30] to-[#0a1a1f] border-b border-[#d6fb00]/20">
                            <tr>
                                <th onclick="sortTable(0)" class="cursor-pointer hover:bg-[#d6fb00]/10 transition-colors px-8 py-5 text-left text-[11px] font-black tracking-widest text-[#d6fb00] uppercase select-none"><div class="flex items-center gap-2"><i class="fa-solid fa-building text-[#d6fb00]/50"></i> <span>Nama Pengembang</span> <i class="fa-solid fa-sort text-zinc-600 ml-auto sort-icon" id="sortIcon0"></i></div></th>
                                <th onclick="sortTable(1)" class="cursor-pointer hover:bg-[#d6fb00]/10 transition-colors px-8 py-5 text-left text-[11px] font-black tracking-widest text-[#d6fb00] uppercase select-none"><div class="flex items-center gap-2"><span>Asosiasi</span> <i class="fa-solid fa-sort text-zinc-600 ml-auto sort-icon" id="sortIcon1"></i></div></th>
                                <th onclick="sortTable(2)" class="cursor-pointer hover:bg-[#d6fb00]/10 transition-colors px-8 py-5 text-left text-[11px] font-black tracking-widest text-[#d6fb00] uppercase select-none"><div class="flex items-center gap-2"><i class="fa-solid fa-map-location-dot text-[#d6fb00]/50"></i> <span>Kabupaten</span> <i class="fa-solid fa-sort text-zinc-600 ml-auto sort-icon" id="sortIcon2"></i></div></th>
                                <th onclick="sortTable(3)" class="cursor-pointer hover:bg-[#d6fb00]/10 transition-colors px-8 py-5 text-left text-[11px] font-black tracking-widest text-[#d6fb00] uppercase select-none"><div class="flex items-center gap-2"><i class="fa-solid fa-file-contract text-[#d6fb00]/50"></i> <span>Status SP2</span> <i class="fa-solid fa-sort text-zinc-600 ml-auto sort-icon" id="sortIcon3"></i></div></th>
                                <th class="px-8 py-5 text-center text-[11px] font-black tracking-widest text-[#d6fb00] uppercase">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#d6fb00]/10 bg-transparent">
                            <?php foreach($developers as $dev): ?>
                            <tr class="hover:bg-[#d6fb00]/5 transition-all duration-300 group">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-4">
                                        <div class="w-10 h-10 rounded-full bg-[#d6fb00]/10 flex items-center justify-center border border-[#d6fb00]/20 text-[#d6fb00] group-hover:bg-[#d6fb00] group-hover:text-black transition-colors shrink-0">
                                            <i class="fa-solid fa-industry text-sm"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm font-bold text-white group-hover:text-[#d6fb00] transition-colors"><?= $dev['pengembang'] ?></div>
                                            <div class="text-[10px] text-zinc-500 font-medium mt-0.5"><i class="fa-solid fa-house-chimney text-[8px] mr-1"></i><?= $dev['nama_perumahan'] ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5 text-sm">
                                    <span class="inline-flex items-center px-3 py-1 bg-[#0a1a1f] text-[#d6fb00] border border-[#d6fb00]/20 rounded-lg font-bold text-[10px] uppercase tracking-wider shadow-sm group-hover:border-[#d6fb00]/40 transition-colors">
                                        <i class="fa-solid fa-users text-[#d6fb00]/60 mr-1.5"></i> <?= $dev['asosiasi'] ?>
                                    </span>
                                </td>
                                <td class="px-8 py-5 text-sm">
                                    <span class="text-zinc-400 font-semibold group-hover:text-zinc-300 transition-colors"><?= $dev['kabupaten'] ?></span>
                                </td>
                                <td class="px-8 py-5 text-sm">
                                    <?php // Penanda kini dari direktori resmi (status_aktif = 1), bukan
                                          // dari keberadaan baris pengajuan berstatus apa pun. NIB tidak
                                          // lagi ditampilkan — kolom itu milik baris pengajuan dan hampir
                                          // selalu kosong; echo lamanya juga tanpa escape. ?>
                                    <?php if (empty($dev['sp2_tersertifikasi'])): ?>
                                        <span class="inline-flex items-center px-3 py-1 bg-zinc-500/10 text-zinc-400 border border-zinc-500/20 rounded-lg font-bold text-[10px] uppercase tracking-wider shadow-sm transition-colors">
                                            <i class="fa-solid fa-circle-minus mr-1.5"></i> Belum Terdata
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-3 py-1 bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 rounded-lg font-bold text-[10px] uppercase tracking-wider shadow-sm group-hover:border-emerald-500/40 transition-colors">
                                            <i class="fa-solid fa-circle-check mr-1.5"></i> Tersertifikasi SRP2
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-8 py-5 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $dev['telepon']) ?>" target="_blank" class="inline-flex items-center justify-center gap-2 bg-emerald-500/10 hover:bg-emerald-500 text-emerald-500 hover:text-white px-4 py-2 rounded-xl font-bold transition-all duration-300 border border-emerald-500/20 hover:border-emerald-500 hover:shadow-lg hover:shadow-emerald-500/20 hover:-translate-y-0.5">
                                            <i class="fa-brands fa-whatsapp text-lg"></i> <span>Hubungi</span>
                                        </a>
                                        <a href="<?= base_url('Umum/detail_pengembang') . '?nama=' . urlencode($dev['pengembang']) ?>" class="inline-flex items-center justify-center gap-2 bg-[#d6fb00]/10 hover:bg-[#d6fb00] text-[#d6fb00] hover:text-black px-4 py-2 rounded-xl font-bold transition-all duration-300 border border-[#d6fb00]/20 hover:border-[#d6fb00] hover:shadow-lg hover:shadow-[#d6fb00]/20 hover:-translate-y-0.5">
                                            <i class="fa-solid fa-circle-info text-lg"></i> <span>Detail</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination Controls -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-8 px-4" id="paginationWrapper">
            <div class="text-xs text-zinc-400 font-medium">
                Menampilkan <span id="pageStart" class="text-white font-bold">0</span> - <span id="pageEnd" class="text-white font-bold">0</span> dari <span id="totalRows" class="text-[#d6fb00] font-bold">0</span> pengembang
            </div>
            <div class="flex items-center gap-1.5" id="paginationControls">
                <!-- Pagination buttons will be generated here -->
            </div>
        </div>

    </div>
</div>

    </div>
</section>
<script>
let currentPage = 1;
let rowsPerPage = 10;
let filteredRows = [];
let sortCol = -1;
let sortAsc = true;

const table = document.getElementById("devTable");
const tbody = table.querySelector("tbody");
// Convert nodelist to real array of original rows
const allRows = Array.from(tbody.querySelectorAll("tr"));

function initTable() {
    filteredRows = [...allRows];
    document.getElementById("searchInput").addEventListener("input", function() {
        let filter = this.value.toLowerCase();
        filteredRows = allRows.filter(row => {
            return row.innerText.toLowerCase().includes(filter);
        });
        currentPage = 1;
        renderTable();
    });
    renderTable();
}

function sortTable(colIndex) {
    if (sortCol === colIndex) {
        sortAsc = !sortAsc;
    } else {
        sortCol = colIndex;
        sortAsc = true;
    }

    // Reset icons
    document.querySelectorAll('.sort-icon').forEach((icon, idx) => {
        icon.className = 'fa-solid fa-sort text-zinc-600 ml-auto sort-icon';
        if (idx === colIndex) {
            icon.className = sortAsc ? 'fa-solid fa-sort-up text-[#d6fb00] ml-auto sort-icon' : 'fa-solid fa-sort-down text-[#d6fb00] ml-auto sort-icon';
        }
    });

    filteredRows.sort((a, b) => {
        let aText = a.cells[colIndex].innerText.trim().toLowerCase();
        let bText = b.cells[colIndex].innerText.trim().toLowerCase();
        
        if (aText < bText) return sortAsc ? -1 : 1;
        if (aText > bText) return sortAsc ? 1 : -1;
        return 0;
    });

    currentPage = 1;
    renderTable();
}

function renderTable() {
    tbody.innerHTML = '';
    
    const total = filteredRows.length;
    const totalPages = Math.ceil(total / rowsPerPage);
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const start = (currentPage - 1) * rowsPerPage;
    const end = Math.min(start + rowsPerPage, total);

    for (let i = start; i < end; i++) {
        tbody.appendChild(filteredRows[i]);
    }

    document.getElementById('totalRows').innerText = total;
    document.getElementById('pageStart').innerText = total === 0 ? 0 : start + 1;
    document.getElementById('pageEnd').innerText = end;

    renderPagination(totalPages);
}

function renderPagination(totalPages) {
    const controls = document.getElementById('paginationControls');
    controls.innerHTML = '';

    if (totalPages <= 1) return;

    // Prev Button
    const prevBtn = document.createElement('button');
    prevBtn.innerHTML = '<i class="fa-solid fa-chevron-left"></i>';
    prevBtn.className = `w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold transition-all ${currentPage === 1 ? 'bg-[#d6fb00]/5 text-zinc-600 cursor-not-allowed' : 'bg-[#0f2a30] text-[#d6fb00] border border-[#d6fb00]/20 hover:bg-[#d6fb00]/10 shadow-lg'}`;
    prevBtn.disabled = currentPage === 1;
    prevBtn.onclick = () => { currentPage--; renderTable(); };
    controls.appendChild(prevBtn);

    // Page Numbers (Simple window)
    let startPage = Math.max(1, currentPage - 2);
    let endPage = Math.min(totalPages, startPage + 4);
    if (endPage - startPage < 4) {
        startPage = Math.max(1, endPage - 4);
    }

    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.innerText = i;
        pageBtn.className = `w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold transition-all ${currentPage === i ? 'bg-[#d6fb00] text-black shadow-[0_0_15px_rgba(214,251,0,0.3)]' : 'bg-[#0f2a30] text-zinc-400 border border-[#d6fb00]/20 hover:text-white hover:bg-[#d6fb00]/10'}`;
        pageBtn.onclick = () => { currentPage = i; renderTable(); };
        controls.appendChild(pageBtn);
    }

    // Next Button
    const nextBtn = document.createElement('button');
    nextBtn.innerHTML = '<i class="fa-solid fa-chevron-right"></i>';
    nextBtn.className = `w-8 h-8 rounded-lg flex items-center justify-center text-xs font-bold transition-all ${currentPage === totalPages ? 'bg-[#d6fb00]/5 text-zinc-600 cursor-not-allowed' : 'bg-[#0f2a30] text-[#d6fb00] border border-[#d6fb00]/20 hover:bg-[#d6fb00]/10 shadow-lg'}`;
    nextBtn.disabled = currentPage === totalPages;
    nextBtn.onclick = () => { currentPage++; renderTable(); };
    controls.appendChild(nextBtn);
}

document.addEventListener("DOMContentLoaded", initTable);
</script>
