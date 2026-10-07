<?php
/**
 * Roster peserta satu KKN - permintaan user 22 Agt 2026. Sejak 7 Okt 2026 (kendali sertifikat di tangan
 * dinas) admin juga bisa mengunggah atau mengganti roster di sini (Admin_Kemitraan::unggah_peserta) dan
 * menetapkan tanggal sertifikat; universitas tetap bisa mengunggah dari dashboard-nya sendiri.
 * Nomor sertifikat per peserta bisa diubah admin (permintaan dinas 7 Okt 2026, migrasi 078); kosong = otomatis.
 * Awalan nomor diatur per KKN, dua digit terakhir urut peserta (migrasi 079); aturannya di
 * MY_Controller::nomor_sertifikat_kkn, dikirim controller sebagai $nomor.
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
        <form method="post" action="<?= base_url('Admin_Kemitraan/awalan_nomor/' . (int) $row->id) ?>" class="space-y-1 border-t border-gray-200 pt-3 dark:border-white/10" data-awalan-nomor>
            <?= $csrf ?>
            <label for="awalan-nomor" class="block text-xs font-bold text-gray-600 dark:text-brand-muted">Awalan nomor sertifikat</label>
            <div class="flex flex-wrap items-center gap-2">
                <input id="awalan-nomor" name="awalan_nomor" maxlength="80" value="<?= html_escape($awalan) ?>" placeholder="600.2/69" class="w-48 rounded-lg border border-gray-200 bg-transparent px-3 py-2 text-sm text-gray-800 dark:border-white/10 dark:text-white">
                <span class="text-sm tabular-nums text-gray-500 dark:text-brand-muted">.01, .02, ...</span>
                <button type="submit" class="tombol-aksi"><i class="ph ph-floppy-disk" aria-hidden="true"></i><span>Simpan awalan</span></button>
            </div>
            <p class="text-[11px] text-gray-500 dark:text-brand-muted">Berlaku untuk semua peserta KKN ini yang bernomor otomatis; dua digit terakhir adalah nomor urut peserta dan tidak berubah walau daftar diunggah ulang. Kosongkan untuk kembali ke bawaan 600.2/69. + nomor urut database.</p>
        </form>
    </div>
</div>

<div class="kartu-admin overflow-hidden">
    <?php $this->load->view('admin/components/kepala_tabel', ['kt_judul' => 'Daftar Peserta', 'kt_jumlah' => count($peserta), 'kt_keterangan' => 'Otomatis: ' . ($awalan !== '' ? html_escape($awalan) . '.01, .02, ... menurut urut peserta' : '600.2/69. + nomor urut database') . '. Manual: lewat tombol Ubah.']); ?>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-3">NIM</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Nomor sertifikat</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                <?php if (empty($peserta)): ?>
                <tr>
                    <td colspan="3" class="px-4 py-12 text-center text-gray-500 dark:text-brand-muted">
                        <div class="flex flex-col items-center justify-center">
                            <div class="w-16 h-16 mb-4 rounded-full bg-gray-100 dark:bg-white/5 flex items-center justify-center text-3xl text-gray-300 dark:text-white/20">
                                <i class="ph ph-users-three"></i>
                            </div>
                            <p>Daftar peserta belum diunggah. Unggah lewat formulir di atas, atau tunggu universitas mengunggahnya.</p>
                        </div>
                    </td>
                </tr>
                <?php else: foreach ($peserta as $p): ?>
                <?php $n = $nomor[(int) $p->id]; $manual = (string) $n->manual !== ''; $otomatis = html_escape($n->otomatis); ?>
                <tr id="peserta-<?= (int) $p->id ?>">
                    <td class="px-4 py-3 text-gray-900 dark:text-white"><?= html_escape($p->nim) ?></td>
                    <td class="px-4 py-3 text-gray-900 dark:text-white"><?= html_escape($p->nama) ?></td>
                    <td class="px-4 py-2" x-data="{ ubah: false }" @keydown.escape="ubah = false">
                        <?php /* Tampilan baca dulu; isian baru muncul setelah Ubah (cek visual 7 Okt 2026: sepuluh isian
                                 dan tombol Simpan sekaligus membuat nomor manual dan otomatis sulit dibedakan). */ ?>
                        <div x-show="! ubah" class="flex items-center gap-3" data-nomor-tampil="<?= $manual ? 'manual' : 'otomatis' ?>">
                            <?php if ($manual): ?>
                            <span class="inline-flex min-w-[5rem] justify-center rounded-md bg-blue-50 px-1.5 py-0.5 text-[11px] font-bold uppercase text-blue-700 dark:bg-brand-primary/10 dark:text-brand-primary" title="Ditetapkan admin; nomor otomatisnya <?= $otomatis ?>">Manual</span>
                            <?php else: ?>
                            <span class="inline-flex min-w-[5rem] justify-center rounded-md bg-gray-100 px-1.5 py-0.5 text-[11px] font-bold uppercase text-gray-500 dark:bg-white/5 dark:text-brand-muted">Otomatis</span>
                            <?php endif; ?>
                            <span class="tabular-nums <?= $manual ? 'font-bold text-gray-900 dark:text-white' : 'text-gray-500 dark:text-brand-muted' ?>"><?= $manual ? html_escape($n->manual) : $otomatis ?></span>
                            <button type="button" @click="ubah = true; $nextTick(() => $refs.isian.focus())" class="tombol-aksi ml-auto"><i class="ph ph-pencil-simple" aria-hidden="true"></i><span>Ubah</span></button>
                        </div>
                        <form x-show="ubah" x-cloak method="post" action="<?= base_url('Admin_Kemitraan/nomor_sertifikat/' . (int) $p->id) ?>" class="flex flex-wrap items-center gap-2" data-nomor-sertifikat="<?= (int) $p->id ?>">
                            <?= $csrf ?>
                            <input x-ref="isian" name="nomor_sertifikat" maxlength="100" value="<?= html_escape((string) $n->manual) ?>" placeholder="<?= $otomatis ?>" aria-label="Nomor sertifikat <?= html_escape($p->nama) ?>" class="w-56 rounded-lg border border-gray-200 bg-transparent px-3 py-1.5 text-sm text-gray-800 dark:border-white/10 dark:text-white">
                            <button type="submit" class="tombol-aksi"><i class="ph ph-floppy-disk" aria-hidden="true"></i><span>Simpan</span></button>
                            <?php if ($manual): ?>
                            <button type="submit" @click="$refs.isian.value = ''" class="tombol-aksi" title="Kembali ke <?= $otomatis ?>"><i class="ph ph-arrow-counter-clockwise" aria-hidden="true"></i><span>Pakai otomatis</span></button>
                            <?php endif; ?>
                            <button type="button" @click="ubah = false" class="tombol-aksi"><span>Batal</span></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
