<div class="mb-8 flex flex-col md:flex-row md:items-end justify-between gap-4">
    <div>
        <h2 class="text-3xl font-black text-gray-900 dark:text-white tracking-tight mb-2">Dashboard Overview</h2>
        <p class="text-sm text-gray-500 dark:text-brand-muted">Ringkasan aktivitas dan metrik sistem KlinikPKP saat ini.</p>
    </div>
    <div class="flex items-center gap-3">
        <button class="px-4 py-2 bg-white dark:bg-brand-card text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-white/10 rounded-xl text-sm font-semibold hover:bg-gray-50 dark:hover:bg-white/5 transition-colors shadow-sm flex items-center gap-2">
            <i class="ph ph-calendar-blank text-lg text-gray-400 dark:text-brand-muted"></i> Hari Ini
            <i class="ph ph-caret-down text-gray-400 ml-1"></i>
        </button>
        <button class="px-4 py-2 bg-blue-600 dark:bg-brand-primary text-white dark:text-brand-dark border border-transparent rounded-xl text-sm font-bold hover:bg-blue-700 dark:hover:bg-brand-hover transition-colors shadow-sm shadow-blue-500/30 dark:shadow-brand-primary/20 flex items-center gap-2">
            <i class="ph ph-download-simple text-lg"></i> Unduh Laporan
        </button>
    </div>
</div>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    
    <!-- Stat 1: Total Users -->
    <div class="bg-white dark:bg-brand-card rounded-2xl p-6 border border-gray-200 dark:border-white/5 shadow-sm relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="ph ph-users text-6xl text-blue-500 dark:text-brand-primary"></i>
        </div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-brand-primary/10 flex items-center justify-center text-blue-600 dark:text-brand-primary">
                    <i class="ph ph-users text-xl"></i>
                </div>
                <h3 class="text-sm font-semibold text-gray-600 dark:text-brand-muted">Total Pengguna</h3>
            </div>
            <div class="flex items-end gap-3">
                <div class="text-3xl font-black text-gray-900 dark:text-white leading-none"><?= number_format($stats['total_users']) ?></div>
                <div class="flex items-center text-xs font-semibold text-green-600 dark:text-green-400 mb-1">
                    <i class="ph ph-trend-up mr-1"></i> +12%
                </div>
            </div>
        </div>
    </div>
    
    <!-- Stat 2: Antrean Pending -->
    <div class="bg-white dark:bg-brand-card rounded-2xl p-6 border border-gray-200 dark:border-white/5 shadow-sm relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="ph ph-clock text-6xl text-yellow-500"></i>
        </div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-yellow-50 dark:bg-yellow-500/10 flex items-center justify-center text-yellow-600 dark:text-yellow-400">
                    <i class="ph ph-clock text-xl"></i>
                </div>
                <h3 class="text-sm font-semibold text-gray-600 dark:text-brand-muted">Antrean Validasi</h3>
            </div>
            <div class="flex items-end gap-3">
                <div class="text-3xl font-black text-gray-900 dark:text-white leading-none"><?= number_format($stats['total_antrean']) ?></div>
                <div class="flex items-center text-xs font-semibold text-red-600 dark:text-red-400 mb-1">
                    <i class="ph ph-warning-circle mr-1"></i> Butuh proses
                </div>
            </div>
        </div>
    </div>

    <!-- Stat 3: Total Konten/Berita -->
    <div class="bg-white dark:bg-brand-card rounded-2xl p-6 border border-gray-200 dark:border-white/5 shadow-sm relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="ph ph-article text-6xl text-purple-500"></i>
        </div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-500/10 flex items-center justify-center text-purple-600 dark:text-purple-400">
                    <i class="ph ph-article text-xl"></i>
                </div>
                <h3 class="text-sm font-semibold text-gray-600 dark:text-brand-muted">Publikasi Aktif</h3>
            </div>
            <div class="flex items-end gap-3">
                <div class="text-3xl font-black text-gray-900 dark:text-white leading-none">24</div>
                <div class="flex items-center text-xs font-semibold text-green-600 dark:text-green-400 mb-1">
                    <i class="ph ph-trend-up mr-1"></i> +3 mgg ini
                </div>
            </div>
        </div>
    </div>

    <!-- Stat 4: Topik Forum -->
    <div class="bg-white dark:bg-brand-card rounded-2xl p-6 border border-gray-200 dark:border-white/5 shadow-sm relative overflow-hidden group">
        <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
            <i class="ph ph-chats-circle text-6xl text-indigo-500 dark:text-indigo-400"></i>
        </div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-500/10 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                    <i class="ph ph-chats-circle text-xl"></i>
                </div>
                <h3 class="text-sm font-semibold text-gray-600 dark:text-brand-muted">Topik Diskusi</h3>
            </div>
            <div class="flex items-end gap-3">
                <div class="text-3xl font-black text-gray-900 dark:text-white leading-none"><?= number_format($stats['total_diskusi']) ?></div>
                <div class="flex items-center text-xs font-semibold text-gray-500 dark:text-brand-muted mb-1">
                    Aktif di forum
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Main Content Grid -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    
    <!-- Chart Section (Spans 2 columns) -->
    <div class="lg:col-span-2 bg-white dark:bg-brand-card rounded-3xl p-6 border border-gray-200 dark:border-white/5 shadow-sm">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Statistik Kunjungan & Pengajuan</h3>
                <p class="text-xs text-gray-500 dark:text-brand-muted mt-1">Data 7 hari terakhir</p>
            </div>
            <button class="w-8 h-8 rounded-lg bg-gray-50 dark:bg-white/5 text-gray-500 dark:text-brand-muted hover:text-gray-900 dark:hover:text-white flex items-center justify-center transition-colors">
                <i class="ph ph-dots-three-outline-vertical text-lg"></i>
            </button>
        </div>
        
        <!-- Placeholder for Chart -->
        <div class="h-72 w-full flex items-center justify-center bg-gray-50 dark:bg-[#0a1a1f] rounded-2xl border border-dashed border-gray-200 dark:border-white/10 relative">
            <!-- Simulated Chart using Canvas -->
            <canvas id="overviewChart" class="w-full h-full"></canvas>
        </div>
    </div>

    <!-- Right Column: Recent Activity & Quick Actions -->
    <div class="space-y-6">
        
        <!-- Quick Actions -->
        <div class="bg-gradient-to-br from-[#0f2933] to-[#0a1a1f] rounded-3xl p-6 border border-white/10 shadow-lg relative overflow-hidden">
            <!-- decorative circles -->
            <div class="absolute top-[-20%] right-[-10%] w-32 h-32 bg-brand-primary/20 rounded-full blur-2xl pointer-events-none"></div>
            
            <h3 class="text-white font-bold mb-4 relative z-10 flex items-center gap-2">
                <i class="ph ph-lightning text-brand-primary"></i> Jalan Pintas
            </h3>
            
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

        <!-- Recent Activity Feed -->
        <div class="bg-white dark:bg-brand-card rounded-3xl p-6 border border-gray-200 dark:border-white/5 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Aktivitas Terkini</h3>
                <a href="#" class="text-xs font-semibold text-blue-600 dark:text-brand-primary hover:underline">Lihat Semua</a>
            </div>
            
            <div class="space-y-5">
                <!-- Activity Item 1 -->
                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-full bg-green-50 dark:bg-green-500/10 flex items-center justify-center text-green-600 dark:text-green-400 shrink-0">
                        <i class="ph ph-check-circle text-lg"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Pengajuan disetujui</p>
                        <p class="text-xs text-gray-500 dark:text-brand-muted mt-0.5">Budi Santoso telah disetujui untuk program bantuan.</p>
                        <p class="text-[10px] font-medium text-gray-400 dark:text-brand-muted/70 mt-1">2 jam yang lalu</p>
                    </div>
                </div>
                
                <!-- Activity Item 2 -->
                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-brand-primary/10 flex items-center justify-center text-blue-600 dark:text-brand-primary shrink-0">
                        <i class="ph ph-user-plus text-lg"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Pengguna Baru mendaftar</p>
                        <p class="text-xs text-gray-500 dark:text-brand-muted mt-0.5">Siti Aminah mendaftar sebagai Masyarakat.</p>
                        <p class="text-[10px] font-medium text-gray-400 dark:text-brand-muted/70 mt-1">5 jam yang lalu</p>
                    </div>
                </div>

                <!-- Activity Item 3 -->
                <div class="flex gap-4">
                    <div class="w-10 h-10 rounded-full bg-purple-50 dark:bg-purple-500/10 flex items-center justify-center text-purple-600 dark:text-purple-400 shrink-0">
                        <i class="ph ph-article text-lg"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">Artikel Dipublikasikan</p>
                        <p class="text-xs text-gray-500 dark:text-brand-muted mt-0.5">"Program Bantuan Rumah Swadaya 2026" telah terbit.</p>
                        <p class="text-[10px] font-medium text-gray-400 dark:text-brand-muted/70 mt-1">Kemarin</p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    // Initialize Chart.js
    document.addEventListener('alpine:init', () => {
        setTimeout(() => {
            const ctx = document.getElementById('overviewChart');
            if(ctx) {
                // Get current theme from Alpine or document class
                const isDark = document.documentElement.classList.contains('dark');
                
                const textColor = isDark ? '#8aacb0' : '#64748b';
                const gridColor = isDark ? 'rgba(255,255,255,0.05)' : 'rgba(0,0,0,0.05)';
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'],
                        datasets: [
                            {
                                label: 'Pengunjung',
                                data: [65, 59, 80, 81, 56, 55, 40],
                                borderColor: isDark ? '#d6fb00' : '#2563eb',
                                backgroundColor: isDark ? 'rgba(214,251,0,0.1)' : 'rgba(37,99,235,0.1)',
                                borderWidth: 3,
                                tension: 0.4,
                                fill: true,
                                pointBackgroundColor: isDark ? '#d6fb00' : '#2563eb',
                            },
                            {
                                label: 'Pengajuan',
                                data: [28, 48, 40, 19, 86, 27, 90],
                                borderColor: isDark ? '#0ea5e9' : '#0ea5e9',
                                backgroundColor: isDark ? 'rgba(14,165,233,0.1)' : 'rgba(14,165,233,0.1)',
                                borderWidth: 3,
                                tension: 0.4,
                                fill: true,
                                pointBackgroundColor: isDark ? '#0ea5e9' : '#0ea5e9',
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    color: textColor,
                                    font: {
                                        family: "'Plus Jakarta Sans', sans-serif",
                                        weight: '600'
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: gridColor,
                                    drawBorder: false,
                                },
                                ticks: {
                                    color: textColor,
                                    font: { family: "'Plus Jakarta Sans', sans-serif" }
                                }
                            },
                            x: {
                                grid: {
                                    display: false,
                                    drawBorder: false,
                                },
                                ticks: {
                                    color: textColor,
                                    font: { family: "'Plus Jakarta Sans', sans-serif" }
                                }
                            }
                        }
                    }
                });
            }
        }, 100);
    });
</script>
