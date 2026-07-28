<div x-data="{ activeTab: 'hero' }">
    <div class="flex flex-col md:flex-row justify-between md:items-end gap-4 mb-6 relative z-10">
        <div>
            <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2">Manajemen Konten Website</h2>
            <p class="text-sm text-gray-500 dark:text-brand-muted">Kelola teks, gambar hero, dan konten statis landing page.</p>
        </div>
    </div>

    <!-- Tabs Navigation -->
    <div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200 dark:border-white/10 relative z-10">
        <button type="button" @click="activeTab = 'hero'" :class="activeTab === 'hero' ? 'border-blue-600 text-blue-600 dark:border-brand-primary dark:text-brand-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'" class="px-4 py-3 font-bold border-b-2 transition-colors flex items-center gap-2">
            <i class="ph ph-image"></i> Hero Section
        </button>
        <button type="button" @click="activeTab = 'about'" :class="activeTab === 'about' ? 'border-blue-600 text-blue-600 dark:border-brand-primary dark:text-brand-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'" class="px-4 py-3 font-bold border-b-2 transition-colors flex items-center gap-2">
            <i class="ph ph-info"></i> Tentang Kami
        </button>
        <button type="button" @click="activeTab = 'footer'" :class="activeTab === 'footer' ? 'border-blue-600 text-blue-600 dark:border-brand-primary dark:text-brand-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'" class="px-4 py-3 font-bold border-b-2 transition-colors flex items-center gap-2">
            <i class="ph ph-envelope-simple"></i> Footer & Kontak
        </button>
    </div>

    <div class="bg-white dark:bg-brand-card rounded-3xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden relative z-10 p-6">
        <form action="<?= base_url('Admin_Content/update') ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
            
            <!-- SECTION HERO -->
            <div x-show="activeTab === 'hero'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-1 translate-y-0" style="display: none;">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-white/10 pb-2 mb-4">Bagian Utama (Hero Section)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Judul Hero (Gunakan HTML jika perlu)</label>
                        <textarea name="hero_title" rows="3" class="w-full px-4 py-3 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl focus:ring-2 focus:ring-brand-primary/50 text-gray-800 dark:text-gray-200"><?= htmlspecialchars($settings['hero_title'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Subjudul Hero</label>
                        <textarea name="hero_subtitle" rows="3" class="w-full px-4 py-3 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl focus:ring-2 focus:ring-brand-primary/50 text-gray-800 dark:text-gray-200"><?= htmlspecialchars($settings['hero_subtitle'] ?? '') ?></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gambar Background Hero (Kosongkan jika tidak ingin mengubah)</label>
                        <?php if(!empty($settings['hero_background'])): ?>
                            <div class="mb-3">
                                <img src="<?= base_url($settings['hero_background']) ?>" class="h-32 object-cover rounded-lg border border-gray-200 dark:border-white/10">
                            </div>
                        <?php endif; ?>
                        <input type="file" name="hero_background" accept="image/*" class="w-full px-4 py-2 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl text-gray-800 dark:text-gray-200">
                    </div>
                </div>
            </div>

            <!-- SECTION ABOUT -->
            <div x-show="activeTab === 'about'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-1 translate-y-0" style="display: none;">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-white/10 pb-2 mb-4">Tentang Kami (About Section)</h3>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Judul Tentang Kami</label>
                        <textarea name="about_title" rows="2" class="w-full px-4 py-3 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl focus:ring-2 focus:ring-brand-primary/50 text-gray-800 dark:text-gray-200"><?= htmlspecialchars($settings['about_title'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Deskripsi Paragraf 1</label>
                        <textarea name="about_desc_1" rows="4" class="w-full px-4 py-3 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl focus:ring-2 focus:ring-brand-primary/50 text-gray-800 dark:text-gray-200"><?= htmlspecialchars($settings['about_desc_1'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Deskripsi Paragraf 2</label>
                        <textarea name="about_desc_2" rows="4" class="w-full px-4 py-3 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl focus:ring-2 focus:ring-brand-primary/50 text-gray-800 dark:text-gray-200"><?= htmlspecialchars($settings['about_desc_2'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- SECTION FOOTER -->
            <div x-show="activeTab === 'footer'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-1 translate-y-0" style="display: none;">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-white/10 pb-2 mb-4">Footer (Informasi Kontak)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Alamat Lengkap</label>
                        <textarea name="footer_address" rows="2" class="w-full px-4 py-3 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl focus:ring-2 focus:ring-brand-primary/50 text-gray-800 dark:text-gray-200"><?= htmlspecialchars($settings['footer_address'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">No. Telepon</label>
                        <input type="text" name="footer_phone" value="<?= htmlspecialchars($settings['footer_phone'] ?? '') ?>" class="w-full px-4 py-3 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl focus:ring-2 focus:ring-brand-primary/50 text-gray-800 dark:text-gray-200">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email</label>
                        <input type="text" name="footer_email" value="<?= htmlspecialchars($settings['footer_email'] ?? '') ?>" class="w-full px-4 py-3 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl focus:ring-2 focus:ring-brand-primary/50 text-gray-800 dark:text-gray-200">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Teks Hak Cipta (Copyright)</label>
                        <input type="text" name="footer_copyright" value="<?= htmlspecialchars($settings['footer_copyright'] ?? '') ?>" class="w-full px-4 py-3 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl focus:ring-2 focus:ring-brand-primary/50 text-gray-800 dark:text-gray-200">
                    </div>
                </div>
            </div>

            <div class="pt-6 border-t border-gray-200 dark:border-white/10 flex justify-end">
                <button type="submit" class="bg-blue-600 dark:bg-brand-primary text-white dark:text-brand-dark px-8 py-3 rounded-xl font-bold flex items-center hover:bg-blue-700 dark:hover:bg-brand-hover transition-colors shadow-sm shadow-blue-500/30 dark:shadow-brand-primary/20">
                    <i class="ph ph-floppy-disk text-lg mr-2"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
