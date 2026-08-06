<?php
/**
 * Rekam Data — Input Capaian Kawasan (D4).
 *
 * Daftar intervensi sebagai kartu bertumpuk + satu form inline, bukan 20 halaman
 * berurutan. Tanpa batas jumlah. Total anggaran & padat karya DIHITUNG dari
 * daftar, tidak pernah diketik.
 */
$e = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$csrf_name = $this->security->get_csrf_token_name();
$csrf_hash = $this->security->get_csrf_hash();

$laporan_id = (int) $laporan['id'];
$tahun      = (int) $laporan['tahun'];
$triwulan      = (int) $laporan['triwulan'];
$nama_tw = [1 => 'TW I', 2 => 'TW II', 3 => 'TW III', 4 => 'TW IV']; //


$ada_penanganan = $ringkasan ? (int) $ringkasan['ada_penanganan'] : NULL;
$ada_progres    = $ringkasan ? (int) $ringkasan['ada_progres'] : NULL;

// Intervensi yang sedang diubah, kalau ada.
$sedang_diubah = NULL;
foreach ($intervensi as $row) {
    if ((int) $row['id'] === $ubah_id) {
        $sedang_diubah = $row;
    }
}
?>

<div class="space-y-4">

  <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-brand-card">
    <div class="flex flex-wrap items-center gap-3">
      <span class="rounded-lg bg-gray-100 px-3 py-2 text-sm dark:bg-black/20">
        Kabupaten/Kota <b class="text-gray-900 dark:text-white"><?= $e($scope_label) ?></b>
      </span>
      <form method="get" action="<?= base_url('Rekam_Kawasan') ?>" class="flex flex-wrap items-center gap-2">
        <label class="text-sm text-gray-500 dark:text-brand-muted" for="triwulan">Triwulan</label>
        <select id="triwulan" name="triwulan" class="rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm dark:border-white/10">
          <?php foreach ($nama_tw as $n => $label): ?>
            <option value="<?= $n ?>" <?= $n === $triwulan ? 'selected' : '' ?>><?= $e($label) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="tahun" aria-label="Tahun" class="rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm dark:border-white/10">
          <?php for ($t = (int) date('Y') + 1; $t >= (int) date('Y') - 2; $t--): ?>
            <option value="<?= $t ?>" <?= $t === $tahun ? 'selected' : '' ?>><?= $t ?></option>
          <?php endfor; ?>
        </select>
        <button class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-bold dark:border-white/10">Buka periode</button>
      </form>
      <span class="ml-auto rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">
        <?= $e(ucfirst(str_replace('_', ' ', $laporan['status']))) ?>
      </span>
    </div>

    <?php if ( ! empty($laporan['catatan_admin'])): ?>
      <!-- Sama seperti Perumahan: catatan peninjau harus terlihat di layar
           tempat perbaikannya dikerjakan, bukan hanya di riwayat. -->
      <p class="mt-4 rounded-r-xl border-l-4 border-red-500 bg-red-50 p-3 text-sm text-red-900 dark:bg-red-500/10 dark:text-red-200">
        <b>Perlu perbaikan — catatan Admin Bidang:</b> <?= $e($laporan['catatan_admin']) ?>
      </p>
    <?php endif; ?>

    <?php /* Di sini dulu ada spanduk "N intervensi diwarisi dari laporan
             terkirim sebelumnya. Ubah yang berubah, tambah yang baru." Sejak
             pewarisan dicabut ia tidak mungkin muncul lagi — `diwarisi` selalu
             0 — jadi yang tersisa cuma cabang mati yang menyiratkan perilaku
             yang sudah tidak ada. Dibuang, bukan dibiarkan sebagai penanda
             harapan.

             Keputusan user 30 Jul 2026: Kawasan per-triwulan tanpa pewarisan,
             konsisten dengan K7. Petugas mengisi capaian triwulan itu saja;
             daftar triwulan lalu TIDAK terbawa. Kalau kelak diputuskan
             sebaliknya, yang dikembalikan bukan spanduk ini melainkan pintu
             tulisnya di Rekam_data_model. */ ?>
  </section>

  <?php if ($terkunci): ?>
    <p class="rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 dark:border-white/10 dark:bg-black/20 dark:text-brand-muted">
      Laporan periode ini sudah <b>terkirim</b> dan terkunci. Hanya Admin Bidang yang bisa
      mengembalikannya untuk diperbaiki.
    </p>
  <?php endif; ?>

  <!-- ================= ringkasan ================= -->
  <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-brand-card">
    <h2 class="font-bold text-gray-900 dark:text-white">Ringkasan penanganan</h2>

    <form method="post" action="<?= base_url('Rekam_Kawasan/simpan_ringkasan') ?>" class="mt-4 space-y-4">
      <input type="hidden" name="<?= $e($csrf_name) ?>" value="<?= $e($csrf_hash) ?>">
      <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">

      <fieldset <?= $terkunci ? 'disabled' : '' ?> class="space-y-4">
        <div>
          <p class="text-sm font-bold text-gray-900 dark:text-white">Ada penanganan kawasan permukiman kumuh?</p>
          <div class="mt-2 flex flex-wrap gap-4 text-sm">
            <label class="flex items-center gap-2"><input type="radio" name="ada_penanganan" value="1" <?= $ada_penanganan === 1 ? 'checked' : '' ?> required> Ada</label>
            <label class="flex items-center gap-2"><input type="radio" name="ada_penanganan" value="0" <?= $ada_penanganan === 0 ? 'checked' : '' ?>> Tidak Ada</label>
          </div>
        </div>

        <div>
          <p class="text-sm font-bold text-gray-900 dark:text-white">Ada progres realisasi?</p>
          <div class="mt-2 flex flex-wrap gap-4 text-sm">
            <label class="flex items-center gap-2"><input type="radio" name="ada_progres" value="1" <?= $ada_progres === 1 ? 'checked' : '' ?>> Ya</label>
            <label class="flex items-center gap-2"><input type="radio" name="ada_progres" value="0" <?= $ada_progres === 0 ? 'checked' : '' ?>> Tidak</label>
          </div>
          <p class="mt-1 text-xs text-gray-500 dark:text-brand-muted">
            Kalau <b>Tidak</b>, cukup isi catatan progres di bawah — Total Luas tidak dipaksa terisi.
          </p>
        </div>

        <div>
          <label for="catatan_progres" class="text-sm font-bold text-gray-900 dark:text-white">Catatan progres</label>
          <textarea id="catatan_progres" name="catatan_progres" rows="3"
            class="mt-1 w-full rounded-xl border border-gray-200 bg-transparent p-3 text-sm dark:border-white/10"
            placeholder="Wajib bila tidak ada progres realisasi"><?= $e($ringkasan['catatan_progres'] ?? '') ?></textarea>
        </div>

        <div>
          <label for="total_luas_ha" class="text-sm font-bold text-gray-900 dark:text-white">Total Luas Penanganan (Ha)</label>
          <input id="total_luas_ha" type="number" min="0" step="0.01" name="total_luas_ha"
            value="<?= $e($ringkasan['total_luas_ha'] ?? '0.00') ?>"
            class="mt-1 w-40 rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm dark:border-white/10">
        </div>

        <?php if ( ! $terkunci): ?>
          <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-blue-700 dark:bg-brand-primary dark:text-brand-dark dark:hover:bg-brand-hover">Simpan ringkasan</button>
        <?php endif; ?>
      </fieldset>
    </form>
  </section>

  <!-- ================= daftar intervensi ================= -->
  <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-brand-card">
    <div class="flex flex-wrap items-center gap-3">
      <h2 class="font-bold text-gray-900 dark:text-white">Daftar intervensi</h2>
      <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-600 dark:bg-white/5 dark:text-brand-muted">
        <?= (int) $total['jumlah_intervensi'] ?> kegiatan
      </span>
      <span class="ml-auto text-xs text-gray-500 dark:text-brand-muted">Tanpa batas jumlah</span>
    </div>

    <?php if ( ! $intervensi): ?>
      <p class="mt-4 rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-white/10 dark:text-brand-muted">
        Belum ada intervensi dicatat.
      </p>
    <?php endif; ?>

    <div class="mt-4 space-y-3">
      <?php foreach ($intervensi as $row):
          $ind = $indikator_label[$row['indikator']] ?? ['label' => $row['indikator'], 'satuan' => ''];
      ?>
        <article class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
          <div class="flex flex-wrap items-start gap-3">
            <div class="flex-1 min-w-[200px]">
              <p class="font-bold text-gray-900 dark:text-white">
                <?= (int) $row['urutan'] ?>. <?= $e($ind['label']) ?>
                <?php if ($ind['satuan'] !== ''): ?>
                  <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-bold text-purple-800 dark:bg-purple-500/10 dark:text-purple-300">satuan <?= $e($ind['satuan']) ?></span>
                <?php endif; ?>
              </p>
              <p class="mt-1 text-sm text-gray-600 dark:text-brand-muted"><?= $e($row['nama_kegiatan']) ?></p>
              <p class="text-sm text-gray-500 dark:text-brand-muted"><?= $e($row['lokasi_teks']) ?></p>
            </div>
            <?php if ( ! $terkunci): ?>
              <a href="<?= base_url('Rekam_Kawasan?tahun=' . $tahun . '&triwulan=' . $triwulan . '&ubah=' . (int) $row['id']) ?>"
                 class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-bold dark:border-white/10">Ubah</a>
              <form method="post" action="<?= base_url('Rekam_Kawasan/hapus_intervensi') ?>"
                    onsubmit="return confirm('Hapus intervensi ini?')">
                <input type="hidden" name="<?= $e($csrf_name) ?>" value="<?= $e($csrf_hash) ?>">
                <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
                <input type="hidden" name="intervensi_id" value="<?= (int) $row['id'] ?>">
                <button class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-bold text-red-600 dark:border-white/10">Hapus</button>
              </form>
            <?php endif; ?>
          </div>
          <div class="mt-3 flex flex-wrap gap-4 border-t border-dashed border-gray-200 pt-3 text-xs text-gray-500 dark:border-white/10 dark:text-brand-muted">
            <span>Sumber <b class="text-gray-900 dark:text-white"><?= $e($sumber_label[$row['sumber_anggaran']] ?? $row['sumber_anggaran']) ?></b></span>
            <span>Volume <b class="text-gray-900 dark:text-white"><?= $e(number_format((float) $row['volume'], 2, ',', '.')) ?> <?= $e($ind['satuan']) ?></b></span>
            <span>Anggaran <b class="text-gray-900 dark:text-white">Rp <?= number_format((int) $row['nilai_anggaran'], 0, ',', '.') ?></b></span>
            <span>Padat karya <b class="text-gray-900 dark:text-white">Rp <?= number_format((int) $row['nilai_padat_karya'], 0, ',', '.') ?></b></span>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <?php if ( ! $terkunci): ?>
      <form method="post" action="<?= base_url('Rekam_Kawasan/simpan_intervensi') ?>"
            class="mt-4 rounded-xl border border-brand-primary p-4">
        <input type="hidden" name="<?= $e($csrf_name) ?>" value="<?= $e($csrf_hash) ?>">
        <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
        <input type="hidden" name="intervensi_id" value="<?= $sedang_diubah ? (int) $sedang_diubah['id'] : '' ?>">

        <p class="font-bold text-gray-900 dark:text-white">
          <?= $sedang_diubah ? 'Ubah intervensi #' . (int) $sedang_diubah['urutan'] : 'Tambah intervensi' ?>
        </p>

        <div class="mt-3 grid gap-3 md:grid-cols-2">
          <label class="text-sm">
            <span class="font-bold text-gray-900 dark:text-white">Indikator</span>
            <select name="indikator" required class="mt-1 w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 dark:border-white/10">
              <?php foreach ($indikator_label as $kode => $ind): ?>
                <option value="<?= $e($kode) ?>" <?= ($sedang_diubah['indikator'] ?? '') === $kode ? 'selected' : '' ?>>
                  <?= $e($ind['label']) ?><?= $ind['satuan'] !== '' ? ' (' . $e($ind['satuan']) . ')' : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
            <span class="mt-1 block text-xs text-gray-500 dark:text-brand-muted">Satuan ikut indikator, tidak disimpan terpisah.</span>
          </label>

          <label class="text-sm">
            <span class="font-bold text-gray-900 dark:text-white">Sumber anggaran</span>
            <select name="sumber_anggaran" required class="mt-1 w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 dark:border-white/10">
              <?php foreach ($sumber_label as $kode => $label): ?>
                <option value="<?= $e($kode) ?>" <?= ($sedang_diubah['sumber_anggaran'] ?? '') === $kode ? 'selected' : '' ?>><?= $e($label) ?></option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="text-sm md:col-span-2">
            <span class="font-bold text-gray-900 dark:text-white">Nama kegiatan/program</span>
            <input name="nama_kegiatan" required maxlength="500" value="<?= $e($sedang_diubah['nama_kegiatan'] ?? '') ?>"
              class="mt-1 w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 dark:border-white/10">
          </label>

          <label class="text-sm md:col-span-2">
            <span class="font-bold text-gray-900 dark:text-white">Lokasi (RT, RW, Desa/Kelurahan, Kecamatan)</span>
            <input name="lokasi_teks" required maxlength="500" value="<?= $e($sedang_diubah['lokasi_teks'] ?? '') ?>"
              class="mt-1 w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 dark:border-white/10">
          </label>

          <label class="text-sm">
            <span class="font-bold text-gray-900 dark:text-white">Volume</span>
            <input type="number" min="0" step="0.01" name="volume" value="<?= $e($sedang_diubah['volume'] ?? '0') ?>"
              class="mt-1 w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-right dark:border-white/10">
          </label>

          <label class="text-sm">
<?php /* Label saja yang berubah (revisi dinas 5 Agt 2026, butir D3). `name` TETAP
                 `keterangan_sumber` — itu nama kolom di `rd_kawasan_intervensi`, dan
                 menggantinya berarti migrasi untuk perubahan kata di layar.
                 Label "Sumber" di atas (sumber anggaran) BUKAN ini dan tidak disentuh. */ ?>
            <span class="font-bold text-gray-900 dark:text-white">Keterangan (opsional)</span>
            <input name="keterangan_sumber" maxlength="150" value="<?= $e($sedang_diubah['keterangan_sumber'] ?? '') ?>"
              class="mt-1 w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 dark:border-white/10">
          </label>

          <label class="text-sm">
            <span class="font-bold text-gray-900 dark:text-white">Nilai anggaran (Rp)</span>
            <input type="number" min="0" step="1" name="nilai_anggaran" value="<?= (int) ($sedang_diubah['nilai_anggaran'] ?? 0) ?>"
              class="mt-1 w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-right dark:border-white/10">
          </label>

          <label class="text-sm">
            <span class="font-bold text-gray-900 dark:text-white">Nilai padat karya (Rp)</span>
            <input type="number" min="0" step="1" name="nilai_padat_karya" value="<?= (int) ($sedang_diubah['nilai_padat_karya'] ?? 0) ?>"
              class="mt-1 w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-right dark:border-white/10">
          </label>
        </div>

        <div class="mt-3 flex flex-wrap gap-2">
          <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-blue-700 dark:bg-brand-primary dark:text-brand-dark dark:hover:bg-brand-hover">
            <?= $sedang_diubah ? 'Simpan perubahan' : 'Tambah intervensi' ?>
          </button>
          <?php if ($sedang_diubah): ?>
            <a href="<?= base_url('Rekam_Kawasan?tahun=' . $tahun . '&triwulan=' . $triwulan) ?>"
               class="rounded-xl border border-gray-200 px-4 py-2 text-sm font-bold dark:border-white/10">Batal</a>
          <?php endif; ?>
        </div>
      </form>
    <?php endif; ?>
  </section>

  <!-- ================= total ================= -->
  <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-brand-card">
    <div class="flex flex-wrap items-center gap-3">
      <h2 class="font-bold text-gray-900 dark:text-white">Total</h2>
      <span class="rounded-full bg-purple-100 px-3 py-1 text-xs font-bold text-purple-800 dark:bg-purple-500/10 dark:text-purple-300">
        dihitung, tidak diketik
      </span>
    </div>
    <p class="mt-3 rounded-r-xl border-l-4 border-purple-500 bg-purple-50 p-3 text-sm text-purple-900 dark:bg-purple-500/10 dark:text-purple-200">
      Di form dinas Total Anggaran diketik manual dan bisa berbeda dari jumlah rinciannya.
      Di sini ia dijumlahkan dari daftar intervensi, jadi mustahil berselisih.
    </p>
    <div class="mt-3 overflow-x-auto">
      <table class="w-full text-sm">
        <tr class="border-b border-gray-100 dark:border-white/5">
          <th class="py-2 text-left font-medium text-gray-500 dark:text-brand-muted">Jumlah intervensi</th>
          <td class="py-2 text-right font-bold text-gray-900 dark:text-white"><?= (int) $total['jumlah_intervensi'] ?> kegiatan</td>
        </tr>
        <tr class="border-b border-gray-100 dark:border-white/5">
          <th class="py-2 text-left font-medium text-gray-500 dark:text-brand-muted">Total anggaran</th>
          <td class="py-2 text-right font-bold text-gray-900 dark:text-white">Rp <?= number_format((int) $total['total_anggaran'], 0, ',', '.') ?></td>
        </tr>
        <tr>
          <th class="py-2 text-left font-medium text-gray-500 dark:text-brand-muted">Total nilai padat karya</th>
          <td class="py-2 text-right font-bold text-gray-900 dark:text-white">Rp <?= number_format((int) $total['total_padat_karya'], 0, ',', '.') ?></td>
        </tr>
      </table>
    </div>
  </section>

  <?php if ( ! $terkunci): ?>
    <!-- Sama seperti layar Perumahan: tidak sticky, karena sebagai bar melayang
         ia menutupi konten di bawahnya dan memakan seperempat layar ponsel. -->
    <section class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-brand-card">
      <form method="post" action="<?= base_url('Rekam_Kawasan/kirim') ?>"
            class="flex flex-wrap items-center gap-3"
            onsubmit="return confirm('Kirim laporan periode ini? Setelah terkirim, laporan terkunci.')">
        <input type="hidden" name="<?= $e($csrf_name) ?>" value="<?= $e($csrf_hash) ?>">
        <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
        <span class="flex-1 text-sm text-gray-500 dark:text-brand-muted">
          <?php if ( ! $ringkasan): ?>
            Jawab dulu apakah ada penanganan kawasan permukiman kumuh.
          <?php elseif ($ada_penanganan === 1 && $ada_progres === 1 && (int) $total['jumlah_intervensi'] === 0): ?>
            Ada progres realisasi, tetapi belum ada satu pun intervensi dicatat.
          <?php else: ?>
            Laporan siap dikirim.
          <?php endif; ?>
        </span>
        <button class="rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-blue-700 dark:bg-brand-primary dark:text-brand-dark dark:hover:bg-brand-hover">Kirim laporan</button>
      </form>
    </section>
  <?php endif; ?>
</div>
