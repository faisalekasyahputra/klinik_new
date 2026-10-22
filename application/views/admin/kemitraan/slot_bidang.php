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
<?php
// $mode_bidang: layar yang sama dibuka admin bidang untuk bidangnya sendiri (Kemitraan_Bidang::kuota).
$mode_bidang = ! empty($mode_bidang);
if ( ! $mode_bidang) { $this->load->view('admin/kemitraan/_tabs', ['tab_aktif' => 'slot']); }
?>

<div class="mb-5 flex flex-wrap items-start justify-between gap-4">
    <div>
        <h3 class="text-xl font-black text-gray-900 dark:text-white"><?= html_escape($bidang->nama) ?></h3>
        <p class="text-sm text-gray-500 dark:text-brand-muted">
            Slot tahun <?= (int) $tahun ?>.
            <?= (int) $bidang->aktif ? '' : 'Bidang ini sedang tidak menerima pendaftaran magang.' ?>
        </p>
    </div>
    <?php if ($mode_bidang): ?>
    <div class="flex gap-2">
        <?php foreach ([$tahun - 1, $tahun + 1] as $t): if ($t < 2020 || $t > (int) date('Y') + 5) { continue; } ?>
        <a href="<?= base_url('Kemitraan_Bidang/kuota/' . (int) $t) ?>" class="tombol-kedua"><span class="panah-sorot" aria-hidden="true"><i class="ph <?= $t < $tahun ? 'ph-caret-left' : 'ph-caret-right' ?>"></i></span><span>Tahun <?= (int) $t ?></span></a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <a href="<?= base_url('Admin_Kemitraan/slot/' . (int) $tahun) ?>" class="tombol-kedua"><span class="panah-sorot" aria-hidden="true"><i class="ph ph-caret-left"></i></span><span>Kembali ke daftar bidang</span></a>
    <?php endif; ?>
</div>

<form method="POST" action="<?= base_url($mode_bidang ? 'Kemitraan_Bidang/simpan_kuota' : 'Admin_Kemitraan/simpan_slot_bidang/' . rawurlencode($bidang->kode)) ?>">
    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
    <input type="hidden" name="tahun" value="<?= (int) $tahun ?>">

    <div class="kartu-admin isi-kartu mb-4 flex flex-wrap items-end gap-3">
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

    <div class="kartu-admin overflow-hidden">
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
                        // Bulan yang sudah lewat dikunci: membukanya tidak berarti apa-apa bagi
                        // mahasiswa, dan server pun mengabaikan isiannya (tulis_ulang_bidang()).
                        $lewat   = $this->slot->bulan_lewat($tahun, $nomor);
                        $kunci   = $lewat ? ' disabled' : '';
                        ?>
                        <tr<?= $lewat ? ' class="opacity-50" data-bulan-lewat' : '' ?>>
                            <td class="px-5 py-3 font-bold text-gray-900 dark:text-white">
                                <?= html_escape($label) ?>
                                <?php if ($lewat): ?><span class="ml-1 block text-[11px] font-semibold text-gray-500 dark:text-brand-muted">Sudah lewat</span><?php endif; ?>
                            </td>
                            <td class="px-5 py-3 text-center">
                                <input type="checkbox" name="bulan[<?= (int) $nomor ?>][buka]" value="1"
                                       aria-label="Buka <?= html_escape($label) ?>"
                                       class="h-6 w-6 cursor-pointer rounded border border-gray-300 dark:border-white/10 accent-emerald-500"
                                       <?= $s ? 'checked' : '' ?><?= $kunci ?>>
                            </td>
                            <!-- min/max mengunci pemilih tanggal ke bulan barisnya. Kalau
                                 admin tetap mengirim tanggal di luar itu, model MENJEPITNYA
                                 ke batas bulan - bukan menolak, karena maksudnya sudah
                                 terbaca dan bulan sebelah punya barisnya sendiri. -->
                            <td class="px-5 py-3">
                                <input type="date" name="bulan[<?= (int) $nomor ?>][mulai]" class="<?= $isian ?>"
                                       min="<?= $awal ?>" max="<?= $akhir ?>"
                                       value="<?= $s ? html_escape($s->tgl_mulai) : $awal ?>"<?= $kunci ?>>
                            </td>
                            <td class="px-5 py-3">
                                <input type="date" name="bulan[<?= (int) $nomor ?>][selesai]" class="<?= $isian ?>"
                                       min="<?= $awal ?>" max="<?= $akhir ?>"
                                       value="<?= $s ? html_escape($s->tgl_selesai) : $akhir ?>"<?= $kunci ?>>
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
                                            <?php if ($mode_bidang): ?>
                                            <span class="font-bold"><?= html_escape($o->nama_mahasiswa ?: '(tanpa nama)') ?></span>
                                            <?php else: ?>
                                            <a href="<?= base_url('Admin_Kemitraan/ubah/' . (int) $o->id) ?>" class="font-bold hover:underline"><?= html_escape($o->nama_mahasiswa ?: '(tanpa nama)') ?></a>
                                            <?php endif; ?>
                                            &middot; <?= tgl_id($o->periode_mulai, TRUE) ?> - <?= tgl_id($o->periode_selesai, TRUE) ?>
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
                Bulan yang sudah lewat terkunci dan tidak berubah saat disimpan.
            </p>
            <button type="submit" class="tombol-utama"><i class="ph ph-floppy-disk"></i><span>Simpan <?= html_escape($bidang->nama) ?> <?= (int) $tahun ?></span></button>
        </div>
    </div>
</form>


<?php if ( ! $mode_bidang): ?>
<?php
/* Posisi yang dicari bidang ini, dikelola di sini juga (satu halaman per bidang, 22 Okt 2026).
   Hanya superadmin: admin bidang membuka layar ini lewat Kemitraan_Bidang dan belum boleh
   mengubah posisi. Formulirnya sama dengan layar Posisi Magang; bidangnya dikunci dan
   simpan kembali ke sini. */
$isian_posisi = 'mt-1 w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-white/10 dark:text-white';
$tanpa_awalan = fn($n) => preg_replace('/^Bidang\s+/i', '', (string) $n);
?>
<div class="mt-6" id="posisi" data-posisi-bidang x-data="<?= html_escape($this->load->view('admin/magang_posisi/_alpine', ['bidang_awal' => $bidang->kode], TRUE)) ?>">
    <div class="mb-3 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h3 class="text-base font-black text-gray-900 dark:text-white">Posisi yang dicari</h3>
            <p class="text-xs text-gray-500 dark:text-brand-muted">Tampil di papan magang dan formulir daftar untuk bidang ini. Hanya keterangan; batas pendaftar tetap kuota di atas.</p>
        </div>
        <button type="button" class="tombol-utama" @click="tambah()" data-posisi-tambah><i class="ph ph-plus" aria-hidden="true"></i><span>Tambah posisi</span></button>
    </div>
    <?php if (empty($posisi)): ?>
        <p class="kartu-admin isi-kartu text-center text-sm text-gray-500 dark:text-brand-muted">Belum ada posisi. Papan magang hanya menampilkan nama bidang.</p>
    <?php else: ?>
        <div class="kartu-admin overflow-x-auto aksi-tetap">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="bg-gray-50 text-xs uppercase text-gray-500 dark:bg-black/20">
                    <tr>
                        <th class="px-4 py-3">Posisi dan kualifikasi</th>
                        <th class="px-4 py-3">Jumlah</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                <?php foreach ($posisi as $r): $kual = daftar_kualifikasi($r->keterangan); ?>
                    <tr>
                        <td class="px-4 py-3">
                            <div class="font-bold text-gray-900 dark:text-white"><?= html_escape($r->nama_posisi) ?></div>
                            <?php if ($kual): ?>
                                <ul class="mt-0.5 list-disc pl-4 text-xs text-gray-500 dark:text-brand-muted"><?php foreach (array_slice($kual, 0, 3) as $k): ?><li><?= html_escape($k) ?></li><?php endforeach; ?></ul>
                                <?php if (count($kual) > 3): ?>
                                    <details class="text-xs text-gray-500 dark:text-brand-muted">
                                        <summary class="cursor-pointer font-semibold text-brand-600 hover:underline dark:text-brand-300">Selengkapnya (<?= count($kual) ?> kualifikasi)</summary>
                                        <ul class="list-disc pl-4"><?php foreach (array_slice($kual, 3) as $k): ?><li><?= html_escape($k) ?></li><?php endforeach; ?></ul>
                                    </details>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-xs"><?= (int) $r->kuota ?> orang</td>
                        <td class="px-4 py-3">
                            <?= $this->load->view('admin/components/status_badge', [
                                'label' => $r->aktif ? 'Ditampilkan' : 'Disembunyikan',
                                'kelas' => $r->aktif ? 'ok' : 'reject',
                            ], TRUE) ?>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <button type="button" class="tombol-aksi" @click="ubah($el.dataset)" data-posisi-ubah
                                    data-id="<?= (int) $r->id ?>" data-bidang="<?= html_escape($r->bidang_kode) ?>" data-posisi="<?= html_escape($r->nama_posisi) ?>"
                                    data-kual="<?= html_escape(json_encode($kual)) ?>" data-kuota="<?= (int) $r->kuota ?>" data-urutan="<?= (int) $r->urutan ?>" data-aktif="<?= $r->aktif ? '1' : '0' ?>">
                                <i class="ph ph-pencil-simple" aria-hidden="true"></i><span>Ubah</span>
                            </button>
                            <form class="inline" action="<?= base_url('Admin_Magang_Posisi/hapus') ?>" method="post"
                                  data-konfirmasi="Posisi <?= html_escape($r->nama_posisi) ?> akan dihapus dari daftar. Pendaftaran yang sudah masuk tidak berubah." data-konfirmasi-judul="Hapus posisi?" data-konfirmasi-label="Hapus" data-konfirmasi-bahaya>
                                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                                <input type="hidden" name="id" value="<?= (int) $r->id ?>">
                                <input type="hidden" name="asal" value="slot">
                                <input type="hidden" name="tahun" value="<?= (int) $tahun ?>">
                                <button class="tombol-aksi tombol-aksi-bahaya"><i class="ph ph-trash" aria-hidden="true"></i><span>Hapus</span></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    <?php $this->load->view('admin/magang_posisi/_form', ['bidang' => [], 'isian' => $isian_posisi, 'tanpa_awalan' => $tanpa_awalan,
        'asal' => ['kode' => $bidang->kode, 'tahun' => $tahun]]); ?>
</div>
<?php endif; ?>
