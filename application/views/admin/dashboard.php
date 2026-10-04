<?php
$this->load->helper('housing_queue');
$housing_statuses = housing_queue_statuses();
$kelas_status = [
    'pending' => 'pending', 'approved' => 'ok', 'rejected' => 'reject', 'needs_revision' => 'process',
    'Baru' => 'pending', 'Diproses' => 'process', 'Selesai' => 'ok',
    'Draft' => 'process', 'Pending' => 'pending', 'Diterima' => 'ok', 'Ditolak' => 'reject',
    'Diajukan' => 'pending', 'Ditinjau Bidang' => 'process', 'Dibatalkan' => 'reject',
];
$jumlah_kartu = count($kartu_domain) + ($rekam ? 1 : 0);
$redup = fn($n) => $n > 0 ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-brand-muted/70';
$pk = $peringatan_keamanan;
?>
<?php $this->load->view('admin/components/judul_halaman', ['jh_deskripsi' => 'Jumlah pekerjaan per layanan. Klik angka untuk membuka daftar yang sudah tersaring.']); ?>

<?php // Di dasbor setiap baris adalah deret kartu: jarak tegak antarbaris disamakan dengan jarak antarkartu. ?>
<div class="tumpuk-bagian" style="--jarak-bagian: var(--jarak-kartu)">
<?php
/* Pita "Perlu tindakan" (4 Okt 2026): yang harus dilihat lebih dulu, diambil dari angka utama
   kartu layanan di bawah (jadi pasti sama), plus antrean tanpa wilayah dan peringatan keamanan.
   Dulu angka ini tersebar di enam kartu setara dan satu banner, mata harus menyisir semuanya. */
$tindakan = [];
foreach ($kartu_domain as $k) {
    if ($k['utama']['n'] > 0) {
        $tindakan[] = ['n' => $k['utama']['n'], 'label' => $k['label'], 'ket' => strtolower($k['utama']['label']), 'url' => $k['utama']['url'], 'ikon' => $k['icon'], 'nada' => 'utama'];
    }
}
if ($antrean_tanpa_wilayah > 0) {
    $tindakan[] = ['n' => $antrean_tanpa_wilayah, 'label' => 'Antrean tanpa wilayah', 'ket' => 'antrean menunggu belum memiliki wilayah, tidak terlihat admin kab/kota', 'url' => 'Admin?status=pending&tanpa_wilayah=1', 'ikon' => 'ph-map-pin-area', 'nada' => 'waspada'];
}
if ((int) $pk['tinggi'] > 0) {
    $tindakan[] = ['n' => (int) $pk['total'], 'label' => 'Peringatan keamanan', 'ket' => (int) $pk['jam'] . ' jam terakhir', 'url' => 'Admin_Audit?aksi=peringatan_keamanan', 'ikon' => 'ph-shield-warning', 'nada' => 'bahaya'];
}
$total_tindakan = array_sum(array_column(array_filter($tindakan, fn($t) => $t['nada'] === 'utama'), 'n'));
$nada_angka = ['utama' => 'text-amber-600 dark:text-brand-primary', 'waspada' => 'text-orange-600 dark:text-orange-400', 'bahaya' => 'text-red-600 dark:text-red-400'];
?>
<section class="kartu-admin isi-kartu" aria-label="Perlu tindakan" data-pita-tindakan>
    <?php if ($tindakan): ?>
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
        <div class="flex shrink-0 items-center gap-3 lg:pr-4">
            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-brand-primary/15 dark:text-brand-primary"><i class="ph ph-bell-ringing text-2xl" aria-hidden="true"></i></span>
            <div>
                <p class="text-3xl font-black leading-none text-gray-900 dark:text-white"><?= angka_id($total_tindakan) ?></p>
                <p class="mt-1 text-xs font-semibold text-gray-500 dark:text-brand-muted">perlu tindakan</p>
            </div>
        </div>
        <ul class="grid flex-1 grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-3">
            <?php foreach ($tindakan as $t): ?>
            <?php // Gaya ubin di <li>, <a> tetap tautan polos: ini daftar tautan, bukan tombol (uji_regresi_tampilan). ?>
            <li class="rounded-xl bg-gray-50 transition-colors hover:bg-gray-100 dark:bg-white/5 dark:hover:bg-white/10"><a href="<?= html_escape(base_url($t['url'])) ?>" class="group flex h-full items-center gap-3 px-3 py-2">
                <span class="text-2xl font-black leading-none <?= $nada_angka[$t['nada']] ?>"><?= angka_id($t['n']) ?></span>
                <span class="min-w-0 flex-1">
                    <span class="flex items-center gap-1.5 text-sm font-bold text-gray-900 dark:text-white"><i class="ph <?= html_escape($t['ikon']) ?>" aria-hidden="true"></i><?= html_escape($t['label']) ?></span>
                    <span class="block text-xs text-gray-500 dark:text-brand-muted"><?= html_escape($t['ket']) ?></span>
                </span>
                <?php // Panah tanpa badan (chevron) dengan pendar ambien, permintaan user 4 Okt 2026. ?>
                <span class="panah-sorot panah-sorot-besar transition-transform group-hover:translate-x-0.5" aria-hidden="true"><i class="ph ph-caret-right"></i></span>
            </a></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php else: ?>
    <p class="flex items-center gap-2 text-sm font-semibold text-emerald-700 dark:text-emerald-400"><i class="ph ph-check-circle text-xl" aria-hidden="true"></i>Semua beres: tidak ada pekerjaan yang menunggu tindakan.</p>
    <?php endif; ?>
</section>

<section aria-label="Pekerjaan per layanan">
    <div class="deret-kartu grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 grid-kartu" style="--jumlah-kartu: <?= max(1, $jumlah_kartu) ?>">
        <?php foreach ($kartu_domain as $k): $u = $k['utama']; $ada = $u['n'] > 0; ?>
        <article class="kartu-admin isi-kartu flex flex-col<?= $ada ? ' ring-1 ring-amber-300 dark:ring-brand-primary/40' : '' ?>">
            <h2 class="flex items-center gap-2 text-sm font-semibold text-gray-600 dark:text-brand-muted">
                <i class="ph <?= html_escape($k['icon']) ?> text-lg text-blue-600 dark:text-brand-primary" aria-hidden="true"></i><?= html_escape($k['label']) ?>
            </h2>
            <a href="<?= html_escape(base_url($u['url'])) ?>" class="mt-1 flex items-baseline gap-2 hover:underline">
                <span class="text-3xl font-black leading-tight <?= $ada ? 'text-amber-600 dark:text-brand-primary' : 'text-gray-400 dark:text-brand-muted/70' ?>"><?= angka_id($u['n']) ?></span>
                <span class="text-xs font-semibold <?= $u['n'] > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-gray-400 dark:text-brand-muted/70' ?>"><?= html_escape(strtolower($u['label'])) ?></span>
            </a>
            <ul class="mt-2 space-y-0.5 text-xs">
                <?php foreach ($k['rincian'] as $r): ?>
                <li><a href="<?= html_escape(base_url($r['url'])) ?>" class="flex justify-between gap-2 text-gray-500 hover:underline dark:text-brand-muted"><span><?= html_escape($r['label']) ?></span><span class="font-bold <?= $redup($r['n']) ?>"><?= angka_id($r['n']) ?></span></a></li>
                <?php endforeach; ?>
            </ul>
            <?php // Tombol di dasar kartu (mt-auto): sejajar antarkartu berapa pun panjang rinciannya. ?>
            <div class="mt-auto pt-4"><a href="<?= html_escape(base_url($k['url'])) ?>" class="tombol-kedua w-full font-semibold"><span><?= html_escape($k['lihat']) ?></span><span class="panah-sorot ml-auto" aria-hidden="true"><i class="ph ph-caret-right"></i></span></a></div>
        </article>
        <?php endforeach; ?>

        <?php if ($rekam): $masuk = array_sum(array_column($rekam['domain'], 'masuk')); ?>
        <article class="kartu-admin isi-kartu flex flex-col">
            <h2 class="flex items-center gap-2 text-sm font-semibold text-gray-600 dark:text-brand-muted">
                <i class="ph ph-chart-line-up text-lg text-blue-600 dark:text-brand-primary" aria-hidden="true"></i>Rekam Data
            </h2>
            <p class="mt-1 flex items-baseline gap-2">
                <span class="text-3xl font-black leading-tight <?= $redup($masuk) ?>"><?= angka_id($masuk) ?></span>
                <span class="text-xs font-semibold text-gray-500 dark:text-brand-muted">terkirim &middot; TW <?= (int) $rekam['triwulan'] ?>/<?= (int) $rekam['tahun'] ?></span>
            </p>
            <ul class="mt-2 space-y-0.5 text-xs text-gray-500 dark:text-brand-muted">
                <?php foreach ($rekam['domain'] as $d): ?>
                <li class="flex justify-between gap-2"><span><?= html_escape($d['label']) ?></span><span class="font-bold <?= $redup($d['masuk']) ?>"><?= angka_id($d['masuk']) ?> dari <?= angka_id($d['total']) ?></span></li>
                <?php endforeach; ?>
                <li class="flex justify-between gap-2"><span>Diterima</span><span class="font-bold <?= $redup($rekam['diterima']) ?>"><?= angka_id($rekam['diterima']) ?></span></li>
                <li class="flex justify-between gap-2"><span>Perlu perbaikan</span><span class="font-bold <?= $redup($rekam['perbaikan']) ?>"><?= angka_id($rekam['perbaikan']) ?></span></li>
            </ul>
            <?php // Tombol di dasar kartu (mt-auto): sejajar antarkartu berapa pun panjang rinciannya. ?>
            <div class="mt-auto pt-4"><a href="<?= base_url('Admin_Rekam_Data?tahun=' . (int) $rekam['tahun'] . '&triwulan=' . (int) $rekam['triwulan']) ?>" class="tombol-kedua w-full font-semibold"><span>Lihat laporan</span><span class="panah-sorot ml-auto" aria-hidden="true"><i class="ph ph-caret-right"></i></span></a></div>
        </article>
        <?php endif; ?>
    </div>
</section>

<?php // Kedua kartu bawah sama tinggi (stretch); daftar pengajuan menggulir di dalam kartunya. ?>
<div class="grid grid-cols-1 grid-kartu xl:grid-cols-3">
    <section class="kartu-admin flex flex-col xl:col-span-2">
        <div class="border-b border-gray-100 px-4 pb-3 pt-4 dark:border-white/5">
            <h2 class="text-base font-bold text-gray-900 dark:text-white">Pengajuan terbaru</h2>
            <p class="text-xs text-gray-500 dark:text-brand-muted">Dua puluh pengajuan paling baru dari seluruh layanan; gulir untuk melihat lainnya.</p>
        </div>
        <?php if (empty($aktivitas)): ?>
            <p class="isi-kartu text-sm text-gray-500 dark:text-brand-muted">Belum ada pengajuan yang masuk.</p>
        <?php else: ?>
        <?php /* Daftar absolut di dalam pembungkus flex-1: tingginya tidak ikut menentukan tinggi baris,
                 jadi kartu mengikuti Ringkasan Sistem dan isinya menggulir. Di layar sempit (bertumpuk)
                 pembungkus diberi tinggi minimum. */ ?>
        <div class="relative min-h-[20rem] flex-1 xl:min-h-0">
        <ul class="gulir-halus absolute inset-0 divide-y divide-gray-100 overflow-y-auto dark:divide-white/5">
            <?php foreach ($aktivitas as $a):
                $housing_status = $housing_statuses[$a['status']] ?? NULL;
                $status_label = $housing_status['label'] ?? ($a['label'] ?? NULL) ?? $a['status'];
                $status_kelas = $housing_status['badge'] ?? ($kelas_status[$a['status']] ?? 'pending');
            ?>
            <li><a href="<?= html_escape(base_url($a['url'])) ?>" class="flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-50 dark:hover:bg-white/5">
                <i class="ph <?= html_escape($a['icon']) ?> shrink-0 text-gray-400 dark:text-brand-muted" aria-hidden="true"></i>
                <span class="hidden w-32 shrink-0 text-xs text-gray-500 sm:block dark:text-brand-muted"><?= html_escape($a['jenis']) ?></span>
                <span class="min-w-0 flex-1 truncate font-semibold text-gray-900 dark:text-white"><?= html_escape($a['judul']) ?></span>
                <span class="shrink-0"><?= $this->load->view('admin/components/status_badge', ['label' => ucfirst(strtolower($status_label)), 'kelas' => $status_kelas], TRUE) ?></span>
                <span class="shrink-0 text-xs text-gray-500 dark:text-brand-muted"><?= html_escape(tgl_id($a['waktu'], TRUE)) ?></span>
            </a></li>
            <?php endforeach; ?>
        </ul>
        </div>
        <?php endif; ?>
    </section>

    <aside class="kartu-admin isi-kartu">
        <h2 class="text-base font-bold text-gray-900 dark:text-white">Ringkasan Sistem</h2>
        <h3 class="mt-3 text-xs font-semibold text-gray-500 dark:text-brand-muted">Akun per peran</h3>
        <dl class="mt-1 grid grid-cols-3 gap-2 sm:grid-cols-5 xl:grid-cols-3">
            <?php foreach ($akun_peran as $label => $n): ?>
            <div class="rounded-lg bg-gray-50 px-2 py-1.5 dark:bg-white/5"><dt class="text-xs text-gray-500 dark:text-brand-muted"><?= html_escape($label) ?></dt><dd class="text-lg font-black text-gray-900 dark:text-white"><?= angka_id($n) ?></dd></div>
            <?php endforeach; ?>
        </dl>
        <h3 class="mt-3 text-xs font-semibold text-gray-500 dark:text-brand-muted">Data</h3>
        <dl class="mt-1 grid grid-cols-2 gap-2 sm:grid-cols-3 xl:grid-cols-2">
            <div class="rounded-lg bg-gray-50 px-2 py-1.5 dark:bg-white/5"><dt class="text-xs text-gray-500 dark:text-brand-muted">Warga terdaftar</dt><dd class="text-lg font-black text-gray-900 dark:text-white"><?= angka_id($warga_terdaftar) ?></dd></div>
            <div class="rounded-lg bg-gray-50 px-2 py-1.5 dark:bg-white/5"><dt class="text-xs text-gray-500 dark:text-brand-muted">Tercocokkan SIMPERUM</dt><dd class="text-lg font-black text-gray-900 dark:text-white"><?= angka_id($tercocokkan_simperum) ?></dd></div>
            <div class="rounded-lg bg-gray-50 px-2 py-1.5 dark:bg-white/5"><dt class="text-xs text-gray-500 dark:text-brand-muted">Topik konsultasi</dt><dd class="text-lg font-black text-gray-900 dark:text-white"><?= angka_id($total_diskusi) ?></dd></div>
            <div class="rounded-lg bg-gray-50 px-2 py-1.5 dark:bg-white/5"><dt class="text-xs text-gray-500 dark:text-brand-muted">Data PSU</dt><dd class="text-lg font-black text-gray-900 dark:text-white"><?= angka_id($total_psu) ?></dd></div>
            <div class="rounded-lg bg-gray-50 px-2 py-1.5 dark:bg-white/5"><dt class="text-xs text-gray-500 dark:text-brand-muted">Dokumen Bank Data</dt><dd class="text-lg font-black text-gray-900 dark:text-white"><?= angka_id($total_bank_data) ?></dd></div>
            <div class="rounded-lg bg-gray-50 px-2 py-1.5 dark:bg-white/5"><dt class="text-xs text-gray-500 dark:text-brand-muted">Peringatan keamanan <?= (int) $pk['jam'] ?> jam</dt><dd class="text-lg font-black"><a href="<?= base_url('Admin_Audit?aksi=peringatan_keamanan') ?>" class="hover:underline <?= (int) $pk['tinggi'] > 0 ? 'text-red-600 dark:text-red-400' : $redup((int) $pk['total']) ?>" data-peringatan-keamanan><?= angka_id($pk['total']) ?></a></dd></div>
        </dl>
    </aside>
</div>
</div>
