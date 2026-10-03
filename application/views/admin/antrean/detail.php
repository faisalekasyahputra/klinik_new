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
    'kepemilikan_rumah' => 'Status rumah', 'kepemilikan_lahan' => 'Status lahan', 'jml_penghuni' => 'Jumlah penghuni', 'jml_kk' => 'Jumlah keluarga',
    'luas_rumah' => 'Luas rumah (m²)', 'kawasan_perumahan' => 'Kawasan', 'punya_lahan_calon' => 'Memiliki calon lahan',
    'candidate_land_address' => 'Alamat calon lahan', 'status_lahan_calon' => 'Sertifikat calon lahan', 'asal_lahan_calon' => 'Asal tanah',
    'hubungan_pemilik_lahan' => 'Hubungan dengan pemilik', 'panjang_lahan_m' => 'Panjang tanah (m)', 'lebar_lahan_m' => 'Lebar tanah (m)',
    'kondisi_pondasi' => 'Pondasi', 'kondisi_kolom' => 'Kolom', 'kondisi_balok' => 'Balok', 'kondisi_rangka' => 'Rangka atap',
    'bahan_lantai' => 'Bahan lantai', 'kondisi_lantai' => 'Kondisi lantai', 'bahan_dinding' => 'Bahan dinding', 'kondisi_dinding' => 'Kondisi dinding',
    'bahan_atap' => 'Bahan atap', 'kondisi_atap' => 'Kondisi atap', 'sumber_air' => 'Sumber air', 'jenis_kloset' => 'Jenis jamban',
    'pembuangan_tinja' => 'Jenis TPA', 'jarak_septic_tank' => 'Jarak septik', 'penerangan' => 'Penerangan', 'bahan_bakar_masak' => 'Bahan bakar memasak',
];
/* 7 field "Isi Data Sesuai Matriks" - permintaan user 23 Agt 2026,
   halaman ini dulu tidak menampilkannya sama sekali (field-nya belum
   ada saat halaman ini dibuat). Peta kode->label PERSIS teks yang
   dipakai warga di pendataan.php (step 'housing_family') - sengaja
   disalin, bukan dipusatkan, mengikuti pola $field_labels di atas yang
   juga salinan sendiri dari label sisi warga (bukan inkonsistensi baru
   yang diperkenalkan di sini). */
$matriks_field_labels = ['matriks_penghasilan' => 'Gaji', 'matriks_status_dtks' => 'Status DTKS', 'matriks_kepemilikan_lahan' => 'Kepemilikan Lahan', 'matriks_rumah_sekarang' => 'Kepemilikan Rumah Saat Ini', 'matriks_kondisi_lingkungan' => 'Kondisi Lingkungan / Fisik Bangunan', 'matriks_pekerjaan_keuangan' => 'Pekerjaan / Kondisi Finansial', 'matriks_status_keluarga' => 'Status Perkawinan / Keluarga'];
$matriks_value_labels = [
    'matriks_penghasilan' => ['income_0_1_5' => '0 - 1,5 Juta', 'income_1_5_2_2' => '1,5 - 2,2 Juta', 'income_2_2_2_8' => '2,2 - 2,8 Juta', 'income_2_8_8_5' => '2,8 - 8,5 Juta', 'income_2_8_10' => '2,8 - 10 Juta', 'income_gt_8_5' => '> 8,5 Juta', 'income_gt_10' => '> 10 Juta'],
    'matriks_status_dtks' => ['dtks_ya' => 'Terdaftar DTKS', 'dtks_belum' => 'Belum Terdaftar DTKS'],
    'matriks_kepemilikan_lahan' => ['land_none' => 'Tidak Punya', 'land_legal' => 'Punya Lahan Sah'],
    'matriks_rumah_sekarang' => ['house_none_or_rent' => 'Belum Punya / Numpang / Sewa', 'house_rent_or_staying' => 'Menumpang / Sewa', 'house_restricted_area' => 'Tinggal di Area Terlarang / Numpang', 'house_disaster_affected' => 'Punya / Terdampak Bencana', 'house_owned' => 'Punya Rumah Sendiri'],
    'matriks_kondisi_lingkungan' => ['env_safe' => 'Aman / Tidak Terdampak Bencana', 'env_relocation_zone' => 'Kawasan Relokasi Pemerintah (Rusunawa, Sempadan Sungai, Kumuh)', 'env_disaster_severe' => 'Terdampak Bencana: Kerusakan Berat / Roboh', 'env_disaster_moderate' => 'Terdampak Bencana: Kerusakan Sedang (30-70%)', 'env_slum_uninhabitable' => 'Kumuh / Tidak Layak: Atap, Lantai, Dinding Jelek/Rusak'],
    'matriks_pekerjaan_keuangan' => ['work_stable_or_unstable_no_subsidy' => 'Berpenghasilan Tetap/Tidak Tetap (Belum Pernah Dapat Subsidi)', 'work_can_save_irregular' => 'Mampu Menabung / Penghasilan Tidak Tetap'],
    'matriks_status_keluarga' => ['family_single' => 'Belum Menikah', 'family_married' => 'Menikah', 'family_multi_household' => 'Dihuni > 1 KK (Kepala Keluarga)', 'family_head_of_household' => 'Kepala Keluarga (Menikah / Duda / Janda)'],
];
$identity_fields = [
    'full_name' => ['Nama lengkap', $raw_identity['full_name'] ?? NULL, $profile['full_name'] ?? NULL],
    'address' => ['Alamat', $raw_identity['address'] ?? NULL, $profile['address'] ?? NULL],
    'birth_date' => ['Tanggal lahir', $raw_identity['birth_date'] ?? NULL, $profile['birth_date'] ?? NULL],
    'family_card_number' => ['Nomor KK', $mask($raw_identity['family_card_number'] ?? ''), $mask($profile['family_card_number'] ?? '')],
    'phone' => ['Nomor HP', $mask($raw_identity['phone'] ?? ''), $mask($profile['phone'] ?? '')],
    'desil_kesejahteraan' => ['Desil', $raw_socioeconomic['desil_kesejahteraan'] ?? NULL, $profile['desil_kesejahteraan'] ?? NULL],
];
$provenance_source = static function ($field) use ($provenance) {
    $value = $provenance[$field] ?? 'citizen';
    return is_array($value) ? ($value['source'] ?? 'citizen') : $value;
};
/* Kode isian ditampilkan dengan label yang sama dengan formulir warga
   (pages/warga/pendataan.php), disalin seperti $field_labels di atas, supaya
   petugas tidak membaca kode mentah seperti "hm" atau "inheritance". Kode yang
   tidak dikenal tetap tampil apa adanya, bukan disembunyikan. */
$kondisi = ['good' => 'Baik', 'minor_damage' => 'Rusak Ringan (Permukaan)', 'moderate_damage' => 'Rusak Sedang (Material)', 'severe_damage_or_absent' => 'Rusak Berat (Struktur/Tdk Ada)'];
$sertifikat = ['certificate_unspecified' => 'Sertifikat (jenis tidak disebut SIMPERUM)', 'hm' => 'Sertifikat HM', 'hgb' => 'Sertifikat HGB', 'letter_c' => 'Letter C', 'letter_d' => 'Letter D', 'village_letter' => 'Suket Desa', 'notarial_deed' => 'Akta Notaris', 'other' => 'Lainnya'];
$value_labels = [
    'kepemilikan_rumah' => ['owned' => 'Milik Sendiri', 'rent' => 'Sewa/Kontrak', 'rent_free' => 'Bebas Sewa', 'official' => 'Rumah Dinas', 'staying' => 'Menumpang', 'other' => 'Bukan milik sendiri'],
    'kepemilikan_lahan' => $sertifikat, 'status_lahan_calon' => $sertifikat,
    'kawasan_perumahan' => ['drought' => 'Kekeringan', 'slum' => 'Kumuh', 'disaster_prone' => 'Rawan bencana', 'riverbank' => 'Bantaran sungai', 'railway' => 'Bantaran rel KA', 'poor_other' => 'Kawasan buruk lain', 'good' => 'Kawasan baik'],
    'punya_lahan_calon' => ['1' => 'Ya', '0' => 'Tidak'],
    'asal_lahan_calon' => ['owned' => 'Milik Sendiri', 'inheritance' => 'Warisan', 'grant' => 'Hibah', 'purchase' => 'Jual Beli'],
    'hubungan_pemilik_lahan' => ['parent' => 'Orang Tua', 'other' => 'Orang Lain'],
    'kondisi_pondasi' => $kondisi, 'kondisi_kolom' => $kondisi, 'kondisi_balok' => $kondisi, 'kondisi_rangka' => $kondisi,
    'kondisi_lantai' => $kondisi, 'kondisi_dinding' => $kondisi, 'kondisi_atap' => $kondisi,
    'bahan_lantai' => ['marble_granite' => 'Marmer/Granit', 'ceramic' => 'Keramik', 'parquet_vinyl_carpet' => 'Parket/Vinil/Permadani', 'tile_terrazzo' => 'Ubin/Tegel/Teraso', 'high_quality_wood' => 'Kayu/Papan Kualitas Tinggi', 'cement_plaster' => 'Semen/Plesteran', 'bamboo' => 'Bambu', 'low_quality_wood' => 'Kayu/Papan Kualitas Rendah', 'soil' => 'Tanah', 'other' => 'Lainnya'],
    'bahan_dinding' => ['wall' => 'Tembok', 'plaster_grc' => 'Plesteran/GRC', 'wood' => 'Kayu', 'woven_bamboo' => 'Anyaman Bambu', 'log' => 'Batang Kayu', 'bamboo' => 'Bambu', 'other' => 'Lainnya'],
    'bahan_atap' => ['concrete' => 'Beton', 'ceramic' => 'Keramik', 'metal' => 'Metal', 'clay_tile' => 'Genteng/Tanah Liat', 'asbestos' => 'Asbes', 'zinc' => 'Seng', 'shingle' => 'Sirap', 'bamboo' => 'Bambu', 'thatch' => 'Jerami/Ijuk/Daun/Rumbia', 'other' => 'Lainnya'],
    'sumber_air' => ['bottled' => 'Air Kemasan Bermerek', 'refill' => 'Air Isi Ulang', 'piped' => 'Ledeng (jenis tidak disebut SIMPERUM)', 'pdam' => 'PDAM', 'retail_piped' => 'Leding Eceran', 'well' => 'Sumur', 'well_protected' => 'Sumur Terlindung', 'well_unprotected' => 'Sumur Tak Terlindung', 'spring' => 'Mata Air', 'spring_unprotected' => 'Mata Air Tak Terlindung', 'surface_water' => 'Air Sungai/Danau/Waduk', 'rain' => 'Air Hujan', 'other_unfit' => 'Lainnya/Tidak Layak'],
    'jenis_kloset' => ['swan_neck' => 'Leher Angsa', 'plengsengan' => 'Plengsengan', 'pit' => 'Cemplung/Cubluk', 'none' => 'Tidak Punya'],
    'pembuangan_tinja' => ['septic_tank' => 'Tangki Septik', 'ipal' => 'IPAL', 'water_body' => 'Kolam/Sawah/Sungai', 'ground_hole' => 'Lubang Tanah', 'open_land' => 'Pantai/Tanah Lapang/Kebun'],
    'jarak_septic_tank' => ['lt_10' => '<10 m', 'gte_10' => '>=10 m'],
    'penerangan' => ['pln' => 'PLN', 'pln_unmetered' => 'PLN Non Meteran', 'non_pln' => 'Non PLN', 'none' => 'Bukan Listrik'],
    'bahan_bakar_masak' => ['electric_gas' => 'Listrik/Gas', 'kerosene' => 'Minyak Tanah', 'charcoal_wood' => 'Arang/Kayu', 'other' => 'Lainnya'],
];
$numeric_fields = ['jml_penghuni', 'jml_kk', 'luas_rumah', 'panjang_lahan_m', 'lebar_lahan_m'];
// "12.00" -> "12", "7.50" -> "7,5": desimal koma, nol ekor dibuang.
$angka = static function ($v) { return rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ','); };
$field_value = static function ($key, $v) use ($value_labels, $numeric_fields, $angka) {
    if (in_array($key, $numeric_fields, TRUE) && is_numeric($v)) { return $angka($v); }
    return $value_labels[$key][(string) $v] ?? $v;
};
$eligibility_labels = ['eligible' => 'Memenuhi simulasi', 'potential' => 'Berpotensi memenuhi', 'not_eligible' => 'Belum memenuhi', 'needs_data' => 'Data masih diperlukan'];
$eligibility_styles = ['eligible' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300', 'potential' => 'bg-sky-100 text-sky-800 dark:bg-sky-500/15 dark:text-sky-300', 'not_eligible' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-300', 'needs_data' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300'];
$evidence_labels = ['self_photo' => 'Foto Diri', 'house_front_photo' => 'Rumah Depan', 'house_side_photo' => 'Rumah Samping', 'roof_photo' => 'Atap', 'floor_photo' => 'Lantai', 'wall_photo' => 'Dinding', 'latrine_photo' => 'Jamban', 'candidate_land_photo' => 'Foto Lahan', 'land_transfer_proof' => 'Bukti Pindah Tangan', 'id_card_photo' => 'Foto KTP', 'family_card_photo' => 'Foto KK'];
$tanggal = static function ($v) { return $v === NULL || $v === '' ? NULL : tgl_id($v); };
$identity_fields['birth_date'][1] = $tanggal($identity_fields['birth_date'][1]);
$identity_fields['birth_date'][2] = $tanggal($identity_fields['birth_date'][2]);
$mode_sumber = $assessment['mode_sumber'] ?? '';
// Lencana mengikuti mode koneksi yang aktif (lihat catatan banner di antrean/dashboard.php).
$this->config->load('simperum', FALSE, TRUE);
$lencana = ['simulation' => $this->config->item('simperum_mode') === 'api'
        ? 'Data uji coba: dibuat dengan data simulasi sebelum SIMPERUM tersambung'
        : 'Mode Simulasi: data kependudukan belum tersambung ke SIMPERUM',
    'manual' => 'Diisi mandiri warga, tidak melalui SIMPERUM'][$mode_sumber] ?? '';
?>
<style>
    /* Pilihan keputusan: radio dibungkus label berbingkai, seukuran kontrol form admin. */
    .pilihan-keputusan { display: inline-flex; align-items: center; gap: .5rem; min-height: 2rem; padding: .375rem .75rem; border: 1px solid #e5e7eb; border-radius: .5rem; background: #f9fafb; font-size: .875rem; font-weight: 600; cursor: pointer; }
    .pilihan-keputusan:has(:checked) { border-color: #0ea5e9; background: rgba(14, 165, 233, .08); }
    .pilihan-keputusan input { accent-color: #0ea5e9; }
    .dark .pilihan-keputusan { border-color: rgba(255, 255, 255, .1); background: rgba(0, 0, 0, .2); }
    @media (max-width: 767px) { .pilihan-keputusan { min-height: 40px; } }
</style>
<?php $this->load->view('admin/components/judul_halaman', [
    'jh_judul' => 'Detail Penilaian Warga',
    'jh_deskripsi' => $e($queue['kode_tiket'] ?? '') . ' &middot; Versi ' . (int) ($assessment['no_versi'] ?? 1)
        . ($lencana !== '' ? ' <span class="ml-1 inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-500/15 dark:text-amber-300">' . $e($lencana) . '</span>' : ''),
    'jh_aksi' => '<a href="' . base_url($back_url ?? 'Admin') . '" class="tombol-kedua"><i class="ph ph-arrow-left"></i><span>Kembali ke antrean</span></a>',
]); ?>
<div class="tumpuk-bagian">
    <div class="grid-kartu grid sm:grid-cols-4">
        <div class="kartu-admin isi-kartu"><p class="text-xs text-gray-500 dark:text-brand-muted">Status</p><p class="mt-1 font-bold text-gray-900 dark:text-white"><?= $e($status_labels[$queue['status_antrean'] ?? ''] ?? ($queue['status_antrean'] ?? '-')) ?></p></div>
        <div class="kartu-admin isi-kartu"><p class="text-xs text-gray-500 dark:text-brand-muted">Program</p><p class="mt-1 font-bold text-gray-900 dark:text-white"><?= $display($queue['program_name'] ?? $queue['nama_program'] ?? NULL) ?></p></div>
        <div class="kartu-admin isi-kartu"><p class="text-xs text-gray-500 dark:text-brand-muted">Cabang</p><p class="mt-1 font-bold text-gray-900 dark:text-white"><?= $e($track_labels[$assessment['jalur_penilaian'] ?? ''] ?? 'Belum ditentukan') ?></p></div>
        <div class="kartu-admin isi-kartu"><p class="text-xs text-gray-500 dark:text-brand-muted">Sumber</p><p class="mt-1 font-bold text-gray-900 dark:text-white"><?= $mode_sumber === 'simulation' ? 'SIMPERUM (simulasi)' : ($mode_sumber === 'api' ? 'SIMPERUM' : ($mode_sumber === 'manual' ? 'Diisi mandiri oleh warga' : $display($mode_sumber ?: NULL))) ?></p><p class="mt-1 text-xs font-semibold <?= ! empty($nik_terverifikasi) ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300' ?>" title="Terverifikasi = nama akun dan tanggal lahir pemohon cocok dengan data SIMPERUM untuk NIK ini"><?= ! empty($nik_terverifikasi) ? 'NIK terverifikasi' : 'NIK belum terverifikasi' ?></p></div>
    </div>
    <?php if ( ! empty($queue['catatan_admin'])): ?><section class="kartu-admin isi-kartu border-l-4 border-l-amber-400 text-sm"><h2 class="font-black text-amber-800 dark:text-amber-300">Catatan admin terakhir</h2><p class="mt-1 whitespace-pre-line text-gray-800 dark:text-gray-200"><?= $e($queue['catatan_admin']) ?></p></section><?php endif; ?>

    <section class="kartu-admin isi-kartu"><h2 class="font-black text-gray-900 dark:text-white">Sumber vs koreksi warga</h2><div class="mt-3 overflow-x-auto"><table class="w-full text-left text-sm"><thead><tr class="border-b border-gray-200 text-xs text-gray-500 dark:border-white/10 dark:text-brand-muted"><th class="py-2">Data</th><th>Sumber SIMPERUM</th><th>Nilai efektif</th><th>Asal nilai</th></tr></thead><tbody><?php foreach ($identity_fields as $key => [$label,$raw,$effective]): ?><tr class="border-b border-gray-100 dark:border-white/5"><th class="py-2 pr-3"><?= $e($label) ?></th><td class="pr-3"><?= $display($raw) ?></td><td class="pr-3"><?= $display($effective) ?></td><td><?= $e(in_array($provenance_source($key), ['simulation', 'api'], TRUE) ? 'SIMPERUM' : 'Koreksi warga') ?></td></tr><?php endforeach; ?></tbody></table></div></section>

    <?php
    /* Dua section di bawah BARU 23 Agt 2026 - permintaan user
       ("apakah admin bisa mengelolanya?" -> "ya kerjakan"). Sebelum ini
       7 field "Isi Data Sesuai Matriks" tersimpan di DB tapi TIDAK
       ditampilkan sama sekali di halaman ini - lihat riwayat sesi. */
    ?>
    <section class="kartu-admin isi-kartu"><h2 class="font-black text-gray-900 dark:text-white">Isi Data Sesuai Matriks</h2><dl class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3"><?php foreach ($matriks_field_labels as $key => $label): $val = $assessment[$key] ?? NULL; if ($val === NULL || $val === '') continue; $val_label = $matriks_value_labels[$key][$val] ?? $val; ?><div><dt class="text-xs text-gray-500 dark:text-brand-muted"><?= $e($label) ?></dt><dd class="font-semibold"><?= $display($val_label) ?></dd></div><?php endforeach; ?></dl><?php if (empty(array_filter($matriks_field_labels, static function ($l, $k) use ($assessment) { return isset($assessment[$k]) && $assessment[$k] !== ''; }, ARRAY_FILTER_USE_BOTH))): ?><p class="mt-2 text-sm text-gray-500 dark:text-brand-muted">Warga belum mengisi langkah ini.</p><?php endif; ?></section>

    <section class="kartu-admin isi-kartu">
        <h2 class="font-black text-gray-900 dark:text-white">Rekomendasi awal yang tersimpan</h2>
        <?php $this->load->view('pages/warga/matrix_result', ['matrix_result'=>$preliminary_matrix ?? NULL]); ?>
    </section>

    <section class="kartu-admin isi-kartu"><h2 class="font-black text-gray-900 dark:text-white">Data penilaian</h2><dl class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3"><?php foreach ($field_labels as $key => $label): if (!array_key_exists($key, $assessment) || $assessment[$key] === NULL || $assessment[$key] === '') continue; ?><div><dt class="text-xs text-gray-500 dark:text-brand-muted"><?= $e($label) ?></dt><dd class="font-semibold"><?= $display($field_value($key, $assessment[$key])) ?></dd></div><?php endforeach; ?></dl></section>

    <?php // Versi aturan (mis. SIM-2026-01) tidak ditampilkan: kode internal, tidak membantu keputusan petugas. ?>
    <section class="kartu-admin isi-kartu"><h2 class="font-black text-gray-900 dark:text-white">Rekomendasi program</h2><div class="mt-3 space-y-3"><?php foreach ($recommendations as $item): $reasons = is_array($item['reason_codes'] ?? NULL) ? $item['reason_codes'] : []; $st = (string) ($item['status_kelayakan'] ?? 'needs_data'); ?><article class="rounded-xl bg-gray-50 p-3 dark:bg-white/5"><div class="flex justify-between gap-3"><strong><?= $display($item['program_name'] ?? NULL) ?><?php if (! empty($item['is_selected'])): ?> <span class="ml-1 rounded bg-brand-primary/15 px-2 py-0.5 text-[10px] text-brand-primary">Dipilih warga</span><?php endif; ?></strong><span class="h-fit shrink-0 rounded-full px-2 py-0.5 text-xs font-bold <?= $eligibility_styles[$st] ?? $eligibility_styles['needs_data'] ?>"><?= $e($eligibility_labels[$st] ?? 'Status belum dikenal') ?></span></div><ul class="mt-2 text-xs text-gray-600 dark:text-brand-muted"><?php foreach ($reasons as $reason): ?><li>• <?= $e($reason_labels[$reason] ?? 'Alasan evaluasi tersedia pada catatan server.') ?></li><?php endforeach; ?></ul></article><?php endforeach; ?><?php if (!$recommendations): ?><p class="text-sm text-gray-500 dark:text-brand-muted">Tidak ada rekomendasi tersimpan.</p><?php endif; ?></div></section>

    <section class="kartu-admin isi-kartu"><h2 class="font-black text-gray-900 dark:text-white">Bukti privat</h2><div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3"><?php foreach ($evidence as $file): $nama_bukti = $evidence_labels[$file['jenis_berkas']] ?? $file['jenis_berkas']; ?><a href="<?= base_url(($evidence_url ?? 'Admin/evidence') . '/' . (int) ($queue['id'] ?? 0) . '/' . rawurlencode($file['jenis_berkas'])) ?>" data-file-view data-file-title="<?= $e($nama_bukti) ?>" class="rounded-xl border border-gray-200 p-3 text-sm font-bold text-brand-primary dark:border-white/10">Lihat <?= $e($nama_bukti) ?><span class="block text-xs font-normal text-gray-500 dark:text-brand-muted"><?= number_format(((int) ($file['ukuran_byte'] ?? 0)) / 1024, 0, ',', '.') ?> KB</span></a><?php endforeach; ?><?php if (!$evidence): ?><p class="text-sm text-gray-500 dark:text-brand-muted">Belum ada bukti.</p><?php endif; ?></div></section>

    <?php if (in_array($queue['status_antrean'] ?? '', ['pending'], TRUE)): ?><section class="kartu-admin isi-kartu"><h2 class="font-black text-gray-900 dark:text-white">Keputusan admin</h2><form method="post" action="<?= base_url($action_url ?? 'Admin/update_status') ?>" class="mt-3 space-y-3"><input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>"><input type="hidden" name="antrean_id" value="<?= (int) ($queue['id'] ?? 0) ?>"><input type="hidden" name="status_awal" value="<?= $e($queue['status_antrean'] ?? '') ?>"><?php /* Catatan wajib hanya untuk perbaikan atau tolak, sama dengan aturan server; dipasang lewat onchange di fieldset. */ ?><fieldset onchange="this.form.catatan_admin.required = event.target.value !== 'approved'"><legend class="text-xs font-bold text-gray-700 dark:text-gray-300">Keputusan</legend><div class="mt-2 flex flex-wrap gap-2"><label class="pilihan-keputusan"><input type="radio" name="status" value="approved" required> Setujui</label><label class="pilihan-keputusan"><input type="radio" name="status" value="needs_revision" required> Minta perbaikan</label><label class="pilihan-keputusan"><input type="radio" name="status" value="rejected" required> Tolak</label></div></fieldset><div><label for="catatan_admin" class="text-xs font-bold text-gray-700 dark:text-gray-300">Catatan admin</label><textarea id="catatan_admin" name="catatan_admin" rows="3" class="mt-1 w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-800 dark:border-white/10 dark:bg-black/20 dark:text-white" placeholder="Wajib untuk permintaan perbaikan atau penolakan"></textarea></div><button class="tombol-utama"><i class="ph ph-floppy-disk"></i><span>Simpan keputusan</span></button></form></section><?php endif; ?>
</div>
