<section class="w-full max-w-6xl mx-auto px-4 py-8 text-left bg-white text-zinc-800 min-h-screen">
    
    <div class="border-b border-zinc-200 pb-6 mb-8">
        <span class="text-[#c2e600] font-extrabold text-[11px] uppercase tracking-widest block mb-1.5">Sistem Informasi Publik Disperakim Jateng</span>
        <h2 class="text-2xl sm:text-3xl font-black text-zinc-900 tracking-tight">Data Sosial Media Perumahan</h2>
        <p class="text-zinc-500 text-xs sm:text-sm mt-1">Menampilkan <?php echo count($perumahan); ?> data pengembang dalam bentuk tabel direktori resmi wilayah Jawa Tengah.</p>
    </div>

    <div class="flex flex-col md:flex-row gap-4 mb-6 justify-between items-center">
        <div class="relative w-full md:max-w-md">
            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-zinc-400">
                <i class="fa-solid fa-magnifying-glass text-xs"></i>
            </span>
            <input type="text" id="tableSearch" placeholder="Cari nama perumahan, kota, pengembang..." class="w-full bg-zinc-50 border border-zinc-200 rounded-2xl pl-10 pr-4 py-3 text-sm font-medium text-zinc-800 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00] transition-all shadow-sm">
        </div>
    </div>

    <div class="w-full overflow-x-auto bg-white rounded-3xl border border-zinc-200 shadow-sm">
        <table class="w-full text-sm text-left border-collapse text-zinc-600 min-w-[800px]" id="dataTable">
            <thead>
                <tr class="bg-zinc-50 text-zinc-700 font-bold border-b border-zinc-200 text-xs uppercase tracking-wider">
                    <th scope="col" class="px-6 py-4 text-center w-12 text-zinc-400">No</th>
                    <th scope="col" class="px-6 py-4 text-zinc-900">Nama Perumahan / Pengembang</th>
                    <th scope="col" class="px-6 py-4 w-44 text-zinc-900">Kabupaten / Kota</th>
                    <th scope="col" class="px-6 py-4 text-center w-28 text-zinc-900">Asosiasi</th>
                    <th scope="col" class="px-6 py-4 text-center w-52 text-zinc-900">Tautan Sosial Media</th>
                </tr>
            </thead>
            
            <tbody class="divide-y divide-zinc-100">
                <?php if(!empty($perumahan)): ?>
                    <?php $no = 1; foreach($perumahan as $row): ?>
                    <tr class="table-row hover:bg-zinc-50/80 transition-colors odd:bg-white even:bg-zinc-50/40">
                        <td class="px-6 py-4.5 text-center font-bold text-zinc-400 text-xs">
                            <?php echo $no++; ?>
                        </td>
                        
                        <td class="px-6 py-4.5">
                            <div class="font-black text-zinc-900 tracking-tight text-sm"><?php echo $row->nama_perumahan; ?></div>
                            <div class="text-[11px] text-zinc-400 font-semibold uppercase tracking-wide mt-0.5"><?php echo $row->pengembang; ?></div>
                        </td>
                        
                        <td class="px-6 py-4.5 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1.5 font-bold text-zinc-700 text-xs">
                                <i class="fa-solid fa-location-dot text-[#d6fb00] text-[10px]"></i>
                                <?php echo $row->kabupaten_kota; ?>
                            </span>
                        </td>
                        
                        <td class="px-6 py-4.5 text-center whitespace-nowrap">
                            <span class="px-2.5 py-1 text-[10px] font-black tracking-wider border border-zinc-200 bg-zinc-50 text-zinc-700 rounded-md">
                                <?php echo !empty($row->asosiasi) ? $row->asosiasi : '-'; ?>
                            </span>
                        </td>
                        
                        <td class="px-6 py-4.5 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                
                                <?php if(!empty($row->instagram)): ?>
                                    <a href="<?php echo $row->instagram; ?>" target="_blank" title="Instagram" class="w-8 h-8 inline-flex items-center justify-center bg-pink-50 text-pink-600 hover:bg-pink-600 hover:text-white border border-pink-100 rounded-xl transition-all shadow-sm">
                                        <i class="fa-brands fa-instagram text-sm"></i>
                                    </a>
                                <?php else: ?>
                                    <span title="Tidak Tersedia" class="w-8 h-8 inline-flex items-center justify-center bg-zinc-100 text-zinc-300 border border-zinc-200 rounded-xl cursor-not-allowed">
                                        <i class="fa-brands fa-instagram text-sm"></i>
                                    </span>
                                <?php endif; ?>

                                <?php if(!empty($row->facebook)): ?>
                                    <a href="<?php echo $row->facebook; ?>" target="_blank" title="Facebook" class="w-8 h-8 inline-flex items-center justify-center bg-blue-50 text-blue-600 hover:bg-blue-600 hover:text-white border border-blue-100 rounded-xl transition-all shadow-sm">
                                        <i class="fa-brands fa-facebook-f text-sm"></i>
                                    </a>
                                <?php else: ?>
                                    <span title="Tidak Tersedia" class="w-8 h-8 inline-flex items-center justify-center bg-zinc-100 text-zinc-300 border border-zinc-200 rounded-xl cursor-not-allowed">
                                        <i class="fa-brands fa-facebook-f text-sm"></i>
                                    </span>
                                <?php endif; ?>

                                <?php if(!empty($row->website)): ?>
                                    <a href="<?php echo $row->website; ?>" target="_blank" title="Website Resmi" class="w-8 h-8 inline-flex items-center justify-center bg-cyan-50 text-cyan-600 hover:bg-cyan-600 hover:text-white border border-cyan-100 rounded-xl transition-all shadow-sm">
                                        <i class="fa-solid fa-globe text-sm"></i>
                                    </a>
                                <?php else: ?>
                                    <span title="Tidak Tersedia" class="w-8 h-8 inline-flex items-center justify-center bg-zinc-100 text-zinc-300 border border-zinc-200 rounded-xl cursor-not-allowed">
                                        <i class="fa-solid fa-globe text-sm"></i>
                                    </span>
                                <?php endif; ?>

                                <?php if(!empty($row->youtube)): ?>
                                    <a href="<?php echo $row->youtube; ?>" target="_blank" title="YouTube Kanal" class="w-8 h-8 inline-flex items-center justify-center bg-red-50 text-red-600 hover:bg-red-600 hover:text-white border border-red-100 rounded-xl transition-all shadow-sm">
                                        <i class="fa-brands fa-youtube text-sm"></i>
                                    </a>
                                <?php else: ?>
                                    <span title="Tidak Tersedia" class="w-8 h-8 inline-flex items-center justify-center bg-zinc-100 text-zinc-300 border border-zinc-200 rounded-xl cursor-not-allowed">
                                        <i class="fa-brands fa-youtube text-sm"></i>
                                    </span>
                                <?php endif; ?>

                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center font-bold text-zinc-400 italic bg-white">
                            <i class="fa-solid fa-folder-open text-zinc-300 text-2xl block mb-2"></i> Belum ada data perumahan yang tersimpan.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="flex items-center justify-between border-t border-zinc-200 mt-6 pt-6 text-zinc-700 text-xs font-bold">
        <button id="btnPrev" class="flex items-center gap-2 px-4 py-2.5 border border-zinc-200 bg-white hover:bg-zinc-50 text-zinc-700 rounded-xl transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
            <i class="fa-solid fa-arrow-left"></i> Sebelumnya
        </button>
        <span id="pageInfo" class="text-zinc-500 font-medium">Halaman 1 dari 1</span>
        <button id="btnNext" class="flex items-center gap-2 px-4 py-2.5 border border-zinc-200 bg-white hover:bg-zinc-50 text-zinc-700 rounded-xl transition-colors cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed">
            Selanjutnya <i class="fa-solid fa-arrow-right"></i>
        </button>
    </div>
<div class="flex justify-center mt-12">
            <a href="<?= base_url('Index/pengembang') ?>" class="group flex items-center gap-2.5 bg-[#d6fb00]/5 hover:bg-[#d6fb00]/8 border border-[#d6fb00]/20 hover:border-[#d6fb00]/20 px-6 py-3 rounded-xl text-xs font-semibold text-zinc-400 hover:text-white transition-all duration-300 shadow-xl backdrop-blur-md">
                <i class="fa-solid fa-arrow-left transition-transform group-hover:-translate-x-1"></i>
                <span>Kembali ke Halaman Pengembang</span>
            </a>

            </div>
</section>

<script>
let currentPage = 1;
const rowsPerPage = 10; // Menampilkan 10 data per halaman tabel
const rows = document.querySelectorAll('.table-row');

function displayTable(page) {
    let start = (page - 1) * rowsPerPage;
    let end = start + rowsPerPage;

    rows.forEach((row, index) => {
        if (index >= start && index < end) {
            row.style.style.clear = ""; 
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
document.getElementById('btnPrev').addEventListener('click', () => {
    if (currentPage > 1) {
        currentPage--;
        displayTable(currentPage);
    }
});

document.getElementById('btnNext').addEventListener('click', () => {
    const totalPages = Math.ceil(rows.length / rowsPerPage);
    if (currentPage < totalPages) {
        currentPage++;
        displayTable(currentPage);
    }
});

// Fitur Live Search Bar (Aman, Tidak Tabrakan dengan Sistem Pagination)
document.getElementById('tableSearch').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    
    if(filter === "") {
        displayTable(currentPage);
        return;
    }
    
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
    
    document.getElementById('pageInfo').innerText = "Hasil Filter Pencarian...";
    document.getElementById('btnPrev').disabled = true;
    document.getElementById('btnNext').disabled = true;
});

// Eksekusi sistem saat halaman di-render pertama kali
displayTable(currentPage);
</script>