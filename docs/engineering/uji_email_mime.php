<?php
date_default_timezone_set('Asia/Jakarta'); // samakan dengan aplikasi (index.php)
/**
 * Susunan MIME email HTML bergambar tertanam (application/libraries/MY_Email.php).
 *
 *   php docs/engineering/uji_email_mime.php
 *
 * Menyusun email OTP sungguhan lewat CI_Email + MY_Email (logo, ikon, templat asli) TANPA mengirim,
 * lalu memeriksa bahwa gambar ada di dalam multipart/related bersama HTML, bukan sebagai lampiran.
 * Susunan bawaan CI (related membungkus alternative) membuat Gmail menampilkan logo sebagai lampiran.
 */
define('BASEPATH', dirname(__DIR__, 2) . '/system/');
define('APPPATH', dirname(__DIR__, 2) . '/application/');
define('FCPATH', dirname(__DIR__, 2) . '/');
define('ENVIRONMENT', 'development');
define('ICONV_ENABLED', extension_loaded('iconv'));  // biasanya dipasang core/Utf8.php
define('MB_ENABLED', extension_loaded('mbstring'));
function log_message() {}
require_once BASEPATH . 'core/Common.php';
require_once BASEPATH . 'libraries/Email.php';
require_once APPPATH . 'libraries/MY_Email.php';
function html_escape($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

$total = 0; $gagal = 0;
$cek = function ($ok, $l) use (&$total, &$gagal) { $total++; if (!$ok) $gagal++; echo ($ok ? '  OK    ' : '  GAGAL ') . $l . "\n"; };

echo "=== UJI SUSUNAN MIME EMAIL OTP ===\n";
$e = new MY_Email(['protocol' => 'smtp', 'mailtype' => 'html', 'charset' => 'utf-8', 'newline' => "\r\n", 'crlf' => "\r\n"]);
$e->from('pengirim@example.test', 'Klinik PKP Jawa Tengah');
$e->to('warga@example.test');
$e->subject('Kode verifikasi pendaftaran Klinik PKP');
// Daftar gambar sama dengan libraries/Otp_pendaftaran.php::antar().
$gambar = [];
foreach (['logo' => 'email/logo-jateng.png', 'gembok' => 'email/gembok.png', 'jam' => 'email/jam.png', 'peringatan' => 'email/peringatan.png'] as $nama => $berkas) {
    $jalur = FCPATH . 'assets/img/' . $berkas;
    if (is_file($jalur) && $e->attach($jalur, 'inline')) { $gambar[$nama] = 'cid:' . $e->attachment_cid($jalur); }
}
$cek(count($gambar) === 4, 'Logo dan tiga ikon ditemukan dan ditanam (' . count($gambar) . ' gambar)');
$kode = '123456'; $menit = 10;
ob_start(); include APPPATH . 'views/email/otp_pendaftaran.php'; $html = ob_get_clean();
$e->message($html);
$e->set_alt_message("Kode verifikasi: 123456");

$bangun = new ReflectionMethod($e, '_build_message'); $bangun->setAccessible(TRUE); $bangun->invoke($e);
$final = (new ReflectionProperty($e, '_finalbody'));  $final->setAccessible(TRUE); $mime = $final->getValue($e);

preg_match('/^Content-Type: (multipart\/[a-z]+); boundary="([^"]+)"/', $mime, $akar);
$cek(($akar[1] ?? '') === 'multipart/alternative', 'Akar email multipart/alternative (dapat: ' . ($akar[1] ?? '-') . ')');
$cek(strpos($mime, 'multipart/mixed') === FALSE, 'Tidak ada multipart/mixed: tidak ada lampiran biasa');
$p_teks = strpos($mime, 'Content-Type: text/plain');
$p_rel  = strpos($mime, 'Content-Type: multipart/related');
$p_html = strpos($mime, 'Content-Type: text/html');
$p_img  = strpos($mime, 'Content-Type: image/png');
$cek($p_teks !== FALSE && $p_rel !== FALSE && $p_teks < $p_rel, 'Bagian teks polos berada sebelum bagian related');
$cek($p_rel !== FALSE && $p_html !== FALSE && $p_img !== FALSE && $p_rel < $p_html && $p_html < $p_img, 'Di dalam related: HTML dulu (akar), lalu gambar');
preg_match('/multipart\/related; boundary="([^"]+)"/', $mime, $rel);
$bagian_rel = isset($rel[1]) ? explode('--' . $rel[1], $mime) : [];
$jml_img = count(array_filter($bagian_rel, fn($b) => strpos($b, 'Content-Type: image/png') !== FALSE && strpos($b, 'Content-Disposition: inline') !== FALSE));
$cek($jml_img === 4, 'Keempat gambar ada di dalam related dengan Content-Disposition inline (' . $jml_img . ')');
$semua_cid = TRUE;
foreach ($gambar as $cid) {
    $id = substr($cid, 4);
    $semua_cid = $semua_cid && strpos($mime, 'Content-ID: <' . $id . '>') !== FALSE;
}
$qp = quoted_printable_decode(substr($mime, $p_html, $p_img - $p_html));
foreach ($gambar as $cid) { $semua_cid = $semua_cid && strpos($qp, 'src="' . $cid . '"') !== FALSE; }
$cek($semua_cid, 'Setiap gambar punya Content-ID dan dirujuk dengan cid: yang sama di HTML');
$cek(substr(rtrim($mime), -strlen('--' . ($akar[2] ?? 'x') . '--')) === '--' . ($akar[2] ?? 'x') . '--', 'Batas alternative ditutup di akhir email');
$cek(filesize(FCPATH . 'assets/img/email/logo-jateng.png') < 30000, 'Logo email versi kecil (' . filesize(FCPATH . 'assets/img/email/logo-jateng.png') . ' byte)');

echo "\nRINGKASAN: {$total} pemeriksaan, {$gagal} gagal\n";
exit($gagal ? 1 : 0);
