<?php
/**
 * Rekam Data PBG / BPHTB: satu berkas Excel per bulan, pola unggah BNBA.
 * Dipakai Rekam_Pbg dan Rekam_Bphtb; `$kelas` = awalan URL controller pemanggil.
 */
$e = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$nama_bulan = array_map('ucfirst', Rekam_pbg_bphtb_import::BULAN);
?>

<div class="tumpuk-bagian">

  <section class="kartu-admin isi-kartu">
    <div class="flex flex-wrap items-center gap-3">
      <span class="rounded-lg bg-gray-100 px-3 py-2 text-sm dark:bg-black/20">
        Kabupaten/Kota <b class="text-gray-900 dark:text-white"><?= $e($scope_label) ?></b>
      </span>
      <form method="get" action="<?= base_url($kelas) ?>" class="flex flex-wrap items-center gap-2">
        <label class="text-sm text-gray-500 dark:text-brand-muted" for="tahun">Tahun</label>
        <select id="tahun" name="tahun" class="rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm dark:border-white/10">
          <?php for ($t = (int) date('Y') + 1; $t >= (int) date('Y') - 2; $t--): ?>
            <option value="<?= $t ?>" <?= $t === $tahun ? 'selected' : '' ?>><?= $t ?></option>
          <?php endfor; ?>
        </select>
        <button class="tombol-kedua"><i class="ph ph-calendar-blank"></i><span>Buka tahun</span></button>
      </form>
    </div>
  </section>

  <section class="kartu-admin isi-kartu">
    <h2 class="text-lg font-black text-gray-900 dark:text-white">Unggah data <?= $e($label) ?></h2>
    <form method="post" action="<?= base_url($kelas . '/unggah') ?>" enctype="multipart/form-data" class="mt-3 space-y-3">
      <input type="hidden" name="<?= $e($this->security->get_csrf_token_name()) ?>" value="<?= $e($this->security->get_csrf_hash()) ?>">
      <input type="hidden" name="tahun" value="<?= (int) $tahun ?>">
      <ol class="space-y-3 text-sm text-gray-700 dark:text-gray-300">
        <li class="flex flex-wrap items-center gap-x-3 gap-y-2">
          <span class="font-bold">1.</span>
          <a class="tombol-kedua !min-h-[2.5rem] !px-4 !text-sm !border-blue-600 dark:!border-brand-primary/60" href="<?= base_url($kelas . '/templat') ?>" download>
            <i class="ph ph-microsoft-excel-logo !text-lg" aria-hidden="true"></i><span>Unduh templat <?= $e($label) ?></span>
          </a>
          <span class="text-xs text-gray-500 dark:text-brand-muted">Kolom NIK sudah berformat Teks.</span>
        </li>
        <li class="flex gap-3">
          <span class="font-bold">2.</span>
          <?php if ($jenis === 'pbg'): ?>
            <span>Isi lembar <b>PENGEMBANG</b> dan/atau <b>PERORANGAN</b>. KODE KELURAHAN/DESA terisi otomatis bila
              KECAMATAN dan KELURAHAN/DESA ditulis sama dengan lembar KODE WILAYAH.</span>
          <?php else: ?>
            <span>Isi lembar <b>BPHTB</b>. Nama Kecamatan dan Nama Kelurahan ditulis sama dengan lembar KODE WILAYAH;
              NIK Penerima 16 angka.</span>
          <?php endif; ?>
        </li>
        <li class="flex gap-3">
          <span class="font-bold">3.</span>
          <div class="min-w-0 flex-1 space-y-2">
            <label class="block">
              <span>Bulan data</span>
              <select name="bulan" class="ml-2 rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm dark:border-white/10">
                <?php foreach ($nama_bulan as $n => $nm): ?>
                  <option value="<?= $n ?>" <?= $n === $bulan_pilih ? 'selected' : '' ?>><?= $e($nm) ?> <?= (int) $tahun ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <?php $this->load->view('admin/components/input_berkas', ['ib_name' => 'berkas', 'ib_accept' => '.xlsx,.xls', 'ib_required' => TRUE, 'ib_attr' => '']); ?>
          </div>
        </li>
      </ol>
      <p class="text-xs text-gray-500 dark:text-brand-muted">
        Kolom bulan di setiap baris harus sama dengan bulan yang dipilih. Satu baris salah menolak seluruh berkas,
        dengan nomor barisnya. Mengunggah lagi untuk bulan yang sama menggantikan berkas lama.
      </p>
      <button class="tombol-utama"><i class="ph ph-upload-simple"></i><span>Unggah</span></button>
    </form>
  </section>

  <section class="kartu-admin isi-kartu">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
      <h2 class="text-lg font-black text-gray-900 dark:text-white"><?= $e($label) ?> tahun <?= (int) $tahun ?></h2>
      <span class="text-sm text-gray-500 dark:text-brand-muted"><b class="text-gray-900 dark:text-white"><?= count($unggahan) ?></b> dari 12 bulan terunggah</span>
    </div>
    <ul class="mt-3 grid items-start gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      <?php foreach ($nama_bulan as $n => $nm): $u = $unggahan[$n] ?? NULL; ?>
        <?php if ($u): ?>
          <li class="flex flex-col gap-2 rounded-xl border border-emerald-500/40 bg-emerald-50 p-3 text-sm dark:bg-emerald-500/10">
            <div class="flex items-center justify-between gap-2">
              <span class="font-bold text-gray-900 dark:text-white"><?= $e($nm) ?></span>
              <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 dark:text-emerald-300"><i class="ph ph-check-circle" aria-hidden="true"></i>Terunggah</span>
            </div>
            <span class="text-xs text-gray-600 dark:text-gray-300"><?= $e($u['ringkasan']) ?></span>
            <span class="text-xs text-gray-500 dark:text-brand-muted"><?= $e(date('d/m/Y H:i', strtotime($u['uploaded_at']))) ?></span>
            <a class="tombol-kedua !min-h-[2.25rem] !px-3 !text-xs" href="<?= base_url($kelas . '/unduh/' . (int) $u['id']) ?>" download title="<?= $e($u['nama_asli']) ?>">
              <i class="ph ph-download-simple" aria-hidden="true"></i><span class="truncate"><?= $e($u['nama_asli']) ?></span>
            </a>
          </li>
        <?php else: ?>
          <li class="flex items-center justify-between gap-2 rounded-xl border border-dashed border-gray-200 p-3 text-sm dark:border-white/10">
            <span class="font-bold text-gray-700 dark:text-gray-300"><?= $e($nm) ?></span>
            <span class="text-xs text-gray-400 dark:text-brand-muted">Belum diunggah</span>
          </li>
        <?php endif; ?>
      <?php endforeach; ?>
    </ul>
  </section>

</div>
