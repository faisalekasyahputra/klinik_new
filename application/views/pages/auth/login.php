<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token-name" content="<?= $this->security->get_csrf_token_name(); ?>">
    <meta name="csrf-token-hash" content="<?= $this->security->get_csrf_hash(); ?>">
    <title>Masuk — Klinik PKP</title>
    <meta name="description" content="Masuk ke portal layanan perumahan dan kawasan permukiman terpadu Provinsi Jawa Tengah.">
    <link rel="icon" href="<?= base_url('assets/img/logo-jateng.png') ?>" type="image/png">

    <!-- Styles -->
    <link rel="stylesheet" href="<?= base_url('assets/css/auth-pages.css?v=' . time()) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/notifications.css?v=' . filemtime('assets/css/notifications.css')) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script defer src="<?= base_url('assets/js/notifications.js?v=' . filemtime('assets/js/notifications.js')) ?>"></script>

    <!-- reCAPTCHA v2 -->
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="auth-page">
<?php $this->load->view('components/notification_center'); ?>

<div class="auth-split">

    <!-- =====================================================
         LEFT PANEL — Animated Gradient + Branding
         ===================================================== -->
    <div class="auth-left" aria-hidden="true">
        <div class="auth-left__gradient"></div>
        <div class="auth-left__orb auth-left__orb--1"></div>
        <div class="auth-left__orb auth-left__orb--2"></div>
        <div class="auth-left__orb auth-left__orb--3"></div>
        <!-- Batik Kawung Background Pattern -->
        <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: 0; pointer-events: none; opacity: 0.05; -webkit-mask-image: linear-gradient(to bottom, transparent 0%, black 50%, black 100%), linear-gradient(to right, transparent 0%, black 20%, black 80%, transparent 100%); -webkit-mask-composite: source-in; mask-image: linear-gradient(to bottom, transparent 0%, black 50%, black 100%), linear-gradient(to right, transparent 0%, black 20%, black 80%, transparent 100%); mask-composite: intersect;">
            <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
              <defs>
                <pattern id="batik-kawung-auth" x="0" y="0" width="100" height="100" patternUnits="userSpaceOnUse">
                  <circle cx="0" cy="0" r="50" fill="none" stroke="#00545f" stroke-width="2"/>
                  <circle cx="100" cy="0" r="50" fill="none" stroke="#00545f" stroke-width="2"/>
                  <circle cx="0" cy="100" r="50" fill="none" stroke="#00545f" stroke-width="2"/>
                  <circle cx="100" cy="100" r="50" fill="none" stroke="#00545f" stroke-width="2"/>
                  <line x1="-15" y1="0" x2="15" y2="0" stroke="#00545f" stroke-width="2"/>
                  <line x1="0" y1="-15" x2="0" y2="15" stroke="#00545f" stroke-width="2"/>
                  <circle cx="0" cy="0" r="4.5" fill="#d6fb00"/>
                  <line x1="85" y1="0" x2="115" y2="0" stroke="#00545f" stroke-width="2"/>
                  <line x1="100" y1="-15" x2="100" y2="15" stroke="#00545f" stroke-width="2"/>
                  <circle cx="100" cy="0" r="4.5" fill="#d6fb00"/>
                  <line x1="-15" y1="100" x2="15" y2="100" stroke="#00545f" stroke-width="2"/>
                  <line x1="0" y1="85" x2="0" y2="115" stroke="#00545f" stroke-width="2"/>
                  <circle cx="0" cy="100" r="4.5" fill="#d6fb00"/>
                  <line x1="85" y1="100" x2="115" y2="100" stroke="#00545f" stroke-width="2"/>
                  <line x1="100" y1="85" x2="100" y2="115" stroke="#00545f" stroke-width="2"/>
                  <circle cx="100" cy="100" r="4.5" fill="#d6fb00"/>
                  <polygon points="50,40 60,50 50,60 40,50" fill="none" stroke="#00a3b5" stroke-width="2"/>
                  <circle cx="50" cy="50" r="2.5" fill="#ecffb6"/>
                  <circle cx="50" cy="22" r="2" fill="#00a3b5"/>
                  <circle cx="50" cy="78" r="2" fill="#00a3b5"/>
                  <circle cx="22" cy="50" r="2" fill="#00a3b5"/>
                  <circle cx="78" cy="50" r="2" fill="#00a3b5"/>
                </pattern>
              </defs>
              <rect width="100%" height="100%" fill="url(#batik-kawung-auth)" />
            </svg>
        </div>

        <div class="auth-left__content">
            <a href="<?= base_url() ?>" class="auth-left__logo" style="text-decoration:none; display: flex; align-items: center; gap: 0.75rem;">
                <img src="<?= base_url('assets/img/logo-jateng.png') ?>" alt="Logo Jawa Tengah" style="height: 2.25rem; width: auto; object-fit: contain;">
                <span class="auth-left__logo-text" style="font-size: 1.5rem; font-weight: 900; color: #fff;">
                    Klinik<span style="color: #d6fb00;">PKP</span>
                </span>
            </a>
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

            <!-- Back Link -->
            <a href="<?= base_url() ?>" class="auth-back-link">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Beranda
            </a>

            <!-- Mobile Logo (hidden on desktop) -->
            <div class="auth-mobile-logo" style="display:none; align-items: center; gap: 0.5rem; margin-bottom: 2rem;">
                <img src="<?= base_url('assets/img/logo-jateng.png') ?>" alt="Logo Jawa Tengah" style="height: 2rem; width: auto; object-fit: contain;">
                <span style="font-weight:900; font-size:1.25rem; color:var(--auth-gray-900);">Klinik<span style="color: #0d2228;">PKP</span></span>
            </div>

            <h2 class="auth-heading">Selamat Datang 👋</h2>
            <p class="auth-subheading">Masuk ke akun Anda untuk mengakses seluruh layanan portal.</p>

            </div>

            <?php
            // ============================================================
            // KREDENSIAL DEMO — dikembalikan atas permintaan user 27 Jul 2026
            // ------------------------------------------------------------
            // Alasannya: sistem sedang dalam tahap uji coba oleh dinas, dan
            // tanpa kredensial di layar mereka tidak bisa menelusuri keenam
            // peran. Ini keputusan sadar, bukan kelalaian.
            //
            // SYARAT yang membuatnya boleh ada (lihat AGENTS.md §17 poin 12):
            // seluruh akun di sini WAJIB akun demo berisi data contoh. Begitu
            // sistem memuat data warga sungguhan, atau begitu ada akun di sini
            // yang memegang wewenang nyata, blok ini HARUS dicabut lagi.
            // ============================================================
            ?>
            <!-- Demo Accounts Info Box -->
            <div style="background: rgba(214, 251, 0, 0.1); border: 1px solid rgba(214, 251, 0, 0.3); border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem;">
                <h3 style="font-size: 0.8rem; font-weight: 700; color: #8aacb0; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 0.05em;"><i class="fa-solid fa-flask" style="margin-right: 4px;"></i> Kredensial Demo</h3>
                <p style="font-size: 0.7rem; color: #8aacb0; margin-bottom: 0.6rem; line-height: 1.5;">Akun uji coba berisi data contoh. Klik salah satu untuk mengisi form otomatis.</p>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 0.5rem; font-size: 0.8rem;">
                    <?php
                    $akun_demo = [
                        ['Admin Dashboard',           'admin@klinikpkp.jatengprov.go.id', 'admin'],
                        ['Warga (Pengaju)',           'warga@example.com',                'warga_demo'],
                        ['Pengembang (SRP2)',         'pengembang@example.com',           'pengembang_demo'],
                        ['Mahasiswa (KKN/Magang)',    'mahasiswa@example.com',            'mahasiswa_demo'],
                        ['Admin Kab/Kota (Semarang)', 'adminkabkota@example.com',         'adminkabkota_demo'],
                        ['Admin Bidang (Perumahan)',  'adminbidang@example.com',          'adminbidang_demo'],
                    ];
                    foreach ($akun_demo as [$label, $email, $username]): ?>
                    <button type="button" onclick="document.getElementById('login_email').value='<?= html_escape($email) ?>'; document.getElementById('login_password').value='password';" style="background: rgba(255,255,255,0.05); padding: 0.5rem; border-radius: 8px; border: 1px solid transparent; cursor: pointer; text-align: left; transition: all 0.2s;" onmouseover="this.style.borderColor='rgba(214,251,0,0.5)';" onmouseout="this.style.borderColor='transparent';">
                        <div style="color: #8aacb0; font-size: 0.7rem; margin-bottom: 4px;"><?= html_escape($label) ?></div>
                        <div style="color: #fff; font-weight: 600; line-height: 1.4; word-break: break-all;">E: <?= html_escape($email) ?><br>U: <?= html_escape($username) ?><br>P: password</div>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Login Form -->
            <form action="<?= base_url('Auth/do_login') ?>" method="POST" id="loginForm">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

                <!-- Email / Username -->
                <label class="auth-label" for="login_email">Username atau Email</label>
                <div class="auth-input-group">
                    <input type="text" id="login_email" name="email" class="auth-input"
                           placeholder="Masukkan username atau email" required autocomplete="username"
                           value="<?= set_value('email') ?>">
                    <i class="fa-solid fa-user auth-input-icon"></i>
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
