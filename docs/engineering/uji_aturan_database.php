<?php
/*
 * Uji kesetaraan tahap 1 aturan program (migrasi 084, 26 Okt 2026): aturan rekomendasi pindah dari
 * konstanta kode ke database TANPA mengubah hasil. Mesin baru (data dari DB lokal) dibandingkan dengan
 * mesin LAMA yang diambil utuh dari commit 15a97a0 (terakhir sebelum pemindahan) lewat `git show`, pada
 * kombinasi data warga acak berbenih tetap. Selama versi aturan masih SIM-2026-02 / MATRIKS-2026-09-10,
 * hasil keduanya wajib identik; begitu dinas menerbitkan versi lain (tahap 2), uji ini hanya berlaku untuk
 * versi lama itu. Jalankan: php docs/engineering/uji_aturan_database.php
 */
date_default_timezone_set('Asia/Jakarta');
define('BASEPATH', __DIR__);
define('KOMIT_LAMA', '15a97a0');
$akar = dirname(__DIR__, 2);
require $akar . '/application/libraries/Warga_ruleset.php';
require $akar . '/application/libraries/Matriks_program_ruleset.php';
require __DIR__ . '/_aturan_db.php';

$total = 0; $gagal = 0;
function cek($ok, $label) { global $total, $gagal; $total++; echo ($ok ? '  OK    ' : '  GAGAL ') . "$label\n"; if ( ! $ok) { $gagal++; } }

// Mesin lama: sumber utuh dari commit lama, kelas diberi nama lain supaya hidup berdampingan.
foreach (['Warga_ruleset' => 'Warga_ruleset_lama', 'Matriks_program_ruleset' => 'Matriks_program_ruleset_lama'] as $kelas => $lama) {
    $src = shell_exec('git -C ' . escapeshellarg($akar) . ' show ' . KOMIT_LAMA . ':application/libraries/' . $kelas . '.php');
    if ( ! is_string($src) || strpos($src, "class $kelas") === FALSE) { fwrite(STDERR, "Tidak bisa membaca $kelas dari " . KOMIT_LAMA . "\n"); exit(1); }
    eval(preg_replace(['/^<\?php/', '/defined\(\'BASEPATH\'\).*\n/', "/class $kelas\b/"], ['', '', "class $lama"], $src));
}

$w_lama = new Warga_ruleset_lama(); $w_baru = new Warga_ruleset(aturan_program_uji());
$m_lama = new Matriks_program_ruleset_lama(); $m_baru = new Matriks_program_ruleset(aturan_matriks_uji());

echo "1. ISI DATABASE = KONSTANTA LAMA\n";
cek($m_baru instanceof Matriks_program_ruleset && (new ReflectionProperty($m_baru, 'baris'))->getValue($m_baru) === Matriks_program_ruleset_lama::ROWS,
    '20 baris matriks di database sama persis dengan ROWS lama (urutan, desil, kolom, nama skema)');
cek(Warga_ruleset::VERSION === Warga_ruleset_lama::VERSION && Matriks_program_ruleset::VERSION === Matriks_program_ruleset_lama::VERSION,
    'Kode versi aturan tidak berubah (' . Warga_ruleset::VERSION . ', ' . Matriks_program_ruleset::VERSION . ')');

echo "\n2. ROUTING DESIL\n";
$beda = [];
foreach ([NULL, '', 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, -1, '4'] as $d) {
    if ($w_lama->route_candidates($d) !== $w_baru->route_candidates($d)) { $beda[] = var_export($d, TRUE); }
}
cek( ! $beda, 'route_candidates identik untuk desil kosong, 0 sampai 11, dan teks' . ($beda ? ' - beda: ' . implode(', ', $beda) : ''));

echo "\n3. EVALUASI DAN MATRIKS PADA DATA ACAK\n";
mt_srand(84);
$pilih = fn(array $a) => $a[mt_rand(0, count($a) - 1)];
$kondisi = ['good', 'minor_damage', 'moderate_damage', 'severe_damage_or_absent', NULL];
$kode = ['rtlh', 'pb', 'omah_sekeng', 'flpp', 'oemah_lestari', 'rumah_apung', 'tidak_ada'];
$beda_e = []; $beda_m = []; $n = 6000; $status = [];
for ($i = 0; $i < $n; $i++) {
    $draft = [
        'jalur_penilaian' => $pilih(['existing_house', 'candidate_land', 'financing', 'undetermined', NULL]),
        'kepemilikan_rumah' => $pilih(['owned', 'rent', 'rent_free', 'staying', 'official', 'other', '', NULL]),
        'rumah_lain' => $pilih(['0', '1', '', NULL]), 'tanah_lain' => $pilih(['0', '1', '', NULL]),
        'kawasan_perumahan' => $pilih(['good', 'drought', 'slum', 'poor_other', 'riverbank', 'railway', 'disaster_prone', '', NULL]),
        'jml_kk' => $pilih([NULL, 1, 2, 3]),
        'sumber_air' => $pilih(['pdam', 'other_unfit', NULL]), 'jenis_kloset' => $pilih(['swan_neck', 'none', NULL]),
        'punya_lahan_calon' => $pilih([NULL, '0', '1']), 'candidate_land_address' => $pilih([NULL, 'Jl. Uji']),
        'status_lahan_calon' => $pilih([NULL, 'hm']), 'asal_lahan_calon' => $pilih([NULL, 'own']),
        'panjang_lahan_m' => $pilih([NULL, 0, 8]), 'lebar_lahan_m' => $pilih([NULL, 0, 6]), 'luas_lahan_m2' => $pilih([NULL, 0, 48]),
        'matriks_status_dtks' => $pilih([NULL, 'dtks_ya', 'dtks_tidak']),
    ];
    foreach (['kondisi_pondasi', 'kondisi_kolom', 'kondisi_balok', 'kondisi_sloof', 'kondisi_rangka', 'kondisi_lantai', 'kondisi_dinding', 'kondisi_atap'] as $k) { $draft[$k] = $pilih($kondisi); }
    $profil = [
        'desil_kesejahteraan' => $pilih([NULL, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]),
        'penghasilan_bulanan' => $pilih([NULL, -5, 0, 900000, 1500000, 1500001, 2200000, 2500000, 2800000, 4000000, 8500000, 9000000, 10000000, 15000000]),
        'kelompok_penghasilan' => $pilih([NULL, '', 'income_2_8_8_5']),
        'mampu_swadaya' => $pilih([NULL, '', 'capable', 'not_capable']),
        'stabilitas_pekerjaan' => $pilih([NULL, 'permanent', 'non_permanent']),
        'status_perkawinan' => $pilih([NULL, 'single', 'married', 'divorced']),
        'mode_sumber' => $pilih(['api', 'simulation', 'manual', NULL]),
        'birth_date' => $pilih([NULL, '1990-05-01', '2008-01-01', '1960-01-01', '2005-10-26', 'salah']),
    ];
    $c = $pilih($kode);
    $a = $w_lama->evaluate($c, $draft, $profil); $b = $w_baru->evaluate($c, $draft, $profil);
    $status[$a['status_kelayakan']] = TRUE;
    if ($a !== $b) { $beda_e[] = $c; }
    $pa = $m_lama->preliminary($draft, $profil, '2026-10-26'); $pb = $m_baru->preliminary($draft, $profil, '2026-10-26');
    unset($pa['evaluated_at'], $pb['evaluated_at']);
    if ($pa !== $pb) { $beda_m[] = $i; }
}
cek( ! $beda_e, "evaluate() identik pada $n kombinasi acak (" . count($status) . ' jenis hasil muncul: ' . implode(', ', array_keys($status)) . ')'
    . ($beda_e ? ' - beda pada ' . count($beda_e) . ' kasus, mis. ' . $beda_e[0] : ''));
cek(count($status) === 4, 'Data acak mencakup keempat hasil (eligible, potential, needs_data, not_eligible)');
cek( ! $beda_m, "preliminary() matriks identik pada $n kombinasi acak" . ($beda_m ? ' - beda pada ' . count($beda_m) . ' kasus' : ''));

echo "\n4. GAGAL KERAS BILA ATURAN KOSONG\n";
foreach (['Warga_ruleset', 'Matriks_program_ruleset'] as $kelas) {
    try { new $kelas([]); $ok = FALSE; } catch (RuntimeException $e) { $ok = strpos($e->getMessage(), 'migrasi 084') !== FALSE; }
    cek($ok, "$kelas menolak aturan kosong dengan pesan yang menyebut migrasi 084, bukan diam-diam tanpa rekomendasi");
}

echo "\n=== $total pemeriksaan, $gagal merah ===\n";
exit($gagal ? 1 : 0);
