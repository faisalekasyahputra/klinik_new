<style>
    .tl-btn-base {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.375rem 0.875rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }
</style>
<section class="w-full pt-24 pb-16 px-4 sm:px-6 lg:px-8 relative min-h-screen font-outfit">
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute top-[-10%] right-[-5%] w-[600px] h-[600px] rounded-full bg-[#d6fb00]/5 blur-[120px]"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[500px] h-[500px] rounded-full bg-[#00a3b5]/5 blur-[100px]"></div>
    </div>

    <div class="max-w-5xl mx-auto relative z-10">

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
                            <span class="text-[#d6fb00]">Sertifikasi Pengembang</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <div class="text-left mb-12 space-y-4 max-w-2xl">
            <span class="text-[#d6fb00] font-extrabold text-[11px] uppercase tracking-widest block">SRP2</span>
            <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tighter font-jakarta">
                Sertifikasi <span class="text-[#d6fb00]">Pengembang</span> Perumahan
            </h2>
            <p class="text-zinc-400 text-sm sm:text-base leading-relaxed">
                Program Bimbingan Teknis & Sertifikasi Registrasi Pengembang Perumahan (SRP2) dari Disperakim Provinsi Jawa Tengah, untuk memastikan pengembang yang beroperasi di Jawa Tengah memenuhi standar legalitas dan kompetensi.
            </p>
        </div>

        <!-- Daftar Pengembang Bersertifikat -->
        <div class="rounded-[2rem] p-6 sm:p-8 mb-8" style="background-color: var(--bg-card); border: 1px solid rgba(255,255,255,0.08);">
            <h3 class="text-white font-bold text-lg mb-1">Daftar Pengembang Bersertifikat</h3>
            <p class="text-zinc-500 text-xs mb-6">Pengembang yang telah lolos verifikasi SRP2.</p>

            <?php if (!empty($daftar_pengembang)): ?>
            <div class="space-y-2.5">
                <?php foreach ($daftar_pengembang as $i => $row): ?>
                <div class="flex items-center justify-between gap-4 p-4 rounded-xl bg-black/20 border border-zinc-800 hover:border-[#d6fb00]/30 transition-colors">
                    <div class="flex items-center gap-4 min-w-0">
                        <span class="shrink-0 w-8 h-8 rounded-lg bg-[#d6fb00]/10 border border-[#d6fb00]/20 text-[#d6fb00] font-bold text-xs flex items-center justify-center"><?= $i + 1 ?></span>
                        <span class="text-white font-bold text-sm truncate"><?= htmlspecialchars($row->nama_perusahaan) ?></span>
                    </div>
                    <a href="<?= base_url('Pengembang/profil/' . $row->id) ?>" class="tl-btn-base shrink-0" style="background-color: rgba(214,251,0,0.1); color: #d6fb00; border: 1px solid rgba(214,251,0,0.2);">
                        <span>Detail</span>
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-10">
                <i class="fa-solid fa-building-circle-xmark text-zinc-700 text-3xl mb-3"></i>
                <p class="text-zinc-500 text-sm">Belum ada pengembang bersertifikat yang terdaftar.</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- CTA -->
        <div class="rounded-[2rem] p-6 sm:p-8 flex flex-col sm:flex-row items-center justify-between gap-5" style="background-color: var(--bg-card); border: 1px solid rgba(214, 251, 0, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
            <div>
                <h3 class="text-white font-bold text-lg mb-1">Ingin Mendaftar Sertifikasi?</h3>
                <p class="text-zinc-500 text-xs sm:text-sm">Lihat syarat dan dokumen yang perlu disiapkan sebelum mendaftar.</p>
            </div>
            <a href="<?= base_url('Pengembang/syarat') ?>" class="w-full sm:w-auto shrink-0 bg-[#d6fb00] hover:bg-[#c2e600] text-[#0a1a1f] font-extrabold text-xs uppercase tracking-wider px-6 py-3.5 rounded-xl transition-all duration-300 text-center">
                Lihat Syarat Pendaftaran
            </a>
        </div>

    </div>
</section>
