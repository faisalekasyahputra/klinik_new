<?php
/**
 * Posisi yang dicari satu bidang, beserta kualifikasinya (permintaan dinas 22 Okt 2026:
 * "kualifikasi mahasiswanya belum muncul"). Dipakai papan slot (magang.php) dan kartu bidang
 * di formulir daftar (daftar.php), supaya keduanya selalu sama.
 *
 * Kualifikasi diambil dari kkn_magang_posisi.keterangan: satu butir per baris (admin mengisi
 * di kotak teks Posisi Magang). Titik koma juga diterima sebagai pemisah untuk isian lama.
 *
 * @var object[] $posisi baris kkn_magang_posisi yang aktif
 */
if (empty($posisi)) { return; }
?>
<div class="grid gap-2">
    <?php foreach ($posisi as $p):
        $kualifikasi = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n|;/', (string) $p->keterangan))));
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
                    <?php foreach ($kualifikasi as $butir): ?>
                        <li><?= html_escape($butir) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
