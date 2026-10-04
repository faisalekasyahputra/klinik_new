<?php
/**
 * Roster peserta satu KKN, baca saja - permintaan user 22 Agt 2026.
 * Sunting roster hanya lewat dashboard universitas sendiri
 * (KemitraanPortal::kkn_upload_peserta()), bukan dari sini.
 */
?>
<?php $this->load->view('admin/components/judul_halaman', [
    'jh_judul' => 'Peserta KKN',
    'jh_deskripsi' => html_escape($row->instansi_asal) . ' &middot; ' . html_escape($row->divisi_atau_tema ?: '(tanpa keterangan)'),
    'jh_aksi' => '<a href="' . base_url('Admin_Kemitraan') . '" class="tombol-kedua"><span class="panah-sorot" aria-hidden="true"><i class="ph ph-caret-left"></i></span><span>Kembali</span></a>',
]); ?>

<div class="kartu-admin overflow-hidden">
    <?php $this->load->view('admin/components/kepala_tabel', ['kt_judul' => 'Daftar Peserta', 'kt_jumlah' => count($peserta), 'kt_keterangan' => '']); ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3">NIM</th>
                    <th class="px-4 py-3">Nama</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                <?php if (empty($peserta)): ?>
                <tr>
                    <td colspan="2" class="px-4 py-12 text-center text-gray-500 dark:text-brand-muted">
                        <div class="flex flex-col items-center justify-center">
                            <div class="w-16 h-16 mb-4 rounded-full bg-gray-100 dark:bg-white/5 flex items-center justify-center text-3xl text-gray-300 dark:text-white/20">
                                <i class="ph ph-users-three"></i>
                            </div>
                            <p>Universitas belum mengunggah daftar peserta.</p>
                        </div>
                    </td>
                </tr>
                <?php else: foreach ($peserta as $p): ?>
                <tr>
                    <td class="px-4 py-3 text-gray-900 dark:text-white"><?= html_escape($p->nim) ?></td>
                    <td class="px-4 py-3 text-gray-900 dark:text-white"><?= html_escape($p->nama) ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
