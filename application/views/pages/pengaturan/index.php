<div class="w-full py-12 px-4 sm:px-6 lg:px-8">
    <?php $current_username = htmlspecialchars($user->username ?? $user->name); ?>
    <div class="max-w-2xl mx-auto space-y-8">
        
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-[#d6fb00]/20 pb-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-[#d6fb00]/10 rounded-xl flex items-center justify-center text-[#d6fb00] text-xl">
                    <i class="fa-solid fa-user-gear"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-[#ecffb6]">Pengaturan Pengguna</h1>
                    <p class="text-zinc-400 text-sm">Kelola profil, username, dan preferensi akun Anda.</p>
                </div>
            </div>
            
            <a href="<?= base_url('Auth/logout') ?>" class="flex items-center gap-2 px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/30 rounded-xl transition-all font-semibold text-sm">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> <span class="hidden sm:inline">Keluar / Logout</span>
            </a>
        </div>

        <!-- Flash Messages -->
        <?php if ($this->session->flashdata('success')): ?>
            <div class="bg-[#d6fb00]/10 border border-[#d6fb00]/30 text-[#ecffb6] px-4 py-3 rounded-xl flex items-start gap-3">
                <i class="fa-solid fa-circle-check mt-1"></i>
                <p class="text-sm font-medium"><?= $this->session->flashdata('success') ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($this->session->flashdata('error')): ?>
            <div class="bg-red-500/10 border border-red-500/30 text-red-400 px-4 py-3 rounded-xl flex items-start gap-3">
                <i class="fa-solid fa-circle-exclamation mt-1"></i>
                <p class="text-sm font-medium"><?= $this->session->flashdata('error') ?></p>
            </div>
        <?php endif; ?>

        <!-- Edit Profile Form -->
        <div class="bg-[#0f2a30] rounded-2xl border border-[#d6fb00]/20 p-6 md:p-8 shadow-xl">
            <h2 class="text-lg font-semibold text-[#ecffb6] mb-6 flex items-center gap-2">
                <i class="fa-solid fa-address-card"></i> Informasi Profil
            </h2>
            
            <form action="<?= base_url('akun/update') ?>" method="POST" class="space-y-5">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                
                <div>
                    <label class="block text-sm font-medium text-zinc-300 mb-1">Email <span class="text-zinc-500 text-xs font-normal">(Tidak dapat diubah)</span></label>
                    <input type="email" value="<?= htmlspecialchars($user->email) ?>" disabled
                           class="w-full bg-[#0a191c] border border-zinc-700/50 rounded-xl px-4 py-2.5 text-zinc-500 cursor-not-allowed">
                </div>

                <div>
                    <label class="block text-sm font-medium text-[#ecffb6] mb-1">Username <span class="text-red-400">*</span></label>
                    <p class="text-xs text-zinc-400 mb-2">Ditampilkan di forum diskusi (tanpa spasi, max 30 karakter).</p>
                    <input type="text" name="username" value="<?= htmlspecialchars($user->username ?? '') ?>" required maxlength="30" pattern="^\S+$"
                           oninput="this.value = this.value.replace(/\s/g, '').toLowerCase()"
                           class="w-full bg-[#0a191c] border border-[#d6fb00]/30 rounded-xl px-4 py-2.5 text-white placeholder-zinc-600 focus:outline-none focus:border-[#d6fb00] focus:ring-1 focus:ring-[#d6fb00] transition-all">
                </div>

                <div>
                    <label class="block text-sm font-medium text-[#ecffb6] mb-1">Nama Lengkap <span class="text-red-400">*</span></label>
                    <input type="text" name="name" value="<?= htmlspecialchars($user->name) ?>" required
                           class="w-full bg-[#0a191c] border border-[#d6fb00]/30 rounded-xl px-4 py-2.5 text-white placeholder-zinc-600 focus:outline-none focus:border-[#d6fb00] focus:ring-1 focus:ring-[#d6fb00] transition-all">
                </div>

                <div>
                    <label class="block text-sm font-medium text-[#ecffb6] mb-1">No. WhatsApp</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($user->phone ?? '') ?>"
                           class="w-full bg-[#0a191c] border border-[#d6fb00]/30 rounded-xl px-4 py-2.5 text-white placeholder-zinc-600 focus:outline-none focus:border-[#d6fb00] focus:ring-1 focus:ring-[#d6fb00] transition-all">
                </div>

                <div class="pt-4">
                    <button type="submit" class="bg-[#d6fb00] hover:bg-[#b5d500] text-[#0f2a30] font-bold py-2.5 px-6 rounded-xl transition-colors duration-200 flex items-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

        <!-- Danger Zone -->
        <div class="mt-12 bg-red-500/5 rounded-2xl border border-red-500/20 p-6 shadow-xl">
            <h2 class="text-lg font-semibold text-red-400 mb-2 flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation"></i> Zona Berbahaya
            </h2>
            <p class="text-sm text-zinc-400 mb-6">Sekali Anda menghapus akun, data profil Anda tidak bisa dikembalikan. Komentar dan forum yang pernah Anda kirimkan akan dianonimkan menjadi "Akun Dihapus" agar tidak merusak alur diskusi.</p>
            
            <button type="button" onclick="openDeleteModal()" class="bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/30 font-semibold py-2.5 px-6 rounded-xl transition-colors duration-200 flex items-center gap-2">
                <i class="fa-solid fa-trash-can"></i> Hapus Akun Secara Permanen
            </button>
        </div>

    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden bg-[#0a191c]/80 backdrop-blur-sm flex items-center justify-center px-4">
    <div class="bg-[#0f2a30] rounded-2xl border border-red-500/30 p-6 sm:p-8 max-w-md w-full shadow-2xl relative">
        <button type="button" onclick="closeDeleteModal()" class="absolute top-4 right-4 text-zinc-400 hover:text-white transition-colors">
            <i class="fa-solid fa-xmark text-xl"></i>
        </button>
        
        <div class="w-12 h-12 bg-red-500/10 rounded-full flex items-center justify-center text-red-400 mb-4 mx-auto">
            <i class="fa-solid fa-triangle-exclamation text-2xl"></i>
        </div>
        
        <h3 class="text-xl font-bold text-center text-white mb-2">Konfirmasi Hapus Akun</h3>
        <p class="text-sm text-zinc-400 text-center mb-6">
            Tindakan ini sangat berbahaya dan tidak dapat dibatalkan. Ketik <strong class="text-red-400 select-all"><?= $current_username ?></strong> di bawah ini untuk mengonfirmasi.
        </p>
        
        <form action="<?= base_url('akun/delete') ?>" method="POST" id="deleteForm">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
            
            <div class="mb-6">
                <input type="text" id="confirmDeleteInput" autocomplete="off" onkeyup="checkDeleteConfirm()" placeholder="Ketik nama akun Anda"
                       class="w-full bg-[#0a191c] border border-zinc-700/50 rounded-xl px-4 py-2.5 text-white placeholder-zinc-600 focus:outline-none focus:border-red-500 focus:ring-1 focus:ring-red-500 transition-all text-center">
            </div>
            
            <button type="submit" id="btnConfirmDelete" disabled
                    class="w-full bg-zinc-800 text-zinc-500 font-bold py-3 rounded-xl cursor-not-allowed transition-colors duration-200">
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
        btn.classList.remove('bg-zinc-800', 'text-zinc-500', 'cursor-not-allowed');
        btn.classList.add('bg-red-500', 'hover:bg-red-600', 'text-white', 'cursor-pointer');
    } else {
        btn.disabled = true;
        btn.classList.add('bg-zinc-800', 'text-zinc-500', 'cursor-not-allowed');
        btn.classList.remove('bg-red-500', 'hover:bg-red-600', 'text-white', 'cursor-pointer');
    }
}
</script>
