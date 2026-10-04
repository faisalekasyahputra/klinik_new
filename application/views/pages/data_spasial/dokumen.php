<?php
/**
 * Halaman mandiri untuk Dokumen::index() - viewer flipbook-nya sendiri
 * sekarang ada di partial _dokumen_viewer.php (dipakai bersama dengan
 * tab_bankdata.php, lihat catatan di sana). Berkas ini tinggal
 * membungkusnya dengan header halaman.
 */
?>
<div class="py-4 sm:py-6 px-1 sm:px-2 font-outfit">
    <div class="mx-auto max-w-4xl">

        <?php /* Jalan pulang ke tab Bank Data (permintaan user 4 Okt 2026); gaya tombol teal yang sama
                 dengan halaman Data Kawasan Kumuh. data-tab-link supaya dibuka lewat sistem tab portal. */ ?>
        <a href="<?= base_url('tab/bankdata') ?>" data-tab-link data-tab-key="bankdata" data-kembali-bankdata
           class="mb-3 inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-sm font-bold"
           style="background:var(--portal-brand);color:var(--portal-btn-text)">
            <span aria-hidden="true">&lsaquo;</span> Kembali ke Bank Data
        </a>

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

        <?php $this->load->view('pages/data_spasial/_dokumen_viewer', ['pdf_url' => $pdf_url, 'contoh' => $contoh]); ?>

    </div>
</div>
