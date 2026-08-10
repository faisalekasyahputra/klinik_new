<?php
// MAPPING DATA UNTUK ALPINE.JS
$queue_mapped = [];
if (isset($queue) && !empty($queue)) {
    foreach ($queue as $row) {
        $data_survey = json_decode($row->data_survey_json, true) ?: [];
        $data_simperum = json_decode($row->data_simperum_json, true) ?: [];

        $penghasilan = isset($data_survey['penghasilan']) ? $data_survey['penghasilan'] : '-';
        if (is_numeric($penghasilan)) {
            $penghasilan = 'Rp ' . number_format((float)$penghasilan, 0, ',', '.');
        }

        $pekerjaan = isset($data_survey['pekerjaan']) ? $data_survey['pekerjaan'] : '-';
        $alasan = isset($data_survey['alasan_pengajuan']) ? $data_survey['alasan_pengajuan'] : '-';
        $desil = isset($data_simperum['desil']) ? $data_simperum['desil'] : '-';

        $queue_mapped[] = [
            'id' => $row->id,
            'created_at' => $row->created_at,
            'nama_lengkap' => $row->nama_lengkap,
            'nik_pengaju' => $row->nik_pengaju,
            'nama_program' => $row->nama_program ?? 'Program',
            'desil' => $desil,
            'penghasilan' => $penghasilan,
            'pekerjaan' => $pekerjaan,
            'alasan' => $alasan,
            'status_antrean' => $row->status_antrean,
            'catatan_admin' => $row->catatan_admin,
        ];
    }
}
?>

<div x-data="adminKabkotaDashboard()" class="relative z-10">
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2 flex items-center gap-3">
                <i class="ph ph-map-pin text-brand-primary"></i>
                Antrean Perumahan - <?= html_escape($kabupaten_nama ?: 'Wilayah Saya') ?>
            </h1>
            <p class="text-sm text-gray-500 dark:text-brand-muted">Kelola antrean pengajuan program perumahan warga di wilayah Anda.</p>
        </div>

        <div class="flex items-center gap-4">
            <div class="bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/5 rounded-2xl px-5 py-3 flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-yellow-500/10 flex items-center justify-center text-yellow-500">
                    <i class="ph ph-clock"></i>
                </div>
                <div>
                    <div class="text-xs text-gray-500 dark:text-brand-muted font-medium uppercase tracking-wider mb-0.5">Pending</div>
                    <div class="text-xl font-bold text-gray-900 dark:text-white leading-none">
                        <?php
                            $pending_count = 0;
                            foreach ($queue_mapped as $q) { if ($q['status_antrean'] === 'pending') $pending_count++; }
                            echo $pending_count;
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-brand-card border border-gray-200 dark:border-white/10 rounded-3xl overflow-hidden shadow-sm flex flex-col min-h-[600px]">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-white/10 flex flex-col md:flex-row md:justify-between md:items-center bg-gray-50 dark:bg-white/5 gap-4">
            <div class="flex items-center gap-3">
                <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="ph ph-list-checks text-brand-primary"></i> Antrean Pengajuan
                </h2>
                <div class="px-2.5 py-0.5 rounded-full bg-gray-200 dark:bg-white/10 text-gray-500 dark:text-brand-muted text-[11px] font-medium">
                    <span x-text="allData.length"></span> Total
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
                <div class="relative w-full sm:w-40">
                    <select x-model="filterStatus" class="w-full bg-gray-50 dark:bg-black/30 border border-gray-200 dark:border-white/10 rounded-lg px-3 py-1.5 text-gray-800 dark:text-white text-xs focus:outline-none focus:border-brand-primary/50 focus:ring-1 focus:ring-brand-primary/50 transition-all duration-200 appearance-none cursor-pointer">
                        <option value="all">Semua Status</option>
                        <option value="pending">Menunggu (Pending)</option>
                        <option value="approved">Disetujui</option>
                        <option value="rejected">Ditolak</option>
                    </select>
                    <i class="ph ph-caret-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-brand-muted/70 pointer-events-none text-[10px]"></i>
                </div>
                <div class="relative w-full sm:w-60">
                    <input type="text" x-model="searchQuery" placeholder="Cari nama, NIK, program..." class="w-full bg-gray-50 dark:bg-black/30 border border-gray-200 dark:border-white/10 rounded-lg pl-8 pr-3 py-1.5 text-gray-800 dark:text-white text-xs focus:outline-none focus:border-brand-primary/50 focus:ring-1 focus:ring-brand-primary/50 transition-all duration-200">
                    <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-brand-muted/70 text-[10px]"></i>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto flex-1 flex flex-col">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="text-xs uppercase bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted font-bold tracking-wider">
                    <tr>
                        <th class="px-4 py-2 cursor-pointer" @click="sortBy('created_at')">Tanggal <i class="ph ml-1" :class="sortCol === 'created_at' ? (sortAsc ? 'ph-sort-ascending text-brand-primary' : 'ph-sort-descending text-brand-primary') : 'ph-caret-up-down opacity-30'"></i></th>
                        <th class="px-4 py-2 cursor-pointer" @click="sortBy('nama_lengkap')">Pemohon <i class="ph ml-1" :class="sortCol === 'nama_lengkap' ? (sortAsc ? 'ph-sort-ascending text-brand-primary' : 'ph-sort-descending text-brand-primary') : 'ph-caret-up-down opacity-30'"></i></th>
                        <th class="px-4 py-2 cursor-pointer" @click="sortBy('nama_program')">Program <i class="ph ml-1" :class="sortCol === 'nama_program' ? (sortAsc ? 'ph-sort-ascending text-brand-primary' : 'ph-sort-descending text-brand-primary') : 'ph-caret-up-down opacity-30'"></i></th>
                        <th class="px-4 py-2">Kondisi Sosial</th>
                        <th class="px-4 py-2 cursor-pointer" @click="sortBy('status_antrean')">Status <i class="ph ml-1" :class="sortCol === 'status_antrean' ? (sortAsc ? 'ph-sort-ascending text-brand-primary' : 'ph-sort-descending text-brand-primary') : 'ph-caret-up-down opacity-30'"></i></th>
                        <th class="px-4 py-2 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5 text-gray-600 dark:text-brand-muted relative">
                    <template x-if="paginatedData.length > 0">
                        <template x-for="row in paginatedData" :key="row.id">
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                <td class="px-4 py-2">
                                    <div class="text-gray-900 dark:text-white font-medium" x-text="formatDate(row.created_at)"></div>
                                    <div class="text-[10px]" x-text="formatTime(row.created_at)"></div>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="text-gray-900 dark:text-white font-bold" x-text="row.nama_lengkap"></div>
                                    <div class="text-xs font-mono mt-0.5" x-text="row.nik_pengaju"></div>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="inline-flex items-center px-2.5 py-1 rounded-lg bg-brand-primary/10 text-brand-primary border border-brand-primary/20 text-xs font-semibold mb-1" x-text="row.nama_program"></div>
                                    <div class="text-[10px] text-blue-700 bg-blue-50 dark:text-white dark:bg-blue-500/20 border border-blue-200 dark:border-blue-500/30 px-2 py-0.5 rounded inline-block" x-show="row.desil !== '-'">
                                        Desil: <span class="font-bold" x-text="row.desil"></span>
                                    </div>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="text-xs mb-1"><i class="ph ph-briefcase mr-1"></i> <span x-text="row.pekerjaan"></span></div>
                                    <div class="text-xs mb-1"><i class="ph ph-wallet mr-1"></i> <span x-text="row.penghasilan"></span></div>
                                    <div class="text-xs italic mt-1 max-w-[250px] break-words whitespace-normal leading-tight" :title="row.alasan" x-show="row.alasan !== '-'">
                                        <i class="ph ph-quotes mr-1 text-[10px]"></i> <span x-text="row.alasan"></span>
                                    </div>
                                </td>
                                <td class="px-4 py-2" x-html="renderBadge(row.status_antrean)"></td>
                                <td class="px-4 py-2 text-center">
                                    <button @click="openModal(row)" class="inline-flex items-center justify-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-brand-primary/20 dark:bg-white/5 text-gray-600 dark:text-brand-muted hover:text-brand-primary border border-gray-200 dark:border-white/10 transition-all duration-200" title="Proses">
                                        <i class="ph ph-note-pencil"></i> <span class="text-xs font-bold uppercase tracking-wider">Tinjau</span>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </template>
                    <template x-if="paginatedData.length === 0">
                        <tr>
                            <td colspan="6" class="p-0 border-none relative">
                                <div class="absolute inset-0 flex flex-col items-center justify-center text-gray-400 dark:text-brand-muted min-h-[400px]">
                                    <div class="w-20 h-20 mb-5 rounded-full bg-gray-100 dark:bg-black/40 border border-gray-200 dark:border-white/5 flex items-center justify-center text-3xl text-gray-300 dark:text-white/20">
                                        <i class="ph ph-tray"></i>
                                    </div>
                                    <p class="text-lg font-medium text-gray-500 dark:text-white/60">Data tidak ditemukan</p>
                                    <p class="text-sm mt-1" x-text="searchQuery !== '' ? 'Pencarian tidak cocok dengan data manapun.' : 'Belum ada antrean di wilayah Anda.'"></p>
                                </div>
                                <div style="height: 400px;"></div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="px-4 py-2 border-t border-gray-200 dark:border-white/5 bg-gray-50 dark:bg-black/10 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-gray-500 dark:text-brand-muted">
            <div>
                Menampilkan <span class="font-bold text-gray-900 dark:text-white" x-text="(currentPage - 1) * perPage + (paginatedData.length > 0 ? 1 : 0)"></span> -
                <span class="font-bold text-gray-900 dark:text-white" x-text="Math.min(currentPage * perPage, filteredData.length)"></span> dari
                <span class="font-bold text-gray-900 dark:text-white" x-text="filteredData.length"></span> entri
            </div>
            <div class="flex items-center gap-1" x-show="totalPages > 1">
                <button @click="prevPage()" :disabled="currentPage === 1" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-white/10 hover:bg-gray-100 dark:hover:bg-white/10 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"><i class="ph ph-caret-left"></i></button>
                <template x-for="page in pagesArray()" :key="page">
                    <button @click="currentPage = page" class="w-8 h-8 flex items-center justify-center rounded-lg border transition-colors font-medium" :class="currentPage === page ? 'bg-brand-primary/20 border-brand-primary/50 text-brand-primary' : 'border-gray-200 dark:border-white/10 hover:bg-gray-100 dark:hover:bg-white/10 text-gray-700 dark:text-white'"><span x-text="page"></span></button>
                </template>
                <button @click="nextPage()" :disabled="currentPage === totalPages" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-white/10 hover:bg-gray-100 dark:hover:bg-white/10 disabled:opacity-30 disabled:cursor-not-allowed transition-colors"><i class="ph ph-caret-right"></i></button>
            </div>
        </div>
    </div>

    <!-- Modal Proses -->
    <div x-show="modalOpen" class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6" x-cloak>
        <div x-show="modalOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal()"></div>
        <div x-show="modalOpen" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95" class="relative w-full max-w-lg bg-white dark:bg-brand-card border border-gray-200 dark:border-white/10 rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-4 py-2 border-b border-gray-200 dark:border-white/10 flex justify-between items-center bg-gray-50 dark:bg-white/5">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2"><i class="ph ph-clipboard-text text-brand-primary"></i> Proses Pengajuan</h3>
                <button @click="closeModal()" class="text-gray-400 dark:text-brand-muted hover:text-gray-700 dark:hover:text-white transition-colors"><i class="ph ph-x text-lg"></i></button>
            </div>
            <div class="p-6 overflow-y-auto custom-scrollbar">
                <form action="<?= base_url('Admin_Kabkota/update_status') ?>" method="POST" id="formProsesKabkota">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                    <input type="hidden" name="queue_id" :value="selectedData.id">
                    <div class="mb-5 bg-gray-50 dark:bg-white/5 rounded-2xl p-4 border border-gray-200 dark:border-white/5">
                        <div class="text-xs text-gray-500 dark:text-brand-muted mb-1">Pengaju</div>
                        <div class="text-gray-900 dark:text-white font-bold text-lg mb-3" x-text="selectedData.nama"></div>
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="text-xs text-gray-500 dark:text-brand-muted mb-1">Program</div>
                                <div class="inline-flex items-center px-2.5 py-1 rounded-lg bg-brand-primary/10 text-brand-primary border border-brand-primary/20 text-xs font-semibold" x-text="selectedData.program"></div>
                            </div>
                            <div class="text-right" x-show="selectedData.desil !== '-'">
                                <div class="text-xs text-gray-500 dark:text-brand-muted mb-1">Desil Kesejahteraan</div>
                                <div class="text-gray-900 dark:text-white font-bold text-lg" x-text="selectedData.desil"></div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-5">
                        <label class="block text-xs font-bold text-gray-500 dark:text-brand-muted uppercase tracking-wider mb-2">Aksi Keputusan <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="status" value="approved" class="peer sr-only" x-model="selectedData.status" required>
                                <div class="px-4 py-3 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 text-gray-600 dark:text-brand-muted text-center font-semibold transition-all duration-200 peer-checked:bg-green-50 peer-checked:border-green-400 peer-checked:text-green-700 dark:peer-checked:bg-green-500/20 dark:peer-checked:border-green-500/50 dark:peer-checked:text-green-400"><i class="ph ph-check mr-2"></i> Setujui</div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="status" value="rejected" class="peer sr-only" x-model="selectedData.status" required>
                                <div class="px-4 py-3 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 text-gray-600 dark:text-brand-muted text-center font-semibold transition-all duration-200 peer-checked:bg-red-50 peer-checked:border-red-400 peer-checked:text-red-700 dark:peer-checked:bg-red-500/20 dark:peer-checked:border-red-500/50 dark:peer-checked:text-red-400"><i class="ph ph-x mr-2"></i> Tolak</div>
                            </label>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="block text-xs font-bold text-gray-500 dark:text-brand-muted uppercase tracking-wider mb-2">Catatan Admin</label>
                        <textarea name="catatan_admin" rows="3" class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-gray-800 dark:text-white text-sm focus:outline-none focus:border-brand-primary/50 focus:ring-1 focus:ring-brand-primary/50" placeholder="Tambahkan alasan atau keterangan (opsional)..." x-model="selectedData.catatan"></textarea>
                    </div>
                </form>
            </div>
            <div class="px-4 py-2 border-t border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-black/20 flex justify-end gap-3">
                <button type="button" @click="closeModal()" class="px-5 py-2.5 rounded-xl border border-gray-200 dark:border-white/10 text-gray-700 dark:text-white text-sm font-semibold hover:bg-gray-100 dark:hover:bg-white/5">Batal</button>
                <button type="submit" form="formProsesKabkota" class="px-5 py-2.5 rounded-xl bg-brand-primary text-brand-dark text-sm font-bold hover:brightness-95">Simpan Keputusan</button>
            </div>
        </div>
    </div>
</div>

<script>
function adminKabkotaDashboard() {
    return {
        allData: <?= json_encode($queue_mapped, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
        searchQuery: '',
        filterStatus: 'all',
        sortCol: 'created_at',
        sortAsc: false,
        currentPage: 1,
        perPage: 10,
        modalOpen: false,
        selectedData: { id: '', nama: '', program: '', status: '', catatan: '', desil: '' },

        get filteredData() {
            let data = this.allData;
            if (this.filterStatus !== 'all') data = data.filter(item => item.status_antrean === this.filterStatus);
            if (this.searchQuery.trim() !== '') {
                let q = this.searchQuery.toLowerCase();
                data = data.filter(item =>
                    (item.nama_lengkap && item.nama_lengkap.toLowerCase().includes(q)) ||
                    (item.nik_pengaju && item.nik_pengaju.toLowerCase().includes(q)) ||
                    (item.nama_program && item.nama_program.toLowerCase().includes(q)) ||
                    (item.status_antrean && item.status_antrean.toLowerCase().includes(q))
                );
            }
            data = data.sort((a, b) => {
                let valA = a[this.sortCol] || '', valB = b[this.sortCol] || '';
                if (typeof valA === 'string') valA = valA.toLowerCase();
                if (typeof valB === 'string') valB = valB.toLowerCase();
                if (valA < valB) return this.sortAsc ? -1 : 1;
                if (valA > valB) return this.sortAsc ? 1 : -1;
                return 0;
            });
            return data;
        },
        get paginatedData() {
            let start = (this.currentPage - 1) * this.perPage;
            return this.filteredData.slice(start, start + this.perPage);
        },
        get totalPages() { return Math.ceil(this.filteredData.length / this.perPage) || 1; },
        sortBy(col) { if (this.sortCol === col) { this.sortAsc = !this.sortAsc; } else { this.sortCol = col; this.sortAsc = true; } },
        prevPage() { if (this.currentPage > 1) this.currentPage--; },
        nextPage() { if (this.currentPage < this.totalPages) this.currentPage++; },
        pagesArray() { let pages = []; for (let i = 1; i <= this.totalPages; i++) pages.push(i); return pages; },
        init() { this.$watch('searchQuery', () => { this.currentPage = 1; }); },
        formatDate(datetime) { return new Date(datetime).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }); },
        formatTime(datetime) { return new Date(datetime).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }); },
        renderBadge(status) {
            if (status === 'pending') return '<span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-yellow-50 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400 border border-yellow-200 dark:border-yellow-500/20 uppercase tracking-wider">Pending</span>';
            if (status === 'approved') return '<span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400 border border-green-200 dark:border-green-500/20 uppercase tracking-wider">Disetujui</span>';
            if (status === 'rejected') return '<span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400 border border-red-200 dark:border-red-500/20 uppercase tracking-wider">Ditolak</span>';
            return `<span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-gray-100 text-gray-500 border border-gray-200 uppercase tracking-wider">${status}</span>`;
        },
        openModal(row) {
            this.selectedData = { id: row.id, nama: row.nama_lengkap, program: row.nama_program, desil: row.desil, status: row.status_antrean === 'pending' ? '' : row.status_antrean, catatan: row.catatan_admin || '' };
            this.modalOpen = true;
            document.body.style.overflow = 'hidden';
        },
        closeModal() { this.modalOpen = false; document.body.style.overflow = ''; }
    }
}
</script>
