<?php
/**
 * Halaman persyaratan KKN Kemitraan.
 *
 * NOMENKLATUR: "KKN Tematik" -> "KKN Kemitraan" (revisi dinas 3 Agt 2026).
 * Nilai DB `kkn_magang_pendaftaran.jenis` TETAP 'kkn' - yang berubah hanya
 * yang dibaca orang. Mengganti nilai kolomnya berarti migrasi data untuk nol
 * manfaat, dan memutus setiap harness yang menyebut 'kkn'.
 *
 * JUKNIS DICABUT. Sampai 3 Agt 2026 halaman ini memasang tombol unduh ke
 * `output/pdf/juknis-kkn-kemitraan-disperakim-jateng.pdf` - 114 KB yang
 * tersaji LANGSUNG oleh Apache dari webroot, tanpa controller dan tanpa
 * gerbang apa pun. Dinas menyatakan dokumen itu tidak boleh dipublikasikan,
 * jadi berkasnya dipindahkan ke luar webroot (private_uploads/arsip_tidak_publik/)
 * dan tombolnya dibuang. Diverifikasi: URL lamanya kini 404.
 *
 * Penggantinya - buku panduan KKN Kemitraan + leaflet - MENUNGGU berkas dari
 * dinas. Tempatnya sengaja tidak disiapkan sebagai tombol mati: tautan unduh
 * yang menuju berkas tak ada lebih buruk daripada tidak ada tautan sama sekali.
 */
?>
<?php
// Kelas bersama dua kartu. Kartu sama tinggi (stretch) dan tombol didorong ke dasar
// dengan mt-auto, supaya kedua tombol sejajar walau isi kartunya beda panjang.
$kartu  = 'flex flex-col rounded-3xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] p-5 shadow-sm sm:p-7';
$ikon   = 'flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-[color:var(--portal-btn-bg)] text-lg text-[color:var(--portal-icon)]';
$label  = 'inline-flex items-center gap-1.5 rounded-full border border-[color:var(--portal-border)] px-2.5 py-1 text-[11px] font-bold text-[color:var(--portal-text-muted)]';
$butir  = 'flex gap-3 rounded-2xl border border-[color:var(--portal-border)] p-3.5';
$ikon_butir = 'flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[color:var(--portal-btn-bg)] text-sm text-[color:var(--portal-brand)]';
$tombol = 'mt-auto inline-flex w-full items-center justify-center gap-2 rounded-xl bg-[color:var(--portal-brand)] px-5 py-3 text-sm font-bold text-[#0a1a1f] transition hover:brightness-95 sm:w-auto sm:self-start';
?>
<div class="mx-auto max-w-5xl p-2 sm:p-6">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <span class="text-xs font-bold uppercase tracking-[.18em] text-[color:var(--portal-text-muted)]">Kemitraan</span>
            <h1 class="mt-2 text-2xl font-black text-[color:var(--portal-text)] sm:text-4xl">KKN Kemitraan</h1>
            <p class="mt-2 max-w-xl text-sm leading-relaxed text-[color:var(--portal-text-muted)]">Perguruan tinggi mendaftarkan KKN lewat dashboard; peserta yang sudah selesai mencetak sertifikatnya sendiri.</p>
        </div>
        <a href="<?= base_url('KemitraanPortal') ?>" data-tab-link data-tab-key="kemitraan"
           class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-[color:var(--portal-border)] px-3 py-2 text-xs font-bold text-[color:var(--portal-text)]"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Kembali</a>
    </div>

    <?php // Dua kartu berdampingan di layar lebar (permintaan user 22 Agt 2026),
          // bertumpuk di mobile. ?>
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
    <section class="<?= $kartu ?>" aria-labelledby="kkn-syarat">
        <div class="flex items-start gap-3">
            <span class="<?= $ikon ?>"><i class="fa-solid fa-building-columns" aria-hidden="true"></i></span>
            <div class="min-w-0">
                <h2 id="kkn-syarat" class="text-lg font-black text-[color:var(--portal-text)] sm:text-xl">Persyaratan KKN Kemitraan</h2>
                <span class="<?= $label ?> mt-1.5"><i class="fa-solid fa-user-tie" aria-hidden="true"></i> Untuk perguruan tinggi mitra</span>
            </div>
        </div>
        <p class="mt-4 text-sm leading-relaxed text-[color:var(--portal-text-muted)]">
            Dua surat berikut diterbitkan perguruan tinggi mitra, bukan oleh mahasiswa perorangan.
        </p>

        <?php
        // Teks persyaratan dari dinas 3 Agt 2026. Ini daftar dokumen, bukan urutan,
        // jadi ditandai ikon dokumen, bukan nomor.
        $syarat = [
            ['fa-file-signature', 'Surat permohonan dari perguruan tinggi mitra',
                'Memuat lokasi pelaksanaan, jumlah mahasiswa, dan periode pelaksanaan KKN.'],
            ['fa-users', 'Surat permohonan akses akun SIMPERUM dari perguruan tinggi',
                'Memuat daftar nama mahasiswa peserta yang telah ditetapkan.'],
        ];
        ?>
        <ul class="mt-5 mb-6 space-y-3">
            <?php foreach ($syarat as [$ik, $judul, $rinci]): ?>
                <li class="<?= $butir ?>">
                    <span class="<?= $ikon_butir ?>"><i class="fa-solid <?= $ik ?>" aria-hidden="true"></i></span>
                    <div class="min-w-0">
                        <div class="text-sm font-bold text-[color:var(--portal-text)]"><?= html_escape($judul) ?></div>
                        <div class="mt-0.5 text-[13px] leading-relaxed text-[color:var(--portal-text-muted)]"><?= html_escape($rinci) ?></div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- Menuju dashboard KKN, bukan formulir sekali-daftar (dipensiunkan
             21 Agt 2026, lihat KemitraanPortal::daftar()). -->
        <a href="<?= base_url('KemitraanPortal/kkn_dashboard') ?>" data-tab-link data-tab-key="kemitraan_kkn_dashboard" class="<?= $tombol ?>">
            <i class="fa-solid fa-gauge" aria-hidden="true"></i> Buka Dashboard KKN <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
    </section>

    <?php // Cetak Sertifikat KKN - permintaan user 22 Agt 2026. Lihat komentar
          // anti-enumerasi lengkap di KemitraanPortal::cek_sertifikat_kkn()
          // sebelum mengubah kartu ini. ?>
    <section class="<?= $kartu ?>" aria-labelledby="kkn-sertifikat">
        <div class="flex items-start gap-3">
            <span class="<?= $ikon ?>"><i class="fa-solid fa-award" aria-hidden="true"></i></span>
            <div class="min-w-0">
                <h2 id="kkn-sertifikat" class="text-lg font-black text-[color:var(--portal-text)] sm:text-xl">Cetak Sertifikat KKN</h2>
                <span class="<?= $label ?> mt-1.5"><i class="fa-solid fa-unlock" aria-hidden="true"></i> Untuk peserta KKN, tanpa login</span>
            </div>
        </div>
        <p class="mt-4 text-sm leading-relaxed text-[color:var(--portal-text-muted)]">
            Sudah menyelesaikan KKN Kemitraan? Cetak sertifikat Anda dengan memasukkan NIM.
        </p>

        <?php // Ini memang urutan, jadi tetap bernomor; ikon menjelaskan isinya. ?>
        <ol class="mt-5 mb-6 space-y-3">
            <?php foreach ([
                ['fa-id-card', 'Masukkan NIM', 'Tanpa perlu masuk atau mendaftar akun.'],
                ['fa-stamp', 'Sertifikat terbit setelah disahkan admin', 'Pengajuan KKN Anda harus sudah diterima, periode pelaksanaannya selesai, dan admin Disperakim sudah menetapkan tanggal terbit sertifikat.'],
            ] as $i => [$ik, $judul, $rinci]): ?>
                <li class="<?= $butir ?>">
                    <span class="<?= $ikon_butir ?>"><i class="fa-solid <?= $ik ?>" aria-hidden="true"></i></span>
                    <div class="min-w-0">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-[color:var(--portal-text-muted)]">Langkah <?= $i + 1 ?></div>
                        <div class="text-sm font-bold text-[color:var(--portal-text)]"><?= html_escape($judul) ?></div>
                        <div class="mt-0.5 text-[13px] leading-relaxed text-[color:var(--portal-text-muted)]"><?= html_escape($rinci) ?></div>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>

        <a href="<?= base_url('KemitraanPortal/sertifikat_kkn') ?>" data-tab-link data-tab-key="kemitraan_sertifikat_kkn" class="<?= $tombol ?>">
            <i class="fa-solid fa-print" aria-hidden="true"></i> Cetak Sertifikat KKN <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </a>
    </section>
    </div>
</div>
