<?php
// Peta status domain aduan -> kelas komponen bersama (pending/process/ok/reject).
// Vocabulary status sengaja beda per domain di DB; penyeragaman terjadi di sini,
// bukan di tabel. Lihat admin/components/status_badge.php.
$badge_kelas = ['Baru' => 'pending', 'Diproses' => 'process', 'Selesai' => 'ok'];
?>
<div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2 flex items-center gap-3">
            <i class="ph ph-chat-centered-text text-brand-primary"></i>
            Aduan — <?= html_escape($bidang_nama ?: 'Bidang Saya') ?>
        </h1>
        <p class="text-sm text-gray-500 dark:text-brand-muted">Kelola aduan warga yang masuk ke bidang Anda.</p>
    </div>
</div>

<?php $this->load->helper('admin_table'); ?>
<div class="bg-white dark:bg-brand-card border border-gray-200 dark:border-white/10 rounded-3xl overflow-hidden shadow-sm">
    <?= $this->load->view('admin/components/table_toolbar', ['table' => $table, 'base_url' => $base_url, 'placeholder' => 'Cari pelapor, judul, isi...'], TRUE) ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-4"><?= admin_sort_header('Tanggal', 'created_at', $table, $base_url) ?></th>
                    <th class="px-6 py-4"><?= admin_sort_header('Pelapor', 'nama', $table, $base_url) ?></th>
                    <th class="px-6 py-4"><?= admin_sort_header('Judul & Pesan', 'judul', $table, $base_url) ?></th>
                    <th class="px-6 py-4"><?= admin_sort_header('Status', 'status', $table, $base_url) ?></th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5 text-gray-600 dark:text-brand-muted">
                <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-brand-muted">Belum ada aduan yang masuk ke bidang ini.</td>
                </tr>
                <?php else: foreach ($rows as $r): ?>
                <tr x-data="{ procOpen: false }">
                    <td class="px-6 py-4 text-xs"><?= html_escape(date('d M Y H:i', strtotime($r->created_at))) ?></td>
                    <td class="px-6 py-4">
                        <div class="font-bold text-gray-900 dark:text-white"><?= html_escape($r->nama) ?></div>
                        <div class="text-xs"><?= html_escape($r->email) ?></div>
                    </td>
                    <td class="px-6 py-4 max-w-sm">
                        <div class="font-semibold text-gray-900 dark:text-white"><?= html_escape($r->judul) ?></div>
                        <div class="text-xs mt-0.5 line-clamp-2 whitespace-normal"><?= html_escape($r->pesan) ?></div>
                    </td>
                    <td class="px-6 py-4">
                        <?= $this->load->view('admin/components/status_badge', ['label' => $r->status, 'kelas' => $badge_kelas[$r->status] ?? 'pending'], TRUE) ?>
                    </td>
                    <td class="px-6 py-4 text-right relative">
                        <button @click="procOpen = !procOpen" class="px-3 py-1.5 rounded-lg text-xs font-bold text-blue-600 dark:text-brand-primary hover:bg-blue-50 dark:hover:bg-brand-primary/10">
                            <i class="ph ph-note-pencil"></i> Proses
                        </button>
                        <div x-show="procOpen" x-cloak @click.outside="procOpen = false" class="absolute right-6 top-full mt-1 z-20 w-72 rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-brand-card p-4 text-left shadow-xl">
                            <?= $this->load->view('admin/components/review_form', [
                                'action_url' => 'Admin_Bidang/update_status/' . $r->id,
                                'buttons' => [
                                    ['value' => 'Baru', 'label' => 'Baru', 'style' => 'neutral'],
                                    ['value' => 'Diproses', 'label' => 'Diproses', 'style' => 'neutral'],
                                    ['value' => 'Selesai', 'label' => 'Selesai', 'style' => 'accept'],
                                ],
                                'catatan_name' => 'catatan_admin',
                                'catatan_value' => $r->catatan_admin ?? '',
                            ], TRUE) ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?= $this->load->view('admin/components/pagination', ['pager' => $pager, 'base_url' => $base_url], TRUE) ?>
</div>
