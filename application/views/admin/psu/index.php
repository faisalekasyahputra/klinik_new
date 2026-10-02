<?php
$this->load->helper('admin_table');

/* Label status - dari psu_label_status(), SATU sumber dgn halaman
   publik Psu::index() supaya kalimatnya tidak menyimpang antar keduanya. */
$warna_status = [
    'belum_diserahkan'  => 'text-gray-400',
    'proses_verifikasi' => 'text-amber-600',
    'sudah_diserahkan'  => 'text-emerald-600',
];
?>
<div class="tumpuk-bagian">
    <?php $this->load->view('admin/components/judul_halaman', ['jh_deskripsi' => 'Prasarana, Sarana, dan Utilitas perumahan. Data yang ditandai "Tampilkan di publik"
                muncul di halaman PSU situs publik.']); ?>
    <?php // Halaman publiknya /psu (Psu::index()); kartu PSU di beranda sudah tidak lagi "Segera Hadir". ?>


    <div class="kartu-admin isi-kartu">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="max-w-2xl">
                <h2 class="text-sm font-black text-gray-900 dark:text-white">Impor data PSU dari Excel</h2>
                <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-brand-muted">
                    Unduh templat, isi lembar <span class="font-semibold">Data PSU</span> mulai baris 4, lalu unggah kembali.
                    Seluruh baris divalidasi sebelum disimpan; bila ada kesalahan, tidak ada data yang masuk.
                </p>
            </div>
            <a href="<?= base_url('Admin_Psu/template_excel') ?>" class="tombol-kedua"><i class="ph ph-download-simple"></i><span>Unduh templat Excel</span></a>
        </div>
        <form action="<?= base_url('Admin_Psu/import_excel') ?>" method="post" enctype="multipart/form-data"
              class="mt-4 flex flex-wrap items-end gap-3">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
            <div class="min-w-[260px] flex-1 text-xs text-gray-500 dark:text-brand-muted">Berkas Excel (.xlsx atau .xls, maksimal 5 MB)
                <div class="mt-1"><?php $this->load->view('admin/components/input_berkas', ['ib_name' => 'file_excel', 'ib_accept' => '.xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel', 'ib_required' => TRUE, 'ib_attr' => '']); ?></div>
            </div>
            <button type="submit" class="tombol-utama"><i class="ph ph-upload-simple"></i><span>Impor data</span></button>
        </form>
        <p class="mt-3 text-[11px] text-gray-400 dark:text-brand-muted">
            Maksimal 1.000 baris. Formula ditolak. Data dengan nama perumahan dan kabupaten/kota yang sama akan dilewati sebagai duplikat.
        </p>
    </div>

    <div class="kartu-admin isi-kartu">
        <h2 class="mb-3 text-sm font-black text-gray-900 dark:text-white">Tambah data PSU</h2>
        <form action="<?= base_url('Admin_Psu/simpan') ?>" method="post" class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
            <label class="text-xs text-gray-500 dark:text-brand-muted lg:col-span-2">Nama perumahan
                <input type="text" name="nama_perumahan" maxlength="180" required placeholder="mis. Perumahan Griya Asri"
                       class="mt-1 w-full rounded-lg border border-gray-200 dark:border-white/10 bg-transparent px-3 py-2 text-sm">
            </label>
            <label class="text-xs text-gray-500 dark:text-brand-muted">Kabupaten/Kota
                <select name="kabupaten_id" class="mt-1 w-full rounded-lg border border-gray-200 dark:border-white/10 bg-transparent px-3 py-2 text-sm">
                    <option value="0">- belum tercatat -</option>
                    <?php foreach ($kabupaten as $kb): ?>
                    <option value="<?= (int) $kb->id ?>"><?= html_escape($kb->nama) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="text-xs text-gray-500 dark:text-brand-muted lg:col-span-2">Nama pengembang
                <input type="text" name="nama_pengembang" maxlength="180" required placeholder="mis. PT Contoh Sejahtera"
                       list="daftar-pengembang-srp2"
                       class="mt-1 w-full rounded-lg border border-gray-200 dark:border-white/10 bg-transparent px-3 py-2 text-sm">
                <datalist id="daftar-pengembang-srp2">
                    <?php foreach ($pengembang as $p): ?>
                    <option value="<?= html_escape($p->nama_perusahaan) ?>">
                    <?php endforeach; ?>
                </datalist>
            </label>
            <label class="text-xs text-gray-500 dark:text-brand-muted">Pranala Direktori SRP2 (opsional)
                <select name="pengembang_id" class="mt-1 w-full rounded-lg border border-gray-200 dark:border-white/10 bg-transparent px-3 py-2 text-sm">
                    <option value="0">- tidak terhubung -</option>
                    <?php foreach ($pengembang as $p): ?>
                    <option value="<?= (int) $p->id ?>"><?= html_escape($p->nama_perusahaan) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="text-xs text-gray-500 dark:text-brand-muted">Asosiasi
                <select name="asosiasi" class="mt-1 w-full rounded-lg border border-gray-200 dark:border-white/10 bg-transparent px-3 py-2 text-sm">
                    <option value="">- belum tercatat -</option>
                    <?php foreach ($asosiasi as $ka => $va): ?>
                    <option value="<?= $ka ?>"><?= $va ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="text-xs text-gray-500 dark:text-brand-muted">Status serah terima
                <select name="status_serah_terima" class="mt-1 w-full rounded-lg border border-gray-200 dark:border-white/10 bg-transparent px-3 py-2 text-sm">
                    <?php foreach (Admin_Psu::STATUS_SERAH_TERIMA as $st): ?>
                    <option value="<?= $st ?>"><?= psu_label_status($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="text-xs text-gray-500 dark:text-brand-muted">Tanggal serah terima
                <input type="date" name="tanggal_serah_terima" class="mt-1 w-full rounded-lg border border-gray-200 dark:border-white/10 bg-transparent px-3 py-2 text-sm">
            </label>
            <label class="text-xs text-gray-500 dark:text-brand-muted lg:col-span-3">Keterangan (opsional)
                <input type="text" name="keterangan" maxlength="255" placeholder="mis. Menunggu berita acara"
                       class="mt-1 w-full rounded-lg border border-gray-200 dark:border-white/10 bg-transparent px-3 py-2 text-sm">
            </label>
            <div class="flex items-center justify-between gap-3 lg:col-span-3">
                <label class="flex items-center gap-2 text-xs text-gray-500 dark:text-brand-muted">
                    <input type="checkbox" name="status_aktif" value="1" checked> Tampilkan di publik
                </label>
                <button type="submit" class="tombol-utama"><i class="ph ph-plus"></i><span>Tambah</span></button>
            </div>
        </form>
    </div>

    <div data-tabel-admin style="counter-reset: baris-admin <?= (int) (($table ?? [])['offset'] ?? 0) ?>" class="kartu-admin overflow-hidden">
        <?= $this->load->view('admin/components/table_toolbar', ['table' => $table, 'base_url' => $base_url, 'placeholder' => 'Cari nama perumahan atau pengembang...'], TRUE) ?>
        <div class="overflow-x-auto aksi-tetap">
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="bg-gray-50 dark:bg-black/20 text-xs uppercase text-gray-500">
                    <tr>
                        <th class="px-4 py-3"><?= admin_sort_header('Nama Perumahan', 'nama_perumahan', $table, $base_url) ?></th>
                        <th class="px-3 py-3"><?= admin_sort_header('Pengembang', 'nama_pengembang', $table, $base_url) ?></th>
                        <th class="px-3 py-3">Asosiasi</th>
                        <th class="px-3 py-3">Kabupaten/Kota</th>
                        <th class="px-3 py-3"><?= admin_sort_header('Status', 'status_serah_terima', $table, $base_url) ?></th>
                        <th class="px-3 py-3">Aktif</th>
                        <th class="px-4 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                <?php foreach ($rows as $row): $fid = 'psu-' . (int) $row->id; ?>
                    <tr>
                        <form id="<?= $fid ?>" action="<?= base_url('Admin_Psu/simpan') ?>" method="post">
                            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                            <input type="hidden" name="id" value="<?= (int) $row->id ?>">
                        </form>
                        <td class="px-4 py-3">
                            <input form="<?= $fid ?>" name="nama_perumahan" maxlength="180" required
                                   value="<?= html_escape($row->nama_perumahan) ?>"
                                   class="w-48 rounded-lg border border-gray-200 dark:border-white/10 bg-transparent px-2 py-2 text-xs">
                        </td>
                        <td class="px-3 py-3">
                            <input form="<?= $fid ?>" name="nama_pengembang" maxlength="180" required
                                   value="<?= html_escape($row->nama_pengembang) ?>"
                                   class="w-44 rounded-lg border border-gray-200 dark:border-white/10 bg-transparent px-2 py-2 text-xs">
                            <select form="<?= $fid ?>" name="pengembang_id" aria-label="Pranala Direktori SRP2"
                                    class="mt-2 w-44 rounded-lg border border-gray-200 dark:border-white/10 bg-transparent px-2 py-2 text-xs">
                                <option value="0">- tidak terhubung -</option>
                                <?php foreach ($pengembang as $p): ?>
                                <option value="<?= (int) $p->id ?>" <?= (int) $p->id === (int) ($row->pengembang_id ?? 0) ? 'selected' : '' ?>><?= html_escape($p->nama_perusahaan) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="px-3 py-3">
                            <select form="<?= $fid ?>" name="asosiasi" aria-label="Asosiasi"
                                    class="w-32 rounded-lg border border-gray-200 dark:border-white/10 bg-transparent px-2 py-2 text-xs">
                                <option value="">- belum tercatat -</option>
                                <?php foreach ($asosiasi as $ka => $va): ?>
                                <option value="<?= $ka ?>" <?= $ka === trim((string) ($row->asosiasi ?? '')) ? 'selected' : '' ?>><?= $va ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="px-3 py-3">
                            <select form="<?= $fid ?>" name="kabupaten_id" aria-label="Kabupaten/Kota"
                                    class="w-40 rounded-lg border border-gray-200 dark:border-white/10 bg-transparent px-2 py-2 text-xs">
                                <option value="0">- belum tercatat -</option>
                                <?php foreach ($kabupaten as $kb): ?>
                                <option value="<?= (int) $kb->id ?>" <?= (int) $kb->id === (int) ($row->kabupaten_id ?? 0) ? 'selected' : '' ?>><?= html_escape($kb->nama) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td class="px-3 py-3">
                            <select form="<?= $fid ?>" name="status_serah_terima" aria-label="Status serah terima"
                                    class="w-40 rounded-lg border border-gray-200 dark:border-white/10 bg-transparent px-2 py-2 text-xs">
                                <?php foreach (Admin_Psu::STATUS_SERAH_TERIMA as $st): ?>
                                <option value="<?= $st ?>" <?= $st === $row->status_serah_terima ? 'selected' : '' ?>><?= psu_label_status($st) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="mt-1 block text-[10px] font-bold <?= $warna_status[$row->status_serah_terima] ?? '' ?>"><?= psu_label_status($row->status_serah_terima) ?></span>
                            <input form="<?= $fid ?>" type="date" name="tanggal_serah_terima" aria-label="Tanggal serah terima"
                                   value="<?= html_escape($row->tanggal_serah_terima ?? '') ?>"
                                   class="mt-2 w-40 rounded-lg border border-gray-200 dark:border-white/10 bg-transparent px-2 py-2 text-xs">
                        </td>
                        <td class="px-3 py-3">
                            <input form="<?= $fid ?>" type="checkbox" name="status_aktif" value="1" <?= $row->status_aktif ? 'checked' : '' ?>>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <button type="submit" form="<?= $fid ?>" class="tombol-aksi"><i class="ph ph-floppy-disk" aria-hidden="true"></i><span>Simpan</span></button>
                            <form class="inline" action="<?= base_url('Admin_Psu/hapus') ?>" method="post"
                                  onsubmit="return confirm('Hapus data PSU <?= html_escape($row->nama_perumahan) ?>?')">
                                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                                <input type="hidden" name="id" value="<?= (int) $row->id ?>">
                                <button class="tombol-aksi tombol-aksi-bahaya"><i class="ph ph-trash" aria-hidden="true"></i><span>Hapus</span></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-gray-500 dark:text-brand-muted">Tidak ada data PSU yang cocok dengan pencarian.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?= $this->load->view('admin/components/pagination', ['pager' => $pager, 'base_url' => $base_url], TRUE) ?>
    </div>
</div>
