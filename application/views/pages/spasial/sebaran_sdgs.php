<style>
  .dark-bento-container {
    background-color: rgba(15, 42, 48, 0.4);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid rgba(214, 251, 0, 0.08);
    border-radius: 24px;
  }
  .map-dummy-bg {
    background-image: radial-gradient(rgba(192, 132, 252, 0.1) 1px, transparent 1px);
    background-size: 20px 20px;
  }
</style>

<section class="w-full  pt-24 pb-16 px-4 sm:px-6 lg:px-8 relative min-h-screen font-outfit">
    <!-- Background Ornaments -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-[#d6fb00]/5 blur-[120px] rounded-full"></div>
    </div>

    <div class="max-w-7xl mx-auto relative z-10">
        <!-- Breadcrumb -->
        <div class="mb-10">
            <nav class="flex text-[10px] sm:text-xs text-zinc-500 font-bold uppercase tracking-widest" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2">
                    <li class="inline-flex items-center">
                        <a href="<?= base_url() ?>" class="hover:text-[#d6fb00] transition-colors"><i class="fa-solid fa-house mr-2"></i>Beranda</a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fa-solid fa-chevron-right text-[8px] mx-2"></i>
                            <span class="text-zinc-500">Data & Spasial</span>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fa-solid fa-chevron-right text-[8px] mx-2"></i>
                            <span class="text-[#d6fb00]">Sebaran Bantuan SDGS</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    
    <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-black text-white tracking-tight mb-2">Sebaran Bantuan SDGs</h1>
        <p class="text-[#8aacb0]">Pemetaan penyaluran Dana Alokasi Khusus (DAK) dan program Sustainable Development Goals perumahan (Data Simulasi).</p>
    </div>

    <!-- Map & List Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        
        <!-- Interactive Map Mockup -->
        <div class="dark-bento-container p-4 lg:col-span-2 min-h-[500px] flex flex-col">
            <div class="flex justify-between items-center mb-4 px-2">
                <h3 class="text-white font-bold"><i class="fa-solid fa-map-location-dot text-[#c084fc] mr-2"></i> Peta Realisasi Bantuan SDGs</h3>
                <div class="flex gap-2">
                    <button class="bg-[#c084fc]/10 text-[#c084fc] px-3 py-1 text-xs rounded-lg border border-[#c084fc]/20 hover:bg-[#c084fc]/20 transition-colors">Semua</button>
                    <button class="bg-[#0f2a30] text-[#8aacb0] px-3 py-1 text-xs rounded-lg border border-white/10 hover:text-white transition-colors">Tersalurkan</button>
                    <button class="bg-[#0f2a30] text-[#8aacb0] px-3 py-1 text-xs rounded-lg border border-white/10 hover:text-white transition-colors">Proses</button>
                </div>
            </div>
            <!-- Actual Leaflet Map Canvas -->
            <div id="map_sdgs" class="flex-1 bg-[#050d10] rounded-xl border border-white/5 relative z-0 min-h-[400px]"></div>
        </div>

        <!-- Table List Mockup -->
        <div class="dark-bento-container p-6 flex flex-col">
            <h3 class="text-white font-bold mb-4">10 Kab/Kota Penerima Terbanyak</h3>
            <div class="flex-1 overflow-y-auto pr-2" style="max-height: 440px;">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-[#8aacb0] border-b border-white/10">
                            <th class="pb-3 font-medium">Kabupaten/Kota</th>
                            <th class="pb-3 font-medium text-right">Nilai (M)</th>
                        </tr>
                    </thead>
                    <tbody class="text-white">
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kab. Brebes</td>
                            <td class="py-3 text-right text-[#c084fc] font-semibold">120.5</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kab. Banyumas</td>
                            <td class="py-3 text-right text-[#c084fc] font-semibold">98.2</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kab. Tegal</td>
                            <td class="py-3 text-right text-[#ffd93d] font-semibold">85.0</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kab. Cilacap</td>
                            <td class="py-3 text-right text-[#ffd93d] font-semibold">78.4</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kab. Pemalang</td>
                            <td class="py-3 text-right text-[#ffd93d] font-semibold">72.1</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kab. Grobogan</td>
                            <td class="py-3 text-right text-white font-semibold">65.8</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kab. Demak</td>
                            <td class="py-3 text-right text-white font-semibold">58.3</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kab. Pati</td>
                            <td class="py-3 text-right text-white font-semibold">45.0</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kota Semarang</td>
                            <td class="py-3 text-right text-[#6bcb77] font-semibold">22.4</td>
                        </tr>
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="py-3">Kota Surakarta</td>
                            <td class="py-3 text-right text-[#6bcb77] font-semibold">18.5</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="dark-bento-container p-6 relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-[#c084fc] opacity-5 text-8xl"><i class="fa-solid fa-coins"></i></div>
            <p class="text-xs text-[#8aacb0] uppercase tracking-wider font-bold mb-2">Total Dana Tersalurkan</p>
            <h3 class="text-3xl font-black text-white">1.2 <span class="text-xs font-medium text-zinc-500">Triliun</span></h3>
            <div class="mt-2 text-[10px] text-[#6bcb77] font-semibold"><i class="fa-solid fa-arrow-trend-up"></i> +8.5% dari TA 2024</div>
        </div>
        <div class="dark-bento-container p-6 relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-[#c084fc] opacity-5 text-8xl"><i class="fa-solid fa-users"></i></div>
            <p class="text-xs text-[#8aacb0] uppercase tracking-wider font-bold mb-2">Keluarga Penerima Manfaat</p>
            <h3 class="text-3xl font-black text-[#c084fc]">142,500 <span class="text-xs font-medium text-[#c084fc]/50">KK</span></h3>
            <div class="mt-2 text-[10px] text-[#8aacb0]">Tersebar di 28 Kab/Kota</div>
        </div>
        <div class="dark-bento-container p-6 relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-[#c084fc] opacity-5 text-8xl"><i class="fa-solid fa-clipboard-check"></i></div>
            <p class="text-xs text-[#8aacb0] uppercase tracking-wider font-bold mb-2">Capaian Target Tahunan</p>
            <h3 class="text-3xl font-black text-[#6bcb77]">85 <span class="text-xs font-medium text-[#6bcb77]/50">%</span></h3>
            <div class="w-full bg-black/40 h-1.5 rounded-full mt-3 overflow-hidden">
                <div class="bg-[#6bcb77] h-full rounded-full" style="width: 85%"></div>
            </div>
        </div>
        <div class="dark-bento-container p-6 relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-[#c084fc] opacity-5 text-8xl"><i class="fa-solid fa-hand-holding-hand"></i></div>
            <p class="text-xs text-[#8aacb0] uppercase tracking-wider font-bold mb-2">Program Padat Karya</p>
            <h3 class="text-3xl font-black text-[#ffd93d]">4,210 <span class="text-xs font-medium text-zinc-500">Pekerja</span></h3>
            <div class="mt-2 text-[10px] text-[#8aacb0]">Terserap dari warga lokal</div>
        </div>
    </div>
    </div>
</section>

<!-- Leaflet JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var satelit = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19, attribution: 'Tiles &copy; Esri' });
        var hybrid = L.tileLayer('http://mt0.google.com/vt/lyrs=y&hl=id&x={x}&y={y}&z={z}', { maxZoom: 19, attribution: '&copy; Google Maps' });
        var jalan = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OSM' });
        var gelap = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { maxZoom: 19, attribution: '&copy; CARTO' });

        var map = L.map('map_sdgs', {
            zoomControl: false,
            layers: [gelap] // Default map with labels
        }).setView([-7.1509, 110.1402], 8);

        var baseMaps = {
            "Mode Gelap (Labels)": gelap,
            "Satelit + Label": hybrid,
            "Satelit Murni": satelit,
            "Peta Jalan": jalan
        };

        L.control.layers(baseMaps, null, {position: 'topright'}).addTo(map);
        L.control.zoom({ position: 'bottomright' }).addTo(map);

        // Dummy Data SDGS (Bubbles)
        L.circle([-6.87, 109.05], {
            color: '#c084fc', fillColor: '#c084fc', fillOpacity: 0.6, radius: 6000, weight: 2
        }).addTo(map).bindPopup("<div class='text-zinc-900'><b class='text-sm'>Kab. Brebes</b><br><span class='font-bold text-[#c084fc]'>Bantuan SDGs: Rp 120.5M</span></div>");
        
        L.circle([-7.42, 109.23], {
            color: '#c084fc', fillColor: '#c084fc', fillOpacity: 0.6, radius: 5000, weight: 2
        }).addTo(map).bindPopup("<div class='text-zinc-900'><b class='text-sm'>Kab. Banyumas</b><br><span class='font-bold text-[#c084fc]'>Bantuan SDGs: Rp 98.2M</span></div>");

        L.circle([-6.98, 109.13], {
            color: '#ffd93d', fillColor: '#ffd93d', fillOpacity: 0.6, radius: 4500, weight: 2
        }).addTo(map).bindPopup("<div class='text-zinc-900'><b class='text-sm'>Kab. Tegal</b><br><span class='font-bold text-[#ffd93d]'>Bantuan SDGs: Rp 85.0M</span></div>");

        L.circle([-7.00, 110.43], {
            color: '#6bcb77', fillColor: '#6bcb77', fillOpacity: 0.6, radius: 3000, weight: 2
        }).addTo(map).bindPopup("<div class='text-zinc-900'><b class='text-sm'>Kota Semarang</b><br><span class='font-bold text-[#6bcb77]'>Bantuan SDGs: Rp 22.4M</span></div>");

        setTimeout(function() { map.invalidateSize(); }, 500);
    });
</script>
