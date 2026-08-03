<?php
/**
 * Papan aduan — hanya JUDUL, bidang penanganan, status, dan JAWABAN dinas.
 *
 * Isi aduan (`pesan`), email pelapor, dan lampirannya TIDAK ADA di $rows sama
 * sekali: controllernya tidak pernah meng-SELECT-nya (Umum::papan_aduan). Nama
 * pelapor sudah jadi inisial sebelum sampai ke sini, dan kolom aslinya sudah
 * dibuang dari baris. Kalau suatu saat ada yang perlu ditambahkan di halaman
 * ini, tambahkan ke SELECT-nya dengan sadar — jangan mengembalikan `aduan.*`.
 */
$badge = [
    'Baru'     => ['Menunggu ditinjau', 'var(--portal-text-muted)'],
    'Diproses' => ['Sedang diproses',   'var(--portal-brand)'],
    'Selesai'  => ['Selesai',           'var(--portal-brand)'],
];
?>
<div class="theme-light py-4 sm:py-6 px-1 sm:px-2">
    <div class="mx-auto max-w-3xl text-center" data-aos="fade-down">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[color:var(--portal-btn-bg)] text-2xl text-[color:var(--portal-icon)]">
            <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i>
        </div>
        <p class="mt-5 text-xs font-black uppercase tracking-[0.18em] text-[color:var(--portal-brand)]">Layanan Pengaduan</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-[color:var(--portal-text)]">Papan Aduan</h1>
        <p class="mx-auto mt-2 max-w-lg text-xs leading-relaxed text-[color:var(--portal-text-muted)]">
            Aduan yang masuk beserta jawaban dari bidang yang menangani. Identitas pelapor dan isi aduannya tidak ditampilkan — hanya judul dan tanggapan dinas.
        </p>
        <a href="<?= base_url('umum/aduan') ?>" class="mt-4 inline-flex min-h-11 items-center gap-2 rounded-full px-5 py-2.5 text-sm font-black transition hover:-translate-y-0.5" style="background-color: var(--portal-brand); color: var(--portal-bg)">
            <i class="fa-solid fa-plus"></i> Sampaikan Aduan
        </a>
    </div>

    <div class="mx-auto mt-8 max-w-3xl space-y-3">
        <?php if (empty($rows)): ?>
        <div class="rounded-2xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] p-8 text-center text-sm text-[color:var(--portal-text-muted)] shadow-sm">
            Belum ada aduan yang masuk.
        </div>
        <?php else: foreach ($rows as $r):
            [$status_teks, $status_warna] = $badge[$r->status] ?? [$r->status, 'var(--portal-text-muted)'];
        ?>
        <article class="rounded-2xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] p-4 shadow-sm sm:p-5">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-[color:var(--portal-text-muted)]">
                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full text-[10px] font-black" style="background-color: var(--portal-btn-bg); color: var(--portal-icon)"><i class="fa-solid fa-user"></i></span>
                <span class="font-bold"><?= html_escape($r->inisial) ?></span>
                <span aria-hidden="true">•</span>
                <span><?= html_escape(tgl_id($r->created_at, TRUE)) ?></span>
                <span class="ml-auto font-black" style="color: <?= $status_warna ?>"><?= html_escape($status_teks) ?></span>
            </div>

            <h2 class="mt-2 text-sm font-black leading-snug text-[color:var(--portal-text)]"><?= html_escape($r->judul) ?></h2>

            <p class="mt-1.5 text-[11px] text-[color:var(--portal-text-muted)]">
                <i class="fa-solid fa-arrow-right-long mr-1"></i>
                <?php if ($r->bidang_label): ?>
                    Ditangani <span class="font-bold"><?= html_escape($r->bidang_label) ?></span>
                <?php else: ?>
                    Sedang ditinjau untuk diteruskan ke bidang yang menangani
                <?php endif; ?>
            </p>

            <?php if ( ! empty($r->catatan_admin)): ?>
            <div class="mt-3 rounded-xl border-l-2 p-3 text-xs leading-relaxed" style="border-color: var(--portal-brand); background-color: var(--portal-btn-bg); color: var(--portal-text)">
                <span class="mb-1 block text-[10px] font-black uppercase tracking-wider" style="color: var(--portal-brand)">Jawaban Dinas</span>
                <?= nl2br(html_escape($r->catatan_admin)) ?>
            </div>
            <?php endif; ?>
        </article>
        <?php endforeach; endif; ?>
    </div>

    <?php if ($hal_akhir > 1): ?>
    <div class="mx-auto mt-6 flex max-w-3xl items-center justify-between text-xs">
        <?php if ($hal > 1): ?>
        <a href="<?= base_url('umum/papan_aduan?hal=' . ($hal - 1)) ?>" class="rounded-full border border-[color:var(--portal-border)] px-4 py-2 font-bold text-[color:var(--portal-text)]"><i class="fa-solid fa-chevron-left mr-1"></i> Sebelumnya</a>
        <?php else: ?><span></span><?php endif; ?>

        <span class="text-[color:var(--portal-text-muted)]">Halaman <?= (int) $hal ?> dari <?= (int) $hal_akhir ?> · <?= (int) $total ?> aduan</span>

        <?php if ($hal < $hal_akhir): ?>
        <a href="<?= base_url('umum/papan_aduan?hal=' . ($hal + 1)) ?>" class="rounded-full border border-[color:var(--portal-border)] px-4 py-2 font-bold text-[color:var(--portal-text)]">Berikutnya <i class="fa-solid fa-chevron-right ml-1"></i></a>
        <?php else: ?><span></span><?php endif; ?>
    </div>
    <?php endif; ?>
</div>
