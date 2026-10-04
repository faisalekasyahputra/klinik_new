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
    if ($s <= 15) { return ['Kumuh ringan',  'color:var(--portal-status-aman)']; }
    if ($s <= 44) { return ['Kumuh sedang',  'color:var(--portal-status-waspada)']; }
    return ['Kumuh berat', 'color:var(--portal-status-bahaya)'];
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
    return '<th class="px-3 py-2.5 font-bold ' . $kelas . '"' . $aria . '>'
         . '<a href="' . $e($url(['urut' => $kunci, 'arah' => $arah_baru, 'hal' => 1])) . '"'
         . ' class="inline-flex items-center gap-1 hover:underline"'
         . ' style="color:' . ($aktif ? 'var(--portal-brand)' : 'var(--portal-text-muted)') . '">'
         . $e($label) . '<span aria-hidden="true">' . $panah . '</span></a></th>';
};

/* Tata letak mengikuti tabel Sertifikasi Pengembang (permintaan user 4 Okt 2026): SATU kartu
   berbayang berisi kepala (judul, jumlah, penyaring), tabel rapat, dan kaki (ringkasan, per
   halaman, navigasi). Semua tombol satu gaya: latar teal brand, teks --portal-btn-text;
   halaman aktif ditandai cincin, tombol mati memudar. */
$gaya_tombol = 'background:var(--portal-brand);color:var(--portal-btn-text);border:1px solid var(--portal-brand)';
$gaya_aktif  = $gaya_tombol . ';box-shadow:0 0 0 2px var(--portal-bg-card),0 0 0 3px var(--portal-brand)';
$gaya_mati   = $gaya_tombol . ';opacity:.4;pointer-events:none';
$gaya_kartu  = 'background:var(--portal-bg-card);border:1px solid var(--portal-border);box-shadow:0 8px 24px rgba(0,80,95,.06)';
$gaya_isian  = 'background:var(--portal-bg);border:1px solid var(--portal-border);color:var(--portal-text)';
?>
<section class="w-full px-4 py-8 font-outfit sm:px-6 lg:px-8" style="color:var(--portal-text)">
  <div class="mx-auto max-w-6xl">

    <header class="mb-5">
      <p class="text-[11px] font-black uppercase tracking-wider" style="color:var(--portal-brand)">Bank Data</p>
      <h1 class="mt-1 text-2xl font-black tracking-tight sm:text-3xl" style="color:var(--portal-text)">Data Kawasan Kumuh Jawa Tengah</h1>
      <p class="mt-2 max-w-2xl text-xs leading-relaxed" style="color:var(--portal-text-muted)">
        Bersumber langsung dari SIKAPER Dinas Perumahan Rakyat dan Kawasan Permukiman Provinsi Jawa Tengah.
        Skor kumuh mengikuti penilaian dinas: makin tinggi skornya, makin berat kondisinya.
      </p>
    </header>

    <div class="overflow-hidden rounded-2xl" style="<?= $gaya_kartu ?>">
      <div class="flex flex-col gap-3 border-b px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5" style="border-color:var(--portal-border)">
        <div>
          <h2 class="text-sm font-extrabold" style="color:var(--portal-text)">Daftar Kawasan Kumuh</h2>
          <p class="mt-0.5 text-[10px]" style="color:var(--portal-text-muted)"><?= (int) $total ?> kawasan &middot; tahun <?= (int) $tahun ?></p>
        </div>
        <form method="get" action="<?= base_url('kawasan_kumuh') ?>" class="flex flex-wrap items-center gap-2">
          <input type="hidden" name="urut" value="<?= $e($urut) ?>">
          <input type="hidden" name="arah" value="<?= $e($arah) ?>">
          <input type="hidden" name="per" value="<?= (int) $per ?>">
          <label class="sr-only" for="tahun">Tahun</label>
          <select id="tahun" name="tahun" class="rounded-lg px-2.5 py-2 text-[11px] font-bold outline-none" style="<?= $gaya_isian ?>">
            <?php foreach ($tahun_tersedia as $t): ?>
              <option value="<?= (int) $t ?>" <?= (int) $t === (int) $tahun ? 'selected' : '' ?>><?= (int) $t ?></option>
            <?php endforeach; ?>
          </select>
          <label class="sr-only" for="kab">Kabupaten/Kota</label>
          <select id="kab" name="kab" class="rounded-lg px-2.5 py-2 text-[11px] font-bold outline-none" style="<?= $gaya_isian ?>">
            <option value="">Semua wilayah (<?= count($daftar_kab) ?>)</option>
            <?php foreach ($daftar_kab as $kode => $info): ?>
              <option value="<?= $e($kode) ?>" <?= (string) $kode === (string) $kab_terpilih ? 'selected' : '' ?>>
                <?= $e($info['nama']) ?> (<?= (int) $info['jumlah'] ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <button class="rounded-lg px-3.5 py-2 text-[11px] font-bold" style="<?= $gaya_tombol ?>">Tampilkan</button>
        </form>
      </div>

      <?php if ($gagal): ?>
        <div class="px-4 py-8 text-center sm:px-5">
          <p class="text-sm font-black" style="color:var(--portal-text)">Data belum bisa ditampilkan</p>
          <p class="mt-1 text-xs" style="color:var(--portal-text-muted)">
            Sumber data SIKAPER sedang tidak dapat dihubungi dan belum ada salinan tersimpan untuk tahun ini.
            Silakan coba lagi beberapa saat lagi.
          </p>
        </div>
      <?php elseif ($total === 0): ?>
        <div class="px-4 py-8 text-center sm:px-5">
          <p class="text-sm font-black" style="color:var(--portal-text)">Tidak ada kawasan pada pilihan ini</p>
          <p class="mt-1 text-xs" style="color:var(--portal-text-muted)">Coba tahun atau wilayah lain.</p>
        </div>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="w-full min-w-[760px] text-left text-xs">
            <thead style="background:var(--portal-bg)">
              <tr class="uppercase tracking-wider" style="color:var(--portal-text-muted);font-size:9px">
                <th class="w-12 px-4 py-2.5 font-bold">No.</th>
                <?= $kepala('kawasan', 'Kawasan') ?>
                <?= $kepala('kabupaten', 'Kabupaten/Kota') ?>
                <?= $kepala('skor_awal', 'Skor awal', 'text-right') ?>
                <?= $kepala('skor_akhir', 'Skor akhir', 'text-right') ?>
                <?= $kepala('kondisi', 'Kondisi') ?>
                <th class="px-4 py-2.5 text-right font-bold">Detail</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach (array_values($baris) as $i => $b): ?>
                <?php list($label_kondisi, $gaya_kondisi) = $kategori($b['skor_kumuh_akhir'] ?? 0); ?>
                <tr data-baris-kawasan class="border-t transition-colors hover:bg-[#00a3b5]/[.04]" style="border-color:var(--portal-border)">
                  <td class="px-4 py-2.5 font-bold" style="color:var(--portal-brand)"><?= (int) $mulai + $i ?></td>
                  <td data-sel-kawasan class="px-3 py-2.5 font-semibold" style="color:var(--portal-text)"><?= $e($b['nama_kawasan'] ?? '-') ?></td>
                  <td class="px-3 py-2.5" style="color:var(--portal-text-muted)"><?= $e($b['nama_kab'] ?? '-') ?></td>
                  <td class="px-3 py-2.5 text-right" style="color:var(--portal-text-muted)"><?= (int) ($b['skor_kumuh_awal'] ?? 0) ?></td>
                  <td class="px-3 py-2.5 text-right font-bold" style="color:var(--portal-text)"><?= (int) ($b['skor_kumuh_akhir'] ?? 0) ?></td>
                  <td class="px-3 py-2.5">
                    <span class="inline-flex items-center gap-1.5 text-[10px] font-bold" style="<?= $gaya_kondisi ?>"><span class="h-1.5 w-1.5 rounded-full" style="background:currentColor"></span><?= $e($label_kondisi) ?></span>
                  </td>
                  <td class="px-4 py-2.5 text-right">
                    <?php if ( ! empty($b['id'])): ?>
                      <a class="inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 text-[10px] font-bold" style="<?= $gaya_tombol ?>"
                         href="<?= base_url('kawasan_kumuh/detail/' . rawurlencode($b['id'])) ?>">Detail</a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php
          /* Jendela nomor: halaman 1, terakhir, dan sekitar halaman aktif. Cukup untuk 66 halaman
             (655 / 10) tanpa deretan angka yang memenuhi layar ponsel. */
          $nomor = [];
          foreach ([1, $hal - 1, $hal, $hal + 1, $jumlah_hal] as $n) {
            if ($n >= 1 && $n <= $jumlah_hal) { $nomor[$n] = TRUE; }
          }
          $nomor = array_keys($nomor);
          sort($nomor);
        ?>
        <div class="flex flex-col gap-3 border-t px-4 py-3 sm:flex-row sm:items-center sm:justify-between" style="border-color:var(--portal-border)">
          <p class="text-[10px]" style="color:var(--portal-text-muted)">
            Menampilkan <?= (int) $mulai ?>&ndash;<?= (int) $sampai ?> dari <?= (int) $total ?> kawasan, halaman <?= (int) $hal ?> dari <?= (int) $jumlah_hal ?>
          </p>
          <div class="flex flex-wrap items-center gap-3 sm:justify-end">
            <?php /* Per halaman sebagai tautan, bukan select + JS: halaman ini sengaja berfungsi utuh tanpa JS. */ ?>
            <span class="flex items-center gap-1 text-[10px]" style="color:var(--portal-text-muted)">Tampil
              <?php foreach ($per_pilihan as $p): ?>
                <a href="<?= $e($url(['per' => $p, 'hal' => 1])) ?>" class="rounded-md px-1.5 py-1 text-[10px] font-bold"
                   style="<?= (int) $p === (int) $per ? $gaya_tombol : $gaya_isian ?>" <?= (int) $p === (int) $per ? 'aria-current="true"' : '' ?>><?= (int) $p ?></a>
              <?php endforeach; ?>
            </span>
            <?php if ($jumlah_hal > 1): ?>
            <nav class="flex items-center gap-1" aria-label="Navigasi halaman">
              <a href="<?= $e($url(['hal' => max(1, $hal - 1)])) ?>" class="rounded-md px-2 py-1 text-[10px] font-bold"
                 style="<?= $hal <= 1 ? $gaya_mati : $gaya_tombol ?>" <?= $hal <= 1 ? 'aria-disabled="true"' : '' ?> aria-label="Halaman sebelumnya">&lsaquo;</a>
              <?php $sebelumnya = 0; foreach ($nomor as $n): ?>
                <?php if ($n - $sebelumnya > 1): ?>
                  <span class="px-1 text-[10px]" style="color:var(--portal-text-muted)">&hellip;</span>
                <?php endif; ?>
                <a href="<?= $e($url(['hal' => $n])) ?>" class="rounded-md px-2 py-1 text-[10px] font-bold"
                   style="<?= $n === $hal ? $gaya_aktif : $gaya_tombol ?>" <?= $n === $hal ? 'aria-current="page"' : '' ?>><?= (int) $n ?></a>
                <?php $sebelumnya = $n; ?>
              <?php endforeach; ?>
              <a href="<?= $e($url(['hal' => min($jumlah_hal, $hal + 1)])) ?>" class="rounded-md px-2 py-1 text-[10px] font-bold"
                 style="<?= $hal >= $jumlah_hal ? $gaya_mati : $gaya_tombol ?>" <?= $hal >= $jumlah_hal ? 'aria-disabled="true"' : '' ?> aria-label="Halaman berikutnya">&rsaquo;</a>
            </nav>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <p class="mt-4 text-[10px]" style="color:var(--portal-text-muted)">
      Sumber: SIKAPER Disperakim Provinsi Jawa Tengah. Data disalin berkala; angka pada halaman ini mengikuti
      salinan terakhir yang berhasil diambil.
    </p>
  </div>
</section>
