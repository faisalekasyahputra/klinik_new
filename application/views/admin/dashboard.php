<?php
/**
 * Overview superadmin. Aturan halaman ini: TIDAK ADA ANGKA KARANGAN.
 * Versi lama mencampur data asli dengan "Publikasi Aktif = 24" hardcode,
 * chart berisi data dummy 7 hari, feed aktivitas HTML statis bernama orang
 * fiktif, dan beberapa tombol yang tidak melakukan apa-apa (Hari Ini, Unduh
 * Laporan, Lihat Semua). Semua itu dibuang — lihat B2 di anchor dashboard.
 * Kalau menambah metrik baru, pastikan sumbernya query nyata.
 */
$kelas_status = [
    'pending' => 'pending', 'approved' => 'ok', 'rejected' => 'reject',
    'Baru' => 'pending', 'Diproses' => 'process', 'Selesai' => 'ok',
    'Draft' => 'process', 'Pending' => 'pending', 'Diterima' => 'ok', 'Ditolak' => 'reject',
];
?>
<div class="mb-8">
    <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2">Dashboard Overview</h2>
    <p class="text-sm text-gray-500 dark:text-brand-muted">Ringkasan pengajuan yang masuk ke sistem, per <?= date('d M Y, H:i') ?>.</p>
</div>

<!-- Kartu per domain pengajuan — dibangun dari registry dashboard_modules -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <?php foreach ($kartu_domain as $k): ?>
    <a href="<?= base_url($k['url']) ?>" class="bg-white dark:bg-brand-card rounded-2xl p-6 border border-gray-200 dark:border-white/5 shadow-sm relative overflow-hidden group hover:border-blue-300 dark:hover:border-brand-primary/30 transition-colors">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="ph <?= html_escape($k['icon']) ?> text-6xl text-blue-500 dark:text-brand-primary"></i>
        </div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-brand-primary/10 flex items-center justify-center text-blue-600 dark:text-brand-primary">
                    <i class="ph <?= html_escape($k['icon']) ?> text-xl"></i>
                </div>
                <h3 class="text-sm font-semibold text-gray-600 dark:text-brand-muted"><?= html_escape($k['label']) ?></h3>
            </div>
            <div class="flex items-end gap-3">
                <div class="text-3xl font-black text-gray-900 dark:text-white leading-none"><?= number_format($k['pending']) ?></div>
                <div class="text-xs font-semibold mb-1 <?= $k['pending'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400 dark:text-brand-muted/70' ?>">
                    <?= $k['pending'] > 0 ? 'menunggu proses' : 'tidak ada antrean' ?>
                </div>
            </div>
            <div class="text-[11px] text-gray-400 dark:text-brand-muted/70 mt-2"><?= number_format($k['total']) ?> total sejak awal</div>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<!-- Metrik sistem lain -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white dark:bg-brand-card rounded-2xl p-5 border border-gray-200 dark:border-white/5 shadow-sm flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0"><i class="ph ph-users text-xl"></i></div>
        <div>
            <div class="text-xs text-gray-500 dark:text-brand-muted font-medium uppercase tracking-wider">Akun Terdaftar</div>
            <div class="text-2xl font-black text-gray-900 dark:text-white leading-tight"><?= number_format($total_users) ?></div>
        </div>
    </div>
    <div class="bg-white dark:bg-brand-card rounded-2xl p-5 border border-gray-200 dark:border-white/5 shadow-sm flex items-center gap-4">
        <div class="w-11 h-11 rounded-xl bg-purple-50 dark:bg-purple-500/10 flex items-center justify-center text-purple-600 dark:text-purple-400 shrink-0"><i class="ph ph-chats-circle text-xl"></i></div>
        <div>
            <div class="text-xs text-gray-500 dark:text-brand-muted font-medium uppercase tracking-wider">Topik Forum</div>
            <div class="text-2xl font-black text-gray-900 dark:text-white leading-tight"><?= number_format($total_diskusi) ?></div>
        </div>
    </div>
    <a href="<?= base_url('Admin') ?>" class="rounded-2xl p-5 border shadow-sm flex items-center gap-4 transition-colors <?= $antrean_tanpa_wilayah > 0 ? 'bg-orange-50 dark:bg-orange-500/10 border-orange-200 dark:border-orange-500/20 hover:border-orange-400' : 'bg-white dark:bg-brand-card border-gray-200 dark:border-white/5' ?>">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0 <?= $antrean_tanpa_wilayah > 0 ? 'bg-orange-500/10 text-orange-500' : 'bg-gray-100 dark:bg-white/5 text-gray-400' ?>"><i class="ph ph-map-pin-slash text-xl"></i></div>
        <div>
            <div class="text-xs font-medium uppercase tracking-wider <?= $antrean_tanpa_wilayah > 0 ? 'text-orange-700 dark:text-orange-400' : 'text-gray-500 dark:text-brand-muted' ?>">Antrean Tanpa Wilayah</div>
            <div class="text-2xl font-black leading-tight <?= $antrean_tanpa_wilayah > 0 ? 'text-orange-700 dark:text-orange-400' : 'text-gray-900 dark:text-white' ?>"><?= number_format($antrean_tanpa_wilayah) ?></div>
            <?php if ($antrean_tanpa_wilayah > 0): ?>
            <div class="text-[10px] text-orange-600 dark:text-orange-400/80 mt-0.5">Tidak terlihat admin kabupaten manapun</div>
            <?php endif; ?>
        </div>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Tren pengajuan antrean, data nyata 7 hari terakhir -->
    <div class="lg:col-span-2 bg-white dark:bg-brand-card rounded-3xl p-6 border border-gray-200 dark:border-white/5 shadow-sm">
        <div class="mb-6">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Pengajuan Antrean Masuk</h3>
            <p class="text-xs text-gray-500 dark:text-brand-muted mt-1">Jumlah pengajuan per hari, 7 hari terakhir</p>
        </div>
        <div class="h-72 w-full">
            <canvas id="overviewChart"></canvas>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-gradient-to-br from-[#0f2933] to-[#0a1a1f] rounded-3xl p-6 border border-white/10 shadow-lg relative overflow-hidden">
            <div class="absolute top-[-20%] right-[-10%] w-32 h-32 bg-brand-primary/20 rounded-full blur-2xl pointer-events-none"></div>
            <h3 class="text-white font-bold mb-4 relative z-10 flex items-center gap-2"><i class="ph ph-lightning text-brand-primary"></i> Jalan Pintas</h3>
            <div class="grid grid-cols-2 gap-3 relative z-10">
                <a href="<?= base_url('Admin_Content') ?>" class="flex flex-col items-center justify-center p-4 rounded-2xl bg-white/5 hover:bg-white/10 border border-white/5 hover:border-brand-primary/30 transition-all group">
                    <i class="ph ph-pencil-simple-line text-2xl text-brand-light mb-2 group-hover:scale-110 transition-transform"></i>
                    <span class="text-xs font-semibold text-gray-300">Tulis Berita</span>
                </a>
                <a href="<?= base_url('Admin') ?>" class="flex flex-col items-center justify-center p-4 rounded-2xl bg-brand-primary/10 hover:bg-brand-primary/20 border border-brand-primary/20 transition-all group">
                    <i class="ph ph-clipboard-text text-2xl text-brand-primary mb-2 group-hover:scale-110 transition-transform"></i>
                    <span class="text-xs font-semibold text-brand-light">Validasi</span>
                </a>
            </div>
        </div>

        <!-- Aktivitas nyata lintas domain -->
        <div class="bg-white dark:bg-brand-card rounded-3xl p-6 border border-gray-200 dark:border-white/5 shadow-sm">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-5">Pengajuan Terbaru</h3>
            <?php if (empty($aktivitas)): ?>
                <p class="text-sm text-gray-400 dark:text-brand-muted/70">Belum ada pengajuan yang masuk.</p>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($aktivitas as $a): ?>
                <div class="flex gap-3 items-start">
                    <div class="w-9 h-9 rounded-full bg-gray-100 dark:bg-white/5 flex items-center justify-center text-gray-500 dark:text-brand-muted shrink-0">
                        <i class="ph <?= html_escape($a['icon']) ?>"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-gray-400 dark:text-brand-muted/70"><?= html_escape($a['jenis']) ?></p>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate"><?= html_escape($a['judul'] ?: '—') ?></p>
                        <div class="flex items-center gap-2 mt-1">
                            <?= $this->load->view('admin/components/status_badge', ['label' => $a['status'], 'kelas' => $kelas_status[$a['status']] ?? 'pending'], TRUE) ?>
                            <span class="text-[10px] text-gray-400 dark:text-brand-muted/70"><?= html_escape(date('d M, H:i', strtotime($a['waktu']))) ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php // Chart.js dimuat DI SINI, bukan di head admin — satu-satunya halaman
      // yang memakainya; halaman admin lain tak perlu membayar ~200KB. ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1"></script>
<script>
(() => {
    // alpine:init hanya menembak sekali di load penuh. Saat konten di-swap
    // loader progresif, Alpine sudah ada — langsung init.
    if (window.Alpine) { initOverviewChart(); }
    else { document.addEventListener('alpine:init', () => initOverviewChart()); }

    function initOverviewChart() {
    setTimeout(() => {
        const ctx = document.getElementById('overviewChart');
        if (!ctx) return;
        const isDark = document.documentElement.classList.contains('dark');
        const textColor = isDark ? '#8aacb0' : '#64748b';
        const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($tren['label']) ?>,
                datasets: [{
                    label: 'Pengajuan antrean',
                    data: <?= json_encode($tren['data']) ?>,
                    borderColor: isDark ? '#d6fb00' : '#2563eb',
                    backgroundColor: isDark ? 'rgba(214,251,0,0.1)' : 'rgba(37,99,235,0.1)',
                    borderWidth: 3, tension: 0.4, fill: true,
                    pointBackgroundColor: isDark ? '#d6fb00' : '#2563eb',
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { color: textColor, font: { family: "'Plus Jakarta Sans', sans-serif", weight: '600' } } } },
                scales: {
                    y: { beginAtZero: true, ticks: { color: textColor, precision: 0, font: { family: "'Plus Jakarta Sans', sans-serif" } }, grid: { color: gridColor, drawBorder: false } },
                    x: { ticks: { color: textColor, font: { family: "'Plus Jakarta Sans', sans-serif" } }, grid: { display: false, drawBorder: false } }
                }
            }
        });
    }, 100);
    }
})();
</script>
