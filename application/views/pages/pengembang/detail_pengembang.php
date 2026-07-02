<?php
// ── helpers ──────────────────────────────────────────────────────────
$TAPERA = 'https://sikumbang.tapera.go.id';

/**
 * Buat inisial dari nama perusahaan untuk placeholder logo.
 * "PT GRIYA MATARAM NUSANTARA" → "GMN"
 */
function dev_initials($nama) {
    $words = preg_split('/\s+/', $nama);
    $skip  = ['PT', 'CV', 'UD', 'PD', 'TB', 'FA', 'CO', 'LTD'];
    $init  = '';
    foreach ($words as $w) {
        if (!in_array(strtoupper($w), $skip) && strlen($init) < 3) {
            $init .= strtoupper($w[0]);
        }
    }
    return $init ?: strtoupper(substr($nama, 0, 2));
}

$initials = dev_initials($nama_pengembang);
$total_proyek = count($tapera_data);
$total_unit   = array_sum(array_column($tapera_data, 'jumlahUnit'));

// Kumpulkan semua foto proyek dari seluruh perumahan
$all_project_photos = [];
foreach ($tapera_data as $p) {
    if (!empty($p['foto'])) {
        foreach ($p['foto'] as $f) {
            $all_project_photos[] = $f;
            if (count($all_project_photos) >= 8) break 2;
        }
    }
}

// Harga termurah dari semua tipe rumah
$harga_min = null;
foreach ($tapera_data as $p) {
    foreach (($p['tipeRumah'] ?? []) as $t) {
        if (!empty($t['harga']) && ($harga_min === null || $t['harga'] < $harga_min)) {
            $harga_min = $t['harga'];
        }
    }
}

// Kabupaten unik
$kab_list = array_unique(array_filter(array_map(function($p){ return $p['wilayah']['kabupaten'] ?? ''; }, $tapera_data)));
?>

<!— ================================================================
    DETAIL PENGEMBANG — Layout Premium
    ================================================================ —>
<div class="min-h-screen font-outfit">

<?php if (!empty($info_pengembang)): ?>

<!-- ══════════════════════════════════════════════
     HERO HEADER
══════════════════════════════════════════════ -->
<div class="relative overflow-hidden pt-24 pb-0">
    <!-- Background photo blur jika ada foto proyek -->
    <?php if (!empty($all_project_photos[0])): ?>
    <div class="absolute inset-0 z-0">
        <img src="<?= $all_project_photos[0] ?>" alt="" class="w-full h-full object-cover opacity-10 scale-110 blur-sm">
        <div class="absolute inset-0 bg-gradient-to-b from-[#061217]/80 via-[#061217]/70 to-[#061217]"></div>
    </div>
    <?php endif; ?>

    <div class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-16">

        <!-- Breadcrumb -->
        <nav class="mb-10 text-[10px] sm:text-xs text-zinc-500 font-bold uppercase tracking-widest flex items-center gap-2">
            <a href="<?= base_url() ?>" class="hover:text-[#d6fb00] transition-colors">Beranda</a>
            <i class="fa-solid fa-chevron-right text-[8px]"></i>
            <a href="<?= base_url('Umum/pengembang') ?>" class="hover:text-[#d6fb00] transition-colors">Direktori Pengembang</a>
            <i class="fa-solid fa-chevron-right text-[8px]"></i>
            <span class="text-[#d6fb00] truncate max-w-[200px]"><?= htmlspecialchars($nama_pengembang) ?></span>
        </nav>

        <!-- Flash -->
        <?php if ($this->session->flashdata('success')): ?>
        <div class="mb-6 p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-sm font-medium flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i><?= $this->session->flashdata('success') ?>
        </div>
        <?php endif; ?>

        <!-- Developer Card -->
        <div class="flex flex-col lg:flex-row items-start gap-8">

            <!-- Logo / Inisial -->
            <div class="shrink-0">
                <div class="w-28 h-28 lg:w-36 lg:h-36 rounded-3xl bg-gradient-to-br from-[#d6fb00]/20 to-[#00a3b5]/20 border-2 border-[#d6fb00]/30 flex items-center justify-center shadow-2xl shadow-[#d6fb00]/10 backdrop-blur-xl relative overflow-hidden group">
                    <div class="absolute inset-0 bg-gradient-to-br from-[#d6fb00]/10 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                    <span class="text-4xl lg:text-5xl font-black text-[#d6fb00] tracking-tight z-10"><?= $initials ?></span>
                </div>
                <?php if (!empty($info_pengembang['asosiasi']) && $info_pengembang['asosiasi'] !== '-'): ?>
                <div class="mt-3 flex justify-center">
                    <span class="px-3 py-1 bg-[#d6fb00]/10 text-[#d6fb00] border border-[#d6fb00]/30 rounded-full font-black text-[10px] uppercase tracking-widest">
                        <?= htmlspecialchars(strtoupper($info_pengembang['asosiasi'])) ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Info Utama -->
            <div class="flex-1 min-w-0">
                <p class="text-[#d6fb00] font-bold tracking-widest text-xs uppercase mb-2">Pengembang Perumahan · Jawa Tengah</p>
                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black text-white tracking-tight leading-tight mb-4 break-words">
                    <?= htmlspecialchars($nama_pengembang) ?>
                </h1>

                <!-- Stats Row -->
                <div class="flex flex-wrap gap-4 mb-6">
                    <div class="flex items-center gap-2 bg-white/5 border border-white/10 rounded-xl px-4 py-2.5">
                        <i class="fa-solid fa-house-chimney text-[#d6fb00] text-sm"></i>
                        <div>
                            <div class="text-xl font-black text-white leading-none"><?= $total_proyek ?></div>
                            <div class="text-[10px] text-zinc-500 font-semibold uppercase tracking-wider">Proyek</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 bg-white/5 border border-white/10 rounded-xl px-4 py-2.5">
                        <i class="fa-solid fa-layer-group text-[#d6fb00] text-sm"></i>
                        <div>
                            <div class="text-xl font-black text-white leading-none"><?= number_format($total_unit) ?></div>
                            <div class="text-[10px] text-zinc-500 font-semibold uppercase tracking-wider">Total Unit</div>
                        </div>
                    </div>
                    <?php if ($harga_min): ?>
                    <div class="flex items-center gap-2 bg-white/5 border border-white/10 rounded-xl px-4 py-2.5">
                        <i class="fa-solid fa-tag text-[#d6fb00] text-sm"></i>
                        <div>
                            <div class="text-xl font-black text-white leading-none">Rp <?= number_format($harga_min / 1000000, 0) ?>jt</div>
                            <div class="text-[10px] text-zinc-500 font-semibold uppercase tracking-wider">Harga Mulai</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="flex items-center gap-2 bg-white/5 border border-white/10 rounded-xl px-4 py-2.5">
                        <i class="fa-solid fa-map-location-dot text-[#d6fb00] text-sm"></i>
                        <div>
                            <div class="text-xl font-black text-white leading-none"><?= count($kab_list) ?></div>
                            <div class="text-[10px] text-zinc-500 font-semibold uppercase tracking-wider">Kota/Kab</div>
                        </div>
                    </div>
                </div>

                <!-- Kontak Buttons -->
                <div class="flex flex-wrap gap-3">
                    <?php if (!empty($info_pengembang['telepon']) && $info_pengembang['telepon'] !== '-'): ?>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $info_pengembang['telepon']) ?>" target="_blank"
                       class="inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-400 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all duration-300 shadow-lg shadow-emerald-500/20 hover:-translate-y-0.5">
                        <i class="fa-brands fa-whatsapp text-base"></i> Hubungi via WhatsApp
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($info_pengembang['email']) && $info_pengembang['email'] !== '-'): ?>
                    <a href="mailto:<?= htmlspecialchars($info_pengembang['email']) ?>"
                       class="inline-flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all duration-300 border border-white/20 hover:-translate-y-0.5">
                        <i class="fa-solid fa-envelope text-base"></i> Email
                    </a>
                    <?php endif; ?>
                    <a href="<?= base_url('Umum/pengembang') ?>"
                       class="inline-flex items-center gap-2 bg-transparent hover:bg-white/5 text-zinc-400 hover:text-white px-5 py-2.5 rounded-xl font-bold text-sm transition-all duration-300 border border-white/10">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════
     BODY CONTENT
══════════════════════════════════════════════ -->
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-20 space-y-10 -mt-2">

    <!-- ── Photo Gallery (jika ada) ───────────────────────── -->
    <?php if (!empty($all_project_photos)): ?>
    <div>
        <h2 class="text-sm font-black text-zinc-400 uppercase tracking-widest mb-4 flex items-center gap-2">
            <i class="fa-solid fa-images text-[#d6fb00]"></i> Galeri Proyek
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
            <?php foreach (array_slice($all_project_photos, 0, 8) as $i => $foto): ?>
            <div class="relative aspect-[4/3] rounded-2xl overflow-hidden group cursor-pointer border border-white/5 hover:border-[#d6fb00]/30 transition-all duration-300"
                 onclick="openLightbox('<?= htmlspecialchars($foto) ?>')">
                <img src="<?= htmlspecialchars($foto) ?>" alt="Foto proyek"
                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500 ease-out"
                     loading="lazy" onerror="this.parentElement.style.display='none'">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                    <i class="fa-solid fa-magnifying-glass-plus text-white text-xl drop-shadow-lg"></i>
                </div>
                <?php if ($i === 7 && count($all_project_photos) > 8): ?>
                <div class="absolute inset-0 bg-black/70 flex items-center justify-center">
                    <span class="text-white font-black text-2xl">+<?= count($all_project_photos) - 8 ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Info Kontak & Lokasi ───────────────────────────── -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kontak -->
        <div class="lg:col-span-1 bg-[#0f2a30]/60 backdrop-blur-xl rounded-3xl border border-[#d6fb00]/15 p-6 space-y-4">
            <h2 class="text-sm font-black text-[#d6fb00] uppercase tracking-widest flex items-center gap-2">
                <i class="fa-solid fa-address-card"></i> Kontak
            </h2>
            <?php
            $contacts = [
                ['fa-phone',        'Telepon',  $info_pengembang['telepon']],
                ['fa-envelope',     'Email',    $info_pengembang['email']],
                ['fa-location-dot', 'Alamat',   $info_pengembang['alamat']],
            ];
            foreach ($contacts as [$icon, $label, $val]):
                if (empty($val) || $val === '-') continue;
            ?>
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-[#d6fb00]/10 flex items-center justify-center shrink-0">
                    <i class="fa-solid <?= $icon ?> text-[#d6fb00] text-xs"></i>
                </div>
                <div>
                    <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-widest"><?= $label ?></p>
                    <p class="text-white text-sm font-medium mt-0.5"><?= htmlspecialchars($val) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Kabupaten Coverage -->
        <div class="lg:col-span-2 bg-[#0f2a30]/60 backdrop-blur-xl rounded-3xl border border-[#d6fb00]/15 p-6">
            <h2 class="text-sm font-black text-[#d6fb00] uppercase tracking-widest mb-4 flex items-center gap-2">
                <i class="fa-solid fa-map"></i> Cakupan Wilayah
            </h2>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($kab_list as $kab): ?>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-[#0a1a1f] border border-[#d6fb00]/20 rounded-xl text-xs font-semibold text-zinc-300">
                    <i class="fa-solid fa-location-dot text-[#d6fb00]/60 text-[10px]"></i>
                    <?= htmlspecialchars($kab) ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ── Daftar Proyek Perumahan ────────────────────────── -->
    <?php if (!empty($tapera_data)): ?>
    <div>
        <h2 class="text-sm font-black text-zinc-400 uppercase tracking-widest mb-6 flex items-center gap-2">
            <i class="fa-solid fa-building-user text-[#d6fb00]"></i> Proyek Perumahan
            <span class="ml-auto text-xs bg-[#d6fb00]/10 text-[#d6fb00] border border-[#d6fb00]/20 px-3 py-1 rounded-full font-bold"><?= $total_proyek ?> proyek</span>
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php foreach ($tapera_data as $perum):
                $foto_cover = !empty($perum['foto'][0]) ? $perum['foto'][0] : null;
                $harga_perum = null;
                foreach (($perum['tipeRumah'] ?? []) as $t) {
                    if (!empty($t['harga']) && ($harga_perum === null || $t['harga'] < $harga_perum)) {
                        $harga_perum = $t['harga'];
                    }
                }
                $is_subsidi = false;
                foreach (($perum['tipeRumah'] ?? []) as $t) {
                    if (($t['status'] ?? '') === 'subsidi') { $is_subsidi = true; break; }
                }
                $tapera_url = "https://sikumbang.tapera.go.id/lokasi/detail/" . ($perum['idLokasi'] ?? '');
            ?>
            <div class="group bg-[#0f2a30]/60 backdrop-blur-xl rounded-3xl border border-white/5 hover:border-[#d6fb00]/30 overflow-hidden transition-all duration-500 hover:shadow-2xl hover:shadow-[#d6fb00]/5 hover:-translate-y-1">

                <!-- Cover Foto -->
                <div class="relative h-48 overflow-hidden bg-[#0a1a1f]">
                    <?php if ($foto_cover): ?>
                    <img src="<?= htmlspecialchars($foto_cover) ?>" alt="<?= htmlspecialchars($perum['namaPerumahan'] ?? '') ?>"
                         class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700 ease-out"
                         loading="lazy" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                    <div class="absolute inset-0 hidden items-center justify-center" style="display:none">
                        <i class="fa-solid fa-house-chimney text-4xl text-zinc-700"></i>
                    </div>
                    <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center">
                        <i class="fa-solid fa-house-chimney text-5xl text-zinc-700"></i>
                    </div>
                    <?php endif; ?>

                    <!-- Overlay gradient -->
                    <div class="absolute inset-0 bg-gradient-to-t from-[#0f2a30] via-transparent to-transparent"></div>

                    <!-- Badges -->
                    <div class="absolute top-3 left-3 flex gap-2">
                        <?php if ($is_subsidi): ?>
                        <span class="px-2.5 py-1 bg-emerald-500 text-white text-[10px] font-black uppercase rounded-lg shadow-lg">Subsidi</span>
                        <?php endif; ?>
                        <?php if (!empty($perum['jenisPerumahan'])): ?>
                        <span class="px-2.5 py-1 bg-black/60 backdrop-blur-sm text-zinc-200 text-[10px] font-bold uppercase rounded-lg border border-white/10"><?= htmlspecialchars($perum['jenisPerumahan']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Foto count -->
                    <?php if (!empty($perum['foto']) && count($perum['foto']) > 1): ?>
                    <div class="absolute top-3 right-3 flex items-center gap-1 px-2.5 py-1 bg-black/60 backdrop-blur-sm rounded-lg border border-white/10">
                        <i class="fa-solid fa-images text-zinc-300 text-[10px]"></i>
                        <span class="text-zinc-300 text-[10px] font-bold"><?= count($perum['foto']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Content -->
                <div class="p-5">
                    <h3 class="text-white font-black text-base mb-1 group-hover:text-[#d6fb00] transition-colors duration-300 leading-tight">
                        <?= htmlspecialchars($perum['namaPerumahan'] ?? '-') ?>
                    </h3>
                    <p class="text-zinc-500 text-xs mb-4 flex items-center gap-1.5">
                        <i class="fa-solid fa-map-location-dot text-[#d6fb00]/60"></i>
                        <?= htmlspecialchars(($perum['wilayah']['kecamatan'] ?? '') . ', ' . ($perum['wilayah']['kabupaten'] ?? '')) ?>
                    </p>

                    <!-- Stats kecil -->
                    <div class="grid grid-cols-3 gap-2 mb-4">
                        <div class="bg-[#0a1a1f] rounded-xl p-2.5 text-center border border-white/5">
                            <div class="text-sm font-black text-white"><?= number_format($perum['jumlahUnit'] ?? 0) ?></div>
                            <div class="text-[9px] text-zinc-500 font-bold uppercase tracking-wide mt-0.5">Unit</div>
                        </div>
                        <div class="bg-[#0a1a1f] rounded-xl p-2.5 text-center border border-white/5">
                            <div class="text-sm font-black text-white"><?= count($perum['tipeRumah'] ?? []) ?></div>
                            <div class="text-[9px] text-zinc-500 font-bold uppercase tracking-wide mt-0.5">Tipe</div>
                        </div>
                        <div class="bg-[#0a1a1f] rounded-xl p-2.5 text-center border border-white/5">
                            <?php if ($harga_perum): ?>
                            <div class="text-sm font-black text-[#d6fb00]"><?= number_format($harga_perum / 1000000, 0) ?>jt</div>
                            <div class="text-[9px] text-zinc-500 font-bold uppercase tracking-wide mt-0.5">Mulai</div>
                            <?php else: ?>
                            <div class="text-sm font-black text-zinc-600">—</div>
                            <div class="text-[9px] text-zinc-600 font-bold uppercase tracking-wide mt-0.5">Harga</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tipe Rumah Tags -->
                    <?php if (!empty($perum['tipeRumah'])): ?>
                    <div class="flex flex-wrap gap-1.5 mb-4">
                        <?php foreach ($perum['tipeRumah'] as $tipe): ?>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-lg <?= ($tipe['status'] ?? '') === 'subsidi' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-[#d6fb00]/10 text-[#d6fb00] border border-[#d6fb00]/20' ?>">
                            <?= htmlspecialchars($tipe['nama'] ?? '') ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Thumbnail Foto Proyek (max 4) -->
                    <?php if (!empty($perum['foto']) && count($perum['foto']) > 1): ?>
                    <div class="flex gap-1.5 mb-4">
                        <?php foreach (array_slice($perum['foto'], 1, 4) as $f): ?>
                        <div class="flex-1 aspect-square rounded-xl overflow-hidden border border-white/5 cursor-pointer"
                             onclick="openLightbox('<?= htmlspecialchars($f) ?>')">
                            <img src="<?= htmlspecialchars($f) ?>" alt="" class="w-full h-full object-cover hover:scale-110 transition-transform duration-300"
                                 loading="lazy" onerror="this.parentElement.style.display='none'">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Tipe Rumah Detail (fotoTampak) -->
                    <?php
                    $tipe_with_foto = array_filter($perum['tipeRumah'] ?? [], fn($t) => !empty($t['fotoTampak']));
                    if (!empty($tipe_with_foto)):
                    ?>
                    <div class="border-t border-white/5 pt-4 mb-4">
                        <p class="text-[10px] font-black text-zinc-500 uppercase tracking-widest mb-3">Preview Tipe Rumah</p>
                        <div class="flex gap-3 overflow-x-auto pb-1">
                            <?php foreach ($tipe_with_foto as $tipe): ?>
                            <div class="shrink-0 w-32">
                                <div class="aspect-[4/3] rounded-xl overflow-hidden border border-white/10 mb-1.5">
                                    <img src="<?= $TAPERA . htmlspecialchars($tipe['fotoTampak']) ?>" alt="<?= htmlspecialchars($tipe['nama'] ?? '') ?>"
                                         class="w-full h-full object-cover hover:scale-110 transition-transform duration-300 cursor-pointer"
                                         loading="lazy"
                                         onclick="openLightbox('<?= $TAPERA . htmlspecialchars($tipe['fotoTampak']) ?>')"
                                         onerror="this.parentElement.style.display='none'">
                                </div>
                                <p class="text-[10px] font-bold text-zinc-400 text-center truncate">Tipe <?= htmlspecialchars($tipe['nama'] ?? '') ?></p>
                                <?php if (!empty($tipe['harga'])): ?>
                                <p class="text-[10px] font-black text-[#d6fb00] text-center">Rp <?= number_format($tipe['harga'] / 1000000, 0) ?>jt</p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Link ke Tapera -->
                    <?php if (!empty($perum['idLokasi'])): ?>
                    <a href="https://sikumbang.tapera.go.id/lokasi/<?= htmlspecialchars($perum['idLokasi']) ?>" target="_blank"
                       class="w-full flex items-center justify-center gap-2 bg-[#d6fb00]/5 hover:bg-[#d6fb00]/10 text-[#d6fb00] border border-[#d6fb00]/20 hover:border-[#d6fb00]/40 py-2.5 rounded-xl text-xs font-bold transition-all duration-300 group/btn">
                        <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i>
                        Lihat di Tapera
                        <i class="fa-solid fa-chevron-right text-[10px] group-hover/btn:translate-x-1 transition-transform duration-300"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── SRP2 Data (jika ada) ──────────────────────────── -->
    <?php if (!empty($local_data)): ?>
    <div class="bg-gradient-to-r from-emerald-500/10 to-[#0f2a30]/60 backdrop-blur-xl rounded-3xl border border-emerald-500/20 p-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center border border-emerald-500/20">
                <i class="fa-solid fa-shield-check text-emerald-400"></i>
            </div>
            <div>
                <h2 class="text-base font-black text-white">Terverifikasi SRP2</h2>
                <p class="text-xs text-zinc-400">Terdaftar di Sistem Registrasi Pengembang Perumahan</p>
            </div>
            <span class="ml-auto inline-flex items-center px-3 py-1 bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 rounded-full font-black text-[10px] uppercase tracking-wider">
                <i class="fa-solid fa-circle-check mr-1.5"></i>Verified
            </span>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php if (!empty($local_data['nib'])): ?>
            <div class="bg-[#0a1a1f]/50 rounded-2xl p-4 border border-white/5">
                <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-widest mb-1">NIB</p>
                <p class="text-white font-bold"><?= htmlspecialchars($local_data['nib']) ?></p>
            </div>
            <?php endif; ?>
            <?php if (!empty($local_data['asosiasi'])): ?>
            <div class="bg-[#0a1a1f]/50 rounded-2xl p-4 border border-white/5">
                <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-widest mb-1">Asosiasi</p>
                <p class="text-white font-bold uppercase"><?= htmlspecialchars($local_data['asosiasi']) ?></p>
            </div>
            <?php endif; ?>
            <?php if (!empty($local_data['alamat_kantor'])): ?>
            <div class="bg-[#0a1a1f]/50 rounded-2xl p-4 border border-white/5">
                <p class="text-[10px] text-zinc-500 font-bold uppercase tracking-widest mb-1">Alamat</p>
                <p class="text-white font-medium text-sm"><?= htmlspecialchars($local_data['alamat_kantor']) ?></p>
            </div>
            <?php endif; ?>
        </div>
        <div class="mt-6 pt-4 border-t border-emerald-500/10">
            <a href="<?= base_url('Umum/download_sertifikat') . '?nama=' . urlencode($nama_pengembang) ?>"
               class="inline-flex items-center gap-2 bg-[#d6fb00] hover:bg-[#d6fb00]/90 text-black px-6 py-3 rounded-xl font-black text-sm transition-all duration-300 shadow-lg shadow-[#d6fb00]/20 hover:-translate-y-0.5">
                <i class="fa-solid fa-certificate"></i> Download Sertifikat
            </a>
        </div>
    </div>
    <?php endif; ?>

</div><!-- /body -->

<?php else: ?>
<!-- Not Found -->
<div class="pt-24 min-h-screen flex items-center justify-center px-4">
    <div class="bg-[#0f2a30]/80 backdrop-blur-xl rounded-3xl border border-white/10 p-16 text-center max-w-lg">
        <div class="w-24 h-24 rounded-full bg-red-500/10 text-red-500 border border-red-500/20 flex items-center justify-center mx-auto mb-6">
            <i class="fa-solid fa-triangle-exclamation text-4xl"></i>
        </div>
        <h1 class="text-2xl font-black text-white mb-3">Pengembang Tidak Ditemukan</h1>
        <p class="text-zinc-400 mb-8">Data <span class="text-white font-semibold"><?= htmlspecialchars($nama_pengembang) ?></span> tidak ditemukan di direktori Tapera.</p>
        <a href="<?= base_url('Umum/pengembang') ?>" class="inline-flex items-center gap-2 bg-[#d6fb00] text-black px-6 py-3 rounded-xl font-black transition-all hover:-translate-y-0.5">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Direktori
        </a>
    </div>
</div>
<?php endif; ?>

</div><!-- /min-h-screen -->

<!-- ══════════════════════════════════════════════
     LIGHTBOX
══════════════════════════════════════════════ -->
<div id="lightbox" class="fixed inset-0 z-[9999] bg-black/95 backdrop-blur-sm hidden items-center justify-center p-4" onclick="closeLightbox(event)">
    <button onclick="closeLightbox()" class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors z-10">
        <i class="fa-solid fa-xmark text-lg"></i>
    </button>
    <img id="lightbox-img" src="" alt="" class="max-w-full max-h-[90vh] rounded-2xl shadow-2xl object-contain">
</div>

<script>
function openLightbox(src) {
    const lb = document.getElementById('lightbox');
    const img = document.getElementById('lightbox-img');
    img.src = src;
    lb.classList.remove('hidden');
    lb.classList.add('flex');
    document.body.style.overflow = 'hidden';
}
function closeLightbox(e) {
    if (e && e.target !== document.getElementById('lightbox') && !e.target.classList.contains('fa-xmark') && e.target.tagName !== 'BUTTON') return;
    const lb = document.getElementById('lightbox');
    lb.classList.add('hidden');
    lb.classList.remove('flex');
    document.getElementById('lightbox-img').src = '';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox({target: document.getElementById('lightbox')}); });
</script>
