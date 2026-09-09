<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$this->load->helper('admin_table');
/**
 * Toolbar tabel admin: kotak cari (server-side, GET) + slot filter opsional.
 *
 * Pencarian sengaja server-side - sepasang dengan paginasi server-side. Pola
 * lama (kirim semua baris ke browser lalu filter di klien) memang terasa
 * instan tapi tidak bisa dipakai begitu data banyak; itu yang dibereskan B8.
 *
 * Variabel:
 *   $table    - hasil MY_Controller::table_state() (+ paginate_state())
 *   $base_url - path CI halaman ini
 *   $placeholder - opsional, teks placeholder kotak cari
 *   $filter_html - opsional, HTML kontrol filter tambahan (mis. tombol bidang)
 */
?>
<div class="px-6 py-4 border-b border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
    <div class="flex flex-wrap items-center gap-2"><?= $filter_html ?? '' ?></div>

    <form method="get" action="<?= base_url($base_url) ?>" class="flex items-center gap-2 w-full lg:w-auto">
        <?php
        // Parameter lain (filter, urutan) dibawa sebagai hidden supaya tidak
        // hilang saat mencari. 'page' sengaja TIDAK dibawa - hasil pencarian
        // baru selalu mulai dari halaman 1.
        foreach ($_GET as $k => $v) {
            if (in_array($k, ['q', 'page'], TRUE) || is_array($v)) { continue; }
            echo '<input type="hidden" name="' . html_escape($k) . '" value="' . html_escape($v) . '">';
        }
        ?>
        <div class="relative flex-1 lg:w-64">
            <input type="search" name="q" value="<?= html_escape($table['q'] ?? '') ?>"
                   placeholder="<?= html_escape($placeholder ?? 'Cari...') ?>"
                   class="w-full bg-white dark:bg-black/30 border border-gray-200 dark:border-white/10 rounded-lg pl-8 pr-3 py-1.5 text-gray-800 dark:text-white text-xs focus:outline-none focus:border-brand-primary/50 focus:ring-1 focus:ring-brand-primary/50">
            <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-brand-muted/70 text-[10px]"></i>
        </div>
        <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-blue-600 dark:bg-brand-primary text-white dark:text-brand-dark hover:bg-blue-700 dark:hover:bg-brand-hover transition-colors">Cari</button>
        <?php if (!empty($table['q'])): ?>
        <a href="<?= admin_table_url($base_url, ['q' => NULL]) ?>" class="px-3 py-1.5 rounded-lg text-xs font-bold border border-gray-200 dark:border-white/10 text-gray-600 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/10 transition-colors" title="Hapus pencarian">Reset</a>
        <?php endif; ?>
    </form>
</div>
