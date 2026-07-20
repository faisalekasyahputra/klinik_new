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
                            <span class="text-[#d6fb00]">Syarat & Ketentuan</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <div class="text-left mb-10 space-y-4 max-w-2xl">
            <span class="text-[#d6fb00] font-extrabold text-[11px] uppercase tracking-widest block">SRP2</span>
            <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tighter font-jakarta">
                Syarat Pendaftaran <span class="text-[#d6fb00]">Sertifikasi Pengembang</span>
            </h2>
        </div>

        <!-- Alur Proses -->
        <div class="rounded-[2rem] p-6 sm:p-8 mb-6" style="background-color: var(--bg-card); border: 1px solid rgba(255,255,255,0.08);">
            <h3 class="text-white font-bold text-lg mb-5">Alur Proses Pendaftaran</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="flex sm:flex-col items-center sm:text-center gap-3 p-4 rounded-xl bg-black/20 border border-zinc-800">
                    <span class="shrink-0 w-9 h-9 rounded-lg bg-[#d6fb00]/10 border border-[#d6fb00]/20 text-[#d6fb00] font-black text-sm flex items-center justify-center">1</span>
                    <div>
                        <p class="text-white font-bold text-sm">Daftar Online</p>
                        <p class="text-zinc-500 text-xs mt-0.5">Isi formulir & unggah dokumen persyaratan.</p>
                    </div>
                </div>
                <div class="flex sm:flex-col items-center sm:text-center gap-3 p-4 rounded-xl bg-black/20 border border-zinc-800">
                    <span class="shrink-0 w-9 h-9 rounded-lg bg-[#d6fb00]/10 border border-[#d6fb00]/20 text-[#d6fb00] font-black text-sm flex items-center justify-center">2</span>
                    <div>
                        <p class="text-white font-bold text-sm">Verifikasi Admin</p>
                        <p class="text-zinc-500 text-xs mt-0.5">Berkas diperiksa oleh Admin Disperakim Jateng.</p>
                    </div>
                </div>
                <div class="flex sm:flex-col items-center sm:text-center gap-3 p-4 rounded-xl bg-black/20 border border-zinc-800">
                    <span class="shrink-0 w-9 h-9 rounded-lg bg-[#d6fb00]/10 border border-[#d6fb00]/20 text-[#d6fb00] font-black text-sm flex items-center justify-center">3</span>
                    <div>
                        <p class="text-white font-bold text-sm">Lolos & Bersertifikat</p>
                        <p class="text-zinc-500 text-xs mt-0.5">Terdaftar sebagai pengembang bersertifikat SRP2.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dokumen Wajib -->
        <div class="rounded-[2rem] p-6 sm:p-8 mb-8" style="background-color: var(--bg-card); border: 1px solid rgba(255,255,255,0.08);">
            <h3 class="text-white font-bold text-lg mb-1">Dokumen yang Wajib Disiapkan</h3>
            <p class="text-zinc-500 text-xs mb-6">Siapkan scan/foto dokumen berikut sebelum mengisi formulir pendaftaran.</p>

            <div class="space-y-3">
                <div class="flex items-start gap-4 p-4 rounded-xl bg-black/20 border border-zinc-800">
                    <div class="shrink-0 w-10 h-10 rounded-xl bg-[#d6fb00]/10 border border-[#d6fb00]/20 flex items-center justify-center text-[#d6fb00]">
                        <i class="fa-solid fa-file-invoice"></i>
                    </div>
                    <div>
                        <p class="text-white font-bold text-sm">Scan KTP Peserta</p>
                        <p class="text-zinc-500 text-xs mt-0.5">Format PDF/JPG/PNG. <span class="text-[#d6fb00] font-semibold">Wajib.</span></p>
                    </div>
                </div>
                <div class="flex items-start gap-4 p-4 rounded-xl bg-black/20 border border-zinc-800">
                    <div class="shrink-0 w-10 h-10 rounded-xl bg-[#d6fb00]/10 border border-[#d6fb00]/20 flex items-center justify-center text-[#d6fb00]">
                        <i class="fa-solid fa-address-card"></i>
                    </div>
                    <div>
                        <p class="text-white font-bold text-sm">Scan Bukti KTA Asosiasi Aktif</p>
                        <p class="text-zinc-500 text-xs mt-0.5">Format PDF/JPG/PNG. <span class="text-[#d6fb00] font-semibold">Wajib.</span></p>
                    </div>
                </div>
                <div class="flex items-start gap-4 p-4 rounded-xl bg-black/20 border border-zinc-800">
                    <div class="shrink-0 w-10 h-10 rounded-xl bg-[#d6fb00]/10 border border-[#d6fb00]/20 flex items-center justify-center text-red-400">
                        <i class="fa-solid fa-file-pdf"></i>
                    </div>
                    <div>
                        <p class="text-white font-bold text-sm">Scan NIB (Nomor Induk Berusaha)</p>
                        <p class="text-zinc-500 text-xs mt-0.5">Format PDF. <span class="text-[#d6fb00] font-semibold">Wajib.</span></p>
                    </div>
                </div>
                <div class="flex items-start gap-4 p-4 rounded-xl bg-black/20 border border-zinc-800">
                    <div class="shrink-0 w-10 h-10 rounded-xl bg-zinc-700/50 border border-zinc-600 flex items-center justify-center text-zinc-300">
                        <i class="fa-solid fa-file-signature"></i>
                    </div>
                    <div>
                        <p class="text-white font-bold text-sm">Surat Tugas dari Perusahaan</p>
                        <p class="text-zinc-500 text-xs mt-0.5">Format PDF. <span class="text-zinc-400 font-semibold italic">Wajib hanya jika jabatan peserta bukan Direktur Utama/Pemilik.</span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="text-center">
            <a href="<?= base_url('Pengembang/formulir') ?>" class="inline-block w-full sm:w-auto bg-[#d6fb00] hover:bg-[#c2e600] text-[#0a1a1f] font-extrabold text-sm uppercase tracking-wider px-10 py-4 rounded-xl transition-all duration-300 shadow-lg shadow-[#d6fb00]/10 hover:shadow-[#d6fb00]/20 active:scale-[0.98]">
                Daftar Sekarang
            </a>
        </div>

    </div>
</section>
