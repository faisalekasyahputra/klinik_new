<?php
define('BASEPATH', __DIR__);
require dirname(__DIR__, 2) . '/application/libraries/Matriks_program_ruleset.php';
$rules = new Matriks_program_ruleset();
$total = 0;
function cek($ok, $label) { global $total; $total++; if (!$ok) { fwrite(STDERR, "GAGAL: $label\n"); exit(1); } echo "  OK    $label\n"; }
cek(method_exists($rules, 'preliminary'), 'Wizard membutuhkan perhitungan matriks dari nominal penghasilan');
$profile = ['monthly_income'=>1200000, 'birth_date'=>'1990-01-01', 'marital_status_code'=>'married'];
$draft = ['matrix_current_housing_code'=>'house_owned', 'matrix_environment_condition_code'=>'env_slum_uninhabitable', 'matrix_dtks_status'=>'dtks_ya'];
$result = $rules->preliminary($draft, $profile, '2026-09-10');
cek(array_column($result['items'], 'program_name') === ['PK RTLH (Prioritas 1)'], 'Rumah kumuh pendapatan 1,2 juta menghasilkan PK RTLH prioritas 1');
cek($result['items'][0]['missing'] === [], 'Syarat lengkap cocok');
$draft['matrix_dtks_status'] = NULL;
$result = $rules->preliminary($draft, $profile, '2026-09-10');
cek($result['items'][0]['missing'] === ['Status DTKS'], 'DTKS kosong tidak dianggap YA');
$draft['matrix_environment_condition_code'] = 'env_safe';
cek($rules->preliminary($draft, $profile, '2026-09-10')['items'] === [], 'Rumah aman tidak memperoleh rekomendasi RTLH/FLPP bawaan');
$profile['monthly_income'] = 11000000;
cek(array_column($rules->preliminary($draft, $profile, '2026-09-10')['items'], 'program_name') === ['Oemah Lestari Non-Subsidi'], 'Pendapatan tinggi mengikuti matriks');
$profile['monthly_income'] = 5000000;
$draft = ['matrix_current_housing_code'=>'house_none_or_rent', 'matrix_occupation_finance_code'=>'work_stable_or_unstable_no_subsidy'];
cek(array_column($rules->preliminary($draft, $profile, '2026-09-10')['items'], 'program_name') === ['KPR-FLPP / Oemah Lestari Subsidi'], 'MBR belum punya rumah mengikuti matriks tanpa desil SIMPERUM');
$profile['birth_date'] = '2010-01-01';
cek($rules->preliminary($draft, $profile, '2026-09-10')['items'] === [], 'Batas usia matriks ditegakkan');
// Lima kelompok bantuan x tiga prioritas, sesuai Sheet3 J8:J22.
foreach ([1200000,1800000,2500000] as $priority=>$amount) {
    foreach ([
        ['PB Backlog','house_rent_or_staying','env_safe','family_multi_household'],
        ['PB Relokasi','house_restricted_area','env_relocation_zone','family_head_of_household'],
        ['PB Bencana','house_disaster_affected','env_disaster_severe',NULL],
        ['PK Bencana','house_disaster_affected','env_disaster_moderate',NULL],
        ['PK RTLH','house_owned','env_slum_uninhabitable',NULL],
    ] as [$program,$housing,$environment,$family]) {
        $draft = ['matrix_current_housing_code'=>$housing,'matrix_environment_condition_code'=>$environment,'matrix_marital_family_code'=>$family,'matrix_land_ownership_code'=>'land_legal','matrix_dtks_status'=>'dtks_ya'];
        $profile = ['monthly_income'=>$amount,'birth_date'=>'1990-01-01','marital_status_code'=>'married'];
        $items = $rules->preliminary($draft,$profile,'2026-09-10')['items'];
        cek(array_column($items,'program_name') === [$program.' (Prioritas '.($priority+1).')'] && $items[0]['missing'] === [], $program.' prioritas '.($priority+1));
    }
}
$draft = ['matrix_current_housing_code'=>'house_none_or_rent','matrix_land_ownership_code'=>'land_none','matrix_environment_condition_code'=>'env_safe','matrix_dtks_status'=>'dtks_ya','matrix_occupation_finance_code'=>'work_can_save_irregular'];
cek(array_column($rules->preliminary($draft,$profile,'2026-09-10')['items'],'program_name') === ['KPR-FLPP'], 'Sheet3 baris 7 FLPP rentan miskin');
foreach (['single'=>9000000,'married'=>11000000] as $marital=>$amount) {
    $profile['monthly_income']=$amount; $profile['marital_status_code']=$marital;
    cek(array_column($rules->preliminary($draft,$profile,'2026-09-10')['items'],'program_name') === ['Oemah Lestari Non-Subsidi'], 'Batas non-subsidi '.$marital);
    $profile['monthly_income']=5000000; $draft['matrix_occupation_finance_code']='work_stable_or_unstable_no_subsidy';
    cek(array_column($rules->preliminary($draft,$profile,'2026-09-10')['items'],'program_name') === ['KPR-FLPP / Oemah Lestari Subsidi'], 'Batas subsidi '.$marital);
}
$profile = ['monthly_income'=>9000000,'birth_date'=>'1990-01-01','marital_status_code'=>'married'];
$draft = ['matrix_current_housing_code'=>'house_none_or_rent','matrix_occupation_finance_code'=>'work_stable_or_unstable_no_subsidy','matrix_marital_family_code'=>'family_single'];
cek(array_column($rules->preliminary($draft,$profile,'2026-09-10')['items'],'program_name') === ['KPR-FLPP / Oemah Lestari Subsidi'], 'Status perkawinan profil menjadi satu sumber batas penghasilan');
echo "$total pemeriksaan lulus\n";
