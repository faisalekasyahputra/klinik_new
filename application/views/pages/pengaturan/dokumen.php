<?php
$locked = in_array($srp2['status'], ['Pending', 'Diterima'], TRUE);
$count = count(array_intersect(array_keys($files), array_keys($dokumen)));
$card = 'rounded-2xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-brand-card';
$button = 'inline-flex items-center gap-2 rounded-lg bg-brand-primary px-4 py-2 text-sm font-bold text-gray-900';
?>
<section id="srp2-dashboard-documents" class="space-y-5 text-gray-900 dark:text-white">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h1 class="text-2xl font-black">Dokumen SRP2</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-brand-muted"><?= $count ?> / <?= count($dokumen) ?> dokumen tersimpan · Status: <?= html_escape($srp2['status']) ?></p>
        </div>
        <a href="<?= base_url('akun/profil') ?>" class="text-sm font-bold underline">Lengkapi Data Perusahaan</a>
    </div>
    <?php if (!empty($srp2['catatan_admin'])): ?>
        <div class="<?= $card ?>"><h2 class="font-bold">Catatan Admin</h2><p class="mt-2 text-sm"><?= nl2br(html_escape($srp2['catatan_admin'])) ?></p></div>
    <?php endif; ?>
    <div class="<?= $card ?>">
        <p class="text-sm"><?= $locked ? 'Pengajuan sedang ditinjau atau sudah diterima. Dokumen dapat dilihat; perubahan menunggu pengajuan dibuka kembali oleh admin.' : 'Unggah atau ganti berkas satu per satu. Berkas yang tersimpan juga langsung tersedia di wizard pendaftaran.' ?></p>
        <p class="mt-2 text-sm text-gray-500 dark:text-brand-muted">PDF, JPG, atau PNG, maksimal 2 MB per dokumen.</p>
        <a href="https://s.id/lampiran_SRPP" target="_blank" rel="noopener noreferrer" class="mt-3 inline-block text-sm font-bold underline">Unduh Template Dokumen</a>
    </div>
    <div class="grid gap-4 lg:grid-cols-2">
    <?php foreach ($dokumen as $key => $label): ?>
        <div class="<?= $card ?>">
            <h2 class="text-sm font-bold"><?= html_escape($label) ?></h2>
            <?php if (!empty($keterangan[$key])): ?><p class="mt-2 text-sm text-gray-500 dark:text-brand-muted"><?= html_escape($keterangan[$key]) ?></p><?php endif; ?>
            <?php if (isset($files[$key])): ?>
                <p class="mt-3 break-all text-sm">Tersimpan: <?= html_escape($files[$key]) ?></p>
                <a href="<?= base_url('Pengembang/lihat_dokumen_saya/' . (int) $srp2['registration_id'] . '/' . $key) ?>" target="_blank" rel="noopener" class="mt-2 inline-block text-sm font-bold underline">Lihat Berkas</a>
            <?php else: ?><p class="mt-3 text-sm text-gray-500 dark:text-brand-muted">Belum diunggah</p><?php endif; ?>
            <?php if (!$locked): ?>
                <form action="<?= base_url('Pengembang/simpan_dokumen/' . (int) $srp2['registration_id']) ?>" method="post" enctype="multipart/form-data" class="mt-4 space-y-3">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                    <input type="hidden" name="return_to" value="dashboard">
                    <label for="upload_<?= $key ?>" class="block text-sm"><?= isset($files[$key]) ? 'Pilih Berkas Pengganti' : 'Pilih Berkas' ?></label>
                    <input id="upload_<?= $key ?>" name="<?= $key ?>" type="file" accept=".pdf,.jpg,.jpeg,.png" required class="block w-full text-sm">
                    <button type="submit" class="<?= $button ?>"><?= isset($files[$key]) ? 'Ganti Berkas' : 'Simpan Berkas' ?></button>
                </form>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-4">
        <a href="<?= base_url('akun') ?>" class="text-sm font-bold underline">Kembali ke Status Pengajuan</a>
        <?php if (!$locked): ?>
            <form action="<?= base_url('Pengembang/kirim_pengajuan/' . (int) $srp2['registration_id']) ?>" method="post">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>">
                <input type="hidden" name="return_to" value="dashboard">
                <button type="submit" <?= $count < count($dokumen) ? 'disabled' : '' ?> class="<?= $button ?> disabled:opacity-50">Kirim Pengajuan</button>
            </form>
        <?php endif; ?>
    </div>
</section>
