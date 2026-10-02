<?php
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
define('BASEPATH', __DIR__);
require dirname(__DIR__, 2) . '/application/libraries/Matriks_program_ruleset.php';
$rules = new Matriks_program_ruleset();
$total = 0;
function cek($ok, $label) { global $total; $total++; if (!$ok) { fwrite(STDERR, "GAGAL: $label\n"); exit(1); } echo "  OK    $label\n"; }
cek(method_exists($rules, 'preliminary'), 'Wizard membutuhkan perhitungan matriks dari nominal penghasilan');
$profile = ['penghasilan_bulanan'=>1200000, 'birth_date'=>'1990-01-01', 'status_perkawinan'=>'married'];
$draft = ['matriks_rumah_sekarang'=>'house_owned', 'matriks_kondisi_lingkungan'=>'env_slum_uninhabitable', 'matriks_status_dtks'=>'dtks_ya'];
$result = $rules->preliminary($draft, $profile, '2026-09-10');
cek(array_column($result['items'], 'program_name') === ['PK RTLH (Prioritas 1)'], 'Rumah kumuh pendapatan 1,2 juta menghasilkan PK RTLH prioritas 1');
cek($result['items'][0]['missing'] === [], 'Syarat lengkap cocok');
$draft['matriks_status_dtks'] = NULL;
$result = $rules->preliminary($draft, $profile, '2026-09-10');
// UAT dinas warga #10 (2 Okt 2026): Status DTKS dihilangkan, jadi tidak lagi disyaratkan maupun ditampilkan.
cek($result['items'][0]['missing'] === [] && ! in_array('Status DTKS: Ya', $result['items'][0]['criteria'], TRUE), 'DTKS kosong tidak lagi menahan atau disebut di rekomendasi');
cek($rules->kode_katalog('PK RTLH (Prioritas 1)') === 'rtlh' && $rules->kode_katalog('PB Backlog (Prioritas 2)') === 'pb' && $rules->kode_katalog('KPR-FLPP') === 'flpp' && $rules->kode_katalog('Oemah Lestari Non-Subsidi') === 'oemah_lestari', 'Nama program matriks terpetakan ke katalog untuk deskripsinya');
$draft['matriks_kondisi_lingkungan'] = 'env_safe';
cek($rules->preliminary($draft, $profile, '2026-09-10')['items'] === [], 'Rumah aman tidak memperoleh rekomendasi RTLH/FLPP bawaan');
$profile['penghasilan_bulanan'] = 11000000;
cek(array_column($rules->preliminary($draft, $profile, '2026-09-10')['items'], 'program_name') === ['Oemah Lestari Non-Subsidi'], 'Pendapatan tinggi mengikuti matriks');
$profile['penghasilan_bulanan'] = 5000000;
$draft = ['matriks_rumah_sekarang'=>'house_none_or_rent', 'matriks_pekerjaan_keuangan'=>'work_stable_or_unstable_no_subsidy'];
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
        $draft = ['matriks_rumah_sekarang'=>$housing,'matriks_kondisi_lingkungan'=>$environment,'matriks_status_keluarga'=>$family,'matriks_kepemilikan_lahan'=>'land_legal','matriks_status_dtks'=>'dtks_ya'];
        $profile = ['penghasilan_bulanan'=>$amount,'birth_date'=>'1990-01-01','status_perkawinan'=>'married'];
        $items = $rules->preliminary($draft,$profile,'2026-09-10')['items'];
        cek(array_column($items,'program_name') === [$program.' (Prioritas '.($priority+1).')'] && $items[0]['missing'] === [], $program.' prioritas '.($priority+1));
    }
}
$draft = ['matriks_rumah_sekarang'=>'house_none_or_rent','matriks_kepemilikan_lahan'=>'land_none','matriks_kondisi_lingkungan'=>'env_safe','matriks_status_dtks'=>'dtks_ya','matriks_pekerjaan_keuangan'=>'work_can_save_irregular'];
cek(array_column($rules->preliminary($draft,$profile,'2026-09-10')['items'],'program_name') === ['KPR-FLPP'], 'Sheet3 baris 7 FLPP rentan miskin');
foreach (['single'=>9000000,'married'=>11000000] as $marital=>$amount) {
    $profile['penghasilan_bulanan']=$amount; $profile['status_perkawinan']=$marital;
    cek(array_column($rules->preliminary($draft,$profile,'2026-09-10')['items'],'program_name') === ['Oemah Lestari Non-Subsidi'], 'Batas non-subsidi '.$marital);
    $profile['penghasilan_bulanan']=5000000; $draft['matriks_pekerjaan_keuangan']='work_stable_or_unstable_no_subsidy';
    cek(array_column($rules->preliminary($draft,$profile,'2026-09-10')['items'],'program_name') === ['KPR-FLPP / Oemah Lestari Subsidi'], 'Batas subsidi '.$marital);
}
$profile = ['penghasilan_bulanan'=>9000000,'birth_date'=>'1990-01-01','status_perkawinan'=>'married'];
$draft = ['matriks_rumah_sekarang'=>'house_none_or_rent','matriks_pekerjaan_keuangan'=>'work_stable_or_unstable_no_subsidy','matriks_status_keluarga'=>'family_single'];
cek(array_column($rules->preliminary($draft,$profile,'2026-09-10')['items'],'program_name') === ['KPR-FLPP / Oemah Lestari Subsidi'], 'Status perkawinan profil menjadi satu sumber batas penghasilan');
echo "$total pemeriksaan lulus\n";
