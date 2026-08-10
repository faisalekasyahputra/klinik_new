<section class="w-full  pt-24 pb-16 px-4 sm:px-6 lg:px-8 relative min-h-screen font-outfit">
    <!-- Background Ornaments -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-[#d6fb00]/5 blur-[120px] rounded-full"></div>
    </div>

    <div class="max-w-7xl mx-auto relative z-10">
        
        <!-- Breadcrumb -->
        <div class="mb-10">
            <nav class="flex text-[10px] sm:text-xs text-zinc-500 font-bold uppercase tracking-widest" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2">
                    <li class="inline-flex items-center">
                        <a href="<?= base_url() ?>" class="hover:text-[#d6fb00] transition-colors"><i class="fa-solid fa-house mr-2"></i>Beranda</a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fa-solid fa-chevron-right text-[8px] mx-2"></i>
                            <span class="text-[#d6fb00]">Kabupaten / Kota</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>


<?php
/*
 * A3 - blok "Daftar Intervensi" DICABUT 29 Jul 2026. Tidak butuh keputusan
 * produk: angka anggarannya literal di view tanpa satu pun query, dan tombol
 * tambah/ubah/hapusnya nol handler - UI yang berjanji bisa mengubah data
 * padahal tidak bisa (§19 langkah 3). Halaman ini publik anonim, jadi
 * angkanya bisa dikutip siapa saja.
 *
 * Catatan ditulis sebagai komentar PHP, BUKAN komentar HTML: komentar HTML
 * ikut terkirim ke pengunjung, sehingga menyebut angka aslinya di situ akan
 * membuat literal yang baru dicabut muncul lagi di respons - dan uji
 * "grep respons anonim → nol hasil" gagal karena penjelasannya sendiri.
 */
?>
        <div class="text-left space-y-4 max-w-2xl mb-12">
            <h2 class="text-3xl sm:text-4xl font-black text-white tracking-tighter font-jakarta">
                Daftar <span class="text-[#d6fb00]">Intervensi</span>
            </h2>
            <p class="text-zinc-400 text-sm sm:text-base leading-relaxed">
                Rekapitulasi program perumahan kabupaten/kota belum tersedia di portal ini.
                Data intervensi dikelola melalui modul Rekam Data oleh masing-masing
                kabupaten/kota.
            </p>
        </div>
            </div>
        </div>

    </div>
</section>
