<?php
/**
 * Roster peserta satu KKN, baca saja - permintaan user 22 Agt 2026.
 * Sunting roster hanya lewat dashboard universitas sendiri
 * (KemitraanPortal::kkn_upload_peserta()), bukan dari sini.
 */
?>
<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        <span class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-brand-muted">Kemitraan &middot; KKN</span>
        <h2 class="mt-1 text-2xl font-black text-gray-900 dark:text-white tracking-tight">Peserta KKN</h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-brand-muted">
            <?= html_escape($row->instansi_asal) ?> &middot; <?= html_escape($row->divisi_atau_tema ?: '(tanpa keterangan)') ?>
        </p>
    </div>
    <a href="<?= base_url('Admin_Kemitraan') ?>" class="rounded-xl border border-gray-200 dark:border-white/10 px-4 py-2 text-xs font-bold text-gray-700 dark:text-gray-300">Kembali</a>
</div>

<div class="bg-white dark:bg-brand-card rounded-3xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
    <div class="p-6 border-b border-gray-200 dark:border-white/5">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i class="ph ph-users-three text-brand-primary"></i> <?= count($peserta) ?> Peserta
        </h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-4">NIM</th>
                    <th class="px-4 py-4">Nama</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                <?php if (empty($peserta)): ?>
                <tr>
                    <td colspan="2" class="px-4 py-12 text-center text-gray-500 dark:text-brand-muted">
                        <div class="flex flex-col items-center justify-center">
                            <div class="w-16 h-16 mb-4 rounded-full bg-gray-100 dark:bg-white/5 flex items-center justify-center text-3xl text-gray-300 dark:text-white/20">
                                <i class="ph ph-users-three"></i>
                            </div>
                            <p>Universitas belum mengunggah daftar peserta.</p>
                        </div>
                    </td>
                </tr>
                <?php else: foreach ($peserta as $p): ?>
                <tr>
                    <td class="px-4 py-4 text-gray-900 dark:text-white"><?= html_escape($p->nim) ?></td>
                    <td class="px-4 py-4 text-gray-900 dark:text-white"><?= html_escape($p->nama) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
