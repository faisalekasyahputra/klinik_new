<?php
/**
 * Daftar kebutuhan magang per bidang.
 *
 * Menggantikan matriks bidang × 12 bulan (revisi dinas 3 Agt 2026). Matriks itu
 * benar tapi menuntut pembacanya menyimpulkan sendiri hal yang cuma satu:
 * bidang ini masih menerima atau tidak. Sekarang jawabannya yang ditampilkan,
 * bulan-bulannya jadi keterangan.
 *
 * Aturan kuotanya TIDAK berubah - `KemitraanPortal::periksa_slot()` tetap
 * menolak per bulan dan per hari saat mendaftar. Halaman ini ringkasan, bukan
 * sumber kebenaran; karena itu keterangan bulan tetap ditampilkan supaya
 * pendaftar tahu periode mana yang masuk akal sebelum ditolak formulir.
 */
$kartu = 'rounded-2xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] p-5 shadow-sm';
?>
<div class="mx-auto max-w-4xl p-2 sm:p-6">
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-[.18em] text-[color:var(--portal-text-muted)]">Kemitraan</span>
            <h1 class="mt-2 text-2xl font-black text-[color:var(--portal-text)] sm:text-4xl">Magang/Kerja Praktik</h1>
            <p class="mt-2 text-sm text-[color:var(--portal-text-muted)]">Kebutuhan mahasiswa magang pada tiap bidang di Disperakim Jawa Tengah.</p>
        </div>
        <div class="flex shrink-0 gap-2">
            <a href="<?= base_url('KemitraanPortal/daftar/magang') ?>" data-tab-link data-tab-key="kemitraan_daftar_magang"
               class="rounded-xl bg-[color:var(--portal-brand)] px-3 py-2 text-xs font-bold text-[#0a1a1f]">Daftar Sekarang</a>
            <a href="<?= base_url('KemitraanPortal') ?>" data-tab-link data-tab-key="kemitraan"
               class="rounded-xl border border-[color:var(--portal-border)] px-3 py-2 text-xs font-bold text-[color:var(--portal-text)]">Kembali</a>
        </div>
    </div>

    <?php if (count($tahun_tersedia) > 1): ?>
        <?php // Muncul hanya kalau memang ada lebih dari satu tahun. Satu tombol
              // tahun yang berdiri sendiri bukan pilihan, cuma hiasan. ?>
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

    <div class="mb-4 rounded-2xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg)] px-5 py-4">
        <h2 class="text-base font-black text-[color:var(--portal-text)]">Kebutuhan Magang <?= (int) $tahun ?></h2>
        <p class="mt-1 text-xs leading-relaxed text-[color:var(--portal-text-muted)]">
            Kebutuhan dihitung dari jumlah mahasiswa yang hadir bersamaan, jadi periode yang tidak bertumpang tindih
            tidak saling menghalangi. Satu bidang masih menerima selama ada satu bulan yang belum terisi penuh.
        </p>
    </div>

    <div class="space-y-3">
        <?php foreach ($slot_magang as $b): ?>
            <?php
            // Tiga keadaan, bukan dua. "Belum dibuka" berbeda dari "terpenuhi":
            // yang satu belum pernah ditawarkan tahun ini, yang lain sudah habis
            // diambil - dan pembaca menyusun rencananya dari beda itu.
            $gaya = [
                'menerima'  => ['bg-emerald-500/10 text-emerald-700', 'Masih menerima'],
                'terpenuhi' => ['bg-amber-500/10 text-amber-700',     'Sudah terpenuhi'],
                'tutup'     => ['bg-rose-500/10 text-rose-700',       'Belum dibuka'],
            ][$b['keadaan']];
            ?>
            <div class="<?= $kartu ?> flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0 flex-1">
                    <h3 class="text-sm font-black text-[color:var(--portal-text)] sm:text-base"><?= html_escape($b['bidang']) ?></h3>
                    <p class="mt-1 text-xs text-[color:var(--portal-text-muted)]">
                        Kebutuhan <strong class="text-[color:var(--portal-text)]"><?= (int) $b['kuota'] ?> mahasiswa</strong> hadir bersamaan
                        <?php if ($b['keadaan'] === 'menerima'): ?>
                            &middot; masih bisa menerima <strong class="text-[color:var(--portal-text)]"><?= (int) $b['sisa'] ?></strong> lagi
                        <?php endif; ?>
                    </p>
                    <?php
                    /* Butir F1 - posisi yang dicari, DI DALAM bidang. Dinas
                       memintanya karena "nama bidang" tidak memberi tahu
                       mahasiswa keahlian apa yang sebenarnya dibutuhkan.

                       Kalau dinas belum mengisi daftarnya, blok ini tidak
                       muncul dan kartunya kembali seperti semula - TIDAK ada
                       daftar karangan sebagai pengisi sementara. */
                    ?>
                    <?php if ( ! empty($b['posisi'])): ?>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <?php foreach ($b['posisi'] as $p): ?>
                                <span class="inline-flex items-center gap-1 rounded-full border border-[color:var(--portal-border)] px-2.5 py-1 text-[11px] text-[color:var(--portal-text)]"
                                      <?= $p->keterangan ? 'title="' . html_escape($p->keterangan) . '"' : '' ?>>
                                    <?= html_escape($p->nama_posisi) ?>
                                    <?php if ((int) $p->kuota > 0): ?>
                                        <strong class="text-[color:var(--portal-text-muted)]"><?= (int) $p->kuota ?></strong>
                                    <?php endif; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($b['bulan_dibuka']): ?>
                        <p class="mt-2 text-xs text-[color:var(--portal-text-muted)]">
                            <span class="font-semibold">Bulan dibuka:</span> <?= html_escape(implode(', ', $b['bulan_dibuka'])) ?>
                        </p>
                    <?php else: ?>
                        <p class="mt-2 text-xs text-[color:var(--portal-text-muted)]">Belum ada bulan yang dibuka untuk tahun ini.</p>
                    <?php endif; ?>
                </div>
                <?php
                /* Butir 19 putaran 2: di samping penanda keadaan, bidang yang
                   masih menerima diberi tombol Daftar.

                   BUTIR 24 IKUT DITERAPKAN DI SINI: tombolnya menuntut login,
                   dan itu DIKATAKAN SEBELUM diklik. Sebelumnya halaman ini
                   publik sementara tombol daftarnya dinding login - persis pola
                   yang membuat orang mengira fiturnya rusak (butir 18). */
                $sudah_masuk = (bool) $this->session->userdata('is_logged');
                ?>
                <div class="flex shrink-0 flex-col items-end gap-2">
                    <span class="rounded-full px-3 py-1.5 text-xs font-bold <?= $gaya[0] ?>"><?= $gaya[1] ?></span>
                    <?php if ($b['keadaan'] === 'menerima'): ?>
                        <a href="<?= base_url('KemitraanPortal/daftar/magang') ?>"
                           class="rounded-xl bg-[color:var(--portal-brand)] px-3 py-1.5 text-xs font-bold text-[#0a1a1f] transition-opacity hover:opacity-90">
                            Daftar
                        </a>
                        <?php if ( ! $sudah_masuk): ?>
                            <span class="text-[10px] text-[color:var(--portal-text-muted)]">perlu masuk dulu</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (empty($slot_magang)): ?>
            <div class="<?= $kartu ?> text-center text-sm text-[color:var(--portal-text-muted)]">
                Belum ada bidang yang membuka magang.
            </div>
        <?php endif; ?>
    </div>
</div>
