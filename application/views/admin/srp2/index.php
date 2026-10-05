<?php
$this->load->helper('admin_table');
$this->load->helper('srp2');

/* Daftar ringkas (permintaan pemilik produk 2 Okt 2026): satu baris per perusahaan,
   sunting di halaman Ubah. Dulu setiap baris formulir sunting penuh selebar 980px.

   `$label_status` menerjemahkan ENUM ke bahasa dinas; nilai mentah tetap di DB.
   Berlaku/Tidak berlaku DITURUNKAN dari tanggal (srp2_sertifikat_berlaku, rumus yang
   sama dengan direktori publik), tidak pernah disimpan. */
$label_status = [
    'belum_mendaftar' => 'Belum mendaftar',
    'mendaftar'       => 'Mendaftar',
    'masih_proses'    => 'Masih proses',
    'bersertifikat'   => 'Bersertifikat',
];
?>
<div class="tumpuk-bagian">
<?php $this->load->view('admin/components/judul_halaman', [
    'jh_aksi'      => '<a href="' . base_url('Admin_Srp2/tambah') . '" class="tombol-utama shrink-0"><i class="ph ph-plus"></i><span>Tambah pengembang</span></a>',
    'jh_deskripsi' => 'Kelola profil, penayangan publik, dan akun pengembang bersertifikat. Pengajuan yang diterima masuk otomatis; entri manual untuk data historis.',
]); ?>
<div data-tabel-admin style="counter-reset: baris-admin <?= (int) (($table ?? [])['offset'] ?? 0) ?>" class="kartu-admin overflow-hidden">
<?= $this->load->view('admin/components/table_toolbar', ['table' => $table, 'base_url' => $base_url, 'placeholder' => 'Cari nama perusahaan atau alamat...'], TRUE) ?>
<div class="overflow-x-auto aksi-tetap">
<table class="w-full min-w-[980px] text-left text-sm" data-direktori-ringkas>
    <thead class="bg-gray-50 dark:bg-black/20 text-xs uppercase text-gray-500">
        <tr>
            <th class="px-4 py-3"><?= admin_sort_header('Perusahaan', 'nama_perusahaan', $table, $base_url) ?></th>
            <th class="px-3 py-3">Wilayah</th>
            <th class="px-3 py-3">Asosiasi</th>
            <th class="px-3 py-3"><?= admin_sort_header('Sertifikasi', 'sertifikat_berakhir', $table, $base_url) ?></th>
            <th class="px-3 py-3"><?= admin_sort_header('Publik', 'status_aktif', $table, $base_url) ?></th>
            <th class="px-3 py-3">Akun</th>
            <th class="px-4 py-3 text-right">Aksi</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
    <?php foreach ($rows as $row): $berlaku = srp2_sertifikat_berlaku($row); $akhir = (string) ($row->sertifikat_berakhir ?? ''); ?>
        <tr data-direktori-id="<?= (int) $row->id ?>">
            <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                    <?= srp2_logo($row, 40) ?>
                    <div class="min-w-0">
                        <p class="font-bold text-gray-900 dark:text-white"><?= html_escape($row->nama_perusahaan) ?></p>
                        <p class="max-w-xs truncate text-xs text-gray-500 dark:text-brand-muted" title="<?= html_escape($row->alamat_kantor ?? '') ?>"><?= html_escape(trim((string) $row->alamat_kantor) !== '' ? $row->alamat_kantor : 'Alamat belum tercatat') ?></p>
                    </div>
                </div>
            </td>
            <td class="px-3 py-3 text-xs text-gray-700 dark:text-gray-300"><?= html_escape($row->wilayah ?: 'Belum tercatat') ?></td>
            <td class="px-3 py-3 text-xs text-gray-700 dark:text-gray-300"><?= html_escape(srp2_label_asosiasi($row->asosiasi ?? '', 'Belum tercatat')) ?></td>
            <td class="px-3 py-3 text-xs">
                <span class="block font-bold text-gray-700 dark:text-gray-300"><?= html_escape($label_status[$row->status_sertifikasi] ?? $row->status_sertifikasi) ?></span>
                <span class="mt-1 flex flex-wrap items-center gap-1">
                    <?php $this->load->view('admin/components/status_badge', ['label' => srp2_keadaan_sertifikat($row)[1], 'kelas' => ['berlaku' => 'ok', 'belum_dicatat' => 'pending'][srp2_keadaan_sertifikat($row)[0]] ?? 'reject']); ?>
                    <span class="text-gray-500 dark:text-brand-muted"><?= $akhir !== '' ? 's.d. ' . html_escape(tgl_id($akhir, TRUE)) : 'tanggal belum tercatat' ?></span>
                </span>
            </td>
            <td class="px-3 py-3 text-xs"><?php $this->load->view('admin/components/status_badge', ['label' => $row->status_aktif ? 'Tampil' : 'Disembunyikan', 'kelas' => $row->status_aktif ? 'ok' : 'pending']); ?></td>
            <td class="px-3 py-3 text-xs">
                <?php if ($row->user_id): ?>
                    <span class="block font-bold text-green-700 dark:text-green-400">Tertaut</span>
                    <span class="block max-w-xs truncate text-gray-500 dark:text-brand-muted"><?= html_escape($row->akun_email ?? '') ?></span>
                <?php else: ?>
                    <span class="text-gray-400 dark:text-brand-muted">Belum ada akun</span>
                <?php endif; ?>
            </td>
            <td class="px-4 py-3 text-right"><a href="<?= base_url('Admin_Srp2/ubah/' . (int) $row->id) ?>" class="tombol-aksi"><i class="ph ph-pencil-simple" aria-hidden="true"></i><span>Ubah</span></a></td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($rows)): ?>
        <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-brand-muted">Tidak ada pengembang yang cocok dengan pencarian.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
<?= $this->load->view('admin/components/pagination', ['pager' => $pager, 'base_url' => $base_url], TRUE) ?>
</div></div>
