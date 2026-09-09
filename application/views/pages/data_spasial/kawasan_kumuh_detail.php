<?php
/**
 * Detail satu kawasan kumuh + rincian RT/RW di dalamnya.
 * Nol data pribadi: yang tampil wilayah, luas, dan cacah bangunan/penduduk.
 */
$e = function ($v) { return html_escape((string) $v); };
$angka = function ($v) { return is_numeric($v) ? number_format((float) $v, (floor((float) $v) == (float) $v ? 0 : 2), ',', '.') : '-'; };
?>
<section class="w-full pt-24 pb-16 px-4 sm:px-6 lg:px-8 min-h-screen font-outfit">
  <div class="mx-auto max-w-5xl">

    <a href="<?= base_url('kawasan_kumuh') ?>" class="text-xs font-bold underline" style="color:var(--portal-brand)">
      &larr; Kembali ke daftar kawasan
    </a>

    <header class="mt-3 mb-6">
      <h1 class="text-2xl font-black sm:text-3xl" style="color:var(--portal-text)">
        <?= $e($kawasan['nama_kawasan'] ?? 'Kawasan') ?>
      </h1>
      <p class="mt-1 text-sm" style="color:var(--portal-text-muted)">
        <?= $e($kawasan['nama_kab'] ?? '-') ?> &middot; Tahun <?= $e($kawasan['tahun'] ?? '-') ?>
      </p>
    </header>

    <div class="grid gap-4 sm:grid-cols-2">
      <div class="rounded-2xl border p-4" style="border-color:var(--portal-border)">
        <p class="text-[11px] font-bold uppercase tracking-wider" style="color:var(--portal-text-muted)">Skor kumuh awal</p>
        <p class="mt-1 text-3xl font-black" style="color:var(--portal-text)"><?= (int) ($kawasan['skor_kumuh_awal'] ?? 0) ?></p>
      </div>
      <div class="rounded-2xl border p-4" style="border-color:var(--portal-border)">
        <p class="text-[11px] font-bold uppercase tracking-wider" style="color:var(--portal-text-muted)">Skor kumuh akhir</p>
        <p class="mt-1 text-3xl font-black" style="color:var(--portal-text)"><?= (int) ($kawasan['skor_kumuh_akhir'] ?? 0) ?></p>
      </div>
    </div>

    <h2 class="mt-8 mb-3 text-lg font-black" style="color:var(--portal-text)">
      Rincian RT/RW <span class="text-sm font-bold" style="color:var(--portal-text-muted)">(<?= count($rtrw) ?>)</span>
    </h2>

    <?php if ( ! $rtrw): ?>
      <div class="rounded-2xl border p-6" style="border-color:var(--portal-border)">
        <p class="text-sm" style="color:var(--portal-text-muted)">Belum ada rincian RT/RW untuk kawasan ini.</p>
      </div>
    <?php else: ?>
      <div class="overflow-x-auto rounded-2xl border" style="border-color:var(--portal-border)">
        <table class="w-full min-w-[760px] text-left text-sm">
          <thead>
            <tr class="text-[11px] uppercase tracking-wider" style="color:var(--portal-text-muted)">
              <th class="px-4 py-3 font-bold">Wilayah</th>
              <th class="px-4 py-3 font-bold">Kelurahan</th>
              <th class="px-4 py-3 font-bold">Kecamatan</th>
              <th class="px-4 py-3 font-bold text-right">Luas (Ha)</th>
              <th class="px-4 py-3 font-bold text-right">Bangunan</th>
              <th class="px-4 py-3 font-bold text-right">Penduduk</th>
              <th class="px-4 py-3 font-bold text-right">KK</th>
              <th class="px-4 py-3 font-bold text-right">Skor akhir</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rtrw as $r): ?>
              <tr class="border-t" style="border-color:var(--portal-border)">
                <td class="px-4 py-3 font-bold" style="color:var(--portal-text)"><?= $e($r['nama_wilayah'] ?? '-') ?></td>
                <td class="px-4 py-3" style="color:var(--portal-text-muted)"><?= $e($r['nama_kel'] ?? '-') ?></td>
                <td class="px-4 py-3" style="color:var(--portal-text-muted)"><?= $e($r['nama_kec'] ?? '-') ?></td>
                <td class="px-4 py-3 text-right" style="color:var(--portal-text-muted)"><?= $angka($r['luas_verif'] ?? NULL) ?></td>
                <td class="px-4 py-3 text-right" style="color:var(--portal-text-muted)"><?= $angka($r['jml_bangunan'] ?? NULL) ?></td>
                <td class="px-4 py-3 text-right" style="color:var(--portal-text-muted)"><?= $angka($r['jml_penduduk'] ?? NULL) ?></td>
                <td class="px-4 py-3 text-right" style="color:var(--portal-text-muted)"><?= $angka($r['jml_kk'] ?? NULL) ?></td>
                <td class="px-4 py-3 text-right font-bold" style="color:var(--portal-text)"><?= (int) ($r['skor_kumuh_akhir'] ?? 0) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <p class="mt-6 text-[11px]" style="color:var(--portal-text-muted)">
      Sumber: SIKAPER Disperakim Provinsi Jawa Tengah.
    </p>
  </div>
</section>
