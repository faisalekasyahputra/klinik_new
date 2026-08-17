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
$tautan = static function ($t, $tw) {
    return base_url('Admin_Rekam_Data?tahun=' . (int) $t . '&triwulan=' . (int) $tw);
};

// Butir cetak (17 Agt 2026) - lihat penjelasan lengkap di partial-nya.
$this->load->view('admin/layouts/cetak_rekap');
?>
<div class="mb-6">
    <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2">Pantau Rekam Data</h2>
    <p class="text-sm text-gray-500 dark:text-brand-muted">
        Cakupan pelaporan seluruh kabupaten/kota untuk satu triwulan. Halaman ini <b>hanya baca</b> -
        keputusan terima atau minta perbaikan tetap kewenangan Admin Bidang Perumahan dan Kawasan.
    </p>
</div>

<?php // Ringkasan didahulukan: "berapa yang belum" adalah pertanyaannya, bukan detail per baris. ?>
<div class="mb-6 grid gap-3 sm:grid-cols-2">
    <?php foreach ($domain as $kode => $nama):
        $r = $ringkas[$kode] ?? ['total' => 0, 'masuk' => 0, 'diterima' => 0, 'belum' => 0];
    ?>
    <div class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-white/5 dark:bg-brand-card">
        <div class="flex items-baseline justify-between">
            <span class="text-sm font-black text-gray-900 dark:text-white"><?= html_escape($nama) ?></span>
            <span class="text-2xl font-black text-gray-900 dark:text-white"><?= (int) $r['masuk'] ?><span class="text-sm font-bold text-gray-400 dark:text-brand-muted">/<?= (int) $r['total'] ?></span></span>
        </div>
        <p class="mt-1 text-xs text-gray-500 dark:text-brand-muted">
            sudah mengirim · <b class="text-emerald-600 dark:text-emerald-400"><?= (int) $r['diterima'] ?></b> diterima ·
            <b class="text-gray-700 dark:text-gray-300"><?= (int) $r['belum'] ?></b> belum melapor sama sekali
        </p>
    </div>
    <?php endforeach; ?>
</div>

<div data-tabel-admin class="bg-white dark:bg-brand-card rounded-3xl shadow-sm border border-gray-200 dark:border-white/5 overflow-hidden">
    <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 p-4 dark:border-white/5">
        <span class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-brand-muted mr-1">Tahun:</span>
        <?php
        // Tahun berjalan selalu ikut, walau belum ada satu pun laporannya -
        // kalau tidak, tahun baru tidak punya jalan masuk sampai ada yang melapor.
        $tahun_pilihan = array_values(array_unique(array_merge([date('Y')], $tahun_ada, [$tahun])));
        rsort($tahun_pilihan);
        foreach ($tahun_pilihan as $t): ?>
        <a href="<?= $tautan($t, $triwulan) ?>" class="px-3 py-1 rounded-lg text-xs font-bold border transition-colors <?= (int) $t === (int) $tahun ? 'bg-brand-primary/20 border-brand-primary/50 text-brand-primary' : 'border-gray-200 dark:border-white/10 text-gray-600 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/10' ?>"><?= (int) $t ?></a>
        <?php endforeach; ?>

        <span class="ml-3 text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-brand-muted mr-1">Triwulan:</span>
        <?php foreach ($nama_tw as $n => $label): ?>
        <a href="<?= $tautan($tahun, $n) ?>" class="px-3 py-1 rounded-lg text-xs font-bold border transition-colors <?= (int) $n === (int) $triwulan ? 'bg-brand-primary/20 border-brand-primary/50 text-brand-primary' : 'border-gray-200 dark:border-white/10 text-gray-600 dark:text-brand-muted hover:bg-gray-100 dark:hover:bg-white/10' ?>"><?= html_escape($label) ?></a>
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
           class="ml-auto rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-bold text-gray-700 transition-colors hover:bg-gray-50 dark:border-white/10 dark:text-brand-muted dark:hover:bg-white/5">
            <i class="ph ph-download-simple mr-1" aria-hidden="true"></i> Unduh Excel
        </a>
        <a href="<?= base_url('Admin_Rekam_Data/export?periode=tahun&tahun=' . (int) $tahun) ?>"
           class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-bold text-gray-700 dark:border-white/10 dark:text-brand-muted">
            <i class="ph ph-calendar-blank mr-1" aria-hidden="true"></i> Unduh Setahun
        </a>
        <button type="button" onclick="window.print()"
           class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-bold text-gray-700 transition-colors hover:bg-gray-50 dark:border-white/10 dark:text-brand-muted dark:hover:bg-white/5">
            <i class="ph ph-printer mr-1" aria-hidden="true"></i> Cetak
        </button>
        <?php endif; ?>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm whitespace-nowrap">
            <thead class="bg-gray-50 dark:bg-black/20 text-gray-500 dark:text-brand-muted text-xs font-bold uppercase tracking-wider">
                <tr>
                    <th class="px-4 py-4">Kabupaten / Kota</th>
                    <?php foreach ($domain as $nama): ?>
                    <th class="px-4 py-4"><?= html_escape($nama) ?></th>
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
                        <?php if ( ! empty($sel['laporan_id'])): ?>
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
