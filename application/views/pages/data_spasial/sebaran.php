<style>
    .leaflet-control-zoom-in, 
    .leaflet-control-zoom-out {
        background-color: #16181d !important; /* Warna background gelap */
        color: #ffffff !important;           /* Warna teks putih */
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        font-weight: bold;
    }
    .leaflet-control-container {
        z-index: 500; 
    }
    .leaflet-control-zoom-in:hover, 
    .leaflet-control-zoom-out:hover {
        background-color: #2a2e36 !important;
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
                            <span class="text-[#d6fb00]">Sebaran RTLH</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

<div class="w-full max-w-7xl mx-auto px-4 py-8 bg-transparent">
    <div class="space-y-6">
         <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
            <h2 class="text-2xl md:text-3xl font-black text-white tracking-tight">
                Peta Sebaran <span class="text-[#d6fb00]">RTLH & Perumahan</span>
            </h2>
            <p class="text-zinc-400 mt-2">
                Temukan lokasi hunian dan Rumah Tidak Layak Huni di wilayah operasional kami.
            </p>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Peta (Kiri, 2 Kolom) -->
            <div class="lg:col-span-2 relative bg-[#0f2a30] border border-[#d6fb00]/20 rounded-2xl p-2 shadow-xl flex flex-col">
                <div id="map" class="w-full flex-1 min-h-[500px] md:min-h-[600px] rounded-xl overflow-hidden z-0"></div>
            </div>

            <!-- Panel Filter (Kanan, 1 Kolom) -->
            <div class="lg:col-span-1 bg-[#0f2a30] border border-[#d6fb00]/20 rounded-2xl p-6 shadow-xl flex flex-col">
                <h3 class="text-white font-bold text-lg mb-6 flex items-center gap-2">
                    <i class="fa-solid fa-filter text-[#d6fb00]"></i> Filter Pencarian
                </h3>
                
                <div class="space-y-6 flex-1">
                    <!-- Cari Nama Perumahan -->
                    <div>
                        <label class="block text-xs font-bold text-zinc-400 uppercase tracking-wider mb-2">Pencarian Spesifik</label>
                        <div class="bg-[#0a1a1f] border border-white/10 rounded-xl p-3 flex items-center gap-3 focus-within:border-[#d6fb00]/50 transition-colors relative">
                            <i class="fa-solid fa-magnifying-glass text-zinc-500"></i>
                            <input id="mapSearch" type="text" placeholder="Ketik nama perumahan..." class="w-full bg-transparent outline-none text-white placeholder-zinc-600 text-sm">
                        </div>
                        <div id="resultBox" class="hidden mt-2 bg-[#0a1a1f] border border-[#d6fb00]/30 rounded-xl shadow-2xl max-h-48 overflow-y-auto relative z-50">
                            <ul id="resultList" class="divide-y divide-white/5 text-zinc-300 text-sm">
                                <!-- Isi dari JS -->
                            </ul>
                        </div>
                    </div>

                    <!-- Filter Kabupaten -->
                    <div>
                        <label class="block text-xs font-bold text-zinc-400 uppercase tracking-wider mb-2">Kabupaten / Kota</label>
                        <div class="relative" id="kabupatenDropdown">
                            <div class="w-full bg-[#0a1a1f] border border-white/10 rounded-xl pl-4 pr-10 py-3 text-white text-sm cursor-pointer flex items-center justify-between" id="kabupatenSelected">
                                <span class="truncate">Seluruh Jawa Tengah</span>
                                <i class="fa-solid fa-chevron-down text-zinc-500 text-xs transition-transform" id="kabupatenIcon"></i>
                            </div>
                            <input type="hidden" id="filterKabupaten" value="">
                            
                            <div class="absolute z-50 w-full mt-2 bg-[#0a1a1f] border border-white/10 rounded-xl shadow-xl hidden flex-col overflow-hidden" id="kabupatenListContainer">
                                <div class="p-2 border-b border-white/10">
                                    <input type="text" id="kabupatenSearch" class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white text-sm outline-none focus:border-[#d6fb00]/50 placeholder-zinc-500" placeholder="Cari Kabupaten/Kota...">
                                </div>
                                <ul class="max-h-48 overflow-y-auto flex-1 custom-scrollbar py-1" id="kabupatenList">
                                    <li class="px-4 py-2.5 text-sm text-white hover:bg-[#d6fb00] hover:text-[#0a1a1f] cursor-pointer transition-colors font-bold" data-value="">Seluruh Jawa Tengah</li>
                                    <!-- options will be appended here -->
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Kategori Status -->
                    <div>
                        <label class="block text-xs font-bold text-zinc-400 uppercase tracking-wider mb-2">Status Rumah</label>
                        <style>
                            input[name="status"]:checked + .radio-btn-semua {
                                border-color: #ffffff !important;
                                color: #ffffff !important;
                                box-shadow: 0 0 15px rgba(255,255,255,0.15) !important;
                                background-color: rgba(255,255,255,0.05) !important;
                            }
                            input[name="status"]:checked + .radio-btn-subsidi {
                                border-color: #d6fb00 !important;
                                color: #d6fb00 !important;
                                box-shadow: 0 0 15px rgba(214,251,0,0.15) !important;
                                background-color: rgba(214,251,0,0.05) !important;
                            }
                            input[name="status"]:checked + .radio-btn-komersil {
                                border-color: #00a3b5 !important;
                                color: #00a3b5 !important;
                                box-shadow: 0 0 15px rgba(0,163,181,0.15) !important;
                                background-color: rgba(0,163,181,0.05) !important;
                            }
                            input[name="status"]:checked + div i {
                                transform: scale(1.2);
                            }
                        </style>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="cursor-pointer col-span-2 relative block">
                                <input type="radio" name="status" value="semua" class="peer sr-only" checked>
                                <div class="radio-btn-semua bg-[#0a1a1f] border border-white/10 text-zinc-400 rounded-xl py-2.5 flex items-center justify-center gap-2 transition-all duration-300">
                                    <i class="fa-solid fa-circle-check transition-transform duration-300"></i>
                                    <span class="text-xs font-bold uppercase tracking-wider">Semua Kategori</span>
                                </div>
                            </label>
                            <label class="cursor-pointer relative block">
                                <input type="radio" name="status" value="subsidi" class="peer sr-only">
                                <div class="radio-btn-subsidi bg-[#0a1a1f] border border-white/10 text-zinc-400 rounded-xl py-2.5 flex items-center justify-center gap-2 transition-all duration-300">
                                    <i class="fa-solid fa-circle-check transition-transform duration-300"></i>
                                    <span class="text-xs font-bold uppercase tracking-wider">Subsidi</span>
                                </div>
                            </label>
                            <label class="cursor-pointer relative block">
                                <input type="radio" name="status" value="komersil" class="peer sr-only">
                                <div class="radio-btn-komersil bg-[#0a1a1f] border border-white/10 text-zinc-400 rounded-xl py-2.5 flex items-center justify-center gap-2 transition-all duration-300">
                                    <i class="fa-solid fa-circle-check transition-transform duration-300"></i>
                                    <span class="text-xs font-bold uppercase tracking-wider">Komersil</span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-8 pt-6 border-t border-white/10">
                    <button id="btnFilter" class="w-full bg-[#d6fb00] hover:bg-[#c2e600] text-[#0a1a1f] font-black text-sm uppercase tracking-wider py-3.5 rounded-xl shadow-[0_0_15px_rgba(214,251,0,0.2)] hover:shadow-[0_0_25px_rgba(214,251,0,0.4)] transition-all transform hover:-translate-y-0.5 flex justify-center items-center gap-2">
                        <i class="fa-solid fa-layer-group"></i> Terapkan Filter
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

    </div>
</section>
<style>
.leaflet-container {
    font-family: 'Plus Jakarta Sans', 'Inter', sans-serif !important;
}
.leaflet-popup-content-wrapper {
    border-radius: 12px !important;
    border: 2px solid rgba(214, 251, 0, 0.15) !important;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.3) !important;
}
.leaflet-popup-tip {
    background: white;
}
</style>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var map = L.map('map',{zoomControl: false, attributionControl: false}).setView([-7.3000, 110.0000], 9);

        L.control.zoom({
            position: 'bottomright'
        }).addTo(map);
    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19
    }).addTo(map);
    
    // Label and Boundary overlay
    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}', {
        maxZoom: 19
    }).addTo(map);
         const amberIcon = L.divIcon({
            html: `<div class="relative flex items-center justify-center">
                    <span class="animate-ping absolute inline-flex h-6 w-6 rounded-full bg-[#d6fb00] opacity-75"></span>
                    <div class="relative bg-[#d6fb00] border-2 border-[#d6fb00]/20 w-4 h-4 rounded-full shadow-lg"></div>
                   </div>`,
            className: 'custom-div-icon',
            iconSize: [24, 24],
            iconAnchor: [12, 12]
        });

        const blueIcon = L.divIcon({
            html: `<div class="relative flex items-center justify-center">
                    <span class="animate-ping absolute inline-flex h-6 w-6 rounded-full bg-blue-500 opacity-75"></span>
                    <div class="relative bg-blue-500 border-2 border-[#d6fb00]/20 w-4 h-4 rounded-full shadow-lg"></div>
                </div>`,
            className: 'custom-div-icon',
            iconSize: [24, 24],
            iconAnchor: [12, 12]
        });

        const rawData = <?php echo json_encode($results); ?>;
        
        // Buat layer group untuk marker agar mudah dihapus dan ditambah
        const markersLayer = L.layerGroup().addTo(map);
        
        const mapMarkers = rawData.map(item => {
            let lat = -7.0051;
            let long = 110.4381;
            if(item.koordinatPerumahan) {
                const koord = item.koordinatPerumahan.split(',');
                if(koord.length === 2) {
                    lat = parseFloat(koord[0].trim());
                    long = parseFloat(koord[1].trim());
                }
            }
            
            const tipeRaw = item.tipeRumah && item.tipeRumah.length > 0 ? item.tipeRumah[0].status : '';
            const tipe = tipeRaw.toLowerCase();
            const isKomersil = tipe === 'komersil';
            const icon = isKomersil ? blueIcon : amberIcon;
            
            const marker = L.marker([lat, long], {icon: icon})
                .bindPopup(`<div class="text-[#0a1a1f] font-sans p-1 min-w-[180px]">
                            <b class="text-xs font-black block text-[#0f2a30] leading-tight mb-0.5">${item.namaPerumahan || 'Tanpa Nama'}</b>
                            <span class="text-[10px] text-emerald-600 font-bold block mb-3">
                                <i class="fa-solid fa-circle-check text-[9px]"></i> ${tipeRaw || 'Umum'}
                            </span>
                            <a href="<?php echo base_url('detail_perum/'); ?>${item.idLokasi}" target="_blank" class="block text-center bg-[#d6fb00] hover:bg-[#c2e600] text-[#0a1a1f] font-black text-[10px] uppercase tracking-wider py-2 px-3 rounded-md shadow transition-colors no-underline decoration-none">
                                <i class="fa-solid fa-diamond-turn-right mr-1"></i> Detail
                            </a>
                        </div>`);
            
            // Tambahkan ke map secara default (semua kategori dan lokasi)
            markersLayer.addLayer(marker);
            
            return {
                id: item.idLokasi,
                nama: item.namaPerumahan ? item.namaPerumahan.toLowerCase() : '',
                kabupaten: item.wilayah && item.wilayah.kabupaten ? item.wilayah.kabupaten : '',
                idKabupaten: item.wilayah && item.wilayah.kabupaten ? item.wilayah.kabupaten : '',
                lat: lat,
                long: long,
                status: tipe === 'subsidi' ? 'subsidi' : (tipe === 'komersil' ? 'komersil' : ''),
                marker: marker
            };
        });

        // Generate Dropdown Kabupaten Dinamis dari Data yang Ada
        const uniqueKabupaten = [];
        const kabSelect = document.getElementById('filterKabupaten');
        
        mapMarkers.forEach(item => {
            if (item.idKabupaten && item.kabupaten) {
                if (!uniqueKabupaten.find(k => k.id === item.idKabupaten)) {
                    uniqueKabupaten.push({
                        id: item.idKabupaten,
                        nama: item.kabupaten
                    });
                }
            }
        });
        
        // Urutkan berdasarkan abjad A-Z
        uniqueKabupaten.sort((a, b) => a.nama.localeCompare(b.nama));
        
        // Logika Dropdown Custom Searchable
        const filterKabupaten = document.getElementById('filterKabupaten');
        const kabSelected = document.getElementById('kabupatenSelected').querySelector('span');
        const kabDropdown = document.getElementById('kabupatenDropdown');
        const kabListContainer = document.getElementById('kabupatenListContainer');
        const kabList = document.getElementById('kabupatenList');
        const kabSearch = document.getElementById('kabupatenSearch');
        const kabIcon = document.getElementById('kabupatenIcon');

        // Toggle dropdown open/close
        document.getElementById('kabupatenSelected').addEventListener('click', () => {
            kabListContainer.classList.toggle('hidden');
            kabIcon.classList.toggle('rotate-180');
            if(!kabListContainer.classList.contains('hidden')) {
                kabSearch.focus();
                kabSearch.value = ''; // Reset pencarian saat dibuka
                kabSearch.dispatchEvent(new Event('keyup'));
            }
        });

        // Menutup dropdown jika user klik di luarnya
        document.addEventListener('click', (e) => {
            if (!kabDropdown.contains(e.target)) {
                kabListContainer.classList.add('hidden');
                kabIcon.classList.remove('rotate-180');
            }
        });

        // Fitur pencarian Kabupaten
        kabSearch.addEventListener('keyup', (e) => {
            const keyword = e.target.value.toLowerCase();
            const items = kabList.querySelectorAll('li');
            items.forEach(item => {
                if(item.textContent.toLowerCase().includes(keyword)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Saat item kabupaten diklik
        function selectKabupaten(value, text) {
            filterKabupaten.value = value;
            kabSelected.textContent = text;
            kabListContainer.classList.add('hidden');
            kabIcon.classList.remove('rotate-180');
            
            // Otomatis terapkan filter jika diinginkan
            document.getElementById('btnFilter').click();
        }

        kabList.addEventListener('click', (e) => {
            if(e.target.tagName === 'LI') {
                selectKabupaten(e.target.dataset.value, e.target.textContent);
            }
        });

        // Memasukkan list kabupaten dari data API
        uniqueKabupaten.forEach(kab => {
            const li = document.createElement('li');
            li.className = 'px-4 py-2.5 text-sm text-zinc-300 hover:bg-[#d6fb00] hover:text-[#0a1a1f] cursor-pointer transition-colors';
            li.dataset.value = kab.id;
            li.textContent = kab.nama;
            kabList.appendChild(li);
        });

        // Event handler resize peta
        setTimeout(function() {
            map.invalidateSize();
        }, 500);

        // Filter Logic Implementation
        document.getElementById('btnFilter').addEventListener('click', function() {
            const keyword = document.getElementById('mapSearch').value.toLowerCase();
            const idKab = document.getElementById('filterKabupaten').value;
            const statusNode = document.querySelector('input[name="status"]:checked');
            const status = statusNode ? statusNode.value : 'semua';
            
            // Bersihkan semua marker dari peta
            markersLayer.clearLayers();
            
            let matchCount = 0;
            let lastMatchLatLng = null;

            mapMarkers.forEach(item => {
                let matchKeyword = keyword === '' || item.nama.includes(keyword);
                let matchKab = idKab === '' || item.idKabupaten == idKab;
                let matchStatus = status === 'semua' || item.status === status;
                
                if(matchKeyword && matchKab && matchStatus) {
                    markersLayer.addLayer(item.marker);
                    matchCount++;
                    lastMatchLatLng = [item.lat, item.long];
                }
            });

            // Opsional: Jika ada marker cocok dan keyword tidak kosong, zoom ke marker terakhir
            if(matchCount > 0 && keyword !== '' && lastMatchLatLng) {
                map.flyTo(lastMatchLatLng, 13, { duration: 1.5 });
            }
        });
        
        // Autocomplete Sederhana untuk Nama
        const resultList = document.getElementById('resultList');
        const resultBox = document.getElementById('resultBox');
        const searchInput = document.getElementById('mapSearch');

        searchInput.addEventListener('keyup', function() {
            const keyword = this.value.toLowerCase();
            resultList.innerHTML = '';
            
            if (keyword.length > 0) {
                resultBox.classList.remove('hidden');
                
                const filteredData = mapMarkers.filter(item => item.nama.includes(keyword));
                
                filteredData.forEach(item => {
                    const li = document.createElement('li');
                    li.className = 'px-4 py-3 hover:bg-white/5 cursor-pointer text-sm text-slate-300 flex justify-between items-center transition-colors';
                    
                    li.innerHTML = `
                        <div class="mt-1 text-slate-500">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <div class="flex-1 ml-3">
                            <div class="text-sm font-semibold text-white uppercase">${item.nama}</div>
                            <div class="text-[11px] text-zinc-400">
                                ${item.kabupaten || 'Kabupaten tidak tersedia'}
                            </div>
                        </div>
                        <div class="text-[10px] uppercase font-bold self-center ${item.status === 'komersil' ? 'text-blue-500' : (item.status === 'subsidi' ? 'text-[#d6fb00]' : 'text-zinc-500')}">
                            ${item.status}
                        </div>
                    `;
                    
                    li.onclick = () => {
                        searchInput.value = item.nama;
                        resultBox.classList.add('hidden');
                        
                        // Otomatis terapkan filter pencarian
                        document.getElementById('btnFilter').click();
                    };
                    
                    resultList.appendChild(li);
                });
            } else {
                resultBox.classList.add('hidden');
            }
        });

        // Sembunyikan resultBox jika klik di luar
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !resultBox.contains(e.target)) {
                resultBox.classList.add('hidden');
            }
        });
    });
    
</script>

<style>
    /* Opsional: Membuat filter gelap pada peta agar tidak terlalu silau di mode gelap */
    .map-tiles {
        filter: invert(90%) hue-rotate(180deg) brightness(95%) contrast(90%);
    }
</style>

