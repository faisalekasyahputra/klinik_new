<style>
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
</style>
<section class="w-full max-w-7xl mx-auto px-4 py-8 bg-[#0a1a1f]">
    <div class="space-y-6">
         <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
            <h2 class="text-2xl md:text-3xl font-black text-white tracking-tight">
                Peta Sebaran <span class="text-[#d6fb00]">Perumahan</span>
            </h2>
            <p class="text-zinc-400 mt-2">
                Temukan lokasi hunian dan proyek perumahan di wilayah operasional kami.
            </p>
        </div>
        <div class="relative bg-[#0f2a30] border border-[#d6fb00]/20 rounded-2xl p-2 shadow-xl">
    
    <div class="absolute top-6 left-6 z-[1] w-[calc(100%-48px)] md:w-96">
        <div class="bg-white rounded-2xl shadow-2xl p-4 flex items-center gap-3">
            <input id="mapSearch" type="text" placeholder="Cari nama perumahan..." class="w-full outline-none text-slate-800">
        </div>
        <div id="resultBox" class="hidden mt-2 bg-white rounded-xl shadow-xl max-h-60 overflow-y-auto">
            <ul id="resultList" class="divide-y divide-slate-100">
                </ul>
        </div>
    </div>

    <div id="map" class="w-full h-[500px] md:h-[600px] rounded-xl overflow-hidden z-0"></div>
    <div class="flex justify-center mt-12">
            <a href="<?= base_url('Index/Umum') ?>" class="group flex items-center gap-2.5 bg-[#d6fb00]/5 hover:bg-[#d6fb00]/8 border border-[#d6fb00]/20 hover:border-[#d6fb00]/20 px-6 py-3 rounded-xl text-xs font-semibold text-zinc-400 hover:text-white transition-all duration-300 shadow-xl backdrop-blur-md">
                <i class="fa-solid fa-arrow-left transition-transform group-hover:-translate-x-1"></i>
                <span>Kembali ke Halaman Umum</span>
            </a>

            </div>
</div>
    </div>
</section>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var map = L.map('map',{zoomControl: false}).setView([-7.3000, 110.0000], 9);

        L.control.zoom({
            position: 'bottomright'
        }).addTo(map);
    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri &mdash; Source: Esri, i-cubed, USDA, USGS, AEX, GeoEye, Getmapping, Aerogrid, IGN, IGP, UPR-EGP, and the GIS User Community',
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

        <?php foreach($results as $row) {
            $koordinat = explode(',', $row['koordinatPerumahan']);
            $row['lat']  = isset($koordinat[0]) ? trim($koordinat[0]) : '-7.0051';
            $row['long'] = isset($koordinat[1]) ? trim($koordinat[1]) : '110.4381';
            $tipe = isset($row['tipeRumah'][0]['status']) ? trim($row['tipeRumah'][0]['status']) : '';
            $icon = (strtolower($tipe) == 'komersil') ? 'blueIcon' : 'amberIcon'; 
            ?>
        L.marker([<?=$row['lat']?>, <?=$row['long']?>],{ icon: <?=$icon?> }).addTo(map)
             .bindPopup(`<div class="text-[#0a1a1f] font-sans p-1 min-w-[180px]">
                            <b class="text-xs font-black block text-zinc-900 leading-tight mb-0.5"><?=$row['namaPerumahan']?></b>
                            <span class="text-[10px] text-emerald-600 font-bold block mb-3">
                                <i class="fa-solid fa-circle-check text-[9px]"></i> <?=$tipe?>
                            </span>
                            <a href="<?php echo base_url('Index/detail_perum/' . $row['idLokasi']); ?>" target="_blank" class="block text-center bg-[#d6fb00] hover:bg-[#c2e600] text-[#0a1a1f] font-black text-[10px] uppercase tracking-wider py-2 px-3 rounded-md shadow transition-colors no-underline decoration-none">
                                <i class="fa-solid fa-diamond-turn-right mr-1"></i> Detail
                            </a>
                            
                        </div>`
                    
                    )

            .openPopup();
        <?php } ?>
          // Handler perbaikan rendering grid responsif
        setTimeout(function() {
            map.invalidateSize();
        }, 500);
        // Data dummy sebagai pengganti API sementara
        const rawData = <?php echo json_encode($results); ?>;
        const dummyPerumahan = rawData.map(item => {
            const [lat, long] = item.koordinatPerumahan.split(',').map(Number);
            return {
                id: item.idLokasi,
                nama: item.namaPerumahan,
                kabupaten: item.wilayah.kabupaten,
                kecamatan: item.wilayah.kecamatan,
                lat: lat,
                long: long,
                status: item.tipeRumah.some(t => t.status === 'subsidi') ? 'subsidi' : 'komersil'
            };
        });
        
        const resultList = document.getElementById('resultList');
        const resultBox = document.getElementById('resultBox');

        // Fungsi untuk memuat list dummy
        function loadDummyToSearchList(keyword = '') {
            resultList.innerHTML = ''; // Kosongkan list
            const filteredData = dummyPerumahan.filter(item => 
                item.nama.toLowerCase().includes(keyword.toLowerCase())
            );
            filteredData.forEach(item => {
                const li = document.createElement('li');
                li.className = 'px-4 py-3 hover:bg-slate-100 cursor-pointer text-sm text-slate-700 flex justify-between items-center';
                
                // Menambahkan teks nama dan label kecil status
                li.innerHTML = `
                    <div class="mt-1 text-slate-400">
                        <i class="fa-solid fa-location-dot"></i>
                    </div>
                    <div class="flex-1">
                        <div class="text-sm font-semibold text-slate-800">${item.nama}</div>
                        <div class="text-[11px] text-slate-500">
                            ${item.kecamatan || 'Kecamatan tidak tersedia'}, ${item.kabupaten || 'Kabupaten tidak tersedia'}
                        </div>
                    </div>
                    <div class="text-[10px] uppercase font-bold self-center ${item.status === 'komersil' ? 'text-blue-500' : 'text-red-500'}">
                        ${item.status}
                    </div>
                `;
                
                li.onclick = () => {
                  map.flyTo([item.lat, item.long], 16, {
                duration: 1.5 // Durasi animasi dalam detik
            });

            // 2. Opsional: Buka popup jika ingin memberikan detail langsung
            // Anda bisa memanggil marker yang sesuai jika sudah disimpan di array
            // Atau tampilkan info custom lainnya
            
            // 3. Sembunyikan box hasil pencarian
            resultBox.classList.add('hidden');
            
            // 4. Reset/isi input dengan nama yang dipilih
            searchInput.value = item.nama;
                };
                
                resultList.appendChild(li);
            });
        }

        // Panggil fungsi ini saat user fokus ke input search
        document.getElementById('mapSearch').addEventListener('keyup', function() {
           const keyword = this.value;
    
            if (keyword.length > 0) {
                resultBox.classList.remove('hidden');
                loadDummyToSearchList(keyword); // Kirim kata kunci untuk difilter
            } else {
                resultBox.classList.add('hidden'); // Sembunyikan jika input kosong
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