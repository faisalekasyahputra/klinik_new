<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/* Kontrak aman: controller boleh mengirim array; view tetap dapat dirender saat data belum ada. */
$step_slug = isset($step) && is_string($step) ? $step : 'find_data';
/* citizen_data dipisah ke bucket sendiri (1) - bukan lagi digabung dengan
   housing_family di bucket "Isi Data Sesuai Matriks". Sesuai permintaan
   user, judulnya (baris ~151) sudah "Lengkapi Data SIMPERUM — Data Warga",
   jadi label stepper untuk bucket ini pun disamakan (lihat $step_names).
   Bucket-nya TIDAK digabung jadi satu nomor dengan bucket 4 (housing_family_detail
   dst, label sama) karena keduanya tidak bersebelahan secara urutan wizard -
   housing_family & preliminary_recommendation ada DI ANTARANYA. Menyamakan
   nomor bucket akan merusak logika centang selesai ($complete = $number < $step)
   karena nomor step harus naik monoton mengikuti urutan wizard sebenarnya. */
$step_index = ['find_data' => 0, 'citizen_data' => 1, 'housing_family' => 2, 'preliminary_recommendation' => 3, 'housing_family_detail' => 4, 'building_or_land' => 4, 'building_condition' => 4, 'candidate_land' => 4, 'sanitation_utilities' => 4, 'sanitation' => 4, 'location_evidence' => 4, 'review_recommendation' => 4, 'review' => 4];
$step = $step_index[$step_slug] ?? 0;
$step_slug = array_key_exists($step_slug, $step_index) ? $step_slug : 'find_data';
$assessment = isset($assessment) && is_array($assessment) ? $assessment : [];
$profile = isset($profile) && is_array($profile) ? $profile : [];
$values = isset($values) && is_array($values) ? $values : array_merge($assessment, $profile);
$errors = isset($errors) && is_array($errors) ? $errors : [];
$lookup = isset($lookup) && is_array($lookup) ? $lookup : [];
$evidence_files = isset($evidence_files) && is_array($evidence_files) ? $evidence_files : [];
$recommendations = isset($recommendations) && is_array($recommendations) ? $recommendations : [];
$matriks_recommendation = isset($matriks_recommendation) && is_array($matriks_recommendation) ? $matriks_recommendation : [];
$matriks_decile_label = isset($matriks_decile_label) && is_string($matriks_decile_label) ? $matriks_decile_label : null;
$matriks_data_simperum = isset($matriks_data_simperum) ? $matriks_data_simperum : null; // true/false/null (null = belum ada draft)
$matriks_saran_lengkapi_simperum = isset($matriks_saran_lengkapi_simperum) && $matriks_saran_lengkapi_simperum === TRUE;
$review_summary = isset($review_summary) && is_array($review_summary) ? $review_summary : [];
$action_url = isset($action_url) && is_string($action_url) && $action_url !== '' ? $action_url : base_url('warga/pendataan');
$back_url = isset($back_url) && is_string($back_url) && $back_url !== '' ? $back_url : base_url();
$csrf_name = isset($csrf_name) && is_string($csrf_name) && $csrf_name !== '' ? $csrf_name : (isset($csrf_token_name) && is_string($csrf_token_name) && $csrf_token_name !== '' ? $csrf_token_name : $this->security->get_csrf_token_name());
$csrf_hash = isset($csrf_hash) && is_string($csrf_hash) ? $csrf_hash : $this->security->get_csrf_hash();
$value = static function ($key, $default = '') use ($values) { return (string) ($values[$key] ?? $default); };
$selected = static function ($key, $option) use ($value) { return $value($key) === $option ? ' selected' : ''; };
$checked = static function ($key, $option) use ($value) { return $value($key) === $option ? ' checked' : ''; };
$field_error = static function ($key) use ($errors) { return isset($errors[$key]) ? (string) $errors[$key] : ''; };
$matrix_required = ['income_band_code' => TRUE, 'self_help_capability_code' => TRUE];
/* Field ini menentukan jalur atau evaluasi awal. Badge warga selain daftar
   ini diberi keterangan Opsional agar warga tidak mengira semua wajib. */
$recommendation_required = $matrix_required + [
    'housing_status_code' => TRUE,
    'has_other_house' => TRUE,
    'owns_candidate_land' => TRUE,
];
$has_errors = ! empty($errors);
$step_names = ['Masukkan NIK', 'Lengkapi Data SIMPERUM', 'Isi Data Sesuai Matriks', 'Hasil Rekomendasi', 'Lengkapi Data SIMPERUM'];
$source_label = ['simulation' => 'SIMPERUM (simulasi)', 'api' => 'SIMPERUM', 'citizen' => 'Diisi warga', 'citizen_correction' => 'Koreksi warga', 'admin' => 'Dikoreksi admin'];
$is_simulation = ($assessment['source_mode'] ?? $this->config->item('simperum_mode', 'simperum')) === 'simulation';
$provenance = isset($field_provenance) && is_array($field_provenance) ? $field_provenance : [];
$badge = static function ($field) use ($provenance, $source_label, $recommendation_required) {
    $origin = $provenance[$field] ?? 'citizen';
    $origin = is_array($origin) ? ($origin['source'] ?? 'citizen') : $origin;
    $label = $source_label[$origin] ?? 'Diisi warga';
    if ($origin === 'citizen' && empty($recommendation_required[$field])) {
        $label .= ' · Opsional';
    }
    $class = $origin === 'simulation' || $origin === 'api' ? 'background:rgba(14,165,233,.12);color:#0369a1' : ($origin === 'admin' ? 'background:rgba(245,158,11,.14);color:#92400e' : 'background:rgba(16,185,129,.12);color:#047857');
    return '<span class="ml-1 inline-flex rounded-full px-1.5 py-0.5 text-[9px] font-bold" style="' . $class . '">' . html_escape($label) . '</span>';
};
?>

<?php $this->load->view('components/modal_simperum_simulasi'); ?>

<section class="mx-auto max-w-4xl px-1 py-3 sm:px-3 sm:py-5 font-outfit" style="color:var(--portal-text)">
    <section class="mb-10">
        <div class="mb-5 text-center">
            <p class="text-[10px] font-bold uppercase tracking-[.18em]" style="color:var(--teal-bright)">Program Perumahan</p>
            <h1 class="mt-1 text-2xl font-black sm:text-3xl">Kenali Program Sebelum Cek Kelayakan</h1>
            <p class="mt-2 text-xs sm:text-sm" style="color:var(--portal-text-muted)">Lihat pilihan program, lalu lengkapi pendataan untuk mendapatkan rekomendasi simulasi.</p>
        </div>
        <?php $this->load->view('components/program_showcase_carousel', ['carousel_context' => 'warga']); ?>
    </section>

    <header class="mb-5">
        <p class="text-[10px] font-bold uppercase tracking-[.18em]" style="color:var(--teal-bright)">Pendataan Warga</p>
        <h1 class="mt-1 text-xl font-black sm:text-2xl"><?= html_escape(isset($title) && is_string($title) ? $title : 'Data untuk rekomendasi perumahan') ?></h1>
        <p class="mt-1 text-xs leading-relaxed" style="color:var(--portal-text-muted)">Simpan setiap langkah. Data Anda dapat dilanjutkan setelah masuk kembali.</p>
        <?php if ($is_simulation): ?><p class="mt-3 rounded-xl border p-3 text-xs" style="background:rgba(245,158,11,.1);border-color:rgba(245,158,11,.25);color:#92400e"><strong>Mode Simulasi - API SIMPERUM belum terhubung.</strong> Data dan rekomendasi pada alur ini bukan keputusan bantuan resmi.</p><?php endif; ?>
    </header>

    <ol class="mb-6 grid grid-cols-2 gap-2 sm:grid-cols-5 lg:grid-cols-5" aria-label="Tahapan pendataan">
        <?php foreach ($step_names as $number => $name): ?>
            <?php $active = $number === $step; $complete = $number < $step; $available = $number < count($step_names); ?>
            <li class="rounded-xl border p-2 <?= $active ? 'ring-1' : '' ?>" style="border-color:<?= $active ? 'var(--teal-bright)' : 'var(--portal-border)' ?>;background:<?= $active ? 'rgba(0,163,181,.08)' : 'var(--portal-bg-card)' ?>">
                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full text-[10px] font-black" style="background:<?= $complete || $active ? 'var(--teal-bright)' : 'var(--portal-btn-bg)' ?>;color:<?= $complete || $active ? '#06333b' : 'var(--portal-text-muted)' ?>"><?= $complete ? '✓' : $number + 1 ?></span>
                <span class="mt-1 block text-[10px] font-bold leading-tight" style="color:var(--portal-text)"><?= html_escape($name) ?></span>
                <?php if (! $available): ?><span class="mt-1 block text-[9px]" style="color:var(--portal-text-muted)">Akan dilengkapi</span><?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>

    <?php if ($has_errors): ?>
        <div class="mb-4 rounded-xl border p-3 text-xs" role="alert" style="background:rgba(239,68,68,.08);border-color:rgba(239,68,68,.35);color:#b91c1c">
            <p class="font-bold">Periksa data yang ditandai sebelum melanjutkan.</p>
            <ul class="mt-1 list-inside list-disc"><?php foreach ($errors as $error): ?><li><?= html_escape((string) $error) ?></li><?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <form id="form-pendataan-warga" action="<?= html_escape($action_url) ?>" method="post" enctype="multipart/form-data" class="scroll-mt-4 rounded-2xl border p-4 sm:p-6" style="background:var(--portal-bg-card);border-color:var(--portal-border)">
        <input type="hidden" name="<?= html_escape($csrf_name) ?>" value="<?= html_escape($csrf_hash) ?>">
        <input type="hidden" name="step" value="<?= html_escape($step_slug) ?>">
        <input type="hidden" name="action" value="<?= $step_slug === 'find_data' ? 'lookup' : 'save' ?>">
        <input type="hidden" name="assessment_id" value="<?= (int) ($assessment['id'] ?? $assessment['assessment_id'] ?? 0) ?>">
        <input type="hidden" name="lock_version" value="<?= (int) ($assessment['lock_version'] ?? 0) ?>">

        <?php if ($step === 0): ?>
            <h2 class="text-lg font-black">Masukkan NIK</h2>
            <p class="mt-1 text-xs" style="color:var(--portal-text-muted)">Masukkan NIK. Pencarian dilakukan dari data tersimpan; halaman ini tidak menampilkan data mentah sumber. Satu akun hanya dapat terhubung dengan satu NIK - gunakan NIK Anda sendiri.</p>
            <?php
            /* Tanggal lahir DICABUT dari sini 14 Agt 2026 (keputusan sadar
               user, bukan dinas - beda dengan Cek_Rtlh yang memang keputusan
               dinas). Field-nya SENGAJA tidak pernah menjadi "opsional" -
               dihapus total, konsisten dengan Warga::lookup() yang sekarang
               memanggil gateway dengan $tanpa_tgl_lahir=TRUE. Penggantinya
               BUKAN di layar ini - dua batas laju per-akun (bukan per-NIK)
               di Warga::lookup(), lihat komentarnya. */
            ?>
            <div class="mt-5">
                <div><label for="nik" class="text-xs font-bold">NIK</label><input id="nik" name="nik" inputmode="numeric" pattern="[0-9]{16}" maxlength="16" required autocomplete="off" value="<?= html_escape($value('nik')) ?>" aria-describedby="nik-error" class="mt-1 block w-full rounded-xl border px-3 py-2.5 text-sm" style="background:var(--portal-btn-bg);border-color:<?= $field_error('nik') ? '#dc2626' : 'var(--portal-border)' ?>;color:var(--portal-text)"><p id="nik-error" class="mt-1 text-xs text-red-700"><?= html_escape($field_error('nik')) ?></p></div>
            </div>
            <?php
            /* Hasil pencarian ANONIM (14 Agt 2026) - pengunjung belum login
               yang NIK-nya ditemukan. NIK-nya sendiri sudah tersimpan di
               session (Warga::lookup_anonim()), belum tertulis ke DB apa
               pun - baru benar-benar terikat begitu masuk/daftar (lihat
               Auth::_redirect_after_login()). Tombol di sini murni navigasi,
               bukan submit apa pun. */
            ?>
            <?php if (($lookup['status'] ?? '') === 'found_anonymous'): ?>
            <div class="mt-4 rounded-xl p-4 text-sm" style="background:rgba(14,165,233,.09);color:#075985">
                <p><?= html_escape($lookup['message'] ?? '') ?></p>
                <div class="mt-3 flex flex-wrap gap-3">
                    <a href="<?= base_url('Auth/login') ?>" class="rounded-xl px-4 py-2.5 text-center text-sm font-black" style="background:var(--portal-brand);color:#06333b">Masuk</a>
                    <a href="<?= base_url('Auth/register') ?>" class="rounded-xl border px-4 py-2.5 text-center text-sm font-bold" style="border-color:var(--portal-border);color:var(--portal-text)">Daftar Akun</a>
                </div>
            </div>
            <?php endif; ?>
            <?php
            /* Warga yang SUDAH login tapi NIK-nya tidak ada di SIMPERUM -
               janji pesan Simperum_gateway ("Silakan isi data secara
               manual") baru benar-benar bisa ditempuh lewat kotak ini.
               NIK-nya sudah terisi otomatis dari warga_old_input (lihat
               Warga::lookup()), tidak perlu diketik ulang - warga cuma
               perlu mengonfirmasi nama lengkap untuk mulai draft manual. */
            ?>
            <?php if (($lookup['status'] ?? '') === 'not_found'): ?>
            <div class="mt-4 rounded-xl p-4 text-sm" style="background:rgba(217,119,6,.09);color:#92400e">
                <p><?= html_escape($lookup['message'] ?? 'Data tidak ditemukan di SIMPERUM.') ?></p>
                <div class="mt-3">
                    <input type="hidden" name="nik" value="<?= html_escape($value('nik')) ?>">
                    <label for="isi_manual_full_name" class="text-xs font-bold">Nama lengkap</label>
                    <input id="isi_manual_full_name" name="full_name" type="text" required value="<?= html_escape($value('full_name')) ?>" aria-describedby="isi_manual_full_name-error" class="mt-1 block w-full rounded-xl border px-3 py-2.5 text-sm" style="background:var(--portal-btn-bg);border-color:<?= $field_error('full_name') ? '#dc2626' : 'var(--portal-border)' ?>;color:var(--portal-text)">
                    <p id="isi_manual_full_name-error" class="mt-1 text-xs text-red-700"><?= html_escape($field_error('full_name')) ?></p>
                    <button type="submit" name="manual_entry" value="1" class="mt-3 rounded-xl px-4 py-2.5 text-sm font-black" style="background:var(--portal-brand);color:#06333b">Lanjutkan Isi Manual</button>
                </div>
            </div>
            <?php endif; ?>
        <?php elseif ($step_slug === 'citizen_data'): ?>
            <h2 class="text-lg font-black">Lengkapi Data SIMPERUM — Data Warga</h2>
            <p class="mt-1 text-xs" style="color:var(--portal-text-muted)">Untuk rekomendasi awal, wajib isi hanya <strong>Penghasilan</strong> dan <strong>Kemampuan swadaya</strong>. Isian lain opsional dan dapat dilengkapi kemudian.</p>
            <?php if (! empty($lookup['message'])): ?><p class="mt-3 rounded-xl p-3 text-xs" style="background:rgba(14,165,233,.09);color:#075985"><?= html_escape($lookup['message']) ?></p><?php endif; ?>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <?php $text_fields = [['family_card_number','Nomor KK','text','16 digit'],['full_name','Nama lengkap','text',''],['phone','Nomor HP','tel',''],['birth_date','Tanggal lahir','date',''],['address','Alamat','text','']]; ?>
                <?php foreach ($text_fields as [$key, $label, $type, $hint]): ?><div class="<?= $key === 'address' ? 'sm:col-span-2' : '' ?>"><label for="<?= $key ?>" class="text-xs font-bold"><?= $label ?> <?= $badge($key) ?></label><input id="<?= $key ?>" name="<?= $key ?>" type="<?= $type ?>"  <?= $hint ? 'maxlength="16" inputmode="numeric"' : '' ?> value="<?= html_escape($value($key)) ?>" aria-describedby="<?= $key ?>-error" aria-invalid="<?= $field_error($key) ? 'true' : 'false' ?>" class="mt-1 block w-full rounded-xl border px-3 py-2.5 text-sm" style="background:var(--portal-btn-bg);border-color:<?= $field_error($key) ? '#dc2626' : 'var(--portal-border)' ?>;color:var(--portal-text)"><p id="<?= $key ?>-error" class="mt-1 text-xs text-red-700"><?= html_escape($field_error($key)) ?></p></div><?php endforeach; ?>
                <?php $selects = ['gender_code' => ['Jenis kelamin', ['male' => 'Laki-laki', 'female' => 'Perempuan']], 'marital_status_code' => ['Status perkawinan', ['single' => 'Lajang', 'married' => 'Menikah', 'divorced' => 'Cerai']], 'education_code' => ['Pendidikan', ['no_certificate' => 'Tidak punya ijazah', 'elementary' => 'SD/sederajat', 'junior_high' => 'SMP/sederajat', 'senior_high' => 'SMA/sederajat', 'diploma_1_3' => 'D1/D2/D3', 'bachelor' => 'D4/S1', 'postgraduate' => 'S2/S3']], 'occupation_code' => ['Pekerjaan', ['farmer' => 'Petani', 'horticulture' => 'Hortikultura', 'plantation' => 'Perkebunan', 'capture_fisher' => 'Perikanan tangkap', 'aquaculture_fisher' => 'Perikanan budidaya', 'breeder' => 'Peternak', 'forestry_agriculture_other' => 'Kehutanan & pertanian lain', 'mining' => 'Pertambangan/Penggalian', 'daily_laborer' => 'Buruh harian', 'electricity_gas' => 'Listrik & gas', 'construction_worker' => 'Tukang bangunan', 'trader' => 'Perdagangan', 'hotel_restaurant' => 'Hotel & rumah makan', 'driver' => 'Sopir', 'information_communication' => 'Informasi & komunikasi', 'finance_insurance' => 'Keuangan & asuransi', 'educator' => 'Guru/Dosen', 'health_worker' => 'Dokter/Bidan/Apoteker', 'civil_servant' => 'PNS/BUMN/D', 'scavenger' => 'Pemulung', 'military_police' => 'TNI/POLRI', 'private_employee' => 'Pegawai swasta', 'contract_worker' => 'PHL/PTT', 'retired' => 'Pensiunan', 'unemployed' => 'Tidak bekerja', 'other' => 'Lainnya']], 'income_band_code' => ['Penghasilan', ['lt_1_8' => '< 1,8 jt', '1_9_2_1' => '1,9-2,1 jt', '2_2_2_6' => '2,2-2,6 jt', '2_7_3_1' => '2,7-3,1 jt', '3_2_3_6' => '3,2-3,6 jt', '3_7_4_2' => '3,7-4,2 jt', 'gt_4_2' => '> 4,2 jt (data SIMPERUM)', '4_2_6' => '4,2-6 jt', '6_8' => '6-8 jt', 'gt_8' => '> 8 jt']], 'self_help_capability_code' => ['Kemampuan swadaya', ['capable' => 'Mampu', 'not_capable' => 'Tidak mampu']]]; ?>
                <?php foreach ($selects as $key => [$label, $options]): ?><div><label for="<?= $key ?>" class="text-xs font-bold"><?= $label ?> <?= $badge($key) ?><?= isset($matrix_required[$key]) ? ' <span class="text-red-700">*</span>' : '' ?></label><select id="<?= $key ?>" name="<?= $key ?>" <?= isset($matrix_required[$key]) ? 'required' : '' ?> aria-describedby="<?= $key ?>-error" aria-invalid="<?= $field_error($key) ? 'true' : 'false' ?>" class="mt-1 block w-full rounded-xl border px-3 py-2.5 text-sm" style="background:var(--portal-btn-bg);border-color:<?= $field_error($key) ? '#dc2626' : 'var(--portal-border)' ?>;color:var(--portal-text)"><option value="">Pilih <?= strtolower($label) ?></option><?php foreach ($options as $code => $label): ?><option value="<?= html_escape($code) ?>"<?= $selected($key, $code) ?>><?= html_escape($label) ?></option><?php endforeach; ?></select><p id="<?= $key ?>-error" class="mt-1 text-xs text-red-700"><?= html_escape($field_error($key)) ?></p></div><?php endforeach; ?>
                <?php
                /* Butir A6 revisi dinas: "Desil dari SIMPERUM: 2. Nilai ini tidak
                   dihitung ulang dari penghasilan." - dinas minta dijelaskan.

                   SUMBERNYA IKUT DISEBUT MENGIKUTI MODE, dan itu bukan hiasan.
                   Kalimat "diambil dari data resmi SIMPERUM" menjadi BOHONG saat
                   gateway berjalan mode simulasi - dan per 10 Agt 2026 production
                   memang begitu: `SIMPERUM_MODE` tidak diset sehingga jatuh ke
                   `simulation`, dan seluruh 8 pencarian yang pernah terjadi di sana
                   bermode simulasi. Angka desil adalah hal yang dipercaya orang
                   untuk menilai haknya atas bantuan; menyebutnya "resmi" saat ia
                   berasal dari fixture adalah kekeliruan yang paling mahal di
                   halaman ini. Spanduk mode simulasi di baris 53 memperingatkan
                   hal yang sama - dua kalimat itu tidak boleh saling membantah. */
                ?>
                <?php if ($value('welfare_decile') !== ''): ?><p class="sm:col-span-2 rounded-xl p-3 text-xs" style="background:rgba(14,165,233,.09);color:#075985">Kelompok kesejahteraan (desil) <strong><?= html_escape($value('welfare_decile')) ?></strong>, diambil dari <?= $is_simulation ? '<strong>data simulasi</strong> - SIMPERUM belum terhubung, jadi angka ini contoh, bukan data Anda yang sebenarnya' : 'data resmi SIMPERUM' ?>. Angka ini tidak dihitung ulang dari penghasilan yang Anda isi.</p><?php endif; ?>
            </div>
        <?php elseif ($step_slug === 'housing_family'): ?>
            <?php
            /* Diganti total 23 Agt 2026 - permintaan user: field step ini
               mengikuti persis kolom bertanda '*' di "MATRIKS VARIABEL
               PENENTUAN PROGRAM PERUMAHAN.xlsx" Sheet4 (kolom D-I, kolom
               A-C & J bukan input - A/B/C berasal dari profil/SIMPERUM,
               J adalah hasil rekomendasi, bukan masukan). Field LAMA di
               step ini (Status rumah dkk.) TIDAK dihapus - dipindah ke
               step baru "housing_family_detail" (lihat blok setelah
               'preliminary_recommendation' di bawah) karena masih dipakai
               Warga_ruleset.php + auto-isi SIMPERUM dengan kosakata jawaban
               yang beda dari teks xlsx ini (keputusan eksplisit user).

               Kode pendek tiap pilihan (kolom kedua array di bawah) BUKAN
               dari xlsx - xlsx cuma punya teks panjang di tiap sel. Kode
               ini ciptaan sendiri supaya konsisten dengan pola *_code
               field lain di tabel ini (disimpan pendek, teks panjang
               cuma untuk tampilan) - teks pilihannya sendiri PERSIS
               kutipan dari sel Sheet4, tidak diringkas/ditafsirkan ulang. */
            $matriks = [
                // Ke-7, menyusul terpisah 23 Agt 2026: kolom A xlsx
                // ("Pendapatan / Gaji", tidak bertanda '*' - diminta
                // ditambahkan tersendiri). BUKAN income_band_code yang
                // sudah ada di step "Data Warga" - pita gajinya beda
                // (lihat docblock migrasi 047). Dua pasang pilihan di
                // bawah SENGAJA tumpang tindih (2,8-8,5jt vs 2,8-10jt,
                // >8,5jt vs >10jt) - di baris asli xlsx, batasnya
                // dibedakan oleh Status Perkawinan pada baris yang sama
                // (batas 8,5jt untuk Belum Menikah, 10jt untuk Menikah),
                // bukan duplikasi keliru.
                // Ke-8: kolom C xlsx ("Status DTKS") - dibutuhkan mesin
                // pencocokan 20 baris matriks (lihat komentar di step
                // 'preliminary_recommendation' di bawah), bukan sekadar
                // field tampilan. 16 dari 20 baris xlsx mensyaratkan "YA".
                'matrix_dtks_status' => ['Status DTKS', [
                    'dtks_ya' => 'Terdaftar DTKS',
                    'dtks_belum' => 'Belum Terdaftar DTKS',
                ]],
                'matrix_income_code' => ['Gaji', [
                    'income_0_1_5' => '0 - 1,5 Juta',
                    'income_1_5_2_2' => '1,5 - 2,2 Juta',
                    'income_2_2_2_8' => '2,2 - 2,8 Juta',
                    'income_2_8_8_5' => '2,8 - 8,5 Juta',
                    'income_2_8_10' => '2,8 - 10 Juta',
                    'income_gt_8_5' => '> 8,5 Juta',
                    'income_gt_10' => '> 10 Juta',
                ]],
                /* Opsi "Tidak Dibatasi" DIHAPUS dari semua select 23 Agt
                   2026 - permintaan user: nilai itu di xlsx cuma berarti
                   "baris aturan ini tidak mempedulikan kolom ini", BUKAN
                   keadaan sungguhan yang bisa dialami warga. Warga wajib
                   mendeskripsikan keadaan aslinya; logika "field ini tidak
                   dipedulikan" adalah urusan mesin pencocokan aturan
                   nanti (Warga_ruleset.php/sejenisnya), bukan pilihan yang
                   ditampilkan sebagai jawaban. */
                'matrix_land_ownership_code' => ['Kepemilikan Lahan', [
                    'land_none' => 'Tidak Punya',
                    'land_legal' => 'Punya Lahan Sah',
                ]],
                'matrix_current_housing_code' => ['Kepemilikan Rumah Saat Ini', [
                    'house_none_or_rent' => 'Belum Punya / Numpang / Sewa',
                    'house_rent_or_staying' => 'Menumpang / Sewa',
                    'house_restricted_area' => 'Tinggal di Area Terlarang / Numpang',
                    'house_disaster_affected' => 'Punya / Terdampak Bencana',
                    'house_owned' => 'Punya Rumah Sendiri',
                ]],
                'matrix_environment_condition_code' => ['Kondisi Lingkungan / Fisik Bangunan', [
                    'env_safe' => 'Aman / Tidak Terdampak Bencana',
                    'env_relocation_zone' => 'Kawasan Relokasi Pemerintah (Rusunawa, Sempadan Sungai, Kumuh)',
                    'env_disaster_severe' => 'Terdampak Bencana: Kerusakan Berat / Roboh',
                    'env_disaster_moderate' => 'Terdampak Bencana: Kerusakan Sedang (30-70%)',
                    'env_slum_uninhabitable' => 'Kumuh / Tidak Layak: Atap, Lantai, Dinding Jelek/Rusak',
                ]],
                'matrix_occupation_finance_code' => ['Pekerjaan / Kondisi Finansial', [
                    'work_stable_or_unstable_no_subsidy' => 'Berpenghasilan Tetap/Tidak Tetap (Belum Pernah Dapat Subsidi)',
                    'work_can_save_irregular' => 'Mampu Menabung / Penghasilan Tidak Tetap',
                ]],
                'matrix_marital_family_code' => ['Status Perkawinan / Keluarga', [
                    'family_single' => 'Belum Menikah',
                    'family_married' => 'Menikah',
                    'family_multi_household' => 'Dihuni > 1 KK (Kepala Keluarga)',
                    'family_head_of_household' => 'Kepala Keluarga (Menikah / Duda / Janda)',
                ]],
            ];
            // "Kategori Usia*" - permintaan user: dihitung otomatis dari
            // tanggal lahir yang sudah dikumpulkan di step "Data Warga",
            // BUKAN input baru (supaya tidak bisa keliru isi/tidak
            // konsisten dengan tanggal lahir). Batas 3 kategori PERSIS
            // catatan kaki xlsx Sheet4 ("Kategori Usia dibagi menjadi...").
            $kategori_usia = null;
            $tgl_lahir = $value('birth_date');
            if ($tgl_lahir !== '') {
                try {
                    $umur = (new DateTime($tgl_lahir))->diff(new DateTime('today'))->y;
                    $kategori_usia = $umur < 18 ? 'Non-Produktif Muda (< 18 tahun)'
                        : ($umur < 60 ? 'Produktif (18 – 59 tahun)' : 'Non-Produktif Tua (≥ 60 tahun / Lansia)');
                } catch (Exception $e) { $kategori_usia = null; }
            }
            ?>
            <h2 class="text-lg font-black">Isi Data Sesuai Matriks — Rumah & Keluarga</h2>
            <p class="mt-1 text-xs" style="color:var(--portal-text-muted)">Enam isian ini menentukan rekomendasi awal sesuai matriks program perumahan. Semua wajib dipilih.</p>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <?php foreach ($matriks as $key => [$label, $options]): ?><div><label for="<?= $key ?>" class="text-xs font-bold"><?= html_escape($label) ?> <?= $badge($key) ?></label><select id="<?= $key ?>" name="<?= $key ?>" required aria-describedby="<?= $key ?>-error" aria-invalid="<?= $field_error($key) ? 'true' : 'false' ?>" class="mt-1 block w-full rounded-xl border px-3 py-2.5 text-sm" style="background:var(--portal-btn-bg);border-color:<?= $field_error($key) ? '#dc2626' : 'var(--portal-border)' ?>;color:var(--portal-text)"><option value="">Pilih <?= strtolower(html_escape($label)) ?></option><?php foreach ($options as $code => $opt_label): ?><option value="<?= html_escape($code) ?>"<?= $selected($key, $code) ?>><?= html_escape($opt_label) ?></option><?php endforeach; ?></select><p id="<?= $key ?>-error" class="mt-1 text-xs text-red-700"><?= html_escape($field_error($key)) ?></p></div><?php endforeach; ?>
                <div>
                    <label class="text-xs font-bold">Kategori Usia <span class="ml-1 inline-flex rounded-full px-1.5 py-0.5 text-[9px] font-bold" style="background:rgba(16,185,129,.12);color:#047857">Dihitung otomatis</span></label>
                    <p class="mt-1 block w-full rounded-xl border px-3 py-2.5 text-sm" style="background:var(--portal-btn-bg);border-color:var(--portal-border);color:var(--portal-text)"><?= $kategori_usia !== null ? html_escape($kategori_usia) : 'Isi tanggal lahir di langkah "Data Warga" terlebih dahulu' ?></p>
                </div>
            </div>
        <?php elseif ($step_slug === 'housing_family_detail'): ?>
            <h2 class="text-lg font-black">Lengkapi Data SIMPERUM — Rumah & Keluarga</h2>
            <p class="mt-1 text-xs" style="color:var(--portal-text-muted)">Untuk hasil rekomendasi awal, wajib pilih <strong>Status rumah</strong>. Bila status rumah bukan milik sendiri, pilih juga <strong>Memiliki rumah lain</strong> dan <strong>Memiliki calon lahan</strong>. Isian lain opsional.</p>
            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                <?php $housing = ['housing_status_code' => ['Status rumah', ['owned' => 'Milik sendiri', 'rent' => 'Kontrak/Sewa', 'rent_free' => 'Bebas sewa', 'official' => 'Dinas', 'staying' => 'Menumpang', 'other' => 'Lainnya']], 'land_title_code' => ['Status lahan rumah', ['certificate_unspecified' => 'Sertifikat (jenis tidak disebut SIMPERUM)', 'hm' => 'Sertifikat HM', 'hgb' => 'Sertifikat HGB', 'letter_c' => 'Letter C', 'letter_d' => 'Letter D', 'village_letter' => 'Suket Desa', 'notarial_deed' => 'Akta Notaris', 'other' => 'Lainnya']], 'area_condition_code' => ['Kawasan', ['drought' => 'Kekeringan', 'slum' => 'Kumuh', 'disaster_prone' => 'Rawan bencana', 'riverbank' => 'Bantaran sungai', 'railway' => 'Bantaran rel KA', 'poor_other' => 'Kawasan buruk lain', 'good' => 'Kawasan baik']]]; ?>
                <?php foreach ($housing as $key => [$label, $options]): ?><div><label for="<?= $key ?>" class="text-xs font-bold"><?= $label ?> <?= $badge($key) ?></label><select id="<?= $key ?>" name="<?= $key ?>" <?= $key === 'housing_status_code' ? 'required' : '' ?> aria-describedby="<?= $key ?>-error" aria-invalid="<?= $field_error($key) ? 'true' : 'false' ?>" class="mt-1 block w-full rounded-xl border px-3 py-2.5 text-sm" style="background:var(--portal-btn-bg);border-color:<?= $field_error($key) ? '#dc2626' : 'var(--portal-border)' ?>;color:var(--portal-text)"><option value="">Pilih <?= strtolower($label) ?></option><?php foreach ($options as $code => $label): ?><option value="<?= html_escape($code) ?>"<?= $selected($key, $code) ?>><?= html_escape($label) ?></option><?php endforeach; ?></select><p id="<?= $key ?>-error" class="mt-1 text-xs text-red-700"><?= html_escape($field_error($key)) ?></p></div><?php endforeach; ?>
                <?php foreach (['occupant_count' => 'Jumlah penghuni', 'family_count' => 'Jumlah keluarga', 'house_area_m2' => 'Luas rumah (m²)'] as $key => $label): ?><div><label for="<?= $key ?>" class="text-xs font-bold"><?= $label ?> <?= $badge($key) ?></label><input id="<?= $key ?>" name="<?= $key ?>" type="number" min="<?= $key === 'house_area_m2' ? '0.01' : '1' ?>" step="<?= $key === 'house_area_m2' ? '0.01' : '1' ?>" <?= $key !== 'house_area_m2' ? 'required' : '' ?> value="<?= html_escape($value($key)) ?>" aria-describedby="<?= $key ?>-error" aria-invalid="<?= $field_error($key) ? 'true' : 'false' ?>" class="mt-1 block w-full rounded-xl border px-3 py-2.5 text-sm" style="background:var(--portal-btn-bg);border-color:<?= $field_error($key) ? '#dc2626' : 'var(--portal-border)' ?>;color:var(--portal-text)"><p id="<?= $key ?>-error" class="mt-1 text-xs text-red-700"><?= html_escape($field_error($key)) ?></p></div><?php endforeach; ?>
                <?php foreach (['has_other_land' => 'Memiliki tanah lain', 'has_other_house' => 'Memiliki rumah lain', 'owns_candidate_land' => 'Memiliki calon lahan'] as $key => $label): ?><fieldset><legend class="text-xs font-bold"><?= $label ?> <?= $badge($key) ?></legend><div class="mt-2 flex gap-4 text-xs"><label><input type="radio" name="<?= $key ?>" value="1"<?= $checked($key, '1') ?>> Memiliki</label><label><input type="radio" name="<?= $key ?>" value="0"<?= $checked($key, '0') ?>> Tidak memiliki</label></div><p class="mt-1 text-xs text-red-700"><?= html_escape($field_error($key)) ?></p></fieldset><?php endforeach; ?>
                <div><label for="assistance_source_code" class="text-xs font-bold">Sumber bantuan sebelumnya <span class="font-normal" style="color:var(--portal-text-muted)">(bila ada)</span></label><select id="assistance_source_code" name="assistance_source_code" class="mt-1 block w-full rounded-xl border px-3 py-2.5 text-sm" style="background:var(--portal-btn-bg);border-color:var(--portal-border);color:var(--portal-text)"><option value="">Belum pernah/tidak diisi</option><?php foreach (['apbn_bsps' => 'APBN/BSPS (data SIMPERUM)', 'apbn' => 'APBN', 'apbd_prov' => 'APBD Provinsi', 'apbd_kab' => 'APBD Kab/Kota', 'csr' => 'CSR', 'village_fund' => 'Dana Desa', 'bsps_kl' => 'BSPS-KL', 'bankab' => 'BANKAB', 'baznas' => 'BAZNAS', 'other' => 'Sumber lainnya'] as $code => $label): ?><option value="<?= $code ?>"<?= $selected('assistance_source_code', $code) ?>><?= $label ?></option><?php endforeach; ?></select></div>
                <div><label for="assistance_year" class="text-xs font-bold">Tahun bantuan <span class="font-normal" style="color:var(--portal-text-muted)">(bila ada)</span></label><input id="assistance_year" name="assistance_year" type="number" min="1900" max="<?= date('Y') ?>" step="1" value="<?= html_escape($value('assistance_year')) ?>" class="mt-1 block w-full rounded-xl border px-3 py-2.5 text-sm" style="background:var(--portal-btn-bg);border-color:var(--portal-border);color:var(--portal-text)"></div>
            </div>
            <p class="mt-5 rounded-xl p-3 text-xs" style="background:rgba(14,165,233,.09);color:#075985">Jika jalur calon lahan diperlukan, detail legalitas dan ukuran tanah akan muncul pada langkah berikutnya. Langkah tersebut belum dapat dilompati.</p>
        <?php elseif ($step_slug === 'building_condition'): ?>
            <h2 class="text-lg font-black">Lengkapi Data SIMPERUM — Kondisi Bangunan</h2><p class="mt-1 text-xs" style="color:var(--portal-text-muted)">Isi kondisi rumah yang dinilai. Gunakan kondisi saat ini.</p>
            <?php $condition = ['good'=>'Baik','minor_damage'=>'Rusak Ringan (Permukaan)','moderate_damage'=>'Rusak Sedang (Material)','severe_damage_or_absent'=>'Rusak Berat (Struktur/Tdk Ada)']; $building = ['foundation_condition_code'=>['Pondasi',$condition],'column_condition_code'=>['Kondisi Kolom',$condition],'beam_condition_code'=>['Kondisi Balok',$condition],'sloof_condition_code'=>['Kondisi Sloof',$condition],'ceiling_condition_code'=>['Kondisi Plafon',$condition],'roof_frame_condition_code'=>['Rangka Atap',$condition],'floor_material_code'=>['Bahan Lantai',['marble_granite'=>'Marmer/Granit','ceramic'=>'Keramik','parquet_vinyl_carpet'=>'Parket/Vinil/Permadani','tile_terrazzo'=>'Ubin/Tegel/Teraso','high_quality_wood'=>'Kayu/Papan Kualitas Tinggi','cement_plaster'=>'Semen/Plesteran','bamboo'=>'Bambu','low_quality_wood'=>'Kayu/Papan Kualitas Rendah','soil'=>'Tanah','other'=>'Lainnya']],'floor_condition_code'=>['Kondisi Lantai',$condition],'wall_material_code'=>['Bahan Dinding',['wall'=>'Tembok','plaster_grc'=>'Plesteran/GRC','wood'=>'Kayu','woven_bamboo'=>'Anyaman Bambu','log'=>'Batang Kayu','bamboo'=>'Bambu','other'=>'Lainnya']],'wall_condition_code'=>['Kondisi Dinding',$condition],'roof_material_code'=>['Bahan Atap',['concrete'=>'Beton','ceramic'=>'Keramik','metal'=>'Metal','clay_tile'=>'Genteng/Tanah Liat','asbestos'=>'Asbes','zinc'=>'Seng','shingle'=>'Sirap','bamboo'=>'Bambu','thatch'=>'Jerami/Ijuk/Daun/Rumbia','other'=>'Lainnya']],'roof_condition_code'=>['Kondisi Atap',$condition]]; ?>
            <div class="mt-5 grid gap-4 sm:grid-cols-2"><?php foreach ($building as $key => [$label,$options]): ?><div><label for="<?= $key ?>" class="text-xs font-bold"><?= $label ?></label><select id="<?= $key ?>" name="<?= $key ?>" <?= in_array($key, ['foundation_condition_code','column_condition_code','beam_condition_code','roof_frame_condition_code','floor_condition_code','wall_condition_code','roof_condition_code'], TRUE) ? 'required' : '' ?> aria-describedby="<?= $key ?>-error" aria-invalid="<?= $field_error($key) ? 'true' : 'false' ?>" class="mt-1 block w-full rounded-xl border px-3 py-2.5 text-sm" style="background:var(--portal-btn-bg);border-color:<?= $field_error($key) ? '#dc2626' : 'var(--portal-border)' ?>;color:var(--portal-text)"><option value="">Pilih kondisi</option><?php foreach ($options as $code=>$label): ?><option value="<?= html_escape($code) ?>"<?= $selected($key,$code) ?>><?= html_escape($label) ?></option><?php endforeach; ?></select><p id="<?= $key ?>-error" class="mt-1 text-xs text-red-700"><?= html_escape($field_error($key)) ?></p></div><?php endforeach; ?></div>
        <?php elseif ($step_slug === 'candidate_land'): ?>
            <h2 class="text-lg font-black">Lengkapi Data SIMPERUM — Calon Lahan</h2><p class="mt-1 text-xs" style="color:var(--portal-text-muted)">Isi hanya untuk calon lahan yang relevan dengan pengajuan Anda.</p>
            <?php $land = ['candidate_land_address'=>['Alamat Tanah',[]],'candidate_land_title_code'=>['Sertifikat Tanah',['hm'=>'Sertifikat HM','hgb'=>'Sertifikat HGB','letter_c'=>'Letter C','letter_d'=>'Letter D','village_letter'=>'Suket Desa','notarial_deed'=>'Akta Notaris','other'=>'Lainnya']],'candidate_land_origin_code'=>['Asal Tanah',['owned'=>'Milik Sendiri','inheritance'=>'Warisan','grant'=>'Hibah','purchase'=>'Jual Beli']],'land_owner_relationship_code'=>['Hub. dg Pemilik',['parent'=>'Orang Tua','other'=>'Orang Lain']]]; ?>
            <div class="mt-5 grid gap-4 sm:grid-cols-2"><?php foreach ($land as $key=>[$label,$options]): ?><div class="<?= $key === 'candidate_land_address' ? 'sm:col-span-2' : '' ?>"><label for="<?= $key ?>" class="text-xs font-bold"><?= $label ?></label><?php if ($options): ?><select id="<?= $key ?>" name="<?= $key ?>" class="mt-1 block w-full rounded-xl border px-3 py-2.5 text-sm" style="background:var(--portal-btn-bg);border-color:var(--portal-border);color:var(--portal-text)"><option value="">Pilih <?= strtolower($label) ?></option><?php foreach($options as $code=>$option): ?><option value="<?= $code ?>"<?= $selected($key,$code) ?>><?= html_escape($option) ?></option><?php endforeach; ?></select><?php else: ?><input id="<?= $key ?>" name="<?= $key ?>" value="<?= html_escape($value($key)) ?>" class="mt-1 block w-full rounded-xl border px-3 py-2.5 text-sm" style="background:var(--portal-btn-bg);border-color:var(--portal-border);color:var(--portal-text)"><?php endif; ?></div><?php endforeach; ?><fieldset class="sm:col-span-2 border-0 p-0 m-0"><legend class="text-xs font-bold p-0">Ukuran tanah</legend><div class="mt-1 grid gap-4 sm:grid-cols-2"><div><label for="land_length_m" class="text-[11px] font-bold" style="color:var(--portal-text-muted)">Panjang (m)</label><input id="land_length_m" name="land_length_m" type="number" min="0.01" step="0.01" value="<?= html_escape($value('land_length_m')) ?>" class="mt-1 block w-full rounded-xl border px-3 py-2.5 text-sm" style="background:var(--portal-btn-bg);border-color:var(--portal-border);color:var(--portal-text)"></div><div><label for="land_width_m" class="text-[11px] font-bold" style="color:var(--portal-text-muted)">Lebar (m)</label><input id="land_width_m" name="land_width_m" type="number" min="0.01" step="0.01" value="<?= html_escape($value('land_width_m')) ?>" class="mt-1 block w-full rounded-xl border px-3 py-2.5 text-sm" style="background:var(--portal-btn-bg);border-color:var(--portal-border);color:var(--portal-text)"></div></div></fieldset></div>
        <?php elseif ($step_slug === 'sanitation'): ?>
            <h2 class="text-lg font-black">Lengkapi Data SIMPERUM — Sanitasi & Utilitas</h2><?php $sanitation=['has_window'=>['Jendela',['1'=>'Ada Jendela','0'=>'Tidak Ada']],'has_ventilation'=>['Ventilasi',['1'=>'Ada Ventilasi','0'=>'Tidak Ada']],'water_source_code'=>['Sumber Air',['bottled'=>'Air Kemasan Bermerek','refill'=>'Air Isi Ulang','piped'=>'Ledeng (jenis tidak disebut SIMPERUM)','pdam'=>'PDAM','retail_piped'=>'Leding Eceran','well'=>'Sumur','spring'=>'Mata Air','rain'=>'Air Hujan','other_unfit'=>'Lainnya/Tidak Layak']],'latrine_type_code'=>['Jenis Jamban',['swan_neck'=>'Leher Angsa','plengsengan'=>'Plengsengan','pit'=>'Cemplung/Cubluk','none'=>'Tidak Punya']],'feces_disposal_code'=>['Jenis TPA',['septic_tank'=>'Tangki Septik','ipal'=>'IPAL','water_body'=>'Kolam/Sawah/Sungai','ground_hole'=>'Lubang Tanah','open_land'=>'Pantai/Tanah Lapang/Kebun']],'septic_distance_code'=>['Jarak Septik Tank',['lt_10'=>'<10 m','gte_10'=>'>=10 m']],'lighting_source_code'=>['Sumber Penerangan',['pln'=>'PLN','pln_unmetered'=>'PLN Non Meteran','non_pln'=>'Non PLN','none'=>'Bukan Listrik']],'cooking_fuel_code'=>['BB Masak',['electric_gas'=>'Listrik/Gas','kerosene'=>'Minyak Tanah','charcoal_wood'=>'Arang/Kayu','other'=>'Lainnya']]]; ?><p class="mt-1 text-xs" style="color:var(--portal-text-muted)">Pilihan Kamar Mandi/Jamban belum ditetapkan resmi, sehingga belum diminta pada langkah ini.</p><div class="mt-5 grid gap-4 sm:grid-cols-2"><?php foreach($sanitation as $key=>[$label,$options]): ?><div><label for="<?= $key ?>" class="text-xs font-bold"><?= $label ?></label><select id="<?= $key ?>" name="<?= $key ?>" class="mt-1 block w-full rounded-xl border px-3 py-2.5 text-sm" style="background:var(--portal-btn-bg);border-color:var(--portal-border);color:var(--portal-text)"><option value="">Pilih <?= strtolower($label) ?></option><?php foreach($options as $code=>$option): ?><option value="<?= $code ?>"<?= $selected($key,$code) ?>><?= html_escape($option) ?></option><?php endforeach; ?></select></div><?php endforeach; ?></div>
        <?php elseif ($step_slug === 'preliminary_recommendation'): ?>
            <?php
            /* Diganti 23 Agt 2026 - permintaan user: "Hasil Rekomendasi
               ... sesuaikan dengan xlsx kolom J". Dulu daftar kartu per
               program dari Warga_ruleset.php (mesin lama, program TETAP
               seperti "Peningkatan Kualitas RTLH"/"Program Rumah Apung" -
               nama program itu TIDAK ADA di xlsx sama sekali). Sekarang
               menampilkan $matriks_recommendation - hasil pencocokan 8
               field matriks + desil + umur terhadap 20 baris Sheet4
               (Matriks_program_ruleset::match(), dihitung ulang di
               Warga::pendataan() tiap render). Teksnya PERSIS kolom J
               ("PROGRAM YANG COCOK"), bukan disusun ulang.

               "Kategori Kemiskinan (Desil)" (kolom B) ditambahkan 23 Agt
               2026, lalu DIPERBAIKI sumbernya di hari yang sama: awalnya
               dibaca dari welfare_decile profil (angka SIMPERUM yang
               TIDAK terkait dengan Gaji yang baru dipilih warga di sini -
               bisa membuat kombinasi yang menurut xlsx cocok jadi
               dianggap tidak cocok gara-gara desil profil kebetulan beda
               golongan). Sekarang $matriks_decile_label DITURUNKAN dari
               Gaji (Matriks_program_ruleset::decile_label_for_income(),
               dihitung di Warga::pendataan()) - SATU sumber yang sama
               dipakai baik untuk tampilan ini maupun mesin pencocokan,
               tidak bisa lagi saling berbeda. */
            ?>
            <h2 class="text-lg font-black">Hasil Rekomendasi Awal</h2>
            <p class="mt-1 text-xs" style="color:var(--portal-text-muted)">Hasil ini dicocokkan dari data matriks yang sudah Anda isi terhadap tabel program perumahan. Lengkapi data SIMPERUM setelah ini agar pengajuan dapat ditinjau lebih lengkap.</p>
            <?php
            /* Keterangan "Data di SIMPERUM" - permintaan user 23 Agt
               2026. $matriks_data_simperum === FALSE berarti draft ini
               lahir dari Warga::isi_manual() (NIK tidak ditemukan di
               SIMPERUM saat "Masukkan NIK") - untuk kasus itu hasil
               rekomendasi di bawah SUDAH ditimpa jadi 'Oemah Lestari'
               + 'FLPP' TETAP di Warga::pendataan(), apa pun hasil
               pencocokan 20 baris matriks (lihat komentar di sana) -
               kotak keterangan ini menjelaskan KENAPA, bukan sekadar
               status netral. */
            $simperum_style = $matriks_data_simperum === FALSE
                ? 'background:rgba(245,158,11,.12);color:#92400e'
                : 'background:rgba(16,185,129,.12);color:#047857';
            $simperum_text = $matriks_data_simperum === FALSE
                ? 'Tidak Ada - NIK Anda tidak ditemukan di SIMPERUM, data diisi manual'
                : 'Ada - data ini berasal dari pencarian SIMPERUM';
            ?>
            <p class="mt-4 rounded-xl p-3 text-xs" style="<?= $simperum_style ?>">Data di SIMPERUM: <strong><?= html_escape($simperum_text) ?></strong></p>
            <p class="mt-3 rounded-xl p-3 text-xs" style="background:rgba(14,165,233,.09);color:#075985">Kategori Kemiskinan (Desil): <strong><?= $matriks_decile_label !== null ? html_escape($matriks_decile_label) : 'Belum tersedia - isi Gaji di langkah sebelumnya' ?></strong></p>
            <?php if ($matriks_data_simperum === FALSE): ?>
                <p class="mt-3 rounded-xl border p-3 text-xs" style="border-color:var(--portal-border);color:var(--portal-text-muted)">Karena data Anda belum ditemukan di SIMPERUM, rekomendasi awal di bawah ini bersifat baku (Oemah Lestari &amp; FLPP) - belum dihitung dari isian matriks di atas.</p>
                <?php
                /* Saran melengkapi SIMPERUM - permintaan user 23 Agt
                   2026: KHUSUS ditampilkan kalau jawaban matriks warga
                   SEBENARNYA cocok salah satu dari 15 program yang
                   mensyaratkan verifikasi (PB Backlog/Relokasi/Bencana,
                   PK Bencana/RTLH - lihat
                   Matriks_program_ruleset::SIMPERUM_REQUIRED_PROGRAMS).
                   Nama programnya SENGAJA TIDAK disebut di sini ("hasil
                   yang dari xlsx tidak ditampilkan tetapi memberi
                   saran") - cuma anjuran, bukan bocoran hasil pencocokan
                   yang belum diverifikasi. */
                ?>
                <?php if ($matriks_saran_lengkapi_simperum): ?>
                    <p class="mt-3 rounded-xl border p-3 text-xs" style="border-color:#f59e0b;background:rgba(245,158,11,.08);color:#92400e">
                        <strong>Saran:</strong> berdasarkan jawaban matriks Anda, kemungkinan ada program bantuan lain yang lebih sesuai untuk Anda. Lengkapi data SIMPERUM terlebih dahulu (daftar/verifikasi NIK Anda) agar dapat diperiksa lebih lanjut.
                    </p>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (empty($matriks_recommendation)): ?>
                <p class="mt-5 rounded-xl border p-3 text-xs" style="border-color:var(--portal-border);color:var(--portal-text-muted)">Belum ada program yang cocok dengan kombinasi data matriks Anda saat ini. Ini bukan penolakan akhir - lengkapi data SIMPERUM agar dapat ditinjau lebih lanjut, atau periksa kembali isian matriks Anda.</p>
            <?php else: ?>
                <div class="mt-5 space-y-3">
                    <?php foreach ($matriks_recommendation as $program): ?>
                        <?php
                        /* Syarat penerima NYATA - permintaan user 23 Agt
                           2026 ("apakah bisa dimasukkan sebagai syarat2
                           hasil rekomendasi?"), dikutip dari PPT UN
                           Habitat 2026 (lihat docblock
                           Matriks_program_ruleset::PROGRAM_CRITERIA).
                           $this->matriks_program_ruleset terjangkau di
                           sini walau ini view, bukan controller - CI3
                           melekatkan pustaka yang sudah di-load ke
                           instance super-object yang sama ($this),
                           dipakai bukan hal baru di berkas ini (lihat
                           $this->security/$this->load di tempat lain).
                           Array KOSONG untuk program yang belum punya
                           kriteria terverifikasi (PB Backlog, PB Bencana,
                           Oemah Lestari, KPR-FLPP - lihat catatan kenapa
                           di docblock yang sama) - TIDAK mengarang,
                           kotak syarat cukup tidak muncul. */
                        $syarat = $this->matriks_program_ruleset->criteria_for_program($program);
                        ?>
                        <article class="rounded-xl border p-4" style="border-color:var(--portal-border)">
                            <h3 class="font-black"><?= html_escape($program) ?></h3>
                            <p class="mt-2 text-xs" style="color:var(--portal-text-muted)">Ini rekomendasi awal berdasarkan matriks program, bukan keputusan bantuan resmi. Lanjutkan pelengkapan data agar pengajuan dapat ditinjau.</p>
                            <?php if ( ! empty($syarat)): ?>
                                <div class="mt-3 rounded-lg p-3" style="background:var(--portal-btn-bg)">
                                    <p class="text-[10px] font-bold uppercase tracking-wider" style="color:var(--portal-text-muted)">Syarat Penerima</p>
                                    <ul class="mt-1.5 list-disc space-y-1 pl-4 text-xs" style="color:var(--portal-text)">
                                        <?php foreach ($syarat as $poin): ?><li><?= html_escape($poin) ?></li><?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php elseif ($step_slug === 'location_evidence'): ?>
            <h2 class="text-lg font-black">Lengkapi Data SIMPERUM — Lokasi & Bukti</h2><p class="mt-1 text-xs" style="color:var(--portal-text-muted)">Koordinat dan berkas disimpan privat. Bukti di bawah berlabel simulasi, bukan ketetapan akhir Dinas.</p><div class="mt-5 grid gap-4 sm:grid-cols-3"><div><label for="location_lat" class="text-xs font-bold">Latitude</label><input id="location_lat" name="location_lat" type="number" step="any" value="<?= html_escape($value('location_lat')) ?>" class="mt-1 block w-full rounded-xl border px-3 py-2.5 text-sm" style="background:var(--portal-btn-bg);border-color:var(--portal-border);color:var(--portal-text)"></div><div><label for="location_lng" class="text-xs font-bold">Longitude</label><input id="location_lng" name="location_lng" type="number" step="any" value="<?= html_escape($value('location_lng')) ?>" class="mt-1 block w-full rounded-xl border px-3 py-2.5 text-sm" style="background:var(--portal-btn-bg);border-color:var(--portal-border);color:var(--portal-text)"></div><div><label for="location_accuracy_m" class="text-xs font-bold">Akurasi (meter)</label><input id="location_accuracy_m" name="location_accuracy_m" type="number" min="0" step="0.1" value="<?= html_escape($value('location_accuracy_m')) ?>" class="mt-1 block w-full rounded-xl border px-3 py-2.5 text-sm" style="background:var(--portal-btn-bg);border-color:var(--portal-border);color:var(--portal-text)"></div></div><button type="button" id="use-location" class="mt-3 rounded-xl border px-3 py-2 text-xs font-bold" style="border-color:var(--portal-border)">Gunakan lokasi perangkat</button><?php $existing = ['self_photo'=>'Foto Diri','house_front_photo'=>'Rumah Depan','house_side_photo'=>'Rumah Samping','roof_photo'=>'Atap','floor_photo'=>'Lantai','wall_photo'=>'Dinding','latrine_photo'=>'Jamban','land_photo'=>'Foto Lahan (opsional)']; $candidate=['candidate_land_photo'=>'Foto Calon Lahan','id_card_photo'=>'Foto KTP','family_card_photo'=>'Foto KK','land_owner_family_card_photo'=>'Foto KK Pemilik (jika pemilik lain)']; $files = (($assessment['assessment_track'] ?? '') === 'candidate_land') ? $candidate : ((($assessment['assessment_track'] ?? '') === 'financing') ? ['id_card_photo'=>'Foto KTP','family_card_photo'=>'Foto KK'] : $existing); ?><fieldset class="mt-5 border-t pt-4" style="border-color:var(--portal-border)"><legend class="text-sm font-black">Bukti simulasi</legend><div class="mt-3 grid gap-3 sm:grid-cols-2"><?php foreach($files as $kind=>$label): ?><div class="rounded-xl border p-3" style="border-color:var(--portal-border)"><label for="<?= $kind ?>" class="text-xs font-bold"><?= html_escape($label) ?></label><?php if (isset($evidence_files[$kind])): $bukti = $evidence_files[$kind]; ?><span class="ml-1 inline-flex rounded-full px-1.5 py-0.5 text-[9px] font-bold" style="background:rgba(16,185,129,.12);color:#047857">Sudah tersimpan</span>
                        <?php // Pemohon wajib bisa MEMERIKSA apa yang dia unggah - badge saja
                              // tidak cukup saat petugas meminta perbaikan. Dibuka di modal
                              // (components/file_viewer_modal), halaman tidak ditinggalkan. ?>
                        <span class="mt-1 flex items-center gap-2 text-[10px]" style="color:var(--portal-text-muted)">
                            <a href="<?= site_url('warga/lihat_bukti/' . (int) $assessment['id'] . '/' . rawurlencode($kind)) ?>" data-file-view data-file-title="<?= html_escape($label) ?>" target="_blank" rel="noopener" class="font-bold" style="color:var(--portal-brand-ink,#047857)">Lihat berkas</a>
                            <span>·</span><span><?= number_format(((int) ($bukti['size_bytes'] ?? 0)) / 1024, 0, ',', '.') ?> KB</span>
                            <span>·</span><span><?= html_escape(date('d M Y', strtotime($bukti['created_at'] ?? 'now'))) ?></span>
                        </span><?php endif; ?><input id="<?= $kind ?>" name="<?= $kind ?>" type="file" accept="image/jpeg,image/png" class="mt-2 block w-full text-xs"><button type="submit" name="action" value="upload" class="mt-2 rounded-lg px-3 py-1.5 text-xs font-bold" style="background:var(--portal-brand);color:#06333b" onclick="this.form.querySelector('[name=file_kind]').value='<?= $kind ?>'">Unggah</button></div><?php endforeach; ?></div><input type="hidden" name="file_kind" value=""></fieldset><script>document.getElementById('use-location')?.addEventListener('click',function(){if(!navigator.geolocation)return;navigator.geolocation.getCurrentPosition(function(p){document.getElementById('location_lat').value=p.coords.latitude;document.getElementById('location_lng').value=p.coords.longitude;document.getElementById('location_accuracy_m').value=p.coords.accuracy;});});</script>
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
            <div id="warga-location-map" class="mt-5 h-72 overflow-hidden rounded-xl border" style="border-color:var(--portal-border)" aria-label="Peta lokasi warga"></div>
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
            <script>
            (function () {
                if (!window.L) return;
                var lat = document.getElementById('location_lat');
                var lng = document.getElementById('location_lng');
                var start = [parseFloat(lat.value) || -7.15, parseFloat(lng.value) || 110.14];
                var map = L.map('warga-location-map').setView(start, lat.value && lng.value ? 16 : 8);
                var marker = L.marker(start, {draggable:true}).addTo(map);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19, attribution:'&copy; OpenStreetMap'}).addTo(map);
                function set(point) { marker.setLatLng(point); lat.value = point.lat.toFixed(7); lng.value = point.lng.toFixed(7); }
                map.on('click', function (event) { set(event.latlng); });
                marker.on('dragend', function () { set(marker.getLatLng()); });
            })();
            </script>
        <?php elseif ($step_slug === 'review'): ?>
            <?php
            $status_labels = ['eligible' => 'Memenuhi simulasi', 'potential' => 'Berpotensi memenuhi', 'not_eligible' => 'Belum memenuhi', 'needs_data' => 'Data masih diperlukan'];
            $status_styles = ['eligible' => 'background:rgba(16,185,129,.12);color:#047857', 'potential' => 'background:rgba(14,165,233,.12);color:#075985', 'not_eligible' => 'background:rgba(239,68,68,.1);color:#b91c1c', 'needs_data' => 'background:rgba(245,158,11,.12);color:#92400e'];
            $reason_labels = [
                'SIM_DECILE_MISSING' => 'Data desil belum tersedia.',
                'SIM_DESIL_TIDAK_SESUAI' => 'Desil belum sesuai dengan sasaran simulasi program.',
                'SIM_TRACK_TIDAK_SESUAI' => 'Cabang kebutuhan rumah belum sesuai dengan simulasi program.',
                'SIM_RTLH_DAMAGE' => 'Kondisi kerusakan rumah menjadi pertimbangan simulasi.',
                'SIM_RTLH_SANITATION' => 'Kondisi sanitasi menjadi pertimbangan simulasi.',
                'SIM_PB_LAND_READY' => 'Kelengkapan dan legalitas calon lahan menjadi pertimbangan simulasi.',
                'SIM_OMAH_DESIL4_SELF_HELP' => 'Desil dan kemampuan swadaya menjadi pertimbangan simulasi.',
                'SIM_OMAH_KEBUTUHAN_BELUM_TERVERIFIKASI' => 'Kebutuhan perumahan belum dapat diverifikasi dari data yang tersedia.',
                'SIM_KEBUTUHAN_RUMAH_BELUM_LENGKAP' => 'Data kepemilikan rumah untuk kebutuhan pembiayaan belum lengkap.',
                'SIM_KEBUTUHAN_RUMAH_TIDAK_MEMENUHI' => 'Data kepemilikan rumah belum sesuai dengan sasaran simulasi pembiayaan.',
                'SIM_INCOME_MISSING' => 'Data kelompok penghasilan belum tersedia.',
                'SIM_FLPP_INCOME' => 'Kelompok penghasilan menjadi pertimbangan simulasi.',
                'SIM_OEMAH_INCOME' => 'Kelompok penghasilan menjadi pertimbangan simulasi.',
                'SIM_RUMAH_APUNG_NEEDS_DATA' => 'Data pesisir atau rob belum tersedia pada formulir saat ini.',
            ];
            $track_labels = ['existing_house' => 'Rumah eksisting', 'candidate_land' => 'Calon lahan', 'financing' => 'Pembiayaan', 'undetermined' => 'Belum ditentukan'];
            $source_labels = ['simulation' => 'SIMPERUM (simulasi)', 'api' => 'SIMPERUM', 'manual' => 'Diisi mandiri oleh warga'];
            $submittable_recommendations = array_values(array_filter((array) $recommendations, static fn($item) => in_array($item['eligibility_status'] ?? '', ['eligible', 'potential'], TRUE)));
            $needs_data_recommendations = array_values(array_filter((array) $recommendations, static fn($item) => ($item['eligibility_status'] ?? '') === 'needs_data'));
            $has_missing_decile = FALSE;
            $has_unavailable_requirements = FALSE;
            $has_actionable_needs_data = FALSE;
            $unavailable_reason_codes = [
                'SIM_DECILE_MISSING',
                'SIM_RUMAH_APUNG_NEEDS_DATA',
                'SIM_OMAH_KEBUTUHAN_BELUM_TERVERIFIKASI',
            ];
            foreach ($needs_data_recommendations as $item) {
                $item_reasons = (array) ($item['reason_codes'] ?? []);
                $has_missing_decile = $has_missing_decile || in_array('SIM_DECILE_MISSING', $item_reasons, TRUE);
                $item_is_unavailable = (bool) array_intersect($item_reasons, $unavailable_reason_codes);
                $has_unavailable_requirements = $has_unavailable_requirements || $item_is_unavailable;
                $has_actionable_needs_data = $has_actionable_needs_data || ! $item_is_unavailable;
            }
            ?>
            <h2 class="text-lg font-black">Review & Rekomendasi</h2>
            <dl class="mt-5 grid gap-3 text-xs sm:grid-cols-3"><div class="rounded-xl border p-3" style="border-color:var(--portal-border)"><dt style="color:var(--portal-text-muted)">Sumber data</dt><dd class="mt-1 font-bold"><?= html_escape($source_labels[$review_summary['source_mode'] ?? ''] ?? 'Belum tersedia') ?></dd></div><div class="rounded-xl border p-3" style="border-color:var(--portal-border)"><dt style="color:var(--portal-text-muted)">Desil sumber</dt><dd class="mt-1 font-bold"><?= isset($review_summary['welfare_decile']) && $review_summary['welfare_decile'] !== null ? html_escape((string) $review_summary['welfare_decile']) : 'Belum tersedia' ?></dd></div><div class="rounded-xl border p-3" style="border-color:var(--portal-text-muted);border:1px solid var(--portal-border)"><dt>Cabang</dt><dd class="mt-1 font-bold" style="color:var(--portal-text)"><?= html_escape($track_labels[$review_summary['assessment_track'] ?? ''] ?? 'Belum ditentukan') ?></dd></div></dl>
            <?php if ($submittable_recommendations): ?>
                <section id="review-action-help" role="status" class="mt-5 rounded-xl border p-4 text-sm" style="border-color:rgba(16,185,129,.35);background:rgba(16,185,129,.10);color:#047857"><h3 class="font-black">Program dapat diajukan</h3><p class="mt-1 text-xs">Pilih satu program di bawah, lalu tekan <strong>Ajukan program yang dipilih</strong>. Pengajuan akan masuk ke Admin Kab/Kota untuk diverifikasi dan belum merupakan persetujuan bantuan. Setelah dikirim, data Anda dikunci dan hanya dapat diubah bila petugas meminta perbaikan.</p></section>
            <?php elseif ($needs_data_recommendations): ?>
                <section id="review-action-help" role="status" class="mt-5 rounded-xl border p-4 text-sm" style="border-color:rgba(245,158,11,.35);background:rgba(245,158,11,.11);color:#92400e"><h3 class="font-black">Pengajuan belum dapat dikirim</h3><p class="mt-1 text-xs"><?= $has_actionable_needs_data ? ($has_unavailable_requirements ? 'Sebagian data atau kondisi masih dapat diperiksa; beberapa persyaratan program lain memang belum tersedia di formulir saat ini.' : 'Periksa atau lengkapi data dan kondisi pada langkah sebelumnya, sesuai alasan di setiap rekomendasi.') : ($has_missing_decile ? 'Desil sumber belum tersedia. Sistem tidak akan menebak desil; Anda dapat memeriksa kembali isian atau melihat layanan lain.' : 'Persyaratan program ini belum dapat disimulasikan dari formulir saat ini. Anda tidak perlu mengulang data yang sudah benar.') ?></p></section>
            <?php elseif ( ! empty($recommendations)): ?>
                <section id="review-action-help" role="status" class="mt-5 rounded-xl border p-4 text-sm" style="border-color:rgba(239,68,68,.28);background:rgba(239,68,68,.08);color:#991b1b"><h3 class="font-black">Belum ada program yang dapat diajukan</h3><p class="mt-1 text-xs">Periksa kembali data yang diisi atau lihat layanan perumahan lain. Hasil simulasi ini bukan keputusan akhir Dinas.</p></section>
            <?php endif; ?>
            <h3 class="mt-6 text-sm font-black">Hasil rekomendasi</h3>
            <?php if (empty($recommendations)): ?>
                <p class="mt-3 rounded-xl border p-3 text-xs" style="border-color:var(--portal-border);color:var(--portal-text-muted)">Belum ada hasil untuk ditampilkan. Lengkapi data yang diperlukan lalu simpan langkah sebelumnya.</p>
            <?php else: ?><fieldset class="mt-3"><legend class="sr-only"><?= $submittable_recommendations ? 'Pilih satu program untuk diajukan' : 'Rincian hasil rekomendasi' ?></legend><div class="space-y-3"><?php foreach ($recommendations as $recommendation): ?>
                <?php $status = (string) ($recommendation['eligibility_status'] ?? 'needs_data'); $reasons = isset($recommendation['reason_codes']) && is_array($recommendation['reason_codes']) ? $recommendation['reason_codes'] : []; ?>
                <article class="rounded-xl border p-4" style="border-color:var(--portal-border)"><div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"><div><h4 class="font-black"><?= html_escape((string) ($recommendation['program_name'] ?? $recommendation['program_code'] ?? 'Program')) ?></h4><p class="mt-1 text-[10px]" style="color:var(--portal-text-muted)">Ruleset <?= html_escape((string) ($recommendation['ruleset_version'] ?? 'SIM-2026-01')) ?></p></div><span class="inline-flex w-fit rounded-full px-2 py-1 text-[10px] font-bold" style="<?= $status_styles[$status] ?? $status_styles['needs_data'] ?>"><?= html_escape($status_labels[$status] ?? 'Status belum dikenal') ?></span></div><?php if ($reasons): ?><ul class="mt-3 space-y-1 text-xs" style="color:var(--portal-text-muted)"><?php foreach ($reasons as $reason): ?><li>• <?= html_escape($reason_labels[$reason] ?? 'Alasan evaluasi tersedia pada catatan server.') ?></li><?php endforeach; ?></ul><?php endif; ?><?php if (in_array($status, ['eligible', 'potential'], TRUE) && ! empty($recommendation['recommendation_id'])): ?><label class="mt-3 flex cursor-pointer items-center gap-2 rounded-lg border p-3 text-xs font-bold focus-within:ring-2" style="border-color:var(--portal-brand);background:rgba(14,165,233,.07)"><input type="radio" name="recommendation_id" value="<?= (int) $recommendation['recommendation_id'] ?>" required aria-describedby="review-action-help"> Pilih program ini untuk diajukan</label><?php endif; ?></article>
            <?php endforeach; ?></div></fieldset><?php endif; ?>
        <?php else: ?>
            <h2 class="text-lg font-black"><?= html_escape($step_names[$step]) ?></h2>
            <p class="mt-2 rounded-xl p-3 text-sm" style="background:rgba(245,158,11,.11);color:#92400e">Langkah ini akan dilengkapi pada fase berikutnya. Kembali ke langkah yang sudah tersedia untuk melanjutkan pendataan.</p>
        <?php endif; ?>

        <div class="mt-6 flex flex-col-reverse gap-2 border-t pt-4 sm:flex-row sm:justify-between" style="border-color:var(--portal-border)">
            <?php if ($step_slug === 'review' && $submittable_recommendations): ?>
                <button type="submit" name="direction" value="back" formnovalidate class="rounded-xl border px-4 py-2.5 text-center text-sm font-bold" style="border-color:var(--portal-border);color:var(--portal-text)">Periksa data kembali</button>
                <button type="submit" name="action" value="submit" aria-describedby="review-action-help" class="rounded-xl px-4 py-2.5 text-sm font-black" style="background:var(--portal-brand);color:#06333b">Ajukan program yang dipilih</button>
            <?php elseif ($step_slug === 'review' && $has_actionable_needs_data): ?>
                <a href="<?= site_url('golek_omah') ?>" class="rounded-xl border px-4 py-2.5 text-center text-sm font-bold" style="border-color:var(--portal-border);color:var(--portal-text)">Lihat layanan lain</a>
                <button type="submit" name="direction" value="back" formnovalidate class="rounded-xl px-4 py-2.5 text-sm font-black" style="background:var(--portal-brand);color:#06333b">Periksa/lengkapi data</button>
            <?php elseif ($step_slug === 'review'): ?>
                <button type="submit" name="direction" value="back" formnovalidate class="rounded-xl border px-4 py-2.5 text-center text-sm font-bold" style="border-color:var(--portal-border);color:var(--portal-text)">Periksa data kembali</button>
                <a href="<?= site_url('golek_omah') ?>" class="rounded-xl px-4 py-2.5 text-center text-sm font-black" style="background:var(--portal-brand);color:#06333b">Lihat layanan lain</a>
            <?php else: ?>
                <?php if ($step > 0): ?><button type="submit" name="direction" value="back" formnovalidate class="rounded-xl border px-4 py-2.5 text-center text-sm font-bold" style="border-color:var(--portal-border);color:var(--portal-text)">Kembali</button><?php else: ?><a href="<?= html_escape($back_url) ?>" class="rounded-xl border px-4 py-2.5 text-center text-sm font-bold" style="border-color:var(--portal-border);color:var(--portal-text)">Kembali</a><?php endif; ?>
                <?php if ($step <= 5): ?><button type="submit" name="direction" value="next" class="rounded-xl px-4 py-2.5 text-sm font-black" style="background:var(--portal-brand);color:#06333b">Simpan dan lanjut</button><?php endif; ?>
            <?php endif; ?>
        </div>
    </form>
    <?php if ($step_slug === 'find_data' && empty($is_logged_in)): ?>
    <script>
    (function () {
        var form = document.getElementById('form-pendataan-warga');
        if (!form || !window.fetch) return;
        form.addEventListener('submit', function (event) {
            if (form.querySelector('[name=action]').value !== 'lookup') return;
            event.preventDefault();
            var submit = form.querySelector('button[name=direction][value=next]');
            if (submit) { submit.disabled = true; submit.textContent = 'Memeriksa NIK…'; }
            fetch(form.getAttribute('action'), {
                method: 'POST', body: new FormData(form), credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (response) {
                var path = new URL(response.url, window.location.origin).pathname;
                var nik = (document.getElementById('nik').value || '').replace(/\D/g, '');
                if (/\/Auth\/login\/?$/i.test(path) && window.kpkpShowLoginModal) {
                    window.kpkpShowLoginModal(); return;
                }
                if (/\/Auth\/register\/?$/i.test(path) && window.kpkpShowRegisterModal) {
                    window.kpkpShowRegisterModal(nik); return;
                }
                window.location.href = response.url;
            }).catch(function () { form.submit(); }).finally(function () {
                if (submit) { submit.disabled = false; submit.textContent = 'Simpan dan lanjut'; }
            });
        });
    })();
    </script>
    <?php endif; ?>
</section>
