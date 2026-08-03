<?php
/**
 * Rekam Data — Detail laporan + layar keputusan peninjau provinsi (D6).
 *
 * Satu view melayani dua domain; bentuk datanya memang berbeda, jadi
 * rinciannya bercabang di satu tempat saja, bukan dua berkas yang harus
 * dijaga tetap sinkron.
 */
$e = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$csrf_name = $this->security->get_csrf_token_name();
$csrf_hash = $this->security->get_csrf_hash();

$laporan_id = (int) $laporan['id'];
$nama_tw = [1 => 'TW I', 2 => 'TW II', 3 => 'TW III', 4 => 'TW IV']; //

$sudah_ditinjau = $laporan['status'] === 'terkirim' && ! empty($laporan['reviewed_at']);

/**
 * DUA syarat, dan keduanya harus benar.
 *
 * `$bisa_diputus` soal KEADAAN laporan; `$boleh_putuskan` soal KEWENANGAN
 * pembacanya. View ini kini melayani dua layar: `Rekam_Tinjauan` (admin bidang,
 * memutuskan) dan `Admin_Rekam_Data` (superadmin, memantau — read-only).
 *
 * Default-nya FALSE, bukan TRUE. Kalau kelak ada layar ketiga yang lupa
 * mengirim nilai ini, yang terjadi adalah tombolnya hilang — bukan tombol
 * keputusan yang muncul di layar yang tidak berwenang. Salah arah default di
 * sini berarti dua jalur tulis ke keputusan yang sama, persis masalah yang
 * dihindari Admin_Aduan.
 */
$boleh_putuskan = ! empty($boleh_putuskan);
$bisa_diputus   = $boleh_putuskan && $laporan['status'] === 'terkirim' && ! $sudah_ditinjau;

// Tautan kembali mengikuti layar yang memanggil, bukan dipatok ke Rekam_Tinjauan.
$url_kembali  = $url_kembali ?? 'Rekam_Tinjauan';
?>

<div class="space-y-4">

  <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-brand-card">
    <div class="flex flex-wrap items-center gap-3">
      <a href="<?= base_url($url_kembali) ?>" class="text-sm text-blue-600 hover:underline dark:text-blue-400">&larr; Daftar</a>
      <span class="rounded-lg bg-gray-100 px-3 py-2 text-sm dark:bg-black/20">
        <b class="text-gray-900 dark:text-white"><?= $e($kabupaten) ?></b>
        · <?= $e($nama_tw[(int) $laporan['triwulan']] ?? $laporan['triwulan']) ?> <?= (int) $laporan['tahun'] ?>
      </span>
      <span class="ml-auto rounded-full px-3 py-1 text-xs font-bold
        <?= $laporan['status'] === 'perlu_perbaikan'
              ? 'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-300'
              : ($sudah_ditinjau
                  ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300'
                  : 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300') ?>">
        <?= $laporan['status'] === 'perlu_perbaikan' ? 'Perlu perbaikan'
              : ($sudah_ditinjau ? 'Diterima' : 'Menunggu ditinjau') ?>
      </span>
    </div>
    <?php /* Kalimat ini dulu berbunyi "kumulatif sampai dengan TW X, bukan
             capaian triwulan itu saja" — persis KEBALIKAN kenyataan sejak W1,
             sementara layar kabupaten (perumahan_capaian.php) sudah menyatakan
             yang benar. Halaman inilah tempat provinsi memutuskan terima atau
             minta perbaikan: peninjau yang membaca 200 unit sebagai "total
             sepanjang tahun" padahal itu capaian satu triwulan akan menilai
             wilayahnya jauh di bawah kenyataan, dan angkanya tetap terlihat
             wajar. Sumbernya laporan_bidang() -> isi_laporan(), yang membaca
             baris SATU laporan; tidak ada penjumlahan antar triwulan di jalur
             ini sama sekali. */ ?>
    <p class="mt-3 text-xs text-gray-500 dark:text-brand-muted">
      Angkanya <b>capaian <?= $e($nama_tw[(int) $laporan['triwulan']] ?? '') ?> saja</b>,
      bukan kumulatif sejak Januari. Untuk capaian setahun, jumlahkan laporan
      triwulan lain wilayah ini.
    </p>
    <?php /* Sengaja TIDAK menunjuk "buka Rekap Pelaporan": Rekam_Perumahan dan
             Rekam_Kawasan meng-extend Admin_Kabkota_Controller, jadi peninjau
             provinsi tidak bisa membukanya. Mengganti kalimat yang salah dengan
             petunjuk buntu bukan perbaikan. */ ?>
    <?php if ( ! empty($laporan['catatan_admin'])): ?>
      <p class="mt-3 rounded-r-xl border-l-4 border-red-500 bg-red-50 p-3 text-sm text-red-900 dark:bg-red-500/10 dark:text-red-200">
        <b>Catatan perbaikan terakhir:</b> <?= $e($laporan['catatan_admin']) ?>
      </p>
    <?php endif; ?>
  </section>

  <?php
  // Cabang ditentukan DOMAIN DATANYA, bukan bidang si peninjau. Kalau memakai
  // bidang, laporan yang (karena bug) lolos ke bidang salah akan dirender
  // dengan cabang yang keliru: kunci arraynya tidak ada, layar tampil kosong,
  // dan kesalahannya jadi tak terlihat. Data yang menentukan bentuknya sendiri.
  if ($laporan['domain'] === 'perumahan'): ?>
    <?php
    $per_sumber = [];
    foreach ($isi['baris'] as $row) {
        $per_sumber[$row['sumber_dana']][$row['program']] = $row;
    }
    // Gerbang kini per PROGRAM, bukan per sumber dana (W1). Peninjau perlu tahu
    // program mana yang DIPILIH kabupaten untuk dilaporkan: program yang tidak
    // dipilih memang tidak dilaporkan, dan itu berbeda dari dilaporkan nol.
    $dipilih = $isi['program'] ?? [];
    ?>
    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-brand-card">
      <h2 class="font-bold text-gray-900 dark:text-white">Capaian per sumber dana</h2>
      <div class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[1000px] text-left text-sm">
          <thead class="text-xs uppercase text-gray-500 dark:text-brand-muted">
            <tr>
              <th class="py-2 pr-3">Sumber Dana</th>
              <?php foreach ($label['program'] as $pkode => $plabel): ?>
                <th class="py-2 px-2 text-right">
                  <?= $e($plabel) ?>
                  <?php if ( ! array_key_exists($pkode, $dipilih)): ?>
                    <span class="block text-[10px] font-normal normal-case text-gray-400">tidak dilaporkan</span>
                  <?php else: ?>
                    <span class="block text-[10px] font-normal normal-case">rencana &rarr; realisasi</span>
                  <?php endif; ?>
                </th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100 dark:divide-white/5">
            <?php foreach ($label['sumber'] as $skode => $slabel): ?>
              <tr>
                <td class="py-2 pr-3 font-medium text-gray-900 dark:text-white"><?= $e($slabel) ?></td>
                <?php foreach ($label['program'] as $pkode => $plabel):
                    $sel = $per_sumber[$skode][$pkode] ?? NULL;
                ?>
                  <td class="py-2 px-2 text-right <?= $sel ? '' : 'text-gray-300 dark:text-white/20' ?>">
                    <?php if ($sel): ?>
                      <span class="block text-xs text-gray-500 dark:text-brand-muted">
                        <?= number_format((int) $sel['rencana_unit'], 0, ',', '.') ?> unit /
                        <?= number_format((int) $sel['rencana_anggaran'], 0, ',', '.') ?>
                      </span>
                      <b><?= number_format((int) $sel['realisasi_unit'], 0, ',', '.') ?> unit</b>
                      <span class="block text-xs text-gray-500 dark:text-brand-muted">
                        <?= number_format((int) $sel['realisasi_anggaran'], 0, ',', '.') ?>
                      </span>
                      <?php if ( ! empty($sel['keterangan'])): ?>
                        <span class="block text-xs italic text-gray-400"><?= $e($sel['keterangan']) ?></span>
                      <?php endif; ?>
                    <?php else: ?>&mdash;<?php endif; ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <p class="mt-4 text-sm">
        <b class="text-gray-900 dark:text-white">Lampiran BNBA:</b>
        <?php if ( ! empty($isi['bnba'])): ?>
          <?= $e($isi['bnba']['nama_asli']) ?>
          <span class="text-xs text-gray-500 dark:text-brand-muted">
            (<?= number_format((int) $isi['bnba']['ukuran'] / 1024, 0, ',', '.') ?> KB)
          </span>
        <?php else: ?>
          <span class="text-gray-500 dark:text-brand-muted">tidak diunggah (opsional)</span>
        <?php endif; ?>
      </p>
    </section>

  <?php else: ?>
    <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
      <?php
      $r = $isi['ringkasan'];
      $kartu = [
          ['Penanganan kumuh', $r && (int) $r['ada_penanganan'] === 1 ? 'Ada' : 'Tidak Ada'],
          ['Progres realisasi', $r && (int) $r['ada_progres'] === 1 ? 'Ada' : 'Tidak ada'],
          ['Total luas', $r ? number_format((float) $r['total_luas_ha'], 2, ',', '.') . ' Ha' : '—'],
          ['Total anggaran', 'Rp ' . number_format((int) $isi['total']['total_anggaran'], 0, ',', '.')],
      ];
      foreach ($kartu as [$l, $v]): ?>
        <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-brand-card">
          <p class="text-xs text-gray-500 dark:text-brand-muted"><?= $e($l) ?></p>
          <p class="mt-1 font-black text-gray-900 dark:text-white"><?= $e($v) ?></p>
        </div>
      <?php endforeach; ?>
    </section>

    <?php if ($r && ! empty($r['catatan_progres'])): ?>
      <p class="rounded-2xl border border-gray-200 bg-white p-4 text-sm dark:border-white/10 dark:bg-brand-card">
        <b class="text-gray-900 dark:text-white">Catatan progres kabupaten:</b>
        <?= $e($r['catatan_progres']) ?>
      </p>
    <?php endif; ?>

    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-brand-card">
      <h2 class="font-bold text-gray-900 dark:text-white">Rincian intervensi (<?= (int) $isi['total']['jumlah_intervensi'] ?>)</h2>
      <?php if ( ! $isi['intervensi']): ?>
        <p class="mt-3 text-sm text-gray-500 dark:text-brand-muted">Tidak ada intervensi dicatat.</p>
      <?php else: ?>
        <div class="mt-4 overflow-x-auto">
          <table class="w-full min-w-[820px] text-left text-sm">
            <thead class="text-xs uppercase text-gray-500 dark:text-brand-muted">
              <tr>
                <th class="py-2 pr-3">#</th><th class="py-2 pr-3">Indikator</th>
                <th class="py-2 pr-3">Kegiatan &amp; lokasi</th><th class="py-2 pr-3">Sumber</th>
                <th class="py-2 pr-3 text-right">Volume</th><th class="py-2 pr-3 text-right">Anggaran</th>
                <th class="py-2 text-right">Padat karya</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
              <?php foreach ($isi['intervensi'] as $row): ?>
                <tr>
                  <td class="py-2 pr-3 text-gray-500 dark:text-brand-muted"><?= (int) $row['urutan'] ?></td>
                  <td class="py-2 pr-3 font-medium text-gray-900 dark:text-white"><?= $e($label['indikator'][$row['indikator']] ?? $row['indikator']) ?></td>
                  <td class="py-2 pr-3"><?= $e($row['nama_kegiatan']) ?>
                    <span class="block text-xs text-gray-500 dark:text-brand-muted"><?= $e($row['lokasi_teks']) ?></span>
                  </td>
                  <td class="py-2 pr-3"><?= $e($label['sumber'][$row['sumber_anggaran']] ?? $row['sumber_anggaran']) ?></td>
                  <td class="py-2 pr-3 text-right"><?= $e(number_format((float) $row['volume'], 2, ',', '.')) ?></td>
                  <td class="py-2 pr-3 text-right"><?= number_format((int) $row['nilai_anggaran'], 0, ',', '.') ?></td>
                  <td class="py-2 text-right"><?= number_format((int) $row['nilai_padat_karya'], 0, ',', '.') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </section>
  <?php endif; ?>

  <!-- ================= keputusan ================= -->
  <?php if ($bisa_diputus): ?>
    <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-brand-card">
      <h2 class="font-bold text-gray-900 dark:text-white">Keputusan</h2>

      <div class="mt-4 grid gap-4 md:grid-cols-2">
        <form method="post" action="<?= base_url('Rekam_Tinjauan/terima') ?>"
              onsubmit="return confirm('Terima laporan ini?')">
          <input type="hidden" name="<?= $e($csrf_name) ?>" value="<?= $e($csrf_hash) ?>">
          <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
          <p class="text-sm text-gray-500 dark:text-brand-muted">
            Laporan tetap terkunci untuk kabupaten dan ditandai sudah ditinjau.
          </p>
          <button class="mt-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-blue-700 dark:bg-brand-primary dark:text-brand-dark dark:hover:bg-brand-hover">
            Terima laporan
          </button>
        </form>

        <form method="post" action="<?= base_url('Rekam_Tinjauan/minta_perbaikan') ?>">
          <input type="hidden" name="<?= $e($csrf_name) ?>" value="<?= $e($csrf_hash) ?>">
          <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
          <label for="catatan_admin" class="text-sm font-bold text-gray-900 dark:text-white">
            Catatan perbaikan <span class="text-red-600">(wajib)</span>
          </label>
          <textarea id="catatan_admin" name="catatan_admin" rows="3" required
            class="mt-1 w-full rounded-xl border border-gray-200 bg-transparent p-3 text-sm dark:border-white/10"
            placeholder="Sebutkan apa yang perlu diperbaiki — catatan ini dibaca petugas kabupaten"></textarea>
          <button class="mt-2 rounded-xl border border-gray-200 px-4 py-2 text-sm font-bold dark:border-white/10">
            Minta perbaikan
          </button>
        </form>
      </div>
    </section>
  <?php elseif ( ! $boleh_putuskan): ?>
    <p class="rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 dark:border-white/10 dark:bg-black/20 dark:text-brand-muted">
      <i class="ph ph-eye mr-1"></i> Halaman ini <b>hanya baca</b>. Keputusan terima atau minta
      perbaikan tetap kewenangan Admin Bidang <?= $e(ucfirst($domain)) ?>.
    </p>
  <?php elseif ($sudah_ditinjau): ?>
    <p class="rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 dark:border-white/10 dark:bg-black/20 dark:text-brand-muted">
      Laporan ini sudah <b>diterima</b> pada <?= $e($laporan['reviewed_at']) ?>. Tidak ada
      keputusan lain yang bisa diambil tanpa kabupaten mengirim ulang.
    </p>
  <?php else: ?>
    <p class="rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 dark:border-white/10 dark:bg-black/20 dark:text-brand-muted">
      Laporan sedang dikembalikan ke kabupaten untuk diperbaiki. Keputusan berikutnya
      baru bisa diambil setelah mereka mengirim ulang.
    </p>
  <?php endif; ?>
</div>
