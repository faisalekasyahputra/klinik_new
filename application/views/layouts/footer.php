 <!-- ============================================================
     FOOTER
     ============================================================ -->
 <?php 
    $CI =& get_instance();
    $CI->load->model('Setting_model');
    $settings = $CI->Setting_model->get_all();
 ?>
 <footer class="w-full bg-[#0a1a1f] text-zinc-500 py-6 mt-auto relative z-10 border-t border-white/5">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-center sm:justify-between gap-3 text-center sm:text-left">
        <div class="flex items-center gap-2.5">
            <img src="<?= base_url('assets/img/logo-jateng.png') ?>" alt="Logo Jawa Tengah" class="h-6 w-auto object-contain">
            <span class="text-xs font-bold text-white">Klinik<span class="text-[#d6fb00]">PKP</span></span>
        </div>
        <div class="text-[11px] text-zinc-600">&copy; <?= date('Y') ?> <?= htmlspecialchars($settings['footer_copyright'] ?? 'KLINIK PKP JATENG') ?>. All Rights Reserved.</div>
    </div>
</footer>

<!-- ============================================================
     HELP WIDGET — Combined WA + Aduan (Bottom Right)
     ============================================================ -->
<div class="fixed bottom-6 right-6 z-50" x-data="{ helpOpen: false, chatOpen: false }">
    
    <!-- Help Menu Card -->
    <div x-show="helpOpen && !chatOpen" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
         x-transition:enter-end="opacity-1 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-1 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="help-menu glass-card mb-3">
        <div class="p-5">
            <h4 class="text-white font-bold text-sm mb-1">Butuh Bantuan?</h4>
            <p class="text-zinc-500 text-[11px] mb-4">Pilih cara yang paling nyaman untuk Anda</p>
            
            <a href="https://wa.me/6282137191145" target="_blank" class="flex items-center gap-3 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 hover:bg-emerald-500/20 transition-all mb-2.5 group">
                <div class="w-10 h-10 bg-emerald-500 rounded-xl flex items-center justify-center text-white text-lg shrink-0"><i class="fa-brands fa-whatsapp"></i></div>
                <div>
                    <span class="text-white text-xs font-bold block">WhatsApp Langsung</span>
                    <span class="text-zinc-500 text-[10px]">Chat cepat via WhatsApp</span>
                </div>
                <i class="fa-solid fa-arrow-up-right-from-square text-zinc-600 text-[10px] ml-auto group-hover:text-emerald-400"></i>
            </a>

            <button @click="chatOpen = true; helpOpen = false" class="w-full flex items-center gap-3 p-3 rounded-xl bg-[#d6fb00]/10 border border-[#d6fb00]/15 hover:bg-[#d6fb00]/20 transition-all group text-left">
                <div class="w-10 h-10 bg-[#d6fb00] rounded-xl flex items-center justify-center text-[#0a1a1f] text-lg shrink-0"><i class="fa-solid fa-comments"></i></div>
                <div>
                    <span class="text-white text-xs font-bold block">Aduan & Diskusi</span>
                    <span class="text-zinc-500 text-[10px]">Sampaikan keluhan atau saran</span>
                </div>
                <i class="fa-solid fa-chevron-right text-zinc-600 text-[10px] ml-auto group-hover:text-[#d6fb00]"></i>
            </button>
        </div>
    </div>

    <!-- Chat Widget Window -->
    <div x-show="chatOpen" x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2 scale-95"
         x-transition:enter-end="opacity-1 translate-y-0 scale-100"
         class="fixed bottom-24 right-6 w-[380px] bg-[#0d1f25] border border-[#d6fb00]/20 rounded-2xl overflow-hidden shadow-2xl z-50">
        
        <div class="bg-[#d6fb00] px-5 py-4 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
                <h3 class="text-[#0a1a1f] font-bold text-sm">Asisten Klinik PKP</h3>
            </div>
            <button @click="chatOpen = false" class="text-black/60 hover:text-black transition-colors"><i class="fa-solid fa-xmark text-sm"></i></button>
        </div>

        <div id="pre-chat-section" class="p-6 space-y-4">
            <p class="text-zinc-400 text-xs text-center">Silakan lengkapi data diri Anda.</p>
            <form id="pre-chat-form" class="space-y-3 text-xs">
                <div>
                    <label class="block text-zinc-500 mb-1 font-medium">Nama Lengkap</label>
                    <input type="text" id="reg-nama" required class="w-full bg-[#d6fb00]/5 border border-[#d6fb00]/20 focus:border-[#d6fb00]/40 rounded-xl px-3.5 py-2.5 text-white outline-none transition-all placeholder-[#5a7a80]" placeholder="Budi Santoso">
                </div>
                <div>
                    <label class="block text-zinc-500 mb-1 font-medium">Email</label>
                    <input type="email" id="reg-email" required class="w-full bg-[#d6fb00]/5 border border-[#d6fb00]/20 focus:border-[#d6fb00]/40 rounded-xl px-3.5 py-2.5 text-white outline-none transition-all placeholder-[#5a7a80]" placeholder="nama@email.com">
                </div>
                <div>
                    <label class="block text-zinc-500 mb-1 font-medium">No. WhatsApp</label>
                    <input type="tel" id="reg-hp" required class="w-full bg-[#d6fb00]/5 border border-[#d6fb00]/20 focus:border-[#d6fb00]/40 rounded-xl px-3.5 py-2.5 text-white outline-none transition-all placeholder-[#5a7a80]" placeholder="08XXXXXXXXXX">
                </div>
                <div>
                    <label class="block text-zinc-500 mb-1 font-medium">Pesan / Keluhan</label>
                    <textarea id="reg-pesan" rows="2" required class="w-full bg-[#d6fb00]/5 border border-[#d6fb00]/20 focus:border-[#d6fb00]/40 rounded-xl px-3.5 py-2.5 text-white outline-none transition-all placeholder-[#5a7a80] leading-relaxed" placeholder="Tanya bantuan RTLH..."></textarea>
                </div>
                <button type="submit" class="w-full bg-[#d6fb00] hover:bg-[#ecffb6] text-[#0a1a1f] font-bold py-3 rounded-xl transition-all text-[11px] mt-1 uppercase tracking-wider">Mulai Percakapan</button>
            </form>
        </div>

        <div id="live-chat-section" class="hidden flex flex-col h-[420px]">
            <div id="chat-body" class="flex-1 p-4 overflow-y-auto space-y-3 bg-[#090b0f] custom-scroll">
                <div class="flex justify-start">
                    <div class="bg-[#d6fb00]/5 border border-[#d6fb00]/20 text-zinc-300 text-xs p-3 rounded-2xl rounded-tl-none max-w-[85%] leading-relaxed">
                        Halo! Ada yang bisa saya bantu seputar perumahan Jawa Tengah?
                    </div>
                </div>
            </div>
            <div class="p-4 bg-[#0d1f25] border-t border-[#d6fb00]/20">
                <form id="chat-form" class="flex items-center gap-2">
                    <input type="text" id="chat-input" class="flex-1 bg-[#d6fb00]/5 border border-[#d6fb00]/20 focus:border-[#d6fb00]/40 rounded-xl px-4 py-3 text-white text-xs outline-none transition-all placeholder-[#5a7a80]" placeholder="Ketik pesan...">
                    <button type="submit" class="bg-[#d6fb00] hover:bg-[#ecffb6] text-[#0a1a1f] w-10 h-10 rounded-xl flex items-center justify-center transition-all shrink-0"><i class="fa-solid fa-paper-plane text-xs"></i></button>
                </form>
            </div>
        </div>
    </div>

    <!-- FAB Button -->
    <button @click="chatOpen ? chatOpen = false : helpOpen = !helpOpen" class="help-fab animate-glow">
        <i class="fa-solid transition-transform duration-300" :class="helpOpen || chatOpen ? 'fa-xmark' : 'fa-headset'"></i>
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

    function reinitContent(wrapper) {
        // Halaman ini sudah lama "DOMContentLoaded" sejak load pertama —
        // script per-halaman yang menunggu event itu (pola umum di proyek
        // ini) tidak akan pernah jalan lagi kalau tidak di-shim begini.
        var originalAdd = document.addEventListener.bind(document);
        document.addEventListener = function (type, listener, options) {
            if (type === 'DOMContentLoaded') { listener(); return; }
            return originalAdd(type, listener, options);
        };

        return reExecuteScripts(wrapper).finally(function () {
            document.addEventListener = originalAdd;
            if (window.Alpine) Alpine.initTree(wrapper);
            if (window.AOS) AOS.refreshHard();
        });
    }

    var loadToken = 0;
    function loadTab(url, key, push) {
        var wrapper = document.getElementById('page-content-wrapper');
        if (!wrapper) { window.location.href = url; return; }

        var myToken = ++loadToken;
        wrapper.style.opacity = '0.35';
        wrapper.style.pointerEvents = 'none';

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (res) {
                if (myToken !== loadToken) return null; // klik tab lain sudah menyusul, batalkan
                if (!res.ok) { window.location.href = url; return null; }
                return res.text();
            })
            .then(function (html) {
                if (html === null || html === undefined) return;
                wrapper.innerHTML = html;
                return reinitContent(wrapper).then(function () {
                    setActiveTabKey(key);
                    if (push) history.pushState({ tabUrl: url, tabKey: key }, '', url);
                    var panel = wrapper.closest('.portal-panel');\r
                    if (panel) panel.scrollTop = 0;
                });
            })
            .catch(function () {
                window.location.href = url; // AJAX gagal total → jatuhkan ke navigasi normal
            })
            .finally(function () {
                if (myToken === loadToken) {
                    wrapper.style.opacity = '1';
                    wrapper.style.pointerEvents = '';
                }
            });
    }

    document.addEventListener('click', function (e) {
        var link = e.target.closest('[data-tab-link]');
        if (!link) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return; // biarkan buka-tab-baru dll jalan normal
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
