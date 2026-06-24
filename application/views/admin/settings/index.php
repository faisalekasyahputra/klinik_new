<div class="flex flex-col md:flex-row justify-between md:items-end gap-4 mb-6 relative z-10">
    <div>
        <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2">Pengaturan Sistem</h2>
        <p class="text-sm text-gray-500 dark:text-brand-muted">Konfigurasi variabel utama, preferensi antarmuka, dan integrasi API.</p>
    </div>
    <button class="bg-blue-600 dark:bg-brand-primary text-white dark:text-brand-dark px-5 py-2.5 rounded-xl font-bold flex items-center hover:bg-blue-700 dark:hover:bg-brand-hover transition-colors shadow-sm shadow-blue-500/30 dark:shadow-brand-primary/20">
        <i class="ph ph-floppy-disk text-lg mr-2"></i> Simpan Perubahan
    </button>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 relative z-10">
    <!-- Sidebar Navigation for Settings -->
    <div class="lg:col-span-1 space-y-2">
        <a href="#" class="flex items-center px-4 py-3 bg-white dark:bg-brand-card rounded-2xl border border-blue-200 dark:border-brand-primary/30 shadow-sm text-blue-700 dark:text-brand-primary font-semibold transition-all">
            <i class="ph ph-globe text-xl mr-3"></i> Informasi Website
        </a>
        <a href="#" class="flex items-center px-4 py-3 bg-transparent hover:bg-white dark:hover:bg-brand-card rounded-2xl border border-transparent hover:border-gray-200 dark:hover:border-white/5 text-gray-600 dark:text-brand-muted hover:text-gray-900 dark:hover:text-white transition-all">
            <i class="ph ph-palette text-xl mr-3"></i> Tampilan & Tema
        </a>
        <a href="#" class="flex items-center px-4 py-3 bg-transparent hover:bg-white dark:hover:bg-brand-card rounded-2xl border border-transparent hover:border-gray-200 dark:hover:border-white/5 text-gray-600 dark:text-brand-muted hover:text-gray-900 dark:hover:text-white transition-all">
            <i class="ph ph-shield-check text-xl mr-3"></i> Keamanan & Akses
        </a>
        <a href="#" class="flex items-center px-4 py-3 bg-transparent hover:bg-white dark:hover:bg-brand-card rounded-2xl border border-transparent hover:border-gray-200 dark:hover:border-white/5 text-gray-600 dark:text-brand-muted hover:text-gray-900 dark:hover:text-white transition-all">
            <i class="ph ph-plug text-xl mr-3"></i> Integrasi Layanan (API)
        </a>
    </div>
    
    <!-- Settings Form Area -->
    <div class="lg:col-span-2 bg-white dark:bg-brand-card rounded-3xl shadow-sm border border-gray-200 dark:border-white/5 p-6">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-6 pb-4 border-b border-gray-200 dark:border-white/10 flex items-center gap-2">
            <i class="ph ph-globe text-brand-primary"></i> Informasi Website
        </h3>
        
        <form class="space-y-5">
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Nama Sistem/Aplikasi</label>
                <input type="text" value="KlinikPKP v2.0" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-[#0a1a1f] border border-gray-200 dark:border-white/10 rounded-xl focus:ring-2 focus:ring-brand-primary/50 focus:border-brand-primary/30 outline-none text-gray-800 dark:text-gray-200 transition-all">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Deskripsi Singkat (SEO)</label>
                <textarea rows="3" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-[#0a1a1f] border border-gray-200 dark:border-white/10 rounded-xl focus:ring-2 focus:ring-brand-primary/50 focus:border-brand-primary/30 outline-none text-gray-800 dark:text-gray-200 transition-all resize-none">Sistem informasi pelayanan perumahan dan kawasan permukiman Disperakim Provinsi Jawa Tengah.</textarea>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Email Kontak</label>
                    <input type="email" value="admin@disperakim.jatengprov.go.id" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-[#0a1a1f] border border-gray-200 dark:border-white/10 rounded-xl focus:ring-2 focus:ring-brand-primary/50 focus:border-brand-primary/30 outline-none text-gray-800 dark:text-gray-200 transition-all">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Nomor Telepon</label>
                    <input type="text" value="(024) 1234567" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-[#0a1a1f] border border-gray-200 dark:border-white/10 rounded-xl focus:ring-2 focus:ring-brand-primary/50 focus:border-brand-primary/30 outline-none text-gray-800 dark:text-gray-200 transition-all">
                </div>
            </div>
            
            <div class="pt-4 flex items-center justify-between border-t border-gray-200 dark:border-white/10 mt-6">
                <div>
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">Mode Pemeliharaan (Maintenance)</h4>
                    <p class="text-xs text-gray-500 dark:text-brand-muted">Matikan akses publik ke website jika sedang ada pembaruan sistem.</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer dark:bg-[#0a1a1f] peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-white/10 peer-checked:bg-blue-600 dark:peer-checked:bg-brand-primary"></div>
                </label>
            </div>
        </form>
    </div>
</div>
