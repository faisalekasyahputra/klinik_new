<!-- Homepage portal: kartu menu berlatar aurora + ikon Font Awesome pejal.
     Latar, batik, dan bibir cahayanya dari .aurora-surface (design-system.css) -
     kelas yang sama dipakai slide program. Tiap kartu cuma menyetor tiga warna
     (--t1 paling muda → --t3 paling pekat) dan memilih satu varian .aurora-1..4.
     Ujung --t1 selalu berkumpul di bawah, tempat teks duduk - itu yang menjaga
     kontras tinta gelap. Palet pastel arahan dinas (5 Agt 2026); satu rona per
     kartu, --t3 sekaligus jadi warna ilustrasinya. -->
<div class="mx-auto max-w-6xl p-2 sm:p-4 lg:p-6">
    <div class="space-y-3 sm:space-y-4">
        <!-- Golek Omah -->
        <a href="<?= base_url('golek_omah') ?>" data-tab-link data-tab-key="golek_omah"
           class="portal-home-card aurora-surface aurora-1 portal-home-card-hero min-h-[190px] sm:min-h-[250px]"
           style="--t1:#eaf7ef;--t2:#c7e9d4;--t3:#8ed6ab">
            <i class="portal-home-art fa-solid fa-house-circle-check" aria-hidden="true"></i>
            <div class="portal-home-body">
                <h2 class="portal-home-title">GOLEK<br><span class="portal-home-ghost">OMAH</span></h2>
                <p class="portal-home-subtitle">Cari rumah yang sesuai kebutuhan dan kemampuan.</p>
            </div>
        </a>

        <!-- Sertifikasi Pengembang -->
        <a href="<?= base_url('Pengembang/sertifikasi') ?>" data-tab-link data-tab-key="pengembang_list"
           class="portal-home-card aurora-surface aurora-2 min-h-[150px] sm:min-h-[185px]"
           style="--t1:#f1eafb;--t2:#d9c9f0;--t3:#b39fe2">
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
                 style="--t1:#e8f2fc;--t2:#c5ddf4;--t3:#92bfe7" aria-label="PSU, segera hadir">
                <i class="portal-home-art fa-solid fa-road-bridge" aria-hidden="true"></i>
                <div class="portal-home-body">
                    <h2 class="portal-home-title portal-home-title-sm">PSU</h2>
                    <span class="portal-home-badge">Segera Hadir</span>
                </div>
            </div>

            <!-- Placeholder sampai subhalaman kawasan kumuh tersedia -->
            <div class="portal-home-card aurora-surface aurora-4 portal-home-card-placeholder min-h-[170px] sm:min-h-[245px]"
                 style="--t1:#fdeae3;--t2:#f8c8ba;--t3:#ef9d8a" aria-label="Kawasan kumuh, segera hadir">
                <i class="portal-home-art fa-solid fa-city" aria-hidden="true"></i>
                <div class="portal-home-body">
                    <h2 class="portal-home-title portal-home-title-sm">KAWASAN<br><span class="portal-home-ghost">KUMUH</span></h2>
                    <span class="portal-home-badge">Segera Hadir</span>
                </div>
            </div>
        </div>

        <!-- Rekam Data menunjuk modulnya sendiri; pengunjung tanpa sesi diarahkan
             ke login, dan itu memang benar - merekam capaian wewenang admin kab/kota. -->
        <a href="<?= base_url('Rekam_Data') ?>" class="portal-home-card aurora-surface aurora-2 min-h-[130px] sm:min-h-[155px]"
           style="--t1:#fdf4da;--t2:#fadfa3;--t3:#f2c568">
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
               style="--t1:#dcf3f0;--t2:#a5ded9;--t3:#68c8bf">
                <i class="portal-home-art fa-solid fa-comments" aria-hidden="true"></i>
                <div class="portal-home-body">
                    <h3 class="portal-home-title portal-home-title-xs">KONSULTASI</h3>
                    <p class="portal-home-subtitle">Terjadwal</p>
                </div>
            </a>
            <a href="<?= base_url('umum/aduan') ?>" data-tab-link data-tab-key="aduan"
               class="portal-home-card aurora-surface aurora-1 portal-home-card-compact min-h-[150px] sm:min-h-[195px]"
               style="--t1:#fce5ee;--t2:#f8bed3;--t3:#ef8cb2">
                <i class="portal-home-art fa-solid fa-bullhorn" aria-hidden="true"></i>
                <div class="portal-home-body">
                    <h3 class="portal-home-title portal-home-title-xs">ADUAN</h3>
                    <p class="portal-home-subtitle">Pertanyaan warga</p>
                </div>
            </a>
            <a href="<?= base_url('KemitraanPortal') ?>" data-tab-link data-tab-key="kemitraan"
               class="portal-home-card aurora-surface aurora-4 portal-home-card-compact min-h-[150px] sm:min-h-[195px]"
               style="--t1:#e0e8fb;--t2:#b7c8f3;--t3:#8aa4e8">
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
       assets/css/design-system.css - dipakai bareng slide program. Yang tinggal
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
            inset 0 1px 0 rgba(255, 255, 255, .9),
            inset 0 -1px 0 rgba(22, 50, 58, .07),
            0 2px 4px rgba(22, 50, 58, .07),
            0 22px 38px -16px rgba(22, 50, 58, .32);
    }
    /* Ditekan: kartu kembali rata, bibir atas meredup */
    .portal-home-card:not(.portal-home-card-placeholder):active {
        transform: translateY(0);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, .4),
            inset 0 2px 6px rgba(22, 50, 58, .12),
            0 1px 2px rgba(22, 50, 58, .1);
    }
    /* Ikon ikut maju sedikit dan menguat saat hover */
    .portal-home-card:not(.portal-home-card-placeholder):hover .portal-home-art,
    .portal-home-card:not(.portal-home-card-placeholder):focus-visible .portal-home-art {
        transform: translateY(-50%) translateX(-.5rem) scale(1.08) rotate(-4deg);
        opacity: 1;
    }
    /* Teks naik tipis mengikuti kartu */
    .portal-home-card:not(.portal-home-card-placeholder):hover .portal-home-body,
    .portal-home-card:not(.portal-home-card-placeholder):focus-visible .portal-home-body {
        transform: translateY(-2px);
    }
    .portal-home-card:focus-visible { outline: 3px solid var(--ink); outline-offset: 3px; }
    .portal-home-card-placeholder { cursor: not-allowed; filter: saturate(.8); }

    /* Ilustrasi: ikon Font Awesome Free 6 (solid) yang sudah dimuat di head.php -
       digambar desainer, jadi tidak perlu path buatan sendiri. Warnanya --t3,
       ujung terpekat palet kartu itu sendiri, jadi ikon selalu satu rona dengan
       latarnya tanpa perlu warna kesembilan. (Dulu putih 15% - di atas pastel
       itu praktis lenyap.) Ukurannya dibesarkan sampai menembus tepi kanan. */
    .portal-home-art {
        position: absolute;
        right: -1.5rem;
        top: 50%;
        /* Di atas lapisan kilap (::after), supaya ikon tidak tertutup gradasi
           putih yang bikin tepinya terlihat berbayang. Teks tetap di z-index 2. */
        z-index: 1;
        transform: translateY(-50%);
        color: var(--t3);
        font-size: min(8.5rem, 30vw);
        line-height: 1;
        opacity: .85;
        /* Emboss ke DALAM (letterpress): gelap mengintip di tepi ATAS, terang di
           tepi BAWAH - arah cahaya dari atas, jadi mata membacanya sebagai bentuk
           yang dicetak masuk ke permukaan kartu, bukan yang menonjol keluar.
           (Menonjol = kebalikannya; tertukar arah dan efeknya terbalik total.)
           Satuan `em`, bukan px: ikon ini clamp dari ~90px sampai 190px, dan
           offset px yang pas di kartu ringkas akan lenyap di kartu hero. */
        text-shadow:
            0 -.022em .03em rgba(22, 50, 58, .30),
            0  .024em 0     rgba(255, 255, 255, .80);
        pointer-events: none;
        transition: transform .4s cubic-bezier(.2, .8, .25, 1), opacity .3s ease;
    }
    .portal-home-card-compact .portal-home-art { right: -1rem; font-size: min(6rem, 24vw); }
    .portal-home-card-hero .portal-home-art { font-size: min(12rem, 29vw); opacity: .9; }

    .portal-home-body { position: relative; z-index: 2; max-width: 62%; transition: transform .28s cubic-bezier(.2, .8, .25, 1); }
    .portal-home-card-compact { padding: 1.1rem 1.15rem; }
    .portal-home-card-compact .portal-home-body { max-width: 78%; }

    /* Tinta gelap datang dari .aurora-surface lewat pewarisan; yang ditulis di
       sini cuma turunannya (--ink pudar untuk ghost & subjudul). Bayangan teks
       DIBUANG, bukan dibalik jadi putih: di atas pastel muda ia cuma bikin
       huruf terlihat berkabut. */
    .portal-home-title {
        margin: 0;
        font-size: clamp(1.7rem, 4vw, 3.1rem);
        font-weight: 800;
        line-height: 1.02;
        letter-spacing: -.01em;
    }
    .portal-home-title-sm { font-size: clamp(1.35rem, 2.8vw, 2.1rem); }
    .portal-home-title-xs { font-size: clamp(1.1rem, 2vw, 1.45rem); line-height: 1.1; }
    /* Opasitas DIUKUR, bukan dikira-kira: latar aurora dirasterisasi ke kanvas,
       lapisan batik gelap ditumpuk di atasnya, lalu piksel TERGELAP di dalam
       kotak teks tiap kartu dicuplik. Dari situ dicari alpha terkecil yang masih
       lolos 4.5:1 (subjudul) dan 3:1 (ghost - teks besar) di kesembilan
       permukaan: .79 dan .61. Dipakai .82/.65, disisakan margin karena batasnya
       bergantung palet - kartu baru dengan rona lebih pekat tidak boleh langsung
       jatuh. Judul terendah 7.17:1.

       Batiknya WAJIB ikut dihitung: mengukur gradien saja pernah meloloskan
       KONSULTASI di 4.61 padahal dengan batik ia 4.44. */
    .portal-home-ghost { opacity: .65; }
    .portal-home-subtitle {
        margin: .5rem 0 0;
        opacity: .82;
        font-size: clamp(.8rem, 1.4vw, 1rem);
        line-height: 1.4;
    }
    .portal-home-badge {
        display: inline-block;
        margin-top: .7rem;
        border: 1px solid rgba(255, 255, 255, .85);
        border-radius: 999px;
        padding: .3rem .75rem;
        /* Alas putih tipis: badge kecil ini bisa jatuh di bagian kartu yang
           paling pekat, dan tinta-di-atas-pekat gagal kontras kalau tanpa alas. */
        background: rgba(255, 255, 255, .62);
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .09em;
        box-shadow: 0 1px 2px rgba(22, 50, 58, .1);
    }

    /* "Reduce motion": gerakannya diperkecil dan dipercepat, tidak dimatikan -
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
