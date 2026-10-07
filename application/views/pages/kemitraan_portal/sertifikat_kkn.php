<?php
/**
 * Cetak Sertifikat KKN - permintaan user 22 Agt 2026.
 *
 * Formulir TANPA login (keputusan user) - mahasiswa peserta KKN belum
 * tentu punya akun sendiri, akun yang ada adalah akun universitas.
 * Diproses KemitraanPortal::cek_sertifikat_kkn() - lihat komentar lengkap
 * anti-enumerasi di kepala method itu sebelum mengubah apa pun di sini.
 */
$isian = 'w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)]'
    . ' px-4 py-3 text-sm text-[color:var(--portal-text)] shadow-sm outline-none transition-colors'
    . ' focus:border-[color:var(--portal-brand)] focus:ring-2 focus:ring-[color:var(--portal-brand)]/15';
$label = 'mb-1.5 block text-xs font-bold text-[color:var(--portal-text)]';
?>
<div class="theme-light py-4 sm:py-6 px-1 sm:px-2">
    <div class="mx-auto max-w-2xl text-center" data-aos="fade-down">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[color:var(--portal-btn-bg)] text-2xl text-[color:var(--portal-icon)]">
            <i class="fa-solid fa-certificate" aria-hidden="true"></i>
        </div>
        <p class="mt-5 text-xs font-black uppercase tracking-[0.18em] text-[color:var(--portal-brand)]">Kemitraan</p>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-[color:var(--portal-text)]">Cetak Sertifikat KKN</h1>
        <p class="mx-auto mt-2 max-w-md text-xs leading-relaxed text-[color:var(--portal-text-muted)]">
            Masukkan NIM Anda. Sertifikat hanya dapat dicetak setelah periode pelaksanaan KKN Anda selesai
            dan pengajuannya diterima.
        </p>
    </div>


    <?php /* Tombol permintaan muncul sekali sesudah pencarian yang NIM-nya ditemukan tetapi sertifikatnya belum
             terbit (KemitraanPortal::cek_sertifikat_kkn, 7 Okt 2026); id KKN-nya di sesi, bukan di halaman. */
    if ($this->session->flashdata('sertifikat_bisa_diminta')): ?>
    <section class="mx-auto mt-6 max-w-md rounded-2xl border border-[color:var(--portal-brand)] bg-[color:var(--portal-bg-card)] p-4 text-left shadow-sm" data-minta-sertifikat>
        <h2 class="text-sm font-black text-[color:var(--portal-text)]">Sertifikat belum diterbitkan</h2>
        <p class="mt-1 text-xs leading-relaxed text-[color:var(--portal-text-muted)]">NIM Anda terdaftar dan periode KKN sudah selesai, tetapi Disperakim belum menetapkan tanggal terbit sertifikatnya. Kirim permintaan agar sertifikat segera diterbitkan.</p>
        <form class="mt-3" action="<?= base_url('KemitraanPortal/minta_sertifikat_kkn') ?>" method="POST">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[color:var(--portal-brand)] px-5 py-3 text-sm font-bold text-[#0a1a1f] transition hover:opacity-90">
                Minta sertifikat ke Disperakim <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
    </section>
    <?php endif; ?>

    <form class="mx-auto mt-6 max-w-md space-y-4" action="<?= base_url('KemitraanPortal/cek_sertifikat_kkn') ?>" method="POST">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

        <div>
            <label for="sk-nim" class="<?= $label ?>">NIM</label>
            <input id="sk-nim" name="nim" required maxlength="30" pattern="[A-Za-z0-9]{1,30}"
                   placeholder="Masukkan NIM" class="<?= $isian ?>" autocomplete="off">
        </div>

        <button type="submit"
                class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[color:var(--portal-brand)] px-5 py-3 text-sm font-bold text-[#0a1a1f] transition hover:opacity-90">
            Cek Sertifikat <i class="fa-solid fa-magnifying-glass"></i>
        </button>

        <p class="text-center text-[11px] text-[color:var(--portal-text-muted)]">
            KKN didaftarkan oleh perguruan tinggi mitra, bukan oleh mahasiswa perorangan; sertifikatnya diterbitkan Disperakim.
            Kalau NIM Anda tidak ditemukan, KKN Anda mungkin belum tercatat: hubungi pihak kampus, atau Disperakim melalui menu Aduan.
        </p>
    </form>

    <div class="mx-auto mt-6 max-w-md text-center">
        <a href="<?= base_url('KemitraanPortal/kkn') ?>" data-tab-link data-tab-key="kemitraan_kkn"
           class="text-xs font-bold text-[color:var(--portal-text-muted)] hover:text-[color:var(--portal-text)]">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke KKN Kemitraan
        </a>
    </div>
</div>
