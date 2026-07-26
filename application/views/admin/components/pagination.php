<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->helper('admin_table');
/**
 * Pager server-side untuk tabel admin. LIMIT+OFFSET biasa, bukan infinite
 * scroll — halaman admin butuh posisi yang bisa di-bookmark dan total yang jelas.
 *
 * Link dibangun lewat admin_table_url() supaya parameter lain (filter, cari,
 * urutan) IKUT TERBAWA. Versi pertama komponen ini membangun '?page=N' polos,
 * akibatnya filter bidang di Admin_Aduan hilang begitu pindah halaman.
 *
 * Variabel:
 *   $pager    - hasil MY_Controller::paginate_state()
 *   $base_url - path CI halaman ini (tanpa query string)
 */
if (empty($pager) || $pager['total_rows'] === 0) { return; }
$dari = $pager['offset'] + 1;
$sampai = min($pager['offset'] + $pager['per_page'], $pager['total_rows']);
?>
<div class="px-6 py-3 border-t border-gray-200 dark:border-white/5 bg-gray-50 dark:bg-black/10 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-gray-500 dark:text-brand-muted">
    <div>
        Menampilkan <span class="font-bold text-gray-900 dark:text-white"><?= $dari ?></span>–<span class="font-bold text-gray-900 dark:text-white"><?= $sampai ?></span>
        dari <span class="font-bold text-gray-900 dark:text-white"><?= number_format($pager['total_rows']) ?></span> data
    </div>
    <?php if ($pager['total_pages'] > 1): ?>
    <div class="flex items-center gap-1">
        <?php
        $awal = max(1, $pager['page'] - 2);
        $akhir = min($pager['total_pages'], $awal + 4);
        $awal = max(1, $akhir - 4);
        $link = function ($p, $label, $aktif = FALSE, $nonaktif = FALSE) use ($base_url) {
            if ($nonaktif) {
                return '<span class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 dark:border-white/10 opacity-30">' . $label . '</span>';
            }
            $kelas = $aktif
                ? 'bg-brand-primary/20 border-brand-primary/50 text-brand-primary'
                : 'border-gray-200 dark:border-white/10 hover:bg-gray-100 dark:hover:bg-white/10 text-gray-700 dark:text-white';
            return '<a href="' . admin_table_url($base_url, ['page' => $p]) . '" class="w-8 h-8 flex items-center justify-center rounded-lg border font-medium transition-colors ' . $kelas . '">' . $label . '</a>';
        };
        echo $link($pager['page'] - 1, '<i class="ph ph-caret-left"></i>', FALSE, $pager['page'] <= 1);
        for ($p = $awal; $p <= $akhir; $p++) { echo $link($p, (string) $p, $p === $pager['page']); }
        echo $link($pager['page'] + 1, '<i class="ph ph-caret-right"></i>', FALSE, $pager['page'] >= $pager['total_pages']);
        ?>
    </div>
    <?php endif; ?>
</div>
