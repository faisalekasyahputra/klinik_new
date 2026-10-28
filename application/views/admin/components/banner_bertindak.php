<?php
// Superadmin sedang mengisi atas nama kabupaten: harus selalu terlihat, supaya tidak keliru wilayah.
// Dimuat di shell penuh (admin/index.php) DAN di balasan parsial loader (render_user_dashboard),
// karena wizard Rekam Data berpindah langkah lewat muat parsial yang mengganti isi <main>.
if (empty($bertindak_nama)) { return; }
?>
<form method="post" action="<?= base_url('Admin_Rekam_Data/selesai') ?>" data-banner-bertindak
      class="mb-4 flex flex-wrap items-center gap-3 rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
    <input type="hidden" name="<?= html_escape($this->security->get_csrf_token_name()) ?>" value="<?= html_escape($this->security->get_csrf_hash()) ?>">
    <i class="ph ph-user-switch" aria-hidden="true"></i>
    <span>Anda mengisi atas nama <b><?= html_escape($bertindak_nama) ?></b> sebagai super admin.</span>
    <button class="tombol-kedua ml-auto">Selesai</button>
</form>
