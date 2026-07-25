<div class="mb-6">
    <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2">Pendaftaran KKN/Magang</h2>
    <p class="text-sm text-gray-500 dark:text-brand-muted">Tinjau dan proses pendaftaran KKN/Magang dari mahasiswa.</p>
</div>

<div class="bg-white dark:bg-brand-card rounded-3xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-4">Mahasiswa</th>
                    <th class="px-6 py-4">Jenis</th>
                    <th class="px-6 py-4">Instansi Asal</th>
                    <th class="px-6 py-4">Divisi/Tema</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-brand-muted">Belum ada pendaftaran KKN/Magang.</td>
                </tr>
                <?php else: foreach ($rows as $r): ?>
                <tr x-data="{ procOpen: false }">
                    <td class="px-6 py-4">
                        <div class="font-bold text-gray-900 dark:text-white"><?= html_escape($r->nama_mahasiswa ?: '-') ?></div>
                        <div class="text-xs text-gray-500 dark:text-brand-muted"><?= html_escape($r->email_mahasiswa ?: '-') ?></div>
                    </td>
                    <td class="px-6 py-4 uppercase text-xs font-bold"><?= html_escape($r->jenis) ?></td>
                    <td class="px-6 py-4"><?= html_escape($r->instansi_asal) ?></td>
                    <td class="px-6 py-4"><?= html_escape($r->divisi_atau_tema ?: '-') ?></td>
                    <td class="px-6 py-4">
                        <?php
                            $badge = ['Diajukan' => 'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400', 'Diterima' => 'bg-green-50 text-green-700 dark:bg-green-500/10 dark:text-green-400', 'Ditolak' => 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-400'][$r->status] ?? 'bg-gray-100 text-gray-600';
                        ?>
                        <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-bold <?= $badge ?>"><?= html_escape($r->status) ?></span>
                    </td>
                    <td class="px-6 py-4 text-right relative">
                        <?php if ($r->status === 'Diajukan'): ?>
                        <button @click="procOpen = !procOpen" class="px-3 py-1.5 rounded-lg text-xs font-bold text-blue-600 dark:text-brand-primary hover:bg-blue-50 dark:hover:bg-brand-primary/10">Proses</button>
                        <div x-show="procOpen" x-cloak @click.outside="procOpen = false" class="absolute right-6 top-full mt-1 z-20 w-72 rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-brand-card p-4 text-left shadow-xl">
                            <form method="POST" action="<?= base_url('Admin_Kemitraan/proses/' . $r->id) ?>" class="space-y-2">
                                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                                <textarea name="catatan_admin" rows="2" placeholder="Catatan (opsional)" class="w-full rounded-lg border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-2 py-1.5 text-xs text-gray-800 dark:text-gray-200"></textarea>
                                <div class="flex gap-2">
                                    <button type="submit" name="status" value="Diterima" class="flex-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-green-600 text-white hover:bg-green-700">Terima</button>
                                    <button type="submit" name="status" value="Ditolak" class="flex-1 px-3 py-1.5 rounded-lg text-xs font-bold bg-red-600 text-white hover:bg-red-700">Tolak</button>
                                </div>
                            </form>
                        </div>
                        <?php else: ?>
                        <span class="text-xs text-gray-400 dark:text-brand-muted/60">Selesai diproses</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
