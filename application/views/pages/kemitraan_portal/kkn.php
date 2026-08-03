<?php
/**
 * Halaman persyaratan KKN Kemitraan.
 *
 * NOMENKLATUR: "KKN Tematik" -> "KKN Kemitraan" (revisi dinas 3 Agt 2026).
 * Nilai DB `kkn_magang_pendaftaran.jenis` TETAP 'kkn' — yang berubah hanya
 * yang dibaca orang. Mengganti nilai kolomnya berarti migrasi data untuk nol
 * manfaat, dan memutus setiap harness yang menyebut 'kkn'.
 *
 * JUKNIS DICABUT. Sampai 3 Agt 2026 halaman ini memasang tombol unduh ke
 * `output/pdf/juknis-kkn-kemitraan-disperakim-jateng.pdf` — 114 KB yang
 * tersaji LANGSUNG oleh Apache dari webroot, tanpa controller dan tanpa
 * gerbang apa pun. Dinas menyatakan dokumen itu tidak boleh dipublikasikan,
 * jadi berkasnya dipindahkan ke luar webroot (private_uploads/arsip_tidak_publik/)
 * dan tombolnya dibuang. Diverifikasi: URL lamanya kini 404.
 *
 * Penggantinya — buku panduan KKN Kemitraan + leaflet — MENUNGGU berkas dari
 * dinas. Tempatnya sengaja tidak disiapkan sebagai tombol mati: tautan unduh
 * yang menuju berkas tak ada lebih buruk daripada tidak ada tautan sama sekali.
 */
?>
<div class="mx-auto max-w-4xl p-2 sm:p-6">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-[.18em] text-[color:var(--portal-text-muted)]">Kemitraan</span>
            <h1 class="mt-2 text-2xl font-black text-[color:var(--portal-text)] sm:text-4xl">KKN Kemitraan</h1>
        </div>
        <a href="<?= base_url('KemitraanPortal') ?>" data-tab-link data-tab-key="kemitraan"
           class="rounded-xl border border-[color:var(--portal-border)] px-3 py-2 text-xs font-bold text-[color:var(--portal-text)]">Kembali</a>
    </div>

    <section class="rounded-3xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] p-5 shadow-sm sm:p-8">
        <h2 class="text-xl font-black text-[color:var(--portal-text)]">Persyaratan KKN Kemitraan</h2>
        <p class="mt-2 text-sm leading-relaxed text-[color:var(--portal-text-muted)]">
            Dua surat berikut diterbitkan perguruan tinggi mitra, bukan oleh mahasiswa perorangan.
        </p>

        <?php
        // Teks persyaratan datang dari dinas 3 Agt 2026, menggantikan empat butir
        // lama (surat pengantar, proposal, daftar peserta, rencana jadwal) yang
        // disusun perancang awal tanpa konfirmasi. Ditulis di view karena hanya
        // dibaca di sini — memindahkannya ke DB/config berarti membangun layar
        // penyunting untuk teks yang berubah sekali setahun.
        $syarat = [
            'Surat permohonan dari perguruan tinggi mitra' =>
                'Memuat lokasi pelaksanaan, jumlah mahasiswa, dan periode pelaksanaan KKN.',
            'Surat permohonan akses akun SIMPERUM dari perguruan tinggi' =>
                'Memuat daftar nama mahasiswa peserta yang telah ditetapkan.',
        ];
        ?>
        <ol class="mt-6 space-y-4">
            <?php $n = 0; foreach ($syarat as $judul => $rinci): $n++; ?>
                <li class="flex gap-4">
                    <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[color:var(--portal-brand)] text-xs font-black text-[#0a1a1f]"><?= $n ?></span>
                    <div>
                        <div class="text-sm font-bold text-[color:var(--portal-text)]"><?= html_escape($judul) ?></div>
                        <div class="mt-1 text-sm leading-relaxed text-[color:var(--portal-text-muted)]"><?= html_escape($rinci) ?></div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>

        <div class="mt-7 flex flex-wrap gap-3">
            <a href="<?= base_url('KemitraanPortal/daftar/kkn') ?>" data-tab-link data-tab-key="kemitraan_daftar_kkn"
               class="inline-flex items-center gap-2 rounded-xl bg-[color:var(--portal-brand)] px-5 py-3 text-sm font-bold text-[#0a1a1f]">
                Daftar Sekarang <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </section>
</div>
