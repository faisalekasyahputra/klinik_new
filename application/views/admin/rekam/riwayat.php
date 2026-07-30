<?php
/**
 * Rekam Data — Riwayat Pelaporan (D5). Dipakai kedua domain.
 *
 * Baca-saja: daftar periode + statusnya. Bukan rekap angka — gunanya supaya
 * petugas tahu triwulan mana yang sudah dikirim, mana yang dikembalikan untuk
 * diperbaiki, dan apa catatan peninjaunya.
 */
$e = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$nama_tw = [1 => 'TW I', 2 => 'TW II', 3 => 'TW III', 4 => 'TW IV'];


$warna = [
    'draft'           => 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300',
    'terkirim'        => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300',
    'perlu_perbaikan' => 'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-300',
];
?>

<div class="space-y-4">

  <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-brand-card">
    <div class="flex flex-wrap items-center gap-3">
      <span class="rounded-lg bg-gray-100 px-3 py-2 text-sm dark:bg-black/20">
        Kabupaten/Kota <b class="text-gray-900 dark:text-white"><?= $e($scope_label) ?></b>
      </span>
      <form method="get" action="<?= base_url($base_url . '/riwayat') ?>" class="flex flex-wrap items-center gap-2">
        <label class="text-sm text-gray-500 dark:text-brand-muted" for="tahun">Tahun</label>
        <select id="tahun" name="tahun" class="rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm dark:border-white/10">
          <?php for ($t = (int) date('Y') + 1; $t >= (int) date('Y') - 2; $t--): ?>
            <option value="<?= $t ?>" <?= $t === (int) $tahun ? 'selected' : '' ?>><?= $t ?></option>
          <?php endfor; ?>
        </select>
        <button class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-bold dark:border-white/10">Tampilkan</button>
      </form>
      <span class="ml-auto text-xs text-gray-500 dark:text-brand-muted">Baca-saja</span>
    </div>
  </section>

  <?php if ( ! $periode): ?>
    <section class="rounded-2xl border border-gray-200 bg-white p-8 text-center dark:border-white/10 dark:bg-brand-card">
      <p class="font-bold text-gray-900 dark:text-white">Belum ada periode <?= $e($domain) ?> di tahun <?= (int) $tahun ?>.</p>
      <p class="mt-1 text-sm text-gray-500 dark:text-brand-muted">
        Periode dibuat otomatis saat kamu membuka layar Input Capaian.
      </p>
    </section>
  <?php else: ?>
    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-brand-card">
      <div class="overflow-x-auto">
        <table class="w-full min-w-[840px] text-left text-sm">
          <thead class="text-xs uppercase text-gray-500 dark:text-brand-muted">
            <tr>
              <th class="py-2 pr-3">Periode</th>
              <th class="py-2 pr-3">Status</th>
              <th class="py-2 pr-3">Dikirim</th>
              <th class="py-2 pr-3">Ditinjau</th>
              <th class="py-2 pr-3">Catatan peninjau</th>
              <th class="py-2"><span class="sr-only">Aksi</span></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-white/5">
            <?php foreach ($periode as $row):
                $status = $row['status'];
                // "Diterima" bukan status tersendiri di skema: ia laporan
                // `terkirim` yang sudah punya reviewed_at. Ditampilkan apa
                // adanya, tidak dikarang jadi status baru.
                $label_status = $status === 'terkirim' && ! empty($row['reviewed_at'])
                    ? 'Terkirim · sudah ditinjau'
                    : ucfirst(str_replace('_', ' ', $status));
            ?>
              <?php $url_detail = $e(base_url($base_url . '?tahun=' . (int) $tahun . '&triwulan=' . (int) $row['triwulan'])); ?>
              <tr>
                <td class="py-2 pr-3 font-medium text-gray-900 dark:text-white">
                  <?= $e($nama_tw[(int) $row['triwulan']] ?? $row['triwulan']) ?> <?= (int) $tahun ?>
                </td>
                <td class="py-2 pr-3">
                  <span class="rounded-full px-3 py-1 text-xs font-bold <?= $warna[$status] ?? '' ?>">
                    <?= $e($label_status) ?>
                  </span>
                </td>
                <td class="py-2 pr-3 text-gray-500 dark:text-brand-muted"><?= $e($row['submitted_at'] ?: '—') ?></td>
                <td class="py-2 pr-3 text-gray-500 dark:text-brand-muted"><?= $e($row['reviewed_at'] ?: '—') ?></td>
                <td class="py-2 pr-3 text-gray-600 dark:text-brand-muted"><?= $e($row['catatan_admin'] ?: '—') ?></td>
                <td class="py-2 text-right whitespace-nowrap">
                  <a href="<?= $url_detail ?>"
                     class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-bold dark:border-white/10">
                    Lihat capaian
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <p class="mt-4 text-xs text-gray-500 dark:text-brand-muted">
        Daftar ini menampilkan status, bukan angka. <b>Lihat capaian</b> membuka angka
        triwulan itu apa adanya, termasuk yang masih draft. Untuk angka yang sudah
        resmi — hanya laporan terkirim, berikut kumulatifnya — buka
        <a class="text-blue-600 hover:underline dark:text-blue-400" href="<?= base_url($base_url . '/rekap') ?>">Rekap Pelaporan</a>.
      </p>
    </section>
  <?php endif; ?>
</div>
