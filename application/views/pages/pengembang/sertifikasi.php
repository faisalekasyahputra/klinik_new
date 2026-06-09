<section class="w-full  pt-24 pb-16 px-4 sm:px-6 lg:px-8 relative min-h-screen font-outfit">
    <!-- Background Ornaments -->
    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <!-- Batik Pattern Overlay -->
        
        
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[300px] bg-[#d6fb00]/5 blur-[120px] rounded-full pointer-events-none"></div>
    </div>

    <div class="max-w-4xl mx-auto relative z-10">
        <!-- Breadcrumb -->
        <div class="mb-8">
            <nav class="flex text-[10px] sm:text-xs text-zinc-500 font-bold uppercase tracking-widest" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-2">
                    <li class="inline-flex items-center">
                        <a href="<?= base_url() ?>" class="hover:text-[#d6fb00] transition-colors"><i class="fa-solid fa-house mr-2"></i>Beranda</a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fa-solid fa-chevron-right text-[8px] mx-2"></i>
                            <a href="<?= base_url('umum/pengembang') ?>" class="hover:text-[#d6fb00] transition-colors">Menu Pengembang</a>
                        </div>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fa-solid fa-chevron-right text-[8px] mx-2"></i>
                            <span class="text-[#d6fb00]">Sertifikasi Registrasi</span>
                        </div>
                    </li>
                </ol>
            </nav>
        </div>

        <div class="bg-[#0f2a30] border border-[#d6fb00]/20 rounded-[24px] p-6 sm:p-8 shadow-2xl shadow-black/40">
            <!-- Header -->
            <div class="border-b border-[#d6fb00]/10 pb-5 mb-6">
                <h2 class="text-2xl font-black text-white tracking-tight mb-2 font-jakarta">
                    Formulir <span class="text-[#d6fb00]">Pendaftaran</span> SRP2
                </h2>
                <div class="bg-[#d6fb00]/10 border border-[#d6fb00]/20 rounded-xl p-3 flex gap-3 items-center mt-3">
                    <div class="text-[#d6fb00] text-lg">
                        <i class="fa-solid fa-circle-info"></i>
                    </div>
                    <div class="text-[11px] sm:text-xs text-zinc-300">
                        Isi form menggunakan huruf kapital pada isian nama. Pastikan dokumen yang diunggah jelas dan mudah terbaca.
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form action="<?php echo base_url('Pengembang/simpan'); ?>" method="POST" enctype="multipart/form-data" id="form-pendaftaran-srp2" class="space-y-6">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Column 1: Personal -->
                    <div class="space-y-4">
                        <div>
                            <h4 class="text-[11px] font-black text-[#d6fb00] uppercase tracking-widest mb-3">1. Data Personal</h4>
                        </div>
                        
                        <div>
                            <label for="nama_peserta" class="block text-zinc-400 font-medium text-[11px] mb-1.5 uppercase tracking-wide">Nama Lengkap (Beserta Gelar)</label>
                            <input type="text" id="nama_peserta" name="nama_peserta" placeholder="Contoh: IR. BUDI SANTOSO, S.T." required class="w-full bg-[#0a1a1f] border border-[#d6fb00]/20 text-white placeholder-zinc-600 rounded-lg px-3 py-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-[#d6fb00] focus:border-[#d6fb00] transition-all uppercase shadow-inner">
                        </div>

                        <div>
                            <label for="nik_ktp" class="block text-zinc-400 font-medium text-[11px] mb-1.5 uppercase tracking-wide">NIK KTP (16 Digit)</label>
                            <input type="text" id="nik_ktp" name="nik_ktp" maxlength="16" pattern="\d{16}" placeholder="Masukkan NIK" required class="w-full bg-[#0a1a1f] border border-[#d6fb00]/20 text-white placeholder-zinc-600 rounded-lg px-3 py-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-[#d6fb00] focus:border-[#d6fb00] transition-all shadow-inner">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="no_whatsapp" class="block text-zinc-400 font-medium text-[11px] mb-1.5 uppercase tracking-wide">No. WhatsApp</label>
                                <input type="tel" id="no_whatsapp" name="no_whatsapp" pattern="[0-9]{10,15}" placeholder="08xxxx" required class="w-full bg-[#0a1a1f] border border-[#d6fb00]/20 text-white placeholder-zinc-600 rounded-lg px-3 py-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-[#d6fb00] focus:border-[#d6fb00] transition-all shadow-inner">
                            </div>
                            <div>
                                <label for="email" class="block text-zinc-400 font-medium text-[11px] mb-1.5 uppercase tracking-wide">Email</label>
                                <input type="email" id="email" name="email" placeholder="alamat@email.com" required class="w-full bg-[#0a1a1f] border border-[#d6fb00]/20 text-white placeholder-zinc-600 rounded-lg px-3 py-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-[#d6fb00] focus:border-[#d6fb00] transition-all shadow-inner">
                            </div>
                        </div>

                        <div>
                            <label for="jabatan" class="block text-zinc-400 font-medium text-[11px] mb-1.5 uppercase tracking-wide">Jabatan Perusahaan</label>
                            <div class="relative">
                                <select id="jabatan" name="jabatan" required class="w-full bg-[#0a1a1f] border border-[#d6fb00]/20 text-white rounded-lg px-3 py-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-[#d6fb00] focus:border-[#d6fb00] transition-all appearance-none shadow-inner">
                                    <option value="" disabled selected hidden>Pilih Jabatan</option>
                                    <option value="direktur_utama">Direktur Utama</option>
                                    <option value="manajer_proyek">Manajer Proyek</option>
                                    <option value="penanggung_jawab">Penanggung Jawab Lapangan</option>
                                    <option value="staf_legal">Staf Legal</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-[#d6fb00]">
                                    <i class="fa-solid fa-chevron-down text-[10px]"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Column 2: Perusahaan -->
                    <div class="space-y-4">
                        <div>
                            <h4 class="text-[11px] font-black text-cyan-400 uppercase tracking-widest mb-3">2. Data Perusahaan</h4>
                        </div>

                        <div>
                            <label for="nama_perusahaan" class="block text-zinc-400 font-medium text-[11px] mb-1.5 uppercase tracking-wide">Nama Perusahaan (PT/CV)</label>
                            <input type="text" id="nama_perusahaan" name="nama_perusahaan" placeholder="PT / CV..." required class="w-full bg-[#0a1a1f] border border-[#d6fb00]/20 text-white placeholder-zinc-600 rounded-lg px-3 py-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-[#d6fb00] focus:border-[#d6fb00] transition-all uppercase shadow-inner">
                        </div>

                        <div>
                            <label for="nib" class="block text-zinc-400 font-medium text-[11px] mb-1.5 uppercase tracking-wide">NIB (13 Digit)</label>
                            <input type="text" id="nib" name="nib" maxlength="13" pattern="\d{13}" placeholder="Masukkan NIB" required class="w-full bg-[#0a1a1f] border border-[#d6fb00]/20 text-white placeholder-zinc-600 rounded-lg px-3 py-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-[#d6fb00] focus:border-[#d6fb00] transition-all shadow-inner">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="asosiasi" class="block text-zinc-400 font-medium text-[11px] mb-1.5 uppercase tracking-wide">Asosiasi</label>
                                <div class="relative">
                                    <select id="asosiasi" name="asosiasi" required class="w-full bg-[#0a1a1f] border border-[#d6fb00]/20 text-white rounded-lg px-3 py-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-[#d6fb00] focus:border-[#d6fb00] transition-all appearance-none shadow-inner">
                                        <option value="" disabled selected hidden>Pilih</option>
                                        <option value="rei">REI</option>
                                        <option value="himperra">HIMPERRA</option>
                                        <option value="apersi">APERSI</option>
                                        <option value="pi">PI</option>
                                        <option value="lainnya">Lainnya</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-[#d6fb00]">
                                        <i class="fa-solid fa-chevron-down text-[10px]"></i>
                                    </div>
                                </div>
                            </div>
                            <div>
                                <label for="no_keanggotaan" class="block text-zinc-400 font-medium text-[11px] mb-1.5 uppercase tracking-wide">No. KTA</label>
                                <input type="text" id="no_keanggotaan" name="no_keanggotaan" placeholder="No KTA" required class="w-full bg-[#0a1a1f] border border-[#d6fb00]/20 text-white placeholder-zinc-600 rounded-lg px-3 py-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-[#d6fb00] focus:border-[#d6fb00] transition-all shadow-inner">
                            </div>
                        </div>

                        <div>
                            <label for="alamat_kantor" class="block text-zinc-400 font-medium text-[11px] mb-1.5 uppercase tracking-wide">Alamat Lengkap Kantor</label>
                            <textarea id="alamat_kantor" name="alamat_kantor" rows="2" placeholder="Tuliskan alamat lengkap..." required class="w-full bg-[#0a1a1f] border border-[#d6fb00]/20 text-white placeholder-zinc-600 rounded-lg px-3 py-2.5 text-xs focus:outline-none focus:ring-1 focus:ring-[#d6fb00] focus:border-[#d6fb00] transition-all resize-none shadow-inner"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Column 3: Upload -->
                <div class="pt-4 border-t border-[#d6fb00]/10">
                    <h4 class="text-[11px] font-black text-purple-400 uppercase tracking-widest mb-3">3. Dokumen Pendukung (PDF/JPG, Maks 5MB)</h4>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div>
                            <label for="file_ktp" class="block text-zinc-400 font-medium text-[10px] mb-1.5 uppercase tracking-wide">KTP Peserta</label>
                            <input type="file" id="file_ktp" name="file_ktp" accept=".pdf, .jpg, .jpeg, .png" required class="w-full text-[10px] text-zinc-400 bg-[#0a1a1f] border border-[#d6fb00]/20 rounded-lg file:bg-[#d6fb00]/10 file:text-[#d6fb00] file:border-0 file:px-3 file:py-2 file:mr-2 file:font-bold hover:file:bg-[#d6fb00]/20 file:transition-colors cursor-pointer">
                        </div>
                        <div>
                            <label for="file_kta" class="block text-zinc-400 font-medium text-[10px] mb-1.5 uppercase tracking-wide">KTA Asosiasi</label>
                            <input type="file" id="file_kta" name="file_kta" accept=".pdf, .jpg, .jpeg, .png" required class="w-full text-[10px] text-zinc-400 bg-[#0a1a1f] border border-[#d6fb00]/20 rounded-lg file:bg-[#d6fb00]/10 file:text-[#d6fb00] file:border-0 file:px-3 file:py-2 file:mr-2 file:font-bold hover:file:bg-[#d6fb00]/20 file:transition-colors cursor-pointer">
                        </div>
                        <div>
                            <label for="file_nib" class="block text-zinc-400 font-medium text-[10px] mb-1.5 uppercase tracking-wide">Scan NIB</label>
                            <input type="file" id="file_nib" name="file_nib" accept=".pdf" required class="w-full text-[10px] text-zinc-400 bg-[#0a1a1f] border border-[#d6fb00]/20 rounded-lg file:bg-[#d6fb00]/10 file:text-[#d6fb00] file:border-0 file:px-3 file:py-2 file:mr-2 file:font-bold hover:file:bg-[#d6fb00]/20 file:transition-colors cursor-pointer">
                        </div>
                        <div>
                            <label for="file_surat_tugas" class="block text-zinc-400 font-medium text-[10px] mb-1.5 uppercase tracking-wide">Surat Tugas <span class="text-[10px] text-red-500">*</span></label>
                            <input type="file" id="file_surat_tugas" name="file_surat_tugas" accept=".pdf" class="w-full text-[10px] text-zinc-400 bg-[#0a1a1f] border border-[#d6fb00]/20 rounded-lg file:bg-[#d6fb00]/10 file:text-[#d6fb00] file:border-0 file:px-3 file:py-2 file:mr-2 file:font-bold hover:file:bg-[#d6fb00]/20 file:transition-colors cursor-pointer">
                        </div>
                    </div>
                </div>

                <!-- Terms & Submit -->
                <div class="pt-4 border-t border-[#d6fb00]/10 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <label for="pernyataan" class="flex items-start gap-3 group cursor-pointer flex-1">
                        <input type="checkbox" id="pernyataan" name="pernyataan" value="1" required class="w-4 h-4 rounded border-[#d6fb00]/40 text-[#d6fb00] bg-[#0a1a1f] focus:ring-[#d6fb00] focus:ring-offset-0 focus:ring-1 shrink-0 mt-0.5">
                        <span class="text-[10px] sm:text-[11px] text-zinc-400 group-hover:text-zinc-300 font-medium leading-relaxed select-none transition-colors">
                            Saya menyatakan data ini benar & sah. Saya bersedia menerima sanksi pembatalan jika terbukti ada pemalsuan.
                        </span>
                    </label>

                    <div class="flex gap-2 w-full sm:w-auto flex-shrink-0">
                        <button type="reset" class="px-4 py-2.5 rounded-xl border border-zinc-600 text-zinc-400 hover:text-white hover:border-zinc-400 text-xs font-bold transition-colors">
                            Reset
                        </button>
                        <button type="submit" class="bg-[#d6fb00] hover:bg-[#c2e600] text-[#0a1a1f] px-6 py-2.5 rounded-xl text-xs font-bold transition-all shadow-[0_0_15px_rgba(214,251,0,0.3)] hover:shadow-[0_0_25px_rgba(214,251,0,0.5)]">
                            <i class="fa-solid fa-paper-plane mr-1.5"></i> Kirim Formulir
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</section>
