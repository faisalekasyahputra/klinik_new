<?php
/**
 * Meja kedua: bidang memutuskan pendaftaran magang yang sudah diteruskan
 * sekretariat Disperakim.
 *
 * Yang tampil hanya pendaftaran yang bidang tujuannya adalah bidang akun ini -
 * penyaringnya kolom `bidang_kode` pada pendaftaran, bukan sesuatu yang dikirim
 * peramban. Tombol keputusan hanya muncul untuk status 'Ditinjau Bidang'; yang
 * lain sudah lewat mejanya, dan tetap ditampilkan sebagai riwayat.
 */
$this->load->helper('admin_table');
$badge_kelas = [
    'Diajukan' => 'pending', 'Ditinjau Bidang' => 'process',
    'Diterima' => 'ok', 'Ditolak' => 'reject', 'Dibatalkan' => 'reject',
];
?>
<div class="mb-6">
    <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2">Magang Bidang Saya</h2>
    <p class="text-sm text-gray-500 dark:text-brand-muted">
        Surat pengantar yang sudah diteruskan sekretariat dan menunggu keputusan bidang Anda.
        Surat balasan resmi disiapkan sekretariat setelah Anda menerima.
    </p>
</div>

<div data-tabel-admin class="bg-white dark:bg-brand-card rounded-3xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
    <?= $this->load->view('admin/components/table_toolbar', ['table' => $table, 'base_url' => $base_url, 'placeholder' => 'Cari mahasiswa, instansi, bidang...'], TRUE) ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-4"><?= admin_sort_header('Mahasiswa', 'usr_users.name', $table, $base_url) ?></th>
                    <th class="px-4 py-4"><?= admin_sort_header('Instansi Asal', 'kkn_magang_pendaftaran.instansi_asal', $table, $base_url) ?></th>
                    <!-- Bukan "Bidang": seluruh baris di meja ini SUDAH bidang
                         Anda, dan yang ditampilkan sel itu tema kegiatan. Judul
                         lama menjanjikan nama bidang lalu memberi hal lain. -->
                    <th class="px-4 py-4">Tema &amp; Periode</th>
                    <th class="px-4 py-4"><?= admin_sort_header('Status', 'kkn_magang_pendaftaran.status', $table, $base_url) ?></th>
                    <th class="px-4 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                <?php if (empty($rows)): ?>
                    <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-brand-muted">Belum ada pendaftaran magang untuk bidang Anda.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr x-data="{ procOpen: false }">
                        <td class="px-4 py-4">
                            <div class="font-bold text-gray-900 dark:text-white"><?= html_escape($r->nama_mahasiswa ?: '-') ?></div>
                            <div class="text-xs text-gray-500 dark:text-brand-muted"><?= html_escape($r->email_mahasiswa ?: '-') ?></div>
                            <?php $identitas = array_filter([$r->nim, $r->jurusan, $r->semester ? 'Smt ' . (int) $r->semester : NULL]); ?>
                            <?php if ($identitas): ?>
                                <div class="mt-1 text-xs text-gray-500 dark:text-brand-muted"><?= html_escape(implode(' · ', $identitas)) ?></div>
                            <?php endif; ?>
                        </td>
                        <!-- Boleh membungkus. Dengan `whitespace-nowrap` milik
                             tabel, nama kampus panjang mendorong lebar tabel
                             melewati viewport dan kolom Aksi - satu-satunya
                             tombol di layar ini - hilang di balik gulir
                             horizontal tanpa petunjuk apa pun. -->
                        <td class="px-4 py-4 max-w-[16rem] whitespace-normal"><?= html_escape($r->instansi_asal) ?></td>
                        <td class="px-4 py-4">
                            <div class="font-semibold"><?= html_escape($r->divisi_atau_tema) ?></div>
                            <div class="text-xs text-gray-500 dark:text-brand-muted">
                                <?= tgl_id($r->periode_mulai, TRUE) ?> - <?= tgl_id($r->periode_selesai, TRUE) ?>
                            </div>
                            <?php
                            $dokumen = ['surat' => ['Surat pengantar', $r->file_surat_pengantar], 'proposal' => ['Proposal', $r->file_proposal]];
                            foreach ($dokumen as $kunci => $d):
                                if (empty($d[1])) { continue; } ?>
                                <div class="mt-1">
                                    <a href="<?= base_url('Kemitraan_Bidang/lihat_dokumen/' . $r->id . '/' . $kunci) ?>" target="_blank" rel="noopener"
                                       class="text-xs font-bold text-blue-600 dark:text-brand-primary hover:underline"><i class="ph ph-paperclip"></i> <?= html_escape($d[0]) ?></a>
                                </div>
                            <?php endforeach; ?>
                        </td>
                        <td class="px-4 py-4">
                            <?= $this->load->view('admin/components/status_badge', ['label' => $r->status, 'kelas' => $badge_kelas[$r->status] ?? 'pending'], TRUE) ?>
                            <?php if ( ! empty($r->catatan_admin)): ?>
                                <div class="mt-1 max-w-xs whitespace-normal text-xs text-gray-500 dark:text-brand-muted">
                                    Sekretariat: <?= html_escape($r->catatan_admin) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4 text-right relative">
                            <?php if ($r->status === 'Ditinjau Bidang'): ?>
                                <button @click="procOpen = !procOpen" class="px-3 py-1.5 rounded-lg text-xs font-bold text-blue-600 dark:text-brand-primary hover:bg-blue-50 dark:hover:bg-brand-primary/10">Putuskan</button>
                                <div x-show="procOpen" x-cloak @click.outside="procOpen = false" class="absolute right-6 top-full mt-1 z-20 w-72 whitespace-normal rounded-2xl border border-gray-200 dark:border-white/10 bg-white dark:bg-brand-card p-4 text-left shadow-xl">
                                    <?= $this->load->view('admin/components/review_form', [
                                        'action_url' => 'Kemitraan_Bidang/proses/' . $r->id,
                                        'buttons' => [
                                            ['value' => 'Diterima', 'label' => 'Terima', 'style' => 'accept'],
                                            ['value' => 'Ditolak', 'label' => 'Tolak', 'style' => 'reject'],
                                        ],
                                        'catatan_name' => 'catatan_admin',
                                    ], TRUE) ?>
                                </div>
                            <?php else: ?>
                                <span class="text-xs text-gray-400 dark:text-brand-muted/60">Sudah lewat meja Anda</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?= $this->load->view('admin/components/pagination', ['pager' => $pager, 'base_url' => $base_url], TRUE) ?>
</div>
