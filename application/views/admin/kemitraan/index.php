<div class="mb-6">
    <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2">Pendaftaran KKN/Magang</h2>
    <p class="text-sm text-gray-500 dark:text-brand-muted">Tinjau dan proses pendaftaran KKN/Magang dari mahasiswa.</p>
</div>

<?php $this->load->helper('admin_table'); ?>
<div data-tabel-admin class="bg-white dark:bg-brand-card rounded-3xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
    <?= $this->load->view('admin/components/table_toolbar', ['table' => $table, 'base_url' => $base_url, 'placeholder' => 'Cari mahasiswa, instansi, divisi...'], TRUE) ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-4"><?= admin_sort_header('Mahasiswa', 'usr_users.name', $table, $base_url) ?></th>
                    <th class="px-6 py-4">Jenis</th>
                    <th class="px-6 py-4"><?= admin_sort_header('Instansi Asal', 'kkn_magang_pendaftaran.instansi_asal', $table, $base_url) ?></th>
                    <th class="px-6 py-4">Divisi/Tema</th>
                    <th class="px-6 py-4"><?= admin_sort_header('Status', 'kkn_magang_pendaftaran.status', $table, $base_url) ?></th>
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
                    <td class="px-6 py-4">
                        <?= html_escape($r->divisi_atau_tema ?: '-') ?>
                        <?php if (!empty($r->file_surat_pengantar)): ?>
                        <div class="mt-1"><a href="<?= base_url('Admin_Kemitraan/lihat_dokumen/' . $r->id) ?>" target="_blank" rel="noopener" class="text-xs font-bold text-blue-600 dark:text-brand-primary hover:underline"><i class="ph ph-paperclip"></i> Surat pengantar</a></div>
                        <?php else: ?>
                        <div class="mt-1 text-[10px] text-gray-400 dark:text-brand-muted/60">Tanpa surat pengantar</div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <?php
                            // Peta status domain KKN/Magang -> kelas komponen bersama.
                            $badge_kelas = ['Diajukan' => 'pending', 'Diterima' => 'ok', 'Ditolak' => 'reject'];
                        ?>
                        <?= $this->load->view('admin/components/status_badge', ['label' => $r->status, 'kelas' => $badge_kelas[$r->status] ?? 'pending'], TRUE) ?>
                    </td>
                    <td class="px-6 py-4 text-right relative">
                        <?php if ($r->status === 'Diajukan'): ?>
                        <button @click="procOpen = !procOpen" class="px-3 py-1.5 rounded-lg text-xs font-bold text-blue-600 dark:text-brand-primary hover:bg-blue-50 dark:hover:bg-brand-primary/10">Proses</button>
                        <div x-show="procOpen" x-cloak @click.outside="procOpen = false" class="absolute right-6 top-full mt-1 z-20 w-72 rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-brand-card p-4 text-left shadow-xl">
                            <?= $this->load->view('admin/components/review_form', [
                                'action_url' => 'Admin_Kemitraan/proses/' . $r->id,
                                'buttons' => [
                                    ['value' => 'Diterima', 'label' => 'Terima', 'style' => 'accept'],
                                    ['value' => 'Ditolak', 'label' => 'Tolak', 'style' => 'reject'],
                                ],
                                'catatan_name' => 'catatan_admin',
                            ], TRUE) ?>
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
    <?= $this->load->view('admin/components/pagination', ['pager' => $pager, 'base_url' => $base_url], TRUE) ?>
</div>
