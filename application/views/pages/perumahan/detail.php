<section class="w-full bg-[#0a1a1f] py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-[#d6fb00]/5 blur-[120px] rounded-full pointer-events-none"></div>

    <div class="max-w-4xl mx-auto relative z-10">
        
        <a href="<?= base_url('Umum/forum') ?>" class="inline-flex items-center gap-2.5 text-xs font-semibold text-zinc-400 hover:text-[#d6fb00] mb-8 transition-colors group">
            <i class="fa-solid fa-arrow-left transition-transform group-hover:-translate-x-1"></i>
            <span>Kembali ke Ruang Diskusi</span>
        </a>

        <div class="bg-[#0f2a30] border border-[#d6fb00]/20 rounded-2xl p-6 sm:p-8 mb-10 space-y-5 shadow-xl">
            <div class="flex flex-wrap items-center gap-3 text-xs text-zinc-500">
                <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold uppercase bg-[#d6fb00]/10 text-[#d6fb00] border border-[#d6fb00]/20 tracking-wider">
                    <?= $topik['kategori'] ?>
                </span>
                <span class="flex items-center gap-1">
                    Dimulai oleh <b class="text-zinc-300 ml-1"><?= htmlspecialchars($topik['nama_user']) ?></b>
                </span>
                <span>•</span>
                <span class="text-zinc-500"><?= date('d M Y H:i', strtotime($topik['created_at'])) ?> WIB</span>
            </div>
            
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight leading-snug">
                <?= htmlspecialchars($topik['judul_topik']) ?>
            </h1>
            
            <div class="border-t border-[#d6fb00]/20 pt-4">
                <p class="text-zinc-300 text-sm leading-relaxed whitespace-pre-line font-light">
                    <?= htmlspecialchars($topik['isi_diskusi']) ?>
                </p>
            </div>
        </div>

        <div class="space-y-6">
            <div class="flex items-center gap-2 border-b border-[#d6fb00]/20 pb-3">
                <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-widest font-jakarta">Tanggapan Komunitas</h3>
                <span class="bg-[#d6fb00]/5 text-zinc-400 text-[10px] font-bold px-2 py-0.5 rounded-full">
                    <?= count($komentar) ?>
                </span>
            </div>
            
            <div class="space-y-4">
                <?php if(!empty($komentar)): foreach($komentar as $kom): ?>
                <div class="bg-[#0f2a30]/50 border <?= $kom['role'] == 'Petugas Disperakim' ? 'border-[#d6fb00]/20 bg-[#f5ffd9]0/[0.02]' : 'border-[#d6fb00]/20' ?> p-5 rounded-2xl space-y-3 transition-all duration-300">
                    <div class="flex justify-between items-start gap-4">
                        <div class="flex items-center gap-2.5 text-xs">
                            <div class="w-7 h-7 rounded-lg <?= $kom['role'] == 'Petugas Disperakim' ? 'bg-[#d6fb00]/10 text-[#d6fb00]' : 'bg-[#d6fb00]/5 text-zinc-400' ?> flex items-center justify-center shrink-0 border border-[#d6fb00]/20">
                                <i class="fa-solid <?= $kom['role'] == 'Petugas Disperakim' ? 'fa-user-shield' : 'fa-user' ?> text-xs"></i>
                            </div>
                            <span class="font-bold text-zinc-200 flex items-center gap-2">
                                <?= htmlspecialchars($kom['nama_komentator']) ?>
                                <?php if($kom['role'] == 'Petugas Disperakim'): ?>
                                    <span class="bg-[#d6fb00] text-black font-black text-[8px] px-1.5 py-0.5 rounded tracking-wider uppercase scale-90">Staff Ahli</span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <span class="text-[10px] text-zinc-500 bg-[#d6fb00]/5 px-2 py-1 rounded-md"><?= date('d M Y H:i', strtotime($kom['created_at'])) ?></span>
                    </div>
                    <p class="text-zinc-400 text-xs sm:text-sm leading-relaxed pl-9 font-light">
                        <?= htmlspecialchars($kom['isi_komentar']) ?>
                    </p>
                </div>
                <?php endforeach; else: ?>
                <div class="bg-[#0f2a30]/30 border border-dashed border-[#d6fb00]/20 p-8 rounded-2xl text-center text-zinc-500 text-xs italic">
                    <i class="fa-regular fa-comments text-xl block mb-2 text-zinc-600"></i>
                    Belum ada tanggapan struktural. Berikan masukan atau solusi pertama Anda di bawah ini.
                </div>
                <?php endif; ?>
            </div>

            <form action="<?= base_url('Umum/balas_aksi') ?>" method="POST" class="bg-[#0f2a30] border border-[#d6fb00]/20 rounded-2xl p-6 mt-8 space-y-4 text-xs shadow-xl relative z-10">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                <input type="hidden" name="id_diskusi" value="<?= $topik['id_diskusi'] ?>">
                
                <h4 class="text-white font-bold text-sm tracking-tight flex items-center gap-2">
                    <i class="fa-solid fa-reply text-[#d6fb00] text-xs"></i> Kirim Tanggapan Resmi
                </h4>
                
                <div>
                    <div>
                        <label class="block text-zinc-400 mb-1.5 font-medium">Nama / Identitas Pengirim</label>
                        <input type="text" name="nama_komentator" required class="w-full bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl px-3 py-2.5 text-white outline-none focus:border-[#d6fb00]/40 transition-colors">
                    </div>
                </div>
                
                <div>
                    <label class="block text-zinc-400 mb-1.5 font-medium">Isi Masukan / Tanggapan</label>
                    <textarea name="isi_komentar" rows="4" required class="w-full bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl px-3 py-2.5 text-white outline-none focus:border-[#d6fb00]/40 placeholder-[#5a7a80] transition-colors leading-relaxed" placeholder="Tulis regulasi struktural, solusi teknis atau tanggapan informatif terkait masalah di atas..."></textarea>
                </div>
                
                <div class="flex justify-end pt-2">
                    <button type="submit" class="w-full sm:w-auto bg-[#d6fb00] hover:bg-[#d6fb00] text-black font-black px-6 py-3 rounded-xl transition-all uppercase tracking-wider text-[11px]">
                        Submit Tanggapan
                    </button>
                </div>
            </form>
        </div>

    </div>
</section>