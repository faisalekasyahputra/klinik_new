<section class="py-10 px-4 md:px-10">
    <div class="max-w-6xl mx-auto">
        <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
            <h2 class="text-2xl md:text-3xl font-black text-white tracking-tight">
                Data Pengembang <span class="text-[#d6fb00]">Perumahan</span>
            </h2>
            <p class="text-zinc-400 mt-2">
                Data Resmi Pengembang di Jawa Tengah.
            </p>
        </div>

        <div class="mb-4">
            <input type="text" id="searchInput" onkeyup="searchTable()" 
                   placeholder="Cari nama perumahan atau pengembang..." 
                   class="w-full md:w-1/3 px-4 py-2 bg-slate-900 border border-slate-700 rounded-lg text-sm text-white focus:ring-2 focus:ring-indigo-500 outline-none">
        </div>

        <div class="bg-slate-900 rounded-2xl shadow-lg border border-slate-800 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-800" id="devTable">
                    <thead class="bg-slate-950">
                        <tr>
                            
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase">Pengembang</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase">Asosiasi</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase">Kabupaten</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-400 uppercase">Kontak</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        <?php foreach($developers as $dev): ?>
                        <tr class="hover:bg-slate-800/50 transition-colors">
                            
                            <td class="px-6 py-4 text-sm font-semibold text-slate-100"><?= $dev['pengembang'] ?></td>
                            <td class="px-6 py-4 text-sm">
                                <span class="px-2.5 py-0.5 bg-indigo-900/50 text-indigo-300 rounded-md font-medium text-[10px]"><?= $dev['asosiasi'] ?></span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-400"><?= $dev['kabupaten'] ?></td>
                            <td class="px-6 py-4 text-sm">
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $dev['telepon']) ?>" target="_blank" class="text-emerald-400 hover:text-emerald-300 font-medium">WhatsApp</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="flex justify-center mt-12">
            <a href="<?= base_url('Index/Umum') ?>" class="group flex items-center gap-2.5 bg-[#d6fb00]/5 hover:bg-[#d6fb00]/8 border border-[#d6fb00]/20 hover:border-[#d6fb00]/20 px-6 py-3 rounded-xl text-xs font-semibold text-zinc-400 hover:text-white transition-all duration-300 shadow-xl backdrop-blur-md">
                <i class="fa-solid fa-arrow-left transition-transform group-hover:-translate-x-1"></i>
                 <span>Kembali ke Halaman Umum</span>
            </a>

            </div>
    </div>
</section>
<script>
function searchTable() {
    let filter = document.getElementById("searchInput").value.toLowerCase();
    let rows = document.querySelectorAll("#devTable tbody tr");
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
}
</script>