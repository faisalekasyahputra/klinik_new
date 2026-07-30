<?php
/**
 * Isi menu sidebar admin — DIPAKAI DUA KALI dan itu justru maksudnya: sekali
 * oleh sidebar penuh saat halaman dimuat utuh, sekali lagi oleh
 * MY_Controller::render_user_dashboard() yang mengirimkannya ke loader
 * progresif tiap pindah halaman.
 *
 * Satu markup, satu aturan. Sebelum dipisah, sorotan aktif dirender PHP
 * sementara navigasi SPA menempelkan `aria-current` lewat JS — dua sumber
 * kebenaran yang langsung berbeda: item lama tetap menyala, item baru ikut
 * menyala, dan sub-menu cabang lama tidak pernah tertutup.
 *
 * Kedalaman BEBAS, ditentukan `parent` di dashboard_modules.php. Rekam Data
 * memakai dua tingkat: Rekam Data → Perumahan/Kawasan → Capaian/Rekap/Riwayat.
 *
 * Keadaan buka-tutup AWAL dihitung dashboard_menu() (cabang yang memuat halaman
 * sekarang terbuka). Sesudah itu penggunanya yang pegang kendali lewat tombol
 * lipat — anak SELALU dirender, hanya disembunyikan Alpine, supaya cabang lain
 * bisa dibuka tanpa berpindah halaman lebih dulu.
 */
$aktif_kelas = 'bg-blue-50 text-blue-700 dark:bg-brand-primary/10 dark:text-brand-primary';
$diam_kelas  = 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'
    . ' dark:text-brand-muted dark:hover:bg-white/5 dark:hover:text-brand-light';

/**
 * @param array $items   daftar node sejajar
 * @param int   $tingkat 0 = akar; tiap tingkat menambah indentasi
 */
$gambar = function (array $items, $tingkat) use (&$gambar, $aktif_kelas, $diam_kelas) {
    foreach ($items as $item):
        $punya_anak = ! empty($item['children']);
        $akar       = $tingkat === 0;
        ?>
        <div <?= $punya_anak ? 'x-data="{ buka: ' . (! empty($item['open']) ? 'true' : 'false') . ' }"' : '' ?>>
          <div class="relative flex items-center gap-1">
            <a href="<?= base_url($item['url']) ?>"
               <?= ! empty($item['active']) ? 'aria-current="page"' : '' ?>
               class="relative flex flex-1 items-center rounded-lg px-3 transition-all duration-200
                      <?= $akar ? 'py-2 text-sm font-medium' : 'py-1.5 text-[13px] font-medium' ?>
                      <?= ! empty($item['active']) ? $aktif_kelas : $diam_kelas ?>"
               :class="!sidebarOpen ? 'justify-center' : ''"
               title="<?= htmlspecialchars($item['label']) ?>">
              <i class="ph <?= htmlspecialchars($item['icon']) ?> shrink-0 <?= $akar ? 'text-lg' : 'text-base' ?>"
                 :class="sidebarOpen ? 'mr-3' : 'mr-0'"></i>
              <span x-show="sidebarOpen" class="flex-1 overflow-hidden whitespace-nowrap"><?= htmlspecialchars($item['label']) ?></span>
              <?php if ( ! empty($item['badge'])): ?>
                <span x-show="sidebarOpen" class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-100 text-[10px] font-bold text-red-600 dark:bg-red-500/20 dark:text-red-400"><?= (int) $item['badge'] ?></span>
                <span x-show="!sidebarOpen" class="absolute -right-1 -top-1 z-10 flex h-4 w-4 items-center justify-center rounded-full border-[1.5px] border-white bg-red-500 text-[9px] font-bold text-white dark:border-brand-card"><?= (int) $item['badge'] ?></span>
              <?php endif; ?>
            </a>

            <?php if ($punya_anak): ?>
              <?php /* Tombol lipat DIPISAH dari tautannya. Kalau jadi satu, orang
                        yang ingin membuka cabang justru berpindah halaman, dan yang
                        ingin membuka halaman induk justru cuma melipat. */ ?>
              <button type="button" @click="buka = !buka" x-show="sidebarOpen"
                      class="shrink-0 rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5 dark:hover:text-brand-light"
                      :aria-expanded="buka ? 'true' : 'false'"
                      aria-label="Buka atau tutup sub-menu <?= htmlspecialchars($item['label'], ENT_QUOTES) ?>">
                <i class="ph ph-caret-down text-sm transition-transform duration-200" :class="buka ? 'rotate-180' : ''"></i>
              </button>
            <?php endif; ?>
          </div>

          <?php if ($punya_anak): ?>
            <div class="ml-3 mt-1 space-y-1 border-l border-gray-200 pl-2 dark:border-white/10"
                 x-show="buka && sidebarOpen" x-transition.opacity>
              <?php $gambar($item['children'], $tingkat + 1); ?>
            </div>
          <?php endif; ?>
        </div>
        <?php
    endforeach;
};
?>
<?php foreach (($dashboard_menu ?? []) as $group_label => $menu_items): ?>
  <div class="mb-2 ml-2 mt-6 overflow-hidden whitespace-nowrap text-[10px] font-bold uppercase tracking-wider text-gray-400 transition-all duration-200 first:mt-0 dark:text-brand-muted/70" x-show="sidebarOpen"><?= htmlspecialchars($group_label) ?></div>
  <nav class="relative space-y-1"><?php $gambar($menu_items, 0); ?></nav>
<?php endforeach; ?>
