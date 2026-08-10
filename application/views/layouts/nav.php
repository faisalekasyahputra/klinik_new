<?php 
  // Auth button desktop sudah inline di tab bar (main.php)
  // nav.php sekarang hanya mobile menu overlay + hamburger
?>
<!-- Mobile Hamburger (floating) -->
<button @click="mobileMenu = !mobileMenu" class="lg:hidden fixed top-3 right-3 z-50 w-9 h-9 rounded-xl bg-[#0d2228]/90 backdrop-blur-md border border-[#d6fb00]/20 hover:border-[#d6fb00] flex items-center justify-center text-[#8aacb0] hover:text-[#d6fb00] transition-all duration-200 shadow-lg shadow-black/30">
    <i class="fa-solid fa-bars text-sm"></i>
</button>

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
<?php // Perumahan / Kawasan / Pertanahan disembunyikan 1 Agu 2026, sejalan dengan tab bar di main.php ?>
        <a href="<?= base_url('tab/pengembang') ?>" class="block text-zinc-400 hover:text-white py-3 text-sm border-b border-[#d6fb00]/20" @click="mobileMenu = false">Pengembang</a>
        <a href="<?= base_url('tab/bankdata') ?>" class="block text-zinc-400 hover:text-white py-3 text-sm border-b border-[#d6fb00]/20" @click="mobileMenu = false">Bank Data</a>

        <div class="pt-6">
            <?php if ($this->session->userdata('is_logged')): ?>
                <?php
                    $avatar_src_m = $this->session->userdata('avatar');
                    if (empty($avatar_src_m)) {
                        // Dulu ui-avatars.com - lihat catatan di main.php.
                        $avatar_src_m = avatar_inisial($this->session->userdata('username') ?: $this->session->userdata('name') ?: 'User');
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
