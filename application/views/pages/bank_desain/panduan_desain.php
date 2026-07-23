<div class="design-catalog px-1 pb-4 sm:px-2 sm:pb-6 relative font-outfit">
    <!-- Section Header -->
    <div class="flex flex-col sm:flex-row items-start sm:items-end justify-between mb-6 gap-4">
        <div>
            <h2 class="text-3xl sm:text-4xl font-black text-[color:var(--portal-text)] tracking-tighter font-jakarta"><span style="color: var(--portal-brand);">Bank</span> Desain</h2>
            <p class="text-[color:var(--portal-text-muted)] text-sm mt-2">Temukan desain rumah yang sesuai kebutuhan Anda</p>
        </div>
    </div>

    <div id="designs-container" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">
        <?php if (empty($is_ajax_load)): ?>
            <!-- Direct load only; SPA navigation already has a global skeleton. -->
            <?php for ($i = 0; $i < 5; $i++): ?>
                <div><div class="skeleton h-80"></div></div>
            <?php endfor; ?>
        <?php endif; ?>
    </div>
</div>

<style>
    .design-catalog .card-content-wrapper {
        transform: translateY(0);
    }
    .design-catalog .card-details {
        height: auto;
        opacity: 1;
    }
    .design-catalog #designs-container > div {
        min-width: 0;
    }
</style>

<script>
// Halaman mandiri: tampilkan katalog penuh dalam grid responsif.
document.addEventListener('DOMContentLoaded', function() {
    $.ajax({
        url: '<?= base_url("ajax_house_designs") ?>',
        success: function(html) {
            $('#designs-container').html(html);
        },
        error: function() {
            $('#designs-container').html('<p class="col-span-full text-center text-[#5a7a80] py-8">Gagal memuat data desain rumah.</p>');
        }
    });
});
</script>
