<?php
/**
 * Serah Terima PSU - halaman publik. Lihat Psu::index() dan migrasi 043.
 *
 * Tata letak & kelas CSS mengikuti pages/pengembang/sertifikasi.php (tabel
 * portal dgn cari/urut client-side, portal_data_table.php) - daftar ini
 * diharapkan tumbuh sepelan Direktori SRP2, bukan ribuan baris, jadi
 * pencarian client-side (bukan server-side B8 seperti admin) sudah cukup.
 */
// Psu::index() sudah memuat ketiganya - disebut lagi di sini supaya view
// tidak diam-diam bergantung pada pemanggilnya (load->helper() idempoten).
$this->load->helper('srp2');
$this->load->helper('psu');
$this->load->helper('ternak'); // tgl_id() - lihat pemakaiannya di bawah
$warna_status = [
    'belum_diserahkan'  => 'background:rgba(156,163,175,.14);color:#6b7280',
    'proses_verifikasi' => 'background:rgba(217,119,6,.12);color:#92400e',
    'sudah_diserahkan'  => 'background:rgba(16,185,129,.12);color:#047857',
];
?>
<section class="w-full px-4 py-8 font-outfit sm:px-6 lg:px-8" style="color:var(--portal-text)">
    <div class="mx-auto max-w-6xl">
        <div class="mb-5">
            <h1 class="text-2xl font-black tracking-tight sm:text-3xl" style="color:var(--portal-text)">Serah Terima PSU</h1>
            <p class="mt-2 max-w-2xl text-xs leading-relaxed" style="color:var(--portal-text-muted)">
                Status serah terima Prasarana, Sarana, dan Utilitas (PSU) perumahan dari pengembang
                kepada pemerintah daerah - dikelola Dinas Perumahan dan Kawasan Permukiman Provinsi Jawa Tengah.
            </p>
        </div>

        <div data-portal-table data-table-per-page="10" class="overflow-hidden rounded-2xl" style="background:var(--portal-bg-card);border:1px solid var(--portal-border);box-shadow:0 8px 24px rgba(0,80,95,.06)">
            <div class="flex flex-col gap-3 border-b px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5" style="border-color:var(--portal-border)">
                <div>
                    <h2 class="text-sm font-extrabold" style="color:var(--portal-text)">Daftar Perumahan</h2>
                    <p class="mt-0.5 text-[10px]" style="color:var(--portal-text-muted)"><?= count($daftar_psu) ?> perumahan tercatat</p>
                </div>
                <label class="relative block flex-1 sm:w-60">
                    <span class="sr-only">Cari perumahan</span>
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-[10px]" style="color:var(--teal)"></i>
                    <input data-table-search type="search" placeholder="Cari nama perumahan/pengembang..." class="w-full rounded-lg py-2 pl-8 pr-3 text-[11px] outline-none" style="background:var(--portal-bg);border:1px solid var(--portal-border);color:var(--portal-text)">
                </label>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-xs">
                    <thead style="background:var(--portal-bg)">
                        <tr class="uppercase tracking-wider" style="color:var(--portal-text-muted);font-size:9px">
                            <th class="w-12 px-4 py-2.5 font-bold">No.</th>
                            <th class="px-2 py-2.5 font-bold"><button type="button" data-table-sort="nama" aria-sort="none" class="inline-flex items-center gap-1 font-bold uppercase tracking-wider" style="color:inherit">Nama Perumahan <i data-table-sort-icon class="fa-solid fa-sort text-[9px] opacity-50"></i></button></th>
                            <th class="px-3 py-2.5 font-bold"><button type="button" data-table-sort="pengembang" aria-sort="none" class="inline-flex items-center gap-1 font-bold uppercase tracking-wider" style="color:inherit">Pengembang <i data-table-sort-icon class="fa-solid fa-sort text-[9px] opacity-50"></i></button></th>
                            <th class="px-3 py-2.5 font-bold">Asosiasi</th>
                            <th class="px-3 py-2.5 font-bold"><button type="button" data-table-sort="kabupaten" aria-sort="none" class="inline-flex items-center gap-1 font-bold uppercase tracking-wider" style="color:inherit">Kabupaten/Kota <i data-table-sort-icon class="fa-solid fa-sort text-[9px] opacity-50"></i></button></th>
                            <th class="px-4 py-2.5 font-bold">Status Serah Terima</th>
                        </tr>
                    </thead>
                    <tbody data-table-body>
                        <?php foreach ($daftar_psu as $i => $row): ?>
                            <tr data-table-row class="border-t transition-colors hover:bg-[#00a3b5]/[.04]" style="border-color:var(--portal-border)">
                                <td data-table-index class="px-4 py-2.5 font-bold" style="color:var(--teal-bright)"><?= $i + 1 ?></td>
                                <td data-table-column="nama" class="px-2 py-2.5 font-semibold" style="color:var(--portal-text)"><?= html_escape($row->nama_perumahan) ?></td>
                                <td data-table-column="pengembang" class="px-3 py-2.5" style="color:var(--portal-text)"><?= html_escape($row->nama_pengembang) ?></td>
                                <td data-table-column="asosiasi" class="px-3 py-2.5" style="color:var(--portal-text-muted)"><?= html_escape(srp2_label_asosiasi($row->asosiasi ?? '')) ?></td>
                                <td data-table-column="kabupaten" class="px-3 py-2.5" style="color:var(--portal-text-muted)"><?= html_escape($nama_kabupaten[(int) ($row->kabupaten_id ?? 0)] ?? '-') ?></td>
                                <td class="px-4 py-2.5">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold" style="<?= $warna_status[$row->status_serah_terima] ?? '' ?>">
                                        <span class="h-1.5 w-1.5 rounded-full" style="background:currentColor"></span>
                                        <?= html_escape(psu_label_status($row->status_serah_terima)) ?>
                                    </span>
                                    <?php if ($row->status_serah_terima === 'sudah_diserahkan' && ! empty($row->tanggal_serah_terima)): ?>
                                    <span class="mt-1 block text-[10px]" style="color:var(--portal-text-muted)"><?= html_escape(tgl_id($row->tanggal_serah_terima, TRUE)) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div data-table-empty hidden class="border-t px-4 py-8 text-center text-xs" style="border-color:var(--portal-border);color:var(--portal-text-muted)">Perumahan yang dicari belum tersedia.</div>
            <div class="flex flex-col gap-3 border-t px-4 py-3 sm:flex-row sm:items-center sm:justify-between" style="border-color:var(--portal-border)">
                <p data-table-summary class="text-[10px]" style="color:var(--portal-text-muted)"></p>
                <div class="flex items-center justify-between gap-3 sm:justify-end">
                    <label class="flex items-center gap-1.5 text-[10px]" style="color:var(--portal-text-muted)">Tampil
                        <select data-table-per-page-select class="rounded-md px-1.5 py-1 text-[10px] font-bold outline-none" style="background:var(--portal-bg);border:1px solid var(--portal-border);color:var(--portal-text)"><option value="10" selected>10</option><option value="25">25</option><option value="50">50</option></select>
                    </label>
                    <div data-table-pagination class="flex items-center gap-1" aria-label="Pagination tabel"></div>
                </div>
            </div>
        </div>

        <p class="mt-4 text-[11px] leading-relaxed" style="color:var(--portal-text-muted)">
            Status ditentukan berdasarkan verifikasi Dinas Perumahan dan Kawasan Permukiman. Belum menemukan
            perumahan yang dicari? Data ini masih terus dilengkapi - hubungi Dinas untuk informasi terbaru.
        </p>
    </div>
</section>
<?php $this->load->view('components/portal_data_table'); ?>
