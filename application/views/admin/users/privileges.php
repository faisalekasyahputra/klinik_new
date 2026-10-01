<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$e = static fn($value) => html_escape((string) $value);
// Label menu induk, supaya dua "Rekap" (Perumahan dan Kawasan) bisa dibedakan tanpa menampilkan path controller.
$label_modul = [];
foreach ($module_groups as $modules) { foreach ($modules as $key => $module) { $label_modul[$key] = $module['label']; } }
?>
<div class="tumpuk-bagian">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.16em] text-brand-muted">Akses Staf</p>
            <h1 class="mt-1 text-2xl font-black text-gray-900 dark:text-white">Hak Modul <?= $e($user->name ?: $user->email) ?></h1>
            <p class="mt-2 max-w-2xl text-sm text-gray-500 dark:text-brand-muted">Pilih modul yang boleh dibuka akun ini. Pembatasan berlaku pada menu dan akses URL langsung.</p>
        </div>
        <a href="<?= base_url('Admin_Users') ?>" class="tombol-kedua"><i class="ph ph-arrow-left"></i><span>Kembali</span></a>
    </div>
    <section class="kartu-admin isi-kartu">
        <div class="mb-5 grid gap-3 sm:grid-cols-3">
            <div><span class="block text-xs text-gray-500 dark:text-brand-muted">Email</span><strong class="text-sm text-gray-900 dark:text-white"><?= $e($user->email) ?></strong></div>
            <div><span class="block text-xs text-gray-500 dark:text-brand-muted">Peran</span><strong class="text-sm text-gray-900 dark:text-white"><?= $e($role_label) ?></strong></div>
            <div><span class="block text-xs text-gray-500 dark:text-brand-muted">Cakupan</span><strong class="text-sm text-gray-900 dark:text-white"><?= $e($scope_label) ?></strong></div>
        </div>
        <form method="post" action="<?= base_url('Admin_Privileges/save') ?>">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
            <input type="hidden" name="id" value="<?= (int) $user->id ?>">
            <div class="space-y-5">
                <?php foreach ($module_groups as $group => $modules): ?>
                <fieldset>
                    <legend class="mb-2 text-sm font-black text-gray-900 dark:text-white"><?= $e($group) ?></legend>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($modules as $key => $module): ?>
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-3 hover:border-brand-primary dark:border-white/10">
                            <input type="checkbox" name="modules[]" value="<?= $e($key) ?>" <?= !empty($selected[$key]) ? 'checked' : '' ?> class="mt-1 h-4 w-4 rounded border-gray-300 text-brand-primary focus:ring-brand-primary">
                            <span><strong class="block text-sm text-gray-900 dark:text-white"><?= $e($module['label']) ?></strong><?php if (!empty($module['parent']) && isset($label_modul[$module['parent']])): ?><small class="text-xs text-gray-500 dark:text-brand-muted">Bagian dari <?= $e($label_modul[$module['parent']]) ?></small><?php endif; ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <?php endforeach; ?>
            </div>
            <div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-800 dark:border-brand-primary/20 dark:bg-brand-primary/5 dark:text-brand-primary">Profil Saya selalu tersedia dan tidak dapat dinonaktifkan. Bila semua pilihan dikosongkan, akun tetap dapat masuk tetapi hanya bisa membuka profil.</div>
            <button type="submit" class="tombol-utama mt-5"><i class="ph ph-floppy-disk"></i><span>Simpan hak modul</span></button>
        </form>
    </section>
</div>