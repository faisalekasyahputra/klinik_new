<?php
defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Modal "Masuk" - dipakai portal publik (layouts/main.php) untuk seluruh
 * tautan yang MEMAKSA login (gerbang_login()), SELAIN tombol "Masuk" di
 * navbar (permintaan user 17 Agt 2026, disertai contoh tampilan).
 *
 * Tombol "Masuk" navbar (nav.php/main.php) SENGAJA tetap menuju halaman
 * penuh Auth/login - itu titik masuk yang memang diniatkan orang menuju
 * login, bukan dialihkan ke sana oleh gerbang. Modal ini menangani kasus
 * SEBALIKNYA: orang mengklik sesuatu yang lain (mis. "Kirim Aduan",
 * "Rekam Data", dashboard) sambil belum login, dan tanpa modal ini mereka
 * dilempar penuh ke /Auth/login - kehilangan konteks halaman yang barusan
 * dilihat.
 *
 * KENAPA FORM INI MASIH POST BIASA (bukan fetch/AJAX), padahal dipicu dari
 * loader progresif: gerbang_login() di controller tujuan SUDAH menulis
 * session `intended_url` = halaman yang barusan digerbangi, SEBELUM
 * responsnya sampai ke sini (lihat footer.php, deteksi res.redirected).
 * Auth::do_login() sesudah sukses login membaca intended_url itu dan
 * mengarahkan orang ke SANA, bukan ke halaman tempat modal ini dibuka -
 * jadi submit form ini (navigasi PENUH, sengaja bukan fetch) otomatis
 * membawa orang ke tujuan aslinya. Menjadikannya fetch/AJAX di sini cuma
 * menambah pekerjaan meniru ulang mekanisme itu, bukan memperbaiki apa pun.
 *
 * Bentuk & gaya SENGAJA TIDAK memuat assets/css/auth-pages.css - stylesheet
 * itu punya reset global (`*{margin:0;padding:0}`) dan aturan `height:100vh`
 * yang ditulis untuk halaman berdiri sendiri; memuatnya di sini akan bocor
 * ke seluruh portal. Nilai warna/radius di bawah disalin manual dari sana
 * supaya tampilannya sama persis, dinamai kpkp-login-modal-* (bukan
 * auth-*) supaya tidak pernah tabrakan kalau suatu saat auth-pages.css
 * ATAU stylesheet ini sama-sama termuat di halaman yang sama.
 */
$modal_recaptcha_site_key = getenv('RECAPTCHA_SITE_KEY') ?: '';
?>
<dialog id="kpkp-login-dialog" class="kpkp-login-modal">
    <div class="kpkp-login-modal__card">
        <button type="button" id="kpkp-login-close" class="kpkp-login-modal__close" aria-label="Tutup">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <div class="kpkp-login-modal__logo">
            <img src="<?= base_url('assets/img/logo-jateng.png') ?>" alt="Logo Jawa Tengah">
            <span>Klinik<span class="kpkp-login-modal__logo-accent">PKP</span></span>
        </div>

        <h2 class="kpkp-login-modal__heading">Selamat Datang 👋</h2>
        <p class="kpkp-login-modal__subheading">Masuk ke akun Anda untuk melanjutkan.</p>

        <form action="<?= base_url('Auth/do_login') ?>" method="POST" id="kpkp-login-modal-form">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

            <label class="kpkp-login-modal__label" for="kpkp_login_modal_email">Username atau Email</label>
            <div class="kpkp-login-modal__input-group">
                <input type="text" id="kpkp_login_modal_email" name="email" class="kpkp-login-modal__input"
                       placeholder="Masukkan username atau email" required autocomplete="username">
                <i class="fa-solid fa-user kpkp-login-modal__input-icon"></i>
            </div>

            <label class="kpkp-login-modal__label" for="kpkp_login_modal_password">Password</label>
            <div class="kpkp-login-modal__input-group">
                <input type="password" id="kpkp_login_modal_password" name="password" class="kpkp-login-modal__input"
                       placeholder="Masukkan password" required autocomplete="current-password">
                <i class="fa-solid fa-lock kpkp-login-modal__input-icon"></i>
                <button type="button" class="kpkp-login-modal__password-toggle" id="kpkp-login-modal-toggle-pw" aria-label="Tampilkan password">
                    <i class="fa-solid fa-eye"></i>
                </button>
            </div>

            <?php if ($modal_recaptcha_site_key !== ''): ?>
            <!-- Sengaja tidak dimuat kalau site key kosong (default lingkungan
                 ini) - menghindari memuat skrip Google di setiap halaman
                 portal untuk widget yang tidak dipakai. Kalau dinas
                 mengaktifkan reCAPTCHA di production, blok ini otomatis
                 ikut aktif dan skrip di bawah memuat API-nya sekali. -->
            <div class="kpkp-login-modal__recaptcha">
                <div class="g-recaptcha" data-sitekey="<?= html_escape($modal_recaptcha_site_key) ?>"></div>
            </div>
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
            <?php endif; ?>

            <button type="submit" class="kpkp-login-modal__submit" id="kpkp-login-modal-submit">
                <span>Masuk</span>
                <i class="fa-solid fa-arrow-right"></i>
                <span class="kpkp-login-modal__spinner"></span>
            </button>
        </form>

        <div class="kpkp-login-modal__divider">atau</div>

        <button type="button" class="kpkp-login-modal__google" id="kpkp-login-modal-google">
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
            Masuk dengan Google
        </button>

        <div class="kpkp-login-modal__footer-links">
            <a href="<?= base_url('Auth/forgot_password') ?>" class="kpkp-login-modal__link">Lupa Password?</a>
            <span>Belum punya akun? <a href="<?= base_url('Auth/register') ?>" class="kpkp-login-modal__link">Daftar →</a></span>
        </div>

        <?php
        // Kredensial Demo (§17 poin 12 AGENTS.md - wajib akun demo berisi
        // data contoh, dicabut begitu ada data/wewenang sungguhan). Daftar
        // akunnya SAMA PERSIS dengan pages/auth/login.php - dua salinan
        // yang sengaja disinkronkan manual, bukan di-extract ke satu
        // sumber, karena komponen ini dan halaman login berumur beda dan
        // salah satu bisa dicabut duluan (butir §17 di atas).
        ?>
        <details class="kpkp-login-modal__demo">
            <summary>
                <i class="fa-solid fa-flask"></i> Kredensial Demo
                <span class="kpkp-login-modal__demo-hint">klik akun untuk mengisi form</span>
            </summary>
            <div class="kpkp-login-modal__demo-grid">
                <?php
                $modal_akun_demo = [
                    ['Admin Dashboard',           'admin@klinikpkp.jatengprov.go.id'],
                    ['Warga (Pengaju)',           'warga@example.com'],
                    ['Pengembang (SRP2)',         'pengembang@example.com'],
                    /* Dua akun terpisah dengan role SAMA ('mahasiswa') - KKN
                       sekarang mendaftarkan kampus (permintaan user 21 Agt
                       2026), bukan satu mahasiswa. Sinkron manual dengan
                       pages/auth/login.php, lihat komentar di sana. */
                    ['Universitas (KKN)',         'universitas@example.com'],
                    ['Mahasiswa (Magang)',        'mahasiswa@example.com'],
                    ['Admin Kab/Kota (Semarang)', 'adminkabkota@example.com'],
                    ['Admin Bidang (Perumahan)',            'adminbidang@example.com'],
                    ['Admin Bidang (Kawasan Permukiman)',   'adminbidang.kawasan@example.com'],
                    ['Admin Bidang (Pertanahan)',           'adminbidang.pertanahan@example.com'],
                    ['Admin Bidang (Perencanaan Teknis)',   'adminbidang.perencanaan@example.com'],
                    ['Admin Bidang (Sekretariat)',          'adminbidang.sekretariat@example.com'],
                ];
                foreach ($modal_akun_demo as [$label, $email]): ?>
                <button type="button" class="kpkp-login-modal__demo-card" data-demo-email="<?= html_escape($email) ?>">
                    <span class="kpkp-login-modal__demo-card-role"><?= html_escape($label) ?></span>
                    <span class="kpkp-login-modal__demo-card-email"><?= html_escape($email) ?></span>
                </button>
                <?php endforeach; ?>
            </div>
            <p class="kpkp-login-modal__demo-note">Akun uji berisi data contoh. Password semua akun: <code>password</code></p>
        </details>

        <div class="kpkp-login-modal__govt-badge">
            <i class="fa-solid fa-shield-halved"></i>
            <span>Dinas Perumahan Rakyat &amp; Kawasan Permukiman<br>Provinsi Jawa Tengah</span>
        </div>
    </div>
</dialog>

<dialog id="kpkp-register-dialog" class="kpkp-login-modal">
    <div class="kpkp-login-modal__card">
        <button type="button" id="kpkp-register-close" class="kpkp-login-modal__close" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button>
        <div class="kpkp-login-modal__logo"><img src="<?= base_url('assets/img/logo-jateng.png') ?>" alt="Logo Jawa Tengah"><span>Klinik<span class="kpkp-login-modal__logo-accent">PKP</span></span></div>
        <h2 class="kpkp-login-modal__heading">Buat Akun Warga</h2>
        <p class="kpkp-login-modal__subheading">Daftar untuk melanjutkan pendataan secara mandiri.</p>
        <p id="kpkp-register-nik-info" class="kpkp-register-nik-info" hidden></p>
        <form action="<?= base_url('Auth/do_register') ?>" method="POST" id="kpkp-register-modal-form">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
            <label class="kpkp-login-modal__label" for="kpkp_register_modal_email">Alamat Email</label>
            <div class="kpkp-login-modal__input-group"><input type="email" id="kpkp_register_modal_email" name="email" class="kpkp-login-modal__input" placeholder="nama@email.com" required autocomplete="email"><i class="fa-solid fa-envelope kpkp-login-modal__input-icon"></i></div>
            <label class="kpkp-login-modal__label" for="kpkp_register_modal_password">Password</label>
            <div class="kpkp-login-modal__input-group"><input type="password" id="kpkp_register_modal_password" name="password" class="kpkp-login-modal__input" placeholder="Minimal 8 karakter, huruf besar, angka, simbol" required autocomplete="new-password"><i class="fa-solid fa-lock kpkp-login-modal__input-icon"></i></div>
            <label class="kpkp-login-modal__label" for="kpkp_register_modal_confirm">Konfirmasi Password</label>
            <div class="kpkp-login-modal__input-group"><input type="password" id="kpkp_register_modal_confirm" name="password_confirm" class="kpkp-login-modal__input" placeholder="Ulangi password" required autocomplete="new-password"><i class="fa-solid fa-lock kpkp-login-modal__input-icon"></i></div>
            <?php if ($modal_recaptcha_site_key !== ''): ?>
            <div class="kpkp-login-modal__recaptcha"><div class="g-recaptcha" data-sitekey="<?= html_escape($modal_recaptcha_site_key) ?>"></div></div>
            <?php endif; ?>
            <label class="kpkp-register-agreement"><input type="checkbox" name="tos_agree" required> Saya menyetujui Ketentuan Layanan dan Kebijakan Privasi.</label>
            <button type="submit" class="kpkp-login-modal__submit" id="kpkp-register-modal-submit"><span>Daftar dan Lanjutkan</span><i class="fa-solid fa-arrow-right"></i><span class="kpkp-login-modal__spinner"></span></button>
        </form>
        <div class="kpkp-login-modal__footer-links" style="justify-content:center"><span>Sudah punya akun? <a href="#" id="kpkp-register-to-login" class="kpkp-login-modal__link">Masuk →</a></span></div>
    </div>
</dialog>

<style>
.kpkp-login-modal {
    padding: 0; border: 0; border-radius: 20px; max-width: min(92vw, 440px);
    width: 100%; max-height: 90vh; box-shadow: 0 25px 60px rgba(0,0,0,.5);
    background: transparent;
}
.kpkp-login-modal::backdrop { background: rgba(5, 14, 17, .72); backdrop-filter: blur(2px); }
.kpkp-login-modal__card {
    position: relative; background: #0a1a1f; color: #fff;
    font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
    padding: 2rem 1.75rem 1.5rem; max-height: 90vh; overflow-y: auto;
}
.kpkp-login-modal__close {
    position: absolute; top: 1rem; right: 1rem; width: 32px; height: 32px;
    border: 0; border-radius: 8px; background: rgba(255,255,255,.06); color: #8aacb0;
    cursor: pointer; font-size: .95rem; display: flex; align-items: center; justify-content: center;
}
.kpkp-login-modal__close:hover { background: rgba(255,255,255,.12); color: #fff; }
.kpkp-login-modal__logo { display: flex; align-items: center; gap: .6rem; margin-bottom: 1.5rem; }
.kpkp-login-modal__logo img { height: 1.9rem; width: auto; object-fit: contain; }
.kpkp-login-modal__logo span { font-weight: 900; font-size: 1.15rem; }
.kpkp-login-modal__logo-accent { color: #d6fb00; }
.kpkp-login-modal__heading { font-size: 1.35rem; font-weight: 800; letter-spacing: -.03em; margin: 0 0 .25rem; }
.kpkp-login-modal__subheading { font-size: .825rem; color: #a1a1aa; margin: 0 0 1.5rem; line-height: 1.5; }
.kpkp-login-modal__label {
    display: block; font-size: .7rem; font-weight: 700; color: #8aacb0;
    text-transform: uppercase; letter-spacing: .05em; margin-bottom: .4rem;
}
.kpkp-login-modal__input-group { position: relative; margin-bottom: 1.1rem; }
.kpkp-login-modal__input-icon {
    position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
    color: #8aacb0; font-size: .825rem; pointer-events: none;
}
.kpkp-login-modal__input {
    width: 100%; padding: .7rem .875rem .7rem 2.6rem; background: #0f2a30;
    border: 1.5px solid rgba(214,251,0,.2); border-radius: 12px; font-size: .825rem;
    font-family: inherit; color: #fff; outline: none; transition: all .2s ease;
    box-sizing: border-box;
}
.kpkp-login-modal__input::placeholder { color: #8aacb0; }
.kpkp-login-modal__input:focus { border-color: #d6fb00; box-shadow: 0 0 0 3px rgba(214,251,0,.15); }
.kpkp-login-modal__password-toggle {
    position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
    background: none; border: 0; color: #8aacb0; cursor: pointer; font-size: .825rem; padding: 4px;
}
.kpkp-login-modal__password-toggle:hover { color: #d4d4d8; }
.kpkp-login-modal__recaptcha { margin-bottom: 1.1rem; display: flex; justify-content: center; }
.kpkp-login-modal__submit {
    width: 100%; padding: .75rem 1.5rem; background: #d6fb00; color: #0a1a1f; border: 0;
    border-radius: 12px; font-family: inherit; font-size: .825rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .05em; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 8px; transition: all .2s ease;
}
.kpkp-login-modal__submit:hover { background: #ecffb6; }
.kpkp-login-modal__submit:disabled { opacity: .6; cursor: not-allowed; }
.kpkp-login-modal__spinner {
    display: none; width: 16px; height: 16px; border: 2px solid rgba(0,0,0,.2);
    border-top-color: #0a1a1f; border-radius: 50%; animation: kpkp-login-modal-spin .6s linear infinite;
}
.kpkp-login-modal__submit.loading .kpkp-login-modal__spinner { display: block; }
.kpkp-login-modal__submit.loading span:first-child { display: none; }
.kpkp-login-modal__submit.loading i { display: none; }
@keyframes kpkp-login-modal-spin { to { transform: rotate(360deg); } }
.kpkp-login-modal__divider {
    display: flex; align-items: center; gap: 14px; margin: 1.25rem 0; color: #8aacb0;
    font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em;
}
.kpkp-login-modal__divider::before, .kpkp-login-modal__divider::after {
    content: ''; flex: 1; height: 1px; background: rgba(255,255,255,.08);
}
.kpkp-login-modal__google {
    width: 100%; padding: .7rem 1.5rem; background: #0f2a30; color: #d4d4d8;
    border: 1.5px solid rgba(214,251,0,.2); border-radius: 12px; font-family: inherit;
    font-size: .825rem; font-weight: 600; cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 10px; transition: all .2s ease;
}
.kpkp-login-modal__google:hover { border-color: rgba(214,251,0,.4); }
.kpkp-login-modal__google svg { width: 17px; height: 17px; }
.kpkp-login-modal__footer-links {
    display: flex; align-items: center; justify-content: space-between; margin-top: 1.25rem;
    font-size: .75rem; color: #a1a1aa; flex-wrap: wrap; gap: .5rem;
}
.kpkp-login-modal__link { color: #ecffb6; text-decoration: none; font-weight: 600; }
.kpkp-login-modal__link:hover { color: #d6fb00; text-decoration: underline; }
.kpkp-login-modal__demo {
    background: rgba(214,251,0,.06); border: 1px solid rgba(214,251,0,.25);
    border-radius: 12px; padding: .75rem .9rem; margin-top: 1.5rem;
}
.kpkp-login-modal__demo summary {
    cursor: pointer; list-style: none; display: flex; align-items: center; gap: .5rem;
    font-size: .75rem; font-weight: 700; color: #8aacb0; text-transform: uppercase;
    letter-spacing: .05em; user-select: none;
}
.kpkp-login-modal__demo summary::-webkit-details-marker { display: none; }
.kpkp-login-modal__demo summary::after {
    content: '\f078'; font-family: 'Font Awesome 6 Free'; font-weight: 900; font-size: .6rem;
    margin-left: auto; transition: transform .2s;
}
.kpkp-login-modal__demo[open] summary::after { transform: rotate(180deg); }
.kpkp-login-modal__demo-hint { font-weight: 500; text-transform: none; letter-spacing: 0; font-size: .65rem; color: rgba(138,172,176,.75); }
.kpkp-login-modal__demo-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: .45rem; margin-top: .7rem; }
.kpkp-login-modal__demo-card {
    background: rgba(255,255,255,.05); padding: .5rem .6rem; border-radius: 8px;
    border: 1px solid transparent; cursor: pointer; text-align: left; width: 100%; min-width: 0;
    transition: border-color .2s, background .2s;
}
.kpkp-login-modal__demo-card:hover, .kpkp-login-modal__demo-card:focus-visible {
    border-color: rgba(214,251,0,.5); background: rgba(255,255,255,.08); outline: none;
}
.kpkp-login-modal__demo-card-role { display: block; color: #8aacb0; font-size: .63rem; margin-bottom: 2px; }
.kpkp-login-modal__demo-card-email { display: block; color: #fff; font-weight: 600; font-size: .7rem; line-height: 1.35; overflow-wrap: anywhere; }
.kpkp-login-modal__demo-note { margin-top: .6rem; font-size: .65rem; color: #8aacb0; }
.kpkp-login-modal__demo-note code { background: rgba(255,255,255,.08); border-radius: 4px; padding: .05rem .35rem; color: #d6fb00; font-weight: 700; }
.kpkp-login-modal__govt-badge {
    margin-top: 1.5rem; padding-top: 1.1rem; border-top: 1px solid rgba(255,255,255,.06);
    display: flex; align-items: center; justify-content: center; gap: 10px;
    font-size: .625rem; color: #8aacb0; text-align: center;
}
.kpkp-login-modal__govt-badge i { font-size: 1.1rem; color: rgba(214,251,0,.4); }
.kpkp-register-nik-info { margin: 0 0 1.2rem; padding: .75rem .85rem; border-radius: 10px; background: rgba(214,251,0,.08); border: 1px solid rgba(214,251,0,.2); color: #dcefa4; font-size: .75rem; line-height: 1.45; }
.kpkp-register-agreement { display: flex; gap: .55rem; align-items: flex-start; margin: 0 0 1.15rem; color: #a1a1aa; font-size: .72rem; line-height: 1.45; }
.kpkp-register-agreement input { margin-top: .15rem; accent-color: #d6fb00; }
@media (max-width: 420px) {
    .kpkp-login-modal__demo-grid { grid-template-columns: 1fr; }
}
</style>

<script>
(function () {
    'use strict';
    var dlg = document.getElementById('kpkp-login-dialog');
    if (!dlg || !dlg.showModal) return; // browser purba: gerbang jatuh ke navigasi penuh seperti sediakala

    /* Dipanggil dari footer.php saat loader progresif mendeteksi hasil
       fetch-nya dialihkan server ke Auth/login. Nama global sengaja
       dipertahankan sesempit mungkin (satu fungsi), diperiksa dulu
       ada/tidaknya di footer.php sebelum dipakai. */
    window.kpkpShowLoginModal = function () {
        if (dlg.open) return;
        dlg.showModal();
        var email = document.getElementById('kpkp_login_modal_email');
        if (email) email.focus();
    };

    var registerDlg = document.getElementById('kpkp-register-dialog');
    window.kpkpShowRegisterModal = function (nik) {
        if (!registerDlg || !registerDlg.showModal) { window.location.href = '<?= base_url('Auth/register') ?>'; return; }
        if (dlg.open) dlg.close();
        var info = document.getElementById('kpkp-register-nik-info');
        if (info) {
            info.hidden = !nik;
            info.textContent = nik ? 'NIK ' + nik + ' belum terdaftar di SIMPERUM. Buat akun untuk melanjutkan pendataan secara mandiri.' : '';
        }
        if (!registerDlg.open) registerDlg.showModal();
        var email = document.getElementById('kpkp_register_modal_email');
        if (email) email.focus();
    };

    function tutup() { if (dlg.open) dlg.close(); }
    function tutupDaftar() { if (registerDlg && registerDlg.open) registerDlg.close(); }
    document.getElementById('kpkp-login-close').addEventListener('click', tutup);
    document.getElementById('kpkp-register-close').addEventListener('click', tutupDaftar);
    registerDlg.addEventListener('click', function (e) { if (e.target === registerDlg) tutupDaftar(); });
    document.getElementById('kpkp-register-modal-form').addEventListener('submit', function () {
        var btn = document.getElementById('kpkp-register-modal-submit'); btn.classList.add('loading'); btn.disabled = true;
    });
    document.getElementById('kpkp-register-to-login').addEventListener('click', function (e) { e.preventDefault(); tutupDaftar(); window.kpkpShowLoginModal(); });
    dlg.addEventListener('click', function (e) { if (e.target === dlg) tutup(); }); // klik backdrop

    document.getElementById('kpkp-login-modal-toggle-pw').addEventListener('click', function () {
        var input = document.getElementById('kpkp_login_modal_password');
        var icon = this.querySelector('i');
        if (input.type === 'password') { input.type = 'text'; icon.classList.replace('fa-eye', 'fa-eye-slash'); }
        else { input.type = 'password'; icon.classList.replace('fa-eye-slash', 'fa-eye'); }
    });

    document.getElementById('kpkp-login-modal-form').addEventListener('submit', function () {
        var btn = document.getElementById('kpkp-login-modal-submit');
        btn.classList.add('loading');
        btn.disabled = true;
    });

    document.getElementById('kpkp-login-modal-google').addEventListener('click', function () {
        var w = 500, h = 600;
        var left = (screen.width - w) / 2, top = (screen.height - h) / 2;
        window.open('<?= base_url('Auth/google') ?>', 'GoogleLogin',
            'width=' + w + ',height=' + h + ',top=' + top + ',left=' + left + ',scrollbars=yes');
    });

    document.querySelectorAll('.kpkp-login-modal__demo-card').forEach(function (card) {
        card.addEventListener('click', function () {
            document.getElementById('kpkp_login_modal_email').value = card.dataset.demoEmail;
            document.getElementById('kpkp_login_modal_password').value = 'password';
            document.getElementById('kpkp_login_modal_email').focus();
        });
    });
})();
</script>
