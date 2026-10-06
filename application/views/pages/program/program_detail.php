<?php
/* Satu program pemerintah (Index::program_pemerintah, SEO 6 Okt 2026). Isi dari Katalog Program (sf_program);
   tombol utama mengarah ke pendataan warga, tempat kelayakan sungguhan dihitung. */
$e = function ($v) { return html_escape((string) $v); };
$gaya_kartu = 'background:var(--portal-bg-card);border:1px solid var(--portal-border);box-shadow:0 8px 24px rgba(0,80,95,.06)';
$gaya_tombol = 'background:var(--portal-brand);color:var(--portal-btn-text);border:1px solid var(--portal-brand)';
?>
<section class="w-full px-4 py-8 font-outfit sm:px-6 lg:px-8" style="color:var(--portal-text)" data-program-detail="<?= $e($p['kode_program']) ?>">
  <div class="mx-auto max-w-5xl">
    <nav class="mb-4 text-[11px] font-bold" aria-label="Jejak halaman" style="color:var(--portal-text-muted)">
      <a href="<?= base_url() ?>" class="hover:underline">Beranda</a> <span aria-hidden="true">/</span>
      <a href="<?= base_url('program-pemerintah') ?>" class="hover:underline">Program Pemerintah</a> <span aria-hidden="true">/</span>
      <span style="color:var(--portal-text)"><?= $e($p['nama_program']) ?></span>
    </nav>

    <article class="overflow-hidden rounded-2xl" style="<?= $gaya_kartu ?>">
      <img src="<?= base_url($gambar) ?>" alt="<?= $e($p['nama_program']) ?>" class="h-56 w-full object-cover sm:h-72">
      <div class="p-5 sm:p-7">
        <p class="text-[11px] font-black uppercase tracking-wider" style="color:var(--portal-brand)"><?= $e($p['nama_kategori'] ?: 'Program perumahan') ?></p>
        <h1 class="mt-1 text-2xl font-black tracking-tight sm:text-3xl"><?= $e($p['nama_program']) ?></h1>
        <?php if ($p['lencana']): ?>
          <p class="mt-2 inline-block rounded-full px-3 py-1 text-[11px] font-bold" style="background:var(--portal-bg);border:1px solid var(--portal-border)">Sasaran: <?= $e($p['lencana']) ?></p>
        <?php endif; ?>
        <p class="mt-4 max-w-3xl text-sm leading-relaxed"><?= $e($p['deskripsi_singkat']) ?></p>

        <div class="mt-5 rounded-xl p-4" style="background:var(--portal-bg);border:1px solid var(--portal-border)">
          <p class="text-[10px] font-black uppercase tracking-wider" style="color:var(--portal-text-muted)">Syarat utama</p>
          <p class="mt-1 text-sm font-semibold"><?= $e($p['syarat_utama'] ?: 'Dinilai dari data pendataan rumah dan penghasilan Anda.') ?></p>
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-3">
          <a href="<?= base_url('warga/pendataan') ?>" class="rounded-xl px-5 py-2.5 text-xs font-bold" style="<?= $gaya_tombol ?>">Cek kelayakan saya</a>
          <p class="text-[11px]" style="color:var(--portal-text-muted)">Kelayakan dihitung dari pendataan rumah Anda; perlu masuk dengan akun Klinik PKP.</p>
        </div>
      </div>
    </article>

    <?php if ($lain): ?>
    <section class="mt-8">
      <h2 class="text-sm font-extrabold">Program lain</h2>
      <ul class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($lain as $x): ?>
        <li><a href="<?= base_url('program-pemerintah/' . str_replace('_', '-', $x['kode_program'])) ?>" class="flex items-center gap-3 rounded-xl p-3 hover:underline" style="<?= $gaya_kartu ?>">
          <img src="<?= base_url($pm->gambar_tampil($x)) ?>" alt="" loading="lazy" class="h-12 w-16 shrink-0 rounded-lg object-cover">
          <span class="text-xs font-bold"><?= $e($x['nama_program']) ?></span>
        </a></li>
        <?php endforeach; ?>
      </ul>
    </section>
    <?php endif; ?>
  </div>
</section>
