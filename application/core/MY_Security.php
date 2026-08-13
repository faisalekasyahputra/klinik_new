<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Halaman CSRF gagal yang MANUSIAWI, bukan dump mentah CodeIgniter.
 *
 * Ditemukan 14 Agt 2026: admin isi form edit di Direktori SRP2 (mis. baris
 * ARTACIPTA), klik "Simpan", mendarat di "An Error Was Encountered - The
 * action you have requested is not allowed." - putih polos, tanpa
 * sidebar/shell, satu-satunya jalan keluar tombol Back browser. Token CSRF
 * di formulir itu sudah tidak cocok lagi dengan cookie saat ini (tab lama
 * yang dibuka sebelum re-login, atau cookie kedaluwarsa - csrf_expire 7200
 * detik, application/config/config.php) - bukan sesuatu yang salah
 * dilakukan admin, tapi pesannya tidak bilang itu SAMA SEKALI.
 *
 * KENAPA DI SINI, bukan flashdata + redirect via session seperti pola
 * error lain di seluruh aplikasi ini: `CI_Security::csrf_verify()`
 * dipanggil dari `CI_Input::__construct()` (system/core/CodeIgniter.php,
 * Security dimuat SEBELUM Input, Input SEBELUM Controller) - jauh
 * SEBELUM MY_Controller ada, sebelum Session library dimuat, sebelum
 * helper `url` ter-autoload. `get_instance()->load->library('session')`
 * TIDAK AMAN dipanggil di sini. Makanya halaman ini header/style-nya
 * MANDIRI (PHP+HTML mentah, nol dependensi CI lain) - bukan malas,
 * batasan bootstrap yang sungguhan.
 *
 * Tombol "Coba Lagi" mengarah ke Referer KALAU dan HANYA KALAU host-nya
 * sama dengan situs ini sendiri (anti open-redirect - Referer datang dari
 * klien, tidak boleh dipercaya mentah); kalau tidak ada/tidak cocok,
 * jatuh ke root situs.
 */
class MY_Security extends CI_Security {

    public function csrf_show_error() {
        $tujuan = '/';
        $referer = isset($_SERVER['HTTP_REFERER']) ? (string) $_SERVER['HTTP_REFERER'] : '';
        if ($referer !== '') {
            $host_referer = parse_url($referer, PHP_URL_HOST);
            $host_sendiri = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : NULL;
            if ($host_referer !== NULL && $host_sendiri !== NULL && strcasecmp($host_referer, $host_sendiri) === 0) {
                $tujuan = $referer;
            }
        }

        http_response_code(403);
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        ?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Formulir Kedaluwarsa - Klinik PKP</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
  body { margin:0; min-height:100vh; display:flex; align-items:center; justify-content:center;
         background:#0a1a1f; color:#ecffb6; font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif; padding:24px; box-sizing:border-box; }
  .kotak { max-width:420px; text-align:center; }
  .kotak i { font-size:40px; color:#d6fb00; }
  h1 { font-size:20px; margin:16px 0 8px; color:#fff; }
  p { font-size:14px; line-height:1.6; color:#8aacb0; margin:0 0 24px; }
  a.tombol { display:inline-block; background:#d6fb00; color:#0a1a1f; font-weight:700; font-size:14px;
             padding:12px 28px; border-radius:12px; text-decoration:none; }
  a.tombol:hover { opacity:.85; }
</style>
</head>
<body>
  <div class="kotak">
    <div style="font-size:40px;">&#9888;</div>
    <h1>Formulir Sudah Kedaluwarsa</h1>
    <p>Halaman ini sudah lama terbuka sehingga formulirnya tidak lagi berlaku. Isian yang tadi diketik memang tidak ikut tersimpan - silakan buka lagi halamannya dan coba sekali lagi.</p>
    <a class="tombol" href="<?= htmlspecialchars($tujuan, ENT_QUOTES, 'UTF-8') ?>">Coba Lagi</a>
  </div>
</body>
</html>
        <?php
        exit(4); // Kode keluar CodeIgniter untuk error yang sudah ditangani.
    }
}
