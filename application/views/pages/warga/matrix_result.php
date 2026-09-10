<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<?php if (empty($matrix_result)): ?>
    <p class="mt-3 text-sm">Rekomendasi matriks belum tersimpan untuk draft ini.</p>
<?php else: ?>
    <p class="mt-3 text-xs">Hasil berdasarkan matriks program perumahan. Kecocokan awal masih perlu pemeriksaan petugas.</p>
    <?php if (empty($matrix_result['items'])): ?>
        <p class="mt-3 text-sm">Belum ada program yang cocok dengan data pada matriks. Periksa isian atau lanjutkan melengkapi data.</p>
    <?php endif; ?>
    <?php foreach ($matrix_result['items'] ?? [] as $item): ?>
        <article class="mt-4 rounded-xl border p-4" style="border-color:var(--portal-border)">
            <h3 class="font-bold"><?= html_escape($item['program_name']) ?></h3>
            <p class="mt-2 text-xs">Syarat matriks: <?= html_escape(implode('; ', $item['criteria'] ?? [])) ?>.</p>
            <p class="mt-2 text-sm"><?= empty($item['missing']) ? 'Sesuai kriteria awal matriks.' : 'Kandidat belum dapat dipastikan. Data yang perlu dilengkapi atau diverifikasi: ' . html_escape(implode(', ', $item['missing'])) . '.' ?></p>
        </article>
    <?php endforeach; ?>
<?php endif; ?>
