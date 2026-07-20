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
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-10">

        <a href="<?= base_url('golek_omah') ?>" class="group rounded-3xl p-5 sm:p-6 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--bg-card); border: 1px solid rgba(214, 251, 0, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-height: 168px;">
            <i class="fa-solid fa-house-chimney-window absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 120px; right: -1rem; bottom: -1rem; color: #d6fb00; opacity: 0.06;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-house-chimney-window mb-4 transition-transform duration-500 group-hover:scale-110" style="font-size: 32px; color: #d6fb00; filter: drop-shadow(0 0 12px rgba(214, 251, 0, 0.4));"></i>
                <h4 class="text-white font-bold text-lg mb-1.5 group-hover:text-[#d6fb00] transition-colors">Nggolek Omah</h4>
                <p class="text-zinc-500 text-xs leading-relaxed">Cari rumah sesuai kelayakan Anda</p>
            </div>
            <div class="relative z-10 mt-auto pt-4">
                <div class="tl-btn-base" style="background-color: rgba(214,251,0,0.1); color: #d6fb00; border: 1px solid rgba(214,251,0,0.2);">
                    <span>Cek Sekarang</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <a href="<?= base_url('Pengembang/sertifikasi') ?>" class="group rounded-3xl p-5 sm:p-6 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--bg-card); border: 1px solid rgba(214, 251, 0, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-height: 168px;">
            <i class="fa-solid fa-certificate absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 120px; right: -1rem; bottom: -1rem; color: #d6fb00; opacity: 0.06;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-certificate mb-4 transition-transform duration-500 group-hover:scale-110" style="font-size: 32px; color: #d6fb00; filter: drop-shadow(0 0 12px rgba(214, 251, 0, 0.4));"></i>
                <h4 class="text-white font-bold text-lg mb-1.5 group-hover:text-[#d6fb00] transition-colors">Sertifikasi Pengembang</h4>
                <p class="text-zinc-500 text-xs leading-relaxed">SRP2 untuk pengembang perumahan</p>
            </div>
            <div class="relative z-10 mt-auto pt-4">
                <div class="tl-btn-base" style="background-color: rgba(214,251,0,0.1); color: #d6fb00; border: 1px solid rgba(214,251,0,0.2);">
                    <span>Daftar</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <div class="rounded-3xl p-5 sm:p-6 flex flex-col relative overflow-hidden opacity-50 cursor-not-allowed" style="background-color: var(--bg-card); border: 1px solid rgba(255,255,255,0.08); min-height: 168px;" title="Segera Hadir">
            <i class="fa-solid fa-city absolute pointer-events-none" style="font-size: 120px; right: -1rem; bottom: -1rem; color: #8aacb0; opacity: 0.05;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-city mb-4" style="font-size: 32px; color: #8aacb0;"></i>
                <h4 class="text-zinc-300 font-bold text-lg mb-1.5">PSU dan Kawasan Kumuh</h4>
                <p class="text-zinc-500 text-xs leading-relaxed">Data prasarana & kawasan kumuh</p>
            </div>
            <div class="relative z-10 mt-auto pt-4">
                <div class="tl-btn-base" style="background-color: rgba(255,255,255,0.05); color: #5a7a80; border: 1px solid rgba(255,255,255,0.08);">
                    <span>Segera Hadir</span>
                    <i class="fa-solid fa-lock"></i>
                </div>
            </div>
        </div>

        <div class="rounded-3xl p-5 sm:p-6 flex flex-col relative overflow-hidden opacity-50 cursor-not-allowed" style="background-color: var(--bg-card); border: 1px solid rgba(255,255,255,0.08); min-height: 168px;" title="Segera Hadir">
            <i class="fa-solid fa-chart-line absolute pointer-events-none" style="font-size: 120px; right: -1rem; bottom: -1rem; color: #8aacb0; opacity: 0.05;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-chart-line mb-4" style="font-size: 32px; color: #8aacb0;"></i>
                <h4 class="text-zinc-300 font-bold text-lg mb-1.5">Monitoring Capaian Kinerja</h4>
                <p class="text-zinc-500 text-xs leading-relaxed">Pemantauan capaian program</p>
            </div>
            <div class="relative z-10 mt-auto pt-4">
                <div class="tl-btn-base" style="background-color: rgba(255,255,255,0.05); color: #5a7a80; border: 1px solid rgba(255,255,255,0.08);">
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
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4 mb-10">

        <a href="<?= base_url('Program/diagnosa/flpp') ?>" class="group rounded-3xl p-5 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--bg-card); border: 1px solid rgba(0, 163, 181, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-height: 156px;">
            <i class="fa-solid fa-house-chimney-window absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 100px; right: -0.75rem; bottom: -0.75rem; color: #00a3b5; opacity: 0.07;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-house-chimney-window mb-3 transition-transform duration-500 group-hover:scale-110" style="font-size: 28px; color: #00a3b5; filter: drop-shadow(0 0 10px rgba(0, 163, 181, 0.4));"></i>
                <h4 class="text-white font-bold text-sm mb-1.5 group-hover:text-[#00a3b5] transition-colors">KPR-FLPP<br>Rumah Subsidi</h4>
                <p class="text-zinc-500 text-[11px] leading-relaxed">Bunga flat 5%, DP mulai 1%</p>
            </div>
            <div class="relative z-10 mt-auto pt-4">
                <div class="tl-btn-base" style="background-color: rgba(0,163,181,0.1); color: #00a3b5; border: 1px solid rgba(0,163,181,0.2);">
                    <span>Cek Kelayakan</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <a href="<?= base_url('Program/diagnosa/oemah_lestari') ?>" class="group rounded-3xl p-5 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--bg-card); border: 1px solid rgba(107, 203, 119, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-height: 156px;">
            <i class="fa-solid fa-leaf absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 100px; right: -0.75rem; bottom: -0.75rem; color: #6bcb77; opacity: 0.07;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-leaf mb-3 transition-transform duration-500 group-hover:scale-110" style="font-size: 28px; color: #6bcb77; filter: drop-shadow(0 0 10px rgba(107, 203, 119, 0.4));"></i>
                <h4 class="text-white font-bold text-sm mb-1.5 group-hover:text-[#6bcb77] transition-colors">Oemah<br>Lestari</h4>
                <p class="text-zinc-500 text-[11px] leading-relaxed">Bunga ringan 8%, tenor 15 tahun</p>
            </div>
            <div class="relative z-10 mt-auto pt-4">
                <div class="tl-btn-base" style="background-color: rgba(107,203,119,0.1); color: #6bcb77; border: 1px solid rgba(107,203,119,0.2);">
                    <span>Cek Kelayakan</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <a href="<?= base_url('Program/diagnosa/rtlh') ?>" class="group rounded-3xl p-5 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--bg-card); border: 1px solid rgba(245, 158, 11, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-height: 156px;">
            <i class="fa-solid fa-house-crack absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 100px; right: -0.75rem; bottom: -0.75rem; color: #f59e0b; opacity: 0.07;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-house-crack mb-3 transition-transform duration-500 group-hover:scale-110" style="font-size: 28px; color: #f59e0b; filter: drop-shadow(0 0 10px rgba(245, 158, 11, 0.4));"></i>
                <h4 class="text-white font-bold text-sm mb-1.5 group-hover:text-[#f59e0b] transition-colors">Peningkatan<br>Kualitas RTLH</h4>
                <p class="text-zinc-500 text-[11px] leading-relaxed">Renovasi rumah tidak layak huni</p>
            </div>
            <div class="relative z-10 mt-auto pt-4">
                <div class="tl-btn-base" style="background-color: rgba(245,158,11,0.1); color: #f59e0b; border: 1px solid rgba(245,158,11,0.2);">
                    <span>Cek Kelayakan</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <a href="<?= base_url('Program/diagnosa/pb') ?>" class="group rounded-3xl p-5 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--bg-card); border: 1px solid rgba(214, 251, 0, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-height: 156px;">
            <i class="fa-solid fa-trowel-bricks absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 100px; right: -0.75rem; bottom: -0.75rem; color: #d6fb00; opacity: 0.07;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-trowel-bricks mb-3 transition-transform duration-500 group-hover:scale-110" style="font-size: 28px; color: #d6fb00; filter: drop-shadow(0 0 10px rgba(214, 251, 0, 0.4));"></i>
                <h4 class="text-white font-bold text-sm mb-1.5 group-hover:text-[#d6fb00] transition-colors">Stimulan<br>Pembangunan Baru</h4>
                <p class="text-zinc-500 text-[11px] leading-relaxed">Bantuan material Rp 40 Juta</p>
            </div>
            <div class="relative z-10 mt-auto pt-4">
                <div class="tl-btn-base" style="background-color: rgba(214,251,0,0.1); color: #d6fb00; border: 1px solid rgba(214,251,0,0.2);">
                    <span>Cek Kelayakan</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <a href="<?= base_url('Program/diagnosa/rumah_apung') ?>" class="group rounded-3xl p-5 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--bg-card); border: 1px solid rgba(96, 165, 250, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2); min-height: 156px;">
            <i class="fa-solid fa-water absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 100px; right: -0.75rem; bottom: -0.75rem; color: #60a5fa; opacity: 0.07;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-water mb-3 transition-transform duration-500 group-hover:scale-110" style="font-size: 28px; color: #60a5fa; filter: drop-shadow(0 0 10px rgba(96, 165, 250, 0.4));"></i>
                <h4 class="text-white font-bold text-sm mb-1.5 group-hover:text-[#60a5fa] transition-colors">Program<br>Rumah Apung</h4>
                <p class="text-zinc-500 text-[11px] leading-relaxed">Hunian adaptif kawasan pesisir</p>
            </div>
            <div class="relative z-10 mt-auto pt-4">
                <div class="tl-btn-base" style="background-color: rgba(96,165,250,0.1); color: #60a5fa; border: 1px solid rgba(96,165,250,0.2);">
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
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">

        <a href="<?= base_url('umum/forum') ?>" class="group rounded-3xl p-5 sm:p-6 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--bg-card); border: 1px solid rgba(214, 251, 0, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
            <i class="fa-solid fa-comments absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 130px; right: -1rem; bottom: -1.5rem; color: #d6fb00; opacity: 0.06;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-comments mb-4 transition-transform duration-500 group-hover:scale-110" style="font-size: 34px; color: #d6fb00; filter: drop-shadow(0 0 12px rgba(214, 251, 0, 0.4));"></i>
                <h4 class="text-white font-bold text-xl mb-2 group-hover:text-[#d6fb00] transition-colors">Forum Diskusi</h4>
                <p class="text-zinc-500 text-sm leading-relaxed">Diskusi seputar perumahan bersama komunitas dan pemerintah.</p>
            </div>
            <div class="relative z-10 mt-auto pt-6">
                <div class="tl-btn-base" style="background-color: rgba(214,251,0,0.1); color: #d6fb00; border: 1px solid rgba(214,251,0,0.2);">
                    <span>Buka Forum</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <a href="<?= base_url('umum/aduan') ?>" class="group rounded-3xl p-5 sm:p-6 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--bg-card); border: 1px solid rgba(214, 251, 0, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
            <i class="fa-solid fa-circle-question absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 130px; right: -1rem; bottom: -1.5rem; color: #d6fb00; opacity: 0.06;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-circle-question mb-4 transition-transform duration-500 group-hover:scale-110" style="font-size: 34px; color: #d6fb00; filter: drop-shadow(0 0 12px rgba(214, 251, 0, 0.4));"></i>
                <h4 class="text-white font-bold text-xl mb-2 group-hover:text-[#d6fb00] transition-colors">Aduan</h4>
                <p class="text-zinc-500 text-sm leading-relaxed">Sampaikan pertanyaan atau laporan seputar layanan perumahan.</p>
            </div>
            <div class="relative z-10 mt-auto pt-6">
                <div class="tl-btn-base" style="background-color: rgba(214,251,0,0.1); color: #d6fb00; border: 1px solid rgba(214,251,0,0.2);">
                    <span>Kirim Aduan</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <a href="<?= base_url('kemitraan') ?>" class="group rounded-3xl p-5 sm:p-6 flex flex-col transition-all duration-500 relative overflow-hidden" style="background-color: var(--bg-card); border: 1px solid rgba(214, 251, 0, 0.15); box-shadow: 0 4px 20px rgba(0,0,0,0.2);">
            <i class="fa-solid fa-user-graduate absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none" style="font-size: 130px; right: -1rem; bottom: -1.5rem; color: #d6fb00; opacity: 0.06;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-user-graduate mb-4 transition-transform duration-500 group-hover:scale-110" style="font-size: 34px; color: #d6fb00; filter: drop-shadow(0 0 12px rgba(214, 251, 0, 0.4));"></i>
                <h4 class="text-white font-bold text-xl mb-2 group-hover:text-[#d6fb00] transition-colors">KKN dan Magang</h4>
                <p class="text-zinc-500 text-sm leading-relaxed">Program tematik untuk universitas dan mahasiswa.</p>
            </div>
            <div class="relative z-10 mt-auto pt-6">
                <div class="tl-btn-base" style="background-color: rgba(214,251,0,0.1); color: #d6fb00; border: 1px solid rgba(214,251,0,0.2);">
                    <span>Info Selengkapnya</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

    </div>

</div>
