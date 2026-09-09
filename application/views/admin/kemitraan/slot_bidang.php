<?php
/**
 * Detail satu bidang: dua belas bulan, masing-masing dengan rentang tanggalnya
 * dan daftar mahasiswa yang mengisinya.
 *
 * Kolom "Terisi" bukan sekadar angka. Sebelum layar ini ada, "2 dari 2" muncul
 * tanpa bisa ditelusuri ke siapa pun - dan hitungan yang tidak bisa ditelusuri
 * akan dihitung ulang manual di sebelahnya, yang berarti slot ini tidak
 * menghemat apa-apa.
 */
$isian = 'w-full rounded-lg border border-gray-200 dark:border-white/10 bg-white dark:bg-black/20'
    . ' px-2 py-1.5 text-xs text-gray-900 dark:text-white outline-none';
?>
<?php $this->load->view('admin/kemitraan/_tabs', ['tab_aktif' => 'slot']); ?>

<div class="mb-5 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h3 class="text-xl font-black text-gray-900 dark:text-white"><?= html_escape($bidang->nama) ?></h3>
        <p class="text-sm text-gray-500 dark:text-brand-muted">
            Slot tahun <?= (int) $tahun ?>.
            <?= (int) $bidang->aktif ? '' : 'Bidang ini sedang TIDAK MENERIMA pendaftaran magang.' ?>
        </p>
    </div>
    <a href="<?= base_url('Admin_Kemitraan/slot/' . (int) $tahun) ?>"
       class="rounded-xl border border-gray-200 dark:border-white/10 px-4 py-2 text-xs font-bold text-gray-700 dark:text-gray-300">
        Kembali ke Daftar Bidang
    </a>
</div>

<form method="POST" action="<?= base_url('Admin_Kemitraan/simpan_slot_bidang/' . rawurlencode($bidang->kode)) ?>">
    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
    <input type="hidden" name="tahun" value="<?= (int) $tahun ?>">

    <div class="mb-4 flex flex-wrap items-end gap-3 rounded-3xl border border-gray-200 dark:border-white/5 bg-white dark:bg-brand-card p-5">
        <div>
            <label for="kuota" class="mb-1.5 block text-xs font-bold text-gray-900 dark:text-white">Kuota hadir bersamaan</label>
            <input id="kuota" name="kuota" type="number" min="0" max="255" value="<?= (int) $bidang->kuota ?>"
                   class="w-24 rounded-xl border border-gray-200 dark:border-white/10 bg-white dark:bg-black/20 px-3 py-2 text-sm font-bold text-gray-900 dark:text-white outline-none">
        </div>
        <p class="flex-1 text-xs text-gray-500 dark:text-brand-muted">
            Berapa mahasiswa boleh berada di bidang ini pada hari yang sama. Dua orang yang
            periodenya tidak bertumpang tindih tidak saling menghalangi.
        </p>
    </div>

    <div class="bg-white dark:bg-brand-card rounded-3xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[860px] text-left text-sm">
                <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                    <tr>
                        <th class="px-5 py-3">Bulan</th>
                        <th class="px-5 py-3 text-center">Buka</th>
                        <th class="px-5 py-3">Tanggal Mulai</th>
                        <th class="px-5 py-3">Tanggal Selesai</th>
                        <th class="px-5 py-3">Terisi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                    <?php foreach ($nama_bulan as $nomor => $label): ?>
                        <?php
                        $s       = $slot[$nomor] ?? NULL;
                        $awal    = sprintf('%04d-%02d-01', (int) $tahun, (int) $nomor);
                        $akhir   = date('Y-m-t', strtotime($awal));
                        $isi     = (int) ($terisi[(int) $tahun . '-' . (int) $nomor] ?? 0);
                        $penuh   = $isi >= (int) $bidang->kuota;
                        $orang   = $pendaftar[$nomor] ?? [];
                        ?>
                        <tr>
                            <td class="px-5 py-3 font-bold text-gray-900 dark:text-white"><?= html_escape($label) ?></td>
                            <td class="px-5 py-3 text-center">
                                <input type="checkbox" name="bulan[<?= (int) $nomor ?>][buka]" value="1"
                                       aria-label="Buka <?= html_escape($label) ?>"
                                       class="h-6 w-6 cursor-pointer rounded border border-gray-300 dark:border-white/10 accent-emerald-500"
                                       <?= $s ? 'checked' : '' ?>>
                            </td>
                            <!-- min/max mengunci pemilih tanggal ke bulan barisnya. Kalau
                                 admin tetap mengirim tanggal di luar itu, model MENJEPITNYA
                                 ke batas bulan - bukan menolak, karena maksudnya sudah
                                 terbaca dan bulan sebelah punya barisnya sendiri. -->
                            <td class="px-5 py-3">
                                <input type="date" name="bulan[<?= (int) $nomor ?>][mulai]" class="<?= $isian ?>"
                                       min="<?= $awal ?>" max="<?= $akhir ?>"
                                       value="<?= $s ? html_escape($s->tgl_mulai) : $awal ?>">
                            </td>
                            <td class="px-5 py-3">
                                <input type="date" name="bulan[<?= (int) $nomor ?>][selesai]" class="<?= $isian ?>"
                                       min="<?= $awal ?>" max="<?= $akhir ?>"
                                       value="<?= $s ? html_escape($s->tgl_selesai) : $akhir ?>">
                            </td>
                            <td class="px-5 py-3">
                                <?php if (empty($orang)): ?>
                                    <span class="text-xs text-gray-400 dark:text-brand-muted/60">-</span>
                                <?php else: ?>
                                    <div class="mb-1 text-xs font-bold <?= $penuh ? 'text-amber-500' : 'text-gray-500 dark:text-brand-muted' ?>">
                                        Paling ramai <?= $isi ?> dari <?= (int) $bidang->kuota ?>
                                    </div>
                                    <?php foreach ($orang as $o): ?>
                                        <div class="text-[11px] text-gray-600 dark:text-gray-400">
                                            <a href="<?= base_url('Admin_Kemitraan/ubah/' . (int) $o->id) ?>" class="font-bold hover:underline"><?= html_escape($o->nama_mahasiswa ?: '(tanpa nama)') ?></a>
                                            &middot; <?= date('j M', strtotime($o->periode_mulai)) ?>-<?= date('j M', strtotime($o->periode_selesai)) ?>
                                            &middot; <?= html_escape($o->status) ?>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 dark:border-white/5 px-5 py-4">
            <p class="text-xs text-gray-500 dark:text-brand-muted">
                Bulan yang kotak bukanya tidak dicentang akan ditutup, apa pun tanggal yang tertulis di sebelahnya.
            </p>
            <button type="submit" class="rounded-xl bg-emerald-500 px-5 py-2.5 text-xs font-bold text-white">
                Simpan <?= html_escape($bidang->nama) ?> <?= (int) $tahun ?>
            </button>
        </div>
    </div>
</form>
