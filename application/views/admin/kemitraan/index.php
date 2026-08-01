<?php $this->load->view('admin/kemitraan/_tabs', ['tab_aktif' => 'pendaftaran']); ?>

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
                        <?php
                        // Identitas dari migrasi 20260701000025. Baris LAMA tidak
                        // punya nilainya (kolomnya NULL demi mereka), jadi barisnya
                        // hanya muncul kalau memang terisi — bukan deretan "-"
                        // yang menyaru seperti data.
                        $identitas = array_filter([
                            $r->nim ?? NULL,
                            ($r->jurusan ?? NULL),
                            ($r->semester ?? NULL) ? 'Smt ' . (int) $r->semester : NULL,
                        ]);
                        ?>
                        <?php if ($identitas): ?>
                        <div class="mt-1 text-xs text-gray-500 dark:text-brand-muted"><?= html_escape(implode(' · ', $identitas)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 uppercase text-xs font-bold"><?= html_escape($r->jenis) ?></td>
                    <td class="px-6 py-4"><?= html_escape($r->instansi_asal) ?></td>
                    <td class="px-6 py-4">
                        <?= html_escape($r->divisi_atau_tema ?: '-') ?>
                        <?php
                        // Dokumen didaftar dari satu tempat supaya menambah jenis
                        // berkas berikutnya tidak berarti menyalin blok <a> lagi.
                        // Proposal hanya ada pada magang.
                        $dokumen = ['surat' => ['Surat pengantar', $r->file_surat_pengantar ?? NULL]];
                        if ($r->jenis === 'magang') { $dokumen['proposal'] = ['Proposal', $r->file_proposal ?? NULL]; }
                        ?>
                        <?php foreach ($dokumen as $kunci => $d): ?>
                            <?php if ( ! empty($d[1])): ?>
                            <div class="mt-1"><a href="<?= base_url('Admin_Kemitraan/lihat_dokumen/' . $r->id . '/' . $kunci) ?>" target="_blank" rel="noopener" class="text-xs font-bold text-blue-600 dark:text-brand-primary hover:underline"><i class="ph ph-paperclip"></i> <?= html_escape($d[0]) ?></a></div>
                            <?php else: ?>
                            <div class="mt-1 text-[10px] text-gray-400 dark:text-brand-muted/60">Tanpa <?= html_escape(strtolower($d[0])) ?></div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </td>
                    <td class="px-6 py-4">
                        <?php
                            // Peta status domain KKN/Magang -> kelas komponen bersama.
                            $badge_kelas = ['Diajukan' => 'pending', 'Diterima' => 'ok', 'Ditolak' => 'reject'];
                        ?>
                        <?= $this->load->view('admin/components/status_badge', ['label' => $r->status, 'kelas' => $badge_kelas[$r->status] ?? 'pending'], TRUE) ?>
                    </td>
                    <td class="px-6 py-4 text-right relative">
                        <!-- Tersedia pada status APA PUN: koreksi data paling sering
                             dibutuhkan justru setelah diproses, saat mahasiswa
                             mengabari NIM keliru atau periodenya bergeser. -->
                        <a href="<?= base_url('Admin_Kemitraan/ubah/' . $r->id) ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5">Ubah</a>
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
