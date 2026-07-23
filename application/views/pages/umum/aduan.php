<div class="theme-light min-h-full bg-[color:var(--portal-bg)] px-4 py-8 sm:px-6 lg:px-8">
    <section class="mx-auto max-w-2xl rounded-[2rem] border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] p-6 shadow-[var(--portal-shadow)] sm:p-10" aria-labelledby="aduan-title">
        <div class="text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[color:var(--portal-btn-bg)] text-2xl text-[color:var(--portal-icon)]">
                <i class="fa-solid fa-comment-dots" aria-hidden="true"></i>
            </div>
            <p class="mt-5 text-xs font-black uppercase tracking-[0.18em] text-[color:var(--portal-brand)]">Layanan Pengaduan</p>
            <h1 id="aduan-title" class="mt-2 text-3xl font-black tracking-tight text-[color:var(--portal-text)]">Sampaikan Aduan Anda</h1>
            <p class="mx-auto mt-3 max-w-lg text-sm leading-relaxed text-[color:var(--portal-text-muted)]">Tuliskan aduan atau pertanyaan Anda satu kali di sini. Sistem akan mengarahkan otomatis ke bidang yang paling sesuai berdasarkan isi pesan — kalau tidak cocok dengan bidang mana pun, aduan diteruskan ke admin terkait.</p>
        </div>

        <form id="aduan-form" class="mt-8 space-y-4">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="aduan-nama" class="mb-1.5 block text-xs font-bold text-[color:var(--portal-text)]">Nama Lengkap</label>
                    <input id="aduan-nama" name="nama" required maxlength="150" value="<?= htmlspecialchars($nama_default ?? '') ?>" placeholder="Nama Anda" class="w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg)] px-4 py-3 text-sm text-[color:var(--portal-text)] outline-none focus:border-[color:var(--portal-brand)]">
                </div>
                <div>
                    <label for="aduan-email" class="mb-1.5 block text-xs font-bold text-[color:var(--portal-text)]">Alamat Email</label>
                    <input id="aduan-email" name="email" type="email" required maxlength="100" value="<?= htmlspecialchars($email_default ?? '') ?>" placeholder="nama@email.com" class="w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg)] px-4 py-3 text-sm text-[color:var(--portal-text)] outline-none focus:border-[color:var(--portal-brand)]">
                </div>
            </div>

            <div>
                <label for="aduan-judul" class="mb-1.5 block text-xs font-bold text-[color:var(--portal-text)]">Judul</label>
                <input id="aduan-judul" name="judul" required maxlength="150" placeholder="Ringkasan singkat aduan Anda" class="w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg)] px-4 py-3 text-sm text-[color:var(--portal-text)] outline-none focus:border-[color:var(--portal-brand)]">
            </div>

            <div>
                <label for="aduan-pesan" class="mb-1.5 block text-xs font-bold text-[color:var(--portal-text)]">Pesan</label>
                <textarea id="aduan-pesan" name="pesan" required rows="6" maxlength="2000" placeholder="Tuliskan detail aduan atau pertanyaan Anda di sini. Sebutkan bidang terkait (mis. perumahan, kawasan permukiman, pertanahan) supaya lebih cepat diarahkan." class="w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg)] px-4 py-3 text-sm text-[color:var(--portal-text)] outline-none focus:border-[color:var(--portal-brand)] resize-y"></textarea>
                <p class="mt-1.5 text-[11px] text-[color:var(--portal-text-muted)]"><i class="fa-solid fa-circle-info mr-1"></i>Bidang tujuan dideteksi otomatis dari kata kunci pada pesan — tidak perlu memilih bidang secara manual.</p>
            </div>

            <div>
                <label for="aduan-lampiran" class="mb-1.5 block text-xs font-bold text-[color:var(--portal-text)]">Lampiran <span class="font-normal text-[color:var(--portal-text-muted)]">(opsional)</span></label>
                <input id="aduan-lampiran" name="lampiran" type="file" accept=".jpg,.jpeg,.png,.pdf" class="w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg)] px-4 py-2.5 text-xs text-[color:var(--portal-text-muted)] file:mr-3 file:rounded-lg file:border-0 file:bg-[color:var(--portal-btn-bg)] file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-[color:var(--portal-icon)]">
                <p class="mt-1.5 text-[11px] text-[color:var(--portal-text-muted)]">Format JPG, PNG, atau PDF. Maksimal 5 MB.</p>
            </div>

            <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-full bg-[color:var(--portal-btn-bg)] px-5 py-3 text-sm font-black text-[color:var(--portal-icon)] transition hover:-translate-y-0.5 hover:brightness-95">Kirim Aduan <i class="fa-solid fa-paper-plane"></i></button>
            <p id="aduan-result" class="hidden rounded-xl border border-[color:var(--portal-border)] px-4 py-3 text-sm font-semibold text-[color:var(--portal-text)]" role="status"></p>
        </form>

        <div class="mt-6 rounded-2xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg)] p-4 text-sm text-[color:var(--portal-text-muted)]">
            <i class="fa-solid fa-circle-info mr-2 text-[color:var(--portal-icon)]"></i> Butuh respons lebih cepat? Pakai tombol bantuan di pojok kanan bawah untuk WhatsApp langsung atau live chat.
        </div>
    </section>
</div>

<script>
document.getElementById('aduan-form')?.addEventListener('submit', function (event) {
    event.preventDefault();
    const result = document.getElementById('aduan-result');
    result.classList.remove('hidden');
    result.textContent = 'Pengiriman otomatis untuk fitur ini masih disiapkan. Sementara itu, silakan pakai tombol bantuan di pojok kanan bawah (WhatsApp atau live chat) supaya aduan Anda langsung ditindaklanjuti.';
});
</script>
