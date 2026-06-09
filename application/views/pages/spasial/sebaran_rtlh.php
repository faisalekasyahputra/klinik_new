<style>
  .dark-bento-container {
    background-color: rgba(15, 42, 48, 0.4);
    backdrop-filter: blur(24px);
    -webkit-backdrop-filter: blur(24px);
    border: 1px solid rgba(214, 251, 0, 0.08);
    border-radius: 24px;
  }
  .map-dummy-bg {
    background-image: radial-gradient(rgba(214, 251, 0, 0.1) 1px, transparent 1px);
    background-size: 20px 20px;
  }
</style>

<section class="w-full max-w-7xl mx-auto px-4 py-8" style="padding-top: 100px;">
    
    <nav class="flex items-center gap-2.5 text-xs md:text-sm text-[#8aacb0] font-medium mb-8" aria-label="Breadcrumb">
        <a href="<?= base_url() ?>" class="hover:text-[#d6fb00] transition-colors duration-200 flex items-center gap-1.5">
            <i class="fa-solid fa-house text-[10px]"></i> Beranda
        </a>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-40"></i>
        <span class="hover:text-white transition-colors duration-200 cursor-default">Data & Spasial</span>
        <i class="fa-solid fa-chevron-right text-[8px] opacity-40"></i>
        <span class="text-white">Sebaran RTLH</span>
    </nav>
    
    <div class="mb-8">
        <h1 class="text-3xl md:text-4xl font-black text-white tracking-tight mb-2">Sebaran RTLH</h1>
        <p class="text-[#8aacb0]">Peta interaktif dan statistik Rumah Tidak Layak Huni di Provinsi Jawa Tengah (Data Simulasi).</p>
    </div>

    <!-- Map & List Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        
        <!-- Interactive Map Mockup -->
        <div class="dark-bento-container p-4 lg:col-span-2 min-h-[500px] flex flex-col">
            <div class="flex justify-between items-center mb-4 px-2">
                <h3 class="text-white font-bold"><i class="fa-solid fa-map-location-dot text-[#d6fb00] mr-2"></i> Peta Spasial RTLH</h3>
                <div class="flex gap-2">
                    <button class="bg-[#d6fb00]/10 text-[#d6fb00] px-3 py-1 text-xs rounded-lg border border-[#d6fb00]/20 hover:bg-[#d6fb00]/20 transition-colors">Semua</button>
                    <button class="bg-[#0f2a30] text-[#8aacb0] px-3 py-1 text-xs rounded-lg border border-white/10 hover:text-white transition-colors">Tinggi</button>
                    <button class="bg-[#0f2a30] text-[#8aacb0] px-3 py-1 text-xs rounded-lg border border-white/10 hover:text-white transition-colors">Rendah</button>
                </div>
            </div>
            <!-- Actual Leaflet Map Canvas -->
            <div id="map_rtlh" class="flex-1 bg-[#050d10] rounded-xl border border-white/5 relative z-0 min-h-[400px]"></div>
        </div>

        <!-- Table List Mockup -->
        <div class="dark-bento-container p-6 flex flex-col">
            <h3 class="text-white font-bold mb-4">10 Kab/Kota Teratas</h3>
            <div class="flex-1 overflow-y-auto pr-2" style="max-height: 440px;">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="text-[#8aacb0] border-b border-white/10">
                            <th class="pb-3 font-medium">Kabupaten/Kota</th>
                            <th class="pb-3 font-medium text-right">Jml RTLH</th>
                        </tr>
                    </thead>
                    <tbody class="text-white">
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kab. Brebes</td>
                            <td class="py-3 text-right text-[#ff6b6b] font-semibold">89,204</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kab. Pemalang</td>
                            <td class="py-3 text-right text-[#ff6b6b] font-semibold">76,412</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kab. Banyumas</td>
                            <td class="py-3 text-right text-[#ffd93d] font-semibold">65,100</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kab. Cilacap</td>
                            <td class="py-3 text-right text-[#ffd93d] font-semibold">58,990</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kab. Tegal</td>
                            <td class="py-3 text-right text-[#ffd93d] font-semibold">54,230</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kab. Grobogan</td>
                            <td class="py-3 text-right text-white font-semibold">49,800</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kab. Demak</td>
                            <td class="py-3 text-right text-white font-semibold">42,150</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kab. Pati</td>
                            <td class="py-3 text-right text-white font-semibold">39,400</td>
                        </tr>
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-3">Kota Semarang</td>
                            <td class="py-3 text-right text-[#6bcb77] font-semibold">12,300</td>
                        </tr>
                        <tr class="hover:bg-white/5 transition-colors">
                            <td class="py-3">Kota Salatiga</td>
                            <td class="py-3 text-right text-[#6bcb77] font-semibold">3,200</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="dark-bento-container p-6 relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-[#d6fb00] opacity-5 text-8xl"><i class="fa-solid fa-house-crack"></i></div>
            <p class="text-xs text-[#8aacb0] uppercase tracking-wider font-bold mb-2">Total RTLH Terdata</p>
            <h3 class="text-3xl font-black text-white">1,452,890 <span class="text-xs font-medium text-zinc-500">Unit</span></h3>
            <div class="mt-2 text-[10px] text-[#6bcb77] font-semibold"><i class="fa-solid fa-arrow-trend-down"></i> -2.4% dari tahun lalu</div>
        </div>
        <div class="dark-bento-container p-6 relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-[#d6fb00] opacity-5 text-8xl"><i class="fa-solid fa-hammer"></i></div>
            <p class="text-xs text-[#8aacb0] uppercase tracking-wider font-bold mb-2">Sudah Ditangani</p>
            <h3 class="text-3xl font-black text-[#d6fb00]">845,210 <span class="text-xs font-medium text-[#d6fb00]/50">Unit</span></h3>
            <div class="w-full bg-black/40 h-1.5 rounded-full mt-3 overflow-hidden">
                <div class="bg-[#d6fb00] h-full rounded-full" style="width: 58%"></div>
            </div>
        </div>
        <div class="dark-bento-container p-6 relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-[#d6fb00] opacity-5 text-8xl"><i class="fa-solid fa-hourglass-half"></i></div>
            <p class="text-xs text-[#8aacb0] uppercase tracking-wider font-bold mb-2">Sisa Belum Tertangani</p>
            <h3 class="text-3xl font-black text-[#ff6b6b]">607,680 <span class="text-xs font-medium text-[#ff6b6b]/50">Unit</span></h3>
            <div class="mt-2 text-[10px] text-[#8aacb0]">Tersebar di 35 Kab/Kota</div>
        </div>
        <div class="dark-bento-container p-6 relative overflow-hidden">
            <div class="absolute -right-4 -bottom-4 text-[#d6fb00] opacity-5 text-8xl"><i class="fa-solid fa-money-bill-wave"></i></div>
            <p class="text-xs text-[#8aacb0] uppercase tracking-wider font-bold mb-2">Alokasi Anggaran (APBD)</p>
            <h3 class="text-3xl font-black text-white">Rp 450<span class="text-xs font-medium text-zinc-500">M</span></h3>
            <div class="mt-2 text-[10px] text-[#6bcb77] font-semibold"><i class="fa-solid fa-arrow-trend-up"></i> +12% dari 2024</div>
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

        var map = L.map('map_rtlh', {
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

        const dangerIcon = L.divIcon({
            html: `<div class="relative flex items-center justify-center">
                    <span class="animate-ping absolute inline-flex h-6 w-6 rounded-full bg-[#ff6b6b] opacity-75"></span>
                    <div class="relative bg-[#ff6b6b] border-2 border-[#ff6b6b]/20 w-4 h-4 rounded-full shadow-lg"></div>
                   </div>`,
            className: 'custom-div-icon', iconSize: [24, 24], iconAnchor: [12, 12]
        });
        const warningIcon = L.divIcon({
            html: `<div class="relative flex items-center justify-center">
                    <span class="animate-ping absolute inline-flex h-6 w-6 rounded-full bg-[#ffd93d] opacity-75"></span>
                    <div class="relative bg-[#ffd93d] border-2 border-[#ffd93d]/20 w-4 h-4 rounded-full shadow-lg"></div>
                   </div>`,
            className: 'custom-div-icon', iconSize: [24, 24], iconAnchor: [12, 12]
        });

        // Dummy Data RTLH (Jateng)
        const dummyData = [
            { lat: -6.8698, lng: 109.0555, name: "Kab. Brebes", icon: dangerIcon, amount: "89,204 Unit" },
            { lat: -6.8909, lng: 109.3844, name: "Kab. Pemalang", icon: dangerIcon, amount: "76,412 Unit" },
            { lat: -7.0051, lng: 110.4381, name: "Kota Semarang", icon: warningIcon, amount: "12,300 Unit" },
            { lat: -7.4258, lng: 109.2301, name: "Kab. Banyumas", icon: warningIcon, amount: "65,100 Unit" },
            { lat: -7.6749, lng: 109.0205, name: "Kab. Cilacap", icon: dangerIcon, amount: "58,990 Unit" },
            { lat: -6.8942, lng: 110.6386, name: "Kab. Demak", icon: warningIcon, amount: "42,150 Unit" }
        ];

        dummyData.forEach(function(item) {
            L.marker([item.lat, item.lng], {icon: item.icon}).addTo(map)
             .bindPopup(`<div class="text-[#0a1a1f] p-1"><b class="block text-sm font-black">\${item.name}</b><span class="text-xs text-[#ff6b6b] font-bold">\${item.amount} RTLH</span></div>`);
        });

        setTimeout(function() { map.invalidateSize(); }, 500);
    });
</script>
