<div class="py-4 sm:py-6 px-1 sm:px-2 relative font-outfit">
    <!-- Header -->
    <div class="text-center mb-10" data-aos="fade-down">
        <h3 class="text-3xl sm:text-4xl font-extrabold text-[color:var(--portal-text)] tracking-tight mb-2">Nggoleki <span class="text-[color:var(--portal-brand)]">Omah</span></h3>
        <p class="text-[color:var(--portal-text-muted)] text-xs">Data real-time dari Sikumbang - Tapera</p>
    </div>

        <div class="border p-6 sm:p-8 rounded-3xl mb-10 shadow-sm bg-[color:var(--portal-bg-card)] border-[color:var(--portal-border)]" data-aos="fade-up" data-aos-delay="100">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-end">
                <div class="lg:col-span-3">
                    <label class="block text-[10px] font-bold text-[color:var(--portal-text-muted)] uppercase tracking-widest mb-2"><i class="fa-solid fa-location-dot mr-1.5 text-[color:var(--portal-brand)]"></i> Wilayah</label>
                    <select id="kodeWilayah" onchange="cari_wil()" class="w-full rounded-xl px-4 py-3 text-sm outline-none transition-all bg-[color:var(--portal-btn-bg)] border border-[color:var(--portal-border)] text-[color:var(--portal-text)] focus:border-[color:var(--portal-brand)]">
                        <option value="33">Seluruh Jawa Tengah</option>
                        <?php foreach ($kabupaten_kota_jateng as $kode => $nama): ?>
                        <option value="<?= $kode ?>"><?= $nama ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="lg:col-span-3">
                    <label class="block text-[10px] font-bold text-[color:var(--portal-text-muted)] uppercase tracking-widest mb-2"><i class="fa-solid fa-list mr-1.5 text-[color:var(--portal-brand)]"></i> Kategori</label>
                    <select id="searchBy" onchange="cari_wil()" class="w-full rounded-xl px-4 py-3 text-sm outline-none transition-all bg-[color:var(--portal-btn-bg)] border border-[color:var(--portal-border)] text-[color:var(--portal-text)] focus:border-[color:var(--portal-brand)]">
                        <option value="nama-perumahan">Nama Perumahan</option>
                        <option value="nama-pengembang">Nama Pengembang</option>
                        <option value="asosiasi">Asosiasi</option>
                    </select>
                </div>
                <div class="lg:col-span-6">
                    <label class="block text-[10px] font-bold text-[color:var(--portal-text-muted)] uppercase tracking-widest mb-2"><i class="fa-solid fa-magnifying-glass mr-1.5 text-[color:var(--portal-brand)]"></i> Kata Kunci</label>
                    <div class="relative flex items-center">
                        <input id="keyword" onkeyup="cari_wil()" type="text" placeholder="Masukkan kata kunci..." class="w-full rounded-xl px-4 py-3 pr-12 text-sm outline-none transition-all bg-[color:var(--portal-btn-bg)] border border-[color:var(--portal-border)] text-[color:var(--portal-text)] focus:border-[color:var(--portal-brand)] placeholder-[color:var(--portal-text-muted)]">
                        <button onclick="cari_wil()" class="absolute right-1.5 flex items-center justify-center transition-all w-9 h-9 rounded-lg bg-[color:var(--portal-brand)] text-[color:var(--portal-bg)] hover:opacity-80">
                            <i class="fa-solid fa-magnifying-glass text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-center mt-6 pt-6" style="border-top: 1px solid var(--portal-border);">
                <div class="lg:col-span-3">
                    <label class="block text-[10px] font-bold text-[color:var(--portal-text-muted)] uppercase tracking-widest mb-2"><i class="fa-solid fa-sort mr-1.5 text-[color:var(--portal-brand)]"></i> Urutkan</label>
                    <select id="sort" onchange="cari_wil()" class="w-full rounded-xl px-4 py-2.5 text-sm outline-none transition-all bg-[color:var(--portal-btn-bg)] border border-[color:var(--portal-border)] text-[color:var(--portal-text)] focus:border-[color:var(--portal-brand)]">
                        <option value="terbaru">Terbaru</option>
                        <option value="subsidi-termurah">Harga Terendah</option>
                        <option value="subsidi-tertinggi">Harga Tertinggi</option>
                    </select>
                </div>
                <?php
                /**
                 * DUA TOMBOL, bukan satu toggle (revisi dinas 3 Agt 2026).
                 *
                 * Toggle lamanya bukan cuma kurang jelas - ia BERBOHONG. Ia
                 * mengirim `komersil` saat dimatikan, sementara filter di
                 * `Index::cari_wil()` hanya mengenal `semua` sebagai "jangan
                 * saring", jadi mematikannya menghasilkan daftar yang persis
                 * sama. Orang mengira sudah melihat rumah non-subsidi padahal
                 * tidak pernah. Filternya ikut diperbaiki di commit yang sama
                 * (`Index::saring_status_rumah()`).
                 *
                 * Dua pilihan eksplisit juga menghapus pertanyaan yang tidak
                 * pernah terjawab toggle: "kalau dimatikan, saya melihat SEMUA
                 * rumah atau NON-subsidi saja?"
                 */
                ?>
                <div class="lg:col-span-9 flex lg:justify-end items-center mt-2 lg:mt-0">
                    <div role="radiogroup" aria-label="Jenis rumah"
                         class="inline-flex rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-btn-bg)] p-1">
                        <button type="button" id="btn-subsidi" data-status="subsidi" onclick="pilihStatus('subsidi')"
                                role="radio" aria-checked="true"
                                class="rounded-lg px-4 py-2 text-sm font-bold transition-all bg-[color:var(--portal-brand)] text-[#0a1a1f]">
                            Subsidi
                        </button>
                        <button type="button" id="btn-komersil" data-status="komersil" onclick="pilihStatus('komersil')"
                                role="radio" aria-checked="false"
                                class="rounded-lg px-4 py-2 text-sm font-bold transition-all text-[color:var(--portal-text-muted)]">
                            Non Subsidi
                        </button>
                        <?php
                        /* Butir 2 putaran 2. Mesin penyaringnya SUDAH mengenal
                           `semua` sejak toggle lama diganti dua tombol - yang
                           hilang cuma tombolnya, sehingga tidak ada cara melihat
                           kedua jenis sekaligus. */
                        ?>
                        <button type="button" id="btn-semua" data-status="semua" onclick="pilihStatus('semua')"
                                role="radio" aria-checked="false"
                                class="rounded-lg px-4 py-2 text-sm font-bold transition-all text-[color:var(--portal-text-muted)]">
                            Semua
                        </button>
                    </div>
                </div>
            </div>

            <?php
            /* Butir A2 revisi dinas: dua tombol itu sudah benar, tinggal diberi
               keterangan singkat apa bedanya.

               DEFINISI UMUM, BUKAN KUTIPAN REGULASI - dan itu keputusan user
               10 Agt 2026 sesudah dinas belum sempat mengirim rumusannya.
               Sengaja TANPA ANGKA: batas penghasilan, harga, dan besaran uang
               muka berubah tiap tahun dan berbeda per wilayah. Menuliskannya di
               sini berarti memajang angka yang diam-diam basi, dan warga
               mempercayainya. Kalau dinas kelak mengirim rumusan resmi, ganti
               kalimat di `keteranganStatus` (satu tempat, di bawah). */
            ?>
            <p id="ket-status" aria-live="polite"
               class="mt-3 text-xs leading-relaxed text-[color:var(--portal-text-muted)]">
                Rumah dengan bantuan pembiayaan pemerintah untuk masyarakat berpenghasilan rendah -
                uang muka dan angsuran lebih ringan, dengan syarat batas penghasilan, belum memiliki rumah,
                dan rumahnya wajib dihuni sendiri.
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5 gap-4 sm:gap-5" id="temp_rumah" data-aos="fade-up" data-aos-delay="200">
            <div class="skeleton h-72"></div>
            <div class="skeleton h-72"></div>
            <div class="skeleton h-72"></div>
            <div class="skeleton h-72"></div>
            <div class="skeleton h-72 hidden xl:block"></div>
        </div>

        <div class="w-full flex items-center justify-center gap-3 sm:gap-4 mt-12" data-aos="zoom-in" data-aos-delay="300">
            <?php
            /* Token --portal-*, BUKAN hex #d6fb00/#ecffb6/#5a7a80 dari tombol
               "Muat Lebih Banyak" lama - itu warna TEMA GELAP (lihat
               design-system.css baris 43 vs 17), sementara portal publik
               dipatok theme-light (AGENTS.md §1). Teks pucat di atas latar
               putih nyaris tidak berkontras - bukan cuma estetik, tombolnya
               praktis tidak kelihatan. */
            ?>
            <button id="btn-prev-page" onclick="gantiHalaman(-1)" disabled
                    class="inline-flex items-center gap-2 px-5 sm:px-8 py-3.5 rounded-full text-xs font-bold tracking-wide transition-all bg-[color:var(--portal-btn-bg)] border border-[color:var(--portal-border)] text-[color:var(--portal-text)] hover:border-[color:var(--portal-brand)] hover:text-[color:var(--portal-brand)] disabled:opacity-40 disabled:pointer-events-none">
                <i class="fa-solid fa-chevron-left text-sm"></i>
                <span class="hidden sm:inline">Sebelumnya</span>
            </button>
            <span id="label-halaman" class="text-xs font-bold uppercase tracking-widest text-[color:var(--portal-text-muted)] min-w-[92px] text-center">Halaman 1</span>
            <button id="btn-next-page" onclick="gantiHalaman(1)"
                    class="inline-flex items-center gap-2 px-5 sm:px-8 py-3.5 rounded-full text-xs font-bold tracking-wide transition-all bg-[color:var(--portal-brand)] text-[color:var(--portal-bg)] hover:opacity-80 disabled:opacity-40 disabled:pointer-events-none">
                <span class="hidden sm:inline">Berikutnya</span>
                <i class="fa-solid fa-chevron-right text-sm"></i>
            </button>
        </div>

        <!-- CTA: Direktori Sosmed Pengembang (perlu login) -->
        <div class="mt-16 border p-6 sm:p-8 rounded-3xl backdrop-blur-md flex flex-col sm:flex-row items-center justify-between gap-6" style="background-color: rgba(15, 42, 48, 0.7); border-color: rgba(168, 85, 247, 0.2);" data-aos="fade-up">
            <div class="flex items-start gap-4">
                <div class="text-purple-400 shrink-0 pt-0.5">
                    <i class="fa-solid fa-bullhorn text-[28px]"></i>
                </div>
                <div class="space-y-1.5">
                    <h4 class="text-white font-bold text-base sm:text-lg tracking-tight">Lihat Direktori Sosial Media Pengembang</h4>
                    <p class="text-zinc-400 text-xs sm:text-sm leading-relaxed">Jelajahi kanal media sosial resmi pengembang perumahan untuk info unit dan promo terbaru. Login diperlukan untuk mengakses direktori lengkap.</p>
                </div>
            </div>
            <a href="<?= base_url('Pengembang/publikasi') ?>" class="shrink-0 inline-flex items-center gap-2 px-6 py-3 bg-purple-500/10 border border-purple-400/40 text-purple-300 font-semibold text-xs rounded-full hover:bg-purple-500/20 hover:border-purple-400/60 tracking-wide transition-all whitespace-nowrap">
                Buka Direktori
                <i class="fa-solid fa-arrow-right text-xs"></i>
            </a>
        </div>

</div>

<script>
/**
 * Pilihan jenis rumah disimpan di satu variabel, bukan dibaca ulang dari DOM.
 * `load_more()` dulu membaca checkbox yang sama - kalau kelak tombolnya
 * berpindah/berganti nama, satu tempat saja yang perlu diperbaiki.
 */
window.statusRumahAktif = 'subsidi';
window.halamanSekarang = 1;

var SKELETON_HALAMAN = `
    <div class="skeleton h-72"></div>
    <div class="skeleton h-72"></div>
    <div class="skeleton h-72"></div>
    <div class="skeleton h-72"></div>
    <div class="skeleton h-72 hidden xl:block"></div>
`;

/* Keterangan A2. Satu tempat saja - kalau dinas mengirim rumusan resminya,
   yang diganti cukup dua kalimat di sini (dan kalimat awal di HTML atas,
   yang sengaja dirender server supaya sudah benar sebelum JS jalan). */
var keteranganStatus = {
    subsidi: 'Rumah dengan bantuan pembiayaan pemerintah untuk masyarakat berpenghasilan rendah - uang muka dan angsuran lebih ringan, dengan syarat batas penghasilan, belum memiliki rumah, dan rumahnya wajib dihuni sendiri.',
    komersil: 'Rumah yang dijual dengan harga pasar tanpa bantuan pemerintah - tidak ada batas penghasilan maupun syarat kepemilikan, pilihan tipe dan lokasi lebih luas, dan angsurannya mengikuti bunga komersial.',
    semua: 'Menampilkan rumah subsidi dan non subsidi sekaligus, tanpa disaring.'
};

function pilihStatus(status) {
    if (window.statusRumahAktif === status) { return; }
    window.statusRumahAktif = status;

    var ket = document.getElementById('ket-status');
    if (ket && keteranganStatus[status]) { ket.textContent = keteranganStatus[status]; }

    ['subsidi', 'komersil', 'semua'].forEach(function (s) {
        var btn = document.getElementById('btn-' + s);
        if (!btn) { return; }
        var aktif = (s === status);
        btn.setAttribute('aria-checked', aktif ? 'true' : 'false');
        btn.classList.toggle('bg-[color:var(--portal-brand)]', aktif);
        btn.classList.toggle('text-[#0a1a1f]', aktif);
        btn.classList.toggle('text-[color:var(--portal-text-muted)]', !aktif);
    });

    cari_wil();
}

/**
 * Satu fungsi untuk SEMUA halaman - maju maupun mundur - bukan dua endpoint
 * terpisah seperti sebelumnya (cari_wil untuk halaman 1, load_more untuk
 * tombol "Muat Lebih Banyak" yang MENAMBAH ke grid). Sekarang setiap halaman
 * MENGGANTI isi grid, dan cari_wil() di server memang sudah generik menerima
 * parameter `page` berapa pun - tidak perlu endpoint kedua.
 *
 * `window.halamanSekarang` hanya diperbarui saat FETCH BERHASIL, sehingga
 * kegagalan otomatis jadi "coba lagi" - klik Berikutnya sesudah gagal
 * meminta ULANG halaman yang sama, bukan melompatinya.
 */
function muatHalaman(halaman, gulirKeAtas) {
    var keyword = document.getElementById('keyword').value;
    var kodeWilayah = document.getElementById('kodeWilayah').value;
    var sort = document.getElementById('sort').value;
    var searchBy = document.getElementById('searchBy').value;
    var statusRumah = window.statusRumahAktif || 'subsidi';

    jQuery('#btn-prev-page, #btn-next-page').prop('disabled', true);
    jQuery('#temp_rumah').html(SKELETON_HALAMAN);

    $.ajax({
        url: '<?= base_url('cari_wil') ?>?kodeWilayah='+encodeURIComponent(kodeWilayah)+'&keyword='+encodeURIComponent(keyword)+'&searchBy='+encodeURIComponent(searchBy)+'&sort='+encodeURIComponent(sort)+'&status_rumah='+statusRumah+'&page='+halaman+'&limit=12',
        success: function(response) {
            // Gagal jaringan dibedakan dari halaman yang memang kosong -
            // lihat komentar penanda ini di Index::cari_wil().
            if (response.indexOf('<!-- gagal-jaringan -->') !== -1) {
                jQuery('#temp_rumah').html(response);
                jQuery('#btn-prev-page').prop('disabled', halaman <= 1);
                jQuery('#btn-next-page').prop('disabled', false);
                return;
            }

            var cocok = response.match(/<!--\s*jumlah:(\d+)\s*-->/);
            var jumlah = cocok ? parseInt(cocok[1], 10) : 0;

            window.halamanSekarang = halaman;
            jQuery('#temp_rumah').html(response);
            jQuery('#label-halaman').text('Halaman ' + halaman);
            jQuery('#btn-prev-page').prop('disabled', halaman <= 1);
            // Kurang dari 12 berarti sumbernya sudah habis di halaman ini -
            // konsisten dengan cara lokasi_tersaring() menandai "habis" di server.
            jQuery('#btn-next-page').prop('disabled', jumlah < 12);

            if (gulirKeAtas) {
                document.getElementById('temp_rumah').scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        },
        error: function() {
            jQuery('#temp_rumah').html('<p class="col-span-full py-10 text-center text-sm text-[color:var(--portal-text-muted)]">Data rumah gagal dimuat. Silakan coba lagi.</p>');
            jQuery('#btn-prev-page').prop('disabled', halaman <= 1);
            jQuery('#btn-next-page').prop('disabled', false);
        }
    });
}

function gantiHalaman(delta) {
    var target = window.halamanSekarang + delta;
    if (target < 1) { return; }
    muatHalaman(target, true);
}

function cari_wil() {
    muatHalaman(1, false);
}

document.addEventListener('DOMContentLoaded', function () {
    muatHalaman(1, false);
});
</script>
