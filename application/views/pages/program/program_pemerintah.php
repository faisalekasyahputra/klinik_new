<?php
/* Daftar Program Pemerintah (Index::program_pemerintah, SEO 6 Okt 2026). Satu kartu per program aktif di
   Katalog Program; kartu menaut ke halaman detail yang bisa ditemukan mesin pencari. */
$e = function ($v) { return html_escape((string) $v); };
$gaya_kartu = 'background:var(--portal-bg-card);border:1px solid var(--portal-border);box-shadow:0 8px 24px rgba(0,80,95,.06)';
?>
<section class="w-full px-4 py-8 font-outfit sm:px-6 lg:px-8" style="color:var(--portal-text)">
  <div class="mx-auto max-w-6xl">
    <header class="mb-6">
      <p class="text-[11px] font-black uppercase tracking-wider" style="color:var(--portal-brand)">Layanan Perumahan</p>
      <h1 class="mt-1 text-2xl font-black tracking-tight sm:text-3xl">Program Perumahan Pemerintah Provinsi Jawa Tengah</h1>
      <p class="mt-2 max-w-2xl text-xs leading-relaxed" style="color:var(--portal-text-muted)">
        Bantuan dan skema pembiayaan rumah dari Disperakim Provinsi Jawa Tengah. Pilih program untuk melihat sasaran,
        syarat utama, dan batas penghasilannya, lalu cek kelayakan Anda lewat pendataan.
      </p>
    </header>

    <?php if ( ! $program): ?>
      <p class="rounded-2xl px-5 py-8 text-center text-sm" style="<?= $gaya_kartu ?>">Belum ada program yang dibuka saat ini.</p>
    <?php else: ?>
    <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" data-program-daftar>
      <?php foreach ($program as $p): $slug = str_replace('_', '-', $p['kode_program']); ?>
      <li>
        <a href="<?= base_url('program-pemerintah/' . $slug) ?>" class="group flex h-full flex-col overflow-hidden rounded-2xl transition-transform hover:-translate-y-0.5" style="<?= $gaya_kartu ?>">
          <img src="<?= base_url($pm->gambar_tampil($p)) ?>" alt="<?= $e($p['nama_program']) ?>" loading="lazy" class="h-40 w-full object-cover">
          <div class="flex flex-1 flex-col p-4">
            <p class="text-[10px] font-black uppercase tracking-wider" style="color:var(--portal-brand)"><?= $e($p['nama_kategori'] ?: 'Program perumahan') ?></p>
            <h2 class="mt-1 text-base font-extrabold leading-snug"><?= $e($p['nama_program']) ?></h2>
            <p class="mt-1 flex-1 text-xs leading-relaxed" style="color:var(--portal-text-muted)"><?= $e($p['deskripsi_singkat']) ?></p>
            <div class="mt-3 flex items-center justify-between gap-2">
              <?php if ($p['lencana']): ?><span class="rounded-full px-2.5 py-1 text-[10px] font-bold" style="background:var(--portal-bg);border:1px solid var(--portal-border)"><?= $e($p['lencana']) ?></span><?php else: ?><span></span><?php endif; ?>
              <span class="text-[11px] font-bold" style="color:var(--portal-brand)">Lihat syarat <i class="fa-solid fa-arrow-right text-[10px] transition-transform group-hover:translate-x-0.5"></i></span>
            </div>
          </div>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
    <?php endif; ?>
  </div>
</section>
