<section class="w-full pt-10 pb-16 px-4 sm:px-6 lg:px-8 min-h-[60vh] font-outfit" data-detail-tidak-tersedia>
    <div class="max-w-xl mx-auto text-center rounded-2xl p-8" style="background:var(--portal-bg-card);border:1px solid var(--portal-border);box-shadow:var(--portal-shadow);">
        <i class="fa-solid fa-cloud-arrow-down text-3xl mb-4" style="color:var(--portal-brand);"></i>
        <h1 class="text-lg font-black mb-2" style="color:var(--portal-text);">Data perumahan sementara belum bisa dimuat</h1>
        <p class="text-sm mb-6" style="color:var(--portal-text-muted);">
            Sumber data SIKUMBANG sedang lambat atau tidak menjawab. Perumahannya tetap ada;
            silakan coba lagi dalam satu menit.
        </p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="<?= htmlspecialchars(current_url(), ENT_QUOTES, 'UTF-8') ?>" class="px-6 py-3 rounded-xl font-bold text-xs uppercase tracking-widest" style="background:var(--brand);color:var(--bg-body);">Coba lagi</a>
            <a href="<?= base_url('cari_rumah') ?>" class="px-6 py-3 rounded-xl font-bold text-xs uppercase tracking-widest" style="background:var(--portal-btn-bg);color:var(--portal-text);border:1px solid var(--portal-btn-border);">Kembali ke daftar</a>
        </div>
    </div>
</section>
