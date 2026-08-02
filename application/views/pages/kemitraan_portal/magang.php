<div class="mx-auto max-w-6xl p-2 sm:p-6">
    <div class="mb-6 flex items-start justify-between gap-4"><div><span class="text-xs font-bold uppercase tracking-[.18em] text-[color:var(--portal-text-muted)]">Kemitraan</span><h1 class="mt-2 text-2xl font-black text-[color:var(--portal-text)] sm:text-4xl">Magang/Kerja Praktik</h1><p class="mt-2 text-sm text-[color:var(--portal-text-muted)]">Informasi ketersediaan slot magang pada tiap bidang di Disperakim Jawa Tengah.</p></div><div class="flex shrink-0 gap-2"><a href="<?= base_url('KemitraanPortal/daftar/magang') ?>" data-tab-link data-tab-key="kemitraan_daftar_magang" class="rounded-xl bg-[color:var(--portal-brand)] px-3 py-2 text-xs font-bold text-[#0a1a1f]">Daftar Sekarang</a><a href="<?= base_url('KemitraanPortal') ?>" data-tab-link data-tab-key="kemitraan" class="rounded-xl border border-[color:var(--portal-border)] px-3 py-2 text-xs font-bold text-[color:var(--portal-text)]">Kembali</a></div></div>
    <?php $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember']; ?>

    <?php if (count($tahun_tersedia) > 1): ?>
        <!-- Muncul hanya kalau memang ada lebih dari satu tahun. Satu tombol
             tahun yang berdiri sendiri bukan pilihan, cuma hiasan yang menyita
             perhatian. -->
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <span class="text-xs font-bold uppercase tracking-wider text-[color:var(--portal-text-muted)]">Tahun</span>
            <?php foreach ($tahun_tersedia as $t): ?>
                <a href="<?= base_url('KemitraanPortal/magang/' . (int) $t) ?>" data-tab-link
                   class="rounded-lg px-3 py-1.5 text-xs font-bold <?= (int) $t === (int) $tahun
                        ? 'bg-[color:var(--portal-brand)] text-[#0a1a1f]'
                        : 'border border-[color:var(--portal-border)] text-[color:var(--portal-text)]' ?>"><?= (int) $t ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php
    /**
     * Lebar kolom dikecilkan khusus layar kecil.
     *
     * Sebelumnya kolom bidang dipatok 250px dan tiap bulan 105px di SEMUA
     * ukuran. Di 375px, wadah tabelnya cuma 323px — kolom bidang yang sticky
     * memakan 250, menyisakan 73px, yaitu 0,70 kolom bulan. Halamannya tidak
     * jebol (body tidak meluber, tabelnya menggulir di dalam wadahnya sendiri),
     * jadi "responsif" secara teknis benar — tapi yang terlihat cuma nama bidang
     * dan sepotong Januari. Diukur di browser, bukan dikira.
     */
    $kolom_bidang = 'min-w-[124px] sm:min-w-[250px]';
    $kolom_bulan  = 'min-w-[76px] sm:min-w-[105px]';
    ?>
    <section class="overflow-hidden rounded-3xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] shadow-sm"><div class="border-b border-[color:var(--portal-border)] p-5 sm:p-6"><h2 class="text-lg font-black text-[color:var(--portal-text)]">Slot Magang per Bidang <?= (int) $tahun ?></h2><p class="mt-1 text-xs text-[color:var(--portal-text-muted)]">Kuota dihitung dari jumlah mahasiswa yang hadir BERSAMAAN, bukan dari berapa orang menyentuh bulan itu — jadi periode yang tidak bertumpang tindih tidak saling menghalangi. Angka pada tiap sel adalah kondisi paling ramai di bulan tersebut. Hijau berarti masih ada sisa, kuning berarti sudah penuh, merah berarti bulan itu tidak dibuka.</p></div><div class="overflow-x-auto"><table class="w-full min-w-[920px] sm:min-w-[1250px] border-collapse text-left text-xs" data-table-search data-table-sort data-table-pagination data-table-summary data-table-per-page="10"><thead class="bg-[color:var(--portal-bg)] uppercase tracking-wider text-[color:var(--portal-text-muted)]"><tr><th class="sticky left-0 z-10 <?= $kolom_bidang ?> border-r border-[color:var(--portal-border)] bg-[color:var(--portal-bg)] px-4 py-3">Bidang</th><?php foreach ($bulan as $nama_bulan): ?><th class="<?= $kolom_bulan ?> border-r border-[color:var(--portal-border)] px-3 py-3 text-center"><?= $nama_bulan ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach ($slot_magang as $slot): ?><tr data-table-row class="border-t border-[color:var(--portal-border)]"><td class="sticky left-0 z-[1] border-r border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] px-4 py-4 font-semibold text-[color:var(--portal-text)]" data-table-column><?= html_escape($slot['divisi']) ?><span class="mt-0.5 block text-[11px] font-normal text-[color:var(--portal-text-muted)]">Kuota <?= (int) $slot['kuota'] ?> mahasiswa/bulan</span></td><?php foreach ($bulan as $nama_bulan): ?><?php
    $sel = $slot['sel'][$nama_bulan] ?? ['keadaan' => 'tutup', 'teks' => 'Tidak Tersedia', 'rentang' => ''];
    // Tiga keadaan, bukan dua. "Penuh" berbeda dari "tidak dibuka": yang satu
    // bisa terbuka lagi kalau ada yang membatalkan, yang lain tidak pernah
    // ditawarkan sejak awal — dan pembaca menyusun rencananya dari beda itu.
    $warna = ['buka' => 'bg-emerald-500 text-white', 'penuh' => 'bg-amber-500 text-white', 'tutup' => 'bg-rose-500 text-white'][$sel['keadaan']];
?><td class="border-r border-[color:var(--portal-border)] px-2 py-2 text-center"><span class="inline-flex min-h-[42px] w-full flex-col items-center justify-center rounded-lg px-2 py-2 font-bold leading-tight <?= $warna ?>"><span><?= html_escape($sel['teks']) ?></span><?php if ( ! empty($sel['rentang'])): ?><span class="text-[10px] font-semibold opacity-90">tgl <?= html_escape($sel['rentang']) ?></span><?php endif; ?></span></td><?php endforeach; ?></tr><?php endforeach; ?></tbody><tbody data-table-empty hidden><tr><td colspan="13" class="px-4 py-8 text-center text-[color:var(--portal-text-muted)]">Data slot tidak ditemukan.</td></tr></tbody></table></div><div data-table-summary class="border-t border-[color:var(--portal-border)] px-5 py-4 text-xs text-[color:var(--portal-text-muted)]"></div><div data-table-pagination class="flex flex-wrap justify-end gap-2 px-5 py-4"></div></section>
</div>
