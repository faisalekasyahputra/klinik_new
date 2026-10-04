<header class="admin-topbar h-16 bg-white dark:bg-[#0a1a1f] border-b border-gray-200 dark:border-white/5 flex items-center justify-between px-6 z-40 sticky top-0">
    <div class="flex items-center gap-6">
        <button @click="sidebarOpen = !sidebarOpen" :aria-expanded="sidebarOpen.toString()" aria-label="Buka atau tutup menu navigasi" class="w-10 h-10 rounded-xl flex items-center justify-center text-gray-500 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/5 hover:text-gray-900 dark:hover:text-white transition-all">
            <i class="ph ph-list text-2xl"></i>
        </button>
        
    </div>
    
    <div class="flex items-center space-x-2">
        <?php
        /* "Perlu tindakan" (4 Okt 2026): ringkasan badge sidebar per modul, menaut ke bagiannya di
           Pusat Pemberitahuan. Angkanya DIAMBIL dari $dashboard_menu yang sudah dihitung
           dashboard_menu() untuk sidebar, jadi nol query tambahan dan pasti sama dengan menu.
           Terpisah dari tombol lonceng Web Push di sebelahnya (admin-web-push.js memegang klik
           tombol itu untuk berlangganan), supaya izin notifikasi HP tidak terpicu dari sini.
           Topbar tidak ikut ditukar navigasi progresif, jadi angkanya diperbarui pada muat
           halaman penuh berikutnya; sidebar diperbarui tiap pindah halaman. */
        $perlu_tindakan = [];
        $staf_tindakan = in_array($this->session->userdata('role'), ['admin', 'admin_kabkota', 'admin_bidang'], TRUE);
        if ($staf_tindakan) {
            $kumpul_tindakan = function (array $items) use (&$kumpul_tindakan, &$perlu_tindakan) {
                foreach ($items as $it) {
                    if ( ! empty($it['badge'])) { $perlu_tindakan[] = $it; }
                    $kumpul_tindakan($it['children'] ?? []);
                }
            };
            foreach (($dashboard_menu ?? []) as $grup_tindakan) { $kumpul_tindakan($grup_tindakan); }
        }
        $total_tindakan = array_sum(array_column($perlu_tindakan, 'badge'));
        ?>
        <?php if ($staf_tindakan): ?>
        <div class="relative" x-data="{ tindakanOpen: false }" @click.outside="tindakanOpen = false" @keydown.escape="tindakanOpen = false">
            <button type="button" @click="tindakanOpen = !tindakanOpen" :aria-expanded="tindakanOpen.toString()" aria-haspopup="true"
                    aria-label="Perlu tindakan: <?= (int) $total_tindakan ?>" title="Perlu tindakan"
                    class="relative w-10 h-10 rounded-xl flex items-center justify-center text-gray-500 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/5 hover:text-gray-900 transition-all">
                <i class="ph ph-tray text-xl" aria-hidden="true"></i>
                <?php if ($total_tindakan > 0): ?>
                <span class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs font-bold text-white"><?= $total_tindakan > 99 ? '99+' : (int) $total_tindakan ?></span>
                <?php endif; ?>
            </button>
            <div x-show="tindakanOpen" x-cloak x-transition.opacity.duration.200ms class="absolute right-0 top-full mt-3 w-64 bg-white dark:bg-brand-card border border-gray-100 dark:border-white/10 rounded-xl shadow-lg overflow-hidden z-50">
                <div class="px-4 py-3 border-b border-gray-100 dark:border-white/10 text-sm font-bold text-gray-900 dark:text-white">Perlu tindakan</div>
                <?php if ($perlu_tindakan): ?>
                <div class="p-2 space-y-1">
                    <?php foreach ($perlu_tindakan as $it): ?>
                    <a href="<?= base_url('pemberitahuan#modul-' . $it['key']) ?>" data-perlu-tindakan="<?= html_escape($it['key']) ?>" data-no-page-transition
                       title="<?= html_escape((string) ($it['badge_judul'] ?? '')) ?>"
                       class="flex items-center justify-between gap-3 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-white/5">
                        <span class="truncate"><?= html_escape($it['label']) ?></span>
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-xs font-bold text-red-600 dark:bg-red-500/20 dark:text-red-400"><?= (int) $it['badge'] ?></span>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="px-4 py-3 text-sm text-gray-500 dark:text-brand-muted">Tidak ada yang menunggu tindakan.</p>
                <?php endif; ?>
                <a href="<?= base_url('pemberitahuan') ?>" data-perlu-tindakan-semua class="block border-t border-gray-100 dark:border-white/10 px-4 py-3 text-sm font-bold text-blue-600 dark:text-brand-primary hover:bg-gray-100 dark:hover:bg-white/5">Lihat semua</a>
            </div>
        </div>
        <?php endif; ?>

        <?php if (in_array($this->session->userdata('role'), ['admin', 'admin_kabkota', 'admin_bidang', 'warga'], TRUE)): ?>
        <!-- Izin Web Push hanya diminta setelah klik pengguna, sesuai aturan browser/iOS. -->
        <button type="button" data-web-push-toggle data-state="loading"
                data-config-url="<?= base_url('push/config') ?>"
                data-subscribe-url="<?= base_url('push/subscribe') ?>"
                data-unsubscribe-url="<?= base_url('push/unsubscribe') ?>"
                data-sw-url="<?= base_url('push-sw.js') ?>"
                data-csrf-name="<?= html_escape($this->security->get_csrf_token_name()) ?>"
                data-csrf-hash="<?= html_escape($this->security->get_csrf_hash()) ?>"
                class="h-10 rounded-xl px-3 flex items-center gap-2 text-gray-500 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/5 hover:text-gray-900 transition-all disabled:cursor-not-allowed disabled:opacity-60"
                aria-label="Memeriksa notifikasi" title="Memeriksa notifikasi">
            <i class="ph ph-bell text-xl"></i>
            <span data-web-push-label class="hidden xl:inline text-xs font-bold">Memeriksa notifikasi...</span>
        </button>
        <?php endif; ?>

        <!-- Theme Toggle -->
        <button type="button" @click="darkMode = !darkMode" :aria-pressed="darkMode.toString()" aria-label="Mode gelap" title="Mode gelap" class="w-10 h-10 rounded-xl flex items-center justify-center text-gray-500 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/5 hover:text-gray-900 dark:hover:text-brand-primary transition-all relative overflow-hidden group">
            <div class="absolute inset-0 bg-brand-primary/10 translate-y-full group-hover:translate-y-0 transition-transform duration-300 rounded-xl"></div>
            <i class="ph ph-sun text-xl relative z-10" x-show="!darkMode"></i>
            <i class="ph ph-moon text-xl relative z-10" x-show="darkMode" style="display: none;"></i>
        </button>
        
        <div class="w-px h-6 bg-gray-200 dark:bg-white/10 mx-2"></div>
        
        <!-- User Menu -->
        <div class="relative" x-data="{ userMenuOpen: false }">
            <div @click="userMenuOpen = !userMenuOpen" @click.away="userMenuOpen = false" class="flex items-center gap-3 pl-2 cursor-pointer group">
                <div class="text-right hidden md:block">
                    <div class="text-sm font-bold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-brand-primary transition-colors"><?= html_escape($this->session->userdata('name') ?: 'Administrator') ?></div>
                    <div class="text-[10px] text-gray-500 dark:text-brand-muted uppercase tracking-wider font-semibold">
                        <?php
                        /* Sampai 21 Agt 2026, KKN (universitas) dan Magang
                           (perorangan) berbagi SATU role 'mahasiswa' - label
                           di layar disamarkan jadi "Universitas" lewat kasus
                           khusus di sini, role internalnya sendiri tidak
                           berubah. 22 Agt 2026: role 'universitas' berdiri
                           sendiri (config/roles.php, KemitraanPortal::
                           akses_universitas()), jadi ucwords() generik di
                           bawah sudah cukup - tidak perlu kasus khusus lagi. */
                        $peran = $this->session->userdata('role');
                        echo $peran ? ucwords(str_replace('_', ' ', $peran)) : 'Belum Memilih Peran';
                        ?>
                    </div>
                </div>
                <div class="relative">
                    <img src="<?= $this->session->userdata('avatar') ?: avatar_inisial($this->session->userdata('name') ?: 'Admin') ?>" alt="Avatar" class="h-10 w-10 rounded-xl object-cover border-2 border-transparent group-hover:border-brand-primary transition-all shadow-sm">
                    <div class="absolute bottom-[-2px] right-[-2px] w-3.5 h-3.5 bg-green-500 border-2 border-white dark:border-[#0a1a1f] rounded-full"></div>
                </div>
                <i class="ph ph-caret-down text-gray-400 dark:text-brand-muted text-xs transition-transform duration-200" :class="userMenuOpen ? 'rotate-180' : ''"></i>
            </div>
            
            <!-- Dropdown -->
            <div x-show="userMenuOpen" x-transition.opacity.duration.200ms class="absolute right-0 top-full mt-3 w-48 bg-white dark:bg-brand-card border border-gray-100 dark:border-white/10 rounded-xl shadow-lg overflow-hidden z-50">
                <?php
                // Satu halaman profil untuk SEMUA role (akun/profil). Dulu link ini
                // hardcode ke User_Profile yang digate superadmin, jadi role lain
                // diusir ke login (B3); lalu User_Profile sendiri dilebur ke sini (B9).
                ?>
                <div class="p-2 space-y-1">
                    <a href="<?= base_url('akun/profil') ?>" class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-white/5 rounded-lg transition-colors">
                        <i class="ph ph-user text-lg"></i> Profil Saya
                    </a>
                    <div class="h-px bg-gray-100 dark:bg-white/10 my-1 mx-2"></div>
                    <a href="<?= base_url('Auth/logout') ?>" class="flex items-center gap-2 px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-500/10 rounded-lg transition-colors">
                        <i class="ph ph-sign-out text-lg"></i> Keluar
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
