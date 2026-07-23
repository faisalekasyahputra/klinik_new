<div class="mx-auto max-w-4xl p-2 sm:p-6">
    <div class="mb-6 text-center">
        <span class="text-xs font-bold uppercase tracking-[.18em] text-[color:var(--portal-text-muted)]">Kemitraan</span>
        <h1 class="mt-2 text-2xl font-black text-[color:var(--portal-text)] sm:text-4xl">KKN dan Magang</h1>
        <p class="mt-2 text-sm text-[color:var(--portal-text-muted)]">Pilih informasi kemitraan yang ingin Anda lihat.</p>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <a href="<?= base_url('KemitraanPortal/kkn') ?>" data-tab-link data-tab-key="kemitraan_kkn" class="group flex min-h-[220px] items-center justify-center rounded-3xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] p-6 text-center shadow-sm transition hover:-translate-y-1 hover:border-[color:var(--portal-brand)]">
            <div><i class="fa-solid fa-people-group mb-4 text-4xl text-[color:var(--portal-brand)]"></i><h2 class="text-2xl font-black text-[color:var(--portal-text)]">KKN Tematik</h2><p class="mt-2 text-sm text-[color:var(--portal-text-muted)]">Persyaratan dan unduh juknis</p></div>
        </a>
        <a href="<?= base_url('KemitraanPortal/magang') ?>" data-tab-link data-tab-key="kemitraan_magang" class="group flex min-h-[220px] items-center justify-center rounded-3xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] p-6 text-center shadow-sm transition hover:-translate-y-1 hover:border-[color:var(--portal-brand)]">
            <div><i class="fa-solid fa-user-graduate mb-4 text-4xl text-[color:var(--portal-brand)]"></i><h2 class="text-2xl font-black text-[color:var(--portal-text)]">Magang/Kerja Praktik</h2><p class="mt-2 text-sm text-[color:var(--portal-text-muted)]">Lihat slot magang yang tersedia</p></div>
        </a>
    </div>
</div>
