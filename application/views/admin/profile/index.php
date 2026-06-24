<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2">Profil Saya</h2>
            <p class="text-sm text-gray-500 dark:text-brand-muted">Kelola informasi pribadi dan keamanan akun Anda.</p>
        </div>
    </div>

    <!-- Edit Profile Card -->
    <div class="bg-white dark:bg-brand-card rounded-2xl border border-gray-200 dark:border-white/5 shadow-xl shadow-gray-200/50 dark:shadow-none overflow-hidden">
        <div class="p-6 md:p-8">
            <div class="flex items-center gap-4 mb-8">
                <div class="relative group">
                    <img src="<?= $this->session->userdata('avatar') ?: 'https://ui-avatars.com/api/?name='.urlencode($user->name ?: 'Admin').'&background=d6fb00&color=0a1a1f&bold=true' ?>" alt="Avatar" class="h-20 w-20 rounded-2xl object-cover shadow-sm">
                    <div class="absolute inset-0 bg-black/50 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center cursor-not-allowed" title="Avatar didapatkan secara otomatis">
                        <i class="ph ph-lock-key text-white text-xl"></i>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white"><?= html_escape($user->name) ?></h3>
                    <p class="text-sm text-gray-500 dark:text-brand-muted">Role: <span class="font-semibold text-brand-primary uppercase"><?= html_escape($user->role) ?></span></p>
                </div>
            </div>

            <form action="<?= base_url('User_Profile/update') ?>" method="POST" class="space-y-6">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Username</label>
                        <input type="text" value="<?= htmlspecialchars($user->username ?? '') ?>" disabled
                               class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-gray-500 dark:text-gray-400 cursor-not-allowed">
                        <p class="text-xs text-gray-500 dark:text-brand-muted/70 mt-2">Username tidak dapat diubah oleh pengguna.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Email</label>
                        <input type="email" value="<?= htmlspecialchars($user->email ?? '') ?>" disabled
                               class="w-full bg-gray-50 dark:bg-black/20 border border-gray-200 dark:border-white/10 rounded-xl px-4 py-3 text-gray-500 dark:text-gray-400 cursor-not-allowed">
                    </div>

                    <div class="md:col-span-2 border-t border-gray-200 dark:border-white/5 my-2"></div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" name="name" value="<?= htmlspecialchars($user->name) ?>" required
                               class="w-full bg-white dark:bg-[#0a1a1f] border border-gray-300 dark:border-white/10 rounded-xl px-4 py-3 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-primary/50 focus:border-brand-primary transition-all">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">No. WhatsApp</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($user->phone ?? '') ?>"
                               class="w-full bg-white dark:bg-[#0a1a1f] border border-gray-300 dark:border-white/10 rounded-xl px-4 py-3 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-primary/50 focus:border-brand-primary transition-all">
                    </div>
                    
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Ubah Password</label>
                        <div class="relative">
                            <input type="password" name="password" placeholder="Kosongkan jika tidak ingin mengubah password"
                                   class="w-full bg-white dark:bg-[#0a1a1f] border border-gray-300 dark:border-white/10 rounded-xl px-4 py-3 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-primary/50 focus:border-brand-primary transition-all">
                            <i class="ph ph-lock-key absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>
                </div>

                <div class="pt-6 border-t border-gray-200 dark:border-white/5 flex justify-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 dark:bg-brand-primary dark:hover:bg-brand-hover text-white dark:text-brand-dark font-bold py-3 px-8 rounded-xl transition-colors duration-200 flex items-center gap-2 shadow-lg shadow-blue-500/20 dark:shadow-brand-primary/20">
                        <i class="ph ph-floppy-disk text-lg"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
