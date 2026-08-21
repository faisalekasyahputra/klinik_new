<?php
/**
 * Kepala halaman KKN/Magang: judul + tab.
 *
 * Sebelumnya domain ini terpecah jadi DUA entri sidebar - "KKN/Magang" dan
 * "Slot Magang" - padahal keduanya satu urusan: yang satu menetapkan tempatnya,
 * yang lain memproses orang yang mengisinya. Sidebar yang tumbuh satu baris
 * setiap kali ada layar baru akan berhenti bisa dibaca, dan admin harus
 * mengingat sendiri bahwa dua entri itu sebenarnya bersaudara.
 *
 * Sub-halaman (detail divisi, sunting pendaftaran) memakai kepala yang sama
 * dengan tab induknya menyala, supaya admin tidak pernah kehilangan tempatnya.
 *
 * @param string $tab_aktif 'pendaftaran' | 'slot' | 'universitas'
 */
$tab = [
    'pendaftaran' => ['label' => 'Pendaftaran', 'url' => 'Admin_Kemitraan',      'ikon' => 'ph-student'],
    'slot'        => ['label' => 'Slot & Bidang', 'url' => 'Admin_Kemitraan/slot', 'ikon' => 'ph-calendar-check'],
    // Akun (bukan pengajuan) - permintaan user 22 Agt 2026: "bisa mengelola
    // Akun KKN/Universitas". Daftar role='mahasiswa' berikut jumlah KKN
    // yang pernah diajukan; aksi sunting/nonaktifkan/reset sandi TETAP di
    // Admin_Users (satu sumber kebenaran untuk seluruh akun, apa pun
    // rolenya) - tab ini menautkan ke sana per akun, tidak menyalin
    // logikanya. Lihat Admin_Kemitraan::universitas().
    'universitas' => ['label' => 'Akun Universitas', 'url' => 'Admin_Kemitraan/universitas', 'ikon' => 'ph-bank'],
];
$aktif = isset($tab_aktif) ? $tab_aktif : 'pendaftaran';
?>
<div class="mb-5">
    <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-1">KKN &amp; Magang</h2>
    <p class="text-sm text-gray-500 dark:text-brand-muted">
        Tempat yang dibuka, dan orang yang mengisinya - dikelola dari satu halaman.
    </p>
</div>

<div class="mb-6 flex flex-wrap gap-1 border-b border-gray-200 dark:border-white/5">
    <?php foreach ($tab as $kunci => $t): ?>
        <a href="<?= base_url($t['url']) ?>"
           class="-mb-px flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-bold transition-colors <?= $kunci === $aktif
                ? 'border-emerald-500 text-emerald-600 dark:text-emerald-400'
                : 'border-transparent text-gray-500 dark:text-brand-muted hover:text-gray-800 dark:hover:text-white' ?>">
            <i class="ph <?= $t['ikon'] ?>"></i>
            <?= html_escape($t['label']) ?>
        </a>
    <?php endforeach; ?>
</div>
