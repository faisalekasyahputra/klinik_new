<div class="flex flex-col md:flex-row justify-between md:items-end gap-4 mb-6 relative z-10">
    <div>
        <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2">Manajemen Pengguna</h2>
        <p class="text-sm text-gray-500 dark:text-brand-muted">Kelola akun, peran, dan akses pengguna dalam sistem.</p>
    </div>
    <button class="bg-blue-600 dark:bg-brand-primary text-white dark:text-brand-dark px-5 py-2.5 rounded-xl font-bold flex items-center hover:bg-blue-700 dark:hover:bg-brand-hover transition-colors shadow-sm shadow-blue-500/30 dark:shadow-brand-primary/20">
        <i class="ph ph-user-plus text-lg mr-2"></i> Tambah Pengguna Baru
    </button>
</div>

<div class="bg-white dark:bg-brand-card rounded-3xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden relative z-10">
    <div class="p-6 border-b border-gray-200 dark:border-white/5 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <i class="ph ph-users-three text-brand-primary"></i> Daftar Pengguna
        </h3>
        <div class="flex items-center gap-3">
            <div class="relative">
                <i class="ph ph-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-brand-muted"></i>
                <input type="text" placeholder="Cari pengguna..." class="pl-10 pr-4 py-2 bg-gray-50 dark:bg-white/5 border border-gray-200 dark:border-white/10 rounded-xl text-sm focus:ring-2 focus:ring-brand-primary/50 focus:outline-none w-64 text-gray-800 dark:text-gray-200">
            </div>
            <button class="w-10 h-10 rounded-xl border border-gray-200 dark:border-white/10 flex items-center justify-center text-gray-500 dark:text-brand-muted hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                <i class="ph ph-funnel text-lg"></i>
            </button>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th scope="col" class="px-6 py-4">Nama Pengguna</th>
                    <th scope="col" class="px-6 py-4">Peran (Role)</th>
                    <th scope="col" class="px-6 py-4">Status</th>
                    <th scope="col" class="px-6 py-4">Terdaftar</th>
                    <th scope="col" class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-brand-muted">
                        <div class="flex flex-col items-center justify-center">
                            <div class="w-16 h-16 mb-4 rounded-full bg-gray-100 dark:bg-white/5 flex items-center justify-center text-3xl text-gray-300 dark:text-white/20">
                                <i class="ph ph-users"></i>
                            </div>
                            <p>Data pengguna sedang dimuat atau belum tersedia.</p>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="px-6 py-4 border-t border-gray-200 dark:border-white/5 flex items-center justify-between text-xs text-gray-500 dark:text-brand-muted">
        <div>Menampilkan 0 dari 0 pengguna.</div>
    </div>
</div>
