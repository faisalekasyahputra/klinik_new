<?php
/**
 * Judul halaman admin: SATU gaya untuk semua layar ketiga peran admin (audit UI
 * 2 Okt 2026). Dulu tiap layar menulis sendiri: h1/h2, text-xl sampai text-3xl,
 * ada yang berikon, dan teksnya berbeda dari label sidebar serta <title>.
 *
 * Teks judul bawaannya `$title` dari controller, yang juga mengisi <title> di
 * layouts/head.php, dan disamakan dengan label menu di config/dashboard_modules.php.
 * Jadi satu nama per layar, di tiga tempat.
 *
 * Nama variabel berawalan jh_ karena variabel view CodeIgniter menempel ke view
 * berikutnya; nama umum seperti $deskripsi bisa bertabrakan dengan data layar.
 *
 * @param string $jh_judul     opsional, menimpa $title
 * @param string $jh_deskripsi opsional, HTML tepercaya dari view (bukan masukan pengguna)
 * @param string $jh_aksi      opsional, HTML tombol di sisi kanan judul
 */
$jh_teks = isset($jh_judul) && $jh_judul !== '' ? $jh_judul : ($title ?? '');
?>
<div class="mb-8 flex flex-wrap items-start justify-between gap-4" data-judul-halaman>
    <div class="min-w-0">
        <h1 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2"><?= html_escape($jh_teks) ?></h1>
        <?php if ( ! empty($jh_deskripsi)): ?>
            <p class="text-sm text-gray-500 dark:text-brand-muted"><?= $jh_deskripsi ?></p>
        <?php endif; ?>
    </div>
    <?php if ( ! empty($jh_aksi)): ?>
        <div class="flex flex-wrap items-center gap-2"><?= $jh_aksi ?></div>
    <?php endif; ?>
</div>
