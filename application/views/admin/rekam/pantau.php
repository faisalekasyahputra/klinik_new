<?php
/**
 * Papan cakupan Rekam Data - 35 kabupaten × 2 domain, satu triwulan.
 *
 * Barisnya SELALU 35, ada laporannya atau tidak. Layar rekam data lain
 * menampilkan daftar laporan; yang ini menampilkan daftar KABUPATEN, karena
 * yang ditanyakan dinas adalah siapa yang BELUM - dan yang belum tidak punya
 * baris laporan untuk ditampilkan.
 */
$this->load->helper('admin_table');
$nama_tw = [1 => 'TW I', 2 => 'TW II', 3 => 'TW III', 4 => 'TW IV'];

// Warna ditulis UTUH per keadaan, bukan dirakit dari potongan nama kelas -
// Tailwind di proyek ini sebagian datang dari CSS statis hasil panen, dan kelas
// yang baru lahir saat render tidak pernah ikut terpanen.
$gaya = [
    'belum'     => 'bg-gray-100 text-gray-500 dark:bg-white/5 dark:text-brand-muted',
    'draft'     => 'bg-amber-100 text-amber-800 dark:bg-amber-500/10 dark:text-amber-300',
    'menunggu'  => 'bg-blue-100 text-blue-800 dark:bg-blue-500/10 dark:text-blue-300',
    'perbaikan' => 'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-300',
    'diterima'  => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/10 dark:text-emerald-300',
];
// Urutan rincian ringkasan: yang sudah masuk dulu, yang belum di akhir. Label sama dengan sel tabel.
$keadaan_label = ['diterima' => 'diterima', 'menunggu' => 'menunggu ditinjau', 'perbaikan' => 'perlu perbaikan',
                  'draft' => 'draft, belum dikirim', 'belum' => 'belum ada laporan'];
$tautan = static function ($t, $tw) {
    return base_url('Admin_Rekam_Data?tahun=' . (int) $t . '&triwulan=' . (int) $tw);
};

// Butir cetak (17 Agt 2026) - lihat penjelasan lengkap di partial-nya.
$this->load->view('admin/layouts/cetak_rekap');
?>
<?php $this->load->view('admin/components/judul_halaman', ['jh_deskripsi' => 'Cakupan pelaporan seluruh kabupaten/kota untuk satu triwulan. Halaman ini <b>hanya baca</b> -
        keputusan terima atau minta perbaikan tetap kewenangan Admin Bidang Perumahan dan Kawasan.']); ?>

<?php // Ringkasan didahulukan: "berapa yang belum" adalah pertanyaannya, bukan detail per baris. ?>
<div class="tumpuk-bagian">
<div class="grid-kartu grid sm:grid-cols-2">
    <?php foreach ($domain as $kode => $nama):
        $r = $ringkas[$kode] ?? [];
    ?>
    <div class="kartu-admin isi-kartu">
        <div class="flex items-start justify-between gap-3">
            <div>
                <span class="text-sm font-black text-gray-900 dark:text-white"><?= html_escape($nama) ?></span>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-brand-muted">kab/kota sudah mengirim laporan <?= html_escape($nama_tw[$triwulan] ?? '') ?> <?= (int) $tahun ?></p>
            </div>
            <span class="text-2xl font-black text-gray-900 dark:text-white"><?= (int) ($r['masuk'] ?? 0) ?><span class="text-sm font-bold text-gray-400 dark:text-brand-muted">/<?= (int) ($r['total'] ?? 0) ?></span></span>
        </div>
        <?php // Rincian berwarna sama dengan label di tabel, sekaligus jadi keterangan warnanya; jumlahnya selalu = total. ?>
        <ul class="mt-3 flex flex-wrap gap-1.5" data-rincian-pantau>
            <?php foreach ($keadaan_label as $k => $label): ?>
            <li class="rounded-full px-2.5 py-1 text-[11px] font-bold <?= $gaya[$k] ?>"><?= (int) ($r[$k] ?? 0) ?> <?= html_escape($label) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endforeach; ?>
</div>

<div data-tabel-admin style="counter-reset: baris-admin <?= (int) (($table ?? [])['offset'] ?? 0) ?>" class="kartu-admin overflow-hidden">
    <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 px-4 py-3 dark:border-white/5">
        <span class="text-xs font-bold text-gray-500 dark:text-brand-muted mr-1">Tahun:</span>
        <?php
        // Tahun berjalan selalu ikut, walau belum ada satu pun laporannya -
        // kalau tidak, tahun baru tidak punya jalan masuk sampai ada yang melapor.
        $tahun_pilihan = array_values(array_unique(array_merge([date('Y')], $tahun_ada, [$tahun])));
        rsort($tahun_pilihan);
        foreach ($tahun_pilihan as $t): ?>
        <a href="<?= $tautan($t, $triwulan) ?>" class="chip-filter"<?= (int) $t === (int) $tahun ? ' aria-current="true"' : '' ?>><?= (int) $t ?></a>
        <?php endforeach; ?>

        <span class="ml-3 text-xs font-bold text-gray-500 dark:text-brand-muted mr-1">Triwulan:</span>
        <?php foreach ($nama_tw as $n => $label): ?>
        <a href="<?= $tautan($tahun, $n) ?>" class="chip-filter"<?= (int) $n === (int) $triwulan ? ' aria-current="true"' : '' ?>><?= html_escape($label) ?></a>
        <?php endforeach; ?>

        <?php /* Tombol unduh HANYA muncul kalau memang ada baris kabupaten -
                 pola yang sama dengan Rekam_Perumahan/Rekam_Kawasan. Permintaan
                 user 17 Agt 2026: "bisa diexport ke excel atau pdf di
                 breakdown ke per tw dan per tahun". Screenshot ini punya
                 dua sasaran: layar per-kabupaten (Excel sudah ada sejak
                 butir 23 putaran 2, lihat perumahan_capaian.php) dan layar
                 lintas-kabupaten INI, yang sebelumnya tidak punya unduhan
                 sama sekali. */ ?>
        <?php if ( ! empty($baris)): ?>
        <a href="<?= base_url('Admin_Rekam_Data/export?tahun=' . (int) $tahun . '&triwulan=' . (int) $triwulan) ?>"
           class="tombol-kedua ml-auto">
            <i class="ph ph-download-simple" aria-hidden="true"></i><span>Unduh Excel</span>
        </a>
        <a href="<?= base_url('Admin_Rekam_Data/export?periode=tahun&tahun=' . (int) $tahun) ?>"
           class="tombol-kedua">
            <i class="ph ph-calendar-blank" aria-hidden="true"></i><span>Unduh setahun</span>
        </a>
        <button type="button" onclick="window.print()"
           class="tombol-kedua">
            <i class="ph ph-printer" aria-hidden="true"></i><span>Cetak</span>
        </button>
        <?php endif; ?>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold">
                <tr>
                    <th class="px-4 py-3">Kabupaten / Kota</th>
                    <?php foreach ($domain as $nama): ?>
                    <th class="px-4 py-3"><?= html_escape($nama) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-white/5 text-gray-700 dark:text-gray-300">
                <?php if (empty($baris)): ?>
                <tr><td colspan="3" class="px-4 py-12 text-center text-gray-500 dark:text-brand-muted">Tabel kabupaten kosong - seed wilayah belum jalan.</td></tr>
                <?php else: foreach ($baris as $b): ?>
                <tr>
                    <td class="px-4 py-3 font-bold text-gray-900 dark:text-white"><?= html_escape($b['kabupaten']) ?></td>
                    <?php foreach (array_keys($domain) as $kode):
                        $sel = $b[$kode] ?? NULL;
                        $keadaan = $sel['keadaan'] ?? 'belum';
                    ?>
                    <td class="px-4 py-3">
                        <span class="inline-block rounded-full px-2.5 py-1 text-[11px] font-bold <?= $gaya[$keadaan] ?? $gaya['belum'] ?>">
                            <?= html_escape($sel['keadaan_label'] ?? 'Belum ada laporan') ?>
                        </span>
                        <?php // Draft belum dikirim = isian setengah jadi milik kab/kota; superadmin melihat yang sudah dikirim saja. ?>
                        <?php if ( ! empty($sel['laporan_id']) && $keadaan !== 'draft'): ?>
                        <a href="<?= base_url('Admin_Rekam_Data/detail/' . (int) $sel['laporan_id']) ?>" class="ml-1.5 text-[11px] font-bold text-blue-600 hover:underline dark:text-brand-primary">Lihat</a>
                        <?php endif; ?>
                        <?php if ($keadaan === 'perbaikan' && ! empty($sel['catatan_admin'])): ?>
                        <div class="mt-0.5 max-w-[220px] truncate text-[10px] text-gray-500 dark:text-brand-muted" title="<?= html_escape($sel['catatan_admin']) ?>">
                            <?= html_escape($sel['catatan_admin']) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
