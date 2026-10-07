<?php
/* Catat KKN atas nama universitas (Admin_Kemitraan::simpan_catat, keputusan user 7 Okt 2026). Untuk KKN yang
   berjalan sebelum aplikasi selesai: dicatat dinas langsung Diterima, roster dan tanggal sertifikat menyusul. */
$v = fn($k) => html_escape((string) ($isian[$k] ?? ''));
$label = 'block text-xs text-gray-500 dark:text-brand-muted';
$isian_kelas = 'mt-1 w-full rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-white/10 dark:text-white';
$petunjuk = 'mt-1 block text-[11px] text-gray-500 dark:text-brand-muted';
$this->load->view('admin/kemitraan/_tabs', ['tab_aktif' => 'sertifikat']);
?>
<form action="<?= base_url('Admin_Kemitraan/simpan_catat') ?>" method="post" class="kartu-admin isi-kartu max-w-2xl space-y-4" data-catat-kkn>
    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
    <div>
        <h2 class="text-sm font-black text-gray-900 dark:text-white">Catat KKN atas nama universitas</h2>
        <p class="<?= $petunjuk ?>">Untuk KKN yang sudah atau sedang berjalan tetapi belum tercatat, misalnya karena berlangsung sebelum aplikasi tersedia. KKN yang dicatat di sini langsung berstatus Diterima dan tampil juga di dashboard universitasnya.</p>
    </div>
    <label class="<?= $label ?>">Universitas
        <select name="universitas" required class="<?= $isian_kelas ?>">
            <option value="">Pilih akun universitas</option>
            <?php foreach ($universitas as $u): ?>
            <option value="<?= (int) $u->id ?>"<?= (int) ($isian['universitas'] ?? 0) === (int) $u->id ? ' selected' : '' ?>><?= html_escape($u->nama) ?> (<?= html_escape($u->email) ?>)</option>
            <?php endforeach; ?>
        </select>
        <?php if ( ! $universitas): ?><span class="<?= $petunjuk ?>">Belum ada akun universitas aktif. Buat dulu di tab Akun universitas.</span><?php endif; ?>
    </label>
    <div class="grid gap-3 sm:grid-cols-2">
        <label class="<?= $label ?>">Periode mulai
            <input type="date" name="periode_mulai" required value="<?= $v('periode_mulai') ?>" class="<?= $isian_kelas ?>">
        </label>
        <label class="<?= $label ?>">Periode selesai
            <input type="date" name="periode_selesai" required value="<?= $v('periode_selesai') ?>" class="<?= $isian_kelas ?>">
        </label>
    </div>
    <label class="<?= $label ?>">Keterangan
        <input name="keterangan" required maxlength="150" value="<?= $v('keterangan') ?>" placeholder="Contoh: KKN Kemitraan Desa Sukamaju 2026" class="<?= $isian_kelas ?>">
        <span class="<?= $petunjuk ?>">Tampil di daftar KKN dan sertifikat peserta.</span>
    </label>
    <label class="<?= $label ?>">Catatan admin (opsional)
        <textarea name="catatan" rows="2" maxlength="500" placeholder="Contoh: dicatat susulan, KKN berlangsung Juli sampai Agustus sebelum aplikasi tersedia." class="<?= $isian_kelas ?>"><?= $v('catatan') ?></textarea>
    </label>
    <div class="flex flex-wrap items-center gap-2">
        <button type="submit" class="tombol-utama"><i class="ph ph-floppy-disk"></i><span>Catat dan terima</span></button>
        <a href="<?= base_url('Admin_Kemitraan/sertifikat') ?>" class="tombol-kedua"><span>Batal</span></a>
    </div>
</form>
