<?php defined('BASEPATH') OR exit('No direct script access allowed');
/* Email pemberitahuan umum (libraries/Surel_pemberitahuan.php). Kerangka sama dengan otp_pendaftaran.php:
   tabel dan gaya inline karena klien email membuang <style> dan flex/grid; logo ditanam (CID). */ ?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= html_escape($judul) ?></title>
</head>
<body style="margin:0; padding:0; background:#eef2f3; font-family:Arial, Helvetica, sans-serif; color:#0a1a1f;">
<div style="display:none; max-height:0; overflow:hidden; opacity:0;"><?= html_escape($paragraf[0] ?? $judul) ?></div>
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
                        <h1 style="margin:0 0 12px; font-size:20px; line-height:1.3; color:#0a1a1f;"><?= html_escape($judul) ?></h1>
                        <?php foreach ($paragraf as $p): ?>
                        <p style="margin:0 0 12px; font-size:15px; line-height:1.6; color:#3c4f54;"><?= html_escape($p) ?></p>
                        <?php endforeach; ?>
                    </td>
                </tr>
                <?php if ($catatan !== ''): ?>
                <tr>
                    <td style="padding:4px 32px 8px;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                            <tr>
                                <td style="background:#f2f8f9; border-left:4px solid #00a3b5; border-radius:6px; padding:12px 16px; font-size:14px; line-height:1.6; color:#2a4248;">
                                    <b>Catatan petugas:</b> <?= html_escape($catatan) ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <?php endif; ?>
                <?php if ($tautan !== ''): ?>
                <tr>
                    <td align="center" style="padding:20px 32px 28px;">
                        <table role="presentation" cellpadding="0" cellspacing="0">
                            <tr>
                                <td align="center" style="background:#00a3b5; border-radius:10px;">
                                    <a href="<?= html_escape($tautan) ?>" style="display:inline-block; padding:12px 24px; font-size:15px; font-weight:bold; color:#ecffb6; text-decoration:none;"><?= html_escape($tombol_label !== '' ? $tombol_label : 'Buka Klinik PKP') ?></a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <?php endif; ?>
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
