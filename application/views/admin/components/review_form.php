<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Form update status generik: satu <form>, satu textarea catatan opsional,
 * beberapa tombol submit dengan value 'status' berbeda ke SATU endpoint.
 * Pola ini sudah dipakai Admin_Kemitraan::proses() sebelum komponen ini ada —
 * sekarang dijadikan komponen bersama, lahir bareng Admin_Srp2::proses().
 *
 * Validasi wajib/tidaknya catatan (mis. wajib saat menolak) SELALU di
 * server pada controller tujuan — form ini murni markup, jangan andalkan
 * `required` HTML sebagai satu-satunya penjagaan.
 *
 * Variabel:
 *   $action_url    - path CI tujuan POST (base_url() ditambahkan di sini)
 *   $buttons       - array of ['value'=>.., 'label'=>.., 'style'=>'accept'|'reject'|'neutral']
 *   $catatan_name  - nama field textarea, atau null/kosong untuk sembunyikan
 *   $catatan_placeholder - opsional
 *   $catatan_value - opsional, isi awal textarea (mis. catatan yang sudah ada)
 *   $hidden        - opsional, array field=>value tambahan selain CSRF
 */
$style_class = [
    'accept'  => 'bg-green-600 hover:bg-green-700 text-white',
    'reject'  => 'bg-red-600 hover:bg-red-700 text-white',
    'neutral' => 'bg-gray-200 hover:bg-gray-300 text-gray-800 dark:bg-white/10 dark:hover:bg-white/20 dark:text-white',
];
?>
<form method="post" action="<?= base_url($action_url) ?>" class="space-y-2">
    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
    <?php foreach (($hidden ?? []) as $field => $value): ?>
    <input type="hidden" name="<?= html_escape($field) ?>" value="<?= html_escape($value) ?>">
    <?php endforeach; ?>
    <?php if (!empty($catatan_name)): ?>
    <textarea name="<?= html_escape($catatan_name) ?>" rows="2" placeholder="<?= html_escape($catatan_placeholder ?? 'Catatan (opsional)') ?>" class="w-full rounded-lg border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-2 text-xs text-gray-800 dark:text-gray-200"><?= html_escape($catatan_value ?? '') ?></textarea>
    <?php endif; ?>
    <div class="flex gap-2">
        <?php foreach ($buttons as $b): ?>
        <button type="submit" name="status" value="<?= html_escape($b['value']) ?>" class="flex-1 px-3 py-1.5 rounded-lg text-xs font-bold <?= $style_class[$b['style'] ?? 'neutral'] ?>"><?= html_escape($b['label']) ?></button>
        <?php endforeach; ?>
    </div>
</form>
