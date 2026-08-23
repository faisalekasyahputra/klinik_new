<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Mesin pencocokan 20 baris "MATRIKS VARIABEL PENENTUAN PROGRAM
 * PERUMAHAN.xlsx" Sheet4 - permintaan user 23 Agt 2026: "Hasil Rekomendasi
 * ... sesuaikan dengan xlsx kolom J".
 *
 * TERPISAH dari Warga_ruleset.php (mesin lama yang MASIH DIPAKAI di step
 * "Lengkapi Data SIMPERUM" seterusnya/review) - dua sistem rekomendasi
 * yang hidup berdampingan, bukan saling menggantikan. Ini KHUSUS untuk
 * step "Isi Data Sesuai Matriks" (Hasil Rekomendasi awal), sumber datanya
 * 8 field matrix_*_code + umur (dihitung dari tanggal lahir) - PERSIS
 * kolom A & C-I xlsx.
 *
 * KOLOM B (Desil) DITURUNKAN DARI KOLOM A (Gaji), BUKAN dari
 * welfare_decile profil - permintaan user 23 Agt 2026 ("samakan
 * logikanya dengan xlsx-nya di Sheet4"), sesudah ditemukan lewat tes
 * langsung bahwa welfare_decile (angka simulasi dari SIMPERUM, sumbernya
 * TIDAK terkait dengan Gaji yang baru saja dipilih warga di step ini)
 * bisa membuat kombinasi yang menurut xlsx seharusnya cocok (Gaji &
 * Status Perkawinan sudah pas) jadi tidak cocok gara-gara desil profil
 * kebetulan berbeda golongan. DIPERIKSA LANGSUNG ke tiap baris Sheet4:
 * pasangan Gaji-Desil di 20 baris itu KONSISTEN SEMPURNA (Gaji '0-1,5jt'
 * SELALU berpasangan dengan 'Desil 1', '1,5-2,2jt' SELALU 'Desil 2-3',
 * dst, tidak pernah pasangan gaji yang sama muncul dengan desil
 * berbeda) - artinya Desil di tabel ini memang REDUNDAN terhadap Gaji,
 * bukan dimensi independen sungguhan yang butuh sumber data terpisah.
 * INCOME_TO_DECILE di bawah ini salinan LANGSUNG pasangan itu.
 *
 * ROWS = salinan LANGSUNG kolom A-J Sheet4 baris 3-22, diterjemahkan ke
 * kode field yang dipakai form (lihat pendataan.php step 'housing_family'
 * untuk kamus kode lengkapnya). `null` pada suatu kolom = "Tidak Dibatasi"
 * di xlsx asli - baris itu TIDAK MEMPEDULIKAN kolom tersebut sama sekali,
 * cocok dengan jawaban apa pun. Ini PERSIS makna yang diminta user
 * sebelumnya ("'Tidak Dibatasi' artinya tidak dimasukkan ke logika") -
 * makanya opsi itu dihapus dari pilihan warga (tidak boleh dipilih
 * sebagai JAWABAN) tapi tetap hidup DI SINI sebagai wildcard pencocokan.
 *
 * Format tiap baris (larik, bukan asosiatif - ringkas, urutan tetap TETAP
 * kolom xlsx A,B,C,D,E,F,G,H,I,J):
 *   [income_code, desil_set|null, dtks_code|null, land_code|null,
 *    housing_code|null, environment_code|null, occupation_code|null,
 *    age_rule|null, family_code|null, program_text]
 */
class Matriks_program_ruleset {

    /* Gaji -> satu angka desil REPRESENTATIF dari rentang yang dipakai
       baris-baris Sheet4 (mis. income_1_5_2_2 -> 2, cukup untuk lolos
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

    /** Label PERSIS kolom B Sheet4, dipakai tampilan "Kategori Kemiskinan
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
     *   Sheet4 (baris lebih awal = prioritas lebih tinggi kalau lebih
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
     * Tiga aturan umur PERSIS 3 varian yang muncul di kolom H Sheet4 -
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
