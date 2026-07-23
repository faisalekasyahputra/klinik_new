<!-- Homepage portal: susunan mengikuti layout referensi -->
<div class="mx-auto max-w-6xl p-2 sm:p-4 lg:p-6">
    <div class="space-y-3 sm:space-y-4">
        <!-- Nggolek Omah -->
        <a href="<?= base_url('golek_omah') ?>" data-tab-link data-tab-key="golek_omah"
           class="portal-home-card portal-home-card-primary group min-h-[150px] sm:min-h-[175px]">
            <i class="fa-solid fa-house-chimney-window portal-home-watermark"></i>
            <div class="relative z-10 text-center">
                <i class="fa-solid fa-house-chimney-window portal-home-icon"></i>
                <h2 class="portal-home-title">NGGOLEK OMAH</h2>
                <p class="portal-home-subtitle">(Cari Rumah)</p>
            </div>
        </a>

        <!-- Sertifikasi Pengembang -->
        <a href="<?= base_url('Pengembang/sertifikasi') ?>" data-tab-link data-tab-key="pengembang_list"
           class="portal-home-card group min-h-[120px] sm:min-h-[145px]">
            <i class="fa-solid fa-certificate portal-home-watermark"></i>
            <div class="relative z-10 text-center">
                <i class="fa-solid fa-certificate portal-home-icon"></i>
                <h2 class="portal-home-title text-xl sm:text-3xl">SERTIFIKASI PENGEMBANG</h2>
                <p class="portal-home-subtitle">(SRP2)</p>
            </div>
        </a>

        <!-- Menu layanan utama -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4">
            <!-- Placeholder sampai subhalaman PSU tersedia -->
            <div class="portal-home-card portal-home-card-placeholder min-h-[170px] sm:min-h-[245px]" aria-label="PSU, segera hadir">
                <i class="fa-solid fa-road portal-home-watermark"></i>
                <div class="relative z-10 text-center">
                    <i class="fa-solid fa-road portal-home-icon"></i>
                    <h2 class="portal-home-title text-2xl sm:text-4xl">PSU</h2>
                    <span class="portal-home-badge">Segera Hadir</span>
                </div>
            </div>

            <!-- Placeholder sampai subhalaman kawasan kumuh tersedia -->
            <div class="portal-home-card portal-home-card-placeholder min-h-[170px] sm:min-h-[245px]" aria-label="Kawasan kumuh, segera hadir">
                <i class="fa-solid fa-city portal-home-watermark"></i>
                <div class="relative z-10 text-center">
                    <i class="fa-solid fa-city portal-home-icon"></i>
                    <h2 class="portal-home-title text-2xl sm:text-4xl">KAWASAN<br>KUMUH</h2>
                    <span class="portal-home-badge">Segera Hadir</span>
                </div>
            </div>
        </div>

        <!-- Rekam Data -->
        <a href="<?= base_url('tab/bankdata') ?>" class="portal-home-card group min-h-[105px] sm:min-h-[125px]">
            <i class="fa-solid fa-chart-line portal-home-watermark"></i>
                <div class="relative z-10 text-center">
                    <i class="fa-solid fa-chart-line portal-home-icon"></i>
                    <h2 class="portal-home-title text-xl sm:text-3xl">REKAM DATA</h2>
            </div>
        </a>

        <!-- Akses cepat -->
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-2 lg:gap-3">
            <a href="<?= base_url('umum/forum') ?>" data-tab-link data-tab-key="forum" class="portal-home-card portal-home-card-compact group min-h-[130px] sm:min-h-[180px]">
                <i class="fa-solid fa-comments portal-home-watermark"></i>
                <div class="relative z-10 text-center">
                    <i class="fa-solid fa-comments portal-home-icon"></i>
                    <h3 class="portal-home-title text-lg sm:text-xl">KONSULTASI</h3>
                    <p class="portal-home-subtitle">(Terjadwal)</p>
                </div>
            </a>
            <a href="<?= base_url('umum/aduan') ?>" data-tab-link data-tab-key="aduan" class="portal-home-card portal-home-card-compact group min-h-[130px] sm:min-h-[180px]">
                <i class="fa-solid fa-circle-question portal-home-watermark"></i>
                <div class="relative z-10 text-center">
                    <i class="fa-solid fa-circle-question portal-home-icon"></i>
                    <h3 class="portal-home-title text-lg sm:text-xl">ADUAN</h3>
                    <p class="portal-home-subtitle">(Pertanyaan)</p>
                </div>
            </a>
            <a href="<?= base_url('KemitraanPortal') ?>" data-tab-link data-tab-key="kemitraan" class="portal-home-card portal-home-card-compact group min-h-[130px] sm:min-h-[180px]">
                <i class="fa-solid fa-user-graduate portal-home-watermark"></i>
                <div class="relative z-10 text-center">
                    <i class="fa-solid fa-user-graduate portal-home-icon"></i>
                    <h3 class="portal-home-title text-lg sm:text-xl">KKN DAN MAGANG</h3>
                    <p class="portal-home-subtitle">(Universitas dan Mahasiswa)</p>
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
    .portal-home-card {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        border: 1px solid var(--portal-border);
        border-radius: 1rem;
        background: var(--portal-bg-card);
        color: var(--portal-text);
        box-shadow: var(--portal-shadow);
        padding: 1.25rem;
        transition: transform .2s ease, border-color .2s ease, box-shadow .2s ease;
    }
    .portal-home-card:not(.portal-home-card-placeholder):hover,
    .portal-home-card:not(.portal-home-card-placeholder):focus-visible {
        transform: translateY(-2px);
        border-color: rgba(0, 163, 181, .35);
        box-shadow: 0 8px 22px rgba(0, 80, 95, .12);
    }
    .portal-home-card:focus-visible { outline: 3px solid rgba(0, 163, 181, .35); outline-offset: 2px; }
    .portal-home-card-placeholder { opacity: .62; cursor: not-allowed; }
    .portal-home-card-compact { padding: 1rem .75rem; }
    .portal-home-card-compact .portal-home-icon { margin-bottom: .45rem; font-size: 1.35rem; }
    .portal-home-card-compact .portal-home-title { font-size: clamp(1rem, 2vw, 1.45rem); }
    .portal-home-card-compact .portal-home-subtitle { font-size: clamp(.75rem, 1.4vw, .95rem); }
    .portal-home-watermark {
        position: absolute;
        right: -1rem;
        bottom: -1.25rem;
        color: var(--portal-icon);
        font-size: 6rem;
        opacity: .06;
        pointer-events: none;
    }
    .portal-home-icon { display: block; margin-bottom: .6rem; color: var(--portal-icon); font-size: 1.7rem; }
    .portal-home-title { margin: 0; color: var(--portal-text); font-size: clamp(1.35rem, 3vw, 2.35rem); font-weight: 700; line-height: 1.15; letter-spacing: .02em; }
    .portal-home-subtitle { margin: .35rem 0 0; color: var(--portal-text-muted); font-size: clamp(.9rem, 1.7vw, 1.15rem); }
    .portal-home-badge { display: inline-block; margin-top: .7rem; border: 1px solid var(--portal-border); border-radius: 999px; padding: .3rem .7rem; color: var(--portal-text-muted); font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; }
</style>
