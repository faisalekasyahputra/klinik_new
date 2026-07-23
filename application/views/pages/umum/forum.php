<section class="w-full min-h-full px-2 py-3 sm:px-4 sm:py-5 font-outfit theme-light" x-data="{ openModal: false }">
    <div class="mx-auto max-w-6xl space-y-5">
        <div class="flex flex-col justify-between gap-4 border-b pb-5 sm:flex-row sm:items-end" style="border-color:var(--portal-border);">
            <div class="max-w-2xl">
                <p class="mb-2 text-[10px] font-black uppercase tracking-[.18em]" style="color:var(--portal-brand);">Pendampingan Klinik PKP</p>
                <h1 class="text-2xl font-black tracking-tight sm:text-4xl" style="color:var(--portal-text);">Konsultasi <span style="color:var(--portal-brand);">Terjadwal</span></h1>
                <p class="mt-2 text-sm leading-relaxed" style="color:var(--portal-text-muted);">Sampaikan kebutuhan perumahan Anda. Admin akan meninjau penjelasan warga dan mengatur agenda konsultasi bersama petugas.</p>
            </div>
            <?php if ($this->session->userdata('is_logged') === TRUE): ?>
                <button type="button" @click="openModal = true" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl px-5 py-3 text-xs font-bold transition-all" style="background:var(--portal-brand);color:#0a1a1f;box-shadow:0 6px 18px rgba(0,163,181,.16);">
                    <i class="fa-solid fa-calendar-plus"></i> Ajukan Konsultasi
                </button>
            <?php else: ?>
                <a href="<?= base_url('Auth/login') ?>" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border px-5 py-3 text-xs font-bold transition-all" style="border-color:rgba(0,163,181,.3);color:var(--portal-brand);">
                    <i class="fa-solid fa-right-to-bracket"></i> Masuk untuk Mengajukan
                </a>
            <?php endif; ?>
        </div>

        <?php if ($this->session->flashdata('error')): ?>
            <div class="flex items-center gap-2 rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-xs text-red-500"><i class="fa-solid fa-circle-exclamation"></i><?= $this->session->flashdata('error') ?></div>
        <?php endif; ?>
        <?php if ($this->session->flashdata('success')): ?>
            <div class="flex items-center gap-2 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-xs text-emerald-600"><i class="fa-solid fa-circle-check"></i><?= $this->session->flashdata('success') ?></div>
        <?php endif; ?>

        <div class="grid gap-3 sm:grid-cols-3">
            <?php foreach ([
                ['fa-file-pen','Jelaskan kebutuhan','Ceritakan masalah dan konteks yang ingin dibahas.'],
                ['fa-user-check','Admin meninjau','Pengajuan diarahkan ke petugas yang sesuai.'],
                ['fa-calendar-check','Agenda ditentukan','Waktu konsultasi disampaikan setelah ditinjau.']
            ] as $step): ?>
                <div class="rounded-2xl border p-4" style="background:var(--portal-bg-card);border-color:var(--portal-border);box-shadow:var(--portal-shadow);">
                    <i class="fa-solid <?= $step[0] ?> mb-3 text-lg" style="color:var(--portal-brand);"></i>
                    <p class="text-xs font-bold" style="color:var(--portal-text);"> <?= $step[1] ?></p>
                    <p class="mt-1 text-[11px] leading-relaxed" style="color:var(--portal-text-muted);"> <?= $step[2] ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <form method="GET" action="<?= base_url('Umum/forum') ?>" class="flex flex-col gap-2 sm:flex-row">
            <input type="search" name="q" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="Cari konsultasi atau kebutuhan warga..." class="min-w-0 flex-1 rounded-xl border px-4 py-3 text-xs outline-none transition-colors focus:border-[color:var(--portal-brand)]" style="background:var(--portal-bg-card);border-color:var(--portal-border);color:var(--portal-text);">
            <select name="kategori" class="rounded-xl border px-4 py-3 text-xs outline-none focus:border-[color:var(--portal-brand)] sm:w-48" style="background:var(--portal-bg-card);border-color:var(--portal-border);color:var(--portal-text);">
                <option value="">Semua agenda</option>
                <?php foreach (['RTLH','Prasarana Umum','Sengketa Lahan','Rumah Subsidi','Lainnya'] as $kategori): ?>
                    <option value="<?= $kategori ?>" <?= ($kategori_aktif ?? '') === $kategori ? 'selected' : '' ?>><?= $kategori ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="rounded-xl px-5 py-3 text-xs font-bold transition-all" style="background:var(--portal-text);color:#fff;"><i class="fa-solid fa-magnifying-glass mr-1"></i> Cari</button>
        </form>

        <div class="space-y-3">
            <?php if (!empty($diskusi)): foreach ($diskusi as $row): ?>
                <article class="rounded-2xl border p-4 transition-all hover:-translate-y-0.5 sm:p-5" style="background:var(--portal-bg-card);border-color:var(--portal-border);box-shadow:var(--portal-shadow);">
                    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                        <div class="min-w-0 space-y-2">
                            <div class="flex flex-wrap items-center gap-2 text-[10px]" style="color:var(--portal-text-muted);">
                                <span class="rounded-full border px-2.5 py-1 font-bold uppercase tracking-wide" style="background:rgba(0,163,181,.08);border-color:rgba(0,163,181,.18);color:var(--portal-brand);"> <?= htmlspecialchars($row['kategori']) ?></span>
                                <?php if (($row['status'] ?? '') === 'resolved'): ?><span class="text-emerald-600"><i class="fa-solid fa-circle-check mr-1"></i>Selesai</span><?php elseif (($row['status'] ?? '') === 'closed'): ?><span class="text-red-500"><i class="fa-solid fa-lock mr-1"></i>Ditutup</span><?php endif; ?>
                                <span>Oleh <?= htmlspecialchars($row['nama_user']) ?> · <?= date('d M Y', strtotime($row['created_at'])) ?></span>
                            </div>
                            <h2 class="truncate text-base font-bold" style="color:var(--portal-text);"><a href="<?= base_url('Umum/detail/'.$row['id_diskusi']); ?>" data-no-page-transition class="transition-colors hover:text-[color:var(--portal-brand)]"><?= htmlspecialchars($row['judul_topik']) ?></a></h2>
                            <p class="line-clamp-2 text-xs leading-relaxed" style="color:var(--portal-text-muted);"> <?= htmlspecialchars($row['isi_diskusi']) ?></p>
                        </div>
                        <a href="<?= base_url('Umum/detail/'.$row['id_diskusi']); ?>" data-no-page-transition class="inline-flex shrink-0 items-center gap-2 self-end rounded-xl border px-3 py-2 text-xs font-bold transition-all hover:border-[color:var(--portal-brand)] sm:self-center" style="border-color:var(--portal-border);color:var(--portal-brand);" aria-label="Lihat detail konsultasi">Lihat detail <i class="fa-solid fa-arrow-right"></i></a>
                    </div>
                </article>
            <?php endforeach; else: ?>
                <div class="rounded-2xl border border-dashed p-10 text-center" style="background:var(--portal-bg-card);border-color:var(--portal-border);color:var(--portal-text-muted);"><i class="fa-regular fa-calendar-xmark mb-3 text-2xl"></i><p class="text-sm">Belum ada pengajuan konsultasi.</p></div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($this->session->userdata('is_logged') === TRUE): ?>
        <div x-show="openModal" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4 backdrop-blur-sm">
            <div @click.away="openModal = false" class="w-full max-w-lg space-y-4 rounded-2xl border p-6 shadow-2xl" style="background:var(--portal-bg-card);border-color:var(--portal-border);">
                <div class="flex items-center justify-between border-b pb-3" style="border-color:var(--portal-border);"><h2 class="text-base font-bold" style="color:var(--portal-text);"><i class="fa-solid fa-calendar-plus mr-2" style="color:var(--portal-brand);"></i>Ajukan Konsultasi</h2><button type="button" @click="openModal = false" class="text-lg" style="color:var(--portal-text-muted);" aria-label="Tutup"><i class="fa-solid fa-xmark"></i></button></div>
                <p class="text-xs leading-relaxed" style="color:var(--portal-text-muted);">Admin akan meninjau pengajuan ini. Hindari menuliskan NIK atau data pribadi sensitif di dalam penjelasan.</p>
                <form action="<?= base_url('Umum/tambah_aksi') ?>" method="POST" class="space-y-4 text-xs">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                    <label class="block"><span class="mb-1.5 block font-medium" style="color:var(--portal-text-muted);">Agenda layanan</span><select name="kategori" class="w-full rounded-xl border px-3 py-2.5 outline-none focus:border-[color:var(--portal-brand)]" style="background:var(--portal-bg);border-color:var(--portal-border);color:var(--portal-text);"><option value="RTLH">RTLH (Rumah Tidak Layak Huni)</option><option value="Prasarana Umum">Prasarana Umum</option><option value="Sengketa Lahan">Sengketa Lahan</option><option value="Rumah Subsidi">Rumah Subsidi</option><option value="Lainnya">Lainnya</option></select></label>
                    <label class="block"><span class="mb-1.5 block font-medium" style="color:var(--portal-text-muted);">Tema konsultasi</span><input type="text" name="judul_topik" required minlength="10" maxlength="200" class="w-full rounded-xl border px-3 py-2.5 outline-none focus:border-[color:var(--portal-brand)]" style="background:var(--portal-bg);border-color:var(--portal-border);color:var(--portal-text);" placeholder="Contoh: Konsultasi penanganan RTLH"></label>
                    <label class="block"><span class="mb-1.5 block font-medium" style="color:var(--portal-text-muted);">Penjelasan kebutuhan warga</span><textarea name="isi_diskusi" rows="5" required minlength="20" class="w-full rounded-xl border px-3 py-2.5 outline-none focus:border-[color:var(--portal-brand)]" style="background:var(--portal-bg);border-color:var(--portal-border);color:var(--portal-text);" placeholder="Jelaskan kondisi, wilayah secara umum, dan hal yang ingin dikonsultasikan..."></textarea></label>
                    <button type="submit" class="w-full rounded-xl py-3 font-bold transition-all" style="background:var(--portal-brand);color:#0a1a1f;">Kirim Pengajuan Konsultasi</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
</section>
