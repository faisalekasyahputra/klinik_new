<section class="w-full px-4 py-8 font-outfit sm:px-6 lg:px-8" style="color:var(--portal-text)">
    <div class="mx-auto max-w-md">
        <nav class="mb-4 text-[10px] font-bold uppercase tracking-wider" style="color:var(--portal-text-muted)">
            <a href="<?= base_url('Pengembang/sertifikasi') ?>" data-tab-link data-tab-key="pengembang_list" class="hover:underline" style="color:var(--teal)">Sertifikasi Pengembang</a><span class="mx-2">/</span><span>Masuk</span>
        </nav>

        <div class="mb-5 text-center">
            <h1 class="text-xl font-black tracking-tight sm:text-2xl">Lanjutkan Pendaftaran SRP2</h1>
            <p class="mt-1 text-xs leading-relaxed" style="color:var(--portal-text-muted)">Masuk kalau sudah punya akun pengembang, atau daftar cepat kalau baru mulai - tanpa keluar dari alur ini.</p>
        </div>

        <div x-data="{ tab: 'masuk' }" class="rounded-2xl p-5 sm:p-6" style="background:var(--portal-bg-card);border:1px solid var(--portal-border);box-shadow:0 8px 24px rgba(0,80,95,.06)">

            <!-- Tab switcher -->
            <div class="mb-5 grid grid-cols-2 gap-1.5 rounded-xl p-1" style="background:var(--portal-bg)">
                <button type="button" @click="tab = 'masuk'" :class="tab === 'masuk' ? 'shadow-sm' : ''" :style="tab === 'masuk' ? 'background:var(--portal-bg-card);color:var(--portal-text)' : 'color:var(--portal-text-muted)'" class="rounded-lg py-2 text-xs font-extrabold uppercase tracking-wide transition-all">Masuk</button>
                <button type="button" @click="tab = 'daftar'" :class="tab === 'daftar' ? 'shadow-sm' : ''" :style="tab === 'daftar' ? 'background:var(--portal-bg-card);color:var(--portal-text)' : 'color:var(--portal-text-muted)'" class="rounded-lg py-2 text-xs font-extrabold uppercase tracking-wide transition-all">Daftar Cepat</button>
            </div>

            <!-- Panel: Masuk -->
            <form x-show="tab === 'masuk'" x-cloak action="<?= base_url('Auth/do_login') ?>" method="POST" class="space-y-3">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                <input type="hidden" name="redirect_to" value="Pengembang/masuk">
                <label class="block text-[11px] font-bold">Email atau Username <span style="color:#dc2626">*</span>
                    <input name="email" autocomplete="username" required placeholder="Email atau username" class="mt-1 block w-full rounded-lg px-3 py-2.5 text-xs font-normal outline-none" style="background:var(--portal-bg);border:1px solid var(--portal-border)">
                </label>
                <label class="block text-[11px] font-bold">Kata Sandi <span style="color:#dc2626">*</span>
                    <span class="relative mt-1 block">
                        <input id="srp2-masuk-password" name="password" type="password" autocomplete="current-password" required placeholder="Kata sandi" class="block w-full rounded-lg px-3 py-2.5 pr-10 text-xs font-normal outline-none" style="background:var(--portal-bg);border:1px solid var(--portal-border)">
                        <button type="button" data-password-toggle="srp2-masuk-password" class="absolute right-3 top-1/2 -translate-y-1/2" aria-label="Tampilkan kata sandi" style="color:var(--teal)"><i class="fa-solid fa-eye"></i></button>
                    </span>
                </label>
                <?php if (!empty($recaptcha_site_key)): ?><div class="g-recaptcha" data-sitekey="<?= htmlspecialchars($recaptcha_site_key, ENT_QUOTES, 'UTF-8') ?>"></div><?php endif; ?>
                <button type="submit" class="w-full rounded-lg py-2.5 text-[11px] font-extrabold uppercase" style="background:#d6fb00;color:#0a1a1f">Masuk & Lanjutkan</button>
                <p class="text-center text-[11px]" style="color:var(--portal-text-muted)"><a href="<?= base_url('Auth/forgot_password') ?>" style="color:var(--teal)">Lupa kata sandi?</a></p>
            </form>

            <!-- Panel: Daftar Cepat -->
            <div x-show="tab === 'daftar'" x-cloak class="space-y-4 text-center py-2">
                <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-full text-lg" style="background:color-mix(in srgb, var(--teal) 12%, transparent);color:var(--teal)"><i class="fa-solid fa-bolt"></i></div>
                <p class="text-xs leading-relaxed" style="color:var(--portal-text-muted)">Belum punya akun pengembang? Daftar cukup dengan nama perusahaan, email, dan kata sandi - dokumen dan data lainnya bisa dilengkapi belakangan lewat dashboard.</p>
                <a href="<?= base_url('Pengembang/daftar') ?>" class="inline-flex w-full items-center justify-center gap-2 rounded-lg py-2.5 text-[11px] font-extrabold uppercase tracking-wide" style="background:#d6fb00;color:#0a1a1f"><i class="fa-solid fa-arrow-right"></i> Daftar Cepat Sekarang</a>
            </div>
        </div>
    </div>
</section>

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
