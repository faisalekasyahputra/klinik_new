<?php $this->load->view('admin/layouts/head'); ?>
<?php $this->load->view('components/notification_center'); $this->load->view('components/file_viewer_modal'); ?>

<?php /* Batas desktop 1024, bukan 768 (audit UI 2 Okt 2026). Di 768 sidebar 256px
         dulu terbuka permanen dan menyisakan ~447px untuk isi, jadi setiap tabel
         menggulir di wadahnya. Di bawah 1024 sidebar kini panel geser yang
         tertutup secara bawaan; di desktop tombol menu tetap menyempitkannya
         jadi 80px. Angka 1024 juga dipakai media query .admin-sidebar dan
         .admin-kolom di layouts/head.php - ubah ketiganya bersamaan. */ ?>
<div x-data="{ sidebarOpen: false, desktop: window.innerWidth >= 1024 }"
     x-init="sidebarOpen = desktop"
     @resize.window="sidebarOpen = (desktop && window.innerWidth < 1024) ? false : sidebarOpen; desktop = window.innerWidth >= 1024"
     @keydown.escape.window="if (!desktop) sidebarOpen = false"
     class="admin-shell flex h-screen w-full bg-[#f8fafc] dark:bg-brand-dark">
    <!-- Sidebar -->
    <?php $this->load->view('admin/layouts/sidebar', [
        'dashboard_home' => $dashboard_home ?? 'akun',
        'dashboard_menu' => $dashboard_menu ?? [],
    ]); ?>
    <button x-cloak x-show="!desktop && sidebarOpen" @click="sidebarOpen = false"
            class="admin-sidebar-backdrop"
            :style="!desktop && sidebarOpen ? 'display:block !important;position:fixed !important;inset:0 !important;z-index:50 !important;background:rgba(10,26,31,.55);' : ''"
            aria-label="Tutup menu navigasi"></button>
    
    <!-- Main Content Wrapper -->
    <div class="admin-kolom min-w-0 flex-1 flex flex-col h-screen overflow-hidden relative">
        
        <?php /* Latar admin POLOS (permintaan pemilik produk 2 Okt 2026): satu warna netral di terang
                 dan satu di gelap, tanpa batik, glow, atau gradasi. Dulu di sini ada pola batik kawung
                 SVG dan dua glow blur yang membuat kartu tanpa latar tampak tembus. Portal publik tidak
                 terpengaruh; uji_regresi_tampilan.php menjaga shell admin tetap tanpa gradasi/batik. */ ?>

        <!-- Topbar -->
        <?php $this->load->view('admin/layouts/topbar'); ?>
        
        <!-- Main Content Area -->
        <?php /* `relative` TANPA `z-10` - dan hilangnya satu kelas itu yang membuat
                 modal admin bisa tampil sama sekali.

                 `position:relative` + `z-index` bernilai = STACKING CONTEXT. Selama
                 `#main-content` ber-`z-10`, setiap `z-50` di DALAMNYA cuma berlaku
                 relatif terhadap sesamanya di konteks itu - jadi modal `z-50`
                 tetap dicat DI BAWAH topbar (`z-40`) dan sidebar (`z-20`), yang
                 saudara-saudaranya di luar. Dilaporkan user 4 Agt 2026 sebagai
                 "modalnya tidak muncul karena tertumpuk".

                 `relative` dipertahankan (dipakai penempatan di dalamnya); yang
                 dibuang hanya z-index-nya. main tetap tercat di bawah sidebar &
                 topbar lewat urutan dokumen, jadi nol yang berubah secara visual. */ ?>
        <main id="main-content" class="admin-main flex-1 min-h-0 overflow-x-hidden overflow-y-auto p-6 relative custom-scrollbar">
            <!-- Injected Content -->
            <div class="animate-[fadeIn_0.3s_ease-out]">
                <?= isset($content) ? $content : '' ?>
            </div>
        </main>
        
        <!-- Footer (Fixed at bottom) -->
        <?php $this->load->view('admin/layouts/footer'); ?>
    </div>
</div>
