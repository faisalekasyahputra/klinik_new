<?php
/**
 * Viewer PDF bergaya flipbook - permintaan user 23 Agt 2026, mengganti
 * kartu "Data Lainnya" (dulu "Segera Hadir") di tab Bank Data.
 *
 * Render halaman PDF ke <canvas> lewat PDF.js (CDN, pola sama seperti
 * Chart.js di statistika.php - CDN dipakai luas di halaman publik situs
 * ini, tidak ada larangan khususnya). Navigasi (halaman pertama/sebelum/
 * sesudah/terakhir + hitungan "x / y") dan bingkai "buku" murni CSS/JS
 * milik sendiri - BUKAN menyalin tampilan/branding produk viewer
 * flipbook pihak ketiga mana pun, cuma menerapkan pola UI generik yang
 * sama (toolbar navigasi + halaman di tengah berbayang) dengan token
 * warna --portal-* aplikasi ini sendiri.
 *
 * $pdf_url  : alamat berkas PDF yang ditampilkan.
 * $contoh   : TRUE selama dokumen resminya belum diterima dari user -
 *             menampilkan notice kuning supaya tidak disangka data final.
 */
?>
<div class="py-4 sm:py-6 px-1 sm:px-2 font-outfit" id="dokumen-viewer-root" data-pdf-url="<?= html_escape($pdf_url) ?>">
    <div class="mx-auto max-w-4xl">

        <div class="text-center mb-4">
            <div class="mx-auto mb-2 flex h-9 w-9 items-center justify-center rounded-xl bg-[color:var(--portal-btn-bg)] text-[color:var(--portal-brand)] shadow-sm rotate-3">
                <i class="fa-solid fa-book-open"></i>
            </div>
            <h1 class="text-xl sm:text-2xl font-black font-jakarta tracking-tighter text-[color:var(--portal-text)] mb-1">
                Bank Data <span class="text-[color:var(--portal-brand)]">Dokumen</span>
            </h1>
            <p class="text-sm text-[color:var(--portal-text-muted)] max-w-2xl mx-auto">
                Baca dokumen data perumahan dan kawasan permukiman langsung di sini.
            </p>
        </div>

        <?php if ( ! empty($contoh)): ?>
        <div class="mx-auto mb-4 max-w-2xl rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-800">
            <i class="fa-solid fa-triangle-exclamation mr-1.5" aria-hidden="true"></i>
            Dokumen di bawah ini <b>contoh tampilan</b> untuk menguji navigasi flipbook -
            menunggu berkas resmi. Watermark di tiap halaman menandainya jelas.
        </div>
        <?php endif; ?>

        <!-- Toolbar navigasi -->
        <div class="flex items-center justify-center gap-1.5 mb-3 rounded-2xl p-2"
             style="background-color: var(--portal-text); border: 1px solid var(--portal-border);">
            <button type="button" data-dok-nav="first" title="Halaman pertama"
                    class="dok-nav-btn"><i class="fa-solid fa-angles-left"></i></button>
            <button type="button" data-dok-nav="prev" title="Sebelumnya"
                    class="dok-nav-btn"><i class="fa-solid fa-angle-left"></i></button>

            <div class="mx-1 flex items-center gap-1.5 rounded-lg bg-white/10 px-2.5 py-1 text-xs font-bold text-white">
                <input type="text" inputmode="numeric" id="dok-page-input" value="1"
                       class="w-7 rounded bg-transparent text-center outline-none" aria-label="Nomor halaman">
                <span>/</span>
                <span id="dok-page-total">-</span>
            </div>

            <button type="button" data-dok-nav="next" title="Berikutnya"
                    class="dok-nav-btn"><i class="fa-solid fa-angle-right"></i></button>
            <button type="button" data-dok-nav="last" title="Halaman terakhir"
                    class="dok-nav-btn"><i class="fa-solid fa-angles-right"></i></button>

            <div class="relative ml-1">
                <button type="button" id="dok-more-btn" title="Opsi lain"
                        class="dok-nav-btn"><i class="fa-solid fa-ellipsis-vertical"></i></button>
                <div id="dok-more-menu"
                     class="absolute right-0 top-full z-30 mt-1.5 hidden w-44 overflow-hidden rounded-xl border py-1 text-xs shadow-lg"
                     style="background-color: var(--portal-bg-card); border-color: var(--portal-border);">
                    <a href="<?= html_escape($pdf_url) ?>" target="_blank" rel="noopener"
                       class="flex items-center gap-2 px-3 py-2 text-[color:var(--portal-text)] hover:bg-[color:var(--portal-btn-bg)]">
                        <i class="fa-solid fa-up-right-from-square w-4 text-center"></i> Buka di tab baru
                    </a>
                    <a href="<?= html_escape($pdf_url) ?>" download
                       class="flex items-center gap-2 px-3 py-2 text-[color:var(--portal-text)] hover:bg-[color:var(--portal-btn-bg)]">
                        <i class="fa-solid fa-download w-4 text-center"></i> Unduh PDF
                    </a>
                    <button type="button" data-dok-nav="fullscreen"
                            class="flex w-full items-center gap-2 px-3 py-2 text-left text-[color:var(--portal-text)] hover:bg-[color:var(--portal-btn-bg)]">
                        <i class="fa-solid fa-expand w-4 text-center"></i> Layar penuh
                    </button>
                </div>
            </div>
        </div>

        <!-- Panggung buku -->
        <div id="dok-stage"
             class="relative flex items-center justify-center rounded-2xl p-4 sm:p-8"
             style="background: radial-gradient(ellipse at center, rgba(0,0,0,0.06), rgba(0,0,0,0.14)); min-height: 60vh;">

            <button type="button" data-dok-nav="prev" title="Sebelumnya"
                    class="dok-side-arrow left-2 sm:left-4"><i class="fa-solid fa-chevron-left"></i></button>

            <div id="dok-page-wrap" class="relative max-w-full" style="filter: drop-shadow(0 12px 28px rgba(0,0,0,0.28));">
                <canvas id="dok-canvas" class="block max-w-full rounded-sm bg-white"></canvas>
                <div id="dok-loading" class="absolute inset-0 flex items-center justify-center rounded-sm bg-white text-[color:var(--portal-text-muted)]">
                    <i class="fa-solid fa-spinner fa-spin mr-2"></i> Memuat dokumen...
                </div>
            </div>

            <button type="button" data-dok-nav="next" title="Berikutnya"
                    class="dok-side-arrow right-2 sm:right-4"><i class="fa-solid fa-chevron-right"></i></button>
        </div>

    </div>
</div>

<style>
    .dok-nav-btn {
        display: flex; align-items: center; justify-content: center;
        width: 2rem; height: 2rem; border-radius: 0.6rem;
        color: rgba(255,255,255,0.85); background: transparent;
        transition: background-color .15s ease;
    }
    .dok-nav-btn:hover { background-color: rgba(255,255,255,0.14); color: #fff; }
    .dok-nav-btn:disabled { opacity: 0.35; pointer-events: none; }

    .dok-side-arrow {
        position: absolute; top: 50%; transform: translateY(-50%);
        display: flex; align-items: center; justify-content: center;
        width: 2.25rem; height: 2.25rem; border-radius: 9999px;
        background-color: var(--portal-bg-card); color: var(--portal-text);
        border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow);
        z-index: 10; transition: transform .15s ease, opacity .15s ease;
    }
    .dok-side-arrow:hover { transform: translateY(-50%) scale(1.08); }
    .dok-side-arrow:disabled { opacity: 0.3; pointer-events: none; }

    #dok-canvas.dok-flip { animation: dokFlip .28s ease; }
    @keyframes dokFlip {
        0%   { opacity: .35; transform: perspective(1200px) rotateY(6deg) scale(.985); }
        100% { opacity: 1;   transform: perspective(1200px) rotateY(0deg) scale(1); }
    }

    @media (max-width: 640px) {
        .dok-side-arrow { display: none; } /* layar sempit: cukup toolbar + swipe */
    }
</style>

<?php
/* PDF.js DI-HOST SENDIRI (bukan CDN) - permintaan teknis, bukan
   permintaan user: worker-nya (pdf.worker.min.js) sempat DIAM-DIAM
   MACET selamanya (render() tidak pernah resolve, tidak pernah reject,
   tanpa error di konsol) saat dimuat cross-origin dari cdn.jsdelivr.net
   di lingkungan pratinjau sandbox sesi ini - kemungkinan besar Worker
   lintas-origin dibatasi/di-polyfill secara berbeda di sana. Meng-host
   pdf.min.js + pdf.worker.min.js dari origin YANG SAMA dengan halaman
   ini menghilangkan sama sekali kemungkinan pembatasan lintas-origin
   itu, dan sekalian menghapus ketergantungan ke CDN luar untuk fitur
   yang sifatnya inti (bukan sekadar font/ikon dekoratif seperti
   FontAwesome/Google Fonts yang lain di aplikasi ini). */
?>
<script src="<?= base_url('assets/js/vendor/pdfjs/pdf.min.js') ?>"></script>
<script>
(function () {
    var root = document.getElementById('dokumen-viewer-root');
    if (!root) { return; }
    var pdfUrl = root.getAttribute('data-pdf-url');

    var canvas = document.getElementById('dok-canvas');
    var ctx = canvas.getContext('2d');
    var loadingEl = document.getElementById('dok-loading');
    var pageInput = document.getElementById('dok-page-input');
    var pageTotalEl = document.getElementById('dok-page-total');
    var stage = document.getElementById('dok-stage');

    var pdfDoc = null;
    var currentPage = 1;
    var rendering = false;
    var pendingPage = null;

    if (window['pdfjsLib']) {
        pdfjsLib.GlobalWorkerOptions.workerSrc = '<?= base_url('assets/js/vendor/pdfjs/pdf.worker.min.js') ?>';
    }

    function setNavState() {
        var atFirst = currentPage <= 1;
        var atLast = pdfDoc && currentPage >= pdfDoc.numPages;
        document.querySelectorAll('[data-dok-nav="first"], [data-dok-nav="prev"]').forEach(function (b) { b.disabled = atFirst; });
        document.querySelectorAll('[data-dok-nav="last"], [data-dok-nav="next"]').forEach(function (b) { b.disabled = atLast; });
        pageInput.value = currentPage;
    }

    function renderPage(num) {
        if (!pdfDoc) { return; }
        if (rendering) { pendingPage = num; return; }
        rendering = true;

        pdfDoc.getPage(num).then(function (page) {
            // Skalakan supaya lebar halaman mendekati lebar panggung, dibatasi
            // tinggi 78% viewport - konsisten di layar kecil maupun besar.
            var unscaled = page.getViewport({ scale: 1 });
            var maxW = Math.min(stage.clientWidth - 40, 760);
            var maxH = window.innerHeight * 0.72;
            var scale = Math.min(maxW / unscaled.width, maxH / unscaled.height);
            var viewport = page.getViewport({ scale: scale });

            canvas.width = viewport.width;
            canvas.height = viewport.height;

            var renderTask = page.render({ canvasContext: ctx, viewport: viewport });
            return renderTask.promise;
        }).then(function () {
            loadingEl.style.display = 'none';
            canvas.classList.remove('dok-flip');
            void canvas.offsetWidth; // restart animasi
            canvas.classList.add('dok-flip');
            currentPage = num;
            setNavState();
            rendering = false;
            if (pendingPage !== null) {
                var next = pendingPage; pendingPage = null; renderPage(next);
            }
        }).catch(function (err) {
            console.error('Gagal render halaman PDF:', err);
            rendering = false;
        });
    }

    function goTo(num) {
        if (!pdfDoc) { return; }
        num = Math.max(1, Math.min(pdfDoc.numPages, num));
        if (num === currentPage && canvas.width > 0) { return; }
        renderPage(num);
    }

    document.querySelectorAll('[data-dok-nav]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var action = btn.getAttribute('data-dok-nav');
            if (action === 'first') { goTo(1); }
            else if (action === 'prev') { goTo(currentPage - 1); }
            else if (action === 'next') { goTo(currentPage + 1); }
            else if (action === 'last') { goTo(pdfDoc ? pdfDoc.numPages : 1); }
            else if (action === 'fullscreen') {
                if (stage.requestFullscreen) { stage.requestFullscreen(); }
            }
        });
    });

    pageInput.addEventListener('change', function () {
        var n = parseInt(pageInput.value, 10);
        if (!isNaN(n)) { goTo(n); } else { pageInput.value = currentPage; }
    });

    // Menu "..." - buka/tutup, tutup kalau klik di luar.
    var moreBtn = document.getElementById('dok-more-btn');
    var moreMenu = document.getElementById('dok-more-menu');
    moreBtn.addEventListener('click', function (e) {
        e.stopPropagation();
        moreMenu.classList.toggle('hidden');
    });
    document.addEventListener('click', function (e) {
        if (!moreMenu.contains(e.target) && e.target !== moreBtn) { moreMenu.classList.add('hidden'); }
    });

    // Panah kiri/kanan keyboard - hanya saat viewer ini yang di layar.
    document.addEventListener('keydown', function (e) {
        if (!root.isConnected) { return; }
        var rect = root.getBoundingClientRect();
        var visible = rect.bottom > 0 && rect.top < window.innerHeight;
        if (!visible) { return; }
        if (e.key === 'ArrowLeft') { goTo(currentPage - 1); }
        else if (e.key === 'ArrowRight') { goTo(currentPage + 1); }
    });

    // Swipe sentuh sederhana untuk layar kecil.
    var touchStartX = null;
    stage.addEventListener('touchstart', function (e) { touchStartX = e.changedTouches[0].clientX; }, { passive: true });
    stage.addEventListener('touchend', function (e) {
        if (touchStartX === null) { return; }
        var dx = e.changedTouches[0].clientX - touchStartX;
        if (Math.abs(dx) > 40) { dx < 0 ? goTo(currentPage + 1) : goTo(currentPage - 1); }
        touchStartX = null;
    }, { passive: true });

    window.addEventListener('resize', function () {
        if (pdfDoc) { renderPage(currentPage); }
    });

    if (!pdfUrl || !window['pdfjsLib']) {
        loadingEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation mr-2"></i> Dokumen tidak dapat dimuat.';
        return;
    }

    pdfjsLib.getDocument(pdfUrl).promise.then(function (doc) {
        pdfDoc = doc;
        pageTotalEl.textContent = doc.numPages;
        renderPage(1);
    }).catch(function (err) {
        console.error('Gagal membuka PDF:', err);
        loadingEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation mr-2"></i> Dokumen tidak dapat dimuat.';
    });
})();
</script>
