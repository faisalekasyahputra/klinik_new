<?php $carousel_context = $carousel_context ?? 'home'; ?>

<style>
    .program-slide-track {
        touch-action: pan-y;            /* geser mendatar untuk kami, gulir tegak untuk halaman */
        user-select: none;
        will-change: transform;
        transition: transform .6s cubic-bezier(.22, .75, .24, 1);
    }
    /* Tanpa transisi: saat jari masih menempel, dan saat rel dipulangkan
       diam-diam dari slide klon ke slide pertama. */
    .program-slide-track.is-still { transition: none; }
    /* Slide berjajar rapat: garis tepi 1px milik .aurora-surface akan terlihat
       sebagai jahitan antar slide, jadi dimatikan — bibir cahayanya sudah
       datang dari box-shadow inset, dan bingkai luar punya bordernya sendiri. */
    .program-slide { border: 0; border-radius: 0; }

    /* Tinta slide mewaris dari .aurora-surface (pastel bertinta gelap sejak
       5 Agt 2026). Turunannya ditulis di sini sebagai opacity, BUKAN sebagai
       kelas Tailwind `text-white/85` seperti dulu: nilainya harus ikut kalau
       tinta induknya berubah lagi, dan kelas nilai-sembarang belum tentu ikut
       terpanen ke tailwind-*.css. */
    .program-slide-badge { background: rgba(255, 255, 255, .6); }
    .program-slide-desc  { opacity: .82; }
    .program-slide-terms { opacity: .82; border-color: rgba(22, 50, 58, .16); }
    .program-slide-terms > span:first-child { opacity: 1; }
    /* Tombol dibalik: dulu putih dengan teks sewarna slide — di atas pastel
       muda itu putih-di-atas-putih. Sekarang tinta pekat, teks putih. */
    .program-showcase-action { background: #16323a; color: #fff; }
    /* Gambar memudar ke bawah saat satu kolom, ke kanan saat sudah dua kolom */
    .program-slide-media img {
        -webkit-mask-image: linear-gradient(to bottom, #000 62%, transparent 100%);
        mask-image: linear-gradient(to bottom, #000 62%, transparent 100%);
    }
    @media (min-width: 768px) {
        .program-slide-media img {
            -webkit-mask-image: linear-gradient(to right, #000 58%, transparent 100%);
            mask-image: linear-gradient(to right, #000 58%, transparent 100%);
        }
    }
    @media (prefers-reduced-motion: reduce) {
        .program-slide-track { transition-duration: .2s; }
    }
</style>

<div x-data="solutionProgramShowcase()" @mouseenter="stop()" @mouseleave="start()" class="relative mx-auto w-full">
    <!-- Panah disembunyikan di layar kecil: di sana geseran jari + titik indikator
         sudah cukup, dan tombol melayang malah menutupi teks slide. -->
    <button type="button" @click="previous()" aria-label="Program sebelumnya" class="absolute left-0 sm:-left-4 top-1/2 z-10 hidden h-10 w-10 -translate-y-1/2 rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] text-[color:var(--portal-text)] shadow-sm transition hover:border-[color:var(--portal-brand)] sm:block"><i class="fa-solid fa-chevron-left"></i></button>
    <button type="button" @click="next()" aria-label="Program berikutnya" class="absolute right-0 sm:-right-4 top-1/2 z-10 hidden h-10 w-10 -translate-y-1/2 rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] text-[color:var(--portal-text)] shadow-sm transition hover:border-[color:var(--portal-brand)] sm:block"><i class="fa-solid fa-chevron-right"></i></button>

    <!-- Semua slide berjajar dalam satu rel; yang bergerak relnya, bukan slide
         muncul-hilang. Tingginya ikut slide terpanjang, jadi kartunya tidak
         melompat saat berganti. -->
    <div class="overflow-hidden rounded-3xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] shadow-sm"
         @pointerdown="grab($event)" @pointermove="move($event)" @pointerup="drop()" @pointercancel="drop()" @pointerleave="drop()" @dragstart.prevent>
        <div x-ref="rel" class="program-slide-track flex" :class="(dragging || jumping) ? 'is-still' : ''"
             :style="'transform:translate3d(calc(' + (active * -100) + '% + ' + dx + 'px),0,0)'">
        <template x-for="(slide, index) in reel" :key="slide.id">
            <!-- Latar slide memakai .aurora-surface yang sama dengan kartu menu
                 beranda; slide cuma menyetor tiga warnanya sendiri. Varian aurora
                 dihitung dari index yang sudah dimodulo panjang daftar — slide klon
                 di ujung harus persis sama dengan slide pertama, kalau tidak
                 pemulangan rel jadi kelihatan. -->
            <article class="program-slide aurora-surface relative grid w-full shrink-0 grid-cols-1 min-h-[300px] md:grid-cols-5"
                     :class="'aurora-' + ((index % slides.length) % 4 + 1)"
                     :style="'--t1:' + slide.muda + ';--t2:' + slide.tengah + ';--t3:' + slide.pekat">
                <!-- Mask memudar ke bawah di layar sempit (gambar di atas teks),
                     ke kanan begitu jadi dua kolom. -->
                <div class="program-slide-media relative z-10 min-h-[170px] md:col-span-2">
                    <img :src="slide.image" :alt="slide.title" draggable="false" loading="lazy" class="absolute inset-0 h-full w-full select-none object-cover">
                </div>
                <div class="relative z-10 flex flex-col justify-center p-6 md:col-span-3 md:p-8">
                    <span class="program-slide-badge mb-3 inline-flex w-max items-center gap-2 rounded-full px-3 py-1 text-[11px] font-bold uppercase tracking-widest"><span class="h-2 w-2 rounded-full" :style="'background:' + slide.pekat"></span><span x-text="slide.badge"></span></span>
                    <h2 class="text-3xl font-black sm:text-4xl" x-text="slide.title"></h2>
                    <p class="program-slide-desc mt-3 text-sm leading-relaxed" x-text="slide.description"></p>
                    <div class="program-slide-terms mt-4 border-t pt-4 text-xs leading-relaxed"><span class="font-bold">Syarat utama: </span><span x-text="slide.terms"></span></div>
                    <?php if (in_array($carousel_context, ['diagnosa', 'warga'], TRUE)): ?>
                    <button type="button" @click="document.getElementById('<?= $carousel_context === 'warga' ? 'form-pendataan-warga' : 'form-diagnosa' ?>').scrollIntoView({ behavior: 'smooth', block: 'start' })" class="program-showcase-action mt-5 inline-flex w-max items-center gap-2 rounded-full px-5 py-2.5 text-sm font-bold shadow-sm transition hover:-translate-y-0.5 hover:brightness-125">Cek Kelayakan <i class="fa-solid fa-arrow-right"></i></button>
                    <?php else: ?>
                    <!-- Diarahkan ke wizard pendataan warga (1 Agu 2026), bukan lagi
                         diagnosa singkat `solusi_pembiayaan`. Halaman wizard menuntut
                         sesi, jadi pengunjung anonim mendarat di layar masuk dulu. -->
                    <a href="<?= base_url('warga/pendataan') ?>" data-tab-link data-tab-key="warga_pendataan" class="program-showcase-action mt-5 inline-flex w-max items-center gap-2 rounded-full px-5 py-2.5 text-sm font-bold shadow-sm transition hover:-translate-y-0.5 hover:brightness-125">Cek Kelayakan <i class="fa-solid fa-arrow-right"></i></a>
                    <?php endif; ?>
                </div>
            </article>
        </template>
        </div>
    </div>
    <div class="mt-3 flex justify-center gap-2">
        <template x-for="(slide, index) in slides" :key="slide.id + '-dot'"><button type="button" @click="go(index)" class="h-2 rounded-full transition-all" :class="(active % slides.length) === index ? 'w-6' : 'w-2 bg-[color:var(--portal-border)]'" :style="(active % slides.length) === index ? 'background:' + slide.pekat : ''" :aria-label="'Lihat ' + slide.title"></button></template>
    </div>
</div>

<script>
    function solutionProgramShowcase() {
        return {
            active: 0,
            timer: null,
            // muda → tengah → pekat, sejalan dengan --t1..--t3 di .aurora-surface.
            // Dulu bernama cool/color/warm saat palet masih gelap; nama itu jadi
            // menyesatkan begitu urutannya terang→pekat, bukan dingin→hangat.
            slides: [
                { id: 'flpp', title: 'KPR-FLPP Rumah Subsidi', badge: 'MBR Fixed Income', muda: '#dff2f4', tengah: '#b5e2e6', pekat: '#74c9d1', image: '<?= base_url('assets/img/program/01_subsidif_lpp.avif') ?>', description: 'Skema pembiayaan rumah subsidi dengan bunga tetap dan tenor panjang.', terms: 'Penghasilan maksimal Rp8 juta per bulan dan memenuhi ketentuan MBR.' },
                { id: 'oemah-lestari', title: 'Oemah Lestari', badge: 'MBR & Umum', muda: '#e6f6ec', tengah: '#c0e8cf', pekat: '#85d3a6', image: '<?= base_url('assets/img/program/02_oemahletari.avif') ?>', description: 'Pembiayaan rumah terjangkau yang berpihak pada hunian ramah lingkungan.', terms: 'Skema bunga ringan melalui kolaborasi BPR-BRK.' },
                { id: 'rtlh', title: 'Peningkatan Kualitas RTLH', badge: 'Miskin & Ekstrem', muda: '#fdf1dc', tengah: '#f8dcab', pekat: '#eebe6c', image: '<?= base_url('assets/img/program/03_rtlh.avif') ?>', description: 'Bantuan perbaikan rumah bagi masyarakat dengan hunian tidak layak.', terms: 'Terdaftar DTKS dan rumah memenuhi kriteria kerusakan.' },
                { id: 'pb', title: 'Stimulan Pembangunan Baru', badge: 'Bencana & Relokasi', muda: '#f2f7dc', tengah: '#dfeba6', pekat: '#c2d961', image: '<?= base_url('assets/img/program/04_Bantuan.avif') ?>', description: 'Bantuan stimulan material untuk pembangunan rumah baru yang layak.', terms: 'Tersedia untuk skema backlog, relokasi, atau pascabencana.' },
                { id: 'rumah-apung', title: 'Program Rumah Apung', badge: 'Kawasan Pesisir', muda: '#e5eefc', tengah: '#c0d5f4', pekat: '#8fb1e8', image: '<?= base_url('assets/img/program/05_areakumuh.avif') ?>', description: 'Solusi hunian adaptif untuk masyarakat pesisir terdampak rob.', terms: 'Prioritas kawasan pesisir yang mengalami genangan permanen.' }
            ],
            reel: [],
            dragging: false,
            jumping: false,
            x0: 0,
            dx: 0,

            // Rel berisi semua slide PLUS salinan slide pertama di ujung. Jalan
            // maju terus sampai salinan itu, lalu rel dipulangkan ke slide
            // pertama tanpa transisi — karena gambarnya identik, perpindahan itu
            // tidak terlihat. Hasilnya maju terus satu arah, tanpa mundur balik.
            init() {
                this.reel = [...this.slides, { ...this.slides[0], id: this.slides[0].id + '-klon' }];
                this.start();
            },
            start() { this.stop(); this.timer = setInterval(() => this.next(), 6500); },
            stop() { clearInterval(this.timer); },

            // Setiap perpindahan me-restart hitungan otomatis — kalau tidak,
            // slide bisa loncat sendiri sepersekian detik setelah user menekan panah.
            go(i) { this.active = i; this.start(); },
            next() {
                if (this.active >= this.slides.length) { this.diam(0, () => this.go(1)); return; }
                this.go(this.active + 1);
                // Baru masuk ke slide klon: jadwalkan pemulangannya begitu
                // geseran selesai. Pakai timer, bukan transitionend — di tab
                // latar transitionend bisa tidak pernah datang dan rel akan
                // tersangkut di klon selamanya.
                if (this.active === this.slides.length) { setTimeout(() => this.settle(), this.durasi()); }
            },
            durasi() {
                const d = this.$refs.rel ? getComputedStyle(this.$refs.rel).transitionDuration : '';
                return (parseFloat(d) || 0) * 1000 + 60;
            },
            previous() {
                // Dari slide pertama: pindah diam-diam ke salinan di ujung dulu,
                // baru mundur satu langkah — jadi mundurnya juga tetap mulus.
                if (this.active === 0) { this.diam(this.slides.length, () => this.go(this.slides.length - 1)); }
                else { this.go(this.active - 1); }
            },
            // Pindah posisi rel tanpa transisi, lalu jalankan lanjutannya.
            // setTimeout, bukan requestAnimationFrame: rAF berhenti total saat
            // tab tidak dirender, dan rel akan terkunci di kelas is-still.
            diam(i, lalu) {
                this.jumping = true;
                this.active = i;
                setTimeout(() => { this.jumping = false; if (lalu) lalu(); }, 40);
            },
            // Kalau rel sedang berdiri di slide klon, pulangkan ke slide pertama.
            settle() { if (this.active === this.slides.length && !this.dragging) { this.diam(0); } },

            // Geser jari/mouse: rel ikut jari dulu (tanpa transisi), baru
            // memutuskan pindah slide kalau jaraknya lewat 60px.
            grab(e) { if (e.button > 0) return; this.dragging = true; this.x0 = e.clientX; this.dx = 0; this.stop(); },
            move(e) { if (this.dragging) this.dx = e.clientX - this.x0; },
            drop() {
                if (!this.dragging) return;
                const geser = this.dx;
                this.dragging = false;
                this.dx = 0;
                if (Math.abs(geser) > 60) { geser < 0 ? this.next() : this.previous(); } else { this.start(); }
                if (this.active === this.slides.length) { setTimeout(() => this.settle(), this.durasi()); }
            }
        };
    }
</script>
