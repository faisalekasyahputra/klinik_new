<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <?= csp_meta_tag() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php /* Token ada di alamat halaman ini: jangan ikut terkirim ke CDN lewat Referer. */ ?>
    <meta name="referrer" content="no-referrer">
    <meta name="robots" content="noindex, nofollow">
    <title>Buat Kata Sandi Baru - Klinik PKP</title>
    <link rel="icon" href="<?= base_url('assets/img/logo-jateng.png') ?>" type="image/png">
    <link rel="stylesheet" href="<?= base_url('assets/css/auth-pages.css?v=' . filemtime('assets/css/auth-pages.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/notifications.css?v=' . filemtime('assets/css/notifications.css')) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha384-t1nt8BQoYMLFN5p42tRAtuAAFQaCQODekUVeKKZrEnEyp4H2R0RHFz0KWpmj7i8g" crossorigin="anonymous">
    <script defer src="<?= base_url('assets/js/notifications.js?v=' . filemtime('assets/js/notifications.js')) ?>"></script>
</head>
<body class="auth-page">
<?php $this->load->view('components/notification_center'); ?>

<div class="auth-split">

    <div class="auth-left" aria-hidden="true">
        <div class="auth-left__gradient"></div>
        <div class="auth-left__orb auth-left__orb--1"></div>
        <div class="auth-left__orb auth-left__orb--2"></div>
        <div class="auth-left__orb auth-left__orb--3"></div>
        <div class="auth-left__pattern"></div>
        <div class="auth-left__content">
            <a href="<?= base_url() ?>" class="auth-left__logo" style="text-decoration:none;">
                <div class="auth-left__logo-icon"><i class="fa-solid fa-house-chimney"></i></div>
                <span class="auth-left__logo-text">Klinik PKP</span>
            </a>
            <h1 class="auth-left__tagline">Buat<br><span>Sandi</span> Baru</h1>
            <p class="auth-left__desc">Pilih kata sandi yang hanya Anda ketahui. Sesudah disimpan, semua perangkat yang masih masuk akan dikeluarkan.</p>
        </div>
    </div>

    <div class="auth-right">
        <div class="auth-form-container">

            <a href="<?= base_url('Auth/login') ?>" class="auth-back-link">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Login
            </a>

            <h2 class="auth-heading">Buat Kata Sandi Baru 🔒</h2>
            <p class="auth-subheading">Minimal 8 karakter, dengan huruf besar, angka, dan simbol.</p>

            <form action="<?= base_url('Auth/simpan_sandi') ?>" method="POST" id="aturSandiForm" data-atur-sandi>
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                <input type="hidden" name="token" value="<?= html_escape($token) ?>">

                <label class="auth-label" for="sandi_baru">Kata sandi baru</label>
                <div class="auth-input-group">
                    <input type="password" id="sandi_baru" name="password" class="auth-input" minlength="8" maxlength="255"
                           placeholder="Kata sandi baru" required autocomplete="new-password" autofocus>
                    <i class="fa-solid fa-lock auth-input-icon"></i>
                    <button type="button" class="auth-password-toggle" data-lihat="sandi_baru" aria-label="Tampilkan kata sandi"><i class="fa-solid fa-eye"></i></button>
                </div>

                <label class="auth-label" for="sandi_ulang">Ulangi kata sandi baru</label>
                <div class="auth-input-group">
                    <input type="password" id="sandi_ulang" name="password_confirm" class="auth-input" minlength="8" maxlength="255"
                           placeholder="Ketik ulang kata sandi baru" required autocomplete="new-password">
                    <i class="fa-solid fa-lock auth-input-icon"></i>
                    <button type="button" class="auth-password-toggle" data-lihat="sandi_ulang" aria-label="Tampilkan kata sandi"><i class="fa-solid fa-eye"></i></button>
                </div>

                <button type="submit" class="auth-btn" id="btnAturSandi">
                    <span>Simpan kata sandi</span>
                    <i class="fa-solid fa-check"></i>
                    <div class="spinner"></div>
                </button>
            </form>

            <div class="auth-govt-badge">
                <i class="fa-solid fa-landmark"></i>
                <span>Dinas Perumahan Rakyat & Kawasan Permukiman<br>Provinsi Jawa Tengah</span>
            </div>

        </div>
    </div>

</div>

<script>
document.querySelectorAll('[data-lihat]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        const input = document.getElementById(btn.dataset.lihat);
        const icon = btn.querySelector('i');
        const tampil = input.type === 'password';
        input.type = tampil ? 'text' : 'password';
        icon.classList.replace(tampil ? 'fa-eye' : 'fa-eye-slash', tampil ? 'fa-eye-slash' : 'fa-eye');
    });
});
document.getElementById('aturSandiForm').addEventListener('submit', function (e) {
    const ulang = document.getElementById('sandi_ulang');
    if (ulang.value !== document.getElementById('sandi_baru').value) {
        e.preventDefault();
        ulang.setCustomValidity('Kata sandi tidak sama.');
        ulang.reportValidity();
        return;
    }
    const btn = document.getElementById('btnAturSandi');
    btn.classList.add('loading');
    btn.disabled = true;
});
document.getElementById('sandi_ulang').addEventListener('input', function () { this.setCustomValidity(''); });
</script>

</body>
</html>
