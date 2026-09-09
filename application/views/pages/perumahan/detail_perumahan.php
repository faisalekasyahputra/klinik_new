<style>
    .detail-perumahan [class*="bg-[#0f2a30]"] { background-color: var(--portal-bg-card) !important; }
    .detail-perumahan [class*="bg-[#0a1a1f]"] { background-color: var(--portal-bg) !important; }
    .detail-perumahan [class*="text-white"] { color: var(--portal-text) !important; }
    .detail-perumahan [class*="text-zinc-"] { color: #467780 !important; }
    .detail-perumahan [class*="border-[#d6fb00]"] { border-color: var(--portal-border) !important; }
    .detail-perumahan [class*="text-[#d6fb00]"] { color: var(--portal-brand) !important; }
    .detail-perumahan [class*="bg-black/"] { background-color: rgba(15, 42, 48, 0.82) !important; }
    .detail-perumahan .shadow-xl,
    .detail-perumahan .shadow-lg { box-shadow: var(--portal-shadow) !important; }
    .detail-perumahan .shadow-\[0_4px_15px_rgba\(214\,251\,0\,0\.3\)\] { box-shadow: 0 4px 12px rgba(0, 163, 181, 0.14) !important; }
    .detail-perumahan .detail-heading { color: var(--portal-text) !important; }
    .detail-perumahan .detail-muted { color: var(--portal-text-muted) !important; }
    .detail-perumahan .detail-primary-btn { background: var(--brand) !important; color: var(--bg-body) !important; }
    .detail-perumahan .detail-primary-btn:hover { background: var(--brand-light) !important; }
    .detail-perumahan .detail-secondary-btn { background: var(--portal-btn-bg) !important; color: var(--portal-text) !important; border-color: var(--portal-btn-border) !important; }
</style>

<section class="detail-perumahan w-full pt-4 sm:pt-6 lg:pt-8 pb-12 px-4 sm:px-6 lg:px-8 relative min-h-screen font-outfit overflow-hidden">
    <!-- Background Ornaments -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <!-- Batik Pattern Overlay -->
        
        
        <!-- Glow -->
        <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[400px] bg-[#00a3b5]/5 blur-[120px] rounded-full pointer-events-none"></div>
    </div>

    <div class="max-w-7xl mx-auto relative z-10">
        
        <div class="mb-8">
                    <a href="javascript:history.back()" class="group inline-flex items-center gap-2 detail-muted hover:text-[#00a3b5] text-xs font-bold uppercase tracking-wider transition-colors">
                <i class="fa-solid fa-arrow-left transition-transform group-hover:-translate-x-1"></i> Kembali ke List Hunian
            </a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 mb-10">
            
            <div class="lg:col-span-7 grid grid-cols-12 gap-3 w-full relative">
                
                <?php 
                    // Ambil foto dari array jika tersedia
                    $foto1 = (!empty($row['foto']) && isset($row['foto'][0])) ? $row['foto'][0] : '';
                    $foto2 = (!empty($row['foto']) && isset($row['foto'][1])) ? $row['foto'][1] : '';
                    $foto3 = (!empty($row['foto']) && isset($row['foto'][2])) ? $row['foto'][2] : '';

                    // Fungsi pembantu untuk membersihkan path foto lokal jika diperlukan
                    if (!function_exists('dapatkan_url_foto')) {
                        function dapatkan_url_foto($url_mentah) {
                            if (empty($url_mentah)) return '';
                            if (strpos($url_mentah, 'http') === 0) return $url_mentah;
                            
                            $posisi_public = strpos($url_mentah, "public");
                            if ($posisi_public !== false) {
                                $path_bounds = stripslashes(substr($url_mentah, $posisi_public));
                                return base_url('Index/buka_foto?path=' . urlencode($path_bounds));
                            }
                            return $url_mentah;
                        }
                    }
                ?>

                <div class="col-span-8 relative bg-[#0f2a30] border border-[#d6fb00]/20 rounded-2xl overflow-hidden shadow-xl group h-[400px] md:h-[550px] lg:h-[600px] animate-pulse">
                    <?php if (!empty($foto1)): 
                         $url_asli = $row['foto'][0]; 
                        
                        // 2. Cari posisi kata "public" dan potong stringnya
                        $posisi_public = strpos($url_asli, "public");
                        $path_potong = substr($url_asli, $posisi_public);
                        
                        // 3. Bersihkan tanda backslash (\) agar menjadi public/upload/...
                        $path_bersih = stripslashes($path_potong);?>
                        
                        <img src="<?php echo base_url('Index/buka_foto'); ?>?path=<?= urlencode($path_bersih) ?>" class="absolute inset-0 w-full h-full object-cover transition-all duration-700 hover:scale-105 opacity-0" alt="Foto Utama Perumahan" loading="lazy" onload="this.parentElement.classList.remove('animate-pulse'); this.classList.remove('opacity-0');">
                    <?php else: ?>
                        <div class="absolute inset-0 w-full h-full flex flex-col items-center justify-center text-zinc-600 bg-[#0f2a30]">
                            <i class="fa-regular fa-image text-4xl mb-2"></i>
                            <span class="text-[10px] uppercase tracking-widest font-bold">Foto Utama</span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($row['tipeRumah'][0]['status'])): ?>
                        <span class="absolute top-4 left-4 z-10 bg-[#d6fb00] text-black text-[10px] font-black px-3 py-1 rounded-md shadow-md tracking-widest uppercase">
                            <?= htmlspecialchars($row['tipeRumah'][0]['status']) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="col-span-4 grid grid-rows-2 gap-3 h-[400px] md:h-[550px] lg:h-[600px]">
                    
                    <div class="relative bg-[#0f2a30] border border-[#d6fb00]/20 rounded-2xl overflow-hidden shadow-xl h-full w-full animate-pulse">
                        <?php if (!empty($foto2)): 
                            $url_asli = $row['foto'][1]; 
                            
                            // 2. Cari posisi kata "public" dan potong stringnya
                            $posisi_public = strpos($url_asli, "public");
                            $path_potong = substr($url_asli, $posisi_public);
                            
                            // 3. Bersihkan tanda backslash (\) agar menjadi public/upload/...
                            $path_bersih = stripslashes($path_potong);?>
                            <img src="<?php echo base_url('Index/buka_foto'); ?>?path=<?= urlencode($path_bersih) ?>" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-500 opacity-0" alt="Foto Unit Dalam" loading="lazy" onload="this.parentElement.classList.remove('animate-pulse'); this.classList.remove('opacity-0');">
                        <?php else: ?>
                            <div class="absolute inset-0 w-full h-full flex flex-col items-center justify-center text-zinc-600 bg-[#0f2a30]">
                                <i class="fa-regular fa-image text-2xl mb-1"></i>
                                <span class="text-[9px] uppercase tracking-widest font-bold">Foto Dalam</span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="relative bg-[#0f2a30] border border-[#d6fb00]/20 rounded-2xl overflow-hidden shadow-xl h-full w-full animate-pulse">
                        <?php if (!empty($foto3)): 
                            $url_asli = $row['foto'][2]; 
                            
                            // 2. Cari posisi kata "public" dan potong stringnya
                            $posisi_public = strpos($url_asli, "public");
                            $path_potong = substr($url_asli, $posisi_public);
                            
                            // 3. Bersihkan tanda backslash (\) agar menjadi public/upload/...
                            $path_bersih = stripslashes($path_potong);
                            ?>
                            <img src="<?php echo base_url('Index/buka_foto'); ?>?path=<?= urlencode($path_bersih) ?>" class="absolute inset-0 w-full h-full object-cover transition-opacity duration-500 opacity-0" alt="Foto Fasilitas" loading="lazy" onload="this.parentElement.classList.remove('animate-pulse'); this.classList.remove('opacity-0');">
                        <?php else: ?>
                            <div class="absolute inset-0 w-full h-full flex flex-col items-center justify-center text-zinc-600 bg-[#0f2a30]">
                                <i class="fa-regular fa-image text-2xl mb-1"></i>
                                <span class="text-[9px] uppercase tracking-widest font-bold">Fasilitas</span>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>

                <?php if (!empty($row['id'])): ?>
                    <div class="absolute bottom-4 right-4 z-20">
                    <a href="https://sikumbang.tapera.go.id/lokasi/<?= $row['id'] ?>/siteplan.svg" target="_blank" class="detail-secondary-btn inline-flex items-center gap-2 bg-black/70 hover:bg-[#d6fb00] hover:text-black text-white font-bold text-[11px] uppercase tracking-wider px-5 py-2.5 rounded-xl border border-[#d6fb00]/20 hover:border-[#d6fb00] backdrop-blur-md shadow-lg transition-all duration-300">
                            <i class="fa-solid fa-map-location text-xs"></i>
                            <span>Lihat Siteplan</span>
                        </a>
                    </div>
                <?php endif; ?>

            </div>

            <div class="lg:col-span-5 flex flex-col justify-center space-y-4">
                <span class="text-[#d6fb00] text-xs font-bold uppercase tracking-widest flex items-center gap-1.5">
                    Unit Terverifikasi <img src="<?= base_url('assets/img/icon-verified.svg') ?>" alt="Verified" class="inline-block w-4 h-4" style="filter: brightness(0) saturate(100%) invert(47%) sepia(78%) saturate(1200%) hue-rotate(145deg) brightness(88%) contrast(101%);">
                </span>
                <h1 class="detail-heading text-3xl md:text-4xl font-black text-white tracking-tighter font-jakarta">
                    <?= isset($row['namaPerumahan']) ? htmlspecialchars($row['namaPerumahan']) : 'Nama Perumahan Tidak Tersedia' ?>
                </h1>
                
                <p class="text-zinc-400 text-xs md:text-sm leading-relaxed flex items-start gap-2">
                    <i class="fa-solid fa-location-dot text-[#d6fb00] mt-1 text-xs shrink-0"></i>
                    <span>
                        Kecamatan <?= htmlspecialchars($row['wilayah']['kecamatan'] ?? '-') ?>, 
                        <?= htmlspecialchars($row['wilayah']['kabupaten'] ?? '-') ?>, 
                        <?= htmlspecialchars($row['wilayah']['provinsi'] ?? '-') ?>
                    </span>
                </p>

                <div class="p-5 bg-[#0f2a30] border border-[#d6fb00]/20 rounded-2xl flex items-center gap-4 mt-4">
                    <div class="h-12 w-12 rounded-xl bg-[#0a1a1f] border border-[#d6fb00]/20 flex items-center justify-center text-zinc-500 text-lg">
                        <i class="fa-solid fa-building-shield text-[#d6fb00]/80"></i>
                    </div>
                    <div>
                        <span class="block text-[9px] font-bold text-zinc-500 uppercase tracking-widest">Pengembang Resmi</span>
                        <h3 class="text-white font-bold text-sm tracking-tight">
                            <?= isset($row['pengembang']['nama']) ? htmlspecialchars($row['pengembang']['nama']) : 'Pengembang Tidak Terdata' ?>
                        </h3>
                    </div>
                </div>

                <div class="bg-[#0f2a30] border border-[#d6fb00]/20 p-5 rounded-2xl shadow-xl mt-4 w-full">
                    <h3 class="text-sm font-bold text-white mb-4 uppercase tracking-widest flex items-center gap-2 border-b border-[#d6fb00]/20 pb-3">
                        <i class="fa-solid fa-store text-[#d6fb00]"></i> Kantor Pemasaran
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <span class="block text-[9px] text-zinc-500 font-bold uppercase tracking-widest mb-1"><i class="fa-solid fa-map-location-dot text-zinc-600 mr-1"></i> Lokasi Pemasaran</span>
                            <p class="text-zinc-400 text-xs leading-relaxed">
                                <?= !empty($row['kantorPemasaran'][0]['alamat']) ? htmlspecialchars($row['kantorPemasaran'][0]['alamat']) : 'Informasi alamat kantor belum terdata' ?>
                            </p>
                        </div>

                        <div>
                            <span class="block text-[9px] text-zinc-500 font-bold uppercase tracking-widest mb-1"><i class="fa-solid fa-phone text-zinc-600 mr-1"></i> Kontak Telepon</span>
                            <p class="text-white text-xs font-bold tracking-wide">
                                <?= !empty($row['kantorPemasaran'][0]['noTelp']) ? htmlspecialchars($row['kantorPemasaran'][0]['noTelp']) : '-' ?>
                            </p>
                        </div>

                        <div>
                            <span class="block text-[9px] text-zinc-500 font-bold uppercase tracking-widest mb-1"><i class="fa-solid fa-envelope text-zinc-600 mr-1"></i> Korespondensi Email</span>
                            <p class="text-zinc-400 text-xs break-all">
                                <?= !empty($row['kantorPemasaran'][0]['email']) ? htmlspecialchars($row['kantorPemasaran'][0]['email']) : '-' ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- CTA Buttons -->
                <div class="mt-4 flex flex-col sm:flex-row items-center gap-3 w-full">
                    <?php
                        // Format nomor telepon menjadi nomor WhatsApp (62xxx)
                        $wa_number = !empty($row['kantorPemasaran'][0]['noTelp']) ? preg_replace('/[^0-9]/', '', $row['kantorPemasaran'][0]['noTelp']) : '';
                        if (strpos($wa_number, '0') === 0) {
                            $wa_number = '62' . substr($wa_number, 1);
                        }
                    ?>
                    <?php if(!empty($wa_number)): ?>
                    <a href="https://wa.me/<?= $wa_number ?>" target="_blank" class="detail-primary-btn w-full sm:flex-1 bg-[#d6fb00] hover:bg-[#c2e600] text-[#0a1a1f] font-black text-xs uppercase tracking-widest py-3.5 px-6 rounded-xl flex items-center justify-center gap-2 shadow-[0_4px_15px_rgba(214,251,0,0.3)] transition-all">
                        <i class="fa-brands fa-whatsapp text-lg"></i> Hubungi via WhatsApp
                    </a>
                    <?php else: ?>
                    <button disabled class="w-full sm:flex-1 bg-zinc-800 text-zinc-500 cursor-not-allowed font-black text-xs uppercase tracking-widest py-3.5 px-6 rounded-xl flex items-center justify-center gap-2 transition-all">
                        <i class="fa-brands fa-whatsapp text-lg"></i> WhatsApp Tidak Tersedia
                    </button>
                    <?php endif; ?>
                    
                    <?php if(!empty($row['kantorPemasaran'][0]['email']) && $row['kantorPemasaran'][0]['email'] !== '-'): ?>
                    <a href="mailto:<?= htmlspecialchars($row['kantorPemasaran'][0]['email']) ?>" class="detail-secondary-btn w-full sm:w-auto flex-none bg-[#0a1a1f] hover:bg-[#1a3a40] text-white border border-[#d6fb00]/30 hover:border-[#d6fb00] font-bold text-lg py-3.5 px-5 rounded-xl flex items-center justify-center transition-all" title="Kirim Email">
                        <i class="fa-regular fa-envelope"></i>
                    </a>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start mb-8 w-full">
    
            <div class="lg:col-span-12">
                <div class="bg-[#0f2a30] border border-[#d6fb00]/20 p-6 rounded-2xl shadow-xl">
                    <h3 class="text-sm font-bold text-white mb-6 uppercase tracking-widest flex items-center gap-2 border-b border-[#d6fb00]/20 pb-3">
                        <i class="fa-solid fa-circle-info text-[#d6fb00]"></i> Informasi Umum Unit
                    </h3>
                    
                    <div class="mb-6 bg-[#0a1a1f] border border-[#d6fb00]/20 rounded-xl p-4 flex justify-between items-center">
                        <span class="text-xs font-bold text-zinc-400 uppercase tracking-wide">Nilai Jual Mulai</span>
                        <span class="text-xl md:text-2xl font-black text-[#d6fb00] tracking-tight">
                            Rp <?= isset($row['tipeRumah'][0]['harga']) ? number_format($row['tipeRumah'][0]['harga'], 0, ',', '.') : '0' ?>
                        </span>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-[#0a1a1f] border border-[#d6fb00]/20 p-4 rounded-xl text-center">
                            <i class="fa-solid fa-house-chimney text-zinc-600 mb-1.5 text-base"></i>
                            <span class="block text-[9px] text-zinc-500 font-bold uppercase tracking-wider">Luas Bangunan</span>
                            <span class="text-white font-black text-sm"><?= htmlspecialchars($row['tipeRumah'][0]['luasBangunan'] ?? '-') ?> m²</span>
                        </div>
                        <div class="bg-[#0a1a1f] border border-[#d6fb00]/20 p-4 rounded-xl text-center">
                            <i class="fa-solid fa-maximize text-zinc-600 mb-1.5 text-base"></i>
                            <span class="block text-[9px] text-zinc-500 font-bold uppercase tracking-wider">Luas Tanah</span>
                            <span class="text-white font-black text-sm"><?= htmlspecialchars($row['tipeRumah'][0]['luasTanah'] ?? '-') ?> m²</span>
                        </div>
                        <div class="bg-[#0a1a1f] border border-[#d6fb00]/20 p-4 rounded-xl text-center">
                            <i class="fa-solid fa-bed text-zinc-600 mb-1.5 text-base"></i>
                            <span class="block text-[9px] text-zinc-500 font-bold uppercase tracking-wider">Kamar Tidur</span>
                            <span class="text-white font-black text-sm"><?= htmlspecialchars($row['tipeRumah'][0]['kamarTidur'] ?? '0') ?> Ruang</span>
                        </div>
                        <div class="bg-[#0a1a1f] border border-[#d6fb00]/20 p-4 rounded-xl text-center">
                            <i class="fa-solid fa-shower text-zinc-600 mb-1.5 text-base"></i>
                            <span class="block text-[9px] text-zinc-500 font-bold uppercase tracking-wider">Kamar Mandi</span>
                            <span class="text-white font-black text-sm"><?= htmlspecialchars($row['tipeRumah'][0]['kamarMandi'] ?? '0') ?> Ruang</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="space-y-8 w-full">

            <div class="bg-[#0f2a30] border border-[#d6fb00]/20 p-6 rounded-2xl shadow-xl w-full">
                <h3 class="text-sm font-bold text-white mb-6 uppercase tracking-widest flex items-center gap-2 border-b border-[#d6fb00]/20 pb-3">
                    <i class="fa-solid fa-screwdriver-wrench text-[#d6fb00]"></i> Spesifikasi Teknis Bangunan
                </h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="flex items-start gap-3 p-4 rounded-xl bg-[#0a1a1f] border border-[#d6fb00]/20">
                        <div class="text-[#d6fb00]/80 text-xs mt-0.5"><i class="fa-solid fa-kaaba"></i></div>
                        <div>
                            <span class="block text-[9px] text-zinc-500 font-bold uppercase tracking-wider">Konstruksi Atap</span>
                            <p class="text-zinc-300 text-xs font-semibold mt-0.5"><?= !empty($row['tipeRumah'][0]['atap']) ? htmlspecialchars($row['tipeRumah'][0]['atap']) : 'Rangka baja ringan, penutup genteng metal' ?></p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-4 rounded-xl bg-[#0a1a1f] border border-[#d6fb00]/20">
                        <div class="text-[#d6fb00]/80 text-xs mt-0.5"><i class="fa-solid fa-border-all"></i></div>
                        <div>
                            <span class="block text-[9px] text-zinc-500 font-bold uppercase tracking-wider">Struktur Dinding</span>
                            <p class="text-zinc-300 text-xs font-semibold mt-0.5"><?= !empty($row['tipeRumah'][0]['dinding']) ? htmlspecialchars($row['tipeRumah'][0]['dinding']) : 'Bata ringan/merah, plester aci finish cat' ?></p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-4 rounded-xl bg-[#0a1a1f] border border-[#d6fb00]/20">
                        <div class="text-[#d6fb00]/80 text-xs mt-0.5"><i class="fa-solid fa-table-cells"></i></div>
                        <div>
                            <span class="block text-[9px] text-zinc-500 font-bold uppercase tracking-wider">Komponen Lantai</span>
                            <p class="text-zinc-300 text-xs font-semibold mt-0.5"><?= !empty($row['tipeRumah'][0]['lantai']) ? htmlspecialchars($row['tipeRumah'][0]['lantai']) : 'Keramik motif standar 40x40' ?></p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3 p-4 rounded-xl bg-[#0a1a1f] border border-[#d6fb00]/20">
                        <div class="text-[#d6fb00]/80 text-xs mt-0.5"><i class="fa-solid fa-cubes"></i></div>
                        <div>
                            <span class="block text-[9px] text-zinc-500 font-bold uppercase tracking-wider">Sistem Pondasi</span>
                            <p class="text-zinc-300 text-xs font-semibold mt-0.5"><?= !empty($row['tipeRumah'][0]['pondasi']) ? htmlspecialchars($row['tipeRumah'][0]['pondasi']) : 'Pondasi batu kali dengan sloof beton bertulang' ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-[#0f2a30] border border-[#d6fb00]/20 p-6 rounded-2xl shadow-xl w-full">
                <h3 class="text-sm font-bold text-white mb-6 uppercase tracking-widest flex items-center gap-2 border-b border-[#d6fb00]/20 pb-3">
                    <i class="fa-solid fa-map-location-dot text-[#d6fb00]"></i> Peta Lokasi Perumahan
                </h3>
                
                <div id="map" class="w-full h-96 rounded-xl border border-[#d6fb00]/20 bg-[#0a1a1f] relative z-10"></div>
                
                <div class="mt-4 flex items-center gap-2 text-zinc-500 text-[11px]">
                    <i class="fa-solid fa-circle-info"></i>
                    <span>Gunakan mouse scroll atau cubit layar untuk memperbesar peta lokasi.</span>
                </div>
            </div>

            <div class="bg-[#0f2a30] border border-[#d6fb00]/20 p-6 rounded-2xl shadow-xl w-full">
                <h3 class="text-sm font-bold text-white mb-6 uppercase tracking-widest flex items-center gap-2 border-b border-[#d6fb00]/20 pb-3">
                    <i class="fa-solid fa-image text-[#d6fb00]"></i> Siteplan Perumahan
                </h3>
                
                <div class="w-full min-h-[350px] md:h-[500px] bg-[#0a1a1f] border border-[#d6fb00]/20 rounded-xl overflow-hidden flex items-center justify-center relative group animate-pulse">
                    <?php  $url_asli = $row['siteplan']; 
                            
                            // 2. Cari posisi kata "public" dan potong stringnya
                            $posisi_public = strpos($url_asli, "public");
                            $path_potong = substr($url_asli, $posisi_public);
                            
                            // 3. Bersihkan tanda backslash (\) agar menjadi public/upload/...
                            $path_bersih = stripslashes($path_potong);?>
                    <img src="<?php echo base_url('Index/buka_foto'); ?>?path=<?= urlencode($path_bersih) ?>" 
                        class="w-full h-full object-contain transition-all duration-500 group-hover:scale-[1.01] opacity-0" 
                        alt="Foto Unit Perumahan" loading="lazy" onload="this.parentElement.classList.remove('animate-pulse'); this.classList.remove('opacity-0');">
                        
                    <div class="absolute bottom-4 left-4 bg-black/70 backdrop-blur-md border border-[#d6fb00]/20 px-3 py-1.5 rounded-lg text-[10px] text-zinc-400 pointer-events-none">
                        <i class="fa-solid fa-camera mr-1 text-[#d6fb00]"></i> Tampilan Unit Terkait
                    </div>

                </div>
            </div>

        </div>

    </div>
</section>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Ambil data koordinat dari objek API Sikumbang
        <?php $koordinat = explode(',', $row['koordinatPerumahan']);
        $row['lat']  = isset($koordinat[0]) ? trim($koordinat[0]) : '-7.0051';
        $row['long'] = isset($koordinat[1]) ? trim($koordinat[1]) : '110.4381';
        ?>
        let lat = <?= isset($row['lat']) && !empty($row['lat']) ? $row['lat'] : '-7.0051' ?>; 
        let lng = <?= isset($row['long']) && !empty($row['long']) ? $row['long'] : '110.4381' ?>; 
        let namaPerumahan = "<?= isset($row['namaPerumahan']) ? htmlspecialchars($row['namaPerumahan']) : 'Lokasi Perumahan' ?>";

        // 1. Inisialisasi Kontrol Peta
        const map = L.map('map').setView([lat, lng], 15);

        // 2. Set tile layer terang agar selaras dengan portal tema cerah.
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 20
        }).addTo(map);

        // 3. Custom Marker Berkedip (Glowing Amber) dengan Tailwind
        const amberIcon = L.divIcon({
            html: `<div class="relative flex items-center justify-center">
                    <span class="animate-ping absolute inline-flex h-6 w-6 rounded-full bg-[#d6fb00] opacity-75"></span>
                    <div class="relative bg-[#d6fb00] border-2 border-[#d6fb00]/20 w-4 h-4 rounded-full shadow-lg"></div>
                   </div>`,
            className: 'custom-div-icon',
            iconSize: [24, 24],
            iconAnchor: [12, 12]
        });

        // Generasi URL Google Maps rute/lokasi berdasarkan koordinat variabel lat & lng
        const googleMapsUrl = `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;

        // 4. Pasang Marker dan Popup (Ditambahkan Link Google Maps Interaktif)
        L.marker([lat, lng], { icon: amberIcon }).addTo(map)
            .bindPopup(`<div class="text-[#0a1a1f] font-sans p-1 min-w-[180px]">
                            <b class="text-xs font-black block text-zinc-900 leading-tight mb-0.5">${namaPerumahan}</b>
                            <span class="text-[10px] text-emerald-600 font-bold block mb-3 flex items-center gap-1">
                                <img src="<?= base_url('assets/img/icon-verified.svg') ?>" alt="Verified" class="inline-block w-3 h-3" style="filter: brightness(0) saturate(100%) invert(47%) sepia(78%) saturate(1200%) hue-rotate(145deg) brightness(88%) contrast(101%);"> Unit Lokasi Terverifikasi
                            </span>
                            
                            <a href="${googleMapsUrl}" target="_blank" class="block text-center bg-[#d6fb00] hover:bg-[#c2e600] text-[#0a1a1f] font-black text-[10px] uppercase tracking-wider py-2 px-3 rounded-md shadow transition-colors no-underline decoration-none">
                                <i class="fa-solid fa-diamond-turn-right mr-1"></i> Buka Rute Google Maps
                            </a>
                        </div>`)
            .openPopup();
            
        // Handler perbaikan rendering grid responsif
        setTimeout(function() {
            map.invalidateSize();
        }, 500);
    });
</script>
