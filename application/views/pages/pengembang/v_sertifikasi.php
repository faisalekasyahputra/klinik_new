<section class="w-full max-w-3xl mx-auto bg-[#0b0c10]/75 border border-zinc-800/50 rounded-[2.5rem] p-6 sm:p-10 backdrop-blur-xl shadow-2xl z-10 relative mt-8 text-left">
    
    <?php if($this->session->flashdata('success_alert')): ?>
    <div class="bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-2xl p-4 mb-8 flex items-center gap-3.5">
        <div class="w-10 h-10 rounded-xl bg-emerald-500/10 flex items-center justify-center text-lg shrink-0">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
            <h4 class="text-sm font-bold text-white">Formulir Berhasil Dikirim!</h4>
            <p class="text-xs text-zinc-400 mt-0.5">Data Anda telah terekam secara sah di sistem database Disperakim Jawa Tengah.</p>
        </div>
    </div>
    <?php endif; ?>

    <div class="flex flex-col sm:flex-row justify-between items-start gap-4 border-b border-zinc-800/80 pb-6 mb-6">
        <div>
            <span class="text-[#d6fb00] font-extrabold text-[10px] uppercase tracking-widest block mb-1">Tanda Terima Pendaftaran</span>
            <h2 class="text-2xl font-black text-white tracking-tight">SRP2-REG-<?php echo sprintf("%05d", $pendaftar->id); ?></h2>
            <p class="text-zinc-500 text-xs mt-1">Didaftarkan pada: <?php echo date('d M Y, H:i', strtotime($pendaftar->created_at)); ?> WIB</p>
        </div>
        
        <div>
            <?php if($pendaftar->status_verifikasi == 'Pending'): ?>
                <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-[#d6fb00]/10 border border-[#d6fb00]/20 text-[#d6fb00] font-bold text-xs uppercase tracking-wider">
                    <span class="w-1.5 h-1.5 rounded-full bg-[#d6fb00] animate-pulse"></span>
                    Dalam Peninjauan
                </span>
            <?php elseif($pendaftar->status_verifikasi == 'Diterima'): ?>
                <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 font-bold text-xs uppercase tracking-wider">
                    <i class="fa-solid fa-circle-check text-xs"></i> Verified / Lolos
                </span>
            <?php else: ?>
                <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full bg-red-500/10 border border-red-500/30 text-red-400 font-bold text-xs uppercase tracking-wider">
                    <i class="fa-solid fa-circle-xmark text-xs"></i> Ditolak
                </span>
            <?php endif; ?>
        </div>
    </div>

    <?php if($pendaftar->status_verifikasi == 'Ditolak' && !empty($pendaftar->catatan_admin)): ?>
    <div class="bg-red-500/5 border border-red-500/20 rounded-2xl p-4 mb-6 text-xs sm:text-sm text-zinc-400">
        <strong class="text-red-400 block mb-1"><i class="fa-solid fa-triangle-exclamation mr-1"></i> Catatan Koreksi Admin:</strong>
        <?php echo htmlspecialchars($pendaftar->catatan_admin); ?>
    </div>
    <?php endif; ?>

    <div class="space-y-6">
        
        <div>
            <h3 class="text-cyan-400 font-bold uppercase tracking-wider text-[11px] border-b border-zinc-800 pb-1.5 mb-3">
                Identitas Peserta Bimtek
            </h3>
            <table class="w-full text-xs sm:text-sm text-zinc-300 border-separate border-spacing-y-2.5">
                <tbody>
                    <tr>
                        <td class="w-1/3 text-zinc-500 font-medium">Nama Lengkap</td>
                        <td class="text-white font-bold uppercase">: <?php echo htmlspecialchars($pendaftar->nama_peserta); ?></td>
                    </tr>
                    <tr>
                        <td class="text-zinc-500 font-medium">NIK KTP</td>
                        <td>: <?php echo htmlspecialchars($pendaftar->nik_ktp); ?></td>
                    </tr>
                    <tr>
                        <td class="text-zinc-500 font-medium">Jabatan Perusahaan</td>
                        <td>: 
                            <?php 
                                $jabatan_labels = [
                                    'direktur_utama'   => 'Direktur Utama / Pemilik',
                                    'manajer_proyek'   => 'Manajer Proyek / Direktur Teknis',
                                    'penanggung_jawab' => 'Penanggung Jawab Lapangan',
                                    'staf_legal'       => 'Staf Legal / Perizinan'
                                ];
                                echo isset($jabatan_labels[$pendaftar->jabatan]) ? $jabatan_labels[$pendaftar->jabatan] : $pendaftar->jabatan;
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="text-zinc-500 font-medium">WhatsApp / Email</td>
                        <td>: <?php echo htmlspecialchars($pendaftar->no_whatsapp); ?> / <span class="text-zinc-400 underline"><?php echo htmlspecialchars($pendaftar->email); ?></span></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div>
            <h3 class="text-cyan-400 font-bold uppercase tracking-wider text-[11px] border-b border-zinc-800 pb-1.5 mb-3">
                Legalitas Perusahaan
            </h3>
            <table class="w-full text-xs sm:text-sm text-zinc-300 border-separate border-spacing-y-2.5">
                <tbody>
                    <tr>
                        <td class="w-1/3 text-zinc-500 font-medium">Nama Badan Usaha</td>
                        <td class="text-white font-bold uppercase">: <?php echo htmlspecialchars($pendaftar->nama_perusahaan); ?></td>
                    </tr>
                    <tr>
                        <td class="text-zinc-500 font-medium">Nomor NIB</td>
                        <td>: <?php echo htmlspecialchars($pendaftar->nib); ?></td>
                    </tr>
                    <tr>
                        <td class="text-zinc-500 font-medium">Asosiasi Diikuti</td>
                        <td class="uppercase">: <?php echo htmlspecialchars($pendaftar->asosiasi); ?></td>
                    </tr>
                    <tr>
                        <td class="text-zinc-500 font-medium">No. KTA Asosiasi</td>
                        <td>: <?php echo htmlspecialchars($pendaftar->no_keanggotaan); ?></td>
                    </tr>
                    <tr>
                        <td class="text-zinc-500 font-medium">Alamat Kantor</td>
                        <td class="leading-relaxed">: <?php echo nl2br(htmlspecialchars($pendaftar->alamat_kantor)); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div>
            <h3 class="text-cyan-400 font-bold uppercase tracking-wider text-[11px] border-b border-zinc-800 pb-1.5 mb-3">
                Status Unggah Berkas Pendukung
            </h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1 text-xs">
                <div class="flex items-center justify-between p-3 rounded-xl bg-[#d6fb00]/5 border border-zinc-800">
                    <span class="text-zinc-400 font-medium"><i class="fa-solid fa-file-invoice text-[#005e6a] mr-2"></i>Scan KTP Peserta</span>
                    <a href="<?php echo base_url('uploads/srp2_documents/' . $pendaftar->file_ktp); ?>" target="_blank" class="text-cyan-400 font-bold hover:underline">Lihat Berkas</a>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl bg-[#d6fb00]/5 border border-zinc-800">
                    <span class="text-zinc-400 font-medium"><i class="fa-solid fa-address-card text-[#005e6a] mr-2"></i>KTA Asosiasi</span>
                    <a href="<?php echo base_url('uploads/srp2_documents/' . $pendaftar->file_kta); ?>" target="_blank" class="text-cyan-400 font-bold hover:underline">Lihat Berkas</a>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl bg-[#d6fb00]/5 border border-zinc-800">
                    <span class="text-zinc-400 font-medium"><i class="fa-solid fa-file-pdf text-red-400 mr-2"></i>NIB Perusahaan</span>
                    <a href="<?php echo base_url('uploads/srp2_documents/' . $pendaftar->file_nib); ?>" target="_blank" class="text-cyan-400 font-bold hover:underline">Lihat Berkas</a>
                </div>
                <div class="flex items-center justify-between p-3 rounded-xl bg-[#d6fb00]/5 border border-zinc-800">
                    <span class="text-zinc-400 font-medium"><i class="fa-solid fa-file-signature text-zinc-500 mr-2"></i>Surat Tugas Khusus</span>
                    <?php if($pendaftar->file_surat_tugas): ?>
                        <a href="<?php echo base_url('uploads/srp2_documents/' . $pendaftar->file_surat_tugas); ?>" target="_blank" class="text-cyan-400 font-bold hover:underline">Lihat Berkas</a>
                    <?php else: ?>
                        <span class="text-zinc-500 font-medium italic">Tidak Diperlukan</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>

    <div class="border-t border-zinc-800/80 mt-8 pt-6 flex flex-col sm:flex-row gap-3 items-center justify-between">
        <p class="text-[11px] text-zinc-500 leading-relaxed text-center sm:text-left">
            * Undangan Bimtek & Koordinasi gelombang terbaru akan dikirim otomatis melalui WhatsApp dan Email terdaftar setelah verifikasi manual selesai oleh Admin Disperakim Jateng.
        </p>
        <div class="flex gap-3 w-full sm:w-auto shrink-0">
            <button onclick="window.print()" class="w-full sm:w-auto bg-zinc-800 hover:bg-zinc-700 text-white font-bold text-xs uppercase tracking-wider px-4 py-3 rounded-xl transition-colors flex items-center justify-center gap-2">
                <i class="fa-solid fa-print"></i> Cetak Resi
            </button>
            <a href="<?php echo base_url(); ?>" class="w-full sm:w-auto bg-[#d6fb00] hover:bg-[#c2e600] text-[#0a1a1f] font-extrabold text-xs uppercase tracking-wider px-5 py-3 rounded-xl transition-all duration-300 text-center">
                Kembali
            </a>
        </div>
    </div>
</section>