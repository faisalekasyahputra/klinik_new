<section class="w-full max-w-4xl mx-auto bg-[#0b0c10]/75 border border-zinc-800/50 rounded-[2.5rem] p-6 sm:p-10 md:p-12 backdrop-blur-xl shadow-2xl z-10 relative mt-8 text-left">
    
    <div class="border-b border-zinc-800/80 pb-6 mb-8">
        <span class="text-[#d6fb00] font-extrabold text-[11px] uppercase tracking-widest block mb-2">Disperakim Provinsi Jawa Tengah</span>
        <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight mb-3">
            FORMULIR PENDAFTARAN ONLINE
        </h2>
        <h3 class="text-sm sm:text-base font-bold text-zinc-300 mb-4">
            Bimbingan Teknis & Sertifikasi Pengembang Perumahan (SRP2)
        </h3>
        
        <div class="bg-[#d6fb00]/5 border border-[#d6fb00]/15 rounded-2xl p-4 flex gap-3 items-start">
            <div class="text-[#d6fb00] text-lg pt-0.5">
                <i class="fa-solid fa-circle-info"></i>
            </div>
            <div class="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                <strong class="text-[#d6fb00]">Petunjuk Pengisian:</strong> Mohon isi data di bawah ini dengan lengkap, benar, dan menggunakan huruf kapital pada nama serta nama perusahaan. Pastikan file dokumen yang diunggah memiliki format yang jelas (tidak blur).
            </div>
        </div>
    </div>

    <form action="<?php echo base_url('Pengembang/simpan'); ?>" method="POST" enctype="multipart/form-data" id="form-pendaftaran-srp2" class="space-y-10">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
        
        <div class="space-y-5">
            <div class="border-b border-zinc-800 pb-2 mb-4">
                <h4 class="text-sm font-black text-cyan-400 uppercase tracking-wider">
                    BAGIAN 1: DATA PERSONAL PESERTA
                </h4>
                <p class="text-[11px] text-zinc-500 mt-0.5">Bagian ini diisi dengan identitas perwakilan perusahaan yang akan mengikuti ujian sertifikasi.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="nama_peserta" class="block text-zinc-300 font-bold text-xs mb-2">Nama Lengkap Peserta (Beserta Gelar)</label>
                    <input type="text" id="nama_peserta" name="nama_peserta" placeholder="Contoh: IR. BUDI SANTOSO, S.T." required class="w-full bg-zinc-800/40 border border-zinc-700/60 text-white placeholder-[#5a7a80] rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00]/40 transition-all duration-300 uppercase">
                </div>
                <div>
                    <label for="nik_ktp" class="block text-zinc-300 font-bold text-xs mb-2">Nomor Induk Kependudukan (NIK KTP)</label>
                    <input type="text" id="nik_ktp" name="nik_ktp" maxlength="16" pattern="\d{16}" placeholder="Masukkan 16 digit angka NIK" required class="w-full bg-zinc-800/40 border border-zinc-700/60 text-white placeholder-[#5a7a80] rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00]/40 transition-all duration-300">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div>
                    <label for="jabatan" class="block text-zinc-300 font-bold text-xs mb-2">Jabatan di Perusahaan</label>
                    <div class="relative">
                        <select id="jabatan" name="jabatan" required class="w-full bg-zinc-800/40 border border-zinc-700/60 text-white rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00]/40 transition-all duration-300 appearance-none">
                            <option value="" disabled selected hidden>Pilih Jabatan</option>
                            <option value="direktur_utama">Direktur Utama / Pemilik</option>
                            <option value="manajer_proyek">Manajer Proyek / Direktur Teknis</option>
                            <option value="penanggung_jawab">Penanggung Jawab Lapangan</option>
                            <option value="staf_legal">Staf Legal / Perizinan</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-zinc-500">
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                </div>
                <div>
                    <label for="no_whatsapp" class="block text-zinc-300 font-bold text-xs mb-2">Nomor WhatsApp Aktif</label>
                    <input type="tel" id="no_whatsapp" name="no_whatsapp" pattern="[0-9]{10,15}" placeholder="Contoh: 081234567xxx" required class="w-full bg-zinc-800/40 border border-zinc-700/60 text-white placeholder-[#5a7a80] rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00]/40 transition-all duration-300">
                </div>
                <div>
                    <label for="email" class="block text-zinc-300 font-bold text-xs mb-2">Alamat Email</label>
                    <input type="email" id="email" name="email" placeholder="alamat@email.com" required class="w-full bg-zinc-800/40 border border-zinc-700/60 text-white placeholder-[#5a7a80] rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00]/40 transition-all duration-300">
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="border-b border-zinc-800 pb-2 mb-4">
                <h4 class="text-sm font-black text-cyan-400 uppercase tracking-wider">
                    BAGIAN 2: LEGALITAS & DATA PERUSAHAAN
                </h4>
                <p class="text-[11px] text-zinc-500 mt-0.5">Bagian ini diisi dengan profil badan usaha pengembang properti.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="nama_perusahaan" class="block text-zinc-300 font-bold text-xs mb-2">Nama Perusahaan / PT / CV</label>
                    <input type="text" id="nama_perusahaan" name="nama_perusahaan" placeholder="Masukkan nama badan usaha" required class="w-full bg-zinc-800/40 border border-zinc-700/60 text-white placeholder-[#5a7a80] rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00]/40 transition-all duration-300 uppercase">
                </div>
                <div>
                    <label for="nib" class="block text-zinc-300 font-bold text-xs mb-2">Nomor Induk Berusaha (NIB)</label>
                    <input type="text" id="nib" name="nib" maxlength="13" pattern="\d{13}" placeholder="Masukkan 13 digit angka NIB" required class="w-full bg-zinc-800/40 border border-zinc-700/60 text-white placeholder-[#5a7a80] rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00]/40 transition-all duration-300">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="asosiasi" class="block text-zinc-300 font-bold text-xs mb-2">Asosiasi Pengembang yang Diikuti</label>
                    <div class="relative">
                        <select id="asosiasi" name="asosiasi" required class="w-full bg-zinc-800/40 border border-zinc-700/60 text-white rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00]/40 transition-all duration-300 appearance-none">
                            <option value="" disabled selected hidden>Pilih Asosiasi</option>
                            <option value="rei">REI (Real Estat Indonesia)</option>
                            <option value="himperra">HIMPERRA (Himpunan Pengembang Permukiman dan Perumahan Rakyat)</option>
                            <option value="apersi">APERSI (Asosiasi Pengembang Perumahan Dan Permukiman Seluruh Indonesia)</option>
                            <option value="pi">PI (Pengembang Indonesia)</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-zinc-500">
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                </div>
                <div>
                    <label for="no_keanggotaan" class="block text-zinc-300 font-bold text-xs mb-2">Nomor Keanggotaan Asosiasi</label>
                    <input type="text" id="no_keanggotaan" name="no_keanggotaan" placeholder="Masukkan nomor KTA" required class="w-full bg-zinc-800/40 border border-zinc-700/60 text-white placeholder-[#5a7a80] rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00]/40 transition-all duration-300">
                </div>
            </div>

            <div>
                <label for="alamat_kantor" class="block text-zinc-300 font-bold text-xs mb-2">Alamat Kantor Perusahaan</label>
                <textarea id="alamat_kantor" name="alamat_kantor" rows="3" placeholder="Tuliskan alamat lengkap kantor pusat/cabang perusahaan" required class="w-full bg-zinc-800/40 border border-zinc-700/60 text-white placeholder-[#5a7a80] rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00]/40 transition-all duration-300 resize-none"></textarea>
            </div>
        </div>

        <div class="space-y-5">
            <div class="border-b border-zinc-800 pb-2 mb-4">
                <h4 class="text-sm font-black text-cyan-400 uppercase tracking-wider">
                    BAGIAN 3: UNGGAH DOKUMEN PENDUKUNG (UPLOAD FILE)
                </h4>
                <p class="text-[11px] text-zinc-500 mt-0.5">Maksimal ukuran file masing-masing adalah 5 MB, format PDF atau JPG/PNG.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="file_ktp" class="block text-zinc-300 font-bold text-xs mb-2">Scan KTP Peserta</label>
                    <div class="relative">
                        <input type="file" id="file_ktp" name="file_ktp" accept=".pdf, .jpg, .jpeg, .png" required class="w-full text-xs text-zinc-400 bg-zinc-800/40 border border-zinc-700/60 rounded-xl file:bg-zinc-700 file:text-white file:border-0 file:px-4 file:py-3 file:mr-4 file:font-bold hover:file:bg-zinc-600 file:transition-colors cursor-pointer">
                    </div>
                </div>
                <div>
                    <label for="file_kta" class="block text-zinc-300 font-bold text-xs mb-2">Scan Bukti KTA Asosiasi Aktif</label>
                    <div class="relative">
                        <input type="file" id="file_kta" name="file_kta" accept=".pdf, .jpg, .jpeg, .png" required class="w-full text-xs text-zinc-400 bg-zinc-800/40 border border-zinc-700/60 rounded-xl file:bg-zinc-700 file:text-white file:border-0 file:px-4 file:py-3 file:mr-4 file:font-bold hover:file:bg-zinc-600 file:transition-colors cursor-pointer">
                    </div>
                </div>
                <div>
                    <label for="file_nib" class="block text-zinc-300 font-bold text-xs mb-2">Scan NIB (Nomor Induk Berusaha)</label>
                    <div class="relative">
                        <input type="file" id="file_nib" name="file_nib" accept=".pdf" required class="w-full text-xs text-zinc-400 bg-zinc-800/40 border border-zinc-700/60 rounded-xl file:bg-zinc-700 file:text-white file:border-0 file:px-4 file:py-3 file:mr-4 file:font-bold hover:file:bg-zinc-600 file:transition-colors cursor-pointer">
                    </div>
                </div>
                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label for="file_surat_tugas" class="block text-zinc-300 font-bold text-xs">Surat Tugas dari Perusahaan</label>
                        <span class="text-[10px] text-[#d6fb00]/80 italic font-medium">*Wajib jika bukan Pemilik/Direktur Utama</span>
                    </div>
                    <div class="relative">
                        <input type="file" id="file_surat_tugas" name="file_surat_tugas" accept=".pdf" class="w-full text-xs text-zinc-400 bg-zinc-800/40 border border-zinc-700/60 rounded-xl file:bg-zinc-700 file:text-white file:border-0 file:px-4 file:py-3 file:mr-4 file:font-bold hover:file:bg-zinc-600 file:transition-colors cursor-pointer">
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-5 pt-2">
            <div class="border-b border-zinc-800 pb-2 mb-4">
                <h4 class="text-sm font-black text-cyan-400 uppercase tracking-wider">
                    BAGIAN 4: PERNYATAAN & KONFIRMASI
                </h4>
            </div>

            <label for="pernyataan" class="flex items-start gap-3.5 group cursor-pointer bg-[#d6fb00]/5 border border-zinc-800 p-4 sm:p-5 rounded-2xl hover:border-zinc-700/80 transition-all duration-300">
                <input type="checkbox" id="pernyataan" name="pernyataan" value="1" required class="w-5 h-5 rounded border-zinc-700 text-[#d6fb00] bg-zinc-800/60 focus:ring-[#d6fb00]/30 accent-[#d6fb00] shrink-0 mt-0.5">
                <span class="text-xs sm:text-sm text-zinc-400 group-hover:text-zinc-300 font-medium leading-relaxed select-none transition-colors">
                    Dengan ini saya menyatakan bahwa seluruh data dan dokumen yang saya kirimkan adalah benar, sah, dan dapat dipertanggungjawabkan. Jika di kemudian hari ditemukan pemalsuan, saya bersedia menerima sanksi pembatalan kepesertaan sertifikasi.
                </span>
            </label>
        </div>

        <div class="pt-4 flex flex-col sm:flex-row gap-3">
            <button type="submit" id="btn-submit" class="w-full sm:w-auto bg-[#d6fb00] hover:bg-[#c2e600] text-[#0a1a1f] font-extrabold text-xs sm:text-sm uppercase tracking-wider px-10 py-4 rounded-xl transition-all duration-300 shadow-lg hover:shadow-[#d6fb00]/20 active:scale-[0.98]">
                Kirim Formulir Pendaftaran
            </button>
            <button type="reset" id="btn-reset" class="w-full sm:w-auto bg-transparent hover:bg-[#d6fb00]/5 border border-zinc-800 hover:border-[#d6fb00]/20 text-zinc-500 hover:text-white font-bold text-xs sm:text-sm uppercase tracking-wider px-6 py-4 rounded-xl transition-colors">
                Kosongkan Form
            </button>
        </div>

    </form>
</section>