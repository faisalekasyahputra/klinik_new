<?php
/**
 * Isian yang pulang bersama pesan error dari Auth::_onboarding_fail().
 * Kosong pada kunjungan pertama. Password sengaja tidak ada di sini.
 */
$old = isset($old) && is_array($old) ? $old : [];
$isi = function ($nama) use ($old) {
    return isset($old[$nama]) ? html_escape($old[$nama]) : '';
};
?>
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

    <!-- Alpine dimuat `defer`, jadi ada jeda sebelum x-show bekerja. Tanpa ini
         ketiga tombol navigasi (Kembali, Lanjut, Simpan) berkedip bersamaan
         dulu. Langkah dan blok peran tidak butuh ini — mereka tersembunyi
         lewat CSS `.auth-dynamic-form` yang default-nya memang tertutup. -->
    <style>[x-cloak] { display: none !important; }</style>
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
            <!-- tabindex=-1: panel ini aria-hidden, jadi tautannya jangan ikut
                 antre fokus keyboard. Jalur setara yang terbaca pembaca layar
                 ada di .auth-back-link pada panel kanan. -->
            <a href="<?= base_url() ?>" class="auth-left__logo" tabindex="-1" style="text-decoration:none; display:flex; align-items:center; gap:12px;">
                <img src="<?= base_url('assets/img/logo-jateng.png') ?>" alt="Logo Jawa Tengah" style="height: 32px; width: auto; object-fit: contain;">
                <span class="auth-left__logo-text" style="color: #fff; font-weight: 900;">Klinik<span style="color: #d6fb00;">PKP</span></span>
            </a>
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
        <?php
        /**
         * Saat isian pulang karena validasi gagal, buka langkah 2 — bukan 1.
         * Perannya sudah dipilih, dan hampir semua cabang yang bisa gagal
         * (username terpakai, NIK, password) tinggal di langkah itu. Memulangkan
         * user ke pemilihan peran hanya menambah dua klik untuk keputusan yang
         * sudah ia buat.
         */
        $langkah_awal = empty($old) ? 1 : 2;
        ?>
        <div class="auth-form-container" style="max-width: 520px;" x-data="onboardingForm('<?= $isi('role') ?>', <?= (int) $langkah_awal ?>)">

            <!-- Back Link — halaman ini TIDAK ditegakkan secara global (cek
                 profile_completed cuma terjadi sekali sesudah login), jadi user
                 memang boleh pergi. Dulu tidak ada tautan apa pun ke luar, dan
                 itu membuatnya terasa seperti jebakan. -->
            <a href="<?= base_url() ?>" class="auth-back-link">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Beranda
            </a>

            <!-- Mobile Logo -->
            <div class="auth-mobile-logo" style="display:flex; align-items:center; gap:10px; margin-bottom:24px;">
                <img src="<?= base_url('assets/img/logo-jateng.png') ?>" alt="Logo Jawa Tengah" style="height: 36px; width: auto; object-fit: contain;">
                <span style="font-weight:900; font-size:1.25rem; color:#0f2a30;">Klinik<span style="color: #6d8000;">PKP</span></span>
            </div>

            <!-- Welcome -->
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:0.25rem;">
                <h2 class="auth-heading" style="margin:0;">Lengkapi Profil</h2>
                <span style="font-size:1.5rem; color:var(--auth-amber);"><i class="fa-solid fa-address-card"></i></span>
            </div>
            <p class="auth-subheading">
                Halo, <strong><?= isset($user_email) ? htmlspecialchars($user_email) : '' ?></strong>!
                Pilih peran dan lengkapi data Anda.
            </p>

            <!-- Form — satu <form>, tiga langkah. Semua isian tetap terkirim
                 dalam SATU POST di akhir; `save_onboarding()` tidak berubah
                 sama sekali. Langkah hanya urusan tampilan. -->
            <form action="<?= base_url('Auth/save_onboarding') ?>" method="POST" enctype="multipart/form-data" id="onboardingForm"
                  @submit="if (langkah < totalLangkah) { $event.preventDefault(); }">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

                <!-- ===== INDIKATOR LANGKAH ===== -->
                <div style="margin-bottom:1.25rem;">
                    <div style="display:flex; justify-content:space-between; align-items:baseline; margin-bottom:0.5rem;">
                        <span class="auth-label" style="margin:0;" x-text="'Langkah ' + langkah + ' dari ' + totalLangkah"></span>
                        <span style="font-size:0.75rem; color:var(--auth-gray-400);" x-text="judulLangkah"></span>
                    </div>
                    <div style="height:4px; background:var(--auth-gray-200); border-radius:999px; overflow:hidden;">
                        <div style="height:100%; background:var(--auth-amber); border-radius:999px; transition:width 0.3s ease;"
                             :style="'width:' + (langkah / totalLangkah * 100) + '%'"></div>
                    </div>
                </div>

                <!-- ===== LANGKAH 1 — PERAN ===== -->
                <div class="auth-dynamic-form" :class="{ 'visible': langkah === 1 }" x-ref="langkah1">
                    <label class="auth-label">Pilih Peran Anda <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-role-grid">
                        <!-- Warga -->
                        <div class="auth-role-card" :class="{ 'selected': role === 'warga' }" @click="selectRole('warga')">
                            <!-- :required hanya di langkah 1. Radio ini
                                 disembunyikan secara visual; kalau ia tetap
                                 `required` saat langkahnya lewat, submit gagal
                                 diam-diam dengan "invalid form control is not
                                 focusable" — error di console, tanpa pesan
                                 apa pun ke user. -->
                            <input type="radio" name="role" value="warga" x-model="role" :required="langkah === 1">
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
                        <!-- Mahasiswa -->
                        <div class="auth-role-card" :class="{ 'selected': role === 'mahasiswa' }" @click="selectRole('mahasiswa')">
                            <input type="radio" name="role" value="mahasiswa" x-model="role">
                            <div class="auth-role-card__icon" style="color:#8b5cf6;"><i class="fa-solid fa-user-graduate"></i></div>
                            <div class="auth-role-card__title">Mahasiswa</div>
                            <div class="auth-role-card__desc">Magang / Penelitian</div>
                        </div>
                    </div>
                    <p style="font-size:0.8rem; color:var(--auth-gray-400); margin:0.75rem 0 0; line-height:1.5;">
                        Warga selesai dalam 2 langkah. Pengembang dan mahasiswa butuh satu langkah dokumen.
                    </p>
                </div>

                <!-- ===== LANGKAH 2 — DATA PRIBADI ===== -->
                <div class="auth-dynamic-form" :class="{ 'visible': langkah === 2 }" x-ref="langkah2">
                    <div class="auth-section-title">
                        <i class="fa-solid fa-user"></i> Data Pribadi
                    </div>

                    <!-- Username (Tampil di Forum) -->
                    <label class="auth-label" for="username">Username (Tampil di Forum) <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <input type="text" id="username" name="username" class="auth-input"
                               value="<?= $isi('username') ?>"
                               placeholder="Nama singkat tanpa spasi, cth: budi_santoso" :required="langkah === 2"
                               maxlength="30" pattern="^\S+$" oninput="this.value = this.value.replace(/\s/g, '').toLowerCase()">
                        <i class="fa-solid fa-at auth-input-icon"></i>
                    </div>

                    <!-- Nama Lengkap -->
                    <label class="auth-label" for="nama_lengkap">Nama Lengkap Sesuai Identitas <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <input type="text" id="nama_lengkap" name="nama_lengkap" class="auth-input"
                               value="<?= $isi('nama_lengkap') ?>"
                               placeholder="Masukkan nama sesuai KTP" :required="langkah === 2">
                        <i class="fa-solid fa-user auth-input-icon"></i>
                    </div>

                    <!-- NIK -->
                    <label class="auth-label" for="nik_identitas">No. Identitas (NIK) <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <input type="text" id="nik_identitas" name="nik_identitas" class="auth-input"
                               value="<?= $isi('nik_identitas') ?>"
                               placeholder="Masukkan 16 digit NIK" maxlength="16" pattern="[0-9]{16}"
                               :required="langkah === 2" inputmode="numeric"
                               oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        <i class="fa-solid fa-id-card auth-input-icon"></i>
                    </div>

                    <!-- Alamat -->
                    <label class="auth-label" for="alamat_domisili">Alamat Domisili <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <textarea id="alamat_domisili" name="alamat_domisili" class="auth-input"
                                  placeholder="Alamat lengkap beserta RT/RW, Desa/Kelurahan" rows="3"
                                  :required="langkah === 2" style="padding-left:2.75rem;"><?= $isi('alamat_domisili') ?></textarea>
                        <i class="fa-solid fa-map-location-dot auth-input-icon" style="top:1rem;transform:none;"></i>
                    </div>

                    <!-- Phone -->
                    <label class="auth-label" for="phone">No. Telepon / WhatsApp <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <input type="tel" id="phone" name="phone" class="auth-input"
                               value="<?= $isi('phone') ?>"
                               placeholder="08xxxxxxxxxx" :required="langkah === 2">
                        <i class="fa-solid fa-phone auth-input-icon"></i>
                    </div>

                <!-- ===== PASSWORD (akun Google) — masih bagian dari langkah 2,
                     supaya ikut divalidasi tombol Lanjut lewat x-ref langkah2 ===== -->
                <?php if (!empty($needs_password)): ?>
                <div style="margin-top:1.75rem;">
                    <div class="auth-section-title" style="color:#f59e0b;">
                        <i class="fa-solid fa-key"></i> Buat Password
                    </div>
                    <p style="font-size: 0.8rem; color: var(--auth-gray-400); margin: -0.5rem 0 1rem; line-height: 1.5;">
                        Karena Anda mendaftar melalui Google, buat password agar Anda juga bisa login dengan email dan password.
                    </p>

                    <label class="auth-label" for="ob_password">Password <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <input type="password" id="ob_password" name="password" class="auth-input"
                               placeholder="Buat password yang kuat" :required="langkah === 2" autocomplete="new-password"
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
                               placeholder="Ulangi password" :required="langkah === 2" autocomplete="new-password">
                        <i class="fa-solid fa-lock auth-input-icon"></i>
                        <button type="button" class="auth-password-toggle" onclick="togglePw('ob_password_confirm', this)" aria-label="Tampilkan password">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
                </div><!-- /langkah 2 -->

                <!-- ===== LANGKAH 3 — DOKUMEN PERAN ===== -->
                <!-- Dilewati untuk warga: totalLangkah menjadi 2, jadi tombol
                     simpan sudah muncul di langkah 2. -->
                <div class="auth-dynamic-form" :class="{ 'visible': langkah === 3 }" x-ref="langkah3">

                <?php if (!empty($old)): ?>
                <!-- Input file tidak bisa diisi ulang dari server — HTML
                     melarangnya demi keamanan. Daripada user mengira berkasnya
                     masih terpasang lalu gagal lagi, katakan saja. -->
                <p style="font-size:0.8rem; color:var(--auth-amber); margin:0 0 1rem; line-height:1.5;">
                    <i class="fa-solid fa-circle-info"></i>
                    Isian teks sudah dikembalikan, tetapi berkas unggahan perlu dipilih ulang.
                </p>
                <?php endif; ?>

                <!-- ===== PENGEMBANG FIELDS ===== -->
                <div class="auth-dynamic-form" :class="{ 'visible': role === 'pengembang' }">
                    <div class="auth-section-title" style="color:#f59e0b;">
                        <i class="fa-solid fa-building"></i> Data Perusahaan Pengembang
                    </div>

                    <label class="auth-label" for="nama_perusahaan">Nama Perusahaan <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <input type="text" id="nama_perusahaan" name="nama_perusahaan" class="auth-input"
                               value="<?= $isi('nama_perusahaan') ?>"
                               placeholder="PT / CV / Nama Usaha" :required="role === 'pengembang' && langkah === 3">
                        <i class="fa-solid fa-building auth-input-icon"></i>
                    </div>

                    <label class="auth-label" for="alamat_kantor">Alamat Kantor <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <textarea id="alamat_kantor" name="alamat_kantor" class="auth-input"
                                  placeholder="Alamat kantor perusahaan" rows="2"
                                  :required="role === 'pengembang' && langkah === 3" style="padding-left:2.75rem;"><?= $isi('alamat_kantor') ?></textarea>
                        <i class="fa-solid fa-location-dot auth-input-icon" style="top:1rem;transform:none;"></i>
                    </div>

                    <label class="auth-label" for="telp_kantor">No. Telepon Kantor</label>
                    <div class="auth-input-group">
                        <input type="tel" id="telp_kantor" name="telp_kantor" class="auth-input"
                               value="<?= $isi('telp_kantor') ?>"
                               placeholder="Nomor telepon kantor">
                        <i class="fa-solid fa-phone-office auth-input-icon"></i>
                    </div>

                    <label class="auth-label">Upload KTP Penanggung Jawab <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <input type="file" name="file_ktp" class="auth-file-input" accept=".pdf,.jpg,.jpeg,.png"
                               :required="role === 'pengembang' && langkah === 3">
                    </div>

                    <label class="auth-label">Upload SIUP / NIB / SIUJK <span style="color:var(--auth-red)">*</span></label>
                    <div class="auth-input-group">
                        <input type="file" name="file_siup" class="auth-file-input" accept=".pdf"
                               :required="role === 'pengembang' && langkah === 3">
                    </div>
                </div>

                <!-- ===== MAHASISWA FIELDS ===== -->
                <div class="auth-dynamic-form" :class="{ 'visible': role === 'mahasiswa' }">
                    <div class="auth-section-title" style="color:#8b5cf6;">
                        <i class="fa-solid fa-graduation-cap"></i> Data Mahasiswa
                    </div>

                    <!-- Dulu keduanya WAJIB, padahal berkasnya tidak pernah
                         ditampilkan di UI mana pun (lihat catatan pada
                         Auth::_handle_uploads) — sementara surat pengantar dan
                         proposal di pendaftaran KKN/Magang yang sesungguhnya
                         justru opsional. Gerbang wajib di depan, longgar di
                         belakang, untuk berkas yang tak dibaca siapa pun. -->
                    <p style="font-size:0.8rem; color:var(--auth-gray-400); margin:-0.25rem 0 1rem; line-height:1.5;">
                        Berkas boleh menyusul. Detail KKN/Magang diisi nanti pada formulir pendaftaran kemitraan.
                    </p>

                    <label class="auth-label">Upload Kartu Tanda Mahasiswa (KTM) <span style="color:var(--auth-gray-400)">(Opsional)</span></label>
                    <div class="auth-input-group">
                        <input type="file" name="file_ktm" class="auth-file-input" accept=".pdf,.jpg,.jpeg,.png">
                    </div>

                    <label class="auth-label">Upload Surat Keterangan Magang / Pengantar <span style="color:var(--auth-gray-400)">(Opsional)</span></label>
                    <div class="auth-input-group">
                        <input type="file" name="file_surat_magang" class="auth-file-input" accept=".pdf">
                    </div>
                </div>

                </div><!-- /langkah 3 -->

                <!-- ===== NAVIGASI ===== -->
                <div style="display:flex; gap:12px; margin-top:1.5rem;" x-cloak>
                    <button type="button" class="auth-btn" x-show="langkah > 1" @click="mundur()"
                            style="flex:0 0 auto; padding-left:1.25rem; padding-right:1.25rem; background:transparent; border:1.5px solid var(--auth-gray-200); color:var(--auth-gray-900);">
                        <i class="fa-solid fa-arrow-left"></i>
                        <span>Kembali</span>
                    </button>
                    <!-- Tombol Lanjut mati sampai peran dipilih: radio peran
                         disembunyikan secara visual, jadi gelembung validasi
                         bawaan browser tidak punya tempat untuk muncul. -->
                    <button type="button" class="auth-btn" x-show="langkah < totalLangkah" @click="lanjut()"
                            :disabled="langkah === 1 && role === ''" style="flex:1;">
                        <span>Lanjut</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </button>
                    <button type="submit" class="auth-btn" id="btnOnboarding" x-show="langkah === totalLangkah" style="flex:1;">
                        <span>Simpan &amp; Lanjutkan</span>
                        <i class="fa-solid fa-check"></i>
                        <div class="spinner"></div>
                    </button>
                </div>

                <p style="text-align:center; font-size:0.8rem; color:var(--auth-gray-400); margin:1.25rem 0 0;">
                    Ingin melanjutkan nanti?
                    <a href="<?= base_url('Auth/logout') ?>" style="color:var(--auth-amber); font-weight:600; text-decoration:none;">Keluar dari akun</a>
                </p>

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
function onboardingForm(peranTerpilih, langkahAwal) {
    return {
        // Peran yang tadi dipilih ikut pulang bersama pesan error, supaya blok
        // isian yang benar langsung terbuka lagi tanpa user memilih ulang.
        role: peranTerpilih || '',
        langkah: langkahAwal || 1,

        // Warga tidak punya blok dokumen sama sekali, jadi langkah 3 tidak
        // pernah ada untuknya — tombol simpan sudah muncul di langkah 2.
        get totalLangkah() {
            return this.role === 'warga' ? 2 : 3;
        },
        get judulLangkah() {
            return ['Pilih peran', 'Data pribadi', 'Dokumen peran'][this.langkah - 1] || '';
        },

        selectRole(r) {
            this.role = r;
        },

        lanjut() {
            // reportValidity() sekaligus memeriksa DAN memunculkan pesan bawaan
            // browser. every() berhenti di yang pertama tidak valid, jadi hanya
            // satu gelembung yang tampil — bukan lima sekaligus.
            const blok = this.$refs['langkah' + this.langkah];
            if (blok) {
                const isian = Array.from(blok.querySelectorAll('input, select, textarea'));
                if ( ! isian.every(el => el.reportValidity())) { return; }
            }
            if (this.langkah < this.totalLangkah) { this.langkah++; }
        },

        mundur() {
            if (this.langkah > 1) { this.langkah--; }
        }
    };
}

// Input file telanjang tidak mengabarkan apa pun setelah dipilih — tidak nama,
// tidak ukuran, apalagi batas 5 MB yang ditegakkan store_private_upload().
// Satu pendengar berdelegasi mengurus keenam input tanpa markup tambahan.
const BATAS_UNGGAH = 5 * 1024 * 1024;
document.addEventListener('change', function (e) {
    const input = e.target.closest ? e.target.closest('.auth-file-input') : null;
    if (!input) return;

    let ket = input.nextElementSibling;
    if (!ket || !ket.classList.contains('auth-file-note')) {
        ket = document.createElement('small');
        ket.className = 'auth-file-note';
        ket.style.cssText = 'display:block;margin-top:0.375rem;font-size:0.75rem;line-height:1.4;';
        input.insertAdjacentElement('afterend', ket);
    }

    const berkas = input.files && input.files[0];
    if (!berkas) { ket.textContent = ''; return; }

    const kebesaran = berkas.size > BATAS_UNGGAH;
    ket.textContent = berkas.name + ' — ' + (berkas.size / 1048576).toFixed(1) + ' MB'
        + (kebesaran ? ' — melebihi batas 5 MB, akan ditolak server' : '');
    ket.style.color = kebesaran ? 'var(--auth-red)' : 'var(--auth-green)';
});

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
