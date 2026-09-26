<?php
define('BASEPATH', __DIR__);
require dirname(__DIR__, 2) . '/application/libraries/Warga_ruleset.php';
require dirname(__DIR__, 2) . '/application/libraries/Matriks_program_ruleset.php';
$matriks = new Matriks_program_ruleset();
$rules = new Warga_ruleset();
$checks = [
    'Data manual tetap punya kandidat untuk dilengkapi' => $rules->route_candidates(NULL) === ['flpp', 'oemah_lestari'],
    'Desil kosong tidak menjadi kelayakan bantuan' => $rules->evaluate('flpp', [], [])['eligibility_status'] === 'needs_data',
    'Cabang tanah tidak menutup pembiayaan sebelum data lengkap' => $rules->evaluate('flpp', ['assessment_track'=>'candidate_land', 'housing_status_code'=>'other'], ['welfare_decile'=>6, 'monthly_income'=>3000000])['eligibility_status'] === 'needs_data',
    'Pendapatan angka diterima untuk pemeriksaan pembiayaan' => $rules->evaluate('flpp', ['assessment_track'=>'candidate_land', 'housing_status_code'=>'other', 'has_other_house'=>'0'], ['welfare_decile'=>6, 'monthly_income'=>3000000])['eligibility_status'] === 'potential',
    // Desil turunan pendapatan (27 Sep 2026): rentang Sheet3 yang sama dengan rekomendasi awal.
    'Desil dari pendapatan mengikuti rentang Sheet3' => array_map([$matriks, 'decile_for_monthly_income'], [0, 1500000, 1500001, 2200000, 2800000, 2800001, 8500000, 8500001]) === [1, 1, 2, 2, 4, 5, 5, 9],
    'Pendapatan kosong tidak menjadi desil' => $matriks->decile_for_monthly_income(NULL) === NULL && $matriks->decile_for_monthly_income('') === NULL && $matriks->decile_for_monthly_income(-5) === NULL,
    'Pemilik rumah lain tidak lolos pembiayaan' => $rules->evaluate('flpp', ['assessment_track'=>'candidate_land', 'housing_status_code'=>'other', 'has_other_house'=>'1'], ['welfare_decile'=>6, 'monthly_income'=>3000000])['eligibility_status'] === 'not_eligible',
];
foreach ($checks as $label=>$ok) echo ($ok ? '[OK] ' : '[FAIL] ') . $label . PHP_EOL;
exit(in_array(FALSE, $checks, TRUE) ? 1 : 0);
