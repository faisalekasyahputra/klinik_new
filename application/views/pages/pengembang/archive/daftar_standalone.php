<?php
/**
 * DIARSIPKAN (2026-07) - halaman daftar cepat standalone ini digantikan wizard
 * satu halaman di pages/pengembang/syarat.php (step "Akun", tab Daftar Cepat).
 * Pengembang::daftar() sekarang cuma redirect ke Pengembang/syarat. Disimpan
 * sebagai referensi, tidak lagi dirender controller manapun.
 */
$verification_pending = $this->session->userdata('srp2_verify_pending') === TRUE; ?>
<section class="w-full px-4 py-8 font-outfit sm:px-6 lg:px-8" style="color:var(--portal-text)">
    <div class="mx-auto max-w-3xl">
        <nav class="mb-4 text-[10px] font-bold uppercase tracking-wider" style="color:var(--portal-text-muted)">
            <a href="<?= base_url('Pengembang/syarat') ?>" style="color:var(--teal)">Syarat SRP2</a><span class="mx-2">/</span><span>Daftar</span>
        </nav>

        <?php if ($verification_pending): ?>
            <div class="rounded-2xl p-8 text-center sm:p-10" style="background:var(--portal-bg-card);border:1px solid var(--portal-border)">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full text-xl" style="background:color-mix(in srgb, var(--teal) 12%, transparent);color:var(--teal)"><i class="fa-solid fa-envelope-circle-check"></i></div>
                <h1 class="mt-4 text-xl font-black sm:text-2xl">Memverifikasi Email</h1>
                <p class="mx-auto mt-2 max-w-md text-xs leading-relaxed" style="color:var(--portal-text-muted)">Email Anda sedang diverifikasi. Setelah selesai, Anda langsung masuk ke halaman unggah dokumen SRP2.</p>
                <div class="mx-auto mt-6 h-1.5 max-w-xs overflow-hidden rounded-full" style="background:var(--portal-border)"><div class="h-full w-full origin-left animate-pulse rounded-full" style="background:var(--teal)"></div></div>
                <p class="mt-3 text-[11px] font-bold" style="color:var(--teal)">Verifikasi simulasi sedang diproses…</p>
            </div>
        <?php else: ?>
            <form action="<?= base_url('Auth/do_register') ?>" method="post" class="rounded-2xl p-5 sm:p-6" style="background:var(--portal-bg-card);border:1px solid var(--portal-border)">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                <input type="hidden" name="srp2_pengembang" value="1">
                <h1 class="text-xl font-black sm:text-2xl">Buat Akun Pengembang</h1>
                <p class="mt-1 text-xs" style="color:var(--portal-text-muted)">Verifikasi email, lalu lanjutkan pengajuan dan unggah dokumen dari dashboard.</p>
                <p class="mt-3 text-[11px]" style="color:var(--portal-text-muted)"><span style="color:#dc2626">*</span> Wajib diisi</p>

                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                    <label class="text-[11px] font-bold">Nama perusahaan <span style="color:#dc2626">*</span><input name="nama_perusahaan" autocomplete="organization" required placeholder="Nama perusahaan" class="mt-1 block w-full rounded-lg px-3 py-2.5 text-xs font-normal outline-none" style="background:var(--portal-bg);border:1px solid var(--portal-border)"></label>
                    <label class="text-[11px] font-bold">Email perusahaan <span style="color:#dc2626">*</span><input name="email" type="email" autocomplete="email" required placeholder="Email perusahaan" class="mt-1 block w-full rounded-lg px-3 py-2.5 text-xs font-normal outline-none" style="background:var(--portal-bg);border:1px solid var(--portal-border)"></label>
                    <label class="text-[11px] font-bold">Kata sandi <span style="color:#dc2626">*</span><span class="relative mt-1 block"><input id="srp2-password" name="password" type="password" autocomplete="new-password" required minlength="8" aria-describedby="aturan-sandi" placeholder="Kata sandi" class="block w-full rounded-lg px-3 py-2.5 pr-10 text-xs font-normal outline-none" style="background:var(--portal-bg);border:1px solid var(--portal-border)"><button type="button" data-password-toggle="srp2-password" class="absolute right-3 top-1/2 -translate-y-1/2" aria-label="Tampilkan kata sandi" style="color:var(--teal)"><i class="fa-solid fa-eye"></i></button></span></label>
                    <label class="text-[11px] font-bold">Konfirmasi kata sandi <span style="color:#dc2626">*</span><span class="relative mt-1 block"><input id="srp2-password-confirm" name="password_confirm" type="password" autocomplete="new-password" required minlength="8" placeholder="Konfirmasi kata sandi" class="block w-full rounded-lg px-3 py-2.5 pr-10 text-xs font-normal outline-none" style="background:var(--portal-bg);border:1px solid var(--portal-border)"><button type="button" data-password-toggle="srp2-password-confirm" class="absolute right-3 top-1/2 -translate-y-1/2" aria-label="Tampilkan konfirmasi kata sandi" style="color:var(--teal)"><i class="fa-solid fa-eye"></i></button></span></label>
                </div>
                <p id="aturan-sandi" class="mt-2 text-[11px]" style="color:var(--portal-text-muted)"><i class="fa-solid fa-circle-info mr-1" style="color:var(--teal)"></i>Minimal 8 karakter, huruf besar, angka, dan simbol. Contoh: <strong style="color:var(--portal-text)">KlinikPKP#2026</strong></p>
                <?php if (!empty($recaptcha_site_key)): ?><div class="mt-4 g-recaptcha" data-sitekey="<?= htmlspecialchars($recaptcha_site_key, ENT_QUOTES, 'UTF-8') ?>"></div><?php endif; ?>
                <div class="mt-5 flex flex-wrap items-center justify-between gap-3"><a href="<?= base_url('Auth/login?next=akun') ?>" class="text-xs font-bold" style="color:var(--teal)">Sudah punya akun? Masuk</a><button class="rounded-lg px-4 py-2.5 text-[11px] font-extrabold uppercase" style="background:#d6fb00;color:#0a1a1f">Daftar Akun Pengembang</button></div>
            </form>
        <?php endif; ?>
    </div>
</section>

<?php if ($verification_pending): ?>
    <script>
        window.setTimeout(function () {
            fetch('<?= base_url('Auth/do_verify_email') ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: '<?= rawurlencode($this->security->get_csrf_token_name()) ?>=<?= rawurlencode($this->security->get_csrf_hash()) ?>'
            }).then(function () { window.location.href = '<?= base_url('Auth/lanjutkan') ?>'; });
        }, 1200);
    </script>
<?php else: ?>
    <script>
        document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                var input = document.getElementById(button.dataset.passwordToggle), icon = button.querySelector('i'), visible = input.type === 'text';
                input.type = visible ? 'password' : 'text';
                icon.className = visible ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
                button.setAttribute('aria-label', visible ? 'Tampilkan kata sandi' : 'Sembunyikan kata sandi');
            });
        });
    </script>
    <?php if (!empty($recaptcha_site_key)): ?><script src="https://www.google.com/recaptcha/api.js" async defer></script><?php endif; ?>
<?php endif; ?>
