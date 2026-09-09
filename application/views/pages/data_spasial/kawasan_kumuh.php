<?php
/**
 * Daftar kawasan kumuh per tahun, sumber API Sikaper.
 *
 * Nol data pribadi di layar ini - hanya nama kawasan, kabupaten, dan skor.
 * Batas itu disengaja, lihat docblock Kawasan_kumuh.php.
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
    <?php elseif ( ! $baris): ?>
      <div class="rounded-2xl border p-6" style="border-color:var(--portal-border)">
        <p class="font-black" style="color:var(--portal-text)">Tidak ada kawasan pada pilihan ini</p>
        <p class="mt-2 text-sm" style="color:var(--portal-text-muted)">Coba tahun atau wilayah lain.</p>
      </div>
    <?php else: ?>

      <p class="mb-3 text-xs font-bold" style="color:var(--portal-text-muted)">
        <?= count($baris) ?> kawasan ditampilkan, tahun <?= (int) $tahun ?>.
      </p>

      <div class="overflow-x-auto rounded-2xl border" style="border-color:var(--portal-border)">
        <table class="w-full min-w-[720px] text-left text-sm">
          <thead>
            <tr class="text-[11px] uppercase tracking-wider" style="color:var(--portal-text-muted)">
              <th class="px-4 py-3 font-bold">Kawasan</th>
              <th class="px-4 py-3 font-bold">Kabupaten/Kota</th>
              <th class="px-4 py-3 font-bold text-right">Skor awal</th>
              <th class="px-4 py-3 font-bold text-right">Skor akhir</th>
              <th class="px-4 py-3 font-bold">Kondisi</th>
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

    <?php endif; ?>

    <p class="mt-6 text-[11px]" style="color:var(--portal-text-muted)">
      Sumber: SIKAPER Disperakim Provinsi Jawa Tengah. Data disalin berkala; angka pada halaman ini mengikuti
      salinan terakhir yang berhasil diambil.
    </p>
  </div>
</section>
