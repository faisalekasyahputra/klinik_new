<?php
/**
 * Hasil Cetak Sertifikat KKN - permintaan user 22 Agt 2026.
 *
 * $data sudah TERVERIFIKASI oleh KemitraanPortal::cek_sertifikat_kkn()
 * sebelum sampai di sini (NIM cocok, status Diterima, periode sudah lewat)
 * - halaman ini murni MENAMPILKAN, tidak memeriksa apa pun lagi.
 *
 * TEMPLATE SERTIFIKAT RESMI BELUM ADA - menyusul dari user. Tata letak di
 * bawah ini SEMENTARA: data yang ditampilkan sudah final, cuma rancangan
 * visualnya yang akan diganti begitu templatenya diterima. Ditandai jelas
 * lewat kotak kuning supaya tidak disangka sudah final oleh siapa pun yang
 * membukanya sebelum penggantian itu terjadi.
 */
?>
<div class="theme-light py-4 sm:py-6 px-1 sm:px-2 print:p-0">
    <div class="mx-auto max-w-2xl print:hidden">
        <div class="rounded-2xl border border-amber-300 bg-amber-50 px-4 py-3 text-xs leading-relaxed text-amber-800">
            <i class="fa-solid fa-triangle-exclamation mr-1.5" aria-hidden="true"></i>
            Tata letak sertifikat di bawah ini masih sementara - menunggu desain resmi dari Disperakim.
            Data yang tertera (nama, NIM, universitas, tema, periode) sudah terverifikasi dan final.
        </div>
    </div>

    <div class="mx-auto mt-6 max-w-2xl rounded-3xl border-2 border-[color:var(--portal-brand)] bg-[color:var(--portal-bg-card)] p-8 text-center shadow-sm print:mt-0 print:max-w-none print:rounded-none print:border-4 print:shadow-none sm:p-12">
        <p class="text-xs font-black uppercase tracking-[0.25em] text-[color:var(--portal-text-muted)]">Dinas Perumahan Rakyat &amp; Kawasan Permukiman Provinsi Jawa Tengah</p>
        <h1 class="mt-4 text-2xl font-black uppercase tracking-wide text-[color:var(--portal-text)] sm:text-3xl">Sertifikat KKN Kemitraan</h1>
        <p class="mt-1 text-xs text-[color:var(--portal-text-muted)]">Diberikan kepada:</p>

        <p class="mt-4 text-2xl font-black text-[color:var(--portal-brand)] sm:text-3xl"><?= html_escape($data->nama_peserta) ?></p>
        <p class="mt-1 text-sm text-[color:var(--portal-text-muted)]">NIM <?= html_escape($data->nim) ?></p>

        <p class="mx-auto mt-6 max-w-lg text-sm leading-relaxed text-[color:var(--portal-text)]">
            atas partisipasinya sebagai peserta Kuliah Kerja Nyata (KKN) Kemitraan
            dari <span class="font-bold"><?= html_escape($data->instansi_asal) ?></span>
            <?php if ( ! empty($data->divisi_atau_tema)): ?>
                dengan tema <span class="font-bold">&ldquo;<?= html_escape($data->divisi_atau_tema) ?>&rdquo;</span>
            <?php endif; ?>
            <?php if ( ! empty($data->periode_mulai) && ! empty($data->periode_selesai)): ?>
                selama periode <span class="font-bold"><?= html_escape(tgl_id($data->periode_mulai) . ' - ' . tgl_id($data->periode_selesai)) ?></span>
            <?php endif; ?>.
        </p>

        <div class="mt-10 flex items-center justify-center gap-2 text-[10px] text-[color:var(--portal-text-muted)]">
            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
            Diterbitkan otomatis oleh sistem Klinik PKP berdasarkan data pengajuan yang telah diverifikasi dan disetujui.
        </div>
    </div>

    <div class="mx-auto mt-6 flex max-w-2xl flex-wrap items-center justify-center gap-3 print:hidden">
        <button type="button" onclick="window.print()"
                class="inline-flex items-center gap-2 rounded-xl bg-[color:var(--portal-brand)] px-5 py-3 text-sm font-bold text-[#0a1a1f] transition hover:opacity-90">
            <i class="fa-solid fa-print" aria-hidden="true"></i> Cetak
        </button>
        <a href="<?= base_url('KemitraanPortal/sertifikat_kkn') ?>" data-tab-link data-tab-key="kemitraan_sertifikat_kkn"
           class="rounded-xl border border-[color:var(--portal-border)] px-5 py-3 text-sm font-bold text-[color:var(--portal-text)]">
            Cek NIM Lain
        </a>
    </div>
</div>
