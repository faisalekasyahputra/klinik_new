<?php
require_once dirname(__DIR__, 2) . '/application/helpers/env_berkas_helper.php'; // lokasi .env (luar akar dulu)
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Uji Ringkasan Kerja super admin (Admin_Dashboard).
 *
 * Login sebagai super admin lewat HTTP (akun uji sementara, dihapus di akhir),
 * ambil Admin_Dashboard, lalu cocokkan angka kartu dengan COUNT DB memakai
 * aturan yang sama dengan filter layar tujuan. Juga menjaga: satu kartu satu
 * tautan "Lihat ...", tidak ada "Buka antrean", tidak ada tautan bersarang.
 *
 * Jalankan: php docs/engineering/uji_ui_ringkasan_kerja.php
 */

define('BASE_URL', rtrim(getenv('UJI_BASE_URL') ?: 'http://localhost/klinik_new', '/'));
define('APP_ROOT', dirname(__DIR__, 2));
define('SANDI', 'UjiRingkas!' . bin2hex(random_bytes(4)));

$total = 0; $gagal = 0; $akun = []; $jar = tempnam(sys_get_temp_dir(), 'ujrk_');
function cek($kondisi, $label) {
    global $total, $gagal;
    $total++;
    echo ($kondisi ? '  OK    ' : '  GAGAL ') . $label . "\n";
    if ( ! $kondisi) { $gagal++; }
}
function http($path, ?array $post = NULL, $ajax = FALSE) {
    global $jar;
    $ch = curl_init(BASE_URL . '/' . ltrim($path, '/'));
    $o = [CURLOPT_RETURNTRANSFER => TRUE, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar,
          CURLOPT_FOLLOWLOCATION => TRUE, CURLOPT_TIMEOUT => 60];
    if ($ajax) { $o[CURLOPT_HTTPHEADER] = ['X-Requested-With: XMLHttpRequest']; }
    if ($post !== NULL) { $o[CURLOPT_POST] = TRUE; $o[CURLOPT_POSTFIELDS] = http_build_query($post); }
    curl_setopt_array($ch, $o);
    $b = (string) curl_exec($ch);
    curl_close($ch);
    return $b;
}
function n($sql) { return (int) $GLOBALS['db']->query($sql)->fetch_row()[0]; }

$env = [];
foreach (@file(env_berkas_path(APP_ROOT), FILE_IGNORE_NEW_LINES) ?: [] as $b) {
    $b = trim($b);
    if ($b === '' || $b[0] === '#' || strpos($b, '=') === FALSE) { continue; }
    [$k, $v] = explode('=', $b, 2);
    $env[trim($k)] = $env[trim($k)] ?? trim($v);
}
$GLOBALS['db'] = new mysqli($env['DB_HOST'] ?? 'localhost', $env['DB_USER'] ?? 'root', $env['DB_PASS'] ?? '', $env['DB_NAME'] ?? 'klinikpkp');
$GLOBALS['db']->set_charset('utf8mb4');

register_shutdown_function(function () use (&$akun, $jar) {
    foreach ($akun as $id) { $GLOBALS['db']->query('DELETE FROM usr_akun WHERE id=' . (int) $id); }
    @unlink($jar);
});

echo "== Ringkasan Kerja super admin ==\n";
$email = 'uji_ringkas_' . time() . '_' . mt_rand(1000, 9999) . '@example.test';
$st = $GLOBALS['db']->prepare('INSERT INTO usr_akun (email,kata_sandi,nama,nama_pengguna,peran,status,profil_lengkap,created_at)
    VALUES (?,?,"Uji Ringkasan Kerja",?,"admin","active",1,NOW())');
$hash = password_hash(SANDI, PASSWORD_BCRYPT);
$uname = 'uji_ringkas_' . mt_rand(10000, 99999);
$st->bind_param('sss', $email, $hash, $uname);
$st->execute();
$akun[] = $st->insert_id;

$login = http('Auth/login');
$t = preg_match('/name="csrf_kpkp_token" value="([^"]+)"/', $login, $m) ? $m[1] : '';
$r = json_decode(http('Auth/do_login', ['csrf_kpkp_token' => $t, 'email' => $email, 'password' => SANDI], TRUE), TRUE);
cek(($r['status'] ?? '') === 'success', 'Login super admin uji berhasil');

/** Angka pertama dalam tautan pertama ke $tujuan di dalam $html (teks tanpa tag). */
function angka_tautan($html, $tujuan) {
    if ( ! preg_match('#<a href="[^"]*/' . preg_quote($tujuan, '#') . '"[^>]*>(.*?)</a>#s', $html, $m)) { return NULL; }
    return preg_match('/(\d[\d.]*)/', strip_tags($m[1]), $a) ? (int) str_replace('.', '', $a[1]) : NULL;
}

// Angka DB, aturan sama dengan filter layar tujuan.
$harap = function () {
    $o = [];
    foreach (['pending', 'needs_revision', 'approved', 'rejected'] as $s) {
        $o["Admin?status=$s"] = n("SELECT COUNT(*) FROM sf_antrean_pengajuan WHERE status_antrean='$s'");
    }
    foreach (['Pending', 'Draft'] as $s) {
        $o["Admin_Srp2/pending?status=$s"] = n("SELECT COUNT(*) FROM srp2_pengajuan WHERE status_verifikasi='$s'");
    }
    // Admin_Srp2::keadaan_berlaku: bersertifikat + tanggal akhir terisi + belum lewat.
    $o['Admin_Srp2'] = n("SELECT COUNT(*) FROM srp2_direktori_pengembang WHERE status_sertifikasi='bersertifikat'
        AND sertifikat_berakhir IS NOT NULL AND sertifikat_berakhir <> '' AND sertifikat_berakhir >= CURDATE()");
    $o['Admin_Aduan?bidang=belum'] = n('SELECT COUNT(*) FROM aduan WHERE bidang_kode IS NULL');
    foreach (['Baru', 'Diproses', 'Selesai'] as $s) {
        $o["Admin_Aduan?status=$s"] = n("SELECT COUNT(*) FROM aduan WHERE status='$s'");
    }
    foreach (['Diajukan' => 'Diajukan', 'Ditinjau+Bidang' => 'Ditinjau Bidang', 'Diterima' => 'Diterima'] as $q => $s) {
        $o["Admin_Kemitraan?status=$q"] = n("SELECT COUNT(*) FROM kkn_magang_pendaftaran WHERE status='$s'");
    }
    foreach (['diajukan', 'ditawarkan', 'disetujui'] as $s) {
        $o["Admin_Konsultasi?status=$s"] = n("SELECT COUNT(*) FROM forum_janji_temu WHERE status='$s'");
    }
    return $o;
};

// Agen lain bisa menulis DB di sela pengambilan halaman; satu kali ulang sebelum dinyatakan gagal.
for ($coba = 0; $coba < 2; $coba++) {
    $html = http('Admin_Dashboard');
    $harapan = $harap();
    $a = strpos($html, 'aria-label="Pekerjaan per layanan"');
    $b = strpos($html, 'Pengajuan terbaru');
    $kartu = ($a !== FALSE && $b !== FALSE) ? substr($html, $a, $b - $a) : '';
    $beda = [];
    foreach ($harapan as $tujuan => $nilai) {
        if (angka_tautan($kartu, $tujuan) !== $nilai) { $beda[] = "$tujuan: layar " . var_export(angka_tautan($kartu, $tujuan), TRUE) . ", DB $nilai"; }
    }
    if ( ! $beda) { break; }
}
cek(strpos($html, '>Ringkasan Kerja</h1>') !== FALSE, 'Halaman Ringkasan Kerja terbuka');
cek($kartu !== '', 'Bagian kartu ditemukan sebelum Pengajuan terbaru');
cek($beda === [], 'Angka antrean, SRP2, aduan, KKN dan janji temu = COUNT DB dengan aturan filter yang sama' . ($beda ? ' (' . implode('; ', $beda) . ')' : ''));

// Rekam Data triwulan berjalan: aturan Rekam_data_model::keadaan_laporan.
$tahun = (int) date('Y'); $tw = (int) ceil((int) date('n') / 3);
$kab = n('SELECT COUNT(*) FROM kabupaten');
$rekam_beda = [];
preg_match('#<article[^>]*>(?:(?!</article>).)*?Rekam Data(.*?)</article>#s', $kartu, $ra);
$rekam_html = $ra[0] ?? '';
foreach (['perumahan' => 'Perumahan', 'kawasan' => 'Kawasan'] as $d => $label) {
    $masuk = n("SELECT COUNT(*) FROM kabupaten k JOIN rd_laporan l ON l.kabupaten_id=k.id AND l.domain='$d'
        AND l.tahun=$tahun AND l.triwulan=$tw WHERE l.status IN ('terkirim','perlu_perbaikan')");
    if ( ! preg_match('#' . preg_quote($label, '#') . '</span><span[^>]*>' . $masuk . ' dari ' . $kab . '<#', $rekam_html)) { $rekam_beda[] = "$label $masuk dari $kab"; }
}
$diterima = n("SELECT COUNT(*) FROM kabupaten k JOIN rd_laporan l ON l.kabupaten_id=k.id AND l.tahun=$tahun AND l.triwulan=$tw
    WHERE l.status='terkirim' AND l.reviewed_at IS NOT NULL AND l.reviewed_at <> ''");
$perbaikan = n("SELECT COUNT(*) FROM kabupaten k JOIN rd_laporan l ON l.kabupaten_id=k.id AND l.tahun=$tahun AND l.triwulan=$tw
    WHERE l.status='perlu_perbaikan'");
if ( ! preg_match('#Diterima</span><span[^>]*>' . $diterima . '<#', $rekam_html)) { $rekam_beda[] = "diterima $diterima"; }
if ( ! preg_match('#Perlu perbaikan</span><span[^>]*>' . $perbaikan . '<#', $rekam_html)) { $rekam_beda[] = "perbaikan $perbaikan"; }
cek($rekam_html !== '' && $rekam_beda === [], 'Kartu Rekam Data triwulan berjalan = COUNT DB' . ($rekam_beda ? ' (harap: ' . implode(', ', $rekam_beda) . ')' : ''));
cek(strpos($rekam_html, "Admin_Rekam_Data?tahun=$tahun&amp;triwulan=$tw") !== FALSE || strpos($rekam_html, "Admin_Rekam_Data?tahun=$tahun&triwulan=$tw") !== FALSE,
    'Kartu Rekam Data menaut ke papan triwulan yang sama');

// Satu kartu = satu kontrol utama berlabel sesuai isi.
$jml_kartu = preg_match_all('#<article\b#', $kartu);
// Sejak 4 Okt 2026 kontrolnya tombol di dasar kartu: label dibungkus <span>, diikuti chevron.
$lihat = preg_match_all('#<a [^>]*>(?:<span>)?Lihat (antrean|pengajuan|aduan|pendaftaran|janji temu|laporan)(?:</span>|</a>)#', $kartu, $lm);
cek($jml_kartu === 6 && $lihat === $jml_kartu, "Enam kartu, masing-masing satu tautan Lihat ... ($jml_kartu kartu, $lihat tautan)");
cek(count(array_unique($lm[1])) === $lihat, 'Label tautan Lihat berbeda per kartu: ' . implode(', ', $lm[1]));
cek(strpos($html, 'Buka antrean') === FALSE, 'Tidak ada teks "Buka antrean" di halaman');
cek(strpos($kartu, 'ph-arrow-up-right') === FALSE && strpos($kartu, 'ph-arrow-right') === FALSE, 'Kartu tanpa ikon panah');

// Tautan bersarang di seluruh halaman.
$dalam = 0; $bersarang = 0;
preg_match_all('#<a\b|</a>#i', $html, $tk);
foreach ($tk[0] as $tok) {
    if ($tok[1] === '/') { $dalam = max(0, $dalam - 1); continue; }
    if (++$dalam > 1) { $bersarang++; }
}
cek($bersarang === 0, "Tidak ada tautan bersarang ($bersarang)");

// Ringkasan Sistem: akun per peran dan peringatan keamanan.
$staf = n("SELECT COUNT(*) FROM usr_akun WHERE peran IN ('admin','admin_kabkota','admin_bidang')");
$warga = n("SELECT COUNT(*) FROM usr_akun WHERE peran='warga'");
cek(preg_match('#>Staf</dt><dd[^>]*>' . $staf . '<#', $html) === 1 && preg_match('#>Warga</dt><dd[^>]*>' . $warga . '<#', $html) === 1,
    "Ringkasan Sistem: akun staf ($staf) dan warga ($warga) = COUNT DB");
cek(preg_match('#href="[^"]*Admin_Audit\?aksi=peringatan_keamanan"[^>]*data-peringatan-keamanan>[\d.]+<#', $html) === 1,
    'Peringatan keamanan 24 jam menaut ke Jejak Audit');
foreach (['Topik konsultasi', 'Data PSU', 'Dokumen Bank Data', 'Universitas', 'Mahasiswa', 'Pengembang'] as $l) {
    cek(strpos($html, ">$l</dt>") !== FALSE, "Ringkasan Sistem memuat $l");
}

// Sumber view: tanpa gradien dekoratif dan tanpa label huruf besar.
$src = (string) file_get_contents(APP_ROOT . '/application/views/admin/dashboard.php');
cek(preg_match('/gradient|bg-gradient-|\bfrom-[a-z]+-\d/', $src) === 0, 'View dasbor tanpa gradien');
cek(strpos($src, 'uppercase') === FALSE, 'View dasbor tanpa teks huruf besar paksa');

echo "\nRINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal > 0 ? 1 : 0);
