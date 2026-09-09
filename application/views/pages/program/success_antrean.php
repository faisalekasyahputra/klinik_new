<?php
$ticket_code = isset($ticket_code) && $ticket_code !== '' ? $ticket_code : NULL;
$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>

<main class="relative flex min-h-[calc(100vh-80px)] w-full items-center justify-center overflow-hidden px-4 py-12 sm:px-6">
    <div class="pointer-events-none absolute -left-24 top-16 h-64 w-64 rounded-full bg-cyan-100/70 blur-3xl"></div>
    <div class="pointer-events-none absolute -right-24 bottom-10 h-72 w-72 rounded-full bg-lime-100/70 blur-3xl"></div>

    <section class="relative w-full max-w-3xl rounded-[2rem] border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] p-6 shadow-[var(--portal-shadow)] sm:p-10" aria-labelledby="success-title">
        <div class="mx-auto max-w-2xl text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[color:var(--portal-btn-bg)] text-2xl text-[color:var(--portal-icon)]" aria-hidden="true">
                <i class="fa-solid fa-check"></i>
            </div>
            <p class="mt-5 text-xs font-black uppercase tracking-[0.18em] text-[color:var(--portal-brand)]">Antrean Klinik PKP</p>
            <?php // Istilah "pengajuan" disapu dari jalur ini (revisi dinas 3 Agt
                  // 2026) - halaman sukses ikut, kalau tidak tombolnya berbunyi
                  // "Simpan Data" lalu layar berikutnya bilang "Pengajuan
                  // terkirim", dan warga tidak tahu mana yang benar. ?>
            <h1 id="success-title" class="mt-2 text-3xl font-black tracking-tight text-[color:var(--portal-text)] sm:text-4xl">Data Anda tersimpan</h1>
            <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-[color:var(--portal-text-muted)]">
                Simpan nomor tiket ini untuk memantau tindak lanjutnya oleh petugas.
            </p>
        </div>

        <div class="mx-auto mt-8 max-w-xl rounded-2xl border border-dashed border-[color:var(--portal-brand)] bg-[color:var(--portal-bg)] p-5 text-center" aria-label="Nomor tiket pengajuan">
            <p class="text-xs font-bold uppercase tracking-[0.16em] text-[color:var(--portal-text-muted)]">Nomor tiket Anda</p>
            <p class="mt-2 break-all font-mono text-2xl font-black tracking-[0.12em] text-[color:var(--portal-text)] sm:text-3xl">
                <?= $ticket_code ? $escape($ticket_code) : 'MENUNGGU SISTEM TIKET' ?>
            </p>
            <?php if ($ticket_code): ?>
                <button type="button" class="mt-3 inline-flex min-h-10 items-center gap-2 rounded-full border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] px-4 py-2 text-xs font-bold text-[color:var(--portal-text)] transition hover:border-[color:var(--portal-brand)]" onclick="navigator.clipboard && navigator.clipboard.writeText('<?= $escape($ticket_code) ?>')">
                    <i class="fa-regular fa-copy" aria-hidden="true"></i> Salin nomor tiket
                </button>
            <?php else: ?>
                <p class="mt-2 text-xs text-[color:var(--portal-text-muted)]">Nomor tiket akan muncul setelah generator tiket backend diaktifkan.</p>
            <?php endif; ?>
        </div>

        <?php
        /* Kotak "Cek status tanpa login" (tiket + 4 digit NIK) DICABUT
           16 Agt 2026 - permintaan user, membereskan celah yang sama yang
           sudah lama didokumentasikan di Program::cek_status_pengajuan()
           tapi belum tuntas: halaman itu sudah jadi redirect stub, tapi
           formulir di SINI masih memanggil Program::cek_tiket() langsung
           dan tidak pernah ikut dicabut. Sekarang status pengajuan HANYA
           lewat dashboard (halaman akun) - satu tempat, wajib login. */
        ?>
        <div class="mx-auto mt-6 max-w-xl rounded-2xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg)] p-5">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-bell mt-0.5 text-[color:var(--portal-icon)]" aria-hidden="true"></i>
                <div>
                    <h2 class="text-sm font-black text-[color:var(--portal-text)]">Langkah berikutnya</h2>
                    <p class="mt-1 text-sm leading-relaxed text-[color:var(--portal-text-muted)]">
                        Petugas akan memeriksa data Anda dalam 3×24 jam kerja. Masuk ke akun untuk memantau status dan riwayatnya.
                    </p>
                </div>
            </div>
        </div>

        <div class="mx-auto mt-8 flex max-w-xl">
            <a href="<?= base_url('login') ?>" class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-full bg-[color:var(--portal-btn-bg)] px-5 py-3 text-sm font-black text-[color:var(--portal-icon)] transition hover:-translate-y-0.5 hover:brightness-95">
                <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i> Masuk ke Akun
            </a>
        </div>

        <div class="mt-8 text-center">
            <a href="<?= base_url('Index') ?>" class="text-sm font-bold text-[color:var(--portal-text-muted)] underline decoration-[color:var(--portal-brand)] decoration-2 underline-offset-4 transition hover:text-[color:var(--portal-text)]">Kembali ke beranda</a>
        </div>
    </section>
</main>
