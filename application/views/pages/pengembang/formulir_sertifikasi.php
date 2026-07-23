<section class="w-full max-w-4xl mx-auto rounded-[2.5rem] p-6 sm:p-10 md:p-12 shadow-2xl z-10 relative mt-8 text-left" style="background-color: var(--bg-card); border: 1px solid rgba(255,255,255,0.08);">

    <div class="border-b border-zinc-800/80 pb-6 mb-8">
        <h2 class="text-2xl sm:text-3xl font-black text-white tracking-tight mb-3">
            FORMULIR PENDAFTARAN ONLINE
        </h2>
        <h3 class="text-sm sm:text-base font-bold text-zinc-400 mb-4">
            Bimbingan Teknis & Sertifikasi Pengembang Perumahan (SRP2)
        </h3>

        <div class="bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-2xl p-4 flex gap-3 items-start">
            <div class="text-[#d6fb00] text-lg pt-0.5">
                <i class="fa-solid fa-circle-info"></i>
            </div>
            <div class="text-xs sm:text-sm text-zinc-400 leading-relaxed">
                <strong class="text-[#d6fb00]">Petunjuk Pengisian:</strong> Mohon isi data di bawah ini dengan lengkap, benar, dan menggunakan huruf kapital pada nama serta nama perusahaan. Pastikan file dokumen yang diunggah memiliki format yang jelas (tidak blur).
            </div>
        </div>
    </div>

    <?php if($this->session->flashdata('error')): ?>
    <div class="bg-red-500/10 border border-red-500/20 text-red-400 rounded-2xl p-4 mb-8 text-xs sm:text-sm leading-relaxed">
        <?php echo $this->session->flashdata('error'); ?>
    </div>
    <?php endif; ?>

    <form action="<?php echo base_url('Pengembang/simpan'); ?>" method="POST" id="form-pendaftaran-srp2" class="space-y-10">
        <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

        <div class="space-y-5">
            <div class="border-b border-zinc-800 pb-2 mb-4">
                <h4 class="text-sm font-black text-[#d6fb00] uppercase tracking-wider">
                    BAGIAN 1: DATA PERSONAL PESERTA
                </h4>
                <p class="text-[11px] text-zinc-500 mt-0.5">Bagian ini diisi dengan identitas perwakilan perusahaan yang akan mengikuti ujian sertifikasi.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="nama_peserta" class="block text-zinc-300 font-bold text-xs mb-2">Nama Lengkap Peserta (Beserta Gelar)</label>
                    <input type="text" id="nama_peserta" name="nama_peserta" placeholder="Contoh: IR. BUDI SANTOSO, S.T." required class="w-full bg-black/20 border border-zinc-700 text-white placeholder-zinc-600 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00] transition-all duration-300 uppercase">
                </div>
                <div>
                    <label for="nik_ktp" class="block text-zinc-300 font-bold text-xs mb-2">Nomor Induk Kependudukan (NIK KTP)</label>
                    <input type="text" id="nik_ktp" name="nik_ktp" maxlength="16" pattern="\d{16}" placeholder="Masukkan 16 digit angka NIK" required class="w-full bg-black/20 border border-zinc-700 text-white placeholder-zinc-600 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00] transition-all duration-300">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                <div>
                    <label for="jabatan" class="block text-zinc-300 font-bold text-xs mb-2">Jabatan di Perusahaan</label>
                    <div class="relative">
                        <select id="jabatan" name="jabatan" required class="w-full bg-black/20 border border-zinc-700 text-white rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00] transition-all duration-300 appearance-none">
                            <option value="" disabled selected hidden class="text-zinc-900">Pilih Jabatan</option>
                            <option value="direktur_utama" class="text-zinc-900">Direktur Utama / Pemilik</option>
                            <option value="manajer_proyek" class="text-zinc-900">Manajer Proyek / Direktur Teknis</option>
                            <option value="penanggung_jawab" class="text-zinc-900">Penanggung Jawab Lapangan</option>
                            <option value="staf_legal" class="text-zinc-900">Staf Legal / Perizinan</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-zinc-500">
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                </div>
                <div>
                    <label for="no_whatsapp" class="block text-zinc-300 font-bold text-xs mb-2">Nomor WhatsApp Aktif</label>
                    <input type="tel" id="no_whatsapp" name="no_whatsapp" pattern="[0-9]{10,15}" placeholder="Contoh: 081234567xxx" required class="w-full bg-black/20 border border-zinc-700 text-white placeholder-zinc-600 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00] transition-all duration-300">
                </div>
                <div>
                    <label for="email" class="block text-zinc-300 font-bold text-xs mb-2">Alamat Email</label>
                    <input type="email" id="email" name="email" placeholder="alamat@email.com" required class="w-full bg-black/20 border border-zinc-700 text-white placeholder-zinc-600 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00] transition-all duration-300">
                </div>
            </div>
        </div>

        <div class="space-y-5">
            <div class="border-b border-zinc-800 pb-2 mb-4">
                <h4 class="text-sm font-black text-[#d6fb00] uppercase tracking-wider">
                    BAGIAN 2: LEGALITAS & DATA PERUSAHAAN
                </h4>
                <p class="text-[11px] text-zinc-500 mt-0.5">Bagian ini diisi dengan profil badan usaha pengembang properti.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="nama_perusahaan" class="block text-zinc-300 font-bold text-xs mb-2">Nama Perusahaan / PT / CV</label>
                    <input type="text" id="nama_perusahaan" name="nama_perusahaan" placeholder="Masukkan nama badan usaha" required class="w-full bg-black/20 border border-zinc-700 text-white placeholder-zinc-600 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00] transition-all duration-300 uppercase">
                </div>
                <div>
                    <label for="nib" class="block text-zinc-300 font-bold text-xs mb-2">Nomor Induk Berusaha (NIB)</label>
                    <input type="text" id="nib" name="nib" maxlength="13" pattern="\d{13}" placeholder="Masukkan 13 digit angka NIB" required class="w-full bg-black/20 border border-zinc-700 text-white placeholder-zinc-600 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00] transition-all duration-300">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="asosiasi" class="block text-zinc-300 font-bold text-xs mb-2">Asosiasi Pengembang yang Diikuti</label>
                    <div class="relative">
                        <select id="asosiasi" name="asosiasi" required class="w-full bg-black/20 border border-zinc-700 text-white rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00] transition-all duration-300 appearance-none">
                            <option value="" disabled selected hidden class="text-zinc-900">Pilih Asosiasi</option>
                            <option value="rei" class="text-zinc-900">REI (Real Estat Indonesia)</option>
                            <option value="himperra" class="text-zinc-900">HIMPERRA (Himpunan Pengembang Permukiman dan Perumahan Rakyat)</option>
                            <option value="apersi" class="text-zinc-900">APERSI (Asosiasi Pengembang Perumahan Dan Permukiman Seluruh Indonesia)</option>
                            <option value="pi" class="text-zinc-900">PI (Pengembang Indonesia)</option>
                            <option value="lainnya" class="text-zinc-900">Lainnya</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-zinc-500">
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                </div>
                <div>
                    <label for="no_keanggotaan" class="block text-zinc-300 font-bold text-xs mb-2">Nomor Keanggotaan Asosiasi</label>
                    <input type="text" id="no_keanggotaan" name="no_keanggotaan" placeholder="Masukkan nomor KTA" required class="w-full bg-black/20 border border-zinc-700 text-white placeholder-zinc-600 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00] transition-all duration-300">
                </div>
            </div>

            <div>
                <label for="alamat_kantor" class="block text-zinc-300 font-bold text-xs mb-2">Alamat Kantor Perusahaan</label>
                <textarea id="alamat_kantor" name="alamat_kantor" rows="3" placeholder="Tuliskan alamat lengkap kantor pusat/cabang perusahaan" required class="w-full bg-black/20 border border-zinc-700 text-white placeholder-zinc-600 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-[#d6fb00]/40 focus:border-[#d6fb00] transition-all duration-300 resize-none"></textarea>
            </div>
        </div>

        <div class="space-y-5" hidden>
            <div class="border-b border-zinc-800 pb-2 mb-4">
                <h4 class="text-sm font-black text-[#d6fb00] uppercase tracking-wider">
                    BAGIAN 3: UNGGAH DOKUMEN PENDUKUNG (UPLOAD FILE)
                </h4>
                <p class="text-[11px] text-zinc-500 mt-0.5">Maksimal ukuran file masing-masing adalah 5 MB, format PDF atau JPG/PNG.</p>
            </div>

            <div class="space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-4 rounded-xl bg-black/20 border border-zinc-700">
                    <label for="file_ktp" class="w-full sm:w-56 shrink-0 text-zinc-300 font-bold text-xs">Scan KTP Peserta</label>
                    <input type="file" id="file_ktp" name="file_ktp" accept=".pdf, .jpg, .jpeg, .png" disabled class="w-full text-xs text-zinc-400 file:bg-[#d6fb00]/10 file:text-[#d6fb00] file:border file:border-[#d6fb00]/20 file:rounded-lg file:px-4 file:py-2 file:mr-4 file:font-bold hover:file:bg-[#d6fb00]/20 file:transition-colors cursor-pointer">
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-4 rounded-xl bg-black/20 border border-zinc-700">
                    <label for="file_kta" class="w-full sm:w-56 shrink-0 text-zinc-300 font-bold text-xs">Scan Bukti KTA Asosiasi Aktif</label>
                    <input type="file" id="file_kta" name="file_kta" accept=".pdf, .jpg, .jpeg, .png" disabled class="w-full text-xs text-zinc-400 file:bg-[#d6fb00]/10 file:text-[#d6fb00] file:border file:border-[#d6fb00]/20 file:rounded-lg file:px-4 file:py-2 file:mr-4 file:font-bold hover:file:bg-[#d6fb00]/20 file:transition-colors cursor-pointer">
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-4 rounded-xl bg-black/20 border border-zinc-700">
                    <label for="file_nib" class="w-full sm:w-56 shrink-0 text-zinc-300 font-bold text-xs">Scan NIB (Nomor Induk Berusaha)</label>
                    <input type="file" id="file_nib" name="file_nib" accept=".pdf" disabled class="w-full text-xs text-zinc-400 file:bg-[#d6fb00]/10 file:text-[#d6fb00] file:border file:border-[#d6fb00]/20 file:rounded-lg file:px-4 file:py-2 file:mr-4 file:font-bold hover:file:bg-[#d6fb00]/20 file:transition-colors cursor-pointer">
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-4 rounded-xl bg-black/20 border border-zinc-700">
                    <div class="w-full sm:w-56 shrink-0">
                        <label for="file_surat_tugas" class="block text-zinc-300 font-bold text-xs">Surat Tugas dari Perusahaan</label>
                        <span class="text-[10px] text-[#d6fb00] italic font-medium">*Wajib jika bukan Pemilik/Direktur Utama</span>
                    </div>
                    <input type="file" id="file_surat_tugas" name="file_surat_tugas" accept=".pdf" class="w-full text-xs text-zinc-400 file:bg-zinc-700/50 file:text-zinc-300 file:border file:border-zinc-600 file:rounded-lg file:px-4 file:py-2 file:mr-4 file:font-bold hover:file:bg-zinc-700 file:transition-colors cursor-pointer">
                </div>
            </div>
        </div>

        <div class="space-y-5 pt-2">
            <div class="border-b border-zinc-800 pb-2 mb-4">
                <h4 class="text-sm font-black text-[#d6fb00] uppercase tracking-wider">
                    BAGIAN 4: PERNYATAAN & KONFIRMASI
                </h4>
            </div>

            <label for="pernyataan" class="flex items-start gap-3.5 group cursor-pointer bg-black/20 border border-zinc-700 p-4 sm:p-5 rounded-2xl hover:border-zinc-600 transition-all duration-300">
                <input type="checkbox" id="pernyataan" name="pernyataan" value="1" required class="w-5 h-5 rounded border-zinc-600 bg-black/30 focus:ring-[#d6fb00]/30 accent-[#d6fb00] shrink-0 mt-0.5">
                <span class="text-xs sm:text-sm text-zinc-400 group-hover:text-zinc-200 font-medium leading-relaxed select-none transition-colors">
                    Dengan ini saya menyatakan bahwa seluruh data dan dokumen yang saya kirimkan adalah benar, sah, dan dapat dipertanggungjawabkan. Jika di kemudian hari ditemukan pemalsuan, saya bersedia menerima sanksi pembatalan kepesertaan sertifikasi.
                </span>
            </label>
        </div>

        <div class="pt-4 flex flex-col sm:flex-row gap-3">
            <button type="submit" id="btn-submit" class="w-full sm:w-auto bg-[#d6fb00] hover:bg-[#c2e600] text-[#0a1a1f] font-extrabold text-xs sm:text-sm uppercase tracking-wider px-10 py-4 rounded-xl transition-all duration-300 shadow-lg shadow-[#d6fb00]/10 hover:shadow-[#d6fb00]/20 active:scale-[0.98]">
                Lanjutkan Unggah Dokumen
            </button>
            <button type="reset" id="btn-reset" class="w-full sm:w-auto bg-transparent hover:bg-white/5 border border-zinc-700 text-zinc-400 hover:text-zinc-200 font-bold text-xs sm:text-sm uppercase tracking-wider px-6 py-4 rounded-xl transition-colors">
                Kosongkan Form
            </button>
        </div>

    </form>
</section>
