<?php
/**
 * Rekam Data - Daftar laporan untuk peninjau provinsi (D6).
 *
 * Hanya laporan yang SUDAH dikirim. Draft milik kabupaten tidak pernah muncul
 * di sini: yang belum dikirim bukan urusan peninjau.
 */
$e = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$nama_tw = [1 => 'TW I', 2 => 'TW II', 3 => 'TW III', 4 => 'TW IV']; //

?>

<div class="space-y-4">

  <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-brand-card">
    <div class="flex flex-wrap items-center gap-3">
      <span class="rounded-lg bg-gray-100 px-3 py-2 text-sm dark:bg-black/20">
        Bidang <b class="text-gray-900 dark:text-white"><?= $e(ucfirst($domain)) ?></b>
        · seluruh kabupaten/kota
      </span>
      <form method="get" action="<?= base_url('Rekam_Tinjauan') ?>" class="flex flex-wrap items-center gap-2">
        <select name="triwulan" aria-label="Triwulan" class="rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm dark:border-white/10">
          <option value="">Semua triwulan</option>
          <?php foreach ($nama_tw as $n => $label): ?>
            <option value="<?= $n ?>" <?= $triwulan === $n ? 'selected' : '' ?>><?= $e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="tahun" aria-label="Tahun" class="rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm dark:border-white/10">
          <?php for ($t = (int) date('Y') + 1; $t >= (int) date('Y') - 2; $t--): ?>
            <option value="<?= $t ?>" <?= $t === (int) $tahun ? 'selected' : '' ?>><?= $t ?></option>
          <?php endfor; ?>
        </select>
        <button class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-bold dark:border-white/10">Tampilkan</button>
      </form>
    </div>
    <p class="mt-3 text-xs text-gray-500 dark:text-brand-muted">
      Laporan domain <b><?= $e($domain) ?></b> saja. Domain sebelah dikelola Bidang lain
      dan tidak dapat dibuka dari sini.
    </p>
  </section>

  <?php if ( ! $laporan): ?>
    <section class="rounded-2xl border border-gray-200 bg-white p-8 text-center dark:border-white/10 dark:bg-brand-card">
      <p class="font-bold text-gray-900 dark:text-white">Belum ada laporan yang dikirim untuk periode ini.</p>
      <p class="mt-1 text-sm text-gray-500 dark:text-brand-muted">
        Laporan muncul di sini setelah kabupaten/kota menekan Kirim.
      </p>
    </section>
  <?php else: ?>
    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-brand-card">
      <div class="overflow-x-auto">
        <table class="w-full min-w-[760px] text-left text-sm">
          <thead class="text-xs uppercase text-gray-500 dark:text-brand-muted">
            <tr>
              <th class="py-2 pr-3">Kabupaten/Kota</th>
              <th class="py-2 pr-3">Periode</th>
              <th class="py-2 pr-3">Status</th>
              <th class="py-2 pr-3">Dikirim</th>
              <th class="py-2 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-white/5">
            <?php foreach ($laporan as $row):
                $sudah_ditinjau = $row['status'] === 'terkirim' && ! empty($row['reviewed_at']);
            ?>
              <tr>
                <td class="py-2 pr-3 font-medium text-gray-900 dark:text-white"><?= $e($row['kabupaten'] ?: '-') ?></td>
                <td class="py-2 pr-3"><?= $e($nama_tw[(int) $row['triwulan']] ?? $row['triwulan']) ?> <?= (int) $tahun ?></td>
                <td class="py-2 pr-3">
                  <?php if ($row['status'] === 'perlu_perbaikan'): ?>
                    <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-800 dark:bg-red-500/10 dark:text-red-300">Perlu perbaikan</span>
                  <?php elseif ($sudah_ditinjau): ?>
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300">Diterima</span>
                  <?php else: ?>
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">Menunggu ditinjau</span>
                  <?php endif; ?>
                </td>
                <td class="py-2 pr-3 text-gray-500 dark:text-brand-muted"><?= $e($row['submitted_at'] ?: '-') ?></td>
                <td class="py-2 text-right">
                  <a href="<?= base_url('Rekam_Tinjauan/detail/' . (int) $row['id']) ?>"
                     class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-bold dark:border-white/10">Periksa</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </section>
  <?php endif; ?>
</div>
