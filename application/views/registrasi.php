<section class="min-h-[calc(100vh-5rem)] bg-[#0a1a1f] flex items-center justify-center px-4 py-12 relative overflow-hidden">
    
    <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-[#d6fb00]/10 rounded-full blur-[120px] pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-[#d6fb00]/5 rounded-full blur-[150px] pointer-events-none"></div>

    <div class="w-full max-w-xl bg-[#0f2a30]/80 backdrop-blur-xl border border-[#d6fb00]/20 rounded-3xl p-8 md:p-10 shadow-2xl relative z-10">
        
        <div class="mb-8 text-center sm:text-left">
            <h2 class="text-xl font-black text-white uppercase tracking-tight flex items-center justify-center sm:justify-start gap-2.5">
                <i class="fa-solid fa-user-plus text-[#d6fb00] text-lg"></i>
                Registrasi <span class="text-[#d6fb00]">User Baru</span>
            </h2>
            <p class="text-[11px] font-bold text-zinc-500 uppercase tracking-widest mt-1.5">
                Lengkapi data identitas Anda secara benar untuk akses layanan
            </p>
        </div>

        <form action="<?php echo base_url('Auth/update'); ?>" method="POST" enctype="multipart/form-data" class="space-y-6">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
            
            <div class="space-y-2">
                <label for="nama_lengkap" class="block text-[11px] font-extrabold text-zinc-400 uppercase tracking-wider">
                    Nama Lengkap <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-zinc-500">
                        <i class="fa-solid fa-user text-xs"></i>
                    </div>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" required placeholder="Masukkan nama sesuai KTP"
                        class="w-full pl-11 pr-4 py-3 bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl text-zinc-200 placeholder-[#5a7a80] text-sm focus:outline-none focus:border-[#d6fb00]/40 focus:ring-1 focus:ring-[#d6fb00]/40 transition-all">
                </div>
            </div>

            <div class="space-y-2">
                <label for="kategori_user" class="block text-[11px] font-extrabold text-zinc-400 uppercase tracking-wider">
                    Mendaftar Sebagai <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-zinc-500">
                        <i class="fa-solid fa-user-gear text-xs"></i>
                    </div>
                    <select id="kategori_user" name="kategori_user" required onchange="handleKategoriChange(this.value)"
                        class="w-full pl-11 pr-10 py-3 bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl text-zinc-200 text-sm focus:outline-none focus:border-[#d6fb00]/40 focus:ring-1 focus:ring-[#d6fb00]/40 transition-all appearance-none cursor-pointer">
                        <option value="" disabled selected class="bg-[#0f2a30] text-zinc-600">-- Pilih Kategori Pendaftaran --</option>
                        <option value="Pengembang" class="bg-[#0f2a30] text-zinc-300">Pengembang</option>
                        <option value="Mahasiswa Magang" class="bg-[#0f2a30] text-zinc-300">Mahasiswa Magang</option>
                        <option value="Masyarakat Umum" class="bg-[#0f2a30] text-zinc-300">Masyarakat Umum</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-4 flex items-center pointer-events-none text-zinc-500">
                        <i class="fa-solid fa-chevron-down text-[10px]"></i>
                    </div>
                </div>
            </div>

            <div id="section_pengembang" class="hidden space-y-4 p-4 rounded-2xl bg-[#d6fb00]/5 border border-[#d6fb00]/20">
                <p class="text-[10px] font-extrabold text-[#d6fb00] uppercase tracking-wider mb-2"><i class="fa-solid fa-folder-open mr-1"></i> Dokumen Lampiran Pengembang</p>
                <div class="space-y-2">
                    <label for="file_identitas_pengembang" class="block text-[11px] font-bold text-zinc-400">File Identitas KTP/Paspor Pengembang <span class="text-rose-500">*</span></label>
                    <input type="file" id="file_identitas_pengembang" name="file_identitas_pengembang" accept=".pdf,.jpg,.jpeg,.png"
                        class="w-full text-xs text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#d6fb00]/8 file:text-white hover:file:bg-[#d6fb00]/12 file:cursor-pointer bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl p-1 focus:outline-none">
                </div>
                <div class="space-y-2">
                    <label for="file_ijin_pengembang" class="block text-[11px] font-bold text-zinc-400">Surat Ijin Pengembang (SIUP/NIB/SIUJK) <span class="text-rose-500">*</span></label>
                    <input type="file" id="file_ijin_pengembang" name="file_ijin_pengembang" accept=".pdf"
                        class="w-full text-xs text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#d6fb00]/8 file:text-white hover:file:bg-[#d6fb00]/12 file:cursor-pointer bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl p-1 focus:outline-none">
                </div>
            </div>

            <div id="section_mahasiswa" class="hidden space-y-4 p-4 rounded-2xl bg-blue-500/5 border border-blue-500/10">
                <p class="text-[10px] font-extrabold text-blue-400 uppercase tracking-wider mb-2"><i class="fa-solid fa-folder-open mr-1"></i> Dokumen Lampiran Mahasiswa</p>
                <div class="space-y-2">
                    <label for="file_identitas_mahasiswa" class="block text-[11px] font-bold text-zinc-400">File Kartu Tanda Mahasiswa (KTM) <span class="text-rose-500">*</span></label>
                    <input type="file" id="file_identitas_mahasiswa" name="file_identitas_mahasiswa" accept=".pdf,.jpg,.jpeg,.png"
                        class="w-full text-xs text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#d6fb00]/8 file:text-white hover:file:bg-[#d6fb00]/12 file:cursor-pointer bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl p-1 focus:outline-none">
                </div>
                <div class="space-y-2">
                    <label for="file_surat_keterangan" class="block text-[11px] font-bold text-zinc-400">Surat Keterangan Magang / Pengantar Kampus <span class="text-rose-500">*</span></label>
                    <input type="file" id="file_surat_keterangan" name="file_surat_keterangan" accept=".pdf"
                        class="w-full text-xs text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#d6fb00]/8 file:text-white hover:file:bg-[#d6fb00]/12 file:cursor-pointer bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl p-1 focus:outline-none">
                </div>
                    
            </div>

            <div class="space-y-2">
                <label for="nik_identitas" class="block text-[11px] font-extrabold text-zinc-400 uppercase tracking-wider">
                    No. Identitas (NIK) <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-zinc-500">
                        <i class="fa-solid fa-id-card text-xs"></i>
                    </div>
                    <input type="number" id="nik_identitas" name="nik_identitas" required placeholder="Masukkan 16 digit NIK"
                        class="w-full pl-11 pr-4 py-3 bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl text-zinc-200 placeholder-[#5a7a80] text-sm focus:outline-none focus:border-[#d6fb00]/40 focus:ring-1 focus:ring-[#d6fb00]/40 transition-all [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                </div>
            </div>

            <div class="space-y-2">
                <label for="email_user" class="block text-[11px] font-extrabold text-zinc-400 uppercase tracking-wider">
                    Alamat Email <span class="text-rose-500">*</span>
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-zinc-500">
                        <i class="fa-solid fa-envelope text-xs"></i>
                    </div>
                    <input readonly type="email" value="<?=$user[0]->email?>" id="email_user" name="email_user" required placeholder="contoh@email.com"
                        class="w-full pl-11 pr-4 py-3 bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl text-zinc-200 placeholder-[#5a7a80] text-sm focus:outline-none focus:border-[#d6fb00]/40 focus:ring-1 focus:ring-[#d6fb00]/40 transition-all">
                </div>
            </div>

            <div class="space-y-2">
                <label for="alamat_domisili" class="block text-[11px] font-extrabold text-zinc-400 uppercase tracking-wider">
                    Alamat Domisili <span class="text-rose-500">*</span>
                    
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 pt-3.5 flex items-start pointer-events-none text-zinc-500">
                        <i class="fa-solid fa-map-location-dot text-xs"></i>
                    </div>
                    <textarea id="alamat_domisili" name="alamat_domisili" required rows="3" placeholder="Tuliskan alamat lengkap beserta RT/RW dan nama desa/kelurahan"
                        class="w-full pl-11 pr-4 py-3 bg-[#d6fb00]/5 border border-[#d6fb00]/20 rounded-xl text-zinc-200 placeholder-[#5a7a80] text-sm focus:outline-none focus:border-[#d6fb00]/40 focus:ring-1 focus:ring-[#d6fb00]/40 transition-all resize-none"></textarea>
                    </div>
            </div>

            <div class="pt-2 flex flex-col sm:flex-row items-center gap-3">
                <button type="submit" id="btn_submit_registrasi" name="btn_submit_registrasi"
                    class="w-full sm:w-auto px-6 py-3 bg-[#d6fb00] hover:bg-[#c2e600] text-black font-bold text-xs uppercase tracking-wider rounded-xl transition-all shadow-lg shadow-[#d6fb00]/10 active:scale-[0.98] flex items-center justify-center gap-2">
                    Simpan Pendaftaran <i class="fa-solid fa-arrow-right text-xs"></i>
                </button>
                <a href="<?php echo base_url(); ?>" id="btn_batal_registrasi"
                    class="w-full sm:w-auto px-6 py-3 bg-[#d6fb00]/5 hover:bg-[#d6fb00]/8 text-zinc-400 hover:text-white border border-[#d6fb00]/20 font-bold text-xs uppercase tracking-wider rounded-xl transition-all text-center">
                    Batal
                </a>
            </div>

        </form>
    </div>
</section>

<script>
function handleKategoriChange(val) {
    const secPengembang = document.getElementById('section_pengembang');
    const secMahasiswa = document.getElementById('section_mahasiswa');
    
    const inputIdPengembang = document.getElementById('file_identitas_pengembang');
    const inputIjinPengembang = document.getElementById('file_ijin_pengembang');
    const inputIdMahasiswa = document.getElementById('file_identitas_mahasiswa');
    const inputSuratKet = document.getElementById('file_surat_keterangan');

    if (val === 'Pengembang') {
        secPengembang.classList.remove('hidden');
        secMahasiswa.classList.add('hidden');
        
        inputIdPengembang.required = true;
        inputIjinPengembang.required = true;
        inputIdMahasiswa.required = false;
        inputSuratKet.required = false;

    } else if (val === 'Mahasiswa Magang') {
        secPengembang.classList.add('hidden');
        secMahasiswa.classList.remove('hidden');
        
        inputIdPengembang.required = false;
        inputIjinPengembang.required = false;
        inputIdMahasiswa.required = true;
        inputSuratKet.required = true;

    } else {
        secPengembang.classList.add('hidden');
        secMahasiswa.classList.add('hidden');
        
        inputIdPengembang.required = false;
        inputIjinPengembang.required = false;
        inputIdMahasiswa.required = false;
        inputSuratKet.required = false;
    }
}
</script>