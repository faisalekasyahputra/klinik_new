<?php
define('BASEPATH', __DIR__);
require dirname(__DIR__, 2) . '/application/libraries/Warga_ruleset.php';
$rules = new Warga_ruleset();
$checks = [
    'Data manual tetap punya kandidat untuk dilengkapi' => $rules->route_candidates(NULL) === ['flpp', 'oemah_lestari'],
    'Desil kosong tidak menjadi kelayakan bantuan' => $rules->evaluate('flpp', [], [])['eligibility_status'] === 'needs_data',
    'Cabang tanah tidak menutup pembiayaan sebelum data lengkap' => $rules->evaluate('flpp', ['assessment_track'=>'candidate_land', 'housing_status_code'=>'other'], ['welfare_decile'=>6, 'monthly_income'=>3000000])['eligibility_status'] === 'needs_data',
    'Pendapatan angka diterima untuk pemeriksaan pembiayaan' => $rules->evaluate('flpp', ['assessment_track'=>'candidate_land', 'housing_status_code'=>'other', 'has_other_house'=>'0'], ['welfare_decile'=>6, 'monthly_income'=>3000000])['eligibility_status'] === 'potential',
    'Pemilik rumah lain tidak lolos pembiayaan' => $rules->evaluate('flpp', ['assessment_track'=>'candidate_land', 'housing_status_code'=>'other', 'has_other_house'=>'1'], ['welfare_decile'=>6, 'monthly_income'=>3000000])['eligibility_status'] === 'not_eligible',
];
foreach ($checks as $label=>$ok) echo ($ok ? '[OK] ' : '[FAIL] ') . $label . PHP_EOL;
exit(in_array(FALSE, $checks, TRUE) ? 1 : 0);
