<section class="w-full bg-[#0a1a1f] py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden" x-data="{ openModal: false }">
    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-[#d6fb00]/5 blur-[120px] rounded-full pointer-events-none"></div>

    <div class="max-w-7xl mx-auto relative z-10">
        
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-12 border-b border-[#d6fb00]/20 pb-8">
            <div class="space-y-1">
                <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tighter font-jakarta">
                    Ruang Diskusi <span class="text-[#d6fb00]">Komunitas</span>
                </h2>
                <p class="text-zinc-400 text-xs sm:text-sm leading-relaxed">
                    Saling berbagi informasi, kendala sarana prasarana, dan solusi permukiman di wilayah Jawa Tengah.
                </p>
            </div>
            <div class="shrink-0">
                <button @click="openModal = true" class="w-full sm:w-auto bg-[#d6fb00] hover:bg-[#d6fb00] text-black font-bold text-xs px-5 py-3 rounded-xl transition-all flex items-center justify-center gap-2 shadow-lg shadow-[#d6fb00]/10">
                    <i class="fa-solid fa-plus"></i> Buat Diskusi Baru
                </button>
            </div>
        </div>

        <div class="space-y-4">
            <?php if(!empty($diskusi)): foreach($diskusi as $row): ?>
            <div class="bg-[#0f2a30] border border-[#d6fb00]/20 hover:border-[#d6fb00]/15 p-5 rounded-2xl flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 transition-all duration-300">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold tracking-wide uppercase bg-[#d6fb00]/5 text-[#d6fb00] border border-[#d6fb00]/20">
                            <?= $row['kategori']; ?>
                        </span>
                        <span class="text-[11px] text-zinc-500">
                            Oleh <b class="text-zinc-300"><?= htmlspecialchars($row['nama_user']); ?></b> • <?= date('d M Y', strtotime($row['created_at'])); ?>
                        </span>
                    </div>
                    <h3 class="text-white font-bold text-base hover:text-[#d6fb00] transition-colors">
                        <a href="<?= base_url('Umum/detail/'.$row['id_diskusi']); ?>"><?= htmlspecialchars($row['judul_topik']); ?></a>
                    </h3>
                    <p class="text-zinc-400 text-xs max-w-4xl line-clamp-2 leading-relaxed"><?= htmlspecialchars($row['isi_diskusi']); ?></p>
                </div>
                
                <div class="flex items-center gap-4 shrink-0 w-full sm:w-auto justify-between sm:justify-end border-t sm:border-t-0 pt-3 sm:pt-0 border-[#d6fb00]/20">
                    <div class="flex items-center gap-1.5 text-zinc-500 text-xs bg-[#d6fb00]/5 px-3 py-1.5 rounded-xl">
                        <i class="fa-regular fa-comment-dots text-[#d6fb00]"></i>
                        <span><?= $row['total_balasan']; ?> Balasan</span>
                    </div>
                    <a href="<?= base_url('Umum/detail/'.$row['id_diskusi']); ?>" class="text-zinc-400 hover:text-white text-xs font-semibold bg-[#d6fb00]/5 hover:bg-[#d6fb00]/8 p-2.5 rounded-xl transition-all">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; else: ?>
            <div class="bg-[#0f2a30] border border-dashed border-[#d6fb00]/20 p-12 rounded-2xl text-center text-zinc-500 text-sm">
                <i class="fa-regular fa-folder-open text-2xl text-zinc-600 block mb-3"></i>
                Belum ada topik diskusi yang dibuat. Mulai diskusi pertama sekarang!
            </div>
            <?php endif; ?>
        </div>

        <div class="flex justify-center mt-12">
            <a href="<?= base_url('Index/Umum') ?>" class="group flex items-center gap-2.5 bg-[#d6fb00]/5 hover:bg-[#d6fb00]/8 border border-[#d6fb00]/20 hover:border-[#d6fb00]/20 px-6 py-3 rounded-xl text-xs font-semibold text-zinc-400 hover:text-white transition-all duration-300 shadow-xl backdrop-blur-md">
                <i class="fa-solid fa-arrow-left transition-transform group-hover:-translate-x-1"></i>
                <span>Kembali ke Beranda Utama</span>
            </a>
        </div>

    </div>

    <div x-show="openModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" x-transition x-cloak>
        <div class="bg-[#0f2a30] border border-[#d6fb00]/20 max-w-lg w-full rounded-2xl p-6 space-y-4" @click.away="openModal = false">
            <div class="flex justify-between items-center border-b border-[#d6fb00]/20 pb-3">
                <h3 class="text-white font-bold text-base flex items-center gap-2">
                    <i class="fa-solid fa-pen-to-square text-[#d6fb00]"></i> Mulai Diskusi Baru
                </h3>
                <button @click="openModal = false" class="text-zinc-500 hover:text-white transition-colors">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            
            <form action="<?= base_url('Umum/tambah_aksi') ?>" method="POST" class="space-y-4 text-xs">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-zinc-400 mb-1.5">Nama Anda</label>
                        <input type="text" name="nama_user" required class="w-full bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl px-3 py-2.5 text-white outline-none focus:border-[#d6fb00]/40">
                    </div>
                    <div>
                        <label class="block text-zinc-400 mb-1.5">Email</label>
                        <input type="email" name="email_user" required class="w-full bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl px-3 py-2.5 text-white outline-none focus:border-[#d6fb00]/40">
                    </div>
                </div>
                <div>
                    <label class="block text-zinc-400 mb-1.5">Kategori Permasalahan</label>
                    <select name="kategori" class="w-full bg-[#0a1a1f] border border-[#d6fb00]/20 rounded-xl px-3 py-2.5 text-zinc-300 outline-none focus:border-[#d6fb00]/40">
                        <option value="RTLH">RTLH (Rumah Tidak Layak Huni)</option>
                        <option value="Prasarana Umum">Prasarana Umum</option>
                        <option value="Sengketa Lahan">Sengketa Lahan</option>
                        <option value="Rumah Subsidi">Rumah Subsidi</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div>
                    <label class="block text-zinc-400 mb-1.5">Judul Topik</label>
                    <input type="text" name="judul_topik" required class="w-full bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl px-3 py-2.5 text-white outline-none focus:border-[#d6fb00]/40" placeholder="Contoh: Kendala verifikasi nomor berkas RTLH">
                </div>
                <div>
                    <label class="block text-zinc-400 mb-1.5">Deskripsi Lengkap Masalah</label>
                    <textarea name="isi_diskusi" rows="4" required class="w-full bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl px-3 py-2.5 text-white outline-none focus:border-[#d6fb00]/40 placeholder-[#5a7a80]" placeholder="Ceritakan secara detail kronologi kendala atau informasi yang ingin Anda tanyakan..."></textarea>
                </div>
                <button type="submit" class="w-full bg-[#d6fb00] hover:bg-[#d6fb00] text-black font-black py-3 rounded-xl transition-all uppercase tracking-wider">Kirim ke Forum</button>
            </form>
        </div>
    </div>
</section>