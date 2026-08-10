<?php
/**
 * Daftar bidang penerima magang - satu baris per bidang.
 *
 * Daftarnya TIDAK bisa ditambah atau dihapus dari sini: lima bidang itu
 * struktur organisasi dinas (dikonfirmasi 1 Agt 2026), bukan data milik modul
 * magang. Yang bisa diatur cuma kuota, aktif/nonaktif, dan bulan mana yang
 * dibuka - dan ketiganya ada di layar detail.
 */
?>
<?php $this->load->view('admin/kemitraan/_tabs', ['tab_aktif' => 'slot']); ?>

<p class="mb-4 text-sm text-gray-500 dark:text-brand-muted">
    Kuota berarti berapa mahasiswa boleh hadir <strong>bersamaan</strong> pada satu bidang.
    Buka detail untuk mengatur bulan dan tanggalnya.
</p>

<div class="mb-4 flex flex-wrap items-center gap-2">
    <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-brand-muted">Tahun</span>
    <?php foreach ($tahun_tersedia as $t): ?>
        <a href="<?= base_url('Admin_Kemitraan/slot/' . (int) $t) ?>"
           class="rounded-lg px-3 py-1.5 text-xs font-bold <?= (int) $t === (int) $tahun
                ? 'bg-emerald-500 text-white'
                : 'border border-gray-200 dark:border-white/10 text-gray-700 dark:text-gray-300' ?>"><?= (int) $t ?></a>
    <?php endforeach; ?>
    <a href="<?= base_url('Admin_Kemitraan/slot/' . ((int) date('Y') + 1)) ?>"
       class="rounded-lg border border-dashed border-gray-300 dark:border-white/20 px-3 py-1.5 text-xs font-bold text-gray-500 dark:text-brand-muted">
        + <?= (int) date('Y') + 1 ?>
    </a>
</div>

<div class="bg-white dark:bg-brand-card rounded-3xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-6 py-4">Bidang</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-center">Kuota</th>
                    <th class="px-6 py-4">Bulan Terbuka <?= (int) $tahun ?></th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                <?php foreach ($bidang as $b): ?>
                    <?php $r = $ringkas[$b->kode] ?? ['label' => [], 'puncak' => 0]; ?>
                    <tr class="<?= (int) $b->aktif ? '' : 'opacity-60' ?>">
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900 dark:text-white"><?= html_escape($b->nama) ?></div>
                            <?php if ($r['puncak'] > 0): ?>
                                <div class="text-xs text-gray-500 dark:text-brand-muted">
                                    Paling ramai <?= (int) $r['puncak'] ?> dari <?= (int) $b->kuota ?> mahasiswa
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <?= $this->load->view('admin/components/status_badge', [
                                'label' => (int) $b->aktif ? 'Menerima' : 'Tidak Menerima',
                                'kelas' => (int) $b->aktif ? 'ok' : 'reject',
                            ], TRUE) ?>
                        </td>
                        <td class="px-6 py-4 text-center font-bold text-gray-900 dark:text-white"><?= (int) $b->kuota ?></td>
                        <td class="px-6 py-4">
                            <?php if (empty($r['label'])): ?>
                                <span class="text-xs text-gray-400 dark:text-brand-muted/60">Belum ada bulan dibuka</span>
                            <?php else: ?>
                                <div class="flex flex-wrap gap-1.5">
                                    <?php foreach ($r['label'] as $label): ?>
                                        <span class="rounded-lg bg-emerald-500/10 px-2 py-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400"><?= html_escape($label) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            <a href="<?= base_url('Admin_Kemitraan/slot_bidang/' . rawurlencode($b->kode) . '/' . (int) $tahun) ?>"
                               class="px-3 py-1.5 rounded-lg text-xs font-bold text-blue-600 dark:text-brand-primary hover:bg-blue-50 dark:hover:bg-brand-primary/10">Detail</a>
                            <button type="submit" form="bidang-<?= html_escape($b->kode) ?>"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5">
                                <?= (int) $b->aktif ? 'Setop Terima' : 'Mulai Terima' ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($bidang as $b): ?>
    <form id="bidang-<?= html_escape($b->kode) ?>" method="POST"
          action="<?= base_url('Admin_Kemitraan/ubah_status_bidang/' . rawurlencode($b->kode)) ?>" class="hidden">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
        <input type="hidden" name="tahun" value="<?= (int) $tahun ?>">
    </form>
<?php endforeach; ?>

<p class="mt-6 text-xs text-gray-500 dark:text-brand-muted">
    Daftar bidang mengikuti struktur dinas dan tidak diubah dari layar ini.
    Kalau ada bidang baru atau berganti nama, itu perubahan struktur - ubah lewat
    tabel <code>bidang</code>, karena modul aduan juga memakainya.
</p>
