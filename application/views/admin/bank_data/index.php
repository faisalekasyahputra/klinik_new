<?php
/* Bank Data admin (migrasi 063). Kartu di tab Bank Data publik dibangun dari baris yang aktif. */
$kolom = 'mt-1 block w-full rounded-lg border border-gray-200 dark:border-white/10 bg-transparent px-3 py-2 text-sm';
?>
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-gray-900 dark:text-white">Bank Data</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-brand-muted max-w-2xl">
            Unggah PDF Buku Data dan PDF Statistika. Dokumen yang aktif tampil sebagai kartu di tab
            <span class="font-semibold">Bank Data</span> portal dan dibuka dengan pembaca halaman.
        </p>
    </div>

    <div class="rounded-2xl bg-white dark:bg-brand-card border border-gray-200 dark:border-white/5 p-5">
        <h2 class="mb-3 text-sm font-black text-gray-900 dark:text-white">Unggah dokumen</h2>
        <form action="<?= base_url('Admin_Bank_Data/simpan') ?>" method="post" enctype="multipart/form-data" class="grid gap-3 md:grid-cols-2">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
            <label class="text-xs text-gray-500 dark:text-brand-muted">Jenis
                <select name="jenis" required class="<?= $kolom ?>">
                    <?php foreach ($jenis as $k => $label): ?><option value="<?= html_escape($k) ?>"><?= html_escape($label) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label class="text-xs text-gray-500 dark:text-brand-muted">Urutan tampil (kecil lebih dulu)
                <input type="number" name="urutan" min="0" max="999" value="0" class="<?= $kolom ?>">
            </label>
            <label class="text-xs text-gray-500 dark:text-brand-muted md:col-span-2">Judul kartu
                <input type="text" name="judul" maxlength="150" required placeholder="mis. Buku Data Perumahan Jawa Tengah 2026" class="<?= $kolom ?>">
            </label>
            <label class="text-xs text-gray-500 dark:text-brand-muted md:col-span-2">Deskripsi singkat (opsional)
                <input type="text" name="deskripsi" maxlength="255" class="<?= $kolom ?>">
            </label>
            <label class="text-xs text-gray-500 dark:text-brand-muted md:col-span-2">Berkas PDF (maksimal 20 MB)
                <input type="file" name="berkas_pdf" accept="application/pdf,.pdf" required class="<?= $kolom ?>">
            </label>
            <div class="md:col-span-2">
                <button type="submit" class="rounded-xl bg-brand-primary px-5 py-2.5 text-sm font-bold text-brand-dark hover:opacity-90">Unggah</button>
            </div>
        </form>
    </div>

    <div data-tabel-admin style="counter-reset: baris-admin 0" class="rounded-2xl bg-white dark:bg-brand-card border border-gray-200 dark:border-white/5 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                    <tr><th class="px-4 py-4">Dokumen</th><th class="px-4 py-4">Jenis</th><th class="px-4 py-4">Status</th><th class="px-4 py-4 text-right">Aksi</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                    <?php if (empty($rows)): ?>
                    <tr><td colspan="4" class="px-4 py-10 text-center text-gray-500 dark:text-brand-muted">Belum ada dokumen. Tab Bank Data masih menampilkan contoh.</td></tr>
                    <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td class="px-4 py-3">
                            <a href="<?= base_url($r->berkas) ?>" target="_blank" rel="noopener" class="font-bold text-gray-900 dark:text-white hover:underline"><?= html_escape($r->judul) ?></a>
                            <?php if ($r->deskripsi): ?><div class="text-xs text-gray-500 dark:text-brand-muted"><?= html_escape($r->deskripsi) ?></div><?php endif; ?>
                            <div class="text-[10px] text-gray-400"><?= number_format($r->ukuran / 1048576, 1, ',', '.') ?> MB, urutan <?= (int) $r->urutan ?></div>
                        </td>
                        <td class="px-4 py-3 text-xs font-bold"><?= html_escape($jenis[$r->jenis] ?? $r->jenis) ?></td>
                        <td class="px-4 py-3 text-xs font-bold <?= $r->aktif ? 'text-emerald-600' : 'text-gray-400' ?>"><?= $r->aktif ? 'Tampil' : 'Disembunyikan' ?></td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <form action="<?= base_url('Admin_Bank_Data/ubah_status/' . (int) $r->id) ?>" method="post" class="inline">
                                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                                <button type="submit" class="px-2.5 py-1.5 rounded-lg text-xs font-bold text-blue-600 dark:text-brand-primary hover:bg-blue-50 dark:hover:bg-brand-primary/10"><?= $r->aktif ? 'Sembunyikan' : 'Tampilkan' ?></button>
                            </form>
                            <form action="<?= base_url('Admin_Bank_Data/hapus/' . (int) $r->id) ?>" method="post" class="inline" onsubmit="return confirm('Hapus dokumen ini beserta berkasnya?')">
                                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                                <button type="submit" class="px-2.5 py-1.5 rounded-lg text-xs font-bold text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
