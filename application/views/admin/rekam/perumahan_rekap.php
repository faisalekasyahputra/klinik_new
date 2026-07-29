<?php
/**
 * Rekam Data — Rekap Pelaporan Perumahan (D5).
 *
 * Bentuk matriks 10 sumber dana × 6 program mengikuti spreadsheet yang sudah
 * dikenal dinas. Di layar INPUT bentuk ini tidak dipakai karena tidak punya
 * tempat untuk gerbang Ada/Tidak Ada maupun kolom keterangan; di layar rekap
 * yang ada hanya angka, jadi ia justru pas.
 *
 * ATURAN YANG TIDAK BOLEH DILANGGAR: nol `SUM()` antar bulan. Angkanya sudah
 * kumulatif, menjumlahkannya melipatgandakan capaian.
 */
$e = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$nama_bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
?>

<div class="space-y-4">

  <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-brand-card">
    <div class="flex flex-wrap items-center gap-3">
      <span class="rounded-lg bg-gray-100 px-3 py-2 text-sm dark:bg-black/20">
        Kabupaten/Kota <b class="text-gray-900 dark:text-white"><?= $e($scope_label) ?></b>
      </span>
      <form method="get" action="<?= base_url('Rekam_Perumahan/rekap') ?>" class="flex flex-wrap items-center gap-2">
        <label class="text-sm text-gray-500 dark:text-brand-muted" for="bulan">Periode</label>
        <select id="bulan" name="bulan" class="rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm dark:border-white/10">
          <?php foreach ($nama_bulan as $n => $label): ?>
            <option value="<?= $n ?>" <?= $n === (int) $bulan ? 'selected' : '' ?>><?= $e($label) ?></option>
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

    <!-- Label periode WAJIB eksplisit: angka kumulatif tanpa keterangan
         "sampai dengan bulan apa" tidak bisa dibaca dengan benar. -->
    <p class="mt-4 text-sm font-bold text-gray-900 dark:text-white">
      Angka kumulatif s.d. <?= $e($nama_bulan[(int) $bulan] ?? $bulan) ?> <?= (int) $tahun ?>
    </p>
    <p class="text-xs text-gray-500 dark:text-brand-muted">
      Dari laporan berstatus <b>terkirim</b> pada periode ini saja. Angka bulan-bulan
      sebelumnya <b>tidak dijumlahkan</b> — nilainya memang sudah kumulatif.
    </p>
  </section>

  <?php if ( ! $ada_data): ?>
    <!-- Keadaan kosong yang jujur: TIDAK merender tabel nol. Nol yang dikarang
         tidak bisa dibedakan dari nol yang benar-benar dilaporkan. -->
    <section class="rounded-2xl border border-gray-200 bg-white p-8 text-center dark:border-white/10 dark:bg-brand-card">
      <p class="font-bold text-gray-900 dark:text-white">Belum ada laporan terkirim untuk periode ini.</p>
      <p class="mt-1 text-sm text-gray-500 dark:text-brand-muted">
        Bukan berarti capaiannya nol — laporannya memang belum dikirim.
      </p>
      <a href="<?= base_url('Rekam_Perumahan?tahun=' . (int) $tahun . '&bulan=' . (int) $bulan) ?>"
         class="mt-4 inline-block rounded-xl bg-brand-primary px-4 py-2 text-sm font-bold text-brand-dark">
        Buka Input Capaian periode ini
      </a>
    </section>
  <?php else: ?>
    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-brand-card">
      <!-- Tabel lebar bergulir di wadahnya sendiri; <body> tidak ikut bergulir. -->
      <div class="overflow-x-auto">
        <table class="w-full min-w-[1100px] text-left text-sm">
          <thead>
            <tr class="text-xs uppercase text-gray-500 dark:text-brand-muted">
              <th class="sticky left-0 bg-white py-2 pr-3 dark:bg-brand-card">Sumber Dana</th>
              <?php foreach ($program_label as $plabel): ?>
                <th class="py-2 px-2 text-right"><?= $e($plabel) ?><br><span class="font-normal normal-case">unit / Rp</span></th>
              <?php endforeach; ?>
              <th class="py-2 pl-2 text-right">Subtotal</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-white/5">
            <?php
            $total_unit = 0; $total_rp = 0;
            foreach ($sumber_label as $skode => $slabel):
                $baris_sumber = $matriks[$skode] ?? [];
                $sub_unit = 0; $sub_rp = 0;
            ?>
              <tr>
                <td class="sticky left-0 bg-white py-2 pr-3 font-medium text-gray-900 dark:bg-brand-card dark:text-white"><?= $e($slabel) ?></td>
                <?php foreach ($program_label as $pkode => $plabel):
                    $sel = $baris_sumber[$pkode] ?? NULL;
                    $unit = $sel ? (int) $sel['unit'] : 0;
                    $rp   = $sel ? (int) $sel['anggaran'] : 0;
                    $sub_unit += $unit; $sub_rp += $rp;
                ?>
                  <td class="py-2 px-2 text-right <?= $sel ? '' : 'text-gray-300 dark:text-white/20' ?>">
                    <?php if ($sel): ?>
                      <?= number_format($unit, 0, ',', '.') ?><br>
                      <span class="text-xs text-gray-500 dark:text-brand-muted"><?= number_format($rp, 0, ',', '.') ?></span>
                    <?php else: ?>
                      —
                    <?php endif; ?>
                  </td>
                <?php endforeach; ?>
                <td class="py-2 pl-2 text-right font-bold <?= $baris_sumber ? 'text-gray-900 dark:text-white' : 'text-gray-300 dark:text-white/20' ?>">
                  <?php if ($baris_sumber): ?>
                    <?= number_format($sub_unit, 0, ',', '.') ?><br>
                    <span class="text-xs font-normal text-gray-500 dark:text-brand-muted"><?= number_format($sub_rp, 0, ',', '.') ?></span>
                  <?php else: ?>
                    <!-- Sumber tanpa satu pun baris: subtotalnya "—", bukan 0.
                         Selnya sudah "—"; menaruh 0 di subtotal membuat sumber
                         yang belum dijawab terbaca sebagai "dilaporkan nol". -->
                    —
                  <?php endif; ?>
                </td>
              </tr>
              <?php $total_unit += $sub_unit; $total_rp += $sub_rp; ?>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr class="font-bold text-gray-900 dark:text-white">
              <td class="sticky left-0 bg-white pt-3 dark:bg-brand-card">Total</td>
              <td class="pt-3" colspan="<?= count($program_label) ?>"></td>
              <td class="pt-3 pl-2 text-right">
                <?= number_format($total_unit, 0, ',', '.') ?> unit<br>
                <span class="text-xs font-normal">Rp <?= number_format($total_rp, 0, ',', '.') ?></span>
              </td>
            </tr>
          </tfoot>
        </table>
      </div>

      <p class="mt-4 text-xs text-gray-500 dark:text-brand-muted">
        Tanda <b>—</b> berarti sumber dana itu dinyatakan <b>Tidak Ada</b> atau belum diisi,
        bukan angka nol. Rekap ini <b>tidak digabungkan</b> dengan rekap Kawasan: daftar
        sumber dananya memang berbeda (Perumahan 10, Kawasan 7) dan belum ada pemetaan
        resmi dari dinas, jadi menjumlahkan keduanya menghasilkan angka yang tidak bisa
        dilacak.
      </p>
    </section>
  <?php endif; ?>
</div>
