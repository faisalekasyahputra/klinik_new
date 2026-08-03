<?php
$this->load->helper('admin_table');
$this->load->helper('housing_queue');
/**
 * View antrean perumahan bersama — dipakai DUA konteks:
 *   1. Admin_Kabkota::index()  — ter-scope 1 kabupaten
 *   2. Admin::index()          — superadmin, lintas wilayah
 * Data disiapkan MY_Controller::antrean_table_data(); kontrak POST update
 * status sama persis di kedua konteks (queue_id + status + catatan_admin).
 *
 * Cari/filter/urut/paginasi SEMUANYA server-side (B8). Versi sebelumnya
 * mengirim s.d. 1000 baris sebagai JSON ke browser lalu memproses di klien —
 * terasa instan tapi tidak bisa dipakai begitu data menumpuk, dan bikin
 * halaman ini jadi paradigma tabel tersendiri. Alpine sekarang HANYA untuk
 * modal keputusan (interaksi yang memang milik klien).
 *
 * Badge di sini memakai komponen bersama admin/components/status_badge.php —
 * dulu tidak bisa karena barisnya dirender JS.
 */
$statuses = housing_queue_statuses();
$badge_kelas = $badge_label = [];
foreach ($statuses as $kode => $status) {
    $badge_kelas[$kode] = $status['badge'];
    $badge_label[$kode] = $status['label'];
}
if ( ! isset($badge_label['needs_revision'])) {
    $badge_label['needs_revision'] = 'Perlu Perbaikan';
    $badge_kelas['needs_revision'] = 'pending';
}

// Filter status dibangun lewat admin_table_url() supaya pencarian/urutan aktif
// tidak hilang saat ganti filter, dan sebaliknya.
ob_start(); ?>
<span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-brand-muted mr-1">Status:</span>
<a href="<?= admin_table_url($base_url, ['status' => NULL]) ?>" class="px-3 py-1 rounded-lg text-xs font-bold border transition-colors <?= empty($filter_status) ? 'bg-brand-primary/20 border-brand-primary/50 text-brand-primary' : 'border-gray-200 dark:border-white/10 text-gray-600 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/10' ?>">Semua</a>
<?php foreach ($badge_label as $kode => $label): ?>
<a href="<?= admin_table_url($base_url, ['status' => $kode]) ?>" class="px-3 py-1 rounded-lg text-xs font-bold border transition-colors <?= $filter_status === $kode ? 'bg-brand-primary/20 border-brand-primary/50 text-brand-primary' : 'border-gray-200 dark:border-white/10 text-gray-600 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/10' ?>"><?= $label ?></a>
<?php endforeach;
if (!empty($can_filter_tanpa_wilayah)): ?>
<span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-brand-muted mr-1">Wilayah:</span>
<a href="<?= admin_table_url($base_url, ['tanpa_wilayah' => NULL]) ?>" class="px-3 py-1 rounded-lg text-xs font-bold border transition-colors <?= empty($filter_tanpa_wilayah) ? 'bg-brand-primary/20 border-brand-primary/50 text-brand-primary' : 'border-gray-200 dark:border-white/10 text-gray-600 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/10' ?>">Semua</a>
<a href="<?= admin_table_url($base_url, ['tanpa_wilayah' => '1']) ?>" class="px-3 py-1 rounded-lg text-xs font-bold border transition-colors <?= !empty($filter_tanpa_wilayah) ? 'bg-orange-100 border-orange-300 text-orange-700 dark:bg-orange-500/20 dark:border-orange-500/50 dark:text-orange-400' : 'border-gray-200 dark:border-white/10 text-gray-600 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/10' ?>">Belum Terpetakan</a>
<?php endif;
$filter_html = ob_get_clean();
?>

<div x-data="antreanModal()" class="relative z-10">
    <div class="mb-8">
        <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2 flex items-center gap-3">
            <i class="ph ph-map-pin text-brand-primary"></i>
            Antrean Perumahan — <?= html_escape($scope_label) ?>
        </h1>
        <p class="text-sm text-gray-500 dark:text-brand-muted">Kelola antrean pengajuan program perumahan warga.</p>
    </div>

    <div data-tabel-admin class="bg-white dark:bg-brand-card border border-gray-200 dark:border-white/10 rounded-3xl overflow-hidden shadow-sm">
        <?= $this->load->view('admin/components/table_toolbar', ['table' => $table, 'base_url' => $base_url, 'placeholder' => 'Cari nama, NIK, tiket, program...', 'filter_html' => $filter_html], TRUE) ?>

        <p class="px-4 pt-3 text-xs text-gray-500 dark:text-brand-muted sm:hidden">
            <i class="ph ph-arrows-left-right mr-1" aria-hidden="true"></i>
            Geser tabel ke samping untuk melihat seluruh kolom, termasuk Aksi.
        </p>
        <div class="overflow-x-auto" role="region" aria-label="Tabel antrean perumahan; geser ke samping untuk melihat kolom Aksi" tabindex="0">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="text-xs uppercase bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted font-bold tracking-wider">
                    <tr>
                        <th class="px-4 py-3"><?= admin_sort_header('Tanggal', 'sf_housing_queue.created_at', $table, $base_url) ?></th>
                        <th class="px-4 py-3"><?= admin_sort_header('Pemohon', 'sf_housing_queue.nama_lengkap', $table, $base_url) ?></th>
                        <th class="px-4 py-3"><?= admin_sort_header('Program', 'sf_programs.nama_program', $table, $base_url) ?></th>
                        <th class="px-4 py-3">Kondisi Sosial</th>
                        <th class="px-4 py-3"><?= admin_sort_header('Status', 'sf_housing_queue.status_antrean', $table, $base_url) ?></th>
                        <th class="w-px whitespace-nowrap px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5 text-gray-600 dark:text-brand-muted">
                    <?php if (empty($queue)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-gray-100 dark:bg-black/40 border border-gray-200 dark:border-white/5 flex items-center justify-center text-2xl text-gray-300 dark:text-white/20"><i class="ph ph-tray"></i></div>
                            <p class="text-base font-medium text-gray-500 dark:text-white/60">Data tidak ditemukan</p>
                            <p class="text-sm mt-1 text-gray-400 dark:text-brand-muted"><?= ($table['q'] !== '' || $filter_status || !empty($filter_tanpa_wilayah)) ? 'Tidak ada yang cocok dengan pencarian/filter ini.' : html_escape($empty_text) ?></p>
                        </td>
                    </tr>
                    <?php else: foreach ($queue as $row):
                        $survey   = json_decode($row->data_survey_json, TRUE) ?: [];
                        $simperum = json_decode($row->data_simperum_json, TRUE) ?: [];
                        $penghasilan = $survey['penghasilan'] ?? '-';
                        if (is_numeric($penghasilan)) { $penghasilan = 'Rp ' . number_format((float) $penghasilan, 0, ',', '.'); }
                        $desil  = $simperum['desil'] ?? '-';
                        $alasan = $survey['alasan_pengajuan'] ?? '-';
                        $has_assessment = ! empty($row->assessment_id);
                        $nama_display = trim((string) $row->nama_lengkap) !== ''
                            ? $row->nama_lengkap : ($has_assessment ? 'Identitas ada di detail pengajuan' : 'Nama belum tersedia');
                        $nik_display = strlen((string) $row->nik_pengaju) >= 4
                            ? str_repeat('•', 12) . substr($row->nik_pengaju, -4)
                            : ($has_assessment ? 'NIK disimpan privat' : 'NIK belum tersedia');
                        $payload = [
                            'id' => $row->id, 'nama' => $nama_display,
                            'program' => $row->nama_program ?? 'Program belum terpetakan', 'desil' => $desil,
                            'status' => '', 'currentStatus' => $badge_label[$row->status_antrean] ?? $row->status_antrean, 'currentStatusCode' => $row->status_antrean,
                            'catatan' => $row->catatan_admin ?? '',
                            'ticket' => $row->ticket_code,
                            'nik' => $nik_display,
                            'pekerjaan' => $survey['pekerjaan'] ?? '-',
                            'penghasilan' => $penghasilan,
                            'kepemilikan' => $survey['status_kepemilikan'] ?? '-',
                            'alasan' => $alasan,
                        ];
                    ?>
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                        <td class="px-4 py-3">
                            <div class="text-gray-900 dark:text-white font-medium"><?= html_escape(date('d M Y', strtotime($row->created_at))) ?></div>
                            <div class="text-[10px]"><?= html_escape(date('H:i', strtotime($row->created_at))) ?></div>
                        </td>
                        <!-- Teks panjang boleh membungkus agar kolom Aksi tidak
                             terdorong keluar seperti kasus meja KKN/Magang. -->
                        <td class="max-w-[14rem] whitespace-normal break-words px-4 py-3">
                            <div class="text-gray-900 dark:text-white font-bold"><?= html_escape($nama_display) ?></div>
                            <div class="text-xs font-mono font-bold text-brand-primary"><?= html_escape($row->ticket_code) ?></div>
                            <div class="text-xs font-mono mt-0.5"><?= html_escape($nik_display) ?></div>
                            <?php if (empty($row->kabupaten_id)): ?>
                            <div class="mt-1 inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-orange-50 text-orange-700 dark:bg-orange-500/10 dark:text-orange-400 border border-orange-200 dark:border-orange-500/20" title="Tidak muncul di dashboard Admin Kabupaten/Kota manapun">
                                <i class="ph ph-map-pin-slash text-[10px]"></i> Belum Terpetakan Wilayah
                            </div>
                            <?php endif; ?>
                        </td>
                        <td class="max-w-[14rem] whitespace-normal break-words px-4 py-3">
                            <div class="inline-block max-w-full break-words rounded-lg bg-brand-primary/10 px-2.5 py-1 text-xs font-semibold text-brand-primary border border-brand-primary/20 mb-1"><?= html_escape($row->nama_program ?? 'Program belum terpetakan') ?></div>
                            <?php if (($row->source_mode ?? '') === 'simulation'): ?><div class="mt-1 inline-block max-w-full break-words rounded bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-800">Mode Simulasi — API SIMPERUM belum terhubung</div><?php endif; ?>
                            <?php if ($desil !== '-'): ?>
                            <div class="text-[10px] text-blue-700 bg-blue-50 dark:text-white dark:bg-blue-500/20 border border-blue-200 dark:border-blue-500/30 px-2 py-0.5 rounded inline-block">Desil: <span class="font-bold"><?= html_escape($desil) ?></span></div>
                            <?php endif; ?>
                        </td>
                        <td class="max-w-[14rem] whitespace-normal break-words px-4 py-3">
                            <div class="text-xs mb-1"><i class="ph ph-briefcase mr-1"></i> <?= html_escape($survey['pekerjaan'] ?? '-') ?></div>
                            <div class="text-xs mb-1"><i class="ph ph-wallet mr-1"></i> <?= html_escape($penghasilan) ?></div>
                            <?php if ($alasan !== '-'): ?>
                            <div class="text-xs italic mt-1 max-w-[250px] break-words whitespace-normal leading-tight"><i class="ph ph-quotes mr-1 text-[10px]"></i> <?= html_escape($alasan) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3"><?= $this->load->view('admin/components/status_badge', ['label' => $badge_label[$row->status_antrean] ?? $row->status_antrean, 'kelas' => $badge_kelas[$row->status_antrean] ?? 'pending'], TRUE) ?></td>
                        <td class="w-px whitespace-nowrap px-4 py-3 text-center">
                            <?php if ( ! empty($row->assessment_id)): ?>
                            <a href="<?= base_url($base_url . '/detail/' . (int) $row->id) ?>" class="inline-flex items-center justify-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-brand-primary/20 dark:bg-white/5 text-gray-600 dark:text-brand-muted hover:text-brand-primary border border-gray-200 dark:border-white/10 transition-all duration-200"><i class="ph ph-eye"></i><span class="text-xs font-bold uppercase tracking-wider">Detail</span></a>
                            <?php else: ?>
                            <button @click='openModal(<?= htmlspecialchars(json_encode($payload), ENT_QUOTES, "UTF-8") ?>)' class="inline-flex items-center justify-center gap-2 px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-brand-primary/20 dark:bg-white/5 text-gray-600 dark:text-brand-muted hover:text-brand-primary border border-gray-200 dark:border-white/10 transition-all duration-200" title="Proses"><i class="ph ph-note-pencil"></i><span class="text-xs font-bold uppercase tracking-wider">Tinjau</span></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <?= $this->load->view('admin/components/pagination', ['pager' => $pager, 'base_url' => $base_url], TRUE) ?>
    </div>

    <?php /* Modal keputusan — satu-satunya bagian yang masih Alpine.
              `style="display:none"` WAJIB, dan bukan pengganti x-cloak melainkan
              pasangannya. x-cloak hanya berlaku selama atributnya masih ada;
              begitu Alpine melepasnya lalu `x-data` gagal dievaluasi, `x-show`
              tidak pernah mengambil alih dan modal tertinggal TERLIHAT —
              menutupi seluruh halaman dengan formulir kosong yang tidak bisa
              ditutup. Terjadi nyata di layar ini.
              Dengan display:none inline, kegagalan Alpine berarti modal tetap
              tersembunyi (gagal-tertutup); saat Alpine sehat, `x-show` menimpa
              gaya inline itu seperti biasa. */ ?>
    <div x-show="open" x-cloak style="display:none"
         class="fixed inset-0 z-[100] flex items-center justify-center p-4 sm:p-6">
        <div x-show="open" x-transition.opacity class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="close()"></div>
        <div x-show="open" x-transition class="relative w-full max-w-lg bg-white dark:bg-brand-card border border-gray-200 dark:border-white/10 rounded-3xl shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <div class="px-4 py-3 border-b border-gray-200 dark:border-white/10 flex justify-between items-center bg-gray-50 dark:bg-white/5">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2"><i class="ph ph-clipboard-text text-brand-primary"></i> Proses Pengajuan</h3>
                <button @click="close()" class="text-gray-400 dark:text-brand-muted hover:text-gray-700 dark:hover:text-white transition-colors"><i class="ph ph-x text-lg"></i></button>
            </div>
            <div class="p-6 overflow-y-auto custom-scrollbar">
                <form action="<?= base_url($action_url) ?>" method="POST" id="formProsesAntrean">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                    <input type="hidden" name="queue_id" :value="data.id">
                    <input type="hidden" name="from_status" :value="data.currentStatusCode">
                    <div class="mb-5 bg-gray-50 dark:bg-white/5 rounded-2xl p-4 border border-gray-200 dark:border-white/5">
                        <div class="text-xs text-gray-500 dark:text-brand-muted mb-1">Pengaju</div>
                        <div class="text-gray-900 dark:text-white font-bold text-lg mb-3" x-text="data.nama"></div>
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="text-xs text-gray-500 dark:text-brand-muted mb-1">Program</div>
                                <div class="inline-flex items-center px-2.5 py-1 rounded-lg bg-brand-primary/10 text-brand-primary border border-brand-primary/20 text-xs font-semibold" x-text="data.program"></div>
                            </div>
                            <div class="text-right" x-show="data.desil !== '-'">
                                <div class="text-xs text-gray-500 dark:text-brand-muted mb-1">Desil Kesejahteraan</div>
                                <div class="text-gray-900 dark:text-white font-bold text-lg" x-text="data.desil"></div>
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-3 border-t border-gray-200 pt-3 text-xs dark:border-white/10">
                            <div><span class="block text-gray-500 dark:text-brand-muted">Status saat ini</span><strong class="text-gray-900 dark:text-white" x-text="data.currentStatus"></strong></div>
                            <div><span class="block text-gray-500 dark:text-brand-muted">Tiket</span><strong class="font-mono text-gray-900 dark:text-white" x-text="data.ticket"></strong></div>
                            <div><span class="block text-gray-500 dark:text-brand-muted">NIK</span><strong class="font-mono text-gray-900 dark:text-white" x-text="data.nik"></strong></div>
                            <div><span class="block text-gray-500 dark:text-brand-muted">Pekerjaan</span><strong class="text-gray-900 dark:text-white" x-text="data.pekerjaan"></strong></div>
                            <div><span class="block text-gray-500 dark:text-brand-muted">Penghasilan</span><strong class="text-gray-900 dark:text-white" x-text="data.penghasilan"></strong></div>
                            <div class="col-span-2"><span class="block text-gray-500 dark:text-brand-muted">Status hunian</span><strong class="text-gray-900 dark:text-white" x-text="data.kepemilikan"></strong></div>
                            <div class="col-span-2"><span class="block text-gray-500 dark:text-brand-muted">Alasan pengajuan</span><strong class="whitespace-normal text-gray-900 dark:text-white" x-text="data.alasan"></strong></div>
                        </div>
                    </div>
                    <div class="mb-5">
                        <label class="block text-xs font-bold text-gray-500 dark:text-brand-muted uppercase tracking-wider mb-2">Aksi Keputusan <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="status" value="approved" class="peer sr-only" x-model="data.status" required>
                                <div class="px-4 py-3 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 text-gray-600 dark:text-brand-muted text-center font-semibold transition-all duration-200 peer-checked:bg-green-50 peer-checked:border-green-400 peer-checked:text-green-700 dark:peer-checked:bg-green-500/20 dark:peer-checked:border-green-500/50 dark:peer-checked:text-green-400"><i class="ph ph-check mr-2"></i> Setujui</div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="status" value="rejected" class="peer sr-only" x-model="data.status" required>
                                <div class="px-4 py-3 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 text-gray-600 dark:text-brand-muted text-center font-semibold transition-all duration-200 peer-checked:bg-red-50 peer-checked:border-red-400 peer-checked:text-red-700 dark:peer-checked:bg-red-500/20 dark:peer-checked:border-red-500/50 dark:peer-checked:text-red-400"><i class="ph ph-x mr-2"></i> Tolak</div>
                            </label>
                            <label class="relative cursor-pointer">
                                <input type="radio" name="status" value="needs_revision" class="peer sr-only" x-model="data.status" required>
                                <div class="px-4 py-3 rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 text-gray-600 dark:text-brand-muted text-center font-semibold transition-all duration-200 peer-checked:bg-amber-50 peer-checked:border-amber-400 peer-checked:text-amber-700"><i class="ph ph-arrow-counter-clockwise mr-2"></i> Minta Perbaikan</div>
                            </label>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="block text-xs font-bold text-gray-500 dark:text-brand-muted uppercase tracking-wider mb-2">Catatan Admin <span x-show="data.status === 'rejected' || data.status === 'needs_revision'" class="text-red-500">*</span></label>
                        <textarea name="catatan_admin" rows="3" class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-gray-800 dark:text-white text-sm focus:outline-none focus:border-brand-primary/50 focus:ring-1 focus:ring-brand-primary/50" placeholder="Wajib untuk penolakan atau permintaan perbaikan" x-model="data.catatan" :required="data.status === 'rejected' || data.status === 'needs_revision'"></textarea>
                    </div>
                </form>
            </div>
            <div class="px-4 py-3 border-t border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-black/20 flex justify-end gap-3">
                <button type="button" @click="close()" class="px-5 py-2.5 rounded-xl border border-gray-200 dark:border-white/10 text-gray-700 dark:text-white text-sm font-semibold hover:bg-gray-100 dark:hover:bg-white/5">Batal</button>
                <button type="submit" form="formProsesAntrean" class="px-5 py-2.5 rounded-xl bg-brand-primary text-brand-dark text-sm font-bold hover:brightness-95">Simpan Keputusan</button>
            </div>
        </div>
    </div>
</div>

<script>
function antreanModal() {
    return {
        open: false,
        data: { id: '', nama: '', program: '', status: '', currentStatus: '', currentStatusCode: '', catatan: '', desil: '', ticket: '', nik: '', pekerjaan: '', penghasilan: '', kepemilikan: '', alasan: '' },
        openModal(row) { this.data = row; this.open = true; document.body.style.overflow = 'hidden'; },
        close() { this.open = false; document.body.style.overflow = ''; }
    }
}
</script>
