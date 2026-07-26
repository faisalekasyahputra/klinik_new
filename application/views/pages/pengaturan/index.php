<div class="relative z-10">

    <!-- Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-brand-primary/10 flex items-center justify-center text-brand-primary text-xl">
                <i class="ph ph-list-checks"></i>
            </div>
            <div>
                <h1 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight">Status Pengajuan</h1>
                <p class="text-gray-500 dark:text-brand-muted text-sm">Semua pengajuan yang pernah Anda kirim, dalam satu daftar.</p>
            </div>
        </div>
        <a href="<?= base_url('Auth/logout') ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-red-50 hover:bg-red-100 dark:bg-red-500/10 dark:hover:bg-red-500/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-500/30 rounded-xl transition-all font-semibold text-sm">
            <i class="ph ph-sign-out"></i> <span>Keluar</span>
        </a>
    </div>

    <?php
        // Flash success/error sudah dirender shell admin/index.php sebelum $content
        // disuntikkan — blok di sini dulu merender ulang pesan yang sama (bug B5).
    ?>

    <?php
        $kelas_badge = [
            'pending' => 'bg-amber-50 dark:bg-brand-primary/10 text-amber-700 dark:text-brand-primary border-amber-200 dark:border-brand-primary/20',
            'process' => 'bg-cyan-50 dark:bg-cyan-500/10 text-cyan-700 dark:text-cyan-400 border-cyan-200 dark:border-cyan-500/20',
            'ok'      => 'bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400 border-green-200 dark:border-green-500/30',
            'reject'  => 'bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 border-red-200 dark:border-red-500/30',
        ];
    ?>

    <div class="bg-white dark:bg-brand-card rounded-3xl border border-gray-200 dark:border-white/10 shadow-sm overflow-hidden">
        <?php if (empty($items)): ?>
            <div class="flex flex-col items-center justify-center text-center py-16 px-6">
                <div class="w-16 h-16 mb-4 rounded-full bg-gray-100 dark:bg-white/5 flex items-center justify-center text-3xl text-gray-300 dark:text-white/20">
                    <i class="ph ph-tray"></i>
                </div>
                <p class="text-gray-500 dark:text-brand-muted font-medium">Belum ada pengajuan yang tercatat.</p>
                <p class="text-xs text-gray-400 dark:text-brand-muted/70 mt-1">Riwayat antrean, aduan, dan pengajuan lain yang Anda kirim akan muncul di sini.</p>
            </div>
        <?php else: ?>
            <div class="divide-y divide-gray-100 dark:divide-white/5">
                <?php foreach ($items as $item): ?>
                <div class="flex items-center gap-4 px-6 py-4">
                    <div class="w-10 h-10 shrink-0 rounded-xl bg-gray-100 dark:bg-white/5 flex items-center justify-center text-gray-500 dark:text-brand-muted">
                        <i class="ph <?= $item['icon'] ?> text-lg"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold uppercase tracking-wide text-gray-400 dark:text-brand-muted/70"><?= htmlspecialchars($item['jenis']) ?></p>
                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate"><?= htmlspecialchars($item['judul']) ?></p>
                        <p class="text-xs text-gray-500 dark:text-brand-muted mt-0.5"><?= htmlspecialchars(date('d M Y, H:i', strtotime($item['created_at']))) ?></p>
                        <?php if (!empty($item['catatan_admin'])): ?>
                        <p class="text-xs mt-1.5 px-3 py-2 rounded-lg bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 text-gray-600 dark:text-brand-muted whitespace-normal">
                            <span class="font-bold">Catatan admin:</span> <?= htmlspecialchars($item['catatan_admin']) ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <div class="shrink-0 flex flex-col items-end gap-2">
                        <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold border <?= $kelas_badge[$item['status_kelas']] ?? $kelas_badge['pending'] ?>"><?= htmlspecialchars($item['status_label']) ?></span>
                        <?php if (!empty($item['aksi_url'])): ?>
                        <a href="<?= base_url($item['aksi_url']) ?>" class="text-xs font-bold text-blue-600 dark:text-brand-primary hover:underline">Kelola →</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</div>
