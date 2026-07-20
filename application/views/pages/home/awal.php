<!-- ============================================================
     HOMEPAGE — Menu Portal Sederhana (gaya dashboard lama)
     ============================================================
     Hero besar (judul + slideshow bg) dan carousel Etalase Program
     sudah diarsipkan, LIHAT: archive/hero_dan_etalase_lama.php
     ============================================================ -->
<!-- Homepage Content: Menu Portal -->
<div class="p-2 sm:p-4">

    <!-- SECTION 1: MENU UTAMA -->
    <div id="menu-utama" class="mb-3">
        <h2 class="text-sm font-bold uppercase tracking-widest text-[#8aacb0]">Menu Utama</h2>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2 sm:gap-3 mb-10">

        <a href="<?= base_url('golek_omah') ?>" data-tab-link data-tab-key="golek_omah" class="group rounded-3xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow); min-height: 168px;">
            <i class="fa-solid fa-house-chimney-window absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 80px; right: -1rem; bottom: -1rem; color: var(--portal-icon); opacity: 0.06;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-house-chimney-window mb-4 transition-transform duration-500 group-hover:scale-110" style="font-size: 24px; color: var(--portal-icon); "></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1 group-hover:text-[color:var(--portal-text)] transition-colors">Nggolek Omah</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">Cari rumah sesuai kelayakan Anda</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: var(--portal-btn-bg); color: var(--portal-icon); border: 1px solid var(--portal-btn-border);">
                    <span>Cek Sekarang</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <a href="<?= base_url('Pengembang/sertifikasi') ?>" class="group rounded-3xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow); min-height: 168px;">
            <i class="fa-solid fa-certificate absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 80px; right: -1rem; bottom: -1rem; color: var(--portal-icon); opacity: 0.06;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-certificate mb-4 transition-transform duration-500 group-hover:scale-110" style="font-size: 24px; color: var(--portal-icon); "></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1 group-hover:text-[color:var(--portal-text)] transition-colors">Sertifikasi Pengembang</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">SRP2 untuk pengembang perumahan</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: var(--portal-btn-bg); color: var(--portal-icon); border: 1px solid var(--portal-btn-border);">
                    <span>Daftar</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <div class="rounded-3xl p-3.5 sm:p-4 flex flex-col relative overflow-hidden opacity-50 cursor-not-allowed" style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border); min-height: 168px;" title="Segera Hadir">
            <i class="fa-solid fa-city absolute pointer-events-none" style="font-size: 80px; right: -1rem; bottom: -1rem; color: var(--portal-icon); opacity: 0.05;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-city mb-4" style="font-size: 24px; color: var(--portal-icon);"></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1">PSU dan Kawasan Kumuh</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">Data prasarana & kawasan kumuh</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: var(--portal-btn-bg); color: var(--portal-icon); border: 1px solid var(--portal-btn-border);">
                    <span>Segera Hadir</span>
                    <i class="fa-solid fa-lock"></i>
                </div>
            </div>
        </div>

        <div class="rounded-3xl p-3.5 sm:p-4 flex flex-col relative overflow-hidden opacity-50 cursor-not-allowed" style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border); min-height: 168px;" title="Segera Hadir">
            <i class="fa-solid fa-chart-line absolute pointer-events-none" style="font-size: 80px; right: -1rem; bottom: -1rem; color: var(--portal-icon); opacity: 0.05;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-chart-line mb-4" style="font-size: 24px; color: var(--portal-icon);"></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1">Monitoring Capaian Kinerja</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">Pemantauan capaian program</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: var(--portal-btn-bg); color: var(--portal-icon); border: 1px solid var(--portal-btn-border);">
                    <span>Segera Hadir</span>
                    <i class="fa-solid fa-lock"></i>
                </div>
            </div>
        </div>

    </div>

    <!-- SECTION 2: PROGRAM UNGGULAN -->
    <div id="etalase-program" class="mb-3">
        <h2 class="text-sm font-bold uppercase tracking-widest text-[#8aacb0]">Program Unggulan</h2>
    </div>
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-2 sm:gap-3 mb-10">

        <a href="<?= base_url('Program/diagnosa/flpp') ?>" class="group rounded-3xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow); min-height: 156px;">
            <i class="fa-solid fa-house-chimney-window absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 70px; right: -0.75rem; bottom: -0.75rem; color: var(--portal-icon); opacity: 0.07;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-house-chimney-window mb-3 transition-transform duration-500 group-hover:scale-110" style="font-size: 28px; color: var(--portal-icon); "></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1.5 group-hover:text-[color:var(--portal-text)] transition-colors">KPR-FLPP<br>Rumah Subsidi</h4>
                <p class="text-[color:var(--portal-text-muted)] text-[11px] leading-relaxed">Bunga flat 5%, DP mulai 1%</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: var(--portal-btn-bg); color: var(--portal-icon); border: 1px solid var(--portal-btn-border);">
                    <span>Cek Kelayakan</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <a href="<?= base_url('Program/diagnosa/oemah_lestari') ?>" class="group rounded-3xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow); min-height: 156px;">
            <i class="fa-solid fa-leaf absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 70px; right: -0.75rem; bottom: -0.75rem; color: var(--portal-icon); opacity: 0.07;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-leaf mb-3 transition-transform duration-500 group-hover:scale-110" style="font-size: 28px; color: var(--portal-icon); "></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1.5 group-hover:text-[color:var(--portal-text)] transition-colors">Oemah<br>Lestari</h4>
                <p class="text-[color:var(--portal-text-muted)] text-[11px] leading-relaxed">Bunga ringan 8%, tenor 15 tahun</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: var(--portal-btn-bg); color: var(--portal-icon); border: 1px solid var(--portal-btn-border);">
                    <span>Cek Kelayakan</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <a href="<?= base_url('Program/diagnosa/rtlh') ?>" class="group rounded-3xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow); min-height: 156px;">
            <i class="fa-solid fa-house-crack absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 70px; right: -0.75rem; bottom: -0.75rem; color: var(--portal-icon); opacity: 0.07;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-house-crack mb-3 transition-transform duration-500 group-hover:scale-110" style="font-size: 28px; color: var(--portal-icon); "></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1.5 group-hover:text-[color:var(--portal-text)] transition-colors">Peningkatan<br>Kualitas RTLH</h4>
                <p class="text-[color:var(--portal-text-muted)] text-[11px] leading-relaxed">Renovasi rumah tidak layak huni</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: var(--portal-btn-bg); color: var(--portal-icon); border: 1px solid var(--portal-btn-border);">
                    <span>Cek Kelayakan</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <a href="<?= base_url('Program/diagnosa/pb') ?>" class="group rounded-3xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow); min-height: 156px;">
            <i class="fa-solid fa-trowel-bricks absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 70px; right: -0.75rem; bottom: -0.75rem; color: var(--portal-icon); opacity: 0.07;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-trowel-bricks mb-3 transition-transform duration-500 group-hover:scale-110" style="font-size: 28px; color: var(--portal-icon); "></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1.5 group-hover:text-[color:var(--portal-text)] transition-colors">Stimulan<br>Pembangunan Baru</h4>
                <p class="text-[color:var(--portal-text-muted)] text-[11px] leading-relaxed">Bantuan material Rp 40 Juta</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: var(--portal-btn-bg); color: var(--portal-icon); border: 1px solid var(--portal-btn-border);">
                    <span>Cek Kelayakan</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <a href="<?= base_url('Program/diagnosa/rumah_apung') ?>" class="group rounded-3xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow); min-height: 156px;">
            <i class="fa-solid fa-water absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 70px; right: -0.75rem; bottom: -0.75rem; color: var(--portal-icon); opacity: 0.07;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-water mb-3 transition-transform duration-500 group-hover:scale-110" style="font-size: 28px; color: var(--portal-icon); "></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1.5 group-hover:text-[color:var(--portal-text)] transition-colors">Program<br>Rumah Apung</h4>
                <p class="text-[color:var(--portal-text-muted)] text-[11px] leading-relaxed">Hunian adaptif kawasan pesisir</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: var(--portal-btn-bg); color: var(--portal-icon); border: 1px solid var(--portal-btn-border);">
                    <span>Cek Kelayakan</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

    </div>

    <!-- SECTION 3: AKSES CEPAT — Forum, Aduan, KKN & Magang -->
    <div id="akses-cepat" class="mb-3">
        <h2 class="text-sm font-bold uppercase tracking-widest text-[#8aacb0]">Akses Cepat</h2>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 sm:gap-3">

        <a href="<?= base_url('umum/forum') ?>" class="group rounded-3xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow);">
            <i class="fa-solid fa-comments absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 80px; right: -1rem; bottom: -1.5rem; color: var(--portal-icon); opacity: 0.06;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-comments mb-4 transition-transform duration-500 group-hover:scale-110" style="font-size: 34px; color: var(--portal-icon); "></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1 group-hover:text-[color:var(--portal-text)] transition-colors">Forum Diskusi</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">Diskusi seputar perumahan bersama komunitas dan pemerintah.</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: var(--portal-btn-bg); color: var(--portal-icon); border: 1px solid var(--portal-btn-border);">
                    <span>Buka Forum</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <a href="<?= base_url('umum/aduan') ?>" class="group rounded-3xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow);">
            <i class="fa-solid fa-circle-question absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 80px; right: -1rem; bottom: -1.5rem; color: var(--portal-icon); opacity: 0.06;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-circle-question mb-4 transition-transform duration-500 group-hover:scale-110" style="font-size: 34px; color: var(--portal-icon); "></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1 group-hover:text-[color:var(--portal-text)] transition-colors">Aduan</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">Sampaikan pertanyaan atau laporan seputar layanan perumahan.</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: var(--portal-btn-bg); color: var(--portal-icon); border: 1px solid var(--portal-btn-border);">
                    <span>Kirim Aduan</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <a href="<?= base_url('kemitraan') ?>" class="group rounded-3xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow);">
            <i class="fa-solid fa-user-graduate absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 80px; right: -1rem; bottom: -1.5rem; color: var(--portal-icon); opacity: 0.06;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-user-graduate mb-4 transition-transform duration-500 group-hover:scale-110" style="font-size: 34px; color: var(--portal-icon); "></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1 group-hover:text-[color:var(--portal-text)] transition-colors">KKN dan Magang</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">Program tematik untuk universitas dan mahasiswa.</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: var(--portal-btn-bg); color: var(--portal-icon); border: 1px solid var(--portal-btn-border);">
                    <span>Info Selengkapnya</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

    </div>

</div>
