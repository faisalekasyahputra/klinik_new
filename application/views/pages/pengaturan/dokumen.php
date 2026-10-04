<?php
/**
 * Panel Dokumen SRP2 di dasbor pengembang.
 *
 * Dirapikan 4 Okt 2026: tiap kartu kini punya label status, THUMBNAIL berkas yang tersimpan
 * (gambar langsung; PDF digambar halaman pertamanya dengan PDF.js lokal, sama dengan pembaca
 * Buku Data), dan tombol pilih berkas bergaya alih-alih "Choose File / No file chosen" bawaan.
 * Klik thumbnail atau "Lihat Berkas" membuka penampil berkas bersama (modal), bukan tab baru.
 */
$locked = in_array($srp2['status'], ['Pending', 'Diterima'], TRUE);
$count = count(array_intersect(array_keys($files), array_keys($dokumen)));
$card = 'kartu-admin isi-kartu flex flex-col';
$url_berkas = fn($key) => base_url('Pengembang/lihat_dokumen_saya/' . (int) $srp2['pengajuan_id'] . '/' . $key);
$jenis_berkas = function ($nama) {
    $ext = strtolower(pathinfo((string) $nama, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png'], TRUE) ? 'gambar' : ($ext === 'pdf' ? 'pdf' : 'lain');
};
?>
<style>
    /* Tombol pilih berkas: varian file: Tailwind tidak ada di CSS admin statis, jadi ditulis di sini. */
    #srp2-dashboard-documents .isian-berkas { width: 100%; font-size: .8125rem; color: #6b7280; }
    .dark #srp2-dashboard-documents .isian-berkas { color: #9fb4b8; }
    #srp2-dashboard-documents .isian-berkas::file-selector-button {
        margin-right: .75rem; border: 1px solid #d1d5db; border-radius: .5rem; padding: .375rem .875rem;
        background: #fff; color: #374151; font-weight: 700; font-size: .8125rem; cursor: pointer;
    }
    .dark #srp2-dashboard-documents .isian-berkas::file-selector-button { border-color: rgba(255,255,255,.15); background: transparent; color: #e5e7eb; }
    #srp2-dashboard-documents .thumb-berkas { aspect-ratio: 4 / 3; }
</style>
<section id="srp2-dashboard-documents" class="tumpuk-bagian text-gray-900 dark:text-white">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h1 class="text-2xl font-black">Dokumen SRP2</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-brand-muted"><?= $count ?> / <?= count($dokumen) ?> dokumen tersimpan · Status: <?= html_escape($srp2['status']) ?></p>
        </div>
        <a href="<?= base_url('akun/profil') ?>" class="tombol-kedua">Lengkapi Data Perusahaan</a>
    </div>

    <?php if (!empty($srp2['catatan_admin'])): ?>
        <div class="kartu-admin isi-kartu border-l-4 border-l-amber-500"><h2 class="font-bold">Catatan Admin</h2><p class="mt-2 text-sm"><?= nl2br(html_escape($srp2['catatan_admin'])) ?></p></div>
    <?php endif; ?>

    <div class="kartu-admin isi-kartu flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm"><?= $locked ? 'Pengajuan sedang ditinjau atau sudah diterima. Dokumen dapat dilihat; perubahan menunggu pengajuan dibuka kembali oleh admin.' : 'Unggah atau ganti berkas satu per satu. Berkas yang tersimpan juga langsung tersedia di wizard pendaftaran.' ?></p>
            <p class="mt-1 text-xs text-gray-500 dark:text-brand-muted">PDF, JPG, atau PNG, maksimal 2 MB per dokumen.</p>
        </div>
        <a href="https://s.id/lampiran_SRPP" target="_blank" rel="noopener noreferrer" class="tombol-kedua"><i class="ph ph-download-simple" aria-hidden="true"></i><span>Unduh Template Dokumen</span></a>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
    <?php foreach ($dokumen as $key => $label): $ada = isset($files[$key]); $jenis = $ada ? $jenis_berkas($files[$key]) : NULL; ?>
        <div class="<?= $card ?>" data-kartu-dokumen="<?= html_escape($key) ?>">
            <div class="flex items-start justify-between gap-2">
                <h2 class="text-sm font-bold leading-tight"><?= html_escape($label) ?></h2>
                <?php if ($ada): ?>
                    <span class="shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">Tersimpan</span>
                <?php else: ?>
                    <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-[11px] font-bold text-gray-500 dark:bg-white/5 dark:text-brand-muted">Belum diunggah</span>
                <?php endif; ?>
            </div>
            <?php if (!empty($keterangan[$key])): ?><p class="mt-1 text-xs text-gray-500 dark:text-brand-muted"><?= html_escape($keterangan[$key]) ?></p><?php endif; ?>

            <?php if ($ada): ?>
                <a href="<?= $url_berkas($key) ?>" data-file-view data-file-title="<?= html_escape($label) ?>"
                   class="thumb-berkas mt-3 block overflow-hidden rounded-xl border border-gray-200 bg-gray-50 dark:border-white/10 dark:bg-black/20" aria-label="Lihat <?= html_escape($label) ?>">
                    <?php if ($jenis === 'gambar'): ?>
                        <img src="<?= $url_berkas($key) ?>" alt="" class="h-full w-full object-cover">
                    <?php elseif ($jenis === 'pdf'): ?>
                        <canvas data-thumb-pdf="<?= $url_berkas($key) ?>" class="h-full w-full object-contain"></canvas>
                    <?php else: ?>
                        <span class="flex h-full w-full items-center justify-center text-3xl text-gray-400"><i class="ph ph-file" aria-hidden="true"></i></span>
                    <?php endif; ?>
                </a>
                <div class="mt-2 flex items-center justify-between gap-2">
                    <p class="min-w-0 truncate text-xs text-gray-500 dark:text-brand-muted" title="<?= html_escape($files[$key]) ?>"><?= html_escape($files[$key]) ?></p>
                    <a href="<?= $url_berkas($key) ?>" data-file-view data-file-title="<?= html_escape($label) ?>" class="tombol-aksi shrink-0"><i class="ph ph-eye" aria-hidden="true"></i><span>Lihat Berkas</span></a>
                </div>
            <?php else: ?>
                <div class="thumb-berkas mt-3 flex items-center justify-center rounded-xl border border-dashed border-gray-300 text-3xl text-gray-300 dark:border-white/10 dark:text-white/20">
                    <i class="ph ph-file-arrow-up" aria-hidden="true"></i>
                </div>
            <?php endif; ?>

            <?php if (!$locked): ?>
                <form action="<?= base_url('Pengembang/simpan_dokumen/' . (int) $srp2['pengajuan_id']) ?>" method="post" enctype="multipart/form-data" class="mt-auto space-y-2 pt-3">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                    <input type="hidden" name="return_to" value="dashboard">
                    <label for="upload_<?= $key ?>" class="block text-xs font-bold text-gray-500 dark:text-brand-muted"><?= $ada ? 'Pilih Berkas Pengganti' : 'Pilih Berkas' ?></label>
                    <input id="upload_<?= $key ?>" name="<?= $key ?>" type="file" accept=".pdf,.jpg,.jpeg,.png" required class="isian-berkas">
                    <button type="submit" class="tombol-utama w-full"><i class="ph <?= $ada ? 'ph-arrows-clockwise' : 'ph-upload-simple' ?>" aria-hidden="true"></i><span><?= $ada ? 'Ganti Berkas' : 'Simpan Berkas' ?></span></button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-4">
        <a href="<?= base_url('akun') ?>" class="tombol-kedua"><span class="panah-sorot" aria-hidden="true"><i class="ph ph-caret-left"></i></span><span>Kembali ke Status Pengajuan</span></a>
        <?php if (!$locked): ?>
            <form action="<?= base_url('Pengembang/kirim_pengajuan/' . (int) $srp2['pengajuan_id']) ?>" method="post">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                <input type="hidden" name="return_to" value="dashboard">
                <button type="submit" <?= $count < count($dokumen) ? 'disabled' : '' ?> class="tombol-utama"><i class="ph ph-paper-plane-tilt" aria-hidden="true"></i><span>Kirim Pengajuan</span></button>
            </form>
        <?php endif; ?>
    </div>
</section>
<script>
/* Thumbnail PDF: halaman pertama digambar ke kanvas dengan PDF.js lokal (import dinamis, supaya jalan
   juga saat konten dimuat ulang lewat navigasi progresif admin). isEvalSupported:false sama dengan
   pembaca Buku Data (mitigasi CVE-2024-4367). Gagal = kotak tetap kosong, tautannya tetap berfungsi. */
(function () {
    var kanvas = document.querySelectorAll('#srp2-dashboard-documents canvas[data-thumb-pdf]');
    if (!kanvas.length) { return; }
    import('<?= base_url('assets/js/vendor/pdfjs/pdf.min.mjs') ?>').then(function (pdfjsLib) {
        pdfjsLib.GlobalWorkerOptions.workerSrc = '<?= base_url('assets/js/vendor/pdfjs/pdf.worker.min.mjs') ?>';
        kanvas.forEach(function (c) {
            fetch(c.getAttribute('data-thumb-pdf'), { credentials: 'same-origin' })
                .then(function (r) { if (!r.ok) { throw new Error('gagal'); } return r.arrayBuffer(); })
                .then(function (data) { return pdfjsLib.getDocument({ data: data, isEvalSupported: false }).promise; })
                .then(function (pdf) { return pdf.getPage(1); })
                .then(function (page) {
                    var lebar = c.parentElement.clientWidth || 320;
                    var vp1 = page.getViewport({ scale: 1 });
                    var vp = page.getViewport({ scale: (lebar * (window.devicePixelRatio || 1)) / vp1.width });
                    c.width = vp.width; c.height = vp.height;
                    return page.render({ canvasContext: c.getContext('2d'), viewport: vp }).promise;
                })
                .catch(function () { /* biarkan kosong */ });
        });
    }).catch(function () {});
})();
</script>
