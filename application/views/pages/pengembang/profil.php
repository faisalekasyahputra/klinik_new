<?php
$safe_url = function ($url) {
    return ($url && preg_match('#^https?://#i', $url)) ? $url : null;
};
$sosmed = [
    ['fa-brands fa-instagram', 'Instagram', $pengembang->instagram ?? null],
    ['fa-solid fa-globe', 'Website', $pengembang->website ?? null],
    ['fa-solid fa-share-nodes', 'Sosial media lain', $pengembang->sosmed_lainnya ?? null],
];
$value = function ($field, $fallback = 'Belum diisi') use ($pengembang) {
    $text = trim((string) ($pengembang->$field ?? ''));
    return $text !== '' ? $text : $fallback;
};
/* Asosiasi disimpan sebagai KODE huruf kecil (`rei`), bukan teks siap tampil -
   sebelum 14 Agt 2026 halaman ini mencetaknya mentah, jadi begitu ada satu
   saja baris terisi, profil publik akan bertuliskan "rei". Belum pernah
   terlihat cuma karena seluruh kolomnya masih NULL. */
$this->load->helper('srp2');
$asosiasi_tampil = srp2_label_asosiasi($pengembang->asosiasi ?? '', 'Belum diisi');
?>
<section class="w-full min-h-screen px-4 sm:px-6 lg:px-8 py-8 font-outfit" style="color:var(--portal-text)"><div class="max-w-4xl mx-auto">
<nav class="mb-5 text-[10px] font-bold uppercase tracking-wider" style="color:var(--portal-text-muted)"><a href="<?= base_url('Pengembang/sertifikasi') ?>" data-tab-link data-tab-key="pengembang_list" class="hover:underline" style="color:var(--teal)">Sertifikasi Pengembang</a><span class="mx-2">/</span><span><?= htmlspecialchars($pengembang->nama_perusahaan, ENT_QUOTES, 'UTF-8') ?></span></nav>
<div class="rounded-2xl p-5 sm:p-6" style="background:var(--portal-bg-card);border:1px solid var(--portal-border);box-shadow:0 8px 24px rgba(0,80,95,.06)"><div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 border-b pb-4" style="border-color:var(--portal-border)"><div><span class="text-[10px] font-extrabold uppercase tracking-[.2em]" style="color:var(--teal-bright)">Profil Pengembang</span><h1 class="mt-1 text-xl sm:text-2xl font-black" style="color:var(--portal-text)"><?= htmlspecialchars($pengembang->nama_perusahaan, ENT_QUOTES, 'UTF-8') ?></h1></div><span class="inline-flex w-fit items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold" style="background:rgba(16,185,129,.1);color:#059669"><i class="fa-solid fa-circle-check"></i> Bersertifikat</span></div>
<div class="grid sm:grid-cols-2 gap-x-8 gap-y-4 pt-5 text-xs"><div><p class="mb-1 text-[10px] font-bold uppercase tracking-wider" style="color:var(--portal-text-muted)">Asosiasi</p><p class="font-semibold" style="color:var(--portal-text)"><?= htmlspecialchars($asosiasi_tampil, ENT_QUOTES, 'UTF-8') ?></p></div><div><p class="mb-1 text-[10px] font-bold uppercase tracking-wider" style="color:var(--portal-text-muted)">Nomor keanggotaan</p><p class="font-semibold" style="color:var(--portal-text)"><?= htmlspecialchars($value('no_keanggotaan'), ENT_QUOTES, 'UTF-8') ?></p></div><div class="sm:col-span-2"><p class="mb-1 text-[10px] font-bold uppercase tracking-wider" style="color:var(--portal-text-muted)">Alamat kantor</p><p class="font-semibold leading-relaxed" style="color:var(--portal-text)"><?= nl2br(htmlspecialchars($value('alamat_kantor'), ENT_QUOTES, 'UTF-8')) ?></p></div></div></div>
<div class="mt-4 rounded-2xl p-5 sm:p-6" style="background:var(--portal-bg-card);border:1px solid var(--portal-border);box-shadow:0 8px 24px rgba(0,80,95,.06)"><div class="flex items-center justify-between border-b pb-3" style="border-color:var(--portal-border)"><h2 class="text-sm font-extrabold" style="color:var(--portal-text)">Kontak & Sosial Media</h2><i class="fa-solid fa-link text-xs" style="color:var(--teal-bright)"></i></div><div class="grid sm:grid-cols-3 gap-2.5 pt-4"><?php foreach ($sosmed as [$icon, $label, $raw]): $link = $safe_url($raw); ?><div class="rounded-xl p-3" style="background:var(--portal-bg);border:1px solid var(--portal-border)"><div class="flex items-center gap-2 text-[10px] font-bold uppercase tracking-wider" style="color:var(--portal-text-muted)"><i class="<?= $icon ?>" style="color:var(--teal-bright)"></i><?= $label ?></div><?php if ($link): ?><a href="<?= htmlspecialchars($link, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="mt-2 block truncate text-xs font-bold hover:underline" style="color:var(--teal)"><?= htmlspecialchars($raw, ENT_QUOTES, 'UTF-8') ?></a><?php else: ?><span class="mt-2 block text-xs italic" style="color:var(--portal-text-muted)">Belum diisi</span><?php endif; ?></div><?php endforeach; ?></div></div>
</div></section>
