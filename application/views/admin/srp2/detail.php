<?php
$status_kelas = ['Draft' => 'process', 'Pending' => 'pending', 'Diterima' => 'ok', 'Ditolak' => 'reject'][$pendaftar->status_verifikasi] ?? 'pending';
?>
<div class="mb-6 flex items-start justify-between gap-4">
    <div>
        <a href="<?= base_url('Admin_Srp2/pending') ?>" class="text-xs font-bold text-gray-400 hover:text-gray-600 dark:hover:text-white">← Kembali ke daftar menunggu</a>
        <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mt-1"><?= html_escape($pendaftar->nama_perusahaan ?: '-') ?></h2>
        <p class="text-sm text-gray-500 dark:text-brand-muted"><?= html_escape($pendaftar->email ?: '-') ?></p>
    </div>
    <?= $this->load->view('admin/components/status_badge', ['label' => $pendaftar->status_verifikasi, 'kelas' => $status_kelas], TRUE) ?>
</div>

<?php if (!empty($pendaftar->catatan_admin)): ?>
<div class="mb-6 p-4 rounded-2xl bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/20 text-red-700 dark:text-red-400 text-sm">
    <strong>Catatan admin sebelumnya:</strong> <?= html_escape($pendaftar->catatan_admin) ?>
</div>
<?php endif; ?>

<div class="grid lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 bg-white dark:bg-brand-card rounded-3xl border border-gray-200 dark:border-white/5 p-6">
        <h3 class="font-bold text-gray-900 dark:text-white mb-4">Dokumen Persyaratan (<?= count($uploaded) ?>/<?= count($dokumen_list) ?>)</h3>
        <div class="divide-y divide-gray-100 dark:divide-white/5">
            <?php foreach ($dokumen_list as $key => $label): $doc = $uploaded[$key] ?? NULL; ?>
            <div class="flex items-center justify-between py-3 gap-3">
                <span class="text-sm text-gray-700 dark:text-gray-300"><?= html_escape($label) ?></span>
                <?php if ($doc): ?>
                <a href="<?= base_url('Admin_Srp2/lihat_dokumen/' . $pendaftar->id . '/' . $key) ?>" target="_blank" rel="noopener" class="shrink-0 text-xs font-bold text-blue-600 dark:text-brand-primary hover:underline">Lihat berkas</a>
                <?php else: ?>
                <span class="shrink-0 text-xs text-gray-400 dark:text-brand-muted/60">Belum diunggah</span>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="bg-white dark:bg-brand-card rounded-3xl border border-gray-200 dark:border-white/5 p-6 space-y-4">
        <div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-brand-muted/70 mb-1">Alamat Kantor</h4>
            <p class="text-sm text-gray-700 dark:text-gray-300"><?= html_escape($pendaftar->alamat_kantor ?: '-') ?></p>
        </div>
        <div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-brand-muted/70 mb-1">Tautan</h4>
            <p class="text-xs text-gray-500 dark:text-brand-muted space-x-2">
                <?php foreach (['website' => 'Website', 'instagram' => 'Instagram', 'sosmed_lainnya' => 'Lainnya'] as $field => $label): ?>
                    <?php if (!empty($pendaftar->$field)): ?>
                    <a href="<?= html_escape($pendaftar->$field) ?>" target="_blank" rel="noopener" class="text-blue-600 dark:text-brand-primary hover:underline"><?= $label ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </p>
        </div>

        <?php if ($pendaftar->status_verifikasi === 'Pending'): ?>
        <div class="pt-4 border-t border-gray-100 dark:border-white/5">
            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-400 dark:text-brand-muted/70 mb-3">Keputusan</h4>
            <?= $this->load->view('admin/components/review_form', [
                'action_url' => 'Admin_Srp2/proses/' . $pendaftar->id,
                'buttons' => [
                    ['value' => 'Diterima', 'label' => 'Terima', 'style' => 'accept'],
                    ['value' => 'Ditolak', 'label' => 'Tolak', 'style' => 'reject'],
                ],
                'catatan_name' => 'catatan_admin',
                'catatan_placeholder' => 'Catatan (wajib diisi kalau menolak)',
            ], TRUE) ?>
        </div>
        <?php endif; ?>
    </div>
</div>
