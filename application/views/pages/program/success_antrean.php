<!-- application/views/pages/program/success_antrean.php -->
<div class="flex flex-col justify-center items-center w-full min-h-[calc(100vh-80px)] py-12 px-4 relative">
    
    <!-- Ambient Glow behind the card -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-emerald-500/20 rounded-full blur-[100px] pointer-events-none z-0"></div>

    <div class="bg-[#0f2933] border border-emerald-500/30 rounded-3xl p-8 sm:p-12 shadow-2xl relative z-10 max-w-xl w-full text-center transform hover:-translate-y-1 transition-transform duration-500">
        
        <!-- Animated Check Icon -->
        <div class="w-24 h-24 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-full flex items-center justify-center mx-auto mb-6 shadow-[0_0_40px_rgba(52,211,153,0.3)] animate-bounce">
            <i class="fa-solid fa-check text-4xl text-white"></i>
        </div>

        <h1 class="text-3xl font-bold text-white mb-3">Pengajuan Terkirim!</h1>
        <p class="text-zinc-300 text-sm leading-relaxed mb-8">
            Data Anda telah berhasil direkam ke dalam sistem <span class="font-semibold text-emerald-400">Antrean Klinik PKP</span>. Tim kami akan segera melakukan verifikasi data lebih lanjut.
        </p>

        <div class="bg-black/30 border border-white/5 rounded-2xl p-5 mb-8 text-left">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-bell text-blue-400 mt-1"></i>
                <div>
                    <h4 class="text-white font-medium text-sm mb-1">Langkah Selanjutnya</h4>
                    <p class="text-xs text-zinc-400 leading-relaxed">
                        Pantau status pengajuan Anda melalui menu profil (jika sudah memiliki akun) atau tunggu pesan notifikasi WhatsApp dari petugas kami dalam 3x24 jam kerja.
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="<?= base_url('Index') ?>" class="px-6 py-3 rounded-xl font-semibold bg-white/5 text-white hover:bg-white/10 transition-colors">
                Kembali ke Beranda
            </a>
            <button onclick="fireConfetti()" class="px-6 py-3 rounded-xl font-semibold bg-emerald-500 text-white hover:bg-emerald-600 shadow-lg shadow-emerald-500/20 transition-colors">
                Rayakan Sekali Lagi! <i class="fa-solid fa-wand-magic-sparkles ml-2"></i>
            </button>
        </div>
    </div>
</div>

<!-- Canvas Confetti CDN -->
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
<script>
    function fireConfetti() {
        var duration = 3 * 1000;
        var animationEnd = Date.now() + duration;
        var defaults = { startVelocity: 30, spread: 360, ticks: 60, zIndex: 100 };

        function randomInRange(min, max) {
            return Math.random() * (max - min) + min;
        }

        var interval = setInterval(function() {
            var timeLeft = animationEnd - Date.now();

            if (timeLeft <= 0) {
                return clearInterval(interval);
            }

            var particleCount = 50 * (timeLeft / duration);
            // since particles fall down, start a bit higher than random
            confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.1, 0.3), y: Math.random() - 0.2 } }));
            confetti(Object.assign({}, defaults, { particleCount, origin: { x: randomInRange(0.7, 0.9), y: Math.random() - 0.2 } }));
        }, 250);
    }

    // Auto trigger on load
    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(fireConfetti, 300);
    });
</script>
