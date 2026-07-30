<?php
/**
 * Rekam Data — "BANK Data / Tampilkan Buku Data" di sketsa Menu Utama.
 *
 * Halaman PUBLIK, di shell portal. Sengaja bukan halaman admin: kartu REKAM
 * DATA ada di beranda publik, dan sebelum ini mengekliknya melempar pengunjung
 * ke layar login dengan pesan "Akses ditolak. Anda bukan Admin Kabupaten/Kota."
 * — menuduh orang yang cuma menekan satu kartu menu.
 *
 * Gayanya mengikuti `pages/home/tab_bankdata.php`, tetangga terdekatnya:
 * FontAwesome (portal TIDAK memuat Phosphor), variabel `--portal-*`, dan
 * `tl-btn-base`. Kelas `portal-home-card` SENGAJA tidak dipakai — kelas itu
 * didefinisikan di dalam `<style>` milik `awal.php` sendiri, jadi hanya hidup
 * di beranda.
 *
 * Nol angka di sini. Pintu yang memajang ringkasan menampilkan data beberapa
 * detik lebih tua dari halaman tujuannya — dua angka berbeda untuk hal yang
 * sama, dan yang salah justru dilihat lebih dulu.
 */
$e = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');

$domain = [
    [
        'url'   => 'Rekam_Perumahan',
        'ikon'  => 'fa-house-chimney-window',
        'judul' => 'Capaian Perumahan',
        'isi'   => 'Sepuluh sumber dana × enam program: penanganan dan pembangunan RTLH, backlog, bencana, dan relokasi.',
        'aksi'  => 'Buka Capaian Perumahan',
    ],
    [
        'url'   => 'Rekam_Kawasan',
        'ikon'  => 'fa-city',
        'judul' => 'Capaian Kawasan',
        'isi'   => 'Tujuh indikator kawasan permukiman beserta daftar intervensinya, luas tertangani, dan serapan padat karya.',
        'aksi'  => 'Buka Capaian Kawasan',
    ],
];
?>

<div class="py-4 sm:py-6 px-1 sm:px-2">
    <div class="flex items-center gap-2 mb-3">
        <i class="fa-solid fa-chart-line text-[color:var(--portal-text)]"></i>
        <h2 class="text-sm font-bold uppercase tracking-widest text-[#2d6b75]">Rekam Data</h2>
    </div>

    <p class="mb-3 text-xs leading-relaxed text-[color:var(--portal-text-muted)]">
        Buku data capaian perumahan dan kawasan permukiman kabupaten/kota.
        Pengisiannya dilakukan Admin Kabupaten/Kota untuk wilayahnya sendiri —
        membuka salah satu buku di bawah akan meminta kamu masuk lebih dulu.
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">
        <?php foreach ($domain as $d): ?>
            <?php /* `data-no-page-transition` DISENGAJA: kedua tautan ini keluar dari
                      shell portal menuju shell admin. Tanpa penanda ini, loader portal
                      mem-fetch-nya dulu, mendapat dokumen utuh, lalu baru jatuh ke
                      navigasi penuh — dua GET untuk satu klik. Pelajaran S2 yang sama
                      dengan tautan detail forum. */ ?>
            <a href="<?= base_url($d['url']) ?>" data-no-page-transition
               class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
               style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow); min-height: 120px;">
                <i class="fa-solid <?= $e($d['ikon']) ?> absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
                   style="font-size: 80px; right: -1rem; bottom: -1rem; color: var(--portal-icon); opacity: 0.08;"></i>
                <div class="relative z-10">
                    <i class="fa-solid <?= $e($d['ikon']) ?> mb-2.5 transition-transform duration-500 group-hover:scale-110"
                       style="font-size: 24px; color: var(--portal-icon);"></i>
                    <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1"><?= $e($d['judul']) ?></h4>
                    <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed"><?= $e($d['isi']) ?></p>
                </div>
                <div class="relative z-10 mt-auto pt-2.5">
                    <div class="tl-btn-base" style="background-color: var(--portal-btn-bg); color: var(--portal-icon); border: 1px solid var(--portal-btn-border);">
                        <span><?= $e($d['aksi']) ?></span>
                        <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
