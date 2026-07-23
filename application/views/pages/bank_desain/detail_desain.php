<div class="design-detail px-1 pb-4 sm:px-2 sm:pb-6">
    <a href="<?= base_url('panduan_desain') ?>" class="mb-5 inline-flex items-center gap-2 text-sm font-semibold text-[color:var(--portal-text-muted)] transition-colors hover:text-[color:var(--portal-brand)]">
        <i class="fa-solid fa-arrow-left"></i> Kembali ke Bank Desain
    </a>

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1.4fr)_minmax(280px,0.6fr)]">
        <div class="overflow-hidden rounded-2xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] shadow-[var(--portal-shadow)]">
            <img src="https://apiternak.krsjawa3.com/<?= htmlspecialchars($design['path_image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($design['title'], ENT_QUOTES, 'UTF-8') ?>" class="block max-h-[70vh] w-full object-contain bg-[color:var(--portal-bg)]" />
        </div>

        <aside class="h-fit rounded-2xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] p-5 shadow-[var(--portal-shadow)]">
            <p class="mb-2 text-[11px] font-bold uppercase tracking-[0.2em] text-[color:var(--portal-text-muted)]">Bank Desain</p>
            <h1 class="text-2xl font-black leading-tight text-[color:var(--portal-text)]"><?= htmlspecialchars($design['title'], ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="mt-3 text-sm leading-6 text-[color:var(--portal-text-muted)]">Referensi desain rumah yang dapat digunakan sebagai bahan pertimbangan pembangunan rumah swadaya.</p>

            <div class="mt-6 grid gap-2">
                <a href="https://apiternak.krsjawa3.com/<?= htmlspecialchars($design['path_file'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-xl bg-[color:var(--portal-brand)] px-4 py-3 text-sm font-bold text-[color:var(--portal-bg)] transition-colors hover:opacity-85">
                    <i class="fa-solid fa-download"></i> Unduh Desain
                </a>
                <?php if (!empty($design['video']['link_video'])): ?>
                <a href="<?= htmlspecialchars($design['video']['link_video'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-xl border border-[#ff0000]/30 px-4 py-3 text-sm font-bold text-[#ff0000] transition-colors hover:bg-[#ff0000] hover:text-white">
                    <i class="fa-brands fa-youtube"></i> Lihat Video
                </a>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</div>
