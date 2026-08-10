<?php
/**
 * Layar "akses ditolak" — modal besar di tengah halaman.
 *
 * Butir 24 putaran 2. Sampai 10 Agt 2026, orang yang SUDAH login lalu membuka
 * halaman milik peran lain dilempar ke halaman MASUK — padahal sesinya sehat.
 * Yang dialami: menekan sesuatu, diminta login lagi tanpa sebab, lalu terlempar
 * entah ke mana. Ini layar yang menggantikannya.
 *
 * DITULIS SEBAGAI HALAMAN, BUKAN <dialog>. Modal yang bisa ditutup akan
 * meninggalkan orang di halaman kosong di belakangnya — tidak ada apa pun untuk
 * kembali dilihat, karena aksesnya memang ditolak. Jadi kotaknya dipasang di
 * tengah halaman tanpa tombol tutup, dan SETIAP jalan keluarnya adalah tautan
 * yang benar-benar membawa ke suatu tempat. Tidak ada jalan buntu.
 */
$label_peran = [
    'warga'          => 'Warga',
    'pengembang'     => 'Pengembang',
    'mahasiswa'      => 'Mahasiswa',
    'admin_kabkota'  => 'Admin Kabupaten/Kota',
    'admin_bidang'   => 'Admin Bidang',
    'admin'          => 'Superadmin',
];
$peran_saya = $label_peran[$peran] ?? 'Belum ditentukan';
?>
<section class="mx-auto flex min-h-[70vh] max-w-2xl items-center px-4 py-10">
  <div class="w-full rounded-2xl border p-6 sm:p-8"
       style="background:var(--portal-bg-card);border-color:var(--portal-border);color:var(--portal-text)">

    <p class="text-[10px] font-bold uppercase tracking-[.18em]" style="color:#b45309">Akses ditolak</p>
    <h1 class="mt-1 text-xl font-black sm:text-2xl"><?= html_escape($judul) ?></h1>

    <p class="mt-3 text-sm leading-relaxed" style="color:var(--portal-text-muted)">
      <strong>Anda tetap masuk seperti biasa &mdash; tidak perlu login ulang.</strong>
      Yang tidak cocok adalah perannya: halaman yang Anda tuju diperuntukkan bagi peran lain.
    </p>

    <dl class="mt-4 grid gap-2 rounded-xl border p-3 text-xs sm:grid-cols-2"
        style="border-color:var(--portal-border)">
      <div>
        <dt style="color:var(--portal-text-muted)">Peran akun Anda</dt>
        <dd class="mt-0.5 font-bold"><?= html_escape($peran_saya) ?></dd>
      </div>
      <?php if ($tujuan !== ''): ?>
      <div>
        <dt style="color:var(--portal-text-muted)">Halaman yang dituju</dt>
        <dd class="mt-0.5 font-mono font-bold break-all"><?= html_escape($tujuan) ?></dd>
      </div>
      <?php endif; ?>
    </dl>

    <?php if (trim((string) $pesan) !== ''): ?>
      <p class="mt-3 rounded-xl border p-3 text-xs leading-relaxed"
         style="background:rgba(245,158,11,.1);border-color:rgba(245,158,11,.28);color:#92400e">
        <?= html_escape($pesan) ?>
      </p>
    <?php endif; ?>

    <p class="mt-4 text-xs leading-relaxed" style="color:var(--portal-text-muted)">
      Bila Anda merasa seharusnya berhak membuka halaman ini, hubungi superadmin untuk memeriksa
      peran akun Anda &mdash; bukan dengan membuat akun baru, karena satu orang cukup satu akun.
    </p>

    <div class="mt-5 flex flex-wrap gap-2">
      <a href="<?= base_url($tujuan_pulang) ?>"
         class="rounded-xl px-4 py-2 text-sm font-bold"
         style="background:var(--portal-brand);color:#0a1a1f">
        <?= $tujuan_pulang === '' ? 'Kembali ke beranda' : 'Ke dashboard saya' ?>
      </a>
      <?php if ($tujuan_pulang !== ''): ?>
        <a href="<?= base_url() ?>" class="rounded-xl border px-4 py-2 text-sm font-bold"
           style="border-color:var(--portal-border);color:var(--portal-text)">Kembali ke beranda</a>
      <?php endif; ?>
      <a href="<?= base_url('Auth/logout') ?>" class="rounded-xl border px-4 py-2 text-sm font-bold"
         style="border-color:var(--portal-border);color:var(--portal-text-muted)">Keluar &amp; masuk akun lain</a>
    </div>
  </div>
</section>
