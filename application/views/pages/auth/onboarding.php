<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token-name" content="<?= $this->security->get_csrf_token_name(); ?>">
    <meta name="csrf-token-hash" content="<?= $this->security->get_csrf_hash(); ?>">
    <title>Lengkapi Profil — Klinik PKP</title>
    <link rel="icon" href="<?= base_url('assets/img/logo-jateng.png') ?>" type="image/png">

    <link rel="stylesheet" href="<?= base_url('assets/css/auth-pages.css?v=' . filemtime('assets/css/auth-pages.css')) ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/notifications.css?v=' . filemtime('assets/css/notifications.css')) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script defer src="<?= base_url('assets/js/notifications.js?v=' . filemtime('assets/js/notifications.js')) ?>"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
            <div class="auth-left__logo">
                <div class="auth-left__logo-icon">
                    <i class="fa-solid fa-house-chimney"></i>
                </div>
                <span class="auth-left__logo-text">Klinik PKP</span>
            </div>
            <h1 class="auth-left__tagline">
                Satu Langkah<br>Lagi <span>Menuju</span> Akses
            </h1>
            <p class="auth-left__desc">
                Lengkapi profil Anda untuk membuka akses penuh ke seluruh
                layanan portal perumahan. Pilih peran yang sesuai dengan
                kebutuhan Anda.
            </p>
            <div class="auth-left__badge">
                <i class="fa-solid fa-lock"></i>
                Data tersimpan aman dan terenkripsi
            </div>
        </div>
    </div>

    <!-- RIGHT PANEL — Onboarding Form -->
    <div class="auth-right" style="align-items: flex-start; padding-top: 3rem;">
        <div class="auth-form-container" style="max-width: 520px;" x-data="onboardingForm()">

            <!-- Mobile Logo -->
            <div class="auth-mobile-logo" style="display:none;">
                <div class="auth-left__logo-icon" style="width:40px;height:40px;font-size:1rem;">
                    <i class="fa-solid fa-house-chimney"></i>
                </div>
                <span style="font-weight:700;font-size:1.125rem;color:var(--auth-gray-900);">Klinik PKP</span>
            </div>

            <!-- Welcome -->
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:0.25rem;">
                <h2 class="auth-heading" style="margin:0;">Lengkapi Profil</h2>
                <span style="font-size:1.5rem; color:var(--auth-primary);"><i class="fa-solid fa-address-card"></i></span>
            </div>
            <p class="auth-subheading">
                Halo, <strong><?= isset($user_email) ? htmlspecialchars($user_email) : '' ?></strong>!
                Pilih peran dan lengkapi data Anda.
            </p>

            <!-- Form -->
            <form action="<?= base_url('Auth/save_onboarding') ?>" method="POST" enctype="multipart/form-data" id="onboardingForm">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

                <!-- ===== ROLE SELECTOR ===== -->
                <label class="auth-label">Pilih Peran Anda <span style="color:var(--auth-red)">*</span></label>
                <div class="auth-role-grid">
                    <!-- Warga -->
                    <div class="auth-role-card" :class="{ 'selected': role === 'warga' }" @click="selectRole('warga')">
                        <input type="radio" name="role" value="warga" x-model="role" required>
                        <div class="auth-role-card__icon" style="color:#10b981;"><i class="fa-solid fa-house-user"></i></div>
                        <div class="auth-role-card__title">Warga</div>
                        <div class="auth-role-card__desc">Pencari Rumah</div>
                    </div>
                    <!-- Pengembang -->
                    <div class="auth-role-card" :class="{ 'selected': role === 'pengembang' }" @click="selectRole('pengembang')">
                        <input type="radio" name="role" value="pengembang" x-model="role">
                        <div class="auth-role-card__icon" style="color:#f59e0b;"><i class="fa-solid fa-helmet-safety"></i></div>
                        <div class="auth-role-card__title">Pengembang</div>
                        <div class="auth-role-card__desc">Developer Perumahan</div>
                    </div>
                    <!-- Vendor -->
                    <div class="auth-role-card" :class="{ 'selected': role === 'vendor' }" @click="selectRole('vendor')">
                        <input type="radio" name="role" value="vendor" x-model="role">
                        <div class="auth-role-card__icon" style="color:#3b82f6;"><i class="fa-solid fa-store"></i></div>
                        <div class="auth-role-card__title">Vendor</div>
                        <div class="auth-role-card__desc">Supplier / Jasa</div>
                    </div>
                    <!-- Mahasiswa -->
                    <div class="auth-role-card" :class="{ 'selected': role === 'mahasiswa' }" @click="selectRole('mahasiswa')">
                        <input type="radio" name="role" value="mahasiswa" x-model="role">
                        <div class="auth-role-card__icon" style="color:#8b5cf6;"><i class="fa-solid fa-user-graduate"></i></div>
                        <div class="auth-role-card__title">Mahasiswa</div>
                        <div class="auth-role-card__desc">Magang / Penelitian</div>
                    </div>
                </div>

                <!-- ===== COMMON FIELDS (all roles) ===== -->
                <div class="auth-dynamic-form" :class="{ 'visible': role !== '' }">
                    <div class="auth-section-title">
                        <i class="fa-solid fa-user"></i> Data Pribadi
                    </div>

                    <!-- Username (Tampil di Forum) -->
                    <label class="auth-label" for="username">Username (Tampil di Forum) <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <input type="text" id="username" name="username" class="auth-input"
                               placeholder="Nama singkat tanpa spasi, cth: budi_santoso" :required="role !== ''"
                               maxlength="30" pattern="^\S+$" oninput="this.value = this.value.replace(/\s/g, '').toLowerCase()">
                        <i class="fa-solid fa-at auth-input-icon"></i>
                    </div>

                    <!-- Nama Lengkap -->
                    <label class="auth-label" for="nama_lengkap">Nama Lengkap Sesuai Identitas <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <input type="text" id="nama_lengkap" name="nama_lengkap" class="auth-input"
                               placeholder="Masukkan nama sesuai KTP" :required="role !== ''">
                        <i class="fa-solid fa-user auth-input-icon"></i>
                    </div>

                    <!-- NIK -->
                    <label class="auth-label" for="nik_identitas">No. Identitas (NIK) <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <input type="text" id="nik_identitas" name="nik_identitas" class="auth-input"
                               placeholder="Masukkan 16 digit NIK" maxlength="16" pattern="[0-9]{16}"
                               :required="role !== ''" inputmode="numeric"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        <i class="fa-solid fa-id-card auth-input-icon"></i>
                    </div>

                    <!-- Alamat -->
                    <label class="auth-label" for="alamat_domisili">Alamat Domisili <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <textarea id="alamat_domisili" name="alamat_domisili" class="auth-input"
                                  placeholder="Alamat lengkap beserta RT/RW, Desa/Kelurahan" rows="3"
                                  :required="role !== ''" style="padding-left:2.75rem;"></textarea>
                        <i class="fa-solid fa-map-location-dot auth-input-icon" style="top:1rem;transform:none;"></i>
                    </div>

                    <!-- Phone -->
                    <label class="auth-label" for="phone">No. Telepon / WhatsApp <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <input type="tel" id="phone" name="phone" class="auth-input"
                               placeholder="08xxxxxxxxxx" :required="role !== ''">
                        <i class="fa-solid fa-phone auth-input-icon"></i>
                    </div>
                </div>

                <!-- ===== PASSWORD FIELDS (Google users only) ===== -->
                <?php if (!empty($needs_password)): ?>
                <div class="auth-dynamic-form" :class="{ 'visible': role !== '' }">
                    <div class="auth-section-title" style="color:#f59e0b;">
                        <i class="fa-solid fa-key"></i> Buat Password
                    </div>
                    <p style="font-size: 0.8rem; color: var(--auth-gray-400); margin: -0.5rem 0 1rem; line-height: 1.5;">
                        Karena Anda mendaftar melalui Google, buat password agar Anda juga bisa login dengan email dan password.
                    </p>

                    <label class="auth-label" for="ob_password">Password <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <input type="password" id="ob_password" name="password" class="auth-input"
                               placeholder="Buat password yang kuat" required autocomplete="new-password"
                               oninput="checkPwStrength(this.value)">
                        <i class="fa-solid fa-lock auth-input-icon"></i>
                        <button type="button" class="auth-password-toggle" onclick="togglePw('ob_password', this)" aria-label="Tampilkan password">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>

                    <!-- Password Strength -->
                    <div class="auth-strength" id="obStrengthBars" data-level="0">
                        <div class="auth-strength__bar"></div>
                        <div class="auth-strength__bar"></div>
                        <div class="auth-strength__bar"></div>
                        <div class="auth-strength__bar"></div>
                    </div>
                    <div class="auth-strength-label" id="obStrengthLabel" style="color:var(--auth-gray-400);">—</div>

                    <ul class="auth-rules" id="obPasswordRules">
                        <li id="ob-rule-length"><i class="fa-solid fa-circle"></i> Min. 8 karakter</li>
                        <li id="ob-rule-upper"><i class="fa-solid fa-circle"></i> 1 huruf besar</li>
                        <li id="ob-rule-number"><i class="fa-solid fa-circle"></i> 1 angka</li>
                        <li id="ob-rule-symbol"><i class="fa-solid fa-circle"></i> 1 simbol</li>
                    </ul>

                    <label class="auth-label" for="ob_password_confirm">Konfirmasi Password <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <input type="password" id="ob_password_confirm" name="password_confirm" class="auth-input"
                               placeholder="Ulangi password" required autocomplete="new-password">
                        <i class="fa-solid fa-lock auth-input-icon"></i>
                        <button type="button" class="auth-password-toggle" onclick="togglePw('ob_password_confirm', this)" aria-label="Tampilkan password">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ===== PENGEMBANG FIELDS ===== -->
                <div class="auth-dynamic-form" :class="{ 'visible': role === 'pengembang' }">
                    <div class="auth-section-title" style="color:#f59e0b;">
                        <i class="fa-solid fa-building"></i> Data Perusahaan Pengembang
                    </div>

                    <label class="auth-label" for="nama_perusahaan">Nama Perusahaan <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <input type="text" id="nama_perusahaan" name="nama_perusahaan" class="auth-input"
                               placeholder="PT / CV / Nama Usaha" :required="role === 'pengembang'">
                        <i class="fa-solid fa-building auth-input-icon"></i>
                    </div>

                    <label class="auth-label" for="alamat_kantor">Alamat Kantor <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <textarea id="alamat_kantor" name="alamat_kantor" class="auth-input"
                                  placeholder="Alamat kantor perusahaan" rows="2"
                                  :required="role === 'pengembang'" style="padding-left:2.75rem;"></textarea>
                        <i class="fa-solid fa-location-dot auth-input-icon" style="top:1rem;transform:none;"></i>
                    </div>

                    <label class="auth-label" for="telp_kantor">No. Telepon Kantor</label>
                    <div class="auth-input-group">
                        <input type="tel" id="telp_kantor" name="telp_kantor" class="auth-input"
                               placeholder="Nomor telepon kantor">
                        <i class="fa-solid fa-phone-office auth-input-icon"></i>
                    </div>

                    <label class="auth-label">Upload KTP Penanggung Jawab <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <input type="file" name="file_ktp" class="auth-file-input" accept=".pdf,.jpg,.jpeg,.png"
                               :required="role === 'pengembang'">
                    </div>

                    <label class="auth-label">Upload SIUP / NIB / SIUJK <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <input type="file" name="file_siup" class="auth-file-input" accept=".pdf"
                               :required="role === 'pengembang'">
                    </div>
                </div>

                <!-- ===== VENDOR FIELDS ===== -->
                <div class="auth-dynamic-form" :class="{ 'visible': role === 'vendor' }">
                    <div class="auth-section-title" style="color:#3b82f6;">
                        <i class="fa-solid fa-store"></i> Data Usaha Vendor
                    </div>

                    <label class="auth-label" for="nama_usaha">Nama Usaha / Toko <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <input type="text" id="nama_usaha" name="nama_usaha" class="auth-input"
                               placeholder="Nama usaha atau toko" :required="role === 'vendor'">
                        <i class="fa-solid fa-store auth-input-icon"></i>
                    </div>

                    <label class="auth-label" for="alamat_usaha">Alamat Usaha <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <textarea id="alamat_usaha" name="alamat_usaha" class="auth-input"
                                  placeholder="Alamat usaha/toko" rows="2"
                                  :required="role === 'vendor'" style="padding-left:2.75rem;"></textarea>
                        <i class="fa-solid fa-location-dot auth-input-icon" style="top:1rem;transform:none;"></i>
                    </div>

                    <label class="auth-label" for="jenis_usaha">Jenis Usaha <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <select id="jenis_usaha" name="jenis_usaha" class="auth-select" :required="role === 'vendor'">
                            <option value="" disabled selected>— Pilih Jenis Usaha —</option>
                            <option value="Bahan Bangunan">Bahan Bangunan</option>
                            <option value="Jasa Konstruksi">Jasa Konstruksi</option>
                            <option value="Interior & Furniture">Interior & Furniture</option>
                            <option value="Elektrikal & Plumbing">Elektrikal & Plumbing</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                        <i class="fa-solid fa-briefcase auth-input-icon"></i>
                        <i class="fa-solid fa-chevron-down auth-select-arrow"></i>
                    </div>

                    <label class="auth-label">Upload KTP Pemilik <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <input type="file" name="file_ktp_vendor" class="auth-file-input" accept=".pdf,.jpg,.jpeg,.png"
                               :required="role === 'vendor'">
                    </div>

                    <label class="auth-label">Upload Surat Ijin Usaha <span style="color:var(--auth-gray-400)">(Opsional)</span></label>
                    <div class="auth-input-group">
                        <input type="file" name="file_siu_vendor" class="auth-file-input" accept=".pdf">
                    </div>
                </div>

                <!-- ===== MAHASISWA FIELDS ===== -->
                <div class="auth-dynamic-form" :class="{ 'visible': role === 'mahasiswa' }">
                    <div class="auth-section-title" style="color:#8b5cf6;">
                        <i class="fa-solid fa-graduation-cap"></i> Data Mahasiswa
                    </div>

                    <label class="auth-label">Upload Kartu Tanda Mahasiswa (KTM) <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <input type="file" name="file_ktm" class="auth-file-input" accept=".pdf,.jpg,.jpeg,.png"
                               :required="role === 'mahasiswa'">
                    </div>

                    <label class="auth-label">Upload Surat Keterangan Magang / Pengantar <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <input type="file" name="file_surat_magang" class="auth-file-input" accept=".pdf"
                               :required="role === 'mahasiswa'">
                    </div>
                </div>

                <!-- ===== SUBMIT ===== -->
                <div class="auth-dynamic-form" :class="{ 'visible': role !== '' }" style="margin-top:0.5rem;">
                    <button type="submit" class="auth-btn" id="btnOnboarding">
                        <span>Simpan & Lanjutkan</span>
                        <i class="fa-solid fa-arrow-right"></i>
                        <div class="spinner"></div>
                    </button>
                </div>

            </form>

            <!-- Government Badge -->
            <div class="auth-govt-badge">
                <i class="fa-solid fa-landmark"></i>
                <span>Dinas Perumahan Rakyat & Kawasan Permukiman<br>Provinsi Jawa Tengah</span>
            </div>

        </div>
    </div>

</div>

<script>
function onboardingForm() {
    return {
        role: '',
        selectRole(r) {
            this.role = r;
        }
    };
}

function togglePw(inputId, btn) {
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

function checkPwStrength(pw) {
    const rules = {
        length: pw.length >= 8,
        upper:  /[A-Z]/.test(pw),
        number: /[0-9]/.test(pw),
        symbol: /[^A-Za-z0-9]/.test(pw)
    };

    Object.keys(rules).forEach(key => {
        const el = document.getElementById('ob-rule-' + key);
        if (!el) return;
        const icon = el.querySelector('i');
        if (rules[key]) {
            el.classList.add('valid');
            icon.className = 'fa-solid fa-circle-check';
        } else {
            el.classList.remove('valid');
            icon.className = 'fa-solid fa-circle';
        }
    });

    const level = Object.values(rules).filter(Boolean).length;
    const bars = document.getElementById('obStrengthBars');
    const label = document.getElementById('obStrengthLabel');
    if (bars) bars.setAttribute('data-level', pw.length === 0 ? '0' : level);

    const labels = ['', 'Lemah', 'Sedang', 'Kuat', 'Sangat Kuat'];
    const colors = ['', 'var(--auth-red)', '#f97316', 'var(--auth-amber)', 'var(--auth-green)'];
    if (label) {
        label.textContent = pw.length === 0 ? '—' : labels[level];
        label.style.color = pw.length === 0 ? 'var(--auth-gray-400)' : colors[level];
    }
}
</script>

</body>
</html>
