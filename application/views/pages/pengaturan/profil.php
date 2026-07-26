<div class="relative z-10">
    <?php $current_username = htmlspecialchars($user->username ?? $user->name); ?>

    <!-- Header -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-brand-primary/10 flex items-center justify-center text-brand-primary text-xl">
                <i class="ph ph-user-circle"></i>
            </div>
            <div>
                <h1 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white tracking-tight">Profil Saya</h1>
                <p class="text-gray-500 dark:text-brand-muted text-sm">Kelola data pribadi dan preferensi akun Anda.</p>
            </div>
        </div>
        <a href="<?= base_url('Auth/logout') ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-red-50 hover:bg-red-100 dark:bg-red-500/10 dark:hover:bg-red-500/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-500/30 rounded-xl transition-all font-semibold text-sm">
            <i class="ph ph-sign-out"></i> <span>Keluar</span>
        </a>
    </div>

    <?php
        // Flash success/error sudah dirender shell admin/index.php sebelum $content
        // disuntikkan — blok di sini dulu merender ulang pesan yang sama (bug B5,
        // sama seperti pages/pengaturan/index.php).
    ?>

    <div class="max-w-2xl space-y-6">

        <!-- Edit Profile Form -->
        <div class="bg-white dark:bg-brand-card rounded-3xl border border-gray-200 dark:border-white/10 p-6 md:p-8 shadow-sm">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                <i class="ph ph-identification-card text-brand-primary"></i> Informasi Profil
            </h2>

            <form action="<?= base_url('akun/update') ?>" method="POST" class="space-y-5">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

                <div>
                    <label class="block text-sm font-medium text-gray-500 dark:text-brand-muted mb-1">Email <span class="text-xs font-normal">(Tidak dapat diubah)</span></label>
                    <input type="email" value="<?= htmlspecialchars($user->email) ?>" disabled
                           class="w-full bg-gray-100 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-gray-500 dark:text-brand-muted cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-800 dark:text-white mb-1">Username <span class="text-red-500">*</span></label>
                    <p class="text-xs text-gray-500 dark:text-brand-muted mb-2">Ditampilkan di forum diskusi (tanpa spasi, max 30 karakter).</p>
                    <input type="text" name="username" value="<?= htmlspecialchars($user->username ?? '') ?>" required maxlength="30" pattern="^\S+$"
                           oninput="this.value = this.value.replace(/\s/g, '').toLowerCase()"
                           class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-gray-800 dark:text-white placeholder-gray-400 dark:placeholder-brand-muted/60 focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary transition-all">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-800 dark:text-white mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user->name) ?>" required
                           class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-gray-800 dark:text-white placeholder-gray-400 dark:placeholder-brand-muted/60 focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary transition-all">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-800 dark:text-white mb-1">No. WhatsApp</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($user->phone ?? '') ?>"
                           class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-gray-800 dark:text-white placeholder-gray-400 dark:placeholder-brand-muted/60 focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary transition-all">
                </div>

                <div class="pt-4 border-t border-gray-100 dark:border-white/5">
                    <p class="text-sm font-medium text-gray-800 dark:text-white mb-1">Ganti Password</p>
                    <p class="text-xs text-gray-500 dark:text-brand-muted mb-3">Kosongkan kalau tidak ingin mengubah. Minimal 8 karakter, ada huruf besar, angka, dan simbol.</p>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <input type="password" name="password" autocomplete="new-password" placeholder="Password baru"
                               class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-gray-800 dark:text-white placeholder-gray-400 dark:placeholder-brand-muted/60 focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary transition-all">
                        <input type="password" name="password_confirm" autocomplete="new-password" placeholder="Ulangi password baru"
                               class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-gray-800 dark:text-white placeholder-gray-400 dark:placeholder-brand-muted/60 focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary transition-all">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="bg-blue-600 dark:bg-brand-primary hover:bg-blue-700 dark:hover:bg-brand-hover text-white dark:text-brand-dark font-bold py-2.5 px-6 rounded-xl transition-colors flex items-center gap-2">
                        <i class="ph ph-floppy-disk"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

        <?php if (isset($pengajuan_sp2)): ?>
        <!-- SP2 Status & Data Pengembang -->
        <div class="bg-white dark:bg-brand-card rounded-3xl border border-gray-200 dark:border-white/10 p-6 md:p-8 shadow-sm">
            <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="ph ph-certificate text-brand-primary"></i> Sertifikasi Pengembang (SRP2)
                </h2>
                <?php if($pengajuan_sp2->status_verifikasi == 'Pending'): ?>
                    <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-amber-50 dark:bg-brand-primary/10 border border-amber-200 dark:border-brand-primary/20 text-amber-700 dark:text-brand-primary font-bold text-xs uppercase tracking-wider">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 dark:bg-brand-primary animate-pulse"></span> Dalam Peninjauan
                    </span>
                <?php elseif($pengajuan_sp2->status_verifikasi == 'Draft'): ?>
                    <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-cyan-50 dark:bg-cyan-500/10 border border-cyan-200 dark:border-cyan-500/20 text-cyan-700 dark:text-cyan-400 font-bold text-xs uppercase tracking-wider">
                        <i class="ph ph-pencil-simple text-xs"></i> Lengkapi Dokumen
                    </span>
                <?php elseif($pengajuan_sp2->status_verifikasi == 'Diterima'): ?>
                    <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-green-50 dark:bg-green-500/10 border border-green-200 dark:border-green-500/30 text-green-700 dark:text-green-400 font-bold text-xs uppercase tracking-wider">
                        <i class="ph ph-check-circle text-xs"></i> Diterima
                    </span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-500/30 text-red-600 dark:text-red-400 font-bold text-xs uppercase tracking-wider">
                        <i class="ph ph-x-circle text-xs"></i> Ditolak
                    </span>
                <?php endif; ?>
            </div>

            <?php if($pengajuan_sp2->status_verifikasi == 'Ditolak' && !empty($pengajuan_sp2->catatan_admin)): ?>
            <div class="bg-red-50 dark:bg-red-500/5 border border-red-200 dark:border-red-500/20 rounded-2xl p-4 mb-6 text-xs sm:text-sm text-gray-600 dark:text-brand-muted">
                <strong class="text-red-600 dark:text-red-400 block mb-1"><i class="ph ph-warning mr-1"></i> Catatan Koreksi Admin:</strong>
                <?= htmlspecialchars($pengajuan_sp2->catatan_admin) ?>
            </div>
            <?php endif; ?>

            <div class="mb-6 pb-6 border-b border-gray-100 dark:border-white/10">
                <?php if($pengajuan_sp2->status_verifikasi == 'Diterima'): ?>
                    <a href="<?= base_url('Pengembang/profil/' . $pengajuan_sp2->id) ?>" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 bg-brand-primary/10 hover:bg-brand-primary/20 text-brand-hover dark:text-brand-primary border border-brand-primary/30 rounded-xl transition-all font-semibold text-sm mb-3">
                        <i class="ph ph-eye"></i> Lihat Profil Publik
                    </a>
                    <div>
                        <button type="button" disabled title="Sertifikat digital belum tersedia untuk diunduh" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 dark:bg-white/5 text-gray-400 dark:text-brand-muted/60 border border-gray-200 dark:border-white/10 rounded-xl font-semibold text-sm cursor-not-allowed">
                            <i class="ph ph-download"></i> Download Sertifikat
                        </button>
                        <p class="text-xs text-gray-500 dark:text-brand-muted mt-2">Sertifikat digital belum tersedia untuk diunduh — proses penerbitan sedang disiapkan Admin Disperakim Jateng.</p>
                    </div>
                <?php else: ?>
                    <p class="text-xs text-gray-500 dark:text-brand-muted">Download sertifikat akan tersedia setelah pengajuan Anda dinyatakan <strong class="text-green-600 dark:text-green-400">Diterima</strong>.</p>
                <?php endif; ?>
            </div>

            <h3 class="text-sm font-bold text-gray-700 dark:text-gray-300 mb-4">Edit Data Perusahaan</h3>
            <form action="<?= base_url('akun/update_pengembang') ?>" method="POST" class="space-y-5">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

                <div>
                    <label class="block text-sm font-medium text-gray-800 dark:text-white mb-1">Nama Perusahaan</label>
                    <input type="text" name="nama_perusahaan" value="<?= htmlspecialchars($pengajuan_sp2->nama_perusahaan) ?>" required
                           class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-gray-800 dark:text-white placeholder-gray-400 dark:placeholder-brand-muted/60 focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary transition-all uppercase">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-800 dark:text-white mb-1">Alamat Kantor</label>
                    <textarea name="alamat_kantor" rows="2" required
                              class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-gray-800 dark:text-white placeholder-gray-400 dark:placeholder-brand-muted/60 focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary transition-all resize-none"><?= htmlspecialchars($pengajuan_sp2->alamat_kantor) ?></textarea>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-800 dark:text-white mb-1">Asosiasi</label>
                        <select name="asosiasi" required class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-gray-800 dark:text-white focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary transition-all">
                            <?php $asosiasi_labels = ['rei' => 'REI', 'himperra' => 'HIMPERRA', 'apersi' => 'APERSI', 'pi' => 'PI', 'lainnya' => 'Lainnya']; ?>
                            <?php foreach ($asosiasi_labels as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $pengajuan_sp2->asosiasi === $val ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-800 dark:text-white mb-1">No. Keanggotaan</label>
                        <input type="text" name="no_keanggotaan" value="<?= htmlspecialchars($pengajuan_sp2->no_keanggotaan) ?>" required
                               class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-gray-800 dark:text-white placeholder-gray-400 dark:placeholder-brand-muted/60 focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary transition-all">
                    </div>
                </div>

                <p class="text-xs text-gray-500 dark:text-brand-muted pt-1">Kontak publik — ditampilkan di halaman profil pengembang.</p>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Instagram</label>
                        <input type="text" name="instagram" value="<?= htmlspecialchars($pengajuan_sp2->instagram ?? '') ?>" placeholder="https://instagram.com/..."
                               class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-gray-800 dark:text-white placeholder-gray-400 dark:placeholder-brand-muted/60 focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Website</label>
                        <input type="text" name="website" value="<?= htmlspecialchars($pengajuan_sp2->website ?? '') ?>" placeholder="https://..."
                               class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-gray-800 dark:text-white placeholder-gray-400 dark:placeholder-brand-muted/60 focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sosmed Lainnya</label>
                        <input type="text" name="sosmed_lainnya" value="<?= htmlspecialchars($pengajuan_sp2->sosmed_lainnya ?? '') ?>" placeholder="Facebook, WhatsApp Business, dst."
                               class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-gray-800 dark:text-white placeholder-gray-400 dark:placeholder-brand-muted/60 focus:outline-none focus:border-brand-primary focus:ring-1 focus:ring-brand-primary transition-all">
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" class="bg-blue-600 dark:bg-brand-primary hover:bg-blue-700 dark:hover:bg-brand-hover text-white dark:text-brand-dark font-bold py-2.5 px-6 rounded-xl transition-colors flex items-center gap-2">
                        <i class="ph ph-floppy-disk"></i> Simpan Data Pengembang
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <?php if (($user->role ?? '') === 'pengembang' && !isset($pengajuan_sp2)): ?>
        <div class="bg-white dark:bg-brand-card rounded-3xl border border-gray-200 dark:border-white/10 p-6 md:p-8 shadow-sm">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Lengkapi Pengajuan SRP2</h2>
            <p class="mt-2 text-sm text-gray-500 dark:text-brand-muted">Akun pengembang sudah aktif. Lengkapi profil pengajuan sebelum mengunggah dokumen persyaratan.</p>
            <a href="<?= base_url('Pengembang/formulir') ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-blue-600 dark:bg-brand-primary px-5 py-2.5 text-sm font-bold text-white dark:text-brand-dark"><i class="ph ph-arrow-right"></i> Lengkapi Profil SRP2</a>
        </div>
        <?php endif; ?>

        <!-- Danger Zone -->
        <div class="mt-4 bg-red-50 dark:bg-red-500/5 rounded-3xl border border-red-200 dark:border-red-500/20 p-6 shadow-sm">
            <h2 class="text-lg font-bold text-red-600 dark:text-red-400 mb-2 flex items-center gap-2">
                <i class="ph ph-warning"></i> Zona Berbahaya
            </h2>
            <p class="text-sm text-gray-500 dark:text-brand-muted mb-6">Sekali Anda menghapus akun, data profil Anda tidak bisa dikembalikan. Komentar dan forum yang pernah Anda kirimkan akan dianonimkan menjadi "Akun Dihapus" agar tidak merusak alur diskusi.</p>

            <button type="button" onclick="openDeleteModal()" class="bg-red-100 hover:bg-red-200 dark:bg-red-500/10 dark:hover:bg-red-500/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-500/30 font-semibold py-2.5 px-6 rounded-xl transition-colors flex items-center gap-2">
                <i class="ph ph-trash"></i> Hapus Akun Secara Permanen
            </button>
        </div>

    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center px-4">
    <div class="bg-white dark:bg-brand-card rounded-3xl border border-red-200 dark:border-red-500/30 p-6 sm:p-8 max-w-md w-full shadow-2xl relative">
        <button type="button" onclick="closeDeleteModal()" class="absolute top-4 right-4 text-gray-400 dark:text-brand-muted hover:text-gray-700 dark:hover:text-white transition-colors">
            <i class="ph ph-x text-xl"></i>
        </button>

        <div class="w-12 h-12 bg-red-100 dark:bg-red-500/10 rounded-full flex items-center justify-center text-red-500 dark:text-red-400 mb-4 mx-auto">
            <i class="ph ph-warning text-2xl"></i>
        </div>

        <h3 class="text-xl font-bold text-center text-gray-900 dark:text-white mb-2">Konfirmasi Hapus Akun</h3>
        <p class="text-sm text-gray-500 dark:text-brand-muted text-center mb-6">
            Tindakan ini sangat berbahaya dan tidak dapat dibatalkan. Ketik <strong class="text-red-600 dark:text-red-400 select-all"><?= $current_username ?></strong> di bawah ini untuk mengonfirmasi.
        </p>

        <form action="<?= base_url('akun/delete') ?>" method="POST" id="deleteForm">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

            <div class="mb-6">
                <input type="text" id="confirmDeleteInput" autocomplete="off" onkeyup="checkDeleteConfirm()" placeholder="Ketik nama akun Anda"
                       class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-2.5 text-gray-800 dark:text-white placeholder-gray-400 dark:placeholder-brand-muted/60 focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 transition-all text-center">
            </div>

            <button type="submit" id="btnConfirmDelete" disabled
                    class="w-full bg-gray-100 dark:bg-white/5 text-gray-400 dark:text-brand-muted/60 font-bold py-3 rounded-xl cursor-not-allowed transition-colors">
                Hapus Akun Saya
            </button>
        </form>
    </div>
</div>

<script>
const targetUsername = <?= json_encode($user->username ?? $user->name) ?>;

function openDeleteModal() {
    document.getElementById('deleteModal').classList.remove('hidden');
    document.getElementById('confirmDeleteInput').value = '';
    document.getElementById('confirmDeleteInput').focus();
    checkDeleteConfirm();
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

function checkDeleteConfirm() {
    const input = document.getElementById('confirmDeleteInput').value;
    const btn = document.getElementById('btnConfirmDelete');

    if (input === targetUsername) {
        btn.disabled = false;
        btn.classList.remove('bg-gray-100', 'dark:bg-white/5', 'text-gray-400', 'dark:text-brand-muted/60', 'cursor-not-allowed');
        btn.classList.add('bg-red-600', 'hover:bg-red-700', 'text-white', 'cursor-pointer');
    } else {
        btn.disabled = true;
        btn.classList.add('bg-gray-100', 'dark:bg-white/5', 'text-gray-400', 'dark:text-brand-muted/60', 'cursor-not-allowed');
        btn.classList.remove('bg-red-600', 'hover:bg-red-700', 'text-white', 'cursor-pointer');
    }
}
</script>
