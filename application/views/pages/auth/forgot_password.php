<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <?= csp_meta_tag() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - Klinik PKP</title>
    <link rel="icon" href="<?= base_url('assets/img/logo-jateng.png') ?>" type="image/png">

    <link rel="stylesheet" href="<?= base_url('assets/css/auth-pages.css?v=' . filemtime('assets/css/auth-pages.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/notifications.css?v=' . filemtime('assets/css/notifications.css')) ?>">
    <script defer src="<?= base_url('assets/js/notifications.js?v=' . filemtime('assets/js/notifications.js')) ?>"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha384-t1nt8BQoYMLFN5p42tRAtuAAFQaCQODekUVeKKZrEnEyp4H2R0RHFz0KWpmj7i8g" crossorigin="anonymous">
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
                Pulihkan<br><span>Akses</span> Akun
            </h1>
            <p class="auth-left__desc">
                Masukkan alamat email yang terdaftar dan kami akan mengirimkan
                tautan untuk mengatur ulang password Anda.
            </p>
        </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="auth-right">
        <div class="auth-form-container">

            <!-- Back Link -->
            <a href="<?= base_url('Auth/login') ?>" class="auth-back-link">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Login
            </a>

            <!-- Mobile Logo (hidden on desktop) -->
            <div class="auth-mobile-logo" style="display:none;">
                <a href="<?= base_url() ?>" style="display:flex; align-items:center; text-decoration:none; gap:10px;">
                    <div class="auth-left__logo-icon" style="width:40px;height:40px;font-size:1rem;">
                        <i class="fa-solid fa-house-chimney"></i>
                    </div>
                    <span style="font-weight:700;font-size:1.125rem;color:var(--auth-gray-900);">Klinik PKP</span>
                </a>
            </div>

            <h2 class="auth-heading">Lupa Password? 🔑</h2>
            <p class="auth-subheading">
                Masukkan email akun Anda. Kami kirim tautan untuk membuat kata sandi baru, berlaku 30 menit.
            </p>

            <!-- Reset mandiri lewat email (Auth::kirim_tautan_sandi, 6 Okt 2026). -->
            <form action="<?= base_url('Auth/kirim_tautan_sandi') ?>" method="POST" id="lupaSandiForm" data-lupa-sandi>
                <?= bot_guard_fields('lupa_sandi') ?>
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                <label class="auth-label" for="lupa_email">Email</label>
                <div class="auth-input-group">
                    <input type="email" id="lupa_email" name="email" class="auth-input" maxlength="255"
                           placeholder="nama@contoh.com" required autocomplete="email" autofocus>
                    <i class="fa-solid fa-envelope auth-input-icon"></i>
                </div>
                <button type="submit" class="auth-btn" id="btnLupaSandi">
                    <span>Kirim tautan</span>
                    <i class="fa-solid fa-paper-plane"></i>
                    <div class="spinner"></div>
                </button>
            </form>
            <p class="auth-subheading" style="margin-top:1rem;font-size:.8125rem;">
                Tidak bisa membuka email itu lagi? Hubungi Admin Disperakim Jateng untuk reset kata sandi manual.
            </p>

            <!-- Back link -->
            <div class="auth-footer-links" style="justify-content:center;">
                <a href="<?= base_url('Auth/login') ?>" class="auth-link">
                    <i class="fa-solid fa-arrow-left" style="margin-right:4px;"></i> Kembali ke Login
                </a>
            </div>

            <!-- Government Badge -->
            <div class="auth-govt-badge">
                <i class="fa-solid fa-landmark"></i>
                <span>Dinas Perumahan Rakyat & Kawasan Permukiman<br>Provinsi Jawa Tengah</span>
            </div>

        </div>
    </div>

</div>

<script>
document.getElementById('lupaSandiForm').addEventListener('submit', function () {
    const btn = document.getElementById('btnLupaSandi');
    btn.classList.add('loading');
    btn.disabled = true;
});
</script>

</body>
</html>
