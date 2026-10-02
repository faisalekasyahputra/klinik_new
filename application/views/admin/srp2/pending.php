<?php
$this->load->helper('srp2');
$label_status = srp2_label_status();
$kelas_status = ['Pending' => 'pending', 'Draft' => 'process', 'Diterima' => 'ok', 'Ditolak' => 'reject'];
?>
<?php $this->load->view('admin/components/judul_halaman', ['jh_deskripsi' => 'Pengajuan sertifikasi pengembang' . ($status_filter === 'semua' ? '.' : ' berstatus ' . html_escape($label_status[$status_filter] ?? $status_filter) . '.')]); ?>

<?php $this->load->helper('admin_table'); ?>
<div data-tabel-admin style="counter-reset: baris-admin <?= (int) (($table ?? [])['offset'] ?? 0) ?>" class="kartu-admin overflow-hidden">
    <?php
    $this->load->helper('admin_table');
    // Filter status dibangun lewat admin_table_url() supaya pencarian/urutan yang
    // sedang aktif ikut terbawa saat ganti filter (dan sebaliknya). Mengikuti pola
    // filter bidang di admin/aduan/index.php - jangan bikin varian baru (§17.6).
    ob_start(); ?>
    <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-brand-muted mr-1">Status:</span>
    <?php foreach ($status_pilihan as $s): ?>
    <a href="<?= admin_table_url($base_url, ['status' => $s]) ?>" class="chip-filter"<?= $status_filter === $s ? ' aria-current="true"' : '' ?>><?= html_escape($label_status[$s] ?? $s) ?></a>
    <?php endforeach; ?>
    <a href="<?= admin_table_url($base_url, ['status' => 'semua']) ?>" class="chip-filter"<?= $status_filter === 'semua' ? ' aria-current="true"' : '' ?>>Semua</a>
    <?php $filter_html = ob_get_clean(); ?>
    <?= $this->load->view('admin/components/table_toolbar', ['table' => $table, 'base_url' => $base_url, 'placeholder' => 'Cari perusahaan atau email...', 'filter_html' => $filter_html], TRUE) ?>
    <div class="overflow-x-auto aksi-tetap">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3"><?= admin_sort_header('Perusahaan', 'nama_perusahaan', $table, $base_url) ?></th>
                    <th class="px-4 py-3"><?= admin_sort_header('Email', 'email', $table, $base_url) ?></th>
                    <th class="px-4 py-3"><?= admin_sort_header('Dikirim', 'updated_at', $table, $base_url) ?></th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="5" class="px-4 py-10 text-center text-gray-500 dark:text-brand-muted">Tidak ada pengajuan<?= $status_filter === 'semua' ? '.' : ' berstatus ' . html_escape($label_status[$status_filter] ?? $status_filter) . '.' ?></td>
                </tr>
                <?php else: foreach ($rows as $r): ?>
                <tr>
                    <td class="px-4 py-3 font-bold text-gray-900 dark:text-white"><?= html_escape($r->nama_perusahaan) ?></td>
                    <td class="px-4 py-3"><?= html_escape($r->email) ?></td>
                    <td class="px-4 py-3"><?= html_escape(tgl_id($r->updated_at, TRUE, TRUE)) ?></td>
                    <td class="px-4 py-3"><?= $this->load->view('admin/components/status_badge', ['label' => $label_status[$r->status_verifikasi] ?? $r->status_verifikasi, 'kelas' => $kelas_status[$r->status_verifikasi] ?? 'pending'], TRUE) ?></td>
                    <td class="px-4 py-3 text-right">
                        <a href="<?= base_url('Admin_Srp2/detail/' . $r->id) ?>" class="tombol-aksi"><i class="ph ph-note-pencil" aria-hidden="true"></i><span>Tinjau</span></a>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?= $this->load->view('admin/components/pagination', ['pager' => $pager, 'base_url' => $base_url], TRUE) ?>
</div>
