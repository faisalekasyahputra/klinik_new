<?php
require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php'; // lokasi .env (luar akar dulu)
date_default_timezone_set('Asia/Jakarta');
/**
 * Uji Rekam Data PBG dan BPHTB (permintaan dinas 30 Okt 2026) lewat HTTP sungguhan:
 * menu, templat XLSX, unggah per bulan yang ditolak per baris, penggantian berkas,
 * unduh ter-scope, dan superadmin yang bertindak atas nama kabupaten.
 *
 *   php docs/engineering/uji_pbg_bphtb.php
 *
 * Env opsional: UJI_BASE_URL. Tahun sentinel 2098 dan akun uji_pb_* sendiri (bukan akun demo yang dipinjam
 * suite lain), supaya bisa berjalan berdampingan dengan uji_bnba_per_sumber (2099). Jejaknya dihapus di akhir.
 * Data orang di berkas uji karangan (NIK 3399..., nama "Uji ..."), bukan dari berkas dinas.
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('TAHUN', 2098);

$total = 0; $gagal = 0;
function cek($ok, $label) { global $total, $gagal; $total++; echo ($ok ? '  OK    ' : '  GAGAL ') . $label . "\n"; if ( ! $ok) { $gagal++; } return (bool) $ok; }
function wajib($ok, $label) { if ( ! cek($ok, $label)) { fwrite(STDERR, "Berhenti: prasyarat gagal.\n"); bersihkan(); exit(1); } }

$env = [];
foreach (file(env_berkas_path(APP_ROOT), FILE_IGNORE_NEW_LINES) as $l) {
    if (strpos($l, '=') === FALSE || ltrim($l)[0] === '#') { continue; }
    [$k, $v] = explode('=', $l, 2);
    if ( ! isset($env[trim($k)])) { $env[trim($k)] = trim($v, " \t\"'"); }
}
$db = new mysqli($env['DB_HOST'] ?? 'localhost', $env['DB_USER'] ?? 'root', $env['DB_PASS'] ?? '', $env['DB_NAME'] ?? 'klinikpkp');
function q($sql, $p = []) {
    global $db;
    $st = $db->prepare($sql);
    if ($p) { $st->bind_param(str_repeat('s', count($p)), ...$p); }
    $st->execute();
    $r = $st->get_result();
    return $r ? $r->fetch_assoc() : NULL;
}

require_once APP_ROOT . '/vendor/autoload.php';
define('BASEPATH', 'uji');
define('FCPATH', APP_ROOT . '/');
if ( ! empty($env['PRIVATE_UPLOADS_PATH'])) { putenv('PRIVATE_UPLOADS_PATH=' . $env['PRIVATE_UPLOADS_PATH']); }
require_once APP_ROOT . '/application/helpers/private_upload_helper.php';

$jars = []; $tmp = []; $mulai = date('Y-m-d H:i:s', time() - 1);
function jar($n) { global $jars; return $jars[$n] ??= tempnam(sys_get_temp_dir(), 'ujipb_'); }
function http($n, $path, $post = NULL) {
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_COOKIEJAR => jar($n), CURLOPT_COOKIEFILE => jar($n),
        CURLOPT_FOLLOWLOCATION => TRUE, CURLOPT_TIMEOUT => 60]);
    if ($post !== NULL) { curl_setopt($ch, CURLOPT_POST, TRUE); curl_setopt($ch, CURLOPT_POSTFIELDS, $post); }
    $body = (string) curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body];
}
function csrf($n, $path) { return preg_match('/name="csrf_kpkp_token" value="([^"]+)"/', http($n, $path)['body'], $m) ? $m[1] : ''; }
function login($n, $email, $sandi) {
    $t = csrf($n, 'Auth/login');
    $ch = curl_init(BASE_URL . '/Auth/do_login');
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_COOKIEJAR => jar($n), CURLOPT_COOKIEFILE => jar($n),
        CURLOPT_POST => TRUE, CURLOPT_HTTPHEADER => ['X-Requested-With: XMLHttpRequest'],
        CURLOPT_POSTFIELDS => http_build_query(['csrf_kpkp_token' => $t, 'email' => $email, 'password' => $sandi])]);
    $j = json_decode((string) curl_exec($ch), TRUE);
    curl_close($ch);
    return ($j['status'] ?? '') === 'success';
}
/** Unggah lewat form layar (token dari halaman yang merender form), balikan halaman sesudah redirect. */
function unggah($n, $kelas, $bulan, $berkas) {
    return http($n, $kelas . '/unggah', ['csrf_kpkp_token' => csrf($n, $kelas . '?tahun=' . TAHUN), 'tahun' => TAHUN,
        'bulan' => $bulan, 'berkas' => new CURLFile($berkas, 'application/octet-stream', 'data.xlsx')])['body'];
}
function templat($n, $kelas) {
    global $tmp;
    $r = http($n, $kelas . '/templat');
    file_put_contents($p = $tmp[] = tempnam(sys_get_temp_dir(), 'ujipb_') . '.xlsx', $r['body']);
    return [$r, $p];
}
/** Isi templat seperti petugas: sel teks, lalu simpan dengan nilai rumus ikut tersimpan (seperti Excel). */
function isi($templat, array $per_lembar) {
    global $tmp;
    $ss = \PhpOffice\PhpSpreadsheet\IOFactory::load($templat);
    foreach ($per_lembar as $nama => $baris) {
        $sh = $ss->getSheetByName($nama);
        foreach ($baris as $i => $r) {
            foreach ($r as $kol => $v) {
                $sh->setCellValueExplicit($kol . ($i + 2), (string) $v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }
    }
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save($p = $tmp[] = tempnam(sys_get_temp_dir(), 'ujipb_') . '.xlsx');
    return $p;
}
function baris_db($jenis, $bulan) { return q('SELECT * FROM rd_pbg_bphtb WHERE jenis = ? AND tahun = ? AND bulan = ?', [$jenis, TAHUN, $bulan]); }
function berkas_dir() { global $KAB; clearstatcache(); return glob(private_uploads_dir('rekam_pbg_bphtb', $KAB) . '*') ?: []; }

function bersihkan() {
    global $db, $jars, $tmp, $mulai, $KAB;
    if ( ! empty($KAB)) {
        $res = $db->query('SELECT path_privat FROM rd_pbg_bphtb WHERE tahun = ' . TAHUN);
        while ($res && ($r = $res->fetch_assoc())) { @unlink(private_uploads_dir('rekam_pbg_bphtb', $KAB) . basename($r['path_privat'])); }
    }
    $db->query('DELETE FROM rd_pbg_bphtb WHERE tahun = ' . TAHUN);
    $db->query("DELETE FROM sys_batas_laju WHERE jendela_mulai_at >= '" . $db->real_escape_string($mulai) . "'");
    $db->query("DELETE FROM usr_akun WHERE email LIKE 'uji_pb_%'");
    foreach (array_merge($jars, $tmp) as $f) { @unlink($f); }
}

echo "Uji Rekam Data PBG dan BPHTB\n";
$KAB = (int) (q('SELECT k.kabupaten_id FROM kecamatan k JOIN desa d ON d.kecamatan_id = k.id ORDER BY k.kabupaten_id LIMIT 1')['kabupaten_id'] ?? 0);
wajib($KAB > 0, 'Ada kabupaten bertabel desa untuk akun uji');
$NAMA_KAB = (string) q('SELECT nama FROM kabupaten WHERE id = ?', [$KAB])['nama'];
$DESA = q('SELECT d.id AS kode, d.nama AS desa, k.nama AS kec FROM desa d JOIN kecamatan k ON k.id = d.kecamatan_id
           WHERE k.kabupaten_id = ? ORDER BY d.id LIMIT 1', [$KAB]);
wajib($DESA !== NULL, 'Kabupaten penguji punya desa di tabel desa');
bersihkan();

$stamp = time(); $sandi = 'Uji#' . bin2hex(random_bytes(6)) . 'A1';
foreach (['kab' => "'admin_kabkota', NULL, $KAB", 'bidang' => "'admin_bidang', 'perumahan', NULL", 'super' => "'admin', NULL, NULL"] as $k => $peran) {
    $db->query(sprintf("INSERT INTO usr_akun (email, kata_sandi, peran, bidang_kode, kabupaten_id, nama, nama_pengguna) VALUES ('uji_pb_%s_%d@example.test', '%s', %s, 'Uji PB', 'uji_pb_%s_%d')",
        $k, $stamp, $db->real_escape_string(password_hash($sandi, PASSWORD_DEFAULT)), $peran, $k, $stamp));
}
wajib(login('kab', "uji_pb_kab_{$stamp}@example.test", $sandi), 'Login admin_kabkota uji');
wajib(login('bid', "uji_pb_bidang_{$stamp}@example.test", $sandi), 'Login admin_bidang');
wajib(login('sup', "uji_pb_super_{$stamp}@example.test", $sandi), 'Login superadmin');

// Baris PBG: [kolom => nilai]. Nama desa ditulis huruf kecil, seperti petugas mengetik.
$peng = fn($bulan = 'Maret') => ['A' => '1', 'B' => 'Perumahan Uji', 'C' => 'PT Uji Bangun', 'D' => 'Jl. Uji 1', 'E' => 'tapak', 'F' => '36/72', 'G' => $bulan];
$oran = fn($nik, $bulan = 'Maret', $desa = NULL) => ['A' => '1', 'B' => $nik, 'C' => 'Uji Perorangan', 'D' => 'RT 01 RW 02',
    'E' => strtolower($DESA['kec']), 'F' => $desa ?? strtolower($DESA['desa']), 'H' => $bulan];

try {
    echo "\n[1] Menu dan templat\n";
    $r = http('kab', 'Rekam_Pbg');
    cek($r['code'] === 200 && strpos($r['body'], 'Rekam_Pbg/unggah') !== FALSE, 'Layar PBG terbuka untuk admin kab/kota');
    cek(strpos($r['body'], base_url_rel('Rekam_Pbg')) !== FALSE && strpos($r['body'], base_url_rel('Rekam_Bphtb')) !== FALSE
        && strpos($r['body'], '>PBG<') !== FALSE && strpos($r['body'], '>BPHTB<') !== FALSE, 'Sidebar Rekam Data memuat menu PBG dan BPHTB');
    cek(preg_match('#href="[^"]*Rekam_Pbg/templat"[^>]*\bdownload\b#', $r['body']) === 1, 'Tautan templat memakai atribut download (#129)');

    [$t, $TPBG] = templat('kab', 'Rekam_Pbg');
    cek($t['code'] === 200 && substr($t['body'], 0, 2) === 'PK', 'Templat PBG adalah XLSX asli');
    $ss = \PhpOffice\PhpSpreadsheet\IOFactory::load($TPBG);
    $per = $ss->getSheetByName('PERORANGAN'); $kw = $ss->getSheetByName('KODE WILAYAH');
    cek($ss->getSheetByName('PENGEMBANG') && $per && $kw, 'Templat PBG punya lembar PENGEMBANG, PERORANGAN, KODE WILAYAH');
    cek($per->getStyle('B2')->getNumberFormat()->getFormatCode() === '@', 'NIK perorangan berformat Teks');
    cek(strpos((string) $per->getCell('G2')->getValue(), 'VLOOKUP') !== FALSE, 'KODE KELURAHAN/DESA berumus VLOOKUP');
    $kode_kw = [];
    for ($i = 2; $i <= $kw->getHighestRow(); $i++) { $kode_kw[] = (string) $kw->getCell("D$i")->getValue(); }
    cek($kode_kw && ! array_filter($kode_kw, fn($k) => strpos($k, (string) $KAB) !== 0) && in_array((string) $DESA['kode'], $kode_kw, TRUE),
        'KODE WILAYAH hanya desa kabupaten pengunduh (' . count($kode_kw) . ' desa)');

    [$t, $TBP] = templat('kab', 'Rekam_Bphtb');
    $bp = \PhpOffice\PhpSpreadsheet\IOFactory::load($TBP)->getSheetByName('BPHTB');
    cek($t['code'] === 200 && $bp && $bp->getCell('AH1')->getValue() === 'Apakah MBR atau bukan' && $bp->getCell('B1')->getValue() === 'Nama Kab/Kota',
        'Templat BPHTB memuat 34 kolom dinas (A sampai AH)');
    cek($bp->getStyle('O2')->getNumberFormat()->getFormatCode() === '@', 'NIK Penerima berformat Teks');

    echo "\n[2] Unggah PBG\n";
    $awal = count(berkas_dir());
    $b = unggah('kab', 'Rekam_Pbg', 3, isi($TPBG, ['PENGEMBANG' => [$peng()], 'PERORANGAN' => [$oran('3399010101010001'), $oran('339901010101002')]]));
    cek(strpos($b, 'Baris PERORANGAN 3: NIK harus 16 angka') !== FALSE, 'NIK 15 angka ditolak dengan nomor baris dan lembarnya');
    cek(baris_db('pbg', 3) === NULL && count(berkas_dir()) === $awal, 'Berkas ditolak: tidak tercatat dan dibuang dari disk');

    $b = unggah('kab', 'Rekam_Pbg', 3, isi($TPBG, ['PENGEMBANG' => [$peng('April')], 'PERORANGAN' => []]));
    cek(strpos($b, 'Baris PENGEMBANG 2: Status Data (Bulan) harus Maret') !== FALSE, 'Bulan baris yang beda dari bulan pilihan ditolak');

    $b = unggah('kab', 'Rekam_Pbg', 3, isi($TPBG, ['PERORANGAN' => [$oran('3399010101010001', 'Maret', 'desa tak ada')]]));
    cek(strpos($b, 'Baris PERORANGAN 2: KODE KELURAHAN/DESA kosong') !== FALSE, 'Desa salah ketik (kode tidak terisi) ditolak');

    $b = unggah('kab', 'Rekam_Pbg', 3, isi($TPBG, ['PENGEMBANG' => [$peng()], 'PERORANGAN' => [$oran('3399010101010001'), $oran('3399010101010002')]]));
    $row = baris_db('pbg', 3);
    cek(strpos($b, 'PBG Maret ' . TAHUN . ' tersimpan: Pengembang 1, perorangan 2') !== FALSE, 'Berkas sah tersimpan dengan pesan ringkasan');
    cek($row && (int) $row['jumlah_baris'] === 3 && (int) $row['kabupaten_id'] === $KAB && in_array(private_uploads_dir('rekam_pbg_bphtb', $KAB) . $row['path_privat'], berkas_dir(), TRUE),
        'Tercatat 3 baris untuk kabupaten pelapor, berkas privat di disk');
    cek(strpos(json_encode(q('SELECT * FROM rd_pbg_bphtb WHERE id = ?', [$row['id']])), '3399010101010001') === FALSE, 'NIK tidak disalin ke DB');

    $b = unggah('kab', 'Rekam_Pbg', 3, isi($TPBG, ['PENGEMBANG' => [$peng()]]));
    $baru = baris_db('pbg', 3);
    cek($baru && (int) $baru['id'] === (int) $row['id'] && (int) $baru['jumlah_baris'] === 1, 'Unggah ulang bulan sama menggantikan, bukan menambah');
    cek( ! in_array(private_uploads_dir('rekam_pbg_bphtb', $KAB) . $row['path_privat'], berkas_dir(), TRUE), 'Berkas lama dibuang dari disk');

    echo "\n[3] Unggah BPHTB\n";
    $bph = fn($kec, $kab = NULL, $nik = '3399010101010003') => ['A' => '1', 'B' => $kab ?? $NAMA_KAB, 'C' => (string) TAHUN, 'D' => $kec, 'E' => $DESA['desa'],
        'N' => 'Uji Penerima', 'O' => $nik, 'R' => '3500000', 'V' => '60', 'AA' => '150.000.000', 'AF' => 'Mei', 'AH' => 'MBR'];
    $b = unggah('kab', 'Rekam_Bphtb', 5, isi($TBP, ['BPHTB' => [$bph($DESA['kec']), $bph('Kecamatan Tak Ada')]]));
    cek(strpos($b, 'Baris 3: Nama Kecamatan') !== FALSE && baris_db('bphtb', 5) === NULL, 'Kecamatan tak dikenal ditolak dengan nomor baris');
    $b = unggah('kab', 'Rekam_Bphtb', 5, isi($TBP, ['BPHTB' => [$bph($DESA['kec'], 'Kabupaten Tak Ada')]]));
    cek(strpos($b, 'Baris 2: Nama Kab') !== FALSE, 'Kabupaten lain ditolak');
    $b = unggah('kab', 'Rekam_Bphtb', 5, isi($TBP, ['BPHTB' => [$bph($DESA['kec'], NULL, '3399')]]));
    cek(strpos($b, 'Baris 2: NIK Penerima harus 16 angka') !== FALSE, 'NIK Penerima pendek ditolak');
    $b = unggah('kab', 'Rekam_Bphtb', 5, isi($TBP, ['BPHTB' => [$bph(strtolower($DESA['kec'])), $bph($DESA['kec'])]]));
    cek(strpos($b, 'BPHTB Mei ' . TAHUN . ' tersimpan: 2 transaksi') !== FALSE && (int) (baris_db('bphtb', 5)['jumlah_baris'] ?? 0) === 2, 'BPHTB sah tersimpan (2 transaksi)');

    echo "\n[4] Unduh ter-scope\n";
    $id_pbg = (int) baris_db('pbg', 3)['id'];
    $u = http('kab', 'Rekam_Pbg/unduh/' . $id_pbg);
    cek($u['code'] === 200 && substr($u['body'], 0, 2) === 'PK', 'Admin kabupaten pemilik mengunduh berkasnya');
    cek(substr(http('kab', 'Rekam_Bphtb/unduh/' . $id_pbg)['body'], 0, 2) !== 'PK', 'Id PBG tidak bisa diunduh lewat jalur BPHTB');
    cek(substr(http('bid', 'Rekam_Pbg/unduh/' . $id_pbg)['body'], 0, 2) !== 'PK', 'Admin bidang tidak bisa mengunduh');
    cek(substr(http('sup', 'Rekam_Pbg/unduh/' . $id_pbg)['body'], 0, 2) !== 'PK', 'Superadmin tanpa bertindak tidak bisa mengunduh');

    echo "\n[5] Superadmin bertindak atas nama kabupaten\n";
    http('sup', 'Admin_Rekam_Data/bertindak', ['csrf_kpkp_token' => csrf('sup', 'Admin_Rekam_Data'), 'kabupaten_id' => $KAB,
        'domain' => 'perumahan', 'tahun' => TAHUN, 'triwulan' => 1]);
    $r = http('sup', 'Rekam_Pbg?tahun=' . TAHUN);
    cek($r['code'] === 200 && strpos($r['body'], 'atas nama') !== FALSE && strpos($r['body'], 'Rekam_Pbg/unduh/' . $id_pbg) !== FALSE,
        'Superadmin melihat daftar PBG kabupaten yang diwakili');
    cek(strpos($r['body'], base_url_rel('Rekam_Bphtb') . '"') !== FALSE, 'Banner bertindak menautkan menu PBG dan BPHTB');
    $u = http('sup', 'Rekam_Pbg/unduh/' . $id_pbg);
    cek($u['code'] === 200 && substr($u['body'], 0, 2) === 'PK', 'Superadmin yang bertindak bisa mengunduh');
} finally {
    bersihkan();
}

cek(q('SELECT COUNT(*) AS n FROM rd_pbg_bphtb WHERE tahun = ?', [TAHUN])['n'] == 0, 'Data uji dibersihkan');
echo "\nRINGKASAN: $total pemeriksaan, $gagal gagal\n";
exit($gagal > 0 ? 1 : 0);

function base_url_rel($path) { return BASE_URL . '/' . $path; }
