<?php
/**
 * Struktur & Cakupan.
 *
 * Kode/ID sengaja ditampilkan tapi TIDAK bisa disunting - dan itu dinyatakan
 * di layar, bukan cuma tidak disediakan. Kolom yang hilang tanpa keterangan
 * terbaca sebagai fitur yang belum jadi, lalu ada yang menambahkannya.
 * Pernyataan itu kini ada di modal Ubah Nama, bukan di kepala kolom.
 *
 * Kotak integritas: empat jalur rujukan di $yatim. Sejak migrasi 069 semuanya
 * dijaga foreign key, jadi angkanya mestinya selalu nol; di atas nol berarti
 * FK-nya hilang dari skema. Mengganti kode tetap migrasi data, bukan isian
 * formulir.
 */
$csrf_nama = $this->security->get_csrf_token_name();
$csrf_hash = $this->security->get_csrf_hash();
$ada_yatim = array_sum($yatim) > 0;
?>
<div class="tumpuk-bagian" x-data="{ buka: false, jenis: '', kunci: '', nama: '', label: '' }">
    <?php $this->load->view('admin/components/judul_halaman', ['jh_deskripsi' => 'Bidang dan wilayah yang jadi acuan seluruh sistem, beserta siapa yang menanganinya.
        Yang bisa diubah dari sini hanya <b>nama tampilan</b>.']); ?>

    <?php // ============ INTEGRITAS ============ ?>
    <div class="p-4 rounded-2xl border text-sm flex items-start gap-3 <?= $ada_yatim
        ? 'bg-red-50 dark:bg-red-500/10 border-red-200 dark:border-red-500/20 text-red-800 dark:text-red-400'
        : 'bg-emerald-50 dark:bg-emerald-500/10 border-emerald-200 dark:border-emerald-500/20 text-emerald-800 dark:text-emerald-400' ?>">
        <i class="ph <?= $ada_yatim ? 'ph-warning-octagon' : 'ph-check-circle' ?> text-lg mt-0.5"></i>
        <div>
            <?php if ($ada_yatim): ?>
            <strong>Ada data yang menunjuk bidang atau wilayah yang tidak ada lagi.</strong>
            Laporkan ke pengembang agar dibetulkan.
            <?php else: ?>
            <strong>Semua data cocok dengan bidang dan wilayah yang ada.</strong>
            <?php endif; ?>
            <ul class="mt-2 space-y-0.5 text-xs">
                <?php foreach ($yatim as $label => $n): ?>
                <li><span class="font-mono font-bold"><?= (int) $n ?></span> - <?= html_escape($label) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <?php // ============ BIDANG ============ ?>
    <div data-tabel-admin style="counter-reset: baris-admin <?= (int) (($table ?? [])['offset'] ?? 0) ?>" class="kartu-admin overflow-hidden">
        <?php $this->load->view('admin/components/kepala_tabel', ['kt_judul' => 'Bidang', 'kt_jumlah' => count($bidang),
            'kt_keterangan' => '<span class="text-xs text-gray-500 dark:text-brand-muted">Langsung di bawah Kepala Dinas</span>']); ?>
        <div class="overflow-x-auto aksi-tetap">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Petugas</th>
                        <th class="px-4 py-3">Aduan Aktif</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                    <?php foreach ($bidang as $b): ?>
                    <tr>
                        <td class="px-4 py-3 font-bold text-gray-900 dark:text-white"><?= html_escape($b->nama) ?></td>
                        <td class="px-4 py-3"><code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs dark:bg-black/30"><?= html_escape($b->kode) ?></code></td>
                        <td class="px-4 py-3">
                            <?php if ((int) $b->petugas === 0): ?>
                            <a href="<?= base_url('Admin_Users') ?>" class="tombol-aksi tombol-aksi-bahaya"><i class="ph ph-user-plus" aria-hidden="true"></i><span>Belum ada, tetapkan</span></a>
                            <?php else: ?>
                            <span class="text-xs"><?= (int) $b->petugas ?> orang</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-xs"><?= (int) $b->aduan_aktif ?></td>
                        <td class="px-4 py-3">
                            <button type="button" title="Ubah nama tampilan"
                                    @click="jenis='bidang'; kunci=<?= htmlspecialchars(json_encode($b->kode), ENT_QUOTES) ?>; nama=<?= htmlspecialchars(json_encode($b->nama), ENT_QUOTES) ?>; label='Bidang'; buka=true" class="tombol-aksi">
                                <i class="ph ph-pencil-simple" aria-hidden="true"></i><span>Ubah nama</span>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php // ============ WILAYAH ============ ?>
    <?php $tanpa_petugas = count(array_filter($wilayah, static function ($w) { return (int) $w->petugas === 0; })); ?>
    <div data-tabel-admin style="counter-reset: baris-admin <?= (int) (($table ?? [])['offset'] ?? 0) ?>" class="kartu-admin overflow-hidden">
        <?php $this->load->view('admin/components/kepala_tabel', ['kt_judul' => 'Kabupaten/Kota', 'kt_jumlah' => count($wilayah),
            'kt_keterangan' => $tanpa_petugas > 0 ? '<span class="text-xs font-bold text-red-600 dark:text-red-400">' . (int) $tanpa_petugas . ' wilayah belum punya petugas</span>' : '']); ?>
        <div class="overflow-x-auto aksi-tetap">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Kode Kemendagri</th>
                        <th class="px-4 py-3">Petugas</th>
                        <th class="px-4 py-3">Laporan Rekam Data</th>
                        <th class="px-4 py-3">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                    <?php foreach ($wilayah as $w): ?>
                    <tr>
                        <td class="px-4 py-3 font-bold text-gray-900 dark:text-white"><?= html_escape($w->nama) ?></td>
                        <td class="px-4 py-3"><code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs dark:bg-black/30"><?= (int) $w->id ?></code></td>
                        <td class="px-4 py-3">
                            <?php if ((int) $w->petugas === 0): ?>
                            <span class="inline-block rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-bold text-gray-500 dark:bg-white/5 dark:text-brand-muted">Belum ada</span>
                            <?php else: ?>
                            <span class="text-xs"><?= (int) $w->petugas ?> orang</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-xs"><?= (int) $w->laporan ?></td>
                        <td class="px-4 py-3">
                            <button type="button" title="Ubah nama tampilan"
                                    @click="jenis='kabupaten'; kunci=<?= htmlspecialchars(json_encode((string) $w->id), ENT_QUOTES) ?>; nama=<?= htmlspecialchars(json_encode($w->nama), ENT_QUOTES) ?>; label='Kabupaten/Kota'; buka=true" class="tombol-aksi">
                                <i class="ph ph-pencil-simple" aria-hidden="true"></i><span>Ubah nama</span>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php // Satu modal untuk kedua tabel - 40 baris x formulir inline akan mendorong tabel melewati wadahnya. ?>
    <div x-show="buka" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
        <div @click.away="buka = false" class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-5 shadow-2xl dark:border-white/10 dark:bg-brand-card">
            <div class="flex items-start justify-between gap-3 border-b border-gray-200 pb-3 dark:border-white/10">
                <div>
                    <h3 class="text-sm font-black text-gray-900 dark:text-white">Ubah Nama Tampilan</h3>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-brand-muted"><span x-text="label"></span> <code x-text="kunci"></code></p>
                </div>
                <button type="button" @click="buka = false" aria-label="Tutup" class="tombol-ikon"><i class="ph ph-x" aria-hidden="true"></i></button>
            </div>

            <form method="POST" action="<?= base_url('Admin_Struktur/ubah_nama') ?>" class="mt-4 space-y-3 text-xs">
                <input type="hidden" name="<?= $csrf_nama ?>" value="<?= $csrf_hash ?>">
                <input type="hidden" name="jenis" :value="jenis">
                <input type="hidden" name="kunci" :value="kunci">

                <label class="block">
                    <span class="mb-1 block font-bold text-gray-700 dark:text-gray-300">Nama</span>
                    <input type="text" name="nama" x-model="nama" required maxlength="100"
                           class="w-full rounded-lg border border-gray-200 bg-white px-3 py-2 text-gray-800 dark:border-white/10 dark:bg-black/20 dark:text-gray-200">
                </label>

                <p class="rounded-lg bg-gray-50 p-2.5 text-[11px] leading-relaxed text-gray-500 dark:bg-black/20 dark:text-brand-muted">
                    <i class="ph ph-info mr-1" aria-hidden="true"></i>
                    Kode tidak bisa diubah, hanya nama tampilannya. Kode dipakai oleh data lain di seluruh sistem.
                </p>

                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" @click="buka = false" class="tombol-kedua"><span>Batal</span></button>
                    <button type="submit" class="tombol-utama"><i class="ph ph-floppy-disk"></i><span>Simpan</span></button>
                </div>
            </form>
        </div>
    </div>
</div>
