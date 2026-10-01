<div x-data="{ activeTab: 'hero' }">
    <?php $this->load->view('admin/components/judul_halaman', ['jh_deskripsi' => 'Kelola teks, gambar hero, dan konten statis landing page.']); ?>

    <!-- Tabs Navigation -->
    <div class="flex flex-wrap gap-1 mb-5 border-b border-gray-200 dark:border-white/10 relative z-10" role="tablist">
        <button type="button" role="tab" @click="activeTab = 'hero'" :aria-selected="activeTab === 'hero' ? 'true' : 'false'" class="tombol-tab">
            <i class="ph ph-image"></i><span>Bagian utama</span>
        </button>
        <button type="button" role="tab" @click="activeTab = 'about'" :aria-selected="activeTab === 'about' ? 'true' : 'false'" class="tombol-tab">
            <i class="ph ph-info"></i><span>Tentang kami</span>
        </button>
        <button type="button" role="tab" @click="activeTab = 'footer'" :aria-selected="activeTab === 'footer' ? 'true' : 'false'" class="tombol-tab">
            <i class="ph ph-envelope-simple"></i><span>Kaki halaman &amp; kontak</span>
        </button>
    </div>

    <div class="kartu-admin isi-kartu overflow-hidden relative z-10">
        <form action="<?= base_url('Admin_Content/update') ?>" method="POST" enctype="multipart/form-data" class="space-y-5">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
            
            <!-- SECTION HERO -->
            <div x-show="activeTab === 'hero'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-1 translate-y-0" style="display: none;">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-white/10 pb-2 mb-4">Bagian utama</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Judul bagian utama (boleh memakai tag HTML)</label>
                        <textarea name="hero_title" rows="3" class="w-full px-4 py-3 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl focus:ring-2 focus:ring-brand-primary/50 text-gray-800 dark:text-gray-200"><?= htmlspecialchars($settings['hero_title'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Subjudul bagian utama</label>
                        <textarea name="hero_subtitle" rows="3" class="w-full px-4 py-3 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl focus:ring-2 focus:ring-brand-primary/50 text-gray-800 dark:text-gray-200"><?= htmlspecialchars($settings['hero_subtitle'] ?? '') ?></textarea>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gambar latar bagian utama (kosongkan bila tidak diubah)</label>
                        <?php if(!empty($settings['hero_background'])): ?>
                            <div class="mb-3">
                                <img src="<?= base_url($settings['hero_background']) ?>" class="h-32 object-cover rounded-lg border border-gray-200 dark:border-white/10">
                            </div>
                        <?php endif; ?>
                        <?php $this->load->view('admin/components/input_berkas', ['ib_name' => 'hero_background', 'ib_accept' => 'image/*', 'ib_required' => FALSE, 'ib_attr' => '']); ?>
                    </div>
                </div>
            </div>

            <!-- SECTION ABOUT -->
            <div x-show="activeTab === 'about'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-1 translate-y-0" style="display: none;">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-white/10 pb-2 mb-4">Tentang kami</h3>
                <div class="grid grid-cols-1 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Judul tentang kami</label>
                        <textarea name="about_title" rows="2" class="w-full px-4 py-3 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl focus:ring-2 focus:ring-brand-primary/50 text-gray-800 dark:text-gray-200"><?= htmlspecialchars($settings['about_title'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Deskripsi paragraf 1</label>
                        <textarea name="about_desc_1" rows="4" class="w-full px-4 py-3 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl focus:ring-2 focus:ring-brand-primary/50 text-gray-800 dark:text-gray-200"><?= htmlspecialchars($settings['about_desc_1'] ?? '') ?></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Deskripsi paragraf 2</label>
                        <textarea name="about_desc_2" rows="4" class="w-full px-4 py-3 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl focus:ring-2 focus:ring-brand-primary/50 text-gray-800 dark:text-gray-200"><?= htmlspecialchars($settings['about_desc_2'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- SECTION FOOTER -->
            <div x-show="activeTab === 'footer'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-1 translate-y-0" style="display: none;">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white border-b border-gray-200 dark:border-white/10 pb-2 mb-4">Kaki halaman (informasi kontak)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Alamat lengkap</label>
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
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Teks hak cipta</label>
                        <input type="text" name="footer_copyright" value="<?= htmlspecialchars($settings['footer_copyright'] ?? '') ?>" class="w-full px-4 py-3 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl focus:ring-2 focus:ring-brand-primary/50 text-gray-800 dark:text-gray-200">
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-gray-200 dark:border-white/10 flex justify-end">
                <button type="submit" class="tombol-utama">
                    <i class="ph ph-floppy-disk"></i><span>Simpan perubahan</span>
                </button>
            </div>
        </form>
    </div>
</div>
