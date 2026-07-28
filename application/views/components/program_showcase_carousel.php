<?php $carousel_context = $carousel_context ?? 'home'; ?>

<div x-data="solutionProgramShowcase()" class="relative mx-auto w-full">
    <button type="button" @click="previous()" aria-label="Program sebelumnya" class="absolute left-0 sm:-left-4 top-1/2 z-10 -translate-y-1/2 h-10 w-10 rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] text-[color:var(--portal-text)] shadow-sm transition hover:border-[color:var(--portal-brand)]"><i class="fa-solid fa-chevron-left"></i></button>
    <button type="button" @click="next()" aria-label="Program berikutnya" class="absolute right-0 sm:-right-4 top-1/2 z-10 -translate-y-1/2 h-10 w-10 rounded-xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] text-[color:var(--portal-text)] shadow-sm transition hover:border-[color:var(--portal-brand)]"><i class="fa-solid fa-chevron-right"></i></button>

    <div class="overflow-hidden rounded-3xl border border-[color:var(--portal-border)] bg-[color:var(--portal-bg-card)] shadow-sm">
        <template x-for="(slide, index) in slides" :key="slide.id">
            <article x-show="active === index" x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-x-5" x-transition:enter-end="opacity-100 translate-x-0" class="relative grid min-h-[300px] md:grid-cols-5" style="display:none" :style="'--slide-accent:' + slide.color + ';background:' + slide.color">
                <div class="absolute inset-0 pointer-events-none opacity-[0.045]" style="background-image:radial-gradient(circle at 1px 1px, #00545f 1px, transparent 0);background-size:22px 22px"></div>
                <div class="relative min-h-[170px] md:col-span-2">
                    <img :src="slide.image" :alt="slide.title" class="absolute inset-0 h-full w-full object-cover" style="mask-image:linear-gradient(to right,#000 58%,transparent 100%);-webkit-mask-image:linear-gradient(to right,#000 58%,transparent 100%)">
                </div>
                <div class="relative flex flex-col justify-center p-6 text-white md:col-span-3 md:p-8">
                    <span class="mb-3 inline-flex w-max items-center gap-2 text-[11px] font-bold uppercase tracking-widest" :style="'color:' + slide.color"><span class="h-2 w-2 rounded-full" :style="'background:' + slide.color"></span><span x-text="slide.badge"></span></span>
                    <h2 class="text-3xl font-black text-white sm:text-4xl" x-text="slide.title"></h2>
                    <p class="mt-3 text-sm leading-relaxed text-white/85" x-text="slide.description"></p>
                    <div class="mt-4 border-t border-white/20 pt-4 text-xs leading-relaxed text-white/80"><span class="font-bold text-white">Syarat utama: </span><span x-text="slide.terms"></span></div>
                    <?php if (in_array($carousel_context, ['diagnosa', 'warga'], TRUE)): ?>
                    <button type="button" @click="document.getElementById('<?= $carousel_context === 'warga' ? 'form-pendataan-warga' : 'form-diagnosa' ?>').scrollIntoView({ behavior: 'smooth', block: 'start' })" class="program-showcase-action mt-5 inline-flex w-max items-center gap-2 rounded-full px-5 py-2.5 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:brightness-110" :style="'background:' + slide.color + ';border-color:' + slide.color">Cek Kelayakan <i class="fa-solid fa-arrow-right"></i></button>
                    <?php else: ?>
                    <a href="<?= base_url('solusi_pembiayaan') ?>" data-tab-link data-tab-key="solusi_pembiayaan" class="program-showcase-action mt-5 inline-flex w-max items-center gap-2 rounded-full px-5 py-2.5 text-sm font-bold text-white transition hover:-translate-y-0.5 hover:brightness-110" :style="'background:' + slide.color + ';border-color:' + slide.color">Cek Kelayakan <i class="fa-solid fa-arrow-right"></i></a>
                    <?php endif; ?>
                </div>
            </article>
        </template>
    </div>
    <div class="mt-3 flex justify-center gap-2">
        <template x-for="(slide, index) in slides" :key="slide.id + '-dot'"><button type="button" @click="active = index" class="h-2 rounded-full transition-all" :class="active === index ? 'w-6' : 'w-2 bg-[color:var(--portal-border)]'" :style="active === index ? 'background:' + slide.color : ''" :aria-label="'Lihat ' + slide.title"></button></template>
    </div>
</div>

<script>
    function solutionProgramShowcase() {
        return {
            active: 0,
            timer: null,
            slides: [
                { id: 'flpp', title: 'KPR-FLPP Rumah Subsidi', badge: 'MBR Fixed Income', color: '#007f8f', image: '<?= base_url('assets/img/program/01_subsidif_lpp.avif') ?>', description: 'Skema pembiayaan rumah subsidi dengan bunga tetap dan tenor panjang.', terms: 'Penghasilan maksimal Rp8 juta per bulan dan memenuhi ketentuan MBR.' },
                { id: 'oemah-lestari', title: 'Oemah Lestari', badge: 'MBR & Umum', color: '#07865a', image: '<?= base_url('assets/img/program/02_oemahletari.avif') ?>', description: 'Pembiayaan rumah terjangkau yang berpihak pada hunian ramah lingkungan.', terms: 'Skema bunga ringan melalui kolaborasi BPR-BRK.' },
                { id: 'rtlh', title: 'Peningkatan Kualitas RTLH', badge: 'Miskin & Ekstrem', color: '#b45309', image: '<?= base_url('assets/img/program/03_rtlh.avif') ?>', description: 'Bantuan perbaikan rumah bagi masyarakat dengan hunian tidak layak.', terms: 'Terdaftar DTKS dan rumah memenuhi kriteria kerusakan.' },
                { id: 'pb', title: 'Stimulan Pembangunan Baru', badge: 'Bencana & Relokasi', color: '#647a00', image: '<?= base_url('assets/img/program/04_Bantuan.avif') ?>', description: 'Bantuan stimulan material untuk pembangunan rumah baru yang layak.', terms: 'Tersedia untuk skema backlog, relokasi, atau pascabencana.' },
                { id: 'rumah-apung', title: 'Program Rumah Apung', badge: 'Kawasan Pesisir', color: '#2563eb', image: '<?= base_url('assets/img/program/05_areakumuh.avif') ?>', description: 'Solusi hunian adaptif untuk masyarakat pesisir terdampak rob.', terms: 'Prioritas kawasan pesisir yang mengalami genangan permanen.' }
            ],
            init() { this.timer = setInterval(() => this.next(), 6500); },
            next() { this.active = (this.active + 1) % this.slides.length; },
            previous() { this.active = (this.active - 1 + this.slides.length) % this.slides.length; }
        };
    }
</script>
