<section class="w-full pt-24 pb-16 px-4 sm:px-6 lg:px-8 relative min-h-screen font-outfit">
    <!-- Background Ornaments -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-[#d6fb00]/5 blur-[120px] rounded-full"></div>
    </div>

    <div class="max-w-4xl mx-auto relative z-10">
        
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
                            <a href="<?= base_url('umum') ?>" class="hover:text-[#d6fb00] transition-colors">Layanan Umum</a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fa-solid fa-chevron-right text-[8px] mx-2"></i>
                            <a href="<?= base_url('Umum/pengembang') ?>" class="hover:text-[#d6fb00] transition-colors">Informasi Pengembang</a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fa-solid fa-chevron-right text-[8px] mx-2"></i>
                            <span class="text-[#d6fb00]">Detail Pengembang</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <!-- Flash Data -->
        <?php if ($this->session->flashdata('success')): ?>
            <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-500 font-medium">
                <i class="fa-solid fa-circle-check mr-2"></i><?= $this->session->flashdata('success') ?>
            </div>
        <?php endif; ?>
        <?php if ($this->session->flashdata('error')): ?>
            <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20 text-red-500 font-medium">
                <i class="fa-solid fa-circle-xmark mr-2"></i><?= $this->session->flashdata('error') ?>
            </div>
        <?php endif; ?>

        <div class="bg-[#0f2a30]/80 backdrop-blur-xl rounded-[2rem] shadow-2xl shadow-[#d6fb00]/5 border border-[#d6fb00]/20 p-8 md:p-12">
            
            <div class="text-center max-w-2xl mx-auto mb-10 space-y-4">
                <div class="w-20 h-20 mx-auto rounded-full bg-[#d6fb00]/10 flex items-center justify-center border border-[#d6fb00]/20 text-[#d6fb00] mb-4">
                    <i class="fa-solid fa-industry text-3xl"></i>
                </div>
                <h2 class="text-3xl md:text-4xl font-black text-white tracking-tight">
                    <?= htmlspecialchars($nama_pengembang) ?>
                </h2>
                <p class="text-[#d6fb00] font-semibold tracking-wider text-sm uppercase">Detail Pengembang Perumahan</p>
            </div>

            <div class="bg-[#0a1a1f] rounded-2xl p-6 md:p-8 border border-[#d6fb00]/10">
                <?php if (!empty($local_data)): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <p class="text-zinc-500 text-xs font-bold uppercase tracking-widest mb-1">Nomor Induk Berusaha (NIB)</p>
                            <p class="text-white text-lg font-semibold flex items-center gap-2">
                                <i class="fa-solid fa-id-card text-[#d6fb00]/60"></i> <?= htmlspecialchars($local_data['nib']) ?>
                            </p>
                        </div>
                        <div>
                            <p class="text-zinc-500 text-xs font-bold uppercase tracking-widest mb-1">Asosiasi</p>
                            <p class="text-white text-lg font-semibold flex items-center gap-2">
                                <i class="fa-solid fa-users text-[#d6fb00]/60"></i> <?= htmlspecialchars($local_data['asosiasi']) ?>
                            </p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-zinc-500 text-xs font-bold uppercase tracking-widest mb-1">Alamat Perusahaan</p>
                            <p class="text-white text-lg font-medium flex items-start gap-2">
                                <i class="fa-solid fa-location-dot text-[#d6fb00]/60 mt-1"></i> <?= htmlspecialchars($local_data['alamat_kantor']) ?>
                            </p>
                        </div>
                    </div>

                    <div class="mt-10 flex flex-col sm:flex-row justify-center gap-4 border-t border-[#d6fb00]/10 pt-8">
                        <a href="<?= base_url('Umum/download_sertifikat/' . urlencode($nama_pengembang)) ?>" class="inline-flex items-center justify-center gap-2 bg-[#d6fb00] hover:bg-[#d6fb00]/90 text-black px-8 py-4 rounded-xl font-black transition-all duration-300 shadow-[0_0_20px_rgba(214,251,0,0.2)] hover:shadow-[0_0_30px_rgba(214,251,0,0.4)] hover:-translate-y-1">
                            <i class="fa-solid fa-certificate text-xl"></i> <span>Download Sertifikat</span>
                        </a>
                        <a href="<?= base_url('Umum/pengembang') ?>" class="inline-flex items-center justify-center gap-2 bg-[#0f2a30] hover:bg-[#d6fb00]/10 text-white px-8 py-4 rounded-xl font-bold transition-all duration-300 border border-[#d6fb00]/20 hover:border-[#d6fb00]/50">
                            <i class="fa-solid fa-arrow-left"></i> <span>Kembali</span>
                        </a>
                    </div>

                <?php else: ?>
                    <div class="text-center py-10">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-red-500/10 text-red-500 mb-4 border border-red-500/20">
                            <i class="fa-solid fa-triangle-exclamation text-2xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-white mb-2">Data Belum Terdaftar</h3>
                        <p class="text-zinc-400 max-w-md mx-auto mb-8">Data pengembang <span class="text-white font-semibold"><?= htmlspecialchars($nama_pengembang) ?></span> belum terdaftar di Sistem Registrasi Pengembang (SRP2).</p>
                        <a href="<?= base_url('Umum/pengembang') ?>" class="inline-flex items-center justify-center gap-2 bg-[#0f2a30] hover:bg-white/10 text-white px-6 py-3 rounded-xl font-bold transition-all duration-300 border border-white/20">
                            <i class="fa-solid fa-arrow-left"></i> <span>Kembali ke Daftar</span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</section>
