<?php
/**
 * Master asosiasi pengembang - lihat Admin_Asosiasi.
 *
 * Tata letaknya mengikuti admin/magang_posisi/index.php (master data sejenis):
 * form tambah di atas, sunting inline per baris lewat atribut form=, hapus
 * lewat form POST tersembunyi.
 *
 * Kolom "Dipakai" bukan hiasan - ia yang membuat admin tahu SEBELUM menekan
 * Hapus bahwa asosiasi itu masih menempel di data orang. Penolakannya sendiri
 * tetap di server (Admin_Asosiasi::hapus()), ini cuma supaya tidak perlu
 * mencobanya dulu untuk tahu.
 */
$total_pakai = static function ($kode) use ($pemakaian) {
    return array_sum($pemakaian[$kode] ?? []);
};
?>
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-black text-gray-900 dark:text-white">Asosiasi Pengembang</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-brand-muted">
            Daftar asosiasi yang bisa dipilih pengembang di <span class="font-semibold">/akun/profil</span>
            dan admin di <span class="font-semibold">Direktori SRP2</span>. Nama yang tersimpan di sini
            juga yang tampil di kolom Asosiasi pada direktori publik.
        </p>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/5 dark:bg-brand-card">
        <h2 class="mb-3 text-sm font-black text-gray-900 dark:text-white">Tambah asosiasi</h2>
        <form action="<?= base_url('Admin_Asosiasi/simpan') ?>" method="post">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <label class="text-xs text-gray-500 dark:text-brand-muted">Nama (yang dibaca orang)
                    <input type="text" name="nama" maxlength="100" required placeholder="mis. APERSI"
                           class="mt-1 w-full rounded-lg border border-gray-200 bg-transparent p-2 text-sm dark:border-white/10">
                </label>
                <label class="text-xs text-gray-500 dark:text-brand-muted">Kode (tidak bisa diubah lagi)
                    <input type="text" name="kode" maxlength="30" required placeholder="mis. apersi"
                           class="mt-1 w-full rounded-lg border border-gray-200 bg-transparent p-2 text-sm dark:border-white/10">
                </label>
                <label class="text-xs text-gray-500 dark:text-brand-muted">Urutan tampil
                    <input type="number" name="urutan" min="0" max="999" value="0"
                           class="mt-1 w-full rounded-lg border border-gray-200 bg-transparent p-2 text-sm dark:border-white/10">
                </label>
                <div class="flex items-end justify-between gap-3">
                    <label class="flex items-center gap-2 text-xs text-gray-500 dark:text-brand-muted">
                        <input type="checkbox" name="aktif" value="1" checked> Aktif
                    </label>
                    <button type="submit" class="rounded-xl bg-brand-primary px-4 py-2 text-sm font-bold text-brand-dark hover:opacity-90">Tambah</button>
                </div>
            </div>
            <p class="mt-2 text-[11px] leading-relaxed text-gray-400 dark:text-brand-muted/70">
                <i class="ph ph-info"></i>
                Kode adalah nilai yang tersimpan di data pengembang. Ia <strong>tidak bisa diubah</strong>
                setelah dibuat - kalau diganti, seluruh data yang terlanjur memakainya akan kehilangan
                rujukannya tanpa peringatan. Yang tampil ke orang adalah <strong>Nama</strong>, dan itu bebas diubah.
            </p>
        </form>
    </div>

    <div class="rounded-2xl border border-gray-200 bg-white dark:border-white/5 dark:bg-brand-card overflow-hidden">
        <?php if (empty($rows)): ?>
            <p class="px-5 py-10 text-center text-sm text-gray-500 dark:text-brand-muted">
                Belum ada asosiasi. Selama daftar ini kosong, isian asosiasi di formulir pengembang dan admin tidak punya pilihan apa pun.
            </p>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-black/20">
                    <tr>
                        <th class="px-5 py-4">Nama</th>
                        <th class="px-3 py-4">Kode</th>
                        <th class="px-3 py-4">Urutan</th>
                        <th class="px-3 py-4">Dipakai</th>
                        <th class="px-3 py-4">Aktif</th>
                        <th class="px-5 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                <?php foreach ($rows as $r): $fid = 'asosiasi-' . (int) $r->id; $dipakai = $total_pakai($r->kode); ?>
                    <tr>
                        <form id="<?= $fid ?>" action="<?= base_url('Admin_Asosiasi/simpan') ?>" method="post">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                            <input type="hidden" name="id" value="<?= (int) $r->id ?>">
                        </form>
                        <td class="px-5 py-4">
                            <input form="<?= $fid ?>" name="nama" maxlength="100" required
                                   value="<?= html_escape($r->nama) ?>"
                                   class="w-48 rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm dark:border-white/10">
                        </td>
                        <td class="px-3 py-4">
                            <code class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-600 dark:bg-white/5 dark:text-brand-muted"><?= html_escape($r->kode) ?></code>
                        </td>
                        <td class="px-3 py-4">
                            <input form="<?= $fid ?>" name="urutan" type="number" min="0" max="999"
                                   value="<?= (int) $r->urutan ?>"
                                   class="w-20 rounded-lg border border-gray-200 bg-transparent px-2 py-2 text-sm dark:border-white/10">
                        </td>
                        <td class="px-3 py-4 text-xs">
                            <?php if ($dipakai > 0): ?>
                                <span class="font-bold text-gray-700 dark:text-white"><?= $dipakai ?> data</span>
                                <span class="block text-[10px] text-gray-400">tidak bisa dihapus</span>
                            <?php else: ?>
                                <span class="text-gray-400">belum dipakai</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-3 py-4">
                            <input form="<?= $fid ?>" type="checkbox" name="aktif" value="1" <?= $r->aktif ? 'checked' : '' ?>>
                        </td>
                        <td class="whitespace-nowrap px-5 py-4 text-right">
                            <button type="submit" form="<?= $fid ?>" class="mr-3 text-xs font-bold text-blue-500 hover:underline">Simpan</button>
                            <?php if ($dipakai === 0): ?>
                            <form class="inline" action="<?= base_url('Admin_Asosiasi/hapus') ?>" method="post"
                                  onsubmit="return confirm('Hapus asosiasi <?= html_escape($r->nama) ?>?')">
                                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                                <input type="hidden" name="id" value="<?= (int) $r->id ?>">
                                <button class="text-xs font-bold text-red-500 hover:underline">Hapus</button>
                            </form>
                            <?php else: ?>
                            <span class="text-xs text-gray-300 dark:text-white/20" title="Masih dipakai <?= $dipakai ?> data">Hapus</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
