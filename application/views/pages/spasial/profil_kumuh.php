<style>
  .dark-bento-container {
    background-color: rgba(15, 42, 48, 0.4);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid rgba(214, 251, 0, 0.08);
    border-radius: 24px;
  }
  .map-dummy-bg {
    background-image: radial-gradient(rgba(255, 217, 61, 0.1) 1px, transparent 1px);
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
                            <span class="text-[#d6fb00]">Profil Kawasan Kumuh</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    
    <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-black text-white tracking-tight mb-2">Profil Kawasan Kumuh</h1>
        <p class="text-[#8aacb0]">Pemetaan profil dan pendataan delineasi luas kawasan kumuh di Provinsi Jawa Tengah (Data Simulasi).</p>
    </div>

    <!-- Map & List Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        
        <!-- Interactive Map Mockup -->
        <div class="dark-bento-container p-4 lg:col-span-2 min-h-[500px] flex flex-col">
            <div class="flex justify-between items-center mb-4 px-2">
                <h3 class="text-white font-bold"><i class="fa-solid fa-map-location-dot text-[#ffd93d] mr-2"></i> Peta Spasial Delineasi Kumuh</h3>
                <div class="flex gap-2">
                    <button class="bg-[#ffd93d]/10 text-[#ffd93d] px-3 py-1 text-xs rounded-lg border border-[#ffd93d]/20 hover:bg-[#ffd93d]/20 transition-colors">Semua</button>
                    <button class="bg-[#0f2a30] text-[#8aacb0] px-3 py-1 text-xs rounded-lg border border-white/10 hover:text-white transition-colors">Berat</button>
                    <button class="bg-[#0f2a30] text-[#8aacb0] px-3 py-1 text-xs rounded-lg border border-white/10 hover:text-white transition-colors">Ringan</button>
                </div>
            </div>
            <!-- Actual Leaflet Map Canvas -->
            <div id="map_kumuh" class="flex-1 bg-[#050d10] rounded-xl border border-white/5 relative z-0 min-h-[400px]"></div>
        </div>

        <!-- Table List Mockup -->
        <div class="dark-bento-container p-6 flex flex-col">
            <h3 class="text-white font-bold mb-4">10 Kab/Kota Terluas</h3>
            <div class="flex-1 overflow-y-auto pr-2" style="max-height: 440px;">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-[#8aacb0] border-b border-white/10">
                            <th class="pb-3 font-medium">Kabupaten/Kota</th>
                            <th class="pb-3 font-medium text-right">Luas (Ha)</th>
                        </tr>
                    </thead>
                    <tbody class="text-white">
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kota Semarang</td>
                            <td class="py-3 text-right text-[#ff6b6b] font-semibold">415.6</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kab. Brebes</td>
                            <td class="py-3 text-right text-[#ff6b6b] font-semibold">390.2</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kota Surakarta</td>
                            <td class="py-3 text-right text-[#ffd93d] font-semibold">280.4</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kab. Pemalang</td>
                            <td class="py-3 text-right text-[#ffd93d] font-semibold">250.1</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kota Tegal</td>
                            <td class="py-3 text-right text-[#ffd93d] font-semibold">210.8</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kab. Cilacap</td>
                            <td class="py-3 text-right text-white font-semibold">180.5</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kab. Banyumas</td>
                            <td class="py-3 text-right text-white font-semibold">165.2</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kab. Demak</td>
                            <td class="py-3 text-right text-white font-semibold">140.0</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kota Pekalongan</td>
                            <td class="py-3 text-right text-white font-semibold">120.3</td>
                        </tr>
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="py-3">Kab. Kudus</td>
                            <td class="py-3 text-right text-[#6bcb77] font-semibold">80.5</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="dark-bento-container p-6 relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-[#ffd93d] opacity-5 text-8xl"><i class="fa-solid fa-vector-square"></i></div>
            <p class="text-xs text-[#8aacb0] uppercase tracking-wider font-bold mb-2">Total Luasan Kumuh</p>
            <h3 class="text-3xl font-black text-white">4,250.8 <span class="text-xs font-medium text-zinc-500">Hektar</span></h3>
            <div class="mt-2 text-[10px] text-[#6bcb77] font-semibold"><i class="fa-solid fa-arrow-trend-down"></i> Berkurang 150 Ha (2025)</div>
        </div>
        <div class="dark-bento-container p-6 relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-[#ffd93d] opacity-5 text-8xl"><i class="fa-solid fa-city"></i></div>
            <p class="text-xs text-[#8aacb0] uppercase tracking-wider font-bold mb-2">Lokasi Terdata</p>
            <h3 class="text-3xl font-black text-[#ffd93d]">1,102 <span class="text-xs font-medium text-[#ffd93d]/50">Titik Kawasan</span></h3>
            <div class="mt-2 text-[10px] text-[#8aacb0]">Tersebar di 35 Kab/Kota</div>
        </div>
        <div class="dark-bento-container p-6 relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-[#ffd93d] opacity-5 text-8xl"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <p class="text-xs text-[#8aacb0] uppercase tracking-wider font-bold mb-2">Kumuh Berat</p>
            <h3 class="text-3xl font-black text-[#ff6b6b]">18.5 <span class="text-xs font-medium text-[#ff6b6b]/50">%</span></h3>
            <div class="w-full bg-black/40 h-1.5 rounded-full mt-3 overflow-hidden">
                <div class="bg-[#ff6b6b] h-full rounded-full" style="width: 18.5%"></div>
            </div>
        </div>
        <div class="dark-bento-container p-6 relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-[#ffd93d] opacity-5 text-8xl"><i class="fa-solid fa-hands-holding-circle"></i></div>
            <p class="text-xs text-[#8aacb0] uppercase tracking-wider font-bold mb-2">Progress Penanganan</p>
            <h3 class="text-3xl font-black text-[#6bcb77]">42 <span class="text-xs font-medium text-[#6bcb77]/50">Kawasan</span></h3>
            <div class="mt-2 text-[10px] text-[#8aacb0]">Sedang direvitalisasi TA 2025</div>
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

        var map = L.map('map_kumuh', {
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

        // Dummy Data Kumuh (Polygon & Circle)
        L.polygon([
            [-6.95, 110.40],
            [-6.96, 110.42],
            [-6.97, 110.41],
            [-6.955, 110.39]
        ], {color: '#ff6b6b', fillColor: '#ff6b6b', fillOpacity: 0.5, weight: 2}).addTo(map).bindPopup("<div class='text-zinc-900 font-bold'>Kumuh Berat: Kota Semarang</div>");
        
        L.circle([-6.87, 109.05], {
            color: '#ff6b6b', fillColor: '#ff6b6b', fillOpacity: 0.5, radius: 4500, weight: 2
        }).addTo(map).bindPopup("<div class='text-zinc-900 font-bold'>Kumuh Berat: Kab. Brebes</div>");
        
        L.circle([-7.56, 110.82], {
            color: '#ffd93d', fillColor: '#ffd93d', fillOpacity: 0.5, radius: 3500, weight: 2
        }).addTo(map).bindPopup("<div class='text-zinc-900 font-bold'>Kumuh Ringan: Kota Surakarta</div>");

        L.circle([-6.89, 109.38], {
            color: '#ffd93d', fillColor: '#ffd93d', fillOpacity: 0.5, radius: 4000, weight: 2
        }).addTo(map).bindPopup("<div class='text-zinc-900 font-bold'>Kumuh Ringan: Kab. Pemalang</div>");

        setTimeout(function() { map.invalidateSize(); }, 500);
    });
</script>
