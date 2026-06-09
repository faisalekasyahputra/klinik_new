<!-- application/views/pages/sikaper/index.php -->
<main class="min-h-screen pt-24 pb-16  relative overflow-hidden font-outfit">
    <!-- Background Ornaments -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <!-- Batik Pattern Overlay -->
        
        
        <div class="absolute top-[-10%] right-[-5%] w-[600px] h-[600px] rounded-full bg-[#d6fb00]/5 blur-[120px]"></div>
        <div class="absolute bottom-[-10%] left-[-10%] w-[500px] h-[500px] rounded-full bg-[#00a3b5]/5 blur-[100px]"></div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <!-- Header -->
        <div class="text-center mb-16 animate-fade-in-up">
            <h1 class="text-4xl md:text-5xl font-extrabold text-white tracking-tight mb-4">
                Integrasi <span class="text-[#d6fb00] drop-shadow-[0_0_15px_rgba(214,251,0,0.3)]">Si-KAPER</span> API
            </h1>
            <p class="text-lg text-zinc-400 max-w-2xl mx-auto">
                Sistem Informasi Kawasan Permukiman Provinsi Jawa Tengah
            </p>
        </div>

        <?php 
        // Helper function to render JSON nicely
        function render_api_card($title, $icon, $color, $api_response) {
            $status_class = $api_response['status'] ? 'text-green-400' : 'text-red-400';
            $status_icon = $api_response['status'] ? 'fa-check-circle' : 'fa-exclamation-circle';
            ?>
            <div class="bg-[#0a1a1f] border border-[<?= $color ?>]/20 rounded-3xl p-6 relative overflow-hidden shadow-lg group hover:border-[<?= $color ?>]/50 transition-all duration-300">
                <i class="<?= $icon ?> absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 160px; right: -1.5rem; bottom: -1.5rem; color: <?= $color ?>; opacity: 0.05;"></i>
                
                <div class="flex items-center gap-3 mb-6 relative z-10">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background-color: <?= $color ?>15; color: <?= $color ?>;">
                        <i class="<?= $icon ?>"></i>
                    </div>
                    <div>
                        <h3 class="text-white font-bold text-lg"><?= $title ?></h3>
                        <div class="text-xs <?= $status_class ?> flex items-center gap-1 mt-0.5 font-medium">
                            <i class="fa-solid <?= $status_icon ?>"></i>
                            <?= $api_response['status'] ? 'Connected (HTTP '.$api_response['http_code'].')' : 'Error / Disconnected' ?>
                        </div>
                    </div>
                </div>

                <div class="relative z-10  rounded-xl border border-white/5 p-4 overflow-x-auto max-h-[300px] overflow-y-auto custom-scrollbar">
                    <?php if(!$api_response['status']): ?>
                        <div class="text-red-400 text-sm mb-4 font-mono font-bold"><?= $api_response['message'] ?? 'Connection Failed' ?></div>
                        
                        <?php if (strpos($api_response['message'], 'Could not resolve host') !== false || strpos($api_response['message'], 'cURL Error') !== false || strpos($api_response['message'], 'API Error (HTTP 404)') !== false): ?>
                        <div class="bg-red-500/10 border border-red-500/20 rounded-xl p-4 mb-4 font-sans">
                            <p class="text-red-300 text-xs leading-relaxed italic">
                                "Mohon maaf, untuk dokumentasi Postman Sikaper-nya sudah saya pelajari dan integrasinya sudah saya siapkan. Namun, boleh minta informasi Base URL / Host API-nya? Karena di variabel Postman-nya masih kosong, sehingga saya belum bisa melakukan tembakan data."
                            </p>
                            <p class="text-red-400/50 text-[10px] mt-3">
                                *Copy pesan di atas dan kirimkan ke tim penyedia API. Jika sudah dapat URL-nya, masukkan ke: <code class="bg-black/50 px-1 py-0.5 rounded border border-white/10 text-zinc-400">application/config/sikaper.php</code>
                            </p>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    <pre class="text-zinc-300 text-xs font-mono leading-relaxed"><?= json_encode($api_response['data'], JSON_PRETTY_PRINT) ?></pre>
                </div>
            </div>
            <?php
        }
        ?>

        <!-- Bento Grid for API Endpoints -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 animate-fade-in-up" style="animation-delay: 0.2s;">
            <!-- Info Hari Habitat -->
            <?= render_api_card('Info Hari Habitat', 'fa-solid fa-leaf', '#6bcb77', $info_habitat) ?>
            
            <!-- Jadwal Hari Habitat -->
            <?= render_api_card('Jadwal Hari Habitat', 'fa-solid fa-calendar-check', '#4facfe', $jadwal_habitat) ?>

            <!-- Data Kawasan Per Tahun -->
            <?= render_api_card('Data Kawasan (2024)', 'fa-solid fa-city', '#c084fc', $data_kawasan) ?>

            <!-- Detail Per ID Kawasan -->
            <?= render_api_card('Detail Kawasan (MzU4)', 'fa-solid fa-map-location-dot', '#fbc2eb', $detail_kawasan) ?>

            <!-- Data RTRW -->
            <div class="lg:col-span-2">
                <?= render_api_card('Data RTRW per Kawasan', 'fa-solid fa-road', '#ff9a9e', $data_rtrw) ?>
            </div>
        </div>
        
    </div>
</main>

<style>
/* Custom Scrollbar for API JSON Pre boxes */
.custom-scrollbar::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.02);
    border-radius: 8px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: rgba(214, 251, 0, 0.3);
}
</style>
