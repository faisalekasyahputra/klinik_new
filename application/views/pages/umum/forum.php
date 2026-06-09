<section class="w-full pt-24 pb-16 px-4 sm:px-6 lg:px-8 relative min-h-screen overflow-hidden font-outfit" x-data="{ openModal: false }">
    <!-- Background Ornaments -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-[#d6fb00]/5 blur-[120px] rounded-full pointer-events-none"></div>
    </div>

    <div class="max-w-7xl mx-auto relative z-10">
        
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
                            <span class="text-[#d6fb00]">Forum Diskusi</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8 border-b border-[#d6fb00]/20 pb-8">
            <div class="space-y-1">
                <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tighter font-jakarta">
                    Ruang Diskusi <span class="text-[#d6fb00]">Komunitas</span>
                </h2>
                <p class="text-zinc-400 text-xs sm:text-sm leading-relaxed">
                    Saling berbagi informasi, kendala sarana prasarana, dan solusi permukiman di wilayah Jawa Tengah.
                </p>
            </div>
            <div class="shrink-0">
                <?php if ($this->session->userdata('is_logged') === TRUE): ?>
                <button @click="openModal = true" class="w-full sm:w-auto bg-[#d6fb00] hover:bg-[#c5ea00] text-black font-bold text-xs px-5 py-3 rounded-xl transition-all flex items-center justify-center gap-2 shadow-lg shadow-[#d6fb00]/10">
                    <i class="fa-solid fa-plus"></i> Buat Diskusi Baru
                </button>
                <?php else: ?>
                <a href="<?= base_url('Auth/login') ?>" class="w-full sm:w-auto bg-[#d6fb00]/10 hover:bg-[#d6fb00]/20 border border-[#d6fb00]/30 text-[#d6fb00] font-bold text-xs px-5 py-3 rounded-xl transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-right-to-bracket"></i> Login untuk Berdiskusi
                </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if ($this->session->flashdata('error')): ?>
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-5 py-3 rounded-xl text-xs mb-6 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= $this->session->flashdata('error') ?>
        </div>
        <?php endif; ?>
        <?php if ($this->session->flashdata('success')): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 px-5 py-3 rounded-xl text-xs mb-6 flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i>
            <?= $this->session->flashdata('success') ?>
        </div>
        <?php endif; ?>

        <!-- Search & Filter Bar -->
        <form method="GET" action="<?= base_url('Umum/forum') ?>" class="flex flex-col sm:flex-row gap-3 mb-8">
            <div class="flex-grow">
                <input type="text" name="q" value="<?= htmlspecialchars($search ?? '') ?>" 
                       placeholder="Cari topik diskusi..." 
                       class="w-full bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl px-4 py-3 text-white text-xs outline-none focus:border-[#d6fb00]/40 placeholder-[#5a7a80] transition-colors">
            </div>
            <select name="kategori" class="bg-[#0a1a1f] border border-[#d6fb00]/20 rounded-xl px-4 py-3 text-zinc-300 text-xs outline-none w-full sm:w-48">
                <option value="">Semua Kategori</option>
                <option value="RTLH" <?= ($kategori_aktif ?? '') === 'RTLH' ? 'selected' : '' ?>>RTLH</option>
                <option value="Prasarana Umum" <?= ($kategori_aktif ?? '') === 'Prasarana Umum' ? 'selected' : '' ?>>Prasarana Umum</option>
                <option value="Sengketa Lahan" <?= ($kategori_aktif ?? '') === 'Sengketa Lahan' ? 'selected' : '' ?>>Sengketa Lahan</option>
                <option value="Rumah Subsidi" <?= ($kategori_aktif ?? '') === 'Rumah Subsidi' ? 'selected' : '' ?>>Rumah Subsidi</option>
                <option value="Lainnya" <?= ($kategori_aktif ?? '') === 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
            </select>
            <button type="submit" class="bg-[#d6fb00] hover:bg-[#c5ea00] text-black font-bold text-xs px-5 py-3 rounded-xl flex items-center gap-2 shrink-0 transition-all">
                <i class="fa-solid fa-magnifying-glass"></i> Cari
            </button>
        </form>

        <!-- Diskusi List -->
        <div class="space-y-4">
            <?php if(!empty($diskusi)): foreach($diskusi as $row): ?>
            <div class="bg-[#0f2a30] border border-[#d6fb00]/20 hover:border-[#d6fb00]/15 p-5 rounded-2xl flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 transition-all duration-300">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold tracking-wide uppercase bg-[#d6fb00]/5 text-[#d6fb00] border border-[#d6fb00]/20">
                            <?= $row['kategori']; ?>
                        </span>
                        <?php if (isset($row['status']) && $row['status'] === 'resolved'): ?>
                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold tracking-wide uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            <i class="fa-solid fa-circle-check mr-1"></i>Selesai
                        </span>
                        <?php elseif (isset($row['status']) && $row['status'] === 'closed'): ?>
                        <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold tracking-wide uppercase bg-red-500/10 text-red-400 border border-red-500/20">
                            <i class="fa-solid fa-lock mr-1"></i>Ditutup
                        </span>
                        <?php endif; ?>
                        <span class="text-[11px] text-zinc-500">
                            Oleh <b class="text-zinc-300"><?= htmlspecialchars($row['nama_user']); ?></b> • <?= date('d M Y', strtotime($row['created_at'])); ?>
                        </span>
                    </div>
                    <h3 class="text-white font-bold text-base hover:text-[#d6fb00] transition-colors">
                        <a href="<?= base_url('Umum/detail/'.$row['id_diskusi']); ?>"><?= htmlspecialchars($row['judul_topik']); ?></a>
                    </h3>
                    <p class="text-zinc-400 text-xs max-w-4xl line-clamp-2 leading-relaxed"><?= htmlspecialchars($row['isi_diskusi']); ?></p>
                </div>
                
                <div class="flex items-center gap-2 shrink-0 w-full sm:w-auto justify-between sm:justify-end border-t sm:border-t-0 pt-3 sm:pt-0 border-[#d6fb00]/20">
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-1.5 text-zinc-500 text-[11px] bg-[#d6fb00]/5 px-2.5 py-1.5 rounded-xl">
                            <i class="fa-regular fa-eye text-zinc-600"></i>
                            <span><?= $row['view_count'] ?? 0 ?></span>
                        </div>
                        <div class="flex items-center gap-1.5 text-zinc-500 text-[11px] bg-[#d6fb00]/5 px-2.5 py-1.5 rounded-xl">
                            <i class="fa-regular fa-heart text-pink-500/60"></i>
                            <span><?= $row['like_count'] ?? 0 ?></span>
                        </div>
                        <div class="flex items-center gap-1.5 text-zinc-500 text-[11px] bg-[#d6fb00]/5 px-2.5 py-1.5 rounded-xl">
                            <i class="fa-regular fa-comment-dots text-[#d6fb00]"></i>
                            <span><?= $row['total_balasan']; ?></span>
                        </div>
                    </div>
                    <a href="<?= base_url('Umum/detail/'.$row['id_diskusi']); ?>" class="text-zinc-400 hover:text-white text-xs font-semibold bg-[#d6fb00]/5 hover:bg-[#d6fb00]/8 p-2.5 rounded-xl transition-all">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>
            </div>
            <?php endforeach; else: ?>
            <div class="bg-[#0f2a30] border border-dashed border-[#d6fb00]/20 p-12 rounded-2xl text-center text-zinc-500 text-sm">
                <i class="fa-regular fa-folder-open text-2xl text-zinc-600 block mb-3"></i>
                <?php if (!empty($search ?? '') || !empty($kategori_aktif ?? '')): ?>
                    Tidak ditemukan topik diskusi yang cocok dengan pencarian Anda.
                <?php else: ?>
                    Belum ada topik diskusi yang dibuat. Mulai diskusi pertama sekarang!
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="flex justify-center mt-12">
            <a href="<?= base_url('umum') ?>" class="group flex items-center gap-2.5 bg-[#d6fb00]/5 hover:bg-[#d6fb00]/8 border border-[#d6fb00]/20 hover:border-[#d6fb00]/20 px-6 py-3 rounded-xl text-xs font-semibold text-zinc-400 hover:text-white transition-all duration-300 shadow-xl backdrop-blur-md">
                <i class="fa-solid fa-arrow-left transition-transform group-hover:-translate-x-1"></i>
                <span>Kembali ke Beranda Utama</span>
            </a>
        </div>

    </div>

    <!-- Modal: Buat Diskusi Baru (hanya muncul jika login) -->
    <?php if ($this->session->userdata('is_logged') === TRUE): ?>
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
            
            <!-- Info user yang sedang login -->
            <div class="flex items-center gap-3 bg-[#d6fb00]/5 border border-[#d6fb00]/10 rounded-xl px-4 py-2.5">
                <div class="w-8 h-8 rounded-lg bg-[#d6fb00]/20 flex items-center justify-center text-[#d6fb00]">
                    <i class="fa-solid fa-user text-xs"></i>
                </div>
                <div>
                    <p class="text-white text-xs font-bold"><?= htmlspecialchars($this->session->userdata('username') ?: $this->session->userdata('name') ?: 'Pengguna') ?></p>
                    <p class="text-zinc-500 text-[10px]"><?= htmlspecialchars($this->session->userdata('email') ?: '') ?></p>
                </div>
            </div>

            <form action="<?= base_url('Umum/tambah_aksi') ?>" method="POST" class="space-y-4 text-xs">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
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
                    <input type="text" name="judul_topik" required minlength="10" maxlength="200" class="w-full bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl px-3 py-2.5 text-white outline-none focus:border-[#d6fb00]/40" placeholder="Contoh: Kendala verifikasi nomor berkas RTLH">
                </div>
                <div>
                    <label class="block text-zinc-400 mb-1.5">Deskripsi Lengkap Masalah</label>
                    <textarea name="isi_diskusi" rows="4" required minlength="20" class="w-full bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl px-3 py-2.5 text-white outline-none focus:border-[#d6fb00]/40 placeholder-[#5a7a80]" placeholder="Ceritakan secara detail kronologi kendala atau informasi yang ingin Anda tanyakan..."></textarea>
                </div>
                <button type="submit" class="w-full bg-[#d6fb00] hover:bg-[#c5ea00] text-black font-black py-3 rounded-xl transition-all uppercase tracking-wider">Kirim ke Forum</button>
            </form>
        </div>
    </div>
    <?php endif; ?>
</section>
