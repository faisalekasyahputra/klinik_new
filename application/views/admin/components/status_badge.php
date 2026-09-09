<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Badge status generik - dipakai lintas modul dashboard (SRP2, antrean,
 * aduan, kemitraan). Vocabulary status SENGAJA tidak diseragamkan di DB
 * (beda per domain: Pending/Diajukan/pending/Baru, dst - lihat
 * docs/engineering/AUDIT_SISTEM_ROLE_RINGKASAN.md) - pemanggil wajib
 * memetakan status domainnya ke salah satu $kelas berikut:
 *   pending | process | ok | reject
 *
 * Variabel: $label (teks tampil), $kelas (salah satu di atas).
 */
$kelas_map = [
    'pending' => 'bg-amber-50 dark:bg-brand-primary/10 text-amber-700 dark:text-brand-primary border-amber-200 dark:border-brand-primary/20',
    'process' => 'bg-cyan-50 dark:bg-cyan-500/10 text-cyan-700 dark:text-cyan-400 border-cyan-200 dark:border-cyan-500/20',
    'ok'      => 'bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-400 border-green-200 dark:border-green-500/30',
    'reject'  => 'bg-red-50 dark:bg-red-500/10 text-red-600 dark:text-red-400 border-red-200 dark:border-red-500/30',
];
?>
<span class="inline-flex px-3 py-1 rounded-full text-xs font-bold border <?= $kelas_map[$kelas] ?? $kelas_map['pending'] ?>"><?= html_escape($label) ?></span>
