<!-- application/views/pages/program/diagnosa.php -->
<div class="py-4 sm:py-6 px-1 sm:px-2 relative font-outfit" x-data="wizardData()">
    <!-- Header -->
    <div class="text-center mb-10">
        <h1 class="text-3xl font-bold text-[color:var(--portal-text)] mb-3">Klinik Diagnosa Kelayakan</h1>
        <p class="text-[color:var(--portal-text-muted)]">Program: <span class="text-[color:var(--brand)] font-semibold"><?= htmlspecialchars($program['nama_program']) ?></span></p>
    </div>

        <?php if ($program['kode_program'] === 'umum'): ?>
        <!-- Intro: Program Strategis (konteks sebelum wizard) -->
        <div class="mb-10">
            <p class="text-center text-xs font-bold text-[#8aacb0] uppercase tracking-widest mb-4">Program Strategis Perumahan Jawa Tengah</p>
            <div class="flex flex-wrap items-center justify-center gap-2">
                <span class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-white/5 border border-white/10 text-xs text-white/80"><i class="fa-solid fa-building-columns text-[#00a3b5]"></i> KPR-FLPP</span>
                <span class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-white/5 border border-white/10 text-xs text-white/80"><i class="fa-solid fa-hammer text-amber-400"></i> Peningkatan RTLH</span>
                <span class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-white/5 border border-white/10 text-xs text-white/80"><i class="fa-solid fa-trowel-bricks text-[#d6fb00]"></i> Stimulan PB</span>
                <span class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-white/5 border border-white/10 text-xs text-white/80"><i class="fa-solid fa-leaf text-[#6bcb77]"></i> Oemah Lestari</span>
                <span class="inline-flex items-center gap-2 px-3 py-2 rounded-full bg-white/5 border border-white/10 text-xs text-white/80"><i class="fa-solid fa-water text-[#60a5fa]"></i> Rumah Apung</span>
            </div>
            <p class="text-center text-zinc-500 text-xs mt-4">Isi data di bawah untuk mengetahui program mana yang sesuai untuk Anda.</p>
        </div>
        <?php endif; ?>

        <!-- Progress Bar -->
        <div class="mb-12">
            <div class="flex items-center justify-between relative">
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-1 bg-white/10 rounded-full z-0"></div>
                <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-emerald-500 rounded-full z-0 transition-all duration-500" :style="'width: ' + ((step - 1) / 3 * 100) + '%'"></div>
                
                <!-- Steps Indicators (4 Steps now) -->
                <template x-for="i in 4" :key="i">
                    <div class="relative z-10 flex flex-col items-center w-10 h-10">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold border-2 transition-colors duration-300 shrink-0"
                             :class="step >= i ? 'bg-emerald-500 border-emerald-500 text-white' : 'bg-[#0f2933] border-white/20 text-zinc-500'">
                            <span x-text="i"></span>
                        </div>
                        <span class="absolute top-12 text-xs font-medium whitespace-nowrap" :class="step >= i ? 'text-emerald-400' : 'text-zinc-500'" 
                              x-text="i === 1 ? 'Identitas' : (i === 2 ? 'Data Survei' : (i === 3 ? 'Pilih Program' : 'Konfirmasi'))"></span>
                    </div>
                </template>
            </div>
        </div>

        <!-- Form Container -->
        <div class="bg-[#0f2933] border border-white/10 rounded-2xl p-5 sm:p-7 shadow-2xl relative overflow-hidden">
            
            <form id="diagnosaForm" action="<?= base_url('Program/submit_antrean') ?>" method="POST">
                <!-- Program ID diisi otomatis saat user memilih di Etalase -->
                <input type="hidden" name="program_id" :value="chosenProgram ? chosenProgram.id : ''">
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
                                        <span class="text-zinc-400">(Desil 4: Omah Sekeng & Bansos PB)</span>
                                    </li>
                                    <li>
                                        <button type="button" @click="nik = '3329000000000002'; tgl_lahir = '1990-12-31'; simperumData = null; errorMsg = '';" class="hover:text-white underline decoration-blue-500/50 text-left">NIK: 3329000000000002<br>Tgl Lahir: 31-12-1990</button><br>
                                        <span class="text-zinc-400">(Data Survei Kosong - Tes Smart Filter)</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- SIMPERUM Fetch Result -->
                        <div x-show="simperumData" class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-4 mt-4" style="display: none;">
                            <div class="flex items-start gap-3">
                                <i class="fa-solid fa-circle-check text-emerald-400 mt-0.5"></i>
                                <div>
                                    <h3 class="font-medium text-emerald-400 text-sm">Data Ditemukan (Desil <span x-text="simperumData?.desil"></span>)</h3>
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
                            Lanjut ke Data Survei <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 2: Kalkulator Kelayakan -->
                <div x-show="step === 2" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-8" x-transition:enter-end="opacity-100 translate-x-0">
                    <h2 class="text-lg font-semibold mb-4 flex items-center gap-2"><i class="fa-solid fa-clipboard-list text-blue-400"></i> Data Survei Tambahan</h2>
                    
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

                        <div>
                            <label class="block text-xs font-semibold text-zinc-400 mb-1.5 uppercase tracking-wide">Alasan Pengajuan Bantuan</label>
                            <div class="relative group">
                                <div class="absolute top-3 left-0 flex items-center pl-4 pointer-events-none text-zinc-500 group-focus-within:text-blue-400 transition-colors">
                                    <i class="fa-solid fa-comment-dots text-sm"></i>
                                </div>
                                <textarea name="alasan_pengajuan" x-model="survey.alasan_pengajuan" required rows="3" class="w-full bg-black/20 border border-white/10 rounded-xl pl-12 pr-4 py-2.5 text-white placeholder-zinc-600 focus:outline-none focus:border-blue-500 focus:bg-black/40 transition-colors text-sm resize-none" placeholder="Ceritakan secara singkat alasan Anda membutuhkan bantuan ini..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-between">
                        <button type="button" @click="step = 1" class="px-5 py-2.5 text-sm rounded-xl font-semibold bg-white/5 text-white hover:bg-white/10 transition-colors">Kembali</button>
                        <button type="button" @click="validateSurvey()" class="px-5 py-2.5 text-sm rounded-xl font-semibold bg-blue-500 text-white hover:bg-blue-600 transition-colors flex items-center gap-2" :disabled="!isSurveyComplete() || isLoading" :class="{'opacity-50 cursor-not-allowed': !isSurveyComplete() || isLoading}">
                            <span x-text="isLoading ? 'Menghitung...' : 'Temukan Program'"></span>
                            <i class="fa-solid fa-spinner fa-spin" x-show="isLoading"></i>
                            <i class="fa-solid fa-microchip" x-show="!isLoading"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 3: Etalase Pilihan Program -->
                <div x-show="step === 3" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-8" x-transition:enter-end="opacity-100 translate-x-0">
                    <h2 class="text-lg font-semibold mb-2 flex items-center gap-2"><i class="fa-solid fa-store text-emerald-400"></i> Etalase Program Anda</h2>
                    
                    <!-- Alert jika Ditolak dari Program Target -->
                    <div x-show="!isEligibleForTarget" class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 mb-4" style="display: none;">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-xmark text-red-400 mt-0.5"></i>
                            <div>
                                <h3 class="font-medium text-red-400 text-sm">Mohon Maaf, Anda Tidak Memenuhi Syarat</h3>
                                <p class="text-xs text-zinc-300 mt-1">Berdasarkan hasil analisa (Desil <span x-text="simperumData?.desil"></span>), Anda tidak memenuhi kriteria untuk program <strong class="text-white"><?= htmlspecialchars($program['nama_program']) ?></strong>. Namun, sistem kami merekomendasikan program alternatif berikut:</p>
                            </div>
                        </div>
                    </div>

                    <!-- Alert jika Lolos Program Target -->
                    <div x-show="isEligibleForTarget && '<?= $program['kode_program'] ?>' !== 'umum'" class="bg-emerald-500/10 border border-emerald-500/30 rounded-xl p-4 mb-4" style="display: none;">
                        <div class="flex items-start gap-3">
                            <i class="fa-solid fa-circle-check text-emerald-400 mt-0.5"></i>
                            <div>
                                <h3 class="font-medium text-emerald-400 text-sm">Selamat, Anda Memenuhi Kriteria!</h3>
                                <p class="text-xs text-zinc-300 mt-1">Sistem merekomendasikan Anda untuk melanjutkan pendaftaran program <strong class="text-white"><?= htmlspecialchars($program['nama_program']) ?></strong>.</p>
                            </div>
                        </div>
                    </div>

                    <p class="text-xs text-zinc-400 mb-6" x-show="'<?= $program['kode_program'] ?>' === 'umum'">Berdasarkan hasil analisa (Desil <span x-text="simperumData?.desil"></span>), Anda berhak mengikuti salah satu program prioritas berikut. Silakan pilih satu yang paling sesuai dengan kondisi Anda.</p>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                        <template x-for="prog in eligiblePrograms" :key="prog.id">
                            <div class="relative group cursor-pointer transition-all duration-300 hover:-translate-y-1" @click="selectProgram(prog)">
                                <div class="absolute inset-0 bg-transparent rounded-2xl shadow-[0_5px_15px_0_rgba(0,0,0,0.3)] group-hover:shadow-[0_10px_25px_0_rgba(0,0,0,0.5)] transition-shadow duration-300 pointer-events-none z-0"></div>
                                <div class="relative bg-black/20 border-2 rounded-2xl p-4 flex flex-col h-full z-10 transition-colors"
                                     :class="chosenProgram?.id === prog.id ? 'border-emerald-500 bg-emerald-500/10' : 'border-white/10 group-hover:border-white/30'">
                                    
                                    <!-- Selected Checkmark -->
                                    <div x-show="chosenProgram?.id === prog.id" class="absolute top-3 right-3 w-6 h-6 bg-emerald-500 rounded-full flex items-center justify-center text-white text-xs shadow-lg">
                                        <i class="fa-solid fa-check"></i>
                                    </div>

                                    <div class="flex items-center gap-3 mb-3">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center text-xl shrink-0" :style="'background-color: ' + prog.color + '20; color: ' + prog.color">
                                            <i class="fa-solid" :class="prog.icon"></i>
                                        </div>
                                        <div>
                                            <div class="text-[10px] font-bold tracking-widest uppercase" :style="'color: ' + prog.color" x-text="prog.badge"></div>
                                            <h4 class="text-white font-bold text-sm leading-tight mt-0.5" x-text="prog.title"></h4>
                                        </div>
                                    </div>
                                    
                                    <p class="text-zinc-400 text-xs leading-relaxed flex-grow" x-text="prog.desc"></p>
                                    
                                    <div class="mt-4 pt-3 border-t border-white/10 w-full text-center">
                                        <span class="text-xs font-semibold transition-colors" :class="chosenProgram?.id === prog.id ? 'text-emerald-400' : 'text-blue-400'">
                                            <span x-text="chosenProgram?.id === prog.id ? 'Terpilih' : 'Pilih Program Ini'"></span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="flex justify-between">
                        <button type="button" @click="step = 2" class="px-5 py-2.5 text-sm rounded-xl font-semibold bg-white/5 text-white hover:bg-white/10 transition-colors">Kembali</button>
                        <button type="button" @click="step = 4" class="px-5 py-2.5 text-sm rounded-xl font-semibold bg-blue-500 text-white hover:bg-blue-600 transition-colors flex items-center gap-2" :disabled="!chosenProgram" :class="{'opacity-50 cursor-not-allowed': !chosenProgram}">
                            Lanjut Konfirmasi <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </div>
                </div>

                <!-- Step 4: Konfirmasi & Submit -->
                <div x-show="step === 4" style="display: none;" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-8" x-transition:enter-end="opacity-100 translate-x-0">
                    <div class="text-center mb-6">
                        <div class="w-16 h-16 bg-emerald-500/20 rounded-full flex items-center justify-center mx-auto mb-3 border border-emerald-500/30">
                            <i class="fa-solid fa-clipboard-check text-2xl text-emerald-400"></i>
                        </div>
                        <h2 class="text-xl font-bold text-white mb-2">Konfirmasi Pengajuan</h2>
                        <p class="text-xs text-zinc-400 leading-relaxed max-w-md mx-auto mb-6">Mohon periksa kembali pilihan Anda sebelum mengirimkan pengajuan ke dalam sistem Antrean Dinas Perumahan.</p>
                        
                        <!-- Ticket Card Component -->
                        <div class="relative w-full max-w-sm mx-auto mb-2 text-left" 
                             x-show="chosenProgram">
                            
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
                                        <i class="fa-solid text-3xl group-hover:scale-110 group-hover:-rotate-3 transition-transform" :class="chosenProgram?.icon" :style="'color: ' + chosenProgram?.color + '; filter: drop-shadow(0 0 10px ' + chosenProgram?.color + '80)'"></i>
                                    </div>
                                    <div class="relative z-20 p-4 flex-1 flex flex-col justify-center text-left">
                                        <div class="text-[9px] font-bold tracking-widest uppercase mb-1" :style="'color: ' + chosenProgram?.color" x-text="chosenProgram?.badge"></div>
                                        <h4 class="text-white font-bold text-base mb-1.5 leading-tight" x-text="chosenProgram?.title"></h4>
                                        <p class="text-white/80 text-[11px] leading-relaxed line-clamp-2" x-text="chosenProgram?.desc"></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4 text-center text-[10px] font-bold tracking-widest text-emerald-400 uppercase"><i class="fa-solid fa-ticket-simple mr-1"></i> Tiket Pengajuan Program</div>
                        </div>
                    </div>

                    <div class="bg-black/30 rounded-xl p-4 border border-white/5 mb-5 text-sm">
                        <h4 class="font-semibold text-zinc-300 border-b border-white/10 pb-2 mb-3 text-xs">Ringkasan Pemohon</h4>
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between"><span class="text-zinc-500">Nama Lengkap</span><span class="font-medium text-white" x-text="simperumData?.nama_lengkap"></span></div>
                            <div class="flex justify-between"><span class="text-zinc-500">NIK</span><span class="font-medium text-white" x-text="nik"></span></div>
                            <div class="flex justify-between"><span class="text-zinc-500">Kategori Penghasilan</span><span class="font-medium text-white" x-text="'Desil ' + simperumData?.desil"></span></div>
                        </div>
                    </div>

                    <div class="bg-[#0a1a1f] border border-blue-500/30 rounded-xl p-3 flex gap-3 items-start mb-6">
                        <i class="fa-solid fa-info-circle text-blue-400 mt-0.5 text-xs"></i>
                        <p class="text-[11px] text-zinc-300 leading-relaxed">
                            Dengan menekan tombol Ajukan, Anda menyatakan bahwa data di atas adalah benar dan menyetujui program yang dipilih. Pengajuan ini akan diteruskan ke <strong class="text-white">Dinas Perumahan Rakyat (Disperakim) Jawa Tengah</strong>.
                        </p>
                    </div>

                    <div class="flex justify-between">
                        <button type="button" @click="step = 3" class="px-5 py-2.5 text-sm rounded-xl font-semibold bg-white/5 text-white hover:bg-white/10 transition-colors">Ganti Program</button>
                        <button type="submit" class="px-6 py-2.5 text-sm rounded-xl font-semibold bg-gradient-to-r from-emerald-500 to-blue-500 text-white hover:from-emerald-400 hover:to-blue-400 transition-all shadow-lg shadow-emerald-500/20 transform hover:-translate-y-0.5">
                            Kirim Pengajuan <i class="fa-solid fa-paper-plane ml-2"></i>
                        </button>
                    </div>
                </div>

            </form>
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
        
        eligiblePrograms: [],
        chosenProgram: null,
        isEligibleForTarget: true,

        survey: {
            penghasilan: '',
            pekerjaan: '',
            status_kepemilikan: '',
            alasan_pengajuan: ''
        },
        
        init() {
            // Auto-fetch dinonaktifkan
        },
        
        async fetchSimperum() {
            if(this.nik.length !== 16 || !this.tgl_lahir) return;
            
            this.isLoading = true;
            this.errorMsg = '';
            
            try {
                const formData = new FormData();
                formData.append('nik', this.nik);
                formData.append('tgl_lahir', this.tgl_lahir);
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
                    this.eligiblePrograms = result.eligible_programs || [];
                    this.chosenProgram = null; // Reset choice if re-fetched
                    
                    if(result.data.penghasilan) this.survey.penghasilan = result.data.penghasilan;
                    if(result.data.pekerjaan) this.survey.pekerjaan = result.data.pekerjaan;
                    if(result.data.status_kepemilikan) this.survey.status_kepemilikan = result.data.status_kepemilikan;

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
                   this.survey.status_kepemilikan !== '' &&
                   this.survey.alasan_pengajuan.trim() !== '';
        },

        async validateSurvey() {
            if(this.isSurveyComplete()) {
                
                // Set loading status so user knows it's calculating
                this.isLoading = true;
                
                try {
                    const formData = new FormData();
                    formData.append('penghasilan', this.survey.penghasilan);
                    formData.append('pekerjaan', this.survey.pekerjaan);
                    formData.append('status_kepemilikan', this.survey.status_kepemilikan);
                    formData.append('alasan_pengajuan', this.survey.alasan_pengajuan);
                    formData.append('kode_program_target', '<?= $program['kode_program'] ?>');
                    formData.append('<?= $this->security->get_csrf_token_name(); ?>', '<?= $this->security->get_csrf_hash(); ?>');
                    
                    const response = await fetch('<?= base_url('Program/api_kalkulasi_program') ?>', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    const result = await response.json();
                    
                    if(result.status === 'success') {
                        // Update the decile and available programs
                        if(this.simperumData) {
                            this.simperumData.desil = result.desil;
                        }
                        this.eligiblePrograms = result.eligible_programs || [];
                        this.isEligibleForTarget = result.is_eligible_for_target;
                        
                        this.chosenProgram = null; 
                        
                        // Jika eligible untuk target spesifik, otomatis pilihkan
                        if (this.isEligibleForTarget && result.kode_program_target !== 'umum') {
                            const found = this.eligiblePrograms.find(p => p.kode === result.kode_program_target);
                            if (found) {
                                this.chosenProgram = found;
                            }
                        }
                        
                        // Go to Step 3
                        this.step = 3;
                    } else {
                        alert("Terjadi kesalahan saat menghitung program.");
                    }
                } catch(e) {
                    alert("Gagal terhubung ke server untuk kalkulasi.");
                } finally {
                    this.isLoading = false;
                }
            }
        },

        selectProgram(prog) {
            this.chosenProgram = prog;
        }
    }
}
</script>
