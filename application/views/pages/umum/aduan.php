<div class="theme-light py-4 sm:py-6 px-1 sm:px-2">
    <div class="mx-auto max-w-2xl text-center" data-aos="fade-down">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[color:var(--portal-btn-bg)] text-2xl text-[color:var(--portal-icon)]">
            <i class="fa-solid fa-comment-dots" aria-hidden="true"></i>
        </div>
        <p class="mt-5 text-xs font-black uppercase tracking-[0.18em] text-[color:var(--portal-brand)]">Layanan Pengaduan</p>
        <h1 id="aduan-title" class="mt-2 text-3xl font-black tracking-tight text-[color:var(--portal-text)]">Sampaikan Aduan Anda</h1>
        <p class="mx-auto mt-2 max-w-sm text-xs leading-relaxed text-[color:var(--portal-text-muted)]">Isi formulir di bawah dan pilih bidang tujuan yang sesuai, aduan Anda akan langsung diteruskan ke bidang tersebut.</p>
    </div>

    <?php if ($this->session->flashdata('success')): ?>
    <div class="mx-auto mt-6 max-w-2xl rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm font-semibold text-emerald-600" role="status">
        <i class="fa-solid fa-circle-check mr-1.5"></i> <?= $this->session->flashdata('success') ?>
    </div>
    <?php endif; ?>
    <?php if ($this->session->flashdata('error')): ?>
    <div class="mx-auto mt-6 max-w-2xl rounded-2xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm font-semibold text-red-500" role="alert">
        <i class="fa-solid fa-triangle-exclamation mr-1.5"></i> <?= $this->session->flashdata('error') ?>
    </div>
    <?php endif; ?>

    <form id="aduan-form" class="mx-auto mt-8 max-w-2xl space-y-4" action="<?= base_url('umum/simpan_aduan') ?>" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="aduan-nama" class="mb-1.5 block text-xs font-bold text-[color:var(--portal-text)]">Nama Lengkap</label>
                <input id="aduan-nama" name="nama" required maxlength="150" value="<?= htmlspecialchars($nama_default ?? '') ?>" placeholder="Nama Anda" class="w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] px-4 py-3 text-sm text-[color:var(--portal-text)] shadow-sm outline-none transition-colors focus:border-[color:var(--portal-brand)] focus:ring-2 focus:ring-[color:var(--portal-brand)]/15">
            </div>
            <div>
                <label for="aduan-email" class="mb-1.5 block text-xs font-bold text-[color:var(--portal-text)]">Alamat Email</label>
                <input id="aduan-email" name="email" type="email" required maxlength="100" value="<?= htmlspecialchars($email_default ?? '') ?>" placeholder="nama@email.com" class="w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] px-4 py-3 text-sm text-[color:var(--portal-text)] shadow-sm outline-none transition-colors focus:border-[color:var(--portal-brand)] focus:ring-2 focus:ring-[color:var(--portal-brand)]/15">
            </div>
        </div>

        <div>
            <label for="aduan-judul" class="mb-1.5 block text-xs font-bold text-[color:var(--portal-text)]">Judul</label>
            <input id="aduan-judul" name="judul" required maxlength="150" placeholder="Ringkasan singkat aduan Anda" class="w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] px-4 py-3 text-sm text-[color:var(--portal-text)] shadow-sm outline-none transition-colors focus:border-[color:var(--portal-brand)] focus:ring-2 focus:ring-[color:var(--portal-brand)]/15">
        </div>

        <div x-data="{
                open: false,
                value: '',
                label: 'Pilih Bidang Tujuan',
                opsi: [
                    { value: 'perumahan',  label: 'Bidang Perumahan' },
                    { value: 'kawasan',    label: 'Bidang Kawasan Permukiman' },
                    { value: 'pertanahan', label: 'Bidang Pertanahan' },
                    { value: 'pengembang', label: 'Bidang Pengembang' },
                    { value: 'umum',       label: 'Umum / Lainnya' },
                ]
             }">
            <label class="mb-1.5 block text-xs font-bold text-[color:var(--portal-text)]">Bidang Tujuan</label>
            <div class="relative">
                <input type="hidden" name="bidang" :value="value" required>
                <button type="button" @click="open = !open" @click.outside="open = false"
                        class="flex w-full items-center justify-between rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] px-4 py-3 text-left text-sm shadow-sm outline-none transition-colors focus:border-[color:var(--portal-brand)] focus:ring-2 focus:ring-[color:var(--portal-brand)]/15"
                        :style="!value && 'color: var(--portal-text-muted)' || 'color: var(--portal-text)'">
                    <span x-text="label"></span>
                    <i class="fa-solid fa-chevron-down text-xs transition-transform duration-200" :class="open && 'rotate-180'" style="color: var(--portal-text-muted);"></i>
                </button>
                <div x-show="open" x-cloak x-transition.origin.top
                     class="absolute z-20 mt-2 w-full overflow-hidden rounded-xl border shadow-lg"
                     style="background-color: var(--portal-bg-card); border-color: var(--portal-border);">
                    <template x-for="opt in opsi" :key="opt.value">
                        <button type="button" @click="value = opt.value; label = opt.label; open = false"
                                class="block w-full px-4 py-2.5 text-left text-sm transition-colors"
                                :class="value === opt.value ? 'font-bold' : 'font-medium'"
                                :style="'color: var(--portal-text); background-color: ' + (value === opt.value ? 'var(--portal-btn-bg)' : 'transparent')"
                                @mouseenter="$el.style.backgroundColor = 'var(--portal-btn-bg)'"
                                @mouseleave="$el.style.backgroundColor = value === opt.value ? 'var(--portal-btn-bg)' : 'transparent'"
                                x-text="opt.label"></button>
                    </template>
                </div>
            </div>
        </div>

        <div>
            <label for="aduan-pesan" class="mb-1.5 block text-xs font-bold text-[color:var(--portal-text)]">Pesan</label>
            <textarea id="aduan-pesan" name="pesan" required rows="6" maxlength="2000" placeholder="Tuliskan detail aduan atau pertanyaan Anda di sini." class="w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] px-4 py-3 text-sm text-[color:var(--portal-text)] shadow-sm outline-none transition-colors focus:border-[color:var(--portal-brand)] focus:ring-2 focus:ring-[color:var(--portal-brand)]/15 resize-y"></textarea>
        </div>

        <div>
            <label for="aduan-lampiran" class="mb-1.5 block text-xs font-bold text-[color:var(--portal-text)]">Lampiran <span class="font-normal text-[color:var(--portal-text-muted)]">(opsional)</span></label>
            <input id="aduan-lampiran" name="lampiran" type="file" accept=".jpg,.jpeg,.png,.pdf" class="w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] px-4 py-2.5 text-xs text-[color:var(--portal-text-muted)] shadow-sm file:mr-3 file:rounded-lg file:border-0 file:bg-[color:var(--portal-btn-bg)] file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-[color:var(--portal-icon)]">
            <p class="mt-1.5 text-[11px] text-[color:var(--portal-text-muted)]">Format JPG, PNG, atau PDF. Maksimal 5 MB.</p>
        </div>

        <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-full bg-[color:var(--portal-btn-bg)] px-5 py-3 text-sm font-black text-[color:var(--portal-icon)] transition hover:-translate-y-0.5 hover:brightness-95">Kirim Aduan <i class="fa-solid fa-paper-plane"></i></button>
    </form>

    <div class="mx-auto mt-6 max-w-2xl rounded-2xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] p-4 text-xs text-[color:var(--portal-text-muted)] shadow-sm">
        <i class="fa-solid fa-circle-info mr-2 text-[color:var(--portal-icon)]"></i> Punya pertanyaan umum seputar layanan? Cek dulu lewat tombol bantuan di pojok kanan bawah.
    </div>
</div>
