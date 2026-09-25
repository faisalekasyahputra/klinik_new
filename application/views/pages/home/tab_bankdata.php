<!-- Tab Content: Bank Data -->
<div class="py-4 sm:py-6 px-1 sm:px-2 font-outfit">
    <div class="mb-4">
        <div class="flex items-center gap-2">
            <i class="fa-solid fa-chart-pie text-[color:var(--portal-text)]"></i>
            <h2 class="text-sm font-bold uppercase tracking-widest text-[#2d6b75]">Bank Data</h2>
        </div>
        <p class="mt-2 max-w-2xl text-xs leading-relaxed" style="color:var(--portal-text-muted)">Pilih jenis informasi yang ingin dibuka. Dokumen buku data akan ditampilkan dalam pembaca halaman setelah dipilih.</p>
    </div>

    <?php
    $dokumen_bank = $dokumen_bank ?? [];
    $ada_buku = (bool) array_filter($dokumen_bank, fn($d) => $d->jenis === 'buku_data');
    $ada_stat = (bool) array_filter($dokumen_bank, fn($d) => $d->jenis === 'statistika');
    ?>
    <?php if ($dokumen_bank): ?>
    <div class="grid gap-4 sm:grid-cols-2 mb-4">
        <?php foreach ($dokumen_bank as $d): ?>
        <a href="<?= base_url('Dokumen/lihat/' . (int) $d->id) ?>" class="rounded-2xl p-5 text-left transition hover:-translate-y-0.5" style="background:var(--portal-bg-card);border:1px solid var(--portal-border)">
            <i class="fa-solid <?= $d->jenis === 'statistika' ? 'fa-chart-column' : 'fa-book-open' ?> text-xl" style="color:var(--teal)"></i>
            <div class="mt-3 text-[10px] font-black uppercase tracking-widest" style="color:var(--portal-text-muted)"><?= $d->jenis === 'statistika' ? 'Statistika' : 'Buku Data' ?></div>
            <h3 class="mt-1 text-base font-black" style="color:var(--portal-text)"><?= html_escape($d->judul) ?></h3>
            <?php if ($d->deskripsi): ?><p class="mt-1 text-xs" style="color:var(--portal-text-muted)"><?= html_escape($d->deskripsi) ?></p><?php endif; ?>
            <span class="mt-4 inline-flex items-center gap-2 text-xs font-black" style="color:var(--teal)">Buka dokumen <i class="fa-solid fa-arrow-right"></i></span>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="grid gap-4 sm:grid-cols-2">
        <?php if ( ! $ada_buku): ?>
        <a href="<?= base_url('Dokumen') ?>" class="rounded-2xl p-5 text-left transition hover:-translate-y-0.5" style="background:var(--portal-bg-card);border:1px solid var(--portal-border)">
            <i class="fa-solid fa-book-open text-xl" style="color:var(--teal)"></i>
            <h3 class="mt-3 text-base font-black" style="color:var(--portal-text)">Buku Data</h3>
            <p class="mt-1 text-xs" style="color:var(--portal-text-muted)">Publikasi data perumahan dan kawasan permukiman dalam format buku digital.</p>
            <span class="mt-4 inline-flex items-center gap-2 text-xs font-black" style="color:var(--teal)">Buka buku <i class="fa-solid fa-arrow-right"></i></span>
        </a>
        <?php endif; ?>
        <a href="<?= base_url('Statistika') ?>" class="rounded-2xl p-5 text-left transition hover:-translate-y-0.5" style="background:var(--portal-bg-card);border:1px solid var(--portal-border)">
            <i class="fa-solid fa-chart-column text-xl" style="color:var(--teal)"></i>
            <h3 class="mt-3 text-base font-black" style="color:var(--portal-text)">Statistik & Infografis</h3>
            <p class="mt-1 text-xs" style="color:var(--portal-text-muted)">Lihat ringkasan angka, grafik, dan informasi visual pembangunan perumahan.</p>
            <span class="mt-4 inline-flex items-center gap-2 text-xs font-black" style="color:var(--teal)">Lihat data <i class="fa-solid fa-arrow-right"></i></span>
        </a>
    </div>

</div>
