<?php
/**
 * Rekam Data — Capaian Perumahan: TABEL UNIT RENCANA + TABEL UNIT REALISASI (W5).
 *
 * Bentuk dua tabel mengikuti `new_flow/rekamdata/tabel_design.png`, matriks 12
 * sumber dana × 6 program. Di layar isian bentuk ini tidak dipakai karena tidak
 * punya tempat untuk tombol Tambah/Ubah per baris; di layar baca yang ada hanya
 * angka, jadi ia justru pas.
 *
 * ATURAN YANG TIDAK BOLEH DILANGGAR:
 *
 *   1. Nol `SUM()` di view. Kolom kumulatif datang dari
 *      `Rekam_data_model::kumulatif()` — satu-satunya tempat yang boleh
 *      menjumlahkan antar triwulan. Penjumlahan yang salah pada angka capaian
 *      tidak terlihat salah, dan itu yang membuatnya berbahaya.
 *   2. Nol tabel nol. Sumber tanpa baris ditampilkan `—`, bukan `0`. Nol yang
 *      dikarang tidak bisa dibedakan dari nol yang benar-benar dilaporkan.
 */
$e  = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$rp = static fn($n) => number_format((int) $n, 0, ',', '.');

$nama_tw   = [1 => 'TW I', 2 => 'TW II', 3 => 'TW III', 4 => 'TW IV'];

/* Periode yang tercetak di judul tabel — satu sumber, dipakai ketiganya.
   Dinas hanya meminta TW dicantumkan pada "Tabel Unit Realisasi" (butir C3,
   5 Agt 2026), tapi tabel Rencana di sebelahnya ikut diberi supaya halaman
   tidak memajang satu judul bertriwulan dan satu tanpa — keduanya memang
   menggambarkan triwulan yang sama. */
$periode   = ($nama_tw[(int) $triwulan] ?? $triwulan) . ' ' . (int) $tahun;
$mode_rekap = $mode_rekap ?? FALSE;
$laporan    = $laporan ?? NULL;

$warna_status = [
    'draft'           => 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300',
    'terkirim'        => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300',
    'perlu_perbaikan' => 'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-300',
];
$label_status = ['draft' => 'Draft', 'terkirim' => 'Terkirim', 'perlu_perbaikan' => 'Perlu Perbaikan'];

$kotak  = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-brand-card';
$tombol = 'inline-block rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white transition-colors'
    . ' hover:bg-blue-700 dark:bg-brand-primary dark:text-brand-dark dark:hover:bg-brand-hover';

/**
 * Satu tabel matriks. `$sisi_list` menentukan sisi angka yang ditampilkan:
 * `['rencana','realisasi']` memberi DUA BARIS per sumber dana, `['realisasi']`
 * memberi satu baris seperti semula.
 *
 * SATU TABEL, DUA BARIS PER SUMBER — bukan dua tabel terpisah, dan bukan pula
 * dua angka berpasangan di dalam satu sel (revisi dinas 5 Agt 2026, butir C2:
 * "target dan realisasinya dijejer aja, jangan dipisah").
 *
 * Bentuk berpasangan-dalam-sel dicoba lebih dulu dan DIBUANG setelah diukur:
 * isi sel jadi `999.999.999 / 999.999.999`, dan tabelnya melebar 1100 → 1348px
 * pada viewport 1440 — guliran mendatar naik dari 35px jadi 283px. Dua baris
 * per sumber membuat isi sel kembali satu angka, jadi lebar kolomnya persis
 * seperti semula: terukur 1100px, guliran 35px, sama dengan sebelum perubahan.
 * Yang bertambah tingginya, dan menggulir ke bawah memang hal biasa —
 * menggulir ke samping tidak.
 *
 * Tetap SATU closure meski tabel kumulatif cuma satu sisi. Menyalinnya jadi
 * dua berarti dua tempat yang harus disunting setiap kali kolomnya berubah.
 */
$nama_sisi = ['rencana' => 'Rencana', 'realisasi' => 'Realisasi'];

$tabel = function ($judul, array $sisi_list, array $data) use ($e, $rp, $sumber_label, $program_label, $nama_sisi) {
    $pasangan   = count($sisi_list) > 1;
    $total_unit = array_fill_keys($sisi_list, 0);
    $total_rp   = array_fill_keys($sisi_list, 0);
    ?>
    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-brand-card">
      <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500 dark:text-brand-muted"><?= $e($judul) ?></h3>
      <?php if ($pasangan): ?>
        <p class="mt-1 text-xs text-gray-500 dark:text-brand-muted">Tiap sumber dana punya dua baris: <b class="text-gray-900 dark:text-white">Rencana</b> lalu <b class="text-gray-900 dark:text-white">Realisasi</b>. Dalam tiap sel, angka atas unit dan angka bawah rupiah.</p>
      <?php endif; ?>
      <!-- Tabel lebar bergulir di wadahnya sendiri; <body> tidak ikut bergulir. -->
      <div class="mt-3 overflow-x-auto">
        <table class="w-full min-w-[1100px] text-left text-sm">
          <thead>
            <tr class="text-xs uppercase text-gray-500 dark:text-brand-muted">
              <th class="sticky left-0 bg-white py-2 pr-3 dark:bg-brand-card">Sumber Dana</th>
              <?php if ($pasangan): ?><th class="py-2 pr-2"><span class="sr-only">Sisi angka</span></th><?php endif; ?>
              <?php foreach ($program_label as $plabel): ?>
                <th class="px-2 py-2 text-right"><?= $e($plabel) ?><br><span class="font-normal normal-case">unit / Rp</span></th>
              <?php endforeach; ?>
              <th class="py-2 pl-2 text-right">Subtotal</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-white/5">
            <?php foreach ($sumber_label as $skode => $slabel): ?>
              <?php
              $baris    = $data[$skode] ?? [];
              $sub_unit = array_fill_keys($sisi_list, 0);
              $sub_rp   = array_fill_keys($sisi_list, 0);
              ?>
              <?php foreach (array_values($sisi_list) as $i => $sisi): ?>
                <tr>
                  <?php if ($i === 0): ?>
                    <td rowspan="<?= count($sisi_list) ?>" class="sticky left-0 bg-white py-2 pr-3 align-top font-medium text-gray-900 dark:bg-brand-card dark:text-white"><?= $e($slabel) ?></td>
                  <?php endif; ?>
                  <?php if ($pasangan): ?>
                    <td class="whitespace-nowrap py-2 pr-2 text-xs text-gray-500 dark:text-brand-muted"><?= $e($nama_sisi[$sisi] ?? $sisi) ?></td>
                  <?php endif; ?>
                  <?php foreach ($program_label as $pkode => $plabel): ?>
                    <?php
                    $sel  = $baris[$pkode] ?? NULL;
                    $unit = $sel ? (int) $sel[$sisi . '_unit'] : 0;
                    $duit = $sel ? (int) $sel[$sisi . '_anggaran'] : 0;
                    $sub_unit[$sisi] += $unit; $sub_rp[$sisi] += $duit;
                    ?>
                    <td class="px-2 py-2 text-right <?= $sel ? '' : 'text-gray-300 dark:text-white/20' ?>">
                      <?php if ($sel): ?>
                        <?= $rp($unit) ?><br>
                        <span class="text-xs text-gray-500 dark:text-brand-muted"><?= $rp($duit) ?></span>
                      <?php else: ?>
                        &mdash;
                      <?php endif; ?>
                    </td>
                  <?php endforeach; ?>
                  <td class="py-2 pl-2 text-right font-bold <?= $baris ? 'text-gray-900 dark:text-white' : 'text-gray-300 dark:text-white/20' ?>">
                    <?php if ($baris): ?>
                      <?= $rp($sub_unit[$sisi]) ?><br>
                      <span class="text-xs font-normal text-gray-500 dark:text-brand-muted"><?= $rp($sub_rp[$sisi]) ?></span>
                    <?php else: ?>
                      <?php /* Sumber tanpa satu pun baris: subtotalnya "—", bukan 0.
                                Komentar PHP, BUKAN HTML: blok ini di dalam foreach,
                                jadi versi HTML-nya terkirim dua belas kali per tabel. */ ?>
                      &mdash;
                    <?php endif; ?>
                  </td>
                </tr>
                <?php $total_unit[$sisi] += $sub_unit[$sisi]; $total_rp[$sisi] += $sub_rp[$sisi]; ?>
              <?php endforeach; ?>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <?php foreach (array_values($sisi_list) as $i => $sisi): ?>
            <tr class="font-bold text-gray-900 dark:text-white">
              <?php if ($i === 0): ?>
                <td rowspan="<?= count($sisi_list) ?>" class="sticky left-0 bg-white pt-3 align-top dark:bg-brand-card">Total</td>
              <?php endif; ?>
              <?php if ($pasangan): ?>
                <td class="whitespace-nowrap pt-3 pr-2 text-xs font-normal text-gray-500 dark:text-brand-muted"><?= $e($nama_sisi[$sisi] ?? $sisi) ?></td>
              <?php endif; ?>
              <td class="pt-3" colspan="<?= count($program_label) ?>"></td>
              <td class="pt-3 pl-2 text-right">
                <?= $rp($total_unit[$sisi]) ?> unit<br>
                <span class="text-xs font-normal">Rp <?= $rp($total_rp[$sisi]) ?></span>
              </td>
            </tr>
            <?php endforeach; ?>
          </tfoot>
        </table>
      </div>
    </section>
    <?php
};
?>

<div class="space-y-4">

  <?php /* Tata letak kepala: JUDUL kiri, KENDALI kanan, keterangan di bawah.
            Versi sebelumnya menumpuk identitas wilayah, badge status, label
            periode, dua dropdown, dan tombol Tampilkan dalam SATU baris — enam
            benda dengan enam peran berbeda, dan mata tidak punya titik masuk.
            Tombol Input Capaian pun terdampar di bawah paragraf panjang, jauh
            dari kendali lain, padahal ia tindakan utama halaman ini. */ ?>
  <section class="<?= $kotak ?>">
    <div class="flex flex-wrap items-start justify-between gap-4">

      <div class="min-w-0">
        <h2 class="text-lg font-black text-gray-900 dark:text-white">
          Capaian <?= $e($nama_tw[(int) $triwulan] ?? $triwulan) ?> <?= (int) $tahun ?>
        </h2>
        <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-brand-muted">
          <span><?= $e($scope_label) ?></span>
          <?php if ($laporan): ?>
            <span class="rounded-full px-2.5 py-0.5 text-xs font-bold <?= $warna_status[$laporan['status']] ?? '' ?>">
              <?= $e($label_status[$laporan['status']] ?? $laporan['status']) ?>
            </span>
          <?php endif; ?>
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <form method="get" action="<?= base_url($mode_rekap ? 'Rekam_Perumahan/rekap' : 'Rekam_Perumahan') ?>"
              class="flex items-center gap-2">
          <label class="sr-only" for="triwulan">Triwulan</label>
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
          <button class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-bold text-gray-700 transition-colors hover:bg-gray-50 dark:border-white/10 dark:text-brand-muted dark:hover:bg-white/5">Tampilkan</button>
        </form>

        <?php if ( ! $mode_rekap): ?>
          <a href="<?= base_url('Rekam_Perumahan/input' . ($laporan ? '?laporan=' . (int) $laporan['id'] : '')) ?>"
             class="<?= $tombol ?>">Input Capaian</a>
        <?php endif; ?>
      </div>
    </div>

    <?php /* Keterangan dipecah jadi dua kalimat pendek. Yang paling gampang
              salah dibaca — "per triwulan, bukan kumulatif" — berdiri sendiri
              supaya tidak tenggelam di tengah paragraf. */ ?>
    <p class="mt-3 border-t border-gray-100 pt-3 text-xs leading-relaxed text-gray-500 dark:border-white/5 dark:text-brand-muted">
      Angkanya <b>per triwulan</b>, bukan kumulatif sejak Januari — kumulatif s.d.
      triwulan ini ada di tabel paling bawah.
      <?php if ($mode_rekap): ?>
        Hanya laporan berstatus <b>terkirim</b> yang dihitung.
      <?php else: ?>
        Yang ditampilkan angka wilayahmu <b>apa adanya</b>, termasuk draft; status di
        atas yang menentukan apakah sudah dilaporkan ke provinsi.
      <?php endif; ?>
    </p>
  </section>

  <?php if ( ! $matriks): ?>
    <section class="rounded-2xl border border-gray-200 bg-white p-8 text-center dark:border-white/10 dark:bg-brand-card">
      <p class="font-bold text-gray-900 dark:text-white">
        <?= $mode_rekap ? 'Belum ada laporan terkirim untuk triwulan ini.' : 'Belum ada angka tercatat untuk triwulan ini.' ?>
      </p>
      <p class="mt-1 text-sm text-gray-500 dark:text-brand-muted">
        Bukan berarti capaiannya nol — <?= $mode_rekap ? 'laporannya memang belum dikirim.' : 'triwulannya memang belum diisi.' ?>
      </p>
    </section>
  <?php else: ?>
    <?php /* Satu tabel, dua sisi berpasangan — dulu dua tabel terpisah yang
             memaksa mata bolak-balik untuk membandingkan (butir C2). */ ?>
    <?php $tabel('Tabel Unit Rencana & Realisasi ' . $periode, ['rencana', 'realisasi'], $matriks); ?>
  <?php endif; ?>

  <?php if ($kumulatif): ?>
    <?php $tabel('Kumulatif Realisasi s.d. ' . $periode, ['realisasi'], $kumulatif); ?>
    <p class="text-xs text-gray-500 dark:text-brand-muted">
      Tabel kumulatif dihitung oleh sistem dari laporan <b>terkirim</b> TW I sampai
      triwulan ini. Yang tersimpan di basis data tetap angka per triwulan — kumulatif
      tidak pernah diketik siapa pun, jadi tidak bisa saling bertentangan.
    </p>
  <?php endif; ?>

</div>
