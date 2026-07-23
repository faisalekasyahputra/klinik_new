<section class="w-full px-4 py-8 font-outfit sm:px-6 lg:px-8" style="color:var(--portal-text)">
    <div class="mx-auto max-w-6xl">
        <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-2xl font-black tracking-tight sm:text-3xl" style="color:var(--portal-text)">Sertifikasi Pengembang</h1>
                <p class="mt-2 max-w-2xl text-xs leading-relaxed" style="color:var(--portal-text-muted)">Daftar pengembang perumahan yang telah tersertifikasi dan terdaftar di Klinik PKP.</p>
            </div>
            <a href="<?= base_url('Pengembang/syarat') ?>" data-tab-link data-tab-key="pengembang_syarat" class="btn-primary inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-[11px] font-extrabold uppercase tracking-wide"><i class="fa-solid fa-arrow-right"></i> Daftar SRP2</a>
        </div>

        <div data-portal-table data-table-per-page="10" class="overflow-hidden rounded-2xl" style="background:var(--portal-bg-card);border:1px solid var(--portal-border);box-shadow:0 8px 24px rgba(0,80,95,.06)">
            <div class="flex flex-col gap-3 border-b px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5" style="border-color:var(--portal-border)">
                <div>
                    <h2 class="text-sm font-extrabold" style="color:var(--portal-text)">Daftar Pengembang Bersertifikat</h2>
                    <p class="mt-0.5 text-[10px]" style="color:var(--portal-text-muted)"><?= count($daftar_pengembang) ?> pengembang terdaftar</p>
                </div>
                <div class="flex w-full items-center gap-2 sm:w-auto">
                    <label class="relative block flex-1 sm:w-60">
                        <span class="sr-only">Cari pengembang</span>
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-[10px]" style="color:var(--teal)"></i>
                        <input data-table-search type="search" placeholder="Cari pengembang..." class="w-full rounded-lg py-2 pl-8 pr-3 text-[11px] outline-none" style="background:var(--portal-bg);border:1px solid var(--portal-border);color:var(--portal-text)">
                    </label>
                    <span class="hidden items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold sm:inline-flex" style="background:rgba(16,185,129,.1);color:#059669"><i class="fa-solid fa-circle-check"></i> Terverifikasi</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[620px] text-left text-xs">
                    <thead style="background:var(--portal-bg)">
                        <tr class="uppercase tracking-wider" style="color:var(--portal-text-muted);font-size:9px">
                            <th class="w-12 px-4 py-2.5 font-bold">No.</th>
                            <th class="px-2 py-2.5 font-bold">
                                <button type="button" data-table-sort="nama" aria-sort="none" class="inline-flex items-center gap-1 font-bold uppercase tracking-wider" style="color:inherit">Nama Perusahaan <i data-table-sort-icon class="fa-solid fa-sort text-[9px] opacity-50"></i></button>
                            </th>
                            <th class="px-3 py-2.5 font-bold">
                                <button type="button" data-table-sort="status" aria-sort="none" class="inline-flex items-center gap-1 font-bold uppercase tracking-wider" style="color:inherit">Status <i data-table-sort-icon class="fa-solid fa-sort text-[9px] opacity-50"></i></button>
                            </th>
                            <th class="px-4 py-2.5 text-right font-bold">Detail</th>
                        </tr>
                    </thead>
                    <tbody data-table-body>
                        <?php foreach ($daftar_pengembang as $i => $row): ?>
                            <tr data-table-row class="border-t transition-colors hover:bg-[#00a3b5]/[.04]" style="border-color:var(--portal-border)">
                                <td data-table-index class="px-4 py-2.5 font-bold" style="color:var(--teal-bright)"><?= $i + 1 ?></td>
                                <td data-table-column="nama" class="px-2 py-2.5 font-semibold" style="color:var(--portal-text)"><?= htmlspecialchars($row->nama_perusahaan, ENT_QUOTES, 'UTF-8') ?></td>
                                <td data-table-column="status" class="px-3 py-2.5"><span class="inline-flex items-center gap-1.5 text-[10px] font-bold" style="color:#059669"><span class="h-1.5 w-1.5 rounded-full" style="background:#10b981"></span>Aktif</span></td>
                                <td class="px-4 py-2.5 text-right"><a href="<?= base_url('Pengembang/profil/' . $row->id) ?>" data-tab-link data-tab-key="pengembang_list" class="inline-flex items-center gap-1 rounded-md px-2.5 py-1.5 text-[10px] font-bold" style="color:var(--teal);background:rgba(0,163,181,.08);border:1px solid rgba(0,163,181,.18)"><i class="fa-solid fa-arrow-up-right-from-square"></i> Buka</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div data-table-empty hidden class="border-t px-4 py-8 text-center text-xs" style="border-color:var(--portal-border);color:var(--portal-text-muted)">Pengembang yang dicari belum tersedia.</div>
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
    </div>
</section>
<?php $this->load->view('components/portal_data_table'); ?>
