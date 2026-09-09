<?php
/**
 * Rekam Data - wizard Input Capaian Perumahan (W3).
 *
 * Lima langkah dirender server, satu berkas - mengikuti idiom
 * `pages/warga/pendataan.php`. Tidak ada JavaScript yang wajib: form
 * tambah/ubah memakai `<details>`, sehingga layar tetap berfungsi penuh kalau
 * skrip gagal dimuat. Modal di rancangan diterjemahkan menjadi disclosure,
 * bukan ditiru dengan overlay yang butuh JS.
 *
 * Gaya: dialek `rekam` (space-y-4, p-5, tanpa shadow, dark:border-white/10) dan
 * tombol utama `bg-blue-600 dark:bg-brand-primary` - biru di terang, lime di
 * gelap. `bg-brand-primary` tanpa `dark:` menghasilkan tombol lime di halaman
 * putih; itu jebakan yang sudah pernah terjadi.
 */
$e = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$rp = static fn($n) => number_format((int) $n, 0, ',', '.');

$laporan_id = (int) ($laporan['id'] ?? 0);
$aktif      = array_search($langkah, $urutan, TRUE);

$tombol = 'rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white transition-colors'
    . ' hover:bg-blue-700 dark:bg-brand-primary dark:text-brand-dark dark:hover:bg-brand-hover';
$tombol_lembut = 'rounded-xl border border-gray-200 px-4 py-2 text-sm font-bold'
    . ' text-gray-700 transition-colors hover:bg-gray-50 dark:border-white/10'
    . ' dark:text-brand-muted dark:hover:bg-white/5';
$kotak  = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-brand-card';
$isian  = 'mt-1 block w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm'
    . ' dark:border-white/10';

$nama_tw = [1 => 'TW I', 2 => 'TW II', 3 => 'TW III', 4 => 'TW IV'];
$warna_status = [
    'draft'           => 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300',
    'terkirim'        => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300',
    'perlu_perbaikan' => 'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-300',
];
$label_status = ['draft' => 'Draft', 'terkirim' => 'Terkirim', 'perlu_perbaikan' => 'Perlu Perbaikan'];

/** Form tambah/ubah satu sumber dana. Dipakai untuk baris baru maupun edit. */
$form_sumber = function ($program, $row = NULL) use ($e, $laporan_id, $isian, $tombol, $sumber_label, $sumber_berketerangan) {
    $terkunci_sumber = $row !== NULL;
    ?>
    <form method="post" action="<?= base_url('Rekam_Perumahan/simpan_sumber') ?>" class="space-y-3">
      <input type="hidden" name="<?= $e($this->security->get_csrf_token_name()) ?>" value="<?= $e($this->security->get_csrf_hash()) ?>">
      <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
      <input type="hidden" name="program" value="<?= $e($program) ?>">

      <div>
        <label class="text-xs font-bold text-gray-700 dark:text-brand-muted">Sumber Dana</label>
        <?php if ($terkunci_sumber): ?>
          <input type="hidden" name="sumber_dana" value="<?= $e($row['sumber_dana']) ?>">
          <p class="mt-1 text-sm font-bold text-gray-900 dark:text-white"><?= $e($sumber_label[$row['sumber_dana']] ?? $row['sumber_dana']) ?></p>
        <?php else: ?>
          <select name="sumber_dana" required class="<?= $isian ?>">
            <option value="">Pilih sumber dana</option>
            <?php foreach ($sumber_label as $kode => $label): ?>
              <option value="<?= $e($kode) ?>"><?= $e($label) ?></option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
      </div>

      <?php foreach (['rencana' => 'Rencana', 'realisasi' => 'Realisasi'] as $sisi => $judul): ?>
        <fieldset class="rounded-xl border border-gray-200 p-3 dark:border-white/10">
          <legend class="px-1 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-brand-muted"><?= $judul ?></legend>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs text-gray-500 dark:text-brand-muted">Unit</label>
              <input type="number" min="0" step="1" name="<?= $sisi ?>_unit"
                     value="<?= (int) ($row[$sisi . '_unit'] ?? 0) ?>" class="<?= $isian ?>">
            </div>
            <div>
              <label class="text-xs text-gray-500 dark:text-brand-muted">Anggaran (Rp)</label>
              <input type="number" min="0" step="1" name="<?= $sisi ?>_anggaran"
                     value="<?= (int) ($row[$sisi . '_anggaran'] ?? 0) ?>" class="<?= $isian ?>">
            </div>
          </div>
        </fieldset>
      <?php endforeach; ?>

      <div>
        <label class="text-xs font-bold text-gray-700 dark:text-brand-muted">
          Nama perusahaan / penyalur
        </label>
        <input type="text" name="keterangan" maxlength="150"
               value="<?= $e($row['keterangan'] ?? '') ?>" class="<?= $isian ?>">
        <p class="mt-1 text-xs text-gray-500 dark:text-brand-muted">
          Hanya dipakai untuk <b><?= $e(implode(', ', array_map(fn($k) => $sumber_label[$k] ?? $k, array_keys($sumber_berketerangan)))) ?></b>;
          di sumber lain isian ini diabaikan, bukan ditolak.
        </p>
      </div>

      <button class="<?= $tombol ?>">Simpan</button>
    </form>
    <?php
};
?>

<div data-panel-progresif class="space-y-4">

  <!-- ================= kepala + penunjuk langkah ================= -->
  <section class="<?= $kotak ?>">
    <div class="flex flex-wrap items-center gap-3">
      <span class="rounded-lg bg-gray-100 px-3 py-2 text-sm dark:bg-black/20">
        Kabupaten/Kota <b class="text-gray-900 dark:text-white"><?= $e($scope_label) ?></b>
      </span>
      <?php if ($laporan): ?>
        <span class="rounded-lg bg-gray-100 px-3 py-2 text-sm dark:bg-black/20">
          <b class="text-gray-900 dark:text-white"><?= $e($nama_tw[(int) $triwulan] ?? $triwulan) ?> <?= (int) $tahun ?></b>
        </span>
        <span class="rounded-full px-3 py-1 text-xs font-bold <?= $warna_status[$laporan['status']] ?? '' ?>">
          <?= $e($label_status[$laporan['status']] ?? $laporan['status']) ?>
        </span>
      <?php endif; ?>
      <a href="<?= base_url('Rekam_Perumahan') ?>" class="ml-auto text-sm font-bold text-blue-600 dark:text-brand-primary">
        &larr; Capaian
      </a>
    </div>

    <?php if ($laporan && $terkunci): ?>
      <?php /* Spanduk ini muncul di SETIAP langkah, bukan hanya di layar isian.
                Sebelumnya L1 dan L2 tetap menyajikan formulir yang bisa ditekan
                untuk laporan terkunci, dan tiap kiriman dijawab "Laporan sudah
                terkirim dan terkunci" - menawarkan tindakan yang sudah pasti
                ditolak, lalu menyalahkan penggunanya. */ ?>
      <p class="mt-4 rounded-xl border-l-4 border-emerald-500 bg-emerald-50 p-3 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">
        <b>Periode ini sudah dikirim dan terkunci.</b> Isinya bisa dibaca, tidak bisa diubah.
        Perubahan baru mungkin setelah Admin Bidang mengembalikannya lewat
        <i>Minta Perbaikan</i>. Untuk mengisi periode lain,
        <a class="font-bold underline" href="<?= base_url('Rekam_Perumahan/input') ?>">pilih triwulan lain</a>.
      </p>
    <?php endif; ?>

    <?php if ($laporan && $laporan['status'] === 'perlu_perbaikan' && ! empty($laporan['catatan_admin'])): ?>
      <?php /* Catatan peninjau dulu HANYA dirender di L5 (Review & Kirim).
                Alasan orang ini membuka wizard justru karena laporannya
                dikembalikan - dan ia mendarat di langkah yang tersimpan di
                `current_step`, yang belum tentu L5. Jadi ia melihat formulir
                terbuka tanpa satu pun keterangan kenapa, dan baru menemukan
                alasannya kalau kebetulan mengklik sampai langkah terakhir.
                Alasan yang sama persis dengan spanduk terkunci di atas: kalau
                sesuatu mengubah apa yang boleh/harus dilakukan di SETIAP
                langkah, ia harus terlihat di setiap langkah. */ ?>
      <p class="mt-4 rounded-xl border-l-4 border-red-500 bg-red-50 p-3 text-sm text-red-900 dark:bg-red-500/10 dark:text-red-200">
        <b>Dikembalikan untuk diperbaiki.</b> Catatan peninjau:
        <?= $e($laporan['catatan_admin']) ?>
      </p>
    <?php endif; ?>

    <ol class="mt-4 flex flex-wrap gap-2">
      <?php foreach ($urutan as $i => $kode): ?>
        <?php $lewat = $aktif !== FALSE && $i < $aktif; ?>
        <li class="flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold
                   <?= $kode === $langkah
                        ? 'bg-blue-600 text-white dark:bg-brand-primary dark:text-brand-dark'
                        : ($lewat ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300'
                                  : 'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-brand-muted') ?>">
          <span><?= $i + 1 ?>.</span><span><?= $e($label_langkah[$kode]) ?></span>
        </li>
      <?php endforeach; ?>
    </ol>
  </section>

  <?php // ================= L1 - PERIODE (frame 003) ================= ?>
  <?php if ($langkah === 'periode'): ?>
    <section class="<?= $kotak ?>">
      <h2 class="text-lg font-black text-gray-900 dark:text-white">Periode Pelaporan</h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-brand-muted">
        Pilih tahun dan triwulan yang akan diisi. Draft baru lahir setelah tombol
        Lanjut ditekan - membuka layar ini saja tidak membuat apa pun.
      </p>

      <form method="post" action="<?= base_url('Rekam_Perumahan/mulai') ?>" class="mt-5 space-y-4">
        <input type="hidden" name="<?= $e($this->security->get_csrf_token_name()) ?>" value="<?= $e($this->security->get_csrf_hash()) ?>">

        <div class="max-w-xs">
          <label for="tahun" class="text-xs font-bold text-gray-700 dark:text-brand-muted">Tahun</label>
          <select id="tahun" name="tahun" class="<?= $isian ?>">
            <?php for ($t = (int) date('Y') + 1; $t >= (int) date('Y') - 2; $t--): ?>
              <option value="<?= $t ?>" <?= $t === (int) $tahun ? 'selected' : '' ?>><?= $t ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <fieldset>
          <legend class="text-xs font-bold text-gray-700 dark:text-brand-muted">Triwulan</legend>
          <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
            <?php foreach ($nama_tw as $n => $label): ?>
              <?php $st = $status_triwulan[$n] ?? NULL; ?>
              <label class="cursor-pointer rounded-xl border px-3 py-2.5 text-center transition-colors
                            <?= $st === 'terkirim'
                                  ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-500/30 dark:bg-emerald-500/5'
                                  : 'border-gray-200 hover:border-blue-300 dark:border-white/10 dark:hover:border-brand-primary/30' ?>">
                <span class="block text-sm font-bold">
                  <input type="radio" name="triwulan" value="<?= $n ?>" class="mr-1.5"
                         <?= $n === (int) $triwulan ? 'checked' : '' ?>>
                  <?= $e($label) ?>
                </span>
                <?php /* Status disebut DI SINI, bukan setelah orang masuk. Periode
                          terkirim tetap boleh dipilih - isinya boleh dibaca - tetapi
                          ia tahu lebih dulu bahwa tidak ada yang bisa diubah. */ ?>
                <span class="mt-0.5 block text-[11px] font-medium
                             <?= $st ? 'text-gray-600 dark:text-brand-muted' : 'text-gray-400 dark:text-brand-muted/60' ?>">
                  <?= $st === 'terkirim' ? 'terkunci - sudah dikirim'
                        : ($st === 'perlu_perbaikan' ? 'perlu perbaikan'
                        : ($st === 'draft' ? 'draft tersimpan' : 'belum diisi')) ?>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
        </fieldset>

        <p class="rounded-xl border-l-4 border-blue-500 bg-blue-50 p-3 text-sm text-blue-900 dark:bg-blue-500/10 dark:text-blue-200">
          Isi <b>capaian triwulan ini saja</b>, bukan kumulatif sejak Januari.
          Angka kumulatif dihitung sendiri oleh sistem dan ditampilkan di layar Capaian.
        </p>

        <button class="<?= $tombol ?>">Lanjut &rarr;</button>
      </form>
    </section>

  <?php // ================= L2 - PROGRAM (frame 004) ================= ?>
  <?php elseif ($langkah === 'program'): ?>
    <section class="<?= $kotak ?>">
      <h2 class="text-lg font-black text-gray-900 dark:text-white">Program yang akan dilaporkan</h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-brand-muted">
        Centang hanya program yang memang punya capaian pada triwulan ini.
        Program yang tidak dicentang tidak dianggap kekurangan.
      </p>

      <?php if ($terkunci): ?>
        <div class="mt-5 grid grid-cols-1 gap-2 sm:grid-cols-2">
          <?php foreach ($program_label as $kode => $label): ?>
            <?php $tercentang = in_array($kode, $program_dipilih, TRUE); ?>
            <div class="flex items-center gap-3 rounded-xl border px-4 py-3
                        <?= $tercentang
                              ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-500/20 dark:bg-emerald-500/5'
                              : 'border-gray-200 opacity-50 dark:border-white/10' ?>">
              <i class="ph <?= $tercentang ? 'ph-check-circle text-emerald-600 dark:text-emerald-400' : 'ph-circle text-gray-400' ?>"></i>
              <span class="text-sm font-bold text-gray-900 dark:text-white"><?= $e($label) ?></span>
              <?php if ( ! $tercentang): ?>
                <span class="ml-auto text-xs text-gray-500 dark:text-brand-muted">tidak dilaporkan</span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
        <form method="post" action="<?= base_url('Rekam_Perumahan/langkah') ?>" class="mt-4">
          <input type="hidden" name="<?= $e($this->security->get_csrf_token_name()) ?>" value="<?= $e($this->security->get_csrf_hash()) ?>">
          <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
          <input type="hidden" name="langkah" value="isian">
          <button class="<?= $tombol ?>">Lihat isian &rarr;</button>
        </form>
      <?php else: ?>
      <form method="post" action="<?= base_url('Rekam_Perumahan/simpan_program') ?>" class="mt-5 space-y-4">
        <input type="hidden" name="<?= $e($this->security->get_csrf_token_name()) ?>" value="<?= $e($this->security->get_csrf_hash()) ?>">
        <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">

        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
          <?php foreach ($program_label as $kode => $label): ?>
            <?php $tercentang = in_array($kode, $program_dipilih, TRUE); ?>
            <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-gray-200 px-4 py-3
                          transition-colors hover:border-blue-300 dark:border-white/10 dark:hover:border-brand-primary/30">
              <input type="checkbox" name="program[]" value="<?= $e($kode) ?>" <?= $tercentang ? 'checked' : '' ?>>
              <span class="text-sm font-bold text-gray-900 dark:text-white"><?= $e($label) ?></span>
            </label>
          <?php endforeach; ?>
        </div>

        <p class="rounded-xl border-l-4 border-amber-500 bg-amber-50 p-3 text-xs text-amber-900 dark:bg-amber-500/10 dark:text-amber-200">
          Mencabut centang <b>menghapus seluruh angka</b> program itu pada triwulan ini.
        </p>

        <div class="flex flex-wrap gap-2">
          <button class="<?= $tombol ?>">Lanjut &rarr;</button>
        </div>
      </form>
      <?php endif; ?>
    </section>

  <?php // ========== L3 - ISIAN PER PROGRAM ("Setelah ada Data") ========== ?>
  <?php elseif ($langkah === 'isian'): ?>
    <?php if ( ! $program_dipilih): ?>
      <section class="rounded-2xl border border-gray-200 bg-white p-8 text-center dark:border-white/10 dark:bg-brand-card">
        <p class="font-bold text-gray-900 dark:text-white">Belum ada program yang dicentang.</p>
        <form method="post" action="<?= base_url('Rekam_Perumahan/langkah') ?>" class="mt-4">
          <input type="hidden" name="<?= $e($this->security->get_csrf_token_name()) ?>" value="<?= $e($this->security->get_csrf_hash()) ?>">
          <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
          <input type="hidden" name="langkah" value="program">
          <button class="<?= $tombol ?>">Pilih program dulu</button>
        </form>
      </section>
    <?php else: ?>
      <section class="<?= $kotak ?>">
        <div class="flex flex-wrap gap-2">
          <?php foreach ($program_dipilih as $kode): ?>
            <?php $kosong = in_array($kode, $program_kosong, TRUE); ?>
            <a href="<?= base_url('Rekam_Perumahan/input?laporan=' . $laporan_id . '&langkah=isian&program=' . rawurlencode($kode)) ?>"
               class="rounded-lg px-3 py-1.5 text-xs font-bold
                      <?= $kode === $program_aktif
                            ? 'bg-blue-600 text-white dark:bg-brand-primary dark:text-brand-dark'
                            : 'border border-gray-200 text-gray-600 dark:border-white/10 dark:text-brand-muted' ?>">
              <?= $e($program_label[$kode] ?? $kode) ?><?= $kosong ? ' •' : '' ?>
            </a>
          <?php endforeach; ?>
        </div>
        <p class="mt-2 text-xs text-gray-500 dark:text-brand-muted">
          Tanda <b>•</b> berarti program itu belum punya satu pun sumber dana. Laporan
          tidak bisa dikirim selama masih ada tanda itu.
        </p>
      </section>

      <?php $daftar = $baris[$program_aktif] ?? []; ?>
      <section class="<?= $kotak ?>">
        <h2 class="text-lg font-black text-gray-900 dark:text-white">
          <?= $e($program_label[$program_aktif] ?? $program_aktif) ?>
        </h2>
        <p class="mt-1 text-sm text-gray-500 dark:text-brand-muted">Target dan Realisasi</p>

        <?php if ( ! $daftar): ?>
          <p class="mt-4 rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-white/10 dark:text-brand-muted">
            Belum ada data.
          </p>
        <?php else: ?>
          <ul class="mt-4 divide-y divide-gray-100 dark:divide-white/5">
            <?php foreach ($daftar as $kode => $row): ?>
              <li class="py-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                  <div>
                    <p class="text-sm font-bold text-gray-900 dark:text-white"><?= $e($sumber_label[$kode] ?? $kode) ?></p>
                    <?php if ($row['keterangan'] !== ''): ?>
                      <p class="text-xs text-gray-500 dark:text-brand-muted"><?= $e($row['keterangan']) ?></p>
                    <?php endif; ?>
                    <dl class="mt-2 grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
                      <dt class="text-gray-500 dark:text-brand-muted">Rencana</dt>
                      <dt class="text-gray-500 dark:text-brand-muted">Realisasi</dt>
                      <dd class="text-gray-900 dark:text-white"><?= $rp($row['rencana_unit']) ?> unit<br>
                        <span class="text-xs text-gray-500 dark:text-brand-muted">Rp <?= $rp($row['rencana_anggaran']) ?></span></dd>
                      <dd class="text-gray-900 dark:text-white"><?= $rp($row['realisasi_unit']) ?> unit<br>
                        <span class="text-xs text-gray-500 dark:text-brand-muted">Rp <?= $rp($row['realisasi_anggaran']) ?></span></dd>
                    </dl>
                  </div>
                  <?php if ( ! $terkunci): ?>
                    <form method="post" action="<?= base_url('Rekam_Perumahan/hapus_sumber') ?>"
                          onsubmit="return confirm('Hapus <?= $e($sumber_label[$kode] ?? $kode) ?> dari program ini?')">
                      <input type="hidden" name="<?= $e($this->security->get_csrf_token_name()) ?>" value="<?= $e($this->security->get_csrf_hash()) ?>">
                      <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
                      <input type="hidden" name="program" value="<?= $e($program_aktif) ?>">
                      <input type="hidden" name="sumber_dana" value="<?= $e($kode) ?>">
                      <button class="text-xs font-bold text-red-600 hover:underline dark:text-red-400">Hapus</button>
                    </form>
                  <?php endif; ?>
                </div>

                <?php if ( ! $terkunci): ?>
                  <details class="mt-3">
                    <summary class="cursor-pointer text-sm font-bold text-blue-600 dark:text-brand-primary">Ubah</summary>
                    <div class="mt-3 rounded-xl border border-gray-200 p-4 dark:border-white/10">
                      <?php $form_sumber($program_aktif, $row); ?>
                    </div>
                  </details>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <?php if ( ! $terkunci): ?>
          <details class="mt-4" <?= $daftar ? '' : 'open' ?>>
            <summary class="cursor-pointer text-sm font-bold text-blue-600 dark:text-brand-primary">
              &plus; Tambah Sumber Dana
            </summary>
            <div class="mt-3 rounded-xl border border-gray-200 p-4 dark:border-white/10">
              <?php $form_sumber($program_aktif); ?>
            </div>
          </details>
        <?php endif; ?>
      </section>

      <section class="<?= $kotak ?>">
        <form method="post" action="<?= base_url('Rekam_Perumahan/langkah') ?>" class="flex flex-wrap gap-2">
          <input type="hidden" name="<?= $e($this->security->get_csrf_token_name()) ?>" value="<?= $e($this->security->get_csrf_hash()) ?>">
          <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
          <button name="langkah" value="program" class="<?= $tombol_lembut ?>">&larr; Program</button>
          <button name="langkah" value="bnba" class="<?= $tombol ?>">Lanjut &rarr;</button>
        </form>
      </section>
    <?php endif; ?>

  <?php // ================= L4 - BNBA (WAJIB sejak 5 Agt 2026) ================= ?>
  <?php elseif ($langkah === 'bnba'): ?>
    <section class="<?= $kotak ?>">
      <?php /* Kata "wajib" di sini HARUS sama dengan yang ditegakkan
               `Rekam_data_model::kirim()`. Sampai 5 Agt 2026 langkah ini
               berbunyi "opsional - boleh dilewati", dan membiarkannya berbunyi
               begitu sesudah servernya menolak berarti layar menjanjikan
               sesuatu yang lalu ditolak tanpa sebab yang terbaca. */ ?>
      <h2 class="text-lg font-black text-gray-900 dark:text-white">Unggah BNBA <span class="text-sm font-normal text-red-600 dark:text-red-400">(wajib)</span></h2>
      <p class="mt-1 text-sm text-gray-500 dark:text-brand-muted">
        Daftar penerima <i>by name by address</i>. <b class="text-gray-900 dark:text-white">Wajib dilampirkan
        sejak 5 Agustus 2026</b> - laporan tidak bisa dikirim tanpa berkas ini.
        Laporan yang sudah terkirim sebelum tanggal itu tetap sah.
      </p>

      <?php if ($bnba): ?>
        <p class="mt-4 rounded-xl border-l-4 border-emerald-500 bg-emerald-50 p-3 text-sm text-emerald-900 dark:bg-emerald-500/10 dark:text-emerald-200">
          Tersimpan:
          <a class="font-bold underline" href="<?= base_url('Rekam_Perumahan/unduh_bnba/' . $laporan_id) ?>"><?= $e($bnba['nama_asli']) ?></a>
          <span class="text-xs">(<?= $rp((int) ($bnba['ukuran'] / 1024)) ?> KB)</span>
        </p>
      <?php endif; ?>

      <?php if ( ! $terkunci): ?>
        <form method="post" action="<?= base_url('Rekam_Perumahan/unggah_bnba') ?>" enctype="multipart/form-data" class="mt-4 space-y-3">
          <input type="hidden" name="<?= $e($this->security->get_csrf_token_name()) ?>" value="<?= $e($this->security->get_csrf_hash()) ?>">
          <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
          <input type="file" name="bnba" accept=".pdf,.jpg,.jpeg,.png" class="<?= $isian ?>">
          <button class="<?= $tombol_lembut ?>"><?= $bnba ? 'Ganti berkas' : 'Unggah' ?></button>
        </form>
      <?php endif; ?>
    </section>

    <section class="<?= $kotak ?>">
      <form method="post" action="<?= base_url('Rekam_Perumahan/langkah') ?>" class="flex flex-wrap gap-2">
        <input type="hidden" name="<?= $e($this->security->get_csrf_token_name()) ?>" value="<?= $e($this->security->get_csrf_hash()) ?>">
        <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
        <button name="langkah" value="isian" class="<?= $tombol_lembut ?>">&larr; Isian</button>
        <button name="langkah" value="review" class="<?= $tombol ?>">Lanjut &rarr;</button>
      </form>
    </section>

  <?php // ================= L5 - REVIEW & KIRIM ================= ?>
  <?php else: ?>
    <section class="<?= $kotak ?>">
      <h2 class="text-lg font-black text-gray-900 dark:text-white">Review &amp; Kirim</h2>

      <?php // Catatan peninjau DIPINDAH ke tingkat halaman (spanduk di atas
            // stepper) supaya terbaca di setiap langkah. Sengaja tidak
            // digandakan di sini: dua salinan pesan yang sama pada satu layar
            // membuat pembacanya mengira ada dua catatan berbeda. ?>

      <ul class="mt-4 divide-y divide-gray-100 dark:divide-white/5">
        <?php foreach ($program_dipilih as $kode): ?>
          <?php $n = count($baris[$kode] ?? []); ?>
          <li class="flex items-center justify-between py-2.5 text-sm">
            <span class="font-bold text-gray-900 dark:text-white"><?= $e($program_label[$kode] ?? $kode) ?></span>
            <span class="<?= $n ? 'text-gray-500 dark:text-brand-muted' : 'font-bold text-red-600 dark:text-red-400' ?>">
              <?= $n ? $n . ' sumber dana' : 'belum ada sumber dana' ?>
            </span>
          </li>
        <?php endforeach; ?>
      </ul>

      <?php if ($program_kosong): ?>
        <p class="mt-4 rounded-xl border-l-4 border-red-500 bg-red-50 p-3 text-sm text-red-900 dark:bg-red-500/10 dark:text-red-200">
          <?= count($program_kosong) ?> program dicentang tetapi belum punya satu pun sumber dana.
          Lengkapi atau cabut centangnya sebelum mengirim.
        </p>
      <?php elseif ( ! $program_dipilih): ?>
        <p class="mt-4 rounded-xl border-l-4 border-red-500 bg-red-50 p-3 text-sm text-red-900 dark:bg-red-500/10 dark:text-red-200">
          Belum ada program yang dipilih untuk dilaporkan.
        </p>
      <?php endif; ?>
    </section>

    <section class="<?= $kotak ?>">
      <div class="flex flex-wrap gap-2">
        <form method="post" action="<?= base_url('Rekam_Perumahan/langkah') ?>">
          <input type="hidden" name="<?= $e($this->security->get_csrf_token_name()) ?>" value="<?= $e($this->security->get_csrf_hash()) ?>">
          <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
          <input type="hidden" name="langkah" value="bnba">
          <button class="<?= $tombol_lembut ?>">&larr; BNBA</button>
        </form>
        <?php if ( ! $terkunci): ?>
          <form method="post" action="<?= base_url('Rekam_Perumahan/kirim') ?>"
                onsubmit="return confirm('Kirim laporan ini? Setelah terkirim, laporan terkunci sampai peninjau mengembalikannya.')">
            <input type="hidden" name="<?= $e($this->security->get_csrf_token_name()) ?>" value="<?= $e($this->security->get_csrf_hash()) ?>">
            <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
            <button class="<?= $tombol ?>">Kirim Laporan</button>
          </form>
        <?php else: ?>
          <p class="text-sm text-gray-500 dark:text-brand-muted">
            Laporan sudah terkirim dan terkunci. Perubahan hanya bisa dilakukan setelah
            Admin Bidang mengembalikannya untuk diperbaiki.
          </p>
        <?php endif; ?>
      </div>
    </section>
  <?php endif; ?>

</div>
