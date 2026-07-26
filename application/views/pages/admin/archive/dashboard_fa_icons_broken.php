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
            'nama_lengkap' => $row->nama_lengkap, // Uncensored
            'nik_pengaju' => $row->nik_pengaju, // Uncensored
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

<div x-data="adminDashboard()" class="relative z-10">
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-black text-white tracking-tight mb-2 flex items-center gap-3">
                <i class="fa-solid fa-gauge-high text-[#d6fb00]"></i>
                Admin Dashboard
            </h1>
            <p class="text-sm text-[#8aacb0]">Kelola antrean pengajuan program perumahan secara pintar.</p>
        </div>
        
        <!-- Stats overview -->
        <div class="flex items-center gap-4">
            <div class="bg-[#0f2933]/80 backdrop-blur-md border border-white/5 rounded-2xl px-5 py-3 flex items-center gap-4">
                <div class="w-10 h-10 rounded-full bg-yellow-500/10 flex items-center justify-center text-yellow-400">
                    <i class="fa-solid fa-clock"></i>
                </div>
                <div>
                    <div class="text-xs text-[#8aacb0] font-medium uppercase tracking-wider mb-0.5">Pending</div>
                    <div class="text-xl font-bold text-white leading-none">
                        <?php 
                            $pending_count = 0;
                            foreach($queue_mapped as $q) {
                                if($q['status_antrean'] === 'pending') $pending_count++;
                            }
                            echo $pending_count;
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alpine Smart Table Card -->
    <div class="bg-[#0f2933]/80 backdrop-blur-xl border border-white/10 rounded-3xl overflow-hidden shadow-2xl shadow-black/40 flex flex-col min-h-[600px]">
        <div class="px-5 py-4 border-b border-white/10 flex flex-col md:flex-row md:justify-between md:items-center bg-white/5 gap-4">
            <div class="flex items-center gap-3">
                <h2 class="text-base font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-[#d6fb00]"></i> Antrean Pengajuan
                </h2>
                <div class="px-2.5 py-0.5 rounded-full bg-white/10 text-[#8aacb0] text-[11px] font-medium border border-white/5">
                    <span x-text="allData.length"></span> Total
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row items-center gap-2 w-full sm:w-auto">
                <!-- Filter Status -->
                <div class="relative w-full sm:w-40">
                    <select x-model="filterStatus" 
                        class="w-full bg-black/30 border border-white/10 rounded-lg px-3 py-1.5 text-white text-xs focus:outline-none focus:border-[#d6fb00]/50 focus:ring-1 focus:ring-[#d6fb00]/50 transition-all duration-200 appearance-none cursor-pointer">
                        <option value="all" class="bg-[#0a1a1f] text-white">Semua Status</option>
                        <option value="pending" class="bg-[#0a1a1f] text-yellow-400">Menunggu (Pending)</option>
                        <option value="approved" class="bg-[#0a1a1f] text-green-400">Disetujui</option>
                        <option value="rejected" class="bg-[#0a1a1f] text-red-400">Ditolak</option>
                    </select>
                    <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-[#8aacb0]/70 pointer-events-none text-[10px]"></i>
                </div>

                <!-- Global Search -->
                <div class="relative w-full sm:w-60">
                    <input type="text" x-model="searchQuery" placeholder="Cari nama, NIK, program..." 
                        class="w-full bg-black/30 border border-white/10 rounded-lg pl-8 pr-3 py-1.5 text-white text-xs focus:outline-none focus:border-[#d6fb00]/50 focus:ring-1 focus:ring-[#d6fb00]/50 transition-all duration-200 placeholder-[#8aacb0]/50">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-[#8aacb0]/70 text-[10px]"></i>
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto flex-1 flex flex-col">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="text-xs uppercase bg-black/20 text-[#8aacb0] font-bold tracking-wider">
                    <tr>
                        <th class="px-4 py-2 rounded-tl-xl cursor-pointer hover:text-white" @click="sortBy('created_at')">
                            Tanggal <i class="fa-solid ml-1" :class="sortCol === 'created_at' ? (sortAsc ? 'fa-sort-up text-[#d6fb00]' : 'fa-sort-down text-[#d6fb00]') : 'fa-sort opacity-30'"></i>
                        </th>
                        <th class="px-4 py-2 cursor-pointer hover:text-white" @click="sortBy('nama_lengkap')">
                            Pemohon <i class="fa-solid ml-1" :class="sortCol === 'nama_lengkap' ? (sortAsc ? 'fa-sort-up text-[#d6fb00]' : 'fa-sort-down text-[#d6fb00]') : 'fa-sort opacity-30'"></i>
                        </th>
                        <th class="px-4 py-2 cursor-pointer hover:text-white" @click="sortBy('nama_program')">
                            Detail Program <i class="fa-solid ml-1" :class="sortCol === 'nama_program' ? (sortAsc ? 'fa-sort-up text-[#d6fb00]' : 'fa-sort-down text-[#d6fb00]') : 'fa-sort opacity-30'"></i>
                        </th>
                        <th class="px-4 py-2">Kondisi Sosial</th>
                        <th class="px-4 py-2 cursor-pointer hover:text-white" @click="sortBy('status_antrean')">
                            Status <i class="fa-solid ml-1" :class="sortCol === 'status_antrean' ? (sortAsc ? 'fa-sort-up text-[#d6fb00]' : 'fa-sort-down text-[#d6fb00]') : 'fa-sort opacity-30'"></i>
                        </th>
                        <th class="px-4 py-2 text-center rounded-tr-xl">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5 text-[#8aacb0] relative">
                    <template x-if="paginatedData.length > 0">
                        <template x-for="row in paginatedData" :key="row.id">
                            <tr class="hover:bg-white/5 transition-colors group">
                                <td class="px-4 py-2">
                                    <div class="text-white font-medium" x-text="formatDate(row.created_at)"></div>
                                    <div class="text-[10px] text-[#8aacb0]" x-text="formatTime(row.created_at)"></div>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="text-white font-bold" x-text="row.nama_lengkap"></div>
                                    <div class="text-xs text-[#8aacb0] font-mono mt-0.5" x-text="row.nik_pengaju"></div>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="inline-flex items-center px-2.5 py-1 rounded-lg bg-[#d6fb00]/10 text-[#ecffb6] border border-[#d6fb00]/20 text-xs font-semibold mb-1" x-text="row.nama_program"></div>
                                    <div class="text-[10px] text-white bg-blue-500/20 border border-blue-500/30 px-2 py-0.5 rounded" x-show="row.desil !== '-'">
                                        Desil: <span class="font-bold" x-text="row.desil"></span>
                                    </div>
                                </td>
                                <td class="px-4 py-2">
                                    <div class="text-white font-medium text-xs mb-1">
                                        <i class="fa-solid fa-briefcase text-white/40 mr-1"></i> <span x-text="row.pekerjaan"></span>
                                    </div>
                                    <div class="text-white font-medium text-xs mb-1">
                                        <i class="fa-solid fa-wallet text-white/40 mr-1"></i> <span x-text="row.penghasilan"></span>
                                    </div>
                                    <div class="text-xs text-white/70 italic mt-1 max-w-[250px] break-words whitespace-normal leading-tight" :title="row.alasan" x-show="row.alasan !== '-'">
                                        <i class="fa-solid fa-quote-left text-white/30 mr-1 text-[8px]"></i> <span x-text="row.alasan"></span>
                                    </div>
                                </td>
                                <td class="px-4 py-2" x-html="renderBadge(row.status_antrean)"></td>
                                <td class="px-4 py-2 text-center">
                                    <button @click="openModal(row)" 
                                        class="inline-flex items-center justify-center gap-2 px-3 py-1.5 rounded-lg bg-white/5 hover:bg-[#d6fb00]/20 text-[#8aacb0] hover:text-[#d6fb00] border border-white/10 hover:border-[#d6fb00]/30 transition-all duration-200" title="Proses">
                                        <i class="fa-solid fa-edit"></i> <span class="text-xs font-bold uppercase tracking-wider">Tinjau</span>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </template>
                    
                    <!-- Empty State Full Height -->
                    <template x-if="paginatedData.length === 0">
                        <tr>
                            <td colspan="6" class="p-0 border-none relative">
                                <div class="absolute inset-0 flex flex-col items-center justify-center text-[#8aacb0] min-h-[400px]">
                                    <div class="w-20 h-20 mb-5 rounded-full bg-black/40 border border-white/5 flex items-center justify-center text-3xl text-white/20 shadow-inner">
                                        <i class="fa-solid fa-inbox"></i>
                                    </div>
                                    <p class="text-lg font-medium text-white/60">Data tidak ditemukan</p>
                                    <p class="text-sm mt-1" x-text="searchQuery !== '' ? 'Pencarian tidak cocok dengan data manapun.' : 'Belum ada antrean yang masuk.'"></p>
                                </div>
                                <div style="height: 400px;"></div> <!-- Spacer -->
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div class="px-4 py-2 border-t border-white/5 bg-black/10 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-[#8aacb0]">
            <div>
                Menampilkan <span class="font-bold text-white" x-text="(currentPage - 1) * perPage + (paginatedData.length > 0 ? 1 : 0)"></span> - 
                <span class="font-bold text-white" x-text="Math.min(currentPage * perPage, filteredData.length)"></span> dari 
                <span class="font-bold text-white" x-text="filteredData.length"></span> entri
            </div>
            
            <div class="flex items-center gap-1" x-show="totalPages > 1">
                <button @click="prevPage()" :disabled="currentPage === 1" 
                    class="w-8 h-8 flex items-center justify-center rounded-lg border border-white/10 hover:bg-white/10 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                
                <template x-for="page in pagesArray()" :key="page">
                    <button @click="currentPage = page" 
                        class="w-8 h-8 flex items-center justify-center rounded-lg border transition-colors font-medium"
                        :class="currentPage === page ? 'bg-[#d6fb00]/20 border-[#d6fb00]/50 text-[#d6fb00]' : 'border-white/10 hover:bg-white/10 text-white'">
                        <span x-text="page"></span>
                    </button>
                </template>

                <button @click="nextPage()" :disabled="currentPage === totalPages" 
                    class="w-8 h-8 flex items-center justify-center rounded-lg border border-white/10 hover:bg-white/10 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Alpine.js (Dipertahankan seperti sebelumnya namun dengan variable row) -->
    <div x-show="modalOpen" 
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6"
         x-cloak>
         
        <!-- Backdrop -->
        <div x-show="modalOpen" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-black/60 backdrop-blur-sm"
             @click="closeModal()"></div>

        <!-- Modal Content -->
        <div x-show="modalOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4 sm:translate-y-0"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-4 sm:translate-y-0"
             class="relative w-full max-w-lg bg-[#0f2933] border border-white/10 rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            
            <div class="px-4 py-2 border-b border-white/10 flex justify-between items-center bg-white/5">
                <h3 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-clipboard-check text-[#d6fb00]"></i> Proses Pengajuan
                </h3>
                <button @click="closeModal()" class="text-[#8aacb0] hover:text-white transition-colors">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <div class="p-6 overflow-y-auto custom-scrollbar">
                <form action="<?= base_url('Admin/update_status') ?>" method="POST" id="formProses">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                    <input type="hidden" name="queue_id" :value="selectedData.id">

                    <div class="mb-5 bg-white/5 rounded-2xl p-4 border border-white/5">
                        <div class="text-xs text-[#8aacb0] mb-1">Pengaju</div>
                        <div class="text-white font-bold text-lg mb-3" x-text="selectedData.nama"></div>
                        
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="text-xs text-[#8aacb0] mb-1">Program</div>
                                <div class="inline-flex items-center px-2.5 py-1 rounded-lg bg-[#d6fb00]/10 text-[#ecffb6] border border-[#d6fb00]/20 text-xs font-semibold" x-text="selectedData.program"></div>
                            </div>
                            <div class="text-right" x-show="selectedData.desil !== '-'">
                                <div class="text-xs text-[#8aacb0] mb-1">Desil Kesejahteraan</div>
                                <div class="text-white font-bold text-lg" x-text="selectedData.desil"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-5">
                        <label class="block text-xs font-bold text-[#8aacb0] uppercase tracking-wider mb-2">Aksi Keputusan <span class="text-red-400">*</span></label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative cursor-pointer group">
                                <input type="radio" name="status" value="approved" class="peer sr-only" x-model="selectedData.status" required>
                                <div class="px-4 py-3 rounded-xl border border-white/10 bg-white/5 text-[#8aacb0] text-center font-semibold transition-all duration-200 peer-checked:bg-green-500/20 peer-checked:border-green-500/50 peer-checked:text-green-400 hover:bg-white/10">
                                    <i class="fa-solid fa-check mr-2"></i> Setujui
                                </div>
                            </label>
                            <label class="relative cursor-pointer group">
                                <input type="radio" name="status" value="rejected" class="peer sr-only" x-model="selectedData.status" required>
                                <div class="px-4 py-3 rounded-xl border border-white/10 bg-white/5 text-[#8aacb0] text-center font-semibold transition-all duration-200 peer-checked:bg-red-500/20 peer-checked:border-red-500/50 peer-checked:text-red-400 hover:bg-white/10">
                                    <i class="fa-solid fa-xmark mr-2"></i> Tolak
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="block text-xs font-bold text-[#8aacb0] uppercase tracking-wider mb-2" for="catatan_admin">Catatan Admin</label>
                        <textarea id="catatan_admin" name="catatan_admin" rows="3" class="w-full bg-black/20 border border-white/10 rounded-xl px-4 py-3 text-white text-sm focus:outline-none focus:border-[#d6fb00]/50 focus:ring-1 focus:ring-[#d6fb00]/50 transition-all duration-200 placeholder-[#8aacb0]/50" placeholder="Tambahkan alasan atau keterangan (opsional)..." x-model="selectedData.catatan"></textarea>
                    </div>
                </form>
            </div>

            <div class="px-4 py-2 border-t border-white/10 bg-black/20 flex justify-end gap-3">
                <button type="button" @click="closeModal()" class="px-5 py-2.5 rounded-xl border border-white/10 text-white text-sm font-semibold hover:bg-white/5 transition-all duration-200">
                    Batal
                </button>
                <button type="submit" form="formProses" class="px-5 py-2.5 rounded-xl bg-[#d6fb00] text-[#0a1a1f] text-sm font-bold hover:bg-[#b5d400] transition-all duration-200 shadow-[0_0_15px_rgba(214,251,0,0.3)]">
                    Simpan Keputusan
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function adminDashboard() {
    return {
        // Data dari PHP
        allData: <?= json_encode($queue_mapped, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
        
        // State
        searchQuery: '',
        filterStatus: 'all', // New filter status
        sortCol: 'created_at',
        sortAsc: false,
        currentPage: 1,
        perPage: 10,
        
        // Modal State
        modalOpen: false,
        selectedData: { id: '', nama: '', program: '', status: '', catatan: '', desil: '' },

        // Filter & Sort & Pagination Logic
        get filteredData() {
            let data = this.allData;
            
            // 0. Filter by Status
            if (this.filterStatus !== 'all') {
                data = data.filter(item => item.status_antrean === this.filterStatus);
            }
            
            // 1. Search
            if (this.searchQuery.trim() !== '') {
                let q = this.searchQuery.toLowerCase();
                data = data.filter(item => {
                    return (item.nama_lengkap && item.nama_lengkap.toLowerCase().includes(q)) ||
                           (item.nik_pengaju && item.nik_pengaju.toLowerCase().includes(q)) ||
                           (item.nama_program && item.nama_program.toLowerCase().includes(q)) ||
                           (item.status_antrean && item.status_antrean.toLowerCase().includes(q));
                });
            }
            
            // 2. Sort
            data = data.sort((a, b) => {
                let valA = a[this.sortCol] || '';
                let valB = b[this.sortCol] || '';
                
                // Case-insensitive sorting for strings
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
            let end = start + this.perPage;
            return this.filteredData.slice(start, end);
        },
        
        get totalPages() {
            return Math.ceil(this.filteredData.length / this.perPage) || 1;
        },
        
        // Actions
        sortBy(col) {
            if (this.sortCol === col) {
                this.sortAsc = !this.sortAsc;
            } else {
                this.sortCol = col;
                this.sortAsc = true;
            }
        },
        
        prevPage() {
            if (this.currentPage > 1) this.currentPage--;
        },
        
        nextPage() {
            if (this.currentPage < this.totalPages) this.currentPage++;
        },
        
        pagesArray() {
            // Sederhana, mengembalikan list angka halaman
            let pages = [];
            for(let i = 1; i <= this.totalPages; i++) {
                pages.push(i);
            }
            return pages;
        },
        
        // Watchers
        init() {
            this.$watch('searchQuery', () => {
                this.currentPage = 1; // Reset halaman ke 1 kalau melakukan pencarian
            });
        },
        
        // Formatter Utils
        formatDate(datetime) {
            const date = new Date(datetime);
            return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
        },
        
        formatTime(datetime) {
            const date = new Date(datetime);
            return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
        },
        
        renderBadge(status) {
            if (status === 'pending') {
                return '<span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-yellow-500/10 text-yellow-400 border border-yellow-500/20 uppercase tracking-wider">Pending</span>';
            } else if (status === 'approved') {
                return '<span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-green-500/10 text-green-400 border border-green-500/20 uppercase tracking-wider">Disetujui</span>';
            } else if (status === 'rejected') {
                return '<span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-red-500/10 text-red-400 border border-red-500/20 uppercase tracking-wider">Ditolak</span>';
            }
            return `<span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-gray-500/10 text-gray-400 border border-gray-500/20 uppercase tracking-wider">${status}</span>`;
        },
        
        // Modal Logic
        openModal(row) {
            this.selectedData = {
                id: row.id,
                nama: row.nama_lengkap,
                program: row.nama_program,
                desil: row.desil,
                status: row.status_antrean === 'pending' ? '' : row.status_antrean,
                catatan: row.catatan_admin || ''
            };
            this.modalOpen = true;
            document.body.style.overflow = 'hidden';
        },
        closeModal() {
            this.modalOpen = false;
            document.body.style.overflow = '';
        }
    }
}
</script>
