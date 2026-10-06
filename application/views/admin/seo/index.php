<?php
/* SEO Halaman (Admin_Seo::index): semua halaman portal yang SEO-nya bisa diatur, dengan nilai yang
   BERLAKU sekarang (timpaan admin bila ada, selain itu bawaan). */
$e = fn($v) => html_escape((string) $v);
$ditimpa = count(array_filter($halaman, fn($h) => $h['timpaan'] !== NULL));
$this->load->view('admin/components/judul_halaman', ['jh_deskripsi' => 'Judul, deskripsi, dan gambar pratinjau yang tampil di hasil pencarian Google
    dan saat tautan dibagikan di WhatsApp atau Facebook. Kosongkan isian untuk memakai nilai bawaan.
    Halaman pribadi (akun, login, forum) tidak ada di daftar ini karena memang tidak boleh diindeks.']);
?>
<div class="kartu-admin overflow-hidden" data-seo-daftar>
    <?php $this->load->view('admin/components/kepala_tabel', ['kt_judul' => 'Halaman portal', 'kt_jumlah' => count($halaman),
        'kt_keterangan' => $ditimpa . ' halaman memakai isian admin, sisanya bawaan. Peta situs: ' . base_url('sitemap.xml')]); ?>
    <div class="overflow-x-auto aksi-tetap">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr><th class="px-4 py-3">Pratinjau</th><th class="px-4 py-3">Halaman dan judul</th><th class="px-4 py-3">Indeks</th><th class="px-4 py-3 text-right">Aksi</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
            <?php foreach ($halaman as $kunci => $h): $ef = $h['efektif']; ?>
                <tr>
                    <td class="px-4 py-3 w-36"><img src="<?= base_url($ef['gambar']) ?>" alt="" loading="lazy" class="w-32 rounded-lg border border-gray-200 object-cover dark:border-white/10" style="aspect-ratio:1.91/1"></td>
                    <td class="px-4 py-3" style="min-width:16rem">
                        <div class="flex flex-wrap items-center gap-1.5 text-xs">
                            <code class="text-gray-500 dark:text-brand-muted">/<?= $e($kunci) ?></code>
                            <span class="rounded bg-gray-100 px-1.5 py-0.5 font-bold text-gray-600 dark:bg-white/10 dark:text-gray-300"><?= $e($h['kelompok']) ?></span>
                            <?php if ($h['timpaan']): ?><span class="rounded bg-blue-50 px-1.5 py-0.5 font-bold text-blue-700 dark:bg-brand-primary/10 dark:text-brand-primary" data-seo-ditimpa>Isian admin</span><?php endif; ?>
                        </div>
                        <div class="mt-1 font-bold text-gray-900 dark:text-white"><?= $e($ef['judul']) ?></div>
                        <div class="mt-0.5 line-clamp-2 text-xs text-gray-500 dark:text-brand-muted"><?= $e($ef['deskripsi']) ?></div>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <?php if ($ef['noindex']): ?>
                            <span class="text-xs font-bold text-amber-700 dark:text-amber-300">Disembunyikan</span>
                        <?php else: ?>
                            <span class="text-xs font-bold text-emerald-700 dark:text-emerald-300">Diindeks</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a href="<?= base_url('Admin_Seo/ubah?halaman=' . rawurlencode($kunci === '' ? '/' : $kunci)) ?>" class="tombol-aksi"><i class="ph ph-pencil-simple"></i><span>Ubah</span></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
