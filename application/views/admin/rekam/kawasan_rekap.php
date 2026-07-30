<?php
/**
 * Rekam Data — Rekap Pelaporan Kawasan (D5).
 *
 * Satu periode saja, tanpa `SUM()` antar triwulan. Total anggaran & padat karya
 * di sini adalah nilai TURUNAN dari daftar intervensi, sama seperti di layar
 * input — tidak ada kolomnya di DB.
 */
$e = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$nama_tw = [1 => 'TW I', 2 => 'TW II', 3 => 'TW III', 4 => 'TW IV']; //

?>

<div class="space-y-4">

  <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-brand-card">
    <div class="flex flex-wrap items-center gap-3">
      <span class="rounded-lg bg-gray-100 px-3 py-2 text-sm dark:bg-black/20">
        Kabupaten/Kota <b class="text-gray-900 dark:text-white"><?= $e($scope_label) ?></b>
      </span>
      <form method="get" action="<?= base_url('Rekam_Kawasan/rekap') ?>" class="flex flex-wrap items-center gap-2">
        <label class="text-sm text-gray-500 dark:text-brand-muted" for="triwulan">Periode</label>
        <select id="triwulan" name="triwulan" class="rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm dark:border-white/10">
          <?php foreach ($nama_tw as $n => $label): ?>
            <option value="<?= $n ?>" <?= $n === (int) $triwulan ? 'selected' : '' ?>><?= $e($label) ?></option>
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

    <?php /* Judul ini dulu berbunyi "Angka kumulatif s.d. TW X" — dibantah oleh
             keterangannya sendiri dua baris di bawah ("triwulan sebelumnya tidak
             dijumlahkan"), dan dibantah oleh kodenya: Rekam_Kawasan::rekap()
             memanggil rekap() untuk SATU triwulan lalu membaca intervensi dari
             satu laporan_id. Tidak ada kumulatif di jalur ini. Sisa dari masa
             angkanya masih "s.d. bulan ini". */ ?>
    <p class="mt-4 text-sm font-bold text-gray-900 dark:text-white">
      Capaian <?= $e($nama_tw[(int) $triwulan] ?? $triwulan) ?> <?= (int) $tahun ?>
    </p>
    <p class="text-xs text-gray-500 dark:text-brand-muted">
      Dari laporan berstatus <b>terkirim</b> pada periode ini saja; triwulan sebelumnya
      <b>tidak dijumlahkan</b>.
    </p>
  </section>

  <?php if ( ! $ringkasan): ?>
    <section class="rounded-2xl border border-gray-200 bg-white p-8 text-center dark:border-white/10 dark:bg-brand-card">
      <p class="font-bold text-gray-900 dark:text-white">Belum ada laporan terkirim untuk periode ini.</p>
      <p class="mt-1 text-sm text-gray-500 dark:text-brand-muted">
        Bukan berarti capaiannya nol — laporannya memang belum dikirim.
      </p>
      <a href="<?= base_url('Rekam_Kawasan?tahun=' . (int) $tahun . '&triwulan=' . (int) $triwulan) ?>"
         class="mt-4 inline-block rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-blue-700 dark:bg-brand-primary dark:text-brand-dark dark:hover:bg-brand-hover">
        Buka Input Capaian periode ini
      </a>
    </section>
  <?php else: ?>

    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
      <?php
      $kartu = [
          ['Penanganan kawasan kumuh', ((int) $ringkasan['ada_penanganan'] === 1 ? 'Ada' : 'Tidak Ada')],
          ['Total luas penanganan', number_format((float) $ringkasan['total_luas_ha'], 2, ',', '.') . ' Ha'],
          ['Jumlah intervensi', number_format((int) $ringkasan['jumlah_intervensi'], 0, ',', '.') . ' kegiatan'],
          ['Total anggaran', 'Rp ' . number_format((int) $ringkasan['total_anggaran'], 0, ',', '.')],
      ];
      foreach ($kartu as [$label, $nilai]): ?>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-brand-card">
          <p class="text-xs text-gray-500 dark:text-brand-muted"><?= $e($label) ?></p>
          <p class="mt-1 font-black text-gray-900 dark:text-white"><?= $e($nilai) ?></p>
        </div>
      <?php endforeach; ?>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-brand-card">
      <div class="flex flex-wrap items-center gap-3">
        <h2 class="font-bold text-gray-900 dark:text-white">Rincian intervensi</h2>
        <span class="rounded-full bg-purple-100 px-3 py-1 text-xs font-bold text-purple-800 dark:bg-purple-500/10 dark:text-purple-300">
          total dihitung dari rincian ini
        </span>
      </div>

      <div class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[820px] text-left text-sm">
          <thead class="text-xs uppercase text-gray-500 dark:text-brand-muted">
            <tr>
              <th class="py-2 pr-3">#</th>
              <th class="py-2 pr-3">Indikator</th>
              <th class="py-2 pr-3">Kegiatan &amp; lokasi</th>
              <th class="py-2 pr-3">Sumber</th>
              <th class="py-2 pr-3 text-right">Volume</th>
              <th class="py-2 pr-3 text-right">Anggaran</th>
              <th class="py-2 text-right">Padat karya</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-white/5">
            <?php foreach ($intervensi as $row):
                $ind = $indikator_label[$row['indikator']] ?? ['label' => $row['indikator'], 'satuan' => ''];
            ?>
              <tr>
                <td class="py-2 pr-3 text-gray-500 dark:text-brand-muted"><?= (int) $row['urutan'] ?></td>
                <td class="py-2 pr-3 font-medium text-gray-900 dark:text-white"><?= $e($ind['label']) ?></td>
                <td class="py-2 pr-3">
                  <?= $e($row['nama_kegiatan']) ?>
                  <span class="block text-xs text-gray-500 dark:text-brand-muted"><?= $e($row['lokasi_teks']) ?></span>
                </td>
                <td class="py-2 pr-3"><?= $e($sumber_label[$row['sumber_anggaran']] ?? $row['sumber_anggaran']) ?></td>
                <td class="py-2 pr-3 text-right"><?= $e(number_format((float) $row['volume'], 2, ',', '.')) ?> <?= $e($ind['satuan']) ?></td>
                <td class="py-2 pr-3 text-right"><?= number_format((int) $row['nilai_anggaran'], 0, ',', '.') ?></td>
                <td class="py-2 text-right"><?= number_format((int) $row['nilai_padat_karya'], 0, ',', '.') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <p class="mt-4 text-xs text-gray-500 dark:text-brand-muted">
        Rekap ini <b>tidak digabungkan</b> dengan rekap Perumahan. Daftar sumber dananya
        berbeda (Kawasan 7, Perumahan 10) dan belum ada pemetaan resmi dari dinas —
        menjumlahkan keduanya menghasilkan angka yang tidak bisa dilacak kembali.
      </p>
    </section>
  <?php endif; ?>
</div>
