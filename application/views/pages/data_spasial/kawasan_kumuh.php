<?php
/**
 * Daftar kawasan kumuh per tahun, sumber API Sikaper.
 *
 * Nol data pribadi di layar ini - hanya nama kawasan, kabupaten, dan skor.
 * Batas itu disengaja, lihat docblock Kawasan_kumuh.php.
 *
 * Urut dan halaman sepenuhnya lewat URL (parameter `urut`, `arah`, `hal`,
 * `per`), bukan JavaScript: seluruh baris satu tahun sudah ada di memori
 * server dari cache, jadi memotong dan mengurutkannya gratis, dan URL-nya
 * membawa keadaan penuh sehingga bisa dibagikan apa adanya. Tanpa JS pun
 * halaman ini tetap berfungsi utuh.
 */
$e = function ($v) { return html_escape((string) $v); };

/* Ambang warna diambil dari kategori Sikaper sendiri (situs dinas memakai
   TIDAK KUMUH / KUMUH RINGAN / SEDANG / BERAT), bukan dikarang di sini.
   Skor TINGGI = kondisi lebih buruk. */
$kategori = function ($skor) {
    $s = (int) $skor;
    if ($s <= 0)  { return ['Belum dinilai', 'color:var(--portal-text-muted)']; }
    if ($s <= 15) { return ['Kumuh ringan',  'color:#047857']; }
    if ($s <= 44) { return ['Kumuh sedang',  'color:#b45309']; }
    return ['Kumuh berat', 'color:#b91c1c'];
};

/* Satu pembangun URL untuk semua tautan urut & halaman. Ia MEMPERTAHANKAN
   parameter lain (tahun, kabupaten, per) dan hanya menimpa yang diminta -
   kalau tidak, mengklik kepala kolom akan diam-diam membuang penyaring
   kabupaten yang baru saja dipilih. */
$dasar = ['tahun' => $tahun, 'kab' => $kab_terpilih, 'urut' => $urut, 'arah' => $arah, 'per' => $per, 'hal' => $hal];
$url = function (array $ubah) use ($dasar) {
    $q = array_filter(array_merge($dasar, $ubah), function ($v) { return $v !== '' && $v !== NULL; });
    return base_url('kawasan_kumuh') . ($q ? '?' . http_build_query($q) : '');
};

/* Kepala kolom: klik = urut menaik; klik lagi pada kolom yang sama =
   membalik arah. Halaman selalu kembali ke 1 saat urutan berubah, karena
   halaman 12 dari urutan lama tidak punya arti pada urutan baru. */
$kepala = function ($kunci, $label, $kelas = '') use ($url, $urut, $arah, $e) {
    $aktif = $urut === $kunci;
    $arah_baru = ($aktif && $arah === 'asc') ? 'desc' : 'asc';
    $panah = $aktif ? ($arah === 'asc' ? '&#9650;' : '&#9660;') : '&#8693;';
    $aria = $aktif ? ' aria-sort="' . ($arah === 'asc' ? 'ascending' : 'descending') . '"' : '';
    return '<th class="px-4 py-3 font-bold ' . $kelas . '"' . $aria . '>'
         . '<a href="' . $e($url(['urut' => $kunci, 'arah' => $arah_baru, 'hal' => 1])) . '"'
         . ' class="inline-flex items-center gap-1.5 hover:underline"'
         . ' style="color:' . ($aktif ? 'var(--portal-brand)' : 'var(--portal-text-muted)') . '">'
         . $e($label) . '<span aria-hidden="true" class="text-[10px]">' . $panah . '</span></a></th>';
};

/* Tombol halaman memakai warna yang SAMA dengan tombol Tampilkan di atas:
   aktif = latar brand, sisanya = latar tombol biasa dengan garis tepi tema. */
$gaya_aktif  = 'background:var(--portal-brand);color:var(--portal-btn-text);border:1px solid var(--portal-brand)';
$gaya_biasa  = 'background:var(--portal-btn-bg);color:var(--portal-text);border:1px solid var(--portal-border)';
$gaya_mati   = 'background:var(--portal-btn-bg);color:var(--portal-text-muted);border:1px solid var(--portal-border);opacity:.5;pointer-events:none';
?>
<section class="w-full pt-24 pb-16 px-4 sm:px-6 lg:px-8 min-h-screen font-outfit">
  <div class="mx-auto max-w-6xl">

    <header class="mb-6">
      <p class="text-[11px] font-black uppercase tracking-wider" style="color:var(--portal-brand)">Bank Data</p>
      <h1 class="mt-1 text-2xl font-black sm:text-3xl" style="color:var(--portal-text)">Data Kawasan Kumuh Jawa Tengah</h1>
      <p class="mt-2 max-w-3xl text-sm" style="color:var(--portal-text-muted)">
        Bersumber langsung dari SIKAPER Dinas Perumahan Rakyat dan Kawasan Permukiman Provinsi Jawa Tengah.
        Skor kumuh mengikuti penilaian dinas: makin tinggi skornya, makin berat kondisinya.
      </p>
    </header>

    <form method="get" action="<?= base_url('kawasan_kumuh') ?>" class="mb-6 flex flex-wrap items-end gap-3">
      <input type="hidden" name="urut" value="<?= $e($urut) ?>">
      <input type="hidden" name="arah" value="<?= $e($arah) ?>">
      <div>
        <label for="tahun" class="text-xs font-bold" style="color:var(--portal-text)">Tahun</label>
        <select id="tahun" name="tahun" class="mt-1 block rounded-xl border px-3 py-2.5 text-sm"
                style="background:var(--portal-btn-bg);border-color:var(--portal-border);color:var(--portal-text)">
          <?php foreach ($tahun_tersedia as $t): ?>
            <option value="<?= (int) $t ?>" <?= (int) $t === (int) $tahun ? 'selected' : '' ?>><?= (int) $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="kab" class="text-xs font-bold" style="color:var(--portal-text)">Kabupaten/Kota</label>
        <select id="kab" name="kab" class="mt-1 block rounded-xl border px-3 py-2.5 text-sm"
                style="background:var(--portal-btn-bg);border-color:var(--portal-border);color:var(--portal-text)">
          <option value="">Semua (<?= count($daftar_kab) ?> wilayah)</option>
          <?php foreach ($daftar_kab as $kode => $info): ?>
            <option value="<?= $e($kode) ?>" <?= (string) $kode === (string) $kab_terpilih ? 'selected' : '' ?>>
              <?= $e($info['nama']) ?> (<?= (int) $info['jumlah'] ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label for="per" class="text-xs font-bold" style="color:var(--portal-text)">Per halaman</label>
        <select id="per" name="per" class="mt-1 block rounded-xl border px-3 py-2.5 text-sm"
                style="background:var(--portal-btn-bg);border-color:var(--portal-border);color:var(--portal-text)">
          <?php foreach ($per_pilihan as $p): ?>
            <option value="<?= (int) $p ?>" <?= (int) $p === (int) $per ? 'selected' : '' ?>><?= (int) $p ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <button class="rounded-xl px-4 py-2.5 text-sm font-bold"
              style="background:var(--portal-brand);color:var(--portal-btn-text)">Tampilkan</button>
    </form>

    <?php if ($gagal): ?>
      <div class="rounded-2xl border p-6" style="border-color:var(--portal-border)">
        <p class="font-black" style="color:var(--portal-text)">Data belum bisa ditampilkan</p>
        <p class="mt-2 text-sm" style="color:var(--portal-text-muted)">
          Sumber data SIKAPER sedang tidak dapat dihubungi dan belum ada salinan tersimpan untuk tahun ini.
          Silakan coba lagi beberapa saat lagi.
        </p>
      </div>
    <?php elseif ($total === 0): ?>
      <div class="rounded-2xl border p-6" style="border-color:var(--portal-border)">
        <p class="font-black" style="color:var(--portal-text)">Tidak ada kawasan pada pilihan ini</p>
        <p class="mt-2 text-sm" style="color:var(--portal-text-muted)">Coba tahun atau wilayah lain.</p>
      </div>
    <?php else: ?>

      <p class="mb-3 text-xs font-bold" style="color:var(--portal-text-muted)">
        <?= (int) $total ?> kawasan ditampilkan, tahun <?= (int) $tahun ?>.
        Menampilkan <?= (int) $mulai ?>&ndash;<?= (int) $sampai ?>, halaman <?= (int) $hal ?> dari <?= (int) $jumlah_hal ?>.
      </p>

      <div class="overflow-x-auto rounded-2xl border" style="border-color:var(--portal-border)">
        <table class="w-full min-w-[720px] text-left text-sm">
          <thead>
            <tr class="text-[11px] uppercase tracking-wider">
              <?= $kepala('kawasan', 'Kawasan') ?>
              <?= $kepala('kabupaten', 'Kabupaten/Kota') ?>
              <?= $kepala('skor_awal', 'Skor awal', 'text-right') ?>
              <?= $kepala('skor_akhir', 'Skor akhir', 'text-right') ?>
              <?= $kepala('kondisi', 'Kondisi') ?>
              <th class="px-4 py-3 font-bold"></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($baris as $b): ?>
              <?php list($label_kondisi, $gaya_kondisi) = $kategori($b['skor_kumuh_akhir'] ?? 0); ?>
              <tr class="border-t" style="border-color:var(--portal-border)">
                <td class="px-4 py-3 font-bold" style="color:var(--portal-text)"><?= $e($b['nama_kawasan'] ?? '-') ?></td>
                <td class="px-4 py-3" style="color:var(--portal-text-muted)"><?= $e($b['nama_kab'] ?? '-') ?></td>
                <td class="px-4 py-3 text-right" style="color:var(--portal-text-muted)"><?= (int) ($b['skor_kumuh_awal'] ?? 0) ?></td>
                <td class="px-4 py-3 text-right font-bold" style="color:var(--portal-text)"><?= (int) ($b['skor_kumuh_akhir'] ?? 0) ?></td>
                <td class="px-4 py-3 font-bold" style="<?= $gaya_kondisi ?>"><?= $e($label_kondisi) ?></td>
                <td class="px-4 py-3">
                  <?php if ( ! empty($b['id'])): ?>
                    <a class="text-xs font-bold underline" style="color:var(--portal-brand)"
                       href="<?= base_url('kawasan_kumuh/detail/' . rawurlencode($b['id'])) ?>">Detail</a>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if ($jumlah_hal > 1): ?>
        <?php
          /* Jendela nomor: halaman 1, terakhir, dan sekitar halaman aktif.
             Cukup untuk 27 halaman (655 / 25) tanpa deretan angka yang
             memenuhi layar ponsel. */
          $nomor = [];
          foreach ([1, $hal - 2, $hal - 1, $hal, $hal + 1, $hal + 2, $jumlah_hal] as $n) {
            if ($n >= 1 && $n <= $jumlah_hal) { $nomor[$n] = TRUE; }
          }
          $nomor = array_keys($nomor);
          sort($nomor);
        ?>
        <nav class="mt-4 flex flex-wrap items-center gap-1.5" aria-label="Navigasi halaman">
          <a href="<?= $e($url(['hal' => max(1, $hal - 1)])) ?>" class="rounded-xl px-3 py-2 text-xs font-bold"
             style="<?= $hal <= 1 ? $gaya_mati : $gaya_biasa ?>" <?= $hal <= 1 ? 'aria-disabled="true"' : '' ?>>&laquo; Sebelumnya</a>

          <?php $sebelumnya = 0; foreach ($nomor as $n): ?>
            <?php if ($n - $sebelumnya > 1): ?>
              <span class="px-1 text-xs" style="color:var(--portal-text-muted)">&hellip;</span>
            <?php endif; ?>
            <a href="<?= $e($url(['hal' => $n])) ?>" class="rounded-xl px-3 py-2 text-xs font-bold"
               style="<?= $n === $hal ? $gaya_aktif : $gaya_biasa ?>" <?= $n === $hal ? 'aria-current="page"' : '' ?>><?= (int) $n ?></a>
            <?php $sebelumnya = $n; ?>
          <?php endforeach; ?>

          <a href="<?= $e($url(['hal' => min($jumlah_hal, $hal + 1)])) ?>" class="rounded-xl px-3 py-2 text-xs font-bold"
             style="<?= $hal >= $jumlah_hal ? $gaya_mati : $gaya_biasa ?>" <?= $hal >= $jumlah_hal ? 'aria-disabled="true"' : '' ?>>Berikutnya &raquo;</a>
        </nav>
      <?php endif; ?>

    <?php endif; ?>

    <p class="mt-6 text-[11px]" style="color:var(--portal-text-muted)">
      Sumber: SIKAPER Disperakim Provinsi Jawa Tengah. Data disalin berkala; angka pada halaman ini mengikuti
      salinan terakhir yang berhasil diambil.
    </p>
  </div>
</section>
