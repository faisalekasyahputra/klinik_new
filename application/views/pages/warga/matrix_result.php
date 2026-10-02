<?php defined('BASEPATH') OR exit('No direct script access allowed');
/* Deskripsi program dari katalog (UAT dinas warga #10: rekomendasi tampil beserta
   deskripsi programnya). get_instance() eksplisit: di view $this adalah CI_Loader. */
$CI =& get_instance();
$CI->load->library('Matriks_program_ruleset');
$CI->load->model('Program_model');
$deskripsi_program = [];
foreach ($matrix_result['items'] ?? [] as $item) {
    $kode = $CI->matriks_program_ruleset->kode_katalog($item['program_name'] ?? '');
    if ($kode !== NULL && ! array_key_exists($kode, $deskripsi_program)) {
        $deskripsi_program[$kode] = $CI->Program_model->get_program_by_code($kode)['deskripsi_singkat'] ?? NULL;
    }
}
?>
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
            <?php $deskripsi = $deskripsi_program[$CI->matriks_program_ruleset->kode_katalog($item['program_name'] ?? '')] ?? NULL; ?>
            <?php if ($deskripsi): ?><p class="mt-1 text-sm"><?= html_escape($deskripsi) ?></p><?php endif; ?>
            <p class="mt-2 text-xs">Syarat matriks: <?= html_escape(implode('; ', $item['criteria'] ?? [])) ?>.</p>
            <p class="mt-2 text-sm"><?= empty($item['missing']) ? 'Sesuai kriteria awal matriks.' : 'Kandidat belum dapat dipastikan. Data yang perlu dilengkapi atau diverifikasi: ' . html_escape(implode(', ', $item['missing'])) . '.' ?></p>
        </article>
    <?php endforeach; ?>
<?php endif; ?>
