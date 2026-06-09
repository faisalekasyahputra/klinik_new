 <!-- ============================================================
     FOOTER
     ============================================================ -->
 <footer class="w-full bg-[#0a1a1f] text-zinc-400 pt-16 pb-6 mt-auto relative z-10 overflow-hidden">
    
    <!-- Batik Kawung Background Pattern with Top Fade Mask -->
    <div class="absolute inset-0 z-0 pointer-events-none" style="opacity: 0.05; -webkit-mask-image: linear-gradient(to bottom, transparent 0%, black 60%, black 100%); mask-image: linear-gradient(to bottom, transparent 0%, black 60%, black 100%);">
        <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
          <defs>
            <pattern id="batik-kawung-pkp" x="0" y="0" width="100" height="100" patternUnits="userSpaceOnUse">
              <circle cx="0" cy="0" r="50" fill="none" stroke="#00545f" stroke-width="2"/>
              <circle cx="100" cy="0" r="50" fill="none" stroke="#00545f" stroke-width="2"/>
              <circle cx="0" cy="100" r="50" fill="none" stroke="#00545f" stroke-width="2"/>
              <circle cx="100" cy="100" r="50" fill="none" stroke="#00545f" stroke-width="2"/>
              <line x1="-15" y1="0" x2="15" y2="0" stroke="#00545f" stroke-width="2"/>
              <line x1="0" y1="-15" x2="0" y2="15" stroke="#00545f" stroke-width="2"/>
              <circle cx="0" cy="0" r="4.5" fill="#d6fb00"/>
              <line x1="85" y1="0" x2="115" y2="0" stroke="#00545f" stroke-width="2"/>
              <line x1="100" y1="-15" x2="100" y2="15" stroke="#00545f" stroke-width="2"/>
              <circle cx="100" cy="0" r="4.5" fill="#d6fb00"/>
              <line x1="-15" y1="100" x2="15" y2="100" stroke="#00545f" stroke-width="2"/>
              <line x1="0" y1="85" x2="0" y2="115" stroke="#00545f" stroke-width="2"/>
              <circle cx="0" cy="100" r="4.5" fill="#d6fb00"/>
              <line x1="85" y1="100" x2="115" y2="100" stroke="#00545f" stroke-width="2"/>
              <line x1="100" y1="85" x2="100" y2="115" stroke="#00545f" stroke-width="2"/>
              <circle cx="100" cy="100" r="4.5" fill="#d6fb00"/>
              <polygon points="50,40 60,50 50,60 40,50" fill="none" stroke="#00a3b5" stroke-width="2"/>
              <circle cx="50" cy="50" r="2.5" fill="#ecffb6"/>
              <circle cx="50" cy="22" r="2" fill="#00a3b5"/>
              <circle cx="50" cy="78" r="2" fill="#00a3b5"/>
              <circle cx="22" cy="50" r="2" fill="#00a3b5"/>
              <circle cx="78" cy="50" r="2" fill="#00a3b5"/>
            </pattern>
          </defs>
          <rect width="100%" height="100%" fill="url(#batik-kawung-pkp)" />
        </svg>
    </div>

    <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-12 pb-12">
            
            <!-- Brand -->
            <div class="md:col-span-5 space-y-5">
                <div class="flex items-center gap-3">
                    <img src="<?= base_url('assets/img/logo-jateng.png') ?>" alt="Logo Jawa Tengah" class="h-10 w-auto object-contain">
                    <div>
                        <h5 class="text-sm font-black tracking-tight text-white leading-none">Klinik<span class="text-[#d6fb00]">PKP</span></h5>
                        <p class="text-[9px] text-[#8aacb0] font-bold tracking-widest uppercase mt-1">Disperakim Prov. Jateng</p>
                    </div>
                </div>
                <p class="text-xs text-zinc-500 leading-relaxed max-w-sm">
                    Klinik Perumahan dan Kawasan Permukiman hadir sebagai pusat layanan informasi dan konsultasi terpadu di wilayah Jawa Tengah.
                </p>

            </div>

            <!-- Quick Links -->
            <div class="md:col-span-3">
                <h5 class="text-white font-bold text-xs tracking-wider uppercase mb-5">Layanan</h5>
                <ul class="space-y-2.5 text-xs text-zinc-500">
                    <li><a href="#" class="hover:text-[#d6fb00] transition-colors flex items-center gap-2"><i class="fa-solid fa-chevron-right text-[7px] text-zinc-700"></i> Konsultasi</a></li>
                    <li><a href="#" class="hover:text-[#d6fb00] transition-colors flex items-center gap-2"><i class="fa-solid fa-chevron-right text-[7px] text-zinc-700"></i> Masukan & Saran</a></li>
                    <li><a href="#" class="hover:text-[#d6fb00] transition-colors flex items-center gap-2"><i class="fa-solid fa-chevron-right text-[7px] text-zinc-700"></i> FAQ</a></li>
                    <li><a href="#" class="hover:text-[#d6fb00] transition-colors flex items-center gap-2"><i class="fa-solid fa-chevron-right text-[7px] text-zinc-700"></i> Pejabat Struktural</a></li>
                    <li><a href="#" class="hover:text-[#d6fb00] transition-colors flex items-center gap-2"><i class="fa-solid fa-chevron-right text-[7px] text-zinc-700"></i> Tim Magang</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div class="md:col-span-4 space-y-4">
                <h5 class="text-white font-bold text-xs tracking-wider uppercase mb-5">Kontak</h5>
                <div class="space-y-3 text-xs text-zinc-500">
                    <div class="flex items-start gap-3">
                        <div class="w-7 h-7 rounded-lg bg-white/5 flex items-center justify-center text-zinc-600 mt-0.5 shrink-0"><i class="fa-solid fa-location-dot text-[10px]"></i></div>
                        <p>KLINIK PKP DISPERAKIM<br><span class="text-zinc-600">Jl. Madukoro Blok BB, Semarang</span></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-7 h-7 rounded-lg bg-white/5 flex items-center justify-center text-zinc-600 shrink-0"><i class="fa-solid fa-phone text-[10px]"></i></div>
                        <p>+6282137191145</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-7 h-7 rounded-lg bg-white/5 flex items-center justify-center text-zinc-600 shrink-0"><i class="fa-solid fa-envelope text-[10px]"></i></div>
                        <p class="lowercase">klinikpkpjawa3@gmail.com</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 pt-2">
                    <a href="#" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-[#d6fb00] hover:text-[#0a1a1f] flex items-center justify-center text-zinc-500 transition-all text-xs"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-[#d6fb00] hover:text-[#0a1a1f] flex items-center justify-center text-zinc-500 transition-all text-xs"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-[#d6fb00] hover:text-[#0a1a1f] flex items-center justify-center text-zinc-500 transition-all text-xs"><i class="fa-brands fa-youtube"></i></a>
                </div>
            </div>
        </div>

        <!-- Copyright -->
        <div class="pt-6 flex flex-col sm:flex-row justify-between items-center gap-4 text-[11px] text-zinc-600 font-medium">
            <div>&copy; 2026 <span class="text-zinc-500 font-semibold">KLINIK PKP JATENG</span>. All Rights Reserved.</div>
            <div class="flex items-center gap-3 text-zinc-500/80">
                <a href="#" class="hover:text-[#d6fb00] transition-colors">Support</a>
                <span>•</span>
                <a href="#" class="hover:text-[#d6fb00] transition-colors">Ketentuan</a>
                <span>•</span>
                <a href="#" class="hover:text-[#d6fb00] transition-colors">Privasi</a>
            </div>
        </div>
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
</script>
