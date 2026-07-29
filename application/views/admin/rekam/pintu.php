<?php
/**
 * Rekam Data — pintu masuk modul ("BANK Data" di sketsa Menu Utama).
 *
 * Sengaja TANPA angka. Pintu yang memajang ringkasan harus menarik data, dan
 * data yang ditarik di pintu masuk selalu berumur beberapa detik lebih tua
 * dari halaman yang dituju — dua angka berbeda untuk hal yang sama. Rekap
 * sudah punya layarnya sendiri; ini cuma pengarah.
 *
 * Gaya mengikuti anatomi kartu `admin/dashboard.php`, bukan karangan sendiri:
 * aksen biru di mode terang dan `brand-primary` di mode gelap. `brand-primary`
 * (#d6fb00, lime) hanya boleh jadi WARNA TEKS di balik `dark:` — sebagai teks
 * di atas latar putih ia praktis tidak terbaca. Tanpa `dark:` ia hanya dipakai
 * sebagai LATAR tombol terisi, dipasangkan `text-brand-dark`, seperti di
 * perumahan_input.php dan perumahan_rekap.php.
 */
$e = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');

$domain = [
    [
        'url'   => 'Rekam_Perumahan',
        'judul' => 'Capaian Perumahan',
        'ikon'  => 'ph-house-line',
        'isi'   => 'Sepuluh sumber dana × enam program: penanganan dan pembangunan RTLH, backlog, bencana, dan relokasi. Termasuk unggah BNBA.',
    ],
    [
        'url'   => 'Rekam_Kawasan',
        'judul' => 'Capaian Kawasan',
        'ikon'  => 'ph-map-trifold',
        'isi'   => 'Tujuh indikator kawasan permukiman beserta daftar intervensinya, ringkasan luas tertangani, dan serapan padat karya.',
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

  <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
    <?php foreach ($domain as $d): ?>
      <?php /* Kartu ini WAJIB memakai dialek `rekam`, bukan dialek
                `admin/dashboard.php`. Kanon di lingkungan ini (18 kemunculan):
                `p-5`, TANPA shadow, `dark:border-white/10`. Versi pertama saya
                menyalin dashboard (`p-6 shadow-sm dark:border-white/5`) plus
                `space-y-6`, dan berdiri sendiri di antara tujuh view rekam
                lain yang seragam. */ ?>
      <a href="<?= base_url($d['url']) ?>"
         class="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 transition-colors hover:border-blue-300 dark:border-white/10 dark:bg-brand-card dark:hover:border-brand-primary/30">

        <div class="absolute right-0 top-0 p-4 opacity-10 transition-opacity group-hover:opacity-20">
          <i class="ph <?= $e($d['ikon']) ?> text-6xl text-blue-500 dark:text-brand-primary"></i>
        </div>

        <div class="relative z-10">
          <div class="mb-4 flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-brand-primary/10 dark:text-brand-primary">
              <i class="ph <?= $e($d['ikon']) ?> text-xl"></i>
            </div>
            <h3 class="text-base font-bold text-gray-900 dark:text-white"><?= $e($d['judul']) ?></h3>
          </div>

          <p class="text-sm leading-relaxed text-gray-500 dark:text-brand-muted"><?= $e($d['isi']) ?></p>

          <?php /* Aksen CTA: biru di terang, lime di gelap — sama seperti ikon
                    kartu `dashboard.php` dan sidebar. `dark:text-blue-400`
                    (dipakai riwayat.php) SENGAJA tidak dipakai di sini: di sana
                    ia tautan inline dalam paragraf, sedangkan di mode gelap
                    satu-satunya benda biru di layar terlihat asing. */ ?>
          <span class="mt-4 inline-flex items-center gap-1 text-sm font-bold text-blue-600 dark:text-brand-primary">
            Buka
            <i class="ph ph-arrow-right transition-transform group-hover:translate-x-1"></i>
          </span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>

</div>
