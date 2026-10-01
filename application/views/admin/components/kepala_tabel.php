<?php
/**
 * Kepala kartu tabel admin: SATU gaya (audit UI 2 Okt 2026). Dulu Akses Staf,
 * Jejak Audit dan Akun Universitas memakai judul besar berikon, sementara
 * Struktur & Cakupan memakai judul kecil tanpa ikon. Kini semuanya begini:
 * judul tebal tanpa ikon, jumlah abu di belakangnya, keterangan opsional di kanan.
 *
 * Variabel berawalan kt_ karena variabel view CodeIgniter menempel ke view
 * berikutnya (lihat judul_halaman.php), jadi kt_keterangan selalu dikirim.
 *
 * @param string   $kt_judul      teks judul
 * @param int|null $kt_jumlah     jumlah baris, NULL bila tidak ditampilkan
 * @param string   $kt_keterangan HTML tepercaya dari view untuk sisi kanan, boleh ''
 */
?>
<div class="flex flex-wrap items-baseline justify-between gap-2 border-b border-gray-200 p-4 dark:border-white/5" data-kepala-tabel>
    <h2 class="font-black text-gray-900 dark:text-white"><?= html_escape($kt_judul) ?><?php if (isset($kt_jumlah) && $kt_jumlah !== NULL): ?> <span class="text-gray-400 dark:text-brand-muted">(<?= angka_id((int) $kt_jumlah) ?>)</span><?php endif; ?></h2>
    <?php if ( ! empty($kt_keterangan)): ?><?= $kt_keterangan ?><?php endif; ?>
</div>
