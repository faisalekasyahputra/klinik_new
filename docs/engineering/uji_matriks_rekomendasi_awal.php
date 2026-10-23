<?php
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
define('BASEPATH', __DIR__);
require dirname(__DIR__, 2) . '/application/libraries/Matriks_program_ruleset.php';
$rules = new Matriks_program_ruleset();
$total = 0;
function cek($ok, $label) { global $total; $total++; if (!$ok) { fwrite(STDERR, "GAGAL: $label\n"); exit(1); } echo "  OK    $label\n"; }
/* Sejak 23 Okt 2026 kolom D/E/F/G/I tidak ditanyakan lagi (permintaan dinas) dan diturunkan dari data warga
   (Matriks_program_ruleset::turunan). Draft di uji ini karena itu berisi data sumber, bukan kode matriks. */
$program = fn($draft, $profile) => array_column($rules->preliminary($draft, $profile, '2026-09-10')['items'], 'program_name');
cek(method_exists($rules, 'preliminary'), 'Wizard membutuhkan perhitungan matriks dari nominal penghasilan');
$profile = ['penghasilan_bulanan'=>1200000, 'birth_date'=>'1990-01-01', 'status_perkawinan'=>'married', 'stabilitas_pekerjaan'=>'permanent'];
$draft = ['kepemilikan_rumah'=>'owned', 'kawasan_perumahan'=>'slum'];
$result = $rules->preliminary($draft, $profile, '2026-09-10');
cek(array_column($result['items'], 'program_name') === ['PK RTLH (Prioritas 1)'], 'Rumah milik di kawasan kumuh, pendapatan 1,2 juta: PK RTLH prioritas 1');
cek($result['items'][0]['missing'] === [], 'Syarat lengkap cocok tanpa isian matriks');
// UAT dinas warga #10 (2 Okt 2026): Status DTKS dihilangkan, jadi tidak lagi disyaratkan maupun ditampilkan.
cek(! in_array('Status DTKS: Ya', $result['items'][0]['criteria'], TRUE), 'DTKS kosong tidak lagi menahan atau disebut di rekomendasi');
cek($rules->kode_katalog('PK RTLH (Prioritas 1)') === 'rtlh' && $rules->kode_katalog('PB Backlog (Prioritas 2)') === 'pb' && $rules->kode_katalog('KPR-FLPP') === 'flpp' && $rules->kode_katalog('Oemah Lestari Non-Subsidi') === 'oemah_lestari', 'Nama program matriks terpetakan ke katalog untuk deskripsinya');
$draft['kawasan_perumahan'] = 'good';
cek($program($draft, $profile) === [], 'Rumah milik di kawasan baik tanpa data SIMPERUM tidak memperoleh PK RTLH');
cek($program($draft, $profile + ['mode_sumber'=>'simulation']) === ['PK RTLH (Prioritas 1)'], 'Rumah milik yang datanya dari SIMPERUM (basis data RTLH) tetap memperoleh PK RTLH di kawasan baik');
$profile['penghasilan_bulanan'] = 11000000;
cek($program($draft, $profile) === ['Oemah Lestari Non-Subsidi'], 'Pendapatan tinggi mengikuti matriks');
$profile['penghasilan_bulanan'] = 5000000;
$draft = ['kepemilikan_rumah'=>'other', 'kawasan_perumahan'=>'good'];
cek($program($draft, $profile) === ['KPR-FLPP / Oemah Lestari Subsidi'], 'MBR belum punya rumah mengikuti matriks tanpa desil SIMPERUM');
$profile['birth_date'] = '2010-01-01';
cek($program($draft, $profile) === [], 'Batas usia matriks ditegakkan');
// Lima kelompok bantuan x tiga prioritas, sesuai Sheet3 J8:J22.
foreach ([1200000,1800000,2500000] as $priority=>$amount) {
    foreach ([
        ['PB Backlog',  ['kepemilikan_rumah'=>'staying', 'kawasan_perumahan'=>'good', 'tanah_lain'=>'1', 'jml_kk'=>'2']],
        ['PB Relokasi', ['kepemilikan_rumah'=>'staying', 'kawasan_perumahan'=>'riverbank', 'tanah_lain'=>'1', 'jml_kk'=>'1']],
        ['PB Bencana',  ['kepemilikan_rumah'=>'owned', 'kawasan_perumahan'=>'disaster_prone', 'kondisi_kolom'=>'severe_damage_or_absent']],
        ['PK Bencana',  ['kepemilikan_rumah'=>'owned', 'kawasan_perumahan'=>'disaster_prone', 'kondisi_kolom'=>'moderate_damage']],
        ['PK RTLH',     ['kepemilikan_rumah'=>'owned', 'kawasan_perumahan'=>'slum']],
    ] as [$nama, $draft]) {
        $profile = ['penghasilan_bulanan'=>$amount,'birth_date'=>'1990-01-01','status_perkawinan'=>'married','stabilitas_pekerjaan'=>'permanent'];
        $items = $rules->preliminary($draft,$profile,'2026-09-10')['items'];
        cek(array_column($items,'program_name') === [$nama.' (Prioritas '.($priority+1).')'] && $items[0]['missing'] === [], $nama.' prioritas '.($priority+1));
    }
}
$draft = ['kepemilikan_rumah'=>'other','kawasan_perumahan'=>'good','tanah_lain'=>'0'];
$profile['stabilitas_pekerjaan'] = 'non_permanent';
cek($program($draft,$profile) === ['KPR-FLPP'], 'Sheet3 baris 7 FLPP rentan miskin (penghasilan tidak tetap, tanpa lahan)');
foreach (['single'=>9000000,'married'=>11000000] as $marital=>$amount) {
    $profile['penghasilan_bulanan']=$amount; $profile['status_perkawinan']=$marital;
    cek($program($draft,$profile) === ['Oemah Lestari Non-Subsidi'], 'Batas non-subsidi '.$marital);
    $profile['penghasilan_bulanan']=5000000;
    cek($program($draft,$profile) === ['KPR-FLPP / Oemah Lestari Subsidi'], 'Batas subsidi '.$marital.' (penghasilan tidak tetap juga memenuhi)');
}
$profile = ['penghasilan_bulanan'=>9000000,'birth_date'=>'1990-01-01','status_perkawinan'=>'married','stabilitas_pekerjaan'=>'permanent'];
cek($program(['kepemilikan_rumah'=>'other','kawasan_perumahan'=>'good'],$profile) === ['KPR-FLPP / Oemah Lestari Subsidi'], 'Status perkawinan profil menjadi satu sumber batas penghasilan');
cek($program(['kepemilikan_rumah'=>'staying','kawasan_perumahan'=>'riverbank'],$profile) === ['KPR-FLPP / Oemah Lestari Subsidi'], 'Bukan milik di kawasan relokasi tetap tergolong belum punya rumah');

// Turunan kolom matriks dari data warga (aturan dinas 23 Okt 2026, celah ditambal keputusan user).
$t = fn($draft, $profile = []) => Matriks_program_ruleset::turunan($draft, $profile);
cek($t(['kepemilikan_rumah'=>'owned'])['lahan'] === ['land_legal'] && $t(['kepemilikan_rumah'=>'rent','tanah_lain'=>'0'])['lahan'] === ['land_none']
    && $t(['kepemilikan_rumah'=>'rent','tanah_lain'=>'1'])['lahan'] === ['land_legal'] && $t(['kepemilikan_rumah'=>'rent'])['lahan'] === [],
    'Lahan: milik sendiri = sah; selain itu dari Memiliki tanah lain; tanpa data = kosong');
cek($t(['kawasan_perumahan'=>'railway'])['lingkungan'] === ['env_relocation_zone'] && $t(['kawasan_perumahan'=>'poor_other'])['lingkungan'] === ['env_slum_uninhabitable']
    && $t(['kawasan_perumahan'=>'drought'])['lingkungan'] === ['env_safe'] && $t([])['lingkungan'] === [],
    'Lingkungan dari Kawasan: rel KA = relokasi, kawasan buruk lain = kumuh, kekeringan = aman');
cek($t(['kepemilikan_rumah'=>'official'])['rumah'] === ['house_none_or_rent'] && $t(['kepemilikan_rumah'=>'rent_free'])['rumah'] === ['house_rent_or_staying']
    && $t(['kepemilikan_rumah'=>'owned','kawasan_perumahan'=>'disaster_prone'])['rumah'] === ['house_owned', 'house_disaster_affected'] && $t([])['rumah'] === [],
    'Rumah: Dinas = belum punya, Bebas Sewa = menumpang/sewa, milik di rawan bencana juga terdampak');
cek($t([], ['stabilitas_pekerjaan'=>'non_permanent'])['finansial'] === ['work_stable_or_unstable_no_subsidy', 'work_can_save_irregular']
    && $t([], ['stabilitas_pekerjaan'=>'permanent'])['finansial'] === ['work_stable_or_unstable_no_subsidy'] && $t([])['finansial'] === [],
    'Finansial dari Status pekerjaan: tidak tetap memenuhi kedua kategori');
cek($t([], ['status_perkawinan'=>'married'])['keluarga'] === ['family_married', 'family_head_of_household']
    && $t(['jml_kk'=>'3'], ['status_perkawinan'=>'divorced'])['keluarga'] === ['family_head_of_household', 'family_multi_household']
    && $t([], ['status_perkawinan'=>'single'])['keluarga'] === ['family_single'],
    'Keluarga dari Status perkawinan: menikah dan cerai juga kepala keluarga, Jumlah KK > 1 = lebih dari 1 KK');
$hasil = $rules->preliminary(['kepemilikan_rumah'=>'staying','kawasan_perumahan'=>'good','jml_kk'=>'2'], ['penghasilan_bulanan'=>1200000,'birth_date'=>'1990-01-01','status_perkawinan'=>'married','stabilitas_pekerjaan'=>'permanent'], '2026-09-10');
cek(array_column($hasil['items'], 'program_name') === ['PB Backlog (Prioritas 1)'] && $hasil['items'][0]['missing'] === ['Kepemilikan lahan'],
    'Tanpa data tanah lain, PB Backlog tetap tampil dengan catatan Kepemilikan lahan perlu dilengkapi');
echo "$total pemeriksaan lulus\n";
