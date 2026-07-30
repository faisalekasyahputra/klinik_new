<div class="mb-6">
    <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2">Verifikasi SRP2</h2>
    <p class="text-sm text-gray-500 dark:text-brand-muted">Pengajuan sertifikasi pengembang yang menunggu keputusan.</p>
</div>

<?php $this->load->helper('admin_table'); ?>
<div data-tabel-admin class="bg-white dark:bg-brand-card rounded-3xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
    <?php
    $this->load->helper('admin_table');
    // Filter status dibangun lewat admin_table_url() supaya pencarian/urutan yang
    // sedang aktif ikut terbawa saat ganti filter (dan sebaliknya). Mengikuti pola
    // filter bidang di admin/aduan/index.php — jangan bikin varian baru (§17.6).
    $label_status = ['Pending' => 'Menunggu', 'Draft' => 'Diminta Perbaikan', 'Diterima' => 'Diterima', 'Ditolak' => 'Ditolak'];
    $kelas_aktif  = 'bg-brand-primary/20 border-brand-primary/50 text-brand-primary';
    $kelas_pasif  = 'border-gray-200 dark:border-white/10 text-gray-600 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/10';
    ob_start(); ?>
    <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-brand-muted mr-1">Status:</span>
    <?php foreach ($status_pilihan as $s): ?>
    <a href="<?= admin_table_url($base_url, ['status' => $s]) ?>" class="px-3 py-1 rounded-lg text-xs font-bold border transition-colors <?= $status_filter === $s ? $kelas_aktif : $kelas_pasif ?>"><?= html_escape($label_status[$s] ?? $s) ?></a>
    <?php endforeach; ?>
    <a href="<?= admin_table_url($base_url, ['status' => 'semua']) ?>" class="px-3 py-1 rounded-lg text-xs font-bold border transition-colors <?= $status_filter === 'semua' ? $kelas_aktif : $kelas_pasif ?>">Semua</a>
    <?php $filter_html = ob_get_clean(); ?>
    <?= $this->load->view('admin/components/table_toolbar', ['table' => $table, 'base_url' => $base_url, 'placeholder' => 'Cari perusahaan atau email...', 'filter_html' => $filter_html], TRUE) ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-4"><?= admin_sort_header('Perusahaan', 'nama_perusahaan', $table, $base_url) ?></th>
                    <th class="px-6 py-4"><?= admin_sort_header('Email', 'email', $table, $base_url) ?></th>
                    <th class="px-6 py-4"><?= admin_sort_header('Dikirim', 'updated_at', $table, $base_url) ?></th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-brand-muted">Tidak ada pengajuan yang menunggu verifikasi.</td>
                </tr>
                <?php else: foreach ($rows as $r): ?>
                <tr>
                    <td class="px-6 py-4 font-bold text-gray-900 dark:text-white"><?= html_escape($r->nama_perusahaan) ?></td>
                    <td class="px-6 py-4"><?= html_escape($r->email) ?></td>
                    <td class="px-6 py-4"><?= html_escape(date('d M Y, H:i', strtotime($r->updated_at))) ?></td>
                    <td class="px-6 py-4"><?= $this->load->view('admin/components/status_badge', ['label' => 'Menunggu', 'kelas' => 'pending'], TRUE) ?></td>
                    <td class="px-6 py-4 text-right">
                        <a href="<?= base_url('Admin_Srp2/detail/' . $r->id) ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold text-blue-600 dark:text-brand-primary hover:bg-blue-50 dark:hover:bg-brand-primary/10">Tinjau →</a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?= $this->load->view('admin/components/pagination', ['pager' => $pager, 'base_url' => $base_url], TRUE) ?>
</div>
