<?php
/**
 * Rekam Data — pintu masuk modul ("BANK Data" di sketsa Menu Utama).
 *
 * Sengaja TANPA angka. Pintu yang memajang ringkasan harus menarik data, dan
 * data yang ditarik di pintu masuk selalu berumur beberapa detik lebih tua
 * dari halaman yang dituju — dua angka berbeda untuk hal yang sama. Rekap
 * sudah punya layarnya sendiri; ini cuma pengarah.
 */
$e = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');

$domain = [
    [
        'url'   => 'Rekam_Perumahan',
        'judul' => 'Capaian Perumahan',
        'ikon'  => 'ph-house-line',
        'isi'   => 'Sepuluh sumber dana × enam program penanganan RTLH, backlog, bencana, dan relokasi. Termasuk unggah BNBA.',
    ],
    [
        'url'   => 'Rekam_Kawasan',
        'judul' => 'Capaian Kawasan',
        'ikon'  => 'ph-map-trifold',
        'isi'   => 'Tujuh indikator kawasan permukiman dan daftar intervensinya, beserta ringkasan luas dan padat karya.',
    ],
];
?>

<div class="space-y-4">

  <section class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-brand-card">
    <div class="flex flex-wrap items-center gap-3">
      <span class="rounded-lg bg-gray-100 px-3 py-2 text-sm dark:bg-black/20">
        Kabupaten/Kota <b class="text-gray-900 dark:text-white"><?= $e($scope_label) ?></b>
      </span>
      <span class="ml-auto text-xs text-gray-500 dark:text-brand-muted">Pilih buku data</span>
    </div>
    <p class="mt-3 text-sm text-gray-500 dark:text-brand-muted">
      Wilayah diambil dari akunmu, bukan dari pilihan di layar — kamu hanya bisa
      merekam capaian wilayah sendiri.
    </p>
  </section>

  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <?php foreach ($domain as $d): ?>
      <a href="<?= base_url($d['url']) ?>"
         class="group flex flex-col rounded-2xl border border-gray-200 bg-white p-5 transition hover:border-brand-primary hover:shadow-lg dark:border-white/10 dark:bg-brand-card dark:hover:border-brand-primary">
        <i class="ph <?= $e($d['ikon']) ?> text-3xl text-brand-primary"></i>
        <h2 class="mt-3 text-lg font-bold text-gray-900 dark:text-white"><?= $e($d['judul']) ?></h2>
        <p class="mt-1 flex-1 text-sm text-gray-500 dark:text-brand-muted"><?= $e($d['isi']) ?></p>
        <span class="mt-4 inline-flex items-center gap-1 text-sm font-bold text-brand-primary">
          Buka <i class="ph ph-arrow-right transition group-hover:translate-x-1"></i>
        </span>
      </a>
    <?php endforeach; ?>
  </div>

</div>
