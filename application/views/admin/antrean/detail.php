<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$queue = isset($queue) && is_array($queue) ? $queue : [];
$assessment = isset($assessment) && is_array($assessment) ? $assessment : [];
$profile = isset($profile) && is_array($profile) ? $profile : [];
$source_snapshot = isset($source_snapshot) && is_array($source_snapshot) ? $source_snapshot : [];
$provenance = isset($provenance) && is_array($provenance) ? $provenance : [];
$recommendations = isset($recommendations) && is_array($recommendations) ? $recommendations : [];
$evidence = isset($evidence) && is_array($evidence) ? $evidence : [];
$raw_identity = $source_snapshot['identity'] ?? [];
$raw_socioeconomic = $source_snapshot['socioeconomic'] ?? [];
$e = static function ($value) { return html_escape((string) ($value ?? '')); };
$display = static function ($value) use ($e) { return $value === NULL || $value === '' ? 'Belum tersedia' : $e($value); };
$mask = static function ($value, $visible = 4) { $value = (string) $value; return $value === '' ? 'Belum tersedia' : str_repeat('•', max(0, strlen($value) - $visible)) . substr($value, -$visible); };
$track_labels = ['existing_house' => 'Rumah eksisting', 'candidate_land' => 'Calon lahan', 'financing' => 'Pembiayaan'];
$status_labels = ['pending' => 'Dalam peninjauan', 'needs_revision' => 'Perlu diperbaiki', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];
$reason_labels = [
    'SIM_DECILE_MISSING' => 'Data desil belum tersedia.',
    'SIM_DESIL_TIDAK_SESUAI' => 'Desil belum sesuai dengan sasaran simulasi program.',
    'SIM_TRACK_TIDAK_SESUAI' => 'Cabang kebutuhan rumah belum sesuai dengan simulasi program.',
    'SIM_RTLH_DAMAGE' => 'Kondisi kerusakan rumah menjadi pertimbangan simulasi.', 'SIM_RTLH_SANITATION' => 'Kondisi sanitasi menjadi pertimbangan simulasi.',
    'SIM_PB_LAND_READY' => 'Kesiapan calon lahan menjadi pertimbangan simulasi.', 'SIM_OMAH_DESIL4_SELF_HELP' => 'Desil dan kemampuan swadaya menjadi pertimbangan simulasi.',
    'SIM_OMAH_KEBUTUHAN_BELUM_TERVERIFIKASI' => 'Kebutuhan rumah atau perbaikan belum terkonfirmasi.',
    'SIM_KEBUTUHAN_RUMAH_BELUM_LENGKAP' => 'Data kepemilikan rumah untuk pembiayaan belum lengkap.',
    'SIM_KEBUTUHAN_RUMAH_TIDAK_MEMENUHI' => 'Data kepemilikan rumah belum sesuai sasaran simulasi pembiayaan.',
    'SIM_FLPP_INCOME' => 'Penghasilan menjadi pertimbangan simulasi FLPP.',
    'SIM_OEMAH_INCOME' => 'Penghasilan menjadi pertimbangan simulasi Oemah Lestari.', 'SIM_INCOME_MISSING' => 'Data penghasilan belum tersedia.',
    'SIM_RUMAH_APUNG_NEEDS_DATA' => 'Definisi dan data Rumah Apung belum tersedia.',
];
$field_labels = [
    'housing_status_code' => 'Status rumah', 'land_title_code' => 'Status lahan', 'occupant_count' => 'Jumlah penghuni', 'family_count' => 'Jumlah keluarga',
    'house_area_m2' => 'Luas rumah (m²)', 'area_condition_code' => 'Kawasan', 'owns_candidate_land' => 'Memiliki calon lahan',
    'candidate_land_address' => 'Alamat calon lahan', 'candidate_land_title_code' => 'Sertifikat calon lahan', 'candidate_land_origin_code' => 'Asal tanah',
    'land_owner_relationship_code' => 'Hubungan dengan pemilik', 'land_length_m' => 'Panjang tanah (m)', 'land_width_m' => 'Lebar tanah (m)',
    'foundation_condition_code' => 'Pondasi', 'column_condition_code' => 'Kolom', 'beam_condition_code' => 'Balok', 'roof_frame_condition_code' => 'Rangka atap',
    'floor_material_code' => 'Bahan lantai', 'floor_condition_code' => 'Kondisi lantai', 'wall_material_code' => 'Bahan dinding', 'wall_condition_code' => 'Kondisi dinding',
    'roof_material_code' => 'Bahan atap', 'roof_condition_code' => 'Kondisi atap', 'water_source_code' => 'Sumber air', 'latrine_type_code' => 'Jenis jamban',
    'feces_disposal_code' => 'Jenis TPA', 'septic_distance_code' => 'Jarak septik', 'lighting_source_code' => 'Penerangan', 'cooking_fuel_code' => 'Bahan bakar memasak',
];
$identity_fields = [
    'full_name' => ['Nama lengkap', $raw_identity['full_name'] ?? NULL, $profile['full_name'] ?? NULL],
    'address' => ['Alamat', $raw_identity['address'] ?? NULL, $profile['address'] ?? NULL],
    'birth_date' => ['Tanggal lahir', $raw_identity['birth_date'] ?? NULL, $profile['birth_date'] ?? NULL],
    'family_card_number' => ['Nomor KK', $mask($raw_identity['family_card_number'] ?? ''), $mask($profile['family_card_number'] ?? '')],
    'phone' => ['Nomor HP', $mask($raw_identity['phone'] ?? ''), $mask($profile['phone'] ?? '')],
    'welfare_decile' => ['Desil', $raw_socioeconomic['welfare_decile'] ?? NULL, $profile['welfare_decile'] ?? NULL],
];
$provenance_source = static function ($field) use ($provenance) {
    $value = $provenance[$field] ?? 'citizen';
    return is_array($value) ? ($value['source'] ?? 'citizen') : $value;
};
?>
<div class="space-y-5">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><a href="<?= base_url($back_url ?? 'Admin') ?>" class="text-xs font-bold text-brand-primary">← Kembali ke antrean</a><h1 class="mt-2 text-2xl font-black text-gray-900 dark:text-white">Detail Penilaian Warga</h1><p class="text-sm text-gray-500 dark:text-brand-muted"><?= $e($queue['ticket_code'] ?? '') ?> · Versi <?= (int) ($assessment['version_no'] ?? 1) ?></p></div><?php if (($assessment['source_mode'] ?? '') === 'simulation'): ?><span class="w-fit rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">Mode Simulasi — API SIMPERUM belum terhubung</span><?php endif; ?></div>

    <section class="grid gap-3 sm:grid-cols-4"><div class="rounded-2xl border border-gray-200 p-4 dark:border-white/10"><p class="text-xs text-gray-500">Status</p><p class="mt-1 font-bold"><?= $e($status_labels[$queue['status_antrean'] ?? ''] ?? ($queue['status_antrean'] ?? '-')) ?></p></div><div class="rounded-2xl border border-gray-200 p-4 dark:border-white/10"><p class="text-xs text-gray-500">Program</p><p class="mt-1 font-bold"><?= $display($queue['program_name'] ?? $queue['nama_program'] ?? NULL) ?></p></div><div class="rounded-2xl border border-gray-200 p-4 dark:border-white/10"><p class="text-xs text-gray-500">Cabang</p><p class="mt-1 font-bold"><?= $e($track_labels[$assessment['assessment_track'] ?? ''] ?? 'Belum ditentukan') ?></p></div><div class="rounded-2xl border border-gray-200 p-4 dark:border-white/10"><p class="text-xs text-gray-500">Sumber</p><p class="mt-1 font-bold"><?= ($assessment['source_mode'] ?? '') === 'simulation' ? 'SIMPERUM (simulasi)' : (($assessment['source_mode'] ?? '') === 'api' ? 'SIMPERUM' : $display($assessment['source_mode'] ?? NULL)) ?></p></div></section>
    <?php if ( ! empty($queue['catatan_admin'])): ?><section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"><h2 class="font-black">Catatan admin terakhir</h2><p class="mt-1 whitespace-pre-line"><?= $e($queue['catatan_admin']) ?></p></section><?php endif; ?>

    <section class="rounded-2xl border border-gray-200 p-4 dark:border-white/10"><h2 class="font-black text-gray-900 dark:text-white">Sumber vs koreksi warga</h2><div class="mt-3 overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr class="border-b border-gray-200 text-xs text-gray-500 dark:border-white/10"><th class="py-2">Data</th><th>Sumber SIMPERUM</th><th>Nilai efektif</th><th>Asal nilai</th></tr></thead><tbody><?php foreach ($identity_fields as $key => [$label,$raw,$effective]): ?><tr class="border-b border-gray-100 dark:border-white/5"><th class="py-2 pr-3"><?= $e($label) ?></th><td class="pr-3"><?= $display($raw) ?></td><td class="pr-3"><?= $display($effective) ?></td><td><?= $e(in_array($provenance_source($key), ['simulation', 'api'], TRUE) ? 'SIMPERUM' : 'Koreksi warga') ?></td></tr><?php endforeach; ?></tbody></table></div></section>

    <section class="rounded-2xl border border-gray-200 p-4 dark:border-white/10"><h2 class="font-black text-gray-900 dark:text-white">Data assessment</h2><dl class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3"><?php foreach ($field_labels as $key => $label): if (!array_key_exists($key, $assessment) || $assessment[$key] === NULL || $assessment[$key] === '') continue; ?><div><dt class="text-xs text-gray-500"><?= $e($label) ?></dt><dd class="font-semibold"><?= $display($assessment[$key]) ?></dd></div><?php endforeach; ?></dl></section>

    <section class="rounded-2xl border border-gray-200 p-4 dark:border-white/10"><h2 class="font-black text-gray-900 dark:text-white">Rekomendasi ruleset</h2><div class="mt-3 space-y-3"><?php foreach ($recommendations as $item): $reasons = is_array($item['reason_codes'] ?? NULL) ? $item['reason_codes'] : []; ?><article class="rounded-xl bg-gray-50 p-3 dark:bg-white/5"><div class="flex justify-between gap-3"><strong><?= $display($item['program_name'] ?? NULL) ?><?php if (! empty($item['is_selected'])): ?> <span class="ml-1 rounded bg-brand-primary/15 px-2 py-0.5 text-[10px] text-brand-primary">Dipilih warga</span><?php endif; ?></strong><span class="text-xs font-bold"><?= $e($item['eligibility_status'] ?? '') ?></span></div><p class="mt-1 text-xs text-gray-500">Ruleset <?= $display($item['ruleset_version'] ?? NULL) ?></p><ul class="mt-2 text-xs text-gray-600 dark:text-brand-muted"><?php foreach ($reasons as $reason): ?><li>• <?= $e($reason_labels[$reason] ?? $reason) ?></li><?php endforeach; ?></ul></article><?php endforeach; ?><?php if (!$recommendations): ?><p class="text-sm text-gray-500">Tidak ada rekomendasi tersimpan.</p><?php endif; ?></div></section>

    <section class="rounded-2xl border border-gray-200 p-4 dark:border-white/10"><h2 class="font-black text-gray-900 dark:text-white">Bukti privat</h2><div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3"><?php foreach ($evidence as $file): ?><a href="<?= base_url(($evidence_url ?? 'Admin/evidence') . '/' . (int) ($queue['id'] ?? 0) . '/' . rawurlencode($file['file_kind'])) ?>" class="rounded-xl border border-gray-200 p-3 text-sm font-bold text-brand-primary dark:border-white/10">Lihat <?= $e($file['file_kind']) ?><span class="block text-xs font-normal text-gray-500"><?= number_format(((int) ($file['size_bytes'] ?? 0)) / 1024, 0, ',', '.') ?> KB</span></a><?php endforeach; ?><?php if (!$evidence): ?><p class="text-sm text-gray-500">Belum ada bukti.</p><?php endif; ?></div></section>

    <?php if (in_array($queue['status_antrean'] ?? '', ['pending'], TRUE)): ?><section class="rounded-2xl border border-gray-200 p-4 dark:border-white/10"><h2 class="font-black text-gray-900 dark:text-white">Keputusan admin</h2><form method="post" action="<?= base_url($action_url ?? 'Admin/update_status') ?>" class="mt-3 space-y-3"><input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>"><input type="hidden" name="queue_id" value="<?= (int) ($queue['id'] ?? 0) ?>"><input type="hidden" name="from_status" value="<?= $e($queue['status_antrean'] ?? '') ?>"><fieldset><legend class="text-xs font-bold">Keputusan</legend><div class="mt-2 flex flex-wrap gap-4"><label><input type="radio" name="status" value="approved" required> Setujui</label><label><input type="radio" name="status" value="needs_revision" required> Minta perbaikan</label><label><input type="radio" name="status" value="rejected" required> Tolak</label></div></fieldset><div><label for="catatan_admin" class="text-xs font-bold">Catatan admin</label><textarea id="catatan_admin" name="catatan_admin" rows="3" class="mt-1 w-full rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-white/10 dark:bg-black/20" placeholder="Wajib untuk permintaan perbaikan atau penolakan"></textarea></div><button class="rounded-xl bg-brand-primary px-4 py-2 text-sm font-bold text-brand-dark">Simpan keputusan</button></form></section><?php endif; ?>
</div>
