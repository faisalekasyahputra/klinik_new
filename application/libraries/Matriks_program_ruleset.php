<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Matriks program dari workbook user, Sheet3 A3:J22, dibaca 10 Sep 2026.
 * preliminary() dipakai wizard dan disimpan sebagai snapshot untuk warga/admin.
 * match() dipertahankan untuk pemanggil lama yang memakai kode rentang gaji.
 * Desil pasangan gaji di matriks bukan desil resmi profil SIMPERUM.
 */
class Matriks_program_ruleset {

    const VERSION = 'MATRIKS-2026-09-10';
    // Workbook yang dibaca 10 Sep 2026: Sheet3 A3:J22 (dulu disebut Sheet3).
    const FORM_FIELDS = [
        'matrix_land_ownership_code' => ['Kepemilikan lahan', ['land_none'=>'Tidak punya', 'land_legal'=>'Punya lahan sah']],
        'matrix_environment_condition_code' => ['Kondisi lingkungan / fisik bangunan', ['env_safe'=>'Aman / tidak terdampak bencana', 'env_relocation_zone'=>'Kawasan relokasi pemerintah', 'env_disaster_severe'=>'Terdampak bencana: kerusakan berat / roboh', 'env_disaster_moderate'=>'Terdampak bencana: kerusakan sedang (30–70%)', 'env_slum_uninhabitable'=>'Kumuh / tidak layak: atap, lantai, dinding jelek atau rusak']],
        'matrix_occupation_finance_code' => ['Kondisi finansial untuk program', ['work_stable_or_unstable_no_subsidy'=>'Berpenghasilan tetap / tidak tetap, belum pernah mendapat subsidi', 'work_can_save_irregular'=>'Mampu menabung / penghasilan tidak tetap', 'work_other'=>'Kondisi lainnya']],
        'matrix_marital_family_code' => ['Kondisi keluarga', ['family_single'=>'Belum menikah', 'family_married'=>'Menikah', 'family_head_of_household'=>'Kepala keluarga (menikah / duda / janda)', 'family_multi_household'=>'Dihuni lebih dari 1 KK (kepala keluarga)']],
    ];

    /** Hasil awal tersimpan terpisah; data yang kosong bukan persetujuan syarat. */
    public function preliminary(array $draft, array $profile, $today = NULL)
    {
        $today = $today ?: date('Y-m-d');
        $birth = DateTimeImmutable::createFromFormat('!Y-m-d', (string) ($profile['birth_date'] ?? ''));
        $age = $birth && $birth->format('Y-m-d') === ($profile['birth_date'] ?? '') && $birth->format('Y-m-d') <= $today
            ? $birth->diff(new DateTimeImmutable($today))->y : NULL;
        $income = $profile['monthly_income'] ?? NULL;
        $income = is_numeric($income) && $income >= 0 ? (float) $income : NULL;
        $family = $draft['matrix_marital_family_code'] ?? NULL;
        if (!$family) $family = ['single'=>'family_single', 'married'=>'family_married'][$profile['marital_status_code'] ?? ''] ?? NULL;
        $input = [
            'monthly_income'=>$income, 'age_years'=>$age,
            'dtks_code'=>$draft['matrix_dtks_status'] ?? NULL,
            'land_code'=>$draft['matrix_land_ownership_code'] ?? NULL,
            'housing_code'=>$draft['matrix_current_housing_code'] ?? NULL,
            'environment_code'=>$draft['matrix_environment_condition_code'] ?? NULL,
            'occupation_code'=>$draft['matrix_occupation_finance_code'] ?? NULL,
            'family_code'=>$family,
            'marital_status_code'=>$profile['marital_status_code'] ?? NULL,
        ];
        $ranges = ['income_0_1_5'=>[0,1500000], 'income_1_5_2_2'=>[1500000,2200000], 'income_2_2_2_8'=>[2200000,2800000], 'income_2_8_8_5'=>[2800000,8500000], 'income_2_8_10'=>[2800000,10000000], 'income_gt_8_5'=>[8500000,INF], 'income_gt_10'=>[10000000,INF]];
        $fields = [2=>['dtks_code','Status DTKS'],3=>['land_code','Kepemilikan lahan'],4=>['housing_code','Kepemilikan rumah'],5=>['environment_code','Kondisi lingkungan / bangunan'],6=>['occupation_code','Kondisi finansial'],8=>['family_code','Kondisi keluarga']];
        $items = [];
        foreach (self::ROWS as $index => $row) {
            [$min,$max] = $ranges[$row[0]];
            // Rentang yang bersinggungan di Excel tetap dapat menghasilkan dua
            // prioritas pada batas persisnya; jangan menciptakan batas baru.
            if ($income !== NULL && ($income < $min || $income > $max || (strpos($row[0], 'income_gt_') === 0 && $income === (float) $min))) continue;
            $missing = $income === NULL ? ['Pendapatan per bulan'] : [];
            foreach ($fields as $column => [$key,$label]) {
                if ($row[$column] === NULL) continue;
                $actual = $input[$key];
                if ($key === 'family_code' && in_array($row[$column], ['family_single','family_married'], TRUE)) {
                    $actual = ['single'=>'family_single','married'=>'family_married','divorced'=>'family_divorced'][$input['marital_status_code'] ?? ''] ?? NULL;
                }
                if ($actual === NULL || $actual === '') { $missing[] = $label; continue; }
                // Menumpang/sewa juga memenuhi kategori gabungan belum punya.
                if ($key === 'housing_code' && $row[$column] === 'house_none_or_rent' && $actual === 'house_rent_or_staying') continue;
                if ($actual !== $row[$column]) continue 2;
            }
            if ($row[7] !== NULL) {
                if ($age === NULL) $missing[] = 'Tanggal lahir';
                elseif (!$this->age_matches($row[7], $age)) continue;
            }
            $criteria = ['Pendapatan: ' . ($max === INF ? '> Rp' . number_format($min, 0, ',', '.') : 'Rp' . number_format($min, 0, ',', '.') . '–Rp' . number_format($max, 0, ',', '.'))];
            foreach ([3=>'matrix_land_ownership_code',5=>'matrix_environment_condition_code',6=>'matrix_occupation_finance_code',8=>'matrix_marital_family_code'] as $column=>$field) {
                if ($row[$column] !== NULL) $criteria[] = self::FORM_FIELDS[$field][1][$row[$column]];
            }
            if ($row[2] !== NULL) $criteria[] = 'Status DTKS: Ya';
            if ($row[7] !== NULL) $criteria[] = ['produktif_21'=>'Usia 21–59 tahun', 'produktif_18'=>'Usia 18–59 tahun', 'produktif_18_or_tua'=>'Usia minimal 18 tahun'][$row[7]];
            $items[] = ['program_name'=>$row[9], 'source_row'=>$index+3, 'missing'=>$missing, 'criteria'=>$criteria];
        }
        return ['ruleset_version'=>self::VERSION, 'source_sheet'=>'Sheet3', 'evaluated_at'=>date('c'), 'input'=>$input, 'items'=>$items];
    }

    /* Gaji -> satu angka desil REPRESENTATIF dari rentang yang dipakai
       baris-baris Sheet3 (mis. income_1_5_2_2 -> 2, cukup untuk lolos
       cek in_array($decile,[2,3]) - tidak perlu representasi rentang
       penuh, cukup satu anggota sah dari himpunan desil baris terkait). */
    const INCOME_TO_DECILE = [
        'income_0_1_5' => 1,
        'income_1_5_2_2' => 2,
        'income_2_2_2_8' => 4,
        'income_2_8_8_5' => 5,
        'income_2_8_10' => 5,
        'income_gt_8_5' => 9,
        'income_gt_10' => 9,
    ];

    /** Label PERSIS kolom B Sheet3, dipakai tampilan "Kategori Kemiskinan
     * (Desil)" - satu sumber kebenaran yang sama dengan yang dipakai
     * match(), supaya yang ditampilkan ke warga PERSIS yang dipakai
     * mesin pencocokan (tidak ada lagi dua sumber desil yang bisa
     * berbeda seperti sebelum perubahan ini). */
    const DECILE_LABELS = [
        1 => 'Desil 1 (Sangat Miskin)',
        2 => 'Desil 2-3 (Miskin)',
        4 => 'Desil 4 (Rentan Miskin)',
        5 => 'Desil 5-8 (MBR)',
        9 => 'Desil 9-10 (Non-MBR)',
    ];

    /** @return int|null Null kalau kode Gaji belum diisi/tidak dikenal. */
    public function decile_for_income($income_code)
    {
        return self::INCOME_TO_DECILE[$income_code] ?? NULL;
    }

    /** @return string|null Label kolom B untuk kode Gaji yang sama. */
    public function decile_label_for_income($income_code)
    {
        $decile = $this->decile_for_income($income_code);
        return $decile !== NULL ? (self::DECILE_LABELS[$decile] ?? NULL) : NULL;
    }

    /**
     * 15 program (kolom J) yang SEMUANYA berasal dari baris xlsx yang
     * mensyaratkan DTKS='YA' (kecuali KPR-FLPP - itu satu-satunya baris
     * ber-DTKS yang bukan bagian kelompok ini) - permintaan user 23 Agt
     * 2026: kalau NIK warga BELUM terdaftar di SIMPERUM tapi jawaban
     * matriksnya SEBENARNYA cocok salah satu dari 15 program ini, beri
     * SARAN melengkapi data SIMPERUM di halaman Hasil Rekomendasi -
     * bukan menampilkan nama programnya (itu tetap ditimpa 'Oemah
     * Lestari'/'FLPP', lihat Warga::pendataan()), cuma anjuran supaya
     * warga tahu kenapa perlu melengkapi data.
     */
    const SIMPERUM_REQUIRED_PROGRAMS = [
        'PB Backlog (Prioritas 1)', 'PB Backlog (Prioritas 2)', 'PB Backlog (Prioritas 3)',
        'PB Relokasi (Prioritas 1)', 'PB Relokasi (Prioritas 2)', 'PB Relokasi (Prioritas 3)',
        'PB Bencana (Prioritas 1)', 'PB Bencana (Prioritas 2)', 'PB Bencana (Prioritas 3)',
        'PK Bencana (Prioritas 1)', 'PK Bencana (Prioritas 2)', 'PK Bencana (Prioritas 3)',
        'PK RTLH (Prioritas 1)', 'PK RTLH (Prioritas 2)', 'PK RTLH (Prioritas 3)',
    ];

    /**
     * Kriteria penerima NYATA per program - permintaan user 23 Agt 2026
     * ("apakah bisa dimasukkan sebagai syarat-syarat hasil rekomendasi?"),
     * dikutip LANGSUNG dari "PPT UN HABITAT 2026.pdf" (presentasi resmi
     * Disperakim Provinsi Jawa Tengah), bukan karangan/tafsiran:
     *   - PB Relokasi: hal. ±22, bagian "Kriteria Penerima"
     *   - PK Bencana: hal. ±92, bagian "KRITERIA PENERIMA BANTUAN"
     *     (ambang kerusakan mengutip Permen PUPR No. 22 Tahun 2018)
     *   - PK RTLH (BANKEUPEMDES): hal. ±76, bagian "Kriteria Rumah"
     *
     * KUNCI = nama program TANPA akhiran "(Prioritas N)" - satu daftar
     * kriteria dipakai untuk ketiga tingkat prioritas program yang sama
     * (PDF tidak membedakan kriteria PENERIMA per tingkat prioritas,
     * cuma urutan pemenuhannya).
     *
     * SENGAJA HANYA 3 PROGRAM - PDF ini TIDAK PUNYA bagian "Kriteria
     * Penerima" eksplisit untuk PB Backlog maupun PB Bencana (sudah
     * ditelusuri langsung, nihil - PB Backlog cuma punya rincian besaran
     * & komponen material, PB Bencana cuma muncul di tabel ringkasan
     * jumlah unit). Program lain (Oemah Lestari*, KPR-FLPP*, dan
     * fallback 'Oemah Lestari'/'FLPP' saat NIK belum di SIMPERUM) juga
     * TIDAK punya entri - TIDAK MENGARANG kriteria untuk program yang
     * sumbernya belum ketemu, cukup tidak menampilkan kotak kriteria
     * untuk program itu (lihat criteria_for_program()).
     */
    const PROGRAM_CRITERIA = [
        'PB Relokasi' => [
            'Rumah warga terdampak relokasi program pemerintah',
            'Tercatat dalam data kemiskinan/kesejahteraan sosial (DTKS)',
            'Memiliki lahan dengan kepemilikan sah, sesuai tata ruang, dan aman dari bencana',
            'Sanggup berswadaya',
        ],
        'PK Bencana' => [
            'Rumah warga terdampak bencana',
            'Terdaftar dalam BDT (Basis Data Terpadu)',
            'Kerusakan rumah minimal kategori Sedang (30-70%), sesuai Permen PUPR No. 22 Tahun 2018',
        ],
        'PK RTLH' => [
            'Memenuhi minimal 2 dari 3 kondisi berikut: Atap, Lantai, atau Dinding rumah berkualitas jelek/rusak',
        ],
    ];

    /**
     * @param string $program Salah satu hasil match() - boleh dengan
     *   atau tanpa akhiran "(Prioritas N)".
     * @return string[] Kosong kalau program ini belum punya kriteria
     *   terverifikasi dari sumber manapun - lihat catatan di atas,
     *   bukan bug.
     */
    public function criteria_for_program($program)
    {
        $base = trim((string) preg_replace('/\s*\(Prioritas\s*\d+\)\s*$/u', '', (string) $program));
        return self::PROGRAM_CRITERIA[$base] ?? [];
    }

    /** @param string[] $programs Hasil match(). @return bool */
    public function needs_simperum_suggestion(array $programs)
    {
        return count(array_intersect($programs, self::SIMPERUM_REQUIRED_PROGRAMS)) > 0;
    }

    const ROWS = [
        // A                    B          C           D                  E                          F                                                                G                                       H                I                              J
        ['income_gt_8_5',       [9, 10],   null,       null,              null,                      null,                                                            null,                                   null,            'family_single',               'Oemah Lestari Non-Subsidi'],
        ['income_gt_10',        [9, 10],   null,       null,              null,                      null,                                                            null,                                   null,            'family_married',              'Oemah Lestari Non-Subsidi'],
        ['income_2_8_8_5',      [5, 6, 7, 8], null,    null,              'house_none_or_rent',      null,                                                            'work_stable_or_unstable_no_subsidy',  'produktif_21',  'family_single',               'KPR-FLPP / Oemah Lestari Subsidi'],
        ['income_2_8_10',       [5, 6, 7, 8], null,    null,              'house_none_or_rent',      null,                                                            'work_stable_or_unstable_no_subsidy',  'produktif_21',  'family_married',              'KPR-FLPP / Oemah Lestari Subsidi'],
        ['income_2_2_2_8',      [4],       'dtks_ya',  'land_none',       'house_none_or_rent',      'env_safe',                                                      'work_can_save_irregular',             'produktif_21',  null,                          'KPR-FLPP'],
        ['income_0_1_5',        [1],       'dtks_ya',  'land_legal',      'house_rent_or_staying',   'env_safe',                                                      null,                                   'produktif_18',  'family_multi_household',      'PB Backlog (Prioritas 1)'],
        ['income_1_5_2_2',      [2, 3],    'dtks_ya',  'land_legal',      'house_rent_or_staying',   'env_safe',                                                      null,                                   'produktif_18',  'family_multi_household',      'PB Backlog (Prioritas 2)'],
        ['income_2_2_2_8',      [4],       'dtks_ya',  'land_legal',      'house_rent_or_staying',   'env_safe',                                                      null,                                   'produktif_18',  'family_multi_household',      'PB Backlog (Prioritas 3)'],
        ['income_0_1_5',        [1],       'dtks_ya',  'land_legal',      'house_restricted_area',   'env_relocation_zone',                                           null,                                   null,            'family_head_of_household',    'PB Relokasi (Prioritas 1)'],
        ['income_1_5_2_2',      [2, 3],    'dtks_ya',  'land_legal',      'house_restricted_area',   'env_relocation_zone',                                           null,                                   null,            'family_head_of_household',    'PB Relokasi (Prioritas 2)'],
        ['income_2_2_2_8',      [4],       'dtks_ya',  'land_legal',      'house_restricted_area',   'env_relocation_zone',                                           null,                                   null,            'family_head_of_household',    'PB Relokasi (Prioritas 3)'],
        ['income_0_1_5',        [1],       'dtks_ya',  null,              'house_disaster_affected', 'env_disaster_severe',                                           null,                                   null,            null,                          'PB Bencana (Prioritas 1)'],
        ['income_1_5_2_2',      [2, 3],    'dtks_ya',  null,              'house_disaster_affected', 'env_disaster_severe',                                           null,                                   null,            null,                          'PB Bencana (Prioritas 2)'],
        ['income_2_2_2_8',      [4],       'dtks_ya',  null,              'house_disaster_affected', 'env_disaster_severe',                                           null,                                   null,            null,                          'PB Bencana (Prioritas 3)'],
        ['income_0_1_5',        [1],       'dtks_ya',  null,              'house_disaster_affected', 'env_disaster_moderate',                                         null,                                   null,            null,                          'PK Bencana (Prioritas 1)'],
        ['income_1_5_2_2',      [2, 3],    'dtks_ya',  null,              'house_disaster_affected', 'env_disaster_moderate',                                         null,                                   null,            null,                          'PK Bencana (Prioritas 2)'],
        ['income_2_2_2_8',      [4],       'dtks_ya',  null,              'house_disaster_affected', 'env_disaster_moderate',                                         null,                                   null,            null,                          'PK Bencana (Prioritas 3)'],
        ['income_0_1_5',        [1],       'dtks_ya',  null,              'house_owned',             'env_slum_uninhabitable',                                        null,                                   'produktif_18_or_tua', null,                   'PK RTLH (Prioritas 1)'],
        ['income_1_5_2_2',      [2, 3],    'dtks_ya',  null,              'house_owned',             'env_slum_uninhabitable',                                        null,                                   'produktif_18_or_tua', null,                   'PK RTLH (Prioritas 2)'],
        ['income_2_2_2_8',      [4],       'dtks_ya',  null,              'house_owned',             'env_slum_uninhabitable',                                        null,                                   'produktif_18_or_tua', null,                   'PK RTLH (Prioritas 3)'],
    ];

    /**
     * @param array $input Kunci yang dipakai: income_code, welfare_decile,
     *   dtks_code, land_code, housing_code, environment_code,
     *   occupation_code, age_years, family_code - null/'' berarti belum
     *   diisi (baris yang mensyaratkan kolom itu otomatis tidak cocok,
     *   BUKAN dianggap wildcard - beda dari 'Tidak Dibatasi' di sumbernya
     *   yang memang sengaja tidak mempedulikan kolom itu).
     * @return string[] Daftar teks "PROGRAM YANG COCOK" (kolom J) dari
     *   setiap baris yang cocok, urutan sama seperti urutan baris di
     *   Sheet3 (baris lebih awal = prioritas lebih tinggi kalau lebih
     *   dari satu baris cocok sekaligus). Kosong kalau tidak ada yang cocok.
     */
    public function match(array $input)
    {
        $matches = [];
        foreach (self::ROWS as $row) {
            [$income, $deciles, $dtks, $land, $housing, $environment, $occupation, $age_rule, $family, $program] = $row;

            if ($income !== null && $income !== ($input['income_code'] ?? NULL)) { continue; }
            if ($deciles !== null) {
                $decile = filter_var($input['welfare_decile'] ?? NULL, FILTER_VALIDATE_INT);
                if ($decile === FALSE || ! in_array($decile, $deciles, TRUE)) { continue; }
            }
            if ($dtks !== null && $dtks !== ($input['dtks_code'] ?? NULL)) { continue; }
            if ($land !== null && $land !== ($input['land_code'] ?? NULL)) { continue; }
            if ($housing !== null && $housing !== ($input['housing_code'] ?? NULL)) { continue; }
            if ($environment !== null && $environment !== ($input['environment_code'] ?? NULL)) { continue; }
            if ($occupation !== null && $occupation !== ($input['occupation_code'] ?? NULL)) { continue; }
            if ($age_rule !== null && ! $this->age_matches($age_rule, $input['age_years'] ?? NULL)) { continue; }
            if ($family !== null && $family !== ($input['family_code'] ?? NULL)) { continue; }

            $matches[] = $program;
        }
        return $matches;
    }

    /**
     * Tiga aturan umur PERSIS 3 varian yang muncul di kolom H Sheet3 -
     * bukan 3 kategori umum di catatan kaki xlsx ("Kategori Usia dibagi
     * menjadi..."), karena beberapa baris mensyaratkan batas LEBIH KETAT
     * (Min. 21 Tahun, bukan 18) daripada definisi umum "usia produktif"
     * di catatan kaki itu - 21 tahun adalah usia dewasa penuh menurut
     * hukum perdata lama Indonesia, masuk akal untuk baris yang
     * melibatkan kapasitas hukum (KPR/pembiayaan), beda dari baris
     * "usia produktif" biasa (pekerjaan) yang cukup 18 tahun.
     */
    private function age_matches($rule, $age_years)
    {
        if ($age_years === null || $age_years === '') { return FALSE; }
        $age = (int) $age_years;
        if ($rule === 'produktif_21') { return $age >= 21 && $age < 60; }
        if ($rule === 'produktif_18') { return $age >= 18 && $age < 60; }
        if ($rule === 'produktif_18_or_tua') { return $age >= 18; }
        return TRUE;
    }
}
