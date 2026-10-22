<?php
/**
 * Posisi yang dicari satu bidang, beserta kualifikasinya (permintaan dinas 22 Okt 2026:
 * "kualifikasi mahasiswanya belum muncul"). Dipakai papan slot (magang.php) dan kartu bidang
 * di formulir daftar (daftar.php), supaya keduanya selalu sama.
 *
 * Kualifikasi diambil dari kkn_magang_posisi.keterangan yang diisi admin di Posisi Magang:
 * satu butir per baris; titik koma juga diterima sebagai pemisah untuk isian lama. Tampil
 * paling banyak 3 butir; sisanya lewat tombol Selengkapnya yang membuka <dialog>.
 *
 * Di formulir daftar potongan ini berada di dalam <label> kartu bidang, jadi klik pada tombol
 * dan isi dialog dicegah (preventDefault) supaya tidak ikut memilih bidang itu.
 *
 * @var object[] $posisi baris kkn_magang_posisi yang aktif
 */
if (empty($posisi)) { return; }
$batas = 3;
?>
<div class="grid gap-2">
    <?php foreach ($posisi as $p):
        $kualifikasi = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|;/', (string) $p->keterangan))));
        $id_dialog = 'kualifikasi-' . (int) $p->id;
    ?>
        <div class="rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] px-3 py-2 text-left">
            <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5">
                <span class="text-xs font-bold text-[color:var(--portal-text)]"><i class="fa-solid fa-user-graduate mr-1 text-[color:var(--portal-icon)]" aria-hidden="true"></i><?= html_escape($p->nama_posisi) ?></span>
                <?php if ((int) $p->kuota > 0): ?>
                    <span class="text-[10px] font-semibold text-[color:var(--portal-text-muted)]">Dibutuhkan <?= (int) $p->kuota ?> orang</span>
                <?php endif; ?>
            </div>
            <?php if ($kualifikasi): ?>
                <ul class="mt-1 list-disc space-y-0.5 pl-5 text-[11px] leading-snug text-[color:var(--portal-text-muted)]" aria-label="Kualifikasi <?= html_escape($p->nama_posisi) ?>">
                    <?php foreach (array_slice($kualifikasi, 0, $batas) as $butir): ?>
                        <li><?= html_escape($butir) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php if (count($kualifikasi) > $batas): ?>
                    <button type="button" class="mt-1 text-[11px] font-bold text-[color:var(--portal-brand)] hover:underline"
                            onclick="event.preventDefault(); document.getElementById('<?= $id_dialog ?>').showModal()">
                        Selengkapnya (<?= count($kualifikasi) ?> kualifikasi) <i class="fa-solid fa-chevron-right text-[9px]" aria-hidden="true"></i>
                    </button>
                    <dialog id="<?= $id_dialog ?>" aria-labelledby="<?= $id_dialog ?>-judul"
                            class="w-[min(92vw,28rem)] rounded-2xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] p-0 text-[color:var(--portal-text)] backdrop:bg-black/40"
                            onclick="event.preventDefault(); if (event.target === this) this.close()">
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-[color:var(--portal-text-muted)]">Kualifikasi</p>
                                    <h3 id="<?= $id_dialog ?>-judul" class="mt-0.5 text-base font-black"><?= html_escape($p->nama_posisi) ?></h3>
                                    <?php if ((int) $p->kuota > 0): ?>
                                        <p class="text-xs text-[color:var(--portal-text-muted)]">Dibutuhkan <?= (int) $p->kuota ?> orang</p>
                                    <?php endif; ?>
                                </div>
                                <button type="button" aria-label="Tutup" class="rounded-lg border border-[color:var(--portal-border)] px-2 py-1 text-xs"
                                        onclick="document.getElementById('<?= $id_dialog ?>').close()"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
                            </div>
                            <ul class="mt-3 list-disc space-y-1 pl-5 text-sm leading-relaxed text-[color:var(--portal-text-muted)]">
                                <?php foreach ($kualifikasi as $butir): ?>
                                    <li><?= html_escape($butir) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </dialog>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
