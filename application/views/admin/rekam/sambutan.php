<?php
/**
 * Rekam Data - layar sambutan sesudah masuk (frame 002 rancangan).
 *
 * Judul "Rekam Data", lalu kabupaten dan tahun pelaporan di keterangan, lalu tiga menu. Wilayah
 * dan tahun disebut di muka SEBELUM orang menyentuh angka apa pun: modul ini
 * ter-scope satu kabupaten dan satu periode, dan kekeliruan paling mahal di
 * sini adalah mengisi capaian ke wilayah atau tahun yang salah tanpa sadar.
 *
 * Rancangan aslinya hanya memuat Perumahan. Kawasan ditaruh sebagai kelompok
 * KEDUA yang terpisah, bukan dicampur ke tiga menu itu - mencampurnya membuat
 * "Input Capaian" ambigu antara dua domain yang bentuk datanya berbeda.
 */
$e = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');

$menu = [
    'Perumahan' => [
        ['Rekam_Perumahan',         'ph-chart-bar',                'Input Capaian',    'Isi capaian perumahan periode berjalan.'],
        ['Rekam_Perumahan/rekap',   'ph-chart-line-up',            'Rekap Pelaporan',  'Angka yang sudah dikirim, per periode.'],
        ['Rekam_Perumahan/riwayat', 'ph-clock-counter-clockwise',  'Riwayat Pelaporan','Daftar periode beserta statusnya.'],
    ],
    'Kawasan Permukiman' => [
        ['Rekam_Kawasan',         'ph-chart-bar',               'Input Capaian',     'Isi capaian kawasan periode berjalan.'],
        ['Rekam_Kawasan/rekap',   'ph-chart-line-up',           'Rekap Pelaporan',   'Angka yang sudah dikirim, per periode.'],
        ['Rekam_Kawasan/riwayat', 'ph-clock-counter-clockwise', 'Riwayat Pelaporan', 'Daftar periode beserta statusnya.'],
    ],
];
?>

<?php // Judul = label sidebar ("Rekam Data"); wilayah dan tahun tetap disebut di muka, di baris keterangan.
$this->load->view('admin/components/judul_halaman', ['jh_deskripsi' =>
    '<b class="text-gray-900 dark:text-white">' . $e($nama_wilayah) . '</b>, pelaporan tahun ' . (int) $tahun
    . '. Wilayah diambil dari akunmu, bukan dari pilihan di layar - kamu hanya bisa merekam capaian wilayah sendiri.']); ?>

<div class="tumpuk-bagian">

  <?php foreach ($menu as $judul => $baris): ?>
    <section class="kartu-admin isi-kartu">
      <h3 class="text-sm font-bold text-gray-900 dark:text-white">
        <?= $e($judul) ?>
      </h3>
      <div class="mt-3 grid-kartu grid grid-cols-1 sm:grid-cols-3">
        <?php foreach ($baris as [$url, $ikon, $label, $isi]): ?>
          <a href="<?= base_url($url) ?>"
             class="group flex flex-col rounded-xl border border-gray-200 p-4 transition-colors hover:border-blue-300 dark:border-white/10 dark:hover:border-brand-primary/30">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-brand-primary/10 dark:text-brand-primary">
              <i class="ph <?= $e($ikon) ?> text-lg"></i>
            </div>
            <span class="mt-3 text-sm font-bold text-gray-900 dark:text-white"><?= $e($label) ?></span>
            <span class="mt-1 text-xs leading-relaxed text-gray-500 dark:text-brand-muted"><?= $e($isi) ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    </section>
  <?php endforeach; ?>

</div>
