<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token-name" content="<?= $this->security->get_csrf_token_name(); ?>">
    <meta name="csrf-token-hash" content="<?= $this->security->get_csrf_hash(); ?>">
    <title>Masuk — Klinik PKP</title>
    <meta name="description" content="Masuk ke portal layanan perumahan dan kawasan permukiman terpadu Provinsi Jawa Tengah.">

    <!-- Styles -->
    <link rel="stylesheet" href="<?= base_url('assets/css/auth-pages.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- reCAPTCHA v2 -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="auth-page">

<div class="auth-split">

    <!-- =====================================================
         LEFT PANEL — Animated Gradient + Branding
         ===================================================== -->
    <div class="auth-left" aria-hidden="true">
        <div class="auth-left__gradient"></div>
        <div class="auth-left__orb auth-left__orb--1"></div>
        <div class="auth-left__orb auth-left__orb--2"></div>
        <div class="auth-left__orb auth-left__orb--3"></div>
        <div class="auth-left__pattern"></div>

        <div class="auth-left__content">
            <div class="auth-left__logo">
                <div class="auth-left__logo-icon">
                    <i class="fa-solid fa-house-chimney"></i>
                </div>
                <span class="auth-left__logo-text">Klinik PKP</span>
            </div>
            <h1 class="auth-left__tagline">
                Portal Layanan<br><span>Perumahan</span> Terpadu
            </h1>
            <p class="auth-left__desc">
                Sistem informasi perumahan dan kawasan permukiman terpadu
                untuk masyarakat Jawa Tengah — akses data, layanan, dan
                informasi pembangunan perumahan dalam satu platform.
            </p>
            <div class="auth-left__badge">
                <i class="fa-solid fa-shield-halved"></i>
                Disperakim Provinsi Jawa Tengah
            </div>
        </div>
    </div>

    <!-- =====================================================
         RIGHT PANEL — Login Form
         ===================================================== -->
    <div class="auth-right">
        <div class="auth-form-container">

            <!-- Mobile Logo (hidden on desktop) -->
            <div class="auth-mobile-logo" style="display:none;">
                <div class="auth-left__logo-icon" style="width:40px;height:40px;font-size:1rem;">
                    <i class="fa-solid fa-house-chimney"></i>
                </div>
                <span style="font-weight:700;font-size:1.125rem;color:var(--auth-gray-900);">Klinik PKP</span>
            </div>

            <h2 class="auth-heading">Selamat Datang 👋</h2>
            <p class="auth-subheading">Masuk ke akun Anda untuk mengakses seluruh layanan portal.</p>

            <!-- Flash Messages -->
            <?php if ($this->session->flashdata('error')): ?>
                <div class="auth-alert auth-alert--error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= $this->session->flashdata('error') ?>
                </div>
            <?php endif; ?>
            <?php if ($this->session->flashdata('success')): ?>
                <div class="auth-alert auth-alert--success">
                    <i class="fa-solid fa-circle-check"></i>
                    <?= $this->session->flashdata('success') ?>
                </div>
            <?php endif; ?>
            <?php if ($this->session->flashdata('warning')): ?>
                <div class="auth-alert auth-alert--warning">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <?= $this->session->flashdata('warning') ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form action="<?= base_url('Auth/do_login') ?>" method="POST" id="loginForm">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

                <!-- Email -->
                <label class="auth-label" for="login_email">Alamat Email</label>
                <div class="auth-input-group">
                    <input type="email" id="login_email" name="email" class="auth-input"
                           placeholder="nama@email.com" required autocomplete="email"
                           value="<?= set_value('email') ?>">
                    <i class="fa-solid fa-envelope auth-input-icon"></i>
                </div>

                <!-- Password -->
                <label class="auth-label" for="login_password">Password</label>
                <div class="auth-input-group">
                    <input type="password" id="login_password" name="password" class="auth-input"
                           placeholder="Masukkan password" required autocomplete="current-password">
                    <i class="fa-solid fa-lock auth-input-icon"></i>
                    <button type="button" class="auth-password-toggle" onclick="togglePassword('login_password', this)" aria-label="Tampilkan password">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>

                <!-- reCAPTCHA -->
                <div class="auth-recaptcha">
                    <div class="g-recaptcha" data-sitekey="<?= isset($recaptcha_site_key) ? $recaptcha_site_key : '' ?>"></div>
                </div>

                <!-- Submit -->
                <button type="submit" class="auth-btn" id="btnLogin">
                    <span>Masuk</span>
                    <i class="fa-solid fa-arrow-right"></i>
                    <div class="spinner"></div>
                </button>
            </form>

            <!-- Divider -->
            <div class="auth-divider">atau</div>

            <!-- Google Login -->
            <button type="button" class="auth-btn-google" onclick="googleLogin()">
                <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                Masuk dengan Google
            </button>

            <!-- Footer Links -->
            <div class="auth-footer-links">
                <a href="<?= base_url('Auth/forgot_password') ?>" class="auth-link">Lupa Password?</a>
                <span>Belum punya akun? <a href="<?= base_url('Auth/register') ?>" class="auth-link">Daftar →</a></span>
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
// Toggle password visibility
function togglePassword(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// Google Login popup
function googleLogin() {
    const w = 500, h = 600;
    const left = (screen.width - w) / 2;
    const top = (screen.height - h) / 2;
    window.open(
        '<?= base_url("Auth/google") ?>',
        'GoogleLogin',
        `width=${w},height=${h},top=${top},left=${left},scrollbars=yes`
    );
}

// Form loading state
document.getElementById('loginForm').addEventListener('submit', function() {
    const btn = document.getElementById('btnLogin');
    btn.classList.add('loading');
    btn.disabled = true;
});
</script>

</body>
</html>
