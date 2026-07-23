<div class="theme-light min-h-full bg-[color:var(--portal-bg)] px-4 py-8 sm:px-6 lg:px-8">
    <section class="mx-auto max-w-2xl rounded-[2rem] border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] p-6 shadow-[var(--portal-shadow)] sm:p-10" aria-labelledby="status-title">
        <div class="text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[color:var(--portal-btn-bg)] text-2xl text-[color:var(--portal-icon)]">
                <i class="fa-solid fa-ticket" aria-hidden="true"></i>
            </div>
            <p class="mt-5 text-xs font-black uppercase tracking-[0.18em] text-[color:var(--portal-brand)]">Layanan Pengajuan</p>
            <h1 id="status-title" class="mt-2 text-3xl font-black tracking-tight text-[color:var(--portal-text)]">Cek Status Pengajuan</h1>
            <p class="mx-auto mt-3 max-w-lg text-sm leading-relaxed text-[color:var(--portal-text-muted)]">Masukkan tiket PKP atau SRP2-REG dan empat digit terakhir NIK untuk melihat status terbaru tanpa login.</p>
        </div>

        <form id="status-ticket-form" class="mt-8 space-y-4">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
            <div>
                <label for="status-ticket-code" class="mb-1.5 block text-xs font-bold text-[color:var(--portal-text)]">Nomor tiket</label>
                <input id="status-ticket-code" name="ticket_code" required pattern="(PKP-[A-Z0-9]{6}|SRP2-REG-[0-9]{5})" maxlength="14" class="w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg)] px-4 py-3 font-mono text-sm uppercase text-[color:var(--portal-text)] outline-none focus:border-[color:var(--portal-brand)]" placeholder="PKP-XXXXXX atau SRP2-REG-00001">
            </div>
            <div>
                <label for="status-nik-suffix" class="mb-1.5 block text-xs font-bold text-[color:var(--portal-text)]">Empat digit terakhir NIK</label>
                <input id="status-nik-suffix" name="nik_suffix" required inputmode="numeric" pattern="[0-9]{4}" maxlength="4" class="w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg)] px-4 py-3 text-sm text-[color:var(--portal-text)] outline-none focus:border-[color:var(--portal-brand)]" placeholder="Contoh: 0001">
            </div>
            <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-full bg-[color:var(--portal-btn-bg)] px-5 py-3 text-sm font-black text-[color:var(--portal-icon)] transition hover:-translate-y-0.5 hover:brightness-95">Lihat Status <i class="fa-solid fa-arrow-right"></i></button>
            <p id="status-ticket-result" class="hidden rounded-xl border border-[color:var(--portal-border)] px-4 py-3 text-sm font-semibold" role="status"></p>
        </form>

        <div class="mt-6 rounded-2xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg)] p-4 text-sm text-[color:var(--portal-text-muted)]">
            <i class="fa-solid fa-circle-info mr-2 text-[color:var(--portal-icon)]"></i> Belum punya tiket? Selesaikan diagnosa pembiayaan terlebih dahulu.
        </div>
    </section>
</div>

<script>
document.getElementById('status-ticket-form')?.addEventListener('submit', async function (event) {
    event.preventDefault();
    const result = document.getElementById('status-ticket-result');
    result.classList.remove('hidden');
    result.textContent = 'Memeriksa status...';
    try {
        const response = await fetch('<?= base_url('solusi_pembiayaan/cek-tiket') ?>', {
            method: 'POST',
            body: new FormData(this),
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        });
        const data = await response.json();
        result.textContent = data.status === 'success' ? data.status_pengajuan + ' · Diperbarui ' + data.updated_at : data.message;
    } catch (error) {
        result.textContent = 'Status belum dapat diperiksa. Silakan coba lagi.';
    }
});
</script>
