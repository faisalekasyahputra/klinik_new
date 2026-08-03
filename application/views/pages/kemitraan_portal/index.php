<div class="mx-auto max-w-4xl p-2 sm:p-6">
    <div class="mb-6 text-center">
        <span class="text-xs font-bold uppercase tracking-[.18em] text-[color:var(--portal-text-muted)]">Kemitraan</span>
        <h1 class="mt-2 text-2xl font-black text-[color:var(--portal-text)] sm:text-4xl">KKN dan Magang</h1>
        <p class="mt-2 text-sm text-[color:var(--portal-text-muted)]">Pilih informasi kemitraan yang ingin Anda lihat.</p>
    </div>

    <?php
    // Kartu memakai pola kartu layanan portal yang sama dengan tab Perumahan,
    // Kawasan, Pertanahan, Bank Data, dan Pengembang: ikon 24px di atas judul,
    // ikon "hantu" 80px opacity .08 di pojok kanan bawah, dan tombol aksi
    // menempel di dasar lewat `mt-auto`. Sebelumnya halaman ini punya kartunya
    // sendiri — tinggi 220px, rata tengah, judul text-2xl, tanpa tombol —
    // sehingga satu-satunya halaman portal yang kartunya beda sendiri.
    //
    // `data-tab-group` sengaja TIDAK ikut disalin meski ada di kelima tab itu:
    // tidak ada satu pun kode yang membacanya (nol hasil di seluruh
    // application/ dan assets/). Menyalin atribut mati cuma memperbanyak
    // tempat yang harus dibaca orang berikutnya sebelum sadar ia tidak berarti.
    // `data-tab-link` dan `data-tab-key` DIPAKAI — loader portal di
    // layouts/footer.php membacanya untuk menyorot tab aktif.
    ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 sm:gap-3">

        <!-- Card: KKN Kemitraan -->
        <a href="<?= base_url('KemitraanPortal/kkn') ?>" data-tab-link data-tab-key="kemitraan_kkn"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow); min-height: 120px;">
            <i class="fa-solid fa-people-group absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 80px; right: -1rem; bottom: -1rem; color: var(--portal-icon); opacity: 0.08;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-people-group mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 24px; color: var(--portal-icon);"></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1">KKN Kemitraan</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">Persyaratan pendaftaran</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: var(--portal-btn-bg); color: var(--portal-icon); border: 1px solid var(--portal-btn-border);">
                    <span>Lihat Persyaratan</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>

        <!-- Card: Magang/Kerja Praktik -->
        <a href="<?= base_url('KemitraanPortal/magang') ?>" data-tab-link data-tab-key="kemitraan_magang"
           class="group rounded-2xl p-3.5 sm:p-4 flex flex-col transition-all duration-500 relative overflow-hidden"
           style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border); box-shadow: var(--portal-shadow); min-height: 120px;">
            <i class="fa-solid fa-user-graduate absolute transition-transform duration-700 group-hover:scale-110 group-hover:-rotate-6 pointer-events-none"
               style="font-size: 80px; right: -1rem; bottom: -1rem; color: var(--portal-icon); opacity: 0.08;"></i>
            <div class="relative z-10">
                <i class="fa-solid fa-user-graduate mb-2.5 transition-transform duration-500 group-hover:scale-110"
                   style="font-size: 24px; color: var(--portal-icon);"></i>
                <h4 class="text-[color:var(--portal-text)] font-bold text-sm mb-1">Magang/Kerja Praktik</h4>
                <p class="text-[color:var(--portal-text-muted)] text-xs leading-relaxed">Lihat slot magang yang tersedia</p>
            </div>
            <div class="relative z-10 mt-auto pt-2.5">
                <div class="tl-btn-base" style="background-color: var(--portal-btn-bg); color: var(--portal-icon); border: 1px solid var(--portal-btn-border);">
                    <span>Lihat Slot Magang</span>
                    <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                </div>
            </div>
        </a>
    </div>
</div>
