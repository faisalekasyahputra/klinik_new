<?php
/**
 * Roster peserta satu KKN - permintaan user 22 Agt 2026. Sejak 7 Okt 2026 (kendali sertifikat di tangan
 * dinas) admin juga bisa mengunggah atau mengganti roster di sini (Admin_Kemitraan::unggah_peserta) dan
 * menetapkan tanggal sertifikat; universitas tetap bisa mengunggah dari dashboard-nya sendiri.
 */
$csrf = '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">';
$lewat = ! empty($row->periode_selesai) && $row->periode_selesai < date('Y-m-d');
?>
<?php $this->load->view('admin/components/judul_halaman', [
    'jh_judul' => 'Peserta KKN',
    'jh_deskripsi' => html_escape($row->instansi_asal) . ' &middot; ' . html_escape($row->divisi_atau_tema ?: '(tanpa keterangan)'),
    'jh_aksi' => '<a href="' . base_url('Admin_Kemitraan/sertifikat') . '" class="tombol-kedua"><span class="panah-sorot" aria-hidden="true"><i class="ph ph-caret-left"></i></span><span>Kembali</span></a>',
]); ?>

<div class="grid grid-kartu items-start lg:grid-cols-2 mb-6" data-kendali-sertifikat>
    <form method="post" action="<?= base_url('Admin_Kemitraan/unggah_peserta/' . (int) $row->id) ?>" enctype="multipart/form-data" class="kartu-admin isi-kartu space-y-2">
        <?= $csrf ?>
        <h2 class="text-sm font-black text-gray-900 dark:text-white">Unggah daftar peserta</h2>
        <p class="text-[11px] text-gray-500 dark:text-brand-muted">Excel (XLS/XLSX) dengan judul kolom "NIM" dan "Nama" di baris pertama, format sama dengan universitas. Mengunggah ulang MENGGANTI seluruh daftar peserta.<?= ! empty($row->tanggal_sertifikat) ? ' Tanggal sertifikat sudah ditetapkan: peserta yang dihapus dari daftar tidak bisa lagi mencetak.' : '' ?></p>
        <?php $this->load->view('admin/components/input_berkas', ['ib_name' => 'file_peserta', 'ib_accept' => '.xls,.xlsx', 'ib_required' => TRUE]); ?>
        <button type="submit" class="tombol-utama"><i class="ph ph-upload-simple"></i><span>Simpan daftar peserta</span></button>
    </form>
    <div class="kartu-admin isi-kartu space-y-2">
        <h2 class="text-sm font-black text-gray-900 dark:text-white">Sertifikat</h2>
        <?php if ($row->status !== 'Diterima'): ?>
            <p class="text-xs text-amber-700 dark:text-amber-300">KKN berstatus <?= html_escape($row->status) ?>. Terima dulu di tab Pendaftaran sebelum menetapkan tanggal sertifikat.</p>
        <?php else: ?>
            <p class="text-[11px] text-gray-500 dark:text-brand-muted">
                <?= ! empty($row->tanggal_sertifikat) ? 'Tanggal terbit: <b>' . html_escape(tgl_id($row->tanggal_sertifikat)) . '</b>. Kosongkan lalu simpan untuk menarik sertifikat.' : 'Belum terbit. Peserta bisa mencetak sesudah tanggal ini ditetapkan' . ($lewat ? '.' : ' dan periode KKN selesai.') ?>
                <?= ! empty($row->sertifikat_diminta_at) ? '<br><b>' . (int) $row->sertifikat_diminta_jumlah . 'x diminta mahasiswa</b> sejak ' . html_escape(tgl_id($row->sertifikat_diminta_at, TRUE)) . '.' : '' ?>
            </p>
            <form method="post" action="<?= base_url('Admin_Kemitraan/tanggal_sertifikat/' . (int) $row->id) ?>" class="flex flex-wrap items-center gap-2">
                <?= $csrf ?><input type="hidden" name="kembali" value="peserta">
                <input type="date" name="tanggal_sertifikat" value="<?= html_escape($row->tanggal_sertifikat ?: date('Y-m-d')) ?>" aria-label="Tanggal sertifikat" class="rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-white/10 dark:text-white">
                <button type="submit" class="tombol-aksi"><i class="ph ph-seal-check"></i><span><?= ! empty($row->tanggal_sertifikat) ? 'Simpan tanggal' : 'Terbitkan' ?></span></button>
            </form>
        <?php endif; ?>
    </div>
</div>

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
                            <p>Daftar peserta belum diunggah. Unggah lewat formulir di atas, atau tunggu universitas mengunggahnya.</p>
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
