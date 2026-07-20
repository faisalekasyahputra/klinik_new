<?php 
  $class = strtolower($this->router->fetch_class());
  $method = strtolower($this->router->fetch_method());
?>
<!-- Floating Auth Button — fixed top-right, overlaps content -->
<div class="fixed top-3 right-3 sm:top-4 sm:right-5 z-50 flex items-center gap-2">
    <?php if ($this->session->userdata('is_logged')): ?>
        <!-- Logged-in User -->
        <div class="flex items-center gap-1.5 p-1 bg-[#0d2228]/90 backdrop-blur-md border border-[#d6fb00]/15 rounded-xl shadow-lg shadow-black/30">
            <?php if ($this->session->userdata('role') === 'admin'): ?>
                <a href="<?= base_url('Admin_Dashboard') ?>" class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg hover:bg-[#d6fb00]/15 text-[#d6fb00] transition-colors" title="Dashboard Admin">
                    <i class="fa-solid fa-gauge-high text-[10px]"></i>
                    <span class="text-[11px] font-semibold hidden sm:inline">Dashboard</span>
                </a>
                <div class="w-px h-4 bg-[#d6fb00]/20"></div>
            <?php endif; ?>
            
            <?php 
                $avatar_src = $this->session->userdata('avatar');
                if (empty($avatar_src)) {
                    $fallback_name = urlencode($this->session->userdata('username') ?: $this->session->userdata('name') ?: 'User');
                    $avatar_src = "https://ui-avatars.com/api/?name={$fallback_name}&background=d6fb00&color=0a1a1f&bold=true";
                }
            ?>
            <a href="<?= base_url('akun') ?>" class="flex items-center gap-1.5 px-2 py-1 rounded-lg hover:bg-[#d6fb00]/15 transition-colors group">
                <img src="<?= $avatar_src ?>" class="w-6 h-6 rounded-md object-cover border border-[#d6fb00]/20 group-hover:border-[#d6fb00]/40 transition-colors">
                <span class="text-[11px] font-semibold text-[#ecffb6] hidden sm:inline"><?= $this->session->userdata('username') ?: $this->session->userdata('name') ?></span>
            </a>
        </div>
    <?php else: ?>
        <!-- Login Button: Floating Pill -->
        <a href="<?= base_url('Auth/login') ?>" class="hidden lg:flex items-center gap-1.5 btn-primary text-xs px-4 py-2 rounded-xl shadow-lg shadow-[#d6fb00]/10">
            <i class="fa-solid fa-right-to-bracket text-[11px]"></i>
            Masuk
        </a>
    <?php endif; ?>

    <!-- Mobile Hamburger -->
    <button @click="mobileMenu = !mobileMenu" class="lg:hidden w-9 h-9 rounded-xl bg-[#0d2228]/90 backdrop-blur-md border border-[#d6fb00]/20 hover:border-[#d6fb00] flex items-center justify-center text-[#8aacb0] hover:text-[#d6fb00] transition-all duration-200 shadow-lg shadow-black/30">
        <i class="fa-solid fa-bars text-sm"></i>
    </button>
</div>

<!-- ============================================================
     MOBILE MENU (Alpine.js)
     ============================================================ -->
<div x-show="mobileMenu" x-cloak x-transition.opacity class="lg:hidden fixed inset-0 bg-[#0a1a1f]/85 backdrop-blur-sm z-40" @click="mobileMenu = false"></div>
<div x-show="mobileMenu" x-cloak x-transition:enter="transition transform ease-out duration-300" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition transform ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
     class="lg:hidden fixed top-0 right-0 h-full w-[300px] bg-[#0d2228] border-l border-[#d6fb00]/20 z-50 overflow-y-auto">
    <div class="p-6 space-y-1">
        <div class="flex items-center justify-between mb-8">
            <span class="text-xs font-bold text-zinc-500 uppercase tracking-widest">Menu</span>
            <button @click="mobileMenu = false" class="w-8 h-8 flex items-center justify-center rounded-lg bg-[#d6fb00]/8 border border-[#d6fb00]/20 text-zinc-400 hover:text-[#d6fb00] hover:bg-[#d6fb00]/10 transition-colors"><i class="fa-solid fa-xmark text-sm"></i></button>
        </div>

        <a href="<?= base_url() ?>" class="block text-zinc-400 hover:text-white py-3 text-sm border-b border-[#d6fb00]/20" @click="mobileMenu = false">Beranda</a>
        <a href="<?= base_url('tab/perumahan') ?>" class="block text-zinc-400 hover:text-white py-3 text-sm border-b border-[#d6fb00]/20" @click="mobileMenu = false">Perumahan</a>
        <a href="<?= base_url('tab/kawasan') ?>" class="block text-zinc-400 hover:text-white py-3 text-sm border-b border-[#d6fb00]/20" @click="mobileMenu = false">Kawasan</a>
        <a href="<?= base_url('tab/pertanahan') ?>" class="block text-zinc-400 hover:text-white py-3 text-sm border-b border-[#d6fb00]/20" @click="mobileMenu = false">Pertanahan</a>
        <a href="<?= base_url('tab/pengembang') ?>" class="block text-zinc-400 hover:text-white py-3 text-sm border-b border-[#d6fb00]/20" @click="mobileMenu = false">Pengembang</a>
        <a href="<?= base_url('tab/bankdata') ?>" class="block text-zinc-400 hover:text-white py-3 text-sm border-b border-[#d6fb00]/20" @click="mobileMenu = false">Bank Data</a>

        <div class="pt-6">
            <?php if ($this->session->userdata('is_logged')): ?>
                <?php
                    $avatar_src_m = $this->session->userdata('avatar');
                    if (empty($avatar_src_m)) {
                        $fallback_name_m = urlencode($this->session->userdata('username') ?: $this->session->userdata('name') ?: 'User');
                        $avatar_src_m = "https://ui-avatars.com/api/?name={$fallback_name_m}&background=d6fb00&color=0a1a1f&bold=true";
                    }
                ?>
                <div class="flex items-center gap-3 p-3 bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl mb-3">
                    <img src="<?= $avatar_src_m ?>" class="w-9 h-9 rounded-lg object-cover ring-1 ring-[#d6fb00]/20">
                    <div>
                        <p class="text-[#ecffb6] text-xs font-semibold"><?= $this->session->userdata('username') ?: $this->session->userdata('name') ?></p>
                        <p class="text-[#5a7a80] text-[10px]"><?= $this->session->userdata('email') ?></p>
                    </div>
                </div>
                <a href="#" onClick="logout()" class="block w-full text-center text-[#ff6b6b] text-xs font-semibold py-2.5 border border-[#ff6b6b]/20 rounded-xl hover:bg-[#ff6b6b]/10 transition-all duration-200">Keluar</a>
            <?php else: ?>
                <a href="<?= base_url('Auth/login') ?>" class="flex items-center justify-center gap-2 w-full btn-primary text-xs py-3 rounded-xl">
                    <i class="fa-solid fa-right-to-bracket text-sm"></i>
                    Masuk
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
