<?php
require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php'; // lokasi .env (luar akar dulu)
date_default_timezone_set('Asia/Jakarta');
/**
 * Piagam KKN (permintaan dinas 1 Nov 2026, migrasi 091).
 *
 *   php docs/engineering/uji_kemitraan_piagam.php
 *
 * Dijaga:
 *   - Label surat "Surat Permohonan Pelaksanaan KKN" (bukan "menjadi mitra"); halaman mahasiswa memakai kata Piagam.
 *   - Universitas mengunggah daftar DPL dari Excel (Nama wajib, NIDN opsional); berkas tanpa kolom Nama ditolak;
 *     daftar terkunci begitu tanggal piagam ditetapkan.
 *   - Unduh semua piagam (ZIP) hanya sesudah terbit, berisi PDF tiap peserta dan DPL, dicatat di jejak audit;
 *     universitas lain tidak bisa mengunduh ZIP maupun piagam DPL milik KKN itu (anti-IDOR).
 *   - Piagam DPL per dosen tercetak sebagai PDF dengan kalimat Dosen Pembimbing Lapangan.
 *
 * Akun, KKN, peserta, DPL, dan jejak audit uji dibuat lalu dihapus sendiri.
 */
$AKAR = dirname(__DIR__, 2);
require $AKAR . '/vendor/autoload.php';
$BASE = rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/') . '/';
$env = [];
foreach (file(env_berkas_path($AKAR), FILE_IGNORE_NEW_LINES) as $l) { $l = trim($l); if ($l === '' || $l[0] === '#' || strpos($l, '=') === FALSE) continue; [$k, $v] = explode('=', $l, 2); if ( ! isset($env[trim($k)])) $env[trim($k)] = trim($v, " \t\"'"); }
if ( ! in_array(strtolower($env['DB_HOST'] ?? ''), ['localhost', '127.0.0.1', '::1'], TRUE)) { echo "  GAGAL DB lokal\nRINGKASAN: 1 pemeriksaan, 1 gagal\n"; exit(1); }
$db = new mysqli($env['DB_HOST'], $env['DB_USER'], $env['DB_PASS'] ?? '', $env['DB_NAME']);
$db->set_charset('utf8mb4');
$tag = 'ujipiagam' . bin2hex(random_bytes(3));
$sandi = 'Kp1#' . bin2hex(random_bytes(5));
$total = 0; $gagal = 0; $jars = []; $tmp = []; $akun = [];
$audit_awal = (int) $db->query('SELECT COALESCE(MAX(id),0) FROM sys_jejak_audit')->fetch_row()[0];
$cek = function ($ok, $l) use (&$total, &$gagal) { $total++; if ( ! $ok) $gagal++; echo ($ok ? '  OK    ' : '  GAGAL ') . $l . "\n"; };
$nilai = function ($sql) use ($db) { $r = $db->query($sql); $b = $r ? $r->fetch_row() : NULL; return $b ? $b[0] : NULL; };
// Badan mentah (ZIP/PDF tidak boleh lewat html_entity_decode).
$http = function ($jar, $path, $post = NULL) use ($BASE) {
    $ch = curl_init($BASE . $path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar, CURLOPT_TIMEOUT => 120]);
    if ($post !== NULL) {
        $berkas = (bool) array_filter($post, fn($v) => $v instanceof CURLFile);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $berkas ? $post : http_build_query($post));
    }
    $b = (string) curl_exec($ch); $k = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return ['badan' => $b, 'kode' => $k];
};
$csrf = function ($jar) { foreach (@file($jar) ?: [] as $l) { $p = explode("\t", trim($l)); if (($p[5] ?? '') === 'csrf_kpkp_cookie') return $p[6]; } return ''; };
$sesi = function () use (&$jars) { $j = tempnam(sys_get_temp_dir(), 'ukp'); $jars[] = $j; return $j; };
$buat_akun = function ($peran, $urut) use ($db, $tag, $sandi, &$akun) {
    $e = "{$tag}_{$urut}@piagam.test"; $h = password_hash($sandi, PASSWORD_BCRYPT); $nama = "Uji {$peran} {$tag} {$urut}";
    $st = $db->prepare("INSERT INTO usr_akun (nama,email,kata_sandi,peran,status,profil_lengkap,no_hp,email_verified_at,sandi_diganti_at,sandi_kedaluwarsa_at,created_at) VALUES (?,?,?,?,'active',1,'081200000000',NOW(),NOW(),DATE_ADD(NOW(),INTERVAL 90 DAY),NOW())");
    $st->bind_param('ssss', $nama, $e, $h, $peran); $st->execute(); $akun[] = (int) $db->insert_id;
    return [(int) $db->insert_id, $e, $nama];
};
$login = function ($email) use ($http, $csrf, $sesi, $sandi) { $j = $sesi(); $http($j, 'Auth/login'); $http($j, 'Auth/do_login', ['email' => $email, 'password' => $sandi, 'csrf_kpkp_token' => $csrf($j)]); return $j; };
$xlsx = function (array $baris) use (&$tmp) {
    $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $book->getActiveSheet()->fromArray($baris, NULL, 'A1');
    $f = tempnam(sys_get_temp_dir(), 'ukpx') . '.xlsx'; $tmp[] = $f;
    \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($book, 'Xlsx')->save($f); return $f;
};
$unggah_dpl = function ($jar, $kkn, $berkas) use ($http, $csrf) {
    return $http($jar, 'KemitraanPortal/kkn_upload_dpl/' . $kkn, ['csrf_kpkp_token' => $csrf($jar),
        'file_dpl' => new CURLFile($berkas, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'dpl.xlsx')]);
};
$teks_pdf = function ($pdf) {
    $teks = $pdf;
    if (preg_match_all('/stream\r?\n(.*?)\r?\nendstream/s', $pdf, $m)) { foreach ($m[1] as $b) { $teks .= (string) @gzuncompress($b); } }
    return $teks;
};

try {
    echo "=== UJI PIAGAM KKN (unduh massal dan DPL) ===\n";
    [$univ, $eUniv, $namaUniv] = $buat_akun('universitas', 'a');
    [, $eLain] = $buat_akun('universitas', 'b');
    $db->query("INSERT INTO kkn_magang_pendaftaran (user_id,jenis,instansi_asal,no_hp,divisi_atau_tema,periode_mulai,periode_selesai,status,created_at)
        VALUES ({$univ},'kkn','{$namaUniv}','081200000000','KKN piagam {$tag}','" . date('Y-m-d', strtotime('-60 days')) . "','" . date('Y-m-d', strtotime('-10 days')) . "','Diterima',NOW())");
    $kkn = (int) $db->insert_id;
    foreach ([['PG1' . strtoupper(bin2hex(random_bytes(3))), "Mahasiswa satu {$tag}"], ['PG2' . strtoupper(bin2hex(random_bytes(3))), "Mahasiswa dua {$tag}"]] as $i => $p) {
        $db->query("INSERT INTO kkn_peserta (pendaftaran_id,nim,nama,urut,created_at) VALUES ({$kkn},'{$p[0]}','{$p[1]}'," . ($i + 1) . ",NOW())");
    }

    echo "\n== Label\n";
    $jU = $login($eUniv);
    $dash = $http($jU, 'KemitraanPortal/kkn_dashboard')['badan'];
    $cek(strpos($dash, 'Surat Permohonan Pelaksanaan KKN') !== FALSE && stripos($dash, 'menjadi mitra') === FALSE, 'Form Tambah KKN memakai label Surat Permohonan Pelaksanaan KKN');
    $pub = $http($sesi(), 'KemitraanPortal/sertifikat_kkn')['badan'];
    $cek(strpos($pub, 'Cetak Piagam KKN') !== FALSE && strpos($pub, 'Cetak Sertifikat KKN') === FALSE, 'Halaman cetak mahasiswa memakai kata Piagam');

    echo "\n== Unggah DPL\n";
    $unggah_dpl($jU, $kkn, $xlsx([['NIDN', 'Keterangan'], ['0601', 'x']]));
    $cek((int) $nilai("SELECT COUNT(*) FROM kkn_dpl WHERE pendaftaran_id={$kkn}") === 0, 'Berkas tanpa kolom Nama ditolak');
    $unggah_dpl($jU, $kkn, $xlsx([['Nama', 'NIDN'], ["Dosen satu {$tag}", '06.0101'], ["Dosen dua {$tag}", '']]));
    $dpl = $db->query("SELECT id, nidn, nama FROM kkn_dpl WHERE pendaftaran_id={$kkn} ORDER BY urut")->fetch_all(MYSQLI_ASSOC);
    $cek(count($dpl) === 2 && $dpl[0]['nidn'] === '060101' && $dpl[1]['nidn'] === NULL, 'Daftar DPL tersimpan (NIDN dinormalkan, boleh kosong)');
    $detail = $http($jU, 'KemitraanPortal/pendaftaran/' . $kkn)['badan'];
    $cek(strpos($detail, 'data-kkn-dpl') !== FALSE && strpos($detail, "Dosen satu {$tag}") !== FALSE && strpos($detail, 'data-unduh-piagam') === FALSE,
        'Detail KKN menampilkan DPL; tombol unduh piagam belum muncul sebelum terbit');
    $r = $http($jU, 'KemitraanPortal/kkn_unduh_piagam/' . $kkn);
    $cek(strncmp($r['badan'], 'PK', 2) !== 0, 'ZIP tidak bisa diunduh sebelum tanggal piagam ditetapkan');

    echo "\n== Sesudah terbit\n";
    $db->query("UPDATE kkn_magang_pendaftaran SET tanggal_sertifikat='" . date('Y-m-d', strtotime('-1 day')) . "' WHERE id={$kkn}");
    $unggah_dpl($jU, $kkn, $xlsx([['Nama'], ["Dosen pengganti {$tag}"]]));
    $cek((int) $nilai("SELECT COUNT(*) FROM kkn_dpl WHERE pendaftaran_id={$kkn}") === 2, 'Daftar DPL terkunci sesudah tanggal piagam ditetapkan');
    $detail = $http($jU, 'KemitraanPortal/pendaftaran/' . $kkn)['badan'];
    $cek(preg_match('/<a [^>]*kkn_unduh_piagam\/' . $kkn . '"[^>]*\bdownload\b/', $detail) === 1, 'Tombol unduh semua piagam muncul dengan atribut download');

    $r = $http($jU, 'KemitraanPortal/kkn_unduh_piagam/' . $kkn);
    $zipf = tempnam(sys_get_temp_dir(), 'ukpz'); $tmp[] = $zipf; file_put_contents($zipf, $r['badan']);
    $zip = new ZipArchive(); $nama = [];
    if ($zip->open($zipf) === TRUE) { for ($i = 0; $i < $zip->numFiles; $i++) { $nama[] = $zip->getNameIndex($i); } }
    $mhs = array_filter($nama, fn($n) => strpos($n, 'mahasiswa/') === 0);
    $dos = array_filter($nama, fn($n) => strpos($n, 'dpl/') === 0);
    $cek(count($mhs) === 2 && count($dos) === 2, 'ZIP berisi 2 piagam mahasiswa dan 2 piagam DPL');
    $isi_dpl = $dos ? $zip->getFromName(reset($dos)) : '';
    $cek(strncmp((string) $isi_dpl, '%PDF', 4) === 0 && strpos($teks_pdf($isi_dpl), 'Dosen Pembimbing Lapangan') !== FALSE, 'Piagam DPL di ZIP memuat kalimat Dosen Pembimbing Lapangan');
    $zip->close();
    $cek((int) $nilai("SELECT COUNT(*) FROM sys_jejak_audit WHERE id > {$audit_awal} AND aksi='piagam_kkn_diunduh' AND objek_id='{$kkn}' AND pelaku_id={$univ}") === 1, 'Unduhan ZIP tercatat di jejak audit');

    $r = $http($jU, 'KemitraanPortal/kkn_piagam_dpl/' . $kkn . '/' . $dpl[0]['id']);
    $cek(strncmp($r['badan'], '%PDF', 4) === 0 && strpos($teks_pdf($r['badan']), "Dosen satu {$tag}") !== FALSE, 'Piagam DPL per dosen tercetak sebagai PDF');

    echo "\n== Anti-IDOR\n";
    $jL = $login($eLain);
    $r = $http($jL, 'KemitraanPortal/kkn_unduh_piagam/' . $kkn);
    $cek($r['kode'] === 404 && strncmp($r['badan'], 'PK', 2) !== 0, 'Universitas lain mendapat 404 untuk ZIP piagam');
    $r = $http($jL, 'KemitraanPortal/kkn_piagam_dpl/' . $kkn . '/' . $dpl[0]['id']);
    $cek($r['kode'] === 404 && strncmp($r['badan'], '%PDF', 4) !== 0, 'Universitas lain mendapat 404 untuk piagam DPL');
    $unggah_dpl($jL, $kkn, $xlsx([['Nama'], ['Penyusup']]));
    $cek((int) $nilai("SELECT COUNT(*) FROM kkn_dpl WHERE pendaftaran_id={$kkn} AND nama='Penyusup'") === 0, 'Universitas lain tidak bisa mengganti daftar DPL');
    $cek((int) $nilai("SELECT COUNT(*) FROM sys_jejak_audit WHERE id > {$audit_awal} AND aksi='piagam_kkn_diunduh' AND objek_id='{$kkn}'") === 1, 'Percobaan yang ditolak tidak menambah catatan unduhan');
} catch (Throwable $e) {
    $cek(FALSE, 'Suite berhenti: ' . $e->getMessage());
} finally {
    if ($akun) {
        $daftar = implode(',', $akun);
        $kkn_uji = array_column($db->query("SELECT id FROM kkn_magang_pendaftaran WHERE user_id IN ($daftar)")->fetch_all(), 0);
        if ($kkn_uji) {
            $id_kkn = implode(',', array_map('intval', $kkn_uji));
            $db->query("DELETE FROM kkn_dpl WHERE pendaftaran_id IN ($id_kkn)");
            $db->query("DELETE FROM kkn_peserta WHERE pendaftaran_id IN ($id_kkn)");
            $db->query("DELETE FROM sys_jejak_audit WHERE id > {$audit_awal} AND objek_tipe='kkn_magang_pendaftaran' AND objek_id IN ('" . implode("','", $kkn_uji) . "')");
            $db->query("DELETE FROM kkn_magang_pendaftaran WHERE id IN ($id_kkn)");
        }
        $db->query("DELETE FROM sys_jejak_audit WHERE id > {$audit_awal} AND pelaku_id IN ($daftar)");
        $db->query("DELETE FROM usr_akun WHERE id IN ($daftar)");
    }
    foreach (array_merge($jars, $tmp) as $f) { @unlink($f); }
    echo 'Akun uji tersisa: ' . (int) $nilai("SELECT COUNT(*) FROM usr_akun WHERE email LIKE '{$tag}%'") . "\n";
}
echo "RINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal ? 1 : 0);
