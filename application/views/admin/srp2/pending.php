<div class="mb-6">
    <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2">Verifikasi SRP2</h2>
    <p class="text-sm text-gray-500 dark:text-brand-muted">Pengajuan sertifikasi pengembang yang menunggu keputusan.</p>
</div>

<?php $this->load->helper('admin_table'); ?>
<div class="bg-white dark:bg-brand-card rounded-3xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
    <?= $this->load->view('admin/components/table_toolbar', ['table' => $table, 'base_url' => $base_url, 'placeholder' => 'Cari perusahaan atau email...'], TRUE) ?>
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
