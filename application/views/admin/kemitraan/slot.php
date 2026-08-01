<?php
/**
 * Daftar divisi magang — satu baris per divisi.
 *
 * Bentuk sebelumnya adalah matriks 7 x 12 kotak centang di satu layar: 84 kotak
 * yang menuntut admin memahami seluruh tahun sekaligus, dan tidak punya tempat
 * untuk menyatakan "Juni cuma tanggal 1–15". Pengaturannya pindah ke layar
 * detail per divisi; layar ini hanya menjawab tiga pertanyaan — divisi apa saja,
 * statusnya bagaimana, bulan mana yang terbuka.
 */
?>
<?php $this->load->view('admin/kemitraan/_tabs', ['tab_aktif' => 'slot']); ?>

<p class="mb-4 text-sm text-gray-500 dark:text-brand-muted">
    Kuota berarti berapa mahasiswa boleh hadir <strong>bersamaan</strong> pada satu divisi.
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
                    <th class="px-6 py-4">Divisi</th>
                    <th class="px-6 py-4">Status</th>
                    <th class="px-6 py-4 text-center">Kuota</th>
                    <th class="px-6 py-4">Bulan Terbuka <?= (int) $tahun ?></th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                <?php if (empty($divisi)): ?>
                    <tr><td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-brand-muted">Belum ada divisi. Tambahkan di bawah.</td></tr>
                <?php else: foreach ($divisi as $d): ?>
                    <?php $r = $ringkas[(int) $d->id] ?? ['label' => [], 'puncak' => 0]; ?>
                    <tr class="<?= (int) $d->aktif ? '' : 'opacity-60' ?>">
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900 dark:text-white"><?= html_escape($d->nama) ?></div>
                            <?php if ($r['puncak'] > 0): ?>
                                <div class="text-xs text-gray-500 dark:text-brand-muted">
                                    Paling ramai <?= (int) $r['puncak'] ?> dari <?= (int) $d->kuota ?> mahasiswa
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <?= $this->load->view('admin/components/status_badge', [
                                'label' => (int) $d->aktif ? 'Aktif' : 'Nonaktif',
                                'kelas' => (int) $d->aktif ? 'ok' : 'reject',
                            ], TRUE) ?>
                        </td>
                        <td class="px-6 py-4 text-center font-bold text-gray-900 dark:text-white"><?= (int) $d->kuota ?></td>
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
                            <a href="<?= base_url('Admin_Kemitraan/slot_divisi/' . (int) $d->id . '/' . (int) $tahun) ?>"
                               class="px-3 py-1.5 rounded-lg text-xs font-bold text-blue-600 dark:text-brand-primary hover:bg-blue-50 dark:hover:bg-brand-primary/10">Detail</a>
                            <button type="submit" form="divisi-<?= (int) $d->id ?>"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/5">
                                <?= (int) $d->aktif ? 'Nonaktifkan' : 'Aktifkan' ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php foreach ($divisi as $d): ?>
    <form id="divisi-<?= (int) $d->id ?>" method="POST" action="<?= base_url('Admin_Kemitraan/ubah_status_divisi/' . (int) $d->id) ?>" class="hidden">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
        <input type="hidden" name="tahun" value="<?= (int) $tahun ?>">
    </form>
<?php endforeach; ?>

<!-- Divisi ditambah, tidak pernah dihapus. Menghapusnya menyeret slotnya lewat
     ON DELETE CASCADE, sementara pendaftaran lama menyimpan NAMA divisi sebagai
     teks dan akan menunjuk ke sesuatu yang tidak ada lagi. -->
<form method="POST" action="<?= base_url('Admin_Kemitraan/tambah_divisi') ?>"
      class="mt-6 flex flex-wrap items-end gap-3 rounded-3xl border border-gray-200 dark:border-white/5 bg-white dark:bg-brand-card p-5">
    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
    <input type="hidden" name="tahun" value="<?= (int) $tahun ?>">
    <div class="min-w-[260px] flex-1">
        <label for="nama-divisi" class="mb-1.5 block text-xs font-bold text-gray-900 dark:text-white">Tambah Divisi</label>
        <input id="nama-divisi" name="nama" required maxlength="150" placeholder="Nama unit kerja, cth: Sekretariat"
               class="w-full rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-black/20 px-4 py-2.5 text-sm text-gray-900 dark:text-white outline-none">
    </div>
    <button type="submit" class="rounded-xl border border-gray-200 dark:border-white/10 px-4 py-2.5 text-xs font-bold text-gray-700 dark:text-gray-300">
        Tambah
    </button>
</form>
