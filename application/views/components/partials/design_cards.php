<?php if(!empty($house_designs)): ?>
<style>
    .design-card-image { aspect-ratio: 1 / 1; object-fit: contain; }
</style>
<?php foreach($house_designs as $index => $item): ?>
<div class="min-w-0">
    <article class="group flex h-full flex-col overflow-hidden rounded-[26px] border border-[#d6e9eb] bg-white shadow-[0_8px_24px_rgba(15,42,48,0.08)] transition-all duration-300 hover:-translate-y-1 hover:border-[color:var(--portal-brand)] hover:shadow-[0_14px_30px_rgba(0,163,181,0.16)]">
        <a href="<?= base_url('panduan_desain/' . (int) $item['id']) ?>" class="relative block overflow-hidden border-b border-[#e5f0f1] bg-[#f7fbfb] p-1.5" aria-label="Lihat detail <?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>">
            <img src="https://apiternak.krsjawa3.com/<?= htmlspecialchars($item['path_image'], ENT_QUOTES, 'UTF-8') ?>" class="design-card-image block w-full rounded-[20px] transition-transform duration-500 group-hover:scale-[1.03]" alt="<?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
        </a>
        <div class="p-4">
            <p class="mb-1.5 text-[9px] font-bold uppercase tracking-[0.18em] text-[#6d969b]">Desain Rumah</p>
            <h4 class="line-clamp-2 text-base font-extrabold leading-6 text-[#123b42]">
                <?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>
            </h4>
            <div class="mt-3 flex items-center gap-2">
                <a href="<?= base_url('panduan_desain/' . (int) $item['id']) ?>" class="flex min-w-0 flex-1 items-center justify-center gap-2 rounded-xl bg-[#f1f5f5] px-3 py-3 text-sm font-extrabold text-[color:var(--portal-brand)] transition-all hover:bg-[color:var(--portal-brand)] hover:text-white">
                    <i class="fa-solid fa-eye text-sm"></i> Detail
                </a>
                <?php if (!empty($item['video']['link_video'])): ?>
                <a href="<?= htmlspecialchars($item['video']['link_video'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#ff0000] text-white shadow-[0_5px_12px_rgba(255,0,0,0.2)] transition-all hover:bg-[#cc0000] hover:shadow-none" aria-label="Lihat video <?= htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fa-brands fa-youtube text-xl"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </article>
</div>
<?php endforeach; ?>
<?php else: ?>
    <div class="col-span-full py-10 text-center text-[color:var(--portal-text-muted)]">Tidak ada data desain.</div>
<?php endif; ?>
