<section class="forum-detail-page theme-light w-full pt-4 pb-10 px-2 sm:px-4 sm:pt-6 sm:pb-16 relative min-h-screen font-outfit overflow-hidden">
    <!-- Background Ornaments -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-[#00a3b5]/10 blur-[120px] rounded-full pointer-events-none"></div>
    </div>

    <div class="max-w-4xl mx-auto relative z-10">

        <style>
            .forum-detail-page .bg-\[\#0f2a30\],
            .forum-detail-page .bg-\[\#0f2a30\]\/50 {
                background: var(--portal-bg-card) !important;
            }
            .forum-detail-page .bg-\[\#0f2a30\]\/30 {
                background: var(--portal-bg) !important;
            }
            .forum-detail-page [class*="border-[#d6fb00]"] {
                border-color: var(--portal-border) !important;
            }
            .forum-detail-page [class*="bg-[#d6fb00]/5"] {
                background: rgba(0, 163, 181, .06) !important;
            }
            .forum-detail-page [class*="bg-[#d6fb00]/10"] {
                background: rgba(0, 163, 181, .1) !important;
            }
            .forum-detail-page [class*="text-[#d6fb00]"],
            .forum-detail-page [class*="text-[#ecffb6]"] {
                color: var(--portal-brand) !important;
            }
            .forum-detail-page .text-zinc-300,
            .forum-detail-page .text-zinc-400 {
                color: var(--portal-text-muted) !important;
            }
            .forum-detail-page .text-zinc-200,
            .forum-detail-page .text-white {
                color: var(--portal-text) !important;
            }
            .forum-detail-page .thr-node > .flex > .flex-1 > div {
                box-shadow: var(--portal-shadow);
            }
        </style>
        
        <a href="<?= base_url('Umum/forum') ?>" data-no-page-transition class="inline-flex items-center gap-2.5 text-xs font-semibold text-zinc-400 hover:text-[#00a3b5] mb-8 transition-colors group">
            <i class="fa-solid fa-arrow-left transition-transform group-hover:-translate-x-1"></i>
            <span>Kembali ke Konsultasi Terjadwal</span>
        </a>

        <!-- Flash Messages -->
        <?php if ($this->session->flashdata('error')): ?>
        <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-5 py-3 rounded-xl text-xs mb-6 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= $this->session->flashdata('error') ?>
        </div>
        <?php endif; ?>

        <!-- Topik Utama -->
        <div class="bg-[#0f2a30] border border-[#00545f]/70 rounded-2xl p-6 sm:p-8 mb-10 space-y-5 shadow-xl">
            <div class="flex flex-wrap items-center gap-3 text-xs text-zinc-500">
                <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold uppercase bg-[#00a3b5]/10 text-[#00a3b5] border border-[#00a3b5]/30 tracking-wider">
                    <?= $topik['kategori'] ?>
                </span>
                <?php if (isset($topik['status']) && $topik['status'] === 'resolved'): ?>
                <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                    <i class="fa-solid fa-circle-check mr-1"></i>Selesai
                </span>
                <?php elseif (isset($topik['status']) && $topik['status'] === 'closed'): ?>
                <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold uppercase bg-red-500/10 text-red-400 border border-red-500/20">
                    <i class="fa-solid fa-lock mr-1"></i>Ditutup
                </span>
                <?php endif; ?>
                <span class="flex items-center gap-1">
                    Dimulai oleh <b class="text-zinc-300 ml-1"><?= htmlspecialchars($topik['nama_user']) ?></b>
                </span>
                <span>•</span>
                <span class="text-zinc-500"><?= date('d M Y H:i', strtotime($topik['created_at'])) ?> WIB</span>
            </div>
            
            <h1 class="text-xl sm:text-2xl font-black text-white tracking-tight leading-snug">
                <?= htmlspecialchars($topik['judul_topik']) ?>
            </h1>
            
            <div class="border-t border-[#00545f]/70 pt-4">
                <p class="text-zinc-300 text-sm leading-relaxed whitespace-pre-line font-light">
                    <?= htmlspecialchars($topik['isi_diskusi']) ?>
                </p>
            </div>

            <!-- Engagement Stats -->
            <?php $is_logged = ($this->session->userdata('is_logged') === TRUE); ?>
            <?php $topik_liked = isset($user_likes['diskusi_' . $topik['id_diskusi']]); ?>
            <div class="flex items-center gap-4 mt-5 pt-4 border-t border-[#d6fb00]/10">
                <div class="flex items-center gap-1.5 text-zinc-500 text-[11px]">
                    <i class="fa-regular fa-eye"></i>
                    <span><?= $topik['view_count'] ?? 0 ?> dilihat</span>
                </div>
                <button id="like-diskusi-<?= $topik['id_diskusi'] ?>" 
                        onclick="toggleLike('diskusi', <?= $topik['id_diskusi'] ?>)" 
                        class="flex items-center gap-1.5 text-[11px] px-3 py-1.5 rounded-xl transition-all <?= $topik_liked ? 'bg-pink-500/10 text-pink-400 border border-pink-500/20' : 'bg-[#d6fb00]/5 text-zinc-500 hover:text-pink-400 hover:bg-pink-500/10' ?> <?= $is_logged ? 'cursor-pointer' : 'cursor-default opacity-60' ?>">
                    <i class="<?= $topik_liked ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                    <span id="like-count-diskusi-<?= $topik['id_diskusi'] ?>"><?= $topik['like_count'] ?? 0 ?></span>
                </button>
                <div class="flex items-center gap-1.5 text-zinc-500 text-[11px]">
                    <i class="fa-regular fa-comment-dots"></i>
                    <span><?= count($komentar) ?> tanggapan</span>
                </div>
            </div>

            <!-- Admin Controls -->
            <?php 
            $is_admin = ($this->session->userdata('is_logged') === TRUE && 
                         in_array($this->session->userdata('role'), ['admin', 'staff', 'Petugas Disperakim']));
            ?>
            <?php if ($is_admin): ?>
            <div class="flex flex-wrap gap-2 mt-4 pt-4 border-t border-[#d6fb00]/10">
                <?php if (($topik['status'] ?? 'open') !== 'resolved'): ?>
                <form method="POST" action="<?= base_url('Umum/update_status_diskusi') ?>" class="inline">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                    <input type="hidden" name="id_diskusi" value="<?= $topik['id_diskusi'] ?>">
                    <input type="hidden" name="status" value="resolved">
                    <button type="submit" class="text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-3 py-1.5 rounded-lg hover:bg-emerald-500/20 transition-all">
                        <i class="fa-solid fa-circle-check mr-1"></i> Tandai Selesai
                    </button>
                </form>
                <?php endif; ?>
                <?php if (($topik['status'] ?? 'open') !== 'closed'): ?>
                <form method="POST" action="<?= base_url('Umum/update_status_diskusi') ?>" class="inline">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                    <input type="hidden" name="id_diskusi" value="<?= $topik['id_diskusi'] ?>">
                    <input type="hidden" name="status" value="closed">
                    <button type="submit" class="text-[10px] font-bold bg-red-500/10 text-red-400 border border-red-500/20 px-3 py-1.5 rounded-lg hover:bg-red-500/20 transition-all">
                        <i class="fa-solid fa-lock mr-1"></i> Tutup Diskusi
                    </button>
                </form>
                <?php endif; ?>
                <form method="POST" action="<?= base_url('Umum/delete_diskusi') ?>" class="inline" onsubmit="return confirm('Yakin ingin menghapus diskusi ini?')">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                    <input type="hidden" name="id_diskusi" value="<?= $topik['id_diskusi'] ?>">
                    <button type="submit" class="text-[10px] font-bold bg-zinc-500/10 text-zinc-400 border border-zinc-500/20 px-3 py-1.5 rounded-lg hover:bg-red-500/20 hover:text-red-400 transition-all">
                        <i class="fa-solid fa-trash mr-1"></i> Hapus
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Thread Connector CSS -->
        <style>
            .thr-node { position: relative; }
            .thr-children { position: relative; }
            
            /* ── 1. STEM: from parent avatar down through card area only ── */
            .has-child > .flex > .shrink-0 {
                position: relative;
            }
            .has-child > .flex > .shrink-0::after {
                content: '';
                position: absolute;
                left: 15px;
                top: 34px;
                bottom: -10px;
                border-left: 1px dashed rgba(0, 163, 181, 0.35);
                z-index: 5;
                pointer-events: none;
            }
            @media (min-width: 640px) {
                .has-child > .flex > .shrink-0::after {
                    left: 17px;
                    top: 38px;
                }
            }
            
            /* ── 2. SIBLING CONTINUATION: between non-last direct children ── */
            .thr-children > .thr-node:not(:last-child)::after {
                content: '';
                position: absolute;
                left: -13px;
                top: 16px;
                bottom: -16px;
                border-left: 1px dashed rgba(0, 163, 181, 0.35);
                z-index: 5;
                pointer-events: none;
            }
            @media (min-width: 640px) {
                .thr-children > .thr-node:not(:last-child)::after {
                    left: -19px;
                    top: 18px;
                }
            }
            
            /* ── 3. L-BEND: curves from parent vertical line to child avatar ── */
            .thr-bend {
                position: absolute;
                left: -13px;
                top: 0;
                width: 15px;
                height: 16px;
                border-left: 1px dashed rgba(0, 163, 181, 0.35);
                border-bottom: 1px dashed rgba(0, 163, 181, 0.35);
                border-bottom-left-radius: 12px;
                z-index: 5;
                pointer-events: none;
            }
            @media (min-width: 640px) {
                .thr-bend {
                    left: -19px;
                    width: 21px;
                    height: 18px;
                }
            }
            
            /* Smooth scroll anchor highlight */
            .thr-node:target > .flex > .flex-1 > div {
                box-shadow: 0 0 0 2px rgba(214, 251, 0, 0.25);
                transition: box-shadow 0.3s ease;
            }
        </style>

        <!-- Tanggapan Konsultasi -->
        <div class="space-y-6">
            <div class="flex items-center gap-2 border-b border-[#00545f]/70 pb-3">
                <h3 class="text-xs font-bold text-zinc-400 uppercase tracking-widest font-jakarta">Tanggapan Konsultasi</h3>
                <span class="bg-[#00a3b5]/10 text-[#8aacb0] text-[10px] font-bold px-2 py-0.5 rounded-full">
                    <?= count($komentar) ?>
                </span>
            </div>
            
            <?php
            // === Build comment tree from flat array ===
            $by_id = [];
            foreach ($komentar as $k) {
                $k['children'] = [];
                $by_id[$k['id_komentar']] = $k;
            }
            $tree = [];
            foreach ($by_id as $id => &$k) {
                if (!empty($k['reply_to']) && isset($by_id[$k['reply_to']])) {
                    $by_id[$k['reply_to']]['children'][] = &$k;
                } else {
                    $tree[] = &$k;
                }
            }
            unset($k);

            // === Recursive render function ===
            function render_thread($nodes, $depth, $user_likes, $is_logged, $is_admin, $topik, $ci) {
                $is_child = ($depth > 0);
                echo '<div class="thr-wrap ' . ($is_child ? 'thr-children ml-7 sm:ml-9 mt-1.5' : '') . '">';
                
                foreach ($nodes as $idx => $kom):
                    $is_staff = ($kom['role'] == 'Petugas Disperakim');
                    $has_child = !empty($kom['children']);
                    $is_reply = ($depth > 0);
            ?>
            <div id="komentar-<?= $kom['id_komentar'] ?>" class="thr-node relative <?= $has_child ? 'has-child' : '' ?> <?= $idx > 0 ? 'mt-3' : '' ?> <?= $is_reply && $idx > 0 ? 'mt-1.5' : '' ?>">
                
                <!-- L-bend connector from parent line to this avatar -->
                <?php if ($is_reply): ?>
                <div class="thr-bend"></div>
                <?php endif; ?>

                <!-- Comment Row -->
                <div class="flex gap-3 relative">
                    <!-- Avatar -->
                    <div class="shrink-0">
                        <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-full <?= $is_staff ? 'bg-[#d6fb00]/10 text-[#d6fb00] ring-2 ring-[#d6fb00]/20' : 'bg-[#d6fb00]/5 text-zinc-400' ?> flex items-center justify-center relative z-[2]">
                            <i class="fa-solid <?= $is_staff ? 'fa-user-shield' : 'fa-user' ?> text-xs"></i>
                        </div>
                    </div>
                    <!-- Content Card -->
                    <div class="flex-1 min-w-0">
                        <div class="bg-[#0f2a30]/50 border <?= $is_staff ? 'border-[#d6fb00]/30 bg-[#d6fb00]/[0.02]' : 'border-[#d6fb00]/20' ?> p-2.5 sm:p-3 rounded-xl flex flex-col gap-1.5">
                            
                            <!-- Header: Name, Date, Actions -->
                            <div class="flex justify-between items-start gap-2">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="font-bold text-zinc-200 text-xs">
                                        <?= htmlspecialchars($kom['nama_komentator']) ?>
                                    </span>
                                    <?php if($is_staff): ?>
                                    <span class="bg-[#d6fb00] text-black font-black text-[7px] px-1 py-0.5 rounded tracking-wider uppercase">Staff Ahli</span>
                                    <?php endif; ?>
                                    <span class="text-[9px] text-zinc-500 hidden sm:inline">• <?= date('d M Y H:i', strtotime($kom['created_at'])) ?></span>
                                </div>

                                <!-- Actions -->
                                <?php $kom_liked = isset($user_likes['komentar_' . $kom['id_komentar']]); ?>
                                <div class="flex items-center gap-0.5 shrink-0 -mt-1 -mr-1">
                                    <!-- Like -->
                                    <button id="like-komentar-<?= $kom['id_komentar'] ?>" 
                                            onclick="toggleLike('komentar', <?= $kom['id_komentar'] ?>)" 
                                            class="flex items-center gap-1 text-[10px] px-1.5 py-1 rounded transition-colors <?= $kom_liked ? 'text-pink-400' : 'text-zinc-500 hover:text-pink-400' ?> <?= $is_logged ? 'cursor-pointer' : 'cursor-default opacity-60' ?>">
                                        <i class="<?= $kom_liked ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
                                        <span id="like-count-komentar-<?= $kom['id_komentar'] ?>"><?= $kom['like_count'] ?? 0 ?></span>
                                    </button>

                                    <!-- Reply -->
                                    <?php if ($is_logged && ($topik['status'] ?? 'open') !== 'closed'): ?>
                                    <button onclick="setReplyTo(<?= $kom['id_komentar'] ?>, '<?= htmlspecialchars(addslashes($kom['nama_komentator']), ENT_QUOTES) ?>')" 
                                            class="text-[10px] text-zinc-500 hover:text-[#d6fb00] transition-colors px-1.5 py-1 rounded" title="Balas">
                                        <i class="fa-solid fa-reply"></i>
                                    </button>
                                    <?php endif; ?>

                                    <!-- Delete/Report -->
                                    <?php if ($is_admin): ?>
                                    <form method="POST" action="<?= base_url('Umum/delete_komentar') ?>" class="inline" onsubmit="return confirm('Hapus komentar ini?')">
                                        <input type="hidden" name="<?= $ci->security->get_csrf_token_name(); ?>" value="<?= $ci->security->get_csrf_hash(); ?>">
                                        <input type="hidden" name="id_komentar" value="<?= $kom['id_komentar'] ?>">
                                        <input type="hidden" name="id_diskusi" value="<?= $topik['id_diskusi'] ?>">
                                        <button type="submit" class="text-[10px] text-zinc-500 hover:text-red-400 transition-colors px-1.5 py-1 rounded" title="Hapus">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                    <?php elseif ($is_logged): ?>
                                    <button onclick="reportKomentar(<?= $kom['id_komentar'] ?>)" class="text-[10px] text-zinc-500 hover:text-red-400 transition-colors px-1.5 py-1 rounded" title="Laporkan">
                                        <i class="fa-regular fa-flag"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Mobile Date & Quote reference -->
                            <div class="flex flex-wrap items-center gap-2 -mt-1">
                                <span class="text-[9px] text-zinc-500 sm:hidden"><?= date('d M Y H:i', strtotime($kom['created_at'])) ?></span>
                                <?php if ($is_reply && !empty($kom['reply_to_name'])): ?>
                                <a href="#komentar-<?= $kom['reply_to'] ?>" class="inline-flex items-center gap-1 bg-[#d6fb00]/[0.03] border border-[#d6fb00]/10 rounded px-1.5 py-0.5 text-[9px] text-zinc-500 hover:border-[#d6fb00]/25 transition-colors">
                                    <i class="fa-solid fa-reply fa-flip-horizontal text-[#d6fb00]/40 text-[8px]"></i>
                                    <span class="text-[#d6fb00]/70 font-bold">@<?= htmlspecialchars($kom['reply_to_name']) ?></span>
                                </a>
                                <?php endif; ?>
                            </div>

                            <!-- Body -->
                            <p class="text-zinc-400 text-xs leading-snug font-light whitespace-pre-line"><?= htmlspecialchars($kom['isi_komentar']) ?></p>
                        </div>
                    </div>
                </div>

                <!-- Render children recursively -->
                <?php if ($has_child): ?>
                    <?php render_thread($kom['children'], $depth + 1, $user_likes, $is_logged, $is_admin, $topik, $ci); ?>
                <?php endif; ?>

            </div>
            <?php
                endforeach;
                echo '</div>';
            }
            ?>

            <div>
                <?php if(!empty($tree)): ?>
                    <?php render_thread($tree, 0, $user_likes, $is_logged, $is_admin, $topik, $this); ?>
                <?php else: ?>
                <div class="bg-[#0f2a30]/30 border border-dashed border-[#d6fb00]/20 p-8 rounded-2xl text-center text-zinc-500 text-xs italic">
                    <i class="fa-regular fa-comments text-xl block mb-2 text-zinc-600"></i>
                    Belum ada tanggapan. Berikan masukan atau solusi pertama Anda di bawah ini.
                </div>
                <?php endif; ?>
            </div>

            <!-- Form Balas: LOGIN REQUIRED -->
            <?php if ($this->session->userdata('is_logged') === TRUE): ?>
                <?php if (($topik['status'] ?? 'open') !== 'closed'): ?>
                <form id="reply-form" action="<?= base_url('Umum/balas_aksi') ?>" method="POST" class="bg-[#0f2a30] border border-[#d6fb00]/20 rounded-2xl p-6 mt-8 space-y-4 text-xs shadow-xl relative z-10">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                    <input type="hidden" name="id_diskusi" value="<?= $topik['id_diskusi'] ?>">
                    <input type="hidden" name="reply_to" id="reply_to_input" value="">
                    
                    <h4 class="text-white font-bold text-sm tracking-tight flex items-center gap-2">
                        <i class="fa-solid fa-reply text-[#00a3b5] text-xs"></i> Tambahkan Informasi
                    </h4>

                    <!-- Reply-To Indicator (hidden by default) -->
                    <div id="reply-indicator" class="hidden bg-[#d6fb00]/[0.03] border border-[#d6fb00]/15 rounded-xl px-4 py-2.5 flex items-center justify-between">
                        <div class="flex items-center gap-2 text-[11px]">
                            <i class="fa-solid fa-reply fa-flip-horizontal text-[#d6fb00]/50"></i>
                            <span class="text-zinc-400">Membalas</span>
                            <span id="reply-to-label" class="text-[#d6fb00] font-bold"></span>
                        </div>
                        <button type="button" onclick="clearReplyTo()" class="text-zinc-500 hover:text-red-400 transition-colors text-xs p-1">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <!-- Info user -->
                    <div class="flex items-center gap-3 bg-[#d6fb00]/5 border border-[#d6fb00]/10 rounded-xl px-4 py-2.5">
                        <div class="w-7 h-7 rounded-lg bg-[#d6fb00]/20 flex items-center justify-center text-[#d6fb00]">
                            <i class="fa-solid fa-user text-[10px]"></i>
                        </div>
                        <span class="text-white text-xs font-bold"><?= htmlspecialchars($this->session->userdata('username') ?: $this->session->userdata('name') ?: 'Pengguna') ?></span>
                    </div>
                    
                    <div>
                        <label class="block text-zinc-400 mb-1.5 font-medium">Isi Tanggapan</label>
                        <textarea name="isi_komentar" rows="4" required minlength="5" class="w-full bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl px-3 py-2.5 text-white outline-none focus:border-[#d6fb00]/40 placeholder-[#5a7a80] transition-colors leading-relaxed" placeholder="Tulis tanggapan, solusi, atau informasi terkait masalah di atas..."></textarea>
                    </div>
                    
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="w-full sm:w-auto bg-[#00a3b5] hover:bg-[#18b8c9] text-white font-black px-6 py-3 rounded-xl transition-all uppercase tracking-wider text-[11px]">
                            Tambahkan Informasi
                        </button>
                    </div>
                </form>
                <?php else: ?>
                <div class="bg-red-500/5 border border-red-500/20 rounded-2xl p-6 mt-8 text-center">
                    <i class="fa-solid fa-lock text-red-400 text-lg mb-2 block"></i>
                    <p class="text-red-400 text-xs font-bold">Pengajuan ini telah ditutup dan tidak menerima informasi baru.</p>
                </div>
                <?php endif; ?>
            <?php else: ?>
            <!-- CTA Login untuk Non-Login -->
            <div class="bg-[#0f2a30] border border-[#d6fb00]/20 rounded-2xl p-6 mt-8 text-center space-y-3">
                <i class="fa-solid fa-right-to-bracket text-[#d6fb00] text-lg block"></i>
                <p class="text-zinc-400 text-xs">Ingin memberikan tanggapan? Silakan login terlebih dahulu.</p>
                <a href="<?= base_url('Auth/login') ?>" class="inline-flex items-center gap-2 bg-[#d6fb00] hover:bg-[#c5ea00] text-black font-bold text-xs px-5 py-2.5 rounded-xl transition-all">
                    <i class="fa-solid fa-right-to-bracket"></i> Login Sekarang
                </a>
            </div>
            <?php endif; ?>
        </div>

    </div>
</section>

<!-- Report Script -->
<script>
function reportKomentar(id) {
    if (!confirm('Laporkan komentar ini karena mengandung konten tidak pantas?')) return;
    fetch('<?= base_url("Umum/report_komentar") ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: '<?= $this->security->get_csrf_token_name() ?>=<?= $this->security->get_csrf_hash() ?>&id=' + id
    })
    .then(r => r.json())
    .then(d => {
        alert(d.status === 'ok' ? 'Laporan terkirim. Terima kasih atas partisipasi Anda.' : 'Gagal mengirim laporan.');
    })
    .catch(() => alert('Terjadi kesalahan jaringan.'));
}

function toggleLike(type, id) {
    <?php if (!$is_logged): ?>
    alert('Silakan login terlebih dahulu untuk memberikan like.');
    return;
    <?php endif; ?>
    
    const btn = document.getElementById('like-' + type + '-' + id);
    const countEl = document.getElementById('like-count-' + type + '-' + id);
    if (!btn || !countEl) return;
    
    // Disable sementara
    btn.disabled = true;
    
    fetch('<?= base_url("Umum/toggle_like") ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: '<?= $this->security->get_csrf_token_name() ?>=<?= $this->security->get_csrf_hash() ?>&type=' + type + '&id=' + id
    })
    .then(r => r.json())
    .then(d => {
        if (d.status === 'ok') {
            countEl.textContent = d.count;
            const icon = btn.querySelector('i');
            if (d.action === 'liked') {
                icon.className = 'fa-solid fa-heart';
                btn.classList.remove('bg-[#d6fb00]/5', 'text-zinc-500', 'text-zinc-600');
                btn.classList.add('bg-pink-500/10', 'text-pink-400');
            } else {
                icon.className = 'fa-regular fa-heart';
                btn.classList.remove('bg-pink-500/10', 'text-pink-400');
                btn.classList.add('bg-[#d6fb00]/5', 'text-zinc-500');
            }
        }
        btn.disabled = false;
    })
    .catch(() => { btn.disabled = false; });
}

// ================================
// Reply-To System
// ================================
function setReplyTo(komentarId, namaUser) {
    const input = document.getElementById('reply_to_input');
    const indicator = document.getElementById('reply-indicator');
    const label = document.getElementById('reply-to-label');
    const form = document.getElementById('reply-form');
    const textarea = form ? form.querySelector('textarea[name="isi_komentar"]') : null;
    
    if (input) input.value = komentarId;
    if (label) label.textContent = '@' + namaUser;
    if (indicator) {
        indicator.classList.remove('hidden');
        indicator.classList.add('flex');
    }
    
    // Scroll ke form dan fokus textarea
    if (form) {
        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
        setTimeout(() => { if (textarea) textarea.focus(); }, 500);
    }
    
    // Highlight komentar sumber sebentar
    const source = document.getElementById('komentar-' + komentarId);
    if (source) {
        source.classList.add('ring-2', 'ring-[#d6fb00]/30', 'ring-offset-2', 'ring-offset-[#0a1a1f]');
        setTimeout(() => {
            source.classList.remove('ring-2', 'ring-[#d6fb00]/30', 'ring-offset-2', 'ring-offset-[#0a1a1f]');
        }, 2000);
    }
}

function clearReplyTo() {
    const input = document.getElementById('reply_to_input');
    const indicator = document.getElementById('reply-indicator');
    
    if (input) input.value = '';
    if (indicator) {
        indicator.classList.add('hidden');
        indicator.classList.remove('flex');
    }
}
</script>
