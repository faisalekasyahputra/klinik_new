<?php
$badge_class = [
    'Baru'     => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400 border-amber-200 dark:border-amber-500/20',
    'Diproses' => 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400 border-blue-200 dark:border-blue-500/20',
    'Selesai'  => 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400 border-green-200 dark:border-green-500/20',
];
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

<div class="bg-white dark:bg-brand-card border border-gray-200 dark:border-white/10 rounded-3xl overflow-hidden shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-4">Tanggal</th>
                    <th class="px-6 py-4">Pelapor</th>
                    <th class="px-6 py-4">Judul & Pesan</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5 text-gray-600 dark:text-brand-muted">
                <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-brand-muted">Belum ada aduan yang masuk ke bidang ini.</td>
                </tr>
                <?php else: foreach ($rows as $r): ?>
                <tr x-data="{ procOpen: false, status: '<?= html_escape($r->status) ?>' }">
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
                        <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-bold border <?= $badge_class[$r->status] ?? 'bg-gray-100 text-gray-600 border-gray-200' ?>"><?= html_escape($r->status) ?></span>
                    </td>
                    <td class="px-6 py-4 text-right relative">
                        <button @click="procOpen = !procOpen" class="px-3 py-1.5 rounded-lg text-xs font-bold text-blue-600 dark:text-brand-primary hover:bg-blue-50 dark:hover:bg-brand-primary/10">
                            <i class="ph ph-note-pencil"></i> Proses
                        </button>
                        <div x-show="procOpen" x-cloak @click.outside="procOpen = false" class="absolute right-6 top-full mt-1 z-20 w-72 rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-brand-card p-4 text-left shadow-xl">
                            <form method="POST" action="<?= base_url('Admin_Bidang/update_status/' . $r->id) ?>" class="space-y-2">
                                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                                <select name="status" x-model="status" class="w-full rounded-lg border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-2 py-1.5 text-xs text-gray-800 dark:text-gray-200">
                                    <option value="Baru">Baru</option>
                                    <option value="Diproses">Diproses</option>
                                    <option value="Selesai">Selesai</option>
                                </select>
                                <textarea name="catatan_admin" rows="2" placeholder="Catatan (opsional)" class="w-full rounded-lg border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-2 py-1.5 text-xs text-gray-800 dark:text-gray-200"><?= html_escape($r->catatan_admin ?? '') ?></textarea>
                                <button type="submit" class="w-full mt-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-blue-600 dark:bg-brand-primary text-white dark:text-brand-dark hover:bg-blue-700 dark:hover:bg-brand-hover">Simpan</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
