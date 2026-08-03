<?php
$this->load->helper('housing_queue');
$housing_statuses = housing_queue_statuses();
$kelas_status = [
    'pending' => 'pending', 'approved' => 'ok', 'rejected' => 'reject', 'needs_revision' => 'process',
    'Baru' => 'pending', 'Diproses' => 'process', 'Selesai' => 'ok',
    'Draft' => 'process', 'Pending' => 'pending', 'Diterima' => 'ok', 'Ditolak' => 'reject',
    'Diajukan' => 'pending', 'Ditinjau Bidang' => 'process', 'Dibatalkan' => 'reject',
];
?>
<div class="mb-8">
    <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2">Meja Kerja Super Admin</h2>
    <p class="text-sm text-gray-500 dark:text-brand-muted">Buka antrean yang perlu perhatian; setiap kartu sudah mengarah ke daftar yang sesuai.</p>
</div>

<?php if ($antrean_tanpa_wilayah > 0): ?>
<a href="<?= base_url('Admin?status=pending&tanpa_wilayah=1') ?>" class="mb-6 flex items-center gap-4 rounded-2xl border border-orange-200 bg-orange-50 p-5 text-orange-800 transition-colors hover:border-orange-400 dark:border-orange-500/20 dark:bg-orange-500/10 dark:text-orange-300">
    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-500/10 text-orange-600 dark:text-orange-400"><i class="ph ph-map-pin-slash text-xl"></i></div>
    <div class="min-w-0 flex-1">
        <p class="font-bold"><?= number_format($antrean_tanpa_wilayah) ?> antrean pending belum memiliki wilayah</p>
        <p class="mt-0.5 text-xs text-orange-700 dark:text-orange-400/80">Tidak terlihat oleh admin kabupaten/kota mana pun.</p>
    </div>
    <span class="hidden items-center gap-1 text-xs font-bold sm:inline-flex">Buka antrean <i class="ph ph-arrow-right"></i></span>
</a>
<?php endif; ?>

<section class="mb-8">
    <div class="mb-4">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Pekerjaan yang perlu perhatian</h3>
        <p class="mt-1 text-xs text-gray-500 dark:text-brand-muted">Jumlah pada kartu adalah antrean aktif untuk masing-masing domain.</p>
    </div>
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <?php foreach ($kartu_domain as $k): ?>
        <a href="<?= base_url($k['url']) ?>" class="group rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition-colors hover:border-blue-300 dark:border-white/5 dark:bg-brand-card dark:hover:border-brand-primary/30">
            <div class="mb-5 flex items-center justify-between gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-brand-primary/10 dark:text-brand-primary"><i class="ph <?= html_escape($k['icon']) ?> text-xl"></i></div>
                <i class="ph ph-arrow-up-right text-gray-300 transition-colors group-hover:text-blue-600 dark:text-brand-muted/50 dark:group-hover:text-brand-primary"></i>
            </div>
            <p class="text-sm font-semibold text-gray-600 dark:text-brand-muted"><?= html_escape($k['label']) ?></p>
            <div class="mt-2 flex items-end gap-3">
                <span class="text-3xl font-black leading-none text-gray-900 dark:text-white"><?= number_format($k['pending']) ?></span>
                <span class="mb-0.5 text-xs font-semibold <?= $k['pending'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400 dark:text-brand-muted/70' ?>"><?= $k['pending'] > 0 ? 'perlu perhatian' : 'tidak ada antrean' ?></span>
            </div>
            <span class="mt-4 inline-flex items-center gap-1 text-xs font-bold text-blue-600 dark:text-brand-primary">Buka antrean <i class="ph ph-arrow-right"></i></span>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/5 dark:bg-brand-card xl:col-span-2">
        <div class="mb-5">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Pengajuan terbaru</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-brand-muted">Enam pengajuan paling baru dari seluruh domain kerja.</p>
        </div>
        <?php if (empty($aktivitas)): ?>
            <p class="text-sm text-gray-400 dark:text-brand-muted/70">Belum ada pengajuan yang masuk.</p>
        <?php else: ?>
        <div class="space-y-2">
            <?php foreach ($aktivitas as $a):
                $housing_status = $housing_statuses[$a['status']] ?? NULL;
                $status_label = $housing_status['label'] ?? $a['status'];
                $status_kelas = $housing_status['badge'] ?? ($kelas_status[$a['status']] ?? 'pending');
            ?>
            <a href="<?= html_escape(base_url($a['url'])) ?>" class="group flex items-start gap-3 rounded-2xl px-2 py-3 transition-colors hover:bg-gray-50 dark:hover:bg-white/5">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-brand-muted"><i class="ph <?= html_escape($a['icon']) ?>"></i></div>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-bold uppercase tracking-wide text-gray-400 dark:text-brand-muted/70"><?= html_escape($a['jenis']) ?></p>
                    <p class="truncate text-sm font-semibold text-gray-900 group-hover:text-blue-600 dark:text-white dark:group-hover:text-brand-primary"><?= html_escape($a['judul']) ?></p>
                    <div class="mt-1 flex items-center gap-2">
                        <?= $this->load->view('admin/components/status_badge', ['label' => $status_label, 'kelas' => $status_kelas], TRUE) ?>
                        <span class="text-[10px] text-gray-400 dark:text-brand-muted/70"><?= html_escape(date('d M, H:i', strtotime($a['waktu']))) ?></span>
                    </div>
                </div>
                <i class="ph ph-caret-right mt-3 text-gray-300 group-hover:text-blue-600 dark:text-brand-muted/50 dark:group-hover:text-brand-primary"></i>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>

    <aside class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm dark:border-white/5 dark:bg-brand-card">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Ringkasan Sistem</h3>
        <dl class="mt-5 divide-y divide-gray-100 dark:divide-white/5">
            <div class="flex items-center gap-3 py-4 first:pt-0">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-500/10 dark:text-indigo-400"><i class="ph ph-users text-xl"></i></div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-brand-muted">Akun Terdaftar</dt>
                    <dd class="mt-1 text-2xl font-black leading-none text-gray-900 dark:text-white"><?= number_format($total_users) ?></dd>
                </div>
            </div>
            <div class="flex items-center gap-3 py-4 pb-0">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400"><i class="ph ph-chats-circle text-xl"></i></div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-brand-muted">Topik Forum</dt>
                    <dd class="mt-1 text-2xl font-black leading-none text-gray-900 dark:text-white"><?= number_format($total_diskusi) ?></dd>
                </div>
            </div>
        </dl>
    </aside>
</div>
