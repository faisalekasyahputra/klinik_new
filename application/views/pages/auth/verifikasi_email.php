<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <?= csp_meta_tag() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email - Klinik PKP</title>
    <link rel="icon" href="<?= base_url('assets/img/logo-jateng.png') ?>" type="image/png">

    <link rel="stylesheet" href="<?= base_url('assets/css/auth-pages.css?v=' . filemtime('assets/css/auth-pages.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/notifications.css?v=' . filemtime('assets/css/notifications.css')) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha384-t1nt8BQoYMLFN5p42tRAtuAAFQaCQODekUVeKKZrEnEyp4H2R0RHFz0KWpmj7i8g" crossorigin="anonymous">
    <script defer src="<?= base_url('assets/js/notifications.js?v=' . filemtime('assets/js/notifications.js')) ?>"></script>
</head>
<body class="auth-page">
<?php $this->load->view('components/notification_center'); ?>

<div class="auth-split">

    <!-- LEFT PANEL -->
    <div class="auth-left" aria-hidden="true">
        <div class="auth-left__gradient"></div>
        <div class="auth-left__orb auth-left__orb--1"></div>
        <div class="auth-left__orb auth-left__orb--2"></div>
        <div class="auth-left__orb auth-left__orb--3"></div>
        <div class="auth-left__pattern"></div>

        <div class="auth-left__content">
            <a href="<?= base_url() ?>" class="auth-left__logo" style="text-decoration:none;">
                <div class="auth-left__logo-icon">
                    <i class="fa-solid fa-house-chimney"></i>
                </div>
                <span class="auth-left__logo-text">Klinik PKP</span>
            </a>
            <h1 class="auth-left__tagline">
                Satu Langkah<br><span>Lagi</span>
            </h1>
            <p class="auth-left__desc">
                Kami perlu memastikan alamat email ini benar milik Anda sebelum akun dibuat.
            </p>
        </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="auth-right">
        <div class="auth-form-container">

            <a href="<?= base_url('Auth/register') ?>" class="auth-back-link">
                <i class="fa-solid fa-arrow-left"></i> Ganti email
            </a>

            <h2 class="auth-heading">Verifikasi Email</h2>
            <p class="auth-subheading">
                Kode 6 angka sudah dikirim ke <strong><?= html_escape($email) ?></strong>. Kode berlaku 10 menit. Periksa juga folder spam.
            </p>

            <form action="<?= base_url('Auth/do_verifikasi_email') ?>" method="POST">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                <label class="auth-label" for="kode_otp">Kode verifikasi</label>
                <div class="auth-input-group">
                    <input type="text" class="auth-input" id="kode_otp" name="kode_otp" inputmode="numeric" pattern="[0-9]{6}"
                           maxlength="6" autocomplete="one-time-code" required autofocus
                           style="letter-spacing:.5em; text-indent:.5em; text-align:center; font-size:1.25rem; padding-left:.875rem; padding-right:.875rem;"><?php /* padding simetris: kolom ini tanpa ikon; text-indent mengimbangi spasi sesudah angka terakhir */ ?>
                </div>
                <button type="submit" class="auth-btn"><span>Verifikasi dan Buat Akun</span></button>
            </form>

            <form action="<?= base_url('Auth/kirim_ulang_otp') ?>" method="POST" style="margin-top:16px; text-align:center;">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                <?php if ($batas): ?>
                <p class="auth-subheading" style="margin:0;">Batas permintaan kode tercapai. <a href="<?= base_url('Auth/register') ?>" class="auth-link">Ulangi pendaftaran</a> beberapa saat lagi.</p>
                <?php else: ?>
                <button type="submit" id="kirimUlang" class="auth-link" data-tunggu="<?= (int) $tunggu ?>"
                        style="background:none; border:0; cursor:pointer; font:inherit; font-weight:600;"
                        <?= $tunggu > 0 ? 'disabled' : '' ?>>Kirim ulang kode</button>
                <?php endif; ?>
            </form>

            <div class="auth-govt-badge">
                <i class="fa-solid fa-landmark"></i>
                <span>Dinas Perumahan Rakyat & Kawasan Permukiman<br>Provinsi Jawa Tengah</span>
            </div>

        </div>
    </div>

</div>

<script>
// Hitung mundur tombol kirim ulang. Jeda (berlipat dua tiap pengiriman) ditegakkan server; ini cerminannya.
(function () {
    var tombol = document.getElementById('kirimUlang');
    if (!tombol) return;
    var sisa = parseInt(tombol.dataset.tunggu, 10) || 0;
    function tampil() {
        if (sisa <= 0) {
            tombol.disabled = false;
            tombol.style.opacity = '';
            tombol.style.cursor = 'pointer';
            tombol.textContent = 'Kirim ulang kode';
            return;
        }
        var m = Math.floor(sisa / 60), d = sisa % 60;
        tombol.disabled = true;
        tombol.style.opacity = '.55';
        tombol.style.cursor = 'not-allowed';
        tombol.textContent = 'Kirim ulang kode dalam ' + m + ':' + (d < 10 ? '0' : '') + d;
        sisa--;
        setTimeout(tampil, 1000);
    }
    tampil();
})();
</script>
</body>
</html>
