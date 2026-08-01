<!-- Homepage portal: kartu menu berlatar aurora + ikon Font Awesome pejal.
     Latar, batik, dan bibir cahayanya dari .aurora-surface (design-system.css) —
     kelas yang sama dipakai slide program. Tiap kartu cuma menyetor tiga warna
     (--t1 dingin → --t3 hangat) dan memilih satu varian .aurora-1..4. Ujung
     dingin selalu berkumpul di bawah, tempat teks duduk — itu yang menjaga
     kontras teks putih. Semua warna turunan palet Electric Lime x Deep Teal. -->
<div class="mx-auto max-w-6xl p-2 sm:p-4 lg:p-6">
    <div class="space-y-3 sm:space-y-4">
        <!-- Nggolek Omah -->
        <a href="<?= base_url('golek_omah') ?>" data-tab-link data-tab-key="golek_omah"
           class="portal-home-card aurora-surface aurora-1 portal-home-card-hero min-h-[190px] sm:min-h-[250px]"
           style="--t1:#00272f;--t2:#00788b;--t3:#d6fb00">
            <i class="portal-home-art fa-solid fa-house-circle-check" aria-hidden="true"></i>
            <div class="portal-home-body">
                <h2 class="portal-home-title">NGGOLEK<br><span class="portal-home-ghost">OMAH</span></h2>
                <p class="portal-home-subtitle">Cari rumah yang sesuai kebutuhan dan kemampuan.</p>
            </div>
        </a>

        <!-- Sertifikasi Pengembang -->
        <a href="<?= base_url('Pengembang/sertifikasi') ?>" data-tab-link data-tab-key="pengembang_list"
           class="portal-home-card aurora-surface aurora-2 min-h-[150px] sm:min-h-[185px]"
           style="--t1:#052b31;--t2:#0e7a6a;--t3:#e9d04b">
            <i class="portal-home-art fa-solid fa-award" aria-hidden="true"></i>
            <div class="portal-home-body">
                <h2 class="portal-home-title portal-home-title-sm">SERTIFIKASI<br><span class="portal-home-ghost">PENGEMBANG</span></h2>
                <p class="portal-home-subtitle">Pendaftaran dan daftar SRP2.</p>
            </div>
        </a>

        <!-- Menu layanan utama -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4">
            <!-- Placeholder sampai subhalaman PSU tersedia -->
            <div class="portal-home-card aurora-surface aurora-3 portal-home-card-placeholder min-h-[170px] sm:min-h-[245px]"
                 style="--t1:#041f2c;--t2:#15607a;--t3:#8fd6b4" aria-label="PSU, segera hadir">
                <i class="portal-home-art fa-solid fa-road-bridge" aria-hidden="true"></i>
                <div class="portal-home-body">
                    <h2 class="portal-home-title portal-home-title-sm">PSU</h2>
                    <span class="portal-home-badge">Segera Hadir</span>
                </div>
            </div>

            <!-- Placeholder sampai subhalaman kawasan kumuh tersedia -->
            <div class="portal-home-card aurora-surface aurora-4 portal-home-card-placeholder min-h-[170px] sm:min-h-[245px]"
                 style="--t1:#2a1712;--t2:#96502a;--t3:#efb457" aria-label="Kawasan kumuh, segera hadir">
                <i class="portal-home-art fa-solid fa-city" aria-hidden="true"></i>
                <div class="portal-home-body">
                    <h2 class="portal-home-title portal-home-title-sm">KAWASAN<br><span class="portal-home-ghost">KUMUH</span></h2>
                    <span class="portal-home-badge">Segera Hadir</span>
                </div>
            </div>
        </div>

        <!-- Rekam Data menunjuk modulnya sendiri; pengunjung tanpa sesi diarahkan
             ke login, dan itu memang benar — merekam capaian wewenang admin kab/kota. -->
        <a href="<?= base_url('Rekam_Data') ?>" class="portal-home-card aurora-surface aurora-2 min-h-[130px] sm:min-h-[155px]"
           style="--t1:#032b26;--t2:#0a7857;--t3:#c3f24a">
            <i class="portal-home-art fa-solid fa-chart-column" aria-hidden="true"></i>
            <div class="portal-home-body">
                <h2 class="portal-home-title portal-home-title-sm">REKAM DATA</h2>
                <p class="portal-home-subtitle">Rekam capaian perumahan kabupaten/kota.</p>
            </div>
        </a>

        <!-- Akses cepat -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-3">
            <a href="<?= base_url('umum/forum') ?>" data-tab-link data-tab-key="forum"
               class="portal-home-card aurora-surface aurora-3 portal-home-card-compact min-h-[150px] sm:min-h-[195px]"
               style="--t1:#04203a;--t2:#10608f;--t3:#66dcd2">
                <i class="portal-home-art fa-solid fa-comments" aria-hidden="true"></i>
                <div class="portal-home-body">
                    <h3 class="portal-home-title portal-home-title-xs">KONSULTASI</h3>
                    <p class="portal-home-subtitle">Terjadwal</p>
                </div>
            </a>
            <a href="<?= base_url('umum/aduan') ?>" data-tab-link data-tab-key="aduan"
               class="portal-home-card aurora-surface aurora-1 portal-home-card-compact min-h-[150px] sm:min-h-[195px]"
               style="--t1:#2b1226;--t2:#8e3560;--t3:#f2a48a">
                <i class="portal-home-art fa-solid fa-bullhorn" aria-hidden="true"></i>
                <div class="portal-home-body">
                    <h3 class="portal-home-title portal-home-title-xs">ADUAN</h3>
                    <p class="portal-home-subtitle">Pertanyaan warga</p>
                </div>
            </a>
            <a href="<?= base_url('KemitraanPortal') ?>" data-tab-link data-tab-key="kemitraan"
               class="portal-home-card aurora-surface aurora-4 portal-home-card-compact min-h-[150px] sm:min-h-[195px]"
               style="--t1:#0b1d3a;--t2:#2f5f8f;--t3:#a9e07a">
                <i class="portal-home-art fa-solid fa-graduation-cap" aria-hidden="true"></i>
                <div class="portal-home-body">
                    <h3 class="portal-home-title portal-home-title-xs">KKN &amp;<br>MAGANG</h3>
                    <p class="portal-home-subtitle">Universitas dan mahasiswa</p>
                </div>
            </a>
        </div>

        <!-- Slideshow program strategis: komponen reusable yang sama dengan halaman diagnosa -->
        <section class="w-full pt-2" aria-label="Program strategis">
            <?php $carousel_context = 'home'; $this->load->view('components/program_showcase_carousel'); ?>
        </section>
    </div>
</div>

<style>
    /* Warna, batik, kilap, dan bibir cahaya datang dari .aurora-surface di
       assets/css/design-system.css — dipakai bareng slide program. Yang tinggal
       di sini cuma tata letak kartunya. */
    .portal-home-card {
        position: relative;
        display: flex;
        align-items: flex-end;
        overflow: hidden;
        isolation: isolate;
        border-radius: 1.5rem;
        padding: 1.35rem 1.5rem;
        /* Easing memantul: cepat berangkat, melambat mendarat. Durasi turun
           dipendekkan lewat :not(:hover) supaya kembalinya tidak terasa berat. */
        transition: transform .34s cubic-bezier(.16, .84, .28, 1), box-shadow .34s ease;
    }
    .portal-home-card:not(:hover) { transition-duration: .22s; }

    .portal-home-card:not(.portal-home-card-placeholder):hover,
    .portal-home-card:not(.portal-home-card-placeholder):focus-visible {
        transform: translateY(-5px) scale(1.008);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, .5),
            inset 0 -1px 0 rgba(0, 0, 0, .22),
            0 2px 4px rgba(2, 18, 24, .22),
            0 22px 38px -14px rgba(2, 18, 24, .6);
    }
    /* Ditekan: kartu kembali rata, bibir atas meredup */
    .portal-home-card:not(.portal-home-card-placeholder):active {
        transform: translateY(0);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, .18),
            inset 0 2px 6px rgba(0, 0, 0, .22),
            0 1px 2px rgba(2, 18, 24, .25);
    }
    /* Ikon ikut maju sedikit dan menguat saat hover */
    .portal-home-card:not(.portal-home-card-placeholder):hover .portal-home-art,
    .portal-home-card:not(.portal-home-card-placeholder):focus-visible .portal-home-art {
        transform: translateY(-50%) translateX(-.5rem) scale(1.08) rotate(-4deg);
        opacity: .26;
    }
    /* Teks naik tipis mengikuti kartu */
    .portal-home-card:not(.portal-home-card-placeholder):hover .portal-home-body,
    .portal-home-card:not(.portal-home-card-placeholder):focus-visible .portal-home-body {
        transform: translateY(-2px);
    }
    .portal-home-card:focus-visible { outline: 3px solid #fff; outline-offset: 3px; }
    .portal-home-card-placeholder { cursor: not-allowed; filter: saturate(.8); }

    /* Ilustrasi: ikon Font Awesome Free 6 (solid) yang sudah dimuat di head.php —
       digambar desainer, jadi tidak perlu path buatan sendiri. Satu warna putih,
       ukurannya dibesarkan sampai menembus tepi kanan kartu. */
    .portal-home-art {
        position: absolute;
        right: -1.5rem;
        top: 50%;
        /* Di atas lapisan kilap (::after), supaya ikon tidak tertutup gradasi
           putih yang bikin tepinya terlihat berbayang. Teks tetap di z-index 2. */
        z-index: 1;
        transform: translateY(-50%);
        color: #fff;
        font-size: min(8.5rem, 30vw);
        line-height: 1;
        opacity: .15;
        pointer-events: none;
        transition: transform .4s cubic-bezier(.2, .8, .25, 1), opacity .3s ease;
    }
    .portal-home-card-compact .portal-home-art { right: -1rem; font-size: min(6rem, 24vw); }
    .portal-home-card-hero .portal-home-art { font-size: min(12rem, 29vw); opacity: .17; }

    .portal-home-body { position: relative; z-index: 2; max-width: 62%; transition: transform .28s cubic-bezier(.2, .8, .25, 1); }
    .portal-home-card-compact { padding: 1.1rem 1.15rem; }
    .portal-home-card-compact .portal-home-body { max-width: 78%; }

    .portal-home-title {
        margin: 0;
        color: #fff;
        font-size: clamp(1.7rem, 4vw, 3.1rem);
        font-weight: 800;
        line-height: 1.02;
        letter-spacing: -.01em;
        text-shadow: 0 2px 10px rgba(0, 0, 0, .3);
    }
    .portal-home-title-sm { font-size: clamp(1.35rem, 2.8vw, 2.1rem); }
    .portal-home-title-xs { font-size: clamp(1.1rem, 2vw, 1.45rem); line-height: 1.1; }
    .portal-home-ghost { color: rgba(255, 255, 255, .52); }
    .portal-home-subtitle {
        margin: .5rem 0 0;
        color: rgba(255, 255, 255, .9);
        font-size: clamp(.8rem, 1.4vw, 1rem);
        line-height: 1.4;
        text-shadow: 0 1px 6px rgba(0, 0, 0, .35);
    }
    .portal-home-badge {
        display: inline-block;
        margin-top: .7rem;
        border: 1px solid rgba(255, 255, 255, .5);
        border-radius: 999px;
        padding: .3rem .75rem;
        /* Lapisan gelap tipis: badge kecil ini bisa jatuh di bagian aurora yang
           terang, dan putih-di-atas-terang gagal kontras kalau tanpa alas. */
        background: rgba(0, 0, 0, .22);
        color: #fff;
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .09em;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .4), 0 1px 2px rgba(0, 0, 0, .25);
    }

    /* "Reduce motion": gerakannya diperkecil dan dipercepat, tidak dimatikan —
       user minta animasi hover ini tetap terasa (1 Agu 2026). Angkatan tinggal
       2px, teks diam, ikon berhenti berputar. */
    @media (prefers-reduced-motion: reduce) {
        .portal-home-card { transition-duration: .14s; }
        .portal-home-art { transition-duration: .14s; }
        .portal-home-body { transition: none; }
        .portal-home-card:not(.portal-home-card-placeholder):hover,
        .portal-home-card:not(.portal-home-card-placeholder):focus-visible { transform: translateY(-2px); }
        .portal-home-card:not(.portal-home-card-placeholder):hover .portal-home-art,
        .portal-home-card:not(.portal-home-card-placeholder):focus-visible .portal-home-art { transform: translateY(-50%) scale(1.04); }
        .portal-home-card:not(.portal-home-card-placeholder):hover .portal-home-body,
        .portal-home-card:not(.portal-home-card-placeholder):focus-visible .portal-home-body { transform: none; }
    }
</style>
