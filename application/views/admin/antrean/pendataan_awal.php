<?php
$this->load->helper('admin_table');
/**
 * Pendataan awal warga di wilayah admin kab/kota (daftar revisi dinas 23 Sep 2026: "langkah
 * ketiga harus tersimpan, 4 opsional dan bisa dipantau admin"). Draft yang sudah menyimpan
 * rekomendasi awal tetapi belum dikirim. Hanya baca.
 *
 * Identitas mengikuti sakelar B2 di config/kebijakan_data.php, sama seperti layar antrean:
 * selama 'menunggu_keputusan', nama dan HP diganti contoh yang diturunkan dari id baris.
 */
$this->config->load('kebijakan_data', TRUE, TRUE);
$identitas_menunggu = $this->config->item('identitas_warga_kabkota', 'kebijakan_data') === 'menunggu_keputusan';
$langkah = [
    'preliminary_recommendation' => 'Berhenti di hasil rekomendasi awal',
    'housing_family_detail' => 'Melengkapi data warga',
    'building_condition' => 'Melengkapi kondisi bangunan',
    'candidate_land' => 'Melengkapi data calon lahan',
    'sanitation' => 'Melengkapi sanitasi',
    'location_evidence' => 'Melengkapi lokasi dan bukti',
    'review' => 'Siap dikirim, belum dikirim',
];
?>
<div class="mb-4">
    <p class="text-sm text-gray-500 dark:text-brand-muted">
        Warga di <strong><?= html_escape($scope_label) ?></strong> yang sudah menyimpan data dan rekomendasi awal,
        tetapi belum mengirim pengajuan. Melengkapi data sesudah rekomendasi awal bersifat opsional bagi warga;
        daftar ini untuk tindak lanjut petugas. Pengajuan yang sudah dikirim ada di Antrean Wilayah Saya.
    </p>
    <?php if ($identitas_menunggu): ?>
    <p class="mt-2 text-xs text-amber-700 dark:text-amber-400">Identitas warga disamarkan sampai dinas memutuskan kebijakan tampilan data pribadi (butir B2).</p>
    <?php endif; ?>
</div>
<div data-tabel-admin style="counter-reset: baris-admin <?= (int) ($table['offset'] ?? 0) ?>" class="bg-white dark:bg-brand-card rounded-3xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-4">Warga</th>
                    <th class="px-4 py-4">Rekomendasi Awal</th>
                    <th class="px-4 py-4">Posisi Terakhir</th>
                    <th class="px-4 py-4">Terakhir Diperbarui</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                <?php if (empty($rows)): ?>
                <tr><td colspan="4" class="px-4 py-12 text-center text-gray-500 dark:text-brand-muted">Belum ada pendataan awal di wilayah Anda.</td></tr>
                <?php else: foreach ($rows as $r): ?>
                <?php
                $nama = $identitas_menunggu ? 'Warga Contoh ' . str_pad((string) $r['id'], 3, '0', STR_PAD_LEFT) : ($r['full_name'] ?: '-');
                $hp = $identitas_menunggu ? str_repeat('•', 8) . str_pad(substr((string) $r['id'], -4), 4, '0', STR_PAD_LEFT) : ($r['phone'] ?: '-');
                ?>
                <tr>
                    <td class="px-4 py-3 align-top">
                        <div class="font-bold text-gray-900 dark:text-white"><?= html_escape($nama) ?></div>
                        <div class="text-xs font-mono"><?= html_escape($hp) ?></div>
                    </td>
                    <td class="px-4 py-3 align-top whitespace-normal">
                        <?php if ($r['programs']): foreach ($r['programs'] as $prog): ?>
                            <div class="mb-1 inline-block rounded-lg bg-brand-primary/10 px-2 py-0.5 text-xs font-semibold text-brand-primary"><?= html_escape($prog) ?></div>
                        <?php endforeach; else: ?>
                            <span class="text-xs text-gray-500 dark:text-brand-muted">Belum ada program yang cocok</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 align-top text-xs"><?= html_escape($langkah[$r['current_step']] ?? $r['current_step']) ?></td>
                    <td class="px-4 py-3 align-top text-xs"><?= html_escape($r['updated_at'] ? date('d M Y H:i', strtotime($r['updated_at'])) : '-') ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?= $this->load->view('admin/components/pagination', ['pager' => $pager, 'base_url' => $base_url], TRUE) ?>
</div>
