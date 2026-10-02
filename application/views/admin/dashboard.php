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
$tautan = 'font-semibold text-blue-700 hover:underline dark:text-brand-primary';
$redup = fn($n) => $n > 0 ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-brand-muted/70';
$pk = $peringatan_keamanan;
?>
<?php $this->load->view('admin/components/judul_halaman', ['jh_deskripsi' => 'Jumlah pekerjaan per layanan. Klik angka untuk membuka daftar yang sudah tersaring.']); ?>

<div class="tumpuk-bagian">
<?php if ($antrean_tanpa_wilayah > 0): ?>
<div class="flex flex-wrap items-center gap-x-2 gap-y-1 rounded-xl border border-orange-200 bg-orange-50 px-4 py-2.5 text-sm text-orange-800 dark:border-orange-500/20 dark:bg-orange-500/10 dark:text-orange-300">
    <i class="ph ph-map-pin-area text-lg" aria-hidden="true"></i>
    <strong><?= angka_id($antrean_tanpa_wilayah) ?> antrean menunggu belum memiliki wilayah</strong>
    <span class="text-orange-700 dark:text-orange-400/80">dan tidak terlihat oleh admin kabupaten/kota mana pun.</span>
    <a href="<?= base_url('Admin?status=pending&tanpa_wilayah=1') ?>" class="ml-auto font-semibold underline">Lihat antrean</a>
</div>
<?php endif; ?>

<section aria-label="Pekerjaan per layanan">
    <div class="deret-kartu grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 grid-kartu" style="--jumlah-kartu: <?= max(1, $jumlah_kartu) ?>">
        <?php foreach ($kartu_domain as $k): $u = $k['utama']; ?>
        <article class="kartu-admin isi-kartu flex flex-col">
            <h2 class="flex items-center gap-2 text-sm font-semibold text-gray-600 dark:text-brand-muted">
                <i class="ph <?= html_escape($k['icon']) ?> text-lg text-blue-600 dark:text-brand-primary" aria-hidden="true"></i><?= html_escape($k['label']) ?>
            </h2>
            <a href="<?= html_escape(base_url($u['url'])) ?>" class="mt-1 flex items-baseline gap-2 hover:underline">
                <span class="text-2xl font-black leading-tight <?= $redup($u['n']) ?>"><?= angka_id($u['n']) ?></span>
                <span class="text-xs font-semibold <?= $u['n'] > 0 ? 'text-amber-700 dark:text-amber-400' : 'text-gray-400 dark:text-brand-muted/70' ?>"><?= html_escape(strtolower($u['label'])) ?></span>
            </a>
            <ul class="mt-2 space-y-0.5 text-xs">
                <?php foreach ($k['rincian'] as $r): ?>
                <li><a href="<?= html_escape(base_url($r['url'])) ?>" class="flex justify-between gap-2 text-gray-500 hover:underline dark:text-brand-muted"><span><?= html_escape($r['label']) ?></span><span class="font-bold <?= $redup($r['n']) ?>"><?= angka_id($r['n']) ?></span></a></li>
                <?php endforeach; ?>
            </ul>
            <a href="<?= html_escape(base_url($k['url'])) ?>" class="mt-auto pt-2 text-xs <?= $tautan ?>"><?= html_escape($k['lihat']) ?></a>
        </article>
        <?php endforeach; ?>

        <?php if ($rekam): $masuk = array_sum(array_column($rekam['domain'], 'masuk')); ?>
        <article class="kartu-admin isi-kartu flex flex-col">
            <h2 class="flex items-center gap-2 text-sm font-semibold text-gray-600 dark:text-brand-muted">
                <i class="ph ph-chart-line-up text-lg text-blue-600 dark:text-brand-primary" aria-hidden="true"></i>Rekam Data
            </h2>
            <p class="mt-1 flex items-baseline gap-2">
                <span class="text-2xl font-black leading-tight <?= $redup($masuk) ?>"><?= angka_id($masuk) ?></span>
                <span class="text-xs font-semibold text-gray-500 dark:text-brand-muted">terkirim, triwulan <?= (int) $rekam['triwulan'] ?> tahun <?= (int) $rekam['tahun'] ?></span>
            </p>
            <ul class="mt-2 space-y-0.5 text-xs text-gray-500 dark:text-brand-muted">
                <?php foreach ($rekam['domain'] as $d): ?>
                <li class="flex justify-between gap-2"><span><?= html_escape($d['label']) ?></span><span class="font-bold <?= $redup($d['masuk']) ?>"><?= angka_id($d['masuk']) ?> dari <?= angka_id($d['total']) ?></span></li>
                <?php endforeach; ?>
                <li class="flex justify-between gap-2"><span>Diterima</span><span class="font-bold <?= $redup($rekam['diterima']) ?>"><?= angka_id($rekam['diterima']) ?></span></li>
                <li class="flex justify-between gap-2"><span>Perlu perbaikan</span><span class="font-bold <?= $redup($rekam['perbaikan']) ?>"><?= angka_id($rekam['perbaikan']) ?></span></li>
            </ul>
            <a href="<?= base_url('Admin_Rekam_Data?tahun=' . (int) $rekam['tahun'] . '&triwulan=' . (int) $rekam['triwulan']) ?>" class="mt-auto pt-2 text-xs <?= $tautan ?>">Lihat laporan</a>
        </article>
        <?php endif; ?>
    </div>
</section>

<div class="grid grid-cols-1 grid-kartu xl:grid-cols-3">
    <section class="kartu-admin xl:col-span-2">
        <div class="border-b border-gray-100 px-4 py-3 dark:border-white/5">
            <h2 class="text-base font-bold text-gray-900 dark:text-white">Pengajuan terbaru</h2>
            <p class="text-xs text-gray-500 dark:text-brand-muted">Enam pengajuan paling baru dari seluruh layanan.</p>
        </div>
        <?php if (empty($aktivitas)): ?>
            <p class="isi-kartu text-sm text-gray-500 dark:text-brand-muted">Belum ada pengajuan yang masuk.</p>
        <?php else: ?>
        <ul class="divide-y divide-gray-100 dark:divide-white/5">
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
