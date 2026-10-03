<?php defined('BASEPATH') OR exit('No direct script access allowed');
/* Email kode OTP pendaftaran (libraries/Otp_pendaftaran.php). Tata letak tabel dan gaya inline karena
   klien email membuang <style> dan flex/grid; tanpa gambar supaya tidak bergantung URL publik. */ ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kode verifikasi pendaftaran Klinik PKP</title>
</head>
<body style="margin:0; padding:0; background:#eef2f3; font-family:Arial, Helvetica, sans-serif; color:#0a1a1f;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;">Kode verifikasi Anda <?= html_escape($kode) ?>, berlaku <?= (int) $menit ?> menit.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef2f3;">
    <tr>
        <td align="center" style="padding:32px 16px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:520px; background:#ffffff; border-radius:16px; overflow:hidden;">
                <tr>
                    <td style="background:#0a1a1f; padding:22px 32px;">
                        <table role="presentation" cellpadding="0" cellspacing="0">
                            <tr>
                                <?php if ( ! empty($gambar['logo'])): ?>
                                <td style="padding-right:14px; vertical-align:middle;">
                                    <img src="<?= html_escape($gambar['logo']) ?>" alt="Logo Provinsi Jawa Tengah" width="43" height="48" style="display:block; border:0;">
                                </td>
                                <?php endif; ?>
                                <td style="vertical-align:middle;">
                                    <span style="font-size:20px; font-weight:bold; color:#ffffff;">Klinik <span style="color:#d6fb00;">PKP</span></span><br>
                                    <span style="font-size:12px; line-height:1.5; color:#9fb3b8;">Dinas Perumahan Rakyat dan Kawasan Permukiman<br>Provinsi Jawa Tengah</span>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px 32px 8px;">
                        <?php /* Ikon: PNG dari assets/img/email/*.svg yang ditanam (CID); klien email membuang SVG. */ ?>
                        <h1 style="margin:0 0 12px; font-size:20px; line-height:1.3; color:#0a1a1f;"><?php if ( ! empty($gambar['gembok'])): ?><img src="<?= html_escape($gambar['gembok']) ?>" alt="" width="22" height="22" style="vertical-align:middle; border:0; margin-right:8px;"><?php endif; ?>Verifikasi email Anda</h1>
                        <p style="margin:0; font-size:15px; line-height:1.6; color:#3c4f54;">
                            Masukkan kode berikut di halaman pendaftaran Klinik PKP untuk menyelesaikan pembuatan akun.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td align="center" style="padding:20px 32px;">
                        <table role="presentation" cellpadding="0" cellspacing="0">
                            <tr>
                                <td align="center" style="background:#f4f8e6; border:2px solid #d6fb00; border-radius:12px; padding:18px 28px;">
                                    <span style="font-family:'Courier New', Courier, monospace; font-size:34px; font-weight:bold; letter-spacing:10px; color:#0a1a1f;"><?= html_escape($kode) ?></span>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:14px 0 0; font-size:13px; color:#5b6f74;"><?php if ( ! empty($gambar['jam'])): ?><img src="<?= html_escape($gambar['jam']) ?>" alt="" width="16" height="16" style="vertical-align:middle; border:0; margin-right:8px;"><?php endif; ?>Berlaku <strong><?= (int) $menit ?> menit</strong> dan hanya untuk satu kali pendaftaran.</p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:8px 32px 28px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="background:#fff7e6; border-left:4px solid #f5a623; border-radius:6px; padding:12px 16px; font-size:13px; line-height:1.6; color:#5c4a1f;">
                                    <?php if ( ! empty($gambar['peringatan'])): ?><img src="<?= html_escape($gambar['peringatan']) ?>" alt="Peringatan" width="18" height="18" style="vertical-align:middle; border:0; margin-right:8px;"><?php endif; ?><strong>Jangan berikan kode ini kepada siapa pun</strong>, termasuk orang yang mengaku petugas. Petugas kami tidak pernah meminta kode verifikasi.
                                </td>
                            </tr>
                        </table>
                        <p style="margin:18px 0 0; font-size:13px; line-height:1.6; color:#5b6f74;">
                            Kalau Anda tidak merasa mendaftar di Klinik PKP, abaikan email ini. Tidak ada akun yang dibuat tanpa kode di atas.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td align="center" style="background:#f6f8f9; padding:18px 32px; font-size:12px; line-height:1.6; color:#7b8d92; text-align:center;">
                        Email ini dikirim otomatis, mohon tidak dibalas.<br>
                        Klinik PKP, layanan konsultasi perumahan dan kawasan permukiman Jawa Tengah.
                        <div style="margin-top:12px; padding-top:12px; border-top:1px solid #e1e7e9; color:#9aa9ad;">
                            &copy; <?= date('Y') ?> Dinas Perumahan Rakyat &amp; Kawasan Permukiman Provinsi Jawa Tengah. Hak cipta dilindungi.
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
