<?php
// ── Helpers ──────────────────────────────────────────────────────────
$TAPERA_BASE = 'https://sikumbang.tapera.go.id';

function dev_initials($nama) {
    $skip  = array('PT','CV','UD','PD','TB','FA','CO','LTD','AND','THE');
    $words = preg_split('/\s+/', trim($nama));
    $init  = '';
    foreach ($words as $w) {
        if (!in_array(strtoupper($w), $skip) && mb_strlen($init) < 3) {
            $init .= strtoupper(mb_substr($w, 0, 1));
        }
    }
    return $init ?: strtoupper(mb_substr($nama, 0, 2));
}

$initials     = dev_initials($nama_pengembang);
$total_proyek = count($tapera_data);
$total_unit   = 0;
foreach ($tapera_data as $p) { $total_unit += ($p['jumlahUnit'] ?? 0); }

// Kumpulkan semua foto
$all_photos = array();
foreach ($tapera_data as $p) {
    if (!empty($p['foto'])) {
        foreach ($p['foto'] as $f) {
            $all_photos[] = $f;
            if (count($all_photos) >= 8) break 2;
        }
    }
}

// Harga minimum global
$harga_min = null;
foreach ($tapera_data as $p) {
    foreach (($p['tipeRumah'] ?? array()) as $t) {
        if (!empty($t['harga']) && ($harga_min === null || $t['harga'] < $harga_min)) {
            $harga_min = $t['harga'];
        }
    }
}

// Kabupaten unik
$kab_list = array();
foreach ($tapera_data as $p) {
    $kab = $p['wilayah']['kabupaten'] ?? '';
    if ($kab && !in_array($kab, $kab_list)) $kab_list[] = $kab;
}
?>

<style>
.dev-hero-bg {
    background: linear-gradient(135deg, #0a1f26 0%, #061217 50%, #0d2a20 100%);
}
.glass-card {
    background: rgba(15,42,48,0.7);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(214,251,0,0.12);
}
.glass-card-dark {
    background: rgba(10,26,31,0.8);
    backdrop-filter: blur(12px);
    border: 1px solid rgba(255,255,255,0.06);
}
.project-card {
    background: rgba(15,42,48,0.8);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(255,255,255,0.07);
    transition: all 0.4s ease;
}
.project-card:hover {
    border-color: rgba(214,251,0,0.35);
    transform: translateY(-4px);
    box-shadow: 0 20px 60px rgba(0,0,0,0.4), 0 0 30px rgba(214,251,0,0.06);
}
.stat-box {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 14px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.photo-thumb {
    position: relative;
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.06);
    cursor: pointer;
    aspect-ratio: 4/3;
}
.photo-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}
.photo-thumb:hover img { transform: scale(1.08); }
.photo-thumb .overlay {
    position: absolute; inset: 0;
    background: rgba(0,0,0,0);
    display: flex; align-items: center; justify-content: center;
    transition: background 0.3s;
}
.photo-thumb:hover .overlay { background: rgba(0,0,0,0.4); }
.photo-thumb .overlay i { color: white; font-size: 22px; opacity: 0; transition: opacity 0.3s; }
.photo-thumb:hover .overlay i { opacity: 1; }

.badge-subsidi { background: rgba(16,185,129,0.15); color: #34d399; border: 1px solid rgba(16,185,129,0.3); }
.badge-jenis   { background: rgba(0,0,0,0.5); color: #d1d5db; border: 1px solid rgba(255,255,255,0.12); backdrop-filter: blur(8px); }
.badge-asosiasi{ background: rgba(214,251,0,0.1); color: #d6fb00; border: 1px solid rgba(214,251,0,0.3); }

#lightbox { display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.95); align-items:center; justify-content:center; padding:16px; }
#lightbox.active { display:flex; }
#lightbox img { max-width:100%; max-height:90vh; border-radius:16px; object-fit:contain; }
</style>

<div class="font-outfit">

<?php if (!empty($info_pengembang)): ?>

<!-- ============================================================
     HERO SECTION
============================================================ -->
<div class="dev-hero-bg relative overflow-hidden pt-24 pb-12">
    <!-- Decorative blobs -->
    <div style="position:absolute;top:-100px;right:-100px;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(214,251,0,0.06),transparent 70%);pointer-events:none;"></div>
    <div style="position:absolute;bottom:-150px;left:-100px;width:400px;height:400px;border-radius:50%;background:radial-gradient(circle,rgba(0,163,181,0.05),transparent 70%);pointer-events:none;"></div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">

        <!-- Breadcrumb -->
        <nav style="display:flex;align-items:center;gap:8px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:#71717a;margin-bottom:36px;">
            <a href="<?= base_url() ?>" style="color:#71717a;text-decoration:none;transition:color .2s;" onmouseover="this.style.color='#d6fb00'" onmouseout="this.style.color='#71717a'">
                <i class="fa-solid fa-house" style="margin-right:5px;"></i>Beranda
            </a>
            <i class="fa-solid fa-chevron-right" style="font-size:8px;"></i>
            <a href="<?= base_url('Umum/pengembang') ?>" style="color:#71717a;text-decoration:none;transition:color .2s;" onmouseover="this.style.color='#d6fb00'" onmouseout="this.style.color='#71717a'">Direktori Pengembang</a>
            <i class="fa-solid fa-chevron-right" style="font-size:8px;"></i>
            <span style="color:#d6fb00;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($nama_pengembang) ?></span>
        </nav>

        <!-- Flash -->
        <?php if ($this->session->flashdata('success')): ?>
        <div style="background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.25);color:#34d399;padding:14px 18px;border-radius:14px;font-size:13px;display:flex;align-items:center;gap:10px;margin-bottom:20px;">
            <i class="fa-solid fa-circle-check"></i><?= $this->session->flashdata('success') ?>
        </div>
        <?php endif; ?>

        <!-- Developer Header Row -->
        <div style="display:flex;flex-wrap:wrap;align-items:flex-start;gap:28px;">

            <!-- Logo / Inisial -->
            <div style="flex-shrink:0;">
                <div style="width:130px;height:130px;border-radius:28px;background:linear-gradient(135deg,rgba(214,251,0,.15),rgba(0,163,181,.12));border:2px solid rgba(214,251,0,.35);display:flex;align-items:center;justify-content:center;box-shadow:0 0 40px rgba(214,251,0,.08),0 20px 40px rgba(0,0,0,.3);">
                    <span style="font-size:44px;font-weight:900;color:#d6fb00;letter-spacing:-2px;"><?= $initials ?></span>
                </div>
                <?php if (!empty($info_pengembang['asosiasi']) && $info_pengembang['asosiasi'] !== '-'): ?>
                <div style="margin-top:10px;text-align:center;">
                    <span class="badge-asosiasi" style="display:inline-block;padding:4px 12px;border-radius:999px;font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;">
                        <?= htmlspecialchars(strtoupper($info_pengembang['asosiasi'])) ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Info -->
            <div style="flex:1;min-width:0;">
                <p style="color:#d6fb00;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;margin-bottom:8px;">
                    Pengembang Perumahan &middot; Jawa Tengah
                </p>
                <h1 style="color:#ffffff;font-size:clamp(22px,4vw,40px);font-weight:900;letter-spacing:-1px;line-height:1.15;margin-bottom:20px;word-break:break-word;">
                    <?= htmlspecialchars($nama_pengembang) ?>
                </h1>

                <!-- Stats -->
                <div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:24px;">
                    <div class="stat-box">
                        <i class="fa-solid fa-building" style="color:#d6fb00;font-size:16px;"></i>
                        <div>
                            <div style="color:#fff;font-size:22px;font-weight:900;line-height:1;"><?= $total_proyek ?></div>
                            <div style="color:#71717a;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;margin-top:2px;">Proyek</div>
                        </div>
                    </div>
                    <div class="stat-box">
                        <i class="fa-solid fa-layer-group" style="color:#d6fb00;font-size:16px;"></i>
                        <div>
                            <div style="color:#fff;font-size:22px;font-weight:900;line-height:1;"><?= number_format($total_unit) ?></div>
                            <div style="color:#71717a;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;margin-top:2px;">Total Unit</div>
                        </div>
                    </div>
                    <?php if ($harga_min): ?>
                    <div class="stat-box">
                        <i class="fa-solid fa-tag" style="color:#d6fb00;font-size:16px;"></i>
                        <div>
                            <div style="color:#d6fb00;font-size:22px;font-weight:900;line-height:1;">Rp <?= number_format($harga_min/1000000,0) ?>jt</div>
                            <div style="color:#71717a;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;margin-top:2px;">Harga Mulai</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="stat-box">
                        <i class="fa-solid fa-map-location-dot" style="color:#d6fb00;font-size:16px;"></i>
                        <div>
                            <div style="color:#fff;font-size:22px;font-weight:900;line-height:1;"><?= count($kab_list) ?></div>
                            <div style="color:#71717a;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.1em;margin-top:2px;">Kota/Kab</div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div style="display:flex;flex-wrap:wrap;gap:10px;">
                    <?php if (!empty($info_pengembang['telepon']) && $info_pengembang['telepon'] !== '-'): ?>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$info_pengembang['telepon']) ?>" target="_blank"
                       style="display:inline-flex;align-items:center;gap:8px;background:#22c55e;color:#fff;padding:12px 22px;border-radius:14px;font-weight:800;font-size:13px;text-decoration:none;transition:all .3s;box-shadow:0 4px 20px rgba(34,197,94,.3);"
                       onmouseover="this.style.background='#16a34a';this.style.transform='translateY(-2px)'"
                       onmouseout="this.style.background='#22c55e';this.style.transform='none'">
                        <i class="fa-brands fa-whatsapp" style="font-size:16px;"></i> Hubungi via WA
                    </a>
                    <?php endif; ?>
                    <?php if (!empty($info_pengembang['email']) && $info_pengembang['email'] !== '-'): ?>
                    <a href="mailto:<?= htmlspecialchars($info_pengembang['email']) ?>"
                       style="display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.08);color:#e4e4e7;padding:12px 22px;border-radius:14px;font-weight:700;font-size:13px;text-decoration:none;transition:all .3s;border:1px solid rgba(255,255,255,.15);"
                       onmouseover="this.style.background='rgba(255,255,255,.14)';this.style.transform='translateY(-2px)'"
                       onmouseout="this.style.background='rgba(255,255,255,.08)';this.style.transform='none'">
                        <i class="fa-solid fa-envelope"></i> Email
                    </a>
                    <?php endif; ?>
                    <a href="<?= base_url('Umum/pengembang') ?>"
                       style="display:inline-flex;align-items:center;gap:8px;background:transparent;color:#71717a;padding:12px 18px;border-radius:14px;font-weight:700;font-size:13px;text-decoration:none;transition:all .3s;border:1px solid rgba(255,255,255,.08);"
                       onmouseover="this.style.color='#d6fb00';this.style.borderColor='rgba(214,251,0,.3)'"
                       onmouseout="this.style.color='#71717a';this.style.borderColor='rgba(255,255,255,.08)'">
                        <i class="fa-solid fa-arrow-left"></i> Kembali
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     BODY CONTENT
============================================================ -->
<div style="max-width:1152px;margin:0 auto;padding:40px 16px 80px;display:flex;flex-direction:column;gap:36px;">

    <!-- ── Galeri Foto ─────────────────────────────────────── -->
    <?php if (!empty($all_photos)): ?>
    <div>
        <p style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.14em;color:#d6fb00;margin-bottom:16px;display:flex;align-items:center;gap:8px;">
            <i class="fa-solid fa-images"></i> Galeri Proyek
        </p>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:12px;">
            <?php foreach (array_slice($all_photos, 0, 8) as $i => $foto): ?>
            <div class="photo-thumb" onclick="openLightbox('<?= htmlspecialchars($foto) ?>')">
                <img src="<?= htmlspecialchars($foto) ?>" alt="Foto proyek" loading="lazy"
                     onerror="this.closest('.photo-thumb').style.display='none'">
                <div class="overlay"><i class="fa-solid fa-magnifying-glass-plus"></i></div>
                <?php if ($i === 7 && count($all_photos) > 8): ?>
                <div style="position:absolute;inset:0;background:rgba(0,0,0,.7);display:flex;align-items:center;justify-content:center;color:#fff;font-size:26px;font-weight:900;">
                    +<?= count($all_photos) - 8 ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Info & Wilayah ──────────────────────────────────── -->
    <div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;flex-wrap:wrap;" class="info-grid">
        <!-- Kontak -->
        <div class="glass-card" style="border-radius:24px;padding:24px;">
            <p style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.14em;color:#d6fb00;margin-bottom:18px;"><i class="fa-solid fa-address-card" style="margin-right:6px;"></i>Kontak</p>
            <?php
            $contacts = array(
                array('fa-phone',        'Telepon',  $info_pengembang['telepon']),
                array('fa-envelope',     'Email',    $info_pengembang['email']),
                array('fa-location-dot', 'Alamat',   $info_pengembang['alamat']),
            );
            foreach ($contacts as $c):
                if (empty($c[2]) || $c[2] === '-') continue;
            ?>
            <div style="display:flex;align-items:flex-start;gap:12px;margin-bottom:16px;">
                <div style="width:34px;height:34px;border-radius:10px;background:rgba(214,251,0,.1);border:1px solid rgba(214,251,0,.2);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="fa-solid <?= $c[0] ?>" style="color:#d6fb00;font-size:12px;"></i>
                </div>
                <div>
                    <p style="color:#52525b;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;"><?= $c[1] ?></p>
                    <p style="color:#e4e4e7;font-size:13px;font-weight:600;margin-top:2px;word-break:break-word;"><?= htmlspecialchars($c[2]) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Cakupan Wilayah -->
        <div class="glass-card" style="border-radius:24px;padding:24px;">
            <p style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.14em;color:#d6fb00;margin-bottom:18px;"><i class="fa-solid fa-map" style="margin-right:6px;"></i>Cakupan Wilayah</p>
            <div style="display:flex;flex-wrap:wrap;gap:8px;">
                <?php foreach ($kab_list as $kab): ?>
                <span style="display:inline-flex;align-items:center;gap:6px;padding:6px 14px;background:rgba(10,26,31,.8);border:1px solid rgba(214,251,0,.2);border-radius:10px;color:#d1d5db;font-size:11px;font-weight:600;">
                    <i class="fa-solid fa-location-dot" style="color:rgba(214,251,0,.5);font-size:9px;"></i>
                    <?= htmlspecialchars($kab) ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ── Proyek Perumahan ────────────────────────────────── -->
    <?php if (!empty($tapera_data)): ?>
    <div>
        <div style="display:flex;align-items:center;margin-bottom:20px;">
            <p style="font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.14em;color:#71717a;display:flex;align-items:center;gap:8px;">
                <i class="fa-solid fa-building-user" style="color:#d6fb00;"></i> Proyek Perumahan
            </p>
            <span style="margin-left:auto;padding:4px 14px;background:rgba(214,251,0,.1);color:#d6fb00;border:1px solid rgba(214,251,0,.25);border-radius:999px;font-size:11px;font-weight:800;"><?= $total_proyek ?> proyek</span>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:24px;">
            <?php foreach ($tapera_data as $perum):
                $foto_cover = !empty($perum['foto'][0]) ? $perum['foto'][0] : null;
                $foto_extra = !empty($perum['foto']) ? array_slice($perum['foto'], 1, 3) : array();

                $harga_perum = null;
                $is_subsidi  = false;
                foreach (($perum['tipeRumah'] ?? array()) as $t) {
                    if (!empty($t['harga']) && ($harga_perum === null || $t['harga'] < $harga_perum)) $harga_perum = $t['harga'];
                    if (($t['status'] ?? '') === 'subsidi') $is_subsidi = true;
                }

                $tipe_foto = array();
                foreach (($perum['tipeRumah'] ?? array()) as $t) {
                    if (!empty($t['fotoTampak'])) $tipe_foto[] = $t;
                }
            ?>
            <div class="project-card" style="border-radius:24px;overflow:hidden;">

                <!-- Cover Foto -->
                <div style="position:relative;height:200px;overflow:hidden;background:#0a1a1f;">
                    <?php if ($foto_cover): ?>
                    <img src="<?= htmlspecialchars($foto_cover) ?>"
                         alt="<?= htmlspecialchars($perum['namaPerumahan'] ?? '') ?>"
                         loading="lazy"
                         style="width:100%;height:100%;object-fit:cover;transition:transform .6s ease;cursor:pointer;"
                         onmouseover="this.style.transform='scale(1.08)'"
                         onmouseout="this.style.transform='scale(1)'"
                         onclick="openLightbox(this.src)"
                         onerror="this.parentElement.style.background='#0a1a1f';this.style.display='none'">
                    <?php else: ?>
                    <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;">
                        <i class="fa-solid fa-house-chimney" style="color:#27272a;font-size:56px;"></i>
                    </div>
                    <?php endif; ?>

                    <!-- Gradient overlay bawah -->
                    <div style="position:absolute;bottom:0;left:0;right:0;height:80px;background:linear-gradient(transparent,rgba(10,26,31,.95));pointer-events:none;"></div>

                    <!-- Badges -->
                    <div style="position:absolute;top:12px;left:12px;display:flex;gap:6px;">
                        <?php if ($is_subsidi): ?>
                        <span class="badge-subsidi" style="padding:4px 10px;border-radius:8px;font-size:10px;font-weight:800;text-transform:uppercase;">Subsidi</span>
                        <?php endif; ?>
                        <?php if (!empty($perum['jenisPerumahan'])): ?>
                        <span class="badge-jenis" style="padding:4px 10px;border-radius:8px;font-size:10px;font-weight:700;"><?= htmlspecialchars($perum['jenisPerumahan']) ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Foto count -->
                    <?php if (!empty($perum['foto']) && count($perum['foto']) > 1): ?>
                    <div style="position:absolute;top:12px;right:12px;display:flex;align-items:center;gap:5px;padding:4px 10px;background:rgba(0,0,0,.55);border:1px solid rgba(255,255,255,.12);border-radius:8px;backdrop-filter:blur(8px);">
                        <i class="fa-solid fa-images" style="color:#d1d5db;font-size:10px;"></i>
                        <span style="color:#d1d5db;font-size:10px;font-weight:700;"><?= count($perum['foto']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Content -->
                <div style="padding:20px;">
                    <h3 style="color:#ffffff;font-size:15px;font-weight:800;margin-bottom:4px;line-height:1.3;"><?= htmlspecialchars($perum['namaPerumahan'] ?? '-') ?></h3>
                    <p style="color:#71717a;font-size:12px;margin-bottom:16px;display:flex;align-items:center;gap:6px;">
                        <i class="fa-solid fa-map-location-dot" style="color:rgba(214,251,0,.5);"></i>
                        <?= htmlspecialchars(($perum['wilayah']['kecamatan'] ?? '') . ', ' . ($perum['wilayah']['kabupaten'] ?? '')) ?>
                    </p>

                    <!-- Stats 3-col -->
                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px;margin-bottom:16px;">
                        <div style="background:rgba(10,26,31,.8);border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:10px;text-align:center;">
                            <div style="color:#fff;font-size:16px;font-weight:900;"><?= number_format($perum['jumlahUnit'] ?? 0) ?></div>
                            <div style="color:#52525b;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-top:2px;">Unit</div>
                        </div>
                        <div style="background:rgba(10,26,31,.8);border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:10px;text-align:center;">
                            <div style="color:#fff;font-size:16px;font-weight:900;"><?= count($perum['tipeRumah'] ?? array()) ?></div>
                            <div style="color:#52525b;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-top:2px;">Tipe</div>
                        </div>
                        <div style="background:rgba(10,26,31,.8);border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:10px;text-align:center;">
                            <?php if ($harga_perum): ?>
                            <div style="color:#d6fb00;font-size:16px;font-weight:900;"><?= number_format($harga_perum/1000000,0) ?>jt</div>
                            <div style="color:#52525b;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-top:2px;">Mulai</div>
                            <?php else: ?>
                            <div style="color:#3f3f46;font-size:16px;font-weight:900;">—</div>
                            <div style="color:#52525b;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;margin-top:2px;">Harga</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tipe rumah tags -->
                    <?php if (!empty($perum['tipeRumah'])): ?>
                    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-bottom:16px;">
                        <?php foreach ($perum['tipeRumah'] as $tipe): ?>
                        <span style="padding:3px 10px;border-radius:8px;font-size:10px;font-weight:700;<?= ($tipe['status'] ?? '') === 'subsidi' ? 'background:rgba(16,185,129,.12);color:#34d399;border:1px solid rgba(16,185,129,.25);' : 'background:rgba(214,251,0,.08);color:#d6fb00;border:1px solid rgba(214,251,0,.2);' ?>">
                            <?= htmlspecialchars($tipe['nama'] ?? '') ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Thumbnail foto tambahan -->
                    <?php if (!empty($foto_extra)): ?>
                    <div style="display:flex;gap:6px;margin-bottom:16px;">
                        <?php foreach ($foto_extra as $fe): ?>
                        <div style="flex:1;aspect-ratio:4/3;border-radius:10px;overflow:hidden;border:1px solid rgba(255,255,255,.06);cursor:pointer;"
                             onclick="openLightbox('<?= htmlspecialchars($fe) ?>')">
                            <img src="<?= htmlspecialchars($fe) ?>" alt="" loading="lazy"
                                 style="width:100%;height:100%;object-fit:cover;transition:transform .3s;"
                                 onmouseover="this.style.transform='scale(1.1)'"
                                 onmouseout="this.style.transform='scale(1)'"
                                 onerror="this.parentElement.style.display='none'">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Preview Tampak Rumah -->
                    <?php if (!empty($tipe_foto)): ?>
                    <div style="border-top:1px solid rgba(255,255,255,.06);padding-top:14px;margin-bottom:14px;">
                        <p style="color:#52525b;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.14em;margin-bottom:10px;">Preview Tipe Rumah</p>
                        <div style="display:flex;gap:10px;overflow-x:auto;padding-bottom:4px;">
                            <?php foreach ($tipe_foto as $tipe): ?>
                            <div style="flex-shrink:0;width:120px;">
                                <div style="border-radius:12px;overflow:hidden;border:1px solid rgba(255,255,255,.08);aspect-ratio:4/3;cursor:pointer;"
                                     onclick="openLightbox('<?= $TAPERA_BASE . htmlspecialchars($tipe['fotoTampak']) ?>')">
                                    <img src="<?= $TAPERA_BASE . htmlspecialchars($tipe['fotoTampak']) ?>" alt="Tipe <?= htmlspecialchars($tipe['nama'] ?? '') ?>"
                                         loading="lazy"
                                         style="width:100%;height:100%;object-fit:cover;transition:transform .3s;"
                                         onmouseover="this.style.transform='scale(1.1)'"
                                         onmouseout="this.style.transform='scale(1)'"
                                         onerror="this.parentElement.parentElement.style.display='none'">
                                </div>
                                <p style="color:#a1a1aa;font-size:10px;font-weight:700;text-align:center;margin-top:5px;">Tipe <?= htmlspecialchars($tipe['nama'] ?? '') ?></p>
                                <?php if (!empty($tipe['harga'])): ?>
                                <p style="color:#d6fb00;font-size:10px;font-weight:800;text-align:center;">Rp <?= number_format($tipe['harga']/1000000,0) ?>jt</p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Link Tapera -->
                    <?php if (!empty($perum['idLokasi'])): ?>
                    <a href="https://sikumbang.tapera.go.id/lokasi/<?= htmlspecialchars($perum['idLokasi']) ?>" target="_blank"
                       style="display:flex;align-items:center;justify-content:center;gap:8px;background:rgba(214,251,0,.07);color:#d6fb00;border:1px solid rgba(214,251,0,.2);padding:10px;border-radius:12px;font-size:12px;font-weight:800;text-decoration:none;transition:all .3s;text-transform:uppercase;letter-spacing:.05em;"
                       onmouseover="this.style.background='rgba(214,251,0,.14)';this.style.borderColor='rgba(214,251,0,.4)'"
                       onmouseout="this.style.background='rgba(214,251,0,.07)';this.style.borderColor='rgba(214,251,0,.2)'">
                        <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:11px;"></i>
                        Lihat di Tapera
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── SRP2 ────────────────────────────────────────────── -->
    <?php if (!empty($local_data)): ?>
    <div style="background:linear-gradient(135deg,rgba(16,185,129,.08),rgba(15,42,48,.6));border:1px solid rgba(16,185,129,.2);border-radius:24px;padding:28px;">
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:20px;">
            <div style="width:42px;height:42px;border-radius:12px;background:rgba(16,185,129,.15);border:1px solid rgba(16,185,129,.3);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fa-solid fa-shield-check" style="color:#34d399;"></i>
            </div>
            <div>
                <h2 style="color:#fff;font-size:14px;font-weight:800;">Terverifikasi SRP2</h2>
                <p style="color:#71717a;font-size:11px;">Terdaftar di Sistem Registrasi Pengembang Perumahan</p>
            </div>
            <span style="margin-left:auto;padding:4px 12px;background:rgba(16,185,129,.15);color:#34d399;border:1px solid rgba(16,185,129,.3);border-radius:999px;font-size:10px;font-weight:800;text-transform:uppercase;">
                <i class="fa-solid fa-circle-check" style="margin-right:5px;"></i>Verified
            </span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-bottom:20px;">
            <?php if (!empty($local_data['nib'])): ?>
            <div style="background:rgba(10,26,31,.5);border-radius:14px;padding:14px;border:1px solid rgba(255,255,255,.06);">
                <p style="color:#52525b;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;margin-bottom:4px;">NIB</p>
                <p style="color:#fff;font-weight:700;font-size:13px;"><?= htmlspecialchars($local_data['nib']) ?></p>
            </div>
            <?php endif; ?>
            <?php if (!empty($local_data['asosiasi'])): ?>
            <div style="background:rgba(10,26,31,.5);border-radius:14px;padding:14px;border:1px solid rgba(255,255,255,.06);">
                <p style="color:#52525b;font-size:9px;font-weight:700;text-transform:uppercase;letter-spacing:.12em;margin-bottom:4px;">Asosiasi</p>
                <p style="color:#fff;font-weight:700;font-size:13px;text-transform:uppercase;"><?= htmlspecialchars($local_data['asosiasi']) ?></p>
            </div>
            <?php endif; ?>
        </div>
        <a href="<?= base_url('Umum/download_sertifikat') . '?nama=' . urlencode($nama_pengembang) ?>"
           style="display:inline-flex;align-items:center;gap:8px;background:#d6fb00;color:#000;padding:12px 24px;border-radius:12px;font-weight:900;font-size:13px;text-decoration:none;transition:all .3s;box-shadow:0 4px 20px rgba(214,251,0,.2);"
           onmouseover="this.style.background='#c5e800';this.style.transform='translateY(-2px)'"
           onmouseout="this.style.background='#d6fb00';this.style.transform='none'">
            <i class="fa-solid fa-certificate"></i> Download Sertifikat
        </a>
    </div>
    <?php endif; ?>

</div><!-- /body -->

<?php else: ?>

<!-- Not Found -->
<div style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:100px 16px 40px;background:linear-gradient(135deg,#0a1f26,#061217);">
    <div class="glass-card" style="border-radius:28px;padding:60px 40px;text-align:center;max-width:480px;">
        <div style="width:80px;height:80px;border-radius:50%;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);display:flex;align-items:center;justify-content:center;margin:0 auto 24px;">
            <i class="fa-solid fa-triangle-exclamation" style="color:#ef4444;font-size:34px;"></i>
        </div>
        <h1 style="color:#fff;font-size:22px;font-weight:900;margin-bottom:12px;">Pengembang Tidak Ditemukan</h1>
        <p style="color:#71717a;font-size:13px;margin-bottom:28px;line-height:1.6;">
            Data <span style="color:#fff;font-weight:700;"><?= htmlspecialchars($nama_pengembang) ?></span> tidak ditemukan di direktori Tapera. Coba refresh atau cari pengembang lain.
        </p>
        <a href="<?= base_url('Umum/pengembang') ?>"
           style="display:inline-flex;align-items:center;gap:8px;background:#d6fb00;color:#000;padding:12px 24px;border-radius:12px;font-weight:900;font-size:13px;text-decoration:none;">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Direktori
        </a>
    </div>
</div>

<?php endif; ?>

<!-- ============================================================
     LIGHTBOX
============================================================ -->
<div id="lightbox" onclick="if(event.target===this)closeLightbox()">
    <button onclick="closeLightbox()" style="position:absolute;top:16px;right:16px;width:42px;height:42px;border-radius:50%;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:#fff;font-size:18px;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .2s;z-index:1;" onmouseover="this.style.background='rgba(255,255,255,.2)'" onmouseout="this.style.background='rgba(255,255,255,.1)'">
        <i class="fa-solid fa-xmark"></i>
    </button>
    <img id="lightbox-img" src="" alt="" style="max-width:100%;max-height:90vh;border-radius:18px;object-fit:contain;box-shadow:0 30px 80px rgba(0,0,0,.6);">
</div>

<script>
function openLightbox(src) {
    var lb = document.getElementById('lightbox');
    document.getElementById('lightbox-img').src = src;
    lb.classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeLightbox() {
    var lb = document.getElementById('lightbox');
    lb.classList.remove('active');
    document.getElementById('lightbox-img').src = '';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLightbox();
});

/* Responsive info-grid */
(function() {
    var g = document.querySelector('.info-grid');
    if (!g) return;
    function check() {
        g.style.gridTemplateColumns = window.innerWidth < 768 ? '1fr' : '1fr 2fr';
    }
    check();
    window.addEventListener('resize', check);
})();
</script>
