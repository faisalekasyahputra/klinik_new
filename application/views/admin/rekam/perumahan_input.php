<?php
/**
 * Rekam Data — Input Capaian Perumahan (D2).
 *
 * Sepuluh sumber dana sebagai satu daftar yang bisa dilompati bebas, bukan 21
 * halaman berurutan seperti form Google-nya. Gerbang "Ada / Tidak Ada" tetap
 * ada karena datanya bermakna (membedakan nihil dari belum diisi), tapi tidak
 * lagi menentukan navigasi.
 *
 * Akordeon memakai <details> bawaan browser — tanpa JavaScript sama sekali.
 */
$e = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$csrf_name = $this->security->get_csrf_token_name();
$csrf_hash = $this->security->get_csrf_hash();

$laporan_id = (int) $laporan['id'];
$tahun      = (int) $laporan['tahun'];
$bulan      = (int) $laporan['bulan'];
$nama_bulan = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$terjawab = count($sumber_label) - count($belum_dijawab);
?>

<div class="space-y-4">

  <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-brand-card">
    <div class="flex flex-wrap items-center gap-3">
      <!-- Wilayah dari sesi. Sengaja BUKAN dropdown: scope-nya gerbang, bukan pilihan. -->
      <span class="rounded-lg bg-gray-100 px-3 py-2 text-sm dark:bg-black/20">
        Kabupaten/Kota <b class="text-gray-900 dark:text-white"><?= $e($scope_label) ?></b>
      </span>

      <form method="get" action="<?= base_url('Rekam_Perumahan') ?>" class="flex flex-wrap items-center gap-2">
        <label class="text-sm text-gray-500 dark:text-brand-muted" for="bulan">Kumulatif s.d.</label>
        <select id="bulan" name="bulan" class="rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm dark:border-white/10">
          <?php foreach ($nama_bulan as $n => $label): ?>
            <option value="<?= $n ?>" <?= $n === $bulan ? 'selected' : '' ?>><?= $e($label) ?></option>
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
      <!-- Catatan peninjau WAJIB terlihat di layar tempat perbaikannya
           dikerjakan. Menaruhnya hanya di riwayat berarti petugas harus
           menebak apa yang salah sambil menyunting. -->
      <p class="mt-4 rounded-r-xl border-l-4 border-red-500 bg-red-50 p-3 text-sm text-red-900 dark:bg-red-500/10 dark:text-red-200">
        <b>Perlu perbaikan — catatan Admin Bidang:</b> <?= $e($laporan['catatan_admin']) ?>
      </p>
    <?php endif; ?>

    <?php if ($diwarisi > 0): ?>
      <p class="mt-4 rounded-r-xl border-l-4 border-blue-500 bg-blue-50 p-3 text-sm text-blue-900 dark:bg-blue-500/10 dark:text-blue-200">
        <b><?= (int) $diwarisi ?> baris angka diwarisi</b> dari laporan terkirim sebelumnya.
        Ubah yang berubah saja — angkanya kumulatif, mengetik ulang dari nol membuat capaian menyusut.
      </p>
    <?php endif; ?>

    <p class="mt-4 text-sm font-bold text-gray-900 dark:text-white">
      <?= (int) $terjawab ?> dari <?= count($sumber_label) ?> sumber dana sudah dijawab
    </p>
    <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-black/30">
      <div class="h-full bg-blue-600 dark:bg-brand-primary" style="width: <?= (int) round($terjawab / max(1, count($sumber_label)) * 100) ?>%"></div>
    </div>
  </section>

  <?php if ($terkunci): ?>
    <p class="rounded-2xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 dark:border-white/10 dark:bg-black/20 dark:text-brand-muted">
      Laporan periode ini sudah <b>terkirim</b> dan terkunci. Perubahan hanya bisa dilakukan
      setelah Admin Bidang mengembalikannya untuk diperbaiki.
    </p>
  <?php endif; ?>

  <?php foreach ($sumber_label as $kode => $label):
      $ada    = array_key_exists($kode, $bagian) ? (int) $bagian[$kode] : NULL;
      $angka  = $baris[$kode] ?? [];
      $ket_label = $sumber_berketerangan[$kode] ?? NULL;
  ?>
    <details class="rounded-2xl border border-gray-200 bg-white dark:border-white/10 dark:bg-brand-card" <?= $ada === 1 ? 'open' : '' ?>>
      <!-- Gerbang Ada/Tidak Ada ada DI BARIS JUDUL, bukan di dalam badan
           akordeon. Menaruhnya di dalam memaksa petugas membuka sepuluh
           akordeon dulu sebelum bisa menjawab apa pun — 20 klik untuk
           pekerjaan 10 klik. Tombolnya ikut men-toggle <details>, dan itu tidak
           masalah: form-nya submit lalu halaman dimuat ulang. -->
      <summary class="flex cursor-pointer flex-wrap items-center gap-3 p-4">
        <span class="min-w-[180px] flex-1 font-bold text-gray-900 dark:text-white"><?= $e($label) ?></span>

        <?php if ($terkunci): ?>
          <?php if ($ada === 1): ?>
            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300">Ada</span>
          <?php elseif ($ada === 0): ?>
            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-600 dark:bg-white/5 dark:text-brand-muted">Tidak Ada</span>
          <?php else: ?>
            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">Belum dijawab</span>
          <?php endif; ?>
        <?php else: ?>
          <?php if ($ada === NULL): ?>
            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800 dark:bg-amber-500/10 dark:text-amber-300">Belum dijawab</span>
          <?php endif; ?>
          <form method="post" action="<?= base_url('Rekam_Perumahan/simpan_gerbang') ?>" class="flex items-center gap-1.5">
            <input type="hidden" name="<?= $e($csrf_name) ?>" value="<?= $e($csrf_hash) ?>">
            <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
            <input type="hidden" name="sumber_dana" value="<?= $e($kode) ?>">
            <button name="ada" value="1" class="rounded-lg px-3 py-1.5 text-xs font-bold <?= $ada === 1 ? 'bg-blue-600 text-white dark:bg-brand-primary dark:text-brand-dark' : 'border border-gray-200 dark:border-white/10' ?>">Ada</button>
            <button name="ada" value="0" class="rounded-lg px-3 py-1.5 text-xs font-bold <?= $ada === 0 ? 'bg-gray-500 text-white' : 'border border-gray-200 dark:border-white/10' ?>">Tidak Ada</button>
          </form>
        <?php endif; ?>
      </summary>

      <div class="border-t border-gray-200 p-4 dark:border-white/10">

        <?php if ($ada === 0): ?>
          <p class="mt-3 text-sm text-gray-500 dark:text-brand-muted">
            Dinyatakan nihil. Angka yang pernah diisi untuk sumber ini sudah tersapu.
          </p>

        <?php elseif ($ada === 1): ?>
          <form method="post" action="<?= base_url('Rekam_Perumahan/simpan_angka') ?>" class="mt-4">
            <input type="hidden" name="<?= $e($csrf_name) ?>" value="<?= $e($csrf_hash) ?>">
            <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
            <input type="hidden" name="sumber_dana" value="<?= $e($kode) ?>">

            <!-- Tabel lebar bergulir di dalam wadahnya sendiri; <body> tidak ikut bergulir. -->
            <div class="overflow-x-auto">
              <table class="w-full min-w-[560px] text-left text-sm">
                <thead class="text-xs uppercase text-gray-500 dark:text-brand-muted">
                  <tr>
                    <th class="py-2 pr-3">Program</th>
                    <th class="py-2 pr-3 w-24">Unit</th>
                    <th class="py-2 pr-3 w-44">Anggaran (Rp)</th>
                    <?php if ($ket_label): ?><th class="py-2 w-56"><?= $e($ket_label) ?></th><?php endif; ?>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                  <?php $sub_unit = 0; $sub_rp = 0; ?>
                  <?php foreach ($program_label as $pkode => $plabel):
                      $row = $angka[$pkode] ?? ['unit' => 0, 'anggaran' => 0, 'keterangan' => ''];
                      $sub_unit += (int) $row['unit'];
                      $sub_rp   += (int) $row['anggaran'];
                  ?>
                    <tr>
                      <td class="py-2 pr-3 font-medium text-gray-900 dark:text-white"><?= $e($plabel) ?></td>
                      <td class="py-2 pr-3">
                        <input type="number" min="0" step="1" inputmode="numeric"
                          name="program[<?= $e($pkode) ?>][unit]" value="<?= (int) $row['unit'] ?>"
                          aria-label="Unit <?= $e($plabel) ?>"
                          class="w-full rounded-lg border border-gray-200 bg-transparent px-2 py-1.5 text-right dark:border-white/10">
                      </td>
                      <td class="py-2 pr-3">
                        <input type="number" min="0" step="1" inputmode="numeric"
                          name="program[<?= $e($pkode) ?>][anggaran]" value="<?= (int) $row['anggaran'] ?>"
                          aria-label="Anggaran <?= $e($plabel) ?>"
                          class="w-full rounded-lg border border-gray-200 bg-transparent px-2 py-1.5 text-right dark:border-white/10">
                      </td>
                      <?php if ($ket_label): ?>
                        <td class="py-2">
                          <input type="text" maxlength="150"
                            name="program[<?= $e($pkode) ?>][keterangan]" value="<?= $e($row['keterangan'] ?? '') ?>"
                            aria-label="<?= $e($ket_label) ?> <?= $e($plabel) ?>"
                            class="w-full rounded-lg border border-gray-200 bg-transparent px-2 py-1.5 dark:border-white/10">
                        </td>
                      <?php endif; ?>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
                <tfoot>
                  <tr class="font-bold text-gray-900 dark:text-white">
                    <td class="pt-3">Subtotal</td>
                    <td class="pt-3 pr-3 text-right"><?= number_format($sub_unit, 0, ',', '.') ?></td>
                    <td class="pt-3 pr-3 text-right">Rp <?= number_format($sub_rp, 0, ',', '.') ?></td>
                    <?php if ($ket_label): ?><td></td><?php endif; ?>
                  </tr>
                </tfoot>
              </table>
            </div>

            <p class="mt-3 text-xs text-gray-500 dark:text-brand-muted">
              Isikan <b>0</b> bila tidak ada. Anggaran dalam rupiah penuh, tanpa titik —
              contoh <code>3000000000</code>. Subtotal dihitung, tidak diketik.
            </p>

            <?php if ( ! $terkunci): ?>
              <button class="mt-3 rounded-xl bg-blue-600 px-4 py-2 text-sm font-bold text-white transition-colors hover:bg-blue-700 dark:bg-brand-primary dark:text-brand-dark dark:hover:bg-brand-hover">
                Simpan <?= $e($label) ?>
              </button>
            <?php endif; ?>
          </form>

        <?php else: ?>
          <p class="mt-3 text-sm text-gray-500 dark:text-brand-muted">
            Pilih <b>Ada</b> atau <b>Tidak Ada</b> dulu. Selama belum dijawab, sumber dana ini
            terhitung "belum diisi" — berbeda dari "nihil".
          </p>
        <?php endif; ?>

      </div>
    </details>
  <?php endforeach; ?>

  <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-brand-card">
    <div class="flex flex-wrap items-center gap-3">
      <h2 class="font-bold text-gray-900 dark:text-white">BNBA — By Name By Address</h2>
      <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-bold text-gray-600 dark:bg-white/5 dark:text-brand-muted">Opsional</span>
    </div>

    <?php if ($bnba): ?>
      <p class="mt-3 text-sm text-gray-700 dark:text-brand-muted">
        <a class="font-bold text-blue-600 hover:underline dark:text-blue-400"
           href="<?= base_url('Rekam_Perumahan/unduh_bnba/' . $laporan_id) ?>"><?= $e($bnba['nama_asli']) ?></a>
        · <?= number_format((int) $bnba['ukuran'] / 1024, 0, ',', '.') ?> KB
        · diunggah <?= $e($bnba['uploaded_at']) ?>
      </p>
    <?php else: ?>
      <p class="mt-3 rounded-xl border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500 dark:border-white/10 dark:text-brand-muted">
        Belum ada berkas diunggah.
      </p>
    <?php endif; ?>

    <?php if ( ! $terkunci): ?>
      <form method="post" enctype="multipart/form-data"
            action="<?= base_url('Rekam_Perumahan/unggah_bnba') ?>"
            class="mt-3 flex flex-wrap items-center gap-2">
        <input type="hidden" name="<?= $e($csrf_name) ?>" value="<?= $e($csrf_hash) ?>">
        <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
        <input type="file" name="bnba" accept=".pdf,.jpg,.jpeg,.png"
               class="text-sm text-gray-600 dark:text-brand-muted">
        <button class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-bold dark:border-white/10">
          <?= $bnba ? 'Ganti berkas' : 'Unggah' ?>
        </button>
      </form>
      <p class="mt-2 text-xs text-gray-500 dark:text-brand-muted">
        PDF, JPG, atau PNG. Berkas disimpan di penyimpanan privat dan hanya bisa dibuka
        dari wilayah ini. Mengunggah ulang <b>mengganti</b> berkas sebelumnya.
      </p>
    <?php endif; ?>
  </section>

  <?php if ( ! $terkunci): ?>
    <!-- SENGAJA tidak sticky. Sebagai bar melayang ia menutupi blok BNBA di
         desktop dan memakan seperempat layar ponsel (teksnya membungkus jadi
         empat baris) — konten di bawahnya jadi tidak terjangkau. Halaman ini
         punya penunjuk progres di kepala, jadi tombol Kirim tidak perlu
         mengikuti gulir. -->
    <section class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-brand-card">
      <form method="post" action="<?= base_url('Rekam_Perumahan/kirim') ?>"
            class="flex flex-wrap items-center gap-3"
            onsubmit="return confirm('Kirim laporan periode ini? Setelah terkirim, laporan terkunci dan hanya Admin Bidang yang bisa membukanya kembali.')">
        <input type="hidden" name="<?= $e($csrf_name) ?>" value="<?= $e($csrf_hash) ?>">
        <input type="hidden" name="laporan_id" value="<?= $laporan_id ?>">
        <span class="flex-1 text-sm text-gray-500 dark:text-brand-muted">
          <?php if ($belum_dijawab): ?>
            Kirim terkunci: <b><?= count($belum_dijawab) ?> sumber dana</b> belum dijawab Ada/Tidak Ada.
          <?php else: ?>
            Sepuluh sumber dana sudah dijawab. Laporan siap dikirim.
          <?php endif; ?>
        </span>
        <button <?= $belum_dijawab ? 'disabled' : '' ?>
          class="rounded-xl px-4 py-2 text-sm font-bold <?= $belum_dijawab
            ? 'cursor-not-allowed bg-gray-200 text-gray-500 dark:bg-white/5 dark:text-brand-muted'
            : 'bg-blue-600 text-white dark:bg-brand-primary dark:text-brand-dark' ?>">
          Kirim laporan
        </button>
      </form>
    </section>
  <?php endif; ?>
</div>
