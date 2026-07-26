<?php
// Peta status domain aduan -> kelas komponen bersama.
$badge_kelas = ['Baru' => 'pending', 'Diproses' => 'process', 'Selesai' => 'ok'];
?>
<div class="mb-6">
    <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2">Semua Aduan</h2>
    <p class="text-sm text-gray-500 dark:text-brand-muted">Pantauan lintas bidang untuk audit dan eskalasi. Keputusan tetap di tangan admin bidang masing-masing — halaman ini hanya baca.</p>
</div>

<?php if (!empty($bidang_tanpa_admin)): ?>
<div class="mb-6 p-4 rounded-2xl bg-orange-50 dark:bg-orange-500/10 border border-orange-200 dark:border-orange-500/20 text-orange-800 dark:text-orange-400 text-sm flex items-start gap-3">
    <i class="ph ph-warning-circle text-lg mt-0.5"></i>
    <div>
        <strong>Bidang tanpa admin ter-assign:</strong> <?= html_escape(implode(', ', $bidang_tanpa_admin)) ?>.
        Aduan yang masuk ke bidang ini tidak akan muncul di dashboard siapa pun — tetapkan admin lewat <a href="<?= base_url('Admin_Users') ?>" class="underline font-semibold">Manajemen Pengguna</a>.
    </div>
</div>
<?php endif; ?>

<?php
$this->load->helper('admin_table');
// Filter bidang dibangun lewat admin_table_url() supaya pencarian/urutan yang
// sedang aktif tidak hilang saat ganti filter (dan sebaliknya).
ob_start(); ?>
<span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-brand-muted mr-1">Bidang:</span>
<a href="<?= admin_table_url($base_url, ['bidang' => NULL]) ?>" class="px-3 py-1 rounded-lg text-xs font-bold border transition-colors <?= empty($bidang_filter) ? 'bg-brand-primary/20 border-brand-primary/50 text-brand-primary' : 'border-gray-200 dark:border-white/10 text-gray-600 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/10' ?>">Semua</a>
<?php foreach ($daftar_bidang as $b): ?>
<a href="<?= admin_table_url($base_url, ['bidang' => $b->kode]) ?>" class="px-3 py-1 rounded-lg text-xs font-bold border transition-colors <?= $bidang_filter === $b->kode ? 'bg-brand-primary/20 border-brand-primary/50 text-brand-primary' : 'border-gray-200 dark:border-white/10 text-gray-600 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/10' ?>"><?= html_escape($b->nama) ?></a>
<?php endforeach;
$filter_html = ob_get_clean();
?>
<div class="bg-white dark:bg-brand-card rounded-3xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
    <?= $this->load->view('admin/components/table_toolbar', ['table' => $table, 'base_url' => $base_url, 'placeholder' => 'Cari pelapor, judul, isi...', 'filter_html' => $filter_html], TRUE) ?>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-4"><?= admin_sort_header('Tanggal', 'aduan.created_at', $table, $base_url) ?></th>
                    <th class="px-6 py-4"><?= admin_sort_header('Pelapor', 'aduan.nama', $table, $base_url) ?></th>
                    <th class="px-6 py-4"><?= admin_sort_header('Judul', 'aduan.judul', $table, $base_url) ?></th>
                    <th class="px-6 py-4"><?= admin_sort_header('Bidang', 'aduan.bidang', $table, $base_url) ?></th>
                    <th class="px-6 py-4"><?= admin_sort_header('Status', 'aduan.status', $table, $base_url) ?></th>
                    <th class="px-6 py-4">Diproses Oleh</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                <?php if (empty($rows)): ?>
                <tr><td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-brand-muted">Belum ada aduan.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                <tr>
                    <td class="px-6 py-4 text-xs"><?= html_escape(date('d M Y H:i', strtotime($r->created_at))) ?></td>
                    <td class="px-6 py-4">
                        <div class="font-bold text-gray-900 dark:text-white"><?= html_escape($r->nama) ?></div>
                        <div class="text-xs text-gray-500 dark:text-brand-muted"><?= html_escape($r->email) ?></div>
                    </td>
                    <td class="px-6 py-4 max-w-xs">
                        <div class="font-semibold text-gray-900 dark:text-white truncate"><?= html_escape($r->judul) ?></div>
                        <?php if (!empty($r->catatan_admin)): ?>
                        <div class="text-xs text-gray-500 dark:text-brand-muted mt-0.5 truncate">Catatan: <?= html_escape($r->catatan_admin) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-xs uppercase font-bold"><?= html_escape($r->bidang) ?></td>
                    <td class="px-6 py-4"><?= $this->load->view('admin/components/status_badge', ['label' => $r->status, 'kelas' => $badge_kelas[$r->status] ?? 'pending'], TRUE) ?></td>
                    <td class="px-6 py-4 text-xs">
                        <?php if (!empty($r->nama_petugas)): ?>
                            <span class="text-gray-700 dark:text-gray-300"><?= html_escape($r->nama_petugas) ?></span>
                            <div class="text-[10px] text-gray-400 dark:text-brand-muted/70"><?= $r->reviewed_at ? html_escape(date('d M Y H:i', strtotime($r->reviewed_at))) : '' ?></div>
                        <?php else: ?>
                            <span class="text-gray-400 dark:text-brand-muted/60">Belum diproses</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?= $this->load->view('admin/components/pagination', ['pager' => $pager, 'base_url' => $base_url], TRUE) ?>
</div>
