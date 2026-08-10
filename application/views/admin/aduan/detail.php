<?php
/**
 * Detail satu aduan - butir 16 putaran 2. READ-ONLY.
 *
 * Isi aduan ditampilkan UTUH di sini, dan itu justru alasan layar ini ada:
 * di daftar, `pesan` terpotong, sehingga admin menriase dari judul saja.
 *
 * Lampiran TIDAK ditautkan langsung ke berkasnya - tetap lewat
 * `Admin_Aduan/lihat_lampiran` yang sudah ada, karena itu satu-satunya jalur
 * yang memeriksa kewenangan sebelum menyajikan berkas privat.
 */
$e = static fn($v) => html_escape((string) $v);
$warna_status = [
    'Baru'     => 'background:rgba(59,130,246,.12);color:#1d4ed8',
    'Diproses' => 'background:rgba(245,158,11,.14);color:#92400e',
    'Selesai'  => 'background:rgba(16,185,129,.12);color:#047857',
];
?>
<div class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="<?= base_url($back_url) ?>" class="text-xs font-bold" style="color:var(--portal-text-muted,#6b7280)">&larr; Kembali ke daftar aduan</a>
            <h1 class="mt-1 text-xl font-black"><?= $e($aduan->judul) ?></h1>
            <p class="mt-1 text-xs" style="color:var(--portal-text-muted,#6b7280)">
                Diterima <?= $e(date('d M Y, H:i', strtotime($aduan->created_at))) ?>
            </p>
        </div>
        <span class="rounded-full px-3 py-1.5 text-xs font-bold" style="<?= $warna_status[$aduan->status] ?? '' ?>">
            <?= $e($aduan->status) ?>
        </span>
    </div>

    <dl class="grid gap-3 rounded-2xl border p-4 text-sm sm:grid-cols-2" style="border-color:var(--portal-border,#e5e7eb)">
        <div>
            <dt class="text-xs" style="color:var(--portal-text-muted,#6b7280)">Pengirim</dt>
            <dd class="mt-0.5 font-bold"><?= $e($aduan->nama ?: 'Tidak dicantumkan') ?></dd>
        </div>
        <div>
            <dt class="text-xs" style="color:var(--portal-text-muted,#6b7280)">Email</dt>
            <dd class="mt-0.5 font-bold"><?= $e($aduan->email ?: 'Tidak dicantumkan') ?></dd>
        </div>
        <div>
            <dt class="text-xs" style="color:var(--portal-text-muted,#6b7280)">Bidang tujuan</dt>
            <dd class="mt-0.5 font-bold">
                <?= $aduan->bidang === NULL
                    ? '<span style="color:#92400e">Belum ditriase</span>'
                    : $e($aduan->nama_bidang ?: $aduan->bidang) ?>
            </dd>
        </div>
        <div>
            <dt class="text-xs" style="color:var(--portal-text-muted,#6b7280)">Ditinjau oleh</dt>
            <dd class="mt-0.5 font-bold">
                <?= $aduan->reviewed_by
                    ? $e($aduan->nama_peninjau ?: 'Petugas') . ' &middot; ' . $e(date('d M Y', strtotime($aduan->reviewed_at)))
                    : 'Belum ditinjau' ?>
            </dd>
        </div>
    </dl>

    <div class="rounded-2xl border p-4" style="border-color:var(--portal-border,#e5e7eb)">
        <p class="text-xs font-bold uppercase tracking-wider" style="color:var(--portal-text-muted,#6b7280)">Isi aduan</p>
        <?php // Sengaja `nl2br` atas teks yang SUDAH di-escape: paragraf pengirim
              // tetap terbaca sebagaimana ia menulisnya, tanpa satu pun tag ikut hidup. ?>
        <p class="mt-2 whitespace-pre-line text-sm leading-relaxed"><?= nl2br($e($aduan->pesan)) ?></p>
    </div>

    <?php if ( ! empty($aduan->lampiran)): ?>
        <div class="rounded-2xl border p-4" style="border-color:var(--portal-border,#e5e7eb)">
            <p class="text-xs font-bold uppercase tracking-wider" style="color:var(--portal-text-muted,#6b7280)">Lampiran</p>
            <a href="<?= base_url('Admin_Aduan/lihat_lampiran/' . (int) $aduan->id) ?>"
               class="mt-2 inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-bold"
               style="border-color:var(--portal-border,#e5e7eb)">
                <i class="ph ph-paperclip"></i> Buka lampiran
            </a>
        </div>
    <?php endif; ?>

    <?php if ( ! empty($aduan->catatan_admin)): ?>
        <div class="rounded-2xl border p-4" style="background:rgba(16,185,129,.06);border-color:rgba(16,185,129,.3)">
            <p class="text-xs font-bold uppercase tracking-wider" style="color:#047857">Jawaban / catatan petugas</p>
            <p class="mt-2 whitespace-pre-line text-sm leading-relaxed"><?= nl2br($e($aduan->catatan_admin)) ?></p>
        </div>
    <?php endif; ?>
</div>
