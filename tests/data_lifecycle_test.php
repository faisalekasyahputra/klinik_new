<?php
/**
 * Penjaga regresi siklus hidup informasi yang dikecualikan: pertukaran dan audit (form keamanan poin 7.3).
 * Offline: tanpa basis data dan tanpa jaringan. Jalankan:  php tests/data_lifecycle_test.php
 * Bukti penghapusan dan retensi atas MySQL nyata: tests/data_lifecycle_db_test.php.
 */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

define('BASEPATH', __DIR__ . '/../system/');
$app = realpath(__DIR__ . '/../application');
$akar = realpath(__DIR__ . '/..');
$config = []; require $app . '/config/data_lifecycle.php'; $LC = $config['data_lifecycle'];
require $app . '/libraries/Data_erasure.php';
require $app . '/libraries/Penyapu_retensi.php';

$total = 0;
function check($kondisi, $pesan) { global $total; $total++; if ( ! $kondisi) { throw new RuntimeException($pesan); } }
function sumber($rel) {
    global $app;
    $t = @file_get_contents($app . '/' . $rel);
    if ($t === FALSE) { throw new RuntimeException("Tidak bisa membaca application/$rel"); }
    return $t;
}

// ------------------------------------------------------------------ 1. Kebijakan retensi masuk akal
$r = $LC['retensi'];
foreach (['interval_detik', 'snapshot_simperum_lewat_hari', 'rate_limit_hari', 'token_surel_lewat_hari', 'langganan_push_nonaktif_hari', 'log_aplikasi_hari', 'jejak_audit_hari'] as $k) {
    check(isset($r[$k]) && is_int($r[$k]) && $r[$k] > 0, "Retensi $k harus bilangan bulat positif");
}
check($r['interval_detik'] >= 3600 && $r['interval_detik'] <= 7 * 86400, 'Interval penyapu harus antara 1 jam dan 7 hari');
check($r['jejak_audit_hari'] >= 730, 'Jejak audit tidak boleh disapu lebih cepat dari 2 tahun');
check($r['log_aplikasi_hari'] >= 90, 'Log aplikasi tidak boleh disapu lebih cepat dari 90 hari (bahan penyelidikan insiden)');
check($r['rate_limit_hari'] >= 1, 'Penghitung laju tidak boleh disapu sebelum jendela terpanjangnya (1 hari) berakhir');
$config = []; require $app . '/config/rate_limits.php';
$maks_jendela = 0; foreach ($config['rate_limit_policies'] as $p) { $maks_jendela = max($maks_jendela, (int) $p['window']); }
check($r['rate_limit_hari'] * 86400 >= $maks_jendela, "Retensi penghitung laju ({$r['rate_limit_hari']} hari) harus mencakup jendela terpanjang ($maks_jendela detik)");

// ------------------------------------------------------------------ 2. Register pertukaran = daftar koneksi keluar yang diizinkan
$pindai = (string) file_get_contents($akar . '/docs/engineering/pindai_kode_berbahaya.php');
check(preg_match('/const PINDAI_JARINGAN_DIIZINKAN = \[(.*?)\n\];/s', $pindai, $blok) === 1, 'Daftar PINDAI_JARINGAN_DIIZINKAN tidak ditemukan');
preg_match_all("/^\s*'([^']+\.php)'\s*=>/m", $blok[1], $mm);
$diizinkan = $mm[1];
check(count($diizinkan) >= 8, 'Daftar koneksi keluar yang dibaca terlalu sedikit (' . count($diizinkan) . ')');
$register = $LC['pertukaran'];
$belum = array_diff($diizinkan, array_keys($register));
check($belum === [], 'Berkas dengan koneksi keluar yang diizinkan tetapi TIDAK ada di register pertukaran data: ' . implode(', ', $belum));
$basi = array_diff(array_keys($register), $diizinkan);
check($basi === [], 'Register pertukaran memuat berkas yang tidak ada di daftar koneksi keluar: ' . implode(', ', $basi));
foreach ($register as $berkas => $e) {
    check(is_file($app . '/' . $berkas), "Register menunjuk berkas yang tidak ada: $berkas");
    foreach (['pihak', 'arah', 'data', 'perlindungan'] as $k) { check(isset($e[$k]) && strlen(trim($e[$k])) >= 5, "Register $berkas: '$k' wajib diisi"); }
    check(array_key_exists('pribadi', $e) && is_bool($e['pribadi']), "Register $berkas: 'pribadi' harus TRUE/FALSE");
    check(in_array($e['arah'], ['keluar', 'masuk', 'keluar+masuk'], TRUE), "Register $berkas: arah tidak dikenal");
    if ($e['pribadi']) {
        check(preg_match('/HTTPS|soket/i', $e['perlindungan']) === 1, "Register $berkas: aliran data PRIBADI harus menyebut HTTPS/soket sebagai perlindungan");
    }
}
check($register['libraries/Simperum_gateway.php']['pribadi'] === TRUE, 'Aliran NIK ke SIMPERUM harus ditandai sebagai data pribadi');

// ------------------------------------------------------------------ 3. Semua jalur unduhan/penyajian berkas diaudit
// Jalur langsung yang boleh ada: penyaji berkas privat, pengirim spreadsheet, ekspor akun, dan templat kosong.
$jalur = []; $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($app, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
    if ($f->getExtension() !== 'php' || strpos(str_replace('\\', '/', $f->getPathname()), '/views/') !== FALSE) { continue; }
    $s = file_get_contents($f->getPathname());
    $rel = ltrim(str_replace('\\', '/', substr($f->getPathname(), strlen($app))), '/');
    if (preg_match('/\breadfile\(|\bfpassthru\(|Content-Disposition/', $s)) { $jalur[] = $rel; }
}
sort($jalur);
check($jalur === ['controllers/Admin_Psu.php', 'controllers/Pengaturan.php', 'core/MY_Controller.php'], 'Jalur unduhan berkas berubah; tinjau audit untuk yang baru: ' . implode(', ', $jalur));
$my = sumber('core/MY_Controller.php');
check(preg_match('/function serve_private_file.*?catat_akses_data_pribadi\(\'berkas_privat\'.*?readfile/s', $my) === 1, 'serve_private_file harus mencatat akses staf sebelum menyajikan berkas');
check(preg_match('/function catat_akses_data_pribadi.*?peran_staf.*?audit_akses_dedupe.*?catat_audit\(\'akses_\'/s', $my) === 1, 'Audit akses hanya untuk staf, ditekan duplikatnya, dan tercatat lewat catat_audit');
check(sumber('controllers/Pengaturan.php') !== '' && strpos(sumber('controllers/Pengaturan.php'), "catat_audit('data_akun_diekspor'") !== FALSE && strpos(sumber('controllers/Pengaturan.php'), "catat_audit('ekspor_data_ditolak'") !== FALSE, 'Ekspor data akun (dan penolakannya) harus diaudit');
check(strpos(sumber('controllers/Pengaturan.php'), "rate_limit_consume('account_export'") !== FALSE, 'Ekspor data akun harus dibatasi lajunya');
// Setiap pemanggil kirim_spreadsheet mengaudit unduhannya.
$pemanggil = 0;
foreach (glob($app . '/controllers/*.php') as $f) {
    $s = file_get_contents($f);
    preg_match_all('/\R\s*(?:public|protected|private)?\s*function\s+(\w+)\s*\(/', $s, $mf, PREG_OFFSET_CAPTURE);
    foreach ($mf[1] as $i => [$nama, $pos]) {
        $body = substr($s, $pos, ($mf[1][$i + 1][1] ?? strlen($s)) - $pos);
        if (strpos($body, 'kirim_spreadsheet(') !== FALSE) {
            $pemanggil++;
            check(strpos($body, "catat_audit('rekap_diunduh'") !== FALSE, basename($f) . "::$nama mengirim spreadsheet tanpa mencatat audit 'rekap_diunduh'");
        }
    }
}
check($pemanggil >= 6, "Pemanggil kirim_spreadsheet yang ditemukan terlalu sedikit ($pemanggil)");
// Tampilan data pribadi terdekripsi ke staf dicatat.
check(preg_match('/function assessment_detail_data.*?catat_akses_data_pribadi\(\'penilaian_warga\'/s', $my) === 1, 'Profil warga terdekripsi ke staf harus dicatat');
$srp = sumber('controllers/Admin_Srp2.php');
check(strpos($srp, "catat_akses_data_pribadi('npwp_srp2'") !== FALSE && strpos($srp, "catat_akses_data_pribadi('pengajuan_srp2'") !== FALSE, 'NPWP dan detail pengajuan SRP2 ke staf harus dicatat');
foreach ($LC['audit']['peran_staf'] as $peran) { check(in_array($peran, ['admin', 'admin_kabkota', 'admin_bidang'], TRUE), "Peran staf '$peran' tidak dikenal"); }
check($LC['audit']['akses_dedupe_detik'] >= 60 && $LC['audit']['akses_dedupe_detik'] <= 3600, 'Penekan duplikat audit akses harus antara 1 menit dan 1 jam');

// ------------------------------------------------------------------ 4. Pseudonim surel (penghapusan)
$a = Data_erasure::pseudonim_surel('Budi@Contoh.id', 'k'); $b = Data_erasure::pseudonim_surel('budi@contoh.id ', 'k');
check($a === $b && preg_match('/^akun-dihapus-[0-9a-f]{12}$/', $a) === 1 && strpos($a, 'budi') === FALSE, 'Pseudonim stabil, tanpa bagian surel');
check(Data_erasure::pseudonim_surel('budi@contoh.id', 'k') !== Data_erasure::pseudonim_surel('budi@contoh.id', 'lain'), 'Pseudonim bergantung pada kunci (tidak dapat ditebak dari daftar surel tanpa kunci)');
check(strpos(sumber('models/User_model.php'), "getenv('KPKP_DATA_PEPPER')") !== FALSE, 'Hapus akun harus memakai KPKP_DATA_PEPPER untuk pseudonim');

// ------------------------------------------------------------------ 5. Penyapu: jatuh tempo, pelari CLI, pemicu web
$m = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'uji_penanda_' . bin2hex(random_bytes(3));
check(Penyapu_retensi::jatuh_tempo($m, 100), 'Tanpa penanda = jatuh tempo');
touch($m); check( ! Penyapu_retensi::jatuh_tempo($m, 100), 'Penanda baru = belum');
check(Penyapu_retensi::jatuh_tempo($m, 100, time() + 200), 'Sesudah interval = jatuh tempo'); @unlink($m);
$ctl = sumber('controllers/Retensi.php');
check(strpos($ctl, 'is_cli_request()') !== FALSE && strpos($ctl, 'show_404()') !== FALSE && strpos($ctl, "'kering'") !== FALSE, 'Pelari retensi hanya CLI dan punya mode kering');
check(preg_match('/function __construct\(\)\s*\{(.*?)\R    \}/s', $my, $ktor) === 1 && strpos($ktor[1], '$this->jadwalkan_retensi();') !== FALSE, 'Konstruktor MY_Controller harus memicu penyapu retensi');
check(preg_match('/function jadwalkan_retensi\(\).*?register_shutdown_function.*?fastcgi_finish_request.*?LOCK_EX \| LOCK_NB.*?catch \(Throwable/s', $my) === 1, 'Penyapu berjalan sesudah respons, dijaga flock, dan gagal diam-diam');
$pr = sumber('libraries/Penyapu_retensi.php');
check(strpos($pr, 'status <> \'draft\'') !== FALSE, 'Snapshot yang dirujuk penilaian terkirim harus dilindungi dari penyapu');

echo "data_lifecycle_test: OK ($total pemeriksaan; " . count($register) . " aliran data terdaftar, $pemanggil pengunduh spreadsheet diaudit)\n";
