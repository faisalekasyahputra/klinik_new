<?php
/**
 * Cek RTLH — hasilnya sengaja SEDIKIT.
 *
 * Yang ditampilkan: terdaftar atau tidak, plus identitas TERSAMAR secukupnya
 * untuk memastikan orangnya benar. Tidak ada desil, penghasilan, atau kondisi
 * bangunan — itu bahan penilaian program (wizard pendataan), bukan jawaban
 * atas pertanyaan "apakah saya terdaftar".
 */
$terdaftar = ($hasil['status'] ?? '') === 'found';
$tidak_ada = ($hasil['status'] ?? '') === 'not_found';
?>
<?php
/* Layar ini paling rawan disalahpahami penguji: selama SIMPERUM belum aktif,
   SETIAP NIK sungguhan dijawab "tidak terdaftar". Tanpa pemberitahuan, itu
   terbaca sebagai pencarian yang rusak. */
$this->load->view('components/modal_simperum_simulasi');
?>
<div class="theme-light py-4 sm:py-6 px-1 sm:px-2">
    <div class="mx-auto max-w-xl text-center" data-aos="fade-down">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-[color:var(--portal-btn-bg)] text-2xl text-[color:var(--portal-icon)]">
            <i class="fa-solid fa-house-circle-check" aria-hidden="true"></i>
        </div>
        <p class="mt-5 text-xs font-black uppercase tracking-[0.18em] text-[color:var(--portal-brand)]">Data SIMPERUM</p>
        <?php /* "Cek Data Rumah", bukan "Cek RTLH" maupun "Cek Backlog" (revisi
                 dinas 5 Agt 2026, butir A11). Dinas meminta "Cek Backlog", tapi
                 backlog dan RTLH dua indikator berbeda — backlog soal
                 kepemilikan/kepenghunian, RTLH soal kelayakan bangunan — dan API
                 yang dipanggil halaman ini mengembalikan data RTLH. Menamainya
                 "Backlog" berarti menjanjikan angka yang tidak ada di baliknya.
                 Nama netral ini melepas singkatan teknisnya tanpa berbohong soal
                 isinya. Keputusan user 5 Agt 2026. */ ?>
        <h1 class="mt-2 text-3xl font-black tracking-tight text-[color:var(--portal-text)]">Cek Data Rumah</h1>
        <p class="mx-auto mt-2 max-w-md text-xs leading-relaxed text-[color:var(--portal-text-muted)]">
            Periksa apakah sebuah NIK terdaftar dalam data Rumah Tidak Layak Huni Provinsi Jawa Tengah.
        </p>
    </div>

    <?php if ( ! empty($hasil)): ?>
    <div class="mx-auto mt-6 max-w-xl">
        <?php if ( ! empty($hasil['simulasi'])): ?>
        <?php // Wajib terlihat: data simulasi yang disangka nyata adalah kesalahan yang menyebar. ?>
        <div class="mb-3 rounded-xl border border-dashed p-3 text-[11px] font-bold" style="border-color: var(--portal-brand); color: var(--portal-brand)">
            <i class="fa-solid fa-flask mr-1"></i> MODE SIMULASI — angka di bawah ini data contoh, bukan data SIMPERUM sungguhan.
        </div>
        <?php endif; ?>

        <div class="rounded-2xl border p-5 shadow-sm" style="background-color: var(--portal-bg-card); border-color: <?= $terdaftar ? 'var(--portal-brand)' : 'var(--portal-border)' ?>">
            <div class="flex items-start gap-3">
                <i class="fa-solid <?= $terdaftar ? 'fa-circle-check' : ($tidak_ada ? 'fa-circle-minus' : 'fa-triangle-exclamation') ?> mt-0.5 text-xl"
                   style="color: <?= $terdaftar ? 'var(--portal-brand)' : 'var(--portal-text-muted)' ?>"></i>
                <div class="min-w-0">
                    <p class="text-sm font-black text-[color:var(--portal-text)]">
                        <?php if ($terdaftar): ?>
                            NIK ****<?= html_escape($hasil['nik_ekor']) ?> <span style="color: var(--portal-brand)">TERDAFTAR</span> dalam data RTLH
                        <?php elseif ($tidak_ada): ?>
                            NIK ****<?= html_escape($hasil['nik_ekor']) ?> tidak terdaftar dalam data RTLH
                        <?php else: ?>
                            Pencarian belum berhasil
                        <?php endif; ?>
                    </p>
                    <p class="mt-1 text-xs leading-relaxed text-[color:var(--portal-text-muted)]"><?= html_escape($hasil['pesan']) ?></p>

                    <?php if ($terdaftar && ! empty($hasil['profil'])): ?>
                    <?php // Tersamar — cukup untuk memastikan orangnya benar, tidak lebih. ?>
                    <dl class="mt-3 space-y-1 border-t pt-3 text-xs" style="border-color: var(--portal-border)">
                        <div class="flex gap-2"><dt class="w-24 shrink-0 text-[color:var(--portal-text-muted)]">Nama</dt><dd class="font-bold text-[color:var(--portal-text)]"><?= html_escape($hasil['profil']['nama_lengkap'] ?? '—') ?></dd></div>
                        <div class="flex gap-2"><dt class="w-24 shrink-0 text-[color:var(--portal-text-muted)]">Alamat</dt><dd class="text-[color:var(--portal-text)]"><?= html_escape($hasil['profil']['alamat'] ?? '—') ?></dd></div>
                    </dl>
                    <?php endif; ?>

                    <?php if ($tidak_ada): ?>
                    <p class="mt-3 text-xs leading-relaxed text-[color:var(--portal-text-muted)]">
                        Tidak terdaftar bukan berarti tidak bisa mengajukan. Kelayakan program dinilai lewat
                        <a href="<?= base_url('warga/pendataan') ?>" class="font-bold underline" style="color: var(--portal-brand)">pendataan warga</a>,
                        bukan hanya dari data ini.
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <form class="mx-auto mt-6 max-w-xl space-y-4" action="<?= base_url('Cek_Rtlh/periksa') ?>" method="POST">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

        <div>
            <label for="rtlh-nik" class="mb-1.5 block text-xs font-bold text-[color:var(--portal-text)]">NIK</label>
            <input id="rtlh-nik" name="nik" required inputmode="numeric" pattern="\d{16}" maxlength="16"
                   value="<?= html_escape($isian['nik'] ?? '') ?>" placeholder="16 digit tanpa spasi"
                   class="w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] px-4 py-3 text-sm tracking-wider text-[color:var(--portal-text)] shadow-sm outline-none transition-colors focus:border-[color:var(--portal-brand)] focus:ring-2 focus:ring-[color:var(--portal-brand)]/15">
        </div>

        <div>
            <label for="rtlh-tgl" class="mb-1.5 block text-xs font-bold text-[color:var(--portal-text)]">Tanggal Lahir</label>
            <?php // `type=date` bawaan browser: sudah menangani keyboard, pembaca layar, dan format lokal. ?>
            <input id="rtlh-tgl" name="tgl_lahir" type="date" required max="<?= date('Y-m-d') ?>"
                   value="<?= html_escape($isian['tgl_lahir'] ?? '') ?>"
                   class="w-full rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] px-4 py-3 text-sm text-[color:var(--portal-text)] shadow-sm outline-none transition-colors focus:border-[color:var(--portal-brand)] focus:ring-2 focus:ring-[color:var(--portal-brand)]/15">
            <p class="mt-1.5 text-[11px] text-[color:var(--portal-text-muted)]">Harus cocok dengan NIK yang dimasukkan.</p>
        </div>

        <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-full px-5 py-3 text-sm font-black transition hover:-translate-y-0.5"
                style="background-color: var(--portal-brand); color: var(--portal-bg)">
            Periksa <i class="fa-solid fa-magnifying-glass"></i>
        </button>
    </form>

    <div class="mx-auto mt-6 max-w-xl rounded-2xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] p-4 text-xs leading-relaxed text-[color:var(--portal-text-muted)] shadow-sm">
        <i class="fa-solid fa-shield-halved mr-2 text-[color:var(--portal-icon)]"></i>
        Pencarian dibatasi <b>10 kali per jam</b> per akun dan tercatat di jejak audit. Data RTLH menyangkut
        keadaan tempat tinggal seseorang — gunakan hanya untuk keperluan layanan.
    </div>
</div>
