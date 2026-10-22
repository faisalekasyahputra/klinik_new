<?php defined('BASEPATH') OR exit('No direct script access allowed');
/* Permintaan NIK dari warga (Admin_Users::permintaan_nik, 6 Okt 2026): klaim NIK dan reset NIK.
   Keputusan dikirim ke Admin_Users/putuskan_klaim_nik dan putuskan_reset_nik. Tanpa NIK di layar. */
$pemohon = function (array $k) use ($available_roles) {
    $peran = $k['pemohon_peran'] ?? ''; ?>
    <div class="font-bold text-gray-900 dark:text-white"><?= html_escape(($k['pemohon_nama'] ?? '') !== '' ? $k['pemohon_nama'] : ('akun #' . (int) $k['pemohon_id'])) ?></div>
    <div class="text-xs text-gray-500 dark:text-brand-muted break-words"><?= html_escape($k['pemohon_email'] ?? '-') ?><?php if ( ! empty($k['pemohon_username'])): ?> &middot; @<?= html_escape($k['pemohon_username']) ?><?php endif; ?></div>
    <?php if ($peran !== ''): ?><span class="mt-1 inline-flex px-2 py-0.5 rounded-lg text-[11px] font-bold bg-blue-50 text-blue-700 dark:bg-brand-primary/10 dark:text-brand-primary"><?= html_escape($available_roles[$peran] ?? $peran) ?></span><?php endif; ?>
<?php };
$this->load->view('admin/components/judul_halaman', ['jh_deskripsi' => 'Permintaan reset NIK dan klaim NIK dari warga. Warga diberi tahu hasil keputusan lewat email.']); ?>

<?php if (empty($klaim_nik) && empty($reset_nik_minta) && empty($lolos_nama_awalan)): ?>
<div class="kartu-admin p-8 text-center" data-permintaan-nik-kosong>
    <i class="ph ph-identification-card text-3xl text-gray-300 dark:text-brand-muted/50"></i>
    <p class="mt-2 font-bold text-gray-900 dark:text-white">Tidak ada permintaan NIK yang menunggu</p>
    <p class="text-sm text-gray-500 dark:text-brand-muted">Permintaan baru dari warga akan muncul di sini dan ditandai di menu.</p>
</div>
<?php endif; ?>

<?php if ( ! empty($lolos_nama_awalan)): /* Cek susulan NIK yang lolos otomatis lewat nama SIMPERUM terpotong (Admin_Users::putuskan_lolos_nama_awalan, 23 Okt 2026). */ ?>
<div class="kartu-admin overflow-hidden mb-6" data-lolos-nama-awalan>
    <?php $this->load->view('admin/components/kepala_tabel', ['kt_judul' => 'Pemeriksaan Susulan NIK', 'kt_jumlah' => count($lolos_nama_awalan),
        'kt_keterangan' => 'NIK ini sudah terverifikasi otomatis: tanggal lahir cocok dan nama di SIMPERUM hanya awal nama akun (data sumber terpotong). Periksa apakah nama akun memang nama pemilik NIK. Sudah dicek: ikatan tetap. Cabut: NIK dilepas dari akun (aturan reset NIK berlaku) dan NIK itu tidak bisa lolos otomatis lagi.']); ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr><th class="px-4 py-3">Lolos pada</th><th class="px-4 py-3">Warga</th><th class="px-4 py-3">Pendataan</th><th class="px-4 py-3">Keputusan</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                <?php foreach ($lolos_nama_awalan as $k): $p = $lolos_nama_awalan_pendataan[(int) $k['warga_id']] ?? ['disetujui' => 0, 'diproses' => 0]; ?>
                <tr>
                    <td class="px-4 py-3 whitespace-nowrap"><?= html_escape($k['created_at']) ?></td>
                    <td class="px-4 py-3"><?php $pemohon(['pemohon_id' => $k['warga_id'], 'pemohon_nama' => $k['warga_nama'], 'pemohon_email' => $k['warga_email'], 'pemohon_peran' => 'warga']); ?></td>
                    <td class="px-4 py-3 text-xs"><?= (int) $p['disetujui'] ?> disetujui, <?= (int) $p['diproses'] ?> diproses</td>
                    <td class="px-4 py-3">
                        <form method="POST" action="<?= base_url('Admin_Users/putuskan_lolos_nama_awalan') ?>" class="flex flex-wrap items-center gap-2">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                            <input type="hidden" name="id" value="<?= (int) $k['id'] ?>">
                            <input type="text" name="alasan" maxlength="500" placeholder="Catatan (opsional)" class="rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-1.5 text-xs text-gray-800 dark:text-gray-200">
                            <button type="submit" name="keputusan" value="cek" class="tombol-aksi"><span>Sudah dicek</span></button>
                            <button type="submit" name="keputusan" value="cabut" class="tombol-aksi-bahaya"><span>Cabut</span></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ( ! empty($klaim_nik)): /* Permintaan klaim NIK (Admin_Users::putuskan_klaim_nik). Tanpa NIK: hanya akun dan hitungan. */ ?>
<div class="kartu-admin overflow-hidden mb-6" data-klaim-nik>
    <?php $this->load->view('admin/components/kepala_tabel', ['kt_judul' => 'Permintaan Klaim NIK', 'kt_jumlah' => count($klaim_nik),
        'kt_keterangan' => 'NIK terikat: akun lolos verifikasi nama dan tanggal lahir untuk NIK yang terikat belum terverifikasi ke akun lain; setujui memindahkan NIK (draft pemegang lama dilepas, tidak dihapus). Nama SIMPERUM terpotong: tanggal lahir cocok, nama di SIMPERUM hanya awal nama akun, tetapi NIK terikat ke akun lain atau verifikasinya pernah dicabut; setujui bila nama akun memang nama pemilik NIK, lalu pemohon mengulang Cek NIK.']); ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr><th class="px-4 py-3">Diajukan</th><th class="px-4 py-3">Jenis</th><th class="px-4 py-3">Pemohon</th><th class="px-4 py-3">Pemegang saat ini</th><th class="px-4 py-3">Pengajuan berjalan</th><th class="px-4 py-3">Keputusan</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                <?php foreach ($klaim_nik as $k): ?>
                <tr>
                    <td class="px-4 py-3 whitespace-nowrap"><?= html_escape($k['created_at']) ?></td>
                    <td class="px-4 py-3"><?= $k['jenis'] === 'nama_awalan' ? 'Nama SIMPERUM terpotong' : 'NIK terikat' ?></td>
                    <td class="px-4 py-3"><?php $pemohon($k); ?></td>
                    <td class="px-4 py-3"><?= html_escape(implode(', ', array_map(fn($id) => $email_pemegang[$id] ?? ('akun #' . $id), $k['pemegang'])) ?: '-') ?></td>
                    <td class="px-4 py-3"><?= (int) $k['pengajuan_berjalan'] ?></td>
                    <td class="px-4 py-3">
                        <form method="POST" action="<?= base_url('Admin_Users/putuskan_klaim_nik') ?>" class="flex flex-wrap items-center gap-2">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                            <input type="hidden" name="id" value="<?= (int) $k['id'] ?>">
                            <input type="text" name="alasan" maxlength="500" placeholder="Catatan (opsional)" class="rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-1.5 text-xs text-gray-800 dark:text-gray-200">
                            <button type="submit" name="keputusan" value="setuju" class="tombol-aksi"><span>Setujui</span></button>
                            <button type="submit" name="keputusan" value="tolak" class="tombol-aksi-bahaya"><span>Tolak</span></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if ( ! empty($reset_nik_minta)): /* Permintaan reset NIK dari warga (Admin_Users::putuskan_reset_nik, 6 Okt 2026). */ ?>
<div class="kartu-admin overflow-hidden mb-6" data-reset-nik-minta>
    <?php $this->load->view('admin/components/kepala_tabel', ['kt_judul' => 'Permintaan Reset NIK', 'kt_jumlah' => count($reset_nik_minta),
        'kt_keterangan' => 'Warga meminta NIK yang terkunci di akunnya dibuka agar bisa memasukkan NIK yang benar. Setujui membuka NIK (draf pendataan yang belum dikirim ikut dihapus); tolak mengirim catatan Anda ke warga.']); ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr><th class="px-4 py-3">Diajukan</th><th class="px-4 py-3">Pemohon</th><th class="px-4 py-3">Alasan</th><th class="px-4 py-3">Keputusan</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                <?php foreach ($reset_nik_minta as $k):
                    // Aturan 21 Okt 2026 (Admin_Users::lepas_nik): disetujui memblokir, masih diproses ikut dibatalkan.
                    $pd = $reset_nik_pendataan[(int) $k['pemohon_id']] ?? ['disetujui' => 0, 'diproses' => 0, 'tanpa_antrean' => 0];
                    $terblokir = $pd['disetujui'] > 0 || $pd['tanpa_antrean'] > 0; ?>
                <tr>
                    <td class="px-4 py-3 whitespace-nowrap"><?= html_escape(tgl_id($k['created_at'], TRUE, TRUE)) ?></td>
                    <td class="px-4 py-3"><?php $pemohon($k); ?>
                        <?php if ($terblokir): ?><span class="mt-1 block text-xs font-semibold text-amber-700 dark:text-amber-300" data-reset-nik-terblokir>Warga ini sudah punya pendataan yang disetujui atas NIK ini, jadi NIK tidak bisa dibuka lewat sistem. Tolak permintaan ini dan arahkan warga ke Dinas.</span>
                        <?php elseif ($pd['diproses'] > 0): ?><span class="mt-1 block text-xs font-semibold text-amber-700 dark:text-amber-300" data-reset-nik-batalkan>Menyetujui akan membatalkan <?= (int) $pd['diproses'] ?> pendataan yang masih diproses admin kabupaten/kota. Warga lalu mengirim pendataan baru dengan NIK yang benar.</span><?php endif; ?>
                    </td>
                    <td class="px-4 py-3 whitespace-normal text-xs text-gray-600 dark:text-brand-muted"><?= html_escape($k['alasan']) ?></td>
                    <td class="px-4 py-3">
                        <form method="POST" action="<?= base_url('Admin_Users/putuskan_reset_nik') ?>" class="flex flex-wrap items-center gap-2">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                            <input type="hidden" name="id" value="<?= (int) $k['id'] ?>">
                            <input type="text" name="alasan" maxlength="500" placeholder="Catatan untuk warga (opsional)" class="rounded-xl border border-gray-200 dark:border-white/10 bg-gray-50 dark:bg-white/5 px-3 py-1.5 text-xs text-gray-800 dark:text-gray-200">
                            <?php if ( ! $terblokir): ?><button type="submit" name="keputusan" value="setuju" class="tombol-aksi" data-reset-nik-setuju><span>Setujui</span></button><?php endif; ?>
                            <button type="submit" name="keputusan" value="tolak" class="tombol-aksi tombol-aksi-bahaya"><span>Tolak</span></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
