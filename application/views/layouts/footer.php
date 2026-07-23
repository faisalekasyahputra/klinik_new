<!-- ============================================================
     FOOTER SCRIPTS & WIDGETS
     Visual footer bar sekarang inline di main.php
     ============================================================ -->

<!-- ============================================================
     HELP WIDGET — FAQ seputar web (Bottom Right)
     ============================================================ -->
<div class="theme-light fixed bottom-6 right-6 z-50" x-data="{
        helpOpen: false,
        chatOpen: false,
        openFaq: null,
        faqs: [
            { q: 'Apa itu Klinik PKP?', a: 'Klinik PKP adalah portal layanan informasi dan konsultasi perumahan serta kawasan permukiman dari Disperakim Provinsi Jawa Tengah.' },
            { q: 'Bagaimana cara cek kelayakan bantuan rumah?', a: 'Buka menu Perumahan lalu pilih Etalase Program untuk melihat daftar program, atau langsung isi diagnosa NIK untuk mengecek program yang sesuai untuk Anda.' },
            { q: 'Bagaimana cara cek status pengajuan?', a: 'Buka tab \'Cek Status Pengajuan\' di menu utama, lalu masukkan nomor tiket dan empat digit terakhir NIK Anda.' },
            { q: 'Bagaimana cara daftar Sertifikasi Pengembang (SRP2)?', a: 'Buka menu Pengembang dari menu utama dan ikuti alur pendaftaran. Anda perlu login terlebih dahulu.' },
            { q: 'Bagaimana cara menyampaikan aduan?', a: 'Buka halaman Aduan dari beranda, isi formulir, dan pilih bidang tujuan yang sesuai dengan aduan Anda.' },
        ]
     }">

    <!-- FAQ Panel -->
    <div x-show="helpOpen && !chatOpen" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="help-menu mb-3 max-h-[70vh] w-[320px] overflow-y-auto rounded-2xl shadow-2xl"
         style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border);">
        <div class="p-5">
            <h4 class="text-sm font-bold" style="color: var(--portal-text);">Pertanyaan Umum</h4>
            <p class="mb-4 text-[11px]" style="color: var(--portal-text-muted);">Jawaban cepat seputar layanan Klinik PKP</p>

            <div class="space-y-2">
                <template x-for="(faq, i) in faqs" :key="i">
                    <div class="overflow-hidden rounded-xl border" style="border-color: var(--portal-border);">
                        <button type="button" @click="openFaq = (openFaq === i ? null : i)"
                                class="flex w-full items-center justify-between gap-3 px-3.5 py-3 text-left text-xs font-bold"
                                style="color: var(--portal-text);">
                            <span x-text="faq.q"></span>
                            <i class="fa-solid fa-chevron-down shrink-0 text-[10px] transition-transform duration-200" :class="openFaq === i && 'rotate-180'" style="color: var(--portal-text-muted);"></i>
                        </button>
                        <div x-show="openFaq === i" x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             class="px-3.5 pb-3 text-[11px] leading-relaxed" style="color: var(--portal-text-muted);" x-text="faq.a"></div>
                    </div>
                </template>
            </div>

            <button @click="chatOpen = true; helpOpen = false" class="mt-4 flex w-full items-center gap-3 rounded-xl p-3 text-left transition-all" style="background-color: var(--portal-btn-bg); border: 1px solid var(--portal-btn-border);">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg" style="background-color: var(--portal-brand); color: var(--portal-bg);">
                    <i class="fa-solid fa-comments text-sm"></i>
                </div>
                <div>
                    <span class="block text-xs font-bold" style="color: var(--portal-text);">Masih Butuh Bantuan?</span>
                    <span class="text-[10px]" style="color: var(--portal-text-muted);">Chat langsung dengan kami</span>
                </div>
                <i class="fa-solid fa-chevron-right ml-auto text-[10px]" style="color: var(--portal-text-muted);"></i>
            </button>
        </div>
    </div>

    <!-- Chat Widget Window -->
    <div x-show="chatOpen" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
         x-transition:enter-end="opacity-1 translate-y-0 scale-100"
         class="fixed bottom-24 right-6 z-50 w-[380px] overflow-hidden rounded-2xl shadow-2xl"
         style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border);">

        <div class="flex items-center justify-between px-5 py-4" style="background-color: var(--portal-brand);">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                <h3 class="font-bold text-sm" style="color: var(--portal-bg);">Asisten Klinik PKP</h3>
            </div>
            <button @click="chatOpen = false" class="transition-colors" style="color: var(--portal-bg);"><i class="fa-solid fa-xmark text-sm"></i></button>
        </div>

        <div id="pre-chat-section" class="p-6 space-y-4">
            <p class="text-xs text-center" style="color: var(--portal-text-muted);">Silakan lengkapi data diri Anda.</p>
            <form id="pre-chat-form" class="space-y-3 text-xs">
                <div>
                    <label class="block mb-1 font-medium" style="color: var(--portal-text-muted);">Nama Lengkap</label>
                    <input type="text" id="reg-nama" required class="w-full rounded-xl px-3.5 py-2.5 outline-none transition-all" style="background-color: var(--portal-bg); border: 1px solid var(--portal-border); color: var(--portal-text);" placeholder="Budi Santoso">
                </div>
                <div>
                    <label class="block mb-1 font-medium" style="color: var(--portal-text-muted);">Email</label>
                    <input type="email" id="reg-email" required class="w-full rounded-xl px-3.5 py-2.5 outline-none transition-all" style="background-color: var(--portal-bg); border: 1px solid var(--portal-border); color: var(--portal-text);" placeholder="nama@email.com">
                </div>
                <div>
                    <label class="block mb-1 font-medium" style="color: var(--portal-text-muted);">No. WhatsApp</label>
                    <input type="tel" id="reg-hp" required class="w-full rounded-xl px-3.5 py-2.5 outline-none transition-all" style="background-color: var(--portal-bg); border: 1px solid var(--portal-border); color: var(--portal-text);" placeholder="08XXXXXXXXXX">
                </div>
                <div>
                    <label class="block mb-1 font-medium" style="color: var(--portal-text-muted);">Pesan / Keluhan</label>
                    <textarea id="reg-pesan" rows="2" required class="w-full rounded-xl px-3.5 py-2.5 outline-none transition-all leading-relaxed" style="background-color: var(--portal-bg); border: 1px solid var(--portal-border); color: var(--portal-text);" placeholder="Tanya bantuan RTLH..."></textarea>
                </div>
                <button type="submit" class="w-full font-bold py-3 rounded-xl transition-all text-[11px] mt-1 uppercase tracking-wider" style="background-color: var(--portal-brand); color: var(--portal-bg);">Mulai Percakapan</button>
            </form>
        </div>

        <div id="live-chat-section" class="hidden flex flex-col h-[420px]">
            <div id="chat-body" class="flex-1 p-4 overflow-y-auto space-y-3 custom-scroll" style="background-color: var(--portal-bg);">
                <div class="flex justify-start">
                    <div class="text-xs p-3 rounded-2xl rounded-tl-none max-w-[85%] leading-relaxed" style="background-color: var(--portal-bg-card); border: 1px solid var(--portal-border); color: var(--portal-text-muted);">
                        Halo! Ada yang bisa saya bantu seputar perumahan Jawa Tengah?
                    </div>
                </div>
            </div>
            <div class="p-4" style="background-color: var(--portal-bg-card); border-top: 1px solid var(--portal-border);">
                <form id="chat-form" class="flex items-center gap-2">
                    <input type="text" id="chat-input" class="flex-1 rounded-xl px-4 py-3 text-xs outline-none transition-all" style="background-color: var(--portal-bg); border: 1px solid var(--portal-border); color: var(--portal-text);" placeholder="Ketik pesan...">
                    <button type="submit" class="w-10 h-10 rounded-xl flex items-center justify-center transition-all shrink-0" style="background-color: var(--portal-brand); color: var(--portal-bg);"><i class="fa-solid fa-paper-plane text-xs"></i></button>
                </form>
            </div>
        </div>
    </div>

    <!-- FAB Button -->
    <button @click="chatOpen ? chatOpen = false : helpOpen = !helpOpen" class="help-fab animate-glow">
        <i class="fa-solid transition-transform duration-300" :class="helpOpen || chatOpen ? 'fa-xmark' : 'fa-circle-question'"></i>
    </button>
</div>

<!-- ============================================================
     SWIPER JS
     ============================================================ -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
<script>
    // Initialize AOS
    AOS.init({
        duration: 800,
        once: true,
        offset: 50,
    });

    document.addEventListener('DOMContentLoaded', function () {
        var initialSkeleton = document.getElementById('page-loading-skeleton');
        if (initialSkeleton) requestAnimationFrame(function () { initialSkeleton.classList.add('is-hidden'); });
    });

    // Lenis smooth scroll disabled — panel now uses native overflow-y scroll
</script>

<!-- ============================================================
     CHAT SCRIPTS
     ============================================================ -->
<script>
$(document).ready(function() {
    $('#pre-chat-form').on('submit', function(e) {
        e.preventDefault();
        let nama  = $('#reg-nama').val().trim();
        let email = $('#reg-email').val().trim();
        let hp    = $('#reg-hp').val().trim();
        let pesan = $('#reg-pesan').val().trim();
        let session_id = 'SESS_' + Math.random().toString(36).substr(2, 9);
        $.ajax({
            url: '<?= base_url('Chat/register_session') ?>',
            type: 'POST',
            data: { session_id, nama, email, hp, pesan_awal: pesan },
            dataType: 'JSON',
            success: function(r) {
                if (r.status == 'success') {
                    localStorage.setItem('chat_session_id', session_id);
                    $('#pre-chat-section').addClass('hidden');
                    $('#live-chat-section').removeClass('hidden');
                    muatChat();
                } else { alert('Gagal memulai sesi.'); }
            },
            error: function(xhr) { console.error(xhr.responseText); }
        });
    });

    function muatChat() {
        if ($('#live-chat-section').hasClass('hidden')) return;
        const session_id = localStorage.getItem('chat_session_id');
        if (!session_id) return;
        $.ajax({
            url: '<?= base_url('Chat/ambil_pesan') ?>',
            type: 'POST',
            data: { session_id, '<?= $this->security->get_csrf_token_name(); ?>': '<?= $this->security->get_csrf_hash(); ?>' },
            success: function(response) {
                let res = (typeof response === 'string') ? JSON.parse(response) : response;
                if (res.status === 'success') {
                    let html = '';
                    if (res.data.length === 0) {
                        html = '<div class="flex justify-start"><div class="bg-[#d6fb00]/5 border border-[#d6fb00]/20 text-zinc-300 text-xs p-3 rounded-2xl rounded-tl-none max-w-[85%]"><div class="text-[10px] text-[#d6fb00] font-bold uppercase tracking-wider mb-1">🤖 Asisten</div>Halo! Ada yang bisa saya bantu?</div></div>';
                    } else {
                        res.data.forEach(function(msg) {
                            if (msg.pengirim === 'warga') {
                                html += '<div class="flex justify-end"><div class="bg-[#d6fb00] text-[#0a1a1f] text-xs font-medium px-4 py-3 rounded-2xl rounded-tr-none max-w-[80%] whitespace-pre-line leading-relaxed">' + escapeHTML(msg.pesan) + '</div></div>';
                            } else {
                                let label = msg.pengirim === 'admin' ? '🏷️ Petugas' : '🤖 Asisten';
                                let color = msg.pengirim === 'admin' ? 'text-emerald-400' : 'text-[#d6fb00]';
                                html += '<div class="flex justify-start"><div class="bg-[#d6fb00]/5 border border-[#d6fb00]/20 text-zinc-200 text-xs px-4 py-3 rounded-2xl rounded-tl-none max-w-[80%] leading-relaxed"><div class="text-[10px] ' + color + ' font-bold uppercase tracking-wider mb-1">' + label + '</div><div class="whitespace-pre-line">' + escapeHTML(msg.pesan) + '</div></div></div>';
                            }
                        });
                    }
                    const chatBody = $('#chat-body');
                    const isAtBottom = chatBody[0].scrollHeight - chatBody.scrollTop() <= chatBody[0].clientHeight + 100;
                    chatBody.html(html);
                    if (isAtBottom) chatBody.scrollTop(chatBody[0].scrollHeight);
                }
            }
        });
    }

    function escapeHTML(str) {
        return str.replace(/[&<>'"]/g, t => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[t]||t));
    }

    setInterval(muatChat, 3000);

    $('#chat-form').on('submit', function(e) {
        e.preventDefault();
        const pesan = $('#chat-input').val().trim();
        const session_id = localStorage.getItem('chat_session_id');
        if (!pesan || !session_id) return;
        $('#chat-input').val('');
        $.ajax({
            url: '<?= base_url('Chat/kirim_pesan_lanjutan') ?>',
            type: 'POST',
            data: { session_id, pesan, '<?= $this->security->get_csrf_token_name(); ?>': '<?= $this->security->get_csrf_hash(); ?>' },
            success: function() { muatChat(); }
        });
    });

    // Auto-detect existing session
    const existingSession = localStorage.getItem('chat_session_id');
    if (existingSession) {
        // When chat opens, go straight to live chat
        const obs = new MutationObserver(function() {
            if (!$('#live-chat-section').hasClass('hidden')) return;
            if ($('#pre-chat-section').is(':visible')) {
                $('#pre-chat-section').addClass('hidden');
                $('#live-chat-section').removeClass('hidden');
                muatChat();
            }
        });
    }
});

function globalSystem() {
    return {
        mobileMenu: false,
        scrolled: false,
        init() {
            window.addEventListener('scroll', () => {
                this.scrolled = window.scrollY > 20;
            });
            this.scrolled = window.scrollY > 20;
        }
    }
}

// ============================================================
// TAB NAVIGATION LOADER
// ============================================================
// Link navbar bertanda [data-tab-link] tidak pindah halaman penuh —
// isi #page-content-wrapper ditukar via fetch AJAX, lalu
// script di dalamnya dijalankan ulang (urut, menunggu script eksternal
// seperti Leaflet/Chart.js selesai load sebelum script berikutnya jalan
// — sama seperti urutan pemuatan halaman normal), Alpine & AOS di-init
// ulang untuk konten baru, dan URL di-update lewat history API supaya
// tombol back/forward & reload/bookmark tetap berfungsi wajar.
(function () {
    // Maps sub-page tab keys to their parent tab, so the correct
    // main tab stays highlighted when a sub-page is loaded via AJAX.
    var TAB_GROUPS = {
        simulasi_kpr: 'perumahan',
        panduan_desain: 'perumahan',
        golek_omah: 'perumahan',
        cari_rumah: 'perumahan',
        solusi_pembiayaan: 'perumahan',
        etalase: 'perumahan',
        sebaran: 'kawasan',
        sebaran_rusun: 'kawasan',
        profil_kumuh: 'kawasan',
        sebaran_sdgs: 'kawasan',
        info_tanah: 'pertanahan',
        sertifikasi_tanah: 'pertanahan',
        sengketa: 'pertanahan',
        bank_tanah: 'pertanahan',
        pengembang_list: 'pengembang',
        pengembang_syarat: 'pengembang',
        pengembang_formulir: 'pengembang',
        statistika: 'bankdata'
    };

    function setActiveTabKey(key) {
        // Remove active from all portal tabs
        document.querySelectorAll('.portal-tab-btn').forEach(function (el) {
            el.classList.remove('active');
        });

        // Resolve sub-page key to parent tab key
        var tabKey = TAB_GROUPS[key] || key;

        // Activate the matching main tab
        var activeTab = document.querySelector('.portal-tab-btn[data-tab-key="' + tabKey + '"]');
        if (activeTab) activeTab.classList.add('active');
    }

    // Jalankan ulang <script> yang ikut masuk lewat innerHTML (browser
    // tidak otomatis mengeksekusinya), URUT satu-satu — script eksternal
    // (src=...) ditunggu sampai 'load' dulu sebelum lanjut ke script
    // berikutnya, supaya library seperti Leaflet/Chart.js sudah siap
    // saat script inline yang memakainya dijalankan.
    function reExecuteScripts(wrapper) {
        var scripts = Array.prototype.slice.call(wrapper.querySelectorAll('script'));
        return scripts.reduce(function (chain, oldScript) {
            return chain.then(function () {
                return new Promise(function (resolve) {
                    var newScript = document.createElement('script');
                    for (var i = 0; i < oldScript.attributes.length; i++) {
                        var attr = oldScript.attributes[i];
                        newScript.setAttribute(attr.name, attr.value);
                    }
                    newScript.textContent = oldScript.textContent;
                    if (newScript.src) {
                        newScript.onload = resolve;
                        newScript.onerror = resolve; // jangan macet kalau CDN gagal
                        oldScript.replaceWith(newScript);
                    } else {
                        oldScript.replaceWith(newScript); // inline: jalan sinkron saat disisipkan
                        resolve();
                    }
                });
            });
        }, Promise.resolve());
    }

    function reinitContent(wrapper, mutationsDeferred) {
        // Halaman ini sudah lama "DOMContentLoaded" sejak load pertama —
        // script per-halaman yang menunggu event itu (pola umum di proyek
        // ini) tidak akan pernah jalan lagi kalau tidak di-shim begini.
        var originalAdd = document.addEventListener.bind(document);
        document.addEventListener = function (type, listener, options) {
            if (type === 'DOMContentLoaded') { listener(); return; }
            return originalAdd(type, listener, options);
        };

        return reExecuteScripts(wrapper).then(function () {
            document.addEventListener = originalAdd;
            if (mutationsDeferred && window.Alpine && Alpine.flushAndStopDeferringMutations) {
                Alpine.flushAndStopDeferringMutations();
            }
            if (window.Alpine) {
                Alpine.initTree(wrapper);
            }
            if (window.AOS) AOS.refreshHard();
        }).catch(function (error) {
            document.addEventListener = originalAdd;
            if (mutationsDeferred && window.Alpine && Alpine.flushAndStopDeferringMutations) {
                Alpine.flushAndStopDeferringMutations();
            }
            throw error;
        });
    }

    var SKELETON_HTML = '<div class="page-skeleton animate-pulse flex min-h-full flex-col py-4 sm:py-6 px-1 sm:px-2 space-y-4">'
        + '<div class="flex items-center gap-2 mb-3"><div class="w-4 h-4 rounded bg-[color:var(--portal-skeleton)]"></div><div class="h-3 w-36 rounded bg-[color:var(--portal-skeleton)]"></div></div>'
        + '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2 sm:gap-3">'
        + '<div class="rounded-2xl p-3.5 sm:p-4 space-y-3" style="background:#fff;border: 1px solid var(--portal-border);min-height:120px"><div class="w-6 h-6 rounded-lg bg-[#0a1a1f]/6"></div><div class="h-3.5 w-3/4 rounded bg-[#0a1a1f]/6"></div><div class="space-y-1.5"><div class="h-2 w-full rounded bg-[#0a1a1f]/5"></div><div class="h-2 w-2/3 rounded bg-[#0a1a1f]/5"></div></div><div class="mt-auto pt-2.5"><div class="h-5 w-20 rounded-full bg-[#0a1a1f]/6"></div></div></div>'
        + '<div class="rounded-2xl p-3.5 sm:p-4 space-y-3" style="background:#fff;border: 1px solid var(--portal-border);min-height:120px"><div class="w-6 h-6 rounded-lg bg-[#0a1a1f]/6"></div><div class="h-3.5 w-2/3 rounded bg-[#0a1a1f]/6"></div><div class="space-y-1.5"><div class="h-2 w-full rounded bg-[#0a1a1f]/5"></div><div class="h-2 w-1/2 rounded bg-[#0a1a1f]/5"></div></div><div class="mt-auto pt-2.5"><div class="h-5 w-20 rounded-full bg-[#0a1a1f]/6"></div></div></div>'
        + '<div class="rounded-2xl p-3.5 sm:p-4 space-y-3 hidden sm:block" style="background:#fff;border: 1px solid var(--portal-border);min-height:120px"><div class="w-6 h-6 rounded-lg bg-[#0a1a1f]/6"></div><div class="h-3.5 w-1/2 rounded bg-[#0a1a1f]/6"></div><div class="space-y-1.5"><div class="h-2 w-full rounded bg-[#0a1a1f]/5"></div><div class="h-2 w-3/4 rounded bg-[#0a1a1f]/5"></div></div><div class="mt-auto pt-2.5"><div class="h-5 w-20 rounded-full bg-[#0a1a1f]/6"></div></div></div>'
        + '</div></div>';

    var loadToken = 0;
    function loadTab(url, key, push) {
        var wrapper = document.getElementById('page-content-wrapper');
        if (!wrapper) { window.location.href = url; return; }
        var panel = wrapper.closest('.portal-panel');
        var myToken = ++loadToken;

        // 1) Langsung aktifkan tab (progresif — tidak menunggu fetch)
        setActiveTabKey(key);

        // 2) Pertahankan tinggi panel dan tampilkan skeleton segera.
        wrapper.style.transition = 'opacity 0.12s ease-out';
        wrapper.style.opacity = '1';
        var pageSkeleton = SKELETON_HTML;
        if (/pengembang\/(sertifikasi|syarat|formulir|profil|dokumen)/i.test(url)) {
            pageSkeleton = '<div class="page-skeleton animate-pulse min-h-full py-4 sm:py-6 px-1 sm:px-2">'
                + '<div class="h-3 w-16 rounded bg-[color:var(--portal-skeleton)] mb-2"></div>'
                + '<div class="h-7 w-64 rounded bg-[color:var(--portal-skeleton)] mb-4"></div>'
                + '<div class="rounded-2xl overflow-hidden" style="background:var(--portal-bg-card);border:1px solid var(--portal-border)">'
                + '<div class="flex items-center justify-between p-4 border-b" style="border-color:var(--portal-border)"><div class="h-4 w-44 rounded bg-[color:var(--portal-skeleton)]"></div><div class="h-7 w-24 rounded bg-[color:var(--portal-skeleton)]"></div></div>'
                + '<div class="p-3 space-y-2"><div class="h-8 rounded bg-[color:var(--portal-skeleton)]"></div><div class="h-8 rounded bg-[color:var(--portal-skeleton)]"></div><div class="h-8 rounded bg-[color:var(--portal-skeleton)]"></div><div class="h-8 rounded bg-[color:var(--portal-skeleton)]"></div><div class="h-8 rounded bg-[color:var(--portal-skeleton)]"></div></div></div></div>';
        }
        if (key === 'panduan_desain') {
            var designSkeletonCard = '<div class="rounded-2xl overflow-hidden" style="background:#fff;border:1px solid var(--portal-border);min-height:260px">'
                + '<div class="h-48 bg-[color:var(--portal-skeleton)]"></div>'
                + '<div class="space-y-2 p-4"><div class="h-2.5 w-24 rounded bg-[color:var(--portal-skeleton)]"></div><div class="h-4 w-4/5 rounded bg-[color:var(--portal-skeleton)]"></div><div class="h-9 rounded-xl bg-[color:var(--portal-skeleton)]"></div></div></div>';
            pageSkeleton = '<div class="page-skeleton animate-pulse flex min-h-full flex-col py-4 sm:py-6 px-1 sm:px-2 space-y-4">'
                + '<div class="flex items-center gap-2 mb-3"><div class="w-4 h-4 rounded bg-[color:var(--portal-skeleton)]"></div><div class="h-3 w-36 rounded bg-[color:var(--portal-skeleton)]"></div></div>'
                + '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-3">'
                + designSkeletonCard.repeat(5)
                + '</div></div>';
        }
        wrapper.innerHTML = pageSkeleton;
        if (panel) panel.scrollTop = 0;

        // 3) Fetch konten baru
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (res) {
                if (myToken !== loadToken) return null;
                if (!res.ok) { window.location.href = url; return null; }
                return res.text();
            })
            .then(function (html) {
                if (html === null || html === undefined || myToken !== loadToken) return;
                if (!html.trim()) html = '<div class="page-skeleton animate-pulse min-h-full"></div>';
                // 4) Fade out skeleton → inject konten baru
                wrapper.style.transition = 'opacity 0.1s ease-out';
                wrapper.style.opacity = '0';
                setTimeout(function () {
                    if (myToken !== loadToken) return;
                    var mutationsDeferred = !!(window.Alpine && Alpine.deferMutations);
                    if (mutationsDeferred) Alpine.deferMutations();
                    wrapper.innerHTML = html;
                    reinitContent(wrapper, mutationsDeferred).then(function () {
                        if (push) history.pushState({ tabUrl: url, tabKey: key }, '', url);
                        if (panel) panel.scrollTop = 0;
                        // 5) Fade in konten baru — halus
                        wrapper.style.transition = 'opacity 0.25s ease-in';
                        wrapper.style.opacity = '1';
                    }).catch(function () {
                        // Jangan biarkan error script mengunci panel dalam keadaan opacity 0.
                        wrapper.style.opacity = '1';
                    });
                }, 100);
            })
            .catch(function () {
                if (myToken === loadToken) window.location.href = url;
            });
    }

    document.addEventListener('click', function (e) {
        var link = e.target.closest('[data-tab-link]');
        if (!link) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        e.preventDefault();
        loadTab(link.getAttribute('href'), link.getAttribute('data-tab-key'), true);
    });

    window.addEventListener('popstate', function (e) {
        if (e.state && e.state.tabUrl) {
            loadTab(e.state.tabUrl, e.state.tabKey, false);
        }
    });
})();
</script>
