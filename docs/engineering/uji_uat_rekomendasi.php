<?php
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
define('BASEPATH', __DIR__);
require dirname(__DIR__, 2) . '/application/libraries/Warga_ruleset.php';
require dirname(__DIR__, 2) . '/application/libraries/Matriks_program_ruleset.php';
$matriks = new Matriks_program_ruleset();
$rules = new Warga_ruleset();
$checks = [
    'Data manual tetap punya kandidat untuk dilengkapi' => $rules->route_candidates(NULL) === ['flpp', 'oemah_lestari'],
    'Desil kosong tidak menjadi kelayakan bantuan' => $rules->evaluate('flpp', [], [])['status_kelayakan'] === 'needs_data',
    'Cabang tanah tidak menutup pembiayaan sebelum data lengkap' => $rules->evaluate('flpp', ['jalur_penilaian'=>'candidate_land', 'kepemilikan_rumah'=>'other'], ['desil_kesejahteraan'=>6, 'penghasilan'=>3000000])['status_kelayakan'] === 'needs_data',
    'Pendapatan angka diterima untuk pemeriksaan pembiayaan' => $rules->evaluate('flpp', ['jalur_penilaian'=>'candidate_land', 'kepemilikan_rumah'=>'other', 'rumah_lain'=>'0'], ['desil_kesejahteraan'=>6, 'penghasilan'=>3000000])['status_kelayakan'] === 'potential',
    // Desil turunan pendapatan (27 Sep 2026): rentang Sheet3 yang sama dengan rekomendasi awal.
    'Desil dari pendapatan mengikuti rentang Sheet3' => array_map([$matriks, 'decile_for_monthly_income'], [0, 1500000, 1500001, 2200000, 2800000, 2800001, 8500000, 8500001]) === [1, 1, 2, 2, 4, 5, 5, 9],
    'Pendapatan kosong tidak menjadi desil' => $matriks->decile_for_monthly_income(NULL) === NULL && $matriks->decile_for_monthly_income('') === NULL && $matriks->decile_for_monthly_income(-5) === NULL,
    'Pemilik rumah lain tidak lolos pembiayaan' => $rules->evaluate('flpp', ['jalur_penilaian'=>'candidate_land', 'kepemilikan_rumah'=>'other', 'rumah_lain'=>'1'], ['desil_kesejahteraan'=>6, 'penghasilan'=>3000000])['status_kelayakan'] === 'not_eligible',
];
foreach ($checks as $label=>$ok) echo ($ok ? '[OK] ' : '[FAIL] ') . $label . PHP_EOL;
exit(in_array(FALSE, $checks, TRUE) ? 1 : 0);
