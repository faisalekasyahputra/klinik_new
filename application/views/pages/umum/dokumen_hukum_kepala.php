<?php
/* Kepala bersama Kebijakan Privasi dan Syarat dan Ketentuan: spanduk draf, judul, tanggal.
   Sengaja hanya memakai variabel dan htmlspecialchars (tanpa helper CI) supaya
   docs/engineering/uji_halaman_hukum.php dapat merendernya di luar aplikasi.
   $draf, $diperbarui: dari config/kebijakan_data.php (Index::_data_dokumen_hukum). */
$e = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
?>
<?php if ( ! empty($draf)): ?>
<div id="spanduk-draf-hukum" role="note" class="mb-5 flex items-start gap-3 rounded-2xl border px-4 py-3 text-xs leading-relaxed" style="border-color:rgba(245,158,11,.45);background:rgba(245,158,11,.10);color:var(--portal-text)">
    <i class="fa-solid fa-triangle-exclamation mt-0.5" style="color:#d97706" aria-hidden="true"></i>
    <p><strong>Versi draf, menunggu peninjauan dinas.</strong> Isi halaman ini disusun dari cara kerja aplikasi saat ini dan belum disahkan. Bunyinya dapat berubah setelah ditinjau.</p>
</div>
<?php endif; ?>
<p class="text-xs font-black uppercase tracking-[0.18em] text-[color:var(--portal-brand)]">Klinik PKP Jawa Tengah</p>
<h1 class="mt-2 text-3xl font-black tracking-tight text-[color:var(--portal-text)]"><?= $e($judul_dokumen) ?></h1>
<p class="mt-2 text-xs text-[color:var(--portal-text-muted)]">Terakhir diperbarui: <?= $e($diperbarui) ?></p>
<p class="mt-4 text-sm leading-relaxed text-[color:var(--portal-text-muted)]"><?= $e($pengantar) ?></p>
<p class="mt-3 text-xs text-[color:var(--portal-text-muted)]">Baca juga: <a href="<?= $e($tautan_lain) ?>" class="font-bold underline" style="color:var(--teal)"><?= $e($label_lain) ?></a></p>
