<style>
  .dark-bento-container {
    background-color: rgba(15, 42, 48, 0.4);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid rgba(214, 251, 0, 0.08);
    border-radius: 24px;
  }
  .map-dummy-bg {
    background-image: radial-gradient(rgba(0, 163, 181, 0.1) 1px, transparent 1px);
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
                            <span class="text-[#d6fb00]">Sebaran Rusun</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>
    
    <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-black text-white tracking-tight mb-2">Sebaran Rumah Susun</h1>
        <p class="text-[#8aacb0]">Pemetaan aset dan okupansi Rumah Susun Sederhana Sewa (Rusunawa) Provinsi Jawa Tengah (Data Simulasi).</p>
    </div>

    <!-- Map & List Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        
        <!-- Interactive Map Mockup -->
        <div class="dark-bento-container p-4 lg:col-span-2 min-h-[500px] flex flex-col">
            <div class="flex justify-between items-center mb-4 px-2">
                <h3 class="text-white font-bold"><i class="fa-solid fa-map-location-dot text-[#00a3b5] mr-2"></i> Peta Lokasi Rusunawa</h3>
                <div class="flex gap-2">
                    <button class="bg-[#00a3b5]/10 text-[#00a3b5] px-3 py-1 text-xs rounded-lg border border-[#00a3b5]/20 hover:bg-[#00a3b5]/20 transition-colors">Semua</button>
                    <button class="bg-[#0f2a30] text-[#8aacb0] px-3 py-1 text-xs rounded-lg border border-white/10 hover:text-white transition-colors">Tersedia</button>
                    <button class="bg-[#0f2a30] text-[#8aacb0] px-3 py-1 text-xs rounded-lg border border-white/10 hover:text-white transition-colors">Penuh</button>
                </div>
            </div>
            <!-- Actual Leaflet Map Canvas -->
            <div id="map_rusun" class="flex-1 bg-[#050d10] rounded-xl border border-white/5 relative z-0 min-h-[400px]"></div>
        </div>

        <!-- Table List Mockup -->
        <div class="dark-bento-container p-6 flex flex-col">
            <h3 class="text-white font-bold mb-4">Daftar Rusunawa Terpadat</h3>
            <div class="flex-1 overflow-y-auto pr-2" style="max-height: 440px;">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-[#8aacb0] border-b border-white/10">
                            <th class="pb-3 font-medium">Nama Rusunawa</th>
                            <th class="pb-3 font-medium text-right">Okupansi</th>
                        </tr>
                    </thead>
                    <tbody class="text-white">
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">
                                <div class="font-semibold text-white">Rusunawa Kudu</div>
                                <div class="text-[10px] text-zinc-500">Kota Semarang</div>
                            </td>
                            <td class="py-3 text-right text-[#ff6b6b] font-bold">100%</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">
                                <div class="font-semibold text-white">Rusunawa Kraton</div>
                                <div class="text-[10px] text-zinc-500">Kota Tegal</div>
                            </td>
                            <td class="py-3 text-right text-[#ff6b6b] font-bold">100%</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">
                                <div class="font-semibold text-white">Rusunawa Pekalongan</div>
                                <div class="text-[10px] text-zinc-500">Kota Pekalongan</div>
                            </td>
                            <td class="py-3 text-right text-[#ffd93d] font-bold">98%</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">
                                <div class="font-semibold text-white">Rusunawa Kaligawe</div>
                                <div class="text-[10px] text-zinc-500">Kota Semarang</div>
                            </td>
                            <td class="py-3 text-right text-[#ffd93d] font-bold">96%</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">
                                <div class="font-semibold text-white">Rusunawa Mangkang</div>
                                <div class="text-[10px] text-zinc-500">Kota Semarang</div>
                            </td>
                            <td class="py-3 text-right text-[#6bcb77] font-bold">85%</td>
                        </tr>
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="py-3">
                                <div class="font-semibold text-white">Rusunawa Cilacap</div>
                                <div class="text-[10px] text-zinc-500">Kab. Cilacap</div>
                            </td>
                            <td class="py-3 text-right text-[#6bcb77] font-bold">78%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="dark-bento-container p-6 relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-[#00a3b5] opacity-5 text-8xl"><i class="fa-solid fa-building"></i></div>
            <p class="text-xs text-[#8aacb0] uppercase tracking-wider font-bold mb-2">Total Tower Bangunan</p>
            <h3 class="text-3xl font-black text-white">124 <span class="text-xs font-medium text-zinc-500">Tower</span></h3>
            <div class="mt-2 text-[10px] text-[#6bcb77] font-semibold"><i class="fa-solid fa-arrow-trend-up"></i> +4 Tower Baru (2025)</div>
        </div>
        <div class="dark-bento-container p-6 relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-[#00a3b5] opacity-5 text-8xl"><i class="fa-solid fa-bed"></i></div>
            <p class="text-xs text-[#8aacb0] uppercase tracking-wider font-bold mb-2">Total Unit Hunian</p>
            <h3 class="text-3xl font-black text-[#00a3b5]">8,420 <span class="text-xs font-medium text-[#00a3b5]/50">Unit</span></h3>
            <div class="mt-2 text-[10px] text-[#8aacb0]">Rata-rata 68 unit/tower</div>
        </div>
        <div class="dark-bento-container p-6 relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-[#00a3b5] opacity-5 text-8xl"><i class="fa-solid fa-users"></i></div>
            <p class="text-xs text-[#8aacb0] uppercase tracking-wider font-bold mb-2">Tingkat Okupansi</p>
            <h3 class="text-3xl font-black text-[#6bcb77]">92.4 <span class="text-xs font-medium text-[#6bcb77]/50">%</span></h3>
            <div class="w-full bg-black/40 h-1.5 rounded-full mt-3 overflow-hidden">
                <div class="bg-[#6bcb77] h-full rounded-full" style="width: 92.4%"></div>
            </div>
        </div>
        <div class="dark-bento-container p-6 relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-[#00a3b5] opacity-5 text-8xl"><i class="fa-solid fa-wrench"></i></div>
            <p class="text-xs text-[#8aacb0] uppercase tracking-wider font-bold mb-2">Perlu Pemeliharaan</p>
            <h3 class="text-3xl font-black text-[#ffd93d]">12 <span class="text-xs font-medium text-zinc-500">Tower</span></h3>
            <div class="mt-2 text-[10px] text-[#ff6b6b] font-semibold">Terdapat keluhan kerusakan aset</div>
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

        var map = L.map('map_rusun', {
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

        const availIcon = L.divIcon({
            html: `<div class="relative flex items-center justify-center">
                    <span class="animate-ping absolute inline-flex h-6 w-6 rounded-full bg-[#00a3b5] opacity-75"></span>
                    <div class="relative bg-[#00a3b5] border-2 border-white/20 w-4 h-4 rounded-full shadow-lg"></div>
                   </div>`,
            className: 'custom-div-icon', iconSize: [24, 24], iconAnchor: [12, 12]
        });
        const fullIcon = L.divIcon({
            html: `<div class="relative flex items-center justify-center">
                    <span class="animate-ping absolute inline-flex h-6 w-6 rounded-full bg-[#ff6b6b] opacity-75"></span>
                    <div class="relative bg-[#ff6b6b] border-2 border-white/20 w-4 h-4 rounded-full shadow-lg"></div>
                   </div>`,
            className: 'custom-div-icon', iconSize: [24, 24], iconAnchor: [12, 12]
        });

        // Dummy Data Rusun (Jateng)
        const dummyData = [
            { lat: -6.9531, lng: 110.4578, name: "Rusunawa Kudu", icon: fullIcon, occupancy: "100%" },
            { lat: -6.8694, lng: 109.1402, name: "Rusunawa Kraton", icon: fullIcon, occupancy: "100%" },
            { lat: -6.8898, lng: 109.6745, name: "Rusunawa Pekalongan", icon: availIcon, occupancy: "98%" },
            { lat: -6.9575, lng: 110.4385, name: "Rusunawa Kaligawe", icon: availIcon, occupancy: "96%" },
            { lat: -6.9745, lng: 110.3167, name: "Rusunawa Mangkang", icon: availIcon, occupancy: "85%" },
            { lat: -7.7123, lng: 109.0065, name: "Rusunawa Cilacap", icon: availIcon, occupancy: "78%" }
        ];

        dummyData.forEach(function(item) {
            L.marker([item.lat, item.lng], {icon: item.icon}).addTo(map)
             .bindPopup(`<div class="text-[#0a1a1f] p-1"><b class="block text-sm font-black">\${item.name}</b><span class="text-xs text-[#00a3b5] font-bold">Okupansi: \${item.occupancy}</span></div>`);
        });

        setTimeout(function() { map.invalidateSize(); }, 500);
    });
</script>
