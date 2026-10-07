<?php
/* Sertifikat KKN (Admin_Kemitraan::sertifikat): permintaan dari mahasiswa lebih dulu, lalu KKN Diterima yang belum
   bertanggal sertifikat. Menetapkan tanggal menjawab permintaannya; Abaikan menutupnya tanpa tanggal. */
$csrf = '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">';
$diminta = count(array_filter($rows, fn($r) => ! empty($r->sertifikat_diminta_at)));
$this->load->view('admin/kemitraan/_tabs', ['tab_aktif' => 'sertifikat']);
?>
<div class="kartu-admin overflow-hidden" data-sertifikat-kkn>
    <?php $this->load->view('admin/components/kepala_tabel', ['kt_judul' => 'Sertifikat KKN belum terbit', 'kt_jumlah' => count($rows),
        'kt_keterangan' => $diminta . ' diminta mahasiswa. Sertifikat bisa dicetak peserta sesudah periode selesai, daftar peserta terisi, dan tanggal sertifikat ditetapkan.']); ?>
    <div class="overflow-x-auto aksi-tetap">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr><th class="px-4 py-3">KKN</th><th class="px-4 py-3">Periode</th><th class="px-4 py-3">Peserta</th><th class="px-4 py-3">Permintaan</th><th class="px-4 py-3">Tanggal sertifikat</th><th class="px-4 py-3 text-right">Aksi</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
            <?php if ( ! $rows): ?>
                <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500 dark:text-brand-muted">Tidak ada sertifikat yang menunggu. Catat KKN baru dengan tombol Catat KKN.</td></tr>
            <?php endif; ?>
            <?php foreach ($rows as $r): $lewat = ! empty($r->periode_selesai) && $r->periode_selesai < date('Y-m-d'); ?>
                <tr data-sertifikat-baris="<?= (int) $r->id ?>">
                    <td class="px-4 py-3" style="min-width:14rem">
                        <div class="font-bold text-gray-900 dark:text-white"><?= html_escape($r->instansi_asal) ?></div>
                        <div class="text-xs text-gray-500 dark:text-brand-muted"><?= html_escape($r->divisi_atau_tema ?: '-') ?></div>
                        <?php if ($r->status !== 'Diterima'): ?><div class="mt-1 text-[11px] font-bold text-amber-700 dark:text-amber-300">Status <?= html_escape($r->status) ?>: terima dulu di tab Pendaftaran</div><?php endif; ?>
                        <?php if ( ! empty($r->dicatat_oleh)): ?><div class="mt-1 text-[11px] text-gray-500 dark:text-brand-muted">Dicatat admin</div><?php endif; ?>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-xs"><?= html_escape(tgl_id($r->periode_mulai, TRUE) . ' - ' . tgl_id($r->periode_selesai, TRUE)) ?><?php if ( ! $lewat): ?><div class="text-[11px] text-gray-500 dark:text-brand-muted">belum selesai</div><?php endif; ?></td>
                    <td class="px-4 py-3 whitespace-nowrap">
                        <span class="font-bold <?= (int) $r->jumlah_peserta === 0 ? 'text-amber-700 dark:text-amber-300' : 'text-gray-900 dark:text-white' ?>"><?= (int) $r->jumlah_peserta ?></span>
                        <?php if ((int) $r->jumlah_peserta === 0): ?><div class="text-[11px] text-amber-700 dark:text-amber-300">daftar peserta kosong</div><?php endif; ?>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-xs">
                        <?php if ( ! empty($r->sertifikat_diminta_at)): ?>
                            <span class="font-bold text-blue-700 dark:text-brand-primary" data-sertifikat-diminta><?= (int) $r->sertifikat_diminta_jumlah ?>x diminta</span>
                            <div class="text-[11px] text-gray-500 dark:text-brand-muted">sejak <?= html_escape(tgl_id($r->sertifikat_diminta_at, TRUE)) ?></div>
                        <?php else: ?><span class="text-gray-400 dark:text-brand-muted">-</span><?php endif; ?>
                    </td>
                    <td class="px-4 py-3">
                        <?php if ($r->status === 'Diterima'): ?>
                        <form method="post" action="<?= base_url('Admin_Kemitraan/tanggal_sertifikat/' . (int) $r->id) ?>" class="flex items-center gap-2">
                            <?= $csrf ?><input type="hidden" name="kembali" value="sertifikat">
                            <input type="date" name="tanggal_sertifikat" required value="<?= date('Y-m-d') ?>" aria-label="Tanggal sertifikat" class="rounded-lg border border-gray-200 bg-transparent px-2 py-1.5 text-xs text-gray-800 dark:border-white/10 dark:text-white">
                            <button type="submit" class="tombol-aksi"><i class="ph ph-seal-check"></i><span>Terbitkan</span></button>
                        </form>
                        <?php else: ?><span class="text-xs text-gray-400 dark:text-brand-muted">-</span><?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a href="<?= base_url('Admin_Kemitraan/peserta/' . (int) $r->id) ?>" class="tombol-aksi"><i class="ph ph-users-three"></i><span>Peserta &amp; nomor</span></a>
                        <?php if ( ! empty($r->sertifikat_diminta_at)): ?>
                        <form method="post" action="<?= base_url('Admin_Kemitraan/abaikan_permintaan/' . (int) $r->id) ?>" class="inline" data-konfirmasi="Tutup permintaan sertifikat ini tanpa menetapkan tanggal?" data-konfirmasi-judul="Abaikan permintaan" data-konfirmasi-label="Ya, abaikan">
                            <?= $csrf ?><button type="submit" class="tombol-aksi tombol-aksi-bahaya"><i class="ph ph-x"></i><span>Abaikan</span></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
