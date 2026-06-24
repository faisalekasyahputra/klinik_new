<!-- application/views/pages/program/diagnosa.php -->
<div class="flex flex-col justify-start w-full min-h-[calc(100vh-80px)] pt-16 pb-12 text-white relative" x-data="wizardData()">

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        
        <!-- Header -->
        <div class="text-center mb-10">
            <h1 class="text-3xl font-bold text-white mb-3">Klinik Diagnosa Kelayakan</h1>
            <p class="text-zinc-400">Program: <span class="text-emerald-400 font-semibold"><?= htmlspecialchars($program['nama_program']) ?></span></p>
        </div>

        <!-- Progress Bar -->
        <div class="mb-12">
            <div class="flex items-center justify-between relative">
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-white/10 rounded-full z-0"></div>
                <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-emerald-500 rounded-full z-0 transition-all duration-500" :style="'width: ' + ((step - 1) / 2 * 100) + '%'"></div>
                
                <!-- Steps Indicators -->
                <template x-for="i in 3" :key="i">
                    <div class="relative z-10 flex flex-col items-center w-10 h-10">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold border-2 transition-colors duration-300 shrink-0"
                             :class="step >= i ? 'bg-emerald-500 border-emerald-500 text-white' : 'bg-[#0f2933] border-white/20 text-zinc-500'">
                            <span x-text="i"></span>
                        </div>
                        <span class="absolute top-12 text-xs font-medium whitespace-nowrap" :class="step >= i ? 'text-emerald-400' : 'text-zinc-500'" 
                              x-text="i === 1 ? 'Data Diri' : (i === 2 ? 'Kalkulator' : 'Konfirmasi')"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Form Container -->
        <div class="bg-[#0f2933] border border-white/10 rounded-2xl p-5 sm:p-7 shadow-2xl relative overflow-hidden">
            
            <form id="diagnosaForm" action="<?= base_url('Program/submit_antrean') ?>" method="POST">
                <input type="hidden" name="program_id" value="<?= $program['id'] ?>">
                <!-- CSRF Token -->
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                
                <!-- Step 1: NIK & SIMPERUM Fetch -->
                <div x-show="step === 1" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-8" x-transition:enter-end="opacity-100 translate-x-0">
                    <h2 class="text-lg font-semibold mb-4 flex items-center gap-2"><i class="fa-solid fa-id-card text-emerald-400"></i> Verifikasi Identitas</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-zinc-300 mb-1.5">Nomor Induk Kependudukan (NIK)</label>
                            <input type="text" x-model="nik" @input="simperumData = null; errorMsg = '';" name="nik" maxlength="16" required class="w-full bg-[#0a1a1f] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-zinc-600 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors" placeholder="Masukkan 16 digit NIK">
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-zinc-300 mb-1.5">Tanggal Lahir</label>
                            <input type="date" x-model="tgl_lahir" @input="simperumData = null; errorMsg = '';" name="tgl_lahir" required class="w-full bg-[#0a1a1f] border border-white/10 rounded-xl px-4 py-2.5 text-sm text-white placeholder-zinc-600 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition-colors" style="color-scheme: dark;">
                            <p class="text-[11px] text-zinc-500 mt-1.5">Data NIK dan Tanggal Lahir Anda akan divalidasi secara aman dengan database Dinas Perumahan.</p>
                        </div>

                        <div>
                            <div class="bg-blue-500/10 border border-blue-500/20 rounded-lg p-2.5">
                                <p class="text-[11px] text-blue-400 mb-1"><i class="fa-solid fa-flask"></i> <strong>Skenario Pengujian (Klik untuk salin):</strong></p>
                                <ul class="text-xs text-blue-300 space-y-2 ml-4 list-disc">
                                    <li>
                                        <button type="button" @click="nik = '3329000000000001'; tgl_lahir = '1980-01-01'; simperumData = null; errorMsg = '';" class="hover:text-white underline decoration-blue-500/50 text-left">NIK: 3329000000000001<br>Tgl Lahir: 01-01-1980</button><br>
                                        <span class="text-zinc-400">(Punya data diri & data survei)</span>
                                    </li>
                                    <li>
                                        <button type="button" @click="nik = '3329000000000002'; tgl_lahir = '1990-12-31'; simperumData = null; errorMsg = '';" class="hover:text-white underline decoration-blue-500/50 text-left">NIK: 3329000000000002<br>Tgl Lahir: 31-12-1990</button><br>
                                        <span class="text-zinc-400">(Hanya punya data diri dasar)</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- SIMPERUM Fetch Result -->
                        <div x-show="simperumData" class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-4 mt-4" style="display: none;">
                            <div class="flex items-start gap-3">
                                <i class="fa-solid fa-circle-check text-emerald-400 mt-0.5"></i>
                                <div>
                                    <h3 class="font-medium text-emerald-400 text-sm">Data Ditemukan</h3>
                                    <p class="text-sm text-zinc-300 mt-1">Nama: <span class="font-semibold text-white" x-text="simperumData?.nama_lengkap"></span></p>
                                    <p class="text-sm text-zinc-300">Alamat: <span x-text="simperumData?.alamat"></span></p>
                                </div>
                            </div>
                            <!-- Hidden inputs for submission -->
                            <input type="hidden" name="nama_lengkap" :value="simperumData?.nama_lengkap">
                            <input type="hidden" name="data_simperum" :value="JSON.stringify(simperumData)">
                        </div>

                        <!-- Error Message -->
                        <div x-show="errorMsg" class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 mt-4" style="display: none;">
                            <div class="flex items-start gap-3">
                                <i class="fa-solid fa-circle-exclamation text-red-400 mt-0.5"></i>
                                <p class="text-sm text-red-300" x-text="errorMsg"></p>
                            </div>
                        </div>

                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" x-show="!simperumData" @click="fetchSimperum()" class="px-5 py-2.5 text-sm rounded-xl font-semibold bg-emerald-500 text-white hover:bg-emerald-600 transition-colors flex items-center gap-2" :disabled="isLoading || nik.length !== 16 || !tgl_lahir" :class="{'opacity-50 cursor-not-allowed': nik.length !== 16 || !tgl_lahir || isLoading}">
                            <span x-text="isLoading ? 'Memvalidasi...' : 'Cek & Validasi Data'"></span>
                            <i class="fa-solid fa-spinner fa-spin" x-show="isLoading"></i>
                            <i class="fa-solid fa-magnifying-glass" x-show="!isLoading"></i>
                        </button>

                        <button type="button" x-show="simperumData" @click="step = 2" class="px-5 py-2.5 text-sm rounded-xl font-semibold bg-blue-500 text-white hover:bg-blue-600 transition-colors flex items-center gap-2">
                            Lanjut ke Kalkulator <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Kalkulator Kelayakan -->
                <div x-show="step === 2" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-8" x-transition:enter-end="opacity-100 translate-x-0">
                    <h2 class="text-lg font-semibold mb-4 flex items-center gap-2"><i class="fa-solid fa-calculator text-blue-400"></i> Smart Filter & Kalkulator</h2>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-zinc-400 mb-1.5 uppercase tracking-wide">Total Penghasilan Per Bulan (Keluarga)</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-zinc-500 group-focus-within:text-blue-400 transition-colors">
                                    <span class="font-bold text-sm">Rp</span>
                                </div>
                                <input type="number" name="penghasilan" x-model="survey.penghasilan" required class="w-full bg-black/20 border border-white/10 rounded-xl pl-12 pr-4 py-2.5 text-white placeholder-zinc-600 focus:outline-none focus:border-blue-500 focus:bg-black/40 transition-colors text-sm" placeholder="Contoh: 4000000">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-400 mb-1.5 uppercase tracking-wide">Status Pekerjaan</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-zinc-500 group-focus-within:text-blue-400 transition-colors">
                                    <i class="fa-solid fa-briefcase text-sm"></i>
                                </div>
                                <select name="pekerjaan" x-model="survey.pekerjaan" required class="w-full bg-black/20 border border-white/10 rounded-xl pl-10 pr-10 py-2.5 text-white focus:outline-none focus:border-blue-500 focus:bg-black/40 transition-colors appearance-none text-sm">
                                    <option value="" class="bg-[#0f2933]">Pilih Pekerjaan</option>
                                    <option value="PNS/TNI/POLRI" class="bg-[#0f2933]">PNS/TNI/POLRI</option>
                                    <option value="Karyawan Swasta" class="bg-[#0f2933]">Karyawan Swasta</option>
                                    <option value="Wiraswasta" class="bg-[#0f2933]">Wiraswasta / Pengusaha</option>
                                    <option value="Pekerja Informal" class="bg-[#0f2933]">Pekerja Informal / Freelance</option>
                                    <option value="Lainnya" class="bg-[#0f2933]">Lainnya</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-zinc-500">
                                    <i class="fa-solid fa-chevron-down text-xs"></i>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-zinc-400 mb-1.5 uppercase tracking-wide">Status Kepemilikan Lahan/Rumah Saat Ini</label>
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-zinc-500 group-focus-within:text-blue-400 transition-colors">
                                    <i class="fa-solid fa-house-user text-sm"></i>
                                </div>
                                <select name="status_kepemilikan" x-model="survey.status_kepemilikan" required class="w-full bg-black/20 border border-white/10 rounded-xl pl-10 pr-10 py-2.5 text-white focus:outline-none focus:border-blue-500 focus:bg-black/40 transition-colors appearance-none text-sm">
                                    <option value="" class="bg-[#0f2933]">Pilih Status</option>
                                    <option value="Sewa/Kontrak" class="bg-[#0f2933]">Sewa / Kontrak</option>
                                    <option value="Numpang/Keluarga" class="bg-[#0f2933]">Menumpang Bersama Keluarga</option>
                                    <option value="Punya Lahan Belum Bangun" class="bg-[#0f2933]">Memiliki Lahan, Belum Ada Bangunan</option>
                                    <option value="Punya Rumah Tidak Layak" class="bg-[#0f2933]">Memiliki Rumah (Tidak Layak Huni)</option>
                                    <option value="Punya Rumah Layak" class="bg-[#0f2933]">Memiliki Rumah Layak Huni</option>
                                </select>
                                <div class="absolute inset-y-0 right-0 flex items-center pr-4 pointer-events-none text-zinc-500">
                                    <i class="fa-solid fa-chevron-down text-xs"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-between">
                        <button type="button" @click="step = 1" class="px-5 py-2.5 text-sm rounded-xl font-semibold bg-white/5 text-white hover:bg-white/10 transition-colors">Kembali</button>
                        <button type="button" @click="validateSurvey()" class="px-5 py-2.5 text-sm rounded-xl font-semibold bg-blue-500 text-white hover:bg-blue-600 transition-colors flex items-center gap-2" :disabled="!isSurveyComplete()" :class="{'opacity-50 cursor-not-allowed': !isSurveyComplete()}">
                            Analisa Data <i class="fa-solid fa-microchip"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Konfirmasi & Submit -->
                <div x-show="step === 3" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-8" x-transition:enter-end="opacity-100 translate-x-0">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto mb-3 border border-emerald-500/30">
                            <i class="fa-solid fa-clipboard-check text-2xl text-emerald-400"></i>
                        </div>
                        <h2 class="text-xl font-bold text-white mb-2">Diagnosa Selesai!</h2>
                        <p class="text-xs text-zinc-400 leading-relaxed max-w-md mx-auto mb-6">Berdasarkan hasil pemadanan NIK dan kalkulator kelayakan, Anda <strong>memenuhi kriteria utama</strong>. Anda berstatus Masyarakat Berpenghasilan Rendah (MBR) dan kondisi kepemilikan hunian Anda masuk dalam prioritas penanganan Dinas Perumahan.</p>
                        
                        <!-- Ticket Card Component -->
                        <div class="relative w-full max-w-sm mx-auto mb-2 text-left" 
                             x-show="recommendedProgram && programs[recommendedProgram]">
                            
                            <style>
                            .ticket-mask {
                                -webkit-mask-image: radial-gradient(circle at 5rem 0px, transparent 7.5px, black 8.5px), radial-gradient(circle at 5rem 100%, transparent 7.5px, black 8.5px);
                                -webkit-mask-position: top left, bottom left;
                                -webkit-mask-size: 100% 51%;
                                -webkit-mask-repeat: no-repeat;
                                mask-image: radial-gradient(circle at 5rem 0px, transparent 7.5px, black 8.5px), radial-gradient(circle at 5rem 100%, transparent 7.5px, black 8.5px);
                                mask-position: top left, bottom left;
                                mask-size: 100% 51%;
                                mask-repeat: no-repeat;
                            }
                            .ticket-base {
                                background: linear-gradient(135deg, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0.02) 100%);
                                backdrop-filter: blur(16px);
                                -webkit-backdrop-filter: blur(16px);
                                border-radius: 1rem;
                                transform: translateZ(0);
                            }
                            .ticket-wrapper:hover .ticket-base {
                                background: linear-gradient(135deg, rgba(255,255,255,0.12) 0%, rgba(255,255,255,0.05) 100%);
                            }
                            .ticket-border {
                                border: 1px solid rgba(255,255,255,0.2);
                                border-radius: 1rem;
                            }
                            .ticket-wrapper:hover .ticket-border {
                                border-color: rgba(255,255,255,0.4);
                            }
                            .ticket-border::before, .ticket-border::after {
                                content: '';
                                position: absolute;
                                left: calc(5rem - 9px);
                                width: 18px;
                                height: 18px;
                                border-radius: 50%;
                                border: 1px solid rgba(255,255,255,0.2);
                                pointer-events: none;
                                transition: border-color 0.3s ease;
                                box-sizing: border-box;
                            }
                            .ticket-border::before { top: -9px; border-top-color: transparent; border-left-color: transparent; border-right-color: transparent; }
                            .ticket-border::after { bottom: -9px; border-bottom-color: transparent; border-left-color: transparent; border-right-color: transparent; }
                            .ticket-wrapper:hover .ticket-border::before, .ticket-wrapper:hover .ticket-border::after {
                                border-color: rgba(255,255,255,0.4);
                            }
                            </style>

                            <div class="ticket-wrapper relative group transition-all duration-300 hover:-translate-y-0.5">
                                <div class="absolute inset-0 bg-transparent rounded-2xl shadow-[0_10px_30px_0_rgba(0,0,0,0.4)] group-hover:shadow-[0_15px_35px_0_rgba(0,0,0,0.5)] transition-shadow duration-300 pointer-events-none z-0"></div>
                                <div class="ticket-base ticket-mask relative flex min-h-[110px] z-10">
                                    <div class="absolute inset-0 ticket-border pointer-events-none transition-colors duration-300 z-10"></div>
                                    <div class="relative z-20 w-20 shrink-0 flex items-center justify-center border-r border-dashed border-white/40">
                                        <i class="fa-solid text-3xl group-hover:scale-110 group-hover:-rotate-3 transition-transform" :class="programs[recommendedProgram].icon" :style="'color: ' + programs[recommendedProgram].color + '; filter: drop-shadow(0 0 10px ' + programs[recommendedProgram].color + '80)'"></i>
                                    </div>
                                    <div class="relative z-20 p-4 flex-1 flex flex-col justify-center text-left">
                                        <div class="text-[9px] font-bold tracking-widest uppercase mb-1" :style="'color: ' + programs[recommendedProgram].color" x-text="programs[recommendedProgram].badge"></div>
                                        <h4 class="text-white font-bold text-base mb-1.5 leading-tight" x-text="programs[recommendedProgram].title"></h4>
                                        <p class="text-white/80 text-[11px] leading-relaxed line-clamp-2" x-text="programs[recommendedProgram].desc"></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4 text-center text-[10px] font-bold tracking-widest text-emerald-400 uppercase"><i class="fa-solid fa-ticket-simple mr-1"></i> Tiket Rekomendasi Program</div>
                        </div>
                    </div>

                    <div class="bg-black/30 rounded-xl p-4 border border-white/5 mb-5 text-sm">
                        <h4 class="font-semibold text-zinc-300 border-b border-white/10 pb-2 mb-3 text-xs">Ringkasan Hasil Verifikasi</h4>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between"><span class="text-zinc-500">Rekomendasi Program</span><span class="font-bold text-emerald-400 text-right" x-text="programs[recommendedProgram]?.title || '<?= htmlspecialchars($program['nama_program']) ?>'"></span></div>
                            <div class="flex justify-between"><span class="text-zinc-500">Nama Lengkap</span><span class="font-medium text-white" x-text="simperumData?.nama_lengkap"></span></div>
                            <div class="flex justify-between"><span class="text-zinc-500">NIK</span><span class="font-medium text-white" x-text="nik"></span></div>
                            <div class="flex justify-between"><span class="text-zinc-500">Penghasilan</span><span class="font-medium text-white" x-text="'Rp ' + parseInt(survey.penghasilan).toLocaleString('id-ID')"></span></div>
                        </div>
                    </div>

                    <div class="bg-[#0a1a1f] border border-blue-500/30 rounded-xl p-3 flex gap-3 items-start mb-6">
                        <i class="fa-solid fa-info-circle text-blue-400 mt-0.5 text-xs"></i>
                        <p class="text-[11px] text-zinc-300 leading-relaxed">
                            Dengan menekan tombol Konfirmasi, Anda menyatakan bahwa data di atas adalah benar. NIK Anda akan direkam dalam <strong class="text-white">Daftar Antrean (Housing Queue)</strong> Klinik PKP Jawa Tengah untuk divalidasi lebih lanjut oleh petugas dinas.
                        </p>
                    </div>

                    <div class="flex justify-between">
                        <button type="button" @click="step = 2" class="px-5 py-2.5 text-sm rounded-xl font-semibold bg-white/5 text-white hover:bg-white/10 transition-colors">Revisi Data</button>
                        <button type="submit" class="px-6 py-2.5 text-sm rounded-xl font-semibold bg-gradient-to-r from-emerald-500 to-blue-500 text-white hover:from-emerald-400 hover:to-blue-400 transition-all shadow-lg shadow-emerald-500/20 transform hover:-translate-y-0.5">
                            Konfirmasi & Lanjutkan <i class="fa-solid fa-check ml-2"></i>
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
function wizardData() {
    return {
        step: 1,
        nik: '<?= $this->input->get('nik') ? htmlspecialchars(trim($this->input->get('nik'))) : '' ?>',
        tgl_lahir: '',
        isLoading: false,
        errorMsg: '',
        simperumData: null,
        programCode: '<?= $program['kode_program'] ?>',
        recommendedProgram: '',
        programs: {
            'flpp': { title: 'KPR-FLPP', badge: 'MBR Fixed Income', desc: 'Bunga flat 5% & cicilan ringan hingga 20 tahun.', icon: 'fa-building-columns', color: '#00a3b5' },
            'oemah_lestari': { title: 'Oemah Lestari', badge: 'MBR & Umum', desc: 'Fasilitas pembiayaan rumah murah bagi MBR dengan skema kredit bunga ringan.', icon: 'fa-leaf', color: '#6bcb77' },
            'rtlh': { title: 'Peningkatan RTLH', badge: 'Miskin & Ekstrem', desc: 'Bantuan perbaikan rumah via Bankeupemdes Rp 20 Juta.', icon: 'fa-hammer', color: '#c084fc' },
            'pb': { title: 'Stimulan PB', badge: 'Bencana & Relokasi', desc: 'Bantuan material Rp 40 Juta untuk relokasi dan korban bencana.', icon: 'fa-trowel-bricks', color: '#d6fb00' },
            'rumah_apung': { title: 'Program Rumah Apung', badge: 'Kawasan Pesisir', desc: 'Inovasi desain rumah tahan banjir rob untuk pesisir (Timbulsloko).', icon: 'fa-water', color: '#60a5fa' },
            'umum': { title: 'Program Terpadu', badge: 'Semua Kategori', desc: 'Program bantuan perumahan Dinas Perkim Jawa Tengah.', icon: 'fa-star', color: '#ffffff' }
        },
        survey: {
            penghasilan: '',
            pekerjaan: '',
            status_kepemilikan: ''
        },
        
        init() {
            // Karena sekarang membutuhkan validasi NIK + Tanggal Lahir,
            // auto-fetch ditiadakan. Pengguna harus memasukkan Tanggal Lahir secara manual.
        },
        
        async fetchSimperum() {
            if(this.nik.length !== 16 || !this.tgl_lahir) return;
            
            this.isLoading = true;
            this.errorMsg = '';
            
            try {
                const formData = new FormData();
                formData.append('nik', this.nik);
                formData.append('tgl_lahir', this.tgl_lahir);
                // Tambahkan CSRF Token CodeIgniter
                formData.append('<?= $this->security->get_csrf_token_name(); ?>', '<?= $this->security->get_csrf_hash(); ?>');
                
                const response = await fetch('<?= base_url('Program/api_cek_simperum') ?>', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                const result = await response.json();
                
                if(result.status === 'success') {
                    this.simperumData = result.data;
                    
                    // Auto-fill survey data if it exists in SIMPERUM
                    if(result.data.penghasilan) this.survey.penghasilan = result.data.penghasilan;
                    if(result.data.pekerjaan) this.survey.pekerjaan = result.data.pekerjaan;
                    if(result.data.status_kepemilikan) this.survey.status_kepemilikan = result.data.status_kepemilikan;

                    // Diubah: Jangan langsung pindah ke step 2, biarkan user mengecek data diri dulu
                    // this.step = 2;
                } else {
                    this.errorMsg = result.message;
                    this.simperumData = null;
                }
            } catch(e) {
                this.errorMsg = 'Terjadi kesalahan koneksi saat memvalidasi SIMPERUM.';
            } finally {
                this.isLoading = false;
            }
        },

        isSurveyComplete() {
            return this.survey.penghasilan !== '' && 
                   this.survey.pekerjaan !== '' && 
                   this.survey.status_kepemilikan !== '';
        },

        validateSurvey() {
            if(this.isSurveyComplete()) {
                // Tentukan Rekomendasi Program berdasarkan jawaban
                if (this.programCode === 'umum') {
                    const status = this.survey.status_kepemilikan;
                    if (status === 'Sewa/Kontrak' || status === 'Punya Lahan Belum Bangun') {
                        this.recommendedProgram = 'pb';
                    } else if (status === 'Punya Rumah Tidak Layak') {
                        this.recommendedProgram = 'rtlh';
                    } else if (status === 'Numpang/Keluarga') {
                        this.recommendedProgram = 'flpp';
                    } else {
                        this.recommendedProgram = 'oemah_lestari';
                    }
                } else {
                    this.recommendedProgram = this.programCode;
                }
                
                // Pindah ke step 3
                this.step = 3;
            }
        }
    }
}
</script>
