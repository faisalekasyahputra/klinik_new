<!-- Tab Content: Kawasan -->
<div class="py-4 sm:py-6 px-1 sm:px-2">
    <div class="flex items-center gap-2 mb-3">
        <i class="fa-solid fa-city  text-[color:var(--portal-text)]"></i>
        <h2 class="text-sm font-bold uppercase tracking-widest text-[#2d6b75]">Data Kawasan</h2>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">

        <!-- Card: Sebaran RTLH -->
        <a href="<?= base_url('sebaran') ?>" data-tab-link data-tab-key="sebaran" data-tab-group="kawasan"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow); min-height: 120px;">
            <i class="fa-solid fa-house-crack absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 80px; right: -1rem; bottom: -1rem; color: var(--portal-icon); opacity: 0.08;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-house-crack mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 24px; color: var(--portal-icon); "></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1 group-hover:text-[color:var(--portal-text)] transition-colors">Sebaran RTLH</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">Peta sebaran rumah tidak layak huni di Jawa Tengah</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: var(--portal-btn-bg); color: var(--portal-icon); border: 1px solid var(--portal-btn-border);">
                    <span>Lihat Peta</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <!--
          A1 - tiga kartu (Sebaran Rusun, Profil Kawasan Kumuh, Sebaran Bantuan
          SDGS) DICABUT 29 Jul 2026 atas keputusan user, bersama halamannya.
          Ketiganya menampilkan angka literal tanpa sumber apa pun - 8.420 unit,
          4.250,8 Ha, Rp 1,2 Triliun tersalurkan - dan yang paling berat:
          rusunawa BERNAMA NYATA lengkap dengan koordinat asli, diberi angka
          okupansi karangan serta tuduhan "terdapat keluhan kerusakan aset".
          Label "(Data Simulasi)" di subjudul tidak melisensikan itu.
          Kartunya dihapus DULU sebelum route-nya, supaya beranda tidak pernah
          menunjuk ke halaman yang sudah tiada.
        -->

    </div>
</div>
