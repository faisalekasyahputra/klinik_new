<?php
/**
 * Roster peserta satu KKN - permintaan user 22 Agt 2026. Sejak 7 Okt 2026 (kendali sertifikat di tangan
 * dinas) admin juga bisa mengunggah atau mengganti roster di sini (Admin_Kemitraan::unggah_peserta) dan
 * menetapkan tanggal sertifikat; universitas tetap bisa mengunggah dari dashboard-nya sendiri.
 * Nomor sertifikat diatur per KKN: awalan dari admin, dua digit terakhir urut peserta (migrasi 079; aturannya di
 * MY_Controller::nomor_sertifikat_kkn, dikirim controller sebagai $nomor). Tabel hanya pratinjau nomor dan tombol
 * Lihat sertifikat; edit nomor per peserta dihapus atas keputusan user 7 Okt 2026.
 */
$csrf = '<input type="hidden" name="' . $this->security->get_csrf_token_name() . '" value="' . $this->security->get_csrf_hash() . '">';
$lewat = ! empty($row->periode_selesai) && $row->periode_selesai < date('Y-m-d');
$awalan = (string) ($row->awalan_nomor_sertifikat ?? '');
?>
<?php $this->load->view('admin/components/judul_halaman', [
    'jh_judul' => 'Peserta KKN',
    'jh_deskripsi' => html_escape($row->instansi_asal) . ' &middot; ' . html_escape($row->divisi_atau_tema ?: '(tanpa keterangan)'),
    'jh_aksi' => '<a href="' . base_url('Admin_Kemitraan/sertifikat') . '" class="tombol-kedua"><span class="panah-sorot" aria-hidden="true"><i class="ph ph-caret-left"></i></span><span>Kembali</span></a>',
]); ?>

<?php /* Dirapikan 7 Okt 2026 atas permintaan user: dua kartu sama tinggi (tanpa celah), isian awalan dan tanggal
         satu baris dengan satu tombol, teks bantuan satu kalimat. */ ?>
<div class="grid grid-kartu items-stretch lg:grid-cols-2 mb-6" data-kendali-sertifikat>
    <form method="post" action="<?= base_url('Admin_Kemitraan/unggah_peserta/' . (int) $row->id) ?>" enctype="multipart/form-data" class="kartu-admin isi-kartu flex flex-col gap-3">
        <?= $csrf ?>
        <div>
            <h2 class="text-sm font-black text-gray-900 dark:text-white">Unggah daftar peserta</h2>
            <p class="mt-1 text-[11px] text-gray-500 dark:text-brand-muted">Excel (XLS/XLSX) berjudul kolom "NIM" dan "Nama". Unggah ulang mengganti daftar; peserta yang tetap ada mempertahankan nomornya.<?= ! empty($row->tanggal_sertifikat) ? ' Peserta yang dihapus tidak bisa lagi mencetak.' : '' ?></p>
        </div>
        <div class="mt-auto flex flex-wrap items-center gap-2">
            <div class="min-w-0 flex-1"><?php $this->load->view('admin/components/input_berkas', ['ib_name' => 'file_peserta', 'ib_accept' => '.xls,.xlsx', 'ib_required' => TRUE]); ?></div>
            <button type="submit" class="tombol-utama"><i class="ph ph-upload-simple"></i><span>Simpan</span></button>
        </div>
    </form>
    <div class="kartu-admin isi-kartu flex flex-col gap-3">
        <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1">
            <h2 class="text-sm font-black text-gray-900 dark:text-white">Sertifikat</h2>
            <span class="text-[11px] text-gray-500 dark:text-brand-muted">
                <?php if ($row->status !== 'Diterima'): ?><span class="font-bold text-amber-700 dark:text-amber-300">KKN <?= html_escape($row->status) ?>: terima dulu di tab Pendaftaran</span>
                <?php elseif ( ! empty($row->tanggal_sertifikat)): ?>Terbit <b><?= html_escape(tgl_id($row->tanggal_sertifikat)) ?></b>
                <?php else: ?>Belum terbit<?= $lewat ? '' : ', periode belum selesai' ?><?php endif; ?>
                <?= ! empty($row->sertifikat_diminta_at) ? ' &middot; <b>' . (int) $row->sertifikat_diminta_jumlah . 'x diminta</b>' : '' ?>
            </span>
        </div>
        <?php if ($row->status !== 'Diterima'): ?>
            <form method="post" action="<?= base_url('Admin_Kemitraan/awalan_nomor/' . (int) $row->id) ?>" class="mt-auto flex flex-wrap items-end gap-3" data-awalan-nomor>
                <?= $csrf ?>
                <label class="text-xs font-bold text-gray-600 dark:text-brand-muted">Awalan nomor
                        <span class="mt-1 flex items-center gap-1.5"><input name="awalan_nomor" maxlength="80" value="<?= html_escape($awalan) ?>" placeholder="600.2/69" class="w-36 rounded-lg border border-gray-200 bg-transparent px-3 py-1.5 text-sm font-normal text-gray-800 dark:border-white/10 dark:text-white"><span class="whitespace-nowrap tabular-nums font-normal text-gray-500 dark:text-brand-muted">.01, .02, ...</span></span>
                    </label>
                <button type="submit" class="tombol-kedua"><i class="ph ph-floppy-disk" aria-hidden="true"></i><span>Simpan</span></button>
            </form>
        <?php else: ?>
            <?php /* Awalan di depan tanggal, satu tombol (permintaan dinas 7 Okt 2026); disimpan lewat tanggal_sertifikat. */ ?>
            <form method="post" action="<?= base_url('Admin_Kemitraan/tanggal_sertifikat/' . (int) $row->id) ?>" class="mt-auto flex flex-wrap items-end gap-3" data-awalan-nomor>
                <?= $csrf ?><input type="hidden" name="kembali" value="peserta">
                <label class="text-xs font-bold text-gray-600 dark:text-brand-muted">Awalan nomor
                        <span class="mt-1 flex items-center gap-1.5"><input name="awalan_nomor" maxlength="80" value="<?= html_escape($awalan) ?>" placeholder="600.2/69" class="w-36 rounded-lg border border-gray-200 bg-transparent px-3 py-1.5 text-sm font-normal text-gray-800 dark:border-white/10 dark:text-white"><span class="whitespace-nowrap tabular-nums font-normal text-gray-500 dark:text-brand-muted">.01, .02, ...</span></span>
                    </label>
                <label class="text-xs font-bold text-gray-600 dark:text-brand-muted">Tanggal terbit
                    <input type="date" name="tanggal_sertifikat" value="<?= html_escape($row->tanggal_sertifikat ?: date('Y-m-d')) ?>" class="mt-1 block rounded-lg border border-gray-200 bg-transparent px-3 py-1.5 text-sm font-normal text-gray-800 dark:border-white/10 dark:text-white">
                </label>
                <button type="submit" class="tombol-utama"><i class="ph ph-seal-check" aria-hidden="true"></i><span><?= ! empty($row->tanggal_sertifikat) ? 'Simpan' : 'Terbitkan' ?></span></button>
            </form>
        <?php endif; ?>
        <p class="text-[11px] text-gray-500 dark:text-brand-muted">Nomor otomatis = awalan + urut peserta. Kosongkan awalan untuk bawaan 600.2/69.<?= $row->status === 'Diterima' ? ' Kosongkan tanggal untuk menarik sertifikat.' : '' ?></p>
    </div>
</div>

<div class="kartu-admin overflow-hidden">
    <?php $this->load->view('admin/components/kepala_tabel', ['kt_judul' => 'Daftar Peserta', 'kt_jumlah' => count($peserta), 'kt_keterangan' => '<span class="text-xs">Nomor mengikuti awalan di kartu Sertifikat; dua digit terakhir urut peserta.</span>']); ?>
    <div class="overflow-x-auto aksi-tetap">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3">NIM</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Nomor sertifikat</th>
                    <th class="px-4 py-3 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                <?php if (empty($peserta)): ?>
                <tr>
                    <td colspan="4" class="px-4 py-12 text-center text-gray-500 dark:text-brand-muted">
                        <div class="flex flex-col items-center justify-center">
                            <div class="w-16 h-16 mb-4 rounded-full bg-gray-100 dark:bg-white/5 flex items-center justify-center text-3xl text-gray-300 dark:text-white/20">
                                <i class="ph ph-users-three"></i>
                            </div>
                            <p>Daftar peserta belum diunggah. Unggah lewat formulir di atas, atau tunggu universitas mengunggahnya.</p>
                        </div>
                    </td>
                </tr>
                <?php else: foreach ($peserta as $p): ?>
                <tr id="peserta-<?= (int) $p->id ?>">
                    <td class="px-4 py-3 text-gray-900 dark:text-white"><?= html_escape($p->nim) ?></td>
                    <td class="px-4 py-3 text-gray-900 dark:text-white"><?= html_escape($p->nama) ?></td>
                    <td class="px-4 py-3 tabular-nums text-gray-900 dark:text-white" data-nomor-sertifikat="<?= (int) $p->id ?>"><?= html_escape($nomor[(int) $p->id]->nomor) ?></td>
                    <td class="px-4 py-2 text-right"><a href="<?= base_url('Admin_Kemitraan/pratinjau_sertifikat/' . (int) $p->id) ?>" data-file-view data-file-title="Sertifikat <?= html_escape($p->nama) ?> (<?= html_escape($nomor[(int) $p->id]->nomor) ?>)" target="_blank" rel="noopener" class="tombol-aksi"><i class="ph ph-certificate" aria-hidden="true"></i><span>Lihat sertifikat</span></a></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
