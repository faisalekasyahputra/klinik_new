<div x-data="adminDashboard()">
    <div class="relative z-10">
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-3xl font-black text-white tracking-tight mb-2 flex items-center gap-3">
                    <i class="fa-solid fa-gauge-high text-[#d6fb00]"></i>
                    Admin Dashboard
                </h1>
                <p class="text-sm text-[#8aacb0]">Kelola antrean pengajuan program perumahan.</p>
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
                                if(isset($queue)) {
                                    foreach($queue as $q) {
                                        if($q->status_antrean === 'pending') $pending_count++;
                                    }
                                }
                                echo $pending_count;
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Data Table Card -->
        <div class="bg-[#0f2933]/80 backdrop-blur-xl border border-white/10 rounded-3xl overflow-hidden shadow-2xl shadow-black/40">
            <div class="px-6 py-5 border-b border-white/10 flex justify-between items-center bg-white/5">
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-[#d6fb00]"></i> Antrean Pengajuan
                </h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm whitespace-nowrap">
                    <thead class="text-xs uppercase bg-black/20 text-[#8aacb0] font-bold tracking-wider">
                        <tr>
                            <th class="px-6 py-4 rounded-tl-xl">Tanggal</th>
                            <th class="px-6 py-4">Nama & NIK</th>
                            <th class="px-6 py-4">Program</th>
                            <th class="px-6 py-4">Penghasilan & Pekerjaan</th>
                            <th class="px-6 py-4">Status</th>
                            <th class="px-6 py-4 text-center rounded-tr-xl">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5 text-[#8aacb0]">
                        <?php if (isset($queue) && !empty($queue)): ?>
                            <?php foreach ($queue as $row): 
                                $data_survey = json_decode($row->data_survey_json, true);
                                $penghasilan = isset($data_survey['penghasilan']) ? $data_survey['penghasilan'] : '-';
                                $pekerjaan = isset($data_survey['pekerjaan']) ? $data_survey['pekerjaan'] : '-';
                                
                                // Status styling
                                $status_badge = '';
                                if ($row->status_antrean === 'pending') {
                                    $status_badge = '<span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-yellow-500/10 text-yellow-400 border border-yellow-500/20 uppercase tracking-wider">Pending</span>';
                                } elseif ($row->status_antrean === 'approved') {
                                    $status_badge = '<span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-green-500/10 text-green-400 border border-green-500/20 uppercase tracking-wider">Disetujui</span>';
                                } elseif ($row->status_antrean === 'rejected') {
                                    $status_badge = '<span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-red-500/10 text-red-400 border border-red-500/20 uppercase tracking-wider">Ditolak</span>';
                                } else {
                                    $status_badge = '<span class="px-2.5 py-1 rounded-md text-[10px] font-bold bg-gray-500/10 text-gray-400 border border-gray-500/20 uppercase tracking-wider">'.$row->status_antrean.'</span>';
                                }
                            ?>
                            <tr class="hover:bg-white/5 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="text-white font-medium"><?= date('d M Y', strtotime($row->created_at)) ?></div>
                                    <div class="text-[10px] text-[#8aacb0]"><?= date('H:i', strtotime($row->created_at)) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-white font-bold"><?= htmlspecialchars($row->nama_lengkap) ?></div>
                                    <div class="text-xs text-[#8aacb0] font-mono mt-0.5"><?= htmlspecialchars($row->nik_pengaju) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="inline-flex items-center px-2.5 py-1 rounded-lg bg-[#d6fb00]/10 text-[#ecffb6] border border-[#d6fb00]/20 text-xs font-semibold">
                                        <?= htmlspecialchars($row->nama_program ?? 'Program') ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-white font-medium"><?= htmlspecialchars($penghasilan) ?></div>
                                    <div class="text-xs text-[#8aacb0] mt-0.5"><?= htmlspecialchars($pekerjaan) ?></div>
                                </td>
                                <td class="px-6 py-4">
                                    <?= $status_badge ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <button @click="openModal(<?= htmlspecialchars(json_encode([
                                        'id' => $row->id,
                                        'nama' => $row->nama_lengkap,
                                        'program' => $row->nama_program ?? 'Program',
                                        'status' => $row->status_antrean,
                                        'catatan' => $row->catatan_admin
                                    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>)" 
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-white/5 hover:bg-[#d6fb00]/20 text-[#8aacb0] hover:text-[#d6fb00] border border-white/10 hover:border-[#d6fb00]/30 transition-all duration-200" title="Proses">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-[#8aacb0]">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 mb-4 rounded-full bg-white/5 flex items-center justify-center text-2xl text-white/20">
                                            <i class="fa-solid fa-inbox"></i>
                                        </div>
                                        <p>Belum ada data antrean pengajuan.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="px-6 py-4 border-t border-white/5 flex items-center justify-between text-xs text-[#8aacb0]">
                <div>Menampilkan <?= isset($queue) ? count($queue) : 0 ?> data antrean.</div>
            </div>
        </div>

    </div>

    <!-- Modal Alpine.js -->
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
            
            <div class="px-6 py-4 border-b border-white/10 flex justify-between items-center bg-white/5">
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
                        
                        <div class="text-xs text-[#8aacb0] mb-1">Program</div>
                        <div class="inline-flex items-center px-2.5 py-1 rounded-lg bg-[#d6fb00]/10 text-[#ecffb6] border border-[#d6fb00]/20 text-xs font-semibold" x-text="selectedData.program"></div>
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

            <div class="px-6 py-4 border-t border-white/10 bg-black/20 flex justify-end gap-3">
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
        modalOpen: false,
        selectedData: {
            id: '',
            nama: '',
            program: '',
            status: '',
            catatan: ''
        },
        openModal(data) {
            this.selectedData = {
                id: data.id,
                nama: data.nama,
                program: data.program,
                status: data.status === 'pending' ? '' : data.status,
                catatan: data.catatan || ''
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
