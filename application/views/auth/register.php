<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token-name" content="<?= $this->security->get_csrf_token_name(); ?>">
    <meta name="csrf-token-hash" content="<?= $this->security->get_csrf_hash(); ?>">
    <title>Daftar Akun — Klinik PKP</title>
    <meta name="description" content="Buat akun baru untuk mengakses portal layanan perumahan dan kawasan permukiman terpadu.">

    <link rel="stylesheet" href="<?= base_url('assets/css/auth-pages.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body class="auth-page">

<div class="auth-split">

    <!-- LEFT PANEL -->
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
                Bergabung &<br>Mulai <span>Akses</span> Layanan
            </h1>
            <p class="auth-left__desc">
                Daftarkan diri Anda untuk mendapatkan akses ke seluruh
                layanan perumahan digital — informasi, aduan, dan data
                pembangunan kawasan permukiman.
            </p>
            <div class="auth-left__badge">
                <i class="fa-solid fa-shield-halved"></i>
                Data Anda dilindungi sesuai UU PDP
            </div>
        </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="auth-right">
        <div class="auth-form-container">

            <!-- Mobile Logo -->
            <div class="auth-mobile-logo" style="display:none;">
                <div class="auth-left__logo-icon" style="width:40px;height:40px;font-size:1rem;">
                    <i class="fa-solid fa-house-chimney"></i>
                </div>
                <span style="font-weight:700;font-size:1.125rem;color:var(--auth-gray-900);">Klinik PKP</span>
            </div>

            <h2 class="auth-heading">Buat Akun Baru</h2>
            <p class="auth-subheading">Isi data berikut untuk mendaftar. Cepat dan mudah.</p>

            <!-- Flash Messages -->
            <?php if ($this->session->flashdata('error')): ?>
                <div class="auth-alert auth-alert--error">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= $this->session->flashdata('error') ?>
                </div>
            <?php endif; ?>

            <!-- Registration Form -->
            <form action="<?= base_url('Auth/do_register') ?>" method="POST" id="registerForm">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

                <!-- Email -->
                <label class="auth-label" for="reg_email">Alamat Email <span style="color:var(--auth-red)">*</span></label>
                <div class="auth-input-group">
                    <input type="email" id="reg_email" name="email" class="auth-input"
                           placeholder="nama@email.com" required autocomplete="email"
                           value="<?= set_value('email') ?>">
                    <i class="fa-solid fa-envelope auth-input-icon"></i>
                </div>

                <!-- Password -->
                <label class="auth-label" for="reg_password">Password <span style="color:var(--auth-red)">*</span></label>
                <div class="auth-input-group">
                    <input type="password" id="reg_password" name="password" class="auth-input"
                           placeholder="Buat password yang kuat" required autocomplete="new-password"
                           oninput="checkPasswordStrength(this.value)">
                    <i class="fa-solid fa-lock auth-input-icon"></i>
                    <button type="button" class="auth-password-toggle" onclick="togglePassword('reg_password', this)" aria-label="Tampilkan password">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>

                <!-- Password Strength -->
                <div class="auth-strength" id="strengthBars" data-level="0">
                    <div class="auth-strength__bar"></div>
                    <div class="auth-strength__bar"></div>
                    <div class="auth-strength__bar"></div>
                    <div class="auth-strength__bar"></div>
                </div>
                <div class="auth-strength-label" id="strengthLabel" style="color:var(--auth-gray-400);">—</div>

                <!-- Password Rules -->
                <ul class="auth-rules" id="passwordRules">
                    <li id="rule-length"><i class="fa-solid fa-circle"></i> Min. 8 karakter</li>
                    <li id="rule-upper"><i class="fa-solid fa-circle"></i> 1 huruf besar</li>
                    <li id="rule-number"><i class="fa-solid fa-circle"></i> 1 angka</li>
                    <li id="rule-symbol"><i class="fa-solid fa-circle"></i> 1 simbol</li>
                </ul>

                <!-- Confirm Password -->
                <label class="auth-label" for="reg_password_confirm">Konfirmasi Password <span style="color:var(--auth-red)">*</span></label>
                <div class="auth-input-group">
                    <input type="password" id="reg_password_confirm" name="password_confirm" class="auth-input"
                           placeholder="Ulangi password" required autocomplete="new-password">
                    <i class="fa-solid fa-lock auth-input-icon"></i>
                    <button type="button" class="auth-password-toggle" onclick="togglePassword('reg_password_confirm', this)" aria-label="Tampilkan password">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>

                <!-- reCAPTCHA -->
                <div class="auth-recaptcha">
                    <div class="g-recaptcha" data-sitekey="<?= isset($recaptcha_site_key) ? $recaptcha_site_key : '' ?>"></div>
                </div>

                <!-- ToS Checkbox -->
                <label class="auth-checkbox">
                    <input type="checkbox" id="tos_agree" name="tos_agree" required>
                    Saya menyetujui <a href="#" class="auth-link" style="margin-left:4px;">Ketentuan Layanan</a>&nbsp;dan&nbsp;<a href="#" class="auth-link">Kebijakan Privasi</a>
                </label>

                <!-- Submit -->
                <button type="submit" class="auth-btn" id="btnRegister">
                    <span>Daftar Sekarang</span>
                    <i class="fa-solid fa-arrow-right"></i>
                    <div class="spinner"></div>
                </button>
            </form>

            <!-- Footer Links -->
            <div class="auth-footer-links" style="justify-content:center;">
                <span>Sudah punya akun? <a href="<?= base_url('Auth/login') ?>" class="auth-link">Masuk →</a></span>
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

function checkPasswordStrength(pw) {
    const rules = {
        length: pw.length >= 8,
        upper:  /[A-Z]/.test(pw),
        number: /[0-9]/.test(pw),
        symbol: /[^A-Za-z0-9]/.test(pw)
    };

    // Update rule indicators
    Object.keys(rules).forEach(key => {
        const el = document.getElementById('rule-' + key);
        const icon = el.querySelector('i');
        if (rules[key]) {
            el.classList.add('valid');
            icon.className = 'fa-solid fa-circle-check';
        } else {
            el.classList.remove('valid');
            icon.className = 'fa-solid fa-circle';
        }
    });

    // Calculate strength level (0-4)
    const level = Object.values(rules).filter(Boolean).length;
    const bars = document.getElementById('strengthBars');
    const label = document.getElementById('strengthLabel');
    bars.setAttribute('data-level', pw.length === 0 ? '0' : level);

    const labels = ['', 'Lemah', 'Sedang', 'Kuat', 'Sangat Kuat'];
    const colors = ['', 'var(--auth-red)', '#f97316', 'var(--auth-amber)', 'var(--auth-green)'];
    label.textContent = pw.length === 0 ? '—' : labels[level];
    label.style.color = pw.length === 0 ? 'var(--auth-gray-400)' : colors[level];
}

// Form validation & loading
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const pw = document.getElementById('reg_password').value;
    const pwConfirm = document.getElementById('reg_password_confirm').value;

    if (pw !== pwConfirm) {
        e.preventDefault();
        alert('Password dan Konfirmasi Password tidak cocok.');
        return;
    }

    if (pw.length < 8 || !/[A-Z]/.test(pw) || !/[0-9]/.test(pw) || !/[^A-Za-z0-9]/.test(pw)) {
        e.preventDefault();
        alert('Password harus minimal 8 karakter, mengandung huruf besar, angka, dan simbol.');
        return;
    }

    const btn = document.getElementById('btnRegister');
    btn.classList.add('loading');
    btn.disabled = true;
});
</script>

</body>
</html>
