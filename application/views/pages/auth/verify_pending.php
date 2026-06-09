<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Email — Klinik PKP</title>

    <link rel="stylesheet" href="<?= base_url('assets/css/auth-pages.css?v=' . time()) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        .verify-card {
            max-width: 480px;
            margin: 0 auto;
            text-align: center;
        }
        .verify-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 2rem;
            position: relative;
        }
        .verify-icon--pending {
            background: rgba(214, 251, 0, 0.1);
            color: #d6fb00;
        }
        .verify-icon--success {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        .verify-icon .spinner-ring {
            position: absolute;
            inset: -4px;
            border: 3px solid transparent;
            border-top-color: #d6fb00;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .verify-email {
            display: inline-block;
            background: rgba(214, 251, 0, 0.08);
            border: 1px solid rgba(214, 251, 0, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 0.75rem;
            color: #ecffb6;
            font-weight: 600;
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }
        .verify-progress {
            width: 100%;
            height: 4px;
            background: rgba(255,255,255,0.06);
            border-radius: 2px;
            margin: 1.5rem 0;
            overflow: hidden;
        }
        .verify-progress__bar {
            height: 100%;
            background: linear-gradient(90deg, #d6fb00, #10b981);
            border-radius: 2px;
            width: 0%;
            transition: width 0.5s ease;
        }
        .verify-status {
            color: #8aacb0;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }
        .verify-steps {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            text-align: left;
            margin: 1.5rem 0;
        }
        .verify-step {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            transition: all 0.4s ease;
        }
        .verify-step.active {
            background: rgba(214, 251, 0, 0.05);
            border-color: rgba(214, 251, 0, 0.2);
        }
        .verify-step.done {
            background: rgba(16, 185, 129, 0.05);
            border-color: rgba(16, 185, 129, 0.2);
        }
        .verify-step__icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 0.8rem;
            background: rgba(255,255,255,0.05);
            color: #5a7a80;
            transition: all 0.4s ease;
        }
        .verify-step.active .verify-step__icon {
            background: rgba(214, 251, 0, 0.15);
            color: #d6fb00;
        }
        .verify-step.done .verify-step__icon {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
        }
        .verify-step__text {
            color: #5a7a80;
            font-size: 0.85rem;
            font-weight: 500;
            transition: color 0.4s ease;
        }
        .verify-step.active .verify-step__text,
        .verify-step.done .verify-step__text {
            color: #ecffb6;
        }
        .verify-btn-continue {
            display: none;
            width: 100%;
            padding: 0.75rem;
            border: none;
            border-radius: 0.75rem;
            background: linear-gradient(135deg, #d6fb00, #b5d500);
            color: #0f2a30;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }
        .verify-btn-continue:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(214, 251, 0, 0.3);
        }
        .verify-btn-continue.show {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            animation: fadeInUp 0.5s ease;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
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
            <a href="<?= base_url() ?>" class="auth-left__logo" style="text-decoration:none; display:flex; align-items:center; gap:12px;">
                <img src="<?= base_url('assets/img/logo-jateng.png') ?>" alt="Logo Jawa Tengah" style="height: 32px; width: auto; object-fit: contain;">
                <span class="auth-left__logo-text" style="color: #fff; font-weight: 900;">Klinik<span style="color: #d6fb00;">PKP</span></span>
            </a>
            <h1 class="auth-left__tagline">
                Verifikasi<br><span>Email</span> Anda
            </h1>
            <p class="auth-left__desc">
                Kami sedang memverifikasi alamat email Anda untuk memastikan
                keamanan akun. Proses ini berlangsung otomatis.
            </p>
            <div class="auth-left__badge">
                <i class="fa-solid fa-shield-halved"></i>
                Keamanan Akun Terjamin
            </div>
        </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="auth-right">
        <div class="auth-form-container verify-card">

            <!-- Icon -->
            <div class="verify-icon verify-icon--pending" id="verifyIcon">
                <div class="spinner-ring" id="spinnerRing"></div>
                <i class="fa-solid fa-envelope" id="verifyIconInner"></i>
            </div>

            <h2 class="auth-heading">Memverifikasi Email</h2>
            <p class="auth-subheading">Kami sedang memverifikasi email Anda secara otomatis.</p>

            <div class="verify-email">
                <i class="fa-solid fa-at" style="margin-right:6px; opacity:0.6;"></i>
                <?= htmlspecialchars($user_email ?? '') ?>
            </div>

            <!-- Progress Bar -->
            <div class="verify-progress">
                <div class="verify-progress__bar" id="progressBar"></div>
            </div>
            <div class="verify-status" id="statusText">Menginisialisasi...</div>

            <!-- Steps -->
            <div class="verify-steps">
                <div class="verify-step active" id="step1">
                    <div class="verify-step__icon"><i class="fa-solid fa-paper-plane"></i></div>
                    <span class="verify-step__text">Mengirim kode verifikasi...</span>
                </div>
                <div class="verify-step" id="step2">
                    <div class="verify-step__icon"><i class="fa-solid fa-envelope-open-text"></i></div>
                    <span class="verify-step__text">Memvalidasi alamat email...</span>
                </div>
                <div class="verify-step" id="step3">
                    <div class="verify-step__icon"><i class="fa-solid fa-circle-check"></i></div>
                    <span class="verify-step__text">Email berhasil diverifikasi!</span>
                </div>
            </div>

            <!-- Continue Button (hidden until complete) -->
            <button class="verify-btn-continue" id="btnContinue" onclick="window.location.href='<?= base_url('Auth/onboarding') ?>'">
                <span>Lanjutkan Lengkapi Profil</span>
                <i class="fa-solid fa-arrow-right"></i>
            </button>

            <!-- Government Badge -->
            <div class="auth-govt-badge" style="margin-top: 2rem;">
                <i class="fa-solid fa-landmark"></i>
                <span>Dinas Perumahan Rakyat & Kawasan Permukiman<br>Provinsi Jawa Tengah</span>
            </div>
        </div>
    </div>

</div>

<script>
const steps = [
    { id: 'step1', progress: 33, text: 'Mengirim kode verifikasi...', delay: 1500 },
    { id: 'step2', progress: 66, text: 'Memvalidasi alamat email...', delay: 2000 },
    { id: 'step3', progress: 100, text: 'Email berhasil diverifikasi!', delay: 1500 },
];

let currentStep = 0;

function runStep() {
    if (currentStep >= steps.length) {
        // All done — call AJAX to actually verify
        fetch('<?= base_url('Auth/do_verify_email') ?>', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: '<?= $this->security->get_csrf_token_name() ?>=<?= $this->security->get_csrf_hash() ?>'
        }).then(() => {
            // Show success state
            const icon = document.getElementById('verifyIcon');
            icon.classList.remove('verify-icon--pending');
            icon.classList.add('verify-icon--success');
            document.getElementById('spinnerRing').style.display = 'none';
            document.getElementById('verifyIconInner').className = 'fa-solid fa-circle-check';

            document.querySelector('.auth-heading').textContent = 'Email Terverifikasi!';
            document.querySelector('.auth-subheading').textContent = 'Akun Anda telah dikonfirmasi. Silakan lengkapi profil untuk melanjutkan.';
            document.getElementById('statusText').textContent = 'Verifikasi selesai';
            document.getElementById('statusText').style.color = '#10b981';

            // Show continue button
            document.getElementById('btnContinue').classList.add('show');
        });
        return;
    }

    const step = steps[currentStep];
    
    // Activate current step
    document.getElementById(step.id).classList.add('active');
    
    // Mark previous as done
    if (currentStep > 0) {
        document.getElementById(steps[currentStep - 1].id).classList.remove('active');
        document.getElementById(steps[currentStep - 1].id).classList.add('done');
    }

    // Update progress
    document.getElementById('progressBar').style.width = step.progress + '%';
    document.getElementById('statusText').textContent = step.text;

    currentStep++;
    setTimeout(runStep, step.delay);
}

// Start after a brief delay
setTimeout(runStep, 800);
</script>

</body>
</html>
