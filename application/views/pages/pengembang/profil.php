<?php
// Hanya izinkan skema http/https sebagai link — cegah stored XSS via "javascript:" URI
// pada field yang diisi bebas oleh user (instagram/website/sosmed_lainnya).
$safe_url = function ($url) {
    if (empty($url)) return null;
    return preg_match('#^https?://#i', $url) ? $url : null;
};
$sosmed = [
    ['fa-brands fa-instagram', 'Instagram', $pengembang->instagram ?? null],
    ['fa-solid fa-globe', 'Website', $pengembang->website ?? null],
    ['fa-solid fa-share-nodes', 'Sosmed Lainnya', $pengembang->sosmed_lainnya ?? null],
];
?>
<section class="w-full pt-24 pb-16 px-4 sm:px-6 lg:px-8 relative min-h-screen font-outfit">
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute top-[-10%] right-[-5%] w-[600px] h-[600px] rounded-full bg-[#d6fb00]/5 blur-[120px]"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[500px] h-[500px] rounded-full bg-[#00a3b5]/5 blur-[100px]"></div>
    </div>

    <div class="max-w-3xl mx-auto relative z-10">

        <!-- Breadcrumb -->
        <div class="mb-10">
            <nav class="flex text-[10px] sm:text-xs text-zinc-500 font-bold uppercase tracking-widest" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2">
                    <li class="inline-flex items-center">
                        <a href="<?= base_url() ?>" class="hover:text-[#d6fb00] transition-colors"><i class="fa-solid fa-house mr-2"></i>Beranda</a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fa-solid fa-chevron-right text-[8px] mx-2"></i>
                            <a href="<?= base_url('Pengembang/sertifikasi') ?>" class="hover:text-[#d6fb00] transition-colors">Sertifikasi Pengembang</a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fa-solid fa-chevron-right text-[8px] mx-2"></i>
                            <span class="text-[#d6fb00] truncate max-w-[180px] inline-block align-bottom"><?= htmlspecialchars($pengembang->nama_perusahaan) ?></span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <!-- Profil Card -->
        <div class="rounded-[2rem] p-6 sm:p-8 mb-6" style="background-color: var(--bg-card); border: 1px solid rgba(255,255,255,0.08);">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 border-b border-zinc-800/80 pb-6 mb-6">
                <div>
                    <span class="text-[#d6fb00] font-extrabold text-[10px] uppercase tracking-widest block mb-1">Profil Pengembang</span>
                    <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight"><?= htmlspecialchars($pengembang->nama_perusahaan) ?></h2>
                </div>
                <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 font-bold text-xs uppercase tracking-wider shrink-0">
                    <i class="fa-solid fa-circle-check text-xs"></i> Bersertifikat
                </span>
            </div>

            <table class="w-full text-xs sm:text-sm text-zinc-300 border-separate border-spacing-y-2.5">
                <tbody>
                    <tr>
                        <td class="w-1/3 text-zinc-500 font-medium">Asosiasi</td>
                        <td class="uppercase">: <?= htmlspecialchars($pengembang->asosiasi) ?></td>
                    </tr>
                    <tr>
                        <td class="text-zinc-500 font-medium">No. Keanggotaan</td>
                        <td>: <?= htmlspecialchars($pengembang->no_keanggotaan) ?></td>
                    </tr>
                    <tr>
                        <td class="text-zinc-500 font-medium align-top">Alamat Kantor</td>
                        <td class="leading-relaxed">: <?= nl2br(htmlspecialchars($pengembang->alamat_kantor)) ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Kontak & Sosial Media -->
        <div class="rounded-[2rem] p-6 sm:p-8" style="background-color: var(--bg-card); border: 1px solid rgba(255,255,255,0.08);">
            <h3 class="text-[#d6fb00] font-bold uppercase tracking-wider text-[11px] border-b border-zinc-800 pb-1.5 mb-4">
                Kontak & Sosial Media
            </h3>
            <div class="space-y-3">
                <?php foreach ($sosmed as $s): [$icon, $label, $value] = $s; $link = $safe_url($value); ?>
                <div class="flex items-center justify-between gap-4 p-4 rounded-xl bg-black/20 border border-zinc-800">
                    <span class="text-zinc-400 font-medium text-sm"><i class="<?= $icon ?> text-[#d6fb00] mr-2"></i><?= $label ?></span>
                    <?php if ($link): ?>
                        <a href="<?= htmlspecialchars($link) ?>" target="_blank" rel="noopener" class="text-[#d6fb00] font-bold text-sm hover:underline truncate max-w-[50%]"><?= htmlspecialchars($value) ?></a>
                    <?php elseif (!empty($value)): ?>
                        <span class="text-zinc-400 text-sm truncate max-w-[50%]"><?= htmlspecialchars($value) ?></span>
                    <?php else: ?>
                        <span class="text-zinc-500 italic text-sm">Belum diisi</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</section>
